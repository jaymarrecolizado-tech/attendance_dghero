<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\Database;
use App\Services\EventContext;

final class AdminExportPageController
{
    public function index(): void
    {
        if (!AuthService::check()) {
            header('Location: ?r=admin_login');
            return;
        }
        $pdo = Database::pdo();
        $event = EventContext::currentEvent($pdo);
        if ($event && !EventContext::canAccess($pdo, (int)$_SESSION['admin_id'], (int)$event['id'], ['event_admin']) && !AuthService::isAdmin()) {
            AuthService::deny('GET');
            return;
        }

        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_export.php';
    }
}

