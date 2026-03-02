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
     * @var array<string, BrowserInterface>
     */
    protected array $instances = [];

    /**
     * Shared browser instance.
     */
    protected ?BrowserInterface $sharedInstance = null;

    /**
     * Instance creation timestamps.
     *
     * @var array<string, int>
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

        // TODO: Implement actual browser launch (TASK-22-4)
        // This is a skeleton - Developer will implement using Playwright PHP SDK
        // For now, return a mock implementation for testing

        throw new \RuntimeException('BrowserLauncher::launch() not implemented - awaiting Developer implementation');
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
            $browser->close();
            unset($this->instances[$id]);
            unset($this->instanceTimestamps[$id]);

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
     * @param array $options
     * @return BrowserInterface
     */
    protected function createBrowserInstance(array $options = []): BrowserInterface
    {
        // This method is intended to be overridden in tests
        return $this->launch($options);
    }
}
