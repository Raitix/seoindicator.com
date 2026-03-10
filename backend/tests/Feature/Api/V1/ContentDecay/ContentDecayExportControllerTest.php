<?php

namespace Tests\Feature\Api\V1\ContentDecay;

use App\Models\User;
use App\Models\Site;
use App\Services\ContentDecay\Contracts\ContentDecayExportServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayCandidateServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @group content-decay
 * @group api
 */
class ContentDecayExportControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Create mock user and site
     */
    protected function createMockExportContext(): array
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        return compact('user', 'site');
    }

    // ==========================================
    // AUTHENTICATION TESTS
    // ==========================================

    /** @test */
    public function unauthenticated_user_cannot_export(): void
    {
        $site = Site::factory()->create();

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=csv");

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_generate_ai_brief(): void
    {
        $response = $this->postJson('/api/v1/content-decay/candidates/1/ai-brief');

        $response->assertStatus(401);
    }

    // ==========================================
    // AUTHORIZATION TESTS
    // ==========================================

    /** @test */
    public function non_owner_user_cannot_export(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($nonOwner);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=csv");

        $response->assertStatus(403);
    }

    /** @test */
    public function site_owner_can_export(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayExportServiceInterface::class);
        $mockService->shouldReceive('exportQueue')
            ->once()
            ->andReturn('page_url,decay_score,severity,status
https://example.com/page1,75.5,high,backlog');

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=csv");

        $response->assertStatus(200);
    }

    // ==========================================
    // VALIDATION TESTS
    // ==========================================

    /** @test */
    public function export_validates_format_parameter(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=docx");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function export_accepts_csv_format(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayExportServiceInterface::class);
        $mockService->shouldReceive('exportQueue')
            ->once()
            ->andReturn('page_url,decay_score,severity,status');

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=csv");

        $response->assertStatus(200);
    }

    /** @test */
    public function export_accepts_pdf_format(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayExportServiceInterface::class);
        $mockService->shouldReceive('exportQueue')
            ->once()
            ->andReturn('%PDF-1.4 binary content');

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=pdf");

        $response->assertStatus(200);
    }

    // ==========================================
    // SUCCESS TESTS - EXPORT
    // ==========================================

    /** @test */
    public function export_csv_returns_correct_headers(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayExportServiceInterface::class);
        $mockService->shouldReceive('exportQueue')
            ->once()
            ->andReturn('page_url,decay_score,severity,status
https://example.com/page1,75.5,high,backlog');

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=csv");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function export_pdf_returns_correct_headers(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayExportServiceInterface::class);
        $mockService->shouldReceive('exportQueue')
            ->once()
            ->andReturn('%PDF-1.4 binary content');

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=pdf");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function export_with_filters(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayExportServiceInterface::class);
        $mockService->shouldReceive('exportQueue')
            ->once()
            ->withArgs(function ($siteId, $filters) {
                return $siteId === $site->id
                    && isset($filters['status'])
                    && $filters['status'] === 'critical';
            })
            ->andReturn('page_url,decay_score,severity,status');

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=csv&status=critical");

        $response->assertStatus(200);
    }

    /** @test */
    public function export_with_multiple_filters(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayExportServiceInterface::class);
        $mockService->shouldReceive('exportQueue')
            ->once()
            ->andReturn('page_url,decay_score,severity,status');

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=csv&status=planned&severity=high&page_type=blog");

        $response->assertStatus(200);
    }

    // ==========================================
    // SUCCESS TESTS - AI BRIEF
    // ==========================================

    /** @test */
    public function generate_ai_brief_returns_job_id(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $candidate = (object) [
            'id' => 1,
            'project_id' => 1,
            'page_url' => 'https://example.com/blog/seo-tips',
            'status' => 'planned',
        ];

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $mockExportService = $this->mock(ContentDecayExportServiceInterface::class);
        $mockExportService->shouldReceive('generateAiBrief')
            ->once()
            ->andReturn('job-uuid-ai-brief');

        $response = $this->postJson('/api/v1/content-decay/candidates/1/ai-brief');

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'data' => [
                    'job_id' => 'job-uuid-ai-brief',
                ],
            ]);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function export_empty_queue_returns_headers_only(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayExportServiceInterface::class);
        $mockService->shouldReceive('exportQueue')
            ->once()
            ->andReturn('page_url,decay_score,severity,status');

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=csv");

        $response->assertStatus(200);
    }

    /** @test */
    public function ai_brief_for_non_existent_candidate_returns_404(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn(null);

        $response = $this->postJson('/api/v1/content-decay/candidates/999999/ai-brief');

        $response->assertStatus(404);
    }

    /** @test */
    public function export_for_non_existent_site_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/sites/999999/content-decay/export?format=csv');

        $response->assertStatus(404);
    }

    /** @test */
    public function ai_brief_for_ignored_candidate_returns_400(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $ignoredCandidate = (object) [
            'id' => 1,
            'project_id' => 1,
            'page_url' => 'https://example.com/blog/seo-tips',
            'status' => 'ignored',
        ];

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($ignoredCandidate);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/ai-brief');

        $response->assertStatus(400);
    }

    // ==========================================
    // SECURITY TESTS
    // ==========================================

    /** @test */
    public function sql_injection_in_export_filters_is_prevented(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayExportServiceInterface::class);
        $mockService->shouldReceive('exportQueue')
            ->once()
            ->andReturn('page_url,decay_score,severity,status');

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/export?format=csv&search=';DROP TABLE candidates;--");

        $response->assertStatus(200);
    }

    /** @test */
    public function idor_ai_brief_access_is_prevented(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($nonOwner);

        $mockCandidateService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockCandidateService->shouldReceive('getCandidate')
            ->once()
            ->andReturn(null);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/ai-brief');

        $response->assertStatus(404);
    }

    // ==========================================
    // HTTP METHOD TESTS
    // ==========================================

    /** @test */
    public function export_endpoint_rejects_post_method(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/content-decay/export");

        $response->assertStatus(405);
    }

    /** @test */
    public function ai_brief_endpoint_rejects_get_method(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/content-decay/candidates/1/ai-brief');

        $response->assertStatus(405);
    }

    /** @test */
    public function ai_brief_endpoint_rejects_patch_method(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockExportContext();
        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/content-decay/candidates/1/ai-brief');

        $response->assertStatus(405);
    }
}
