# TESTS/SPEC-7-12.md — Tests for Filter by Feature Endpoint

## Task Reference
- Task: [TASK-7-12](../TASKS/TASK-7-12.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: Medium

---

## Overview

Tests for extending the keywords listing endpoint to support filtering by SERP feature presence.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/api/keywords-filter-feature.test.js` | API filter tests |

---

## Test Scenarios

### 1. Single Feature Filter

| ID | Scenario | Request | Expected Response |
|----|----------|---------|-------------------|
| SF-001 | Filter by featured_snippet | ?serpFeature=featured_snippet | Keywords with snippet |
| SF-002 | Filter by local_pack | ?serpFeature=local_pack | Keywords in local pack |
| SF-003 | Filter by sitelinks | ?serpFeature=sitelinks | Keywords with sitelinks |
| SF-004 | No matching keywords | ?serpFeature=knowledge_panel (none) | Empty array |

### 2. Present Parameter

| ID | Scenario | Request | Expected Response |
|----|----------|---------|-------------------|
| PP-001 | present=true (default) | ?serpFeature=featured_snippet | Keywords WITH snippet |
| PP-002 | present=true explicit | ?serpFeature=featured_snippet&present=true | Keywords WITH snippet |
| PP-003 | present=false | ?serpFeature=featured_snippet&present=false | Keywords WITHOUT snippet |
| PP-004 | present=1 | ?serpFeature=featured_snippet&present=1 | Keywords WITH snippet |
| PP-005 | present=0 | ?serpFeature=featured_snippet&present=0 | Keywords WITHOUT snippet |

### 3. Multiple Feature Filter

| ID | Scenario | Request | Expected Response |
|----|----------|---------|-------------------|
| MF-001 | Two features (OR) | ?serpFeature=featured_snippet,local_pack | Keywords with either |
| MF-002 | Three features | ?serpFeature=featured_snippet,local_pack,sitelinks | Keywords with any |
| MF-003 | Multiple with present=false | ?serpFeature=featured_snippet,local_pack&present=false | Keywords without either |

### 4. Combined Filters

| ID | Scenario | Request | Expected Response |
|----|----------|---------|-------------------|
| CF-001 | With position filter | ?serpFeature=featured_snippet&position=1 | Snippet + position 1 |
| CF-002 | With search engine | ?serpFeature=local_pack&searchEngine=google | Local pack + Google |
| CF-003 | With status | ?serpFeature=sitelinks&status=active | Sitelinks + active |
| CF-004 | With pagination | ?serpFeature=featured_snippet&page=2&limit=20 | Paged results |

### 5. Response Structure

| ID | Scenario | Expected Fields |
|----|----------|-----------------|
| RS-001 | Each keyword has serpFeatures | `keyword.serpFeatures: object` |
| RS-002 | Features object has boolean values | `{ featured_snippet: true, ... }` |
| RS-003 | Standard pagination metadata | `{ data, pagination }` |

### 6. Performance

| ID | Scenario | Data Volume | Expected Time |
|----|----------|-------------|---------------|
| PF-001 | Filter uses index | EXPLAIN shows index | Index scan |
| PF-002 | No full table scan | Large dataset | < 100ms |
| PF-003 | Efficient join | Multiple tables | Optimized query |

### 7. Error Handling

| ID | Scenario | Input | Expected Response |
|----|----------|-------|-------------------|
| EH-001 | Invalid feature type | ?serpFeature=invalid_type | 400 Bad Request |
| EH-002 | Empty feature param | ?serpFeature= | 400 or ignored |
| EH-003 | SQL injection attempt | ?serpFeature=";DROP TABLE | Sanitized, no injection |

---

## Mock Data

### Request
```
GET /api/v1/sites/1/keywords?serpFeature=featured_snippet&present=true
Authorization: Bearer <token>
```

### Expected Response (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "keyword": "seo tools",
      "position": 1,
      "serpFeatures": {
        "featured_snippet": true,
        "sitelinks": true,
        "local_pack": false
      }
    },
    {
      "id": 5,
      "keyword": "keyword tracker",
      "position": 2,
      "serpFeatures": {
        "featured_snippet": true,
        "sitelinks": false,
        "local_pack": false
      }
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 50,
    "total": 2,
    "hasMore": false
  }
}
```

---

## Coverage Targets

| Component | Target |
|-----------|--------|
| Single feature filter | 100% |
| Present parameter | 100% |
| Multiple features | 100% |
| Combined filters | 100% |
| Performance | Verified |
| Error handling | 100% |

---

## Test Execution

```bash
npm test -- tests/api/keywords-filter-feature.test.js
```

---

## Dependencies

- TASK-7-3 (Repository)
- Existing keywords endpoint

---

## Comments

### [2026-02-26 18:48] Agent: PreTester
Created test documentation for TASK-7-12 Filter by Feature Endpoint.

Test coverage: 24 scenarios covering single/multiple feature filters, present parameter, combined filters, performance, and error handling.

Ready for Developer implementation.
