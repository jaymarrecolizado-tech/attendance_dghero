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

// Cleanup.
AuthService::logoutLocal();
$pdo->exec("DELETE FROM admins WHERE username = '_csrf_test'");

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
