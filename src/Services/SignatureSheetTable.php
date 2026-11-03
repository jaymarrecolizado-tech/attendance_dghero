<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/** Plan#17 sign sheet: page, sort, and filter by ink count. */
final class SignatureSheetTable
{
    public const PER_OPTIONS = ['10', '20', '40', '50', '100', 'all'];
    public const PER_DEFAULT = '10';

    /**
     * @param array<string,mixed> $event
     * @param array<string,mixed> $get
     * @param list<string> $days
     * @return array{
     *   rows:list<array<string,mixed>>,
     *   pager:array{page:int,pages:int,offset:int,per:int,total:int,from:int,to:int},
     *   sort:string, dir:string, perChoice:string, signed:string, q:string
     * }
     */
    public static function fromRequest(PDO $pdo, array $event, array $days, array $get): array
    {
        $eventId = (int)($event['id'] ?? 0);
        $sort = strtolower(trim((string)($get['sort'] ?? 'sigs')));
        if (!in_array($sort, ['sigs', 'name'], true)) {
            $sort = 'sigs';
        }
        $dir = strtolower(trim((string)($get['dir'] ?? 'desc'))) === 'asc' ? 'asc' : 'desc';
        $q = trim((string)($get['q'] ?? ''));
        $dayCount = count($days);
        $signed = self::normalizeSigned((string)($get['signed'] ?? 'all'), $dayCount);
        $perChoice = Paginator::perChoice(
            isset($get['per']) ? (string)$get['per'] : self::PER_DEFAULT,
            self::PER_OPTIONS,
            self::PER_DEFAULT
        );

        $inkSql = "a.status = 'present' AND a.signature_path IS NOT NULL AND TRIM(a.signature_path) <> ''";
        $joinDays = '';
        $joinBind = [$eventId];
        if ($days !== []) {
            $placeholders = implode(',', array_fill(0, count($days), '?'));
            $joinDays = " AND a.attendance_date IN ({$placeholders})";
            $joinBind = array_merge([$eventId], $days);
        }
        $where = 'p.event_id = ?';
        $whereBind = [$eventId];
        if ($q !== '') {
            $where .= ' AND (p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT(p.first_name, \' \', p.last_name) LIKE ?)';
            $like = '%' . $q . '%';
            $whereBind[] = $like;
            $whereBind[] = $like;
            $whereBind[] = $like;
        }
        $having = self::havingSql($signed, $dayCount);
        $inner = "SELECT p.id,
                    SUM(CASE WHEN {$inkSql} THEN 1 ELSE 0 END) AS sig_count
                  FROM participants p
                  LEFT JOIN attendance a ON a.participant_id = p.id AND a.event_id = ?{$joinDays}
                  WHERE {$where}
                  GROUP BY p.id
                  HAVING {$having}";
        $count = $pdo->prepare("SELECT COUNT(*) FROM ({$inner}) t");
        $count->execute(array_merge($joinBind, $whereBind));
        $total = (int)$count->fetchColumn();
        $per = Paginator::perSize($perChoice, $total, self::PER_OPTIONS, 10);
        $pager = Paginator::make((int)($get['page'] ?? 1), $per, $total);
        $order = $sort === 'name'
            ? "p.last_name {$dir}, p.first_name {$dir}, p.id ASC"
            : "sig_count {$dir}, p.last_name ASC, p.first_name ASC, p.id ASC";

        $listSql = "SELECT p.id, p.uuid, p.first_name, p.last_name, p.is_vip,
                      SUM(CASE WHEN {$inkSql} THEN 1 ELSE 0 END) AS sig_count
                    FROM participants p
                    LEFT JOIN attendance a ON a.participant_id = p.id AND a.event_id = ?{$joinDays}
                    WHERE {$where}
                    GROUP BY p.id, p.uuid, p.first_name, p.last_name, p.is_vip
                    HAVING {$having}
                    ORDER BY {$order}
                    LIMIT " . (int)$pager['per'] . ' OFFSET ' . (int)$pager['offset'];
        $stmt = $pdo->prepare($listSql);
        $stmt->execute(array_merge($joinBind, $whereBind));
        $rows = $stmt->fetchAll() ?: [];
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['id'];
        }
        $byPerson = self::cellsFor($pdo, $eventId, $days, $ids);
        foreach ($rows as &$row) {
            $pid = (int)$row['id'];
            $row['sig_count'] = (int)$row['sig_count'];
            $row['cells'] = $byPerson[$pid] ?? [];
        }
        unset($row);

        return [
            'rows' => $rows,
            'pager' => $pager,
            'sort' => $sort,
            'dir' => $dir,
            'perChoice' => $perChoice,
            'signed' => $signed,
            'q' => $q,
        ];
    }

    public static function normalizeSigned(string $raw, int $dayCount): string
    {
        $raw = strtolower(trim($raw));
        if ($raw === '' || $raw === 'all') {
            return 'all';
        }
        if ($raw === 'incomplete' || $raw === 'complete') {
            return $raw;
        }
        if (ctype_digit($raw) && (int)$raw >= 0 && (int)$raw <= max(0, $dayCount)) {
            return (string)((int)$raw);
        }
        return 'all';
    }

    /** @return list<array{value:string,label:string}> */
    public static function signedOptions(int $dayCount): array
    {
        $opts = [
            ['value' => 'all', 'label' => 'All signatures'],
            ['value' => 'incomplete', 'label' => 'Incomplete'],
            ['value' => 'complete', 'label' => 'Complete'],
        ];
        for ($n = 0; $n <= max(0, $dayCount); $n++) {
            $opts[] = ['value' => (string)$n, 'label' => $n . '/' . $dayCount];
        }
        return $opts;
    }

    private static function havingSql(string $signed, int $dayCount): string
    {
        if ($signed === 'incomplete') {
            return 'sig_count < ' . max(0, $dayCount);
        }
        if ($signed === 'complete') {
            return 'sig_count = ' . max(0, $dayCount);
        }
        if (ctype_digit($signed)) {
            return 'sig_count = ' . (int)$signed;
        }
        return '1=1';
    }

    /**
     * @param list<string> $days
     * @param list<int> $ids
     * @return array<int,array<string,array<string,mixed>>>
     */
    private static function cellsFor(PDO $pdo, int $eventId, array $days, array $ids): array
    {
        $byPerson = [];
        if ($days === [] || $ids === []) {
            return $byPerson;
        }
        $dayPh = implode(',', array_fill(0, count($days), '?'));
        $idPh = implode(',', array_fill(0, count($ids), '?'));
        $att = $pdo->prepare(
            "SELECT id, participant_id, attendance_date, status, signature_path, time_in
             FROM attendance
             WHERE event_id = ? AND attendance_date IN ({$dayPh}) AND participant_id IN ({$idPh})"
        );
        $att->execute(array_merge([$eventId], $days, $ids));
        foreach ($att->fetchAll() ?: [] as $a) {
            $byPerson[(int)$a['participant_id']][(string)$a['attendance_date']] = $a;
        }
        return $byPerson;
    }
}
