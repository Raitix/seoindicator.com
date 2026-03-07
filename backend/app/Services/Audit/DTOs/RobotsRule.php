<?php

namespace App\Services\Audit\DTOs;

/**
 * Data Transfer Object for a single robots.txt rule.
 * 
 * @property string $userAgent User-agent this rule applies to
 * @property string $path Path pattern (may contain wildcards)
 * @property bool $allow True for Allow directive, false for Disallow
 */
class RobotsRule
{
    public function __construct(
        public readonly string $userAgent,
        public readonly string $path,
        public readonly bool $allow = false
    ) {}
}
