<?php
declare(strict_types=1);

namespace App\Services;

class Mailer
{
    /** Human-readable reason for the most recent send failure (Plan#13). */
    public static string $lastError = '';

    public static function send(string $to, string $subject, string $body, ?string $attachmentPath = null, ?string $fromName = null): bool
    {
        self::$lastError = '';
        $mode = getenv('MAIL_MODE') ?: 'log';
        if ($mode === 'log') {
            $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'outbox';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $name = $dir . DIRECTORY_SEPARATOR . time() . '_' . preg_replace('/[^a-z0-9]+/i','_', $to) . '.eml';
            $fromLine = ($fromName !== null && $fromName !== '') ? 'From: ' . $fromName . ' <' . $fromName . '@local>' . "\n" : '';
            $content = "To: {$to}\nSubject: {$subject}\n{$fromLine}\n{$body}\n";
            if ($attachmentPath && is_file($attachmentPath)) {
                $content .= "\nAttachment: {$attachmentPath}\n";
            }
            $ok = (bool)file_put_contents($name, $content);
            if (!$ok) {
                self::$lastError = 'Could not write to the log outbox';
            }
            return $ok;
        }
        if ($mode === 'smtp') {
            if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
                $host = getenv('SMTP_HOST') ?: '';
                $port = (int)(getenv('SMTP_PORT') ?: '587');
                $user = getenv('SMTP_USER') ?: '';
                $pass = getenv('SMTP_PASS') ?: '';
                $secure = getenv('SMTP_SECURE') ?: 'tls';
                $from = getenv('SMTP_FROM') ?: $user;
                if ($host === '' || $user === '' || $pass === '') {
                    self::$lastError = 'SMTP settings incomplete (host, user, or password missing)';
                    return false;
                }
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = $host;
                    $mail->Port = $port;
                    $mail->SMTPAuth = true;
                    $mail->Username = $user;
                    $mail->Password = $pass;
                    $mail->SMTPSecure = $secure === 'ssl' ? 'ssl' : 'tls';
                    $mail->setFrom($from, $fromName ?? '');
                    $mail->addAddress($to);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $body;
                    if ($attachmentPath && is_file($attachmentPath)) {
                        $mail->addAttachment($attachmentPath, basename($attachmentPath));
                    }
                    return $mail->send();
                } catch (\Exception $e) {
                    self::$lastError = $e->getMessage();
                    return false;
                }
            }
            $host = getenv('SMTP_HOST') ?: '';
            $port = (int)(getenv('SMTP_PORT') ?: '587');
            $user = getenv('SMTP_USER') ?: '';
            $pass = getenv('SMTP_PASS') ?: '';
            $secure = getenv('SMTP_SECURE') ?: 'tls';
            $from = getenv('SMTP_FROM') ?: $user;
            if ($host === '' || $user === '' || $pass === '') {
                self::$lastError = 'SMTP settings incomplete (host, user, or password missing)';
                return false;
            }
            $transport = $secure === 'ssl' ? 'ssl://' : 'tcp://';
            $sock = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 15);
            if (!$sock) {
                self::$lastError = 'SMTP connect failed: ' . ($errstr !== '' ? $errstr : 'error ' . $errno);
                return false;
            }
            stream_set_timeout($sock, 15);
            $fail = function (string $reason) use ($sock) {
                self::$lastError = $reason;
                fclose($sock);
                return false;
            };
            $read = function() use ($sock) { $line = ''; $resp = ''; do { $line = fgets($sock); if ($line === false) break; $resp .= $line; } while (strlen($line) > 3 && isset($line[3]) && $line[3] === '-'); return $resp; };
            $code = function($resp){ return (int)substr($resp,0,3); };
            $write = function($cmd) use ($sock) { fwrite($sock, $cmd . "\r\n"); };
            if ($code($read()) !== 220) { return $fail('SMTP greeting failed'); }
            $write('EHLO localhost');
            if ($code($read()) !== 250) { return $fail('EHLO rejected'); }
            if ($secure === 'tls') {
                $write('STARTTLS');
                if ($code($read()) !== 220) { return $fail('STARTTLS rejected'); }
                if (!@stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { return $fail('TLS negotiation failed'); }
                $write('EHLO localhost');
                if ($code($read()) !== 250) { return $fail('EHLO after TLS rejected'); }
            }
            $write('AUTH LOGIN');
            if ($code($read()) !== 334) { return $fail('AUTH LOGIN rejected'); }
            $write(base64_encode($user));
            if ($code($read()) !== 334) { return $fail('AUTH username rejected'); }
            $write(base64_encode($pass));
            if ($code($read()) !== 235) { return $fail('SMTP authentication failed (check SMTP_PASS)'); }
            $write('MAIL FROM:<' . $from . '>');
            if ($code($read()) !== 250) { return $fail('MAIL FROM rejected'); }
            $write('RCPT TO:<' . $to . '>');
            if ($code($read()) !== 250) { return $fail('Recipient rejected by SMTP server'); }
            $write('DATA');
            if ($code($read()) !== 354) { return $fail('DATA rejected by SMTP server'); }
            $boundary = 'bnd_' . bin2hex(random_bytes(8));
            $date = gmdate('D, d M Y H:i:s') . ' +0000';
            $msgId = bin2hex(random_bytes(8)) . '@localhost';
            $headers = [];
            $headers[] = 'From: ' . (($fromName !== null && $fromName !== '') ? $fromName . ' <' . $from . '>' : $from);
            $headers[] = 'To: ' . $to;
            $headers[] = 'Subject: ' . $subject;
            $headers[] = 'Date: ' . $date;
            $headers[] = 'Message-ID: <' . $msgId . '>';
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
            $message = '';
            $message .= '--' . $boundary . "\r\n";
            $message .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
            $message .= 'Content-Transfer-Encoding: 7bit' . "\r\n\r\n";
            $message .= $body . "\r\n";
            if ($attachmentPath && is_file($attachmentPath)) {
                $data = file_get_contents($attachmentPath);
                $filename = basename($attachmentPath);
                $mime = str_ends_with(strtolower($filename), '.pdf') ? 'application/pdf' : 'image/png';
                $message .= '--' . $boundary . "\r\n";
                $message .= 'Content-Type: ' . $mime . '; name="' . $filename . '"' . "\r\n";
                $message .= 'Content-Transfer-Encoding: base64' . "\r\n";
                $message .= 'Content-Disposition: attachment; filename="' . $filename . '"' . "\r\n\r\n";
                $message .= chunk_split(base64_encode($data), 76, "\r\n") . "\r\n";
            }
            $message .= '--' . $boundary . '--' . "\r\n";
            $dataOut = implode("\r\n", $headers) . "\r\n\r\n" . $message . "\r\n.";
            $write($dataOut);
            $resp = $read();
            if ($code($resp) !== 250) { return $fail('Message not accepted: ' . trim($resp)); }
            $write('QUIT');
            $read();
            fclose($sock);
            return true;
        }
        $headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=UTF-8';
        $ok = @mail($to, $subject, $body, $headers);
        if (!$ok) {
            self::$lastError = 'PHP mail() returned false';
        }
        return $ok;
    }
}
