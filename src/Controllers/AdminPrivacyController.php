<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\Database;
use App\Services\Logger;
use App\Services\PrivacyArchive;
use App\Services\PrivacyJobTable;
use App\Services\PrivacyNotice;
use App\Services\PrivacyNoticeTable;
use App\Services\PrivacyPurgeService;

/** Plan#18: All Father privacy purge / restore monitor. */
final class AdminPrivacyController
{
    private function requireAllFather(string $method = 'GET'): bool
    {
        if (!AuthService::isAdmin()) {
            AuthService::deny($method);
            return false;
        }
        return true;
    }

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

    public function list(): void
    {
        if (!$this->requireAllFather()) {
            return;
        }
        $pdo = Database::pdo();
        Database::ensurePrivacyFacility($pdo);
        $now = PrivacyPurgeService::nowManila();
        $events = $pdo->query(
            'SELECT e.*,
                (SELECT COUNT(*) FROM participants p WHERE p.event_id = e.id) AS participant_count
             FROM events e ORDER BY e.ends_at IS NULL, e.ends_at DESC, e.id DESC'
        )->fetchAll() ?: [];
        $privacyTab = PrivacyNoticeTable::tab(isset($_GET['tab']) ? (string)$_GET['tab'] : 'jobs');
        $noticeCounts = PrivacyNoticeTable::counts($pdo);
        $jobCount = (int)$pdo->query('SELECT COUNT(*) FROM privacy_jobs')->fetchColumn();
        $jobs = [];
        $notices = [];
        $jobPager = null;
        $jobQ = trim((string)($_GET['q'] ?? ''));
        $jobSort = 'when';
        $jobDir = 'desc';
        $jobPer = PrivacyJobTable::PER_DEFAULT;
        if ($privacyTab === 'jobs') {
            $jobTable = PrivacyJobTable::fromRequest($pdo, $_GET);
            $jobs = $jobTable['rows'];
            $jobPager = $jobTable['pager'];
            $jobQ = $jobTable['q'];
            $jobSort = $jobTable['sort'];
            $jobDir = $jobTable['dir'];
            $jobPer = $jobTable['perChoice'];
        } else {
            $noticeTable = PrivacyNoticeTable::fromRequest($pdo, $_GET, $privacyTab);
            $notices = $noticeTable['rows'];
            $jobPager = $noticeTable['pager'];
            $jobQ = $noticeTable['q'];
            $jobSort = $noticeTable['sort'];
            $jobDir = $noticeTable['dir'];
            $jobPer = $noticeTable['perChoice'];
        }
        foreach ($events as &$ev) {
            $ends = trim((string)($ev['ends_at'] ?? ''));
            $ev['days_until_purge'] = null;
            if ($ends !== '' && empty($ev['privacy_purged_at'])) {
                $end = new \DateTimeImmutable($ends, new \DateTimeZone('Asia/Manila'));
                $due = $end->modify('+15 days');
                $ev['days_until_purge'] = (int)$now->diff($due)->format('%r%a');
            }
            $ev['privacy_photo_url'] = PrivacyNotice::registrationPhotoMailUrl($ev);
            $ev['deletion_preview_html'] = PrivacyNotice::deletionEmailHtml(
                'Participant',
                (string)$ev['name'],
                $ev['privacy_photo_url'] !== '' ? $ev['privacy_photo_url'] : null
            );
        }
        unset($ev);
        $flash = $_SESSION['privacy_flash'] ?? null;
        unset($_SESSION['privacy_flash']);
        $activeNav = 'admin_privacy';
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_privacy.php';
    }

    public function purgeNow(): void
    {
        if (!$this->requireAllFather('POST') || !$this->requireCsrf()) {
            return;
        }
        $eventId = (int)($_POST['event_id'] ?? 0);
        $confirm = trim((string)($_POST['confirm_name'] ?? ''));
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT name FROM events WHERE id = ?');
        $stmt->execute([$eventId]);
        $name = (string)($stmt->fetchColumn() ?: '');
        if ($eventId <= 0 || $name === '' || strcasecmp($confirm, $name) !== 0) {
            $_SESSION['privacy_flash'] = ['type' => 'danger', 'message' => 'Type the exact event name to confirm purge.'];
            header('Location: ?r=admin_privacy');
            return;
        }
        $result = PrivacyPurgeService::purgeEvent($eventId);
        $_SESSION['privacy_flash'] = $result['ok']
            ? ['type' => 'success', 'message' => !empty($result['drive'])
                ? 'Purged. Drive uploaded. Participants were emailed.'
                : 'Purged. Encrypted zip is on the server. Download it here and copy to Drive if you want. Participants were emailed.']
            : ['type' => 'danger', 'message' => 'Purge failed: ' . (string)($result['error'] ?? 'error')];
        header('Location: ?r=admin_privacy');
    }

    public function download(): void
    {
        if (!$this->requireAllFather()) {
            return;
        }
        $eventId = (int)($_GET['event_id'] ?? 0);
        if ($eventId <= 0) {
            http_response_code(400);
            echo 'Missing event';
            return;
        }
        $pdo = Database::pdo();
        Database::ensurePrivacyFacility($pdo);
        $stmt = $pdo->prepare('SELECT name, privacy_backup_path FROM events WHERE id = ?');
        $stmt->execute([$eventId]);
        $row = $stmt->fetch();
        $path = is_array($row) ? (string)($row['privacy_backup_path'] ?? '') : '';
        if (!PrivacyArchive::isLocalBackupFile($path)) {
            http_response_code(404);
            echo 'Archive is not on this server';
            return;
        }
        $real = (string)realpath($path);
        Logger::log((int)($_SESSION['admin_id'] ?? 0) ?: null, 'privacy_backup_download', [
            'event_id' => $eventId,
            'file' => basename($real),
        ], $eventId);
        header('Content-Type: application/zip');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . basename($real) . '"');
        header('Content-Length: ' . (string)filesize($real));
        readfile($real);
    }

    public function restore(): void
    {
        if (!$this->requireAllFather('POST') || !$this->requireCsrf()) {
            return;
        }
        $eventId = (int)($_POST['event_id'] ?? 0);
        $confirm = trim((string)($_POST['confirm_name'] ?? ''));
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT name FROM events WHERE id = ?');
        $stmt->execute([$eventId]);
        $name = (string)($stmt->fetchColumn() ?: '');
        if ($eventId <= 0 || $name === '' || strcasecmp($confirm, $name) !== 0) {
            $_SESSION['privacy_flash'] = ['type' => 'danger', 'message' => 'Type the exact event name to confirm restore.'];
            header('Location: ?r=admin_privacy');
            return;
        }
        $result = PrivacyPurgeService::restoreEvent($eventId);
        $_SESSION['privacy_flash'] = $result['ok']
            ? ['type' => 'success', 'message' => (int)($result['restored'] ?? 0) . ' participant(s) restored. Auto-purge is on hold for 15 days.']
            : ['type' => 'danger', 'message' => 'Restore failed: ' . (string)($result['error'] ?? 'error')];
        header('Location: ?r=admin_privacy');
    }
}
