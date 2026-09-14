<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\Database;

class AdminLogsController
{
    public function list(): void
    {
        if (!AuthService::isAdmin()) {
            AuthService::deny('GET');
            return;
        }
        $pdo = Database::pdo();
        $eventId = (int)($_GET['event_id'] ?? $_SESSION['current_event_id'] ?? 0);
        if ($eventId > 0) {
            $stmt = $pdo->prepare('SELECT id, admin_id, event_id, action, detail, created_at FROM action_logs WHERE event_id = ? ORDER BY id DESC LIMIT 200');
            $stmt->execute([$eventId]);
            $rows = $stmt->fetchAll();
        } else {
            $rows = $pdo->query('SELECT id, admin_id, event_id, action, detail, created_at FROM action_logs ORDER BY id DESC LIMIT 200')->fetchAll();
        }
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_logs.php';
    }
}
