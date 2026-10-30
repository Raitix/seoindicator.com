<?php

namespace App\Services\Playwright\Contracts;

/**
 * Interface for Playwright BrowserContext.
 *
 * This interface allows mocking the browser context in unit tests
 * without requiring the actual Playwright package.
 *
 * @see TASK-22-4
 * @see SPEC-022
 */
interface BrowserContextInterface
{
    /**
     * Create a new page in this context.
     */
    public function newPage(): PageInterface;

    /**
     * Close the context.
     */
    public function close(): void;

    /**
     * Get all pages in this context.
     *
     * @return PageInterface[]
     */
    public function pages(): array;

    /**
     * Add cookies to the context.
     *
     * @param array $cookies Cookie data
     */
    public function addCookies(array $cookies): void;

    /**
     * Get all cookies in the context.
     *
     * @return array
     */
    public function cookies(): array;

    /**
     * Clear all cookies.
     */
    public function clearCookies(): void;

    /**
     * Set extra HTTP headers for all requests.
     *
     * @param array $headers Headers to set
     */
    public function setExtraHTTPHeaders(array $headers): void;

    /**
     * Get the storage state (cookies and localStorage).
     *
     * @param string|null $path Path to save state
     * @return array
     */
    public function storageState(?string $path = null): array;

    /**
     * Grant permissions to the context.
     *
     * @param array $permissions Permissions to grant
     */
    public function grantPermissions(array $permissions): void;

    /**
     * Clear all granted permissions.
     */
    public function clearPermissions(): void;

    /**
     * Set geolocation.
     *
     * @param array|null $geolocation Geolocation data
     */
    public function setGeolocation(?array $geolocation): void;

    /**
     * Set offline mode.
     *
     * @param bool $enabled Whether offline mode is enabled
     */
    public function setOffline(bool $enabled): void;
}
