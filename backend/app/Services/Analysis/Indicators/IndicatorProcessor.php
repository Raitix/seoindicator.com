<?php

namespace App\Services\Analysis\Indicators;

use App\Services\URLValidationService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;

/**
 * Abstract base class for SEO indicator processors.
 *
 * Provides common functionality for fetching URLs, parsing HTML,
 * and returning standardized results.
 */
abstract class IndicatorProcessor
{
    protected URLValidationService $urlValidator;
    protected Client $httpClient;

    /**
     * Default timeout in seconds.
     */
    protected int $timeout = 30;

    /**
     * Default max response size in bytes (10MB).
     */
    protected int $maxResponseSize = 10485760;

    /**
     * User agent for HTTP requests.
     */
    protected string $userAgent = 'SEOIndicator/1.0 (+https://seoindicator.com/bot)';

    public function __construct(URLValidationService $urlValidator)
    {
        $this->urlValidator = $urlValidator;
        $this->httpClient = new Client([
            'timeout' => $this->timeout,
            'max_response_size' => $this->maxResponseSize,
            'headers' => [
                'User-Agent' => $this->userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ],
            'allow_redirects' => [
                'max' => 5,
                'strict' => true,
                'referer' => true,
            ],
            'verify' => true,
            'http_errors' => false,
        ]);
    }

    /**
     * Get the unique slug identifier for this indicator.
     */
    abstract public function slug(): string;

    /**
     * Get the human-readable name for this indicator.
     */
    abstract public function name(): string;

    /**
     * Get the maximum possible score for this indicator.
     */
    abstract public function maxScore(): float;

    /**
     * Process the URL and return analysis results.
     */
    abstract public function process(string $url): IndicatorResult;

    /**
     * Fetch URL content with SSRF protection.
     *
     * @param string $url
     * @return string HTML content
     * @throws RuntimeException If fetch fails or SSRF protection blocks
     */
    protected function fetchUrl(string $url): string
    {
        $startTime = microtime(true);

        // Validate URL for SSRF protection
        $validation = $this->urlValidator->validate($url);

        if (!$validation['valid']) {
            throw new RuntimeException($validation['error']);
        }

        $normalizedUrl = $validation['normalized_url'] ?? $url;

        try {
            $response = $this->httpClient->get($normalizedUrl);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                throw new RuntimeException("HTTP error {$statusCode} when fetching URL");
            }

            $content = (string) $response->getBody();

            $duration = round((microtime(true) - $startTime) * 1000);

            Log::info('URL fetched successfully', [
                'url' => $normalizedUrl,
                'status' => $statusCode,
                'size' => strlen($content),
                'duration_ms' => $duration,
            ]);

            return $content;

        } catch (RequestException $e) {
            throw new RuntimeException("Failed to fetch URL: {$e->getMessage()}");
        }
    }

    /**
     * Parse HTML into a DomCrawler instance.
     *
     * @param string $html
     * @return Crawler
     */
    protected function parseHtml(string $html): Crawler
    {
        $crawler = new Crawler($html);

        // Check for valid HTML
        if ($crawler->filter('html')->count() === 0) {
            throw new RuntimeException('Invalid HTML content');
        }

        return $crawler;
    }

    /**
     * Extract text content from a selector.
     *
     * @param Crawler $crawler
     * @param string $selector
     * @return string|null
     */
    protected function extractText(Crawler $crawler, string $selector): ?string
    {
        $node = $crawler->filter($selector)->first();
        if ($node->count() === 0) {
            return null;
        }

        return trim($node->text());
    }

    /**
     * Extract attribute value from a selector.
     *
     * @param Crawler $crawler
     * @param string $selector
     * @param string $attribute
     * @return string|null
     */
    protected function extractAttribute(Crawler $crawler, string $selector, string $attribute): ?string
    {
        $node = $crawler->filter($selector)->first();
        if ($node->count() === 0) {
            return null;
        }

        return $node->attr($attribute);
    }

    /**
     * Extract all matching elements' text content.
     *
     * @param Crawler $crawler
     * @param string $selector
     * @return array
     */
    protected function extractAllText(Crawler $crawler, string $selector): array
    {
        $results = [];
        $crawler->filter($selector)->each(function (Crawler $node) use (&$results) {
            $results[] = trim($node->text());
        });

        return $results;
    }

    /**
     * Extract all matching elements with attributes.
     *
     * @param Crawler $crawler
     * @param string $selector
     * @param array $attributes
     * @return array
     */
    protected function extractAllWithAttributes(Crawler $crawler, string $selector, array $attributes): array
    {
        $results = [];
        $crawler->filter($selector)->each(function (Crawler $node) use (&$results, $attributes) {
            $item = ['text' => trim($node->text())];
            foreach ($attributes as $attr) {
                $item[$attr] = $node->attr($attr);
            }
            $results[] = $item;
        });

        return $results;
    }

    /**
     * Count elements matching a selector.
     *
     * @param Crawler $crawler
     * @param string $selector
     * @return int
     */
    protected function countElements(Crawler $crawler, string $selector): int
    {
        return $crawler->filter($selector)->count();
    }

    /**
     * Calculate processing time in milliseconds.
     *
     * @param float $startTime
     * @return int
     */
    protected function calculateProcessingTime(float $startTime): int
    {
        return (int) round((microtime(true) - $startTime) * 1000);
    }

    /**
     * Create a recommendation array.
     *
     * @param string $priority high, medium, low
     * @param string $message
     * @param string|null $details
     * @return array
     */
    protected function recommendation(string $priority, string $message, ?string $details = null): array
    {
        return [
            'priority' => $priority,
            'message' => $message,
            'details' => $details,
        ];
    }

    /**
     * Set timeout for HTTP requests.
     *
     * @param int $seconds
     */
    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
        $this->httpClient = new Client([
            'timeout' => $this->timeout,
            'max_response_size' => $this->maxResponseSize,
            'headers' => [
                'User-Agent' => $this->userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ],
            'allow_redirects' => [
                'max' => 5,
                'strict' => true,
                'referer' => true,
            ],
            'verify' => true,
            'http_errors' => false,
        ]);
    }

    /**
     * Set max response size.
     *
     * @param int $bytes
     */
    public function setMaxResponseSize(int $bytes): void
    {
        $this->maxResponseSize = $bytes;
    }
}
