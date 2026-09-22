<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CoaService;
use App\Services\Database;

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

    private function scopeEventId(): int
    {
        return (int)($_GET['event_id'] ?? 0);
    }

    /** Monitor: KPI strip, action forms, recent batches, optional batch detail. */
    public function monitor(): void
    {
        if (!$this->requireAllFather()) return;
        $pdo = Database::pdo();
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
            }
        }

        $flash = $_SESSION['coa_flash'] ?? null;
        unset($_SESSION['coa_flash']);
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_coa_monitor.php';
    }

    /** Manual batch: generate + email CoAs for attendees missing a sent CoA on a date. */
    public function sendNew(): void
    {
        if (!$this->requireAllFather('POST')) return;
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
        $ids = array_map('intval', array_column($missing->fetchAll(), 'id'));

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
        if (!$this->requireAllFather('POST')) return;
        $eventId = (int)($_POST['event_id'] ?? 0);
        $count = CoaService::queueFailed($eventId > 0 ? $eventId : null);
        $_SESSION['coa_flash'] = ['type' => 'success', 'message' => $count . ' failed send(s) queued for resend.'];
        header('Location: ?r=admin_coa_monitor' . ($eventId > 0 ? '&event_id=' . $eventId : ''));
    }

    /** Process up to 50 queued rows. */
    public function resendQueued(): void
    {
        if (!$this->requireAllFather('POST')) return;
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
                'name' => $_SESSION['current_event_name'] ?? 'Current Event',
                'coa_venue' => $template['venue'],
                'coa_purpose' => $template['purpose'],
                'coa_particulars' => $template['particulars'],
                'coa_signatory_name' => $template['signatory_name'],
                'coa_signatory_title' => $template['signatory_title'],
                'coa_signatory_path' => $template['signatory_path'],
                'coa_logo_path' => $template['logo_path'],
            ];
        } else {
            $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
            $stmt->execute([(int)($_SESSION['current_event_id'] ?? 0)]);
            $eventLike = $stmt->fetch() ?: [];
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
        $currentEventId = (int)($_SESSION['current_event_id'] ?? 0);
        $flash = $_SESSION['coa_flash'] ?? null;
        unset($_SESSION['coa_flash']);
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_coa_templates.php';
    }

    /** Save as template (new) or update an existing template. */
    public function templateSave(): void
    {
        if (!$this->requireAllFather('POST')) return;
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['coa_flash'] = ['type' => 'danger', 'message' => 'Template name is required.'];
            header('Location: ?r=admin_coa_templates');
            return;
        }
        $pdo = Database::pdo();
        $now = date('Y-m-d H:i:s');
        $fields = [
            'name' => mb_substr($name, 0, 120),
            'venue' => trim((string)($_POST['venue'] ?? '')),
            'purpose' => trim(strip_tags((string)($_POST['purpose'] ?? ''))),
            'particulars' => trim((string)($_POST['particulars'] ?? '')),
            'signatory_name' => trim(strip_tags((string)($_POST['signatory_name'] ?? ''))),
            'signatory_title' => trim(strip_tags((string)($_POST['signatory_title'] ?? ''))),
            'signatory_path' => trim((string)($_POST['signatory_path'] ?? '')),
            'logo_path' => trim((string)($_POST['logo_path'] ?? '')),
        ];
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE coa_templates SET name = ?, venue = ?, purpose = ?, particulars = ?, signatory_name = ?, signatory_title = ?, signatory_path = ?, logo_path = ?, updated_at = ? WHERE id = ?');
            $stmt->execute([...array_values($fields), $now, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO coa_templates (name, venue, purpose, particulars, signatory_name, signatory_title, signatory_path, logo_path, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([...array_values($fields), $now, $now]);
        }
        $_SESSION['coa_flash'] = ['type' => 'success', 'message' => 'Template saved.'];
        header('Location: ?r=admin_coa_templates');
    }

    /** Copy a template's fields onto an event's CoA columns. */
    public function templateApply(): void
    {
        if (!$this->requireAllFather('POST')) return;
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
}
