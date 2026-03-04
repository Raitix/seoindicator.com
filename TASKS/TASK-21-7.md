# TASK-21-7: Add Debug Mode and Logging

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification
[SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

## Description
Implement debug mode and comprehensive logging for AI suggestions. Debug mode exposes detailed information about prompts, responses, and provider behavior for troubleshooting.

---

## Acceptance Criteria

### AC-1: AILogger Service Created
- [ ] File exists at `backend/app/Services/AI/AILogger.php`
- [ ] Method `logRequest(string $indicator, array $context, string $provider): void`
- [ ] Method `logResponse(string $indicator, array $response, int $latencyMs): void`
- [ ] Method `logError(string $indicator, string $error, array $context = []): void`
- [ ] Method `logDebug(string $message, array $data = []): void`

### AC-2: Debug Mode Implementation
- [ ] Enabled via `AI_DEBUG=true` env variable
- [ ] Debug info included in API responses when enabled:
  - Full prompt text
  - Raw AI response
  - Token usage
  - Latency
  - Provider and model
- [ ] Debug info excluded in production (security)

### AC-3: Request/Response Logging
- [ ] Log all AI requests with:
  - Indicator type
  - Provider used
  - Request hash
  - Timestamp
- [ ] Log all AI responses with:
  - Success/failure status
  - Latency in milliseconds
  - Token usage (if available)
  - Error message (if failed)

### AC-4: Log Storage
- [ ] Logs written to `storage/logs/ai-suggestions.log`
- [ ] Separate log channel for AI suggestions
- [ ] Log rotation configured (daily, keep 30 days)

### AC-5: Performance Metrics
- [ ] Track average response time per provider
- [ ] Track success rate per provider
- [ ] Track token usage over time

### AC-6: Privacy in Logs
- [ ] No API keys in logs (masked)
- [ ] User PII excluded from logs
- [ ] URLs anonymized in logs

---

## Dependencies
- TASK-21-1: AI Service Structure (Done)
- TASK-21-5: AI Suggestion API Endpoint (Done)

---

## Effort Estimate
**Medium** (2-3 hours)

---

## Technical Notes

### Debug Response Structure
```json
{
    "success": true,
    "data": { /* suggestions */ },
    "debug": {
        "prompt": "Full prompt text...",
        "raw_response": "Raw JSON from provider...",
        "tokens_used": 245,
        "latency_ms": 1250,
        "provider": "openrouter",
        "model": "llama-3.1-8b-instruct"
    }
}
```

### Log Channel Configuration
```php
// config/logging.php
'channels' => [
    'ai-suggestions' => [
        'driver' => 'daily',
        'path' => storage_path('logs/ai-suggestions.log'),
        'level' => 'debug',
        'days' => 30,
    ],
],
```

---

## Comments
_Agent log_

### [2026-03-04 13:35 GMT+2] Agent: ProjectManager
Task file created from SPEC-021 specification. Previously empty file (0 bytes).
This task was never properly created by SpecSystemAnalyst - file existed but had no content.
Status set to `To Prioritize` for normal workflow processing.

### [2026-03-04 14:35 GMT+2] Agent: ProjectManager
**Prioritization Complete**

**Priority:** Medium (confirmed) - Enhancement task, not on critical path
- Debug mode is developer productivity tool, not foundation infrastructure
- AI suggestions work without debug mode (TASK-21-5 is functional)
- Can proceed in parallel with TASK-21-6 (Caching), TASK-21-9 (Vercel provider)

**Risk:** Low (confirmed) - Standard Laravel patterns, no external dependencies
- No external APIs (logs to local storage)
- No security-critical code (debug mode controlled by env variable)
- Privacy protections built into requirements (API key masking, no PII)

**Dependencies:** Both satisfied ✅
- TASK-21-1 (AI Service Structure): Done ✅
- TASK-21-5 (AI Suggestion API Endpoint): Done ✅

**TDD Ready:** PreTester can design tests immediately using:
- Existing AIProviderInterface from TASK-21-1
- Existing AISuggestionController from TASK-21-5
- Log channel configuration patterns

**Execution Order:** Phase 2 enhancement task - can proceed in parallel with TASK-21-6, TASK-21-9
**Status:** `To Write Tests`

---
*Created from SPEC-021 on 2026-03-04*
