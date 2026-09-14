<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ResolvesEventContext;
use App\Services\Database;

final class AdminExportPageController
{
    use ResolvesEventContext;

    public function index(): void
    {
        $pdo = Database::pdo();
        if (!$this->requireEventContext($pdo, ['event_admin'])) {
            return;
        }

        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_export.php';
    }
}
