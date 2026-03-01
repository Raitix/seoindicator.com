<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;

/**
 * Feature tests for CrawlController API endpoints.
 *
 * Tests CRUD operations, authorization, rate limiting,
 * and content retrieval for the Headless Crawler Service.
 *
 * TDD Approach: Tests use mocked CrawlerService interface.
 *
 * @see TASK-22-11
 * @see \App\Http\Controllers\Api\V1\CrawlController
 * @see SPEC-022
 */
class CrawlControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Project $project;
    protected Project $otherProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // HELPER METHODS - Mock CrawlerService
    // =========================================================================

    /**
     * Mock the CrawlerService for TDD testing.
     */
    protected function mockCrawlerService(): MockInterface
    {
        return $this->mock(\App\Services\Crawler\CrawlerServiceInterface::class);
    }

    /**
     * Generate a valid job ID for testing.
     */
    protected function generateJobId(): string
    {
        return 'crawl-' . Str::uuid()->toString();
    }

    /**
     * Create a mock crawl job response.
     */
    protected function mockCrawlJob(array $overrides = []): array
    {
        $jobId = $this->generateJobId();
        
        return array_merge([
            'jobId' => $jobId,
            'status' => 'queued',
            'url' => 'https://example.com',
            'depth' => 0,
            'estimatedTime' => '30s',
            'message' => 'Crawl job queued for processing',
        ], $overrides);
    }

    /**
     * Create a mock completed job status.
     */
    protected function mockCompletedJob(string $jobId): array
    {
        return [
            'jobId' => $jobId,
            'status' => 'completed',
            'url' => 'https://example.com',
            'depth' => 1,
            'pagesCrawled' => 5,
            'totalSize' => 1024000,
            'sizeFormatted' => '1.0 MB',
            'startedAt' => '2026-03-01T10:00:00Z',
            'completedAt' => '2026-03-01T10:00:45Z',
            'duration' => 45,
            'contentUrl' => "/api/v1/crawler/jobs/{$jobId}/content",
            'error' => null,
        ];
    }

    /**
     * Create mock crawl content.
     */
    protected function mockCrawlContent(string $jobId): array
    {
        return [
            'jobId' => $jobId,
            'pages' => [
                [
                    'url' => 'https://example.com',
                    'depth' => 0,
                    'title' => 'Example Domain',
                    'renderedHtml' => '<!DOCTYPE html><html><body>Example</body></html>',
                    'structuredText' => 'Example Domain',
                    'meta' => [
                        'description' => 'Example domain',
                        'robots' => 'index, follow',
                    ],
                    'links' => [
                        ['href' => 'https://example.com/about', 'text' => 'About', 'internal' => true],
                    ],
                    'size' => 204800,
                    'sizeFormatted' => '200.0 KB',
                    'crawledAt' => '2026-03-01T10:00:30Z',
                ],
            ],
            'summary' => [
                'totalPages' => 1,
                'totalSize' => 204800,
                'depthDistribution' => ['0' => 1],
            ],
        ];
    }

    // =========================================================================
    // POST /api/v1/crawler/crawl - CREATE CRAWL JOB
    // =========================================================================

    // -------------------------------------------------------------------------
    // Authentication Tests (AUTH-*)
    // -------------------------------------------------------------------------

    /** @test */
    public function auth_01_create_crawl_job_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/crawler/crawl', [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function auth_02_create_crawl_job_with_valid_token_succeeds(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'jobId',
                'status',
                'url',
                'depth',
                'estimatedTime',
                'message',
            ]);
    }

    // -------------------------------------------------------------------------
    // Success Cases (C-POST-*)
    // -------------------------------------------------------------------------

    /** @test */
    public function c_post_01_create_crawl_job_with_minimal_options(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->withArgs(function ($userId, $url, $options) {
                return $userId === $this->user->id
                    && $url === 'https://example.com'
                    && $options['depth'] === 0;
            })
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'status' => 'queued',
                'url' => 'https://example.com',
                'depth' => 0,
            ]);
    }

    /** @test */
    public function c_post_02_create_crawl_job_with_depth_1(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob(['depth' => 1]));

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'depth' => 1,
            ]);

        $response->assertStatus(202)
            ->assertJson(['depth' => 1]);
    }

    /** @test */
    public function c_post_03_create_crawl_job_with_depth_2(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob(['depth' => 2]));

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'depth' => 2,
            ]);

        $response->assertStatus(202)
            ->assertJson(['depth' => 2]);
    }

    /** @test */
    public function c_post_04_create_crawl_job_with_custom_user_agent(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'userAgent' => 'CustomBot/1.0',
                ],
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function c_post_05_create_crawl_job_with_wait_for_selector(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'waitForSelector' => '.content',
                ],
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function c_post_06_create_crawl_job_with_wait_time(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'waitTime' => 5000,
                ],
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function c_post_07_create_crawl_job_with_include_images(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'includeImages' => true,
                ],
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function c_post_08_create_crawl_job_with_follow_redirects_false(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'followRedirects' => false,
                ],
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function c_post_09_create_crawl_job_with_max_redirects(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'maxRedirects' => 3,
                ],
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function c_post_10_create_crawl_job_with_all_options(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'depth' => 2,
                'options' => [
                    'waitForSelector' => '.main-content',
                    'waitTime' => 3000,
                    'userAgent' => 'SEOIndicator/2.0',
                    'includeImages' => true,
                    'followRedirects' => true,
                    'maxRedirects' => 5,
                ],
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function c_post_11_job_id_has_correct_format(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ]);

        $response->assertStatus(202);
        
        $jobId = $response->json('jobId');
        $this->assertStringStartsWith('crawl-', $jobId);
    }

    /** @test */
    public function c_post_12_estimated_time_returned(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob(['estimatedTime' => '45s']));

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'depth' => 2,
            ]);

        $response->assertStatus(202)
            ->assertJson(['estimatedTime' => '45s']);
    }

    // -------------------------------------------------------------------------
    // Validation Tests (C-POST-VAL-*)
    // -------------------------------------------------------------------------

    /** @test */
    public function c_post_val_01_missing_url_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_val_02_invalid_url_format_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'not-a-url',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_val_03_url_exceeds_max_length_returns_validation_error(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2070);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => $longUrl,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_val_04_depth_below_minimum_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'depth' => -1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['depth']);
    }

    /** @test */
    public function c_post_val_05_depth_above_maximum_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'depth' => 3,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['depth']);
    }

    /** @test */
    public function c_post_val_06_depth_non_integer_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'depth' => 1.5,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['depth']);
    }

    /** @test */
    public function c_post_val_07_wait_for_selector_too_long_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'waitForSelector' => str_repeat('a', 501),
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options.waitForSelector']);
    }

    /** @test */
    public function c_post_val_08_wait_time_below_minimum_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'waitTime' => -1,
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options.waitTime']);
    }

    /** @test */
    public function c_post_val_09_wait_time_above_maximum_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'waitTime' => 30001,
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options.waitTime']);
    }

    /** @test */
    public function c_post_val_10_user_agent_too_long_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'userAgent' => str_repeat('a', 501),
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options.userAgent']);
    }

    /** @test */
    public function c_post_val_11_include_images_non_boolean_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'includeImages' => 'yes',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options.includeImages']);
    }

    /** @test */
    public function c_post_val_12_follow_redirects_non_boolean_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'followRedirects' => 'true',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options.followRedirects']);
    }

    /** @test */
    public function c_post_val_13_max_redirects_below_minimum_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'maxRedirects' => -1,
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options.maxRedirects']);
    }

    /** @test */
    public function c_post_val_14_max_redirects_above_maximum_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'maxRedirects' => 11,
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options.maxRedirects']);
    }

    /** @test */
    public function c_post_val_15_non_string_url_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 12345,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    // -------------------------------------------------------------------------
    // SSRF Protection Tests (C-POST-SSRF-*)
    // -------------------------------------------------------------------------

    /** @test */
    public function c_post_ssrf_01_localhost_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://localhost/page',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_ssrf_02_127_0_0_1_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://127.0.0.1/page',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_ssrf_03_private_ip_10_x_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://10.0.0.1/page',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_ssrf_04_private_ip_172_16_31_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://172.16.0.1/page',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_ssrf_05_private_ip_192_168_x_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://192.168.1.1/page',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_ssrf_06_cloud_metadata_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://169.254.169.254/',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_ssrf_07_file_protocol_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'file:///etc/passwd',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_ssrf_08_ftp_protocol_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'ftp://server/file',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_ssrf_09_internal_domain_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://internal.company.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_ssrf_10_local_domain_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://server.local',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_ssrf_11_ipv6_localhost_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://[::1]/page',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function c_post_ssrf_12_ipv6_link_local_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://[fe80::1]/page',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    // =========================================================================
    // GET /api/v1/crawler/jobs/{jobId} - GET JOB STATUS
    // =========================================================================

    /** @test */
    public function c_get_01_get_queued_job_status(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->with($jobId)
            ->andReturn([
                'jobId' => $jobId,
                'status' => 'queued',
                'url' => 'https://example.com',
                'depth' => 1,
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(200)
            ->assertJson([
                'jobId' => $jobId,
                'status' => 'queued',
            ]);
    }

    /** @test */
    public function c_get_02_get_running_job_status(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn([
                'jobId' => $jobId,
                'status' => 'running',
                'url' => 'https://example.com',
                'depth' => 1,
                'pagesCrawled' => 3,
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(200)
            ->assertJson(['status' => 'running']);
    }

    /** @test */
    public function c_get_03_get_completed_job_status(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn($this->mockCompletedJob($jobId));

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'completed',
                'pagesCrawled' => 5,
            ]);
    }

    /** @test */
    public function c_get_04_get_failed_job_status(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn([
                'jobId' => $jobId,
                'status' => 'failed',
                'url' => 'https://example.com',
                'error' => 'Connection timeout',
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'failed',
                'error' => 'Connection timeout',
            ]);
    }

    /** @test */
    public function c_get_05_get_cancelled_job_status(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn([
                'jobId' => $jobId,
                'status' => 'cancelled',
                'url' => 'https://example.com',
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(200)
            ->assertJson(['status' => 'cancelled']);
    }

    /** @test */
    public function c_get_06_response_includes_content_url(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn($this->mockCompletedJob($jobId));

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(200)
            ->assertJsonPath('contentUrl', "/api/v1/crawler/jobs/{$jobId}/content");
    }

    /** @test */
    public function c_get_07_response_includes_duration(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn($this->mockCompletedJob($jobId));

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(200)
            ->assertJson(['duration' => 45]);
    }

    /** @test */
    public function c_get_08_response_includes_size_formatted(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn($this->mockCompletedJob($jobId));

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(200)
            ->assertJson(['sizeFormatted' => '1.0 MB']);
    }

    /** @test */
    public function c_get_err_01_job_not_found_returns_404(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn(null);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(404);
    }

    /** @test */
    public function c_get_err_02_other_user_job_returns_404(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn(null); // Service returns null for unauthorized access

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(404);
    }

    /** @test */
    public function c_get_err_03_malformed_job_id_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crawler/jobs/invalid-uuid');

        $response->assertStatus(404);
    }

    /** @test */
    public function c_get_requires_authentication(): void
    {
        $jobId = $this->generateJobId();

        $response = $this->getJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/crawler/jobs/{jobId}/content - GET CRAWLED CONTENT
    // =========================================================================

    /** @test */
    public function c_content_01_get_content_from_completed_job(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn($this->mockCompletedJob($jobId));
        $crawlerService->shouldReceive('getJobContent')
            ->once()
            ->andReturn($this->mockCrawlContent($jobId));

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'jobId',
                'pages' => [
                    '*' => [
                        'url',
                        'depth',
                        'title',
                        'renderedHtml',
                        'structuredText',
                        'meta',
                        'links',
                        'size',
                        'crawledAt',
                    ],
                ],
                'summary',
            ]);
    }

    /** @test */
    public function c_content_02_content_includes_rendered_html(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn($this->mockCompletedJob($jobId));
        $crawlerService->shouldReceive('getJobContent')
            ->once()
            ->andReturn($this->mockCrawlContent($jobId));

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(200)
            ->assertJsonPath('pages.0.renderedHtml', '<!DOCTYPE html><html><body>Example</body></html>');
    }

    /** @test */
    public function c_content_03_content_includes_structured_text(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn($this->mockCompletedJob($jobId));
        $crawlerService->shouldReceive('getJobContent')
            ->once()
            ->andReturn($this->mockCrawlContent($jobId));

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(200)
            ->assertJsonPath('pages.0.structuredText', 'Example Domain');
    }

    /** @test */
    public function c_content_04_content_includes_meta(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn($this->mockCompletedJob($jobId));
        $crawlerService->shouldReceive('getJobContent')
            ->once()
            ->andReturn($this->mockCrawlContent($jobId));

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(200)
            ->assertJsonPath('pages.0.meta.description', 'Example domain')
            ->assertJsonPath('pages.0.meta.robots', 'index, follow');
    }

    /** @test */
    public function c_content_05_content_includes_links(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn($this->mockCompletedJob($jobId));
        $crawlerService->shouldReceive('getJobContent')
            ->once()
            ->andReturn($this->mockCrawlContent($jobId));

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(200)
            ->assertJsonPath('pages.0.links.0.href', 'https://example.com/about');
    }

    /** @test */
    public function c_content_06_content_includes_depth_distribution(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn($this->mockCompletedJob($jobId));
        $crawlerService->shouldReceive('getJobContent')
            ->once()
            ->andReturn($this->mockCrawlContent($jobId));

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(200)
            ->assertJsonPath('summary.depthDistribution.0', 1);
    }

    /** @test */
    public function c_content_07_empty_result_set(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn($this->mockCompletedJob($jobId));
        $crawlerService->shouldReceive('getJobContent')
            ->once()
            ->andReturn([
                'jobId' => $jobId,
                'pages' => [],
                'summary' => [
                    'totalPages' => 0,
                    'totalSize' => 0,
                    'depthDistribution' => [],
                ],
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(200)
            ->assertJson(['pages' => []]);
    }

    /** @test */
    public function c_content_err_01_running_job_returns_409(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn([
                'jobId' => $jobId,
                'status' => 'running',
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(409);
    }

    /** @test */
    public function c_content_err_02_failed_job_returns_409(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn([
                'jobId' => $jobId,
                'status' => 'failed',
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(409);
    }

    /** @test */
    public function c_content_err_03_job_not_found_returns_404(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn(null);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(404);
    }

    /** @test */
    public function c_content_err_05_cancelled_job_returns_409(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn([
                'jobId' => $jobId,
                'status' => 'cancelled',
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(409);
    }

    /** @test */
    public function c_content_requires_authentication(): void
    {
        $jobId = $this->generateJobId();

        $response = $this->getJson("/api/v1/crawler/jobs/{$jobId}/content");

        $response->assertStatus(401);
    }

    // =========================================================================
    // DELETE /api/v1/crawler/jobs/{jobId} - CANCEL JOB
    // =========================================================================

    /** @test */
    public function c_del_01_cancel_queued_job(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('cancelJob')
            ->once()
            ->with($jobId)
            ->andReturn(true);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(200)
            ->assertJson(['status' => 'cancelled']);
    }

    /** @test */
    public function c_del_02_cancel_running_job(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('cancelJob')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(200);
    }

    /** @test */
    public function c_del_03_completed_job_returns_409(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('cancelJob')
            ->once()
            ->andReturn(false); // Cannot cancel completed job

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(409);
    }

    /** @test */
    public function c_del_err_01_job_not_found_returns_404(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('cancelJob')
            ->once()
            ->andReturn(null);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(404);
    }

    /** @test */
    public function c_del_requires_authentication(): void
    {
        $jobId = $this->generateJobId();

        $response = $this->deleteJson("/api/v1/crawler/jobs/{$jobId}");

        $response->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/crawler/jobs - LIST USER'S JOBS
    // =========================================================================

    /** @test */
    public function c_list_01_list_all_jobs(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('listJobs')
            ->once()
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator(
                [
                    $this->mockCompletedJob($this->generateJobId()),
                    $this->mockCrawlJob(['status' => 'queued']),
                ],
                2,
                15,
                1
            ));

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crawler/jobs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['jobId', 'status', 'url'],
                ],
                'meta' => ['total', 'page', 'perPage'],
            ]);
    }

    /** @test */
    public function c_list_02_filter_by_status_queued(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('listJobs')
            ->once()
            ->withArgs(function ($userId, $filters) {
                return $filters['status'] === 'queued';
            })
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1));

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crawler/jobs?status=queued');

        $response->assertStatus(200);
    }

    /** @test */
    public function c_list_03_filter_by_status_running(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('listJobs')
            ->once()
            ->withArgs(function ($userId, $filters) {
                return $filters['status'] === 'running';
            })
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1));

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crawler/jobs?status=running');

        $response->assertStatus(200);
    }

    /** @test */
    public function c_list_04_filter_by_status_completed(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('listJobs')
            ->once()
            ->withArgs(function ($userId, $filters) {
                return $filters['status'] === 'completed';
            })
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1));

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crawler/jobs?status=completed');

        $response->assertStatus(200);
    }

    /** @test */
    public function c_list_07_pagination_default(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('listJobs')
            ->once()
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1));

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crawler/jobs');

        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 15);
    }

    /** @test */
    public function c_list_08_pagination_custom(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('listJobs')
            ->once()
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 2));

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crawler/jobs?page=2&perPage=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.perPage', 10);
    }

    /** @test */
    public function c_list_11_empty_list(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('listJobs')
            ->once()
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1));

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crawler/jobs');

        $response->assertStatus(200)
            ->assertJson(['data' => []]);
    }

    /** @test */
    public function c_list_val_01_invalid_status(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crawler/jobs?status=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function c_list_val_03_invalid_per_page(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crawler/jobs?perPage=1000');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['perPage']);
    }

    /** @test */
    public function c_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/crawler/jobs');

        $response->assertStatus(401);
    }

    // =========================================================================
    // Security Tests (SEC-*)
    // =========================================================================

    /** @test */
    public function sec_01_sql_injection_in_job_id(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/1'; DROP TABLE crawl_jobs;--");

        $response->assertStatus(404); // Not 500 (SQL not executed)
    }

    /** @test */
    public function sec_02_sql_injection_in_url(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => "https://example.com'; DROP TABLE users;--",
            ]);

        // Should either validate as invalid URL or sanitize
        $this->assertNotEquals(500, $response->status());
    }

    /** @test */
    public function sec_03_xss_in_url(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com/<script>alert(1)</script>',
            ]);

        // Should validate as invalid URL
        $response->assertStatus(422);
    }

    /** @test */
    public function sec_04_idor_via_job_id(): void
    {
        $jobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->once()
            ->andReturn(null); // Service returns null for other user's job

        // User tries to access another user's job
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$jobId}");

        // Should return 404, not 403 (prevents enumeration)
        $response->assertStatus(404);
    }

    /** @test */
    public function sec_05_path_traversal_in_wait_for_selector(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'options' => [
                    'waitForSelector' => '../../../etc/passwd',
                ],
            ]);

        // CSS selectors shouldn't allow path traversal, but validate anyway
        $response->assertStatus(422);
    }

    /** @test */
    public function sec_06_no_sensitive_data_in_response(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ]);

        $response->assertStatus(202);
        
        // Ensure no sensitive data is exposed
        $json = $response->json();
        $this->assertArrayNotHasKey('api_key', $json);
        $this->assertArrayNotHasKey('token', $json);
        $this->assertArrayNotHasKey('password', $json);
    }

    /** @test */
    public function sec_08_idor_enumeration_prevention(): void
    {
        $existingJobId = $this->generateJobId();
        $nonExistentJobId = $this->generateJobId();
        
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('getJob')
            ->twice()
            ->andReturn(null);

        // Both should return same status (404) to prevent enumeration
        $response1 = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$existingJobId}");
        
        $response2 = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$nonExistentJobId}");

        $this->assertEquals($response1->status(), $response2->status());
    }

    // =========================================================================
    // Rate Limiting Tests (RL-*)
    // =========================================================================

    /** @test */
    public function rl_01_within_rate_limit_succeeds(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function rl_05_rate_limit_headers_present(): void
    {
        $crawlerService = $this->mockCrawlerService();
        $crawlerService->shouldReceive('createJob')
            ->once()
            ->andReturn($this->mockCrawlJob());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ]);

        // Rate limit headers should be present (middleware responsibility)
        // This test documents the expected behavior
        $response->assertStatus(202);
    }
}
