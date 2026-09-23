<?php
declare(strict_types=1);

namespace App\Services;

// The TCPDF page subclass below needs its parent loaded at declare time.
if (!class_exists('\\TCPDF') && is_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}

/**
 * Plan#16: Attendance Report PDF that matches the AI Roadshow guest-list
 * sample (Resource/DAY-2-JULY-24-2026-AI-ROADSHOW-2026 (2).pdf). Landscape
 * A4, DICT + Bagong Pilipinas marks flanking a centered header stack on
 * every page, a thick+thin rule, "Registered Guest List" + record count on
 * page 1 only, a navy column-header band, borderless zebra rows with the
 * ink signature always in the last column, and a real footer (generated
 * time in Asia/Manila + Page X of Y). Painted with TCPDF cells, not one
 * writeHTML table.
 */
final class ReportPdf
{
    private const NAVY = [26, 54, 93];      // #1A365D title stack + header band
    private const GRAY = [55, 65, 81];      // #374151 venue / day line
    private const SLATE = [100, 116, 139];  // #64748B record count + footer
    private const STRIPE = [248, 250, 252]; // #F8FAFC odd row fill

    private const MARGIN = 12.0;  // 34pt side margins from the sample
    private const TOP = 38.5;     // content starts below the header rule
    private const BOTTOM = 15.0;  // footer zone
    private const BAND_H = 6.0;   // 17pt column header band
    private const ROW_H = 12.0;   // 34pt fixed row pitch
    private const LINE_H = 3.6;   // 8pt cell line height in mm

    /** Builder field order + labels; Signature is appended by the painter. */
    public static function fieldLabels(): array
    {
        return [
            'id' => 'No.',
            'name' => 'Name',
            'agency' => 'Agency/Org.',
            'sector' => 'Sector',
            'designation' => 'Designation',
            'email' => 'Email',
            'sex' => 'Gender',
            'time_in' => 'Time In',
            'registered_at' => 'Registered At',
        ];
    }

    /** Default checked set in the builder: the sample column order. */
    public static function defaultFields(): array
    {
        return ['id', 'name', 'agency', 'sector', 'designation', 'email', 'sex'];
    }

    /** Cell text for one field. Name = first + middle initial + last (same as CoA). */
    public static function cellValue(string $field, array $row, int $rowNumber = 0): string
    {
        switch ($field) {
            case 'id':
                return $rowNumber > 0 ? (string)$rowNumber : (string)$row['id'];
            case 'name':
                $name = trim((string)($row['first_name'] ?? ''));
                $middle = trim((string)($row['middle_name'] ?? ''));
                if ($middle !== '') {
                    $name .= ' ' . strtoupper(substr($middle, 0, 1)) . '.';
                }
                $name .= ' ' . trim((string)($row['last_name'] ?? ''));
                return trim($name);
            case 'time_in':
                $t = (string)($row['time_in'] ?? '');
                $ts = strtotime($t);
                return $ts ? date('g:i A', $ts) : $t;
            case 'registered_at':
                return (string)($row['attendance_date'] ?? '');
            case 'agency':
            case 'designation':
            case 'email':
            case 'sector':
            case 'sex':
                return (string)($row[$field] ?? '');
        }
        return '';
    }

    /**
     * "Day 2 - July 24, 2026" from the chosen single date (day number
     * counted from the event start), a "F j, Y - F j, Y" span for a range,
     * or the event start date when nothing was picked.
     */
    public static function dayLine(array $event, string $date, string $start, string $end): string
    {
        if ($start !== '' && $end !== '') {
            $s = strtotime($start);
            $e = strtotime($end);
            if ($s && $e) {
                return date('F j, Y', $s) . ' - ' . date('F j, Y', $e);
            }
            return $start . ' - ' . $end;
        }
        $day = $date !== '' ? $date : (string)($event['starts_at'] ?? '');
        $ts = $day !== '' ? strtotime(substr($day, 0, 10)) : false;
        if (!$ts) {
            return '';
        }
        $eventStart = strtotime(substr((string)($event['starts_at'] ?? ''), 0, 10));
        if ($date !== '' && $eventStart && $ts >= $eventStart) {
            $n = (int)floor(($ts - $eventStart) / 86400) + 1;
            return 'Day ' . $n . ' - ' . date('F j, Y', $ts);
        }
        return date('F j, Y', $ts);
    }

    /**
     * Official header mark: the background-cleared copy CoA caches under
     * storage/coa/brand when present, else the raw Resource file.
     */
    public static function markPath(string $filename): string
    {
        $root = dirname(__DIR__, 2);
        $cache = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'coa'
            . DIRECTORY_SEPARATOR . 'brand' . DIRECTORY_SEPARATOR . $filename;
        if (is_file($cache)) {
            return $cache;
        }
        $src = $root . DIRECTORY_SEPARATOR . 'Resource' . DIRECTORY_SEPARATOR . $filename;
        return is_file($src) ? $src : '';
    }

    /**
     * Derive the subtitle stack the sample shows: event name, venue, day
     * line. An empty venue simply drops that line.
     * @return list<string>
     */
    public static function subtitleLines(array $event, string $dayLine): array
    {
        $lines = [trim((string)($event['name'] ?? ''))];
        $venue = trim((string)($event['coa_venue'] ?? ''));
        if ($venue !== '') {
            $lines[] = $venue;
        }
        if ($dayLine !== '') {
            $lines[] = $dayLine;
        }
        return array_values(array_filter($lines, static fn ($l) => $l !== ''));
    }

    /**
     * Paint and stream the report.
     *
     * @param list<array<string,mixed>> $rows attendance+participant rows
     * @param list<string> $fields chosen field keys (signature appended last)
     * @param array{title:string,subtitleLines:list<string>,leftLogo:string,rightLogo:string,download:bool} $opts
     */
    public static function stream(array $rows, array $fields, array $opts): void
    {
        if (!class_exists('\\TCPDF') && is_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
            require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        }
        if (!class_exists('\\TCPDF')) {
            throw new \RuntimeException('TCPDF not available');
        }

        $labels = self::fieldLabels();
        $fields = array_values(array_filter($fields, static fn ($f) => isset($labels[$f])));
        if ($fields === []) {
            $fields = self::defaultFields();
        }

        $pdf = new ReportPdfPage('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->rpTitle = (string)($opts['title'] ?? 'Attendance Report');
        $pdf->rpSubtitleLines = array_values(array_filter((array)($opts['subtitleLines'] ?? []), 'is_string'));
        $pdf->rpLeftLogo = (string)($opts['leftLogo'] ?? '');
        $pdf->rpRightLogo = (string)($opts['rightLogo'] ?? '');
        $pdf->SetCreator('ISSP Solo');
        $pdf->SetAuthor('ISSP Solo');
        $pdf->SetTitle($pdf->rpTitle);
        $pdf->SetSubject('Attendance Report');
        $pdf->SetMargins(self::MARGIN, self::TOP, self::MARGIN);
        $pdf->SetAutoPageBreak(true, self::BOTTOM);
        $pdf->SetCellPadding(1.1);
        if (defined('PDF_IMAGE_SCALE_RATIO')) {
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        } else {
            $pdf->setImageScale(1.25);
        }

        $pdf->AddPage();

        $usable = $pdf->getPageWidth() - 2 * self::MARGIN;
        $colW = $usable / (count($fields) + 1); // + signature column

        // Page-1 only: guest list heading + record count.
        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 5.2, 'Registered Guest List', 0, 1, 'L');
        if ($rows === []) {
            $pdf->SetFont('helvetica', 'I', 10);
            $pdf->SetTextColor(...self::GRAY);
            $pdf->Cell(0, 6, 'No attendance records found for the selected criteria.', 0, 1, 'L');
            $pdf->Output('attendance_report.pdf', !empty($opts['download']) ? 'D' : 'I');
            return;
        }
        $pdf->SetTextColor(...self::SLATE);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 3.6, count($rows) . ' record' . (count($rows) === 1 ? '' : 's'), 0, 1, 'L');
        $pdf->Ln(1.4);

        self::drawColumnHeaders($pdf, $fields, $colW, $usable);

        $breakAt = $pdf->getPageHeight() - self::BOTTOM - 0.5;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            // Measure wrap needs first so the page break uses the real height.
            $cellTexts = [];
            $maxLines = 1;
            foreach ($fields as $f) {
                $txt = self::cellValue($f, $row, $n);
                $cellTexts[$f] = $txt;
                $lines = $pdf->getNumLines($txt, $colW - 2.2, true, false);
                if ($lines > $maxLines) {
                    $maxLines = $lines;
                }
            }
            $h = max(self::ROW_H, $maxLines * self::LINE_H + 1.6);

            if ($pdf->GetY() + $h > $breakAt && $pdf->GetY() > self::TOP) {
                $pdf->AddPage();
                self::drawColumnHeaders($pdf, $fields, $colW, $usable);
            }

            $y = $pdf->GetY();
            if ($n % 2 === 1) {
                $pdf->SetFillColor(...self::STRIPE);
                $pdf->Rect(self::MARGIN, $y, $usable, $h, 'F');
            }

            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(0, 0, 0);
            $x = self::MARGIN;
            foreach ($fields as $f) {
                $lines = $pdf->getNumLines($cellTexts[$f], $colW - 2.2, true, false);
                $pdf->SetXY($x + 1.1, $y + max(0.6, ($h - $lines * self::LINE_H) / 2));
                $pdf->MultiCell($colW - 2.2, self::LINE_H, $cellTexts[$f], 0, self::cellAlign($f), false, 0, '', '', true, 0, false, false);
                $x += $colW;
            }

            $sig = CoaService::resolvePath((string)($row['signature_path'] ?? ''));
            if ($sig !== '' && is_file($sig)) {
                $dim = @getimagesize($sig);
                if ($dim && $dim[0] > 0 && $dim[1] > 0) {
                    $ar = $dim[0] / $dim[1];
                    $ih = min($h - 2.2, 8.4);
                    $iw = $ih * $ar;
                    if ($iw > $colW - 3) {
                        $iw = $colW - 3;
                        $ih = $iw / $ar;
                    }
                    $pdf->Image(
                        $sig,
                        self::MARGIN + $usable - $colW + ($colW - $iw) / 2,
                        $y + ($h - $ih) / 2,
                        $iw,
                        $ih,
                        '',
                        '',
                        '',
                        false,
                        300,
                        '',
                        false,
                        false,
                        0
                    );
                }
            }

            $pdf->SetY($y + $h, true);
        }

        $pdf->Output('attendance_report.pdf', !empty($opts['download']) ? 'D' : 'I');
    }

    private static function cellAlign(string $field): string
    {
        // No. and Gender sit centered in the sample; text columns are left.
        return ($field === 'id' || $field === 'sex' || $field === 'time_in') ? 'C' : 'L';
    }

    private static function drawColumnHeaders(\TCPDF $pdf, array $fields, float $colW, float $usable): void
    {
        $labels = self::fieldLabels();
        $y = $pdf->GetY();
        $pdf->SetFillColor(26, 54, 93);
        $pdf->Rect(self::MARGIN, $y, $usable, self::BAND_H, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetXY(self::MARGIN, $y);
        foreach ($fields as $f) {
            $pdf->Cell($colW, self::BAND_H, $labels[$f], 0, 0, self::cellAlign($f));
        }
        $pdf->Cell($colW, self::BAND_H, 'Signature', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY($y + self::BAND_H, true);
    }
}

/**
 * TCPDF page with the sample chrome: centered header stack + flanking marks
 * + thick/thin rule on every page, and a real footer (thin rule, generated
 * timestamp, Page X of Y) that replaces the default TCPDF footer. Declared
 * only when TCPDF is installed so the builder page still loads without it.
 */
if (class_exists('\\TCPDF')) {
    class ReportPdfPage extends \TCPDF
    {
    private const NAVY = [26, 54, 93];
    private const GRAY = [55, 65, 81];
    private const SLATE = [100, 116, 139];
    private const RULE = [203, 213, 225]; // #CBD5E1

    private const MARGIN = 12.0;

    /** @var list<string> */
    public array $rpSubtitleLines = [];
    public string $rpTitle = 'Attendance Report';
    public string $rpLeftLogo = '';
    public string $rpRightLogo = '';

    public function Header(): void
    {
        $left = self::MARGIN;
        $right = $this->getPageWidth() - self::MARGIN;

        // Marks flank the centered text (45pt tall in the sample).
        $this->drawMark($this->rpLeftLogo, $left, 8.1, 'L');
        $this->drawMark($this->rpRightLogo, $right, 8.1, 'R');

        // Centered stack: title, event name (bold, navy), then gray 9pt lines.
        $this->SetCellPadding(0);
        $this->SetY(5.2);
        $this->SetX($left);
        $this->SetTextColor(...self::NAVY);
        $this->SetFont('helvetica', 'B', 13);
        $this->Cell(0, 4.6, $this->rpTitle, 0, 1, 'C');

        $first = true;
        foreach ($this->rpSubtitleLines as $line) {
            $this->SetX($left);
            if ($first) {
                $this->SetTextColor(...self::NAVY);
                $this->SetFont('helvetica', 'B', 13);
                $this->Cell(0, 4.6, $line, 0, 1, 'C');
                $first = false;
                continue;
            }
            $this->SetTextColor(...self::GRAY);
            $this->SetFont('helvetica', '', 9);
            $this->MultiCell(0, 3.4, $line, 0, 'C', false, 1, '', '', true, 0, false, false);
        }

        // Thick + thin closing rule under the header block.
        $this->SetDrawColor(...self::NAVY);
        $this->SetLineWidth(0.45);
        $this->Line($left, 32.3, $right, 32.3);
        $this->SetLineWidth(0.2);
        $this->Line($left, 33.1, $right, 33.1);
        $this->SetCellPadding(1.1);
    }

    public function Footer(): void
    {
        $left = self::MARGIN;
        $right = $this->getPageWidth() - self::MARGIN;
        $this->SetDrawColor(...self::RULE);
        $this->SetLineWidth(0.2);
        $this->Line($left, $this->getPageHeight() - 14.0, $right, $this->getPageHeight() - 14.0);
        $this->SetY(-12.4);
        $this->SetX($left);
        $this->SetCellPadding(0);
        $this->SetTextColor(...self::SLATE);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 4, 'Generated ' . date('M j, Y g:i A'), 0, 0, 'L');
        $this->Cell(0, 4, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
    }

    private function drawMark(string $path, float $edge, float $y, string $side): void
    {
        if ($path === '' || !is_file($path)) {
            return;
        }
        $dim = @getimagesize($path);
        if (!$dim || $dim[0] <= 0 || $dim[1] <= 0) {
            return;
        }
        $h = 16.0;
        $w = $h * ($dim[0] / $dim[1]);
        $x = $side === 'L' ? $edge : $edge - $w;
        $this->Image($path, $x, $y, $w, $h, '', '', '', false, 300, '', false, false, 0);
    }
    }
}
