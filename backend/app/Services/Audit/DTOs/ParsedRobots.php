<?php

namespace App\Services\Audit\DTOs;

/**
 * Data Transfer Object for parsed robots.txt content.
 * 
 * @property array $userAgents List of user-agents defined in robots.txt
 * @property array $rules Array of RobotsRule objects
 * @property array $sitemaps List of sitemap URLs
 * @property int|null $crawlDelay Crawl delay in seconds
 * @property bool $isEmpty Whether the robots.txt is empty or invalid
 */
class ParsedRobots
{
    public array $userAgents = [];
    public array $rules = [];
    public array $sitemaps = [];
    public ?int $crawlDelay = null;
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
        return array_values(array_filter(
            array_map(fn($r) => $r->allow ? null : $r->path, $rules),
            fn($p) => $p !== null
        ));
    }

    /**
     * Get rules for a specific user-agent.
     *
     * @param string $userAgent
     * @return array
     */
    public function getRules(string $userAgent = '*'): array
    {
        return array_values(array_filter(
            $this->rules,
            fn($r) => $r->userAgent === $userAgent || $r->userAgent === '*'
        ));
    }
}
