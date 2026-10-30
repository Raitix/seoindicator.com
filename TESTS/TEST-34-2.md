# TEST-34-2: AIVisibilityProject Models

## Task Reference
- **Task:** TASK-34-2
- **Specification:** SPEC-034
- **Feature:** FEAT-034 - AI Visibility Tracking (Generative Engine Optimization)

---

## Overview

Comprehensive test suite for four Eloquent models in the AI Visibility Tracking system:

1. **AIVisibilityProject** - Project configuration for brand tracking
2. **AIQueryResult** - Individual AI query results and responses
3. **AIMentionAnalytics** - Aggregated analytics data
4. **AIVisibilityAlert** - Alert notifications for significant changes

All models use namespace: `App\Models\AIVisibility\*`

---

## Test Files

| File | Location | Purpose |
|------|----------|---------|
| `AIVisibilityProjectTest.php` | `backend/tests/Unit/Models/AIVisibility/` | AIVisibilityProject model tests |
| `AIQueryResultTest.php` | `backend/tests/Unit/Models/AIVisibility/` | AIQueryResult model tests |
| `AIMentionAnalyticsTest.php` | `backend/tests/Unit/Models/AIVisibility/` | AIMentionAnalytics model tests |
| `AIVisibilityAlertTest.php` | `backend/tests/Unit/Models/AIVisibility/` | AIVisibilityAlert model tests |

---

## Test Scenarios

### Category 1: AIVisibilityProject Model (VP-001 to VP-040)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VP-001 | Model exists at correct namespace | Check class | `App\Models\AIVisibility\AIVisibilityProject` |
| VP-002 | Create with required fields | brand_name, keywords, competitors | Model persisted |
| VP-003 | Fillable: brand_name | Mass assign | Accepted |
| VP-004 | Fillable: keywords | Mass assign array | Accepted |
| VP-005 | Fillable: competitors | Mass assign array | Accepted |
| VP-006 | Fillable: query_templates | Mass assign array | Accepted |
| VP-007 | Fillable: tracking_frequency | Mass assign string | Accepted |
| VP-008 | Fillable: is_active | Mass assign boolean | Accepted |
| VP-009 | Fillable: last_queried_at | Mass assign datetime | Accepted |
| VP-010 | Cast: keywords → array | JSON input | PHP array output |
| VP-011 | Cast: competitors → array | JSON input | PHP array output |
| VP-012 | Cast: query_templates → array | JSON input | PHP array output |
| VP-013 | Cast: is_active → boolean | 1/0 input | true/false output |
| VP-014 | Cast: last_queried_at → datetime | DateTime input | Carbon instance |
| VP-015 | BelongsTo: user() | Relationship | Returns User model |
| VP-016 | HasMany: queryResults() | Relationship | Returns AIQueryResult collection |
| VP-017 | HasMany: analytics() | Relationship | Returns AIMentionAnalytics collection |
| VP-018 | HasMany: alerts() | Relationship | Returns AIVisibilityAlert collection |
| VP-019 | Scope: active() | Query with scope | Only is_active=true |
| VP-020 | Scope: dueForQuery() | Query with scope | Projects needing queries |
| VP-021 | Accessor: getCompetitorListAttribute() | Access competitors | Returns competitor names array |
| VP-022 | Default is_active | Omit field | Defaults to true |
| VP-023 | Default tracking_frequency | Omit field | Defaults to 'weekly' |
| VP-024 | Nullable query_templates | Null value | Stored as null |
| VP-025 | Nullable last_queried_at | Null value | Stored as null |
| VP-026 | User relationship retrieval | Eager load | User loaded correctly |
| VP-027 | QueryResults cascade delete | Delete project | Results deleted |
| VP-028 | Analytics cascade delete | Delete project | Analytics deleted |
| VP-029 | Alerts cascade delete | Delete project | Alerts deleted |
| VP-030 | Tracking frequency enum values | daily/weekly/monthly | All valid |
| VP-031 | Large keywords array | 1000 keywords | Stored correctly |
| VP-032 | Complex competitors JSON | Nested objects | Stored correctly |
| VP-033 | Unicode in brand_name | Chinese/emoji | Stored correctly |
| VP-034 | Active scope chainable | active()->dueForQuery() | Both applied |
| VP-035 | PHPDoc type hints | Check docblocks | IDE support |
| VP-036 | Table name explicit | Check $table | ai_visibility_projects |
| VP-037 | Guarded vs fillable | Check model | Uses $fillable |
| VP-038 | Timestamps enabled | Check model | created_at, updated_at |
| VP-039 | Multiple projects per user | Create 5 projects | All stored |
| VP-040 | Query relationship count | With results | Correct count |

### Category 2: AIQueryResult Model (QR-001 to QR-045)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| QR-001 | Model exists at correct namespace | Check class | `App\Models\AIVisibility\AIQueryResult` |
| QR-002 | Create with required fields | project_id, platform, model, query_text, response_text | Model persisted |
| QR-003 | Fillable: project_id | Mass assign | Accepted |
| QR-004 | Fillable: platform | Mass assign | Accepted |
| QR-005 | Fillable: model | Mass assign | Accepted |
| QR-006 | Fillable: query_text | Mass assign | Accepted |
| QR-007 | Fillable: query_category | Mass assign | Accepted |
| QR-008 | Fillable: response_text | Mass assign | Accepted |
| QR-009 | Fillable: brand_mentioned | Mass assign | Accepted |
| QR-010 | Fillable: brand_context | Mass assign | Accepted |
| QR-011 | Fillable: brand_sentiment | Mass assign | Accepted |
| QR-012 | Fillable: competitor_mentions | Mass assign | Accepted |
| QR-013 | Fillable: citations | Mass assign | Accepted |
| QR-014 | Fillable: query_tokens | Mass assign | Accepted |
| QR-015 | Fillable: response_tokens | Mass assign | Accepted |
| QR-016 | Fillable: latency_ms | Mass assign | Accepted |
| QR-017 | Fillable: queried_at | Mass assign | Accepted |
| QR-018 | Fillable: cache_hit | Mass assign | Accepted |
| QR-019 | Cast: competitor_mentions → array | JSON input | PHP array output |
| QR-020 | Cast: citations → array | JSON input | PHP array output |
| QR-021 | Cast: brand_mentioned → boolean | 1/0 input | true/false output |
| QR-022 | Cast: cache_hit → boolean | 1/0 input | true/false output |
| QR-023 | Cast: queried_at → datetime | DateTime input | Carbon instance |
| QR-024 | BelongsTo: project() | Relationship | Returns AIVisibilityProject |
| QR-025 | Scope: mentioned() | Query with scope | Only brand_mentioned=true |
| QR-026 | Scope: forPlatform($platform) | Query with scope | Filtered by platform |
| QR-027 | Default brand_mentioned | Omit field | Defaults to false |
| QR-028 | Default cache_hit | Omit field | Defaults to false |
| QR-029 | Default queried_at | Omit field | Current timestamp |
| QR-030 | Nullable query_category | Null value | Stored as null |
| QR-031 | Nullable brand_context | Null value | Stored as null |
| QR-032 | Nullable brand_sentiment | Null value | Stored as null |
| QR-033 | Nullable competitor_mentions | Null value | Stored as null |
| QR-034 | Nullable citations | Null value | Stored as null |
| QR-035 | Nullable query_tokens | Null value | Stored as null |
| QR-036 | Nullable response_tokens | Null value | Stored as null |
| QR-037 | Nullable latency_ms | Null value | Stored as null |
| QR-038 | Sentiment enum values | positive/neutral/negative/mixed | All valid |
| QR-039 | Project relationship retrieval | Eager load | Project loaded correctly |
| QR-040 | Mentioned scope chainable | mentioned()->forPlatform() | Both applied |
| QR-041 | Large response_text | 50KB text | Stored correctly |
| QR-042 | Complex competitor_mentions | Nested objects | Stored correctly |
| QR-043 | Unicode in response_text | Multi-language | Stored correctly |
| QR-044 | Table name explicit | Check $table | ai_query_results |
| QR-045 | Timestamps enabled | Check model | created_at, updated_at |

### Category 3: AIMentionAnalytics Model (MA-001 to MA-040)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MA-001 | Model exists at correct namespace | Check class | `App\Models\AIVisibility\AIMentionAnalytics` |
| MA-002 | Create with required fields | project_id, platform, period_start, period_end | Model persisted |
| MA-003 | Fillable: project_id | Mass assign | Accepted |
| MA-004 | Fillable: platform | Mass assign | Accepted |
| MA-005 | Fillable: period_start | Mass assign | Accepted |
| MA-006 | Fillable: period_end | Mass assign | Accepted |
| MA-007 | Fillable: total_queries | Mass assign | Accepted |
| MA-008 | Fillable: brand_mentions | Mass assign | Accepted |
| MA-009 | Fillable: mention_rate | Mass assign | Accepted |
| MA-010 | Fillable: competitor_mentions | Mass assign | Accepted |
| MA-011 | Fillable: share_of_voice | Mass assign | Accepted |
| MA-012 | Fillable: avg_sentiment_score | Mass assign | Accepted |
| MA-013 | Fillable: top_query_categories | Mass assign | Accepted |
| MA-014 | Fillable: mention_trend | Mass assign | Accepted |
| MA-015 | Cast: competitor_mentions → array | JSON input | PHP array output |
| MA-016 | Cast: top_query_categories → array | JSON input | PHP array output |
| MA-017 | Cast: period_start → date | Date input | Carbon date instance |
| MA-018 | Cast: period_end → date | Date input | Carbon date instance |
| MA-019 | BelongsTo: project() | Relationship | Returns AIVisibilityProject |
| MA-020 | Default total_queries | Omit field | Defaults to 0 |
| MA-021 | Default brand_mentions | Omit field | Defaults to 0 |
| MA-022 | Default mention_rate | Omit field | Defaults to 0.00 |
| MA-023 | Default share_of_voice | Omit field | Defaults to 0.00 |
| MA-024 | Default avg_sentiment_score | Omit field | Defaults to 0.00 |
| MA-025 | Default mention_trend | Omit field | Defaults to 'stable' |
| MA-026 | Nullable competitor_mentions | Null value | Stored as null |
| MA-027 | Nullable top_query_categories | Null value | Stored as null |
| MA-028 | Trend enum values | up/down/stable | All valid |
| MA-029 | Decimal precision mention_rate | 99.99 | Stored correctly |
| MA-030 | Decimal precision share_of_voice | 100.00 | Stored correctly |
| MA-031 | Decimal precision avg_sentiment_score | 0.99 | Stored correctly |
| MA-032 | Project relationship retrieval | Eager load | Project loaded correctly |
| MA-033 | Unique constraint | Duplicate entry | Throws exception |
| MA-034 | Complex competitor_mentions JSON | Nested objects | Stored correctly |
| MA-035 | Multiple analytics per project | Create 5 | All stored |
| MA-036 | Date casting format | Y-m-d | Correct format |
| MA-037 | Table name explicit | Check $table | ai_mention_analytics |
| MA-038 | Timestamps enabled | Check model | created_at, updated_at |
| MA-039 | PHPDoc type hints | Check docblocks | IDE support |
| MA-040 | Period date range query | Filter by dates | Correct results |

### Category 4: AIVisibilityAlert Model (VA-001 to VA-040)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| VA-001 | Model exists at correct namespace | Check class | `App\Models\AIVisibility\AIVisibilityAlert` |
| VA-002 | Create with required fields | project_id, alert_type, threshold, previous_value, current_value, change_percent | Model persisted |
| VA-003 | Fillable: project_id | Mass assign | Accepted |
| VA-004 | Fillable: alert_type | Mass assign | Accepted |
| VA-005 | Fillable: threshold | Mass assign | Accepted |
| VA-006 | Fillable: previous_value | Mass assign | Accepted |
| VA-007 | Fillable: current_value | Mass assign | Accepted |
| VA-008 | Fillable: change_percent | Mass assign | Accepted |
| VA-009 | Fillable: is_read | Mass assign | Accepted |
| VA-010 | Fillable: is_resolved | Mass assign | Accepted |
| VA-011 | Fillable: triggered_at | Mass assign | Accepted |
| VA-012 | Fillable: resolved_at | Mass assign | Accepted |
| VA-013 | Cast: is_read → boolean | 1/0 input | true/false output |
| VA-014 | Cast: is_resolved → boolean | 1/0 input | true/false output |
| VA-015 | Cast: triggered_at → datetime | DateTime input | Carbon instance |
| VA-016 | Cast: resolved_at → datetime | DateTime input | Carbon instance |
| VA-017 | BelongsTo: project() | Relationship | Returns AIVisibilityProject |
| VA-018 | Scope: unread() | Query with scope | Only is_read=false |
| VA-019 | Scope: unresolved() | Query with scope | Only is_resolved=false |
| VA-020 | Default is_read | Omit field | Defaults to false |
| VA-021 | Default is_resolved | Omit field | Defaults to false |
| VA-022 | Default triggered_at | Omit field | Current timestamp |
| VA-023 | Nullable resolved_at | Null value | Stored as null |
| VA-024 | Alert type enum values | mention_spike/mention_drop/competitor_gain/sentiment_change | All valid |
| VA-025 | Decimal precision threshold | 999.99 | Stored correctly |
| VA-026 | Decimal precision change_percent | -99.99 | Stored correctly (negative) |
| VA-027 | Project relationship retrieval | Eager load | Project loaded correctly |
| VA-028 | Unread scope chainable | unread()->unresolved() | Both applied |
| VA-029 | Mark as read | Update is_read | Changed to true |
| VA-030 | Resolve alert | Update is_resolved, resolved_at | Changed to true with timestamp |
| VA-031 | Multiple alerts per project | Create 10 | All stored |
| VA-032 | Negative change_percent | -50.00 | Stored correctly |
| VA-033 | Large change_percent | 999.99 | Stored correctly |
| VA-034 | Table name explicit | Check $table | ai_visibility_alerts |
| VA-035 | Timestamps enabled | Check model | created_at, updated_at |
| VA-036 | PHPDoc type hints | Check docblocks | IDE support |
| VA-037 | Cascade delete via project | Delete project | Alert deleted |
| VA-038 | Unread count by project | Count unread | Correct count |
| VA-039 | Recent alerts query | Order by triggered_at | Most recent first |
| VA-040 | Alert summary accessor | Get summary | Formatted string |

---

## Mock Data

### Sample AIVisibilityProject

```php
$projectData = [
    'brand_name' => 'Acme Corp',
    'keywords' => ['acme', 'acme corp', 'acme products'],
    'competitors' => [
        ['name' => 'Competitor A', 'keywords' => ['comp a']],
        ['name' => 'Competitor B', 'keywords' => ['comp b']],
    ],
    'query_templates' => [
        'What are the best {industry} companies?',
        'Who makes the best {product}?',
    ],
    'tracking_frequency' => 'weekly',
    'is_active' => true,
    'last_queried_at' => now(),
];
```

### Sample AIQueryResult

```php
$queryResultData = [
    'project_id' => 1,
    'platform' => 'chatgpt',
    'model' => 'gpt-4-turbo',
    'query_text' => 'What are the best widget companies?',
    'query_category' => 'industry_comparison',
    'response_text' => 'Some of the top widget companies include Acme Corp...',
    'brand_mentioned' => true,
    'brand_context' => 'Listed as one of the top 5 widget manufacturers',
    'brand_sentiment' => 'positive',
    'competitor_mentions' => [
        ['name' => 'Competitor A', 'mentioned' => true, 'sentiment' => 'neutral'],
    ],
    'citations' => [
        ['title' => 'Widget Industry Report', 'url' => 'https://example.com/report'],
    ],
    'query_tokens' => 15,
    'response_tokens' => 450,
    'latency_ms' => 2300,
    'queried_at' => now(),
    'cache_hit' => false,
];
```

### Sample AIMentionAnalytics

```php
$analyticsData = [
    'project_id' => 1,
    'platform' => 'chatgpt',
    'period_start' => '2026-03-01',
    'period_end' => '2026-03-07',
    'total_queries' => 50,
    'brand_mentions' => 35,
    'mention_rate' => 70.00,
    'competitor_mentions' => [
        'Competitor A' => ['mentions' => 42, 'rate' => 84.00],
        'Competitor B' => ['mentions' => 28, 'rate' => 56.00],
    ],
    'share_of_voice' => 33.33,
    'avg_sentiment_score' => 0.72,
    'top_query_categories' => ['industry_comparison', 'product_review'],
    'mention_trend' => 'up',
];
```

### Sample AIVisibilityAlert

```php
$alertData = [
    'project_id' => 1,
    'alert_type' => 'mention_spike',
    'threshold' => 20.00,
    'previous_value' => 35.00,
    'current_value' => 52.00,
    'change_percent' => 48.57,
    'is_read' => false,
    'is_resolved' => false,
    'triggered_at' => now(),
    'resolved_at' => null,
];
```

---

## Coverage Targets

| Model | Tests | Target |
|-------|-------|--------|
| AIVisibilityProject | 40 | 100% AC coverage |
| AIQueryResult | 45 | 100% AC coverage |
| AIMentionAnalytics | 40 | 100% AC coverage |
| AIVisibilityAlert | 40 | 100% AC coverage |
| **Total** | **165** | **100% acceptance criteria coverage** |

---

## Test Execution

```bash
# Run all AI Visibility model tests
cd backend
php artisan test --filter=AIVisibility

# Run specific model tests
php artisan test --filter=AIVisibilityProjectTest
php artisan test --filter=AIQueryResultTest
php artisan test --filter=AIMentionAnalyticsTest
php artisan test --filter=AIVisibilityAlertTest

# Run with coverage
php artisan test --filter=AIVisibility --coverage
```

---

## Dependencies

- TASK-34-1 (Database Migrations) must be completed
- `App\Models\User` model exists
- `App\Models\AIVisibility\*` namespace structure

---

## Notes for Developer

1. **Namespace Structure**: All models must be in `App\Models\AIVisibility\` directory:
   ```
   app/Models/AIVisibility/
   ├── AIVisibilityProject.php
   ├── AIQueryResult.php
   ├── AIMentionAnalytics.php
   └── AIVisibilityAlert.php
   ```

2. **JSON Casts**: Use Laravel's array casting for JSON columns:
   ```php
   protected $casts = [
       'keywords' => 'array',
       'competitors' => 'array',
   ];
   ```

3. **Relationships**: Define both sides of relationships:
   - User hasMany AIVisibilityProject
   - AIVisibilityProject belongsTo User
   - AIVisibilityProject hasMany AIQueryResult, AIMentionAnalytics, AIVisibilityAlert

4. **Scopes**: Use local scopes for common queries:
   ```php
   public function scopeActive($query)
   {
       return $query->where('is_active', true);
   }
   ```

5. **PHPDoc**: Add type hints for IDE support:
   ```php
   /**
    * @property int $id
    * @property string $brand_name
    * @property array $keywords
    * @property bool $is_active
    */
   class AIVisibilityProject extends Model
   ```

6. **Accessor for Competitors**: Consider adding an accessor:
   ```php
   public function getCompetitorListAttribute(): array
   {
       return collect($this->competitors)->pluck('name')->toArray();
   }
   ```

---

## Comments

### PreTester Test Design (2026-03-07 18:45 UTC)

**Test Approach:**
- Model unit tests using Laravel's RefreshDatabase trait
- Test fillable attributes, casts, relationships, and scopes
- Mock database state for isolated testing
- Verify default values and null handling
- Test enum validation through database constraints

**Acceptance Criteria Mapping:**
- Each AC item has corresponding test ID(s)
- Fillable attributes tested via mass assignment
- Casts tested with type assertions
- Relationships tested with eager loading
- Scopes tested with query filtering

**Edge Cases Identified:**
- Null values in nullable columns
- Empty arrays for JSON columns
- Large JSON data storage
- Unicode characters in text fields
- Negative decimal values (for change_percent)
- Cascade delete behavior

**Notes:**
- Tests written for TDD - models don't exist yet
- Developer should implement models to pass these tests
- Tests use RefreshDatabase for clean state per test
- All tests follow Laravel best practices

---

*Created by PreTester on 2026-03-07*
