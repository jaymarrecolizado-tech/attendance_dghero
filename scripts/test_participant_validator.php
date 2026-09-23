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
    'sex' => 'Female',
    'contact_no' => '09171234567',
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

$noSex = ParticipantValidator::validateForRegistration(array_merge($base, ['sex' => '']));
assertTrue(isset($noSex['errors']['sex']), 'blank sex is rejected');

$badSex = ParticipantValidator::validateForRegistration(array_merge($base, ['sex' => 'Alien']));
assertTrue(isset($badSex['errors']['sex']), 'sex outside Female/Male/Other is rejected');

$otherSex = ParticipantValidator::validateForRegistration(array_merge($base, ['sex' => 'Other']));
assertTrue(!isset($otherSex['errors']['sex']), 'Other ("Prefer not to say") is a valid sex');

$noContact = ParticipantValidator::validateForRegistration(array_merge($base, ['contact_no' => '']));
assertTrue(isset($noContact['errors']['contact_no']), 'blank contact number is rejected');

$shortContact = ParticipantValidator::validateForRegistration(array_merge($base, ['contact_no' => '12345']));
assertTrue(isset($shortContact['errors']['contact_no']), 'contact number shorter than 7 digits is rejected');

$ok = ParticipantValidator::validateForRegistration(array_merge($base, ['email' => 'Ana.Reyes@Example.COM']));
assertTrue($ok['errors'] === [], 'complete payload passes');
assertTrue($ok['data']['email'] === 'ana.reyes@example.com', 'email is stored lowercase');

Database::migrate();
$pdo = Database::pdo();
$pdo->exec("DELETE FROM participants WHERE email LIKE '_regval_%@test.local' OR office_email LIKE '_regval_%@test.local'");
$pdo->exec("DELETE FROM events WHERE slug LIKE '_regval-%'");

$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status) VALUES ('_RegVal A','_regval-a',0,0,'open')")->execute();
$eventA = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO events (name, slug, enforce_single_time_in, active, status) VALUES ('_RegVal B','_regval-b',0,0,'open')")->execute();
$eventB = (int)$pdo->lastInsertId();

$ins = $pdo->prepare('INSERT INTO participants (event_id, uuid, email, first_name, last_name, agency) VALUES (?,?,?,?,?,?)');
$ins->execute([$eventA, Uuid::v4(), '_regval_one@test.local', 'One', 'User', 'DICT']);
$office = $pdo->prepare('INSERT INTO participants (event_id, uuid, email, office_email, first_name, last_name, agency) VALUES (?,?,?,?,?,?,?)');
$office->execute([$eventA, Uuid::v4(), '_regval_owner@test.local', '_regval_office@test.local', 'Off', 'User', 'DICT']);

assertTrue(ParticipantValidator::emailTaken($pdo, $eventA, '_regval_one@test.local'), 'same email is taken on the same event');
assertTrue(ParticipantValidator::emailTaken($pdo, $eventA, '_REGVAL_ONE@test.local'), 'same email with different case is taken');
assertTrue(!ParticipantValidator::emailTaken($pdo, $eventB, '_regval_one@test.local'), 'same email may be used on another event');
assertTrue(!ParticipantValidator::emailTaken($pdo, $eventA, '_regval_two@test.local'), 'unused email is free');
assertTrue(ParticipantValidator::emailTaken($pdo, $eventA, '_regval_office@test.local'), 'office email already used is taken');
assertTrue(ParticipantValidator::emailTaken($pdo, $eventA, '_RegVal_Office@test.local'), 'office email match ignores case');

$pdo->exec("DELETE FROM participants WHERE email LIKE '_regval_%@test.local' OR office_email LIKE '_regval_%@test.local'");
$pdo->exec("DELETE FROM events WHERE slug LIKE '_regval-%'");

echo $failed === 0 ? "OK {$passed} passed\n" : "FAILED {$failed}\n";
exit($failed === 0 ? 0 : 1);
