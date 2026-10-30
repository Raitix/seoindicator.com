<?php

namespace App\Services\AI\Contracts;

/**
 * Interface for unified prompt building service.
 *
 * Builds consolidated prompts for unified AI analysis that request
 * all indicator results in a single API call.
 *
 * @see TASK-31-4
 * @see TASK-31-7
 */
interface UnifiedPromptServiceInterface
{
    /**
     * Build a consolidated prompt for unified AI analysis.
     *
     * @param string $url The URL being analyzed
     * @param string $html The HTML content to analyze
     * @param array $options Optional parameters
     * @return string The built prompt
     */
    public function buildPrompt(string $url, string $html, array $options = []): string;

    /**
     * Get the estimated token overhead for the prompt.
     *
     * @return int Token overhead
     */
    public function getTokenOverhead(): int;
}
