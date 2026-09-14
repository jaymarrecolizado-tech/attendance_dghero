<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../src/Services/Database.php';
require __DIR__ . '/../src/Services/EventContext.php';
require __DIR__ . '/../src/Services/AuthService.php';
require __DIR__ . '/../src/Services/Uuid.php';
require __DIR__ . '/../src/Services/SignatureService.php';
require __DIR__ . '/../src/Services/RateLimiter.php';
require __DIR__ . '/../src/Controllers/AttendanceController.php';

$_SESSION['staff'] = true;
$_SESSION['csrf'] = bin2hex(random_bytes(32));
$pdo = \App\Services\Database::pdo();
$pdo->exec("DELETE FROM events WHERE slug='qa-event-rule'");
$pdo->prepare("INSERT INTO events (name,slug,enforce_single_time_in,active,status) VALUES ('QA Event','qa-event-rule',1,0,'open')")->execute();
$eventId = (int)$pdo->lastInsertId();

$uuid = \App\Services\Uuid::v4();
$pdo->prepare('INSERT INTO participants (event_id, uuid,first_name,last_name) VALUES (?,?,?,?)')->execute([$eventId, $uuid,'QA','Tester']);
$_SESSION['scan_event_id'] = $eventId;
$png = 'data:image/png;base64,' . base64_encode(random_bytes(128));
$ctrl = new \App\Controllers\AttendanceController();
$r1 = $ctrl->submitJsonForTest(['uuid'=>$uuid,'signature'=>$png,'e'=>'qa-event-rule'], $_SESSION['csrf']);
$r2 = $ctrl->submitJsonForTest(['uuid'=>$uuid,'signature'=>$png,'e'=>'qa-event-rule'], $_SESSION['csrf']);
echo ($r1['ok']??false) ? "first_ok\n" : "first_fail\n";
echo (isset($r2['error']) && $r2['error']==='already_marked') ? "second_blocked\n" : "second_fail\n";
$pdo->prepare('DELETE FROM attendance WHERE participant_id IN (SELECT id FROM participants WHERE uuid=?)')->execute([$uuid]);
$pdo->prepare('DELETE FROM participants WHERE uuid=?')->execute([$uuid]);
$pdo->exec("DELETE FROM events WHERE slug='qa-event-rule'");
unset($_SESSION['scan_event_id']);