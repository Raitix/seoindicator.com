<?php

namespace App\Services\Analysis\Indicators;

use App\Services\URLValidationService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\CssSelector\CssSelectorConverter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RedirectMiddleware;
use RuntimeException;
use DOMDocument;
use DOMXPath;
use DOMNode;
use DOMNodeList;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Abstract base class for SEO indicator processors.
 *
 * Provides common functionality for fetching URLs, parsing HTML,
 * and returning standardized results using native PHP DOM.
 * 
 * Includes SSRF protection with DNS rebinding prevention via redirect validation.
 */
abstract class IndicatorProcessor
{
    protected URLValidationService $urlValidator;
    protected Client $httpClient;
    protected ?CssSelectorConverter $cssConverter = null;

    /**
     * Default timeout in seconds.
     */
    protected int $timeout = 30;

    /**
     * Default max response size in bytes (10MB).
     */
    protected int $maxResponseSize = 10485760;

    /**
     * Maximum number of redirects.
     */
    protected int $maxRedirects = 5;

    /**
     * User agent for HTTP requests.
     */
    protected string $userAgent = 'SEOIndicator/1.0 (+https://seoindicator.com/bot)';

    public function __construct(URLValidationService $urlValidator)
    {
        $this->urlValidator = $urlValidator;
        $this->httpClient = $this->createHttpClient();
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
     * Includes DNS rebinding protection by validating all redirect targets.
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

            // Log if available (not in unit tests)
            if (app()->bound('log')) {
                Log::info('URL fetched successfully', [
                    'url' => $normalizedUrl,
                    'status' => $statusCode,
                    'size' => strlen($content),
                    'duration_ms' => $duration,
                ]);
            }

            return $content;

        } catch (RequestException $e) {
            throw new RuntimeException("Failed to fetch URL: {$e->getMessage()}");
        }
    }

    /**
     * Create HTTP client with SSRF-protected redirect handling.
     *
     * @return Client
     */
    protected function createHttpClient(): Client
    {
        $urlValidator = $this->urlValidator;

        return new Client([
            'timeout' => $this->timeout,
            'max_response_size' => $this->maxResponseSize,
            'headers' => [
                'User-Agent' => $this->userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ],
            'allow_redirects' => [
                'max' => $this->maxRedirects,
                'strict' => true,
                'referer' => true,
                'on_redirect' => function (RequestInterface $request, ResponseInterface $response, UriInterface $uri) use ($urlValidator) {
                    $redirectUrl = (string) $uri;

                    // Validate redirect URL for SSRF (DNS rebinding protection)
                    $validation = $urlValidator->validateRedirect($redirectUrl);

                    if (!$validation['valid']) {
                        // Log the blocked redirect
                        Log::channel('security')->warning('SSRF redirect blocked', [
                            'original_url' => (string) $request->getUri(),
                            'redirect_url' => $redirectUrl,
                            'reason' => $validation['error'] ?? 'unknown',
                        ]);

                        throw new RuntimeException('Redirect blocked: ' . ($validation['error'] ?? 'SSRF protection'));
                    }

                    // Log successful redirect validation
                    Log::debug('Redirect validated', [
                        'redirect_url' => $redirectUrl,
                    ]);
                },
            ],
            'verify' => true,
            'http_errors' => false,
        ]);
    }

    /**
     * Parse HTML into a DOMDocument instance.
     *
     * @param string $html
     * @return DOMDocument
     * @throws RuntimeException If HTML is invalid
     */
    protected function parseHtml(string $html): DOMDocument
    {
        if (empty(trim($html))) {
            throw new RuntimeException('Invalid HTML content');
        }

        $dom = new DOMDocument();
        
        // Suppress libxml errors (HTML5 tags cause warnings)
        libxml_use_internal_errors(true);
        
        // Load HTML with UTF-8 encoding
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        
        libxml_clear_errors();

        if (!$loaded || $dom->documentElement === null) {
            throw new RuntimeException('Invalid HTML content');
        }

        return $dom;
    }

    /**
     * Get the CSS selector converter.
     *
     * @return CssSelectorConverter
     */
    protected function getCssConverter(): CssSelectorConverter
    {
        if ($this->cssConverter === null) {
            $this->cssConverter = new CssSelectorConverter();
        }
        return $this->cssConverter;
    }

    /**
     * Convert CSS selector to XPath.
     *
     * @param string $selector
     * @return string
     */
    protected function cssToXpath(string $selector): string
    {
        return $this->getCssConverter()->toXPath($selector);
    }

    /**
     * Query DOM using CSS selector.
     *
     * @param DOMDocument $dom
     * @param string $selector
     * @return DOMNodeList
     */
    protected function query(DOMDocument $dom, string $selector): DOMNodeList
    {
        $xpath = new DOMXPath($dom);
        $xpathExpr = $this->cssToXpath($selector);
        $result = $xpath->query($xpathExpr);
        
        return $result !== false ? $result : new DOMNodeList();
    }

    /**
     * Extract text content from a selector.
     *
     * @param DOMDocument $dom
     * @param string $selector
     * @return string|null
     */
    protected function extractText(DOMDocument $dom, string $selector): ?string
    {
        $nodes = $this->query($dom, $selector);
        
        if ($nodes->length === 0) {
            return null;
        }

        return trim($nodes->item(0)->textContent);
    }

    /**
     * Extract attribute value from a selector.
     *
     * @param DOMDocument $dom
     * @param string $selector
     * @param string $attribute
     * @return string|null
     */
    protected function extractAttribute(DOMDocument $dom, string $selector, string $attribute): ?string
    {
        $nodes = $this->query($dom, $selector);
        
        if ($nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);
        if (!$node->hasAttributes()) {
            return null;
        }

        $attr = $node->attributes->getNamedItem($attribute);
        return $attr !== null ? $attr->nodeValue : null;
    }

    /**
     * Extract all matching elements' text content.
     *
     * @param DOMDocument $dom
     * @param string $selector
     * @return array
     */
    protected function extractAllText(DOMDocument $dom, string $selector): array
    {
        $nodes = $this->query($dom, $selector);
        $results = [];

        foreach ($nodes as $node) {
            $results[] = trim($node->textContent);
        }

        return $results;
    }

    /**
     * Extract all matching elements with attributes.
     *
     * @param DOMDocument $dom
     * @param string $selector
     * @param array $attributes
     * @return array
     */
    protected function extractAllWithAttributes(DOMDocument $dom, string $selector, array $attributes): array
    {
        $nodes = $this->query($dom, $selector);
        $results = [];

        foreach ($nodes as $node) {
            $item = ['text' => trim($node->textContent)];
            
            foreach ($attributes as $attr) {
                $item[$attr] = $node->hasAttributes() 
                    ? ($node->attributes->getNamedItem($attr)?->nodeValue ?? null)
                    : null;
            }
            
            $results[] = $item;
        }

        return $results;
    }

    /**
     * Count elements matching a selector.
     *
     * @param DOMDocument $dom
     * @param string $selector
     * @return int
     */
    protected function countElements(DOMDocument $dom, string $selector): int
    {
        return $this->query($dom, $selector)->length;
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
        $this->httpClient = $this->createHttpClient();
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

    /**
     * Set max redirects.
     *
     * @param int $max
     */
    public function setMaxRedirects(int $max): void
    {
        $this->maxRedirects = $max;
        $this->httpClient = $this->createHttpClient();
    }
}
