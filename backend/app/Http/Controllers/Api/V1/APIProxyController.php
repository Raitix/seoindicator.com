<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ProxyAnalyzeRequest;
use App\Services\CaptchaService;
use App\Services\HtmlParserService;
use App\Services\RateLimitService;
use App\Services\URLValidationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * API Proxy Controller for TASK-16-3.
 * 
 * Provides a secure proxy endpoint for fetching and analyzing external web pages
 * for SEO tool functionality. Implements SSRF protection, rate limiting, and CAPTCHA.
 */
class APIProxyController extends Controller
{
    protected URLValidationService $urlValidator;
    protected HtmlParserService $htmlParser;
    protected RateLimitService $rateLimiter;
    protected CaptchaService $captcha;

    /**
     * Request timeout in seconds.
     */
    private const REQUEST_TIMEOUT = 30;

    /**
     * Connection timeout in seconds.
     */
    private const CONNECTION_TIMEOUT = 10;

    /**
     * Maximum response size (10MB).
     */
    private const MAX_RESPONSE_SIZE = 10485760;

    /**
     * Maximum redirects to follow.
     */
    private const MAX_REDIRECTS = 5;

    public function __construct(
        URLValidationService $urlValidator,
        HtmlParserService $htmlParser,
        RateLimitService $rateLimiter,
        CaptchaService $captcha
    ) {
        $this->urlValidator = $urlValidator;
        $this->htmlParser = $htmlParser;
        $this->rateLimiter = $rateLimiter;
        $this->captcha = $captcha;
    }

    /**
     * Analyze a URL and return SEO metadata.
     *
     * @param ProxyAnalyzeRequest $request
     * @return JsonResponse
     */
    public function analyze(ProxyAnalyzeRequest $request): JsonResponse
    {
        $url = $request->input('url');
        $captchaToken = $request->input('captchaToken');
        $clientIp = $this->rateLimiter->getClientIp($request);

        // Check rate limiting first
        $rateLimit = $this->rateLimiter->check($clientIp);

        // If rate limited, check for CAPTCHA bypass or token
        if (!$rateLimit['allowed']) {
            // Check for valid CAPTCHA bypass
            if ($this->rateLimiter->hasCaptchaBypass($clientIp)) {
                // Continue with request
            } elseif ($captchaToken) {
                // Verify CAPTCHA
                $captchaResult = $this->captcha->verify($captchaToken, $clientIp);
                
                if ($captchaResult['valid']) {
                    // Reset rate limit after successful CAPTCHA
                    $this->rateLimiter->verifyCaptcha($clientIp, $captchaToken);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'CAPTCHA_FAILED',
                            'message' => $captchaResult['error'] ?? 'CAPTCHA verification failed.',
                        ],
                    ], 403);
                }
            } else {
                // Return rate limit exceeded with CAPTCHA required
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'message' => 'You have exceeded the rate limit. Please complete the CAPTCHA to continue.',
                        'captchaRequired' => true,
                        'captchaSiteKey' => $this->captcha->getSiteKey(),
                        'captchaDriver' => $this->captcha->getDriverName(),
                    ],
                ], 429)->withHeaders($this->rateLimiter->getHeaders($clientIp));
            }
        }

        // Validate URL for SSRF protection
        $validation = $this->urlValidator->validate($url);
        
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN_URL',
                    'message' => $validation['error'],
                ],
            ], 403);
        }

        // Increment rate limit counter
        $this->rateLimiter->increment($clientIp);

        // Fetch and analyze the URL
        try {
            $fetchResult = $this->fetchUrl($validation['normalized_url'] ?? $url);
        } catch (\Exception $e) {
            Log::error('Failed to fetch URL', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_ERROR',
                    'message' => 'Unable to fetch the page. The server may be down or the URL may be incorrect.',
                    'details' => app()->environment('local') ? $e->getMessage() : null,
                ],
            ], 502);
        }

        // Check if fetch was successful
        if (!$fetchResult['success']) {
            return response()->json($fetchResult, $fetchResult['status'] ?? 502);
        }

        // Parse HTML content
        $parseResult = $this->htmlParser->parse(
            $fetchResult['html'],
            $fetchResult['finalUrl']
        );

        if (!$parseResult['success']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PARSE_ERROR',
                    'message' => $parseResult['error'] ?? 'Failed to parse HTML content.',
                ],
            ], 500);
        }

        // Combine fetch and parse results
        $response = [
            'success' => true,
            'data' => array_merge($parseResult['data'], [
                'url' => $url,
                'finalUrl' => $fetchResult['finalUrl'],
                'statusCode' => $fetchResult['statusCode'],
                'contentType' => $fetchResult['contentType'],
                'analyzedAt' => now()->toISOString(),
            ]),
        ];

        return response()->json($response)
            ->withHeaders($this->rateLimiter->getHeaders($clientIp));
    }

    /**
     * Fetch URL content with security measures.
     *
     * @param string $url
     * @return array
     */
    protected function fetchUrl(string $url): array
    {
        try {
            $response = Http::timeout(self::REQUEST_TIMEOUT)
                ->connectTimeout(self::CONNECTION_TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'SEOIndicator/1.0 (SEO Analysis Tool)',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                ])
                ->maxRedirects(self::MAX_REDIRECTS)
                ->get($url);
        } catch (ConnectionException $e) {
            return [
                'success' => false,
                'status' => 504,
                'error' => [
                    'code' => 'CONNECTION_ERROR',
                    'message' => 'Connection timed out or failed. The server may be unreachable.',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 502,
                'error' => [
                    'code' => 'FETCH_ERROR',
                    'message' => 'Failed to fetch the URL: ' . $e->getMessage(),
                ],
            ];
        }

        $statusCode = $response->status();
        $finalUrl = $response->effectiveUri() ?? $url;
        $contentType = $response->header('Content-Type', '');

        // Check for non-HTML content
        if (!$this->isHtmlContent($contentType)) {
            return [
                'success' => false,
                'status' => 415,
                'error' => [
                    'code' => 'INVALID_CONTENT_TYPE',
                    'message' => 'The URL does not return HTML content.',
                    'details' => [
                        'contentType' => $contentType,
                    ],
                ],
            ];
        }

        // Check response size
        $bodySize = strlen($response->body());
        if ($bodySize > self::MAX_RESPONSE_SIZE) {
            return [
                'success' => false,
                'status' => 413,
                'error' => [
                    'code' => 'RESPONSE_TOO_LARGE',
                    'message' => 'The response is too large. Maximum size is 10MB.',
                ],
            ];
        }

        // Check for HTTP errors
        if ($statusCode >= 400) {
            return [
                'success' => false,
                'status' => $statusCode >= 500 ? 502 : $statusCode,
                'error' => [
                    'code' => 'HTTP_ERROR',
                    'message' => "The server returned HTTP {$statusCode}.",
                    'details' => [
                        'statusCode' => $statusCode,
                    ],
                ],
            ];
        }

        return [
            'success' => true,
            'html' => $response->body(),
            'finalUrl' => (string) $finalUrl,
            'statusCode' => $statusCode,
            'contentType' => $contentType,
        ];
    }

    /**
     * Check if content type indicates HTML.
     *
     * @param string $contentType
     * @return bool
     */
    protected function isHtmlContent(string $contentType): bool
    {
        if (empty($contentType)) {
            // Assume HTML if no content type header
            return true;
        }

        $contentType = strtolower($contentType);
        
        // Check for HTML content types
        $htmlTypes = [
            'text/html',
            'application/xhtml+xml',
            'application/xml',
            'text/xml',
        ];

        foreach ($htmlTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get CAPTCHA configuration for frontend.
     *
     * @return JsonResponse
     */
    public function captchaConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'driver' => $this->captcha->getDriverName(),
                'siteKey' => $this->captcha->getSiteKey(),
                'isRequired' => true,
            ],
        ]);
    }

    /**
     * Get rate limit status for current IP.
     *
     * @return JsonResponse
     */
    public function rateLimitStatus(): JsonResponse
    {
        $clientIp = $this->rateLimiter->getClientIp(request());
        $headers = $this->rateLimiter->getHeaders($clientIp);
        $currentCount = $this->rateLimiter->getCurrentCount($clientIp);

        return response()->json([
            'success' => true,
            'data' => [
                'limit' => $this->rateLimiter->getMaxRequests(),
                'remaining' => max(0, $this->rateLimiter->getMaxRequests() - $currentCount),
                'current' => $currentCount,
                'resetAt' => $headers['X-RateLimit-Reset'] ?? null,
            ],
        ])->withHeaders($headers);
    }
}
