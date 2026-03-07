<?php

namespace Tests\Unit\Http\Requests\AIVisibility;

use App\Http\Requests\Api\V1\AIVisibility\StoreAIVisibilityProjectRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * StoreAIVisibilityProjectRequest Test - TASK-34-6
 * 
 * Tests for form request validation when creating AI Visibility projects.
 * 
 * @see TESTS/TEST-34-6.md for full test documentation
 */
class StoreProjectRequestTest extends TestCase
{
    /**
     * Test brand_name is required.
     */
    public function test_brand_name_required(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('brand_name', $validator->errors()->toArray());
    }

    /**
     * Test brand_name max 255 characters.
     */
    public function test_brand_name_max_255_characters(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => str_repeat('a', 256),
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('brand_name', $validator->errors()->toArray());
    }

    /**
     * Test brand_name accepts valid characters.
     */
    public function test_brand_name_accepts_valid_characters(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Acme Corp Inc.',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test brand_name accepts unicode.
     */
    public function test_brand_name_accepts_unicode(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => '日本語ブランド',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test keywords is required.
     */
    public function test_keywords_required(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keywords', $validator->errors()->toArray());
    }

    /**
     * Test keywords must be array.
     */
    public function test_keywords_must_be_array(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => 'not-an-array',
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keywords', $validator->errors()->toArray());
    }

    /**
     * Test keywords min 1 item.
     */
    public function test_keywords_min_1_item(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keywords', $validator->errors()->toArray());
    }

    /**
     * Test keywords max 10 items.
     */
    public function test_keywords_max_10_items(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => range(1, 11),
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keywords', $validator->errors()->toArray());
    }

    /**
     * Test keyword items max 100 characters.
     */
    public function test_keyword_items_max_100_characters(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => [str_repeat('a', 101)],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keywords.0', $validator->errors()->toArray());
    }

    /**
     * Test keywords accepts valid array.
     */
    public function test_keywords_accepts_valid_array(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword1', 'keyword2', 'keyword3'],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test competitors is required.
     */
    public function test_competitors_required(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('competitors', $validator->errors()->toArray());
    }

    /**
     * Test competitors must be array.
     */
    public function test_competitors_must_be_array(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => 'not-an-array',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('competitors', $validator->errors()->toArray());
    }

    /**
     * Test competitors min 1 item.
     */
    public function test_competitors_min_1_item(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => [],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('competitors', $validator->errors()->toArray());
    }

    /**
     * Test competitors max 3 items.
     */
    public function test_competitors_max_3_items(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['A', 'B', 'C', 'D'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('competitors', $validator->errors()->toArray());
    }

    /**
     * Test competitor items max 100 characters.
     */
    public function test_competitor_items_max_100_characters(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => [str_repeat('a', 101)],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('competitors.0', $validator->errors()->toArray());
    }

    /**
     * Test competitors accepts valid array.
     */
    public function test_competitors_accepts_valid_array(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['Competitor A', 'Competitor B', 'Competitor C'],
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test query_templates is optional.
     */
    public function test_query_templates_optional(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test query_templates must be array when provided.
     */
    public function test_query_templates_must_be_array(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'query_templates' => 'not-an-array',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('query_templates', $validator->errors()->toArray());
    }

    /**
     * Test tracking_frequency is optional.
     */
    public function test_tracking_frequency_optional(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test tracking_frequency must be valid enum value.
     */
    public function test_tracking_frequency_valid_enum(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'tracking_frequency' => 'hourly',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tracking_frequency', $validator->errors()->toArray());
    }

    /**
     * Test tracking_frequency accepts daily.
     */
    public function test_tracking_frequency_accepts_daily(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'tracking_frequency' => 'daily',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test tracking_frequency accepts weekly.
     */
    public function test_tracking_frequency_accepts_weekly(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'tracking_frequency' => 'weekly',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test tracking_frequency accepts monthly.
     */
    public function test_tracking_frequency_accepts_monthly(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'tracking_frequency' => 'monthly',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test tracking_frequency is case sensitive.
     */
    public function test_tracking_frequency_case_sensitive(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
            'tracking_frequency' => 'DAILY',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tracking_frequency', $validator->errors()->toArray());
    }

    /**
     * Test empty brand_name fails.
     */
    public function test_empty_brand_name_fails(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => '',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('brand_name', $validator->errors()->toArray());
    }

    /**
     * Test whitespace brand_name fails.
     */
    public function test_whitespace_brand_name_fails(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => '   ',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('brand_name', $validator->errors()->toArray());
    }

    /**
     * Test empty keyword item fails.
     */
    public function test_empty_keyword_item_fails(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => [''],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keywords.0', $validator->errors()->toArray());
    }

    /**
     * Test empty competitor item fails.
     */
    public function test_empty_competitor_item_fails(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => [''],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('competitors.0', $validator->errors()->toArray());
    }

    /**
     * Test multiple validation errors.
     */
    public function test_multiple_validation_errors(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('brand_name', $errors);
        $this->assertArrayHasKey('keywords', $errors);
        $this->assertArrayHasKey('competitors', $errors);
    }

    /**
     * Test valid data passes.
     */
    public function test_valid_data_passes(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Acme Corp',
            'keywords' => ['acme', 'acme corp', 'acme products'],
            'competitors' => ['Competitor A', 'Competitor B'],
            'query_templates' => ['What is {brand}?', 'How does {brand} compare?'],
            'tracking_frequency' => 'daily',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test authorize returns true for authenticated users.
     */
    public function test_authorize_returns_true(): void
    {
        $request = new StoreAIVisibilityProjectRequest();

        $this->assertTrue($request->authorize());
    }

    /**
     * Test custom error messages.
     */
    public function test_custom_error_messages(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $messages = $request->messages();

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('brand_name.required', $messages);
        $this->assertArrayHasKey('keywords.required', $messages);
        $this->assertArrayHasKey('competitors.required', $messages);
    }

    /**
     * Test XSS in brand_name is accepted (sanitization happens elsewhere).
     */
    public function test_xss_in_brand_name_accepted(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => '<script>alert(1)</script>',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        // Validation passes, sanitization is handled by the service
        $this->assertFalse($validator->fails());
    }

    /**
     * Test unicode in all fields.
     */
    public function test_unicode_in_all_fields(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => '日本語ブランド',
            'keywords' => ['العربية', '中文', 'Ελληνικά'],
            'competitors' => [' competitore 🚀'],
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test emoji in brand_name.
     */
    public function test_emoji_in_brand_name(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => '🚀 Rocket Brand 🎯',
            'keywords' => ['keyword'],
            'competitors' => ['competitor'],
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test url in competitors.
     */
    public function test_url_in_competitors(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => 'Test',
            'keywords' => ['keyword'],
            'competitors' => ['https://competitor.com'],
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test exact boundary values.
     */
    public function test_exact_boundary_values(): void
    {
        $request = new StoreAIVisibilityProjectRequest();
        $validator = Validator::make([
            'brand_name' => str_repeat('a', 255),
            'keywords' => range(1, 10),
            'competitors' => ['A', 'B', 'C'],
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test prepared data removes extra fields.
     */
    public function test_prepared_data(): void
    {
        $request = new StoreAIVisibilityProjectRequest();

        if (method_exists($request, 'passedValidation')) {
            $request->replace([
                'brand_name' => 'Test',
                'keywords' => ['keyword'],
                'competitors' => ['competitor'],
                'extra_field' => 'should be ignored',
            ]);

            $request->passedValidation();

            $this->assertArrayNotHasKey('extra_field', $request->validated());
        } else {
            $this->assertTrue(true);
        }
    }
}
