# TASK-24-1: Database Migration - PWA Analyses Table

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create database migration for the `pwa_analyses` table to store cached PWA manifest analysis results. This is the foundation for the PWA indicator feature.

## Acceptance Criteria
- [ ] Migration creates `pwa_analyses` table with all columns from SPEC-024 schema:
  - id (BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
  - site_id (BIGINT UNSIGNED, foreign key to sites.id)
  - has_manifest, manifest_valid (TINYINT(1))
  - manifest_url (VARCHAR(500) nullable)
  - manifest_json (JSON nullable)
  - Field presence flags: has_name, has_short_name, has_start_url, has_display, has_background_color, has_theme_color, has_description (TINYINT(1))
  - Icon counts: icons_total, icons_valid (INT UNSIGNED)
  - Icon presence flags: icons_192_present, icons_512_present, icons_180_present (TINYINT(1))
  - icon_errors (JSON nullable)
  - pwa_score (TINYINT UNSIGNED, 0-100)
  - readiness_level (ENUM: none, basic, enhanced, full)
  - analyzed_at, created_at, updated_at (TIMESTAMP)
- [ ] Foreign key constraint: site_id → sites.id with CASCADE delete
- [ ] Indexes on: (site_id, analyzed_at), pwa_score
- [ ] Migration is reversible (down method drops table)
- [ ] Migration runs successfully on local MySQL

## Dependencies
- sites table must exist
- TASK-16-1 (Tools infrastructure - URL validation)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use Laravel migration syntax (PHP)
- JSON columns for flexible manifest and error storage
- ENUM for readiness_level with default 'none'
- TINYINT UNSIGNED for score (0-100 range)

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for database migration. Foundation task for PWA Indicator - must be completed before services can store analysis results. Set priority to High due to dependency chain. Reuses URL validation infrastructure from TASK-16-1 for SSRF protection.
