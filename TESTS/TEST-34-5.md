# TEST-34-5: AIVisibilityService Tests

## Task Reference
- **Task:** TASK-34-5
- **Specification:** SPEC-034
- **Feature:** FEAT-034 - AI Visibility Tracking (Generative Engine Optimization)

---

## Overview

Comprehensive test suite for the `AIVisibilityService` that orchestrates project management, query execution, and analytics retrieval for AI visibility tracking.

**Key Responsibilities:**
1. Project CRUD operations (create/update/delete)
2. Query dispatch and tracking
3. Analytics aggregation with caching
4. Competitor comparison generation
5. Alert detection and creation
6. Query limit enforcement by tier

---

## Test Files

| File | Location | Purpose |
|------|----------|---------|
| `AIVisibilityServiceTest.php` | `backend/tests/Unit/Services/AIVisibility/` | Main service unit tests |
| `AIVisibilityServiceInterfaceTest.php` | `backend/tests/Unit/Services/AIVisibility/` | Interface contract tests |

---

## Dependencies (Mocked)

| Dependency | Source | Mock Behavior |
|------------|--------|---------------|
| AIVisibilityProject | TASK-34-2 | Model mock for CRUD |
| AIQueryEngine | TASK-34-3 | Service mock for queries |
| MentionParser | TASK-34-4 | Service mock for parsing |
| AIQueryResult | TASK-34-2 | Model mock for results |
| AIMentionAnalytics | TASK-34-2 | Model mock for analytics |
| AIVisibilityAlert | TASK-34-2 | Model mock for alerts |
| Cache | Laravel | Facade mock |
| Queue | Laravel | Facade mock for job dispatch |

---

## Test Scenarios

### Category 1: Service Instantiation (VS-001 to VS-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-001 | Class exists at correct namespace | Check class | `App\Services\AIVisibility\AIVisibilityService` |
| VS-002 | Implements AIVisibilityServiceInterface | Check interface | Implements contract |
| VS-003 | Constructor accepts dependencies | Inject mocks | Instance created |
| VS-004 | Dependency injection support | App::make() | Resolves correctly |
| VS-005 | AIQueryEngine injected correctly | Check property | Set correctly |
| VS-006 | MentionParser injected correctly | Check property | Set correctly |
| VS-007 | Logger injected correctly | Check property | Set correctly |
| VS-008 | Config loaded for query limits | Check config | Array returned |
| VS-009 | Cache TTL configurable | Get TTL | Default 3600 seconds |
| VS-010 | Service is singleton | Check binding | Singleton in provider |

---

### Category 2: createProject() Method (VS-011 to VS-035)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-011 | Method exists | Check method | Method exists |
| VS-012 | Accepts userId parameter | int $userId | Accepted |
| VS-013 | Accepts data array | array $data | Accepted |
| VS-014 | Returns AIVisibilityProject | Valid data | Model instance |
| VS-015 | Creates project with brand_name | brand_name = "Acme" | Stored correctly |
| VS-016 | Creates project with keywords | keywords = ["acme", "corp"] | Stored as JSON |
| VS-017 | Creates project with competitors | competitors = [{name, keywords}] | Stored as JSON |
| VS-018 | Creates project with query_templates | templates array | Stored correctly |
| VS-019 | Sets default tracking_frequency | Omit frequency | Defaults to 'weekly' |
| VS-020 | Sets default is_active | Omit is_active | Defaults to true |
| VS-021 | Sets user_id from parameter | userId = 1 | user_id = 1 |
| VS-022 | Validates competitor count: 0 | 0 competitors | Valid, stored |
| VS-023 | Validates competitor count: 1 | 1 competitor | Valid, stored |
| VS-024 | Validates competitor count: 2 | 2 competitors | Valid, stored |
| VS-025 | Validates competitor count: 3 | 3 competitors | Valid, stored |
| VS-026 | Validates competitor count: 4 | 4 competitors | throws ValidationException |
| VS-027 | Validates competitor count: 10 | 10 competitors | throws ValidationException |
| VS-028 | Validates keywords format: array | keywords = ["a", "b"] | Valid |
| VS-029 | Validates keywords format: string | keywords = "string" | throws ValidationException |
| VS-030 | Validates keywords format: empty | keywords = [] | Valid (empty array) |
| VS-031 | Validates brand_name required | Omit brand_name | throws ValidationException |
| VS-032 | Logs project creation | Create project | Log entry with userId |
| VS-033 | Returns model with id | After creation | id populated |
| VS-034 | Transaction rollback on error | Force DB error | Transaction rolled back |
| VS-035 | Dispatches project created event | Create project | Event dispatched |

---

### Category 3: updateProject() Method (VS-036 to VS-055)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-036 | Method exists | Check method | Method exists |
| VS-037 | Accepts projectId parameter | int $projectId | Accepted |
| VS-038 | Accepts data array | array $data | Accepted |
| VS-039 | Returns updated model | Valid update | Model instance |
| VS-040 | Updates brand_name | new brand_name | Updated correctly |
| VS-041 | Updates keywords | new keywords array | Updated correctly |
| VS-042 | Updates competitors | new competitors | Updated correctly |
| VS-043 | Updates tracking_frequency | new frequency | Updated correctly |
| VS-044 | Validates user ownership: owner | Same user | Update succeeds |
| VS-045 | Validates user ownership: not owner | Different user | throws AuthorizationException |
| VS-046 | Validates project exists | Invalid ID | throws ModelNotFoundException |
| VS-047 | Validates competitor count on update | 4 competitors | throws ValidationException |
| VS-048 | Logs project update | Update project | Log entry with projectId |
| VS-049 | Updates updated_at timestamp | Update data | Timestamp updated |
| VS-050 | Partial update: only brand_name | Only brand_name | Other fields unchanged |
| VS-051 | Partial update: only keywords | Only keywords | Other fields unchanged |
| VS-052 | Does not change user_id | Try to change user_id | user_id unchanged |
| VS-053 | Does not change id | Try to change id | id unchanged |
| VS-054 | Transaction rollback on error | Force DB error | Transaction rolled back |
| VS-055 | Dispatches project updated event | Update project | Event dispatched |

---

### Category 4: deleteProject() Method (VS-056 to VS-075)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-056 | Method exists | Check method | Method exists |
| VS-057 | Accepts projectId parameter | int $projectId | Accepted |
| VS-058 | Accepts userId parameter | int $userId | Accepted |
| VS-059 | Returns true on success | Valid delete | true |
| VS-060 | Validates user ownership: owner | Same user | Delete succeeds |
| VS-061 | Validates user ownership: not owner | Different user | throws AuthorizationException |
| VS-062 | Validates project exists | Invalid ID | throws ModelNotFoundException |
| VS-063 | Soft delete when configured | Config soft delete | Soft deleted |
| VS-064 | Hard delete when configured | Config hard delete | Hard deleted |
| VS-065 | Cascades to query results | Delete project | Results deleted |
| VS-066 | Cascades to analytics | Delete project | Analytics deleted |
| VS-067 | Cascades to alerts | Delete project | Alerts deleted |
| VS-068 | Logs project deletion | Delete project | Log entry with projectId |
| VS-069 | Clears related cache | Delete project | Cache cleared |
| VS-070 | Transaction rollback on error | Force DB error | Transaction rolled back |
| VS-071 | Dispatches project deleted event | Delete project | Event dispatched |
| VS-072 | Returns false if already deleted | Delete twice | false |
| VS-073 | Can restore soft-deleted | Restore project | Project restored |
| VS-074 | Query count check before delete | With queries | Returns count |
| VS-075 | Force delete option | Force=true | Hard delete |

---

### Category 5: runQueries() Method (VS-076 to VS-110)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-076 | Method exists | Check method | Method exists |
| VS-077 | Accepts projectId parameter | int $projectId | Accepted |
| VS-078 | Returns array of job IDs | Valid call | Array of strings |
| VS-079 | Dispatches job for chatgpt platform | Call method | Job dispatched for chatgpt |
| VS-080 | Dispatches job for perplexity platform | Call method | Job dispatched for perplexity |
| VS-081 | Dispatches 2 jobs total | Call method | 2 job IDs returned |
| VS-082 | Generates queries from templates | Has templates | Queries generated |
| VS-083 | Includes brand in queries | Has brand_name | Brand in query text |
| VS-084 | Includes competitors in queries | Has competitors | Competitors in query text |
| VS-085 | Updates last_queried_at | Call method | Timestamp updated |
| VS-086 | Validates project exists | Invalid ID | throws ModelNotFoundException |
| VS-087 | Validates project is active | Inactive project | throws Exception |
| VS-088 | Checks query limit: free tier under limit | Free, 3 queries used | Jobs dispatched |
| VS-089 | Checks query limit: free tier at limit | Free, 5 queries used | throws QueryLimitExceededException |
| VS-090 | Checks query limit: pro tier under limit | Pro, 40 queries used | Jobs dispatched |
| VS-091 | Checks query limit: pro tier at limit | Pro, 50 queries used | throws QueryLimitExceededException |
| VS-092 | Checks query limit: business under limit | Business, 150 queries | Jobs dispatched |
| VS-093 | Checks query limit: business at limit | Business, 200 queries | throws QueryLimitExceededException |
| VS-094 | Increments query count | After dispatch | Count incremented |
| VS-095 | Uses Queue facade for dispatch | Call method | Queue::push called |
| VS-096 | Job payload includes projectId | Dispatch job | projectId in payload |
| VS-097 | Job payload includes platform | Dispatch job | platform in payload |
| VS-098 | Job payload includes generated queries | Dispatch job | queries in payload |
| VS-099 | Logs query dispatch | Call method | Log entry |
| VS-100 | Handles no query templates | Empty templates | Uses defaults |
| VS-101 | Handles empty competitors | No competitors | Query without competitors |
| VS-102 | Transaction rollback on error | Force error | Rolled back |
| VS-103 | Does not update last_queried_at on error | Error occurs | Not updated |
| VS-104 | Returns empty array if no platforms | No platforms | Empty array |
| VS-105 | Query limit reset monthly | New month | Count reset |
| VS-106 | Query limit exception message | At limit | "exceeded your monthly limit" |
| VS-107 | Query limit exception includes tier | At limit | Tier in message |
| VS-108 | Query limit exception includes limit | At limit | Limit in message |
| VS-109 | Query limit exception includes used | At limit | Used count in message |
| VS-110 | Creates job tracking record | Dispatch job | Record created |

---

### Category 6: getAnalytics() Method (VS-111 to VS-145)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-111 | Method exists | Check method | Method exists |
| VS-112 | Accepts projectId parameter | int $projectId | Accepted |
| VS-113 | Accepts period parameter | string $period | Accepted |
| VS-114 | Returns array | Valid call | Array returned |
| VS-115 | Returns mention_rate_by_platform | Valid call | Array of platform rates |
| VS-116 | Returns share_of_voice | Valid call | Float value |
| VS-117 | Returns sentiment_breakdown | Valid call | Array positive/neutral/negative |
| VS-118 | Returns trend_direction | Valid call | String up/down/stable |
| VS-119 | Period: day | period="day" | Last 24 hours data |
| VS-120 | Period: week | period="week" | Last 7 days data |
| VS-121 | Period: month | period="month" | Last 30 days data |
| VS-122 | Caches results for 1 hour | Call twice | Cache hit on second |
| VS-123 | Cache key includes projectId | Different projects | Different cache keys |
| VS-124 | Cache key includes period | Different periods | Different cache keys |
| VS-125 | Validates project exists | Invalid ID | throws ModelNotFoundException |
| VS-126 | Validates period value | Invalid period | throws ValidationException |
| VS-127 | Returns zeros for no data | No queries yet | All zeros |
| VS-128 | Aggregates from AIMentionAnalytics | Has data | Aggregated correctly |
| VS-129 | Calculates average sentiment | Multiple results | Average returned |
| VS-130 | Calculates mention rate | 50 queries, 35 mentions | 70% rate |
| VS-131 | Calculates share of voice | Brand + competitors | Correct percentage |
| VS-132 | Determines trend direction | Previous period data | Correct trend |
| VS-133 | Handles single platform | Only chatgpt data | ChatGPT only results |
| VS-134 | Handles multiple platforms | chatgpt + perplexity | Both platforms |
| VS-135 | Bypasses cache when requested | Skip cache | Fresh query |
| VS-136 | Logs analytics retrieval | Call method | Log entry |
| VS-137 | Performance: <500ms | Large dataset | Completes quickly |
| VS-138 | Returns total_queries | Valid call | Integer count |
| VS-139 | Returns brand_mentions | Valid call | Integer count |
| VS-140 | Returns competitor_mentions | Valid call | Array by competitor |
| VS-141 | Cache cleared on new queries | After runQueries | Cache invalidated |
| VS-142 | Handles deleted analytics | Missing records | Graceful fallback |
| VS-143 | Returns period dates | Valid call | start_date, end_date |
| VS-144 | Handles partial data | Some platforms missing | Available data |
| VS-145 | Returns last_updated timestamp | Valid call | ISO timestamp |

---

### Category 7: getCompetitorComparison() Method (VS-146 to VS-175)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-146 | Method exists | Check method | Method exists |
| VS-147 | Accepts projectId parameter | int $projectId | Accepted |
| VS-148 | Returns array | Valid call | Array returned |
| VS-149 | Returns brand data | Valid call | Brand section with mentions |
| VS-150 | Returns competitors array | Valid call | Array of competitor data |
| VS-151 | Each competitor has mention_count | Valid call | Integer count |
| VS-152 | Each competitor has mention_rate | Valid call | Float percentage |
| VS-153 | Each competitor has sentiment | Valid call | Sentiment breakdown |
| VS-154 | Brand has mention_count | Valid call | Integer count |
| VS-155 | Brand has mention_rate | Valid call | Float percentage |
| VS-156 | Brand has sentiment | Valid call | Sentiment breakdown |
| VS-157 | Caches results for 1 hour | Call twice | Cache hit on second |
| VS-158 | Cache key includes projectId | Different projects | Different cache keys |
| VS-159 | Validates project exists | Invalid ID | throws ModelNotFoundException |
| VS-160 | Returns empty competitors if none | No competitors | Empty array |
| VS-161 | Handles 1 competitor | 1 competitor | 1 competitor result |
| VS-162 | Handles 2 competitors | 2 competitors | 2 competitor results |
| VS-163 | Handles 3 competitors | 3 competitors | 3 competitor results |
| VS-164 | Uses AIQueryResult for counts | Has results | Aggregated from results |
| VS-165 | Calculates relative performance | Brand vs competitors | Correct percentages |
| VS-166 | Bypasses cache when requested | Skip cache | Fresh query |
| VS-167 | Logs comparison retrieval | Call method | Log entry |
| VS-168 | Returns competitor names | Valid call | Name in each result |
| VS-169 | Handles no query results | No results | All zeros |
| VS-170 | Cache cleared on new queries | After runQueries | Cache invalidated |
| VS-171 | Handles partial competitor data | Missing for some | Available data |
| VS-172 | Returns winner (highest rate) | Valid call | winner field populated |
| VS-173 | Returns performance gap | Valid call | gap_percentage field |
| VS-174 | Handles tie in rates | Same rates | Both marked as tie |
| VS-175 | Period comparison support | With previous data | Includes change |

---

### Category 8: checkAlerts() Method (VS-176 to VS-210)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-176 | Method exists | Check method | Method exists |
| VS-177 | Accepts projectId parameter | int $projectId | Accepted |
| VS-178 | Returns array of alerts | Valid call | Array returned |
| VS-179 | Detects mention spike: +25% | 25% increase | Alert created |
| VS-180 | Detects mention spike: +20% | Exactly 20% | Alert created |
| VS-181 | Does NOT alert on +19% | 19% increase | No alert |
| VS-182 | Detects mention drop: -25% | 25% decrease | Alert created |
| VS-183 | Detects mention drop: -20% | Exactly 20% | Alert created |
| VS-184 | Does NOT alert on -19% | 19% decrease | No alert |
| VS-185 | Detects competitor gain: +20% | Competitor +20% | Alert created |
| VS-186 | Detects competitor gain: +15% | Exactly 15% | Alert created |
| VS-187 | Does NOT alert on +14% | Competitor +14% | No alert |
| VS-188 | Creates AIVisibilityAlert model | Alert triggered | Model persisted |
| VS-189 | Alert includes alert_type | Created alert | Type populated |
| VS-190 | Alert includes threshold | Created alert | 20.00 or 15.00 |
| VS-191 | Alert includes previous_value | Created alert | Previous populated |
| VS-192 | Alert includes current_value | Created alert | Current populated |
| VS-193 | Alert includes change_percent | Created alert | Percentage populated |
| VS-194 | Validates project exists | Invalid ID | throws ModelNotFoundException |
| VS-195 | Returns empty array if no alerts | No threshold breaches | Empty array |
| VS-196 | Returns multiple alerts | Multiple breaches | Array of alerts |
| VS-197 | Logs alert creation | Alert triggered | Log entry |
| VS-198 | Does not create duplicate alert | Same alert exists | Skipped |
| VS-199 | Checks previous period data | Has history | Comparison made |
| VS-200 | Handles no previous data | First period | No alerts (no baseline) |
| VS-201 | Mention spike alert type | Spike detected | alert_type = "mention_spike" |
| VS-202 | Mention drop alert type | Drop detected | alert_type = "mention_drop" |
| VS-203 | Competitor gain alert type | Gain detected | alert_type = "competitor_gain" |
| VS-204 | Alert triggered_at timestamp | Created alert | Timestamp set |
| VS-205 | Alert is_read defaults to false | Created alert | is_read = false |
| VS-206 | Alert is_resolved defaults to false | Created alert | is_resolved = false |
| VS-207 | Transaction on alert creation | Create alert | Transaction used |
| VS-208 | Alert project_id set correctly | Created alert | project_id matches |
| VS-209 | Returns alert models | Alerts created | Model instances |
| VS-210 | Handles partial data gracefully | Missing some data | Continues checking |

---

### Category 9: Query Limit Enforcement (VS-211 to VS-235)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-211 | Free tier limit: 5 | Free user | Max 5 queries/month |
| VS-212 | Pro tier limit: 50 | Pro user | Max 50 queries/month |
| VS-213 | Business tier limit: 200 | Business user | Max 200 queries/month |
| VS-214 | getQueryLimit() method | Check method | Method exists |
| VS-215 | getQueryLimit() returns int | Call method | Integer returned |
| VS-216 | getQueryUsage() method | Check method | Method exists |
| VS-217 | getQueryUsage() returns int | Call method | Integer returned |
| VS-218 | getRemainingQueries() method | Check method | Method exists |
| VS-219 | getRemainingQueries() returns int | Call method | Integer returned |
| VS-220 | Remaining = limit - usage | 3 used of 5 | 2 remaining |
| VS-221 | Remaining = 0 at limit | 5 used of 5 | 0 remaining |
| VS-222 | Usage counts current month only | Last month + this month | Only this month |
| VS-223 | Usage resets on 1st of month | New month | 0 usage |
| VS-224 | Limit from config | Check config | Configurable values |
| VS-225 | Exception includes limit | At limit | Limit in exception |
| VS-226 | Exception includes usage | At limit | Usage in exception |
| VS-227 | Exception includes remaining | At limit | Remaining in exception |
| VS-228 | Exception includes tier | At limit | Tier in exception |
| VS-229 | canRunQueries() method | Check method | Method exists |
| VS-230 | canRunQueries() returns bool | Under limit | true |
| VS-231 | canRunQueries() returns false | At limit | false |
| VS-232 | Zero queries requested | 0 queries | Returns true |
| VS-233 | Negative queries requested | -1 queries | ValidationException |
| VS-234 | Limits configurable per tenant | Custom config | Custom limits |
| VS-235 | Unlimited tier (null limit) | No limit | Unlimited queries |

---

### Category 10: Integration & Edge Cases (VS-236 to VS-260)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VS-236 | Full flow: create → run → analytics | Complete flow | Works end-to-end |
| VS-237 | Concurrent project creation | Multiple creates | No race condition |
| VS-238 | Concurrent query dispatch | Multiple dispatches | No race condition |
| VS-239 | Large keywords array | 100 keywords | Handled correctly |
| VS-240 | Large competitors array | 3 competitors (max) | Handled correctly |
| VS-241 | Unicode in brand_name | "東京株式会社" | Stored correctly |
| VS-242 | Unicode in keywords | ["日本語", "中文"] | Stored correctly |
| VS-243 | Emoji in brand_name | "Acme 🚀" | Stored correctly |
| VS-244 | SQL injection in brand_name | "'; DROP TABLE" | Escaped safely |
| VS-245 | XSS in brand_name | "<script>alert()" | Escaped safely |
| VS-246 | Very long brand_name | 500 chars | Truncated or error |
| VS-247 | Empty brand_name | "" | ValidationException |
| VS-248 | Null values in optional fields | null competitors | Handled correctly |
| VS-249 | Cache invalidation on update | Update project | Cache cleared |
| VS-250 | Cache invalidation on delete | Delete project | Cache cleared |
| VS-251 | Memory usage with large data | 1000 projects | No memory leak |
| VS-252 | Performance: create | Create project | <100ms |
| VS-253 | Performance: analytics | Get analytics | <500ms |
| VS-254 | Performance: comparison | Get comparison | <500ms |
| VS-255 | Logger called on all operations | Any operation | Log entries |
| VS-256 | Transaction rollback on partial failure | Force error | All rolled back |
| VS-257 | Event dispatching | All CRUD ops | Events dispatched |
| VS-258 | Repository pattern usage | Check calls | Repository used |
| VS-259 | Dependency injection throughout | Check constructor | All injected |
| VS-260 | Service handles its own exceptions | Internal error | Graceful handling |

---

## Mock Data

### Sample Project Creation Data

```php
$projectData = [
    'brand_name' => 'Acme Corp',
    'keywords' => ['acme', 'acme corp', 'acme products'],
    'competitors' => [
        [
            'name' => 'Competitor A',
            'keywords' => ['comp a', 'competitor a']
        ],
        [
            'name' => 'Competitor B',
            'keywords' => ['comp b', 'competitor b']
        ],
    ],
    'query_templates' => [
        'What are the best {category} tools for {audience}?',
        'Can you recommend a good {category} tool?',
    ],
    'tracking_frequency' => 'weekly',
];
```

### Sample Analytics Response

```php
$analyticsResponse = [
    'mention_rate_by_platform' => [
        'chatgpt' => 72.5,
        'perplexity' => 65.0,
    ],
    'share_of_voice' => 33.33,
    'sentiment_breakdown' => [
        'positive' => 45,
        'neutral' => 35,
        'negative' => 20,
    ],
    'trend_direction' => 'up',
    'total_queries' => 50,
    'brand_mentions' => 35,
    'competitor_mentions' => [
        'Competitor A' => 42,
        'Competitor B' => 28,
    ],
    'period_start' => '2026-03-01',
    'period_end' => '2026-03-07',
    'last_updated' => '2026-03-07T12:00:00Z',
];
```

### Sample Competitor Comparison Response

```php
$comparisonResponse = [
    'brand' => [
        'name' => 'Acme Corp',
        'mention_count' => 35,
        'mention_rate' => 70.0,
        'sentiment' => [
            'positive' => 25,
            'neutral' => 8,
            'negative' => 2,
        ],
    ],
    'competitors' => [
        [
            'name' => 'Competitor A',
            'mention_count' => 42,
            'mention_rate' => 84.0,
            'sentiment' => [
                'positive' => 30,
                'neutral' => 10,
                'negative' => 2,
            ],
        ],
        [
            'name' => 'Competitor B',
            'mention_count' => 28,
            'mention_rate' => 56.0,
            'sentiment' => [
                'positive' => 18,
                'neutral' => 8,
                'negative' => 2,
            ],
        ],
    ],
    'winner' => 'Competitor A',
    'gap_percentage' => 14.0,
];
```

### Sample Alert Response

```php
$alertResponse = [
    [
        'id' => 1,
        'project_id' => 1,
        'alert_type' => 'mention_spike',
        'threshold' => 20.00,
        'previous_value' => 35.00,
        'current_value' => 52.00,
        'change_percent' => 48.57,
        'triggered_at' => '2026-03-07T12:00:00Z',
        'is_read' => false,
        'is_resolved' => false,
    ],
];
```

---

## Coverage Targets

| Category | Tests | Target |
|----------|-------|--------|
| Service Instantiation | 10 | 100% AC coverage |
| createProject() | 25 | 100% AC coverage |
| updateProject() | 20 | 100% AC coverage |
| deleteProject() | 20 | 100% AC coverage |
| runQueries() | 35 | 100% AC coverage |
| getAnalytics() | 35 | 100% AC coverage |
| getCompetitorComparison() | 30 | 100% AC coverage |
| checkAlerts() | 35 | 100% AC coverage |
| Query Limit Enforcement | 25 | 100% AC coverage |
| Integration & Edge Cases | 25 | 100% AC coverage |
| **Total** | **260** | **100% acceptance criteria coverage** |

---

## Test Execution

```bash
# Run all AIVisibilityService tests
cd backend
php artisan test --filter=AIVisibilityServiceTest

# Run with coverage
php artisan test --filter=AIVisibilityServiceTest --coverage

# Run specific test category
php artisan test --filter="VS-001"
```

---

## Notes for Developer

### 1. Namespace Structure
```
app/Services/AIVisibility/
├── AIVisibilityService.php
├── Contracts/
│   └── AIVisibilityServiceInterface.php
└── Exceptions/
    └── QueryLimitExceededException.php
```

### 2. Service Interface
```php
interface AIVisibilityServiceInterface
{
    public function createProject(int $userId, array $data): AIVisibilityProject;
    public function updateProject(int $projectId, array $data): AIVisibilityProject;
    public function deleteProject(int $projectId, int $userId): bool;
    public function runQueries(int $projectId): array;
    public function getAnalytics(int $projectId, string $period = 'week'): array;
    public function getCompetitorComparison(int $projectId): array;
    public function checkAlerts(int $projectId): array;
    public function getQueryLimit(int $userId): int;
    public function getQueryUsage(int $userId): int;
    public function getRemainingQueries(int $userId): int;
    public function canRunQueries(int $userId, int $count = 1): bool;
}
```

### 3. Query Limit Configuration
```php
// config/ai-visibility.php
return [
    'query_limits' => [
        'free' => 5,
        'pro' => 50,
        'business' => 200,
        'unlimited' => null,
    ],
    'cache_ttl' => 3600, // 1 hour
    'alert_thresholds' => [
        'mention_spike' => 20, // +20%
        'mention_drop' => 20,  // -20%
        'competitor_gain' => 15, // +15%
    ],
];
```

### 4. Query Limit Exception
```php
class QueryLimitExceededException extends Exception
{
    public function __construct(
        public int $limit,
        public int $used,
        public string $tier
    ) {
        parent::__construct(
            "You have exceeded your monthly query limit of {$limit} queries ({$tier} tier). Used: {$used}"
        );
    }
}
```

### 5. Cache Key Patterns
```php
$analyticsCacheKey = "ai_visibility_analytics_{$projectId}_{$period}";
$comparisonCacheKey = "ai_visibility_comparison_{$projectId}";
```

### 6. Alert Threshold Check
```php
private function shouldCreateAlert(string $type, float $changePercent): bool
{
    $threshold = config("ai-visibility.alert_thresholds.{$type}", 20);
    return abs($changePercent) >= $threshold;
}
```

### 7. Query Generation
```php
private function generateQueries(AIVisibilityProject $project): array
{
    $queries = [];
    $templates = $project->query_templates ?: $this->getDefaultTemplates();
    $brand = $project->brand_name;
    $competitors = collect($project->competitors)->pluck('name')->implode(', ');
    
    foreach ($templates as $template) {
        $queries[] = str_replace(
            ['{brand}', '{competitors}'],
            [$brand, $competitors],
            $template
        );
    }
    
    return $queries;
}
```

---

## Acceptance Criteria Mapping

| AC | Test IDs |
|----|----------|
| AIVisibilityService class created | VS-001, VS-002 |
| Implements AIVisibilityServiceInterface | VS-002 |
| createProject() method | VS-011 to VS-035 |
| updateProject() method | VS-036 to VS-055 |
| deleteProject() method | VS-056 to VS-075 |
| runQueries() method | VS-076 to VS-110 |
| getAnalytics() method | VS-111 to VS-145 |
| getCompetitorComparison() method | VS-146 to VS-175 |
| checkAlerts() method | VS-176 to VS-210 |
| Query limit enforcement | VS-211 to VS-235 |
| Unit tests created | All VS-* tests |

---

## Comments

### PreTester Test Design (2026-03-07 19:35 UTC)

**Test Approach:**
- Unit tests with mocked dependencies (models, services, facades)
- Each method tested independently with comprehensive scenarios
- Error paths tested with exception mocking
- Cache behavior tested with Cache facade mock
- Queue dispatch tested with Queue facade mock

**Dependencies:**
- TASK-34-2 (Models) - Mocked in tests
- TASK-34-3 (AIQueryEngine) - Mocked in tests
- TASK-34-4 (MentionParser) - Mocked in tests

**TDD Pattern:**
- Tests written before service implementation
- All tests will fail until service is created
- Mock interfaces define expected behavior

**Edge Cases Identified:**
- User ownership validation
- Query limit enforcement with tier-based limits
- Cache invalidation on data changes
- Alert threshold boundaries (+19% vs +20%)
- Transaction rollback on errors
- Concurrent operations
- Unicode and special characters
- XSS and SQL injection prevention
- Large data handling
- Performance requirements

**Security Considerations:**
- User ownership validation prevents unauthorized access
- SQL injection prevention via Eloquent
- XSS prevention via model attribute casting
- Query limits prevent abuse

---

*Created by PreTester on 2026-03-07*
