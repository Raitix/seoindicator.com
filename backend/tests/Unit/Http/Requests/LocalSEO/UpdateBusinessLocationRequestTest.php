<?php

namespace Tests\Unit\Http\Requests\LocalSEO;

use App\Http\Requests\Api\V1\LocalSEO\UpdateBusinessLocationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * @Task TASK-35-13
 * @Description Update Business Location Request Validation Tests
 * @Tests 35+
 */
class UpdateBusinessLocationRequestTest extends TestCase
{
    use RefreshDatabase;

    protected UpdateBusinessLocationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateBusinessLocationRequest();
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
    // Optional Fields Tests
    // ==========================================

    /** @test */
    public function all_fields_are_optional(): void
    {
        $validator = $this->validate([]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function name_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Updated Name',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function address_is_optional(): void
    {
        $validator = $this->validate([
            'address' => '456 New St',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function phone_is_optional(): void
    {
        $validator = $this->validate([
            'phone' => '+1-555-123-4567',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function website_is_optional(): void
    {
        $validator = $this->validate([
            'website' => 'https://newsite.com',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function category_is_optional(): void
    {
        $validator = $this->validate([
            'category' => 'retail',
        ]);

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // Name Validation Tests
    // ==========================================

    /** @test */
    public function name_can_be_updated(): void
    {
        $validator = $this->validate([
            'name' => 'New Business Name',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function name_cannot_be_empty_if_provided(): void
    {
        $validator = $this->validate([
            'name' => '',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function name_must_be_string(): void
    {
        $validator = $this->validate([
            'name' => 12345,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function name_max_255_characters(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 256),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function name_accepts_unicode(): void
    {
        $validator = $this->validate([
            'name' => '新しい名前',
        ]);

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // Address Validation Tests
    // ==========================================

    /** @test */
    public function address_can_be_updated(): void
    {
        $validator = $this->validate([
            'address' => '456 New Street, Los Angeles, CA 90001',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function address_cannot_be_empty_if_provided(): void
    {
        $validator = $this->validate([
            'address' => '',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
    }

    /** @test */
    public function address_max_512_characters(): void
    {
        $validator = $this->validate([
            'address' => str_repeat('a', 513),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
    }

    // ==========================================
    // Phone Validation Tests
    // ==========================================

    /** @test */
    public function phone_can_be_cleared(): void
    {
        $validator = $this->validate([
            'phone' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function phone_must_be_valid_format_if_provided(): void
    {
        $validator = $this->validate([
            'phone' => 'invalid-phone',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    /** @test */
    public function phone_accepts_international_format(): void
    {
        $validator = $this->validate([
            'phone' => '+44-20-1234-5678',
        ]);

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // Website Validation Tests
    // ==========================================

    /** @test */
    public function website_can_be_cleared(): void
    {
        $validator = $this->validate([
            'website' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function website_must_be_valid_url_if_provided(): void
    {
        $validator = $this->validate([
            'website' => 'not-a-url',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('website', $validator->errors()->toArray());
    }

    /** @test */
    public function website_accepts_https(): void
    {
        $validator = $this->validate([
            'website' => 'https://newsite.com',
        ]);

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // Category Validation Tests
    // ==========================================

    /** @test */
    public function category_can_be_updated(): void
    {
        $validator = $this->validate([
            'category' => 'healthcare',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function category_must_be_string_if_provided(): void
    {
        $validator = $this->validate([
            'category' => 123,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    // ==========================================
    // Operating Hours Validation Tests
    // ==========================================

    /** @test */
    public function hours_can_be_updated(): void
    {
        $validator = $this->validate([
            'hours' => [
                'monday' => ['open' => '08:00', 'close' => '18:00'],
            ],
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function hours_can_be_cleared(): void
    {
        $validator = $this->validate([
            'hours' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function hours_must_be_array_if_provided(): void
    {
        $validator = $this->validate([
            'hours' => 'not-an-array',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('hours', $validator->errors()->toArray());
    }

    // ==========================================
    // Immutable Fields Tests
    // ==========================================

    /** @test */
    public function user_id_is_ignored(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'user_id' => 999,
        ]);

        // Should pass - user_id is ignored (not validated)
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function site_id_is_ignored(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'site_id' => 999,
        ]);

        // Should pass - site_id is ignored
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function gbp_tokens_are_ignored(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'gbp_access_token' => 'malicious-token',
            'gbp_refresh_token' => 'malicious-refresh',
        ]);

        // Should pass - tokens are ignored
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function is_gbp_connected_is_ignored(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'is_gbp_connected' => true,
        ]);

        // Should pass - is_gbp_connected is ignored
        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // Multiple Fields Tests
    // ==========================================

    /** @test */
    public function multiple_fields_can_be_updated(): void
    {
        $validator = $this->validate([
            'name' => 'New Name',
            'address' => 'New Address',
            'phone' => '+1-555-999-8888',
            'website' => 'https://newsite.com',
            'category' => 'retail',
        ]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function partial_update_passes(): void
    {
        $validator = $this->validate([
            'name' => 'Only Name Changed',
        ]);

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // XSS Prevention Tests
    // ==========================================

    /** @test */
    public function xss_in_name_rejected(): void
    {
        $validator = $this->validate([
            'name' => '<script>alert(1)</script>',
        ]);

        if ($validator->fails()) {
            $this->assertTrue(true);
        } else {
            $this->assertStringNotContainsString('<script>', $validator->validated()['name']);
        }
    }

    /** @test */
    public function xss_in_address_rejected(): void
    {
        $validator = $this->validate([
            'address' => '<img onerror=alert(1) src=x>',
        ]);

        if ($validator->fails()) {
            $this->assertTrue(true);
        } else {
            $this->assertStringNotContainsString('<img', $validator->validated()['address']);
        }
    }

    // ==========================================
    // Edge Cases Tests
    // ==========================================

    /** @test */
    public function empty_request_body_passes(): void
    {
        $validator = $this->validate([]);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function extra_fields_ignored(): void
    {
        $validator = $this->validate([
            'name' => 'Test',
            'unknown_field' => 'some value',
        ]);

        $this->assertFalse($validator->fails());
    }
}
