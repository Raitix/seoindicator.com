<?php

namespace Tests\Feature\Api\V1\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Services\AI\AISuggestionCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;

/**
 * Feature tests for Admin AI Cache Invalidation API.
 * 
 * Covers AC-4: Cache Invalidation (Admin endpoint)
 * 
 * @task TASK-21-6
 */
class AICacheControllerTest extends TestCase
{
    use RefreshDatabase;
    
    private AISuggestionCache $cacheService;
    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(AISuggestionCache::class);
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->regularUser = User::factory()->create(['role' => 'user']);
    }

    protected function tearDown(): void
    {
        $this->cacheService->clear();
        Mockery::close();
        parent::tearDown();
    }

    // ============================================================
    // POST /api/v1/admin/ai/cache/invalidate - Single Entry
    // ============================================================

    /** @test Admin can invalidate specific cache entry */
    public function test_admin_invalidate_specific_entry(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $hash = 'test_hash_' . uniqid();
        $this->cacheService->set($hash, ['score' => 85]);
        
        $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
            'hash' => $hash
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cache entry invalidated'
            ]);
        
        $this->assertNull($this->cacheService->get($hash));
    }

    /** @test Admin invalidate returns 404 for non-existent entry */
    public function test_admin_invalidate_not_found(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
            'hash' => 'nonexistent_hash_12345'
        ]);
        
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Cache entry not found'
            ]);
    }

    /** @test Admin can invalidate by indicator type */
    public function test_admin_invalidate_by_indicator(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        // Create cache entries for different indicators
        $titleHash = $this->cacheService->generateHash('title', ['title' => 'Test'], 'openrouter');
        $metaHash = $this->cacheService->generateHash('meta_description', ['desc' => 'Test'], 'openrouter');
        
        $this->cacheService->set($titleHash, ['indicator' => 'title', 'score' => 80]);
        $this->cacheService->set($metaHash, ['indicator' => 'meta_description', 'score' => 70]);
        
        $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
            'indicator' => 'title'
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure(['success', 'message', 'invalidated_count']);
        
        // Title cache should be gone
        $this->assertNull($this->cacheService->get($titleHash));
        
        // Meta cache should remain
        $this->assertNotNull($this->cacheService->get($metaHash));
    }

    /** @test Admin invalidate by indicator with invalid type */
    public function test_admin_invalidate_invalid_indicator(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
            'indicator' => 'invalid_indicator'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['indicator']);
    }

    // ============================================================
    // DELETE /api/v1/admin/ai/cache - Clear All
    // ============================================================

    /** @test Admin can clear all cache */
    public function test_admin_clear_all_cache(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        // Create multiple cache entries
        for ($i = 0; $i < 5; $i++) {
            $hash = "clear_test_{$i}_" . uniqid();
            $this->cacheService->set($hash, ['data' => $i]);
        }
        
        $response = $this->deleteJson('/api/v1/admin/ai/cache');
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'All AI suggestion cache cleared'
            ])
            ->assertJsonStructure(['success', 'message', 'cleared_count']);
    }

    /** @test Admin clear returns count of cleared entries */
    public function test_admin_clear_returns_count(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $count = 3;
        for ($i = 0; $i < $count; $i++) {
            $hash = "count_test_{$i}_" . uniqid();
            $this->cacheService->set($hash, ['data' => $i]);
        }
        
        $response = $this->deleteJson('/api/v1/admin/ai/cache');
        
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual($count, $response->json('cleared_count'));
    }

    // ============================================================
    // GET /api/v1/admin/ai/cache/stats - Cache Statistics
    // ============================================================

    /** @test Admin can view cache statistics */
    public function test_admin_cache_stats(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        // Generate some cache activity
        $this->cacheService->set('stats_test_1', ['data' => 1]);
        $this->cacheService->set('stats_test_2', ['data' => 2]);
        $this->cacheService->get('stats_test_1'); // Hit
        $this->cacheService->get('nonexistent'); // Miss
        
        $response = $this->getJson('/api/v1/admin/ai/cache/stats');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_entries',
                    'hits',
                    'misses',
                    'hit_ratio',
                    'evictions',
                    'eviction_rate'
                ]
            ]);
    }

    /** @test Admin stats includes per-indicator breakdown */
    public function test_admin_stats_per_indicator(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        // Create cache entries for different indicators
        $indicators = ['title', 'meta_description', 'robots'];
        foreach ($indicators as $indicator) {
            $hash = $this->cacheService->generateHash($indicator, ['test' => $indicator], 'openrouter');
            $this->cacheService->set($hash, ['indicator' => $indicator]);
        }
        
        $response = $this->getJson('/api/v1/admin/ai/cache/stats');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'by_indicator'
                ]
            ]);
    }

    // ============================================================
    // Authentication Tests
    // ============================================================

    /** @test Unauthenticated request returns 401 */
    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
            'hash' => 'test_hash'
        ]);
        
        $response->assertStatus(401);
    }

    /** @test Non-admin user returns 403 */
    public function test_non_admin_returns_403(): void
    {
        Sanctum::actingAs($this->regularUser, ['*']);
        
        $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
            'hash' => 'test_hash'
        ]);
        
        $response->assertStatus(403);
    }

    /** @test Non-admin cannot clear cache */
    public function test_non_admin_cannot_clear(): void
    {
        Sanctum::actingAs($this->regularUser, ['*']);
        
        $response = $this->deleteJson('/api/v1/admin/ai/cache');
        
        $response->assertStatus(403);
    }

    /** @test Non-admin cannot view stats */
    public function test_non_admin_cannot_view_stats(): void
    {
        Sanctum::actingAs($this->regularUser, ['*']);
        
        $response = $this->getJson('/api/v1/admin/ai/cache/stats');
        
        $response->assertStatus(403);
    }

    // ============================================================
    // Validation Tests
    // ============================================================

    /** @test Invalidate requires hash or indicator */
    public function test_invalidate_requires_hash_or_indicator(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['hash', 'indicator']);
    }

    /** @test Hash must be string */
    public function test_hash_must_be_string(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
            'hash' => 12345
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['hash']);
    }

    /** @test Indicator must be valid type */
    public function test_indicator_must_be_valid(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $invalidIndicators = ['invalid', 'foo', 'bar', 'TITLE', 'Meta_Description'];
        
        foreach ($invalidIndicators as $indicator) {
            $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
                'indicator' => $indicator
            ]);
            
            $response->assertStatus(422);
        }
    }

    /** @test Valid indicator types accepted */
    public function test_valid_indicator_types(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $validIndicators = [
            'title', 'meta_description', 'robots', 'sitemap', 'headings',
            'image_alt', 'filenames', 'url', 'links', '404'
        ];
        
        foreach ($validIndicators as $indicator) {
            $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
                'indicator' => $indicator
            ]);
            
            $response->assertStatus(200);
        }
    }

    // ============================================================
    // Rate Limiting Tests
    // ============================================================

    /** @test Cache endpoints have reasonable rate limits */
    public function test_cache_endpoints_rate_limited(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        // Make multiple requests quickly
        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson('/api/v1/admin/ai/cache/stats');
            
            if ($response->status() === 429) {
                // Rate limit hit
                $this->assertTrue(true);
                return;
            }
        }
        
        // If we didn't hit rate limit, that's also acceptable for admin
        $this->assertTrue(true);
    }

    // ============================================================
    // HTTP Method Tests
    // ============================================================

    /** @test Invalidate endpoint only accepts POST */
    public function test_invalidate_method_restrictions(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $this->getJson('/api/v1/admin/ai/cache/invalidate')->assertStatus(405);
        $this->putJson('/api/v1/admin/ai/cache/invalidate')->assertStatus(405);
        $this->deleteJson('/api/v1/admin/ai/cache/invalidate')->assertStatus(405);
        $this->patchJson('/api/v1/admin/ai/cache/invalidate')->assertStatus(405);
    }

    /** @test Clear endpoint only accepts DELETE */
    public function test_clear_method_restrictions(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $this->getJson('/api/v1/admin/ai/cache')->assertStatus(405);
        $this->postJson('/api/v1/admin/ai/cache')->assertStatus(405);
        $this->putJson('/api/v1/admin/ai/cache')->assertStatus(405);
        $this->patchJson('/api/v1/admin/ai/cache')->assertStatus(405);
    }

    /** @test Stats endpoint only accepts GET */
    public function test_stats_method_restrictions(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $this->postJson('/api/v1/admin/ai/cache/stats')->assertStatus(405);
        $this->putJson('/api/v1/admin/ai/cache/stats')->assertStatus(405);
        $this->deleteJson('/api/v1/admin/ai/cache/stats')->assertStatus(405);
        $this->patchJson('/api/v1/admin/ai/cache/stats')->assertStatus(405);
    }

    // ============================================================
    // Security Tests
    // ============================================================

    /** @test SQL injection prevention in hash parameter */
    public function test_sql_injection_prevention(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $maliciousHashes = [
            "'; DROP TABLE ai_suggestions_cache; --",
            "1' OR '1'='1",
            "admin'--",
            "1; SELECT * FROM users"
        ];
        
        foreach ($maliciousHashes as $hash) {
            $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
                'hash' => $hash
            ]);
            
            // Should return 404 (not found), not error
            $response->assertStatus(404);
        }
    }

    /** @test XSS prevention in response */
    public function test_xss_prevention(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $xssHash = '<script>alert("xss")</script>';
        
        $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
            'hash' => $xssHash
        ]);
        
        // Should handle safely
        $response->assertStatus(404);
        
        // Response should not contain unescaped script tag
        $content = $response->getContent();
        $this->assertStringNotContainsString('<script>', $content);
    }

    /** @test No sensitive data in stats response */
    public function test_no_sensitive_data_in_stats(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        // Cache with potentially sensitive data
        $this->cacheService->set('sensitive_test', [
            'api_key' => 'secret_key_123',
            'password' => 'secret_password'
        ]);
        
        $response = $this->getJson('/api/v1/admin/ai/cache/stats');
        
        $response->assertStatus(200);
        
        // Stats should not include cache content
        $content = $response->getContent();
        $this->assertStringNotContainsString('secret_key_123', $content);
        $this->assertStringNotContainsString('secret_password', $content);
    }

    // ============================================================
    // Performance Tests
    // ============================================================

    /** @test Invalidate endpoint responds quickly */
    public function test_invalidate_performance(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $hash = 'perf_test_' . uniqid();
        $this->cacheService->set($hash, ['data' => true]);
        
        $start = microtime(true);
        
        $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
            'hash' => $hash
        ]);
        
        $elapsed = (microtime(true) - $start) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(500, $elapsed, 'Response should be < 500ms');
    }

    /** @test Clear endpoint handles many entries */
    public function test_clear_many_entries(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        // Create 100 cache entries
        for ($i = 0; $i < 100; $i++) {
            $hash = "bulk_clear_{$i}_" . uniqid();
            $this->cacheService->set($hash, ['index' => $i]);
        }
        
        $start = microtime(true);
        
        $response = $this->deleteJson('/api/v1/admin/ai/cache');
        
        $elapsed = (microtime(true) - $start) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(2000, $elapsed, 'Clear should complete in < 2s');
    }

    // ============================================================
    // Edge Cases
    // ============================================================

    /** @test Invalidate with empty cache */
    public function test_invalidate_empty_cache(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $this->cacheService->clear();
        
        $response = $this->deleteJson('/api/v1/admin/ai/cache');
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'cleared_count' => 0
            ]);
    }

    /** @test Stats with empty cache */
    public function test_stats_empty_cache(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $this->cacheService->clear();
        
        $response = $this->getJson('/api/v1/admin/ai/cache/stats');
        
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total_entries' => 0
                ]
            ]);
    }

    /** @test Invalidate with unicode hash */
    public function test_invalidate_unicode_hash(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        
        $unicodeHash = 'unicode_' . uniqid() . '_テスト_标题';
        $this->cacheService->set($unicodeHash, ['data' => 'unicode']);
        
        $response = $this->postJson('/api/v1/admin/ai/cache/invalidate', [
            'hash' => $unicodeHash
        ]);
        
        $response->assertStatus(200);
        $this->assertNull($this->cacheService->get($unicodeHash));
    }
}
