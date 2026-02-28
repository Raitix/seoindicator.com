<?php

namespace Tests\Unit\Repositories;

use App\Models\PWAAnalysis;
use App\Models\Site;
use App\Repositories\PWAAnalysisRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for PWAAnalysisRepository.
 *
 * @see TASK-24-2
 * @see TEST-24-2
 */
class PWAAnalysisRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected PWAAnalysisRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(PWAAnalysisRepository::class);
    }

    // ==========================================
    // CRUD Operations Tests (CRUD)
    // ==========================================

    /** @test CRUD-001: Create analysis with all fields */
    public function test_crud_001_create_analysis(): void
    {
        $site = Site::factory()->create();

        $data = [
            'site_id' => $site->id,
            'has_manifest' => true,
            'manifest_url' => '/manifest.json',
            'manifest_valid' => true,
            'manifest_json' => ['name' => 'Test App'],
            'pwa_score' => 85,
            'readiness_level' => 'enhanced',
            'icons_total' => 4,
            'icons_valid' => 3,
        ];

        $analysis = $this->repository->create($data);

        $this->assertInstanceOf(PWAAnalysis::class, $analysis);
        $this->assertEquals($site->id, $analysis->site_id);
        $this->assertTrue($analysis->has_manifest);
        $this->assertEquals(85, $analysis->pwa_score);
        $this->assertDatabaseHas('pwa_analyses', ['id' => $analysis->id]);
    }

    /** @test CRUD-002: Find analysis by ID */
    public function test_crud_002_find_by_id(): void
    {
        $analysis = PWAAnalysis::factory()->create();

        $found = $this->repository->find($analysis->id);

        $this->assertInstanceOf(PWAAnalysis::class, $found);
        $this->assertEquals($analysis->id, $found->id);
    }

    /** @test CRUD-003: Find analysis by ID returns null if not found */
    public function test_crud_003_find_returns_null_if_not_found(): void
    {
        $found = $this->repository->find(99999);

        $this->assertNull($found);
    }

    /** @test CRUD-004: Update analysis */
    public function test_crud_004_update_analysis(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'pwa_score' => 50,
            'readiness_level' => 'basic',
        ]);

        $updated = $this->repository->update($analysis->id, [
            'pwa_score' => 85,
            'readiness_level' => 'enhanced',
        ]);

        $this->assertEquals(85, $updated->pwa_score);
        $this->assertEquals('enhanced', $updated->readiness_level);
        $this->assertDatabaseHas('pwa_analyses', [
            'id' => $analysis->id,
            'pwa_score' => 85,
        ]);
    }

    /** @test CRUD-005: Update returns false if not found */
    public function test_crud_005_update_returns_false_if_not_found(): void
    {
        $result = $this->repository->update(99999, ['pwa_score' => 85]);

        $this->assertFalse($result);
    }

    /** @test CRUD-006: Delete analysis */
    public function test_crud_006_delete_analysis(): void
    {
        $analysis = PWAAnalysis::factory()->create();

        $result = $this->repository->delete($analysis->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('pwa_analyses', ['id' => $analysis->id]);
    }

    /** @test CRUD-007: Delete returns false if not found */
    public function test_crud_007_delete_returns_false_if_not_found(): void
    {
        $result = $this->repository->delete(99999);

        $this->assertFalse($result);
    }

    /** @test CRUD-008: Create with JSON fields stored correctly */
    public function test_crud_008_create_with_json_fields(): void
    {
        $site = Site::factory()->create();

        $data = [
            'site_id' => $site->id,
            'manifest_json' => [
                'name' => 'Test App',
                'icons' => [
                    ['src' => '/icon.png', 'sizes' => '192x192'],
                ],
            ],
            'icon_errors' => [
                ['size' => '180x180', 'error' => 'Not found'],
            ],
        ];

        $analysis = $this->repository->create($data);

        $this->assertIsArray($analysis->manifest_json);
        $this->assertEquals('Test App', $analysis->manifest_json['name']);
        $this->assertIsArray($analysis->icon_errors);
        $this->assertCount(1, $analysis->icon_errors);
    }

    /** @test CRUD-009: Update JSON fields correctly */
    public function test_crud_009_update_json_fields(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'manifest_json' => ['name' => 'Old Name'],
            'icon_errors' => null,
        ]);

        $updated = $this->repository->update($analysis->id, [
            'manifest_json' => ['name' => 'New Name', 'version' => '2.0'],
            'icon_errors' => [['size' => '96x96', 'error' => 'Missing']],
        ]);

        $this->assertEquals('New Name', $updated->manifest_json['name']);
        $this->assertEquals('2.0', $updated->manifest_json['version']);
        $this->assertCount(1, $updated->icon_errors);
    }

    // ==========================================
    // Query Methods Tests (QM)
    // ==========================================

    /** @test QM-001: Find by site ID */
    public function test_qm_001_find_by_site_id(): void
    {
        $site = Site::factory()->create();
        $analysis = PWAAnalysis::factory()->forSite($site)->create();

        $found = $this->repository->findBySiteId($site->id);

        $this->assertInstanceOf(PWAAnalysis::class, $found);
        $this->assertEquals($site->id, $found->site_id);
    }

    /** @test QM-002: Find by site ID returns null if not found */
    public function test_qm_002_find_by_site_id_returns_null(): void
    {
        $found = $this->repository->findBySiteId(99999);

        $this->assertNull($found);
    }

    /** @test QM-003: Get latest for site ordered by analyzed_at */
    public function test_qm_003_get_latest_for_site_ordered(): void
    {
        $site = Site::factory()->create();

        $old = PWAAnalysis::factory()->forSite($site)->create([
            'analyzed_at' => now()->subDays(5),
        ]);
        $latest = PWAAnalysis::factory()->forSite($site)->create([
            'analyzed_at' => now(),
        ]);
        $middle = PWAAnalysis::factory()->forSite($site)->create([
            'analyzed_at' => now()->subDays(2),
        ]);

        $result = $this->repository->getLatestForSite($site->id);

        $this->assertEquals($latest->id, $result->id);
    }

    /** @test QM-004: Get latest for site returns null if none */
    public function test_qm_004_get_latest_for_site_returns_null(): void
    {
        $site = Site::factory()->create();

        $result = $this->repository->getLatestForSite($site->id);

        $this->assertNull($result);
    }

    /** @test QM-005: Multiple analyses same site returns latest */
    public function test_qm_005_multiple_analyses_same_site(): void
    {
        $site = Site::factory()->create();

        // Create multiple analyses
        PWAAnalysis::factory()->forSite($site)->count(5)->create([
            'analyzed_at' => now()->subDays(rand(1, 10)),
        ]);
        $latest = PWAAnalysis::factory()->forSite($site)->create([
            'analyzed_at' => now(),
        ]);

        $result = $this->repository->getLatestForSite($site->id);

        $this->assertEquals($latest->id, $result->id);
    }

    /** @test QM-006: Find by site ID returns most recent */
    public function test_qm_006_find_by_site_id_returns_most_recent(): void
    {
        $site = Site::factory()->create();

        $old = PWAAnalysis::factory()->forSite($site)->create([
            'analyzed_at' => now()->subDay(),
        ]);
        $newer = PWAAnalysis::factory()->forSite($site)->create([
            'analyzed_at' => now(),
        ]);

        $found = $this->repository->findBySiteId($site->id);

        // findBySiteId should return the most recent analysis
        $this->assertEquals($newer->id, $found->id);
    }

    // ==========================================
    // Edge Cases Tests (EC-REP)
    // ==========================================

    /** @test EC-REP-001: Delete non-existent returns false */
    public function test_ec_rep_001_delete_non_existent_returns_false(): void
    {
        $result = $this->repository->delete(99999);

        $this->assertFalse($result);
    }

    /** @test EC-REP-002: Update non-existent returns false */
    public function test_ec_rep_002_update_non_existent_returns_false(): void
    {
        $result = $this->repository->update(99999, ['pwa_score' => 50]);

        $this->assertFalse($result);
    }

    /** @test EC-REP-003: Find with invalid ID type */
    public function test_ec_rep_003_find_with_invalid_id(): void
    {
        // PHP will type-hint enforce int, but test null coalescing
        $found = $this->repository->find(0);

        $this->assertNull($found);
    }

    /** @test EC-REP-004: Create with missing required field throws error */
    public function test_ec_rep_004_create_with_missing_required_field(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Missing site_id (required field)
        $this->repository->create(['pwa_score' => 50]);
    }

    /** @test EC-REP-005: Create with invalid readiness level */
    public function test_ec_rep_005_create_with_invalid_readiness_level(): void
    {
        $site = Site::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->repository->create([
            'site_id' => $site->id,
            'readiness_level' => 'invalid_level',
        ]);
    }

    // ==========================================
    // Performance Tests (PF)
    // ==========================================

    /** @test PF-001: Large manifest storage is fast */
    public function test_pf_001_large_manifest_storage(): void
    {
        $site = Site::factory()->create();

        // Create 10KB manifest
        $largeManifest = [
            'name' => str_repeat('A', 5000),
            'description' => str_repeat('D', 5000),
        ];

        $start = microtime(true);
        $analysis = $this->repository->create([
            'site_id' => $site->id,
            'manifest_json' => $largeManifest,
        ]);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $elapsed, 'Create should complete in < 100ms');
        $this->assertNotNull($analysis->id);
    }

    /** @test PF-002: Bulk queries are efficient */
    public function test_pf_002_bulk_queries_efficient(): void
    {
        $site = Site::factory()->create();

        // Create 50 analyses
        PWAAnalysis::factory()->forSite($site)->count(50)->create();

        $start = microtime(true);
        $latest = $this->repository->getLatestForSite($site->id);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(50, $elapsed, 'Query should complete in < 50ms');
        $this->assertNotNull($latest);
    }

    // ==========================================
    // Integration Tests (INT)
    // ==========================================

    /** @test INT-001: Full CRUD workflow */
    public function test_int_001_full_crud_workflow(): void
    {
        $site = Site::factory()->create();

        // Create
        $analysis = $this->repository->create([
            'site_id' => $site->id,
            'has_manifest' => false,
            'pwa_score' => 0,
        ]);

        $this->assertNotNull($analysis->id);

        // Read
        $found = $this->repository->find($analysis->id);
        $this->assertEquals($analysis->id, $found->id);

        // Update
        $updated = $this->repository->update($analysis->id, [
            'has_manifest' => true,
            'pwa_score' => 75,
        ]);
        $this->assertEquals(75, $updated->pwa_score);

        // Delete
        $deleted = $this->repository->delete($analysis->id);
        $this->assertTrue($deleted);

        // Verify deleted
        $notFound = $this->repository->find($analysis->id);
        $this->assertNull($notFound);
    }

    /** @test INT-002: Site relationship integrity */
    public function test_int_002_site_relationship_integrity(): void
    {
        $site = Site::factory()->create();
        $analysis = $this->repository->create([
            'site_id' => $site->id,
            'pwa_score' => 50,
        ]);

        // Verify relationship loaded
        $found = $this->repository->find($analysis->id);
        $this->assertEquals($site->id, $found->site_id);

        // Verify site can access analyses
        $site->refresh();
        $this->assertTrue($site->pwaAnalyses->contains($analysis));
    }
}
