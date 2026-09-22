<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CoaService;
use App\Services\Database;
use App\Services\EventContext;

/**
 * Plan#11: Certificate send monitor, manual batches, queue/resend, and
 * template library. All Father only (route guard + in-controller check).
 */
final class AdminCoaMonitorController
{
    private function requireAllFather(string $method = 'GET'): bool
    {
        if (!AuthService::isAdmin()) {
            AuthService::deny($method);
            return false;
        }
        return true;
    }

    /** Same CSRF gate as other admin POST controllers. */
    private function requireCsrf(): bool
    {
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check((string)$_POST['csrf'])) {
            http_response_code(400);
            echo 'Invalid CSRF';
            return false;
        }
        if (function_exists('csrf_rotate')) {
            csrf_rotate();
        }
        return true;
    }

    private function scopeEventId(): int
    {
        return (int)($_GET['event_id'] ?? 0);
    }

    /** Display name for the nav-selected event (never invents a session key). */
    private function currentEventName(\PDO $pdo): string
    {
        $event = EventContext::currentEvent($pdo);
        if ($event && trim((string)($event['name'] ?? '')) !== '') {
            return (string)$event['name'];
        }
        return 'Certificate of Appearance';
    }

    /** Monitor: KPI strip, action forms, recent batches, optional batch detail. */
    public function monitor(): void
    {
        if (!$this->requireAllFather()) return;
        $pdo = Database::pdo();
        Database::ensureCoaFacility($pdo);
        $scopeEventId = $this->scopeEventId();
        $scopeSql = $scopeEventId > 0 ? ' WHERE event_id = ?' : '';
        $scopeParams = $scopeEventId > 0 ? [$scopeEventId] : [];

        $kpis = ['sent' => 0, 'failed' => 0, 'queued' => 0, 'skipped' => 0, 'batches' => 0];
        $stmt = $pdo->prepare('SELECT status, COUNT(*) AS c FROM coa_sends' . $scopeSql . ' GROUP BY status');
        $stmt->execute($scopeParams);
        foreach ($stmt->fetchAll() as $row) {
            $kpis[(string)$row['status']] = (int)$row['c'];
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM coa_batches' . $scopeSql);
        $stmt->execute($scopeParams);
        $kpis['batches'] = (int)$stmt->fetchColumn();

        $batchSql = 'SELECT b.*,
                COALESCE(SUM(CASE WHEN s.status = \'sent\' THEN 1 ELSE 0 END), 0) AS sent_count,
                COALESCE(SUM(CASE WHEN s.status = \'failed\' THEN 1 ELSE 0 END), 0) AS failed_count,
                COALESCE(SUM(CASE WHEN s.status = \'queued\' THEN 1 ELSE 0 END), 0) AS queued_count,
                COALESCE(SUM(CASE WHEN s.status = \'skipped\' THEN 1 ELSE 0 END), 0) AS skipped_count
            FROM coa_batches b
            LEFT JOIN coa_sends s ON s.batch_id = b.id'
            . ($scopeEventId > 0 ? ' WHERE b.event_id = ?' : '')
            . ' GROUP BY b.id ORDER BY b.id DESC LIMIT 20';
        $stmt = $pdo->prepare($batchSql);
        $stmt->execute($scopeParams);
        $batches = $stmt->fetchAll();

        $events = $pdo->query('SELECT id, name FROM events ORDER BY name ASC')->fetchAll();
        $templates = $pdo->query('SELECT id, name FROM coa_templates ORDER BY name ASC')->fetchAll();
        $signatories = $pdo->query('SELECT id, name, title FROM coa_signatories ORDER BY name ASC')->fetchAll();

        $batchDetail = null;
        $recipients = [];
        $statusFilter = (string)($_GET['status'] ?? 'all');
        $batchId = (int)($_GET['batch_id'] ?? 0);
        if ($batchId > 0) {
            $stmt = $pdo->prepare('SELECT b.* FROM coa_batches b WHERE b.id = ? LIMIT 1');
            $stmt->execute([$batchId]);
            $batchDetail = $stmt->fetch();
            if ($batchDetail) {
                $sql = 'SELECT s.*, p.first_name, p.last_name, p.agency
                    FROM coa_sends s LEFT JOIN participants p ON p.id = s.participant_id
                    WHERE s.batch_id = ?';
                $params = [$batchId];
                if (in_array($statusFilter, ['sent', 'failed', 'queued', 'skipped'], true)) {
                    $sql .= ' AND s.status = ?';
                    $params[] = $statusFilter;
                }
                $sql .= ' ORDER BY s.id ASC';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $recipients = $stmt->fetchAll();
                $q = $pdo->prepare("SELECT COUNT(*) FROM coa_sends WHERE batch_id = ? AND status = 'queued'");
                $q->execute([$batchId]);
                $batchDetail['queued_left'] = (int)$q->fetchColumn();
            }
        }

        $composeEventId = (int)($_GET['compose_event_id'] ?? ($scopeEventId > 0 ? $scopeEventId : 0));
        $composeDate = trim((string)($_GET['compose_date'] ?? date('Y-m-d')));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $composeDate) !== 1) {
            $composeDate = date('Y-m-d');
        }
        $composeTemplateId = (int)($_GET['template'] ?? 0);
        $composeAttendees = [];
        if ($composeEventId > 0) {
            $stmt = $pdo->prepare(
                'SELECT p.id, p.first_name, p.last_name, p.agency,
                        COALESCE(NULLIF(p.email, \'\'), NULLIF(p.office_email, \'\')) AS email,
                        (SELECT status FROM coa_sends cs WHERE cs.participant_id = p.id AND cs.attendance_date = a.attendance_date ORDER BY cs.id DESC LIMIT 1) AS last_status
                 FROM participants p
                 JOIN attendance a ON a.participant_id = p.id AND a.event_id = p.event_id AND a.attendance_date = ?
                 WHERE p.event_id = ?
                 ORDER BY p.last_name ASC, p.first_name ASC'
            );
            $stmt->execute([$composeDate, $composeEventId]);
            $composeAttendees = $stmt->fetchAll();
        }

        $flash = $_SESSION['coa_flash'] ?? null;
        unset($_SESSION['coa_flash']);
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_coa_monitor.php';
    }

    /** Manual batch: generate + email CoAs for attendees missing a sent CoA on a date. */
    public function sendNew(): void
    {
        if (!$this->requireAllFather('POST') || !$this->requireCsrf()) return;
        $eventId = (int)($_POST['event_id'] ?? 0);
        $date = trim((string)($_POST['attendance_date'] ?? ''));
        if ($eventId <= 0 || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            $_SESSION['coa_flash'] = ['type' => 'danger', 'message' => 'Pick an event and a valid attendance date (YYYY-MM-DD).'];
            header('Location: ?r=admin_coa_monitor');
            return;
        }
        $pdo = Database::pdo();
        $missing = $pdo->prepare(
            'SELECT p.id FROM participants p
             JOIN attendance a ON a.participant_id = p.id AND a.event_id = p.event_id AND a.attendance_date = ?
             WHERE p.event_id = ?
               AND p.id NOT IN (SELECT participant_id FROM coa_sends WHERE event_id = ? AND attendance_date = ? AND status = ?)'
        );
        $missing->execute([$date, $eventId, $eventId, $date, 'sent']);
        $ids = array_slice(array_map('intval', array_column($missing->fetchAll(), 'id')), 0, 50);

        $sentCount = 0;
        foreach ($ids as $pid) {
            if (\App\Services\CoaService::sendNow($eventId, $pid, $date, 'manual')) {
                $sentCount++;
            }
        }
        $_SESSION['coa_flash'] = ['type' => 'success', 'message' => 'Send new complete: ' . count($ids) . ' attendee(s) processed, ' . $sentCount . ' sent.'];
        header('Location: ?r=admin_coa_monitor&event_id=' . $eventId);
    }

    /** Move failed rows (scoped) into the resend queue. */
    public function queueFailed(): void
    {
        if (!$this->requireAllFather('POST') || !$this->requireCsrf()) return;
        $eventId = (int)($_POST['event_id'] ?? 0);
        $count = CoaService::queueFailed($eventId > 0 ? $eventId : null);
        $_SESSION['coa_flash'] = ['type' => 'success', 'message' => $count . ' failed send(s) queued for resend.'];
        header('Location: ?r=admin_coa_monitor' . ($eventId > 0 ? '&event_id=' . $eventId : ''));
    }

    /** Process up to 50 queued rows. */
    public function resendQueued(): void
    {
        if (!$this->requireAllFather('POST') || !$this->requireCsrf()) return;
        $eventId = (int)($_POST['event_id'] ?? 0);
        $pdo = Database::pdo();
        $sql = "SELECT id FROM coa_sends WHERE status = 'queued'";
        $params = [];
        if ($eventId > 0) {
            $sql .= ' AND event_id = ?';
            $params[] = $eventId;
        }
        $sql .= ' ORDER BY id ASC LIMIT 50';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));
        $sent = 0;
        foreach ($ids as $id) {
            if (CoaService::resendRow($id)) $sent++;
        }
        $_SESSION['coa_flash'] = ['type' => 'success', 'message' => count($ids) . ' queued send(s) processed, ' . $sent . ' sent.'];
        header('Location: ?r=admin_coa_monitor' . ($eventId > 0 ? '&event_id=' . $eventId : ''));
    }

    /** Stream a send's PDF inline (never emails). */
    public function preview(): void
    {
        if (!$this->requireAllFather()) return;
        $pdo = Database::pdo();
        $sendId = (int)($_GET['send_id'] ?? 0);
        $participantId = (int)($_GET['participant_id'] ?? 0);
        $date = trim((string)($_GET['date'] ?? ''));
        $path = null;
        $eventId = 0;
        if ($sendId > 0) {
            $stmt = $pdo->prepare('SELECT event_id, participant_id, attendance_date, pdf_path FROM coa_sends WHERE id = ? LIMIT 1');
            $stmt->execute([$sendId]);
            $row = $stmt->fetch();
            if ($row) {
                $eventId = (int)$row['event_id'];
                $path = (string)($row['pdf_path'] ?? '');
                if ($path === '' || !is_file($path)) {
                    $path = CoaService::generate($eventId, (int)$row['participant_id'], (string)$row['attendance_date']);
                }
            }
        } elseif ($participantId > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $stmt = $pdo->prepare('SELECT event_id FROM participants WHERE id = ? LIMIT 1');
            $stmt->execute([$participantId]);
            $eventId = (int)($stmt->fetchColumn() ?: 0);
            if ($eventId > 0) {
                $path = CoaService::generate($eventId, $participantId, $date);
            }
        }
        if ($path === null || !is_file($path)) {
            http_response_code(404);
            echo 'Preview not available';
            return;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
    }

    /** Sample PDF from a saved template or the current event's CoA settings. */
    public function previewTemplate(): void
    {
        if (!$this->requireAllFather()) return;
        $pdo = Database::pdo();
        $templateId = (int)($_GET['template_id'] ?? 0);
        if ($templateId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM coa_templates WHERE id = ? LIMIT 1');
            $stmt->execute([$templateId]);
            $template = $stmt->fetch();
            if (!$template) {
                http_response_code(404);
                echo 'Template not found';
                return;
            }
            $eventLike = [
                'name' => $this->currentEventName($pdo),
                'coa_venue' => $template['venue'],
                'coa_purpose' => $template['purpose'],
                'coa_particulars' => $template['particulars'],
                'coa_signatory_name' => $template['signatory_name'],
                'coa_signatory_title' => $template['signatory_title'],
                'coa_signatory_path' => $template['signatory_path'],
                'coa_logo_path' => $template['logo_path'],
            ];
        } else {
            $current = EventContext::currentEvent($pdo);
            $eventLike = $current ?: [];
            if ($eventLike) {
                $eventLike['name'] = (string)$eventLike['name'];
            }
        }
        $path = CoaService::generatePreview($eventLike, date('Y-m-d'));
        if ($path === null || !is_file($path)) {
            http_response_code(404);
            echo 'Preview not available';
            return;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="coa_preview.pdf"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
    }

    /** Template library page: list, edit form, apply-to-event, preview links. */
    public function templates(): void
    {
        if (!$this->requireAllFather()) return;
        $pdo = Database::pdo();
        $templates = $pdo->query('SELECT * FROM coa_templates ORDER BY updated_at DESC, id DESC')->fetchAll();
        $editing = null;
        $editId = (int)($_GET['edit_id'] ?? 0);
        if ($editId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM coa_templates WHERE id = ? LIMIT 1');
            $stmt->execute([$editId]);
            $editing = $stmt->fetch() ?: null;
        }
        $events = $pdo->query('SELECT id, name FROM events ORDER BY name ASC')->fetchAll();
        $signatories = $pdo->query('SELECT id, name, title, signature_path FROM coa_signatories ORDER BY name ASC')->fetchAll();
        $current = EventContext::currentEvent($pdo);
        $currentEventId = $current ? (int)$current['id'] : 0;
        $currentSignatoryId = $current ? (int)($current['coa_signatory_id'] ?? 0) : 0;
        $flash = $_SESSION['coa_flash'] ?? null;
        unset($_SESSION['coa_flash']);
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_coa_templates.php';
    }

    /** Save as template (new) or update an existing template. */
    public function templateSave(): void
    {
        if (!$this->requireAllFather('POST') || !$this->requireCsrf()) return;
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['coa_flash'] = ['type' => 'danger', 'message' => 'Template name is required.'];
            header('Location: ?r=admin_coa_templates');
            return;
        }
        $pdo = Database::pdo();
        $now = date('Y-m-d H:i:s');

        // Signatory dropdown: copy name/title/signature onto the template row.
        $signatoryId = (int)($_POST['signatory_id'] ?? 0);
        $sigName = '';
        $sigTitle = '';
        $sigPath = '';
        if ($signatoryId > 0) {
            $stmt = $pdo->prepare('SELECT name, title, signature_path FROM coa_signatories WHERE id = ? LIMIT 1');
            $stmt->execute([$signatoryId]);
            $sig = $stmt->fetch();
            if (!$sig) {
                $_SESSION['coa_flash'] = ['type' => 'danger', 'message' => 'Signatory not found.'];
                header('Location: ?r=admin_coa_templates');
                return;
            }
            $sigName = (string)$sig['name'];
            $sigTitle = (string)($sig['title'] ?? '');
            $sigPath = (string)($sig['signature_path'] ?? '');
        }

        $existing = null;
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT logo_path FROM coa_templates WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
        }
        $logoPath = $existing['logo_path'] ?? null;

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE coa_templates SET name = ?, venue = ?, purpose = ?, particulars = ?, signatory_id = ?, signatory_name = ?, signatory_title = ?, signatory_path = ?, updated_at = ? WHERE id = ?');
            $stmt->execute([
                mb_substr($name, 0, 120),
                trim((string)($_POST['venue'] ?? '')),
                trim(strip_tags((string)($_POST['purpose'] ?? ''))),
                trim((string)($_POST['particulars'] ?? '')),
                $signatoryId > 0 ? $signatoryId : null,
                $sigName, $sigTitle, $sigPath,
                $now, $id,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO coa_templates (name, venue, purpose, particulars, signatory_id, signatory_name, signatory_title, signatory_path, logo_path, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                mb_substr($name, 0, 120),
                trim((string)($_POST['venue'] ?? '')),
                trim(strip_tags((string)($_POST['purpose'] ?? ''))),
                trim((string)($_POST['particulars'] ?? '')),
                $signatoryId > 0 ? $signatoryId : null,
                $sigName, $sigTitle, $sigPath,
                $logoPath, $now, $now,
            ]);
        }
        $_SESSION['coa_flash'] = ['type' => 'success', 'message' => 'Template saved.'];
        header('Location: ?r=admin_coa_templates');
    }

    /** Copy a template's fields onto an event's CoA columns. */
    public function templateApply(): void
    {
        if (!$this->requireAllFather('POST') || !$this->requireCsrf()) return;
        $templateId = (int)($_POST['template_id'] ?? 0);
        $eventId = (int)($_POST['event_id'] ?? 0);
        if ($templateId <= 0 || $eventId <= 0) {
            $_SESSION['coa_flash'] = ['type' => 'danger', 'message' => 'Pick a template and an event.'];
            header('Location: ?r=admin_coa_templates');
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM coa_templates WHERE id = ? LIMIT 1');
        $stmt->execute([$templateId]);
        $template = $stmt->fetch();
        if (!$template) {
            $_SESSION['coa_flash'] = ['type' => 'danger', 'message' => 'Template not found.'];
            header('Location: ?r=admin_coa_templates');
            return;
        }
        $upd = $pdo->prepare('UPDATE events SET coa_venue = ?, coa_purpose = ?, coa_particulars = ?, coa_signatory_name = ?, coa_signatory_title = ?, coa_signatory_path = ?, coa_logo_path = ? WHERE id = ?');
        $upd->execute([
            $template['venue'], $template['purpose'], $template['particulars'],
            $template['signatory_name'], $template['signatory_title'],
            $template['signatory_path'], $template['logo_path'], $eventId,
        ]);
        $_SESSION['coa_flash'] = ['type' => 'success', 'message' => 'Template applied to the selected event.'];
        header('Location: ?r=admin_coa_templates');
    }

    /* ---------------- Plan#12: signatory library, compose, schedule -------- */

    /** Signatory library page (list + upload form + thumbnails). */
    public function signatories(): void
    {
        if (!$this->requireAllFather()) return;
        $pdo = Database::pdo();
        $signatories = $pdo->query('SELECT * FROM coa_signatories ORDER BY name ASC')->fetchAll();
        $flash = $_SESSION['coa_flash'] ?? null;
        unset($_SESSION['coa_flash']);
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_coa_signatories.php';
    }

    /** Create a signatory (with e-signature upload) or update name/title. */
    public function signatorySave(): void
    {
        if (!$this->requireAllFather('POST') || !$this->requireCsrf()) return;
        $pdo = Database::pdo();
        $now = date('Y-m-d H:i:s');
        $id = (int)($_POST['id'] ?? 0);
        $name = trim(strip_tags((string)($_POST['name'] ?? '')));
        $title = trim(strip_tags((string)($_POST['title'] ?? '')));
        if ($name === '') {
            $_SESSION['coa_flash'] = ['type' => 'danger', 'message' => 'Signatory name is required.'];
            header('Location: ?r=admin_coa_signatories');
            return;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE coa_signatories SET name = ?, title = ?, updated_at = ? WHERE id = ?');
            $stmt->execute([mb_substr($name, 0, 120), mb_substr($title, 0, 120), $now, $id]);
            $_SESSION['coa_flash'] = ['type' => 'success', 'message' => 'Signatory updated.'];
        } else {
            $stmt = $pdo->prepare('INSERT INTO coa_signatories (name, title, created_at, updated_at) VALUES (?,?,?,?)');
            $stmt->execute([mb_substr($name, 0, 120), mb_substr($title, 0, 120), $now, $now]);
            $id = (int)$pdo->lastInsertId();
            $_SESSION['coa_flash'] = ['type' => 'success', 'message' => 'Signatory added.'];
        }

        $file = $_FILES['signature'] ?? null;
        if (is_array($file) && (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $path = $this->storeSignatureImage($id, $file);
            if ($path !== null) {
                $pdo->prepare('UPDATE coa_signatories SET signature_path = ? WHERE id = ?')->execute([$path, $id]);
                $_SESSION['coa_flash'] = ['type' => 'success', 'message' => 'Signatory saved with e-signature.'];
            } else {
                $_SESSION['coa_flash'] = ['type' => 'danger', 'message' => 'Signature not saved: use a PNG, JPG, or WebP image up to 1 MB.'];
            }
        }
        header('Location: ?r=admin_coa_signatories');
    }

    /** Delete a signatory and its stored signature file. */
    public function signatoryDelete(): void
    {
        if (!$this->requireAllFather('POST') || !$this->requireCsrf()) return;
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header('Location: ?r=admin_coa_signatories'); return; }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT signature_path FROM coa_signatories WHERE id = ?');
        $stmt->execute([$id]);
        $real = CoaService::resolvePath((string)($stmt->fetchColumn() ?: ''));
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'coa' . DIRECTORY_SEPARATOR . 'signatures' . DIRECTORY_SEPARATOR . $id;
        $realDir = realpath($dir);
        if ($realDir !== false && $real !== '' && str_starts_with($real, $realDir . DIRECTORY_SEPARATOR) && is_file($real)) {
            @unlink($real);
        }
        $pdo->prepare('DELETE FROM coa_signatories WHERE id = ?')->execute([$id]);
        $_SESSION['coa_flash'] = ['type' => 'success', 'message' => 'Signatory deleted.'];
        header('Location: ?r=admin_coa_signatories');
    }

    /** Stream a signatory thumbnail (file is not web-public). */
    public function signatoryImage(): void
    {
        if (!$this->requireAllFather()) return;
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { http_response_code(404); echo 'Not found'; return; }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT signature_path FROM coa_signatories WHERE id = ?');
        $stmt->execute([$id]);
        $path = CoaService::resolvePath((string)($stmt->fetchColumn() ?: ''));
        if ($path === '' || !is_file($path)) {
            http_response_code(404);
            echo 'Not found';
            return;
        }
        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=60');
        readfile($path);
    }

    /** Store an uploaded e-signature under storage/coa/signatures/{id}/ (same checks as branding). */
    private function storeSignatureImage(int $signatoryId, array $file): ?string
    {
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
        if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > 1_048_576) return null;
        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) return null;
        try {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        } catch (\Throwable $e) {
            return null;
        }
        if (!in_array((string)$mime, ['image/png', 'image/jpeg', 'image/webp'], true)) return null;

        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'coa' . DIRECTORY_SEPARATOR . 'signatures' . DIRECTORY_SEPARATOR . $signatoryId;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) return null;
        $name = 'signature.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        foreach (['png', 'jpg', 'webp'] as $old) {
            if ($old !== $name) {
                $oldPath = $dir . DIRECTORY_SEPARATOR . 'signature.' . $old;
                if (is_file($oldPath)) @unlink($oldPath);
            }
        }
        if (!move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $name)) return null;
        return 'storage/coa/signatures/' . $signatoryId . '/' . $name;
    }

    /** Cancel still-queued rows of a batch (no mail is sent). */
    public function cancelBatch(): void
    {
        if (!$this->requireAllFather('POST') || !$this->requireCsrf()) return;
        $batchId = (int)($_POST['batch_id'] ?? 0);
        if ($batchId <= 0) { header('Location: ?r=admin_coa_monitor'); return; }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM coa_sends WHERE batch_id = ? AND status = 'queued'");
        $stmt->execute([$batchId]);
        $_SESSION['coa_flash'] = ['type' => 'success', 'message' => $stmt->rowCount() . ' queued row(s) cancelled - no mail will be sent.'];
        header('Location: ?r=admin_coa_monitor&batch_id=' . $batchId);
    }

    /**
     * Compose: send (or schedule) the checked attendees against a template.
     * The template supplies venue/topic/particulars/signatory for the batch
     * snapshot; the event row is never mutated.
     */
    public function sendSelected(): void
    {
        if (!$this->requireAllFather('POST') || !$this->requireCsrf()) return;
        $eventId = (int)($_POST['event_id'] ?? 0);
        $templateId = (int)($_POST['template_id'] ?? 0);
        $date = trim((string)($_POST['attendance_date'] ?? ''));
        $sendAtRaw = trim((string)($_POST['send_at'] ?? ''));
        $do = (string)($_POST['do'] ?? 'send');
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['participant_ids'] ?? [])), static fn($v) => $v > 0));
        $ids = array_slice($ids, 0, 50);
        $backTo = '?r=admin_coa_monitor' . ($eventId > 0 ? '&event_id=' . $eventId : '')
            . '&compose_event_id=' . $eventId . '&compose_date=' . urlencode($date ?: date('Y-m-d')) . '&template=' . $templateId . '&compose=1';

        if ($eventId <= 0 || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1 || !count($ids)) {
            $_SESSION['coa_flash'] = ['type' => 'danger', 'message' => 'Pick at least one attendee and a valid date.'];
            header('Location: ' . $backTo);
            return;
        }
        $sendAt = null;
        $source = 'manual';
        if ($do === 'schedule') {
            $ts = strtotime($sendAtRaw);
            if ($ts === false || $ts <= time()) {
                $_SESSION['coa_flash'] = ['type' => 'danger', 'message' => 'Pick a future schedule time first.'];
                header('Location: ' . $backTo);
                return;
            }
            $sendAt = date('Y-m-d H:i:s', $ts);
            $source = 'scheduled';
        }

        $pdo = Database::pdo();
        $overrides = [];
        if ($templateId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM coa_templates WHERE id = ? LIMIT 1');
            $stmt->execute([$templateId]);
            $template = $stmt->fetch();
            if (!$template) {
                $_SESSION['coa_flash'] = ['type' => 'danger', 'message' => 'Template not found.'];
                header('Location: ' . $backTo);
                return;
            }
            $overrides = [
                'template_id' => $templateId,
                'venue' => (string)$template['venue'],
                'purpose' => (string)$template['purpose'],
                'particulars' => (string)$template['particulars'],
                'signatory_name' => (string)$template['signatory_name'],
                'signatory_title' => (string)$template['signatory_title'],
                'signatory_path' => (string)$template['signatory_path'],
                'logo_path' => (string)$template['logo_path'],
            ];
        }

        $result = CoaService::sendBatch($eventId, $ids, $date, $overrides, $source, $sendAt);
        $_SESSION['coa_flash'] = ['type' => 'success', 'message' => $result['processed'] . ' attendee(s) processed, ' . $result['sent'] . ' sent'
            . ($sendAt !== null ? ' (queued for ' . $sendAt . ' Asia/Manila).' : '.')];
        header('Location: ?r=admin_coa_monitor&event_id=' . $eventId . ($sendAt !== null ? '&status=queued' : ''));
    }
}
