<?php

namespace App\Services;

use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\UserOnboarding;
use App\Events\UserActivated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnboardingService
{
    /**
     * Blocked URL patterns for SSRF protection.
     */
    private const BLOCKED_PATTERNS = [
        'localhost',
        '127.0.0.1',
        '10.',
        '172.16.',
        '172.17.',
        '172.18.',
        '172.19.',
        '172.20.',
        '172.21.',
        '172.22.',
        '172.23.',
        '172.24.',
        '172.25.',
        '172.26.',
        '172.27.',
        '172.28.',
        '172.29.',
        '172.30.',
        '172.31.',
        '192.168.',
        '169.254.169.254',
    ];

    /**
     * Blocked protocols.
     */
    private const BLOCKED_PROTOCOLS = [
        'file',
        'gopher',
        'ftp',
        'dict',
        'ldap',
        'php',
        'data',
        'expect',
        'zip',
    ];

    /**
     * Maximum URL length.
     */
    private const MAX_URL_LENGTH = 500;

    /**
     * Maximum keyword length.
     */
    private const MAX_KEYWORD_LENGTH = 255;

    /**
     * Total number of onboarding steps.
     */
    private const TOTAL_STEPS = 5;

    /**
     * Get the user's onboarding progress.
     */
    public function getProgress(User $user): ?UserOnboarding
    {
        return UserOnboarding::where('user_id', $user->id)->first();
    }

    /**
     * Start onboarding for a user.
     */
    public function startOnboarding(User $user): UserOnboarding
    {
        $existing = $this->getProgress($user);

        if ($existing) {
            // If skipped, clear skipped_at and resume
            if ($existing->skipped_at) {
                $existing->update(['skipped_at' => null]);
            }
            return $existing;
        }

        return UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 1,
        ]);
    }

    /**
     * Update a step with data.
     */
    public function updateStep(User $user, int $step, array $data): UserOnboarding
    {
        $onboarding = $this->getProgress($user);

        if (!$onboarding) {
            $onboarding = $this->startOnboarding($user);
        }

        // Validate step
        if ($step < 1 || $step > self::TOTAL_STEPS) {
            throw ValidationException::withMessages([
                'step' => ['Invalid step number.'],
            ]);
        }

        // Validate and update based on step
        match ($step) {
            1 => $this->updateStep1($onboarding, $data),
            2 => $this->updateStep2($onboarding, $data),
            4 => $this->updateStep4($onboarding, $data),
            default => null,
        };

        // Mark step as completed
        return $this->completeStep($user, $step);
    }

    /**
     * Complete a step.
     */
    public function completeStep(User $user, int $step): UserOnboarding
    {
        $onboarding = $this->getProgress($user);

        if (!$onboarding) {
            $onboarding = $this->startOnboarding($user);
        }

        $completedSteps = $onboarding->completed_steps ?? [];

        if (!in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
        }

        $nextStep = min($step + 1, self::TOTAL_STEPS);

        $onboarding->update([
            'completed_steps' => $completedSteps,
            'current_step' => $nextStep,
        ]);

        return $onboarding->fresh();
    }

    /**
     * Skip onboarding.
     */
    public function skipOnboarding(User $user): UserOnboarding
    {
        $onboarding = $this->getProgress($user);

        if (!$onboarding) {
            $onboarding = UserOnboarding::create([
                'user_id' => $user->id,
                'skipped_at' => now(),
            ]);
        } elseif (!$onboarding->completed_at) {
            $onboarding->update(['skipped_at' => now()]);
        }

        return $onboarding;
    }

    /**
     * Complete onboarding.
     */
    public function completeOnboarding(User $user): UserOnboarding
    {
        $onboarding = $this->getProgress($user);

        if (!$onboarding) {
            $onboarding = UserOnboarding::create([
                'user_id' => $user->id,
                'completed_at' => now(),
            ]);
            return $onboarding;
        }

        // Already completed
        if ($onboarding->completed_at) {
            return $onboarding;
        }

        DB::transaction(function () use ($onboarding, $user) {
            $keywordId = null;
            $projectId = $onboarding->project_id;

            // Create keyword if provided
            if ($onboarding->first_keyword) {
                $keyword = $this->createKeyword($user, $onboarding);
                $keywordId = $keyword->id;
            }

            $onboarding->update([
                'completed_at' => now(),
                'keyword_id' => $keywordId,
            ]);

            // Dispatch event
            UserActivated::dispatch($user, $keywordId, $projectId);
        });

        return $onboarding->fresh();
    }

    /**
     * Calculate progress percentage.
     */
    public function calculateProgressPercentage(UserOnboarding $onboarding): int
    {
        if ($onboarding->completed_at) {
            return 100;
        }

        return min(100, $onboarding->current_step * 20);
    }

    /**
     * Validate if a URL is allowed (SSRF protection).
     */
    public function isValidUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        // Check length
        if (strlen($url) > self::MAX_URL_LENGTH) {
            return false;
        }

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Parse URL
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return false;
        }

        // Check for blocked protocols
        $scheme = strtolower($parsed['scheme']);
        if (in_array($scheme, self::BLOCKED_PROTOCOLS)) {
            return false;
        }

        // Only allow http and https
        if (!in_array($scheme, ['http', 'https'])) {
            return false;
        }

        $host = strtolower($parsed['host']);

        // Check for blocked patterns
        foreach (self::BLOCKED_PATTERNS as $pattern) {
            if (str_contains($host, $pattern) || str_contains($url, $pattern)) {
                return false;
            }
        }

        // Resolve host to IP and check if it's private
        $ip = gethostbyname($host);
        if ($ip !== $host) {
            // Check if IP is in private range
            if ($this->isPrivateIp($ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if an IP address is private.
     */
    private function isPrivateIp(string $ip): bool
    {
        $longIp = ip2long($ip);
        if ($longIp === false) {
            return false;
        }

        // Private IP ranges
        $privateRanges = [
            ['10.0.0.0', '10.255.255.255'],
            ['172.16.0.0', '172.31.255.255'],
            ['192.168.0.0', '192.168.255.255'],
            ['127.0.0.0', '127.255.255.255'],
            ['169.254.0.0', '169.254.255.255'],
        ];

        foreach ($privateRanges as $range) {
            $start = ip2long($range[0]);
            $end = ip2long($range[1]);
            if ($longIp >= $start && $longIp <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update step 1 (website URL).
     */
    private function updateStep1(UserOnboarding $onboarding, array $data): void
    {
        $websiteUrl = $data['website_url'] ?? null;

        if (!$this->isValidUrl($websiteUrl)) {
            throw ValidationException::withMessages([
                'website_url' => ['Please provide a valid public URL.'],
            ]);
        }

        $onboarding->update(['website_url' => $websiteUrl]);
    }

    /**
     * Update step 2 (first keyword).
     */
    private function updateStep2(UserOnboarding $onboarding, array $data): void
    {
        $keyword = trim($data['first_keyword'] ?? '');

        if (empty($keyword)) {
            throw ValidationException::withMessages([
                'first_keyword' => ['Please provide a keyword to track.'],
            ]);
        }

        if (strlen($keyword) > self::MAX_KEYWORD_LENGTH) {
            throw ValidationException::withMessages([
                'first_keyword' => ['Keyword must be 255 characters or less.'],
            ]);
        }

        // Normalize: lowercase
        $keyword = strtolower($keyword);

        $onboarding->update(['first_keyword' => $keyword]);
    }

    /**
     * Update step 4 (alert preferences).
     */
    private function updateStep4(UserOnboarding $onboarding, array $data): void
    {
        $preferences = $data['alert_preferences'] ?? [];

        if (!is_array($preferences)) {
            throw ValidationException::withMessages([
                'alert_preferences' => ['Invalid alert preferences format.'],
            ]);
        }

        // Validate boolean values
        $email = $preferences['email'] ?? false;
        $weekly = $preferences['weekly'] ?? false;

        if (!is_bool($email) || !is_bool($weekly)) {
            throw ValidationException::withMessages([
                'alert_preferences' => ['Alert preferences must be boolean values.'],
            ]);
        }

        $onboarding->update([
            'alert_preferences' => [
                'email' => $email,
                'weekly' => $weekly,
            ],
        ]);
    }

    /**
     * Create a keyword from onboarding data.
     */
    private function createKeyword(User $user, UserOnboarding $onboarding): Keyword
    {
        // Find or create site for the user
        $site = Site::firstOrCreate(
            ['url' => $onboarding->website_url ?? 'https://example.com'],
            ['user_id' => $user->id]
        );

        return Keyword::create([
            'site_id' => $site->id,
            'keyword' => $onboarding->first_keyword,
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
        ]);
    }
}
