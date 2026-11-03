<?php
declare(strict_types=1);

/**
 * Plan#18 daily worker: purge events past 15-day retention, retry Drive uploads.
 * Hostinger: 15 1 * * * cd /home/digitalhero/htdocs/digitalhero.dictr2.cloud && php scripts/privacy_purge_due.php
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
use App\Services\PrivacyPurgeService;

Database::migrate();
$result = PrivacyPurgeService::processDue();
echo 'purged=' . $result['purged'] . ' drive_retried=' . $result['drive_retried'] . PHP_EOL;
exit(0);
