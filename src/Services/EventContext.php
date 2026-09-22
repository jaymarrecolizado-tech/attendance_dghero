<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Single resolver for multi-event context (replaces WHERE active=1).
 * Public pages use e=slug; admin uses session current_event_id.
 */
final class EventContext
{
    public const ROLE_EVENT_ADMIN = 'event_admin';

    public static function slugify(string $name, int $id = 0): string
    {
        $slug = strtolower(trim($name));
        $slug = (string)preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'event';
        }
        $slug = substr($slug, 0, 150);
        return $id > 0 ? $slug . '-' . $id : $slug;
    }

    public static function hasColumn(\PDO $pdo, string $table, string $column): bool
    {
        try {
            $stmt = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array<string,mixed>|null */
    public static function findBySlug(\PDO $pdo, string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }
        try {
            if (!self::hasColumn($pdo, 'events', 'slug')) {
                $row = $pdo->query('SELECT id, name, enforce_single_time_in, active FROM events WHERE active=1 ORDER BY id DESC LIMIT 1')->fetch();
                return $row ?: null;
            }
            $stmt = $pdo->prepare('SELECT * FROM events WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @return array<string,mixed>|null */
    public static function findById(\PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @return list<array<string,mixed>> */
    public static function openEvents(\PDO $pdo): array
    {
        try {
            if (!self::hasColumn($pdo, 'events', 'status')) {
                return $pdo->query('SELECT id, name FROM events ORDER BY id DESC')->fetchAll() ?: [];
            }
            return $pdo->query("SELECT * FROM events WHERE status = 'open' ORDER BY starts_at IS NULL, starts_at ASC, id DESC")->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return list<array<string,mixed>> */
    public static function allEvents(\PDO $pdo): array
    {
        try {
            return $pdo->query('SELECT * FROM events ORDER BY id DESC')->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function isPublicOpen(?array $event): bool
    {
        if (!$event) {
            return false;
        }
        if (!array_key_exists('status', $event)) {
            return ((int)($event['active'] ?? 1)) === 1;
        }
        if ((string)($event['status'] ?? '') !== 'open') {
            return false;
        }
        // Schedule window uses server local time, same as attendance date()/time().
        $now = time();
        if (!empty($event['starts_at']) && $now < (int)strtotime((string)$event['starts_at'])) {
            return false;
        }
        if (!empty($event['ends_at']) && $now > (int)strtotime((string)$event['ends_at'])) {
            return false;
        }
        return true;
    }

    public static function isAllFather(): bool
    {
        return AuthService::isAdmin();
    }

    /** @return list<array<string,mixed>> events with assignment role in `assign_role` */
    public static function assignedEvents(\PDO $pdo, int $adminId): array
    {
        if ($adminId <= 0) {
            return [];
        }
        try {
            if (AuthService::isAdmin()) {
                return self::allEvents($pdo);
            }
            if (!self::hasColumn($pdo, 'events', 'slug')) {
                return self::allEvents($pdo);
            }
            $stmt = $pdo->prepare('SELECT e.*, a.role AS assign_role FROM event_assignments a JOIN events e ON e.id = a.event_id WHERE a.admin_id = ? ORDER BY e.id DESC');
            $stmt->execute([$adminId]);
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array<string,mixed>|null current admin event (auto-picks and stores in session) */
    public static function currentEvent(\PDO $pdo): ?array
    {
        try {
            $adminId = (int)($_SESSION['admin_id'] ?? 0);
            if ($adminId <= 0) {
                return null;
            }
            $sessionId = (int)($_SESSION['current_event_id'] ?? 0);
            if ($sessionId > 0) {
                $event = self::findById($pdo, $sessionId);
                if ($event && self::canAccess($pdo, $adminId, $sessionId, null)) {
                    return $event;
                }
                // Forced or stale context the admin cannot access: hard-deny by
                // resolving to nothing. Never silently remap to another event.
                return null;
            }
            if (self::isAllFather()) {
                $event = null;
                try {
                    if (self::hasColumn($pdo, 'events', 'status')) {
                        $event = $pdo->query("SELECT * FROM events WHERE status = 'open' ORDER BY id DESC LIMIT 1")->fetch() ?: null;
                    }
                    if (!$event) {
                        $event = $pdo->query('SELECT * FROM events ORDER BY id DESC LIMIT 1')->fetch() ?: null;
                    }
                } catch (\Throwable $e) {
                    return null;
                }
                if ($event) {
                    $_SESSION['current_event_id'] = (int)$event['id'];
                }
                return $event ?: null;
            }
            $assigned = self::assignedEvents($pdo, $adminId);
            if ($assigned) {
                $_SESSION['current_event_id'] = (int)$assigned[0]['id'];
                return $assigned[0];
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function setCurrentEvent(int $eventId): void
    {
        $_SESSION['current_event_id'] = $eventId > 0 ? $eventId : null;
    }

    /** Effective role for an event: 'admin' for All Father, else assignment role or null. */
    public static function effectiveRole(\PDO $pdo, int $adminId, int $eventId): ?string
    {
        try {
            $stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ? LIMIT 1');
            $stmt->execute([$adminId]);
            $row = $stmt->fetch();
            if ($row && (string)($row['role'] ?? '') === AuthService::ROLE_ADMIN) {
                return AuthService::ROLE_ADMIN;
            }
            if ($eventId <= 0 || !self::hasColumn($pdo, 'events', 'slug')) {
                return isset($row['role']) ? (string)$row['role'] : null;
            }
            $s = $pdo->prepare('SELECT role FROM event_assignments WHERE admin_id = ? AND event_id = ? LIMIT 1');
            $s->execute([$adminId, $eventId]);
            $a = $s->fetch();
            return $a ? (string)$a['role'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param list<string>|null $allowed assignment roles (All Father always passes)
     */
    public static function canAccess(\PDO $pdo, int $adminId, int $eventId, ?array $allowed = null): bool
    {
        if ($adminId <= 0 || $eventId <= 0) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ? LIMIT 1');
            $stmt->execute([$adminId]);
            $row = $stmt->fetch();
            if ($row && (string)($row['role'] ?? '') === AuthService::ROLE_ADMIN) {
                return true;
            }
            if (!self::hasColumn($pdo, 'events', 'slug')) {
                return true;
            }
            $s = $pdo->prepare('SELECT role FROM event_assignments WHERE admin_id = ? AND event_id = ? LIMIT 1');
            $s->execute([$adminId, $eventId]);
            $a = $s->fetch();
            if (!$a) {
                return false;
            }
            if ($allowed === null || $allowed === []) {
                return true;
            }
            return in_array((string)$a['role'], $allowed, true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array{register:string,scan:string} */
    public static function publicLinks(string $slug): array
    {
        $e = urlencode($slug);
        return [
            'register' => "?r=register&e={$e}",
            'scan' => "?r=scan&e={$e}",
        ];
    }

    /**
     * Structured per-event branding, sanitized. Returns [] when the event has
     * no theme so guests keep the default Public Sans + federal navy look.
     * @return array{primary:string,dark:string,accent:string,welcome:string,logo_url:string,banner_url:string}
     */
    public static function themeFor(?array $event): array
    {
        if (!$event) {
            return [];
        }
        $hex = static function (string $value): string {
            $value = strtoupper(trim($value));
            return preg_match('/^#[0-9A-F]{6}$/', $value) === 1 ? $value : '';
        };
        $primary = $hex((string)($event['theme_primary'] ?? ''));
        $accent = $hex((string)($event['theme_accent'] ?? ''));
        $welcome = trim((string)($event['welcome_text'] ?? ''));
        $logoPath = trim((string)($event['logo_path'] ?? ''));
        $bannerPath = trim((string)($event['banner_path'] ?? ''));
        if ($primary === '' && $accent === '' && $welcome === '' && $logoPath === '' && $bannerPath === '') {
            return [];
        }
        $eventId = (int)$event['id'];
        $theme = [
            'primary' => $primary,
            'dark' => $primary !== '' ? self::darkenHex($primary) : '',
            'accent' => $accent,
            'welcome' => mb_substr($welcome, 0, 180),
            'logo_url' => '',
            'banner_url' => '',
        ];
        if ($logoPath !== '' && is_file($logoPath)) {
            $theme['logo_url'] = '?r=event_branding&eid=' . $eventId . '&f=logo';
        }
        if ($bannerPath !== '' && is_file($bannerPath)) {
            $theme['banner_url'] = '?r=event_branding&eid=' . $eventId . '&f=banner';
        }
        return $theme;
    }

    /** Same hue, ~28% darker, for hover and hero gradient stops. */
    public static function darkenHex(string $hex): string
    {
        $r = (int)hexdec(substr($hex, 1, 2));
        $g = (int)hexdec(substr($hex, 3, 2));
        $b = (int)hexdec(substr($hex, 5, 2));
        return sprintf('#%02X%02X%02X', (int)round($r * 0.72), (int)round($g * 0.72), (int)round($b * 0.72));
    }
}
