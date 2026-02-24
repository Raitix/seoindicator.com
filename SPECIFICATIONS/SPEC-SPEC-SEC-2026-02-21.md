# SPEC-SPEC-SEC-2026-02-21: Security Remediation for Hardcoded Secrets and OAuth Review — Technical Specification

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`High`

## RISK
`High`

## Overview
Remediate critical credential exposure and harden deployment/OAuth endpoints. This spec operationalizes the SecurityAuditor findings in SPEC-SEC-2026-02-21 with concrete implementation steps, tests, and risk controls.

## Requirements
### Functional
- Remove all hardcoded secrets (APP_KEY, DB password, OAuth secrets) from tracked files.
- Store secrets in environment variables / GitHub Secrets only.
- Rotate APP_KEY and ensure deployment uses the new secret.
- Implement rate limiting on deploy endpoints and log failed attempts.
- Verify OAuth production configuration (redirect URIs, secret presence, token encryption).
- Purge secrets from git history.

### Non-Functional
- No paid services or subscriptions.
- Minimal downtime; communicate session invalidation due to APP_KEY rotation.
- Safe rollback plan if deployment breaks.

## Technical Design
### Architecture
- Secrets resolved via environment variables at runtime (Laravel config).
- GitHub Actions deploy workflow references GitHub Secrets for APP_KEY and DB_PASSWORD.
- Deploy endpoints protected by Laravel throttle middleware and logging.
- OAuth config validated in production environment and stored via env variables.

### Components
- `.github/workflows/deploy.yml`
- `backend/.env` and `backend/.env.example`
- `backend/app/Http/Controllers/DeployController.php`
- `backend/app/Providers/RouteServiceProvider.php` (or `Http/Kernel.php` for limiter registration)
- `backend/app/Services/OAuthService.php` (verification only)

### Data Flow
1. GitHub Actions deploy job injects secrets from repository secrets.
2. Laravel loads secrets from environment at boot.
3. Deploy endpoints enforce rate limits and log failed auth attempts.
4. OAuth flow validates redirect URI and encrypts tokens at rest.

### API Design
No new external API endpoints required. Existing `/deploy/migrate` and `/deploy/rollback` receive throttle middleware.

## Implementation Notes
- Use `php artisan key:generate --show` to generate new APP_KEY; update GitHub Secret `LARAVEL_APP_KEY`.
- Ensure `.env` is not committed; `.env.example` holds placeholders only.
- Purge leaked secrets from git history using `git filter-repo` (or BFG if available) and force-push as needed (coordinate with owner).
- Rate limiting: `throttle:5,15` on deploy endpoints; optionally define `RateLimiter::for('deploy', ...)` and use `throttle:deploy`.
- Logging: on failed authentication in deploy endpoints, log IP, user-agent, timestamp.
- OAuth: verify production redirect URI and secrets are set in env; confirm encryption on stored tokens (check model mutators/casts).

## Dependencies
- Access to GitHub Secrets configuration
- Production DB credential update
- OAuth implementation task completion (TASK-12-4)

## Risks & Mitigations
- **High:** APP_KEY rotation invalidates sessions → communicate and schedule during low-traffic window.
- **High:** Git history rewrite can disrupt collaborators → coordinate and provide reset instructions.
- **Medium:** Rate limiting may block legitimate deploys → keep limits conservative and adjust if needed.
- **Medium:** OAuth misconfiguration → verify redirect URIs in Google Console before enabling.

## Tasks
- [ ] SPEC-SEC-2026-02-21-1: Remove hardcoded secrets from repo files; update `.env`/`.env.example` placeholders
- [ ] SPEC-SEC-2026-02-21-2: Rotate APP_KEY and set GitHub Secrets; update deploy workflow to use secrets
- [ ] SPEC-SEC-2026-02-21-3: Implement deploy endpoint rate limiting and failure logging
- [ ] SPEC-SEC-2026-02-21-4: Validate OAuth production config and token encryption
- [ ] SPEC-SEC-2026-02-21-5: Purge secrets from git history and verify repository clean

## Related
- Feature: SPEC-SEC-2026-02-21
- Source Spec: SPEC-SEC-2026-02-21
- Tests: TESTS/SPEC-SEC-2026-02-21-*.md

---

## Comments

### [2026-02-23 01:06] Agent: Architect
Derived this technical specification from SecurityAuditor spec. Ready for task breakdown; high-risk item requiring SecurityAuditor review before deployment.

### [2026-02-24 18:15] Agent: SpecSystemAnalyst
Broke specification into 5 tasks:
- SPEC-SEC-2026-02-21-1: Remove hardcoded secrets from repo files
- SPEC-SEC-2026-02-21-2: Rotate APP_KEY and configure GitHub Secrets
- SPEC-SEC-2026-02-21-3: Implement deploy endpoint rate limiting and logging
- SPEC-SEC-2026-02-21-4: Validate OAuth production configuration
- SPEC-SEC-2026-02-21-5: Purge secrets from git history

Task dependencies:
- Task 1 is the foundation (no dependencies)
- Task 2 depends on Task 1
- Task 3 is independent (can run in parallel)
- Task 4 depends on Task 1
- Task 5 depends on Tasks 1 and 2 (final cleanup)

All tasks are High priority due to security nature. Status updated to "To Prioritize" for ProjectManager.

### [2026-02-24 19:16] Agent: ProjectManager
**Prioritization Complete.** All 5 tasks assigned **High** priority per security override rule.

**Execution Order (dependency-aware):**
| Order | Task | Priority | Dependencies | Notes |
|-------|------|----------|--------------|-------|
| 1 | SPEC-SEC-2026-02-21-1 | High | None | Foundation - start here |
| 2 | SPEC-SEC-2026-02-21-3 | High | None | Parallel with Task 1 |
| 3 | SPEC-SEC-2026-02-21-2 | High | Task 1 | After secrets removed |
| 4 | SPEC-SEC-2026-02-21-4 | High | Task 1 | Parallel with Task 2 |
| 5 | SPEC-SEC-2026-02-21-5 | High | Tasks 1, 2 | Final - git history purge |

**Parallelization Opportunities:**
- Tasks 1 and 3 can run simultaneously (independent)
- Tasks 2 and 4 can run simultaneously (both depend only on Task 1)

Status updated to "To Write Tests". Ready for PreTester.
