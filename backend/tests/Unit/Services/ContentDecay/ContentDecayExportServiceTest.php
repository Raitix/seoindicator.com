<?php

namespace Tests\Unit\Services\ContentDecay;

use Tests\TestCase;
use App\Services\ContentDecay\ContentDecayExportService;
use App\Services\ContentDecay\Contracts\ContentDecayExportServiceInterface;
use App\Models\ContentDecayProject;
use App\Models\ContentDecayCandidate;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

/**
 * Unit tests for ContentDecayExportService.
 *
 * TASK-36-8: Content Decay Export Service Tests
 *
 * Tests cover:
 * - Service instantiation and interface implementation
 * - CSV export generation with all required columns
 * - PDF export generation with summary and details
 * - XLS export generation
 * - Filtering by status, severity, date range
 * - AI brief generation and retrieval
 * - Export filename generation
 * - Error handling for invalid inputs
 * - Security (XSS prevention, SQL injection)
 *
 * @see TASK-36-8
 * @see \App\Services\ContentDecay\ContentDecayExportService
 */
class ContentDecayExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContentDecayExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContentDecayExportService();
    }

    // ============================================
    // SERVICE INSTANTIATION TESTS
    // ============================================

    /**
     * Test EXP-001: Service can be instantiated.
     */
    public function test_service_can_be_instantiated(): void
    {
        $service = new ContentDecayExportService();

        $this->assertInstanceOf(ContentDecayExportService::class, $service);
    }

    /**
     * Test EXP-002: Service implements interface.
     */
    public function test_service_implements_interface(): void
    {
        $this->assertInstanceOf(ContentDecayExportServiceInterface::class, $this->service);
    }

    /**
     * Test EXP-003: Service has required methods.
     */
    public function test_service_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, 'exportQueue'));
        $this->assertTrue(method_exists($this->service, 'generateAiBrief'));
        $this->assertTrue(method_exists($this->service, 'getExportFormats'));
        $this->assertTrue(method_exists($this->service, 'isAiBriefAvailable'));
        $this->assertTrue(method_exists($this->service, 'getAiBrief'));
        $this->assertTrue(method_exists($this->service, 'getExportFilename'));
    }

    // ============================================
    // GET EXPORT FORMATS TESTS
    // ============================================

    /**
     * Test EXP-010: getExportFormats returns array.
     */
    public function test_get_export_formats_returns_array(): void
    {
        $formats = $this->service->getExportFormats();

        $this->assertIsArray($formats);
    }

    /**
     * Test EXP-011: getExportFormats contains csv.
     */
    public function test_get_export_formats_contains_csv(): void
    {
        $formats = $this->service->getExportFormats();

        $this->assertContains('csv', $formats);
    }

    /**
     * Test EXP-012: getExportFormats contains pdf.
     */
    public function test_get_export_formats_contains_pdf(): void
    {
        $formats = $this->service->getExportFormats();

        $this->assertContains('pdf', $formats);
    }

    /**
     * Test EXP-013: getExportFormats contains xls.
     */
    public function test_get_export_formats_contains_xls(): void
    {
        $formats = $this->service->getExportFormats();

        $this->assertContains('xls', $formats);
    }

    /**
     * Test EXP-014: getExportFormats returns exactly three formats.
     */
    public function test_get_export_formats_returns_three_formats(): void
    {
        $formats = $this->service->getExportFormats();

        $this->assertCount(3, $formats);
        $this->assertEquals(['csv', 'pdf', 'xls'], $formats);
    }

    // ============================================
    // AI BRIEF AVAILABILITY TESTS
    // ============================================

    /**
     * Test EXP-020: isAiBriefAvailable returns bool.
     */
    public function test_is_ai_brief_available_returns_bool(): void
    {
        $result = $this->service->isAiBriefAvailable();

        $this->assertIsBool($result);
    }

    /**
     * Test EXP-021: isAiBriefAvailable true when OpenAI key set.
     */
    public function test_is_ai_brief_available_true_when_openai_key_set(): void
    {
        Config::set('services.openai.api_key', 'test-key');
        Config::set('services.anthropic.api_key', null);

        $this->assertTrue($this->service->isAiBriefAvailable());
    }

    /**
     * Test EXP-022: isAiBriefAvailable true when Anthropic key set.
     */
    public function test_is_ai_brief_available_true_when_anthropic_key_set(): void
    {
        Config::set('services.openai.api_key', null);
        Config::set('services.anthropic.api_key', 'test-key');

        $this->assertTrue($this->service->isAiBriefAvailable());
    }

    /**
     * Test EXP-023: isAiBriefAvailable false when no keys set.
     */
    public function test_is_ai_brief_available_false_when_no_keys_set(): void
    {
        Config::set('services.openai.api_key', null);
        Config::set('services.anthropic.api_key', null);

        $this->assertFalse($this->service->isAiBriefAvailable());
    }

    // ============================================
    // EXPORT FILENAME TESTS
    // ============================================

    /**
     * Test EXP-030: getExportFilename generates correct format.
     */
    public function test_get_export_filename_generates_correct_format(): void
    {
        $filename = $this->service->getExportFilename(1, 'csv');

        $this->assertStringContainsString('content_decay_queue', $filename);
        $this->assertStringContainsString('site_1', $filename);
        $this->assertStringEndsWith('.csv', $filename);
    }

    /**
     * Test EXP-031: getExportFilename includes date.
     */
    public function test_get_export_filename_includes_date(): void
    {
        $filename = $this->service->getExportFilename(1, 'csv');

        // Date format: Y-m-d_His
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}_\d{6}/', $filename);
    }

    /**
     * Test EXP-032: getExportFilename uses correct extension for csv.
     */
    public function test_get_export_filename_uses_correct_extension_for_csv(): void
    {
        $filename = $this->service->getExportFilename(1, 'csv');

        $this->assertStringEndsWith('.csv', $filename);
    }

    /**
     * Test EXP-033: getExportFilename uses correct extension for pdf.
     */
    public function test_get_export_filename_uses_correct_extension_for_pdf(): void
    {
        $filename = $this->service->getExportFilename(1, 'pdf');

        $this->assertStringEndsWith('.pdf', $filename);
    }

    /**
     * Test EXP-034: getExportFilename uses correct extension for xls.
     */
    public function test_get_export_filename_uses_correct_extension_for_xls(): void
    {
        $filename = $this->service->getExportFilename(1, 'xls');

        $this->assertStringEndsWith('.xls', $filename);
    }

    /**
     * Test EXP-035: getExportFilename uses recovery_report prefix for recovery.
     */
    public function test_get_export_filename_uses_recovery_report_prefix(): void
    {
        $filename = $this->service->getExportFilename(1, 'pdf', ['recovery_report' => true]);

        $this->assertStringContainsString('recovery_report', $filename);
        $this->assertStringNotContainsString('content_decay_queue', $filename);
    }

    /**
     * Test EXP-036: getExportFilename with different site IDs.
     */
    public function test_get_export_filename_with_different_site_ids(): void
    {
        $filename1 = $this->service->getExportFilename(123, 'csv');
        $filename2 = $this->service->getExportFilename(456, 'csv');

        $this->assertStringContainsString('site_123', $filename1);
        $this->assertStringContainsString('site_456', $filename2);
    }

    // ============================================
    // EXPORT QUEUE - FORMAT VALIDATION TESTS
    // ============================================

    /**
     * Test EXP-040: exportQueue throws for invalid format.
     */
    public function test_export_queue_throws_for_invalid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid export format');

        $this->service->exportQueue(1, 'docx');
    }

    /**
     * Test EXP-041: exportQueue throws for missing project.
     */
    public function test_export_queue_throws_for_missing_project(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No content decay project found');

        $this->service->exportQueue(99999, 'csv');
    }

    /**
     * Test EXP-042: exportQueue accepts csv format.
     */
    public function test_export_queue_accepts_csv_format(): void
    {
        $project = ContentDecayProject::factory()->create();

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $this->assertNotEmpty($path);
        $this->assertStringEndsWith('.csv', $path);
    }

    /**
     * Test EXP-043: exportQueue accepts pdf format.
     */
    public function test_export_queue_accepts_pdf_format(): void
    {
        $project = ContentDecayProject::factory()->create();

        $path = $this->service->exportQueue($project->site_id, 'pdf');

        $this->assertNotEmpty($path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    /**
     * Test EXP-044: exportQueue accepts xls format.
     */
    public function test_export_queue_accepts_xls_format(): void
    {
        $project = ContentDecayProject::factory()->create();

        $path = $this->service->exportQueue($project->site_id, 'xls');

        $this->assertNotEmpty($path);
        $this->assertStringEndsWith('.xls', $path);
    }

    // ============================================
    // CSV EXPORT TESTS
    // ============================================

    /**
     * Test EXP-050: CSV export creates file.
     */
    public function test_csv_export_creates_file(): void
    {
        $project = ContentDecayProject::factory()->create();

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $this->assertFileExists($path);
        
        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-051: CSV export has correct headers.
     */
    public function test_csv_export_has_correct_headers(): void
    {
        $project = ContentDecayProject::factory()->create();

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        // Remove UTF-8 BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $lines = explode("\n", $content);
        $headers = str_getcsv($lines[0]);

        $this->assertContains('URL', $headers);
        $this->assertContains('Title', $headers);
        $this->assertContains('Page Type', $headers);
        $this->assertContains('Severity', $headers);
        $this->assertContains('Decay Score', $headers);
        $this->assertContains('Confidence', $headers);
        $this->assertContains('Primary Reason', $headers);
        $this->assertContains('All Reason Codes', $headers);
        $this->assertContains('Lost Clicks', $headers);
        $this->assertContains('Recovery Upside', $headers);
        $this->assertContains('Status', $headers);
        $this->assertContains('First Detected', $headers);
        $this->assertContains('Last Detected', $headers);
        $this->assertContains('Planned Date', $headers);
        $this->assertContains('Started Date', $headers);
        $this->assertContains('Refreshed Date', $headers);
        $this->assertContains('Recovered Date', $headers);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-052: CSV export includes candidate data.
     */
    public function test_csv_export_includes_candidate_data(): void
    {
        $project = ContentDecayProject::factory()->create();
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test-page',
            'page_title' => 'Test Page Title',
            'severity' => 'high',
            'decay_score' => 75.50,
            'status' => 'backlog',
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        
        $this->assertStringContainsString('https://example.com/test-page', $content);
        $this->assertStringContainsString('Test Page Title', $content);
        $this->assertStringContainsString('high', $content);
        $this->assertStringContainsString('backlog', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-053: CSV export with multiple candidates.
     */
    public function test_csv_export_with_multiple_candidates(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->count(5)->create([
            'project_id' => $project->id,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $lines = explode("\n", trim($content));
        
        // Header + 5 data rows
        $this->assertCount(6, $lines);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-054: CSV export empty queue has headers only.
     */
    public function test_csv_export_empty_queue_has_headers_only(): void
    {
        $project = ContentDecayProject::factory()->create();

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $lines = explode("\n", trim($content));
        
        // Header only, no data rows
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('URL', $lines[0]);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-055: CSV export contains decay score.
     */
    public function test_csv_export_contains_decay_score(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'decay_score' => 85.25,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $this->assertStringContainsString('85.25', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-056: CSV export contains lost clicks estimate.
     */
    public function test_csv_export_contains_lost_clicks_estimate(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'lost_click_estimate' => 450,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $this->assertStringContainsString('450', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-057: CSV export contains recovery upside estimate.
     */
    public function test_csv_export_contains_recovery_upside_estimate(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'recovery_upside_estimate' => 1200,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $this->assertStringContainsString('1200', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-058: CSV export contains confidence.
     */
    public function test_csv_export_contains_confidence(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'detection_confidence' => 95.50,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $this->assertStringContainsString('95.50', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-059: CSV export contains all reason codes.
     */
    public function test_csv_export_contains_all_reason_codes(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'reason_codes' => ['clicks_down', 'impressions_down', 'ctr_down'],
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $this->assertStringContainsString('clicks_down', $content);
        $this->assertStringContainsString('impressions_down', $content);
        $this->assertStringContainsString('ctr_down', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-060: CSV export contains planned date.
     */
    public function test_csv_export_contains_planned_date(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'planned',
            'planned_at' => now()->subDays(2),
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $headers = str_getcsv($lines[0]);
        $dataRow = str_getcsv($lines[1]);

        // Find Planned Date column index
        $plannedDateIndex = array_search('Planned Date', $headers);
        $this->assertNotFalse($plannedDateIndex);
        $this->assertNotEmpty($dataRow[$plannedDateIndex]);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-061: CSV export contains recovered date.
     */
    public function test_csv_export_contains_recovered_date(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'recovered',
            'recovered_at' => now(),
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $headers = str_getcsv($lines[0]);
        $dataRow = str_getcsv($lines[1]);

        // Find Recovered Date column index
        $recoveredDateIndex = array_search('Recovered Date', $headers);
        $this->assertNotFalse($recoveredDateIndex);
        $this->assertNotEmpty($dataRow[$recoveredDateIndex]);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // ============================================
    // PDF EXPORT TESTS
    // ============================================

    /**
     * Test EXP-060: PDF export creates file.
     */
    public function test_pdf_export_creates_file(): void
    {
        $project = ContentDecayProject::factory()->create();

        $path = $this->service->exportQueue($project->site_id, 'pdf');

        $this->assertFileExists($path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-061: PDF export contains candidate count.
     */
    public function test_pdf_export_contains_candidate_count(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->count(3)->create([
            'project_id' => $project->id,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'pdf');

        $content = file_get_contents($path);
        $this->assertStringContainsString('3 candidates', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-062: PDF export with recovery report flag.
     */
    public function test_pdf_export_with_recovery_report_flag(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->count(2)->create([
            'project_id' => $project->id,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'pdf', ['recovery_report' => true]);

        $this->assertFileExists($path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // ============================================
    // XLS EXPORT TESTS
    // ============================================

    /**
     * Test EXP-070: XLS export creates file.
     */
    public function test_xls_export_creates_file(): void
    {
        $project = ContentDecayProject::factory()->create();

        $path = $this->service->exportQueue($project->site_id, 'xls');

        $this->assertFileExists($path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-071: XLS export contains candidate count.
     */
    public function test_xls_export_contains_candidate_count(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->count(4)->create([
            'project_id' => $project->id,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'xls');

        $content = file_get_contents($path);
        $this->assertStringContainsString('4 candidates', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // ============================================
    // FILTERING TESTS
    // ============================================

    /**
     * Test EXP-080: Filter by status.
     */
    public function test_filter_by_status(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'page_url' => 'https://example.com/backlog',
        ]);
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'planned',
            'page_url' => 'https://example.com/planned',
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv', ['status' => 'planned']);

        $content = file_get_contents($path);
        $this->assertStringContainsString('https://example.com/planned', $content);
        $this->assertStringNotContainsString('https://example.com/backlog', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-081: Filter by severity.
     */
    public function test_filter_by_severity(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'severity' => 'critical',
            'page_url' => 'https://example.com/critical',
        ]);
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'severity' => 'low',
            'page_url' => 'https://example.com/low',
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv', ['severity' => 'critical']);

        $content = file_get_contents($path);
        $this->assertStringContainsString('https://example.com/critical', $content);
        $this->assertStringNotContainsString('https://example.com/low', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-082: Filter by date_from.
     */
    public function test_filter_by_date_from(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'first_detected_at' => now()->subDays(10),
            'page_url' => 'https://example.com/old',
        ]);
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'first_detected_at' => now()->subDays(2),
            'page_url' => 'https://example.com/recent',
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv', [
            'date_from' => now()->subDays(5)->toDateString(),
        ]);

        $content = file_get_contents($path);
        $this->assertStringContainsString('https://example.com/recent', $content);
        $this->assertStringNotContainsString('https://example.com/old', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-083: Filter by date_to.
     */
    public function test_filter_by_date_to(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'first_detected_at' => now()->subDays(10),
            'page_url' => 'https://example.com/old',
        ]);
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'first_detected_at' => now()->subDays(2),
            'page_url' => 'https://example.com/recent',
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv', [
            'date_to' => now()->subDays(5)->toDateString(),
        ]);

        $content = file_get_contents($path);
        $this->assertStringContainsString('https://example.com/old', $content);
        $this->assertStringNotContainsString('https://example.com/recent', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-084: Filter with multiple criteria.
     */
    public function test_filter_with_multiple_criteria(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'severity' => 'critical',
            'page_url' => 'https://example.com/match',
        ]);
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'planned',
            'severity' => 'critical',
            'page_url' => 'https://example.com/wrong-status',
        ]);
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'severity' => 'low',
            'page_url' => 'https://example.com/wrong-severity',
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv', [
            'status' => 'backlog',
            'severity' => 'critical',
        ]);

        $content = file_get_contents($path);
        $this->assertStringContainsString('https://example.com/match', $content);
        $this->assertStringNotContainsString('wrong-status', $content);
        $this->assertStringNotContainsString('wrong-severity', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-085: Filter with date range.
     */
    public function test_filter_with_date_range(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'first_detected_at' => now()->subDays(7),
            'page_url' => 'https://example.com/in-range',
        ]);
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'first_detected_at' => now()->subDays(20),
            'page_url' => 'https://example.com/too-old',
        ]);
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'first_detected_at' => now()->subDays(1),
            'page_url' => 'https://example.com/too-new',
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv', [
            'date_from' => now()->subDays(10)->toDateString(),
            'date_to' => now()->subDays(5)->toDateString(),
        ]);

        $content = file_get_contents($path);
        $this->assertStringContainsString('https://example.com/in-range', $content);
        $this->assertStringNotContainsString('too-old', $content);
        $this->assertStringNotContainsString('too-new', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // ============================================
    // AI BRIEF GENERATION TESTS
    // ============================================

    /**
     * Test EXP-090: generateAiBrief returns array.
     */
    public function test_generate_ai_brief_returns_array(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $result = $this->service->generateAiBrief($candidate->id);

        $this->assertIsArray($result);
    }

    /**
     * Test EXP-091: generateAiBrief returns job_id.
     */
    public function test_generate_ai_brief_returns_job_id(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $result = $this->service->generateAiBrief($candidate->id);

        $this->assertArrayHasKey('job_id', $result);
        $this->assertNotEmpty($result['job_id']);
    }

    /**
     * Test EXP-092: generateAiBrief returns status queued.
     */
    public function test_generate_ai_brief_returns_status_queued(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $result = $this->service->generateAiBrief($candidate->id);

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('queued', $result['status']);
    }

    /**
     * Test EXP-093: generateAiBrief job_id contains candidate id.
     */
    public function test_generate_ai_brief_job_id_contains_candidate_id(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $result = $this->service->generateAiBrief($candidate->id);

        $this->assertStringContainsString((string) $candidate->id, $result['job_id']);
    }

    /**
     * Test EXP-094: generateAiBrief throws for non-existent candidate.
     */
    public function test_generate_ai_brief_throws_for_non_existent_candidate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Candidate not found');

        $this->service->generateAiBrief(99999);
    }

    // ============================================
    // GET AI BRIEF TESTS
    // ============================================

    /**
     * Test EXP-100: getAiBrief returns null for non-existent candidate.
     */
    public function test_get_ai_brief_returns_null_for_non_existent(): void
    {
        $result = $this->service->getAiBrief(99999);

        $this->assertNull($result);
    }

    /**
     * Test EXP-101: getAiBrief returns null when no brief exists.
     */
    public function test_get_ai_brief_returns_null_when_no_brief(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'evidence_json' => null,
        ]);

        $result = $this->service->getAiBrief($candidate->id);

        $this->assertNull($result);
    }

    /**
     * Test EXP-102: getAiBrief returns brief when exists.
     */
    public function test_get_ai_brief_returns_brief_when_exists(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'evidence_json' => [
                'ai_brief' => [
                    'summary' => 'Content needs refresh',
                    'suggestions' => ['Update title', 'Add keywords'],
                ],
            ],
        ]);

        $result = $this->service->getAiBrief($candidate->id);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertEquals('Content needs refresh', $result['summary']);
    }

    // ============================================
    // SECURITY TESTS
    // ============================================

    /**
     * Test EXP-110: XSS in page_url is preserved in CSV.
     */
    public function test_xss_in_page_url_is_preserved_in_csv(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/<script>alert("xss")</script>',
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        // The script tag should be present (CSV doesn't execute scripts)
        $this->assertStringContainsString('<script>', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-111: SQL injection in filter is handled safely.
     */
    public function test_sql_injection_in_filter_is_handled_safely(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
        ]);

        // This should not throw an exception
        $path = $this->service->exportQueue($project->site_id, 'csv', [
            'status' => "backlog'; DROP TABLE candidates; --",
        ]);

        $this->assertFileExists($path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-112: Special characters in title handled.
     */
    public function test_special_characters_in_title_handled(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_title' => 'Title with "quotes" and \'apostrophes\'',
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $this->assertFileExists($path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-113: Unicode characters handled.
     */
    public function test_unicode_characters_handled(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_title' => '日本語タイトル 中文标题',
            'page_url' => 'https://example.com/путь',
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $this->assertStringContainsString('日本語', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-114: Emoji in content handled.
     */
    public function test_emoji_in_content_handled(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_title' => 'Title with emoji 🎉 🚀 ✅',
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $this->assertFileExists($path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // ============================================
    // EDGE CASE TESTS
    // ============================================

    /**
     * Test EXP-120: Export with null values handled.
     */
    public function test_export_with_null_values_handled(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_title' => null,
            'lost_click_estimate' => null,
            'recovery_upside_estimate' => null,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $this->assertFileExists($path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-121: Export with very long URL.
     */
    public function test_export_with_very_long_url(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/' . str_repeat('a', 500),
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $this->assertFileExists($path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-122: Export with zero decay score.
     */
    public function test_export_with_zero_decay_score(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'decay_score' => 0,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $this->assertStringContainsString('0', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-123: Export with max decay score.
     */
    public function test_export_with_max_decay_score(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'decay_score' => 100,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $this->assertStringContainsString('100', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-124: Export with all reason codes.
     */
    public function test_export_with_all_reason_codes(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'primary_reason_code' => 'clicks_down',
            'reason_codes' => ['clicks_down', 'impressions_down', 'ctr_down'],
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        $this->assertStringContainsString('clicks_down', $content);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-125: Export with all statuses.
     */
    public function test_export_with_all_statuses(): void
    {
        $project = ContentDecayProject::factory()->create();
        $statuses = ['backlog', 'planned', 'in_progress', 'refreshed', 'recovered', 'ignored'];
        
        foreach ($statuses as $status) {
            ContentDecayCandidate::factory()->create([
                'project_id' => $project->id,
                'status' => $status,
            ]);
        }

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        foreach ($statuses as $status) {
            $this->assertStringContainsString($status, $content);
        }

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-126: Export with all severities.
     */
    public function test_export_with_all_severities(): void
    {
        $project = ContentDecayProject::factory()->create();
        $severities = ['critical', 'high', 'medium', 'low'];
        
        foreach ($severities as $severity) {
            ContentDecayCandidate::factory()->create([
                'project_id' => $project->id,
                'severity' => $severity,
            ]);
        }

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $content = file_get_contents($path);
        foreach ($severities as $severity) {
            $this->assertStringContainsString($severity, $content);
        }

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // ============================================
    // INTEGRATION TESTS
    // ============================================

    /**
     * Test EXP-130: Full export workflow.
     */
    public function test_full_export_workflow(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->count(10)->create([
            'project_id' => $project->id,
        ]);

        $path = $this->service->exportQueue($project->site_id, 'csv');

        $this->assertFileExists($path);
        $this->assertGreaterThan(100, filesize($path));

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-131: Multiple exports for same site.
     */
    public function test_multiple_exports_for_same_site(): void
    {
        $project = ContentDecayProject::factory()->create();

        $path1 = $this->service->exportQueue($project->site_id, 'csv');
        $path2 = $this->service->exportQueue($project->site_id, 'pdf');
        $path3 = $this->service->exportQueue($project->site_id, 'xls');

        $this->assertFileExists($path1);
        $this->assertFileExists($path2);
        $this->assertFileExists($path3);

        // Cleanup
        foreach ([$path1, $path2, $path3] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Test EXP-132: Container binding resolves service.
     */
    public function test_container_binding_resolves_service(): void
    {
        $service = app(ContentDecayExportServiceInterface::class);

        $this->assertInstanceOf(ContentDecayExportService::class, $service);
    }

    // ============================================
    // ACCEPTANCE CRITERIA TESTS
    // ============================================

    /**
     * Test EXP-AC01: ContentDecayExportService class created.
     */
    public function test_ac_content_decay_export_service_class_created(): void
    {
        $this->assertInstanceOf(ContentDecayExportService::class, $this->service);
    }

    /**
     * Test EXP-AC02: exportQueue method exists with correct signature.
     */
    public function test_ac_export_queue_method_exists(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'exportQueue');
        $parameters = $reflection->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertEquals('siteId', $parameters[0]->getName());
        $this->assertEquals('format', $parameters[1]->getName());
        $this->assertEquals('filters', $parameters[2]->getName());
    }

    /**
     * Test EXP-AC03: CSV export includes all required columns.
     */
    public function test_ac_csv_export_includes_all_required_columns(): void
    {
        $project = ContentDecayProject::factory()->create();

        $path = $this->service->exportQueue($project->site_id, 'csv');
        $content = file_get_contents($path);
        // Remove UTF-8 BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $headers = str_getcsv(explode("\n", $content)[0]);

        $requiredColumns = [
            'URL', 'Title', 'Page Type', 'Severity', 'Decay Score',
            'Confidence', 'Primary Reason', 'All Reason Codes',
            'Lost Clicks', 'Recovery Upside', 'Status',
            'First Detected', 'Last Detected',
            'Planned Date', 'Started Date', 'Refreshed Date', 'Recovered Date',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertContains($column, $headers, "Missing column: {$column}");
        }

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-AC04: Support filtering by status.
     */
    public function test_ac_support_filtering_by_status(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'planned',
        ]);

        // Should not throw exception
        $path = $this->service->exportQueue($project->site_id, 'csv', ['status' => 'planned']);

        $this->assertFileExists($path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-AC05: Support filtering by severity.
     */
    public function test_ac_support_filtering_by_severity(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'severity' => 'critical',
        ]);

        // Should not throw exception
        $path = $this->service->exportQueue($project->site_id, 'csv', ['severity' => 'critical']);

        $this->assertFileExists($path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Test EXP-AC06: Support filtering by date range.
     */
    public function test_ac_support_filtering_by_date_range(): void
    {
        $project = ContentDecayProject::factory()->create();

        // Should not throw exception
        $path = $this->service->exportQueue($project->site_id, 'csv', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]);

        $this->assertFileExists($path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
