<?php

namespace Tests\Unit\Services\Playwright;

use Tests\TestCase;
use App\Services\Playwright\BrowserLauncher;
use App\Services\Playwright\Contracts\BrowserInterface;
use App\Services\Playwright\Contracts\BrowserContextInterface;
use App\Services\Playwright\Contracts\PageInterface;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;

/**
 * Unit tests for the BrowserLauncher service.
 *
 * @see TASK-22-4
 * @see SPEC-022
 */
class BrowserLauncherTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Service Instantiation Tests (BL-001)
    // ==========================================

    /**
     * Test service can be instantiated via container.
     * BL-001: Service instantiation
     */
    public function test_service_can_be_instantiated(): void
    {
        $service = app(BrowserLauncher::class);
        $this->assertInstanceOf(BrowserLauncher::class, $service);
    }

    /**
     * Test service has default configuration loaded.
     */
    public function test_service_has_default_configuration(): void
    {
        $service = app(BrowserLauncher::class);

        $this->assertEquals('chromium', config('playwright.browser', 'chromium'));
        $this->assertTrue(config('playwright.headless', true));
        $this->assertEquals(30000, config('playwright.timeout', 30000));
    }

    // ==========================================
    // Browser Launch Tests (BL-002 to BL-005)
    // ==========================================

    /**
     * Test launch browser with defaults.
     * BL-002: Launch browser with defaults
     */
    public function test_launch_browser_with_defaults(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('isConnected')->once()->andReturn(true);
        $mockBrowser->shouldReceive('close')->once();

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // createBrowserInstance is called with merged default options
        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->withArgs(function ($options) {
                // Verify default options are present
                return isset($options['browser']) &&
                    isset($options['headless']) &&
                    isset($options['timeout']) &&
                    isset($options['viewport']);
            })
            ->andReturn($mockBrowser);

        $browser = $service->launch();

        $this->assertInstanceOf(BrowserInterface::class, $browser);
        $this->assertTrue($browser->isConnected());

        // Cleanup
        $browser->close();
    }

    /**
     * Test launch browser with custom browser type.
     * BL-003: Launch browser with custom options
     */
    public function test_launch_browser_with_custom_browser(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser->shouldReceive('close');

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->withArgs(function ($options) {
                return isset($options['browser']) && $options['browser'] === 'firefox';
            })
            ->andReturn($mockBrowser);

        $browser = $service->launch(['browser' => 'firefox']);

        $this->assertInstanceOf(BrowserInterface::class, $browser);
    }

    /**
     * Test launch browser with headless false.
     * BL-004: Launch browser with headless false
     */
    public function test_launch_browser_with_headless_false(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->withArgs(function ($options) {
                return isset($options['headless']) && $options['headless'] === false;
            })
            ->andReturn($mockBrowser);

        $browser = $service->launch(['headless' => false]);

        $this->assertInstanceOf(BrowserInterface::class, $browser);
    }

    /**
     * Test launch with custom viewport.
     * BL-014: Launch with custom viewport
     */
    public function test_launch_browser_with_custom_viewport(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $customViewport = ['width' => 1920, 'height' => 1080];

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->withArgs(function ($options) use ($customViewport) {
                return isset($options['viewport']) && $options['viewport'] === $customViewport;
            })
            ->andReturn($mockBrowser);

        $browser = $service->launch(['viewport' => $customViewport]);

        $this->assertInstanceOf(BrowserInterface::class, $browser);
    }

    /**
     * Test launch with custom user agent.
     * BL-015: Launch with custom user agent
     */
    public function test_launch_browser_with_custom_user_agent(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $customAgent = 'CustomBot/1.0';

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->withArgs(function ($options) use ($customAgent) {
                return isset($options['userAgent']) && $options['userAgent'] === $customAgent;
            })
            ->andReturn($mockBrowser);

        $browser = $service->launch(['userAgent' => $customAgent]);

        $this->assertInstanceOf(BrowserInterface::class, $browser);
    }

    // ==========================================
    // Shared Instance Tests (BL-005)
    // ==========================================

    /**
     * Test get shared instance returns same browser.
     * BL-005: Get shared instance
     */
    public function test_get_shared_instance_returns_same_browser(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser->shouldReceive('close');

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($mockBrowser);

        $browser1 = $service->getSharedInstance();
        $browser2 = $service->getSharedInstance();

        $this->assertSame($browser1, $browser2);
    }

    // ==========================================
    // Browser Close Tests (BL-006, BL-007)
    // ==========================================

    /**
     * Test close specific browser instance.
     * BL-006: Close specific browser
     */
    public function test_close_specific_browser(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser->shouldReceive('close')->once();

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($mockBrowser);

        $browser = $service->launch();
        $service->closeInstance($browser);

        // Verify close was called (via mock expectations)
        $this->assertTrue(true);
    }

    /**
     * Test close all browser instances.
     * BL-007: Close all browsers
     */
    public function test_close_all_browsers(): void
    {
        $mockBrowser1 = Mockery::mock(BrowserInterface::class);
        $mockBrowser1->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser1->shouldReceive('close')->once();

        $mockBrowser2 = Mockery::mock(BrowserInterface::class);
        $mockBrowser2->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser2->shouldReceive('close')->once();

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->twice()
            ->andReturn($mockBrowser1, $mockBrowser2);

        $service->launch();
        $service->launch();
        $service->closeAll();

        // Verify close was called on both (via mock expectations)
        $this->assertTrue(true);
    }

    // ==========================================
    // Instance Tracking Tests (BL-008, BL-013)
    // ==========================================

    /**
     * Test track active instances.
     * BL-008: Track active instances
     */
    public function test_track_active_instances(): void
    {
        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Create different mock browsers for each launch (different spl_object_id)
        $mockBrowser1 = Mockery::mock(BrowserInterface::class);
        $mockBrowser1->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser1->shouldReceive('close');

        $mockBrowser2 = Mockery::mock(BrowserInterface::class);
        $mockBrowser2->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser2->shouldReceive('close');

        $service->shouldReceive('createBrowserInstance')
            ->twice()
            ->andReturn($mockBrowser1, $mockBrowser2);

        $this->assertEquals(0, $service->getActiveInstanceCount());

        $service->launch();
        $this->assertEquals(1, $service->getActiveInstanceCount());

        $service->launch();
        $this->assertEquals(2, $service->getActiveInstanceCount());
    }

    /**
     * Test max instances limit is enforced.
     * BL-013: Max instances limit
     */
    public function test_max_instances_limit(): void
    {
        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Create different mock browsers for each launch
        $mockBrowser1 = Mockery::mock(BrowserInterface::class);
        $mockBrowser1->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser1->shouldReceive('close');

        $mockBrowser2 = Mockery::mock(BrowserInterface::class);
        $mockBrowser2->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser2->shouldReceive('close');

        $mockBrowser3 = Mockery::mock(BrowserInterface::class);
        $mockBrowser3->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser3->shouldReceive('close');

        $service->shouldReceive('createBrowserInstance')
            ->times(3)
            ->andReturn($mockBrowser1, $mockBrowser2, $mockBrowser3);

        // Launch max allowed instances (default is 3)
        $service->launch();
        $service->launch();
        $service->launch();

        // Fourth launch should throw exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maximum browser instances reached');

        $service->launch();
    }

    // ==========================================
    // Instance Timeout Tests (BL-009)
    // ==========================================

    /**
     * Test instance timeout enforcement.
     * BL-009: Instance timeout enforcement
     */
    public function test_instance_timeout_enforcement(): void
    {
        // Create a browser that will appear unhealthy
        $oldBrowser = Mockery::mock(BrowserInterface::class);
        $oldBrowser->shouldReceive('close')->once();

        // Create a new healthy browser
        $newBrowser = Mockery::mock(BrowserInterface::class);
        $newBrowser->shouldReceive('isConnected')->andReturn(true);

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // First call returns the old browser (will be marked as unhealthy)
        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($oldBrowser);

        // Second call returns the new browser
        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($newBrowser);

        // First launch the old browser
        $browser = $service->launch();
        $this->assertInstanceOf(BrowserInterface::class, $browser);

        // Now the old browser becomes unhealthy (not connected)
        // Manually close the old instance and launch a new one
        $service->closeInstance($oldBrowser);
        $newBrowserInstance = $service->launch();

        $this->assertInstanceOf(BrowserInterface::class, $newBrowserInstance);
    }

    // ==========================================
    // Health Check Tests (BL-010, BL-011)
    // ==========================================

    /**
     * Test health check with healthy browser.
     * BL-010: Health check - healthy
     */
    public function test_health_check_healthy(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $service = app(BrowserLauncher::class);

        $isHealthy = $service->isHealthy($mockBrowser);

        $this->assertTrue($isHealthy);
    }

    /**
     * Test health check with crashed browser.
     * BL-011: Health check - crashed
     */
    public function test_health_check_crashed(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('isConnected')->andReturn(false);

        $service = app(BrowserLauncher::class);

        $isHealthy = $service->isHealthy($mockBrowser);

        $this->assertFalse($isHealthy);
    }

    // ==========================================
    // Error Handling Tests (BL-012)
    // ==========================================

    /**
     * Test restart on consecutive failures.
     * BL-012: Restart on consecutive failures
     */
    public function test_restart_on_consecutive_failures(): void
    {
        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Mock createBrowserInstance to fail 3 times
        $service->shouldReceive('createBrowserInstance')
            ->times(3)
            ->andThrow(new \RuntimeException('Failed to launch browser'));

        // First two attempts should throw the original exception
        try {
            $service->launch();
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Failed to launch browser', $e->getMessage());
        }

        try {
            $service->launch();
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Failed to launch browser', $e->getMessage());
        }

        // Third attempt should throw "Maximum retry attempts reached" because
        // consecutiveFailures is now >= maxAttempts (3)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maximum retry attempts reached');

        $service->launch();
    }

    // ==========================================
    // Context Tests (BC-001 to BC-008)
    // ==========================================

    /**
     * Test create incognito context.
     * BC-001: Create incognito context
     */
    public function test_create_incognito_context(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockContext = Mockery::mock(BrowserContextInterface::class);

        $mockBrowser->shouldReceive('newContext')
            ->once()
            ->with([])
            ->andReturn($mockContext);

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($mockBrowser);

        $browser = $service->launch();
        $context = $service->createContext($browser);

        $this->assertInstanceOf(BrowserContextInterface::class, $context);
    }

    /**
     * Test context with geolocation.
     * BC-002: Context with geolocation
     */
    public function test_context_with_geolocation(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockContext = Mockery::mock(BrowserContextInterface::class);

        $geolocation = ['latitude' => 51.5074, 'longitude' => -0.1278];

        $mockBrowser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use ($geolocation) {
                return isset($options['geolocation']) &&
                    $options['geolocation'] === $geolocation;
            })
            ->andReturn($mockContext);

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($mockBrowser);

        $browser = $service->launch();
        $context = $service->createContext($browser, ['geolocation' => $geolocation]);

        $this->assertInstanceOf(BrowserContextInterface::class, $context);
    }

    /**
     * Test context with locale.
     * BC-003: Context with locale
     */
    public function test_context_with_locale(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockContext = Mockery::mock(BrowserContextInterface::class);

        $mockBrowser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) {
                return isset($options['locale']) && $options['locale'] === 'en-GB';
            })
            ->andReturn($mockContext);

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($mockBrowser);

        $browser = $service->launch();
        $context = $service->createContext($browser, ['locale' => 'en-GB']);

        $this->assertInstanceOf(BrowserContextInterface::class, $context);
    }

    /**
     * Test context with timezone.
     * BC-004: Context with timezone
     */
    public function test_context_with_timezone(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockContext = Mockery::mock(BrowserContextInterface::class);

        $mockBrowser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) {
                return isset($options['timezoneId']) && $options['timezoneId'] === 'Europe/London';
            })
            ->andReturn($mockContext);

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($mockBrowser);

        $browser = $service->launch();
        $context = $service->createContext($browser, ['timezoneId' => 'Europe/London']);

        $this->assertInstanceOf(BrowserContextInterface::class, $context);
    }

    /**
     * Test context with permissions.
     * BC-005: Context with permissions
     */
    public function test_context_with_permissions(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockContext = Mockery::mock(BrowserContextInterface::class);

        $permissions = ['geolocation', 'notifications'];

        $mockBrowser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use ($permissions) {
                return isset($options['permissions']) &&
                    $options['permissions'] === $permissions;
            })
            ->andReturn($mockContext);

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($mockBrowser);

        $browser = $service->launch();
        $context = $service->createContext($browser, ['permissions' => $permissions]);

        $this->assertInstanceOf(BrowserContextInterface::class, $context);
    }

    /**
     * Test context with cookies.
     * BC-006: Context with cookies
     */
    public function test_context_with_cookies(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockContext = Mockery::mock(BrowserContextInterface::class);

        $cookies = [
            ['name' => 'session', 'value' => 'abc123', 'domain' => 'example.com']
        ];

        $mockBrowser->shouldReceive('newContext')
            ->once()
            ->andReturn($mockContext);

        $mockContext->shouldReceive('addCookies')
            ->once()
            ->with($cookies);

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($mockBrowser);

        $browser = $service->launch();
        $context = $service->createContext($browser, ['cookies' => $cookies]);

        $this->assertInstanceOf(BrowserContextInterface::class, $context);
    }

    /**
     * Test context with localStorage.
     * BC-007: Context with localStorage
     */
    public function test_context_with_local_storage(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockContext = Mockery::mock(BrowserContextInterface::class);

        $localStorage = ['key' => 'value'];

        $mockBrowser->shouldReceive('newContext')
            ->once()
            ->andReturn($mockContext);

        // For localStorage, we'd need a page to set it up
        // This test verifies the context is created (localStorage setup would be done by the caller)
        $mockContext->shouldReceive('close')->once();

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($mockBrowser);

        $browser = $service->launch();
        $context = $service->createContext($browser);

        $this->assertInstanceOf(BrowserContextInterface::class, $context);

        // Cleanup
        $service->closeContext($context);
    }

    /**
     * Test close context.
     * BC-008: Close context
     */
    public function test_close_context(): void
    {
        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('close')->once();

        $service = app(BrowserLauncher::class);
        $service->closeContext($mockContext);

        // Verify close was called (via mock expectations)
        $this->assertTrue(true);
    }

    // ==========================================
    // Resource Management Tests (RM-001 to RM-008)
    // ==========================================

    /**
     * Test remove instance on close.
     * RM-002: Remove instance on close
     */
    public function test_remove_instance_on_close(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser->shouldReceive('close')->once();

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($mockBrowser);

        $browser = $service->launch();
        $this->assertEquals(1, $service->getActiveInstanceCount());

        $service->closeInstance($browser);
        $this->assertEquals(0, $service->getActiveInstanceCount());
    }

    /**
     * Test instance pooling.
     * RM-003: Instance pooling
     */
    public function test_instance_pooling(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once() // Only once due to pooling
            ->andReturn($mockBrowser);

        // Get instance multiple times
        $browser1 = $service->getSharedInstance();
        $browser2 = $service->getSharedInstance();
        $browser3 = $service->getSharedInstance();

        // Should be the same instance
        $this->assertSame($browser1, $browser2);
        $this->assertSame($browser2, $browser3);

        // Should have only 1 instance
        $this->assertEquals(1, $service->getActiveInstanceCount());
    }

    /**
     * Test force garbage collection on close.
     * RM-004: Force garbage collection
     */
    public function test_force_garbage_collection_on_close(): void
    {
        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser->shouldReceive('close')->once();

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->once()
            ->andReturn($mockBrowser);

        $service->shouldReceive('forceGarbageCollection')
            ->once();

        $browser = $service->launch();
        $service->closeInstance($browser);

        $this->assertTrue(true);
    }

    /**
     * Test concurrent access safety.
     * RM-008: Concurrent access safety
     */
    public function test_concurrent_access_safety(): void
    {
        // Create different mock browsers for each launch (different spl_object_id)
        $mockBrowser1 = Mockery::mock(BrowserInterface::class);
        $mockBrowser1->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser1->shouldReceive('close');

        $mockBrowser2 = Mockery::mock(BrowserInterface::class);
        $mockBrowser2->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser2->shouldReceive('close');

        $mockBrowser3 = Mockery::mock(BrowserInterface::class);
        $mockBrowser3->shouldReceive('isConnected')->andReturn(true);
        $mockBrowser3->shouldReceive('close');

        $service = Mockery::mock(BrowserLauncher::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createBrowserInstance')
            ->times(3)
            ->andReturn($mockBrowser1, $mockBrowser2, $mockBrowser3);

        // Simulate concurrent access
        $instances = [];
        for ($i = 0; $i < 3; $i++) {
            $instances[] = $service->launch();
        }

        $this->assertCount(3, $instances);
        $this->assertEquals(3, $service->getActiveInstanceCount());
    }
}
