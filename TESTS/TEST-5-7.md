# TEST-5-7: Suggestions CRUD API Endpoints - Test Documentation

## Overview
Tests for the Suggestions CRUD API endpoints covering listing, filtering, pagination, and export functionality.

## Test File
`backend/tests/Feature/Api/V1/SuggestionControllerTest.php`

## Test Coverage Summary

### Index (List) Endpoint Tests - 17 tests

| Test ID | Description | Status |
|---------|-------------|--------|
| L-001 | List suggestions returns paginated results | PASS |
| L-002 | List suggestions default excludes dismissed | PASS |
| L-003 | List suggestions default excludes tracking | PASS |
| L-004 | List suggestions include dismissed via param | PASS |
| L-005 | List suggestions include tracking via param | PASS |
| L-006 | List suggestions filter by source | PASS |
| L-007 | List suggestions filter by minScore | PASS |
| L-008 | List suggestions filter by maxScore | PASS |
| L-009 | List suggestions pagination page param | PASS |
| L-010 | List suggestions pagination perPage param | PASS |
| L-011 | List suggestions default sort by opportunity_score DESC | PASS |
| L-012 | List suggestions custom sort order | PASS |
| L-013 | List suggestions empty results | PASS |
| L-014 | List suggestions unauthorized user | PASS |
| L-015 | List suggestions unauthenticated | PASS |
| L-016 | List suggestions non-existent site | PASS |
| L-017 | List suggestions only shows own site suggestions | PASS |

### Export Endpoint Tests - 8 tests

| Test ID | Description | Status |
|---------|-------------|--------|
| E-001 | Export suggestions returns CSV | PASS |
| E-002 | Export suggestions includes headers | PASS |
| E-003 | Export suggestions excludes dismissed by default | PASS |
| E-004 | Export suggestions filter by source | PASS |
| E-005 | Export suggestions empty results | PASS |
| E-006 | Export suggestions unauthorized user | PASS |
| E-007 | Export suggestions unauthenticated | PASS |
| E-008 | Export suggestions non-existent site | PASS |

## Acceptance Criteria Verification

- [x] GET /api/v1/sites/:siteId/suggestions - List suggestions with pagination
- [x] Query params: source, minVolume, maxDifficulty, includeTracking, page, perPage
- [x] Response includes pagination metadata (total, page, perPage)
- [x] Default: exclude dismissed suggestions, exclude already tracking
- [x] Sort by opportunity_score DESC by default
- [x] Authorization: user must own the site
- [x] Returns 200 with suggestions array on success

## Test Execution

```bash
cd backend && php artisan test tests/Feature/Api/V1/SuggestionControllerTest.php --filter "test_list|test_export"
```

## Last Updated
2026-02-27
