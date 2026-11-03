<?php
declare(strict_types=1);

namespace App\Services;

/** Plan#18: Data Privacy Act of 2012 notice shown before registration. */
final class PrivacyNotice
{
    public const VERSION = '2026-10-31';

    public static function dpoEmail(): string
    {
        $dpo = trim((string)(getenv('PRIVACY_DPO_EMAIL') ?: ''));
        if ($dpo !== '') {
            return $dpo;
        }
        $support = trim((string)(getenv('SUPPORT_EMAIL') ?: ''));
        return $support !== '' ? $support : 'dictro2@dict.gov.ph';
    }

    public static function title(): string
    {
        return 'Data Privacy Notice — Event Registration';
    }

    public static function checkboxLabel(): string
    {
        return 'I have read this Data Privacy Notice. I consent to DICT Region 2 processing my personal data for this event, including attendance, QR and email notices, Certificate of Appearance if used, 15-day live retention after the event, then deletion from the live system with an organizational archive as described.';
    }

    /** @return list<array{heading:string,body:string}> */
    public static function sections(): array
    {
        $dpo = self::dpoEmail();
        return [
            [
                'heading' => 'Personal information controller',
                'body' => 'The Department of Information and Communications Technology — Regional Office II (DICT Region 2) is the personal information controller for this registration system under Republic Act No. 10173 (Data Privacy Act of 2012) and its Implementing Rules and Regulations.',
            ],
            [
                'heading' => 'What we collect',
                'body' => 'When you register and attend, we process: full name, nickname, sex, personal email, office email, sector, agency or office, designation, contact number, event attendance dates and times, handwritten or captured signature, QR check-in identifier, and Certificate of Appearance records (including generated PDFs sent to your email).',
            ],
            [
                'heading' => 'Why we process it',
                'body' => 'We use this information only to: (1) register you for the named event; (2) verify identity and record attendance; (3) issue a Certificate of Appearance when that event uses CoA; (4) contact you about this event (QR, schedule, certificate); (5) keep an organizational archive after the live copy is deleted, as described below. We do not sell your data. We do not use it for unrelated profiling or public marketing.',
            ],
            [
                'heading' => 'Lawful basis',
                'body' => 'Processing is based on your consent given by agreeing to this notice, and on DICT’s mandate to conduct ICT capacity-building and related official events. You may refuse; you then cannot complete registration because we cannot run attendance or CoA without these fields.',
            ],
            [
                'heading' => 'Who sees it',
                'body' => 'Authorized DICT Region 2 All Father administrators, assigned event administrators, and checkers for this event only. Email is sent through DICT’s configured mail account. After retention, an encrypted archive may be stored in a restricted DICT Google Drive folder for organizational and audit use. We do not post your roster publicly.',
            ],
            [
                'heading' => 'Retention',
                'body' => 'Live participant records stay in this system until 15 calendar days after the event end date (Asia/Manila). That is the retention period for the operational database.',
            ],
            [
                'heading' => 'Deletion and archive',
                'body' => 'When that period lapses we: (1) copy your event records into an encrypted backup for DICT; (2) try to store that backup in the official Drive folder; (3) delete your personal data from the live event database and associated files (QR, signatures, CoA PDFs); (4) email you that your data for this event has been deleted from the live system. The event name may remain. The organizational archive is not the live register; it is kept only as long as DICT needs it for audit, CoA disputes, or legal holds, then it should be destroyed under DICT records rules.',
            ],
            [
                'heading' => 'Your rights',
                'body' => 'You may request access, correction, or withdrawal of consent / erasure before the 15-day deletion, subject to DPA limitations. To exercise rights, email ' . $dpo . '. You may complain to the National Privacy Commission.',
            ],
            [
                'heading' => 'Contact',
                'body' => 'Data protection inquiries: DICT Region 2, ' . $dpo . '. This notice is version ' . self::VERSION . '.',
            ],
        ];
    }

    /** Banner, then logo, then the gate mark. Relative paths resolve from the app root. */
    public static function registrationPhotoPath(array $event): ?string
    {
        foreach (['banner_path', 'logo_path'] as $col) {
            $resolved = self::resolveAppFile((string)($event[$col] ?? ''));
            if ($resolved !== null) {
                return $resolved;
            }
        }
        if (trim((string)($event['theme_layout'] ?? '')) === 'gate') {
            return self::resolveAppFile('assets/hack4gov-door-logo.png');
        }
        return null;
    }

    public static function registrationPhotoPreviewUrl(array $event): string
    {
        $eventId = (int)($event['id'] ?? 0);
        if ($eventId > 0 && self::resolveAppFile((string)($event['banner_path'] ?? '')) !== null) {
            return '?r=event_branding&eid=' . $eventId . '&f=banner';
        }
        if ($eventId > 0 && self::resolveAppFile((string)($event['logo_path'] ?? '')) !== null) {
            return '?r=event_branding&eid=' . $eventId . '&f=logo';
        }
        if (trim((string)($event['theme_layout'] ?? '')) === 'gate' && self::resolveAppFile('assets/hack4gov-door-logo.png') !== null) {
            return 'assets/hack4gov-door-logo.png';
        }
        return '';
    }

    public static function publicBaseUrl(): string
    {
        $url = rtrim(trim((string)(getenv('APP_URL') ?: '')), '/');
        return $url !== '' ? $url : 'https://digitalhero.dictr2.cloud';
    }

    /** Absolute HTTPS URL for the registration photo in email (Gmail cannot inline CID reliably). */
    public static function registrationPhotoMailUrl(array $event): string
    {
        $rel = self::registrationPhotoPreviewUrl($event);
        if ($rel === '') {
            return '';
        }
        if (str_starts_with($rel, 'http://') || str_starts_with($rel, 'https://')) {
            return $rel;
        }
        return self::publicBaseUrl() . '/' . ltrim($rel, '/');
    }

    /**
     * Participant deletion email. $photoSrc is cid:event-photo in mail, or a URL in the admin preview.
     * Use "placeholder" to show the photo slot without a file.
     */
    public static function deletionEmailHtml(string $firstName, string $eventName, ?string $photoSrc = null): string
    {
        $firstName = trim($firstName);
        $eventName = trim($eventName);
        $greeting = $firstName !== ''
            ? 'Hi ' . htmlspecialchars($firstName, ENT_QUOTES) . ','
            : 'Hello,';
        $eventBit = $eventName !== '' ? htmlspecialchars($eventName, ENT_QUOTES) : 'the event you attended';
        $photo = '';
        if ($photoSrc === 'placeholder') {
            $photo = '<div style="background:#e8eef7;border:1px dashed #94a3b8;color:#475569;text-align:center;padding:28px 12px;margin:0 0 16px;font-size:13px">Registration photo for this event</div>';
        } elseif ($photoSrc !== null && trim($photoSrc) !== '') {
            $photo = '<img src="' . htmlspecialchars(trim($photoSrc), ENT_QUOTES)
                . '" alt="" width="560" style="display:block;width:100%;max-width:560px;height:auto;border:0;margin:0 0 16px">';
        }
        return $photo
            . '<p>' . $greeting . '</p>'
            . '<p>Thank you for participating in <strong>' . $eventBit . '</strong>.</p>'
            . '<p>In line with the Data Privacy Act of 2012, personal data for this event is kept in the live system for 15 days after the event ends. That period has ended, so your registration, attendance, signature, QR, and certificate records for this event have been <strong>deleted from the live database</strong>.</p>'
            . '<p>If you have questions, contact DICT Region 2.</p>';
    }

    /** @return list<string> */
    public static function summaryRecipients(): array
    {
        $raw = trim((string)(getenv('PRIVACY_SUMMARY_TO') ?: 'jaymar.recolizado@dict.gov.ph'));
        $out = [];
        foreach (explode(',', $raw) as $to) {
            $to = trim($to);
            if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL) !== false) {
                $out[] = $to;
            }
        }
        return $out;
    }

    /**
     * @param array{sent?:int,failed?:int,skipped?:int,queued?:int,participant_sent?:int,participant_failed?:int,participant_skipped?:int,staff_sent?:int,staff_failed?:int} $counts
     * @param list<array{to_email?:string,error?:string,kind?:string}> $failed
     */
    public static function summaryEmailHtml(string $eventName, array $counts, array $failed): string
    {
        $eventBit = $eventName !== '' ? htmlspecialchars($eventName, ENT_QUOTES) : 'this event';
        $n = static function (string $key) use ($counts): int {
            return (int)($counts[$key] ?? 0);
        };
        $html = '<p>Deletion notices for <strong>' . $eventBit . '</strong> have finished sending.</p>'
            . '<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;font-size:14px">'
            . '<tr style="background:#f1f5f9"><th align="left">Type</th><th>Sent</th><th>Failed</th><th>Skipped</th></tr>'
            . '<tr><td>Participants</td><td align="right">' . $n('participant_sent') . '</td><td align="right">' . $n('participant_failed') . '</td><td align="right">' . $n('participant_skipped') . '</td></tr>'
            . '<tr><td>Staff</td><td align="right">' . $n('staff_sent') . '</td><td align="right">' . $n('staff_failed') . '</td><td align="right">0</td></tr>'
            . '<tr><td><strong>Total</strong></td><td align="right"><strong>' . $n('sent') . '</strong></td><td align="right"><strong>' . $n('failed') . '</strong></td><td align="right"><strong>' . $n('skipped') . '</strong></td></tr>'
            . '</table>';
        if ($n('queued') > 0) {
            $html .= '<p>' . $n('queued') . ' notice(s) are still queued.</p>';
        }
        if ($failed) {
            $html .= '<p><strong>Failed addresses</strong></p><ul>';
            foreach (array_slice($failed, 0, 50) as $row) {
                $to = htmlspecialchars((string)($row['to_email'] ?? ''), ENT_QUOTES);
                $err = htmlspecialchars((string)($row['error'] ?? ''), ENT_QUOTES);
                $html .= '<li>' . ($to !== '' ? $to : '(no email)') . ($err !== '' ? ' — ' . $err : '') . '</li>';
            }
            $html .= '</ul>';
            if (count($failed) > 50) {
                $html .= '<p>Plus ' . (count($failed) - 50) . ' more in Privacy → Failed.</p>';
            }
        }
        $html .= '<p style="color:#64748b;font-size:13px">Open Privacy in the admin app for the queued and failed tables.</p>';
        return $html;
    }

    public static function summaryEmailSubject(string $eventName, int $sent, int $failed): string
    {
        $eventName = trim($eventName);
        $head = $eventName !== '' ? 'Deletion notices: ' . $eventName : 'Deletion notices';
        return $head . ' — ' . $sent . ' sent, ' . $failed . ' failed';
    }

    private static function resolveAppFile(string $stored): ?string
    {
        $stored = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $stored));
        if ($stored === '') {
            return null;
        }
        $candidates = [$stored, dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . ltrim($stored, DIRECTORY_SEPARATOR)];
        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }
            $real = realpath($path);
            return $real !== false ? $real : $path;
        }
        return null;
    }
}
