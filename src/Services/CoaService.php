<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Certificate of Appearance: dual-copy TCPDF layout (two identical
 * certificates on one A4 landscape sheet with a cut line), generated after
 * a signed door scan and emailed to the participant. Files are stored under
 * storage/coa/{eventId}/ which is not web-public.
 */
final class CoaService
{
    private const DEFAULT_PARTICULARS = [
        'Lodging' => 'DID NOT PROVIDE hotel/lodging',
        'Meals' => 'PROVIDED food and meals - Lunch',
        'Vehicle' => 'DID NOT PROVIDE VEHICLE',
    ];

    /** Whether the event has the CoA flow switched on. */
    public static function enabledFor(array $event): bool
    {
        return (int)($event['coa_enabled'] ?? 0) === 1;
    }

    /**
     * Generate the PDF and email it to the participant. Returns false (with
     * a recorded send row) when the event is not enabled, the participant
     * has no email, or generation/mailing failed. Never throws into the
     * scan JSON path - callers wrap it, this only logs.
     */
    public static function maybeSendFor(int $eventId, int $participantId, string $attendanceDate, ?string $fromName = null, string $source = 'auto'): bool
    {
        try {
            return self::sendNow($eventId, $participantId, $attendanceDate, $source, $fromName);
        } catch (\Throwable $e) {
            Logger::log(null, 'coa_failed', [
                'participant_id' => $participantId,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ], $eventId);
            return false;
        }
    }

    /**
     * Core generate + mail + record path. Records a coa_sends row (attached
     * to the event's batch for the attendance date) for every outcome:
     * sent, failed, or skipped (no email). $source is 'auto' (scan),
     * 'manual' (resend button, monitor send-new), or 'scheduled'. Compose
     * passes $overrides (venue/purpose/particulars/signatory/template_id)
     * so the batch snapshot does not have to mutate the event row. A future
     * $sendAt queues the row instead of sending now.
     */
    public static function sendNow(int $eventId, int $participantId, string $attendanceDate, string $source = 'manual', ?string $fromName = null, array $overrides = [], ?string $sendAt = null): bool
    {
        $pdo = Database::pdo();

        $ev = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $ev->execute([$eventId]);
        $event = $ev->fetch();
        if (!$event) {
            return false;
        }
        // Scan auto-send stays gated. Compose / schedule / resend may run
        // against a chosen template without requiring coa_enabled.
        if ($source === 'auto' && !self::enabledFor($event)) {
            return false;
        }

        $eventLike = self::applyDefaultTemplate($pdo, self::applyOverrides($event, $overrides));
        $venue = trim((string)($eventLike['coa_venue'] ?? '')) !== '' ? (string)$eventLike['coa_venue'] : 'Venue to be announced';
        $signatory = trim((string)($eventLike['coa_signatory_name'] ?? '')) !== '' ? (string)$eventLike['coa_signatory_name'] : 'Event Head';
        $templateId = (int)($overrides['template_id'] ?? 0);
        $batchId = self::ensureBatch($pdo, $eventId, $attendanceDate, (string)$event['name'], $venue, $signatory, $source, $templateId);

        $st = $pdo->prepare('SELECT id, uuid, first_name, middle_name, last_name, agency, email, office_email FROM participants WHERE id = ? AND event_id = ? LIMIT 1');
        $st->execute([$participantId, $eventId]);
        $participant = $st->fetch();
        if (!$participant) {
            return false;
        }
        $to = trim((string)($participant['email'] ?: $participant['office_email'] ?? ''));

        // Scheduled compose: queue now, the cron worker sends when due.
        if ($sendAt !== null && $sendAt !== '' && strtotime($sendAt) > time()) {
            self::recordSend($pdo, $batchId, $eventId, $participantId, $attendanceDate, $to, 'queued', '', '', (string)$event['name'], $venue, $signatory, $sendAt);
            return true;
        }

        if ($to === '') {
            self::recordSend($pdo, $batchId, $eventId, $participantId, $attendanceDate, '', 'skipped', 'No email on record', '', (string)$event['name'], $venue, $signatory);
            Logger::log(null, 'coa_skipped', ['participant_id' => $participantId, 'reason' => 'no_email'], $eventId);
            return false;
        }

        $eventLike['name'] = (string)$event['name'];
        $path = self::generateForEventLike($eventId, $eventLike, $participant, $attendanceDate);
        if ($path === null) {
            self::recordSend($pdo, $batchId, $eventId, $participantId, $attendanceDate, $to, 'failed', 'PDF generation failed (TCPDF unavailable?)', '', (string)$event['name'], $venue, $signatory);
            return false;
        }

        $dateLabel = self::dateLabel($event, $attendanceDate);
        $name = self::fullName($participant);
        $subject = 'Certificate of Appearance — ' . $dateLabel;
        $body = self::mailBody($name, $dateLabel);
        $sent = Mailer::send($to, $subject, $body, $path, $fromName ?? (string)$event['name']);
        self::recordSend($pdo, $batchId, $eventId, $participantId, $attendanceDate, $to, $sent ? 'sent' : 'failed', $sent ? '' : 'Mail send failed' . (Mailer::$lastError !== '' ? ': ' . Mailer::$lastError : ''), $path, (string)$event['name'], $venue, $signatory);
        Logger::log(null, $sent ? 'coa_sent' : 'coa_mail_failed', [
            'participant_id' => $participantId,
            'to' => $to,
            'pdf' => basename($path),
        ], $eventId);
        return $sent;
    }

    /**
     * Plan#12 compose: queue (or send, when $sendAt is now/past) a list of
     * participants against a template snapshot. Returns [processed, sent].
     * @param list<int> $participantIds
     * @return array{processed:int,sent:int}
     */
    public static function sendBatch(int $eventId, array $participantIds, string $attendanceDate, array $overrides, string $source, ?string $sendAt = null, ?string $fromName = null): array
    {
        $processed = 0;
        $sent = 0;
        foreach (array_slice($participantIds, 0, 50) as $pid) {
            $ok = self::sendNow($eventId, (int)$pid, $attendanceDate, $source, $fromName, $overrides, $sendAt);
            $processed++;
            if ($ok) $sent++;
        }
        return ['processed' => $processed, 'sent' => $sent];
    }

    /**
     * Cron / CLI: send due queued rows (send_at null or in the past).
     * @return array{processed:int,sent:int}
     */
    public static function processDue(int $limit = 50): array
    {
        $pdo = Database::pdo();
        $limit = max(1, min(50, $limit));
        $stmt = $pdo->prepare(
            "SELECT id FROM coa_sends WHERE status = 'queued' AND (send_at IS NULL OR send_at <= ?) ORDER BY id ASC LIMIT {$limit}"
        );
        $stmt->execute([date('Y-m-d H:i:s')]);
        $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));
        $sent = 0;
        foreach ($ids as $id) {
            if (self::resendRow($id)) {
                $sent++;
            }
        }
        return ['processed' => count($ids), 'sent' => $sent];
    }

    /** Merge compose/template overrides onto an event row (generation view). */
    private static function applyOverrides(array $event, array $overrides): array
    {
        $eventLike = $event;
        $map = [
            'venue' => 'coa_venue',
            'purpose' => 'coa_purpose',
            'particulars' => 'coa_particulars',
            'signatory_name' => 'coa_signatory_name',
            'signatory_title' => 'coa_signatory_title',
            'signatory_path' => 'coa_signatory_path',
            'logo_path' => 'coa_logo_path',
        ];
        foreach ($map as $key => $column) {
            if (array_key_exists($key, $overrides) && trim((string)$overrides[$key]) !== '') {
                $eventLike[$column] = trim((string)$overrides[$key]);
            }
        }
        return $eventLike;
    }

    /** Resolve a stored relative path against the project root. */
    public static function resolvePath(string $path): string
    {
        if ($path === '') return '';
        if (preg_match('#^([A-Za-z]:)?[/\\\\]#', $path) === 1) {
            return $path; // already absolute
        }
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Official header art from Resource/. Near-black backgrounds are cleared
     * so the marks sit on the white certificate instead of a black box.
     */
    private static function officialLogo(string $filename): string
    {
        $src = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Resource' . DIRECTORY_SEPARATOR . $filename;
        if (!is_file($src)) {
            return '';
        }
        if (!function_exists('imagecreatefrompng')) {
            return $src;
        }
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'coa' . DIRECTORY_SEPARATOR . 'brand';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $src;
        }
        $cache = $dir . DIRECTORY_SEPARATOR . $filename;
        if (is_file($cache) && filemtime($cache) >= filemtime($src)) {
            return $cache;
        }
        $im = @imagecreatefrompng($src);
        if ($im === false) {
            return $src;
        }
        $w = imagesx($im);
        $h = imagesy($im);
        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        $clear = imagecolorallocatealpha($out, 0, 0, 0, 127);
        imagefill($out, 0, 0, $clear);
        for ($yy = 0; $yy < $h; $yy++) {
            for ($xx = 0; $xx < $w; $xx++) {
                $c = imagecolorsforindex($im, imagecolorat($im, $xx, $yy));
                if (($c['alpha'] ?? 0) > 100) {
                    continue;
                }
                $r = (int)$c['red'];
                $g = (int)$c['green'];
                $b = (int)$c['blue'];
                if ($r < 28 && $g < 28 && $b < 28) {
                    continue;
                }
                $color = imagecolorallocatealpha($out, $r, $g, $b, 0);
                imagesetpixel($out, $xx, $yy, $color);
            }
        }
        imagepng($out, $cache);
        imagedestroy($im);
        imagedestroy($out);
        return is_file($cache) ? $cache : $src;
    }

    /** The batch for this event + attendance date, created on demand.
     *  Plan#13: scheduled composes never reuse a batch - each send_at run is
     *  its own batch so waiting vs failed stays readable. Auto and manual
     *  sends still share the per-date batch. */
    private static function ensureBatch(\PDO $pdo, int $eventId, string $attendanceDate, string $eventName, string $venue, string $signatory, string $source, int $templateId = 0): int
    {
        if ($source !== 'scheduled') {
            $sel = $pdo->prepare('SELECT id FROM coa_batches WHERE event_id = ? AND inclusive_date = ? AND source = ? ORDER BY id DESC LIMIT 1');
            $sel->execute([$eventId, $attendanceDate, $source]);
            $existing = $sel->fetchColumn();
            if ($existing !== false && $existing !== null) {
                return (int)$existing;
            }
        }
        $ins = $pdo->prepare('INSERT INTO coa_batches (event_id, created_at, inclusive_date, signatory_name, venue_snapshot, event_name_snapshot, source, template_id) VALUES (?,?,?,?,?,?,?,?)');
        $ins->execute([$eventId, date('Y-m-d H:i:s'), $attendanceDate, $signatory, $venue, $eventName, $source, $templateId > 0 ? $templateId : null]);
        return (int)$pdo->lastInsertId();
    }

    private static function recordSend(\PDO $pdo, int $batchId, int $eventId, int $participantId, string $attendanceDate, string $email, string $status, string $error, string $pdfPath, string $eventName, string $venue, string $signatory, ?string $sendAt = null): void
    {
        $now = date('Y-m-d H:i:s');
        $ins = $pdo->prepare('INSERT INTO coa_sends (batch_id, event_id, participant_id, attendance_date, email, status, error, pdf_path, event_name_snapshot, venue_snapshot, signatory_snapshot, send_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $ins->execute([
            $batchId, $eventId, $participantId, $attendanceDate, $email, $status,
            $error !== '' ? mb_substr($error, 0, 255) : null,
            $pdfPath !== '' ? $pdfPath : null,
            $eventName, $venue, $signatory,
            $sendAt, $now, $now,
        ]);
    }

    /**
     * Render the dual-copy PDF and return the stored path, or null when
     * TCPDF is unavailable or the rows are missing.
     */
    public static function generate(int $eventId, int $participantId, string $attendanceDate): ?string
    {
        if (!class_exists('TCPDF')) {
            $autoload = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
            }
        }
        if (!class_exists('TCPDF')) {
            Logger::log(null, 'coa_skipped', ['participant_id' => $participantId, 'reason' => 'no_tcpdf'], $eventId);
            return null;
        }

        $pdo = Database::pdo();
        $ev = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $ev->execute([$eventId]);
        $event = $ev->fetch();
        $st = $pdo->prepare('SELECT id, first_name, middle_name, last_name, agency FROM participants WHERE id = ? AND event_id = ? LIMIT 1');
        $st->execute([$participantId, $eventId]);
        $participant = $st->fetch();
        if (!$event || !$participant) {
            return null;
        }
        return self::generateForEventLike($eventId, self::applyDefaultTemplate($pdo, $event), $participant, $attendanceDate);
    }

    /** Render into the event's storage folder from an event-like settings array. */
    public static function generateForEventLike(int $eventId, array $eventLike, array $participant, string $attendanceDate): ?string
    {
        if (!class_exists('TCPDF')) {
            $autoload = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
            }
        }
        if (!class_exists('TCPDF')) {
            return null;
        }

        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'coa' . DIRECTORY_SEPARATOR . $eventId;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }
        $fileName = 'coa_' . (int)$participant['id'] . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.pdf';
        $path = $dir . DIRECTORY_SEPARATOR . $fileName;

        $data = self::buildData($eventLike, self::fullName($participant), trim((string)($participant['agency'] ?? '')), $attendanceDate);
        return self::renderPdf($data, $path) ? $path : null;
    }

    /**
     * Plan#11: render a sample certificate from an event-like settings array
     * (event row, template row, or inline overrides) with a placeholder
     * participant, for the All Father preview. Never sends.
     */
    public static function generatePreview(array $eventLike, string $attendanceDate): ?string
    {
        if (!class_exists('TCPDF')) {
            $autoload = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
            }
        }
        if (!class_exists('TCPDF')) {
            return null;
        }
        $data = self::buildData($eventLike, 'Juan Dela Cruz (Sample)', 'Sample Agency', $attendanceDate);
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'coa' . DIRECTORY_SEPARATOR . 'preview';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }
        $path = $dir . DIRECTORY_SEPARATOR . 'preview_logos_' . md5(serialize($data)) . '.pdf';
        if (is_file($path)) {
            return $path; // Same content: reuse the rendered file.
        }
        return self::renderPdf($data, $path) ? $path : null;
    }

    /** Certificate fields from an event/template-like row plus participant bits. */
    private static function buildData(array $eventLike, string $name, string $agency, string $attendanceDate): array
    {
        return [
            'name' => $name,
            'agency' => $agency,
            'eventName' => (string)($eventLike['name'] ?? 'Event'),
            'venue' => trim((string)($eventLike['coa_venue'] ?? '')) !== '' ? (string)$eventLike['coa_venue'] : 'Venue to be announced',
            'purpose' => trim((string)($eventLike['coa_purpose'] ?? '')),
            'particulars' => self::particulars($eventLike),
            'appearanceDate' => self::dateLabel($eventLike, $attendanceDate),
            'issueDate' => self::issueDateLabel($eventLike, $attendanceDate),
            'signatoryName' => trim((string)($eventLike['coa_signatory_name'] ?? '')) !== '' ? (string)$eventLike['coa_signatory_name'] : 'Event Head',
            'signatoryTitle' => trim((string)($eventLike['coa_signatory_title'] ?? '')) !== '' ? (string)$eventLike['coa_signatory_title'] : 'Event Lead',
            'signatoryPath' => trim((string)($eventLike['coa_signatory_path'] ?? '')),
            'logoPath' => trim((string)($eventLike['coa_logo_path'] ?? '')),
        ];
    }

    private static function renderPdf(array $data, string $path): bool
    {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('GovNet-Launching');
        $pdf->SetTitle('Certificate of Appearance - ' . $data['name']);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage('L', 'A4');

        $pageW = 297.0;
        $pageH = 210.0;
        $half = $pageW / 2.0;

        // Cut line down the middle.
        $pdf->SetLineStyle(['width' => 0.2, 'dash' => '2,2', 'color' => [150, 150, 150]]);
        $pdf->Line($half, 6, $half, $pageH - 6);

        for ($copy = 0; $copy < 2; $copy++) {
            $x0 = $copy * $half;
            self::renderCopy($pdf, $x0, $half, $pageH, $data);
        }

        $pdf->Output($path, 'F');
        return is_file($path);
    }

    /** One certificate copy inside the given half of the sheet. */
    private static function renderCopy(\TCPDF $pdf, float $x0, float $half, float $pageH, array $data): void
    {
        $m = 10.0;                 // side margin inside the copy
        $inner = $half - $m * 2;   // usable width
        $y = 8.0;

        // Header: DICT mark on the left, Bagong Pilipinas on the right.
        $dictLogo = self::officialLogo('DICT-Logo-Final-2-300x153.png');
        $bagongLogo = self::officialLogo('Bagong_Pilipinas_logo.png');
        if ($dictLogo !== '') {
            $pdf->Image($dictLogo, $x0 + $m, $y, 34, 0, 'PNG', '', '', true, 300);
        }
        if ($bagongLogo !== '') {
            $pdf->Image($bagongLogo, $x0 + $half - $m - 20, $y, 20, 0, 'PNG', '', '', true, 300);
        }

        // Agency line + title.
        $pdf->SetTextColor(31, 41, 51);
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetXY($x0 + $m, $y + 20);
        $pdf->Cell($inner, 4, 'Republic of the Philippines', 0, 0, 'C');
        $pdf->SetXY($x0 + $m, $y + 25);
        $pdf->Cell($inner, 4, 'DEPARTMENT OF INFORMATION AND COMMUNICATIONS TECHNOLOGY', 0, 0, 'C');
        $pdf->SetXY($x0 + $m, $y + 30);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($inner, 4, 'Region II', 0, 0, 'C');

        $y += 42;
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->SetTextColor(11, 27, 69);
        $pdf->SetXY($x0 + $m, $y);
        $pdf->Cell($inner, 8, 'CERTIFICATE OF APPEARANCE', 0, 0, 'C');
        $y += 12;

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(31, 41, 51);
        $text = 'This is to certify that ' . $data['name']
            . ($data['agency'] !== '' ? ' (' . $data['agency'] . ')' : '')
            . ' appeared in ' . $data['eventName']
            . ' held at ' . $data['venue'] . ' on ' . $data['appearanceDate'] . '.'
            . ' This certification is issued upon request to attest to the fact and duration of the appearance.';
        $text = trim(preg_replace('/\s+/', ' ', $text));
        $pdf->SetXY($x0 + $m, $y);
        $pdf->writeHTMLCell($inner, 0, $x0 + $m, $y, '<p style="text-align:center; line-height:160%;">'
            . htmlspecialchars($text, ENT_QUOTES) . '</p>', 0, 0, false, true, 'C');
        $y += 26;

        // Particulars table.
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY($x0 + $m, $y);
        $pdf->Cell($inner, 5, 'Particulars:', 0, 0, 'L');
        $y += 6;
        $rowH = 6.5;
        $labelW = $inner * 0.28;
        foreach ($data['particulars'] as $label => $value) {
            $pdf->SetXY($x0 + $m, $y);
            $pdf->SetFillColor(244, 246, 248);
            $pdf->Cell($labelW, $rowH, $label, 1, 0, 'L', true);
            $pdf->Cell($inner - $labelW, $rowH, $value, 1, 0, 'L');
            $y += $rowH;
        }
        $y += 6;

        // Issue date + signatory block.
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY($x0 + $m, $y);
        $pdf->Cell($inner, 5, 'Issued this ' . $data['issueDate'] . ' at ' . $data['venue'] . '.', 0, 0, 'L');

        $sigY = max($y + 22, $pageH - 52);
        $signatoryImagePath = self::resolvePath($data['signatoryPath']);
        if ($signatoryImagePath !== '' && is_file($signatoryImagePath)) {
            $pdf->Image($signatoryImagePath, $x0 + $half - $m - 52, $sigY - 14, 44, 0, '', '', '', true, 300);
        }
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY($x0 + $half - $m - 62, $sigY + 12);
        $pdf->Cell(62, 5, $data['signatoryName'], 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetXY($x0 + $half - $m - 62, $sigY + 17.5);
        $pdf->Cell(62, 5, $data['signatoryTitle'], 0, 0, 'C');
        $pdf->SetLineStyle(['width' => 0.25, 'color' => [31, 41, 51]]);
        $pdf->Line($x0 + $half - $m - 52, $sigY + 11.5, $x0 + $half - $m - 10, $sigY + 11.5);

        // Footer.
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(120, 128, 138);
        $pdf->SetXY($x0 + $m, $pageH - 12);
        $pdf->Cell($inner, 4, 'Department of Information and Communications Technology - Region II', 0, 0, 'C');
    }

    /** @return array<string,string> ordered particulars rows with defaults applied. */
    private static function particulars(array $event): array
    {
        $raw = trim((string)($event['coa_particulars'] ?? ''));
        if ($raw === '') {
            return self::DEFAULT_PARTICULARS;
        }
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (preg_match('/^([^:\-]+)\s*[-:]\s*(.+)$/', $line, $m) === 1) {
                $rows[trim($m[1])] = trim($m[2]);
            } else {
                $rows[$line] = '';
            }
        }
        return $rows ?: self::DEFAULT_PARTICULARS;
    }

    /**
     * Plan#11: regenerate and mail one queued coa_sends row (monitor resend).
     * Updates the row's status, error, and pdf_path.
     */
    public static function resendRow(int $sendId): bool
    {
        $pdo = Database::pdo();
        $sel = $pdo->prepare('SELECT * FROM coa_sends WHERE id = ? LIMIT 1');
        $sel->execute([$sendId]);
        $row = $sel->fetch();
        if (!$row || ($row['status'] ?? '') !== 'queued') {
            return false;
        }

        // Plan#13: rebuild from the batch snapshot - the scheduled compose's
        // template wins over the current event CoA columns.
        $eventLike = self::eventLikeForSend($pdo, $row);

        $st = $pdo->prepare('SELECT id, first_name, middle_name, last_name, agency FROM participants WHERE id = ? AND event_id = ? LIMIT 1');
        $st->execute([(int)$row['participant_id'], (int)$row['event_id']]);
        $participant = $st->fetch();
        $path = $participant ? self::generateForEventLike((int)$row['event_id'], $eventLike, $participant, (string)$row['attendance_date']) : null;

        $to = trim((string)($row['email'] ?? ''));
        $sent = false;
        if ($path !== null && $to !== '') {
            $fromName = (string)($eventLike['name'] ?? '');
            $dateLabel = self::dateLabel($eventLike, (string)$row['attendance_date']);
            $body = self::mailBody(self::fullName($participant), $dateLabel);
            $sent = Mailer::send($to, 'Certificate of Appearance — ' . $dateLabel, $body, $path, $fromName !== '' ? $fromName : null);
        }
        $reason = $sent ? '' : ($to === '' ? 'No email on record' : ($path === null ? 'PDF generation failed' : ('Mail send failed' . (Mailer::$lastError !== '' ? ': ' . Mailer::$lastError : ''))));
        $upd = $pdo->prepare("UPDATE coa_sends SET status = ?, error = ?, pdf_path = COALESCE(?, pdf_path), updated_at = ? WHERE id = ?");
        $upd->execute([
            $sent ? 'sent' : 'failed',
            mb_substr($reason, 0, 255),
            $path,
            date('Y-m-d H:i:s'),
            $sendId,
        ]);
        Logger::log(null, $sent ? 'coa_queued_sent' : 'coa_queued_failed', ['send_id' => $sendId, 'error' => $reason], (int)$row['event_id']);
        return $sent;
    }

    /**
     * Plan#13: settings used to rebuild a queued send's PDF. Priority:
     * batch snapshots (venue, signatory, event name) > batch template
     * (purpose, particulars, signature, logo) > event CoA columns.
     * Returns the overrides keyed for generateForEventLike via applyOverrides.
     */
    public static function sendOverridesFor(int $sendId): array
    {
        $pdo = Database::pdo();
        $sel = $pdo->prepare('SELECT s.*, b.template_id, b.venue_snapshot, b.signatory_name AS signatory_snapshot, b.event_name_snapshot FROM coa_sends s JOIN coa_batches b ON b.id = s.batch_id WHERE s.id = ? LIMIT 1');
        $sel->execute([$sendId]);
        $row = $sel->fetch();
        if (!$row) {
            return [];
        }
        $overrides = [
            'venue' => (string)($row['venue_snapshot'] ?? ''),
            'signatory_name' => (string)($row['signatory_name'] ?? ''),
        ];
        $templateId = (int)($row['template_id'] ?? 0);
        if ($templateId > 0) {
            $stmt = $pdo->prepare('SELECT purpose, particulars, signatory_path, logo_path FROM coa_templates WHERE id = ? LIMIT 1');
            $stmt->execute([$templateId]);
            $tpl = $stmt->fetch();
            if ($tpl) {
                $overrides['purpose'] = (string)($tpl['purpose'] ?? '');
                $overrides['particulars'] = (string)($tpl['particulars'] ?? '');
                $overrides['signatory_path'] = (string)($tpl['signatory_path'] ?? '');
                $overrides['logo_path'] = (string)($tpl['logo_path'] ?? '');
            }
        }
        return $overrides;
    }

    /** Event-like row for regeneration: event columns + batch/template snapshot overrides. */
    private static function eventLikeForSend(\PDO $pdo, array $row): array
    {
        $ev = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $ev->execute([(int)$row['event_id']]);
        $eventLike = $ev->fetch() ?: [];
        $overrides = self::sendOverridesFor((int)$row['id']);
        if (isset($overrides['venue']) && $overrides['venue'] !== '') {
            $eventLike['coa_venue'] = $overrides['venue'];
        }
        if (isset($overrides['signatory_name']) && $overrides['signatory_name'] !== '') {
            $eventLike['coa_signatory_name'] = $overrides['signatory_name'];
        }
        foreach (['purpose', 'particulars', 'signatory_path', 'logo_path'] as $key) {
            if (array_key_exists($key, $overrides)) {
                $col = 'coa_' . $key;
                $eventLike[$col] = $overrides[$key];
            }
        }
        if (isset($overrides['venue']) || isset($overrides['signatory_name'])) {
            $eventLike['name'] = (string)($row['event_name_snapshot'] ?? ($eventLike['name'] ?? 'Event'));
        }
        return $eventLike;
    }

    /** Plan#11: move failed rows into the resend queue; returns the count moved. */
    public static function queueFailed(?int $eventId): int
    {
        $pdo = Database::pdo();
        $sql = "UPDATE coa_sends SET status = 'queued', updated_at = ? WHERE status = 'failed'";
        $params = [date('Y-m-d H:i:s')];
        if ($eventId !== null && $eventId > 0) {
            $sql .= ' AND event_id = ?';
            $params[] = $eventId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    private static function fullName(array $participant): string
    {
        $first = trim((string)($participant['first_name'] ?? ''));
        $middle = trim((string)($participant['middle_name'] ?? ''));
        $last = trim((string)($participant['last_name'] ?? ''));
        $initial = $middle !== '' ? mb_strtoupper(mb_substr($middle, 0, 1)) . '.' : '';
        return trim(preg_replace('/\s+/', ' ', $first . ' ' . $initial . ' ' . $last));
    }

    /** Appearance span when set (September 22-24, 2026), otherwise the attendance day. */
    private static function dateLabel(array $event, string $attendanceDate): string
    {
        [$start, $end] = self::dateRange($event, $attendanceDate);
        if (date('Y-m-d', $start) === date('Y-m-d', $end)) {
            return date('F j, Y', $start);
        }
        if (date('F Y', $start) === date('F Y', $end)) {
            return date('F j', $start) . '-' . date('j, Y', $end);
        }
        if (date('Y', $start) === date('Y', $end)) {
            return date('F j', $start) . ' - ' . date('F j, Y', $end);
        }
        return date('F j, Y', $start) . ' - ' . date('F j, Y', $end);
    }

    /** Issued this… line: explicit picker, else the last day of the range. */
    private static function issueDateLabel(array $event, string $attendanceDate): string
    {
        $picked = strtotime((string)($event['coa_issue_date'] ?? ''));
        if ($picked !== false) {
            return date('F j, Y', $picked);
        }
        [, $end] = self::dateRange($event, $attendanceDate);
        return date('F j, Y', $end);
    }

    /** @return array{0:int,1:int} unix start and end */
    private static function dateRange(array $event, string $attendanceDate): array
    {
        $start = strtotime((string)($event['coa_date_from'] ?? ''));
        $end = strtotime((string)($event['coa_date_to'] ?? ''));
        if ($start === false) {
            $start = strtotime($attendanceDate) ?: time();
            $end = $start;
        } elseif ($end === false || $end < $start) {
            $end = $start;
        }
        return [$start, $end];
    }

    private static function mailBody(string $name, string $dateLabel): string
    {
        $who = $name !== '' ? $name : 'participant';
        return '<p>Dear ' . htmlspecialchars($who, ENT_QUOTES) . ',</p>'
            . '<p>Please find attached your Certificate of Appearance for ' . htmlspecialchars($dateLabel, ENT_QUOTES) . '.</p>'
            . '<p>This certification is issued upon request to attest to the fact and duration of your appearance.</p>'
            . '<p>Thank you.</p>';
    }

    public static function ensureDefaultTemplate(): void
    {
        self::applyDefaultTemplate(Database::pdo(), ['coa_purpose' => '', 'coa_particulars' => '']);
    }

    /**
     * The DICT notice is the template used when an event has not set its own
     * certificate wording. Inserted once, then reused.
     */
    private static function applyDefaultTemplate(\PDO $pdo, array $eventLike): array
    {
        $needsPurpose = trim((string)($eventLike['coa_purpose'] ?? '')) === '';
        $needsParticulars = trim((string)($eventLike['coa_particulars'] ?? '')) === '';
        if (!$needsPurpose && !$needsParticulars) {
            return $eventLike;
        }
        if (!self::tableReady($pdo)) {
            return $eventLike;
        }
        $particulars = "Lodging - DID NOT PROVIDE hotel/lodging\nMeals - PROVIDED food and meals - Lunch\nVehicle - DID NOT PROVIDE VEHICLE";
        $existing = $pdo->query("SELECT purpose, particulars FROM coa_templates WHERE name = 'Certificate of Appearance' ORDER BY id ASC LIMIT 1")->fetch();
        if (!$existing && self::tableReady($pdo)) {
            $now = date('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO coa_templates (name, venue, purpose, particulars, signatory_name, signatory_title, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)')
                ->execute(['Certificate of Appearance', '', 'to attest to the fact and duration of their appearance', $particulars, '', '', $now, $now]);
            $existing = ['purpose' => 'to attest to the fact and duration of their appearance', 'particulars' => $particulars];
        }
        if ($needsPurpose && trim((string)($existing['purpose'] ?? '')) !== '') {
            $eventLike['coa_purpose'] = (string)$existing['purpose'];
        }
        if ($needsParticulars && trim((string)($existing['particulars'] ?? '')) !== '') {
            $eventLike['coa_particulars'] = (string)$existing['particulars'];
        }
        return $eventLike;
    }

    private static function tableReady(\PDO $pdo): bool
    {
        try {
            $pdo->query('SELECT 1 FROM coa_templates LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
