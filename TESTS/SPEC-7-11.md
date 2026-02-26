# TESTS/SPEC-7-11: Feature Summary Endpoint Tests

## Task Reference
- Task: [TASK-7-11](../TASKS/TASK-7-11.md)
- Specification: [SPEC-007](../SPECIFICATIONS/SPEC-007.md)
- Feature: [FEAT-007](../FEATURES/FEAT-007.md)
- Priority: Medium
- Risk: Low

---

## Overview

Tests for the `GET /api/v1/sites/:siteId/serp-features/summary` endpoint. This endpoint provides dashboard-ready SERP feature statistics including totals, feature counts by type, and recent changes (gained/lost features).

---

## Endpoint Under Test

```
GET /api/v1/sites/:siteId/serp-features/summary
```

### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `days` | integer | 7 | Time range for recentChanges (1-365) |

---

## Response Structure

### Success Response (200)

```json
{
  "success": true,
  "data": {
    "totalKeywords": 150,
    "keywordsWithFeatures": 47,
    "featureCounts": {
      "featured_snippet": 5,
      "local_pack": 3,
      "image_pack": 2,
      "video": 0,
      "people_also_ask": 8,
      "knowledge_panel": 1,
      "sitelinks": 12,
      "reviews": 10,
      "faq": 4,
      "product": 2
    },
    "recentChanges": {
      "gained": [
        {
          "keyword": "seo tools",
          "keywordId": 42,
          "feature": "featured_snippet",
          "detectedAt": "2026-02-25T10:30:00Z"
        }
      ],
      "lost": [
        {
          "keyword": "keyword research",
          "keywordId": 15,
          "feature": "local_pack",
          "detectedAt": "2026-02-24T14:20:00Z"
        }
      ]
    }
  }
}
```

### Error Response (404)

```json
{
  "success": false,
  "error": {
    "code": "SITE_NOT_FOUND",
    "message": "Site with ID 999 not found"
  }
}
```

---

## Test Scenarios

### 1. Success Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| S-001 | Returns complete summary for valid site | Valid siteId | 200 with all summary fields |
| S-002 | Returns zero counts for site with no keywords | Site with 0 keywords | 200 with `totalKeywords: 0`, all counts 0 |
| S-003 | Returns empty arrays for no recent changes | Site with no changes in period | 200 with empty `gained`/`lost` arrays |
| S-004 | Uses default 7-day range | No `days` parameter | 200 with changes from last 7 days |
| S-005 | Custom time range | `?days=30` | 200 with changes from last 30 days |
| S-006 | Minimum time range (1 day) | `?days=1` | 200 with changes from last 24 hours |
| S-007 | Maximum time range (365 days) | `?days=365` | 200 with changes from last year |
| S-008 | Site with all feature types present | Site with all 10 feature types | 200 with all feature counts > 0 |
| S-009 | Site with only featured snippets | Site with featured_snippet only | 200, other feature counts = 0 |
| S-010 | Multiple gained features same day | Multiple gains in period | 200 with all gains listed |
| S-011 | Multiple lost features same day | Multiple losses in period | 200 with all losses listed |
| S-012 | Same keyword gained multiple features | One keyword, multiple new features | 200 with separate entries per feature |

### 2. Error Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| E-001 | Site not found | Non-existent siteId | 404 with `SITE_NOT_FOUND` error |
| E-002 | Invalid siteId format | `siteId=abc` | 400 with validation error |
| E-003 | Invalid siteId (negative) | `siteId=-1` | 400 with validation error |
| E-004 | Invalid siteId (zero) | `siteId=0` | 400 with validation error |
| E-005 | Invalid days parameter (non-numeric) | `?days=abc` | 400 with validation error |
| E-006 | Invalid days parameter (negative) | `?days=-5` | 400 with validation error |
| E-007 | Invalid days parameter (zero) | `?days=0` | 400 with validation error |
| E-008 | Invalid days parameter (too large) | `?days=500` | 400 with max limit error |
| E-009 | Invalid days parameter (float) | `?days=7.5` | 400 with validation error |

### 3. Response Field Validation

| ID | Field | Type | Required | Validation |
|----|-------|------|----------|------------|
| R-001 | `success` | boolean | Yes | Must be `true` |
| R-002 | `data.totalKeywords` | integer | Yes | >= 0 |
| R-003 | `data.keywordsWithFeatures` | integer | Yes | >= 0, <= totalKeywords |
| R-004 | `data.featureCounts` | object | Yes | Contains all 10 feature types |
| R-005 | `data.featureCounts.featured_snippet` | integer | Yes | >= 0 |
| R-006 | `data.featureCounts.local_pack` | integer | Yes | >= 0 |
| R-007 | `data.featureCounts.image_pack` | integer | Yes | >= 0 |
| R-008 | `data.featureCounts.video` | integer | Yes | >= 0 |
| R-009 | `data.featureCounts.people_also_ask` | integer | Yes | >= 0 |
| R-010 | `data.featureCounts.knowledge_panel` | integer | Yes | >= 0 |
| R-011 | `data.featureCounts.sitelinks` | integer | Yes | >= 0 |
| R-012 | `data.featureCounts.reviews` | integer | Yes | >= 0 |
| R-013 | `data.featureCounts.faq` | integer | Yes | >= 0 |
| R-014 | `data.featureCounts.product` | integer | Yes | >= 0 |
| R-015 | `data.recentChanges` | object | Yes | Contains gained/lost arrays |
| R-016 | `data.recentChanges.gained` | array | Yes | Can be empty |
| R-017 | `data.recentChanges.lost` | array | Yes | Can be empty |
| R-018 | `gained[].keyword` | string | Yes | Non-empty |
| R-019 | `gained[].keywordId` | integer | Yes | Valid keyword ID |
| R-020 | `gained[].feature` | string | Yes | Valid feature type |
| R-021 | `gained[].detectedAt` | string (ISO) | Yes | Valid ISO 8601 datetime |
| R-022 | `lost[].keyword` | string | Yes | Non-empty |
| R-023 | `lost[].keywordId` | integer | Yes | Valid keyword ID |
| R-024 | `lost[].feature` | string | Yes | Valid feature type |
| R-025 | `lost[].detectedAt` | string (ISO) | Yes | Valid ISO 8601 datetime |

### 4. Performance Tests

| ID | Scenario | Input | Expected Performance |
|----|----------|-------|---------------------|
| P-001 | Small site (< 100 keywords) | Site with 50 keywords | Response < 200ms |
| P-002 | Medium site (100-500 keywords) | Site with 300 keywords | Response < 500ms |
| P-003 | Large site (1000+ keywords) | Site with 1500 keywords | Response < 500ms |
| P-004 | Site with many feature changes | 100+ changes in period | Response < 1000ms |
| P-005 | Concurrent requests | 10 simultaneous requests | No race conditions, all succeed |

### 5. Caching Tests

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| C-001 | First request (cache miss) | Returns data, caches result |
| C-002 | Second request (cache hit) | Returns cached data faster |
| C-003 | Cache invalidation on new ranking | After ranking check, cache cleared |
| C-004 | Cache respects site isolation | Different sites have separate caches |
| C-005 | Cache TTL expiration | After TTL, fresh data fetched |

### 6. Edge Cases

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| EC-001 | Site exists but has no keywords | Return `totalKeywords: 0`, all counts 0, empty changes |
| EC-002 | Site has keywords but no SERP features | Return `keywordsWithFeatures: 0`, all counts 0 |
| EC-003 | Feature type not in count (new type) | Ignore unknown feature types |
| EC-004 | Very large site ID | Handle gracefully, validate as integer |
| EC-005 | URL-encoded parameters | Decode properly |
| EC-006 | Duplicate changes in history | Return unique changes only |
| EC-007 | Changes at exact boundary of time range | Include changes at boundary timestamp |
| EC-008 | Keyword deleted after feature gained | Exclude from results |
| EC-009 | All keywords have all features | Return correct counts (counts = totalKeywords) |

### 7. Security Tests

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| SEC-001 | SQL injection in siteId | Sanitized, 400 error |
| SEC-002 | SQL injection in days param | Sanitized, 400 error |
| SEC-003 | XSS attempt in response | Sanitized output |
| SEC-004 | Access to another user's site | 403 Forbidden (when auth enabled) |
| SEC-005 | Rate limiting | 429 when limit exceeded |
| SEC-006 | Missing auth token | 401 Unauthorized (when auth enabled) |

### 8. Data Integrity Tests

| ID | Scenario | Validation |
|----|----------|------------|
| D-001 | Total keywords count matches | `totalKeywords` = count of keywords for site |
| D-002 | Keywords with features count accurate | `keywordsWithFeatures` = count of keywords with >= 1 feature |
| D-003 | Feature counts sum check | Sum of all featureCounts >= keywordsWithFeatures |
| D-004 | Recent changes within time range | All `detectedAt` within `days` range |
| D-005 | No duplicate entries in changes | Each keyword+feature combination appears once |
| D-006 | Feature types are valid | All feature types in response are from defined list |
| D-007 | Keyword IDs exist | All keywordIds reference existing keywords |

---

## Test Files

### API Integration Tests
| File | Description |
|------|-------------|
| `backend/tests/Feature/Api/V1/SerpFeatureSummaryTest.php` | Full endpoint integration tests |

### Unit Tests
| File | Description |
|------|-------------|
| `backend/tests/Unit/Services/SerpFeatureSummaryServiceTest.php` | Summary service logic tests |
| `backend/tests/Unit/Http/Requests/SerpFeatureSummaryRequestTest.php` | Request validation tests |

### Performance Tests
| File | Description |
|------|-------------|
| `backend/tests/Performance/SerpFeatureSummaryBenchmark.php` | Response time benchmarks |

---

## Test Data Setup

### Required Test Data

```php
// Test sites
$smallSite = Site::create(['id' => 100, 'url' => 'https://small-test.com', 'name' => 'Small Site']);
$mediumSite = Site::create(['id' => 101, 'url' => 'https://medium-test.com', 'name' => 'Medium Site']);
$largeSite = Site::create(['id' => 102, 'url' => 'https://large-test.com', 'name' => 'Large Site']);
$emptySite = Site::create(['id' => 103, 'url' => 'https://empty-test.com', 'name' => 'Empty Site']);

// Test keywords for small site
$keywords = [
    Keyword::create(['site_id' => 100, 'keyword' => 'seo tools', 'id' => 1]),
    Keyword::create(['site_id' => 100, 'keyword' => 'keyword tracker', 'id' => 2]),
    Keyword::create(['site_id' => 100, 'keyword' => 'backlink analysis', 'id' => 3]),
];

// SERP features
SerpFeature::create([
    'keyword_id' => 1,
    'feature_type' => 'featured_snippet',
    'is_present' => true,
    'url' => 'https://small-test.com/seo-tools',
    'checked_at' => now()
]);

SerpFeature::create([
    'keyword_id' => 1,
    'feature_type' => 'sitelinks',
    'is_present' => true,
    'checked_at' => now()
]);

SerpFeature::create([
    'keyword_id' => 2,
    'feature_type' => 'reviews',
    'is_present' => true,
    'checked_at' => now()
]);

// Feature history for changes
SerpFeatureHistory::create([
    'keyword_id' => 1,
    'feature_type' => 'featured_snippet',
    'action' => 'gained',
    'detected_at' => now()->subDays(2)
]);

SerpFeatureHistory::create([
    'keyword_id' => 3,
    'feature_type' => 'local_pack',
    'action' => 'lost',
    'detected_at' => now()->subDays(5)
]);
```

---

## Mock Service Response

### Summary Service Mock

```typescript
// Mock SerpFeatureService.getSummary()
const mockSummaryResponse = {
  totalKeywords: 3,
  keywordsWithFeatures: 2,
  featureCounts: {
    featured_snippet: 1,
    local_pack: 0,
    image_pack: 0,
    video: 0,
    people_also_ask: 0,
    knowledge_panel: 0,
    sitelinks: 1,
    reviews: 1,
    faq: 0,
    product: 0
  },
  recentChanges: {
    gained: [
      {
        keyword: 'seo tools',
        keywordId: 1,
        feature: 'featured_snippet',
        detectedAt: '2026-02-24T10:00:00Z'
      }
    ],
    lost: [
      {
        keyword: 'backlink analysis',
        keywordId: 3,
        feature: 'local_pack',
        detectedAt: '2026-02-21T14:00:00Z'
      }
    ]
  }
};
```

---

## Coverage Targets

| Category | Target Coverage |
|----------|-----------------|
| Line Coverage | >= 90% |
| Branch Coverage | >= 85% |
| Method Coverage | >= 95% |
| Critical Paths | 100% |

---

## Test Execution Commands

```bash
# Run all summary endpoint tests
npm test -- --testPathPattern="SerpFeatureSummary"

# Run integration tests only
npm run test:integration -- SerpFeatureSummaryTest

# Run unit tests only
npm run test:unit -- SerpFeatureSummaryServiceTest

# Run with coverage
npm run test:coverage -- --testPathPattern="SerpFeatureSummary"

# Run performance benchmarks
npm run test:performance -- SerpFeatureSummaryBenchmark
```

---

## Dependencies

- **TASK-7-3**: SERP Feature Repository (for data queries)
- **TASK-7-9**: SERP Feature History Repository (for recentChanges)
- **TASK-7-10**: API endpoints base structure

---

## Notes for Developer

1. **Caching Strategy**: Consider using Redis or similar for caching. Cache key should include siteId and invalidate on new ranking checks.

2. **Performance Optimization**: 
   - Use database aggregation for feature counts (single query with GROUP BY)
   - Consider materialized view for summary stats on large sites
   - Paginate recentChanges if list becomes large (> 100 items)

3. **Error Handling**:
   - Return user-friendly error messages
   - Log unexpected errors for debugging
   - Include request ID in error responses for support

4. **Response Time SLA**: 
   - The 500ms limit for 1000+ keywords is critical for UX
   - Profile queries and add indexes as needed
   - Consider async loading for recentChanges if performance degrades

5. **Future Enhancements** (not in scope):
   - Time series data for feature trends
   - Competitor feature comparison
   - Feature velocity metrics

---

## Comments

### [2026-02-26 18:10] Agent: PreTester
Created comprehensive test documentation for Feature Summary Endpoint. Tests cover:
- 12 success scenarios including edge cases
- 9 error cases with validation
- 25 response field validations
- 5 performance benchmarks with specific targets
- 5 caching test scenarios
- 9 edge cases
- 6 security tests
- 7 data integrity validations

All tests designed for TDD approach. Ready for Developer implementation.

---

Last updated: 2026-02-26
