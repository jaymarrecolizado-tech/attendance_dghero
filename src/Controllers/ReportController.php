<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ResolvesEventContext;
use App\Services\AuthService;
use App\Services\CoaService;
use App\Services\Database;
use App\Services\EventContext;
use App\Services\ReportPdf;

class ReportController
{
    use ResolvesEventContext;

    public function form(): void
    {
        $pdo = Database::pdo();
        $event = $this->requireEventContext($pdo, ['event_admin']);
        if (!$event) return;
        $pdfAvailable = class_exists('TCPDF') || class_exists('\\TCPDF');
        if (!$pdfAvailable && is_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
            require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            $pdfAvailable = class_exists('TCPDF') || class_exists('\\TCPDF');
        }

        $tpl = [];
        $reportNotice = null;
        // Plan#16 builder defaults so a first click already matches the AI
        // Roadshow sample: event name + venue + day line, sample field set.
        $defaultSubtitle = implode("\n", ReportPdf::subtitleLines(
            $event,
            ReportPdf::dayLine($event, '', '', '')
        ));
        $defaultFields = ReportPdf::defaultFields();
        $fieldLabels = ReportPdf::fieldLabels();
        try {
            // Ensure templates table exists even when auto-migrate is off.
            $exists = $pdo->query("SHOW TABLES LIKE 'report_templates'")->fetch();
            if (!$exists) {
                $sqlFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '004_report_templates.sql';
                if (is_file($sqlFile)) {
                    $pdo->exec((string)file_get_contents($sqlFile));
                }
            }
            $stmt = $pdo->prepare('SELECT id, name FROM report_templates WHERE admin_id = ? ORDER BY id DESC');
            $stmt->execute([(int)$_SESSION['admin_id']]);
            $tpl = $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log('Report form templates error: ' . $e->getMessage());
            $reportNotice = 'Report templates are temporarily unavailable. You can still generate reports.';
            $tpl = [];
        }

        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin_report.php';
    }

    public function generate(): void
    {
        if (!$this->requireLogin()) return;
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check($_POST['csrf'])) { http_response_code(400); echo 'Invalid CSRF'; return; }
        $date = trim((string)($_POST['date'] ?? ''));
        $title = trim((string)($_POST['title'] ?? 'Attendance Report'));
        // Normalize CRLF textareas so "operator kept the prefill" compares
        // cleanly against the LF-only builder default.
        $subtitle = trim(str_replace("\r\n", "\n", (string)($_POST['subtitle'] ?? '')));
        $fields = (array)($_POST['fields'] ?? []);
        $format = trim((string)($_POST['format'] ?? 'auto'));
        $download = ((string)($_POST['download'] ?? '0')) === '1';
        $start = trim((string)($_POST['start_date'] ?? ''));
        $end = trim((string)($_POST['end_date'] ?? ''));
        $leftLogoPath = null; $rightLogoPath = null;
        foreach (['left_logo'=>'leftLogoPath','right_logo'=>'rightLogoPath'] as $key=>$var) {
            if (isset($_FILES[$key]) && is_uploaded_file($_FILES[$key]['tmp_name'])) {
                $type = mime_content_type($_FILES[$key]['tmp_name']);
                if (!in_array($type, ['image/png','image/jpeg'])) continue;
                $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . 'logos';
                if (!is_dir($dir)) mkdir($dir, 0775, true);
                $ext = $type === 'image/png' ? 'png' : 'jpg';
                $dest = $dir . DIRECTORY_SEPARATOR . (time().'_'.bin2hex(random_bytes(4))).'.'.$ext;
                move_uploaded_file($_FILES[$key]['tmp_name'], $dest);
                $$var = $dest;
            }
        }
        $pdo = Database::pdo();
        $event = $this->requireEventContext($pdo, ['event_admin']);
        if (!$event) return;
        $eventId = (int)$event['id'];
        $where=['a.event_id = ?','p.event_id = ?'];$bind=[$eventId, $eventId];
        if ($date !== '') { $where[]='a.attendance_date = ?'; $bind[]=$date; }
        if ($start !== '' && $end !== '') { $where[]='a.attendance_date BETWEEN ? AND ?'; $bind[]=$start; $bind[]=$end; }
        $sqlWhere = $where?('WHERE '.implode(' AND ',$where)) : '';
        $stmt = $pdo->prepare("SELECT a.id,a.signature_path,a.attendance_date,a.time_in,p.uuid,p.first_name,p.middle_name,p.last_name,p.agency,p.designation,p.email,p.sex,p.contact_no,p.sector FROM attendance a JOIN participants p ON p.id=a.participant_id $sqlWhere ORDER BY a.id ASC");
        $stmt->execute($bind);
        $rows = $stmt->fetchAll();

        $labels = ReportPdf::fieldLabels();
        $fields = array_values(array_unique((array)$fields));
        $fields = array_values(array_filter($fields, static fn ($f) => is_string($f) && isset($labels[$f])));
        if ($fields === []) {
            $fields = ReportPdf::defaultFields();
        }

        // Plan#16 default chrome from the current event. The untouched
        // builder prefill (or an empty subtitle) re-derives from the chosen
        // date so the day line is never stale; any edit is an override.
        $dayLine = ReportPdf::dayLine($event, $date, $start, $end);
        $prefillSubtitle = implode("\n", ReportPdf::subtitleLines(
            $event,
            ReportPdf::dayLine($event, '', '', '')
        ));
        if ($subtitle === '' || $subtitle === $prefillSubtitle) {
            $subtitleLines = ReportPdf::subtitleLines($event, $dayLine);
        } else {
            $subtitleLines = array_values(array_filter(array_map('trim', explode("\n", $subtitle)), static fn ($v) => $v !== ''));
        }
        $leftLogoPath = $leftLogoPath ?? ReportPdf::markPath('DICT-Logo-Final-2-300x153.png');
        $rightLogoPath = $rightLogoPath ?? ReportPdf::markPath('Bagong_Pilipinas_logo.png');

        // Check if TCPDF is available via composer autoload
        $pdfAvailable = false;
        if (class_exists('\\TCPDF')) {
            $pdfAvailable = true;
        } elseif (class_exists('TCPDF')) {
            $pdfAvailable = true;
        } elseif (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
            // Try to ensure autoload is loaded
            require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            $pdfAvailable = class_exists('\\TCPDF') || class_exists('TCPDF');
        }
        $usePdf = ($format === 'pdf' && $pdfAvailable) || ($format === 'auto' && $pdfAvailable);

        // Convert newlines to <br> for HTML display
        $titleHtml = nl2br(htmlspecialchars($title, ENT_QUOTES, 'UTF-8'));
        $subtitleHtml = $subtitleLines !== [] ? nl2br(htmlspecialchars(implode("\n", $subtitleLines), ENT_QUOTES, 'UTF-8')) : '';

        $pdfError = null;
        if ($usePdf) {
            try {
                ReportPdf::stream($rows, $fields, [
                    'title' => $title !== '' ? $title : 'Attendance Report',
                    'subtitleLines' => $subtitleLines,
                    'leftLogo' => (string)$leftLogoPath,
                    'rightLogo' => (string)$rightLogoPath,
                    'download' => $download,
                ]);
                return;
            } catch (\Throwable $e) {
                // Fallback to HTML if PDF fails
                $usePdf = false;
                $pdfError = 'PDF generation failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                error_log('Attendance report PDF error: ' . $e->getMessage());
            }
        }
        header('Content-Type: text/html; charset=UTF-8');
        $titleEscaped = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $styleBlock = '<style>@page{size:landscape;margin:0}@media print{body{width:100%;}}</style>';
        echo '<!doctype html><html><head><meta charset="utf-8"><title>'.$titleEscaped.'</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'.$styleBlock.'</head><body class="p-3">';
        if (isset($pdfError)) {
            echo '<div class="alert alert-warning"><strong>Note:</strong> '.$pdfError.' Displaying as HTML instead.</div>';
        }
        echo '<table width="100%"><tr>';
        echo '<td width="20%">'.($leftLogoPath?('<img src="data:image/'.($this->extOf($leftLogoPath)).';base64,'.base64_encode(file_get_contents($leftLogoPath)).'" style="height:60px">'):'').'</td>';
        echo '<td width="60%" class="text-center"><h2>'.$titleHtml.'</h2>'.($subtitleHtml!==''?('<div>'.$subtitleHtml.'</div>'):'').'</td>';
        echo '<td width="20%" class="text-end">'.($rightLogoPath?('<img src="data:image/'.($this->extOf($rightLogoPath)).';base64,'.base64_encode(file_get_contents($rightLogoPath)).'" style="height:60px">'):'').'</td>';
        echo '</tr></table>';
        echo '<h4>Registered Guest List'.(count($rows) > 0 ? ' <span class="text-muted small">'.count($rows).' record'.(count($rows) === 1 ? '' : 's').'</span>' : '').'</h4>';
        if (empty($rows)) {
            echo '<p><em>No attendance records found for the selected criteria.</em></p>';
        } else {
            echo '<table class="table table-sm table-bordered"><thead><tr>';
            $map = $this->fieldMap(); foreach ($fields as $f) { if (isset($map[$f])) echo '<th>'.$map[$f].'</th>'; } echo '<th>Signature</th></tr></thead><tbody>';
            $rowNo = 0;
            foreach ($rows as $r) {
                $rowNo++;
                echo '<tr>'; foreach ($fields as $f) { echo '<td>'.$this->val($f,$r,$rowNo).'</td>'; }
                $sigPath = CoaService::resolvePath((string)($r['signature_path'] ?? ''));
                $b64 = '';
                if ($sigPath !== '' && is_file($sigPath)) { $b64 = base64_encode(file_get_contents($sigPath)); }
                $imgTag = $b64 !== '' ? ('<img src="data:image/png;base64,'.$b64.'" style="height:40px">') : '';
                echo '<td>'.$imgTag.'</td></tr>';
            }
            echo '</tbody></table>';
            echo '<div class="text-end text-muted small mt-3">Page 1 / 1</div>';
        }
        echo '</body></html>';
    }

    private function fieldMap(): array
    {
        return ReportPdf::fieldLabels();
    }

    private function val(string $f, array $r, int $rowNumber = 0): string
    {
        return htmlspecialchars(ReportPdf::cellValue($f, $r, $rowNumber), ENT_QUOTES, 'UTF-8');
    }
    private function extOf(string $path): string {
        $lower = strtolower($path);
        return (substr($lower, -4) === '.png') ? 'png' : 'jpeg';
    }

    public function saveTemplate(): void
    {
        if (!$this->requireLogin()) return;
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check($_POST['csrf'])) { http_response_code(400); echo 'Invalid CSRF'; return; }
        $name = trim((string)($_POST['tpl_name'] ?? 'Untitled'));
        $config = [
            'title' => (string)($_POST['title'] ?? ''),
            'subtitle' => (string)($_POST['subtitle'] ?? ''),
            'date' => (string)($_POST['date'] ?? ''),
            'start_date' => (string)($_POST['start_date'] ?? ''),
            'end_date' => (string)($_POST['end_date'] ?? ''),
            'fields' => (array)($_POST['fields'] ?? []),
            'format' => (string)($_POST['format'] ?? 'auto'),
        ];
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO report_templates (admin_id,name,config) VALUES (?,?,?)');
        $stmt->execute([(int)$_SESSION['admin_id'],$name,json_encode($config)]);
        header('Location: ?r=admin_report');
        if (function_exists('csrf_rotate')) csrf_rotate();
    }

    public function loadTemplate(): void
    {
        if (!$this->requireLogin()) return;
        $id = (int)($_GET['tpl_id'] ?? 0);
        $pdo = Database::pdo();
        $tpl = $pdo->prepare('SELECT config FROM report_templates WHERE id=? AND admin_id=?');
        $tpl->execute([$id,(int)$_SESSION['admin_id']]);
        $row = $tpl->fetch();
        header('Content-Type: application/json');
        echo $row ? $row['config'] : json_encode(['error'=>'not_found']);
    }
}