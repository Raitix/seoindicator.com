<?php

namespace App\Services\SERP;

use App\Services\SERPFetcherInterface;
use App\Services\Playwright\Contracts\BrowserInterface;
use App\Services\Playwright\Contracts\BrowserContextInterface;
use App\Services\Playwright\Contracts\PageInterface;
use App\Services\Playwright\BrowserLauncher;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Self-hosted SERP fetcher using Playwright for headless browser scraping.
 *
 * Provides unlimited SERP queries using web scraping with Playwright.
 * Carries IP blocking risk - use with caution and proper rate limiting.
 *
 * @see TASK-1-9
 * @see SPEC-001
 *
 * @warning This scraper may violate search engine Terms of Service.
 *          Use as a last resort after other fetchers (GoogleCSEFetcher, etc.).
 *          Implement proper rate limiting and robots.txt compliance.
 */
class ScraperSERPFetcher implements SERPFetcherInterface
{
    /**
     * Default user agents for rotation.
     */
    protected const USER_AGENTS = [
        'desktop' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ],
        'mobile' => [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.43 Mobile Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
        ],
    ];

    /**
     * Search engine base URLs.
     */
    protected const SEARCH_ENGINE_URLS = [
        'google' => 'https://www.google.com/search',
        'bing' => 'https://www.bing.com/search',
    ];

    /**
     * CAPTCHA detection patterns.
     */
    protected const CAPTCHA_SELECTORS = [
        '#captcha-form',
        '.g-recaptcha',
        'iframe[src*="recaptcha"]',
        '#cf-turnstile',
        '.captcha',
        '[data-testid="captcha"]',
    ];

    /**
     * CAPTCHA page patterns in content.
     */
    protected const CAPTCHA_PATTERNS = [
        'recaptcha',
        'captcha',
        'unusual traffic',
        'verify you are human',
        'access denied',
        'blocked',
        'automated requests',
    ];

    /**
     * Rate limiting state.
     */
    protected static ?float $lastRequestTime = null;
    protected static int $requestCount = 0;
    protected static ?string $lastSearchEngine = null;

    public function __construct(
        protected BrowserLauncher $browserLauncher,
        protected ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? app(LoggerInterface::class);
    }

    /**
     * Fetch SERP results using headless browser scraping.
     *
     * @param string $keyword Search keyword
     * @param SERPOptions $options Search options
     * @return SERPResult
     */
    public function fetch(string $keyword, SERPOptions $options): SERPResult
    {
        $start = microtime(true);

        try {
            // Apply rate limiting
            $this->applyRateLimiting($options->getSearchEngine());

            // Get browser and create context
            $browser = $this->browserLauncher->getSharedInstance();
            $context = $this->createContext($browser, $options);

            // Create page and navigate
            $page = $context->newPage();
            $url = $this->buildSearchUrl($keyword, $options);

            $this->logger->info('Scraping SERP', [
                'keyword' => $keyword,
                'search_engine' => $options->getSearchEngine(),
                'url' => $url,
            ]);

            // Navigate with timeout
            $timeout = config('serp-scraper.timeout', 30000);
            $page->setDefaultNavigationTimeout($timeout);
            $page->goto($url, ['waitUntil' => 'networkidle', 'timeout' => $timeout]);

            // Check for CAPTCHA
            $captchaDetected = $this->detectCaptcha($page);
            if ($captchaDetected) {
                return $this->buildCaptchaErrorResult($keyword, $options, $start);
            }

            // Extract SERP results
            $results = $this->extractSERPResults($page, $options);

            // Cleanup
            $page->close();
            $context->close();

            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $checkedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM);

            // Update rate limiting state
            self::$lastRequestTime = microtime(true);
            self::$requestCount++;
            self::$lastSearchEngine = $options->getSearchEngine();

            $this->logger->info('SERP scrape completed', [
                'keyword' => $keyword,
                'results_count' => count($results),
                'duration_ms' => $durationMs,
            ]);

            return new SERPResultData(
                $keyword,
                $options->getSearchEngine(),
                $options->getLocation(),
                $options->getDevice(),
                $results,
                $checkedAt,
                null,
                $durationMs,
                null
            );
        } catch (\Exception $e) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $checkedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM);

            $this->logger->error('SERP scrape failed', [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            return new SERPResultData(
                $keyword,
                $options->getSearchEngine(),
                $options->getLocation(),
                $options->getDevice(),
                [],
                $checkedAt,
                'Scraper error: ' . $e->getMessage(),
                $durationMs,
                null
            );
        }
    }

    /**
     * Build search URL for the given keyword and options.
     *
     * @param string $keyword
     * @param SERPOptions $options
     * @return string
     */
    protected function buildSearchUrl(string $keyword, SERPOptions $options): string
    {
        $baseUrl = self::SEARCH_ENGINE_URLS[$options->getSearchEngine()] ?? self::SEARCH_ENGINE_URLS['google'];
        $encodedKeyword = urlencode($keyword);

        $params = [
            'q' => $encodedKeyword,
            'num' => 100,
        ];

        // Add location parameter (Google specific)
        if ($options->getLocation() !== 'US' && $options->getSearchEngine() === 'google') {
            $params['gl'] = strtolower($options->getLocation());
        }

        // Build URL
        $queryString = http_build_query($params);
        return "{$baseUrl}?{$queryString}";
    }

    /**
     * Create browser context with user agent and proxy settings.
     *
     * @param BrowserInterface $browser
     * @param SERPOptions $options
     * @return BrowserContextInterface
     */
    protected function createContext(BrowserInterface $browser, SERPOptions $options): BrowserContextInterface
    {
        $contextOptions = [
            'userAgent' => $this->getUserAgent($options->getDevice()),
            'viewport' => $this->getViewport($options->getDevice()),
            'locale' => $this->getLocaleFromLocation($options->getLocation()),
        ];

        // Add proxy configuration if available
        $proxy = config('serp-scraper.proxy');
        if ($proxy && !empty($proxy['server'])) {
            $contextOptions['proxy'] = [
                'server' => $proxy['server'],
            ];
            if (!empty($proxy['username']) && !empty($proxy['password'])) {
                $contextOptions['proxy']['username'] = $proxy['username'];
                $contextOptions['proxy']['password'] = $proxy['password'];
            }
        }

        return $browser->newContext($contextOptions);
    }

    /**
     * Get a random user agent for the given device type.
     *
     * @param string $device
     * @return string
     */
    protected function getUserAgent(string $device): string
    {
        $deviceType = in_array($device, ['desktop', 'mobile']) ? $device : 'desktop';
        $agents = self::USER_AGENTS[$deviceType];
        return $agents[array_rand($agents)];
    }

    /**
     * Get viewport dimensions for the given device type.
     *
     * @param string $device
     * @return array{width: int, height: int}
     */
    protected function getViewport(string $device): array
    {
        return match ($device) {
            'mobile' => ['width' => 375, 'height' => 667],
            'tablet' => ['width' => 768, 'height' => 1024],
            default => ['width' => 1280, 'height' => 720],
        };
    }

    /**
     * Get locale string from location code.
     *
     * @param string $location
     * @return string
     */
    protected function getLocaleFromLocation(string $location): string
    {
        $locales = [
            'US' => 'en-US',
            'UK' => 'en-GB',
            'DE' => 'de-DE',
            'FR' => 'fr-FR',
            'ES' => 'es-ES',
            'IT' => 'it-IT',
            'JP' => 'ja-JP',
            'AU' => 'en-AU',
            'CA' => 'en-CA',
            'BR' => 'pt-BR',
            'MX' => 'es-MX',
            'NL' => 'nl-NL',
            'PL' => 'pl-PL',
            'RU' => 'ru-RU',
            'IN' => 'en-IN',
        ];

        return $locales[$location] ?? 'en-US';
    }

    /**
     * Apply rate limiting between requests.
     *
     * @param string $searchEngine
     */
    protected function applyRateLimiting(string $searchEngine): void
    {
        $minDelay = config('serp-scraper.min_delay_ms', 2000);
        $maxDelay = config('serp-scraper.max_delay_ms', 5000);

        // Calculate delay with some randomness
        $delayMs = rand($minDelay, $maxDelay);

        // Add extra delay when switching search engines
        if (self::$lastSearchEngine !== null && self::$lastSearchEngine !== $searchEngine) {
            $delayMs += config('serp-scraper.engine_switch_delay_ms', 3000);
        }

        // Apply delay if we've made recent requests
        if (self::$lastRequestTime !== null) {
            $elapsed = (microtime(true) - self::$lastRequestTime) * 1000;
            $remainingDelay = $delayMs - $elapsed;

            if ($remainingDelay > 0) {
                usleep((int) $remainingDelay * 1000);
            }
        }
    }

    /**
     * Detect if a CAPTCHA challenge is present on the page.
     *
     * @param PageInterface $page
     * @return bool
     */
    protected function detectCaptcha(PageInterface $page): bool
    {
        try {
            // Check for CAPTCHA selectors
            foreach (self::CAPTCHA_SELECTORS as $selector) {
                try {
                    $element = $page->evaluate("
                        () => {
                            const el = document.querySelector('{$selector}');
                            return el !== null;
                        }
                    ");
                    if ($element === true) {
                        $this->logger->warning('CAPTCHA detected via selector', ['selector' => $selector]);
                        return true;
                    }
                } catch (\Exception $e) {
                    // Selector not found, continue
                }
            }

            // Check for CAPTCHA patterns in page content
            $content = strtolower($page->content());
            foreach (self::CAPTCHA_PATTERNS as $pattern) {
                if (str_contains($content, $pattern)) {
                    $this->logger->warning('CAPTCHA detected via content pattern', ['pattern' => $pattern]);
                    return true;
                }
            }

            // Check for unusual HTTP status codes that might indicate blocking
            $title = strtolower($page->title() ?? '');
            if (str_contains($title, 'access denied') || str_contains($title, 'blocked')) {
                $this->logger->warning('Potential blocking detected via title', ['title' => $title]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error during CAPTCHA detection', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Extract SERP results from the page.
     *
     * @param PageInterface $page
     * @param SERPOptions $options
     * @return array<int, SERPEntry>
     */
    protected function extractSERPResults(PageInterface $page, SERPOptions $options): array
    {
        $searchEngine = $options->getSearchEngine();
        $targetUrl = $options->getTargetUrl();

        // Use engine-specific extraction
        $extractionScript = match ($searchEngine) {
            'bing' => $this->getBingExtractionScript(),
            default => $this->getGoogleExtractionScript(),
        };

        $rawResults = $page->evaluate($extractionScript);

        if (!is_array($rawResults)) {
            return [];
        }

        $results = [];
        $position = 0;

        foreach ($rawResults as $result) {
            $position++;
            $url = $result['url'] ?? '';
            $title = $result['title'] ?? '';
            $description = $result['description'] ?? '';

            if (empty($url) || empty($title)) {
                continue;
            }

            $isOurSite = $targetUrl ? $this->isTargetMatch($targetUrl, $url) : false;

            $results[] = new SERPEntryData(
                $position,
                $url,
                $title,
                $description,
                $isOurSite
            );

            // Limit to top 100
            if (count($results) >= 100) {
                break;
            }
        }

        return $results;
    }

    /**
     * Get JavaScript extraction script for Google SERP.
     *
     * @return string
     */
    protected function getGoogleExtractionScript(): string
    {
        return <<<'JS'
            () => {
                const results = [];
                
                // Standard organic results
                const containers = document.querySelectorAll('#search .g, #rso .g, div[data-sokoban-container] .g');
                
                containers.forEach((container, index) => {
                    const link = container.querySelector('a[href^="http"]');
                    const titleEl = container.querySelector('h3');
                    const descEl = container.querySelector('[data-sncf], .VwiC3b, div[style*="-webkit-line-clamp"]');
                    
                    if (link && titleEl) {
                        const url = link.href;
                        const title = titleEl.textContent.trim();
                        const description = descEl ? descEl.textContent.trim() : '';
                        
                        // Filter out ads and non-organic results
                        if (!url.includes('googleadservices') && 
                            !url.includes('google.com/aclk') &&
                            !url.includes('google.com/pagead')) {
                            results.push({ url, title, description });
                        }
                    }
                });
                
                // Fallback to broader selectors if no results
                if (results.length === 0) {
                    document.querySelectorAll('a[href^="http"]:not([href*="google"])').forEach((link, index) => {
                        const titleEl = link.querySelector('h3, [role="heading"]');
                        const parent = link.closest('div');
                        const descEl = parent ? parent.querySelector('[data-sncf], .VwiC3b, span') : null;
                        
                        if (titleEl && !link.href.includes('googleadservices')) {
                            results.push({
                                url: link.href,
                                title: titleEl.textContent.trim(),
                                description: descEl ? descEl.textContent.trim() : ''
                            });
                        }
                    });
                }
                
                return results.slice(0, 100);
            }
        JS;
    }

    /**
     * Get JavaScript extraction script for Bing SERP.
     *
     * @return string
     */
    protected function getBingExtractionScript(): string
    {
        return <<<'JS'
            () => {
                const results = [];
                
                // Bing organic results
                const containers = document.querySelectorAll('#b_results .b_algo');
                
                containers.forEach((container, index) => {
                    const link = container.querySelector('a[href^="http"]');
                    const titleEl = container.querySelector('h2');
                    const descEl = container.querySelector('.b_caption p, .b_algoSlug');
                    
                    if (link && titleEl) {
                        results.push({
                            url: link.href,
                            title: titleEl.textContent.trim(),
                            description: descEl ? descEl.textContent.trim() : ''
                        });
                    }
                });
                
                return results.slice(0, 100);
            }
        JS;
    }

    /**
     * Check if target URL matches result URL.
     *
     * @param string|null $targetUrl
     * @param string $resultUrl
     * @return bool
     */
    protected function isTargetMatch(?string $targetUrl, string $resultUrl): bool
    {
        if (!$targetUrl) {
            return false;
        }

        if (strcasecmp($targetUrl, $resultUrl) === 0) {
            return true;
        }

        $targetHost = parse_url($targetUrl, PHP_URL_HOST);
        $resultHost = parse_url($resultUrl, PHP_URL_HOST);

        if (!$targetHost || !$resultHost) {
            return false;
        }

        return strcasecmp($targetHost, $resultHost) === 0;
    }

    /**
     * Build error result for CAPTCHA detection.
     *
     * @param string $keyword
     * @param SERPOptions $options
     * @param float $start
     * @return SERPResult
     */
    protected function buildCaptchaErrorResult(string $keyword, SERPOptions $options, float $start): SERPResult
    {
        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $checkedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM);

        $this->logger->warning('CAPTCHA challenge detected, aborting scrape', [
            'keyword' => $keyword,
            'search_engine' => $options->getSearchEngine(),
            'duration_ms' => $durationMs,
        ]);

        return new SERPResultData(
            $keyword,
            $options->getSearchEngine(),
            $options->getLocation(),
            $options->getDevice(),
            [],
            $checkedAt,
            'CAPTCHA detected - unable to scrape results. Consider using a proxy or reducing request frequency.',
            $durationMs,
            null
        );
    }

    /**
     * Get the current request count (for monitoring).
     *
     * @return int
     */
    public function getRequestCount(): int
    {
        return self::$requestCount;
    }

    /**
     * Reset rate limiting state.
     */
    public static function resetRateLimiting(): void
    {
        self::$lastRequestTime = null;
        self::$requestCount = 0;
        self::$lastSearchEngine = null;
    }
}
