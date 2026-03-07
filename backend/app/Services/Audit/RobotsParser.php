<?php

namespace App\Services\Audit;

/**
 * Robots.txt Parser Service.
 *
 * Parses and evaluates robots.txt rules for crawl permission.
 *
 * @see TASK-14-6 (Full implementation)
 * @see TASK-14-5 (Integration)
 */
class RobotsParser
{
    /**
     * Check if a URL is allowed by robots.txt rules.
     *
     * @param string $url The URL to check
     * @param array $rules The robots.txt rules
     * @param string $userAgent The user agent to check against
     * @return bool True if allowed, false if disallowed
     */
    public function isAllowed(string $url, array $rules, string $userAgent = '*'): bool
    {
        if (empty($rules) || empty($rules['user_agents'])) {
            return true;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '/';

        // Check specific user agent first, then fall back to *
        $agentsToCheck = [$userAgent, '*'];

        foreach ($agentsToCheck as $agent) {
            if (!isset($rules['user_agents'][$agent])) {
                continue;
            }

            $agentRules = $rules['user_agents'][$agent];

            // Check disallow rules
            foreach ($agentRules['disallow'] ?? [] as $disallowPath) {
                if ($this->pathMatches($path, $disallowPath)) {
                    // Check if there's an allow rule that overrides
                    foreach ($agentRules['allow'] ?? [] as $allowPath) {
                        if ($this->pathMatches($path, $allowPath) && strlen($allowPath) > strlen($disallowPath)) {
                            return true;
                        }
                    }
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the crawl delay for a user agent.
     *
     * @param array $rules The robots.txt rules
     * @param string $userAgent The user agent to check
     * @return float|null The crawl delay in seconds, or null if not specified
     */
    public function getCrawlDelay(array $rules, string $userAgent = '*'): ?float
    {
        if (empty($rules) || empty($rules['user_agents'])) {
            return null;
        }

        // Check specific user agent first
        if (isset($rules['user_agents'][$userAgent]['crawl-delay'])) {
            return $rules['user_agents'][$userAgent]['crawl-delay'];
        }

        // Fall back to wildcard
        if (isset($rules['user_agents']['*']['crawl-delay'])) {
            return $rules['user_agents']['*']['crawl-delay'];
        }

        return null;
    }

    /**
     * Get sitemap URLs from robots.txt.
     *
     * @param array $rules The robots.txt rules
     * @return array List of sitemap URLs
     */
    public function getSitemaps(array $rules): array
    {
        return $rules['sitemaps'] ?? [];
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
        if (empty($pattern)) {
            return false;
        }

        // Exact match
        if ($pattern === $path) {
            return true;
        }

        // Handle directory patterns (ending with /)
        // /admin/ should block both /admin/ and /admin
        if (str_ends_with($pattern, '/')) {
            $basePattern = rtrim($pattern, '/');
            if ($path === $basePattern) {
                return true;
            }
        }

        // Convert robots.txt pattern to regex
        // * matches any sequence of characters
        // $ matches end of URL
        $regex = preg_quote($pattern, '/');
        $regex = str_replace('\*', '.*', $regex);
        $regex = str_replace('\$', '$', $regex);
        $regex = '/^' . $regex . '/i';

        return (bool)preg_match($regex, $path);
    }
}
