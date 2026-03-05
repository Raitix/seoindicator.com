# TEST-31-6: OpenRouter Client Service Tests

## Task Reference
- **Task:** [TASK-31-6](../TASKS/TASK-31-6.md)
- **Specification:** SPEC-31: Unified AI Prompt + Context Management
- **Feature:** FEAT-031

## Overview

Comprehensive unit test suite for the OpenRouterClient service. Tests cover API integration with HTTP mocking, response handling, error handling for all HTTP codes, timeout configuration, free-tier models support, and security measures.

## Test Files

| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `backend/tests/Unit/Services/AI/OpenRouterClientTest.php` | 80+ | 120+ | Ready for Implementation |

## Test Scenarios

### AC-1: Class Structure (ORC-001 to ORC-009)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| ORC-001 | Class exists at correct path | `class_exists()` returns true |
| ORC-002 | Constructor accepts config array | Instance created successfully |
| ORC-003 | Constructor validates api_key | InvalidArgumentException thrown |
| ORC-004 | Default model set | `google/gemini-2.0-flash-exp:free` |
| ORC-005 | Default timeout set | 120 seconds |
| ORC-006 | Custom timeout set | Configured value returned |
| ORC-007 | Default max_tokens set | 4000 |
| ORC-008 | Custom max_tokens set | Configured value returned |
| ORC-009 | Dependency injection support | Container resolves instance |

### AC-1: generate() Method - API Integration (GEN-001 to GEN-014)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| GEN-001 | POST request to correct endpoint | URL contains `/chat/completions` |
| GEN-002 | Authorization header format | `Bearer {api_key}` |
| GEN-003 | HTTP-Referer header present | Header exists |
| GEN-004 | X-Title header present | Header exists |
| GEN-005 | Request body includes model | Model in JSON body |
| GEN-006 | Request body includes messages | Messages array in body |
| GEN-007 | Request body includes max_tokens | max_tokens in body |
| GEN-008 | Request body includes temperature | temperature in body |
| GEN-009 | Request body includes response_format | `{type: "json_object"}` |
| GEN-010 | Custom max_tokens override | Option value used |
| GEN-011 | Custom temperature override | Option value used |
| GEN-012 | Custom model override | Option model used |
| GEN-013 | Custom timeout override | Timeout applied |
| GEN-014 | User message in request | role: "user", content: prompt |

### AC-3: Response Handling (RES-001 to RES-012)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| RES-001 | Parse successful JSON response | `success: true` |
| RES-002 | Extract content from choices | Content matches response |
| RES-003 | Extract total tokens | tokensUsed matches |
| RES-004 | Extract prompt tokens | promptTokens matches |
| RES-005 | Extract response tokens | responseTokens matches |
| RES-006 | Response latency tracking | latencyMs populated |
| RES-007 | Response includes model | model field populated |
| RES-008 | Handle non-streaming response | Success response |
| RES-009 | Handle empty content | Empty string, success true |
| RES-010 | Handle null content | Null content, success true |
| RES-011 | JSON with special chars | Content preserved exactly |
| RES-012 | AIResponse DTO returned | Correct class type |

### AC-4: Error Handling (ERR-001 to ERR-014)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| ERR-001 | HTTP 400 Bad Request | `success: false`, 400 in error |
| ERR-002 | HTTP 401 Unauthorized | `success: false`, 401 in error |
| ERR-003 | HTTP 403 Forbidden | `success: false`, 403 in error |
| ERR-004 | HTTP 429 Rate Limited | `success: false`, 429 in error |
| ERR-005 | HTTP 500 Server Error | `success: false`, 500 in error |
| ERR-006 | HTTP 502 Bad Gateway | `success: false`, 502 in error |
| ERR-007 | HTTP 503 Service Unavailable | `success: false`, 503 in error |
| ERR-008 | Request timeout | `success: false`, "timeout" in error |
| ERR-009 | Connection refused | `success: false`, error message |
| ERR-010 | Malformed JSON response | `success: false`, "invalid" in error |
| ERR-011 | Empty response body | `success: false`, "empty" in error |
| ERR-012 | Missing choices array | `success: false`, "format" in error |
| ERR-013 | Empty choices array | `success: false`, error message |
| ERR-014 | Missing message in choice | `success: false`, "format" in error |

### AC-5: Timeout Configuration (TMO-001 to TMO-005)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| TMO-001 | Default timeout 120s | `getTimeout()` returns 120 |
| TMO-002 | Custom timeout from config | Configured value returned |
| TMO-003 | Timeout applied to HTTP request | Request completes |
| TMO-004 | Negative timeout uses default | 120 returned |
| TMO-005 | Zero timeout uses default | 120 returned |

### AC-6: Free Models Support (MOD-001 to MOD-010)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| MOD-001 | getAvailableModels returns array | Array type |
| MOD-002 | Includes Gemini 2.0 Flash | Key exists in array |
| MOD-003 | Includes Qwen 2 7B | Key exists in array |
| MOD-004 | Includes Mistral 7B | Key exists in array |
| MOD-005 | Includes Llama 3 8B | Key exists in array |
| MOD-006 | Model info has name | Array key exists |
| MOD-007 | Model info has context_window | Array key exists |
| MOD-008 | getModelInfo returns details | Array with name, context_window |
| MOD-009 | getModelInfo returns null for unknown | null returned |
| MOD-010 | Gemini context window 1M | 1000000 value |

### stream() Method (STR-001 to STR-003)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| STR-001 | stream method exists | `method_exists()` true |
| STR-002 | stream accepts callable | No error thrown |
| STR-003 | stream calls callback | Callback invoked |

### Security Tests (SEC-001 to SEC-005)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SEC-001 | API key not in error messages | Key not in errorMessage |
| SEC-002 | XSS prevention | Content returned as-is |
| SEC-003 | Prompt injection handled | Request completes |
| SEC-004 | Invalid base URL rejected | Default URL used |
| SEC-005 | SQL injection safe | No DB interaction |

### Configuration Tests (CFG-001 to CFG-005)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| CFG-001 | Config array values used | All getters return config values |
| CFG-002 | Base URL configurable | Custom URL returned |
| CFG-003 | Default base URL | OpenRouter API URL |
| CFG-004 | Zero max_tokens uses default | 4000 returned |
| CFG-005 | Negative max_tokens uses default | 4000 returned |

## Mock Data

### Successful Response
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

### Error Response
```json
{
  "error": {
    "message": "Error description",
    "type": "invalid_request_error"
  }
}
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Class Structure | 100% | 9 |
| API Integration | 100% | 14 |
| Response Handling | 100% | 12 |
| Error Handling | 100% | 14 |
| Timeout Config | 100% | 5 |
| Free Models | 100% | 10 |
| Stream Method | 100% | 3 |
| Security | 100% | 5 |
| Configuration | 100% | 5 |
| **Total** | **100%** | **77+** |

## Test Execution

```bash
# Run all OpenRouterClient tests
cd backend
php artisan test --filter=OpenRouterClientTest

# Run specific test category
php artisan test --filter=OpenRouterClientTest::test_generate

# Run with coverage
php artisan test --filter=OpenRouterClientTest --coverage
```

## Dependencies

- TASK-21-2 (OpenRouter Provider - patterns reference)
- Laravel HTTP Client
- PHPUnit

## Notes for Developer

### Implementation Checklist
1. Create `backend/app/Services/AI/OpenRouterClient.php`
2. Implement `generate(string $prompt, array $options = []): AIResponse`
3. Implement `stream(string $prompt, callable $onChunk): void`
4. Implement `getAvailableModels(): array`
5. Implement `getModelInfo(string $modelId): ?array`
6. Extend AIResponse DTO or use constructor directly for extended fields
7. Create `backend/config/openrouter.php` with free_models config

### Extended AIResponse Fields
The task spec requires additional fields not in the current AIResponse DTO:
- `model` (string|null)
- `promptTokens` (int)
- `responseTokens` (int)

Options:
1. Extend AIResponse DTO with new fields
2. Create OpenRouterResponse DTO extending AIResponse
3. Use constructor directly and access via magic methods

### HTTP Headers Required
```
Authorization: Bearer {api_key}
HTTP-Referer: {app_url}
X-Title: SEOIndicator
Content-Type: application/json
```

### Request Body Format
```json
{
  "model": "google/gemini-2.0-flash-exp:free",
  "messages": [
    {"role": "user", "content": "prompt text"}
  ],
  "max_tokens": 4000,
  "temperature": 0.3,
  "response_format": {"type": "json_object"}
}
```

## Comments
_Agent log_

### [2026-03-05 14:03 GMT+2] PreTester - Test Design Complete
Comprehensive test suite designed following existing OpenRouterProviderTest patterns.

**Test Categories:**
- Class Structure: 9 tests
- API Integration: 14 tests
- Response Handling: 12 tests
- Error Handling: 14 tests
- Timeout Config: 5 tests
- Free Models: 10 tests
- Stream Method: 3 tests
- Security: 5 tests
- Configuration: 5 tests

**Total: 77+ tests**

**Key Design Decisions:**
1. HTTP mocking via Laravel's `Http::fake()` for unit tests
2. Separate test IDs by category for easy filtering
3. Security tests ensure API key protection and XSS prevention
4. Free models configuration tested comprehensively
5. Stream method tested but may fall back to non-streaming for MVP

**Reference:**
- Pattern: `backend/tests/Unit/Services/AI/Providers/OpenRouterProviderTest.php`
- 72 tests passing for OpenRouterProvider

**Handoff:** Ready for Developer implementation. All test cases designed to pass when OpenRouterClient is properly implemented.

Status: `Tests Coverage In Progress` → `To Develop`
