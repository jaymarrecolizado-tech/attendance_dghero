<?php
declare(strict_types=1);

/**
 * Certificate of Appearance smoke test (Plan#10).
 * Verifies: the signed-scan hook generates the dual-copy PDF and the mail
 * lands in the log outbox; a disabled event generates nothing; the resend
 * service path returns true. Uses MAIL_MODE=log so nothing real is sent.
 */

require __DIR__ . '/../config/bootstrap.php';

spl_autoload_register(static function ($class): void {
    $prefix = 'App\\';
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = $base . $rel . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Controllers\AttendanceController;
use App\Services\AuthService;
use App\Services\Database;
use App\Services\Logger;

putenv('MAIL_MODE=log');

Database::migrate();
$pdo = Database::pdo();

$failed = 0;
$passed = 0;
function assertTrue(bool $cond, string $msg): void
{
    global $failed, $passed;
    echo ($cond ? 'PASS  ' : 'FAIL  ') . $msg . PHP_EOL;
    if (!$cond) { $failed++; }
}

// Fixture: one CoA-enabled event, one CoA-disabled event, participants on both.
$pdo->exec("DELETE FROM attendance WHERE participant_id IN (SELECT id FROM participants WHERE email LIKE '_coa_%@test.local')");
$pdo->exec("DELETE FROM participants WHERE email LIKE '_coa_%@test.local'");
$pdo->exec("DELETE FROM events WHERE slug LIKE '_coa-test-%'");

$starts = date('Y-m-d H:i:s', time() - 3600);
$ends = date('Y-m-d H:i:s', time() + 86400);
$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status, starts_at, ends_at, coa_enabled, coa_venue, coa_purpose, coa_signatory_name, coa_signatory_title)
    VALUES ('_CoA Test Event','_coa-test-on',0,0,'open',?,?,1,'Test Convention Center','for the attendance record','Juan Signatory','Regional Director')")
    ->execute([$starts, $ends]);
$eventIdOn = (int)$pdo->lastInsertId();
$slugOn = '_coa-test-on-' . $eventIdOn;
$pdo->prepare('UPDATE events SET slug=? WHERE id=?')->execute([$slugOn, $eventIdOn]);

$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status, starts_at, ends_at, coa_enabled) VALUES ('_CoA Off Event','_coa-test-off',0,0,'open',?,?,0)")
    ->execute([$starts, $ends]);
$eventIdOff = (int)$pdo->lastInsertId();
$slugOff = '_coa-test-off-' . $eventIdOff;
$pdo->prepare('UPDATE events SET slug=? WHERE id=?')->execute([$slugOff, $eventIdOff]);

$mkUuid = static function (): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
};
$uuidOn = $mkUuid();
$uuidOff = $mkUuid();
$pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name) VALUES (?,?,?,?,?)')
    ->execute([$eventIdOn, $uuidOn, '_coa_on@test.local', 'Maria', 'Santos']);
$pidOn = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name) VALUES (?,?,?,?,?)')
    ->execute([$eventIdOff, $uuidOff, '_coa_off@test.local', 'Paolo', 'Reyes']);
$pidOff = (int)$pdo->lastInsertId();

// Submit attendance through the controller helper (same path as the kiosk).
AuthService::logoutLocal();
$_SESSION['staff'] = true;
$_SESSION['csrf'] = bin2hex(random_bytes(32));
$ctrl = new AttendanceController();
$outbox = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'outbox';
$mailsBefore = is_dir($outbox) ? count(glob($outbox . '/*.eml') ?: []) : 0;

$res = $ctrl->submitJsonForTest(['uuid' => $uuidOn, 'signature' => 'data:image/png;base64,AAAA', 'e' => $slugOn], $_SESSION['csrf']);
assertTrue(($res['ok'] ?? false) === true, 'attendance accepted on CoA-enabled event');

$pdf = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'coa' . DIRECTORY_SEPARATOR . $eventIdOn . DIRECTORY_SEPARATOR . 'coa_' . $pidOn . '_' . date('Ymd') . '.pdf';
assertTrue(is_file($pdf), 'dual-copy CoA PDF generated under storage/coa/{eventId}');
if (is_file($pdf)) {
    $bytes = filesize($pdf);
    assertTrue($bytes > 2000, 'CoA PDF has real content (' . $bytes . ' bytes)');
    assertTrue(substr(file_get_contents($pdf) ?: '', 0, 4) === "%PDF", 'CoA file is a PDF');
}

$mailsAfter = is_dir($outbox) ? count(glob($outbox . '/*.eml') ?: []) : 0;
assertTrue($mailsAfter > $mailsBefore, 'CoA email written to the log outbox');

// Disabled event: attendance accepted, but no CoA PDF appears.
$res2 = $ctrl->submitJsonForTest(['uuid' => $uuidOff, 'signature' => 'data:image/png;base64,AAAA', 'e' => $slugOff], $_SESSION['csrf']);
assertTrue(($res2['ok'] ?? false) === true, 'attendance accepted on CoA-disabled event');
$pdfOff = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'coa' . DIRECTORY_SEPARATOR . $eventIdOff;
assertTrue(!is_dir($pdfOff) || count(glob($pdfOff . '/*.pdf') ?: []) === 0, 'no CoA generated for a disabled event');

// Resend service path returns true (log-mode mail).
$sent = \App\Services\CoaService::maybeSendFor($eventIdOn, $pidOn, date('Y-m-d'));
assertTrue($sent === true, 'resend path returns true (regenerate + log-mode mail)');

// Plan#11: the send was recorded on an auto batch, and the monitor queue
// and manual batch actions work.
$row = $pdo->prepare('SELECT s.*, b.source FROM coa_sends s JOIN coa_batches b ON b.id = s.batch_id WHERE s.participant_id = ? AND s.event_id = ? ORDER BY s.id DESC LIMIT 1');
$row->execute([$pidOn, $eventIdOn]);
$sendRow = $row->fetch();
assertTrue($sendRow && $sendRow['status'] === 'sent', 'coa_sends row recorded as sent');
assertTrue($sendRow && $sendRow['source'] === 'auto', 'auto batch attached to the scan send');
assertTrue($sendRow && (string)$sendRow['event_name_snapshot'] !== '' && (string)$sendRow['venue_snapshot'] !== '', 'send row snapshots event title and venue');
$sendId = (int)$sendRow['id'];
$batchId = (int)$sendRow['batch_id'];

// Force the row failed, then queue it through the monitor action.
$pdo->prepare("UPDATE coa_sends SET status = 'failed' WHERE id = ?")->execute([$sendId]);
$adminId2 = (int)$pdo->query("SELECT id FROM admins WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1")->fetchColumn();
AuthService::establishSession(['id' => $adminId2, 'username' => '_coa_admin_probe', 'role' => 'admin', 'display_name' => 'Probe']);
$_SESSION['current_event_id'] = $eventIdOn;
$mon = new \App\Controllers\AdminCoaMonitorController();
$freshCsrf = static function (): string {
    $t = function_exists('csrf_token') ? csrf_token() : bin2hex(random_bytes(16));
    $_SESSION['csrf'] = $t;
    return $t;
};
$_POST = ['event_id' => (string)$eventIdOn, 'csrf' => $freshCsrf()];
ob_start();
$mon->queueFailed();
ob_end_clean();
$st = $pdo->prepare('SELECT status FROM coa_sends WHERE id = ?');
$st->execute([$sendId]);
assertTrue((string)$st->fetchColumn() === 'queued', 'queue-failed moves the row to queued');

// Resend queued through the monitor action; the row comes back as sent.
$_POST = ['event_id' => (string)$eventIdOn, 'csrf' => $freshCsrf()];
ob_start();
$mon->resendQueued();
ob_end_clean();
$st->execute([$sendId]);
assertTrue((string)$st->fetchColumn() === 'sent', 'resend-queued sends the queued row');

// Manual batch: a second attendee with attendance but no send gets one.
$uuid2 = $mkUuid();
$pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name) VALUES (?,?,?,?,?)')
    ->execute([$eventIdOn, $uuid2, '_coa_second@test.local', 'Andres', 'Bonifacio']);
$pid2 = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO attendance (participant_id, attendance_date, time_in, signature_path, event_id, status) VALUES (?,?,?,?,?,?)')
    ->execute([$pid2, date('Y-m-d'), date('H:i:s'), '', $eventIdOn, 'present']);
$_POST = ['event_id' => (string)$eventIdOn, 'attendance_date' => date('Y-m-d'), 'csrf' => $freshCsrf()];
ob_start();
$mon->sendNew();
ob_end_clean();
$st = $pdo->prepare('SELECT s.status, b.source FROM coa_sends s JOIN coa_batches b ON b.id = s.batch_id WHERE s.participant_id = ? AND s.event_id = ?');
$st->execute([$pid2, $eventIdOn]);
$row2 = $st->fetch();
assertTrue($row2 && $row2['status'] === 'sent' && $row2['source'] === 'manual', 'send-new batches the missing attendee (manual source)');

// Templates: save, apply to event, and list.
$pdo->prepare('INSERT INTO coa_signatories (name, title, signature_path, created_at, updated_at) VALUES (?,?,?,?,?)')
    ->execute(['_CoA Signatory', 'Director', '', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
$sigId = (int)$pdo->lastInsertId();
assertTrue($sigId > 0, 'signatory row created');

$_POST = [
    'name' => '_CoA Test Template',
    'venue' => 'Template Hall',
    'purpose' => 'Template purpose',
    'particulars' => "Lodging - PROVIDED dorm\nMeals - PROVIDED full board",
    'signatory_id' => (string)$sigId,
    'csrf' => $freshCsrf(),
];
ob_start();
$mon->templateSave();
ob_end_clean();
$tplId = (int)$pdo->query("SELECT id FROM coa_templates WHERE name = '_CoA Test Template' ORDER BY id DESC LIMIT 1")->fetchColumn();
assertTrue($tplId > 0, 'template saved');
$tplRow = $pdo->prepare('SELECT signatory_id, signatory_name FROM coa_templates WHERE id = ?');
$tplRow->execute([$tplId]);
$tplRow = $tplRow->fetch();
assertTrue((int)$tplRow['signatory_id'] === $sigId && $tplRow['signatory_name'] === '_CoA Signatory', 'template copies signatory name from library');

$_POST = ['template_id' => (string)$tplId, 'event_id' => (string)$eventIdOn, 'csrf' => $freshCsrf()];
ob_start();
$mon->templateApply();
ob_end_clean();
$evRow = $pdo->prepare('SELECT coa_venue, coa_signatory_name FROM events WHERE id = ?');
$evRow->execute([$eventIdOn]);
$evRow = $evRow->fetch();
assertTrue($evRow['coa_venue'] === 'Template Hall' && $evRow['coa_signatory_name'] === '_CoA Signatory', 'template applied to the event');

$uuid3 = $mkUuid();
$pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name) VALUES (?,?,?,?,?)')
    ->execute([$eventIdOn, $uuid3, '_coa_third@test.local', 'Apolinario', 'Mabini']);
$pid3 = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO attendance (participant_id, attendance_date, time_in, signature_path, event_id, status) VALUES (?,?,?,?,?,?)')
    ->execute([$pid3, date('Y-m-d'), date('H:i:s'), '', $eventIdOn, 'present']);
$future = date('Y-m-d\TH:i', time() + 3600);
$_POST = [
    'event_id' => (string)$eventIdOn,
    'attendance_date' => date('Y-m-d'),
    'template_id' => (string)$tplId,
    'participant_ids' => [(string)$pid3],
    'send_at' => $future,
    'do' => 'schedule',
    'csrf' => $freshCsrf(),
];
ob_start();
$mon->sendSelected();
ob_end_clean();
$st = $pdo->prepare('SELECT s.status, s.send_at, b.source FROM coa_sends s JOIN coa_batches b ON b.id = s.batch_id WHERE s.participant_id = ? ORDER BY s.id DESC LIMIT 1');
$st->execute([$pid3]);
$sched = $st->fetch();
assertTrue($sched && $sched['status'] === 'queued' && $sched['source'] === 'scheduled' && (string)$sched['send_at'] !== '', 'schedule selected queues a future send');
$schedId = (int)$pdo->query('SELECT id FROM coa_sends WHERE participant_id = ' . $pid3 . ' ORDER BY id DESC LIMIT 1')->fetchColumn();
$pdo->prepare("UPDATE coa_sends SET send_at = ? WHERE id = ?")->execute([date('Y-m-d H:i:s', time() - 60), $schedId]);
$due = \App\Services\CoaService::processDue(50);
assertTrue($due['processed'] >= 1, 'processDue picks the due queued row');
$st->execute([$pid3]);
$afterDue = $st->fetch();
assertTrue($afterDue && $afterDue['status'] === 'sent', 'processDue sends the due row');

$uuid4 = $mkUuid();
$pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name) VALUES (?,?,?,?,?)')
    ->execute([$eventIdOn, $uuid4, '_coa_fourth@test.local', 'Gabriela', 'Silang']);
$pid4 = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO attendance (participant_id, attendance_date, time_in, signature_path, event_id, status) VALUES (?,?,?,?,?,?)')
    ->execute([$pid4, date('Y-m-d'), date('H:i:s'), '', $eventIdOn, 'present']);
$_POST = [
    'event_id' => (string)$eventIdOn,
    'attendance_date' => date('Y-m-d'),
    'template_id' => '0',
    'participant_ids' => [(string)$pid4],
    'do' => 'send',
    'csrf' => $freshCsrf(),
];
ob_start();
$mon->sendSelected();
ob_end_clean();
$st = $pdo->prepare('SELECT status FROM coa_sends WHERE participant_id = ? ORDER BY id DESC LIMIT 1');
$st->execute([$pid4]);
assertTrue((string)$st->fetchColumn() === 'sent', 'compose send selected without a template uses event CoA settings');

$_POST = [
    'event_id' => (string)$eventIdOn,
    'attendance_date' => date('Y-m-d'),
    'template_id' => (string)$tplId,
    'participant_ids' => [(string)$pid4],
    'send_at' => date('Y-m-d\TH:i', time() + 7200),
    'do' => 'schedule',
    'csrf' => $freshCsrf(),
];
ob_start();
$mon->sendSelected();
ob_end_clean();
$batchToCancel = (int)$pdo->query("SELECT batch_id FROM coa_sends WHERE participant_id = $pid4 AND status = 'queued' ORDER BY id DESC LIMIT 1")->fetchColumn();
assertTrue($batchToCancel > 0, 'second schedule created a queued batch');
$_POST = ['batch_id' => (string)$batchToCancel, 'csrf' => $freshCsrf()];
ob_start();
$mon->cancelBatch();
ob_end_clean();
$left = (int)$pdo->query("SELECT COUNT(*) FROM coa_sends WHERE batch_id = $batchToCancel AND status = 'queued'")->fetchColumn();
assertTrue($left === 0, 'cancel queued removes still-queued rows');

// Preview: a sample PDF renders from the template.
$sample = \App\Services\CoaService::generatePreview(
    ['name' => 'Hack for Gov 5', 'coa_venue' => 'Template Hall', 'coa_purpose' => 'p', 'coa_particulars' => '', 'coa_signatory_name' => '', 'coa_signatory_title' => '', 'coa_signatory_path' => '', 'coa_logo_path' => ''],
    date('Y-m-d')
);
assertTrue($sample !== null && is_file($sample), 'template preview PDF renders');
if ($sample !== null && is_file($sample)) { @unlink($sample); }

unset($_SESSION['staff']);
$pdo->exec("DELETE FROM coa_sends WHERE event_id IN ($eventIdOn, $eventIdOff)");
$pdo->exec("DELETE FROM coa_batches WHERE event_id IN ($eventIdOn, $eventIdOff)");
$pdo->exec("DELETE FROM coa_templates WHERE name = '_CoA Test Template'");
$pdo->exec("DELETE FROM coa_signatories WHERE name = '_CoA Signatory'");
$pdo->exec("DELETE FROM attendance WHERE participant_id IN (SELECT id FROM participants WHERE email LIKE '_coa_%@test.local')");
$pdo->exec("DELETE FROM participants WHERE email LIKE '_coa_%@test.local'");
$pdo->exec("DELETE FROM events WHERE slug LIKE '_coa-test-%'");
$coaDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'coa' . DIRECTORY_SEPARATOR . $eventIdOn;
if (is_dir($coaDir)) {
    foreach (glob($coaDir . '/*') ?: [] as $f) { @unlink($f); }
    @rmdir($coaDir);
}

echo PHP_EOL . ($failed === 0 ? 'ALL OK' : $failed . ' FAILED') . PHP_EOL;
exit($failed > 0 ? 1 : 0);
