# SPEC-031: Unified AI Prompt + Context Management

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`High`

## Source Feature
FEAT-031: Unified AI Prompt + Context Management

---

## 1. Technical Overview

### 1.1 Purpose
Refactor the AI analysis pipeline to consolidate multiple AI calls into a single unified request. This feature:
- Combines all indicator analysis prompts into one request
- Returns structured JSON with all indicator results plus an overall SEO summary
- Implements context window safeguards for model limits
- Provides intelligent truncation strategies for large content

### 1.2 Problem Statement
Currently, the SEO analysis system makes multiple sequential AI calls:
1. Individual indicator analysis calls (potentially 17+ calls)
2. Summary generation call
3. Suggestion generation calls

This approach has several issues:
- **Latency**: Multiple round-trips to AI API
- **Cost**: Each call counts separately
- **Context Loss**: No shared context between indicator analyses
- **Error Handling**: Complex recovery from partial failures
- **Rate Limiting**: More API calls = more rate limit hits

### 1.3 Technology Stack

| Layer | Technology | Rationale |
|-------|------------|-----------|
| Backend | Laravel 11 (PHP 8.2+) | Existing project stack |
| AI Provider | OpenRouter API | Multi-model access, free tier available |
| HTTP Client | Laravel HTTP Client | Built-in, supports timeouts, retries |
| Caching | Laravel Cache | Response caching for identical prompts |
| Queue | Laravel Queue | Async processing for large analyses |

### 1.4 Free Tools Constraint
Per CONSTITUTION.md, all implementations must use free tools:
- OpenRouter free-tier models (e.g., `google/gemini-2.0-flash-exp:free`, `meta-llama/llama-3-8b-instruct:free`)
- No paid-only model requirements
- Graceful fallback to smaller models when context limits hit

---

## 2. Architecture Design

### 2.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      Current Architecture (Before)                       │
│  ┌──────────┐   ┌──────────┐   ┌──────────┐         ┌──────────┐       │
│  │Indicator │   │Indicator │   │Indicator │   ...   │ Summary  │       │
│  │   AI 1   │   │   AI 2   │   │   AI 3   │         │   AI     │       │
│  └────┬─────┘   └────┬─────┘   └────┬─────┘         └────┬─────┘       │
│       │              │              │                    │              │
│       v              v              v                    v              │
│  [17+ separate API calls with no shared context]                        │
└─────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                       New Architecture (After)                           │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                    UnifiedPromptService                           │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐               │  │
│  │  │   Prompt    │  │   Context   │  │   Truncate  │               │  │
│  │  │  Builder    │──▶  Manager    │──▶  Strategy   │               │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘               │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                              │                                          │
│                              ▼                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                    Single AI API Call                             │  │
│  │                    (OpenRouter)                                   │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                              │                                          │
│                              ▼                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                    JSON Response Parser                           │  │
│  │  - All indicator results                                          │  │
│  │  - Overall SEO summary                                            │  │
│  │  - Actionable suggestions                                         │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Service Layer Design

#### Core Services

| Service | Responsibility |
|---------|----------------|
| `UnifiedPromptService` | Build consolidated prompt with all indicators |
| `ContextWindowManager` | Track and manage context window limits |
| `TruncationService` | Apply intelligent truncation strategies |
| `AIAnalysisService` | Execute unified AI request and parse response |
| `ModelSelectionService` | Choose appropriate model based on content size |

### 2.3 Data Flow

```
1. Analysis request received with domain HTML
2. ContextWindowManager estimates token count
3. If within limits:
   a. UnifiedPromptService builds complete prompt
   b. Single AI API call
   c. Parse JSON response
4. If exceeds limits:
   a. TruncationService applies strategy
   b. Retry with truncated content
   c. Or fallback to smaller model
5. AIAnalysisService returns structured results
6. All indicators + summary available in one response
```

---

## 3. Prompt Architecture

### 3.1 Unified Prompt Structure

```markdown
# SEO Analysis Request

Analyze the following webpage for SEO quality. Return a JSON response with ALL indicator scores and an overall summary.

## Page Information
- URL: {url}
- Title: {title}
- Meta Description: {meta_description}

## HTML Content
```html
{truncated_html}
```

## Analysis Requirements

Score each indicator from 1-3 stars and provide specific suggestions:

### Indicators to Analyze:
1. **URL Structure** (weight: 0.08)
   - Check: HTTPS, length, keywords, readability
   
2. **Title Tag** (weight: 0.10)
   - Check: Presence, length (50-60 chars), keywords
   
3. **Meta Description** (weight: 0.08)
   - Check: Presence, length (150-160 chars), keywords

[... all 17 indicators listed with criteria ...]

### Response Format (STRICT JSON)

```json
{
  "overall_score": 0-100,
  "overall_summary": "2-3 sentence executive summary",
  "category_scores": {
    "content": 0-100,
    "technical": 0-100,
    "accessibility": 0-100,
    "performance": 0-100
  },
  "indicators": [
    {
      "id": "url",
      "name": "URL Structure",
      "score": 1-3,
      "passed": true/false,
      "details": {
        "key": "value"
      },
      "suggestions": ["specific suggestion 1", "suggestion 2"]
    }
  ],
  "top_priorities": [
    {
      "indicator": "indicator_id",
      "message": "Why this is important"
    }
  ]
}
```
```

### 3.2 Prompt Size Estimation

| Component | Estimated Tokens |
|-----------|------------------|
| System prompt + instructions | ~800 |
| Indicator descriptions | ~1,200 |
| HTML content (varies) | Input-dependent |
| JSON schema template | ~400 |
| Response buffer | ~1,000 |
| **Base overhead** | ~3,400 |

**Context Window Reference:**

| Model | Context Window | Effective Limit (after overhead) |
|-------|----------------|----------------------------------|
| `google/gemini-2.0-flash-exp:free` | 1M tokens | ~996,600 tokens |
| `meta-llama/llama-3-8b-instruct:free` | 8K tokens | ~4,600 tokens |
| `mistralai/mistral-7b-instruct:free` | 32K tokens | ~28,600 tokens |
| `qwen/qwen-2-7b-instruct:free` | 32K tokens | ~28,600 tokens |

---

## 4. Context Window Management

### 4.1 Token Counting Strategy

**Implementation Options:**

1. **Tiktoken-based (Recommended for accuracy)**
   - Use `yetopen/tiktoken-php` or similar
   - Accurate for GPT-family models
   - Falls back to estimation for other models

2. **Character-based estimation**
   - Approximation: 1 token ≈ 4 characters (English)
   - Less accurate but faster
   - Use for initial checks

```php
class ContextWindowManager
{
    // Model context limits
    private const MODEL_LIMITS = [
        'google/gemini-2.0-flash-exp:free' => 1000000,
        'meta-llama/llama-3-8b-instruct:free' => 8192,
        'mistralai/mistral-7b-instruct:free' => 32768,
        'qwen/qwen-2-7b-instruct:free' => 32768,
    ];
    
    private const SAFETY_MARGIN = 0.10; // 10% safety margin
    
    public function estimateTokens(string $content): int
    {
        // Character-based estimation (fast)
        return (int) ceil(strlen($content) / 4);
    }
    
    public function fitsInContext(string $model, int $estimatedTokens): bool
    {
        $limit = self::MODEL_LIMITS[$model] ?? 8192;
        $effectiveLimit = $limit * (1 - self::SAFETY_MARGIN);
        return $estimatedTokens <= $effectiveLimit;
    }
    
    public function selectOptimalModel(int $estimatedTokens): string
    {
        // Start with free models, prefer larger context
        $models = [
            'google/gemini-2.0-flash-exp:free',
            'qwen/qwen-2-7b-instruct:free',
            'mistralai/mistral-7b-instruct:free',
        ];
        
        foreach ($models as $model) {
            if ($this->fitsInContext($model, $estimatedTokens)) {
                return $model;
            }
        }
        
        // Fallback: truncate and use available model
        return 'google/gemini-2.0-flash-exp:free';
    }
}
```

### 4.2 Truncation Strategies

**Strategy Hierarchy (applied in order):**

| Priority | Strategy | Reduction | Data Loss |
|----------|----------|-----------|-----------|
| 1 | Remove comments | 5-15% | None (non-visible) |
| 2 | Collapse whitespace | 3-8% | None |
| 3 | Remove `<script>` tags | 10-40% | JS code only |
| 4 | Remove `<style>` tags | 5-20% | CSS only |
| 5 | Truncate base64 images | Variable | Image data (keep alt) |
| 6 | Summarize text content | 50-70% | Paraphrased |
| 7 | Keep semantic structure only | 70-90% | Significant |

**Implementation:**

```php
class TruncationService
{
    public function truncate(string $html, int $targetTokens): TruncationResult
    {
        $strategies = [
            'remove_comments',
            'collapse_whitespace',
            'remove_scripts',
            'remove_styles',
            'summarize_content',
            'keep_structure_only',
        ];
        
        $currentHtml = $html;
        $appliedStrategies = [];
        
        foreach ($strategies as $strategy) {
            $estimatedTokens = $this->estimateTokens($currentHtml);
            
            if ($estimatedTokens <= $targetTokens) {
                break;
            }
            
            $currentHtml = $this->{$strategy}($currentHtml);
            $appliedStrategies[] = $strategy;
        }
        
        return new TruncationResult(
            html: $currentHtml,
            originalTokens: $this->estimateTokens($html),
            finalTokens: $this->estimateTokens($currentHtml),
            strategiesApplied: $appliedStrategies
        );
    }
    
    private function removeComments(string $html): string
    {
        return preg_replace('/<!--.*?-->/s', '', $html);
    }
    
    private function collapseWhitespace(string $html): string
    {
        return preg_replace('/\s+/', ' ', $html);
    }
    
    private function removeScripts(string $html): string
    {
        return preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    }
    
    private function removeStyles(string $html): string
    {
        return preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    }
    
    private function summarizeContent(string $html): string
    {
        // Extract key semantic elements only
        $crawler = new Crawler($html);
        $important = $crawler->filter('h1, h2, h3, p, a, img, meta, title');
        // Return simplified structure
        return $important->html();
    }
}
```

---

## 5. Response Schema

### 5.1 Strict JSON Schema

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "required": ["overall_score", "overall_summary", "indicators"],
  "properties": {
    "overall_score": {
      "type": "integer",
      "minimum": 0,
      "maximum": 100,
      "description": "Weighted overall SEO score"
    },
    "overall_summary": {
      "type": "string",
      "minLength": 50,
      "maxLength": 500,
      "description": "Executive summary of the analysis"
    },
    "category_scores": {
      "type": "object",
      "properties": {
        "content": { "type": "integer", "minimum": 0, "maximum": 100 },
        "technical": { "type": "integer", "minimum": 0, "maximum": 100 },
        "accessibility": { "type": "integer", "minimum": 0, "maximum": 100 },
        "performance": { "type": "integer", "minimum": 0, "maximum": 100 }
      }
    },
    "indicators": {
      "type": "array",
      "minItems": 17,
      "maxItems": 17,
      "items": {
        "type": "object",
        "required": ["id", "name", "score", "passed", "suggestions"],
        "properties": {
          "id": {
            "type": "string",
            "enum": [
              "url", "title_tag", "meta_description", "robots_txt",
              "sitemap_xml", "h1_headings", "filenames", "image_alt",
              "links", "404_page", "mobile_friendly", "language",
              "favicon", "canonical", "social_meta", "aria", "page_speed"
            ]
          },
          "name": { "type": "string" },
          "score": { "type": "integer", "minimum": 0, "maximum": 3 },
          "passed": { "type": "boolean" },
          "details": { "type": "object" },
          "suggestions": {
            "type": "array",
            "items": { "type": "string" }
          }
        }
      }
    },
    "top_priorities": {
      "type": "array",
      "maxItems": 5,
      "items": {
        "type": "object",
        "properties": {
          "indicator": { "type": "string" },
          "message": { "type": "string" }
        }
      }
    }
  }
}
```

### 5.2 Response Parsing

```php
class AIAnalysisResponseParser
{
    public function parse(string $rawResponse): AnalysisResult
    {
        // Extract JSON from response (handle markdown code blocks)
        $json = $this->extractJson($rawResponse);
        
        // Validate against schema
        $validator = new JsonSchemaValidator();
        $errors = $validator->validate($json, $this->getSchema());
        
        if (!empty($errors)) {
            throw new InvalidResponseException(
                "AI response failed validation: " . json_encode($errors)
            );
        }
        
        return AnalysisResult::fromArray($json);
    }
    
    private function extractJson(string $response): array
    {
        // Handle markdown code blocks
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $response, $matches)) {
            return json_decode($matches[1], true);
        }
        
        // Handle raw JSON
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            return json_decode($matches[0], true);
        }
        
        throw new InvalidResponseException("No valid JSON found in response");
    }
}
```

---

## 6. Error Handling

### 6.1 Error Categories

| Error Code | HTTP Status | Description | Recovery |
|------------|-------------|-------------|----------|
| `CONTEXT_EXCEEDED` | 413 | Content exceeds all model limits | Apply truncation |
| `AI_UNAVAILABLE` | 503 | AI provider unavailable | Retry with backoff |
| `INVALID_RESPONSE` | 422 | AI response malformed | Retry with stricter prompt |
| `RATE_LIMITED` | 429 | AI API rate limit hit | Queue for retry |
| `TIMEOUT` | 504 | AI request timed out | Retry with smaller content |

### 6.2 Retry Strategy

```php
class AIAnalysisRetryPolicy
{
    private const MAX_RETRIES = 3;
    private const BACKOFF_MULTIPLIER = 2;
    private const INITIAL_DELAY_MS = 1000;
    
    public function shouldRetry(Exception $e, int $attempt): bool
    {
        if ($attempt >= self::MAX_RETRIES) {
            return false;
        }
        
        return match (true) {
            $e instanceof RateLimitException => true,
            $e instanceof TimeoutException => true,
            $e instanceof ServiceUnavailableException => true,
            default => false,
        };
    }
    
    public function getDelayMs(int $attempt): int
    {
        return self::INITIAL_DELAY_MS * pow(self::BACKOFF_MULTIPLIER, $attempt - 1);
    }
}
```

### 6.3 Graceful Degradation

| Scenario | Fallback Behavior |
|----------|-------------------|
| JSON parse failure | Attempt repair, log error, return partial results |
| Missing indicator | Set score to 0, add to "not_analyzed" list |
| Context too large | Truncate progressively, prioritize key elements |
| AI timeout | Return cached analysis if available |
| All AI fails | Return basic analysis from DOM only |

---

## 7. Caching Strategy

### 7.1 Response Caching

| Cache Type | TTL | Key | Purpose |
|------------|-----|-----|---------|
| Analysis Result | 24 hours | `analysis:{domain_hash}` | Serve repeated requests |
| AI Response | 24 hours | `ai_response:{prompt_hash}` | Avoid duplicate AI calls |
| Model Selection | Request | - | Per-request optimization |

### 7.2 Cache Key Generation

```php
class AnalysisCacheKeyGenerator
{
    public function generate(string $domain, string $htmlHash): string
    {
        return sprintf(
            'analysis:%s:%s',
            hash('sha256', $domain),
            substr($htmlHash, 0, 16)
        );
    }
    
    public function generatePromptKey(string $prompt): string
    {
        return 'ai_response:' . hash('sha256', $prompt);
    }
}
```

---

## 8. Performance Considerations

### 8.1 Latency Comparison

| Approach | API Calls | Avg Latency | Cost Multiplier |
|----------|-----------|-------------|-----------------|
| Current (17+ calls) | 18-20 | 30-60s | 18-20x |
| Unified (1 call) | 1 | 3-8s | 1x |

### 8.2 Optimization Techniques

1. **Prompt Caching**: Use identical prompt structure for cache hits
2. **Streaming**: Consider streaming responses for faster TTFB
3. **Parallel Preparation**: Pre-process HTML while waiting for AI
4. **Connection Pooling**: Reuse HTTP connections to OpenRouter

---

## 9. Security Considerations

### 9.1 Prompt Injection Prevention

| Risk | Mitigation |
|------|------------|
| Malicious HTML content | Sanitize before including in prompt |
| Prompt escape attempts | Structure prompts with clear boundaries |
| Data exfiltration | No sensitive data in prompts |

### 9.2 Input Validation

```php
class PromptSecurityValidator
{
    private const MAX_HTML_SIZE = 10 * 1024 * 1024; // 10MB
    private const SUSPICIOUS_PATTERNS = [
        '/<\s*script/i',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/data:/i',
    ];
    
    public function validate(string $html): ValidationResult
    {
        $errors = [];
        
        if (strlen($html) > self::MAX_HTML_SIZE) {
            $errors[] = 'HTML content exceeds maximum size';
        }
        
        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $html)) {
                // Log but don't block - let sanitization handle it
                Log::warning('Suspicious HTML pattern detected', ['pattern' => $pattern]);
            }
        }
        
        return new ValidationResult(empty($errors), $errors);
    }
}
```

---

## 10. Database Changes

### 10.1 New Tables

#### ai_analysis_requests Table

```sql
CREATE TABLE ai_analysis_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    analysis_id BIGINT UNSIGNED NOT NULL,
    model VARCHAR(100) NOT NULL,
    prompt_tokens INT UNSIGNED NULL,
    response_tokens INT UNSIGNED NULL,
    truncation_strategies JSON NULL,
    request_duration_ms INT UNSIGNED NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (analysis_id) REFERENCES seo_analyses(id) ON DELETE CASCADE,
    INDEX idx_analysis_status (analysis_id, status),
    INDEX idx_model (model)
);
```

### 10.2 Modified Tables

#### seo_analyses Table (Add columns)

```sql
ALTER TABLE seo_analyses 
ADD COLUMN ai_model VARCHAR(100) NULL AFTER analysis_data,
ADD COLUMN prompt_tokens INT UNSIGNED NULL AFTER ai_model,
ADD COLUMN response_tokens INT UNSIGNED NULL AFTER prompt_tokens,
ADD COLUMN truncation_applied JSON NULL AFTER response_tokens;
```

---

## 11. API Endpoints

### 11.1 Analyze Domain (Existing, Modified)

```http
POST /api/v1/analyze
Content-Type: application/json

{
  "domain": "example.com",
  "model": "auto" // Optional: "auto", "gemini", "llama", specific model ID
}
```

**Response (unchanged structure, populated from single AI call):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "overallScore": 78,
    "overallSummary": "The website has good technical SEO foundation...",
    "indicators": [...],
    "aiModel": "google/gemini-2.0-flash-exp:free",
    "tokenUsage": {
      "prompt": 15234,
      "response": 2847
    }
  }
}
```

### 11.2 Get Model Options (New)

```http
GET /api/v1/ai/models
```

**Response:**
```json
{
  "success": true,
  "data": {
    "models": [
      {
        "id": "google/gemini-2.0-flash-exp:free",
        "name": "Gemini 2.0 Flash",
        "contextWindow": 1000000,
        "costPerToken": 0,
        "isFree": true
      },
      {
        "id": "meta-llama/llama-3-8b-instruct:free",
        "name": "Llama 3 8B",
        "contextWindow": 8192,
        "costPerToken": 0,
        "isFree": true
      }
    ],
    "defaultModel": "google/gemini-2.0-flash-exp:free"
  }
}
```

---

## 12. Task Breakdown Summary

### Phase 1: Core Services (4 tasks)
- TASK-31-1: ContextWindowManager service
- TASK-31-2: TruncationService implementation
- TASK-31-3: UnifiedPromptService for prompt building
- TASK-31-4: AIAnalysisResponseParser

### Phase 2: AI Integration (4 tasks)
- TASK-31-5: OpenRouter client service
- TASK-31-6: AIAnalysisService (unified caller)
- TASK-31-7: ModelSelectionService
- TASK-31-8: Retry policy implementation

### Phase 3: Database & Infrastructure (3 tasks)
- TASK-31-9: Migration for new columns/tables
- TASK-31-10: Update SEOAnalysis model
- TASK-31-11: Cache key generation and invalidation

### Phase 4: Integration (3 tasks)
- TASK-31-12: Update AnalysisRunnerService to use unified prompt
- TASK-31-13: API endpoint for model selection
- TASK-31-14: Frontend model picker component

### Phase 5: Testing & Polish (3 tasks)
- TASK-31-15: Unit tests for all services
- TASK-31-16: Integration tests for unified flow
- TASK-31-17: Performance benchmarking

**Total: 17 tasks**

---

## 13. Dependencies

### Internal Dependencies

| Feature/Task | Description |
|--------------|-------------|
| FEAT-018 | SEO Analysis Core Engine (indicator structure) |
| FEAT-016 | HTML parsing infrastructure |
| FEAT-026 | Billing/Credits (model pricing display) |

### External Dependencies

| Service | Purpose | Free Tier |
|---------|---------|-----------|
| OpenRouter | AI model access | Multiple free models |
| Google Gemini | Large context AI | Free tier available |

---

## 14. Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| AI response malformed | Medium | High | Strict JSON validation, retry logic |
| Context window exceeded | Medium | Medium | Intelligent truncation, model fallback |
| AI provider outage | Low | High | Multiple model support, graceful degradation |
| Rate limiting | Medium | Medium | Request queuing, caching |
| JSON schema changes | Low | Medium | Version-locked schema, migration plan |
| Cost overruns (if using paid) | Medium | High | Free-tier enforcement, usage tracking |

---

## 15. Migration Path

### 15.1 Backward Compatibility

- Existing `seo_analyses` records remain valid
- New columns are nullable
- Old analysis results can be re-analyzed with unified prompt

### 15.2 Rollout Plan

1. **Phase 1**: Deploy new services alongside existing
2. **Phase 2**: Feature flag to enable unified prompt
3. **Phase 3**: Monitor performance and accuracy
4. **Phase 4**: Deprecate individual AI calls

---

## Comments

### [2026-02-28] Architect
**Specification Created:** Unified AI prompt architecture with context window management.

**Key Design Decisions:**
1. Single AI call replaces 17+ individual calls (18-20x latency reduction)
2. Context window management with intelligent truncation
3. Strict JSON schema validation for AI responses
4. Multiple free-tier model support via OpenRouter
5. Graceful degradation for all failure modes

**Risk Assessment: Confirmed HIGH** - AI integration complexity, response validation, error handling.

**Dependencies:**
- FEAT-018 (indicator structure and analysis flow)
- FEAT-026 (model selection and pricing display)

**Handoff to SpecSystemAnalyst:** Ready for task breakdown. 17 tasks defined across 5 phases.

---

## Changelog

| Date | Agent | Action |
|------|-------|--------|
| 2026-02-28 | Architect | Initial specification created |
| 2026-02-28 | Architect | Status updated to "To Break Into Tasks" - specification complete |

---

*Specification created by Architect agent from FEAT-031.*
