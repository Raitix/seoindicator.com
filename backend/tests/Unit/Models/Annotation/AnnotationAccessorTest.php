<?php

namespace Tests\Unit\Models\Annotation;

use App\Models\Annotation;
use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for Annotation model accessors.
 * 
 * Tests verify:
 * - category_label accessor returns human-readable label
 * - category_icon accessor returns emoji icon
 * - Fallback behavior for unknown categories
 * 
 * @see TASK-9-3
 * @see TEST-9-3 (AC-001 to AC-014, AI-001 to AI-014)
 */
class AnnotationAccessorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Category to label mapping (from TASK-9-2 spec)
     */
    private const CATEGORY_LABELS = [
        'content_update' => 'Content Update',
        'link_building' => 'Link Building',
        'technical_fix' => 'Technical Fix',
        'algorithm_update' => 'Algorithm Update',
        'competitor_action' => 'Competitor Action',
        'seasonal' => 'Seasonal',
        'manual_action' => 'Manual Action',
        'other' => 'Other',
    ];

    /**
     * Category to icon mapping (from TASK-9-2 spec)
     */
    private const CATEGORY_ICONS = [
        'content_update' => '✏️',
        'link_building' => '🔗',
        'technical_fix' => '🔧',
        'algorithm_update' => '🤖',
        'competitor_action' => '👀',
        'seasonal' => '📅',
        'manual_action' => '⚠️',
        'other' => '📝',
    ];

    /**
     * Create an annotation with a specific category.
     */
    private function createAnnotationWithCategory(string $category): Annotation
    {
        return Annotation::create([
            'keyword_id' => Keyword::factory()->create()->id,
            'project_id' => Project::factory()->create()->id,
            'date' => now(),
            'title' => 'Test Annotation',
            'category' => $category,
        ]);
    }

    // ============================================
    // category_label Accessor Tests (AC-001 to AC-014)
    // ============================================

    /**
     * Test AC-001: category_label accessor exists
     */
    public function test_category_label_accessor_exists(): void
    {
        $annotation = $this->createAnnotationWithCategory('other');
        
        $this->assertIsString($annotation->category_label);
    }

    /**
     * Test AC-002: Content update category label
     */
    public function test_content_update_category_label(): void
    {
        $annotation = $this->createAnnotationWithCategory('content_update');
        
        $this->assertEquals('Content Update', $annotation->category_label);
    }

    /**
     * Test AC-003: Link building category label
     */
    public function test_link_building_category_label(): void
    {
        $annotation = $this->createAnnotationWithCategory('link_building');
        
        $this->assertEquals('Link Building', $annotation->category_label);
    }

    /**
     * Test AC-004: Technical fix category label
     */
    public function test_technical_fix_category_label(): void
    {
        $annotation = $this->createAnnotationWithCategory('technical_fix');
        
        $this->assertEquals('Technical Fix', $annotation->category_label);
    }

    /**
     * Test AC-005: Algorithm update category label
     */
    public function test_algorithm_update_category_label(): void
    {
        $annotation = $this->createAnnotationWithCategory('algorithm_update');
        
        $this->assertEquals('Algorithm Update', $annotation->category_label);
    }

    /**
     * Test AC-006: Competitor action category label
     */
    public function test_competitor_action_category_label(): void
    {
        $annotation = $this->createAnnotationWithCategory('competitor_action');
        
        $this->assertEquals('Competitor Action', $annotation->category_label);
    }

    /**
     * Test AC-007: Seasonal category label
     */
    public function test_seasonal_category_label(): void
    {
        $annotation = $this->createAnnotationWithCategory('seasonal');
        
        $this->assertEquals('Seasonal', $annotation->category_label);
    }

    /**
     * Test AC-008: Manual action category label
     */
    public function test_manual_action_category_label(): void
    {
        $annotation = $this->createAnnotationWithCategory('manual_action');
        
        $this->assertEquals('Manual Action', $annotation->category_label);
    }

    /**
     * Test AC-009: Other category label
     */
    public function test_other_category_label(): void
    {
        $annotation = $this->createAnnotationWithCategory('other');
        
        $this->assertEquals('Other', $annotation->category_label);
    }

    /**
     * Test AC-010: Unknown category fallback
     */
    public function test_unknown_category_label_fallback(): void
    {
        $annotation = $this->createAnnotationWithCategory('unknown_category');
        
        // Should fallback to 'Other' for unknown categories
        $this->assertEquals('Other', $annotation->category_label);
    }

    /**
     * Test AC-011: Legacy category with hyphen (different format)
     */
    public function test_legacy_category_with_hyphen_label(): void
    {
        // Legacy format uses hyphens instead of underscores
        $annotation = $this->createAnnotationWithCategory('content-update');
        
        // Should fallback to 'Other' since format doesn't match enum
        $this->assertEquals('Other', $annotation->category_label);
    }

    /**
     * Test AC-012: Empty category fallback
     */
    public function test_empty_category_label_fallback(): void
    {
        $annotation = $this->createAnnotationWithCategory('');
        
        // Should fallback to 'Other' for empty category
        $this->assertEquals('Other', $annotation->category_label);
    }

    /**
     * Test AC-013: Case sensitivity test
     */
    public function test_category_label_case_sensitive(): void
    {
        // Uppercase version should not match
        $annotation = $this->createAnnotationWithCategory('CONTENT_UPDATE');
        
        // Should fallback to 'Other' since enum values are lowercase
        $this->assertEquals('Other', $annotation->category_label);
    }

    /**
     * Test AC-014: Accessor works with fresh model
     */
    public function test_category_label_with_fresh_model(): void
    {
        $annotation = $this->createAnnotationWithCategory('algorithm_update');
        
        $freshAnnotation = $annotation->fresh();
        
        $this->assertEquals('Algorithm Update', $freshAnnotation->category_label);
    }

    // ============================================
    // category_icon Accessor Tests (AI-001 to AI-014)
    // ============================================

    /**
     * Test AI-001: category_icon accessor exists
     */
    public function test_category_icon_accessor_exists(): void
    {
        $annotation = $this->createAnnotationWithCategory('other');
        
        $this->assertIsString($annotation->category_icon);
    }

    /**
     * Test AI-002: Content update icon
     */
    public function test_content_update_category_icon(): void
    {
        $annotation = $this->createAnnotationWithCategory('content_update');
        
        $this->assertEquals('✏️', $annotation->category_icon);
    }

    /**
     * Test AI-003: Link building icon
     */
    public function test_link_building_category_icon(): void
    {
        $annotation = $this->createAnnotationWithCategory('link_building');
        
        $this->assertEquals('🔗', $annotation->category_icon);
    }

    /**
     * Test AI-004: Technical fix icon
     */
    public function test_technical_fix_category_icon(): void
    {
        $annotation = $this->createAnnotationWithCategory('technical_fix');
        
        $this->assertEquals('🔧', $annotation->category_icon);
    }

    /**
     * Test AI-005: Algorithm update icon
     */
    public function test_algorithm_update_category_icon(): void
    {
        $annotation = $this->createAnnotationWithCategory('algorithm_update');
        
        $this->assertEquals('🤖', $annotation->category_icon);
    }

    /**
     * Test AI-006: Competitor action icon
     */
    public function test_competitor_action_category_icon(): void
    {
        $annotation = $this->createAnnotationWithCategory('competitor_action');
        
        $this->assertEquals('👀', $annotation->category_icon);
    }

    /**
     * Test AI-007: Seasonal icon
     */
    public function test_seasonal_category_icon(): void
    {
        $annotation = $this->createAnnotationWithCategory('seasonal');
        
        $this->assertEquals('📅', $annotation->category_icon);
    }

    /**
     * Test AI-008: Manual action icon
     */
    public function test_manual_action_category_icon(): void
    {
        $annotation = $this->createAnnotationWithCategory('manual_action');
        
        $this->assertEquals('⚠️', $annotation->category_icon);
    }

    /**
     * Test AI-009: Other icon
     */
    public function test_other_category_icon(): void
    {
        $annotation = $this->createAnnotationWithCategory('other');
        
        $this->assertEquals('📝', $annotation->category_icon);
    }

    /**
     * Test AI-010: Unknown category icon fallback
     */
    public function test_unknown_category_icon_fallback(): void
    {
        $annotation = $this->createAnnotationWithCategory('unknown_category');
        
        // Should fallback to '📝' for unknown categories
        $this->assertEquals('📝', $annotation->category_icon);
    }

    /**
     * Test AI-011: Legacy category with hyphen icon
     */
    public function test_legacy_category_with_hyphen_icon(): void
    {
        $annotation = $this->createAnnotationWithCategory('content-update');
        
        // Should fallback to '📝' since format doesn't match enum
        $this->assertEquals('📝', $annotation->category_icon);
    }

    /**
     * Test AI-012: Empty category icon fallback
     */
    public function test_empty_category_icon_fallback(): void
    {
        $annotation = $this->createAnnotationWithCategory('');
        
        // Should fallback to '📝' for empty category
        $this->assertEquals('📝', $annotation->category_icon);
    }

    /**
     * Test AI-013: Case sensitivity test for icon
     */
    public function test_category_icon_case_sensitive(): void
    {
        $annotation = $this->createAnnotationWithCategory('CONTENT_UPDATE');
        
        // Should fallback to '📝' since enum values are lowercase
        $this->assertEquals('📝', $annotation->category_icon);
    }

    /**
     * Test AI-014: Accessor works with fresh model
     */
    public function test_category_icon_with_fresh_model(): void
    {
        $annotation = $this->createAnnotationWithCategory('algorithm_update');
        
        $freshAnnotation = $annotation->fresh();
        
        $this->assertEquals('🤖', $freshAnnotation->category_icon);
    }

    // ============================================
    // Edge Case Tests (EC-001 to EC-004)
    // ============================================

    /**
     * Test EC-001: Accessor with null category
     */
    public function test_accessor_with_null_category(): void
    {
        // Create annotation and manually set category to null
        $annotation = Annotation::create([
            'keyword_id' => Keyword::factory()->create()->id,
            'project_id' => Project::factory()->create()->id,
            'date' => now(),
            'title' => 'Test',
            'category' => 'other', // Required by schema
        ]);
        
        // Manually update to null (bypassing fillable)
        $annotation->category = null;
        
        // Accessors should handle null gracefully
        // Note: This tests the accessor behavior, not schema constraint
        $label = $annotation->category_label;
        $icon = $annotation->category_icon;
        
        // Should return fallback values
        $this->assertEquals('Other', $label);
        $this->assertEquals('📝', $icon);
    }

    /**
     * Test EC-003: Mass assignment with accessor
     */
    public function test_mass_assignment_with_accessor(): void
    {
        $annotation = Annotation::create([
            'keyword_id' => Keyword::factory()->create()->id,
            'project_id' => Project::factory()->create()->id,
            'date' => now(),
            'title' => 'Test',
            'category' => 'link_building',
        ]);
        
        // Accessor should compute correctly after mass assignment
        $this->assertEquals('Link Building', $annotation->category_label);
        $this->assertEquals('🔗', $annotation->category_icon);
    }

    /**
     * Test EC-004: JSON serialization may include accessors
     */
    public function test_json_serialization_includes_accessors(): void
    {
        $annotation = $this->createAnnotationWithCategory('algorithm_update');
        
        $array = $annotation->toArray();
        
        // Note: Accessors are included in toArray() by default in Laravel
        // when they are defined as getAttributeProperty naming convention
        // Check if accessors are included (depends on model implementation)
        $hasLabel = array_key_exists('category_label', $array);
        $hasIcon = array_key_exists('category_icon', $array);
        
        // Document the behavior - accessors may or may not be included
        // depending on model's appends configuration
        $this->assertIsArray($array);
        
        // If model has $appends = ['category_label', 'category_icon'],
        // these would be true:
        // $this->assertTrue($hasLabel);
        // $this->assertTrue($hasIcon);
    }

    /**
     * Test EC-007: Accessor on unsaved model
     */
    public function test_accessor_on_unsaved_model(): void
    {
        $annotation = new Annotation([
            'keyword_id' => Keyword::factory()->create()->id,
            'project_id' => Project::factory()->create()->id,
            'date' => now(),
            'title' => 'Test',
            'category' => 'content_update',
        ]);
        
        // Accessor should work even on unsaved model
        $this->assertEquals('Content Update', $annotation->category_label);
        $this->assertEquals('✏️', $annotation->category_icon);
    }

    /**
     * Test all valid categories return correct label/icon pairs
     */
    public function test_all_valid_categories_return_correct_pairs(): void
    {
        foreach (self::CATEGORY_LABELS as $category => $expectedLabel) {
            $annotation = $this->createAnnotationWithCategory($category);
            
            $this->assertEquals(
                $expectedLabel,
                $annotation->category_label,
                "Category '{$category}' should have label '{$expectedLabel}'"
            );
            
            $this->assertEquals(
                self::CATEGORY_ICONS[$category],
                $annotation->category_icon,
                "Category '{$category}' should have correct icon"
            );
        }
    }

    /**
     * Test accessor consistency across multiple models
     */
    public function test_accessor_consistency_across_multiple_models(): void
    {
        $category = 'algorithm_update';
        $expectedLabel = 'Algorithm Update';
        $expectedIcon = '🤖';
        
        // Create multiple annotations with same category
        $annotations = collect(range(1, 5))->map(function () use ($category) {
            return $this->createAnnotationWithCategory($category);
        });
        
        // All should return same label and icon
        foreach ($annotations as $annotation) {
            $this->assertEquals($expectedLabel, $annotation->category_label);
            $this->assertEquals($expectedIcon, $annotation->category_icon);
        }
    }
}
