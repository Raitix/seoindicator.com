# TASK-12-5: Implement GSC Data Sync Service

## STATUS
`To Test Locally`

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
- [x] Daily sync job imports GSC data for all connected projects.
- [x] Rate limiting handled gracefully without data loss.
- [x] Historical import supports custom date ranges.
- [x] Unit and integration tests verify sync logic.

## Dependencies
- TASK-12-2: `gsc_data` table must exist.
- TASK-12-4: OAuth service must be functional.

## Effort Estimate
6-8 hours.

---

## Comments
### [2026-02-27 23:55] Developer: Implementation Complete
GSCDataSync service already fully implemented with comprehensive test coverage:

**Files:**
- `backend/app/Services/GSCDataSync.php` - Complete sync service (16KB)
- `backend/tests/Unit/Services/GSCDataSyncServiceTest.php` - 13 unit tests

**Features Implemented:**
1. **Data Fetching:**
   - `syncProject()` - Fetches GSC data for a specific site
   - `fetchBatch()` - Batch API requests with retry logic
   - `importHistorical()` - Historical data import with 16-month limit
   - Query support for clicks, impressions, CTR, position

2. **Rate Limiting:**
   - `handleRateLimit()` - Exponential backoff with jitter
   - `calculateBackoffDelay()` - 1s, 2s, 4s, 8s, 16s pattern
   - Max 5 retry attempts with configurable buffer
   - Cache-based rate limit lock

3. **Data Storage:**
   - `storeData()` - Upsert with deduplication
   - Unique constraint on (site_id, query, page, data_date)
   - Auto keyword matching via QueryMatcher integration

4. **Sync Management:**
   - `syncAllConnections()` - Sync all active connections
   - `getSyncStatus()` - Connection and data status
   - Lock mechanism to prevent concurrent syncs
   - Date chunking for large date ranges

**Test Results:**
- GSCDataSyncServiceTest: 13 tests, 45 assertions ✓
- GSCOAuthIntegrationTest: 9 tests ✓
- GSCApiEndpointsTest: 11 tests ✓
- Total: 85 GSC-related tests passing

Status: Moving to LocalTester for verification

### [2026-02-20 19:06] ProjectManager: Prioritization Complete
- Priority: High (correct - core data pipeline for GSC metrics)
- Risk: Medium (rate limiting, API quota management, data integrity)
- Dependencies: TASK-12-2 (gsc_data table), TASK-12-4 (OAuth service)
- Execution order: After OAuth service (TASK-12-4) is complete
- Largest effort in FEAT-12 services (6-8 hours) - allocate accordingly
- Status: To Develop

### [2026-02-20 17:35] Agent: SpecSystemAnalyst
Service for automated GSC data import with rate limit handling.