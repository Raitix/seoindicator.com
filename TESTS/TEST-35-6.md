# TEST-35-6: Nominatim Geocoding Service

## 1. Task Reference
- Task: `TASK-35-6`
- Spec: `SPEC-035`
- Component: `backend/app/Services/LocalSEO/NominatimService.php`
- Contract: `backend/app/Services/LocalSEO/Contracts/NominatimServiceInterface.php`

## 2. Overview
This test plan covers the free Nominatim-based geocoding service used by Local SEO features.

Scope:
- service/interface contract
- forward geocoding
- reverse geocoding
- required HTTP headers
- error handling
- cache behavior
- base URL configurability
- 1 request/second throttling for uncached lookups
- Laravel container integration

Out of scope for this task:
- downstream `BusinessLocationService` integration logic
- fallback provider orchestration
- production monitoring / retry queues

## 3. Test Files
| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Services/LocalSEO/NominatimServiceTest.php` | Unit | Contract, HTTP mocking, cache/rate-limit expectations, error handling |
| `backend/tests/Feature/Services/LocalSEO/NominatimServiceIntegrationTest.php` | Feature | Container binding, config wiring, interface-level integration |

## 4. Acceptance Criteria → Test Mapping
| AC | Description | Planned Coverage |
|----|-------------|------------------|
| AC-1 | Service created with interface | `nom_cls_001`-`nom_cls_004`, `nom_int_001` |
| AC-2 | `geocode(string $address): ?array` | `nom_geo_001`-`nom_geo_010` |
| AC-3 | `reverseGeocode(float $lat, float $lng): ?string` | `nom_rev_001`-`nom_rev_007`, `nom_int_006` |
| AC-4 | 1 request/second rate limiting | `nom_geo_010` |
| AC-5 | 30-day caching via Laravel Cache | `nom_geo_008`-`nom_geo_009`, `nom_rev_006`-`nom_rev_007`, `nom_int_005` |
| AC-6 | User-Agent header set | `nom_geo_003`, `nom_int_004` |
| AC-7 | Network failure handling | `nom_geo_006`-`nom_geo_007`, `nom_rev_004`-`nom_rev_005` |
| AC-8 | Configurable endpoint | `nom_geo_004`, `nom_int_002`-`nom_int_003` |
| AC-9 | Unit tests with mocked HTTP responses | `NominatimServiceTest.php` |
| AC-10 | Feature tests for integration | `NominatimServiceIntegrationTest.php` |

## 5. Test Scenarios

### 5.1 Contract & Wiring
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NOM-CLS-001 | Class exists | Resolve class name | Class autoloads |
| NOM-CLS-002 | Implements interface | App service instance | `instanceof NominatimServiceInterface` |
| NOM-CLS-003 | Required methods exist | Reflection | `geocode`, `reverseGeocode` present |
| NOM-CLS-004 | Interface signature matches task | Reflection | `geocode(string): ?array`, `reverseGeocode(float,float): ?string` |
| NOM-INT-001 | Container binding | `app(NominatimServiceInterface::class)` | Returns `NominatimService` |

### 5.2 geocode()
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NOM-GEO-001 | Successful geocode | Full address string | `['lat' => float, 'lng' => float, 'display_name' => string]` |
| NOM-GEO-002 | Search endpoint formatting | Address string | Request hits `/search?format=jsonv2&limit=1&q=...` |
| NOM-GEO-003 | User-Agent header | Any address | Header contains app identity + contact |
| NOM-GEO-004 | Custom base URL | Config override | Request targets configured host |
| NOM-GEO-005 | Empty payload | No matches | Returns `null` |
| NOM-GEO-006 | HTTP 5xx | API failure | Returns `null`, no unhandled exception |
| NOM-GEO-007 | Network failure | Connection exception | Returns `null`, no unhandled exception |
| NOM-GEO-008 | Cache hit by deterministic key | Preloaded cache entry | Returns cached value without HTTP |
| NOM-GEO-009 | Repeated identical request | Same address twice | Single HTTP request, cached second result |
| NOM-GEO-010 | Throttling | Two different uncached addresses | ≥ 1 second elapsed |

### 5.3 reverseGeocode()
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NOM-REV-001 | Successful reverse lookup | Lat/lng | Human-readable address string |
| NOM-REV-002 | Reverse endpoint formatting | Lat/lng | Request hits `/reverse?format=jsonv2&lat=...&lon=...` |
| NOM-REV-003 | Missing display name | Payload without `display_name` | Returns `null` |
| NOM-REV-004 | HTTP 5xx | API failure | Returns `null` |
| NOM-REV-005 | Network failure | Connection exception | Returns `null` |
| NOM-REV-006 | Reverse cache hit | Preloaded cache entry | Returns cached string without HTTP |
| NOM-REV-007 | Repeated identical reverse lookup | Same coordinates twice | Single HTTP request, cached second result |

### 5.4 Integration
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NOM-INT-002 | Default endpoint wiring | Default config | Uses `https://nominatim.openstreetmap.org` |
| NOM-INT-003 | Custom endpoint wiring | Config override | Uses custom domain |
| NOM-INT-004 | Custom user-agent wiring | Config override | Exact header propagated |
| NOM-INT-005 | Cache reuse via interface | Same request twice via container | Cached second result |
| NOM-INT-006 | Reverse lookup via interface | Lat/lng | Returns address string |

## 6. Mock Data Samples
```php
$mockGeocodeResponse = [[
    'lat' => '56.9496',
    'lon' => '24.1052',
    'display_name' => 'Riga, Latvia',
]];

$mockReverseResponse = [
    'display_name' => 'Riga, Latvia',
];

$cachedGeocode = [
    'lat' => 56.9496,
    'lng' => 24.1052,
    'display_name' => 'Riga, Latvia',
];
```

## 7. Coverage Targets
| Area | Target |
|------|--------|
| Service contract | 100% |
| Happy-path geocoding | 100% |
| Error handling branches | 100% |
| Cache behavior | 90%+ |
| Config wiring | 100% |
| Acceptance criteria traceability | 100% |

## 8. Test Execution Commands
```bash
cd backend
php artisan test --filter NominatimService
```

Optional narrower commands:
```bash
cd backend
php artisan test tests/Unit/Services/LocalSEO/NominatimServiceTest.php
php artisan test tests/Feature/Services/LocalSEO/NominatimServiceIntegrationTest.php
```

## 9. Dependencies
- Laravel HTTP client facade (`Http::fake` / `Http::assertSent`)
- Laravel cache facade
- App service container binding in `AppServiceProvider`
- `config/services.php` Nominatim configuration block

## 10. Notes for Developer
1. Normalize forward geocode output to exactly:
   ```php
   ['lat' => float, 'lng' => float, 'display_name' => string]
   ```
2. Reverse geocode should return only the address string, not the full payload.
3. Use deterministic cache keys:
   - `geocode:` . `md5($address)`
   - `reverse_geocode:` . `md5("{$lat},{$lng}")`
4. Cache TTL must be **30 days**.
5. Include a valid Nominatim `User-Agent` header on every request.
6. Respect Nominatim’s **1 request/second** rule for uncached calls.
7. Gracefully return `null` on API errors and transport failures.
8. Keep the endpoint configurable via `config/services.php`.
9. Downstream `BusinessLocationService` work should consume the normalized `lat` / `lng` contract from this task.

## 11. Comments
### PreTester Session (2026-03-10 10:45 GMT+2)
- Drafted red-phase tests for TASK-35-6.
- Normalized the interface contract to the task acceptance criteria (`geocode(string $address)` and `reverseGeocode(float $lat, float $lng)`).
- Added a minimal service skeleton, service-provider binding, and config scaffolding so the test suite can compile and the Developer has a concrete contract to implement against.
- Targeted execution result: `5 passing / 22 failing` (expected red phase; failures map to unimplemented service behavior).
