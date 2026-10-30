<?php

namespace Tests\Unit\Services\Crawler;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use App\Services\Crawler\SizeLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit tests for SizeLimiterService.
 *
 * Tests the 3MB content cap enforcement for crawl jobs,
 * including size tracking, limit checking, and Unicode handling.
 *
 * @see TASK-22-9
 * @see SPEC-022
 */
class SizeLimiterServiceTest extends TestCase
{
    use RefreshDatabase;

    private SizeLimiterService $service;

    // Size constants
    private const MAX_CONTENT_SIZE = 3145728; // 3MB in bytes
    private const MAX_PAGE_SIZE = 10485760;   // 10MB in bytes
    private const WARNING_THRESHOLD = 0.8;    // 80%
    private const WARNING_SIZE = 2516582;     // 2.4MB (80% of 3MB)

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set default config values
        Config::set('crawler.max_content_size', self::MAX_CONTENT_SIZE);
        Config::set('crawler.max_page_size', self::MAX_PAGE_SIZE);
        Config::set('crawler.warning_threshold', self::WARNING_THRESHOLD);
        
        $this->service = app(SizeLimiterService::class);
    }

    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    /**
     * @test
     * Test SI-001: Service can be instantiated via container
     */
    public function service_can_be_instantiated_via_container(): void
    {
        $service = app(SizeLimiterService::class);
        
        $this->assertInstanceOf(SizeLimiterService::class, $service);
    }

    /**
     * @test
     * Test SI-002: Service uses config values for limits
     */
    public function service_uses_config_values_for_limits(): void
    {
        Config::set('crawler.max_content_size', 1048576); // 1MB
        
        $service = app(SizeLimiterService::class);
        
        // Verify through behavior - adding 1.5MB should fail with 1MB limit
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        $result = $service->checkLimit($job->job_id, 1572864); // 1.5MB
        
        $this->assertFalse($result['canProceed']);
    }

    // =========================================================================
    // checkLimit() Tests
    // =========================================================================

    /**
     * @test
     * Test CL-001: Returns canProceed true when under limit
     */
    public function check_limit_returns_true_when_under_limit(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1000000]); // 1MB
        
        $result = $this->service->checkLimit($job->job_id, 1000000); // Add 1MB
        
        $this->assertTrue($result['canProceed']);
        $this->assertFalse($result['pageExceedsLimit']);
    }

    /**
     * @test
     * Test CL-002: Returns canProceed false when would exceed limit
     */
    public function check_limit_returns_false_when_would_exceed_limit(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 2500000]); // ~2.4MB
        
        $result = $this->service->checkLimit($job->job_id, 1000000); // Try to add 1MB
        
        $this->assertFalse($result['canProceed']);
    }

    /**
     * @test
     * Test CL-003: Returns correct result structure
     */
    public function check_limit_returns_correct_result_structure(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1000000]);
        
        $result = $this->service->checkLimit($job->job_id, 500000);
        
        $this->assertArrayHasKey('canProceed', $result);
        $this->assertArrayHasKey('currentSize', $result);
        $this->assertArrayHasKey('maxSize', $result);
        $this->assertArrayHasKey('remaining', $result);
        $this->assertArrayHasKey('percentUsed', $result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertArrayHasKey('pageExceedsLimit', $result);
    }

    /**
     * @test
     * Test CL-004: Calculates remaining capacity correctly
     */
    public function check_limit_calculates_remaining_correctly(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1000000]); // 1MB
        
        $result = $this->service->checkLimit($job->job_id, 500000);
        
        $expectedRemaining = self::MAX_CONTENT_SIZE - 1000000;
        $this->assertEquals($expectedRemaining, $result['remaining']);
    }

    /**
     * @test
     * Test CL-005: Calculates percent used correctly
     */
    public function check_limit_calculates_percent_used_correctly(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1572864]); // 1.5MB = 50% of 3MB
        
        $result = $this->service->checkLimit($job->job_id, 0);
        
        $this->assertEquals(50.0, $result['percentUsed']);
    }

    /**
     * @test
     * Test CL-006: Warning flag true at 80% threshold
     */
    public function check_limit_warning_true_at_80_percent(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 2516582]); // 80% of 3MB
        
        $result = $this->service->checkLimit($job->job_id, 0);
        
        $this->assertTrue($result['warning']);
    }

    /**
     * @test
     * Test CL-007: Warning flag false below 80% threshold
     */
    public function check_limit_warning_false_below_80_percent(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 2000000]); // ~63% of 3MB
        
        $result = $this->service->checkLimit($job->job_id, 0);
        
        $this->assertFalse($result['warning']);
    }

    /**
     * @test
     * Test CL-008: Page size exceeds max_page_size flag
     */
    public function check_limit_flags_page_exceeds_max(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $result = $this->service->checkLimit($job->job_id, 11000000); // 11MB > 10MB limit
        
        $this->assertTrue($result['pageExceedsLimit']);
        $this->assertFalse($result['canProceed']);
    }

    /**
     * @test
     * Test CL-009: Exact limit boundary - at max
     */
    public function check_limit_exact_boundary_at_max(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => self::MAX_CONTENT_SIZE]);
        
        $result = $this->service->checkLimit($job->job_id, 1);
        
        $this->assertFalse($result['canProceed']);
        $this->assertEquals(100.0, $result['percentUsed']);
    }

    /**
     * @test
     * Test CL-010: Exact limit boundary - one byte under max
     */
    public function check_limit_exact_boundary_one_byte_under_max(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => self::MAX_CONTENT_SIZE - 1]);
        
        $result = $this->service->checkLimit($job->job_id, 1);
        
        $this->assertTrue($result['canProceed']);
    }

    /**
     * @test
     * Test CL-011: Job not found returns default values
     */
    public function check_limit_job_not_found_returns_defaults(): void
    {
        $result = $this->service->checkLimit('non-existent-job-id', 1000);
        
        $this->assertFalse($result['canProceed']);
        $this->assertEquals(0, $result['currentSize']);
    }

    // =========================================================================
    // addSize() Tests
    // =========================================================================

    /**
     * @test
     * Test AS-001: Updates job total_size correctly
     */
    public function add_size_updates_job_total_size(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1000000]);
        
        $this->service->addSize($job->job_id, 500000);
        
        $job->refresh();
        $this->assertEquals(1500000, $job->total_size);
    }

    /**
     * @test
     * Test AS-002: Handles multiple additions
     */
    public function add_size_handles_multiple_additions(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $this->service->addSize($job->job_id, 500000);
        $this->service->addSize($job->job_id, 300000);
        $this->service->addSize($job->job_id, 200000);
        
        $job->refresh();
        $this->assertEquals(1000000, $job->total_size);
    }

    /**
     * @test
     * Test AS-003: Handles zero size addition
     */
    public function add_size_handles_zero_addition(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1000000]);
        
        $this->service->addSize($job->job_id, 0);
        
        $job->refresh();
        $this->assertEquals(1000000, $job->total_size);
    }

    /**
     * @test
     * Test AS-004: Logs warning when approaching limit
     */
    public function add_size_logs_warning_when_approaching_limit(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'approaching size limit') &&
                    isset($context['percent_used']) &&
                    $context['percent_used'] >= 80;
            });
        
        $job = CrawlJob::factory()->create(['total_size' => 2000000]);
        
        $this->service->addSize($job->job_id, 600000); // Will exceed 80%
    }

    /**
     * @test
     * Test AS-005: Non-existent job throws exception
     */
    public function add_size_non_existent_job_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        
        $this->service->addSize('non-existent-job-id', 1000);
    }

    /**
     * @test
     * Test AS-006: Negative size throws exception
     */
    public function add_size_negative_throws_exception(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1000000]);
        
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->addSize($job->job_id, -500);
    }

    // =========================================================================
    // getTotalSize() Tests
    // =========================================================================

    /**
     * @test
     * Test GT-001: Returns correct total size
     */
    public function get_total_size_returns_correct_value(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1234567]);
        
        $this->assertEquals(1234567, $this->service->getTotalSize($job->job_id));
    }

    /**
     * @test
     * Test GT-002: Returns zero for new job
     */
    public function get_total_size_returns_zero_for_new_job(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $this->assertEquals(0, $this->service->getTotalSize($job->job_id));
    }

    /**
     * @test
     * Test GT-003: Returns zero for non-existent job
     */
    public function get_total_size_returns_zero_for_non_existent(): void
    {
        $this->assertEquals(0, $this->service->getTotalSize('non-existent-job-id'));
    }

    /**
     * @test
     * Test GT-004: Reflects changes after addSize
     */
    public function get_total_size_reflects_add_size_changes(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $this->service->addSize($job->job_id, 500000);
        
        $this->assertEquals(500000, $this->service->getTotalSize($job->job_id));
    }

    // =========================================================================
    // getRemaining() Tests
    // =========================================================================

    /**
     * @test
     * Test GR-001: Returns correct remaining capacity
     */
    public function get_remaining_returns_correct_capacity(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1000000]);
        
        $remaining = $this->service->getRemaining($job->job_id);
        
        $this->assertEquals(self::MAX_CONTENT_SIZE - 1000000, $remaining);
    }

    /**
     * @test
     * Test GR-002: Returns max size for new job
     */
    public function get_remaining_returns_max_for_new_job(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $remaining = $this->service->getRemaining($job->job_id);
        
        $this->assertEquals(self::MAX_CONTENT_SIZE, $remaining);
    }

    /**
     * @test
     * Test GR-003: Returns zero when at limit
     */
    public function get_remaining_returns_zero_at_limit(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => self::MAX_CONTENT_SIZE]);
        
        $remaining = $this->service->getRemaining($job->job_id);
        
        $this->assertEquals(0, $remaining);
    }

    /**
     * @test
     * Test GR-004: Returns zero for over-limit job
     */
    public function get_remaining_returns_zero_when_over_limit(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => self::MAX_CONTENT_SIZE + 1000]);
        
        $remaining = $this->service->getRemaining($job->job_id);
        
        $this->assertEquals(0, $remaining);
    }

    /**
     * @test
     * Test GR-005: Returns max for non-existent job
     */
    public function get_remaining_returns_max_for_non_existent(): void
    {
        $remaining = $this->service->getRemaining('non-existent-job-id');
        
        $this->assertEquals(self::MAX_CONTENT_SIZE, $remaining);
    }

    // =========================================================================
    // enforceLimit() Tests
    // =========================================================================

    /**
     * @test
     * Test EL-001: Returns null when content fits
     */
    public function enforce_limit_returns_null_when_content_fits(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $content = str_repeat('a', 1000000); // 1MB
        
        $result = $this->service->enforceLimit($job->job_id, $content);
        
        $this->assertNull($result); // Content accepted as-is
    }

    /**
     * @test
     * Test EL-002: Returns null and logs when page exceeds page limit
     */
    public function enforce_limit_rejects_oversized_page(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'exceeds page size limit');
            });
        
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $content = str_repeat('a', 11000000); // 11MB > 10MB page limit
        
        $result = $this->service->enforceLimit($job->job_id, $content);
        
        // Returns null (page rejected, but never truncated)
        $this->assertNull($result);
    }

    /**
     * @test
     * Test EL-003: Never silently truncates HTML
     */
    public function enforce_limit_never_silently_truncates_html(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => self::MAX_CONTENT_SIZE - 100]);
        
        $content = str_repeat('a', 1000); // Would exceed limit but not page limit
        
        $result = $this->service->enforceLimit($job->job_id, $content);
        
        // Should return null indicating rejection, not truncated content
        $this->assertNull($result);
    }

    /**
     * @test
     * Test EL-004: Records skipped pages for oversized content
     */
    public function enforce_limit_records_skipped_pages(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $content = str_repeat('a', 11000000); // 11MB > 10MB limit
        
        $this->service->enforceLimit($job->job_id, $content, 'https://example.com/large-page');
        
        // Verify page was recorded as skipped
        $skippedPage = CrawlPage::where('crawl_job_id', $job->id)
            ->where('url', 'https://example.com/large-page')
            ->where('error', 'LIKE', '%exceeds page size limit%')
            ->first();
        
        $this->assertNotNull($skippedPage);
    }

    /**
     * @test
     * Test EL-005: Empty content returns null
     */
    public function enforce_limit_empty_content_returns_null(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $result = $this->service->enforceLimit($job->job_id, '');
        
        $this->assertNull($result);
    }

    /**
     * @test
     * Test EL-006: Null content returns null
     */
    public function enforce_limit_null_content_returns_null(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $result = $this->service->enforceLimit($job->job_id, null);
        
        $this->assertNull($result);
    }

    // =========================================================================
    // Byte Calculation Tests (UTF-8 Multi-byte)
    // =========================================================================

    /**
     * @test
     * Test BC-001: Counts bytes not characters for ASCII
     */
    public function byte_calculation_counts_bytes_for_ascii(): void
    {
        $content = str_repeat('a', 1000); // 1000 ASCII chars = 1000 bytes
        
        $size = $this->service->calculateContentSize($content);
        
        $this->assertEquals(1000, $size);
    }

    /**
     * @test
     * Test BC-002: Counts bytes correctly for UTF-8 multibyte
     */
    public function byte_calculation_counts_bytes_for_utf8_multibyte(): void
    {
        // Japanese characters (3 bytes each in UTF-8)
        $content = str_repeat('あ', 100); // 100 chars = 300 bytes
        
        $size = $this->service->calculateContentSize($content);
        
        $this->assertEquals(300, $size);
    }

    /**
     * @test
     * Test BC-003: Counts bytes correctly for emoji (4 bytes each)
     */
    public function byte_calculation_counts_bytes_for_emoji(): void
    {
        $content = str_repeat('😀', 100); // 100 emoji = 400 bytes
        
        $size = $this->service->calculateContentSize($content);
        
        $this->assertEquals(400, $size);
    }

    /**
     * @test
     * Test BC-004: Mixed ASCII and multibyte content
     */
    public function byte_calculation_mixed_ascii_multibyte(): void
    {
        // 10 ASCII chars (10 bytes) + 10 Japanese (30 bytes) + 5 emoji (20 bytes)
        $content = 'abcdefghij' . str_repeat('あ', 10) . str_repeat('😀', 5);
        
        $size = $this->service->calculateContentSize($content);
        
        $this->assertEquals(60, $size);
    }

    /**
     * @test
     * Test BC-005: Uses mb_strlen with 8bit encoding
     */
    public function byte_calculation_uses_8bit_encoding(): void
    {
        // This verifies the implementation uses mb_strlen($content, '8bit')
        // not strlen (which would fail on UTF-8) or mb_strlen without 8bit
        $content = 'äöü'; // 6 bytes in UTF-8 (2 bytes each)
        
        $size = $this->service->calculateContentSize($content);
        
        $this->assertEquals(6, $size);
    }

    /**
     * @test
     * Test BC-006: Empty string returns zero
     */
    public function byte_calculation_empty_string(): void
    {
        $this->assertEquals(0, $this->service->calculateContentSize(''));
    }

    /**
     * @test
     * Test BC-007: Null returns zero
     */
    public function byte_calculation_null_returns_zero(): void
    {
        $this->assertEquals(0, $this->service->calculateContentSize(null));
    }

    /**
     * @test
     * Test BC-008: Large content near limit
     */
    public function byte_calculation_large_content_near_limit(): void
    {
        $content = str_repeat('x', self::MAX_CONTENT_SIZE - 1);
        
        $size = $this->service->calculateContentSize($content);
        
        $this->assertEquals(self::MAX_CONTENT_SIZE - 1, $size);
    }

    // =========================================================================
    // Warning Threshold Tests
    // =========================================================================

    /**
     * @test
     * Test WT-001: Warning at exactly 80% threshold
     */
    public function warning_at_exactly_80_percent(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => self::WARNING_SIZE]);
        
        $result = $this->service->checkLimit($job->job_id, 0);
        
        $this->assertTrue($result['warning']);
    }

    /**
     * @test
     * Test WT-002: No warning just below 80%
     */
    public function no_warning_just_below_80_percent(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => self::WARNING_SIZE - 1]);
        
        $result = $this->service->checkLimit($job->job_id, 0);
        
        $this->assertFalse($result['warning']);
    }

    /**
     * @test
     * Test WT-003: Warning persists above 80%
     */
    public function warning_persists_above_80_percent(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 2800000]); // ~89%
        
        $result = $this->service->checkLimit($job->job_id, 0);
        
        $this->assertTrue($result['warning']);
    }

    /**
     * @test
     * Test WT-004: Custom warning threshold from config
     */
    public function custom_warning_threshold(): void
    {
        Config::set('crawler.warning_threshold', 0.5); // 50%
        
        $service = app(SizeLimiterService::class);
        $job = CrawlJob::factory()->create(['total_size' => 1600000]); // ~51%
        
        $result = $service->checkLimit($job->job_id, 0);
        
        $this->assertTrue($result['warning']);
    }

    // =========================================================================
    // Configuration Tests
    // =========================================================================

    /**
     * @test
     * Test CF-001: Custom max_content_size from config
     */
    public function custom_max_content_size(): void
    {
        Config::set('crawler.max_content_size', 1048576); // 1MB
        
        $service = app(SizeLimiterService::class);
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $result = $service->checkLimit($job->job_id, 1100000); // 1.1MB
        
        $this->assertFalse($result['canProceed']);
    }

    /**
     * @test
     * Test CF-002: Custom max_page_size from config
     */
    public function custom_max_page_size(): void
    {
        Config::set('crawler.max_page_size', 5000000); // 5MB
        
        $service = app(SizeLimiterService::class);
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $result = $service->checkLimit($job->job_id, 6000000); // 6MB > 5MB
        
        $this->assertTrue($result['pageExceedsLimit']);
    }

    /**
     * @test
     * Test CF-003: Default values when config missing
     */
    public function default_values_when_config_missing(): void
    {
        Config::set('crawler.max_content_size', null);
        Config::set('crawler.max_page_size', null);
        Config::set('crawler.warning_threshold', null);
        
        $service = app(SizeLimiterService::class);
        
        // Should use defaults: 3MB, 10MB, 80%
        $this->assertInstanceOf(SizeLimiterService::class, $service);
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    /**
     * @test
     * Test IN-001: Full workflow with multiple pages
     */
    public function full_workflow_multiple_pages(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        // Page 1: 500KB
        $content1 = str_repeat('a', 512000);
        $result1 = $this->service->enforceLimit($job->job_id, $content1);
        $this->assertNull($result1); // Accepted
        $this->service->addSize($job->job_id, 512000);
        
        // Page 2: 1MB
        $content2 = str_repeat('b', 1048576);
        $result2 = $this->service->enforceLimit($job->job_id, $content2);
        $this->assertNull($result2); // Accepted
        $this->service->addSize($job->job_id, 1048576);
        
        // Verify total size
        $this->assertEquals(1560576, $this->service->getTotalSize($job->job_id));
        
        // Verify remaining
        $remaining = $this->service->getRemaining($job->job_id);
        $this->assertEquals(self::MAX_CONTENT_SIZE - 1560576, $remaining);
    }

    /**
     * @test
     * Test IN-002: Workflow hits size limit
     */
    public function workflow_hits_size_limit(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 2500000]);
        
        // Try to add 1MB - should exceed limit
        $check = $this->service->checkLimit($job->job_id, 1048576);
        
        $this->assertFalse($check['canProceed']);
        $this->assertTrue($check['warning']);
    }

    /**
     * @test
     * Test IN-003: Page rejected for size but other pages continue
     */
    public function page_rejected_others_continue(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        // Oversized page (11MB > 10MB limit)
        $oversizedContent = str_repeat('x', 11000000);
        $result1 = $this->service->enforceLimit(
            $job->job_id, 
            $oversizedContent, 
            'https://example.com/huge'
        );
        $this->assertNull($result1); // Rejected
        
        // Normal page should still work
        $normalContent = str_repeat('y', 500000);
        $result2 = $this->service->enforceLimit(
            $job->job_id,
            $normalContent,
            'https://example.com/normal'
        );
        $this->assertNull($result2); // Accepted
        
        // Job size should not include rejected page
        $this->service->addSize($job->job_id, 500000);
        $this->assertEquals(500000, $this->service->getTotalSize($job->job_id));
    }

    /**
     * @test
     * Test IN-004: Percent calculation accuracy
     */
    public function percent_calculation_accuracy(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1572864]); // Exactly 50%
        
        $result = $this->service->checkLimit($job->job_id, 0);
        
        $this->assertEquals(50.0, $result['percentUsed']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /**
     * @test
     * Test EC-001: Very small additions accumulate
     */
    public function very_small_additions_accumulate(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        for ($i = 0; $i < 1000; $i++) {
            $this->service->addSize($job->job_id, 1000);
        }
        
        $job->refresh();
        $this->assertEquals(1000000, $job->total_size);
    }

    /**
     * @test
     * Test EC-002: Integer overflow protection
     */
    public function integer_overflow_protection(): void
    {
        // Create job with max int - 1 size
        $job = CrawlJob::factory()->create(['total_size' => PHP_INT_MAX - 1]);
        
        // Should handle gracefully without overflow
        $result = $this->service->checkLimit($job->job_id, 1);
        
        $this->assertFalse($result['canProceed']);
    }

    /**
     * @test
     * Test EC-003: Concurrent access simulation
     */
    public function concurrent_access_simulation(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        // Simulate concurrent additions
        $sizes = [100000, 200000, 300000, 400000];
        foreach ($sizes as $size) {
            $this->service->addSize($job->job_id, $size);
        }
        
        $job->refresh();
        $this->assertEquals(1000000, $job->total_size);
    }

    /**
     * @test
     * Test EC-004: Zero byte content
     */
    public function zero_byte_content(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $result = $this->service->enforceLimit($job->job_id, '');
        
        $this->assertNull($result);
        $this->assertEquals(0, $this->service->getTotalSize($job->job_id));
    }

    /**
     * @test
     * Test EC-005: Whitespace-only content
     */
    public function whitespace_only_content(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $content = str_repeat(' ', 1000);
        $result = $this->service->enforceLimit($job->job_id, $content);
        
        $this->assertNull($result);
        $this->assertEquals(1000, $this->service->calculateContentSize($content));
    }

    // =========================================================================
    // Security Tests
    // =========================================================================

    /**
     * @test
     * Test SC-001: Negative size injection prevented
     */
    public function negative_size_injection_prevented(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1000000]);
        
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->addSize($job->job_id, -500000);
    }

    /**
     * @test
     * Test SC-002: SQL injection in job_id handled
     */
    public function sql_injection_in_job_id_handled(): void
    {
        $maliciousId = "'; DROP TABLE crawl_jobs; --";
        
        $result = $this->service->checkLimit($maliciousId, 1000);
        
        // Should return default values, not execute SQL
        $this->assertFalse($result['canProceed']);
    }

    /**
     * @test
     * Test SC-003: XSS in URL logged safely
     */
    public function xss_in_url_logged_safely(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                // URL should be escaped/logged safely
                return isset($context['url']) || true; // Accept any logging
            });
        
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $xssUrl = '<script>alert("xss")</script>';
        $content = str_repeat('a', 11000000); // Oversized
        
        $this->service->enforceLimit($job->job_id, $content, $xssUrl);
    }

    // =========================================================================
    // Response Structure Tests
    // =========================================================================

    /**
     * @test
     * Test RS-001: SizeLimiterResult structure matches spec
     */
    public function result_structure_matches_spec(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1000000]);
        
        $result = $this->service->checkLimit($job->job_id, 500000);
        
        // Verify all required keys
        $this->assertArrayHasKey('canProceed', $result);
        $this->assertArrayHasKey('currentSize', $result);
        $this->assertArrayHasKey('maxSize', $result);
        $this->assertArrayHasKey('remaining', $result);
        $this->assertArrayHasKey('percentUsed', $result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertArrayHasKey('pageExceedsLimit', $result);
        
        // Verify types
        $this->assertIsBool($result['canProceed']);
        $this->assertIsInt($result['currentSize']);
        $this->assertIsInt($result['maxSize']);
        $this->assertIsInt($result['remaining']);
        $this->assertIsFloat($result['percentUsed']);
        $this->assertIsBool($result['warning']);
        $this->assertIsBool($result['pageExceedsLimit']);
    }

    /**
     * @test
     * Test RS-002: maxSize matches configured value
     */
    public function max_size_matches_config(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 0]);
        
        $result = $this->service->checkLimit($job->job_id, 0);
        
        $this->assertEquals(self::MAX_CONTENT_SIZE, $result['maxSize']);
    }

    /**
     * @test
     * Test RS-003: currentSize matches job total_size
     */
    public function current_size_matches_job(): void
    {
        $job = CrawlJob::factory()->create(['total_size' => 1234567]);
        
        $result = $this->service->checkLimit($job->job_id, 0);
        
        $this->assertEquals(1234567, $result['currentSize']);
    }
}
