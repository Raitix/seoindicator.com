# TEST-21-9: Vercel AI Provider Tests

## Task Reference
- **Task:** [TASK-21-9](../TASKS/TASK-21-9.md)
- **Specification:** [SPEC-021](../SPECIFICATIONS/SPEC-021.md)
- **Reference Implementation:** [OpenRouterProviderTest.php](../backend/tests/Unit/Services/AI/Providers/OpenRouterProviderTest.php)

---

## Overview

Comprehensive unit test suite for `VercelAIProvider` class. Tests cover:
- Class creation and interface implementation
- API integration (with HTTP mocking)
- Response handling and parsing
- Error handling for all HTTP error codes
- Availability check logic
- Configuration validation
- Free tier compliance

---

## Test Files

| File | Location | Tests |
|------|----------|-------|
| `VercelAIProviderTest.php` | `backend/tests/Unit/Services/AI/Providers/` | Unit tests |

---

## Test Scenarios

### AC-1: VercelAIProvider Class Created

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VRC-001 | Class exists at correct path | N/A | `class_exists(VercelAIProvider::class)` returns true |
| VRC-002 | Implements AIProviderInterface | `new VercelAIProvider(['api_key' => 'test'])` | Instance of `AIProviderInterface` |
| VRC-003 | Constructor accepts config array | Config array with api_key | No exception thrown |
| VRC-004 | Constructor validates required config (missing api_key) | `['model' => 'gpt-4o-mini']` | `InvalidArgumentException` with 'api_key' message |
| VRC-005 | Constructor sets default model | `['api_key' => 'test']` | `getModel()` returns 'gpt-4o-mini' |
| VRC-006 | Constructor sets default timeout | `['api_key' => 'test']` | `getTimeout()` returns 30 |
| VRC-007 | Constructor sets custom timeout | `['api_key' => 'test', 'timeout' => 60]` | `getTimeout()` returns 60 |
| VRC-008 | Dependency injection support | Container binding | Provider resolves from container |

### AC-2: Configuration Support

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CFG-001 | VERCEL_AI_API_KEY env variable supported | Config with api_key | Provider uses api_key |
| CFG-002 | VERCEL_AI_MODEL env variable supported | Config with model | `getModel()` returns configured model |
| CFG-003 | Default model when not specified | No model in config | Returns 'gpt-4o-mini' |
| CFG-004 | Custom model override | `['api_key' => 'test', 'model' => 'claude-3-haiku']` | `getModel()` returns 'claude-3-haiku' |
| CFG-005 | Timeout configurable via AI_TIMEOUT_SECONDS | Config with timeout | Uses configured timeout |
| CFG-006 | Provider registered in config/ai.php | N/A | Config file has 'vercel' provider entry |

### AC-3: API Integration (Unit Tests with HTTP Mocking)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| API-001 | Generate makes POST request | Mock HTTP response | POST request made to Vercel API |
| API-002 | Authorization header format | Mock HTTP response | `Bearer` header with API key |
| API-003 | Request body includes model | Mock HTTP response | `model` field in request body |
| API-004 | Request body includes messages | Mock HTTP response | `messages` array in request body |
| API-005 | Request body includes max_tokens | Mock HTTP response | `max_tokens` field present |
| API-006 | Request body includes temperature | Mock HTTP response | `temperature` field present |
| API-007 | Custom max_tokens override | `['max_tokens' => 500]` option | Request uses 500 |
| API-008 | Custom temperature override | `['temperature' => 0.5]` option | Request uses 0.5 |
| API-009 | Configurable timeout (default) | No timeout config | 30 second timeout |
| API-010 | Configurable timeout (custom) | `timeout: 60` in config | 60 second timeout |
| API-011 | System message in request (optional) | Config with system_prompt | `role: system` message present |
| API-012 | User message in request | Prompt 'test prompt' | `role: user` message with content |

### AC-4: Response Parsing

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RES-001 | Parse successful JSON response | Valid Vercel response | `success: true` in AIResponse |
| RES-002 | Extract content from choices | Response with choices | Content extracted from first choice |
| RES-003 | Extract prompt tokens | Response with usage | `tokensUsed` populated |
| RES-004 | Extract completion tokens | Response with completion_tokens | Total tokens calculated |
| RES-005 | Calculate total tokens | Response with token counts | `tokensUsed` equals total |
| RES-006 | Response latency tracking | Any response | `latencyMs` populated, >= 0 |
| RES-007 | Handle non-streaming response | Standard response | Success with content |
| RES-008 | Handle empty content | Response with empty string | Success, empty content |
| RES-009 | Handle null content gracefully | Response with null content | Success, null content |
| RES-010 | JSON response with special chars | Response with unicode/emoji | Content preserved exactly |

### AC-5: Error Handling

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERR-001 | HTTP 400 Bad Request | Mock 400 response | `success: false`, error message contains 400 |
| ERR-002 | HTTP 401 Unauthorized | Mock 401 response | `success: false`, error mentions invalid/unauthorized |
| ERR-003 | HTTP 403 Forbidden | Mock 403 response | `success: false`, error mentions denied |
| ERR-004 | HTTP 404 Not Found | Mock 404 response | `success: false`, error message present |
| ERR-005 | HTTP 429 Rate Limited | Mock 429 response | `success: false`, error mentions rate limit |
| ERR-006 | HTTP 500 Server Error | Mock 500 response | `success: false`, error mentions server |
| ERR-007 | HTTP 502 Bad Gateway | Mock 502 response | `success: false`, error message present |
| ERR-008 | HTTP 503 Service Unavailable | Mock 503 response | `success: false`, error mentions unavailable |
| ERR-009 | Request timeout | Mock connection failure | `success: false`, error mentions timeout |
| ERR-010 | Connection refused | Mock connection failure | `success: false`, error message present |
| ERR-011 | Malformed JSON response | Invalid JSON body | `success: false`, error mentions invalid |
| ERR-012 | Empty response body | Empty string response | `success: false`, error mentions empty |
| ERR-013 | Missing choices array | Response without choices | `success: false`, error mentions format |
| ERR-014 | Empty choices array | `choices: []` response | `success: false`, error message present |
| ERR-015 | Missing message in choice | Choice without message | `success: false`, error mentions format |

### AC-5: Provider Selection

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEL-001 | Can be selected via AI_PROVIDER=vercel | N/A | Provider configurable |
| SEL-002 | Works as fallback to OpenRouter | N/A | Can be used as secondary |
| SEL-003 | Supports both primary and secondary roles | N/A | Flexible role assignment |

### AC-6: Free Tier Compliance

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FTC-001 | Works with Vercel free tier | Free tier API key | Provider functions |
| FTC-002 | Documented free tier limitations | N/A | Code comments document limits |
| FTC-003 | Graceful degradation when limits hit | Rate limit response | Error response, no crash |
| FTC-004 | Free tier models work | gpt-4o-mini, claude-3-haiku | Models accepted |

### Availability Check

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AVL-001 | API key configured | Valid api_key | `isAvailable()` returns true |
| AVL-002 | API key missing | No api_key | `InvalidArgumentException` thrown |
| AVL-003 | API key is null | `api_key: null` | `InvalidArgumentException` thrown |
| AVL-004 | API key is empty string | `api_key: ''` | `isAvailable()` returns false |
| AVL-005 | API key is whitespace | `api_key: '   '` | `isAvailable()` returns false |
| AVL-006 | Provider name returns correctly | Valid config | `getName()` returns 'vercel' |

---

## Mock Data

### Successful Vercel AI Response
```json
{
  "id": "gen-test123",
  "choices": [
    {
      "message": {
        "role": "assistant",
        "content": "This is a test response."
      },
      "finish_reason": "stop"
    }
  ],
  "usage": {
    "prompt_tokens": 150,
    "completion_tokens": 200,
    "total_tokens": 350
  }
}
```

### Vercel AI Error Response
```json
{
  "error": {
    "message": "Rate limit exceeded",
    "type": "rate_limit_error",
    "code": "rate_limit_exceeded"
  }
}
```

### Free Tier Models Configuration
```php
$freeTierModels = [
    'gpt-4o-mini',      // Default, recommended
    'claude-3-haiku',   // Alternative
];
```

---

## Coverage Targets

| Category | Target | Notes |
|----------|--------|-------|
| Class Instantiation | 100% | Constructor, config validation |
| Interface Implementation | 100% | All interface methods |
| API Integration | 100% | HTTP mocking for all scenarios |
| Response Parsing | 100% | All response formats |
| Error Handling | 100% | All HTTP codes, edge cases |
| Availability | 100% | All availability scenarios |
| Configuration | 100% | All config options |

**Minimum Overall Coverage:** 80%

---

## Test Execution

```bash
# Run all VercelAIProvider tests
cd backend
php artisan test --filter=VercelAIProvider

# Run specific test file
php artisan test tests/Unit/Services/AI/Providers/VercelAIProviderTest.php

# Run with coverage
php artisan test --coverage --filter=VercelAIProvider
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-21-1 | Done | AIProviderInterface, AIResponse |
| TASK-21-2 | Done | Reference implementation (OpenRouter) |
| PHPUnit | Installed | Testing framework |
| Laravel HTTP Client | Available | For mocking |

---

## Notes for Developer

1. **Use OpenRouterProvider as reference** - Same interface, similar patterns
2. **HTTP mocking** - Use `Http::fake()` for all API tests
3. **Vercel API format** - Similar to OpenAI format (choices array, usage object)
4. **Free tier models** - gpt-4o-mini is default, claude-3-haiku is alternative
5. **Error handling** - Follow same patterns as OpenRouterProvider
6. **Latency tracking** - Must track response time in milliseconds

### Vercel AI API Differences from OpenRouter

| Aspect | OpenRouter | Vercel AI |
|--------|------------|-----------|
| Base URL | `openrouter.ai/api/v1` | `api.vercel.ai/v1` (or SDK) |
| Auth Header | `Authorization: Bearer` | Same |
| Required Headers | `HTTP-Referer`, `X-Title` | None required |
| Default Model | `llama-3.1-8b-instruct:free` | `gpt-4o-mini` |

---

## Comments
_Agent log_

### [2026-03-04 16:35 GMT+2] Agent: PreTester
Test documentation created. Following OpenRouterProvider test patterns with adaptations for Vercel AI API format.

**Key Testing Focus:**
1. HTTP client mocking for Vercel AI API
2. Configuration validation (api_key, model, timeout)
3. Response parsing from Vercel format (same as OpenAI format)
4. Error handling for all HTTP codes
5. Free tier model support
6. Availability check logic

Status: `To Write Tests` → `Tests Coverage In Progress`
