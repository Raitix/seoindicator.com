# TASK-21-5: Create AI Suggestion API Endpoint

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
[SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

## Description
Create the REST API endpoint for generating AI suggestions. This endpoint receives indicator type and context data, processes it through the AI service, and returns structured suggestions.

---

## Acceptance Criteria

### AC-1: Controller Created
- [ ] File exists at `backend/app/Http/Controllers/Api/V1/AISuggestionController.php`
- [ ] Method `generate(Request $request): JsonResponse`
- [ ] Proper dependency injection for AISuggestionService

### AC-2: Route Registered
- [ ] Route `POST /api/v1/ai/suggestions` registered in `routes/api.php`
- [ ] Route protected by `auth` middleware (or public with rate limiting)
- [ ] Route documented in API documentation

### AC-3: Request Validation
- [ ] FormRequest `AISuggestionRequest` created
- [ ] Validates `indicator` field: required, enum (title, meta_description, robots, sitemap, headings, image_alt, filenames, url, links, 404)
- [ ] Validates `context` field: required, array, max 5000 chars total
- [ ] Validates `provider` field: optional, enum (openrouter, vercel, static)
- [ ] Validates `debug` field: optional, boolean
- [ ] Returns 422 with clear error messages for invalid input

### AC-4: Response Structure (Success)
- [ ] Returns HTTP 200 with JSON body
- [ ] Contains `success: true`
- [ ] Contains `data` object with:
  - `indicator`: string
  - `score`: integer (0-100)
  - `issues`: array
  - `suggestions`: array
  - `best_practices`: array
  - `provider`: string
  - `model`: string|null
  - `generated_at`: ISO 8601 timestamp

### AC-5: Response Structure (Error with Fallback)
- [ ] Returns HTTP 200 even when AI fails (fallback used)
- [ ] Contains `success: true`
- [ ] Contains `data.fallback_reason` when fallback was used
- [ ] Contains `data.provider: "static_fallback"`

### AC-6: Debug Mode Response
- [ ] When `?debug=true` and user is admin/authenticated
- [ ] Includes `debug` object in response:
  - `prompt`: full prompt text
  - `raw_response`: raw AI response
  - `tokens_used`: token count
  - `latency_ms`: response time
  - `provider`: provider name
  - `model`: model identifier

### AC-7: Error Handling
- [ ] Returns 500 for internal server errors
- [ ] Returns 429 for rate limit exceeded
- [ ] Returns 503 for service unavailable (all providers down)
- [ ] Logs all errors with context
- [ ] Generic error messages to client (no sensitive data)

### AC-8: Feature Tests
- [ ] Test file at `backend/tests/Feature/Http/Controllers/Api/V1/AISuggestionControllerTest.php`
- [ ] Tests for successful generation
- [ ] Tests for validation errors
- [ ] Tests for debug mode
- [ ] Tests for fallback behavior
- [ ] Tests for error responses
- [ ] All tests passing

---

## Dependencies
- TASK-21-1 (AI Service Structure)
- TASK-21-2 (OpenRouter Provider)
- TASK-21-3 (Static Fallback Provider)
- TASK-21-4 (Prompt Templates)

---

## Effort Estimate
**Medium** (3-4 hours)

- Controller and request: 1.5 hours
- Response handling: 1 hour
- Tests: 1.5 hours

---

## Technical Notes

### Request Example
```json
POST /api/v1/ai/suggestions
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

### Response Example
```json
{
    "success": true,
    "data": {
        "indicator": "title",
        "score": 65,
        "issues": ["Title is too generic", "Missing target keyword"],
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

---

## Comments
_Agent log_

### [2026-03-01 18:53 GMT+2] ProjectManager - Prioritization Complete
**Priority Confirmed: High** ✅
**Risk Confirmed: Medium** ✅

**Rationale for Priority:**
- **Phase 2 API & Integration** - Core API endpoint that exposes AI suggestions to frontend
- **Essential for feature value** - Without this endpoint, all AI provider work (TASK-21-2, 21-3, 21-4) has no consumer
- **Critical path** - Blocks TASK-21-6 (Caching), TASK-21-7 (Debug Mode), TASK-21-11 (Frontend Integration)
- **User-facing functionality** - This is the primary interface for the AI Suggestions feature

**Rationale for Risk:**
- **Medium Risk** - Standard Laravel controller implementation
- Dependencies handle the complex logic (providers, prompts, fallbacks)
- Request validation and response formatting are well-established patterns
- No direct external API calls (delegated to AISuggestionService)

**Dependency Analysis:**
| Task | Status | Ready? |
|------|--------|--------|
| TASK-21-1 (AI Service Structure) | `To Test Prod` | ✅ Essentially complete |
| TASK-21-2 (OpenRouter Provider) | `To Develop` | ⏳ In development |
| TASK-21-3 (Static Fallback Provider) | `To Write Tests` | ⏳ Tests being written |
| TASK-21-4 (Prompt Templates) | `To Write Tests` | ⏳ Tests being written |

**Execution Strategy:**
- Tests can be designed NOW using TDD approach based on:
  - `AIProviderInterface` from TASK-21-1 (available)
  - `AISuggestionService` from TASK-21-1 (available)
  - `SuggestionResult` DTO from TASK-21-1 (available)
- PreTester can create tests that mock the provider interface
- Implementation will wait for dependencies to reach `To Test Locally`

**Execution Order within SPEC-021:**
1. TASK-21-1: `To Test Prod` (nearly done)
2. TASK-21-2, 21-3, 21-4: Parallel development (in progress)
3. TASK-21-5 (this): After all Phase 1 tasks reach `To Test Locally`
4. TASK-21-6, 21-7, 21-8: Parallel after TASK-21-5
5. TASK-21-9+: Vercel provider and polish

**Handoff to PreTester:** Ready for test design. Focus on:
- Controller instantiation with mocked `AISuggestionService`
- Request validation (`AISuggestionRequest`): indicator enum, context array, provider optional
- Response format: success, data (indicator, score, issues, suggestions, best_practices, provider, model, generated_at)
- Debug mode: additional debug object in response
- Error handling: 500, 429, 503 responses
- Fallback behavior: `provider: "static_fallback"` in response

Status: `To Prioritize` → `To Write Tests`

### [2026-03-01 19:22 GMT+2] PreTester - Test Design Complete
**Status:** `To Write Tests` → `Tests Coverage In Progress` → `To Develop`

**Tests Created:**
- **File:** `backend/tests/Feature/Http/Controllers/Api/V1/AISuggestionControllerTest.php`
- **Documentation:** `TESTS/TEST-21-5.md`
- **Test Count:** 65+ tests, 120+ assertions

**Test Coverage:**
| Acceptance Criteria | Tests | Status |
|---------------------|-------|--------|
| AC1: Controller Created | 2 | ✅ |
| AC2: Route Registered | 2 | ✅ |
| AC3: Request Validation | 12 | ✅ |
| AC4: Response Structure (Success) | 11 | ✅ |
| AC5: Response Structure (Fallback) | 4 | ✅ |
| AC6: Debug Mode Response | 4 | ✅ |
| AC7: Error Handling | 5 | ✅ |
| AC8: Feature Tests | 6 | ✅ |
| Security Tests | 5 | ✅ |
| Edge Cases | 8 | ✅ |

**Implementation Notes for Developer:**
1. Create `AISuggestionController` at `backend/app/Http/Controllers/Api/V1/AISuggestionController.php`
2. Create `AISuggestionRequest` form request for validation
3. Register route `POST /api/v1/ai/suggestions` in `routes/api.php`
4. Create `AIServiceUnavailableException` for 503 handling
5. All tests use mocked `AISuggestionService` - service implementation from TASK-21-1

**Test Execution:**
```bash
cd backend
php artisan test tests/Feature/Http/Controllers/Api/V1/AISuggestionControllerTest.php
```

---
*Created by SpecSystemAnalyst on 2026-03-01*
