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

// Cleanup.
unset($_SESSION['staff']);
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
