# TASK-33-2: Create Public Audit Views Table

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Create the public_audit_views table to track analytics for public audit page views. This enables measuring the reach and effectiveness of shared audits.

## Acceptance Criteria
- [ ] Migration creates `public_audit_views` table
- [ ] All columns from spec schema created:
  - `id`, `audit_id`, `public_hash`, `viewed_at`, `referrer`, `ip_hash`, `user_agent`, `country_code`, `is_lead_capture`, `lead_email`, `created_at`
- [ ] Foreign key constraint on audit_id with CASCADE delete
- [ ] Indexes on audit_id, public_hash, viewed_at for query performance
- [ ] PublicAuditView model created with relationships
- [ ] Factory created for testing
- [ ] Unit test verifies table structure and relationships

## Dependencies
- TASK-33-1 (visibility columns exist)

## Effort Estimate
**2 hours** - Standard migration with model

## Technical Notes
- IP addresses stored as SHA-256 hash + salt for privacy
- referrer column limited to 500 chars for long URLs
- country_code stored as ISO 2-char code
- is_lead_capture tracks if visitor submitted email

## Files to Create
- `backend/database/migrations/xxxx_create_public_audit_views_table.php`
- `backend/app/Models/PublicAuditView.php`
- `backend/database/factories/PublicAuditViewFactory.php`
- `backend/tests/Unit/Models/PublicAuditViewTest.php`

## Comments

### [2026-03-05 23:20 GMT+2] ProjectManager - Prioritization Assessment

**Decision:** HIGH priority confirmed ✅

**Priority Assessment:**
- **Foundation Task:** Phase 1 database schema for public audit analytics
- **Dependency Satisfied:** TASK-33-1 (visibility columns) is Done ✅
- **Enables Downstream:** This table is required for:
  - TASK-33-3 (PublicAuditController - needs model)
  - TASK-33-4 (PublicAudit model - relates to views)
  - TASK-33-5 (PublicAuditService - queries view counts)
  - Analytics and reporting features throughout SPEC-33
- **Effort:** 2 hours (low effort, high impact)

**Risk Assessment: LOW (confirmed)**
- Standard Laravel migration (non-destructive, adds table only)
- No external APIs
- Privacy-conscious design (IP hashed with salt)
- Standard Eloquent model with relationships

**Key Technical Points:**
- 11 columns: id, audit_id, public_hash, viewed_at, referrer, ip_hash, user_agent, country_code, is_lead_capture, lead_email, created_at
- Foreign key CASCADE delete (views removed when audit deleted)
- 3 indexes for query performance (audit_id, public_hash, viewed_at)
- IP privacy via SHA-256 hash + salt
- Lead capture tracking for conversion analytics

**TDD Ready:** PreTester can design tests immediately
- Test migration creates all columns correctly
- Test foreign key constraint works (cascade delete)
- Test indexes exist for performance
- Test model relationships (belongsTo Audit)
- Test factory generates valid data

**Handoff to PreTester:** Ready for test design. All acceptance criteria are testable.
