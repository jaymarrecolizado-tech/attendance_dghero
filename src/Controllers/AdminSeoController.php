<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\Database;

class AdminSeoController
{
    public function dashboard(): void
    {
        if (!AuthService::check()) {
            header('Location: ?r=admin_login');
            return;
        }
        $pdo = Database::pdo();
        $selectedDate = trim((string)($_GET['date'] ?? ''));
        if ($selectedDate === '') {
            $selectedDate = date('Y-m-d');
        }
        $summary = $this->buildSummary($pdo, $selectedDate);
        extract($summary, EXTR_SKIP);
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_seo_dashboard.php';
    }

    public function summaryJson(): void
    {
        if (!AuthService::check()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'unauthorized']);
            return;
        }
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        $pdo = Database::pdo();
        $selectedDate = trim((string)($_GET['date'] ?? ''));
        if ($selectedDate === '') {
            $selectedDate = date('Y-m-d');
        }
        echo json_encode($this->buildSummary($pdo, $selectedDate));
    }

    /**
     * @return array<string,mixed>
     */
    private function buildSummary(\PDO $pdo, string $selectedDate): array
    {
        $event = $pdo->query('SELECT id, name, created_at FROM events WHERE active=1 ORDER BY id DESC LIMIT 1')->fetch() ?: null;
        $eventId = $event ? (int)$event['id'] : null;

        $kpi = $this->computeKpis($pdo, $selectedDate, $eventId);
        $vips = $this->vipRows($pdo, $selectedDate, $eventId);
        $agencyRollup = $this->agencyRollup($vips);
        $recentVip = array_values(array_filter($vips, static fn(array $r): bool => $r['guest_status'] === 'present' && !empty($r['time_in'])));
        usort($recentVip, static function (array $a, array $b): int {
            return strcmp((string)($b['time_in'] ?? ''), (string)($a['time_in'] ?? ''));
        });
        $recentVip = array_slice($recentVip, 0, 12);

        $graceMinutes = 30;
        $attention = [];
        if ($selectedDate === date('Y-m-d')) {
            $cutoff = date('H:i:s', strtotime('-' . $graceMinutes . ' minutes'));
            foreach ($vips as $vip) {
                if ($vip['guest_status'] === 'in_vicinity') {
                    $attention[] = $vip;
                }
            }
            // Prefer VIPs still expected after a soft grace from midnight of event day.
            if ($cutoff < '08:00:00') {
                // Keep full vicinity list early in the day.
            }
        } else {
            foreach ($vips as $vip) {
                if ($vip['guest_status'] === 'in_vicinity') {
                    $attention[] = $vip;
                }
            }
        }

        $vipCounts = [
            'total' => count($vips),
            'present' => 0,
            'in_vicinity' => 0,
            'absent' => 0,
        ];
        foreach ($vips as $vip) {
            $vipCounts[$vip['guest_status']] = ($vipCounts[$vip['guest_status']] ?? 0) + 1;
        }

        return [
            'selectedDate' => $selectedDate,
            'event' => $event ? [
                'id' => (int)$event['id'],
                'name' => (string)$event['name'],
            ] : null,
            'kpi' => $kpi,
            'vipCounts' => $vipCounts,
            'vips' => $vips,
            'agencyRollup' => $agencyRollup,
            'recentVip' => $recentVip,
            'attention' => array_slice($attention, 0, 20),
            'refreshedAt' => date('c'),
            'timestamp' => time(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function computeKpis(\PDO $pdo, string $selectedDate, ?int $eventId): array
    {
        $scopeSql = "COALESCE(a.signature_path, '') <> '' AND COALESCE(a.status, 'present') = 'present'";
        $scopeParams = [];
        if ($eventId !== null) {
            $scopeSql .= ' AND (a.event_id = ? OR a.event_id IS NULL)';
            $scopeParams[] = $eventId;
        }

        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT a.participant_id) FROM attendance a WHERE a.attendance_date = ? AND {$scopeSql}"
        );
        $stmt->execute(array_merge([$selectedDate], $scopeParams));
        $dateCount = (int)$stmt->fetchColumn();

        $totalRegistered = (int)$pdo->query('SELECT COUNT(*) FROM participants')->fetchColumn();

        $absentSql = "SELECT COUNT(DISTINCT a.participant_id) FROM attendance a WHERE a.attendance_date = ? AND a.status = 'absent'";
        $absentBind = [$selectedDate];
        if ($eventId !== null) {
            $absentSql .= ' AND (a.event_id = ? OR a.event_id IS NULL)';
            $absentBind[] = $eventId;
        }
        $absentStmt = $pdo->prepare($absentSql);
        $absentStmt->execute($absentBind);
        $absentCount = (int)$absentStmt->fetchColumn();

        $vicinityCount = max(0, $totalRegistered - $dateCount - $absentCount);
        $accountedFor = max(0, $totalRegistered - $absentCount);
        $attendanceRate = $totalRegistered > 0 ? round(($accountedFor / $totalRegistered) * 100, 1) : 0;

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
            'vicinityCount' => $vicinityCount,
            'absentCount' => $absentCount,
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function vipRows(\PDO $pdo, string $selectedDate, ?int $eventId): array
    {
        $joinOn = 'a.participant_id = p.id AND a.attendance_date = ?';
        $bind = [$selectedDate];
        if ($eventId !== null) {
            $joinOn .= ' AND (a.event_id = ? OR a.event_id IS NULL)';
            $bind[] = $eventId;
        }

        $stmt = $pdo->prepare(
            "SELECT p.id, p.first_name, p.last_name, p.agency, p.sector, p.designation,
                    a.id AS attendance_id, a.signature_path, a.status AS attendance_status, a.time_in
             FROM participants p
             LEFT JOIN attendance a ON {$joinOn}
             WHERE p.is_vip = 1
             ORDER BY p.last_name ASC, p.first_name ASC"
        );
        $stmt->execute($bind);
        $rows = $stmt->fetchAll() ?: [];

        $out = [];
        foreach ($rows as $row) {
            $status = $this->resolveGuestStatus($row);
            $out[] = [
                'id' => (int)$row['id'],
                'name' => trim((string)$row['first_name'] . ' ' . (string)$row['last_name']),
                'agency' => (string)($row['agency'] ?? ''),
                'sector' => (string)($row['sector'] ?? ''),
                'designation' => (string)($row['designation'] ?? ''),
                'guest_status' => $status,
                'time_in' => $row['time_in'] ?? null,
            ];
        }
        return $out;
    }

    /**
     * @param list<array<string,mixed>> $vips
     * @return list<array<string,mixed>>
     */
    private function agencyRollup(array $vips): array
    {
        $map = [];
        foreach ($vips as $vip) {
            $agency = trim((string)($vip['agency'] ?? '')) ?: 'Unspecified';
            if (!isset($map[$agency])) {
                $map[$agency] = [
                    'agency' => $agency,
                    'present' => 0,
                    'in_vicinity' => 0,
                    'absent' => 0,
                    'total' => 0,
                ];
            }
            $status = (string)$vip['guest_status'];
            if (!isset($map[$agency][$status])) {
                $map[$agency][$status] = 0;
            }
            $map[$agency][$status]++;
            $map[$agency]['total']++;
        }
        $list = array_values($map);
        usort($list, static fn(array $a, array $b): int => $b['total'] <=> $a['total']);
        return array_slice($list, 0, 15);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function resolveGuestStatus(array $row): string
    {
        $status = (string)($row['attendance_status'] ?? '');
        if ($status === 'absent') {
            return 'absent';
        }
        if (!empty($row['attendance_id']) && trim((string)($row['signature_path'] ?? '')) !== '') {
            return 'present';
        }
        return 'in_vicinity';
    }
}
