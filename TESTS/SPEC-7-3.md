# TESTS/SPEC-7-3.md — SERP Feature Repository Tests

## Overview
Unit and integration tests for TASK-7-3: SERP Feature Repository layer implementation.

## Test File Locations
- Unit: `tests/unit/serp-feature-repo.test.js`
- Integration: `tests/integration/serp-feature-repo.integration.test.js`

---

## 1. SERPFeatureRepository Class Tests

### 1.1 Class Structure Tests

| ID | Test Scenario | Expected Result |
|----|---------------|-----------------|
| 1.1.1 | Class should be instantiable | Instance created successfully |
| 1.1.2 | Class should accept database connection in constructor | Connection stored |
| 1.1.3 | Class should expose all required methods | All methods defined |
| 1.1.4 | Class should be singleton or export instance | Single instance pattern |

### 1.2 insert(feature: SERPFeature) Method Tests

| ID | Test Scenario | Input | Expected Result |
|----|---------------|-------|-----------------|
| 1.2.1 | Insert valid feature | Valid SERPFeature object | Returns inserted record with id |
| 1.2.2 | Insert with all optional fields | Feature with position, url, title, snippet | All fields stored correctly |
| 1.2.3 | Insert with minimal fields | Feature with only type and isPresent | Record created, optional fields null |
| 1.2.4 | Insert sets checked_at automatically | Feature without checked_at | checked_at set to current time |
| 1.2.5 | Insert with explicit checked_at | Feature with custom date | Date preserved correctly |
| 1.2.6 | Insert with invalid type | Feature with wrong type string | Throws validation error |
| 1.2.7 | Insert with null feature | null input | Throws error |
| 1.2.8 | Insert with missing required fields | Feature without type | Throws validation error |
| 1.2.9 | Insert with non-numeric ranking_id | ranking_id as string | Throws type error |
| 1.2.10 | Insert with non-numeric keyword_id | keyword_id as string | Throws type error |
| 1.2.11 | Insert duplicate (ranking_id, feature_type) | Same ranking_id + feature_type | Throws unique constraint error |
| 1.2.12 | Insert with non-existent ranking_id | ranking_id = 999999 | Foreign key constraint error |
| 1.2.13 | Insert with non-existent keyword_id | keyword_id = 999999 | Foreign key constraint error |

### 1.3 insertBatch(features: SERPFeature[]) Method Tests

| ID | Test Scenario | Input | Expected Result |
|----|---------------|-------|-----------------|
| 1.3.1 | Insert empty array | [] | Returns empty array |
| 1.3.2 | Insert single feature in batch | [feature] | Returns array with 1 record |
| 1.3.3 | Insert multiple features | [feature1, feature2, feature3] | Returns array with 3 records |
| 1.3.4 | Batch insert is atomic | One invalid feature in batch | Entire batch fails, transaction rollback |
| 1.3.5 | Batch insert performance | 100 features | Completes within timeout |
| 1.3.6 | Batch with mixed valid/invalid | [valid, invalid, valid] | Throws error, no records inserted |
| 1.3.7 | Batch with duplicate types in same batch | Same ranking_id, same type | Throws unique constraint error |
| 1.3.8 | Batch with same ranking_id different types | Same ranking_id, different types | All inserted successfully |

### 1.4 findByKeywordId(keywordId: number) Method Tests

| ID | Test Scenario | Input | Expected Result |
|----|---------------|-------|-----------------|
| 1.4.1 | Find by valid keyword_id | Existing keyword_id | Returns array of features |
| 1.4.2 | Find by non-existent keyword_id | 999999 | Returns empty array |
| 1.4.3 | Find with null keyword_id | null | Throws error |
| 1.4.4 | Find with string keyword_id | "1" | Throws type error or converts |
| 1.4.5 | Find with negative keyword_id | -1 | Returns empty or throws error |
| 1.4.6 | Find returns features in correct order | Multiple features for keyword | Ordered by checked_at DESC |
| 1.4.7 | Find includes all feature types | Keyword with multiple types | All types returned |
| 1.4.8 | Find returns only present features | Mixed present/not present | Returns both, isPresent flag set |

### 1.5 findByRankingId(rankingId: number) Method Tests

| ID | Test Scenario | Input | Expected Result |
|----|---------------|-------|-----------------|
| 1.5.1 | Find by valid ranking_id | Existing ranking_id | Returns array of features |
| 1.5.2 | Find by non-existent ranking_id | 999999 | Returns empty array |
| 1.5.3 | Find with null ranking_id | null | Throws error |
| 1.5.4 | Find with string ranking_id | "1" | Throws type error or converts |
| 1.5.5 | Find with negative ranking_id | -1 | Returns empty or throws error |
| 1.5.6 | Find returns all feature types for ranking | Ranking with multiple features | All features returned |
| 1.5.7 | Find respects unique constraint | One feature per type per ranking | Max 10 features (one per type) |

### 1.6 findFeaturesByType(featureType: SERPFeatureType, siteId?: number) Method Tests

| ID | Test Scenario | Input | Expected Result |
|----|---------------|-------|-----------------|
| 1.6.1 | Find by valid feature type | "featured_snippet" | Returns array of features |
| 1.6.2 | Find by invalid feature type | "invalid_type" | Throws validation error |
| 1.6.3 | Find by type with siteId filter | "featured_snippet", siteId=1 | Only features from site |
| 1.6.4 | Find by type without siteId | "featured_snippet", undefined | All features of that type |
| 1.6.5 | Find returns only isPresent=true | Mixed present states | Only features with isPresent=true |
| 1.6.6 | Find by type with null siteId | "featured_snippet", null | All features of that type |
| 1.6.7 | Find by type case sensitivity | "Featured_Snippet" | No results or throws error |
| 1.6.8 | Find returns features with related data | Valid type | Includes keyword info via join |
| 1.6.9 | Find for local_pack type | "local_pack" | Returns local_pack features |
| 1.6.10 | Find for product type | "product" | Returns product features |

### 1.7 getFeatureSummary(siteId: number) Method Tests

| ID | Test Scenario | Input | Expected Result |
|----|---------------|-------|-----------------|
| 1.7.1 | Get summary for valid site | Existing siteId | Returns feature counts object |
| 1.7.2 | Summary includes all feature types | Valid siteId | Object with all 10 types |
| 1.7.3 | Summary counts only present features | Mixed present states | Only isPresent=true counted |
| 1.7.4 | Summary for site with no features | Site without features | All counts are 0 |
| 1.7.5 | Summary for non-existent site | 999999 | Returns zero counts |
| 1.7.6 | Summary with null siteId | null | Throws error |
| 1.7.7 | Summary structure validation | Valid siteId | Returns {featured_snippet: n, ...} |
| 1.7.8 | Summary with recent features | Site with recent features | Accurate counts |
| 1.7.9 | Summary excludes deleted keywords | Deleted keyword | Not counted |
| 1.7.10 | Summary returns totalKeywords count | Valid siteId | Includes totalKeywords field |

---

## 2. SERPFeatureHistoryRepository Class Tests

### 2.1 Class Structure Tests

| ID | Test Scenario | Expected Result |
|----|---------------|-----------------|
| 2.1.1 | Class should be instantiable | Instance created successfully |
| 2.1.2 | Class should accept database connection | Connection stored |
| 2.1.3 | Class should expose all required methods | All methods defined |
| 2.1.4 | Class should be singleton or export instance | Single instance pattern |

### 2.2 insertHistory(entry: FeatureHistoryEntry) Method Tests

| ID | Test Scenario | Input | Expected Result |
|----|---------------|-------|-----------------|
| 2.2.1 | Insert valid history entry | Valid entry object | Returns inserted record with id |
| 2.2.2 | Insert with "gained" action | action="gained" | Record created |
| 2.2.3 | Insert with "lost" action | action="lost" | Record created |
| 2.2.4 | Insert sets detected_at automatically | Entry without detected_at | detected_at set to current time |
| 2.2.5 | Insert with invalid action | action="invalid" | Throws validation error |
| 2.2.6 | Insert with null entry | null | Throws error |
| 2.2.7 | Insert with missing keyword_id | Entry without keyword_id | Throws validation error |
| 2.2.8 | Insert with missing feature_type | Entry without feature_type | Throws validation error |
| 2.2.9 | Insert with previous_url and current_url | Entry with both URLs | Both stored correctly |
| 2.2.10 | Insert with non-existent keyword_id | keyword_id=999999 | Foreign key constraint error |

### 2.3 getHistoryByKeyword(keywordId: number, limit?: number) Method Tests

| ID | Test Scenario | Input | Expected Result |
|----|---------------|-------|-----------------|
| 2.3.1 | Get history for valid keyword | Existing keyword_id | Returns array of history entries |
| 2.3.2 | Get history with limit | keywordId, limit=5 | Max 5 entries returned |
| 2.3.3 | Get history without limit | keywordId, undefined | Default limit applied (e.g., 50) |
| 2.3.4 | Get history for non-existent keyword | 999999 | Returns empty array |
| 2.3.5 | Get history with null keyword_id | null | Throws error |
| 2.3.6 | Get history returns newest first | Multiple entries | Ordered by detected_at DESC |
| 2.3.7 | Get history with limit 0 | limit=0 | Returns empty array |
| 2.3.8 | Get history with negative limit | limit=-1 | Throws error or returns empty |
| 2.3.9 | Get history includes all fields | Valid keyword | All fields populated |
| 2.3.10 | Get history with large dataset | 100+ entries, limit=10 | Only 10 returned |

### 2.4 getRecentChanges(siteId: number, hours?: number) Method Tests

| ID | Test Scenario | Input | Expected Result |
|----|---------------|-------|-----------------|
| 2.4.1 | Get recent changes for valid site | Existing siteId | Returns array of changes |
| 2.4.2 | Get changes with custom hours | siteId, hours=24 | Changes from last 24 hours |
| 2.4.3 | Get changes with default hours | siteId, undefined | Default 24 hours used |
| 2.4.4 | Get changes for non-existent site | 999999 | Returns empty array |
| 2.4.5 | Get changes with null siteId | null | Throws error |
| 2.4.6 | Get changes with 0 hours | hours=0 | Returns empty array |
| 2.4.7 | Get changes with negative hours | hours=-1 | Throws error or returns empty |
| 2.4.8 | Get changes returns gained and lost | Site with both actions | Both types included |
| 2.4.9 | Get changes ordered by recency | Multiple changes | Ordered by detected_at DESC |
| 2.4.10 | Get changes includes keyword info | Valid site | Returns keyword name via join |
| 2.4.11 | Get changes with large hours value | hours=8760 (1 year) | Returns all changes |
| 2.4.12 | Get changes filters by site correctly | Multiple sites | Only specified site's changes |

---

## 3. Integration Tests

### 3.1 Database Connection Tests

| ID | Test Scenario | Expected Result |
|----|---------------|-----------------|
| 3.1.1 | Repository uses existing db connection | Connection works |
| 3.1.2 | Repository handles connection errors | Throws appropriate error |
| 3.1.3 | Repository handles transaction rollback | Rollback works correctly |

### 3.2 Foreign Key Constraint Tests

| ID | Test Scenario | Expected Result |
|----|---------------|-----------------|
| 3.2.1 | Insert with valid ranking_id FK | Success |
| 3.2.2 | Insert with valid keyword_id FK | Success |
| 3.2.3 | Insert with invalid ranking_id FK | FK constraint error |
| 3.2.4 | Insert with invalid keyword_id FK | FK constraint error |
| 3.2.5 | Cascade delete on keyword deletion | Features deleted |

### 3.3 Unique Constraint Tests

| ID | Test Scenario | Expected Result |
|----|---------------|-----------------|
| 3.3.1 | Insert same ranking_id, same type | Unique constraint error |
| 3.3.2 | Insert same ranking_id, different type | Success |
| 3.3.3 | Update to duplicate type | Unique constraint error |

### 3.4 Index Performance Tests

| ID | Test Scenario | Expected Result |
|----|---------------|-----------------|
| 3.4.1 | findByKeywordId uses index | Query plan shows index |
| 3.4.2 | findByRankingId uses index | Query plan shows index |
| 3.4.3 | findFeaturesByType uses index | Query plan shows index |
| 3.4.4 | getRecentChanges uses index | Query plan shows index |

---

## 4. Edge Cases and Adversarial Tests

### 4.1 Input Validation Edge Cases

| ID | Test Scenario | Input | Expected Result |
|----|---------------|-------|-----------------|
| 4.1.1 | Empty string feature type | "" | Throws validation error |
| 4.1.2 | Whitespace-only URL | "   " | Trims or throws error |
| 4.1.3 | SQL injection attempt | "'; DROP TABLE serp_features;--" | Escaped/stored safely |
| 4.1.4 | Very long URL (2048+ chars) | "https://..." + "a"*2000 | Stored or truncated |
| 4.1.5 | Very long title (500+ chars) | "Title..."*100 | Stored or truncated |
| 4.1.6 | Very long snippet (10000+ chars) | "Snippet..."*1000 | Stored or truncated |
| 4.1.7 | Unicode in URL | "https://例え.jp/パス" | Stored correctly |
| 4.1.8 | Emoji in title | "Best 🏆 SEO Tools" | Stored correctly |
| 4.1.9 | Null bytes in strings | "test\x00value" | Handled safely |
| 4.1.10 | Negative position | position=-1 | Throws error or null |

### 4.2 Concurrency Tests

| ID | Test Scenario | Expected Result |
|----|---------------|-----------------|
| 4.2.1 | Concurrent inserts same ranking/type | One succeeds, one fails |
| 4.2.2 | Concurrent batch inserts | Both complete or one fails |
| 4.2.3 | Read during write | Read returns consistent data |

### 4.3 Data Consistency Tests

| ID | Test Scenario | Expected Result |
|----|---------------|-----------------|
| 4.3.1 | isPresent=0 stored correctly | Retrieved as false |
| 4.3.2 | isPresent=1 stored correctly | Retrieved as true |
| 4.3.3 | Null optional fields preserved | Retrieved as null/undefined |
| 4.3.4 | Date precision preserved | Milliseconds stored correctly |

### 4.4 Error Handling Tests

| ID | Test Scenario | Expected Result |
|----|---------------|-----------------|
| 4.4.1 | Database connection lost | Throws ConnectionError |
| 4.4.2 | Transaction deadlock | Retry or throw DeadlockError |
| 4.4.3 | Disk full | Throws StorageError |
| 4.4.4 | Query timeout | Throws TimeoutError |
| 4.4.5 | Invalid JSON in serp_features column | Handled gracefully |

---

## 5. Performance Tests

### 5.1 Bulk Operations

| ID | Test Scenario | Input | Expected Result |
|----|---------------|-------|-----------------|
| 5.1.1 | Insert 100 features via batch | 100 features | Completes < 1 second |
| 5.1.2 | Insert 1000 features via batch | 1000 features | Completes < 5 seconds |
| 5.1.3 | Find by keyword with 100 features | Large dataset | Completes < 100ms |
| 5.1.4 | Get summary with 1000 keywords | Large dataset | Completes < 500ms |

---

## Test Count Summary

| Category | Unit Tests | Integration Tests | Total |
|----------|------------|-------------------|-------|
| SERPFeatureRepository | 58 | 15 | 73 |
| SERPFeatureHistoryRepository | 32 | 8 | 40 |
| Edge Cases | 20 | 10 | 30 |
| Performance | 4 | 0 | 4 |
| **Total** | **114** | **33** | **147** |

---

## Developer Notes

1. **Database Connection Pattern**: Follow existing `reportRepo.js` pattern with singleton export
2. **TypeScript Types**: Use types from TASK-7-2 (SERPFeature, SERPFeatureType, etc.)
3. **Error Handling**: Throw descriptive errors with context for debugging
4. **Transaction Support**: Use database transactions for batch operations
5. **JSON Serialization**: Handle serp_features JSON column with proper parsing
6. **Caching**: Consider adding optional caching layer for frequently accessed data
7. **Logging**: Add debug logging for development, remove in production

---

## Test Execution Commands

```bash
# Run all SERP feature repository tests
npm test -- tests/unit/serp-feature-repo.test.js
npm test -- tests/integration/serp-feature-repo.integration.test.js

# Run with coverage
npm run test:coverage -- tests/unit/serp-feature-repo.test.js

# Run specific test
npm test -- tests/unit/serp-feature-repo.test.js -t "insert method"
```

---

Created: 2026-02-26 by PreTester
Task: TASK-7-3
