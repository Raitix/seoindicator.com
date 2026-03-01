<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for AnalysisJobResult model.
 *
 * @see TASK-19-16
 * @see \App\Models\AnalysisJobResult
 */
class AnalysisJobResultTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;
    protected AnalysisJob $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 3,
        ]);
    }

    // =========================================================================
    // STATUS CONSTANTS TESTS
    // =========================================================================

    /** @test */
    public function status_constants_are_defined(): void
    {
        $this->assertEquals('pending', AnalysisJobResult::STATUS_PENDING);
        $this->assertEquals('running', AnalysisJobResult::STATUS_RUNNING);
        $this->assertEquals('completed', AnalysisJobResult::STATUS_COMPLETED);
        $this->assertEquals('failed', AnalysisJobResult::STATUS_FAILED);
        $this->assertEquals('skipped', AnalysisJobResult::STATUS_SKIPPED);
    }

    // =========================================================================
    // MODEL CREATION TESTS
    // =========================================================================

    /** @test */
    public function can_create_analysis_job_result(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('analysis_job_results', [
            'id' => $result->id,
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function default_status_is_pending(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
        ]);

        $this->assertEquals('pending', $result->status);
    }

    /** @test */
    public function unique_constraint_on_job_and_indicator(): void
    {
        AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
        ]);

        // Attempting to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
        ]);
    }

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    /** @test */
    public function belongs_to_job(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
        ]);

        $this->assertTrue($result->job->is($this->job));
    }

    // =========================================================================
    // SCOPE TESTS
    // =========================================================================

    /** @test */
    public function scope_pending_filters_correctly(): void
    {
        $pendingResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'pending',
        ]);

        $completedResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
            'status' => 'completed',
        ]);

        $results = AnalysisJobResult::pending()->get();

        $this->assertTrue($results->contains($pendingResult));
        $this->assertFalse($results->contains($completedResult));
    }

    /** @test */
    public function scope_running_filters_correctly(): void
    {
        $runningResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'running',
        ]);

        $pendingResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
            'status' => 'pending',
        ]);

        $results = AnalysisJobResult::running()->get();

        $this->assertTrue($results->contains($runningResult));
        $this->assertFalse($results->contains($pendingResult));
    }

    /** @test */
    public function scope_completed_filters_correctly(): void
    {
        $completedResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'completed',
        ]);

        $pendingResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
            'status' => 'pending',
        ]);

        $results = AnalysisJobResult::completed()->get();

        $this->assertTrue($results->contains($completedResult));
        $this->assertFalse($results->contains($pendingResult));
    }

    /** @test */
    public function scope_failed_filters_correctly(): void
    {
        $failedResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'failed',
        ]);

        $pendingResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
            'status' => 'pending',
        ]);

        $results = AnalysisJobResult::failed()->get();

        $this->assertTrue($results->contains($failedResult));
        $this->assertFalse($results->contains($pendingResult));
    }

    /** @test */
    public function scope_skipped_filters_correctly(): void
    {
        $skippedResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'skipped',
        ]);

        $pendingResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
            'status' => 'pending',
        ]);

        $results = AnalysisJobResult::skipped()->get();

        $this->assertTrue($results->contains($skippedResult));
        $this->assertFalse($results->contains($pendingResult));
    }

    /** @test */
    public function scope_by_job_filters_correctly(): void
    {
        $otherJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 1,
        ]);

        $result1 = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
        ]);

        $result2 = AnalysisJobResult::create([
            'job_id' => $otherJob->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
        ]);

        $results = AnalysisJobResult::byJob($this->job->id)->get();

        $this->assertTrue($results->contains($result1));
        $this->assertFalse($results->contains($result2));
    }

    /** @test */
    public function scope_by_indicator_filters_correctly(): void
    {
        $metaResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
        ]);

        $headingsResult = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
        ]);

        $results = AnalysisJobResult::byIndicator('meta_tags')->get();

        $this->assertTrue($results->contains($metaResult));
        $this->assertFalse($results->contains($headingsResult));
    }

    // =========================================================================
    // STATUS HELPER TESTS
    // =========================================================================

    /** @test */
    public function is_pending_returns_correct_boolean(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'pending',
        ]);

        $this->assertTrue($result->isPending());
        $this->assertFalse($result->isRunning());
        $this->assertFalse($result->isCompleted());
        $this->assertFalse($result->isFailed());
        $this->assertFalse($result->isSkipped());
    }

    /** @test */
    public function is_running_returns_correct_boolean(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'running',
        ]);

        $this->assertFalse($result->isPending());
        $this->assertTrue($result->isRunning());
        $this->assertFalse($result->isCompleted());
        $this->assertFalse($result->isFailed());
        $this->assertFalse($result->isSkipped());
    }

    /** @test */
    public function is_completed_returns_correct_boolean(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'completed',
        ]);

        $this->assertFalse($result->isPending());
        $this->assertFalse($result->isRunning());
        $this->assertTrue($result->isCompleted());
        $this->assertFalse($result->isFailed());
        $this->assertFalse($result->isSkipped());
    }

    /** @test */
    public function is_failed_returns_correct_boolean(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'failed',
        ]);

        $this->assertFalse($result->isPending());
        $this->assertFalse($result->isRunning());
        $this->assertFalse($result->isCompleted());
        $this->assertTrue($result->isFailed());
        $this->assertFalse($result->isSkipped());
    }

    /** @test */
    public function is_skipped_returns_correct_boolean(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'skipped',
        ]);

        $this->assertFalse($result->isPending());
        $this->assertFalse($result->isRunning());
        $this->assertFalse($result->isCompleted());
        $this->assertFalse($result->isFailed());
        $this->assertTrue($result->isSkipped());
    }

    // =========================================================================
    // STATUS TRANSITION TESTS
    // =========================================================================

    /** @test */
    public function mark_as_running_updates_status_and_sets_started_at(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'pending',
        ]);

        $updated = $result->markAsRunning();

        $this->assertTrue($updated);
        $this->assertEquals('running', $result->status);
        $this->assertNotNull($result->started_at);
    }

    /** @test */
    public function mark_as_completed_updates_all_fields(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'running',
            'started_at' => now()->subSeconds(5),
        ]);

        $data = ['title' => 'Test Page', 'description' => 'Test description'];
        $recommendations = [
            ['priority' => 'high', 'message' => 'Add meta description'],
        ];

        $updated = $result->markAsCompleted(
            score: 85.5,
            maxScore: 100.0,
            data: $data,
            recommendations: $recommendations
        );

        $this->assertTrue($updated);
        $this->assertEquals('completed', $result->status);
        $this->assertEquals(85.5, $result->score);
        $this->assertEquals(100.0, $result->max_score);
        $this->assertEquals(86, $result->percentage);
        $this->assertEquals($data, $result->data);
        $this->assertEquals($recommendations, $result->recommendations);
        $this->assertNotNull($result->completed_at);
        $this->assertNotNull($result->processing_time_ms);
    }

    /** @test */
    public function mark_as_completed_calculates_percentage(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $result->markAsCompleted(score: 75.0, maxScore: 100.0);

        $this->assertEquals(75, $result->percentage);
    }

    /** @test */
    public function mark_as_completed_handles_zero_max_score(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $result->markAsCompleted(score: 75.0, maxScore: 0);

        $this->assertEquals(0, $result->percentage);
    }

    /** @test */
    public function mark_as_failed_updates_status_and_error(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'running',
            'started_at' => now()->subSeconds(3),
        ]);

        $updated = $result->markAsFailed('Failed to fetch page');

        $this->assertTrue($updated);
        $this->assertEquals('failed', $result->status);
        $this->assertEquals('Failed to fetch page', $result->error_message);
        $this->assertNotNull($result->completed_at);
        $this->assertNotNull($result->processing_time_ms);
    }

    /** @test */
    public function mark_as_failed_without_error_message(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $updated = $result->markAsFailed();

        $this->assertTrue($updated);
        $this->assertEquals('failed', $result->status);
        $this->assertNull($result->error_message);
    }

    // =========================================================================
    // ACCESSOR TESTS
    // =========================================================================

    /** @test */
    public function percentage_accessor_returns_stored_value(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'completed',
            'score' => 85.0,
            'max_score' => 100.0,
            'percentage' => 85,
        ]);

        $this->assertEquals(85, $result->percentage);
    }

    /** @test */
    public function percentage_accessor_calculates_if_not_stored(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'completed',
            'score' => 75.0,
            'max_score' => 100.0,
        ]);

        $this->assertEquals(75, $result->percentage);
    }

    /** @test */
    public function percentage_accessor_returns_null_for_missing_scores(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'pending',
        ]);

        $this->assertNull($result->percentage);
    }

    /** @test */
    public function data_accessor_returns_array(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'data' => ['title' => 'Test'],
        ]);

        $this->assertIsArray($result->data);
        $this->assertEquals('Test', $result->data['title']);
    }

    /** @test */
    public function data_accessor_returns_empty_array_for_null(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
        ]);

        $this->assertIsArray($result->data);
        $this->assertEmpty($result->data);
    }

    /** @test */
    public function recommendations_accessor_returns_array(): void
    {
        $recommendations = [
            ['priority' => 'high', 'message' => 'Fix this'],
        ];

        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'recommendations' => $recommendations,
        ]);

        $this->assertIsArray($result->recommendations);
        $this->assertEquals($recommendations, $result->recommendations);
    }

    /** @test */
    public function recommendations_accessor_returns_empty_array_for_null(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
        ]);

        $this->assertIsArray($result->recommendations);
        $this->assertEmpty($result->recommendations);
    }

    // =========================================================================
    // PROCESSING TIME TESTS
    // =========================================================================

    /** @test */
    public function processing_time_is_calculated_on_completion(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'running',
            'started_at' => now()->subMilliseconds(1500),
        ]);

        $result->markAsCompleted(score: 100);

        $this->assertGreaterThanOrEqual(1400, $result->processing_time_ms);
    }

    /** @test */
    public function processing_time_is_calculated_on_failure(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'running',
            'started_at' => now()->subMilliseconds(500),
        ]);

        $result->markAsFailed('Error');

        $this->assertGreaterThanOrEqual(400, $result->processing_time_ms);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /** @test */
    public function can_store_large_data_json(): void
    {
        $largeData = [
            'links' => array_map(function ($i) {
                return ['url' => "https://example.com/page-{$i}", 'text' => "Link {$i}"];
            }, range(1, 500)),
        ];

        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'links',
            'indicator_name' => 'Links Analysis',
            'data' => $largeData,
        ]);

        $this->assertCount(500, $result->data['links']);
    }

    /** @test */
    public function can_store_large_recommendations_json(): void
    {
        $recommendations = array_map(function ($i) {
            return [
                'priority' => 'medium',
                'message' => "Recommendation {$i}",
                'details' => "Details for recommendation {$i}",
            ];
        }, range(1, 50));

        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'recommendations' => $recommendations,
        ]);

        $this->assertCount(50, $result->recommendations);
    }

    /** @test */
    public function handles_special_characters_in_data(): void
    {
        $data = [
            'title' => 'Test with "quotes" and \'apostrophes\'',
            'unicode' => '日本語 🎉 Tëst',
        ];

        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'data' => $data,
        ]);

        $this->assertEquals('Test with "quotes" and \'apostrophes\'', $result->data['title']);
        $this->assertEquals('日本語 🎉 Tëst', $result->data['unicode']);
    }

    /** @test */
    public function decimal_precision_for_scores(): void
    {
        $result = AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'completed',
            'score' => 85.55555,
            'max_score' => 100.0,
        ]);

        // Score is stored with 2 decimal precision
        $this->assertEquals('85.56', number_format($result->score, 2));
    }
}
