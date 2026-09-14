<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;
use App\Services\EventContext;

class ParticipantController
{
    public function getByUuidJson(): void
    {
        header('Content-Type: application/json');
        $uuid = isset($_GET['uuid']) ? (string)$_GET['uuid'] : '';
        if ($uuid === '') { http_response_code(400); echo json_encode(['error'=>'missing']); return; }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, uuid, event_id, email, first_name, middle_name, last_name, nickname, sex, sector, agency, designation, office_email, contact_no, is_vip FROM participants WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo json_encode(['error'=>'not_found']); return; }
        $slug = trim((string)($_GET['e'] ?? ''));
        if ($slug === '' && !empty($_SESSION['scan_event_id'])) {
            $kiosk = EventContext::findById($pdo, (int)$_SESSION['scan_event_id']);
            $slug = $kiosk && isset($kiosk['slug']) ? (string)$kiosk['slug'] : '';
        }
        if ($slug === '') { http_response_code(400); echo json_encode(['error'=>'missing_event']); return; }
        $event = EventContext::findBySlug($pdo, $slug);
        if (!$event || (int)$row['event_id'] !== (int)$event['id']) { http_response_code(404); echo json_encode(['error'=>'not_found']); return; }
        echo json_encode(['participant'=>$row]);
    }
}