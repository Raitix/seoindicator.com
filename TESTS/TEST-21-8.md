# TEST-21-8: Database Migrations Test Documentation

## Task Reference
- Task: [TASK-21-8](../TASKS/TASK-21-8.md) - Create Database Migrations
- Specification: [SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

---

## Overview

Tests for database migrations creating `ai_suggestion_logs` and `ai_suggestions_cache` tables, along with their corresponding Eloquent models. Tests verify schema structure, indexes, foreign keys, and rollback functionality.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Migrations/AISuggestionTablesTest.php` | Unit | Migration schema validation, model tests |

---

## Test Scenarios

### TC-1: ai_suggestion_logs Table Schema

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-1.1 | Table exists after migration | Run migration | Table `ai_suggestion_logs` exists |
| TC-1.2 | Column `id` exists | Check schema | `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` |
| TC-1.3 | Column `user_id` exists | Check schema | `BIGINT UNSIGNED NULL`, FK to `users.id` |
| TC-1.4 | Column `session_id` exists | Check schema | `VARCHAR(255) NULL` |
| TC-1.5 | Column `indicator` exists | Check schema | `VARCHAR(50) NOT NULL` |
| TC-1.6 | Column `provider` exists | Check schema | `VARCHAR(50) NOT NULL` |
| TC-1.7 | Column `model` exists | Check schema | `VARCHAR(100) NULL` |
| TC-1.8 | Column `prompt_tokens` exists | Check schema | `INT UNSIGNED NULL` |
| TC-1.9 | Column `completion_tokens` exists | Check schema | `INT UNSIGNED NULL` |
| TC-1.10 | Column `latency_ms` exists | Check schema | `INT UNSIGNED NOT NULL` |
| TC-1.11 | Column `success` exists | Check schema | `TINYINT(1) NOT NULL` |
| TC-1.12 | Column `error_message` exists | Check schema | `TEXT NULL` |
| TC-1.13 | Column `request_hash` exists | Check schema | `VARCHAR(64) NULL` |
| TC-1.14 | Column `created_at` exists | Check schema | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` |
| TC-1.15 | Index `idx_user_indicator` exists | Check indexes | Composite index on `(user_id, indicator)` |
| TC-1.16 | Index `idx_provider_created` exists | Check indexes | Composite index on `(provider, created_at)` |
| TC-1.17 | Index `idx_success` exists | Check indexes | Index on `success` |
| TC-1.18 | Index `idx_request_hash` exists | Check indexes | Index on `request_hash` |
| TC-1.19 | Foreign key on `user_id` | Delete user | Log entry remains with `user_id = NULL` |
| TC-1.20 | Table dropped on rollback | Rollback migration | Table `ai_suggestion_logs` does not exist |

### TC-2: ai_suggestions_cache Table Schema

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-2.1 | Table exists after migration | Run migration | Table `ai_suggestions_cache` exists |
| TC-2.2 | Column `id` exists | Check schema | `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` |
| TC-2.3 | Column `request_hash` exists | Check schema | `VARCHAR(64) NOT NULL UNIQUE` |
| TC-2.4 | Column `indicator` exists | Check schema | `VARCHAR(50) NOT NULL` |
| TC-2.5 | Column `context_hash` exists | Check schema | `VARCHAR(64) NOT NULL` |
| TC-2.6 | Column `response_json` exists | Check schema | `TEXT NOT NULL` |
| TC-2.7 | Column `provider` exists | Check schema | `VARCHAR(50) NOT NULL` |
| TC-2.8 | Column `created_at` exists | Check schema | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` |
| TC-2.9 | Column `expires_at` exists | Check schema | `TIMESTAMP NOT NULL` |
| TC-2.10 | Unique constraint on `request_hash` | Insert duplicate | Database error (unique violation) |
| TC-2.11 | Index `idx_hash` exists | Check indexes | Index on `request_hash` |
| TC-2.12 | Index `idx_expires` exists | Check indexes | Index on `expires_at` |
| TC-2.13 | Table dropped on rollback | Rollback migration | Table `ai_suggestions_cache` does not exist |

### TC-3: AISuggestionLog Model

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-3.1 | Model exists | Check file | File exists at `backend/app/Models/AISuggestionLog.php` |
| TC-3.2 | Model is instantiable | `new AISuggestionLog()` | Instance created |
| TC-3.3 | Fillable attributes | Check `$fillable` | Contains: `user_id`, `session_id`, `indicator`, `provider`, `model`, `prompt_tokens`, `completion_tokens`, `latency_ms`, `success`, `error_message`, `request_hash` |
| TC-3.4 | Casts defined | Check `$casts` | `success` → `boolean`, `prompt_tokens` → `integer`, `completion_tokens` → `integer`, `latency_ms` → `integer` |
| TC-3.5 | User relationship | `$log->user` | Returns `BelongsTo` relationship to `User` model |
| TC-3.6 | Create log entry | Factory create | Record persists to database |
| TC-3.7 | Query by indicator | `AISuggestionLog::where('indicator', 'title')` | Returns collection |
| TC-3.8 | Query by user | `AISuggestionLog::where('user_id', 1)` | Returns collection |
| TC-3.9 | Query failed requests | `AISuggestionLog::where('success', false)` | Returns failed log entries |

### TC-4: AISuggestionCache Model

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-4.1 | Model exists | Check file | File exists at `backend/app/Models/AISuggestionCache.php` |
| TC-4.2 | Model is instantiable | `new AISuggestionCache()` | Instance created |
| TC-4.3 | Fillable attributes | Check `$fillable` | Contains: `request_hash`, `indicator`, `context_hash`, `response_json`, `provider`, `expires_at` |
| TC-4.4 | Casts defined | Check `$casts` | `expires_at` → `datetime` |
| TC-4.5 | Expired scope | `AISuggestionCache::expired()->get()` | Returns entries where `expires_at < now` |
| TC-4.6 | Valid scope | `AISuggestionCache::valid()->get()` | Returns entries where `expires_at > now` |
| TC-4.7 | Create cache entry | Factory create | Record persists with unique `request_hash` |
| TC-4.8 | Find by hash | `AISuggestionCache::findByHash($hash)` | Returns cache entry or null |
| TC-4.9 | Duplicate hash rejected | Insert same `request_hash` | Database unique constraint violation |

### TC-5: Migration Rollback & Re-run

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-5.1 | Rollback both tables | `php artisan migrate:rollback` | Both tables dropped |
| TC-5.2 | Re-run after rollback | `php artisan migrate` | Both tables recreated successfully |
| TC-5.3 | Fresh migration | `php artisan migrate:fresh` | All tables including AI tables created |

### TC-6: Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-6.1 | Null user_id allowed | Insert log with `user_id = null` | Record created successfully |
| TC-6.2 | Long session_id | Insert 255 char session_id | Record created successfully |
| TC-6.3 | Long error_message | Insert 65535 char error_message | Record created successfully |
| TC-6.4 | Zero latency | Insert `latency_ms = 0` | Record created successfully |
| TC-6.5 | Negative latency rejected | Insert `latency_ms = -1` | Database error or validation error |
| TC-6.6 | Expired cache cleanup | Delete expired entries | Only expired entries removed |
| TC-6.7 | Cache TTL calculation | Create entry with 24h TTL | `expires_at` = `created_at + 24 hours` |

---

## Mock Data

### AISuggestionLog Factory
```php
// database/factories/AISuggestionLogFactory.php
public function definition()
{
    return [
        'user_id' => User::factory(),
        'session_id' => Str::random(32),
        'indicator' => $this->faker->randomElement(['title', 'meta_description', 'robots', 'sitemap']),
        'provider' => $this->faker->randomElement(['openrouter', 'vercel', 'static_fallback']),
        'model' => 'llama-3.1-8b-instruct',
        'prompt_tokens' => $this->faker->numberBetween(50, 500),
        'completion_tokens' => $this->faker->numberBetween(100, 1000),
        'latency_ms' => $this->faker->numberBetween(100, 5000),
        'success' => $this->faker->boolean(90),
        'error_message' => null,
        'request_hash' => hash('sha256', Str::random(64)),
    ];
}
```

### AISuggestionCache Factory
```php
// database/factories/AISuggestionCacheFactory.php
public function definition()
{
    return [
        'request_hash' => hash('sha256', Str::random(64)),
        'indicator' => $this->faker->randomElement(['title', 'meta_description', 'robots', 'sitemap']),
        'context_hash' => hash('sha256', Str::random(64)),
        'response_json' => json_encode(['suggestions' => ['Test suggestion']]),
        'provider' => $this->faker->randomElement(['openrouter', 'vercel']),
        'expires_at' => now()->addHours(24),
    ];
}
```

---

## Coverage Targets

| Component | Target Coverage |
|-----------|-----------------|
| Migration Schema Validation | 100% |
| Model Fillable/Casts | 100% |
| Model Relationships | 100% |
| Model Scopes | 100% |
| Rollback Tests | 100% |
| Edge Cases | 80%+ |

---

## Test Execution

```bash
# Run all migration tests
php artisan test --filter=AISuggestionTablesTest

# Run with coverage
php artisan test --filter=AISuggestionTablesTest --coverage

# Run specific test method
php artisan test --filter=test_ai_suggestion_logs_table_schema
```

---

## Dependencies

| Dependency | Type | Notes |
|------------|------|-------|
| Laravel Framework | Runtime | Migration, Schema, Eloquent |
| PHPUnit | Testing | Test framework |
| Users table | Database | Foreign key reference |
| User model | Model | Relationship target |

---

## Notes for Developer

1. **Migration Naming**: Use Laravel's timestamp naming convention (e.g., `2026_03_02_000000_create_ai_suggestion_logs_table.php`)

2. **Foreign Key**: Use `nullOnDelete()` for `user_id` foreign key to preserve logs when users are deleted

3. **Indexes**: All specified indexes are critical for query performance in production

4. **Model Scopes**: Implement `expired()` and `valid()` scopes on `AISuggestionCache` for easy cache management

5. **Factory Creation**: Create model factories for test data generation

6. **Cache TTL**: Default TTL should be configurable via `config('ai.cache.ttl_hours')`

---

## Comments
_Agent log_

### [2026-03-02 17:25 GMT+2] PreTester - Tests Coverage In Progress
Created test documentation for TASK-21-8.

**Test Strategy:**
- Schema validation tests for both tables (columns, types, indexes)
- Model tests (fillable, casts, relationships, scopes)
- Rollback and re-run tests
- Edge cases (null values, long strings, constraints)

**Coverage Target:** 100% for migration and model tests

**Test File Created:** `backend/tests/Unit/Migrations/AISuggestionTablesTest.php`

**Next Step:** Create the actual test file with PHPUnit tests.
