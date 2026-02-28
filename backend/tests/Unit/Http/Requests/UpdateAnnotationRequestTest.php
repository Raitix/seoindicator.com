<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StoreAnnotationRequest;
use App\Http\Requests\UpdateAnnotationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for UpdateAnnotationRequest validation.
 *
 * Tests verify that UpdateAnnotationRequest properly inherits
 * validation rules from StoreAnnotationRequest.
 *
 * @see TASK-9-6
 * @see TASK-9-9
 * @see TEST-9-9
 */
class UpdateAnnotationRequestTest extends TestCase
{
    private UpdateAnnotationRequest $request;
    private array $validData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->request = new UpdateAnnotationRequest();
        
        $this->validData = [
            'date' => '2026-02-27',
            'title' => 'Google Algorithm Update Impact',
            'description' => 'Position dropped from #5 to #12 after core update',
            'category' => 'algorithm_update',
        ];
    }

    // ============================================
    // Inheritance Tests (INH-001 to INH-005)
    // ============================================

    /**
     * Test INH-001: UpdateAnnotationRequest class exists
     */
    public function test_update_annotation_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateAnnotationRequest::class));
    }

    /**
     * Test INH-002: UpdateAnnotationRequest has authorize method
     */
    public function test_update_annotation_request_has_authorize_method(): void
    {
        $this->assertTrue(method_exists($this->request, 'authorize'));
    }

    /**
     * Test INH-003: UpdateAnnotationRequest has rules method
     */
    public function test_update_annotation_request_has_rules_method(): void
    {
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    /**
     * Test INH-004: authorize returns true
     */
    public function test_authorize_returns_true(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    /**
     * Test INH-005: rules returns array with expected keys
     */
    public function test_rules_returns_array_with_expected_keys(): void
    {
        $rules = $this->request->rules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('date', $rules);
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('category', $rules);
    }

    // ============================================
    // Same Rules as Store Tests (SAME-001 to SAME-005)
    // ============================================

    /**
     * Test SAME-001: Validation rules match StoreAnnotationRequest for common fields
     * 
     * Note: UpdateAnnotationRequest may not include project_id as it's typically
     * determined from the route/model binding during updates.
     */
    public function test_validation_rules_match_store_annotation_request(): void
    {
        $storeRequest = new StoreAnnotationRequest();
        $updateRequest = new UpdateAnnotationRequest();
        
        $storeRules = $storeRequest->rules();
        $updateRules = $updateRequest->rules();
        
        // UpdateAnnotationRequest should have all core annotation fields
        // (project_id may be excluded as it's typically from route binding)
        $coreFields = ['date', 'title', 'description', 'category'];
        foreach ($coreFields as $field) {
            $this->assertArrayHasKey(
                $field,
                $updateRules,
                "UpdateAnnotationRequest should have '{$field}' field"
            );
        }
        
        // Verify UpdateAnnotationRequest fields are subset of StoreAnnotationRequest
        foreach (array_keys($updateRules) as $field) {
            $this->assertArrayHasKey(
                $field,
                $storeRules,
                "UpdateAnnotationRequest field '{$field}' should exist in StoreAnnotationRequest"
            );
        }
    }

    /**
     * Test SAME-002: Same date validation
     */
    public function test_same_date_validation(): void
    {
        $storeRequest = new StoreAnnotationRequest();
        $updateRequest = new UpdateAnnotationRequest();
        
        // Test invalid date
        $data = array_merge($this->validData, ['date' => 'invalid-date']);
        
        $storeValidator = Validator::make($data, $storeRequest->rules());
        $updateValidator = Validator::make($data, $updateRequest->rules());
        
        $this->assertEquals($storeValidator->fails(), $updateValidator->fails());
    }

    /**
     * Test SAME-003: Same title validation
     */
    public function test_same_title_validation(): void
    {
        $storeRequest = new StoreAnnotationRequest();
        $updateRequest = new UpdateAnnotationRequest();
        
        // Test title over 255 chars
        $data = array_merge($this->validData, ['title' => str_repeat('A', 256)]);
        
        $storeValidator = Validator::make($data, $storeRequest->rules());
        $updateValidator = Validator::make($data, $updateRequest->rules());
        
        $this->assertEquals($storeValidator->fails(), $updateValidator->fails());
    }

    /**
     * Test SAME-004: Same description validation
     */
    public function test_same_description_validation(): void
    {
        $storeRequest = new StoreAnnotationRequest();
        $updateRequest = new UpdateAnnotationRequest();
        
        // Test description over TEXT limit
        $data = array_merge($this->validData, ['description' => str_repeat('A', 65536)]);
        
        $storeValidator = Validator::make($data, $storeRequest->rules());
        $updateValidator = Validator::make($data, $updateRequest->rules());
        
        $this->assertEquals($storeValidator->fails(), $updateValidator->fails());
    }

    /**
     * Test SAME-005: Same category validation
     */
    public function test_same_category_validation(): void
    {
        $storeRequest = new StoreAnnotationRequest();
        $updateRequest = new UpdateAnnotationRequest();
        
        // Test category over max length
        $data = array_merge($this->validData, ['category' => str_repeat('a', 51)]);
        
        $storeValidator = Validator::make($data, $storeRequest->rules());
        $updateValidator = Validator::make($data, $updateRequest->rules());
        
        $this->assertEquals($storeValidator->fails(), $updateValidator->fails());
    }

    // ============================================
    // Update-Specific Tests (UPD-001 to UPD-005)
    // ============================================

    /**
     * Test UPD-001: Full valid update passes
     */
    public function test_full_valid_update_passes(): void
    {
        $validator = Validator::make($this->validData, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test UPD-002: Partial update passes (fields are nullable for updates)
     * 
     * UpdateAnnotationRequest allows partial updates where only some fields
     * are provided. This is standard behavior for PATCH/PUT requests.
     */
    public function test_partial_update_passes(): void
    {
        $data = [
            'title' => 'Updated Title',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Partial updates should pass - only provided fields are validated
        // Required fields are only required when present in the request
        $this->assertFalse($validator->fails());
    }

    /**
     * Test UPD-003: Update with only optional field passes (with required fields)
     */
    public function test_update_with_optional_field_passes(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test UPD-004: Messages method exists and returns array
     */
    public function test_messages_method_exists_and_returns_array(): void
    {
        $this->assertTrue(method_exists($this->request, 'messages'));
        
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
    }

    /**
     * Test UPD-005: Messages have custom error text
     */
    public function test_messages_have_custom_error_text(): void
    {
        $messages = $this->request->messages();
        
        $this->assertArrayHasKey('date.required', $messages);
        $this->assertArrayHasKey('title.required', $messages);
        $this->assertArrayHasKey('title.max', $messages);
    }

    // ============================================
    // Security Tests (SEC-001 to SEC-003)
    // ============================================

    /**
     * Test SEC-001: SQL injection passes validation (handled by Eloquent)
     */
    public function test_sql_injection_passes_validation(): void
    {
        $data = array_merge($this->validData, ['title' => "' OR '1'='1"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test SEC-002: XSS passes validation (sanitized elsewhere)
     */
    public function test_xss_passes_validation(): void
    {
        $data = array_merge($this->validData, ['title' => "<script>alert('xss')</script>"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test SEC-003: CRLF injection passes validation (handled by app)
     */
    public function test_crlf_injection_passes_validation(): void
    {
        $data = array_merge($this->validData, ['title' => "title\r\nSet-Cookie: hacked=true"]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    // ============================================
    // Edge Cases (EDG-001 to EDG-003)
    // ============================================

    /**
     * Test EDG-001: Empty string values pass for nullable fields
     * 
     * For updates, fields are typically nullable to allow partial updates.
     * Empty strings are treated as null/absent values.
     */
    public function test_empty_values_for_nullable_fields_pass(): void
    {
        $data = [
            'date' => '',
            'title' => '',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        // Empty strings pass for nullable fields in update requests
        // The application layer handles merging with existing data
        $this->assertFalse($validator->fails());
    }

    /**
     * Test EDG-002: Unicode in all fields
     */
    public function test_unicode_in_all_fields(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => '更新标题 🚀',
            'description' => '描述内容 中文',
            'category' => 'other',
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /**
     * Test EDG-003: Max length boundaries
     */
    public function test_max_length_boundaries(): void
    {
        $data = [
            'date' => '2026-02-27',
            'title' => str_repeat('A', 255), // Exactly at max
            'description' => str_repeat('B', 65535), // Exactly at max
            'category' => str_repeat('c', 50), // Exactly at max
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }

    // ============================================
    // Integration Tests (INT-001 to INT-002)
    // ============================================

    /**
     * Test INT-001: Validation errors are returned correctly
     */
    public function test_validation_errors_returned_correctly(): void
    {
        $data = [
            'date' => 'not-a-date',
            'title' => str_repeat('A', 300), // Over max
        ];
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('date'));
        $this->assertTrue($errors->has('title'));
    }

    /**
     * Test INT-002: Valid data with extra fields passes
     */
    public function test_valid_data_with_extra_fields_passes(): void
    {
        $data = array_merge($this->validData, [
            'extra_field' => 'should be ignored',
            'another_field' => 12345,
        ]);
        
        $validator = Validator::make($data, $this->request->rules());
        
        $this->assertFalse($validator->fails());
    }
}
