# TASK-33-1: Add Visibility Columns to Audits Table

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Add visibility, public_hash, and public_hash_created_at columns to the audits table to support public audit sharing. This enables users to control whether their audits are publicly accessible.

## Acceptance Criteria
- [ ] Migration creates `visibility` ENUM column ('private', 'public') with default 'private'
- [ ] Migration creates `public_hash` VARCHAR(32) UNIQUE NULL column
- [ ] Migration creates `public_hash_created_at` TIMESTAMP NULL column
- [ ] Migration is reversible (down method removes columns)
- [ ] Audit model updated with fillable attributes
- [ ] Audit model updated with casts for visibility enum
- [ ] Index added on public_hash for fast lookups
- [ ] Existing audits remain private (default value applied)
- [ ] Unit test verifies column existence and defaults

## Dependencies
- None (foundation task)

## Effort Estimate
**2 hours** - Simple migration with model updates

## Technical Notes
- Use Laravel's enum column type for visibility
- public_hash should be nullable (only set when user publishes)
- Consider adding a generated unique hash method to Audit model
- Index public_hash for O(1) lookup performance on public pages

## Comments

### [2026-03-05 21:23 GMT+2] ProjectManager - Prioritization Assessment

**Decision:** HIGH priority confirmed ✅

**Priority Assessment:**
- **Foundation Task:** This is the first task in Phase 1 of SPEC-33, enabling all 34 downstream tasks
- **No Dependencies:** Can start immediately - no blocking tasks
- **Critical Path:** All other tasks in SPEC-33 depend on visibility columns existing
- **Effort:** 2 hours (low effort, high impact)

**Risk Assessment: MEDIUM (confirmed)**
- Non-destructive migration (adds columns only)
- Existing audits remain private (default value applied)
- Migration is reversible (down method removes columns)
- Standard Laravel migration patterns

**Key Technical Points:**
- Adds `visibility` ENUM column with default 'private'
- Adds `public_hash` VARCHAR(32) UNIQUE NULL for public URL access
- Adds `public_hash_created_at` TIMESTAMP NULL for audit trail
- Index on public_hash for O(1) lookup performance

**TDD Ready:** PreTester can design tests immediately
- Test migration creates all columns correctly
- Test default values applied to existing audits
- Test model fillable attributes and casts
- Test index exists on public_hash

**Handoff to PreTester:** Ready for test design. All acceptance criteria are testable.
