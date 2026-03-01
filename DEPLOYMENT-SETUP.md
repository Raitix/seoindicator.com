# DEPLOYMENT-SETUP.md — Production Deployment Guide

**Target:** Hostinger Shared Hosting (seoindicator.com)

---

## Overview

This document describes the automated deployment pipeline for SEOIndicator.

### Deployment Flow
```
GitHub Push → GitHub Actions → Build → Test → SSH Deploy → git pull → composer install
```

---

## Environments

| Environment | Purpose | URL |
|-------------|---------|-----|
| **Local** | Development | http://localhost:3000 (frontend), http://localhost:8000 (backend) |
| **Production** | Live site | https://seoindicator.com |

---

## Credentials

**All credentials stored in:** `~/.openclaw/secrets/seoindicator.env`

| Service | Local | Production |
|---------|-------|------------|
| MySQL | 127.0.0.1 (XAMPP) | srv1924.hstgr.io |
| SSH | — | 45.84.206.11:65002 |

**Also includes:**
- Vercel AI Gateway API key
- OpenRouter.ai API key
- Google PageSpeed Insights API key
- GitHub PAT for private repo access
- Deploy key for migrations

---

## Deployment Strategy

### 1. Frontend Deployment (Next.js → Static Export)

Since production is on shared hosting (no Node.js runtime), we use static export:

```bash
# Build static files
cd frontend
npm run build
# Outputs to ./out (configured in next.config.ts)
```

**Deployed to:** `public_html/` via SCP

### 2. Backend Deployment (Laravel PHP)

Uses SSH + git pull + composer install:

1. SSH into server
2. Configure git remote with PAT
3. `git fetch origin`
4. `git reset --hard origin/main`
5. `composer install --no-dev`

**No more FTP timeout issues!**

### 3. Database Migrations

Migrations run automatically during deployment:

```bash
php artisan migrate --force
```

---

## GitHub Actions Workflow

### Workflow File
`.github/workflows/deploy.yml`

### Triggers
- **Push to `main`** → Full deployment
- **Pull Request** → Build & test only (no deploy)

### Required GitHub Secrets

| Secret | Description |
|--------|-------------|
| `SSH_HOST` | SSH server hostname (45.84.206.11) |
| `SSH_PORT` | SSH port (65002) |
| `SSH_USERNAME` | SSH username (u949031532) |
| `SSH_PASSWORD` | SSH password |
| `SSH_DEPLOY_PATH` | Deploy path (/home/u949031532/domains/seoindicator.com/public_html) |
| `GH_PAT` | GitHub PAT for private repo access |
| `DB_HOST` | Database hostname |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database username |
| `DB_PASSWORD` | Database password |
| `DEPLOY_KEY` | Secret key for migration endpoint |

---

## Manual Deployment

### SSH Connection
```bash
ssh -p 65002 u949031532@45.84.206.11
```

### Manual git pull
```bash
cd /home/u949031532/domains/seoindicator.com/public_html
git fetch origin
git reset --hard origin/main
cd backend
composer install --no-dev
```

### Build & Upload Frontend
```bash
cd frontend
npm run build
# Upload ./out/* to server via SCP or FTP
```

---

## Rollback Procedure

### Quick Rollback (git)
```bash
ssh -p 65002 u949031532@45.84.206.11
cd /home/u949031532/domains/seoindicator.com/public_html
git reset --hard HEAD~1  # Previous commit
```

### Database Rollback
```bash
php artisan migrate:rollback
```

---

## Monitoring

### Post-Deployment Checks
1. Health endpoint: `GET https://seoindicator.com/api/health`
2. Homepage loads: `GET https://seoindicator.com/`
3. Git status shows clean: `git status`

### Error Tracking
- PHP errors logged to `storage/logs/laravel.log`
- Access via SSH or Hostinger file manager

---

## Apache .htaccess Configuration

### Overview
The `.htaccess` file is located at `frontend/public/.htaccess` and is automatically copied to `out/.htaccess` during the build process. It provides:

1. **Clean URL Routing** - 301 redirects for SEO guides
2. **Security Headers** - XSS, clickjacking, and MIME sniffing protection
3. **File Access Control** - Blocks hidden and backup files
4. **Caching** - Optimized cache headers for static assets
5. **Compression** - Gzip for text-based files

### SEO Guides Routing
```apache
# Redirect /seo-guides to /seo-guides/ (301)
RewriteCond %{REQUEST_URI} ^/seo-guides$ [NC]
RewriteRule ^(.*)$ /seo-guides/ [L,R=301]

# Redirect /seo-guides/[slug] to /seo-guides/[slug]/ (301)
RewriteCond %{REQUEST_URI} ^/seo-guides/([a-z0-9-]+)$ [NC]
RewriteRule ^seo-guides/(.*)$ /seo-guides/$1/ [L,R=301]
```

### Security Features
| Feature | Purpose |
|---------|---------|
| Hidden file blocking | Denies access to `.htaccess`, `.env`, `.git`, etc. |
| Backup file blocking | Denies access to `.bak`, `.backup`, `.old`, etc. |
| X-Content-Type-Options | Prevents MIME sniffing |
| X-Frame-Options | Prevents clickjacking |
| X-XSS-Protection | Browser XSS filter |

### Requirements
- Apache with `mod_rewrite` enabled
- Apache with `mod_headers` enabled (for security headers)
- Apache with `mod_deflate` enabled (for gzip compression)
- Apache with `mod_expires` enabled (for caching)

### Verification
After deployment, verify:
1. `/seo-guides` redirects to `/seo-guides/` with 301 status
2. `/seo-guides/meta-title` redirects to `/seo-guides/meta-title/` with 301 status
3. Security headers are present in responses
4. Hidden files return 403 Forbidden

---

## Security Considerations

1. **GitHub PAT:** Stored in GitHub Secrets, used for private repo access
2. **SSH Credentials:** Stored in secrets, not in repo
3. **Database Credentials:** In secrets and `.env` (gitignored)
4. **.htaccess:** Restricts access to sensitive files (see Apache Configuration section)
5. **Migrations Endpoint:** Protected by deploy key

---

## Deployment Checklist

### Each Deployment
- [x] All tests pass in CI
- [x] Build succeeds
- [x] GitHub Actions workflow completes
- [x] Health check returns 200
- [x] Homepage loads correctly

---

## Troubleshooting

### Git Pull Fails
- Check PAT is valid and has `repo` scope
- Verify `.git` directory exists
- Check remote URL is configured

### Composer Install Fails
- Check PHP version (8.2+)
- Check for missing extensions
- Review `composer.json` requirements

### Build Fails
- Check Node.js version matches (23)
- Clear npm cache: `npm cache clean --force`
- Delete `node_modules` and reinstall

### Static Export Fails
- Ensure no server-side features used
- Check for dynamic routes (not supported in static export)
- Verify `next.config.js` has `output: 'export'`

---

Last updated: 2026-03-01
