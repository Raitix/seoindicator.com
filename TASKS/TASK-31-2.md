# TASK-31-2: ContextWindowManager Service

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-31: Unified AI Prompt + Context Management

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
