# TEST-34-3: AIQueryEngine Service

## Task Reference
- **Task:** TASK-34-3
- **Specification:** SPEC-034
- **Feature:** FEAT-034 - AI Visibility Tracking (Generative Engine Optimization)

---

## Overview

Comprehensive test suite for the `AIQueryEngine` service responsible for querying AI platforms via OpenRouter and parsing responses for brand mentions.

**Key Responsibilities:**
1. Query AI platforms (ChatGPT, Perplexity) via OpenRouter
2. Parse brand mentions from responses
3. Analyze sentiment of context around mentions
4. Handle errors gracefully with structured results
5. Cache query results with 7-day TTL

---

## Test Files

| File | Location | Purpose |
|------|----------|---------|
| `AIQueryEngineTest.php` | `backend/tests/Unit/Services/AIVisibility/` | AIQueryEngine service unit tests |

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| OpenRouterClient | ✅ Done | From FEAT-031 (TASK-31-6) |
| AIResponse DTO | ✅ Done | `App\Services\AI\DTOs\AIResponse` |
| AIQueryResult DTO | ❌ Not created | Will be created with this task |

---

## Test Scenarios

### Category 1: Class Structure & Configuration (QE-001 to QE-025)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QE-001 | Class exists at correct namespace | Check class | `App\Services\AIVisibility\AIQueryEngine` |
| QE-002 | Constructor accepts OpenRouterClient | Inject client | Instance created |
| QE-003 | Platform configuration: chatgpt enabled | Get platform | `chatgpt` in platforms array |
| QE-004 | Platform configuration: perplexity enabled | Get platform | `perplexity` in platforms array |
| QE-005 | Platform configuration: claude disabled | Get platform | `claude` NOT in platforms or disabled |
| QE-006 | ChatGPT model: Gemini 2.0 Flash free | Get model for chatgpt | `google/gemini-2.0-flash-exp:free` |
| QE-007 | Perplexity model: Gemini 2.0 Flash free | Get model for perplexity | `google/gemini-2.0-flash-exp:free` |
| QE-008 | Query template: comparison exists | Get template | "What are the best {category} tools for {audience}?" |
| QE-009 | Query template: recommendation exists | Get template | "Can you recommend a good {category} tool?" |
| QE-010 | Query template: feature exists | Get template | "How does {brand} compare to {competitor}?" |
| QE-011 | Query template: use_case exists | Get template | "What tool should I use to {use_case}?" |
| QE-012 | Platform validation: valid platform | `chatgpt` | Returns true |
| QE-013 | Platform validation: invalid platform | `unknown` | Returns false |
| QE-014 | Platform validation: disabled platform | `claude` | Returns false or throws |
| QE-015 | Template interpolation: category only | `{category}` = "SEO" | Interpolated string |
| QE-016 | Template interpolation: multiple placeholders | `{category}`, `{audience}` | All interpolated |
| QE-017 | Template interpolation: brand placeholder | `{brand}` = "Acme" | Interpolated |
| QE-018 | Template interpolation: competitor placeholder | `{competitor}` = "Other" | Interpolated |
| QE-019 | Template interpolation: use_case placeholder | `{use_case}` = "analyze" | Interpolated |
| QE-020 | Cache TTL configuration | Get cache TTL | 7 days (604800 seconds) |
| QE-021 | Dependency injection support | App::make() | Resolves correctly |
| QE-022 | Interface implementation | Check interface | Implements contract if defined |
| QE-023 | Default platform when not specified | Omit platform | Uses default (chatgpt) |
| QE-024 | Default useFreeModel flag | Omit flag | Defaults to true |
| QE-025 | Logger injection | Inject logger | Logs errors with context |

---

### Category 2: query() Method (QE-026 to QE-055)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QE-026 | query() method exists | Check method | Method exists |
| QE-027 | query() accepts platform parameter | `chatgpt` | Accepted |
| QE-028 | query() accepts query parameter | String | Accepted |
| QE-029 | query() accepts useFreeModel parameter | true/false | Accepted |
| QE-030 | query() returns AIQueryResult DTO | Call query | Returns DTO instance |
| QE-031 | query() uses OpenRouterClient | Call query | Client generate() called |
| QE-032 | query() passes correct model to client | useFreeModel=true | Free model ID |
| QE-033 | query() tracks latency in milliseconds | Call query | latencyMs populated |
| QE-034 | query() populates response text | Successful query | responseText populated |
| QE-035 | query() populates model used | Successful query | model populated |
| QE-036 | query() populates tokens used | Successful query | tokensUsed populated |
| QE-037 | query() with valid response | Mock success | AIQueryResult with success=true |
| QE-038 | query() with empty response | Mock empty | Handled gracefully |
| QE-039 | query() with long response | 50KB response | Handled correctly |
| QE-040 | query() with unicode response | Chinese/emoji | Stored correctly |
| QE-041 | query() caches result | Call twice | Cache hit on second call |
| QE-042 | query() cache key format | Same query+platform | Same cache key |
| QE-043 | query() respects cache TTL | Check cache | 7-day TTL applied |
| QE-044 | query() bypasses cache when requested | Skip cache | Fresh API call |
| QE-045 | query() invalid platform throws | `invalid` | InvalidArgumentException |
| QE-046 | query() empty query throws | Empty string | InvalidArgumentException |
| QE-047 | query() rate limit handling | Mock 429 | Structured error result |
| QE-048 | query() timeout handling | Mock timeout | Structured error result |
| QE-049 | query() API error handling | Mock 500 | Structured error result |
| QE-050 | query() connection error handling | Mock connection fail | Structured error result |
| QE-051 | query() logs errors | Trigger error | Log called with context |
| QE-052 | query() error includes platform | Trigger error | Platform in error context |
| QE-053 | query() error includes query text | Trigger error | Query in error context |
| QE-054 | query() error returns DTO not exception | Any error | AIQueryResult with error |
| QE-055 | query() null handling | null parameters | InvalidArgumentException |

---

### Category 3: parseMentions() Method (QE-056 to QE-085)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QE-056 | parseMentions() method exists | Check method | Method exists |
| QE-057 | parseMentions() accepts response string | String input | Accepted |
| QE-058 | parseMentions() accepts brands array | Array input | Accepted |
| QE-059 | parseMentions() returns array | Call method | Returns array |
| QE-060 | Single brand found once | "Acme is great", ["Acme"] | [{brand: "Acme", position: 0, context: "..."}] |
| QE-061 | Brand found at start | "Acme Corp is the best", ["Acme"] | Position 0 |
| QE-062 | Brand found in middle | "The best is Acme Corp here", ["Acme"] | Middle position |
| QE-063 | Brand found at end | "The best is Acme", ["Acme"] | End position |
| QE-064 | Multiple brands found | "Acme and Beta", ["Acme", "Beta"] | 2 mentions |
| QE-065 | Same brand multiple times | "Acme, then Acme again", ["Acme"] | 2 mentions |
| QE-066 | Case insensitive matching | "ACME corp", ["Acme"] | Found |
| QE-067 | Case insensitive: all caps | "ACME", ["acme"] | Found |
| QE-068 | Case insensitive: mixed case | "AcMe CoRp", ["acme"] | Found |
| QE-069 | Context extraction: 100 chars | Mention at position 50 | 100 chars around |
| QE-070 | Context at start of response | Position 5 | Chars from start |
| QE-071 | Context at end of response | Position end | Chars to end |
| QE-072 | Context includes mention | Extract context | Includes brand name |
| QE-073 | No brands found | "No brands here", ["Acme"] | Empty array |
| QE-074 | Empty response string | "", ["Acme"] | Empty array |
| QE-075 | Empty brands array | "Acme is here", [] | Empty array |
| QE-076 | Brand with special chars | "C++ Tools", ["C++"] | Found |
| QE-077 | Brand with unicode | "東京ツール", ["東京"] | Found |
| QE-078 | Brand with apostrophe | "O'Brien's Tools", ["O'Brien"] | Found |
| QE-079 | Overlapping brands | "AcmeBeta", ["Acme", "Beta"] | Both found |
| QE-080 | Brand as substring warning | "Parameter is...", ["Meter"] | May find (document behavior) |
| QE-081 | Whitespace handling | "  Acme  ", ["Acme"] | Found correctly |
| QE-082 | Newline handling | "Line1\nAcme\nLine3", ["Acme"] | Found correctly |
| QE-083 | Mention structure: brand field | Parse mentions | Has brand field |
| QE-084 | Mention structure: position field | Parse mentions | Has position (int) |
| QE-085 | Mention structure: context field | Parse mentions | Has context (string) |

---

### Category 4: analyzeSentiment() Method (QE-086 to QE-120)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QE-086 | analyzeSentiment() method exists | Check method | Method exists |
| QE-087 | analyzeSentiment() accepts context string | String input | Accepted |
| QE-088 | analyzeSentiment() returns string enum | Call method | Returns string |
| QE-089 | Return value: positive | Check return | Valid enum value |
| QE-090 | Return value: neutral | Check return | Valid enum value |
| QE-091 | Return value: negative | Check return | Valid enum value |
| QE-092 | Return value: mixed | Check return | Valid enum value |
| QE-093 | Positive keywords: excellent | "excellent product" | positive |
| QE-094 | Positive keywords: great | "great tool" | positive |
| QE-095 | Positive keywords: best | "the best option" | positive |
| QE-096 | Positive keywords: recommended | "highly recommended" | positive |
| QE-097 | Positive keywords: top | "top choice" | positive |
| QE-098 | Negative keywords: poor | "poor quality" | negative |
| QE-099 | Negative keywords: bad | "bad experience" | negative |
| QE-100 | Negative keywords: worst | "the worst" | negative |
| QE-101 | Negative keywords: avoid | "avoid this" | negative |
| QE-102 | Negative keywords: limited | "limited features" | negative |
| QE-103 | Mixed sentiment: positive + negative | "great but limited" | mixed |
| QE-104 | Neutral: no sentiment words | "it is a tool" | neutral |
| QE-105 | Neutral: factual statement | "founded in 2020" | neutral |
| QE-106 | Case insensitive: positive | "EXCELLENT product" | positive |
| QE-107 | Case insensitive: negative | "POOR quality" | negative |
| QE-108 | Empty context | "" | neutral |
| QE-109 | Null context handling | null | neutral or error |
| QE-110 | Unicode context | "素晴らしい製品" | Analyzes correctly |
| QE-111 | Long context (1000+ chars) | Long text | Analyzes full text |
| QE-112 | Multiple positive words | "excellent, great, best" | positive (stronger) |
| QE-113 | Multiple negative words | "poor, bad, worst" | negative (stronger) |
| QE-114 | Word boundary: "best" vs "bestie" | "my bestie" | neutral (not "best") |
| QE-115 | Word boundary: "great" vs "greatly" | "greatly affected" | neutral |
| QE-116 | Sentiment keyword in brand name | "BestAcme is okay" | Handles correctly |
| QE-117 | Punctuation handling | "Great!" | positive |
| QE-118 | Whitespace handling | "  great  " | positive |
| QE-119 | Multiple mentions with different sentiments | Multiple contexts | Analyzes each |
| QE-120 | Sentiment scoring threshold | Check logic | Document threshold |

---

### Category 5: Error Handling (QE-121 to QE-140)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QE-121 | API rate limit exception | Mock 429 | Structured error result |
| QE-122 | API timeout exception | Mock timeout | Structured error result |
| QE-123 | API server error | Mock 500 | Structured error result |
| QE-124 | API bad request | Mock 400 | Structured error result |
| QE-125 | API unauthorized | Mock 401 | Structured error result |
| QE-126 | Connection exception | Mock connection fail | Structured error result |
| QE-127 | JSON parse error | Malformed response | Structured error result |
| QE-128 | Error result includes message | Any error | errorMessage populated |
| QE-129 | Error result includes latency | Any error | latencyMs populated |
| QE-130 | Error result has success=false | Any error | success=false |
| QE-131 | Error logged with context | Trigger error | Log entry with context |
| QE-132 | Platform in error context | Trigger error | Platform logged |
| QE-133 | Query text in error context | Trigger error | Query logged |
| QE-134 | Timestamp in error context | Trigger error | Timestamp logged |
| QE-135 | Stack trace in debug mode | Trigger error | Stack trace if debug |
| QE-136 | Graceful degradation | Partial failure | Service continues |
| QE-137 | Retry on transient error | Timeout | Optional retry logic |
| QE-138 | Error does not throw exception | Any error | Returns DTO, no throw |
| QE-139 | Empty brand array validation | Empty brands | Handled gracefully |
| QE-140 | Invalid brand type validation | Non-array brands | InvalidArgumentException |

---

### Category 6: Integration & Edge Cases (QE-141 to QE-160)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QE-141 | Full flow: query → parse → sentiment | Valid inputs | Complete result |
| QE-142 | Multiple platforms same query | Query both | Separate results |
| QE-143 | Concurrent queries | Parallel requests | No race conditions |
| QE-144 | Cache invalidation | Clear cache | Fresh results |
| QE-145 | Memory usage: large response | 1MB response | No memory leak |
| QE-146 | Performance: query latency | Measure | < 5s for typical query |
| QE-147 | Performance: parse latency | 10KB response | < 100ms |
| QE-148 | Performance: sentiment latency | 1KB context | < 50ms |
| QE-149 | Unicode in all inputs | Chinese brand | Works correctly |
| QE-150 | Emoji in response | "Best tool! 🎉" | Works correctly |
| QE-151 | SQL injection in brand name | "'; DROP TABLE" | Handled safely |
| QE-152 | XSS in response | "<script>alert()" | Sanitized in output |
| QE-153 | Brand name with regex chars | "Acme (Inc.)" | Escaped correctly |
| QE-154 | Very long brand name | 1000 char brand | Handled gracefully |
| QE-155 | Brand name with numbers | "123Tools" | Found correctly |
| QE-156 | Response with code blocks | "```code```" | Parsed correctly |
| QE-157 | Response with markdown | "**bold** text" | Parsed correctly |
| QE-158 | Response with URLs | "Visit https://..." | Parsed correctly |
| QE-159 | Template special characters | "{category}" with special chars | Escaped correctly |
| QE-160 | Null OpenRouterClient injection | null client | InvalidArgumentException |

---

## Mock Data

### Sample AI Response (Success)

```php
$mockAIResponse = new AIResponse(
    success: true,
    content: 'Some of the best SEO tools include Acme Corp for enterprise teams, Beta Tools for startups, and Gamma for agencies. Acme Corp is highly recommended for its excellent analytics and great customer support.',
    tokensUsed: 150,
    latencyMs: 2300,
    errorMessage: null,
    model: 'google/gemini-2.0-flash-exp:free',
    promptTokens: 50,
    responseTokens: 100
);
```

### Sample Brands Array

```php
$brands = ['Acme Corp', 'Beta Tools', 'Gamma'];
```

### Expected Mentions

```php
$expectedMentions = [
    [
        'brand' => 'Acme Corp',
        'position' => 35, // approximate
        'context' => 'best SEO tools include Acme Corp for enterprise teams, Beta Tools for',
    ],
    [
        'brand' => 'Acme Corp',
        'position' => 95, // approximate
        'context' => 'Acme Corp is highly recommended for its excellent analytics',
    ],
    [
        'brand' => 'Beta Tools',
        'position' => 70, // approximate
        'context' => 'enterprise teams, Beta Tools for startups, and Gamma for agencies',
    ],
    [
        'brand' => 'Gamma',
        'position' => 105, // approximate
        'context' => 'Beta Tools for startups, and Gamma for agencies. Acme Corp is',
    ],
];
```

### Sample Contexts for Sentiment

```php
$positiveContext = 'Acme Corp is highly recommended for its excellent analytics and great customer support.';
$negativeContext = 'The tool has poor documentation and bad performance.';
$mixedContext = 'Acme Corp has excellent features but poor support.';
$neutralContext = 'Acme Corp was founded in 2020 and is based in San Francisco.';
```

### Error Response Structure

```php
$errorResult = [
    'success' => false,
    'error' => 'Rate limit exceeded',
    'error_code' => 'RATE_LIMIT',
    'latency_ms' => 150,
    'platform' => 'chatgpt',
    'query' => 'What are the best SEO tools?',
    'timestamp' => '2026-03-07T12:00:00Z',
];
```

---

## Coverage Targets

| Category | Tests | Target |
|----------|-------|--------|
| Class Structure & Configuration | 25 | 100% AC coverage |
| query() Method | 30 | 100% AC coverage |
| parseMentions() Method | 30 | 100% AC coverage |
| analyzeSentiment() Method | 35 | 100% AC coverage |
| Error Handling | 20 | 100% AC coverage |
| Integration & Edge Cases | 20 | 100% AC coverage |
| **Total** | **160** | **100% acceptance criteria coverage** |

---

## Test Execution

```bash
# Run AIQueryEngine tests
cd backend
php artisan test --filter=AIQueryEngineTest

# Run with coverage
php artisan test --filter=AIQueryEngineTest --coverage

# Run specific test category
php artisan test --filter="QE-001"
```

---

## Notes for Developer

### 1. Namespace Structure
```
app/Services/AIVisibility/
├── AIQueryEngine.php
├── Contracts/
│   └── AIQueryEngineInterface.php (optional)
└── DTOs/
    └── AIQueryResult.php
```

### 2. AIQueryResult DTO Structure
```php
readonly class AIQueryResult
{
    public function __construct(
        public bool $success,
        public ?string $responseText,
        public ?string $model,
        public ?int $tokensUsed,
        public ?int $latencyMs,
        public ?string $errorMessage,
        public ?string $platform,
        public ?string $queryText,
        public array $mentions = [],
        public ?string $sentiment = null,
    ) {}
    
    public static function success(/* ... */): self;
    public static function error(/* ... */): self;
}
```

### 3. Sentiment Keyword Lists
```php
private const POSITIVE_KEYWORDS = [
    'excellent', 'great', 'best', 'recommended', 'top',
    'amazing', 'outstanding', 'fantastic', 'superb', 'wonderful',
    'impressive', 'solid', 'reliable', 'powerful', 'innovative',
];

private const NEGATIVE_KEYWORDS = [
    'poor', 'bad', 'worst', 'avoid', 'limited',
    'terrible', 'awful', 'disappointing', 'frustrating', 'lacking',
    'unreliable', 'problematic', 'difficult', 'confusing', 'expensive',
];
```

### 4. Cache Implementation
```php
$cacheKey = "ai_query_{$platform}_" . md5($query);
$ttl = 7 * 24 * 60 * 60; // 7 days in seconds

return Cache::remember($cacheKey, $ttl, function () use ($platform, $query) {
    // API call here
});
```

### 5. Error Result Structure
```php
return AIQueryResult::error(
    errorMessage: 'Rate limit exceeded',
    latencyMs: $latencyMs,
    platform: $platform,
    queryText: $query,
);
```

### 6. Mention Parsing Algorithm
```php
public function parseMentions(string $response, array $brands): array
{
    $mentions = [];
    $responseLower = mb_strtolower($response);
    
    foreach ($brands as $brand) {
        $brandLower = mb_strtolower($brand);
        $offset = 0;
        
        while (($pos = mb_strpos($responseLower, $brandLower, $offset)) !== false) {
            $contextStart = max(0, $pos - 50);
            $contextEnd = min(mb_strlen($response), $pos + mb_strlen($brand) + 50);
            $context = mb_substr($response, $contextStart, $contextEnd - $contextStart);
            
            $mentions[] = [
                'brand' => $brand,
                'position' => $pos,
                'context' => $context,
            ];
            
            $offset = $pos + mb_strlen($brand);
        }
    }
    
    return $mentions;
}
```

### 7. Sentiment Analysis Algorithm
```php
public function analyzeSentiment(string $context): string
{
    $contextLower = mb_strtolower($context);
    
    $positiveCount = 0;
    $negativeCount = 0;
    
    foreach (self::POSITIVE_KEYWORDS as $keyword) {
        if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $context)) {
            $positiveCount++;
        }
    }
    
    foreach (self::NEGATIVE_KEYWORDS as $keyword) {
        if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $context)) {
            $negativeCount++;
        }
    }
    
    if ($positiveCount > 0 && $negativeCount > 0) {
        return 'mixed';
    }
    
    if ($positiveCount > 0) {
        return 'positive';
    }
    
    if ($negativeCount > 0) {
        return 'negative';
    }
    
    return 'neutral';
}
```

---

## Acceptance Criteria Mapping

| AC | Test IDs |
|----|----------|
| AIQueryEngine class created | QE-001, QE-002 |
| Platform: chatgpt configured | QE-003, QE-006 |
| Platform: perplexity configured | QE-004, QE-007 |
| Platform: claude disabled | QE-005, QE-014 |
| Template: comparison | QE-008, QE-015 |
| Template: recommendation | QE-009, QE-016 |
| Template: feature | QE-010, QE-017, QE-018 |
| Template: use_case | QE-011, QE-019 |
| query() method | QE-026 to QE-055 |
| parseMentions() method | QE-056 to QE-085 |
| analyzeSentiment() method | QE-086 to QE-120 |
| Error handling | QE-121 to QE-140 |
| Unit tests created | All QE-* tests |

---

## Comments

### PreTester Test Design (2026-03-07)

**Test Approach:**
- Unit tests with mocked OpenRouterClient
- Mock AIResponse DTO for predictable testing
- Test all public methods independently
- Test error paths with exception mocking
- Test caching behavior with Cache facade mock

**Dependencies Resolved:**
- OpenRouterClient from FEAT-031 ✅
- AIResponse DTO from FEAT-031 ✅
- Cache facade from Laravel ✅

**Edge Cases Identified:**
- Unicode brand names and responses
- Emoji in AI responses
- SQL injection patterns in brand names
- XSS in response content
- Regex special characters in brand names
- Very long brand names and responses
- Empty/null inputs
- Case sensitivity in brand matching
- Word boundary detection for sentiment keywords
- Cache key collisions

**Test Patterns Used:**
- Arrange-Act-Assert structure
- Mock OpenRouterClient for predictable responses
- Mock Cache facade for cache testing
- Data providers for similar test cases
- @test annotation with descriptive name

**Notes:**
- Tests written for TDD - AIQueryEngine doesn't exist yet
- Developer should implement service to pass these tests
- AIQueryResult DTO should be created alongside the service
- Cache TTL should be configurable (default 7 days)

---

*Created by PreTester on 2026-03-07*
