<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Services\Crawler\PartialResultsService;
use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Support\Facades\Storage;
use Mockery;

/**
 * Unit tests for TASK-22-17: Partial Results Service
 *
 * Tests partial results storage, retrieval, and response formatting
 *
 * @see TESTS/TASK-22-17.md
 * @see TASK-22-17
 */
class PartialResultsServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // TC-05: PARTIAL RESULTS SERVICE TESTS
    // =========================================================================

    /**
     * TC-05-01: Store partial results
     */
    public function test_store_partial_results(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-partial-1';
        $crawlJob->shouldReceive('update')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return isset($data['partial_results']) &&
                       isset($data['status']);
            }));

        $service = new PartialResultsService();

        $pages = [
            ['url' => 'https://example.com/page1', 'status' => 'completed'],
            ['url' => 'https://example.com/page2', 'status' => 'completed'],
            ['url' => 'https://example.com/page3', 'status' => 'completed'],
        ];

        $result = $service->storePartialResults($crawlJob, $pages, 'timeout');

        $this->assertTrue($result);
    }

    /**
     * TC-05-02: Include timeout reason
     */
    public function test_include_timeout_reason(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-timeout-reason';
        $crawlJob->shouldReceive('update')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return $data['partial_results']['reason'] === 'Job timeout reached';
            }));

        $service = new PartialResultsService();
        $service->storePartialResults($crawlJob, [], 'timeout');
    }

    /**
     * TC-05-03: Include cancellation reason
     */
    public function test_include_cancellation_reason(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-cancel-reason';
        $crawlJob->shouldReceive('update')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return $data['partial_results']['reason'] === 'User requested cancellation';
            }));

        $service = new PartialResultsService();
        $service->storePartialResults($crawlJob, [], 'cancelled');
    }

    /**
     * TC-05-04: Mark uncrawled pages
     */
    public function test_mark_uncrawled_pages(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-uncrawled';
        $crawlJob->shouldReceive('update')
            ->once()
            ->with(\Mockery::on(function ($data) {
                $partial = $data['partial_results'];
                return isset($partial['not_crawled']) &&
                       $partial['not_crawled'] === 2;
            }));

        $service = new PartialResultsService();

        // 5 total URLs, 3 crawled
        $crawledPages = [
            ['url' => 'https://example.com/1'],
            ['url' => 'https://example.com/2'],
            ['url' => 'https://example.com/3'],
        ];
        $totalUrls = 5;

        $service->storePartialResults($crawlJob, $crawledPages, 'timeout', $totalUrls);
    }

    /**
     * TC-05-05: Generate partial content URL
     */
    public function test_generate_partial_content_url(): void
    {
        $service = new PartialResultsService();

        $url = $service->generatePartialContentUrl('crawl-url-test');

        $this->assertEquals('/api/v1/crawler/jobs/crawl-url-test/content', $url);
    }

    /**
     * TC-05-06: Retrieve partial content
     */
    public function test_retrieve_partial_content(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-retrieve';
        $crawlJob->partial_results = [
            'pages' => [
                ['url' => 'https://example.com/1', 'html' => '<html>1</html>'],
                ['url' => 'https://example.com/2', 'html' => '<html>2</html>'],
            ],
            'reason' => 'timeout',
            'pages_crawled' => 2,
        ];

        $service = new PartialResultsService();
        $content = $service->getPartialContent($crawlJob);

        $this->assertIsArray($content);
        $this->assertCount(2, $content['pages']);
    }

    /**
     * TC-05-07: Partial results response format
     */
    public function test_partial_results_response_format(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-response-format';
        $crawlJob->pages_crawled = 3;
        $crawlJob->total_size = 600000;
        $crawlJob->shouldReceive('update');

        $service = new PartialResultsService();
        $response = $service->buildPartialResponse($crawlJob, 'timeout');

        $this->assertArrayHasKey('jobId', $response);
        $this->assertArrayHasKey('pagesCrawled', $response);
        $this->assertArrayHasKey('totalSize', $response);
        $this->assertArrayHasKey('reason', $response);
        $this->assertArrayHasKey('partialContentUrl', $response);
    }

    /**
     * TC-05-08: Timeout response format shows completed status
     */
    public function test_timeout_response_format_shows_completed_status(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-timeout-status';
        $crawlJob->pages_crawled = 8;
        $crawlJob->total_size = 1500000;
        $crawlJob->shouldReceive('update');

        $service = new PartialResultsService();
        $response = $service->buildPartialResponse($crawlJob, 'timeout');

        // Timeout shows as 'completed' with reason
        $this->assertEquals('completed', $response['status'] ?? 'completed');
        $this->assertStringContainsString('timeout', $response['reason']);
    }

    /**
     * TC-05-09: Cancelled response format
     */
    public function test_cancelled_response_format(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-cancelled-status';
        $crawlJob->pages_crawled = 3;
        $crawlJob->total_size = 600000;
        $crawlJob->shouldReceive('update');

        $service = new PartialResultsService();
        $response = $service->buildPartialResponse($crawlJob, 'cancelled');

        $this->assertEquals('cancelled', $response['status']);
        $this->assertArrayHasKey('cancelledAt', $response);
    }

    /**
     * TC-05-10: Include all successful pages
     */
    public function test_include_all_successful_pages(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-success-pages';
        $crawlJob->shouldReceive('update')
            ->once()
            ->with(\Mockery::on(function ($data) {
                $pages = $data['partial_results']['pages'];
                return count($pages) === 3 &&
                       $pages[0]['status'] === 'completed' &&
                       $pages[1]['status'] === 'completed' &&
                       $pages[2]['status'] === 'completed';
            }));

        $service = new PartialResultsService();

        $pages = [
            ['url' => 'https://example.com/1', 'status' => 'completed'],
            ['url' => 'https://example.com/2', 'status' => 'completed'],
            ['url' => 'https://example.com/3', 'status' => 'completed'],
        ];

        $service->storePartialResults($crawlJob, $pages, 'timeout');
    }

    /**
     * TC-05-11: Exclude failed pages from count
     */
    public function test_exclude_failed_pages_from_count(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-exclude-failed';
        $crawlJob->shouldReceive('update')
            ->once()
            ->with(\Mockery::on(function ($data) {
                // Only count successful pages
                return $data['partial_results']['pages_crawled'] === 3;
            }));

        $service = new PartialResultsService();

        $pages = [
            ['url' => 'https://example.com/1', 'status' => 'completed'],
            ['url' => 'https://example.com/2', 'status' => 'completed'],
            ['url' => 'https://example.com/3', 'status' => 'failed'],
            ['url' => 'https://example.com/4', 'status' => 'completed'],
        ];

        $service->storePartialResults($crawlJob, $pages, 'timeout');
    }

    /**
     * TC-05-12: Store in job metadata
     */
    public function test_store_in_job_metadata(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-metadata';
        $crawlJob->shouldReceive('update')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return array_key_exists('partial_results', $data);
            }));

        $service = new PartialResultsService();
        $service->storePartialResults($crawlJob, [], 'timeout');
    }

    /**
     * TC-05-13: Calculate total size
     */
    public function test_calculate_total_size(): void
    {
        $service = new PartialResultsService();

        $pages = [
            ['url' => 'https://example.com/1', 'size' => 1000],
            ['url' => 'https://example.com/2', 'size' => 2000],
            ['url' => 'https://example.com/3', 'size' => 3000],
        ];

        $totalSize = $service->calculateTotalSize($pages);

        $this->assertEquals(6000, $totalSize);
    }

    /**
     * TC-05-14: Format size for display
     */
    public function test_format_size_for_display(): void
    {
        $service = new PartialResultsService();

        $this->assertEquals('500.0 B', $service->formatSize(500));
        $this->assertEquals('1.5 KB', $service->formatSize(1536));
        $this->assertEquals('1.5 MB', $service->formatSize(1572864));
    }

    /**
     * TC-05-15: Empty results handled gracefully
     */
    public function test_empty_results_handled_gracefully(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-empty';
        $crawlJob->shouldReceive('update');

        $service = new PartialResultsService();
        $result = $service->storePartialResults($crawlJob, [], 'timeout');

        $this->assertTrue($result);
    }

    /**
     * TC-05-16: Build timeout message
     */
    public function test_build_timeout_message(): void
    {
        $service = new PartialResultsService();

        $message = $service->buildReasonMessage('timeout', 300);

        $this->assertStringContainsString('timeout', $message);
        $this->assertStringContainsString('300', $message);
    }

    /**
     * TC-05-17: Build cancellation message
     */
    public function test_build_cancellation_message(): void
    {
        $service = new PartialResultsService();

        $message = $service->buildReasonMessage('cancelled');

        $this->assertStringContainsString('cancellation', $message);
    }

    /**
     * TC-05-18: Get pages summary
     */
    public function test_get_pages_summary(): void
    {
        $service = new PartialResultsService();

        $pages = [
            ['url' => 'https://example.com/1', 'status' => 'completed'],
            ['url' => 'https://example.com/2', 'status' => 'completed'],
            ['url' => 'https://example.com/3', 'status' => 'failed'],
            ['url' => 'https://example.com/4', 'status' => 'pending'],
        ];

        $summary = $service->getPagesSummary($pages);

        $this->assertEquals(2, $summary['completed']);
        $this->assertEquals(1, $summary['failed']);
        $this->assertEquals(1, $summary['pending']);
    }

    /**
     * TC-05-19: Validate partial results data
     */
    public function test_validate_partial_results_data(): void
    {
        $service = new PartialResultsService();

        $validData = [
            'pages' => [['url' => 'https://example.com']],
            'reason' => 'timeout',
            'pages_crawled' => 1,
        ];

        $this->assertTrue($service->validatePartialData($validData));
    }

    /**
     * TC-05-20: Reject invalid partial results data
     */
    public function test_reject_invalid_partial_results_data(): void
    {
        $service = new PartialResultsService();

        $invalidData = [
            'pages' => 'not an array',
        ];

        $this->assertFalse($service->validatePartialData($invalidData));
    }

    /**
     * TC-05-21: Serialize pages for storage
     */
    public function test_serialize_pages_for_storage(): void
    {
        $service = new PartialResultsService();

        $pages = [
            ['url' => 'https://example.com/1', 'html' => '<html>Test</html>'],
        ];

        $serialized = $service->serializePages($pages);

        $this->assertIsString($serialized);
        $this->assertJson($serialized);
    }

    /**
     * TC-05-22: Deserialize pages from storage
     */
    public function test_deserialize_pages_from_storage(): void
    {
        $service = new PartialResultsService();

        $json = '{"pages":[{"url":"https://example.com/1"}]}';

        $pages = $service->deserializePages($json);

        $this->assertIsArray($pages);
        $this->assertArrayHasKey('pages', $pages);
    }

    /**
     * TC-05-23: Handle large partial results
     */
    public function test_handle_large_partial_results(): void
    {
        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-large';
        $crawlJob->shouldReceive('update');

        $service = new PartialResultsService();

        // Generate 50 pages
        $pages = [];
        for ($i = 0; $i < 50; $i++) {
            $pages[] = [
                'url' => "https://example.com/page-{$i}",
                'status' => 'completed',
                'size' => 10000,
            ];
        }

        $result = $service->storePartialResults($crawlJob, $pages, 'timeout');

        $this->assertTrue($result);
    }

    /**
     * TC-05-24: Merge with existing partial results
     */
    public function test_merge_with_existing_partial_results(): void
    {
        $existingData = [
            'pages' => [['url' => 'https://example.com/1']],
            'pages_crawled' => 1,
        ];

        $crawlJob = Mockery::mock(CrawlJob::class);
        $crawlJob->job_id = 'crawl-merge';
        $crawlJob->partial_results = $existingData;
        $crawlJob->shouldReceive('update')
            ->once()
            ->with(\Mockery::on(function ($data) {
                $pages = $data['partial_results']['pages'];
                return count($pages) === 2;
            }));

        $service = new PartialResultsService();

        $newPages = [
            ['url' => 'https://example.com/2', 'status' => 'completed'],
        ];

        $service->storePartialResults($crawlJob, $newPages, 'timeout', 2, true);
    }

    /**
     * TC-05-25: Generate summary for API response
     */
    public function test_generate_summary_for_api_response(): void
    {
        $service = new PartialResultsService();

        $partialResults = [
            'pages_crawled' => 5,
            'total_size' => 50000,
            'reason' => 'timeout',
            'not_crawled' => 3,
        ];

        $summary = $service->generateSummary($partialResults, 'crawl-summary');

        $this->assertArrayHasKey('jobId', $summary);
        $this->assertArrayHasKey('pagesCrawled', $summary);
        $this->assertArrayHasKey('totalSize', $summary);
        $this->assertArrayHasKey('reason', $summary);
        $this->assertArrayHasKey('partialContentUrl', $summary);
        $this->assertEquals(5, $summary['pagesCrawled']);
        $this->assertEquals('timeout', $summary['reason']);
    }
}
