# TEST-21-7: Debug Mode and Logging Tests

## Task Reference
- **Task:** [TASK-21-7](../TASKS/TASK-21-7.md)
- **Specification:** [SPEC-021](../SPECIFICATIONS/SPEC-021.md)
- **Feature:** AI Suggestions via OpenRouter/Vercel Gateway

## Overview

Comprehensive test suite for the AILogger service and debug mode implementation. Tests cover:
- Service instantiation and method signatures
- Request/response/error logging
- Debug mode toggle behavior
- Privacy protection (API key masking, PII exclusion, URL anonymization)
- Log channel configuration
- API debug response structure

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `backend/tests/Unit/Services/AI/AILoggerTest.php` | Unit | 70+ | Core AILogger service tests |
| `backend/tests/Feature/Api/V1/AIDebugModeTest.php` | Feature | 30+ | API debug mode integration tests |

## Test Scenarios

### Unit Tests: AILogger Service

#### AC-1: Service Instantiation
| ID | Scenario | Expected |
|----|----------|----------|
| INST-001 | Can instantiate AILogger | Instance created |
| INST-002 | Can resolve from container | Instance from app() |
| INST-003 | Uses 'ai' log channel | Channel name is 'ai' |

#### AC-1: logRequest() Method
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| REQ-001 | Log with indicator, context, provider | `('title', ['url'=>'...'], 'openrouter')` | Log entry with all fields |
| REQ-002 | Includes ISO 8601 timestamp | Any valid input | Timestamp in format `YYYY-MM-DDTHH:MM:SS` |
| REQ-003 | Generates request hash | Any valid input | SHA-256 hash (64 chars) |
| REQ-004 | All 10 indicator types | Each indicator | Log entry for each |
| REQ-005 | Different providers | openrouter, vercel, static_fallback | Provider in log entry |
| REQ-006 | Empty context | `[]` | Log entry without error |
| REQ-007 | Nested context | `{page_data:{title:'...'}}` | Nested data preserved |

#### AC-1: logResponse() Method
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| RES-001 | Log with indicator, response, latency | `('title', {success:true}, 1250)` | Log entry with all fields |
| RES-002 | Includes token usage | Response with `tokens_used: 245` | Token count in log |
| RES-003 | Includes provider and model | Response with provider/model fields | Both in log entry |
| RES-004 | Handles failed response | `{success: false, error_message: '...'}` | Error logged |
| RES-005 | Latency is integer | Latency values | Integer type in log |
| RES-006 | Zero latency | `latency: 0` | Log entry with 0 |
| RES-007 | High latency | `latency: 30000` (30s) | Log entry with 30000 |

#### AC-1: logError() Method
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| ERR-001 | Log with indicator, error, context | `('title', 'Timeout', {url:'...'})` | Log entry with all fields |
| ERR-002 | Includes timestamp | Any error | Timestamp present |
| ERR-003 | Handles stack trace | Long stack trace | Included in log |
| ERR-004 | Truncates long stack traces | 6000+ char trace | Truncated to 5000 + suffix |
| ERR-005 | Empty context | No context | Log entry without error |
| ERR-006 | Multiple error types | Various error messages | Each logged correctly |

#### AC-1: logDebug() Method
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| DBG-001 | Logs when debug enabled | `AI_DEBUG=true` | Log entry created |
| DBG-002 | Does NOT log when disabled | `AI_DEBUG=false` | No log entry |
| DBG-003 | Empty data | `logDebug('message')` | Log entry with timestamp only |
| DBG-004 | Includes all data | `{prompt:'...', model:'...'}` | All data in log |

#### AC-6: Privacy Protection (API Key Masking)
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| PRIV-001 | Masks `api_key` | `{api_key: 'sk-secret'}` | `'***REDACTED***'` |
| PRIV-002 | Masks `apiKey` (camelCase) | `{apiKey: 'sk-secret'}` | `'***REDACTED***'` |
| PRIV-003 | Masks `password` | `{password: 'secret'}` | `'***REDACTED***'` |
| PRIV-004 | Masks `secret` | `{secret: 'value'}` | `'***REDACTED***'` |
| PRIV-005 | Masks `token` | `{token: 'bearer...'}` | `'***REDACTED***'` |
| PRIV-006 | Masks `authorization` | `{authorization: 'Bearer ...'}` | `'***REDACTED***'` |
| PRIV-007 | Masks `Bearer` | `{Bearer: 'token'}` | `'***REDACTED***'` |
| PRIV-008 | Masks `X-Api-Key` | `{X-Api-Key: 'key'}` | `'***REDACTED***'` |
| PRIV-009 | Masks nested sensitive data | `{headers:{api_key:'...'}}` | Nested value redacted |

#### AC-6: PII Exclusion
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| PII-001 | Excludes `user_email` | `{user_email: 'a@b.com'}` | Field removed |
| PII-002 | Excludes `user_name` | `{user_name: 'John'}` | Field removed |
| PII-003 | Excludes `user_id` | `{user_id: 123}` | Field removed |
| PII-004 | Excludes `ip_address` | `{ip_address: '192.168.1.1'}` | Field removed |

#### AC-6: URL Anonymization
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| URL-001 | Anonymizes URLs | `{url: 'https://example.com/secret'}` | URL transformed/hashed |

#### AC-4: Log Channel Configuration
| ID | Scenario | Expected |
|----|----------|----------|
| CHAN-001 | Uses 'ai' channel | Channel name matches |
| CHAN-002 | Channel configured in logging.php | 'ai' key exists |
| CHAN-003 | Path is correct | `storage_path('logs/ai.log')` |
| CHAN-004 | Keeps logs for 30 days | `days: 30` |

#### Edge Cases
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EDGE-001 | Unicode context | Japanese characters | Preserved correctly |
| EDGE-002 | Emoji in context | Emoji characters | Preserved correctly |
| EDGE-003 | Very long context | 10000+ char value | Handled gracefully |
| EDGE-004 | Null values | `{field: null}` | null preserved |
| EDGE-005 | Boolean values | `{is_secure: true, has_meta: false}` | Booleans preserved |
| EDGE-006 | Numeric values | `{score: 75, ratio: 0.5}` | Numbers preserved |

#### Performance Tests
| ID | Scenario | Threshold | Expected |
|----|----------|-----------|----------|
| PERF-001 | 100 logRequest calls | <500ms | Pass |
| PERF-002 | 100 logResponse calls | <500ms | Pass |
| PERF-003 | 100 sanitization ops | <100ms | Pass |

### Feature Tests: API Debug Mode

#### AC-2: Debug Mode Disabled
| ID | Scenario | Expected |
|----|----------|----------|
| API-DIS-001 | No debug key in response | `debug` key absent |
| API-DIS-002 | Response structure valid | Standard success structure |
| API-DIS-003 | No prompt in response | `prompt` key absent |
| API-DIS-004 | No raw_response in response | `raw_response` key absent |

#### AC-2: Debug Mode Enabled
| ID | Scenario | Expected |
|----|----------|----------|
| API-EN-001 | Debug key present | `debug` object in response |
| API-EN-002 | Contains full prompt | `debug.prompt` string |
| API-EN-003 | Contains raw response | `debug.raw_response` string |
| API-EN-004 | Contains token usage | `debug.tokens_used` integer |
| API-EN-005 | Contains latency | `debug.latency_ms` integer |
| API-EN-006 | Contains provider | `debug.provider` string |
| API-EN-007 | Contains model | `debug.model` string |

#### AC-2: Debug Mode Security
| ID | Scenario | Expected |
|----|----------|----------|
| API-SEC-001 | No API keys exposed | `sk-` not in response |
| API-SEC-002 | No user PII exposed | Email/name not in response |
| API-SEC-003 | Controlled by env variable | AI_DEBUG config used |

#### AC-2: All Indicators
| ID | Scenario | Indicators |
|----|----------|------------|
| API-IND-001 | Debug works for all indicators | title, meta_description, robots, sitemap, headings, image_alt, filenames, url, links, 404 |

#### Different Providers
| ID | Scenario | Provider | Expected |
|----|----------|----------|----------|
| API-PROV-001 | Shows openrouter | openrouter | `debug.provider: 'openrouter'` |
| API-PROV-002 | Shows vercel | vercel | `debug.provider: 'vercel'` |
| API-PROV-003 | Shows static_fallback | static_fallback | `debug.provider: 'static_fallback'` |

#### Error Cases
| ID | Scenario | Debug Mode | Expected |
|----|----------|------------|----------|
| API-ERR-001 | Error with debug enabled | true | Debug info included |
| API-ERR-002 | Error with debug disabled | false | No debug info |

## Mock Data

### AI Response (Success)
```json
{
    "success": true,
    "content": "{\"indicator\":\"title\",\"score\":75,\"issues\":[\"Title too short\"],\"suggestions\":[{\"type\":\"rewrite\",\"text\":\"Better Title\"}],\"best_practices\":[\"Keep under 60 characters\"],\"provider\":\"openrouter\",\"model\":\"llama-3.1-8b-instruct\",\"generated_at\":\"2026-03-04T12:00:00Z\"}",
    "errorMessage": null,
    "tokensUsed": 245,
    "latencyMs": 1250
}
```

### AI Response (Error)
```json
{
    "success": false,
    "content": "",
    "errorMessage": "Connection timeout",
    "tokensUsed": 0,
    "latencyMs": 30000
}
```

### Expected Debug Response Structure
```json
{
    "success": true,
    "data": {
        "indicator": "title",
        "score": 75,
        "issues": ["Title too short"],
        "suggestions": [{"type": "rewrite", "text": "Better Title"}],
        "best_practices": ["Keep under 60 characters"],
        "provider": "openrouter",
        "model": "llama-3.1-8b-instruct",
        "generated_at": "2026-03-04T12:00:00Z"
    },
    "debug": {
        "prompt": "Full prompt text...",
        "raw_response": "Raw JSON from provider...",
        "tokens_used": 245,
        "latency_ms": 1250,
        "provider": "openrouter",
        "model": "llama-3.1-8b-instruct"
    }
}
```

## Coverage Targets

| Category | Target | Notes |
|----------|--------|-------|
| AILogger Service | 95% | All methods, branches |
| Debug Mode Toggle | 100% | Both enabled/disabled |
| Privacy Protection | 100% | All sensitive keys |
| Log Channel Config | 100% | Channel setup |
| Error Handling | 100% | All error cases |

## Test Execution

```bash
# Run all TASK-21-7 tests
cd backend
php artisan test --filter AILoggerTest
php artisan test --filter AIDebugModeTest

# Run with coverage
php artisan test --filter "AILoggerTest|AIDebugModeTest" --coverage
```

## Dependencies

### Existing (Ready)
- `config/logging.php` - 'ai' channel configured
- `config/ai.php` - debug flag configured
- `app/Services/AI/Contracts/AIProviderInterface.php`
- `app/Services/AI/DTOs/AIResponse.php`
- `app/Services/AI/AISuggestionService.php`

### Requires Implementation
- Update `AILogger::logRequest()` to match spec signature
- Update `AILogger::logResponse()` to match spec signature
- Update `AILogger::logError()` to match spec signature
- Add request hash generation
- Add URL anonymization
- Add PII exclusion logic
- Controller must include debug info in responses when `AI_DEBUG=true`

## Notes for Developer

### Method Signature Changes Required

The current AILogger implementation has simplified signatures. Update to match spec:

```php
// Current
public function logRequest(array $context): void
public function logResponse(array $response): void
public function logError(array $error): void

// Required (from specification)
public function logRequest(string $indicator, array $context, string $provider): void
public function logResponse(string $indicator, array $response, int $latencyMs): void
public function logError(string $indicator, string $error, array $context = []): void
```

### Request Hash Generation
Generate SHA-256 hash from indicator + context JSON:
```php
$hash = hash('sha256', json_encode(['indicator' => $indicator, 'context' => $context]));
```

### URL Anonymization
Transform URLs to prevent sensitive path exposure:
```php
// Option 1: Hash the path
$url = preg_replace('/^(https?:\/\/[^\/]+).*/', '$1/[HASH]', $url);

// Option 2: Replace with hash
$url = hash('sha256', $url);
```

### PII Exclusion
Add to sensitive keys list:
```php
$sensitiveKeys = [
    'api_key', 'apiKey', 'password', 'secret', 'token',
    'authorization', 'Bearer', 'X-Api-Key',
    // Add PII exclusions
    'user_email', 'user_name', 'user_id', 'ip_address',
];
```

### Debug Response in Controller
In `AISuggestionController`, wrap response with debug info when enabled:
```php
if (config('ai.debug')) {
    return response()->json([
        'success' => true,
        'data' => $result->toArray(),
        'debug' => [
            'prompt' => $prompt,
            'raw_response' => $rawResponse,
            'tokens_used' => $response->tokensUsed,
            'latency_ms' => $response->latencyMs,
            'provider' => $provider,
            'model' => $model,
        ],
    ]);
}
```

---

## Comments

### [2026-03-04 16:55 GMT+2] Agent: PreTester
Test suite created covering all acceptance criteria:
- 70+ unit tests for AILogger service
- 30+ feature tests for API debug mode
- Full coverage of privacy protection requirements
- Performance benchmarks included

**Implementation Notes:**
- Current AILogger signatures differ from spec - tests expect spec signatures
- URL anonymization and PII exclusion not implemented - tests expect them
- Debug mode response structure in controller not implemented - tests expect it

**Status:** Ready for Developer implementation

---
*Created: 2026-03-04*
