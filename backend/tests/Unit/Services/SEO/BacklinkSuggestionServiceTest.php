<?php

namespace Tests\Unit\Services\SEO;

use PHPUnit\Framework\TestCase;
use App\Services\SEO\BacklinkSuggestionService;

/**
 * Tests for BacklinkSuggestionService
 * 
 * @see TASK-23-12
 * @see TEST-23-12
 */
class BacklinkSuggestionServiceTest extends TestCase
{
    // =========================================================================
    // LOW REFERRING DOMAINS TESTS (RD) - High Priority
    // =========================================================================

    /**
     * RD-001: Very low domains (5).
     * 
     * @test
     */
    public function low_domains_very_low_triggers_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 5,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $this->assertNotEmpty($suggestions);
        $lowDomainSuggestion = collect($suggestions)->firstWhere('id', 'low_referring_domains');
        $this->assertNotNull($lowDomainSuggestion);
        $this->assertEquals('high', $lowDomainSuggestion['priority']);
    }

    /**
     * RD-002: Low domains (25).
     * 
     * @test
     */
    public function low_domains_low_triggers_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 25,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $lowDomainSuggestion = collect($suggestions)->firstWhere('id', 'low_referring_domains');
        $this->assertNotNull($lowDomainSuggestion);
        $this->assertEquals('high', $lowDomainSuggestion['priority']);
    }

    /**
     * RD-003: At threshold (50).
     * 
     * @test
     */
    public function low_domains_at_threshold_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 50,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $lowDomainSuggestion = collect($suggestions)->firstWhere('id', 'low_referring_domains');
        $this->assertNull($lowDomainSuggestion);
    }

    /**
     * RD-004: Above threshold (75).
     * 
     * @test
     */
    public function low_domains_above_threshold_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 75,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $lowDomainSuggestion = collect($suggestions)->firstWhere('id', 'low_referring_domains');
        $this->assertNull($lowDomainSuggestion);
    }

    /**
     * RD-005: Zero domains.
     * 
     * @test
     */
    public function low_domains_zero_triggers_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 0,
            'referring_domains' => 0,
            'avg_domain_trust' => null,
            'growth_rate' => null,
            'exact_match_anchor_ratio' => null,
        ]);

        $lowDomainSuggestion = collect($suggestions)->firstWhere('id', 'low_referring_domains');
        $this->assertNotNull($lowDomainSuggestion);
        $this->assertEquals('high', $lowDomainSuggestion['priority']);
    }

    /**
     * RD-006: Null domains.
     * 
     * @test
     */
    public function low_domains_null_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => null,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $lowDomainSuggestion = collect($suggestions)->firstWhere('id', 'low_referring_domains');
        $this->assertNull($lowDomainSuggestion);
    }

    // =========================================================================
    // HIGH EXACT-MATCH ANCHORS TESTS (EM) - High Priority
    // =========================================================================

    /**
     * EM-001: Very high ratio (60%).
     * 
     * @test
     */
    public function exact_match_very_high_triggers_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.60,
        ]);

        $exactMatchSuggestion = collect($suggestions)->firstWhere('id', 'high_exact_match_anchors');
        $this->assertNotNull($exactMatchSuggestion);
        $this->assertEquals('high', $exactMatchSuggestion['priority']);
    }

    /**
     * EM-002: At threshold (25%).
     * 
     * @test
     */
    public function exact_match_at_threshold_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.25,
        ]);

        $exactMatchSuggestion = collect($suggestions)->firstWhere('id', 'high_exact_match_anchors');
        $this->assertNull($exactMatchSuggestion);
    }

    /**
     * EM-003: Below threshold (15%).
     * 
     * @test
     */
    public function exact_match_below_threshold_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.15,
        ]);

        $exactMatchSuggestion = collect($suggestions)->firstWhere('id', 'high_exact_match_anchors');
        $this->assertNull($exactMatchSuggestion);
    }

    /**
     * EM-004: Perfect (0%).
     * 
     * @test
     */
    public function exact_match_perfect_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.0,
        ]);

        $exactMatchSuggestion = collect($suggestions)->firstWhere('id', 'high_exact_match_anchors');
        $this->assertNull($exactMatchSuggestion);
    }

    /**
     * EM-005: 100% exact match.
     * 
     * @test
     */
    public function exact_match_100_percent_triggers_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 1.0,
        ]);

        $exactMatchSuggestion = collect($suggestions)->firstWhere('id', 'high_exact_match_anchors');
        $this->assertNotNull($exactMatchSuggestion);
        $this->assertEquals('high', $exactMatchSuggestion['priority']);
    }

    /**
     * EM-006: Null ratio.
     * 
     * @test
     */
    public function exact_match_null_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => null,
        ]);

        $exactMatchSuggestion = collect($suggestions)->firstWhere('id', 'high_exact_match_anchors');
        $this->assertNull($exactMatchSuggestion);
    }

    // =========================================================================
    // LOW DOMAIN TRUST TESTS (DT) - Medium Priority
    // =========================================================================

    /**
     * DT-001: Very low trust (15).
     * 
     * @test
     */
    public function domain_trust_very_low_triggers_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 15,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $trustSuggestion = collect($suggestions)->firstWhere('id', 'low_domain_trust');
        $this->assertNotNull($trustSuggestion);
        $this->assertEquals('medium', $trustSuggestion['priority']);
    }

    /**
     * DT-002: Low trust (35).
     * 
     * @test
     */
    public function domain_trust_low_triggers_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 35,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $trustSuggestion = collect($suggestions)->firstWhere('id', 'low_domain_trust');
        $this->assertNotNull($trustSuggestion);
        $this->assertEquals('medium', $trustSuggestion['priority']);
    }

    /**
     * DT-003: At threshold (40).
     * 
     * @test
     */
    public function domain_trust_at_threshold_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 40,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $trustSuggestion = collect($suggestions)->firstWhere('id', 'low_domain_trust');
        $this->assertNull($trustSuggestion);
    }

    /**
     * DT-004: Above threshold (60).
     * 
     * @test
     */
    public function domain_trust_above_threshold_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $trustSuggestion = collect($suggestions)->firstWhere('id', 'low_domain_trust');
        $this->assertNull($trustSuggestion);
    }

    /**
     * DT-005: High trust (80).
     * 
     * @test
     */
    public function domain_trust_high_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 80,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $trustSuggestion = collect($suggestions)->firstWhere('id', 'low_domain_trust');
        $this->assertNull($trustSuggestion);
    }

    /**
     * DT-006: Null trust.
     * 
     * @test
     */
    public function domain_trust_null_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => null,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $trustSuggestion = collect($suggestions)->firstWhere('id', 'low_domain_trust');
        $this->assertNull($trustSuggestion);
    }

    // =========================================================================
    // DECLINING BACKLINKS TESTS (DB) - Medium Priority
    // =========================================================================

    /**
     * DB-001: Severe decline (-30%).
     * 
     * @test
     */
    public function declining_severe_triggers_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => -30,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $declineSuggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertNotNull($declineSuggestion);
        $this->assertEquals('medium', $declineSuggestion['priority']);
    }

    /**
     * DB-002: Moderate decline (-15%).
     * 
     * @test
     */
    public function declining_moderate_triggers_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => -15,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $declineSuggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertNotNull($declineSuggestion);
    }

    /**
     * DB-003: Slight decline (-6%).
     * 
     * @test
     */
    public function declining_slight_triggers_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => -6,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $declineSuggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertNotNull($declineSuggestion);
    }

    /**
     * DB-004: At threshold (-5%).
     * 
     * @test
     */
    public function declining_at_threshold_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => -5,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $declineSuggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertNull($declineSuggestion);
    }

    /**
     * DB-005: Slight decline above threshold (-3%).
     * 
     * @test
     */
    public function declining_above_threshold_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => -3,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $declineSuggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertNull($declineSuggestion);
    }

    /**
     * DB-006: No growth (0%).
     * 
     * @test
     */
    public function declining_no_growth_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 0,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $declineSuggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertNull($declineSuggestion);
    }

    /**
     * DB-007: Positive growth (+10%).
     * 
     * @test
     */
    public function declining_positive_growth_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $declineSuggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertNull($declineSuggestion);
    }

    /**
     * DB-008: Null growth rate.
     * 
     * @test
     */
    public function declining_null_growth_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => null,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $declineSuggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertNull($declineSuggestion);
    }

    // =========================================================================
    // HIGH NOFOLLOW RATIO TESTS (NF) - Low Priority
    // =========================================================================

    /**
     * NF-001: Very high nofollow (80%).
     * 
     * @test
     */
    public function nofollow_very_high_triggers_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
            'dofollow_count' => 20,
            'nofollow_count' => 80,
        ]);

        $nofollowSuggestion = collect($suggestions)->firstWhere('id', 'high_nofollow_ratio');
        $this->assertNotNull($nofollowSuggestion);
        $this->assertEquals('low', $nofollowSuggestion['priority']);
    }

    /**
     * NF-002: At threshold (50%).
     * 
     * @test
     */
    public function nofollow_at_threshold_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
            'dofollow_count' => 50,
            'nofollow_count' => 50,
        ]);

        $nofollowSuggestion = collect($suggestions)->firstWhere('id', 'high_nofollow_ratio');
        $this->assertNull($nofollowSuggestion);
    }

    /**
     * NF-003: Below threshold (30%).
     * 
     * @test
     */
    public function nofollow_below_threshold_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
            'dofollow_count' => 70,
            'nofollow_count' => 30,
        ]);

        $nofollowSuggestion = collect($suggestions)->firstWhere('id', 'high_nofollow_ratio');
        $this->assertNull($nofollowSuggestion);
    }

    /**
     * NF-004: Low nofollow (10%).
     * 
     * @test
     */
    public function nofollow_low_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
            'dofollow_count' => 90,
            'nofollow_count' => 10,
        ]);

        $nofollowSuggestion = collect($suggestions)->firstWhere('id', 'high_nofollow_ratio');
        $this->assertNull($nofollowSuggestion);
    }

    /**
     * NF-005: Null nofollow ratio.
     * 
     * @test
     */
    public function nofollow_null_no_suggestion(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
            // No dofollow/nofollow counts
        ]);

        $nofollowSuggestion = collect($suggestions)->firstWhere('id', 'high_nofollow_ratio');
        $this->assertNull($nofollowSuggestion);
    }

    // =========================================================================
    // PRIORITIZATION TESTS (PR)
    // =========================================================================

    /**
     * PR-001: All issues present - sorted by priority.
     * 
     * @test
     */
    public function prioritization_all_issues_sorted_correctly(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 5,           // High priority
            'avg_domain_trust' => 15,           // Medium priority
            'growth_rate' => -20,               // Medium priority
            'exact_match_anchor_ratio' => 0.60, // High priority
            'dofollow_count' => 20,
            'nofollow_count' => 80,             // Low priority
        ]);

        $this->assertCount(5, $suggestions);
        
        // Verify priority ordering: high → medium → low
        $priorities = array_column($suggestions, 'priority');
        $this->assertEquals(['high', 'high', 'medium', 'medium', 'low'], $priorities);
    }

    /**
     * PR-002: Multiple high priority.
     * 
     * @test
     */
    public function prioritization_multiple_high_priority(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 10,          // High priority
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.60, // High priority
        ]);

        $highPrioritySuggestions = array_filter($suggestions, fn($s) => $s['priority'] === 'high');
        $this->assertCount(2, $highPrioritySuggestions);
    }

    /**
     * PR-003: Multiple medium priority.
     * 
     * @test
     */
    public function prioritization_multiple_medium_priority(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 25,   // Medium priority
            'growth_rate' => -15,       // Medium priority
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $mediumPrioritySuggestions = array_filter($suggestions, fn($s) => $s['priority'] === 'medium');
        $this->assertCount(2, $mediumPrioritySuggestions);
    }

    /**
     * PR-004: Mixed priorities.
     * 
     * @test
     */
    public function prioritization_mixed_priorities(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 10,          // High
            'avg_domain_trust' => 25,           // Medium
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
            'dofollow_count' => 30,
            'nofollow_count' => 70,             // Low
        ]);

        $this->assertCount(3, $suggestions);
        
        // First should be high
        $this->assertEquals('high', $suggestions[0]['priority']);
        // Second should be medium
        $this->assertEquals('medium', $suggestions[1]['priority']);
        // Last should be low
        $this->assertEquals('low', $suggestions[2]['priority']);
    }

    /**
     * PR-005: Only low priority.
     * 
     * @test
     */
    public function prioritization_only_low_priority(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
            'dofollow_count' => 30,
            'nofollow_count' => 70,
        ]);

        $this->assertCount(1, $suggestions);
        $this->assertEquals('low', $suggestions[0]['priority']);
    }

    /**
     * PR-006: No issues - empty array.
     * 
     * @test
     */
    public function prioritization_no_issues_empty_array(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 500,
            'referring_domains' => 150,
            'avg_domain_trust' => 75,
            'growth_rate' => 15,
            'exact_match_anchor_ratio' => 0.10,
            'dofollow_count' => 450,
            'nofollow_count' => 50,
        ]);

        $this->assertEmpty($suggestions);
    }

    // =========================================================================
    // SUGGESTION STRUCTURE TESTS (SS)
    // =========================================================================

    /**
     * SS-001: Suggestion structure.
     * 
     * @test
     */
    public function suggestion_structure_has_all_fields(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 5,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $suggestion = $suggestions[0];
        
        $this->assertArrayHasKey('id', $suggestion);
        $this->assertArrayHasKey('title', $suggestion);
        $this->assertArrayHasKey('description', $suggestion);
        $this->assertArrayHasKey('priority', $suggestion);
        $this->assertArrayHasKey('impact', $suggestion);
    }

    /**
     * SS-002: Metrics structure in suggestion.
     * 
     * @test
     */
    public function suggestion_metrics_structure(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 5,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $suggestion = $suggestions[0];
        
        if (isset($suggestion['metrics'])) {
            $this->assertArrayHasKey('current', $suggestion['metrics']);
            $this->assertArrayHasKey('benchmark', $suggestion['metrics']);
        }
    }

    /**
     * SS-003: Priority values are valid.
     * 
     * @test
     */
    public function suggestion_priority_values_valid(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 5,
            'avg_domain_trust' => 25,
            'growth_rate' => -15,
            'exact_match_anchor_ratio' => 0.60,
            'dofollow_count' => 20,
            'nofollow_count' => 80,
        ]);

        $validPriorities = ['high', 'medium', 'low'];
        
        foreach ($suggestions as $suggestion) {
            $this->assertContains($suggestion['priority'], $validPriorities);
        }
    }

    /**
     * SS-004: Impact values are valid.
     * 
     * @test
     */
    public function suggestion_impact_values_valid(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 5,
            'avg_domain_trust' => 25,
            'growth_rate' => -15,
            'exact_match_anchor_ratio' => 0.60,
        ]);

        $validImpacts = ['high', 'medium', 'low'];
        
        foreach ($suggestions as $suggestion) {
            $this->assertContains($suggestion['impact'], $validImpacts);
        }
    }

    /**
     * SS-005: Unique IDs for each suggestion.
     * 
     * @test
     */
    public function suggestion_unique_ids(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 5,
            'avg_domain_trust' => 25,
            'growth_rate' => -15,
            'exact_match_anchor_ratio' => 0.60,
        ]);

        $ids = array_column($suggestions, 'id');
        $uniqueIds = array_unique($ids);
        
        $this->assertCount(count($ids), $uniqueIds);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Empty metrics.
     * 
     * @test
     */
    public function edge_case_empty_metrics(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([]);

        $this->assertIsArray($suggestions);
        // Should not crash, may have low_domain suggestion for 0 domains
    }

    /**
     * EC-002: All null metrics.
     * 
     * @test
     */
    public function edge_case_all_null_metrics(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => null,
            'referring_domains' => null,
            'avg_domain_trust' => null,
            'growth_rate' => null,
            'exact_match_anchor_ratio' => null,
        ]);

        $this->assertIsArray($suggestions);
    }

    /**
     * EC-003: Zero backlinks.
     * 
     * @test
     */
    public function edge_case_zero_backlinks(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 0,
            'referring_domains' => 0,
            'avg_domain_trust' => null,
            'growth_rate' => null,
            'exact_match_anchor_ratio' => null,
        ]);

        // Should have low_referring_domains suggestion
        $lowDomainSuggestion = collect($suggestions)->firstWhere('id', 'low_referring_domains');
        $this->assertNotNull($lowDomainSuggestion);
    }

    /**
     * EC-004: Perfect profile - no suggestions.
     * 
     * @test
     */
    public function edge_case_perfect_profile(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 500,
            'referring_domains' => 150,
            'avg_domain_trust' => 80,
            'growth_rate' => 20,
            'exact_match_anchor_ratio' => 0.05,
            'dofollow_count' => 450,
            'nofollow_count' => 50,
        ]);

        $this->assertEmpty($suggestions);
    }

    /**
     * EC-005: Extreme negative growth.
     * 
     * @test
     */
    public function edge_case_extreme_negative_growth(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => -100,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $declineSuggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertNotNull($declineSuggestion);
        $this->assertArrayHasKey('id', $declineSuggestion);
    }

    /**
     * EC-006: Boundary values at exact thresholds.
     * 
     * @test
     */
    public function edge_case_boundary_values(): void
    {
        $service = new BacklinkSuggestionService();
        
        // All values at exact threshold - should not trigger suggestions
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 50,     // At threshold
            'avg_domain_trust' => 40,      // At threshold
            'growth_rate' => -5,           // At threshold
            'exact_match_anchor_ratio' => 0.25, // At threshold
            'dofollow_count' => 50,
            'nofollow_count' => 50,        // At threshold
        ]);

        $this->assertEmpty($suggestions);
    }

    // =========================================================================
    // IMPACT CALCULATION TESTS (IC)
    // =========================================================================

    /**
     * IC-001: Very low domains - high impact.
     * 
     * @test
     */
    public function impact_very_low_domains_high(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 5,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $suggestion = collect($suggestions)->firstWhere('id', 'low_referring_domains');
        $this->assertEquals('high', $suggestion['impact']);
    }

    /**
     * IC-002: Moderately low domains - medium impact.
     * 
     * @test
     */
    public function impact_moderately_low_domains_medium(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 35,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $suggestion = collect($suggestions)->firstWhere('id', 'low_referring_domains');
        $this->assertEquals('medium', $suggestion['impact']);
    }

    /**
     * IC-003: Very high exact match - high impact.
     * 
     * @test
     */
    public function impact_very_high_exact_match_high(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.60,
        ]);

        $suggestion = collect($suggestions)->firstWhere('id', 'high_exact_match_anchors');
        $this->assertEquals('high', $suggestion['impact']);
    }

    /**
     * IC-004: Moderate exact match - medium impact.
     * 
     * @test
     */
    public function impact_moderate_exact_match_medium(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.35,
        ]);

        $suggestion = collect($suggestions)->firstWhere('id', 'high_exact_match_anchors');
        $this->assertEquals('medium', $suggestion['impact']);
    }

    /**
     * IC-005: Severe decline - high impact.
     * 
     * @test
     */
    public function impact_severe_decline_high(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => -30,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $suggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertEquals('high', $suggestion['impact']);
    }

    /**
     * IC-006: Moderate decline - medium impact.
     * 
     * @test
     */
    public function impact_moderate_decline_medium(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => -10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $suggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertEquals('medium', $suggestion['impact']);
    }

    // =========================================================================
    // SECURITY TESTS (SC)
    // =========================================================================

    /**
     * SC-001: Invalid metrics type handled.
     * 
     * @test
     */
    public function security_invalid_type_handled(): void
    {
        $service = new BacklinkSuggestionService();
        
        // Service expects array, invalid input should be handled
        $this->expectException(\TypeError::class);
        $service->generateFromMetrics('invalid');
    }

    /**
     * SC-002: Negative domain count treated as 0.
     * 
     * @test
     */
    public function security_negative_domains_treated_as_zero(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => -10,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        // Should still generate low domains suggestion (treated as 0 or very low)
        $lowDomainSuggestion = collect($suggestions)->firstWhere('id', 'low_referring_domains');
        $this->assertNotNull($lowDomainSuggestion);
    }

    /**
     * SC-003: Negative trust treated as 0.
     * 
     * @test
     */
    public function security_negative_trust_treated_as_zero(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => -50,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        // Negative trust should trigger low trust suggestion
        $trustSuggestion = collect($suggestions)->firstWhere('id', 'low_domain_trust');
        $this->assertNotNull($trustSuggestion);
    }

    /**
     * SC-004: Invalid ratio capped at 1.0.
     * 
     * @test
     */
    public function security_invalid_ratio_capped(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 2.0, // Invalid, > 1.0
        ]);

        // Should still generate suggestion (treated as 100%)
        $exactMatchSuggestion = collect($suggestions)->firstWhere('id', 'high_exact_match_anchors');
        $this->assertNotNull($exactMatchSuggestion);
    }

    // =========================================================================
    // DESCRIPTION TESTS
    // =========================================================================

    /**
     * Verify correct description for low domains.
     * 
     * @test
     */
    public function description_low_domains_correct(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 5,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $suggestion = collect($suggestions)->firstWhere('id', 'low_referring_domains');
        $this->assertStringContainsString('referring domains', strtolower($suggestion['description']));
    }

    /**
     * Verify correct description for exact match anchors.
     * 
     * @test
     */
    public function description_exact_match_correct(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => 10,
            'exact_match_anchor_ratio' => 0.60,
        ]);

        $suggestion = collect($suggestions)->firstWhere('id', 'high_exact_match_anchors');
        $this->assertStringContainsString('anchor', strtolower($suggestion['description']));
    }

    /**
     * Verify correct description for declining backlinks.
     * 
     * @test
     */
    public function description_declining_correct(): void
    {
        $service = new BacklinkSuggestionService();
        
        $suggestions = $service->generateFromMetrics([
            'total_backlinks' => 100,
            'referring_domains' => 60,
            'avg_domain_trust' => 60,
            'growth_rate' => -20,
            'exact_match_anchor_ratio' => 0.10,
        ]);

        $suggestion = collect($suggestions)->firstWhere('id', 'declining_backlinks');
        $this->assertStringContainsString('backlink', strtolower($suggestion['description']));
    }
}
