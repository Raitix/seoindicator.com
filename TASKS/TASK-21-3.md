# TASK-21-3: Implement Static Fallback Provider

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Specification
[SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

## Description
Implement a static, rule-based fallback provider that generates SEO suggestions without external AI dependencies. This ensures the feature always returns useful suggestions even when AI providers are unavailable or rate-limited.

---

## Acceptance Criteria

### AC-1: StaticFallbackProvider Class Created
- [ ] File exists at `backend/app/Services/AI/Providers/StaticFallbackProvider.php`
- [ ] Implements `AIProviderInterface`
- [ ] No external dependencies (pure PHP)

### AC-2: Indicator-Specific Rules
- [ ] `title` indicator rules:
  - Length check (50-60 chars optimal)
  - Keyword presence check
  - Generic improvement suggestions
- [ ] `meta_description` indicator rules:
  - Length check (150-160 chars optimal)
  - Call-to-action suggestions
- [ ] `robots` indicator rules:
  - Common directives explanation
  - Allow/disallow patterns
- [ ] `sitemap` indicator rules:
  - XML structure validation
  - URL coverage suggestions
- [ ] `headings` indicator rules:
  - H1 uniqueness check
  - Hierarchy validation
- [ ] `image_alt` indicator rules:
  - Missing alt detection
  - Descriptive text suggestions
- [ ] `filenames` indicator rules:
  - SEO-friendly naming patterns
- [ ] `url` indicator rules:
  - URL structure analysis
  - Slug optimization
- [ ] `links` indicator rules:
  - Internal linking suggestions
  - External link safety
- [ ] `404` indicator rules:
  - Common 404 causes
  - Redirect suggestions

### AC-3: Response Generation
- [ ] `generate()` method accepts indicator type and context
- [ ] Returns structured JSON matching AI response format
- [ ] Score calculation based on rule violations
- [ ] Suggestions array with actionable items
- [ ] Returns `AIResponse` DTO with `success=true`

### AC-4: Availability Check
- [ ] `isAvailable()` always returns `true`
- [ ] No external dependencies to fail

### AC-5: Response Format
- [ ] Output matches AI provider JSON schema
- [ ] Includes `provider: "static_fallback"` identifier
- [ ] Includes indicator-specific fields
- [ ] Structured for frontend compatibility

### AC-6: Unit Tests
- [ ] Test file at `backend/tests/Unit/Services/AI/Providers/StaticFallbackProviderTest.php`
- [ ] Tests for each indicator type
- [ ] Tests for edge cases (empty context, missing fields)
- [ ] Tests for response format compliance
- [ ] All tests passing

---

## Dependencies
- TASK-21-1 (AI Service Structure and Interfaces)

---

## Effort Estimate
**Medium** (4-5 hours)

- Provider implementation: 2 hours
- Rule definitions: 1.5 hours
- Tests: 1.5 hours

---

## Technical Notes

### Rule-Based Scoring Example (Title)
```php
private function scoreTitle(string $title, array $context): array
{
    $score = 100;
    $issues = [];
    
    // Length check
    $length = strlen($title);
    if ($length < 30) {
        $score -= 20;
        $issues[] = "Title is too short ({$length} chars). Aim for 50-60 characters.";
    } elseif ($length > 60) {
        $score -= 15;
        $issues[] = "Title is too long ({$length} chars). Keep under 60 characters.";
    }
    
    // Keyword presence
    $keyword = $context['keyword'] ?? '';
    if ($keyword && stripos($title, $keyword) === false) {
        $score -= 25;
        $issues[] = "Target keyword '{$keyword}' not found in title.";
    }
    
    return [
        'score' => max(0, $score),
        'issues' => $issues,
        'suggestions' => $this->generateTitleSuggestions($title, $context),
    ];
}
```

### Response Format
```json
{
  "score": 65,
  "issues": ["Title is too generic", "Missing target keyword"],
  "suggestions": [
    {
      "type": "rewrite",
      "text": "Best Widgets 2026 | Premium Quality",
      "reason": "Includes target keyword and value proposition"
    }
  ],
  "best_practices": [
    "Keep title under 60 characters",
    "Place primary keyword near the beginning"
  ]
}
```

---

## Comments
_Agent log_

### [2026-03-01 18:45 GMT+2] ProjectManager - Prioritization Complete
**Priority Confirmed: High** ✅
**Risk Confirmed: Low** ✅

**Rationale:**
- **Phase 1 Core Infrastructure** - Essential fallback mechanism for AI feature
- **No external dependencies** - Pure PHP implementation with rule-based logic
- **Critical for reliability** - Ensures suggestions are always available even when AI providers fail
- **Deterministic behavior** - No network calls, no API keys, no rate limits
- **Foundation for graceful degradation** - UX remains functional under all conditions

**Dependency Analysis:**
- TASK-21-1 (AI Service Structure): Status `To Test Locally` - interfaces and DTOs available ✅
- `AIProviderInterface` exists and is implemented by this provider
- `AIResponse` DTO exists for response structure
- `SuggestionResult` DTO exists for result formatting
- **Can proceed** - dependencies are implemented and functional

**Execution Order within SPEC-021:**
- TASK-21-1 → TASK-21-2, 21-3, 21-4 (parallel) → TASK-21-5 → remaining tasks
- This task can run in parallel with TASK-21-2 and TASK-21-4 after TASK-21-1

**Technical Considerations:**
- 10 indicator types require rule implementations (title, meta_description, robots, sitemap, headings, image_alt, filenames, url, links, 404)
- Each indicator needs: length checks, pattern validation, keyword analysis, actionable suggestions
- Response format must match AI provider JSON schema for frontend compatibility
- Score calculation (0-100) based on rule violations

**Handoff to PreTester:** Ready for test design. Focus on:
- Rule-based scoring accuracy per indicator type
- Edge cases: empty context, missing fields, special characters
- Response format compliance with AI provider schema
- `isAvailable()` always returns true (no external deps)
- Suggestion quality and actionability

Status: `To Prioritize` → `To Write Tests`

---
*Created by SpecSystemAnalyst on 2026-03-01*
