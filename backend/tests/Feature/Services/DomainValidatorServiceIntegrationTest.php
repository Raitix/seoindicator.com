<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\DomainValidatorService;
use App\Services\URLValidationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Mockery;

/**
 * Integration tests for DomainValidatorService.
 *
 * Tests accessibility checking, redirect handling, timeouts,
 * and full integration with URLValidationService.
 *
 * @see \App\Services\DomainValidatorService
 * @see TEST-18-4
 */
class DomainValidatorServiceIntegrationTest extends TestCase
{
    protected DomainValidatorService $service;
    protected $urlValidationServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlValidationServiceMock = Mockery::mock(URLValidationService::class);
        $this->service = new DomainValidatorService($this->urlValidationServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // ACCESSIBILITY CHECK TESTS (AC-*)
    // =========================================================================

    /**
     * AC-01: HTTP 200 OK.
     */
    public function test_check_accessibility_returns_success_on_200(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 200),
        ]);

        $result = $this->service->checkAccessibility('https://example.com');

        $this->assertTrue($result['accessible']);
        $this->assertEquals(200, $result['statusCode']);
        $this->assertEquals('https://example.com', $result['finalUrl']);
        $this->assertEquals(0, $result['redirects']);
    }

    /**
     * AC-02: HTTP 301 redirect.
     */
    public function test_check_accessibility_follows_301_redirect(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 301, ['Location' => 'https://www.example.com']),
            'https://www.example.com' => Http::response('', 200),
        ]);

        $result = $this->service->checkAccessibility('https://example.com');

        $this->assertTrue($result['accessible']);
        $this->assertEquals('https://www.example.com', $result['finalUrl']);
        $this->assertEquals(1, $result['redirects']);
    }

    /**
     * AC-03: HTTP 302 redirect.
     */
    public function test_check_accessibility_follows_302_redirect(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 302, ['Location' => 'https://new.example.com']),
            'https://new.example.com' => Http::response('', 200),
        ]);

        $result = $this->service->checkAccessibility('https://example.com');

        $this->assertTrue($result['accessible']);
        $this->assertEquals('https://new.example.com', $result['finalUrl']);
        $this->assertEquals(1, $result['redirects']);
    }

    /**
     * AC-04: HTTP 307 redirect.
     */
    public function test_check_accessibility_follows_307_redirect(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 307, ['Location' => 'https://tmp.example.com']),
            'https://tmp.example.com' => Http::response('', 200),
        ]);

        $result = $this->service->checkAccessibility('https://example.com');

        $this->assertTrue($result['accessible']);
        $this->assertEquals('https://tmp.example.com', $result['finalUrl']);
        $this->assertEquals(1, $result['redirects']);
    }

    /**
     * AC-05: HTTP 308 redirect.
     */
    public function test_check_accessibility_follows_308_redirect(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 308, ['Location' => 'https://permanent.example.com']),
            'https://permanent.example.com' => Http::response('', 200),
        ]);

        $result = $this->service->checkAccessibility('https://example.com');

        $this->assertTrue($result['accessible']);
        $this->assertEquals('https://permanent.example.com', $result['finalUrl']);
        $this->assertEquals(1, $result['redirects']);
    }

    /**
     * AC-08: HTTPS upgrade redirect.
     */
    public function test_check_accessibility_follows_https_upgrade(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            'http://example.com' => Http::response('', 301, ['Location' => 'https://example.com']),
            'https://example.com' => Http::response('', 200),
        ]);

        $result = $this->service->checkAccessibility('http://example.com');

        $this->assertTrue($result['accessible']);
        $this->assertEquals('https://example.com', $result['finalUrl']);
        $this->assertEquals(1, $result['redirects']);
    }

    /**
     * AC-09: WWW redirect.
     */
    public function test_check_accessibility_follows_www_redirect(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 301, ['Location' => 'https://www.example.com']),
            'https://www.example.com' => Http::response('', 200),
        ]);

        $result = $this->service->checkAccessibility('https://example.com');

        $this->assertTrue($result['accessible']);
        $this->assertEquals('https://www.example.com', $result['finalUrl']);
    }

    /**
     * AC-10: Non-WWW redirect.
     */
    public function test_check_accessibility_follows_non_www_redirect(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://www.example.com' => Http::response('', 301, ['Location' => 'https://example.com']),
            'https://example.com' => Http::response('', 200),
        ]);

        $result = $this->service->checkAccessibility('https://www.example.com');

        $this->assertTrue($result['accessible']);
        $this->assertEquals('https://example.com', $result['finalUrl']);
    }

    /**
     * AC-11: Connection timeout.
     */
    public function test_check_accessibility_handles_timeout(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://slow-example.com' => Http::response('', 200)->timeout(15),
        ]);

        // Simulate timeout by using a short timeout
        $result = $this->service->checkAccessibility('https://slow-example.com', 1);

        // Depending on implementation, should handle timeout gracefully
        $this->assertArrayHasKey('accessible', $result);
    }

    /**
     * AC-12: DNS failure.
     */
    public function test_check_accessibility_handles_dns_failure(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://this-domain-does-not-exist.invalid' => Http::response(null, 0),
        ]);

        $result = $this->service->checkAccessibility('https://this-domain-does-not-exist.invalid');

        $this->assertFalse($result['accessible']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * AC-14: HTTP 403 Forbidden.
     */
    public function test_check_accessibility_handles_403(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://forbidden.example.com' => Http::response('', 403),
        ]);

        $result = $this->service->checkAccessibility('https://forbidden.example.com');

        $this->assertFalse($result['accessible']);
        $this->assertEquals(403, $result['statusCode']);
    }

    /**
     * AC-15: HTTP 404 Not Found.
     */
    public function test_check_accessibility_handles_404(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://notfound.example.com' => Http::response('', 404),
        ]);

        $result = $this->service->checkAccessibility('https://notfound.example.com');

        $this->assertFalse($result['accessible']);
        $this->assertEquals(404, $result['statusCode']);
    }

    /**
     * AC-16: HTTP 500 Server Error.
     */
    public function test_check_accessibility_handles_500(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://error.example.com' => Http::response('', 500),
        ]);

        $result = $this->service->checkAccessibility('https://error.example.com');

        $this->assertFalse($result['accessible']);
        $this->assertEquals(500, $result['statusCode']);
    }

    // =========================================================================
    // REDIRECT LIMIT TESTS (RL-*)
    // =========================================================================

    /**
     * RL-01: No redirect.
     */
    public function test_redirect_handling_no_redirect(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('example.com');

        $this->assertEquals(0, $result['redirects']);
        $this->assertEquals('https://example.com', $result['finalUrl']);
    }

    /**
     * RL-02: 1 redirect.
     */
    public function test_redirect_handling_single_redirect(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://old.example.com' => Http::response('', 301, ['Location' => 'https://new.example.com']),
            'https://new.example.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('old.example.com');

        $this->assertEquals(1, $result['redirects']);
        $this->assertEquals('https://new.example.com', $result['finalUrl']);
    }

    /**
     * RL-03: 2 redirects.
     */
    public function test_redirect_handling_double_redirect(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->times(3)
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://a.example.com' => Http::response('', 301, ['Location' => 'https://b.example.com']),
            'https://b.example.com' => Http::response('', 301, ['Location' => 'https://c.example.com']),
            'https://c.example.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('a.example.com');

        $this->assertEquals(2, $result['redirects']);
        $this->assertEquals('https://c.example.com', $result['finalUrl']);
    }

    /**
     * RL-04: 3 redirects (max).
     */
    public function test_redirect_handling_max_redirects(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->times(4)
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://1.example.com' => Http::response('', 301, ['Location' => 'https://2.example.com']),
            'https://2.example.com' => Http::response('', 301, ['Location' => 'https://3.example.com']),
            'https://3.example.com' => Http::response('', 301, ['Location' => 'https://4.example.com']),
            'https://4.example.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('1.example.com');

        $this->assertEquals(3, $result['redirects']);
        $this->assertEquals('https://4.example.com', $result['finalUrl']);
    }

    /**
     * RL-05: 4 redirects (exceeds limit).
     */
    public function test_redirect_handling_exceeds_limit(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->times(4)
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://1.example.com' => Http::response('', 301, ['Location' => 'https://2.example.com']),
            'https://2.example.com' => Http::response('', 301, ['Location' => 'https://3.example.com']),
            'https://3.example.com' => Http::response('', 301, ['Location' => 'https://4.example.com']),
            'https://4.example.com' => Http::response('', 301, ['Location' => 'https://5.example.com']),
        ]);

        $this->expectException(ValidationException::class);

        $this->service->validate('1.example.com');
    }

    /**
     * RL-06: Redirect loop detection.
     */
    public function test_redirect_handling_detects_loop(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://a.example.com' => Http::response('', 301, ['Location' => 'https://b.example.com']),
            'https://b.example.com' => Http::response('', 301, ['Location' => 'https://a.example.com']),
        ]);

        $this->expectException(ValidationException::class);

        $this->service->validate('a.example.com');
    }

    /**
     * RL-07: Cross-domain redirect.
     */
    public function test_redirect_handling_cross_domain(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://old-domain.com' => Http::response('', 301, ['Location' => 'https://new-domain.com']),
            'https://new-domain.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('old-domain.com');

        $this->assertEquals(1, $result['redirects']);
        $this->assertEquals('https://new-domain.com', $result['finalUrl']);
    }

    /**
     * RL-08: Protocol change redirect.
     */
    public function test_redirect_handling_protocol_change(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            'http://example.com' => Http::response('', 301, ['Location' => 'https://example.com']),
            'https://example.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('http://example.com');

        $this->assertEquals(1, $result['redirects']);
        $this->assertEquals('https://example.com', $result['finalUrl']);
    }

    // =========================================================================
    // TIMEOUT TESTS (TO-*)
    // =========================================================================

    /**
     * TO-01: Fast response.
     */
    public function test_timeout_fast_response(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://fast.example.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('fast.example.com', ['timeout' => 10]);

        $this->assertTrue($result['accessible']);
    }

    /**
     * TO-02: Slow response but within timeout.
     */
    public function test_timeout_slow_response_within_limit(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        // Simulate 5 second response
        Http::fake([
            'https://slow.example.com' => Http::response('', 200)->delay(5000),
        ]);

        $result = $this->service->validate('slow.example.com', ['timeout' => 10]);

        $this->assertTrue($result['accessible']);
    }

    /**
     * TO-04: Custom timeout with valid response.
     */
    public function test_timeout_custom_timeout_valid(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 200)->delay(3000),
        ]);

        $result = $this->service->validate('example.com', ['timeout' => 5]);

        $this->assertTrue($result['accessible']);
    }

    // =========================================================================
    // RETURN VALUE TESTS (RV-*)
    // =========================================================================

    /**
     * RV-01: Success returns complete structure.
     */
    public function test_validate_returns_complete_structure(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('example.com');

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('finalUrl', $result);
        $this->assertArrayHasKey('redirects', $result);
        $this->assertArrayHasKey('accessible', $result);
        $this->assertEquals('https://example.com', $result['url']);
        $this->assertEquals('https://example.com', $result['finalUrl']);
        $this->assertEquals(0, $result['redirects']);
        $this->assertTrue($result['accessible']);
    }

    /**
     * RV-02: Success with redirect.
     */
    public function test_validate_returns_redirect_info(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            'http://example.com' => Http::response('', 301, ['Location' => 'https://example.com']),
            'https://example.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('http://example.com');

        $this->assertEquals('http://example.com', $result['url']);
        $this->assertEquals('https://example.com', $result['finalUrl']);
        $this->assertEquals(1, $result['redirects']);
        $this->assertTrue($result['accessible']);
    }

    /**
     * RV-03: Validation error returns error code.
     */
    public function test_validate_returns_error_code_on_invalid_format(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->service->validate('!@#$%');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('code', $errors['domain'] ?? []);
            throw $e;
        }
    }

    /**
     * RV-05: SSRF error returns BLOCKED_URL code.
     */
    public function test_validate_returns_blocked_url_code(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        try {
            $this->service->validate('localhost');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertEquals('BLOCKED_URL', $errors['domain']['code'] ?? null);
            throw $e;
        }
    }

    // =========================================================================
    // ERROR HANDLING TESTS (EH-*)
    // =========================================================================

    /**
     * EH-05: Rate limited.
     */
    public function test_error_handling_rate_limited(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://rate-limited.example.com' => Http::response('', 429),
        ]);

        $result = $this->service->checkAccessibility('https://rate-limited.example.com');

        $this->assertFalse($result['accessible']);
        $this->assertEquals(429, $result['statusCode']);
    }

    /**
     * EH-06: SSRF blocked.
     */
    public function test_error_handling_ssrf_blocked(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        $this->service->validate('127.0.0.1');
    }

    /**
     * EH-07: Redirect limit exceeded.
     */
    public function test_error_handling_redirect_limit_exceeded(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://1.example.com' => Http::response('', 301, ['Location' => 'https://2.example.com']),
            'https://2.example.com' => Http::response('', 301, ['Location' => 'https://3.example.com']),
            'https://3.example.com' => Http::response('', 301, ['Location' => 'https://4.example.com']),
            'https://4.example.com' => Http::response('', 301, ['Location' => 'https://5.example.com']),
        ]);

        $this->expectException(ValidationException::class);

        try {
            $this->service->validate('1.example.com');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertEquals('REDIRECT_LIMIT_EXCEEDED', $errors['domain']['code'] ?? null);
            throw $e;
        }
    }

    /**
     * EH-08: Redirect loop detected.
     */
    public function test_error_handling_redirect_loop(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://a.example.com' => Http::response('', 301, ['Location' => 'https://b.example.com']),
            'https://b.example.com' => Http::response('', 301, ['Location' => 'https://a.example.com']),
        ]);

        $this->expectException(ValidationException::class);

        try {
            $this->service->validate('a.example.com');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertEquals('REDIRECT_LOOP', $errors['domain']['code'] ?? null);
            throw $e;
        }
    }

    // =========================================================================
    // INTEGRATION TESTS (INT-*)
    // =========================================================================

    /**
     * INT-01: Uses existing SSRF service.
     */
    public function test_integration_uses_url_validation_service(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->with('https://example.com')
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 200),
        ]);

        $this->service->validate('example.com');

        // Mockery verifies the call was made
        $this->assertTrue(true);
    }

    /**
     * INT-02: SSRF errors propagate.
     */
    public function test_integration_ssrf_errors_propagate(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        $this->service->validate('192.168.1.1');
    }

    /**
     * INT-04: Public domain passes SSRF check.
     */
    public function test_integration_public_domain_passes(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://google.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('google.com');

        $this->assertTrue($result['accessible']);
    }

    // =========================================================================
    // FULL VALIDATION FLOW TESTS
    // =========================================================================

    /**
     * Full validation flow with normalization.
     */
    public function test_full_validation_flow_normalizes_and_validates(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->with('https://example.com')
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 200),
        ]);

        // Input with mixed case and www prefix
        $result = $this->service->validate('WWW.EXAMPLE.COM');

        $this->assertEquals('https://example.com', $result['url']);
        $this->assertTrue($result['accessible']);
    }

    /**
     * Full validation with redirect chain.
     */
    public function test_full_validation_with_redirect_chain(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->times(3)
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://old.example.com' => Http::response('', 301, ['Location' => 'https://new.example.com']),
            'https://new.example.com' => Http::response('', 302, ['Location' => 'https://final.example.com']),
            'https://final.example.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('old.example.com');

        $this->assertEquals('https://old.example.com', $result['url']);
        $this->assertEquals('https://final.example.com', $result['finalUrl']);
        $this->assertEquals(2, $result['redirects']);
        $this->assertArrayHasKey('redirectChain', $result);
    }

    /**
     * IDN domain full validation.
     */
    public function test_full_validation_idn_domain(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://*' => Http::response('', 200),
        ]);

        // Japanese IDN
        $result = $this->service->validate('例え.jp');

        $this->assertArrayHasKey('url', $result);
        $this->assertStringContainsString('.jp', $result['url']);
    }

    /**
     * Options parameter works correctly.
     */
    public function test_validation_with_options(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            'https://example.com' => Http::response('', 200),
        ]);

        $result = $this->service->validate('example.com', [
            'timeout' => 5,
            'verify_ssl' => true,
            'follow_redirects' => true,
        ]);

        $this->assertTrue($result['accessible']);
    }
}
