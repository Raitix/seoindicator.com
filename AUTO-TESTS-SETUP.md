# AUTO-TESTS-SETUP.md

## Testing Philosophy
- All new features require automated tests.
- Tests should be written alongside implementation, or earlier when practical.
- Local validation is mandatory before deployment.
- Production deployment must finish with smoke verification from the local machine.

---

## Test Layers

| Layer | Location | Purpose |
|------|----------|---------|
| Backend feature / integration | `backend/tests/Feature/`, `backend/tests/Integration/` | Validate Laravel endpoints, workflows, database behavior |
| Backend unit | `backend/tests/Unit/` | Validate services, models, helpers, rules |
| Frontend unit / component | `frontend/**/*.test.*`, `frontend/**/*.spec.*` | Validate components, utilities, hooks, UI behavior |
| Frontend E2E | `frontend/e2e/` | Validate user flows in browser |
| Repository-level test specs | `TESTS/` | Human-readable test plans, expected coverage, task-level evidence |
| Deployment smoke | `frontend/scripts/prod-smoke-check.mjs` | Validate key production routes after deploy |

---

## Current Test Commands

### Backend
```bash
cd backend
composer test
# or
php artisan test
```

### Frontend unit tests
```bash
cd frontend
npm test
```

### Frontend lint
```bash
cd frontend
npm run lint
```

### Frontend Playwright E2E
```bash
cd frontend
npx playwright test
```

### Production smoke verification
```bash
cd frontend
node scripts/prod-smoke-check.mjs
```

---

## Important Environment Note

The local Windows Playwright **test runner** is currently unreliable in this environment and may fail before test execution with runner-level errors.

Because of that:
- normal E2E specs can still exist and remain valuable
- but **deployment verification must not depend solely on `playwright test`** on this machine
- production deployment uses the direct Playwright browser smoke script instead:
  - `frontend/scripts/prod-smoke-check.mjs`

This is the current source of truth for production deployment verification.

---

## Deployment Verification Standard

A deployment is considered verified only when:
1. frontend build succeeds locally
2. backend deployment succeeds over SSH
3. local production smoke script passes
4. deployment log is recorded as `success`

### Current smoke assertions
- homepage loads
- login page loads

### Not currently used as deployment gate
- public `/api/health` from this machine, because it may return Hostinger’s generic 404 page depending on access path
- Playwright test-runner-based deployment checks on Windows

---

## Local Quality Gate Expectations by Agent

### Developer
- add/update automated tests for the changed code
- run the narrowest relevant local test set first
- escalate to broader suite when touching cross-cutting behavior

### LocalTester
- run local validation suites
- confirm regressions are covered by automated tests
- document gaps in `TESTS/TEST-*.md`

### DevOps
- confirm deployment automation still works
- confirm production smoke script passes after deployment
- review deployment log output

### ProdTester
- validate production behavior after deploy
- if deployment smoke passes but product behavior is wrong, create follow-up task immediately

---

## CI / Automation Policy

SEOIndicator no longer relies on GitHub-hosted Actions for deployment.

### Current automation model
- local development machine runs tests and build checks
- local/self-hosted deployment script performs production rollout
- post-deploy validation is executed locally against production
- Workflow Control Deploy tab exposes deployment history and status

### Practical implication
There is **no GitHub Actions deployment gate** anymore. The real deployment gate is:
- local validation
- successful deploy script execution
- successful production smoke verification

---

## Test Documentation
- Each implementation task should have corresponding documentation in `TESTS/TEST-*.md` or `TESTS/SPEC-*.md`.
- Document:
  - scenarios covered
  - edge cases covered
  - known exclusions
  - manual follow-up needed, if any

---

## Minimum Expectations

| Change Type | Minimum Required Validation |
|------------|-----------------------------|
| UI copy / styling only | relevant frontend unit checks + manual browser sanity |
| Frontend functional change | frontend unit/component coverage + manual path check |
| Backend endpoint / logic | backend automated tests |
| Cross-stack feature | backend tests + frontend tests + targeted browser verification |
| Deployment-related change | full deploy path + production smoke script |

---

Last updated: 2026-03-09
