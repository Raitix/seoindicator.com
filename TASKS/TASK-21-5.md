# TASK-21-5: Create AI Suggestion API Endpoint

## STATUS
`To Prioritize`

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

(No entries yet)

---
*Created by SpecSystemAnalyst on 2026-03-01*
