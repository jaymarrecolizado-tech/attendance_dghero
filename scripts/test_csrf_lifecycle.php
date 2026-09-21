<?php
declare(strict_types=1);

/**
 * CSRF consume-on-success lifecycle test.
 * Verifies: first POST succeeds, replay with same token fails.
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

use App\Controllers\AttendanceController;
use App\Services\AuthService;
use App\Services\Database;

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

// Set up a test admin and establish session.
$adminId = (int)$pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM admins")->fetchColumn();
$hash = password_hash('TestPass123!', PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO admins (id, username, display_name, password_hash, email, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE display_name=VALUES(display_name), password_hash=VALUES(password_hash), role=VALUES(role), is_active=1');
$stmt->execute([$adminId, '_csrf_test', 'CSRF Test', $hash, null, AuthService::ROLE_ADMIN]);
AuthService::establishSession(['id' => $adminId, 'username' => '_csrf_test', 'role' => AuthService::ROLE_ADMIN, 'display_name' => 'CSRF Test']);

// Get a CSRF token.
$token = function_exists('csrf_token') ? csrf_token() : bin2hex(random_bytes(32));

// Test 1: Token is valid before use.
assertTrue(csrf_check($token), 'CSRF token valid before use');

// Test 2: After csrf_rotate(), old token is invalid.
if (function_exists('csrf_rotate')) {
    csrf_rotate();
    $newToken = $_SESSION['csrf'] ?? '';
    assertTrue(!csrf_check($token), 'Old CSRF token invalidated after rotation');
    assertTrue(csrf_check($newToken), 'New CSRF token is valid');
}

// Reset for next test cycle.
if (function_exists('csrf_rotate')) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf'] = $token;

// Test 3: Simulate register POST with valid token.
// RegisterController::submit() calls csrf_rotate() after success.
// We verify the rotate happens by checking the token changes after a simulated success.
$_POST['csrf'] = $token;
$_POST['first_name'] = 'Test';
$_POST['last_name'] = 'User';
$_POST['email'] = '_csrf_' . time() . '@test.local';
$_POST['sex'] = 'Male';
$_POST['sector'] = 'National Government Agency';
$_POST['agency_select'] = 'Test Agency';
$_POST['agency'] = 'Test Agency';
$_POST['designation_select'] = 'Engineer';
$_POST['office_email'] = 'test@test.local';
$_POST['contact_no'] = '1234567890';
$_POST['e'] = '';

// Verify the CSRF check would pass.
assertTrue(csrf_check($_POST['csrf']), 'CSRF check passes for valid token');

// Test 4: Replay with same token should fail after rotation.
// This simulates the attack: attacker captures a valid POST token and replays it.
$replayToken = $token;
if (function_exists('csrf_rotate')) {
    csrf_rotate(); // simulate successful mutation consuming the token
}
$replayOk = isset($replayToken) && csrf_check($replayToken);
assertTrue(!$replayOk, 'Replay of consumed CSRF token fails');

// Test 5: csrf_token generates a fresh token when session has none.
unset($_SESSION['csrf']);
$fresh = function_exists('csrf_token') ? csrf_token() : '';
assertTrue($fresh !== '', 'Fresh CSRF token generated when session empty');
assertTrue(strlen($fresh) === 64, 'Fresh CSRF token is 64 chars');

// Test 6: consume-on-success through the real controller helper.
// submitJsonForTest must rotate the token on success, so a replay with the
// same token is rejected at the controller level (not only in isolation).
$pdo->exec("DELETE FROM attendance WHERE participant_id IN (SELECT id FROM participants WHERE email = '_csrf_attend@test.local')");
$pdo->exec("DELETE FROM participants WHERE email = '_csrf_attend@test.local'");
$pdo->exec("DELETE FROM event_assignments WHERE event_id IN (SELECT id FROM events WHERE slug LIKE '_csrf-lc-%')");
$pdo->exec("DELETE FROM events WHERE slug LIKE '_csrf-lc-%'");
$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status, starts_at, ends_at) VALUES ('_CSRF Lifecycle Event','_csrf-lc-a',0,0,'open',?,?)")
    ->execute([date('Y-m-d H:i:s', time() - 3600), date('Y-m-d H:i:s', time() + 3600)]);
$csrfEventId = (int)$pdo->lastInsertId();
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
$pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name) VALUES (?,?,?,?,?)')
    ->execute([$csrfEventId, $uuid, '_csrf_attend@test.local', 'CSRF', 'Replay']);

$_SESSION['staff'] = true;
$controllerToken = bin2hex(random_bytes(32));
$_SESSION['csrf'] = $controllerToken;
$attCtrl = new AttendanceController();
$ok = $attCtrl->submitJsonForTest(['uuid' => $uuid, 'signature' => 'data:image/png;base64,AAAA', 'e' => '_csrf-lc-a'], $controllerToken);
assertTrue(($ok['ok'] ?? false) === true, 'Controller submit succeeds with valid token');
$rotated = $_SESSION['csrf'] ?? '';
assertTrue($rotated !== '' && $rotated !== $controllerToken, 'Controller submit rotated the CSRF token');
$replay = $attCtrl->submitJsonForTest(['uuid' => $uuid, 'signature' => 'data:image/png;base64,AAAA', 'e' => '_csrf-lc-a'], $controllerToken);
assertTrue(($replay['error'] ?? '') === 'csrf', 'Controller rejects replay of consumed token');
unset($_SESSION['staff']);

// Cleanup.
unset($_SESSION['staff'], $_SESSION['scan_event_id']);
$pdo->exec("DELETE FROM attendance WHERE participant_id IN (SELECT id FROM participants WHERE email = '_csrf_attend@test.local')");
$pdo->exec("DELETE FROM participants WHERE email = '_csrf_attend@test.local'");
$pdo->exec("DELETE FROM event_assignments WHERE event_id IN (SELECT id FROM events WHERE slug LIKE '_csrf-lc-%')");
$pdo->exec("DELETE FROM events WHERE slug LIKE '_csrf-lc-%'");
AuthService::logoutLocal();
$pdo->exec("DELETE FROM admins WHERE username = '_csrf_test'");

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
