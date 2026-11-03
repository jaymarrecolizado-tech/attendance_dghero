<?php
declare(strict_types=1);

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

use App\Services\Database;
use App\Services\EventContext;
use App\Services\GoogleDriveClient;
use App\Services\PrivacyArchive;
use App\Services\PrivacyJobTable;
use App\Services\PrivacyNotice;
use App\Services\PrivacyNoticeTable;
use App\Services\PrivacyPurgeService;

putenv('MAIL_MODE=log');
putenv('PRIVACY_BACKUP_TO=staff-privacy@test.local');
putenv('PRIVACY_SUMMARY_TO=summary-privacy@test.local');
putenv('APP_URL=https://digitalhero.dictr2.cloud');
putenv('PRIVACY_BACKUP_PASSPHRASE=test-pass');

Database::migrate();
$pdo = Database::pdo();

$failed = 0;
function assertTrue(bool $cond, string $msg): void
{
    global $failed;
    echo ($cond ? 'PASS  ' : 'FAIL  ') . $msg . PHP_EOL;
    if (!$cond) {
        $failed++;
    }
}

$pdo->exec("DELETE FROM attendance WHERE event_id IN (SELECT id FROM events WHERE slug LIKE '_privacy-test-%')");
$pdo->exec("DELETE FROM participants WHERE event_id IN (SELECT id FROM events WHERE slug LIKE '_privacy-test-%')");
$pdo->exec("DELETE FROM coa_sends WHERE event_id IN (SELECT id FROM events WHERE slug LIKE '_privacy-test-%')");
$pdo->exec("DELETE FROM privacy_notices WHERE event_id IN (SELECT id FROM events WHERE slug LIKE '_privacy-test-%')");
$pdo->exec("DELETE FROM privacy_jobs WHERE event_id IN (SELECT id FROM events WHERE slug LIKE '_privacy-test-%')");
$pdo->exec("DELETE FROM events WHERE slug LIKE '_privacy-test-%'");

$mkUuid = static function (): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
};

$now = PrivacyPurgeService::nowManila();
$oldEnd = $now->modify('-16 days')->format('Y-m-d H:i:s');
$recentEnd = $now->modify('-1 day')->format('Y-m-d H:i:s');
$start = $now->modify('-20 days')->format('Y-m-d H:i:s');

$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status, starts_at, ends_at) VALUES ('_Privacy Due','_privacy-test-due',0,0,'closed',?,?)")
    ->execute([$start, $oldEnd]);
$dueId = (int)$pdo->lastInsertId();
$pdo->prepare('UPDATE events SET slug=? WHERE id=?')->execute(['_privacy-test-due-' . $dueId, $dueId]);

$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status, starts_at, ends_at) VALUES ('_Privacy Fresh','_privacy-test-fresh',0,0,'open',?,?)")
    ->execute([$start, $recentEnd]);
$freshId = (int)$pdo->lastInsertId();
$pdo->prepare('UPDATE events SET slug=? WHERE id=?')->execute(['_privacy-test-fresh-' . $freshId, $freshId]);

$pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name, privacy_consent_at, privacy_notice_version) VALUES (?,?,?,?,?,?,?)')
    ->execute([$dueId, $mkUuid(), '_privacy_one@test.local', 'Ana', 'Cruz', $now->format('Y-m-d H:i:s'), PrivacyNotice::VERSION]);
$pid1 = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO participants (event_id, uuid, first_name, last_name) VALUES (?,?,?,?)')
    ->execute([$dueId, $mkUuid(), 'NoEmail', 'Guest']);
$pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name) VALUES (?,?,?,?,?)')
    ->execute([$freshId, $mkUuid(), '_privacy_fresh@test.local', 'Keep', 'Me']);
$pdo->prepare('INSERT INTO attendance (participant_id, attendance_date, time_in, signature_path, event_id, status) VALUES (?,?,?,?,?,?)')
    ->execute([$pid1, $now->modify('-16 days')->format('Y-m-d'), '09:00:00', '', $dueId, 'present']);
$brandDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'event-branding' . DIRECTORY_SEPARATOR . $dueId;
if (!is_dir($brandDir)) {
    mkdir($brandDir, 0775, true);
}
$bannerRel = 'storage/event-branding/' . $dueId . '/banner.png';
$bannerAbs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $bannerRel);
file_put_contents($bannerAbs, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
$pdo->prepare('UPDATE events SET banner_path = ? WHERE id = ?')->execute([$bannerRel, $dueId]);
$dueRow = $pdo->query('SELECT id, banner_path, logo_path, theme_layout FROM events WHERE id = ' . $dueId)->fetch() ?: [];
$mailUrl = PrivacyNotice::registrationPhotoMailUrl($dueRow);
assertTrue($mailUrl === 'https://digitalhero.dictr2.cloud/?r=event_branding&eid=' . $dueId . '&f=banner', 'email and share link use the same public photo URL');
$htmlNoPhoto = PrivacyNotice::deletionEmailHtml('Ana', 'Demo', null);
assertTrue(!str_contains($htmlNoPhoto, '<img'), 'template without photo has no image');
$htmlPhoto = PrivacyNotice::deletionEmailHtml('Ana', 'Demo', 'cid:event-photo');
assertTrue(str_contains($htmlPhoto, 'cid:event-photo'), 'template can include the registration photo');
assertTrue(str_contains($htmlPhoto, 'deleted from the live database'), 'template states live deletion');
assertTrue(!str_contains($htmlPhoto, 'organizational archive'), 'deletion email does not mention the archive');

$dueIds = array_map(static fn($e) => (int)$e['id'], PrivacyPurgeService::dueEvents($pdo));
assertTrue(in_array($dueId, $dueIds, true), 'event ended 16 days ago is due');
assertTrue(!in_array($freshId, $dueIds, true), 'event ended yesterday is not due');

$outbox = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'outbox';
$before = is_dir($outbox) ? count(glob($outbox . '/*.eml') ?: []) : 0;

GoogleDriveClient::$uploadOverride = static function (string $path, string $name): ?array {
    return null;
};
$fail = PrivacyPurgeService::purgeEvent($dueId);
assertTrue(!empty($fail['ok']), 'purge succeeds when Drive is down');
assertTrue(empty($fail['drive']), 'Drive failure is recorded');
$left = (int)$pdo->query('SELECT COUNT(*) FROM participants WHERE event_id = ' . $dueId)->fetchColumn();
assertTrue($left === 0, 'live participants deleted even if Drive is down');
$freshLeft = (int)$pdo->query('SELECT COUNT(*) FROM participants WHERE event_id = ' . $freshId)->fetchColumn();
assertTrue($freshLeft === 1, 'other events are not purged');
$job = $pdo->query('SELECT * FROM privacy_jobs WHERE event_id = ' . $dueId . ' ORDER BY id DESC LIMIT 1')->fetch();
assertTrue($job && $job['status'] === 'purged_drive_pending', 'job marked Drive pending');
assertTrue(!empty($job['backup_path']) && is_file((string)$job['backup_path']), 'zip kept on server');
assertTrue(PrivacyArchive::isLocalBackupFile((string)$job['backup_path']), 'zip is under privacy-backups');
$staffLocal = (int)$pdo->query("SELECT COUNT(*) FROM privacy_notices WHERE event_id = {$dueId} AND kind = 'staff_fail'")->fetchColumn();
assertTrue($staffLocal >= 1, 'staff notified that Drive did not take the zip');
$ev = $pdo->query('SELECT * FROM events WHERE id = ' . $dueId)->fetch();
assertTrue(!empty($ev['privacy_purged_at']), 'event marked purged');
assertTrue(!EventContext::isPublicOpen($ev), 'purged event is closed to public register');

$after = is_dir($outbox) ? count(glob($outbox . '/*.eml') ?: []) : 0;
assertTrue($after > $before, 'deletion mail written to log outbox');
$skipped = (int)$pdo->query("SELECT COUNT(*) FROM privacy_notices WHERE event_id = {$dueId} AND status = 'skipped'")->fetchColumn();
assertTrue($skipped >= 1, 'no-email person is skipped not mailed');
$sentPart = (int)$pdo->query("SELECT COUNT(*) FROM privacy_notices WHERE event_id = {$dueId} AND kind = 'participant' AND status = 'sent'")->fetchColumn();
assertTrue($sentPart >= 1, 'participant with email was notified');
$participantMail = '';
foreach (array_reverse(glob($outbox . DIRECTORY_SEPARATOR . '*.eml') ?: []) as $eml) {
    $raw = (string)file_get_contents($eml);
    if (str_contains($raw, '_privacy_one@test.local')) {
        $participantMail = $raw;
        break;
    }
}
assertTrue(str_contains($participantMail, 'src="https://digitalhero.dictr2.cloud/'), 'deletion mail uses a public image URL');
assertTrue(!str_contains($participantMail, 'cid:event-photo'), 'deletion mail does not CID-attach the photo');
assertTrue(!str_contains($participantMail, 'Inline-event-photo:'), 'deletion mail does not add the photo as an attachment');
$summaryMail = '';
foreach (array_reverse(glob($outbox . DIRECTORY_SEPARATOR . '*.eml') ?: []) as $eml) {
    $raw = (string)file_get_contents($eml);
    if (str_contains($raw, 'summary-privacy@test.local')) {
        $summaryMail = $raw;
        break;
    }
}
assertTrue(str_contains($summaryMail, 'sent, ') && str_contains($summaryMail, ' failed'), 'summary subject has sent/failed counts');
assertTrue(str_contains($summaryMail, 'Participants'), 'summary table includes participants');
$skippedTab = PrivacyNoticeTable::fromRequest($pdo, ['per' => 'all'], 'skipped');
assertTrue((int)$skippedTab['pager']['total'] >= 1, 'skipped notices are listed');

GoogleDriveClient::$downloadOverride = static function (string $id, string $dest) use ($job): bool {
    return false;
};
$zipKeep = (string)$job['backup_path'];
$rest = PrivacyPurgeService::restoreEvent($dueId);
assertTrue(!empty($rest['ok']), 'All Father restore from server zip');
assertTrue((int)($rest['restored'] ?? 0) >= 1, 'at least the emailed person is restored');
$back = (int)$pdo->query('SELECT COUNT(*) FROM participants WHERE event_id = ' . $dueId)->fetchColumn();
assertTrue($back >= 1, 'roster is live again');
$held = $pdo->query('SELECT privacy_purged_at, privacy_restore_hold_until FROM events WHERE id = ' . $dueId)->fetch();
assertTrue($held && $held['privacy_purged_at'] === null, 'purged flag cleared after restore');
assertTrue(!empty($held['privacy_restore_hold_until']), 'restore hold is set');
$dueAgain = array_map(static fn($e) => (int)$e['id'], PrivacyPurgeService::dueEvents($pdo));
assertTrue(!in_array($dueId, $dueAgain, true), 'cron does not re-wipe a restored event on hold');

GoogleDriveClient::$uploadOverride = static function (string $path, string $name): array {
    return ['id' => 'drive-file-1', 'url' => 'https://drive.google.com/file/d/drive-file-1/view'];
};
$pdo->prepare('UPDATE events SET privacy_restore_hold_until = NULL, ends_at = ? WHERE id = ?')
    ->execute([$oldEnd, $dueId]);
$okDrive = PrivacyPurgeService::purgeEvent($dueId);
assertTrue(!empty($okDrive['ok']) && !empty($okDrive['drive']), 'purge uploads when Drive mock succeeds');
$ev2 = $pdo->query('SELECT privacy_backup_drive_id, privacy_backup_path FROM events WHERE id = ' . $dueId)->fetch();
assertTrue(($ev2['privacy_backup_drive_id'] ?? '') === 'drive-file-1', 'Drive id stored');
assertTrue(($ev2['privacy_backup_path'] ?? null) === null || $ev2['privacy_backup_path'] === '', 'local zip removed after Drive ok');
if (is_file($zipKeep)) {
    @unlink($zipKeep);
}

GoogleDriveClient::$uploadOverride = null;
GoogleDriveClient::$downloadOverride = null;
putenv('GOOGLE_DRIVE_ENABLED=false');
$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status, starts_at, ends_at) VALUES ('_Privacy Vps','_privacy-test-vps',0,0,'closed',?,?)")
    ->execute([$start, $oldEnd]);
$vpsId = (int)$pdo->lastInsertId();
$pdo->prepare('UPDATE events SET slug=? WHERE id=?')->execute(['_privacy-test-vps-' . $vpsId, $vpsId]);
$pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name) VALUES (?,?,?,?,?)')
    ->execute([$vpsId, $mkUuid(), 'staff-privacy@test.local', 'Vps', 'Keep']);
$vps = PrivacyPurgeService::purgeEvent($vpsId);
assertTrue(!empty($vps['ok']) && empty($vps['drive']), 'VPS-only purge skips Drive');
$jobV = $pdo->query('SELECT * FROM privacy_jobs WHERE event_id = ' . $vpsId . ' ORDER BY id DESC LIMIT 1')->fetch();
assertTrue($jobV && $jobV['status'] === 'purged', 'VPS-only job is purged not Drive-pending');
assertTrue(PrivacyArchive::isLocalBackupFile((string)$jobV['backup_path']), 'VPS-only zip kept for download');
$localStaff = (int)$pdo->query("SELECT COUNT(*) FROM privacy_notices WHERE event_id = {$vpsId} AND kind = 'staff_local'")->fetchColumn();
assertTrue($localStaff >= 1, 'staff told zip is on the server');
assertTrue(PrivacyPurgeService::retryPendingDrive() === 0, 'no Drive retry when uploads are off');
if (!empty($jobV['backup_path']) && is_file((string)$jobV['backup_path'])) {
    @unlink((string)$jobV['backup_path']);
}
if (isset($bannerAbs) && is_file($bannerAbs)) {
    @unlink($bannerAbs);
}

$nowSql = $now->format('Y-m-d H:i:s');
$insJob = $pdo->prepare('INSERT INTO privacy_jobs (event_id, status, participant_count, mailed, skipped_no_email, created_at, updated_at) VALUES (?,?,?,?,?,?,?)');
for ($i = 0; $i < 12; $i++) {
    $insJob->execute([$vpsId, 'paged-' . $i, $i, 0, 0, $nowSql, $nowSql]);
}
$page1 = PrivacyJobTable::fromRequest($pdo, ['q' => 'paged-', 'per' => '10', 'sort' => 'people', 'dir' => 'desc']);
assertTrue((int)$page1['pager']['total'] === 12, 'search finds twelve paged jobs');
assertTrue(count($page1['rows']) === 10, 'page size 10 returns ten rows');
assertTrue((int)$page1['rows'][0]['participant_count'] === 11, 'sort people desc');
$page2 = PrivacyJobTable::fromRequest($pdo, ['q' => 'paged-', 'per' => '10', 'page' => '2', 'sort' => 'people', 'dir' => 'desc']);
assertTrue(count($page2['rows']) === 2, 'second page has the remainder');
$all = PrivacyJobTable::fromRequest($pdo, ['q' => 'paged-', 'per' => 'all']);
assertTrue(count($all['rows']) === 12, 'all shows every matching job');

if ($failed > 0) {
    echo "FAILED {$failed}\n";
    exit(1);
}
echo "ALL OK\n";
exit(0);
