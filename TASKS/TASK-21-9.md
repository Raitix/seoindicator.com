# TASK-21-9: Implement Vercel AI Provider

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`High`

## Specification
[SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

## Description
Implement an alternative AI provider using Vercel AI Gateway. This provides redundancy and options for users who prefer Vercel's ecosystem or need access to different models.

---

## Acceptance Criteria

### AC-1: VercelAIProvider Class Created
- [ ] File exists at `backend/app/Services/AI/Providers/VercelAIProvider.php`
- [ ] Implements `AIProviderInterface`
- [ ] Constructor accepts configuration array
- [ ] Proper dependency injection support

### AC-2: API Integration
- [ ] Supports Vercel AI SDK format
- [ ] Configurable base URL for Vercel AI Gateway
- [ ] Authorization via API key header
- [ ] Request body includes: `model`, `messages`, `max_tokens`, `temperature`
- [ ] Configurable timeout (default 30s)

### AC-3: Supported Models
- [ ] `gpt-4o-mini` (default)
- [ ] `gpt-3.5-turbo`
- [ ] `claude-3-haiku` (if available via Vercel)
- [ ] Configurable via `VERCEL_AI_MODEL` env variable

### AC-4: Response Handling
- [ ] Parses JSON response correctly
- [ ] Extracts message content
- [ ] Captures token usage
- [ ] Returns `AIResponse` DTO

### AC-5: Error Handling
- [ ] Handles HTTP errors (4xx, 5xx)
- [ ] Handles timeout gracefully
- [ ] Handles malformed responses
- [ ] Handles rate limiting (429)
- [ ] Returns `AIResponse` with `success=false` on errors

### AC-6: Availability Check
- [ ] `isAvailable()` checks API key is configured
- [ ] Returns boolean

### AC-7: Configuration
- [ ] Reads from `config('ai.providers.vercel')`
- [ ] Supports environment variables:
  - `VERCEL_AI_API_KEY`
  - `VERCEL_AI_MODEL` (default: `gpt-4o-mini`)
- [ ] Validates required configuration on instantiation

### AC-8: Fallback Integration
- [ ] AISuggestionService can switch between providers
- [ ] Vercel can be configured as secondary provider
- [ ] Graceful fallback to OpenRouter or static

### AC-9: Unit Tests
- [ ] Test file at `backend/tests/Unit/Services/AI/Providers/VercelAIProviderTest.php`
- [ ] Tests for successful generation (mocked HTTP)
- [ ] Tests for error handling
- [ ] Tests for availability check
- [ ] Tests for configuration validation
- [ ] All tests passing

### AC-10: Feature Tests
- [ ] Test file at `backend/tests/Feature/Services/AI/VercelAIProviderIntegrationTest.php`
- [ ] Uses HTTP fake for API calls
- [ ] Tests full request/response cycle
- [ ] All tests passing

---

## Dependencies
- TASK-21-1 (AI Service Structure and Interfaces)
- TASK-21-5 (AI Suggestion API Endpoint)

---

## Effort Estimate
**Medium** (4-5 hours)

- Provider implementation: 2 hours
- Error handling: 1 hour
- Tests: 2 hours

---

## Technical Notes

### Request Format
```json
POST https://api.vercel.ai/v1/chat/completions
Authorization: Bearer <api_key>

{
  "model": "gpt-4o-mini",
  "messages": [
    {"role": "system", "content": "..."},
    {"role": "user", "content": "..."}
  ],
  "max_tokens": 1000,
  "temperature": 0.7
}
```

### Response Format
```json
{
  "id": "chatcmpl-...",
  "object": "chat.completion",
  "choices": [{
    "index": 0,
    "message": {
      "role": "assistant",
      "content": "{...json response...}"
    },
    "finish_reason": "stop"
  }],
  "usage": {
    "prompt_tokens": 150,
    "completion_tokens": 200,
    "total_tokens": 350
  }
}
```

### Configuration
```php
// config/ai.php
'vercel' => [
    'api_key' => env('VERCEL_AI_API_KEY'),
    'base_url' => env('VERCEL_AI_BASE_URL', 'https://api.vercel.ai/v1'),
    'model' => env('VERCEL_AI_MODEL', 'gpt-4o-mini'),
    'timeout' => env('AI_TIMEOUT_SECONDS', 30),
],
```

---

## Comments
_Agent log_

### [2026-03-02 11:53 GMT+2] ProjectManager - Prioritization Complete
**Priority Confirmed: Medium** ✅
**Risk Confirmed: High** ✅

**Rationale for Priority:**
- **Phase 3 Enhancement** - Alternative provider, not on critical path
- **Core functionality already works** - OpenRouter + Static Fallback are functional
- **Redundancy/Options** - Provides users who prefer Vercel's ecosystem an alternative
- **Can be deferred** - Feature is not blocked if this task is delayed

**Rationale for Risk:**
- **High Risk** - External API dependency (similar to OpenRouter)
- Rate limits, availability, API key management
- Response parsing complexity
- Potential costs if free tier limits exceeded

**Dependency Analysis:**
| Task | Status | Ready? |
|------|--------|--------|
| TASK-21-1 (AI Service Structure) | `Done` | ✅ |
| TASK-21-5 (AI Suggestion API Endpoint) | `Done` | ✅ |

**Both dependencies complete!** Task can proceed to PreTester immediately.

**Execution Order within SPEC-021:**
- This task is independent of Phase 1/2 core implementation
- Can be developed in parallel with TASK-21-6, 21-7, 21-8
- After TASK-21-10 (Rate Limiting) would be ideal for consistency

**Technical Notes:**
- Implements `AIProviderInterface` from TASK-21-1
- Uses same patterns as OpenRouterProvider (TASK-21-2)
- Configuration already scaffolded in `config/ai.php`
- Requires `VERCEL_AI_API_KEY` environment variable

**Handoff to PreTester:** Ready for test design. Focus on:
- VercelAIProvider implementing AIProviderInterface
- HTTP client mocking for Vercel AI API format
- Error handling (4xx, 5xx, timeout, rate limiting)
- Configuration validation
- `isAvailable()` check for API key presence

Status: `To Prioritize` → `To Write Tests`

---
*Created by SpecSystemAnalyst on 2026-03-01*
