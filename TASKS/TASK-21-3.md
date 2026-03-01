# TASK-21-3: Implement Static Fallback Provider

## STATUS
`To Prioritize`

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

(No entries yet)

---
*Created by SpecSystemAnalyst on 2026-03-01*
