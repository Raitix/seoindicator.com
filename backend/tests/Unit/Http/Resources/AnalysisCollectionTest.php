<?php

namespace Tests\Unit\Http\Resources;

use App\Models\AnalysisHistory;
use App\Http\Resources\AnalysisResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Tests\TestCase;

/**
 * AnalysisCollection Tests
 *
 * Tests for the AnalysisResource collection transformation.
 *
 * @group analysis-collection
 */
class AnalysisCollectionTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // COLLECTION TESTS (AC-*)
    // ============================================

    /** @test AC-001: Collection pagination */
    public function test_collection_pagination(): void
    {
        $analyses = AnalysisHistory::factory()->count(30)->create();

        $paginator = AnalysisHistory::paginate(15);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertCount(15, $data['data']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('links', $data);
    }

    /** @test AC-002: Meta fields present */
    public function test_meta_fields_present(): void
    {
        AnalysisHistory::factory()->count(45)->create();

        $paginator = AnalysisHistory::paginate(15);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(45, $data['meta']['total']);
        $this->assertEquals(1, $data['meta']['current_page']);
        $this->assertEquals(15, $data['meta']['per_page']);
        $this->assertEquals(3, $data['meta']['last_page']);
    }

    /** @test AC-003: Empty collection */
    public function test_empty_collection(): void
    {
        $paginator = AnalysisHistory::paginate(15);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        $this->assertCount(0, $data['data']);
        $this->assertEquals(0, $data['meta']['total']);
    }

    /** @test AC-004: Links present */
    public function test_links_present(): void
    {
        AnalysisHistory::factory()->count(30)->create();

        $paginator = AnalysisHistory::paginate(15);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('links', $data);
        $this->assertArrayHasKey('first', $data['links']);
        $this->assertArrayHasKey('last', $data['links']);
    }

    /** @test AC-005: Large collection */
    public function test_large_collection(): void
    {
        AnalysisHistory::factory()->count(100)->create();

        $paginator = AnalysisHistory::paginate(50);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        $this->assertCount(50, $data['data']);
        $this->assertEquals(100, $data['meta']['total']);
        $this->assertEquals(2, $data['meta']['last_page']);
    }

    /** @test AC-008: Custom perPage in meta */
    public function test_custom_per_page_in_meta(): void
    {
        AnalysisHistory::factory()->count(30)->create();

        $paginator = AnalysisHistory::paginate(25);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(25, $data['meta']['per_page']);
    }

    /** @test AC-009: Last page calculation */
    public function test_last_page_calculation(): void
    {
        AnalysisHistory::factory()->count(30)->create();

        $paginator = AnalysisHistory::paginate(15);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        // 30 items / 15 per page = 2 pages
        $this->assertEquals(2, $data['meta']['last_page']);
    }

    /** @test AC-010: Single page */
    public function test_single_page(): void
    {
        AnalysisHistory::factory()->count(5)->create();

        $paginator = AnalysisHistory::paginate(15);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(1, $data['meta']['last_page']);
        $this->assertCount(5, $data['data']);
    }

    // ============================================
    // PAGINATION NAVIGATION TESTS
    // ============================================

    /** @test First page has next link */
    public function test_first_page_has_next(): void
    {
        AnalysisHistory::factory()->count(30)->create();

        $paginator = AnalysisHistory::paginate(15, ['*'], 'page', 1);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        $this->assertNotNull($data['links']['next']);
        $this->assertNull($data['links']['prev']);
    }

    /** @test Middle page has both links */
    public function test_middle_page_has_both_links(): void
    {
        AnalysisHistory::factory()->count(45)->create();

        $paginator = AnalysisHistory::paginate(15, ['*'], 'page', 2);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        $this->assertNotNull($data['links']['next']);
        $this->assertNotNull($data['links']['prev']);
    }

    /** @test Last page has prev link */
    public function test_last_page_has_prev(): void
    {
        AnalysisHistory::factory()->count(30)->create();

        $paginator = AnalysisHistory::paginate(15, ['*'], 'page', 2);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        $this->assertNull($data['links']['next']);
        $this->assertNotNull($data['links']['prev']);
    }

    // ============================================
    // DATA STRUCTURE TESTS
    // ============================================

    /** @test Each item has required fields */
    public function test_each_item_has_required_fields(): void
    {
        AnalysisHistory::factory()->count(3)->create();

        $paginator = AnalysisHistory::paginate(15);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        foreach ($data['data'] as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('toolName', $item);
            $this->assertArrayHasKey('inputUrl', $item);
            $this->assertArrayHasKey('status', $item);
            $this->assertArrayHasKey('analyzedAt', $item);
        }
    }

    /** @test Collection preserves order */
    public function test_collection_preserves_order(): void
    {
        // Create analyses with specific dates
        AnalysisHistory::factory()->create(['analyzed_at' => '2026-03-01 10:00:00']);
        AnalysisHistory::factory()->create(['analyzed_at' => '2026-03-01 09:00:00']);
        AnalysisHistory::factory()->create(['analyzed_at' => '2026-03-01 11:00:00']);

        $paginator = AnalysisHistory::orderBy('analyzed_at', 'desc')->paginate(15);
        $collection = AnalysisResource::collection($paginator);

        $response = $collection->toResponse(request());
        $data = json_decode($response->getContent(), true);

        // Items should be in descending order by analyzed_at
        $dates = array_column($data['data'], 'analyzedAt');
        $this->assertTrue($dates[0] >= $dates[1]);
        $this->assertTrue($dates[1] >= $dates[2]);
    }
}
