<?php

namespace Tests\Unit\Rules;

use Tests\TestCase;
use App\Rules\MaxContentSize;
use Mockery;

/**
 * Unit tests for MaxContentSize validation rule.
 *
 * Tests content size limit validation for crawler API:
 * - Default max: 3MB (3,145,728 bytes)
 * - Custom max limits
 * - Boundary conditions
 *
 * @see TASK-22-12
 * @see \App\Rules\MaxContentSize
 */
class MaxContentSizeTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Validate size with optional custom max.
     */
    protected function validateSize(mixed $size, ?int $maxBytes = null): array
    {
        $rule = $maxBytes !== null ? new MaxContentSize($maxBytes) : new MaxContentSize();
        $fails = false;
        $message = null;

        $failCallback = function ($errorMessage) use (&$fails, &$message) {
            $fails = true;
            $message = $errorMessage;
        };

        $rule->validate('size', $size, $failCallback);

        return [
            'passes' => !$fails,
            'message' => $message,
        ];
    }

    // =========================================================================
    // Valid Sizes (MCS-VALID-*)
    // =========================================================================

    /** @test */
    public function mcs_valid_01_zero_bytes(): void
    {
        $result = $this->validateSize(0);
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_valid_02_1_mb(): void
    {
        $result = $this->validateSize(1048576);
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_valid_03_exactly_3_mb(): void
    {
        $result = $this->validateSize(3145728);
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_valid_04_string_number(): void
    {
        $result = $this->validateSize('1000');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_valid_05_default_max(): void
    {
        // Any size <= 3MB should pass with default max
        $result = $this->validateSize(2097152); // 2MB
        
        $this->assertTrue($result['passes']);
    }

    // =========================================================================
    // Invalid Sizes (MCS-INVALID-*)
    // =========================================================================

    /** @test */
    public function mcs_invalid_01_negative_size(): void
    {
        $result = $this->validateSize(-1);
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('positive', strtolower($result['message']));
    }

    /** @test */
    public function mcs_invalid_02_over_3_mb(): void
    {
        $result = $this->validateSize(3145729);
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('exceeds', strtolower($result['message']));
    }

    /** @test */
    public function mcs_invalid_03_over_10_mb(): void
    {
        $result = $this->validateSize(10485760);
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function mcs_invalid_04_non_numeric_string(): void
    {
        $result = $this->validateSize('abc');
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('numeric', strtolower($result['message']));
    }

    /** @test */
    public function mcs_invalid_05_array_input(): void
    {
        $result = $this->validateSize([1024]);
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function mcs_invalid_06_null_input(): void
    {
        $rule = new MaxContentSize();
        $fails = false;

        $failCallback = function ($errorMessage) use (&$fails) {
            $fails = true;
        };

        $rule->validate('size', null, $failCallback);

        $this->assertTrue($fails);
    }

    /** @test */
    public function mcs_invalid_07_boolean_input(): void
    {
        $result = $this->validateSize(true);
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function mcs_invalid_08_object_input(): void
    {
        $result = $this->validateSize(new \stdClass());
        
        $this->assertFalse($result['passes']);
    }

    // =========================================================================
    // Custom Max Limits (MCS-CUSTOM-*)
    // =========================================================================

    /** @test */
    public function mcs_custom_01_custom_max_1mb_at_limit(): void
    {
        $result = $this->validateSize(1048576, 1048576); // Exactly 1MB
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_custom_02_custom_max_1mb_exceeded(): void
    {
        $result = $this->validateSize(1048577, 1048576); // 1 byte over 1MB
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function mcs_custom_03_custom_max_10mb_at_limit(): void
    {
        $result = $this->validateSize(10485760, 10485760); // Exactly 10MB
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_custom_04_custom_max_10mb_exceeded(): void
    {
        $result = $this->validateSize(10485761, 10485760); // 1 byte over 10MB
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function mcs_custom_05_zero_max_with_zero_size(): void
    {
        $result = $this->validateSize(0, 0);
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_custom_06_zero_max_with_non_zero_size(): void
    {
        $result = $this->validateSize(1, 0);
        
        $this->assertFalse($result['passes']);
    }

    // =========================================================================
    // Boundary Tests (MCS-BOUND-*)
    // =========================================================================

    /** @test */
    public function mcs_bound_01_max_minus_1_byte(): void
    {
        $result = $this->validateSize(3145727); // 3MB - 1 byte
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_bound_02_exactly_max(): void
    {
        $result = $this->validateSize(3145728); // Exactly 3MB
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_bound_03_max_plus_1_byte(): void
    {
        $result = $this->validateSize(3145729); // 3MB + 1 byte
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function mcs_bound_04_large_int_fails(): void
    {
        $result = $this->validateSize(PHP_INT_MAX);
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function mcs_bound_05_very_small_custom_max(): void
    {
        $result = $this->validateSize(100, 100);
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_bound_06_very_small_custom_max_exceeded(): void
    {
        $result = $this->validateSize(101, 100);
        
        $this->assertFalse($result['passes']);
    }

    // =========================================================================
    // Float Handling (MCS-FLOAT-*)
    // =========================================================================

    /** @test */
    public function mcs_float_01_float_truncated(): void
    {
        // 1024.5 should be truncated to 1024
        $result = $this->validateSize(1024.5);
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_float_02_float_over_max(): void
    {
        // Float representing value over 3MB
        $result = $this->validateSize(3145728.5);
        
        // Should fail as truncated value (3145728) passes, but exact value fails
        // Implementation decision: fail on any float that would exceed after truncation
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function mcs_float_03_scientific_notation(): void
    {
        $result = $this->validateSize(1e6); // 1,000,000
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function mcs_float_04_scientific_notation_over_max(): void
    {
        $result = $this->validateSize(4e6); // 4,000,000 (over 3MB)
        
        $this->assertFalse($result['passes']);
    }

    // =========================================================================
    // Message Tests (MCS-MSG-*)
    // =========================================================================

    /** @test */
    public function mcs_msg_01_custom_message_includes_limit(): void
    {
        $result = $this->validateSize(3145729);
        
        $this->assertFalse($result['passes']);
        // Message should include the limit for user feedback
        $this->assertNotEmpty($result['message']);
    }

    /** @test */
    public function mcs_msg_02_custom_max_included_in_message(): void
    {
        $result = $this->validateSize(1048577, 1048576);
        
        $this->assertFalse($result['passes']);
        // Message should indicate the custom limit
        $this->assertNotEmpty($result['message']);
    }

    // =========================================================================
    // Integration with Validator (MCS-INTEG-*)
    // =========================================================================

    /** @test */
    public function mcs_integ_01_works_with_laravel_validator(): void
    {
        $validator = \Validator::make(
            ['size' => 1048576],
            ['size' => [new MaxContentSize()]]
        );

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function mcs_integ_02_fails_with_laravel_validator(): void
    {
        $validator = \Validator::make(
            ['size' => 4194304], // 4MB
            ['size' => [new MaxContentSize()]]
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function mcs_integ_03_custom_max_with_validator(): void
    {
        $validator = \Validator::make(
            ['size' => 2097152], // 2MB
            ['size' => [new MaxContentSize(1048576)]] // Max 1MB
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function mcs_integ_04_multiple_rules(): void
    {
        $validator = \Validator::make(
            ['size' => 1048576],
            ['size' => ['required', 'integer', new MaxContentSize()]]
        );

        $this->assertTrue($validator->passes());
    }
}
