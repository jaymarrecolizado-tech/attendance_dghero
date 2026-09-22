<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

class Logger
{
    public static function log(?int $adminId, string $action, array $detail = [], ?int $eventId = null): void
    {
        $pdo = Database::pdo();
        if ($eventId === null && isset($_SESSION['current_event_id'])) {
            $eventId = (int)$_SESSION['current_event_id'] ?: null;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO action_logs (admin_id, event_id, action, detail) VALUES (?,?,?,?)');
            $stmt->execute([$adminId, $eventId, $action, json_encode($detail)]);
        } catch (\Throwable $e) {
            try {
                $stmt = $pdo->prepare('INSERT INTO action_logs (admin_id, action, detail) VALUES (?,?,?)');
                $stmt->execute([$adminId, $action, json_encode($detail)]);
            } catch (\Throwable $e2) {
                error_log('action_logs insert failed: ' . $e2->getMessage());
            }
        }
    }
}