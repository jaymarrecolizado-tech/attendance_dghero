// <?php
// declare(strict_types=1);

// require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

// if (empty($_SESSION['admin_id'])) { http_response_code(403); echo 'Forbidden'; exit; }

// // Autoload App classes
// spl_autoload_register(function($class){
//     $prefix = 'App\\';
//     $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
//     if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
//     $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
//     $file = $base . $rel . '.php';
//     if (is_file($file)) require $file;
// });
// // Vendor autoload if available
// if (is_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
//     require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
// }

// $aid = isset($_GET['aid']) ? (int)$_GET['aid'] : 0;
// if ($aid <= 0) { http_response_code(400); echo 'Bad Request'; exit; }

// $pdo = \App\Services\Database::pdo();
// $stmt = $pdo->prepare('SELECT signature_path FROM attendance WHERE id = ?');
// $stmt->execute([$aid]);
// $row = $stmt->fetch();
// if (!$row) { http_response_code(404); echo 'Not Found'; exit; }
// $path = (string)$row['signature_path'];
// if (!is_file($path)) { http_response_code(404); echo 'Not Found'; exit; }
// if (class_exists('App\\Services\\Logger')) { \App\Services\Logger::log((int)$_SESSION['admin_id'], 'signature_view', ['attendance_id'=>$aid]); }

// header('Content-Type: image/png');
// header('Content-Disposition: inline');
// readfile($path);



<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Autoload App classes
spl_autoload_register(function($class){
    $prefix = 'App\\';
    $base = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = $base . $rel . '.php';
    if (is_file($file)) require $file;
});
// Vendor autoload if available
if (is_file(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}

$aid = isset($_GET['aid']) ? (int)$_GET['aid'] : 0;
if ($aid <= 0) { http_response_code(400); echo 'Bad Request'; exit; }

$pdo = \App\Services\Database::pdo();
$stmt = $pdo->prepare('SELECT signature_path FROM attendance WHERE id = ?');
$stmt->execute([$aid]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); echo 'Not Found'; exit; }
$signaturePath = (string)$row['signature_path'];
// Build full path from root directory (signature_path is relative like "2025/uuid.png")
$path = __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'signatures' . DIRECTORY_SEPARATOR . $signaturePath;
if (!is_file($path)) { http_response_code(404); echo 'Not Found'; exit; }
if (class_exists('App\\Services\\Logger') && !empty($_SESSION['admin_id'])) { \App\Services\Logger::log((int)$_SESSION['admin_id'], 'signature_view', ['attendance_id'=>$aid]); }

header('Content-Type: image/png');
header('Content-Disposition: inline');
readfile($path);