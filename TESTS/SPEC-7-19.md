# TESTS/SPEC-7-19.md — Unit Tests for SERP Features

## Task Reference
- Task: [TASK-7-19](../TASKS/TASK-7-19.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: High

---

## Overview

Comprehensive unit tests for SERP feature-related classes and methods. Tests cover feature detection logic, repository operations, and change tracking with a focus on edge cases and adversarial scenarios.

---

## Test Files

| File | Purpose |
|------|---------|
| `backend/tests/Unit/Services/SerpFeatureServiceTest.php` | Service layer tests (exists) |
| `backend/tests/Unit/Repositories/SerpFeatureRepositoryTest.php` | Repository database operations |
| `backend/tests/Unit/Services/SerpFeatureChangeTrackerTest.php` | Change detection logic |
| `backend/tests/Unit/Http/Requests/SerpFeatureRequestTest.php` | Request validation (exists) |

---

## Test Scenarios

### 1. SerpFeatureRepository Tests

#### Database Operations

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| R-001 | Get current features for keyword | keywordId | Array of current feature records |
| R-002 | Get features by date range | keywordId, dateRange | Array of features within range |
| R-003 | Insert new feature record | featureData | Record created, ID returned |
| R-004 | Update existing feature | featureId, data | Record updated |
| R-005 | Delete features by keyword | keywordId | Features deleted, count returned |
| R-006 | Get site features with pagination | siteId, page, limit | Paginated results with meta |
| R-007 | Get total keywords for site | siteId | Count of keywords |
| R-008 | Get keywords with features count | siteId | Count of keywords with features |
| R-009 | Get feature counts by type | siteId | Array of counts by feature_type |
| R-010 | Get feature history | keywordId, dateRange | Array of historical features |

#### Edge Cases

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| R-011 | Non-existent keyword ID | Return empty array |
| R-012 | Empty date range | Return all features |
| R-013 | Invalid pagination params | Use defaults (page=1, limit=50) |
| R-014 | Null target URL | Store NULL in url column |
| R-015 | Very long snippet text | Truncate or store as-is (documented) |

#### Error Handling

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| R-016 | Database connection failure | Throw RepositoryException |
| R-017 | Invalid feature_type | Throw ValidationException |
| R-018 | Duplicate (ranking_id, feature_type) | Update existing record |

---

### 2. SerpFeatureChangeTracker Tests

#### Change Detection

| ID | Scenario | Current Features | Previous Features | Expected Changes |
|----|----------|------------------|-------------------|------------------|
| CT-001 | Feature gained | featured_snippet=true | featured_snippet=false | 1 gained event |
| CT-002 | Feature lost | featured_snippet=false | featured_snippet=true | 1 lost event |
| CT-003 | Multiple features changed | multiple true/false | multiple false/true | Multiple events |
| CT-004 | No changes | same features | same features | Empty array |
| CT-005 | First check (no previous) | featured_snippet=true | null/empty | Empty array (no gained) |
| CT-006 | URL change for same feature | url=A | url=B | Lost+A, Gained+B or URL change event |

#### Feature Types

| ID | Feature Type | Detection Scenario |
|----|--------------|-------------------|
| CT-007 | featured_snippet | Answer box detection |
| CT-008 | local_pack | Map pack position |
| CT-009 | image_pack | Image carousel |
| CT-010 | video | Video thumbnail |
| CT-011 | people_also_ask | PAA section |
| CT-012 | knowledge_panel | Entity sidebar |
| CT-013 | sitelinks | Additional links |
| CT-014 | reviews | Star ratings |

#### Edge Cases

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| CT-015 | Empty current features array | All previous features = lost |
| CT-016 | Empty previous features array | No events (first check) |
| CT-017 | Null is_present values | Treated as false |
| CT-018 | Unknown feature type | Ignored, no crash |
| CT-019 | Malformed feature object | Logged, skipped |

#### History Recording

| ID | Scenario | Expected Database Record |
|----|----------|-------------------------|
| CT-020 | Gained event recorded | serp_feature_history with action='gained' |
| CT-021 | Lost event recorded | serp_feature_history with action='lost' |
| CT-022 | Previous URL stored | previous_url column populated |
| CT-023 | Current URL stored | current_url column populated |
| CT-024 | Detection timestamp | detected_at set to current time |

---

### 3. SerpFeatureService Tests (Existing)

The existing `SerpFeatureServiceTest.php` covers:
- `getKeywordFeatures()` - Get features for a keyword
- `getSiteFeatures()` - Get paginated features for a site
- `getSummary()` - Get feature summary statistics
- `getHistory()` - Get feature history
- `getChanges()` - Get feature changes

Additional tests needed:

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| S-001 | Service handles repository exception | Returns empty data with logged error |
| S-002 | Service validates input parameters | Throws ValidationException for invalid input |
| S-003 | Service caches frequent queries | Cache hit reduces DB calls |
| S-004 | Service clears cache on update | New data reflected after update |

---

### 4. Adversarial Tests

#### Input Validation

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| A-001 | SQL injection in feature_type | `'; DROP TABLE serp_features;--` | Sanitized, stored as literal string |
| A-002 | XSS in snippet/title | `<script>alert('xss')</script>` | Escaped before storage |
| A-003 | Very long URL | 10000+ characters | Truncated or validation error |
| A-004 | Unicode in feature data | Emojis, RTL text | Stored correctly |
| A-005 | Null byte injection | `feature\u0000type` | Sanitized |

#### Boundary Testing

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| A-006 | Maximum keywords in site | 10000+ keywords | Paginated correctly |
| A-007 | Maximum features per keyword | All 10 feature types | All stored correctly |
| A-008 | Maximum history records | 365+ days of history | Query completes in <1s |
| A-009 | Concurrent feature updates | Same keyword, parallel requests | No race condition |

---

## Mock Data

### Sample SERP Feature Object
```php
[
    'type' => 'featured_snippet',
    'is_present' => true,
    'position' => 1,
    'url' => 'https://example.com/seo-tools',
    'title' => 'Best SEO Tools 2026',
    'snippet' => 'The best SEO tools for 2026 include...',
    'checked_at' => '2026-02-26 10:00:00'
]
```

### Sample Feature Change Object
```php
[
    'type' => 'featured_snippet',
    'action' => 'gained',
    'keyword_id' => 1,
    'previous_url' => null,
    'current_url' => 'https://example.com/seo-tools',
    'detected_at' => '2026-02-26T10:00:00Z'
]
```

### Sample Repository Response
```php
[
    'data' => [
        ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com'],
        ['type' => 'sitelinks', 'is_present' => true],
        ['type' => 'local_pack', 'is_present' => false]
    ],
    'total' => 50,
    'page' => 1,
    'limit' => 20
]
```

---

## Coverage Targets

| Component | Target Coverage |
|-----------|-----------------|
| SerpFeatureRepository | 100% |
| SerpFeatureChangeTracker | 100% |
| SerpFeatureService | 100% (existing tests) |

---

## Test Execution

```bash
# Run all SERP feature unit tests
php artisan test --filter=SerpFeature

# Run specific test file
php artisan test tests/Unit/Repositories/SerpFeatureRepositoryTest.php

# Run with coverage
php artisan test --coverage --filter=SerpFeature
```

---

## Dependencies

- TASK-7-3: SerpFeatureRepository (tested via mocks initially)
- TASK-7-4: SERP Feature Detector (tested via service)
- TASK-7-8: SerpFeatureChangeTracker (tested via mocks)
- TASK-7-9: SerpFeatureHistoryRepository (tested via mocks)

---

## Notes for Developer

1. All repository methods must throw typed exceptions (RepositoryException, ValidationException)
2. Change tracker must be idempotent - same input produces same output
3. First-time detection should NOT produce 'gained' events
4. Feature types are validated against a constant list
5. All timestamps are UTC

---

## Comments

### [2026-02-26 17:20] Agent: PreTester
Created test documentation. Test structure:
- Repository tests: 25 scenarios
- Change tracker tests: 24 scenarios
- Adversarial tests: 9 scenarios

Tests define expected interface behavior for TDD implementation.
