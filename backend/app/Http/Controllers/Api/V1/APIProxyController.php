<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ProxyAnalyzeRequest;
use App\Models\AnalysisHistory;
use App\Services\AnalysisHistoryService;
use App\Services\CaptchaService;
use App\Services\CostTrackingService;
use App\Services\HtmlParserService;
use App\Services\RateLimitService;
use App\Services\URLValidationService;
use App\Services\Tools\MetaTagAnalyzer;
use App\Services\Tools\KeywordDensityChecker;
use App\Services\Tools\MobileFriendlyChecker;
use App\Services\Tools\SocialMediaPreview;
use App\Services\Tools\ToolResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * API Proxy Controller for TASK-16-1, TASK-16-2, TASK-16-3, TASK-25-14.
 * 
 * Provides a secure proxy endpoint for fetching and analyzing external web pages
 * for SEO tool functionality. Implements SSRF protection, rate limiting, and CAPTCHA.
 * 
 * Supported tools:
 * - meta-tag-analyzer: Analyzes meta tags, title, description, Open Graph, Twitter Cards
 * - keyword-density-checker: Analyzes keyword frequency and density
 * 
 * Analysis History Integration (TASK-25-14):
 * - Every tool execution creates an AnalysisHistory record
 * - Duration tracking in milliseconds
 * - Cost tracking triggered for successful analyses
 */
class APIProxyController extends Controller
{
    protected URLValidationService $urlValidator;
    protected HtmlParserService $htmlParser;
    protected RateLimitService $rateLimiter;
    protected CaptchaService $captcha;
    protected MetaTagAnalyzer $metaTagAnalyzer;
    protected KeywordDensityChecker $keywordDensityChecker;
    protected MobileFriendlyChecker $mobileFriendlyChecker;
    protected SocialMediaPreview $socialMediaPreview;
    protected AnalysisHistoryService $analysisHistoryService;
    protected CostTrackingService $costTrackingService;

    public function __construct(
        URLValidationService $urlValidator,
        HtmlParserService $htmlParser,
        RateLimitService $rateLimiter,
        CaptchaService $captcha,
        MetaTagAnalyzer $metaTagAnalyzer,
        KeywordDensityChecker $keywordDensityChecker,
        MobileFriendlyChecker $mobileFriendlyChecker,
        SocialMediaPreview $socialMediaPreview,
        AnalysisHistoryService $analysisHistoryService,
        CostTrackingService $costTrackingService
    ) {
        $this->urlValidator = $urlValidator;
        $this->htmlParser = $htmlParser;
        $this->rateLimiter = $rateLimiter;
        $this->captcha = $captcha;
        $this->metaTagAnalyzer = $metaTagAnalyzer;
        $this->keywordDensityChecker = $keywordDensityChecker;
        $this->mobileFriendlyChecker = $mobileFriendlyChecker;
        $this->socialMediaPreview = $socialMediaPreview;
        $this->analysisHistoryService = $analysisHistoryService;
        $this->costTrackingService = $costTrackingService;
    }

    /**
     * Generate a unique request ID for error tracking.
     */
    protected function generateRequestId(): string
    {
        return 'req_' . Str::lower(Str::random(16));
    }

    /**
     * Create an error response with proper format.
     */
    protected function errorResponse(
        string $code,
        string $message,
        int $status,
        ?string $requestId = null,
        array $additional = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'request_id' => $requestId ?? $this->generateRequestId(),
            'error' => array_merge([
                'code' => $code,
                'message' => $message,
            ], $additional),
        ];

        $headers = ['X-Error-Code' => $code];
        
        return response()->json($response, $status)->withHeaders($headers);
    }

    /**
     * Analyze a URL and return SEO metadata.
     *
     * @param ProxyAnalyzeRequest $request
     * @return JsonResponse
     */
    public function analyze(ProxyAnalyzeRequest $request): JsonResponse
    {
        $startTime = microtime(true);
        $requestId = $this->generateRequestId();
        $url = $request->input('url');
        $tool = $request->input('tool', 'meta-tag-analyzer');
        $keyword = $request->input('keyword');
        $siteId = $request->input('site_id');
        $captchaToken = $request->input('captchaToken');
        $clientIp = $this->rateLimiter->getClientIp($request);

        // Step 1: URL Validation
        $validation = $this->urlValidator->validate($url);
        
        if (!$validation['valid']) {
            $errorCode = str_contains(strtolower($validation['error'] ?? ''), 'security') 
                || str_contains(strtolower($validation['error'] ?? ''), 'not allowed')
                ? 'URL_BLOCKED' 
                : 'INVALID_URL';
            
            $status = $errorCode === 'URL_BLOCKED' ? 403 : 400;
            
            Log::warning('Tools API: URL validation failed', [
                'request_id' => $requestId,
                'url' => $url,
                'error' => $validation['error'],
                'code' => $errorCode,
            ]);

            // Create failed analysis history for blocked URLs (SSRF protection)
            if ($errorCode === 'URL_BLOCKED') {
                $this->analysisHistoryService->createAnalysis([
                    'user_id' => auth()->id(),
                    'site_id' => $siteId,
                    'tool_name' => $tool,
                    'input_url' => $url,
                    'status' => 'failed',
                    'error_message' => $validation['error'],
                    'analysis_duration_ms' => $this->analysisHistoryService->calculateDuration($startTime),
                ]);
            }
            
            return $this->errorResponse(
                $errorCode,
                $validation['error'],
                $status,
                $requestId,
                ['suggestion' => $this->getSuggestion($errorCode)]
            );
        }

        // Step 2: Rate Limiting Check
        // Note: Rate limited requests do NOT create history records (AH-015)
        $rateLimit = $this->rateLimiter->check($clientIp);

        if (!$rateLimit['allowed']) {
            if ($this->rateLimiter->hasCaptchaBypass($clientIp)) {
                // Continue with request
            } elseif ($captchaToken) {
                $captchaResult = $this->captcha->verify($captchaToken, $clientIp);
                
                if ($captchaResult['valid']) {
                    $this->rateLimiter->verifyCaptcha($clientIp, $captchaToken);
                } else {
                    return $this->errorResponse(
                        'CAPTCHA_FAILED',
                        $captchaResult['error'] ?? 'CAPTCHA verification failed.',
                        403,
                        $requestId
                    )->withHeaders($this->rateLimiter->getHeaders($clientIp));
                }
            } else {
                Log::info('Tools API: Rate limit exceeded', [
                    'request_id' => $requestId,
                    'ip' => $clientIp,
                ]);
                
                return response()->json([
                    'success' => false,
                    'request_id' => $requestId,
                    'error' => [
                        'code' => 'RATE_LIMITED',
                        'message' => 'You have exceeded the rate limit. Please wait before trying again.',
                        'captchaRequired' => true,
                        'captchaSiteKey' => $this->captcha->getSiteKey(),
                        'captchaDriver' => $this->captcha->getDriverName(),
                    ],
                ], 429)->withHeaders(array_merge(
                    $this->rateLimiter->getHeaders($clientIp),
                    ['X-Error-Code' => 'RATE_LIMITED']
                ));
            }
        }

        // Increment rate limit counter
        $this->rateLimiter->increment($clientIp);

        // Step 3: Run the tool
        try {
            $result = match ($tool) {
                'keyword-density-checker' => $this->keywordDensityChecker->analyze($url, ['keyword' => $keyword]),
                'mobile-friendly' => $this->mobileFriendlyChecker->analyze($url),
                'social-media-preview' => $this->socialMediaPreview->analyze($url),
                default => $this->metaTagAnalyzer->analyze($url),
            };

            if (!$result->success) {
                // Create failed analysis history
                $duration = $this->analysisHistoryService->calculateDuration($startTime);
                $analysis = $this->analysisHistoryService->createAnalysis([
                    'user_id' => auth()->id(),
                    'site_id' => $siteId,
                    'tool_name' => $tool,
                    'input_url' => $url,
                    'status' => 'failed',
                    'error_message' => $result->error ?? 'Analysis failed',
                    'analysis_duration_ms' => $duration,
                ]);

                return $this->handleToolError($result, $requestId, $url, $analysis->id);
            }

            // Create successful analysis history
            $duration = $this->analysisHistoryService->calculateDuration($startTime);
            $analysis = $this->analysisHistoryService->createAnalysis([
                'user_id' => auth()->id(),
                'site_id' => $siteId,
                'tool_name' => $tool,
                'input_url' => $url,
                'status' => 'completed',
                'results' => $result->toArray(),
                'analysis_duration_ms' => $duration,
            ]);

            // Trigger cost tracking for successful analysis
            $this->costTrackingService->recordCost($analysis, ['units' => 1]);

            return response()->json(array_merge(
                $result->toArray(),
                [
                    'request_id' => $requestId,
                    'analysis_id' => $analysis->id,
                    'analysis_duration_ms' => $duration,
                ]
            ))->withHeaders($this->rateLimiter->getHeaders($clientIp));
            
        } catch (ConnectionException $e) {
            $duration = $this->analysisHistoryService->calculateDuration($startTime);
            $isTimeout = str_contains(strtolower($e->getMessage()), 'timeout');
            
            // Create failed analysis history for connection errors
            $analysis = $this->analysisHistoryService->createAnalysis([
                'user_id' => auth()->id(),
                'site_id' => $siteId,
                'tool_name' => $tool,
                'input_url' => $url,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'analysis_duration_ms' => $duration,
            ]);

            Log::error('Tools API: Connection error', [
                'request_id' => $requestId,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            
            return $this->errorResponse(
                $isTimeout ? 'TIMEOUT' : 'FETCH_FAILED',
                $isTimeout 
                    ? 'The request timed out. The server may be slow to respond.' 
                    : 'Failed to fetch the URL. The server may be unreachable.',
                $isTimeout ? 504 : 502,
                $requestId,
                [
                    'suggestion' => $this->getSuggestion($isTimeout ? 'TIMEOUT' : 'FETCH_FAILED'),
                    'analysis_id' => $analysis->id,
                ]
            );
        } catch (\Exception $e) {
            $duration = $this->analysisHistoryService->calculateDuration($startTime);
            
            // Create failed analysis history for unexpected errors
            $analysis = $this->analysisHistoryService->createAnalysis([
                'user_id' => auth()->id(),
                'site_id' => $siteId,
                'tool_name' => $tool,
                'input_url' => $url,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'analysis_duration_ms' => $duration,
            ]);

            Log::error('Tools API: Unexpected error', [
                'request_id' => $requestId,
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
            
            return $this->errorResponse(
                'TOOL_ERROR',
                'An unexpected error occurred during analysis.',
                500,
                $requestId,
                array_merge(
                    ['analysis_id' => $analysis->id],
                    config('app.debug') ? ['exception' => $e->getMessage()] : []
                )
            );
        }
    }

    /**
     * Handle tool-specific errors with appropriate status codes.
     */
    protected function handleToolError(ToolResult $result, string $requestId, string $url, ?int $analysisId = null): JsonResponse
    {
        $errorCode = $result->errorCode ?? 'TOOL_ERROR';
        $error = $result->error ?? 'An error occurred during analysis.';
        
        // Map error codes to HTTP status codes
        $statusMap = [
            'INVALID_URL' => 400,
            'URL_BLOCKED' => 403,
            'FETCH_ERROR' => 502,
            'FETCH_FAILED' => 502,
            'TIMEOUT' => 504,
            'PARSE_ERROR' => 500,
            'MISSING_KEYWORD' => 400,
        ];
        
        // Detect specific error types from the error message
        $errorLower = strtolower($error);
        
        if (str_contains($errorLower, 'timeout') || str_contains($errorLower, 'timed out')) {
            $errorCode = 'TIMEOUT';
            $status = 504;
        } elseif (str_contains($errorLower, 'connection') || str_contains($errorLower, 'unreachable')) {
            $errorCode = 'FETCH_FAILED';
            $status = 502;
        } elseif (str_contains($errorLower, 'parse') || str_contains($errorLower, 'html')) {
            $errorCode = 'PARSE_ERROR';
            $status = 500;
        } else {
            $status = $statusMap[$errorCode] ?? 500;
        }
        
        Log::warning('Tools API: Tool error', [
            'request_id' => $requestId,
            'url' => $url,
            'error' => $error,
            'code' => $errorCode,
            'status' => $status,
            'analysis_id' => $analysisId,
        ]);
        
        $additionalData = ['suggestion' => $this->getSuggestion($errorCode)];
        if ($analysisId) {
            $additionalData['analysis_id'] = $analysisId;
        }
        
        return $this->errorResponse(
            $errorCode,
            $error,
            $status,
            $requestId,
            $additionalData
        );
    }

    /**
     * Get a suggestion for error recovery.
     */
    protected function getSuggestion(string $errorCode): string
    {
        return match ($errorCode) {
            'INVALID_URL' => 'Please enter a valid URL (e.g., https://example.com).',
            'URL_BLOCKED' => 'This URL cannot be analyzed for security reasons.',
            'FETCH_FAILED' => 'The server may be down or unreachable. Please try again later.',
            'TIMEOUT' => 'The server took too long to respond. Please try again.',
            'RATE_LIMITED' => 'Please wait a few minutes before making more requests.',
            'PARSE_ERROR' => 'The page content could not be parsed. It may have invalid HTML.',
            'MISSING_KEYWORD' => 'Please provide a keyword to analyze.',
            default => 'Please try again or contact support if the problem persists.',
        };
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
                    [
                        'id' => 'social-media-preview',
                        'name' => 'Social Media Preview',
                        'description' => 'Analyzes Open Graph and Twitter Card tags and generates social media share previews for Facebook, Twitter, and LinkedIn.',
                        'requiresKeyword' => false,
                    ],
                ],
            ],
        ]);
    }
}
