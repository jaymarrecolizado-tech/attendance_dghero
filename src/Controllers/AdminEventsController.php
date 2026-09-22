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

    /**
     * Plan#10: per-event Certificate of Appearance settings (All Father).
     * Empty values fall back to the CoaService defaults.
     */
    public function coa(): void
    {
        if (!$this->requireAllFather()) return;
        if (!$this->csrfOk()) return;
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { http_response_code(422); echo 'Invalid'; return; }
        $enabled = isset($_POST['coa_enabled']) ? 1 : 0;
        $venue = trim((string)($_POST['coa_venue'] ?? ''));
        $purpose = trim(strip_tags((string)($_POST['coa_purpose'] ?? '')));
        $particulars = trim((string)($_POST['coa_particulars'] ?? ''));
        $sigName = trim(strip_tags((string)($_POST['coa_signatory_name'] ?? '')));
        $sigTitle = trim(strip_tags((string)($_POST['coa_signatory_title'] ?? '')));
        $sigPath = trim((string)($_POST['coa_signatory_path'] ?? ''));
        $logoPath = trim((string)($_POST['coa_logo_path'] ?? ''));
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE events SET coa_enabled = ?, coa_venue = ?, coa_purpose = ?, coa_particulars = ?, coa_signatory_name = ?, coa_signatory_title = ?, coa_signatory_path = ?, coa_logo_path = ? WHERE id = ?');
        $stmt->execute([
            $enabled,
            $venue !== '' ? mb_substr($venue, 0, 255) : null,
            $purpose !== '' ? mb_substr($purpose, 0, 255) : null,
            $particulars !== '' ? $particulars : null,
            $sigName !== '' ? mb_substr($sigName, 0, 120) : null,
            $sigTitle !== '' ? mb_substr($sigTitle, 0, 120) : null,
            $sigPath !== '' ? $sigPath : null,
            $logoPath !== '' ? $logoPath : null,
            $id,
        ]);
        Logger::log(AuthService::id(), 'event_coa_updated', ['event_id' => $id, 'enabled' => $enabled], $id);
        if (function_exists('csrf_rotate')) csrf_rotate();
        header('Location: ?r=admin_events');
    }

    /**
     * Per-event appearance (structured branding): primary/accent hex colors,
     * a short welcome line, and optional logo/banner images. All Father only.
     */
    public function theme(): void
    {
        if (!$this->requireAllFather()) return;
        if (!$this->csrfOk()) return;
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { http_response_code(422); echo 'Invalid'; return; }
        $pdo = Database::pdo();
        $event = EventContext::findById($pdo, $id);
        if (!$event) { http_response_code(404); echo 'Not found'; return; }

        $primaryRaw = trim((string)($_POST['theme_primary'] ?? ''));
        $accentRaw = trim((string)($_POST['theme_accent'] ?? ''));
        if ($primaryRaw !== '' && $this->normalizeHex($primaryRaw) === null) { http_response_code(422); echo 'Invalid primary color'; return; }
        if ($accentRaw !== '' && $this->normalizeHex($accentRaw) === null) { http_response_code(422); echo 'Invalid accent color'; return; }
        $welcome = trim(strip_tags((string)($_POST['welcome_text'] ?? '')));
        if (mb_strlen($welcome) > 180) { $welcome = mb_substr($welcome, 0, 180); }

        $logoPath = $this->storeBrandingImage($id, 'logo');
        $bannerPath = $this->storeBrandingImage($id, 'banner');
        if ($logoPath === false || $bannerPath === false) {
            http_response_code(422);
            echo 'Invalid image (png, jpeg, or webp, up to 1 MB)';
            return;
        }

        if (isset($_POST['clear_logo'])) { $this->dropBrandingImage($pdo, $id, 'logo_path'); }
        if (isset($_POST['clear_banner'])) { $this->dropBrandingImage($pdo, $id, 'banner_path'); }

        // Column names come from fixed call sites above, never from user input.
        $stmt = $pdo->prepare('UPDATE events SET theme_primary = ?, theme_accent = ?, welcome_text = ?, logo_path = COALESCE(?, logo_path), banner_path = COALESCE(?, banner_path) WHERE id = ?');
        $stmt->execute([
            isset($_POST['reset_colors']) ? null : $this->normalizeHex($primaryRaw),
            isset($_POST['reset_colors']) ? null : $this->normalizeHex($accentRaw),
            $welcome !== '' ? $welcome : null,
            $logoPath,
            $bannerPath,
            $id,
        ]);
        Logger::log(AuthService::id(), 'event_theme_updated', ['event_id' => $id], $id);
        if (function_exists('csrf_rotate')) csrf_rotate();
        header('Location: ?r=admin_events');
    }

    /** Normalize "#RRGGBB" / "RRGGBB" to uppercase "#RRGGBB"; null when invalid or empty. */
    private function normalizeHex(string $value): ?string
    {
        if ($value === '') return null;
        if (preg_match('/^#?([0-9a-fA-F]{6})$/', $value, $m) === 1) {
            return '#' . strtoupper($m[1]);
        }
        return null;
    }

    /**
     * Move an uploaded logo/banner into storage/event-branding/{eventId}/.
     * Returns the stored relative path, null when nothing was uploaded,
     * or false when the upload is not a valid image.
     * @return string|null|false
     */
    private function storeBrandingImage(int $eventId, string $kind)
    {
        $file = $_FILES[$kind] ?? null;
        if (!is_array($file)) return null;
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) return null;
        if ($error !== UPLOAD_ERR_OK) return false;
        if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > 1_048_576) return false;

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) return false;
        try {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        } catch (\Throwable $e) {
            return false;
        }
        if (!in_array((string)$mime, ['image/png', 'image/jpeg', 'image/webp'], true)) return false;

        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'event-branding' . DIRECTORY_SEPARATOR . $eventId;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) return false;
        $name = $kind . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        foreach (['png', 'jpg', 'webp'] as $oldExt) {
            if ($oldExt === ($ext === 'jpeg' ? 'jpg' : $ext)) continue;
            $old = $dir . DIRECTORY_SEPARATOR . $kind . '.' . $oldExt;
            if (is_file($old)) { @unlink($old); }
        }
        if (!move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $name)) return false;
        return 'storage/event-branding/' . $eventId . '/' . $name;
    }

    /** Remove a branding image file (only from this event's branding dir) and clear the column. */
    private function dropBrandingImage(\PDO $pdo, int $eventId, string $column): void
    {
        $stmt = $pdo->prepare("SELECT {$column} FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $path = (string)($stmt->fetchColumn() ?: '');
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'event-branding' . DIRECTORY_SEPARATOR . $eventId;
        $realDir = realpath($dir);
        $real = $path !== '' ? realpath($path) : false;
        if ($realDir !== false && $real !== false && str_starts_with($real, $realDir . DIRECTORY_SEPARATOR) && is_file($real)) {
            @unlink($real);
        }
        $pdo->prepare("UPDATE events SET {$column} = NULL WHERE id = ?")->execute([$eventId]);
    }
}
