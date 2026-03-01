# TASK-21-8: Create Database Migrations

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification
[SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

## Description
Create database migrations for AI suggestion logs and cache tables. These tables support debugging, analytics, and performance optimization.

---

## Acceptance Criteria

### AC-1: ai_suggestion_logs Migration
- [ ] Migration file created with timestamp
- [ ] Table structure matches spec:
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NULL (FK to users.id, ON DELETE SET NULL)
  - `session_id`: VARCHAR(255) NULL
  - `indicator`: VARCHAR(50) NOT NULL
  - `provider`: VARCHAR(50) NOT NULL
  - `model`: VARCHAR(100) NULL
  - `prompt_tokens`: INT UNSIGNED NULL
  - `completion_tokens`: INT UNSIGNED NULL
  - `latency_ms`: INT UNSIGNED NOT NULL
  - `success`: TINYINT(1) NOT NULL
  - `error_message`: TEXT NULL
  - `request_hash`: VARCHAR(64) NULL
  - `created_at`: TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- [ ] Indexes:
  - `idx_user_indicator` on (user_id, indicator)
  - `idx_provider_created` on (provider, created_at)
  - `idx_success` on (success)
  - `idx_request_hash` on (request_hash)

### AC-2: ai_suggestions_cache Migration
- [ ] Migration file created with timestamp
- [ ] Table structure matches spec:
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `request_hash`: VARCHAR(64) NOT NULL UNIQUE
  - `indicator`: VARCHAR(50) NOT NULL
  - `context_hash`: VARCHAR(64) NOT NULL
  - `response_json`: TEXT NOT NULL
  - `provider`: VARCHAR(50) NOT NULL
  - `created_at`: TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  - `expires_at`: TIMESTAMP NOT NULL
- [ ] Indexes:
  - `idx_hash` on (request_hash)
  - `idx_expires` on (expires_at)

### AC-3: Model Created (AISuggestionLog)
- [ ] File exists at `backend/app/Models/AISuggestionLog.php`
- [ ] `$fillable` array defined
- [ ] `$casts` for proper type casting
- [ ] Relationship to User model

### AC-4: Model Created (AISuggestionCache)
- [ ] File exists at `backend/app/Models/AISuggestionCache.php`
- [ ] `$fillable` array defined
- [ ] `$casts` for proper type casting
- [ ] Scope for expired entries

### AC-5: Migration Rollback
- [ ] Both migrations have proper `down()` methods
- [ ] Tables dropped on rollback
- [ ] Safe to re-run after rollback

### AC-6: Unit Tests
- [ ] Test file at `backend/tests/Unit/Migrations/AISuggestionTablesTest.php`
- [ ] Tests for column existence and types
- [ ] Tests for indexes
- [ ] Tests for foreign keys
- [ ] Tests for rollback
- [ ] All tests passing

---

## Dependencies
- Users table (existing)
- TASK-21-7 (Debug Mode and Logging - uses these tables)

---

## Effort Estimate
**Small** (2 hours)

- Migrations: 1 hour
- Models: 0.5 hours
- Tests: 0.5 hours

---

## Technical Notes

### Migration 1: ai_suggestion_logs
```php
Schema::create('ai_suggestion_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('session_id', 255)->nullable();
    $table->string('indicator', 50);
    $table->string('provider', 50);
    $table->string('model', 100)->nullable();
    $table->unsignedInteger('prompt_tokens')->nullable();
    $table->unsignedInteger('completion_tokens')->nullable();
    $table->unsignedInteger('latency_ms');
    $table->boolean('success');
    $table->text('error_message')->nullable();
    $table->string('request_hash', 64)->nullable();
    $table->timestamp('created_at')->useCurrent();
    
    $table->index(['user_id', 'indicator'], 'idx_user_indicator');
    $table->index(['provider', 'created_at'], 'idx_provider_created');
    $table->index('success', 'idx_success');
    $table->index('request_hash', 'idx_request_hash');
});
```

### Migration 2: ai_suggestions_cache
```php
Schema::create('ai_suggestions_cache', function (Blueprint $table) {
    $table->id();
    $table->string('request_hash', 64)->unique();
    $table->string('indicator', 50);
    $table->string('context_hash', 64);
    $table->text('response_json');
    $table->string('provider', 50);
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('expires_at');
    
    $table->index('request_hash', 'idx_hash');
    $table->index('expires_at', 'idx_expires');
});
```

---

## Comments
_Agent log_

(No entries yet)

---
*Created by SpecSystemAnalyst on 2026-03-01*
