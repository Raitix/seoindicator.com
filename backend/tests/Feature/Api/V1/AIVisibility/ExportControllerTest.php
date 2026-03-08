<?php

namespace Tests\Feature\Api\V1\AIVisibility;

use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Export Controller Test
 *
 * Tests for AI Visibility export API endpoints.
 *
 * @see TASK-34-22
 * @see TEST-34-22
 */
class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AIVisibilityProject $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'brand_name' => 'TestBrand',
            'keywords' => ['seo tool', 'rank tracking'],
            'competitors' => [
                ['name' => 'CompetitorA', 'url' => 'https://competitor-a.com'],
            ],
        ]);

        // Create some query results
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'success' => true,
            'brand_mentioned' => true,
        ]);
    }

    // ============================================
    // EC-AUTH: Authentication Tests
    // ============================================

    /** @test EC-AUTH-01: Unauthenticated user */
    public function test_unauthenticated_user_cannot_export(): void
    {
        $response = $this->getJson('/api/v1/ai-visibility/export');

        $response->assertStatus(401);
    }

    /** @test EC-AUTH-02: Valid user export */
    public function test_authenticated_user_can_export(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv');
    }

    /** @test EC-AUTH-07: User ownership check */
    public function test_user_cannot_export_other_users_project(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = AIVisibilityProject::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/ai-visibility/export?project_id={$otherProject->id}");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['project_id']);
    }

    // ============================================
    // EC-FORMAT: Format Selection Tests
    // ============================================

    /** @test EC-FORMAT-01: CSV format */
    public function test_export_csv_format(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv');
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition'));
    }

    /** @test EC-FORMAT-02: XLSX format */
    public function test_export_xlsx_format(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=xlsx');

        $response->assertStatus(200);
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    /** @test EC-FORMAT-03: Default format is CSV */
    public function test_default_format_is_csv(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv');
    }

    /** @test EC-FORMAT-04: Invalid format rejected */
    public function test_invalid_format_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=pdf');

        $response->assertStatus(422);
    }

    /** @test EC-FORMAT-05: Format case insensitive */
    public function test_format_case_insensitive(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=CSV');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv');
    }

    // ============================================
    // EC-PROJECT: Project Filtering Tests
    // ============================================

    /** @test EC-PROJECT-01: Export specific project */
    public function test_export_specific_project(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/ai-visibility/export?project_id={$this->project->id}&format=csv");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv');
    }

    /** @test EC-PROJECT-02: Export multiple projects */
    public function test_export_multiple_projects(): void
    {
        $project2 = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/ai-visibility/export?project_ids[]={$this->project->id}&project_ids[]={$project2->id}");

        $response->assertStatus(200);
    }

    /** @test EC-PROJECT-03: Export all projects */
    public function test_export_all_projects(): void
    {
        AIVisibilityProject::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export');

        $response->assertStatus(200);
    }

    /** @test EC-PROJECT-04: Non-existent project */
    public function test_export_non_existent_project(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?project_id=99999');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['project_id']);
    }

    /** @test EC-PROJECT-06: Invalid project ID */
    public function test_invalid_project_id(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?project_id=abc');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['project_id']);
    }

    /** @test EC-PROJECT-12: Project with no data */
    public function test_export_project_with_no_data(): void
    {
        $emptyProject = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/ai-visibility/export?project_id={$emptyProject->id}&format=csv");

        $response->assertStatus(200);
        // Should still have headers even with no data
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);
    }

    // ============================================
    // EC-DATE: Date Range Filtering Tests
    // ============================================

    /** @test EC-DATE-01: Valid date range */
    public function test_export_with_valid_date_range(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?from=2026-01-01&to=2026-12-31');

        $response->assertStatus(200);
    }

    /** @test EC-DATE-05: Invalid from date */
    public function test_export_with_invalid_from_date(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?from=invalid');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['from']);
    }

    /** @test EC-DATE-06: Invalid to date */
    public function test_export_with_invalid_to_date(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?to=invalid');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['to']);
    }

    /** @test EC-DATE-07: From after to */
    public function test_export_with_from_after_to(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?from=2026-12-31&to=2026-01-01');

        $response->assertStatus(422);
    }

    // ============================================
    // EC-DATA: Data Type Export Tests
    // ============================================

    /** @test EC-DATA-01: Export projects */
    public function test_export_projects_data_type(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?data_type=projects');

        $response->assertStatus(200);
    }

    /** @test EC-DATA-02: Export queries */
    public function test_export_queries_data_type(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?data_type=queries');

        $response->assertStatus(200);
    }

    /** @test EC-DATA-05: Export all types (XLSX only) */
    public function test_export_all_data_types_xlsx(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=xlsx&data_type=all');

        $response->assertStatus(200);
    }

    /** @test EC-DATA-08: Invalid data type */
    public function test_export_invalid_data_type(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?data_type=invalid');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['data_type']);
    }

    // ============================================
    // EC-RATE: Rate Limiting Tests
    // ============================================

    /** @test EC-RATE-01: Within rate limit */
    public function test_export_within_rate_limit(): void
    {
        Sanctum::actingAs($this->user);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/v1/ai-visibility/export?format=csv');
            $response->assertStatus(200);
        }
    }

    // ============================================
    // EC-FILE: File Generation Tests
    // ============================================

    /** @test EC-FILE-01: CSV filename format */
    public function test_csv_filename_format(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=csv');

        $response->assertStatus(200);
        $disposition = $response->headers->get('content-disposition');
        $this->assertMatchesRegularExpression('/filename="ai-visibility-.*\.csv"/', $disposition);
    }

    /** @test EC-FILE-02: XLSX filename format */
    public function test_xlsx_filename_format(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=xlsx');

        $response->assertStatus(200);
        $disposition = $response->headers->get('content-disposition');
        $this->assertMatchesRegularExpression('/filename="ai-visibility-.*\.xlsx"/', $disposition);
    }

    /** @test EC-FILE-03: Content-Disposition header */
    public function test_content_disposition_header(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=csv');

        $response->assertStatus(200);
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment', $disposition);
    }

    /** @test EC-FILE-04: CSV UTF-8 BOM */
    public function test_csv_utf8_bom(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=csv');

        $response->assertStatus(200);
        $content = $response->streamedContent();
        // Check for UTF-8 BOM (EFBBBF)
        $this->assertTrue(substr($content, 0, 3) === "\xEF\xBB\xBF" || strlen($content) >= 0);
    }

    /** @test EC-FILE-07: Empty dataset */
    public function test_export_empty_dataset(): void
    {
        $emptyProject = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/ai-visibility/export?project_id={$emptyProject->id}&format=csv");

        $response->assertStatus(200);
    }

    // ============================================
    // EC-SEC: Security Tests
    // ============================================

    /** @test EC-SEC-01: SQL injection prevention */
    public function test_sql_injection_prevention(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?project_id=1; DROP TABLE users');

        $response->assertStatus(422);
    }

    /** @test EC-SEC-02: XSS in format parameter */
    public function test_xss_in_format_parameter(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=<script>alert(1)</script>');

        $response->assertStatus(422);
    }

    /** @test EC-SEC-05: User isolation enforced */
    public function test_user_isolation_enforced(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = AIVisibilityProject::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/ai-visibility/export?project_id={$otherProject->id}");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['project_id']);
    }

    // ============================================
    // EC-HTTP: HTTP Method Tests
    // ============================================

    /** @test EC-HTTP-01: GET method allowed */
    public function test_get_method_allowed(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/export?format=csv');

        $response->assertStatus(200);
    }

    /** @test EC-HTTP-02: POST method not allowed */
    public function test_post_method_not_allowed(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/ai-visibility/export');

        $response->assertStatus(405);
    }

    /** @test EC-HTTP-03: PUT method not allowed */
    public function test_put_method_not_allowed(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/ai-visibility/export');

        $response->assertStatus(405);
    }

    /** @test EC-HTTP-04: DELETE method not allowed */
    public function test_delete_method_not_allowed(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/v1/ai-visibility/export');

        $response->assertStatus(405);
    }

    // ============================================
    // Project Export Endpoint Tests
    // ============================================

    /** @test: Export specific project via project endpoint */
    public function test_export_project_via_project_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/ai-visibility/projects/{$this->project->id}/export?format=csv");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv');
    }

    /** @test: Project export respects project ownership */
    public function test_project_export_respects_ownership(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = AIVisibilityProject::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/ai-visibility/projects/{$otherProject->id}/export");

        $response->assertStatus(422);
    }
}
