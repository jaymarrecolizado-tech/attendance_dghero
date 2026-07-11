<?php
declare(strict_types=1);

namespace App\Services;

class RateLimiter
{
    public static function allow(string $key, int $max, int $windowSec): bool
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'runtime';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir . DIRECTORY_SEPARATOR . 'ratelimit.json';
        $now = time();

        $handle = fopen($file, 'c+');
        if (!$handle) {
            return true;
        }

        flock($handle, LOCK_EX);
        $raw = stream_get_contents($handle);
        $data = $raw ? (json_decode($raw, true) ?: []) : [];
        $data = is_array($data) ? $data : [];

        foreach ($data as $k => $entry) {
            if ($now - (int)($entry['start'] ?? 0) >= $windowSec) {
                unset($data[$k]);
            }
        }

        $entry = $data[$key] ?? ['count' => 0, 'start' => $now];
        if ($now - (int)$entry['start'] >= $windowSec) {
            $entry = ['count' => 0, 'start' => $now];
        }

        $entry['count'] = (int)$entry['count'] + 1;
        $data[$key] = $entry;

        $payload = '{}';
        try {
            $payload = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $payload = '{}';
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, $payload);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $entry['count'] <= $max;
    }
}