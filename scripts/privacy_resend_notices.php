<?php
declare(strict_types=1);

/**
 * Resend participant deletion notices for one event (from privacy_notices emails).
 * Usage: php scripts/privacy_resend_notices.php {event_id} [YYYY-MM-DD]
 * Optional date: skip unless today (Asia/Manila) matches.
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

use App\Services\Database;
use App\Services\Mailer;
use App\Services\PrivacyArchive;
use App\Services\PrivacyNotice;
use App\Services\PrivacyPurgeService;

$eventId = (int)($argv[1] ?? 0);
$onlyDate = trim((string)($argv[2] ?? ''));
$now = PrivacyPurgeService::nowManila();
if ($eventId <= 0) {
    fwrite(STDERR, "usage: php scripts/privacy_resend_notices.php {event_id} [YYYY-MM-DD]\n");
    exit(1);
}
if ($onlyDate !== '' && $now->format('Y-m-d') !== $onlyDate) {
    echo 'skip date ' . $now->format('Y-m-d') . PHP_EOL;
    exit(0);
}

$lockDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'runtime';
if (!is_dir($lockDir)) {
    mkdir($lockDir, 0775, true);
}
$lock = $lockDir . DIRECTORY_SEPARATOR . 'privacy-resend-' . $eventId . '-' . $now->format('Y-m-d') . '.done';
if (is_file($lock)) {
    echo 'already sent today' . PHP_EOL;
    exit(0);
}

set_time_limit(0);
$pdo = Database::pdo();
Database::ensurePrivacyFacility($pdo);
$evStmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
$evStmt->execute([$eventId]);
$event = $evStmt->fetch();
if (!$event) {
    fwrite(STDERR, "event not found\n");
    exit(1);
}
$eventName = (string)($event['name'] ?? '');
$photoUrl = PrivacyNotice::registrationPhotoMailUrl($event);
$namesByEmail = [];
$zip = trim((string)($event['privacy_backup_path'] ?? ''));
if ($zip !== '' && is_file($zip)) {
    try {
        $pack = PrivacyArchive::extract($zip);
        foreach ($pack['participants'] as $row) {
            $email = strtolower(trim((string)($row['email'] ?? '')));
            if ($email === '') {
                $email = strtolower(trim((string)($row['office_email'] ?? '')));
            }
            if ($email !== '') {
                $namesByEmail[$email] = trim((string)($row['first_name'] ?? ''));
            }
        }
    } catch (\Throwable $e) {
        // keep empty names
    }
}

$stmt = $pdo->prepare(
    "SELECT to_email, MAX(first_name) AS first_name FROM privacy_notices
     WHERE event_id = ? AND kind = 'participant' AND to_email IS NOT NULL AND to_email <> ''
     GROUP BY to_email"
);
$stmt->execute([$eventId]);
$rows = $stmt->fetchAll() ?: [];
$sent = 0;
$failed = 0;
foreach ($rows as $row) {
    $to = trim((string)$row['to_email']);
    if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
        continue;
    }
    $first = trim((string)($row['first_name'] ?? ''));
    if ($first === '') {
        $first = $namesByEmail[strtolower($to)] ?? '';
    }
    $subject = Mailer::privacyDeletedSubject($eventName);
    $body = Mailer::privacyDeletedHtml($first, $eventName, $photoUrl !== '' ? $photoUrl : null);
    $ok = Mailer::send($to, $subject, $body, null, $eventName !== '' ? $eventName : null);
    if ($ok) {
        $sent++;
    } else {
        $failed++;
    }
}
file_put_contents($lock, $now->format('c') . " sent={$sent} failed={$failed}\n");
echo "event={$eventId} photo={$photoUrl} sent={$sent} failed={$failed}" . PHP_EOL;
exit($failed > 0 && $sent === 0 ? 1 : 0);
