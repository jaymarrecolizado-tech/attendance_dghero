# Production file permissions — digitalhero.dictr2.cloud

Run after every upload / sync:

```bash
cd /path/to/public_html
bash set_permissions.sh
# or from this pack:
APP_ROOT=/path/to/public_html bash TODODEPLOYMENT/set_permissions.sh
```

`post_deploy.sh` calls this automatically.

## Target modes

| Path / type | Mode | Why |
|-------------|------|-----|
| Directories | `755` | Traverse + list; owner can write |
| Most files (PHP, CSS, JS, images) | `644` | Readable by web server; not executable |
| `.env` | `600` | Secrets — owner read/write only |
| `.htaccess` | `644` | Apache must read it |
| `storage/` dirs | `755` | QR, signatures, imports, reports, runtime |
| `storage/` files | `644` | Created by PHP as site user |
| `*.sh` helpers | `755` | Executable for SSH only |

## Never use

| Mode | Risk |
|------|------|
| `777` | Anyone can write — insecure |
| `666` on `.env` | Password leak |
| World-writable `storage/` | Tampering |

## Ownership

On Hostinger with site user `digitalhero`, files should be owned by that user:

```bash
# Only if you have root / sudo and ownership is wrong:
# chown -R digitalhero:digitalhero /path/to/public_html
```

If PHP-FPM runs as `digitalhero` (typical shared/cloud panel setup), `755` + `644` + `.env` `600` is enough.

If PHP runs as `www-data` / `nginx` and cannot write storage, either:

1. Prefer fixing so the pool user matches the file owner (best), or  
2. `chgrp -R www-data storage && chmod -R g+w storage` (only if required)

## Quick verify

```bash
stat -c '%a %n' .env .htaccess index.php
stat -c '%a %n' storage storage/qrcodes storage/signatures
# Expect: 600 .env | 644 others | 755 storage dirs
```
