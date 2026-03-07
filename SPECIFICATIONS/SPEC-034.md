# SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Related Feature
FEAT-034: AI Visibility Tracking (GEO)

---

## Executive Summary

This specification defines the technical architecture for tracking brand mentions and citations in AI-generated answers from ChatGPT, Perplexity, Claude, and other generative search engines. The feature addresses the declining traditional SEO traffic by providing visibility into the emerging AI search landscape.

---

## Architecture Overview

### System Components

```
┌─────────────────────────────────────────────────────────────────┐
│                    AI Visibility Tracking System                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────────────┐ │
│  │   Frontend  │    │   Backend   │    │   AI Query Engine   │ │
│  │   Dashboard │◄──►│    API      │◄──►│   (OpenRouter)      │ │
│  └─────────────┘    └─────────────┘    └─────────────────────┘ │
│                            │                    │              │
│                            ▼                    ▼              │
│                    ┌─────────────────────────────────────┐    │
│                    │           Database Layer            │    │
│                    │  - ai_visibility_projects           │    │
│                    │  - ai_query_results                 │    │
│                    │  - ai_mention_analytics             │    │
│                    └─────────────────────────────────────┘    │
│                            │                                   │
│                            ▼                                   │
│                    ┌─────────────────────────────────────┐    │
│                    │          Cache Layer (Redis)        │    │
│                    │  - Query result cache (7 days)      │    │
│                    │  - Analytics aggregation            │    │
│                    └─────────────────────────────────────┘    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Integration Points

| Integration | Purpose | Type |
|-------------|---------|------|
| OpenRouter | AI model access | External API |
| Existing Auth | User authentication | Internal |
| Queue System (FEAT-019) | Background processing | Internal |
| AI Infrastructure (FEAT-031) | Reuse AI service patterns | Internal |
| Notification System | Alert delivery | Internal |

---

## Free Tools Compliance

### Cost Mitigation Strategy

This feature requires AI API calls, which have associated costs. To comply with the **Free Tools Only** constraint:

| Strategy | Implementation | Savings |
|----------|----------------|---------|
| OpenRouter Free Tier | Use free models: `meta-llama/llama-3.1-8b-instruct:free`, `google/gemini-2.0-flash-exp:free` | 100% |
| Aggressive Caching | Cache results for 7 days | ~85% fewer API calls |
| Rate Limiting | Free tier: 5 queries/month, Premium: 50 queries/month | Controlled usage |
| Batch Processing | Run queries during off-peak hours | Efficient resource use |
| Query Deduplication | Same query within cache window = no new API call | ~30% fewer calls |

### Free Model Selection

| Model | Context | Free Limit | Use Case |
|-------|---------|------------|----------|
| `google/gemini-2.0-flash-exp:free` | 1M tokens | 20 req/min | Primary query model |
| `meta-llama/llama-3.1-8b-instruct:free` | 128K tokens | 20 req/min | Fallback model |

**Note**: OpenRouter provides genuinely free tier models. These are sufficient for MVP.

---

## Database Schema

### Migration: create_ai_visibility_projects_table

```sql
CREATE TABLE ai_visibility_projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    brand_name VARCHAR(255) NOT NULL,
    keywords JSON NOT NULL,           -- ["brand", "product", "keywords"]
    competitors JSON NOT NULL,        -- ["competitor1", "competitor2", "competitor3"]
    query_templates JSON NULL,        -- Custom query templates
    tracking_frequency ENUM('daily', 'weekly', 'monthly') DEFAULT 'weekly',
    is_active BOOLEAN DEFAULT TRUE,
    last_queried_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_last_queried (last_queried_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Migration: create_ai_query_results_table

```sql
CREATE TABLE ai_query_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    platform VARCHAR(50) NOT NULL,           -- 'chatgpt', 'perplexity', 'claude'
    model VARCHAR(100) NOT NULL,             -- Model identifier
    query_text TEXT NOT NULL,                -- Query sent to AI
    query_category VARCHAR(100) NULL,        -- 'comparison', 'recommendation', 'feature'
    response_text TEXT NOT NULL,             -- AI response
    brand_mentioned BOOLEAN DEFAULT FALSE,
    brand_context TEXT NULL,                 -- Surrounding text where brand mentioned
    brand_sentiment ENUM('positive', 'neutral', 'negative', 'mixed') NULL,
    competitor_mentions JSON NULL,           -- [{name: "Ahrefs", mentioned: true, sentiment: "neutral"}]
    citations JSON NULL,                     -- Links/sources cited in response
    query_tokens INT UNSIGNED NULL,
    response_tokens INT UNSIGNED NULL,
    latency_ms INT UNSIGNED NULL,
    queried_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cache_hit BOOLEAN DEFAULT FALSE,
    
    INDEX idx_project_platform (project_id, platform),
    INDEX idx_queried_at (queried_at),
    INDEX idx_brand_mentioned (brand_mentioned),
    FULLTEXT INDEX idx_response_text (response_text),
    FOREIGN KEY (project_id) REFERENCES ai_visibility_projects(id) ON DELETE CASCADE
);
```

### Migration: create_ai_mention_analytics_table

```sql
CREATE TABLE ai_mention_analytics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    platform VARCHAR(50) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    total_queries INT UNSIGNED DEFAULT 0,
    brand_mentions INT UNSIGNED DEFAULT 0,
    mention_rate DECIMAL(5,2) DEFAULT 0.00,      -- Percentage
    competitor_mentions JSON NULL,                -- {competitor: mention_rate}
    share_of_voice DECIMAL(5,2) DEFAULT 0.00,     -- Brand vs competitors
    avg_sentiment_score DECIMAL(3,2) DEFAULT 0.00, -- -1.00 to 1.00
    top_query_categories JSON NULL,               -- [{category: "comparison", count: 10}]
    mention_trend ENUM('up', 'down', 'stable') DEFAULT 'stable',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_project_period (project_id, platform, period_start, period_end),
    INDEX idx_period (period_start, period_end),
    FOREIGN KEY (project_id) REFERENCES ai_visibility_projects(id) ON DELETE CASCADE
);
```

### Migration: create_ai_visibility_alerts_table

```sql
CREATE TABLE ai_visibility_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    alert_type ENUM('mention_spike', 'mention_drop', 'competitor_gain', 'sentiment_change') NOT NULL,
    threshold DECIMAL(5,2) NOT NULL,           -- Percentage change threshold
    previous_value DECIMAL(5,2) NOT NULL,
    current_value DECIMAL(5,2) NOT NULL,
    change_percent DECIMAL(5,2) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_resolved BOOLEAN DEFAULT FALSE,
    triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    
    INDEX idx_project_unread (project_id, is_read),
    INDEX idx_triggered_at (triggered_at),
    FOREIGN KEY (project_id) REFERENCES ai_visibility_projects(id) ON DELETE CASCADE
);
```

---

## Backend Services

### AIVisibilityService

**Location**: `backend/app/Services/AIVisibility/AIVisibilityService.php`

```php
namespace App\Services\AIVisibility;

interface AIVisibilityServiceInterface
{
    /**
     * Create a new visibility tracking project
     */
    public function createProject(int $userId, array $data): AIVisibilityProject;
    
    /**
     * Run visibility queries for a project
     */
    public function runQueries(int $projectId): array;
    
    /**
     * Get visibility analytics for a project
     */
    public function getAnalytics(int $projectId, string $period = 'week'): array;
    
    /**
     * Get comparison data vs competitors
     */
    public function getCompetitorComparison(int $projectId): array;
    
    /**
     * Check for alerts and trigger notifications
     */
    public function checkAlerts(int $projectId): array;
}
```

### AIQueryEngine

**Location**: `backend/app/Services/AIVisibility/AIQueryEngine.php`

```php
namespace App\Services\AIVisibility;

class AIQueryEngine
{
    private const PLATFORMS = [
        'chatgpt' => [
            'model' => 'openai/gpt-4o-mini',
            'free_model' => 'google/gemini-2.0-flash-exp:free',
        ],
        'claude' => [
            'model' => 'anthropic/claude-3-haiku',
            'free_model' => 'meta-llama/llama-3.1-8b-instruct:free',
        ],
        'perplexity' => [
            'model' => 'perplexity/llama-3.1-sonar-small-128k-online',
            'free_model' => 'google/gemini-2.0-flash-exp:free', // Fallback
        ],
    ];
    
    private const QUERY_TEMPLATES = [
        'comparison' => 'What are the best {category} tools for {audience}?',
        'recommendation' => 'Can you recommend a good {category} tool?',
        'feature' => 'How does {brand} compare to {competitor} for {feature}?',
        'use_case' => 'What tool should I use to {use_case}?',
    ];
    
    /**
     * Query AI platform and return parsed response
     */
    public function query(string $platform, string $query, bool $useFreeModel = true): AIQueryResult;
    
    /**
     * Parse response for brand mentions
     */
    public function parseMentions(string $response, array $brands): MentionResult;
    
    /**
     * Analyze sentiment of mention context
     */
    public function analyzeSentiment(string $context): string;
}
```

### MentionParser

**Location**: `backend/app/Services/AIVisibility/MentionParser.php`

```php
namespace App\Services\AIVisibility;

class MentionParser
{
    /**
     * Extract brand mentions from AI response
     */
    public function extractMentions(string $response, array $targetBrands): array
    {
        $mentions = [];
        $responseLower = strtolower($response);
        
        foreach ($targetBrands as $brand) {
            $brandLower = strtolower($brand);
            $pattern = '/\b' . preg_quote($brandLower, '/') . '\b/i';
            
            if (preg_match_all($pattern, $response, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $mentions[] = [
                        'brand' => $brand,
                        'position' => $match[1],
                        'context' => $this->extractContext($response, $match[1], 100),
                    ];
                }
            }
        }
        
        return $mentions;
    }
    
    /**
     * Extract surrounding context for a mention
     */
    private function extractContext(string $text, int $position, int $radius): string;
    
    /**
     * Detect if brand is mentioned in a list vs comparison
     */
    public function detectMentionType(string $context): string;
}
```

### AnalyticsAggregator

**Location**: `backend/app/Services/AIVisibility/AnalyticsAggregator.php`

```php
namespace App\Services\AIVisibility;

class AnalyticsAggregator
{
    /**
     * Aggregate query results into analytics
     */
    public function aggregate(int $projectId, string $period = 'week'): AIMentionAnalytics;
    
    /**
     * Calculate mention rate
     */
    public function calculateMentionRate(int $projectId, string $platform, string $period): float;
    
    /**
     * Calculate share of voice
     */
    public function calculateShareOfVoice(int $projectId, string $period): float;
    
    /**
     * Get trend analysis
     */
    public function getTrend(int $projectId): array;
    
    /**
     * Get top query categories that trigger mentions
     */
    public function getTopCategories(int $projectId, int $limit = 5): array;
}
```

---

## API Endpoints

### Project Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/ai-visibility/projects` | List user's projects |
| POST | `/api/v1/ai-visibility/projects` | Create new project |
| GET | `/api/v1/ai-visibility/projects/{id}` | Get project details |
| PUT | `/api/v1/ai-visibility/projects/{id}` | Update project |
| DELETE | `/api/v1/ai-visibility/projects/{id}` | Delete project |
| POST | `/api/v1/ai-visibility/projects/{id}/run` | Manually trigger query run |

### Analytics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/ai-visibility/projects/{id}/analytics` | Get visibility analytics |
| GET | `/api/v1/ai-visibility/projects/{id}/comparison` | Get competitor comparison |
| GET | `/api/v1/ai-visibility/projects/{id}/trend` | Get trend analysis |
| GET | `/api/v1/ai-visibility/projects/{id}/queries` | Get query history |

### Alerts

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/ai-visibility/alerts` | List unread alerts |
| POST | `/api/v1/ai-visibility/alerts/{id}/read` | Mark alert as read |
| POST | `/api/v1/ai-visibility/alerts/{id}/resolve` | Resolve alert |

### Export

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/ai-visibility/projects/{id}/export` | Export data as CSV/JSON |

---

## Frontend Components

### Page Structure

```
/app/ai-visibility/
├── page.tsx                    # List projects
├── new/
│   └── page.tsx               # Create project
├── [projectId]/
│   ├── page.tsx               # Project dashboard
│   ├── queries/
│   │   └── page.tsx           # Query history
│   ├── comparison/
│   │   └── page.tsx           # Competitor comparison
│   ├── settings/
│   │   └── page.tsx           # Project settings
│   └── export/
│       └── page.tsx           # Export data
```

### Components

| Component | Location | Purpose |
|-----------|----------|---------|
| `VisibilityDashboard` | `components/ai-visibility/` | Main dashboard with metrics |
| `MentionRateChart` | `components/ai-visibility/` | Line chart for mention rate trends |
| `CompetitorComparison` | `components/ai-visibility/` | Bar chart comparing competitors |
| `QueryResultsList` | `components/ai-visibility/` | Table of query results |
| `MentionContextCard` | `components/ai-visibility/` | Shows context around mention |
| `AlertNotification` | `components/ai-visibility/` | Alert display component |
| `ProjectSettingsForm` | `components/ai-visibility/` | Project configuration form |

### Dashboard Layout

```
┌─────────────────────────────────────────────────────────────────┐
│ AI Visibility Dashboard                              [Settings] │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │ Mention Rate│  │ Share Voice │  │   Sentiment │             │
│  │    23.5%    │  │    31.2%    │  │   +0.45     │             │
│  │   ↑ 5.2%    │  │   ↑ 2.1%    │  │   Positive  │             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              Mention Rate Over Time                       │  │
│  │  [Chart: 90-day trend line with platform breakdown]      │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌────────────────────┐  ┌────────────────────────────────┐    │
│  │ Competitor         │  │ Recent Mentions                │    │
│  │ Comparison         │  │ [List of recent mentions with  │    │
│  │ [Bar chart:        │  │  context, platform, sentiment]│    │
│  │  brand vs top 3]   │  │                                │    │
│  └────────────────────┘  └────────────────────────────────┘    │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Query Categories                                          │  │
│  │ [Pie chart: Which query types trigger most mentions]     │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Queue Jobs

### AIVisibilityQueryJob

**Location**: `backend/app/Jobs/AIVisibilityQueryJob.php`

```php
class AIVisibilityQueryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $backoff = 60;
    
    public function __construct(
        public int $projectId,
        public string $platform,
        public string $query,
        public bool $useFreeModel = true
    ) {}
    
    public function handle(
        AIQueryEngine $engine,
        AIVisibilityService $service
    ): void {
        // Check cache first
        $cacheKey = $this->generateCacheKey();
        
        if ($cached = Cache::get($cacheKey)) {
            $this->storeResult($cached, cacheHit: true);
            return;
        }
        
        // Query AI platform
        $result = $engine->query(
            $this->platform,
            $this->query,
            $this->useFreeModel
        );
        
        // Store result
        $this->storeResult($result);
        
        // Cache for 7 days
        Cache::put($cacheKey, $result, now()->addDays(7));
    }
}
```

### Scheduled Tasks

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Run weekly queries for active projects
    $schedule->job(RunAIVisibilityQueries::class)
        ->weekly()
        ->sundaysAt('02:00');
    
    // Aggregate analytics daily
    $schedule->job(AggregateAIVisibilityAnalytics::class)
        ->daily()
        ->at('03:00');
    
    // Check for alerts every 6 hours
    $schedule->job(CheckAIVisibilityAlerts::class)
        ->everySixHours();
}
```

---

## Security Considerations

### Input Sanitization

| Input | Sanitization |
|-------|--------------|
| Brand name | `htmlspecialchars()`, max 255 chars, alphanumeric + spaces |
| Keywords | Array of strings, each max 100 chars |
| Competitors | Array of strings, max 3 competitors, each max 100 chars |
| Query templates | Whitelisted templates only, no user arbitrary queries |

### Rate Limiting

| Endpoint | Limit | Window |
|----------|-------|--------|
| POST `/projects` | 5 | 1 hour |
| POST `/projects/{id}/run` | 3 | 1 hour |
| GET `/analytics` | 60 | 1 minute |

### Query Limits per Tier

| Tier | Queries/Month | Platforms |
|------|---------------|-----------|
| Free | 5 | ChatGPT (free model) |
| Pro | 50 | ChatGPT, Perplexity |
| Business | 200 | All platforms |

---

## Configuration

### config/ai-visibility.php

```php
<?php

return [
    'cache_ttl_days' => env('AI_VISIBILITY_CACHE_DAYS', 7),
    
    'query_limits' => [
        'free' => env('AI_VISIBILITY_FREE_LIMIT', 5),
        'pro' => env('AI_VISIBILITY_PRO_LIMIT', 50),
        'business' => env('AI_VISIBILITY_BUSINESS_LIMIT', 200),
    ],
    
    'platforms' => [
        'chatgpt' => [
            'enabled' => true,
            'free_model' => 'google/gemini-2.0-flash-exp:free',
            'premium_model' => 'openai/gpt-4o-mini',
        ],
        'perplexity' => [
            'enabled' => true,
            'free_model' => 'google/gemini-2.0-flash-exp:free',
            'premium_model' => 'perplexity/llama-3.1-sonar-small-128k-online',
        ],
        'claude' => [
            'enabled' => false, // MVP: disabled
            'free_model' => 'meta-llama/llama-3.1-8b-instruct:free',
            'premium_model' => 'anthropic/claude-3-haiku',
        ],
    ],
    
    'alert_thresholds' => [
        'mention_spike' => env('AI_VISIBILITY_SPIKE_THRESHOLD', 20), // +20%
        'mention_drop' => env('AI_VISIBILITY_DROP_THRESHOLD', 20),   // -20%
        'competitor_gain' => env('AI_VISIBILITY_COMPETITOR_THRESHOLD', 15), // +15%
    ],
    
    'query_templates' => [
        'comparison' => 'What are the best {category} tools for {audience}?',
        'recommendation' => 'Can you recommend a good {category} tool?',
        'feature' => 'How does {brand} compare to {competitor}?',
        'use_case' => 'What tool should I use to {use_case}?',
    ],
];
```

---

## Testing Strategy

### Unit Tests

| Test File | Coverage |
|-----------|----------|
| `AIVisibilityServiceTest.php` | Service methods, project management |
| `AIQueryEngineTest.php` | Query generation, response parsing |
| `MentionParserTest.php` | Mention extraction, context parsing |
| `AnalyticsAggregatorTest.php` | Rate calculations, trend analysis |

### Feature Tests

| Test File | Coverage |
|-----------|----------|
| `AIVisibilityProjectTest.php` | API endpoints for project CRUD |
| `AIVisibilityAnalyticsTest.php` | Analytics API endpoints |
| `AIVisibilityAlertTest.php` | Alert system |

### Integration Tests

| Test File | Coverage |
|-----------|----------|
| `AIVisibilityFlowTest.php` | End-to-end query flow |
| `AIVisibilityQueueTest.php` | Background job processing |

---

## Task Breakdown

### Phase 1: MVP (Core Tracking)

| Task | Description | Effort |
|------|-------------|--------|
| TASK-34-1 | Database migrations | 2h |
| TASK-34-2 | AIVisibilityProject model | 2h |
| TASK-34-3 | AIQueryEngine service | 4h |
| TASK-34-4 | MentionParser service | 3h |
| TASK-34-5 | AIVisibilityService | 4h |
| TASK-34-6 | Project CRUD API endpoints | 3h |
| TASK-34-7 | Query job and scheduling | 3h |
| TASK-34-8 | Frontend: Project list page | 3h |
| TASK-34-9 | Frontend: Create project form | 3h |
| TASK-34-10 | Frontend: Dashboard page | 4h |
| TASK-34-11 | Unit tests for services | 4h |
| TASK-34-12 | Feature tests for API | 3h |

**Phase 1 Total**: ~38 hours

### Phase 2: Analytics & Comparison

| Task | Description | Effort |
|------|-------------|--------|
| TASK-34-13 | AnalyticsAggregator service | 3h |
| TASK-34-14 | Analytics API endpoints | 2h |
| TASK-34-15 | Competitor comparison logic | 3h |
| TASK-34-16 | Frontend: Analytics charts | 4h |
| TASK-34-17 | Frontend: Competitor comparison | 3h |
| TASK-34-18 | Tests for analytics | 3h |

**Phase 2 Total**: ~18 hours

### Phase 3: Alerts & Export

| Task | Description | Effort |
|------|-------------|--------|
| TASK-34-19 | Alert detection service | 3h |
| TASK-34-20 | Alert API endpoints | 2h |
| TASK-34-21 | Frontend: Alert notifications | 3h |
| TASK-34-22 | Export functionality | 3h |
| TASK-34-23 | Tests for alerts | 2h |

**Phase 3 Total**: ~13 hours

---

## Dependencies

### Internal Dependencies

| Dependency | Feature | Status |
|------------|---------|--------|
| User Authentication | Core | ✅ Available |
| Queue System | FEAT-019 | ✅ Available |
| AI Infrastructure | FEAT-031 | ✅ Available |
| OpenRouter Client | FEAT-031 | ✅ Available |

### External Dependencies

| Dependency | Purpose | Status |
|------------|---------|--------|
| OpenRouter API | AI model access | ✅ Free tier available |

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| API rate limits | Medium | High | Aggressive caching, query limits per user |
| API cost overrun | Low | High | Free models only, budget monitoring |
| Non-deterministic results | High | Medium | Multiple queries, average metrics |
| Platform API changes | Low | Medium | Abstraction layer, fallback models |
| Sentiment accuracy | Medium | Low | Manual review option, improve over time |

---

## Success Criteria

- [ ] Users can create AI visibility projects with brand and competitors
- [ ] System queries AI platforms weekly and stores results
- [ ] Dashboard shows mention rate, share of voice, sentiment
- [ ] Competitor comparison available
- [ ] Alerts triggered on significant changes
- [ ] Data exportable as CSV/JSON
- [ ] All tests passing
- [ ] Free tier users limited to 5 queries/month
- [ ] API response time < 2s (cached)

---

## Open Questions

1. **Query variation strategy**: Should we ask the same question multiple times to account for LLM non-determinism?
   - **Recommendation**: Yes, run 3 variations per query and average results

2. **Historical data retention**: How long to keep raw query results?
   - **Recommendation**: 90 days for raw data, aggregated analytics indefinite

3. **Google AI Overview**: Include in MVP?
   - **Recommendation**: No, API access is limited. Add in Phase 4.

---

## Comments

### [2026-03-07 18:25 UTC] SpecSystemAnalyst: Task Breakdown Complete

**Action**: Created 23 tasks across 3 phases

**Task Summary**:

| Phase | Tasks | Effort | Description |
|-------|-------|--------|-------------|
| Phase 1 (MVP) | TASK-34-1 to 34-12 | ~38 hours | Core infrastructure, models, services, API, frontend MVP |
| Phase 2 (Analytics) | TASK-34-13 to 34-18 | ~18 hours | Analytics aggregation, charts, competitor comparison |
| Phase 3 (Alerts/Export) | TASK-34-19 to 34-23 | ~13 hours | Alert detection, notifications, export functionality |

**Total Effort**: ~69 hours

**Key Task Dependencies**:
- TASK-34-1 (Migrations) → TASK-34-2 (Models)
- TASK-34-2 → TASK-34-3, 34-4, 34-5 (Services)
- TASK-34-5 → TASK-34-6 (API) → TASK-34-8, 34-9, 34-10 (Frontend)
- TASK-34-13 → TASK-34-14 → TASK-34-16, 34-17 (Analytics Flow)

**Critical Path**: 
1. Migrations → Models → Services → API → Frontend → Tests

**Risk Notes**:
- TASK-34-3 (AIQueryEngine): Medium risk - OpenRouter integration complexity
- TASK-34-5 (AIVisibilityService): Medium risk - orchestration of multiple services
- TASK-34-19 (AlertDetectionService): Medium risk - threshold tuning required

**Free Tools Compliance**:
- All tasks use OpenRouter free-tier models
- No paid API dependencies
- Aggressive caching minimizes API calls

**Handoff**: Ready for ProjectManager to prioritize tasks.

---

*Created by Architect agent on 2026-03-07*
*Feature: FEAT-034*
