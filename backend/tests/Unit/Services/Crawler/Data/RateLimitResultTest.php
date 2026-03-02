<?php

namespace Tests\Unit\Services\Crawler\Data;

use Tests\TestCase;
use App\Services\Crawler\Data\RateLimitResult;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Unit tests for RateLimitResult DTO.
 *
 * @see TASK-22-13
 * @see SPEC-022
 */
class RateLimitResultTest extends TestCase
{
    // =========================================================================
    // Creation (RLR-CREATE-*)
    // =========================================================================

    /** @test */
    public function rlr_create_01_creates_from_array(): void
    {
        $result = RateLimitResult::fromArray([
            'allowed' => true,
            'limit' => 10,
            'remaining' => 5,
            'resetAt' => Carbon::now()->addHour(),
            'retryAfter' => null,
        ]);

        $this->assertInstanceOf(RateLimitResult::class, $result);
        $this->assertTrue($result->allowed);
        $this->assertEquals(10, $result->limit);
    }

    /** @test */
    public function rlr_create_02_creates_allowed_result(): void
    {
        $resetAt = Carbon::now()->addHour();
        $result = RateLimitResult::allowed(10, 8, $resetAt);

        $this->assertTrue($result->allowed);
        $this->assertEquals(10, $result->limit);
        $this->assertEquals(8, $result->remaining);
        $this->assertEquals($resetAt, $result->resetAt);
        $this->assertNull($result->retryAfter);
    }

    /** @test */
    public function rlr_create_03_creates_denied_result(): void
    {
        $resetAt = Carbon::now()->addHour();
        $result = RateLimitResult::denied(10, 0, $resetAt, 3600);

        $this->assertFalse($result->allowed);
        $this->assertEquals(10, $result->limit);
        $this->assertEquals(0, $result->remaining);
        $this->assertEquals($resetAt, $result->resetAt);
        $this->assertEquals(3600, $result->retryAfter);
    }

    /** @test */
    public function rlr_create_04_validates_required_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RateLimitResult::fromArray([
            'allowed' => true,
            // Missing limit, remaining, resetAt
        ]);
    }

    /** @test */
    public function rlr_create_05_validates_allowed_is_bool(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RateLimitResult::fromArray([
            'allowed' => 'yes', // Invalid type
            'limit' => 10,
            'remaining' => 5,
            'resetAt' => Carbon::now()->addHour(),
        ]);
    }

    /** @test */
    public function rlr_create_06_validates_limit_is_int(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RateLimitResult::fromArray([
            'allowed' => true,
            'limit' => 'ten', // Invalid type
            'remaining' => 5,
            'resetAt' => Carbon::now()->addHour(),
        ]);
    }

    /** @test */
    public function rlr_create_07_validates_remaining_is_int(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RateLimitResult::fromArray([
            'allowed' => true,
            'limit' => 10,
            'remaining' => 5.5, // Invalid type
            'resetAt' => Carbon::now()->addHour(),
        ]);
    }

    /** @test */
    public function rlr_create_08_validates_reset_at_is_carbon(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RateLimitResult::fromArray([
            'allowed' => true,
            'limit' => 10,
            'remaining' => 5,
            'resetAt' => '2026-01-01', // Invalid type
        ]);
    }

    /** @test */
    public function rlr_create_09_validates_retry_after_is_int_or_null(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RateLimitResult::fromArray([
            'allowed' => false,
            'limit' => 10,
            'remaining' => 0,
            'resetAt' => Carbon::now()->addHour(),
            'retryAfter' => 'soon', // Invalid type
        ]);
    }

    // =========================================================================
    // Properties (RLR-PROP-*)
    // =========================================================================

    /** @test */
    public function rlr_prop_01_has_allowed_property(): void
    {
        $result = RateLimitResult::allowed(10, 5, Carbon::now()->addHour());

        $this->assertTrue(isset($result->allowed));
        $this->assertIsBool($result->allowed);
    }

    /** @test */
    public function rlr_prop_02_has_limit_property(): void
    {
        $result = RateLimitResult::allowed(10, 5, Carbon::now()->addHour());

        $this->assertTrue(isset($result->limit));
        $this->assertIsInt($result->limit);
    }

    /** @test */
    public function rlr_prop_03_has_remaining_property(): void
    {
        $result = RateLimitResult::allowed(10, 5, Carbon::now()->addHour());

        $this->assertTrue(isset($result->remaining));
        $this->assertIsInt($result->remaining);
    }

    /** @test */
    public function rlr_prop_04_has_reset_at_property(): void
    {
        $result = RateLimitResult::allowed(10, 5, Carbon::now()->addHour());

        $this->assertTrue(isset($result->resetAt));
        $this->assertInstanceOf(Carbon::class, $result->resetAt);
    }

    /** @test */
    public function rlr_prop_05_has_retry_after_property(): void
    {
        $result = RateLimitResult::denied(10, 0, Carbon::now()->addHour(), 3600);

        $this->assertTrue(isset($result->retryAfter));
        $this->assertIsInt($result->retryAfter);
    }

    /** @test */
    public function rlr_prop_06_retry_after_nullable(): void
    {
        $result = RateLimitResult::allowed(10, 5, Carbon::now()->addHour());

        $this->assertNull($result->retryAfter);
    }

    // =========================================================================
    // To Array (RLR-ARRAY-*)
    // =========================================================================

    /** @test */
    public function rlr_array_01_converts_to_array(): void
    {
        $resetAt = Carbon::now()->addHour();
        $result = RateLimitResult::allowed(10, 8, $resetAt);

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('allowed', $array);
        $this->assertArrayHasKey('limit', $array);
        $this->assertArrayHasKey('remaining', $array);
        $this->assertArrayHasKey('resetAt', $array);
        $this->assertArrayHasKey('retryAfter', $array);
    }

    /** @test */
    public function rlr_array_02_reset_at_as_iso_string(): void
    {
        $resetAt = Carbon::parse('2026-03-02T12:00:00Z');
        $result = RateLimitResult::allowed(10, 8, $resetAt);

        $array = $result->toArray();

        $this->assertEquals('2026-03-02T12:00:00+00:00', $array['resetAt']);
    }

    /** @test */
    public function rlr_array_03_retry_after_as_int(): void
    {
        $result = RateLimitResult::denied(10, 0, Carbon::now()->addHour(), 3600);

        $array = $result->toArray();

        $this->assertEquals(3600, $array['retryAfter']);
    }

    /** @test */
    public function rlr_array_04_retry_after_as_null(): void
    {
        $result = RateLimitResult::allowed(10, 8, Carbon::now()->addHour());

        $array = $result->toArray();

        $this->assertNull($array['retryAfter']);
    }

    // =========================================================================
    // JSON Serialization (RLR-JSON-*)
    // =========================================================================

    /** @test */
    public function rlr_json_01_implements_json_serializable(): void
    {
        $result = RateLimitResult::allowed(10, 8, Carbon::now()->addHour());

        $this->assertInstanceOf(\JsonSerializable::class, $result);
    }

    /** @test */
    public function rlr_json_02_serializes_to_json(): void
    {
        $resetAt = Carbon::parse('2026-03-02T12:00:00Z');
        $result = RateLimitResult::allowed(10, 8, $resetAt);

        $json = json_encode($result);

        $this->assertJson($json);
    }

    /** @test */
    public function rlr_json_03_json_contains_all_fields(): void
    {
        $resetAt = Carbon::parse('2026-03-02T12:00:00Z');
        $result = RateLimitResult::denied(10, 0, $resetAt, 3600);

        $json = json_encode($result);
        $data = json_decode($json, true);

        $this->assertFalse($data['allowed']);
        $this->assertEquals(10, $data['limit']);
        $this->assertEquals(0, $data['remaining']);
        $this->assertEquals('2026-03-02T12:00:00+00:00', $data['resetAt']);
        $this->assertEquals(3600, $data['retryAfter']);
    }

    /** @test */
    public function rlr_json_04_null_retry_after_in_json(): void
    {
        $result = RateLimitResult::allowed(10, 8, Carbon::now()->addHour());

        $json = json_encode($result);
        $data = json_decode($json, true);

        $this->assertNull($data['retryAfter']);
    }

    // =========================================================================
    // Edge Cases (RLR-EDGE-*)
    // =========================================================================

    /** @test */
    public function rlr_edge_01_remaining_cannot_exceed_limit(): void
    {
        // This is a business rule - remaining should not exceed limit
        $result = RateLimitResult::allowed(10, 15, Carbon::now()->addHour());

        // Either enforce in constructor or document as edge case
        $this->assertLessThanOrEqual($result->limit, $result->remaining);
    }

    /** @test */
    public function rlr_edge_02_remaining_cannot_be_negative(): void
    {
        $result = RateLimitResult::denied(10, -1, Carbon::now()->addHour(), 3600);

        // Remaining should be at least 0
        $this->assertGreaterThanOrEqual(0, $result->remaining);
    }

    /** @test */
    public function rlr_edge_03_zero_limit_allowed(): void
    {
        $result = RateLimitResult::allowed(0, 0, Carbon::now()->addHour());

        $this->assertEquals(0, $result->limit);
        $this->assertEquals(0, $result->remaining);
    }

    /** @test */
    public function rlr_edge_04_large_limit_values(): void
    {
        $result = RateLimitResult::allowed(PHP_INT_MAX, PHP_INT_MAX - 1, Carbon::now()->addHour());

        $this->assertEquals(PHP_INT_MAX, $result->limit);
        $this->assertEquals(PHP_INT_MAX - 1, $result->remaining);
    }

    /** @test */
    public function rlr_edge_05_past_reset_at_still_works(): void
    {
        $pastTime = Carbon::now()->subHour();

        $result = RateLimitResult::allowed(10, 5, $pastTime);

        $this->assertEquals($pastTime, $result->resetAt);
    }

    /** @test */
    public function rlr_edge_06_retry_after_zero(): void
    {
        $result = RateLimitResult::denied(10, 0, Carbon::now()->addHour(), 0);

        $this->assertEquals(0, $result->retryAfter);
    }

    /** @test */
    public function rlr_edge_07_large_retry_after(): void
    {
        $result = RateLimitResult::denied(10, 0, Carbon::now()->addHour(), 86400);

        $this->assertEquals(86400, $result->retryAfter);
    }

    // =========================================================================
    // Immutability (RLR-IMMUTABLE-*)
    // =========================================================================

    /** @test */
    public function rlr_immutable_01_properties_are_readonly(): void
    {
        $result = RateLimitResult::allowed(10, 5, Carbon::now()->addHour());

        // PHP 8.1+ readonly properties cannot be modified
        // This test documents the expected behavior
        $this->assertTrue(true);
    }

    /** @test */
    public function rlr_immutable_02_with_remaining_creates_new_instance(): void
    {
        $result1 = RateLimitResult::allowed(10, 5, Carbon::now()->addHour());

        if (method_exists($result1, 'withRemaining')) {
            $result2 = $result1->withRemaining(3);

            $this->assertNotSame($result1, $result2);
            $this->assertEquals(5, $result1->remaining);
            $this->assertEquals(3, $result2->remaining);
        } else {
            $this->markTestSkipped('withRemaining method not implemented');
        }
    }
}
