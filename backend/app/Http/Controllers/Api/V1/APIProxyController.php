<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ProxyAnalyzeRequest;
use App\Services\CaptchaService;
use App\Services\HtmlParserService;
use App\Services\RateLimitService;
use App\Services\URLValidationService;
use App\Services\Tools\MetaTagAnalyzer;
use App\Services\Tools\KeywordDensityChecker;
use App\Services\Tools\ToolResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API Proxy Controller for TASK-16-1, TASK-16-2, TASK-16-3.
 * 
 * Provides a secure proxy endpoint for fetching and analyzing external web pages
 * for SEO tool functionality. Implements SSRF protection, rate limiting, and CAPTCHA.
 * 
 * Supported tools:
 * - meta-tag-analyzer: Analyzes meta tags, title, description, Open Graph, Twitter Cards
 * - keyword-density-checker: Analyzes keyword frequency and density
 */
class APIProxyController extends Controller
{
    protected URLValidationService $urlValidator;
    protected HtmlParserService $htmlParser;
    protected RateLimitService $rateLimiter;
    protected CaptchaService $captcha;
    protected MetaTagAnalyzer $metaTagAnalyzer;
    protected KeywordDensityChecker $keywordDensityChecker;

    public function __construct(
        URLValidationService $urlValidator,
        HtmlParserService $htmlParser,
        RateLimitService $rateLimiter,
        CaptchaService $captcha,
        MetaTagAnalyzer $metaTagAnalyzer,
        KeywordDensityChecker $keywordDensityChecker
    ) {
        $this->urlValidator = $urlValidator;
        $this->htmlParser = $htmlParser;
        $this->rateLimiter = $rateLimiter;
        $this->captcha = $captcha;
        $this->metaTagAnalyzer = $metaTagAnalyzer;
        $this->keywordDensityChecker = $keywordDensityChecker;
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
        $tool = $request->input('tool', 'meta-tag-analyzer');
        $keyword = $request->input('keyword');
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

        // Increment rate limit counter
        $this->rateLimiter->increment($clientIp);

        // Run the appropriate tool
        $result = match ($tool) {
            'keyword-density-checker' => $this->keywordDensityChecker->analyze($url, ['keyword' => $keyword]),
            default => $this->metaTagAnalyzer->analyze($url),
        };

        // Return response with rate limit headers
        return response()->json($result->toArray())
            ->withHeaders($this->rateLimiter->getHeaders($clientIp));
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

    /**
     * Get available tools and their descriptions.
     *
     * @return JsonResponse
     */
    public function tools(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'tools' => [
                    [
                        'id' => 'meta-tag-analyzer',
                        'name' => 'Meta Tag Analyzer',
                        'description' => 'Analyzes meta tags including title, description, Open Graph, and Twitter Card tags.',
                        'requiresKeyword' => false,
                    ],
                    [
                        'id' => 'keyword-density-checker',
                        'name' => 'Keyword Density Checker',
                        'description' => 'Analyzes keyword frequency and density in page content.',
                        'requiresKeyword' => true,
                    ],
                ],
            ],
        ]);
    }
}
