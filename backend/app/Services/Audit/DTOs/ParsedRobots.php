<?php

namespace App\Services\Audit\DTOs;

/**
 * Data Transfer Object for parsed robots.txt content.
 * 
 * @property array $userAgents List of user-agents defined in robots.txt
 * @property array $rules Array of RobotsRule objects
 * @property array $sitemaps List of sitemap URLs
 * @property int|float|null $crawlDelay Crawl delay in seconds (for wildcard)
 * @property array $crawlDelays Per-user-agent crawl delays
 * @property bool $isEmpty Whether the robots.txt is empty or invalid
 */
class ParsedRobots
{
    /**
     * List of user-agents defined in robots.txt.
     */
    public array $userAgents = [];

    /**
     * Array of RobotsRule objects.
     */
    public array $rules = [];

    /**
     * List of sitemap URLs.
     */
    public array $sitemaps = [];

    /**
     * Crawl delay in seconds (for wildcard user-agent).
     * @deprecated Use $crawlDelays instead
     */
    public int|float|null $crawlDelay = null;

    /**
     * Per-user-agent crawl delays.
     */
    public array $crawlDelays = [];

    /**
     * Whether the robots.txt is empty or invalid.
     */
    public bool $isEmpty = false;

    /**
     * Get disallowed paths for a specific user-agent.
     *
     * @param string $userAgent
     * @return array
     */
    public function getDisallowedPaths(string $userAgent = '*'): array
    {
        $rules = $this->getRules($userAgent);
        $paths = [];
        
        foreach ($rules as $rule) {
            if (!$rule->allow && $rule->path !== '') {
                $paths[] = $rule->path;
            }
        }
        
        return array_values(array_unique($paths));
    }

    /**
     * Get rules for a specific user-agent.
     *
     * @param string $userAgent
     * @return array
     */
    public function getRules(string $userAgent = '*'): array
    {
        // Get rules that match the specific user-agent OR wildcard
        $specificRules = [];
        $wildcardRules = [];
        
        foreach ($this->rules as $rule) {
            if ($rule->userAgent === $userAgent) {
                $specificRules[] = $rule;
            } elseif ($rule->userAgent === '*') {
                $wildcardRules[] = $rule;
            }
        }
        
        // Merge: specific rules first, then wildcard
        return array_merge($specificRules, $wildcardRules);
    }
}
