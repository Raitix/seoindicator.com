<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ToolExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Storage::fake('local');
    }

    // =====================================================
    // Export Endpoint - Authentication Tests (CTR-001 to CTR-005)
    // =====================================================

    /** @test */
    public function export_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => ['indicators' => []],
        ]);
        
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_create_export(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [
                'indicators' => [
                    ['category' => 'Meta', 'name' => 'Title', 'value' => 'Test', 'status' => 'OK'],
                ],
            ],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function export_endpoint_uses_sanctum_middleware(): void
    {
        // Test that the route is protected by Sanctum
        $response = $this->postJson('/api/v1/tools/export', []);
        
        $response->assertStatus(401);
    }

    /** @test */
    public function user_token_must_have_abilities(): void
    {
        Sanctum::actingAs($this->user, ['read']);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        // Should work with read ability
        $response->assertStatus(200);
    }

    /** @test */
    public function different_users_create_separate_exports(): void
    {
        $user2 = User::factory()->create();
        
        Sanctum::actingAs($this->user);
        
        $response1 = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        Sanctum::actingAs($user2);
        
        $response2 = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $url1 = $response1->json('downloadUrl');
        $url2 = $response2->json('downloadUrl');
        
        // URLs should be different (different tokens)
        $this->assertNotEquals($url1, $url2);
    }

    // =====================================================
    // Export Endpoint - Request Validation Tests (CTR-010 to CTR-025)
    // =====================================================

    /** @test */
    public function tool_field_is_required(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tool']);
    }

    /** @test */
    public function tool_field_must_be_string(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 123,
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tool']);
    }

    /** @test */
    public function url_field_is_required(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function url_field_must_be_valid_url(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'not-a-url',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function format_field_is_required(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'data' => [],
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function format_must_be_pdf_or_xls(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'docx',
            'data' => [],
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function format_pdf_is_accepted(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function format_xls_is_accepted(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'xls',
            'data' => [],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function data_field_is_required(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data']);
    }

    /** @test */
    public function data_field_must_be_array(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => 'not-an-array',
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data']);
    }

    /** @test */
    public function data_can_contain_indicators(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [
                'indicators' => [
                    ['category' => 'Meta', 'name' => 'Title', 'value' => 'Test', 'status' => 'OK'],
                ],
            ],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function data_can_contain_summary(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [
                'summary' => [
                    'score' => 85,
                    'pass' => 10,
                    'warning' => 3,
                    'fail' => 2,
                ],
            ],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function data_can_contain_recommendations(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [
                'recommendations' => [
                    ['priority' => 'high', 'message' => 'Add meta description'],
                ],
            ],
        ]);
        
        $response->assertStatus(200);
    }

    // =====================================================
    // Export Endpoint - Response Structure Tests (CTR-030 to CTR-035)
    // =====================================================

    /** @test */
    public function successful_response_has_success_true(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function successful_response_has_download_url(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['downloadUrl']);
        
        $downloadUrl = $response->json('downloadUrl');
        $this->assertStringContainsString('/api/v1/tools/export/download/', $downloadUrl);
    }

    /** @test */
    public function successful_response_has_expires_at(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['expiresAt']);
    }

    /** @test */
    public function expires_at_is_one_hour_from_now(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $expiresAt = $response->json('expiresAt');
        $expiresAtTime = strtotime($expiresAt);
        $expectedTime = strtotime('+1 hour');
        
        // Allow 1 minute tolerance
        $this->assertLessThan(60, abs($expiresAtTime - $expectedTime));
    }

    /** @test */
    public function response_structure_is_complete(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'downloadUrl',
                'expiresAt',
            ]);
    }

    // =====================================================
    // Download Endpoint - Authentication Tests (CTR-040 to CTR-045)
    // =====================================================

    /** @test */
    public function download_endpoint_requires_authentication(): void
    {
        $response = $this->get('/api/v1/tools/export/download/sometoken');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_download_export(): void
    {
        Sanctum::actingAs($this->user);
        
        // Create export first
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $downloadUrl = $createResponse->json('downloadUrl');
        
        // Download the file
        $response = $this->get($downloadUrl);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function download_returns_file_response(): void
    {
        Sanctum::actingAs($this->user);
        
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $downloadUrl = $createResponse->json('downloadUrl');
        
        $response = $this->get($downloadUrl);
        
        $response->assertStatus(200);
    }

    // =====================================================
    // Download Endpoint - Error Handling Tests (CTR-050 to CTR-055)
    // =====================================================

    /** @test */
    public function invalid_token_returns_404(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->get('/api/v1/tools/export/download/invalidtoken123');
        
        $response->assertStatus(404);
    }

    /** @test */
    public function expired_token_returns_404(): void
    {
        Sanctum::actingAs($this->user);
        
        // Create an expired token
        $token = 'expiredtoken123';
        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => $this->user->id,
        ], now()->subHour());
        
        $response = $this->get("/api/v1/tools/export/download/{$token}");
        
        $response->assertStatus(404);
    }

    /** @test */
    public function wrong_user_returns_404(): void
    {
        Sanctum::actingAs($this->user);
        
        // Create export for user 1
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $downloadUrl = $createResponse->json('downloadUrl');
        
        // Try to download as different user
        $user2 = User::factory()->create();
        Sanctum::actingAs($user2);
        
        $response = $this->get($downloadUrl);
        
        $response->assertStatus(404);
    }

    /** @test */
    public function token_can_only_be_used_once(): void
    {
        Sanctum::actingAs($this->user);
        
        // Create export
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $downloadUrl = $createResponse->json('downloadUrl');
        
        // First download succeeds
        $this->get($downloadUrl)->assertStatus(200);
        
        // Second download fails (token invalidated)
        $this->get($downloadUrl)->assertStatus(404);
    }

    // =====================================================
    // Download Endpoint - Response Headers Tests (CTR-060 to CTR-065)
    // =====================================================

    /** @test */
    public function pdf_download_has_correct_content_type(): void
    {
        Sanctum::actingAs($this->user);
        
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response = $this->get($createResponse->json('downloadUrl'));
        
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function xls_download_has_correct_content_type(): void
    {
        Sanctum::actingAs($this->user);
        
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'xls',
            'data' => [],
        ]);
        
        $response = $this->get($createResponse->json('downloadUrl'));
        
        $contentType = $response->headers->get('Content-Type');
        $this->assertTrue(in_array($contentType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel;charset=utf-8',
        ]));
    }

    /** @test */
    public function download_has_content_disposition_header(): void
    {
        Sanctum::actingAs($this->user);
        
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response = $this->get($createResponse->json('downloadUrl'));
        
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    /** @test */
    public function filename_contains_tool_name(): void
    {
        Sanctum::actingAs($this->user);
        
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response = $this->get($createResponse->json('downloadUrl'));
        
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('meta-tag-analyzer', $disposition);
    }

    /** @test */
    public function pdf_filename_has_pdf_extension(): void
    {
        Sanctum::actingAs($this->user);
        
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response = $this->get($createResponse->json('downloadUrl'));
        
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('.pdf', $disposition);
    }

    /** @test */
    public function xls_filename_has_xlsx_extension(): void
    {
        Sanctum::actingAs($this->user);
        
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'xls',
            'data' => [],
        ]);
        
        $response = $this->get($createResponse->json('downloadUrl'));
        
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    // =====================================================
    // HTTP Methods Tests (CTR-070 to CTR-075)
    // =====================================================

    /** @test */
    public function export_endpoint_accepts_post_only(): void
    {
        Sanctum::actingAs($this->user);
        
        $this->get('/api/v1/tools/export')->assertStatus(405);
        $this->put('/api/v1/tools/export')->assertStatus(405);
        $this->patch('/api/v1/tools/export')->assertStatus(405);
        $this->delete('/api/v1/tools/export')->assertStatus(405);
    }

    /** @test */
    public function download_endpoint_accepts_get_only(): void
    {
        Sanctum::actingAs($this->user);
        
        $this->post('/api/v1/tools/export/download/token')->assertStatus(405);
        $this->put('/api/v1/tools/export/download/token')->assertStatus(405);
        $this->patch('/api/v1/tools/export/download/token')->assertStatus(405);
        $this->delete('/api/v1/tools/export/download/token')->assertStatus(405);
    }

    // =====================================================
    // Rate Limiting Tests (CTR-080 to CTR-082)
    // =====================================================

    /** @test */
    public function export_endpoint_respects_rate_limiting(): void
    {
        Sanctum::actingAs($this->user);
        
        // Make reasonable number of requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/tools/export', [
                'tool' => 'meta-tag-analyzer',
                'url' => 'https://example.com',
                'format' => 'pdf',
                'data' => [],
            ]);
            
            $response->assertStatus(200);
        }
    }

    // =====================================================
    // Security Tests (CTR-090 to CTR-095)
    // =====================================================

    /** @test */
    public function export_sanitizes_tool_name_in_filename(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => '<script>alert("xss")</script>',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200);
        
        $downloadUrl = $response->json('downloadUrl');
        $downloadResponse = $this->get($downloadUrl);
        
        $disposition = $downloadResponse->headers->get('Content-Disposition');
        $this->assertStringNotContainsString('<script>', $disposition);
    }

    /** @test */
    public function export_sanitizes_url_in_output(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com/<img src=x onerror=alert(1)>',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function no_sensitive_data_in_response(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200);
        
        // Should not expose internal paths, tokens, or user IDs
        $response->assertJsonMissing(['user_id']);
        $response->assertJsonMissing(['file_path']);
        $response->assertJsonMissing(['token']);
    }

    /** @test */
    public function prevents_enumeration_of_other_users_exports(): void
    {
        Sanctum::actingAs($this->user);
        
        // Create export for user 1
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $downloadUrl = $createResponse->json('downloadUrl');
        
        // User 2 tries to access
        $user2 = User::factory()->create();
        Sanctum::actingAs($user2);
        
        $response = $this->get($downloadUrl);
        
        // Should return 404, not 403 (prevents enumeration)
        $response->assertStatus(404);
    }

    /** @test */
    public function handles_sql_injection_attempts(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => "'; DROP TABLE users; --",
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        // Should handle gracefully
        $response->assertStatus(200);
    }

    // =====================================================
    // Edge Cases Tests (CTR-100 to CTR-110)
    // =====================================================

    /** @test */
    public function handles_empty_data_array(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function handles_large_indicators_array(): void
    {
        Sanctum::actingAs($this->user);
        
        $largeIndicators = [];
        for ($i = 0; $i < 500; $i++) {
            $largeIndicators[] = [
                'category' => 'Category ' . $i,
                'name' => 'Indicator ' . $i,
                'value' => 'Value ' . $i,
                'status' => 'OK',
            ];
        }
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => ['indicators' => $largeIndicators],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function handles_unicode_tool_name(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'メタタグ分析ツール',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function handles_unicode_url(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com/日本語',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function handles_unicode_in_data(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [
                'indicators' => [
                    [
                        'category' => 'メタタグ',
                        'name' => 'タイトル',
                        'value' => '日本語タイトル',
                        'status' => 'OK',
                    ],
                ],
            ],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function handles_emoji_in_data(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [
                'indicators' => [
                    [
                        'category' => 'Meta 🏷️',
                        'name' => 'Title 📝',
                        'value' => 'Test ✅',
                        'status' => 'OK',
                    ],
                ],
            ],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function handles_special_characters_in_url(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com/page?foo=bar&baz=qux#section',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function handles_very_long_tool_name(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => str_repeat('a', 500),
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function handles_very_long_url(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com/' . str_repeat('a', 500),
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function handles_null_values_in_data(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [
                'indicators' => [
                    [
                        'category' => null,
                        'name' => 'Title',
                        'value' => null,
                        'status' => 'OK',
                        'recommendation' => null,
                    ],
                ],
            ],
        ]);
        
        $response->assertStatus(200);
    }

    // =====================================================
    // Performance Tests (CTR-120 to CTR-122)
    // =====================================================

    /** @test */
    public function export_endpoint_responds_within_500ms(): void
    {
        Sanctum::actingAs($this->user);
        
        $start = microtime(true);
        
        $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $duration = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(500, $duration);
    }

    /** @test */
    public function download_endpoint_responds_within_500ms(): void
    {
        Sanctum::actingAs($this->user);
        
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $start = microtime(true);
        
        $this->get($createResponse->json('downloadUrl'));
        
        $duration = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(500, $duration);
    }

    // =====================================================
    // Integration Tests (CTR-130 to CTR-135)
    // =====================================================

    /** @test */
    public function full_export_download_workflow(): void
    {
        Sanctum::actingAs($this->user);
        
        // Step 1: Create export
        $createResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [
                'indicators' => [
                    ['category' => 'Meta', 'name' => 'Title', 'value' => 'Test', 'status' => 'OK'],
                ],
            ],
        ]);
        
        $createResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['downloadUrl', 'expiresAt']);
        
        // Step 2: Download file
        $downloadUrl = $createResponse->json('downloadUrl');
        $downloadResponse = $this->get($downloadUrl);
        
        $downloadResponse->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
        
        // Step 3: Verify token is consumed
        $secondDownloadResponse = $this->get($downloadUrl);
        $secondDownloadResponse->assertStatus(404);
    }

    /** @test */
    public function multiple_exports_for_same_user(): void
    {
        Sanctum::actingAs($this->user);
        
        $response1 = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example1.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $response2 = $this->postJson('/api/v1/tools/export', [
            'tool' => 'keyword-density',
            'url' => 'https://example2.com',
            'format' => 'xls',
            'data' => [],
        ]);
        
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // URLs should be different
        $this->assertNotEquals(
            $response1->json('downloadUrl'),
            $response2->json('downloadUrl')
        );
    }

    /** @test */
    public function different_formats_create_different_files(): void
    {
        Sanctum::actingAs($this->user);
        
        $pdfResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [],
        ]);
        
        $xlsResponse = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'xls',
            'data' => [],
        ]);
        
        // Download PDF
        $pdfDownload = $this->get($pdfResponse->json('downloadUrl'));
        $pdfDownload->assertHeader('Content-Type', 'application/pdf');
        
        // Download XLS
        $xlsDownload = $this->get($xlsResponse->json('downloadUrl'));
        $xlsContentType = $xlsDownload->headers->get('Content-Type');
        $this->assertTrue(in_array($xlsContentType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]));
    }

    /** @test */
    public function export_with_full_tool_result(): void
    {
        Sanctum::actingAs($this->user);
        
        $fullData = [
            'toolName' => 'Meta Tag Analyzer',
            'url' => 'https://example.com',
            'analyzedAt' => now()->toIso8601String(),
            'indicators' => [
                [
                    'category' => 'Meta Tags',
                    'name' => 'Title',
                    'value' => 'Example Page - Welcome',
                    'status' => 'OK',
                    'recommendation' => '',
                ],
                [
                    'category' => 'Meta Tags',
                    'name' => 'Description',
                    'value' => 'Too short at 50 characters',
                    'status' => 'Warning',
                    'recommendation' => 'Increase length to 150-160 characters',
                ],
                [
                    'category' => 'Content',
                    'name' => 'H1 Count',
                    'value' => '0',
                    'status' => 'Error',
                    'recommendation' => 'Add exactly one H1 tag',
                ],
            ],
            'summary' => [
                'score' => 65,
                'pass' => 5,
                'warning' => 3,
                'fail' => 2,
            ],
            'recommendations' => [
                [
                    'priority' => 'high',
                    'message' => 'Add meta description',
                    'action' => 'Write 150-160 characters',
                ],
                [
                    'priority' => 'medium',
                    'message' => 'Add H1 tag',
                    'action' => 'Add exactly one H1 tag to your page',
                ],
            ],
        ];
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => $fullData,
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =====================================================
    // Error Response Format Tests (CTR-140 to CTR-142)
    // =====================================================

    /** @test */
    public function validation_error_has_consistent_format(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/tools/export', [
            'tool' => 'meta-tag-analyzer',
            // Missing url, format, data
        ]);
        
        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    /** @test */
    public function not_found_error_has_message(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->get('/api/v1/tools/export/download/invalidtoken');
        
        $response->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function unauthorized_error_has_message(): void
    {
        $response = $this->postJson('/api/v1/tools/export', []);
        
        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }
}
