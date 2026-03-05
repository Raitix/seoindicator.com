# TASK-33-4: Implement PublicAudit Model

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Create the PublicAudit model that handles public audit data retrieval, hash generation, and visibility management. This is the core model for public audit functionality.

## Acceptance Criteria
- [ ] PublicAudit model created with audit relationship
- [ ] Method `generateHash()` creates 32-char alphanumeric string
- [ ] Method `publish()` sets visibility='public' and generates hash
- [ ] Method `unpublish()` sets visibility='private' and clears hash
- [ ] Method `isPublic()` checks visibility status
- [ ] Method `findByHash(string $hash)` retrieves public audit or null
- [ ] Hash uniqueness guaranteed (regenerate on collision)
- [ ] Unit tests for all public methods
- [ ] Hash is cryptographically random (not ID-derived)

## Dependencies
- TASK-33-1 (visibility columns in audits table)

## Effort Estimate
**3 hours** - Model with business logic

## Technical Notes
- Use `Str::random(32)` for hash generation
- Wrap publish in transaction to prevent race conditions
- Include timestamp tracking for hash creation
- Consider cache layer for frequently accessed public audits

## Files to Create/Modify
- `backend/app/Models/PublicAudit.php` (new)
- `backend/app/Models/Audit.php` (add publicAudit relationship)
- `backend/tests/Unit/Models/PublicAuditTest.php`
