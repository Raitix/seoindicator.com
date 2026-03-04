# TASK-21-9: Implement Vercel AI Provider

## STATUS
`Tests Complete`

## PRIORITY
`Medium`

## RISK
`High`

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

### [2026-03-04 15:12 GMT+2] ProjectManager - Prioritization Complete
**Priority Confirmed: Medium** ✅
**Risk Upgraded: Medium → High** ✅

**Rationale for Priority (Medium):**
- **Phase 3 Enhancement** - Not on critical path for MVP
- **Alternative Provider** - OpenRouter is primary, Vercel is secondary/fallback
- **Optional Feature** - System works without Vercel provider
- **Deferable** - Can be implemented after core functionality is stable

**Rationale for Risk (High):**
- **External API Dependency** - Vercel AI API availability, rate limits, free tier limits
- **HTTP Client Implementation** - Same complexity as OpenRouter (marked High risk)
- **Error Handling** - Rate limiting, timeouts, malformed responses
- **Free Tier Compliance** - Must handle Vercel free tier limitations gracefully

**Dependency Analysis:**
| Task | Status | Ready? |
|------|--------|--------|
| TASK-21-1 (AI Service Structure) | `Done` | ✅ Complete |
| TASK-21-2 (OpenRouter Provider) | `Done` | ✅ Reference implementation |
| TASK-21-5 (AI Suggestion API) | `Done` | ✅ Endpoint ready |

**All Dependencies Complete!** This task can proceed immediately.

**Implementation Notes:**
- Use OpenRouterProvider (TASK-21-2) as reference implementation
- Implement `AIProviderInterface` from TASK-21-1
- Configuration scaffolded in `config/ai.php`
- Free tier models: `gpt-4o-mini`, `claude-3-haiku`

**Execution Order within SPEC-021:**
- Phase 1 (TASK-21-1 to 21-4): Done ✅
- Phase 2 (TASK-21-5 to 21-8): In Progress
- Phase 3 (TASK-21-9, 21-11): This task

**Handoff to PreTester:** Ready for test design. Focus on:
- HTTP client mocking for Vercel AI API
- Configuration validation (`VERCEL_AI_API_KEY`, `VERCEL_AI_MODEL`)
- Response parsing from Vercel format
- Error handling (rate limits, timeouts, free tier limits)
- Availability check logic
- Free tier limitation handling

Status: `To Prioritize` → `To Write Tests`

### [2026-03-04 16:40 GMT+2] Agent: PreTester
**Test Design Complete.**

Created comprehensive test suite for VercelAIProvider:

**Test File:** `backend/tests/Unit/Services/AI/Providers/VercelAIProviderTest.php`

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| AC-1: Class Creation (VRC) | 8 | Class existence, interface implementation, constructor, DI |
| AC-2: Configuration (CFG) | 6 | Env variables, model config, timeout config |
| AC-3: API Integration (API) | 12 | POST requests, headers, body format, options |
| AC-4: Response Parsing (RES) | 10 | JSON parsing, content extraction, token tracking |
| AC-5: Error Handling (ERR) | 15 | All HTTP codes, timeout, malformed responses |
| AC-5: Provider Selection (SEL) | 3 | Config selection, fallback, roles |
| AC-6: Free Tier (FTC) | 4 | Free tier models, graceful degradation |
| Availability (AVL) | 6 | API key validation, availability checks |
| **Total** | **64** | **Comprehensive coverage** |

**Test Documentation:** `PROJECT-SEOINDICATOR/TESTS/TEST-21-9.md`

**Key Design Decisions:**
1. **Follow OpenRouterProvider Pattern** - Same structure, adapted for Vercel AI API
2. **HTTP Mocking** - All tests use `Http::fake()` for deterministic results
3. **Vercel API Format** - Same as OpenAI (choices array, usage object)
4. **Free Tier Focus** - Tests validate gpt-4o-mini and claude-3-haiku models
5. **Error Coverage** - All HTTP codes from 400 to 503 plus connection failures

**Coverage Targets:**
- Class Instantiation: 100%
- Interface Implementation: 100%
- API Integration: 100%
- Response Parsing: 100%
- Error Handling: 100%
- Availability: 100%
- Configuration: 100%
- **Minimum Overall: 80%**

**Next Steps for Developer:**
1. Implement `VercelAIProvider.php` to pass all tests
2. Configuration already scaffolded in `config/ai.php`
3. Add `getMaxTokens()` method (referenced in tests)
4. Ensure provider name returns 'vercel'

Status: `To Write Tests` → `Tests Complete`

**Handoff to Developer:** All tests ready for implementation.
