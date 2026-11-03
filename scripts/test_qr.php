<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../src/Services/Uuid.php';
require __DIR__ . '/../src/Services/QrService.php';

putenv('QR_ENABLE_REMOTE=false');
$uuid = \App\Services\Uuid::v4();
$payload = 'PART|' . $uuid;
$t0 = microtime(true);
$path = \App\Services\QrService::generate($payload, $uuid);
$ms = (microtime(true) - $t0) * 1000;

if (!is_file($path)) {
    fwrite(STDERR, "QR file missing: {$path}\n");
    exit(1);
}
$info = getimagesize($path);
if ($info === false || ($info[2] ?? 0) !== IMAGETYPE_PNG) {
    fwrite(STDERR, "QR is not a PNG\n");
    exit(1);
}
if (\App\Services\QrService::needsRebuild($path)) {
    fwrite(STDERR, "QR looks like the UNAVAILABLE placeholder\n");
    exit(1);
}
if ($ms > 2000) {
    fwrite(STDERR, "QR generate too slow: {$ms} ms\n");
    exit(1);
}
echo "ok {$path} " . round($ms) . "ms\n";
