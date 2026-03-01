# TASK-21-2: Implement OpenRouter Provider

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`High`

## RISK
`High`

## Specification
[SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

## Description
Implement the OpenRouter API provider for AI suggestions. OpenRouter provides access to free models like Llama 3.1 and Gemma 2, making it the recommended default provider for this feature.

---

## Acceptance Criteria

### AC-1: OpenRouterProvider Class Created
- [ ] File exists at `backend/app/Services/AI/Providers/OpenRouterProvider.php`
- [ ] Implements `AIProviderInterface`
- [ ] Constructor accepts configuration array
- [ ] Proper dependency injection support

### AC-2: API Integration
- [ ] `generate()` method makes HTTP POST to `https://openrouter.ai/api/v1/chat/completions`
- [ ] Authorization header: `Bearer {api_key}`
- [ ] Required headers: `HTTP-Referer`, `X-Title` (OpenRouter requirements)
- [ ] Request body includes: `model`, `messages`, `max_tokens`, `temperature`
- [ ] Configurable timeout (default 30s)

### AC-3: Response Handling
- [ ] Parses JSON response correctly
- [ ] Extracts `choices[0].message.content`
- [ ] Captures token usage from `usage` field
- [ ] Handles streaming vs non-streaming (non-streaming only for MVP)
- [ ] Returns `AIResponse` DTO

### AC-4: Error Handling
- [ ] Handles HTTP errors (4xx, 5xx)
- [ ] Handles timeout gracefully
- [ ] Handles malformed JSON responses
- [ ] Handles rate limiting (429) with retry-after header
- [ ] Returns `AIResponse` with `success=false` on errors

### AC-5: Availability Check
- [ ] `isAvailable()` checks API key is configured
- [ ] Optional: Ping OpenRouter status endpoint
- [ ] Returns boolean

### AC-6: Configuration
- [ ] Reads from `config('ai.providers.openrouter')`
- [ ] Supports environment variables:
  - `OPENROUTER_API_KEY`
  - `OPENROUTER_MODEL` (default: `meta-llama/llama-3.1-8b-instruct:free`)
  - `OPENROUTER_BASE_URL`
- [ ] Validates required configuration on instantiation

### AC-7: Unit Tests
- [ ] Test file at `backend/tests/Unit/Services/AI/Providers/OpenRouterProviderTest.php`
- [ ] Tests for successful generation (mocked HTTP)
- [ ] Tests for error handling (4xx, 5xx, timeout)
- [ ] Tests for availability check
- [ ] Tests for configuration validation
- [ ] All tests passing

### AC-8: Feature Tests
- [ ] Test file at `backend/tests/Feature/Services/AI/OpenRouterProviderIntegrationTest.php`
- [ ] Uses HTTP fake for API calls
- [ ] Tests full request/response cycle
- [ ] All tests passing

---

## Dependencies
- TASK-21-1 (AI Service Structure and Interfaces)

---

## Effort Estimate
**Medium** (4-6 hours)

- Provider implementation: 2 hours
- Error handling: 1.5 hours
- Tests: 2.5 hours

---

## Technical Notes

### Free Models Available
| Model ID | Context | Notes |
|----------|---------|-------|
| `meta-llama/llama-3.1-8b-instruct:free` | 128K | Recommended default |
| `google/gemma-2-9b-it:free` | 8K | Alternative |
| `qwen/qwen-2-7b-instruct:free` | 32K | Alternative |

### Request Format
```json
{
  "model": "meta-llama/llama-3.1-8b-instruct:free",
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
  "id": "gen-...",
  "choices": [{
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

---

## Comments
_Agent log_

### [2026-03-01 18:40 GMT+2] ProjectManager - Prioritization Complete
**Priority Confirmed: High** ✅
**Risk Confirmed: High** ✅

**Rationale for Priority:**
- Primary AI provider for SPEC-021 (OpenRouter is the recommended default)
- Enables actual AI-powered suggestions - core value of the feature
- Phase 1 critical path - blocks TASK-21-5 (API Endpoint) and downstream tasks
- Free models available (Llama 3.1, Gemma 2) - complies with CONSTITUTION.md

**Rationale for Risk:**
- External API dependency (OpenRouter availability, rate limits)
- HTTP client implementation with timeout handling
- Error handling for 4xx/5xx responses, malformed JSON, rate limiting (429)
- Response parsing with validation
- Recommended models use `:free` suffix which may have availability variability

**Dependency Analysis:**
- TASK-21-1 (AI Service Structure): `To Test Locally` ✅
  - Interface `AIProviderInterface` is defined
  - DTOs `AIResponse` is available for return type
  - Configuration structure in `config/ai.php` is ready
- Can proceed with test design - interfaces are available

**Execution Order within SPEC-021:**
1. TASK-21-1: `To Test Locally` (nearly done)
2. TASK-21-2 (this), TASK-21-3, TASK-21-4: Can run in parallel
3. TASK-21-5: After all providers are ready

**Handoff to PreTester:** Ready for test design. Focus on:
- HTTP client mocking for OpenRouter API (`/api/v1/chat/completions`)
- Authorization header format: `Bearer {api_key}`
- Required headers: `HTTP-Referer`, `X-Title`
- Response parsing: `choices[0].message.content`, `usage` field
- Error scenarios: 4xx, 5xx, timeout, malformed JSON, rate limiting (429)
- Configuration validation: `OPENROUTER_API_KEY`, `OPENROUTER_MODEL`

Status: `To Prioritize` → `To Write Tests`

---
*Created by SpecSystemAnalyst on 2026-03-01*
