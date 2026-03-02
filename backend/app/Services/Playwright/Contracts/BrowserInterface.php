<?php

namespace App\Services\Playwright\Contracts;

/**
 * Interface for Playwright Browser.
 *
 * This interface allows mocking the browser in unit tests
 * without requiring the actual Playwright package.
 *
 * @see TASK-22-4
 * @see SPEC-022
 */
interface BrowserInterface
{
    /**
     * Check if browser is still connected.
     */
    public function isConnected(): bool;

    /**
     * Create a new browser context.
     *
     * @param array $options Context options
     */
    public function newContext(array $options = []): BrowserContextInterface;

    /**
     * Create a new page in the default context.
     */
    public function newPage(): PageInterface;

    /**
     * Close the browser.
     */
    public function close(): void;

    /**
     * Get all browser contexts.
     *
     * @return BrowserContextInterface[]
     */
    public function contexts(): array;

    /**
     * Get browser version.
     */
    public function version(): string;
}
