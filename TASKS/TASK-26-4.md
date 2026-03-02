# TASK-26-4: Create AI Models Table with OpenRouter Sync

## STATUS
`To Prioritize`

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
