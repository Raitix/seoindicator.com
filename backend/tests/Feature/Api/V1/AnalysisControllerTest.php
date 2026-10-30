<?php

namespace Tests\Feature\Api\V1;

use App\Models\SEOAnalysis;
use App\Models\User;
use App\Services\DomainValidatorService;
use App\Services\AnalysisRunnerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * AnalysisController Test - TASK-18-26
 *
 * Tests for SEO Analysis API endpoints:
 * - POST /api/v1/analyze - Create analysis
 * - GET /api/v1/analyses/{id} - Get results
 * - GET /api/v1/analyses - List analyses
 * - GET /api/v1/analyze?domain=... - Cached by domain
 */
class AnalysisControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================================
    // AC-001: POST /api/v1/analyze - Create Analysis
    // ============================================================

    /** @test */
    public function create_analysis_authenticated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'example.com',
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'domain',
                    'status',
                    'created_at',
                ],
                'links' => [
                    'self',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('seo_analyses', [
            'user_id' => $user->id,
            'domain' => 'example.com',
            'status' => SEOAnalysis::STATUS_PENDING,
        ]);
    }

    /** @test */
    public function create_analysis_anonymous(): void
    {
        $sessionId = (string) \Str::uuid();

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'example.com',
        ], [
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('seo_analyses', [
            'session_id' => $sessionId,
            'domain' => 'example.com',
            'status' => SEOAnalysis::STATUS_PENDING,
        ]);
    }

    /** @test */
    public function create_analysis_valid_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'https://www.example.com/page?query=value',
        ]);

        $response->assertStatus(202);

        // Verify normalized domain is stored
        $this->assertDatabaseHas('seo_analyses', [
            'user_id' => $user->id,
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function create_analysis_invalid_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'not-a-valid-domain',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['error']);
    }

    /** @test */
    public function create_analysis_normalizes_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'WWW.EXAMPLE.COM',
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('seo_analyses', [
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function create_analysis_returns_polling_url(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'example.com',
        ]);

        $response->assertStatus(202)
            ->assertHeader('Location');

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/api/v1/analyses/', $location);
    }

    /** @test */
    public function create_analysis_returns_analysis_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'example.com',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.id', function ($id) {
                return is_int($id) && $id > 0;
            });
    }

    /** @test */
    public function create_analysis_rate_limit_anonymous(): void
    {
        $sessionId = (string) \Str::uuid();

        // Create 3 analyses (limit)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/v1/analyze', [
                'domain' => "example{$i}.com",
            ], [
                'X-Session-ID' => $sessionId,
            ]);
            $response->assertStatus(202);
        }

        // 4th request should be rate limited
        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'example4.com',
        ], [
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function create_analysis_rate_limit_authenticated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create 10 analyses (limit)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/analyze', [
                'domain' => "example{$i}.com",
            ]);
            $response->assertStatus(202);
        }

        // 11th request should be rate limited
        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'example10.com',
        ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function create_analysis_rate_limit_headers(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'example.com',
        ]);

        $response->assertStatus(202)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }

    /** @test */
    public function create_analysis_429_response(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Exhaust rate limit
        for ($i = 0; $i < 11; $i++) {
            $this->postJson('/api/v1/analyze', [
                'domain' => "example{$i}.com",
            ]);
        }

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'example11.com',
        ]);

        $response->assertStatus(429)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'retry_after',
                ],
            ])
            ->assertHeader('Retry-After');
    }

    /** @test */
    public function create_analysis_ssrf_blocked(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $ssrfPayloads = [
            'localhost',
            '127.0.0.1',
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '169.254.169.254', // Cloud metadata
            '[::1]', // IPv6 loopback
        ];

        foreach ($ssrfPayloads as $payload) {
            $response = $this->postJson('/api/v1/analyze', [
                'domain' => $payload,
            ]);

            $response->assertStatus(422);
        }
    }

    /** @test */
    public function create_analysis_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/analyze', [
            'domain' => 'example.com',
        ]);

        // Job should be dispatched (specific job class TBD by implementation)
        Queue::assertPushed(\App\Jobs\RunAnalysisJob::class);
    }

    /** @test */
    public function create_analysis_links_to_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'example.com',
        ]);

        $response->assertStatus(202);

        $analysis = SEOAnalysis::where('domain', 'example.com')->first();
        $this->assertEquals($user->id, $analysis->user_id);
        $this->assertNull($analysis->session_id);
    }

    /** @test */
    public function create_analysis_links_to_session(): void
    {
        $sessionId = (string) \Str::uuid();

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'example.com',
        ], [
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertStatus(202);

        $analysis = SEOAnalysis::where('domain', 'example.com')->first();
        $this->assertEquals($sessionId, $analysis->session_id);
        $this->assertNull($analysis->user_id);
    }

    // ============================================================
    // AC-002: GET /api/v1/analyses/{id} - Get Results
    // ============================================================

    /** @test */
    public function get_analysis_exists(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'status' => SEOAnalysis::STATUS_COMPLETED,
            'overall_score' => 85,
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $analysis->id)
            ->assertJsonPath('data.domain', $analysis->domain)
            ->assertJsonPath('data.overall_score', 85);
    }

    /** @test */
    public function get_analysis_not_found(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/analyses/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function get_analysis_ownership(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($other);

        $analysis = SEOAnalysis::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        // Same 404 to prevent enumeration
        $response->assertStatus(404);
    }

    /** @test */
    public function get_analysis_pending(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'status' => SEOAnalysis::STATUS_PENDING,
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'pending');
    }

    /** @test */
    public function get_analysis_running(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'status' => SEOAnalysis::STATUS_RUNNING,
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'running');
    }

    /** @test */
    public function get_analysis_completed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'status' => SEOAnalysis::STATUS_COMPLETED,
            'overall_score' => 85,
            'analysis_data' => [
                'indicators' => [
                    ['name' => 'title', 'score' => 3, 'status' => 'OK'],
                ],
            ],
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.overall_score', 85)
            ->assertJsonStructure(['data' => ['indicators']]);
    }

    /** @test */
    public function get_analysis_failed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'status' => SEOAnalysis::STATUS_FAILED,
            'error_message' => 'Analysis timeout',
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.error_message', 'Analysis timeout');
    }

    /** @test */
    public function get_analysis_anonymous_owner(): void
    {
        $sessionId = (string) \Str::uuid();

        $analysis = SEOAnalysis::factory()->create([
            'session_id' => $sessionId,
            'status' => SEOAnalysis::STATUS_COMPLETED,
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}", [], [
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function get_analysis_response_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'overall_summary' => 'Great website!',
            'category_scores' => ['content' => 85, 'technical' => 90],
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'domain',
                    'normalized_url',
                    'overall_score',
                    'status',
                    'error_message',
                    'overall_summary',
                    'category_scores',
                    'indicators',
                    'checked_at',
                    'expires_at',
                    'created_at',
                ],
            ]);
    }

    /** @test */
    public function get_analysis_includes_score(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'overall_score' => 75,
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.overall_score', 75);
    }

    /** @test */
    public function get_analysis_includes_indicators(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'analysis_data' => [
                'indicators' => [
                    ['name' => 'title', 'score' => 3, 'status' => 'OK', 'message' => 'Title found'],
                ],
            ],
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['indicators']]);
    }

    /** @test */
    public function get_analysis_includes_category_scores(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'category_scores' => [
                'content' => 85,
                'technical' => 90,
                'accessibility' => 75,
                'performance' => 80,
            ],
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.category_scores.content', 85)
            ->assertJsonPath('data.category_scores.technical', 90);
    }

    /** @test */
    public function get_analysis_includes_summary(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'overall_summary' => 'Your website performs well overall.',
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.overall_summary', 'Your website performs well overall.');
    }

    /** @test */
    public function get_analysis_etag(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertHeader('ETag');
    }

    /** @test */
    public function get_analysis_304_not_modified(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
        ]);

        // First request to get ETag
        $firstResponse = $this->getJson("/api/v1/analyses/{$analysis->id}");
        $etag = $firstResponse->headers->get('ETag');

        // Second request with If-None-Match
        $response = $this->getJson("/api/v1/analyses/{$analysis->id}", [], [
            'If-None-Match' => $etag,
        ]);

        $response->assertStatus(304);
    }

    // ============================================================
    // AC-003: GET /api/v1/analyses - List Analyses
    // ============================================================

    /** @test */
    public function list_analyses_authenticated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/analyses');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function list_analyses_anonymous(): void
    {
        $sessionId = (string) \Str::uuid();

        SEOAnalysis::factory()->count(2)->create([
            'session_id' => $sessionId,
        ]);

        $response = $this->getJson('/api/v1/analyses', [], [
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function list_analyses_pagination(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->count(20)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/analyses');

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data') // Default 15 per page
            ->assertJsonStructure(['meta' => ['current_page', 'per_page', 'total', 'last_page']]);
    }

    /** @test */
    public function list_analyses_per_page_param(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->count(25)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/analyses?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');
    }

    /** @test */
    public function list_analyses_per_page_max(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->count(150)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/analyses?per_page=200');

        // Should cap at 100
        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }

    /** @test */
    public function list_analyses_page_param(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->count(30)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/analyses?per_page=10&page=2');

        $response->assertStatus(200)
            ->assertJsonPath('meta.current_page', 2);
    }

    /** @test */
    public function list_analyses_filter_status(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => SEOAnalysis::STATUS_COMPLETED,
        ]);

        SEOAnalysis::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => SEOAnalysis::STATUS_PENDING,
        ]);

        $response = $this->getJson('/api/v1/analyses?status=completed');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function list_analyses_filter_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
        ]);

        SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'domain' => 'other.com',
        ]);

        $response = $this->getJson('/api/v1/analyses?domain=example.com');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.domain', 'example.com');
    }

    /** @test */
    public function list_analyses_sort_created_desc(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $first = SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);

        $second = SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/analyses');

        $response->assertStatus(200);
        $this->assertEquals($second->id, $response->json('data.0.id'));
    }

    /** @test */
    public function list_analyses_empty(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/analyses');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function list_analyses_only_owned(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        SEOAnalysis::factory()->count(5)->create([
            'user_id' => $other->id,
        ]);

        $response = $this->getJson('/api/v1/analyses');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function list_analyses_response_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/analyses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'domain',
                        'overall_score',
                        'status',
                        'checked_at',
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
    public function list_analyses_meta_pagination(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->count(30)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/analyses?per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 30)
            ->assertJsonPath('meta.last_page', 3);
    }

    /** @test */
    public function list_analyses_rate_limit(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Make 61 requests
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/v1/analyses');
        }

        // 61st should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function list_analyses_cache_headers(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/analyses');

        $response->assertStatus(200)
            ->assertHeader('Cache-Control');
    }

    // ============================================================
    // AC-004: GET /api/v1/analyze?domain=... - Cached by Domain
    // ============================================================

    /** @test */
    public function cached_analysis_found(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/analyze?domain=example.com');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $analysis->id);
    }

    /** @test */
    public function cached_analysis_not_found(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/analyze?domain=nonexistent.com');

        $response->assertStatus(404);
    }

    /** @test */
    public function cached_analysis_expired(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'expires_at' => now()->subDay(), // Expired
        ]);

        $response = $this->getJson('/api/v1/analyze?domain=example.com');

        $response->assertStatus(404);
    }

    /** @test */
    public function cached_analysis_valid(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/analyze?domain=example.com');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $analysis->id);
    }

    /** @test */
    public function cached_analysis_normalizes_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/analyze?domain=EXAMPLE.COM');

        $response->assertStatus(200);
    }

    /** @test */
    public function cached_analysis_user_specific(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);

        // Other user's analysis
        SEOAnalysis::factory()->completed()->create([
            'user_id' => $other->id,
            'domain' => 'example.com',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/analyze?domain=example.com');

        // Should not return other user's analysis
        $response->assertStatus(404);
    }

    /** @test */
    public function cached_analysis_session_specific(): void
    {
        $sessionId = (string) \Str::uuid();
        $otherSession = (string) \Str::uuid();

        // Other session's analysis
        SEOAnalysis::factory()->completed()->create([
            'session_id' => $otherSession,
            'domain' => 'example.com',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/analyze?domain=example.com', [], [
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function cached_analysis_etag(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/analyze?domain=example.com');

        $response->assertStatus(200)
            ->assertHeader('ETag');
    }

    /** @test */
    public function cached_analysis_304(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'expires_at' => now()->addDay(),
        ]);

        // First request for ETag
        $first = $this->getJson('/api/v1/analyze?domain=example.com');
        $etag = $first->headers->get('ETag');

        // Second request with If-None-Match
        $response = $this->getJson('/api/v1/analyze?domain=example.com', [], [
            'If-None-Match' => $etag,
        ]);

        $response->assertStatus(304);
    }

    /** @test */
    public function cached_analysis_rate_limit(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'expires_at' => now()->addDay(),
        ]);

        // Make 61 requests
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/v1/analyze?domain=example.com');
        }

        $response->assertStatus(429);
    }

    /** @test */
    public function cached_analysis_response_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'expires_at' => now()->addDay(),
            'overall_score' => 85,
        ]);

        $response = $this->getJson('/api/v1/analyze?domain=example.com');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'domain',
                    'overall_score',
                    'status',
                ],
            ]);
    }

    // ============================================================
    // AUTH: Authentication Tests
    // ============================================================

    /** @test */
    public function unauthenticated_list(): void
    {
        $response = $this->getJson('/api/v1/analyses');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_show(): void
    {
        $response = $this->getJson('/api/v1/analyses/1');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_create(): void
    {
        // Anonymous users can create analyses
        $sessionId = (string) \Str::uuid();

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'example.com',
        ], [
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function unauthenticated_cached(): void
    {
        // Anonymous users can get cached analyses
        $sessionId = (string) \Str::uuid();

        SEOAnalysis::factory()->completed()->create([
            'session_id' => $sessionId,
            'domain' => 'example.com',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/analyze?domain=example.com', [], [
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function session_cookie_anonymous(): void
    {
        // Session tracking for anonymous users
        $sessionId = (string) \Str::uuid();

        $response = $this->withHeaders([
            'X-Session-ID' => $sessionId,
        ])->postJson('/api/v1/analyze', [
            'domain' => 'example.com',
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('seo_analyses', [
            'session_id' => $sessionId,
        ]);
    }

    /** @test */
    public function bearer_token_authenticated(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/analyses');

        $response->assertStatus(200);
    }

    // ============================================================
    // SEC: Security Tests
    // ============================================================

    /** @test */
    public function ssrf_localhost(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'localhost',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function ssrf_private_ip(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $privateIPs = [
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '192.168.0.1',
        ];

        foreach ($privateIPs as $ip) {
            $response = $this->postJson('/api/v1/analyze', [
                'domain' => $ip,
            ]);

            $response->assertStatus(422);
        }
    }

    /** @test */
    public function ssrf_cloud_metadata(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => '169.254.169.254',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function ssrf_ipv6_loopback(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => '[::1]',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function ssrf_ipv6_private(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'fc00::1',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function ssrf_protocol_bypass(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $protocols = [
            'file:///etc/passwd',
            'ftp://example.com',
            'gopher://example.com',
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
        ];

        foreach ($protocols as $url) {
            $response = $this->postJson('/api/v1/analyze', [
                'domain' => $url,
            ]);

            $response->assertStatus(422);
        }
    }

    /** @test */
    public function sql_injection_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => "example.com'; DROP TABLE seo_analyses; --",
        ]);

        // Should either reject or safely store
        $response->assertStatus(422);

        // Verify table still exists
        $this->assertDatabaseMissing('seo_analyses', [
            'domain' => "example.com'; DROP TABLE seo_analyses; --",
        ]);
    }

    /** @test */
    public function xss_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => '<script>alert(1)</script>.com',
        ]);

        // Should reject or sanitize
        $response->assertStatus(422);
    }

    /** @test */
    public function enumeration_prevention(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($other);

        $analysis = SEOAnalysis::factory()->create([
            'user_id' => $owner->id,
        ]);

        // Try to access other user's analysis
        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        // Should return 404 (same as not found) to prevent enumeration
        $response->assertStatus(404);
    }

    /** @test */
    public function id_enumeration(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create analysis
        $analysis = SEOAnalysis::factory()->create([
            'user_id' => $user->id,
        ]);

        // Try to access with non-existent ID
        $response = $this->getJson('/api/v1/analyses/99999');

        $response->assertStatus(404);

        // Try to access other user's analysis (same response)
        $other = User::factory()->create();
        $otherAnalysis = SEOAnalysis::factory()->create([
            'user_id' => $other->id,
        ]);

        $response = $this->getJson("/api/v1/analyses/{$otherAnalysis->id}");

        $response->assertStatus(404);
    }

    // ============================================================
    // HTTP: HTTP Method Tests
    // ============================================================

    /** @test */
    public function post_analyze_only(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/analyze');
        $response->assertStatus(405);

        $response = $this->putJson('/api/v1/analyze', []);
        $response->assertStatus(405);

        $response = $this->deleteJson('/api/v1/analyze');
        $response->assertStatus(405);
    }

    /** @test */
    public function get_analyses_only(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyses', []);
        $response->assertStatus(405);

        $response = $this->putJson('/api/v1/analyses', []);
        $response->assertStatus(405);

        $response = $this->deleteJson('/api/v1/analyses');
        $response->assertStatus(405);
    }

    /** @test */
    public function get_analyses_id_only(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson("/api/v1/analyses/{$analysis->id}", []);
        $response->assertStatus(405);

        $response = $this->putJson("/api/v1/analyses/{$analysis->id}", []);
        $response->assertStatus(405);

        $response = $this->deleteJson("/api/v1/analyses/{$analysis->id}");
        $response->assertStatus(405);
    }

    /** @test */
    public function get_cached_only(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze?domain=example.com', []);
        $response->assertStatus(405);

        $response = $this->putJson('/api/v1/analyze?domain=example.com', []);
        $response->assertStatus(405);

        $response = $this->deleteJson('/api/v1/analyze?domain=example.com');
        $response->assertStatus(405);
    }

    /** @test */
    public function http_405_wrong_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // POST should work for /analyze
        $response = $this->postJson('/api/v1/analyze', ['domain' => 'example.com']);
        $response->assertStatus(202);

        // GET should work for /analyze?domain=
        $response = $this->getJson('/api/v1/analyze?domain=example.com');
        // 404 because no cached, but not 405
        $this->assertNotEquals(405, $response->status());
    }

    // ============================================================
    // EDGE: Edge Cases
    // ============================================================

    /** @test */
    public function unicode_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => '例え.jp',
        ]);

        // Should accept and convert to punycode
        $response->assertStatus(202);
    }

    /** @test */
    public function punycode_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'xn--r8jz45g.jp',
        ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function very_long_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $longDomain = str_repeat('a', 250) . '.com';

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => $longDomain,
        ]);

        // Should reject overly long domains
        $response->assertStatus(422);
    }

    /** @test */
    public function empty_domain_param(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => '',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function null_domain_param(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => null,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function whitespace_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => '  example.com  ',
        ]);

        $response->assertStatus(202);

        // Verify whitespace is trimmed
        $this->assertDatabaseHas('seo_analyses', [
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function uppercase_domain(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/analyze', [
            'domain' => 'EXAMPLE.COM',
        ]);

        $response->assertStatus(202);

        // Verify domain is lowercased
        $this->assertDatabaseHas('seo_analyses', [
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function invalid_analysis_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/analyses/abc');

        $response->assertStatus(404);
    }

    /** @test */
    public function negative_page_param(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/analyses?page=-1');

        // Should default to page 1 or return validation error
        $response->assertStatus(200);
    }

    /** @test */
    public function overflow_per_page(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->count(150)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/v1/analyses?per_page=1000');

        // Should cap at max 100
        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }

    // ============================================================
    // PERF: Performance Tests
    // ============================================================

    /** @test */
    public function list_response_time(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->count(100)->create(['user_id' => $user->id]);

        $start = microtime(true);
        $response = $this->getJson('/api/v1/analyses');
        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $elapsed, 'List response should be < 200ms');
    }

    /** @test */
    public function show_response_time(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $analysis = SEOAnalysis::factory()->completed()->create(['user_id' => $user->id]);

        $start = microtime(true);
        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");
        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(100, $elapsed, 'Show response should be < 100ms');
    }

    /** @test */
    public function create_response_time(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $start = microtime(true);
        $response = $this->postJson('/api/v1/analyze', ['domain' => 'example.com']);
        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(202);
        $this->assertLessThan(500, $elapsed, 'Create response should be < 500ms');
    }

    /** @test */
    public function cached_response_time(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        SEOAnalysis::factory()->completed()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'expires_at' => now()->addDay(),
        ]);

        $start = microtime(true);
        $response = $this->getJson('/api/v1/analyze?domain=example.com');
        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(100, $elapsed, 'Cached lookup should be < 100ms');
    }

    /** @test */
    public function pagination_performance(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create 1000 analyses
        SEOAnalysis::factory()->count(1000)->create(['user_id' => $user->id]);

        $start = microtime(true);
        $response = $this->getJson('/api/v1/analyses?per_page=100');
        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $elapsed, 'Pagination with 1000 records should be < 500ms');
    }
}
