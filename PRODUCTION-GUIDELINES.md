# PRODUCTION-GUIDELINES.md — Production Access Rules

**Last updated:** 2026-03-09

---

## ⚠️ CRITICAL RULES

### 1. Production is for running deployed code, not for ad-hoc development

| Action | Allowed? | Reason |
|--------|----------|--------|
| Read logs | ✅ Yes | Debugging |
| Check git status | ✅ Yes | Debugging |
| Read files | ✅ Yes | Debugging |
| Run rollback commands | ✅ With care | Recovery |
| **Modify files directly** | ❌ No | Use deployment |
| **Edit DB directly** | ❌ No | Use migrations |
| **Install packages manually as normal workflow** | ❌ No | Use deployment |
| **Hot-edit application code** | ❌ No | Breaks source-of-truth |

### 2. All normal changes go through the local deployment pipeline

```text
Local change → git commit → git push main → local post-push / manual deploy → production
```

Normal deployment paths:
- `scripts/deploy-prod.ps1`
- Workflow Control → **Deploy** tab

GitHub-hosted Actions are no longer the deployment path.

### 3. SSH access purpose

SSH access is for:
- ✅ debugging deployment issues
- ✅ checking logs
- ✅ verifying deployed state
- ✅ emergency rollback
- ✅ confirming file layout / permissions

SSH access is NOT for:
- ❌ normal feature development
- ❌ editing code directly on server
- ❌ bypassing source control
- ❌ bypassing deployment logging

---

## Deployment Workflow

### Normal deployment
```powershell
# local machine
git add -A
git commit -m "your message"
git push origin main
# post-push hook or manual deploy handles rollout
```

### Manual deployment
```powershell
powershell -ExecutionPolicy Bypass -File scripts\deploy-prod.ps1 -Trigger manual -GitRef origin/main -Branch main
```

### Emergency rollback
```bash
ssh -p 65002 u949031532@45.84.206.11
cd /home/u949031532/domains/seoindicator.com/public_html
git reset --hard <previous-commit>
cd backend
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan config:clear || true
php artisan cache:clear || true
php artisan config:cache || true
php artisan route:cache || true
```

---

## Production Verification

### Current reliable deployment checks
- homepage loads
- login page loads
- deployment log finishes with `success`

### Important note
`https://seoindicator.com/api/health` is **not currently treated as a reliable deployment gate from this local environment**, because some access paths return a Hostinger generic 404 page.

### Smoke check command
```bash
cd frontend
node scripts/prod-smoke-check.mjs
```

---

## Untracked Files on Production

Frontend static export artifacts will appear outside the git-tracked backend source layout. That is expected.

Examples:
- `_next/`
- `index.html`
- route HTML files
- generated OG images
- static assets copied from frontend build output

These are deployment artifacts, not server-side development files.

---

## Database Changes

### All DB changes use migrations
```bash
cd backend
php artisan make:migration your_migration_name
```

Then:
- edit migration locally
- commit locally
- deploy normally
- let deployment run `php artisan migrate --force`

### Never modify DB directly in production
- ❌ no manual schema edits
- ❌ no ad-hoc SQL as normal workflow
- ✅ use Laravel migrations

---

## Incident Response

### If site behavior looks wrong after deploy
1. inspect latest deployment JSON log
2. run local production smoke script
3. inspect Laravel logs over SSH
4. confirm deployed commit on server
5. create a follow-up task if smoke passes but product behavior is still wrong

### If deployment fails
1. inspect latest JSON deployment log
2. identify failing step
3. fix locally
4. redeploy

---

## Access Credentials

| Credential | Location | Purpose |
|------------|----------|---------|
| SSH | `~/.openclaw/secrets/seoindicator.env` | deploy + debugging |
| GH_PAT | `~/.openclaw/secrets/seoindicator.env` | private repo access on server |
| App / DB secrets | production `.env` / local secrets | runtime configuration |

---

## Golden Rule

Production is a deployment target, not an editing workspace.

Use:
- source control
- local build/test flow
- deploy scripts
- deployment logs

Do not use production as a shortcut around the pipeline.
