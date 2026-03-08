<?php

namespace Tests\Unit\Services\AIVisibility;

use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\User;
use App\Services\AIVisibility\CsvExporter;
use App\Services\AIVisibility\ExportService;
use App\Services\AIVisibility\XlsxExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Export Service Test
 *
 * Unit tests for ExportService.
 *
 * @see TASK-34-22
 * @see TEST-34-22
 */
class ExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExportService $service;
    protected User $user;
    protected AIVisibilityProject $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ExportService::class);
        $this->user = User::factory()->create();
        $this->project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'brand_name' => 'TestBrand',
        ]);

        // Create sample query results
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'success' => true,
            'brand_mentioned' => true,
        ]);
    }

    // ============================================
    // ES-INST: Service Instantiation Tests
    // ============================================

    /** @test ES-INST-01: Service exists */
    public function test_service_exists(): void
    {
        $this->assertInstanceOf(ExportService::class, $this->service);
    }

    /** @test ES-INST-03: Dependencies injected */
    public function test_dependencies_injected(): void
    {
        $reflection = new \ReflectionClass($this->service);

        $csvProp = $reflection->getProperty('csvExporter');
        $csvProp->setAccessible(true);
        $this->assertInstanceOf(CsvExporter::class, $csvProp->getValue($this->service));

        $xlsxProp = $reflection->getProperty('xlsxExporter');
        $xlsxProp->setAccessible(true);
        $this->assertInstanceOf(XlsxExporter::class, $xlsxProp->getValue($this->service));
    }

    // ============================================
    // ES-EXPORT: Export Method Tests
    // ============================================

    /** @test ES-EXP-01: Export to CSV */
    public function test_export_to_csv(): void
    {
        $response = $this->service->export($this->user->id, [], 'csv');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
    }

    /** @test ES-EXP-02: Export to XLSX */
    public function test_export_to_xlsx(): void
    {
        $response = $this->service->export($this->user->id, [], 'xlsx');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
    }

    /** @test ES-EXP-03: Export with filters */
    public function test_export_with_filters(): void
    {
        $filters = [
            'project_id' => $this->project->id,
            'from' => '2026-01-01',
            'to' => '2026-12-31',
        ];

        $response = $this->service->export($this->user->id, $filters, 'csv');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test ES-EXP-04: Export projects data type */
    public function test_export_projects_data_type(): void
    {
        $response = $this->service->export($this->user->id, ['data_type' => 'projects'], 'csv');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test ES-EXP-05: Export queries data type */
    public function test_export_queries_data_type(): void
    {
        $response = $this->service->export($this->user->id, ['data_type' => 'queries'], 'csv');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test ES-EXP-09: Empty dataset */
    public function test_export_empty_dataset(): void
    {
        $emptyProject = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->service->export($this->user->id, ['project_id' => $emptyProject->id], 'csv');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test ES-EXP-11: Invalid format throws exception */
    public function test_invalid_format_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->export($this->user->id, [], 'pdf');
    }

    /** @test ES-EXP-12: Invalid data type throws exception */
    public function test_invalid_data_type_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->export($this->user->id, ['data_type' => 'invalid'], 'csv');
    }

    /** @test ES-EXP-13: Permission check - other user's project */
    public function test_permission_check_other_user_project(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = AIVisibilityProject::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->export($this->user->id, ['project_id' => $otherProject->id], 'csv');
    }

    // ============================================
    // Date Range Validation Tests
    // ============================================

    /** @test: Invalid from date throws exception */
    public function test_invalid_from_date_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->export($this->user->id, ['from' => 'invalid-date'], 'csv');
    }

    /** @test: Invalid to date throws exception */
    public function test_invalid_to_date_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->export($this->user->id, ['to' => 'invalid-date'], 'csv');
    }

    /** @test: From after to throws exception */
    public function test_from_after_to_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->export($this->user->id, [
            'from' => '2026-12-31',
            'to' => '2026-01-01',
        ], 'csv');
    }

    /** @test: Date range over 1 year throws exception */
    public function test_date_range_over_one_year_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->export($this->user->id, [
            'from' => '2025-01-01',
            'to' => '2026-12-31',
        ], 'csv');
    }

    // ============================================
    // Project Filtering Tests
    // ============================================

    /** @test: Non-existent project throws exception */
    public function test_non_existent_project_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->export($this->user->id, ['project_id' => 99999], 'csv');
    }

    /** @test: Multiple projects filter */
    public function test_multiple_projects_filter(): void
    {
        $project2 = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->service->export($this->user->id, [
            'project_ids' => [$this->project->id, $project2->id],
        ], 'csv');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test: Empty project_ids returns all user projects */
    public function test_empty_project_ids_returns_all(): void
    {
        $response = $this->service->export($this->user->id, [], 'csv');

        $this->assertEquals(200, $response->getStatusCode());
    }

    // ============================================
    // Multi-sheet XLSX Tests
    // ============================================

    /** @test: Export all data types as multi-sheet XLSX */
    public function test_export_all_data_types_multi_sheet_xlsx(): void
    {
        $response = $this->service->export($this->user->id, ['data_type' => 'all'], 'xlsx');

        $this->assertEquals(200, $response->getStatusCode());
    }

    // ============================================
    // Filename Generation Tests
    // ============================================

    /** @test: Filename includes date */
    public function test_filename_includes_date(): void
    {
        $response = $this->service->export($this->user->id, [], 'csv');

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $disposition);
    }

    /** @test: Filename includes brand name for single project */
    public function test_filename_includes_brand_name_for_single_project(): void
    {
        $response = $this->service->export($this->user->id, ['project_id' => $this->project->id], 'csv');

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString(strtolower($this->project->brand_name), strtolower($disposition));
    }
}
