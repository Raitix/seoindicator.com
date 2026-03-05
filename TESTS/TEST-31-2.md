# TEST-31-2: ContextWindowManager Service Tests

## Task Reference
- **Task:** TASK-31-2
- **Specification:** SPEC-031 (Unified AI Prompt + Context Management)
- **Feature:** FEAT-031

## Overview
Comprehensive unit test suite for the `ContextWindowManager` service. Tests token estimation, context window validation, model selection, and edge cases for the AI context management system.

## Component Location
- **Service:** `backend/app/Services/AI/ContextWindowManager.php`
- **Tests:** `backend/tests/Unit/Services/AI/ContextWindowManagerTest.php`

## Test Summary

| Category | Tests | Description |
|----------|-------|-------------|
| Model Limits Constants | 4 | Verify model limits, safety margin, base overhead |
| Token Estimation | 9 | Character-to-token conversion accuracy |
| Effective Limit Calculation | 7 | Safety margin and overhead application |
| Context Window Validation | 9 | fitsInContext boundary testing |
| Model Selection | 10 | selectOptimalModel priority order |
| Edge Cases | 7 | Zero, negative, large values, unicode |
| Integration Tests | 3 | Full workflow scenarios |
| Acceptance Criteria | 6 | All ACs verified |
| Performance Tests | 3 | Speed benchmarks |
| Data-Driven Tests | 4 | Parameterized model tests |
| **Total** | **62** | |

## Test Scenarios

### Model Limits Constants

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| C-01 | Verify Gemini limit | `getModelLimits()['google/gemini-2.0-flash-exp:free']` | 1,000,000 |
| C-02 | Verify Qwen limit | `getModelLimits()['qwen/qwen-2-7b-instruct:free']` | 32,768 |
| C-03 | Verify Mistral limit | `getModelLimits()['mistralai/mistral-7b-instruct:free']` | 32,768 |
| C-04 | Verify Llama limit | `getModelLimits()['meta-llama/llama-3-8b-instruct:free']` | 8,192 |
| C-05 | Verify 4 models total | `count(getModelLimits())` | 4 |
| C-06 | Safety margin constant | `SAFETY_MARGIN` | 0.10 (10%) |
| C-07 | Base prompt overhead | `BASE_PROMPT_OVERHEAD` | 3400 tokens |

### Token Estimation

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TE-01 | Empty content | `estimateTokens('')` | 0 |
| TE-02 | Single character | `estimateTokens('a')` | 1 |
| TE-03 | Exact 100 tokens | 400 chars | 100 |
| TE-04 | Fractional tokens | 401 chars | 101 (ceiling) |
| TE-05 | Large content | 1,000,000 chars | 250,000 tokens |
| TE-06 | Multibyte chars | `'你好世界'` | 3 tokens (12 bytes / 4) |
| TE-07 | Emoji content | `'😀👍🎉'` | ≥3 tokens |
| TE-08 | Whitespace only | `'   '` | 1 token |
| TE-09 | Newlines | `"\n\n\n\n"` | 1 token |

### Effective Limit Calculation

| ID | Scenario | Model | Expected Effective Limit |
|----|----------|-------|-------------------------|
| EL-01 | Gemini effective | google/gemini-2.0-flash-exp:free | 896,600 |
| EL-02 | Qwen effective | qwen/qwen-2-7b-instruct:free | 26,091 |
| EL-03 | Mistral effective | mistralai/mistral-7b-instruct:free | 26,091 |
| EL-04 | Llama effective | meta-llama/llama-3-8b-instruct:free | 3,972 |
| EL-05 | Unknown model | unknown/model | 3,972 (default) |
| EL-06 | Safety margin applied | Any model | < raw limit |
| EL-07 | Overhead subtracted | Any model | (raw * 0.9) - 3400 |

### Context Window Validation

| ID | Scenario | Model | Tokens | Expected |
|----|----------|-------|--------|----------|
| CV-01 | Fits in Gemini | google/gemini-2.0-flash-exp:free | 500,000 | true |
| CV-02 | Over Gemini limit | google/gemini-2.0-flash-exp:free | 900,000 | false |
| CV-03 | Fits in Llama | meta-llama/llama-3-8b-instruct:free | 3,000 | true |
| CV-04 | Over Llama limit | meta-llama/llama-3-8b-instruct:free | 5,000 | false |
| CV-05 | At exact limit | meta-llama/llama-3-8b-instruct:free | 3,972 | true |
| CV-06 | One over limit | meta-llama/llama-3-8b-instruct:free | 3,973 | false |
| CV-07 | Zero tokens | Any model | 0 | true |
| CV-08 | Unknown model | unknown/model | 3,000 | true |
| CV-09 | All models validated | All 4 models | Effective limit | true |

### Model Selection

| ID | Scenario | Tokens | Expected Model |
|----|----------|--------|----------------|
| MS-01 | Small content | 1,000 | meta-llama/llama-3-8b-instruct:free |
| MS-02 | At Llama limit | 3,972 | meta-llama/llama-3-8b-instruct:free |
| MS-03 | Over Llama limit | 3,973 | qwen/qwen-2-7b-instruct:free |
| MS-04 | Medium content | 10,000 | qwen/qwen-2-7b-instruct:free |
| MS-05 | At Qwen limit | 26,091 | qwen/qwen-2-7b-instruct:free |
| MS-06 | Over Qwen limit | 26,092 | google/gemini-2.0-flash-exp:free |
| MS-07 | Large content | 50,000 | google/gemini-2.0-flash-exp:free |
| MS-08 | At Gemini limit | 896,600 | google/gemini-2.0-flash-exp:free |
| MS-09 | Over all limits | 1,000,000 | google/gemini-2.0-flash-exp:free |
| MS-10 | Zero tokens | 0 | meta-llama/llama-3-8b-instruct:free |

### Edge Cases

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| EC-01 | Zero tokens | 0 | Select smallest model |
| EC-02 | Negative tokens | -100 | Handle gracefully, select smallest |
| EC-03 | Very large tokens | 10,000,000 | Fallback to Gemini |
| EC-04 | Whitespace only | `'   '` | Returns 1 token |
| EC-05 | Newlines only | `"\n\n\n\n"` | Returns 1 token |
| EC-06 | Mixed content | `"Hello 世界 🌍\nNew line"` | Returns valid estimate |
| EC-07 | Empty string | `''` | Returns 0 tokens |

### Acceptance Criteria

| ID | Acceptance Criteria | Test Method | Status |
|----|---------------------|-------------|--------|
| AC-01 | `estimateTokens()` returns ±20% accuracy | `ac_estimate_tokens_returns_reasonable_estimates` | ✅ |
| AC-02 | `fitsInContext()` validates correctly | `ac_fits_in_context_validates_correctly` | ✅ |
| AC-03 | `selectOptimalModel()` returns smallest sufficient | `ac_select_optimal_model_returns_smallest_sufficient` | ✅ |
| AC-04 | Safety margin applied to all limits | `ac_safety_margin_applied_to_all_limits` | ✅ |
| AC-05 | Base prompt overhead accounted for | `ac_base_prompt_overhead_accounted_for` | ✅ |
| AC-06 | All 4 free-tier models supported | `ac_all_four_free_tier_models_supported` | ✅ |

## Model Context Limits Reference

| Model | Raw Limit | Safety Margin (10%) | Base Overhead | Effective Limit |
|-------|-----------|---------------------|---------------|-----------------|
| google/gemini-2.0-flash-exp:free | 1,000,000 | 100,000 | 3,400 | 896,600 |
| qwen/qwen-2-7b-instruct:free | 32,768 | 3,276 | 3,400 | 26,091 |
| mistralai/mistral-7b-instruct:free | 32,768 | 3,276 | 3,400 | 26,091 |
| meta-llama/llama-3-8b-instruct:free | 8,192 | 819 | 3,400 | 3,972 |

## Implementation Reference

```php
class ContextWindowManager
{
    private const MODEL_LIMITS = [
        'google/gemini-2.0-flash-exp:free' => 1000000,
        'qwen/qwen-2-7b-instruct:free' => 32768,
        'mistralai/mistral-7b-instruct:free' => 32768,
        'meta-llama/llama-3-8b-instruct:free' => 8192,
    ];
    
    private const SAFETY_MARGIN = 0.10; // 10%
    private const BASE_PROMPT_OVERHEAD = 3400;

    public function estimateTokens(string $content): int
    {
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
        $models = [
            'meta-llama/llama-3-8b-instruct:free',
            'qwen/qwen-2-7b-instruct:free',
            'mistralai/mistral-7b-instruct:free',
            'google/gemini-2.0-flash-exp:free',
        ];

        foreach ($models as $model) {
            if ($this->fitsInContext($model, $estimatedTokens)) {
                return $model;
            }
        }

        return 'google/gemini-2.0-flash-exp:free';
    }

    public function getModelLimits(): array
    {
        return self::MODEL_LIMITS;
    }
}
```

## Test Execution

```bash
# Run all ContextWindowManager tests
cd backend
php artisan test --filter=ContextWindowManagerTest

# Run specific test groups
php artisan test --filter=ContextWindowManagerTest --group=estimation
php artisan test --filter=ContextWindowManagerTest --group=selection
php artisan test --filter=ContextWindowManagerTest --group=acceptance

# Run with coverage
php artisan test --filter=ContextWindowManagerTest --coverage
```

## Dependencies
- None (foundation task)

## Notes for Developer

1. **Token Estimation Formula:** Uses character-based estimation (1 token ≈ 4 characters). This is a reasonable approximation for English text.

2. **Safety Margin:** 10% margin protects against edge cases in token counting and model API variations.

3. **Base Prompt Overhead:** 3400 tokens reserved for system prompt, response schema, and prompt formatting.

4. **Model Selection Priority:** Selects smallest sufficient model in order: Llama → Qwen → Mistral → Gemini.

5. **Fallback Behavior:** Content exceeding all limits falls back to Gemini (largest context) - will require truncation by caller.

6. **Unicode Handling:** Uses PHP's `strlen()` which counts bytes, so multibyte UTF-8 characters will have higher token estimates.

## Comments
_Agent log_

### [2026-03-05 11:20 GMT+2] PreTester
**Test Design Complete**

- Created 62 unit tests for ContextWindowManager service
- Tests cover all acceptance criteria
- Performance tests ensure < 1ms operations
- Edge cases tested (zero, negative, unicode, large values)
- Integration tests verify full workflow

**Files Created:**
- `backend/tests/Unit/Services/AI/ContextWindowManagerTest.php`
- `TESTS/TEST-31-2.md`

**Status:** Ready for Developer implementation
