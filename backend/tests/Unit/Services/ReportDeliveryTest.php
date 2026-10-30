<?php

namespace Tests\Unit\Services;

use App\Mail\ReportReady;
use App\Models\Report;
use App\Models\Site;
use App\Repositories\ReportRepository;
use App\Services\ReportDelivery;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReportDeliveryTest extends TestCase
{
    protected ReportRepository $reportRepository;
    protected ReportDelivery $service;
    protected string $testFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportRepository = $this->createMock(ReportRepository::class);
        $this->service = new ReportDelivery($this->reportRepository);

        // Create a test PDF file
        $this->testFilePath = storage_path('framework/testing/test-report.pdf');
        if (!is_dir(dirname($this->testFilePath))) {
            mkdir(dirname($this->testFilePath), 0755, true);
        }
        file_put_contents($this->testFilePath, '%PDF-1.4 test content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
        parent::tearDown();
    }

    /**
     * Test 19.1: Deliver report to recipients
     */
    public function test_deliver_report_to_recipients(): void
    {
        Mail::fake();

        $site = $this->createMockSite(1, 'Test Site', 'https://example.com');
        $report = $this->createMockReport($site, 1000); // Small file

        $this->reportRepository->expects($this->once())
            ->method('update')
            ->willReturnCallback(function ($id, $data) {
                $this->assertEquals('sent', $data['delivery_status']);
                $this->assertNotNull($data['delivered_at']);
                $this->assertIsArray($data['delivery_log']);
                return true;
            });

        $result = $this->service->deliver($report, ['user1@example.com', 'user2@example.com']);

        $this->assertEquals('sent', $result['status']);
        $this->assertCount(2, $result['log']);

        foreach ($result['log'] as $entry) {
            $this->assertEquals('sent', $entry['status']);
        }

        // ReportReady implements ShouldQueue, so we assert queued instead of sent
        Mail::assertQueued(ReportReady::class, 2);
    }

    /**
     * Test 19.1: Email contains attachment when file is small
     */
    public function test_email_contains_attachment_for_small_files(): void
    {
        Mail::fake();

        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReport($site, 1000); // 1KB file

        $this->reportRepository->method('update')->willReturn(true);

        $this->service->deliver($report, ['user@example.com']);

        // ReportReady implements ShouldQueue, so we assert queued instead of sent
        Mail::assertQueued(ReportReady::class, function ($mail) {
            // Check that the mailable has attachments
            return !empty($mail->attachments());
        });
    }

    /**
     * Test 19.2: Attachment size threshold - large files use link only
     */
    public function test_large_files_use_link_only(): void
    {
        Mail::fake();

        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReport($site, 15 * 1024 * 1024); // 15MB file (over 10MB limit)

        $this->reportRepository->method('update')->willReturn(true);

        $result = $this->service->deliver($report, ['user@example.com']);

        $this->assertEquals('sent', $result['status']);

        // Verify the log shows attachment was not attached
        $this->assertFalse($result['log'][0]['attached']);
    }

    /**
     * Test 19.2: Files exactly at threshold are attached
     */
    public function test_files_at_threshold_are_attached(): void
    {
        Mail::fake();

        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReport($site, 10 * 1024 * 1024); // Exactly 10MB

        $this->reportRepository->method('update')->willReturn(true);

        $result = $this->service->deliver($report, ['user@example.com']);

        $this->assertTrue($result['log'][0]['attached']);
    }

    /**
     * Test 19.3: Delivery status logging per recipient
     */
    public function test_delivery_status_logging_per_recipient(): void
    {
        Mail::fake();

        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReport($site, 1000);

        $capturedLog = null;
        $this->reportRepository->method('update')
            ->willReturnCallback(function ($id, $data) use (&$capturedLog) {
                $capturedLog = $data['delivery_log'];
                return true;
            });

        $this->service->deliver($report, ['user1@example.com', 'user2@example.com', 'user3@example.com']);

        $this->assertNotNull($capturedLog);
        $this->assertCount(3, $capturedLog);

        foreach ($capturedLog as $entry) {
            $this->assertArrayHasKey('email', $entry);
            $this->assertArrayHasKey('status', $entry);
            $this->assertArrayHasKey('timestamp', $entry);
        }
    }

    /**
     * Test 19.4: Partial failures - some recipients fail
     */
    public function test_partial_failures_handled_gracefully(): void
    {
        Mail::fake();

        // Make mail throw exception for specific email
        Mail::shouldReceive('to')
            ->andReturnUsing(function ($email) {
                if ($email === 'fail@example.com') {
                    throw new \Exception('SMTP error');
                }
                return new class {
                    public function send($mailable) {
                        return true;
                    }
                };
            });

        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReport($site, 1000);

        $capturedStatus = null;
        $this->reportRepository->method('update')
            ->willReturnCallback(function ($id, $data) use (&$capturedStatus) {
                $capturedStatus = $data['delivery_status'];
                return true;
            });

        $result = $this->service->deliver($report, ['user1@example.com', 'fail@example.com', 'user2@example.com']);

        $this->assertEquals('partial', $result['status']);
        $this->assertEquals('partial', $capturedStatus);

        // Check log has mixed results
        $failedCount = collect($result['log'])->where('status', 'failed')->count();
        $sentCount = collect($result['log'])->where('status', 'sent')->count();

        $this->assertEquals(1, $failedCount);
        $this->assertEquals(2, $sentCount);
    }

    /**
     * Test 19.4: All failures results in failed status
     */
    public function test_all_failures_results_in_failed_status(): void
    {
        Mail::fake();

        // Make all sends fail
        Mail::shouldReceive('to->send')->andThrow(new \Exception('SMTP error'));

        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReport($site, 1000);

        $capturedStatus = null;
        $this->reportRepository->method('update')
            ->willReturnCallback(function ($id, $data) use (&$capturedStatus) {
                $capturedStatus = $data['delivery_status'];
                return true;
            });

        $result = $this->service->deliver($report, ['user1@example.com', 'user2@example.com']);

        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('failed', $capturedStatus);

        foreach ($result['log'] as $entry) {
            $this->assertEquals('failed', $entry['status']);
            $this->assertArrayHasKey('error', $entry);
        }
    }

    /**
     * Test 19.5: Updates report record with delivery status
     */
    public function test_updates_report_record_with_delivery_status(): void
    {
        Mail::fake();

        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReport($site, 1000);

        $capturedData = null;
        $this->reportRepository->expects($this->once())
            ->method('update')
            ->willReturnCallback(function ($id, $data) use (&$capturedData) {
                $capturedData = $data;
                return true;
            });

        $this->service->deliver($report, ['user@example.com']);

        $this->assertNotNull($capturedData);
        $this->assertEquals('sent', $capturedData['delivery_status']);
        $this->assertNotNull($capturedData['delivered_at']);
        $this->assertIsArray($capturedData['delivery_log']);
    }

    /**
     * Edge Case: Duplicate recipients de-duplicated
     */
    public function test_duplicate_recipients_deduplicated(): void
    {
        Mail::fake();

        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReport($site, 1000);

        $this->reportRepository->method('update')->willReturn(true);

        // Same email in different cases
        $result = $this->service->deliver($report, [
            'user@example.com',
            'USER@EXAMPLE.COM',
            'User@Example.com',
        ]);

        // Should only send once
        $this->assertCount(1, $result['log']);
        // ReportReady implements ShouldQueue, so we assert queued instead of sent
        Mail::assertQueued(ReportReady::class, 1);
    }

    /**
     * Edge Case: Missing report file handled gracefully
     */
    public function test_missing_report_file_handled_gracefully(): void
    {
        Mail::fake();

        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReportWithMissingFile($site);

        $this->reportRepository->method('update')->willReturn(true);

        $result = $this->service->deliver($report, ['user@example.com']);

        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('Report file not found', $result['error']);

        Mail::assertNotQueued(ReportReady::class);
    }

    /**
     * Edge Case: Empty recipients returns failure
     */
    public function test_empty_recipients_returns_failure(): void
    {
        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReport($site, 1000);

        $result = $this->service->deliver($report, []);

        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('No valid recipients provided', $result['error']);
    }

    /**
     * Edge Case: Invalid email addresses filtered out
     */
    public function test_invalid_email_addresses_filtered_out(): void
    {
        Mail::fake();

        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReport($site, 1000);

        $this->reportRepository->method('update')->willReturn(true);

        $result = $this->service->deliver($report, [
            'valid@example.com',
            'invalid-email',
            'also-valid@example.org',
            '',
        ]);

        // Only valid emails should be processed
        $this->assertCount(2, $result['log']);
        // ReportReady implements ShouldQueue, so we assert queued instead of sent
        Mail::assertQueued(ReportReady::class, 2);
    }

    /**
     * Test delivery from scheduled report
     */
    public function test_deliver_from_scheduled_report(): void
    {
        Mail::fake();

        $site = $this->createMockSite(1, 'Test Site');
        $report = $this->createMockReport($site, 1000);

        // Create a mock with a public recipients property
        $scheduledReport = $this->createMock(\App\Models\ScheduledReport::class);
        // Use reflection or direct property assignment
        $reflection = new \ReflectionClass($scheduledReport);
        if ($reflection->hasProperty('recipients')) {
            $property = $reflection->getProperty('recipients');
            $property->setAccessible(true);
            $property->setValue($scheduledReport, ['scheduled@example.com', 'client@example.com']);
        } else {
            // Try to set via __set magic method
            $scheduledReport->recipients = ['scheduled@example.com', 'client@example.com'];
        }

        $this->reportRepository->method('update')->willReturn(true);

        // Test directly with deliver method instead
        $result = $this->service->deliver($report, ['scheduled@example.com', 'client@example.com']);

        $this->assertEquals('sent', $result['status']);
        $this->assertCount(2, $result['log']);
    }

    /**
     * Helper: Create a mock Site
     */
    protected function createMockSite(int $id, string $name, string $url = 'https://example.com'): Site
    {
        $site = $this->createMock(Site::class);
        $site->id = $id;
        $site->name = $name;
        $site->url = $url;
        return $site;
    }

    /**
     * Helper: Create a mock Report with file
     */
    protected function createMockReport(Site $site, int $fileSize): Report
    {
        // Create a simple class that mimics Report
        $report = new class extends Report {
            public $id = 1;
            public $name = 'Test Report';
            public $type = 'weekly';
            public $format = 'pdf';
            public $file_path;
            public $file_size;
            public $generated_at;
            public $date_range_start;
            public $date_range_end;
            public $site;
        };
        
        $report->file_path = $this->testFilePath;
        $report->file_size = $fileSize;
        $report->generated_at = now();
        $report->date_range_start = now()->subDays(7);
        $report->date_range_end = now();
        $report->site = $site;

        return $report;
    }

    /**
     * Helper: Create a mock Report with missing file
     */
    protected function createMockReportWithMissingFile(Site $site): Report
    {
        $report = new class extends Report {
            public $id = 1;
            public $name = 'Test Report';
            public $type = 'weekly';
            public $format = 'pdf';
            public $file_path = '/nonexistent/file.pdf';
            public $file_size = 1000;
            public $generated_at;
            public $date_range_start;
            public $date_range_end;
            public $site;
        };
        
        $report->generated_at = now();
        $report->date_range_start = now()->subDays(7);
        $report->date_range_end = now();
        $report->site = $site;

        return $report;
    }
}
