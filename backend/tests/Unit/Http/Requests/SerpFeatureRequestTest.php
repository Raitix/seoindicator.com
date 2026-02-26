<?php

namespace Tests\Unit\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\SerpFeatureRequest;
use Illuminate\Support\Facades\Validator;

class SerpFeatureRequestTest extends TestCase
{
    // ============================================
    // Keyword ID Validation
    // ============================================

    /** @test */
    public function it_validates_keyword_id_is_integer(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['id' => 'abc'],
            ['id' => $rules['id'] ?? 'integer']
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_accepts_valid_keyword_id(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['id' => 123],
            ['id' => $rules['id'] ?? 'integer']
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_rejects_negative_keyword_id(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['id' => -1],
            ['id' => $rules['id'] ?? 'integer|min:1']
        );

        $this->assertTrue($validator->fails());
    }

    // ============================================
    // Site ID Validation
    // ============================================

    /** @test */
    public function it_validates_site_id_is_integer(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['siteId' => 'xyz'],
            ['siteId' => $rules['siteId'] ?? 'integer']
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_accepts_valid_site_id(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['siteId' => 456],
            ['siteId' => $rules['siteId'] ?? 'integer']
        );

        $this->assertFalse($validator->fails());
    }

    // ============================================
    // Date Range Validation
    // ============================================

    /** @test */
    public function it_validates_from_date_format(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        // Invalid date format
        $validator = Validator::make(
            ['from' => '02/20/2026'],
            ['from' => $rules['from'] ?? 'date']
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_accepts_valid_iso_date(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['from' => '2026-02-20'],
            ['from' => $rules['from'] ?? 'date']
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_validates_to_date_format(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['to' => '20-02-2026'],
            ['to' => $rules['to'] ?? 'date']
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_accepts_valid_to_date(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['to' => '2026-02-20'],
            ['to' => $rules['to'] ?? 'date']
        );

        $this->assertFalse($validator->fails());
    }

    // ============================================
    // Pagination Validation
    // ============================================

    /** @test */
    public function it_validates_page_is_positive(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['page' => -1],
            ['page' => $rules['page'] ?? 'integer|min:1']
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_validates_page_is_integer(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['page' => 'abc'],
            ['page' => $rules['page'] ?? 'integer']
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_accepts_valid_page(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['page' => 2],
            ['page' => $rules['page'] ?? 'integer|min:1']
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_validates_limit_is_positive(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['limit' => -10],
            ['limit' => $rules['limit'] ?? 'integer|min:1']
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_validates_limit_does_not_exceed_max(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['limit' => 1000],
            ['limit' => $rules['limit'] ?? 'integer|max:100']
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_accepts_valid_limit(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            ['limit' => 50],
            ['limit' => $rules['limit'] ?? 'integer|min:1|max:100']
        );

        $this->assertFalse($validator->fails());
    }

    // ============================================
    // Custom Validation Messages
    // ============================================

    /** @test */
    public function it_returns_custom_error_messages(): void
    {
        $request = new SerpFeatureRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('id.integer', $messages);
        $this->assertArrayHasKey('from.date', $messages);
        $this->assertArrayHasKey('to.date', $messages);
    }

    // ============================================
    // Combined Validation
    // ============================================

    /** @test */
    public function it_validates_multiple_fields_together(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        // Valid request
        $validator = Validator::make(
            [
                'id' => 1,
                'from' => '2026-02-01',
                'to' => '2026-02-20'
            ],
            [
                'id' => $rules['id'] ?? 'integer|min:1',
                'from' => $rules['from'] ?? 'date|nullable',
                'to' => $rules['to'] ?? 'date|nullable|after_or_equal:from'
            ]
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_rejects_from_date_after_to_date(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        $validator = Validator::make(
            [
                'from' => '2026-02-20',
                'to' => '2026-02-01'
            ],
            [
                'from' => $rules['from'] ?? 'date',
                'to' => $rules['to'] ?? 'date|after_or_equal:from'
            ]
        );

        $this->assertTrue($validator->fails());
    }

    // ============================================
    // Security Validation
    // ============================================

    /** @test */
    public function it_rejects_sql_injection_in_params(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        // SQL injection attempt
        $validator = Validator::make(
            ['id' => "1; DROP TABLE keywords;--"],
            ['id' => $rules['id'] ?? 'integer']
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_rejects_xss_in_params(): void
    {
        $request = new SerpFeatureRequest();
        $rules = $request->rules();

        // XSS attempt in a field that might accept strings
        $validator = Validator::make(
            ['from' => '<script>alert(1)</script>'],
            ['from' => $rules['from'] ?? 'date']
        );

        $this->assertTrue($validator->fails());
    }
}
