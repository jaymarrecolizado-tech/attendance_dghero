<?php
declare(strict_types=1);

namespace App\Services;

class SignatureService
{
    public static function saveBase64(string $uuid, string $base64): string
    {
        $parts = explode(',', $base64, 2);
        $data = count($parts) === 2 ? $parts[1] : $parts[0];
        $bin = base64_decode($data, true);
        if ($bin === false || strlen($bin) > 5_000_000) {
            throw new \RuntimeException('Invalid signature');
        }
        $year = date('Y');
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'signatures' . DIRECTORY_SEPARATOR . $year;
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $name = $uuid . '_' . time() . '.png';
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        file_put_contents($path, $bin);
        return $path;
    }
}