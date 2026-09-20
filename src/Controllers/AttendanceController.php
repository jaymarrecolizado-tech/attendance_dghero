<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\Database;
use App\Services\EventContext;
use App\Services\SignatureService;

class AttendanceController
{
    public function submit(): void
    {
        header('Content-Type: application/json');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ok = \App\Services\RateLimiter::allow('attendance_submit:'.$ip, 20, 60);
        if (!$ok) { http_response_code(429); echo json_encode(['error'=>'rate_limited']); return; }
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!function_exists('csrf_check') || !csrf_check($csrf)) { http_response_code(400); echo json_encode(['error'=>'csrf']); return; }
        if (empty($_SESSION['staff']) && !AuthService::check()) { http_response_code(403); echo json_encode(['error'=>'forbidden']); return; }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);
        if (!is_array($payload)) { http_response_code(400); echo json_encode(['error'=>'invalid']); return; }

        $uuid = trim((string)($payload['uuid'] ?? ''));
        $sig = (string)($payload['signature'] ?? '');
        $slug = trim((string)($payload['e'] ?? $_GET['e'] ?? ''));
        if ($uuid === '' || $sig === '') { http_response_code(422); echo json_encode(['error'=>'missing']); return; }

        $pdo = Database::pdo();
        if ($slug === '' && !empty($_SESSION['scan_event_id'])) {
            $ev = EventContext::findById($pdo, (int)$_SESSION['scan_event_id']);
            $slug = $ev && isset($ev['slug']) ? (string)$ev['slug'] : '';
        }
        if ($slug === '') { http_response_code(400); echo json_encode(['error'=>'missing_event']); return; }
        $event = EventContext::findBySlug($pdo, $slug);
        if (!$event || !EventContext::isPublicOpen($event)) { http_response_code(403); echo json_encode(['error'=>'event_closed']); return; }
        $eventId = (int)$event['id'];
        $enforce = (int)($event['enforce_single_time_in'] ?? 1) === 1;

        // Logged-in staff without kiosk session must hold an event role; kiosk session bypasses.
        if (empty($_SESSION['staff']) && AuthService::check() && !AuthService::isAdmin()) {
            $adminId = (int)($_SESSION['admin_id'] ?? 0);
            if (!EventContext::canAccess($pdo, $adminId, $eventId, ['event_admin', 'checker'])) {
                http_response_code(403); echo json_encode(['error'=>'forbidden']); return;
            }
        }

        $stmt = $pdo->prepare('SELECT id, event_id FROM participants WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['event_id'] !== $eventId) { http_response_code(404); echo json_encode(['error'=>'not_found']); return; }

        $path = SignatureService::saveBase64($uuid, $sig);
        $date = date('Y-m-d');
        $time = date('H:i:s');
        if ($enforce) {
            $chk = $pdo->prepare('SELECT id FROM attendance WHERE participant_id=? AND attendance_date=? AND event_id=?');
            $chk->execute([(int)$row['id'], $date, $eventId]);
            if ($chk->fetch()) { echo json_encode(['ok'=>false,'error'=>'already_marked']); return; }
        }
        $ins = $pdo->prepare("INSERT INTO attendance (participant_id, attendance_date, time_in, signature_path, event_id, status) VALUES (?,?,?,?,?,'present')");
        $ins->execute([(int)$row['id'], $date, $time, $path, $eventId]);
        if (function_exists('csrf_rotate')) csrf_rotate();
        echo json_encode(['ok'=>true]);
    }

    public function submitJsonForTest(array $payload, string $csrf): array
    {
        if (!function_exists('csrf_check') || !csrf_check($csrf)) return ['error'=>'csrf'];
        if (empty($_SESSION['staff']) && !AuthService::check()) return ['error'=>'forbidden'];
        $uuid = trim((string)($payload['uuid'] ?? ''));
        $sig = (string)($payload['signature'] ?? '');
        $slug = trim((string)($payload['e'] ?? ''));
        if ($uuid === '' || $sig === '') return ['error'=>'missing'];
        $pdo = \App\Services\Database::pdo();
        if ($slug === '' && !empty($_SESSION['scan_event_id'])) {
            $ev = EventContext::findById($pdo, (int)$_SESSION['scan_event_id']);
            $slug = $ev && isset($ev['slug']) ? (string)$ev['slug'] : '';
        }
        if ($slug === '') return ['error'=>'missing_event'];
        $event = EventContext::findBySlug($pdo, $slug);
        if (!$event || !EventContext::isPublicOpen($event)) return ['error'=>'event_closed'];
        $eventId = (int)$event['id'];
        $enforce = (int)($event['enforce_single_time_in'] ?? 1) === 1;
        if (empty($_SESSION['staff']) && AuthService::check() && !AuthService::isAdmin()) {
            $adminId = (int)($_SESSION['admin_id'] ?? 0);
            if (!EventContext::canAccess($pdo, $adminId, $eventId, ['event_admin', 'checker'])) return ['error'=>'forbidden'];
        }
        $stmt = $pdo->prepare('SELECT id, event_id FROM participants WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['event_id'] !== $eventId) return ['error'=>'not_found'];
        $path = \App\Services\SignatureService::saveBase64($uuid, $sig);
        $date = date('Y-m-d');
        $time = date('H:i:s');
        if ($enforce) {
            $chk = $pdo->prepare('SELECT id FROM attendance WHERE participant_id=? AND attendance_date=? AND event_id=?');
            $chk->execute([(int)$row['id'], $date, $eventId]);
            if ($chk->fetch()) return ['ok'=>false,'error'=>'already_marked'];
        }
        $ins = $pdo->prepare("INSERT INTO attendance (participant_id, attendance_date, time_in, signature_path, event_id, status) VALUES (?,?,?,?,?,'present')");
        $ins->execute([(int)$row['id'], $date, $time, $path, $eventId]);
        if (function_exists('csrf_rotate')) csrf_rotate();
        return ['ok'=>true];
    }
}
