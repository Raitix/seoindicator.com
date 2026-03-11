<?php

/**
 * Production smoke test for ContentDecayReasonService.
 * Run via: php artisan test tests/Production/ContentDecayReasonServiceSmokeTest.php
 * 
 * @see TASK-36-4
 */

namespace Tests\Production;

use Tests\TestCase;
use App\Services\ContentDecay\Contracts\ContentDecayReasonServiceInterface;
use App\Services\ContentDecay\ContentDecayReasonService;

class ContentDecayReasonServiceSmokeTest extends TestCase
{
    /**
     * Test service is registered in DI container.
     */
    public function test_service_registered_in_container(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        $this->assertInstanceOf(ContentDecayReasonService::class, $service);
    }

    /**
     * Test service can be instantiated directly.
     */
    public function test_service_can_be_instantiated(): void
    {
        $service = new ContentDecayReasonService();
        $this->assertInstanceOf(ContentDecayReasonService::class, $service);
    }

    /**
     * Test getThresholds returns expected structure.
     */
    public function test_get_thresholds_returns_expected_structure(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        $thresholds = $service->getThresholds();

        $this->assertIsArray($thresholds);
        $this->assertArrayHasKey('click_loss', $thresholds);
        $this->assertArrayHasKey('impression_loss', $thresholds);
        $this->assertArrayHasKey('ctr_drop', $thresholds);
        $this->assertArrayHasKey('position_decline', $thresholds);
        $this->assertArrayHasKey('staleness_days', $thresholds);
        $this->assertArrayHasKey('internal_links', $thresholds);
        $this->assertArrayHasKey('annotation_window_days', $thresholds);

        // Verify default values
        $this->assertEquals(0.10, $thresholds['click_loss']);
        $this->assertEquals(0.15, $thresholds['impression_loss']);
        $this->assertEquals(0.5, $thresholds['ctr_drop']);
        $this->assertEquals(3, $thresholds['position_decline']);
        $this->assertEquals(180, $thresholds['staleness_days']);
        $this->assertEquals(3, $thresholds['internal_links']);
        $this->assertEquals(30, $thresholds['annotation_window_days']);
    }

    /**
     * Test assignReasonCodes with empty signals.
     */
    public function test_assign_reason_codes_empty_signals(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        $result = $service->assignReasonCodes([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('primary_reason_code', $result);
        $this->assertArrayHasKey('reason_codes', $result);
        $this->assertArrayHasKey('evidence', $result);
        $this->assertNull($result['primary_reason_code']);
        $this->assertEmpty($result['reason_codes']);
        $this->assertEmpty($result['evidence']);
    }

    /**
     * Test clicks_down reason code triggered.
     */
    public function test_clicks_down_reason_code(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        
        $signals = [
            'clicks' => [
                'previous' => 1000,
                'current' => 700, // 30% decline > 10% threshold
            ],
        ];

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('clicks_down', $result['reason_codes']);
        $this->assertEquals('clicks_down', $result['primary_reason_code']);
        $this->assertCount(1, $result['evidence']);
        $this->assertEquals('clicks', $result['evidence'][0]['evidence']['metric']);
    }

    /**
     * Test audit_issues_present has highest priority.
     */
    public function test_audit_issues_highest_priority(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        
        $signals = [
            'clicks' => [
                'previous' => 1000,
                'current' => 700, // clicks_down
            ],
            'unresolved_audit_issues' => 5, // audit_issues_present
        ];

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('clicks_down', $result['reason_codes']);
        $this->assertContains('audit_issues_present', $result['reason_codes']);
        $this->assertEquals('audit_issues_present', $result['primary_reason_code'], 
            'audit_issues_present should have highest priority');
    }

    /**
     * Test stale_content reason code.
     */
    public function test_stale_content_reason_code(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        
        $signals = [
            'content_age_days' => 200, // > 180 threshold
        ];

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('stale_content', $result['reason_codes']);
        $this->assertEquals('stale_content', $result['primary_reason_code']);
    }

    /**
     * Test avg_position_down with page boundary crossing.
     */
    public function test_position_down_page_boundary(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        
        $signals = [
            'avg_position' => [
                'previous' => 8,  // Page 1
                'current' => 12,  // Page 2 - crossed boundary
            ],
        ];

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('avg_position_down', $result['reason_codes']);
        $this->assertEquals('avg_position_down', $result['primary_reason_code']);
    }

    /**
     * Test competitor_gain_suspected suppresses avg_position_down.
     */
    public function test_competitor_gain_suppresses_position_down(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        
        $signals = [
            'avg_position' => [
                'previous' => 5,
                'current' => 15, // Position worsened
            ],
            'impressions' => [
                'previous' => 1000,
                'current' => 1050, // Stable (within 20%)
            ],
            'clicks' => [
                'previous' => 100,
                'current' => 98, // Stable (within 20%)
            ],
        ];

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('competitor_gain_suspected', $result['reason_codes']);
        $this->assertNotContains('avg_position_down', $result['reason_codes'],
            'avg_position_down should be suppressed when competitor_gain_suspected triggers');
        $this->assertEquals('competitor_gain_suspected', $result['primary_reason_code']);
    }

    /**
     * Test evidence structure.
     */
    public function test_evidence_structure(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        
        $signals = [
            'clicks' => [
                'previous' => 1000,
                'current' => 700,
            ],
            'page_title' => 'Test Page Title',
            'page_type' => 'blog',
        ];

        $result = $service->assignReasonCodes($signals);

        $this->assertCount(1, $result['evidence']);
        $evidence = $result['evidence'][0];
        
        $this->assertArrayHasKey('reason', $evidence);
        $this->assertArrayHasKey('primary', $evidence);
        $this->assertArrayHasKey('evidence', $evidence);
        
        $innerEvidence = $evidence['evidence'];
        $this->assertArrayHasKey('metric', $innerEvidence);
        $this->assertArrayHasKey('previous', $innerEvidence);
        $this->assertArrayHasKey('current', $innerEvidence);
        $this->assertArrayHasKey('change_percent', $innerEvidence);
        $this->assertArrayHasKey('change_absolute', $innerEvidence);
        $this->assertArrayHasKey('window', $innerEvidence);
        $this->assertArrayHasKey('context', $innerEvidence);
        
        $this->assertEquals(-30.0, $innerEvidence['change_percent']);
        $this->assertEquals(-300, $innerEvidence['change_absolute']);
        $this->assertStringContainsString('Test Page Title', $innerEvidence['context']);
    }

    /**
     * Test CTR down requires meaningful impressions.
     */
    public function test_ctr_down_requires_meaningful_impressions(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        
        // Low impressions - CTR should not trigger
        $signals = [
            'ctr' => [
                'previous' => 5.0,
                'current' => 2.0, // Large drop
            ],
            'impressions' => [
                'current' => 50, // Below 100 threshold
            ],
        ];

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('ctr_down', $result['reason_codes'],
            'CTR down should not trigger with low impressions');
    }

    /**
     * Test all 11 reason codes can be triggered.
     */
    public function test_all_reason_codes_can_trigger(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        
        $expectedCodes = [
            'audit_issues_present',
            'clicks_down',
            'ctr_down',
            'avg_position_down',
            'tracked_rankings_down',
            'impressions_down',
            'stale_content',
            'ai_visibility_down',
            'competitor_gain_suspected',
            'internal_link_weakness',
            'annotation_recently_changed',
        ];

        // Test each reason code can be triggered
        foreach ($expectedCodes as $expectedCode) {
            $signals = match($expectedCode) {
                'audit_issues_present' => ['unresolved_audit_issues' => 3],
                'clicks_down' => ['clicks' => ['previous' => 1000, 'current' => 700]],
                'ctr_down' => ['ctr' => ['previous' => 5.0, 'current' => 3.0], 'impressions' => ['current' => 500]],
                'avg_position_down' => ['avg_position' => ['previous' => 5, 'current' => 12]],
                'tracked_rankings_down' => ['tracked_rank_visibility' => ['previous' => 100, 'current' => 80]],
                'impressions_down' => ['impressions' => ['previous' => 1000, 'current' => 600]],
                'stale_content' => ['content_age_days' => 200],
                'ai_visibility_down' => ['ai_visibility' => ['previous' => 50, 'current' => 30]],
                'competitor_gain_suspected' => [
                    'avg_position' => ['previous' => 5, 'current' => 15],
                    'impressions' => ['previous' => 1000, 'current' => 1000],
                    'clicks' => ['previous' => 100, 'current' => 100],
                ],
                'internal_link_weakness' => ['internal_links' => 1],
                'annotation_recently_changed' => ['last_annotation_at' => now()->subDays(7)->toIso8601String()],
            };

            $result = $service->assignReasonCodes($signals);
            $this->assertContains($expectedCode, $result['reason_codes'], 
                "Expected reason code '{$expectedCode}' should be triggerable");
        }
    }

    /**
     * Test custom thresholds override defaults.
     */
    public function test_custom_thresholds(): void
    {
        $customThresholds = [
            'click_loss' => 0.05, // 5% instead of 10%
        ];

        $service = new ContentDecayReasonService($customThresholds);
        $thresholds = $service->getThresholds();

        $this->assertEquals(0.05, $thresholds['click_loss']);
        
        // Test that smaller decline now triggers
        $signals = [
            'clicks' => [
                'previous' => 1000,
                'current' => 920, // 8% decline > 5% custom threshold
            ],
        ];

        $result = $service->assignReasonCodes($signals);
        $this->assertContains('clicks_down', $result['reason_codes'],
            '8% decline should trigger with 5% custom threshold');
    }

    /**
     * Test null values handled gracefully.
     */
    public function test_null_values_handled_gracefully(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        
        $signals = [
            'clicks' => [
                'previous' => null,
                'current' => 500,
            ],
            'impressions' => [
                'previous' => 1000,
                'current' => null,
            ],
            'avg_position' => [
                'previous' => null,
                'current' => null,
            ],
        ];

        $result = $service->assignReasonCodes($signals);

        // Should not crash, should return empty or minimal results
        $this->assertIsArray($result);
        $this->assertArrayHasKey('primary_reason_code', $result);
    }

    /**
     * Test primary evidence is marked correctly.
     */
    public function test_primary_evidence_marked(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        
        $signals = [
            'clicks' => ['previous' => 1000, 'current' => 700],
            'impressions' => ['previous' => 10000, 'current' => 6000],
        ];

        $result = $service->assignReasonCodes($signals);

        // clicks_down has priority 2, impressions_down has priority 6
        // So clicks_down should be primary
        foreach ($result['evidence'] as $evidence) {
            $isPrimary = $evidence['reason'] === $result['primary_reason_code'];
            $this->assertEquals($isPrimary, $evidence['primary'],
                "Evidence primary flag should match primary_reason_code");
        }
    }
}
