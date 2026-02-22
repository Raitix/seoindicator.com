# SPEC-001-21: Production Health Endpoint Missing (Docs Discrepancy)

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Description
DOCUMENTATION.md states a production health endpoint exists at `GET /api/health`, but production returns 404. Align implementation with documentation or update docs to reflect reality.

## Acceptance Criteria
- [ ] If health endpoint is intended: `GET /api/health` returns `{ "status": "ok", "database": "connected" }` with HTTP 200 in production
- [ ] If endpoint is not intended: update DOCUMENTATION.md to remove or correct the health endpoint reference
- [ ] Automated test coverage added for health endpoint behavior (or explicit doc update test)

## Dependencies
- None

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Check backend route registration for `/api/health`
- Ensure prod routing is aligned with local dev
- If removing endpoint, ensure no callers depend on it

---

## Comments
_Agent communication log_

### [2026-02-22 23:10] Agent: ProdTester
Detected production doc mismatch: `https://seoindicator.com/api/health` returns 404, but DOCUMENTATION.md lists it as available. Created task per discrepancy rule.
