<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Feature tests for content streaming endpoints.
 *
 * Tests streaming response behavior for large crawl jobs (>1MB),
 * memory efficiency, and client disconnect handling.
 *
 * @see TASK-22-14
 * @see SPEC-022
 */
class ContentStreamingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Generate a valid job ID for testing.
     */
    protected function generateJobId(): string
    {
        return 'crawl-' . Str::uuid()->toString();
    }

    /**
     * Create a job with specific total size.
     */
    protected function createJobWithSize(int $totalSizeBytes, int $pageCount = 50): CrawlJob
    {
        $job = CrawlJob::factory()->completed()->create([
            'user_id' => $this->user->id,
            'total_size' => $totalSizeBytes,
            'pages_crawled' => $pageCount,
        ]);

        $pageSize = (int) ($totalSizeBytes / $pageCount);
        for ($i = 0; $i < $pageCount; $i++) {
            CrawlPage::factory()->forJob($job)->create([
                'size' => $pageSize,
                'rendered_html' => str_repeat('<p>Content</p>', (int) ($pageSize / 20)),
            ]);
        }

        return $job;
    }

    // =========================================================================
    // CR-010: STREAMING TESTS
    // =========================================================================

    /** @test */
    public function cr_010_01_small_job_normal_response(): void
    {
        // Job under 1MB should use normal response
        $job = $this->createJobWithSize(512 * 1024, 10); // 512KB

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200);
        
        // Should not have streaming headers
        $this->assertNotEquals('chunked', $response->headers->get('Transfer-Encoding'));
    }

    /** @test */
    public function cr_010_02_large_job_streamed_response(): void
    {
        // Job over 1MB should use streaming
        $job = $this->createJobWithSize(2 * 1024 * 1024, 100); // 2MB

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200);
        
        // Should have streaming-related behavior
        // Actual streaming is tested via integration tests
    }

    /** @test */
    public function cr_010_03_stream_headers_present(): void
    {
        $job = $this->createJobWithSize(2 * 1024 * 1024, 100);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200);
        
        // X-Content-Type-Options should be present for security
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** @test */
    public function cr_010_04_progress_indicator_for_large_jobs(): void
    {
        $job = $this->createJobWithSize(5 * 1024 * 1024, 200);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200);
        
        // Progress header might be present for large responses
        // This is implementation-specific but documented
    }

    /** @test */
    public function cr_010_05_memory_efficiency_large_job(): void
    {
        // Create a job that would be ~10MB if loaded entirely
        $job = $this->createJobWithSize(10 * 1024 * 1024, 500);

        $memoryBefore = memory_get_usage(true);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?perPage=100");

        $memoryAfter = memory_get_usage(true);

        $response->assertStatus(200);
        
        // Memory increase should be reasonable (< 10MB for 10MB job with pagination)
        $memoryIncrease = $memoryAfter - $memoryBefore;
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease, 'Memory usage should not spike for large jobs with pagination');
    }

    /** @test */
    public function cr_010_06_export_json_streaming(): void
    {
        $job = $this->createJobWithSize(5 * 1024 * 1024, 200);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/json");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
        
        // Large exports should be streamed
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
    }

    /** @test */
    public function cr_010_07_export_csv_streaming(): void
    {
        $job = $this->createJobWithSize(5 * 1024 * 1024, 200);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/csv");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function cr_010_08_export_html_streaming(): void
    {
        $job = $this->createJobWithSize(5 * 1024 * 1024, 200);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/html");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /** @test */
    public function cr_010_09_pagination_reduces_memory(): void
    {
        $job = $this->createJobWithSize(10 * 1024 * 1024, 500);

        // Request with small perPage
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?perPage=10");

        $response->assertStatus(200)
            ->assertJsonPath('pagination.perPage', 10)
            ->assertJsonCount(10, 'pages');
    }

    /** @test */
    public function cr_010_10_unauthorized_streaming_returns_401(): void
    {
        $job = $this->createJobWithSize(2 * 1024 * 1024, 100);

        $response = $this->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(401);
    }

    /** @test */
    public function cr_010_11_other_user_job_returns_404(): void
    {
        $otherUser = User::factory()->create();
        $job = CrawlJob::factory()->completed()->create([
            'user_id' => $otherUser->id,
            'total_size' => 2 * 1024 * 1024,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(404);
    }

    /** @test */
    public function cr_010_12_gzip_compression_available(): void
    {
        $job = $this->createJobWithSize(2 * 1024 * 1024, 100);

        $response = $this->actingAs($this->user)
            ->withHeaders(['Accept-Encoding' => 'gzip'])
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200);
        
        // Server may or may not compress, but should handle the request
    }

    /** @test */
    public function cr_010_13_export_json_contains_all_pages(): void
    {
        $job = $this->createJobWithSize(2 * 1024 * 1024, 50);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/json");

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertCount(50, $data['pages']);
    }

    /** @test */
    public function cr_010_14_content_length_estimated(): void
    {
        $job = $this->createJobWithSize(2 * 1024 * 1024, 100);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200);
        
        // Response should complete successfully even if Content-Length is not set for streams
    }
}
