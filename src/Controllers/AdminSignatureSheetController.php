<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ResolvesEventContext;
use App\Services\AuthService;
use App\Services\CoaService;
use App\Services\Database;
use App\Services\Logger;
use App\Services\RateLimiter;
use App\Services\SignatureService;
use App\Services\SignatureSheetTable;

class AdminSignatureSheetController
{
    use ResolvesEventContext;

    private const MAX_DAYS = 31;

    public function list(): void
    {
        $pdo = Database::pdo();
        $event = $this->requireEventContext($pdo, ['event_admin', 'checker']);
        if (!$event) {
            return;
        }
        $days = self::eventDays($event);
        $list = SignatureSheetTable::fromRequest($pdo, $event, $days, $_GET);
        $rows = $list['rows'];
        $pager = $list['pager'];
        $sort = $list['sort'];
        $dir = $list['dir'];
        $perChoice = $list['perChoice'];
        $signed = $list['signed'];
        $q = $list['q'];
        $eventName = (string)($event['name'] ?? '');
        $spanLabel = self::spanLabel($days);
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_signature_sheet.php';
    }

    public function replicate(): void
    {
        header('Content-Type: application/json');
        $pdo = Database::pdo();
        $event = $this->requireEventContext($pdo, ['event_admin', 'checker'], true);
        if (!$event) {
            return;
        }
        $eventId = (int)$event['id'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!RateLimiter::allow('sig_repl:' . $ip, 20, 60)) {
            http_response_code(429);
            echo json_encode(['error' => 'rate_limited']);
            return;
        }
        $payload = json_decode((string)file_get_contents('php://input'), true);
        $payload = is_array($payload) ? $payload : [];
        $csrf = function_exists('csrf_from_request') ? csrf_from_request($payload) : (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!function_exists('csrf_check') || !csrf_check($csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'csrf']);
            return;
        }
        $uuid = trim((string)($payload['uuid'] ?? ''));
        if ($uuid === '') {
            http_response_code(422);
            echo json_encode(['error' => 'missing']);
            return;
        }
        $stmt = $pdo->prepare('SELECT id, uuid FROM participants WHERE uuid = ? AND event_id = ?');
        $stmt->execute([$uuid, $eventId]);
        $person = $stmt->fetch();
        if (!$person) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
            return;
        }
        $days = self::eventDays($event);
        $result = self::applyReplicate($pdo, $eventId, (int)$person['id'], (string)$person['uuid'], $days);
        Logger::log(AuthService::id(), 'signature_replicate', [
            'uuid' => $uuid,
            'copied' => $result['copied'],
            'error' => $result['error'],
            'ip' => $ip,
        ], $eventId);
        $next = function_exists('csrf_rotate') ? csrf_rotate() : '';
        echo json_encode(['ok' => $result['error'] === '', 'copied' => $result['copied'], 'error' => $result['error'], 'csrf' => $next]);
    }

    public function replicateAll(): void
    {
        header('Content-Type: application/json');
        $pdo = Database::pdo();
        $event = $this->requireEventContext($pdo, ['event_admin', 'checker'], true);
        if (!$event) {
            return;
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!RateLimiter::allow('sig_repl_all:' . $ip, 3, 120)) {
            http_response_code(429);
            echo json_encode(['error' => 'rate_limited']);
            return;
        }
        $payload = json_decode((string)file_get_contents('php://input'), true);
        $payload = is_array($payload) ? $payload : [];
        $csrf = function_exists('csrf_from_request') ? csrf_from_request($payload) : (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!function_exists('csrf_check') || !csrf_check($csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'csrf']);
            return;
        }
        @set_time_limit(300);
        $days = self::eventDays($event);
        $eventId = (int)$event['id'];
        $result = self::applyReplicateMissing($pdo, $eventId, $days);
        Logger::log(AuthService::id(), 'signature_replicate_all', [
            'people' => $result['people'],
            'copied' => $result['copied'],
            'skipped' => $result['skipped'],
            'ip' => $ip,
        ], $eventId);
        $next = function_exists('csrf_rotate') ? csrf_rotate() : '';
        echo json_encode([
            'ok' => true,
            'people' => $result['people'],
            'copied' => $result['copied'],
            'skipped' => $result['skipped'],
            'csrf' => $next,
        ]);
    }

    /**
     * Copy each person's latest ink onto their empty event days.
     *
     * @param list<string> $days
     * @return array{people:int,copied:int,skipped:int}
     */
    public static function applyReplicateMissing(\PDO $pdo, int $eventId, array $days): array
    {
        $stmt = $pdo->prepare('SELECT id, uuid FROM participants WHERE event_id = ? ORDER BY id ASC');
        $stmt->execute([$eventId]);
        $out = ['people' => 0, 'copied' => 0, 'skipped' => 0];
        foreach ($stmt->fetchAll() ?: [] as $person) {
            $result = self::applyReplicate($pdo, $eventId, (int)$person['id'], (string)$person['uuid'], $days);
            if ($result['copied'] > 0) {
                $out['people']++;
                $out['copied'] += $result['copied'];
            } else {
                $out['skipped']++;
            }
        }
        return $out;
    }

    /**
     * Copy the latest ink on this person onto every empty event day.
     *
     * @param list<string> $days
     * @return array{copied:int,error:string}
     */
    public static function applyReplicate(\PDO $pdo, int $eventId, int $participantId, string $uuid, array $days): array
    {
        if ($days === []) {
            return ['copied' => 0, 'error' => 'no_days'];
        }
        $placeholders = implode(',', array_fill(0, count($days), '?'));
        $stmt = $pdo->prepare(
            "SELECT id, attendance_date, status, signature_path, time_in
             FROM attendance
             WHERE event_id = ? AND participant_id = ? AND attendance_date IN ({$placeholders})
             ORDER BY attendance_date DESC"
        );
        $stmt->execute(array_merge([$eventId, $participantId], $days));
        $byDay = [];
        $sourcePath = '';
        foreach ($stmt->fetchAll() as $row) {
            $day = (string)$row['attendance_date'];
            $byDay[$day] = $row;
            if ($sourcePath === ''
                && (string)($row['status'] ?? '') === 'present'
                && trim((string)($row['signature_path'] ?? '')) !== ''
            ) {
                $resolved = CoaService::resolvePath((string)$row['signature_path']);
                if (is_file($resolved)) {
                    $sourcePath = $resolved;
                }
            }
        }
        if ($sourcePath === '') {
            return ['copied' => 0, 'error' => 'no_source'];
        }
        $copied = 0;
        foreach ($days as $day) {
            $cell = $byDay[$day] ?? null;
            $hasInk = $cell
                && (string)($cell['status'] ?? '') === 'present'
                && trim((string)($cell['signature_path'] ?? '')) !== '';
            if ($hasInk) {
                continue;
            }
            $path = SignatureService::copyFile($uuid, $sourcePath);
            if ($cell) {
                $keepTime = (string)($cell['status'] ?? '') === 'present'
                    && trim((string)($cell['time_in'] ?? '')) !== ''
                    && (string)$cell['time_in'] !== '00:00:00';
                $timeIn = $keepTime ? (string)$cell['time_in'] : date('H:i:s');
                $pdo->prepare("UPDATE attendance SET status='present', signature_path=?, time_in=? WHERE id=?")
                    ->execute([$path, $timeIn, (int)$cell['id']]);
            } else {
                $pdo->prepare("INSERT INTO attendance (participant_id, attendance_date, time_in, signature_path, event_id, status) VALUES (?,?,?,?,?,'present')")
                    ->execute([$participantId, $day, date('H:i:s'), $path, $eventId]);
            }
            $copied++;
        }
        return ['copied' => $copied, 'error' => $copied > 0 ? '' : 'nothing_to_copy'];
    }

    /** @param array<string,mixed> $event @return list<string> */
    public static function eventDays(array $event): array
    {
        $startRaw = trim((string)($event['starts_at'] ?? ''));
        $endRaw = trim((string)($event['ends_at'] ?? ''));
        $start = $startRaw !== '' ? substr($startRaw, 0, 10) : date('Y-m-d');
        $end = $endRaw !== '' ? substr($endRaw, 0, 10) : $start;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
            $start = date('Y-m-d');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            $end = $start;
        }
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        $days = [];
        $cursor = strtotime($start . ' 00:00:00');
        $last = strtotime($end . ' 00:00:00');
        if ($cursor === false || $last === false) {
            return [date('Y-m-d')];
        }
        $n = 0;
        while ($cursor <= $last && $n < self::MAX_DAYS) {
            $days[] = date('Y-m-d', $cursor);
            $cursor = strtotime('+1 day', $cursor);
            $n++;
        }
        return $days !== [] ? $days : [date('Y-m-d')];
    }

    /** @param list<string> $days */
    public static function spanLabel(array $days): string
    {
        if ($days === []) {
            return '';
        }
        $first = $days[0];
        $last = $days[count($days) - 1];
        $fmt = static fn(string $d): string => date('F j, Y', strtotime($d . ' 00:00:00') ?: time());
        if ($first === $last) {
            return $fmt($first);
        }
        $a = getdate(strtotime($first . ' 00:00:00') ?: time());
        $b = getdate(strtotime($last . ' 00:00:00') ?: time());
        if ($a['mon'] === $b['mon'] && $a['year'] === $b['year']) {
            return date('F j', strtotime($first) ?: time()) . '–' . date('j, Y', strtotime($last) ?: time());
        }
        return $fmt($first) . ' – ' . $fmt($last);
    }
}
