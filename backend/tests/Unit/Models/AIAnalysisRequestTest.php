<?php

namespace Tests\Unit\Models;

use App\Models\AIAnalysisRequest;
use App\Models\SEOAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Unit tests for AIAnalysisRequest model.
 *
 * Tests for TASK-31-11: Update SEOAnalysis Model (includes AIAnalysisRequest)
 * @see TASK-31-11
 * @see SPEC-31
 */
class AIAnalysisRequestTest extends TestCase
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
    // CATEGORY 1: MODEL INSTANTIATION & FILLABLES (MF-001 to MF-015)
    // =========================================================================

    /** @test MF-001: Model can be instantiated */
    public function test_mf001_model_can_be_instantiated(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $this->assertInstanceOf(AIAnalysisRequest::class, $request);
        $this->assertEquals($analysis->id, $request->analysis_id);
    }

    /** @test MF-002: analysis_id is fillable */
    public function test_mf002_analysis_id_is_fillable(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $this->assertEquals($analysis->id, $request->analysis_id);
    }

    /** @test MF-003: model is fillable */
    public function test_mf003_model_is_fillable(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $request->model);
    }

    /** @test MF-004: prompt_tokens is fillable */
    public function test_mf004_prompt_tokens_is_fillable(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => 1500,
        ]);

        $this->assertEquals(1500, $request->prompt_tokens);
    }

    /** @test MF-005: response_tokens is fillable */
    public function test_mf005_response_tokens_is_fillable(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'response_tokens' => 500,
        ]);

        $this->assertEquals(500, $request->response_tokens);
    }

    /** @test MF-006: total_tokens is fillable */
    public function test_mf006_total_tokens_is_fillable(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'total_tokens' => 2000,
        ]);

        $this->assertEquals(2000, $request->total_tokens);
    }

    /** @test MF-007: latency_ms is fillable */
    public function test_mf007_latency_ms_is_fillable(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'latency_ms' => 2500,
        ]);

        $this->assertEquals(2500, $request->latency_ms);
    }

    /** @test MF-008: truncation_strategies is fillable */
    public function test_mf008_truncation_strategies_is_fillable(): void
    {
        $analysis = $this->createAnalysis();

        $strategies = ['primary' => 'summarize', 'fallback' => 'truncate'];

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'truncation_strategies' => $strategies,
        ]);

        $this->assertEquals($strategies, $request->truncation_strategies);
    }

    /** @test MF-009: request_hash is fillable */
    public function test_mf009_request_hash_is_fillable(): void
    {
        $analysis = $this->createAnalysis();

        $hash = hash('sha256', 'test-request-data');

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'request_hash' => $hash,
        ]);

        $this->assertEquals($hash, $request->request_hash);
    }

    /** @test MF-010: status is fillable */
    public function test_mf010_status_is_fillable(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);

        $this->assertEquals('completed', $request->status);
    }

    /** @test MF-011: error_message is fillable */
    public function test_mf011_error_message_is_fillable(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'failed',
            'error_message' => 'API timeout after 30 seconds',
        ]);

        $this->assertEquals('API timeout after 30 seconds', $request->error_message);
    }

    /** @test MF-012: All fields can be filled together */
    public function test_mf012_all_fields_fillable_together(): void
    {
        $analysis = $this->createAnalysis();

        $data = [
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
            'latency_ms' => 2500,
            'truncation_strategies' => ['primary' => 'summarize'],
            'request_hash' => hash('sha256', 'test'),
            'status' => 'completed',
        ];

        $request = AIAnalysisRequest::create($data);

        $this->assertEquals($data['analysis_id'], $request->analysis_id);
        $this->assertEquals($data['model'], $request->model);
        $this->assertEquals($data['prompt_tokens'], $request->prompt_tokens);
        $this->assertEquals($data['response_tokens'], $request->response_tokens);
        $this->assertEquals($data['total_tokens'], $request->total_tokens);
        $this->assertEquals($data['latency_ms'], $request->latency_ms);
        $this->assertEquals($data['truncation_strategies'], $request->truncation_strategies);
        $this->assertEquals($data['request_hash'], $request->request_hash);
        $this->assertEquals($data['status'], $request->status);
    }

    /** @test MF-013: Status can be set explicitly */
    public function test_mf013_status_can_be_set_explicitly(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $request->status);
    }

    /** @test MF-014: Minimal required fields with status */
    public function test_mf014_minimal_required_fields_with_status(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'pending',
        ]);

        $this->assertNotNull($request->id);
        $this->assertEquals('pending', $request->status);
        $this->assertNull($request->prompt_tokens);
        $this->assertNull($request->response_tokens);
        $this->assertNull($request->error_message);
    }

    /** @test MF-015: Model update with all fields */
    public function test_mf015_model_update_with_all_fields(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'pending',
        ]);

        $request->update([
            'status' => 'completed',
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
            'latency_ms' => 2500,
        ]);

        $request->refresh();

        $this->assertEquals('completed', $request->status);
        $this->assertEquals(1500, $request->prompt_tokens);
        $this->assertEquals(500, $request->response_tokens);
        $this->assertEquals(2000, $request->total_tokens);
        $this->assertEquals(2500, $request->latency_ms);
    }

    // =========================================================================
    // CATEGORY 2: CASTS (CA-001 to CA-010)
    // =========================================================================

    /** @test CA-001: prompt_tokens is cast to integer */
    public function test_ca001_prompt_tokens_is_cast_to_integer(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => 1500,
        ]);

        $this->assertIsInt($request->prompt_tokens);
    }

    /** @test CA-002: response_tokens is cast to integer */
    public function test_ca002_response_tokens_is_cast_to_integer(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'response_tokens' => 500,
        ]);

        $this->assertIsInt($request->response_tokens);
    }

    /** @test CA-003: total_tokens is cast to integer */
    public function test_ca003_total_tokens_is_cast_to_integer(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'total_tokens' => 2000,
        ]);

        $this->assertIsInt($request->total_tokens);
    }

    /** @test CA-004: latency_ms is cast to integer */
    public function test_ca004_latency_ms_is_cast_to_integer(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'latency_ms' => 2500,
        ]);

        $this->assertIsInt($request->latency_ms);
    }

    /** @test CA-005: truncation_strategies is cast to array */
    public function test_ca005_truncation_strategies_is_cast_to_array(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'truncation_strategies' => ['primary' => 'summarize'],
        ]);

        $this->assertIsArray($request->truncation_strategies);
    }

    /** @test CA-006: Null truncation_strategies returns null */
    public function test_ca006_null_truncation_strategies_returns_null(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $this->assertNull($request->truncation_strategies);
    }

    /** @test CA-007: Nested JSON in truncation_strategies */
    public function test_ca007_nested_json_in_truncation_strategies(): void
    {
        $analysis = $this->createAnalysis();

        $nested = [
            'primary' => 'summarize',
            'sections' => [
                'affected' => ['footer', 'sidebar'],
            ],
        ];

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'truncation_strategies' => $nested,
        ]);

        $this->assertEquals($nested, $request->truncation_strategies);
        $this->assertEquals(['footer', 'sidebar'], $request->truncation_strategies['sections']['affected']);
    }

    /** @test CA-008: Timestamps are cast to datetime */
    public function test_ca008_timestamps_are_cast_to_datetime(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $request->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $request->updated_at);
    }

    /** @test CA-009: Integer preservation after refresh */
    public function test_ca009_integer_preservation_after_refresh(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
            'latency_ms' => 2500,
        ]);

        $request->refresh();

        $this->assertEquals(1500, $request->prompt_tokens);
        $this->assertEquals(500, $request->response_tokens);
        $this->assertEquals(2000, $request->total_tokens);
        $this->assertEquals(2500, $request->latency_ms);
    }

    /** @test CA-010: Zero values stored correctly */
    public function test_ca010_zero_values_stored_correctly(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => 0,
            'response_tokens' => 0,
            'total_tokens' => 0,
            'latency_ms' => 0,
        ]);

        $this->assertEquals(0, $request->prompt_tokens);
        $this->assertEquals(0, $request->response_tokens);
        $this->assertEquals(0, $request->total_tokens);
        $this->assertEquals(0, $request->latency_ms);
    }

    // =========================================================================
    // CATEGORY 3: RELATIONSHIP TO SEOAnalysis (RE-001 to RE-010)
    // =========================================================================

    /** @test RE-001: analysis relationship exists */
    public function test_re001_analysis_relationship_exists(): void
    {
        $request = new AIAnalysisRequest();
        $this->assertTrue(method_exists($request, 'analysis'));
    }

    /** @test RE-002: analysis returns BelongsTo relationship */
    public function test_re002_analysis_returns_belongs_to_relationship(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $relation = $request->analysis();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    /** @test RE-003: analysis relationship returns parent model */
    public function test_re003_analysis_relationship_returns_parent_model(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $this->assertEquals($analysis->id, $request->analysis->id);
        $this->assertEquals('example.com', $request->analysis->domain);
    }

    /** @test RE-004: analysis uses correct foreign key */
    public function test_re004_analysis_uses_correct_foreign_key(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        // Verify the relationship query uses analysis_id
        $this->assertEquals($analysis->id, $request->analysis()->first()->id);
    }

    /** @test RE-005: Eager loading analysis */
    public function test_re005_eager_loading_analysis(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $loaded = AIAnalysisRequest::with('analysis')->find($request->id);

        $this->assertTrue($loaded->relationLoaded('analysis'));
        $this->assertEquals('example.com', $loaded->analysis->domain);
    }

    /** @test RE-006: Deleting parent cascades to request */
    public function test_re006_deleting_parent_cascades_to_request(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $requestId = $request->id;
        $analysis->delete();

        $this->assertDatabaseMissing('ai_analysis_requests', ['id' => $requestId]);
    }

    /** @test RE-007: Multiple requests for same analysis (if allowed) */
    public function test_re007_multiple_requests_for_same_analysis(): void
    {
        $analysis = $this->createAnalysis();

        // Create first request
        $request1 = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'failed',
        ]);

        // Create second request (retry)
        $request2 = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);

        $this->assertNotEquals($request1->id, $request2->id);
        $this->assertEquals($analysis->id, $request1->analysis_id);
        $this->assertEquals($analysis->id, $request2->analysis_id);
    }

    /** @test RE-008: Query with analysis relationship */
    public function test_re008_query_with_analysis_relationship(): void
    {
        $analysis = $this->createAnalysis(['domain' => 'test.com', 'overall_score' => 85]);

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);

        $request = AIAnalysisRequest::whereHas('analysis', function ($query) {
            $query->where('domain', 'test.com');
        })->first();

        $this->assertNotNull($request);
        $this->assertEquals('test.com', $request->analysis->domain);
    }

    /** @test RE-009: Request belongs to correct analysis */
    public function test_re009_request_belongs_to_correct_analysis(): void
    {
        $analysis1 = $this->createAnalysis(['domain' => 'one.com']);
        $analysis2 = $this->createAnalysis(['domain' => 'two.com']);

        $request1 = AIAnalysisRequest::create([
            'analysis_id' => $analysis1->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);
        $request2 = AIAnalysisRequest::create([
            'analysis_id' => $analysis2->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
        ]);

        $this->assertEquals('one.com', $request1->analysis->domain);
        $this->assertEquals('two.com', $request2->analysis->domain);
    }

    /** @test RE-010: Relationship query constraints */
    public function test_re010_relationship_query_constraints(): void
    {
        $analysis = $this->createAnalysis(['overall_score' => 90]);

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);

        // Query with constraint on parent
        $highScore = AIAnalysisRequest::whereHas('analysis', function ($query) {
            $query->where('overall_score', '>', 80);
        })->count();

        $this->assertEquals(1, $highScore);
    }

    // =========================================================================
    // CATEGORY 4: ACCESSOR METHODS (AC-001 to AC-010)
    // =========================================================================

    /** @test AC-001: wasSuccessful method exists */
    public function test_ac001_was_successful_method_exists(): void
    {
        $request = new AIAnalysisRequest();
        $this->assertTrue(method_exists($request, 'wasSuccessful'));
    }

    /** @test AC-002: wasSuccessful returns true for completed status */
    public function test_ac002_was_successful_returns_true_for_completed(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);

        $this->assertTrue($request->wasSuccessful());
    }

    /** @test AC-003: wasSuccessful returns false for non-completed status */
    public function test_ac003_was_successful_returns_false_for_non_completed(): void
    {
        $analysis = $this->createAnalysis();

        $pending = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'pending',
        ]);

        $failed = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'failed',
        ]);

        $this->assertFalse($pending->wasSuccessful());
        $this->assertFalse($failed->wasSuccessful());
    }

    /** @test AC-004: wasTruncated method exists */
    public function test_ac004_was_truncated_method_exists(): void
    {
        $request = new AIAnalysisRequest();
        $this->assertTrue(method_exists($request, 'wasTruncated'));
    }

    /** @test AC-005: wasTruncated returns true when truncation_strategies not empty */
    public function test_ac005_was_truncated_returns_true_when_strategies_not_empty(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'truncation_strategies' => ['primary' => 'summarize'],
        ]);

        $this->assertTrue($request->wasTruncated());
    }

    /** @test AC-006: wasTruncated returns false when truncation_strategies empty */
    public function test_ac006_was_truncated_returns_false_when_strategies_empty(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'truncation_strategies' => [],
        ]);

        $this->assertFalse($request->wasTruncated());
    }

    /** @test AC-007: wasTruncated returns false when truncation_strategies null */
    public function test_ac007_was_truncated_returns_false_when_strategies_null(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $this->assertFalse($request->wasTruncated());
    }

    /** @test AC-008: getLatencySeconds method exists */
    public function test_ac008_get_latency_seconds_method_exists(): void
    {
        $request = new AIAnalysisRequest();
        $this->assertTrue(method_exists($request, 'getLatencySeconds'));
    }

    /** @test AC-009: getLatencySeconds converts ms to seconds */
    public function test_ac009_get_latency_seconds_converts_ms_to_seconds(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'latency_ms' => 2500,
        ]);

        $this->assertEquals(2.5, $request->getLatencySeconds());
    }

    /** @test AC-010: getLatencySeconds handles null latency */
    public function test_ac010_get_latency_seconds_handles_null_latency(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $this->assertEquals(0.0, $request->getLatencySeconds());
    }

    // =========================================================================
    // CATEGORY 5: SCOPES (SC-001 to SC-010)
    // =========================================================================

    /** @test SC-001: scopeSuccessful exists */
    public function test_sc001_scope_successful_exists(): void
    {
        $request = new AIAnalysisRequest();
        $this->assertTrue(method_exists($request, 'scopeSuccessful'));
    }

    /** @test SC-002: scopeSuccessful filters by completed status */
    public function test_sc002_scope_successful_filters_by_completed(): void
    {
        $analysis = $this->createAnalysis();

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
            'status' => 'pending',
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'mistralai/mistral-7b-instruct:free',
            'status' => 'failed',
        ]);

        $successful = AIAnalysisRequest::successful()->get();

        $this->assertCount(1, $successful);
        $this->assertEquals('completed', $successful->first()->status);
    }

    /** @test SC-003: scopeFailed exists */
    public function test_sc003_scope_failed_exists(): void
    {
        $request = new AIAnalysisRequest();
        $this->assertTrue(method_exists($request, 'scopeFailed'));
    }

    /** @test SC-004: scopeFailed filters by failed status */
    public function test_sc004_scope_failed_filters_by_failed(): void
    {
        $analysis = $this->createAnalysis();

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
            'status' => 'failed',
        ]);

        $failed = AIAnalysisRequest::failed()->get();

        $this->assertCount(1, $failed);
        $this->assertEquals('failed', $failed->first()->status);
    }

    /** @test SC-005: Query by specific model */
    public function test_sc005_query_by_specific_model(): void
    {
        $analysis = $this->createAnalysis();

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
        ]);

        $gemini = AIAnalysisRequest::where('model', 'google/gemini-2.0-flash-exp:free')->get();

        $this->assertCount(1, $gemini);
    }

    /** @test SC-006: Query by latency threshold */
    public function test_sc006_query_by_latency_threshold(): void
    {
        $analysis = $this->createAnalysis();

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'latency_ms' => 500,
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
            'latency_ms' => 5000,
        ]);

        $slow = AIAnalysisRequest::where('latency_ms', '>', 1000)->get();

        $this->assertCount(1, $slow);
        $this->assertEquals(5000, $slow->first()->latency_ms);
    }

    /** @test SC-007: Query by token usage */
    public function test_sc007_query_by_token_usage(): void
    {
        $analysis = $this->createAnalysis();

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'total_tokens' => 500,
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
            'total_tokens' => 5000,
        ]);

        $highToken = AIAnalysisRequest::where('total_tokens', '>', 1000)->get();

        $this->assertCount(1, $highToken);
        $this->assertEquals(5000, $highToken->first()->total_tokens);
    }

    /** @test SC-008: Query by request_hash */
    public function test_sc008_query_by_request_hash(): void
    {
        $analysis = $this->createAnalysis();

        $hash1 = hash('sha256', 'request-1');
        $hash2 = hash('sha256', 'request-2');

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'request_hash' => $hash1,
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
            'request_hash' => $hash2,
        ]);

        $found = AIAnalysisRequest::where('request_hash', $hash1)->first();

        $this->assertNotNull($found);
        $this->assertEquals($hash1, $found->request_hash);
    }

    /** @test SC-009: Count by status */
    public function test_sc009_count_by_status(): void
    {
        $analysis = $this->createAnalysis();

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
            'status' => 'completed',
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'mistralai/mistral-7b-instruct:free',
            'status' => 'failed',
        ]);

        $counts = AIAnalysisRequest::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $this->assertEquals(2, $counts['completed']);
        $this->assertEquals(1, $counts['failed']);
    }

    /** @test SC-010: Order by latency */
    public function test_sc010_order_by_latency(): void
    {
        $analysis = $this->createAnalysis();

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'latency_ms' => 5000,
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
            'latency_ms' => 500,
        ]);

        $ordered = AIAnalysisRequest::orderBy('latency_ms', 'asc')->get();

        $this->assertEquals(500, $ordered->first()->latency_ms);
        $this->assertEquals(5000, $ordered->last()->latency_ms);
    }

    // =========================================================================
    // CATEGORY 6: EDGE CASES & SECURITY (ES-001 to ES-010)
    // =========================================================================

    /** @test ES-001: Very long model name */
    public function test_es001_very_long_model_name(): void
    {
        $analysis = $this->createAnalysis();

        $longName = str_repeat('a', 100);

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => $longName,
        ]);

        $this->assertEquals($longName, $request->model);
    }

    /** @test ES-002: SHA-256 hash storage */
    public function test_es002_sha256_hash_storage(): void
    {
        $analysis = $this->createAnalysis();

        $hash = hash('sha256', 'test-data');

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'request_hash' => $hash,
        ]);

        $this->assertEquals(64, strlen($request->request_hash));
        $this->assertEquals($hash, $request->request_hash);
    }

    /** @test ES-003: Unicode in error_message */
    public function test_es003_unicode_in_error_message(): void
    {
        $analysis = $this->createAnalysis();

        $message = 'エラーが発生しました: ошибка 错误 🎉';

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'failed',
            'error_message' => $message,
        ]);

        $this->assertEquals($message, $request->error_message);
    }

    /** @test ES-004: XSS in error_message */
    public function test_es004_xss_in_error_message(): void
    {
        $analysis = $this->createAnalysis();

        $malicious = '<script>alert("xss")</script>Error message';

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'failed',
            'error_message' => $malicious,
        ]);

        // Stored as-is, frontend must escape
        $this->assertEquals($malicious, $request->error_message);
    }

    /** @test ES-005: SQL injection in model */
    public function test_es005_sql_injection_in_model(): void
    {
        $analysis = $this->createAnalysis();

        $malicious = "'; DROP TABLE ai_analysis_requests; --";

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => $malicious,
        ]);

        // Stored as literal string
        $this->assertEquals($malicious, $request->model);
        $this->assertDatabaseHas('ai_analysis_requests', ['id' => $request->id]);
    }

    /** @test ES-006: Large error message */
    public function test_es006_large_error_message(): void
    {
        $analysis = $this->createAnalysis();

        $largeMessage = str_repeat('Error: ', 1000);

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'failed',
            'error_message' => $largeMessage,
        ]);

        $this->assertEquals($largeMessage, $request->error_message);
    }

    /** @test ES-007: Negative latency (app-level validation) */
    public function test_es007_negative_latency(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'latency_ms' => -100,
        ]);

        // Stored as-is at DB level
        $this->assertEquals(-100, $request->latency_ms);
    }

    /** @test ES-008: Negative tokens (app-level validation) */
    public function test_es008_negative_tokens(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => -100,
            'response_tokens' => -50,
            'total_tokens' => -150,
        ]);

        // Stored as-is at DB level
        $this->assertEquals(-100, $request->prompt_tokens);
        $this->assertEquals(-50, $request->response_tokens);
        $this->assertEquals(-150, $request->total_tokens);
    }

    /** @test ES-009: Empty truncation_strategies */
    public function test_es009_empty_truncation_strategies(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'truncation_strategies' => [],
        ]);

        $this->assertIsArray($request->truncation_strategies);
        $this->assertEmpty($request->truncation_strategies);
    }

    /** @test ES-010: Concurrent updates */
    public function test_es010_concurrent_updates(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'pending',
        ]);

        $request->update(['status' => 'completed']);
        $request->refresh();
        $request->update(['latency_ms' => 2500]);

        $request->refresh();

        $this->assertEquals('completed', $request->status);
        $this->assertEquals(2500, $request->latency_ms);
    }

    // =========================================================================
    // CATEGORY 7: INTEGRATION TESTS (IN-001 to IN-010)
    // =========================================================================

    /** @test IN-001: Full request lifecycle */
    public function test_in001_full_request_lifecycle(): void
    {
        $analysis = $this->createAnalysis();

        // Create pending request
        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $request->status);
        $this->assertFalse($request->wasSuccessful());

        // Update to completed
        $request->update([
            'status' => 'completed',
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
            'latency_ms' => 2500,
        ]);

        $request->refresh();

        $this->assertTrue($request->wasSuccessful());
        $this->assertEquals(2.5, $request->getLatencySeconds());
    }

    /** @test IN-002: Failed request with error */
    public function test_in002_failed_request_with_error(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'failed',
            'error_message' => 'Rate limit exceeded',
        ]);

        $this->assertFalse($request->wasSuccessful());
        $this->assertEquals('Rate limit exceeded', $request->error_message);
    }

    /** @test IN-003: Request with truncation */
    public function test_in003_request_with_truncation(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'status' => 'completed',
            'truncation_strategies' => [
                'primary' => 'summarize',
                'sections' => ['footer', 'sidebar'],
            ],
        ]);

        $this->assertTrue($request->wasTruncated());
        $this->assertEquals(['footer', 'sidebar'], $request->truncation_strategies['sections']);
    }

    /** @test IN-004: Statistics aggregation */
    public function test_in004_statistics_aggregation(): void
    {
        $analysis = $this->createAnalysis();

        for ($i = 0; $i < 5; $i++) {
            AIAnalysisRequest::create([
                'analysis_id' => $analysis->id,
                'model' => 'google/gemini-2.0-flash-exp:free',
                'status' => 'completed',
                'prompt_tokens' => 1000 * ($i + 1),
                'response_tokens' => 500 * ($i + 1),
                'total_tokens' => 1500 * ($i + 1),
                'latency_ms' => 1000 * ($i + 1),
            ]);
        }

        $stats = [
            'total' => AIAnalysisRequest::count(),
            'successful' => AIAnalysisRequest::successful()->count(),
            'avg_latency' => AIAnalysisRequest::avg('latency_ms'),
            'total_tokens' => AIAnalysisRequest::sum('total_tokens'),
        ];

        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(5, $stats['successful']);
        $this->assertEquals(3000, $stats['avg_latency']);
        $this->assertEquals(22500, $stats['total_tokens']);
    }

    /** @test IN-005: Request hash deduplication simulation */
    public function test_in005_request_hash_deduplication(): void
    {
        $analysis = $this->createAnalysis();

        $hash = hash('sha256', 'duplicate-request');

        $request1 = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'request_hash' => $hash,
            'status' => 'completed',
        ]);

        // Check for existing hash before creating new
        $existing = AIAnalysisRequest::where('request_hash', $hash)->first();
        $this->assertNotNull($existing);
        $this->assertEquals($request1->id, $existing->id);
    }

    /** @test IN-006: Multiple models comparison */
    public function test_in006_multiple_models_comparison(): void
    {
        $analysis = $this->createAnalysis();

        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'latency_ms' => 1500,
            'total_tokens' => 2000,
        ]);
        AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'meta-llama/llama-3-8b-instruct:free',
            'latency_ms' => 3000,
            'total_tokens' => 3000,
        ]);

        $avgLatency = AIAnalysisRequest::groupBy('model')
            ->selectRaw('model, AVG(latency_ms) as avg_latency')
            ->get();

        $this->assertCount(2, $avgLatency);
    }

    /** @test IN-007: Date range queries */
    public function test_in007_date_range_queries(): void
    {
        $analysis = $this->createAnalysis();

        // Create a request with an older timestamp
        $old = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);
        // Manually update the timestamp to simulate an older record using direct DB update
        \DB::table('ai_analysis_requests')
            ->where('id', $old->id)
            ->update(['created_at' => now()->subDays(7)]);

        // Create a new request
        $new = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        // Refresh to get the actual timestamps
        $old->refresh();
        $new->refresh();

        // Filter by this specific analysis to avoid test pollution
        $recent = AIAnalysisRequest::where('analysis_id', $analysis->id)
            ->where('created_at', '>=', now()->subDay())
            ->get();

        $this->assertCount(1, $recent);
        $this->assertEquals($new->id, $recent->first()->id);
    }

    /** @test IN-008: Relationship integrity on delete */
    public function test_in008_relationship_integrity_on_delete(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
        ]);

        $requestId = $request->id;
        $analysis->delete();

        // Request should be cascade deleted
        $this->assertDatabaseMissing('ai_analysis_requests', ['id' => $requestId]);
    }

    /** @test IN-009: Token calculation consistency */
    public function test_in009_token_calculation_consistency(): void
    {
        $analysis = $this->createAnalysis();

        $request = AIAnalysisRequest::create([
            'analysis_id' => $analysis->id,
            'model' => 'google/gemini-2.0-flash-exp:free',
            'prompt_tokens' => 1500,
            'response_tokens' => 500,
            'total_tokens' => 2000,
        ]);

        $this->assertEquals(
            $request->prompt_tokens + $request->response_tokens,
            $request->total_tokens
        );
    }

    /** @test IN-010: Performance test with many records */
    public function test_in010_performance_test_with_many_records(): void
    {
        $analysis = $this->createAnalysis();

        $startTime = microtime(true);

        for ($i = 0; $i < 50; $i++) {
            AIAnalysisRequest::create([
                'analysis_id' => $analysis->id,
                'model' => 'google/gemini-2.0-flash-exp:free',
                'prompt_tokens' => 1000,
                'response_tokens' => 500,
                'total_tokens' => 1500,
                'latency_ms' => 2000,
                'status' => 'completed',
            ]);
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(5000, $elapsed, 'Batch insert should complete within 5 seconds');
        $this->assertEquals(50, AIAnalysisRequest::where('analysis_id', $analysis->id)->count());
    }
}
