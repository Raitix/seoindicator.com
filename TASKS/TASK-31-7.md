# TASK-31-7: AIAnalysisService (Unified Caller)

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`High`

## Specification
SPEC-31: Unified AI Prompt + Context Management

## Description
Create the `AIAnalysisService` that orchestrates the unified AI analysis flow: building prompts, managing context windows, calling the AI, and parsing responses. This is the main orchestrator service.

## Component Location
- **Service:** `backend/app/Services/AI/AIAnalysisService.php`
- **Tests:** `backend/tests/Unit/Services/AI/AIAnalysisServiceTest.php`

## Core Functionalities

| Method | Description |
|--------|-------------|
| `analyze(string $url, string $html): AnalysisResult` | Execute complete unified analysis |
| `analyzeWithModel(string $url, string $html, string $model): AnalysisResult` | Analyze with specific model |
| `getAnalysisMetadata(): array` | Return analysis metadata (tokens, model, etc.) |

## Service Dependencies

| Service | Purpose |
|---------|---------|
| `UnifiedPromptService` | Build consolidated prompt |
| `ContextWindowManager` | Estimate tokens, select model |
| `TruncationService` | Truncate content if needed |
| `OpenRouterClient` | Make AI API call |
| `AIAnalysisResponseParser` | Parse and validate response |
| `AnalysisCacheService` | Cache results |

## Implementation Flow

```php
class AIAnalysisService
{
    public function __construct(
        private UnifiedPromptService $promptService,
        private ContextWindowManager $contextManager,
        private TruncationService $truncationService,
        private OpenRouterClient $aiClient,
        private AIAnalysisResponseParser $parser,
        private AnalysisCacheService $cache,
    ) {}
    
    public function analyze(string $url, string $html, array $options = []): AnalysisResult
    {
        // 1. Check cache first
        $cacheKey = $this->cache->generateKey($url, $html);
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        // 2. Estimate tokens
        $estimatedTokens = $this->contextManager->estimateTokens($html);
        $overhead = 3400; // Prompt overhead
        $totalEstimated = $estimatedTokens + $overhead;
        
        // 3. Select model
        $model = $options['model'] ?? $this->contextManager->selectOptimalModel($totalEstimated);
        
        // 4. Check if truncation needed
        $effectiveLimit = $this->contextManager->getEffectiveLimit($model);
        if ($totalEstimated > $effectiveLimit) {
            $targetTokens = $effectiveLimit - $overhead;
            $truncationResult = $this->truncationService->truncate($html, $targetTokens);
            $html = $truncationResult->html;
            // Store truncation metadata for analysis record
        }
        
        // 5. Build prompt
        $prompt = $this->promptService->buildPrompt($url, $html, $options);
        
        // 6. Call AI
        $response = $this->aiClient->generate($prompt, [
            'model' => $model,
            'timeout' => 120,
        ]);
        
        if (!$response->success) {
            throw new AIAnalysisException($response->errorMessage);
        }
        
        // 7. Parse response
        $result = $this->parser->parse($response->content);
        
        // 8. Enrich with metadata
        $result = $result->withMetadata(
            model: $model,
            promptTokens: $response->promptTokens,
            responseTokens: $response->responseTokens,
            latencyMs: $response->latencyMs,
        );
        
        // 9. Cache result
        $this->cache->put($cacheKey, $result, ttl: 86400); // 24 hours
        
        return $result;
    }
}
```

## Error Handling Flow

```
analyze()
    │
    ├─► Cache hit ─────────────────────────────► Return cached result
    │
    ├─► Token estimation
    │       │
    │       └─► Exceeds limit ──► Truncate ──► Retry
    │
    ├─► AI call
    │       │
    │       ├─► Success ──► Parse ──► Cache ──► Return
    │       │
    │       ├─► Rate limited ──► Queue for retry
    │       │
    │       ├─► Timeout ──► Retry with smaller content
    │       │
    │       └─► Error ──► Log and throw
    │
    └─► Parse failure ──► Attempt repair ──► Return partial
```

## Retry Policy

```php
class AIAnalysisRetryPolicy
{
    private const MAX_RETRIES = 3;
    private const BACKOFF_MS = [1000, 2000, 4000];
    
    public function shouldRetry(AIResponse $response, int $attempt): bool
    {
        if ($attempt >= self::MAX_RETRIES) {
            return false;
        }
        
        return in_array($response->statusCode, [429, 500, 502, 503, 504]);
    }
    
    public function getDelayMs(int $attempt): int
    {
        return self::BACKOFF_MS[$attempt - 1] ?? 4000;
    }
}
```

## Acceptance Criteria
- [ ] `analyze()` orchestrates complete flow
- [ ] Cache checked before AI call
- [ ] Model selection automatic based on content size
- [ ] Truncation applied when needed
- [ ] Response parsed and validated
- [ ] Result cached for 24 hours
- [ ] Retry policy for transient errors
- [ ] All metadata stored (tokens, model, latency)

## Dependencies
- TASK-31-2 (ContextWindowManager)
- TASK-31-3 (TruncationService)
- TASK-31-4 (UnifiedPromptService)
- TASK-31-5 (AIAnalysisResponseParser)
- TASK-31-6 (OpenRouterClient)

## Effort Estimate
4 hours

## Comments
_Agent log_
