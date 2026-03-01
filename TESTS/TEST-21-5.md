# TEST-21-5: AI Suggestion API Endpoint Tests

## Task Reference
- **Task:** [TASK-21-5](../TASKS/TASK-21-5.md)
- **Specification:** [SPEC-021](../SPECIFICATIONS/SPEC-021.md)
- **Feature:** [FEAT-021](../FEATURES/FEAT-021.md)

---

## Overview

Comprehensive feature tests for the AI Suggestion REST API endpoint. Tests cover request validation, response structure, debug mode, error handling, fallback behavior, security, and edge cases.

---

## Test Files

| File | Tests | Assertions |
|------|-------|------------|
| `backend/tests/Feature/Http/Controllers/Api/V1/AISuggestionControllerTest.php` | 65+ | 120+ |

---

## Test Scenarios

### AC1: Controller Created (2 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC1-01 | Controller file exists | N/A | File exists at `app/Http/Controllers/Api/V1/AISuggestionController.php` |
| AC1-02 | Controller has generate method | N/A | Method `generate()` exists |

### AC2: Route Registered (2 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC2-01 | Route is registered | N/A | Route `POST /api/v1/ai/suggestions` exists |
| AC2-02 | Route returns 200 for valid request | Valid payload | HTTP 200 |

### AC3: Request Validation (12 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC3-01 | Requires indicator field | Missing indicator | 422 with validation error |
| AC3-02 | Indicator must be valid enum | `invalid_indicator` | 422 with validation error |
| AC3-03 | All valid indicators accepted | Each valid indicator | 200 OK |
| AC3-04 | Requires context field | Missing context | 422 with validation error |
| AC3-05 | Context must be array | String context | 422 with validation error |
| AC3-06 | Context max 5000 chars | 5001 char context | 422 with validation error |
| AC3-07 | Provider is optional | No provider field | 200 OK |
| AC3-08 | Provider must be valid enum | `invalid_provider` | 422 with validation error |
| AC3-09 | Valid providers accepted | openrouter, vercel, static | 200 OK |
| AC3-10 | Debug is optional | No debug field | 200 OK |
| AC3-11 | Debug must be boolean | String debug | 422 with validation error |
| AC3-12 | Returns 422 with clear messages | Empty payload | 422 with error structure |

### AC4: Response Structure - Success (11 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC4-01 | Returns HTTP 200 with JSON | Valid request | 200, Content-Type: application/json |
| AC4-02 | Contains success: true | Valid request | `{"success": true}` |
| AC4-03 | Contains data object with all fields | Valid request | Complete data structure |
| AC4-04 | Data indicator matches request | indicator: meta_description | data.indicator: "meta_description" |
| AC4-05 | Score is integer 0-100 | Valid request | Integer in range 0-100 |
| AC4-06 | Issues is array | Valid request | Array of strings |
| AC4-07 | Suggestions has proper structure | Valid request | Array with type, text, reason |
| AC4-08 | Best practices is array | Valid request | Array of strings |
| AC4-09 | Provider is string | Valid request | String value |
| AC4-10 | Model is string or null | Valid request | String or null |
| AC4-11 | Generated_at is ISO 8601 | Valid request | Valid timestamp format |

### AC5: Response Structure - Error with Fallback (4 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC5-01 | Returns 200 on AI failure with fallback | AI fails, fallback available | HTTP 200 |
| AC5-02 | Contains success: true on fallback | AI fails, fallback used | `{"success": true}` |
| AC5-03 | Contains fallback_reason | Fallback used | `data.fallback_reason` present |
| AC5-04 | Provider is "static_fallback" | Fallback used | `data.provider: "static_fallback"` |

### AC6: Debug Mode Response (4 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC6-01 | Debug adds debug object | debug: true, authenticated | Response includes debug object |
| AC6-02 | Debug requires authentication | debug: true, not authenticated | Debug ignored or 401 |
| AC6-03 | Debug contains prompt | debug: true | `debug.prompt` present |
| AC6-04 | Debug contains latency_ms | debug: true | `debug.latency_ms` present |

### AC7: Error Handling (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC7-01 | Returns 500 for internal errors | Service throws Exception | HTTP 500 |
| AC7-02 | Returns 429 for rate limit | Rate limit exceeded | HTTP 429 with Retry-After |
| AC7-03 | Returns 503 for service unavailable | All providers down | HTTP 503 |
| AC7-04 | Logs errors with context | Error occurs | Log entry with context |
| AC7-05 | Generic error messages | Internal error | Generic user-facing message |

### AC8: Feature Tests (6 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC8-01 | Test file exists | N/A | File at correct location |
| AC8-02 | Successful generation | Valid request | 200 with complete response |
| AC8-03 | Validation errors | Invalid payload | 422 with error details |
| AC8-04 | Debug mode | debug: true | Debug info in response |
| AC8-05 | Fallback behavior | Provider fails | Fallback response |
| AC8-06 | Error responses | Service error | Appropriate error code |

### Security Tests (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SEC-01 | SQL injection protection | `'; DROP TABLE users; --` | 200, no SQL error |
| SEC-02 | XSS protection | `<script>alert("xss")</script>` | 200, script escaped |
| SEC-03 | Large payload handling | 5001 char context | 422 validation error |
| SEC-04 | No sensitive data in errors | Error with API key | Generic error message |
| SEC-05 | Context data validation | Various context values | Validated appropriately |

### Edge Cases (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EDGE-01 | Empty context array | `context: []` | 200 OK |
| EDGE-02 | Unicode in context | `日本語タイトル 🎉` | 200 OK |
| EDGE-03 | Null values in context | `title: null` | 200 OK |
| EDGE-04 | Very long context field | 5001 chars | 422 validation error |
| EDGE-05 | Concurrent requests | 3 simultaneous | All return 200 |
| EDGE-06 | Special characters in indicator | `title<script>` | 422 validation error |
| EDGE-07 | Case sensitivity | `TITLE` (uppercase) | 422 validation error |
| EDGE-08 | Score boundary values | Score 0 and 100 | Correct values in response |

---

## Mock Data

### Valid Request Payload

```json
{
    "indicator": "title",
    "context": {
        "title": "Home - My Website",
        "content_summary": "E-commerce homepage selling widgets",
        "keyword": "best widgets"
    }
}
```

### Valid Response

```json
{
    "success": true,
    "data": {
        "indicator": "title",
        "score": 75,
        "issues": ["Title is too short", "Missing keyword"],
        "suggestions": [
            {
                "type": "rewrite",
                "text": "Better Title | Your Brand",
                "reason": "Includes keyword and brand"
            }
        ],
        "best_practices": ["Keep title under 60 characters", "Include primary keyword"],
        "provider": "openrouter",
        "model": "llama-3.1-8b-instruct",
        "generated_at": "2026-03-01T12:00:00+00:00"
    }
}
```

### Fallback Response

```json
{
    "success": true,
    "data": {
        "indicator": "title",
        "score": 50,
        "issues": ["Unable to analyze with AI"],
        "suggestions": [],
        "best_practices": ["Keep title under 60 characters"],
        "provider": "static_fallback",
        "fallback_reason": "AI provider timeout",
        "model": null,
        "generated_at": "2026-03-01T12:00:00+00:00"
    }
}
```

### Debug Response

```json
{
    "success": true,
    "data": { "... suggestion data ..." },
    "debug": {
        "prompt": "You are an SEO expert. Analyze this title...",
        "raw_response": "{\"score\": 75, \"issues\": [...]}",
        "tokens_used": 245,
        "latency_ms": 1250,
        "provider": "openrouter",
        "model": "llama-3.1-8b-instruct"
    }
}
```

---

## Coverage Targets

| Category | Target | Status |
|----------|--------|--------|
| Controller instantiation | 100% | ✅ |
| Route registration | 100% | ✅ |
| Request validation | 100% | ✅ |
| Response structure (success) | 100% | ✅ |
| Response structure (fallback) | 100% | ✅ |
| Debug mode | 100% | ✅ |
| Error handling | 100% | ✅ |
| Security tests | 100% | ✅ |
| Edge cases | 100% | ✅ |

---

## Test Execution

```bash
# Run all AI Suggestion tests
cd backend
php artisan test tests/Feature/Http/Controllers/Api/V1/AISuggestionControllerTest.php

# Run with coverage
php artisan test --coverage tests/Feature/Http/Controllers/Api/V1/AISuggestionControllerTest.php

# Run specific test
php artisan test --filter "test_validation_requires_indicator"
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-21-1 (AI Service Structure) | Done | AISuggestionService, AIProviderInterface, DTOs |
| TASK-21-2 (OpenRouter Provider) | In Progress | Will be mocked in tests |
| TASK-21-3 (Static Fallback Provider) | In Progress | Used in fallback tests |
| TASK-21-4 (Prompt Templates) | In Progress | Used by service |

---

## Notes for Developer

### Implementation Checklist

1. **Controller** (`backend/app/Http/Controllers/Api/V1/AISuggestionController.php`)
   - `generate(Request $request): JsonResponse` method
   - Inject `AISuggestionService` via constructor
   - Handle all error cases gracefully

2. **FormRequest** (`backend/app/Http/Requests/AISuggestionRequest.php`)
   - Validation rules for indicator, context, provider, debug
   - Custom error messages

3. **Route** (`backend/routes/api.php`)
   - `Route::post('/ai/suggestions', [AISuggestionController::class, 'generate'])`
   - Consider rate limiting middleware

4. **Exception** (`backend/app/Exceptions/AIServiceUnavailableException.php`)
   - Custom exception for 503 responses

### Key Considerations

- **Fallback always available**: Never return error if fallback can provide value
- **Privacy**: Sanitize context before sending to AI providers
- **Logging**: Log all AI interactions for debugging
- **Rate limiting**: Implement per-IP limits (TASK-21-10)
- **Caching**: Implement in TASK-21-6

---

## Comments
_Agent log_

### [2026-03-01 19:20 GMT+2] PreTester - Test Design Complete
**Status:** `To Write Tests` → `Tests Coverage In Progress` → `To Develop`

**Summary:**
- Created 65+ test cases covering all 8 acceptance criteria
- Tests use mocked AISuggestionService for isolation
- Security tests cover SQL injection, XSS, sensitive data exposure
- Edge cases cover Unicode, nulls, boundaries, concurrency

**Test Structure:**
- Uses RefreshDatabase for clean test state
- Mocks AISuggestionService via Mockery
- Follows project test patterns from LESSONS-LEARNED/PreTester.md

**Handoff to Developer:**
1. Implement `AISuggestionController` with `generate()` method
2. Create `AISuggestionRequest` form request for validation
3. Register route in `routes/api.php`
4. Create `AIServiceUnavailableException` for 503 handling
5. All tests should pass after implementation

**Test Run:**
```bash
cd backend
php artisan test tests/Feature/Http/Controllers/Api/V1/AISuggestionControllerTest.php
```

---

*Created by PreTester on 2026-03-01*
