 <?php
   require __DIR__ . '/config/bootstrap.php';
   require __DIR__ . '/vendor/autoload.php';
   $pdo = \App\Services\Database::pdo();
   $username = 'admin';
   $password = password_hash('admin123!@#', PASSWORD_DEFAULT);
   $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
   $stmt->execute([$username, $password]);
   echo "Admin created!";