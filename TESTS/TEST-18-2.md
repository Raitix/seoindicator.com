# TEST-18-2: SEOAnalysis Model and Repository

## Task Reference
- **Task:** TASK-18-2
- **Feature:** FEAT-018 (Legacy 17-Indicator SEO Analysis Core Engine)
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for the SEOAnalysis model and repository pattern. Verifies model attributes, relationships, JSON casting, scopes, and repository CRUD operations.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Models/SEOAnalysisTest.php` | Unit | 25+ | 60+ |
| `backend/tests/Unit/Repositories/SEOAnalysisRepositoryTest.php` | Unit | 20+ | 50+ |

---

## Test Scenarios

### Model Creation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MC-01 | Create with minimal data | domain, normalized_url, overall_score, analysis_data | Model created with ID |
| MC-02 | Create with user_id | Valid user_id | Model belongs to user |
| MC-03 | Create with session_id | Valid session_id | Model has session_id |
| MC-04 | Create without user/session | Neither user_id nor session_id | Model created (anonymous) |
| MC-05 | Create with all fields | All fields populated | Model with all data |
| MC-06 | Factory creates valid model | Factory::make() | Valid model instance |

### Fillable Attributes Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FA-01 | Mass assign user_id | ['user_id' => 1, ...] | user_id set |
| FA-02 | Mass assign session_id | ['session_id' => 'abc', ...] | session_id set |
| FA-03 | Mass assign domain | ['domain' => 'example.com', ...] | domain set |
| FA-04 | Mass assign normalized_url | ['normalized_url' => 'https://...'] | normalized_url set |
| FA-05 | Mass assign overall_score | ['overall_score' => 78, ...] | overall_score set |
| FA-06 | Mass assign status | ['status' => 'completed', ...] | status set |
| FA-07 | Mass assign error_message | ['error_message' => 'Error', ...] | error_message set |
| FA-08 | Mass assign analysis_data | ['analysis_data' => [...], ...] | analysis_data set |
| FA-09 | Mass assign checked_at | ['checked_at' => now(), ...] | checked_at set |
| FA-10 | Mass assign expires_at | ['expires_at' => future(), ...] | expires_at set |
| FA-11 | Guard non-fillable | ['id' => 999, ...] | id ignored (auto-increment) |

### JSON Casting Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JC-01 | Store as array | analysis_data = ['score' => 78] | Returns as array |
| JC-02 | Retrieve as array | Get model | analysis_data is array |
| JC-03 | Nested JSON | analysis_data with nested objects | Nested structure preserved |
| JC-04 | Empty object | analysis_data = [] | Returns empty array |
| JC-05 | Large JSON | analysis_data with 100 indicators | Stored and retrieved correctly |
| JC-06 | AsArrayObject cast | Access analysis_data['key'] | ArrayObject behavior |
| JC-07 | Update nested value | analysis_data['score'] = 80 | Nested value updated |

### Relationship Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RL-01 | BelongsTo User | analysis->user | User model instance |
| RL-02 | BelongsTo User (null) | analysis without user_id | null |
| RL-03 | User hasMany analyses | user->seoAnalyses | Collection of analyses |
| RL-04 | Eager load user | Analysis::with('user')->get() | User loaded |
| RL-05 | Lazy load user | analysis->user | Query executed |

### Status Accessor/Mutator Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SA-01 | Get status attribute | status = 'pending' | Returns 'pending' |
| SA-02 | Set status attribute | status = 'completed' | Stored as 'completed' |
| SA-03 | Status label accessor | $analysis->status_label | 'Completed' (human-readable) |
| SA-04 | Is pending check | $analysis->isPending() | Boolean |
| SA-05 | Is running check | $analysis->isRunning() | Boolean |
| SA-06 | Is completed check | $analysis->isCompleted() | Boolean |
| SA-07 | Is failed check | $analysis->isFailed() | Boolean |

### Model Scopes Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MS-01 | Scope byStatus | ->byStatus('completed') | Only completed analyses |
| MS-02 | Scope byUser | ->byUser(1) | Only user's analyses |
| MS-03 | Scope bySession | ->bySession('abc') | Only session's analyses |
| MS-04 | Scope expired | ->expired() | Analyses past expires_at |
| MS-05 | Scope notExpired | ->notExpired() | Analyses not expired |
| MS-06 | Scope forDomain | ->forDomain('example.com') | Analyses for domain |
| MS-07 | Scope pending | ->pending() | Status = pending |
| MS-08 | Scope completed | ->completed() | Status = completed |
| MS-09 | Scope failed | ->failed() | Status = failed |
| MS-10 | Scope recent | ->recent(7) | Last N days |
| MS-11 | Chain scopes | ->byUser(1)->completed()->recent(7) | Filtered results |

### Model Methods Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MM-01 | markAsRunning() | Call method | status = 'running' |
| MM-02 | markAsCompleted($score, $data) | Call with params | status = 'completed', fields updated |
| MM-03 | markAsFailed($error) | Call with error | status = 'failed', error_message set |
| MM-04 | isExpired() | expires_at < now | true |
| MM-05 | isExpired() | expires_at > now | false |
| MM-06 | isExpired() | expires_at = null | false |
| MM-07 | getCacheKey() | Call method | Unique cache key string |
| MM-08 | getRemainingTTL() | Call method | Seconds until expiry |

### Repository CRUD Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RC-01 | Create analysis | Repository::create($data) | New analysis returned |
| RC-02 | Find by ID | Repository::find($id) | Analysis model |
| RC-03 | Find by ID (not found) | Repository::find(99999) | null |
| RC-04 | Update analysis | Repository::update($id, $data) | Updated model |
| RC-05 | Delete analysis | Repository::delete($id) | true |
| RC-06 | Delete (not found) | Repository::delete(99999) | false |
| RC-07 | Find by domain | Repository::findByDomain('example.com') | Collection |
| RC-08 | Find cached (valid) | Repository::findCached('example.com') | Analysis (not expired) |
| RC-09 | Find cached (expired) | Repository::findCached('example.com') | null (expired) |
| RC-10 | Find cached (not found) | Repository::findCached('notfound.com') | null |
| RC-11 | Find by user | Repository::findByUser($userId) | Collection |
| RC-12 | Find by session | Repository::findBySession($sessionId) | Collection |
| RC-13 | Get latest for domain | Repository::getLatest('example.com') | Latest analysis |
| RC-14 | Get all pending | Repository::getPending() | Collection of pending |
| RC-15 | Get expired analyses | Repository::getExpired() | Collection of expired |

### Repository Caching Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RCH-01 | Cache on find | Repository::find($id) | Result cached |
| RCH-02 | Cache hit | Second find() call | From cache (no DB query) |
| RCH-03 | Cache invalidation on update | Update analysis | Cache cleared |
| RCH-04 | Cache invalidation on delete | Delete analysis | Cache cleared |
| RCH-05 | Cache TTL matches expires_at | Cached result | Expires with analysis |

### Edge Cases and Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-01 | Very long domain | 500 char domain | Truncated or error |
| EC-02 | Unicode domain | 例え.jp | Handled correctly |
| EC-03 | SQL injection in domain | Malicious string | Safe (parameterized) |
| EC-04 | XSS in error_message | <script> tag | Stored safely |
| EC-05 | Empty analysis_data | {} | Valid empty object |
| EC-06 | Null user_id and session_id | Neither provided | Anonymous analysis |
| EC-07 | Duplicate domain for same user | Same domain twice | Allowed (different analyses) |
| EC-08 | Score out of range | score = 150 | Stored (business logic validates) |
| EC-09 | Score negative | score = -10 | Stored (business logic validates) |
| EC-10 | Large analysis_data | 1MB JSON | Stored (if within limits) |

---

## Mock Data

### Valid Analysis Model
```php
[
    'user_id' => 1,
    'session_id' => null,
    'domain' => 'example.com',
    'normalized_url' => 'https://example.com',
    'overall_score' => 78,
    'status' => 'completed',
    'error_message' => null,
    'analysis_data' => [
        'overallScore' => 78,
        'categoryScores' => [
            'content' => 82,
            'technical' => 75,
        ],
        'indicators' => [],
    ],
    'checked_at' => '2026-02-28 12:00:00',
    'expires_at' => '2026-03-01 12:00:00',
]
```

### Analysis with Indicators
```php
[
    'domain' => 'example.com',
    'overall_score' => 65,
    'analysis_data' => [
        'indicators' => [
            ['id' => 'url', 'name' => 'URL Structure', 'score' => 3, 'passed' => true],
            ['id' => 'title_tag', 'name' => 'Title Tag', 'score' => 2, 'passed' => false],
        ],
    ],
]
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Model Creation | 100% | All fillable attributes |
| JSON Casting | 100% | Array access, nested data |
| Relationships | 100% | User relationship |
| Scopes | 100% | All defined scopes |
| Repository CRUD | 100% | All repository methods |
| Caching | 80% | Cache hit/miss/invalidate |
| Edge Cases | 90% | Boundary conditions |
| Security | 100% | SQL injection, XSS |

---

## Test Execution

```bash
# Run model tests
php artisan test --filter=SEOAnalysisTest

# Run repository tests
php artisan test --filter=SEOAnalysisRepositoryTest

# Run all tests for this task
php artisan test --filter=SEOAnalysis

# Run with coverage
php artisan test --filter=SEOAnalysis --coverage
```

---

## Dependencies
- TASK-18-1 (seo_analyses table migration)
- User model (existing)
- Laravel Eloquent ORM

---

## Notes for Developer
- Use Laravel's `AsArrayObject::class` cast for analysis_data
- Repository should inject Cache facade for caching
- Consider using Cache tags for easier invalidation
- Scopes should be chainable for complex queries
- Factory should generate realistic analysis_data

---

## Comments

### [2026-02-28] PreTester
- Test documentation created
- 70+ test scenarios covering model, repository, and edge cases
- Ready for test implementation
