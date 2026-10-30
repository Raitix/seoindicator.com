# DEPLOYMENT-SETUP.md — Production Deployment Guide

**Target:** Hostinger Shared Hosting (`seoindicator.com`)
**Current deployment model:** local / self-hosted deployment from the development machine

---

## Overview

SEOIndicator no longer deploys through GitHub-hosted Actions.

The production pipeline is now:

```text
Git push to main
  → local post-push hook (or manual trigger)
  → local frontend build in temp worktree
  → SSH/SFTP deployment to production
  → backend composer/migrations/cache steps on server
  → local Playwright smoke checks against production
  → structured deployment log written locally
```

This keeps the repository private and removes dependence on GitHub Actions minute quotas.

---

## Deployment Entry Points

### Automatic trigger
- `.githooks/post-push`
- fires after pushes to `main`
- calls `scripts/deploy-prod.ps1`

### Manual trigger
- `scripts/deploy-prod.ps1`
- Workflow Control UI → **Deploy** tab → **Run Deploy**

### Hook installation
```powershell
powershell -ExecutionPolicy Bypass -File scripts\install-git-hooks.ps1
```

That command sets:

```bash
git config --local core.hooksPath .githooks
```

---

## Deployment Scripts

| File | Purpose |
|------|---------|
| `scripts/deploy-prod.ps1` | PowerShell entrypoint for deployment |
| `scripts/deploy_prod.py` | Main deployment engine |
| `.githooks/post-push` | Auto-deploy hook for `main` pushes |
| `scripts/install-git-hooks.ps1` | Configures git hooks path |
| `frontend/scripts/prod-smoke-check.mjs` | Local Playwright-based production smoke verification |

---

## Deployment Logs

Structured deployment logs are written to:

```text
C:\Users\Raitis\.openclaw\logs\deployments
```

Each deployment creates a JSON log containing:
- deployment id
- trigger source
- git ref / commit
- start / end times
- status
- per-step status and output
- summary text

The Workflow Control UI reads these logs and shows the most recent deployment history.

---

## Workflow Control Integration

The Workflow Control app (`PROJECT-CONTROL`) includes a **Deploy** tab between **Agents** and **Stats**.

### Available deploy features
- show latest deployment history (up to 100 runs)
- inspect step-by-step deployment details
- show running / success / failed state
- manually trigger a deployment of `origin/main`

### API endpoints used by the UI
- `GET /api/deployments`
- `GET /api/deployments/:id`
- `POST /api/deployments/run`

---

## Secrets & Credentials

All deployment secrets are stored locally in:

```text
~/.openclaw/secrets/seoindicator.env
```

### Required values
| Secret | Purpose |
|--------|---------|
| `SSH_HOST` | Production SSH host |
| `SSH_PORT` | Production SSH port |
| `SSH_USERNAME` | Production SSH username |
| `SSH_PASSWORD` | Production SSH password |
| `SSH_DEPLOY_PATH` | Production app root |
| `GH_PAT` | Private GitHub repository access on production server |

### Notes
- secrets are **not** stored in GitHub Actions secrets anymore for deployment purposes
- server-side git access still depends on `GH_PAT`
- production `.env` remains server-managed and is not overwritten by deploy script

---

## What the Deployment Does

### 1. Refresh git state locally
- `git fetch origin main`
- resolve requested git ref
- create a temporary detached worktree for the deployment build

### 2. Build frontend locally
The deploy engine builds the frontend from the temp worktree using explicit Node commands:
- `node scripts/generate-og-images.mjs`
- `node scripts/generate-audit-og-images.mjs`
- `node node_modules/next/dist/bin/next build`

This produces static export output in `frontend/out`.

### 3. Deploy backend over SSH
On the server:
- reset repo to the selected commit
- run `composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader`
- run `php artisan migrate --force`
- run `php artisan db:seed --class=CreditPackageSeeder --force || true`
- refresh Laravel caches

### 4. Upload frontend static files
- upload built `frontend/out/*` to production via SFTP/SCP semantics through the deploy engine
- shared hosting serves the static frontend directly from production web root

### 5. Run local production smoke checks
After deployment, the local machine verifies production using:
- homepage loads
- login page loads

Smoke checks are currently executed through:
- `frontend/scripts/prod-smoke-check.mjs`

### 6. Write deployment result log
- mark deployment `success` or `failed`
- store per-step output for debugging

---

## Production Verification Policy

### Primary deployment-health checks
These are the current reliable deployment checks:
1. homepage loads correctly
2. login page loads correctly
3. deployment log finishes with `success`

### Important note about `/api/health`
A public `https://seoindicator.com/api/health` request is **not currently reliable from this machine** and may return Hostinger’s generic 404 page depending on access path. It is therefore **not** used as the deployment success gate.

### Important note about Playwright Test runner
The local Windows Playwright **test runner** is currently unreliable in this environment (`test.describe() did not expect...`). For deployment verification we therefore use a direct Playwright browser script instead of `playwright test`.

---

## Manual Usage

### Manual deploy from local machine
```powershell
powershell -ExecutionPolicy Bypass -File scripts\deploy-prod.ps1 -Trigger manual -GitRef origin/main -Branch main
```

### Optional flags
```powershell
powershell -ExecutionPolicy Bypass -File scripts\deploy-prod.ps1 -Trigger manual -GitRef origin/main -Branch main -SkipPlaywright
```

Use `-SkipPlaywright` only for debugging deployment mechanics. Normal production deployments should keep smoke verification enabled.

---

## Rollback Procedure

### Code rollback
```bash
ssh -p 65002 <user>@<host>
cd /home/.../public_html
git reset --hard <previous-commit>
cd backend
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan config:clear || true
php artisan cache:clear || true
php artisan config:cache || true
php artisan route:cache || true
```

### Database rollback
Use Laravel migrations only, and only with explicit intent:
```bash
php artisan migrate:rollback
```

---

## Troubleshooting

### Build fails locally
- run `npm install` in `frontend`
- verify `@next/env` and other Next.js dependencies are present
- if needed, delete and reinstall `frontend/node_modules`

### Git hook does not fire
- run `scripts/install-git-hooks.ps1`
- verify `git config --local --get core.hooksPath`
- expected value: `.githooks`

### UI-triggered deploy does nothing
- confirm `scripts/deploy-prod.ps1` exists
- confirm Python is available through `python` or `py`
- inspect `C:\Users\Raitis\.openclaw\logs\deployments`

### SSH deploy fails
- verify `SSH_*` credentials in `~/.openclaw/secrets/seoindicator.env`
- verify production host is reachable
- verify `GH_PAT` still grants repo access on server

### Production verification fails
- inspect latest deployment JSON log
- run `node frontend/scripts/prod-smoke-check.mjs` locally
- verify homepage/login behavior directly in browser

---

## Security Considerations

1. Production changes must go through the deploy scripts or Workflow Control Deploy tab.
2. Do not edit production files directly except explicit emergency rollback/debugging scenarios.
3. Do not store deployment secrets in the repository.
4. Use Laravel migrations for schema changes.
5. Use deployment logs as the source of truth for deployment history.

---

## Deployment Checklist

### Before deploy
- [ ] local code committed
- [ ] pushing the intended commit/ref
- [ ] frontend dependencies installed
- [ ] secrets file present and valid

### Successful deploy means
- [ ] local frontend build succeeded
- [ ] SSH/backend deployment succeeded
- [ ] smoke check script passed
- [ ] deployment JSON log shows `success`
- [ ] Workflow Control Deploy tab shows the run in history

---

Last updated: 2026-03-09
