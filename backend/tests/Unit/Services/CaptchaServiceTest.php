<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CaptchaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for CaptchaService.
 *
 * Tests CAPTCHA verification for rate limit bypass.
 * Supports Google reCAPTCHA v3 (invisible) with fallback to hCaptcha.
 *
 * @see \App\Services\CaptchaService
 */
class CaptchaServiceTest extends TestCase
{
    protected CaptchaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CaptchaService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // SITE KEY TESTS
    // =========================================================================

    /**
     * Get site key returns reCAPTCHA key by default.
     */
    public function test_get_site_key_returns_recaptcha_key_by_default(): void
    {
        config(['services.recaptcha.site_key' => 'test-recaptcha-site-key']);
        config(['services.captcha.driver' => 'recaptcha']);

        $siteKey = $this->service->getSiteKey();

        $this->assertEquals('test-recaptcha-site-key', $siteKey);
    }

    /**
     * Get site key returns hCaptcha key when configured.
     */
    public function test_get_site_key_returns_hcaptcha_key_when_configured(): void
    {
        config(['services.hcaptcha.site_key' => 'test-hcaptcha-site-key']);
        config(['services.captcha.driver' => 'hcaptcha']);

        $siteKey = $this->service->getSiteKey();

        $this->assertEquals('test-hcaptcha-site-key', $siteKey);
    }

    /**
     * Get site key returns null when not configured.
     */
    public function test_get_site_key_returns_null_when_not_configured(): void
    {
        config(['services.recaptcha.site_key' => null]);
        config(['services.hcaptcha.site_key' => null]);
        config(['services.captcha.driver' => 'recaptcha']);

        $siteKey = $this->service->getSiteKey();

        $this->assertNull($siteKey);
    }

    // =========================================================================
    // VERIFY TESTS
    // =========================================================================

    /**
     * Verify returns error for empty token.
     */
    public function test_verify_returns_error_for_empty_token(): void
    {
        $result = $this->service->verify('');

        $this->assertFalse($result['valid']);
        $this->assertEquals('CAPTCHA token is required.', $result['error']);
    }

    /**
     * Verify handles whitespace only token.
     */
    public function test_verify_handles_whitespace_only_token(): void
    {
        // Whitespace-only tokens are currently not treated as empty by the service
        // In testing environment with no config, it bypasses and returns valid
        config(['services.recaptcha.secret_key' => null]);
        config(['services.captcha.driver' => 'recaptcha']);

        $result = $this->service->verify('   ');

        // In testing environment without config, it bypasses
        $this->assertTrue($result['valid']);
    }

    /**
     * Verify bypasses in testing environment when not configured.
     */
    public function test_verify_bypasses_in_testing_environment_when_not_configured(): void
    {
        config(['services.recaptcha.secret_key' => null]);
        config(['services.captcha.driver' => 'recaptcha']);

        $result = $this->service->verify('test-token');

        $this->assertTrue($result['valid']);
        $this->assertEquals(1.0, $result['score']);
    }

    /**
     * Verify reCAPTCHA success returns valid response.
     */
    public function test_verify_recaptcha_success_returns_valid_response(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
            ], 200),
        ]);

        $result = $this->service->verify('valid-token');

        $this->assertTrue($result['valid']);
        $this->assertEquals(0.9, $result['score']);
    }

    /**
     * Verify reCAPTCHA fails with low score.
     */
    public function test_verify_recaptcha_fails_with_low_score(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.3,
            ], 200),
        ]);

        $result = $this->service->verify('low-score-token');

        $this->assertFalse($result['valid']);
        $this->assertEquals(0.3, $result['score']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Verify reCAPTCHA fails when success is false.
     */
    public function test_verify_recaptcha_fails_when_success_is_false(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $result = $this->service->verify('invalid-token');

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Verify reCAPTCHA handles network error.
     */
    public function test_verify_recaptcha_handles_network_error(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response(null, 500),
        ]);

        $result = $this->service->verify('test-token');

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Verify hCaptcha success returns valid response.
     */
    public function test_verify_hcaptcha_success_returns_valid_response(): void
    {
        config(['services.hcaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'hcaptcha']);

        Http::fake([
            'https://hcaptcha.com/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $result = $this->service->verify('valid-hcaptcha-token');

        $this->assertTrue($result['valid']);
    }

    /**
     * Verify hCaptcha fails when success is false.
     */
    public function test_verify_hcaptcha_fails_when_success_is_false(): void
    {
        config(['services.hcaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'hcaptcha']);

        Http::fake([
            'https://hcaptcha.com/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $result = $this->service->verify('invalid-hcaptcha-token');

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Verify passes client IP to verification endpoint.
     */
    public function test_verify_passes_client_ip_to_verification_endpoint(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
            ], 200),
        ]);

        $this->service->verify('test-token', '192.168.1.1');

        Http::assertSent(function ($request) {
            return $request['remoteip'] === '192.168.1.1';
        });
    }

    // =========================================================================
    // IS CONFIGURED TESTS
    // =========================================================================

    /**
     * Is configured returns true when reCAPTCHA keys are set.
     */
    public function test_is_configured_returns_true_when_recaptcha_keys_are_set(): void
    {
        config(['services.recaptcha.site_key' => 'site-key']);
        config(['services.recaptcha.secret_key' => 'secret-key']);
        config(['services.captcha.driver' => 'recaptcha']);

        $this->assertTrue($this->service->isConfigured());
    }

    /**
     * Is configured returns true when hCaptcha keys are set.
     */
    public function test_is_configured_returns_true_when_hcaptcha_keys_are_set(): void
    {
        config(['services.hcaptcha.site_key' => 'site-key']);
        config(['services.hcaptcha.secret_key' => 'secret-key']);
        config(['services.captcha.driver' => 'hcaptcha']);

        $this->assertTrue($this->service->isConfigured());
    }

    /**
     * Is configured returns false when keys are missing.
     */
    public function test_is_configured_returns_false_when_keys_are_missing(): void
    {
        config(['services.recaptcha.site_key' => null]);
        config(['services.recaptcha.secret_key' => null]);
        config(['services.captcha.driver' => 'recaptcha']);

        $this->assertFalse($this->service->isConfigured());
    }

    /**
     * Is configured returns false when only site key is set.
     */
    public function test_is_configured_returns_false_when_only_site_key_is_set(): void
    {
        config(['services.recaptcha.site_key' => 'site-key']);
        config(['services.recaptcha.secret_key' => null]);
        config(['services.captcha.driver' => 'recaptcha']);

        $this->assertFalse($this->service->isConfigured());
    }

    /**
     * Is configured returns false when only secret key is set.
     */
    public function test_is_configured_returns_false_when_only_secret_key_is_set(): void
    {
        config(['services.recaptcha.site_key' => null]);
        config(['services.recaptcha.secret_key' => 'secret-key']);
        config(['services.captcha.driver' => 'recaptcha']);

        $this->assertFalse($this->service->isConfigured());
    }

    // =========================================================================
    // DRIVER NAME TESTS
    // =========================================================================

    /**
     * Get driver name returns configured driver.
     */
    public function test_get_driver_name_returns_configured_driver(): void
    {
        config(['services.captcha.driver' => 'hcaptcha']);

        $this->assertEquals('hcaptcha', $this->service->getDriverName());
    }

    /**
     * Get driver name defaults to reCAPTCHA.
     */
    public function test_get_driver_name_defaults_to_recaptcha(): void
    {
        config(['services.captcha.driver' => null]);

        $this->assertEquals('recaptcha', $this->service->getDriverName());
    }

    // =========================================================================
    // DEVELOPMENT BYPASS TESTS
    // =========================================================================

    /**
     * Development bypass allowed in testing environment without config.
     */
    public function test_development_bypass_allowed_in_testing_environment_without_config(): void
    {
        config(['services.recaptcha.site_key' => null]);
        config(['services.recaptcha.secret_key' => null]);
        config(['services.captcha.driver' => 'recaptcha']);

        $this->assertTrue($this->service->isDevelopmentBypassAllowed());
    }

    /**
     * Development bypass not allowed when configured.
     */
    public function test_development_bypass_not_allowed_when_configured(): void
    {
        config(['services.recaptcha.site_key' => 'site-key']);
        config(['services.recaptcha.secret_key' => 'secret-key']);
        config(['services.captcha.driver' => 'recaptcha']);

        $this->assertFalse($this->service->isDevelopmentBypassAllowed());
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Verify handles exception gracefully.
     */
    public function test_verify_handles_exception_gracefully(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake(function () {
            throw new \Exception('Connection timeout');
        });

        $result = $this->service->verify('test-token');

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Verify handles malformed JSON response.
     */
    public function test_verify_handles_malformed_json_response(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response('invalid json', 200),
        ]);

        $result = $this->service->verify('test-token');

        // Should handle gracefully
        $this->assertIsArray($result);
    }

    /**
     * Verify handles empty response body.
     */
    public function test_verify_handles_empty_response_body(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response(null, 200),
        ]);

        $result = $this->service->verify('test-token');

        // Should handle gracefully
        $this->assertIsArray($result);
    }

    /**
     * Verify handles missing score in reCAPTCHA response.
     */
    public function test_verify_handles_missing_score_in_recaptcha_response(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                // No score field
            ], 200),
        ]);

        $result = $this->service->verify('test-token');

        // Should treat missing score as 0 (fail)
        $this->assertFalse($result['valid']);
    }

    /**
     * Verify handles exact minimum score.
     */
    public function test_verify_handles_exact_minimum_score(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.5, // Exact minimum
            ], 200),
        ]);

        $result = $this->service->verify('test-token');

        $this->assertTrue($result['valid']);
    }

    /**
     * Verify handles score just below minimum.
     */
    public function test_verify_handles_score_just_below_minimum(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.49,
            ], 200),
        ]);

        $result = $this->service->verify('test-token');

        $this->assertFalse($result['valid']);
    }

    /**
     * Verify handles hCaptcha with score.
     */
    public function test_verify_handles_hcaptcha_with_score(): void
    {
        config(['services.hcaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'hcaptcha']);

        Http::fake([
            'https://hcaptcha.com/siteverify' => Http::response([
                'success' => true,
                'score' => 0.8,
            ], 200),
        ]);

        $result = $this->service->verify('test-token');

        $this->assertTrue($result['valid']);
        $this->assertEquals(0.8, $result['score']);
    }

    /**
     * Multiple rapid verify calls.
     */
    public function test_multiple_rapid_verify_calls(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
            ], 200),
        ]);

        for ($i = 0; $i < 10; $i++) {
            $result = $this->service->verify('token-' . $i);
            $this->assertTrue($result['valid']);
        }
    }

    /**
     * Very long token is handled.
     */
    public function test_very_long_token_is_handled(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
            ], 200),
        ]);

        $longToken = str_repeat('a', 10000);
        $result = $this->service->verify($longToken);

        $this->assertTrue($result['valid']);
    }

    /**
     * Token with special characters is handled.
     */
    public function test_token_with_special_characters_is_handled(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        config(['services.captcha.driver' => 'recaptcha']);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
            ], 200),
        ]);

        $specialToken = 'token-with_special.chars!@#$%';
        $result = $this->service->verify($specialToken);

        $this->assertTrue($result['valid']);
    }
}
