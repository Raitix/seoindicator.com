# TASK-33-1: Add Visibility Columns to Audits Table

## STATUS
`To Prioritize`

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

## Files to Modify
- `backend/database/migrations/xxxx_add_visibility_to_audits.php`
- `backend/app/Models/Audit.php`
- `backend/tests/Unit/Models/AuditTest.php`
