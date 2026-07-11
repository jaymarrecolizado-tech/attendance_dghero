<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;

class AuthController
{
    public function loginForm(): void
    {
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_login.php';
    }

    public function login(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ok = \App\Services\RateLimiter::allow('login:'.$ip, 5, 60);
        if (!$ok) { http_response_code(429); echo 'Too Many Attempts'; return; }
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check($_POST['csrf'])) {
            http_response_code(400);
            echo 'Invalid CSRF';
            return;
        }
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($username === '' || $password === '') { http_response_code(422); echo 'Missing'; return; }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        if (!$admin || !password_verify($password, $admin['password_hash'])) { \App\Services\Logger::log(null, 'login_failed', ['username'=>$username]); http_response_code(401); echo 'Invalid credentials'; return; }
        \App\Services\Logger::log((int)$admin['id'], 'login_success', []);
        $_SESSION['admin_id'] = (int)$admin['id'];
        header('Location: ?r=admin_registrants');
    }

    public function logout(): void
    {
        unset($_SESSION['admin_id']);
        header('Location: ?r=admin_login');
    }
}
