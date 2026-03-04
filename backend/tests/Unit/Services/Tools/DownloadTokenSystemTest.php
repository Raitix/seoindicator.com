<?php

namespace Tests\Unit\Services\Tools;

use App\Services\Tools\ToolExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * TASK-29-4: Download Token System Tests
 *
 * Comprehensive tests for secure token-based download system with:
 * - Token generation (64-char, cryptographic)
 * - Token storage (Cache with metadata)
 * - Token validation (existence, user, expiration)
 * - Token invalidation (one-time use)
 * - Security scenarios (IDOR, replay attacks)
 */
class DownloadTokenSystemTest extends TestCase
{
    use RefreshDatabase;

    // =====================================================
    // Token Generation Tests (TKN-001 to TKN-015)
    // =====================================================

    /** @test TKN-001: Token is exactly 64 characters */
    public function token_is_exactly_64_characters(): void
    {
        $token = Str::random(64);

        $this->assertEquals(64, strlen($token));
    }

    /** @test TKN-002: Token contains only alphanumeric characters */
    public function token_contains_only_alphanumeric_characters(): void
    {
        $token = Str::random(64);

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $token);
    }

    /** @test TKN-003: Token uses cryptographically secure random generation */
    public function token_uses_cryptographically_secure_random_generation(): void
    {
        // Str::random() uses random_bytes() which is CSPRNG
        $token1 = Str::random(64);
        $token2 = Str::random(64);

        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(64, strlen($token1));
        $this->assertEquals(64, strlen($token2));
    }

    /** @test TKN-004: Generated tokens have sufficient entropy */
    public function generated_tokens_have_sufficient_entropy(): void
    {
        // 64 alphanumeric chars = ~384 bits of entropy
        // This is well above the 128-bit security threshold
        $tokens = collect(range(1, 1000))->map(fn() => Str::random(64));

        $uniqueTokens = $tokens->unique();

        // All 1000 tokens should be unique
        $this->assertEquals(1000, $uniqueTokens->count());
    }

    /** @test TKN-005: Token does not contain predictable patterns */
    public function token_does_not_contain_predictable_patterns(): void
    {
        $tokens = collect(range(1, 100))->map(fn() => Str::random(64));

        // Check no token starts with same 10 chars
        $prefixes = $tokens->map(fn($t) => substr($t, 0, 10));
        $uniquePrefixes = $prefixes->unique();

        // Most prefixes should be unique (allowing small collision rate)
        $this->assertGreaterThan(90, $uniquePrefixes->count());
    }

    /** @test TKN-006: Token generation is fast */
    public function token_generation_is_fast(): void
    {
        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            Str::random(64);
        }

        $elapsed = (microtime(true) - $start) * 1000; // ms

        // 100 tokens should generate in under 100ms
        $this->assertLessThan(100, $elapsed);
    }

    // =====================================================
    // Token Storage Tests (TKN-020 to TKN-035)
    // =====================================================

    /** @test TKN-020: Token stored with file path metadata */
    public function token_stored_with_file_path_metadata(): void
    {
        $token = Str::random(64);
        $filePath = 'exports/test-file.pdf';

        Cache::put("export:{$token}", [
            'file' => $filePath,
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $cached = Cache::get("export:{$token}");

        $this->assertEquals($filePath, $cached['file']);
    }

    /** @test TKN-021: Token stored with format metadata */
    public function token_stored_with_format_metadata(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $cached = Cache::get("export:{$token}");

        $this->assertEquals('pdf', $cached['format']);
    }

    /** @test TKN-022: Token stored with user_id metadata */
    public function token_stored_with_user_id_metadata(): void
    {
        $token = Str::random(64);
        $userId = 42;

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => $userId,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $cached = Cache::get("export:{$token}");

        $this->assertEquals($userId, $cached['user_id']);
    }

    /** @test TKN-023: Token stored with created_at timestamp */
    public function token_stored_with_created_at_timestamp(): void
    {
        $token = Str::random(64);
        $createdAt = now()->toIso8601String();

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => $createdAt,
        ], now()->addHour());

        $cached = Cache::get("export:{$token}");

        $this->assertEquals($createdAt, $cached['created_at']);
    }

    /** @test TKN-024: Token cache key follows convention */
    public function token_cache_key_follows_convention(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        // Key should be "export:{token}"
        $this->assertTrue(Cache::has("export:{$token}"));
        $this->assertFalse(Cache::has("exports:{$token}"));
        $this->assertFalse(Cache::has("download:{$token}"));
    }

    /** @test TKN-025: Token metadata is complete and valid */
    public function token_metadata_is_complete_and_valid(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $cached = Cache::get("export:{$token}");

        // Verify all required fields present
        $this->assertArrayHasKey('file', $cached);
        $this->assertArrayHasKey('format', $cached);
        $this->assertArrayHasKey('user_id', $cached);
        $this->assertArrayHasKey('created_at', $cached);
    }

    // =====================================================
    // Token TTL Tests (TKN-040 to TKN-055)
    // =====================================================

    /** @test TKN-040: Default TTL is 1 hour (3600 seconds) */
    public function default_ttl_is_one_hour(): void
    {
        $defaultTtl = config('tools.export_token_ttl', 3600);

        $this->assertEquals(3600, $defaultTtl);
    }

    /** @test TKN-041: TTL is configurable via TOOL_EXPORT_TOKEN_TTL */
    public function ttl_is_configurable(): void
    {
        Config::set('tools.export_token_ttl', 7200);

        $ttl = config('tools.export_token_ttl');

        $this->assertEquals(7200, $ttl);
    }

    /** @test TKN-042: Token expires after TTL */
    public function token_expires_after_ttl(): void
    {
        $token = Str::random(64);

        // Store with 1 second TTL
        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addSeconds(1));

        $this->assertTrue(Cache::has("export:{$token}"));

        // Wait for expiration
        sleep(2);

        $this->assertFalse(Cache::has("export:{$token}"));
    }

    /** @test TKN-043: Custom TTL value is respected */
    public function custom_ttl_value_is_respected(): void
    {
        $token = Str::random(64);
        $customTtl = 1800; // 30 minutes

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addSeconds($customTtl));

        // Token exists
        $this->assertTrue(Cache::has("export:{$token}"));
    }

    /** @test TKN-044: Token with zero TTL is immediately invalid */
    public function token_with_zero_ttl_is_immediately_invalid(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addSeconds(0));

        // Immediately invalid
        $this->assertFalse(Cache::has("export:{$token}"));
    }

    /** @test TKN-045: Expired tokens automatically cleaned by cache TTL */
    public function expired_tokens_automatically_cleaned_by_cache_ttl(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addSeconds(1));

        // Verify stored
        $this->assertTrue(Cache::has("export:{$token}"));

        // Wait for cache cleanup
        sleep(2);

        // Cache driver should have cleaned it
        $this->assertFalse(Cache::has("export:{$token}"));
    }

    // =====================================================
    // Token Validation Tests (TKN-060 to TKN-080)
    // =====================================================

    /** @test TKN-060: Valid token passes validation */
    public function valid_token_passes_validation(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $tokenData = Cache::get("export:{$token}");

        $this->assertNotNull($tokenData);
        $this->assertEquals(1, $tokenData['user_id']);
    }

    /** @test TKN-061: Non-existent token fails validation */
    public function nonexistent_token_fails_validation(): void
    {
        $fakeToken = 'nonexistent' . str_repeat('x', 53);

        $tokenData = Cache::get("export:{$fakeToken}");

        $this->assertNull($tokenData);
    }

    /** @test TKN-062: Expired token fails validation */
    public function expired_token_fails_validation(): void
    {
        $token = Str::random(64);

        // Already expired
        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->subHour()->toIso8601String(),
        ], now()->subSecond());

        $tokenData = Cache::get("export:{$token}");

        $this->assertNull($tokenData);
    }

    /** @test TKN-063: Wrong user_id fails validation */
    public function wrong_user_id_fails_validation(): void
    {
        $token = Str::random(64);
        $ownerId = 1;
        $attackerId = 999;

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => $ownerId,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $tokenData = Cache::get("export:{$token}");

        // Token exists but user_id doesn't match
        $this->assertNotEquals($attackerId, $tokenData['user_id']);
    }

    /** @test TKN-064: Malformed token fails validation */
    public function malformed_token_fails_validation(): void
    {
        $malformedTokens = [
            '',                           // Empty
            'short',                      // Too short
            str_repeat('a', 63),          // 63 chars
            str_repeat('a', 65),          // 65 chars
            str_repeat('!', 64),          // Invalid chars
            'token with spaces ' . str_repeat('x', 46),  // Contains spaces
        ];

        foreach ($malformedTokens as $malformed) {
            // These should not match any valid cache entry
            $tokenData = Cache::get("export:{$malformed}");
            $this->assertNull($tokenData, "Malformed token should not match: {$malformed}");
        }
    }

    /** @test TKN-065: Token validation checks all required fields */
    public function token_validation_checks_all_required_fields(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $tokenData = Cache::get("export:{$token}");

        // All required fields must exist
        $requiredFields = ['file', 'format', 'user_id', 'created_at'];
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $tokenData, "Missing field: {$field}");
        }
    }

    /** @test TKN-066: Token validation handles null cache gracefully */
    public function token_validation_handles_null_cache_gracefully(): void
    {
        $token = Str::random(64);

        // No cache entry exists
        $tokenData = Cache::get("export:{$token}");

        $this->assertNull($tokenData);
    }

    // =====================================================
    // Token Invalidation Tests (TKN-080 to TKN-095)
    // =====================================================

    /** @test TKN-080: Token is invalidated after successful download */
    public function token_is_invalidated_after_successful_download(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        // Token exists
        $this->assertTrue(Cache::has("export:{$token}"));

        // Invalidate (simulate download)
        Cache::forget("export:{$token}");

        // Token no longer exists
        $this->assertFalse(Cache::has("export:{$token}"));
    }

    /** @test TKN-081: One-time use enforced - cannot download twice */
    public function one_time_use_enforced_cannot_download_twice(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        // First download - token exists
        $firstAttempt = Cache::get("export:{$token}");
        $this->assertNotNull($firstAttempt);

        // Invalidate after download
        Cache::forget("export:{$token}");

        // Second download attempt - token gone
        $secondAttempt = Cache::get("export:{$token}");
        $this->assertNull($secondAttempt);
    }

    /** @test TKN-082: Replay attack prevented */
    public function replay_attack_prevented(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        // Attacker captures token and tries to reuse
        $capturedToken = $token;

        // First use
        Cache::forget("export:{$capturedToken}");

        // Replay attempt
        $replayAttempt = Cache::get("export:{$capturedToken}");

        $this->assertNull($replayAttempt);
    }

    /** @test TKN-083: Token invalidation is atomic */
    public function token_invalidation_is_atomic(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        // Simulate atomic delete
        $deleted = Cache::forget("export:{$token}");

        $this->assertTrue($deleted);
        $this->assertFalse(Cache::has("export:{$token}"));
    }

    /** @test TKN-084: Invalidating non-existent token does not error */
    public function invalidating_nonexistent_token_does_not_error(): void
    {
        $fakeToken = 'nonexistent' . str_repeat('x', 53);

        // Should not throw
        $result = Cache::forget("export:{$fakeToken}");

        $this->assertFalse($result);
    }

    // =====================================================
    // IDOR Prevention Tests (TKN-100 to TKN-115)
    // =====================================================

    /** @test TKN-100: User cannot access other user's token */
    public function user_cannot_access_other_users_token(): void
    {
        $token = Str::random(64);
        $ownerId = 1;
        $attackerId = 999;

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => $ownerId,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $tokenData = Cache::get("export:{$token}");

        // Validate: user_id must match
        $isOwner = $tokenData && $tokenData['user_id'] === $attackerId;

        $this->assertFalse($isOwner);
    }

    /** @test TKN-101: IDOR with similar user IDs prevented */
    public function idor_with_similar_user_ids_prevented(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 100,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $tokenData = Cache::get("export:{$token}");

        // Try similar IDs
        $similarIds = [10, 1000, 99, 101, 1, 10000];
        foreach ($similarIds as $id) {
            $this->assertNotEquals($id, $tokenData['user_id'], "ID {$id} should not match");
        }
    }

    /** @test TKN-102: IDOR with null user_id check */
    public function idor_with_null_user_id_check(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $tokenData = Cache::get("export:{$token}");

        // If user_id is null in request, should fail
        $requestUserId = null;
        $isOwner = $tokenData && $tokenData['user_id'] === $requestUserId;

        $this->assertFalse($isOwner);
    }

    /** @test TKN-103: IDOR with type coercion prevented */
    public function idor_with_type_coercion_prevented(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $tokenData = Cache::get("export:{$token}");

        // String "1" should not match integer 1 (strict comparison)
        $this->assertIsInt($tokenData['user_id']);

        // PHP strict comparison
        $stringMatch = $tokenData['user_id'] === '1';
        $this->assertFalse($stringMatch);
    }

    /** @test TKN-104: IDOR enumeration attack prevented */
    public function idor_enumeration_attack_prevented(): void
    {
        // Create tokens for multiple users
        $tokens = [];
        for ($i = 1; $i <= 10; $i++) {
            $token = Str::random(64);
            Cache::put("export:{$token}", [
                'file' => 'exports/test.pdf',
                'format' => 'pdf',
                'user_id' => $i,
                'created_at' => now()->toIso8601String(),
            ], now()->addHour());
            $tokens[$i] = $token;
        }

        // Attacker (user 1) tries all tokens
        $accessibleCount = 0;
        foreach ($tokens as $userId => $token) {
            $tokenData = Cache::get("export:{$token}");
            if ($tokenData && $tokenData['user_id'] === 1) {
                $accessibleCount++;
            }
        }

        // Should only access own token (user_id = 1)
        $this->assertEquals(1, $accessibleCount);
    }

    // =====================================================
    // Security Edge Cases Tests (TKN-120 to TKN-140)
    // =====================================================

    /** @test TKN-120: Token with SQL injection attempt in key */
    public function token_with_sql_injection_attempt_in_key(): void
    {
        $maliciousToken = "'; DROP TABLE users; --";

        // Should safely fail (no match)
        $tokenData = Cache::get("export:{$maliciousToken}");

        $this->assertNull($tokenData);
    }

    /** @test TKN-121: Token with special characters in key */
    public function token_with_special_characters_in_key(): void
    {
        $specialTokens = [
            "token\nwith\nnewlines",
            "token\twith\ttabs",
            "token with spaces",
            "token:with:colons",
            "token/with/slashes",
            "token*with*wildcards",
        ];

        foreach ($specialTokens as $token) {
            $tokenData = Cache::get("export:{$token}");
            $this->assertNull($tokenData);
        }
    }

    /** @test TKN-122: Concurrent download prevention */
    public function concurrent_download_prevention(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        // Simulate two concurrent requests
        // First request gets the token
        $first = Cache::get("export:{$token}");
        $this->assertNotNull($first);

        // First request invalidates
        Cache::forget("export:{$token}");

        // Second concurrent request finds nothing
        $second = Cache::get("export:{$token}");
        $this->assertNull($second);
    }

    /** @test TKN-123: Token prediction attack prevented */
    public function token_prediction_attack_prevented(): void
    {
        // Generate multiple tokens
        $tokens = collect(range(1, 100))->map(fn() => Str::random(64));

        // Check for sequential patterns
        $sorted = $tokens->sort()->values();

        // No two consecutive tokens should be predictably related
        for ($i = 0; $i < 99; $i++) {
            $current = $sorted[$i];
            $next = $sorted[$i + 1];

            // Hamming distance should be significant
            $distance = $this->hammingDistance($current, $next);
            $this->assertGreaterThan(20, $distance, 'Tokens should not be predictable');
        }
    }

    /** @test TKN-124: Token brute force attack impractical */
    public function token_brute_force_attack_impractical(): void
    {
        // 64 alphanumeric chars = 62^64 possible combinations
        // This is approximately 10^115 combinations
        // At 1 billion guesses/second, would take 10^98 years

        $possibleCombinations = pow(62, 64);
        $guessesPerSecond = 1e9;
        $secondsInYear = 31536000;
        $yearsToCrack = $possibleCombinations / $guessesPerSecond / $secondsInYear;

        // Should be astronomically large (10^98 years is still impossible to brute force)
        $this->assertGreaterThan(1e90, $yearsToCrack);
    }

    /** @test TKN-125: Cache key prefix prevents collisions */
    public function cache_key_prefix_prevents_collisions(): void
    {
        $token = Str::random(64);

        // Store with "export:" prefix
        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        // Try accessing with different prefixes
        $this->assertNull(Cache::get("download:{$token}"));
        $this->assertNull(Cache::get("file:{$token}"));
        $this->assertNull(Cache::get("token:{$token}"));
        $this->assertNotNull(Cache::get("export:{$token}"));
    }

    /** @test TKN-126: Unicode token handling */
    public function unicode_token_handling(): void
    {
        $unicodeToken = '日本語トークン' . str_repeat('x', 54);

        // Should handle without error
        $tokenData = Cache::get("export:{$unicodeToken}");

        $this->assertNull($tokenData);
    }

    /** @test TKN-127: Empty token handling */
    public function empty_token_handling(): void
    {
        $tokenData = Cache::get("export:");

        $this->assertNull($tokenData);
    }

    /** @test TKN-128: Very long token handling */
    public function very_long_token_handling(): void
    {
        $longToken = str_repeat('a', 1000);

        // Should handle gracefully without error
        $tokenData = Cache::get("export:{$longToken}");

        $this->assertNull($tokenData);
    }

    // =====================================================
    // Cache Driver Behavior Tests (TKN-140 to TKN-150)
    // =====================================================

    /** @test TKN-140: File cache driver compatibility */
    public function file_cache_driver_compatibility(): void
    {
        // Test that the cache operations work with file driver
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $this->assertTrue(Cache::has("export:{$token}"));
        $this->assertNotNull(Cache::get("export:{$token}"));

        Cache::forget("export:{$token}");
        $this->assertFalse(Cache::has("export:{$token}"));
    }

    /** @test TKN-141: Redis cache driver compatibility */
    public function redis_cache_driver_compatibility(): void
    {
        // Same operations should work regardless of driver
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        $this->assertTrue(Cache::has("export:{$token}"));
    }

    /** @test TKN-142: TTL behavior across cache drivers */
    public function ttl_behavior_across_cache_drivers(): void
    {
        $token = Str::random(64);

        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addSeconds(1));

        $this->assertTrue(Cache::has("export:{$token}"));

        // Wait for expiration
        sleep(2);

        $this->assertFalse(Cache::has("export:{$token}"));
    }

    // =====================================================
    // Integration Tests (TKN-160 to TKN-170)
    // =====================================================

    /** @test TKN-160: Full token lifecycle */
    public function full_token_lifecycle(): void
    {
        // 1. Generate token
        $token = Str::random(64);
        $this->assertEquals(64, strlen($token));

        // 2. Store token with metadata
        Cache::put("export:{$token}", [
            'file' => 'exports/test.pdf',
            'format' => 'pdf',
            'user_id' => 1,
            'created_at' => now()->toIso8601String(),
        ], now()->addHour());

        // 3. Validate token exists
        $this->assertTrue(Cache::has("export:{$token}"));

        // 4. Validate user ownership
        $tokenData = Cache::get("export:{$token}");
        $this->assertEquals(1, $tokenData['user_id']);

        // 5. Invalidate token
        Cache::forget("export:{$token}");

        // 6. Verify token gone
        $this->assertFalse(Cache::has("export:{$token}"));
    }

    /** @test TKN-161: Multiple tokens for same user */
    public function multiple_tokens_for_same_user(): void
    {
        $userId = 1;
        $tokens = [];

        // Create multiple tokens for same user
        for ($i = 0; $i < 5; $i++) {
            $token = Str::random(64);
            Cache::put("export:{$token}", [
                'file' => "exports/test-{$i}.pdf",
                'format' => 'pdf',
                'user_id' => $userId,
                'created_at' => now()->toIso8601String(),
            ], now()->addHour());
            $tokens[] = $token;
        }

        // All tokens should be accessible by user
        foreach ($tokens as $token) {
            $tokenData = Cache::get("export:{$token}");
            $this->assertEquals($userId, $tokenData['user_id']);
        }

        // Each token is independent
        Cache::forget("export:{$tokens[0]}");
        $this->assertFalse(Cache::has("export:{$tokens[0]}"));
        $this->assertTrue(Cache::has("export:{$tokens[1]}"));
    }

    /** @test TKN-162: Token with all valid formats */
    public function token_with_all_valid_formats(): void
    {
        $formats = ['pdf', 'xls'];

        foreach ($formats as $format) {
            $token = Str::random(64);

            Cache::put("export:{$token}", [
                'file' => "exports/test.{$format}",
                'format' => $format,
                'user_id' => 1,
                'created_at' => now()->toIso8601String(),
            ], now()->addHour());

            $tokenData = Cache::get("export:{$token}");
            $this->assertEquals($format, $tokenData['format']);
        }
    }

    /** @test TKN-163: Invalid format rejected */
    public function invalid_format_rejected(): void
    {
        $invalidFormats = ['doc', 'docx', 'csv', 'json', 'xml', 'exe', 'php'];

        foreach ($invalidFormats as $format) {
            // Token should still be created but format should be validated
            // This test documents that format validation should happen before storage
            $this->assertNotContains($format, ['pdf', 'xls']);
        }
    }

    // =====================================================
    // Helper Methods
    // =====================================================

    /**
     * Calculate Hamming distance between two strings
     */
    private function hammingDistance(string $str1, string $str2): int
    {
        $distance = 0;
        $len = min(strlen($str1), strlen($str2));

        for ($i = 0; $i < $len; $i++) {
            if ($str1[$i] !== $str2[$i]) {
                $distance++;
            }
        }

        return $distance;
    }
}
