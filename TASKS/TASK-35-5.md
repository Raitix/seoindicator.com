# TASK-35-5: BusinessLocationService

## STATUS
`To Write Tests`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create BusinessLocationService for CRUD operations and business logic related to managing business locations.

## Acceptance Criteria
- [ ] BusinessLocationService created with interface
- [ ] Service bound in ServiceProvider
- [ ] Method: createLocation(array $data): BusinessLocation
  - Validates required fields (name, address)
  - Optionally geocodes address to get coordinates
  - Creates location record
- [ ] Method: updateLocation(BusinessLocation $location, array $data): BusinessLocation
  - Updates location fields
  - Re-geocodes if address changed
- [ ] Method: deleteLocation(BusinessLocation $location): void
  - Soft deletes location
  - Clears related GBP tokens
- [ ] Method: getLocationsForSite(int $siteId): Collection
  - Returns all locations for a site
- [ ] Method: getLocationsForUser(int $userId): Collection
  - Returns all locations for a user across all sites
- [ ] Method: validateLocationData(array $data): array
  - Validates and sanitizes input data
- [ ] Proper error handling with custom exceptions
- [ ] Unit tests with mocked dependencies

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
