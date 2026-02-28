<?php

namespace Tests\Unit\Models;

use App\Models\PWAAnalysis;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for PWAAnalysis model.
 *
 * @see TASK-24-2
 * @see TEST-24-2
 */
class PWAAnalysisTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Model Creation Tests (MC)
    // ==========================================

    /** @test MC-001: Can create PWA analysis with all fields */
    public function test_mc_001_can_create_with_all_fields(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'has_manifest' => true,
            'manifest_url' => '/manifest.json',
            'manifest_valid' => true,
            'manifest_json' => ['name' => 'Test App'],
            'has_name' => true,
            'has_short_name' => true,
            'has_start_url' => true,
            'has_display' => true,
            'has_background_color' => true,
            'has_theme_color' => true,
            'has_description' => true,
            'icons_total' => 4,
            'icons_valid' => 3,
            'icons_192_present' => true,
            'icons_512_present' => true,
            'icons_180_present' => false,
            'icon_errors' => [['size' => '180x180', 'error' => 'Not found']],
            'pwa_score' => 85,
            'readiness_level' => 'enhanced',
            'analyzed_at' => now(),
        ]);

        $this->assertDatabaseHas('pwa_analyses', [
            'id' => $analysis->id,
            'site_id' => $site->id,
            'has_manifest' => 1,
            'pwa_score' => 85,
            'readiness_level' => 'enhanced',
        ]);
    }

    /** @test MC-002: Can create with minimal fields */
    public function test_mc_002_can_create_with_minimal_fields(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
        ]);

        $this->assertDatabaseHas('pwa_analyses', [
            'id' => $analysis->id,
            'site_id' => $site->id,
        ]);
    }

    /** @test MC-003: Default values are set correctly */
    public function test_mc_003_default_values_are_set_correctly(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create(['site_id' => $site->id]);
        $analysis->refresh(); // Get DB defaults

        $this->assertFalse($analysis->has_manifest);
        $this->assertFalse($analysis->manifest_valid);
        $this->assertEquals(0, $analysis->pwa_score);
        $this->assertEquals('none', $analysis->readiness_level);
        $this->assertEquals(0, $analysis->icons_total);
        $this->assertEquals(0, $analysis->icons_valid);
    }

    /** @test MC-004: manifest_json is cast to array */
    public function test_mc_004_manifest_json_is_cast_to_array(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'manifest_json' => ['name' => 'Test App', 'version' => '1.0'],
        ]);

        $this->assertIsArray($analysis->manifest_json);
        $this->assertEquals('Test App', $analysis->manifest_json['name']);
    }

    /** @test MC-005: icon_errors is cast to array */
    public function test_mc_005_icon_errors_is_cast_to_array(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'icon_errors' => [
                ['size' => '180x180', 'error' => 'Not found', 'severity' => 'warning'],
            ],
        ]);

        $this->assertIsArray($analysis->icon_errors);
        $this->assertCount(1, $analysis->icon_errors);
        $this->assertEquals('180x180', $analysis->icon_errors[0]['size']);
    }

    /** @test MC-006: Boolean fields are cast correctly */
    public function test_mc_006_boolean_fields_are_cast_correctly(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => true,
            'has_short_name' => false,
            'icons_192_present' => true,
            'icons_512_present' => false,
        ]);

        $this->assertTrue($analysis->has_manifest);
        $this->assertTrue($analysis->manifest_valid);
        $this->assertTrue($analysis->has_name);
        $this->assertFalse($analysis->has_short_name);
        $this->assertTrue($analysis->icons_192_present);
        $this->assertFalse($analysis->icons_512_present);
    }

    /** @test MC-007: pwa_score accepts valid range 0-100 */
    public function test_mc_007_pwa_score_accepts_valid_range(): void
    {
        $site = Site::factory()->create();

        $analysis1 = PWAAnalysis::create(['site_id' => $site->id, 'pwa_score' => 0]);
        $this->assertEquals(0, $analysis1->pwa_score);

        $analysis2 = PWAAnalysis::create(['site_id' => $site->id, 'pwa_score' => 50]);
        $this->assertEquals(50, $analysis2->pwa_score);

        $analysis3 = PWAAnalysis::create(['site_id' => $site->id, 'pwa_score' => 100]);
        $this->assertEquals(100, $analysis3->pwa_score);
    }

    /** @test MC-008: readiness_level accepts valid enum values */
    public function test_mc_008_readiness_level_accepts_valid_enum_values(): void
    {
        $site = Site::factory()->create();

        $validLevels = ['none', 'basic', 'enhanced', 'full'];

        foreach ($validLevels as $level) {
            $analysis = PWAAnalysis::create([
                'site_id' => $site->id,
                'readiness_level' => $level,
            ]);
            $this->assertEquals($level, $analysis->readiness_level);
        }
    }

    // ==========================================
    // Relationship Tests (RL)
    // ==========================================

    /** @test RL-001: PWA analysis belongs to site */
    public function test_rl_001_belongs_to_site(): void
    {
        $site = Site::factory()->create();
        $analysis = PWAAnalysis::factory()->forSite($site)->create();

        $this->assertEquals($site->id, $analysis->site->id);
        $this->assertInstanceOf(Site::class, $analysis->site);
    }

    /** @test RL-002: Site has many PWA analyses */
    public function test_rl_002_site_has_many_pwa_analyses(): void
    {
        $site = Site::factory()->create();
        $analysis1 = PWAAnalysis::factory()->forSite($site)->create();
        $analysis2 = PWAAnalysis::factory()->forSite($site)->create();

        $this->assertCount(2, $site->pwaAnalyses);
        $this->assertTrue($site->pwaAnalyses->contains($analysis1));
        $this->assertTrue($site->pwaAnalyses->contains($analysis2));
    }

    /** @test RL-003: Deleting site cascades to PWA analyses */
    public function test_rl_003_deleting_site_cascades_to_analyses(): void
    {
        $site = Site::factory()->create();
        $analysis = PWAAnalysis::factory()->forSite($site)->create();

        $site->delete();

        $this->assertDatabaseMissing('pwa_analyses', [
            'id' => $analysis->id,
        ]);
    }

    // ==========================================
    // Scope Tests (SC)
    // ==========================================

    /** @test SC-001: Scope forSite filters by site */
    public function test_sc_001_scope_for_site_filters_by_site(): void
    {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        PWAAnalysis::factory()->forSite($site1)->count(3)->create();
        PWAAnalysis::factory()->forSite($site2)->count(2)->create();

        $results = PWAAnalysis::forSite($site1->id)->get();

        $this->assertCount(3, $results);
        $results->each(fn ($a) => $this->assertEquals($site1->id, $a->site_id));
    }

    /** @test SC-002: Scope withManifest filters has_manifest=true */
    public function test_sc_002_scope_with_manifest_filters_has_manifest(): void
    {
        $site = Site::factory()->create();

        PWAAnalysis::factory()->forSite($site)->withManifest()->count(3)->create();
        PWAAnalysis::factory()->forSite($site)->withoutManifest()->count(2)->create();

        $results = PWAAnalysis::withManifest()->get();

        $this->assertCount(3, $results);
        $results->each(fn ($a) => $this->assertTrue($a->has_manifest));
    }

    /** @test SC-003: Scope withoutManifest filters has_manifest=false */
    public function test_sc_003_scope_without_manifest_filters_no_manifest(): void
    {
        $site = Site::factory()->create();

        PWAAnalysis::factory()->forSite($site)->withManifest()->count(2)->create();
        PWAAnalysis::factory()->forSite($site)->withoutManifest()->count(3)->create();

        $results = PWAAnalysis::withoutManifest()->get();

        $this->assertCount(3, $results);
        $results->each(fn ($a) => $this->assertFalse($a->has_manifest));
    }

    /** @test SC-004: Scope byReadinessLevel filters correctly */
    public function test_sc_004_scope_by_readiness_level_filters(): void
    {
        $site = Site::factory()->create();

        PWAAnalysis::factory()->forSite($site)->create(['readiness_level' => 'none']);
        PWAAnalysis::factory()->forSite($site)->create(['readiness_level' => 'basic']);
        PWAAnalysis::factory()->forSite($site)->create(['readiness_level' => 'enhanced']);
        PWAAnalysis::factory()->forSite($site)->create(['readiness_level' => 'full']);

        $results = PWAAnalysis::byReadinessLevel('enhanced')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('enhanced', $results->first()->readiness_level);
    }

    /** @test SC-005: Scope minScore filters by minimum pwa_score */
    public function test_sc_005_scope_min_score_filters(): void
    {
        $site = Site::factory()->create();

        PWAAnalysis::factory()->forSite($site)->create(['pwa_score' => 30]);
        PWAAnalysis::factory()->forSite($site)->create(['pwa_score' => 50]);
        PWAAnalysis::factory()->forSite($site)->create(['pwa_score' => 75]);
        PWAAnalysis::factory()->forSite($site)->create(['pwa_score' => 95]);

        $results = PWAAnalysis::minScore(70)->get();

        $this->assertCount(2, $results);
        $results->each(fn ($a) => $this->assertGreaterThanOrEqual(70, $a->pwa_score));
    }

    // ==========================================
    // Accessor Tests (AC)
    // ==========================================

    /** @test AC-001: Get manifest fields attribute returns structured data */
    public function test_ac_001_get_manifest_fields_attribute(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'has_name' => true,
            'has_short_name' => true,
            'has_start_url' => true,
            'has_display' => true,
            'has_background_color' => false,
            'has_theme_color' => true,
            'has_description' => false,
            'manifest_json' => [
                'name' => 'Test App',
                'short_name' => 'Test',
                'start_url' => '/',
                'display' => 'standalone',
                'theme_color' => '#2563eb',
            ],
        ]);

        $fields = $analysis->manifest_fields;

        $this->assertIsArray($fields);
        $this->assertTrue($fields['name']['present']);
        $this->assertEquals('Test App', $fields['name']['value']);
        $this->assertFalse($fields['background_color']['present']);
        $this->assertNull($fields['background_color']['value']);
    }

    /** @test AC-002: Get icon summary attribute returns validation summary */
    public function test_ac_002_get_icon_summary_attribute(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'icons_total' => 4,
            'icons_valid' => 3,
            'icons_192_present' => true,
            'icons_512_present' => true,
            'icons_180_present' => false,
            'icon_errors' => [
                ['size' => '180x180', 'error' => 'Not found', 'severity' => 'warning'],
            ],
        ]);

        $summary = $analysis->icon_summary;

        $this->assertIsArray($summary);
        $this->assertEquals(4, $summary['total']);
        $this->assertEquals(3, $summary['valid']);
        $this->assertTrue($summary['required']['192x192']['present']);
        $this->assertFalse($summary['required']['180x180']['present']);
        $this->assertCount(1, $summary['errors']);
    }

    // ==========================================
    // Edge Case Tests (EC)
    // ==========================================

    /** @test EC-001: Null manifest_json handled correctly */
    public function test_ec_001_null_manifest_json_handled(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'manifest_json' => null,
        ]);

        $this->assertNull($analysis->manifest_json);
    }

    /** @test EC-002: Empty manifest_json object handled */
    public function test_ec_002_empty_manifest_json_handled(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'manifest_json' => [],
        ]);

        $this->assertIsArray($analysis->manifest_json);
        $this->assertEmpty($analysis->manifest_json);
    }

    /** @test EC-003: Large manifest_json stored correctly */
    public function test_ec_003_large_manifest_json_stored(): void
    {
        $site = Site::factory()->create();

        // Create a large manifest (simulating 10KB+)
        $largeManifest = [
            'name' => str_repeat('A', 1000),
            'description' => str_repeat('D', 5000),
            'icons' => array_map(fn ($i) => [
                'src' => "/icons/icon-{$i}.png",
                'sizes' => "{$i}x{$i}",
                'type' => 'image/png',
            ], range(1, 50)),
        ];

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'manifest_json' => $largeManifest,
        ]);

        $this->assertIsArray($analysis->manifest_json);
        $this->assertCount(50, $analysis->manifest_json['icons']);
    }

    /** @test EC-004: Null icon_errors handled */
    public function test_ec_004_null_icon_errors_handled(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'icon_errors' => null,
        ]);

        $this->assertNull($analysis->icon_errors);
    }

    /** @test EC-005: Zero icons_total handled */
    public function test_ec_005_zero_icons_total_handled(): void
    {
        $site = Site::factory()->create();

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'icons_total' => 0,
            'icons_valid' => 0,
        ]);

        $this->assertEquals(0, $analysis->icons_total);
        $this->assertEquals(0, $analysis->icons_valid);
    }

    // ==========================================
    // Security Tests (SEC)
    // ==========================================

    /** @test SEC-001: SQL injection in JSON field is prevented */
    public function test_sec_001_sql_injection_in_json_prevented(): void
    {
        $site = Site::factory()->create();

        $maliciousJson = [
            'name' => "'; DROP TABLE pwa_analyses; --",
            'description' => '<script>alert("xss")</script>',
        ];

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'manifest_json' => $maliciousJson,
        ]);

        // Verify the data is stored as-is (parameterized query)
        $this->assertEquals("'; DROP TABLE pwa_analyses; --", $analysis->manifest_json['name']);

        // Verify table still exists
        $this->assertDatabaseHas('pwa_analyses', ['id' => $analysis->id]);
    }

    /** @test SEC-002: XSS in manifest values are not executed */
    public function test_sec_002_xss_in_manifest_values_not_executed(): void
    {
        $site = Site::factory()->create();

        $xssJson = [
            'name' => '<script>alert("xss")</script>',
            'description' => '<img src="x" onerror="alert(1)">',
        ];

        $analysis = PWAAnalysis::create([
            'site_id' => $site->id,
            'manifest_json' => $xssJson,
        ]);

        // Values are stored as-is, output escaping happens at display layer
        $this->assertEquals('<script>alert("xss")</script>', $analysis->manifest_json['name']);
    }
}
