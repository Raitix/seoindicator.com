# TASK-33-5: Create PublicAuditController with Endpoints

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Implement the PublicAuditController with endpoints for retrieving public audit data, lead capture, and badge configuration. This is the main API for public-facing audit pages.

## Acceptance Criteria
- [ ] GET `/api/v1/public-audit/{hash}` returns audit data or 404
- [ ] Response includes: siteUrl, auditedAt, overallScore, categoryScores, topIssues
- [ ] Response includes `isPartial` flag for anonymous users
- [ ] POST `/api/v1/public-audit/{hash}/lead` accepts email for lead capture
- [ ] Lead endpoint validates email format
- [ ] Lead endpoint stores email with audit reference in public_audit_views
- [ ] GET `/api/v1/public-audit/{hash}/badge` returns badge configuration
- [ ] Rate limiting: 100 requests/hour per hash for anonymous users
- [ ] No authentication required for public endpoints
- [ ] 404 returned for invalid/expired/non-public hashes
- [ ] Feature tests for all endpoints

## Dependencies
- TASK-33-4 (PublicAudit model)

## Effort Estimate
**4 hours** - Controller with multiple endpoints

## Technical Notes
- Use route model binding with custom key (public_hash)
- Exclude sensitive data from public response
- Track views in public_audit_views table
- Rate limiting uses IP + hash combination

## API Response Example
```json
{
  "siteUrl": "example.com",
  "auditedAt": "2026-03-05T12:00:00Z",
  "overallScore": 85,
  "categoryScores": [...],
  "topIssues": [...],
  "isPartial": true
}
```

## Files to Create
- `backend/app/Http/Controllers/Api/V1/PublicAuditController.php`
- `backend/app/Http/Requests/PublicAuditLeadRequest.php`
- `backend/tests/Feature/Api/V1/PublicAuditControllerTest.php`

## Comments

### [2026-03-06 14:23 GMT+2] ProjectManager - Prioritization Assessment

**Decision:** HIGH priority confirmed ✅

**Priority Assessment:**
- **Core API Component:** Main controller for public-facing audit pages - enables the primary user journey
- **Multiple Endpoints:** GET (audit data), POST (lead capture), GET (badge config) - comprehensive API surface
- **Business Critical:** Lead capture endpoint is conversion mechanism for anonymous users
- **Effort:** 4 hours (moderate, well-defined scope)

**Risk Assessment: MEDIUM (confirmed)**
- New read-only endpoints (GET) - Low risk
- POST endpoint for lead capture - Medium risk (data persistence, email validation)
- Rate limiting implementation - Medium risk (DoS protection, IP tracking)
- No authentication required - Medium risk (security considerations for public exposure)
- Overall: MEDIUM is appropriate

**Dependency Status:**
- TASK-33-4 (PublicAudit Model): Status `To Write Tests` - NOT YET SATISFIED
  - Model must exist for controller to use `findByHash()`, `isPublic()` methods
  - PreTester can design tests using interface-based mocking (TDD approach)
  - Developer should wait for TASK-33-4 to reach `To Test Locally` before implementing

**Downstream Impact:**
- TASK-33-7 (API Routes) - BLOCKED until this controller exists
- TASK-33-8 (PublicAuditPage Component) - BLOCKED until API endpoint available
- All Phase 2 Frontend tasks - INDIRECTLY BLOCKED

**Key Technical Points:**
- Route model binding with custom key (public_hash) - Laravel feature
- Rate limiting: 100 req/hour per hash for anonymous users - uses IP + hash combination
- Response filtering: `isPartial` flag differentiates anonymous vs authenticated users
- Security: Exclude sensitive data, validate all inputs, 404 for invalid hashes

**TDD Ready:** PreTester can design tests immediately using:
- Mock PublicAudit model interface for unit tests
- Feature tests can wait for model implementation
- Test rate limiting behavior (mock Redis/cache)
- Test email validation scenarios
- Test 404 handling for invalid/expired hashes

**Handoff to PreTester:** Ready for test design. Note dependency on TASK-33-4 for integration tests.
