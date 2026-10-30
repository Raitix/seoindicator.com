<?php

namespace App\Services\Playwright\Contracts;

/**
 * Interface for Playwright Page.
 *
 * This interface allows mocking the page in unit tests
 * without requiring the actual Playwright package.
 *
 * @see TASK-22-4
 * @see SPEC-022
 */
interface PageInterface
{
    /**
     * Navigate to a URL.
     *
     * @param string $url URL to navigate to
     * @param array $options Navigation options
     */
    public function goto(string $url, array $options = []): self;

    /**
     * Get the current URL.
     */
    public function url(): string;

    /**
     * Get the page content (HTML).
     */
    public function content(): string;

    /**
     * Wait for a selector to appear.
     *
     * @param string $selector CSS selector
     * @param array $options Wait options
     */
    public function waitForSelector(string $selector, array $options = []): self;

    /**
     * Wait for navigation to complete.
     *
     * @param array $options Navigation options
     */
    public function waitForNavigation(array $options = []): self;

    /**
     * Wait for a function to return true.
     *
     * @param string $function JavaScript function
     * @param array $args Function arguments
     * @param array $options Wait options
     */
    public function waitForFunction(string $function, array $args = [], array $options = []): self;

    /**
     * Click an element.
     *
     * @param string $selector CSS selector
     * @param array $options Click options
     */
    public function click(string $selector, array $options = []): self;

    /**
     * Fill an input field.
     *
     * @param string $selector CSS selector
     * @param string $value Value to fill
     */
    public function fill(string $selector, string $value): self;

    /**
     * Type into an input field.
     *
     * @param string $selector CSS selector
     * @param string $text Text to type
     * @param array $options Type options
     */
    public function type(string $selector, string $text, array $options = []): self;

    /**
     * Press a key.
     *
     * @param string $key Key to press
     */
    public function press(string $key): self;

    /**
     * Evaluate JavaScript in the page.
     *
     * @param string $script JavaScript to evaluate
     * @param array $args Arguments to pass
     * @return mixed
     */
    public function evaluate(string $script, array $args = []): mixed;

    /**
     * Take a screenshot.
     *
     * @param array $options Screenshot options
     * @return string Screenshot data (base64 or binary)
     */
    public function screenshot(array $options = []): string;

    /**
     * Generate a PDF.
     *
     * @param array $options PDF options
     * @return string PDF data
     */
    public function pdf(array $options = []): string;

    /**
     * Set up request interception.
     *
     * @param string $pattern URL pattern to match
     * @param callable $handler Handler function
     */
    public function route(string $pattern, callable $handler): self;

    /**
     * Register an event listener.
     *
     * @param string $event Event name
     * @param callable $handler Event handler
     */
    public function on(string $event, callable $handler): self;

    /**
     * Go back in history.
     *
     * @param array $options Navigation options
     */
    public function goBack(array $options = []): self;

    /**
     * Go forward in history.
     *
     * @param array $options Navigation options
     */
    public function goForward(array $options = []): self;

    /**
     * Reload the page.
     *
     * @param array $options Reload options
     */
    public function reload(array $options = []): self;

    /**
     * Close the page.
     */
    public function close(): void;

    /**
     * Check if page is closed.
     */
    public function isClosed(): bool;

    /**
     * Get the page title.
     */
    public function title(): string;

    /**
     * Set the viewport size.
     *
     * @param array $viewport Viewport dimensions
     */
    public function setViewport(array $viewport): self;

    /**
     * Get the current viewport.
     */
    public function viewport(): array;

    /**
     * Set the page timeout.
     *
     * @param int $timeout Timeout in milliseconds
     */
    public function setDefaultTimeout(int $timeout): self;

    /**
     * Set the navigation timeout.
     *
     * @param int $timeout Timeout in milliseconds
     */
    public function setDefaultNavigationTimeout(int $timeout): self;
}
