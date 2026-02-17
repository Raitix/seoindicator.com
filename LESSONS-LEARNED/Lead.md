# Lead.md — Project Lessons Learned

Knowledge base for Lead agent working on SEOIndicator project.

---

## Architecture & Patterns

_(Document architectural decisions and patterns used)_

---

## Common Pitfalls

### SSL Certificate Issues with Composer/npm

**Problem:** Firewall/antivirus performing SSL inspection (man-in-the-middle) breaks certificate validation for HTTPS requests. This causes:
- Composer: `curl error 60 while downloading... SSL certificate problem: unable to get local issuer certificate`
- npm: Similar SSL handshake failures

**Root Cause:** Corporate/personal firewalls intercept HTTPS traffic and re-sign certificates with their own CA. PHP/curl doesn't trust this CA by default.

**Solutions (in order of preference):**

1. **Temporary disable firewall/antivirus** - Quick fix for development setup
2. **Allowlist domains** in firewall for `repo.packagist.org`, `registry.npmjs.org`, `github.com`
3. **Download packages via PowerShell** (which handles SSL differently):
   ```powershell
   Invoke-WebRequest -Uri "https://github.com/laravel/laravel/archive/refs/tags/v12.11.2.zip" -OutFile laravel.zip
   ```
4. **Add firewall's CA certificate** to PHP's trusted store:
   - Export CA cert from firewall/antivirus
   - Add to `C:\xampp\php\extras\ssl\cacert.pem`
   - Configure `curl.cainfo` and `openssl.cafile` in `php.ini`

**Workaround that worked:** Disabling antivirus temporarily allowed Composer to install all dependencies successfully.

**Date:** 2026-02-17

---

## Performance Notes

_(Document performance optimizations and considerations)_

---

## Integration Points

### Production Environment - Hostgator Shared Hosting

**Constraints:**
- ❌ No Node.js runtime - must use static export for Next.js
- ❌ No WebSockets - use polling or server-sent events instead
- ❌ No Redis caching - use file or database caching
- ⚠️ Limited shell access - use cPanel/cron jobs
- ✅ Composer available - install dependencies locally, upload `vendor/` folder

**Deployment Strategy:**
1. Build Next.js static: `npm run build && npx next export`
2. Upload `out/` to `public_html`
3. Upload Laravel with `vendor/` pre-installed
4. Configure `.env` for production database

---

## Code Conventions

_(Document project-specific coding conventions)_

---

Last updated: 2026-02-17
