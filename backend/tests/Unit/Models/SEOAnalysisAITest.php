<?php

namespace Tests\Unit\Models;

use App\Models\SEOAnalysis;
use App\Models\AIAnalysisRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Unit tests for SEOAnalysis model AI-related functionality.
 *
 * Tests for TASK-31-11: Update SEOAnalysis Model
 * @see TASK-31-11
 * @see SPEC-31
 */
class SEOAnalysisAITest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Create an SEOAnalysis with minimal required fields.
     */
    private function createAnalysis(array $attributes = []): SEOAnalysis
    {
        $defaults = [
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
        ];

        return SEOAnalysis::create(array_merge($defaults, $attributes));
    }

    // =========================================================================
    // CATEGORY 1: MODEL INSTANTIATION & NEW FILLABLES (MF-001 to MF-015)
    // =========================================================================

    /** @test MF-001: Model can be instantiated with AI fields */
    public function test_mf001_model_can_be_instantiated_with_ai_fields(): void
    {
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 75,
            'status' => 'completed',
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
        ]);

        $this->assertInstanceOf(SEOAnalysis::class, $analysis);
        $this->assertEquals('google/gemini-2.0-flash-exp:free', $analysis->ai_model);
        $this->assertEquals(1500, $analysis->prompt_tokens);
        $this->assertEquals(500, $analysis->response_tokens);
        $this->assertEquals(2000, $analysis->total_tokens);
    }

    /** @test MF-002: ai_model is fillable */
    public function test_mf002_ai_model_is_fillable(): void
    {
        $analysis = $this->createAnalysis([
            'ai_model' => 'meta-llama/llama-3-8b-instruct:free',
        ]);

        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $analysis->ai_model);
    }

    /** @test MF-003: prompt_tokens is fillable */
    public function test_mf003_prompt_tokens_is_fillable(): void
    {
        $analysis = $this->createAnalysis([
            'prompt_tokens' => 2500,
        ]);

        $this->assertEquals(2500, $analysis->prompt_tokens);
    }

    /** @test MF-004: response_tokens is fillable */
    public function test_mf004_response_tokens_is_fillable(): void
    {
        $analysis = $this->createAnalysis([
            'response_tokens' => 800,
        ]);

        $this->assertEquals(800, $analysis->response_tokens);
    }

    /** @test MF-005: total_tokens is fillable */
    public function test_mf005_total_tokens_is_fillable(): void
    {
        $analysis = $this->createAnalysis([
            'total_tokens' => 3300,
        ]);

        $this->assertEquals(3300, $analysis->total_tokens);
    }

    /** @test MF-006: truncation_applied is fillable */
    public function test_mf006_truncation_applied_is_fillable(): void
    {
        $truncationData = ['primary' => 'summarize', 'fallback' => 'truncate'];

        $analysis = $this->createAnalysis([
            'truncation_applied' => $truncationData,
        ]);

        $this->assertEquals($truncationData, $analysis->truncation_applied);
    }

    /** @test MF-007: overall_summary is fillable */
    public function test_mf007_overall_summary_is_fillable(): void
    {
        $summary = 'This page has good SEO fundamentals but needs improvement in meta tags.';

        $analysis = $this->createAnalysis([
            'overall_summary' => $summary,
        ]);

        $this->assertEquals($summary, $analysis->overall_summary);
    }

    /** @test MF-008: category_scores is fillable */
    public function test_mf008_category_scores_is_fillable(): void
    {
        $scores = [
            'technical' => 85,
            'content' => 72,
            'link_profile' => 68,
            'user_experience' => 90,
        ];

        $analysis = $this->createAnalysis([
            'category_scores' => $scores,
        ]);

        $this->assertEquals($scores, $analysis->category_scores);
    }

    /** @test MF-009: ai_request_id is fillable */
    public function test_mf009_ai_request_id_is_fillable(): void
    {
        $analysis = $this->createAnalysis([
            'ai_request_id' => 1,
        ]);

        $this->assertEquals(1, $analysis->ai_request_id);
    }

    /** @test MF-010: All AI fields can be filled together */
    public function test_mf010_all_ai_fields_fillable_together(): void
    {
        $analysis = $this->createAnalysis([
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
            'truncation_applied' => ['primary' => 'summarize'],
            'overall_summary' => 'Good SEO overall.',
            'category_scores' => ['technical' => 85, 'content' => 72],
            'ai_request_id' => null,
        ]);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $analysis->ai_model);
        $this->assertEquals(1500, $analysis->prompt_tokens);
        $this->assertEquals(500, $analysis->response_tokens);
        $this->assertEquals(2000, $analysis->total_tokens);
        $this->assertEquals(['primary' => 'summarize'], $analysis->truncation_applied);
        $this->assertEquals('Good SEO overall.', $analysis->overall_summary);
        $this->assertEquals(['technical' => 85, 'content' => 72], $analysis->category_scores);
        $this->assertNull($analysis->ai_request_id);
    }

    /** @test MF-011: Model can be created without AI fields (backward compatibility) */
    public function test_mf011_backward_compatibility_without_ai_fields(): void
    {
        $analysis = $this->createAnalysis([
            'overall_score' => 75,
            'status' => 'completed',
        ]);

        $this->assertNull($analysis->ai_model);
        $this->assertNull($analysis->prompt_tokens);
        $this->assertNull($analysis->response_tokens);
        $this->assertNull($analysis->total_tokens);
        $this->assertNull($analysis->truncation_applied);
        $this->assertNull($analysis->overall_summary);
        $this->assertNull($analysis->category_scores);
        $this->assertNull($analysis->ai_request_id);
    }

    /** @test MF-012: Existing fields remain fillable */
    public function test_mf012_existing_fields_remain_fillable(): void
    {
        $analysis = $this->createAnalysis([
            'user_id' => 1,
            'session_id' => 'test-session',
            'overall_score' => 85,
            'status' => 'completed',
            'error_message' => null,
            'analysis_data' => ['test' => 'data'],
        ]);

        $this->assertEquals(1, $analysis->user_id);
        $this->assertEquals('test-session', $analysis->session_id);
        $this->assertEquals('example.com', $analysis->domain);
        $this->assertEquals(85, $analysis->overall_score);
        $this->assertEquals(['test' => 'data'], $analysis->analysis_data);
    }

    /** @test MF-013: AI fields are nullable */
    public function test_mf013_ai_fields_are_nullable(): void
    {
        $analysis = $this->createAnalysis();

        $this->assertNull($analysis->ai_model);
        $this->assertNull($analysis->prompt_tokens);
        $this->assertNull($analysis->response_tokens);
        $this->assertNull($analysis->total_tokens);
        $this->assertNull($analysis->truncation_applied);
        $this->assertNull($analysis->overall_summary);
        $this->assertNull($analysis->category_scores);
        $this->assertNull($analysis->ai_request_id);
    }

    /** @test MF-014: Partial AI fields can be set */
    public function test_mf014_partial_ai_fields_can_be_set(): void
    {
        $analysis = $this->createAnalysis([
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $analysis->ai_model);
        $this->assertNull($analysis->prompt_tokens);
        $this->assertNull($analysis->total_tokens);
    }

    /** @test MF-015: Model update with AI fields */
    public function test_mf015_model_update_with_ai_fields(): void
    {
        $analysis = $this->createAnalysis();

        $analysis->update([
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => 1000,
            'response_tokens' => 300,
            'total_tokens' => 1300,
        ]);

        $analysis->refresh();

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $analysis->ai_model);
        $this->assertEquals(1000, $analysis->prompt_tokens);
        $this->assertEquals(300, $analysis->response_tokens);
        $this->assertEquals(1300, $analysis->total_tokens);
    }

    // =========================================================================
    // CATEGORY 2: CASTS FOR AI FIELDS (CA-001 to CA-015)
    // =========================================================================

    /** @test CA-001: prompt_tokens is cast to integer */
    public function test_ca001_prompt_tokens_is_cast_to_integer(): void
    {
        $analysis = $this->createAnalysis([
            'prompt_tokens' => 1500,
        ]);

        $this->assertIsInt($analysis->prompt_tokens);
    }

    /** @test CA-002: response_tokens is cast to integer */
    public function test_ca002_response_tokens_is_cast_to_integer(): void
    {
        $analysis = $this->createAnalysis([
            'response_tokens' => 500,
        ]);

        $this->assertIsInt($analysis->response_tokens);
    }

    /** @test CA-003: total_tokens is cast to integer */
    public function test_ca003_total_tokens_is_cast_to_integer(): void
    {
        $analysis = $this->createAnalysis([
            'total_tokens' => 2000,
        ]);

        $this->assertIsInt($analysis->total_tokens);
    }

    /** @test CA-004: truncation_applied is cast to array */
    public function test_ca004_truncation_applied_is_cast_to_array(): void
    {
        $analysis = $this->createAnalysis([
            'truncation_applied' => ['primary' => 'summarize'],
        ]);

        $this->assertIsArray($analysis->truncation_applied);
        $this->assertEquals(['primary' => 'summarize'], $analysis->truncation_applied);
    }

    /** @test CA-005: category_scores is cast to array */
    public function test_ca005_category_scores_is_cast_to_array(): void
    {
        $analysis = $this->createAnalysis([
            'category_scores' => ['technical' => 85, 'content' => 72],
        ]);

        $this->assertIsArray($analysis->category_scores);
        $this->assertEquals(['technical' => 85, 'content' => 72], $analysis->category_scores);
    }

    /** @test CA-006: Null truncation_applied returns null not array */
    public function test_ca006_null_truncation_applied_returns_null(): void
    {
        $analysis = $this->createAnalysis();

        $this->assertNull($analysis->truncation_applied);
    }

    /** @test CA-007: Null category_scores returns null not array */
    public function test_ca007_null_category_scores_returns_null(): void
    {
        $analysis = $this->createAnalysis();

        $this->assertNull($analysis->category_scores);
    }

    /** @test CA-008: Nested JSON in truncation_applied */
    public function test_ca008_nested_json_in_truncation_applied(): void
    {
        $nested = [
            'primary' => 'summarize',
            'sections' => [
                'affected' => ['footer', 'sidebar'],
                'preserved' => ['header', 'main'],
            ],
            'threshold' => 4000,
        ];

        $analysis = $this->createAnalysis([
            'truncation_applied' => $nested,
        ]);

        $this->assertEquals($nested, $analysis->truncation_applied);
        $this->assertEquals(['footer', 'sidebar'], $analysis->truncation_applied['sections']['affected']);
    }

    /** @test CA-009: Nested JSON in category_scores */
    public function test_ca009_nested_json_in_category_scores(): void
    {
        $scores = [
            'main' => [
                'technical' => 85,
                'content' => 72,
            ],
            'sub' => [
                'images' => 90,
                'links' => 65,
            ],
        ];

        $analysis = $this->createAnalysis([
            'category_scores' => $scores,
        ]);

        $this->assertEquals($scores, $analysis->category_scores);
        $this->assertEquals(85, $analysis->category_scores['main']['technical']);
    }

    /** @test CA-010: Empty array for truncation_applied */
    public function test_ca010_empty_array_for_truncation_applied(): void
    {
        $analysis = $this->createAnalysis([
            'truncation_applied' => [],
        ]);

        $this->assertIsArray($analysis->truncation_applied);
        $this->assertEmpty($analysis->truncation_applied);
    }

    /** @test CA-011: Empty array for category_scores */
    public function test_ca011_empty_array_for_category_scores(): void
    {
        $analysis = $this->createAnalysis([
            'category_scores' => [],
        ]);

        $this->assertIsArray($analysis->category_scores);
        $this->assertEmpty($analysis->category_scores);
    }

    /** @test CA-012: Integer preservation after refresh */
    public function test_ca012_integer_preservation_after_refresh(): void
    {
        $analysis = $this->createAnalysis([
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
        ]);

        $analysis->refresh();

        $this->assertEquals(1500, $analysis->prompt_tokens);
        $this->assertEquals(500, $analysis->response_tokens);
        $this->assertEquals(2000, $analysis->total_tokens);
    }

    /** @test CA-013: JSON preservation after refresh */
    public function test_ca013_json_preservation_after_refresh(): void
    {
        $scores = ['technical' => 85, 'content' => 72];
        $truncation = ['primary' => 'summarize'];

        $analysis = $this->createAnalysis([
            'category_scores' => $scores,
            'truncation_applied' => $truncation,
        ]);

        $analysis->refresh();

        $this->assertEquals($scores, $analysis->category_scores);
        $this->assertEquals($truncation, $analysis->truncation_applied);
    }

    /** @test CA-014: Zero tokens stored correctly */
    public function test_ca014_zero_tokens_stored_correctly(): void
    {
        $analysis = $this->createAnalysis([
            'prompt_tokens' => 0,
            'response_tokens' => 0,
            'total_tokens' => 0,
        ]);

        $this->assertEquals(0, $analysis->prompt_tokens);
        $this->assertEquals(0, $analysis->response_tokens);
        $this->assertEquals(0, $analysis->total_tokens);
    }

    /** @test CA-015: Large token values stored correctly */
    public function test_ca015_large_token_values_stored_correctly(): void
    {
        $analysis = $this->createAnalysis([
            'prompt_tokens' => 100000,
            'response_tokens' => 50000,
            'total_tokens' => 150000,
        ]);

        $this->assertEquals(100000, $analysis->prompt_tokens);
        $this->assertEquals(50000, $analysis->response_tokens);
        $this->assertEquals(150000, $analysis->total_tokens);
    }

    // =========================================================================
    // CATEGORY 3: RELATIONSHIP TO AIAnalysisRequest (RE-001 to RE-010)
    // =========================================================================

    /** @test RE-001: aiRequest relationship exists */
    public function test_re001_ai_request_relationship_exists(): void
    {
        $analysis = new SEOAnalysis();
        $this->assertTrue(method_exists($analysis, 'aiRequest'));
    }

    /** @test RE-002: aiRequest returns HasOne relationship */
    public function test_re002_ai_request_returns_has_one_relationship(): void
    {
        $analysis = $this->createAnalysis();

        $relation = $analysis->aiRequest();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
    }

    /** @test RE-003: aiRequest relationship returns related model */
    public function test_re003_ai_request_relationship_returns_related_model(): void
    {
        $analysis = $this->createAnalysis();

        $aiRequest = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);

        $this->assertEquals($aiRequest->id, $analysis->aiRequest->id);
        $this->assertEquals($aiRequest->model, $analysis->aiRequest->model);
    }

    /** @test RE-004: aiRequest returns null when not exists */
    public function test_re004_ai_request_returns_null_when_not_exists(): void
    {
        $analysis = $this->createAnalysis();

        $this->assertNull($analysis->aiRequest);
    }

    /** @test RE-005: aiRequest uses correct foreign key */
    public function test_re005_ai_request_uses_correct_foreign_key(): void
    {
        $analysis = $this->createAnalysis();

        $aiRequest = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);

        // Verify the relationship query uses analysis_id
        $related = $analysis->aiRequest()->where('analysis_id', $analysis->id)->first();
        $this->assertNotNull($related);
    }

    /** @test RE-006: Creating aiRequest through relationship */
    public function test_re006_creating_ai_request_through_relationship(): void
    {
        $analysis = $this->createAnalysis();

        $aiRequest = $analysis->aiRequest()->create([
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'pending',
        ]);

        $this->assertNotNull($aiRequest);
        $this->assertEquals($analysis->id, $aiRequest->analysis_id);
    }

    /** @test RE-007: Deleting analysis cascades to aiRequest */
    public function test_re007_deleting_analysis_cascades_to_ai_request(): void
    {
        $analysis = $this->createAnalysis();

        $aiRequest = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);

        $aiRequestId = $aiRequest->id;
        $analysis->delete();

        $this->assertDatabaseMissing('ai_analysis_requests', ['id' => $aiRequestId]);
    }

    /** @test RE-008: Eager loading aiRequest */
    public function test_re008_eager_loading_ai_request(): void
    {
        $analysis = $this->createAnalysis();

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);

        $loaded = SEOAnalysis::with('aiRequest')->find($analysis->id);

        $this->assertTrue($loaded->relationLoaded('aiRequest'));
        $this->assertNotNull($loaded->aiRequest);
    }

    /** @test RE-009: aiRequest with multiple queries */
    public function test_re009_ai_request_with_multiple_queries(): void
    {
        $analysis1 = $this->createAnalysis(['domain' => 'example1.com', 'normalized_url' => 'https://example1.com/']);
        $analysis2 = $this->createAnalysis(['domain' => 'example2.com', 'normalized_url' => 'https://example2.com/']);

        AIAnalysisRequest::create([
            'analysis_id' => $analysis1->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis2->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
            'status' => 'completed',
        ]);

        $analyses = SEOAnalysis::with('aiRequest')->get();

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $analyses[0]->aiRequest->model);
        $this->assertEquals('meta-llama/llama-3-8b-instruct:free', $analyses[1]->aiRequest->model);
    }

    /** @test RE-010: aiRequest relationship query constraints */
    public function test_re010_ai_request_relationship_query_constraints(): void
    {
        $analysis = $this->createAnalysis();

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
            'latency_ms' => 1500,
        ]);

        // Query with constraint
        $slowRequest = $analysis->aiRequest()->where('latency_ms', '>', 1000)->first();
        $this->assertNotNull($slowRequest);

        $fastRequest = $analysis->aiRequest()->where('latency_ms', '<', 1000)->first();
        $this->assertNull($fastRequest);
    }

    // =========================================================================
    // CATEGORY 4: ACCESSOR METHODS (AC-001 to AC-025)
    // =========================================================================

    /** @test AC-001: wasUnifiedAnalysis method exists */
    public function test_ac001_was_unified_analysis_method_exists(): void
    {
        $analysis = new SEOAnalysis();
        $this->assertTrue(method_exists($analysis, 'wasUnifiedAnalysis'));
    }

    /** @test AC-002: wasUnifiedAnalysis returns true when ai_model set */
    public function test_ac002_was_unified_analysis_returns_true_when_ai_model_set(): void
    {
        $analysis = $this->createAnalysis([
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $this->assertTrue($analysis->wasUnifiedAnalysis());
    }

    /** @test AC-003: wasUnifiedAnalysis returns false when ai_model null */
    public function test_ac003_was_unified_analysis_returns_false_when_ai_model_null(): void
    {
        $analysis = $this->createAnalysis();

        $this->assertFalse($analysis->wasUnifiedAnalysis());
    }

    /** @test AC-004: getCategoryScore method exists */
    public function test_ac004_get_category_score_method_exists(): void
    {
        $analysis = new SEOAnalysis();
        $this->assertTrue(method_exists($analysis, 'getCategoryScore'));
    }

    /** @test AC-005: getCategoryScore returns score for existing category */
    public function test_ac005_get_category_score_returns_score_for_existing_category(): void
    {
        $analysis = $this->createAnalysis([
            'category_scores' => ['technical' => 85, 'content' => 72],
        ]);

        $this->assertEquals(85, $analysis->getCategoryScore('technical'));
        $this->assertEquals(72, $analysis->getCategoryScore('content'));
    }

    /** @test AC-006: getCategoryScore returns null for missing category */
    public function test_ac006_get_category_score_returns_null_for_missing_category(): void
    {
        $analysis = $this->createAnalysis([
            'category_scores' => ['technical' => 85],
        ]);

        $this->assertNull($analysis->getCategoryScore('nonexistent'));
    }

    /** @test AC-007: getCategoryScore returns null when category_scores null */
    public function test_ac007_get_category_score_returns_null_when_category_scores_null(): void
    {
        $analysis = $this->createAnalysis();

        $this->assertNull($analysis->getCategoryScore('technical'));
    }

    /** @test AC-008: getTruncationStrategies method exists */
    public function test_ac008_get_truncation_strategies_method_exists(): void
    {
        $analysis = new SEOAnalysis();
        $this->assertTrue(method_exists($analysis, 'getTruncationStrategies'));
    }

    /** @test AC-009: getTruncationStrategies returns array when set */
    public function test_ac009_get_truncation_strategies_returns_array_when_set(): void
    {
        $strategies = ['primary' => 'summarize', 'fallback' => 'truncate'];

        $analysis = $this->createAnalysis([
            'truncation_applied' => $strategies,
        ]);

        $this->assertEquals($strategies, $analysis->getTruncationStrategies());
    }

    /** @test AC-010: getTruncationStrategies returns empty array when null */
    public function test_ac010_get_truncation_strategies_returns_empty_array_when_null(): void
    {
        $analysis = $this->createAnalysis();

        $this->assertEquals([], $analysis->getTruncationStrategies());
    }

    /** @test AC-011: wasTruncated method exists */
    public function test_ac011_was_truncated_method_exists(): void
    {
        $analysis = new SEOAnalysis();
        $this->assertTrue(method_exists($analysis, 'wasTruncated'));
    }

    /** @test AC-012: wasTruncated returns true when truncation_applied not empty */
    public function test_ac012_was_truncated_returns_true_when_truncation_applied_not_empty(): void
    {
        $analysis = $this->createAnalysis([
            'truncation_applied' => ['primary' => 'summarize'],
        ]);

        $this->assertTrue($analysis->wasTruncated());
    }

    /** @test AC-013: wasTruncated returns false when truncation_applied empty */
    public function test_ac013_was_truncated_returns_false_when_truncation_applied_empty(): void
    {
        $analysis = $this->createAnalysis([
            'truncation_applied' => [],
        ]);

        $this->assertFalse($analysis->wasTruncated());
    }

    /** @test AC-014: wasTruncated returns false when truncation_applied null */
    public function test_ac014_was_truncated_returns_false_when_truncation_applied_null(): void
    {
        $analysis = $this->createAnalysis();

        $this->assertFalse($analysis->wasTruncated());
    }

    /** @test AC-015: getTokenUsage method exists */
    public function test_ac015_get_token_usage_method_exists(): void
    {
        $analysis = new SEOAnalysis();
        $this->assertTrue(method_exists($analysis, 'getTokenUsage'));
    }

    /** @test AC-016: getTokenUsage returns array with all token fields */
    public function test_ac016_get_token_usage_returns_array_with_all_token_fields(): void
    {
        $analysis = $this->createAnalysis([
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
        ]);

        $usage = $analysis->getTokenUsage();

        $this->assertIsArray($usage);
        $this->assertArrayHasKey('prompt', $usage);
        $this->assertArrayHasKey('response', $usage);
        $this->assertArrayHasKey('total', $usage);
    }

    /** @test AC-017: getTokenUsage returns correct values */
    public function test_ac017_get_token_usage_returns_correct_values(): void
    {
        $analysis = $this->createAnalysis([
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
        ]);

        $usage = $analysis->getTokenUsage();

        $this->assertEquals(1500, $usage['prompt']);
        $this->assertEquals(500, $usage['response']);
        $this->assertEquals(2000, $usage['total']);
    }

    /** @test AC-018: getTokenUsage handles null values */
    public function test_ac018_get_token_usage_handles_null_values(): void
    {
        $analysis = $this->createAnalysis();

        $usage = $analysis->getTokenUsage();

        $this->assertNull($usage['prompt']);
        $this->assertNull($usage['response']);
        $this->assertNull($usage['total']);
    }

    /** @test AC-019: getAiModelDisplayName method exists */
    public function test_ac019_get_ai_model_display_name_method_exists(): void
    {
        $analysis = new SEOAnalysis();
        $this->assertTrue(method_exists($analysis, 'getAiModelDisplayName'));
    }

    /** @test AC-020: getAiModelDisplayName returns display name for Gemini */
    public function test_ac020_get_ai_model_display_name_returns_display_name_for_gemini(): void
    {
        $analysis = $this->createAnalysis([
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $this->assertEquals('Gemini 2.0 Flash', $analysis->getAiModelDisplayName());
    }

    /** @test AC-021: getAiModelDisplayName returns display name for Llama */
    public function test_ac021_get_ai_model_display_name_returns_display_name_for_llama(): void
    {
        $analysis = $this->createAnalysis([
            'ai_model' => 'meta-llama/llama-3-8b-instruct:free',
        ]);

        $this->assertEquals('Llama 3 8B', $analysis->getAiModelDisplayName());
    }

    /** @test AC-022: getAiModelDisplayName returns display name for Mistral */
    public function test_ac022_get_ai_model_display_name_returns_display_name_for_mistral(): void
    {
        $analysis = $this->createAnalysis([
            'ai_model' => 'mistralai/mistral-7b-instruct:free',
        ]);

        $this->assertEquals('Mistral 7B', $analysis->getAiModelDisplayName());
    }

    /** @test AC-023: getAiModelDisplayName returns display name for Qwen */
    public function test_ac023_get_ai_model_display_name_returns_display_name_for_qwen(): void
    {
        $analysis = $this->createAnalysis([
            'ai_model' => 'qwen/qwen-2-7b-instruct:free',
        ]);

        $this->assertEquals('Qwen 2 7B', $analysis->getAiModelDisplayName());
    }

    /** @test AC-024: getAiModelDisplayName returns raw model name for unknown */
    public function test_ac024_get_ai_model_display_name_returns_raw_model_name_for_unknown(): void
    {
        $analysis = $this->createAnalysis([
            'ai_model' => 'unknown/model-name',
        ]);

        $this->assertEquals('unknown/model-name', $analysis->getAiModelDisplayName());
    }

    /** @test AC-025: getAiModelDisplayName returns Unknown when ai_model null */
    public function test_ac025_get_ai_model_display_name_returns_unknown_when_ai_model_null(): void
    {
        $analysis = $this->createAnalysis();

        $this->assertEquals('Unknown', $analysis->getAiModelDisplayName());
    }

    // =========================================================================
    // CATEGORY 5: SCOPES AND QUERYING (SQ-001 to SQ-010)
    // =========================================================================

    /** @test SQ-001: Scope for analyses with AI model */
    public function test_sq001_scope_for_analyses_with_ai_model(): void
    {
        SEOAnalysis::create([
            'domain' => 'with-ai.com',
            'normalized_url' => 'https://with-ai.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
        ]);
        SEOAnalysis::create([
            'domain' => 'without-ai.com',
            'normalized_url' => 'https://without-ai.com/',
            'overall_score' => 0,
            'analysis_data' => [],
        ]);

        $withAi = SEOAnalysis::whereNotNull('ai_model')->get();

        $this->assertCount(1, $withAi);
        $this->assertEquals('with-ai.com', $withAi->first()->domain);
    }

    /** @test SQ-002: Scope for analyses without AI model */
    public function test_sq002_scope_for_analyses_without_ai_model(): void
    {
        SEOAnalysis::create([
            'domain' => 'with-ai.com',
            'normalized_url' => 'https://with-ai.com/',
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
        ]);
        SEOAnalysis::create([
            'domain' => 'without-ai.com',
            'normalized_url' => 'https://without-ai.com/',
        ]);

        $withoutAi = SEOAnalysis::whereNull('ai_model')->get();

        $this->assertCount(1, $withoutAi);
        $this->assertEquals('without-ai.com', $withoutAi->first()->domain);
    }

    /** @test SQ-003: Query by specific AI model */
    public function test_sq003_query_by_specific_ai_model(): void
    {
        SEOAnalysis::create([
            'domain' => 'gemini.com',
            'normalized_url' => 'https://gemini.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
        ]);
        SEOAnalysis::create([
            'domain' => 'llama.com',
            'normalized_url' => 'https://llama.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => 'meta-llama/llama-3-8b-instruct:free',
        ]);

        $gemini = SEOAnalysis::where('ai_model', 'google/gemini-2.0-flash-exp:free')->get();

        $this->assertCount(1, $gemini);
        $this->assertEquals('gemini.com', $gemini->first()->domain);
    }

    /** @test SQ-004: Query by token threshold */
    public function test_sq004_query_by_token_threshold(): void
    {
        SEOAnalysis::create([
            'domain' => 'low-tokens.com',
            'normalized_url' => 'https://low-tokens.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'total_tokens' => 500,
        ]);
        SEOAnalysis::create([
            'domain' => 'high-tokens.com',
            'normalized_url' => 'https://high-tokens.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'total_tokens' => 5000,
        ]);

        $highTokens = SEOAnalysis::where('total_tokens', '>', 1000)->get();

        $this->assertCount(1, $highTokens);
        $this->assertEquals('high-tokens.com', $highTokens->first()->domain);
    }

    /** @test SQ-005: Query with aiRequest relationship loaded */
    public function test_sq005_query_with_ai_request_relationship_loaded(): void
    {
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);

        $results = SEOAnalysis::with('aiRequest')->where('id', $analysis->id)->get();

        $this->assertTrue($results->first()->relationLoaded('aiRequest'));
    }

    /** @test SQ-006: Query truncated analyses */
    public function test_sq006_query_truncated_analyses(): void
    {
        SEOAnalysis::create([
            'domain' => 'truncated.com',
            'normalized_url' => 'https://truncated.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'truncation_applied' => ['primary' => 'summarize'],
        ]);
        SEOAnalysis::create([
            'domain' => 'not-truncated.com',
            'normalized_url' => 'https://not-truncated.com/',
            'overall_score' => 0,
            'analysis_data' => [],
        ]);

        $truncated = SEOAnalysis::whereNotNull('truncation_applied')->get();

        $this->assertCount(1, $truncated);
        $this->assertEquals('truncated.com', $truncated->first()->domain);
    }

    /** @test SQ-007: Query with category_scores filter */
    public function test_sq007_query_with_category_scores_filter(): void
    {
        SEOAnalysis::create([
            'domain' => 'good-technical.com',
            'normalized_url' => 'https://good-technical.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'category_scores' => ['technical' => 90, 'content' => 60],
        ]);
        SEOAnalysis::create([
            'domain' => 'poor-technical.com',
            'normalized_url' => 'https://poor-technical.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'category_scores' => ['technical' => 40, 'content' => 90],
        ]);

        // Note: JSON queries vary by database
        $all = SEOAnalysis::whereNotNull('category_scores')->get();
        $this->assertCount(2, $all);
    }

    /** @test SQ-008: Query with multiple AI fields */
    public function test_sq008_query_with_multiple_ai_fields(): void
    {
        SEOAnalysis::create([
            'domain' => 'complete.com',
            'normalized_url' => 'https://complete.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
            'truncation_applied' => ['primary' => 'summarize'],
            'category_scores' => ['technical' => 85],
        ]);

        $complete = SEOAnalysis::where('domain', 'complete.com')
            ->whereNotNull('ai_model')
            ->whereNotNull('total_tokens')
            ->first();

        $this->assertNotNull($complete);
        $this->assertEquals('google/gemini-2.0-flash-exp:free', $complete->ai_model);
    }

    /** @test SQ-009: Order by token usage */
    public function test_sq009_order_by_token_usage(): void
    {
        SEOAnalysis::create([
            'domain' => 'low.com',
            'normalized_url' => 'https://low.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'total_tokens' => 100,
        ]);
        SEOAnalysis::create([
            'domain' => 'high.com',
            'normalized_url' => 'https://high.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'total_tokens' => 10000,
        ]);

        $ordered = SEOAnalysis::orderBy('total_tokens', 'desc')->get();

        $this->assertEquals('high.com', $ordered->first()->domain);
        $this->assertEquals('low.com', $ordered->last()->domain);
    }

    /** @test SQ-010: Count by AI model */
    public function test_sq010_count_by_ai_model(): void
    {
        SEOAnalysis::create([
            'domain' => 'gemini1.com',
            'normalized_url' => 'https://gemini1.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
        ]);
        SEOAnalysis::create([
            'domain' => 'gemini2.com',
            'normalized_url' => 'https://gemini2.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
        ]);
        SEOAnalysis::create([
            'domain' => 'llama.com',
            'normalized_url' => 'https://llama.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => 'meta-llama/llama-3-8b-instruct:free',
        ]);

        $counts = SEOAnalysis::whereNotNull('ai_model')
            ->selectRaw('ai_model, COUNT(*) as count')
            ->groupBy('ai_model')
            ->get();

        $this->assertCount(2, $counts);
    }

    // =========================================================================
    // CATEGORY 6: EDGE CASES & SECURITY (ES-001 to ES-015)
    // =========================================================================

    /** @test ES-001: Very long model name */
    public function test_es001_very_long_model_name(): void
    {
        $longName = str_repeat('a', 100);

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => $longName,
        ]);

        $this->assertEquals($longName, $analysis->ai_model);
    }

    /** @test ES-002: Unicode in overall_summary */
    public function test_es002_unicode_in_overall_summary(): void
    {
        $summary = '分析显示页面有良好的SEO基础 🎉 Значительно улучшен';

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'overall_summary' => $summary,
        ]);

        $this->assertEquals($summary, $analysis->overall_summary);
    }

    /** @test ES-003: Unicode in JSON fields */
    public function test_es003_unicode_in_json_fields(): void
    {
        $scores = ['技术' => 85, 'content' => 72, 'emoji' => '🎯'];
        $truncation = ['策略' => 'summarize', 'note' => '日本語'];

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'category_scores' => $scores,
            'truncation_applied' => $truncation,
        ]);

        $this->assertEquals($scores, $analysis->category_scores);
        $this->assertEquals($truncation, $analysis->truncation_applied);
    }

    /** @test ES-004: XSS prevention in overall_summary */
    public function test_es004_xss_prevention_in_overall_summary(): void
    {
        $malicious = '<script>alert("xss")</script>Good summary';

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'overall_summary' => $malicious,
        ]);

        // The value should be stored as-is (no automatic HTML escaping at model level)
        // Frontend should escape when rendering
        $this->assertEquals($malicious, $analysis->overall_summary);
    }

    /** @test ES-005: XSS prevention in JSON fields */
    public function test_es005_xss_prevention_in_json_fields(): void
    {
        $malicious = ['script' => '<script>alert("xss")</script>'];

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'category_scores' => $malicious,
        ]);

        // JSON encoding handles the escaping
        $this->assertEquals($malicious, $analysis->category_scores);
    }

    /** @test ES-006: SQL injection in ai_model */
    public function test_es006_sql_injection_in_ai_model(): void
    {
        $malicious = "'; DROP TABLE seo_analyses; --";

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => $malicious,
        ]);

        // Should be stored as literal string
        $this->assertEquals($malicious, $analysis->ai_model);

        // Table should still exist
        $this->assertDatabaseHas('seo_analyses', ['id' => $analysis->id]);
    }

    /** @test ES-007: Negative tokens (app-level validation) */
    public function test_es007_negative_tokens(): void
    {
        // Database may accept negative, app should validate
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'prompt_tokens' => -100,
        ]);

        // Stored as-is (negative accepted at DB level)
        $this->assertEquals(-100, $analysis->prompt_tokens);
    }

    /** @test ES-008: Empty string for ai_model */
    public function test_es008_empty_string_for_ai_model(): void
    {
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => '',
        ]);

        // Empty string is different from null
        $this->assertEquals('', $analysis->ai_model);
        $this->assertFalse($analysis->wasUnifiedAnalysis());
    }

    /** @test ES-009: Very large JSON in category_scores */
    public function test_es009_very_large_json_in_category_scores(): void
    {
        $largeScores = [];
        for ($i = 0; $i < 100; $i++) {
            $largeScores["category_$i"] = rand(0, 100);
        }

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'category_scores' => $largeScores,
        ]);

        $this->assertCount(100, $analysis->category_scores);
    }

    /** @test ES-010: Deeply nested JSON */
    public function test_es010_deeply_nested_json(): void
    {
        $nested = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'value' => 'deep',
                        ],
                    ],
                ],
            ],
        ];

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'truncation_applied' => $nested,
        ]);

        $this->assertEquals('deep', $analysis->truncation_applied['level1']['level2']['level3']['level4']['value']);
    }

    /** @test ES-011: Null vs empty array in JSON fields */
    public function test_es011_null_vs_empty_array_in_json_fields(): void
    {
        $nullAnalysis = SEOAnalysis::create([
            'domain' => 'null.com',
            'normalized_url' => 'https://null.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'category_scores' => null,
        ]);

        $emptyAnalysis = SEOAnalysis::create([
            'domain' => 'empty.com',
            'normalized_url' => 'https://empty.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'category_scores' => [],
        ]);

        $this->assertNull($nullAnalysis->category_scores);
        $this->assertIsArray($emptyAnalysis->category_scores);
        $this->assertEmpty($emptyAnalysis->category_scores);
    }

    /** @test ES-012: Special characters in overall_summary */
    public function test_es012_special_characters_in_overall_summary(): void
    {
        $special = "Line1\nLine2\tTabbed\r\nWindows\"Quote'Quote\\Backslash";

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'overall_summary' => $special,
        ]);

        $this->assertEquals($special, $analysis->overall_summary);
    }

    /** @test ES-013: Boolean values in JSON fields */
    public function test_es013_boolean_values_in_json_fields(): void
    {
        $data = ['flag' => true, 'no_flag' => false, 'number' => 1];

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'category_scores' => $data,
        ]);

        $this->assertTrue($analysis->category_scores['flag']);
        $this->assertFalse($analysis->category_scores['no_flag']);
    }

    /** @test ES-014: Category score out of range (app-level validation) */
    public function test_es014_category_score_out_of_range(): void
    {
        // Scores outside 0-100 range stored as-is (app validates)
        $scores = ['valid' => 75, 'too_high' => 150, 'too_low' => -20];

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'category_scores' => $scores,
        ]);

        $this->assertEquals(150, $analysis->getCategoryScore('too_high'));
        $this->assertEquals(-20, $analysis->getCategoryScore('too_low'));
    }

    /** @test ES-015: Concurrent updates to AI fields */
    public function test_es015_concurrent_updates_to_ai_fields(): void
    {
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
        ]);

        // Simulate concurrent update
        $analysis->update([
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
            'total_tokens' => 1000,
        ]);

        $analysis->refresh();
        $analysis->update([
            'prompt_tokens' => 800,
            'response_tokens' => 200,
        ]);

        $analysis->refresh();

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $analysis->ai_model);
        $this->assertEquals(1000, $analysis->total_tokens);
        $this->assertEquals(800, $analysis->prompt_tokens);
        $this->assertEquals(200, $analysis->response_tokens);
    }

    // =========================================================================
    // CATEGORY 7: INTEGRATION TESTS (IN-001 to IN-010)
    // =========================================================================

    /** @test IN-001: Full analysis lifecycle with AI */
    public function test_in001_full_analysis_lifecycle_with_ai(): void
    {
        // Create analysis
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'status' => 'pending',
        ]);

        // Simulate AI processing
        $analysis->update(['status' => 'running']);

        // Create AI request
        $aiRequest = $analysis->aiRequest()->create([
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'pending',
        ]);

        // Complete AI analysis
        $analysis->update([
            'status' => 'completed',
            'overall_score' => 78,
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
            'category_scores' => ['technical' => 85, 'content' => 72],
            'overall_summary' => 'Good SEO with room for improvement.',
        ]);

        $aiRequest->update([
            'status' => 'completed',
            'latency_ms' => 2500,
        ]);

        // Verify
        $analysis->refresh();
        $this->assertEquals('completed', $analysis->status);
        $this->assertEquals(78, $analysis->overall_score);
        $this->assertTrue($analysis->wasUnifiedAnalysis());
        $this->assertEquals('Gemini 2.0 Flash', $analysis->getAiModelDisplayName());
        $this->assertEquals(85, $analysis->getCategoryScore('technical'));
    }

    /** @test IN-002: Analysis without AI (legacy path) */
    public function test_in002_analysis_without_ai_legacy_path(): void
    {
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'status' => 'completed',
            'overall_score' => 75,
            'analysis_data' => ['legacy' => 'data'],
        ]);

        $this->assertFalse($analysis->wasUnifiedAnalysis());
        $this->assertEquals('Unknown', $analysis->getAiModelDisplayName());
        $this->assertNull($analysis->aiRequest);
    }

    /** @test IN-003: Multiple analyses with different AI models */
    public function test_in003_multiple_analyses_with_different_ai_models(): void
    {
        $gemini = SEOAnalysis::create([
            'domain' => 'gemini.com',
            'normalized_url' => 'https://gemini.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
            'total_tokens' => 2000,
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $gemini->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);

        $llama = SEOAnalysis::create([
            'domain' => 'llama.com',
            'normalized_url' => 'https://llama.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'ai_model' => 'meta-llama/llama-3-8b-instruct:free',
            'total_tokens' => 3000,
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $llama->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
            'status' => 'completed',
        ]);

        $this->assertEquals('Gemini 2.0 Flash', $gemini->getAiModelDisplayName());
        $this->assertEquals('Llama 3 8B', $llama->getAiModelDisplayName());
    }

    /** @test IN-004: Token usage aggregation */
    public function test_in004_token_usage_aggregation(): void
    {
        SEOAnalysis::create([
            'domain' => 'a.com',
            'normalized_url' => 'https://a.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'prompt_tokens' => 1000,
            'response_tokens' => 500,
            'total_tokens' => 1500,
        ]);
        SEOAnalysis::create([
            'domain' => 'b.com',
            'normalized_url' => 'https://b.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'prompt_tokens' => 2000,
            'response_tokens' => 800,
            'total_tokens' => 2800,
        ]);

        $totalPrompt = SEOAnalysis::sum('prompt_tokens');
        $totalResponse = SEOAnalysis::sum('response_tokens');
        $totalAll = SEOAnalysis::sum('total_tokens');

        $this->assertEquals(3000, $totalPrompt);
        $this->assertEquals(1300, $totalResponse);
        $this->assertEquals(4300, $totalAll);
    }

    /** @test IN-005: Category scores across analyses */
    public function test_in005_category_scores_across_analyses(): void
    {
        SEOAnalysis::create([
            'domain' => 'a.com',
            'normalized_url' => 'https://a.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'category_scores' => ['technical' => 80, 'content' => 70],
        ]);
        SEOAnalysis::create([
            'domain' => 'b.com',
            'normalized_url' => 'https://b.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'category_scores' => ['technical' => 90, 'content' => 60],
        ]);

        $allWithScores = SEOAnalysis::whereNotNull('category_scores')->get();

        $this->assertCount(2, $allWithScores);
    }

    /** @test IN-006: Truncation tracking */
    public function test_in006_truncation_tracking(): void
    {
        $truncated = SEOAnalysis::create([
            'domain' => 'truncated.com',
            'normalized_url' => 'https://truncated.com/',
            'overall_score' => 0,
            'analysis_data' => [],
            'truncation_applied' => [
                'primary' => 'summarize',
                'sections' => ['footer', 'sidebar'],
            ],
        ]);

        $notTruncated = SEOAnalysis::create([
            'domain' => 'not-truncated.com',
            'normalized_url' => 'https://not-truncated.com/',
            'overall_score' => 0,
            'analysis_data' => [],
        ]);

        $this->assertTrue($truncated->wasTruncated());
        $this->assertFalse($notTruncated->wasTruncated());
        $this->assertEquals(['footer', 'sidebar'], $truncated->getTruncationStrategies()['sections']);
    }

    /** @test IN-007: Failed AI analysis handling */
    public function test_in007_failed_ai_analysis_handling(): void
    {
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com/',
            'overall_score' => 0,
            'analysis_data' => [],
        ]);

        $aiRequest = $analysis->aiRequest()->create([
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'failed',
            'error_message' => 'API timeout after 30 seconds',
        ]);

        $analysis->update([
            'status' => 'failed',
            'error_message' => 'AI analysis failed: API timeout',
        ]);

        $analysis->refresh();

        $this->assertEquals('failed', $analysis->status);
        $this->assertEquals('failed', $analysis->aiRequest->status);
        $this->assertEquals('API timeout after 30 seconds', $analysis->aiRequest->error_message);
    }

    /** @test IN-008: Mixed legacy and AI analyses */
    public function test_in008_mixed_legacy_and_ai_analyses(): void
    {
        // Legacy analysis (no AI fields)
        SEOAnalysis::create([
            'domain' => 'legacy.com',
            'normalized_url' => 'https://legacy.com/',
            'overall_score' => 70,
            'analysis_data' => [],
            'status' => 'completed',
        ]);

        // New AI-powered analysis
        $aiAnalysis = SEOAnalysis::create([
            'domain' => 'ai-powered.com',
            'normalized_url' => 'https://ai-powered.com/',
            'overall_score' => 85,
            'analysis_data' => [],
            'status' => 'completed',
            'ai_model' => 'google/gemini-2.0-flash-exp:free',
            'category_scores' => ['technical' => 90, 'content' => 80],
        ]);

        $legacy = SEOAnalysis::where('domain', 'legacy.com')->first();
        $ai = SEOAnalysis::where('domain', 'ai-powered.com')->first();

        $this->assertFalse($legacy->wasUnifiedAnalysis());
        $this->assertTrue($ai->wasUnifiedAnalysis());
    }

    /** @test IN-009: Dashboard statistics query */
    public function test_in009_dashboard_statistics_query(): void
    {
        for ($i = 0; $i < 5; $i++) {
            SEOAnalysis::create([
                'domain' => "site$i.com",
                'normalized_url' => "https://site$i.com/",
                'overall_score' => 0,
                'analysis_data' => [],
                'ai_model' => 'google/gemini-2.0-flash-exp:free',
                'total_tokens' => ($i + 1) * 1000,
            ]);
        }

        $stats = [
            'total_analyses' => SEOAnalysis::count(),
            'ai_analyses' => SEOAnalysis::whereNotNull('ai_model')->count(),
            'total_tokens' => SEOAnalysis::sum('total_tokens'),
            'avg_tokens' => SEOAnalysis::whereNotNull('total_tokens')->avg('total_tokens'),
        ];

        $this->assertEquals(5, $stats['total_analyses']);
        $this->assertEquals(5, $stats['ai_analyses']);
        $this->assertEquals(15000, $stats['total_tokens']);
        $this->assertEquals(3000, $stats['avg_tokens']);
    }

    /** @test IN-010: Performance test with many records */
    public function test_in010_performance_test_with_many_records(): void
    {
        $startTime = microtime(true);

        for ($i = 0; $i < 50; $i++) {
            SEOAnalysis::create([
                'domain' => "perf$i.com",
                'normalized_url' => "https://perf$i.com/",
                'overall_score' => 0,
                'analysis_data' => [],
                'ai_model' => 'google/gemini-2.0-flash-exp:free',
                'prompt_tokens' => 1000,
                'response_tokens' => 500,
                'total_tokens' => 1500,
                'category_scores' => ['technical' => 85, 'content' => 72],
            ]);
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(5000, $elapsed, 'Batch insert should complete within 5 seconds');
        $this->assertEquals(50, SEOAnalysis::where('domain', 'like', 'perf%')->count());
    }
}
