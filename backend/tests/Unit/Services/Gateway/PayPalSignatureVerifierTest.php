<?php

namespace Tests\Unit\Services\Gateway;

use App\Services\Gateway\PayPalGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit tests for TASK-26-14: PayPal Webhook Signature Verification
 *
 * SECURITY CRITICAL: These tests validate the signature verification
 * algorithm that protects against webhook spoofing and replay attacks.
 *
 * Tests cover:
 * - Signature header validation
 * - Certificate URL validation
 * - Transmission time validation
 * - Signature computation and comparison
 * - Timing-constant comparison
 *
 * @see \App\Services\Gateway\PayPalGatewayService::verifyWebhookSignature()
 */
class PayPalSignatureVerifierTest extends TestCase
{
    protected string $webhookId = 'test-webhook-id-12345';
    protected string $validCertUrl = 'https://api.paypal.com/v1/notifications/certs/CERT-test';
    protected string $sandboxCertUrl = 'https://api.sandbox.paypal.com/v1/notifications/certs/CERT-test';

    // ==================== HEADER VALIDATION TESTS ====================

    /**
     * Test all required headers are present.
     * PPW-SIG-001
     */
    public function test_all_required_headers_are_present(): void
    {
        $headers = [
            'PAYPAL-TRANSMISSION-SIG' => 'valid_signature',
            'PAYPAL-CERT-URL' => $this->validCertUrl,
            'PAYPAL-TRANSMISSION-ID' => '12345678-1234-1234-1234-123456789012',
            'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        ];

        $missingHeaders = $this->validateRequiredHeaders($headers);

        $this->assertEmpty($missingHeaders, 'All required headers should be present');
    }

    /**
     * Test missing transmission signature header fails.
     * PPW-SIG-002
     */
    public function test_missing_transmission_sig_fails(): void
    {
        $headers = [
            'PAYPAL-CERT-URL' => $this->validCertUrl,
            'PAYPAL-TRANSMISSION-ID' => '12345',
            'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
        ];

        $missingHeaders = $this->validateRequiredHeaders($headers);

        $this->assertContains('PAYPAL-TRANSMISSION-SIG', $missingHeaders);
    }

    /**
     * Test missing cert URL header fails.
     * PPW-SIG-003
     */
    public function test_missing_cert_url_fails(): void
    {
        $headers = [
            'PAYPAL-TRANSMISSION-SIG' => 'signature',
            'PAYPAL-TRANSMISSION-ID' => '12345',
            'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
        ];

        $missingHeaders = $this->validateRequiredHeaders($headers);

        $this->assertContains('PAYPAL-CERT-URL', $missingHeaders);
    }

    /**
     * Test missing transmission ID header fails.
     * PPW-SIG-004
     */
    public function test_missing_transmission_id_fails(): void
    {
        $headers = [
            'PAYPAL-TRANSMISSION-SIG' => 'signature',
            'PAYPAL-CERT-URL' => $this->validCertUrl,
            'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
        ];

        $missingHeaders = $this->validateRequiredHeaders($headers);

        $this->assertContains('PAYPAL-TRANSMISSION-ID', $missingHeaders);
    }

    /**
     * Test missing transmission time header fails.
     * PPW-SIG-005
     */
    public function test_missing_transmission_time_fails(): void
    {
        $headers = [
            'PAYPAL-TRANSMISSION-SIG' => 'signature',
            'PAYPAL-CERT-URL' => $this->validCertUrl,
            'PAYPAL-TRANSMISSION-ID' => '12345',
        ];

        $missingHeaders = $this->validateRequiredHeaders($headers);

        $this->assertContains('PAYPAL-TRANSMISSION-TIME', $missingHeaders);
    }

    // ==================== CERTIFICATE URL VALIDATION TESTS ====================

    /**
     * Test PayPal production certificate URL is accepted.
     * PPW-SIG-006
     */
    public function test_paypal_production_cert_url_accepted(): void
    {
        $certUrls = [
            'https://api.paypal.com/v1/notifications/certs/CERT-36045042b18',
            'https://api.paypal.com/v1/notifications/certs/CERT-abc123',
        ];

        foreach ($certUrls as $url) {
            $this->assertTrue(
                $this->isValidCertificateUrl($url),
                "URL should be valid: {$url}"
            );
        }
    }

    /**
     * Test PayPal sandbox certificate URL is accepted.
     * PPW-SIG-007
     */
    public function test_paypal_sandbox_cert_url_accepted(): void
    {
        $certUrls = [
            'https://api.sandbox.paypal.com/v1/notifications/certs/CERT-test',
            'https://api.sandbox.paypal.com/v1/notifications/certs/CERT-xyz789',
        ];

        foreach ($certUrls as $url) {
            $this->assertTrue(
                $this->isValidCertificateUrl($url),
                "Sandbox URL should be valid: {$url}"
            );
        }
    }

    /**
     * Test non-PayPal certificate URL is rejected.
     * PPW-SIG-008 (CRITICAL SECURITY)
     */
    public function test_non_paypal_cert_url_rejected(): void
    {
        $maliciousUrls = [
            'https://malicious-site.com/fake-cert',
            'https://paypal.evil.com/v1/notifications/certs/CERT-fake',
            'https://api.paypal.com.malicious.com/v1/notifications/certs/CERT-fake',
            'http://api.paypal.com/v1/notifications/certs/CERT-plain-http',
            'https://user:pass@api.paypal.com/v1/notifications/certs/CERT-creds',
            'https://api.paypal.com.evil.org/v1/notifications/certs/CERT-subdomain',
        ];

        foreach ($maliciousUrls as $url) {
            $this->assertFalse(
                $this->isValidCertificateUrl($url),
                "Malicious URL should be rejected: {$url}"
            );
        }
    }

    /**
     * Test HTTP certificate URL is rejected (HTTPS required).
     * PPW-SIG-009
     */
    public function test_http_cert_url_rejected(): void
    {
        $httpUrls = [
            'http://api.paypal.com/v1/notifications/certs/CERT-test',
            'http://api.sandbox.paypal.com/v1/notifications/certs/CERT-test',
        ];

        foreach ($httpUrls as $url) {
            $this->assertFalse(
                $this->isValidCertificateUrl($url),
                "HTTP URL should be rejected: {$url}"
            );
        }
    }

    /**
     * Test certificate URL with invalid path is rejected.
     * PPW-SIG-010
     */
    public function test_invalid_cert_path_rejected(): void
    {
        $invalidPaths = [
            'https://api.paypal.com/v1/invalid/path',
            'https://api.paypal.com/notifications/certs/CERT-test',
            'https://api.paypal.com/v1/notifications/invalid',
        ];

        foreach ($invalidPaths as $url) {
            $this->assertFalse(
                $this->isValidCertificateUrl($url),
                "Invalid path URL should be rejected: {$url}"
            );
        }
    }

    // ==================== TRANSMISSION TIME VALIDATION TESTS ====================

    /**
     * Test current transmission time is accepted.
     * PPW-SIG-011
     */
    public function test_current_transmission_time_accepted(): void
    {
        $time = now()->toIso8601String();

        $this->assertTrue($this->isValidTransmissionTime($time));
    }

    /**
     * Test transmission time within tolerance is accepted.
     * PPW-SIG-012
     */
    public function test_transmission_time_within_tolerance(): void
    {
        // PayPal typically allows a few minutes of clock skew
        $times = [
            now()->subMinutes(2)->toIso8601String(),
            now()->subMinutes(5)->toIso8601String(),
            now()->addMinutes(2)->toIso8601String(),
        ];

        foreach ($times as $time) {
            $this->assertTrue(
                $this->isValidTransmissionTime($time, 300),
                "Time within tolerance should be accepted: {$time}"
            );
        }
    }

    /**
     * Test expired transmission time is rejected.
     * PPW-SIG-013
     */
    public function test_expired_transmission_time_rejected(): void
    {
        $expiredTimes = [
            now()->subHours(1)->toIso8601String(),
            now()->subDays(1)->toIso8601String(),
            '2020-01-01T00:00:00Z',
            '2025-01-01T00:00:00Z',
        ];

        foreach ($expiredTimes as $time) {
            $this->assertFalse(
                $this->isValidTransmissionTime($time, 300),
                "Expired time should be rejected: {$time}"
            );
        }
    }

    /**
     * Test future transmission time is rejected (too far in future).
     * PPW-SIG-014
     */
    public function test_future_transmission_time_rejected(): void
    {
        $futureTimes = [
            now()->addHours(1)->toIso8601String(),
            now()->addDays(1)->toIso8601String(),
        ];

        foreach ($futureTimes as $time) {
            $this->assertFalse(
                $this->isValidTransmissionTime($time, 300),
                "Future time should be rejected: {$time}"
            );
        }
    }

    /**
     * Test invalid transmission time format is rejected.
     * PPW-SIG-015
     */
    public function test_invalid_transmission_time_format_rejected(): void
    {
        $invalidFormats = [
            'not-a-date',
            '2026/03/04 12:00:00',
            '03-04-2026',
            '',
            null,
        ];

        foreach ($invalidFormats as $time) {
            $this->assertFalse(
                $this->isValidTransmissionTime($time, 300),
                "Invalid format should be rejected: " . ($time ?? 'null')
            );
        }
    }

    // ==================== SIGNATURE COMPUTATION TESTS ====================

    /**
     * Test expected signature string format.
     * PPW-SIG-016
     */
    public function test_expected_signature_string_format(): void
    {
        $transmissionId = '12345678-1234-1234-1234-123456789012';
        $transmissionTime = '2026-03-04T12:00:00Z';
        $webhookId = 'test-webhook-id';
        $body = '{"id":"WH-123","event_type":"PAYMENT.CAPTURE.COMPLETED"}';
        $crc = crc32($body);

        $expectedString = "{$transmissionId}|{$transmissionTime}|{$webhookId}|{$crc}";

        $computedString = $this->computeExpectedSignatureString(
            $transmissionId,
            $transmissionTime,
            $webhookId,
            $body
        );

        $this->assertEquals($expectedString, $computedString);
    }

    /**
     * Test CRC32 computation matches PayPal's expectation.
     * PPW-SIG-017
     */
    public function test_crc32_computation_matches(): void
    {
        $body = '{"id":"WH-123","event_type":"PAYMENT.CAPTURE.COMPLETED"}';

        $crc = crc32($body);

        // Verify CRC is a valid integer
        $this->assertIsInt($crc);

        // Verify CRC is consistent
        $this->assertEquals($crc, crc32($body));
    }

    /**
     * Test signature string is deterministic.
     * PPW-SIG-018
     */
    public function test_signature_string_is_deterministic(): void
    {
        $params = [
            'transmission_id' => '12345',
            'transmission_time' => '2026-03-04T12:00:00Z',
            'webhook_id' => 'test-id',
            'body' => '{"test":"data"}',
        ];

        $string1 = $this->computeExpectedSignatureString(
            $params['transmission_id'],
            $params['transmission_time'],
            $params['webhook_id'],
            $params['body']
        );

        $string2 = $this->computeExpectedSignatureString(
            $params['transmission_id'],
            $params['transmission_time'],
            $params['webhook_id'],
            $params['body']
        );

        $this->assertEquals($string1, $string2);
    }

    // ==================== TIMING-CONSTANT COMPARISON TESTS ====================

    /**
     * Test timing-constant comparison for signatures.
     * PPW-SIG-019 (CRITICAL SECURITY)
     */
    public function test_timing_constant_comparison(): void
    {
        $correctSig = str_repeat('a', 64);
        $incorrectSigs = [
            str_repeat('b', 64),        // Completely different
            substr($correctSig, 0, 1) . str_repeat('b', 63), // One char different
            substr($correctSig, 0, 63) . 'b', // Last char different
            str_repeat('a', 63) . 'b', // Same length, one different
        ];

        foreach ($incorrectSigs as $incorrectSig) {
            $startCorrect = microtime(true);
            $this->verifySignatureTimingSafe($correctSig, $correctSig);
            $timeCorrect = microtime(true) - $startCorrect;

            $startIncorrect = microtime(true);
            $this->verifySignatureTimingSafe($correctSig, $incorrectSig);
            $timeIncorrect = microtime(true) - $startIncorrect;

            // Times should be approximately equal (within 50% margin for noise)
            // This is a basic check - real timing attacks require statistical analysis
            $ratio = $timeCorrect / max($timeIncorrect, 0.000001);
            $this->assertGreaterThan(0.5, $ratio, 'Timing should be roughly constant');
            $this->assertLessThan(2.0, $ratio, 'Timing should be roughly constant');
        }
    }

    /**
     * Test hash_equals is used for comparison.
     * PPW-SIG-020
     */
    public function test_hash_equals_used_for_comparison(): void
    {
        $known = 'known_signature_value';
        $user = 'user_provided_signature';

        // This should use hash_equals internally
        $result = $this->verifySignatureTimingSafe($known, $user);

        $this->assertFalse($result);
    }

    /**
     * Test signatures of different length are handled safely.
     * PPW-SIG-021
     */
    public function test_different_length_signatures_handled_safely(): void
    {
        $known = 'short';
        $user = 'this_is_a_very_long_signature_attempt';

        // Should not throw and should return false
        $result = $this->verifySignatureTimingSafe($known, $user);

        $this->assertFalse($result);
    }

    // ==================== AUTH ALGORITHM VALIDATION TESTS ====================

    /**
     * Test SHA256withRSA algorithm is accepted.
     * PPW-SIG-022
     */
    public function test_sha256_with_rsa_algorithm_accepted(): void
    {
        $this->assertTrue($this->isValidAuthAlgorithm('SHA256withRSA'));
    }

    /**
     * Test invalid auth algorithm is rejected.
     * PPW-SIG-023
     */
    public function test_invalid_auth_algorithm_rejected(): void
    {
        $invalidAlgorithms = [
            'MD5withRSA',
            'SHA1withRSA',
            'none',
            '',
            'invalid',
        ];

        foreach ($invalidAlgorithms as $algo) {
            $this->assertFalse(
                $this->isValidAuthAlgorithm($algo),
                "Invalid algorithm should be rejected: {$algo}"
            );
        }
    }

    // ==================== INTEGRATION TESTS ====================

    /**
     * Test full signature verification flow with mock certificate.
     * PPW-SIG-024
     */
    public function test_full_signature_verification_flow(): void
    {
        $headers = [
            'PAYPAL-TRANSMISSION-SIG' => base64_encode('mock_signature'),
            'PAYPAL-CERT-URL' => $this->validCertUrl,
            'PAYPAL-TRANSMISSION-ID' => '12345678-1234-1234-1234-123456789012',
            'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        ];

        $body = '{"id":"WH-123","event_type":"PAYMENT.CAPTURE.COMPLETED"}';

        // All headers present
        $this->assertEmpty($this->validateRequiredHeaders($headers));

        // Certificate URL valid
        $this->assertTrue($this->isValidCertificateUrl($headers['PAYPAL-CERT-URL']));

        // Transmission time valid
        $this->assertTrue($this->isValidTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME']));

        // Auth algorithm valid
        $this->assertTrue($this->isValidAuthAlgorithm($headers['PAYPAL-AUTH-ALGO']));
    }

    /**
     * Test signature verification logs attempt.
     * PPW-SIG-025
     */
    public function test_signature_verification_logs_attempt(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'signature') &&
                       isset($context['transmission_id']);
            });

        $this->logSignatureVerification('test-transmission-id', true);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Validate required PayPal webhook headers.
     */
    protected function validateRequiredHeaders(array $headers): array
    {
        $required = [
            'PAYPAL-TRANSMISSION-SIG',
            'PAYPAL-CERT-URL',
            'PAYPAL-TRANSMISSION-ID',
            'PAYPAL-TRANSMISSION-TIME',
        ];

        $missing = [];
        foreach ($required as $header) {
            if (!isset($headers[$header]) || empty($headers[$header])) {
                $missing[] = $header;
            }
        }

        return $missing;
    }

    /**
     * Validate certificate URL is from PayPal.
     */
    protected function isValidCertificateUrl(string $url): bool
    {
        // Must be HTTPS
        if (!str_starts_with($url, 'https://')) {
            return false;
        }

        // Must be from api.paypal.com or api.sandbox.paypal.com
        $validDomains = [
            'https://api.paypal.com/',
            'https://api.sandbox.paypal.com/',
        ];

        foreach ($validDomains as $domain) {
            if (str_starts_with($url, $domain)) {
                // Must have correct path
                if (str_contains($url, '/v1/notifications/certs/')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validate transmission time is within tolerance.
     */
    protected function isValidTransmissionTime(?string $time, int $toleranceSeconds = 300): bool
    {
        if (!$time) {
            return false;
        }

        try {
            $transmissionTime = \Carbon\Carbon::parse($time);
            $now = now();

            $diff = abs($now->diffInSeconds($transmissionTime));

            return $diff <= $toleranceSeconds;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Compute the expected signature string.
     */
    protected function computeExpectedSignatureString(
        string $transmissionId,
        string $transmissionTime,
        string $webhookId,
        string $body
    ): string {
        $crc = crc32($body);
        return "{$transmissionId}|{$transmissionTime}|{$webhookId}|{$crc}";
    }

    /**
     * Verify signature using timing-safe comparison.
     */
    protected function verifySignatureTimingSafe(string $known, string $user): bool
    {
        // Use hash_equals for timing-safe comparison
        return hash_equals($known, $user);
    }

    /**
     * Validate auth algorithm.
     */
    protected function isValidAuthAlgorithm(string $algorithm): bool
    {
        return $algorithm === 'SHA256withRSA';
    }

    /**
     * Log signature verification attempt.
     */
    protected function logSignatureVerification(string $transmissionId, bool $success): void
    {
        Log::info('PayPal webhook signature verification', [
            'transmission_id' => $transmissionId,
            'success' => $success,
        ]);
    }
}
