<?php

namespace App\Services\Playwright;

use App\Services\Playwright\Contracts\BrowserInterface;
use App\Services\Playwright\Contracts\BrowserContextInterface;
use Illuminate\Support\Facades\Log;

/**
 * BrowserLauncher Service
 *
 * Service for managing Playwright browser instances with support for
 * instance pooling, health checks, and resource management.
 *
 * @see TASK-22-4
 * @see SPEC-022
 */
class BrowserLauncher
{
    /**
     * Active browser instances.
     *
     * @var array<int, BrowserInterface>
     */
    protected array $instances = [];

    /**
     * Shared browser instance.
     */
    protected ?BrowserInterface $sharedInstance = null;

    /**
     * Instance creation timestamps.
     *
     * @var array<int, int>
     */
    protected array $instanceTimestamps = [];

    /**
     * Consecutive failure count.
     */
    protected int $consecutiveFailures = 0;

    /**
     * Launch a new browser instance.
     *
     * @param array $options Browser launch options
     * @return BrowserInterface
     * @throws \RuntimeException If max instances reached or launch fails
     */
    public function launch(array $options = []): BrowserInterface
    {
        // Check max instances limit
        $maxInstances = config('playwright.max_instances', 3);
        if (count($this->instances) >= $maxInstances) {
            throw new \RuntimeException('Maximum browser instances reached');
        }

        // Check for consecutive failures
        $maxAttempts = config('playwright.retry.max_attempts', 3);
        if ($this->consecutiveFailures >= $maxAttempts) {
            throw new \RuntimeException('Maximum retry attempts reached');
        }

        // Merge with default options
        $defaultOptions = [
            'browser' => config('playwright.browser', 'chromium'),
            'headless' => config('playwright.headless', true),
            'timeout' => config('playwright.timeout', 30000),
            'slow_mo' => config('playwright.slow_mo', 0),
            'viewport' => config('playwright.viewport', ['width' => 1280, 'height' => 720]),
            'userAgent' => config('playwright.user_agent', 'SEOIndicator/1.0 Headless Browser'),
            'ignoreHTTPSErrors' => config('playwright.ignore_https_errors', false),
        ];

        $mergedOptions = array_merge($defaultOptions, $options);

        try {
            // Create browser instance via protected method (mockable in tests)
            $browser = $this->createBrowserInstance($mergedOptions);

            // Track instance
            $id = spl_object_id($browser);
            $this->instances[$id] = $browser;
            $this->instanceTimestamps[$id] = time();

            // Reset failure counter on success
            $this->consecutiveFailures = 0;

            Log::debug('Browser instance launched', [
                'instance_id' => $id,
                'options' => $mergedOptions,
            ]);

            return $browser;
        } catch (\Exception $e) {
            $this->consecutiveFailures++;

            Log::error('Failed to launch browser instance', [
                'error' => $e->getMessage(),
                'consecutive_failures' => $this->consecutiveFailures,
            ]);

            // Check if we've exceeded max retry attempts after this failure
            if ($this->consecutiveFailures >= $maxAttempts) {
                throw new \RuntimeException('Maximum retry attempts reached');
            }

            throw $e;
        }
    }

    /**
     * Get or create a shared browser instance.
     *
     * @return BrowserInterface
     */
    public function getSharedInstance(): BrowserInterface
    {
        if ($this->sharedInstance && $this->isHealthy($this->sharedInstance)) {
            return $this->sharedInstance;
        }

        // Create new shared instance
        $this->sharedInstance = $this->launch();

        return $this->sharedInstance;
    }

    /**
     * Close a specific browser instance.
     *
     * @param BrowserInterface $browser
     */
    public function closeInstance(BrowserInterface $browser): void
    {
        $id = spl_object_id($browser);

        if (isset($this->instances[$id])) {
            try {
                $browser->close();
            } catch (\Exception $e) {
                Log::warning('Error closing browser instance', [
                    'instance_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            unset($this->instances[$id]);
            unset($this->instanceTimestamps[$id]);

            // Clear shared instance if it was this one
            if ($this->sharedInstance && spl_object_id($this->sharedInstance) === $id) {
                $this->sharedInstance = null;
            }

            // Force garbage collection
            $this->forceGarbageCollection();

            Log::debug('Browser instance closed', ['instance_id' => $id]);
        }
    }

    /**
     * Close all browser instances.
     */
    public function closeAll(): void
    {
        foreach ($this->instances as $id => $browser) {
            try {
                $browser->close();
            } catch (\Exception $e) {
                Log::warning('Failed to close browser instance', [
                    'instance_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->instances = [];
        $this->instanceTimestamps = [];
        $this->sharedInstance = null;

        $this->forceGarbageCollection();

        Log::info('All browser instances closed');
    }

    /**
     * Create a new browser context.
     *
     * @param BrowserInterface $browser
     * @param array $options Context options
     * @return BrowserContextInterface
     */
    public function createContext(BrowserInterface $browser, array $options = []): BrowserContextInterface
    {
        // Handle cookies separately
        $cookies = $options['cookies'] ?? null;
        unset($options['cookies']);

        $context = $browser->newContext($options);

        // Add cookies if provided
        if ($cookies && method_exists($context, 'addCookies')) {
            $context->addCookies($cookies);
        }

        return $context;
    }

    /**
     * Close a browser context.
     *
     * @param BrowserContextInterface $context
     */
    public function closeContext(BrowserContextInterface $context): void
    {
        $context->close();
    }

    /**
     * Check if a browser instance is healthy.
     *
     * @param BrowserInterface $browser
     * @return bool
     */
    public function isHealthy(BrowserInterface $browser): bool
    {
        try {
            return $browser->isConnected();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the count of active browser instances.
     *
     * @return int
     */
    public function getActiveInstanceCount(): int
    {
        return count($this->instances);
    }

    /**
     * Force garbage collection.
     */
    protected function forceGarbageCollection(): void
    {
        gc_collect_cycles();
    }

    /**
     * Create browser instance (for mocking in tests).
     *
     * This method should be overridden in tests to return mock browser instances.
     * In production, this would use the actual Playwright PHP SDK.
     *
     * @param array $options
     * @return BrowserInterface
     * @throws \RuntimeException When Playwright is not available
     */
    protected function createBrowserInstance(array $options = []): BrowserInterface
    {
        // In production, this would use the Playwright PHP SDK:
        // $playwright = \Playwright\Playwright::create();
        // $browserType = $playwright->{$options['browser']};
        // return $browserType->launch($options);
        
        // For now, this requires the Playwright PHP SDK to be installed
        // The tests mock this method, so this code path is only hit in production
        throw new \RuntimeException(
            'Playwright PHP SDK not installed. Install with: composer require playwright/browser'
        );
    }
}
