<?php

namespace App\Services\Audit;

use App\Models\Audit;
use App\Services\Audit\Contracts\SiteCrawlerInterface;
use App\Services\Audit\DTOs\CrawlQueueItem;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\CrawlState;
use Generator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * SiteCrawler Service for SEO Audits.
 *
 * Orchestrates website crawling with robots.txt compliance,
 * rate limiting, and URL queue management.
 *
 * @see TASK-14-5
 */
class SiteCrawler implements SiteCrawlerInterface
{
    protected ?CrawlState $state = null;
    protected array $queue = [];
    protected array $crawledUrls = [];
    protected string $baseUrl = '';
    protected string $baseDomain = '';
    protected array $config = [];
    protected array $rateLimits = [];
    protected ?array $robotsRules = null;

    /**
     * Create a new SiteCrawler instance.
     */
    public function __construct(
        protected ?RobotsParser $robotsParser = null,
    ) {
        $this->config = [
            'max_depth' => config('audit.crawl.max_depth', 3),
            'max_pages' => config('audit.crawl.max_pages', 500),
            'requests_per_second' => config('audit.crawl.requests_per_second', 2),
            'max_concurrent' => config('audit.crawl.max_concurrent', 5),
            'timeout' => config('audit.crawl.timeout', 30),
            'user_agent' => config('audit.crawl.user_agent', 'SEOIndicator-Bot/1.0'),
            'max_url_length' => config('audit.crawl.max_url_length', 2048),
            'max_html_size' => config('audit.crawl.max_html_size', 104857600),
            'max_redirects' => config('audit.crawl.max_redirects', 5),
            'max_duration' => config('audit.crawl.max_duration', 3600),
            'respect_robots_txt' => config('audit.crawl.respect_robots_txt', true),
            'robots_cache_ttl' => config('audit.crawl.robots_cache_ttl', 3600),
            'ssrf_enabled' => config('audit.ssrf.enabled', true),
            'blocked_ranges' => config('audit.ssrf.blocked_ranges', []),
            'blocked_hosts' => config('audit.ssrf.blocked_hosts', []),
        ];
    }

    /**
     * Set custom configuration.
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function startCrawl(int $auditId, string $baseUrl): void
    {
        // Validate URL
        if (!$this->isValidUrl($baseUrl)) {
            throw new \InvalidArgumentException("Invalid URL: {$baseUrl}");
        }

        // Check SSRF protection
        if ($this->config['ssrf_enabled'] && $this->isSsrfBlocked($baseUrl)) {
            throw new \InvalidArgumentException("URL blocked by SSRF protection: {$baseUrl}");
        }

        $this->baseUrl = $this->normalizeUrl($baseUrl);
        $this->baseDomain = parse_url($this->baseUrl, PHP_URL_HOST);
        $this->state = CrawlState::pending($auditId, $this->baseUrl, $this->config);
        $this->queue = [];
        $this->crawledUrls = [];
        $this->robotsRules = null;
        $this->rateLimits = [];

        // Load existing state from cache if resuming
        $cachedState = $this->loadState($auditId);
        if ($cachedState) {
            $this->state = $cachedState;
            $this->queue = $this->loadQueue($auditId);
            $this->crawledUrls = $this->loadCrawledUrls($auditId);
        }

        // Enqueue the homepage
        $this->enqueueUrl($this->baseUrl, 0);

        Log::info('SiteCrawler: Crawl started', [
            'auditId' => $auditId,
            'baseUrl' => $this->baseUrl,
            'config' => $this->config,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function enqueueUrl(string $url, int $depth = 0): void
    {
        if (!$this->state) {
            throw new RuntimeException('No active crawl. Call startCrawl() first.');
        }

        // Normalize URL
        $url = $this->normalizeUrl($url);

        // Skip if URL is too long
        if (strlen($url) > $this->config['max_url_length']) {
            Log::debug('SiteCrawler: URL too long, skipping', ['url' => $url]);
            return;
        }

        // Skip if already crawled
        if ($this->hasCrawled($url)) {
            return;
        }

        // Skip if depth exceeds max
        if ($depth > $this->config['max_depth']) {
            return;
        }

        // Skip if max pages reached
        if (count($this->crawledUrls) >= $this->config['max_pages']) {
            return;
        }

        // Skip non-HTTP URLs
        if (!$this->isHttpUrl($url)) {
            return;
        }

        // Skip JavaScript, data, and other special URLs
        if ($this->isSpecialUrl($url)) {
            return;
        }

        // Check robots.txt if enabled
        if ($this->config['respect_robots_txt'] && !$this->shouldCrawl($url)) {
            Log::debug('SiteCrawler: URL blocked by robots.txt', ['url' => $url]);
            return;
        }

        // Check SSRF protection
        if ($this->config['ssrf_enabled'] && $this->isSsrfBlocked($url)) {
            Log::debug('SiteCrawler: URL blocked by SSRF protection', ['url' => $url]);
            return;
        }

        // Only enqueue internal URLs (same domain)
        if (!$this->isInternalUrl($url)) {
            return;
        }

        // Create queue item
        $item = CrawlQueueItem::pending($url, $depth, $this->calculatePriority($url, $depth));

        // Add to queue if not already queued
        $queueKey = $this->getQueueKey($url);
        if (!isset($this->queue[$queueKey])) {
            $this->queue[$queueKey] = $item;
            Log::debug('SiteCrawler: URL enqueued', ['url' => $url, 'depth' => $depth]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processQueue(): Generator
    {
        if (!$this->state) {
            throw new RuntimeException('No active crawl. Call startCrawl() first.');
        }

        $this->state->markAsRunning();
        $this->saveState();

        $startTime = time();
        $processedCount = 0;

        while (!empty($this->queue) && !$this->state->isFinished()) {
            // Check duration limit
            if (time() - $startTime > $this->config['max_duration']) {
                Log::warning('SiteCrawler: Max duration reached', ['duration' => time() - $startTime]);
                break;
            }

            // Check max pages limit
            if ($processedCount >= $this->config['max_pages']) {
                Log::info('SiteCrawler: Max pages reached', ['pages' => $processedCount]);
                break;
            }

            // Get next URL from queue (priority order)
            $item = $this->getNextFromQueue();
            if (!$item) {
                break;
            }

            // Mark as processing
            $item->markAsProcessing();
            $queueKey = $this->getQueueKey($item->url);

            // Apply rate limiting
            $this->waitForRateLimit($item->url);

            // Fetch the page
            try {
                $result = $this->fetchPage($item->url);
                $result = new CrawlResult(
                    url: $result->url,
                    statusCode: $result->statusCode,
                    responseTimeMs: $result->responseTimeMs,
                    html: $result->html,
                    links: $result->links,
                    metadata: $result->metadata,
                    errorMessage: $result->errorMessage,
                    headers: $result->headers,
                    depth: $item->depth,
                    redirectChain: $result->redirectChain,
                );

                // Mark as done
                $item->markAsDone();
                $this->crawledUrls[$queueKey] = true;
                $this->state->incrementPagesCrawled();
                $processedCount++;

                // Enqueue found links
                if ($result->isSuccess() && $result->html) {
                    $this->enqueueFoundLinks($result, $item->depth);
                }

                // Update rate limit tracking
                $this->updateRateLimit($item->url, true);

                yield $result;

            } catch (\Exception $e) {
                $item->markAsFailed($e->getMessage());
                $this->state->incrementPagesFailed($e->getMessage());
                $this->updateRateLimit($item->url, false);

                Log::error('SiteCrawler: Failed to fetch URL', [
                    'url' => $item->url,
                    'error' => $e->getMessage(),
                ]);

                yield CrawlResult::failure($item->url, $e->getMessage, 0, 0, $item->depth);
            }

            // Save state periodically
            if ($processedCount % 10 === 0) {
                $this->saveState();
                $this->saveQueue();
                $this->saveCrawledUrls();
            }
        }

        // Mark as completed
        $this->state->markAsCompleted();
        $this->saveState();

        Log::info('SiteCrawler: Crawl completed', [
            'auditId' => $this->state->auditId,
            'pagesCrawled' => $this->state->pagesCrawled,
            'pagesFailed' => $this->state->pagesFailed,
            'duration' => $this->state->getDuration(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchPage(string $url): CrawlResult
    {
        $startTime = microtime(true);
        $url = $this->normalizeUrl($url);
        $redirectChain = [];

        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->config['user_agent'],
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ])
            ->timeout($this->config['timeout'])
            ->maxRedirects($this->config['max_redirects'])
            ->withOptions([
                'track_redirects' => true,
            ])
            ->get($url);

            $responseTime = (microtime(true) - $startTime) * 1000;

            // Track redirects
            $redirectHistory = $response->handlerStats()['redirect_history'] ?? [];
            foreach ($redirectHistory as $redirect) {
                $redirectChain[] = [
                    'url' => $redirect['url'] ?? null,
                    'status' => $redirect['status_code'] ?? null,
                ];
            }

            $statusCode = $response->status();
            $headers = $response->headers();

            // Handle rate limiting responses
            if ($statusCode === 429) {
                return CrawlResult::failure(
                    $url,
                    'Rate limited by server (429)',
                    $statusCode,
                    $responseTime,
                );
            }

            // Handle server errors
            if ($statusCode >= 500) {
                return CrawlResult::failure(
                    $url,
                    "Server error ({$statusCode})",
                    $statusCode,
                    $responseTime,
                );
            }

            // Check content size
            $content = $response->body();
            if (strlen($content) > $this->config['max_html_size']) {
                $content = substr($content, 0, $this->config['max_html_size']);
            }

            // Extract links and metadata
            $links = [];
            $metadata = [];

            if ($response->successful() && $content) {
                $links = $this->extractLinks($content, $url);
                $metadata = $this->extractMetadata($content);
            }

            return CrawlResult::success(
                url: $url,
                statusCode: $statusCode,
                responseTimeMs: $responseTime,
                html: $content,
                links: $links,
                metadata: $metadata,
                headers: $headers,
                redirectChain: $redirectChain,
            );

        } catch (ConnectionException $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            return CrawlResult::failure($url, "Connection error: {$e->getMessage()}", 0, $responseTime);

        } catch (\Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            return CrawlResult::failure($url, $e->getMessage(), 0, $responseTime);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function extractLinks(string $html, string $baseUrl): array
    {
        $internal = [];
        $external = [];

        // Parse HTML and extract links
        if (!preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return ['internal' => [], 'external' => []];
        }

        $baseDomain = parse_url($baseUrl, PHP_URL_HOST);

        foreach ($matches[1] as $href) {
            // Skip empty, javascript, mailto, tel, etc.
            if (empty($href) || preg_match('/^(javascript:|mailto:|tel:|ftp:|#)/i', $href)) {
                continue;
            }

            // Resolve relative URLs
            $absoluteUrl = $this->resolveUrl($href, $baseUrl);

            // Validate URL
            if (!$this->isValidUrl($absoluteUrl)) {
                continue;
            }

            // Normalize URL
            $normalizedUrl = $this->normalizeUrl($absoluteUrl);

            // Categorize as internal or external
            $urlDomain = parse_url($normalizedUrl, PHP_URL_HOST);

            if ($urlDomain === $baseDomain || wwwEquivalent($urlDomain, $baseDomain)) {
                if (!in_array($normalizedUrl, $internal)) {
                    $internal[] = $normalizedUrl;
                }
            } else {
                if (!in_array($normalizedUrl, $external)) {
                    $external[] = $normalizedUrl;
                }
            }
        }

        return ['internal' => $internal, 'external' => $external];
    }

    /**
     * {@inheritdoc}
     */
    public function shouldCrawl(string $url): bool
    {
        if (!$this->config['respect_robots_txt']) {
            return true;
        }

        if (!$this->robotsParser) {
            return true;
        }

        $url = $this->normalizeUrl($url);
        $domain = parse_url($url, PHP_URL_HOST);

        // Load robots.txt rules if not cached
        if ($this->robotsRules === null) {
            $this->robotsRules = $this->fetchRobotsRules($domain);
        }

        // Check if URL is allowed
        return $this->robotsParser->isAllowed($url, $this->robotsRules, $this->config['user_agent']);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelCrawl(): void
    {
        if ($this->state) {
            $this->state->markAsCancelled();
            $this->saveState();
            $this->clearQueue();

            Log::info('SiteCrawler: Crawl cancelled', [
                'auditId' => $this->state->auditId,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): ?CrawlState
    {
        return $this->state;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueSize(): int
    {
        return count(array_filter($this->queue, fn($item) => $item->isPending()));
    }

    /**
     * {@inheritdoc}
     */
    public function hasCrawled(string $url): bool
    {
        $url = $this->normalizeUrl($url);
        $key = $this->getQueueKey($url);
        return isset($this->crawledUrls[$key]);
    }

    // =========================================================================
    // Protected Helper Methods
    // =========================================================================

    /**
     * Normalize a URL for comparison and storage.
     */
    protected function normalizeUrl(string $url): string
    {
        // Parse URL
        $parsed = parse_url($url);
        if (!$parsed) {
            return $url;
        }

        // Lowercase scheme and host
        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host'] ?? '');

        // Normalize path (remove duplicate slashes)
        $path = $parsed['path'] ?? '/';
        $path = preg_replace('#/+#', '/', $path);

        // Handle query parameters
        $query = '';
        if (!empty($parsed['query'])) {
            // Parse and sort query parameters
            parse_str($parsed['query'], $params);

            // Strip tracking parameters if configured
            if ($this->config['strip_tracking_params'] ?? true) {
                $trackingParams = config('audit.normalization.tracking_params', []);
                $params = array_diff_key($params, array_flip($trackingParams));
            }

            // Sort if configured
            if ($this->config['sort_query_params'] ?? true) {
                ksort($params);
            }

            $query = http_build_query($params);
        }

        // Strip fragment if configured
        $fragment = '';
        if (!($this->config['strip_fragments'] ?? true) && !empty($parsed['fragment'])) {
            $fragment = '#' . $parsed['fragment'];
        }

        // Build normalized URL
        $normalized = "{$scheme}://{$host}{$path}";
        if ($query) {
            $normalized .= "?{$query}";
        }
        $normalized .= $fragment;

        return $normalized;
    }

    /**
     * Check if URL is valid.
     */
    protected function isValidUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if URL is an HTTP URL.
     */
    protected function isHttpUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array(strtolower($scheme ?? ''), ['http', 'https']);
    }

    /**
     * Check if URL is a special URL (javascript, data, etc).
     */
    protected function isSpecialUrl(string $url): bool
    {
        $url = strtolower(trim($url));
        return preg_match('/^(javascript:|data:|vbscript:|file:|about:)/i', $url);
    }

    /**
     * Check if URL is internal to the base domain.
     */
    protected function isInternalUrl(string $url): bool
    {
        $urlDomain = parse_url($url, PHP_URL_HOST);
        return $urlDomain === $this->baseDomain || wwwEquivalent($urlDomain, $this->baseDomain);
    }

    /**
     * Check if URL is blocked by SSRF protection.
     */
    protected function isSsrfBlocked(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        // Check blocked hosts
        foreach ($this->config['blocked_hosts'] as $blockedHost) {
            if (strtolower($host) === strtolower($blockedHost)) {
                return true;
            }
        }

        // Resolve hostname to IP
        $ip = gethostbyname($host);

        // Check if IP is in blocked ranges
        foreach ($this->config['blocked_ranges'] as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        // Check for localhost patterns
        if (preg_match('/^(localhost|127\.|0\.0\.0\.0|::1|fc00:|fe80:)/i', $ip)) {
            return true;
        }

        return false;
    }

    /**
     * Check if an IP is in a CIDR range.
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $bits) = explode('/', $range);

        // Convert to long
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        // Calculate mask
        $mask = -1 << (32 - (int)$bits);
        $mask = $mask & 0xFFFFFFFF;

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    /**
     * Get the next URL from the queue (priority order).
     */
    protected function getNextFromQueue(): ?CrawlQueueItem
    {
        if (empty($this->queue)) {
            return null;
        }

        // Sort by priority (lower sort priority = higher priority)
        uasort($this->queue, function (CrawlQueueItem $a, CrawlQueueItem $b) {
            return $a->getSortPriority() <=> $b->getSortPriority();
        });

        // Get first pending item
        foreach ($this->queue as $key => $item) {
            if ($item->isPending()) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Calculate priority for a URL.
     */
    protected function calculatePriority(string $url, int $depth): int
    {
        $priority = 0;

        // Homepage gets highest priority
        if ($url === $this->baseUrl) {
            return 1000;
        }

        // Priority based on URL patterns
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        // Sitemap pages get higher priority
        if (strpos($path, 'sitemap') !== false) {
            $priority += 100;
        }

        // Lower depth = higher priority
        $priority -= $depth * 10;

        return $priority;
    }

    /**
     * Get queue key for a URL.
     */
    protected function getQueueKey(string $url): string
    {
        return md5($this->normalizeUrl($url));
    }

    /**
     * Resolve a relative URL to absolute.
     */
    protected function resolveUrl(string $url, string $baseUrl): string
    {
        // Already absolute
        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        // Protocol-relative
        if (strpos($url, '//') === 0) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
            return $scheme . ':' . $url;
        }

        // Absolute path
        if (strpos($url, '/') === 0) {
            $parsed = parse_url($baseUrl);
            return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $url;
        }

        // Relative path
        $parsed = parse_url($baseUrl);
        $path = $parsed['path'] ?? '/';
        $path = dirname($path);

        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $path . '/' . $url;
    }

    /**
     * Extract metadata from HTML.
     */
    protected function extractMetadata(string $html): array
    {
        $metadata = [];

        // Title
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $match)) {
            $metadata['title'] = trim($match[1]);
        }

        // Meta description
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $match)) {
            $metadata['description'] = trim($match[1]);
        }

        // Canonical
        if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $match)) {
            $metadata['canonical'] = trim($match[1]);
        }

        // H1 tags
        if (preg_match_all('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $matches)) {
            $metadata['h1'] = array_map('trim', $matches[1]);
        }

        // Meta robots
        if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $match)) {
            $metadata['robots'] = trim($match[1]);
        }

        // OG title
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $match)) {
            $metadata['og_title'] = trim($match[1]);
        }

        // OG description
        if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $match)) {
            $metadata['og_description'] = trim($match[1]);
        }

        return $metadata;
    }

    /**
     * Fetch robots.txt rules for a domain.
     */
    protected function fetchRobotsRules(string $domain): array
    {
        $cacheKey = "robots:{$domain}";

        return Cache::remember($cacheKey, $this->config['robots_cache_ttl'], function () use ($domain) {
            try {
                $robotsUrl = "https://{$domain}/robots.txt";
                $response = Http::timeout(10)->get($robotsUrl);

                if ($response->successful()) {
                    return $this->parseRobotsTxt($response->body());
                }
            } catch (\Exception $e) {
                Log::debug('SiteCrawler: Failed to fetch robots.txt', [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ]);
            }

            return [];
        });
    }

    /**
     * Parse robots.txt content.
     */
    protected function parseRobotsTxt(string $content): array
    {
        $rules = [
            'user_agents' => [],
            'sitemaps' => [],
        ];

        $lines = explode("\n", $content);
        $currentUserAgent = '*';

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Parse directive
            if (preg_match('/^([^:]+):\s*(.+)$/i', $line, $match)) {
                $directive = strtolower(trim($match[1]));
                $value = trim($match[2]);

                switch ($directive) {
                    case 'user-agent':
                        $currentUserAgent = $value;
                        if (!isset($rules['user_agents'][$currentUserAgent])) {
                            $rules['user_agents'][$currentUserAgent] = [
                                'disallow' => [],
                                'allow' => [],
                                'crawl-delay' => null,
                            ];
                        }
                        break;

                    case 'disallow':
                        $rules['user_agents'][$currentUserAgent]['disallow'][] = $value;
                        break;

                    case 'allow':
                        $rules['user_agents'][$currentUserAgent]['allow'][] = $value;
                        break;

                    case 'crawl-delay':
                        $rules['user_agents'][$currentUserAgent]['crawl-delay'] = (float)$value;
                        break;

                    case 'sitemap':
                        $rules['sitemaps'][] = $value;
                        break;
                }
            }
        }

        return $rules;
    }

    /**
     * Wait for rate limit.
     */
    protected function waitForRateLimit(string $url): void
    {
        $domain = parse_url($url, PHP_URL_HOST);

        if (!isset($this->rateLimits[$domain])) {
            $this->rateLimits[$domain] = [
                'last_request' => 0,
                'backoff' => 0,
            ];
        }

        $now = microtime(true);
        $minInterval = 1.0 / $this->config['requests_per_second'];
        $elapsed = $now - $this->rateLimits[$domain]['last_request'];

        // Add backoff if rate limited
        $requiredDelay = $minInterval + $this->rateLimits[$domain]['backoff'];

        if ($elapsed < $requiredDelay) {
            usleep((int)(($requiredDelay - $elapsed) * 1000000));
        }

        $this->rateLimits[$domain]['last_request'] = microtime(true);
    }

    /**
     * Update rate limit tracking after a request.
     */
    protected function updateRateLimit(string $url, bool $success): void
    {
        $domain = parse_url($url, PHP_URL_HOST);

        if (!isset($this->rateLimits[$domain])) {
            $this->rateLimits[$domain] = ['backoff' => 0];
        }

        if ($success) {
            // Reset backoff on success
            $this->rateLimits[$domain]['backoff'] = 0;
        } else {
            // Exponential backoff on failure
            $currentBackoff = $this->rateLimits[$domain]['backoff'];
            $this->rateLimits[$domain]['backoff'] = min(60, max(1, $currentBackoff * 2));
        }
    }

    /**
     * Enqueue links found in a crawl result.
     */
    protected function enqueueFoundLinks(CrawlResult $result, int $currentDepth): void
    {
        $links = $result->getInternalLinks();
        $nextDepth = $currentDepth + 1;

        foreach ($links as $link) {
            $this->enqueueUrl($link, $nextDepth);
        }
    }

    // =========================================================================
    // State Persistence Methods
    // =========================================================================

    /**
     * Save crawl state to cache.
     */
    protected function saveState(): void
    {
        if (!$this->state) {
            return;
        }

        $key = "crawl_state:{$this->state->auditId}";
        Cache::put($key, $this->state->toArray(), now()->addHours(24));
    }

    /**
     * Load crawl state from cache.
     */
    protected function loadState(int $auditId): ?CrawlState
    {
        $key = "crawl_state:{$auditId}";
        $data = Cache::get($key);

        if ($data) {
            return CrawlState::fromArray($data);
        }

        return null;
    }

    /**
     * Save queue to cache.
     */
    protected function saveQueue(): void
    {
        if (!$this->state) {
            return;
        }

        $key = "crawl_queue:{$this->state->auditId}";
        $data = array_map(fn($item) => $item->toArray(), $this->queue);
        Cache::put($key, $data, now()->addHours(24));
    }

    /**
     * Load queue from cache.
     */
    protected function loadQueue(int $auditId): array
    {
        $key = "crawl_queue:{$auditId}";
        $data = Cache::get($key, []);

        return array_map(fn($item) => CrawlQueueItem::fromArray($item), $data);
    }

    /**
     * Save crawled URLs to cache.
     */
    protected function saveCrawledUrls(): void
    {
        if (!$this->state) {
            return;
        }

        $key = "crawl_urls:{$this->state->auditId}";
        Cache::put($key, $this->crawledUrls, now()->addHours(24));
    }

    /**
     * Load crawled URLs from cache.
     */
    protected function loadCrawledUrls(int $auditId): array
    {
        $key = "crawl_urls:{$auditId}";
        return Cache::get($key, []);
    }

    /**
     * Clear queue and state from cache.
     */
    protected function clearQueue(): void
    {
        if (!$this->state) {
            return;
        }

        $auditId = $this->state->auditId;
        Cache::forget("crawl_state:{$auditId}");
        Cache::forget("crawl_queue:{$auditId}");
        Cache::forget("crawl_urls:{$auditId}");

        $this->queue = [];
        $this->crawledUrls = [];
    }
}

/**
 * Helper function to check www equivalence.
 */
function wwwEquivalent(?string $domain1, ?string $domain2): bool
{
    if (!$domain1 || !$domain2) {
        return false;
    }

    $domain1 = strtolower($domain1);
    $domain2 = strtolower($domain2);

    // Remove www prefix
    $domain1 = preg_replace('/^www\./', '', $domain1);
    $domain2 = preg_replace('/^www\./', '', $domain2);

    return $domain1 === $domain2;
}
