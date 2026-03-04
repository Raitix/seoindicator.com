# TASK-21-9: Implement Vercel AI Provider

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Medium`

## Specification
[SPEC-021](../SPECIFICATIONS/SPEC-021.md) - AI Suggestions via OpenRouter/Vercel Gateway

## Description
Implement Vercel AI Gateway provider as an alternative to OpenRouter. This provides redundancy and allows users to choose their preferred AI provider.

---

## Acceptance Criteria

### AC-1: VercelAIProvider Class Created
- [ ] File exists at `backend/app/Services/AI/Providers/VercelAIProvider.php`
- [ ] Implements `AIProviderInterface`
- [ ] Method `generate(string $prompt, array $options = []): AIResponse`
- [ ] Method `isAvailable(): bool`
- [ ] Method `getName(): string`

### AC-2: Configuration Support
- [ ] `VERCEL_AI_API_KEY` env variable supported
- [ ] `VERCEL_AI_MODEL` env variable supported (default: gpt-4o-mini)
- [ ] Timeout configurable via `AI_TIMEOUT_SECONDS`
- [ ] Provider registered in `config/ai.php`

### AC-3: API Integration
- [ ] Uses Vercel AI SDK or HTTP client
- [ ] Proper authentication via API key
- [ ] Error handling for rate limits
- [ ] Timeout handling with fallback

### AC-4: Response Parsing
- [ ] Parse Vercel AI response format
- [ ] Extract text content from response
- [ ] Handle streaming vs non-streaming responses
- [ ] Token usage extraction

### AC-5: Provider Selection
- [ ] Can be selected via `AI_PROVIDER=vercel`
- [ ] Works as fallback to OpenRouter
- [ ] Supports both primary and secondary provider roles

### AC-6: Free Tier Compliance
- [ ] Works with Vercel free tier
- [ ] Documented free tier limitations
- [ ] Graceful degradation when limits hit

---

## Dependencies
- TASK-21-1: AI Service Structure (Done)
- TASK-21-2: OpenRouter Provider (Done) - reference implementation

---

## Effort Estimate
**Medium** (3-4 hours)

---

## Technical Notes

### Configuration
```php
// config/ai.php
'vercel' => [
    'api_key' => env('VERCEL_AI_API_KEY'),
    'model' => env('VERCEL_AI_MODEL', 'gpt-4o-mini'),
    'timeout' => env('AI_TIMEOUT_SECONDS', 30),
],
```

### Provider Interface Implementation
```php
class VercelAIProvider implements AIProviderInterface
{
    public function generate(string $prompt, array $options = []): AIResponse
    {
        // Use Vercel AI SDK or HTTP client
        // Handle response parsing
        // Return standardized AIResponse
    }
    
    public function isAvailable(): bool
    {
        return !empty(config('ai.providers.vercel.api_key'));
    }
    
    public function getName(): string
    {
        return 'vercel';
    }
}
```

### Free Tier Models
| Model | Notes |
|-------|-------|
| `gpt-4o-mini` | Recommended default |
| `claude-3-haiku` | Alternative |

---

## Comments
_Agent log_

### [2026-03-04 13:35 GMT+2] Agent: ProjectManager
Task file created from SPEC-021 specification. Previously empty file (0 bytes).
This task was never properly created by SpecSystemAnalyst - file existed but had no content.
Status set to `To Prioritize` for normal workflow processing.

---
*Created from SPEC-021 on 2026-03-04*
