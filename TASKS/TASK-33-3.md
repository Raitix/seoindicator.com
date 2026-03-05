# TASK-33-3: Create Badge Impressions Table

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Create the badge_impressions table to track analytics for embedded badge views and clicks. This measures the reach of embedded widgets on third-party sites.

## Acceptance Criteria
- [ ] Migration creates `badge_impressions` table
- [ ] All columns from spec schema created:
  - `id`, `audit_id`, `public_hash`, `referrer_domain`, `impression_at`, `clicked_at`, `click_target`, `ip_hash`
- [ ] Foreign key constraint on audit_id with CASCADE delete
- [ ] Indexes on audit_id, public_hash, impression_at for query performance
- [ ] BadgeImpression model created with relationships
- [ ] Factory created for testing
- [ ] Unit test verifies table structure and relationships

## Dependencies
- TASK-33-1 (visibility columns exist)

## Effort Estimate
**2 hours** - Standard migration with model

## Technical Notes
- referrer_domain extracted from HTTP referer header (domain only, no path)
- click_target indicates where user clicked: 'audit' or 'home'
- clicked_at initially NULL, set when click event received
- IP hashed for privacy compliance

## Files to Create
- `backend/database/migrations/xxxx_create_badge_impressions_table.php`
- `backend/app/Models/BadgeImpression.php`
- `backend/database/factories/BadgeImpressionFactory.php`
- `backend/tests/Unit/Models/BadgeImpressionTest.php`
