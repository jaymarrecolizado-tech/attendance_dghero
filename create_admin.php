<?php
if (PHP_SAPI !== 'cli') {
    $isLocal = ($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1' || ($_SERVER['REMOTE_ADDR'] ?? '') === '::1';
    $appDebug = getenv('APP_DEBUG') === 'true';
    if (!$isLocal || !$appDebug) {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
}
    require __DIR__ . '/config/bootstrap.php';
    require __DIR__ . '/vendor/autoload.php';
    $pdo = \App\Services\Database::pdo();
    $username = 'admin';
    $password = password_hash('admin123!@#', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
    $stmt->execute([$username, $password]);
    echo "Admin created!";