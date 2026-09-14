<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../src/Controllers/RegisterController.php';
require __DIR__ . '/../src/Services/Database.php';
require __DIR__ . '/../src/Services/EventContext.php';
require __DIR__ . '/../src/Services/Uuid.php';
require __DIR__ . '/../src/Services/QrService.php';
require __DIR__ . '/../src/Services/Mailer.php';
require __DIR__ . '/../src/Services/ParticipantValidator.php';
require __DIR__ . '/../src/Services/RateLimiter.php';
require __DIR__ . '/../src/Services/Logger.php';

$_SESSION['csrf'] = bin2hex(random_bytes(32));
$pdo = \App\Services\Database::pdo();
$slug = $pdo->query("SELECT slug FROM events WHERE status='open' ORDER BY id DESC LIMIT 1")->fetch()['slug'] ?? null;
if (!$slug) {
    $pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status) VALUES ('Test Event','test-event',1,0,'open')")->execute();
    $slug = 'test-event';
}
$_GET['e'] = $slug;
$_POST = [
  'csrf' => $_SESSION['csrf'],
  'e' => $slug,
  'first_name' => 'Alice',
  'middle_name' => 'B',
  'last_name' => 'Tester',
  'email' => 'alice@example.com',
  'agency' => 'Agency X',
  'sector' => 'Sector Y',
  'nickname' => 'Al',
  'sex' => 'Female',
  'designation' => 'Analyst',
  'office_email' => 'alice.office@example.com',
  'contact_no' => '1234567890',
];

$controller = new \App\Controllers\RegisterController();
$controller->submit();