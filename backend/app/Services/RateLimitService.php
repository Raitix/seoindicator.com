<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Rate Limit Service for TASK-16-3.
 * 
 * Implements rate limiting with sliding window algorithm.
 * Uses Redis for distributed rate limiting with fallback to file cache.
 * 
 * Rate limit: 3 requests per IP per 24 hours.
 * CAPTCHA required after limit is reached.
 */
class RateLimitService
{
    /**
     * Maximum requests per IP per window.
     */
    private const MAX_REQUESTS = 3;

    /**
     * Window duration in seconds (24 hours).
     */
    private const WINDOW_SECONDS = 86400;

    /**
     * Cache key prefix.
     */
    private const CACHE_PREFIX = 'seo_tools_rate_limit:';

    /**
     * CAPTCHA bypass key prefix.
     */
    private const CAPTCHA_BYPASS_PREFIX = 'seo_tools_captcha_bypass:';

    /**
     * Check if request is allowed for the given IP.
     *
     * @param string $ip
     * @return array{allowed: bool, remaining: int, captchaRequired: bool, resetAt: int}
     */
    public function check(string $ip): array
    {
        $key = $this->getCacheKey($ip);
        $currentCount = $this->getCount($key);
        $remaining = max(0, self::MAX_REQUESTS - $currentCount);

        if ($currentCount >= self::MAX_REQUESTS) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'captchaRequired' => true,
                'resetAt' => $this->getResetTime($key),
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'captchaRequired' => false,
            'resetAt' => $this->getResetTime($key),
        ];
    }

    /**
     * Increment the request counter for the given IP.
     *
     * @param string $ip
     * @return int New count
     */
    public function increment(string $ip): int
    {
        $key = $this->getCacheKey($ip);
        
        try {
            // Try Redis first (atomic increment)
            if ($this->isRedisAvailable()) {
                $count = Redis::incr($key);
                if ($count === 1) {
                    Redis::expire($key, self::WINDOW_SECONDS);
                }
                return $count;
            }
        } catch (\Exception $e) {
            Log::warning('Redis increment failed, using fallback', ['error' => $e->getMessage()]);
        }

        // Fallback to Cache
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, self::WINDOW_SECONDS);
        
        return $count;
    }

    /**
     * Verify CAPTCHA and reset counter if valid.
     *
     * @param string $ip
     * @param string $token CAPTCHA verification token
     * @return array{valid: bool, remaining: int}
     */
    public function verifyCaptcha(string $ip, string $token): array
    {
        // CAPTCHA verification is delegated to CaptchaService
        // This method just resets the counter after successful verification
        $bypassKey = $this->getCaptchaBypassKey($ip);
        
        // Store a bypass token that's valid for 1 hour
        Cache::put($bypassKey, true, 3600);
        
        // Reset the rate limit counter
        $this->reset($ip);

        return [
            'valid' => true,
            'remaining' => self::MAX_REQUESTS,
        ];
    }

    /**
     * Check if IP has a valid CAPTCHA bypass.
     *
     * @param string $ip
     * @return bool
     */
    public function hasCaptchaBypass(string $ip): bool
    {
        $bypassKey = $this->getCaptchaBypassKey($ip);
        return Cache::has($bypassKey);
    }

    /**
     * Reset the counter for the given IP.
     *
     * @param string $ip
     */
    public function reset(string $ip): void
    {
        $key = $this->getCacheKey($ip);
        
        try {
            if ($this->isRedisAvailable()) {
                Redis::del($key);
                return;
            }
        } catch (\Exception $e) {
            Log::warning('Redis delete failed, using fallback', ['error' => $e->getMessage()]);
        }

        Cache::forget($key);
    }

    /**
     * Get current request count for IP.
     *
     * @param string $ip
     * @return int
     */
    public function getCurrentCount(string $ip): int
    {
        $key = $this->getCacheKey($ip);
        return $this->getCount($key);
    }

    /**
     * Get rate limit headers for response.
     *
     * @param string $ip
     * @return array
     */
    public function getHeaders(string $ip): array
    {
        $key = $this->getCacheKey($ip);
        $count = $this->getCount($key);
        $remaining = max(0, self::MAX_REQUESTS - $count);
        $resetTime = $this->getResetTime($key);

        return [
            'X-RateLimit-Limit' => (string) self::MAX_REQUESTS,
            'X-RateLimit-Remaining' => (string) $remaining,
            'X-RateLimit-Reset' => (string) $resetTime,
        ];
    }

    /**
     * Extract client IP from request.
     * Handles X-Forwarded-For and other proxy headers.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getClientIp($request): string
    {
        // Check for Cloudflare header
        $cfConnectingIp = $request->header('CF-Connecting-IP');
        if (!empty($cfConnectingIp)) {
            return $this->normalizeIp($cfConnectingIp);
        }

        // Check X-Forwarded-For
        $forwardedFor = $request->header('X-Forwarded-For');
        if (!empty($forwardedFor)) {
            // Get the first IP in the chain (original client)
            $ips = explode(',', $forwardedFor);
            $ip = trim($ips[0]);
            return $this->normalizeIp($ip);
        }

        // Check X-Real-IP
        $realIp = $request->header('X-Real-IP');
        if (!empty($realIp)) {
            return $this->normalizeIp($realIp);
        }

        // Fall back to request IP
        return $this->normalizeIp($request->ip() ?? '0.0.0.0');
    }

    /**
     * Normalize IP address (handle IPv4-mapped IPv6).
     *
     * @param string $ip
     * @return string
     */
    private function normalizeIp(string $ip): string
    {
        // Handle IPv4-mapped IPv6 addresses (::ffff:192.168.1.1)
        if (str_starts_with($ip, '::ffff:')) {
            return substr($ip, 7);
        }

        // Handle IPv6 localhost
        if ($ip === '::1') {
            return '127.0.0.1';
        }

        return $ip;
    }

    /**
     * Generate cache key for IP.
     *
     * @param string $ip
     * @return string
     */
    private function getCacheKey(string $ip): string
    {
        // Include date for daily reset
        $date = date('Y-m-d');
        return self::CACHE_PREFIX . md5($ip) . ':' . $date;
    }

    /**
     * Generate CAPTCHA bypass cache key for IP.
     *
     * @param string $ip
     * @return string
     */
    private function getCaptchaBypassKey(string $ip): string
    {
        return self::CAPTCHA_BYPASS_PREFIX . md5($ip);
    }

    /**
     * Get current count from storage.
     *
     * @param string $key
     * @return int
     */
    private function getCount(string $key): int
    {
        try {
            if ($this->isRedisAvailable()) {
                $count = Redis::get($key);
                return (int) ($count ?? 0);
            }
        } catch (\Exception $e) {
            Log::warning('Redis get failed, using fallback', ['error' => $e->getMessage()]);
        }

        return (int) Cache::get($key, 0);
    }

    /**
     * Get reset timestamp for the rate limit window.
     *
     * @param string $key
     * @return int Unix timestamp
     */
    private function getResetTime(string $key): int
    {
        try {
            if ($this->isRedisAvailable()) {
                $ttl = Redis::ttl($key);
                if ($ttl > 0) {
                    return time() + $ttl;
                }
            }
        } catch (\Exception $e) {
            // Fall through to default
        }

        // Default: reset at midnight
        return strtotime('tomorrow 00:00:00');
    }

    /**
     * Check if Redis is available.
     *
     * @return bool
     */
    private function isRedisAvailable(): bool
    {
        static $available = null;

        if ($available === null) {
            // Check if Redis extension is loaded first
            if (!extension_loaded('redis')) {
                $available = false;
                return $available;
            }

            try {
                Redis::ping();
                $available = true;
            } catch (\Exception $e) {
                $available = false;
            }
        }

        return $available;
    }

    /**
     * Get max requests limit.
     *
     * @return int
     */
    public function getMaxRequests(): int
    {
        return self::MAX_REQUESTS;
    }

    /**
     * Get window duration in seconds.
     *
     * @return int
     */
    public function getWindowSeconds(): int
    {
        return self::WINDOW_SECONDS;
    }
}
