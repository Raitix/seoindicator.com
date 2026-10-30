<?php

namespace Tests\Unit\Mail;

use App\Mail\ReportReady;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('mail')]
#[Group('report-ready')]
class ReportReadyTest extends TestCase
{
    /**
     * Test mailable can be instantiated.
     */
    #[Test]
    public function mailable_can_be_instantiated(): void
    {
        $mailable = $this->createReportReady();

        $this->assertInstanceOf(ReportReady::class, $mailable);
    }

    /**
     * Test envelope has correct subject format.
     */
    #[Test]
    public function envelope_has_correct_subject_format(): void
    {
        $reportData = ['name' => 'Weekly SEO Report'];
        $siteData = ['name' => 'Example Site'];

        $mailable = $this->createReportReady($reportData, $siteData);
        $envelope = $mailable->envelope();

        $this->assertEquals('Weekly SEO Report is ready - Example Site', $envelope->subject);
    }

    /**
     * Test envelope uses fallback values for missing names.
     */
    #[Test]
    public function envelope_uses_fallback_values_for_missing_names(): void
    {
        $mailable = $this->createReportReady([], []);
        $envelope = $mailable->envelope();

        $this->assertEquals('SEO Report is ready - Your Site', $envelope->subject);
    }

    /**
     * Test content definition uses correct views.
     */
    #[Test]
    public function content_uses_correct_views(): void
    {
        $mailable = $this->createReportReady();
        $content = $mailable->content();

        $this->assertEquals('emails.report-ready', $content->view);
        $this->assertEquals('emails.report-ready-text', $content->text);
    }

    /**
     * Test content has all required view data.
     */
    #[Test]
    public function content_has_all_required_view_data(): void
    {
        $reportData = [
            'name' => 'Weekly SEO Report',
            'type' => 'weekly',
            'format' => 'pdf',
            'date_range_start' => '2026-03-01',
            'date_range_end' => '2026-03-07',
            'generated_at' => '2026-03-07T12:00:00Z',
        ];
        $siteData = [
            'name' => 'Example Site',
            'url' => 'https://example.com',
        ];
        $metrics = [
            ['label' => 'Visibility', 'value' => '85%', 'change' => '+5%', 'isPositive' => true],
            ['label' => 'Keywords', 'value' => '234', 'change' => '+12', 'isPositive' => true],
            ['label' => 'Traffic', 'value' => '15.2K', 'change' => '-2%', 'isNegative' => true],
        ];

        $mailable = $this->createReportReady($reportData, $siteData, $metrics);
        $content = $mailable->content();

        $viewData = $content->with;

        $this->assertEquals('Weekly SEO Report', $viewData['reportName']);
        $this->assertEquals('weekly', $viewData['reportType']);
        $this->assertEquals('pdf', $viewData['reportFormat']);
        $this->assertEquals('2026-03-01', $viewData['dateRangeStart']);
        $this->assertEquals('2026-03-07', $viewData['dateRangeEnd']);
        $this->assertEquals('Example Site', $viewData['siteName']);
        $this->assertEquals('https://example.com', $viewData['siteUrl']);
        $this->assertEquals($metrics, $viewData['metrics']);
    }

    /**
     * Test content handles missing optional data gracefully.
     */
    #[Test]
    public function content_handles_missing_optional_data_gracefully(): void
    {
        $mailable = $this->createReportReady([], [], []);
        $content = $mailable->content();
        $viewData = $content->with;

        $this->assertEquals('SEO Report', $viewData['reportName']);
        $this->assertEquals('weekly', $viewData['reportType']);
        $this->assertEquals('pdf', $viewData['reportFormat']);
        $this->assertNull($viewData['dateRangeStart']);
        $this->assertNull($viewData['dateRangeEnd']);
        $this->assertEquals('Your Site', $viewData['siteName']);
        $this->assertNull($viewData['siteUrl']);
        $this->assertEmpty($viewData['metrics']);
    }

    /**
     * Test attachments returns empty array when no file path provided.
     */
    #[Test]
    public function attachments_returns_empty_array_when_no_file_path(): void
    {
        $mailable = $this->createReportReady();
        $attachments = $mailable->attachments();

        $this->assertEmpty($attachments);
    }

    /**
     * Test attachments returns empty array when file does not exist.
     */
    #[Test]
    public function attachments_returns_empty_array_when_file_not_exists(): void
    {
        $reportData = [
            'file_path' => '/nonexistent/report.pdf',
            'format' => 'pdf',
        ];

        $mailable = $this->createReportReady($reportData);
        $attachments = $mailable->attachments();

        $this->assertEmpty($attachments);
    }

    /**
     * Test attachments includes existing file.
     */
    #[Test]
    public function attachments_includes_existing_file(): void
    {
        // Create a temporary PDF file
        $tempFile = tempnam(sys_get_temp_dir(), 'report_') . '.pdf';
        file_put_contents($tempFile, '%PDF-1.4 test content');

        try {
            $reportData = [
                'file_path' => $tempFile,
                'file_name' => 'custom-report.pdf',
                'format' => 'pdf',
            ];

            $mailable = $this->createReportReady($reportData);
            $attachments = $mailable->attachments();

            $this->assertCount(1, $attachments);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test attachments uses custom file name when provided.
     */
    #[Test]
    public function attachments_uses_custom_file_name(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'report_') . '.pdf';
        file_put_contents($tempFile, '%PDF-1.4 test content');

        try {
            $reportData = [
                'file_path' => $tempFile,
                'file_name' => 'My-Custom-Report-2026.pdf',
                'format' => 'pdf',
            ];

            $mailable = $this->createReportReady($reportData);
            $attachments = $mailable->attachments();

            $this->assertCount(1, $attachments);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test attachments handles CSV format.
     */
    #[Test]
    public function attachments_handles_csv_format(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'report_') . '.csv';
        file_put_contents($tempFile, "header1,header2\nvalue1,value2");

        try {
            $reportData = [
                'file_path' => $tempFile,
                'format' => 'csv',
            ];

            $mailable = $this->createReportReady($reportData);
            $attachments = $mailable->attachments();

            $this->assertCount(1, $attachments);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test generate file name creates expected format.
     */
    #[Test]
    public function generate_file_name_creates_expected_format(): void
    {
        $reportData = [
            'name' => 'Weekly SEO Report',
            'format' => 'pdf',
        ];

        $mailable = $this->createReportReady($reportData);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($mailable);
        $method = $reflection->getMethod('generateFileName');
        $method->setAccessible(true);
        $fileName = $method->invoke($mailable);

        // Should match pattern: weekly-seo-report-YYYY-MM-DD.pdf
        $this->assertMatchesRegularExpression('/^weekly-seo-report-\d{4}-\d{2}-\d{2}\.pdf$/', $fileName);
    }

    /**
     * Test generate file name handles special characters.
     */
    #[Test]
    public function generate_file_name_handles_special_characters(): void
    {
        $reportData = [
            'name' => 'Report: SEO & Marketing! @2026',
            'format' => 'pdf',
        ];

        $mailable = $this->createReportReady($reportData);

        $reflection = new \ReflectionClass($mailable);
        $method = $reflection->getMethod('generateFileName');
        $method->setAccessible(true);
        $fileName = $method->invoke($mailable);

        // Special characters should be replaced with hyphens
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+-\d{4}-\d{2}-\d{2}\.pdf$/', $fileName);
        $this->assertStringNotContainsString(':', $fileName);
        $this->assertStringNotContainsString('&', $fileName);
        $this->assertStringNotContainsString('!', $fileName);
        $this->assertStringNotContainsString('@', $fileName);
    }

    /**
     * Test MIME type for PDF format.
     */
    #[Test]
    public function mime_type_for_pdf_format(): void
    {
        $mailable = $this->createReportReady(['format' => 'pdf']);

        $reflection = new \ReflectionClass($mailable);
        $method = $reflection->getMethod('getMimeType');
        $method->setAccessible(true);
        $mimeType = $method->invoke($mailable, 'pdf');

        $this->assertEquals('application/pdf', $mimeType);
    }

    /**
     * Test MIME type for CSV format.
     */
    #[Test]
    public function mime_type_for_csv_format(): void
    {
        $mailable = $this->createReportReady();

        $reflection = new \ReflectionClass($mailable);
        $method = $reflection->getMethod('getMimeType');
        $method->setAccessible(true);
        $mimeType = $method->invoke($mailable, 'csv');

        $this->assertEquals('text/csv', $mimeType);
    }

    /**
     * Test MIME type fallback for unknown format.
     */
    #[Test]
    public function mime_type_fallback_for_unknown_format(): void
    {
        $mailable = $this->createReportReady();

        $reflection = new \ReflectionClass($mailable);
        $method = $reflection->getMethod('getMimeType');
        $method->setAccessible(true);
        $mimeType = $method->invoke($mailable, 'unknown');

        $this->assertEquals('application/octet-stream', $mimeType);
    }

    /**
     * Test mailable implements ShouldQueue.
     */
    #[Test]
    public function mailable_implements_should_queue(): void
    {
        $mailable = $this->createReportReady();

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mailable);
    }

    /**
     * Test build method returns static instance.
     */
    #[Test]
    public function build_method_returns_static_instance(): void
    {
        $mailable = $this->createReportReady();
        $result = $mailable->build();

        $this->assertSame($mailable, $result);
    }

    /**
     * Test build sets correct view data.
     */
    #[Test]
    public function build_sets_correct_view_data(): void
    {
        $reportData = [
            'name' => 'Test Report',
            'type' => 'monthly',
            'format' => 'csv',
        ];
        $siteData = ['name' => 'Test Site'];

        $mailable = $this->createReportReady($reportData, $siteData);
        $mailable->build();

        $viewData = $mailable->viewData;

        $this->assertEquals('Test Report', $viewData['reportName']);
        $this->assertEquals('monthly', $viewData['reportType']);
        $this->assertEquals('csv', $viewData['reportFormat']);
        $this->assertEquals('Test Site', $viewData['siteName']);
    }

    /**
     * Test metrics with positive change.
     */
    #[Test]
    public function metrics_with_positive_change(): void
    {
        $metrics = [
            ['label' => 'Visibility', 'value' => '85%', 'change' => '+5%', 'isPositive' => true],
        ];

        $mailable = $this->createReportReady([], [], $metrics);
        $content = $mailable->content();

        $this->assertTrue($content->with['metrics'][0]['isPositive']);
    }

    /**
     * Test metrics with negative change.
     */
    #[Test]
    public function metrics_with_negative_change(): void
    {
        $metrics = [
            ['label' => 'Traffic', 'value' => '15K', 'change' => '-10%', 'isNegative' => true],
        ];

        $mailable = $this->createReportReady([], [], $metrics);
        $content = $mailable->content();

        $this->assertTrue($content->with['metrics'][0]['isNegative']);
    }

    /**
     * Test multiple metrics are preserved in order.
     */
    #[Test]
    public function multiple_metrics_preserved_in_order(): void
    {
        $metrics = [
            ['label' => 'Visibility', 'value' => '85%'],
            ['label' => 'Keywords', 'value' => '234'],
            ['label' => 'Traffic', 'value' => '15.2K'],
        ];

        $mailable = $this->createReportReady([], [], $metrics);
        $content = $mailable->content();

        $this->assertCount(3, $content->with['metrics']);
        $this->assertEquals('Visibility', $content->with['metrics'][0]['label']);
        $this->assertEquals('Keywords', $content->with['metrics'][1]['label']);
        $this->assertEquals('Traffic', $content->with['metrics'][2]['label']);
    }

    /**
     * Test download URL is included in view data.
     */
    #[Test]
    public function download_url_included_in_view_data(): void
    {
        $downloadUrl = 'https://example.com/reports/download/abc123';
        $mailable = $this->createReportReady([], [], [], $downloadUrl);
        $content = $mailable->content();

        $this->assertEquals($downloadUrl, $content->with['downloadUrl']);
    }

    /**
     * Test preview URL is optional.
     */
    #[Test]
    public function preview_url_is_optional(): void
    {
        // Without preview URL
        $mailable = $this->createReportReady([], [], [], 'https://download.url', null);
        $content = $mailable->content();

        $this->assertNull($content->with['previewUrl']);

        // With preview URL
        $previewUrl = 'https://example.com/dashboard/reports/123';
        $mailable = $this->createReportReady([], [], [], 'https://download.url', $previewUrl);
        $content = $mailable->content();

        $this->assertEquals($previewUrl, $content->with['previewUrl']);
    }

    /**
     * Test unsubscribe URL is included in view data.
     */
    #[Test]
    public function unsubscribe_url_included_in_view_data(): void
    {
        $unsubscribeUrl = 'https://example.com/unsubscribe/report/abc123';
        $mailable = new ReportReady(
            [],
            [],
            [],
            'https://download.url',
            $unsubscribeUrl
        );
        $content = $mailable->content();

        $this->assertEquals($unsubscribeUrl, $content->with['unsubscribeUrl']);
    }

    /**
     * Test date range formatting.
     */
    #[Test]
    public function date_range_formatting(): void
    {
        $reportData = [
            'date_range_start' => '2026-03-01',
            'date_range_end' => '2026-03-07',
        ];

        $mailable = $this->createReportReady($reportData);
        $content = $mailable->content();

        $this->assertEquals('2026-03-01', $content->with['dateRangeStart']);
        $this->assertEquals('2026-03-07', $content->with['dateRangeEnd']);
    }

    /**
     * Test generated at timestamp uses provided value.
     */
    #[Test]
    public function generated_at_uses_provided_value(): void
    {
        $generatedAt = '2026-03-07T15:30:00Z';
        $reportData = ['generated_at' => $generatedAt];

        $mailable = $this->createReportReady($reportData);
        $content = $mailable->content();

        $this->assertEquals($generatedAt, $content->with['generatedAt']);
    }

    /**
     * Create a ReportReady mailable instance for testing.
     */
    protected function createReportReady(
        array $reportData = [],
        array $siteData = [],
        array $metrics = [],
        string $downloadUrl = 'https://example.com/download/report.pdf',
        ?string $previewUrl = null
    ): ReportReady {
        return new ReportReady(
            $reportData,
            $siteData,
            $metrics,
            $downloadUrl,
            'https://example.com/unsubscribe/report/abc123',
            $previewUrl
        );
    }
}
