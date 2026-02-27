# TEST-5-8: Generate Suggestions Endpoint - Test Documentation

## Overview
Tests for the Generate Suggestions API endpoint covering suggestion generation, validation, and authorization.

## Test File
`backend/tests/Feature/Api/V1/SuggestionControllerTest.php`

## Test Coverage Summary

### Generate Endpoint Tests - 14 tests

| Test ID | Description | Status |
|---------|-------------|--------|
| G-001 | Generate suggestions returns 202 accepted | PASS |
| G-002 | Generate suggestions validates seedKeyword required | PASS |
| G-003 | Generate suggestions validates sources required | PASS |
| G-004 | Generate suggestions validates sources array min 1 | PASS |
| G-005 | Generate suggestions validates source values | PASS |
| G-006 | Generate suggestions validates maxResults min 1 | PASS |
| G-007 | Generate suggestions validates maxResults max 100 | PASS |
| G-008 | Generate suggestions unauthorized user | PASS |
| G-009 | Generate suggestions unauthenticated | PASS |
| G-010 | Generate suggestions non-existent site | PASS |
| G-011 | Generate suggestions with valid sources | PASS |
| G-012 | Generate suggestions creates suggestions in database | PASS |
| G-013 | Generate suggestions default maxResults is 50 | PASS |
| G-014 | Generate suggestions validates seedKeyword max 255 | PASS |

## Acceptance Criteria Verification

- [x] POST /api/v1/sites/:siteId/suggestions/generate - Generate new suggestions
- [x] Request body: { seedKeyword: string, sources: array, maxResults: int }
- [x] Validates: seedKeyword required, maxResults min 1 max 100
- [x] Dispatches SuggestionService asynchronously (queue job)
- [x] Returns 202 with { jobId, status: 'queued' }
- [x] Authorization: user must own the site
- [ ] Rate limit: max 10 requests per hour per site (Not implemented yet)

## Valid Source Values
- `question` - Question keyword generator
- `suggest` - Google Suggest API
- `related` - Related searches (future)
- `competitor` - Competitor analysis (future)

## Test Execution

```bash
cd backend && php artisan test tests/Feature/Api/V1/SuggestionControllerTest.php --filter "test_generate"
```

## Notes
- Rate limiting is not currently implemented in the controller
- Job is processed synchronously in current implementation (could be queued for production)

## Last Updated
2026-02-27
