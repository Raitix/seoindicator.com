# TEST-35-5: BusinessLocationService Test Documentation

## Task Reference
- **Task:** TASK-35-5 - BusinessLocationService
- **Feature:** SPEC-35 - Local SEO Module
- **Status:** To Develop
- **Depends On:** TASK-35-2 (BusinessLocation Model - tests complete, implementation pending)

---

## Overview

Tests for BusinessLocationService - service layer handling CRUD operations for business locations with optional geocoding integration.

**TDD Pattern:** Tests use mocked BusinessLocation model and NominatimService interfaces.

---

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `backend/tests/Unit/Services/LocalSEO/BusinessLocationServiceTest.php` | 180+ | Unit tests for service methods |
| `backend/app/Services/LocalSEO/Contracts/BusinessLocationServiceInterface.php` | - | Service interface for mocking |
| `backend/app/Services/LocalSEO/Contracts/NominatimServiceInterface.php` | - | Geocoding service interface |

---

## Test Scenarios

### BLS-001: Service Instantiation (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-001-01 | Class exists | - | Class `BusinessLocationService` exists |
| BLS-001-02 | Implements interface | - | Implements `BusinessLocationServiceInterface` |
| BLS-001-03 | Constructor injection | BusinessLocation model, NominatimService | Dependencies injected |
| BLS-001-04 | Container binding | - | Bound in ServiceProvider |
| BLS-001-05 | Interface resolvable | - | Interface resolves to implementation |

### BLS-002: createLocation() - Basic (12 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-002-01 | Create with required fields | site_id, name, address | BusinessLocation created |
| BLS-002-02 | Returns created model | valid data | BusinessLocation instance |
| BLS-002-03 | Geocoding called automatically | address data | NominatimService::geocode() called |
| BLS-002-04 | Geocoding success | valid address | latitude/longitude populated |
| BLS-002-05 | Geocoding failure | invalid address | Location created without coordinates |
| BLS-002-06 | Partial address | missing city | Geocoding returns null, location created |
| BLS-002-07 | Optional fields | phone, website, business_hours | All fields stored |
| BLS-002-08 | GBP fields | gbp_location_id, gbp_place_id | GBP data stored |
| BLS-002-09 | Settings JSON | settings object | JSON encoded in settings column |
| BLS-002-10 | Active by default | no is_active | is_active = true |
| BLS-002-11 | Created timestamp | - | created_at populated |
| BLS-002-12 | Database persisted | - | Record exists in database |

### BLS-003: createLocation() - Validation (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-003-01 | Missing site_id | no site_id | ValidationException |
| BLS-003-02 | Missing name | no name | ValidationException |
| BLS-003-03 | Missing address | no address | ValidationException |
| BLS-003-04 | Missing city | no city | Location still validates and creates |
| BLS-003-05 | Missing country | no country | Location still validates and creates |
| BLS-003-06 | Invalid site_id | non-existent site | ValidationException |
| BLS-003-07 | Name too long | 256+ chars | ValidationException |
| BLS-003-08 | Address too long | 501+ chars | ValidationException |
| BLS-003-09 | Invalid phone format | "abc123" | ValidationException (if format check) |
| BLS-003-10 | Invalid website URL | "not-a-url" | ValidationException |
| BLS-003-11 | Invalid business_hours | non-array | ValidationException |
| BLS-003-12 | Invalid settings | non-array | ValidationException |
| BLS-003-13 | Duplicate location | same name + address for site | ValidationException |
| BLS-003-14 | Unicode in name | "Café ☕ Restaurant" | Allowed, stored correctly |
| BLS-003-15 | Emoji in address | "123 Main St 🏢" | Allowed, stored correctly |

### BLS-004: updateLocation() - Basic (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-004-01 | Update name | new name | Name updated |
| BLS-004-02 | Update address | new address | Address updated, re-geocoded |
| BLS-004-03 | Update phone | new phone | Phone updated |
| BLS-004-04 | Update website | new website | Website updated |
| BLS-005-05 | Update business_hours | new hours | Hours updated |
| BLS-004-06 | Update settings | new settings | Settings merged/updated |
| BLS-004-07 | Partial update | only name | Other fields unchanged |
| BLS-004-08 | Updated timestamp | - | updated_at changed |
| BLS-004-09 | Returns updated model | - | BusinessLocation instance |
| BLS-004-10 | Re-geocode on address change | new address | NominatimService::geocode() called |

### BLS-005: updateLocation() - Validation (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-005-01 | Non-existent location | invalid id | ModelNotFoundException |
| BLS-005-02 | Invalid name | empty string | ValidationException |
| BLS-005-03 | Invalid address | empty string | ValidationException |
| BLS-005-04 | Invalid phone | "abc" | ValidationException (if format check) |
| BLS-005-05 | Invalid website | "not-url" | ValidationException |
| BLS-005-06 | Invalid business_hours | non-array | ValidationException |
| BLS-005-07 | Duplicate check on update | same name + different address | Allowed |
| BLS-005-08 | Same site_id required | different site_id | Ignored or ValidationException |
| BLS-005-09 | GBP fields immutable | gbp_location_id update | Ignored or special handling |
| BLS-005-10 | Soft-deleted location | deleted location | ModelNotFoundException |

### BLS-006: deleteLocation() (12 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-006-01 | Soft delete existing | valid id | Returns true |
| BLS-006-02 | Sets deleted_at | - | deleted_at populated |
| BLS-006-03 | Non-existent location | invalid id | ModelNotFoundException |
| BLS-006-04 | Already deleted | soft-deleted id | ModelNotFoundException |
| BLS-006-05 | Restore location | restore flag | Location restored |
| BLS-006-06 | Force delete | force flag | Hard delete |
| BLS-006-07 | Cascade to alerts | - | Related alerts soft-deleted |
| BLS-006-08 | Cascade to rankings | - | Related rankings soft-deleted |
| BLS-006-09 | Cascade to competitors | - | Related competitors deleted |
| BLS-006-10 | GBP disconnection | - | GBP tokens cleared |
| BLS-006-11 | Returns boolean | - | true on success |
| BLS-006-12 | Transaction rollback | error during delete | All changes rolled back |

### BLS-007: getLocationsForSite() (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-007-01 | Returns collection | valid site_id | Collection of BusinessLocation |
| BLS-007-02 | Empty result | site with no locations | Empty collection |
| BLS-007-03 | Non-existent site | invalid site_id | Empty collection or exception |
| BLS-007-04 | Excludes soft-deleted | - | Only active locations |
| BLS-007-05 | Include deleted flag | include_deleted=true | Includes soft-deleted |
| BLS-007-06 | Filter by active | active_only=true | Only is_active=true |
| BLS-007-07 | Filter by GBP status | gbp_connected=true | Only with GBP connection |
| BLS-007-08 | Order by name | sort=name | Ordered alphabetically |
| BLS-007-09 | Order by created | sort=created | Ordered by created_at DESC |
| BLS-007-10 | With relationships | with=['alerts'] | Relationships loaded |
| BLS-007-11 | Pagination | per_page=10 | LengthAwarePaginator |
| BLS-007-12 | Pagination page | page=2 | Correct page returned |
| BLS-007-13 | Cached results | same query | Cache hit |
| BLS-007-14 | Cache TTL | - | 5 minutes TTL |
| BLS-007-15 | Cache invalidation | create/update/delete | Cache cleared |

### BLS-008: getLocationsForUser() (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-008-01 | Returns collection | user_id | Collection of BusinessLocation |
| BLS-008-02 | Via sites relationship | - | Locations from user's sites |
| BLS-008-03 | Empty result | user with no sites | Empty collection |
| BLS-008-04 | Non-existent user | invalid user_id | Empty collection or exception |
| BLS-008-05 | Excludes other users | - | Only user's locations |
| BLS-008-06 | Excludes soft-deleted | - | Only active locations |
| BLS-008-07 | Include deleted flag | include_deleted=true | Includes soft-deleted |
| BLS-008-08 | Filter by active | active_only=true | Only is_active=true |
| BLS-008-09 | Filter by GBP status | gbp_connected=true | Only with GBP connection |
| BLS-008-10 | Filter by site | site_id=X | Only locations for site |
| BLS-008-11 | Order by name | sort=name | Ordered alphabetically |
| BLS-008-12 | Order by created | sort=created | Ordered by created_at DESC |
| BLS-008-13 | With relationships | with=['site', 'alerts'] | Relationships loaded |
| BLS-008-14 | Pagination | per_page=10 | LengthAwarePaginator |
| BLS-008-15 | Cached results | same query | Cache hit |

### BLS-009: validateLocationData() (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-009-01 | Valid data | all required fields | true |
| BLS-009-02 | Missing site_id | no site_id | false, errors array |
| BLS-009-03 | Missing name | no name | false, errors array |
| BLS-009-04 | Missing address | no address | false, errors array |
| BLS-009-05 | Missing city | no city | true |
| BLS-009-06 | Missing country | no country | true |
| BLS-009-07 | Invalid website | "not-url" | false, errors array |
| BLS-009-08 | Invalid phone | "abc" | false, errors array |
| BLS-009-09 | Invalid business_hours | non-array | false, errors array |
| BLS-009-10 | Returns errors array | invalid data | Array of error messages |
| BLS-009-11 | Returns field names | invalid data | Field names in errors |
| BLS-009-12 | Multiple errors | multiple invalid fields | Multiple error messages |
| BLS-009-13 | Unicode allowed | "Café" | true |
| BLS-009-14 | Emoji allowed | "📍 Location" | true |
| BLS-009-15 | XSS attempt | "<script>alert(1)</script>" | Sanitized or rejected |

### BLS-010: Geocoding Integration (15 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-010-01 | Geocode on create | valid address | NominatimService::geocode() called |
| BLS-010-02 | Store coordinates | geocode result | latitude/longitude populated |
| BLS-010-03 | Geocode on update address | new address | Re-geocoded |
| BLS-010-04 | Geocode failure | invalid address | Location created without coords |
| BLS-010-05 | Geocode timeout | timeout response | Location created without coords |
| BLS-010-06 | Geocode exception | service error | Location created without coords |
| BLS-010-07 | Skip geocode flag | skip_geocode=true | Geocoding skipped |
| BLS-010-08 | Cache geocode result | same address | Cache hit |
| BLS-010-09 | Cache key format | address data | Consistent cache key |
| BLS-010-10 | Cache TTL | - | 24 hours TTL |
| BLS-010-11 | Coordinate precision | - | 6 decimal places |
| BLS-010-12 | Latitude range | - | -90 to 90 |
| BLS-010-13 | Longitude range | - | -180 to 180 |
| BLS-010-14 | Null coordinates | geocode fails | null latitude/longitude |
| BLS-010-15 | Manual coordinates | provided lat/lng | Use provided, skip geocode |

### BLS-011: Error Handling (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-011-01 | Database error | connection failure | ServiceException |
| BLS-011-02 | Model not found | invalid id | ModelNotFoundException |
| BLS-011-03 | Validation error | invalid data | ValidationException |
| BLS-011-04 | Authorization error | wrong user | AuthorizationException |
| BLS-011-05 | Exception message | - | Meaningful error message |
| BLS-011-06 | Exception context | - | Context data included |
| BLS-011-07 | Logging on error | exception | Error logged |
| BLS-011-08 | Transaction rollback | error in transaction | All changes rolled back |
| BLS-011-09 | Graceful degradation | partial failure | Partial success where possible |
| BLS-011-10 | Never expose internals | database error | No internal details exposed |

### BLS-012: Security (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-012-01 | SQL injection in name | "'; DROP TABLE--" | Stored as literal string |
| BLS-012-02 | SQL injection in address | malicious input | Stored as literal string |
| BLS-012-03 | XSS in name | "<script>alert(1)</script>" | Escaped or sanitized |
| BLS-012-04 | User isolation | user A accessing user B's location | AuthorizationException |
| BLS-012-05 | Site isolation | site A accessing site B's location | AuthorizationException |
| BLS-012-06 | Mass assignment | injected is_admin | Ignored |
| BLS-012-07 | GBP token protection | update with gbp_access_token | Token not overwritten |
| BLS-012-08 | No sensitive data in errors | database error | No credentials exposed |

### BLS-013: Performance (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-013-01 | Create performance | single location | < 500ms |
| BLS-013-02 | List performance | 100 locations | < 200ms |
| BLS-013-03 | Update performance | single update | < 200ms |
| BLS-013-04 | Delete performance | single delete | < 200ms |
| BLS-013-05 | Batch create | 10 locations | < 2 seconds |

### BLS-014: Edge Cases (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-014-01 | Empty name | "" | ValidationException |
| BLS-014-02 | Whitespace only name | "   " | ValidationException or trimmed |
| BLS-014-03 | Unicode name | "东京餐厅" | Stored correctly |
| BLS-014-04 | Emoji in fields | "📍 Location" | Stored correctly |
| BLS-014-05 | Very long name | 255 chars | Accepted |
| BLS-014-06 | Max length exceeded | 256 chars | ValidationException |
| BLS-014-07 | Special characters | "O'Brien & Co." | Stored correctly |
| BLS-014-08 | Newlines in address | "Line 1\nLine 2" | Stored correctly |
| BLS-014-09 | Null optional fields | phone = null | Stored as null |
| BLS-014-10 | Concurrent updates | two requests | Last write wins or lock |

### BLS-015: Acceptance Criteria (10 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BLS-015-01 | AC1: Service exists | - | BusinessLocationService class exists |
| BLS-015-02 | AC2: Interface defined | - | BusinessLocationServiceInterface exists |
| BLS-015-03 | AC3: createLocation method | valid data | Creates location |
| BLS-015-04 | AC4: updateLocation method | valid update | Updates location |
| BLS-015-05 | AC5: deleteLocation method | valid id | Deletes location |
| BLS-015-06 | AC6: getLocationsForSite method | site_id | Returns collection |
| BLS-015-07 | AC7: getLocationsForUser method | user_id | Returns collection |
| BLS-015-08 | AC8: validateLocationData method | data | Returns validation result |
| BLS-015-09 | AC9: Geocoding integration | address | Coordinates populated |
| BLS-015-10 | AC10: ServiceProvider binding | - | Interface resolves to service |

---

## Mock Data

### Mock BusinessLocation Model
```php
$mockLocation = new class {
    public $id = 1;
    public $site_id = 1;
    public $name = 'Main Street Café';
    public $address = '123 Main Street';
    public $city = 'New York';
    public $state = 'NY';
    public $postal_code = '10001';
    public $country = 'US';
    public $phone = '+1-555-123-4567';
    public $website = 'https://example.com';
    public $latitude = 40.7128;
    public $longitude = -74.0060;
    public $is_active = true;
    public $gbp_location_id = 'locations/ABC123';
    public $gbp_place_id = 'ChIJ1234567890';
    public $business_hours = [
        'monday' => ['09:00-17:00'],
        'tuesday' => ['09:00-17:00'],
    ];
    public $settings = [
        'notification_email' => 'owner@example.com',
    ];
};
```

### Mock Geocode Response
```php
$mockGeocodeResult = [
    'latitude' => 40.7128,
    'longitude' => -74.0060,
    'display_name' => '123 Main Street, New York, NY 10001, USA',
];
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Service Instantiation | 100% | 5 |
| createLocation | 100% | 27 |
| updateLocation | 100% | 20 |
| deleteLocation | 100% | 12 |
| getLocationsForSite | 100% | 15 |
| getLocationsForUser | 100% | 15 |
| validateLocationData | 100% | 15 |
| Geocoding Integration | 100% | 15 |
| Error Handling | 100% | 10 |
| Security | 100% | 8 |
| Performance | 100% | 5 |
| Edge Cases | 100% | 10 |
| Acceptance Criteria | 100% | 10 |
| **Total** | **100%** | **167+** |

---

## Test Execution

```bash
# Run all BusinessLocationService tests
php artisan test --filter=BusinessLocationServiceTest

# Run specific test category
php artisan test --filter=BLS_002

# Run with coverage
php artisan test --filter=BusinessLocationServiceTest --coverage
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-35-2 (BusinessLocation Model) | Tests Complete | Model mocked in tests |
| NominatimService | Interface Created | Geocoding mocked |
| BusinessLocationServiceInterface | Created | For mocking in other tests |

---

## Notes for Developer

1. **TDD Pattern:** Tests are written first. Implement service to pass these tests.

2. **Model Interface:** BusinessLocation model tests exist (TASK-35-2). Reference those for field structure.

3. **Geocoding:** NominatimServiceInterface created for mocking. Real implementation comes later.

4. **Caching:** Service should cache location queries (5-minute TTL recommended).

5. **Events:** Consider dispatching events on create/update/delete for observers.

6. **Transactions:** Use database transactions for operations affecting multiple records.

7. **Authorization:** Service should validate user owns the site before operations.

8. **Validation:** Use Laravel's Validator or create dedicated FormRequest classes.

---

## Comments

### PreTester Log
- 2026-03-09: Created test documentation and unit tests
- Tests designed with TDD pattern - use mocked interfaces
- BusinessLocationServiceInterface created for DI and mocking
- NominatimServiceInterface created for geocoding mock

---

Last updated: 2026-03-09
