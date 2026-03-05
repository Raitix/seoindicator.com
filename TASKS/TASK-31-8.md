# TASK-31-8: ModelSelectionService

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-31: Unified AI Prompt + Context Management

## Description
Create the `ModelSelectionService` that chooses the optimal AI model based on content size, user preferences, and availability. Integrates with the existing model pricing system from FEAT-026.

## Component Location
- **Service:** `backend/app/Services/AI/ModelSelectionService.php`
- **Tests:** `backend/tests/Unit/Services/AI/ModelSelectionServiceTest.php`

## Core Functionalities

| Method | Description |
|--------|-------------|
| `selectModel(int $estimatedTokens, ?User $user = null): string` | Choose optimal model for content |
| `getUserPreference(User $user): ?string` | Get user's preferred model |
| `getFallbackModel(): string` | Return default fallback model |
| `isModelAvailable(string $model): bool` | Check if model is available |

## Model Selection Logic

```php
class ModelSelectionService
{
    private const MODELS_BY_CONTEXT = [
        // Large context (1M+) - for large pages
        'google/gemini-2.0-flash-exp:free' => 1000000,
        // Medium context (32K)
        'qwen/qwen-2-7b-instruct:free' => 32768,
        'mistralai/mistral-7b-instruct:free' => 32768,
        // Small context (8K)
        'meta-llama/llama-3-8b-instruct:free' => 8192,
    ];
    
    private const DEFAULT_MODEL = 'google/gemini-2.0-flash-exp:free';
    
    public function __construct(
        private ContextWindowManager $contextManager,
        private ModelPricingService $pricingService,
    ) {}
    
    public function selectModel(int $estimatedTokens, ?User $user = null): string
    {
        // 1. Check user preference (if set)
        if ($user) {
            $preferred = $this->getUserPreference($user);
            if ($preferred && $this->contextManager->fitsInContext($preferred, $estimatedTokens)) {
                return $preferred;
            }
        }
        
        // 2. Select smallest sufficient model (cost optimization)
        foreach (self::MODELS_BY_CONTEXT as $model => $contextLimit) {
            if ($this->contextManager->fitsInContext($model, $estimatedTokens)) {
                // Prefer smaller context for same-tier models
                if ($contextLimit < 100000 && $estimatedTokens < 30000) {
                    return $model;
                }
            }
        }
        
        // 3. Fallback to largest context
        return self::DEFAULT_MODEL;
    }
    
    public function getUserPreference(User $user): ?string
    {
        // Check UserModelPreference from FEAT-026
        $preference = UserModelPreference::where('user_id', $user->id)
            ->where('is_default', true)
            ->first();
        
        return $preference?->model?->model_id;
    }
}
```

## Model Priority Matrix

| Content Size | Primary Model | Fallback | Notes |
|--------------|---------------|----------|-------|
| < 8K tokens | Llama 3 8B | Mistral 7B | Fastest, smallest |
| 8K - 32K | Mistral 7B | Qwen 2 | Medium context |
| 32K - 1M | Gemini 2.0 Flash | N/A | Large context |
| > 1M | Gemini + Truncation | - | Requires truncation |

## Integration with FEAT-026

```php
// Leverage existing ai_models table
public function getAvailableModels(): Collection
{
    return AIModel::where('is_active', true)
        ->where('is_free_tier', true)
        ->orderBy('context_window', 'asc')
        ->get();
}

// Consider user's credit balance if using paid models
public function canUseModel(User $user, string $model): bool
{
    if ($this->isFreeModel($model)) {
        return true;
    }
    
    $cost = $this->pricingService->getEstimatedCost($model);
    return $user->credit_balance >= $cost;
}
```

## Acceptance Criteria
- [ ] `selectModel()` returns optimal model for content size
- [ ] User preferences respected when content fits
- [ ] Falls back gracefully when preferred model too small
- [ ] Free-tier models prioritized
- [ ] Integration with existing model pricing (FEAT-026)
- [ ] Model availability checked

## Dependencies
- TASK-31-2 (ContextWindowManager)
- TASK-26-16 (ModelPricingService - optional)
- TASK-26-19 (UserModelPreference - optional)

## Effort Estimate
2 hours

## Comments
_Agent log_

### [2026-03-05 18:55 GMT+2] ProjectManager
**Prioritization Complete**

**Priority Upgraded:** Medium → HIGH
**Risk:** LOW (confirmed)
**Status:** `To Write Tests` - Ready for PreTester

**Dependency Analysis - ALL SATISFIED:**
| Dependency | Status | Implementation |
|------------|--------|----------------|
| TASK-31-2 (ContextWindowManager) | Done ✅ | `app/Services/AI/ContextWindowManager.php` |
| TASK-26-16 (ModelPricingService) | Done ✅ | `app/Services/Billing/ModelPricingService.php` |
| TASK-26-19 (UserModelPreference) | Done ✅ | `app/Models/UserModelPreference.php` |

**Note:** TASK-26-16.md is empty (0 bytes) but implementation exists at `app/Services/Billing/ModelPricingService.php`.

**Priority Rationale:**
1. **Dependencies ALL SATISFIED** - All 3 dependencies are fully implemented and deployed
2. **Foundation Task for SPEC-031** - Model selection is essential for unified AI analysis
3. **Cost Optimization** - Selecting optimal model reduces AI API costs
4. **User Experience Impact** - Automatic model selection improves analysis workflow
5. **Integration Layer** - Bridges ContextWindowManager with billing (FEAT-026)

**TDD Approach:**
PreTester can design tests immediately using existing interfaces:
- `ContextWindowManager` (TASK-31-2) - `fitsInContext()` method available
- `ModelPricingService` (TASK-26-16) - `getEstimatedCost()` method available
- `UserModelPreference` (TASK-26-19) - Model with `is_default` scope

**Key Implementation Points:**
1. User preference respected when content fits context window
2. Falls back to optimal model when preferred model insufficient
3. Free-tier models prioritized (cost optimization)
4. Model availability checked via AIModel active status
5. Credit balance check for paid models (future enhancement)

**Model Selection Priority:**
1. User preference (if fits context)
2. Smallest sufficient model (cost optimization)
3. Fallback to Gemini 2.0 Flash (largest context)

**Handoff to:** PreTester for test design
