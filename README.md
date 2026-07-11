# ISSP Solo - Hostinger Migration Package

This folder contains all files needed to deploy the ISSP Solo Event Registration & Attendance System to Hostinger hosting.

## 📋 Quick Start

1. **Upload all files** from this folder to your Hostinger `public_html` directory
2. **Copy `env.example` to `.env`** and update with your database credentials
3. **Run database migrations** (see DEPLOYMENT.md)
4. **Create admin user** (see DEPLOYMENT.md)
5. **Configure SMTP** via admin panel at `?r=admin_settings`

## 📁 Folder Structure

```
hostinger_migrate/
├── .htaccess              # Apache configuration (routing, security)
├── index.php              # Main entry point
├── qrcode.php             # QR code display
├── signature.php          # Signature display
├── assets/                # CSS and static assets
├── config/                # Application configuration
├── src/                   # PHP source code
├── views/                 # View templates
├── migrations/           # Database migration files
├── storage/               # File storage (qrcodes, signatures, etc.)
├── vendor/                # Composer dependencies
├── scripts/               # Utility scripts
├── env.example            # Environment template
├── DEPLOYMENT.md          # Detailed deployment guide
└── README.md              # This file
```

## 🔐 Important Files

- **`.htaccess`** - Apache routing and security rules
- **`.env`** - Environment configuration (create from env.example)
- **`index.php`** - Application entry point

## ⚙️ Required Configuration

### Database
- Create database in Hostinger hPanel
- Update `.env` with database credentials

### Email (SMTP)
- Configure via admin panel: `?r=admin_settings`
- Or edit `.env` directly

### File Permissions
Ensure these directories are writable (755):
- `storage/qrcodes/`
- `storage/signatures/`
- `storage/imports/`
- `storage/reports/`
- `storage/runtime/`

## 🚀 Deployment Steps

See **DEPLOYMENT.md** for detailed step-by-step instructions.

## 🔒 Security Notes

- `.env` file contains sensitive data - keep it secure
- Never commit `.env` to version control
- Delete temporary migration scripts after use
- Keep `storage/` directories protected (already configured)

## 📞 Support

For deployment issues:
1. Check DEPLOYMENT.md troubleshooting section
2. Verify file permissions
3. Check PHP error logs in Hostinger hPanel
4. Ensure PHP 8.0+ is enabled

## ✅ Post-Deployment Checklist

- [ ] All files uploaded to public_html
- [ ] .env file created and configured
- [ ] Database migrations run successfully
- [ ] Admin user created
- [ ] SMTP configured and tested
- [ ] File permissions set correctly
- [ ] HTTPS/SSL enabled (recommended)
- [ ] Test registration flow
- [ ] Test admin login
- [ ] Test QR code generation
- [ ] Test email sending

---

**Domain:** https://digitalbayanihan.site/
**Version:** 1.0.0
**Last Updated:** 2025-01-18

