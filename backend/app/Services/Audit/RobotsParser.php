<?php

namespace App\Services\Audit;

use App\Services\Audit\DTOs\ParsedRobots;
use App\Services\Audit\DTOs\RobotsRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Robots.txt Parser Service.
 *
 * Parses and evaluates robots.txt rules for crawl permission.
 * Implements SSRF protection and caching.
 *
 * @see TASK-14-6
 */
class RobotsParser
{
    /**
     * Default timeout for HTTP requests in seconds.
     */
    protected int $timeout;

    /**
     * Default cache TTL in seconds (1 hour).
     */
    protected int $cacheTtl;

    /**
     * Blocked IP ranges for SSRF prevention.
     */
    protected const BLOCKED_IP_RANGES = [
        // Loopback
        '127.0.0.0/8',
        '::1/128',
        // Private networks
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        // Link-local
        '169.254.0.0/16',
        // Any address (0.0.0.0)
        '0.0.0.0/8',
    ];

    /**
     * Blocked hostnames for SSRF prevention.
     */
    protected const BLOCKED_HOSTNAMES = [
        'localhost',
        'localhost.localdomain',
        'ip6-localhost',
        'ip6-loopback',
        'internal',
        'internal.local',
        'intranet',
        'metadata.google.internal',
        'metadata',
    ];

    /**
     * Create a new RobotsParser instance.
     */
    public function __construct()
    {
        $this->timeout = config('robots.timeout', 10);
        $this->cacheTtl = config('robots.cache_ttl', 3600);
    }

    /**
     * Fetch robots.txt content from a URL.
     *
     * @param string $baseUrl The base URL to fetch robots.txt from
     * @param bool $forceRefresh Force a cache refresh
     * @return string|null The robots.txt content or null on failure
     */
    public function fetch(string $baseUrl, bool $forceRefresh = false): ?string
    {
        // Validate URL
        if (empty($baseUrl) || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parsedUrl = parse_url($baseUrl);
        if ($parsedUrl === false) {
            return null;
        }

        // Only allow http and https schemes
        $scheme = $parsedUrl['scheme'] ?? '';
        if (!in_array($scheme, ['http', 'https'])) {
            return null;
        }

        // SSRF protection - check host
        $host = $parsedUrl['host'] ?? '';
        if ($this->isBlockedHost($host)) {
            return null;
        }

        // Build robots.txt URL
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $robotsUrl = sprintf(
            '%s://%s%s/robots.txt',
            $scheme,
            $host,
            $port
        );

        // Generate cache key
        $cacheKey = 'robots:' . md5($robotsUrl);

        // Force refresh - clear cache and bypass it
        if ($forceRefresh) {
            Cache::forget($cacheKey);
            // Make the request directly without caching
            return $this->makeRequest($robotsUrl);
        }

        // Try to get from cache
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($robotsUrl) {
            return $this->makeRequest($robotsUrl);
        });
    }

    /**
     * Make the actual HTTP request to fetch robots.txt.
     *
     * @param string $robotsUrl The full robots.txt URL
     * @return string|null The robots.txt content or null on failure
     */
    protected function makeRequest(string $robotsUrl): ?string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withUserAgent('SEOIndicator-Crawler/1.0')
                ->get($robotsUrl);

            if ($response->successful()) {
                return $response->body();
            }

            // 404 means no robots.txt - cache as null (allow all)
            if ($response->status() === 404) {
                return null;
            }

            return null;
        } catch (\Exception $e) {
            // Handle timeouts, connection errors, SSL errors, etc.
            return null;
        }
    }

    /**
     * Parse robots.txt content into a ParsedRobots DTO.
     *
     * @param string|null $robotsTxt The robots.txt content
     * @param string $userAgent The user agent to parse rules for
     * @return ParsedRobots The parsed robots.txt data
     */
    public function parse(?string $robotsTxt, string $userAgent = '*'): ParsedRobots
    {
        $parsed = new ParsedRobots();

        // Handle null or empty content
        if ($robotsTxt === null || trim($robotsTxt) === '') {
            $parsed->isEmpty = true;
            return $parsed;
        }

        // Handle UTF-8 BOM
        if (str_starts_with($robotsTxt, "\xEF\xBB\xBF")) {
            $robotsTxt = substr($robotsTxt, 3);
        }

        // Normalize line endings
        $robotsTxt = str_replace(["\r\n", "\r"], "\n", $robotsTxt);

        $lines = explode("\n", $robotsTxt);
        $currentUserAgents = [];
        $inGroup = false;

        foreach ($lines as $line) {
            // Remove comments
            $line = preg_replace('/\s*#.*/', '', $line);
            $line = trim($line);

            // Skip empty lines
            if ($line === '') {
                continue;
            }

            // Parse directive
            if (!str_contains($line, ':')) {
                continue;
            }

            [$directive, $value] = array_map('trim', explode(':', $line, 2));
            $directive = strtolower($directive);

            switch ($directive) {
                case 'user-agent':
                    // Start a new user-agent group
                    if ($inGroup && !empty($currentUserAgents)) {
                        // Keep the current rules, start new group
                    }
                    $currentUserAgents[] = $value;
                    $inGroup = true;
                    break;

                case 'disallow':
                    if (empty($currentUserAgents)) {
                        $currentUserAgents = ['*'];
                    }
                    foreach ($currentUserAgents as $ua) {
                        $parsed->userAgents[] = $ua;
                        $parsed->rules[] = new RobotsRule($ua, $value, false);
                    }
                    break;

                case 'allow':
                    if (empty($currentUserAgents)) {
                        $currentUserAgents = ['*'];
                    }
                    foreach ($currentUserAgents as $ua) {
                        $parsed->userAgents[] = $ua;
                        $parsed->rules[] = new RobotsRule($ua, $value, true);
                    }
                    break;

                case 'crawl-delay':
                    if (empty($currentUserAgents)) {
                        $currentUserAgents = ['*'];
                    }
                    $delay = $this->parseCrawlDelay($value);
                    if ($delay !== null) {
                        foreach ($currentUserAgents as $ua) {
                            $parsed->userAgents[] = $ua;
                            // Store crawl delay in the first user-agent's delay
                            if ($parsed->crawlDelay === null) {
                                $parsed->crawlDelay = $delay;
                            }
                        }
                        // Store per user-agent
                        if (!isset($parsed->crawlDelays)) {
                            $parsed->crawlDelays = [];
                        }
                        foreach ($currentUserAgents as $ua) {
                            $parsed->crawlDelays[$ua] = $delay;
                        }
                    }
                    break;

                case 'sitemap':
                    $parsed->sitemaps[] = $value;
                    break;
            }

            // Reset user-agents after non-user-agent directive (except sitemap)
            if ($directive !== 'user-agent' && $directive !== 'sitemap') {
                $currentUserAgents = [];
                $inGroup = false;
            }
        }

        // Deduplicate user-agents
        $parsed->userAgents = array_values(array_unique($parsed->userAgents));

        // Deduplicate rules by user-agent and path
        $uniqueRules = [];
        $seen = [];
        foreach ($parsed->rules as $rule) {
            $key = $rule->userAgent . '|' . $rule->path . '|' . ($rule->allow ? '1' : '0');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueRules[] = $rule;
            }
        }
        $parsed->rules = $uniqueRules;

        // Check if effectively empty (only comments/whitespace)
        if (empty($parsed->userAgents) && empty($parsed->rules) && empty($parsed->sitemaps)) {
            $parsed->isEmpty = true;
        }

        return $parsed;
    }

    /**
     * Check if a URL is allowed by robots.txt rules.
     *
     * @param string $url The URL to check
     * @param ParsedRobots $parsed The parsed robots.txt data
     * @param string $userAgent The user agent to check against
     * @return bool True if allowed, false if disallowed
     */
    public function isAllowed(string $url, ParsedRobots $parsed, string $userAgent = '*'): bool
    {
        // Empty robots.txt allows all
        if ($parsed->isEmpty) {
            return true;
        }

        // Handle invalid URLs - default to allow
        if (empty($url)) {
            return true;
        }

        // Extract path from URL
        $path = parse_url($url, PHP_URL_PATH) ?? '/';
        
        // Add query string if present
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query !== null && $query !== false) {
            $path .= '?' . $query;
        }
        
        // Add fragment if present
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        if ($fragment !== null && $fragment !== false) {
            $path .= '#' . $fragment;
        }

        // Get rules for user-agent (specific first, then wildcard)
        $specificRules = $this->getRulesForUserAgent($parsed, $userAgent);
        $wildcardRules = $userAgent !== '*' ? $this->getRulesForUserAgent($parsed, '*') : [];

        // Combine rules - specific rules take precedence
        $allRules = array_merge($specificRules, $wildcardRules);

        if (empty($allRules)) {
            return true; // No rules = allow all
        }

        // Find the most specific matching rule
        $matchedRules = [];
        foreach ($allRules as $rule) {
            if ($this->pathMatches($path, $rule->path)) {
                $matchedRules[] = $rule;
            }
        }

        if (empty($matchedRules)) {
            return true; // No matching rules = allow
        }

        // Sort by path length (most specific wins)
        usort($matchedRules, function ($a, $b) {
            return strlen($b->path) - strlen($a->path);
        });

        // Check the most specific rule
        // Allow rules override disallow rules of same specificity
        foreach ($matchedRules as $rule) {
            // Return based on the first (most specific) matching rule
            return $rule->allow;
        }

        return true;
    }

    /**
     * Get the crawl delay for a user agent.
     *
     * @param ParsedRobots $parsed The parsed robots.txt data
     * @param string $userAgent The user agent to check
     * @return float|int|null The crawl delay in seconds, or null if not specified
     */
    public function getCrawlDelay(ParsedRobots $parsed, string $userAgent = '*'): float|int|null
    {
        // Check for user-agent specific crawl delay
        if (isset($parsed->crawlDelays[$userAgent])) {
            return $parsed->crawlDelays[$userAgent];
        }

        // Fall back to wildcard
        if ($userAgent !== '*' && isset($parsed->crawlDelays['*'])) {
            return $parsed->crawlDelays['*'];
        }

        // Legacy: use the general crawlDelay property
        return $parsed->crawlDelay;
    }

    /**
     * Get sitemap URLs from robots.txt.
     *
     * @param ParsedRobots $parsed The parsed robots.txt data
     * @return array List of sitemap URLs
     */
    public function getSitemaps(ParsedRobots $parsed): array
    {
        return $parsed->sitemaps ?? [];
    }

    /**
     * Get disallowed paths for a user agent.
     *
     * @param ParsedRobots $parsed The parsed robots.txt data
     * @param string $userAgent The user agent to check
     * @return array List of disallowed paths
     */
    public function getDisallowedPaths(ParsedRobots $parsed, string $userAgent = '*'): array
    {
        return $parsed->getDisallowedPaths($userAgent);
    }

    /**
     * Check if a path matches a robots.txt pattern.
     *
     * @param string $path The URL path to check
     * @param string $pattern The robots.txt pattern
     * @return bool True if matches
     */
    protected function pathMatches(string $path, string $pattern): bool
    {
        // Empty pattern matches nothing
        if ($pattern === '') {
            return false;
        }

        // Normalize the pattern and path for URL-encoded character matching
        // Both should be compared in decoded form for proper matching
        $decodedPattern = rawurldecode($pattern);
        $decodedPath = rawurldecode($path);

        // Handle $ end marker
        $hasEndMarker = str_ends_with($decodedPattern, '$');
        if ($hasEndMarker) {
            $decodedPattern = substr($decodedPattern, 0, -1);
        }

        // Check if pattern contains wildcards
        $hasWildcards = str_contains($decodedPattern, '*');

        // Handle path matching
        // If pattern contains special URL characters (? or #), we need exact prefix matching
        // Otherwise, apply standard robots.txt matching rules
        if (!$hasEndMarker && !$hasWildcards) {
            // Check if pattern ends with /
            if (str_ends_with($decodedPattern, '/')) {
                // Directory pattern: /admin/ matches /admin/ and /admin/anything
                $regex = '/^' . preg_quote($decodedPattern, '/') . '/';
            } elseif (str_contains($decodedPattern, '?') || str_contains($decodedPattern, '#')) {
                // Pattern contains query string or fragment
                // /search?q= should match /search?q=test but not /search?other
                $regex = '/^' . preg_quote($decodedPattern, '/') . '/';
            } else {
                // Exact path or prefix with /: /private matches /private or /private/ or /private? or /private#
                $regex = '/^' . preg_quote($decodedPattern, '/') . '(\/|\?|#|$)/';
            }
            return (bool) preg_match($regex, $decodedPath);
        }

        // Convert robots.txt pattern to regex
        // * matches any sequence of characters
        // Note: robots.txt matching is CASE-SENSITIVE
        $regex = preg_quote($decodedPattern, '/');
        $regex = str_replace('\*', '.*', $regex);

        // Add anchors based on $ marker
        if ($hasEndMarker) {
            $regex = '/^' . $regex . '$/';
        } else {
            $regex = '/^' . $regex . '/';
        }

        return (bool) preg_match($regex, $decodedPath);
    }

    /**
     * Get rules for a specific user-agent.
     *
     * @param ParsedRobots $parsed The parsed robots.txt data
     * @param string $userAgent The user agent to get rules for
     * @return array Array of RobotsRule objects
     */
    protected function getRulesForUserAgent(ParsedRobots $parsed, string $userAgent): array
    {
        return array_values(array_filter(
            $parsed->rules,
            fn($r) => $r->userAgent === $userAgent
        ));
    }

    /**
     * Parse crawl delay value.
     *
     * @param string $value The crawl delay value
     * @return float|int|null The parsed delay or null if invalid
     */
    protected function parseCrawlDelay(string $value): float|int|null
    {
        if (!is_numeric($value)) {
            return null;
        }

        $delay = (float) $value;

        // Negative values are invalid
        if ($delay < 0) {
            return null;
        }

        // Return integer if whole number, otherwise float
        return $delay == (int) $delay ? (int) $delay : $delay;
    }

    /**
     * Check if a host is blocked for SSRF prevention.
     *
     * @param string $host The hostname to check
     * @return bool True if blocked
     */
    protected function isBlockedHost(string $host): bool
    {
        $host = strtolower($host);

        // Check blocked hostnames
        foreach (self::BLOCKED_HOSTNAMES as $blocked) {
            if ($host === $blocked || str_ends_with($host, '.' . $blocked)) {
                return true;
            }
        }

        // Convert international domain names to ASCII (punycode)
        // This handles domains like "пример.org" -> "xn--e1afmkfd.org"
        $asciiHost = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if ($asciiHost === false) {
            // If conversion fails, use original host
            $asciiHost = $host;
        }

        // Try to resolve the host to check IP
        try {
            $ip = gethostbyname($asciiHost);
            
            // If gethostbyname returns the same value, it couldn't resolve
            // We'll allow it to proceed and let the HTTP client handle the error
            if ($ip === $asciiHost) {
                return false;
            }

            // Check if IP is in blocked ranges
            if ($this->isBlockedIp($ip)) {
                return true;
            }
        } catch (\Exception $e) {
            // If resolution fails, allow it (HTTP client will handle error)
            return false;
        }

        return false;
    }

    /**
     * Check if an IP address is in a blocked range.
     *
     * @param string $ip The IP address to check
     * @return bool True if blocked
     */
    protected function isBlockedIp(string $ip): bool
    {
        // Convert IP to long
        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            // IPv6 handling
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                // Block IPv6 localhost
                if ($ip === '::1' || $ip === '::') {
                    return true;
                }
                // Block IPv4-mapped IPv6 localhost
                if (str_starts_with($ip, '::ffff:127.')) {
                    return true;
                }
            }
            return false;
        }

        // Check CIDR ranges
        foreach (self::BLOCKED_IP_RANGES as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP is within a CIDR range.
     *
     * @param string $ip The IP address
     * @param string $cidr The CIDR range
     * @return bool True if IP is in range
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $bits) = explode('/', $cidr);
        
        $subnetLong = ip2long($subnet);
        $ipLong = ip2long($ip);
        
        if ($subnetLong === false || $ipLong === false) {
            return false;
        }
        
        $mask = -1 << (32 - (int)$bits);
        $subnetLong &= $mask;
        
        return ($ipLong & $mask) === $subnetLong;
    }
}
