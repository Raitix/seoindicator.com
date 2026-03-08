# TASK-35-6: Nominatim Geocoding Service

## STATUS
`To Write Tests`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Low`

## Description
Create NominatimService for free geocoding using OpenStreetMap's Nominatim API. This replaces paid geocoding services like Google Geocoding API.

## Acceptance Criteria
- [ ] NominatimService created with interface
- [ ] Method: geocode(string $address): ?array
  - Returns ['lat' => float, 'lng' => float, 'display_name' => string] or null
  - Handles API errors gracefully
- [ ] Method: reverseGeocode(float $lat, float $lng): ?string
  - Returns address string from coordinates
- [ ] Rate limiting: 1 request per second maximum
  - Uses Laravel's RateLimiter or similar
- [ ] Caching: 30-day TTL for geocoding results
  - Uses Laravel Cache
- [ ] User-Agent header set (required by Nominatim ToS)
- [ ] Proper error handling for network failures
- [ ] Configurable API endpoint (default: https://nominatim.openstreetmap.org)
- [ ] Unit tests with mocked HTTP responses
- [ ] Feature tests for integration

## Dependencies
- None (utility service)

## Effort Estimate
2 hours

## Technical Notes
- Nominatim is FREE but rate-limited to 1 request/second
- Must include User-Agent header with app name and contact
- Consider adding fallback geocoding providers in future
- Cache key format: "geocode:" . md5($address)

---

*Created by SpecSystemAnalyst on 2026-03-08*

---

## Comments

### ProjectManager Assessment (2026-03-08)

**Priority: HIGH (confirmed)**
- Foundation service for Local SEO feature
- Enables TASK-35-5 (BusinessLocationService) for geocoding functionality
- No dependencies - can proceed immediately
- Uses FREE tool (OpenStreetMap Nominatim) aligned with project constraints

**Risk: LOW (confirmed)**
- Standard Laravel service with external API
- Nominatim is free with reasonable rate limits (1 req/sec)
- No security concerns (read-only geocoding)
- Network failures handled gracefully with caching fallback

**Downstream Impact:**
- TASK-35-5 (BusinessLocationService) uses this for `createLocation()` and `updateLocation()`
- All location management functionality depends on this service
- No downstream blockers - this is a leaf utility service

**TDD Ready:**
- PreTester can design tests immediately with mocked HTTP responses
- No dependencies required to start test design
- Cache layer can be tested with Laravel Cache facade mocks

**Execution Order:** This task can proceed to PreTester immediately. It's independent and enables downstream services.
