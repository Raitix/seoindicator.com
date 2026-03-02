<?php

namespace App\Services\Crawler;

use App\Models\CrawlJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Depth Controller Service for L0/L1/L2 Crawling.
 *
 * Manages multi-level crawling with link discovery, filtering,
 * and queue management.
 *
 * @see TASK-22-8
 * @see SPEC-022
 */
class DepthControllerService
{
    /**
     * Default file extensions to skip.
     */
    protected const DEFAULT_SKIP_EXTENSIONS = [
        'pdf', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'ico',
        'mp4', 'webm', 'avi', 'mov', 'wmv', 'flv', 'mkv',
        'mp3', 'wav', 'ogg', 'flac', 'aac',
        'zip', 'rar', '7z', 'tar', 'gz', 'bz2',
        'exe', 'msi', 'dmg', 'deb', 'rpm',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'apk', 'ipa',
    ];

    /**
     * Default paths to skip.
     */
    protected const DEFAULT_SKIP_PATHS = [
        '/api/*',
        '/admin/*',
        '/login',
        '/logout',
        '/signin',
        '/signup',
        '/register',
    ];

    /**
     * Protocols to skip.
     */
    protected const SKIP_PROTOCOLS = [
        'mailto',
        'javascript',
        'tel',
        'ftp',
        'file',
        'data',
    ];

    /**
     * The URL validator service.
     */
    protected ?UrlValidatorService $urlValidator;

    /**
     * The URL normalizer.
     */
    protected UrlNormalizer $urlNormalizer;

    /**
     * In-memory crawl queue for unit testing.
     */
    protected array $crawlQueue = [];

    /**
     * In-memory crawled URLs for unit testing.
     */
    protected array $crawledUrls = [];

    /**
     * Create a new DepthControllerService instance.
     */
    public function __construct(?UrlValidatorService $urlValidator = null)
    {
        $this->urlValidator = $urlValidator;
        $this->urlNormalizer = new UrlNormalizer();
    }

    // =========================================================================
    // Service Accessors (for testing)
    // =========================================================================

    /**
     * Get the CrawlJob model.
     */
    public function getCrawlJob(): ?CrawlJob
    {
        return app(CrawlJob::class);
    }

    /**
     * Get the logger.
     */
    public function getLogger()
    {
        return Log::getLogger();
    }

    /**
     * Get the URL validator.
     */
    public function getUrlValidator(): ?UrlValidatorService
    {
        return $this->urlValidator;
    }

    /**
     * Get default max depth from config.
     */
    public function getDefaultMaxDepth(): int
    {
        return config('crawler.max_depth', 2);
    }

    /**
     * Get default max pages from config.
     */
    public function getDefaultMaxPages(): int
    {
        return config('crawler.max_pages', 50);
    }

    // =========================================================================
    // Core Methods
    // =========================================================================

    /**
     * Check if a URL should be crawled.
     *
     * @param string|null $url The URL to check
     * @param int $currentDepth Current depth
     * @param int $maxDepth Maximum allowed depth
     * @param string|null $jobId Optional job ID for duplicate detection
     * @return bool True if URL should be crawled
     */
    public function shouldCrawl(?string $url, int $currentDepth, int $maxDepth, ?string $jobId = null): bool
    {
        // Validate inputs
        if (empty($url)) {
            return false;
        }

        if ($currentDepth < 0) {
            return false;
        }

        // Check depth limit
        if ($currentDepth > $maxDepth) {
            return false;
        }

        // Normalize URL for checks
        $normalizedUrl = $this->normalizeUrl($url);

        // Check if already crawled
        if ($jobId && $this->isAlreadyCrawled($normalizedUrl, $jobId)) {
            return false;
        }

        // Check if in queue
        if ($jobId && $this->isInQueue($normalizedUrl, $jobId)) {
            return false;
        }

        // Validate URL (SSRF protection)
        if ($this->urlValidator) {
            $validation = $this->urlValidator->validate($normalizedUrl);
            if (!($validation['valid'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract links from HTML content.
     *
     * @param string|null $html The HTML content
     * @param string $baseUrl The base URL for resolving relative URLs
     * @return array Array of extracted links with url, text, rel, target, nofollow
     */
    public function extractLinks(?string $html, string $baseUrl): array
    {
        if (empty($html)) {
            return [];
        }

        $links = [];
        $baseParsed = parse_url($baseUrl);

        try {
            $crawler = new Crawler($html);

            // Get all anchor tags (excluding those in script and comments)
            $crawler->filter('a[href]')->each(function (Crawler $node) use ($baseUrl, $baseParsed, &$links) {
                $href = trim($node->attr('href') ?? '');

                if (empty($href)) {
                    return;
                }

                // Skip special protocols
                if ($this->shouldSkipProtocol($href)) {
                    return;
                }

                // Skip hash-only links (same-page fragments)
                if (str_starts_with($href, '#')) {
                    return;
                }

                // Resolve URL
                $resolvedUrl = $this->resolveUrl($href, $baseUrl, $baseParsed);

                if (empty($resolvedUrl)) {
                    return;
                }

                // Extract attributes
                $link = [
                    'url' => $resolvedUrl,
                    'text' => trim($node->text()),
                    'rel' => $node->attr('rel'),
                    'target' => $node->attr('target'),
                    'nofollow' => $this->hasRelValue($node->attr('rel'), 'nofollow'),
                    'sponsored' => $this->hasRelValue($node->attr('rel'), 'sponsored'),
                    'ugc' => $this->hasRelValue($node->attr('rel'), 'ugc'),
                ];

                $links[] = $link;
            });
        } catch (\Exception $e) {
            Log::debug('DepthController: Error extracting links', [
                'error' => $e->getMessage(),
            ]);
        }

        return $links;
    }

    /**
     * Filter links based on rules.
     *
     * @param array|null $links Array of links to filter
     * @param array $options Filter options (baseUrl, followExternalLinks, etc.)
     * @return array Filtered links
     */
    public function filterLinks(?array $links, array $options = []): array
    {
        if (empty($links)) {
            return [];
        }

        $baseUrl = $options['baseUrl'] ?? '';
        $followExternalLinks = $options['followExternalLinks'] ?? false;
        $skipSelfReferences = $options['skipSelfReferences'] ?? false;

        $filtered = [];
        $seenUrls = [];

        foreach ($links as $link) {
            $url = $link['url'] ?? null;

            if (empty($url)) {
                continue;
            }

            // Normalize URL for comparison
            $normalizedUrl = $this->normalizeUrl($url);

            // Skip duplicates
            if (isset($seenUrls[$normalizedUrl])) {
                continue;
            }

            // Skip self-references
            if ($skipSelfReferences && $baseUrl && $normalizedUrl === $this->normalizeUrl($baseUrl)) {
                continue;
            }

            // Check file extensions
            if ($this->shouldSkipExtension($url)) {
                continue;
            }

            // Check skip paths
            if ($this->shouldSkipPath($url)) {
                continue;
            }

            // Check SSRF via URL validator
            if ($this->urlValidator) {
                $validation = $this->urlValidator->validate($normalizedUrl);
                if (!($validation['valid'] ?? false)) {
                    continue;
                }
            }

            // Check external domains
            if (!$followExternalLinks && $baseUrl) {
                if (!$this->isSameDomain($normalizedUrl, $baseUrl)) {
                    continue;
                }
            }

            // Mark as seen
            $seenUrls[$normalizedUrl] = true;

            // Add normalized URL to link
            $link['normalizedUrl'] = $normalizedUrl;
            $filtered[] = $link;
        }

        return $filtered;
    }

    /**
     * Queue links for crawling.
     *
     * @param array $links Array of links to queue
     * @param string $jobId The job identifier
     * @param int $depth The depth for these links
     * @param string|null $parentUrl The parent URL
     */
    public function queueLinks(array $links, string $jobId, int $depth, ?string $parentUrl = null): void
    {
        if (empty($links)) {
            return;
        }

        foreach ($links as $link) {
            $url = $link['url'] ?? $link['normalizedUrl'] ?? null;

            if (empty($url)) {
                continue;
            }

            $normalizedUrl = $this->normalizeUrl($url);

            // Skip if already queued or crawled
            if ($this->isInQueue($normalizedUrl, $jobId) || $this->isAlreadyCrawled($normalizedUrl, $jobId)) {
                continue;
            }

            // Add to in-memory queue
            $this->crawlQueue[$jobId][] = [
                'url' => $normalizedUrl,
                'depth' => $depth,
                'status' => 'pending',
                'parent_url' => $parentUrl,
                'created_at' => now()->toIso8601String(),
            ];

            Log::debug('DepthController: Link queued', [
                'jobId' => $jobId,
                'url' => $normalizedUrl,
                'depth' => $depth,
            ]);
        }
    }

    /**
     * Get the crawl queue for a job.
     *
     * @param string $jobId The job identifier
     * @param string|null $status Filter by status
     * @return array The crawl queue
     */
    public function getCrawlQueue(string $jobId, ?string $status = null): array
    {
        if (!isset($this->crawlQueue[$jobId])) {
            return [];
        }

        $queue = $this->crawlQueue[$jobId];

        // Filter by status
        if ($status) {
            $queue = array_filter($queue, fn ($item) => ($item['status'] ?? null) === $status);
        }

        // Sort by depth
        usort($queue, fn ($a, $b) => ($a['depth'] ?? 0) <=> ($b['depth'] ?? 0));

        return array_values($queue);
    }

    /**
     * Mark a URL as crawled.
     *
     * @param string $url The URL
     * @param string $jobId The job identifier
     */
    public function markCrawled(string $url, string $jobId): void
    {
        $normalizedUrl = $this->normalizeUrl($url);

        if (!isset($this->crawledUrls[$jobId])) {
            $this->crawledUrls[$jobId] = [];
        }

        $this->crawledUrls[$jobId][$normalizedUrl] = true;

        // Update queue status if present
        if (isset($this->crawlQueue[$jobId])) {
            foreach ($this->crawlQueue[$jobId] as &$item) {
                if ($this->normalizeUrl($item['url']) === $normalizedUrl) {
                    $item['status'] = 'done';
                }
            }
        }
    }

    /**
     * Normalize a URL.
     *
     * @param string $url The URL to normalize
     * @return string The normalized URL
     */
    public function normalizeUrl(string $url): string
    {
        return $this->urlNormalizer->normalize($url);
    }

    // =========================================================================
    // Protected Helper Methods
    // =========================================================================

    /**
     * Check if a URL protocol should be skipped.
     */
    protected function shouldSkipProtocol(string $href): bool
    {
        $href = strtolower(trim($href));

        foreach (self::SKIP_PROTOCOLS as $protocol) {
            if (str_starts_with($href, $protocol . ':')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve a URL relative to a base URL.
     */
    protected function resolveUrl(string $href, string $baseUrl, array $baseParsed): string
    {
        $href = trim($href);

        // Handle protocol-relative URLs
        if (str_starts_with($href, '//')) {
            $scheme = $baseParsed['scheme'] ?? 'https';
            return $scheme . ':' . $href;
        }

        // Handle absolute URLs
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        // Handle root-relative URLs
        if (str_starts_with($href, '/')) {
            $scheme = $baseParsed['scheme'] ?? 'https';
            $host = $baseParsed['host'] ?? '';
            $port = isset($baseParsed['port']) ? ':' . $baseParsed['port'] : '';

            return $scheme . '://' . $host . $port . $href;
        }

        // Handle relative URLs
        $basePath = $baseParsed['path'] ?? '/';
        $basePath = dirname($basePath);

        // Ensure base path ends with /
        if (!str_ends_with($basePath, '/')) {
            $basePath .= '/';
        }

        // Resolve parent directory references
        while (str_starts_with($href, '../')) {
            $basePath = dirname($basePath);
            if (!str_ends_with($basePath, '/')) {
                $basePath .= '/';
            }
            $href = substr($href, 3);
        }

        // Handle current directory references
        if (str_starts_with($href, './')) {
            $href = substr($href, 2);
        }

        $scheme = $baseParsed['scheme'] ?? 'https';
        $host = $baseParsed['host'] ?? '';
        $port = isset($baseParsed['port']) ? ':' . $baseParsed['port'] : '';

        return $scheme . '://' . $host . $port . $basePath . $href;
    }

    /**
     * Check if a rel attribute contains a specific value.
     */
    protected function hasRelValue(?string $rel, string $value): bool
    {
        if (empty($rel)) {
            return false;
        }

        $values = preg_split('/\s+/', strtolower($rel));
        return in_array(strtolower($value), $values);
    }

    /**
     * Check if a URL should be skipped based on file extension.
     */
    protected function shouldSkipExtension(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        // Remove query string for extension check
        $path = strtok($path, '?');

        if (empty($path)) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, self::DEFAULT_SKIP_EXTENSIONS);
    }

    /**
     * Check if a URL should be skipped based on path.
     */
    protected function shouldSkipPath(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if (empty($path)) {
            return false;
        }

        // Normalize path
        $path = strtolower(rtrim($path, '/'));

        foreach (self::DEFAULT_SKIP_PATHS as $skipPath) {
            $skipPath = strtolower(rtrim($skipPath, '*'));

            // Wildcard match
            if (str_ends_with($skipPath, '*')) {
                $prefix = substr($skipPath, 0, -1);
                if (str_starts_with($path, $prefix)) {
                    return true;
                }
            } else {
                // Exact match
                if ($path === $skipPath) {
                    return true;
                }
            }
        }

        // Check for query parameters
        $query = parse_url($url, PHP_URL_QUERY) ?? '';
        if (!empty($query)) {
            parse_str($query, $params);

            // Skip print versions
            if (isset($params['print']) && $params['print'] == '1') {
                return true;
            }

            // Skip PDF versions
            if (isset($params['format']) && strtolower($params['format']) === 'pdf') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if two URLs have the same domain.
     */
    protected function isSameDomain(string $url1, string $url2): bool
    {
        $host1 = strtolower(parse_url($url1, PHP_URL_HOST) ?? '');
        $host2 = strtolower(parse_url($url2, PHP_URL_HOST) ?? '');

        return $host1 === $host2;
    }

    /**
     * Check if a URL is already in the queue.
     */
    protected function isInQueue(string $normalizedUrl, string $jobId): bool
    {
        if (!isset($this->crawlQueue[$jobId])) {
            return false;
        }

        foreach ($this->crawlQueue[$jobId] as $item) {
            if ($this->normalizeUrl($item['url']) === $normalizedUrl) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a URL has already been crawled.
     */
    protected function isAlreadyCrawled(string $normalizedUrl, string $jobId): bool
    {
        return isset($this->crawledUrls[$jobId][$normalizedUrl]);
    }
}
