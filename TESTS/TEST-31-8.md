# TEST-31-8: ModelSelectionService Tests

## Task Reference
- **Task:** [TASK-31-8](../TASKS/TASK-31-8.md)
- **Specification:** SPEC-31: Unified AI Prompt + Context Management
- **Feature:** FEAT-031

## Overview
Comprehensive unit tests for `ModelSelectionService` that chooses the optimal AI model based on content size, user preferences, and availability.

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Services/AI/ModelSelectionServiceTest.php` | Unit | Service logic tests |

## Test Scenarios

### Model Selection Tests (MS)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MS-001 | Select smallest model for tiny content | 500 tokens | `meta-llama/llama-3-8b-instruct:free` |
| MS-002 | Select Llama for content < 4K tokens | 3000 tokens | `meta-llama/llama-3-8b-instruct:free` |
| MS-003 | Select Qwen for 4K-26K tokens | 10000 tokens | `qwen/qwen-2-7b-instruct:free` |
| MS-004 | Select Gemini for 26K-896K tokens | 50000 tokens | `google/gemini-2.0-flash-exp:free` |
| MS-005 | Fallback to Gemini for > 896K tokens | 1000000 tokens | `google/gemini-2.0-flash-exp:free` |
| MS-006 | Select Llama at exact Llama boundary | 3972 tokens | `meta-llama/llama-3-8b-instruct:free` |
| MS-007 | Select Qwen just over Llama boundary | 3973 tokens | `qwen/qwen-2-7b-instruct:free` |
| MS-008 | Select Gemini just over Qwen boundary | 26092 tokens | `google/gemini-2.0-flash-exp:free` |
| MS-009 | Handle zero tokens | 0 tokens | `meta-llama/llama-3-8b-instruct:free` |

### User Preference Tests (UP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UP-001 | Respect user preference when fits | User prefers Qwen, 5000 tokens | `qwen/qwen-2-7b-instruct:free` |
| UP-002 | Ignore preference when too small | User prefers Llama, 10000 tokens | `qwen/qwen-2-7b-instruct:free` |
| UP-003 | Use preference for Gemini | User prefers Gemini, 5000 tokens | `google/gemini-2.0-flash-exp:free` |
| UP-004 | No preference returns optimal | No user preference, 5000 tokens | `qwen/qwen-2-7b-instruct:free` |
| UP-005 | Null user returns optimal | null user, 5000 tokens | `qwen/qwen-2-7b-instruct:free` |
| UP-006 | Preference for unavailable model | User prefers inactive model | Optimal model selected |
| UP-007 | Preference for non-existent model | User prefers unknown model | Optimal model selected |

### Model Availability Tests (MA)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MA-001 | Active model is available | `google/gemini-2.0-flash-exp:free` | `true` |
| MA-002 | Inactive model not available | Inactive model ID | `false` |
| MA-003 | Unknown model not available | `unknown/model` | `false` |
| MA-004 | Free tier model available | Free model ID | `true` |
| MA-005 | All supported models available | All 4 models | `true` for each |

### Fallback Model Tests (FM)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FM-001 | Fallback returns Gemini | - | `google/gemini-2.0-flash-exp:free` |
| FM-002 | Fallback is consistent | Multiple calls | Same result |

### Context Integration Tests (CI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CI-001 | Integrates with ContextWindowManager | Various token counts | Correct model selected |
| CI-002 | Respects context limits | Boundary values | Proper fit validation |

### Model Priority Matrix Tests (MP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MP-001 | < 8K tokens selects Llama | 5000 tokens | Llama |
| MP-002 | 8K-32K tokens selects Qwen | 15000 tokens | Qwen |
| MP-003 | 32K-1M tokens selects Gemini | 100000 tokens | Gemini |
| MP-004 | > 1M tokens selects Gemini with truncation | 2000000 tokens | Gemini |

### Edge Cases (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Negative tokens handled | -100 tokens | Llama (smallest) |
| EC-002 | Very large tokens | 10000000 tokens | Gemini (fallback) |
| EC-003 | Exactly at Gemini limit | 896600 tokens | Gemini |
| EC-004 | One over Gemini limit | 896601 tokens | Gemini (truncation needed) |
| EC-005 | Empty user object | User with no preferences | Optimal model |

### Acceptance Criteria Tests (AC)

| ID | Acceptance Criteria | Test |
|----|---------------------|------|
| AC-001 | `selectModel()` returns optimal model for content size | MS-001 to MS-009 |
| AC-002 | User preferences respected when content fits | UP-001 to UP-003 |
| AC-003 | Falls back gracefully when preferred model too small | UP-002 |
| AC-004 | Free-tier models prioritized | All tests use free models |
| AC-005 | Integration with existing model pricing (FEAT-026) | CI-001, CI-002 |
| AC-006 | Model availability checked | MA-001 to MA-005 |

## Mock Data

### Mock AI Models
```php
$models = [
    [
        'model_id' => 'google/gemini-2.0-flash-exp:free',
        'display_name' => 'Gemini 2.0 Flash',
        'context_window' => 1000000,
        'is_free_tier' => true,
        'is_active' => true,
    ],
    [
        'model_id' => 'qwen/qwen-2-7b-instruct:free',
        'display_name' => 'Qwen 2 7B',
        'context_window' => 32768,
        'is_free_tier' => true,
        'is_active' => true,
    ],
    [
        'model_id' => 'mistralai/mistral-7b-instruct:free',
        'display_name' => 'Mistral 7B',
        'context_window' => 32768,
        'is_free_tier' => true,
        'is_active' => true,
    ],
    [
        'model_id' => 'meta-llama/llama-3-8b-instruct:free',
        'display_name' => 'Llama 3 8B',
        'context_window' => 8192,
        'is_free_tier' => true,
        'is_active' => true,
    ],
];
```

### Mock User Preferences
```php
$preference = [
    'user_id' => 1,
    'model_id' => 2, // Qwen
    'is_default' => true,
];
```

## Coverage Targets

| Method | Target Coverage |
|--------|-----------------|
| `selectModel()` | 100% |
| `getUserPreference()` | 100% |
| `getFallbackModel()` | 100% |
| `isModelAvailable()` | 100% |
| Overall | ≥ 95% |

## Test Execution

```bash
# Run all ModelSelectionService tests
cd backend
php artisan test --filter=ModelSelectionServiceTest

# Run with coverage
php artisan test --filter=ModelSelectionServiceTest --coverage

# Run specific test group
php artisan test --filter=ModelSelectionServiceTest --group=selection
```

## Dependencies

| Dependency | Type | Status |
|------------|------|--------|
| ContextWindowManager | Service | ✅ Implemented (TASK-31-2) |
| ModelPricingService | Service | ✅ Implemented (TASK-26-16) |
| UserModelPreference | Model | ✅ Implemented (TASK-26-19) |
| AIModel | Model | ✅ Existing |

## Notes for Developer

1. **Model Selection Priority:**
   - User preference (if fits context window)
   - Smallest sufficient model (cost optimization)
   - Fallback to Gemini 2.0 Flash (largest context)

2. **Context Limits with Safety Margin:**
   - Llama: 3,972 effective tokens (8,192 * 0.9 - 3400)
   - Qwen/Mistral: 26,091 effective tokens (32,768 * 0.9 - 3400)
   - Gemini: 896,600 effective tokens (1,000,000 * 0.9 - 3400)

3. **Free-Tier Only:**
   - All models in selection pool are free-tier
   - No credit balance checks needed for current scope
   - Future: Add paid model support with balance checks

4. **Integration Points:**
   - `ContextWindowManager::fitsInContext()` for validation
   - `ModelPricingService::getUserDefaultModel()` for preferences
   - `AIModel` model for availability checks

---

## Comments
_Agent log_

### [2026-03-05 19:10 GMT+2] PreTester
**Test Design Complete**

Created comprehensive test documentation covering:
- 9 model selection scenarios
- 7 user preference scenarios
- 5 model availability scenarios
- 2 fallback scenarios
- 2 context integration scenarios
- 4 model priority matrix scenarios
- 5 edge cases
- 6 acceptance criteria mappings

Total: 40 test scenarios across 8 categories.

**Next Steps:**
1. Developer implements `ModelSelectionService.php`
2. Run tests to validate implementation
3. LocalTester validates locally

**Handoff to:** Developer for implementation
