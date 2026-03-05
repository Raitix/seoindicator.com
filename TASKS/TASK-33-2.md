# TASK-33-2: Create Public Audit Views Table

## STATUS
`To Prioritize`

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
