<?php

namespace Tests\Unit\Http\Requests\LocalSEO;

use App\Http\Requests\Api\V1\LocalSEO\StoreBusinessLocationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * @Task TASK-35-13
 * @Description Store Business Location Request Validation Tests
 * @Tests 45+
 */
class StoreBusinessLocationRequestTest extends TestCase
{
    use RefreshDatabase;

    protected StoreBusinessLocationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreBusinessLocationRequest();
    }

    /**
     * Get validation rules from request
     */
    protected function getRules(): array
    {
        return $this->request->rules();
    }

    /**
     * Validate data against rules
     */
    protected function validate(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, $this->getRules());
    }

    // ==========================================
    // Name Validation Tests
    // ==========================================

    /** @test */
    public function name_is_required(): void
    {
        $validator = $this->validate([
            'address' => '123 Main St',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function name_cannot_be_empty(): void
    {
        $validator = $this->validate([
            'name' => '',
            'address' => '123 Main St',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function name_must_be_string(): void
    {
        $validator = $this->validate([
            'name' => 12345,
            'address' => '123 Main St',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function name_max_255_characters(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 256),
            'address' => '123 Main St',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function name_255_characters_passes(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 255),
            'address' => '123 Main St',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function name_accepts_unicode(): void
    {
        $validator = $this->validate([
            'name' => '日本語ビジネス',
            'address' => '123 Main St',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function name_accepts_emoji(): void
    {
        $validator = $this->validate([
            'name' => '🍕 Pizza Place',
            'address' => '123 Main St',
        ]);

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // Address Validation Tests
    // ==========================================

    /** @test */
    public function address_is_required(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
    }

    /** @test */
    public function address_cannot_be_empty(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
    }

    /** @test */
    public function address_must_be_string(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => 12345,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
    }

    /** @test */
    public function address_max_512_characters(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => str_repeat('a', 513),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
    }

    /** @test */
    public function address_512_characters_passes(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => str_repeat('a', 512),
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function address_accepts_unicode(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '東京都渋谷区道玄坂1-2-3',
        ]);

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // Phone Validation Tests
    // ==========================================

    /** @test */
    public function phone_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function phone_can_be_null(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'phone' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function phone_must_be_valid_format(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'phone' => 'not-a-phone',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    /** @test */
    public function phone_accepts_international_format(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'phone' => '+1-555-123-4567',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function phone_accepts_simple_format(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'phone' => '5551234567',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function phone_accepts_with_parentheses(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'phone' => '(555) 123-4567',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function phone_accepts_japanese_format(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'phone' => '+81-3-1234-5678',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function phone_max_length(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'phone' => str_repeat('1', 51),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    // ==========================================
    // Website Validation Tests
    // ==========================================

    /** @test */
    public function website_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function website_can_be_null(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'website' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function website_must_be_valid_url(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'website' => 'not-a-url',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('website', $validator->errors()->toArray());
    }

    /** @test */
    public function website_accepts_https(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'website' => 'https://example.com',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function website_accepts_http(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'website' => 'http://example.com',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function website_accepts_path(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'website' => 'https://example.com/path/to/page',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function website_max_length(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'website' => 'https://' . str_repeat('a', 2049) . '.com',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('website', $validator->errors()->toArray());
    }

    // ==========================================
    // Category Validation Tests
    // ==========================================

    /** @test */
    public function category_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function category_can_be_null(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'category' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function category_must_be_string(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'category' => 123,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    /** @test */
    public function category_max_100_characters(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'category' => str_repeat('a', 101),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    /** @test */
    public function category_accepts_valid_values(): void
    {
        $validCategories = ['restaurant', 'retail', 'service', 'healthcare', 'professional_services'];

        foreach ($validCategories as $category) {
            $validator = $this->validate([
                'name' => 'Test Business',
                'address' => '123 Main St',
                'category' => $category,
            ]);

            $this->assertFalse($validator->fails(), "Category '{$category}' should be valid");
        }
    }

    // ==========================================
    // Operating Hours Validation Tests
    // ==========================================

    /** @test */
    public function hours_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function hours_must_be_array(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'hours' => 'not-an-array',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('hours', $validator->errors()->toArray());
    }

    /** @test */
    public function hours_accepts_valid_structure(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'hours' => [
                'monday' => ['open' => '09:00', 'close' => '17:00'],
                'tuesday' => ['open' => '09:00', 'close' => '17:00'],
            ],
        ]);

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // GBP Place ID Validation Tests
    // ==========================================

    /** @test */
    public function gbp_place_id_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function gbp_place_id_must_be_string(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'gbp_place_id' => 12345,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gbp_place_id', $validator->errors()->toArray());
    }

    /** @test */
    public function gbp_place_id_max_length(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'gbp_place_id' => str_repeat('a', 256),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gbp_place_id', $validator->errors()->toArray());
    }

    // ==========================================
    // XSS Prevention Tests
    // ==========================================

    /** @test */
    public function xss_in_name_rejected(): void
    {
        $validator = $this->validate([
            'name' => '<script>alert(1)</script>',
            'address' => '123 Main St',
        ]);

        // Should either fail or sanitize
        if ($validator->fails()) {
            $this->assertTrue(true);
        } else {
            // If passes, the XSS should be sanitized
            $this->assertStringNotContainsString('<script>', $validator->validated()['name']);
        }
    }

    /** @test */
    public function xss_in_address_rejected(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '<img onerror=alert(1) src=x>',
        ]);

        if ($validator->fails()) {
            $this->assertTrue(true);
        } else {
            $this->assertStringNotContainsString('<img', $validator->validated()['address']);
        }
    }

    // ==========================================
    // Complete Data Tests
    // ==========================================

    /** @test */
    public function complete_valid_data_passes(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St, New York, NY 10001',
            'phone' => '+1-555-123-4567',
            'website' => 'https://example.com',
            'category' => 'restaurant',
            'hours' => [
                'monday' => ['open' => '09:00', 'close' => '17:00'],
            ],
            'gbp_place_id' => 'places/ABC123',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function minimal_valid_data_passes(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function extra_fields_ignored(): void
    {
        $validator = $this->validate([
            'name' => 'Test Business',
            'address' => '123 Main St',
            'unknown_field' => 'some value',
            'another_unknown' => 123,
        ]);

        // Should pass - extra fields are ignored
        $this->assertFalse($validator->fails());
    }
}
