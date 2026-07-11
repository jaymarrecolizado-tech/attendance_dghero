<?php
declare(strict_types=1);

$tests = [
  'scripts/test_validators.php',
  'scripts/test_duplicate_detection.php',
  'scripts/test_attendance_rule.php'
];

foreach ($tests as $t) {
  passthru(PHP_BINARY . ' ' . $t);
}