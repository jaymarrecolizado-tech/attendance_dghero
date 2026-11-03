<?php
declare(strict_types=1);

/**
 * Copy each person's latest ink onto empty event days.
 * Usage: php scripts/signature_replicate_missing.php {event_id|slug}
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

use App\Controllers\AdminSignatureSheetController;
use App\Services\Database;

$needle = trim((string)($argv[1] ?? ''));
if ($needle === '') {
    fwrite(STDERR, "usage: php scripts/signature_replicate_missing.php {event_id|slug}\n");
    exit(1);
}

@set_time_limit(300);
$pdo = Database::pdo();
if (ctype_digit($needle)) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([(int)$needle]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE slug = ?');
    $stmt->execute([$needle]);
}
$event = $stmt->fetch();
if (!$event) {
    fwrite(STDERR, "event not found\n");
    exit(1);
}

$days = AdminSignatureSheetController::eventDays($event);
$result = AdminSignatureSheetController::applyReplicateMissing($pdo, (int)$event['id'], $days);
echo 'event=' . (int)$event['id']
    . ' people=' . $result['people']
    . ' copied=' . $result['copied']
    . ' skipped=' . $result['skipped']
    . PHP_EOL;
exit(0);
