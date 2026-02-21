# SPEC-003-4: Backend - Ranking Check Integration

## STATUS
`In Development`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Extend the existing rank checking service to also fetch and store competitor rankings. This reuses FEAT-001 infrastructure and stores competitor rankings alongside user rankings.

## Acceptance Criteria
- [ ] Extend RankCheckerService to accept competitor domains
- [ ] When checking keyword, also fetch rankings for all competitors associated with the site
- [ ] Store competitor rankings in competitor_rankings table
- [ ] Handle competitor domain not ranking (store null position)
- [ ] Reuse same free SERP API as FEAT-001 (no paid services)
- [ ] Batch competitor checks when possible (single SERP request)
- [ ] Log any competitor check failures without failing user's check
- [ ] Unit tests for competitor ranking fetch logic

## Dependencies
- SPEC-003-1 (competitor_rankings table must exist)
- FEAT-001 (RankCheckerService must exist)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- This is an extension of existing FEAT-001 rank checking
- Must use free SERP alternatives (same as FEAT-001)
- Consider performance: batch competitor checks
- Error handling: don't fail user's check if competitor check fails

---

## Comments
_Agent communication log_

### [2026-02-20 00:55] Agent: PreTester
**Test Design Complete**

**Tests Created:**
1. `TESTS/SPEC-003-4.md` - Test documentation (30 test cases)
2. `backend/tests/Unit/Services/RankCheckerServiceTest.php` - Unit tests (19 tests)
   - Competitor domain parameter handling
   - Single/multiple competitor rankings
   - Not ranking (null position) handling
   - Single SERP call optimization
   - Competitors for site retrieval
   - Error handling (log competitor failures, user check succeeds)
   - Batch processing
   - Empty competitors handling
   - Duration tracking
   - SERP features tracking
   - Security: malicious domain sanitization
3. `backend/tests/Feature/Services/RankCheckerIntegrationTest.php` - Integration test stub

**Dependency Blocker:**
- Requires `competitors` and `competitor_rankings` tables (SPEC-003-1)
- Requires `RankCheckerService` from FEAT-001
- Tests marked with `markTestSkipped` until dependencies exist

**Developer Requirements:**
Extend/modify:
- `backend/app/Services/RankCheckerService.php`
- `backend/app/Jobs/CheckKeywordJob.php`
- `backend/app/Models/CompetitorRanking.php`

**Handoff:** → Developer for implementation

### [2026-02-20 00:53] Agent: PreTester
**Test Design Complete**

**Tests Created:**
1. `TESTS/SPEC-003-4.md` - Test documentation (30 test cases documented)
2. `backend/tests/Unit/Services/RankCheckerServiceTest.php` - Unit tests (22 tests)
   - Competitor domains parameter handling
   - Single/multiple competitor ranking fetch
   - Not-ranking handling (null position)
   - Single SERP call optimization
   - Error handling (user succeeds, competitor fails)
   - Batch processing
   - Duration tracking
   - SERP features tracking
   - Security: malicious domain sanitization
3. `backend/tests/Feature/Services/RankCheckerIntegrationTest.php` - Integration tests (16 tests)
   - Database storage of competitor rankings
   - Auto-include competitors from site
   - Historical rankings preservation
   - Unique constraint verification
   - Concurrency handling
   - Large keyword set performance
   - API endpoint integration
   - Model fillable/relationships
4. `backend/tests/Unit/Jobs/CheckKeywordJobTest.php` - Job tests (11 tests)
   - Job dispatch and processing
   - Retry on transient failure
   - SERP API down handling
   - Queue integration

**⚠️ DEPENDENCY BLOCKERS:**
- `competitor_rankings` table (SPEC-003-1)
- `keywords` table (FEAT-001)
- `RankCheckerService` (FEAT-001)
Tests will fail until dependencies exist.

**Key Test Scenarios:**
- Single SERP call for user + all competitors (efficiency)
- Competitor failure doesn't fail user check (resilience)
- Auto-include site competitors (convenience)
- Batch processing with minimal API calls

**Developer Requirements:**
Create/modify:
- `backend/app/Services/RankCheckerService.php` (extend with competitor support)
- `backend/app/Jobs/CheckKeywordJob.php` (extend for competitors)
- `backend/app/Models/CompetitorRanking.php`

**Test Execution:**
```bash
cd backend
php artisan test --filter=RankCheckerServiceTest
php artisan test --filter=RankCheckerIntegrationTest
php artisan test --filter=CheckKeywordJobTest
```

**Handoff:** → Developer for implementation (after dependencies)

### [2026-02-20 00:42] Agent: ProjectManager
**Prioritization Complete**
- Priority: High ✓ (connects competitor tracking to ranking pipeline)
- Risk: Medium ✓ (extends existing service, must use free tools)
- **BLOCKERS:**
  - SPEC-003-1 (competitor_rankings table)
  - FEAT-001 RankCheckerService must exist first
- **Handoff:** → PreTester

### [2026-02-19 23:24] Agent: SpecSystemAnalyst
Created task for ranking check integration. Critical task that connects competitor tracking to the ranking pipeline. Must use FREE tools only as per CONSTITUTION.md.
