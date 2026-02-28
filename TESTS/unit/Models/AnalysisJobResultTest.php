<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalysisJobResultTest extends TestCase
{
    use RefreshDatabase;

    protected function createJob(): AnalysisJob
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        return AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
        ]);
    }

    // =========================================================================
    // 1. MODEL CREATION TESTS
    // =========================================================================

    /** @test */
    public function can_create_result_with_all_attributes(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'completed',
            'score' => 85.50,
            'max_score' => 100.00,
            'percentage' => 85,
            'data' => ['title' => 'Test', 'description' => 'Test desc'],
            'recommendations' => ['Add meta description'],
            'processing_time_ms' => 1500,
        ]);

        $this->assertDatabaseHas('analysis_job_results', [
            'id' => $result->id,
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function can_create_with_minimal_attributes(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
        ]);

        $this->assertEquals('pending', $result->status);
        $this->assertNull($result->score);
        $this->assertNull($result->max_score);
    }

    // =========================================================================
    // 2. FILLABLE ATTRIBUTES TESTS
    // =========================================================================

    /** @test */
    public function all_fillable_attributes_are_accessible(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings Structure',
            'status' => 'completed',
            'score' => 90.00,
            'max_score' => 100.00,
            'percentage' => 90,
            'data' => ['h1_count' => 1, 'h2_count' => 5],
            'recommendations' => ['Add more H2 sections'],
            'error_message' => null,
            'started_at' => now()->subSeconds(5),
            'completed_at' => now(),
            'processing_time_ms' => 5000,
        ]);

        $this->assertEquals($job->id, $result->job_id);
        $this->assertEquals('headings', $result->indicator_slug);
        $this->assertEquals('Headings Structure', $result->indicator_name);
        $this->assertEquals('completed', $result->status);
        $this->assertEquals(90.00, $result->score);
        $this->assertEquals(100.00, $result->max_score);
        $this->assertEquals(90, $result->percentage);
    }

    // =========================================================================
    // 3. STATUS CONSTANTS TESTS
    // =========================================================================

    /** @test */
    public function status_constants_exist(): void
    {
        $this->assertEquals('pending', AnalysisJobResult::STATUS_PENDING);
        $this->assertEquals('running', AnalysisJobResult::STATUS_RUNNING);
        $this->assertEquals('completed', AnalysisJobResult::STATUS_COMPLETED);
        $this->assertEquals('failed', AnalysisJobResult::STATUS_FAILED);
        $this->assertEquals('skipped', AnalysisJobResult::STATUS_SKIPPED);
    }

    // =========================================================================
    // 4. RELATIONSHIP TESTS
    // =========================================================================

    /** @test */
    public function belongs_to_analysis_job(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
        ]);

        $this->assertInstanceOf(AnalysisJob::class, $result->job);
        $this->assertEquals($job->id, $result->job->id);
    }

    // =========================================================================
    // 5. SCOPE TESTS
    // =========================================================================

    /** @test */
    public function scope_pending_returns_only_pending_results(): void
    {
        $job = $this->createJob();

        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'a', 'indicator_name' => 'A', 'status' => 'pending']);
        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'b', 'indicator_name' => 'B', 'status' => 'completed']);

        $pendingResults = AnalysisJobResult::pending()->get();

        $this->assertCount(1, $pendingResults);
        $this->assertEquals('pending', $pendingResults->first()->status);
    }

    /** @test */
    public function scope_running_returns_only_running_results(): void
    {
        $job = $this->createJob();

        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'a', 'indicator_name' => 'A', 'status' => 'running']);
        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'b', 'indicator_name' => 'B', 'status' => 'pending']);

        $runningResults = AnalysisJobResult::running()->get();

        $this->assertCount(1, $runningResults);
        $this->assertEquals('running', $runningResults->first()->status);
    }

    /** @test */
    public function scope_completed_returns_only_completed_results(): void
    {
        $job = $this->createJob();

        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'a', 'indicator_name' => 'A', 'status' => 'completed']);
        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'b', 'indicator_name' => 'B', 'status' => 'pending']);

        $completedResults = AnalysisJobResult::completed()->get();

        $this->assertCount(1, $completedResults);
        $this->assertEquals('completed', $completedResults->first()->status);
    }

    /** @test */
    public function scope_failed_returns_only_failed_results(): void
    {
        $job = $this->createJob();

        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'a', 'indicator_name' => 'A', 'status' => 'failed']);
        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'b', 'indicator_name' => 'B', 'status' => 'pending']);

        $failedResults = AnalysisJobResult::failed()->get();

        $this->assertCount(1, $failedResults);
        $this->assertEquals('failed', $failedResults->first()->status);
    }

    /** @test */
    public function scope_skipped_returns_only_skipped_results(): void
    {
        $job = $this->createJob();

        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'a', 'indicator_name' => 'A', 'status' => 'skipped']);
        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'b', 'indicator_name' => 'B', 'status' => 'pending']);

        $skippedResults = AnalysisJobResult::skipped()->get();

        $this->assertCount(1, $skippedResults);
        $this->assertEquals('skipped', $skippedResults->first()->status);
    }

    /** @test */
    public function scope_by_job_filters_by_job(): void
    {
        $job1 = $this->createJob();
        $job2 = $this->createJob();

        AnalysisJobResult::create(['job_id' => $job1->id, 'indicator_slug' => 'a', 'indicator_name' => 'A']);
        AnalysisJobResult::create(['job_id' => $job2->id, 'indicator_slug' => 'b', 'indicator_name' => 'B']);

        $job1Results = AnalysisJobResult::byJob($job1->id)->get();

        $this->assertCount(1, $job1Results);
        $this->assertEquals($job1->id, $job1Results->first()->job_id);
    }

    /** @test */
    public function scope_by_indicator_filters_by_slug(): void
    {
        $job = $this->createJob();

        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'meta_tags', 'indicator_name' => 'Meta Tags']);
        AnalysisJobResult::create(['job_id' => $job->id, 'indicator_slug' => 'headings', 'indicator_name' => 'Headings']);

        $metaResults = AnalysisJobResult::byIndicator('meta_tags')->get();

        $this->assertCount(1, $metaResults);
        $this->assertEquals('meta_tags', $metaResults->first()->indicator_slug);
    }

    // =========================================================================
    // 6. JSON CASTS TESTS
    // =========================================================================

    /** @test */
    public function data_attribute_cast_to_array(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'data' => ['key1' => 'value1', 'key2' => ['nested' => 'value']],
        ]);

        $this->assertIsArray($result->data);
        $this->assertEquals('value1', $result->data['key1']);
        $this->assertEquals(['nested' => 'value'], $result->data['key2']);
    }

    /** @test */
    public function data_attribute_returns_empty_array_when_null(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'data' => null,
        ]);

        $this->assertEquals([], $result->data);
    }

    /** @test */
    public function recommendations_attribute_cast_to_array(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'recommendations' => [
                ['priority' => 'high', 'message' => 'Fix title'],
                ['priority' => 'medium', 'message' => 'Add alt text'],
            ],
        ]);

        $this->assertIsArray($result->recommendations);
        $this->assertCount(2, $result->recommendations);
        $this->assertEquals('Fix title', $result->recommendations[0]['message']);
    }

    /** @test */
    public function recommendations_attribute_returns_empty_array_when_null(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'recommendations' => null,
        ]);

        $this->assertEquals([], $result->recommendations);
    }

    // =========================================================================
    // 7. CALCULATED ACCESSOR TESTS
    // =========================================================================

    /** @test */
    public function percentage_accessor_calculates_from_score(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'score' => 75.00,
            'max_score' => 100.00,
        ]);

        $this->assertEquals(75, $result->percentage);
    }

    /** @test */
    public function percentage_accessor_returns_null_when_no_score(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'score' => null,
            'max_score' => null,
        ]);

        $this->assertNull($result->percentage);
    }

    /** @test */
    public function percentage_accessor_handles_partial_scores(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'score' => 45.50,
            'max_score' => 50.00,
        ]);

        $this->assertEquals(91, $result->percentage);
    }

    // =========================================================================
    // 8. STATUS HELPER TESTS
    // =========================================================================

    /** @test */
    public function is_pending_returns_correct_boolean(): void
    {
        $job = $this->createJob();

        $pendingResult = AnalysisJobResult::create([
            'job_id' => $job->id, 'indicator_slug' => 'a', 'indicator_name' => 'A', 'status' => 'pending']);
        $completedResult = AnalysisJobResult::create([
            'job_id' => $job->id, 'indicator_slug' => 'b', 'indicator_name' => 'B', 'status' => 'completed']);

        $this->assertTrue($pendingResult->isPending());
        $this->assertFalse($completedResult->isPending());
    }

    /** @test */
    public function is_running_returns_correct_boolean(): void
    {
        $job = $this->createJob();

        $runningResult = AnalysisJobResult::create([
            'job_id' => $job->id, 'indicator_slug' => 'a', 'indicator_name' => 'A', 'status' => 'running']);
        $pendingResult = AnalysisJobResult::create([
            'job_id' => $job->id, 'indicator_slug' => 'b', 'indicator_name' => 'B', 'status' => 'pending']);

        $this->assertTrue($runningResult->isRunning());
        $this->assertFalse($pendingResult->isRunning());
    }

    /** @test */
    public function is_completed_returns_correct_boolean(): void
    {
        $job = $this->createJob();

        $completedResult = AnalysisJobResult::create([
            'job_id' => $job->id, 'indicator_slug' => 'a', 'indicator_name' => 'A', 'status' => 'completed']);
        $pendingResult = AnalysisJobResult::create([
            'job_id' => $job->id, 'indicator_slug' => 'b', 'indicator_name' => 'B', 'status' => 'pending']);

        $this->assertTrue($completedResult->isCompleted());
        $this->assertFalse($pendingResult->isCompleted());
    }

    /** @test */
    public function is_failed_returns_correct_boolean(): void
    {
        $job = $this->createJob();

        $failedResult = AnalysisJobResult::create([
            'job_id' => $job->id, 'indicator_slug' => 'a', 'indicator_name' => 'A', 'status' => 'failed']);
        $pendingResult = AnalysisJobResult::create([
            'job_id' => $job->id, 'indicator_slug' => 'b', 'indicator_name' => 'B', 'status' => 'pending']);

        $this->assertTrue($failedResult->isFailed());
        $this->assertFalse($pendingResult->isFailed());
    }

    /** @test */
    public function is_skipped_returns_correct_boolean(): void
    {
        $job = $this->createJob();

        $skippedResult = AnalysisJobResult::create([
            'job_id' => $job->id, 'indicator_slug' => 'a', 'indicator_name' => 'A', 'status' => 'skipped']);
        $pendingResult = AnalysisJobResult::create([
            'job_id' => $job->id, 'indicator_slug' => 'b', 'indicator_name' => 'B', 'status' => 'pending']);

        $this->assertTrue($skippedResult->isSkipped());
        $this->assertFalse($pendingResult->isSkipped());
    }

    // =========================================================================
    // 9. STATUS TRANSITION METHODS
    // =========================================================================

    /** @test */
    public function mark_as_running_sets_status_and_started_at(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'status' => 'pending',
        ]);

        $result->markAsRunning();

        $this->assertEquals('running', $result->status);
        $this->assertNotNull($result->started_at);
    }

    /** @test */
    public function mark_as_completed_sets_status_completed_at_and_score(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'status' => 'running',
            'started_at' => now()->subSeconds(3),
        ]);

        $result->markAsCompleted(85.00, 100.00, ['key' => 'value']);

        $this->assertEquals('completed', $result->status);
        $this->assertNotNull($result->completed_at);
        $this->assertEquals(85.00, $result->score);
        $this->assertEquals(100.00, $result->max_score);
        $this->assertEquals(['key' => 'value'], $result->data);
    }

    /** @test */
    public function mark_as_failed_sets_status_and_error_message(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $result->markAsFailed('Connection timeout');

        $this->assertEquals('failed', $result->status);
        $this->assertEquals('Connection timeout', $result->error_message);
    }

    // =========================================================================
    // 10. EDGE CASE TESTS
    // =========================================================================

    /** @test */
    public function large_json_data_stored_correctly(): void
    {
        $job = $this->createJob();

        $largeData = [];
        for ($i = 0; $i < 100; $i++) {
            $largeData["key_$i"] = str_repeat('x', 100);
        }

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'data' => $largeData,
        ]);

        $this->assertCount(100, $result->data);
    }

    /** @test */
    public function empty_recommendations_array(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'recommendations' => [],
        ]);

        $this->assertEquals([], $result->recommendations);
    }

    /** @test */
    public function very_long_processing_times_logged(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'processing_time_ms' => 300000, // 5 minutes
        ]);

        $this->assertEquals(300000, $result->processing_time_ms);
    }

    // =========================================================================
    // 11. SECURITY TESTS
    // =========================================================================

    /** @test */
    public function indicator_slug_with_sql_characters_safe(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => "test'; DROP TABLE users; --",
            'indicator_name' => 'Test',
        ]);

        $this->assertStringContainsString("DROP TABLE", $result->indicator_slug);
        $this->assertDatabaseHas('users', ['id' => $job->user_id]);
    }

    /** @test */
    public function indicator_name_with_script_tags_safe(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => '<script>alert("xss")</script>Meta Tags',
        ]);

        $this->assertStringContainsString('<script>', $result->indicator_name);
    }

    /** @test */
    public function error_message_with_html_safe(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'error_message' => '<b>Error</b>: <script>alert(1)</script>',
        ]);

        $this->assertStringContainsString('<b>', $result->error_message);
        $this->assertStringContainsString('<script>', $result->error_message);
    }

    /** @test */
    public function recommendations_with_html_safe(): void
    {
        $job = $this->createJob();

        $result = AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'test',
            'indicator_name' => 'Test',
            'recommendations' => [
                ['message' => '<script>alert("xss")</script>Fix this'],
            ],
        ]);

        $this->assertStringContainsString('<script>', $result->recommendations[0]['message']);
    }
}
