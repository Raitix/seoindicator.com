<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\CreateStripeIntentRequest;
use App\Models\CreditPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * CreateStripeIntentRequest Validation Test Suite
 *
 * Tests validation rules for the payment intent creation request.
 *
 * @see TASK-26-9
 * @see FEAT-026
 */
class CreateStripeIntentRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a test package for validation tests.
     */
    protected function createTestPackage(array $overrides = []): CreditPackage
    {
        return CreditPackage::factory()->create(array_merge([
            'name' => 'Basic',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'is_active' => true,
        ], $overrides));
    }

    // ============================================
    // Validation Rule Tests: package_id
    // ============================================

    /**
     * Test VAL-001: package_id is required.
     * 
     * @test
     */
    public function val_001_package_id_required(): void
    {
        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('package_id', $validator->errors()->toArray());
    }

    /**
     * Test VAL-002: package_id must be integer.
     * 
     * @test
     */
    public function val_002_package_id_must_be_integer(): void
    {
        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => 'abc',
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('package_id', $validator->errors()->toArray());
    }

    /**
     * Test VAL-003: package_id must exist in database.
     * 
     * @test
     */
    public function val_003_package_id_must_exist(): void
    {
        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => 99999,
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('package_id', $validator->errors()->toArray());
    }

    /**
     * Test VAL-004: package_id validates with existing package.
     * 
     * @test
     */
    public function val_004_package_id_validates_existing(): void
    {
        $package = $this->createTestPackage();

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test VAL-005: package_id accepts numeric string.
     * 
     * @test
     */
    public function val_005_package_id_accepts_numeric_string(): void
    {
        $package = $this->createTestPackage();

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => (string) $package->id,
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    // ============================================
    // Validation Rule Tests: return_url
    // ============================================

    /**
     * Test VAL-010: return_url is optional.
     * 
     * @test
     */
    public function val_010_return_url_optional(): void
    {
        $package = $this->createTestPackage();

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test VAL-011: return_url must be valid URL.
     * 
     * @test
     */
    public function val_011_return_url_must_be_valid_url(): void
    {
        $package = $this->createTestPackage();

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
            'return_url' => 'not-a-valid-url',
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('return_url', $validator->errors()->toArray());
    }

    /**
     * Test VAL-012: return_url accepts valid HTTP URL.
     * 
     * @test
     */
    public function val_012_return_url_accepts_http_url(): void
    {
        $package = $this->createTestPackage();

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
            'return_url' => 'http://example.com/success',
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test VAL-013: return_url accepts valid HTTPS URL.
     * 
     * @test
     */
    public function val_013_return_url_accepts_https_url(): void
    {
        $package = $this->createTestPackage();

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
            'return_url' => 'https://example.com/success',
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test VAL-014: return_url max length 500 characters.
     * 
     * @test
     */
    public function val_014_return_url_max_length(): void
    {
        $package = $this->createTestPackage();
        $longUrl = 'https://example.com/' . str_repeat('a', 500);

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
            'return_url' => $longUrl,
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('return_url', $validator->errors()->toArray());
    }

    // ============================================
    // Custom Validation Rule Tests
    // ============================================

    /**
     * Test VAL-020: Active package validation.
     * 
     * @test
     */
    public function val_020_active_package_validation(): void
    {
        $package = $this->createTestPackage(['is_active' => false]);

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
        ], $request->rules());

        // Package exists but is inactive - this is handled by controller, not request
        // The request validation should pass (package exists)
        $this->assertTrue($validator->passes());
    }

    // ============================================
    // Edge Cases
    // ============================================

    /**
     * Test VAL-030: Empty request fails validation.
     * 
     * @test
     */
    public function val_030_empty_request_fails(): void
    {
        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertCount(1, $validator->errors()->toArray()); // Only package_id required
    }

    /**
     * Test VAL-031: Extra fields are ignored.
     * 
     * @test
     */
    public function val_031_extra_fields_ignored(): void
    {
        $package = $this->createTestPackage();

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
            'extra_field' => 'some value',
            'another_field' => 123,
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test VAL-032: Negative package_id fails.
     * 
     * @test
     */
    public function val_032_negative_package_id_fails(): void
    {
        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => -1,
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('package_id', $validator->errors()->toArray());
    }

    /**
     * Test VAL-033: Zero package_id fails.
     * 
     * @test
     */
    public function val_033_zero_package_id_fails(): void
    {
        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => 0,
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('package_id', $validator->errors()->toArray());
    }

    /**
     * Test VAL-034: Float package_id fails.
     * 
     * @test
     */
    public function val_034_float_package_id_fails(): void
    {
        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => 1.5,
        ], $request->rules());

        $this->assertFalse($validator->passes());
    }

    /**
     * Test VAL-035: Null return_url passes.
     * 
     * @test
     */
    public function val_035_null_return_url_passes(): void
    {
        $package = $this->createTestPackage();

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
            'return_url' => null,
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test VAL-036: Empty string return_url passes.
     * 
     * @test
     */
    public function val_036_empty_return_url_passes(): void
    {
        $package = $this->createTestPackage();

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
            'return_url' => '',
        ], $request->rules());

        // Empty string should pass (nullable field)
        $this->assertTrue($validator->passes());
    }

    // ============================================
    // Security Tests
    // ============================================

    /**
     * Test VAL-040: SQL injection in package_id.
     * 
     * @test
     */
    public function val_040_sql_injection_package_id(): void
    {
        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => "1; DROP TABLE credit_packages; --",
        ], $request->rules());

        $this->assertFalse($validator->passes());
    }

    /**
     * Test VAL-041: XSS in return_url.
     * 
     * @test
     */
    public function val_041_xss_in_return_url(): void
    {
        $package = $this->createTestPackage();

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
            'return_url' => 'javascript:alert(1)',
        ], $request->rules());

        // javascript: protocol should fail URL validation
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('return_url', $validator->errors()->toArray());
    }

    /**
     * Test VAL-042: File protocol in return_url blocked.
     * 
     * @test
     */
    public function val_042_file_protocol_blocked(): void
    {
        $package = $this->createTestPackage();

        $request = new CreateStripeIntentRequest();
        $validator = Validator::make([
            'package_id' => $package->id,
            'return_url' => 'file:///etc/passwd',
        ], $request->rules());

        $this->assertFalse($validator->passes());
    }
}
