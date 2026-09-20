<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;

class SettingsController
{
    public function form(): void
    {
        if (!AuthService::check()) { header('Location: ?r=admin_login'); return; }
        $env = [
            'SMTP_HOST' => getenv('SMTP_HOST') ?: '',
            'SMTP_PORT' => getenv('SMTP_PORT') ?: '',
            'SMTP_USER' => getenv('SMTP_USER') ?: '',
            'SMTP_SECURE' => getenv('SMTP_SECURE') ?: 'tls',
            'SMTP_FROM' => getenv('SMTP_FROM') ?: '',
        ];
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_settings.php';
    }

    public function save(): void
    {
        if (!AuthService::check()) { header('Location: ?r=admin_login'); return; }
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check($_POST['csrf'])) { http_response_code(400); echo 'Invalid CSRF'; return; }
        $host = trim((string)($_POST['SMTP_HOST'] ?? ''));
        $port = trim((string)($_POST['SMTP_PORT'] ?? ''));
        $user = trim((string)($_POST['SMTP_USER'] ?? ''));
        $pass = trim((string)($_POST['SMTP_PASS'] ?? ''));
        $secure = trim((string)($_POST['SMTP_SECURE'] ?? 'tls'));
        $from = trim((string)($_POST['SMTP_FROM'] ?? ''));
        $file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
        $lines = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES) : [];
        $envKeys = ['DB_HOST','DB_NAME','DB_USER','DB_PASS','MAIL_MODE','SMTP_HOST','SMTP_PORT','SMTP_USER','SMTP_PASS','SMTP_SECURE','SMTP_FROM','QR_EXTERNAL'];
        $newLines = [];
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                $k = trim(substr($line, 0, strpos($line, '=')));
                if (in_array($k, $envKeys)) {
                    switch ($k) {
                        case 'SMTP_HOST': $newLines[] = 'SMTP_HOST=' . $host; break;
                        case 'SMTP_PORT': $newLines[] = 'SMTP_PORT=' . ($port ?: '465'); break;
                        case 'SMTP_USER': $newLines[] = 'SMTP_USER=' . $user; break;
                        case 'SMTP_PASS': $newLines[] = 'SMTP_PASS=' . (($pass !== '' ? $pass : getenv('SMTP_PASS')) ?: ''); break;
                        case 'SMTP_SECURE': $newLines[] = 'SMTP_SECURE=' . ($secure ?: 'ssl'); break;
                        case 'SMTP_FROM': $newLines[] = 'SMTP_FROM=' . ($from ?: $user); break;
                        default: $newLines[] = $line; break;
                    }
                } else {
                    $newLines[] = $line;
                }
            } else {
                $newLines[] = $line;
            }
        }
        foreach ($envKeys as $k) {
            $found = false;
            foreach ($newLines as $l) { if (strpos($l, $k . '=') === 0) { $found = true; break; } }
            if (!$found) {
                switch ($k) {
                    case 'SMTP_HOST': $newLines[] = 'SMTP_HOST=' . $host; break;
                    case 'SMTP_PORT': $newLines[] = 'SMTP_PORT=' . ($port ?: '465'); break;
                    case 'SMTP_USER': $newLines[] = 'SMTP_USER=' . $user; break;
case 'SMTP_PASS': $newLines[] = 'SMTP_PASS=' . (($pass !== '' ? $pass : getenv('SMTP_PASS')) ?: ''); break;
                        case 'SMTP_SECURE': $newLines[] = 'SMTP_SECURE=' . ($secure ?: 'ssl'); break;
                        case 'SMTP_FROM': $newLines[] = 'SMTP_FROM=' . ($from ?: $user); break;
                    case 'QR_EXTERNAL': $newLines[] = 'QR_EXTERNAL=' . (getenv('QR_EXTERNAL') ?: 'false'); break;
                }
            }
        }
        file_put_contents($file, implode("\n", $newLines) . "\n");
        if (function_exists('csrf_rotate')) csrf_rotate();
        header('Location: ?r=admin_settings');
    }
}