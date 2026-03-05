<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\AnalysisResult;

/**
 * Interface for unified AI analysis service.
 *
 * Defines the contract for services that execute unified AI analysis
 * with a single call that returns all indicator results.
 *
 * @see TASK-31-7
 * @see TASK-31-13
 */
interface AIAnalysisServiceInterface
{
    /**
     * Execute unified AI analysis.
     *
     * Performs a single AI call that analyzes all SEO indicators
     * and returns a complete analysis result.
     *
     * @param string $url The URL being analyzed
     * @param string $html The HTML content to analyze
     * @param array $options Optional parameters:
     *   - model: string (specific model to use)
     *   - timeout: int (timeout in seconds)
     *   - force_refresh: bool (bypass cache)
     * @return AnalysisResult The analysis result with all indicators
     * @throws \App\Exceptions\AIAnalysisException On analysis failure
     */
    public function analyze(string $url, string $html, array $options = []): AnalysisResult;

    /**
     * Execute analysis with a specific model.
     *
     * @param string $url The URL being analyzed
     * @param string $html The HTML content to analyze
     * @param string $model The model identifier to use
     * @param array $options Additional options
     * @return AnalysisResult The analysis result
     * @throws \App\Exceptions\AIAnalysisException On analysis failure
     */
    public function analyzeWithModel(string $url, string $html, string $model, array $options = []): AnalysisResult;

    /**
     * Get available AI models.
     *
     * @return array<string, array{id: string, name: string, context_window: int, is_free: bool}>
     */
    public function getAvailableModels(): array;

    /**
     * Get the default model.
     *
     * @return string The default model identifier
     */
    public function getDefaultModel(): string;

    /**
     * Get analysis metadata.
     *
     * @return array<string, mixed> Metadata about the analysis service
     */
    public function getAnalysisMetadata(): array;
}
