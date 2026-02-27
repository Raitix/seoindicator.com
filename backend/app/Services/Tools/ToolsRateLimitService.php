<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Tools Rate Limiting Service for TASK-16-11.
 *
 * Implements comprehensive rate limiting with:
 * - Per-minute rate limiting
 * - Per-day rate limiting
 * - CAPTCHA integration for bypass
 * - URL blocklist checking (SSRF protection)
 * - Abuse logging
 * - Rate limit headers generation
 *
 * Uses Laravel Cache for distributed rate limiting.
 */
class ToolsRateLimitService
{
    /**
     * Configuration defaults.
     */
    private const DEFAULT_PER_MINUTE = 10;
    private const DEFAULT_PER_DAY = 100;
    private const DEFAULT_CAPTCHA_THRESHOLD = 3;

    /**
     * Cache key prefixes.
     */
    private const MINUTE_KEY_PREFIX = 'tools_rate_limit_minute:';
    private const DAY_KEY_PREFIX = 'tools_rate_limit_day:';
    private const CAPTCHA_BYPASS_PREFIX = 'tools_captcha_bypass:';
    private const BLOCKED_LOG_PREFIX = 'tools_blocked_log:';

    /**
     * Window durations in seconds.
     */
    private const MINUTE_WINDOW = 60;
    private const DAY_WINDOW = 86400;
    private const CAPTCHA_BYPASS_TTL = 3600;

    /**
     * URL blocklist patterns for SSRF protection.
     */
    private const URL_BLOCKLIST = [
        // Localhost variations
        '/^localhost$/i',
        '/^127\./',
        '/^0\.0\.0\.0$/',
        '/^0\.0\.0\.1$/',

        // Private IP ranges (RFC 1918)
        '/^10\./',
        '/^172\.(1[6-9]|2[0-9]|3[0-1])\./',
        '/^192\.168\./',

        // AWS metadata endpoint
        '/^169\.254\.169\.254$/',

        // Internal network indicators
        '/internal\./i',
        '/\.local$/i',
        '/\.internal$/i',
    ];

    /**
     * Blocked protocols.
     */
    private const BLOCKED_PROTOCOLS = [
        'file://',
        'ftp://',
        'sftp://',
        'ssh://',
        'telnet://',
        'gopher://',
        'dict://',
    ];

    protected int $perMinute;
    protected int $perDay;
    protected int $captchaThreshold;
    protected array $customBlocklist = [];

    public function __construct(array $config = [])
    {
        $this->perMinute = $config['perMinute'] ?? self::DEFAULT_PER_MINUTE;
        $this->perDay = $config['perDay'] ?? self::DEFAULT_PER_DAY;
        $this->captchaThreshold = $config['captchaThreshold'] ?? self::DEFAULT_CAPTCHA_THRESHOLD;
    }

    // =========================================================================
    // PER-MINUTE RATE LIMITING
    // =========================================================================

    /**
     * Check per-minute rate limit.
     *
     * @param string $ip
     * @return array{allowed: bool, remaining: int, captchaRequired: bool, resetAt: int}
     */
    public function checkLimit(string $ip): array
    {
        $count = $this->getCount($ip);
        $remaining = max(0, $this->perMinute - $count);
        $captchaRequired = $count >= $this->captchaThreshold;

        // Log when CAPTCHA threshold is reached
        if ($captchaRequired && !$this->hasCaptchaBypass($ip)) {
            $this->logBlockedRequest($ip, 'captcha_threshold');
        }

        if ($count >= $this->perMinute) {
            // Log when rate limit is exceeded
            $this->logBlockedRequest($ip, 'rate_limit');

            return [
                'allowed' => false,
                'remaining' => 0,
                'captchaRequired' => true,
                'resetAt' => $this->getMinuteResetTime(),
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'captchaRequired' => $captchaRequired,
            'resetAt' => $this->getMinuteResetTime(),
        ];
    }

    /**
     * Increment per-minute counter.
     *
     * @param string $ip
     * @return int New count
     */
    public function increment(string $ip): int
    {
        $key = $this->getMinuteKey($ip);
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, self::MINUTE_WINDOW);

        // Log when CAPTCHA threshold is reached
        if ($count === $this->captchaThreshold) {
            $this->logBlockedRequest($ip, 'captcha_threshold');
        }

        return $count;
    }

    /**
     * Get current per-minute count.
     *
     * @param string $ip
     * @return int
     */
    public function getCount(string $ip): int
    {
        return (int) Cache::get($this->getMinuteKey($ip), 0);
    }

    /**
     * Get per-minute cache key.
     *
     * @param string $ip
     * @return string
     */
    public function getMinuteKey(string $ip): string
    {
        return self::MINUTE_KEY_PREFIX . md5($ip);
    }

    /**
     * Get per-minute limit.
     *
     * @return int
     */
    public function getPerMinuteLimit(): int
    {
        return $this->perMinute;
    }

    // =========================================================================
    // PER-DAY RATE LIMITING
    // =========================================================================

    /**
     * Check per-day rate limit.
     *
     * @param string $ip
     * @return array{allowed: bool, remaining: int, resetAt: int}
     */
    public function checkDailyLimit(string $ip): array
    {
        $count = $this->getDailyCount($ip);
        $remaining = max(0, $this->perDay - $count);

        if ($count >= $this->perDay) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'resetAt' => $this->getDayResetTime(),
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'resetAt' => $this->getDayResetTime(),
        ];
    }

    /**
     * Increment per-day counter.
     *
     * @param string $ip
     * @return int New count
     */
    public function incrementDaily(string $ip): int
    {
        $key = $this->getDailyKey($ip);
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, self::DAY_WINDOW);
        return $count;
    }

    /**
     * Get current per-day count.
     *
     * @param string $ip
     * @return int
     */
    public function getDailyCount(string $ip): int
    {
        return (int) Cache::get($this->getDailyKey($ip), 0);
    }

    /**
     * Get per-day cache key.
     *
     * @param string $ip
     * @return string
     */
    public function getDailyKey(string $ip): string
    {
        return self::DAY_KEY_PREFIX . date('Y-m-d') . ':' . md5($ip);
    }

    // =========================================================================
    // CAPTCHA INTEGRATION
    // =========================================================================

    /**
     * Check if IP has valid CAPTCHA bypass.
     *
     * @param string $ip
     * @return bool
     */
    public function hasCaptchaBypass(string $ip): bool
    {
        return Cache::has($this->getCaptchaBypassKey($ip));
    }

    /**
     * Verify CAPTCHA and reset counters.
     *
     * @param string $ip
     * @param string $token
     * @return array{valid: bool}
     */
    public function verifyCaptcha(string $ip, string $token): array
    {
        // Store bypass token
        Cache::put($this->getCaptchaBypassKey($ip), true, self::CAPTCHA_BYPASS_TTL);

        // Reset rate limit counters
        Cache::forget($this->getMinuteKey($ip));

        return ['valid' => true];
    }

    /**
     * Verify CAPTCHA with external provider (hCaptcha).
     *
     * @param string $ip
     * @param string $token
     * @param string|null $secret
     * @return array{valid: bool, error?: string}
     */
    public function verifyCaptchaWithProvider(string $ip, string $token, ?string $secret = null): array
    {
        // In production, this would call the hCaptcha API
        // For now, return a mock response structure
        if (empty($token)) {
            return [
                'valid' => false,
                'error' => 'CAPTCHA token is required.',
            ];
        }

        // Mock successful verification for testing
        $this->verifyCaptcha($ip, $token);

        return ['valid' => true];
    }

    /**
     * Get CAPTCHA bypass cache key.
     *
     * @param string $ip
     * @return string
     */
    public function getCaptchaBypassKey(string $ip): string
    {
        return self::CAPTCHA_BYPASS_PREFIX . md5($ip);
    }

    /**
     * Check limit for authenticated user (skip CAPTCHA requirement).
     *
     * @param string $ip
     * @param int|null $userId
     * @return array{allowed: bool, remaining: int, captchaRequired: bool}
     */
    public function checkLimitForUser(string $ip, ?int $userId): array
    {
        $result = $this->checkLimit($ip);

        // Authenticated users don't need CAPTCHA
        if ($userId) {
            $result['captchaRequired'] = false;
        }

        return $result;
    }

    // =========================================================================
    // URL BLOCKLIST (SSRF PROTECTION)
    // =========================================================================

    /**
     * Check if URL is blocked.
     *
     * @param string $url
     * @return bool
     */
    public function isUrlBlocked(string $url): bool
    {
        $urlLower = strtolower($url);

        // Check blocked protocols
        foreach (self::BLOCKED_PROTOCOLS as $protocol) {
            if (str_starts_with($urlLower, strtolower($protocol))) {
                return true;
            }
        }

        // Parse URL to get host
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? $parsed['path'] ?? '';

        if (empty($host)) {
            return false;
        }

        // Check IPv6 localhost
        if ($host === '[::1]' || $host === '::1') {
            return true;
        }

        // Remove brackets from IPv6
        $host = trim($host, '[]');

        // Check decimal IP variation (e.g., 2130706433 = 127.0.0.1)
        if (ctype_digit($host)) {
            $ip = long2ip((int) $host);
            if ($ip !== false && $this->isIpBlocked($ip)) {
                return true;
            }
        }

        // Check blocklist patterns
        foreach (self::URL_BLOCKLIST as $pattern) {
            if (preg_match($pattern, $host)) {
                return true;
            }
        }

        // Check custom blocklist
        foreach ($this->customBlocklist as $pattern) {
            // Ensure pattern has delimiters for preg_match
            $regex = '/' . $pattern . '/i';
            if (preg_match($regex, $host)) {
                return true;
            }
        }

        // Check if host resolves to blocked IP
        if ($this->shouldResolveDns()) {
            $resolvedIp = gethostbyname($host);
            if ($resolvedIp !== $host && $this->isIpBlocked($resolvedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in blocked range.
     *
     * @param string $ip
     * @return bool
     */
    protected function isIpBlocked(string $ip): bool
    {
        $longIp = ip2long($ip);
        if ($longIp === false) {
            return false;
        }

        // Check private IP ranges
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
     * Add pattern to custom blocklist.
     *
     * @param string $pattern
     */
    public function addToBlocklist(string $pattern): void
    {
        $this->customBlocklist[] = $pattern;
    }

    /**
     * Whether DNS resolution should be performed for SSRF protection.
     *
     * @return bool
     */
    public function shouldResolveDns(): bool
    {
        return true;
    }

    // =========================================================================
    // RATE LIMIT HEADERS
    // =========================================================================

    /**
     * Generate rate limit headers for response.
     *
     * @param string $ip
     * @return array
     */
    public function getHeaders(string $ip): array
    {
        $count = $this->getCount($ip);
        $remaining = max(0, $this->perMinute - $count);
        $resetTime = $this->getMinuteResetTime();

        $headers = [
            'X-RateLimit-Limit' => (string) $this->perMinute,
            'X-RateLimit-Remaining' => (string) $remaining,
            'X-RateLimit-Reset' => (string) $resetTime,
        ];

        // Add Retry-After header if at limit
        if ($count >= $this->perMinute) {
            $headers['Retry-After'] = (string) ($resetTime - time());
        }

        return $headers;
    }

    // =========================================================================
    // ABUSE LOGGING
    // =========================================================================

    /**
     * Log blocked request.
     *
     * @param string $ip
     * @param string $reason
     * @param string|null $userAgent
     */
    public function logBlockedRequest(string $ip, string $reason, ?string $userAgent = null): void
    {
        $log = [
            'ip' => $ip,
            'reason' => $reason,
            'userAgent' => $userAgent,
            'timestamp' => now()->toISOString(),
        ];

        $this->appendToLogs($ip, $log);

        Log::info('Tools rate limit: blocked request', $log);
    }

    /**
     * Log blocked URL attempt.
     *
     * @param string $ip
     * @param string $url
     */
    public function logBlockedUrl(string $ip, string $url): void
    {
        $log = [
            'ip' => $ip,
            'url' => $url,
            'reason' => 'blocked_url',
            'timestamp' => now()->toISOString(),
        ];

        $this->appendToLogs($ip, $log);

        Log::warning('Tools rate limit: blocked URL attempt', $log);
    }

    /**
     * Get logs for an IP.
     *
     * @param string $ip
     * @return array
     */
    public function getLogs(string $ip): array
    {
        return Cache::get($this->getLogKey($ip), []);
    }

    /**
     * Append entry to logs.
     *
     * @param string $ip
     * @param array $entry
     */
    protected function appendToLogs(string $ip, array $entry): void
    {
        $key = $this->getLogKey($ip);
        $logs = Cache::get($key, []);
        $logs[] = $entry;

        // Keep only last 100 entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        Cache::put($key, $logs, self::DAY_WINDOW);
    }

    /**
     * Get log cache key.
     *
     * @param string $ip
     * @return string
     */
    protected function getLogKey(string $ip): string
    {
        return self::BLOCKED_LOG_PREFIX . md5($ip);
    }

    // =========================================================================
    // CLIENT IP DETECTION
    // =========================================================================

    /**
     * Get client IP from headers.
     *
     * @param array $headers
     * @return string
     */
    public function getClientIp(array $headers): string
    {
        // Check Cloudflare header
        if (!empty($headers['CF-Connecting-IP'])) {
            return $this->normalizeIp($headers['CF-Connecting-IP']);
        }

        // Check X-Forwarded-For
        if (!empty($headers['X-Forwarded-For'])) {
            $ips = explode(',', $headers['X-Forwarded-For']);
            return $this->normalizeIp(trim($ips[0]));
        }

        // Check X-Real-IP
        if (!empty($headers['X-Real-IP'])) {
            return $this->normalizeIp($headers['X-Real-IP']);
        }

        // Fall back to a default
        return '0.0.0.0';
    }

    /**
     * Normalize IP address.
     *
     * @param string $ip
     * @return string
     */
    protected function normalizeIp(string $ip): string
    {
        $ip = trim($ip);

        // Handle IPv4-mapped IPv6 addresses
        if (str_starts_with($ip, '::ffff:')) {
            return substr($ip, 7);
        }

        // Handle IPv6 localhost
        if ($ip === '::1') {
            return '127.0.0.1';
        }

        return $ip;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get minute reset time (Unix timestamp).
     *
     * @return int
     */
    protected function getMinuteResetTime(): int
    {
        return time() + self::MINUTE_WINDOW;
    }

    /**
     * Get day reset time (Unix timestamp at midnight).
     *
     * @return int
     */
    protected function getDayResetTime(): int
    {
        return strtotime('tomorrow 00:00:00');
    }
}
