# TASK-26-4: Create AI Models Table with OpenRouter Sync

## STATUS
`In Development`

## PRIORITY
`High`

## RISK
`Medium`

## Related Specification
SPEC-026: Billing, Credits & Model Selection

## Related Feature
FEAT-026: Billing, Credits & Model Selection

---

## Description
Create database migration for AI models table and implement sync service to fetch pricing from OpenRouter API.

## Acceptance Criteria

### ai_models Table
- [ ] `id` - BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- [ ] `provider` - VARCHAR(100) NOT NULL (e.g., 'openrouter', 'openai')
- [ ] `model_id` - VARCHAR(255) NOT NULL (e.g., 'openai/gpt-4-turbo')
- [ ] `display_name` - VARCHAR(255) NOT NULL (user-friendly name)
- [ ] `cost_per_1k_input` - DECIMAL(10, 6) NOT NULL DEFAULT 0.000000
- [ ] `cost_per_1k_output` - DECIMAL(10, 6) NOT NULL DEFAULT 0.000000
- [ ] `context_window` - INT UNSIGNED NOT NULL DEFAULT 4096
- [ ] `is_free_tier` - TINYINT(1) DEFAULT 0 (OpenRouter free tier)
- [ ] `free_tier_limit` - INT UNSIGNED NULL (requests per day)
- [ ] `capabilities` - JSON NULL (e.g., vision, tools)
- [ ] `is_active` - TINYINT(1) DEFAULT 1
- [ ] `last_synced_at` - TIMESTAMP NULL
- [ ] `created_at` and `updated_at` timestamps

### Indexes
- [ ] UNIQUE index on `(provider, model_id)`
- [ ] Index on `is_active`

### AIModel Model
- [ ] `app/Models/AIModel.php` created
- [ ] Scope for active models
- [ ] Scope for free tier models
- [ ] Accessor for cost per analysis estimate

### OpenRouter Sync Service
- [ ] `app/Services/OpenRouterSyncService.php` created
- [ ] `syncModels(): int` - Fetch models from OpenRouter API
- [ ] `getModelPricing(string $modelId): array` - Get pricing for specific model
- [ ] API endpoint: `https://openrouter.ai/api/v1/models`
- [ ] Parse pricing, context window, capabilities
- [ ] Update existing models or create new ones
- [ ] Return count of synced models

### Console Command
- [ ] `php artisan models:sync` command to trigger sync
- [ ] Can be scheduled via Laravel scheduler

## Dependencies
- None (independent table)
- Requires `OPENROUTER_API_KEY` in .env

## Effort Estimate
`Medium` (3-4 hours)

## Notes
- OpenRouter API is FREE to query
- Sync should be idempotent (run multiple times safely)
- Free tier models have daily limits per OpenRouter policy
- Capabilities JSON allows filtering by vision, tools, etc.

## Security Considerations
- API key stored in .env (not in code)
- Rate limit sync calls to avoid abuse
- Validate API responses before storing
- No user input affects model data

## Environment Variables Required
```env
OPENROUTER_API_KEY=sk-or-...
```

## Comments
_Leave notes for the next agent here_

### 2026-03-02 - ProjectManager
**Prioritization Complete**

**Priority: High (Confirmed)**
- Foundation task for billing MVP
- Creates ai_models table that TASK-26-16 (ModelPricingService) depends on
- Phase 1 infrastructure - enables Phase 5 Model Selection

**Risk: Medium (Confirmed)**
- External API (OpenRouter) integration
- Free to query - no cost concerns
- Standard migration + model + service pattern
- No payment data or security-sensitive operations

**Dependencies:**
- None - independent table (can proceed immediately)
- Requires: `OPENROUTER_API_KEY` in .env (PreTester should verify this exists or is documented)

**Enables:**
- TASK-26-16 (ModelPricingService) - needs OpenRouterSyncService
- TASK-26-17 (Models API) - reads from ai_models table

**Execution Notes:**
- Sync should be idempotent (can run multiple times safely)
- Console command `php artisan models:sync` can be scheduled
- Free tier models have daily limits per OpenRouter policy

**Status: Ready for PreTester**

### 2026-03-02 - PreTester
**Tests Complete - Ready for Developer**

**Test Suite Summary:**
- **Total Tests:** 177 tests across 3 files
- **AIModelTest.php:** 82 tests (model creation, casts, scopes, accessors, indexes, edge cases)
- **OpenRouterSyncServiceTest.php:** 70 tests (API sync, pricing parsing, capabilities, error handling)
- **ModelsSyncCommandTest.php:** 25 tests (command execution, options, scheduling)

**Test Files Created:**
1. `backend/tests/Unit/Models/AIModelTest.php`
2. `backend/tests/Unit/Services/OpenRouterSyncServiceTest.php`
3. `backend/tests/Feature/Console/ModelsSyncCommandTest.php`
4. `TESTS/TASK-26-4.md` (documentation)

**Key Test Coverage:**
- Model creation with required fields and full data
- DECIMAL(10,6) precision for pricing columns
- JSON cast for capabilities array
- Unique index on (provider, model_id)
- Index on is_active
- Scopes: active, freeTier, byProvider, ordered
- Accessor: cost_per_analysis_estimate
- OpenRouter API sync with HTTP mocking
- Pricing parsing (per-token to per-1k conversion)
- Context window parsing with fallback
- Capabilities detection (vision, tools)
- Free tier model detection
- API error handling (500, 429, 504, malformed response)
- XSS sanitization
- Console command options (--force, --provider, --quiet)

**Factory Already Exists:**
- `backend/database/factories/AIModelFactory.php` with states:
  - `freeTier()` - Free tier model
  - `inactive()` - Inactive model
  - `withVision()` - Vision-capable model
  - `gpt4()` - GPT-4 style model
  - `claude()` - Claude style model
  - `llamaFree()` - Llama free tier model

**Notes for Developer:**
1. AIModelFactory already exists - no need to create
2. Tests use `Http::fake()` for API mocking
3. Tests assume `OPENROUTER_API_KEY` in config/services.php
4. Migration should include unique index on (provider, model_id)
5. Model should have $casts for DECIMAL, JSON, BOOLEAN, INT

**Status: To Develop**
