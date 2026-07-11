<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;
use App\Services\SignatureService;

class AdminAttendanceController
{
    private function requireAdmin(): bool
    {
        if (empty($_SESSION['admin_id'])) { header('Location: ?r=admin_login'); return false; }
        return true;
    }

    public function list(): void
    {
        if (!$this->requireAdmin()) return;
        $pdo = Database::pdo();
        $date = trim((string)($_GET['date'] ?? ''));
        $agency = trim((string)($_GET['agency'] ?? ''));
        $name = trim((string)($_GET['name'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = 20;
        $offset = ($page - 1) * $per;

        $selectedDate = $date !== '' ? $date : date('Y-m-d');
        $eventId = $this->getActiveEventId($pdo);

        $joinOn = "a.participant_id = p.id AND a.attendance_date = ? AND COALESCE(a.signature_path, '') <> ''";
        $bind = [$selectedDate];
        if ($eventId !== null) {
            $joinOn .= ' AND (a.event_id = ? OR a.event_id IS NULL)';
            $bind[] = $eventId;
        }

        $where = [];
        if ($agency !== '') { $where[] = 'p.agency LIKE ?'; $bind[] = "%{$agency}%"; }
        if ($name !== '') { $where[] = '(p.first_name LIKE ? OR p.last_name LIKE ?)'; $bind[] = "%{$name}%"; $bind[] = "%{$name}%"; }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $pdo->prepare(
            "SELECT SQL_CALC_FOUND_ROWS
                p.id AS participant_id,
                p.uuid,
                p.first_name,
                p.last_name,
                p.agency,
                a.id,
                a.attendance_date,
                a.time_in,
                a.signature_path,
                CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END AS is_present
             FROM participants p
             LEFT JOIN attendance a ON {$joinOn}
             {$sqlWhere}
             ORDER BY is_present DESC, p.last_name ASC, p.first_name ASC
             LIMIT {$per} OFFSET {$offset}"
        );
        $stmt->execute($bind);
        $rows = $stmt->fetchAll();
        $total = (int)$pdo->query('SELECT FOUND_ROWS() AS t')->fetch()['t'];
        $pages = max(1, (int)ceil($total / $per));

        $kpi = $this->computeKpis($pdo, $selectedDate);

        $date = $selectedDate;
        $data = array_merge(
            compact('rows', 'page', 'pages', 'date', 'agency', 'name', 'total', 'selectedDate'),
            [
                'selectedDateCount' => $kpi['dateCount'],
                'totalRegistered' => $kpi['totalRegistered'],
                'attendanceRate' => $kpi['attendanceRate'],
                'recentCount' => $kpi['recentCount'],
                'peakHourText' => $kpi['peakHourText'],
            ]
        );
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_attendance.php';
    }

    public function kpiJson(): void
    {
        if (!$this->requireAdmin()) return;
        header('Content-Type: application/json');
        $pdo = Database::pdo();
        $selectedDate = isset($_GET['date']) && trim($_GET['date']) !== '' ? trim($_GET['date']) : date('Y-m-d');
        echo json_encode($this->computeKpis($pdo, $selectedDate));
    }

    public function kpiStream(): void
    {
        if (!$this->requireAdmin()) return;

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', '0');
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        $pdo = Database::pdo();
        $selectedDate = isset($_GET['date']) && trim($_GET['date']) !== '' ? trim($_GET['date']) : date('Y-m-d');
        $lastPayload = '';

        while (!connection_aborted()) {
            $kpi = $this->computeKpis($pdo, $selectedDate);
            $payload = json_encode($kpi);
            if ($payload !== $lastPayload) {
                echo 'data: ' . $payload . "\n\n";
                $lastPayload = $payload;
                flush();
            }
            sleep(3);
        }
    }

    private function getActiveEventId(\PDO $pdo): ?int
    {
        $event = $pdo->query('SELECT id FROM events WHERE active=1 ORDER BY id DESC LIMIT 1')->fetch();
        return $event ? (int)$event['id'] : null;
    }

    /**
     * @return array{0:string,1:array<int,mixed>}
     */
    private function presentAttendanceScope(\PDO $pdo, string $alias = 'a'): array
    {
        $sql = "COALESCE({$alias}.signature_path, '') <> ''";
        $params = [];
        $eventId = $this->getActiveEventId($pdo);
        if ($eventId !== null) {
            $sql .= " AND ({$alias}.event_id = ? OR {$alias}.event_id IS NULL)";
            $params[] = $eventId;
        }
        return [$sql, $params];
    }

    private function isPresentForDate(\PDO $pdo, int $participantId, string $date): bool
    {
        [$scopeSql, $scopeParams] = $this->presentAttendanceScope($pdo);
        $stmt = $pdo->prepare(
            "SELECT id FROM attendance WHERE participant_id = ? AND attendance_date = ? AND {$scopeSql} LIMIT 1"
        );
        $stmt->execute(array_merge([$participantId, $date], $scopeParams));
        return $stmt->fetch() !== false;
    }

    private function computeKpis(\PDO $pdo, string $selectedDate): array
    {
        [$scopeSql, $scopeParams] = $this->presentAttendanceScope($pdo);

        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT a.participant_id) FROM attendance a WHERE a.attendance_date = ? AND {$scopeSql}"
        );
        $stmt->execute(array_merge([$selectedDate], $scopeParams));
        $dateCount = (int)$stmt->fetchColumn();

        $totalRegistered = (int)$pdo->query('SELECT COUNT(*) FROM participants')->fetchColumn();
        $attendanceRate = $totalRegistered > 0 ? round(($dateCount / $totalRegistered) * 100, 1) : 0;

        $recentCount = 0;
        if ($selectedDate === date('Y-m-d')) {
            $lastHourTime = date('H:i:s', strtotime('-1 hour'));
            $stmt = $pdo->prepare(
                "SELECT COUNT(DISTINCT a.participant_id) FROM attendance a WHERE a.attendance_date = ? AND a.time_in >= ? AND {$scopeSql}"
            );
            $stmt->execute(array_merge([$selectedDate, $lastHourTime], $scopeParams));
            $recentCount = (int)$stmt->fetchColumn();
        }

        $stmt = $pdo->prepare(
            "SELECT HOUR(a.time_in) as hour, COUNT(DISTINCT a.participant_id) as cnt FROM attendance a WHERE a.attendance_date = ? AND {$scopeSql} GROUP BY HOUR(a.time_in) ORDER BY cnt DESC LIMIT 1"
        );
        $stmt->execute(array_merge([$selectedDate], $scopeParams));
        $peakHour = $stmt->fetch();
        $peakHourText = $peakHour ? sprintf('%02d:00 (%d sign-ins)', (int)$peakHour['hour'], (int)$peakHour['cnt']) : 'N/A';

        return [
            'dateCount' => $dateCount,
            'totalRegistered' => $totalRegistered,
            'attendanceRate' => $attendanceRate,
            'recentCount' => $recentCount,
            'peakHourText' => $peakHourText,
            'selectedDate' => $selectedDate,
            'timestamp' => time(),
        ];
    }

    public function searchParticipants(): void
    {
        if (!$this->requireAdmin()) return;
        header('Content-Type: application/json');
        
        $query = trim((string)($_GET['q'] ?? ''));
        if (strlen($query) < 2) {
            echo json_encode(['results' => []]);
            return;
        }
        
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT id, uuid, first_name, last_name, middle_name, agency, email FROM participants WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ? ORDER BY last_name, first_name LIMIT 20");
        $searchTerm = "%{$query}%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $results = $stmt->fetchAll();

        $attendanceDate = isset($_GET['date']) && trim((string)$_GET['date']) !== ''
            ? trim((string)$_GET['date'])
            : date('Y-m-d');

        foreach ($results as &$result) {
            $result['already_marked'] = $this->isPresentForDate($pdo, (int)$result['id'], $attendanceDate);
        }
        
        echo json_encode(['results' => $results]);
    }

    public function manualAttendance(): void
    {
        if (!$this->requireAdmin()) return;
        header('Content-Type: application/json');
        
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!function_exists('csrf_check') || !csrf_check($csrf)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'csrf']);
            return;
        }
        
        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid']);
            return;
        }
        
        $participantId = (int)($payload['participant_id'] ?? 0);
        $signature = (string)($payload['signature'] ?? '');
        $attendanceDate = trim((string)($payload['date'] ?? date('Y-m-d')));
        
        if ($participantId <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'missing_participant']);
            return;
        }
        
        $pdo = Database::pdo();
        
        // Check if participant exists
        $stmt = $pdo->prepare('SELECT id, uuid FROM participants WHERE id = ?');
        $stmt->execute([$participantId]);
        $participant = $stmt->fetch();
        if (!$participant) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'not_found']);
            return;
        }
        
        // Create default signature if none provided
        if ($signature === '') {
            // Create a simple 1x1 transparent PNG as default signature
            $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        }
        
        // Get event info
        $event = $pdo->query('SELECT id, enforce_single_time_in FROM events WHERE active=1 ORDER BY id DESC LIMIT 1')->fetch();
        $eventId = $event ? (int)$event['id'] : null;
        $enforce = $event ? (int)$event['enforce_single_time_in'] === 1 : false;
        
        // Check if already marked (if enforcement is enabled)
        if ($enforce) {
            $chk = $pdo->prepare('SELECT id FROM attendance WHERE participant_id=? AND attendance_date=?' . ($eventId ? ' AND event_id=?' : ''));
            $bind = $eventId ? [$participantId, $attendanceDate, $eventId] : [$participantId, $attendanceDate];
            $chk->execute($bind);
            if ($chk->fetch()) {
                echo json_encode(['ok' => false, 'error' => 'already_marked']);
                return;
            }
        }
        
        // Save signature
        $path = SignatureService::saveBase64($participant['uuid'], $signature);
        $time = date('H:i:s');
        
        // Insert attendance
        $ins = $pdo->prepare('INSERT INTO attendance (participant_id, attendance_date, time_in, signature_path, event_id) VALUES (?,?,?,?,?)');
        $ins->execute([$participantId, $attendanceDate, $time, $path, $eventId]);
        
        echo json_encode(['ok' => true, 'message' => 'Attendance marked successfully']);
    }
}
