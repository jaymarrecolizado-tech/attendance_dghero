<?php
declare(strict_types=1);

namespace App\Services;

class QrService
{
    private static bool $remoteFailed = false;

    public static function generate(string $payload, string $uuid): string
    {
        $path = self::pathFor($uuid);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (self::writeLocal($payload, $path)) {
            return $path;
        }
        $external = getenv('QR_EXTERNAL') === 'true';
        if (!$external && class_exists('Endroid\\QrCode\\QrCode')) {
            $qr = new \Endroid\QrCode\QrCode($payload);
            $qr->setSize(300);
            $qr->writeFile($path);
            return $path;
        }
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . rawurlencode($payload);
        if (self::remoteEnabled() && !self::$remoteFailed) {
            $img = self::fetchRemote($url);
            if ($img !== null) {
                file_put_contents($path, $img);
                return $path;
            }
            self::$remoteFailed = true;
        }
        self::generateFallback($payload, $path);
        return $path;
    }

    public static function pathFor(string $uuid): string
    {
        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'qrcodes';
        $shard = substr($uuid, 0, 2);
        return $base . DIRECTORY_SEPARATOR . $shard . DIRECTORY_SEPARATOR . $uuid . '.png';
    }

    /** True when the PNG is missing or the gray "QR UNAVAILABLE" placeholder. */
    public static function needsRebuild(string $path): bool
    {
        if (!is_file($path) || filesize($path) < 80) {
            return true;
        }
        if (!function_exists('imagecreatefrompng')) {
            return false;
        }
        $im = @imagecreatefrompng($path);
        if ($im === false) {
            return true;
        }
        $c = imagecolorat($im, 0, 0);
        imagedestroy($im);
        $r = ($c >> 16) & 255;
        $g = ($c >> 8) & 255;
        $b = $c & 255;
        return abs($r - 247) < 3 && abs($g - 247) < 3 && abs($b - 247) < 3;
    }

    public static function ensure(string $uuid, string $payload): string
    {
        $path = self::pathFor($uuid);
        if (self::needsRebuild($path)) {
            return self::generate($payload, $uuid);
        }
        return $path;
    }

    private static function writeLocal(string $payload, string $path): bool
    {
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }
        $qrFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR
            . 'tecnickcom' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR
            . 'include' . DIRECTORY_SEPARATOR . 'barcodes' . DIRECTORY_SEPARATOR . 'qrcode.php';
        if (!class_exists('QRcode', false) && is_file($qrFile)) {
            require_once $qrFile;
        }
        if (!class_exists('QRcode', false)) {
            return false;
        }
        $qr = new \QRcode($payload, 'M');
        $arr = $qr->getBarcodeArray();
        $n = (int)($arr['num_cols'] ?? 0);
        $bcode = $arr['bcode'] ?? null;
        if ($n < 21 || !is_array($bcode)) {
            return false;
        }
        $pixel = 300;
        $cell = intdiv($pixel, $n + 8);
        if ($cell < 1) {
            $cell = 1;
        }
        $size = $cell * ($n + 8);
        $img = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, $size, $size, $white);
        $origin = $cell * 4;
        for ($y = 0; $y < $n; $y++) {
            $row = $bcode[$y] ?? [];
            for ($x = 0; $x < $n; $x++) {
                if ((int)($row[$x] ?? 0) !== 1) {
                    continue;
                }
                $px = $origin + ($x * $cell);
                $py = $origin + ($y * $cell);
                imagefilledrectangle($img, $px, $py, $px + $cell - 1, $py + $cell - 1, $black);
            }
        }
        $ok = imagepng($img, $path);
        imagedestroy($img);
        return $ok && is_file($path) && filesize($path) > 80;
    }

    private static function fetchRemote(string $url): ?string
    {
        $strict = getenv('QR_STRICT_SSL') === 'true';
        $context = $strict ? null : self::insecureContext();
        $data = @file_get_contents($url, false, $context);
        if ($data !== false) {
            return $data;
        }
        if ($strict) {
            $data = @file_get_contents($url, false, self::insecureContext());
            if ($data !== false) {
                return $data;
            }
        }
        return null;
    }

    private static function remoteEnabled(): bool
    {
        $env = getenv('QR_ENABLE_REMOTE');
        if ($env === false) {
            return false;
        }
        return filter_var($env, FILTER_VALIDATE_BOOLEAN);
    }

    private static function insecureContext()
    {
        return stream_context_create([
            'http' => [
                'timeout' => 2,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);
    }

    private static function generateFallback(string $payload, string $path): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            file_put_contents($path, '');
            return;
        }
        $img = imagecreatetruecolor(300, 300);
        $bg = imagecolorallocate($img, 247, 247, 247);
        $fg = imagecolorallocate($img, 60, 60, 60);
        imagefilledrectangle($img, 0, 0, 300, 300, $bg);
        imagestring($img, 5, 20, 130, 'QR UNAVAILABLE', $fg);
        imagestring($img, 3, 20, 160, substr($payload, 0, 24), $fg);
        imagepng($img, $path);
        imagedestroy($img);
    }
}
