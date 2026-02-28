# TASK-19-2: AnalysisJob and AnalysisJobResult Models

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create Eloquent models for AnalysisJob and AnalysisJobResult with relationships, accessors, and scopes.

## Acceptance Criteria
- [ ] AnalysisJob model with fillable: user_id, site_id, status, total_indicators, completed_indicators, failed_indicators, progress_percent, started_at, completed_at, cancelled_at, error_message
- [ ] AnalysisJobResult model with fillable: job_id, indicator_slug, indicator_name, status, score, max_score, percentage, data, recommendations, error_message, started_at, completed_at, processing_time_ms
- [ ] Relationships:
  - AnalysisJob belongsTo User, belongsTo Site, hasMany AnalysisJobResult
  - AnalysisJobResult belongsTo AnalysisJob
- [ ] Status scopes: pending(), running(), completed(), cancelled(), failed()
- [ ] Progress accessor: calculates percentage based on completed/total
- [ ] Duration accessor: calculates time elapsed from started_at to completed_at
- [ ] Cast JSON columns (data, recommendations) to array

## Dependencies
- TASK-19-1 (Database migration must be complete)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Use Laravel Eloquent conventions
- Add status constants for enum values
- Consider using enum casting (Laravel 9+)

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for Eloquent models. Required for service layer and API implementation. Low risk as it's standard model definitions.

### [2026-02-28 13:40 GMT+2] Agent: Developer
**Implementation Complete**

Created models with full functionality:
- `AnalysisJob` model with status constants, scopes, relationships, status helpers, and accessors
- `AnalysisJobResult` model with status constants, scopes, relationships, status helpers, and JSON casts
- Model factories for both models with state helpers

**Files Created:**
- `app/Models/AnalysisJob.php`
- `app/Models/AnalysisJobResult.php`
- `database/factories/AnalysisJobFactory.php`
- `database/factories/AnalysisJobResultFactory.php`

All model tests pass via `php artisan model:show`.
