# Pre-Deployment Checklist

## ✅ Files Verification

- [x] `.htaccess` - Apache routing and security
- [x] `index.php` - Main entry point
- [x] `qrcode.php` - QR code handler
- [x] `signature.php` - Signature handler
- [x] `config/` - Configuration files
- [x] `src/` - PHP source code
- [x] `views/` - View templates
- [x] `migrations/` - Database migrations
- [x] `vendor/` - Composer dependencies
- [x] `storage/` - Storage directories with protection
- [x] `assets/` - CSS and static files
- [x] `scripts/` - Utility scripts
- [x] `env.example` - Environment template
- [x] Documentation files

## ✅ Security Files

- [x] `.htaccess` in root (routing + security)
- [x] `.htaccess` in storage/ (deny access)
- [x] `index.php` files in storage subdirectories (403 protection)
- [x] Storage directories protected

## ✅ Documentation

- [x] `README.md` - Overview
- [x] `DEPLOYMENT.md` - Detailed deployment guide
- [x] `QUICK_START.txt` - Quick reference
- [x] `CHECKLIST.md` - This file

## 📋 Pre-Upload Checklist

Before uploading to Hostinger:

1. **Verify all files are present**
   - Check that all folders exist
   - Verify vendor/ folder is complete
   - Ensure storage/ subdirectories exist

2. **Review configuration**
   - Check `.htaccess` settings
   - Review `env.example` template
   - Verify file paths are correct

3. **Test locally (optional)**
   - Can test file structure
   - Verify no missing dependencies

## 📤 Upload Checklist

When uploading to Hostinger:

1. **Upload all files** to `public_html`
2. **Set file permissions:**
   - Directories: 755
   - Files: 644
   - `.env`: 600 (after creation)
3. **Create `.env`** from `env.example`
4. **Run migrations**
5. **Create admin user**
6. **Test application**

## 🔍 Post-Deployment Verification

After deployment, verify:

- [ ] Homepage loads: https://digitalbayanihan.site/
- [ ] Registration form works
- [ ] Admin login works: `?r=admin_login`
- [ ] Database connection successful
- [ ] File uploads work (QR codes, signatures)
- [ ] Email sending works (SMTP configured)
- [ ] All routes accessible
- [ ] No PHP errors in logs
- [ ] HTTPS/SSL working (if enabled)

## 🚨 Common Issues

### Issue: 500 Internal Server Error
- Check `.htaccess` syntax
- Verify PHP version (8.0+)
- Check file permissions
- Review error logs

### Issue: Database Connection Failed
- Verify `.env` credentials
- Check database user permissions
- Ensure database exists

### Issue: Routes Not Working
- Verify `.htaccess` is in root
- Check `mod_rewrite` is enabled
- Verify Apache configuration

### Issue: File Upload Fails
- Check storage/ directory permissions (755)
- Verify PHP upload settings
- Check disk space

### Issue: Email Not Sending
- Verify SMTP credentials in `.env`
- Test SMTP settings via admin panel
- Check Hostinger SMTP requirements

## 📞 Support Resources

- **Hostinger Support:** hPanel → Support
- **PHP Error Logs:** hPanel → Advanced → Error Log
- **Application Logs:** `storage/runtime/`
- **Documentation:** See DEPLOYMENT.md

---

**Ready for deployment!** Follow DEPLOYMENT.md for step-by-step instructions.

