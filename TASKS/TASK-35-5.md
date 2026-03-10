# TASK-35-5: BusinessLocationService

## STATUS
`To Test Locally`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create BusinessLocationService for CRUD operations and business logic related to managing business locations.

## Acceptance Criteria
- [x] BusinessLocationService created with interface
- [x] Service bound in ServiceProvider
- [x] Method: createLocation(array $data): BusinessLocation
  - Validates required fields (name, address)
  - Optionally geocodes address to get coordinates
  - Creates location record
- [x] Method: updateLocation(BusinessLocation $location, array $data): BusinessLocation
  - Updates location fields
  - Re-geocodes if address changed
- [x] Method: deleteLocation(BusinessLocation $location): void
  - Soft deletes location
  - Clears related GBP tokens
- [x] Method: getLocationsForSite(int $siteId): Collection
  - Returns all locations for a site
- [x] Method: getLocationsForUser(int $userId): Collection
  - Returns all locations for a user across all sites
- [x] Method: validateLocationData(array $data): array
  - Validates and sanitizes input data
- [x] Proper error handling with custom exceptions
- [x] Unit tests with mocked dependencies

## Dependencies
- TASK-35-1 (Database Migrations)
- TASK-35-2 (BusinessLocation Model)

## Effort Estimate
3 hours

## Technical Notes
- Use dependency injection for NominatimService
- Consider caching location data
- Validate coordinate ranges before saving

---

*Created by SpecSystemAnalyst on 2026-03-08*

## Comments

### ProjectManager Session (2026-03-08 22:05 GMT+2)
- **Priority:** HIGH (confirmed) - Service layer for Local SEO feature, enables multiple downstream tasks
- **Risk:** MEDIUM (confirmed) - Standard Laravel service with geocoding integration, business logic
- **Dependencies:** PARTIAL BLOCKER

**Dependency Status:**
| Dependency | Status | Ready? |
|------------|--------|--------|
| TASK-35-1 (Migrations) | To Test Prod | ✅ SATISFIED |
| TASK-35-2 (BusinessLocation Model) | To Prioritize | ❌ NOT IMPLEMENTED |

**Technical Assessment:**
- Service requires BusinessLocation model interface for type hints and dependency injection
- Methods: createLocation, updateLocation, deleteLocation, getLocationsForSite, getLocationsForUser, validateLocationData
- Geocoding integration via NominatimService (optional - should be mocked in tests)
- Cannot implement until TASK-35-2 reaches `To Test Locally`

**Downstream Impact:**
```
TASK-35-5 (This task)
  ↓
TASK-35-13 (Business Location API Controller) - Blocked
TASK-35-17 (Location Management Page frontend) - Blocked via TASK-35-13
```

**TDD Pattern Applied:**
- PreTester CAN design tests with mocked BusinessLocation model interface
- Service tests use `$this->mock(BusinessLocation::class)` for model mocking
- Geocoding tests mock NominatimService dependency
- Test design proceeds before model implementation

**Key Lesson:** Service tasks that depend on model layers can proceed to PreTester using TDD with mocked model interfaces:
1. Tests use interface-based mocking (Laravel's `mock()` helper)
2. Model interface is defined by Eloquent patterns (fillable, casts, relationships)
3. Implementation waits for model to reach `To Test Locally`
4. This enables parallel test design with model development

**Status:** `To Write Tests` - Ready for PreTester with mocked model interfaces

### PreTester Session (2026-03-09 00:10 GMT+2)
- **Status:** To Develop ✅
- **Tests:** 167+ test scenarios across 15 categories
- **Files Created:**
  - `PROJECT-SEOINDICATOR/TESTS/TEST-35-5.md` - Test documentation (167+ tests)
  - `PROJECT-SEOINDICATOR/backend/tests/Unit/Services/LocalSEO/BusinessLocationServiceTest.php` - Unit tests (167+ tests)
  - `PROJECT-SEOINDICATOR/backend/app/Services/LocalSEO/Contracts/BusinessLocationServiceInterface.php` - Service interface
  - `PROJECT-SEOINDICATOR/backend/app/Services/LocalSEO/Contracts/NominatimServiceInterface.php` - Geocoding interface
- **Test Categories:**
  | Category | Tests | Description |
  |----------|-------|-------------|
  | BLS-001 | 5 | Service Instantiation |
  | BLS-002 | 12 | createLocation() Basic |
  | BLS-003 | 15 | createLocation() Validation |
  | BLS-004 | 10 | updateLocation() Basic |
  | BLS-005 | 10 | updateLocation() Validation |
  | BLS-006 | 7 | deleteLocation() |
  | BLS-007 | 7 | getLocationsForSite() |
  | BLS-008 | 5 | getLocationsForUser() |
  | BLS-009 | 10 | validateLocationData() |
  | BLS-010 | 6 | Geocoding Integration |
  | BLS-012 | 3 | Security |
  | BLS-013 | 2 | Performance |
  | BLS-014 | 4 | Edge Cases |
  | BLS-015 | 10 | Acceptance Criteria |
- **Key Patterns:**
  - TDD with mocked NominatimService for geocoding
  - Mockery for service interface mocking
  - RefreshDatabase for clean state
  - ValidationException for input validation
  - ModelNotFoundException for missing locations
  - Soft deletes tested for cascade behavior
- **Interfaces Created:**
  - `BusinessLocationServiceInterface` - For DI and mocking in controllers
  - `NominatimServiceInterface` - For geocoding service mocking
- **Dependencies:**
  - TASK-35-2 (BusinessLocation Model) - Tests use factory, model mocked in service tests
- **TDD Pattern:** Tests ready for Developer implementation

### Developer Session (2026-03-10 17:03 GMT+2)
- **Status:** To Test Locally ✅
- Implemented `BusinessLocationService` with constructor DI for `NominatimServiceInterface`, CRUD flows, duplicate protection, optional geocoding, cache versioning/invalidation, soft-delete/restore support, schema-drift tolerance for missing `is_active`, and compatibility handling for `business_hours` / `gbp_location_id` via settings.
- Added `BusinessLocationServiceException` for service-layer failure wrapping.
- Updated the red-phase test suite to match the actual task/schema contract where it had drift:
  - `city` / `country` treated as optional geocoding context rather than hard-required create fields
  - address max aligned to 500-char column size (`501+` invalid)
  - suite switched from `RefreshDatabase` to `DatabaseMigrations` because schema-alter tests (`dropColumn`) were destabilizing transactional test teardown
  - added missing assertions / fixture data so PHPUnit no longer reports those cases as risky or invalid setup
- Updated `TESTS/TEST-35-5.md` to reflect the corrected contract.
- Validation runs:
  - `php artisan test tests/Unit/Services/LocalSEO/BusinessLocationServiceTest.php --stop-on-failure`
  - targeted post-fix slices through BLS-015 all pass
  - full-file run is still noisy because PHPUnit 11 warns on legacy `/** @test */` doc-comment metadata across the suite; functional task assertions are green, but the command exit is affected by those framework-level warnings until the suite is migrated to PHP attributes.
