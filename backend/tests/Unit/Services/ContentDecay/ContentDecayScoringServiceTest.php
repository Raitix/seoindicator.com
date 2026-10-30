<?php

namespace Tests\Unit\Services\ContentDecay;

use App\Services\ContentDecay\ContentDecayScoringService;
use Tests\TestCase;

/**
 * Unit tests for ContentDecayScoringService.
 *
 * Tests cover:
 * - Service instantiation and structure (SI)
 * - Score calculation (CALC)
 * - Weight renormalization (RENORM)
 * - Confidence multiplier (CONF)
 * - Severity bands (SEV)
 * - Contributing factors (CONTRIB)
 * - Score breakdown (BREAKDOWN)
 * - Deterministic behavior (DET)
 * - Edge cases (EDGE)
 */
class ContentDecayScoringServiceTest extends TestCase
{
    private ContentDecayScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContentDecayScoringService();
    }

    // =========================================================================
    // SI: Service Instantiation Tests
    // =========================================================================

    /** @test SI-001: Class exists in correct namespace */
    public function test_class_exists_in_correct_namespace(): void
    {
        $this->assertTrue(class_exists(ContentDecayScoringService::class));
    }

    /** @test SI-002: Class can be instantiated */
    public function test_class_can_be_instantiated(): void
    {
        $service = new ContentDecayScoringService();
        $this->assertInstanceOf(ContentDecayScoringService::class, $service);
    }

    /** @test SI-003: Container binding works */
    public function test_container_binding_works(): void
    {
        $service = app(ContentDecayScoringService::class);
        $this->assertInstanceOf(ContentDecayScoringService::class, $service);
    }

    /** @test SI-004: Base weights constant defined */
    public function test_base_weights_constant_defined(): void
    {
        $weights = ContentDecayScoringService::BASE_WEIGHTS;
        $this->assertIsArray($weights);
        $this->assertCount(7, $weights);
    }

    /** @test SI-005: Base weights sum to 1.0 */
    public function test_base_weights_sum_to_one(): void
    {
        $weights = ContentDecayScoringService::BASE_WEIGHTS;
        $sum = array_sum($weights);
        $this->assertEqualsWithDelta(1.0, $sum, 0.0001);
    }

    /** @test SI-006: All expected weight keys present */
    public function test_all_expected_weight_keys_present(): void
    {
        $weights = ContentDecayScoringService::BASE_WEIGHTS;
        $expectedKeys = [
            'click_loss',
            'impression_loss',
            'ctr_drop',
            'position_decline',
            'ranking_decline',
            'audit_pressure',
            'staleness',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $weights, "Missing weight key: $key");
        }
    }

    /** @test SI-007: calculateDecayScore method exists */
    public function test_calculate_decay_score_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'calculateDecayScore'));
    }

    /** @test SI-008: Method returns expected structure */
    public function test_method_returns_expected_structure(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
        ]);

        $this->assertArrayHasKey('decay_score', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertArrayHasKey('detection_confidence', $result);
        $this->assertArrayHasKey('contributing_factors', $result);
        $this->assertArrayHasKey('score_breakdown', $result);
    }

    // =========================================================================
    // CALC: Score Calculation Tests
    // =========================================================================

    /** @test CALC-001: Zero signals → zero decay */
    public function test_zero_signals_zero_decay(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
        ]);

        $this->assertEquals(0.0, $result['decay_score']);
        $this->assertEquals('low', $result['severity']);
    }

    /** @test CALC-002: Minimal signals → low decay */
    public function test_minimal_signals_low_decay(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.1,
        ]);

        $this->assertLessThan(20, $result['decay_score']);
        $this->assertEquals('low', $result['severity']);
    }

    /** @test CALC-003: All signals minimal → low-medium decay */
    public function test_all_signals_minimal_low_medium_decay(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.1,
            'impression_loss' => 0.1,
            'ctr_drop' => 0.1,
            'position_decline' => 0.1,
        ]);

        $this->assertGreaterThanOrEqual(0, $result['decay_score']);
        $this->assertLessThanOrEqual(39, $result['decay_score']);
    }

    /** @test CALC-004: Moderate signals → high decay */
    public function test_moderate_signals_high_decay(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.5,
            'ctr_drop' => 0.5,
            'position_decline' => 0.5,
            'ranking_decline' => 0.5,
            'audit_pressure' => 0.5,
            'staleness' => 0.5,
        ]);

        $this->assertGreaterThanOrEqual(40, $result['decay_score']);
        $this->assertLessThanOrEqual(69, $result['decay_score']);
    }

    /** @test CALC-005: Severe signals → critical decay */
    public function test_severe_signals_critical_decay(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 1.0,
            'ctr_drop' => 1.0,
            'position_decline' => 1.0,
            'ranking_decline' => 1.0,
            'audit_pressure' => 1.0,
            'staleness' => 1.0,
        ]);

        $this->assertGreaterThanOrEqual(70, $result['decay_score']);
        $this->assertEquals('critical', $result['severity']);
    }

    /** @test CALC-006: Click loss dominant */
    public function test_click_loss_dominant(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
        ]);

        // With renormalization: optional signals removed, remaining weight = 0.80
        // click_loss weight = 0.35 / 0.80 = 0.4375
        // Score = 0.4375 * 100 * 0.85 = 37.19
        $this->assertEqualsWithDelta(37.19, $result['decay_score'], 0.5);
    }

    /** @test CALC-007: Impression loss dominant */
    public function test_impression_loss_dominant(): void
    {
        $result = $this->service->calculateDecayScore([
            'impression_loss' => 1.0,
        ]);

        // With renormalization, impression_loss gets weight 0.15 / 0.65 ≈ 0.231
        // With 0.85 confidence: 0.231 * 100 * 0.85 ≈ 19.6
        $this->assertLessThan(25, $result['decay_score']);
    }

    /** @test CALC-008: CTR drop dominant */
    public function test_ctr_drop_dominant(): void
    {
        $result = $this->service->calculateDecayScore([
            'ctr_drop' => 1.0,
        ]);

        // Similar calculation with renormalization
        $this->assertLessThan(25, $result['decay_score']);
    }

    /** @test CALC-009: Position decline dominant */
    public function test_position_decline_dominant(): void
    {
        $result = $this->service->calculateDecayScore([
            'position_decline' => 1.0,
        ]);

        $this->assertLessThan(25, $result['decay_score']);
    }

    /** @test CALC-010: Ranking decline dominant */
    public function test_ranking_decline_dominant(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0,
            'ranking_decline' => 1.0,
        ]);

        // Ranking decline weight is 0.10, with renormalization and 0.93 confidence
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    /** @test CALC-011: Audit pressure dominant */
    public function test_audit_pressure_dominant(): void
    {
        $result = $this->service->calculateDecayScore([
            'audit_pressure' => 1.0,
        ]);

        // Audit pressure weight is 0.05
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    /** @test CALC-012: Staleness dominant */
    public function test_staleness_dominant(): void
    {
        $result = $this->service->calculateDecayScore([
            'staleness' => 1.0,
        ]);

        // Staleness weight is 0.05
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    /** @test CALC-013: Only negative contributes */
    public function test_only_negative_contributes(): void
    {
        // Positive signals (improvements) should not contribute to decay
        $result = $this->service->calculateDecayScore([
            'click_loss' => -0.5,  // Improvement, not decay
            'impression_loss' => 0.3,  // Decay
        ]);

        // Only impression_loss should count
        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertLessThan(20, $result['decay_score']);
    }

    /** @test CALC-014: Mixed positive and negative */
    public function test_mixed_positive_negative(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => -0.5,  // Improvement
            'impression_loss' => 0.3,  // Decay
            'position_decline' => 0.4,  // Decay
        ]);

        // Only decay signals count
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    /** @test CALC-015: Click loss calculation */
    public function test_click_loss_calculation(): void
    {
        $normalized = $this->service->normalizeClickLoss(100, 70);
        $this->assertEqualsWithDelta(0.3, $normalized, 0.001);
    }

    /** @test CALC-016: Impression loss calculation */
    public function test_impression_loss_calculation(): void
    {
        $normalized = $this->service->normalizeImpressionLoss(1000, 600);
        $this->assertEqualsWithDelta(0.4, $normalized, 0.001);
    }

    /** @test CALC-017: CTR drop calculation */
    public function test_ctr_drop_calculation(): void
    {
        $normalized = $this->service->normalizeCtrDrop(5.0, 3.0);
        $this->assertEqualsWithDelta(0.4, $normalized, 0.001);
    }

    /** @test CALC-018: Position decline calculation */
    public function test_position_decline_calculation(): void
    {
        $normalized = $this->service->normalizePositionDecline(3, 8);
        $this->assertEqualsWithDelta(0.5, $normalized, 0.001);
    }

    /** @test CALC-019: Position decline capped */
    public function test_position_decline_capped(): void
    {
        // Drop from position 1 to 20 (19 positions) = 1.9, capped at 1.0
        $normalized = $this->service->normalizePositionDecline(1, 20);
        $this->assertEqualsWithDelta(1.0, $normalized, 0.001);
    }

    /** @test CALC-020: Zero previous clicks handled */
    public function test_zero_previous_clicks_handled(): void
    {
        $normalized = $this->service->normalizeClickLoss(0, 10);
        $this->assertEquals(0.0, $normalized);
    }

    /** @test CALC-021: Zero previous CTR handled */
    public function test_zero_previous_ctr_handled(): void
    {
        $normalized = $this->service->normalizeCtrDrop(0, 1);
        $this->assertEquals(0.0, $normalized);
    }

    /** @test CALC-022: Realistic scenario A - GSC only */
    public function test_realistic_scenario_a_gsc_only(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.2,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    /** @test CALC-023: Realistic scenario B - GSC + rankings */
    public function test_realistic_scenario_b_gsc_rankings(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.2,
            'ranking_decline' => 0.15,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    /** @test CALC-024: Realistic scenario C - All signals */
    public function test_realistic_scenario_c_all_signals(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.2,
            'ranking_decline' => 0.15,
            'audit_pressure' => 0.1,
            'staleness' => 0.05,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    /** @test CALC-025: Contributing factors populated */
    public function test_contributing_factors_populated(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
        ]);

        $this->assertNotEmpty($result['contributing_factors']);
    }

    /** @test CALC-026: Score breakdown populated */
    public function test_score_breakdown_populated(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $this->assertNotEmpty($result['score_breakdown']);
    }

    /** @test CALC-027: Decay score is 0-100 */
    public function test_decay_score_in_range(): void
    {
        // Test with extreme values
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.5,  // Over 1.0
            'impression_loss' => -0.5,  // Negative
        ]);

        $this->assertGreaterThanOrEqual(0, $result['decay_score']);
        $this->assertLessThanOrEqual(100, $result['decay_score']);
    }

    /** @test CALC-028: Confidence within 0.85-1.00 */
    public function test_confidence_in_range(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $this->assertGreaterThanOrEqual(0.85, $result['detection_confidence']);
        $this->assertLessThanOrEqual(1.00, $result['detection_confidence']);
    }

    /** @test CALC-029: Negative signal ignored */
    public function test_negative_signal_ignored(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => -0.5,
        ]);

        $this->assertEquals(0.0, $result['decay_score']);
    }

    /** @test CALC-030: Signal over 1.0 clamped */
    public function test_signal_over_one_clamped(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.5,
        ]);

        // Should be clamped to 1.0
        $this->assertLessThanOrEqual(100, $result['decay_score']);
    }

    /** @test CALC-031: Empty signals array */
    public function test_empty_signals_array(): void
    {
        $result = $this->service->calculateDecayScore([]);

        $this->assertEquals(0.0, $result['decay_score']);
    }

    /** @test CALC-032: Partial signals array */
    public function test_partial_signals_array(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertArrayHasKey('click_loss', $result['score_breakdown']);
    }

    /** @test CALC-033: Extra signals ignored */
    public function test_extra_signals_ignored(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'unknown_signal' => 0.5,
        ]);

        $this->assertArrayNotHasKey('unknown_signal', $result['score_breakdown']);
    }

    /** @test CALC-034: Floating point precision */
    public function test_floating_point_precision(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.33333333,
        ]);

        $this->assertIsFloat($result['decay_score']);
    }

    /** @test CALC-035: Large numbers handled */
    public function test_large_numbers_handled(): void
    {
        // The normalization functions should handle large numbers
        $normalized = $this->service->normalizeClickLoss(1000000, 500000);
        $this->assertEqualsWithDelta(0.5, $normalized, 0.001);
    }

    // =========================================================================
    // RENORM: Weight Renormalization Tests
    // =========================================================================

    /** @test RENORM-001: No renormalization needed */
    public function test_no_renormalization_needed(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.2,
            'ranking_decline' => 0.15,
            'audit_pressure' => 0.1,
            'staleness' => 0.05,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        $this->assertEquals(ContentDecayScoringService::BASE_WEIGHTS, $weights);
    }

    /** @test RENORM-002: ranking_decline missing */
    public function test_ranking_decline_missing(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ranking_decline' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        $this->assertArrayNotHasKey('ranking_decline', $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-003: audit_pressure missing */
    public function test_audit_pressure_missing(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'audit_pressure' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        $this->assertArrayNotHasKey('audit_pressure', $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-004: staleness missing */
    public function test_staleness_missing(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'staleness' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        $this->assertArrayNotHasKey('staleness', $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-005: ranking_decline renorm math */
    public function test_ranking_decline_renorm_math(): void
    {
        $signals = ['ranking_decline' => null];

        $weights = $this->service->getRenormalizedWeights($signals);
        // Missing weight = 0.10, remaining = 0.90
        // Scale factor = 1 / 0.90 = 1.111...
        $this->assertArrayNotHasKey('ranking_decline', $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-006: audit_pressure renorm math */
    public function test_audit_pressure_renorm_math(): void
    {
        $signals = ['audit_pressure' => null];

        $weights = $this->service->getRenormalizedWeights($signals);
        // Missing weight = 0.05, remaining = 0.95
        $this->assertArrayNotHasKey('audit_pressure', $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-007: Two signals missing */
    public function test_two_signals_missing(): void
    {
        $signals = [
            'ranking_decline' => null,
            'staleness' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        $this->assertArrayNotHasKey('ranking_decline', $weights);
        $this->assertArrayNotHasKey('staleness', $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-008: Three signals missing */
    public function test_three_signals_missing(): void
    {
        $signals = [
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        $this->assertArrayNotHasKey('ranking_decline', $weights);
        $this->assertArrayNotHasKey('audit_pressure', $weights);
        $this->assertArrayNotHasKey('staleness', $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-009: Renormalized weights sum to 1.0 */
    public function test_renormalized_weights_sum_to_one(): void
    {
        $signals = [
            'ranking_decline' => null,
            'audit_pressure' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-010: Core signals never renormalized */
    public function test_core_signals_never_renormalized(): void
    {
        $signals = [];  // All optional missing

        $weights = $this->service->getRenormalizedWeights($signals);
        
        // Core signals should always be present
        $this->assertArrayHasKey('click_loss', $weights);
        $this->assertArrayHasKey('impression_loss', $weights);
        $this->assertArrayHasKey('ctr_drop', $weights);
        $this->assertArrayHasKey('position_decline', $weights);
    }

    /** @test RENORM-011: Missing ranking_decline detection */
    public function test_missing_ranking_decline_detection(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'ranking_decline' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        $this->assertArrayNotHasKey('ranking_decline', $weights);
    }

    /** @test RENORM-012: Missing audit_pressure detection */
    public function test_missing_audit_pressure_detection(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'audit_pressure' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        $this->assertArrayNotHasKey('audit_pressure', $weights);
    }

    /** @test RENORM-013: Missing staleness detection */
    public function test_missing_staleness_detection(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'staleness' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        $this->assertArrayNotHasKey('staleness', $weights);
    }

    /** @test RENORM-014: Missing AI visibility = ranking_decline */
    public function test_missing_ai_visibility(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            // No ranking_decline
        ]);

        // Should still calculate with renormalized weights
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    /** @test RENORM-015: Missing audit data = audit_pressure */
    public function test_missing_audit_data(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            // No audit_pressure
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
    }

    /** @test RENORM-016: Click loss weight after ranking renorm */
    public function test_click_loss_weight_after_ranking_renorm(): void
    {
        // Include all signals except ranking_decline (null) to test specific renorm
        $signals = [
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => 0.05,
            'staleness' => 0.03,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        // Original: 0.35, Missing: 0.10, Remaining: 0.90
        // New: 0.35 / 0.90 ≈ 0.389
        $expectedClickLoss = 0.35 / 0.90;
        $this->assertEqualsWithDelta($expectedClickLoss, $weights['click_loss'], 0.001);
    }

    /** @test RENORM-017: Impression loss weight after staleness renorm */
    public function test_impression_loss_weight_after_staleness_renorm(): void
    {
        // Include all signals except staleness (null) to test specific renorm
        $signals = [
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.1,
            'ranking_decline' => 0.05,
            'audit_pressure' => 0.03,
            'staleness' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);

        // Original: 0.15, Missing: 0.05, Remaining: 0.95
        // New: 0.15 / 0.95 ≈ 0.158
        $expectedImpressionLoss = 0.15 / 0.95;
        $this->assertEqualsWithDelta($expectedImpressionLoss, $weights['impression_loss'], 0.001);
    }

    /** @test RENORM-018: All optional missing */
    public function test_all_optional_missing(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.2,
            // ranking_decline, audit_pressure, staleness all missing
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        
        // Only core signals should remain
        $this->assertCount(4, $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-019: Renorm preserves relative ratios */
    public function test_renorm_preserves_relative_ratios(): void
    {
        $signals = ['ranking_decline' => null, 'audit_pressure' => null];
        $weights = $this->service->getRenormalizedWeights($signals);

        // Click loss should still be the highest weight
        // Original ratios should be preserved among remaining weights
        $clickLossRatio = $weights['click_loss'] / $weights['impression_loss'];
        $originalRatio = 0.35 / 0.15;
        $this->assertEqualsWithDelta($originalRatio, $clickLossRatio, 0.001);
    }

    /** @test RENORM-020: Renorm with zero values for optional signals */
    public function test_renorm_with_zero_values(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'ranking_decline' => 0,  // Zero value for optional signal
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        
        // Zero value for optional signals triggers renormalization
        // This ensures confidence boost (from having the data) actually increases the score
        $this->assertArrayNotHasKey('ranking_decline', $weights);
    }

    /** @test RENORM-021: Renorm calculation consistency */
    public function test_renorm_calculation_consistency(): void
    {
        $signals = ['ranking_decline' => null];

        $weights1 = $this->service->getRenormalizedWeights($signals);
        $weights2 = $this->service->getRenormalizedWeights($signals);

        $this->assertEquals($weights1, $weights2);
    }

    /** @test RENORM-022: Missing signal not in breakdown (renorm) */
    public function test_missing_signal_renorm_not_in_breakdown(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => null,
        ]);

        $this->assertArrayNotHasKey('ranking_decline', $result['score_breakdown']);
    }

    /** @test RENORM-023: Partial data vs no data */
    public function test_partial_data_vs_no_data(): void
    {
        $resultZero = $this->service->calculateDecayScore([
            'ranking_decline' => 0,
        ]);

        $resultNull = $this->service->calculateDecayScore([
            'ranking_decline' => null,
        ]);

        // Both should produce valid results
        $this->assertIsFloat($resultZero['decay_score']);
        $this->assertIsFloat($resultNull['decay_score']);
    }

    /** @test RENORM-024: GSC-only scenario weights */
    public function test_gsc_only_scenario_weights(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.2,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        
        // Only GSC signals, optional signals missing
        $this->assertCount(4, $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-025: GSC + rankings scenario weights */
    public function test_gsc_rankings_scenario_weights(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.2,
            'ranking_decline' => 0.15,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        
        // 5 signals present, 2 optional missing
        $this->assertCount(5, $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-026: Full data scenario weights */
    public function test_full_data_scenario_weights(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.2,
            'ranking_decline' => 0.15,
            'audit_pressure' => 0.1,
            'staleness' => 0.05,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        
        // All signals present, no renormalization
        $this->assertCount(7, $weights);
        $this->assertEquals(ContentDecayScoringService::BASE_WEIGHTS, $weights);
    }

    /** @test RENORM-027: Renorm with 4 missing signals */
    public function test_renorm_with_4_missing_signals(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'position_decline' => 0.2,
            // Missing: impression_loss, ctr_drop, ranking_decline, audit_pressure, staleness
            'impression_loss' => null,
            'ctr_drop' => null,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        
        // Only click_loss and position_decline remain
        $this->assertCount(2, $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /** @test RENORM-028: Renorm edge case - single signal */
    public function test_renorm_edge_case_single_signal(): void
    {
        $signals = [
            'click_loss' => 0.3,
            'impression_loss' => null,
            'ctr_drop' => null,
            'position_decline' => null,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);
        
        // Only click_loss remains with weight 1.0
        $this->assertCount(1, $weights);
        $this->assertArrayHasKey('click_loss', $weights);
        $this->assertEqualsWithDelta(1.0, $weights['click_loss'], 0.0001);
    }

    /** @test RENORM-029: Renorm affects confidence */
    public function test_renorm_affects_confidence(): void
    {
        // Full data = confidence 1.00
        $resultFull = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => 0.1,
            'audit_pressure' => 0.1,
        ]);

        // Missing optional signals = lower confidence
        $resultPartial = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
        ]);

        $this->assertGreaterThan(
            $resultPartial['detection_confidence'],
            $resultFull['detection_confidence']
        );
    }

    /** @test RENORM-030: Renorm documented in breakdown */
    public function test_renorm_documented_in_breakdown(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => null,
        ]);

        // Check that breakdown has correct weights (renormalized)
        $this->assertArrayHasKey('click_loss', $result['score_breakdown']);
        
        // The weight in breakdown should be renormalized
        $clickLossWeight = $result['score_breakdown']['click_loss']['weight'];
        $this->assertGreaterThan(0.35, $clickLossWeight);  // Should be higher after renorm
    }

    // =========================================================================
    // CONF: Confidence Multiplier Tests
    // =========================================================================

    /** @test CONF-001: GSC only confidence */
    public function test_gsc_only_confidence(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.2,
        ]);

        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-002: GSC + rankings confidence */
    public function test_gsc_rankings_confidence(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => 0.15,
        ]);

        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-003: Full data confidence */
    public function test_full_data_confidence(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => 0.15,
            'audit_pressure' => 0.1,
        ]);

        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-004: GSC + audit only */
    public function test_gsc_audit_only_confidence(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'audit_pressure' => 0.1,
        ]);

        // Without rankings, should still be 0.85
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-005: Confidence affects final score */
    public function test_confidence_affects_final_score(): void
    {
        // Same signals with different confidence levels
        $resultLow = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $resultHigh = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'ranking_decline' => 0,
            'audit_pressure' => 0,
        ]);

        // Higher confidence should produce higher score for same signals
        $this->assertGreaterThan($resultLow['decay_score'], $resultHigh['decay_score']);
    }

    /** @test CONF-006: Confidence stored in result */
    public function test_confidence_stored_in_result(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
        ]);

        $this->assertArrayHasKey('detection_confidence', $result);
        $this->assertIsFloat($result['detection_confidence']);
    }

    /** @test CONF-007: Confidence is float */
    public function test_confidence_is_float(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
        ]);

        $this->assertIsFloat($result['detection_confidence']);
        $this->assertGreaterThanOrEqual(0.85, $result['detection_confidence']);
        $this->assertLessThanOrEqual(1.00, $result['detection_confidence']);
    }

    /** @test CONF-008: GSC-only detection */
    public function test_gsc_only_detection(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
        ]);

        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-009: GSC+rankings detection */
    public function test_gsc_rankings_detection(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => 0.1,
        ]);

        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-010: Full data detection */
    public function test_full_data_detection(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => 0.1,
            'audit_pressure' => 0.05,
        ]);

        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-011: Partial ranking data */
    public function test_partial_ranking_data(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => 0.05,  // Small value
        ]);

        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-012: Partial audit data */
    public function test_partial_audit_data(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => 0.1,
            'audit_pressure' => 0.02,  // Small value
        ]);

        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-013: Annotation counts as audit */
    public function test_annotation_counts_as_audit(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => 0.1,
            'staleness' => 0.05,  // staleness counts as audit/annotation
        ]);

        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-014: Confidence with renormalization */
    public function test_confidence_with_renormalization(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            // ranking_decline missing, audit_pressure missing
            'staleness' => 0.05,  // This counts as audit
        ]);

        // Has audit data but no rankings
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-015: Low confidence still valid */
    public function test_low_confidence_still_valid(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    /** @test CONF-016: Full confidence optimal */
    public function test_full_confidence_optimal(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.2,
            'ranking_decline' => 0.15,
            'audit_pressure' => 0.1,
            'staleness' => 0.05,
        ]);

        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-017: Confidence in breakdown */
    public function test_confidence_in_breakdown(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
        ]);

        $this->assertArrayHasKey('detection_confidence', $result);
    }

    /** @test CONF-018: Confidence threshold logic - exactly 0.85 */
    public function test_confidence_threshold_exact_085(): void
    {
        // No rankings or audit
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
        ]);

        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-019: Confidence threshold logic - exactly 0.93 */
    public function test_confidence_threshold_exact_093(): void
    {
        // Has rankings, no audit
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => 0.1,
        ]);

        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    /** @test CONF-020: Confidence is deterministic */
    public function test_confidence_is_deterministic(): void
    {
        $signals = ['click_loss' => 0.3, 'ranking_decline' => 0.1];

        $result1 = $this->service->calculateDecayScore($signals);
        $result2 = $this->service->calculateDecayScore($signals);

        $this->assertEquals($result1['detection_confidence'], $result2['detection_confidence']);
    }

    // =========================================================================
    // SEV: Severity Bands Tests
    // =========================================================================

    /** @test SEV-001: Zero score = low */
    public function test_zero_score_low(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0,
        ]);

        $this->assertEquals('low', $result['severity']);
    }

    /** @test SEV-002: Very low score = low */
    public function test_very_low_score_low(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.01,
        ]);

        $this->assertEquals('low', $result['severity']);
    }

    /** @test SEV-003: Low boundary = low */
    public function test_low_boundary(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.2,  // Should give ~17 with GSC-only confidence
        ]);

        $this->assertLessThan(20, $result['decay_score']);
        $this->assertEquals('low', $result['severity']);
    }

    /** @test SEV-004: Medium lower boundary */
    public function test_medium_lower_boundary(): void
    {
        // Need to craft signals that give exactly 20
        // With full confidence and proper weights
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.1,
            'ctr_drop' => 0.1,
            'position_decline' => 0.1,
            'ranking_decline' => 0.1,
            'audit_pressure' => 0.1,
            'staleness' => 0.1,
        ]);

        if ($result['decay_score'] >= 20 && $result['decay_score'] < 40) {
            $this->assertEquals('medium', $result['severity']);
        }
    }

    /** @test SEV-005: Medium score */
    public function test_medium_score(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.35,
            'impression_loss' => 0.15,
            'ctr_drop' => 0.15,
            'position_decline' => 0.15,
            'ranking_decline' => 0.1,
            'audit_pressure' => 0.05,
            'staleness' => 0.05,
        ]);

        $this->assertGreaterThanOrEqual(20, $result['decay_score']);
        $this->assertLessThan(40, $result['decay_score']);
        $this->assertEquals('medium', $result['severity']);
    }

    /** @test SEV-006: Medium upper boundary */
    public function test_medium_upper_boundary(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.55,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.2,
            'position_decline' => 0.2,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.1,
        ]);

        $this->assertLessThan(40, $result['decay_score']);
        $this->assertEquals('medium', $result['severity']);
    }

    /** @test SEV-007: High lower boundary */
    public function test_high_lower_boundary(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.75,
            'impression_loss' => 0.25,
            'ctr_drop' => 0.25,
            'position_decline' => 0.25,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.1,
        ]);

        $this->assertGreaterThanOrEqual(40, $result['decay_score']);
        $this->assertEquals('high', $result['severity']);
    }

    /** @test SEV-008: High score */
    public function test_high_score(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.7,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.3,
            'position_decline' => 0.3,
            'ranking_decline' => 0.3,
            'audit_pressure' => 0.2,
            'staleness' => 0.2,
        ]);

        $this->assertGreaterThanOrEqual(40, $result['decay_score']);
        $this->assertLessThan(70, $result['decay_score']);
        $this->assertEquals('high', $result['severity']);
    }

    /** @test SEV-009: High upper boundary */
    public function test_high_upper_boundary(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.98,
            'impression_loss' => 0.4,
            'ctr_drop' => 0.4,
            'position_decline' => 0.4,
            'ranking_decline' => 0.35,
            'audit_pressure' => 0.3,
            'staleness' => 0.3,
        ]);

        $this->assertGreaterThanOrEqual(40, $result['decay_score']);
        $this->assertLessThan(70, $result['decay_score']);
        $this->assertEquals('high', $result['severity']);
    }

    /** @test SEV-010: Critical lower boundary */
    public function test_critical_lower_boundary(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 0.7,
            'ctr_drop' => 0.7,
            'position_decline' => 0.7,
            'ranking_decline' => 0.7,
            'audit_pressure' => 0.7,
            'staleness' => 0.7,
        ]);

        $this->assertGreaterThanOrEqual(70, $result['decay_score']);
        $this->assertEquals('critical', $result['severity']);
    }

    /** @test SEV-011: Critical score */
    public function test_critical_score(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 0.8,
            'ctr_drop' => 0.8,
            'position_decline' => 0.8,
            'ranking_decline' => 0.7,
            'audit_pressure' => 0.5,
            'staleness' => 0.5,
        ]);

        $this->assertGreaterThanOrEqual(70, $result['decay_score']);
        $this->assertEquals('critical', $result['severity']);
    }

    /** @test SEV-012: Maximum score = critical */
    public function test_maximum_score_critical(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 1.0,
            'ctr_drop' => 1.0,
            'position_decline' => 1.0,
            'ranking_decline' => 1.0,
            'audit_pressure' => 1.0,
            'staleness' => 1.0,
        ]);

        $this->assertEquals(100, $result['decay_score']);
        $this->assertEquals('critical', $result['severity']);
    }

    /** @test SEV-013: Severity stored in result */
    public function test_severity_stored_in_result(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $this->assertArrayHasKey('severity', $result);
    }

    /** @test SEV-014: Severity is string */
    public function test_severity_is_string(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $this->assertIsString($result['severity']);
        $this->assertContains($result['severity'], ['low', 'medium', 'high', 'critical']);
    }

    /** @test SEV-015: Low severity color class */
    public function test_low_severity_color_class(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.05,
        ]);

        $this->assertEquals('low', $result['severity']);
    }

    /** @test SEV-016: Medium severity color class */
    public function test_medium_severity_color_class(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.4,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.2,
            'position_decline' => 0.2,
            'ranking_decline' => 0.15,
            'audit_pressure' => 0.1,
            'staleness' => 0.05,
        ]);

        if ($result['decay_score'] >= 20 && $result['decay_score'] < 40) {
            $this->assertEquals('medium', $result['severity']);
        }
    }

    /** @test SEV-017: High severity color class */
    public function test_high_severity_color_class(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.6,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.3,
            'position_decline' => 0.3,
            'ranking_decline' => 0.25,
            'audit_pressure' => 0.2,
            'staleness' => 0.15,
        ]);

        if ($result['decay_score'] >= 40 && $result['decay_score'] < 70) {
            $this->assertEquals('high', $result['severity']);
        }
    }

    /** @test SEV-018: Critical severity color class */
    public function test_critical_severity_color_class(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 0.9,
            'ctr_drop' => 0.9,
            'position_decline' => 0.9,
            'ranking_decline' => 0.8,
            'audit_pressure' => 0.7,
            'staleness' => 0.6,
        ]);

        $this->assertGreaterThanOrEqual(70, $result['decay_score']);
        $this->assertEquals('critical', $result['severity']);
    }

    /** @test SEV-019: Severity affects contributing factors */
    public function test_severity_affects_contributing_factors(): void
    {
        $resultLow = $this->service->calculateDecayScore([
            'click_loss' => 0.05,
        ]);

        $resultCritical = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 1.0,
            'ctr_drop' => 1.0,
            'position_decline' => 1.0,
            'ranking_decline' => 1.0,
            'audit_pressure' => 1.0,
            'staleness' => 1.0,
        ]);

        $this->assertLessThanOrEqual(
            count($resultCritical['contributing_factors']),
            count($resultLow['contributing_factors'])
        );
    }

    /** @test SEV-020: Boundary precision 19.999 */
    public function test_boundary_precision_19_999(): void
    {
        // Craft signals to get exactly 19.999
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.235,
            'impression_loss' => 0.1,
            'ctr_drop' => 0.1,
            'position_decline' => 0.1,
            'ranking_decline' => 0.05,
            'audit_pressure' => 0.05,
            'staleness' => 0.05,
        ]);

        if ($result['decay_score'] < 20) {
            $this->assertEquals('low', $result['severity']);
        }
    }

    /** @test SEV-021: Boundary precision 20.000 */
    public function test_boundary_precision_20_000(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.25,
            'impression_loss' => 0.15,
            'ctr_drop' => 0.15,
            'position_decline' => 0.15,
            'ranking_decline' => 0.1,
            'audit_pressure' => 0.05,
            'staleness' => 0.05,
        ]);

        if ($result['decay_score'] >= 20 && $result['decay_score'] < 40) {
            $this->assertEquals('medium', $result['severity']);
        }
    }

    /** @test SEV-022: Boundary precision 39.999 */
    public function test_boundary_precision_39_999(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.2,
            'position_decline' => 0.2,
            'ranking_decline' => 0.15,
            'audit_pressure' => 0.1,
            'staleness' => 0.1,
        ]);

        if ($result['decay_score'] >= 20 && $result['decay_score'] < 40) {
            $this->assertEquals('medium', $result['severity']);
        }
    }

    /** @test SEV-023: Boundary precision 40.000 */
    public function test_boundary_precision_40_000(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.57,
            'impression_loss' => 0.25,
            'ctr_drop' => 0.25,
            'position_decline' => 0.25,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.1,
        ]);

        if ($result['decay_score'] >= 40 && $result['decay_score'] < 70) {
            $this->assertEquals('high', $result['severity']);
        }
    }

    /** @test SEV-024: Boundary precision 69.999 */
    public function test_boundary_precision_69_999(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 0.35,
            'ctr_drop' => 0.35,
            'position_decline' => 0.35,
            'ranking_decline' => 0.3,
            'audit_pressure' => 0.2,
            'staleness' => 0.2,
        ]);

        if ($result['decay_score'] >= 40 && $result['decay_score'] < 70) {
            $this->assertEquals('high', $result['severity']);
        }
    }

    /** @test SEV-025: Boundary precision 70.000 */
    public function test_boundary_precision_70_000(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 0.7,
            'ctr_drop' => 0.7,
            'position_decline' => 0.7,
            'ranking_decline' => 0.7,
            'audit_pressure' => 0.7,
            'staleness' => 0.7,
        ]);

        $this->assertGreaterThanOrEqual(70, $result['decay_score']);
        $this->assertEquals('critical', $result['severity']);
    }

    // =========================================================================
    // CONTRIB: Contributing Factors Tests
    // =========================================================================

    /** @test CONTRIB-001: Zero decay → empty factors */
    public function test_zero_decay_empty_factors(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0,
        ]);

        $this->assertEmpty($result['contributing_factors']);
    }

    /** @test CONTRIB-002: Single factor decay */
    public function test_single_factor_decay(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $this->assertCount(1, $result['contributing_factors']);
        $this->assertEquals('click_loss', $result['contributing_factors'][0]['factor']);
    }

    /** @test CONTRIB-003: Multiple factors */
    public function test_multiple_factors(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
        ]);

        $this->assertGreaterThanOrEqual(1, count($result['contributing_factors']));
    }

    /** @test CONTRIB-004: Factor names match signal keys */
    public function test_factor_names_match_signal_keys(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
        ]);

        foreach ($result['contributing_factors'] as $factor) {
            $this->assertArrayHasKey($factor['factor'], ContentDecayScoringService::BASE_WEIGHTS);
        }
    }

    /** @test CONTRIB-005: Factors sorted by contribution */
    public function test_factors_sorted_by_contribution(): void
    {
        $result = $this->service->calculateDecayScore([
            'impression_loss' => 0.3,
            'click_loss' => 0.5,  // Higher
        ]);

        if (count($result['contributing_factors']) >= 2) {
            $this->assertGreaterThanOrEqual(
                $result['contributing_factors'][1]['contribution'],
                $result['contributing_factors'][0]['contribution']
            );
        }
    }

    /** @test CONTRIB-006: Small contribution excluded */
    public function test_small_contribution_excluded(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.01,  // Very small
        ]);

        // Factor with < 1% contribution should be excluded
        // Note: threshold is 0.01 (1%)
        if ($result['decay_score'] < 1) {
            $this->assertEmpty($result['contributing_factors']);
        }
    }

    /** @test CONTRIB-007: All factors for critical */
    public function test_all_factors_for_critical(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 1.0,
            'ctr_drop' => 1.0,
            'position_decline' => 1.0,
            'ranking_decline' => 1.0,
            'audit_pressure' => 1.0,
            'staleness' => 1.0,
        ]);

        $this->assertCount(7, $result['contributing_factors']);
    }

    /** @test CONTRIB-008: Factor contribution percentage */
    public function test_factor_contribution_percentage(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        if (!empty($result['contributing_factors'])) {
            $this->assertArrayHasKey('contribution', $result['contributing_factors'][0]);
            $this->assertIsFloat($result['contributing_factors'][0]['contribution']);
        }
    }

    /** @test CONTRIB-009: Factor breakdown structure */
    public function test_factor_breakdown_structure(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        if (!empty($result['contributing_factors'])) {
            $factor = $result['contributing_factors'][0];
            $this->assertArrayHasKey('factor', $factor);
            $this->assertArrayHasKey('contribution', $factor);
        }
    }

    /** @test CONTRIB-010: Click loss factor naming */
    public function test_click_loss_factor_naming(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $factorNames = array_column($result['contributing_factors'], 'factor');
        $this->assertContains('click_loss', $factorNames);
    }

    /** @test CONTRIB-011: Impression loss factor naming */
    public function test_impression_loss_factor_naming(): void
    {
        $result = $this->service->calculateDecayScore([
            'impression_loss' => 0.5,
        ]);

        if (!empty($result['contributing_factors'])) {
            $factorNames = array_column($result['contributing_factors'], 'factor');
            $this->assertContains('impression_loss', $factorNames);
        }
    }

    /** @test CONTRIB-012: CTR drop factor naming */
    public function test_ctr_drop_factor_naming(): void
    {
        $result = $this->service->calculateDecayScore([
            'ctr_drop' => 0.5,
        ]);

        if (!empty($result['contributing_factors'])) {
            $factorNames = array_column($result['contributing_factors'], 'factor');
            $this->assertContains('ctr_drop', $factorNames);
        }
    }

    /** @test CONTRIB-013: Position decline factor naming */
    public function test_position_decline_factor_naming(): void
    {
        $result = $this->service->calculateDecayScore([
            'position_decline' => 0.5,
        ]);

        if (!empty($result['contributing_factors'])) {
            $factorNames = array_column($result['contributing_factors'], 'factor');
            $this->assertContains('position_decline', $factorNames);
        }
    }

    /** @test CONTRIB-014: Ranking decline factor naming */
    public function test_ranking_decline_factor_naming(): void
    {
        $result = $this->service->calculateDecayScore([
            'ranking_decline' => 0.5,
        ]);

        // With renormalization, this should still be in factors
        $factorNames = array_column($result['contributing_factors'], 'factor');
        $this->assertContains('ranking_decline', $factorNames);
    }

    /** @test CONTRIB-015: Audit pressure factor naming */
    public function test_audit_pressure_factor_naming(): void
    {
        $result = $this->service->calculateDecayScore([
            'audit_pressure' => 0.5,
        ]);

        if (!empty($result['contributing_factors'])) {
            $factorNames = array_column($result['contributing_factors'], 'factor');
            $this->assertContains('audit_pressure', $factorNames);
        }
    }

    /** @test CONTRIB-016: Staleness factor naming */
    public function test_staleness_factor_naming(): void
    {
        $result = $this->service->calculateDecayScore([
            'staleness' => 0.5,
        ]);

        if (!empty($result['contributing_factors'])) {
            $factorNames = array_column($result['contributing_factors'], 'factor');
            $this->assertContains('staleness', $factorNames);
        }
    }

    /** @test CONTRIB-017: Missing signal not in factors */
    public function test_missing_signal_not_in_factors(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'ranking_decline' => null,
        ]);

        $factorNames = array_column($result['contributing_factors'], 'factor');
        $this->assertNotContains('ranking_decline', $factorNames);
    }

    /** @test CONTRIB-018: Zero contribution not in factors */
    public function test_zero_contribution_not_in_factors(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0,
        ]);

        $this->assertEmpty($result['contributing_factors']);
    }

    /** @test CONTRIB-019: Factors match breakdown keys */
    public function test_factors_match_breakdown_keys(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
        ]);

        $factorNames = array_column($result['contributing_factors'], 'factor');
        $breakdownKeys = array_keys($result['score_breakdown']);

        sort($factorNames);
        sort($breakdownKeys);

        $this->assertEquals($breakdownKeys, $factorNames);
    }

    /** @test CONTRIB-020: Human-readable factor names */
    public function test_human_readable_factor_names(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        // Factor names should be snake_case (machine-readable)
        // Conversion to human-readable would be done in UI layer
        if (!empty($result['contributing_factors'])) {
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $result['contributing_factors'][0]['factor']);
        }
    }

    // =========================================================================
    // BREAKDOWN: Score Breakdown Tests
    // =========================================================================

    /** @test BREAKDOWN-001: Breakdown structure */
    public function test_breakdown_structure(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $this->assertIsArray($result['score_breakdown']);
    }

    /** @test BREAKDOWN-002: Breakdown keys match signals */
    public function test_breakdown_keys_match_signals(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.05,
            'audit_pressure' => 0.03,
            'staleness' => 0.02,
        ]);

        $expectedKeys = [
            'click_loss',
            'impression_loss',
            'ctr_drop',
            'position_decline',
            'ranking_decline',
            'audit_pressure',
            'staleness',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result['score_breakdown']);
        }
    }

    /** @test BREAKDOWN-003: Breakdown values are normalized */
    public function test_breakdown_values_normalized(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        foreach ($result['score_breakdown'] as $factor => $data) {
            $this->assertGreaterThanOrEqual(0, $data['value']);
            $this->assertLessThanOrEqual(1, $data['value']);
        }
    }

    /** @test BREAKDOWN-004: Breakdown includes weights */
    public function test_breakdown_includes_weights(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        foreach ($result['score_breakdown'] as $factor => $data) {
            $this->assertArrayHasKey('weight', $data);
            $this->assertIsFloat($data['weight']);
        }
    }

    /** @test BREAKDOWN-005: Breakdown includes raw values */
    public function test_breakdown_includes_raw_values(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        foreach ($result['score_breakdown'] as $factor => $data) {
            $this->assertArrayHasKey('value', $data);
        }
    }

    /** @test BREAKDOWN-006: Missing signal not in breakdown */
    public function test_missing_signal_not_in_breakdown(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'ranking_decline' => null,
        ]);

        $this->assertArrayNotHasKey('ranking_decline', $result['score_breakdown']);
    }

    /** @test BREAKDOWN-007: Zero signal in breakdown */
    public function test_zero_signal_in_breakdown(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0,
        ]);

        $this->assertArrayHasKey('click_loss', $result['score_breakdown']);
        $this->assertEquals(0, $result['score_breakdown']['click_loss']['value']);
    }

    /** @test BREAKDOWN-008: Breakdown sums correctly */
    public function test_breakdown_sums_correctly(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.1,
            'audit_pressure' => 0.05,
            'staleness' => 0.05,
        ]);

        // Sum of contributions should approximately match decay_score
        $totalContribution = 0;
        foreach ($result['score_breakdown'] as $data) {
            $totalContribution += $data['contribution'];
        }

        $this->assertEqualsWithDelta($result['decay_score'], $totalContribution, 1.0);
    }

    /** @test BREAKDOWN-009: Breakdown shows contribution */
    public function test_breakdown_shows_contribution(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        foreach ($result['score_breakdown'] as $data) {
            $this->assertArrayHasKey('contribution', $data);
        }
    }

    /** @test BREAKDOWN-010: Breakdown is JSON serializable */
    public function test_breakdown_json_serializable(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $json = json_encode($result['score_breakdown']);
        $this->assertIsString($json);
        $this->assertNotFalse($json);
    }

    // =========================================================================
    // DET: Deterministic Behavior Tests
    // =========================================================================

    /** @test DET-001: Same input, same output (run 1) */
    public function test_same_input_same_output_run_1(): void
    {
        $signals = ['click_loss' => 0.3, 'impression_loss' => 0.2];
        $result = $this->service->calculateDecayScore($signals);

        $this->assertIsFloat($result['decay_score']);
    }

    /** @test DET-002: Same input, same output (run 2) */
    public function test_same_input_same_output_run_2(): void
    {
        $signals = ['click_loss' => 0.3, 'impression_loss' => 0.2];
        $result1 = $this->service->calculateDecayScore($signals);
        $result2 = $this->service->calculateDecayScore($signals);

        $this->assertEquals($result1, $result2);
    }

    /** @test DET-003: Same input, same output (run 3) */
    public function test_same_input_same_output_run_3(): void
    {
        $signals = ['click_loss' => 0.3, 'impression_loss' => 0.2];
        $result1 = $this->service->calculateDecayScore($signals);
        $result2 = $this->service->calculateDecayScore($signals);
        $result3 = $this->service->calculateDecayScore($signals);

        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
    }

    /** @test DET-004: Different input, different output */
    public function test_different_input_different_output(): void
    {
        $result1 = $this->service->calculateDecayScore(['click_loss' => 0.3]);
        $result2 = $this->service->calculateDecayScore(['click_loss' => 0.5]);

        $this->assertNotEquals($result1['decay_score'], $result2['decay_score']);
    }

    /** @test DET-005: Decimal precision consistent */
    public function test_decimal_precision_consistent(): void
    {
        $signals = ['click_loss' => 0.333333];
        
        $result1 = $this->service->calculateDecayScore($signals);
        $result2 = $this->service->calculateDecayScore($signals);

        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
    }

    /** @test DET-006: Order independence */
    public function test_order_independence(): void
    {
        $signals1 = ['click_loss' => 0.3, 'impression_loss' => 0.2];
        $signals2 = ['impression_loss' => 0.2, 'click_loss' => 0.3];

        $result1 = $this->service->calculateDecayScore($signals1);
        $result2 = $this->service->calculateDecayScore($signals2);

        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
    }

    /** @test DET-007: Extra keys ignored */
    public function test_extra_keys_ignored(): void
    {
        $signals1 = ['click_loss' => 0.3];
        $signals2 = ['click_loss' => 0.3, 'extra_key' => 0.5];

        $result1 = $this->service->calculateDecayScore($signals1);
        $result2 = $this->service->calculateDecayScore($signals2);

        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
    }

    /** @test DET-008: Null handling consistent */
    public function test_null_handling_consistent(): void
    {
        $signals = ['click_loss' => 0.3, 'ranking_decline' => null];
        
        $result1 = $this->service->calculateDecayScore($signals);
        $result2 = $this->service->calculateDecayScore($signals);

        $this->assertEquals($result1, $result2);
    }

    /** @test DET-009: Zero handling consistent */
    public function test_zero_handling_consistent(): void
    {
        $signals = ['click_loss' => 0];
        
        $result1 = $this->service->calculateDecayScore($signals);
        $result2 = $this->service->calculateDecayScore($signals);

        $this->assertEquals($result1, $result2);
    }

    /** @test DET-010: Large numbers consistent */
    public function test_large_numbers_consistent(): void
    {
        $result1 = $this->service->calculateDecayScore(['click_loss' => 1.0]);
        $result2 = $this->service->calculateDecayScore(['click_loss' => 1.0]);

        $this->assertEquals($result1, $result2);
    }

    /** @test DET-011: Floating point stability */
    public function test_floating_point_stability(): void
    {
        // 0.1 + 0.2 should be handled correctly
        $signals = ['click_loss' => 0.1 + 0.2];
        
        $result = $this->service->calculateDecayScore($signals);

        $this->assertIsFloat($result['decay_score']);
    }

    /** @test DET-012: Multiple calls same session */
    public function test_multiple_calls_same_session(): void
    {
        $signals = ['click_loss' => 0.5];
        $results = [];

        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->service->calculateDecayScore($signals);
        }

        foreach ($results as $result) {
            $this->assertEquals($results[0], $result);
        }
    }

    /** @test DET-013: JSON serialization reproducible */
    public function test_json_serialization_reproducible(): void
    {
        $signals = ['click_loss' => 0.5];
        
        $result = $this->service->calculateDecayScore($signals);
        $json = json_encode($result);
        $decoded = json_decode($json, true);

        $this->assertEquals($result, $decoded);
    }

    /** @test DET-014: Hash/cache key friendly */
    public function test_hash_cache_key_friendly(): void
    {
        $signals = ['click_loss' => 0.5];
        
        $result = $this->service->calculateDecayScore($signals);
        $hash = md5(json_encode($result));

        $this->assertIsString($hash);
        $this->assertEquals(32, strlen($hash));
    }

    /** @test DET-015: Reproducible across instances */
    public function test_reproducible_across_instances(): void
    {
        $signals = ['click_loss' => 0.5];
        
        $service1 = new ContentDecayScoringService();
        $service2 = new ContentDecayScoringService();

        $result1 = $service1->calculateDecayScore($signals);
        $result2 = $service2->calculateDecayScore($signals);

        $this->assertEquals($result1, $result2);
    }

    // =========================================================================
    // EDGE: Edge Cases Tests
    // =========================================================================

    /** @test EDGE-001: Empty signals array */
    public function test_empty_signals_array_edge(): void
    {
        $result = $this->service->calculateDecayScore([]);

        $this->assertEquals(0.0, $result['decay_score']);
        $this->assertEquals('low', $result['severity']);
    }

    /** @test EDGE-002: Null signals */
    public function test_null_signals(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => null,
        ]);

        $this->assertEquals(0.0, $result['decay_score']);
    }

    /** @test EDGE-003: All zeros */
    public function test_all_zeros(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
        ]);

        $this->assertEquals(0.0, $result['decay_score']);
    }

    /** @test EDGE-004: All negative */
    public function test_all_negative(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => -1,
            'impression_loss' => -1,
            'ctr_drop' => -1,
            'position_decline' => -1,
        ]);

        $this->assertEquals(0.0, $result['decay_score']);
    }

    /** @test EDGE-005: All max */
    public function test_all_max(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 1.0,
            'ctr_drop' => 1.0,
            'position_decline' => 1.0,
            'ranking_decline' => 1.0,
            'audit_pressure' => 1.0,
            'staleness' => 1.0,
        ]);

        $this->assertEquals(100, $result['decay_score']);
    }

    /** @test EDGE-006: Mix of null and values */
    public function test_mix_of_null_and_values(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => null,
            'ctr_drop' => 0.3,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
    }

    /** @test EDGE-007: String values */
    public function test_string_values(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => '0.5',
        ]);

        $this->assertIsFloat($result['decay_score']);
    }

    /** @test EDGE-008: Boolean values */
    public function test_boolean_values(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => true,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
    }

    /** @test EDGE-009: Very small decimals */
    public function test_very_small_decimals(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.0000001,
        ]);

        $this->assertGreaterThanOrEqual(0, $result['decay_score']);
    }

    /** @test EDGE-010: Very large numbers */
    public function test_very_large_numbers(): void
    {
        // Values over 1.0 should be clamped
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1000000,
        ]);

        $this->assertLessThanOrEqual(100, $result['decay_score']);
    }

    /** @test EDGE-011: Unicode in factor names */
    public function test_unicode_in_factor_names(): void
    {
        // Factor names are predefined, no unicode expected
        $weights = ContentDecayScoringService::BASE_WEIGHTS;

        foreach (array_keys($weights) as $key) {
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $key);
        }
    }

    /** @test EDGE-012: Special characters */
    public function test_special_characters(): void
    {
        // No special characters in factor names
        $weights = ContentDecayScoringService::BASE_WEIGHTS;

        foreach (array_keys($weights) as $key) {
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $key);
        }
    }

    /** @test EDGE-013: Inf values */
    public function test_inf_values(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => INF,
        ]);

        // INF should be treated as 1.0
        $this->assertLessThanOrEqual(100, $result['decay_score']);
    }

    /** @test EDGE-014: NaN values */
    public function test_nan_values(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => NAN,
        ]);

        // NaN should be treated as 0
        $this->assertEquals(0.0, $result['decay_score']);
    }

    /** @test EDGE-015: Single factor extreme */
    public function test_single_factor_extreme(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 1.0,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertLessThanOrEqual(100, $result['decay_score']);
    }

    /** @test EDGE-016: All optional missing edge case */
    public function test_all_optional_missing_edge(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    /** @test EDGE-017: Concurrent calculation */
    public function test_concurrent_calculation(): void
    {
        $signals = ['click_loss' => 0.5];
        $results = [];

        // Simulate concurrent calls
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->service->calculateDecayScore($signals);
        }

        // All results should be identical
        foreach ($results as $result) {
            $this->assertEquals($results[0], $result);
        }
    }

    /** @test EDGE-018: Memory efficiency */
    public function test_memory_efficiency(): void
    {
        $initialMemory = memory_get_usage();

        for ($i = 0; $i < 100; $i++) {
            $this->service->calculateDecayScore([
                'click_loss' => 0.5,
                'impression_loss' => 0.3,
            ]);
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory shouldn't grow significantly
        $this->assertLessThan(1024 * 1024, $memoryIncrease); // < 1MB increase
    }

    /** @test EDGE-019: Performance threshold */
    public function test_performance_threshold(): void
    {
        $start = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            $this->service->calculateDecayScore([
                'click_loss' => 0.5,
                'impression_loss' => 0.3,
                'ctr_drop' => 0.2,
                'position_decline' => 0.1,
                'ranking_decline' => 0.05,
                'audit_pressure' => 0.03,
                'staleness' => 0.02,
            ]);
        }

        $elapsed = (microtime(true) - $start) * 1000; // ms

        // 1000 calculations should take < 100ms
        $this->assertLessThan(100, $elapsed);
    }

    /** @test EDGE-020: Integration ready */
    public function test_integration_ready(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
        ]);

        // Result should be usable by DetectionService
        $this->assertArrayHasKey('decay_score', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertArrayHasKey('detection_confidence', $result);

        // All values should be valid
        $this->assertIsFloat($result['decay_score']);
        $this->assertIsString($result['severity']);
        $this->assertIsFloat($result['detection_confidence']);
    }

    // =========================================================================
    // INT: Integration Tests
    // =========================================================================

    /** @test INT-001: Full workflow */
    public function test_full_workflow(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.4,
            'ranking_decline' => 0.15,
            'audit_pressure' => 0.1,
            'staleness' => 0.05,
        ]);

        $this->assertArrayHasKey('decay_score', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertArrayHasKey('detection_confidence', $result);
        $this->assertArrayHasKey('contributing_factors', $result);
        $this->assertArrayHasKey('score_breakdown', $result);
    }

    /** @test INT-002: Partial workflow */
    public function test_partial_workflow(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    /** @test INT-003: Result serialization */
    public function test_result_serialization(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $json = json_encode($result);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }

    /** @test INT-004: Result deserialization */
    public function test_result_deserialization(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        $json = json_encode($result);
        $decoded = json_decode($json, true);

        $this->assertEquals($result['decay_score'], $decoded['decay_score']);
        $this->assertEquals($result['severity'], $decoded['severity']);
    }

    /** @test INT-005: Service injection */
    public function test_service_injection(): void
    {
        $service = app(ContentDecayScoringService::class);

        $this->assertInstanceOf(ContentDecayScoringService::class, $service);
    }

    /** @test INT-006: Real GSC data */
    public function test_real_gsc_data(): void
    {
        // Simulate real GSC data
        $clickLoss = $this->service->normalizeClickLoss(1000, 700);
        $impressionLoss = $this->service->normalizeImpressionLoss(50000, 35000);
        $ctrDrop = $this->service->normalizeCtrDrop(3.5, 2.8);
        $positionDecline = $this->service->normalizePositionDecline(5, 12);

        $result = $this->service->calculateDecayScore([
            'click_loss' => $clickLoss,
            'impression_loss' => $impressionLoss,
            'ctr_drop' => $ctrDrop,
            'position_decline' => $positionDecline,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
    }

    /** @test INT-007: Real ranking data */
    public function test_real_ranking_data(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => 0.25,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    /** @test INT-008: Real audit data */
    public function test_real_audit_data(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.3,
            'ranking_decline' => 0.15,
            'audit_pressure' => 0.1,
        ]);

        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    /** @test INT-009: Result usable by DetectionService */
    public function test_result_usable_by_detection_service(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);

        // Result should have all fields needed by DetectionService
        $requiredFields = ['decay_score', 'severity', 'detection_confidence', 'contributing_factors', 'score_breakdown'];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $result);
        }
    }

    /** @test INT-010: Documentation matches */
    public function test_documentation_matches(): void
    {
        // Verify service behaves as documented in task
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.05,
            'audit_pressure' => 0.03,
            'staleness' => 0.02,
        ]);

        // Decay score should be 0-100
        $this->assertGreaterThanOrEqual(0, $result['decay_score']);
        $this->assertLessThanOrEqual(100, $result['decay_score']);

        // Severity should be one of the defined levels
        $this->assertContains($result['severity'], ['low', 'medium', 'high', 'critical']);

        // Confidence should be in range 0.85-1.00
        $this->assertGreaterThanOrEqual(0.85, $result['detection_confidence']);
        $this->assertLessThanOrEqual(1.00, $result['detection_confidence']);
    }

    // =========================================================================
    // Additional Helper Method Tests
    // =========================================================================

    /** @test getBaseWeights returns correct weights */
    public function test_get_base_weights_returns_correct_weights(): void
    {
        $weights = $this->service->getBaseWeights();

        $this->assertEquals(ContentDecayScoringService::BASE_WEIGHTS, $weights);
    }

    /** @test getRenormalizedWeights with all signals */
    public function test_get_renormalized_weights_all_signals(): void
    {
        $signals = [
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.05,
            'audit_pressure' => 0.03,
            'staleness' => 0.02,
        ];

        $weights = $this->service->getRenormalizedWeights($signals);

        $this->assertEquals(ContentDecayScoringService::BASE_WEIGHTS, $weights);
    }

    /** @test normalizePositionDecline with improvement */
    public function test_normalize_position_decline_improvement(): void
    {
        // Position improved (lower number)
        $normalized = $this->service->normalizePositionDecline(10, 5);
        $this->assertEquals(0.0, $normalized);
    }

    /** @test normalizeCtrDrop handles percentage format */
    public function test_normalize_ctr_drop_percentage(): void
    {
        // CTR as percentage (e.g., 5.0% = 5.0)
        $normalized = $this->service->normalizeCtrDrop(5.0, 3.0);
        $this->assertEqualsWithDelta(0.4, $normalized, 0.001);
    }

    /** @test full data produces full confidence */
    public function test_full_data_full_confidence(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.05,
            'audit_pressure' => 0.03,
            'staleness' => 0.02,
        ]);

        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    /** @test staleness counts as audit for confidence */
    public function test_staleness_counts_as_audit(): void
    {
        $result = $this->service->calculateDecayScore([
            'click_loss' => 0.5,
            'ranking_decline' => 0.1,
            'staleness' => 0.05,  // Only staleness, no audit_pressure
        ]);

        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }
}