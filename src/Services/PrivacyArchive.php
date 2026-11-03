<?php
declare(strict_types=1);

namespace App\Services;

use ZipArchive;

/** Plan#18: encrypted zip of one event's participant rows and files. */
final class PrivacyArchive
{
    public static function isLocalBackupFile(string $path): bool
    {
        $path = trim($path);
        if ($path === '' || !is_file($path)) {
            return false;
        }
        $dir = realpath(self::storageDir());
        $real = realpath($path);
        if ($dir === false || $real === false) {
            return false;
        }
        $prefix = $dir . DIRECTORY_SEPARATOR;
        return str_starts_with($real, $prefix) && str_ends_with(strtolower($real), '.zip');
    }

    public static function storageDir(): string
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'privacy-backups';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $deny = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($deny)) {
            file_put_contents($deny, "Require all denied\nDeny from all\n");
        }
        return $dir;
    }

    /**
     * @param list<array<string,mixed>> $participants
     * @param list<array<string,mixed>> $attendance
     * @param list<array<string,mixed>> $coaSends
     */
    public static function writeZip(array $event, array $participants, array $attendance, array $coaSends): string
    {
        $slug = preg_replace('/[^a-z0-9\-]+/i', '-', (string)($event['slug'] ?? 'event')) ?: 'event';
        $name = $slug . '-participants-' . date('YmdHis') . '-AsiaManila.zip';
        $path = self::storageDir() . DIRECTORY_SEPARATOR . $name;
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create privacy zip');
        }
        $pass = trim((string)(getenv('PRIVACY_BACKUP_PASSPHRASE') ?: ''));
        $manifest = json_encode([
            'event_id' => (int)$event['id'],
            'name' => (string)($event['name'] ?? ''),
            'slug' => (string)($event['slug'] ?? ''),
            'ends_at' => (string)($event['ends_at'] ?? ''),
            'purged_at' => date('c'),
            'notice_version' => PrivacyNotice::VERSION,
            'participants' => count($participants),
            'attendance' => count($attendance),
            'coa_sends' => count($coaSends),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        self::addString($zip, 'manifest.json', (string)$manifest, $pass);
        self::addString($zip, 'participants.csv', self::toCsv($participants), $pass);
        self::addString($zip, 'attendance.csv', self::toCsv($attendance), $pass);
        self::addString($zip, 'coa_sends.csv', self::toCsv($coaSends), $pass);
        foreach (self::filePaths($participants, $attendance, $coaSends) as $rel => $abs) {
            if (!is_file($abs)) {
                continue;
            }
            $local = 'files/' . $rel;
            $zip->addFile($abs, $local);
            self::encrypt($zip, $local, $pass);
        }
        $zip->close();
        return $path;
    }

    /** @return array{dir:string,manifest:array,participants:list<array>,attendance:list<array>,coa_sends:list<array>} */
    public static function extract(string $zipPath): array
    {
        $zip = new ZipArchive();
        $flags = 0;
        $pass = trim((string)(getenv('PRIVACY_BACKUP_PASSPHRASE') ?: ''));
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Could not open privacy zip');
        }
        if ($pass !== '') {
            $zip->setPassword($pass);
        }
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privacy-restore-' . bin2hex(random_bytes(4));
        mkdir($dir, 0775, true);
        $zip->extractTo($dir);
        $zip->close();
        return [
            'dir' => $dir,
            'manifest' => self::readJson($dir . DIRECTORY_SEPARATOR . 'manifest.json'),
            'participants' => self::fromCsv($dir . DIRECTORY_SEPARATOR . 'participants.csv'),
            'attendance' => self::fromCsv($dir . DIRECTORY_SEPARATOR . 'attendance.csv'),
            'coa_sends' => self::fromCsv($dir . DIRECTORY_SEPARATOR . 'coa_sends.csv'),
        ];
    }

    public static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function absPath(string $stored): string
    {
        $stored = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $stored);
        if ($stored !== '' && ($stored[0] === DIRECTORY_SEPARATOR || preg_match('/^[A-Za-z]:\\\\/', $stored) === 1)) {
            return $stored;
        }
        return self::projectRoot() . DIRECTORY_SEPARATOR . $stored;
    }

    /**
     * @param list<array<string,mixed>> $participants
     * @param list<array<string,mixed>> $attendance
     * @param list<array<string,mixed>> $coaSends
     * @return array<string,string> relative zip name => absolute path
     */
    private static function filePaths(array $participants, array $attendance, array $coaSends): array
    {
        $out = [];
        foreach ($participants as $row) {
            $p = trim((string)($row['qr_path'] ?? ''));
            if ($p !== '') {
                $out['qr/' . basename($p)] = self::absPath($p);
            }
        }
        foreach ($attendance as $row) {
            $p = trim((string)($row['signature_path'] ?? ''));
            if ($p !== '') {
                $out['sig/' . basename($p)] = self::absPath($p);
            }
        }
        foreach ($coaSends as $row) {
            $p = trim((string)($row['pdf_path'] ?? ''));
            if ($p !== '') {
                $out['coa/' . basename($p)] = self::absPath($p);
            }
        }
        return $out;
    }

    /** @param list<array<string,mixed>> $rows */
    private static function toCsv(array $rows): string
    {
        $fh = fopen('php://temp', 'w+');
        if ($fh === false) {
            return '';
        }
        if ($rows) {
            $headers = array_keys($rows[0]);
            fputcsv($fh, $headers);
            foreach ($rows as $row) {
                $line = [];
                foreach ($headers as $h) {
                    $line[] = $row[$h] ?? '';
                }
                fputcsv($fh, $line);
            }
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);
        return $csv;
    }

    /** @return list<array<string,string>> */
    private static function fromCsv(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        $fh = fopen($path, 'r');
        if ($fh === false) {
            return [];
        }
        $headers = fgetcsv($fh);
        if (!is_array($headers) || $headers === []) {
            fclose($fh);
            return [];
        }
        $out = [];
        while (($line = fgetcsv($fh)) !== false) {
            $row = [];
            foreach ($headers as $i => $h) {
                $row[(string)$h] = (string)($line[$i] ?? '');
            }
            $out[] = $row;
        }
        fclose($fh);
        return $out;
    }

    /** @return array<string,mixed> */
    private static function readJson(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        $decoded = json_decode((string)file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function addString(ZipArchive $zip, string $name, string $contents, string $pass): void
    {
        $zip->addFromString($name, $contents);
        self::encrypt($zip, $name, $pass);
    }

    private static function encrypt(ZipArchive $zip, string $name, string $pass): void
    {
        if ($pass === '' || !method_exists($zip, 'setEncryptionName')) {
            return;
        }
        if (defined('ZipArchive::EM_AES_256')) {
            @$zip->setEncryptionName($name, ZipArchive::EM_AES_256, $pass);
        }
    }
}
