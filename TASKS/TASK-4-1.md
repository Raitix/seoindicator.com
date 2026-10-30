# SPEC-004-1: Database Migration - Reports Tables

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Feature: [FEAT-004](../FEATURES/FEAT-004.md)
- Specification: [SPEC-004](../SPECIFICATIONS/SPEC-004.md)
- Test: TESTS/SPEC-004-1.md

## Description
Create database migrations for `reports`, `report_templates`, `scheduled_reports`, and `report_branding` tables. This is the foundation for the automated SEO reports feature.

## Acceptance Criteria
- [ ] Migration creates `reports` table with: id, site_id, name, type, format, status, date_range_start, date_range_end, compare_range_start, compare_range_end, sections (JSON), generated_at, file_path, file_size, error, created_at
- [ ] Migration creates `report_templates` table with: id, site_id, name, is_default, sections (JSON), branding (JSON), cover_page_text, executive_summary, created_at, updated_at
- [ ] Migration creates `scheduled_reports` table with: id, site_id, template_id, name, frequency, format, recipients (JSON), enabled, last_run, next_run, created_at
- [ ] Migration creates `report_branding` table with: id, site_id (unique), logo_url, logo_file, primary_color, secondary_color, font_family, company_name, company_website, created_at, updated_at
- [ ] Foreign key constraints: reports.site_id → sites.id, scheduled_reports.site_id → sites.id, scheduled_reports.template_id → report_templates.id, report_branding.site_id → sites.id
- [ ] Indexes on: reports.site_id, reports.status, reports.generated_at, report_templates.site_id, report_templates.is_default, scheduled_reports.site_id, scheduled_reports.next_run, scheduled_reports.enabled, report_branding.site_id
- [ ] Migration is reversible (down method drops tables)
- [ ] Migration runs successfully on local database

## Dependencies
- FEAT-001 (sites table must exist)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use Laravel migration syntax
- Tables follow existing naming conventions
- JSON columns for flexible data storage (sections, recipients, branding)
- Consider soft deletes for templates (allow re-activation)

---

## Comments
_Agent communication log_

### [2026-02-20 10:30] Agent: SpecSystemAnalyst
Created as Phase 1 foundation task. Must be completed before any other report tasks can proceed.

### [2026-02-22 18:30] Agent: Developer
- Added migration 2026_02_22_182500_create_reports_tables.php for reports, report_templates, scheduled_reports, report_branding
- Included indexes, foreign keys, and reversible down() drops

### [2026-02-22 20:40] Agent: LocalTester
- Ran migration: `php artisan migrate --path=database/migrations/2026_02_22_182500_create_reports_tables.php` ✅
- Rollback step=1 ✅
- Re-apply migration ✅
- Documented tests in TESTS/SPEC-004-1.md
- Status → To Deploy

### [2026-02-22 23:56] Agent: DevOps
- Status → In Deployment
- Triggered GitHub Actions workflow: "Deploy to Production" (run id: 22286139500)
- Workflow completed successfully; build annotations noted missing Playwright report artifact and an exit code 1 message in annotations, but deploy job completed
- Health check: https://seoindicator.com/api/health → 200 (db connected)
- Homepage: https://seoindicator.com/ → 200
- Status → To Test Prod

### [2026-02-26 17:38] Agent: DocSystemAnalyst
- Added database schema documentation to DOCUMENTATION.md:
  - Reports table (metadata, file references, status tracking)
  - Report Templates table (reusable configurations)
  - Scheduled Reports table (automation configuration)
  - Report Branding table (white-label settings)
- All tables include field descriptions, constraints, and indexes
- Task already marked Done; documentation update completed
- SPEC-004 status: Done
- FEAT-004 status: Done
