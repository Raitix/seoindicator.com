# SPEC-021: AI Suggestions via OpenRouter/Vercel Gateway

## STATUS
`Specification In Progress`

## PRIORITY
`High`

## RISK
`High`

## Related
- Feature: [FEAT-021](../FEATURES/FEAT-021.md)
- Integrates with: [SPEC-016](SPEC-016.md) - Interactive SEO Tools
- Dependencies: TASK-16-1 (Tools Infrastructure - Done)

---

## Overview

AI-powered suggestions for SEO indicators, providing actionable recommendations for title tags, meta descriptions, robots.txt, sitemaps, headings, image alt text, filenames, URLs, links, and 404 handling. Uses configurable AI providers (OpenRouter or Vercel AI Gateway) with debug logging and fallback mechanisms.

---

## Free AI Provider Options

### OpenRouter (Recommended)
- **Free Tier**: Supports free models (GPT-4o-mini, Llama 3.1, etc.)
- **Rate Limits**: ~20 requests/minute on free tier
- **API**: `https://openrouter.ai/api/v1/chat/completions`
- **Auth**: API key in `Authorization: Bearer <key>` header

### Vercel AI Gateway
- **Free Tier**: Supports multiple providers via unified API
- **Rate Limits**: Provider-dependent
- **API**: Uses Vercel AI SDK
- **Auth**: Environment variable configuration

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Frontend (Next.js)                        │
├─────────────────────────────────────────────────────────────────┤
│  Tool Component → API Request → AI Suggestion Display           │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                     Backend (Laravel API)                        │
├─────────────────────────────────────────────────────────────────┤
│  Controller → AISuggestionService → AIProviderInterface         │
│                    ↓                        ↓                   │
│              PromptTemplate           OpenRouterProvider         │
│              ResponseParser           VercelAIProvider           │
│                    ↓                        ↓                   │
│              FallbackService          StaticSuggestionProvider  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      External Services                           │
├─────────────────────────────────────────────────────────────────┤
│  OpenRouter API │ Vercel AI Gateway │ Static Fallbacks          │
└─────────────────────────────────────────────────────────────────┘
```

---

## Components

### 1. AISuggestionService (Core Service)
**File**: `backend/app/Services/AI/AISuggestionService.php`

**Responsibilities**:
- Orchestrate AI suggestion generation
- Manage provider selection
- Handle timeouts and retries
- Coordinate fallback logic

**Methods**:
| Method | Parameters | Returns |
|--------|------------|---------|
| `getSuggestions(string $indicator, array $context)` | indicator type, page data | `SuggestionResult` |
| `setProvider(string $provider)` | provider name | void |
| `isDebugMode()` | none | bool |

### 2. AIProviderInterface
**File**: `backend/app/Services/AI/Contracts/AIProviderInterface.php`

```php
interface AIProviderInterface
{
    public function generate(string $prompt, array $options = []): AIResponse;
    public function isAvailable(): bool;
    public function getName(): string;
}
```

### 3. OpenRouterProvider
**File**: `backend/app/Services/AI/Providers/OpenRouterProvider.php`

**Configuration**:
```php
// config/ai.php
'openrouter' => [
    'api_key' => env('OPENROUTER_API_KEY'),
    'base_url' => 'https://openrouter.ai/api/v1',
    'model' => env('OPENROUTER_MODEL', 'meta-llama/llama-3.1-8b-instruct:free'),
    'timeout' => 30,
    'max_tokens' => 1000,
]
```

**Free Models Available**:
| Model | Max Tokens | Notes |
|-------|------------|-------|
| `meta-llama/llama-3.1-8b-instruct:free` | 128K | Recommended default |
| `google/gemma-2-9b-it:free` | 8K | Alternative |
| `qwen/qwen-2-7b-instruct:free` | 32K | Alternative |

### 4. VercelAIProvider
**File**: `backend/app/Services/AI/Providers/VercelAIProvider.php`

**Configuration**:
```php
// config/ai.php
'vercel' => [
    'api_key' => env('VERCEL_AI_API_KEY'),
    'model' => env('VERCEL_AI_MODEL', 'gpt-4o-mini'),
    'timeout' => 30,
]
```

### 5. StaticFallbackProvider
**File**: `backend/app/Services/AI/Providers/StaticFallbackProvider.php`

**Responsibilities**:
- Provide rule-based suggestions when AI is unavailable
- Fast response with no external dependency
- Pre-defined templates per indicator

---

## Indicator Prompts

### Prompt Template Structure
**File**: `backend/app/Services/AI/Prompts/IndicatorPrompts.php`

```php
return [
    'title' => [
        'system' => 'You are an SEO expert. Provide JSON suggestions for title tag optimization.',
        'user_template' => 'Analyze this title tag for SEO: "{title}".
            Page content summary: {content_summary}
            Target keyword: {keyword}
            
            Respond with JSON:
            {
                "score": <0-100>,
                "issues": ["<issue1>", "<issue2>"],
                "suggestions": [
                    {
                        "type": "improve"|"rewrite",
                        "text": "<suggested title>",
                        "reason": "<why this is better>"
                    }
                ],
                "best_practices": ["<tip1>", "<tip2>"]
            }',
    ],
    // ... other indicators
];
```

### Supported Indicators
| Indicator | Input Data | Output Fields |
|-----------|------------|---------------|
| `title` | title tag, content summary, keyword | score, issues, suggestions, best_practices |
| `meta_description` | meta description, content | score, issues, suggestions, best_practices |
| `robots` | robots.txt content | issues, suggestions, warnings |
| `sitemap` | sitemap.xml URL, page count | issues, suggestions, coverage |
| `headings` | H1-H6 structure | score, hierarchy_issues, suggestions |
| `image_alt` | image list with URLs, context | missing_alts, suggestions_per_image |
| `filenames` | file URLs, types | issues, suggestions |
| `url` | page URL, structure | score, issues, suggestions |
| `links` | internal/external link list | issues, broken_links, suggestions |
| `404` | 404 error URLs, referrers | causes, suggestions, redirects |

---

## API Endpoints

### POST /api/v1/ai/suggestions
Generate AI suggestions for a specific indicator.

**Request**:
```json
{
    "indicator": "title",
    "context": {
        "title": "Home - My Website",
        "content_summary": "E-commerce homepage selling widgets...",
        "keyword": "best widgets"
    },
    "provider": "openrouter",
    "debug": false
}
```

**Response (200)**:
```json
{
    "success": true,
    "data": {
        "indicator": "title",
        "score": 65,
        "issues": [
            "Title is too generic",
            "Missing target keyword"
        ],
        "suggestions": [
            {
                "type": "rewrite",
                "text": "Best Widgets 2026 | Premium Quality Widgets Online",
                "reason": "Includes target keyword and value proposition"
            }
        ],
        "best_practices": [
            "Keep title under 60 characters",
            "Place primary keyword near the beginning"
        ],
        "provider": "openrouter",
        "model": "llama-3.1-8b-instruct",
        "generated_at": "2026-02-28T12:00:00Z"
    }
}
```

**Response (Debug Mode - with `?debug=true`)**:
```json
{
    "success": true,
    "data": { /* ... suggestions ... */ },
    "debug": {
        "prompt": "Full prompt text sent to AI...",
        "raw_response": "Raw JSON string from provider...",
        "tokens_used": 245,
        "latency_ms": 1250,
        "provider": "openrouter",
        "model": "llama-3.1-8b-instruct"
    }
}
```

**Error Response (with fallback)**:
```json
{
    "success": true,
    "data": {
        "indicator": "title",
        "suggestions": [/* static fallback suggestions */],
        "provider": "static_fallback",
        "fallback_reason": "AI provider timeout"
    }
}
```

---

## Database Schema

### ai_suggestion_logs Table
Stores logs for debugging and analytics.

```sql
CREATE TABLE ai_suggestion_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(255) NULL,
    indicator VARCHAR(50) NOT NULL,
    provider VARCHAR(50) NOT NULL,
    model VARCHAR(100) NULL,
    prompt_tokens INT UNSIGNED NULL,
    completion_tokens INT UNSIGNED NULL,
    latency_ms INT UNSIGNED NOT NULL,
    success TINYINT(1) NOT NULL,
    error_message TEXT NULL,
    request_hash VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_indicator (user_id, indicator),
    INDEX idx_provider_created (provider, created_at),
    INDEX idx_success (success)
);
```

### ai_suggestions_cache Table
Caches suggestions for identical requests (24-hour TTL).

```sql
CREATE TABLE ai_suggestions_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_hash VARCHAR(64) NOT NULL UNIQUE,
    indicator VARCHAR(50) NOT NULL,
    context_hash VARCHAR(64) NOT NULL,
    response_json TEXT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    
    INDEX idx_hash (request_hash),
    INDEX idx_expires (expires_at)
);
```

---

## Configuration

### Environment Variables
```env
# AI Provider Selection
AI_PROVIDER=openrouter
AI_DEBUG=false

# OpenRouter Configuration
OPENROUTER_API_KEY=sk-or-...
OPENROUTER_MODEL=meta-llama/llama-3.1-8b-instruct:free

# Vercel AI Configuration
VERCEL_AI_API_KEY=...
VERCEL_AI_MODEL=gpt-4o-mini

# Fallback Settings
AI_FALLBACK_ENABLED=true
AI_CACHE_TTL_HOURS=24
AI_TIMEOUT_SECONDS=30
AI_MAX_RETRIES=2
```

### Laravel Config
**File**: `config/ai.php`

```php
<?php

return [
    'default' => env('AI_PROVIDER', 'openrouter'),
    
    'providers' => [
        'openrouter' => [
            'api_key' => env('OPENROUTER_API_KEY'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'model' => env('OPENROUTER_MODEL', 'meta-llama/llama-3.1-8b-instruct:free'),
            'timeout' => env('AI_TIMEOUT_SECONDS', 30),
        ],
        'vercel' => [
            'api_key' => env('VERCEL_AI_API_KEY'),
            'model' => env('VERCEL_AI_MODEL', 'gpt-4o-mini'),
            'timeout' => env('AI_TIMEOUT_SECONDS', 30),
        ],
    ],
    
    'fallback' => [
        'enabled' => env('AI_FALLBACK_ENABLED', true),
    ],
    
    'cache' => [
        'ttl_hours' => env('AI_CACHE_TTL_HOURS', 24),
    ],
    
    'debug' => env('AI_DEBUG', false),
];
```

---

## Security Implementation

| Security Measure | Implementation |
|------------------|----------------|
| API Key Storage | Encrypted in `.env`, not in code |
| Input Sanitization | Strip HTML, limit length (5000 chars max) |
| Rate Limiting | Per-user: 20/hour, Per-IP: 10/hour |
| PII Protection | Do not send user PII to AI providers |
| Response Validation | Validate JSON structure before returning |
| Error Masking | Generic error messages to users, detailed in logs |

### Data Privacy Considerations
- Page content is summarized before sending to AI (not full HTML)
- User URLs are anonymized in logs
- No personal data sent to AI providers
- GDPR-compliant data handling

---

## Error Handling

### Error Types
| Error | Handling |
|-------|----------|
| AI Provider Timeout | Return static fallback |
| Invalid API Key | Log error, return static fallback |
| Rate Limit Exceeded | Return 429 with retry-after header |
| Malformed Response | Log raw response, return static fallback |
| Content Policy Violation | Return generic suggestion |

### Fallback Logic
```
1. Check cache for identical request
2. If cache hit → return cached response
3. Try primary AI provider
4. If primary fails → try secondary provider (if configured)
5. If all AI fails → return static fallback
6. Log all attempts and outcomes
```

---

## Testing Strategy

### Unit Tests
- `AISuggestionServiceTest` - Core service logic
- `OpenRouterProviderTest` - API integration (mocked)
- `VercelAIProviderTest` - API integration (mocked)
- `StaticFallbackProviderTest` - Rule-based suggestions
- `PromptTemplateTest` - Template rendering
- `ResponseParserTest` - JSON parsing

### Feature Tests
- `AISuggestionControllerTest` - API endpoints
- `AISuggestionSecurityTest` - Rate limiting, input validation
- `AISuggestionFallbackTest` - Fallback scenarios

### Integration Tests
- `OpenRouterLiveTest` - Live API test (optional, gated by env flag)

---

## Task Breakdown

### Phase 1: Core Infrastructure
| Task | Description | Priority |
|------|-------------|----------|
| TASK-21-1 | Create AI service structure and interfaces | High |
| TASK-21-2 | Implement OpenRouter provider | High |
| TASK-21-3 | Implement StaticFallback provider | High |
| TASK-21-4 | Create prompt templates for all indicators | High |

### Phase 2: API & Integration
| Task | Description | Priority |
|------|-------------|----------|
| TASK-21-5 | Create AI suggestion API endpoint | High |
| TASK-21-6 | Implement caching layer | Medium |
| TASK-21-7 | Add debug mode and logging | Medium |
| TASK-21-8 | Create database migrations | Medium |

### Phase 3: Vercel Integration & Polish
| Task | Description | Priority |
|------|-------------|----------|
| TASK-21-9 | Implement Vercel AI provider | Medium |
| TASK-21-10 | Add rate limiting | High |
| TASK-21-11 | Frontend integration with tools | Medium |
| TASK-21-12 | Documentation and tests | High |

---

## Dependencies
- **External**: OpenRouter API (free tier), Vercel AI Gateway (optional)
- **Internal**: TASK-16-1 (Tools Infrastructure - already complete)
- **PHP**: Laravel HTTP Client, JSON validation
- **Frontend**: Existing tool components from SPEC-016

---

## Comments
_Agent log_

### [2026-02-28 10:42 GMT+2] Agent: Architect
Created SPEC-021 for AI Suggestions feature.

**Key Decisions**:
1. **OpenRouter as default provider** - Free models available, good API
2. **Static fallback always enabled** - Ensures functionality even when AI fails
3. **Caching layer** - Reduces API calls and costs
4. **Debug mode** - Essential for troubleshooting AI responses
5. **Privacy-first** - Summarize content before sending to AI

**Risk Assessment**:
- **High Risk** confirmed due to:
  - External API dependency (availability, rate limits)
  - AI-generated content quality variability
  - Potential costs if free tier limits exceeded
  - Data privacy concerns with third-party AI

**Free Tools Compliance**: ✅ OpenRouter provides free models that meet requirements.

Status: Specification In Progress → Ready for SpecSystemAnalyst breakdown.

---

*Created by Architect on 2026-02-28*
