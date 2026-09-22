<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;
use App\Services\EventContext;
use App\Services\Uuid;
use App\Services\QrService;
use App\Services\Mailer;
use App\Services\ParticipantValidator;
use App\Services\RateLimiter;

class RegisterController
{
    private const SEXES = ['Female','Male','Other'];
    private const SECTORS = [
        'National Government Agency',
        'Local Government Unit',
        'Provincial Government Unit',
        'GOCCs',
        'State Universities and Colleges',
        'Water District',
    ];

    public function show(): void
    {
        $pdo = \App\Services\Database::pdo();
        $slug = trim((string)($_GET['e'] ?? ''));
        $flash = $_SESSION['register_flash'] ?? null;
        unset($_SESSION['register_flash']);
        $posted = $flash['fields'] ?? [];
        if ($slug === '') {
            $events = EventContext::openEvents($pdo);
            $mode = 'register';
            require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'public_event_picker.php';
            return;
        }
        $event = EventContext::findBySlug($pdo, $slug);
        if (!$event) { http_response_code(404); echo 'Event not found'; return; }
        if (!EventContext::isPublicOpen($event)) { http_response_code(403); echo 'Registration is closed for this event'; return; }
        $eventId = (int)$event['id'];
        $eventTheme = EventContext::themeFor($event);
        $agencies = $pdo->query("SELECT DISTINCT agency FROM participants WHERE event_id = {$eventId} AND agency IS NOT NULL AND agency <> '' ORDER BY agency ASC LIMIT 500")->fetchAll();
        $designations = $pdo->query("SELECT DISTINCT designation FROM participants WHERE event_id = {$eventId} AND designation IS NOT NULL AND designation <> '' ORDER BY designation ASC LIMIT 500")->fetchAll();
        $sexes = self::SEXES;
        $sectors = self::SECTORS;
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'register.php';
    }

    public function success(): void
    {
        $uuid = isset($_GET['uuid']) ? (string)$_GET['uuid'] : '';
        if ($uuid === '') { http_response_code(400); echo 'Missing UUID'; return; }
        $pdo = \App\Services\Database::pdo();
        $stmt = $pdo->prepare('SELECT uuid, first_name, last_name, event_id FROM participants WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo 'Not Found'; return; }
        $slug = trim((string)($_GET['e'] ?? ''));
        $event = null;
        if ($slug !== '') {
            $event = EventContext::findBySlug($pdo, $slug);
            if (!$event || (int)$row['event_id'] !== (int)$event['id']) { http_response_code(404); echo 'Not Found'; return; }
        }
        $participant = [
            'uuid' => $row['uuid'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
        ];
        if (!isset($_SESSION['qr_allowed'])) $_SESSION['qr_allowed'] = [];
        $_SESSION['qr_allowed'][$uuid] = true;
        $eventTheme = $event ? EventContext::themeFor($event) : [];
        $eventGate = false;
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'register_success.php';
    }

    public function submit(): void
    {
        $slug = trim((string)($_POST['e'] ?? $_GET['e'] ?? ''));
        if (!isset($_POST['csrf']) || !function_exists('csrf_check') || !csrf_check($_POST['csrf'])) {
            $this->flashRegisterError(400, $slug, 'Your security token expired. Please try again - your answers are kept.');
            return;
        }

        $pdo = Database::pdo();
        $event = $slug !== '' ? EventContext::findBySlug($pdo, $slug) : null;
        if (!$event) {
            $this->flashRegisterError(400, $slug, 'This registration link does not point to a valid event.');
            return;
        }
        if (!EventContext::isPublicOpen($event)) {
            $this->flashRegisterError(403, $slug, 'Registration is closed for this event.');
            return;
        }
        $eventId = (int)$event['id'];

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!RateLimiter::allow('register:' . $ip, 10, 300)) {
            $this->flashRegisterError(429, $slug, 'Too many registration attempts. Please try again in a few minutes.');
            return;
        }

        $agencySelected = $_POST['agency_select'] ?? ($_POST['agency'] ?? '');
        $designationSelected = $_POST['designation_select'] ?? ($_POST['designation'] ?? '');
        $agency = $this->resolveCustomSelect($agencySelected, $_POST['agency_other'] ?? '');
        $designation = $this->resolveCustomSelect($designationSelected, $_POST['designation_other'] ?? '');
        $sex = $this->allowList($_POST['sex'] ?? '', self::SEXES);
        $sector = $this->allowList($_POST['sector'] ?? '', self::SECTORS, allowCustom: true);

        $validation = ParticipantValidator::validateForRegistration([
            'first_name' => $_POST['first_name'] ?? '',
            'middle_name' => $_POST['middle_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'agency' => $agency,
            'sector' => $sector,
            'nickname' => $_POST['nickname'] ?? '',
            'sex' => $sex,
            'designation' => $designation,
            'office_email' => $_POST['office_email'] ?? '',
            'contact_no' => $_POST['contact_no'] ?? '',
        ]);

        $clean = $validation['data'];
        $errors = $validation['errors'];
        if ($errors) {
            $this->flashRegisterError(422, $slug, 'Please fix the highlighted fields.', $errors);
            return;
        }

        $pdo = Database::pdo();
        try {
            if (ParticipantValidator::emailTaken($pdo, $eventId, (string)$clean['email'])) {
                $this->flashRegisterError(409, $slug, 'This email is already registered for this event. Use a different address.');
                return;
            }
            $attempts = 0;
            $max = 5;
            $uuid = '';
            while ($attempts < $max) {
                $uuid = Uuid::v4();
                try {
                    $stmt = $pdo->prepare('INSERT INTO participants (event_id, uuid,email,first_name,middle_name,last_name,nickname,sex,sector,agency,designation,office_email,contact_no,qr_path) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([
                        $eventId,
                        $uuid,
                        $clean['email'],
                        $clean['first_name'],
                        $clean['middle_name'],
                        $clean['last_name'],
                        $clean['nickname'],
                        $clean['sex'],
                        $clean['sector'],
                        $clean['agency'],
                        $clean['designation'],
                        $clean['office_email'],
                        $clean['contact_no'],
                        null,
                    ]);
                    break;
                } catch (\PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw $e;
                    }
                    $attempts++;
                    if ($attempts >= $max) throw $e;
                }
            }
            $payload = 'PART|' . $uuid;
            $qrPath = QrService::generate($payload, $uuid);
            $up = $pdo->prepare('UPDATE participants SET qr_path=? WHERE uuid=?');
            $up->execute([$qrPath, $uuid]);
        } catch (\PDOException $e) {
            $duplicate = $e->getCode() === '23000' || str_contains($e->getMessage(), 'uq_participants_event_email');
            $this->flashRegisterError(
                $duplicate ? 409 : 500,
                $slug,
                $duplicate ? 'This email is already registered for this event. Use a different address.' : 'Registration failed'
            );
            return;
        }

        $contactEmail = $clean['email'] ?? $clean['office_email'] ?? '';
        $to = $contactEmail ?: '';
        if ($to !== '') {
            $subject = 'Your registration QR code';
            $body = '<p>Thank you for registering.</p><p>Please find your QR attached or available on the confirmation page.</p>';
            $sent = Mailer::send($to, $subject, $body, $qrPath);
            \App\Services\Logger::log(null, $sent ? 'email_sent' : 'email_failed', ['to'=>$to]);
        }

        if (!isset($_SESSION['qr_allowed'])) $_SESSION['qr_allowed'] = [];
        $_SESSION['qr_allowed'][$uuid] = true;
        if (function_exists('csrf_rotate')) {
            csrf_rotate();
        }
        header('Location: ?r=register_success&uuid=' . urlencode($uuid) . '&e=' . urlencode($slug));
        exit;
    }

    /**
     * Flash the submitted answers back and render the error page with a
     * retry link. One shape for every submit failure (CSRF, missing or
     * closed event, rate limit, validation, duplicate email, server error)
     * so show() can always rehydrate slug + fields.
     */
    private function flashRegisterError(int $code, string $slug, string $error, array $errors = []): void
    {
        http_response_code($code);
        $themeEvent = $slug !== '' ? EventContext::findBySlug(Database::pdo(), $slug) : null;
        $eventTheme = EventContext::themeFor($themeEvent);
        $eventGate = false;
        $_SESSION['register_flash'] = ['slug' => $slug, 'fields' => [
            'first_name' => $_POST['first_name'] ?? '',
            'middle_name' => $_POST['middle_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'nickname' => $_POST['nickname'] ?? '',
            'email' => $_POST['email'] ?? '',
            'agency_select' => $_POST['agency_select'] ?? ($_POST['agency'] ?? ''),
            'agency_other' => $_POST['agency_other'] ?? '',
            'designation_select' => $_POST['designation_select'] ?? ($_POST['designation'] ?? ''),
            'designation_other' => $_POST['designation_other'] ?? '',
            'office_email' => $_POST['office_email'] ?? '',
            'contact_no' => $_POST['contact_no'] ?? '',
            'sex' => $this->allowList($_POST['sex'] ?? '', self::SEXES),
            'sector' => $this->allowList($_POST['sector'] ?? '', self::SECTORS, allowCustom: true),
        ], 'errors' => $errors, 'error' => $error];
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'register_error.php';
    }

    private function resolveCustomSelect(string $selected, string $other): string
    {
        $selected = trim($selected);
        $other = trim($other);
        if ($selected === 'other') {
            return $other;
        }
        return $selected;
    }

    private function allowList(string $value, array $allowed, bool $allowCustom = false): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (in_array($value, $allowed, true)) {
            return $value;
        }
        return $allowCustom ? $value : '';
    }
}