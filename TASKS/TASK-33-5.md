# TASK-33-5: Create PublicAuditController with Endpoints

## STATUS
`To Prioritize`

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
