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

use App\Services\Database;
use App\Services\ParticipantValidator;
use App\Services\Uuid;

$failed = 0;
$passed = 0;
function assertTrue(bool $cond, string $msg): void
{
    global $failed, $passed;
    echo ($cond ? 'PASS  ' : 'FAIL  ') . $msg . PHP_EOL;
    if ($cond) { $passed++; } else { $failed++; }
}

$base = [
    'first_name' => 'Ana',
    'last_name' => 'Reyes',
    'email' => 'ana.reyes@example.com',
    'agency' => 'DICT Region 2',
    'sector' => 'National Government Agency',
];

$missing = ParticipantValidator::validateForRegistration(array_merge($base, ['email' => '']));
assertTrue(isset($missing['errors']['email']), 'blank email is rejected');
assertTrue(
    str_contains($missing['errors']['email'] ?? '', 'Certificate of Appearance'),
    'blank email names Certificate of Appearance'
);

$bad = ParticipantValidator::validateForRegistration(array_merge($base, ['email' => 'not-an-email']));
assertTrue(isset($bad['errors']['email']), 'invalid email format is rejected');

$noAgency = ParticipantValidator::validateForRegistration(array_merge($base, ['agency' => '']));
assertTrue(isset($noAgency['errors']['agency']), 'blank agency is rejected');

$ok = ParticipantValidator::validateForRegistration(array_merge($base, ['email' => 'Ana.Reyes@Example.COM']));
assertTrue($ok['errors'] === [], 'complete payload passes');
assertTrue($ok['data']['email'] === 'ana.reyes@example.com', 'email is stored lowercase');

Database::migrate();
$pdo = Database::pdo();
$pdo->exec("DELETE FROM participants WHERE email LIKE '_regval_%@test.local'");
$pdo->exec("DELETE FROM events WHERE slug LIKE '_regval-%'");

$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status) VALUES ('_RegVal A','_regval-a',0,0,'open')")->execute();
$eventA = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status) VALUES ('_RegVal B','_regval-b',0,0,'open')")->execute();
$eventB = (int)$pdo->lastInsertId();

$ins = $pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name, agency) VALUES (?,?,?,?,?,?)');
$ins->execute([$eventA, Uuid::v4(), '_regval_one@test.local', 'One', 'User', 'DICT']);

assertTrue(ParticipantValidator::emailTaken($pdo, $eventA, '_regval_one@test.local'), 'same email is taken on the same event');
assertTrue(ParticipantValidator::emailTaken($pdo, $eventA, '_REGVAL_ONE@test.local'), 'same email with different case is taken');
assertTrue(!ParticipantValidator::emailTaken($pdo, $eventB, '_regval_one@test.local'), 'same email may be used on another event');
assertTrue(!ParticipantValidator::emailTaken($pdo, $eventA, '_regval_two@test.local'), 'unused email is free');

$pdo->exec("DELETE FROM participants WHERE email LIKE '_regval_%@test.local'");
$pdo->exec("DELETE FROM events WHERE slug LIKE '_regval-%'");

echo $failed === 0 ? "OK {$passed} passed\n" : "FAILED {$failed}\n";
exit($failed === 0 ? 0 : 1);
