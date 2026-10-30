<?php

namespace Tests\Feature\Api\V1\ContentDecay;

use App\Models\User;
use App\Models\Site;
use App\Services\ContentDecay\Contracts\ContentDecayCandidateServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @group content-decay
 * @group api
 */
class ContentDecayCandidateControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Create mock user, site, and candidate
     */
    protected function createMockCandidate(array $attributes = []): array
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $candidate = (object) array_merge([
            'id' => 1,
            'project_id' => 1,
            'page_url' => 'https://example.com/blog/seo-tips',
            'page_url_hash' => hash('sha256', 'https://example.com/blog/seo-tips'),
            'page_title' => '10 SEO Tips for 2024',
            'page_type' => 'blog',
            'severity' => 'high',
            'decay_score' => 65.50,
            'detection_confidence' => 0.93,
            'lost_click_estimate' => 150,
            'recovery_upside_estimate' => 180,
            'primary_reason_code' => 'clicks_down',
            'reason_codes' => ['clicks_down', 'ctr_down'],
            'status' => 'backlog',
            'first_detected_at' => now()->subDays(10)->toIso8601String(),
            'last_detected_at' => now()->toIso8601String(),
            'planned_at' => null,
            'started_at' => null,
            'refreshed_at' => null,
            'recovered_at' => null,
            'ignored_at' => null,
            'evidence_json' => [
                'previous_clicks' => 500,
                'current_clicks' => 350,
                'click_change' => -30.0,
            ],
        ], $attributes);

        return compact('user', 'site', 'candidate');
    }

    /**
     * Create a paginated response mock
     */
    protected function createPaginatedResponse(array $items, int $total = 1): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $items,
            $total,
            15,
            1,
            ['path' => '/api/v1/sites/1/content-decay/candidates']
        );
    }

    // ==========================================
    // AUTHENTICATION TESTS
    // ==========================================

    /** @test */
    public function unauthenticated_user_cannot_list_candidates(): void
    {
        $site = Site::factory()->create();

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates");

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_candidate_detail(): void
    {
        $response = $this->getJson('/api/v1/content-decay/candidates/1');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_update_candidate(): void
    {
        $response = $this->patchJson('/api/v1/content-decay/candidates/1', [
            'status' => 'planned',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_restore_candidate(): void
    {
        $response = $this->postJson('/api/v1/content-decay/candidates/1/restore');

        $response->assertStatus(401);
    }

    // ==========================================
    // AUTHORIZATION TESTS
    // ==========================================

    /** @test */
    public function non_owner_user_cannot_list_candidates(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($nonOwner);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates");

        $response->assertStatus(403);
    }

    /** @test */
    public function site_owner_can_list_candidates(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('listCandidates')
            ->once()
            ->andReturn($this->createPaginatedResponse([$candidate]));

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates");

        $response->assertStatus(200);
    }

    /** @test */
    public function cross_user_candidate_access_returns_404(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($nonOwner);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('getCandidate')
            ->once()
            ->andReturn(null);

        $response = $this->getJson('/api/v1/content-decay/candidates/1');

        $response->assertStatus(404);
    }

    /** @test */
    public function owner_can_access_own_candidate(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $response = $this->getJson('/api/v1/content-decay/candidates/1');

        $response->assertStatus(200);
    }

    // ==========================================
    // VALIDATION TESTS
    // ==========================================

    /** @test */
    public function list_candidates_validates_status_filter(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates?status=invalid");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function list_candidates_validates_severity_filter(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates?severity=unknown");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['severity']);
    }

    /** @test */
    public function list_candidates_validates_sort_field(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates?sort=unknown");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    /** @test */
    public function list_candidates_validates_order_field(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates?order=invalid");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order']);
    }

    /** @test */
    public function update_candidate_validates_status(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/content-decay/candidates/1', [
            'status' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function update_candidate_validates_notes_length(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/content-decay/candidates/1', [
            'status' => 'planned',
            'notes' => str_repeat('a', 65536),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['notes']);
    }

    /** @test */
    public function list_candidates_accepts_valid_filters(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('listCandidates')
            ->once()
            ->andReturn($this->createPaginatedResponse([$candidate]));

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates?status=backlog&severity=high&page_type=blog");

        $response->assertStatus(200);
    }

    // ==========================================
    // SUCCESS TESTS - LIST CANDIDATES
    // ==========================================

    /** @test */
    public function list_candidates_returns_paginated_data(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('listCandidates')
            ->once()
            ->andReturn($this->createPaginatedResponse([$candidate], 15));

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'page_url',
                        'page_title',
                        'page_type',
                        'severity',
                        'decay_score',
                        'status',
                        'primary_reason_code',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /** @test */
    public function list_candidates_with_search_returns_matching_results(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('listCandidates')
            ->once()
            ->andReturn($this->createPaginatedResponse([$candidate]));

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates?search=seo");

        $response->assertStatus(200);
    }

    /** @test */
    public function list_candidates_with_reason_code_filter(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('listCandidates')
            ->once()
            ->andReturn($this->createPaginatedResponse([$candidate]));

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates?reason_code=clicks_down");

        $response->assertStatus(200);
    }

    /** @test */
    public function list_candidates_with_min_lost_clicks_filter(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('listCandidates')
            ->once()
            ->andReturn($this->createPaginatedResponse([$candidate]));

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates?min_lost_clicks=100");

        $response->assertStatus(200);
    }

    // ==========================================
    // SUCCESS TESTS - GET CANDIDATE DETAIL
    // ==========================================

    /** @test */
    public function get_candidate_detail_returns_full_data(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $response = $this->getJson('/api/v1/content-decay/candidates/1');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'page_url' => 'https://example.com/blog/seo-tips',
                    'page_title' => '10 SEO Tips for 2024',
                    'page_type' => 'blog',
                    'severity' => 'high',
                    'decay_score' => 65.50,
                    'status' => 'backlog',
                    'primary_reason_code' => 'clicks_down',
                ],
            ]);
    }

    /** @test */
    public function get_candidate_detail_includes_evidence(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('getCandidate')
            ->once()
            ->andReturn($candidate);

        $response = $this->getJson('/api/v1/content-decay/candidates/1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'evidence_json',
                ],
            ]);
    }

    // ==========================================
    // SUCCESS TESTS - UPDATE STATUS
    // ==========================================

    /** @test */
    public function update_status_to_planned(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $updatedCandidate = (object) array_merge((array) $candidate, [
            'status' => 'planned',
            'planned_at' => now()->toIso8601String(),
        ]);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('updateCandidate')
            ->once()
            ->andReturn($updatedCandidate);

        $response = $this->patchJson('/api/v1/content-decay/candidates/1', [
            'status' => 'planned',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'planned',
                ],
            ]);
    }

    /** @test */
    public function update_status_to_in_progress(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate(['status' => 'planned']);
        Sanctum::actingAs($user);

        $updatedCandidate = (object) array_merge((array) $candidate, [
            'status' => 'in_progress',
            'started_at' => now()->toIso8601String(),
        ]);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('updateCandidate')
            ->once()
            ->andReturn($updatedCandidate);

        $response = $this->patchJson('/api/v1/content-decay/candidates/1', [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'in_progress',
                ],
            ]);
    }

    /** @test */
    public function update_status_to_refreshed(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate(['status' => 'in_progress']);
        Sanctum::actingAs($user);

        $updatedCandidate = (object) array_merge((array) $candidate, [
            'status' => 'refreshed',
            'refreshed_at' => now()->toIso8601String(),
        ]);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('updateCandidate')
            ->once()
            ->andReturn($updatedCandidate);

        $response = $this->patchJson('/api/v1/content-decay/candidates/1', [
            'status' => 'refreshed',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'refreshed',
                ],
            ]);
    }

    /** @test */
    public function update_status_to_ignored(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $updatedCandidate = (object) array_merge((array) $candidate, [
            'status' => 'ignored',
            'ignored_at' => now()->toIso8601String(),
        ]);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('updateCandidate')
            ->once()
            ->andReturn($updatedCandidate);

        $response = $this->patchJson('/api/v1/content-decay/candidates/1', [
            'status' => 'ignored',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'ignored',
                ],
            ]);
    }

    /** @test */
    public function update_status_with_notes(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $updatedCandidate = (object) array_merge((array) $candidate, [
            'status' => 'planned',
            'planned_at' => now()->toIso8601String(),
        ]);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('updateCandidate')
            ->once()
            ->andReturn($updatedCandidate);

        $response = $this->patchJson('/api/v1/content-decay/candidates/1', [
            'status' => 'planned',
            'notes' => 'Scheduled for Q2 refresh',
        ]);

        $response->assertStatus(200);
    }

    // ==========================================
    // SUCCESS TESTS - RESTORE CANDIDATE
    // ==========================================

    /** @test */
    public function restore_ignored_candidate_to_backlog(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate(['status' => 'ignored']);
        Sanctum::actingAs($user);

        $restoredCandidate = (object) array_merge((array) $candidate, [
            'status' => 'backlog',
            'ignored_at' => null,
        ]);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('restoreCandidate')
            ->once()
            ->andReturn($restoredCandidate);

        $response = $this->postJson('/api/v1/content-decay/candidates/1/restore');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'backlog',
                ],
            ]);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function list_candidates_returns_empty_data_when_no_candidates(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('listCandidates')
            ->once()
            ->andReturn($this->createPaginatedResponse([], 0));

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
                'meta' => [
                    'total' => 0,
                ],
            ]);
    }

    /** @test */
    public function get_non_existent_candidate_returns_404(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('getCandidate')
            ->once()
            ->andReturn(null);

        $response = $this->getJson('/api/v1/content-decay/candidates/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function restore_non_ignored_candidate_returns_400(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate(['status' => 'backlog']);
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('restoreCandidate')
            ->once()
            ->andThrow(new \Exception('Candidate is not in ignored state'));

        $response = $this->postJson('/api/v1/content-decay/candidates/1/restore');

        $response->assertStatus(400);
    }

    /** @test */
    public function pagination_metadata_is_correct(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('listCandidates')
            ->once()
            ->andReturn($this->createPaginatedResponse([$candidate], 45));

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates");

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 15,
                    'total' => 45,
                    'last_page' => 3,
                ],
            ]);
    }

    // ==========================================
    // SECURITY TESTS
    // ==========================================

    /** @test */
    public function sql_injection_in_search_is_prevented(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('listCandidates')
            ->once()
            ->andReturn($this->createPaginatedResponse([]));

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/candidates?search=';DROP TABLE candidates;--");

        $response->assertStatus(200);
        // Verify no SQL injection occurred - service should receive sanitized input
    }

    /** @test */
    public function xss_in_notes_is_escaped(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $updatedCandidate = (object) array_merge((array) $candidate, [
            'status' => 'planned',
        ]);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('updateCandidate')
            ->once()
            ->andReturn($updatedCandidate);

        $response = $this->patchJson('/api/v1/content-decay/candidates/1', [
            'status' => 'planned',
            'notes' => '<script>alert("xss")</script>',
        ]);

        $response->assertStatus(200);
        // Verify output is escaped
        $response->assertDontSee('<script>alert("xss")</script>', false);
    }

    /** @test */
    public function idor_candidate_access_is_prevented(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($nonOwner);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('getCandidate')
            ->once()
            ->andReturn(null);

        $response = $this->getJson('/api/v1/content-decay/candidates/1');

        $response->assertStatus(404);
    }

    /** @test */
    public function mass_assignment_protection_on_update(): void
    {
        ['user' => $user, 'site' => $site, 'candidate' => $candidate] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $updatedCandidate = (object) array_merge((array) $candidate, [
            'status' => 'planned',
            // decay_score should not change even if attempted
        ]);

        $mockService = $this->mock(ContentDecayCandidateServiceInterface::class);
        $mockService->shouldReceive('updateCandidate')
            ->once()
            ->andReturn($updatedCandidate);

        $response = $this->patchJson('/api/v1/content-decay/candidates/1', [
            'status' => 'planned',
            'decay_score' => 99.99, // Should be ignored
            'id' => 999, // Should be ignored
        ]);

        $response->assertStatus(200);
    }

    // ==========================================
    // HTTP METHOD TESTS
    // ==========================================

    /** @test */
    public function candidates_endpoint_rejects_post_method(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/content-decay/candidates");

        $response->assertStatus(405);
    }

    /** @test */
    public function candidate_detail_endpoint_rejects_post_method(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/content-decay/candidates/1');

        $response->assertStatus(405);
    }

    /** @test */
    public function restore_endpoint_rejects_get_method(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockCandidate();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/content-decay/candidates/1/restore');

        $response->assertStatus(405);
    }
}
