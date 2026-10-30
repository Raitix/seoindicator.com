# TESTS/SPEC-7-9.md — Unit Tests for Feature History Repository

## Task Reference
- Task: [TASK-7-9](../TASKS/TASK-7-9.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: Medium

---

## Overview

Comprehensive unit tests for the `SERPFeatureHistoryRepository` class. This repository provides data access methods for feature history operations including timelines, gains/losses, statistics, and volatility analysis. Tests verify query correctness, result typing, pagination, and performance with large datasets.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/unit/repositories/serp-feature-history-repo.test.js` | History repository unit tests |

---

## Test Scenarios

---

### 1. getFeatureTimeline Method

#### Basic Functionality

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TL-001 | Returns daily feature presence | keywordId: 1, days: 7 | Array of daily feature objects |
| TL-002 | Returns empty for no history | keywordId: 999 (non-existent) | Empty array |
| TL-003 | Respects days parameter | days: 3 | Only last 3 days returned |
| TL-004 | Ordered by date DESC | Multiple days | Newest first |
| TL-005 | Includes all feature types | History with all features | All 10 types in result |

#### Return Structure

| ID | Scenario | Expected Fields |
|----|----------|-----------------|
| TL-006 | Each entry has date | `date: 'YYYY-MM-DD'` |
| TL-007 | Each entry has features | `features: { featured_snippet: true, ... }` |
| TL-008 | Boolean values | `is_present` as boolean (not 0/1) |

#### Edge Cases

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| TL-009 | Zero days | days: 0 | Returns empty or throws with clear error |
| TL-010 | Negative days | days: -1 | Throws validation error |
| TL-011 | Very large days | days: 3650 | Handles without performance issue |
| TL-012 | Keyword with no checks | keywordId: 2 (no history) | Returns empty array |

---

### 2. getFeatureGainsBySite Method

#### Basic Functionality

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FG-001 | Returns gains in period | siteId: 1, days: 7 | Array of gain objects |
| FG-002 | Returns empty for no gains | siteId: 999 (no gains) | Empty array |
| FG-003 | Respects days parameter | days: 3 | Only last 3 days |
| FG-004 | Ordered by detected_at DESC | Multiple gains | Most recent first |

#### Return Structure

| ID | Scenario | Expected Fields |
|----|----------|-----------------|
| FG-005 | Each gain has keyword_id | `keyword_id: number` |
| FG-006 | Each gain has keyword text | `keyword: string` |
| FG-007 | Each gain has feature_type | `feature_type: SERPFeatureType` |
| FG-008 | Each gain has current_url | `current_url: string` |
| FG-009 | Each gain has detected_at | `detected_at: Date` |

#### Gain Detection Logic

| ID | Scenario | Data Setup | Expected Output |
|----|----------|------------|-----------------|
| FG-010 | Only 'gained' actions included | Mix of gained/lost | Only gained returned |
| FG-011 | Multiple gains for same keyword | 2 features gained | 2 entries returned |
| FG-012 | Same feature gained multiple times | Gained, lost, gained again | Both gains returned |

---

### 3. getFeatureLossesBySite Method

#### Basic Functionality

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FL-001 | Returns losses in period | siteId: 1, days: 7 | Array of loss objects |
| FL-002 | Returns empty for no losses | siteId: 999 (no losses) | Empty array |
| FL-003 | Respects days parameter | days: 3 | Only last 3 days |
| FL-004 | Ordered by detected_at DESC | Multiple losses | Most recent first |

#### Return Structure

| ID | Scenario | Expected Fields |
|----|----------|-----------------|
| FL-005 | Each loss has keyword_id | `keyword_id: number` |
| FL-006 | Each loss has keyword text | `keyword: string` |
| FL-007 | Each loss has feature_type | `feature_type: SERPFeatureType` |
| FL-008 | Each loss has previous_url | `previous_url: string` |
| FL-009 | Each loss has detected_at | `detected_at: Date` |

#### Loss Detection Logic

| ID | Scenario | Data Setup | Expected Output |
|----|----------|------------|-----------------|
| FL-010 | Only 'lost' actions included | Mix of gained/lost | Only lost returned |
| FL-011 | Multiple losses for same keyword | 2 features lost | 2 entries returned |

---

### 4. getFeatureStatistics Method

#### Basic Functionality

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FS-001 | Returns aggregated statistics | siteId: 1 | Statistics object |
| FS-002 | Returns zeros for no data | siteId: 999 (no data) | All zeros |

#### Return Structure

| ID | Scenario | Expected Fields |
|----|----------|-----------------|
| FS-003 | Total keywords with features | `totalKeywordsWithFeatures: number` |
| FS-004 | Total features count | `totalFeatures: number` |
| FS-005 | Features by type | `featuresByType: { featured_snippet: 5, ... }` |
| FS-006 | Gains this period | `gainsThisWeek: number` |
| FS-007 | Losses this period | `lossesThisWeek: number` |

#### Aggregation Logic

| ID | Scenario | Data Setup | Expected Output |
|----|----------|------------|-----------------|
| FS-008 | Count distinct keywords | Same keyword multiple features | Correct unique count |
| FS-009 | Sum all feature types | 5 snippets, 3 local packs | `{ featured_snippet: 5, local_pack: 3 }` |
| FS-010 | Calculate gains/losses ratio | 10 gains, 5 losses | Both values present |

---

### 5. getLongestHeldFeatures Method

#### Basic Functionality

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LF-001 | Returns features held longest | siteId: 1, limit: 10 | Array of feature objects |
| LF-002 | Respects limit parameter | limit: 5 | Max 5 results |
| LF-003 | Returns empty for no features | siteId: 999 (no features) | Empty array |

#### Return Structure

| ID | Scenario | Expected Fields |
|----|----------|-----------------|
| LF-004 | Each entry has keyword_id | `keyword_id: number` |
| LF-005 | Each entry has keyword text | `keyword: string` |
| LF-006 | Each entry has feature_type | `feature_type: SERPFeatureType` |
| LF-007 | Each entry has held_since | `held_since: Date` |
| LF-008 | Each entry has days_held | `days_held: number` |

#### Duration Calculation

| ID | Scenario | Data Setup | Expected Output |
|----|----------|------------|-----------------|
| LF-009 | Ordered by days_held DESC | Multiple features | Longest held first |
| LF-010 | Correctly calculates duration | Gained 30 days ago, still present | `days_held: 30` |
| LF-011 | Excludes lost features | Feature was lost | Not included in results |
| LF-012 | Handles leap years | Feature gained Feb 28, checked Mar 1 | Correct day count |

---

### 6. getMostVolatileKeywords Method

#### Basic Functionality

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MV-001 | Returns keywords with most changes | siteId: 1, limit: 10 | Array of keyword objects |
| MV-002 | Respects limit parameter | limit: 5 | Max 5 results |
| MV-003 | Returns empty for no changes | siteId: 999 (no changes) | Empty array |

#### Return Structure

| ID | Scenario | Expected Fields |
|----|----------|-----------------|
| MV-004 | Each entry has keyword_id | `keyword_id: number` |
| MV-005 | Each entry has keyword text | `keyword: string` |
| MV-006 | Each entry has change_count | `change_count: number` |
| MV-007 | Each entry has gains | `gains: number` |
| MV-008 | Each entry has losses | `losses: number` |

#### Volatility Calculation

| ID | Scenario | Data Setup | Expected Output |
|----|----------|------------|-----------------|
| MV-009 | Ordered by change_count DESC | Multiple keywords | Most changes first |
| MV-010 | Counts both gains and losses | 5 gains, 3 losses | `change_count: 8` |
| MV-011 | Excludes keywords with no changes | Keywords with stable features | Not included |

---

### 7. Pagination Support

#### List Method Pagination

| ID | Scenario | Method | Input | Expected Behavior |
|----|----------|--------|-------|-------------------|
| P-001 | Default limit | getFeatureGainsBySite | No limit param | Uses default (50?) |
| P-002 | Custom limit | getFeatureGainsBySite | limit: 10 | Returns max 10 |
| P-003 | Offset support | getFeatureGainsBySite | offset: 20 | Skips first 20 |
| P-004 | Returns total count | getFeatureGainsBySite | pagination: true | Includes total count |

#### Pagination Response Structure

| ID | Scenario | Expected Fields |
|----|----------|-----------------|
| P-005 | Has data array | `data: []` |
| P-006 | Has pagination metadata | `pagination: { total, page, limit }` |
| P-007 | Has has_more flag | `hasMore: boolean` |

---

### 8. Query Optimization

#### Index Usage

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| Q-001 | Uses index on keyword_id | EXPLAIN shows index usage |
| Q-002 | Uses index on detected_at | EXPLAIN shows index usage |
| Q-003 | Uses index on feature_type | EXPLAIN shows index usage |
| Q-004 | Uses composite index | EXPLAIN shows composite index |

#### Performance Tests

| ID | Scenario | Data Volume | Expected Performance |
|----|----------|-------------|----------------------|
| Q-005 | Small dataset | < 1000 records | < 10ms |
| Q-006 | Medium dataset | < 10000 records | < 50ms |
| Q-007 | Large dataset | < 100000 records | < 200ms |
| Q-008 | Very large dataset | < 1000000 records | < 1000ms |

---

### 9. Type Safety

#### Return Type Validation

| ID | Scenario | Method | Expected Types |
|----|----------|--------|----------------|
| T-001 | Timeline returns array | getFeatureTimeline | `Array<{ date: string, features: object }>` |
| T-002 | Gains returns array | getFeatureGainsBySite | `Array<FeatureGain>` |
| T-003 | Losses returns array | getFeatureLossesBySite | `Array<FeatureLoss>` |
| T-004 | Statistics returns object | getFeatureStatistics | `FeatureStatistics` |
| T-005 | Longest held returns array | getLongestHeldFeatures | `Array<HeldFeature>` |
| T-006 | Volatile returns array | getMostVolatileKeywords | `Array<VolatileKeyword>` |

---

### 10. Error Handling

#### Database Errors

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| E-001 | Database connection failed | DB unavailable | Throws ConnectionError |
| E-002 | Query timeout | Slow query | Throws TimeoutError |
| E-003 | Invalid SQL generated | Edge case | Throws QueryError |

#### Input Validation

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| E-004 | Invalid siteId type | siteId: 'abc' | Throws ValidationError |
| E-005 | Invalid keywordId type | keywordId: null | Throws ValidationError |
| E-006 | Invalid days type | days: 'week' | Throws ValidationError |
| E-007 | Invalid limit type | limit: -1 | Throws ValidationError |
| E-008 | SQL injection attempt | siteId: "1; DROP TABLE" | Sanitized, no injection |

---

## Mock Data

### Feature History Records
```javascript
const mockHistory = [
  {
    id: 1,
    keyword_id: 1,
    feature_type: 'featured_snippet',
    action: 'gained',
    current_url: 'https://ours.com/page',
    detected_at: new Date('2026-02-20T10:00:00Z')
  },
  {
    id: 2,
    keyword_id: 1,
    feature_type: 'local_pack',
    action: 'lost',
    previous_url: 'https://ours.com/location',
    detected_at: new Date('2026-02-19T15:00:00Z')
  },
  {
    id: 3,
    keyword_id: 2,
    feature_type: 'featured_snippet',
    action: 'gained',
    current_url: 'https://ours.com/other',
    detected_at: new Date('2026-02-18T09:00:00Z')
  }
];
```

### Expected Timeline Output
```javascript
[
  {
    date: '2026-02-20',
    features: {
      featured_snippet: true,
      local_pack: false,
      sitelinks: true,
      reviews: false
    }
  },
  {
    date: '2026-02-19',
    features: {
      featured_snippet: true,
      local_pack: true,
      sitelinks: true,
      reviews: false
    }
  }
];
```

### Expected Statistics Output
```javascript
{
  totalKeywordsWithFeatures: 25,
  totalFeatures: 45,
  featuresByType: {
    featured_snippet: 5,
    local_pack: 3,
    image_pack: 2,
    video: 1,
    people_also_ask: 8,
    knowledge_panel: 0,
    sitelinks: 15,
    reviews: 7,
    faq: 3,
    product: 1
  },
  gainsThisWeek: 12,
  lossesThisWeek: 5
};
```

---

## Coverage Targets

| Method | Target Coverage |
|--------|-----------------|
| getFeatureTimeline | 100% |
| getFeatureGainsBySite | 100% |
| getFeatureLossesBySite | 100% |
| getFeatureStatistics | 100% |
| getLongestHeldFeatures | 100% |
| getMostVolatileKeywords | 100% |
| Pagination logic | 100% |
| Error handling | 100% |

---

## Test Execution

```bash
# Run feature history repository tests
npm test -- tests/unit/repositories/serp-feature-history-repo.test.js

# Run with coverage
npm run test:coverage -- --filter=serp-feature-history

# Run all repository tests
npm test -- tests/unit/repositories/
```

---

## Dependencies

- TASK-7-3: SERP Feature Repository (base repository)
- TASK-7-1: Database schema (serp_feature_history table)
- `backend/src/repositories/serp-feature-history-repo.ts` - Implementation
- `backend/src/models/serp-feature.ts` - Type definitions

---

## Notes for Developer

### Repository Class Structure
```typescript
class SERPFeatureHistoryRepository {
  constructor(private db: Database) {}
  
  async getFeatureTimeline(keywordId: number, days: number): Promise<FeatureTimelineEntry[]> {
    // Validate inputs
    if (days <= 0) throw new ValidationError('days must be positive');
    
    // Query with proper date range
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - days);
    
    return this.db.query(`
      SELECT date(checked_at) as date, feature_type, is_present
      FROM serp_features
      WHERE keyword_id = ? AND checked_at >= ?
      ORDER BY date DESC
    `, [keywordId, startDate]);
  }
  
  async getFeatureGainsBySite(siteId: number, days: number, options?: PaginationOptions): Promise<PaginatedResult<FeatureGain>> {
    // Implementation
  }
  
  // ... other methods
}
```

### Performance Considerations

1. **Indexes:** Ensure these indexes exist:
   - `idx_feature_history_keyword` on (keyword_id)
   - `idx_feature_history_detected` on (detected_at)
   - `idx_feature_history_type` on (feature_type)
   - `idx_feature_history_site_date` on (site_id, detected_at)

2. **Query Optimization:**
   - Use `LIMIT` for pagination
   - Use prepared statements
   - Avoid `SELECT *` - specify columns
   - Consider caching for statistics

3. **Large Dataset Handling:**
   - Implement cursor-based pagination for very large datasets
   - Consider materialized views for statistics
   - Add query timeout protection

---

## Comments

### [2026-02-26 18:35] Agent: PreTester
Created test documentation for TASK-7-9 Feature History Repository.

Test coverage includes:
- getFeatureTimeline: 12 scenarios
- getFeatureGainsBySite: 12 scenarios
- getFeatureLossesBySite: 11 scenarios
- getFeatureStatistics: 10 scenarios
- getLongestHeldFeatures: 12 scenarios
- getMostVolatileKeywords: 11 scenarios
- Pagination support: 7 scenarios
- Query optimization: 8 scenarios
- Type safety: 6 scenarios
- Error handling: 8 scenarios

Total: 97 test scenarios covering all acceptance criteria.

Key implementation notes:
1. All methods need input validation
2. Implement pagination with default limit
3. Ensure proper index usage for performance
4. Handle large datasets with pagination
5. Return properly typed results

Ready for Developer implementation.
