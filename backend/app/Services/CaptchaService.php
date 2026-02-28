<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CAPTCHA Service for TASK-16-3.
 * 
 * Handles CAPTCHA verification for rate limit bypass.
 * Supports Google reCAPTCHA v3 (invisible) with fallback to hCaptcha.
 * 
 * Configuration via environment variables:
 * - CAPTCHA_DRIVER: 'recaptcha' or 'hcaptcha'
 * - RECAPTCHA_SITE_KEY / RECAPTCHA_SECRET_KEY
 * - HCAPTCHA_SITE_KEY / HCAPTCHA_SECRET_KEY
 */
class CaptchaService
{
    /**
     * reCAPTCHA verification URL.
     */
    private const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * hCaptcha verification URL.
     */
    private const HCAPTCHA_VERIFY_URL = 'https://hcaptcha.com/siteverify';

    /**
     * Minimum score threshold for reCAPTCHA v3.
     */
    private const MIN_SCORE = 0.5;

    /**
     * Get the configured CAPTCHA driver.
     */
    private function getDriver(): string
    {
        $driver = config('services.captcha.driver') ?? env('CAPTCHA_DRIVER', 'recaptcha');
        return is_string($driver) ? $driver : 'recaptcha';
    }

    /**
     * Get the site key for frontend CAPTCHA widget.
     *
     * @return string|null
     */
    public function getSiteKey(): ?string
    {
        $driver = $this->getDriver();

        if ($driver === 'hcaptcha') {
            return config('services.hcaptcha.site_key', env('HCAPTCHA_SITE_KEY'));
        }

        return config('services.recaptcha.site_key', env('RECAPTCHA_SITE_KEY'));
    }

    /**
     * Verify CAPTCHA response token.
     *
     * @param string $token
     * @param string|null $ip Client IP for additional verification
     * @return array{valid: bool, score?: float, error?: string}
     */
    public function verify(string $token, ?string $ip = null): array
    {
        if (empty($token)) {
            return [
                'valid' => false,
                'error' => 'CAPTCHA token is required.',
            ];
        }

        $driver = $this->getDriver();

        if ($driver === 'hcaptcha') {
            return $this->verifyHcaptcha($token, $ip);
        }

        return $this->verifyRecaptcha($token, $ip);
    }

    /**
     * Verify Google reCAPTCHA v3 token.
     *
     * @param string $token
     * @param string|null $ip
     * @return array
     */
    private function verifyRecaptcha(string $token, ?string $ip): array
    {
        $secretKey = config('services.recaptcha.secret_key', env('RECAPTCHA_SECRET_KEY'));

        if (empty($secretKey)) {
            Log::warning('reCAPTCHA secret key not configured');
            
            // In development/testing, allow bypass
            if (app()->environment(['local', 'testing'])) {
                return ['valid' => true, 'score' => 1.0];
            }

            return [
                'valid' => false,
                'error' => 'CAPTCHA service not configured.',
            ];
        }

        try {
            $response = Http::asForm()->post(self::RECAPTCHA_VERIFY_URL, [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            $data = $response->json();

            if (!isset($data['success']) || $data['success'] !== true) {
                Log::info('reCAPTCHA verification failed', [
                    'error_codes' => $data['error-codes'] ?? [],
                ]);

                return [
                    'valid' => false,
                    'error' => 'CAPTCHA verification failed.',
                ];
            }

            // Check score for reCAPTCHA v3
            $score = $data['score'] ?? 0;
            if ($score < self::MIN_SCORE) {
                Log::info('reCAPTCHA score too low', ['score' => $score]);
                
                return [
                    'valid' => false,
                    'score' => $score,
                    'error' => 'CAPTCHA verification failed. Please try again.',
                ];
            }

            return [
                'valid' => true,
                'score' => $score,
            ];
        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification error', ['error' => $e->getMessage()]);

            return [
                'valid' => false,
                'error' => 'CAPTCHA verification error. Please try again.',
            ];
        }
    }

    /**
     * Verify hCaptcha token.
     *
     * @param string $token
     * @param string|null $ip
     * @return array
     */
    private function verifyHcaptcha(string $token, ?string $ip): array
    {
        $secretKey = config('services.hcaptcha.secret_key', env('HCAPTCHA_SECRET_KEY'));

        if (empty($secretKey)) {
            Log::warning('hCaptcha secret key not configured');
            
            // In development/testing, allow bypass
            if (app()->environment(['local', 'testing'])) {
                return ['valid' => true, 'score' => 1.0];
            }

            return [
                'valid' => false,
                'error' => 'CAPTCHA service not configured.',
            ];
        }

        try {
            $response = Http::asForm()->post(self::HCAPTCHA_VERIFY_URL, [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            $data = $response->json();

            if (!isset($data['success']) || $data['success'] !== true) {
                Log::info('hCaptcha verification failed', [
                    'error_codes' => $data['error-codes'] ?? [],
                ]);

                return [
                    'valid' => false,
                    'error' => 'CAPTCHA verification failed.',
                ];
            }

            return [
                'valid' => true,
                'score' => $data['score'] ?? 1.0,
            ];
        } catch (\Exception $e) {
            Log::error('hCaptcha verification error', ['error' => $e->getMessage()]);

            return [
                'valid' => false,
                'error' => 'CAPTCHA verification error. Please try again.',
            ];
        }
    }

    /**
     * Check if CAPTCHA service is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        $driver = $this->getDriver();

        if ($driver === 'hcaptcha') {
            return !empty(config('services.hcaptcha.site_key', env('HCAPTCHA_SITE_KEY'))) &&
                   !empty(config('services.hcaptcha.secret_key', env('HCAPTCHA_SECRET_KEY')));
        }

        return !empty(config('services.recaptcha.site_key', env('RECAPTCHA_SITE_KEY'))) &&
               !empty(config('services.recaptcha.secret_key', env('RECAPTCHA_SECRET_KEY')));
    }

    /**
     * Get CAPTCHA driver name for frontend.
     *
     * @return string
     */
    public function getDriverName(): string
    {
        return $this->getDriver();
    }

    /**
     * Check if we're in development/testing mode with CAPTCHA bypass allowed.
     *
     * @return bool
     */
    public function isDevelopmentBypassAllowed(): bool
    {
        return app()->environment(['local', 'testing']) && !$this->isConfigured();
    }
}
