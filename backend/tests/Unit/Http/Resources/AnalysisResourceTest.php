<?php

namespace Tests\Unit\Http\Resources;

use App\Models\AnalysisCost;
use App\Models\AnalysisHistory;
use App\Http\Resources\AnalysisResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AnalysisResource Tests
 *
 * Tests for the AnalysisResource transformation class.
 *
 * @group analysis-resource
 */
class AnalysisResourceTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // TRANSFORMATION TESTS (AR-*)
    // ============================================

    /** @test AR-001: Transform complete analysis */
    public function test_transform_complete_analysis(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com/page',
            'status' => 'completed',
            'results' => ['title' => 'Test', 'h1Count' => 2],
            'analysis_duration_ms' => 1234,
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertEquals($analysis->id, $data['id']);
        $this->assertEquals('meta-tag-analyzer', $data['toolName']);
        $this->assertEquals('https://example.com/page', $data['inputUrl']);
        $this->assertEquals('completed', $data['status']);
        $this->assertEquals(['title' => 'Test', 'h1Count' => 2], $data['results']);
        $this->assertEquals(1234, $data['analysisDurationMs']);
        $this->assertArrayHasKey('analyzedAt', $data);
        $this->assertArrayHasKey('createdAt', $data);
    }

    /** @test AR-002: Transform minimal analysis */
    public function test_transform_minimal_analysis(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'tool_name' => 'keyword-density',
            'input_url' => 'https://example.com',
            'status' => 'pending',
            'results' => null,
            'error_message' => null,
            'analysis_duration_ms' => null,
            'site_id' => null,
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertEquals($analysis->id, $data['id']);
        $this->assertNull($data['results']);
        $this->assertNull($data['errorMessage']);
        $this->assertNull($data['analysisDurationMs']);
        $this->assertNull($data['siteId']);
    }

    /** @test AR-003: Results as array */
    public function test_results_as_array(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'results' => ['key' => 'value', 'nested' => ['a' => 1, 'b' => 2]],
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertIsArray($data['results']);
        $this->assertEquals('value', $data['results']['key']);
        $this->assertEquals(['a' => 1, 'b' => 2], $data['results']['nested']);
    }

    /** @test AR-004: Date formatting */
    public function test_date_formatting(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'analyzed_at' => '2026-03-01 10:30:45',
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        // Should be ISO 8601 format
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $data['analyzedAt']
        );
    }

    /** @test AR-005: Cost summary included */
    public function test_cost_summary_included(): void
    {
        $analysis = AnalysisHistory::factory()->create();
        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.05,
            'cost_currency' => 'USD',
        ]);
        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.03,
            'cost_currency' => 'USD',
        ]);

        $analysis->load('costs');
        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('cost', $data);
        $this->assertEquals('0.080000', $data['cost']['total']);
        $this->assertEquals('USD', $data['cost']['currency']);
    }

    /** @test AR-006: Null cost handling */
    public function test_null_cost_handling(): void
    {
        $analysis = AnalysisHistory::factory()->create();

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('cost', $data);
        $this->assertEquals('0.000000', $data['cost']['total']);
    }

    /** @test AR-007: Duration formatting */
    public function test_duration_formatting(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'analysis_duration_ms' => 5678,
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertEquals(5678, $data['analysisDurationMs']);
    }

    /** @test AR-008: Tool name formatting */
    public function test_tool_name_formatting(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'tool_name' => 'meta-tag-analyzer',
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        // Snake_case should be preserved
        $this->assertEquals('meta-tag-analyzer', $data['toolName']);
    }

    /** @test AR-010: Status enum */
    public function test_status_enum(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'status' => 'completed',
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertEquals('completed', $data['status']);
    }

    /** @test AR-011: Site relationship */
    public function test_site_relationship(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'site_id' => 123,
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertEquals(123, $data['siteId']);
    }

    /** @test AR-012: Site null handling */
    public function test_site_null_handling(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'site_id' => null,
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertNull($data['siteId']);
    }

    /** @test AR-013: Large results */
    public function test_large_results(): void
    {
        $largeResults = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeResults["key_{$i}"] = str_repeat('a', 100);
        }

        $analysis = AnalysisHistory::factory()->create([
            'results' => $largeResults,
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertIsArray($data['results']);
        $this->assertCount(1000, $data['results']);
    }

    /** @test AR-014: Unicode in results */
    public function test_unicode_in_results(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'results' => [
                'title' => 'テストタイトル 🚀',
                'emoji' => '👍🎉',
                'chinese' => '中文测试',
            ],
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertEquals('テストタイトル 🚀', $data['results']['title']);
        $this->assertEquals('👍🎉', $data['results']['emoji']);
        $this->assertEquals('中文测试', $data['results']['chinese']);
    }

    /** @test AR-015: Empty results */
    public function test_empty_results(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'results' => null,
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertNull($data['results']);
    }

    // ============================================
    // COSTS RELATIONSHIP TESTS
    // ============================================

    /** @test Costs loaded in response */
    public function test_costs_loaded_when_present(): void
    {
        $analysis = AnalysisHistory::factory()->create();
        AnalysisCost::factory()->count(3)->create([
            'analysis_id' => $analysis->id,
        ]);

        $analysis->load('costs');
        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('costs', $data);
        $this->assertCount(3, $data['costs']);
    }

    /** @test Costs not loaded by default */
    public function test_costs_not_loaded_by_default(): void
    {
        $analysis = AnalysisHistory::factory()->create();
        AnalysisCost::factory()->count(2)->create([
            'analysis_id' => $analysis->id,
        ]);

        // Don't load costs
        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        // Costs should be empty or not present
        $this->assertTrue(
            !isset($data['costs']) || count($data['costs']) === 0
        );
    }

    /** @test Error message included when failed */
    public function test_error_message_included(): void
    {
        $analysis = AnalysisHistory::factory()->failed()->create([
            'error_message' => 'Connection timeout',
        ]);

        $resource = new AnalysisResource($analysis);
        $data = $resource->toArray(request());

        $this->assertEquals('Connection timeout', $data['errorMessage']);
    }
}
