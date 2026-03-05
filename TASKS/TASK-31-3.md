# TASK-31-3: TruncationService Implementation

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-31: Unified AI Prompt + Context Management

## Description
Create the `TruncationService` that applies progressive truncation strategies to reduce HTML content size when it exceeds model context windows while preserving SEO-relevant information.

## Component Location
- **Service:** `backend/app/Services/AI/TruncationService.php`
- **DTO:** `backend/app/Services/AI/DTOs/TruncationResult.php`
- **Tests:** `backend/tests/Unit/Services/AI/TruncationServiceTest.php`

## Core Functionalities

| Method | Description |
|--------|-------------|
| `truncate(string $html, int $targetTokens): TruncationResult` | Apply truncation strategies to fit target |
| `getAvailableStrategies(): array` | Return list of truncation strategies |
| `estimateReduction(string $strategy): int` | Estimated reduction percentage |

## Truncation Strategies (Priority Order)

| Priority | Strategy | Reduction | Data Loss | Reversible |
|----------|----------|-----------|-----------|------------|
| 1 | Remove HTML comments | 5-15% | None | Yes |
| 2 | Collapse whitespace | 3-8% | None | Yes |
| 3 | Remove `<script>` tags | 10-40% | JS code only | No |
| 4 | Remove `<style>` tags | 5-20% | CSS only | No |
| 5 | Remove base64 images | Variable | Image data | No |
| 6 | Truncate text content | 50-70% | Paraphrased | No |
| 7 | Keep semantic structure | 70-90% | Significant | No |

## Implementation Details

```php
class TruncationService
{
    private ContextWindowManager $tokenManager;
    
    public function truncate(string $html, int $targetTokens): TruncationResult
    {
        $originalTokens = $this->tokenManager->estimateTokens($html);
        $currentHtml = $html;
        $appliedStrategies = [];
        
        $strategies = [
            'removeComments',
            'collapseWhitespace',
            'removeScripts',
            'removeStyles',
            'removeBase64Images',
            'summarizeContent',
            'keepStructureOnly',
        ];
        
        foreach ($strategies as $strategy) {
            $currentTokens = $this->tokenManager->estimateTokens($currentHtml);
            
            if ($currentTokens <= $targetTokens) {
                break;
            }
            
            $currentHtml = $this->{$strategy}($currentHtml);
            $appliedStrategies[] = $strategy;
        }
        
        return new TruncationResult(
            html: $currentHtml,
            originalTokens: $originalTokens,
            finalTokens: $this->tokenManager->estimateTokens($currentHtml),
            strategiesApplied: $appliedStrategies,
            reductionPercent: round((1 - $currentTokens / $originalTokens) * 100, 1)
        );
    }
    
    private function removeComments(string $html): string
    {
        return preg_replace('/<!--.*?-->/s', '', $html);
    }
    
    private function collapseWhitespace(string $html): string
    {
        return preg_replace('/\s+/', ' ', $html);
    }
    
    private function removeScripts(string $html): string
    {
        return preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    }
    
    private function removeStyles(string $html): string
    {
        return preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    }
    
    private function removeBase64Images(string $html): string
    {
        return preg_replace('/src=["\']data:image\/[^"\']+["\']/', 'src=""', $html);
    }
    
    private function summarizeContent(string $html): string
    {
        // Extract key semantic elements (h1-h6, p, a, img, meta, title)
        // Use DOM crawler to preserve structure
    }
    
    private function keepStructureOnly(string $html): string
    {
        // Keep only semantic HTML structure (headings, links, meta)
    }
}
```

## TruncationResult DTO

```php
class TruncationResult
{
    public function __construct(
        public readonly string $html,
        public readonly int $originalTokens,
        public readonly int $finalTokens,
        public readonly array $strategiesApplied,
        public readonly float $reductionPercent,
    ) {}
}
```

## Acceptance Criteria
- [ ] `truncate()` reduces content to fit target tokens
- [ ] Strategies applied in priority order
- [ ] Non-destructive strategies tried first
- [ ] `TruncationResult` contains all metadata
- [ ] Original HTML never modified (returns new string)
- [ ] Handles edge cases (empty HTML, already small, etc.)

## Dependencies
- TASK-31-2 (ContextWindowManager for token estimation)

## Effort Estimate
4 hours

## Comments
_Agent log_

### [2026-03-05 13:15 GMT+2] ProjectManager
**Prioritization Complete**

**Priority:** HIGH (confirmed) - Core service in SPEC-031 Unified AI Prompt architecture
**Risk:** MEDIUM (confirmed) - Standard Laravel service, pure string manipulation, no external APIs
**Status:** `To Write Tests` - Ready for PreTester

**Dependency Analysis:**
- **Dependency:** TASK-31-2 (ContextWindowManager) - ✅ DONE
- **Can Proceed:** YES - dependency satisfied
- **Enables:** TASK-31-7 (AIAnalysisService) requires truncation for content reduction

**TDD Approach:**
PreTester can design tests immediately using the defined interface:
- `truncate(string $html, int $targetTokens): TruncationResult`
- `getAvailableStrategies(): array`
- `estimateReduction(string $strategy): int`

**7 Truncation Strategies (Priority Order):**
| Priority | Strategy | Reduction | Reversible |
|----------|----------|-----------|------------|
| 1 | Remove HTML comments | 5-15% | Yes |
| 2 | Collapse whitespace | 3-8% | Yes |
| 3 | Remove `<script>` tags | 10-40% | No |
| 4 | Remove `<style>` tags | 5-20% | No |
| 5 | Remove base64 images | Variable | No |
| 6 | Truncate text content | 50-70% | No |
| 7 | Keep semantic structure | 70-90% | No |

**Key Design Points:**
1. Uses ContextWindowManager for token estimation
2. Progressive strategy application (non-destructive first)
3. TruncationResult DTO contains: html, originalTokens, finalTokens, strategiesApplied, reductionPercent
4. Original HTML never modified (returns new string)

**Execution Order:** Second task in Phase 1 Core Services pipeline (after TASK-31-2)
**Handoff to:** PreTester for test design
