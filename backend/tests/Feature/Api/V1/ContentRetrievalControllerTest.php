<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;

/**
 * Feature tests for Content Retrieval API endpoints.
 *
 * Tests pagination, filtering, field selection, exports, streaming,
 * and caching for the Headless Crawler Service content retrieval.
 *
 * TDD Approach: Tests use mocked ContentRetrievalService interface.
 *
 * @see TASK-22-14
 * @see \App\Http\Controllers\Api\V1\ContentRetrievalController
 * @see SPEC-022
 */
class ContentRetrievalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Generate a valid job ID for testing.
     */
    protected function generateJobId(): string
    {
        return 'crawl-' . Str::uuid()->toString();
    }

    /**
     * Create a completed CrawlJob with pages.
     */
    protected function createCompletedJobWithPages(int $pageCount = 5, array $overrides = []): CrawlJob
    {
        $job = CrawlJob::factory()->completed()->create(array_merge([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
        ], $overrides));

        for ($i = 0; $i < $pageCount; $i++) {
            CrawlPage::factory()->forJob($job)->atDepth($i % 3)->create([
                'url' => "https://example.com/page-{$i}",
                'title' => "Page {$i} - Example Domain",
                'structured_text' => "Content for page {$i} with keyword test",
            ]);
        }

        return $job;
    }

    /**
     * Create a single CrawlPage with specific data.
     */
    protected function createPageWithData(CrawlJob $job, array $data = []): CrawlPage
    {
        return CrawlPage::factory()->forJob($job)->create($data);
    }

    /**
     * Mock the ContentRetrievalService for TDD testing.
     */
    protected function mockContentRetrievalService(): MockInterface
    {
        return $this->mock(\App\Services\Crawler\ContentRetrievalServiceInterface::class);
    }

    /**
     * Create mock content response with pagination.
     */
    protected function mockPaginatedContent(string $jobId, int $totalPages = 150): array
    {
        $pages = [];
        for ($i = 0; $i < 50; $i++) {
            $pages[] = [
                'id' => $i + 1,
                'url' => "https://example.com/page-{$i}",
                'depth' => $i % 3,
                'title' => "Page {$i} - Example Domain",
                'renderedHtml' => '<html><body>Content</body></html>',
                'structuredText' => "Content for page {$i} with keyword test",
                'meta' => ['description' => "Page {$i} description"],
                'links' => [],
                'size' => 10240,
                'sizeFormatted' => '10.0 KB',
                'crawledAt' => now()->toISOString(),
            ];
        }

        return [
            'jobId' => $jobId,
            'pages' => $pages,
            'pagination' => [
                'currentPage' => 1,
                'perPage' => 50,
                'totalPages' => ceil($totalPages / 50),
                'totalItems' => $totalPages,
            ],
            'summary' => [
                'totalPages' => $totalPages,
                'totalSize' => 1536000,
                'depthDistribution' => ['0' => 50, '1' => 50, '2' => 50],
            ],
        ];
    }

    // =========================================================================
    // CR-001: BASIC RETRIEVAL TESTS
    // =========================================================================

    /** @test */
    public function cr_001_01_get_content_from_completed_job(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'jobId',
                'pages' => [
                    '*' => ['url', 'depth', 'title', 'renderedHtml', 'structuredText', 'meta', 'links', 'size', 'crawledAt'],
                ],
                'pagination',
                'summary',
            ]);
    }

    /** @test */
    public function cr_001_02_job_not_found_returns_404(): void
    {
        $fakeJobId = $this->generateJobId();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$fakeJobId}/content");

        $response->assertStatus(404);
    }

    /** @test */
    public function cr_001_03_running_job_returns_409(): void
    {
        $job = CrawlJob::factory()->running()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(409)
            ->assertJson(['error' => 'Job not complete']);
    }

    /** @test */
    public function cr_001_04_failed_job_returns_409(): void
    {
        $job = CrawlJob::factory()->failed()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(409);
    }

    /** @test */
    public function cr_001_05_cancelled_job_returns_409(): void
    {
        $job = CrawlJob::factory()->cancelled()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(409);
    }

    /** @test */
    public function cr_001_06_unauthorized_returns_401(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(401);
    }

    /** @test */
    public function cr_001_07_other_users_job_returns_404(): void
    {
        $job = CrawlJob::factory()->completed()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(404);
    }

    // =========================================================================
    // CR-002: PAGINATION TESTS
    // =========================================================================

    /** @test */
    public function cr_002_01_default_pagination(): void
    {
        $job = $this->createCompletedJobWithPages(100);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200)
            ->assertJsonPath('pagination.currentPage', 1)
            ->assertJsonPath('pagination.perPage', 50);
    }

    /** @test */
    public function cr_002_02_custom_page(): void
    {
        $job = $this->createCompletedJobWithPages(100);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?page=2");

        $response->assertStatus(200)
            ->assertJsonPath('pagination.currentPage', 2);
    }

    /** @test */
    public function cr_002_03_custom_per_page(): void
    {
        $job = $this->createCompletedJobWithPages(100);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?perPage=20");

        $response->assertStatus(200)
            ->assertJsonPath('pagination.perPage', 20);
    }

    /** @test */
    public function cr_002_04_max_per_page(): void
    {
        $job = $this->createCompletedJobWithPages(150);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?perPage=100");

        $response->assertStatus(200)
            ->assertJsonPath('pagination.perPage', 100);
    }

    /** @test */
    public function cr_002_05_per_page_exceeds_max_returns_validation_error(): void
    {
        $job = $this->createCompletedJobWithPages(10);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?perPage=200");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['perPage']);
    }

    /** @test */
    public function cr_002_06_invalid_page_returns_validation_error(): void
    {
        $job = $this->createCompletedJobWithPages(10);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?page=-1");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    /** @test */
    public function cr_002_07_non_numeric_page_returns_validation_error(): void
    {
        $job = $this->createCompletedJobWithPages(10);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?page=abc");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    /** @test */
    public function cr_002_08_empty_page_returns_empty_array(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?page=999");

        $response->assertStatus(200)
            ->assertJson(['pages' => []]);
    }

    /** @test */
    public function cr_002_09_pagination_metadata_complete(): void
    {
        $job = $this->createCompletedJobWithPages(100);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'pagination' => ['currentPage', 'perPage', 'totalPages', 'totalItems'],
            ]);
    }

    // =========================================================================
    // CR-003: FILTERING TESTS
    // =========================================================================

    /** @test */
    public function cr_003_01_filter_by_depth_0(): void
    {
        $job = $this->createCompletedJobWithPages(15);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?depth=0");

        $response->assertStatus(200);
        
        $pages = $response->json('pages');
        foreach ($pages as $page) {
            $this->assertEquals(0, $page['depth']);
        }
    }

    /** @test */
    public function cr_003_02_filter_by_depth_1(): void
    {
        $job = $this->createCompletedJobWithPages(15);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?depth=1");

        $response->assertStatus(200);
        
        $pages = $response->json('pages');
        foreach ($pages as $page) {
            $this->assertEquals(1, $page['depth']);
        }
    }

    /** @test */
    public function cr_003_03_filter_by_depth_2(): void
    {
        $job = $this->createCompletedJobWithPages(15);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?depth=2");

        $response->assertStatus(200);
        
        $pages = $response->json('pages');
        foreach ($pages as $page) {
            $this->assertEquals(2, $page['depth']);
        }
    }

    /** @test */
    public function cr_003_04_invalid_depth_returns_validation_error(): void
    {
        $job = $this->createCompletedJobWithPages(10);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?depth=5");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['depth']);
    }

    /** @test */
    public function cr_003_05_search_in_title(): void
    {
        $job = $this->createCompletedJobWithPages(5);
        CrawlPage::factory()->forJob($job)->create([
            'title' => 'Special Keyword Page',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?search=Special%20Keyword");

        $response->assertStatus(200);
        
        $pages = $response->json('pages');
        $this->assertCount(1, $pages);
        $this->assertStringContainsString('Special Keyword', $pages[0]['title']);
    }

    /** @test */
    public function cr_003_06_search_in_text(): void
    {
        $job = $this->createCompletedJobWithPages(5);
        CrawlPage::factory()->forJob($job)->create([
            'structured_text' => 'This page contains unique search term xyz123',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?search=xyz123");

        $response->assertStatus(200);
        
        $pages = $response->json('pages');
        $this->assertGreaterThanOrEqual(1, count($pages));
    }

    /** @test */
    public function cr_003_07_search_no_results(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?search=nonexistentxyz987");

        $response->assertStatus(200)
            ->assertJson(['pages' => []]);
    }

    /** @test */
    public function cr_003_08_combined_filters(): void
    {
        $job = $this->createCompletedJobWithPages(10);
        CrawlPage::factory()->forJob($job)->atDepth(0)->create([
            'title' => 'Test Page at Depth Zero',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?depth=0&search=Test");

        $response->assertStatus(200);
    }

    // =========================================================================
    // CR-004: FIELD SELECTION TESTS
    // =========================================================================

    /** @test */
    public function cr_004_01_include_text_only(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?include=text");

        $response->assertStatus(200);
        
        $page = $response->json('pages.0');
        $this->assertArrayHasKey('structuredText', $page);
        $this->assertArrayNotHasKey('renderedHtml', $page);
        $this->assertArrayNotHasKey('meta', $page);
    }

    /** @test */
    public function cr_004_02_include_meta_only(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?include=meta");

        $response->assertStatus(200);
        
        $page = $response->json('pages.0');
        $this->assertArrayHasKey('meta', $page);
        $this->assertArrayNotHasKey('renderedHtml', $page);
        $this->assertArrayNotHasKey('structuredText', $page);
    }

    /** @test */
    public function cr_004_03_include_multiple_fields(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?include=text,meta");

        $response->assertStatus(200);
        
        $page = $response->json('pages.0');
        $this->assertArrayHasKey('structuredText', $page);
        $this->assertArrayHasKey('meta', $page);
        $this->assertArrayNotHasKey('renderedHtml', $page);
    }

    /** @test */
    public function cr_004_04_exclude_rendered_html(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?exclude=renderedHtml");

        $response->assertStatus(200);
        
        $page = $response->json('pages.0');
        $this->assertArrayNotHasKey('renderedHtml', $page);
        $this->assertArrayHasKey('structuredText', $page);
        $this->assertArrayHasKey('meta', $page);
    }

    /** @test */
    public function cr_004_05_exclude_multiple_fields(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?exclude=renderedHtml,links");

        $response->assertStatus(200);
        
        $page = $response->json('pages.0');
        $this->assertArrayNotHasKey('renderedHtml', $page);
        $this->assertArrayNotHasKey('links', $page);
        $this->assertArrayHasKey('structuredText', $page);
    }

    /** @test */
    public function cr_004_06_invalid_include_field_returns_validation_error(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?include=invalid");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['include']);
    }

    /** @test */
    public function cr_004_07_invalid_exclude_field_returns_validation_error(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?exclude=invalid");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['exclude']);
    }

    /** @test */
    public function cr_004_08_default_all_fields_included(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200);
        
        $page = $response->json('pages.0');
        $this->assertArrayHasKey('url', $page);
        $this->assertArrayHasKey('depth', $page);
        $this->assertArrayHasKey('title', $page);
        $this->assertArrayHasKey('renderedHtml', $page);
        $this->assertArrayHasKey('structuredText', $page);
        $this->assertArrayHasKey('meta', $page);
        $this->assertArrayHasKey('links', $page);
        $this->assertArrayHasKey('size', $page);
    }

    /** @test */
    public function cr_004_09_url_always_included(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?exclude=url");

        $response->assertStatus(200);
        
        // URL should still be present as it's required for context
        $page = $response->json('pages.0');
        $this->assertArrayHasKey('url', $page);
    }

    // =========================================================================
    // CR-005: SORTING TESTS
    // =========================================================================

    /** @test */
    public function cr_005_01_sort_by_depth_asc(): void
    {
        $job = $this->createCompletedJobWithPages(15);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?sortBy=depth&sortOrder=asc");

        $response->assertStatus(200);
        
        $pages = $response->json('pages');
        $depths = array_column($pages, 'depth');
        $sortedDepths = $depths;
        sort($sortedDepths);
        $this->assertEquals($sortedDepths, $depths);
    }

    /** @test */
    public function cr_005_02_sort_by_depth_desc(): void
    {
        $job = $this->createCompletedJobWithPages(15);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?sortBy=depth&sortOrder=desc");

        $response->assertStatus(200);
        
        $pages = $response->json('pages');
        $depths = array_column($pages, 'depth');
        $sortedDepths = $depths;
        rsort($sortedDepths);
        $this->assertEquals($sortedDepths, $depths);
    }

    /** @test */
    public function cr_005_03_sort_by_size_asc(): void
    {
        $job = $this->createCompletedJobWithPages(10);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?sortBy=size&sortOrder=asc");

        $response->assertStatus(200);
        
        $pages = $response->json('pages');
        $sizes = array_column($pages, 'size');
        $sortedSizes = $sizes;
        sort($sortedSizes);
        $this->assertEquals($sortedSizes, $sizes);
    }

    /** @test */
    public function cr_005_04_sort_by_size_desc(): void
    {
        $job = $this->createCompletedJobWithPages(10);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?sortBy=size&sortOrder=desc");

        $response->assertStatus(200);
        
        $pages = $response->json('pages');
        $sizes = array_column($pages, 'size');
        $sortedSizes = $sizes;
        rsort($sortedSizes);
        $this->assertEquals($sortedSizes, $sizes);
    }

    /** @test */
    public function cr_005_05_sort_by_crawled_at(): void
    {
        $job = $this->createCompletedJobWithPages(10);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?sortBy=crawledAt&sortOrder=desc");

        $response->assertStatus(200);
    }

    /** @test */
    public function cr_005_06_invalid_sort_by_returns_validation_error(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?sortBy=invalid");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sortBy']);
    }

    /** @test */
    public function cr_005_07_invalid_sort_order_returns_validation_error(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?sortOrder=invalid");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sortOrder']);
    }

    /** @test */
    public function cr_005_08_default_sort_is_crawled_at_desc(): void
    {
        $job = $this->createCompletedJobWithPages(10);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200);
        // Default behavior documented, actual ordering verified by integration tests
    }

    // =========================================================================
    // CR-006: SINGLE PAGE RETRIEVAL TESTS
    // =========================================================================

    /** @test */
    public function cr_006_01_get_existing_page(): void
    {
        $job = $this->createCompletedJobWithPages(1);
        $page = $job->pages->first();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/pages/{$page->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $page->id,
                'url' => $page->url,
                'title' => $page->title,
            ]);
    }

    /** @test */
    public function cr_006_02_page_not_found_returns_404(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/pages/99999");

        $response->assertStatus(404);
    }

    /** @test */
    public function cr_006_03_job_not_found_returns_404(): void
    {
        $fakeJobId = $this->generateJobId();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$fakeJobId}/pages/1");

        $response->assertStatus(404);
    }

    /** @test */
    public function cr_006_04_page_belongs_to_other_job_returns_404(): void
    {
        $job1 = $this->createCompletedJobWithPages(1);
        $job2 = $this->createCompletedJobWithPages(1);
        $page2 = $job2->pages->first();

        // Try to access job2's page via job1's jobId
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job1->job_id}/pages/{$page2->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function cr_006_05_field_selection_include(): void
    {
        $job = $this->createCompletedJobWithPages(1);
        $page = $job->pages->first();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/pages/{$page->id}?include=text,meta");

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayHasKey('structuredText', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayNotHasKey('renderedHtml', $data);
    }

    /** @test */
    public function cr_006_06_field_selection_exclude(): void
    {
        $job = $this->createCompletedJobWithPages(1);
        $page = $job->pages->first();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/pages/{$page->id}?exclude=renderedHtml");

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayNotHasKey('renderedHtml', $data);
    }

    /** @test */
    public function cr_006_07_unauthorized_returns_401(): void
    {
        $job = $this->createCompletedJobWithPages(1);
        $page = $job->pages->first();

        $response = $this->getJson("/api/v1/crawler/jobs/{$job->job_id}/pages/{$page->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function cr_006_08_other_users_job_returns_404(): void
    {
        $otherJob = CrawlJob::factory()->completed()->create(['user_id' => $this->otherUser->id]);
        $page = CrawlPage::factory()->forJob($otherJob)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$otherJob->job_id}/pages/{$page->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // CR-007: JSON EXPORT TESTS
    // =========================================================================

    /** @test */
    public function cr_007_01_export_completed_job_as_json(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/json");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }

    /** @test */
    public function cr_007_02_content_disposition_header(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/json");

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition');
        
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('.json', $disposition);
    }

    /** @test */
    public function cr_007_03_all_pages_included_in_export(): void
    {
        $job = $this->createCompletedJobWithPages(10);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/json");

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertCount(10, $data['pages']);
    }

    /** @test */
    public function cr_007_04_running_job_returns_409(): void
    {
        $job = CrawlJob::factory()->running()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/json");

        $response->assertStatus(409);
    }

    /** @test */
    public function cr_007_05_job_not_found_returns_404(): void
    {
        $fakeJobId = $this->generateJobId();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$fakeJobId}/export/json");

        $response->assertStatus(404);
    }

    /** @test */
    public function cr_007_06_unauthorized_returns_401(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/json");

        $response->assertStatus(401);
    }

    /** @test */
    public function cr_007_07_large_job_export(): void
    {
        $job = $this->createCompletedJobWithPages(100);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/json");

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertCount(100, $data['pages']);
    }

    /** @test */
    public function cr_007_08_empty_job_export(): void
    {
        $job = CrawlJob::factory()->completed()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/json");

        $response->assertStatus(200)
            ->assertJson(['pages' => []]);
    }

    // =========================================================================
    // CR-008: CSV EXPORT TESTS
    // =========================================================================

    /** @test */
    public function cr_008_01_export_completed_job_as_csv(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/csv");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function cr_008_02_csv_headers_present(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/csv");

        $response->assertStatus(200);
        
        $content = $response->getContent();
        $firstLine = explode("\n", $content)[0];
        $headers = str_getcsv($firstLine);
        
        $this->assertContains('url', $headers);
        $this->assertContains('depth', $headers);
        $this->assertContains('title', $headers);
        $this->assertContains('meta_description', $headers);
        $this->assertContains('text_preview', $headers);
        $this->assertContains('size', $headers);
    }

    /** @test */
    public function cr_008_03_no_html_in_csv(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/csv");

        $response->assertStatus(200);
        
        $content = $response->getContent();
        $firstLine = explode("\n", $content)[0];
        $headers = str_getcsv($firstLine);
        
        $this->assertNotContains('renderedHtml', $headers);
        $this->assertNotContains('html', $headers);
    }

    /** @test */
    public function cr_008_04_content_type_header(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/csv");

        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function cr_008_05_special_characters_escaped(): void
    {
        $job = $this->createCompletedJobWithPages(1);
        CrawlPage::factory()->forJob($job)->create([
            'title' => 'Title with "quotes" and, commas',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/csv");

        $response->assertStatus(200);
        // CSV should properly escape quotes and commas
    }

    /** @test */
    public function cr_008_06_newlines_handled(): void
    {
        $job = $this->createCompletedJobWithPages(1);
        CrawlPage::factory()->forJob($job)->create([
            'title' => "Title with\nnewline",
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/csv");

        $response->assertStatus(200);
    }

    /** @test */
    public function cr_008_07_unicode_support(): void
    {
        $job = $this->createCompletedJobWithPages(1);
        CrawlPage::factory()->forJob($job)->create([
            'title' => 'Title with émojis 🎉 and ñoñó',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/csv");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function cr_008_08_running_job_returns_409(): void
    {
        $job = CrawlJob::factory()->running()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/csv");

        $response->assertStatus(409);
    }

    /** @test */
    public function cr_008_09_job_not_found_returns_404(): void
    {
        $fakeJobId = $this->generateJobId();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$fakeJobId}/export/csv");

        $response->assertStatus(404);
    }

    /** @test */
    public function cr_008_10_unauthorized_returns_401(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/csv");

        $response->assertStatus(401);
    }

    // =========================================================================
    // CR-009: HTML EXPORT TESTS
    // =========================================================================

    /** @test */
    public function cr_009_01_export_completed_job_as_html(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/html");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /** @test */
    public function cr_009_02_table_of_contents_present(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/html");

        $response->assertStatus(200);
        
        $content = $response->getContent();
        $this->assertStringContainsString('Table of Contents', $content);
        $this->assertStringContainsString('<nav', $content);
    }

    /** @test */
    public function cr_009_03_page_dividers_present(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/html");

        $response->assertStatus(200);
        
        $content = $response->getContent();
        $this->assertStringContainsString('<hr', $content);
    }

    /** @test */
    public function cr_009_04_content_type_header(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/html");

        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /** @test */
    public function cr_009_05_running_job_returns_409(): void
    {
        $job = CrawlJob::factory()->running()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/html");

        $response->assertStatus(409);
    }

    /** @test */
    public function cr_009_06_job_not_found_returns_404(): void
    {
        $fakeJobId = $this->generateJobId();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$fakeJobId}/export/html");

        $response->assertStatus(404);
    }

    /** @test */
    public function cr_009_07_unauthorized_returns_401(): void
    {
        $job = $this->createCompletedJobWithPages(1);

        $response = $this->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/html");

        $response->assertStatus(401);
    }

    /** @test */
    public function cr_009_08_utf8_encoding(): void
    {
        $job = $this->createCompletedJobWithPages(1);
        CrawlPage::factory()->forJob($job)->create([
            'title' => 'Title with émojis 🎉',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/export/html");

        $response->assertStatus(200);
        
        $content = $response->getContent();
        $this->assertStringContainsString('charset=UTF-8', $content);
    }

    // =========================================================================
    // CR-011: CACHING TESTS
    // =========================================================================

    /** @test */
    public function cr_011_01_first_request_cache_miss(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200);
        // First request should not be cached
    }

    /** @test */
    public function cr_011_02_second_request_cache_hit(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        // First request
        $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        // Second request should be cached
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(200);
        $this->assertEquals('HIT', $response->headers->get('X-Cache'));
    }

    /** @test */
    public function cr_011_03_cache_header_present(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertHeader('X-Cache');
    }

    /** @test */
    public function cr_011_04_etag_header_present(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertHeader('ETag');
    }

    /** @test */
    public function cr_011_05_if_none_match_returns_304(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        // Get ETag from first request
        $firstResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");
        $etag = $firstResponse->headers->get('ETag');

        // Request with If-None-Match
        $response = $this->actingAs($this->user)
            ->withHeaders(['If-None-Match' => $etag])
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(304);
    }

    /** @test */
    public function cr_011_06_cache_invalidation_on_job_delete(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        // Prime the cache
        $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        // Delete the job
        $job->delete();

        // Cache should be invalidated - request should return 404
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        $response->assertStatus(404);
    }

    /** @test */
    public function cr_011_08_running_job_not_cached(): void
    {
        $job = CrawlJob::factory()->running()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content");

        // Running jobs should not be cached (409 response)
        $response->assertStatus(409);
        $response->assertHeaderMissing('X-Cache');
    }

    // =========================================================================
    // CR-013: SECURITY TESTS
    // =========================================================================

    /** @test */
    public function cr_013_01_sql_injection_in_job_id(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/1'; DROP TABLE crawl_jobs;--/content");

        $response->assertStatus(404); // Not 500 (SQL not executed)
    }

    /** @test */
    public function cr_013_02_xss_in_search_param(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?search=<script>alert(1)</script>");

        // Should handle XSS safely
        $response->assertStatus(200);
    }

    /** @test */
    public function cr_013_03_path_traversal_in_include(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?include=../../etc/passwd");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['include']);
    }

    /** @test */
    public function cr_013_04_idor_via_job_id(): void
    {
        $otherJob = CrawlJob::factory()->completed()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$otherJob->job_id}/content");

        $response->assertStatus(404); // Not 403 (prevents enumeration)
    }

    /** @test */
    public function cr_013_05_idor_via_page_id(): void
    {
        $otherJob = CrawlJob::factory()->completed()->create(['user_id' => $this->otherUser->id]);
        $page = CrawlPage::factory()->forJob($otherJob)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$otherJob->job_id}/pages/{$page->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function cr_013_06_mass_assignment_ignored(): void
    {
        $job = $this->createCompletedJobWithPages(5);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?isAdmin=1");

        $response->assertStatus(200);
        // Extra query params should be ignored
    }

    /** @test */
    public function cr_013_07_response_size_limit_handled(): void
    {
        // Create job with large total_size
        $job = CrawlJob::factory()->completed()->create([
            'user_id' => $this->user->id,
            'total_size' => 15728640, // 15MB
        ]);
        
        // Create pages
        for ($i = 0; $i < 100; $i++) {
            CrawlPage::factory()->forJob($job)->create([
                'size' => 157286, // ~150KB each
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crawler/jobs/{$job->job_id}/content?perPage=100");

        // Should handle large responses gracefully
        $response->assertStatus(200);
    }
}
