# TASK-21-2: Implement OpenRouter Provider

## STATUS
`To Test Locally`

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
- [x] File exists at `backend/app/Services/AI/Providers/OpenRouterProvider.php`
- [x] Implements `AIProviderInterface`
- [x] Constructor accepts configuration array
- [x] Proper dependency injection support

### AC-2: API Integration
- [x] `generate()` method makes HTTP POST to `https://openrouter.ai/api/v1/chat/completions`
- [x] Authorization header: `Bearer {api_key}`
- [x] Required headers: `HTTP-Referer`, `X-Title` (OpenRouter requirements)
- [x] Request body includes: `model`, `messages`, `max_tokens`, `temperature`
- [x] Configurable timeout (default 30s)

### AC-3: Response Handling
- [x] Parses JSON response correctly
- [x] Extracts `choices[0].message.content`
- [x] Captures token usage from `usage` field
- [x] Handles streaming vs non-streaming (non-streaming only for MVP)
- [x] Returns `AIResponse` DTO

### AC-4: Error Handling
- [x] Handles HTTP errors (4xx, 5xx)
- [x] Handles timeout gracefully
- [x] Handles malformed JSON responses
- [x] Handles rate limiting (429) with retry-after header
- [x] Returns `AIResponse` with `success=false` on errors

### AC-5: Availability Check
- [x] `isAvailable()` checks API key is configured
- [ ] Optional: Ping OpenRouter status endpoint
- [x] Returns boolean

### AC-6: Configuration
- [x] Reads from `config('ai.providers.openrouter')`
- [x] Supports environment variables:
  - `OPENROUTER_API_KEY`
  - `OPENROUTER_MODEL` (default: `meta-llama/llama-3.1-8b-instruct:free`)
  - `OPENROUTER_BASE_URL`
- [x] Validates required configuration on instantiation

### AC-7: Unit Tests
- [x] Test file at `backend/tests/Unit/Services/AI/Providers/OpenRouterProviderTest.php`
- [x] Tests for successful generation (mocked HTTP)
- [x] Tests for error handling (4xx, 5xx, timeout)
- [x] Tests for availability check
- [x] Tests for configuration validation
- [x] All tests passing

### AC-8: Feature Tests
- [x] Test file at `backend/tests/Feature/Services/AI/OpenRouterProviderIntegrationTest.php`
- [x] Uses HTTP fake for API calls
- [x] Tests full request/response cycle
- [x] All tests passing

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

### [2026-03-01 18:55 GMT+2] PreTester - Test Suite Created
**Test Design Complete** ✅

**Test Files Created:**
1. `TESTS/TEST-21-2.md` - Test documentation with 77 test scenarios
2. `backend/tests/Unit/Services/AI/Providers/OpenRouterProviderTest.php` - 45 unit tests
3. `backend/tests/Feature/Services/AI/OpenRouterProviderIntegrationTest.php` - 25 feature tests

**Acceptance Criteria Test Coverage:**
| AC | Tests | Status |
|----|-------|--------|
| AC-1: Class Created | 8 | ✅ |
| AC-2: API Integration | 14 | ✅ |
| AC-3: Response Handling | 10 | ✅ |
| AC-4: Error Handling | 15 | ✅ |
| AC-5: Availability Check | 6 | ✅ |
| AC-6: Configuration | 9 | ✅ |
| AC-7: Unit Tests | 45 | ✅ |
| AC-8: Feature Tests | 15 | ✅ |
| Security Tests | 5 | ✅ |

**Test Categories:**
- **ORC-***: OpenRouterProvider class creation (8 tests)
- **API-***: API integration with HTTP mocking (14 tests)
- **RES-***: Response handling and parsing (10 tests)
- **ERR-***: Error handling for all HTTP codes (15 tests)
- **AVL-***: Availability check logic (6 tests)
- **CFG-***: Configuration validation (9 tests)
- **FEA-***: Feature/integration tests (15 tests)
- **SEC-***: Security tests (5 tests)

**TDD Note:** Tests will fail until implementation is complete. This is expected behavior.

**Handoff to Developer:** All tests are ready. Implementation should make tests pass.

Status: `To Write Tests` → `To Develop`

---
*Created by SpecSystemAnalyst on 2026-03-01*
