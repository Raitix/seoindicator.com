<?php

namespace App\Services\Tools;

use App\Services\HtmlParserService;
use App\Services\URLValidationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for SEO analysis tools.
 * 
 * Provides common functionality for:
 * - URL validation and fetching
 * - HTML parsing
 * - Result formatting
 * - Error handling
 * 
 * TASK-16-1: Tools Infrastructure
 */
abstract class SEOTool
{
    protected URLValidationService $urlValidator;
    protected HtmlParserService $htmlParser;

    /**
     * Request timeout in seconds.
     */
    protected int $timeout = 30;

    /**
     * Connection timeout in seconds.
     */
    protected int $connectionTimeout = 10;

    /**
     * Maximum response size (10MB).
     */
    protected int $maxResponseSize = 10485760;

    /**
     * Maximum redirects to follow.
     */
    protected int $maxRedirects = 5;

    /**
     * Tool name for identification.
     */
    protected string $name = 'seo-tool';

    /**
     * Tool description for documentation.
     */
    protected string $description = 'SEO analysis tool';

    public function __construct(
        URLValidationService $urlValidator,
        HtmlParserService $htmlParser
    ) {
        $this->urlValidator = $urlValidator;
        $this->htmlParser = $htmlParser;
    }

    /**
     * Get the tool name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the tool description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Analyze a URL and return tool-specific results.
     * Must be implemented by child classes.
     *
     * @param string $url URL to analyze
     * @param array $options Additional options for the tool
     * @return ToolResult
     */
    abstract public function analyze(string $url, array $options = []): ToolResult;

    /**
     * Fetch and parse a URL for analysis.
     *
     * @param string $url
     * @return array{success: bool, html?: string, parsed?: array, error?: string, finalUrl?: string}
     */
    protected function fetchAndParse(string $url): array
    {
        // Validate URL
        $validation = $this->urlValidator->validate($url);
        
        if (!$validation['valid']) {
            $error = $validation['error'];
            $errorCode = str_contains(strtolower($error), 'security') 
                || str_contains(strtolower($error), 'not allowed')
                ? 'URL_BLOCKED' 
                : 'INVALID_URL';
            
            return [
                'success' => false,
                'error' => $error,
                'errorCode' => $errorCode,
            ];
        }

        $normalizedUrl = $validation['normalized_url'] ?? $url;

        // Fetch URL
        $fetchResult = $this->fetchUrl($normalizedUrl);
        
        if (!$fetchResult['success']) {
            return $fetchResult;
        }

        // Parse HTML
        $parseResult = $this->htmlParser->parse(
            $fetchResult['html'],
            $fetchResult['finalUrl']
        );

        if (!$parseResult['success']) {
            return [
                'success' => false,
                'error' => $parseResult['error'] ?? 'Failed to parse HTML content.',
                'errorCode' => 'PARSE_ERROR',
            ];
        }
        
        // Check for empty or invalid content
        if (empty($fetchResult['html']) || strlen(trim($fetchResult['html'])) < 50) {
            return [
                'success' => false,
                'error' => 'The page returned empty or invalid content.',
                'errorCode' => 'PARSE_ERROR',
            ];
        }

        return [
            'success' => true,
            'html' => $fetchResult['html'],
            'parsed' => $parseResult['data'],
            'finalUrl' => $fetchResult['finalUrl'],
            'statusCode' => $fetchResult['statusCode'],
            'contentType' => $fetchResult['contentType'],
        ];
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
            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectionTimeout)
                ->withHeaders([
                    'User-Agent' => 'SEOIndicator/1.0 (SEO Analysis Tool)',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                ])
                ->maxRedirects($this->maxRedirects)
                ->get($url);
        } catch (ConnectionException $e) {
            $errorMsg = $e->getMessage();
            $isTimeout = str_contains(strtolower($errorMsg), 'timeout');
            
            return [
                'success' => false,
                'error' => $isTimeout 
                    ? 'The request timed out. The server may be slow to respond.' 
                    : 'Connection failed. The server may be unreachable.',
                'errorCode' => $isTimeout ? 'TIMEOUT' : 'FETCH_FAILED',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch the URL: ' . $e->getMessage(),
                'errorCode' => 'FETCH_FAILED',
            ];
        }

        $statusCode = $response->status();
        $finalUrl = $response->effectiveUri() ?? $url;
        $contentType = $response->header('Content-Type', '');

        // Check for non-HTML content
        if (!$this->isHtmlContent($contentType)) {
            return [
                'success' => false,
                'error' => 'The URL does not return HTML content.',
                'errorCode' => 'FETCH_FAILED',
            ];
        }

        // Check response size
        $bodySize = strlen($response->body());
        if ($bodySize > $this->maxResponseSize) {
            return [
                'success' => false,
                'error' => 'The response is too large. Maximum size is 10MB.',
                'errorCode' => 'FETCH_FAILED',
            ];
        }

        // Check for HTTP errors
        if ($statusCode >= 400) {
            return [
                'success' => false,
                'error' => "The server returned HTTP {$statusCode}.",
                'errorCode' => 'FETCH_FAILED',
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
            return true;
        }

        $contentType = strtolower($contentType);
        
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
     * Create a successful result.
     *
     * @param array $data
     * @param string $url
     * @return ToolResult
     */
    protected function success(array $data, string $url): ToolResult
    {
        return new ToolResult(
            success: true,
            toolName: $this->name,
            url: $url,
            data: $data,
            analyzedAt: now()->toISOString()
        );
    }

    /**
     * Create an error result.
     *
     * @param string $error
     * @param string $url
     * @param string|null $code
     * @return ToolResult
     */
    protected function error(string $error, string $url, ?string $code = null): ToolResult
    {
        return new ToolResult(
            success: false,
            toolName: $this->name,
            url: $url,
            error: $error,
            errorCode: $code ?? 'ANALYSIS_ERROR',
            analyzedAt: now()->toISOString()
        );
    }
}
