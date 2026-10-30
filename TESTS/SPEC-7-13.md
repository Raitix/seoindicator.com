# TESTS/SPEC-7-13.md — Integration with Ranking Check Workflow

## Task Reference
- Task: [TASK-7-13](../TASKS/TASK-7-13.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Medium

---

## Test Strategy

### Test Type
- **Primary:** Integration Tests (Service-level)
- **Secondary:** Unit Tests (Edge cases)

### Test Location
- `backend/tests/Unit/Services/RankingServiceFeatureIntegrationTest.php`
- `backend/tests/Integration/Feature/RankingWorkflowIntegrationTest.php`

---

## Test Scenarios

### 1. Core Integration Tests

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| T1 | Ranking check triggers feature detection | `SERPFeatureDetector::detectFeatures()` called after SERP fetch |
| T2 | Features stored in serp_features table | Correct `ranking_id`, `keyword_id`, `feature_type` inserted |
| T3 | Rankings.serp_features JSON updated | JSON column contains feature summary |
| T4 | Change tracker triggered on changes | `SERPFeatureChangeTracker::trackChanges()` called |
| T5 | Feature detection failure doesn't break ranking | Ranking still returned, error logged |

### 2. Configuration Tests

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| T6 | Site with enableSerpFeatures=true | Feature detection runs |
| T7 | Site with enableSerpFeatures=false | Feature detection skipped |
| T8 | Site without config flag (default) | Feature detection skipped (safe default) |

### 3. Error Handling Tests

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| T9 | Feature detector throws exception | Logged, ranking returned successfully |
| T10 | Feature repository throws exception | Logged, ranking returned successfully |
| T11 | Change tracker throws exception | Logged, ranking returned successfully |
| T12 | Malformed SERP data | Graceful handling, no crash |

### 4. Performance Tests

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| T13 | Feature detection adds <1 second | Total check time within acceptable bounds |
| T14 | Large SERP result (100 entries) | Still completes within time limit |

### 5. Edge Cases

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| T15 | No previous features (first check) | No 'gained' events recorded |
| T16 | Empty SERP result | No features detected, no crash |
| T17 | Null keyword.site | Feature detection skipped gracefully |
| T18 | Multiple features detected | All stored correctly |

### 6. Security Tests

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| T19 | SQL injection in feature data | Sanitized, no injection |
| T20 | XSS in feature snippet/title | Escaped before storage |

---

## Test Data

### Mock SERP Result with Features
```json
{
  "keyword": "best seo tools 2026",
  "entries": [
    {"position": 1, "url": "https://example.com/seo-tools", "title": "Best SEO Tools"},
    {"position": 2, "url": "https://competitor.com/tools", "title": "SEO Tools List"}
  ],
  "answerBox": {
    "type": "featured_snippet",
    "url": "https://example.com/seo-tools",
    "title": "Best SEO Tools",
    "snippet": "The best SEO tools for 2026 include..."
  },
  "localPack": [
    {"position": 1, "url": "https://local-business.com", "name": "Local SEO Co"}
  ],
  "serpFeatures": ["featured_snippet", "local_pack"]
}
```

### Mock Feature Detection Result
```php
[
    ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com/seo-tools'],
    ['type' => 'local_pack', 'is_present' => false],
    ['type' => 'image_pack', 'is_present' => false],
    ['type' => 'sitelinks', 'is_present' => true]
]
```

---

## Mock Dependencies

### Services to Mock
- `SERPFetcherInterface` - Returns mock SERP data
- `SERPFeatureDetector` - Returns mock feature detection results
- `SERPFeatureRepository` - Tracks storage calls
- `SERPFeatureChangeTracker` - Tracks change detection calls

### Models to Mock
- `Keyword` - With site relationship
- `Site` - With `enableSerpFeatures` attribute
- `Ranking` - Return value from service

---

## Assertions

### Database Assertions
- `assertDatabaseHas('serp_features', [...])`
- `assertDatabaseCount('serp_features', N)`
- `assertDatabaseHas('serp_feature_history', [...])`

### Service Assertions
- `Mockery::shouldReceive('detectFeatures')->once()`
- `Mockery::shouldReceive('trackChanges')->once()`

### Log Assertions
- `Log::shouldReceive('error')->with(...)` for failure scenarios
- `Log::shouldReceive('info')->with(...)` for debugging

---

## Test File Structure

```
backend/tests/
├── Unit/
│   └── Services/
│       └── RankingServiceFeatureIntegrationTest.php
└── Integration/
    └── Feature/
        └── RankingWorkflowIntegrationTest.php
```

---

## Coverage Targets

| Metric | Target |
|--------|--------|
| Line Coverage | ≥85% |
| Branch Coverage | ≥80% |
| Method Coverage | 100% (integration methods) |

---

## Dependencies

- TASK-7-4 (SERP Feature Detector) - Mock for testing
- TASK-7-8 (Feature Change Tracker) - Mock for testing
- TASK-7-3 (SERP Feature Repository) - Mock for testing

---

## Notes for Developer

1. The ranking service must NOT throw exceptions on feature detection failures
2. All feature detection code must be wrapped in try-catch
3. Performance requirement: Feature detection must add <1 second
4. Configuration is per-site via `enableSerpFeatures` flag
5. First check should NOT trigger 'gained' events (no previous state)

---

## Comments

### [2026-02-26 17:10] PreTester
Test design complete. Integration tests focus on the workflow:
1. Ranking check → Feature detection → Storage → Change tracking
2. Error isolation: Feature failures must not break ranking checks
3. Configuration-based enable/disable per site

15 unit tests + 5 integration tests designed.
