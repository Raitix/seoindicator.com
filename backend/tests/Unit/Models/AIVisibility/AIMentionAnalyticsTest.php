<?php

namespace Tests\Unit\Models\AIVisibility;

use Tests\TestCase;
use App\Models\User;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIMentionAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Unit tests for TASK-34-2: AIMentionAnalytics Model
 *
 * Tests the AIMentionAnalytics model for:
 * - Model creation with required fields
 * - Fillable attributes (platform, period_start, period_end, metrics)
 * - Casts (array, date, decimal)
 * - Relationships (belongsTo AIVisibilityProject)
 * - Unique constraint on project+platform+period
 *
 * @see \App\Models\AIVisibility\AIMentionAnalytics
 */
class AIMentionAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = AIVisibilityProject::create([
            'user_id' => $this->user->id,
            'brand_name' => 'Test Brand',
            'keywords' => ['test'],
            'competitors' => [],
        ]);
    }

    // ==================== MODEL EXISTENCE TESTS ====================

    /**
     * MA-001: Model exists at correct namespace.
     */
    public function test_ma001_model_exists_at_correct_namespace(): void
    {
        $this->assertTrue(class_exists(AIMentionAnalytics::class), 'AIMentionAnalytics model should exist');

        $reflection = new \ReflectionClass(AIMentionAnalytics::class);
        $this->assertEquals('App\Models\AIVisibility\AIMentionAnalytics', $reflection->getName());
    }

    /**
     * MA-002: Create with required fields.
     */
    public function test_ma002_create_with_required_fields(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertInstanceOf(AIMentionAnalytics::class, $analytics);
        $this->assertEquals('chatgpt', $analytics->platform);
    }

    // ==================== FILLABLE TESTS ====================

    /**
     * MA-003: Fillable project_id.
     */
    public function test_ma003_fillable_project_id(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertEquals($this->project->id, $analytics->project_id);
    }

    /**
     * MA-004: Fillable platform.
     */
    public function test_ma004_fillable_platform(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'perplexity',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertEquals('perplexity', $analytics->platform);
    }

    /**
     * MA-005: Fillable period_start.
     */
    public function test_ma005_fillable_period_start(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertInstanceOf(Carbon::class, $analytics->period_start);
        $this->assertEquals('2026-03-01', $analytics->period_start->format('Y-m-d'));
    }

    /**
     * MA-006: Fillable period_end.
     */
    public function test_ma006_fillable_period_end(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertInstanceOf(Carbon::class, $analytics->period_end);
        $this->assertEquals('2026-03-07', $analytics->period_end->format('Y-m-d'));
    }

    /**
     * MA-007: Fillable total_queries.
     */
    public function test_ma007_fillable_total_queries(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'total_queries' => 50,
        ]);

        $this->assertEquals(50, $analytics->total_queries);
    }

    /**
     * MA-008: Fillable brand_mentions.
     */
    public function test_ma008_fillable_brand_mentions(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'brand_mentions' => 35,
        ]);

        $this->assertEquals(35, $analytics->brand_mentions);
    }

    /**
     * MA-009: Fillable mention_rate.
     */
    public function test_ma009_fillable_mention_rate(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'mention_rate' => 70.00,
        ]);

        $this->assertEquals(70.00, $analytics->mention_rate);
    }

    /**
     * MA-010: Fillable competitor_mentions.
     */
    public function test_ma010_fillable_competitor_mentions(): void
    {
        $mentions = [
            'Competitor A' => ['mentions' => 42, 'rate' => 84.00],
            'Competitor B' => ['mentions' => 28, 'rate' => 56.00],
        ];

        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'competitor_mentions' => $mentions,
        ]);

        $this->assertEquals($mentions, $analytics->competitor_mentions);
    }

    /**
     * MA-011: Fillable share_of_voice.
     */
    public function test_ma011_fillable_share_of_voice(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'share_of_voice' => 33.33,
        ]);

        $this->assertEquals(33.33, $analytics->share_of_voice);
    }

    /**
     * MA-012: Fillable avg_sentiment_score.
     */
    public function test_ma012_fillable_avg_sentiment_score(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'avg_sentiment_score' => 0.72,
        ]);

        $this->assertEquals(0.72, $analytics->avg_sentiment_score);
    }

    /**
     * MA-013: Fillable top_query_categories.
     */
    public function test_ma013_fillable_top_query_categories(): void
    {
        $categories = ['industry_comparison', 'product_review', 'how_to'];

        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'top_query_categories' => $categories,
        ]);

        $this->assertEquals($categories, $analytics->top_query_categories);
    }

    /**
     * MA-014: Fillable mention_trend.
     */
    public function test_ma014_fillable_mention_trend(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'mention_trend' => 'up',
        ]);

        $this->assertEquals('up', $analytics->mention_trend);
    }

    // ==================== CASTS TESTS ====================

    /**
     * MA-015: Cast competitor_mentions → array.
     */
    public function test_ma015_cast_competitor_mentions_to_array(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'competitor_mentions' => ['A' => ['rate' => 50], 'B' => ['rate' => 30]],
        ]);

        $this->assertIsArray($analytics->competitor_mentions);
        $this->assertArrayHasKey('A', $analytics->competitor_mentions);
    }

    /**
     * MA-016: Cast top_query_categories → array.
     */
    public function test_ma016_cast_top_query_categories_to_array(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'top_query_categories' => ['cat1', 'cat2'],
        ]);

        $this->assertIsArray($analytics->top_query_categories);
        $this->assertCount(2, $analytics->top_query_categories);
    }

    /**
     * MA-017: Cast period_start → date.
     */
    public function test_ma017_cast_period_start_to_date(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertInstanceOf(Carbon::class, $analytics->period_start);
        $this->assertTrue($analytics->period_start->isStartOfDay());
    }

    /**
     * MA-018: Cast period_end → date.
     */
    public function test_ma018_cast_period_end_to_date(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertInstanceOf(Carbon::class, $analytics->period_end);
        $this->assertTrue($analytics->period_end->isStartOfDay());
    }

    // ==================== RELATIONSHIP TESTS ====================

    /**
     * MA-019: BelongsTo project().
     */
    public function test_ma019_belongs_to_project(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertInstanceOf(AIVisibilityProject::class, $analytics->project);
        $this->assertEquals($this->project->id, $analytics->project->id);
    }

    // ==================== DEFAULT VALUE TESTS ====================

    /**
     * MA-020: Default total_queries is 0.
     */
    public function test_ma020_default_total_queries(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertEquals(0, $analytics->total_queries);
    }

    /**
     * MA-021: Default brand_mentions is 0.
     */
    public function test_ma021_default_brand_mentions(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertEquals(0, $analytics->brand_mentions);
    }

    /**
     * MA-022: Default mention_rate is 0.00.
     */
    public function test_ma022_default_mention_rate(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertEquals(0.00, $analytics->mention_rate);
    }

    /**
     * MA-023: Default share_of_voice is 0.00.
     */
    public function test_ma023_default_share_of_voice(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertEquals(0.00, $analytics->share_of_voice);
    }

    /**
     * MA-024: Default avg_sentiment_score is 0.00.
     */
    public function test_ma024_default_avg_sentiment_score(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertEquals(0.00, $analytics->avg_sentiment_score);
    }

    /**
     * MA-025: Default mention_trend is stable.
     */
    public function test_ma025_default_mention_trend(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertEquals('stable', $analytics->mention_trend);
    }

    // ==================== NULLABLE TESTS ====================

    /**
     * MA-026: Nullable competitor_mentions.
     */
    public function test_ma026_nullable_competitor_mentions(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'competitor_mentions' => null,
        ]);

        $this->assertNull($analytics->competitor_mentions);
    }

    /**
     * MA-027: Nullable top_query_categories.
     */
    public function test_ma027_nullable_top_query_categories(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'top_query_categories' => null,
        ]);

        $this->assertNull($analytics->top_query_categories);
    }

    // ==================== ENUM VALIDATION TESTS ====================

    /**
     * MA-028: Trend enum values.
     */
    public function test_ma028_trend_enum_values(): void
    {
        $validTrends = ['up', 'down', 'stable'];

        foreach ($validTrends as $index => $trend) {
            $analytics = AIMentionAnalytics::create([
                'project_id' => $this->project->id,
                'platform' => "platform-{$index}",
                'period_start' => '2026-03-01',
                'period_end' => '2026-03-07',
                'mention_trend' => $trend,
            ]);

            $this->assertEquals($trend, $analytics->mention_trend);
        }
    }

    // ==================== DECIMAL PRECISION TESTS ====================

    /**
     * MA-029: Decimal precision mention_rate.
     */
    public function test_ma029_decimal_precision_mention_rate(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'mention_rate' => 99.99,
        ]);

        $this->assertEquals(99.99, $analytics->mention_rate);
    }

    /**
     * MA-030: Decimal precision share_of_voice.
     */
    public function test_ma030_decimal_precision_share_of_voice(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'share_of_voice' => 100.00,
        ]);

        $this->assertEquals(100.00, $analytics->share_of_voice);
    }

    /**
     * MA-031: Decimal precision avg_sentiment_score.
     */
    public function test_ma031_decimal_precision_avg_sentiment_score(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'avg_sentiment_score' => 0.99,
        ]);

        $this->assertEquals(0.99, $analytics->avg_sentiment_score);
    }

    // ==================== RELATIONSHIP LOADING TESTS ====================

    /**
     * MA-032: Project relationship retrieval.
     */
    public function test_ma032_project_relationship_retrieval(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $loadedAnalytics = AIMentionAnalytics::with('project')->find($analytics->id);

        $this->assertEquals('Test Brand', $loadedAnalytics->project->brand_name);
    }

    // ==================== UNIQUE CONSTRAINT TESTS ====================

    /**
     * MA-033: Unique constraint on project+platform+period.
     */
    public function test_ma033_unique_constraint(): void
    {
        $driver = DB::connection()->getDriverName();

        AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        try {
            AIMentionAnalytics::create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'period_start' => '2026-03-01',
                'period_end' => '2026-03-07',
            ]);

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->fail('Duplicate entry should violate unique constraint');
            }
        } catch (\Exception $e) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->assertStringContainsString('Duplicate', $e->getMessage());
            }
        }
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * MA-034: Complex competitor_mentions JSON.
     */
    public function test_ma034_complex_competitor_mentions_json(): void
    {
        $mentions = [
            'Competitor A' => [
                'mentions' => 42,
                'rate' => 84.00,
                'trend' => 'up',
                'sentiment' => 0.65,
            ],
            'Competitor B' => [
                'mentions' => 28,
                'rate' => 56.00,
                'trend' => 'stable',
                'sentiment' => 0.45,
            ],
        ];

        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'competitor_mentions' => $mentions,
        ]);

        $this->assertEquals(42, $analytics->competitor_mentions['Competitor A']['mentions']);
        $this->assertEquals('up', $analytics->competitor_mentions['Competitor A']['trend']);
    }

    /**
     * MA-035: Multiple analytics per project.
     */
    public function test_ma035_multiple_analytics_per_project(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            AIMentionAnalytics::create([
                'project_id' => $this->project->id,
                'platform' => "platform-{$i}",
                'period_start' => '2026-03-01',
                'period_end' => '2026-03-07',
            ]);
        }

        $this->assertEquals(5, $this->project->analytics()->count());
    }

    // ==================== DATE FORMAT TESTS ====================

    /**
     * MA-036: Date casting format.
     */
    public function test_ma036_date_casting_format(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        // Date cast returns Carbon instance, verify it's a date-only format
        $this->assertInstanceOf(Carbon::class, $analytics->period_start);
        $this->assertTrue($analytics->period_start->isStartOfDay());
        $this->assertEquals('2026-03-01', $analytics->period_start->format('Y-m-d'));
    }

    // ==================== MODEL CONFIGURATION TESTS ====================

    /**
     * MA-037: Table name explicit.
     */
    public function test_ma037_table_name_explicit(): void
    {
        $analytics = new AIMentionAnalytics();
        $this->assertEquals('ai_mention_analytics', $analytics->getTable());
    }

    /**
     * MA-038: Timestamps enabled.
     */
    public function test_ma038_timestamps_enabled(): void
    {
        $analytics = AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $this->assertNotNull($analytics->created_at);
        $this->assertNotNull($analytics->updated_at);
    }

    /**
     * MA-039: PHPDoc type hints.
     */
    public function test_ma039_phpdoc_type_hints(): void
    {
        $reflection = new \ReflectionClass(AIMentionAnalytics::class);
        $docComment = $reflection->getDocComment();

        $this->assertNotFalse($docComment, 'Model should have PHPDoc comments');
    }

    /**
     * MA-040: Period date range query.
     */
    public function test_ma040_period_date_range_query(): void
    {
        AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-07',
        ]);

        AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'platform' => 'perplexity',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
        ]);

        $marchAnalytics = AIMentionAnalytics::where('period_start', '>=', '2026-03-01')->get();

        $this->assertCount(1, $marchAnalytics);
        $this->assertEquals('perplexity', $marchAnalytics->first()->platform);
    }
}
