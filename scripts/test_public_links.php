<?php
declare(strict_types=1);

require __DIR__ . '/../src/Services/EventContext.php';

use App\Services\EventContext;

function assertSame(string $expected, string $actual, string $label): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $label . "\n expected: {$expected}\n actual:   {$actual}\n");
        exit(1);
    }
}

$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'attendance.example';
$_SERVER['SCRIPT_NAME'] = '/index.php';
unset($_SERVER['HTTP_X_FORWARDED_PROTO']);

$links = EventContext::publicLinks('Hack for Gov 5');
assertSame('?r=register&e=Hack+for+Gov+5', $links['register'], 'relative register path');
assertSame(
    'https://attendance.example/index.php?r=register&e=Hack+for+Gov+5',
    EventContext::absolutePublicUrl($links['register']),
    'https absolute register link'
);

$_SERVER['HTTPS'] = 'off';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/Projects/pred-attendance-ult/index.php';
assertSame(
    'https://localhost/Projects/pred-attendance-ult/index.php?r=register&e=demo',
    EventContext::absolutePublicUrl('?r=register&e=demo'),
    'forwarded https'
);

$_SERVER['HTTP_X_FORWARDED_PROTO'] = '';
$_SERVER['HTTP_HOST'] = "evil\r\nhost";
assertSame(
    'http://localhost/Projects/pred-attendance-ult/index.php?r=register&e=demo',
    EventContext::absolutePublicUrl('?r=register&e=demo'),
    'rejects a broken host'
);

$already = 'https://share.example/index.php?r=register&e=demo';
assertSame($already, EventContext::absolutePublicUrl($already), 'leaves absolute urls alone');

echo "public_links_ok\n";
