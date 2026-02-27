# TASK-12-5: Implement GSC Data Sync Service

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Develop `GSCDataSync` service to fetch and store Search Console data via API. Handle batching, rate limits, and daily sync jobs.

## Responsibilities
1. **Data Fetching**
   - Query GSC API for clicks, impressions, CTR, position.
   - Support date range queries for historical import.
   - Batch requests to respect API quotas.

2. **Rate Limiting**
   - Implement exponential backoff on quota errors.
   - Track API usage to prevent limits.

3. **Data Storage**
   - Upsert data into `gsc_data` table.
   - Deduplicate records by project, query, and date.

## Code Example
```php
class GSCDataSync
{
    public function syncProject(Project $project, ?string $fromDate = null): void;
    public function fetchBatch(GSCConnection $conn, array $params): array;
    public function handleRateLimit(): void;
}
```

## Acceptance Criteria
- [ ] Daily sync job imports GSC data for all connected projects.
- [ ] Rate limiting handled gracefully without data loss.
- [ ] Historical import supports custom date ranges.
- [ ] Unit and integration tests verify sync logic.

## Dependencies
- TASK-12-2: `gsc_data` table must exist.
- TASK-12-4: OAuth service must be functional.

## Effort Estimate
6-8 hours.

---

## Comments
### [2026-02-20 19:06] ProjectManager: Prioritization Complete
- Priority: High (correct - core data pipeline for GSC metrics)
- Risk: Medium (rate limiting, API quota management, data integrity)
- Dependencies: TASK-12-2 (gsc_data table), TASK-12-4 (OAuth service)
- Execution order: After OAuth service (TASK-12-4) is complete
- Largest effort in FEAT-12 services (6-8 hours) - allocate accordingly
- Status: To Develop

### [2026-02-20 17:35] Agent: SpecSystemAnalyst
Service for automated GSC data import with rate limit handling.