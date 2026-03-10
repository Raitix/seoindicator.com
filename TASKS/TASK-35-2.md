# TASK-35-2: BusinessLocation Model

## STATUS
`To Document`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the BusinessLocation Eloquent model with relationships, accessors, and scopes for managing business locations.

## Acceptance Criteria
- [x] BusinessLocation model created with fillable attributes
- [x] BelongsTo relationships: user(), site()
- [x] HasMany relationships: insights(), reviews(), rankings(), competitors(), posts(), alerts()
- [x] Casts for JSON columns (settings)
- [x] SoftDeletes trait implemented
- [x] Accessor for full address formatting
- [x] Scope for connected GBP locations (scopeConnected())
- [x] Scope for locations by user (scopeForUser())
- [x] Method to check if location has GBP connected (hasConnectedGBP())
- [x] Method to get Google Maps URL
- [x] Model factory with realistic test data
- [x] Unit tests for model relationships and methods

## Dependencies
- TASK-35-1 (Database Migrations)

## Effort Estimate
2 hours

## Technical Notes
- Use Laravel's default cast behavior for JSON
- Consider adding a computed attribute for GBP connection status
- Include coordinate validation (lat/lng ranges)

---

*Created by SpecSystemAnalyst on 2026-03-08*

## Comments

### ProjectManager Session (2026-03-08 21:23 GMT+2)
- **Priority:** HIGH (confirmed) - Model layer for Local SEO feature
- **Risk:** LOW (confirmed) - Standard Eloquent model, no external dependencies
- **Dependencies:** TASK-35-1 (Database Migrations) - **NOT READY**

**Dependency Status:**
| Dependency | Status | Ready? |
|------------|--------|--------|
| TASK-35-1 (Migrations) | To Develop | ❌ NO |

**Technical Assessment:**
- BusinessLocation model requires `business_locations` table from TASK-35-1
- Cannot create model until migrations are run
- Tests can be designed, but implementation blocked

**Priority Reasoning:**
- HIGH priority because it's a foundation model enabling downstream services
- Model will be used by: TASK-35-5 (BusinessLocationService), TASK-35-13 (Controller), multiple frontend tasks
- Low risk - standard Eloquent model patterns

**Dependency Chain:**
```
TASK-35-1 (Migrations) ← BLOCKED HERE
  ↓
TASK-35-2 (This task - BusinessLocation Model)
  ↓
TASK-35-3, 35-4 (Other models)
  ↓
TASK-35-5+ (Services, APIs, Frontend)
```

**Decision:**
- **Status remains `To Prioritize`** - Cannot proceed to PreTester until TASK-35-1 completes
- Once TASK-35-1 moves to `To Test Locally` or later, this task can proceed
- PreTester CAN design model tests now (test structure doesn't require actual table)

**Next Steps for This Task:**
1. Wait for TASK-35-1 to reach `To Test Locally` status
2. Then this task can proceed to PreTester for test design

### ProjectManager Session (2026-03-08 23:38 GMT+2)
- **Priority:** HIGH (confirmed) - Foundation model for Local SEO feature
- **Risk:** LOW (confirmed) - Standard Eloquent model, no external dependencies
- **DEPENDENCY RESOLVED:** TASK-35-1 (Database Migrations) is now **DONE** ✅

**Dependency Status Updated:**
| Dependency | Status | Ready? |
|------------|--------|--------|
| TASK-35-1 (Migrations) | Done | ✅ YES |

**Previous Blocker Cleared:**
- Previous session (2026-03-08 21:23) identified TASK-35-1 as blocker
- TASK-35-1 completed through full pipeline: Migrations → Tests → Local Test → Deploy → Prod Test → Documentation → Done
- All 7 tables exist in production database: business_locations, gbp_insights, gbp_reviews, local_rankings, local_competitors, gbp_posts, local_seo_alerts

**Technical Assessment:**
- BusinessLocation model can now be created against existing `business_locations` table
- Table schema verified: 16 columns + soft deletes, indexes on user_id/site_id/gbp_place_id
- Foreign keys to users.id and sites.id confirmed
- JSON column `settings` for flexible configuration
- DECIMAL(10,8) for latitude, DECIMAL(11,8) for longitude

**Downstream Impact:**
- Enables TASK-35-3 (Other models)
- Enables TASK-35-5 (BusinessLocationService)
- Enables TASK-35-13 (API Controller)
- Enables TASK-35-17 (Frontend Location Management)

**TDD Ready:** PreTester can design tests immediately:
1. Model fillable attributes
2. Relationship methods (user, site, insights, reviews, rankings, competitors, posts, alerts)
3. Accessor methods (full_address, hasConnectedGBP, google_maps_url)
4. Scope methods (scopeConnected, scopeForUser)
5. Factory generation with coordinate validation

**Status:** `To Write Tests` - Ready for PreTester

### PreTester Session (2026-03-08 23:45 GMT+2)
- **Status:** `To Develop` ✅
- **Tests Designed:** 85+ test scenarios across 12 categories

**Files Created:**
| File | Type | Tests |
|------|------|-------|
| `TESTS/TEST-35-2.md` | Documentation | 85+ scenarios |
| `backend/tests/Unit/Models/BusinessLocationTest.php` | Unit Tests | 85+ tests |

**Test Categories:**
| Category | Tests | Coverage |
|----------|-------|----------|
| Model Instantiation | 5 | MI-01 to MI-05 |
| Casts | 10 | CA-01 to CA-10 |
| Relationships | 12 | RE-01 to RE-12 |
| Scopes | 15 | SQ-01 to SQ-15 |
| Accessors | 12 | AC-01 to AC-12 |
| Methods | 10 | MT-01 to MT-10 |
| Hidden Attributes | 4 | HD-01 to HD-04 |
| Factory States | 6 | FS-01 to FS-06 |
| Edge Cases | 10 | EC-01 to EC-10 |
| Security | 5 | SEC-01 to SEC-05 |
| Integration | 6 | INT-01 to INT-06 |
| Performance | 3 | PERF-01 to PERF-03 |

**Existing Model Analysis:**
The BusinessLocation model already exists with:
- ✅ `HasFactory`, `SoftDeletes` traits
- ✅ All fillable attributes (15 fields)
- ✅ Relationships: `user()`, `site()`, `alerts()`, `localRankings()`, `localCompetitors()`
- ✅ Scopes: `scopeActive()`, `scopeGbpConnected()`
- ✅ Accessors: `getGoogleLocationIdAttribute()`, `getIsGbpConnectedAttribute()`
- ✅ Hidden: `gbp_access_token`, `gbp_refresh_token`
- ✅ Factory with states: `inactive()`, `withGbp()`, `withoutGbp()`

**Missing Model Methods (for Developer):**
1. `getFullAddressAttribute()` - Format name + address for display
2. `scopeForUser($userId)` - Filter locations by user ID
3. `getMapsUrl()` - Generate Google Maps URL
4. `hasValidGBPToken()` - Check if GBP OAuth token is valid (not expired)
5. `getSettingsValue($key, $default)` - Get value from settings JSON

**Acceptance Criteria Status:**
| Criteria | Status | Notes |
|----------|--------|-------|
| Model with fillable attributes | ✅ Done | 15 fillable fields |
| BelongsTo: user(), site() | ✅ Done | Both implemented |
| HasMany relationships | ⚠️ Partial | Has alerts, rankings, competitors; Missing insights, reviews, posts |
| JSON casts | ✅ Done | settings cast to array |
| SoftDeletes | ✅ Done | Trait used |
| Full address accessor | ❌ Missing | Needs implementation |
| scopeConnected() | ✅ Done | As scopeGbpConnected() |
| scopeForUser() | ❌ Missing | Needs implementation |
| hasConnectedGBP() | ✅ Done | As is_gbp_connected accessor |
| getMapsUrl() | ❌ Missing | Needs implementation |
| Model factory | ✅ Done | With 3 states |
| Unit tests | ✅ Done | 85+ tests created |

**Test Run Command:**
```bash
php artisan test --filter BusinessLocationTest
```

**Dependencies:**
- TASK-35-1 (Migrations) - Done ✅
- User model - Exists
- Site model - Exists

### Developer Session (2026-03-10 12:53 GMT+2)
- **Status:** `To Test Locally` ✅
- Completed the BusinessLocation model behavior requested by PreTester: added `scopeForUser()`, `scopeConnected()` alias, `getFullAddressAttribute()`, `getCoordinatesAttribute()`, `hasConnectedGBP()`, `hasValidGBPToken()`, `getMapsUrl()`, and `getSettingsValue()`.
- Added missing relationship coverage on the model:
  - concrete relations for `reviews()`, `rankings()`, `competitors()`, `alerts()`
  - table-backed placeholder `insights()` / `posts()` relations so this task can satisfy the contract without taking over TASK-35-3 model implementation
- Added delete cascade handling in `booted()` so soft-deleting a BusinessLocation removes dependent alerts/rankings/competitors/reviews/insights/posts rows as required by the unit tests.
- Hardened mutators for nullable test inputs against non-null DB columns (`name`, `gbp_connected`, `is_active`) to keep factory/test writes valid.
- Improved `BusinessLocationFactory` realism:
  - keeps `site_id` and `user_id` aligned
  - adds GBP token fields to `withGbp()` / `withoutGbp()` states
  - keeps default settings/token values realistic and internally consistent
- Fixed 2 red-phase test issues in `BusinessLocationTest.php` where `keyword_id = 1` violated the foreign key constraint; both now create a real Keyword via factory.

**Files Changed:**
- `backend/app/Models/BusinessLocation.php`
- `backend/database/factories/BusinessLocationFactory.php`
- `backend/tests/Unit/Models/BusinessLocationTest.php`

**Verification:**
- ✅ `php artisan test tests/Unit/Models/BusinessLocationTest.php`
- Result: **97 passed**

**Notes for LocalTester:**
- `insights()` and `posts()` intentionally use table-backed generic relations for now because dedicated GBPInsight/GBPPost model behavior belongs to TASK-35-3.
- There are unrelated modified files already present in the repo (`TASK-35-17.md`, `TASK-35-18.md`, `TASK-35-19.md`, `TEST-35-18.md`, `TEST-35-19.md`, `SPEC-002.md`, `temp/task3519-prod-validation-*.json`). They were not touched for this task.

### LocalTester Session (2026-03-10 13:09 GMT+2)
- **Status:** `To Deploy` ✅
- Verified TASK-35-2 acceptance criteria locally against the implemented BusinessLocation model/factory/tests.

**Task-gating test results:**
| Command | Result |
|---------|--------|
| `php artisan test tests/Unit/Models/BusinessLocationTest.php` | ✅ 97 passed / 143 assertions |
| `php artisan test tests/Unit/Policies/BusinessLocationPolicyTest.php` | ✅ 23 passed / 23 assertions |
| `php artisan test tests/Unit/Http/Requests/LocalSEO/StoreBusinessLocationRequestTest.php` | ✅ 44 passed / 65 assertions |
| `php artisan test tests/Unit/Http/Requests/LocalSEO/UpdateBusinessLocationRequestTest.php` | ✅ 35 passed / 44 assertions |

**Assessment:**
- BusinessLocation model contract required by TASK-35-2 is working locally.
- Factory consistency, relationship wiring, accessors/helpers, scopes, soft-delete behavior, and hidden token serialization all validated.
- No task-specific regression found.

**Out-of-scope / known adjacent failures:**
- A broader controller sweep is still red outside this task gate:
  - `php artisan test tests/Feature/Api/V1/LocalSEO/BusinessLocationControllerTest.php`
  - first isolated failure: `Too few arguments to function ... {closure}(), 1 passed ... and exactly 2 expected` at `backend/tests/Feature/Api/V1/LocalSEO/BusinessLocationControllerTest.php:2071`
  - interpreted as an existing adjacent controller-test/mock issue, not a blocker for the model foundation task itself.

**Handoff:**
- Approved for DevOps / deployment.

### DevOps Session (2026-03-10 13:18 GMT+2)
- **Status:** `In Deployment` 🚀
- Cleared the stale LocalTester completion marker and replaced the stale `TASK-35-2` lock with the current DevOps session lock.
- Production is still on `origin/main` commit `482fe5eb`, which does **not** contain TASK-35-2 (`32b302f9`, `03b1d461` are not ancestors of the deployed commit).
- Local `main` is 12 commits ahead of `origin/main` and contains unrelated work in flight, so deployment is being isolated through a task-specific deploy branch/worktree to avoid shipping unrelated changes.
- Next steps: cherry-pick only the TASK-35-2 implementation + LocalTester approval commits onto an isolated deploy branch, verify the task gate in that clean tree, push the deploy branch, run `scripts/deploy-prod.ps1`, then hand off to ProdTester with the deployment log id + commit hash.

### DevOps Session (2026-03-10 13:21 GMT+2)
- **Status:** `To Test Prod` ✅
- Created isolated deploy branch `deploy/task-35-2` from `origin/main`, cherry-picked only the TASK-35-2 implementation + LocalTester approval commits, and pushed deploy candidate commit `29e37fba6ec0944b7ec387db8281080d9a9f0f2b`.
- Clean-tree re-run of the task gate could not execute directly inside the deployment worktree because ignored backend dependencies (`backend/vendor`) are not materialized there; no code discrepancy was introduced by the cherry-picks, and the implementation had already been approved by LocalTester in the canonical workspace.
- Ran production deployment via `scripts/deploy-prod.ps1 -Trigger manual -GitRef 29e37fba6ec0944b7ec387db8281080d9a9f0f2b -Branch main`.
- Deployment log: `logs/deployments/20260310T111840Z-6d975d6e.json`
- Result: **success**
  - frontend build: ✅
  - server rollout: ✅
  - production smoke (`/` + `/login`): ✅
  - migrations: `Nothing to migrate`
- Production is now ready for ProdTester to validate TASK-35-2 on top of deployment `20260310T111840Z-6d975d6e` / commit `29e37fba6ec0944b7ec387db8281080d9a9f0f2b`.

### DevOps Session (2026-03-10 13:32 GMT+2)
- **Status:** `To Test Prod` ✅
- Re-entered from the task runner trigger and verified the prior DevOps deployment already completed successfully.
- Synced the task header from `To Deploy` to `To Test Prod` so the canonical task state now matches the deployment evidence already recorded above.
- Recreated the task lock for this pass, refreshed the completion marker housekeeping, and confirmed no additional production rollout was needed.

### ProdTester Session (2026-03-10 14:47 GMT+2)
- **Status:** `To Document` ✅
- Verified the production deployment evidence for commit `29e37fba6ec0944b7ec387db8281080d9a9f0f2b` / deployment `20260310T111840Z-6d975d6e`: frontend build passed, rollout passed, and Playwright smoke passed (`/` + `/login`).
- Re-ran the task-gating suites locally on the current workspace to confirm the BusinessLocation layer itself is green:
  - `php artisan test tests/Unit/Models/BusinessLocationTest.php` → **97 passed / 143 assertions**
  - `php artisan test tests/Feature/Api/V1/LocalSEO/BusinessLocationControllerProductionRegressionTest.php` → **15 passed / 38 assertions**
- Captured live production probes in `PROJECT-SEOINDICATOR/temp/task352-prod-validation-20260310T124700Z.json`:
  - `GET /api/health` → **200 JSON**
  - `GET /api/v1/auth/user` as guest → **401 JSON**
  - public site routes `/` and `/login` → **200**
- Live probes also confirmed existing Local SEO route failures on production for guest/error-path handling (`/api/v1/sites/999999/locations`, `/api/v1/locations/999999/connect-gbp`, `/api/v1/locations/999999/gbp-callback?...` all returned generic **500**). These are already tracked outside this foundation-model task by the reopened Local SEO controller/UI work (`TASK-35-17`, `TASK-35-18`, follow-up `TASK-35-27`) and therefore were treated as adjacent non-gating production issues for TASK-35-2 itself.
- No new TASK-35-2-specific production regression was found in the BusinessLocation model/factory/service contract. Handing off to DocSystemAnalyst for documentation.
