# Deployment pack — digitalhero.dictr2.cloud

Ready-to-use files and steps for Hostinger (KVM / cloud) deployment of **GovNet-Launching / attendance_digital**.

## Target server

| Item | Value |
|------|--------|
| Domain | `digitalhero.dictr2.cloud` |
| Site user | `digitalhero` |
| IP | `187.77.150.203` |
| Database name | `dbdigitalhero` |
| Database user | `dbudigitalhero` |
| Database password | *(see `.env.production`)* |
| App URL | `https://digitalhero.dictr2.cloud` |

> **Security:** `.env.production` contains live DB credentials. Do not commit it to a public repo. Prefer uploading it only over SFTP/SSH and set `chmod 600`.

---

## What’s in this folder

| File / folder | Purpose |
|---------------|---------|
| **`uploads/`** | **Ready-to-upload app package** — upload its *contents* to public_html |
| `.env.production` | Source for production `.env` (also copied into `uploads/.env`) |
| `.htaccess.production` | Source for production `.htaccess` (also in `uploads/`) |
| `set_permissions.sh` | Permission enforcer (also in `uploads/`) |
| `PERMISSIONS.md` | Permission reference |
| `CHECKLIST.md` | Go-live tick list |
| `DO_NOT_UPLOAD.txt` | Files to exclude |
| `post_deploy.sh` | Permissions + migrations helper |
| `SERVER_INFO.md` | Connection summary |
| `README.md` | This guide |

---

## Fast path (recommended)

1. Open **`TODODEPLOYMENT/uploads/`**
2. Upload **all contents** of that folder to the site document root
3. SSH → `bash set_permissions.sh` → `php scripts/run_migrations.php` → seed admin
4. Test https://digitalhero.dictr2.cloud

See `uploads/UPLOAD_README.txt` for the short version.

---

## 1. Prepare files locally

**Preferred:** use the prebuilt package in `TODODEPLOYMENT/uploads/` (already filtered).

If rebuilding from the project root instead, include:

- `index.php`, `config/`, `src/`, `views/`, `assets/`, `migrations/`, `scripts/`
- `vendor/` (or run Composer on the server)
- `storage/` folders (can be empty; must be writable)
- `qrcode.php`, `signature.php`

**Do not upload** items listed in `DO_NOT_UPLOAD.txt`.

---

## 2. Document root

On Hostinger, the site files usually go in one of:

- `~/domains/digitalhero.dictr2.cloud/public_html`
- `~/public_html`
- A custom path set in the panel for site user `digitalhero`

Point the domain/vhost document root at that folder.

---

## 3. Install config on the server

```bash
# From your local machine (example with scp) — adjust path:
# scp TODODEPLOYMENT/.env.production digitalhero@187.77.150.203:~/domains/digitalhero.dictr2.cloud/public_html/.env
# scp TODODEPLOYMENT/.htaccess.production digitalhero@187.77.150.203:~/domains/digitalhero.dictr2.cloud/public_html/.htaccess
```

Or use File Manager / SFTP:

1. Upload app files  
2. Rename/copy `.env.production` → `.env`  
3. Rename/copy `.htaccess.production` → `.htaccess`  
4. Fill `SMTP_USER` / `SMTP_PASS` / `SMTP_FROM` in `.env` when email is ready  

### Enforce permissions (required)

Upload `TODODEPLOYMENT/set_permissions.sh` into the site root (or keep the pack path), then:

```bash
cd /path/to/public_html
# copy helper once:
# cp /path/to/TODODEPLOYMENT/set_permissions.sh .
bash set_permissions.sh
```

This sets:

| Target | Mode |
|--------|------|
| All directories | `755` |
| All normal files | `644` |
| `.env` | `600` |
| `storage/*` | writable by owner (`755` dirs) |
| Shell scripts | `755` |

Never use `777`. Details: `PERMISSIONS.md`.

`post_deploy.sh` runs the same permission pass before migrations.

---

## 4. Database

Database is already created as:

- Name: `dbdigitalhero`
- User: `dbudigitalhero`

`DB_HOST=localhost` is correct for MySQL on the same VPS. If Hostinger shows a remote host (e.g. `mysql.hostinger.com`), change `DB_HOST` in `.env`.

Run migrations over SSH:

```bash
cd /path/to/public_html
php scripts/run_migrations.php
```

Create admin:

```bash
php scripts/seed_admin.php admin 'ReplaceWithStrongPassword!'
```

Optional checker / SEO seeds:

```bash
php scripts/seed_role_users.php
```

Then change those passwords in **Users** (admin UI).

Or run:

```bash
bash TODODEPLOYMENT/post_deploy.sh
# if you uploaded this folder temporarily — better copy script only, then delete the folder
```

---

## 5. PHP / SSL

Ensure PHP **8.1+** (or whatever you use locally) with extensions: `pdo_mysql`, `mbstring`, `openssl`, `gd` or whatever QR needs, `fileinfo`.

Enable SSL for `digitalhero.dictr2.cloud`, then confirm HTTPS redirects work (`.htaccess.production` already forces HTTPS).

---

## 6. Smoke test

1. https://digitalhero.dictr2.cloud/?r=register  
2. https://digitalhero.dictr2.cloud/?r=admin_login  
3. Register guest → QR  
4. Attendance + Scan  
5. SEO dashboard  

---

## 7. After go-live

- Delete `diagnose.php`, `create_admin.php`, and this `TODODEPLOYMENT` folder from the server if uploaded  
- Keep `APP_DEBUG` unset/false  
- Keep `DB_AUTO_MIGRATE=false`  
- Rotate DB password in Hostinger if this chat/file may be shared, then update `.env`

---

## SMTP (still required for QR email)

Edit `.env` on the server:

```env
SMTP_USER=noreply@your-mailbox
SMTP_PASS=your-mailbox-password
SMTP_FROM=noreply@your-mailbox
```

Use a Hostinger mailbox on this domain when available.
