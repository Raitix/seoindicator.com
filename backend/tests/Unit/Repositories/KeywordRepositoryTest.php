<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\KeywordRepository;
use App\Models\Keyword;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

class KeywordRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected KeywordRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new KeywordRepository();
    }

    /**
     * Test findById returns keyword.
     */
    public function test_find_by_id_returns_keyword(): void
    {
        $keyword = Keyword::factory()->create();
        
        $result = $this->repository->findById($keyword->id);
        
        $this->assertInstanceOf(Keyword::class, $result);
        $this->assertEquals($keyword->id, $result->id);
    }

    /**
     * Test findById returns null for invalid ID.
     */
    public function test_find_by_id_returns_null_for_invalid(): void
    {
        $result = $this->repository->findById(9999);
        
        $this->assertNull($result);
    }

    /**
     * Test findBySite returns keywords for site.
     */
    public function test_find_by_site_returns_keywords(): void
    {
        $site = Site::factory()->create();
        Keyword::factory()->count(5)->create(['site_id' => $site->id]);
        
        $result = $this->repository->findBySite($site->id);
        
        $this->assertCount(5, $result);
    }

    /**
     * Test findBySite with pagination.
     */
    public function test_find_by_site_with_pagination(): void
    {
        $site = Site::factory()->create();
        Keyword::factory()->count(25)->create(['site_id' => $site->id]);
        
        $result = $this->repository->findBySite($site->id, perPage: 10, page: 1);
        
        $this->assertCount(10, $result->items());
        $this->assertEquals(25, $result->total());
        $this->assertEquals(3, $result->lastPage());
    }

    /**
     * Test findBySite with status filter.
     */
    public function test_find_by_site_with_status_filter(): void
    {
        $site = Site::factory()->create();
        Keyword::factory()->count(3)->create(['site_id' => $site->id, 'status' => 'active']);
        Keyword::factory()->count(2)->create(['site_id' => $site->id, 'status' => 'paused']);
        
        $result = $this->repository->findBySite($site->id, status: 'active');
        
        $this->assertCount(3, $result);
    }

    /**
     * Test create keyword.
     */
    public function test_create_keyword(): void
    {
        $site = Site::factory()->create();
        
        $data = [
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'target_url' => 'https://example.com',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
        ];
        
        $result = $this->repository->create($data);
        
        $this->assertInstanceOf(Keyword::class, $result);
        $this->assertDatabaseHas('keywords', ['keyword' => 'test keyword']);
    }

    /**
     * Test create keyword with duplicate throws exception.
     */
    public function test_create_duplicate_throws_exception(): void
    {
        $site = Site::factory()->create();
        
        Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'duplicate',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
        ]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        $this->repository->create([
            'site_id' => $site->id,
            'keyword' => 'duplicate',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
        ]);
    }

    /**
     * Test update keyword.
     */
    public function test_update_keyword(): void
    {
        $keyword = Keyword::factory()->create([
            'target_url' => 'https://old-url.com',
        ]);
        
        $result = $this->repository->update($keyword->id, [
            'target_url' => 'https://new-url.com',
        ]);
        
        $this->assertTrue($result);
        $this->assertEquals('https://new-url.com', $keyword->fresh()->target_url);
    }

    /**
     * Test update status.
     */
    public function test_update_status(): void
    {
        $keyword = Keyword::factory()->create(['status' => 'active']);
        
        $result = $this->repository->update($keyword->id, ['status' => 'paused']);
        
        $this->assertTrue($result);
        $this->assertEquals('paused', $keyword->fresh()->status);
    }

    /**
     * Test soft delete keyword.
     */
    public function test_soft_delete_keyword(): void
    {
        $keyword = Keyword::factory()->create(['status' => 'active']);
        
        $result = $this->repository->delete($keyword->id);
        
        $this->assertTrue($result);
        $this->assertEquals('deleted', $keyword->fresh()->status);
    }

    /**
     * Test hard delete keyword.
     */
    public function test_hard_delete_keyword(): void
    {
        $keyword = Keyword::factory()->create();
        
        $result = $this->repository->delete($keyword->id, hardDelete: true);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('keywords', ['id' => $keyword->id]);
    }

    /**
     * Test get active keywords count.
     */
    public function test_get_active_count(): void
    {
        $site = Site::factory()->create();
        Keyword::factory()->count(5)->create(['site_id' => $site->id, 'status' => 'active']);
        Keyword::factory()->count(2)->create(['site_id' => $site->id, 'status' => 'paused']);
        
        $count = $this->repository->getActiveCount($site->id);
        
        $this->assertEquals(5, $count);
    }

    /**
     * Test get keywords needing check.
     */
    public function test_get_keywords_needing_check(): void
    {
        $site = Site::factory()->create();
        
        // Never checked
        Keyword::factory()->create([
            'site_id' => $site->id,
            'last_checked_at' => null,
        ]);
        
        // Checked yesterday
        Keyword::factory()->create([
            'site_id' => $site->id,
            'last_checked_at' => now()->subDay(),
        ]);
        
        // Checked today
        Keyword::factory()->create([
            'site_id' => $site->id,
            'last_checked_at' => now(),
        ]);
        
        $needsCheck = $this->repository->getKeywordsNeedingCheck($site->id);
        
        $this->assertCount(2, $needsCheck);
    }
}
