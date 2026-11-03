# Cron — digitalhero.dictr2.cloud

Install as user **`digitalhero`** (not `dghero111`). Server clock is Asia/Manila (shown as PST on the host).

App path: `/home/digitalhero/htdocs/digitalhero.dictr2.cloud`

```bash
sudo crontab -u digitalhero -e
```

## Installed crontab (as of 2026-11-03)

```cron
# Certificate of Appearance — every minute (due / scheduled sends)
* * * * * cd /home/digitalhero/htdocs/digitalhero.dictr2.cloud && php scripts/coa_process_scheduled.php >> /home/digitalhero/htdocs/digitalhero.dictr2.cloud/storage/runtime/coa_cron.log 2>&1

# Privacy 15-day purge — 01:15 daily (Asia/Manila)
15 1 * * * cd /home/digitalhero/htdocs/digitalhero.dictr2.cloud && php scripts/privacy_purge_due.php >/dev/null 2>&1

# One-shot: Hack for Gov 5 deletion-notice resend (2 Nov 2026 16:40 only; script also date-locks)
40 16 2 11 * cd /home/digitalhero/htdocs/digitalhero.dictr2.cloud && php scripts/privacy_resend_notices.php 8 2026-11-02 >> storage/runtime/privacy-resend-h4g.log 2>&1
```

## What each job does

| Schedule | Script | Purpose |
|----------|--------|---------|
| `* * * * *` | `scripts/coa_process_scheduled.php` | Send due CoA queue rows. Log: `storage/runtime/coa_cron.log` |
| `15 1 * * *` | `scripts/privacy_purge_due.php` | Events past `ends_at` + 15 days: encrypted zip on VPS, delete live participant data, email participants (public registration image in the body), staff archive mail, summary to `PRIVACY_SUMMARY_TO`. Does not auto-upload Drive unless `GOOGLE_DRIVE_ENABLED=true`. |
| `40 16 2 11 *` | `scripts/privacy_resend_notices.php 8 2026-11-02` | **One-time** resend of Hack for Gov 5 (event id 8) deletion notices. Safe to remove after 2026-11-02. Lock file: `storage/runtime/privacy-resend-8-YYYY-MM-DD.done` |

## Notes

- Run `crontab -u digitalhero -l` on the VPS to confirm live lines.
- Do not put secrets in this file. Passphrase and SMTP live in production `.env`.
- Manual resend: `php scripts/privacy_resend_notices.php {event_id}` from the app root as `digitalhero`.
