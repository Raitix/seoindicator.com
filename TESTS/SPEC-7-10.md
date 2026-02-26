# TEST-7-10: SERP Features API Endpoints Tests

## Task Reference
- Task: [TASK-7-10](../TASKS/TASK-7-10.md)
- Specification: [SPEC-007](../SPECIFICATIONS/SPEC-007.md)
- Priority: High
- Risk: Low

---

## Overview

Tests for REST API endpoints exposing SERP feature data. Endpoints provide feature data for keywords and sites, with history tracking and filtering capabilities.

---

## API Endpoints Under Test

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/keywords/:id/serp-features` | GET | Get SERP features for a keyword |
| `/api/v1/sites/:siteId/serp-features` | GET | Get all SERP features for a site |
| `/api/v1/sites/:siteId/serp-features/summary` | GET | Get SERP feature summary |

---

## Test Scenarios

### 1. GET /api/v1/keywords/:id/serp-features

#### Success Cases
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KF-001 | Returns current features for keyword | Valid keyword ID | 200 with features array |
| KF-002 | Returns empty features for keyword with no data | Valid keyword ID, no features | 200 with empty `currentFeatures` array |
| KF-003 | Returns history with date range filter | `?from=2026-02-01&to=2026-02-20` | 200 with filtered history |
| KF-004 | Returns changes (gained/lost) | Valid keyword ID with history | 200 with `changes` array |
| KF-005 | Returns keyword info in response | Valid keyword ID | 200 with `keyword.id` and `keyword.keyword` |

#### Error Cases
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KE-001 | Keyword not found | Invalid keyword ID | 404 with error message |
| KE-002 | Invalid keyword ID format | Non-numeric ID | 400 with validation error |
| KE-003 | Invalid date format in query | `?from=invalid-date` | 400 with validation error |
| KE-004 | Date range exceeds limit | `?from=2020-01-01&to=2026-12-31` | 400 with date range limit error |
| KE-005 | From date after to date | `?from=2026-02-20&to=2026-02-01` | 400 with invalid range error |

#### Response Structure Validation
| ID | Field | Type | Required |
|----|-------|------|----------|
| KR-001 | `success` | boolean | Yes |
| KR-002 | `data.keyword.id` | integer | Yes |
| KR-003 | `data.keyword.keyword` | string | Yes |
| KR-004 | `data.currentFeatures` | array | Yes |
| KR-005 | `data.currentFeatures[].type` | string | Yes |
| KR-006 | `data.currentFeatures[].isPresent` | boolean | Yes |
| KR-007 | `data.currentFeatures[].url` | string | No |
| KR-008 | `data.currentFeatures[].snippet` | string | No |
| KR-009 | `data.history` | array | Yes |
| KR-010 | `data.history[].date` | string (ISO date) | Yes |
| KR-011 | `data.history[].features` | object | Yes |
| KR-012 | `data.changes` | array | Yes |

---

### 2. GET /api/v1/sites/:siteId/serp-features

#### Success Cases
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SF-001 | Returns all features for site | Valid site ID | 200 with paginated features |
| SF-002 | Supports pagination | `?page=2&limit=20` | 200 with correct page |
| SF-003 | Returns pagination metadata | Valid site ID | 200 with `meta.total`, `meta.page`, `meta.limit` |
| SF-004 | Groups features by keyword | Valid site ID | 200 with features grouped by keyword |

#### Error Cases
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SE-001 | Site not found | Invalid site ID | 404 with error message |
| SE-002 | Invalid site ID format | Non-numeric ID | 400 with validation error |
| SE-003 | Invalid pagination params | `?page=-1` | 400 with validation error |
| SE-004 | Limit exceeds maximum | `?limit=1000` | 400 with limit exceeded error |

---

### 3. GET /api/v1/sites/:siteId/serp-features/summary

#### Success Cases
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SS-001 | Returns feature summary | Valid site ID | 200 with summary stats |
| SS-002 | Returns feature counts | Valid site ID | 200 with `featureCounts` object |
| SS-003 | Returns recent changes | Valid site ID | 200 with `recentChanges.gained/lost` |
| SS-004 | Returns keywords with features count | Valid site ID | 200 with `keywordsWithFeatures` |

#### Summary Response Structure
| ID | Field | Type | Description |
|----|-------|------|-------------|
| SR-001 | `data.totalKeywords` | integer | Total tracked keywords |
| SR-002 | `data.keywordsWithFeatures` | integer | Keywords with SERP features |
| SR-003 | `data.featureCounts` | object | Count by feature type |
| SR-004 | `data.recentChanges.gained` | array | Recently gained features |
| SR-005 | `data.recentChanges.lost` | array | Recently lost features |

---

## Validation Tests

### Request Validation Middleware
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| V-001 | Validate keyword ID is integer | `abc` | 400 with "Invalid keyword ID" |
| V-002 | Validate site ID is integer | `xyz` | 400 with "Invalid site ID" |
| V-003 | Validate date format (ISO 8601) | `02/20/2026` | 400 with "Invalid date format" |
| V-004 | Validate pagination limit max | `limit=500` | 400 with "Limit exceeds maximum" |
| V-005 | Validate pagination page min | `page=0` | 400 with "Page must be >= 1" |

---

## Rate Limiting Tests

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| RL-001 | Requests within rate limit | 200 responses |
| RL-002 | Requests exceed rate limit | 429 with Retry-After header |
| RL-003 | Rate limit per IP | Different IPs have separate limits |
| RL-004 | Rate limit response format | `{ "error": "rate_limit_exceeded", "retryAfter": 60 }` |

---

## Edge Cases

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| EC-001 | Keyword exists but no SERP data | Return empty arrays, not 404 |
| EC-002 | Site has no keywords | Return empty summary with zeros |
| EC-003 | Feature type with special characters | Handle gracefully |
| EC-004 | Very long keyword ID (big int) | Validate numeric range |
| EC-005 | Concurrent requests for same keyword | No race conditions |
| EC-006 | History date range with no data | Return empty history array |

---

## Security Tests

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| SEC-001 | SQL injection in keyword ID | Sanitized, 400 error |
| SEC-002 | SQL injection in date params | Sanitized, 400 error |
| SEC-003 | XSS in query parameters | Sanitized output |
| SEC-004 | Access to another user's site | 403 Forbidden (when auth enabled) |
| SEC-005 | Access to another user's keyword | 403 Forbidden (when auth enabled) |

---

## Performance Tests

| ID | Scenario | Expected Performance |
|----|----------|---------------------|
| P-001 | Response time for single keyword | < 100ms |
| P-002 | Response time for site with 100 keywords | < 500ms |
| P-003 | Response time for summary | < 200ms |
| P-004 | Response time with full history (365 days) | < 1000ms |

---

## Test Files

### Integration Tests
- `backend/tests/Feature/Api/V1/SerpFeatureControllerTest.php` - API endpoint tests

### Unit Tests
- `backend/tests/Unit/Services/SerpFeatureServiceTest.php` - Service layer tests
- `backend/tests/Unit/Http/Requests/SerpFeatureRequestTest.php` - Request validation tests

---

## Test Data Setup

### Required Test Data
```php
// Test site
$site = Site::create(['url' => 'https://test-example.com', 'name' => 'Test Site']);

// Test keywords
$keyword1 = Keyword::create(['site_id' => $site->id, 'keyword' => 'seo tools']);
$keyword2 = Keyword::create(['site_id' => $site->id, 'keyword' => 'keyword tracker']);

// Test SERP features
SerpFeature::create([
    'keyword_id' => $keyword1->id,
    'feature_type' => 'featured_snippet',
    'is_present' => true,
    'url' => 'https://test-example.com/seo-tools',
    'snippet' => 'Best SEO tools...',
    'checked_at' => now()
]);
```

---

## Mock Service Responses

### Successful Keyword Features Response
```json
{
  "success": true,
  "data": {
    "keyword": {
      "id": 1,
      "keyword": "seo tools"
    },
    "currentFeatures": [
      {
        "type": "featured_snippet",
        "isPresent": true,
        "url": "https://example.com/seo-tools",
        "snippet": "Best SEO tools for 2026..."
      },
      {
        "type": "sitelinks",
        "isPresent": true
      }
    ],
    "history": [
      {
        "date": "2026-02-19",
        "features": {
          "featured_snippet": true,
          "sitelinks": true
        }
      }
    ],
    "changes": [
      {
        "type": "featured_snippet",
        "action": "gained",
        "detectedAt": "2026-02-19T10:00:00Z"
      }
    ]
  }
}
```

### Error Response Format
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid keyword ID format",
    "details": {
      "field": "id",
      "value": "abc"
    }
  }
}
```

---

## Comments

### [2026-02-26 15:10] Agent: PreTester
Created comprehensive test documentation for SERP Features API endpoints. Tests cover:
- Success and error cases for all 3 endpoints
- Request validation middleware
- Rate limiting
- Edge cases and security scenarios
- Performance benchmarks

Tests are ready for implementation by Developer.

---

## Dependencies
- TASK-7-3: SERP Feature Repository (must exist for service tests)
- TASK-7-9: SERP Feature History Repository (must exist for history tests)
