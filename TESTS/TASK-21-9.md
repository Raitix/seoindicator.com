# TESTS/TASK-21-9.md — VercelAIProvider Tests

## Task Reference
- **Task:** [TASK-21-9](../TASKS/TASK-21-9.md)
- **Specification:** [SPEC-021](../SPECIFICATIONS/SPEC-021.md)
- **Agent:** PreTester

---

## Overview

Comprehensive test suite for `VercelAIProvider` implementing the `AIProviderInterface`. Tests verify API integration, response handling, error management, configuration, and security.

---

## Test Files

| File | Type | Tests | Status |
|------|------|-------|--------|
| `backend/tests/Unit/Services/AI/Providers/VercelAIProviderTest.php` | Unit | 75 | Pending |
| `backend/tests/Feature/Services/AI/VercelAIProviderIntegrationTest.php` | Feature | 15 | Pending |

**Total Tests:** 90

---

## Test Scenarios

### Unit Tests (VercelAIProviderTest.php)

#### AC-1: VercelAIProvider Class Created (8 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ORC-001 | Class exists at correct path | — | `class_exists()` returns true |
| ORC-002 | Implements AIProviderInterface | new instance | Instance of `AIProviderInterface` |
| ORC-003 | Constructor accepts config array | config array | Instance created |
| ORC-004 | Constructor validates required config | missing api_key | `InvalidArgumentException` |
| ORC-005 | Constructor sets default model | api_key only | model = `gpt-4o-mini` |
| ORC-006 | Constructor sets default timeout | api_key only | timeout = 30 |
| ORC-007 | Constructor sets custom timeout | timeout: 60 | timeout = 60 |
| ORC-008 | Dependency injection support | container bind | Instance resolved |

#### AC-2: API Integration (14 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| API-001 | Generate makes POST request | valid prompt | POST to `/chat/completions` |
| API-002 | Authorization header format | api_key | `Authorization: Bearer <key>` |
| API-003 | Request body includes model | custom model | `model: gpt-4o-mini` |
| API-004 | Request body includes messages | prompt | `messages: [{role, content}]` |
| API-005 | Request body includes max_tokens | default | `max_tokens: 1000` |
| API-006 | Request body includes temperature | default | `temperature: 0.7` |
| API-007 | Custom max_tokens override | options | `max_tokens: 500` |
| API-008 | Custom temperature override | options | `temperature: 0.5` |
| API-009 | Configurable timeout default | — | 30 seconds |
| API-010 | Configurable timeout custom | config | 60 seconds |
| API-011 | System message in request | system_prompt | `role: system` message |
| API-012 | User message in request | prompt | `role: user` message |
| API-013 | Configurable base URL | base_url | URL used in request |
| API-014 | Default base URL | — | `https://api.vercel.ai/v1` |

#### AC-3: Supported Models (4 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MOD-001 | Default model is gpt-4o-mini | no config | model = `gpt-4o-mini` |
| MOD-002 | Custom model from config | model: gpt-3.5-turbo | model = `gpt-3.5-turbo` |
| MOD-003 | Claude model support | model: claude-3-haiku | model = `claude-3-haiku` |
| MOD-004 | Model via env variable | VERCEL_AI_MODEL | Uses env value |

#### AC-4: Response Handling (10 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RES-001 | Parse successful JSON response | valid response | `success: true` |
| RES-002 | Extract content from choices | response with content | content extracted |
| RES-003 | Extract prompt tokens | usage data | tokens tracked |
| RES-004 | Extract completion tokens | usage data | tokens tracked |
| RES-005 | Calculate total tokens | usage data | `tokensUsed: total` |
| RES-006 | Response latency tracking | any response | `latencyMs: number` |
| RES-007 | Handle non-streaming response | regular response | works correctly |
| RES-008 | Handle empty content | content: "" | `success: true`, content: "" |
| RES-009 | Handle null content gracefully | content: null | `success: true`, content: null |
| RES-010 | JSON response with special chars | unicode/emoji | content preserved |

#### AC-5: Error Handling (15 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ERR-001 | HTTP 400 Bad Request | 400 response | `success: false`, error message |
| ERR-002 | HTTP 401 Unauthorized | 401 response | `success: false`, "Invalid API key" |
| ERR-003 | HTTP 403 Forbidden | 403 response | `success: false`, "Access denied" |
| ERR-004 | HTTP 404 Not Found | 404 response | `success: false`, error message |
| ERR-005 | HTTP 429 Rate Limited | 429 response | `success: false`, "Rate limit" |
| ERR-006 | HTTP 500 Server Error | 500 response | `success: false`, "Server error" |
| ERR-007 | HTTP 502 Bad Gateway | 502 response | `success: false`, error message |
| ERR-008 | HTTP 503 Service Unavailable | 503 response | `success: false`, "unavailable" |
| ERR-009 | Request timeout | connection fail | `success: false`, "timeout" |
| ERR-010 | Connection refused | connection fail | `success: false`, error message |
| ERR-011 | Malformed JSON response | invalid JSON | `success: false`, "Invalid JSON" |
| ERR-012 | Empty response body | empty body | `success: false`, "Empty response" |
| ERR-013 | Missing choices array | no choices | `success: false`, "Invalid format" |
| ERR-014 | Empty choices array | choices: [] | `success: false`, "No response" |
| ERR-015 | Missing message in choice | no message | `success: false`, "Invalid format" |

#### AC-6: Availability Check (6 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AVL-001 | API key configured | api_key: "key" | `isAvailable(): true` |
| AVL-002 | API key missing | no api_key | `InvalidArgumentException` |
| AVL-003 | API key is null | api_key: null | `InvalidArgumentException` |
| AVL-004 | API key is empty string | api_key: "" | `isAvailable(): false` |
| AVL-005 | API key is whitespace | api_key: "   " | `isAvailable(): false` |
| AVL-006 | Provider name returns correctly | — | `getName(): "vercel"` |

#### AC-7: Configuration (8 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CFG-001 | Reads from config array | config values | Values applied |
| CFG-002 | Environment variable VERCEL_AI_API_KEY | env var | Key loaded |
| CFG-003 | Environment variable VERCEL_AI_MODEL | env var | Model loaded |
| CFG-004 | Environment variable VERCEL_AI_BASE_URL | env var | URL loaded |
| CFG-005 | Default model when not specified | no model | `gpt-4o-mini` |
| CFG-006 | Validates timeout is positive | timeout: -10 | Uses default: 30 |
| CFG-007 | Validates max_tokens is positive | max_tokens: 0 | Uses default: 1000 |
| CFG-008 | Validates base URL format | invalid URL | Uses default URL |

#### AC-8: Fallback Integration (4 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FLB-001 | AISuggestionService can use Vercel | service config | Provider switchable |
| FLB-002 | Vercel as secondary provider | config | Fallback works |
| FLB-003 | Graceful fallback to OpenRouter | Vercel fails | OpenRouter used |
| FLB-004 | Graceful fallback to static | all AI fails | Static used |

#### Security Tests (6 tests)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-001 | API key not logged | any request | Key not in logs |
| SEC-002 | Prompt injection handled | malicious prompt | Safe processing |
| SEC-003 | Response XSS attempt | `<script>` content | Content returned as-is |
| SEC-004 | Invalid URL injection | malicious base_url | Default URL used |
| SEC-005 | SQL injection in prompt | `'; DROP TABLE` | Safe processing |
| SEC-006 | SSRF prevention | localhost URL | Request blocked or safe |

---

### Feature Tests (VercelAIProviderIntegrationTest.php)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FEA-001 | Full request/response cycle | valid request | Complete response |
| FEA-002 | Request headers are set | any request | Correct headers |
| FEA-003 | Request body structure | any request | Valid JSON body |
| FEA-004 | Response parsing | response | AIResponse DTO |
| FEA-005 | Error response handling | error response | Error AIResponse |
| FEA-006 | Timeout handling | slow response | Graceful timeout |
| FEA-007 | Rate limit response | 429 response | Error with message |
| FEA-008 | Multiple sequential requests | 2 requests | Both handled |
| FEA-009 | Large prompt handling | 12K+ chars | Processed correctly |
| FEA-010 | Unicode prompt handling | Chinese/emoji | Unicode preserved |

---

## Mock Data

### Successful Vercel AI Response
```json
{
  "id": "chatcmpl-abc123",
  "object": "chat.completion",
  "choices": [{
    "index": 0,
    "message": {
      "role": "assistant",
      "content": "{\"score\": 85, \"suggestions\": []}"
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

### Error Response
```json
{
  "error": {
    "message": "Invalid API key",
    "type": "invalid_request_error",
    "code": "invalid_api_key"
  }
}
```

### Configuration Mock
```php
$config = [
    'api_key' => 'test-vercel-key',
    'base_url' => 'https://api.vercel.ai/v1',
    'model' => 'gpt-4o-mini',
    'timeout' => 30,
    'max_tokens' => 1000,
];
```

---

## Coverage Targets

| Category | Target | Status |
|----------|--------|--------|
| Class Creation | 100% | Pending |
| API Integration | 100% | Pending |
| Response Handling | 100% | Pending |
| Error Handling | 100% | Pending |
| Availability Check | 100% | Pending |
| Configuration | 100% | Pending |
| Security | 100% | Pending |

---

## Test Execution

```bash
# Run all VercelAIProvider tests
cd backend
php artisan test --filter VercelAIProvider

# Run unit tests only
php artisan test tests/Unit/Services/AI/Providers/VercelAIProviderTest.php

# Run feature tests only
php artisan test tests/Feature/Services/AI/VercelAIProviderIntegrationTest.php

# Run with coverage
php artisan test --coverage --filter VercelAIProvider
```

---

## Dependencies

### Existing Files (to use)
- `app/Services/AI/Contracts/AIProviderInterface.php` — Interface to implement
- `app/Services/AI/DTOs/AIResponse.php` — Response DTO

### Files to Create by Developer
- `app/Services/AI/Providers/VercelAIProvider.php` — Main provider class

---

## Notes for Developer

1. **Follow OpenRouterProvider Pattern**: The VercelAIProvider should follow the same structure as OpenRouterProvider with these differences:
   - Default model: `gpt-4o-mini` (not Llama)
   - Base URL: `https://api.vercel.ai/v1`
   - Headers: Only `Authorization: Bearer <key>` (no HTTP-Referer or X-Title)
   - Provider name: `vercel`

2. **HTTP Mocking**: All tests use `Http::fake()` for mocking. Do not make real API calls in tests.

3. **Error Handling**: Return `AIResponse::error()` for all error cases, never throw exceptions to the caller.

4. **Configuration Validation**: Validate required config in constructor. Use defaults for optional values.

5. **Response Format**: Vercel AI uses OpenAI-compatible response format (same as OpenRouter).

---

## Comments

### [2026-03-02 17:35 GMT+2] PreTester - Tests Created
Created comprehensive test suite with 90 tests covering:
- AC-1: Class creation (8 tests)
- AC-2: API integration (14 tests)
- AC-3: Supported models (4 tests)
- AC-4: Response handling (10 tests)
- AC-5: Error handling (15 tests)
- AC-6: Availability check (6 tests)
- AC-7: Configuration (8 tests)
- AC-8: Fallback integration (4 tests)
- Security tests (6 tests)
- Feature tests (15 tests)

Test files created:
- `backend/tests/Unit/Services/AI/Providers/VercelAIProviderTest.php`
- `backend/tests/Feature/Services/AI/VercelAIProviderIntegrationTest.php`
- `TESTS/TASK-21-9.md` (this file)

**Next Steps for Developer:**
1. Create `VercelAIProvider.php` implementing `AIProviderInterface`
2. Follow the pattern from `OpenRouterProvider.php`
3. Run tests: `php artisan test --filter VercelAIProvider`
4. All tests should pass

Status: `Tests Coverage In Progress` → `To Develop`
