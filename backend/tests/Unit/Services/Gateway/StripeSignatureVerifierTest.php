<?php

namespace Tests\Unit\Services\Gateway;

use App\Services\Gateway\StripeSignatureVerifier;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for StripeSignatureVerifier - TASK-26-10.
 *
 * These tests verify signature verification without database operations.
 */
class StripeSignatureVerifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set a test webhook secret
        config(['services.stripe.webhook_secret' => 'whsec_test_secret_key_12345678']);
    }

    // ============================================
    // Configuration Tests
    // ============================================

    #[Test]
    public function it_is_configured_when_secret_is_set(): void
    {
        $verifier = new StripeSignatureVerifier('whsec_test');

        $this->assertTrue($verifier->isConfigured());
    }

    #[Test]
    public function it_is_not_configured_when_secret_is_empty(): void
    {
        $verifier = new StripeSignatureVerifier('');

        $this->assertFalse($verifier->isConfigured());
    }

    #[Test]
    public function it_is_not_configured_when_secret_is_null_and_no_config(): void
    {
        // Clear config secret to test null behavior
        config(['services.stripe.webhook_secret' => null]);

        $verifier = new StripeSignatureVerifier(null);

        $this->assertFalse($verifier->isConfigured());
    }

    #[Test]
    public function it_uses_config_secret_when_none_provided(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_from_config']);

        $verifier = new StripeSignatureVerifier();

        $this->assertTrue($verifier->isConfigured());
    }

    // ============================================
    // Missing Signature Header Tests
    // ============================================

    #[Test]
    public function it_rejects_request_without_signature_header(): void
    {
        $verifier = new StripeSignatureVerifier('whsec_test');

        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['test' => 'data']));

        $result = $verifier->verify($request);

        $this->assertFalse($result['valid']);
        $this->assertNull($result['payload']);
        $this->assertStringContainsString('Missing Stripe-Signature', $result['error']);
    }

    #[Test]
    public function it_rejects_request_with_empty_signature_header(): void
    {
        $verifier = new StripeSignatureVerifier('whsec_test');

        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['test' => 'data']));
        $request->headers->set('Stripe-Signature', '');

        $result = $verifier->verify($request);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Missing Stripe-Signature', $result['error']);
    }

    // ============================================
    // Empty Payload Tests
    // ============================================

    #[Test]
    public function it_rejects_request_with_empty_payload(): void
    {
        $verifier = new StripeSignatureVerifier('whsec_test');

        $request = Request::create('/webhook', 'POST', [], [], [], [], '');
        $request->headers->set('Stripe-Signature', 't=1234567890,v1=abc123');

        $result = $verifier->verify($request);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Empty payload', $result['error']);
    }

    // ============================================
    // Invalid Signature Tests
    // ============================================

    #[Test]
    public function it_rejects_request_with_invalid_signature_format(): void
    {
        $verifier = new StripeSignatureVerifier('whsec_test');

        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['test' => 'data']));
        $request->headers->set('Stripe-Signature', 'invalid_format');

        $result = $verifier->verify($request);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid signature', $result['error']);
    }

    #[Test]
    public function it_rejects_request_with_wrong_signature(): void
    {
        $verifier = new StripeSignatureVerifier('whsec_correct_secret');

        $payload = json_encode(['id' => 'evt_test', 'type' => 'payment_intent.succeeded']);
        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        // This signature was generated with a different secret
        $request->headers->set('Stripe-Signature', 't=1234567890,v1=wrong_signature_hash');

        $result = $verifier->verify($request);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid signature', $result['error']);
    }

    // ============================================
    // Tolerance Tests
    // ============================================

    #[Test]
    public function it_has_default_tolerance_of_300_seconds(): void
    {
        $verifier = new StripeSignatureVerifier('whsec_test');

        $this->assertEquals(300, $verifier->getTolerance());
    }

    #[Test]
    public function it_allows_custom_tolerance(): void
    {
        $verifier = new StripeSignatureVerifier('whsec_test', 600);

        $this->assertEquals(600, $verifier->getTolerance());
    }

    #[Test]
    public function it_can_set_tolerance_fluently(): void
    {
        $verifier = new StripeSignatureVerifier('whsec_test');

        $result = $verifier->setTolerance(120);

        $this->assertSame($verifier, $result);
        $this->assertEquals(120, $verifier->getTolerance());
    }

    // ============================================
    // Valid Signature Tests (Integration with Stripe SDK)
    // ============================================

    #[Test]
    public function it_accepts_valid_signature(): void
    {
        $secret = 'whsec_test_secret_for_verification';
        $verifier = new StripeSignatureVerifier($secret);

        // Generate a valid signature using Stripe's format
        $timestamp = time();
        $payload = json_encode(['id' => 'evt_test', 'type' => 'payment_intent.succeeded']);
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('Stripe-Signature', "t={$timestamp},v1={$signature}");

        $result = $verifier->verify($request);

        $this->assertTrue($result['valid']);
        $this->assertEquals($payload, $result['payload']);
        $this->assertNull($result['error']);
    }

    #[Test]
    public function it_rejects_expired_timestamp(): void
    {
        $secret = 'whsec_test_secret';
        $verifier = new StripeSignatureVerifier($secret, 300); // 5 min tolerance

        // Use a timestamp from 10 minutes ago (outside tolerance)
        $timestamp = time() - 600;
        $payload = json_encode(['id' => 'evt_test', 'type' => 'payment_intent.succeeded']);
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('Stripe-Signature', "t={$timestamp},v1={$signature}");

        $result = $verifier->verify($request);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid signature', $result['error']);
    }

    // ============================================
    // Security Tests
    // ============================================

    #[Test]
    public function it_does_not_leak_secret_in_error_messages(): void
    {
        $secret = 'whsec_super_secret_key_12345';
        $verifier = new StripeSignatureVerifier($secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['test' => 'data']));
        $request->headers->set('Stripe-Signature', 't=1234567890,v1=wronghash');

        $result = $verifier->verify($request);

        $this->assertFalse($result['valid']);
        $this->assertStringNotContainsString($secret, $result['error']);
    }

    #[Test]
    public function verify_or_fail_throws_exception_on_invalid_signature(): void
    {
        $this->expectException(\Stripe\Exception\SignatureVerificationException::class);

        $verifier = new StripeSignatureVerifier('whsec_test');

        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['test' => 'data']));
        // Missing signature header

        $verifier->verifyOrFail($request);
    }

    #[Test]
    public function verify_or_fail_returns_payload_on_valid_signature(): void
    {
        $secret = 'whsec_test_secret';
        $verifier = new StripeSignatureVerifier($secret);

        $timestamp = time();
        $payload = json_encode(['id' => 'evt_test', 'type' => 'test']);
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('Stripe-Signature', "t={$timestamp},v1={$signature}");

        $result = $verifier->verifyOrFail($request);

        $this->assertEquals($payload, $result);
    }

    // ============================================
    // Edge Case Tests
    // ============================================

    #[Test]
    public function it_handles_large_payload(): void
    {
        $secret = 'whsec_test_secret';
        $verifier = new StripeSignatureVerifier($secret);

        // Create a large payload (100KB)
        $largeData = str_repeat('x', 100000);
        $payload = json_encode(['data' => $largeData]);

        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('Stripe-Signature', "t={$timestamp},v1={$signature}");

        $result = $verifier->verify($request);

        $this->assertTrue($result['valid']);
    }

    #[Test]
    public function it_handles_unicode_in_payload(): void
    {
        $secret = 'whsec_test_secret';
        $verifier = new StripeSignatureVerifier($secret);

        $payload = json_encode(['message' => 'こんにちは世界 🌍']);

        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('Stripe-Signature', "t={$timestamp},v1={$signature}");

        $result = $verifier->verify($request);

        $this->assertTrue($result['valid']);
    }
}
