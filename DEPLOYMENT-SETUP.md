# DEPLOYMENT-SETUP.md — Production Deployment Guide

**Target:** Hostinger Shared Hosting (seoindicator.com)

---

## Overview

This document describes the automated deployment pipeline for SEOIndicator.

### Deployment Flow
```
GitHub Push → GitHub Actions → Build → Test → Deploy FTP → Run Migrations
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
| FTP | — | 45.84.206.11:21 |

**Also includes:**
- Vercel AI Gateway API key
- OpenRouter.ai API key  
- Google PageSpeed Insights API key
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

**Deployed to:** `public_html/` via FTP

### 2. Backend Deployment (Laravel PHP)

**⚠️ Current Limitation:** Laravel's `vendor/` directory contains 6000+ files which causes FTP timeout on shared hosting.

**Solutions (choose one):**

| Option | Requirements | Difficulty |
|--------|--------------|------------|
| **A. Pre-upload vendor** | Upload vendor manually once via FTP client | Easy |
| **B. Use SSH + rsync** | Request SSH access from Hostinger | Medium |
| **C. ZIP vendor** | Upload ZIP, extract via SSH | Medium |
| **D. Simple PHP backend** | Rewrite API without Laravel | Medium |

**Current status:** Backend deployment disabled until solution implemented.

**Option A (recommended for now):**
1. Run `composer install --no-dev` locally
2. Upload entire `backend/` folder via FileZilla/WinSCP (one-time)
3. Subsequent deploys only sync `app/`, `routes/`, `config/` files

### 3. Database Migrations

Migrations are run via a secure endpoint:

1. Migration files uploaded to `api/database/migrations/`
2. Endpoint called: `POST /api/deploy/migrate?key=DEPLOY_KEY`
3. Laravel runs pending migrations

**Security:** Deploy key stored in GitHub Secrets

---

## GitHub Actions Workflow

### Workflow File
`.github/workflows/deploy.yml`

### Triggers
- **Push to `main`** → Full deployment
- **Pull Request** → Build & test only (no deploy)

### Workflow Steps

```yaml
1. Checkout code
2. Setup Node.js
3. Setup PHP
4. Install dependencies (npm, composer)
5. Build frontend (static export)
6. Run Playwright tests
7. Deploy frontend via FTP
8. Deploy backend via FTP
9. Run database migrations
10. Notify on success/failure
```

### Required GitHub Secrets

| Secret | Description |
|--------|-------------|
| `FTP_HOST` | FTP server hostname |
| `FTP_PORT` | FTP port (21) |
| `FTP_USERNAME` | FTP username |
| `FTP_PASSWORD` | FTP password |
| `DB_HOST` | Database hostname |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database username |
| `DB_PASSWORD` | Database password |
| `DEPLOY_KEY` | Secret key for migration endpoint |

---

## Local Deployment Testing

### Test FTP Connection
```powershell
# Via PowerShell
$ftp = [System.Net.WebRequest]::Create("ftp://45.84.206.11/")
$ftp.Credentials = New-Object System.Net.NetworkCredential("u949031532.seoindicator.com", "password")
$ftp.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
$response = $ftp.GetResponse()
```

### Test Database Connection
```powershell
cd backend
# Update .env with prod credentials, then:
php artisan tinker
>>> DB::connection()->getPdo();
```

### Manual Deployment (Emergency)
```powershell
# 1. Build frontend
cd frontend
npm run build
npx next export

# 2. Upload via FTP client (FileZilla, WinSCP)
# Frontend: ./out/* → public_html/
# Backend: ./backend/* → public_html/api/

# 3. Run migrations
# POST request to: https://seoindicator.com/api/deploy/migrate?key=DEPLOY_KEY
```

---

## Playwright Tests

### Test Configuration
Tests are located in `tests/e2e/` and run before deployment.

### Local Test Run
```bash
cd frontend
npx playwright test
```

### CI Test Run
Tests run automatically in GitHub Actions on every push/PR.

### Test Requirements
- All tests must pass before deployment
- Tests run against built static files
- API tests run against local or staging backend

---

## Rollback Procedure

### Quick Rollback (FTP)
1. Download current `public_html/` as backup
2. Upload previous version from local git: `git checkout HEAD~1`
3. Rebuild and re-upload

### Database Rollback
1. Restore from backup (daily backups available in Hostinger)
2. Or run down migrations: `php artisan migrate:rollback`

---

## Monitoring

### Post-Deployment Checks
1. Health endpoint: `GET https://seoindicator.com/api/health`
2. Homepage loads: `GET https://seoindicator.com/`
3. Database connected: Check health endpoint response

### Error Tracking
- PHP errors logged to `storage/logs/laravel.log`
- Access via FTP or Hostinger file manager

---

## Security Considerations

1. **Deploy Key:** Never commit, use GitHub Secrets
2. **FTP Credentials:** Stored in secrets, not in repo
3. **Database Credentials:** In secrets and `.env` (gitignored)
4. **.htaccess:** Restricts access to sensitive files
5. **Migrations Endpoint:** Protected by deploy key, IP whitelist optional

---

## Deployment Checklist

### Before First Deployment
- [ ] GitHub Secrets configured
- [ ] FTP credentials verified
- [ ] Database credentials verified
- [ ] Deploy key generated and stored

### Each Deployment
- [ ] All tests pass locally
- [ ] Build succeeds
- [ ] GitHub Actions workflow completes
- [ ] Health check returns 200
- [ ] Homepage loads correctly

---

## Troubleshooting

### FTP Connection Fails
- Check credentials in GitHub Secrets
- Verify IP is not blocked by Hostinger
- Try passive mode

### Database Migration Fails
- Check migration logs in `storage/logs/`
- Verify DB credentials
- Check for syntax errors in migration files

### Build Fails
- Check Node.js version matches local
- Clear npm cache: `npm cache clean --force`
- Delete `node_modules` and reinstall

### Static Export Fails
- Ensure no server-side features used
- Check for dynamic routes (not supported in static export)
- Verify `next.config.js` has `output: 'export'`

---

Last updated: 2026-02-19
