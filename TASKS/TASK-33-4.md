# TASK-33-4: Implement PublicAudit Model

## STATUS
`To Develop`

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

### [2026-03-06 15:58 GMT+2] PreTester - Test Verification Complete ✅

**Status:** To Write Tests → To Develop

**Test Files Verified:**
- `backend/tests/Unit/Models/PublicAuditTest.php` - 70+ test scenarios across 13 categories
- `backend/database/factories/PublicAuditFactory.php` - 8 factory states
- `TESTS/TEST-33-4.md` - Comprehensive test documentation

**Test Categories:**
| Category | Tests | Coverage |
|----------|-------|----------|
| MI: Model Instantiation | 5 | Table, fillable, defaults |
| RE: Relationships | 3 | BelongsTo Audit, cascade delete |
| GH: generateHash() | 6 | 32-char, alphanumeric, unique, crypto |
| PB: publish() | 6 | Visibility, hash, timestamp, persistence |
| UP: unpublish() | 5 | Visibility, hash clearing, persistence |
| IP: isPublic() | 4 | Visibility check, null handling |
| FH: findByHash() | 7 | Retrieval, null cases, case sensitivity |
| UN: Uniqueness | 3 | Collision detection, unique constraint |
| TR: Transaction Safety | 3 | Transaction wrap, rollback, concurrent |
| SC: Security | 4 | Predictability, SQL injection, XSS, private access |
| EC: Edge Cases | 8 | Double publish/unpublish, rapid cycles, unicode |
| SQ: Model Scopes | 3 | public(), private(), withHash() |
| ACC: Accessors | 2 | public_url accessor |
| AC: Acceptance Criteria | 8 | All ACs covered |

**Factory States:**
- `published()` - Public audit with hash
- `unpublished()` - Private audit
- `withHash($hash)` - Custom hash
- `forAudit($audit)` - Link to specific audit
- `publishedAt($date)` - Time-based testing
- `publishedDaysAgo($days)` - Aging tests
- `recentlyPublished()` - Fresh public audit
- `oldHash()` - Long-published audit

**TDD Pattern:** Tests will fail until:
1. Migration creates `public_audits` table (MISSING - needs to be created)
2. PublicAudit model implemented with all methods

**Important Note for Developer:**
- Tests expect `public_audits` table with columns: `audit_id`, `public_hash`, `visibility`, `public_hash_created_at`
- **Migration required:** Create `xxxx_create_public_audits_table.php` migration
- Factory already exists at `backend/database/factories/PublicAuditFactory.php`
- Consider if PublicAudit should be a wrapper for Audit's existing visibility columns or a separate table

**Dependency Status:**
- TASK-33-1 (visibility columns in audits table): Done ✅
- Migration for `public_audits` table: **MISSING** - Developer needs to create

**Test Execution:**
```bash
php artisan test --filter PublicAuditTest
```

**Expected Result:** 67 failures (Class "App\Models\PublicAudit" not found)

**Handoff to Developer:** Ready for implementation. Create migration first, then implement model.

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
