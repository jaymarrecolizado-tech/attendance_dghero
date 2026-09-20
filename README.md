# All Father — Multi-Event Attendance (digitalhero.dictr2.cloud)

Multi-event platform: All Father creates events, assigns people and roles per event, each event has unique `?r=register&e=slug` / `?r=scan&e=slug` links. VPS `187.77.150.203` / DB `dbdigitalhero`.

## Quick Start

1. Copy `env.example` → `.env` and fill `DB_*`, `SMTP_*`, `APP_URL=https://digitalhero.dictr2.cloud`
2. `composer install` (or upload `vendor/`)
3. `php scripts/run_migrations.php` and `php scripts/seed_admin.php`
4. Configure SMTP via `?r=admin_settings` and use `TODODEPLOYMENT/.htaccess.production` on server (HTTPS)

See `DEPLOYMENT.md` and `TODODEPLOYMENT/README.md` / `TODODEPLOYMENT/CHECKLIST.md`. Do not upload `diagnose.php` / `create_admin.php` — see `TODODEPLOYMENT/DO_NOT_UPLOAD.txt`. Pack from **project root**, not `TODODEPLOYMENT/uploads/` (folder not in repo).

