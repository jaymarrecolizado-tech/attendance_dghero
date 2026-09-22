<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

/**
 * Serves per-event branding images (logo, banner) from
 * storage/event-branding/{eventId}/ through a route so the storage
 * folder stays private, same pattern as QR codes and signatures.
 */
final class EventBrandingController
{
    private const KINDS = [
        'logo' => 'logo_path',
        'banner' => 'banner_path',
    ];

    public function image(): void
    {
        $eventId = (int)($_GET['eid'] ?? 0);
        $kind = (string)($_GET['f'] ?? '');
        $column = self::KINDS[$kind] ?? null;
        if ($eventId <= 0 || $column === null) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("SELECT id, {$column} AS file_path FROM events WHERE id = ? LIMIT 1");
            $stmt->execute([$eventId]);
            $row = $stmt->fetch();
        } catch (\Throwable $e) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $path = (string)($row['file_path'] ?? '');
        if (!$row || $path === '' || !self::pathIsInsideEventDir($path, $eventId) || !is_file($path)) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => '',
        };
        if ($mime === '') {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        header('Content-Type: ' . $mime);
        // Branding changes rarely; short shared cache keeps guest pages fast.
        header('Cache-Control: public, max-age=300');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
    }

    /** Defense in depth: the stored path must live inside this event's branding dir. */
    private static function pathIsInsideEventDir(string $path, int $eventId): bool
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'event-branding' . DIRECTORY_SEPARATOR . $eventId;
        $realDir = realpath($dir);
        $realPath = realpath($path);
        return $realDir !== false && $realPath !== false && str_starts_with($realPath, $realDir . DIRECTORY_SEPARATOR);
    }
}
