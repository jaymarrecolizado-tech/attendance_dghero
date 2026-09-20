<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\Database;
use App\Services\EventContext;
use App\Services\Logger;

class AdminEventsController
{
    private function requireAllFather(): bool
    {
        if (!AuthService::isAdmin()) {
            AuthService::deny($_SERVER['REQUEST_METHOD'] ?? 'GET');
            return false;
        }
        return true;
    }

    private function csrfOk(): bool
    {
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check($_POST['csrf'])) {
            http_response_code(400);
            echo 'Invalid CSRF';
            return false;
        }
        return true;
    }

    /** Normalize HTML datetime-local ("2026-09-14T10:00") to MySQL DATETIME. */
    private function normDt(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') return null;
        $v = str_replace('T', ' ', $v);
        return strlen($v) === 16 ? $v . ':00' : $v;
    }

    public function list(): void
    {
        if (!$this->requireAllFather()) return;
        $pdo = Database::pdo();
        $rows = $pdo->query('SELECT * FROM events ORDER BY id DESC')->fetchAll();
        $staff = $pdo->query("SELECT id, username, display_name, role FROM admins WHERE is_active = 1 ORDER BY username ASC")->fetchAll();
        $assign = [];
        try {
            $assign = $pdo->query('SELECT a.*, m.username, m.display_name FROM event_assignments a JOIN admins m ON m.id = a.admin_id ORDER BY a.event_id DESC')->fetchAll();
        } catch (\Throwable $e) {
            $assign = [];
        }
        $byEvent = [];
        foreach ($assign as $a) {
            $byEvent[(int)$a['event_id']][] = $a;
        }
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_events.php';
    }

    public function create(): void
    {
        if (!$this->requireAllFather()) return;
        if (!$this->csrfOk()) return;
        $name = trim((string)($_POST['name'] ?? ''));
        $status = trim((string)($_POST['status'] ?? 'open'));
        $starts = trim((string)($_POST['starts_at'] ?? ''));
        $ends = trim((string)($_POST['ends_at'] ?? ''));
        $enforce = isset($_POST['enforce']) ? 1 : 0;
        if ($name === '') { http_response_code(422); echo 'Missing name'; return; }
        if (!in_array($status, ['draft', 'open', 'closed'], true)) $status = 'open';
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO events (name, slug, enforce_single_time_in, active, status, starts_at, ends_at) VALUES (?,?,?,?,?,?,?)');
        $tmpSlug = EventContext::slugify($name);
        $stmt->execute([$name, $tmpSlug . '-' . time(), $enforce, 0, $status, $this->normDt($starts), $this->normDt($ends)]);
        $id = (int)$pdo->lastInsertId();
        $slug = EventContext::slugify($name, $id);
        // Ensure unique slug.
        $chk = $pdo->prepare('SELECT id FROM events WHERE slug = ? AND id <> ? LIMIT 1');
        $chk->execute([$slug, $id]);
        if ($chk->fetch()) $slug .= '-' . $id . '-' . time() % 1000;
        $pdo->prepare('UPDATE events SET slug = ? WHERE id = ?')->execute([$slug, $id]);
        $_SESSION['current_event_id'] = $id;
        Logger::log(AuthService::id(), 'event_created', ['event_id' => $id, 'name' => $name], $id);
        if (function_exists('csrf_rotate')) csrf_rotate();
        header('Location: ?r=admin_events');
    }

    public function update(): void
    {
        if (!$this->requireAllFather()) return;
        if (!$this->csrfOk()) return;
        $id = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'open'));
        $starts = trim((string)($_POST['starts_at'] ?? ''));
        $ends = trim((string)($_POST['ends_at'] ?? ''));
        $enforce = isset($_POST['enforce']) ? 1 : 0;
        if ($id <= 0 || !in_array($status, ['draft', 'open', 'closed'], true)) { http_response_code(422); echo 'Invalid'; return; }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE events SET status = ?, starts_at = ?, ends_at = ?, enforce_single_time_in = ? WHERE id = ?');
        $stmt->execute([$status, $this->normDt($starts), $this->normDt($ends), $enforce, $id]);
        Logger::log(AuthService::id(), 'event_updated', ['event_id' => $id, 'status' => $status], $id);
        if (function_exists('csrf_rotate')) csrf_rotate();
        header('Location: ?r=admin_events');
    }

    /**
     * Links-only view for event_admin: assigned events with copyable
     * register/scan links. No create, assign, or cross-event access.
     */
    public function links(): void
    {
        if (!AuthService::check()) { header('Location: ?r=admin_login'); return; }
        $pdo = Database::pdo();
        $adminId = (int)($_SESSION['admin_id'] ?? 0);
        $events = AuthService::isAdmin() ? EventContext::allEvents($pdo) : EventContext::assignedEvents($pdo, $adminId);
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_event_links.php';
    }

    public function switch(): void
    {
        if (!AuthService::check()) { AuthService::deny('POST'); return; }
        if (!$this->csrfOk()) return;
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { http_response_code(422); echo 'Invalid'; return; }
        $pdo = Database::pdo();
        $event = EventContext::findById($pdo, $id);
        if (!$event) { http_response_code(404); echo 'Not found'; return; }
        if (!EventContext::canAccess($pdo, (int)$_SESSION['admin_id'], $id, null)) {
            AuthService::deny('POST');
            return;
        }
        EventContext::setCurrentEvent($id);
        $back = trim((string)($_POST['back'] ?? ''));
        if (function_exists('csrf_rotate')) csrf_rotate();
        header('Location: ' . ($back !== '' ? $back : '?r=admin_registrants'));
    }

    public function assign(): void
    {
        if (!$this->requireAllFather()) return;
        if (!$this->csrfOk()) return;
        $adminId = (int)($_POST['admin_id'] ?? 0);
        $eventId = (int)($_POST['event_id'] ?? 0);
        $role = trim((string)($_POST['role'] ?? 'checker'));
        if ($adminId <= 0 || $eventId <= 0 || !in_array($role, ['event_admin', 'checker', 'seo_viewer'], true)) {
            http_response_code(422); echo 'Invalid'; return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO event_assignments (admin_id, event_id, role) VALUES (?,?,?) ON DUPLICATE KEY UPDATE role = VALUES(role)');
        $stmt->execute([$adminId, $eventId, $role]);
        Logger::log(AuthService::id(), 'event_assigned', ['event_id' => $eventId, 'admin_id' => $adminId, 'role' => $role], $eventId);
        if (function_exists('csrf_rotate')) csrf_rotate();
        header('Location: ?r=admin_events');
    }

    public function unassign(): void
    {
        if (!$this->requireAllFather()) return;
        if (!$this->csrfOk()) return;
        $adminId = (int)($_POST['admin_id'] ?? 0);
        $eventId = (int)($_POST['event_id'] ?? 0);
        if ($adminId <= 0 || $eventId <= 0) { http_response_code(422); echo 'Invalid'; return; }
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM event_assignments WHERE admin_id = ? AND event_id = ?')->execute([$adminId, $eventId]);
        Logger::log(AuthService::id(), 'event_unassigned', ['event_id' => $eventId, 'admin_id' => $adminId], $eventId);
        if (function_exists('csrf_rotate')) csrf_rotate();
        header('Location: ?r=admin_events');
    }
}
