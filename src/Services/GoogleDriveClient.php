<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Plan#18: Drive upload/download via a service-account JWT (no google/apiclient).
 * Tests may set $uploadOverride / $downloadOverride.
 */
final class GoogleDriveClient
{
    public static string $lastError = '';

    /** @var callable|null fn(string $path, string $name): ?array{id:string,url:string} */
    public static $uploadOverride = null;

    /** @var callable|null fn(string $id, string $dest): bool */
    public static $downloadOverride = null;

    public static function uploadsEnabled(): bool
    {
        $v = strtolower(trim((string)(getenv('GOOGLE_DRIVE_ENABLED') ?: '')));
        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }

    public function isConfigured(): bool
    {
        if (!self::uploadsEnabled()) {
            return false;
        }
        $folder = trim((string)(getenv('GOOGLE_DRIVE_FOLDER_ID') ?: ''));
        $json = trim((string)(getenv('GOOGLE_DRIVE_SA_JSON') ?: ''));
        return $folder !== '' && $json !== '' && is_file($json);
    }

    /** @return array{id:string,url:string}|null */
    public function upload(string $localPath, string $name): ?array
    {
        self::$lastError = '';
        if (is_callable(self::$uploadOverride)) {
            $out = (self::$uploadOverride)($localPath, $name);
            return is_array($out) ? $out : null;
        }
        if (!$this->isConfigured() || !is_file($localPath)) {
            self::$lastError = 'Drive is not configured or the zip is missing';
            return null;
        }
        $token = $this->accessToken();
        if ($token === '') {
            self::$lastError = self::$lastError !== '' ? self::$lastError : 'Could not get a Drive access token';
            return null;
        }
        $folder = trim((string)getenv('GOOGLE_DRIVE_FOLDER_ID'));
        $meta = json_encode([
            'name' => $name,
            'parents' => [$folder],
        ], JSON_UNESCAPED_SLASHES);
        $boundary = 'dictprivacy' . bin2hex(random_bytes(8));
        $body = "--{$boundary}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n"
            . $meta . "\r\n--{$boundary}\r\nContent-Type: application/zip\r\n\r\n"
            . (string)file_get_contents($localPath)
            . "\r\n--{$boundary}--";
        $raw = $this->http(
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true&fields=id,webViewLink',
            [
                'Authorization: Bearer ' . $token,
                'Content-Type: multipart/related; boundary=' . $boundary,
            ],
            $body
        );
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded['id'])) {
            $msg = is_array($decoded) ? (string)($decoded['error']['message'] ?? json_encode($decoded)) : substr($raw, 0, 300);
            self::$lastError = $msg !== '' ? $msg : 'Drive upload returned no file id';
            return null;
        }
        $id = (string)$decoded['id'];
        $url = trim((string)($decoded['webViewLink'] ?? ''));
        if ($url === '') {
            $url = 'https://drive.google.com/file/d/' . rawurlencode($id) . '/view';
        }
        return ['id' => $id, 'url' => $url];
    }

    public function download(string $fileId, string $destPath): bool
    {
        if (is_callable(self::$downloadOverride)) {
            return (bool)(self::$downloadOverride)($fileId, $destPath);
        }
        if ($fileId === '' || !$this->isConfigured()) {
            return false;
        }
        $token = $this->accessToken();
        if ($token === '') {
            return false;
        }
        $raw = $this->http(
            'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '?alt=media',
            ['Authorization: Bearer ' . $token],
            null
        );
        if ($raw === '' || str_starts_with(ltrim($raw), '{')) {
            return false;
        }
        $dir = dirname($destPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return file_put_contents($destPath, $raw) !== false;
    }

    private function accessToken(): string
    {
        $path = trim((string)getenv('GOOGLE_DRIVE_SA_JSON'));
        $sa = json_decode((string)file_get_contents($path), true);
        if (!is_array($sa) || empty($sa['client_email']) || empty($sa['private_key'])) {
            self::$lastError = 'Service account JSON is incomplete';
            return '';
        }
        $now = time();
        $header = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->b64url(json_encode([
            'iss' => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        $unsigned = $header . '.' . $claim;
        $ok = openssl_sign($unsigned, $signature, (string)$sa['private_key'], OPENSSL_ALGO_SHA256);
        if (!$ok) {
            return '';
        }
        $jwt = $unsigned . '.' . $this->b64url($signature);
        $raw = $this->http(
            'https://oauth2.googleapis.com/token',
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ])
        );
        $decoded = json_decode($raw, true);
        $token = is_array($decoded) ? trim((string)($decoded['access_token'] ?? '')) : '';
        if ($token === '') {
            self::$lastError = is_array($decoded)
                ? (string)($decoded['error_description'] ?? $decoded['error'] ?? 'token request failed')
                : 'token request failed';
        }
        return $token;
    }

    /** @param list<string> $headers */
    private function http(string $url, array $headers, ?string $body): string
    {
        if (!function_exists('curl_init')) {
            self::$lastError = 'PHP curl extension is missing';
            return '';
        }
        $ch = curl_init($url);
        if ($ch === false) {
            self::$lastError = 'curl_init failed';
            return '';
        }
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        if ($raw === false) {
            self::$lastError = 'curl: ' . curl_error($ch);
            curl_close($ch);
            return '';
        }
        curl_close($ch);
        return is_string($raw) ? $raw : '';
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
