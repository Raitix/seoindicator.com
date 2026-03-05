# TASK-33-7: Create Public Audit API Routes

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Define all API routes for public audit functionality in the Laravel routes file. This includes public endpoints (no auth) and authenticated audit management endpoints.

## Acceptance Criteria
- [ ] Public routes (no authentication):
  - GET `/api/v1/public-audit/{hash}`
  - POST `/api/v1/public-audit/{hash}/lead`
  - GET `/api/v1/public-audit/{hash}/badge`
  - POST `/api/v1/badge/impression`
  - POST `/api/v1/badge/click`
- [ ] Authenticated routes (require auth:sanctum):
  - POST `/api/v1/audits/{id}/publish`
  - DELETE `/api/v1/audits/{id}/unpublish`
  - GET `/api/v1/audits/{id}/embed-code`
- [ ] Rate limiting middleware applied
- [ ] Routes documented with route:list command
- [ ] Integration test verifies route accessibility

## Dependencies
- TASK-33-5 (PublicAuditController)
- TASK-33-17, TASK-33-18 (badge tracking endpoints)

## Effort Estimate
**1 hour** - Route definitions

## Technical Notes
- Use route groups for public vs authenticated
- Apply `throttle:public-audit` rate limiter to public routes
- Use route model binding with Audit model
- Publish/unpublish endpoints check user ownership

## Files to Modify
- `backend/routes/api.php`
- `backend/app/Providers/RouteServiceProvider.php` (rate limiter)
