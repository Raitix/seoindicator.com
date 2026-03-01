<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AnalysisCost;
use App\Models\AnalysisHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test suite for TASK-25-11: AnalysisCost Model
 *
 * This test validates the model for the analysis_costs table.
 *
 * Test Categories:
 * - M-001 to M-003: Model Attributes
 * - M-004 to M-006: Relationships
 * - M-007 to M-011: Scopes
 * - M-012 to M-015: Accessors
 *
 * @see TASK-25-11
 * @see SPEC-025
 * @see FEAT-025
 */
class AnalysisCostTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Category 1: Model Attributes (M-001 to M-003)
    // =========================================================================

    /**
     * M-001: Fillable attributes.
     */
    public function test_m001_fillable_attributes(): void
    {
        $model = new AnalysisCost();
        $fillable = $model->getFillable();

        $expectedFillable = [
            'analysis_id',
            'cost_type',
            'cost_units',
            'cost_currency',
            'cost_amount',
            'description',
        ];

        foreach ($expectedFillable as $attribute) {
            $this->assertContains(
                $attribute,
                $fillable,
                "{$attribute} should be fillable"
            );
        }
    }

    /**
     * M-002: Decimal cast for cost_units.
     */
    public function test_m002_decimal_cast_for_cost_units(): void
    {
        $model = new AnalysisCost();
        $casts = $model->getCasts();

        $this->assertEquals(
            'decimal:4',
            $casts['cost_units'] ?? null,
            'cost_units should be cast to decimal:4'
        );
    }

    /**
     * M-003: Decimal cast for cost_amount.
     */
    public function test_m003_decimal_cast_for_cost_amount(): void
    {
        $model = new AnalysisCost();
        $casts = $model->getCasts();

        $this->assertEquals(
            'decimal:6',
            $casts['cost_amount'] ?? null,
            'cost_amount should be cast to decimal:6'
        );
    }

    // =========================================================================
    // Category 2: Relationships (M-004 to M-006)
    // =========================================================================

    /**
     * M-004: BelongsTo analysis relationship.
     */
    public function test_m004_belongs_to_analysis_relationship(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);
        $cost = AnalysisCost::factory()->create(['analysis_id' => $analysis->id]);

        $this->assertTrue(
            $cost->analysis()->exists(),
            'Cost should belong to an analysis'
        );

        $this->assertInstanceOf(
            AnalysisHistory::class,
            $cost->analysis,
            'analysis relationship should return AnalysisHistory instance'
        );
    }

    /**
     * M-005: Relationship returns correct analysis.
     */
    public function test_m005_relationship_returns_correct_analysis(): void
    {
        $user = User::factory()->create();
        $analysis1 = AnalysisHistory::factory()->create(['user_id' => $user->id]);
        $analysis2 = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        $cost = AnalysisCost::factory()->create(['analysis_id' => $analysis1->id]);

        $this->assertEquals(
            $analysis1->id,
            $cost->analysis->id,
            'Cost should be associated with correct analysis'
        );
        $this->assertNotEquals(
            $analysis2->id,
            $cost->analysis->id,
            'Cost should not be associated with wrong analysis'
        );
    }

    /**
     * M-006: Cascade delete with analysis.
     */
    public function test_m006_cascade_delete_with_analysis(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);
        $cost = AnalysisCost::factory()->create(['analysis_id' => $analysis->id]);

        $costId = $cost->id;

        // Delete analysis
        $analysis->delete();

        // Cost should be deleted
        $this->assertDatabaseMissing('analysis_costs', ['id' => $costId]);
    }

    // =========================================================================
    // Category 3: Scopes (M-007 to M-011)
    // =========================================================================

    /**
     * M-007: Scope by analysis_id.
     */
    public function test_m007_scope_by_analysis_id(): void
    {
        $user = User::factory()->create();
        $analysis1 = AnalysisHistory::factory()->create(['user_id' => $user->id]);
        $analysis2 = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        AnalysisCost::factory()->count(3)->create(['analysis_id' => $analysis1->id]);
        AnalysisCost::factory()->count(2)->create(['analysis_id' => $analysis2->id]);

        $costs = AnalysisCost::forAnalysis($analysis1->id)->get();

        $this->assertCount(3, $costs, 'Should return only costs for analysis1');
        foreach ($costs as $cost) {
            $this->assertEquals($analysis1->id, $cost->analysis_id);
        }
    }

    /**
     * M-008: Scope by cost_type.
     */
    public function test_m008_scope_by_cost_type(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        AnalysisCost::factory()->count(3)->create([
            'analysis_id' => $analysis->id,
            'cost_type' => 'compute',
        ]);
        AnalysisCost::factory()->count(2)->create([
            'analysis_id' => $analysis->id,
            'cost_type' => 'api_call',
        ]);

        $costs = AnalysisCost::ofType('compute')->get();

        $this->assertCount(3, $costs, 'Should return only compute costs');
        foreach ($costs as $cost) {
            $this->assertEquals('compute', $cost->cost_type);
        }
    }

    /**
     * M-009: Scope by currency.
     */
    public function test_m009_scope_by_currency(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        AnalysisCost::factory()->count(3)->create([
            'analysis_id' => $analysis->id,
            'cost_currency' => 'USD',
        ]);
        AnalysisCost::factory()->eur()->count(2)->create([
            'analysis_id' => $analysis->id,
        ]);

        $costs = AnalysisCost::currency('EUR')->get();

        $this->assertCount(2, $costs, 'Should return only EUR costs');
        foreach ($costs as $cost) {
            $this->assertEquals('EUR', $cost->cost_currency);
        }
    }

    /**
     * M-010: Total cost for analysis.
     */
    public function test_m010_total_cost_for_analysis(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.050000,
        ]);
        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.030000,
        ]);
        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.020000,
        ]);

        $total = AnalysisCost::forAnalysis($analysis->id)->sum('cost_amount');

        $this->assertEquals(0.10, (float) $total, 'Total cost should be 0.10');
    }

    /**
     * M-011: Cost breakdown by type.
     */
    public function test_m011_cost_breakdown_by_type(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_type' => 'compute',
            'cost_amount' => 0.040000,
        ]);
        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_type' => 'api_call',
            'cost_amount' => 0.020000,
        ]);

        $breakdown = AnalysisCost::forAnalysis($analysis->id)
            ->selectRaw('cost_type, SUM(cost_amount) as total')
            ->groupBy('cost_type')
            ->pluck('total', 'cost_type');

        $this->assertEquals(0.04, (float) $breakdown['compute']);
        $this->assertEquals(0.02, (float) $breakdown['api_call']);
    }

    // =========================================================================
    // Category 4: Accessors (M-012 to M-015)
    // =========================================================================

    /**
     * M-012: Formatted cost amount accessor.
     */
    public function test_m012_formatted_cost_amount_accessor(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);
        $cost = AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.055000,
            'cost_currency' => 'USD',
        ]);

        $formatted = $cost->formatted_amount;

        $this->assertEquals('$0.055000', $formatted, 'Should format amount with currency symbol');
    }

    /**
     * M-013: Human readable cost type.
     */
    public function test_m013_human_readable_cost_type(): void
    {
        $cost = new AnalysisCost(['cost_type' => 'api_call']);

        $readable = $cost->cost_type_label;

        $this->assertEquals('API Call', $readable, 'Should convert snake_case to Title Case');
    }

    /**
     * M-014: Is zero cost check.
     */
    public function test_m014_is_zero_cost_check(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        $zeroCost = AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.000000,
        ]);

        $nonZeroCost = AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.050000,
        ]);

        $this->assertTrue($zeroCost->is_zero, 'Zero cost should return true');
        $this->assertFalse($nonZeroCost->is_zero, 'Non-zero cost should return false');
    }

    /**
     * M-015: Currency symbol accessor.
     */
    public function test_m015_currency_symbol_accessor(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        $usdCost = AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_currency' => 'USD',
        ]);

        $eurCost = AnalysisCost::factory()->eur()->create([
            'analysis_id' => $analysis->id,
        ]);

        $this->assertEquals('$', $usdCost->currency_symbol);
        $this->assertEquals('€', $eurCost->currency_symbol);
    }
}
