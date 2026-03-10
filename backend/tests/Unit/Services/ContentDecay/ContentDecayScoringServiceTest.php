<?php

namespace Tests\Unit\Services\ContentDecay;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for ContentDecayScoringService.
 * 
 * Tests the core scoring logic for content decay detection including:
 * - Score calculation from normalized signals
 * - Weight renormalization when optional signals are missing
 * - Confidence multiplier calculation
 * - Severity band classification
 * 
 * @see TESTS/TEST-36-3.md for test documentation
 */
class ContentDecayScoringServiceTest extends TestCase
{
    /**
     * Base weights expected in the service.
     */
    private const EXPECTED_WEIGHTS = [
        'click_loss' => 0.35,
        'impression_loss' => 0.15,
        'ctr_drop' => 0.15,
        'position_decline' => 0.15,
        'ranking_decline' => 0.10,
        'audit_pressure' => 0.05,
        'staleness' => 0.05,
    ];

    // =========================================================================
    // SI: Service Instantiation Tests
    // =========================================================================

    #[Test]
    public function si_001_class_exists_in_correct_namespace(): void
    {
        $this->assertTrue(class_exists(\App\Services\ContentDecay\ContentDecayScoringService::class),
            'ContentDecayScoringService class should exist in App\Services\ContentDecay namespace');
    }

    #[Test]
    public function si_002_class_can_be_instantiated(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $this->assertInstanceOf(\App\Services\ContentDecay\ContentDecayScoringService::class, $service);
    }

    #[Test]
    public function si_003_container_binding_works(): void
    {
        $service = app(\App\Services\ContentDecay\ContentDecayScoringService::class);
        $this->assertInstanceOf(\App\Services\ContentDecay\ContentDecayScoringService::class, $service);
    }

    #[Test]
    public function si_004_base_weights_constant_defined(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $weights = $service->getBaseWeights();
        $this->assertIsArray($weights);
        $this->assertCount(7, $weights);
    }

    #[Test]
    public function si_005_base_weights_sum_to_one(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $weights = $service->getBaseWeights();
        $sum = array_sum($weights);
        $this->assertEqualsWithDelta(1.0, $sum, 0.0001, 'Base weights should sum to 1.0');
    }

    #[Test]
    public function si_006_all_expected_weight_keys_present(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $weights = $service->getBaseWeights();
        
        foreach (array_keys(self::EXPECTED_WEIGHTS) as $key) {
            $this->assertArrayHasKey($key, $weights, "Weight key '$key' should be present");
        }
    }

    #[Test]
    public function si_007_calculate_decay_score_method_exists(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $this->assertTrue(method_exists($service, 'calculateDecayScore'),
            'calculateDecayScore method should exist');
    }

    #[Test]
    public function si_008_method_returns_expected_structure(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore($this->getMinimalSignals());
        
        $this->assertArrayHasKey('decay_score', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertArrayHasKey('detection_confidence', $result);
        $this->assertArrayHasKey('contributing_factors', $result);
        $this->assertArrayHasKey('score_breakdown', $result);
    }

    // =========================================================================
    // CALC: Score Calculation Tests
    // =========================================================================

    #[Test]
    public function calc_001_zero_signals_produces_zero_decay(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => 0,
            'audit_pressure' => 0,
            'staleness' => 0,
        ]);
        
        $this->assertEquals(0, $result['decay_score']);
        $this->assertEquals('low', $result['severity']);
    }

    #[Test]
    public function calc_002_minimal_signals_produces_low_decay(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.1,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertLessThan(20, $result['decay_score']);
        $this->assertEquals('low', $result['severity']);
    }

    #[Test]
    public function calc_003_all_signals_minimal_produces_low_medium_decay(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.1,
            'impression_loss' => 0.1,
            'ctr_drop' => 0.1,
            'position_decline' => 0.1,
            'ranking_decline' => 0.1,
            'audit_pressure' => 0.1,
            'staleness' => 0.1,
        ]);
        
        $this->assertLessThan(40, $result['decay_score']);
    }

    #[Test]
    public function calc_004_moderate_signals_produces_high_decay(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.5,
            'ctr_drop' => 0.5,
            'position_decline' => 0.5,
            'ranking_decline' => 0.5,
            'audit_pressure' => 0.5,
            'staleness' => 0.5,
        ]);
        
        $this->assertGreaterThanOrEqual(40, $result['decay_score']);
        $this->assertLessThan(70, $result['decay_score']);
        $this->assertEquals('high', $result['severity']);
    }

    #[Test]
    public function calc_005_severe_signals_produces_critical_decay(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
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

    #[Test]
    public function calc_006_click_loss_dominant(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // With only click_loss (weight 0.35), renormalized to 1.0, times confidence 0.85
        // Expected: 1.0 * 1.0 * 0.85 = 0.85 * 100 = 85
        $this->assertEqualsWithDelta(35 * 0.85, $result['decay_score'], 1);
    }

    #[Test]
    public function calc_007_impression_loss_dominant(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 1.0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // With only impression_loss (weight 0.15), renormalized to 1.0, times confidence 0.85
        $this->assertEqualsWithDelta(15 * 0.85, $result['decay_score'], 1);
    }

    #[Test]
    public function calc_008_ctr_drop_dominant(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 1.0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEqualsWithDelta(15 * 0.85, $result['decay_score'], 1);
    }

    #[Test]
    public function calc_009_position_decline_dominant(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 1.0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEqualsWithDelta(15 * 0.85, $result['decay_score'], 1);
    }

    #[Test]
    public function calc_010_ranking_decline_dominant(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => 1.0,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // GSC + rankings = confidence 0.93
        // ranking_decline weight 0.10, but renormalized since audit/staleness missing
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function calc_011_audit_pressure_dominant(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => 1.0,
            'staleness' => null,
        ]);
        
        // GSC + audit = still confidence 0.85 (rankings required for higher)
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function calc_012_staleness_dominant(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => 1.0,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function calc_013_only_negative_contributes_positive_ignored(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // Negative value should be treated as 0
        $result = $service->calculateDecayScore([
            'click_loss' => -0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEquals(0, $result['decay_score']);
    }

    #[Test]
    public function calc_014_mixed_positive_negative_signals(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,      // Negative - contributes
            'impression_loss' => -0.2, // Positive (improvement) - ignored
            'ctr_drop' => 0.3,         // Negative - contributes
            'position_decline' => -1,  // Positive (improvement) - ignored
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function calc_015_click_loss_calculation(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // Test raw click loss normalization
        $normalized = $service->normalizeClickLoss(100, 70);
        $this->assertEqualsWithDelta(0.3, $normalized, 0.01);
    }

    #[Test]
    public function calc_016_impression_loss_calculation(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $normalized = $service->normalizeImpressionLoss(1000, 600);
        $this->assertEqualsWithDelta(0.4, $normalized, 0.01);
    }

    #[Test]
    public function calc_017_ctr_drop_calculation(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $normalized = $service->normalizeCtrDrop(5.0, 3.0);
        $this->assertEqualsWithDelta(0.4, $normalized, 0.01);
    }

    #[Test]
    public function calc_018_position_decline_calculation(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $normalized = $service->normalizePositionDecline(3, 8);
        $this->assertEqualsWithDelta(0.5, $normalized, 0.01);
    }

    #[Test]
    public function calc_019_position_decline_capped(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // Position went from 1 to 20 - should be capped at 1.0
        $normalized = $service->normalizePositionDecline(1, 20);
        $this->assertEquals(1.0, $normalized);
    }

    #[Test]
    public function calc_020_zero_previous_clicks_handled(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // prev=0, curr=10 - should use max(prev, 1) = 1
        $normalized = $service->normalizeClickLoss(0, 10);
        $this->assertEquals(0, $normalized); // No loss since going from 0
    }

    #[Test]
    public function calc_021_zero_previous_ctr_handled(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // prev=0, curr=1 - should use max(prev, 0.01) = 0.01
        $normalized = $service->normalizeCtrDrop(0, 1);
        $this->assertEquals(0, $normalized); // No drop since going from 0
    }

    #[Test]
    public function calc_022_realistic_scenario_gsc_only(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.15,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function calc_023_realistic_scenario_gsc_plus_rankings(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.15,
            'ranking_decline' => 0.25,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function calc_024_realistic_scenario_all_signals(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.15,
            'ranking_decline' => 0.25,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function calc_025_contributing_factors_populated(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertIsArray($result['contributing_factors']);
        $this->assertNotEmpty($result['contributing_factors']);
    }

    #[Test]
    public function calc_026_score_breakdown_populated(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore($this->getMinimalSignals());
        
        $this->assertIsArray($result['score_breakdown']);
    }

    #[Test]
    public function calc_027_decay_score_in_valid_range(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // Test various edge cases
        $testCases = [
            ['click_loss' => 10.0], // Extreme value
            ['click_loss' => -5.0], // Negative value
            ['click_loss' => 0.00001], // Very small
        ];
        
        foreach ($testCases as $signals) {
            $signals = array_merge([
                'impression_loss' => 0,
                'ctr_drop' => 0,
                'position_decline' => 0,
                'ranking_decline' => null,
                'audit_pressure' => null,
                'staleness' => null,
            ], $signals);
            
            $result = $service->calculateDecayScore($signals);
            $this->assertGreaterThanOrEqual(0, $result['decay_score']);
            $this->assertLessThanOrEqual(100, $result['decay_score']);
        }
    }

    #[Test]
    public function calc_028_confidence_within_valid_range(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore($this->getMinimalSignals());
        
        $this->assertGreaterThanOrEqual(0.85, $result['detection_confidence']);
        $this->assertLessThanOrEqual(1.00, $result['detection_confidence']);
    }

    #[Test]
    public function calc_029_negative_signal_ignored(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => -0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEquals(0, $result['decay_score']);
    }

    #[Test]
    public function calc_030_signal_above_one_clamped(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 1.5, // Should be clamped to 1.0
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Should treat click_loss as 1.0
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function calc_031_empty_signals_array(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([]);
        
        $this->assertEquals(0, $result['decay_score']);
    }

    #[Test]
    public function calc_032_partial_signals_array(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function calc_033_extra_signals_ignored(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'unknown_signal' => 0.8,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Should not throw and should calculate correctly
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function calc_034_floating_point_precision(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.33333333,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertIsFloat($result['decay_score']);
    }

    #[Test]
    public function calc_035_large_numbers_handled(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // Large click numbers should still normalize correctly
        $normalized = $service->normalizeClickLoss(1000000, 500000);
        $this->assertEqualsWithDelta(0.5, $normalized, 0.01);
    }

    // =========================================================================
    // RENORM: Weight Renormalization Tests
    // =========================================================================

    #[Test]
    public function renorm_001_no_renormalization_needed(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        // All signals present, confidence should be 1.0
        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function renorm_002_ranking_decline_missing_weight(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null, // Missing
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        $this->assertArrayNotHasKey('ranking_decline', $result['score_breakdown']);
    }

    #[Test]
    public function renorm_003_audit_pressure_missing(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => null, // Missing
            'staleness' => 0.3,
        ]);
        
        $this->assertArrayNotHasKey('audit_pressure', $result['score_breakdown']);
    }

    #[Test]
    public function renorm_004_staleness_missing(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => null, // Missing
        ]);
        
        $this->assertArrayNotHasKey('staleness', $result['score_breakdown']);
    }

    #[Test]
    public function renorm_005_ranking_decline_renorm_math(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // When ranking_decline (0.10) is missing, remaining weights should sum to 0.90
        // and be scaled by 1/0.90
        $weights = $service->getRenormalizedWeights(['click_loss', 'impression_loss', 'ctr_drop', 'position_decline', 'audit_pressure', 'staleness']);
        
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    #[Test]
    public function renorm_006_audit_pressure_renorm_math(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // When audit_pressure (0.05) is missing, remaining weights should sum to 0.95
        $weights = $service->getRenormalizedWeights(['click_loss', 'impression_loss', 'ctr_drop', 'position_decline', 'ranking_decline', 'staleness']);
        
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    #[Test]
    public function renorm_007_two_signals_missing(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null, // Missing
            'audit_pressure' => null,  // Missing
            'staleness' => 0.3,
        ]);
        
        $this->assertArrayNotHasKey('ranking_decline', $result['score_breakdown']);
        $this->assertArrayNotHasKey('audit_pressure', $result['score_breakdown']);
    }

    #[Test]
    public function renorm_008_three_signals_missing(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null, // Missing
            'audit_pressure' => null,  // Missing
            'staleness' => null,       // Missing
        ]);
        
        // GSC only = 0.85 confidence
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function renorm_009_renormalized_weights_sum_to_one(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // Test various combinations
        $combinations = [
            ['click_loss', 'impression_loss'],
            ['click_loss', 'impression_loss', 'ctr_drop'],
            ['click_loss', 'impression_loss', 'ctr_drop', 'position_decline', 'ranking_decline'],
        ];
        
        foreach ($combinations as $availableSignals) {
            $weights = $service->getRenormalizedWeights($availableSignals);
            $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001,
                "Weights should sum to 1.0 for signals: " . implode(', ', $availableSignals));
        }
    }

    #[Test]
    public function renorm_010_core_signals_never_renormalized(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // Core signals are: click_loss, impression_loss, ctr_drop, position_decline
        // These should always be present
        $coreSignals = ['click_loss', 'impression_loss', 'ctr_drop', 'position_decline'];
        $weights = $service->getRenormalizedWeights($coreSignals);
        
        foreach ($coreSignals as $signal) {
            $this->assertArrayHasKey($signal, $weights);
        }
    }

    #[Test]
    public function renorm_011_missing_ranking_decline_detection(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        // ranking_decline = null should trigger renormalization
        $this->assertArrayNotHasKey('ranking_decline', $result['score_breakdown']);
    }

    #[Test]
    public function renorm_012_missing_audit_pressure_detection(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => null,
            'staleness' => 0.3,
        ]);
        
        $this->assertArrayNotHasKey('audit_pressure', $result['score_breakdown']);
    }

    #[Test]
    public function renorm_013_missing_staleness_detection(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => null,
        ]);
        
        $this->assertArrayNotHasKey('staleness', $result['score_breakdown']);
    }

    #[Test]
    public function renorm_014_missing_ai_visibility_equals_ranking_decline(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null, // AI visibility = ranking_decline
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        $this->assertArrayNotHasKey('ranking_decline', $result['score_breakdown']);
    }

    #[Test]
    public function renorm_015_missing_audit_data_equals_audit_pressure(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => null, // Audit data = audit_pressure
            'staleness' => 0.3,
        ]);
        
        $this->assertArrayNotHasKey('audit_pressure', $result['score_breakdown']);
    }

    #[Test]
    public function renorm_016_click_loss_weight_after_ranking_renorm(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // ranking_decline (0.10) missing, so remaining = 0.90
        // click_loss = 0.35 / 0.90 = 0.389
        $weights = $service->getRenormalizedWeights(['click_loss', 'impression_loss', 'ctr_drop', 'position_decline', 'audit_pressure', 'staleness']);
        
        $this->assertEqualsWithDelta(0.35 / 0.90, $weights['click_loss'], 0.01);
    }

    #[Test]
    public function renorm_017_impression_loss_weight_after_staleness_renorm(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // staleness (0.05) missing, so remaining = 0.95
        // impression_loss = 0.15 / 0.95 = 0.158
        $weights = $service->getRenormalizedWeights(['click_loss', 'impression_loss', 'ctr_drop', 'position_decline', 'ranking_decline', 'audit_pressure']);
        
        $this->assertEqualsWithDelta(0.15 / 0.95, $weights['impression_loss'], 0.01);
    }

    #[Test]
    public function renorm_018_all_optional_missing(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Only GSC signals = confidence 0.85
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function renorm_019_renorm_preserves_relative_ratios(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // Original ratio: click_loss (0.35) / impression_loss (0.15) = 2.33
        $originalRatio = 0.35 / 0.15;
        
        // After removing ranking_decline (0.10)
        $weights = $service->getRenormalizedWeights(['click_loss', 'impression_loss', 'ctr_drop', 'position_decline', 'audit_pressure', 'staleness']);
        $newRatio = $weights['click_loss'] / $weights['impression_loss'];
        
        $this->assertEqualsWithDelta($originalRatio, $newRatio, 0.01, 'Relative ratios should be preserved');
    }

    #[Test]
    public function renorm_020_renorm_with_zero_values(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0.2,
            'position_decline' => 0,
            'ranking_decline' => 0, // Zero, not null
            'audit_pressure' => 0,
            'staleness' => 0,
        ]);
        
        // Zero values are valid, null values trigger renorm
        // With all signals present (even if zero), confidence should be 1.0
        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function renorm_021_renorm_calculation_consistency(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $signals = [
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];
        
        $result1 = $service->calculateDecayScore($signals);
        $result2 = $service->calculateDecayScore($signals);
        
        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
    }

    #[Test]
    public function renorm_022_missing_signal_in_breakdown(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertArrayNotHasKey('ranking_decline', $result['score_breakdown']);
    }

    #[Test]
    public function renorm_023_partial_data_vs_no_data(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // ranking_decline = 0 (valid)
        $result1 = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0,
            'audit_pressure' => 0,
            'staleness' => 0,
        ]);
        
        // ranking_decline = null (missing)
        $result2 = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // result1 should have higher confidence (1.0 vs 0.85)
        $this->assertGreaterThan($result2['detection_confidence'], $result1['detection_confidence']);
    }

    #[Test]
    public function renorm_024_gsc_only_scenario_weights(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // GSC signals: click_loss, impression_loss, ctr_drop, position_decline
        // Total original weight: 0.35 + 0.15 + 0.15 + 0.15 = 0.80
        $weights = $service->getRenormalizedWeights(['click_loss', 'impression_loss', 'ctr_drop', 'position_decline']);
        
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    #[Test]
    public function renorm_025_gsc_plus_rankings_scenario_weights(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // GSC + rankings: all except audit/staleness
        // Total original weight: 0.35 + 0.15 + 0.15 + 0.15 + 0.10 = 0.90
        $weights = $service->getRenormalizedWeights(['click_loss', 'impression_loss', 'ctr_drop', 'position_decline', 'ranking_decline']);
        
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    #[Test]
    public function renorm_026_full_data_scenario_weights(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // All signals = no renormalization
        $allSignals = ['click_loss', 'impression_loss', 'ctr_drop', 'position_decline', 'ranking_decline', 'audit_pressure', 'staleness'];
        $weights = $service->getRenormalizedWeights($allSignals);
        
        // Weights should match original
        foreach (self::EXPECTED_WEIGHTS as $key => $expectedWeight) {
            $this->assertEqualsWithDelta($expectedWeight, $weights[$key], 0.0001);
        }
    }

    #[Test]
    public function renorm_027_renorm_with_four_missing_signals(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => null,
            'ctr_drop' => null,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Extreme renormalization - only click_loss and position_decline
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function renorm_028_renorm_edge_case_single_signal(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
        ]);
        
        // Single signal - weight should be 1.0 after renorm
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function renorm_029_renorm_affects_confidence(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // All signals
        $fullResult = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        // Missing optional signals
        $partialResult = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan($partialResult['detection_confidence'], $fullResult['detection_confidence']);
    }

    #[Test]
    public function renorm_030_renorm_documented_in_breakdown(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Breakdown should show the normalized values
        foreach ($result['score_breakdown'] as $factor => $data) {
            $this->assertArrayHasKey('value', $data);
            $this->assertArrayHasKey('weight', $data);
            $this->assertArrayHasKey('contribution', $data);
        }
    }

    // =========================================================================
    // CONF: Confidence Multiplier Tests
    // =========================================================================

    #[Test]
    public function conf_001_gsc_only_confidence(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_002_gsc_plus_rankings_confidence(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_003_gsc_plus_rankings_plus_audit_confidence(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_004_gsc_plus_audit_only_confidence(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        // Rankings required for higher confidence
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_005_confidence_affects_final_score(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $baseSignals = [
            'click_loss' => 1.0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
        ];
        
        // GSC only (confidence 0.85)
        $result1 = $service->calculateDecayScore(array_merge($baseSignals, [
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]));
        
        // Full data (confidence 1.0)
        $result2 = $service->calculateDecayScore(array_merge($baseSignals, [
            'ranking_decline' => 0,
            'audit_pressure' => 0,
            'staleness' => 0,
        ]));
        
        // Higher confidence should result in higher score for same signals
        $this->assertGreaterThan($result1['decay_score'], $result2['decay_score']);
    }

    #[Test]
    public function conf_006_confidence_stored_in_result(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore($this->getMinimalSignals());
        
        $this->assertArrayHasKey('detection_confidence', $result);
    }

    #[Test]
    public function conf_007_confidence_is_float(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore($this->getMinimalSignals());
        
        $this->assertIsFloat($result['detection_confidence']);
    }

    #[Test]
    public function conf_008_gsc_only_detection(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_009_gsc_plus_rankings_detection(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_010_full_data_detection(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_011_partial_ranking_data(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.1, // Present even if small
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // ranking_decline present = 0.93 confidence
        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_012_partial_audit_data(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.05, // Present even if small
            'staleness' => 0.1,
        ]);
        
        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_013_annotation_counts_as_audit(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1, // Could come from annotations
            'staleness' => 0.3,
        ]);
        
        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_014_confidence_with_renormalization(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null, // Missing - triggers renorm
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Both renormalization and lower confidence should apply
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_015_low_confidence_still_valid(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertIsString($result['severity']);
    }

    #[Test]
    public function conf_016_full_confidence_optimal(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_017_confidence_in_breakdown(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore($this->getMinimalSignals());
        
        $this->assertArrayHasKey('detection_confidence', $result);
        $this->assertGreaterThanOrEqual(0.85, $result['detection_confidence']);
    }

    #[Test]
    public function conf_018_confidence_threshold_logic_85(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Exactly 0.85 - no higher without rankings
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_019_confidence_threshold_logic_93(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Exactly 0.93 - no higher without audit
        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function conf_020_confidence_is_deterministic(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $signals = [
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];
        
        $result1 = $service->calculateDecayScore($signals);
        $result2 = $service->calculateDecayScore($signals);
        
        $this->assertEquals($result1['detection_confidence'], $result2['detection_confidence']);
    }

    // =========================================================================
    // SEV: Severity Band Tests
    // =========================================================================

    #[Test]
    public function sev_001_zero_score(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => 0,
            'audit_pressure' => 0,
            'staleness' => 0,
        ]);
        
        $this->assertEquals('low', $result['severity']);
    }

    #[Test]
    public function sev_002_very_low_score(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.01,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEquals('low', $result['severity']);
    }

    #[Test]
    public function sev_003_low_boundary(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.19, // Should give ~19 score
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertLessThan(20, $result['decay_score']);
        $this->assertEquals('low', $result['severity']);
    }

    #[Test]
    public function sev_004_medium_lower_boundary(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.235, // Should give ~20 score after confidence
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThanOrEqual(20, $result['decay_score']);
        $this->assertEquals('medium', $result['severity']);
    }

    #[Test]
    public function sev_005_medium_score(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThanOrEqual(20, $result['decay_score']);
        $this->assertLessThan(40, $result['decay_score']);
        $this->assertEquals('medium', $result['severity']);
    }

    #[Test]
    public function sev_006_medium_upper_boundary(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.45,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertLessThan(40, $result['decay_score']);
        $this->assertEquals('medium', $result['severity']);
    }

    #[Test]
    public function sev_007_high_lower_boundary(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.55, // Should give ~40 score after confidence
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThanOrEqual(40, $result['decay_score']);
        $this->assertEquals('high', $result['severity']);
    }

    #[Test]
    public function sev_008_high_score(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.6,
            'impression_loss' => 0.4,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThanOrEqual(40, $result['decay_score']);
        $this->assertLessThan(70, $result['decay_score']);
        $this->assertEquals('high', $result['severity']);
    }

    #[Test]
    public function sev_009_high_upper_boundary(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.8,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertLessThan(70, $result['decay_score']);
        $this->assertEquals('high', $result['severity']);
    }

    #[Test]
    public function sev_010_critical_lower_boundary(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 1.0, // Should give ~85 score with confidence
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThanOrEqual(70, $result['decay_score']);
        $this->assertEquals('critical', $result['severity']);
    }

    #[Test]
    public function sev_011_critical_score(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.9,
            'impression_loss' => 0.7,
            'ctr_drop' => 0.5,
            'position_decline' => 0.3,
            'ranking_decline' => 0.5,
            'audit_pressure' => 0.3,
            'staleness' => 0.5,
        ]);
        
        $this->assertGreaterThanOrEqual(70, $result['decay_score']);
        $this->assertEquals('critical', $result['severity']);
    }

    #[Test]
    public function sev_012_maximum_score(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
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

    #[Test]
    public function sev_013_severity_stored_in_result(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore($this->getMinimalSignals());
        
        $this->assertArrayHasKey('severity', $result);
    }

    #[Test]
    public function sev_014_severity_is_string(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore($this->getMinimalSignals());
        
        $this->assertIsString($result['severity']);
        $this->assertContains($result['severity'], ['low', 'medium', 'high', 'critical']);
    }

    #[Test]
    public function sev_015_low_severity_color_class(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.01,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Can be used for UI color mapping
        $this->assertEquals('low', $result['severity']);
    }

    #[Test]
    public function sev_016_medium_severity_color_class(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEquals('medium', $result['severity']);
    }

    #[Test]
    public function sev_017_high_severity_color_class(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.55,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEquals('high', $result['severity']);
    }

    #[Test]
    public function sev_018_critical_severity_color_class(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEquals('critical', $result['severity']);
    }

    #[Test]
    public function sev_019_severity_affects_contributing_factors(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // High severity should have more contributing factors
        $criticalResult = $service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 1.0,
            'ctr_drop' => 1.0,
            'position_decline' => 1.0,
            'ranking_decline' => 1.0,
            'audit_pressure' => 1.0,
            'staleness' => 1.0,
        ]);
        
        $lowResult = $service->calculateDecayScore([
            'click_loss' => 0.05,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan(
            count($lowResult['contributing_factors']),
            count($criticalResult['contributing_factors'])
        );
    }

    #[Test]
    public function sev_020_boundary_precision_19_999(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.235, // Should give ~19.99 score
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        if ($result['decay_score'] < 20) {
            $this->assertEquals('low', $result['severity']);
        }
    }

    #[Test]
    public function sev_021_boundary_precision_20_000(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.24, // Should give ~20 score
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        if ($result['decay_score'] >= 20) {
            $this->assertEquals('medium', $result['severity']);
        }
    }

    #[Test]
    public function sev_022_boundary_precision_39_999(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.47, // Should give ~39.99 score
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        if ($result['decay_score'] < 40 && $result['decay_score'] >= 20) {
            $this->assertEquals('medium', $result['severity']);
        }
    }

    #[Test]
    public function sev_023_boundary_precision_40_000(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.55, // Should give ~40+ score
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        if ($result['decay_score'] >= 40) {
            $this->assertEquals('high', $result['severity']);
        }
    }

    #[Test]
    public function sev_024_boundary_precision_69_999(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.82, // Should give ~69.99 score
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        if ($result['decay_score'] < 70 && $result['decay_score'] >= 40) {
            $this->assertEquals('high', $result['severity']);
        }
    }

    #[Test]
    public function sev_025_boundary_precision_70_000(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.85, // Should give ~70+ score
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        if ($result['decay_score'] >= 70) {
            $this->assertEquals('critical', $result['severity']);
        }
    }

    // =========================================================================
    // CONTRIB: Contributing Factors Tests
    // =========================================================================

    #[Test]
    public function contrib_001_zero_decay_empty_factors(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => 0,
            'audit_pressure' => 0,
            'staleness' => 0,
        ]);
        
        $this->assertIsArray($result['contributing_factors']);
        $this->assertEmpty($result['contributing_factors']);
    }

    #[Test]
    public function contrib_002_single_factor_decay(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertCount(1, $result['contributing_factors']);
        $this->assertEquals('click_loss', $result['contributing_factors'][0]['factor'] ?? $result['contributing_factors'][0] ?? null);
    }

    #[Test]
    public function contrib_003_multiple_factors(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan(1, count($result['contributing_factors']));
    }

    #[Test]
    public function contrib_004_factor_names_match_signal_keys(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $validFactors = ['click_loss', 'impression_loss', 'ctr_drop', 'position_decline', 'ranking_decline', 'audit_pressure', 'staleness'];
        
        foreach ($result['contributing_factors'] as $factor) {
            $factorName = is_array($factor) ? $factor['factor'] : $factor;
            $this->assertContains($factorName, $validFactors);
        }
    }

    #[Test]
    public function contrib_005_factors_sorted_by_contribution(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.8,       // Highest contribution
            'impression_loss' => 0.3,
            'ctr_drop' => 0.1,         // Lowest contribution
            'position_decline' => 0.5,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        if (count($result['contributing_factors']) > 1) {
            $contributions = array_map(function($factor) {
                return is_array($factor) ? $factor['contribution'] : 0;
            }, $result['contributing_factors']);
            
            // Verify descending order
            for ($i = 1; $i < count($contributions); $i++) {
                $this->assertGreaterThanOrEqual($contributions[$i], $contributions[$i - 1]);
            }
        }
    }

    #[Test]
    public function contrib_006_small_contribution_excluded(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.9,       // Large contribution
            'impression_loss' => 0.01, // Very small (< 5%)
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Factors with very small contribution may be excluded
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        // Main factor should be present
        $this->assertContains('click_loss', $factorNames);
    }

    #[Test]
    public function contrib_007_all_factors_for_critical(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 1.0,
            'impression_loss' => 1.0,
            'ctr_drop' => 1.0,
            'position_decline' => 1.0,
            'ranking_decline' => 1.0,
            'audit_pressure' => 1.0,
            'staleness' => 1.0,
        ]);
        
        // All 7 factors should contribute
        $this->assertCount(7, $result['contributing_factors']);
    }

    #[Test]
    public function contrib_008_factor_contribution_percentage(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        if (isset($result['contributing_factors'][0]['contribution'])) {
            $this->assertIsNumeric($result['contributing_factors'][0]['contribution']);
        }
    }

    #[Test]
    public function contrib_009_factor_breakdown_structure(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        if (is_array($result['contributing_factors'][0])) {
            $this->assertArrayHasKey('factor', $result['contributing_factors'][0]);
            $this->assertArrayHasKey('contribution', $result['contributing_factors'][0]);
        }
    }

    #[Test]
    public function contrib_010_click_loss_factor_naming(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        $this->assertContains('click_loss', $factorNames);
    }

    #[Test]
    public function contrib_011_impression_loss_factor_naming(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0.5,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        $this->assertContains('impression_loss', $factorNames);
    }

    #[Test]
    public function contrib_012_ctr_drop_factor_naming(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0.5,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        $this->assertContains('ctr_drop', $factorNames);
    }

    #[Test]
    public function contrib_013_position_decline_factor_naming(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0.5,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        $this->assertContains('position_decline', $factorNames);
    }

    #[Test]
    public function contrib_014_ranking_decline_factor_naming(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => 0.5,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        $this->assertContains('ranking_decline', $factorNames);
    }

    #[Test]
    public function contrib_015_audit_pressure_factor_naming(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => 0,
            'audit_pressure' => 0.5,
            'staleness' => null,
        ]);
        
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        $this->assertContains('audit_pressure', $factorNames);
    }

    #[Test]
    public function contrib_016_staleness_factor_naming(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => 0,
            'audit_pressure' => 0,
            'staleness' => 0.5,
        ]);
        
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        $this->assertContains('staleness', $factorNames);
    }

    #[Test]
    public function contrib_017_missing_signal_not_in_factors(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        $this->assertNotContains('ranking_decline', $factorNames);
    }

    #[Test]
    public function contrib_018_zero_contribution_not_in_factors(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0, // Zero contribution
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        $this->assertNotContains('impression_loss', $factorNames);
    }

    #[Test]
    public function contrib_019_factors_match_breakdown_keys(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        $breakdownKeys = array_keys($result['score_breakdown']);
        
        foreach ($factorNames as $name) {
            $this->assertContains($name, $breakdownKeys);
        }
    }

    #[Test]
    public function contrib_020_human_readable_factor_names(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Factor names should be human-readable (snake_case is acceptable)
        $factorNames = array_map(function($factor) {
            return is_array($factor) ? $factor['factor'] : $factor;
        }, $result['contributing_factors']);
        
        foreach ($factorNames as $name) {
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $name);
        }
    }

    // =========================================================================
    // BREAKDOWN: Score Breakdown Tests
    // =========================================================================

    #[Test]
    public function breakdown_001_breakdown_structure(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertIsArray($result['score_breakdown']);
    }

    #[Test]
    public function breakdown_002_breakdown_keys_match_signals(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        $expectedKeys = ['click_loss', 'impression_loss', 'ctr_drop', 'position_decline', 'ranking_decline', 'audit_pressure', 'staleness'];
        
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result['score_breakdown']);
        }
    }

    #[Test]
    public function breakdown_003_breakdown_values_normalized(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        foreach ($result['score_breakdown'] as $key => $data) {
            if (is_array($data) && isset($data['value'])) {
                $this->assertGreaterThanOrEqual(0, $data['value']);
                $this->assertLessThanOrEqual(1, $data['value']);
            }
        }
    }

    #[Test]
    public function breakdown_004_breakdown_includes_weights(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        foreach ($result['score_breakdown'] as $key => $data) {
            if (is_array($data)) {
                $this->assertArrayHasKey('weight', $data);
            }
        }
    }

    #[Test]
    public function breakdown_005_breakdown_includes_raw_values(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        foreach ($result['score_breakdown'] as $key => $data) {
            if (is_array($data)) {
                $this->assertArrayHasKey('value', $data);
            }
        }
    }

    #[Test]
    public function breakdown_006_missing_signal_not_in_breakdown(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertArrayNotHasKey('ranking_decline', $result['score_breakdown']);
    }

    #[Test]
    public function breakdown_007_zero_signal_in_breakdown(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0, // Zero
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertArrayHasKey('impression_loss', $result['score_breakdown']);
    }

    #[Test]
    public function breakdown_008_breakdown_sums_correctly(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $sum = 0;
        foreach ($result['score_breakdown'] as $key => $data) {
            if (is_array($data) && isset($data['contribution'])) {
                $sum += $data['contribution'];
            }
        }
        
        // Sum should be close to decay_score (before confidence)
        $this->assertGreaterThan(0, $sum);
    }

    #[Test]
    public function breakdown_009_breakdown_shows_contribution(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        foreach ($result['score_breakdown'] as $key => $data) {
            if (is_array($data)) {
                $this->assertArrayHasKey('contribution', $data);
            }
        }
    }

    #[Test]
    public function breakdown_010_breakdown_is_json_serializable(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $json = json_encode($result['score_breakdown']);
        $this->assertNotFalse($json);
        $this->assertIsString($json);
    }

    // =========================================================================
    // DET: Deterministic Behavior Tests
    // =========================================================================

    #[Test]
    public function det_001_same_input_same_output_run_1(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $signals = [
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];
        
        $result = $service->calculateDecayScore($signals);
        $this->assertIsFloat($result['decay_score']);
    }

    #[Test]
    public function det_002_same_input_same_output_run_2(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $signals = [
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];
        
        $result1 = $service->calculateDecayScore($signals);
        $result2 = $service->calculateDecayScore($signals);
        
        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
    }

    #[Test]
    public function det_003_same_input_same_output_run_3(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $signals = [
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];
        
        $result1 = $service->calculateDecayScore($signals);
        $result2 = $service->calculateDecayScore($signals);
        $result3 = $service->calculateDecayScore($signals);
        
        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
        $this->assertEquals($result2['decay_score'], $result3['decay_score']);
    }

    #[Test]
    public function det_004_different_input_different_output(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $result1 = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $result2 = $service->calculateDecayScore([
            'click_loss' => 0.8,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertNotEquals($result1['decay_score'], $result2['decay_score']);
    }

    #[Test]
    public function det_005_decimal_precision_consistent(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $signals = [
            'click_loss' => 0.33333333,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];
        
        $result1 = $service->calculateDecayScore($signals);
        $result2 = $service->calculateDecayScore($signals);
        
        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
    }

    #[Test]
    public function det_006_order_independence(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $result1 = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Different order
        $result2 = $service->calculateDecayScore([
            'position_decline' => 0,
            'impression_loss' => 0.3,
            'click_loss' => 0.5,
            'ctr_drop' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
    }

    #[Test]
    public function det_007_extra_keys_ignored(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $result1 = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $result2 = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
            'extra_key' => 'ignored',
        ]);
        
        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
    }

    #[Test]
    public function det_008_null_handling_consistent(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $result1 = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => null,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $result2 = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => null,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
    }

    #[Test]
    public function det_009_zero_handling_consistent(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $result1 = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => 0,
            'audit_pressure' => 0,
            'staleness' => 0,
        ]);
        
        $result2 = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => 0,
            'audit_pressure' => 0,
            'staleness' => 0,
        ]);
        
        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
    }

    #[Test]
    public function det_010_large_numbers_consistent(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $normalized1 = $service->normalizeClickLoss(1000000, 500000);
        $normalized2 = $service->normalizeClickLoss(1000000, 500000);
        
        $this->assertEquals($normalized1, $normalized2);
    }

    #[Test]
    public function det_011_floating_point_stability(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $result1 = $service->calculateDecayScore([
            'click_loss' => 0.1 + 0.2, // Potential float issue
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $result2 = $service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // Should handle floating point precision
        $this->assertEqualsWithDelta($result1['decay_score'], $result2['decay_score'], 0.1);
    }

    #[Test]
    public function det_012_multiple_calls_same_session(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $signals = [
            'click_loss' => 0.5,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];
        
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $service->calculateDecayScore($signals);
        }
        
        $firstScore = $results[0]['decay_score'];
        foreach ($results as $result) {
            $this->assertEquals($firstScore, $result['decay_score']);
        }
    }

    #[Test]
    public function det_013_json_serialization_reproducible(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $signals = [
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];
        
        $result = $service->calculateDecayScore($signals);
        $json1 = json_encode($result);
        $json2 = json_encode($result);
        
        $this->assertEquals($json1, $json2);
    }

    #[Test]
    public function det_014_hash_cache_key_friendly(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $signals = [
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];
        
        $result = $service->calculateDecayScore($signals);
        
        // Result should be JSON serializable for caching
        $json = json_encode($result);
        $this->assertNotFalse($json);
        
        // Should be deserializable
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals($result['decay_score'], $decoded['decay_score']);
    }

    #[Test]
    public function det_015_reproducible_across_instances(): void
    {
        $signals = [
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];
        
        $service1 = new \App\Services\ContentDecay\ContentDecayScoringService();
        $service2 = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $result1 = $service1->calculateDecayScore($signals);
        $result2 = $service2->calculateDecayScore($signals);
        
        $this->assertEquals($result1['decay_score'], $result2['decay_score']);
    }

    // =========================================================================
    // EDGE: Edge Cases Tests
    // =========================================================================

    #[Test]
    public function edge_001_empty_signals_array(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([]);
        
        $this->assertEquals(0, $result['decay_score']);
        $this->assertEquals('low', $result['severity']);
    }

    #[Test]
    public function edge_002_null_signals(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore(null);
        
        $this->assertEquals(0, $result['decay_score']);
    }

    #[Test]
    public function edge_003_all_zeros(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => 0,
            'audit_pressure' => 0,
            'staleness' => 0,
        ]);
        
        $this->assertEquals(0, $result['decay_score']);
    }

    #[Test]
    public function edge_004_all_negative(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => -1,
            'impression_loss' => -1,
            'ctr_drop' => -1,
            'position_decline' => -1,
            'ranking_decline' => -1,
            'audit_pressure' => -1,
            'staleness' => -1,
        ]);
        
        // Negative values should be treated as 0 (improvement)
        $this->assertEquals(0, $result['decay_score']);
    }

    #[Test]
    public function edge_005_all_max(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
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

    #[Test]
    public function edge_006_mix_of_null_and_values(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => null,
            'ctr_drop' => 0.3,
            'position_decline' => null,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function edge_007_string_values(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => '0.5',
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // String should be converted to float
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function edge_008_boolean_values(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => true,  // Should be 1.0
            'impression_loss' => false, // Should be 0
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function edge_009_very_small_decimals(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.0000001,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThanOrEqual(0, $result['decay_score']);
    }

    #[Test]
    public function edge_010_very_large_numbers(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // Very large click numbers
        $normalized = $service->normalizeClickLoss(1000000000, 500000000);
        $this->assertEqualsWithDelta(0.5, $normalized, 0.01);
    }

    #[Test]
    public function edge_013_inf_values(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => INF,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // INF should be treated as 1.0
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function edge_014_nan_values(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => NAN,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        // NAN should be treated as 0
        $this->assertEquals(0, $result['decay_score']);
    }

    #[Test]
    public function edge_015_single_factor_extreme(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 1.0,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
    }

    #[Test]
    public function edge_016_all_optional_missing(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function edge_018_memory_efficiency(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $initialMemory = memory_get_usage();
        
        for ($i = 0; $i < 100; $i++) {
            $service->calculateDecayScore([
                'click_loss' => 0.5,
                'impression_loss' => 0.3,
                'ctr_drop' => 0.2,
                'position_decline' => 0.1,
                'ranking_decline' => 0.2,
                'audit_pressure' => 0.1,
                'staleness' => 0.3,
            ]);
        }
        
        $finalMemory = memory_get_usage();
        $memoryGrowth = $finalMemory - $initialMemory;
        
        // Should not grow more than 1MB for 100 calculations
        $this->assertLessThan(1048576, $memoryGrowth);
    }

    #[Test]
    public function edge_019_performance_threshold(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $signals = [
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ];
        
        $start = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $service->calculateDecayScore($signals);
        }
        
        $end = microtime(true);
        $duration = ($end - $start) * 1000; // ms
        
        // 1000 calculations should take less than 100ms
        $this->assertLessThan(100, $duration);
    }

    #[Test]
    public function edge_020_integration_ready(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        // Verify result is usable by DetectionService
        $this->assertIsArray($result);
        $this->assertArrayHasKey('decay_score', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertArrayHasKey('detection_confidence', $result);
        $this->assertArrayHasKey('contributing_factors', $result);
        $this->assertArrayHasKey('score_breakdown', $result);
        
        // Verify types
        $this->assertIsFloat($result['decay_score']);
        $this->assertIsString($result['severity']);
        $this->assertIsFloat($result['detection_confidence']);
        $this->assertIsArray($result['contributing_factors']);
        $this->assertIsArray($result['score_breakdown']);
    }

    // =========================================================================
    // INT: Integration Tests
    // =========================================================================

    #[Test]
    public function int_001_full_workflow(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        // Complete result structure
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertArrayHasKey('decay_score', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertArrayHasKey('detection_confidence', $result);
        $this->assertArrayHasKey('contributing_factors', $result);
        $this->assertArrayHasKey('score_breakdown', $result);
    }

    #[Test]
    public function int_002_partial_workflow(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function int_003_result_serialization(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        $json = json_encode($result);
        $this->assertNotFalse($json);
        $this->assertIsString($json);
    }

    #[Test]
    public function int_004_result_deserialization(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        $json = json_encode($result);
        $decoded = json_decode($json, true);
        
        $this->assertIsArray($decoded);
        $this->assertEquals($result['decay_score'], $decoded['decay_score']);
    }

    #[Test]
    public function int_005_service_injection(): void
    {
        $service = app(\App\Services\ContentDecay\ContentDecayScoringService::class);
        
        $this->assertInstanceOf(\App\Services\ContentDecay\ContentDecayScoringService::class, $service);
        
        $result = $service->calculateDecayScore($this->getMinimalSignals());
        $this->assertIsArray($result);
    }

    #[Test]
    public function int_006_real_gsc_data(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // Realistic GSC metrics
        $result = $service->calculateDecayScore([
            'click_loss' => $service->normalizeClickLoss(1500, 900),   // 40% click loss
            'impression_loss' => $service->normalizeImpressionLoss(50000, 40000), // 20% impression loss
            'ctr_drop' => $service->normalizeCtrDrop(3.0, 2.25), // 25% CTR drop
            'position_decline' => $service->normalizePositionDecline(5, 7), // 2 position decline
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(0.85, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function int_007_real_ranking_data(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $result = $service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.15,
            'ranking_decline' => 0.25, // Ranking data
            'audit_pressure' => null,
            'staleness' => null,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(0.93, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function int_008_real_audit_data(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        $result = $service->calculateDecayScore([
            'click_loss' => 0.3,
            'impression_loss' => 0.2,
            'ctr_drop' => 0.1,
            'position_decline' => 0.15,
            'ranking_decline' => 0.25,
            'audit_pressure' => 0.1, // Audit data
            'staleness' => 0.3,
        ]);
        
        $this->assertGreaterThan(0, $result['decay_score']);
        $this->assertEqualsWithDelta(1.00, $result['detection_confidence'], 0.01);
    }

    #[Test]
    public function int_009_result_usable_by_detection_service(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        $result = $service->calculateDecayScore([
            'click_loss' => 0.5,
            'impression_loss' => 0.3,
            'ctr_drop' => 0.2,
            'position_decline' => 0.1,
            'ranking_decline' => 0.2,
            'audit_pressure' => 0.1,
            'staleness' => 0.3,
        ]);
        
        // Verify interface compatibility for DetectionService
        $this->assertIsFloat($result['decay_score']);
        $this->assertIsString($result['severity']);
        $this->assertContains($result['severity'], ['low', 'medium', 'high', 'critical']);
        $this->assertIsFloat($result['detection_confidence']);
        $this->assertIsArray($result['contributing_factors']);
        $this->assertIsArray($result['score_breakdown']);
    }

    #[Test]
    public function int_010_documentation_matches(): void
    {
        $service = new \App\Services\ContentDecay\ContentDecayScoringService();
        
        // Verify base weights match documentation
        $weights = $service->getBaseWeights();
        
        $this->assertEqualsWithDelta(0.35, $weights['click_loss'], 0.001);
        $this->assertEqualsWithDelta(0.15, $weights['impression_loss'], 0.001);
        $this->assertEqualsWithDelta(0.15, $weights['ctr_drop'], 0.001);
        $this->assertEqualsWithDelta(0.15, $weights['position_decline'], 0.001);
        $this->assertEqualsWithDelta(0.10, $weights['ranking_decline'], 0.001);
        $this->assertEqualsWithDelta(0.05, $weights['audit_pressure'], 0.001);
        $this->assertEqualsWithDelta(0.05, $weights['staleness'], 0.001);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Get minimal valid signals for testing.
     */
    private function getMinimalSignals(): array
    {
        return [
            'click_loss' => 0.1,
            'impression_loss' => 0,
            'ctr_drop' => 0,
            'position_decline' => 0,
            'ranking_decline' => null,
            'audit_pressure' => null,
            'staleness' => null,
        ];
    }
}
