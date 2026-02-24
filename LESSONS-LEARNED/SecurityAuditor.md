# SecurityAuditor.md — Project Lessons Learned

Knowledge base for SecurityAuditor agent working on SEOIndicator project.

---

## Vulnerabilities Found

### 2026-02-21 → 2026-02-24: Critical Secrets Exposure

| Issue | Severity | Status |
|-------|----------|--------|
| APP_KEY hardcoded in deploy.yml | CRITICAL | Not fixed |
| APP_KEY in .env (local) | CRITICAL | Not fixed |
| DB password in .env (local) | CRITICAL | Not fixed |
| Secrets in documentation | CRITICAL | Not fixed |
| Deploy endpoints no rate limiting | MEDIUM | Not fixed |

**Root Cause:** Security spec created but tasks not yet prioritized/implemented.

**Timeline:**
- 2026-02-21: Initial discovery, spec created
- 2026-02-22: Follow-up audit, issues persist
- 2026-02-24: Third audit, issues still persist

---

## Security Best Practices

### OAuth Implementation (TASK-12-4)

**Working correctly:**
- Token encryption at rest via Laravel `encrypted` cast
- Redirect URI validation in OAuthService
- CSRF state parameter implemented
- Tokens hidden from serialization

**Needs attention:**
- Production OAuth credentials must be set
- Production redirect URIs need configuration
- Consider rate limiting on OAuth callback

### Deploy Endpoints

**Current protection:**
- DEPLOY_KEY authentication via `hash_equals()`

**Missing:**
- Rate limiting on migrate/rollback endpoints
- Logging of failed authentication attempts

---

## Halt Events

### 2026-02-21 21:28 GMT+2: SEOIndicator Deployment HALTED
**Reason:** CRITICAL security vulnerability - APP_KEY exposed in repository
**Status:** ONGOING - Not resolved as of 2026-02-24

---

## Gitignore Verification

**Correct behavior observed:**
- `backend/.env` is NOT tracked by git
- `.gitignore` correctly includes `.env` patterns
- No .env files in git history

**Issue:**
- `.github/workflows/deploy.yml` IS tracked and contains hardcoded secret

---

Last updated: 2026-02-24 18:15 GMT+2
