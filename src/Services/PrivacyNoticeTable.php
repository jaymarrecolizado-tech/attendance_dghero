<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/** Plan#18: queued / failed / sent notice lists on Privacy. */
final class PrivacyNoticeTable
{
    public const TABS = ['jobs', 'queued', 'failed', 'sent', 'skipped'];

    private const SORTS = [
        'id' => 'n.id',
        'event' => 'n.event_name',
        'kind' => 'n.kind',
        'email' => 'n.to_email',
        'status' => 'n.status',
        'when' => 'n.updated_at',
    ];

    /** @return array<string,int> */
    public static function counts(PDO $pdo): array
    {
        Database::ensurePrivacyFacility($pdo);
        $out = ['queued' => 0, 'failed' => 0, 'sent' => 0, 'skipped' => 0];
        $rows = $pdo->query('SELECT status, COUNT(*) AS c FROM privacy_notices GROUP BY status')->fetchAll() ?: [];
        foreach ($rows as $row) {
            $status = (string)($row['status'] ?? '');
            if (isset($out[$status])) {
                $out[$status] = (int)$row['c'];
            }
        }
        return $out;
    }

    public static function tab(?string $raw): string
    {
        $raw = (string)$raw;
        return in_array($raw, self::TABS, true) ? $raw : 'jobs';
    }

    /**
     * @param array<string,mixed> $get
     * @return array{
     *   rows:list<array<string,mixed>>,
     *   pager:array{page:int,pages:int,offset:int,per:int,total:int,from:int,to:int},
     *   q:string, sort:string, dir:string, perChoice:string
     * }
     */
    public static function fromRequest(PDO $pdo, array $get, string $status): array
    {
        Database::ensurePrivacyFacility($pdo);
        $q = trim((string)($get['q'] ?? ''));
        $sort = (string)($get['sort'] ?? 'when');
        if (!isset(self::SORTS[$sort])) {
            $sort = 'when';
        }
        $dir = strtolower((string)($get['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $perChoice = Paginator::perChoice(
            isset($get['per']) ? (string)$get['per'] : PrivacyJobTable::PER_DEFAULT,
            PrivacyJobTable::PER_OPTIONS,
            PrivacyJobTable::PER_DEFAULT
        );

        $where = 'n.status = ?';
        $bind = [$status];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where .= ' AND (n.event_name LIKE ? OR n.to_email LIKE ? OR n.kind LIKE ? OR n.error LIKE ? OR CAST(n.id AS CHAR) LIKE ?)';
            array_push($bind, $like, $like, $like, $like, $like);
        }

        $count = $pdo->prepare("SELECT COUNT(*) FROM privacy_notices n WHERE {$where}");
        $count->execute($bind);
        $total = (int)$count->fetchColumn();
        $per = Paginator::perSize($perChoice, $total, PrivacyJobTable::PER_OPTIONS, 10);
        $pager = Paginator::make((int)($get['page'] ?? 1), $per, $total);
        $order = self::SORTS[$sort] . ' ' . $dir . ', n.id DESC';
        $sql = "SELECT n.* FROM privacy_notices n WHERE {$where} ORDER BY {$order} LIMIT "
            . (int)$pager['per'] . ' OFFSET ' . (int)$pager['offset'];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bind);

        return [
            'rows' => $stmt->fetchAll() ?: [],
            'pager' => $pager,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'perChoice' => $perChoice,
        ];
    }
}
