<?php

namespace App\Services\SEO\Checkers;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for SEO indicator checkers.
 *
 * Provides common utilities and default implementations for indicator checkers.
 * All concrete indicator checkers should extend this class.
 *
 * @see TASK-18-3
 * @see IndicatorCheckerInterface
 */
abstract class BaseIndicatorChecker implements IndicatorCheckerInterface
{
    /**
     * Default score to use when check fails or returns inconclusive results.
     */
    protected int $defaultScore = 0;

    /**
     * Default weight for this indicator.
     * Override in subclass to set specific weight.
     */
    protected float $defaultWeight = 0.05;

    /**
     * Default category for this indicator.
     * Override in subclass to set specific category.
     */
    protected string $defaultCategory = 'technical';

    /**
     * Whether to log errors during check execution.
     */
    protected bool $logErrors = true;

    /**
     * Get the unique identifier for this indicator.
     * Override to provide custom ID.
     */
    public function id(): string
    {
        // Default: convert class name to snake_case
        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Checker', '', $className)));
    }

    /**
     * Get the weight of this indicator in the overall score.
     */
    public function weight(): float
    {
        return $this->defaultWeight;
    }

    /**
     * Get the category this indicator belongs to.
     */
    public function category(): string
    {
        return $this->defaultCategory;
    }

    /**
     * Perform the SEO indicator check.
     *
     * Template method that wraps the abstract performCheck() method
     * with error handling and logging.
     *
     * @param Crawler $crawler Symfony DomCrawler with loaded HTML
     * @param string $url The URL being analyzed
     * @param array<string, mixed> $options Optional configuration options
     * @return IndicatorResult The check result with score and details
     */
    public function check(Crawler $crawler, string $url, array $options = []): IndicatorResult
    {
        $startTime = microtime(true);

        try {
            // Validate inputs
            if (empty($url)) {
                return $this->createErrorResult('URL is required');
            }

            // Perform the actual check (implemented by subclass)
            $result = $this->performCheck($crawler, $url, $options);

            // Log successful check
            $duration = round((microtime(true) - $startTime) * 1000);
            Log::debug('Indicator check completed', [
                'indicator' => $this->id(),
                'url' => $url,
                'score' => $result->score,
                'duration_ms' => $duration,
            ]);

            return $result;

        } catch (\Throwable $e) {
            // Log error if enabled
            if ($this->logErrors) {
                Log::error('Indicator check failed', [
                    'indicator' => $this->id(),
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // Return error result
            return $this->createErrorResult($e->getMessage());
        }
    }

    /**
     * Perform the actual indicator check.
     *
     * Must be implemented by subclasses to perform the specific check logic.
     *
     * @param Crawler $crawler Symfony DomCrawler with loaded HTML
     * @param string $url The URL being analyzed
     * @param array<string, mixed> $options Optional configuration options
     * @return IndicatorResult The check result
     */
    abstract protected function performCheck(Crawler $crawler, string $url, array $options = []): IndicatorResult;

    /**
     * Get actionable suggestions based on the check result.
     *
     * Default implementation returns empty array.
     * Override in subclass to provide specific suggestions.
     *
     * @param IndicatorResult $result The result from check()
     * @return array<int, Suggestion> Array of suggestions
     */
    public function getSuggestions(IndicatorResult $result): array
    {
        return [];
    }

    /**
     * Create an IndicatorResult with all required fields.
     *
     * @param int $score Score 0-3
     * @param bool $passed Whether the check passed
     * @param array<string, mixed> $details Additional details
     * @return IndicatorResult
     */
    protected function createResult(int $score, bool $passed, array $details = []): IndicatorResult
    {
        return new IndicatorResult(
            id: $this->id(),
            name: $this->name(),
            category: $this->category(),
            score: $score,
            weight: $this->weight(),
            passed: $passed,
            details: $details
        );
    }

    /**
     * Create an error result.
     *
     * @param string $errorMessage The error message
     * @return IndicatorResult
     */
    protected function createErrorResult(string $errorMessage): IndicatorResult
    {
        return $this->createResult(
            score: $this->defaultScore,
            passed: false,
            details: [
                'error' => true,
                'message' => $errorMessage,
            ]
        );
    }

    /**
     * Create a Suggestion with all required fields.
     *
     * @param string $type Type: 'warning', 'error', or 'info'
     * @param string $message Human-readable message
     * @param string|null $action Optional action to take
     * @param int $priority Priority 1-3
     * @return Suggestion
     */
    protected function createSuggestion(
        string $type,
        string $message,
        ?string $action = null,
        int $priority = 1
    ): Suggestion {
        return new Suggestion(
            type: $type,
            message: $message,
            action: $action,
            priority: $priority
        );
    }

    /**
     * Extract text content from a CSS selector.
     *
     * @param Crawler $crawler The DOM crawler
     * @param string $selector CSS selector
     * @return string|null Extracted text or null if not found
     */
    protected function extractText(Crawler $crawler, string $selector): ?string
    {
        try {
            $node = $crawler->filter($selector)->first();
            if ($node->count() === 0) {
                return null;
            }
            return trim($node->text());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract an attribute value from a CSS selector.
     *
     * @param Crawler $crawler The DOM crawler
     * @param string $selector CSS selector
     * @param string $attribute Attribute name to extract
     * @return string|null Extracted attribute value or null if not found
     */
    protected function extractAttribute(Crawler $crawler, string $selector, string $attribute): ?string
    {
        try {
            $node = $crawler->filter($selector)->first();
            if ($node->count() === 0) {
                return null;
            }
            return $node->attr($attribute);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract all elements matching a selector with their attributes.
     *
     * @param Crawler $crawler The DOM crawler
     * @param string $selector CSS selector
     * @param array<int, string> $attributes Attributes to extract
     * @return array<int, array<string, string|null>> Array of element data
     */
    protected function extractAllWithAttributes(Crawler $crawler, string $selector, array $attributes): array
    {
        try {
            $results = [];
            $crawler->filter($selector)->each(function (Crawler $node) use ($attributes, &$results) {
                $item = ['text' => trim($node->text())];
                foreach ($attributes as $attr) {
                    $item[$attr] = $node->attr($attr);
                }
                $results[] = $item;
            });
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Count elements matching a CSS selector.
     *
     * @param Crawler $crawler The DOM crawler
     * @param string $selector CSS selector
     * @return int Number of matching elements
     */
    protected function countElements(Crawler $crawler, string $selector): int
    {
        try {
            return $crawler->filter($selector)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check if an element exists matching a CSS selector.
     *
     * @param Crawler $crawler The DOM crawler
     * @param string $selector CSS selector
     * @return bool True if element exists
     */
    protected function elementExists(Crawler $crawler, string $selector): bool
    {
        return $this->countElements($crawler, $selector) > 0;
    }

    /**
     * Get all text content from elements matching a selector.
     *
     * @param Crawler $crawler The DOM crawler
     * @param string $selector CSS selector
     * @return array<int, string> Array of text content
     */
    protected function extractAllText(Crawler $crawler, string $selector): array
    {
        try {
            $results = [];
            $crawler->filter($selector)->each(function (Crawler $node) use (&$results) {
                $results[] = trim($node->text());
            });
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }
}
