<?php
declare(strict_types=1);

/**
 * Plan#12 cron worker: send due queued CoA rows (cap 50).
 * Hostinger: * * * * * cd /home/digitalhero/htdocs/digitalhero.dictr2.cloud && php scripts/coa_process_scheduled.php
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

use App\Services\CoaService;
use App\Services\Database;

Database::migrate();
$result = CoaService::processDue(50);
echo 'processed=' . $result['processed'] . ' sent=' . $result['sent'] . PHP_EOL;
exit(0);
