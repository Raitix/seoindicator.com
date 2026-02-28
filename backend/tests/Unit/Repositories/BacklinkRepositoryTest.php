<?php

namespace Tests\Unit\Repositories;

use App\Models\Backlink;
use App\Models\Project;
use App\Repositories\BacklinkRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Tests for BacklinkRepository.
 * 
 * @see TASK-23-2
 */
class BacklinkRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected BacklinkRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(BacklinkRepository::class);
    }

    // =============================================
    // CRUD Tests
    // =============================================

    /** @test */
    public function it_can_create_a_backlink(): void
    {
        $project = Project::factory()->create();
        
        $data = [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com/target',
            'anchor_text' => 'keyword',
            'link_type' => 'dofollow',
            'is_active' => true,
        ];

        $backlink = $this->repository->create($data);

        $this->assertInstanceOf(Backlink::class, $backlink);
        $this->assertEquals('example.com', $backlink->source_domain);
    }

    /** @test */
    public function it_can_find_a_backlink_by_id(): void
    {
        $backlink = Backlink::factory()->create();

        $found = $this->repository->findById($backlink->id);

        $this->assertInstanceOf(Backlink::class, $found);
        $this->assertEquals($backlink->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_backlink_not_found(): void
    {
        $found = $this->repository->findById(99999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_update_a_backlink(): void
    {
        $backlink = Backlink::factory()->create(['anchor_text' => 'old text']);

        $result = $this->repository->update($backlink->id, ['anchor_text' => 'new text']);

        $this->assertTrue($result);
        $this->assertEquals('new text', $backlink->fresh()->anchor_text);
    }

    /** @test */
    public function it_can_delete_a_backlink(): void
    {
        $backlink = Backlink::factory()->create();

        $result = $this->repository->delete($backlink->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('backlinks', ['id' => $backlink->id]);
    }

    /** @test */
    public function it_can_get_all_backlinks_for_a_project(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(5)->create(['project_id' => $project->id]);
        Backlink::factory()->count(3)->create(); // Other project

        $backlinks = $this->repository->getByProject($project->id);

        $this->assertCount(5, $backlinks);
        $backlinks->each(fn ($b) => $this->assertEquals($project->id, $b->project_id));
    }

    // =============================================
    // Batch Operations Tests
    // =============================================

    /** @test */
    public function it_can_insert_multiple_backlinks_at_once(): void
    {
        $project = Project::factory()->create();
        
        $backlinks = [
            [
                'project_id' => $project->id,
                'source_url' => 'https://example1.com/page',
                'source_domain' => 'example1.com',
                'target_url' => 'https://myproject.com/target',
                'link_type' => 'dofollow',
            ],
            [
                'project_id' => $project->id,
                'source_url' => 'https://example2.com/page',
                'source_domain' => 'example2.com',
                'target_url' => 'https://myproject.com/target',
                'link_type' => 'nofollow',
            ],
        ];

        $result = $this->repository->insertBatch($backlinks);

        $this->assertTrue($result);
        $this->assertCount(2, Backlink::where('project_id', $project->id)->get());
    }

    /** @test */
    public function it_handles_empty_batch_insert(): void
    {
        $result = $this->repository->insertBatch([]);

        $this->assertTrue($result);
    }

    // =============================================
    // Deactivation Tests
    // =============================================

    /** @test */
    public function it_can_deactivate_stale_backlinks(): void
    {
        $project = Project::factory()->create();
        
        // Create backlinks with active URLs
        Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://active1.com/page',
            'is_active' => true,
        ]);
        Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://active2.com/page',
            'is_active' => true,
        ]);
        
        // Create stale backlinks
        Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://stale.com/page',
            'is_active' => true,
        ]);

        $activeUrls = ['https://active1.com/page', 'https://active2.com/page'];
        
        $count = $this->repository->deactivateStale($project->id, $activeUrls);

        $this->assertEquals(1, $count);
        $this->assertEquals(2, Backlink::where('project_id', $project->id)->where('is_active', true)->count());
    }

    /** @test */
    public function it_preserves_recent_backlinks_when_deactivating_stale(): void
    {
        $project = Project::factory()->create();
        
        $recentBacklink = Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://recent.com/page',
            'is_active' => true,
        ]);
        
        $activeUrls = ['https://recent.com/page'];
        
        $this->repository->deactivateStale($project->id, $activeUrls);

        $this->assertTrue($recentBacklink->fresh()->is_active);
    }

    // =============================================
    // Query Tests
    // =============================================

    /** @test */
    public function it_can_get_active_backlinks_count(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(7)->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(3)->create(['project_id' => $project->id, 'is_active' => false]);

        $count = $this->repository->getActiveCount($project->id);

        $this->assertEquals(7, $count);
    }

    /** @test */
    public function it_can_get_dofollow_backlinks_count(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(5)->dofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(3)->nofollow()->create(['project_id' => $project->id, 'is_active' => true]);

        $count = $this->repository->getDofollowCount($project->id);

        $this->assertEquals(5, $count);
    }

    /** @test */
    public function it_can_get_backlinks_by_domain(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(4)->create([
            'project_id' => $project->id,
            'source_domain' => 'example.com',
        ]);
        Backlink::factory()->count(2)->create([
            'project_id' => $project->id,
            'source_domain' => 'other.com',
        ]);

        $backlinks = $this->repository->getByDomain($project->id, 'example.com');

        $this->assertCount(4, $backlinks);
    }

    /** @test */
    public function it_can_get_recent_backlinks(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $recent = $this->repository->getRecent($project->id, 5);

        $this->assertCount(5, $recent);
    }

    // =============================================
    // Performance Tests
    // =============================================

    /** @test */
    public function it_can_handle_large_batch_insert(): void
    {
        $project = Project::factory()->create();
        
        $startTime = microtime(true);
        
        // Create 100 backlinks
        $backlinks = [];
        for ($i = 0; $i < 100; $i++) {
            $backlinks[] = [
                'project_id' => $project->id,
                'source_url' => "https://domain{$i}.com/page",
                'source_domain' => "domain{$i}.com",
                'target_url' => 'https://myproject.com/target',
                'link_type' => 'dofollow',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        $result = $this->repository->insertBatch($backlinks);
        
        $duration = microtime(true) - $startTime;

        $this->assertTrue($result);
        $this->assertLessThan(5, $duration, 'Batch insert should complete in <5 seconds');
        $this->assertCount(100, Backlink::where('project_id', $project->id)->get());
    }

    /** @test */
    public function it_can_paginate_results(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(25)->create(['project_id' => $project->id]);

        $page1 = $this->repository->getPaginated($project->id, 10, 1);

        $this->assertCount(10, $page1->items());
        $this->assertEquals(25, $page1->total());
        $this->assertEquals(3, $page1->lastPage());
    }

    // =============================================
    // Statistics Tests
    // =============================================

    /** @test */
    public function it_can_count_backlinks_by_link_type(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(5)->dofollow()->create(['project_id' => $project->id]);
        Backlink::factory()->count(3)->nofollow()->create(['project_id' => $project->id]);
        Backlink::factory()->count(2)->create(['project_id' => $project->id, 'link_type' => 'unknown']);

        $stats = $this->repository->countByLinkType($project->id);

        $this->assertEquals(5, $stats['dofollow']);
        $this->assertEquals(3, $stats['nofollow']);
        $this->assertEquals(2, $stats['unknown']);
    }

    /** @test */
    public function it_can_get_unique_referring_domains_count(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(3)->create(['project_id' => $project->id, 'source_domain' => 'example.com', 'is_active' => true]);
        Backlink::factory()->count(2)->create(['project_id' => $project->id, 'source_domain' => 'test.com', 'is_active' => true]);
        Backlink::factory()->count(1)->create(['project_id' => $project->id, 'source_domain' => 'other.com', 'is_active' => true]);

        $uniqueDomains = $this->repository->getUniqueReferringDomainsCount($project->id);

        $this->assertEquals(3, $uniqueDomains);
    }
}
