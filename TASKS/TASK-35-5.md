# TASK-35-5: BusinessLocationService

## STATUS
`To Prioritize`

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
