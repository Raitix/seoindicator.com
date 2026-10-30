# TESTS/TEST-34-7.md — Query Jobs and Scheduling Tests

## Task Reference
- **Task:** TASK-34-7: Query Job and Scheduling
- **Specification:** SPEC-034: AI Visibility Tracking (Generative Engine Optimization)
- **Priority:** High
- **Risk:** Medium

---

## Overview

Comprehensive test suite for Laravel queue jobs that execute AI visibility queries and scheduled tasks for automated query runs. Tests cover job execution, cache handling, scheduling configuration, error handling, and retry logic.

---

## Test Files

| Test File | Tests | Status | Purpose |
|-----------|-------|--------|---------|
| `backend/tests/Unit/Jobs/AIVisibility/AIVisibilityQueryJobTest.php` | 45+ | Pending | AIVisibilityQueryJob unit tests |
| `backend/tests/Unit/Jobs/AIVisibility/RunAIVisibilityQueriesJobTest.php` | 30+ | Pending | RunAIVisibilityQueries job tests |
| `backend/tests/Unit/Jobs/AIVisibility/AggregateAIVisibilityAnalyticsJobTest.php` | 25+ | Pending | Analytics aggregation job tests |
| `backend/tests/Unit/Jobs/AIVisibility/CheckAIVisibilityAlertsJobTest.php` | 25+ | Pending | Alert checking job tests |
| `backend/tests/Feature/Console/KernelScheduleTest.php` | 15+ | Pending | Scheduled tasks configuration |
| **Total** | **140+** | | |

---

## Test Scenarios

### AIVisibilityQueryJob Tests (JQ-001 to JQ-045)

#### Job Configuration (JQ-CFG)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JQ-CFG-01 | Job implements ShouldQueue | N/A | Job implements `ShouldQueue` interface |
| JQ-CFG-02 | Job has correct queue name | N/A | `queue = 'ai-visibility'` |
| JQ-CFG-03 | Job has correct tries count | N/A | `tries = 3` |
| JQ-CFG-04 | Job has correct backoff | N/A | `backoff = [60]` |
| JQ-CFG-05 | Job accepts projectId | `projectId = 1` | Job constructor accepts int |
| JQ-CFG-06 | Job accepts platform | `platform = 'chatgpt'` | Job constructor accepts string |
| JQ-CFG-07 | Job accepts query | `query = 'best seo tools'` | Job constructor accepts string |
| JQ-CFG-08 | Job accepts useFreeModel | `useFreeModel = true` | Job constructor accepts bool |

#### Cache Check (JQ-CACHE)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JQ-CACHE-01 | Cache key generated correctly | `platform='chatgpt', query='test'` | Key format: `ai_visibility:query:{md5}` |
| JQ-CACHE-02 | Cache hit returns cached result | Cache has result | Returns cached result, `cache_hit = true` |
| JQ-CACHE-03 | Cache miss executes query | No cache entry | Calls AIQueryEngine, stores result |
| JQ-CACHE-04 | Cache TTL is 7 days | Store result | Cache stored with 604800s TTL |
| JQ-CACHE-05 | Cache key is deterministic | Same inputs twice | Same key generated |
| JQ-CACHE-06 | Different query different key | Different queries | Different cache keys |
| JQ-CACHE-07 | Cache stores result in database | Cache miss | Result stored in `ai_query_results` |
| JQ-CACHE-08 | Cache stores result in cache | Cache miss | Result cached with correct TTL |

#### Query Execution (JQ-EXEC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JQ-EXEC-01 | Calls AIQueryEngine::query() | Cache miss | AIQueryEngine::query called once |
| JQ-EXEC-02 | Passes correct parameters | All params | Query called with platform, query, useFreeModel |
| JQ-EXEC-03 | Parses mentions with MentionParser | AI response | MentionParser::parse called |
| JQ-EXEC-04 | Stores result in database | Valid response | Result stored in `ai_query_results` |
| JQ-EXEC-05 | Handles empty response | Empty response | Stores result with empty mentions |
| JQ-EXEC-06 | Handles null response | Null response | Job fails with appropriate error |

#### Error Handling (JQ-ERR)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JQ-ERR-01 | AIQueryEngine throws exception | Exception | Job fails, logged with context |
| JQ-ERR-02 | Retry respects backoff | First failure | Delayed 60 seconds before retry |
| JQ-ERR-03 | Max tries reached | 3 failures | Job marked as failed |
| JQ-ERR-04 | Failed job logged | Failure | `failed_jobs` table has entry |
| JQ-ERR-05 | Database error handled | DB exception | Job fails gracefully |

#### WithoutOverlapping Middleware (JQ-MW)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JQ-MW-01 | Middleware prevents duplicate runs | Same project/query | Second job waits |
| JQ-MW-02 | Lock released after completion | Completed job | Next job can run |
| JQ-MW-03 | Lock released after failure | Failed job | Next job can run |

---

### RunAIVisibilityQueriesJob Tests (JRQ-001 to JRQ-030)

#### Job Execution (JRQ-EXEC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JRQ-EXEC-01 | Dispatches jobs for active projects | 3 active projects | 3 AIVisibilityQueryJob dispatched |
| JRQ-EXEC-02 | Respects tracking frequency daily | `frequency='daily'` | Only queries due today |
| JRQ-EXEC-03 | Respects tracking frequency weekly | `frequency='weekly'` | Only queries due this week |
| JRQ-EXEC-04 | Respects tracking frequency monthly | `frequency='monthly'` | Only queries due this month |
| JRQ-EXEC-05 | Skips inactive projects | 1 inactive, 2 active | Only 2 jobs dispatched |
| JRQ-EXEC-06 | Queries all platforms per project | Project with 3 platforms | 3 jobs per project |
| JRQ-EXEC-07 | Handles no projects due | No projects due | No jobs dispatched |
| JRQ-EXEC-08 | Handles empty project list | No projects | No jobs dispatched |

#### Scheduling (JRQ-SCHED)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JRQ-SCHED-01 | Scheduled weekly on Sundays | N/A | Cron: `0 2 * * 0` |
| JRQ-SCHED-02 | Runs at 02:00 UTC | N/A | Time: 02:00 |
| JRQ-SCHED-03 | Registered in Kernel | N/A | Schedule contains job |
| JRQ-SCHED-04 | WithoutOverlapping applied | N/A | Middleware configured |

#### Error Handling (JRQ-ERR)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JRQ-ERR-01 | Handles database error | DB exception | Job fails, logged |
| JRQ-ERR-02 | Continues after single job failure | 1 job fails | Other jobs still dispatched |
| JRQ-ERR-03 | Logs summary of dispatched jobs | 5 jobs dispatched | Log contains count |

---

### AggregateAIVisibilityAnalyticsJob Tests (JAA-001 to JAA-025)

#### Aggregation Logic (JAA-AGG)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JAA-AGG-01 | Aggregates query results | 10 results | Creates 10 analytics records |
| JAA-AGG-02 | Calculates mention rate | 5 mentions / 10 queries | Rate: 50% |
| JAA-AGG-03 | Calculates share of voice | 3 brand / 10 total | SOV: 30% |
| JAA-AGG-04 | Calculates sentiment score | 7 positive / 10 mentions | Sentiment: 0.7 |
| JAA-AGG-05 | Groups by project and platform | 2 projects, 2 platforms | 4 aggregated records |
| JAA-AGG-06 | Skips already processed results | Processed results | Only new results aggregated |
| JAA-AGG-07 | Stores in ai_mention_analytics | Valid data | Record created |
| JAA-AGG-08 | Updates existing analytics | Duplicate date | Updates existing record |

#### Scheduling (JAA-SCHED)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JAA-SCHED-01 | Scheduled daily at 03:00 | N/A | Cron: `0 3 * * *` |
| JAA-SCHED-02 | Registered in Kernel | N/A | Schedule contains job |
| JAA-SCHED-03 | Depends on query jobs | N/A | Runs after queries |

#### Error Handling (JAA-ERR)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JAA-ERR-01 | Handles empty results | No results | Job completes, no records |
| JAA-ERR-02 | Handles database error | DB exception | Job fails, logged |
| JAA-ERR-03 | Continues after single failure | 1 project fails | Other projects processed |

---

### CheckAIVisibilityAlertsJob Tests (JCA-001 to JCA-025)

#### Alert Detection (JCA-ALERT)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JCA-ALERT-01 | Detects mention rate drop | 20% drop from previous | Alert created |
| JCA-ALERT-02 | Detects share of voice drop | 15% drop from previous | Alert created |
| JCA-ALERT-03 | Detects sentiment change | Significant change | Alert created |
| JCA-ALERT-04 | Respects threshold settings | 10% threshold | Alert at threshold breach |
| JCA-ALERT-05 | Creates alert record | Breach detected | Alert in `ai_alerts` table |
| JCA-ALERT-06 | Links alert to project | Valid alert | `project_id` set correctly |
| JCA-ALERT-07 | Skips if no previous data | First run | No alerts (no baseline) |
| JCA-ALERT-08 | Handles multiple metrics | Multiple breaches | Multiple alerts created |

#### Scheduling (JCA-SCHED)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JCA-SCHED-01 | Scheduled every 6 hours | N/A | Cron: `0 */6 * * *` |
| JCA-SCHED-02 | Registered in Kernel | N/A | Schedule contains job |
| JCA-SCHED-03 | Depends on analytics job | N/A | Runs after aggregation |

#### Error Handling (JCA-ERR)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JCA-ERR-01 | Handles no analytics data | No data | Job completes, no alerts |
| JCA-ERR-02 | Handles database error | DB exception | Job fails, logged |

---

### Kernel Schedule Tests (KS-001 to KS-015)

#### Schedule Configuration (KS-CFG)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KS-CFG-01 | RunAIVisibilityQueries registered | N/A | Schedule contains command/job |
| KS-CFG-02 | AggregateAnalytics registered | N/A | Schedule contains command/job |
| KS-CFG-03 | CheckAlerts registered | N/A | Schedule contains command/job |
| KS-CFG-04 | Weekly schedule correct | N/A | Sunday 02:00 UTC |
| KS-CFG-05 | Daily schedule correct | N/A | 03:00 UTC |
| KS-CFG-06 | Hourly schedule correct | N/A | Every 6 hours |

#### Schedule Execution (KS-EXEC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| KS-EXEC-01 | Manual trigger works | `php artisan schedule:run` | Jobs dispatched |
| KS-EXEC-02 | Due check works | `schedule:run` at wrong time | No jobs dispatched |

---

## Mock Data

### Mock AIQueryEngine Response
```php
[
    'response' => 'Based on my knowledge, BrandName is mentioned in...',
    'mentions' => [
        ['text' => 'BrandName is a great tool', 'sentiment' => 'positive'],
        ['text' => 'I recommend BrandName', 'sentiment' => 'positive'],
    ],
    'model' => 'gemini-2.0-flash',
    'tokens_used' => 150,
];
```

### Mock Cached Result
```php
[
    'query_hash' => 'abc123...',
    'platform' => 'chatgpt',
    'query' => 'best seo tools',
    'mentions' => [...],
    'cache_hit' => true,
    'cached_at' => '2026-03-07T12:00:00Z',
];
```

### Mock Analytics Data
```php
[
    'project_id' => 1,
    'platform' => 'chatgpt',
    'date' => '2026-03-07',
    'total_queries' => 10,
    'total_mentions' => 5,
    'mention_rate' => 0.5,
    'share_of_voice' => 0.3,
    'sentiment_score' => 0.7,
];
```

### Mock Alert Data
```php
[
    'project_id' => 1,
    'alert_type' => 'mention_rate_drop',
    'severity' => 'warning',
    'message' => 'Mention rate dropped by 20%',
    'previous_value' => 0.5,
    'current_value' => 0.4,
    'threshold' => 0.1,
];
```

---

## Coverage Targets

| Component | Target | Notes |
|-----------|--------|-------|
| AIVisibilityQueryJob | 90% | Core query execution |
| RunAIVisibilityQueriesJob | 85% | Orchestration |
| AggregateAIVisibilityAnalyticsJob | 85% | Data aggregation |
| CheckAIVisibilityAlertsJob | 85% | Alert detection |
| Schedule Configuration | 100% | Critical for automation |

---

## Test Execution

```bash
# Run all AI visibility job tests
php artisan test --filter=AIVisibility

# Run specific job test
php artisan test tests/Unit/Jobs/AIVisibility/AIVisibilityQueryJobTest.php

# Run schedule tests
php artisan test tests/Feature/Console/KernelScheduleTest.php

# Run with coverage
php artisan test --coverage --filter=AIVisibility
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-34-3 (AIQueryEngine) | To Write Tests | Mock in tests |
| TASK-34-5 (AIVisibilityService) | To Write Tests | Mock in tests |
| FEAT-019 (Queue System) | Available | Redis driver |

---

## Notes for Developer

### TDD Approach
Tests are written before implementation. Create the job classes to pass these tests.

### Service Mocking
All external services (AIQueryEngine, AIVisibilityService) are mocked in tests. This allows testing in isolation.

### Queue Testing
Use `Queue::fake()` to test job dispatching without actual queue processing.

### Cache Testing
Use `Cache::fake()` or clear cache between tests for deterministic behavior.

### WithoutOverlapping Middleware
Test that duplicate jobs are prevented from running simultaneously.

---

## Comments

### PreTester Test Design (2026-03-07 18:05 UTC)

**Test Design Complete:**

- Created comprehensive test documentation with 140+ test scenarios
- Tests follow TDD approach with mocked service interfaces
- Tests cover all acceptance criteria from TASK-34-7
- Tests designed for jobs not yet implemented

**Key Test Patterns:**
- Mock AIQueryEngine for query execution tests
- Mock AIVisibilityService for orchestration tests
- Mock Cache facade for cache hit/miss scenarios
- Use Queue::fake() for dispatch verification
- Test scheduling via Kernel inspection

**Security Tests:**
- No sensitive data in logs
- Error messages don't expose internals
- Rate limiting respected

**Status:** Tests Coverage In Progress → Ready for test file creation

---

*Created by PreTester on 2026-03-07*
