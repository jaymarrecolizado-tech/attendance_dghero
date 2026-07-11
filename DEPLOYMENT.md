# Hostinger Deployment Guide - ISSP Solo

## Domain
**Production URL:** https://digitalbayanihan.site/

## Pre-Deployment Checklist

1. ✅ All tests passing
2. ✅ Database credentials ready
3. ✅ SMTP email credentials ready
4. ✅ SSL certificate installed (recommended)

## Step-by-Step Deployment

### 1. Database Setup

1. Log in to your Hostinger hPanel
2. Go to **Databases** → **MySQL Databases**
3. Create a new database (e.g., `digitalbayanihan_db`)
4. Create a new database user with a strong password
5. Add the user to the database with **ALL PRIVILEGES**
6. Note down:
   - Database name
   - Database username
   - Database password
   - Database host (usually `localhost`)

### 2. File Upload

1. Upload all files from `hostinger_migrate` folder to your `public_html` directory
2. You can use:
   - **File Manager** in hPanel
   - **FTP Client** (FileZilla, WinSCP, etc.)
   - **SSH** (if available)

3. Ensure file permissions:
   ```bash
   chmod 755 storage/
   chmod 755 storage/qrcodes/
   chmod 755 storage/signatures/
   chmod 755 storage/imports/
   chmod 755 storage/reports/
   chmod 755 storage/runtime/
   chmod 644 .htaccess
   chmod 600 .env
   ```

### 3. Environment Configuration

1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` file with your actual credentials:
   ```env
   DB_HOST=localhost
   DB_NAME=digitalbayanihan_db
   DB_USER=your_db_user
   DB_PASS=your_db_password
   DB_AUTO_MIGRATE=false
   
   SMTP_HOST=smtp.hostinger.com
   SMTP_PORT=465
   SMTP_USER=noreply@digitalbayanihan.site
   SMTP_PASS=your_email_password
   SMTP_SECURE=ssl
   SMTP_FROM=noreply@digitalbayanihan.site
   
   APP_URL=https://digitalbayanihan.site
   ```

### 4. Run Database Migrations

**Option A: Via SSH (Recommended)**
```bash
cd ~/public_html
php scripts/run_migrations.php
```

**Option B: Via Browser (if SSH not available)**
1. Create a temporary file: `run_migrations_once.php` in public_html root
2. Add this code:
   ```php
   <?php
   require __DIR__ . '/config/bootstrap.php';
   require __DIR__ . '/vendor/autoload.php';
   \App\Services\Database::migrate();
   echo "Migrations completed!";
   ```
3. Visit: `https://digitalbayanihan.site/run_migrations_once.php`
4. **DELETE this file immediately after running!**

### 5. Create Admin User

**Via SSH:**
```bash
cd ~/public_html
php scripts/seed_admin.php admin your_secure_password
```

**Via Browser (temporary):**
1. Create `create_admin.php`:
   ```php
   <?php
   require __DIR__ . '/config/bootstrap.php';
   require __DIR__ . '/vendor/autoload.php';
   $pdo = \App\Services\Database::pdo();
   $username = 'admin';
   $password = password_hash('your_secure_password', PASSWORD_DEFAULT);
   $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
   $stmt->execute([$username, $password]);
   echo "Admin created!";
   ```
2. Visit the URL once
3. **DELETE immediately!**

### 6. Set File Permissions

Ensure these directories are writable:
- `storage/qrcodes/` - 755
- `storage/signatures/` - 755
- `storage/imports/` - 755
- `storage/reports/` - 755
- `storage/runtime/` - 755

### 7. Test the Application

1. Visit: https://digitalbayanihan.site/
2. Test registration
3. Test admin login: `?r=admin_login`
4. Test all features

### 8. Security Hardening

1. **Protect .env file:**
   - Ensure `.htaccess` denies access to `.env`
   - Set permissions: `chmod 600 .env`

2. **Remove test/development files:**
   - Delete `scripts/` folder (or restrict access)
   - Remove any temporary migration files

3. **Enable HTTPS:**
   - Uncomment HTTPS redirect in `.htaccess` if SSL is installed

4. **Set production settings:**
   - `DB_AUTO_MIGRATE=false` in `.env`
   - `CSP_STRICT=true` for stricter security (test first)

## Post-Deployment

### Email Configuration
1. Log in as admin
2. Go to Settings: `?r=admin_settings`
3. Configure SMTP settings
4. Test email sending

### Backup Strategy
- Set up regular database backups via hPanel
- Backup `storage/` folder regularly
- Keep `.env` file secure and backed up

### Monitoring
- Check error logs in hPanel
- Monitor `storage/runtime/` for rate limit files
- Check `logs/` folder for email logs

## Troubleshooting

### Database Connection Error
- Verify database credentials in `.env`
- Check database user has proper permissions
- Ensure database host is correct (usually `localhost`)

### Permission Denied Errors
- Check file permissions on `storage/` directories
- Ensure PHP can write to storage folders

### 500 Internal Server Error
- Check PHP error logs in hPanel
- Verify `.htaccess` syntax is correct
- Check PHP version (requires PHP 8.0+)

### Routes Not Working
- Ensure `.htaccess` is in `public_html` root
- Verify `mod_rewrite` is enabled
- Check Apache configuration

### Email Not Sending
- Verify SMTP credentials
- Check Hostinger SMTP settings
- Test via Settings page

## Support
For issues, check:
- PHP error logs
- Application logs in `storage/runtime/`
- Hostinger support documentation

