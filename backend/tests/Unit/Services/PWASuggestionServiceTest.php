<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\PWAAnalysis;
use App\Services\PWASuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for PWASuggestionService.
 *
 * Tests suggestion generation for PWA manifest fields and icons.
 * Covers priority levels, sorting, and edge cases.
 *
 * @see TASK-24-7
 * @see \App\Services\PWASuggestionService
 */
class PWASuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PWASuggestionService $service;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PWASuggestionService();
        $user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $user->id]);
    }

    // =========================================================================
    // GENERATE SUGGESTIONS - NO MANIFEST
    // =========================================================================

    /** @test */
    public function gs_001_no_manifest_returns_high_priority_suggestion(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => false,
            'manifest_valid' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $this->assertNotEmpty($suggestions);
        $this->assertEquals('high', $suggestions[0]['priority']);
        $this->assertStringContainsString('manifest', strtolower($suggestions[0]['message']));
        $this->assertEquals('manifest', $suggestions[0]['field']);
    }

    /** @test */
    public function gs_002_invalid_manifest_returns_high_priority_suggestion(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $this->assertNotEmpty($suggestions);
        $invalidManifestSuggestion = collect($suggestions)->firstWhere('field', 'manifest_valid');
        $this->assertNotNull($invalidManifestSuggestion);
        $this->assertEquals('high', $invalidManifestSuggestion['priority']);
    }

    // =========================================================================
    // GENERATE SUGGESTIONS - REQUIRED FIELDS
    // =========================================================================

    /** @test */
    public function gs_003_missing_name_field_returns_suggestion(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $nameSuggestion = collect($suggestions)->firstWhere('field', 'name');
        $this->assertNotNull($nameSuggestion);
        $this->assertEquals('high', $nameSuggestion['priority']);
        $this->assertStringContainsString('name', strtolower($nameSuggestion['message']));
    }

    /** @test */
    public function gs_004_missing_short_name_field_returns_suggestion(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_short_name' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $shortNameSuggestion = collect($suggestions)->firstWhere('field', 'short_name');
        $this->assertNotNull($shortNameSuggestion);
        $this->assertEquals('high', $shortNameSuggestion['priority']);
    }

    /** @test */
    public function gs_005_missing_start_url_field_returns_suggestion(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_start_url' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $startUrlSuggestion = collect($suggestions)->firstWhere('field', 'start_url');
        $this->assertNotNull($startUrlSuggestion);
        $this->assertEquals('high', $startUrlSuggestion['priority']);
    }

    /** @test */
    public function gs_006_missing_display_field_returns_suggestion(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_display' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $displaySuggestion = collect($suggestions)->firstWhere('field', 'display');
        $this->assertNotNull($displaySuggestion);
        $this->assertEquals('high', $displaySuggestion['priority']);
    }

    // =========================================================================
    // GENERATE SUGGESTIONS - RECOMMENDED FIELDS
    // =========================================================================

    /** @test */
    public function gs_007_missing_theme_color_returns_medium_priority(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_theme_color' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $themeColorSuggestion = collect($suggestions)->firstWhere('field', 'theme_color');
        $this->assertNotNull($themeColorSuggestion);
        $this->assertEquals('medium', $themeColorSuggestion['priority']);
    }

    /** @test */
    public function gs_008_missing_background_color_returns_medium_priority(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_background_color' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $bgColorSuggestion = collect($suggestions)->firstWhere('field', 'background_color');
        $this->assertNotNull($bgColorSuggestion);
        $this->assertEquals('medium', $bgColorSuggestion['priority']);
    }

    /** @test */
    public function gs_009_missing_description_returns_low_priority(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_description' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $descriptionSuggestion = collect($suggestions)->firstWhere('field', 'description');
        $this->assertNotNull($descriptionSuggestion);
        $this->assertEquals('low', $descriptionSuggestion['priority']);
    }

    // =========================================================================
    // GENERATE SUGGESTIONS - ICONS
    // =========================================================================

    /** @test */
    public function gs_010_missing_192x192_icon_returns_high_priority(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'icons_192_present' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $iconSuggestion = collect($suggestions)->firstWhere('field', 'icon_192');
        $this->assertNotNull($iconSuggestion);
        $this->assertEquals('high', $iconSuggestion['priority']);
        $this->assertStringContainsString('192', $iconSuggestion['message']);
    }

    /** @test */
    public function gs_011_missing_512x512_icon_returns_high_priority(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'icons_512_present' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $iconSuggestion = collect($suggestions)->firstWhere('field', 'icon_512');
        $this->assertNotNull($iconSuggestion);
        $this->assertEquals('high', $iconSuggestion['priority']);
        $this->assertStringContainsString('512', $iconSuggestion['message']);
    }

    /** @test */
    public function gs_012_missing_180x180_icon_returns_medium_priority(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'icons_180_present' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $iconSuggestion = collect($suggestions)->firstWhere('field', 'icon_180');
        $this->assertNotNull($iconSuggestion);
        $this->assertEquals('medium', $iconSuggestion['priority']);
        $this->assertStringContainsString('180', $iconSuggestion['message']);
    }

    /** @test */
    public function gs_013_complete_pwa_returns_empty_array(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => true,
            'has_short_name' => true,
            'has_start_url' => true,
            'has_display' => true,
            'has_theme_color' => true,
            'has_background_color' => true,
            'has_description' => true,
            'icons_192_present' => true,
            'icons_512_present' => true,
            'icons_180_present' => true,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $this->assertEmpty($suggestions);
    }

    /** @test */
    public function gs_014_suggestions_sorted_by_priority(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => false,           // high
            'has_description' => false,    // low
            'has_theme_color' => false,    // medium
            'icons_192_present' => false,  // high
            'icons_180_present' => false,  // medium
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        // Find indices of each priority
        $priorities = array_column($suggestions, 'priority');
        $highIndices = array_keys($priorities, 'high');
        $mediumIndices = array_keys($priorities, 'medium');
        $lowIndices = array_keys($priorities, 'low');

        // All high should come before medium
        foreach ($highIndices as $highIdx) {
            foreach ($mediumIndices as $mediumIdx) {
                $this->assertLessThan($mediumIdx, $highIdx, 'High priority should come before medium');
            }
        }

        // All medium should come before low
        foreach ($mediumIndices as $mediumIdx) {
            foreach ($lowIndices as $lowIdx) {
                $this->assertLessThan($lowIdx, $mediumIdx, 'Medium priority should come before low');
            }
        }
    }

    /** @test */
    public function gs_015_multiple_missing_fields_return_multiple_suggestions(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => false,
            'has_short_name' => false,
            'has_start_url' => false,
            'has_display' => false,
            'has_theme_color' => true,     // Present - no suggestion
            'has_background_color' => true, // Present - no suggestion
            'has_description' => true,      // Present - no suggestion
            'icons_192_present' => false,
            'icons_512_present' => false,
            'icons_180_present' => true,    // Present - no suggestion
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $this->assertCount(6, $suggestions); // 4 required fields + 2 required icons
    }

    // =========================================================================
    // GET SUGGESTION FOR FIELD
    // =========================================================================

    /** @test */
    public function gf_001_get_suggestion_for_missing_name(): void
    {
        $suggestion = $this->service->getSuggestionForField('name', false);

        $this->assertNotNull($suggestion);
        $this->assertEquals('high', $suggestion['priority']);
        $this->assertEquals('name', $suggestion['field']);
        $this->assertArrayHasKey('message', $suggestion);
        $this->assertArrayHasKey('guide', $suggestion);
    }

    /** @test */
    public function gf_002_get_suggestion_for_missing_short_name(): void
    {
        $suggestion = $this->service->getSuggestionForField('short_name', false);

        $this->assertNotNull($suggestion);
        $this->assertEquals('high', $suggestion['priority']);
        $this->assertEquals('short_name', $suggestion['field']);
    }

    /** @test */
    public function gf_003_get_suggestion_for_missing_start_url(): void
    {
        $suggestion = $this->service->getSuggestionForField('start_url', false);

        $this->assertNotNull($suggestion);
        $this->assertEquals('high', $suggestion['priority']);
        $this->assertEquals('start_url', $suggestion['field']);
    }

    /** @test */
    public function gf_004_get_suggestion_for_missing_display(): void
    {
        $suggestion = $this->service->getSuggestionForField('display', false);

        $this->assertNotNull($suggestion);
        $this->assertEquals('high', $suggestion['priority']);
        $this->assertEquals('display', $suggestion['field']);
    }

    /** @test */
    public function gf_005_get_suggestion_for_missing_theme_color(): void
    {
        $suggestion = $this->service->getSuggestionForField('theme_color', false);

        $this->assertNotNull($suggestion);
        $this->assertEquals('medium', $suggestion['priority']);
        $this->assertEquals('theme_color', $suggestion['field']);
    }

    /** @test */
    public function gf_006_get_suggestion_for_missing_background_color(): void
    {
        $suggestion = $this->service->getSuggestionForField('background_color', false);

        $this->assertNotNull($suggestion);
        $this->assertEquals('medium', $suggestion['priority']);
        $this->assertEquals('background_color', $suggestion['field']);
    }

    /** @test */
    public function gf_007_get_suggestion_for_missing_description(): void
    {
        $suggestion = $this->service->getSuggestionForField('description', false);

        $this->assertNotNull($suggestion);
        $this->assertEquals('low', $suggestion['priority']);
        $this->assertEquals('description', $suggestion['field']);
    }

    /** @test */
    public function gf_008_get_suggestion_for_present_field_returns_null(): void
    {
        $suggestion = $this->service->getSuggestionForField('name', true);

        $this->assertNull($suggestion);
    }

    /** @test */
    public function gf_009_invalid_field_name_returns_null(): void
    {
        $suggestion = $this->service->getSuggestionForField('invalid_field', false);

        $this->assertNull($suggestion);
    }

    /** @test */
    public function gf_010_suggestion_includes_guide_anchor(): void
    {
        $suggestion = $this->service->getSuggestionForField('name', false);

        $this->assertNotNull($suggestion);
        $this->assertNotEmpty($suggestion['guide']);
        $this->assertStringStartsWith('#', $suggestion['guide']);
    }

    // =========================================================================
    // GET SUGGESTION FOR ICON
    // =========================================================================

    /** @test */
    public function gi_001_get_suggestion_for_missing_192x192_icon(): void
    {
        $suggestion = $this->service->getSuggestionForIcon('192', false);

        $this->assertNotNull($suggestion);
        $this->assertEquals('high', $suggestion['priority']);
        $this->assertEquals('icon_192', $suggestion['field']);
        $this->assertStringContainsString('192', $suggestion['message']);
    }

    /** @test */
    public function gi_002_get_suggestion_for_missing_512x512_icon(): void
    {
        $suggestion = $this->service->getSuggestionForIcon('512', false);

        $this->assertNotNull($suggestion);
        $this->assertEquals('high', $suggestion['priority']);
        $this->assertEquals('icon_512', $suggestion['field']);
        $this->assertStringContainsString('512', $suggestion['message']);
    }

    /** @test */
    public function gi_003_get_suggestion_for_missing_180x180_icon(): void
    {
        $suggestion = $this->service->getSuggestionForIcon('180', false);

        $this->assertNotNull($suggestion);
        $this->assertEquals('medium', $suggestion['priority']);
        $this->assertEquals('icon_180', $suggestion['field']);
        $this->assertStringContainsString('180', $suggestion['message']);
    }

    /** @test */
    public function gi_004_get_suggestion_for_present_icon_returns_null(): void
    {
        $suggestion = $this->service->getSuggestionForIcon('192', true);

        $this->assertNull($suggestion);
    }

    /** @test */
    public function gi_005_invalid_icon_size_returns_null(): void
    {
        $suggestion = $this->service->getSuggestionForIcon('999', false);

        $this->assertNull($suggestion);
    }

    /** @test */
    public function gi_006_icon_field_naming_convention(): void
    {
        $suggestion192 = $this->service->getSuggestionForIcon('192', false);
        $suggestion512 = $this->service->getSuggestionForIcon('512', false);
        $suggestion180 = $this->service->getSuggestionForIcon('180', false);

        $this->assertEquals('icon_192', $suggestion192['field']);
        $this->assertEquals('icon_512', $suggestion512['field']);
        $this->assertEquals('icon_180', $suggestion180['field']);
    }

    // =========================================================================
    // EDGE CASES
    // =========================================================================

    /** @test */
    public function ec_001_empty_analysis_object(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => false,
            'manifest_valid' => false,
            'has_name' => false,
            'has_short_name' => false,
            'has_start_url' => false,
            'has_display' => false,
            'has_theme_color' => false,
            'has_background_color' => false,
            'has_description' => false,
            'icons_192_present' => false,
            'icons_512_present' => false,
            'icons_180_present' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        // Should have suggestions for all missing items
        $this->assertGreaterThanOrEqual(10, count($suggestions));
    }

    /** @test */
    public function ec_002_null_manifest_json_handled(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'manifest_json' => null,
            'has_name' => true,
            'has_short_name' => true,
            'has_start_url' => true,
            'has_display' => true,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        // Should not throw exception, just return suggestions for missing fields
        $this->assertIsArray($suggestions);
    }

    /** @test */
    public function ec_003_all_fields_present_returns_empty(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => true,
            'has_short_name' => true,
            'has_start_url' => true,
            'has_display' => true,
            'has_theme_color' => true,
            'has_background_color' => true,
            'has_description' => true,
            'icons_192_present' => true,
            'icons_512_present' => true,
            'icons_180_present' => true,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $this->assertEmpty($suggestions);
    }

    /** @test */
    public function ec_004_only_manifest_missing(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => false,
            // All other fields are false by default when no manifest
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $this->assertNotEmpty($suggestions);
        $this->assertEquals('manifest', $suggestions[0]['field']);
    }

    /** @test */
    public function ec_005_only_icons_missing(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => true,
            'has_short_name' => true,
            'has_start_url' => true,
            'has_display' => true,
            'icons_192_present' => false,
            'icons_512_present' => false,
            'icons_180_present' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        // Should only have icon suggestions
        $fields = array_column($suggestions, 'field');
        $this->assertContains('icon_192', $fields);
        $this->assertContains('icon_512', $fields);
        $this->assertContains('icon_180', $fields);
    }

    // =========================================================================
    // SUGGESTION CONTENT
    // =========================================================================

    /** @test */
    public function sc_001_message_is_human_readable(): void
    {
        $suggestion = $this->service->getSuggestionForField('name', false);

        $this->assertNotNull($suggestion);
        $this->assertNotEmpty($suggestion['message']);
        // Message should be a complete sentence or phrase
        $this->assertGreaterThan(20, strlen($suggestion['message']));
    }

    /** @test */
    public function sc_002_guide_anchor_is_valid_format(): void
    {
        $suggestion = $this->service->getSuggestionForField('name', false);

        $this->assertNotNull($suggestion);
        $this->assertMatchesRegularExpression('/^#[a-z0-9-]+$/', $suggestion['guide']);
    }

    /** @test */
    public function sc_003_field_name_matches_field_or_icon_format(): void
    {
        $fieldSuggestion = $this->service->getSuggestionForField('name', false);
        $iconSuggestion = $this->service->getSuggestionForIcon('192', false);

        $this->assertNotNull($fieldSuggestion);
        $this->assertNotNull($iconSuggestion);

        // Field name should be alphanumeric with underscores
        $this->assertMatchesRegularExpression('/^[a-z_]+$/', $fieldSuggestion['field']);
        $this->assertMatchesRegularExpression('/^icon_[0-9]+$/', $iconSuggestion['field']);
    }

    /** @test */
    public function sc_004_priority_is_valid_enum(): void
    {
        $suggestions = [
            $this->service->getSuggestionForField('name', false),
            $this->service->getSuggestionForField('theme_color', false),
            $this->service->getSuggestionForField('description', false),
        ];

        $validPriorities = ['high', 'medium', 'low'];

        foreach ($suggestions as $suggestion) {
            $this->assertNotNull($suggestion);
            $this->assertContains($suggestion['priority'], $validPriorities);
        }
    }

    // =========================================================================
    // SORTING
    // =========================================================================

    /** @test */
    public function so_001_high_priority_suggestions_first(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => false,        // high
            'has_theme_color' => false, // medium
            'has_description' => false, // low
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $this->assertEquals('high', $suggestions[0]['priority']);
    }

    /** @test */
    public function so_002_medium_priority_after_high(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => false,        // high
            'has_theme_color' => false, // medium
            'has_description' => false, // low
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        // Find medium priority index
        $mediumIndex = null;
        foreach ($suggestions as $index => $suggestion) {
            if ($suggestion['priority'] === 'medium') {
                $mediumIndex = $index;
                break;
            }
        }

        // All suggestions before medium should be high
        if ($mediumIndex !== null) {
            for ($i = 0; $i < $mediumIndex; $i++) {
                $this->assertEquals('high', $suggestions[$i]['priority']);
            }
        }
    }

    /** @test */
    public function so_003_low_priority_last(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => false,        // high
            'has_theme_color' => false, // medium
            'has_description' => false, // low
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        // Last suggestion should be low priority
        $lastSuggestion = end($suggestions);
        $this->assertEquals('low', $lastSuggestion['priority']);
    }

    /** @test */
    public function so_004_same_priority_order_stable(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => false,
            'has_short_name' => false,
            'has_start_url' => false,
        ]);

        $suggestions1 = $this->service->generateSuggestions($analysis);
        $suggestions2 = $this->service->generateSuggestions($analysis);

        // Order should be consistent between calls
        $fields1 = array_column($suggestions1, 'field');
        $fields2 = array_column($suggestions2, 'field');

        $this->assertEquals($fields1, $fields2);
    }

    // =========================================================================
    // INTEGRATION
    // =========================================================================

    /** @test */
    public function int_001_service_can_be_resolved_from_container(): void
    {
        $service = app(PWASuggestionService::class);

        $this->assertInstanceOf(PWASuggestionService::class, $service);
    }

    /** @test */
    public function int_002_works_with_real_pwa_analysis_model(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $this->assertNotEmpty($suggestions);
        $this->assertContains('name', array_column($suggestions, 'field'));
    }

    /** @test */
    public function int_003_suggestion_count_matches_expected(): void
    {
        // Missing 3 required fields + 2 required icons + 1 recommended icon
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => false,
            'has_short_name' => false,
            'has_start_url' => false,
            'has_display' => true,
            'has_theme_color' => true,
            'has_background_color' => true,
            'has_description' => true,
            'icons_192_present' => false,
            'icons_512_present' => false,
            'icons_180_present' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        // 3 required fields + 2 required icons + 1 recommended icon = 6
        $this->assertCount(6, $suggestions);
    }

    // =========================================================================
    // ADDITIONAL FIELD SUGGESTIONS
    // =========================================================================

    /** @test */
    public function missing_manifest_suggestion_has_correct_guide(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => false,
        ]);

        $suggestions = $this->service->generateSuggestions($analysis);

        $manifestSuggestion = collect($suggestions)->firstWhere('field', 'manifest');
        $this->assertNotNull($manifestSuggestion);
        $this->assertEquals('#manifest-basics', $manifestSuggestion['guide']);
    }

    /** @test */
    public function invalid_manifest_suggestion_has_correct_message(): void
    {
        $suggestion = $this->service->getSuggestionForField('manifest_valid', false);

        $this->assertNotNull($suggestion);
        $this->assertStringContainsString('invalid', strtolower($suggestion['message']));
    }

    /** @test */
    public function all_field_suggestions_have_valid_structure(): void
    {
        $fields = ['name', 'short_name', 'start_url', 'display', 'theme_color', 'background_color', 'description'];

        foreach ($fields as $field) {
            $suggestion = $this->service->getSuggestionForField($field, false);

            $this->assertNotNull($suggestion, "Suggestion for {$field} should not be null");
            $this->assertArrayHasKey('priority', $suggestion);
            $this->assertArrayHasKey('field', $suggestion);
            $this->assertArrayHasKey('message', $suggestion);
            $this->assertArrayHasKey('guide', $suggestion);
            $this->assertEquals($field, $suggestion['field']);
        }
    }

    /** @test */
    public function all_icon_suggestions_have_valid_structure(): void
    {
        $sizes = ['192', '512', '180'];

        foreach ($sizes as $size) {
            $suggestion = $this->service->getSuggestionForIcon($size, false);

            $this->assertNotNull($suggestion, "Suggestion for icon {$size} should not be null");
            $this->assertArrayHasKey('priority', $suggestion);
            $this->assertArrayHasKey('field', $suggestion);
            $this->assertArrayHasKey('message', $suggestion);
            $this->assertArrayHasKey('guide', $suggestion);
            $this->assertEquals("icon_{$size}", $suggestion['field']);
        }
    }
}
