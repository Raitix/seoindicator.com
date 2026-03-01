# TASK-21-7: Add Debug Mode and Logging

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Medium`

## Specification
[SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

## Description
Implement comprehensive debug mode and logging for AI suggestion operations. Debug mode allows developers to inspect prompts, responses, and performance metrics. Logging provides audit trail and troubleshooting data.

---

## Acceptance Criteria

### AC-1: Debug Mode Configuration
- [ ] Environment variable `AI_DEBUG=true/false`
- [ ] Config value at `config('ai.debug')`
- [ ] Per-request debug via `?debug=true` query parameter
- [ ] Debug mode restricted to authenticated users or specific roles

### AC-2: Debug Response Fields
- [ ] When debug enabled, response includes:
  - `debug.prompt`: Full prompt sent to AI
  - `debug.raw_response`: Raw AI response text
  - `debug.tokens_used`: Total tokens (prompt + completion)
  - `debug.latency_ms`: Request duration in milliseconds
  - `debug.provider`: Provider name
  - `debug.model`: Model identifier
  - `debug.cache_hit`: Boolean if result was from cache

### AC-3: Logging Service Created
- [ ] File exists at `backend/app/Services/AI/AILogger.php`
- [ ] Method `logRequest(array $data): void`
- [ ] Method `logResponse(array $data): void`
- [ ] Method `logError(array $data): void`
- [ ] Uses Laravel logging with dedicated channel

### AC-4: Log Channel Configuration
- [ ] Custom log channel `ai` in `config/logging.php`
- [ ] Separate log file: `storage/logs/ai.log`
- [ ] Daily rotation enabled
- [ ] Retention: 30 days

### AC-5: Request Logging
- [ ] Logs every AI request with:
  - Timestamp
  - User ID (if authenticated)
  - Session ID (if anonymous)
  - Indicator type
  - Provider name
  - Request hash (for cache tracking)
  - Prompt length

### AC-6: Response Logging
- [ ] Logs every AI response with:
  - Timestamp
  - Request hash
  - Success/failure status
  - Tokens used (prompt, completion, total)
  - Latency in milliseconds
  - Model used
  - Cache hit/miss

### AC-7: Error Logging
- [ ] Logs all errors with:
  - Error type (timeout, rate limit, invalid response, etc.)
  - Error message
  - Stack trace (truncated)
  - Request context
  - Response body (if available)

### AC-8: Database Logging (Optional)
- [ ] Migration for `ai_suggestion_logs` table
- [ ] Stores: user_id, session_id, indicator, provider, model, tokens, latency, success, error
- [ ] Indexes for querying by user, provider, date
- [ ] Retention policy: 90 days

### AC-9: Artisan Commands
- [ ] `php artisan ai:logs` - View recent AI logs
- [ ] `php artisan ai:logs --errors` - View error logs only
- [ ] `php artisan ai:stats` - Show usage statistics

### AC-10: Unit Tests
- [ ] Test file at `backend/tests/Unit/Services/AI/AILoggerTest.php`
- [ ] Tests for each log method
- [ ] Tests for debug response structure
- [ ] All tests passing

---

## Dependencies
- TASK-21-5 (AI Suggestion API Endpoint)
- TASK-21-6 (Caching Layer)

---

## Effort Estimate
**Medium** (3-4 hours)

- Debug mode: 1 hour
- Logging service: 1.5 hours
- Tests: 1.5 hours

---

## Technical Notes

### Debug Response Example
```json
{
    "success": true,
    "data": { /* suggestions */ },
    "debug": {
        "prompt": "You are an SEO expert...\n\nAnalyze this title...",
        "raw_response": "{\"score\": 65, \"issues\": [...]}",
        "tokens_used": {
            "prompt": 150,
            "completion": 200,
            "total": 350
        },
        "latency_ms": 1250,
        "provider": "openrouter",
        "model": "llama-3.1-8b-instruct",
        "cache_hit": false
    }
}
```

### Log Format
```
[2026-03-01 12:00:00] ai.INFO: AI Request {"indicator":"title","provider":"openrouter","user_id":1,"request_hash":"abc123..."}
[2026-03-01 12:00:01] ai.INFO: AI Response {"request_hash":"abc123...","success":true,"tokens":350,"latency_ms":1250}
[2026-03-01 12:00:02] ai.ERROR: AI Error {"indicator":"title","error":"timeout","latency_ms":30000}
```

### Database Schema
```sql
CREATE TABLE ai_suggestion_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(255) NULL,
    indicator VARCHAR(50) NOT NULL,
    provider VARCHAR(50) NOT NULL,
    model VARCHAR(100) NULL,
    prompt_tokens INT UNSIGNED NULL,
    completion_tokens INT UNSIGNED NULL,
    latency_ms INT UNSIGNED NOT NULL,
    success TINYINT(1) NOT NULL,
    error_message TEXT NULL,
    request_hash VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_indicator (user_id, indicator),
    INDEX idx_provider_created (provider, created_at),
    INDEX idx_success (success)
);
```

---

## Comments
_Agent log_

(No entries yet)

---
*Created by SpecSystemAnalyst on 2026-03-01*
