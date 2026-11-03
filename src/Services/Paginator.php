<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Shared page-window math for admin tables.
 */
final class Paginator
{
    /** @return list<string> */
    public static function perOptions(): array
    {
        return ['10', '25', '50', '100', '200', 'all'];
    }

    public static function perChoice(?string $raw, ?array $options = null, string $fallback = '25'): string
    {
        $options = $options ?? self::perOptions();
        $raw = (string)$raw;
        return in_array($raw, $options, true) ? $raw : $fallback;
    }

    public static function perSize(string $choice, int $total, ?array $options = null, int $fallback = 25): int
    {
        if ($choice === 'all') {
            return max($total, 1);
        }
        $options = $options ?? self::perOptions();
        if (in_array($choice, $options, true) && ctype_digit($choice)) {
            return max(1, (int)$choice);
        }
        return max(1, $fallback);
    }

    /**
     * @return array{page:int,pages:int,offset:int,per:int,total:int,from:int,to:int}
     */
    public static function make(int $page, int $per, int $total): array
    {
        $per = max(1, $per);
        $total = max(0, $total);
        $pages = max(1, (int)ceil($total / $per));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $per;
        $from = $total === 0 ? 0 : $offset + 1;
        $to = $total === 0 ? 0 : min($offset + $per, $total);
        return [
            'page' => $page,
            'pages' => $pages,
            'offset' => $offset,
            'per' => $per,
            'total' => $total,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Page numbers with nulls as ellipsis gaps: 1 … 8 9 10 … 20
     *
     * @return list<int|null>
     */
    public static function window(int $page, int $pages, int $radius = 2): array
    {
        $pages = max(1, $pages);
        $page = max(1, min($page, $pages));
        $left = max(1, $page - $radius);
        $right = min($pages, $page + $radius);
        $items = [];
        if ($left > 1) {
            $items[] = 1;
            if ($left > 2) {
                $items[] = null;
            }
        }
        for ($i = $left; $i <= $right; $i++) {
            $items[] = $i;
        }
        if ($right < $pages) {
            if ($right < $pages - 1) {
                $items[] = null;
            }
            $items[] = $pages;
        }
        return $items;
    }

    /**
     * @param array<string,scalar|null> $query
     */
    public static function href(array $query, string $pageKey, int $page): string
    {
        $clean = [];
        foreach ($query as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $clean[$key] = $value;
        }
        if (!isset($clean['r'])) {
            $clean['r'] = 'admin_coa_monitor';
        }
        if ($page <= 1) {
            unset($clean[$pageKey]);
        } else {
            $clean[$pageKey] = $page;
        }
        return '?' . http_build_query($clean);
    }
}
