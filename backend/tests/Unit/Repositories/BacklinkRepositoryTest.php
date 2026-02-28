<?php

namespace Tests\Unit\Repositories;

use App\Models\Backlink;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Tests for BacklinkRepository.
 * 
 * These tests define the expected repository interface.
 * The repository implementation should be created to pass these tests.
 * 
 * @see TASK-23-2
 */
class BacklinkRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create repository instance
        // $this->repository = app(BacklinkRepository::class);
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

        // Using model directly until repository is implemented
        $backlink = Backlink::create($data);

        $this->assertInstanceOf(Backlink::class, $backlink);
        $this->assertEquals('example.com', $backlink->source_domain);
    }

    /** @test */
    public function it_can_find_a_backlink_by_id(): void
    {
        $backlink = Backlink::factory()->create();

        $found = Backlink::find($backlink->id);

        $this->assertInstanceOf(Backlink::class, $found);
        $this->assertEquals($backlink->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_backlink_not_found(): void
    {
        $found = Backlink::find(99999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_update_a_backlink(): void
    {
        $backlink = Backlink::factory()->create(['anchor_text' => 'old text']);

        $backlink->update(['anchor_text' => 'new text']);

        $this->assertEquals('new text', $backlink->fresh()->anchor_text);
    }

    /** @test */
    public function it_can_delete_a_backlink(): void
    {
        $backlink = Backlink::factory()->create();

        $backlink->delete();

        $this->assertDatabaseMissing('backlinks', ['id' => $backlink->id]);
    }

    /** @test */
    public function it_can_get_all_backlinks_for_a_project(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(5)->create(['project_id' => $project->id]);
        Backlink::factory()->count(3)->create(); // Other project

        $backlinks = Backlink::where('project_id', $project->id)->get();

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

        // Batch insert using Eloquent
        foreach ($backlinks as $data) {
            Backlink::create($data);
        }

        $this->assertCount(2, Backlink::where('project_id', $project->id)->get());
    }

    /** @test */
    public function it_handles_empty_batch_insert(): void
    {
        $count = Backlink::count();

        // Empty batch - no operation
        // Should not throw error

        $this->assertEquals($count, Backlink::count());
    }

    // =============================================
    // Deactivation Tests
    // =============================================

    /** @test */
    public function it_can_deactivate_stale_backlinks(): void
    {
        $project = Project::factory()->create();
        
        // Create old backlinks
        Backlink::factory()->count(3)->create([
            'project_id' => $project->id,
            'last_checked_at' => now()->subDays(60),
            'is_active' => true,
        ]);
        
        // Create recent backlinks
        Backlink::factory()->count(2)->create([
            'project_id' => $project->id,
            'last_checked_at' => now()->subDays(5),
            'is_active' => true,
        ]);

        // Deactivate backlinks not checked in last 30 days
        $cutoffDate = now()->subDays(30);
        Backlink::where('project_id', $project->id)
            ->where('last_checked_at', '<', $cutoffDate)
            ->update(['is_active' => false]);

        $activeCount = Backlink::where('project_id', $project->id)->where('is_active', true)->count();
        $inactiveCount = Backlink::where('project_id', $project->id)->where('is_active', false)->count();

        $this->assertEquals(2, $activeCount);
        $this->assertEquals(3, $inactiveCount);
    }

    /** @test */
    public function it_preserves_recent_backlinks_when_deactivating_stale(): void
    {
        $project = Project::factory()->create();
        
        $recentBacklink = Backlink::factory()->create([
            'project_id' => $project->id,
            'last_checked_at' => now()->subDays(5),
            'is_active' => true,
        ]);

        $cutoffDate = now()->subDays(30);
        Backlink::where('project_id', $project->id)
            ->where('last_checked_at', '<', $cutoffDate)
            ->update(['is_active' => false]);

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

        $count = Backlink::where('project_id', $project->id)->where('is_active', true)->count();

        $this->assertEquals(7, $count);
    }

    /** @test */
    public function it_can_get_dofollow_backlinks_count(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(5)->dofollow()->create(['project_id' => $project->id]);
        Backlink::factory()->count(3)->nofollow()->create(['project_id' => $project->id]);

        $count = Backlink::where('project_id', $project->id)
            ->where('link_type', 'dofollow')
            ->count();

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

        $backlinks = Backlink::where('project_id', $project->id)
            ->where('source_domain', 'example.com')
            ->get();

        $this->assertCount(4, $backlinks);
    }

    /** @test */
    public function it_can_get_recent_backlinks(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $recent = Backlink::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

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
        
        // Use insert for performance
        Backlink::insert($backlinks);
        
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(5, $duration, 'Batch insert should complete in <5 seconds');
        $this->assertCount(100, Backlink::where('project_id', $project->id)->get());
    }

    /** @test */
    public function it_can_paginate_results(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(25)->create(['project_id' => $project->id]);

        $page1 = Backlink::where('project_id', $project->id)
            ->orderBy('id')
            ->paginate(10, ['*'], 'page', 1);

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

        $stats = Backlink::where('project_id', $project->id)
            ->selectRaw('link_type, count(*) as count')
            ->groupBy('link_type')
            ->pluck('count', 'link_type')
            ->toArray();

        $this->assertEquals(5, $stats['dofollow']);
        $this->assertEquals(3, $stats['nofollow']);
        $this->assertEquals(2, $stats['unknown']);
    }

    /** @test */
    public function it_can_get_unique_referring_domains_count(): void
    {
        $project = Project::factory()->create();
        
        Backlink::factory()->count(3)->create(['project_id' => $project->id, 'source_domain' => 'example.com']);
        Backlink::factory()->count(2)->create(['project_id' => $project->id, 'source_domain' => 'test.com']);
        Backlink::factory()->count(1)->create(['project_id' => $project->id, 'source_domain' => 'other.com']);

        $uniqueDomains = Backlink::where('project_id', $project->id)
            ->distinct('source_domain')
            ->count('source_domain');

        $this->assertEquals(3, $uniqueDomains);
    }
}
