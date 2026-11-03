<?php
declare(strict_types=1);

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

use App\Controllers\AdminSignatureController;
use App\Controllers\AdminSignatureSheetController;
use App\Core\Router;
use App\Services\AuthService;
use App\Services\Database;
use App\Services\EventContext;
use App\Services\SignatureSheetTable;
use App\Services\Uuid;

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

$days = AdminSignatureSheetController::eventDays([
    'starts_at' => '2026-10-27 00:00:00',
    'ends_at' => '2026-10-29 23:59:59',
]);
assertTrue($days === ['2026-10-27', '2026-10-28', '2026-10-29'], 'event window is three calendar days');
assertTrue(str_contains(AdminSignatureSheetController::spanLabel($days), 'October'), 'span label names the month');

$png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=';

$pdo->exec("DELETE FROM events WHERE slug IN ('_sig-sheet-a','_sig-sheet-b')");
$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status, starts_at, ends_at) VALUES ('_Sig Sheet A','_sig-sheet-a',1,0,'open','2026-10-27 00:00:00','2026-10-29 23:59:59')")->execute();
$eventA = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status, starts_at, ends_at) VALUES ('_Sig Sheet B','_sig-sheet-b',1,0,'open','2026-10-27 00:00:00','2026-10-29 23:59:59')")->execute();
$eventB = (int)$pdo->lastInsertId();

$hash = password_hash('TestPass123!', PASSWORD_BCRYPT);
$pdo->prepare("DELETE FROM admins WHERE username = '_sig_sheet_checker'")->execute();
$pdo->prepare('INSERT INTO admins (username, display_name, password_hash, email, role, is_active) VALUES (?,?,?,?,?,1)')
    ->execute(['_sig_sheet_checker', 'Sheet Checker', $hash, null, AuthService::ROLE_CHECKER]);
$checkerId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO event_assignments (admin_id, event_id, role) VALUES (?,?,?)')->execute([$checkerId, $eventA, 'checker']);

$uuid = Uuid::v4();
$pdo->prepare('INSERT INTO participants (event_id, uuid, first_name, last_name) VALUES (?,?,?,?)')
    ->execute([$eventA, $uuid, 'Sheet', 'Guest']);
$pid = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO attendance (participant_id, attendance_date, time_in, signature_path, event_id, status) VALUES (?,?,?,?,?,'absent')")
    ->execute([$pid, '2026-10-27', '00:00:00', null, $eventA]);

AuthService::establishSession([
    'id' => $checkerId,
    'username' => '_sig_sheet_checker',
    'role' => AuthService::ROLE_CHECKER,
    'display_name' => 'Sheet Checker',
]);
$_SESSION['current_event_id'] = $eventA;
$_SESSION['csrf'] = bin2hex(random_bytes(16));

assertTrue(EventContext::canAccess($pdo, $checkerId, $eventA, ['event_admin', 'checker']), 'checker can access assigned event sheet');
assertTrue(!EventContext::canAccess($pdo, $checkerId, $eventB, ['event_admin', 'checker']), 'checker cannot open other event sheet');

$routes = require dirname(__DIR__) . '/config/routes.php';
$router = new Router($routes);
$ref = new ReflectionClass($router);
$runGuards = $ref->getMethod('runGuards');
$runGuards->setAccessible(true);
ob_start();
$okA = $runGuards->invoke($router, $routes['admin_signature_sheet'], 'GET', 'admin_signature_sheet');
ob_end_clean();
assertTrue($okA === true, 'checker guard allows signature sheet on assigned event');
$_SESSION['current_event_id'] = $eventB;
ob_start();
$okB = $runGuards->invoke($router, $routes['admin_signature_sheet'], 'GET', 'admin_signature_sheet');
ob_end_clean();
assertTrue($okB === false, 'checker guard denies signature sheet on other event');
$_SESSION['current_event_id'] = $eventA;

$ctrl = new AdminSignatureController();
$day2 = $ctrl->addNewJsonForTest(['uuid' => $uuid, 'date' => '2026-10-28', 'signature' => $png], $_SESSION['csrf']);
assertTrue(($day2['ok'] ?? false) === true, 'signing day 2 succeeds');

$d1 = $pdo->prepare('SELECT status, signature_path FROM attendance WHERE participant_id=? AND attendance_date=? AND event_id=?');
$d1->execute([$pid, '2026-10-27', $eventA]);
$row1 = $d1->fetch();
assertTrue($row1 && (string)$row1['status'] === 'absent', 'day 1 stays absent until signed');

$d2 = $pdo->prepare('SELECT status, signature_path FROM attendance WHERE participant_id=? AND attendance_date=? AND event_id=?');
$d2->execute([$pid, '2026-10-28', $eventA]);
$row2 = $d2->fetch();
assertTrue($row2 && (string)$row2['status'] === 'present' && trim((string)$row2['signature_path']) !== '', 'day 2 is present with ink');

$d3 = $pdo->prepare('SELECT id, status, signature_path FROM attendance WHERE participant_id=? AND attendance_date=? AND event_id=?');
$d3->execute([$pid, '2026-10-29', $eventA]);
assertTrue($d3->fetch() === false, 'day 3 has no attendance row');

$promote = $ctrl->addNewJsonForTest(['uuid' => $uuid, 'date' => '2026-10-27', 'signature' => $png], $_SESSION['csrf']);
assertTrue(($promote['ok'] ?? false) === true, 'signing an absent day promotes to present');
$d1->execute([$pid, '2026-10-27', $eventA]);
$row1b = $d1->fetch();
assertTrue($row1b && (string)$row1b['status'] === 'present', 'day 1 is present after signature');

$again = $ctrl->addNewJsonForTest(['uuid' => $uuid, 'date' => '2026-10-28', 'signature' => $png], $_SESSION['csrf']);
assertTrue(($again['error'] ?? '') === 'already_marked', 'second ink on the same day is blocked');

$days = ['2026-10-27', '2026-10-28', '2026-10-29'];
$repl = AdminSignatureSheetController::applyReplicate($pdo, $eventA, $pid, $uuid, $days);
assertTrue($repl['copied'] === 1 && $repl['error'] === '', 'replicate fills the remaining empty day');
$d3->execute([$pid, '2026-10-29', $eventA]);
$row3 = $d3->fetch();
assertTrue($row3 && (string)$row3['status'] === 'present' && trim((string)($row3['signature_path'] ?? '')) !== '', 'day 3 present after replicate');
$replAgain = AdminSignatureSheetController::applyReplicate($pdo, $eventA, $pid, $uuid, $days);
assertTrue($replAgain['copied'] === 0 && $replAgain['error'] === 'nothing_to_copy', 'replicate is a no-op when every day has ink');

$uuidPartial = Uuid::v4();
$pdo->prepare('INSERT INTO participants (event_id, uuid, first_name, last_name) VALUES (?,?,?,?)')
    ->execute([$eventA, $uuidPartial, 'Partial', 'Ink']);
$pidPartial = (int)$pdo->lastInsertId();
$partialNew = $ctrl->addNewJsonForTest(['uuid' => $uuidPartial, 'date' => '2026-10-28', 'signature' => $png], $_SESSION['csrf']);
assertTrue(($partialNew['ok'] ?? false) === true, 'partial person can sign one day');
$bulk = AdminSignatureSheetController::applyReplicateMissing($pdo, $eventA, $days);
assertTrue($bulk['people'] === 1 && $bulk['copied'] === 2, 'bulk replicate fills the other two days for the partial person');
$d3p = $pdo->prepare('SELECT status, signature_path FROM attendance WHERE participant_id=? AND attendance_date=? AND event_id=?');
$d3p->execute([$pidPartial, '2026-10-27', $eventA]);
$rowP = $d3p->fetch();
assertTrue($rowP && (string)$rowP['status'] === 'present' && trim((string)($rowP['signature_path'] ?? '')) !== '', 'partial person has ink on an empty day after bulk');
$bulkAgain = AdminSignatureSheetController::applyReplicateMissing($pdo, $eventA, $days);
assertTrue($bulkAgain['people'] === 0 && $bulkAgain['copied'] === 0, 'bulk replicate is a no-op when nobody is missing ink');

$uuidZero = Uuid::v4();
$pdo->prepare('INSERT INTO participants (event_id, uuid, first_name, last_name) VALUES (?,?,?,?)')
    ->execute([$eventA, $uuidZero, 'Zero', 'Ink']);
for ($i = 0; $i < 9; $i++) {
    $pdo->prepare('INSERT INTO participants (event_id, uuid, first_name, last_name) VALUES (?,?,?,?)')
        ->execute([$eventA, Uuid::v4(), 'Pad' . $i, 'Person']);
}
$eventRow = ['id' => $eventA, 'starts_at' => '2026-10-27 00:00:00', 'ends_at' => '2026-10-29 23:59:59'];
$page1 = SignatureSheetTable::fromRequest($pdo, $eventRow, $days, ['per' => '10', 'sort' => 'name', 'dir' => 'asc', 'page' => '1']);
assertTrue((int)$page1['pager']['total'] === 12, 'sheet total counts all people');
assertTrue((int)$page1['pager']['pages'] === 2, 'per=10 yields two pages');
assertTrue(count($page1['rows']) === 10 && ($page1['rows'][0]['last_name'] ?? '') === 'Guest', 'name sort page 1 starts with Guest');
$page2 = SignatureSheetTable::fromRequest($pdo, $eventRow, $days, ['per' => '10', 'sort' => 'name', 'dir' => 'asc', 'page' => '2']);
assertTrue(count($page2['rows']) === 2, 'name sort page 2 has the remainder');
$none = SignatureSheetTable::fromRequest($pdo, $eventRow, $days, ['signed' => '0']);
assertTrue((int)$none['pager']['total'] === 10, 'signed=0 returns people with no ink');
$full = SignatureSheetTable::fromRequest($pdo, $eventRow, $days, ['signed' => 'complete']);
assertTrue((int)$full['pager']['total'] === 2 && (int)$full['rows'][0]['sig_count'] === 3, 'complete filter is 3/3');
$named = SignatureSheetTable::fromRequest($pdo, $eventRow, $days, ['q' => 'Zero']);
assertTrue(count($named['rows']) === 1 && ($named['rows'][0]['first_name'] ?? '') === 'Zero', 'name search matches Zero');

$pdo->prepare('DELETE FROM attendance WHERE event_id IN (?,?)')->execute([$eventA, $eventB]);
$pdo->prepare('DELETE FROM participants WHERE event_id IN (?,?)')->execute([$eventA, $eventB]);
$pdo->prepare('DELETE FROM event_assignments WHERE admin_id = ?')->execute([$checkerId]);
$pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$checkerId]);
$pdo->exec("DELETE FROM events WHERE slug IN ('_sig-sheet-a','_sig-sheet-b')");
AuthService::logoutLocal();

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
