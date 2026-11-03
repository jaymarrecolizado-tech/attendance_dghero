<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/** Plan#18: backup, Drive, live delete, notices, All Father restore. */
final class PrivacyPurgeService
{
    public static function nowManila(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('Asia/Manila'));
    }

    /** @return list<array<string,mixed>> */
    public static function dueEvents(PDO $pdo): array
    {
        Database::ensurePrivacyFacility($pdo);
        $now = self::nowManila();
        $cutoff = $now->modify('-15 days')->format('Y-m-d H:i:s');
        $nowSql = $now->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare(
            "SELECT e.* FROM events e
             WHERE e.privacy_purged_at IS NULL
               AND e.ends_at IS NOT NULL AND e.ends_at <> ''
               AND e.ends_at <= ?
               AND (e.privacy_restore_hold_until IS NULL OR e.privacy_restore_hold_until <= ?)
               AND EXISTS (SELECT 1 FROM participants p WHERE p.event_id = e.id)
             ORDER BY e.id ASC"
        );
        $stmt->execute([$cutoff, $nowSql]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array{ok:bool,job_id?:int,error?:string,drive?:bool} */
    public static function purgeEvent(int $eventId, ?GoogleDriveClient $drive = null): array
    {
        $pdo = Database::pdo();
        Database::ensurePrivacyFacility($pdo);
        $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        if (!$event) {
            return ['ok' => false, 'error' => 'event_not_found'];
        }
        if (!empty($event['privacy_purged_at'])) {
            return ['ok' => false, 'error' => 'already_purged'];
        }
        $parts = $pdo->prepare('SELECT * FROM participants WHERE event_id = ? ORDER BY id ASC');
        $parts->execute([$eventId]);
        $participants = $parts->fetchAll() ?: [];
        if (!$participants) {
            return ['ok' => false, 'error' => 'no_participants'];
        }
        $att = $pdo->prepare('SELECT * FROM attendance WHERE event_id = ? ORDER BY id ASC');
        $att->execute([$eventId]);
        $attendance = $att->fetchAll() ?: [];
        $coa = $pdo->prepare('SELECT * FROM coa_sends WHERE event_id = ? ORDER BY id ASC');
        $coa->execute([$eventId]);
        $coaSends = $coa->fetchAll() ?: [];

        try {
            $zipPath = PrivacyArchive::writeZip($event, $participants, $attendance, $coaSends);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'zip_failed'];
        }

        $drive = $drive ?? new GoogleDriveClient();
        $attemptDrive = is_callable(GoogleDriveClient::$uploadOverride) || $drive->isConfigured();
        $driveMeta = null;
        $driveOk = false;
        if ($attemptDrive) {
            $driveMeta = $drive->upload($zipPath, basename($zipPath));
            $driveOk = is_array($driveMeta) && !empty($driveMeta['id']);
        }

        $now = self::nowManila()->format('Y-m-d H:i:s');
        $pdo->beginTransaction();
        try {
            $job = $pdo->prepare(
                'INSERT INTO privacy_jobs (event_id, status, participant_count, mailed, skipped_no_email, backup_path, drive_id, drive_url, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            );
            $status = $driveOk ? 'purged' : ($attemptDrive ? 'purged_drive_pending' : 'purged');
            $job->execute([
                $eventId,
                $status,
                count($participants),
                0,
                0,
                $driveOk ? null : $zipPath,
                $driveOk ? (string)$driveMeta['id'] : null,
                $driveOk ? (string)$driveMeta['url'] : null,
                $now,
                $now,
            ]);
            $jobId = (int)$pdo->lastInsertId();
            self::deleteLive($pdo, $eventId, $participants, $attendance, $coaSends);
            $pdo->prepare(
                'UPDATE events SET privacy_purged_at = ?, privacy_backup_drive_id = ?, privacy_backup_url = ?, privacy_backup_path = ?, privacy_restore_hold_until = NULL WHERE id = ?'
            )->execute([
                $now,
                $driveOk ? (string)$driveMeta['id'] : null,
                $driveOk ? (string)$driveMeta['url'] : null,
                $driveOk ? null : $zipPath,
                $eventId,
            ]);
            $mailCounts = self::queueNotices($pdo, $jobId, $event, $participants, $driveOk, $attemptDrive);
            $pdo->prepare('UPDATE privacy_jobs SET mailed = ?, skipped_no_email = ?, updated_at = ? WHERE id = ?')
                ->execute([$mailCounts['mailed'], $mailCounts['skipped'], $now, $jobId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'purge_failed'];
        }

        if ($driveOk && is_file($zipPath)) {
            @unlink($zipPath);
        }
        self::sendQueued($pdo, $jobId);
        self::sendNoticeSummary($pdo, $jobId);
        Logger::log(null, 'privacy_purge', [
            'event_id' => $eventId,
            'job_id' => $jobId,
            'drive' => $driveOk,
            'count' => count($participants),
        ], $eventId);
        return ['ok' => true, 'job_id' => $jobId, 'drive' => $driveOk];
    }

    public static function retryPendingDrive(?GoogleDriveClient $drive = null): int
    {
        $drive = $drive ?? new GoogleDriveClient();
        if (!is_callable(GoogleDriveClient::$uploadOverride) && !$drive->isConfigured()) {
            return 0;
        }
        $pdo = Database::pdo();
        Database::ensurePrivacyFacility($pdo);
        $rows = $pdo->query(
            "SELECT * FROM events WHERE privacy_purged_at IS NOT NULL AND (privacy_backup_drive_id IS NULL OR privacy_backup_drive_id = '') AND privacy_backup_path IS NOT NULL AND privacy_backup_path <> ''"
        )->fetchAll() ?: [];
        $n = 0;
        foreach ($rows as $event) {
            $path = (string)$event['privacy_backup_path'];
            if (!is_file($path)) {
                continue;
            }
            $meta = $drive->upload($path, basename($path));
            if (!is_array($meta) || empty($meta['id'])) {
                continue;
            }
            $pdo->prepare('UPDATE events SET privacy_backup_drive_id = ?, privacy_backup_url = ?, privacy_backup_path = NULL WHERE id = ?')
                ->execute([(string)$meta['id'], (string)$meta['url'], (int)$event['id']]);
            $jid = $pdo->prepare('SELECT id FROM privacy_jobs WHERE event_id = ? ORDER BY id DESC LIMIT 1');
            $jid->execute([(int)$event['id']]);
            $jobId = (int)$jid->fetchColumn();
            if ($jobId > 0) {
                $pdo->prepare("UPDATE privacy_jobs SET status = 'purged', drive_id = ?, drive_url = ?, backup_path = NULL, updated_at = ? WHERE id = ?")
                    ->execute([(string)$meta['id'], (string)$meta['url'], self::nowManila()->format('Y-m-d H:i:s'), $jobId]);
            }
            @unlink($path);
            $n++;
        }
        return $n;
    }

    /**
     * @return array{ok:bool,restored?:int,error?:string}
     */
    public static function restoreEvent(int $eventId, ?GoogleDriveClient $drive = null): array
    {
        $pdo = Database::pdo();
        Database::ensurePrivacyFacility($pdo);
        $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        if (!$event) {
            return ['ok' => false, 'error' => 'event_not_found'];
        }
        $zipPath = trim((string)($event['privacy_backup_path'] ?? ''));
        if ($zipPath === '' || !is_file($zipPath)) {
            $driveId = trim((string)($event['privacy_backup_drive_id'] ?? ''));
            if ($driveId === '') {
                return ['ok' => false, 'error' => 'no_backup'];
            }
            $drive = $drive ?? new GoogleDriveClient();
            $zipPath = PrivacyArchive::storageDir() . DIRECTORY_SEPARATOR . 'restore-' . $eventId . '.zip';
            if (!$drive->download($driveId, $zipPath)) {
                return ['ok' => false, 'error' => 'drive_download_failed'];
            }
        }
        try {
            $pack = PrivacyArchive::extract($zipPath);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'unzip_failed'];
        }
        $idMap = [];
        $restored = 0;
        $pdo->beginTransaction();
        try {
            foreach ($pack['participants'] as $row) {
                $email = trim((string)($row['email'] ?? ''));
                if ($email !== '') {
                    $dup = $pdo->prepare('SELECT id FROM participants WHERE event_id = ? AND email = ? LIMIT 1');
                    $dup->execute([$eventId, $email]);
                    if ($dup->fetch()) {
                        continue;
                    }
                }
                $oldId = (int)($row['id'] ?? 0);
                $uuid = trim((string)($row['uuid'] ?? ''));
                if ($uuid === '') {
                    $uuid = Uuid::v4();
                } else {
                    $clash = $pdo->prepare('SELECT id FROM participants WHERE uuid = ? LIMIT 1');
                    $clash->execute([$uuid]);
                    if ($clash->fetch()) {
                        $uuid = Uuid::v4();
                    }
                }
                $qr = self::restoreFile($pack['dir'], 'qr', (string)($row['qr_path'] ?? ''));
                $ins = $pdo->prepare(
                    'INSERT INTO participants (event_id, uuid, email, first_name, middle_name, last_name, nickname, sex, sector, agency, designation, office_email, contact_no, qr_path, is_vip, privacy_consent_at, privacy_notice_version)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                );
                $ins->execute([
                    $eventId,
                    $uuid,
                    $email !== '' ? $email : null,
                    (string)($row['first_name'] ?? ''),
                    (string)($row['middle_name'] ?? '') !== '' ? $row['middle_name'] : null,
                    (string)($row['last_name'] ?? ''),
                    (string)($row['nickname'] ?? '') !== '' ? $row['nickname'] : null,
                    (string)($row['sex'] ?? '') !== '' ? $row['sex'] : null,
                    (string)($row['sector'] ?? '') !== '' ? $row['sector'] : null,
                    (string)($row['agency'] ?? '') !== '' ? $row['agency'] : null,
                    (string)($row['designation'] ?? '') !== '' ? $row['designation'] : null,
                    (string)($row['office_email'] ?? '') !== '' ? $row['office_email'] : null,
                    (string)($row['contact_no'] ?? '') !== '' ? $row['contact_no'] : null,
                    $qr,
                    (int)($row['is_vip'] ?? 0),
                    (string)($row['privacy_consent_at'] ?? '') !== '' ? $row['privacy_consent_at'] : null,
                    (string)($row['privacy_notice_version'] ?? '') !== '' ? $row['privacy_notice_version'] : null,
                ]);
                $idMap[$oldId] = (int)$pdo->lastInsertId();
                $restored++;
            }
            foreach ($pack['attendance'] as $row) {
                $newPid = $idMap[(int)($row['participant_id'] ?? 0)] ?? 0;
                if ($newPid <= 0) {
                    continue;
                }
                $sig = self::restoreFile($pack['dir'], 'sig', (string)($row['signature_path'] ?? ''));
                $pdo->prepare(
                    'INSERT INTO attendance (participant_id, attendance_date, time_in, signature_path, event_id, status) VALUES (?,?,?,?,?,?)'
                )->execute([
                    $newPid,
                    (string)($row['attendance_date'] ?? date('Y-m-d')),
                    (string)($row['time_in'] ?? '00:00:00'),
                    $sig ?? '',
                    $eventId,
                    (string)($row['status'] ?? 'present'),
                ]);
            }
            foreach ($pack['coa_sends'] as $row) {
                $newPid = $idMap[(int)($row['participant_id'] ?? 0)] ?? 0;
                if ($newPid <= 0) {
                    continue;
                }
                $pdf = self::restoreFile($pack['dir'], 'coa', (string)($row['pdf_path'] ?? ''));
                $pdo->prepare(
                    'INSERT INTO coa_sends (batch_id, event_id, participant_id, attendance_date, email, status, error, pdf_path, event_name_snapshot, venue_snapshot, signatory_snapshot, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
                )->execute([
                    (int)($row['batch_id'] ?? 0) ?: null,
                    $eventId,
                    $newPid,
                    (string)($row['attendance_date'] ?? ''),
                    (string)($row['email'] ?? ''),
                    (string)($row['status'] ?? 'sent'),
                    (string)($row['error'] ?? ''),
                    $pdf,
                    (string)($row['event_name_snapshot'] ?? ''),
                    (string)($row['venue_snapshot'] ?? ''),
                    (string)($row['signatory_snapshot'] ?? ''),
                    (string)($row['created_at'] ?? self::nowManila()->format('Y-m-d H:i:s')),
                    self::nowManila()->format('Y-m-d H:i:s'),
                ]);
            }
            $hold = self::nowManila()->modify('+15 days')->format('Y-m-d H:i:s');
            $pdo->prepare('UPDATE events SET privacy_purged_at = NULL, privacy_restore_hold_until = ? WHERE id = ?')
                ->execute([$hold, $eventId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'restore_failed'];
        }
        Logger::log(isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null, 'privacy_restore', [
            'event_id' => $eventId,
            'restored' => $restored,
        ], $eventId);
        return ['ok' => true, 'restored' => $restored];
    }

    public static function processDue(?GoogleDriveClient $drive = null): array
    {
        $drive = $drive ?? new GoogleDriveClient();
        $pdo = Database::pdo();
        $purged = 0;
        foreach (self::dueEvents($pdo) as $event) {
            $r = self::purgeEvent((int)$event['id'], $drive);
            if ($r['ok'] ?? false) {
                $purged++;
            }
        }
        $retried = self::retryPendingDrive($drive);
        return ['purged' => $purged, 'drive_retried' => $retried];
    }

    /**
     * @param list<array<string,mixed>> $participants
     * @param list<array<string,mixed>> $attendance
     * @param list<array<string,mixed>> $coaSends
     */
    private static function deleteLive(PDO $pdo, int $eventId, array $participants, array $attendance, array $coaSends): void
    {
        foreach (array_merge($participants, $attendance, $coaSends) as $row) {
            foreach (['qr_path', 'signature_path', 'pdf_path'] as $key) {
                $p = trim((string)($row[$key] ?? ''));
                if ($p === '') {
                    continue;
                }
                $abs = PrivacyArchive::absPath($p);
                if (is_file($abs)) {
                    @unlink($abs);
                }
            }
        }
        $coaDir = PrivacyArchive::projectRoot() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'coa' . DIRECTORY_SEPARATOR . $eventId;
        if (is_dir($coaDir)) {
            self::rmdirDeep($coaDir);
        }
        $pdo->prepare('DELETE FROM coa_sends WHERE event_id = ?')->execute([$eventId]);
        $pdo->prepare('DELETE FROM coa_batches WHERE event_id = ?')->execute([$eventId]);
        $pdo->prepare('DELETE FROM attendance WHERE event_id = ?')->execute([$eventId]);
        $pdo->prepare('DELETE FROM participants WHERE event_id = ?')->execute([$eventId]);
    }

    /**
     * @param list<array<string,mixed>> $participants
     * @return array{mailed:int,skipped:int}
     */
    private static function queueNotices(PDO $pdo, int $jobId, array $event, array $participants, bool $driveOk, bool $attemptDrive): array
    {
        $now = self::nowManila()->format('Y-m-d H:i:s');
        $eventName = (string)($event['name'] ?? '');
        $mailed = 0;
        $skipped = 0;
        $ins = $pdo->prepare(
            'INSERT INTO privacy_notices (job_id, event_id, kind, to_email, first_name, event_name, status, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        foreach ($participants as $p) {
            $to = trim((string)($p['email'] ?? ''));
            if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
                $to = trim((string)($p['office_email'] ?? ''));
            }
            if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
                $ins->execute([$jobId, (int)$event['id'], 'participant', null, (string)($p['first_name'] ?? ''), $eventName, 'skipped', $now, $now]);
                $skipped++;
                continue;
            }
            $ins->execute([$jobId, (int)$event['id'], 'participant', $to, (string)($p['first_name'] ?? ''), $eventName, 'queued', $now, $now]);
            $mailed++;
        }
        $staff = array_filter(array_map('trim', explode(',', (string)(getenv('PRIVACY_BACKUP_TO') ?: ''))));
        foreach ($staff as $to) {
            if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }
            $staffKind = $driveOk ? 'staff' : ($attemptDrive ? 'staff_fail' : 'staff_local');
            $ins->execute([$jobId, (int)$event['id'], $staffKind, $to, '', $eventName, 'queued', $now, $now]);
        }
        return ['mailed' => $mailed, 'skipped' => $skipped];
    }

    private static function sendQueued(PDO $pdo, int $jobId): void
    {
        $stmt = $pdo->prepare("SELECT * FROM privacy_notices WHERE job_id = ? AND status = 'queued' ORDER BY id ASC");
        $stmt->execute([$jobId]);
        $photos = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $kind = (string)$row['kind'];
            $to = (string)$row['to_email'];
            $eventName = (string)$row['event_name'];
            $inline = [];
            $fromName = $eventName !== '' ? $eventName : null;
            if ($kind === 'participant') {
                $eid = (int)$row['event_id'];
                if (!array_key_exists($eid, $photos)) {
                    $evStmt = $pdo->prepare('SELECT id, banner_path, logo_path, theme_layout FROM events WHERE id = ?');
                    $evStmt->execute([$eid]);
                    $photos[$eid] = PrivacyNotice::registrationPhotoMailUrl($evStmt->fetch() ?: []);
                }
                $photoUrl = $photos[$eid];
                $subject = Mailer::privacyDeletedSubject($eventName);
                $body = Mailer::privacyDeletedHtml((string)$row['first_name'], $eventName, $photoUrl !== '' ? $photoUrl : null);
            } elseif ($kind === 'staff_fail') {
                $subject = 'Privacy archive pending Drive upload: ' . $eventName;
                $body = '<p>Live participant data for <strong>' . htmlspecialchars($eventName, ENT_QUOTES) . '</strong> was deleted. Google Drive was unavailable. The encrypted zip remains on the server and will be retried.</p>';
            } elseif ($kind === 'staff_local') {
                $subject = 'Privacy archive on server: ' . $eventName;
                $body = '<p>Live participant data for <strong>' . htmlspecialchars($eventName, ENT_QUOTES)
                    . '</strong> was deleted. The encrypted zip is on the VPS. All Father can download it from Privacy in the admin app and copy it to the office Drive folder. The zip passphrase is not in this message.</p>';
            } else {
                $url = '';
                $ev = $pdo->prepare('SELECT privacy_backup_url FROM events WHERE id = ?');
                $ev->execute([(int)$row['event_id']]);
                $url = (string)($ev->fetchColumn() ?: '');
                $subject = 'Privacy archive for ' . $eventName;
                $body = '<p>The encrypted participant archive for <strong>' . htmlspecialchars($eventName, ENT_QUOTES)
                    . '</strong> is in Drive.</p><p><a href="' . htmlspecialchars($url, ENT_QUOTES) . '">'
                    . htmlspecialchars($url !== '' ? $url : 'Open Drive', ENT_QUOTES) . '</a></p>'
                    . '<p>The zip passphrase is not in this message.</p>';
            }
            $ok = Mailer::send($to, $subject, $body, null, $fromName, $inline);
            $pdo->prepare('UPDATE privacy_notices SET status = ?, error = ?, first_name = ?, updated_at = ? WHERE id = ?')
                ->execute([
                    $ok ? 'sent' : 'failed',
                    $ok ? null : Mailer::$lastError,
                    $ok ? null : (string)($row['first_name'] ?? ''),
                    self::nowManila()->format('Y-m-d H:i:s'),
                    (int)$row['id'],
                ]);
        }
    }

    private static function sendNoticeSummary(PDO $pdo, int $jobId): void
    {
        $recipients = PrivacyNotice::summaryRecipients();
        if ($recipients === [] || $jobId <= 0) {
            return;
        }
        $nameStmt = $pdo->prepare('SELECT event_name FROM privacy_notices WHERE job_id = ? AND event_name IS NOT NULL AND event_name <> \'\' LIMIT 1');
        $nameStmt->execute([$jobId]);
        $eventName = (string)($nameStmt->fetchColumn() ?: '');
        $agg = $pdo->prepare('SELECT kind, status, COUNT(*) AS c FROM privacy_notices WHERE job_id = ? GROUP BY kind, status');
        $agg->execute([$jobId]);
        $counts = [
            'sent' => 0, 'failed' => 0, 'skipped' => 0, 'queued' => 0,
            'participant_sent' => 0, 'participant_failed' => 0, 'participant_skipped' => 0,
            'staff_sent' => 0, 'staff_failed' => 0,
        ];
        foreach ($agg->fetchAll() ?: [] as $row) {
            $kind = (string)($row['kind'] ?? '');
            $status = (string)($row['status'] ?? '');
            $c = (int)($row['c'] ?? 0);
            if (isset($counts[$status])) {
                $counts[$status] += $c;
            }
            $group = str_starts_with($kind, 'staff') ? 'staff' : 'participant';
            $key = $group . '_' . $status;
            if (isset($counts[$key])) {
                $counts[$key] += $c;
            }
        }
        $failStmt = $pdo->prepare("SELECT to_email, error, kind FROM privacy_notices WHERE job_id = ? AND status = 'failed' ORDER BY id ASC");
        $failStmt->execute([$jobId]);
        $failed = $failStmt->fetchAll() ?: [];
        $subject = PrivacyNotice::summaryEmailSubject($eventName, $counts['sent'], $counts['failed']);
        $body = PrivacyNotice::summaryEmailHtml($eventName, $counts, $failed);
        foreach ($recipients as $to) {
            Mailer::send($to, $subject, $body);
        }
    }

    private static function restoreFile(string $packDir, string $kind, string $original): ?string
    {
        $original = trim($original);
        if ($original === '') {
            return null;
        }
        $src = $packDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $kind . DIRECTORY_SEPARATOR . basename($original);
        if (!is_file($src)) {
            $abs = PrivacyArchive::absPath($original);
            return is_file($abs) ? $original : null;
        }
        $relDir = 'storage' . DIRECTORY_SEPARATOR . 'privacy-restored' . DIRECTORY_SEPARATOR . $kind;
        $absDir = PrivacyArchive::projectRoot() . DIRECTORY_SEPARATOR . $relDir;
        if (!is_dir($absDir)) {
            mkdir($absDir, 0775, true);
        }
        $name = basename($original);
        copy($src, $absDir . DIRECTORY_SEPARATOR . $name);
        return str_replace('\\', '/', $relDir . '/' . $name);
    }

    private static function rmdirDeep(string $dir): void
    {
        $items = @scandir($dir);
        if (!is_array($items)) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::rmdirDeep($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
