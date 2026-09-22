<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../src/Services/Database.php';

$pdo = \App\Services\Database::pdo();
$username = $argv[1] ?? 'admin';
$password = $argv[2] ?? 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO admins (username, password_hash, email, role, is_active) VALUES (?,?,?,?,1) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role), is_active = 1');
$stmt->execute([$username, $hash, null, 'admin']);
echo 'seeded';