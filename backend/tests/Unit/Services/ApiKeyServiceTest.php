<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ApiKeyService;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Unit tests for TASK-6-3: API Key Generation and Validation
 *
 * Tests the ApiKeyService responsible for:
 * - Secure API key generation with CSPRNG
 * - SHA-256 hashing for storage
 * - Timing-safe validation to prevent timing attacks
 * - Key prefix extraction for identification
 *
 * @see \App\Services\ApiKeyService
 */
class ApiKeyServiceTest extends TestCase
{
    protected ApiKeyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ApiKeyService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // KEY GENERATION TESTS (KG)
    // =========================================================================

    /**
     * KG-001: Generate returns array with required keys
     */
    public function test_generate_returns_array_with_required_keys(): void
    {
        $result = $this->service->generate();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('key', $result);
        $this->assertArrayHasKey('hash', $result);
        $this->assertArrayHasKey('prefix', $result);
    }

    /**
     * KG-002: Generated key has correct prefix
     */
    public function test_generated_key_has_correct_prefix(): void
    {
        $result = $this->service->generate();

        $this->assertStringStartsWith('sk_live_', $result['key']);
    }

    /**
     * KG-003: Generated key is base64url encoded
     */
    public function test_generated_key_is_base64url_encoded(): void
    {
        $result = $this->service->generate();

        // Extract the part after the prefix
        $keyBody = substr($result['key'], strlen('sk_live_'));

        // Base64url chars: A-Z, a-z, 0-9, -, _
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $keyBody);
    }

    /**
     * KG-004: Generated key has sufficient length
     */
    public function test_generated_key_has_sufficient_length(): void
    {
        $result = $this->service->generate();

        // 32 bytes -> 43 chars in base64url, plus 'sk_live_' prefix (8 chars) = 51 chars minimum
        $this->assertGreaterThanOrEqual(51, strlen($result['key']));
    }

    /**
     * KG-005: Generated hash is SHA-256
     */
    public function test_generated_hash_is_sha256(): void
    {
        $result = $this->service->generate();

        // SHA-256 produces 64 hex characters
        $this->assertEquals(64, strlen($result['hash']));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['hash']);
    }

    /**
     * KG-006: Generated hash matches key
     */
    public function test_generated_hash_matches_key(): void
    {
        $result = $this->service->generate();

        $expectedHash = hash('sha256', $result['key']);
        $this->assertEquals($expectedHash, $result['hash']);
    }

    /**
     * KG-007: Generated prefix is correct length
     */
    public function test_generated_prefix_is_correct_length(): void
    {
        $result = $this->service->generate();

        // Prefix is first 20 characters
        $this->assertEquals(20, strlen($result['prefix']));
    }

    /**
     * KG-008: Multiple calls generate unique keys
     */
    public function test_multiple_calls_generate_unique_keys(): void
    {
        $result1 = $this->service->generate();
        $result2 = $this->service->generate();

        $this->assertNotEquals($result1['key'], $result2['key']);
    }

    /**
     * KG-009: Multiple calls generate unique hashes
     */
    public function test_multiple_calls_generate_unique_hashes(): void
    {
        $result1 = $this->service->generate();
        $result2 = $this->service->generate();

        $this->assertNotEquals($result1['hash'], $result2['hash']);
    }

    /**
     * KG-010: Generated key contains sufficient entropy
     */
    public function test_generated_key_contains_sufficient_entropy(): void
    {
        $result = $this->service->generate();

        // Extract base64url part
        $keyBody = substr($result['key'], strlen('sk_live_'));

        // 32 bytes = 256 bits of entropy
        // Base64url encoding: 4 chars per 3 bytes, so 32 bytes = ~43 chars
        $this->assertGreaterThanOrEqual(43, strlen($keyBody));
    }

    /**
     * KG-011: Key has correct format structure
     */
    public function test_key_has_correct_format_structure(): void
    {
        $result = $this->service->generate();

        $this->assertMatchesRegularExpression('/^sk_live_[A-Za-z0-9_-]+$/', $result['key']);
    }

    /**
     * KG-012: Hash is lowercase hex
     */
    public function test_hash_is_lowercase_hex(): void
    {
        $result = $this->service->generate();

        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $result['hash']);
        $this->assertEquals(strtolower($result['hash']), $result['hash']);
    }

    /**
     * KG-013: Prefix matches key start
     */
    public function test_prefix_matches_key_start(): void
    {
        $result = $this->service->generate();

        $expectedPrefix = substr($result['key'], 0, 20);
        $this->assertEquals($expectedPrefix, $result['prefix']);
    }

    /**
     * KG-015: Key does not contain padding chars
     */
    public function test_key_does_not_contain_padding_chars(): void
    {
        $result = $this->service->generate();

        $this->assertStringNotContainsString('=', $result['key']);
    }

    // =========================================================================
    // HASH METHOD TESTS (HM)
    // =========================================================================

    /**
     * HM-001: Hash returns SHA-256 hex string
     */
    public function test_hash_returns_sha256_hex_string(): void
    {
        $hash = $this->service->hash('test_key');

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    /**
     * HM-002: Hash is deterministic
     */
    public function test_hash_is_deterministic(): void
    {
        $key = 'sk_live_testkey123';

        $hash1 = $this->service->hash($key);
        $hash2 = $this->service->hash($key);

        $this->assertEquals($hash1, $hash2);
    }

    /**
     * HM-003: Hash different keys produce different hashes
     */
    public function test_hash_different_keys_produce_different_hashes(): void
    {
        $hash1 = $this->service->hash('key1');
        $hash2 = $this->service->hash('key2');

        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * HM-004: Hash handles empty string
     */
    public function test_hash_handles_empty_string(): void
    {
        $hash = $this->service->hash('');

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    /**
     * HM-005: Hash handles special characters
     */
    public function test_hash_handles_special_characters(): void
    {
        $hash = $this->service->hash('key!@#$%^&*()');

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    /**
     * HM-006: Hash handles unicode
     */
    public function test_hash_handles_unicode(): void
    {
        $hash = $this->service->hash('key日本語🗝️');

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    /**
     * HM-007: Hash handles very long key
     */
    public function test_hash_handles_very_long_key(): void
    {
        $longKey = str_repeat('a', 10000);
        $hash = $this->service->hash($longKey);

        $this->assertEquals(64, strlen($hash));
    }

    /**
     * HM-008: Hash matches generate hash
     */
    public function test_hash_matches_generate_hash(): void
    {
        $result = $this->service->generate();
        $hash = $this->service->hash($result['key']);

        $this->assertEquals($result['hash'], $hash);
    }

    /**
     * HM-010: Hash output length is constant
     */
    public function test_hash_output_length_is_constant(): void
    {
        $hashes = [
            $this->service->hash(''),
            $this->service->hash('a'),
            $this->service->hash('short'),
            $this->service->hash(str_repeat('long', 100)),
        ];

        foreach ($hashes as $hash) {
            $this->assertEquals(64, strlen($hash));
        }
    }

    // =========================================================================
    // VALIDATION TESTS (VL)
    // =========================================================================

    /**
     * VL-001: Validate correct key
     */
    public function test_validate_correct_key(): void
    {
        $result = $this->service->generate();

        $isValid = $this->service->validate($result['key'], $result['hash']);

        $this->assertTrue($isValid);
    }

    /**
     * VL-002: Validate incorrect key
     */
    public function test_validate_incorrect_key(): void
    {
        $result = $this->service->generate();
        $wrongKey = 'sk_live_wrongkey123456789012345678901234567890';

        $isValid = $this->service->validate($wrongKey, $result['hash']);

        $this->assertFalse($isValid);
    }

    /**
     * VL-003: Validate is timing-safe
     * 
     * This test verifies that hash_equals is used internally.
     * We can't directly test timing, but we can verify the method
     * handles both match and no-match cases correctly.
     */
    public function test_validate_is_timing_safe(): void
    {
        $result = $this->service->generate();

        // Both should work without errors
        $validResult = $this->service->validate($result['key'], $result['hash']);
        $invalidResult = $this->service->validate('wrong_key', $result['hash']);

        $this->assertTrue($validResult);
        $this->assertFalse($invalidResult);
    }

    /**
     * VL-004: Validate with empty key
     */
    public function test_validate_with_empty_key(): void
    {
        $result = $this->service->generate();

        $isValid = $this->service->validate('', $result['hash']);

        $this->assertFalse($isValid);
    }

    /**
     * VL-005: Validate with empty hash
     */
    public function test_validate_with_empty_hash(): void
    {
        $result = $this->service->generate();

        $isValid = $this->service->validate($result['key'], '');

        $this->assertFalse($isValid);
    }

    /**
     * VL-006: Validate with both empty
     */
    public function test_validate_with_both_empty(): void
    {
        $isValid = $this->service->validate('', '');

        $this->assertFalse($isValid);
    }

    /**
     * VL-009: Validate similar keys
     */
    public function test_validate_similar_keys(): void
    {
        $result = $this->service->generate();

        // Modify one character
        $similarKey = substr_replace($result['key'], 'X', -1, 1);

        $isValid = $this->service->validate($similarKey, $result['hash']);

        $this->assertFalse($isValid);
    }

    /**
     * VL-010: Validate partial key
     */
    public function test_validate_partial_key(): void
    {
        $result = $this->service->generate();
        $partialKey = substr($result['key'], 0, 20);

        $isValid = $this->service->validate($partialKey, $result['hash']);

        $this->assertFalse($isValid);
    }

    /**
     * VL-011: Validate key with extra chars
     */
    public function test_validate_key_with_extra_chars(): void
    {
        $result = $this->service->generate();
        $keyWithExtra = $result['key'] . 'extra';

        $isValid = $this->service->validate($keyWithExtra, $result['hash']);

        $this->assertFalse($isValid);
    }

    /**
     * VL-012: Validate handles different hash formats
     */
    public function test_validate_handles_different_hash_formats(): void
    {
        $result = $this->service->generate();

        // Invalid hash format (not 64 hex chars)
        $isValid = $this->service->validate($result['key'], 'invalid_hash');

        $this->assertFalse($isValid);
    }

    /**
     * VL-013: Validate is case-sensitive
     */
    public function test_validate_is_case_sensitive(): void
    {
        $result = $this->service->generate();
        $uppercaseKey = strtoupper($result['key']);

        $isValid = $this->service->validate($uppercaseKey, $result['hash']);

        $this->assertFalse($isValid);
    }

    /**
     * VL-015: Validate with whitespace
     */
    public function test_validate_with_whitespace(): void
    {
        $result = $this->service->generate();
        $keyWithWhitespace = ' ' . $result['key'] . ' ';

        $isValid = $this->service->validate($keyWithWhitespace, $result['hash']);

        $this->assertFalse($isValid);
    }

    // =========================================================================
    // PREFIX EXTRACTION TESTS (PX)
    // =========================================================================

    /**
     * PX-001: Get prefix from valid key
     */
    public function test_get_prefix_from_valid_key(): void
    {
        $key = 'sk_live_ABCdefGHIjklMNOpqrSTUvwxYZ12345678901234';

        $prefix = $this->service->getPrefix($key);

        $this->assertEquals(20, strlen($prefix));
        $this->assertEquals('sk_live_ABCdefGHIjkl', $prefix);
    }

    /**
     * PX-002: Get prefix from short key
     */
    public function test_get_prefix_from_short_key(): void
    {
        $shortKey = 'sk_live_abc';

        $prefix = $this->service->getPrefix($shortKey);

        $this->assertEquals($shortKey, $prefix);
    }

    /**
     * PX-003: Get prefix from key without prefix
     */
    public function test_get_prefix_from_key_without_prefix(): void
    {
        $keyWithoutPrefix = 'some_random_key_without_prefix_123456789';

        $prefix = $this->service->getPrefix($keyWithoutPrefix);

        $this->assertLessThanOrEqual(20, strlen($prefix));
    }

    /**
     * PX-004: Get prefix from empty string
     */
    public function test_get_prefix_from_empty_string(): void
    {
        $prefix = $this->service->getPrefix('');

        $this->assertEquals('', $prefix);
    }

    /**
     * PX-005: Get prefix length is consistent
     */
    public function test_get_prefix_length_is_consistent(): void
    {
        $keys = [
            'sk_live_ABCdefGHIjklMNOpqrSTUvwxYZ12345678901234',
            'sk_live_different_key',
            'short',
            str_repeat('a', 100),
        ];

        foreach ($keys as $key) {
            $prefix = $this->service->getPrefix($key);
            $this->assertLessThanOrEqual(20, strlen($prefix));
        }
    }

    /**
     * PX-006: Get prefix matches generate
     */
    public function test_get_prefix_matches_generate(): void
    {
        $result = $this->service->generate();

        $prefix = $this->service->getPrefix($result['key']);

        $this->assertEquals($result['prefix'], $prefix);
    }

    // =========================================================================
    // FORMAT VALIDATION TESTS (FV)
    // =========================================================================

    /**
     * FV-001: Valid format with correct prefix
     */
    public function test_valid_format_with_correct_prefix(): void
    {
        $result = $this->service->generate();

        $isValid = $this->service->isValidFormat($result['key']);

        $this->assertTrue($isValid);
    }

    /**
     * FV-002: Invalid format without prefix
     */
    public function test_invalid_format_without_prefix(): void
    {
        $isValid = $this->service->isValidFormat('abc123_without_prefix');

        $this->assertFalse($isValid);
    }

    /**
     * FV-003: Invalid format with wrong prefix
     */
    public function test_invalid_format_with_wrong_prefix(): void
    {
        $key = 'pk_live_ABCdefGHIjklMNOpqrSTUvwxYZ12345678901234';

        $isValid = $this->service->isValidFormat($key);

        $this->assertFalse($isValid);
    }

    /**
     * FV-004: Invalid format with spaces
     */
    public function test_invalid_format_with_spaces(): void
    {
        $key = 'sk_live_ abc123';

        $isValid = $this->service->isValidFormat($key);

        $this->assertFalse($isValid);
    }

    /**
     * FV-006: Empty string
     */
    public function test_valid_format_empty_string(): void
    {
        $isValid = $this->service->isValidFormat('');

        $this->assertFalse($isValid);
    }

    /**
     * FV-007: Null input
     */
    public function test_valid_format_null_input(): void
    {
        $isValid = $this->service->isValidFormat(null);

        $this->assertFalse($isValid);
    }

    /**
     * FV-009: Valid format allows base64url chars
     */
    public function test_valid_format_allows_base64url_chars(): void
    {
        $key = 'sk_live_ABCdef123_-xyz';

        $isValid = $this->service->isValidFormat($key);

        $this->assertTrue($isValid);
    }

    /**
     * FV-010: Valid format rejects base64 padding
     */
    public function test_valid_format_rejects_base64_padding(): void
    {
        $key = 'sk_live_abc123=';

        $isValid = $this->service->isValidFormat($key);

        $this->assertFalse($isValid);
    }

    /**
     * FV-012: Key with only prefix
     */
    public function test_valid_format_key_with_only_prefix(): void
    {
        $key = 'sk_live_';

        $isValid = $this->service->isValidFormat($key);

        $this->assertFalse($isValid);
    }

    /**
     * FV-014: Uppercase prefix rejected
     */
    public function test_valid_format_uppercase_prefix_rejected(): void
    {
        $key = 'SK_LIVE_abc123';

        $isValid = $this->service->isValidFormat($key);

        $this->assertFalse($isValid);
    }

    // =========================================================================
    // SECURITY TESTS (SC)
    // =========================================================================

    /**
     * SC-001: Timing attack prevention
     * 
     * This test verifies that validation times are similar for
     * matching and non-matching keys. Due to timing variability,
     * we allow a reasonable margin.
     */
    public function test_timing_attack_prevention(): void
    {
        $result = $this->service->generate();
        $iterations = 100;

        // Time valid key validation
        $startValid = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->service->validate($result['key'], $result['hash']);
        }
        $timeValid = microtime(true) - $startValid;

        // Time invalid key validation
        $startInvalid = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->service->validate('invalid_key', $result['hash']);
        }
        $timeInvalid = microtime(true) - $startInvalid;

        // Times should be within 50% of each other (generous margin)
        $ratio = $timeValid / max($timeInvalid, 0.000001);
        $this->assertGreaterThan(0.5, $ratio);
        $this->assertLessThan(2.0, $ratio);
    }

    /**
     * SC-004: Entropy verification
     */
    public function test_entropy_verification(): void
    {
        // Generate many keys and check for uniqueness
        $keys = [];
        for ($i = 0; $i < 1000; $i++) {
            $result = $this->service->generate();
            $keys[] = $result['key'];
        }

        $uniqueKeys = array_unique($keys);
        $this->assertCount(1000, $uniqueKeys, 'All generated keys should be unique');
    }

    /**
     * SC-005: No plaintext storage
     */
    public function test_no_plaintext_storage(): void
    {
        $result = $this->service->generate();

        // Verify the key and hash are different
        $this->assertNotEquals($result['key'], $result['hash']);
    }

    /**
     * SC-007: Random generation uses CSPRNG
     * 
     * We can't directly test the random source, but we can verify
     * that the generated keys have sufficient randomness by checking
     * uniqueness and distribution.
     */
    public function test_random_generation_uses_csprng(): void
    {
        $keys = [];
        for ($i = 0; $i < 100; $i++) {
            $result = $this->service->generate();
            $keys[] = $result['key'];
        }

        // All keys should be unique
        $uniqueKeys = array_unique($keys);
        $this->assertCount(100, $uniqueKeys);

        // Keys should have varied characters (not all same)
        $keyBodies = array_map(fn($k) => substr($k, 8), $keys);
        $charCounts = [];
        foreach ($keyBodies as $body) {
            for ($i = 0; $i < strlen($body); $i++) {
                $char = $body[$i];
                $charCounts[$char] = ($charCounts[$char] ?? 0) + 1;
            }
        }

        // Should have at least 10 different characters across 100 keys
        $this->assertGreaterThan(10, count($charCounts));
    }

    /**
     * SC-010: No information leakage
     */
    public function test_no_information_leakage(): void
    {
        $result = $this->service->generate();

        // All validation failures should return false (same result)
        $results = [
            $this->service->validate('', $result['hash']),
            $this->service->validate('wrong', $result['hash']),
            $this->service->validate(str_repeat('a', 1000), $result['hash']),
            $this->service->validate('sk_live_', $result['hash']),
        ];

        foreach ($results as $isValid) {
            $this->assertFalse($isValid);
        }
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Generate multiple keys rapidly
     */
    public function test_generate_multiple_keys_rapidly(): void
    {
        $keys = [];
        for ($i = 0; $i < 1000; $i++) {
            $result = $this->service->generate();
            $keys[] = $result['key'];
        }

        $uniqueKeys = array_unique($keys);
        $this->assertCount(1000, $uniqueKeys);
    }

    /**
     * EC-002: Hash same key many times
     */
    public function test_hash_same_key_many_times(): void
    {
        $key = 'test_key_for_consistency';
        $hashes = [];

        for ($i = 0; $i < 1000; $i++) {
            $hashes[] = $this->service->hash($key);
        }

        $uniqueHashes = array_unique($hashes);
        $this->assertCount(1, $uniqueHashes);
    }

    /**
     * EC-003: Validate with very long hash
     */
    public function test_validate_with_very_long_hash(): void
    {
        $result = $this->service->generate();
        $longHash = str_repeat('a', 1000);

        $isValid = $this->service->validate($result['key'], $longHash);

        $this->assertFalse($isValid);
    }

    /**
     * EC-006: Hash with mixed case
     */
    public function test_validate_with_mixed_case_hash(): void
    {
        $result = $this->service->generate();
        $mixedCaseHash = strtoupper($result['hash']);

        // Should handle (either work or return false, not error)
        $isValid = $this->service->validate($result['key'], $mixedCaseHash);

        // Hash comparison should be case-insensitive or convert to lowercase
        // Either true (if normalized) or false (if strict), but no error
        $this->assertIsBool($isValid);
    }

    // =========================================================================
    // INTEGRATION TESTS (IT)
    // =========================================================================

    /**
     * IT-001: Full generation and validation cycle
     */
    public function test_full_generation_and_validation_cycle(): void
    {
        $result = $this->service->generate();

        // Validate the generated key against its hash
        $isValid = $this->service->validate($result['key'], $result['hash']);

        $this->assertTrue($isValid);
    }

    /**
     * IT-002: Generate, store hash, validate later
     */
    public function test_generate_store_hash_validate_later(): void
    {
        // Generate
        $result = $this->service->generate();

        // Simulate "storage" (just keeping the hash)
        $storedHash = $result['hash'];

        // Validate later
        $isValid = $this->service->validate($result['key'], $storedHash);

        $this->assertTrue($isValid);
    }

    /**
     * IT-003: Wrong key validation fails
     */
    public function test_wrong_key_validation_fails(): void
    {
        $result1 = $this->service->generate();
        $result2 = $this->service->generate();

        // Try to validate key1 with hash2
        $isValid = $this->service->validate($result1['key'], $result2['hash']);

        $this->assertFalse($isValid);
    }

    /**
     * IT-004: Multiple keys same user
     */
    public function test_multiple_keys_same_user(): void
    {
        $keys = [];
        for ($i = 0; $i < 5; $i++) {
            $result = $this->service->generate();
            $keys[] = $result;
        }

        // All keys should be unique
        $keyStrings = array_column($keys, 'key');
        $this->assertCount(5, array_unique($keyStrings));

        // All keys should validate against their own hashes
        foreach ($keys as $keyData) {
            $this->assertTrue(
                $this->service->validate($keyData['key'], $keyData['hash'])
            );
        }
    }

    /**
     * IT-008: Database storage flow simulation
     */
    public function test_database_storage_flow_simulation(): void
    {
        // Generate
        $result = $this->service->generate();

        // Simulate storing the hash (in a real scenario, this goes to DB)
        $storedHash = $result['hash'];
        $storedPrefix = $result['prefix'];

        // Later, validate using the stored hash
        $isValid = $this->service->validate($result['key'], $storedHash);

        $this->assertTrue($isValid);
        $this->assertEquals($result['prefix'], $storedPrefix);
    }

    /**
     * IT-010: Multiple validations same key
     */
    public function test_multiple_validations_same_key(): void
    {
        $result = $this->service->generate();

        for ($i = 0; $i < 10; $i++) {
            $isValid = $this->service->validate($result['key'], $result['hash']);
            $this->assertTrue($isValid);
        }
    }
}
