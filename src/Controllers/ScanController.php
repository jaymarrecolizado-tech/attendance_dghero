<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;
use App\Services\EventContext;

class ScanController
{
    public function show(): void
    {
        $pdo = Database::pdo();
        $slug = trim((string)($_GET['e'] ?? ''));
        if ($slug === '') {
            $events = EventContext::openEvents($pdo);
            $mode = 'scan';
            require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'public_event_picker.php';
            return;
        }
        $event = EventContext::findBySlug($pdo, $slug);
        if (!$event) { http_response_code(404); echo 'Event not found'; return; }
        if (!EventContext::isPublicOpen($event)) { http_response_code(403); echo 'Check-in is closed for this event'; return; }
        $_SESSION['staff'] = true;
        $_SESSION['scan_event_id'] = (int)$event['id'];
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'scan.php';
    }
}