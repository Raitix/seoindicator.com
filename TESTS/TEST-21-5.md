# TEST-21-5: AI Suggestion API Endpoint Tests

## Task Reference
- **Task**: [TASK-21-5](../TASKS/TASK-21-5.md)
- **Specification**: [SPEC-021](../SPECIFICATIONS/SPEC-021.md)
- **Feature**: [FEAT-021](../FEATURES/FEAT-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

---

## Overview
Tests for the REST API endpoint that generates AI suggestions for SEO indicators. The endpoint receives indicator type and context data, processes it through the AI service, and returns structured suggestions.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Feature/Api/V1/AISuggestionControllerTest.php` | Feature | 45+ | 100+ |
| `backend/tests/Unit/Http/Requests/AISuggestionRequestTest.php` | Unit | 25+ | 50+ |

---

## Test Scenarios

### TC-001: Controller Creation (AC-1)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-001-01 | Controller file exists | N/A | File at `backend/app/Http/Controllers/Api/V1/AISuggestionController.php` |
| TC-001-02 | Controller has generate method | N/A | Method `generate(Request $request): JsonResponse` exists |
| TC-001-03 | Controller injects AISuggestionService | N/A | Constructor accepts AISuggestionInterface |
| TC-001-04 | Controller can be resolved from container | N/A | Instance created successfully |

### TC-002: Route Registration (AC-2)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-002-01 | Route registered | POST `/api/v1/ai/suggestions` | Route exists in route list |
| TC-002-02 | Route returns 401 without auth | No Authorization header | HTTP 401 |
| TC-002-03 | Route accepts POST method | POST request | HTTP 200 or 422 |
| TC-002-04 | Route rejects GET method | GET request | HTTP 405 |

### TC-003: Request Validation - Indicator Field (AC-3)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-003-01 | Valid indicator: title | `{"indicator": "title"}` | HTTP 200 |
| TC-003-02 | Valid indicator: meta_description | `{"indicator": "meta_description"}` | HTTP 200 |
| TC-003-03 | Valid indicator: robots | `{"indicator": "robots"}` | HTTP 200 |
| TC-003-04 | Valid indicator: sitemap | `{"indicator": "sitemap"}` | HTTP 200 |
| TC-003-05 | Valid indicator: headings | `{"indicator": "headings"}` | HTTP 200 |
| TC-003-06 | Valid indicator: image_alt | `{"indicator": "image_alt"}` | HTTP 200 |
| TC-003-07 | Valid indicator: filenames | `{"indicator": "filenames"}` | HTTP 200 |
| TC-003-08 | Valid indicator: url | `{"indicator": "url"}` | HTTP 200 |
| TC-003-09 | Valid indicator: links | `{"indicator": "links"}` | HTTP 200 |
| TC-003-10 | Valid indicator: 404 | `{"indicator": "404"}` | HTTP 200 |
| TC-003-11 | Missing indicator field | `{}` | HTTP 422, validation error |
| TC-003-12 | Invalid indicator value | `{"indicator": "invalid"}` | HTTP 422, validation error |
| TC-003-13 | Empty indicator | `{"indicator": ""}` | HTTP 422, validation error |

### TC-004: Request Validation - Context Field (AC-3)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-004-01 | Valid context object | `{"indicator": "title", "context": {"title": "Test"}}` | HTTP 200 |
| TC-004-02 | Missing context field | `{"indicator": "title"}` | HTTP 422, validation error |
| TC-004-03 | Context not array | `{"indicator": "title", "context": "string"}` | HTTP 422, validation error |
| TC-004-04 | Context exceeds max chars | `{"indicator": "title", "context": {...5000+ chars...}}` | HTTP 422, validation error |

### TC-005: Request Validation - Provider Field (AC-3)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-005-01 | Valid provider: openrouter | `{"indicator": "title", "context": {}, "provider": "openrouter"}` | HTTP 200 |
| TC-005-02 | Valid provider: vercel | `{"indicator": "title", "context": {}, "provider": "vercel"}` | HTTP 200 |
| TC-005-03 | Valid provider: static | `{"indicator": "title", "context": {}, "provider": "static"}` | HTTP 200 |
| TC-005-04 | Invalid provider | `{"indicator": "title", "context": {}, "provider": "invalid"}` | HTTP 422, validation error |
| TC-005-05 | Provider optional | `{"indicator": "title", "context": {}}` | HTTP 200, uses default |

### TC-006: Request Validation - Debug Field (AC-3)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-006-01 | Debug true | `{"indicator": "title", "context": {}, "debug": true}` | HTTP 200, includes debug |
| TC-006-02 | Debug false | `{"indicator": "title", "context": {}, "debug": false}` | HTTP 200, no debug |
| TC-006-03 | Debug invalid | `{"indicator": "title", "context": {}, "debug": "yes"}` | HTTP 422, validation error |
| TC-006-04 | Debug optional | `{"indicator": "title", "context": {}}` | HTTP 200, defaults to false |

### TC-007: Response Structure - Success (AC-4)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-007-01 | Response has success field | Valid request | `{"success": true}` |
| TC-007-02 | Response has data object | Valid request | `{"data": {...}}` |
| TC-007-03 | Data has indicator field | Valid request | `data.indicator: string` |
| TC-007-04 | Data has score field | Valid request | `data.score: int (0-100)` |
| TC-007-05 | Data has issues array | Valid request | `data.issues: array` |
| TC-007-06 | Data has suggestions array | Valid request | `data.suggestions: array` |
| TC-007-07 | Data has best_practices array | Valid request | `data.best_practices: array` |
| TC-007-08 | Data has provider field | Valid request | `data.provider: string` |
| TC-007-09 | Data has model field | Valid request | `data.model: string\|null` |
| TC-007-10 | Data has generated_at field | Valid request | `data.generated_at: ISO 8601` |

### TC-008: Response Structure - Fallback (AC-5)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-008-01 | Fallback returns 200 | AI fails, fallback used | HTTP 200 |
| TC-008-02 | Fallback has success true | AI fails, fallback used | `{"success": true}` |
| TC-008-03 | Fallback has provider | AI fails, fallback used | `data.provider: "static_fallback"` |
| TC-008-04 | Fallback has reason | AI fails, fallback used | `data.fallback_reason: string` |

### TC-009: Debug Mode Response (AC-6)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-009-01 | Debug includes prompt | `debug: true` | `debug.prompt: string` |
| TC-009-02 | Debug includes raw_response | `debug: true` | `debug.raw_response: string` |
| TC-009-03 | Debug includes tokens_used | `debug: true` | `debug.tokens_used: int` |
| TC-009-04 | Debug includes latency_ms | `debug: true` | `debug.latency_ms: int` |
| TC-009-05 | Debug includes provider | `debug: true` | `debug.provider: string` |
| TC-009-06 | Debug includes model | `debug: true` | `debug.model: string` |
| TC-009-07 | Debug hidden when false | `debug: false` | No `debug` key in response |

### TC-010: Error Handling (AC-7)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-010-01 | Internal server error | Service throws exception | HTTP 500 |
| TC-010-02 | Service unavailable | All providers down | HTTP 503 |
| TC-010-03 | Error logs context | Service throws exception | Error logged with context |
| TC-010-04 | Generic error message | Service throws exception | No sensitive data in response |

---

## Mock Data

### Valid Request (Title Indicator)
```json
{
    "indicator": "title",
    "context": {
        "title": "Home - My Website",
        "content_summary": "E-commerce homepage selling widgets...",
        "keyword": "best widgets"
    },
    "provider": "openrouter",
    "debug": false
}
```

### Valid Response
```json
{
    "success": true,
    "data": {
        "indicator": "title",
        "score": 65,
        "issues": [
            "Title is too generic",
            "Missing target keyword"
        ],
        "suggestions": [
            {
                "type": "rewrite",
                "text": "Best Widgets 2026 | Premium Quality Widgets Online",
                "reason": "Includes target keyword and value proposition"
            }
        ],
        "best_practices": [
            "Keep title under 60 characters",
            "Place primary keyword near the beginning"
        ],
        "provider": "openrouter",
        "model": "llama-3.1-8b-instruct",
        "generated_at": "2026-03-01T12:00:00Z"
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
        "issues": ["No specific issues detected"],
        "suggestions": [
            {
                "type": "improve",
                "text": "Consider adding your target keyword to the title",
                "reason": "Helps search engines understand page content"
            }
        ],
        "best_practices": [
            "Keep title under 60 characters",
            "Include primary keyword near the beginning"
        ],
        "provider": "static_fallback",
        "fallback_reason": "AI provider timeout",
        "model": null,
        "generated_at": "2026-03-01T12:00:00Z"
    }
}
```

### Debug Response
```json
{
    "success": true,
    "data": { /* ... suggestions ... */ },
    "debug": {
        "prompt": "You are an SEO expert. Analyze this title tag...",
        "raw_response": "{\"score\": 65, \"issues\": [...], ...}",
        "tokens_used": 245,
        "latency_ms": 1250,
        "provider": "openrouter",
        "model": "llama-3.1-8b-instruct"
    }
}
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Controller Creation | 100% | 4 |
| Route Registration | 100% | 4 |
| Request Validation | 100% | 20 |
| Response Structure | 100% | 14 |
| Error Handling | 100% | 4 |
| **Total** | **100%** | **46+** |

---

## Test Execution

```bash
# Run all AI suggestion tests
cd backend
php artisan test --filter=AISuggestionController

# Run specific test file
php artisan test tests/Feature/Api/V1/AISuggestionControllerTest.php

# Run with coverage
php artisan test --coverage --filter=AISuggestionController
```

---

## Dependencies

- TASK-21-1 (AI Service Structure) - ✅ Complete
- TASK-21-2 (OpenRouter Provider) - In Development
- TASK-21-3 (Static Fallback Provider) - To Test Locally
- TASK-21-4 (Prompt Templates) - To Test Locally

**Note**: Tests use mocked `AISuggestionService` to allow testing independently of provider implementations.

---

## Notes for Developer

### Implementation Requirements

1. **Controller Structure**:
   - `AISuggestionController` at `backend/app/Http/Controllers/Api/V1/AISuggestionController.php`
   - Constructor dependency injection for `AISuggestionService`
   - Single public method `generate(Request $request): JsonResponse`

2. **Request Validation**:
   - Create `AISuggestionRequest` FormRequest class
   - Validate `indicator` against enum values
   - Validate `context` is array with max 5000 chars
   - Validate `provider` as optional enum
   - Validate `debug` as optional boolean

3. **Response Format**:
   - Always return JSON
   - Use consistent structure for success/error
   - Include `generated_at` as ISO 8601 timestamp

4. **Error Handling**:
   - Log errors with full context
   - Return generic messages to client
   - Never expose API keys or internal details

5. **Debug Mode**:
   - Only enable for authenticated admin users
   - Include full prompt and raw response
   - Include timing metrics

---

## Comments
_Agent log_

### [2026-03-01 19:15 GMT+2] PreTester - Tests Coverage In Progress
Creating test documentation and feature tests for TASK-21-5.

**Approach**:
- Mock `AISuggestionService` to test controller independently
- Use `RefreshDatabase` trait for isolation
- Follow existing test patterns from ProjectControllerTest
- Cover all acceptance criteria from task

Status: `To Write Tests` → `Tests Coverage In Progress`
