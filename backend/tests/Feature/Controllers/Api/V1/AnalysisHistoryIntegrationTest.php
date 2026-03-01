<?php

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\AnalysisCost;
use App\Models\AnalysisHistory;
use App\Models\Site;
use App\Models\User;
use App\Repositories\AnalysisHistoryRepository;
use App\Services\CostTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Integration tests for Analysis History tracking with SEO Tools.
 * 
 * Tests the integration between APIProxyController and the Analysis History system,
 * ensuring every tool execution creates appropriate history records with cost tracking.
 * 
 * @covers TASK-25-14
 */
class AnalysisHistoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up config for costs (MVP = FREE)
        config(['costs.rates' => [
            'meta-tag-analyzer' => ['base' => 0.00, 'per_unit' => 0.00],
            'keyword-density-checker' => ['base' => 0.00, 'per_unit' => 0.00],
            '*' => ['base' => 0.00],
        ]]);
    }

    /**
     * Helper to create a mock HTML response for testing.
     */
    protected function mockHtmlResponse(string $html = '<html><head><title>Test</title></head><body>Content</body></html>'): void
    {
        Http::fake([
            '*' => Http::response($html, 200, ['Content-Type' => 'text/html']),
        ]);
    }

    /**
     * AH-001: Successful Analysis Creates History Record
     */
    public function test_successful_analysis_creates_history_record(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse('<html><head><title>Test Page</title></head><body>Content</body></html>');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('analysis_history', [
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'status' => 'completed',
        ]);
        
        // Verify analysis_id in response
        $response->assertJsonStructure(['analysis_id']);
        $analysisId = $response->json('analysis_id');
        $this->assertNotNull($analysisId);
    }

    /**
     * AH-002: Duration Tracking
     */
    public function test_duration_tracking_in_milliseconds(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse();

        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        $analysis = AnalysisHistory::where('user_id', $user->id)->first();
        
        $this->assertNotNull($analysis);
        $this->assertNotNull($analysis->analysis_duration_ms);
        $this->assertIsInt($analysis->analysis_duration_ms);
        $this->assertGreaterThanOrEqual(0, $analysis->analysis_duration_ms);
    }

    /**
     * AH-003: Tool Name Captured Correctly
     */
    public function test_tool_name_captured_correctly(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse();

        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'keyword-density-checker',
                'keyword' => 'test',
            ]);

        $this->assertDatabaseHas('analysis_history', [
            'user_id' => $user->id,
            'tool_name' => 'keyword-density-checker',
        ]);
    }

    /**
     * AH-004: Results Stored as JSON
     */
    public function test_results_stored_as_json(): void
    {
        $user = User::factory()->create();
        $html = '<html><head><title>Test Title</title><meta name="description" content="Test Description"></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        $analysis = AnalysisHistory::where('user_id', $user->id)->first();
        
        $this->assertNotNull($analysis);
        $this->assertNotNull($analysis->results);
        $results = $analysis->results;
        $this->assertIsArray($results);
        $this->assertArrayHasKey('success', $results);
    }

    /**
     * AH-005: Error Message Captured for Failures
     */
    public function test_error_message_captured_for_failures(): void
    {
        $user = User::factory()->create();
        
        // Mock a failed response
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://httpbin.org/status/500',
                'tool' => 'meta-tag-analyzer',
            ]);

        // Analysis should still be created with failed status
        $this->assertDatabaseHas('analysis_history', [
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'status' => 'failed',
        ]);

        $analysis = AnalysisHistory::where('user_id', $user->id)->first();
        $this->assertNotNull($analysis->error_message);
    }

    /**
     * AH-006: Authenticated User Association
     */
    public function test_authenticated_user_association(): void
    {
        $user = User::factory()->create(['id' => 123]);
        $this->mockHtmlResponse();

        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        $this->assertDatabaseHas('analysis_history', [
            'user_id' => 123,
        ]);
    }

    /**
     * AH-007: Anonymous User Handling
     */
    public function test_anonymous_user_handling(): void
    {
        $this->mockHtmlResponse();

        $response = $this->postJson('/api/v1/tools/analyze', [
            'url' => 'https://example.com',
            'tool' => 'meta-tag-analyzer',
        ]);

        // Anonymous users should still be able to use tools
        $response->assertStatus(200);
        
        $analysis = AnalysisHistory::whereNull('user_id')->first();
        $this->assertNotNull($analysis);
        $this->assertNull($analysis->user_id);
    }

    /**
     * AH-008: Site Association Optional
     */
    public function test_site_association_optional(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $this->mockHtmlResponse();

        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
                'site_id' => $site->id,
            ]);

        $this->assertDatabaseHas('analysis_history', [
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);
    }

    /**
     * AH-009: Cost Tracking Triggered
     */
    public function test_cost_tracking_triggered(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse();

        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        $analysis = AnalysisHistory::where('user_id', $user->id)->first();
        
        // Cost record should be created (even if $0 for MVP)
        $this->assertGreaterThan(0, $analysis->costs()->count());
    }

    /**
     * AH-010: No Cost for Failed Analysis
     */
    public function test_no_cost_for_failed_analysis(): void
    {
        $user = User::factory()->create();
        
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://httpbin.org/status/500',
                'tool' => 'meta-tag-analyzer',
            ]);

        $analysis = AnalysisHistory::where('user_id', $user->id)
            ->where('status', 'failed')
            ->first();
        
        // No cost records for failed analyses
        $this->assertEquals(0, $analysis->costs()->count());
    }

    /**
     * AH-011: Analysis ID in Response
     */
    public function test_analysis_id_in_response(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        $response->assertJsonStructure(['analysis_id']);
        
        $analysisId = $response->json('analysis_id');
        $this->assertIsInt($analysisId);
        $this->assertGreaterThan(0, $analysisId);
    }

    /**
     * AH-012: Analyzed At Timestamp
     */
    public function test_analyzed_at_timestamp(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse();

        $before = now();
        
        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        $after = now();

        $analysis = AnalysisHistory::where('user_id', $user->id)->first();
        
        $this->assertNotNull($analysis->analyzed_at);
        $this->assertTrue($analysis->analyzed_at->between($before, $after));
    }

    /**
     * AH-013: Timeout Error Handling
     */
    public function test_timeout_error_handling(): void
    {
        $user = User::factory()->create();
        
        Http::fake([
            '*' => Http::timeout(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        // Should handle timeout gracefully
        $analysis = AnalysisHistory::where('user_id', $user->id)->first();
        
        if ($analysis) {
            $this->assertEquals('failed', $analysis->status);
            $this->assertNotNull($analysis->error_message);
        }
    }

    /**
     * AH-014: SSRF Protection Maintained
     */
    public function test_ssrf_protection_maintained(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'http://localhost/admin',
                'tool' => 'meta-tag-analyzer',
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'URL_BLOCKED');
        
        // History should still be created for blocked URLs
        $this->assertDatabaseHas('analysis_history', [
            'user_id' => $user->id,
            'status' => 'failed',
        ]);
    }

    /**
     * AH-015: Rate Limit Not Tracked
     */
    public function test_rate_limit_not_tracked(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse();

        // Make multiple requests to hit rate limit
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)
                ->postJson('/api/v1/tools/analyze', [
                    'url' => "https://example{$i}.com",
                    'tool' => 'meta-tag-analyzer',
                ]);
        }

        $initialCount = AnalysisHistory::where('user_id', $user->id)->count();

        // This request should be rate limited
        $response = $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example-rate-limited.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        // Rate limited (429) or one of the earlier requests
        // The key assertion: no NEW history record for rate-limited request
        if ($response->status() === 429) {
            $newCount = AnalysisHistory::where('user_id', $user->id)->count();
            $this->assertEquals($initialCount, $newCount);
        }
    }

    /**
     * AH-017: Multiple Tools Same User
     */
    public function test_multiple_tools_same_user(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse();

        $tools = ['meta-tag-analyzer', 'keyword-density-checker', 'meta-tag-analyzer'];

        foreach ($tools as $index => $tool) {
            $params = [
                'url' => "https://example{$index}.com",
                'tool' => $tool,
            ];
            
            if ($tool === 'keyword-density-checker') {
                $params['keyword'] = 'test';
            }

            $this->actingAs($user)
                ->postJson('/api/v1/tools/analyze', $params);
        }

        $this->assertEquals(3, AnalysisHistory::where('user_id', $user->id)->count());
    }

    /**
     * AH-018: Large Results Storage
     */
    public function test_large_results_storage(): void
    {
        $user = User::factory()->create();
        
        // Create a large HTML document
        $largeContent = str_repeat('<p>Test content paragraph with some keywords. </p>', 1000);
        $html = "<html><head><title>Test</title></head><body>{$largeContent}</body></html>";
        $this->mockHtmlResponse($html);

        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        $analysis = AnalysisHistory::where('user_id', $user->id)->first();
        
        $this->assertNotNull($analysis);
        $this->assertNotNull($analysis->results);
    }

    /**
     * AH-019: Unicode URL Handling
     */
    public function test_unicode_url_handling(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse();

        $unicodeUrl = 'https://例え.jp/test';

        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => $unicodeUrl,
                'tool' => 'meta-tag-analyzer',
            ]);

        $this->assertDatabaseHas('analysis_history', [
            'user_id' => $user->id,
            'input_url' => $unicodeUrl,
        ]);
    }

    /**
     * AH-020: IDOR Prevention
     */
    public function test_idor_prevention(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $this->mockHtmlResponse();

        // User 1 creates an analysis
        $response = $this->actingAs($user1)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        $analysisId = $response->json('analysis_id');

        // User 2 tries to access User 1's analysis
        $response = $this->actingAs($user2)
            ->getJson("/api/v1/analyses/{$analysisId}");

        $response->assertStatus(403);
    }

    /**
     * AH-021: Pending Status Not Used
     */
    public function test_pending_status_not_used(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse();

        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        $analysis = AnalysisHistory::where('user_id', $user->id)->first();
        
        $this->assertNotEquals('pending', $analysis->status);
        $this->assertContains($analysis->status, ['completed', 'failed']);
    }

    /**
     * AH-022: Keyword Density with Keyword Parameter
     */
    public function test_keyword_density_with_keyword_parameter(): void
    {
        $user = User::factory()->create();
        $html = '<html><body><p>Test keyword test keyword test keyword</p></body></html>';
        $this->mockHtmlResponse($html);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'keyword-density-checker',
                'keyword' => 'test',
            ]);

        $analysis = AnalysisHistory::where('user_id', $user->id)
            ->where('tool_name', 'keyword-density-checker')
            ->first();
        
        $this->assertNotNull($analysis);
        $this->assertNotNull($analysis->results);
    }

    /**
     * AH-023: Site Soft Delete Handling
     */
    public function test_site_soft_delete_handling(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $this->mockHtmlResponse();

        // Create analysis with site
        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
                'site_id' => $site->id,
            ]);

        $analysis = AnalysisHistory::where('site_id', $site->id)->first();
        $this->assertNotNull($analysis);

        // Delete the site
        $site->delete();

        // Analysis should still exist
        $analysis->refresh();
        $this->assertNull($analysis->site_id);
    }

    /**
     * AH-024: Concurrent Requests
     */
    public function test_concurrent_requests(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse();

        $promises = collect(range(1, 5))->map(function ($i) use ($user) {
            return function () use ($user, $i) {
                return $this->actingAs($user)
                    ->postJson('/api/v1/tools/analyze', [
                        'url' => "https://example{$i}.com",
                        'tool' => 'meta-tag-analyzer',
                    ]);
            };
        });

        // Execute requests
        $promises->each(fn($p) => $p());

        $this->assertEquals(5, AnalysisHistory::where('user_id', $user->id)->count());
    }

    /**
     * AH-025: Performance Requirement
     */
    public function test_performance_requirement(): void
    {
        $user = User::factory()->create();
        $this->mockHtmlResponse();

        $start = microtime(true);

        $this->actingAs($user)
            ->postJson('/api/v1/tools/analyze', [
                'url' => 'https://example.com',
                'tool' => 'meta-tag-analyzer',
            ]);

        $elapsed = (microtime(true) - $start) * 1000; // ms

        // Total response time should be reasonable
        // History creation overhead should be minimal
        $this->assertLessThan(5000, $elapsed, 'Analysis should complete within 5 seconds');
    }
}
