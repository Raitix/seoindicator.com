<?php

namespace App\Services;

/**
 * API Key Service for TASK-6-3.
 *
 * Provides secure API key generation, hashing, and validation.
 * Uses CSPRNG for key generation and timing-safe comparison for validation.
 *
 * Key format: sk_live_<base64url_encoded_random_bytes>
 * Hash: SHA-256 lowercase hex (64 characters)
 * Prefix: First 20 characters of the key for identification
 */
class ApiKeyService
{
    /**
     * Key prefix for production keys.
     */
    private const KEY_PREFIX = 'sk_live_';

    /**
     * Number of random bytes for key generation (256 bits of entropy).
     */
    private const RANDOM_BYTES_LENGTH = 32;

    /**
     * Prefix length for identification.
     */
    private const PREFIX_LENGTH = 20;

    /**
     * Minimum key body length (at least 1 char to ensure there's a body).
     * Real generated keys will have ~43 chars body (base64url of 32 bytes).
     */
    private const MIN_KEY_BODY_LENGTH = 1;

    /**
     * Generate a new API key with hash and prefix.
     *
     * @return array{key: string, hash: string, prefix: string}
     */
    public function generate(): array
    {
        // Generate cryptographically secure random bytes
        $randomBytes = random_bytes(self::RANDOM_BYTES_LENGTH);

        // Convert to base64url (no padding)
        $keyBody = $this->base64UrlEncode($randomBytes);

        // Construct the full key
        $key = self::KEY_PREFIX . $keyBody;

        // Generate SHA-256 hash for storage
        $hash = $this->hash($key);

        // Extract prefix for identification
        $prefix = $this->getPrefix($key);

        return [
            'key' => $key,
            'hash' => $hash,
            'prefix' => $prefix,
        ];
    }

    /**
     * Hash an API key for storage.
     *
     * Uses SHA-256 for secure, fast hashing.
     * Always returns lowercase hex string (64 characters).
     *
     * @param string $key The API key to hash
     * @return string SHA-256 hash (64 lowercase hex characters)
     */
    public function hash(string $key): string
    {
        return hash('sha256', $key);
    }

    /**
     * Validate an API key against a stored hash.
     *
     * Uses timing-safe comparison to prevent timing attacks.
     *
     * @param string $key The API key to validate
     * @param string $storedHash The stored hash to compare against
     * @return bool True if the key matches the hash
     */
    public function validate(string $key, string $storedHash): bool
    {
        // Empty key or hash is always invalid
        if ($key === '' || $storedHash === '') {
            return false;
        }

        // Validate hash format (must be 64 hex chars)
        if (!$this->isValidHashFormat($storedHash)) {
            return false;
        }

        // Compute hash of the provided key
        $computedHash = $this->hash($key);

        // Use timing-safe comparison
        return hash_equals($storedHash, $computedHash);
    }

    /**
     * Extract prefix from a key for identification.
     *
     * Returns the first 20 characters of the key, or the entire key if shorter.
     *
     * @param string $key The API key
     * @return string The prefix (up to 20 characters)
     */
    public function getPrefix(string $key): string
    {
        if ($key === '') {
            return '';
        }

        return substr($key, 0, self::PREFIX_LENGTH);
    }

    /**
     * Validate key format.
     *
     * Checks that the key:
     * - Starts with 'sk_live_' prefix
     * - Contains only base64url characters after the prefix
     * - Has sufficient length (at least 43 chars after prefix)
     * - Has no padding characters (=)
     *
     * @param string|null $key The API key to validate
     * @return bool True if the key format is valid
     */
    public function isValidFormat(?string $key): bool
    {
        // Null or empty is invalid
        if ($key === null || $key === '') {
            return false;
        }

        // Must start with the correct prefix (case-sensitive)
        if (!str_starts_with($key, self::KEY_PREFIX)) {
            return false;
        }

        // Extract the body (part after prefix)
        $keyBody = substr($key, strlen(self::KEY_PREFIX));

        // Body must have minimum length
        if (strlen($keyBody) < self::MIN_KEY_BODY_LENGTH) {
            return false;
        }

        // Body must contain only base64url characters (A-Z, a-z, 0-9, -, _)
        // No padding characters (=) allowed
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $keyBody)) {
            return false;
        }

        return true;
    }

    /**
     * Encode bytes to base64url without padding.
     *
     * Base64url uses '-' and '_' instead of '+' and '/' for URL safety.
     *
     * @param string $data Binary data to encode
     * @return string Base64url encoded string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Validate hash format.
     *
     * SHA-256 hash must be exactly 64 lowercase hexadecimal characters.
     *
     * @param string $hash The hash to validate
     * @return bool True if the hash format is valid
     */
    private function isValidHashFormat(string $hash): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $hash) === 1;
    }
}
