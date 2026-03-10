# TASK-35-4: Local SEO Models

## STATUS
`To Deploy`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Low`

## Description
Create Eloquent models for local SEO tracking: LocalRanking, LocalCompetitor, and LocalSEOAlert.

## Acceptance Criteria
- [ ] LocalRanking model created:
  - BelongsTo relationships: businessLocation(), keyword()
  - Casts for coordinates (decimal) and competitors_in_pack (JSON)
  - Scope for local pack positions (scopeInPack())
  - Scope for date range filtering
  - Method to get position change from previous check
  - Method to check if in local pack (position <= 3)
- [ ] LocalCompetitor model created:
  - BelongsTo relationship to BusinessLocation
  - Casts for rating (decimal) and review_count (integer)
  - Method to get Google Maps URL
  - Method to compare with location (rating difference, etc.)
- [ ] LocalSEOAlert model created:
  - BelongsTo relationships: user(), businessLocation()
  - Casts for data (JSON) and is_read (boolean)
  - Scope for unread alerts
  - Scope by severity (scopeCritical(), scopeWarning(), scopeInfo())
  - Scope by alert_type
  - Method to mark as read
  - Constants for alert types and severities
- [ ] Model factories for all models
- [ ] Unit tests for all models

## Dependencies
- TASK-35-1 (Database Migrations)

## Effort Estimate
2 hours

## Technical Notes
- Alert types: ranking_improved, ranking_dropped, new_review, negative_review, rating_dropped, competitor_overtake, gbp_disconnected
- Severities: info, warning, critical
- Local pack position is TINYINT (NULL if not in pack)

---

*Created by SpecSystemAnalyst on 2026-03-08*

## Comments

### ProjectManager Session (2026-03-08 21:58 GMT+2)
- **Priority:** HIGH (confirmed) - Foundation model task enabling local SEO services and APIs
- **Risk:** LOW (confirmed) - Standard Eloquent models, no external APIs, well-defined relationships
- **Dependency Status:** TASK-35-1 (Database Migrations) at `To Deploy` ✅ - SATISFIED

**Model Assessment:**

| Model | Relationships | Scopes | Methods | Complexity |
|-------|--------------|--------|---------|------------|
| LocalRanking | 2 (businessLocation, keyword) | 2 (inPack, dateRange) | 2 (positionChange, inLocalPack) | Medium |
| LocalCompetitor | 1 (businessLocation) | 0 | 2 (mapsUrl, compareWithLocation) | Low |
| LocalSEOAlert | 2 (user, businessLocation) | 4 (unread, critical, warning, info, byType) | 1 (markAsRead) | Low |

**Downstream Impact:**
- Enables TASK-35-5+ (Services that use these models)
- Enables TASK-35-13+ (API Controllers that query these models)
- Enables TASK-35-17+ (Frontend that displays this data)

**Technical Notes for Developer:**
1. **LocalRanking:**
   - `scopeInPack()` - where local_pack_position IS NOT NULL AND <= 3
   - `getPositionChange()` - compare with previous check by checked_at
   - `inLocalPack()` - return boolean, position <= 3

2. **LocalCompetitor:**
   - `getMapsUrl()` - construct Google Maps URL from gbp_id or address
   - `compareWithLocation(BusinessLocation $location)` - return array with rating_diff, review_count_diff

3. **LocalSEOAlert:**
   - Constants for ALERT_TYPES and SEVERITIES
   - `scopeByType($type)` - filter by alert_type
   - `markAsRead()` - set is_read = true, save

**TDD Ready:** PreTester CAN design tests immediately with migration tables ready:
1. Model attribute tests (fillable, casts, dates)
2. Relationship tests (belongsTo, return types)
3. Scope tests (query builders return correct results)
4. Method tests (return values, edge cases)
5. Factory tests (create valid models)

**Key Lesson:** Model tasks that create foundation data structures should be HIGH priority because:
1. They enable all downstream services and APIs
2. They have no external dependencies (just database)
3. Tests can verify relationships and scopes in isolation
4. Eloquent patterns are well-documented and predictable

**Status:** `To Write Tests` - Ready for PreTester to design model tests

### PreTester Session (2026-03-08 23:58 GMT+2)
- **Test Design:** TDD approach with comprehensive coverage
- **Total Tests:** 152 tests, 244 assertions
- **All tests passing:** ✅
- **Test Documentation:** `TESTS/TASK-35-4.md`

**Test Files Created:**
| File | Tests | Assertions |
|------|-------|------------|
| `tests/Unit/Models/LocalRankingTest.php` | 45 | 69 |
| `tests/Unit/Models/LocalCompetitorTest.php` | 39 | 52 |
| `tests/Unit/Models/LocalSEOAlertTest.php` | 68 | 123 |

**Key Findings:**
1. **BusinessLocation** uses `SoftDeletes` - cascade delete tests require `forceDelete()`
2. **Keyword** uses soft-delete via `status` field - cascade tests require `forceDelete()`
3. **Decimal cast** returns string in Laravel (not float) - tests updated
4. **LocalCompetitor.compareWithLocation()** uses `rating` and `review_count` properties not in BusinessLocation schema (likely dynamic from GBP API)

**Test Coverage:**
- ✅ Model instantiation & attributes
- ✅ Casts (decimal, integer, array, boolean, datetime)
- ✅ Relationships (BelongsTo)
- ✅ Scopes (inPack, dateRange, unread, ofType, ofSeverity, forLocation)
- ✅ Methods (inLocalPack, getPositionChange, getMapsUrl, compareWithLocation, markAsRead)
- ✅ Factory states
- ✅ Edge cases (nulls, max values, unicode)
- ✅ Security (SQL injection, XSS)
- ✅ Integration (cascade delete, CRUD)
- ✅ Performance (indexes, bulk insert)

**Status:** `To Develop` - All tests designed and passing, ready for Developer

### Developer Session (2026-03-10 16:57 GMT+2)
- Picked up `TASK-35-4`, attempted the stale-marker clear step, refreshed the task lock, and moved the task through `In Development`.
- Audited the existing Local SEO model layer and confirmed the core implementation, factories, and PreTester coverage were already in place for:
  - `backend/app/Models/LocalRanking.php`
  - `backend/app/Models/LocalCompetitor.php`
  - `backend/app/Models/LocalSEOAlert.php`
  - corresponding factories and unit suites under `backend/database/factories/` and `backend/tests/Unit/Models/`
- Closed the remaining acceptance-criteria naming gaps without breaking the shipped API used by the existing tests:
  - added `businessLocation()` aliases on `LocalRanking`, `LocalCompetitor`, and `LocalSEOAlert`
  - added `LocalSEOAlert::SEVERITIES` as an alias to the existing severity constant set
  - added `LocalSEOAlert::scopeByType()` plus severity convenience scopes `scopeCritical()`, `scopeWarning()`, and `scopeInfo()`
- Validation run on this machine:
  - `php artisan test tests/Unit/Models/LocalRankingTest.php tests/Unit/Models/LocalCompetitorTest.php tests/Unit/Models/LocalSEOAlertTest.php` PASS
  - Result: **152 tests passed / 244 assertions**
- Non-blocking note: the targeted PHPUnit run still emits the existing doc-comment metadata deprecation warnings from the prewritten test files; no task-specific failures remained.
- **Status Change:** `To Develop` -> `In Development` -> `To Test Locally`
- **Next Agent:** LocalTester should rerun the targeted Local SEO model suite and, if desired, include any nearby Local SEO backend smoke tests that depend on these models.

### LocalTester Session (2026-03-10 17:47 GMT+2)
- Re-cleared the stale completion marker, refreshed the task lock, and moved the task through `Testing Locally` to `To Deploy`.
- Re-ran the targeted backend model suite:
  - `php artisan test tests/Unit/Models/LocalRankingTest.php tests/Unit/Models/LocalCompetitorTest.php tests/Unit/Models/LocalSEOAlertTest.php`
  - Result: **152 tests passed / 244 assertions**
- Added a direct acceptance-criteria spot-check for the developer’s naming-gap compatibility layer (`businessLocation()` aliases on all three models, `LocalSEOAlert::SEVERITIES`, and `scopeByType()` / `scopeCritical()` / `scopeWarning()` / `scopeInfo()`). All checks passed.
- Validation outcome: relationships, casts, scopes, factories, CRUD/cascade behavior, and the acceptance-criteria alias surface are locally green.
- Non-blocking note: the targeted PHPUnit run still emits the existing PHPUnit 12 doc-comment metadata deprecation warnings from the prewritten test files; no task-specific failures or documentation drift were found.
- **Status Change:** `Testing Locally` -> `To Deploy`
- **Next Agent:** DevOps can deploy without additional local remediation for this task.
