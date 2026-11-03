<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/** Plan#18: All Father privacy job list (search, sort, page size). */
final class PrivacyJobTable
{
    public const PER_OPTIONS = ['10', '20', '40', '50', '100', 'all'];
    public const PER_DEFAULT = '10';

    private const SORTS = [
        'id' => 'j.id',
        'event' => 'e.name',
        'status' => 'j.status',
        'people' => 'j.participant_count',
        'mailed' => 'j.mailed',
        'skipped' => 'j.skipped_no_email',
        'when' => 'j.created_at',
    ];

    /**
     * @param array<string,mixed> $get
     * @return array{
     *   rows:list<array<string,mixed>>,
     *   pager:array{page:int,pages:int,offset:int,per:int,total:int,from:int,to:int},
     *   q:string, sort:string, dir:string, perChoice:string
     * }
     */
    public static function fromRequest(PDO $pdo, array $get): array
    {
        Database::ensurePrivacyFacility($pdo);
        $q = trim((string)($get['q'] ?? ''));
        $sort = (string)($get['sort'] ?? 'when');
        if (!isset(self::SORTS[$sort])) {
            $sort = 'when';
        }
        $dir = strtolower((string)($get['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $perChoice = Paginator::perChoice(
            isset($get['per']) ? (string)$get['per'] : self::PER_DEFAULT,
            self::PER_OPTIONS,
            self::PER_DEFAULT
        );

        $where = '1=1';
        $bind = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where = '(e.name LIKE ? OR j.status LIKE ? OR CAST(j.id AS CHAR) LIKE ? OR CAST(j.event_id AS CHAR) LIKE ?)';
            $bind = [$like, $like, $like, $like];
        }

        $count = $pdo->prepare(
            "SELECT COUNT(*) FROM privacy_jobs j LEFT JOIN events e ON e.id = j.event_id WHERE {$where}"
        );
        $count->execute($bind);
        $total = (int)$count->fetchColumn();
        $per = Paginator::perSize($perChoice, $total, self::PER_OPTIONS, 10);
        $pager = Paginator::make((int)($get['page'] ?? 1), $per, $total);
        $order = self::SORTS[$sort] . ' ' . $dir . ', j.id DESC';

        $sql = "SELECT j.*, e.name AS event_name
                FROM privacy_jobs j
                LEFT JOIN events e ON e.id = j.event_id
                WHERE {$where}
                ORDER BY {$order}
                LIMIT " . (int)$pager['per'] . ' OFFSET ' . (int)$pager['offset'];
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
