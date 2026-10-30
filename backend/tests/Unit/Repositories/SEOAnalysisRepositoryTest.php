<?php

namespace Tests\Unit\Repositories;

use App\Models\SEOAnalysis;
use App\Models\User;
use App\Repositories\SEOAnalysisRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for SEOAnalysisRepository
 * 
 * @see TASK-18-2
 * @see TEST-18-2
 */
class SEOAnalysisRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected SEOAnalysisRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(SEOAnalysisRepository::class);
    }

    /**
     * Test repository create method.
     * 
     * @test
     */
    public function create_analysis(): void
    {
        $data = [
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com',
            'overall_score' => 78,
            'analysis_data' => ['score' => 78],
        ];

        $analysis = $this->repository->create($data);

        $this->assertInstanceOf(SEOAnalysis::class, $analysis);
        $this->assertEquals('example.com', $analysis->domain);
        $this->assertDatabaseHas('seo_analyses', ['id' => $analysis->id]);
    }

    /**
     * Test repository find method.
     * 
     * @test
     */
    public function find_analysis(): void
    {
        $analysis = SEOAnalysis::factory()->create();

        $found = $this->repository->find($analysis->id);

        $this->assertInstanceOf(SEOAnalysis::class, $found);
        $this->assertEquals($analysis->id, $found->id);
    }

    /**
     * Test repository find method - not found.
     * 
     * @test
     */
    public function find_analysis_not_found(): void
    {
        $found = $this->repository->find(99999);

        $this->assertNull($found);
    }

    /**
     * Test repository update method.
     * 
     * @test
     */
    public function update_analysis(): void
    {
        $analysis = SEOAnalysis::factory()->create(['status' => 'pending']);

        $updated = $this->repository->update($analysis->id, [
            'status' => 'completed',
            'overall_score' => 85,
        ]);

        $this->assertEquals('completed', $updated->status);
        $this->assertEquals(85, $updated->overall_score);
    }

    /**
     * Test repository delete method.
     * 
     * @test
     */
    public function delete_analysis(): void
    {
        $analysis = SEOAnalysis::factory()->create();

        $result = $this->repository->delete($analysis->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('seo_analyses', ['id' => $analysis->id]);
    }

    /**
     * Test repository delete method - not found.
     * 
     * @test
     */
    public function delete_analysis_not_found(): void
    {
        $result = $this->repository->delete(99999);

        $this->assertFalse($result);
    }

    /**
     * Test repository findByDomain method.
     * 
     * @test
     */
    public function find_by_domain(): void
    {
        SEOAnalysis::factory()->create(['domain' => 'example.com']);
        SEOAnalysis::factory()->create(['domain' => 'example.com']);
        SEOAnalysis::factory()->create(['domain' => 'other.com']);

        $results = $this->repository->findByDomain('example.com');

        $this->assertCount(2, $results);
        $results->each(fn ($a) => $this->assertEquals('example.com', $a->domain));
    }

    /**
     * Test repository findCached method - valid cache.
     * 
     * @test
     */
    public function find_cached_valid(): void
    {
        $analysis = SEOAnalysis::factory()->create([
            'domain' => 'example.com',
            'status' => 'completed',
            'expires_at' => now()->addDay(),
        ]);

        $cached = $this->repository->findCached('example.com');

        $this->assertNotNull($cached);
        $this->assertEquals($analysis->id, $cached->id);
    }

    /**
     * Test repository findCached method - expired.
     * 
     * @test
     */
    public function find_cached_expired(): void
    {
        SEOAnalysis::factory()->create([
            'domain' => 'example.com',
            'status' => 'completed',
            'expires_at' => now()->subHour(),
        ]);

        $cached = $this->repository->findCached('example.com');

        $this->assertNull($cached);
    }

    /**
     * Test repository findCached method - not found.
     * 
     * @test
     */
    public function find_cached_not_found(): void
    {
        $cached = $this->repository->findCached('notfound.com');

        $this->assertNull($cached);
    }

    /**
     * Test repository findCached method - pending status.
     * 
     * @test
     */
    public function find_cached_pending_status(): void
    {
        SEOAnalysis::factory()->create([
            'domain' => 'example.com',
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        $cached = $this->repository->findCached('example.com');

        // Should return null for non-completed analyses
        $this->assertNull($cached);
    }

    /**
     * Test repository findByUser method.
     * 
     * @test
     */
    public function find_by_user(): void
    {
        $user = User::factory()->create();
        SEOAnalysis::factory()->count(3)->create(['user_id' => $user->id]);
        SEOAnalysis::factory()->count(2)->create(['user_id' => null]);

        $results = $this->repository->findByUser($user->id);

        $this->assertCount(3, $results);
    }

    /**
     * Test repository findBySession method.
     * 
     * @test
     */
    public function find_by_session(): void
    {
        SEOAnalysis::factory()->count(2)->create(['session_id' => 'session123']);
        SEOAnalysis::factory()->create(['session_id' => 'other']);

        $results = $this->repository->findBySession('session123');

        $this->assertCount(2, $results);
    }

    /**
     * Test repository getLatest method.
     * 
     * @test
     */
    public function get_latest_for_domain(): void
    {
        $old = SEOAnalysis::factory()->create([
            'domain' => 'example.com',
            'created_at' => now()->subDay(),
        ]);
        $new = SEOAnalysis::factory()->create([
            'domain' => 'example.com',
            'created_at' => now(),
        ]);

        $latest = $this->repository->getLatest('example.com');

        $this->assertEquals($new->id, $latest->id);
    }

    /**
     * Test repository getLatest method - no results.
     * 
     * @test
     */
    public function get_latest_no_results(): void
    {
        $latest = $this->repository->getLatest('notfound.com');

        $this->assertNull($latest);
    }

    /**
     * Test repository getPending method.
     * 
     * @test
     */
    public function get_pending(): void
    {
        SEOAnalysis::factory()->count(3)->create(['status' => 'pending']);
        SEOAnalysis::factory()->create(['status' => 'completed']);

        $pending = $this->repository->getPending();

        $this->assertCount(3, $pending);
    }

    /**
     * Test repository getExpired method.
     * 
     * @test
     */
    public function get_expired(): void
    {
        SEOAnalysis::factory()->create(['expires_at' => now()->subHour()]);
        SEOAnalysis::factory()->create(['expires_at' => now()->subDay()]);
        SEOAnalysis::factory()->create(['expires_at' => now()->addDay()]);

        $expired = $this->repository->getExpired();

        $this->assertCount(2, $expired);
    }

    /**
     * Test repository caching on find.
     * 
     * @test
     */
    public function caching_on_find(): void
    {
        $analysis = SEOAnalysis::factory()->create();
        Cache::flush();

        // First call - should cache
        $found1 = $this->repository->find($analysis->id);
        $this->assertTrue(Cache::has("seo_analysis.{$analysis->id}"));

        // Second call - should come from cache
        $found2 = $this->repository->find($analysis->id);
        $this->assertEquals($found1->id, $found2->id);
    }

    /**
     * Test repository cache invalidation on update.
     * 
     * @test
     */
    public function cache_invalidation_on_update(): void
    {
        $analysis = SEOAnalysis::factory()->create();
        
        // Prime the cache
        $this->repository->find($analysis->id);
        $this->assertTrue(Cache::has("seo_analysis.{$analysis->id}"));

        // Update should clear cache
        $this->repository->update($analysis->id, ['status' => 'completed']);
        $this->assertFalse(Cache::has("seo_analysis.{$analysis->id}"));
    }

    /**
     * Test repository cache invalidation on delete.
     * 
     * @test
     */
    public function cache_invalidation_on_delete(): void
    {
        $analysis = SEOAnalysis::factory()->create();
        
        // Prime the cache
        $this->repository->find($analysis->id);
        $this->assertTrue(Cache::has("seo_analysis.{$analysis->id}"));

        // Delete should clear cache
        $this->repository->delete($analysis->id);
        $this->assertFalse(Cache::has("seo_analysis.{$analysis->id}"));
    }

    /**
     * Test repository findByDomain for user.
     * 
     * @test
     */
    public function find_by_domain_for_user(): void
    {
        $user = User::factory()->create();
        SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
        ]);
        SEOAnalysis::factory()->create([
            'user_id' => null,
            'domain' => 'example.com',
        ]);

        $results = $this->repository->findByDomainForUser('example.com', $user->id);

        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->user_id);
    }

    /**
     * Test repository findCached with session.
     * 
     * @test
     */
    public function find_cached_with_session(): void
    {
        SEOAnalysis::factory()->create([
            'session_id' => 'session123',
            'domain' => 'example.com',
            'status' => 'completed',
            'expires_at' => now()->addDay(),
        ]);

        $cached = $this->repository->findCachedBySession('example.com', 'session123');

        $this->assertNotNull($cached);
    }

    /**
     * Test repository pagination.
     * 
     * @test
     */
    public function pagination(): void
    {
        SEOAnalysis::factory()->count(25)->create();

        $results = $this->repository->paginate(10);

        $this->assertCount(10, $results->items());
        $this->assertEquals(25, $results->total());
        $this->assertEquals(3, $results->lastPage());
    }

    /**
     * Test repository getStats method.
     * 
     * @test
     */
    public function get_stats(): void
    {
        $user = User::factory()->create();
        SEOAnalysis::factory()->count(5)->create(['user_id' => $user->id, 'status' => 'completed']);
        SEOAnalysis::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'failed']);
        SEOAnalysis::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'pending']);

        $stats = $this->repository->getStats($user->id);

        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(5, $stats['completed']);
        $this->assertEquals(3, $stats['failed']);
        $this->assertEquals(2, $stats['pending']);
    }

    /**
     * Test repository cleanupExpired method.
     * 
     * @test
     */
    public function cleanup_expired(): void
    {
        SEOAnalysis::factory()->create(['expires_at' => now()->subDay()]);
        SEOAnalysis::factory()->create(['expires_at' => now()->subHour()]);
        SEOAnalysis::factory()->create(['expires_at' => now()->addDay()]);

        $deleted = $this->repository->cleanupExpired();

        $this->assertEquals(2, $deleted);
        $this->assertEquals(1, SEOAnalysis::count());
    }
}
