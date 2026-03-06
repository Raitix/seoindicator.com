# TASK-33-4: Implement PublicAudit Model

## STATUS
`To Write Tests`

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

## Comments

### [2026-03-06 14:05 GMT+2] ProjectManager - Prioritization Assessment

**Decision:** HIGH priority confirmed ✅

**Priority Assessment:**
- **Foundation Model:** Core model for public audit sharing functionality
- **Enables Downstream:** TASK-33-5 (Controller), TASK-33-7 (Routes), All Phase 2+ frontend
- **Critical Path:** First model after database foundation, essential for MVP
- **Effort:** 3 hours (low effort, high impact)

**Risk Assessment: LOW (confirmed)**
- Standard Eloquent model implementation
- No external API dependencies
- Business logic only: hash generation, publish/unpublish, visibility management
- Uses Laravel's Str::random(32) for cryptographic randomness

**Dependency Status:**
- TASK-33-1 (visibility columns): Done ✅ - SATISFIED
- TASK-33-2 (public_audit_views): Done ✅ - SATISFIED
- TASK-33-3 (badge_impressions): To Deploy ✅ - SATISFIED

**Downstream Impact:**
- TASK-33-5 (PublicAuditController) - BLOCKED until this model exists
- TASK-33-7 (API Routes) - BLOCKED until controller exists
- All Phase 2 Frontend tasks - BLOCKED until API exists

**Key Technical Points:**
- `generateHash()` - Creates 32-char alphanumeric string with collision detection
- `publish()` - Sets visibility='public', generates hash, wraps in transaction
- `unpublish()` - Sets visibility='private', clears hash
- `isPublic()` - Checks visibility status
- `findByHash(string $hash)` - Retrieves public audit or null

**TDD Ready:** PreTester can design tests immediately
- Test hash generation (32 chars, alphanumeric, unique)
- Test publish/unpublish workflow
- Test findByHash retrieval
- Test visibility checks
- Test transaction safety for concurrent publish

**Handoff to PreTester:** Ready for test design. All dependencies satisfied.
