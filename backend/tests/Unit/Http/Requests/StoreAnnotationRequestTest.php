<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StoreAnnotationRequest;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for StoreAnnotationRequest validation.
 *
 * @see TASK-9-6
 * @see TASK-9-9
 * @see TEST-9-9
 */
class StoreAnnotationRequestTest extends TestCase
{
    use RefreshDatabase;

    private StoreAnnotationRequest $request;
    private array $validData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->request = new StoreAnnotationRequest();
        
        $this->validData = [
            'date' => '2026-02-27',
            'title' => 'Google Algorithm Update Impact',
            'description' => 'Position dropped from #5 to #12 after core update',
            'category' => 'algorithm_update',
        ];
    }

    // ============================================
    // Authorization Tests (AUTH-001 to AUTH-002)
    // ============================================

    /**
     * Test AUTH-001: authorize method exists
     */
    public function test_authorize_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'authorize'));
    }

    /**
     * Test AUTH-002: authorize returns true (controller handles auth)
     */
    public function test_authorize_returns_true(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    // ============================================
    // Rules Method Tests (RULES-001 to RULES-002)
    // ============================================

    /**
     * Test RULES-001: rules method exists
     */
    public function test_rules_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    /**
     * Test RULES-002: rules returns array
     */
    public function test_rules_returns_array(): void
    {
        $rules = $this->request->rules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('date', $rules);
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('category', $rules);
    }

    // ============================================
    // Field Presence Tests (FP-001 to FP-006)
    // ============================================

    /**
     * Test FP-001: All required fields present passes
     */
    public function test_all_required_fields_present_passes(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Test Annotation',
            'category' => 'other',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test FP-002: Missing date field fails
     */
    public function test_missing_date_field_fails(): void
    {
        $data = [
            'title' => 'Test Annotation',
            'category' => 'other',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
    }

    /**
     * Test FP-003: Missing title field fails
     */
    public function test_missing_title_field_fails(): void
    {
        $data = [
            'date' => '2026-02-27',
            'category' => 'other',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /**
     * Test FP-004: Missing category passes (nullable in implementation)
     */
    public function test_missing_category_passes(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Test Annotation',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Category is nullable in current implementation
        $this->assertFalse($validator->fails());
    }

    /**
     * Test FP-005: All fields including optional passes
     */
    public function test_all_fields_including_optional_passes(): void
    {
        $data = $this->validData;
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test FP-006: Empty request body fails
     */
    public function test_empty_request_body_fails(): void
    {
        $data = [];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    // ============================================
    // Date Field Validation (DT-001 to DT-012)
    // ============================================

    /**
     * Test DT-001: Valid date format Y-m-d passes
     */
    public function test_valid_date_format_ymd_passes(): void
    {
        $data = array_merge($this->validData, ['date' => '2026-02-27']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DT-002: Valid date with time passes
     */
    public function test_valid_date_with_time_passes(): void
    {
        $data = array_merge($this->validData, ['date' => '2026-02-27 14:30:00']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DT-003: Non-standard date format may pass (Laravel is permissive)
     * 
     * Note: Laravel's date validator is permissive and accepts multiple formats.
     * The format "d-m-Y" (27-02-2026) is parsed as a valid date.
     * For strict format validation, use 'date_format:Y-m-d' rule instead.
     */
    public function test_non_standard_date_format_may_pass(): void
    {
        $data = array_merge($this->validData, ['date' => '27-02-2026']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Laravel's date validator accepts multiple date formats
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DT-004: Non-date string fails
     */
    public function test_non_date_string_fails(): void
    {
        $data = array_merge($this->validData, ['date' => 'not-a-date']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
    }

    /**
     * Test DT-005: Empty string date fails
     */
    public function test_empty_string_date_fails(): void
    {
        $data = array_merge($this->validData, ['date' => '']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test DT-006: Null date fails
     */
    public function test_null_date_fails(): void
    {
        $data = array_merge($this->validData, ['date' => null]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test DT-007: Integer date may pass (Laravel treats as timestamp)
     * 
     * Note: Laravel's date validator accepts integers as Unix timestamps.
     * For strict string date validation, additional rules may be needed.
     */
    public function test_integer_date_may_pass(): void
    {
        $data = array_merge($this->validData, ['date' => 20260227]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Laravel's date validator accepts integers as timestamps
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DT-008: Array date fails
     */
    public function test_array_date_fails(): void
    {
        $data = array_merge($this->validData, ['date' => []]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test DT-009: Date in distant past passes
     */
    public function test_date_in_distant_past_passes(): void
    {
        $data = array_merge($this->validData, ['date' => '1900-01-01']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DT-010: Date in distant future passes
     */
    public function test_date_in_distant_future_passes(): void
    {
        $data = array_merge($this->validData, ['date' => '2100-12-31']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DT-011: Leap year date passes
     */
    public function test_leap_year_date_passes(): void
    {
        $data = array_merge($this->validData, ['date' => '2024-02-29']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DT-012: Invalid Feb 29 fails
     */
    public function test_invalid_feb_29_fails(): void
    {
        $data = array_merge($this->validData, ['date' => '2023-02-29']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    // ============================================
    // Title Field Validation (TL-001 to TL-014)
    // ============================================

    /**
     * Test TL-001: Valid title passes
     */
    public function test_valid_title_passes(): void
    {
        $data = array_merge($this->validData, ['title' => 'Google Algorithm Update']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test TL-002: Title exactly 255 chars passes
     */
    public function test_title_exactly_255_chars_passes(): void
    {
        $data = array_merge($this->validData, ['title' => str_repeat('A', 255)]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test TL-003: Title exceeds 255 chars fails
     */
    public function test_title_exceeds_255_chars_fails(): void
    {
        $data = array_merge($this->validData, ['title' => str_repeat('A', 256)]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /**
     * Test TL-004: Title with special characters passes
     */
    public function test_title_with_special_characters_passes(): void
    {
        $data = array_merge($this->validData, ['title' => "Updated <script>alert('xss')</script>"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Validation passes - sanitization happens elsewhere
        $this->assertFalse($validator->fails());
    }

    /**
     * Test TL-005: Title with Unicode passes
     */
    public function test_title_with_unicode_passes(): void
    {
        $data = array_merge($this->validData, ['title' => 'Content update 中文 🚀']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test TL-006: Title with HTML entities passes
     */
    public function test_title_with_html_entities_passes(): void
    {
        $data = array_merge($this->validData, ['title' => 'Update &amp; Fix']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test TL-007: Empty string title fails
     */
    public function test_empty_string_title_fails(): void
    {
        $data = array_merge($this->validData, ['title' => '']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test TL-008: Null title fails
     */
    public function test_null_title_fails(): void
    {
        $data = array_merge($this->validData, ['title' => null]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test TL-009: Whitespace only title fails (treated as empty)
     * 
     * Note: Laravel's required rule treats whitespace-only strings as empty.
     * This is correct behavior for data integrity.
     */
    public function test_whitespace_only_title_fails(): void
    {
        $data = array_merge($this->validData, ['title' => '   ']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Whitespace-only fails required validation (treated as empty)
        $this->assertTrue($validator->fails());
    }

    /**
     * Test TL-010: Integer title fails
     */
    public function test_integer_title_fails(): void
    {
        $data = array_merge($this->validData, ['title' => 12345]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test TL-011: Array title fails
     */
    public function test_array_title_fails(): void
    {
        $data = array_merge($this->validData, ['title' => []]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test TL-012: Single character title passes
     */
    public function test_single_character_title_passes(): void
    {
        $data = array_merge($this->validData, ['title' => 'A']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test TL-013: Title with newlines passes
     */
    public function test_title_with_newlines_passes(): void
    {
        $data = array_merge($this->validData, ['title' => "Line 1\nLine 2"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test TL-014: Title with only numbers passes
     */
    public function test_title_with_only_numbers_passes(): void
    {
        $data = array_merge($this->validData, ['title' => '123456']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    // ============================================
    // Description Field Validation (DC-001 to DC-012)
    // ============================================

    /**
     * Test DC-001: Valid description passes
     */
    public function test_valid_description_passes(): void
    {
        $data = array_merge($this->validData, ['description' => 'Position dropped after update']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DC-002: Long description passes
     */
    public function test_long_description_passes(): void
    {
        $data = array_merge($this->validData, ['description' => str_repeat('A', 65000)]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DC-003: Description with newlines passes
     */
    public function test_description_with_newlines_passes(): void
    {
        $data = array_merge($this->validData, ['description' => "Line 1\nLine 2\nLine 3"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DC-004: Description with HTML passes
     */
    public function test_description_with_html_passes(): void
    {
        $data = array_merge($this->validData, ['description' => '<p>Content</p>']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DC-005: Description with Unicode passes
     */
    public function test_description_with_unicode_passes(): void
    {
        $data = array_merge($this->validData, ['description' => '内容更新 📈']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DC-006: Null description passes (nullable)
     */
    public function test_null_description_passes(): void
    {
        $data = array_merge($this->validData, ['description' => null]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DC-007: Empty string description passes
     */
    public function test_empty_string_description_passes(): void
    {
        $data = array_merge($this->validData, ['description' => '']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DC-008: Whitespace description passes
     */
    public function test_whitespace_description_passes(): void
    {
        $data = array_merge($this->validData, ['description' => '   ']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DC-009: Integer description fails
     */
    public function test_integer_description_fails(): void
    {
        $data = array_merge($this->validData, ['description' => 12345]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test DC-010: Array description fails
     */
    public function test_array_description_fails(): void
    {
        $data = array_merge($this->validData, ['description' => []]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test DC-011: Markdown content passes
     */
    public function test_markdown_content_passes(): void
    {
        $data = array_merge($this->validData, ['description' => '**Bold** and *italic*']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test DC-012: JSON string passes
     */
    public function test_json_string_passes(): void
    {
        $data = array_merge($this->validData, ['description' => '{"key": "value"}']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    // ============================================
    // Category Field Validation (CT-001 to CT-010)
    // ============================================

    /**
     * Test CT-001: Valid category passes
     */
    public function test_valid_category_passes(): void
    {
        $data = array_merge($this->validData, ['category' => 'algorithm_update']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test CT-002: Various valid categories
     */
    public function test_various_valid_categories(): void
    {
        $validCategories = [
            'content_update',
            'link_building',
            'technical_fix',
            'algorithm_update',
            'competitor_action',
            'seasonal',
            'manual_action',
            'other',
        ];
        
        foreach ($validCategories as $category) {
            $data = array_merge($this->validData, ['category' => $category]);
            $validator = Validator::make($data, $this->request->rules());
            $this->assertFalse($validator->fails(), "Category '{$category}' should pass validation");
        }
    }

    /**
     * Test CT-003: Empty string category passes (nullable)
     */
    public function test_empty_string_category_passes(): void
    {
        $data = array_merge($this->validData, ['category' => '']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Category is nullable in implementation
        $this->assertFalse($validator->fails());
    }

    /**
     * Test CT-004: Null category passes (nullable)
     */
    public function test_null_category_passes(): void
    {
        $data = array_merge($this->validData, ['category' => null]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test CT-005: Integer category fails
     */
    public function test_integer_category_fails(): void
    {
        $data = array_merge($this->validData, ['category' => 1]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test CT-006: Array category fails
     */
    public function test_array_category_fails(): void
    {
        $data = array_merge($this->validData, ['category' => []]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test CT-007: Category exceeds max length fails
     */
    public function test_category_exceeds_max_length_fails(): void
    {
        $data = array_merge($this->validData, ['category' => str_repeat('a', 51)]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test CT-008: Category at max length passes
     */
    public function test_category_at_max_length_passes(): void
    {
        $data = array_merge($this->validData, ['category' => str_repeat('a', 50)]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test CT-009: Legacy hyphenated category passes
     */
    public function test_legacy_hyphenated_category_passes(): void
    {
        $data = array_merge($this->validData, ['category' => 'algorithm-update']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // String validation passes (no enum enforcement in current impl)
        $this->assertFalse($validator->fails());
    }

    /**
     * Test CT-010: Mixed case category passes
     */
    public function test_mixed_case_category_passes(): void
    {
        $data = array_merge($this->validData, ['category' => 'Algorithm_Update']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // String validation passes (no enum enforcement in current impl)
        $this->assertFalse($validator->fails());
    }

    // ============================================
    // project_id Field Validation (PI-001 to PI-006)
    // ============================================

    /**
     * Test PI-001: Valid project_id passes
     */
    public function test_valid_project_id_passes(): void
    {
        $project = Project::factory()->create();
        
        $data = array_merge($this->validData, ['project_id' => $project->id]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test PI-002: Null project_id passes (nullable)
     */
    public function test_null_project_id_passes(): void
    {
        $data = array_merge($this->validData, ['project_id' => null]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test PI-003: Non-existent project_id fails
     */
    public function test_non_existent_project_id_fails(): void
    {
        $data = array_merge($this->validData, ['project_id' => 999999]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('project_id', $validator->errors()->toArray());
    }

    /**
     * Test PI-004: String project_id fails
     */
    public function test_string_project_id_fails(): void
    {
        $data = array_merge($this->validData, ['project_id' => 'not-an-id']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test PI-005: Negative project_id fails
     */
    public function test_negative_project_id_fails(): void
    {
        $data = array_merge($this->validData, ['project_id' => -1]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test PI-006: Zero project_id fails
     */
    public function test_zero_project_id_fails(): void
    {
        $data = array_merge($this->validData, ['project_id' => 0]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    // ============================================
    // Combined Field Tests (CB-001 to CB-008)
    // ============================================

    /**
     * Test CB-001: All valid fields passes
     */
    public function test_all_valid_fields_passes(): void
    {
        $validator = Validator::make($this->validData, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test CB-002: Multiple validation errors
     */
    public function test_multiple_validation_errors(): void
    {
        $data = [
            'date' => 'invalid-date',
            'title' => str_repeat('A', 256),
            'description' => 123, // Integer instead of string
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertGreaterThanOrEqual(2, $validator->errors()->count());
    }

    /**
     * Test CB-003: Valid minimal (required only) passes
     */
    public function test_valid_minimal_required_only_passes(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Quick Note',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test CB-004: All fields empty strings fails
     */
    public function test_all_fields_empty_strings_fails(): void
    {
        $data = [
            'date' => '',
            'title' => '',
            'description' => '',
            'category' => '',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /**
     * Test CB-005: Extra unexpected fields passes
     */
    public function test_extra_unexpected_fields_passes(): void
    {
        $data = array_merge($this->validData, ['extra_field' => 'unexpected']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test CB-006: Nested object fails
     */
    public function test_nested_object_fails(): void
    {
        $data = [
            'annotation' => $this->validData,
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test CB-007: Very large payload passes
     */
    public function test_very_large_payload_passes(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => str_repeat('A', 255),
            'description' => str_repeat('B', 65000),
            'category' => 'other',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test CB-008: Unicode throughout passes
     */
    public function test_unicode_throughout_passes(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => '内容更新 标题 🚀',
            'description' => '描述内容 中文 日本語 한국어',
            'category' => 'other',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    // ============================================
    // Security Tests (SC-001 to SC-008)
    // ============================================

    /**
     * Test SC-001: SQL injection in title passes validation
     */
    public function test_sql_injection_in_title_passes_validation(): void
    {
        $data = array_merge($this->validData, ['title' => "' OR '1'='1"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Validation passes - Eloquent handles escaping
        $this->assertFalse($validator->fails());
    }

    /**
     * Test SC-002: SQL injection in description passes validation
     */
    public function test_sql_injection_in_description_passes_validation(): void
    {
        $data = array_merge($this->validData, ['description' => "; DROP TABLE annotations;--"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Validation passes - Eloquent handles escaping
        $this->assertFalse($validator->fails());
    }

    /**
     * Test SC-003: XSS attempt in title passes validation
     */
    public function test_xss_attempt_in_title_passes_validation(): void
    {
        $data = array_merge($this->validData, ['title' => "<script>alert('xss')</script>"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Validation passes - sanitization happens elsewhere
        $this->assertFalse($validator->fails());
    }

    /**
     * Test SC-004: XSS attempt in description passes validation
     */
    public function test_xss_attempt_in_description_passes_validation(): void
    {
        $data = array_merge($this->validData, ['description' => "<img src=x onerror=alert('xss')>"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Validation passes - sanitization happens elsewhere
        $this->assertFalse($validator->fails());
    }

    /**
     * Test SC-005: Null byte injection passes validation
     */
    public function test_null_byte_injection_passes_validation(): void
    {
        $data = array_merge($this->validData, ['title' => "title\x00description"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Validation passes - handled by application
        $this->assertFalse($validator->fails());
    }

    /**
     * Test SC-006: CRLF injection passes validation
     */
    public function test_crlf_injection_passes_validation(): void
    {
        $data = array_merge($this->validData, ['title' => "title\r\nSet-Cookie: hacked=true"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Validation passes - handled by application
        $this->assertFalse($validator->fails());
    }

    /**
     * Test SC-007: Path traversal passes validation
     */
    public function test_path_traversal_passes_validation(): void
    {
        $data = array_merge($this->validData, ['title' => "../../../etc/passwd"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Validation passes - title is not a path field
        $this->assertFalse($validator->fails());
    }

    /**
     * Test SC-008: Unicode homoglyph passes validation
     */
    public function test_unicode_homoglyph_passes_validation(): void
    {
        $data = array_merge($this->validData, ['title' => "ｓｅｌｅｃｔ"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Validation passes - normal Unicode text
        $this->assertFalse($validator->fails());
    }

    // ============================================
    // Custom Messages Tests (MSG-001 to MSG-005)
    // ============================================

    /**
     * Test MSG-001: messages method exists
     */
    public function test_messages_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'messages'));
    }

    /**
     * Test MSG-002: messages returns array
     */
    public function test_messages_returns_array(): void
    {
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
    }

    /**
     * Test MSG-003: custom messages for date field
     */
    public function test_custom_messages_for_date_field(): void
    {
        $messages = $this->request->messages();
        
        $this->assertArrayHasKey('date.required', $messages);
        $this->assertArrayHasKey('date.date', $messages);
    }

    /**
     * Test MSG-004: custom messages for title field
     */
    public function test_custom_messages_for_title_field(): void
    {
        $messages = $this->request->messages();
        
        $this->assertArrayHasKey('title.required', $messages);
        $this->assertArrayHasKey('title.max', $messages);
    }

    /**
     * Test MSG-005: custom messages for category field
     */
    public function test_custom_messages_for_category_field(): void
    {
        $messages = $this->request->messages();
        
        $this->assertArrayHasKey('category.max', $messages);
    }

    // ============================================
    // Edge Cases (EC-001 to EC-010)
    // ============================================

    /**
     * Test EC-001: Description at TEXT field limit
     */
    public function test_description_at_text_field_limit(): void
    {
        $data = array_merge($this->validData, ['description' => str_repeat('A', 65535)]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test EC-002: Description over TEXT field limit fails
     */
    public function test_description_over_text_field_limit_fails(): void
    {
        $data = array_merge($this->validData, ['description' => str_repeat('A', 65536)]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test EC-003: Zero-width characters in title
     */
    public function test_zero_width_characters_in_title(): void
    {
        $data = array_merge($this->validData, ['title' => "test\u{200B}annotation"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test EC-004: Mixed encoding in title
     */
    public function test_mixed_encoding_in_title(): void
    {
        $data = array_merge($this->validData, ['title' => 'Test 正體中文']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test EC-005: Maximum boundary title exactly 255
     */
    public function test_maximum_boundary_title_exactly_255(): void
    {
        $data = array_merge($this->validData, ['title' => str_repeat('A', 255)]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test EC-006: Boundary plus one title 256 fails
     */
    public function test_boundary_plus_one_title_256_fails(): void
    {
        $data = array_merge($this->validData, ['title' => str_repeat('A', 256)]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test EC-007: ISO 8601 date format
     */
    public function test_iso_8601_date_format(): void
    {
        $data = array_merge($this->validData, ['date' => '2026-02-27T14:30:00Z']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Laravel's date validator accepts ISO 8601
        $this->assertFalse($validator->fails());
    }

    /**
     * Test EC-008: Date with timezone
     */
    public function test_date_with_timezone(): void
    {
        $data = array_merge($this->validData, ['date' => '2026-02-27 14:30:00+02:00']);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test EC-009: Boolean values are rejected for string fields
     */
    public function test_boolean_values_rejected_for_string_fields(): void
    {
        $data = array_merge($this->validData, ['title' => true]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }

    /**
     * Test EC-010: Float values are rejected for string fields
     */
    public function test_float_values_rejected_for_string_fields(): void
    {
        $data = array_merge($this->validData, ['title' => 3.14]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
    }
}
