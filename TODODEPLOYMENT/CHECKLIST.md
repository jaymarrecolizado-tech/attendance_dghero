# digitalhero.dictr2.cloud — Go-live checklist

## Server facts
| Item | Value |
|------|--------|
| Domain | digitalhero.dictr2.cloud |
| Site user | digitalhero |
| IP | 187.77.150.203 |
| Database | dbdigitalhero |
| DB user | dbudigitalhero |

## Pre-upload
- [ ] Project builds/runs locally
- [ ] `vendor/` included OR `composer install --no-dev` will be run on server
- [ ] SMTP mailbox ready (fill SMTP_* in `.env`)
- [ ] SSL certificate planned (Let's Encrypt / Hostinger SSL)

## Upload
- [ ] Upload **all contents** of `TODODEPLOYMENT/uploads/` to the site document root
- [ ] Confirm `.env` and `.htaccess` are in the document root
- [ ] Do **not** upload `diagnose.php`, `create_admin.php`, or SQL dumps

## On server (SSH)
- [ ] Run `bash set_permissions.sh` (or `post_deploy.sh`) — enforces `755` / `644` / `.env` `600`
- [ ] Confirm `stat` on `.env` shows `600`
- [ ] Confirm `storage/` dirs are `755` and writable by site user
- [ ] `php scripts/run_migrations.php`
- [ ] `php scripts/seed_admin.php YOURUSER 'StrongPassword'`
- [ ] Optional: `php scripts/seed_role_users.php`
- [ ] Delete `diagnose.php` / `create_admin.php` if uploaded
- [ ] Remove `TODODEPLOYMENT/` from the live docroot if you uploaded it

## Verify in browser
- [ ] https://digitalhero.dictr2.cloud/?r=register
- [ ] Guest registration + QR
- [ ] Admin login
- [ ] Attendance / Scan
- [ ] SEO dashboard
- [ ] Email QR (if SMTP filled)

## After go-live
- [ ] Change default seed passwords
- [ ] Confirm `APP_DEBUG` is not true
- [ ] Confirm `DB_AUTO_MIGRATE=false`
- [ ] Keep `.env` out of git / public downloads
