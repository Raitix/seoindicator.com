# TASK-31-2: ContextWindowManager Service

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-031: Unified AI Prompt + Context Management

## Description
Create the `ContextWindowManager` service that estimates token counts, checks context window limits for different AI models, and selects the optimal model based on content size.

## Component Location
- **Service:** `backend/app/Services/AI/ContextWindowManager.php`
- **Tests:** `backend/tests/Unit/Services/AI/ContextWindowManagerTest.php`

## Core Functionalities

| Method | Description |
|--------|-------------|
| `estimateTokens(string $content): int` | Estimate token count from content |
| `fitsInContext(string $model, int $estimatedTokens): bool` | Check if content fits in model context |
| `selectOptimalModel(int $estimatedTokens): string` | Choose best model for content size |
| `getModelLimits(): array` | Return all model context limits |

## Model Context Limits

| Model | Context Window | Effective Limit |
|-------|----------------|-----------------|
| `google/gemini-2.0-flash-exp:free` | 1,000,000 | ~996,600 tokens |
| `qwen/qwen-2-7b-instruct:free` | 32,768 | ~28,600 tokens |
| `mistralai/mistral-7b-instruct:free` | 32,768 | ~28,600 tokens |
| `meta-llama/llama-3-8b-instruct:free` | 8,192 | ~4,600 tokens |

## Implementation Details

```php
class ContextWindowManager
{
    private const MODEL_LIMITS = [
        'google/gemini-2.0-flash-exp:free' => 1000000,
        'qwen/qwen-2-7b-instruct:free' => 32768,
        'mistralai/mistral-7b-instruct:free' => 32768,
        'meta-llama/llama-3-8b-instruct:free' => 8192,
    ];
    
    private const SAFETY_MARGIN = 0.10; // 10% safety margin
    private const BASE_PROMPT_OVERHEAD = 3400; // System prompt + schema
    
    public function estimateTokens(string $content): int
    {
        // Character-based estimation: 1 token ≈ 4 characters
        return (int) ceil(strlen($content) / 4);
    }
    
    public function getEffectiveLimit(string $model): int
    {
        $limit = self::MODEL_LIMITS[$model] ?? 8192;
        return (int) ($limit * (1 - self::SAFETY_MARGIN)) - self::BASE_PROMPT_OVERHEAD;
    }
    
    public function fitsInContext(string $model, int $estimatedTokens): bool
    {
        return $estimatedTokens <= $this->getEffectiveLimit($model);
    }
    
    public function selectOptimalModel(int $estimatedTokens): string
    {
        // Prefer larger context models
        $models = [
            'google/gemini-2.0-flash-exp:free',
            'qwen/qwen-2-7b-instruct:free',
            'mistralai/mistral-7b-instruct:free',
            'meta-llama/llama-3-8b-instruct:free',
        ];
        
        foreach ($models as $model) {
            if ($this->fitsInContext($model, $estimatedTokens)) {
                return $model;
            }
        }
        
        // Fallback: will need truncation
        return 'google/gemini-2.0-flash-exp:free';
    }
}
```

## Acceptance Criteria
- [ ] `estimateTokens()` returns reasonable token estimates (±20% accuracy)
- [ ] `fitsInContext()` correctly validates against model limits
- [ ] `selectOptimalModel()` returns smallest sufficient model
- [ ] Safety margin applied to all model limits
- [ ] Base prompt overhead accounted for
- [ ] All 4 free-tier models supported

## Dependencies
- None (foundation task)

## Effort Estimate
3 hours

## Comments
_Agent log_

### [2026-03-05 10:48 GMT+2] ProjectManager
**Prioritization Complete**

**Priority:** HIGH (confirmed) - Foundation task for SPEC-031 Unified AI Prompt architecture
**Risk:** MEDIUM (confirmed) - Standard Laravel service, no external APIs, character-based estimation
**Status:** `To Write Tests` - Ready for PreTester

**Dependency Analysis:**
- **Dependencies:** NONE - Foundation task, can start immediately
- **Enables:** 3 downstream tasks:
  - TASK-31-3 (TruncationService) - requires token estimation
  - TASK-31-7 (AIAnalysisService) - requires context checking
  - TASK-31-8 (ModelSelectionService) - requires model limit validation

**TDD Approach:**
PreTester can design tests immediately using the defined interface:
- `estimateTokens(string $content): int` - ±20% accuracy target
- `fitsInContext(string $model, int $estimatedTokens): bool`
- `selectOptimalModel(int $estimatedTokens): string`
- `getEffectiveLimit(string $model): int`

**Key Design Points:**
1. Character-based estimation (1 token ≈ 4 chars) - fast, good enough for free-tier models
2. 10% safety margin on all context limits
3. 3400 token base prompt overhead (system prompt + schema)
4. 4 free-tier models supported (Gemini 2.0 Flash, Llama 3 8B, Mistral 7B, Qwen 2 7B)

**Execution Order:** First task in Phase 1 Core Services pipeline
**Handoff to:** PreTester for test design
