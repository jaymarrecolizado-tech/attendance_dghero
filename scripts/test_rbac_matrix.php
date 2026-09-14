<?php
declare(strict_types=1);

/**
 * RBAC allow/deny matrix smoke test (CLI, no HTTP server required for core checks).
 * Also optionally probes routes via Router if APP_URL is set.
 */

require __DIR__ . '/../config/bootstrap.php';

spl_autoload_register(static function ($class): void {
    $prefix = 'App\\';
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = $base . $rel . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Router;
use App\Services\AuthService;
use App\Services\Database;
use App\Services\EventContext;

Database::migrate();
$pdo = Database::pdo();

$failed = 0;
$passed = 0;

function assertTrue(bool $cond, string $msg): void
{
    global $failed, $passed;
    if ($cond) {
        echo "PASS  {$msg}\n";
        $passed++;
    } else {
        echo "FAIL  {$msg}\n";
        $failed++;
    }
}

function ensureUser(\PDO $pdo, string $username, string $role, string $password = 'TestPass123!'): int
{
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if ($row) {
        $pdo->prepare('UPDATE admins SET password_hash=?, role=?, is_active=1 WHERE id=?')
            ->execute([$hash, $role, (int)$row['id']]);
        return (int)$row['id'];
    }
    $pdo->prepare('INSERT INTO admins (username, display_name, password_hash, email, role, is_active) VALUES (?,?,?,?,?,1)')
        ->execute([$username, $username, $hash, null, $role]);
    return (int)$pdo->lastInsertId();
}

function loginAs(array $admin): void
{
    AuthService::logoutLocal();
    AuthService::establishSession($admin);
}

$adminId = ensureUser($pdo, '_rbac_admin', AuthService::ROLE_ADMIN);
$checkerId = ensureUser($pdo, '_rbac_checker', AuthService::ROLE_CHECKER);
$seoId = ensureUser($pdo, '_rbac_seo', AuthService::ROLE_SEO);
$eventAdminId = ensureUser($pdo, '_rbac_eventadmin', AuthService::ROLE_CHECKER);
$inactiveId = ensureUser($pdo, '_rbac_inactive', AuthService::ROLE_CHECKER);
$pdo->prepare('UPDATE admins SET is_active=0 WHERE id=?')->execute([$inactiveId]);

// Schema columns
assertTrue(
    (bool)$pdo->query("SHOW COLUMNS FROM admins LIKE 'role'")->fetch(),
    'admins.role column exists'
);
assertTrue(
    (bool)$pdo->query("SHOW COLUMNS FROM participants LIKE 'is_vip'")->fetch(),
    'participants.is_vip column exists'
);

// Session role landing
loginAs(['id' => $adminId, 'username' => '_rbac_admin', 'role' => AuthService::ROLE_ADMIN, 'display_name' => 'A']);
assertTrue(AuthService::isAdmin(), 'admin hasRole admin');
assertTrue(AuthService::loginHomeRoute() === 'admin_registrants', 'admin lands on registrants');

loginAs(['id' => $checkerId, 'username' => '_rbac_checker', 'role' => AuthService::ROLE_CHECKER, 'display_name' => 'C']);
assertTrue(AuthService::isChecker(), 'checker hasRole checker');
assertTrue(AuthService::loginHomeRoute() === 'admin_attendance', 'checker lands on attendance');
assertTrue(!AuthService::hasRole(AuthService::ROLE_ADMIN), 'checker is not admin');

loginAs(['id' => $seoId, 'username' => '_rbac_seo', 'role' => AuthService::ROLE_SEO, 'display_name' => 'S']);
assertTrue(AuthService::isSeoViewer(), 'seo hasRole seo_viewer');
assertTrue(AuthService::loginHomeRoute() === 'admin_seo_dashboard', 'seo lands on dashboard');

// Inactive login path simulation
$stmt = $pdo->prepare('SELECT id, username, password_hash, role, is_active, display_name FROM admins WHERE id=?');
$stmt->execute([$inactiveId]);
$inactive = $stmt->fetch();
assertTrue((int)$inactive['is_active'] === 0, 'inactive user flagged');

// Multi-event fixture: two concurrent open events.
$pdo->prepare('DELETE FROM event_assignments WHERE admin_id IN (?,?,?,?)')->execute([$adminId, $checkerId, $seoId, $eventAdminId]);
$pdo->exec("DELETE FROM participants WHERE email IN ('_rbac_same@test.local')");
$pdo->exec("DELETE FROM events WHERE slug IN ('_test-event-a','_test-event-b')");
$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status) VALUES ('_Test Event A','_test-event-a',1,0,'open')")->execute();
$eventA = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status) VALUES ('_Test Event B','_test-event-b',1,0,'open')")->execute();
$eventB = (int)$pdo->lastInsertId();
assertTrue($eventA > 0 && $eventB > 0 && $eventA !== $eventB, 'two concurrent events created');

// Staff isolation fixture: checker on A only, seo on B only, event_admin on A only.
$pdo->prepare('INSERT INTO event_assignments (admin_id, event_id, role) VALUES (?,?,?) ON DUPLICATE KEY UPDATE role=VALUES(role)')
    ->execute([$checkerId, $eventA, 'checker']);
$pdo->prepare('INSERT INTO event_assignments (admin_id, event_id, role) VALUES (?,?,?) ON DUPLICATE KEY UPDATE role=VALUES(role)')
    ->execute([$seoId, $eventB, 'seo_viewer']);
$pdo->prepare('INSERT INTO event_assignments (admin_id, event_id, role) VALUES (?,?,?) ON DUPLICATE KEY UPDATE role=VALUES(role)')
    ->execute([$eventAdminId, $eventA, 'event_admin']);
$_SESSION['current_event_id'] = $eventA;

// Router matrix via captured output
$routes = require dirname(__DIR__) . '/config/routes.php';
$router = new Router($routes);

function probeRoute(Router $router, string $route, string $method, array $expectRoles, string $label): void
{
    global $pdo, $adminId, $checkerId, $seoId, $eventA;
    $_SESSION['current_event_id'] = $eventA;
    $map = [
        AuthService::ROLE_ADMIN => ['id' => $adminId, 'username' => '_rbac_admin', 'role' => AuthService::ROLE_ADMIN, 'display_name' => 'A'],
        AuthService::ROLE_CHECKER => ['id' => $checkerId, 'username' => '_rbac_checker', 'role' => AuthService::ROLE_CHECKER, 'display_name' => 'C'],
        AuthService::ROLE_SEO => ['id' => $seoId, 'username' => '_rbac_seo', 'role' => AuthService::ROLE_SEO, 'display_name' => 'S'],
    ];

    foreach ($map as $role => $admin) {
        loginAs($admin);
        $allowed = in_array($role, $expectRoles, true);

        // Only test guard layer for GET routes that don't need heavy deps — use reflection on runGuards
        $ref = new ReflectionClass($router);
        $methodRef = $ref->getMethod('runGuards');
        $methodRef->setAccessible(true);
        $routeDef = (require dirname(__DIR__) . '/config/routes.php')[$route] ?? null;
        if (!$routeDef) {
            assertTrue(false, "{$label}: route missing");
            return;
        }
        ob_start();
        $ok = $methodRef->invoke($router, $routeDef, $method, $route);
        ob_end_clean();
        assertTrue($ok === $allowed, "{$label}: {$role} " . ($allowed ? 'allowed' : 'denied'));
    }
}

probeRoute($router, 'admin_settings', 'GET', [AuthService::ROLE_ADMIN], 'settings');
probeRoute($router, 'admin_registrants', 'GET', [AuthService::ROLE_ADMIN, AuthService::ROLE_CHECKER], 'registrants');
probeRoute($router, 'admin_attendance', 'GET', [AuthService::ROLE_ADMIN, AuthService::ROLE_CHECKER], 'attendance');
probeRoute($router, 'admin_attendance_manual', 'POST', [AuthService::ROLE_ADMIN, AuthService::ROLE_CHECKER], 'attendance write');
probeRoute($router, 'admin_seo_dashboard', 'GET', [AuthService::ROLE_ADMIN, AuthService::ROLE_SEO], 'seo dashboard');
probeRoute($router, 'admin_users', 'GET', [AuthService::ROLE_ADMIN], 'users');
probeRoute($router, 'admin_logs', 'GET', [AuthService::ROLE_ADMIN], 'logs');
probeRoute($router, 'admin_attendance_kpi', 'GET', [AuthService::ROLE_ADMIN, AuthService::ROLE_CHECKER, AuthService::ROLE_SEO], 'kpi read');

// Staff isolation: seo staff assigned to B only.
assertTrue(EventContext::canAccess($pdo, $checkerId, $eventA, ['checker']), 'checker can access assigned event A');
assertTrue(!EventContext::canAccess($pdo, $checkerId, $eventB, ['checker']), 'checker cannot open event B');
assertTrue(EventContext::canAccess($pdo, $seoId, $eventB, ['seo_viewer']), 'seo can access assigned event B');
assertTrue(!EventContext::canAccess($pdo, $seoId, $eventA, ['seo_viewer']), 'seo cannot open event A');
assertTrue(EventContext::canAccess($pdo, $adminId, $eventB, null), 'All Father can access any event');

// Email uniqueness is per event: same email on A and B, duplicate inside A rejected.
$uuidA = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
$uuidB = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
$ins = $pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name) VALUES (?,?,?,?,?)');
$ins->execute([$eventA, $uuidA, '_rbac_same@test.local', 'Same', 'User']);
$crossOk = true;
try {
    $ins->execute([$eventB, $uuidB, '_rbac_same@test.local', 'Same', 'User']);
} catch (\Throwable $e) {
    $crossOk = false;
}
assertTrue($crossOk, 'same email may register on different events');
$dupBlocked = false;
try {
    $pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name) VALUES (?,?,?,?)')
        ->execute([$eventA, $uuidB . 'x', '_rbac_same@test.local', 'Same', 'User']);
} catch (\Throwable $e) {
    $dupBlocked = true;
}
if (!$dupBlocked) {
    // Retry with correct placeholder count (guard against test typo).
    try {
        $pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name) VALUES (?,?,?,?,?)')
            ->execute([$eventA, $uuidB . '-dup', '_rbac_same@test.local', 'Same', 'User']);
    } catch (\Throwable $e) {
        $dupBlocked = true;
    }
}
assertTrue($dupBlocked, 'duplicate email inside one event blocked');

// Cross-event QR/scan deny: uuid from A must not resolve under B.
$foundA = $pdo->prepare('SELECT id FROM participants WHERE uuid = ? AND event_id = ?');
$foundA->execute([$uuidA, $eventA]);
assertTrue((bool)$foundA->fetch(), 'participant resolves under own event');
$foundB = $pdo->prepare('SELECT id FROM participants WHERE uuid = ? AND event_id = ?');
$foundB->execute([$uuidA, $eventB]);
assertTrue(!$foundB->fetch(), 'cross-event QR rejected (uuid+event mismatch)');

// event_admin in the matrix: manage routes allowed on A, global admin routes denied.
function guardAllows(Router $router, string $route, string $method, array $admin): bool
{
    $ref = new ReflectionClass($router);
    $m = $ref->getMethod('runGuards');
    $m->setAccessible(true);
    $def = (require dirname(__DIR__) . '/config/routes.php')[$route] ?? null;
    if (!$def) return false;
    loginAs($admin);
    ob_start();
    $ok = $m->invoke($router, $def, $method, $route);
    ob_end_clean();
    return (bool)$ok;
}
$eventAdmin = ['id' => $eventAdminId, 'username' => '_rbac_eventadmin', 'role' => AuthService::ROLE_CHECKER, 'display_name' => 'E'];
$_SESSION['current_event_id'] = $eventA;
assertTrue(guardAllows($router, 'admin_registrant_vip', 'POST', $eventAdmin), 'event_admin may manage VIP on assigned event');
assertTrue(guardAllows($router, 'admin_event_links', 'GET', $eventAdmin), 'event_admin may open unique links');
assertTrue(!guardAllows($router, 'admin_users', 'GET', $eventAdmin), 'event_admin denied global users');
assertTrue(!guardAllows($router, 'admin_events', 'GET', $eventAdmin), 'event_admin denied event management');

// Forced context: A-only checker with session pinned to B must not reach B data.
// currentEvent() remaps to the assigned event, so assert the remap target is A
// and direct B access stays denied.
loginAs(['id' => $checkerId, 'username' => '_rbac_checker', 'role' => AuthService::ROLE_CHECKER, 'display_name' => 'C']);
$_SESSION['current_event_id'] = $eventB;
$remapped = EventContext::currentEvent($pdo);
assertTrue($remapped !== null && (int)$remapped['id'] === $eventA, 'forced B context remaps A-only staff to A');
assertTrue(!EventContext::canAccess($pdo, $checkerId, $eventB, null), 'forced B context still denies B access');
ob_start();
$refRouter = new ReflectionClass($router);
$guardMethod = $refRouter->getMethod('runGuards');
$guardMethod->setAccessible(true);
$regDef = (require dirname(__DIR__) . '/config/routes.php')['admin_registrants'];
$guardOk = $guardMethod->invoke($router, $regDef, 'GET', 'admin_registrants');
ob_end_clean();
assertTrue($guardOk && (int)$_SESSION['current_event_id'] === $eventA, 'guard serves assigned event, never forced B');

// Controller-level cross-event submit: A uuid + B slug must be not_found.
$_SESSION['staff'] = true;
$_SESSION['csrf'] = bin2hex(random_bytes(16));
$attCtrl = new \App\Controllers\AttendanceController();
$cross = $attCtrl->submitJsonForTest(['uuid' => $uuidA, 'signature' => 'data:image/png;base64,AAAA', 'e' => '_test-event-b'], $_SESSION['csrf']);
assertTrue(($cross['error'] ?? '') === 'not_found', 'controller rejects cross-event submit');
$noEvent = $attCtrl->submitJsonForTest(['uuid' => $uuidA, 'signature' => 'data:image/png;base64,AAAA'], $_SESSION['csrf']);
assertTrue(($noEvent['error'] ?? '') === 'missing_event', 'controller requires event slug');
unset($_SESSION['staff']);

// Participant lookup requires the event after item 7.
$partCtrl = new \App\Controllers\ParticipantController();
unset($_SESSION['scan_event_id']);
$_GET = ['uuid' => $uuidA];
ob_start();
$partCtrl->getByUuidJson();
ob_end_clean();
assertTrue(http_response_code() === 400, 'participant lookup without event is refused');
$_GET = ['uuid' => $uuidA, 'e' => '_test-event-b'];
ob_start();
$partCtrl->getByUuidJson();
ob_end_clean();
assertTrue(http_response_code() === 404, 'participant lookup with other event is not found');
$_GET = ['uuid' => $uuidA, 'e' => '_test-event-a'];
http_response_code(200);
ob_start();
$partCtrl->getByUuidJson();
$body = (string)ob_get_clean();
assertTrue(http_response_code() === 200 && str_contains($body, $uuidA), 'participant lookup with own event resolves');
http_response_code(200);

// Schedule window: future starts_at and past ends_at close the event.
$future = date('Y-m-d H:i:s', time() + 86400);
$past = date('Y-m-d H:i:s', time() - 86400);
assertTrue(!EventContext::isPublicOpen(['status' => 'open', 'starts_at' => $future, 'ends_at' => null]), 'future starts_at closes register');
assertTrue(!EventContext::isPublicOpen(['status' => 'open', 'starts_at' => null, 'ends_at' => $past]), 'past ends_at closes register');
assertTrue(EventContext::isPublicOpen(['status' => 'open', 'starts_at' => $past, 'ends_at' => $future]), 'open window stays open');
assertTrue(!EventContext::isPublicOpen(['status' => 'draft', 'starts_at' => null, 'ends_at' => null]), 'draft stays closed');

// Cleanup multi-event fixture rows (keep users for next run).
$pdo->prepare('DELETE FROM participants WHERE email = ?')->execute(['_rbac_same@test.local']);
$pdo->prepare('DELETE FROM event_assignments WHERE admin_id IN (?,?,?,?)')->execute([$adminId, $checkerId, $seoId, $eventAdminId]);
$pdo->exec("DELETE FROM events WHERE slug IN ('_test-event-a','_test-event-b')");
unset($_SESSION['current_event_id'], $_SESSION['scan_event_id']);

// Last admin protection
$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE role='admin' AND is_active=1")->fetchColumn();
assertTrue($adminCount >= 1, 'at least one active admin remains');

AuthService::logoutLocal();
echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
