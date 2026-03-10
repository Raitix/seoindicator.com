# TEST-35-2: BusinessLocation Model Tests

## Task Reference
- **Task:** TASK-35-2
- **Specification:** SPEC-035: Local SEO & Google Business Profile Tracking
- **Priority:** High
- **Risk:** Medium

---

## Overview
Unit tests for the BusinessLocation Eloquent model covering relationships, scopes, accessors, and business logic methods.

---

## Test Files

| File | Type | Tests |
|------|------|-------|
| `backend/tests/Unit/Models/BusinessLocationTest.php` | Unit | 85+ |

---

## Test Scenarios

### Model Instantiation (MI-001 to MI-005)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MI-001 | Create with required fields | user_id, site_id, name, address | Model created successfully |
| MI-002 | Create with all fields | All 15 fillable fields | Model created with all data |
| MI-003 | Mass assignment protection | Attempt to set guarded fields | Fields ignored or exception |
| MI-004 | Factory creation | BusinessLocation::factory()->create() | Valid model with defaults |
| MI-005 | Model uses SoftDeletes | Delete model | Soft deleted, not removed |

### Casts (CA-001 to CA-010)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CA-001 | latitude cast to decimal:8 | 40.71280000 | Decimal with 8 places |
| CA-002 | longitude cast to decimal:8 | -74.00600000 | Decimal with 8 places |
| CA-003 | gbp_connected cast to boolean | 1, 0, true, false | Boolean values |
| CA-004 | is_active cast to boolean | 1, 0, true, false | Boolean values |
| CA-005 | settings cast to array | JSON string | Array |
| CA-006 | settings null returns null | null | null |
| CA-007 | gbp_token_expires_at cast to datetime | ISO datetime string | Carbon instance |
| CA-008 | Decimal precision preserved | 40.71281234 | 8 decimal places preserved |
| CA-009 | Boolean defaults | gbp_connected default false | false |
| CA-010 | Boolean defaults | is_active default true | true |

### Relationships (RE-001 to RE-012)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RE-001 | user() relationship exists | Call user() | BelongsTo relationship |
| RE-002 | user() returns owner | Load user | User model |
| RE-003 | site() relationship exists | Call site() | BelongsTo relationship |
| RE-004 | site() returns site | Load site | Site model |
| RE-005 | alerts() relationship exists | Call alerts() | HasMany relationship |
| RE-006 | alerts() returns alerts | Load alerts | LocalSEOAlert collection |
| RE-007 | localRankings() relationship exists | Call localRankings() | HasMany relationship |
| RE-008 | localRankings() returns rankings | Load localRankings | LocalRanking collection |
| RE-009 | localCompetitors() relationship exists | Call localCompetitors() | HasMany relationship |
| RE-010 | localCompetitors() returns competitors | Load localCompetitors | LocalCompetitor collection |
| RE-011 | Eager loading works | with('user', 'site') | Loaded without N+1 |
| RE-012 | Cascade delete alerts | Delete location | Related alerts deleted |

### Scopes (SQ-001 to SQ-015)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SQ-001 | scopeActive filters active | is_active = true | Only active locations |
| SQ-002 | scopeActive excludes inactive | is_active = false | Excludes inactive |
| SQ-003 | scopeGbpConnected filters connected | gbp_connected = true | Only GBP connected |
| SQ-004 | scopeGbpConnected excludes disconnected | gbp_connected = false | Excludes disconnected |
| SQ-005 | scopeForUser filters by user | user_id = X | Only user's locations |
| SQ-006 | scopeForUser excludes others | Different user_id | Excludes others |
| SQ-007 | Active scope chainable | active()->gbpConnected() | Both filters applied |
| SQ-008 | Scope with soft deletes | active()->withTrashed() | Includes soft deleted |
| SQ-009 | Scope only trashed | onlyTrashed() | Only soft deleted |
| SQ-010 | Scope order by name | orderBy('name') | Alphabetical order |
| SQ-011 | Scope order by created | latest() | Most recent first |
| SQ-012 | Scope with site | whereHas('site') | Locations with sites |
| SQ-013 | Scope without site | whereDoesntHave('site') | Orphan locations |
| SQ-014 | Search by name | where('name', 'like', '%test%') | Matching names |
| SQ-015 | Search by address | where('address', 'like', '%main%') | Matching addresses |

### Accessors (AC-001 to AC-012)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC-001 | google_location_id alias | gbp_place_id | Same value returned |
| AC-002 | google_location_id null | gbp_place_id = null | null returned |
| AC-003 | is_gbp_connected alias | gbp_connected | Same value returned |
| AC-004 | is_gbp_connected default | gbp_connected = null | false returned |
| AC-005 | full_address accessor | name, address | Formatted string |
| AC-006 | full_address without name | address only | Address only |
| AC-007 | coordinates accessor | latitude, longitude | Array [lat, lng] |
| AC-008 | coordinates null | lat/lng null | null |
| AC-009 | has_gbp_tokens true | gbp_access_token set | true |
| AC-010 | has_gbp_tokens false | gbp_access_token null | false |
| AC-011 | gbp_token_expired true | expires_at in past | true |
| AC-012 | gbp_token_expired false | expires_at in future | false |

### Methods (MT-001 to MT-010)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-001 | getMapsUrl() with gbp_place_id | gbp_place_id set | Google Maps URL with place_id |
| MT-002 | getMapsUrl() without gbp_place_id | gbp_place_id null | Google Maps search URL |
| MT-003 | getMapsUrl() with lat/lng | coordinates only | Google Maps coordinates URL |
| MT-004 | hasConnectedGBP() true | gbp_connected = true | true |
| MT-005 | hasConnectedGBP() false | gbp_connected = false | false |
| MT-006 | hasValidGBPToken() true | token not expired | true |
| MT-007 | hasValidGBPToken() false | token expired | false |
| MT-008 | hasValidGBPToken() no token | gbp_access_token null | false |
| MT-009 | getSettingsValue() existing key | settings = {key: 'value'} | 'value' |
| MT-010 | getSettingsValue() missing key | settings = {} | null or default |

### Hidden Attributes (HD-001 to HD-004)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HD-001 | gbp_access_token hidden | toArray() | Token not in array |
| HD-002 | gbp_refresh_token hidden | toArray() | Token not in array |
| HD-003 | JSON serialization hides tokens | json_encode($model) | Tokens not in JSON |
| HD-004 | API response hides tokens | model->toJson() | Tokens not in output |

### Factory States (FS-001 to FS-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FS-001 | Default factory | factory()->create() | is_active = true |
| FS-002 | inactive state | factory()->inactive()->create() | is_active = false |
| FS-003 | withGbp state | factory()->withGbp()->create() | gbp_connected = true |
| FS-004 | withoutGbp state | factory()->withoutGbp()->create() | gbp_connected = false, gbp_place_id = null |
| FS-005 | Factory creates user | factory()->create() | User created via factory |
| FS-006 | Factory creates site | factory()->create() | Site created via factory |

### Edge Cases (EC-001 to EC-010)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Null latitude/longitude | latitude = null | Valid, null stored |
| EC-002 | Null phone | phone = null | Valid, null stored |
| EC-003 | Null website | website = null | Valid, null stored |
| EC-004 | Null category | category = null | Valid, null stored |
| EC-005 | Long address | 500 char address | Valid, stored |
| EC-006 | Unicode in name | 日本語ビジネス | Valid, stored correctly |
| EC-007 | Emoji in name | 🍕 Pizza Place | Valid, stored correctly |
| EC-008 | Decimal precision edge | lat = 90.00000000, lng = 180.00000000 | Valid extremes |
| EC-009 | Negative coordinates | lat = -40.7, lng = -74.0 | Valid, negative allowed |
| EC-010 | Empty settings | settings = [] | Valid, empty array |

### Security (SEC-001 to SEC-005)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-001 | SQL injection in name | '; DROP TABLE locations; -- | Stored as literal string |
| SEC-002 | XSS in name | <script>alert(1)</script> | Stored as literal string |
| SEC-003 | HTML in address | <b>123 Main</b> | Stored as literal string |
| SEC-004 | Token exposure prevention | model->toArray() | Tokens hidden |
| SEC-005 | Mass assignment user_id | Attempt to set user_id | Ignored or protected |

### Integration (INT-001 to INT-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-001 | Create and retrieve | Create, find | Same data |
| INT-002 | Update and persist | Update fields | Changes saved |
| INT-003 | Soft delete and restore | Delete, restore | Restored successfully |
| INT-004 | Force delete | forceDelete() | Permanently removed |
| INT-005 | Cascade to rankings | Delete location | Rankings deleted |
| INT-006 | Cascade to competitors | Delete location | Competitors deleted |

### Performance (PERF-001 to PERF-003)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PERF-001 | Bulk creation | 100 locations | < 5 seconds |
| PERF-002 | Eager loading | 50 locations with relations | No N+1 queries |
| PERF-003 | Index usage | Query by user_id | Uses index |

---

## Coverage Targets
- **Models:** 100% of fillable, casts, relationships, scopes, methods
- **Accessors:** 100% of accessor methods
- **Factory:** All states tested
- **Security:** Token hiding, mass assignment protection

---

## Test Execution

```bash
# Run all BusinessLocation tests
php artisan test --filter BusinessLocation

# Run specific test file
php artisan test backend/tests/Unit/Models/BusinessLocationTest.php

# Run with coverage
php artisan test --coverage --filter BusinessLocation
```

---

## Dependencies
- TASK-35-1 (Database Migrations) - Done ✅
- User model - Exists
- Site model - Exists

---

## Notes for Developer

### Missing Model Methods to Implement
1. **`full_address` accessor** - Format name + address for display
   ```php
   public function getFullAddressAttribute(): string
   {
       return $this->name . ' - ' . $this->address;
   }
   ```

2. **`scopeForUser()`** - Filter by user ID
   ```php
   public function scopeForUser($query, int $userId)
   {
       return $query->where('user_id', $userId);
   }
   ```

3. **`getMapsUrl()`** - Generate Google Maps URL
   ```php
   public function getMapsUrl(): string
   {
       if ($this->gbp_place_id) {
           return "https://www.google.com/maps/place/?q=place_id:{$this->gbp_place_id}";
       }
       if ($this->latitude && $this->longitude) {
           return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
       }
       return 'https://www.google.com/maps/search/' . urlencode($this->name . ' ' . $this->address);
   }
   ```

4. **`hasValidGBPToken()`** - Check if GBP token is valid
   ```php
   public function hasValidGBPToken(): bool
   {
       return $this->gbp_access_token 
           && $this->gbp_token_expires_at 
           && $this->gbp_token_expires_at->isFuture();
   }
   ```

5. **`getSettingsValue()`** - Get value from settings JSON
   ```php
   public function getSettingsValue(string $key, $default = null)
   {
       return data_get($this->settings, $key, $default);
   }
   ```

### Existing Model Methods (Already Implemented)
- `scopeActive()` - Filter active locations
- `scopeGbpConnected()` - Filter GBP connected locations
- `getGoogleLocationIdAttribute()` - Alias for gbp_place_id
- `getIsGbpConnectedAttribute()` - Alias for gbp_connected
- `user()`, `site()`, `alerts()`, `localRankings()`, `localCompetitors()` relationships

### Test Approach
- Tests use RefreshDatabase trait for clean state
- Factory creates dependent models (User, Site) automatically
- Tests verify both existence and behavior
- Security tests ensure tokens never exposed in API responses

---

## Comments

### PreTester Session (2026-03-08 23:45 GMT+2)
- **Status:** `To Develop` - Tests designed and ready for Developer
- **Test Files Created:**
  - `TESTS/TEST-35-2.md` - Test documentation (85+ tests)
  - `backend/tests/Unit/Models/BusinessLocationTest.php` - Unit tests
- **Test Coverage:**
  - Model instantiation and mass assignment
  - All type casts verified
  - All relationships tested
  - All scopes tested
  - All accessors tested
  - Hidden attributes (tokens) verified
  - Factory states tested
  - Edge cases and security tested

**Existing Model Analysis:**
- Model already implements HasFactory, SoftDeletes
- Relationships defined: user(), site(), alerts(), localRankings(), localCompetitors()
- Scopes defined: scopeActive(), scopeGbpConnected()
- Accessors defined: getGoogleLocationIdAttribute(), getIsGbpConnectedAttribute()
- Hidden attributes: gbp_access_token, gbp_refresh_token

**Missing Methods Identified:**
1. `full_address` accessor - Format name + address
2. `scopeForUser()` - Filter by user ID
3. `getMapsUrl()` - Generate Google Maps URL
4. `hasValidGBPToken()` - Check token validity
5. `getSettingsValue()` - Get value from settings JSON

**Test Organization:**
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

**Notes for Developer:**
1. Run tests with: `php artisan test --filter BusinessLocationTest`
2. 5 additional methods to implement (documented above)
3. Existing model methods already cover core functionality
4. Factory already exists with states: inactive(), withGbp(), withoutGbp()
5. Tests verify tokens are hidden from serialization

**Key Decisions:**
- Soft deletes used for data preservation
- GBP tokens hidden from all API responses
- Settings stored as JSON for flexibility
- Decimal precision (10,8) for lat/lng coordinates

### LocalTester Session (2026-03-10 13:09 GMT+2)
- **Result:** PASS — task approved for deployment handoff.

**Executed suites:**
| Command | Result |
|---------|--------|
| `php artisan test tests/Unit/Models/BusinessLocationTest.php` | ✅ 97 passed / 143 assertions |
| `php artisan test tests/Unit/Policies/BusinessLocationPolicyTest.php` | ✅ 23 passed / 23 assertions |
| `php artisan test tests/Unit/Http/Requests/LocalSEO/StoreBusinessLocationRequestTest.php` | ✅ 44 passed / 65 assertions |
| `php artisan test tests/Unit/Http/Requests/LocalSEO/UpdateBusinessLocationRequestTest.php` | ✅ 35 passed / 44 assertions |

**Validation summary:**
- Model acceptance coverage is green end-to-end.
- Relationship contract, helper/accessor methods, scopes, soft-delete semantics, factory states, hidden token behavior, and adversarial string inputs all behaved as expected.
- Additional narrow safety checks through policy/request suites passed, reducing risk for downstream controller/service use.

**Adjacent non-gating observation:**
- `tests/Feature/Api/V1/LocalSEO/BusinessLocationControllerTest.php` remains red on an existing mock-signature issue (`...closure() ... exactly 2 expected` at line 2071). This did not block TASK-35-2 because the model gate was already satisfied and the failure is in the controller-layer test harness, not in the model/factory contract under test.
