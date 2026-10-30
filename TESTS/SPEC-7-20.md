# TESTS/SPEC-7-20.md — Integration Tests for SERP Features

## Task Reference
- Task: [TASK-7-20](../TASKS/TASK-7-20.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: Medium

---

## Overview

End-to-end integration tests for SERP feature tracking functionality. Tests verify that all components work together correctly.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/integration/serp-features-api.test.ts` | API integration tests |
| `tests/integration/serp-feature-workflow.test.ts` | Workflow tests |
| `tests/e2e/serp-features.e2e.ts` | End-to-end tests |

---

## Test Scenarios

### 1. API Endpoints Integration

| ID | Scenario | Flow | Expected Result |
|----|----------|------|-----------------|
| API-001 | Get features for keyword | GET /keywords/:id/serp-features | 200 with data |
| API-002 | Get feature summary | GET /sites/:id/serp-features/summary | 200 with summary |
| API-003 | Filter keywords by feature | GET /sites/:id/keywords?serpFeature=... | Filtered results |
| API-004 | Create report with features | POST /reports/generate | Report includes features |

### 2. Ranking Check Workflow

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| WF-001 | Feature detected on check | 1. Run rank check 2. Check features | Features stored |
| WF-002 | Change tracked | 1. Check changes SERP 2. Re-check | Change recorded |
| WF-003 | Alert triggered | 1. Feature gained 2. Alert system | Alert sent |

### 3. Full Feature Workflow

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| FW-001 | New keyword feature tracking | 1. Add keyword 2. First check | Features detected |
| FW-002 | Feature change detection | 1. Check A 2. Change SERP 3. Check B | Change recorded |
| FW-003 | Alert generation | 1. Feature gained 2. Alert queue | Alert delivered |
| FW-004 | Report generation | 1. Create report 2. Include features | PDF has feature section |

### 4. Mock SERP Data Testing

| ID | Scenario | Mock Data | Expected Result |
|----|----------|-----------|-----------------|
| MS-001 | Featured snippet detected | Mock with answer_box | Detected correctly |
| MS-002 | Local pack detected | Mock with local_pack | Detected correctly |
| MS-003 | Multiple features | Mock with multiple | All detected |
| MS-004 | No features | Mock with none | Empty features |

### 5. Database Integration

| ID | Scenario | Operation | Expected Result |
|----|----------|-----------|-----------------|
| DB-001 | Feature data persisted | Insert feature | Data in database |
| DB-002 | Feature history tracked | Insert history | History recorded |
| DB-003 | Feature query performance | Query with indexes | Fast response |
| DB-004 | Data consistency | Concurrent writes | No corruption |

### 6. Alert System Integration

| ID | Scenario | Trigger | Expected Result |
|----|----------|---------|-----------------|
| AL-001 | Gained alert sent | Feature gained | Email sent |
| AL-002 | Lost alert sent | Feature lost | Email sent |
| AL-003 | Alert preferences | User disabled | No alert sent |
| AL-004 | Multiple alerts | Multiple changes | All alerts sent |

### 7. Report Integration

| ID | Scenario | Report Type | Expected Result |
|----|----------|-------------|-----------------|
| RP-001 | PDF includes features | PDF generation | Feature section |
| RP-002 | CSV includes features | CSV export | Feature column |
| RP-003 | Template controls | Disabled in template | No feature section |

### 8. Error Handling

| ID | Scenario | Error Condition | Expected Behavior |
|----|----------|-----------------|-------------------|
| EH-001 | SERP fetch fails | Network error | Graceful failure |
| EH-002 | Detection fails | Malformed SERP | Default features |
| EH-003 | API timeout | Slow response | Timeout handled |
| EH-004 | Database error | Connection lost | Retry or error |

### 9. Performance

| ID | Scenario | Load | Expected Time |
|----|----------|------|---------------|
| PF-001 | API response | 1000 keywords | < 500ms |
| PF-002 | Feature detection | 100 keywords | < 5s |
| PF-003 | Report generation | With features | < 10s |

### 10. CI Integration

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| CI-001 | Tests run in CI | All tests pass |
| CI-002 | Test isolation | No cross-test contamination |
| CI-003 | Cleanup after tests | Database reset |

---

## Test Setup

### Environment
```javascript
// Test database setup
beforeAll(async () => {
  await setupTestDatabase();
  await seedTestData();
});

afterAll(async () => {
  await cleanupTestDatabase();
});
```

### Mock SERP Fetcher
```javascript
const mockSERPFetcher = {
  fetch: jest.fn().mockResolvedValue({
    keyword: 'test',
    results: [...],
    answerBox: { url: 'https://ours.com', snippet: '...' },
    localPack: [{ url: 'https://ours.com' }]
  })
};
```

### Test Data
```javascript
const testSite = { id: 1, url: 'https://ours.com', userId: 1 };
const testKeyword = { id: 1, keyword: 'seo tools', siteId: 1 };
const testFeatures = [
  { type: 'featured_snippet', isPresent: true }
];
```

---

## Coverage Targets

| Area | Target |
|------|--------|
| API endpoints | 100% |
| Workflow integration | 100% |
| Alert integration | 100% |
| Report integration | 100% |
| Error handling | 100% |

---

## Test Execution

```bash
# Run all SERP feature integration tests
npm test -- tests/integration/serp-features-*.test.ts

# Run E2E tests
npm run test:e2e -- tests/e2e/serp-features.e2e.ts

# Run with coverage
npm run test:coverage -- --filter=serp-feature
```

---

## Dependencies

- All previous SERP tasks (TASK-7-1 through TASK-7-19)
- Test database
- Mock services

---

## Comments

### [2026-02-26 19:12] Agent: PreTester
Created test documentation for TASK-7-20 Integration Tests.

Test coverage: 34 scenarios covering API integration, workflows, alerts, reports, error handling, performance, and CI integration.

Ready for Developer implementation after all other tasks complete.
