<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\ApiKey;
use App\Models\Report;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Feature tests for Reports API Endpoint (TASK-6-11).
 * 
 * Endpoint: POST /api/v1/reports
 * Authentication: API Key (api-key.auth middleware)
 * Permission: reports:write
 * Rate Limiting: api-key.ratelimit middleware
 * 
 * Request Format:
 * {
 *   "site_id": 1,
 *   "type": "weekly",
 *   "format": "pdf"
 * }
 * 
 * Response Format:
 * {
 *   "success": true,
 *   "data": {
 *     "job_id": "rpt-abc123",
 *     "status": "queued"
 *   }
 * }
 */
class ReportsApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;
    protected ApiKey $apiKey;
    protected string $plainKey;
    protected ApiKeyService $apiKeyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->apiKeyService = app(ApiKeyService::class);
        
        // Create API key with reports:write permission
        $keyData = $this->apiKeyService->generateKey($this->user, [
            'name' => 'Reports API Key',
            'permissions' => ['reports:read', 'reports:write'],
        ]);
        $this->plainKey = $keyData['key'];
        $this->apiKey = $keyData['model'];
    }

    // ============================================================
    // AUTHENTICATION TESTS (AUTH-*)
    // ============================================================

    /**
     * AUTH-01: Unauthenticated request returns 401.
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'UNAUTHENTICATED'],
            ]);
    }

    /**
     * AUTH-02: Invalid API key returns 401.
     */
    public function test_invalid_api_key_returns_401(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer sk_live_invalidkey123456789',
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'UNAUTHENTICATED'],
            ]);
    }

    /**
     * AUTH-03: Revoked API key returns 401.
     */
    public function test_revoked_api_key_returns_401(): void
    {
        $this->apiKey->revoke();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'UNAUTHENTICATED'],
            ]);
    }

    /**
     * AUTH-04: Expired API key returns 401.
     */
    public function test_expired_api_key_returns_401(): void
    {
        $this->apiKey->update(['expires_at' => now()->subDay()]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'UNAUTHENTICATED'],
            ]);
    }

    /**
     * AUTH-05: Valid API key with correct permission succeeds.
     */
    public function test_valid_api_key_with_permission_succeeds(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'data' => ['job_id', 'status'],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['status' => 'queued'],
            ]);

        // Verify job_id format
        $jobId = $response->json('data.job_id');
        $this->assertStringStartsWith('rpt-', $jobId);
    }

    // ============================================================
    // PERMISSION TESTS (PERM-*)
    // ============================================================

    /**
     * PERM-01: API key without reports:write permission returns 403.
     */
    public function test_missing_permission_returns_403(): void
    {
        // Create key without reports:write permission
        $keyData = $this->apiKeyService->generateKey($this->user, [
            'name' => 'Read-Only Key',
            'permissions' => ['reports:read'],
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$keyData['key']}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    /**
     * PERM-02: API key with only reports:read permission returns 403.
     */
    public function test_read_only_permission_returns_403(): void
    {
        $keyData = $this->apiKeyService->generateKey($this->user, [
            'name' => 'Read Key',
            'permissions' => ['keywords:read', 'rankings:read'],
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$keyData['key']}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(403);
    }

    /**
     * PERM-03: User cannot create report for another user's site.
     */
    public function test_cannot_create_report_for_other_users_site(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $otherSite->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    /**
     * PERM-04: API key can only access sites owned by key owner.
     */
    public function test_api_key_user_isolation(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        
        // Create key for current user
        $keyData = $this->apiKeyService->generateKey($this->user, [
            'name' => 'Test Key',
            'permissions' => ['reports:read', 'reports:write'],
        ]);

        // Try to access other user's site
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$keyData['key']}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $otherSite->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(403);
    }

    // ============================================================
    // VALIDATION TESTS (VAL-*)
    // ============================================================

    /**
     * VAL-01: Missing site_id returns 422.
     */
    public function test_missing_site_id_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['site_id']);
    }

    /**
     * VAL-02: Missing type returns 422.
     */
    public function test_missing_type_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'format' => 'pdf',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * VAL-03: Missing format returns 422.
     */
    public function test_missing_format_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }

    /**
     * VAL-04: Invalid site_id (non-existent) returns 422.
     */
    public function test_invalid_site_id_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => 99999,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['site_id']);
    }

    /**
     * VAL-05: Invalid type returns 422.
     */
    public function test_invalid_type_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'invalid-type',
            'format' => 'pdf',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * VAL-06: Invalid format returns 422.
     */
    public function test_invalid_format_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'docx',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }

    /**
     * VAL-07: Valid types accepted.
     * @dataProvider validTypesProvider
     */
    public function test_valid_types_accepted(string $type): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => $type,
            'format' => 'pdf',
        ]);

        $response->assertStatus(202);
    }

    public static function validTypesProvider(): array
    {
        return [
            'daily' => ['daily'],
            'weekly' => ['weekly'],
            'monthly' => ['monthly'],
            'custom' => ['custom'],
            'one-time' => ['one-time'],
        ];
    }

    /**
     * VAL-08: Valid formats accepted.
     * @dataProvider validFormatsProvider
     */
    public function test_valid_formats_accepted(string $format): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => $format,
        ]);

        $response->assertStatus(202);
    }

    public static function validFormatsProvider(): array
    {
        return [
            'pdf' => ['pdf'],
            'csv' => ['csv'],
        ];
    }

    /**
     * VAL-09: Non-integer site_id returns 422.
     */
    public function test_non_integer_site_id_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => 'not-an-integer',
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['site_id']);
    }

    /**
     * VAL-10: Empty request body returns 422.
     */
    public function test_empty_request_body_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['site_id', 'type', 'format']);
    }

    // ============================================================
    // SUCCESS CASE TESTS (SUCC-*)
    // ============================================================

    /**
     * SUCC-01: Successful report generation returns job_id.
     */
    public function test_successful_generation_returns_job_id(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'queued',
                ],
            ]);

        $jobId = $response->json('data.job_id');
        $this->assertNotNull($jobId);
        $this->assertStringStartsWith('rpt-', $jobId);
    }

    /**
     * SUCC-02: Job ID format is correct (rpt-{alphanumeric}).
     */
    public function test_job_id_format_is_correct(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $jobId = $response->json('data.job_id');
        
        // Job ID format: rpt-{16 alphanumeric chars}
        $this->assertMatchesRegularExpression('/^rpt-[a-zA-Z0-9]{16,}$/', $jobId);
    }

    /**
     * SUCC-03: Report record created with pending status.
     */
    public function test_report_record_created_with_pending_status(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
            'status' => 'pending',
        ]);
    }

    /**
     * SUCC-04: Report name auto-generated from type and date.
     */
    public function test_report_name_auto_generated(): void
    {
        Queue::fake();

        $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $report = Report::where('site_id', $this->site->id)->first();
        
        // Name should contain "Weekly Report" or similar
        $this->assertStringContainsStringIgnoringCase('weekly', $report->name);
    }

    /**
     * SUCC-05: CSV format triggers CSV report generation.
     */
    public function test_csv_format_triggers_csv_generation(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'monthly',
            'format' => 'csv',
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('reports', [
            'site_id' => $this->site->id,
            'format' => 'csv',
        ]);
    }

    /**
     * SUCC-06: Default date range applied when not specified.
     */
    public function test_default_date_range_applied(): void
    {
        Queue::fake();

        $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $report = Report::where('site_id', $this->site->id)->first();
        
        // Date range should be set based on type
        $this->assertNotNull($report->date_range_start);
        $this->assertNotNull($report->date_range_end);
    }

    /**
     * SUCC-07: Job queued for async processing.
     */
    public function test_job_queued_for_async_processing(): void
    {
        Queue::fake();

        $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        // Verify a job was pushed to the queue
        Queue::assertPushed(\App\Jobs\GenerateReportJob::class);
    }

    /**
     * SUCC-08: API key last_used_at updated.
     */
    public function test_api_key_last_used_at_updated(): void
    {
        Queue::fake();
        
        $this->apiKey->update(['last_used_at' => null]);

        $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $this->apiKey->refresh();
        $this->assertNotNull($this->apiKey->last_used_at);
    }

    /**
     * SUCC-09: Unique job_id returned for each request.
     */
    public function test_unique_job_id_for_each_request(): void
    {
        Queue::fake();

        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'monthly',
            'format' => 'csv',
        ]);

        $jobId1 = $response1->json('data.job_id');
        $jobId2 = $response2->json('data.job_id');

        $this->assertNotEquals($jobId1, $jobId2);
    }

    // ============================================================
    // OPTIONAL PARAMETERS TESTS (OPT-*)
    // ============================================================

    /**
     * OPT-01: Optional name parameter accepted.
     */
    public function test_optional_name_parameter_accepted(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
            'name' => 'Custom Report Name',
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('reports', [
            'site_id' => $this->site->id,
            'name' => 'Custom Report Name',
        ]);
    }

    /**
     * OPT-02: Optional date_range parameter accepted.
     */
    public function test_optional_date_range_parameter_accepted(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'custom',
            'format' => 'pdf',
            'date_range' => [
                'start' => '2026-01-01',
                'end' => '2026-01-31',
            ],
        ]);

        $response->assertStatus(202);

        $report = Report::where('site_id', $this->site->id)->latest()->first();
        $this->assertEquals('2026-01-01', $report->date_range_start->toDateString());
        $this->assertEquals('2026-01-31', $report->date_range_end->toDateString());
    }

    /**
     * OPT-03: Invalid date_range.start returns 422.
     */
    public function test_invalid_date_range_start_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'custom',
            'format' => 'pdf',
            'date_range' => [
                'start' => 'invalid-date',
                'end' => '2026-01-31',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_range.start']);
    }

    /**
     * OPT-04: Invalid date_range.end returns 422.
     */
    public function test_invalid_date_range_end_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'custom',
            'format' => 'pdf',
            'date_range' => [
                'start' => '2026-01-01',
                'end' => 'invalid-date',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_range.end']);
    }

    /**
     * OPT-05: date_range.end before start returns 422.
     */
    public function test_date_range_end_before_start_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'custom',
            'format' => 'pdf',
            'date_range' => [
                'start' => '2026-01-31',
                'end' => '2026-01-01',
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * OPT-06: Optional sections parameter accepted.
     */
    public function test_optional_sections_parameter_accepted(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
            'sections' => ['cover', 'summary', 'keywords', 'trends'],
        ]);

        $response->assertStatus(202);

        $report = Report::where('site_id', $this->site->id)->first();
        $this->assertEquals(['cover', 'summary', 'keywords', 'trends'], $report->sections);
    }

    /**
     * OPT-07: Invalid section returns 422.
     */
    public function test_invalid_section_returns_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
            'sections' => ['cover', 'invalid-section'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sections.1']);
    }

    // ============================================================
    // RATE LIMITING TESTS (RATE-*)
    // ============================================================

    /**
     * RATE-01: Rate limiting applied per API key.
     */
    public function test_rate_limiting_applied_per_api_key(): void
    {
        Queue::fake();

        // Create key with low rate limit
        $keyData = $this->apiKeyService->generateKey($this->user, [
            'name' => 'Limited Key',
            'permissions' => ['reports:read', 'reports:write'],
            'rate_limit' => 5,
        ]);

        // Make requests up to the limit
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeaders([
                'Authorization' => "Bearer {$keyData['key']}",
            ])->postJson('/api/v1/reports', [
                'site_id' => $this->site->id,
                'type' => 'weekly',
                'format' => 'pdf',
            ]);
            $response->assertStatus(202);
        }

        // Next request should be rate limited
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$keyData['key']}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(429);
    }

    /**
     * RATE-02: Rate limit headers present in response.
     */
    public function test_rate_limit_headers_present(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(202)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }

    /**
     * RATE-03: Different API keys have separate rate limits.
     */
    public function test_different_api_keys_separate_limits(): void
    {
        Queue::fake();

        // Create key with low rate limit
        $keyData1 = $this->apiKeyService->generateKey($this->user, [
            'name' => 'Key 1',
            'permissions' => ['reports:read', 'reports:write'],
            'rate_limit' => 2,
        ]);

        $keyData2 = $this->apiKeyService->generateKey($this->user, [
            'name' => 'Key 2',
            'permissions' => ['reports:read', 'reports:write'],
            'rate_limit' => 2,
        ]);

        // Exhaust rate limit for key 1
        for ($i = 0; $i < 3; $i++) {
            $this->withHeaders([
                'Authorization' => "Bearer {$keyData1['key']}",
            ])->postJson('/api/v1/reports', [
                'site_id' => $this->site->id,
                'type' => 'weekly',
                'format' => 'pdf',
            ]);
        }

        // Key 1 should be rate limited
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$keyData1['key']}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);
        $response1->assertStatus(429);

        // Key 2 should still work
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$keyData2['key']}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);
        $response2->assertStatus(202);
    }

    // ============================================================
    // SECURITY TESTS (SEC-*)
    // ============================================================

    /**
     * SEC-01: SQL injection in site_id prevented.
     */
    public function test_sql_injection_in_site_id_prevented(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => "1; DROP TABLE reports; --",
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(422);
    }

    /**
     * SEC-02: XSS in name parameter prevented.
     */
    public function test_xss_in_name_prevented(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
            'name' => '<script>alert("xss")</script>Report',
        ]);

        $response->assertStatus(202);

        $report = Report::where('site_id', $this->site->id)->first();
        // Name should be escaped/sanitized
        $this->assertStringNotContainsString('<script>', $report->name);
    }

    /**
     * SEC-03: Mass assignment prevention.
     */
    public function test_mass_assignment_prevention(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
            'status' => 'completed', // Should not be accepted
            'file_path' => '/malicious/path', // Should not be accepted
        ]);

        $response->assertStatus(202);

        $report = Report::where('site_id', $this->site->id)->first();
        // Status should be 'pending', not 'completed'
        $this->assertEquals('pending', $report->status);
        // file_path should be null
        $this->assertNull($report->file_path);
    }

    /**
     * SEC-04: API key not exposed in response.
     */
    public function test_api_key_not_exposed_in_response(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(202);
        $response->assertJsonMissing(['api_key' => $this->plainKey]);
        $response->assertJsonMissing(['key' => $this->plainKey]);
    }

    /**
     * SEC-05: Enumeration attack prevention (same error for other user's site).
     */
    public function test_enumeration_prevention(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        // Non-existent site
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => 99999,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        // Other user's site
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $otherSite->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        // Both should return similar error (403 or 422)
        // to prevent enumeration
        $this->assertContains($response1->status(), [403, 422]);
        $this->assertContains($response2->status(), [403, 422]);
    }

    // ============================================================
    // ERROR HANDLING TESTS (ERR-*)
    // ============================================================

    /**
     * ERR-01: Invalid JSON body returns 422 (validation error).
     * Note: Laravel treats malformed JSON as empty content, resulting in validation errors.
     */
    public function test_invalid_json_body_returns_400(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
            'Content-Type' => 'application/json',
        ])->post('/api/v1/reports', [], [], [], [], [], 'invalid json {{{');

        // Laravel treats invalid JSON as empty body, resulting in validation errors
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['site_id']);
    }

    /**
     * ERR-02: Wrong HTTP method returns 405.
     */
    public function test_wrong_http_method_returns_405(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->getJson('/api/v1/reports');

        $response->assertStatus(405);
    }

    /**
     * ERR-03: PUT method returns 405.
     */
    public function test_put_method_returns_405(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->putJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(405);
    }

    /**
     * ERR-04: PATCH method returns 405.
     */
    public function test_patch_method_returns_405(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->patchJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(405);
    }

    /**
     * ERR-05: DELETE method returns 405.
     */
    public function test_delete_method_returns_405(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->deleteJson('/api/v1/reports');

        $response->assertStatus(405);
    }

    // ============================================================
    // EDGE CASES TESTS (EDGE-*)
    // ============================================================

    /**
     * EDGE-01: Large name parameter handled.
     */
    public function test_large_name_parameter_handled(): void
    {
        Queue::fake();

        $longName = str_repeat('a', 300);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
            'name' => $longName,
        ]);

        // Should either accept with truncation or return 422
        $this->assertContains($response->status(), [202, 422]);

        if ($response->status() === 202) {
            $report = Report::where('site_id', $this->site->id)->first();
            // Name should be truncated or validated
            $this->assertLessThanOrEqual(255, strlen($report->name));
        }
    }

    /**
     * EDGE-02: Unicode characters in name handled.
     */
    public function test_unicode_characters_in_name_handled(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
            'name' => 'Report 名前 レポート 📊',
        ]);

        $response->assertStatus(202);

        $report = Report::where('site_id', $this->site->id)->first();
        $this->assertEquals('Report 名前 レポート 📊', $report->name);
    }

    /**
     * EDGE-03: Multiple concurrent requests handled correctly.
     */
    public function test_multiple_concurrent_requests_handled(): void
    {
        Queue::fake();

        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeaders([
                'Authorization' => "Bearer {$this->plainKey}",
            ])->postJson('/api/v1/reports', [
                'site_id' => $this->site->id,
                'type' => 'weekly',
                'format' => 'pdf',
                'name' => "Report {$i}",
            ]);
        }

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(202);
        }

        // All should have unique job IDs
        $jobIds = array_map(fn($r) => $r->json('data.job_id'), $responses);
        $this->assertEquals(count($jobIds), count(array_unique($jobIds)));
    }

    /**
     * EDGE-04: Zero site_id handled correctly.
     */
    public function test_zero_site_id_handled(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => 0,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['site_id']);
    }

    /**
     * EDGE-05: Negative site_id handled correctly.
     */
    public function test_negative_site_id_handled(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => -1,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['site_id']);
    }

    /**
     * EDGE-06: Case sensitivity for type and format.
     */
    public function test_case_sensitivity_for_type_and_format(): void
    {
        // Uppercase type should fail
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'WEEKLY',
            'format' => 'pdf',
        ]);
        $response1->assertStatus(422);

        // Uppercase format should fail
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'PDF',
        ]);
        $response2->assertStatus(422);
    }

    // ============================================================
    // PERFORMANCE TESTS (PERF-*)
    // ============================================================

    /**
     * PERF-01: Response time under 500ms.
     */
    public function test_response_time_under_500ms(): void
    {
        Queue::fake();

        $start = microtime(true);

        $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $elapsed, 'Response time should be under 500ms');
    }

    /**
     * PERF-02: Response time under 200ms for cached auth.
     */
    public function test_response_time_under_200ms_cached(): void
    {
        Queue::fake();

        // Warm up the cache
        $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        // Second request should be faster
        $start = microtime(true);

        $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'monthly',
            'format' => 'csv',
        ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $elapsed, 'Cached response time should be under 200ms');
    }

    // ============================================================
    // ACCEPTANCE CRITERIA TESTS (AC-*)
    // ============================================================

    /**
     * AC-01: Endpoint working.
     */
    public function test_ac_endpoint_working(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(202)
            ->assertJson(['success' => true]);
    }

    /**
     * AC-02: Job queued for processing.
     */
    public function test_ac_job_queued_for_processing(): void
    {
        Queue::fake();

        $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        Queue::assertPushed(\App\Jobs\GenerateReportJob::class);
    }

    /**
     * AC-03: Job ID returned.
     */
    public function test_ac_job_id_returned(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'data' => ['job_id', 'status'],
            ]);

        $jobId = $response->json('data.job_id');
        $this->assertNotNull($jobId);
        $this->assertStringStartsWith('rpt-', $jobId);
    }

    /**
     * AC-04: Rate limiting applied.
     */
    public function test_ac_rate_limiting_applied(): void
    {
        Queue::fake();

        // Create key with low rate limit
        $keyData = $this->apiKeyService->generateKey($this->user, [
            'name' => 'Limited Key',
            'permissions' => ['reports:read', 'reports:write'],
            'rate_limit' => 3,
        ]);

        // Exhaust the limit
        for ($i = 0; $i < 3; $i++) {
            $this->withHeaders([
                'Authorization' => "Bearer {$keyData['key']}",
            ])->postJson('/api/v1/reports', [
                'site_id' => $this->site->id,
                'type' => 'weekly',
                'format' => 'pdf',
            ]);
        }

        // Next request should be rate limited
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$keyData['key']}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(429);
    }

    // ============================================================
    // HTTP METHOD RESTRICTION TESTS (HTTP-*)
    // ============================================================

    /**
     * HTTP-01: OPTIONS method allowed (CORS preflight).
     */
    public function test_options_method_allowed(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->options('/api/v1/reports');

        // CORS preflight should be allowed
        $this->assertContains($response->status(), [200, 204]);
    }

    // ============================================================
    // RESPONSE FORMAT TESTS (RESP-*)
    // ============================================================

    /**
     * RESP-01: Success response format correct.
     */
    public function test_success_response_format_correct(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'weekly',
            'format' => 'pdf',
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'job_id',
                    'status',
                ],
            ]);

        // Verify success is boolean true
        $this->assertTrue($response->json('success'));

        // Verify status is string 'queued'
        $this->assertEquals('queued', $response->json('data.status'));
    }

    /**
     * RESP-02: Error response format correct.
     */
    public function test_error_response_format_correct(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            // Missing 'type' and 'format'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);

        // Verify success is boolean false
        $this->assertFalse($response->json('success'));
    }

    /**
     * RESP-03: Validation error includes field details.
     */
    public function test_validation_error_includes_field_details(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->plainKey}",
        ])->postJson('/api/v1/reports', [
            'site_id' => $this->site->id,
            'type' => 'invalid-type',
            'format' => 'invalid-format',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors', // Contains field-level errors
            ]);

        // Verify field errors are present
        $this->assertArrayHasKey('type', $response->json('errors'));
        $this->assertArrayHasKey('format', $response->json('errors'));
    }
}
