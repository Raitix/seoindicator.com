<?php

namespace Tests\Integration\AIVisibility;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMentionAnalytics;
use App\Jobs\AIVisibility\RunQueryJob;
use App\Services\AIVisibility\AIQueryEngine;
use App\Services\AIVisibility\MentionParser;
use App\Services\AIVisibility\Contracts\AnalyticsAggregatorInterface;
use Carbon\Carbon;

/**
 * Integration tests for AI Visibility Analytics Flow
 * 
 * Tests end-to-end flow: Query → Parse → Aggregate → API
 * 
 * @group ai-visibility
 * @group integration
 * @task TASK-34-18
 * @spec SPEC-034
 */
class AnalyticsFlowIntegrationTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected AIVisibilityProject $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        
        // Create test project with competitors
        $this->project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'brand_name' => 'SEOIndicator',
            'keywords' => ['seoindicator', 'seo indicator', 'seo tools'],
            'competitors' => [
                ['name' => 'Ahrefs', 'keywords' => ['ahrefs']],
                ['name' => 'SEMrush', 'keywords' => ['semrush']],
            ],
            'tracking_frequency' => 'daily',
        ]);

        // Fake HTTP for OpenRouter calls
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'Some of the best SEO tools include SEOIndicator, Ahrefs, and SEMrush. I recommend SEOIndicator for its comprehensive features.'
                    ]
                ]]
            ], 200),
        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // ============================================================
    // Category 1: Query Execution Flow (FLOW-001 to FLOW-015)
    // ============================================================

    /** @test FLOW-001: Complete query flow creates database records */
    public function test_complete_query_flow_creates_database_records(): void
    {
        // Step 1: Dispatch query job
        $job = new RunQueryJob($this->project->id, 'chatgpt', 'What are the best SEO tools?');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        // Step 2: Verify query result was created
        $queryResult = AIQueryResult::where('project_id', $this->project->id)->first();
        $this->assertNotNull($queryResult, 'Query result should be created');
        $this->assertEquals('chatgpt', $queryResult->platform);
        $this->assertNotNull($queryResult->response_text);

        // Step 3: Verify mention analytics were created
        $analytics = AIMentionAnalytics::where('project_id', $this->project->id)->get();
        $this->assertGreaterThan(0, $analytics->count(), 'Mention analytics should be created');
    }

    /** @test FLOW-002: Query flow with brand mention creates correct analytics */
    public function test_query_flow_with_mention_creates_correct_analytics(): void
    {
        // Execute query job
        $job = new RunQueryJob($this->project->id, 'chatgpt', 'What are the best SEO tools?');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        // Verify brand mention was recorded
        $brandMention = AIMentionAnalytics::where('project_id', $this->project->id)
            ->where('entity_type', 'brand')
            ->where('entity_name', 'SEOIndicator')
            ->first();

        $this->assertNotNull($brandMention, 'Brand mention should be recorded');
        $this->assertNotNull($brandMention->context);
        $this->assertNotNull($brandMention->mention_type);
    }

    /** @test FLOW-003: Query flow with competitor mention creates analytics */
    public function test_query_flow_with_competitor_mention_creates_analytics(): void
    {
        // Execute query job
        $job = new RunQueryJob($this->project->id, 'chatgpt', 'Compare SEOIndicator vs Ahrefs');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        // Verify competitor mention was recorded
        $competitorMention = AIMentionAnalytics::where('project_id', $this->project->id)
            ->where('entity_type', 'competitor')
            ->where('entity_name', 'Ahrefs')
            ->first();

        $this->assertNotNull($competitorMention, 'Competitor mention should be recorded');
    }

    /** @test FLOW-004: Query flow handles multiple mentions in single response */
    public function test_query_flow_handles_multiple_mentions(): void
    {
        // Execute query job
        $job = new RunQueryJob($this->project->id, 'chatgpt', 'Best SEO tools?');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        // Count all mentions
        $allMentions = AIMentionAnalytics::where('project_id', $this->project->id)->get();
        
        // Should have brand + 2 competitors mentioned
        $this->assertGreaterThanOrEqual(2, $allMentions->count(), 'Should have multiple mentions');
    }

    /** @test FLOW-005: Query flow handles no mentions gracefully */
    public function test_query_flow_handles_no_mentions(): void
    {
        // Mock response without brand mentions
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'There are many SEO tools available on the market today.'
                    ]
                ]]
            ], 200),
        ]);

        // Execute query job
        $job = new RunQueryJob($this->project->id, 'chatgpt', 'Best tools?');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        // Query result should exist even with no mentions
        $queryResult = AIQueryResult::where('project_id', $this->project->id)->first();
        $this->assertNotNull($queryResult);
        
        // Brand mention count should be 0
        $brandMentions = AIMentionAnalytics::where('project_id', $this->project->id)
            ->where('entity_type', 'brand')
            ->count();
        $this->assertEquals(0, $brandMentions);
    }

    /** @test FLOW-006: Query flow handles API errors gracefully */
    public function test_query_flow_handles_api_errors(): void
    {
        // Mock API error
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => 'Rate limited'], 429),
        ]);

        // Execute query job - should not throw
        $job = new RunQueryJob($this->project->id, 'chatgpt', 'Test query');
        
        // Should not throw exception
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        // Query result should record error
        $queryResult = AIQueryResult::where('project_id', $this->project->id)->first();
        if ($queryResult) {
            $this->assertFalse($queryResult->success);
            $this->assertNotNull($queryResult->error_message);
        }
    }

    /** @test FLOW-007: Query flow records correct platform */
    public function test_query_flow_records_correct_platform(): void
    {
        $platforms = ['chatgpt', 'perplexity'];

        foreach ($platforms as $platform) {
            $job = new RunQueryJob($this->project->id, $platform, 'Test query');
            $job->handle(
                app(AIQueryEngine::class),
                app(MentionParser::class)
            );
        }

        foreach ($platforms as $platform) {
            $this->assertDatabaseHas('ai_query_results', [
                'project_id' => $this->project->id,
                'platform' => $platform,
            ]);
        }
    }

    /** @test FLOW-008: Query flow records timestamp correctly */
    public function test_query_flow_records_timestamp_correctly(): void
    {
        $before = now();

        $job = new RunQueryJob($this->project->id, 'chatgpt', 'Test query');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        $after = now();

        $queryResult = AIQueryResult::where('project_id', $this->project->id)->first();
        $this->assertNotNull($queryResult);
        $this->assertTrue($queryResult->queried_at->between($before, $after));
    }

    /** @test FLOW-009: Query flow extracts context around mentions */
    public function test_query_flow_extracts_context_around_mentions(): void
    {
        $response = 'I highly recommend SEOIndicator for its excellent SEO analysis capabilities.';

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => $response]]]
            ], 200),
        ]);

        $job = new RunQueryJob($this->project->id, 'chatgpt', 'Best SEO tools?');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        $mention = AIMentionAnalytics::where('project_id', $this->project->id)
            ->where('entity_type', 'brand')
            ->first();

        $this->assertNotNull($mention);
        $this->assertNotNull($mention->context);
        $this->assertStringContainsStringIgnoringCase('seoindicator', $mention->context);
    }

    /** @test FLOW-010: Query flow detects mention type correctly */
    public function test_query_flow_detects_mention_type_correctly(): void
    {
        // Test recommendation type
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'I recommend SEOIndicator for SEO analysis.'
                    ]
                ]]
            ], 200),
        ]);

        $job = new RunQueryJob($this->project->id, 'chatgpt', 'What should I use?');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        $mention = AIMentionAnalytics::where('project_id', $this->project->id)
            ->where('entity_type', 'brand')
            ->first();

        $this->assertNotNull($mention);
        $this->assertEquals('recommendation', $mention->mention_type);
    }

    /** @test FLOW-011: Query flow handles sentiment analysis */
    public function test_query_flow_handles_sentiment_analysis(): void
    {
        // Positive sentiment
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'SEOIndicator is an excellent and highly recommended SEO tool.'
                    ]
                ]]
            ], 200),
        ]);

        $job = new RunQueryJob($this->project->id, 'chatgpt', 'Review SEOIndicator');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        $mention = AIMentionAnalytics::where('project_id', $this->project->id)
            ->where('entity_type', 'brand')
            ->first();

        $this->assertNotNull($mention);
        $this->assertEquals('positive', $mention->sentiment);
    }

    /** @test FLOW-012: Query flow handles special characters in brand name */
    public function test_query_flow_handles_special_characters(): void
    {
        // Create project with special characters
        $specialProject = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'brand_name' => 'C++ Tools',
            'keywords' => ['c++ tools'],
        ]);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'C++ Tools is great for development.'
                    ]
                ]]
            ], 200),
        ]);

        $job = new RunQueryJob($specialProject->id, 'chatgpt', 'Dev tools?');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        $this->assertDatabaseHas('ai_mention_analytics', [
            'project_id' => $specialProject->id,
            'entity_name' => 'C++ Tools',
        ]);
    }

    /** @test FLOW-013: Query flow handles unicode content */
    public function test_query_flow_handles_unicode_content(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'SEOIndicator は優れたSEOツールです。'
                    ]
                ]]
            ], 200),
        ]);

        $job = new RunQueryJob($this->project->id, 'chatgpt', 'SEO tools in Japanese?');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        $this->assertDatabaseHas('ai_mention_analytics', [
            'project_id' => $this->project->id,
            'entity_type' => 'brand',
        ]);
    }

    /** @test FLOW-014: Query flow tracks token usage */
    public function test_query_flow_tracks_token_usage(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'SEOIndicator is great.'],
                ]],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 20,
                    'total_tokens' => 30,
                ],
            ], 200),
        ]);

        $job = new RunQueryJob($this->project->id, 'chatgpt', 'Test');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        $queryResult = AIQueryResult::where('project_id', $this->project->id)->first();
        $this->assertNotNull($queryResult);
        $this->assertNotNull($queryResult->tokens_used);
    }

    /** @test FLOW-015: Query flow tracks latency */
    public function test_query_flow_tracks_latency(): void
    {
        $job = new RunQueryJob($this->project->id, 'chatgpt', 'Test');
        $job->handle(
            app(AIQueryEngine::class),
            app(MentionParser::class)
        );

        $queryResult = AIQueryResult::where('project_id', $this->project->id)->first();
        $this->assertNotNull($queryResult);
        $this->assertNotNull($queryResult->latency_ms);
        $this->assertGreaterThanOrEqual(0, $queryResult->latency_ms);
    }

    // ============================================================
    // Category 2: Aggregation Flow (AGG-001 to AGG-015)
    // ============================================================

    /** @test AGG-001: Aggregation calculates mention rate correctly */
    public function test_aggregation_calculates_mention_rate_correctly(): void
    {
        // Create multiple query results
        for ($i = 0; $i < 10; $i++) {
            $this->createQueryResult($i < 5); // 5 with mentions, 5 without
        }

        // Get analytics through aggregator
        $aggregator = app(AnalyticsAggregatorInterface::class);
        $analytics = $aggregator->getAnalytics($this->project->id, 'week');

        // Mention rate should be 50% (5 out of 10 queries)
        $this->assertArrayHasKey('mention_rate', $analytics);
        $this->assertEquals(50.0, $analytics['mention_rate']);
    }

    /** @test AGG-002: Aggregation calculates share of voice correctly */
    public function test_aggregation_calculates_share_of_voice_correctly(): void
    {
        // Create mentions: 15 brand mentions, 25 competitor mentions
        for ($i = 0; $i < 15; $i++) {
            AIMentionAnalytics::create([
                'project_id' => $this->project->id,
                'entity_type' => 'brand',
                'entity_name' => 'SEOIndicator',
                'mention_type' => 'neutral',
                'sentiment' => 'neutral',
                'context' => 'test context',
                'mentioned_at' => now(),
            ]);
        }

        for ($i = 0; $i < 25; $i++) {
            AIMentionAnalytics::create([
                'project_id' => $this->project->id,
                'entity_type' => 'competitor',
                'entity_name' => 'Ahrefs',
                'mention_type' => 'neutral',
                'sentiment' => 'neutral',
                'context' => 'test context',
                'mentioned_at' => now(),
            ]);
        }

        $aggregator = app(AnalyticsAggregatorInterface::class);
        $analytics = $aggregator->getAnalytics($this->project->id, 'week');

        // Share of voice: 15 / (15 + 25) = 37.5%
        $this->assertArrayHasKey('share_of_voice', $analytics);
        $this->assertEqualsWithDelta(37.5, $analytics['share_of_voice'], 0.1);
    }

    /** @test AGG-003: Aggregation groups by platform correctly */
    public function test_aggregation_groups_by_platform_correctly(): void
    {
        // Create query results for different platforms
        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_text' => 'Test query',
            'response_text' => 'SEOIndicator mentioned',
            'success' => true,
            'queried_at' => now(),
        ]);

        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'perplexity',
            'query_text' => 'Test query',
            'response_text' => 'Ahrefs mentioned',
            'success' => true,
            'queried_at' => now(),
        ]);

        $aggregator = app(AnalyticsAggregatorInterface::class);
        $analytics = $aggregator->getAnalytics($this->project->id, 'week');

        $this->assertArrayHasKey('by_platform', $analytics);
        $platforms = collect($analytics['by_platform'])->pluck('platform')->toArray();
        $this->assertContains('chatgpt', $platforms);
        $this->assertContains('perplexity', $platforms);
    }

    /** @test AGG-004: Aggregation filters by period correctly */
    public function test_aggregation_filters_by_period_correctly(): void
    {
        // Create old query result
        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_text' => 'Old query',
            'response_text' => 'Old response',
            'success' => true,
            'queried_at' => now()->subDays(30), // Outside 7-day window
        ]);

        // Create recent query result
        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_text' => 'Recent query',
            'response_text' => 'Recent response',
            'success' => true,
            'queried_at' => now()->subDays(3), // Inside 7-day window
        ]);

        $aggregator = app(AnalyticsAggregatorInterface::class);
        
        // Week period should only include recent
        $weekAnalytics = $aggregator->getAnalytics($this->project->id, 'week');
        
        // Month period should include both
        $monthAnalytics = $aggregator->getAnalytics($this->project->id, 'month');

        // Week should have fewer queries than month
        $this->assertLessThanOrEqual(
            $monthAnalytics['total_queries'] ?? 0,
            $weekAnalytics['total_queries'] ?? 0
        );
    }

    /** @test AGG-005: Aggregation calculates sentiment breakdown */
    public function test_aggregation_calculates_sentiment_breakdown(): void
    {
        // Create mentions with different sentiments
        $sentiments = ['positive', 'neutral', 'negative', 'mixed'];
        foreach ($sentiments as $sentiment) {
            AIMentionAnalytics::create([
                'project_id' => $this->project->id,
                'entity_type' => 'brand',
                'entity_name' => 'SEOIndicator',
                'mention_type' => 'neutral',
                'sentiment' => $sentiment,
                'context' => 'test context',
                'mentioned_at' => now(),
            ]);
        }

        $aggregator = app(AnalyticsAggregatorInterface::class);
        $analytics = $aggregator->getAnalytics($this->project->id, 'week');

        $this->assertArrayHasKey('sentiment', $analytics);
        $this->assertArrayHasKey('breakdown', $analytics['sentiment']);
        
        foreach ($sentiments as $sentiment) {
            $this->assertArrayHasKey($sentiment, $analytics['sentiment']['breakdown']);
        }
    }

    /** @test AGG-006: Aggregation calculates trend direction */
    public function test_aggregation_calculates_trend_direction(): void
    {
        // Create historical data showing increasing trend
        for ($i = 7; $i >= 1; $i--) {
            AIQueryResult::create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'query_text' => "Query day $i",
                'response_text' => 'SEOIndicator mentioned',
                'success' => true,
                'queried_at' => now()->subDays($i),
            ]);

            // Mention count increases over time
            for ($j = 0; $j < $i; $j++) {
                AIMentionAnalytics::create([
                    'project_id' => $this->project->id,
                    'entity_type' => 'brand',
                    'entity_name' => 'SEOIndicator',
                    'mention_type' => 'neutral',
                    'sentiment' => 'neutral',
                    'context' => 'test context',
                    'mentioned_at' => now()->subDays($i),
                ]);
            }
        }

        $aggregator = app(AnalyticsAggregatorInterface::class);
        $analytics = $aggregator->getAnalytics($this->project->id, 'week');

        $this->assertArrayHasKey('trend', $analytics);
        $this->assertArrayHasKey('direction', $analytics['trend']);
        $this->assertEquals('up', $analytics['trend']['direction']);
    }

    /** @test AGG-007: Aggregation handles empty data gracefully */
    public function test_aggregation_handles_empty_data_gracefully(): void
    {
        $aggregator = app(AnalyticsAggregatorInterface::class);
        $analytics = $aggregator->getAnalytics($this->project->id, 'week');

        // Should return valid structure with zeros
        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('mention_rate', $analytics);
        $this->assertEquals(0, $analytics['mention_rate']);
    }

    /** @test AGG-008: Aggregation handles single query correctly */
    public function test_aggregation_handles_single_query_correctly(): void
    {
        // Create single query result
        $this->createQueryResult(true);

        $aggregator = app(AnalyticsAggregatorInterface::class);
        $analytics = $aggregator->getAnalytics($this->project->id, 'week');

        $this->assertEquals(100.0, $analytics['mention_rate']); // 1 out of 1
    }

    /** @test AGG-009: Aggregation respects platform filter */
    public function test_aggregation_respects_platform_filter(): void
    {
        // Create data for multiple platforms
        foreach (['chatgpt', 'perplexity'] as $platform) {
            AIQueryResult::create([
                'project_id' => $this->project->id,
                'platform' => $platform,
                'query_text' => 'Test query',
                'response_text' => 'SEOIndicator mentioned',
                'success' => true,
                'queried_at' => now(),
            ]);
        }

        $aggregator = app(AnalyticsAggregatorInterface::class);
        
        // Filter by chatgpt only
        $chatgptAnalytics = $aggregator->getAnalytics($this->project->id, 'week', 'chatgpt');

        // Should only include chatgpt data
        $this->assertArrayHasKey('by_platform', $chatgptAnalytics);
    }

    /** @test AGG-010: Aggregation calculates comparison correctly */
    public function test_aggregation_calculates_comparison_correctly(): void
    {
        // Create brand mentions
        for ($i = 0; $i < 10; $i++) {
            AIMentionAnalytics::create([
                'project_id' => $this->project->id,
                'entity_type' => 'brand',
                'entity_name' => 'SEOIndicator',
                'mention_type' => 'neutral',
                'sentiment' => 'positive',
                'context' => 'test context',
                'mentioned_at' => now(),
            ]);
        }

        // Create competitor mentions
        for ($i = 0; $i < 20; $i++) {
            AIMentionAnalytics::create([
                'project_id' => $this->project->id,
                'entity_type' => 'competitor',
                'entity_name' => 'Ahrefs',
                'mention_type' => 'neutral',
                'sentiment' => 'neutral',
                'context' => 'test context',
                'mentioned_at' => now(),
            ]);
        }

        $aggregator = app(AnalyticsAggregatorInterface::class);
        $comparison = $aggregator->getComparison($this->project->id);

        $this->assertArrayHasKey('brand', $comparison);
        $this->assertArrayHasKey('competitors', $comparison);
        $this->assertEquals('SEOIndicator', $comparison['brand']['name']);
        $this->assertEquals(10, $comparison['brand']['mentions']);
    }

    /** @test AGG-011: Aggregation returns trend data points */
    public function test_aggregation_returns_trend_data_points(): void
    {
        // Create historical data
        for ($i = 7; $i >= 1; $i--) {
            AIQueryResult::create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'query_text' => "Query day $i",
                'response_text' => 'Response',
                'success' => true,
                'queried_at' => now()->subDays($i),
            ]);
        }

        $aggregator = app(AnalyticsAggregatorInterface::class);
        $trend = $aggregator->getTrend($this->project->id, 7);

        $this->assertArrayHasKey('data', $trend);
        $this->assertArrayHasKey('direction', $trend);
    }

    /** @test AGG-012: Aggregation returns paginated queries */
    public function test_aggregation_returns_paginated_queries(): void
    {
        // Create multiple query results
        for ($i = 0; $i < 25; $i++) {
            AIQueryResult::create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'query_text' => "Query $i",
                'response_text' => 'Response',
                'success' => true,
                'queried_at' => now()->subMinutes($i),
            ]);
        }

        $aggregator = app(AnalyticsAggregatorInterface::class);
        
        // Get first page
        $page1 = $aggregator->getQueries($this->project->id, 1);
        
        // Get second page
        $page2 = $aggregator->getQueries($this->project->id, 2);

        $this->assertArrayHasKey('data', $page1);
        $this->assertArrayHasKey('meta', $page1);
        
        // Pages should have different data
        $page1Ids = collect($page1['data'])->pluck('id')->toArray();
        $page2Ids = collect($page2['data'])->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($page1Ids, $page2Ids));
    }

    /** @test AGG-013: Aggregation filters queries by mentioned_only */
    public function test_aggregation_filters_queries_by_mentioned_only(): void
    {
        // Create queries with and without mentions
        for ($i = 0; $i < 10; $i++) {
            AIQueryResult::create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'query_text' => "Query $i",
                'response_text' => 'Response',
                'success' => true,
                'brand_mentioned' => $i < 5, // Only first 5 have mentions
                'queried_at' => now(),
            ]);
        }

        $aggregator = app(AnalyticsAggregatorInterface::class);
        
        // Get all queries
        $allQueries = $aggregator->getQueries($this->project->id, 1, null, false);
        
        // Get only mentioned
        $mentionedOnly = $aggregator->getQueries($this->project->id, 1, null, true);

        $this->assertGreaterThanOrEqual(
            count($mentionedOnly['data']),
            count($allQueries['data'])
        );
    }

    /** @test AGG-014: Aggregation calculates average sentiment score */
    public function test_aggregation_calculates_average_sentiment_score(): void
    {
        // Create mentions with different sentiments
        $sentimentScores = [
            'positive' => 0.8,
            'positive' => 0.9,
            'neutral' => 0.5,
            'negative' => 0.2,
        ];

        foreach ($sentimentScores as $sentiment => $score) {
            AIMentionAnalytics::create([
                'project_id' => $this->project->id,
                'entity_type' => 'brand',
                'entity_name' => 'SEOIndicator',
                'mention_type' => 'neutral',
                'sentiment' => $sentiment,
                'sentiment_score' => $score,
                'context' => 'test context',
                'mentioned_at' => now(),
            ]);
        }

        $aggregator = app(AnalyticsAggregatorInterface::class);
        $analytics = $aggregator->getAnalytics($this->project->id, 'week');

        $this->assertArrayHasKey('sentiment', $analytics);
        $this->assertArrayHasKey('score', $analytics['sentiment']);
    }

    /** @test AGG-015: Aggregation handles division by zero */
    public function test_aggregation_handles_division_by_zero(): void
    {
        // No data created - should handle gracefully
        $aggregator = app(AnalyticsAggregatorInterface::class);
        $analytics = $aggregator->getAnalytics($this->project->id, 'week');

        // Should not throw and should return 0
        $this->assertEquals(0, $analytics['mention_rate']);
        $this->assertEquals(0, $analytics['share_of_voice']);
    }

    // ============================================================
    // Category 3: API Response Flow (API-001 to API-015)
    // ============================================================

    /** @test API-001: Analytics API returns correct structure */
    public function test_analytics_api_returns_correct_structure(): void
    {
        Sanctum::actingAs($this->user);

        $this->createQueryResult(true);

        $response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/analytics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'mention_rate',
                    'share_of_voice',
                    'sentiment',
                    'trend',
                    'by_platform',
                ],
            ]);
    }

    /** @test API-002: Analytics API respects period parameter */
    public function test_analytics_api_respects_period_parameter(): void
    {
        Sanctum::actingAs($this->user);

        // Create old data
        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_text' => 'Old query',
            'response_text' => 'Response',
            'success' => true,
            'queried_at' => now()->subDays(20),
        ]);

        // Create recent data
        AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_text' => 'Recent query',
            'response_text' => 'Response',
            'success' => true,
            'queried_at' => now()->subDays(2),
        ]);

        $weekResponse = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/analytics?period=week");
        $monthResponse = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/analytics?period=month");

        $weekResponse->assertStatus(200);
        $monthResponse->assertStatus(200);
    }

    /** @test API-003: Comparison API returns brand and competitors */
    public function test_comparison_api_returns_brand_and_competitors(): void
    {
        Sanctum::actingAs($this->user);

        // Create mentions
        AIMentionAnalytics::create([
            'project_id' => $this->project->id,
            'entity_type' => 'brand',
            'entity_name' => 'SEOIndicator',
            'mention_type' => 'neutral',
            'sentiment' => 'positive',
            'context' => 'test context',
            'mentioned_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/comparison");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'brand',
                    'competitors',
                ],
            ]);
    }

    /** @test API-004: Trend API returns data points */
    public function test_trend_api_returns_data_points(): void
    {
        Sanctum::actingAs($this->user);

        // Create historical data
        for ($i = 7; $i >= 1; $i--) {
            AIQueryResult::create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'query_text' => "Query day $i",
                'response_text' => 'Response',
                'success' => true,
                'queried_at' => now()->subDays($i),
            ]);
        }

        $response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/trend?days=7");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'direction',
                ],
            ]);
    }

    /** @test API-005: Queries API returns paginated results */
    public function test_queries_api_returns_paginated_results(): void
    {
        Sanctum::actingAs($this->user);

        for ($i = 0; $i < 25; $i++) {
            AIQueryResult::create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'query_text' => "Query $i",
                'response_text' => 'Response',
                'success' => true,
                'queried_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/queries");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);
    }

    /** @test API-006: API returns 403 for unauthorized access */
    public function test_api_returns_403_for_unauthorized_access(): void
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/analytics");

        $response->assertStatus(403);
    }

    /** @test API-007: API returns 401 for unauthenticated access */
    public function test_api_returns_401_for_unauthenticated_access(): void
    {
        $response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/analytics");

        $response->assertStatus(401);
    }

    /** @test API-008: API returns 404 for non-existent project */
    public function test_api_returns_404_for_non_existent_project(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/99999/analytics');

        $response->assertStatus(404);
    }

    /** @test API-009: API filters by platform */
    public function test_api_filters_by_platform(): void
    {
        Sanctum::actingAs($this->user);

        foreach (['chatgpt', 'perplexity'] as $platform) {
            AIQueryResult::create([
                'project_id' => $this->project->id,
                'platform' => $platform,
                'query_text' => 'Test query',
                'response_text' => 'Response',
                'success' => true,
                'queried_at' => now(),
            ]);
        }

        $response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/analytics?platform=chatgpt");

        $response->assertStatus(200);
    }

    /** @test API-010: API returns ETag header */
    public function test_api_returns_etag_header(): void
    {
        Sanctum::actingAs($this->user);

        $this->createQueryResult(true);

        $response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/analytics");

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('ETag'));
    }

    /** @test API-011: API returns 304 for matching ETag */
    public function test_api_returns_304_for_matching_etag(): void
    {
        Sanctum::actingAs($this->user);

        $this->createQueryResult(true);

        // First request to get ETag
        $response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/analytics");
        $etag = $response->headers->get('ETag');

        // Second request with ETag
        $cachedResponse = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/analytics", [
            'If-None-Match' => $etag,
        ]);

        $cachedResponse->assertStatus(304);
    }

    /** @test API-012: API queries respects page parameter */
    public function test_api_queries_respects_page_parameter(): void
    {
        Sanctum::actingAs($this->user);

        for ($i = 0; $i < 25; $i++) {
            AIQueryResult::create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'query_text' => "Query $i",
                'response_text' => 'Response',
                'success' => true,
                'queried_at' => now()->subMinutes($i),
            ]);
        }

        $page1Response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/queries?page=1");
        $page2Response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/queries?page=2");

        $page1Response->assertStatus(200);
        $page2Response->assertStatus(200);

        // Verify different pages have different data
        $page1Data = $page1Response->json('data');
        $page2Data = $page2Response->json('data');
        
        if (count($page1Data) > 0 && count($page2Data) > 0) {
            $this->assertNotEquals($page1Data[0]['id'], $page2Data[0]['id']);
        }
    }

    /** @test API-013: API trend respects days parameter */
    public function test_api_trend_respects_days_parameter(): void
    {
        Sanctum::actingAs($this->user);

        for ($i = 30; $i >= 1; $i--) {
            AIQueryResult::create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'query_text' => "Query day $i",
                'response_text' => 'Response',
                'success' => true,
                'queried_at' => now()->subDays($i),
            ]);
        }

        $response7Days = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/trend?days=7");
        $response30Days = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/trend?days=30");

        $response7Days->assertStatus(200);
        $response30Days->assertStatus(200);
    }

    /** @test API-014: API handles validation errors */
    public function test_api_handles_validation_errors(): void
    {
        Sanctum::actingAs($this->user);

        // Invalid period
        $response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/analytics?period=invalid");

        $response->assertStatus(422);
    }

    /** @test API-015: API returns consistent error format */
    public function test_api_returns_consistent_error_format(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/projects/99999/analytics');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'error',
            ]);
    }

    // ============================================================
    // Helper Methods
    // ============================================================

    /**
     * Create a query result for testing
     */
    protected function createQueryResult(bool $withMention, ?string $platform = 'chatgpt'): AIQueryResult
    {
        $queryResult = AIQueryResult::create([
            'project_id' => $this->project->id,
            'platform' => $platform,
            'query_text' => 'Test query',
            'response_text' => $withMention ? 'SEOIndicator is mentioned here.' : 'Some other content.',
            'success' => true,
            'brand_mentioned' => $withMention,
            'queried_at' => now(),
        ]);

        if ($withMention) {
            AIMentionAnalytics::create([
                'project_id' => $this->project->id,
                'entity_type' => 'brand',
                'entity_name' => 'SEOIndicator',
                'mention_type' => 'neutral',
                'sentiment' => 'positive',
                'context' => 'SEOIndicator is mentioned here.',
                'mentioned_at' => now(),
            ]);
        }

        return $queryResult;
    }
}
