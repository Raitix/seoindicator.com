<?php

namespace Tests\Feature\Mail;

use App\Mail\ReportReady;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('mail')]
#[Group('report-ready')]
#[Group('template-rendering')]
class ReportReadyTemplateRenderingTest extends TestCase
{
    /**
     * Test HTML template renders without errors.
     */
    #[Test]
    public function html_template_renders_without_errors(): void
    {
        $mailable = $this->createReportReady();

        $rendered = $mailable->render();

        $this->assertStringContainsString('DOCTYPE html', $rendered);
        $this->assertStringContainsString('</html>', $rendered);
    }

    /**
     * Test HTML template contains report name.
     */
    #[Test]
    public function html_template_contains_report_name(): void
    {
        $reportData = ['name' => 'Weekly SEO Report'];
        $mailable = $this->createReportReady($reportData);

        $rendered = $mailable->render();

        $this->assertStringContainsString('Weekly SEO Report', $rendered);
    }

    /**
     * Test HTML template contains site name.
     */
    #[Test]
    public function html_template_contains_site_name(): void
    {
        $siteData = ['name' => 'Example Site'];
        $mailable = $this->createReportReady([], $siteData);

        $rendered = $mailable->render();

        $this->assertStringContainsString('Example Site', $rendered);
    }

    /**
     * Test HTML template contains date range.
     */
    #[Test]
    public function html_template_contains_date_range(): void
    {
        $reportData = [
            'date_range_start' => '2026-03-01',
            'date_range_end' => '2026-03-07',
        ];
        $mailable = $this->createReportReady($reportData);

        $rendered = $mailable->render();

        $this->assertStringContainsString('2026-03-01', $rendered);
        $this->assertStringContainsString('2026-03-07', $rendered);
        $this->assertStringContainsString('Report Period', $rendered);
    }

    /**
     * Test HTML template contains download button.
     */
    #[Test]
    public function html_template_contains_download_button(): void
    {
        $downloadUrl = 'https://example.com/reports/download/abc123';
        $mailable = $this->createReportReady([], [], [], $downloadUrl);

        $rendered = $mailable->render();

        $this->assertStringContainsString($downloadUrl, $rendered);
        $this->assertStringContainsString('Download', $rendered);
    }

    /**
     * Test HTML template contains preview link when provided.
     */
    #[Test]
    public function html_template_contains_preview_link_when_provided(): void
    {
        $previewUrl = 'https://example.com/dashboard/reports/123';
        $mailable = $this->createReportReady([], [], [], 'https://download.url', $previewUrl);

        $rendered = $mailable->render();

        $this->assertStringContainsString($previewUrl, $rendered);
        $this->assertStringContainsString('View in Dashboard', $rendered);
    }

    /**
     * Test HTML template omits preview link when not provided.
     */
    #[Test]
    public function html_template_omits_preview_link_when_not_provided(): void
    {
        $mailable = $this->createReportReady([], [], [], 'https://download.url', null);

        $rendered = $mailable->render();

        $this->assertStringNotContainsString('View in Dashboard', $rendered);
    }

    /**
     * Test HTML template contains unsubscribe link.
     */
    #[Test]
    public function html_template_contains_unsubscribe_link(): void
    {
        $unsubscribeUrl = 'https://example.com/unsubscribe/report/abc123';
        $mailable = new ReportReady(
            [],
            [],
            [],
            'https://download.url',
            $unsubscribeUrl
        );

        $rendered = $mailable->render();

        $this->assertStringContainsString($unsubscribeUrl, $rendered);
        $this->assertStringContainsString('Unsubscribe', $rendered);
    }

    /**
     * Test HTML template contains metrics when provided.
     */
    #[Test]
    public function html_template_contains_metrics_when_provided(): void
    {
        $metrics = [
            ['label' => 'Visibility', 'value' => '85%', 'change' => '+5%', 'isPositive' => true],
            ['label' => 'Keywords', 'value' => '234', 'change' => '+12', 'isPositive' => true],
            ['label' => 'Traffic', 'value' => '15.2K', 'change' => '-2%', 'isNegative' => true],
        ];
        $mailable = $this->createReportReady([], [], $metrics);

        $rendered = $mailable->render();

        $this->assertStringContainsString('Key Metrics Preview', $rendered);
        $this->assertStringContainsString('Visibility', $rendered);
        $this->assertStringContainsString('85%', $rendered);
        $this->assertStringContainsString('Keywords', $rendered);
        $this->assertStringContainsString('234', $rendered);
        $this->assertStringContainsString('Traffic', $rendered);
        $this->assertStringContainsString('15.2K', $rendered);
    }

    /**
     * Test HTML template omits metrics section when empty.
     */
    #[Test]
    public function html_template_omits_metrics_section_when_empty(): void
    {
        $mailable = $this->createReportReady([], [], []);

        $rendered = $mailable->render();

        $this->assertStringNotContainsString('Key Metrics Preview', $rendered);
    }

    /**
     * Test HTML template contains positive change styling.
     */
    #[Test]
    public function html_template_contains_positive_change_styling(): void
    {
        $metrics = [
            ['label' => 'Visibility', 'value' => '85%', 'change' => '+5%', 'isPositive' => true],
        ];
        $mailable = $this->createReportReady([], [], $metrics);

        $rendered = $mailable->render();

        $this->assertStringContainsString('+5%', $rendered);
        $this->assertStringContainsString('positive', $rendered);
    }

    /**
     * Test HTML template contains negative change styling.
     */
    #[Test]
    public function html_template_contains_negative_change_styling(): void
    {
        $metrics = [
            ['label' => 'Traffic', 'value' => '15K', 'change' => '-10%', 'isNegative' => true],
        ];
        $mailable = $this->createReportReady([], [], $metrics);

        $rendered = $mailable->render();

        $this->assertStringContainsString('-10%', $rendered);
        $this->assertStringContainsString('negative', $rendered);
    }

    /**
     * Test HTML template contains site URL when provided.
     */
    #[Test]
    public function html_template_contains_site_url_when_provided(): void
    {
        $siteData = [
            'name' => 'Example Site',
            'url' => 'https://example.com',
        ];
        $mailable = $this->createReportReady([], $siteData);

        $rendered = $mailable->render();

        $this->assertStringContainsString('https://example.com', $rendered);
    }

    /**
     * Test HTML template contains format indicator.
     */
    #[Test]
    public function html_template_contains_format_indicator(): void
    {
        $reportData = ['format' => 'pdf'];
        $mailable = $this->createReportReady($reportData);

        $rendered = $mailable->render();

        $this->assertStringContainsString('PDF', $rendered);
    }

    /**
     * Test HTML template handles CSV format.
     */
    #[Test]
    public function html_template_handles_csv_format(): void
    {
        $reportData = ['format' => 'csv'];
        $mailable = $this->createReportReady($reportData);

        $rendered = $mailable->render();

        $this->assertStringContainsString('CSV', $rendered);
    }

    /**
     * Test text template renders without errors.
     */
    #[Test]
    public function text_template_renders_without_errors(): void
    {
        $mailable = $this->createReportReady();

        // Render text view directly
        $rendered = view('emails.report-ready-text', [
            'reportName' => 'Test Report',
            'reportType' => 'weekly',
            'reportFormat' => 'pdf',
            'dateRangeStart' => '2026-03-01',
            'dateRangeEnd' => '2026-03-07',
            'siteName' => 'Example Site',
            'siteUrl' => 'https://example.com',
            'metrics' => [],
            'downloadUrl' => 'https://download.url',
            'previewUrl' => null,
            'unsubscribeUrl' => 'https://unsubscribe.url',
            'generatedAt' => now()->toIso8601String(),
        ])->render();

        $this->assertNotEmpty($rendered);
        $this->assertStringContainsString('Test Report', $rendered);
    }

    /**
     * Test text template contains essential information.
     */
    #[Test]
    public function text_template_contains_essential_information(): void
    {
        $reportData = [
            'name' => 'Weekly Report',
            'date_range_start' => '2026-03-01',
            'date_range_end' => '2026-03-07',
        ];
        $siteData = ['name' => 'Example Site'];
        $metrics = [
            ['label' => 'Visibility', 'value' => '85%'],
        ];

        $rendered = view('emails.report-ready-text', [
            'reportName' => 'Weekly Report',
            'reportType' => 'weekly',
            'reportFormat' => 'pdf',
            'dateRangeStart' => '2026-03-01',
            'dateRangeEnd' => '2026-03-07',
            'siteName' => 'Example Site',
            'siteUrl' => 'https://example.com',
            'metrics' => $metrics,
            'downloadUrl' => 'https://download.url',
            'previewUrl' => 'https://preview.url',
            'unsubscribeUrl' => 'https://unsubscribe.url',
            'generatedAt' => now()->toIso8601String(),
        ])->render();

        $this->assertStringContainsString('Weekly Report', $rendered);
        $this->assertStringContainsString('Example Site', $rendered);
        $this->assertStringContainsString('2026-03-01', $rendered);
        $this->assertStringContainsString('2026-03-07', $rendered);
        $this->assertStringContainsString('https://download.url', $rendered);
        $this->assertStringContainsString('unsubscribe', $rendered);
        $this->assertStringContainsString('Visibility', $rendered);
        $this->assertStringContainsString('85%', $rendered);
    }

    /**
     * Test dark mode CSS is present.
     */
    #[Test]
    public function dark_mode_css_is_present(): void
    {
        $mailable = $this->createReportReady();
        $rendered = $mailable->render();

        $this->assertStringContainsString('prefers-color-scheme: dark', $rendered);
    }

    /**
     * Test responsive CSS is present.
     */
    #[Test]
    public function responsive_css_is_present(): void
    {
        $mailable = $this->createReportReady();
        $rendered = $mailable->render();

        $this->assertStringContainsString('@media only screen and (max-width: 600px)', $rendered);
    }

    /**
     * Test email contains proper meta tags.
     */
    #[Test]
    public function email_contains_proper_meta_tags(): void
    {
        $mailable = $this->createReportReady();
        $rendered = $mailable->render();

        $this->assertStringContainsString('<meta charset="utf-8">', $rendered);
        $this->assertStringContainsString('<meta name="viewport"', $rendered);
        $this->assertStringContainsString('X-UA-Compatible', $rendered);
    }

    /**
     * Test email contains inline styles for compatibility.
     */
    #[Test]
    public function email_contains_inline_styles_for_compatibility(): void
    {
        $mailable = $this->createReportReady();
        $rendered = $mailable->render();

        // Check for style attribute presence (email clients need inline styles)
        $this->assertMatchesRegularExpression('/style="[^"]*"/', $rendered);
    }

    /**
     * Test email contains table-based layout for Outlook.
     */
    #[Test]
    public function email_contains_table_based_layout_for_outlook(): void
    {
        $mailable = $this->createReportReady();
        $rendered = $mailable->render();

        $this->assertStringContainsString('role="presentation"', $rendered);
        $this->assertStringContainsString('cellspacing="0"', $rendered);
        $this->assertStringContainsString('cellpadding="0"', $rendered);
    }

    /**
     * Test email contains MSO conditional comments.
     */
    #[Test]
    public function email_contains_mso_conditional_comments(): void
    {
        $mailable = $this->createReportReady();
        $rendered = $mailable->render();

        $this->assertStringContainsString('<!--[if mso]>', $rendered);
    }

    /**
     * Test HTML template handles special characters in content.
     */
    #[Test]
    public function html_template_handles_special_characters(): void
    {
        $reportData = ['name' => "Report: SEO & Marketing <Test>"];
        $siteData = ['name' => "O'Brien's Site"];

        $mailable = $this->createReportReady($reportData, $siteData);
        $rendered = $mailable->render();

        // Should contain the content without breaking HTML structure
        $this->assertStringContainsString('</html>', $rendered);
    }

    /**
     * Test generated timestamp formatting.
     */
    #[Test]
    public function generated_timestamp_formatting(): void
    {
        $generatedAt = '2026-03-07T15:30:00Z';
        $reportData = ['generated_at' => $generatedAt];

        $mailable = $this->createReportReady($reportData);
        $rendered = $mailable->render();

        // Should contain formatted date
        $this->assertStringContainsString('2026', $rendered);
        $this->assertStringContainsString('generated', $rendered);
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
