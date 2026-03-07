<?php

namespace Tests\Unit\Http\Requests\AIVisibility;

use App\Http\Requests\Api\V1\AIVisibility\UpdateAIVisibilityProjectRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * UpdateAIVisibilityProjectRequest Test - TASK-34-6
 * 
 * Tests for form request validation when updating AI Visibility projects.
 * Same validation as Store, but all fields are optional (partial updates).
 * 
 * @see TESTS/TEST-34-6.md for full test documentation
 */
class UpdateProjectRequestTest extends TestCase
{
    /**
     * Test brand_name is optional for update.
     */
    public function test_brand_name_optional(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test brand_name max 255 characters.
     */
    public function test_brand_name_max_255_characters(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => str_repeat('a', 256),
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('brand_name', $validator->errors()->toArray());
    }

    /**
     * Test brand_name can be updated alone.
     */
    public function test_brand_name_can_be_updated_alone(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Updated Brand',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test keywords is optional for update.
     */
    public function test_keywords_optional(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test keywords must be array when provided.
     */
    public function test_keywords_must_be_array(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'keywords' => 'not-an-array',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keywords', $validator->errors()->toArray());
    }

    /**
     * Test keywords min 1 item when provided.
     */
    public function test_keywords_min_1_item(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'keywords' => [],
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keywords', $validator->errors()->toArray());
    }

    /**
     * Test keywords max 10 items.
     */
    public function test_keywords_max_10_items(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'keywords' => range(1, 11),
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keywords', $validator->errors()->toArray());
    }

    /**
     * Test keywords can be updated alone.
     */
    public function test_keywords_can_be_updated_alone(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'keywords' => ['new keyword1', 'new keyword2'],
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test competitors is optional for update.
     */
    public function test_competitors_optional(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test competitors must be array when provided.
     */
    public function test_competitors_must_be_array(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'competitors' => 'not-an-array',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('competitors', $validator->errors()->toArray());
    }

    /**
     * Test competitors min 1 item when provided.
     */
    public function test_competitors_min_1_item(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'competitors' => [],
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('competitors', $validator->errors()->toArray());
    }

    /**
     * Test competitors max 3 items.
     */
    public function test_competitors_max_3_items(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'competitors' => ['A', 'B', 'C', 'D'],
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('competitors', $validator->errors()->toArray());
    }

    /**
     * Test competitors can be updated alone.
     */
    public function test_competitors_can_be_updated_alone(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'competitors' => ['New Competitor'],
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test is_active can be updated.
     */
    public function test_is_active_can_be_updated(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'is_active' => false,
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test is_active must be boolean.
     */
    public function test_is_active_must_be_boolean(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'is_active' => 'yes',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    /**
     * Test tracking_frequency is optional.
     */
    public function test_tracking_frequency_optional(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test tracking_frequency must be valid enum.
     */
    public function test_tracking_frequency_valid_enum(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'tracking_frequency' => 'hourly',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tracking_frequency', $validator->errors()->toArray());
    }

    /**
     * Test tracking_frequency accepts valid values.
     */
    public function test_tracking_frequency_accepts_valid_values(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();

        foreach (['daily', 'weekly', 'monthly'] as $frequency) {
            $validator = Validator::make([
                'tracking_frequency' => $frequency,
            ], $request->rules());

            $this->assertFalse($validator->fails(), "Frequency {$frequency} should be valid");
        }
    }

    /**
     * Test query_templates is optional.
     */
    public function test_query_templates_optional(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test query_templates must be array when provided.
     */
    public function test_query_templates_must_be_array(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'query_templates' => 'not-an-array',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('query_templates', $validator->errors()->toArray());
    }

    /**
     * Test query_templates can be updated.
     */
    public function test_query_templates_can_be_updated(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'query_templates' => ['New template?'],
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test empty brand_name fails.
     */
    public function test_empty_brand_name_fails(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => '',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('brand_name', $validator->errors()->toArray());
    }

    /**
     * Test multiple fields can be updated together.
     */
    public function test_multiple_fields_can_be_updated(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Updated Brand',
            'keywords' => ['new keyword'],
            'competitors' => ['New Competitor'],
            'is_active' => false,
            'tracking_frequency' => 'weekly',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test authorize returns true for authenticated users.
     */
    public function test_authorize_returns_true(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();

        $this->assertTrue($request->authorize());
    }

    /**
     * Test unicode in updated fields.
     */
    public function test_unicode_in_updated_fields(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => '日本語ブランド',
            'keywords' => ['العربية'],
            'competitors' => ['Ελληνικά'],
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test emoji in brand_name.
     */
    public function test_emoji_in_brand_name(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => '🚀 Brand',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test exact boundary values.
     */
    public function test_exact_boundary_values(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => str_repeat('a', 255),
            'keywords' => range(1, 10),
            'competitors' => ['A', 'B', 'C'],
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test user_id is ignored if provided.
     */
    public function test_user_id_ignored(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'user_id' => 999,
        ], $request->rules());

        // user_id should not be in rules, so validation passes
        $this->assertFalse($validator->fails());
    }

    /**
     * Test id is ignored if provided.
     */
    public function test_id_ignored(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'id' => 999,
        ], $request->rules());

        // id should not be in rules, so validation passes
        $this->assertFalse($validator->fails());
    }

    /**
     * Test extra fields are ignored.
     */
    public function test_extra_fields_ignored(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'extra_field' => 'should be ignored',
        ], $request->rules());

        // Extra fields don't cause validation to fail
        $this->assertFalse($validator->fails());
    }

    /**
     * Test custom error messages.
     */
    public function test_custom_error_messages(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();
        $messages = $request->messages();

        $this->assertIsArray($messages);
    }

    /**
     * Test valid partial update with single field.
     */
    public function test_valid_partial_update_single_field(): void
    {
        $request = new UpdateAIVisibilityProjectRequest();

        $fields = ['brand_name', 'keywords', 'competitors', 'is_active', 'tracking_frequency', 'query_templates'];
        $values = [
            'Updated Brand',
            ['keyword1', 'keyword2'],
            ['Competitor A'],
            false,
            'weekly',
            ['Template?'],
        ];

        foreach (array_combine($fields, $values) as $field => $value) {
            $validator = Validator::make([$field => $value], $request->rules());
            $this->assertFalse($validator->fails(), "Field {$field} should be valid");
        }
    }
}
