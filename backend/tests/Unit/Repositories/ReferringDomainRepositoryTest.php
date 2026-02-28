<?php

namespace Tests\Unit\Repositories;

use App\Models\Project;
use App\Models\ReferringDomain;
use App\Repositories\ReferringDomainRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Tests for ReferringDomainRepository.
 * 
 * @see TASK-23-2
 */
class ReferringDomainRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ReferringDomainRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(ReferringDomainRepository::class);
    }

    // =============================================
    // CRUD Tests
    // =============================================

    /** @test */
    public function it_can_create_a_referring_domain(): void
    {
        $project = Project::factory()->create();
        
        $domain = $this->repository->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'backlinks_count' => 10,
            'domain_trust_score' => 75,
        ]);

        $this->assertInstanceOf(ReferringDomain::class, $domain);
        $this->assertEquals('example.com', $domain->domain);
    }

    /** @test */
    public function it_can_find_a_referring_domain_by_id(): void
    {
        $domain = ReferringDomain::factory()->create();

        $found = $this->repository->findById($domain->id);

        $this->assertInstanceOf(ReferringDomain::class, $found);
        $this->assertEquals($domain->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_referring_domain_not_found(): void
    {
        $found = $this->repository->findById(99999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_update_a_referring_domain(): void
    {
        $domain = ReferringDomain::factory()->create(['backlinks_count' => 10]);

        $result = $this->repository->update($domain->id, ['backlinks_count' => 20]);

        $this->assertTrue($result);
        $this->assertEquals(20, $domain->fresh()->backlinks_count);
    }

    /** @test */
    public function it_can_delete_a_referring_domain(): void
    {
        $domain = ReferringDomain::factory()->create();

        $result = $this->repository->delete($domain->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('referring_domains', ['id' => $domain->id]);
    }

    /** @test */
    public function it_can_get_all_referring_domains_for_a_project(): void
    {
        $project = Project::factory()->create();
        
        ReferringDomain::factory()->count(5)->create(['project_id' => $project->id]);
        ReferringDomain::factory()->count(3)->create(); // Other project

        $domains = $this->repository->getByProject($project->id);

        $this->assertCount(5, $domains);
        $domains->each(fn ($d) => $this->assertEquals($project->id, $d->project_id));
    }

    // =============================================
    // Upsert Tests
    // =============================================

    /** @test */
    public function it_can_upsert_a_new_domain(): void
    {
        $project = Project::factory()->create();

        $domain = $this->repository->upsert([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'backlinks_count' => 10,
            'domain_trust_score' => 75,
        ]);

        $this->assertInstanceOf(ReferringDomain::class, $domain);
        $this->assertEquals('example.com', $domain->domain);
        $this->assertEquals(10, $domain->backlinks_count);
    }

    /** @test */
    public function it_can_upsert_an_existing_domain(): void
    {
        $project = Project::factory()->create();
        
        // Create initial domain
        $original = ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'backlinks_count' => 10,
        ]);

        // Upsert same domain - should update
        $updated = $this->repository->upsert([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'backlinks_count' => 20,
            'domain_trust_score' => 80,
        ]);

        $this->assertEquals($original->id, $updated->id);
        $this->assertEquals(20, $updated->backlinks_count);
        $this->assertEquals(80, $updated->domain_trust_score);
    }

    /** @test */
    public function it_can_find_domain_by_project_and_name(): void
    {
        $project = Project::factory()->create();
        
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
        ]);

        $found = $this->repository->findByProjectAndDomain($project->id, 'example.com');

        $this->assertNotNull($found);
        $this->assertEquals('example.com', $found->domain);
    }

    // =============================================
    // Query Tests
    // =============================================

    /** @test */
    public function it_can_get_domains_ordered_by_backlinks_count(): void
    {
        $project = Project::factory()->create();
        
        ReferringDomain::factory()->create(['project_id' => $project->id, 'backlinks_count' => 10]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'backlinks_count' => 50]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'backlinks_count' => 30]);

        $domains = $this->repository->getOrderedByBacklinksCount($project->id);

        $this->assertEquals(50, $domains[0]->backlinks_count);
        $this->assertEquals(30, $domains[1]->backlinks_count);
        $this->assertEquals(10, $domains[2]->backlinks_count);
    }

    /** @test */
    public function it_can_get_domains_ordered_by_trust_score(): void
    {
        $project = Project::factory()->create();
        
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 50]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 90]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 70]);

        $domains = $this->repository->getOrderedByTrustScore($project->id);

        $this->assertEquals(90, $domains[0]->domain_trust_score);
        $this->assertEquals(70, $domains[1]->domain_trust_score);
        $this->assertEquals(50, $domains[2]->domain_trust_score);
    }

    /** @test */
    public function it_can_get_top_domains_by_backlinks(): void
    {
        $project = Project::factory()->create();
        
        ReferringDomain::factory()->create(['project_id' => $project->id, 'backlinks_count' => 10]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'backlinks_count' => 50]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'backlinks_count' => 30]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'backlinks_count' => 5]);

        $topDomains = $this->repository->getTopByBacklinks($project->id, 3);

        $this->assertCount(3, $topDomains);
        $this->assertEquals([50, 30, 10], $topDomains->pluck('backlinks_count')->toArray());
    }

    /** @test */
    public function it_can_get_total_count_for_project(): void
    {
        $project = Project::factory()->create();
        
        ReferringDomain::factory()->count(7)->create(['project_id' => $project->id]);
        ReferringDomain::factory()->count(3)->create(); // Other project

        $count = $this->repository->getTotalCount($project->id);

        $this->assertEquals(7, $count);
    }

    // =============================================
    // Aggregation Tests
    // =============================================

    /** @test */
    public function it_can_calculate_total_backlinks_from_domains(): void
    {
        $project = Project::factory()->create();
        
        ReferringDomain::factory()->create(['project_id' => $project->id, 'backlinks_count' => 10]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'backlinks_count' => 20]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'backlinks_count' => 15]);

        $totalBacklinks = $this->repository->getTotalBacklinks($project->id);

        $this->assertEquals(45, $totalBacklinks);
    }

    /** @test */
    public function it_can_calculate_average_trust_score(): void
    {
        $project = Project::factory()->create();
        
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 50]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 70]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 60]);

        $avgTrust = $this->repository->getAverageTrustScore($project->id);

        $this->assertEquals(60, $avgTrust);
    }

    // =============================================
    // High Trust Domain Tests
    // =============================================

    /** @test */
    public function it_can_filter_domains_by_trust_threshold(): void
    {
        $project = Project::factory()->create();
        
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 80]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 50]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 90]);

        $highTrustDomains = $this->repository->getByTrustThreshold($project->id, 70);

        $this->assertCount(2, $highTrustDomains);
        $highTrustDomains->each(fn ($d) => $this->assertGreaterThanOrEqual(70, $d->domain_trust_score));
    }
}
