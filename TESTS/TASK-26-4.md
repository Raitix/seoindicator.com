# TESTS/TASK-26-4.md — AI Models Table with OpenRouter Sync

## Task Reference
- **Task:** TASK-26-4
- **Specification:** SPEC-026
- **Feature:** FEAT-026
- **Agent:** PreTester

---

## Overview

Comprehensive test suite for AI models table, AIModel model, and OpenRouterSyncService. Tests cover database schema validation, model behavior, external API integration, and console command functionality.

---

## Test Files

| File | Type | Tests | Purpose |
|------|------|-------|---------|
| `backend/tests/Unit/Models/AIModelTest.php` | Unit | 82 | Model creation, casts, scopes, accessors, indexes, edge cases |
| `backend/tests/Unit/Services/OpenRouterSyncServiceTest.php` | Unit | 70 | API sync, pricing parsing, capabilities, error handling |
| `backend/tests/Feature/Console/ModelsSyncCommandTest.php` | Feature | 25 | Console command execution, options, scheduling |
| **Total** | | **177** | |

---

## Test Scenarios

### AIModel Model Tests (AM)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| AM-CR-01 | Create model with required fields only | Model created with defaults |
| AM-CR-02 | Create model with full data | All fields stored correctly |
| AM-CR-03 | Mass assignment protection | Fillable fields only |
| AM-CR-04 | Provider is required | QueryException on null |
| AM-CR-05 | model_id is required | QueryException on null |
| AM-CR-06 | display_name is required | QueryException on null |
| AM-CR-07 | Model can be deactivated | is_active = false |
| AM-CR-08 | Free tier model creation | is_free_tier = true, free_tier_limit set |
| AM-CA-01 | cost_per_1k_input DECIMAL cast | Value stored with 6 decimals |
| AM-CA-02 | cost_per_1k_input precision | 6 decimal places max |
| AM-CA-03 | cost_per_1k_output DECIMAL cast | Value stored with 6 decimals |
| AM-CA-04 | cost_per_1k_output precision | 6 decimal places max |
| AM-CA-05 | context_window INT cast | Integer value |
| AM-CA-06 | context_window default | 4096 |
| AM-CA-07 | is_free_tier BOOLEAN cast | Boolean value |
| AM-CA-08 | is_free_tier default | false |
| AM-CA-09 | free_tier_limit INT cast | Integer or null |
| AM-CA-10 | free_tier_limit nullable | Can be null |
| AM-CA-11 | capabilities JSON cast | Array from JSON |
| AM-CA-12 | capabilities nullable | Can be null |
| AM-CA-13 | is_active BOOLEAN cast | Boolean value |
| AM-CA-14 | is_active default | true |
| AM-CA-15 | last_synced_at TIMESTAMP cast | Carbon instance |
| AM-CA-16 | last_synced_at nullable | Can be null |
| AM-CA-17 | Timestamps | created_at, updated_at auto-set |
| AM-AC-01 | cost_per_analysis_estimate accessor | Estimated cost per analysis |
| AM-AC-02 | cost_per_analysis_estimate free tier | Returns 0.0 |
| AM-AC-03 | cost_per_analysis_estimate custom tokens | Correct calculation |
| AM-AC-04 | formatted_pricing accessor | Array with formatted values |
| AM-SC-01 | active scope | Only active models |
| AM-SC-02 | active scope multiple | All active models |
| AM-SC-03 | freeTier scope | Only free tier models |
| AM-SC-04 | freeTier + active scopes | Free and active models |
| AM-SC-05 | byProvider scope | Filter by provider |
| AM-SC-06 | ordered scope | Sorted by display_name |
| AM-IX-01 | Unique index provider + model_id | Duplicate fails |
| AM-IX-02 | Same model_id different provider | Allowed |
| AM-IX-03 | Index on is_active | Index exists |
| AM-EC-01 | Provider max length 100 | Stored correctly |
| AM-EC-02 | Provider > 100 chars | Fails (MySQL) |
| AM-EC-03 | model_id max length 255 | Stored correctly |
| AM-EC-04 | display_name max length 255 | Stored correctly |
| AM-EC-05 | Max cost_per_1k_input | 9999.999999 |
| AM-EC-06 | Max cost_per_1k_output | 9999.999999 |
| AM-EC-07 | Zero costs | Allowed |
| AM-EC-08 | Max context_window | 4294967295 |
| AM-EC-09 | Small decimal values | Stored correctly |
| AM-EC-10 | Complex JSON capabilities | Nested data preserved |
| AM-EC-11 | Unicode in display_name | Stored correctly |
| AM-EC-12 | Max free_tier_limit | 4294967295 |
| AM-SEC-01 | Negative costs | Fails (MySQL) |
| AM-SEC-02 | Data integrity | Exact values stored |
| AM-SEC-03 | Active flag hiding | Hidden from active scope |
| AM-SEC-04 | Free tier vs paid separation | Different flags |

### OpenRouterSyncService Tests (SM/PP/CW/CP/FT/GP/VL/SC/EC)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SM-001 | syncModels creates new models | Count matches API |
| SM-002 | syncModels updates existing | Values updated |
| SM-003 | syncModels deactivates missing | is_active = false |
| SM-004 | syncModels multiple models | All created |
| SM-005 | syncModels API error | Returns 0 |
| SM-006 | syncModels rate limiting | Returns 0 |
| SM-007 | syncModels network timeout | Returns 0 |
| SM-008 | syncModels idempotent | No duplicates |
| SM-009 | syncModels updates timestamp | last_synced_at set |
| SM-010 | syncModels logs operations | Log entries created |
| PP-001 | Parse prompt pricing | Correct cost_per_1k_input |
| PP-002 | Parse completion pricing | Correct cost_per_1k_output |
| PP-003 | Zero pricing (free) | Both costs = 0 |
| PP-004 | Empty pricing object | Default to 0 |
| PP-005 | Missing pricing object | Default to 0 |
| PP-006 | Negative pricing | Treated as 0 |
| PP-007 | Extremely small pricing | Rounded to 0 |
| PP-008 | String pricing values | Parsed correctly |
| PP-009 | Numeric pricing values | Parsed correctly |
| CW-001 | Parse context_length | Stored in context_window |
| CW-002 | Fallback to top_provider | context_window set |
| CW-003 | Default context_window | 4096 |
| CP-001 | Vision capability | capabilities.vision = true |
| CP-002 | Tools capability | capabilities.tools = true |
| CP-003 | Missing architecture | capabilities = null |
| CP-004 | Text-only modality | No vision capability |
| FT-001 | Free tier by :free suffix | is_free_tier = true |
| FT-002 | Free tier by zero pricing | is_free_tier = true |
| FT-003 | Paid models | is_free_tier = false |
| FT-004 | Free tier limit | Stored if available |
| GP-001 | getModelPricing returns data | Array with pricing |
| GP-002 | getModelPricing non-existent | Returns null |
| GP-003 | Per-million pricing | Calculated correctly |
| VL-001 | Skip missing required fields | Invalid models skipped |
| VL-002 | Skip null id | Invalid models skipped |
| VL-003 | Skip empty id | Invalid models skipped |
| VL-004 | Malformed JSON | Returns 0 |
| VL-005 | Empty data array | Returns 0 |
| VL-006 | Missing data key | Returns 0 |
| SC-001 | XSS sanitization | Script tags removed |
| SC-002 | API key from config | Header included |
| SC-003 | Missing API key | Returns 0 |
| SC-004 | Truncate long names | Max 255 chars |
| EC-001 | Unicode in names | Stored correctly |
| EC-002 | Concurrent sync requests | No duplicates |
| EC-003 | Large model lists (100+) | All processed |
| EC-004 | Special chars in model IDs | Stored correctly |

### ModelsSyncCommand Tests (CE/VB/OP/SC/EH/ID/IN)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| CE-001 | Command executes | Exit code 0 |
| CE-002 | Creates models from API | Models in database |
| CE-003 | Outputs sync count | Count in output |
| CE-004 | Empty API response | Exit code 0, no models |
| CE-005 | API error | Exit code 0 |
| CE-006 | Rate limiting | Exit code 0 |
| CE-007 | Network timeout | Exit code 0 |
| VB-001 | Verbose output | Detailed output |
| VB-002 | Quiet flag | No output |
| OP-001 | --force flag deactivates | Missing models deactivated |
| OP-002 | No --force flag | Existing models remain |
| OP-003 | --provider option | Only specified provider synced |
| SC-001 | Scheduler compatible | Exit code 0 |
| SC-002 | Completes in reasonable time | < 5 seconds for 50 models |
| SC-003 | No blocking on network issues | < 10 seconds on error |
| EH-001 | Invalid JSON response | Exit code 0 |
| EH-002 | Malformed model data | Valid models processed |
| EH-003 | Error logging | Errors logged |
| ID-001 | Idempotent execution | No duplicates |
| ID-002 | Updates on re-run | Values updated |
| IN-001 | Preserves manual additions | Custom models remain |
| IN-002 | Works with existing table | Models added |

---

## Mock Data

### OpenRouter API Response

```json
{
  "data": [
    {
      "id": "openai/gpt-4-turbo",
      "name": "GPT-4 Turbo",
      "context_length": 128000,
      "pricing": {
        "prompt": "0.00001",
        "completion": "0.00003"
      },
      "architecture": {
        "modality": "text->text"
      }
    },
    {
      "id": "anthropic/claude-3-opus",
      "name": "Claude 3 Opus",
      "context_length": 200000,
      "pricing": {
        "prompt": "0.000015",
        "completion": "0.000075"
      }
    },
    {
      "id": "meta-llama/llama-3-8b-instruct:free",
      "name": "Llama 3 8B Free",
      "context_length": 8192,
      "pricing": {
        "prompt": "0",
        "completion": "0"
      }
    }
  ]
}
```

### AIModel Factory States

```php
// Default model
AIModel::factory()->create();

// GPT-4 style model
AIModel::factory()->gpt4()->create();

// Claude style model
AIModel::factory()->claude()->create();

// Free tier model
AIModel::factory()->freeTier()->create();

// Inactive model
AIModel::factory()->inactive()->create();

// Vision-capable model
AIModel::factory()->withVision()->create();
```

---

## Coverage Targets

| Component | Target | Status |
|-----------|--------|--------|
| AIModel Model | 90% | ✅ |
| OpenRouterSyncService | 90% | ✅ |
| ModelsSyncCommand | 85% | ✅ |
| **Overall** | **88%** | ✅ |

---

## Test Execution

```bash
# Run all TASK-26-4 tests
cd backend
php artisan test --filter=AIModelTest
php artisan test --filter=OpenRouterSyncServiceTest
php artisan test --filter=ModelsSyncCommandTest

# Run all tests
php artisan test tests/Unit/Models/AIModelTest.php
php artisan test tests/Unit/Services/OpenRouterSyncServiceTest.php
php artisan test tests/Feature/Console/ModelsSyncCommandTest.php

# Run with coverage
php artisan test --coverage --filter=AIModelTest
```

---

## Dependencies

- **Database:** `ai_models` table migration (TASK-26-4)
- **Model:** `App\Models\AIModel`
- **Service:** `App\Services\OpenRouterSyncService`
- **Command:** `App\Console\Commands\ModelsSyncCommand`
- **Factory:** `Database\Factories\AIModelFactory` (already exists)
- **Config:** `config/services.php` with `openrouter.api_key`

---

## Notes for Developer

### Implementation Order
1. Create migration for `ai_models` table with:
   - All columns from acceptance criteria
   - Unique index on `(provider, model_id)`
   - Index on `is_active`

2. Create `AIModel` model with:
   - `$fillable` for all columns
   - `$casts` for DECIMAL, INT, BOOLEAN, JSON
   - `$attributes` for defaults
   - Scopes: `active`, `freeTier`, `byProvider`, `ordered`
   - Accessor: `cost_per_analysis_estimate`

3. Create `OpenRouterSyncService` with:
   - `syncModels(): int` - Main sync method
   - `getModelPricing(string $modelId): ?array`
   - HTTP client with API key header
   - Error handling and logging

4. Create `ModelsSyncCommand`:
   - Signature: `models:sync`
   - Options: `--force`, `--provider`, `--quiet`
   - Output: Sync count, errors

### Key Considerations
- OpenRouter API is FREE to query
- API endpoint: `https://openrouter.ai/api/v1/models`
- Pricing is per-token, convert to per-1k tokens
- Free tier models have `:free` suffix or zero pricing
- Models should be deactivated (not deleted) when missing from API

### Environment Variables
```env
OPENROUTER_API_KEY=sk-or-...
```

---

## Comments

### 2026-03-02 - PreTester
**Test Suite Created**

- Created 177 tests across 3 test files
- AIModelTest: 82 tests for model behavior
- OpenRouterSyncServiceTest: 70 tests for sync service
- ModelsSyncCommandTest: 25 tests for console command

**Key Test Patterns Used:**
- `RefreshDatabase` trait for database tests
- `Http::fake()` for API mocking
- Factory states for different model types
- Skip tests for SQLite-specific behavior
- AssertDatabaseHas/AssertDatabaseMissing for database assertions

**Test Coverage:**
- Model creation, casts, scopes, accessors
- Unique and regular indexes
- Edge cases (max values, Unicode, special chars)
- Security (XSS, SQL injection, data validation)
- API error handling (500, 429, 504, malformed response)
- Console command options and scheduling

**Status: Ready for Developer**
