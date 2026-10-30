<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\ReferringDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReferringDomainTest extends TestCase
{
    use RefreshDatabase;

    // =============================================
    // Model Attribute Tests
    // =============================================

    /** @test */
    public function it_can_create_a_referring_domain_with_all_attributes(): void
    {
        $project = Project::factory()->create();
        
        $domain = ReferringDomain::create([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'backlinks_count' => 45,
            'domain_trust_score' => 72,
            'first_seen_at' => now()->subDays(30),
            'last_seen_at' => now(),
        ]);

        $this->assertDatabaseHas('referring_domains', [
            'id' => $domain->id,
            'domain' => 'example.com',
            'backlinks_count' => 45,
            'domain_trust_score' => 72,
        ]);
    }

    /** @test */
    public function it_casts_backlinks_count_to_integer(): void
    {
        $domain = ReferringDomain::factory()->create(['backlinks_count' => 150]);

        $this->assertIsInt($domain->backlinks_count);
        $this->assertEquals(150, $domain->backlinks_count);
    }

    /** @test */
    public function it_casts_domain_trust_score_to_integer(): void
    {
        $domain = ReferringDomain::factory()->create(['domain_trust_score' => 85]);

        $this->assertIsInt($domain->domain_trust_score);
        $this->assertEquals(85, $domain->domain_trust_score);
    }

    /** @test */
    public function it_casts_first_seen_at_to_datetime(): void
    {
        $domain = ReferringDomain::factory()->create([
            'first_seen_at' => '2026-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $domain->first_seen_at);
        $this->assertEquals('2026-01-15 10:30:00', $domain->first_seen_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_casts_last_seen_at_to_datetime(): void
    {
        $domain = ReferringDomain::factory()->create([
            'last_seen_at' => '2026-02-28 14:45:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $domain->last_seen_at);
        $this->assertEquals('2026-02-28 14:45:00', $domain->last_seen_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_defaults_backlinks_count_to_zero(): void
    {
        // Create without backlinks_count field - DB default should apply
        $project = Project::factory()->create();
        $domain = ReferringDomain::create([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'domain_trust_score' => 50,
        ]);

        $freshDomain = ReferringDomain::find($domain->id);
        $this->assertEquals(0, $freshDomain->backlinks_count);
    }

    /** @test */
    public function it_defaults_domain_trust_score_to_zero(): void
    {
        // Create without domain_trust_score field - DB default should apply
        $project = Project::factory()->create();
        $domain = ReferringDomain::create([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'backlinks_count' => 10,
        ]);

        $freshDomain = ReferringDomain::find($domain->id);
        $this->assertEquals(0, $freshDomain->domain_trust_score);
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $domain = new ReferringDomain();
        
        $fillable = [
            'project_id',
            'domain',
            'backlinks_count',
            'domain_trust_score',
            'first_seen_at',
            'last_seen_at',
        ];

        $this->assertEquals($fillable, $domain->getFillable());
    }

    /** @test */
    public function it_uses_correct_table_name(): void
    {
        $domain = new ReferringDomain();

        $this->assertEquals('referring_domains', $domain->getTable());
    }

    // =============================================
    // Relationship Tests
    // =============================================

    /** @test */
    public function it_belongs_to_a_project(): void
    {
        $domain = ReferringDomain::factory()->create();

        $this->assertInstanceOf(Project::class, $domain->project);
        $this->assertEquals($domain->project_id, $domain->project->id);
    }

    // =============================================
    // Scope Tests
    // =============================================

    /** @test */
    public function scope_order_by_backlinks_count_orders_descending(): void
    {
        ReferringDomain::factory()->create(['backlinks_count' => 10]);
        ReferringDomain::factory()->create(['backlinks_count' => 50]);
        ReferringDomain::factory()->create(['backlinks_count' => 30]);

        $domains = ReferringDomain::orderByBacklinksCount()->get();

        $this->assertEquals(50, $domains[0]->backlinks_count);
        $this->assertEquals(30, $domains[1]->backlinks_count);
        $this->assertEquals(10, $domains[2]->backlinks_count);
    }

    /** @test */
    public function scope_order_by_trust_score_orders_descending(): void
    {
        ReferringDomain::factory()->create(['domain_trust_score' => 50]);
        ReferringDomain::factory()->create(['domain_trust_score' => 90]);
        ReferringDomain::factory()->create(['domain_trust_score' => 70]);

        $domains = ReferringDomain::orderByTrustScore()->get();

        $this->assertEquals(90, $domains[0]->domain_trust_score);
        $this->assertEquals(70, $domains[1]->domain_trust_score);
        $this->assertEquals(50, $domains[2]->domain_trust_score);
    }

    // =============================================
    // Unique Constraint Tests
    // =============================================

    /** @test */
    public function it_enforces_unique_project_domain_constraint(): void
    {
        $project = Project::factory()->create();
        
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function it_allows_same_domain_for_different_projects(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        
        $domain1 = ReferringDomain::factory()->create([
            'project_id' => $project1->id,
            'domain' => 'example.com',
        ]);

        $domain2 = ReferringDomain::factory()->create([
            'project_id' => $project2->id,
            'domain' => 'example.com',
        ]);

        $this->assertNotEquals($domain1->id, $domain2->id);
        $this->assertEquals('example.com', $domain1->domain);
        $this->assertEquals('example.com', $domain2->domain);
    }

    // =============================================
    // Edge Cases
    // =============================================

    /** @test */
    public function it_accepts_domain_trust_score_of_zero(): void
    {
        $domain = ReferringDomain::factory()->create(['domain_trust_score' => 0]);

        $this->assertEquals(0, $domain->domain_trust_score);
    }

    /** @test */
    public function it_accepts_domain_trust_score_of_100(): void
    {
        $domain = ReferringDomain::factory()->create(['domain_trust_score' => 100]);

        $this->assertEquals(100, $domain->domain_trust_score);
    }

    /** @test */
    public function first_seen_at_can_be_null(): void
    {
        $domain = ReferringDomain::factory()->create(['first_seen_at' => null]);

        $this->assertNull($domain->first_seen_at);
    }

    /** @test */
    public function last_seen_at_can_be_null(): void
    {
        $domain = ReferringDomain::factory()->create(['last_seen_at' => null]);

        $this->assertNull($domain->last_seen_at);
    }

    // =============================================
    // Security Tests
    // =============================================

    /** @test */
    public function it_safely_stores_sql_injection_in_domain(): void
    {
        $maliciousDomain = "example.com'; DROP TABLE referring_domains; --";
        
        $domain = ReferringDomain::factory()->create(['domain' => $maliciousDomain]);

        $this->assertEquals($maliciousDomain, $domain->domain);
        $this->assertDatabaseCount('referring_domains', 1);
    }

    /** @test */
    public function it_safely_stores_xss_in_domain(): void
    {
        $xssPayload = '<script>alert("xss")</script>.com';
        
        $domain = ReferringDomain::factory()->create(['domain' => $xssPayload]);

        $this->assertEquals($xssPayload, $domain->domain);
    }

    /** @test */
    public function it_handles_unicode_in_domain(): void
    {
        $unicodeDomain = '例え.jp';
        
        $domain = ReferringDomain::factory()->create(['domain' => $unicodeDomain]);

        $this->assertEquals($unicodeDomain, $domain->domain);
    }

    // =============================================
    // Cascade Delete Tests
    // =============================================

    /** @test */
    public function it_is_deleted_when_project_is_deleted(): void
    {
        $project = Project::factory()->create();
        $domain = ReferringDomain::factory()->create(['project_id' => $project->id]);

        $project->delete();

        $this->assertDatabaseMissing('referring_domains', ['id' => $domain->id]);
    }
}
