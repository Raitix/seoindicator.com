# TASK-31-6: OpenRouter Client Service

## STATUS
`To Deploy`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-31: Unified AI Prompt + Context Management

## Description
Create an OpenRouter API client service that handles unified AI requests with proper authentication, error handling, timeouts, and response parsing. Extends the existing OpenRouter provider from TASK-21-2.

## Component Location
- **Service:** `backend/app/Services/AI/OpenRouterClient.php`
- **Config:** `backend/config/openrouter.php`
- **Tests:** `backend/tests/Unit/Services/AI/OpenRouterClientTest.php`

## Core Functionalities

| Method | Description |
|--------|-------------|
| `generate(string $prompt, array $options = []): AIResponse` | Send unified prompt to OpenRouter |
| `stream(string $prompt, callable $onChunk): void` | Stream response chunks |
| `getAvailableModels(): array` | Return list of available models |
| `getModelInfo(string $modelId): ?array` | Get model metadata |

## Request Configuration

```php
class OpenRouterClient
{
    private const BASE_URL = 'https://openrouter.ai/api/v1';
    private const DEFAULT_TIMEOUT = 120; // 2 minutes for large responses
    private const DEFAULT_MODEL = 'google/gemini-2.0-flash-exp:free';
    
    public function generate(string $prompt, array $options = []): AIResponse
    {
        $model = $options['model'] ?? self::DEFAULT_MODEL;
        $startTime = microtime(true);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('openrouter.api_key'),
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'SEOIndicator',
            'Content-Type' => 'application/json',
        ])
        ->timeout($options['timeout'] ?? self::DEFAULT_TIMEOUT)
        ->post(self::BASE_URL . '/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $options['max_tokens'] ?? 4000,
            'temperature' => $options['temperature'] ?? 0.3,
            'response_format' => ['type' => 'json_object'],
        ]);
        
        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
        
        if (!$response->successful()) {
            return AIResponse::error(
                $this->formatError($response),
                $latencyMs
            );
        }
        
        $data = $response->json();
        
        return AIResponse::success(
            content: $data['choices'][0]['message']['content'] ?? '',
            tokensUsed: $data['usage']['total_tokens'] ?? 0,
            latencyMs: $latencyMs,
            model: $model,
            promptTokens: $data['usage']['prompt_tokens'] ?? 0,
            responseTokens: $data['usage']['completion_tokens'] ?? 0,
        );
    }
}
```

## AIResponse DTO

```php
class AIResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $content,
        public readonly int $tokensUsed,
        public readonly int $latencyMs,
        public readonly ?string $errorMessage = null,
        public readonly ?string $model = null,
        public readonly int $promptTokens = 0,
        public readonly int $responseTokens = 0,
    ) {}
    
    public static function success(
        string $content,
        int $tokensUsed,
        int $latencyMs,
        string $model = null,
        int $promptTokens = 0,
        int $responseTokens = 0,
    ): self {
        return new self(
            success: true,
            content: $content,
            tokensUsed: $tokensUsed,
            latencyMs: $latencyMs,
            model: $model,
            promptTokens: $promptTokens,
            responseTokens: $responseTokens,
        );
    }
    
    public static function error(string $message, int $latencyMs): self
    {
        return new self(
            success: false,
            content: null,
            tokensUsed: 0,
            latencyMs: $latencyMs,
            errorMessage: $message,
        );
    }
}
```

## Error Handling

| HTTP Code | Error Type | Recovery |
|-----------|------------|----------|
| 400 | Bad Request | Log and throw `InvalidRequestException` |
| 401 | Unauthorized | Check API key configuration |
| 403 | Forbidden | Log and throw `AccessDeniedException` |
| 429 | Rate Limited | Return with retry-after header |
| 500-503 | Server Error | Retry with exponential backoff |
| Timeout | Timeout | Retry with smaller content |

## Free Models Configuration

```php
// config/openrouter.php
return [
    'api_key' => env('OPENROUTER_API_KEY'),
    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
    'default_model' => env('OPENROUTER_MODEL', 'google/gemini-2.0-flash-exp:free'),
    'timeout' => env('OPENROUTER_TIMEOUT', 120),
    'max_tokens' => env('OPENROUTER_MAX_TOKENS', 4000),
    
    'free_models' => [
        'google/gemini-2.0-flash-exp:free' => [
            'name' => 'Gemini 2.0 Flash',
            'context_window' => 1000000,
        ],
        'qwen/qwen-2-7b-instruct:free' => [
            'name' => 'Qwen 2 7B',
            'context_window' => 32768,
        ],
        'mistralai/mistral-7b-instruct:free' => [
            'name' => 'Mistral 7B',
            'context_window' => 32768,
        ],
        'meta-llama/llama-3-8b-instruct:free' => [
            'name' => 'Llama 3 8B',
            'context_window' => 8192,
        ],
    ],
];
```

## Acceptance Criteria
- [x] `generate()` sends prompt to OpenRouter API
- [x] Handles authentication via API key
- [x] Returns `AIResponse` DTO with all metadata
- [x] Proper error handling for all HTTP codes
- [x] Timeout configurable via environment
- [x] Supports all free-tier models
- [x] Logs request/response for debugging

## Dependencies
- TASK-21-2 (existing OpenRouter provider - extend or reuse)

## Effort Estimate
3 hours

## Comments
_Agent log_

### [2026-03-05 14:10 GMT+2] PreTester - Tests Coverage Complete ✅
**Test Design Complete - Ready for Developer**

**Test File Created:**
- `backend/tests/Unit/Services/AI/OpenRouterClientTest.php` (77 tests)

**Test Documentation:**
- `TESTS/TEST-31-6.md`

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Class Structure | 9 | Constructor, config, DI support |
| API Integration | 14 | HTTP requests, headers, body format |
| Response Handling | 12 | Content, tokens, latency, model |
| Error Handling | 14 | All HTTP codes, timeout, malformed |
| Timeout Config | 5 | Default/custom, validation |
| Free Models | 10 | getAvailableModels, getModelInfo |
| Stream Method | 3 | Callable, callback invocation |
| Security | 5 | API key protection, XSS, injection |
| Configuration | 5 | Config values, base URL |

**Total: 77 tests designed**

**Test Execution Status:**
- All tests fail as expected (class not implemented)
- Tests will pass when OpenRouterClient is properly implemented
- Reference: OpenRouterProviderTest patterns (72 tests passing)

**Developer Notes:**
1. Create `backend/app/Services/AI/OpenRouterClient.php`
2. Implement `generate()`, `stream()`, `getAvailableModels()`, `getModelInfo()`
3. Create `backend/config/openrouter.php` with free_models config
4. Consider extending AIResponse DTO for model/promptTokens/responseTokens fields

**Status:** `To Write Tests` → `To Develop`

### [2026-03-05 14:03 GMT+2] PreTester - Starting Test Design
Starting comprehensive test design for OpenRouterClient service.

**Analysis of Task Requirements:**
- OpenRouterClient is a NEW service (extends patterns from TASK-21-2 OpenRouterProvider)
- Core methods: `generate()`, `stream()`, `getAvailableModels()`, `getModelInfo()`
- Returns enhanced AIResponse DTO with model, promptTokens, responseTokens fields
- Error handling for all HTTP codes (400, 401, 403, 429, 500-503)
- Support for free-tier models configuration
- Configurable timeout via environment

**Test Design Approach:**
- Unit tests with HTTP mocking (following OpenRouterProviderTest patterns)
- Test categories: Class structure, API integration, response handling, error handling, configuration
- Reference existing tests: `backend/tests/Unit/Services/AI/Providers/OpenRouterProviderTest.php`

**Test Files to Create:**
- `backend/tests/Unit/Services/AI/OpenRouterClientTest.php`

**DTO Consideration:**
The existing AIResponse DTO may need extension for additional fields:
- `model` (string|null)
- `promptTokens` (int)
- `responseTokens` (int)

Starting test implementation...

### [2026-03-05 13:55 GMT+2] ProjectManager - Prioritization Complete
**Priority Confirmed: High** ✅
**Risk Confirmed: Medium** ✅

**Rationale for Priority:**
- Foundation service for SPEC-31 unified AI analysis feature
- Dependency for TASK-31-7 (AIAnalysisService - the main orchestrator)
- Extends existing TASK-21-2 OpenRouterProvider with focused client functionality
- Phase 1 critical path - enables downstream integration tasks

**Rationale for Risk:**
- External API dependency (OpenRouter availability, rate limits)
- Standard Laravel HTTP client patterns
- Error handling well-defined in task specification
- Existing OpenRouterProvider serves as proven reference implementation

**Dependency Analysis:**
- TASK-21-2 (OpenRouter Provider): `Done` ✅
  - Existing implementation at `backend/app/Services/AI/Providers/OpenRouterProvider.php`
  - 72 tests passing (unit + feature)
  - Deployed to production
  - Can extend or reuse patterns from this implementation

**Downstream Dependencies:**
- TASK-31-7 (AIAnalysisService): `To Prioritize` - blocked by this task
  - AIAnalysisService will use OpenRouterClient for unified AI calls

**SPEC-31 Phase Context:**
| Task | Status | Dependencies |
|------|--------|--------------|
| TASK-31-2 (ContextWindowManager) | Done ✅ | None |
| TASK-31-3 (TruncationService) | To Develop | TASK-31-2 ✅ |
| TASK-31-4 (UnifiedPromptService) | Tests Written | TASK-31-2 ✅ |
| TASK-31-5 (ResponseParser) | To Write Tests | None |
| TASK-31-6 (OpenRouterClient) | To Write Tests | TASK-21-2 ✅ |
| TASK-31-7 (AIAnalysisService) | To Prioritize | TASK-31-2, 31-3, 31-4, 31-5, 31-6 |

**TDD Ready:** PreTester can design tests immediately:
- Mock HTTP responses for OpenRouter API (`/api/v1/chat/completions`)
- Test `AIResponse` DTO with all metadata fields
- Test error handling for all HTTP codes (400, 401, 403, 429, 500-503)
- Test streaming vs non-streaming (non-streaming for MVP)
- Test free models configuration
- Reference existing tests: `backend/tests/Unit/Services/AI/Providers/OpenRouterProviderTest.php`

**Execution Order within SPEC-31:**
1. TASK-31-2, 31-5, 31-6: Can run in parallel (foundation services)
2. TASK-31-3, 31-4: In progress
3. TASK-31-7: After all foundation services ready

**Handoff to PreTester:** Ready for test design. Focus on:
- `generate()` method with prompt and options
- `stream()` method for chunked responses
- `getAvailableModels()` and `getModelInfo()` helpers
- `AIResponse` DTO with success/error states
- Free models configuration validation
- Error handling for rate limiting (429) with retry-after
- Timeout configuration via environment

Status: `To Prioritize` → `To Write Tests`

### [2026-03-05 14:48 GMT+2] LocalTester - All Tests Passed ✅
**Local Testing Complete - Ready for Deployment**

**Test Execution Results:**
- **Tests:** 77 passed
- **Assertions:** 102
- **Duration:** 8.85s
- **Status:** ALL PASS ✅

**Test Categories Verified:**
| Category | Tests | Status |
|----------|-------|--------|
| Class Structure | 9 | ✅ Pass |
| API Integration | 14 | ✅ Pass |
| Response Handling | 12 | ✅ Pass |
| Error Handling | 14 | ✅ Pass |
| Timeout Config | 5 | ✅ Pass |
| Free Models | 10 | ✅ Pass |
| Stream Method | 3 | ✅ Pass |
| Security | 5 | ✅ Pass |
| Configuration | 5 | ✅ Pass |

**Implementation Verified:**
- `generate()` method correctly sends prompts to OpenRouter API
- Authorization header format: `Bearer {api_key}`
- Required headers present: `HTTP-Referer`, `X-Title`
- Request body includes: model, messages, max_tokens, temperature, response_format
- Error handling covers all HTTP codes (400, 401, 403, 429, 500, 502, 503)
- Timeout validation (negative/zero → default 120s)
- Free models configuration: Gemini, Qwen, Mistral, Llama
- Stream method falls back to non-streaming for MVP
- API key not exposed in error messages
- Base URL validation rejects invalid URLs

**Files Implemented:**
- `backend/app/Services/AI/OpenRouterClient.php` ✅
- `backend/config/openrouter.php` ✅
- `backend/tests/Unit/Services/AI/OpenRouterClientTest.php` ✅

**Acceptance Criteria:** All 7 criteria met ✅

**Status:** `To Test Locally` → `To Deploy`
