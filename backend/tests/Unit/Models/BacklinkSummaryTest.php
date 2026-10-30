<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\BacklinkSummary;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BacklinkSummaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a backlink summary with valid data.
     */
    public function test_create_backlink_summary_with_valid_data(): void
    {
        $site = Site::factory()->create();
        
        $summary = BacklinkSummary::create([
            'site_id' => $site->id,
            'domain' => 'example.com',
            'total_backlinks' => 150,
            'referring_domains' => 45,
            'domain_authority' => 35.50,
            'checked_at' => now(),
        ]);
        
        $this->assertInstanceOf(BacklinkSummary::class, $summary);
        $this->assertEquals('example.com', $summary->domain);
        $this->assertEquals(150, $summary->total_backlinks);
        $this->assertEquals(45, $summary->referring_domains);
        $this->assertEquals(35.50, $summary->domain_authority);
    }

    /**
     * Test backlink summary belongs to site relationship.
     */
    public function test_backlink_summary_belongs_to_site(): void
    {
        $site = Site::factory()->create();
        $summary = BacklinkSummary::factory()->create(['site_id' => $site->id]);
        
        $this->assertInstanceOf(Site::class, $summary->site);
        $this->assertEquals($site->id, $summary->site->id);
    }

    /**
     * Test site has many backlink summaries relationship.
     */
    public function test_site_has_many_backlink_summaries(): void
    {
        $site = Site::factory()->create();
        
        BacklinkSummary::factory()->count(3)->create(['site_id' => $site->id]);
        
        $this->assertCount(3, $site->backlinkSummaries);
        $this->assertInstanceOf(BacklinkSummary::class, $site->backlinkSummaries->first());
    }

    /**
     * Test unique constraint on site_id and domain combination.
     */
    public function test_unique_constraint_on_site_and_domain(): void
    {
        $site = Site::factory()->create();
        
        BacklinkSummary::factory()->create([
            'site_id' => $site->id,
            'domain' => 'duplicate-domain.com',
        ]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        BacklinkSummary::factory()->create([
            'site_id' => $site->id,
            'domain' => 'duplicate-domain.com',
        ]);
    }

    /**
     * Test same domain allowed for different sites.
     */
    public function test_same_domain_allowed_for_different_sites(): void
    {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();
        
        $summary1 = BacklinkSummary::factory()->create([
            'site_id' => $site1->id,
            'domain' => 'shared-domain.com',
        ]);
        
        $summary2 = BacklinkSummary::factory()->create([
            'site_id' => $site2->id,
            'domain' => 'shared-domain.com',
        ]);
        
        $this->assertNotEquals($summary1->id, $summary2->id);
    }

    /**
     * Test default values.
     */
    public function test_default_values(): void
    {
        $site = Site::factory()->create();
        
        $summary = BacklinkSummary::create([
            'site_id' => $site->id,
            'domain' => 'defaults-test.com',
        ]);
        
        $this->assertEquals(0, $summary->total_backlinks);
        $this->assertEquals(0, $summary->referring_domains);
        $this->assertEquals(0.00, $summary->domain_authority);
        $this->assertNotNull($summary->checked_at);
    }

    /**
     * Test factory high authority state.
     */
    public function test_factory_high_authority_state(): void
    {
        $summary = BacklinkSummary::factory()->highAuthority()->create();
        
        $this->assertGreaterThanOrEqual(60, $summary->domain_authority);
        $this->assertGreaterThanOrEqual(1000, $summary->total_backlinks);
        $this->assertGreaterThanOrEqual(100, $summary->referring_domains);
    }

    /**
     * Test factory low authority state.
     */
    public function test_factory_low_authority_state(): void
    {
        $summary = BacklinkSummary::factory()->lowAuthority()->create();
        
        $this->assertLessThanOrEqual(30, $summary->domain_authority);
        $this->assertLessThanOrEqual(100, $summary->total_backlinks);
        $this->assertLessThanOrEqual(10, $summary->referring_domains);
    }

    /**
     * Test factory recently checked state.
     */
    public function test_factory_recently_checked_state(): void
    {
        $summary = BacklinkSummary::factory()->recentlyChecked()->create();
        
        $this->assertTrue($summary->checked_at->isAfter(now()->subDays(2)));
    }

    /**
     * Test factory stale state.
     */
    public function test_factory_stale_state(): void
    {
        $summary = BacklinkSummary::factory()->stale()->create();
        
        $this->assertTrue($summary->checked_at->isBefore(now()->subDays(30)));
    }

    /**
     * Test cascade delete when site is deleted.
     */
    public function test_cascade_delete_when_site_deleted(): void
    {
        $site = Site::factory()->create();
        $summary = BacklinkSummary::factory()->create(['site_id' => $site->id]);
        
        $site->delete();
        
        $this->assertDatabaseMissing('backlink_summaries', ['id' => $summary->id]);
    }

    /**
     * Test domain authority range validation.
     */
    public function test_domain_authority_range(): void
    {
        $site = Site::factory()->create();
        
        // Test minimum
        $summary1 = BacklinkSummary::create([
            'site_id' => $site->id,
            'domain' => 'min-da.com',
            'domain_authority' => 0,
        ]);
        $this->assertEquals(0, $summary1->domain_authority);
        
        // Test maximum
        $summary2 = BacklinkSummary::create([
            'site_id' => $site->id,
            'domain' => 'max-da.com',
            'domain_authority' => 100,
        ]);
        $this->assertEquals(100, $summary2->domain_authority);
    }

    /**
     * Test total_backlinks cannot be negative.
     *
     * @requires extension pdo_mysql
     * @group mysql-only
     */
    public function test_total_backlinks_unsigned(): void
    {
        // This test requires MySQL which enforces UNSIGNED constraints.
        // SQLite does not enforce these constraints.
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('UNSIGNED constraint enforcement requires MySQL.');
        }

        $site = Site::factory()->create();

        // This should fail due to unsigned constraint
        $this->expectException(\Illuminate\Database\QueryException::class);

        BacklinkSummary::create([
            'site_id' => $site->id,
            'domain' => 'negative-test.com',
            'total_backlinks' => -1,
        ]);
    }

    /**
     * Test referring_domains cannot be negative.
     *
     * @requires extension pdo_mysql
     * @group mysql-only
     */
    public function test_referring_domains_unsigned(): void
    {
        // This test requires MySQL which enforces UNSIGNED constraints.
        // SQLite does not enforce these constraints.
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('UNSIGNED constraint enforcement requires MySQL.');
        }

        $site = Site::factory()->create();

        // This should fail due to unsigned constraint
        $this->expectException(\Illuminate\Database\QueryException::class);

        BacklinkSummary::create([
            'site_id' => $site->id,
            'domain' => 'negative-referring.com',
            'referring_domains' => -1,
        ]);
    }

    /**
     * Test domain length limit.
     *
     * @requires extension pdo_mysql
     * @group mysql-only
     */
    public function test_domain_length_limit(): void
    {
        // This test requires MySQL which enforces VARCHAR length constraints.
        // SQLite does not enforce these constraints.
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('VARCHAR length constraint enforcement requires MySQL.');
        }

        $site = Site::factory()->create();
        $longDomain = str_repeat('a', 256) . '.com';

        // This should fail due to varchar(255) constraint
        $this->expectException(\Illuminate\Database\QueryException::class);

        BacklinkSummary::create([
            'site_id' => $site->id,
            'domain' => $longDomain,
        ]);
    }

    /**
     * Test mass assignment protection.
     */
    public function test_mass_assignment_fillable(): void
    {
        $site = Site::factory()->create();
        
        $summary = BacklinkSummary::create([
            'site_id' => $site->id,
            'domain' => 'mass-assignment.com',
            'total_backlinks' => 500,
            'referring_domains' => 100,
            'domain_authority' => 45.00,
            'checked_at' => now(),
            'unexpected_field' => 'should be ignored',
        ]);
        
        $this->assertEquals('mass-assignment.com', $summary->domain);
        $this->assertArrayNotHasKey('unexpected_field', $summary->toArray());
    }
}
