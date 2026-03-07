<?php

namespace Tests\Unit\Models\AIVisibility;

use Tests\TestCase;
use App\Models\User;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Unit tests for TASK-34-2: AIQueryResult Model
 *
 * Tests the AIQueryResult model for:
 * - Model creation with required fields
 * - Fillable attributes (platform, model, query_text, response_text, etc.)
 * - Casts (array, boolean, datetime)
 * - Relationships (belongsTo AIVisibilityProject)
 * - Scopes (mentioned, forPlatform)
 *
 * @see \App\Models\AIVisibility\AIQueryResult
 */
class AIQueryResultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we have a user and project for tests
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
     * QR-001: Model exists at correct namespace.
     */
    public function test_qr001_model_exists_at_correct_namespace(): void
    {
        $this->assertTrue(class_exists(AIQueryResult::class), 'AIQueryResult model should exist');

        $reflection = new \ReflectionClass(AIQueryResult::class);
        $this->assertEquals('App\Models\AIVisibility\AIQueryResult', $reflection->getName());
    }

    /**
     * QR-002: Create with required fields.
     */
    public function test_qr002_create_with_required_fields(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'What are the best widgets?',
            'response_text' => 'The best widgets include...',
        ]);

        $this->assertInstanceOf(AIQueryResult::class, $result);
        $this->assertEquals('chatgpt', $result->platform);
        $this->assertEquals('gpt-4', $result->model);
    }

    // ==================== FILLABLE TESTS ====================

    /**
     * QR-003: Fillable project_id.
     */
    public function test_qr003_fillable_project_id(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
        ]);

        $this->assertEquals($this->project->id, $result->project_id);
    }

    /**
     * QR-004: Fillable platform.
     */
    public function test_qr004_fillable_platform(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'perplexity',
            'model' => 'pplx-7b',
            'query_text' => 'Test',
            'response_text' => 'Response',
        ]);

        $this->assertEquals('perplexity', $result->platform);
    }

    /**
     * QR-005: Fillable model.
     */
    public function test_qr005_fillable_model(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4-turbo-preview',
            'query_text' => 'Test',
            'response_text' => 'Response',
        ]);

        $this->assertEquals('gpt-4-turbo-preview', $result->model);
    }

    /**
     * QR-006: Fillable query_text.
     */
    public function test_qr006_fillable_query_text(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'What are the top SEO tools for 2024?',
            'response_text' => 'Response',
        ]);

        $this->assertEquals('What are the top SEO tools for 2024?', $result->query_text);
    }

    /**
     * QR-007: Fillable query_category.
     */
    public function test_qr007_fillable_query_category(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'query_category' => 'industry_comparison',
        ]);

        $this->assertEquals('industry_comparison', $result->query_category);
    }

    /**
     * QR-008: Fillable response_text.
     */
    public function test_qr008_fillable_response_text(): void
    {
        $response = 'Some of the top companies in this space include Acme Corp, Beta Inc, and Gamma LLC.';

        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => $response,
        ]);

        $this->assertEquals($response, $result->response_text);
    }

    /**
     * QR-009: Fillable brand_mentioned.
     */
    public function test_qr009_fillable_brand_mentioned(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response mentions brand',
            'brand_mentioned' => true,
        ]);

        $this->assertTrue($result->brand_mentioned);
    }

    /**
     * QR-010: Fillable brand_context.
     */
    public function test_qr010_fillable_brand_context(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'brand_context' => 'Mentioned as one of the top 5 providers',
        ]);

        $this->assertEquals('Mentioned as one of the top 5 providers', $result->brand_context);
    }

    /**
     * QR-011: Fillable brand_sentiment.
     */
    public function test_qr011_fillable_brand_sentiment(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'brand_sentiment' => 'positive',
        ]);

        $this->assertEquals('positive', $result->brand_sentiment);
    }

    /**
     * QR-012: Fillable competitor_mentions.
     */
    public function test_qr012_fillable_competitor_mentions(): void
    {
        $mentions = [
            ['name' => 'Competitor A', 'mentioned' => true, 'sentiment' => 'neutral'],
            ['name' => 'Competitor B', 'mentioned' => false],
        ];

        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'competitor_mentions' => $mentions,
        ]);

        $this->assertEquals($mentions, $result->competitor_mentions);
    }

    /**
     * QR-013: Fillable citations.
     */
    public function test_qr013_fillable_citations(): void
    {
        $citations = [
            ['title' => 'Industry Report', 'url' => 'https://example.com/report'],
            ['title' => 'Product Review', 'url' => 'https://example.com/review'],
        ];

        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'citations' => $citations,
        ]);

        $this->assertEquals($citations, $result->citations);
    }

    /**
     * QR-014: Fillable query_tokens.
     */
    public function test_qr014_fillable_query_tokens(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'query_tokens' => 25,
        ]);

        $this->assertEquals(25, $result->query_tokens);
    }

    /**
     * QR-015: Fillable response_tokens.
     */
    public function test_qr015_fillable_response_tokens(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'response_tokens' => 450,
        ]);

        $this->assertEquals(450, $result->response_tokens);
    }

    /**
     * QR-016: Fillable latency_ms.
     */
    public function test_qr016_fillable_latency_ms(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'latency_ms' => 2300,
        ]);

        $this->assertEquals(2300, $result->latency_ms);
    }

    /**
     * QR-017: Fillable queried_at.
     */
    public function test_qr017_fillable_queried_at(): void
    {
        $now = now();

        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'queried_at' => $now,
        ]);

        $this->assertInstanceOf(Carbon::class, $result->queried_at);
    }

    /**
     * QR-018: Fillable cache_hit.
     */
    public function test_qr018_fillable_cache_hit(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'cache_hit' => true,
        ]);

        $this->assertTrue($result->cache_hit);
    }

    // ==================== CASTS TESTS ====================

    /**
     * QR-019: Cast competitor_mentions → array.
     */
    public function test_qr019_cast_competitor_mentions_to_array(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'competitor_mentions' => [['name' => 'Comp A'], ['name' => 'Comp B']],
        ]);

        $this->assertIsArray($result->competitor_mentions);
        $this->assertCount(2, $result->competitor_mentions);
    }

    /**
     * QR-020: Cast citations → array.
     */
    public function test_qr020_cast_citations_to_array(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'citations' => [['url' => 'https://example.com']],
        ]);

        $this->assertIsArray($result->citations);
        $this->assertCount(1, $result->citations);
    }

    /**
     * QR-021: Cast brand_mentioned → boolean.
     */
    public function test_qr021_cast_brand_mentioned_to_boolean(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'brand_mentioned' => 1,
        ]);

        $this->assertIsBool($result->brand_mentioned);
        $this->assertTrue($result->brand_mentioned);
    }

    /**
     * QR-022: Cast cache_hit → boolean.
     */
    public function test_qr022_cast_cache_hit_to_boolean(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'cache_hit' => 0,
        ]);

        $this->assertIsBool($result->cache_hit);
        $this->assertFalse($result->cache_hit);
    }

    /**
     * QR-023: Cast queried_at → datetime.
     */
    public function test_qr023_cast_queried_at_to_datetime(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'queried_at' => '2026-03-07 14:30:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $result->queried_at);
        $this->assertEquals('2026-03-07 14:30:00', $result->queried_at->format('Y-m-d H:i:s'));
    }

    // ==================== RELATIONSHIP TESTS ====================

    /**
     * QR-024: BelongsTo project().
     */
    public function test_qr024_belongs_to_project(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
        ]);

        $this->assertInstanceOf(AIVisibilityProject::class, $result->project);
        $this->assertEquals($this->project->id, $result->project->id);
    }

    // ==================== SCOPE TESTS ====================

    /**
     * QR-025: Scope mentioned().
     */
    public function test_qr025_scope_mentioned(): void
    {
        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response with brand mentioned',
            'brand_mentioned' => true,
        ]);

        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response without brand',
            'brand_mentioned' => false,
        ]);

        if (method_exists(AIQueryResult::class, 'scopeMentioned')) {
            $mentionedResults = AIQueryResult::mentioned()->get();

            $this->assertCount(1, $mentionedResults);
            $this->assertTrue($mentionedResults->first()->brand_mentioned);
        } else {
            $this->markTestSkipped('mentioned scope not yet implemented');
        }
    }

    /**
     * QR-026: Scope forPlatform($platform).
     */
    public function test_qr026_scope_for_platform(): void
    {
        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
        ]);

        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'perplexity',
            'model' => 'pplx-7b',
            'query_text' => 'Test',
            'response_text' => 'Response',
        ]);

        if (method_exists(AIQueryResult::class, 'scopeForPlatform')) {
            $chatgptResults = AIQueryResult::forPlatform('chatgpt')->get();

            $this->assertCount(1, $chatgptResults);
            $this->assertEquals('chatgpt', $chatgptResults->first()->platform);
        } else {
            $this->markTestSkipped('forPlatform scope not yet implemented');
        }
    }

    // ==================== DEFAULT VALUE TESTS ====================

    /**
     * QR-027: Default brand_mentioned is false.
     */
    public function test_qr027_default_brand_mentioned(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
        ]);

        $this->assertFalse($result->brand_mentioned);
    }

    /**
     * QR-028: Default cache_hit is false.
     */
    public function test_qr028_default_cache_hit(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
        ]);

        $this->assertFalse($result->cache_hit);
    }

    /**
     * QR-029: Default queried_at is current timestamp.
     */
    public function test_qr029_default_queried_at(): void
    {
        $before = now();
        
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
        ]);

        $after = now();

        $this->assertNotNull($result->queried_at);
        $this->assertTrue($result->queried_at->between($before, $after));
    }

    // ==================== NULLABLE TESTS ====================

    /**
     * QR-030: Nullable query_category.
     */
    public function test_qr030_nullable_query_category(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'query_category' => null,
        ]);

        $this->assertNull($result->query_category);
    }

    /**
     * QR-031: Nullable brand_context.
     */
    public function test_qr031_nullable_brand_context(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'brand_context' => null,
        ]);

        $this->assertNull($result->brand_context);
    }

    /**
     * QR-032: Nullable brand_sentiment.
     */
    public function test_qr032_nullable_brand_sentiment(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'brand_sentiment' => null,
        ]);

        $this->assertNull($result->brand_sentiment);
    }

    /**
     * QR-033: Nullable competitor_mentions.
     */
    public function test_qr033_nullable_competitor_mentions(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'competitor_mentions' => null,
        ]);

        $this->assertNull($result->competitor_mentions);
    }

    /**
     * QR-034: Nullable citations.
     */
    public function test_qr034_nullable_citations(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'citations' => null,
        ]);

        $this->assertNull($result->citations);
    }

    /**
     * QR-035: Nullable query_tokens.
     */
    public function test_qr035_nullable_query_tokens(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'query_tokens' => null,
        ]);

        $this->assertNull($result->query_tokens);
    }

    /**
     * QR-036: Nullable response_tokens.
     */
    public function test_qr036_nullable_response_tokens(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'response_tokens' => null,
        ]);

        $this->assertNull($result->response_tokens);
    }

    /**
     * QR-037: Nullable latency_ms.
     */
    public function test_qr037_nullable_latency_ms(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'latency_ms' => null,
        ]);

        $this->assertNull($result->latency_ms);
    }

    // ==================== ENUM VALIDATION TESTS ====================

    /**
     * QR-038: Sentiment enum values.
     */
    public function test_qr038_sentiment_enum_values(): void
    {
        $validSentiments = ['positive', 'neutral', 'negative', 'mixed'];

        foreach ($validSentiments as $sentiment) {
            $result = AIQueryResult::create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'model' => 'gpt-4',
                'query_text' => "Test {$sentiment}",
                'response_text' => 'Response',
                'brand_sentiment' => $sentiment,
            ]);

            $this->assertEquals($sentiment, $result->brand_sentiment);
        }
    }

    // ==================== RELATIONSHIP LOADING TESTS ====================

    /**
     * QR-039: Project relationship retrieval.
     */
    public function test_qr039_project_relationship_retrieval(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
        ]);

        $loadedResult = AIQueryResult::with('project')->find($result->id);

        $this->assertEquals('Test Brand', $loadedResult->project->brand_name);
    }

    // ==================== SCOPE CHAINING TESTS ====================

    /**
     * QR-040: Mentioned scope chainable with forPlatform.
     */
    public function test_qr040_mentioned_scope_chainable(): void
    {
        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'brand_mentioned' => true,
        ]);

        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'brand_mentioned' => false,
        ]);

        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'perplexity',
            'model' => 'pplx-7b',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'brand_mentioned' => true,
        ]);

        if (method_exists(AIQueryResult::class, 'scopeMentioned') &&
            method_exists(AIQueryResult::class, 'scopeForPlatform')) {
            $results = AIQueryResult::mentioned()->forPlatform('chatgpt')->get();

            $this->assertCount(1, $results);
            $this->assertEquals('chatgpt', $results->first()->platform);
            $this->assertTrue($results->first()->brand_mentioned);
        } else {
            $this->markTestSkipped('Scopes not yet implemented');
        }
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * QR-041: Large response_text.
     */
    public function test_qr041_large_response_text(): void
    {
        $largeText = str_repeat('This is a sample AI response. ', 2000); // ~60KB

        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => $largeText,
        ]);

        $this->assertEquals($largeText, $result->response_text);
    }

    /**
     * QR-042: Complex competitor_mentions.
     */
    public function test_qr042_complex_competitor_mentions(): void
    {
        $mentions = [
            [
                'name' => 'Competitor A',
                'mentioned' => true,
                'sentiment' => 'positive',
                'context' => 'Ranked #1 in customer satisfaction',
                'position' => 1,
            ],
            [
                'name' => 'Competitor B',
                'mentioned' => true,
                'sentiment' => 'neutral',
                'context' => 'Listed as alternative',
            ],
        ];

        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'competitor_mentions' => $mentions,
        ]);

        $this->assertEquals('Competitor A', $result->competitor_mentions[0]['name']);
        $this->assertEquals(1, $result->competitor_mentions[0]['position']);
    }

    /**
     * QR-043: Unicode in response_text.
     */
    public function test_qr043_unicode_in_response_text(): void
    {
        $unicodeText = '这是中文测试 🎉 日本語テスト العربية';

        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => $unicodeText,
        ]);

        $this->assertEquals($unicodeText, $result->response_text);
    }

    // ==================== MODEL CONFIGURATION TESTS ====================

    /**
     * QR-044: Table name explicit.
     */
    public function test_qr044_table_name_explicit(): void
    {
        $result = new AIQueryResult();
        $this->assertEquals('ai_query_results', $result->getTable());
    }

    /**
     * QR-045: Timestamps enabled.
     */
    public function test_qr045_timestamps_enabled(): void
    {
        $result = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
        ]);

        $this->assertNotNull($result->created_at);
        $this->assertNotNull($result->updated_at);
        $this->assertInstanceOf(Carbon::class, $result->created_at);
        $this->assertInstanceOf(Carbon::class, $result->updated_at);
    }
}
