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
     * a logged reason) when the event is not enabled, the participant has
     * no email, or generation/mailing failed. Never throws into the scan
     * JSON path - callers wrap it, this only logs.
     */
    public static function maybeSendFor(int $eventId, int $participantId, string $attendanceDate, ?string $fromName = null): bool
    {
        try {
            return self::send($eventId, $participantId, $attendanceDate, $fromName);
        } catch (\Throwable $e) {
            Logger::log(null, 'coa_failed', [
                'participant_id' => $participantId,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ], $eventId);
            return false;
        }
    }

    private static function send(int $eventId, int $participantId, string $attendanceDate, ?string $fromName): bool
    {
        $pdo = Database::pdo();

        $ev = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $ev->execute([$eventId]);
        $event = $ev->fetch();
        if (!$event || !self::enabledFor($event)) {
            return false;
        }

        $st = $pdo->prepare('SELECT id, uuid, first_name, middle_name, last_name, agency, email, office_email FROM participants WHERE id = ? AND event_id = ? LIMIT 1');
        $st->execute([$participantId, $eventId]);
        $participant = $st->fetch();
        if (!$participant) {
            return false;
        }
        $to = trim((string)($participant['email'] ?: $participant['office_email'] ?? ''));
        if ($to === '') {
            Logger::log(null, 'coa_skipped', ['participant_id' => $participantId, 'reason' => 'no_email'], $eventId);
            return false;
        }

        $path = self::generate($eventId, $participantId, $attendanceDate);
        if ($path === null) {
            return false;
        }

        $dateLong = date('F j, Y', strtotime($attendanceDate));
        $name = self::fullName($participant);
        $subject = 'Certificate of Appearance - ' . $dateLong;
        $body = '<p>Dear ' . htmlspecialchars($name, ENT_QUOTES) . ',</p>'
            . '<p>Please find attached your Certificate of Appearance for <strong>' . htmlspecialchars($dateLong, ENT_QUOTES) . '</strong>.</p>'
            . '<p>Thank you for participating in ' . htmlspecialchars((string)$event['name'], ENT_QUOTES) . '.</p>';
        $sent = Mailer::send($to, $subject, $body, $path, $fromName ?? (string)$event['name']);
        Logger::log(null, $sent ? 'coa_sent' : 'coa_mail_failed', [
            'participant_id' => $participantId,
            'to' => $to,
            'pdf' => basename($path),
        ], $eventId);
        return $sent;
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

        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'coa' . DIRECTORY_SEPARATOR . $eventId;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }
        $fileName = 'coa_' . $participantId . '_' . date('Ymd', strtotime($attendanceDate)) . '.pdf';
        $path = $dir . DIRECTORY_SEPARATOR . $fileName;

        $data = [
            'name' => self::fullName($participant),
            'agency' => trim((string)($participant['agency'] ?? '')),
            'eventName' => (string)$event['name'],
            'venue' => trim((string)($event['coa_venue'] ?? '')) !== '' ? (string)$event['coa_venue'] : 'Venue to be announced',
            'purpose' => trim((string)($event['coa_purpose'] ?? '')),
            'particulars' => self::particulars($event),
            'issueDate' => date('F j, Y', strtotime($attendanceDate)),
            'signatoryName' => trim((string)($event['coa_signatory_name'] ?? '')) !== '' ? (string)$event['coa_signatory_name'] : 'Event Head',
            'signatoryTitle' => trim((string)($event['coa_signatory_title'] ?? '')) !== '' ? (string)$event['coa_signatory_title'] : 'Event Lead',
            'signatoryPath' => trim((string)($event['coa_signatory_path'] ?? '')),
            'logoPath' => trim((string)($event['coa_logo_path'] ?? '')),
        ];

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
        return is_file($path) ? $path : null;
    }

    /** One certificate copy inside the given half of the sheet. */
    private static function renderCopy(\TCPDF $pdf, float $x0, float $half, float $pageH, array $data): void
    {
        $m = 10.0;                 // side margin inside the copy
        $inner = $half - $m * 2;   // usable width
        $y = 12.0;

        // Header: DICT emblem left, Bagong Pilipinas right.
        if ($data['logoPath'] !== '' && is_file($data['logoPath'])) {
            $pdf->Image($data['logoPath'], $x0 + $m, $y, 26, 0, '', '', '', true, 300);
        } else {
            $pdf->SetLineStyle(['width' => 0.4, 'color' => [11, 27, 69]]);
            $pdf->Rect($x0 + $m, $y, 22, 14, 'D');
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(11, 27, 69);
            $pdf->SetXY($x0 + $m, $y + 4);
            $pdf->Cell(22, 6, 'DICT', 0, 0, 'C');
        }
        $pdf->SetFont('helvetica', 'BI', 12);
        $pdf->SetTextColor(206, 17, 38);
        $pdf->SetXY($x0 + $half - $m - 46, $y + 3);
        $pdf->Cell(46, 8, 'Bagong Pilipinas', 0, 0, 'R');

        // Agency line + title.
        $pdf->SetTextColor(31, 41, 51);
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetXY($x0 + $m, $y + 16);
        $pdf->Cell($inner, 4, 'Republic of the Philippines', 0, 0, 'C');
        $pdf->SetXY($x0 + $m, $y + 21);
        $pdf->Cell($inner, 4, 'DEPARTMENT OF INFORMATION AND COMMUNICATIONS TECHNOLOGY', 0, 0, 'C');
        $pdf->SetXY($x0 + $m, $y + 26);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($inner, 4, 'Region II', 0, 0, 'C');

        $y += 36;
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->SetTextColor(11, 27, 69);
        $pdf->SetXY($x0 + $m, $y);
        $pdf->Cell($inner, 8, 'CERTIFICATE OF APPEARANCE', 0, 0, 'C');
        $y += 12;

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(31, 41, 51);
        $text = 'This is to certify that ' . $data['name']
            . ($data['agency'] !== '' ? ' (' . $data['agency'] . ')' : '')
            . ' appeared and participated in ' . $data['eventName']
            . ' held at ' . $data['venue'] . ' on ' . $data['issueDate']
            . ($data['purpose'] !== '' ? ', ' . $data['purpose'] : '') . '.';
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
        if ($data['signatoryPath'] !== '' && is_file($data['signatoryPath'])) {
            $pdf->Image($data['signatoryPath'], $x0 + $half - $m - 52, $sigY - 14, 44, 0, '', '', '', true, 300);
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

    private static function fullName(array $participant): string
    {
        return trim(implode(' ', array_filter([
            (string)($participant['first_name'] ?? ''),
            (string)($participant['middle_name'] ?? ''),
            (string)($participant['last_name'] ?? ''),
        ], static function (string $part): bool {
            return $part !== '';
        })));
    }
}
