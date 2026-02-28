<?php

namespace App\Services\SERP;

use App\Services\SERPFetcherInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Google Custom Search API SERP Fetcher.
 *
 * Uses Google Custom Search JSON API (free tier: 100 queries/day).
 * Returns up to 10 results per query due to API limitations.
 *
 * @see https://developers.google.com/custom-search/v1/introduction
 */
class GoogleCSEFetcher implements SERPFetcherInterface
{
    /**
     * Google Custom Search API base URL.
     */
    private const API_URL = 'https://www.googleapis.com/customsearch/v1';

    /**
     * Maximum results per query (API limitation).
     */
    private const MAX_RESULTS = 10;

    /**
     * Error type for quota exceeded.
     */
    public const ERROR_QUOTA_EXCEEDED = 'quota_exceeded';

    /**
     * Error type for rate limited.
     */
    public const ERROR_RATE_LIMITED = 'rate_limited';

    /**
     * Error type for invalid credentials.
     */
    public const ERROR_INVALID_CREDENTIALS = 'invalid_credentials';

    /**
     * Error type for API error.
     */
    public const ERROR_API_ERROR = 'api_error';

    /**
     * Create a new GoogleCSEFetcher instance.
     *
     * @param string $apiKey Google API key
     * @param string $searchEngineId Custom Search Engine ID (cx parameter)
     * @param MockSERPFetcher|null $mockFallback Fallback mock fetcher (optional)
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $searchEngineId,
        private readonly ?MockSERPFetcher $mockFallback = null
    ) {
    }

    /**
     * Fetch SERP results for a keyword.
     *
     * @param string $keyword Search keyword
     * @param SERPOptions $options Search options
     * @return SERPResult Search results
     */
    public function fetch(string $keyword, SERPOptions $options): SERPResult
    {
        $start = microtime(true);

        // Check if credentials are configured
        if (empty($this->apiKey) || empty($this->searchEngineId)) {
            return $this->handleMissingCredentials($keyword, $options, $start);
        }

        try {
            $response = $this->makeApiRequest($keyword, $options);
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            if ($response->failed()) {
                return $this->handleApiError($keyword, $options, $response, $durationMs);
            }

            $this->logUsage($keyword, $durationMs, true);

            return $this->buildSuccessResult($keyword, $options, $response->json(), $durationMs);

        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $this->logUsage($keyword, $durationMs, false, $e->getMessage());

            return $this->buildErrorResult(
                $keyword,
                $options,
                self::ERROR_API_ERROR . ': ' . $e->getMessage(),
                $durationMs
            );
        }
    }

    /**
     * Make the API request to Google Custom Search.
     */
    private function makeApiRequest(string $keyword, SERPOptions $options): \Illuminate\Http\Client\Response
    {
        $params = [
            'key' => $this->apiKey,
            'cx' => $this->searchEngineId,
            'q' => $keyword,
            'num' => self::MAX_RESULTS,
        ];

        // Add optional parameters
        if ($options->getLocation() !== 'US') {
            // Google CSE uses 'gl' for geolocation (country code)
            $params['gl'] = strtoupper(substr($options->getLocation(), 0, 2));
        }

        if ($options->getDevice() === 'mobile') {
            // Use mobile interface
            $params['c2coff'] = '1';
        }

        return Http::timeout(30)
            ->retry(2, 1000, throw: false)
            ->get(self::API_URL, $params);
    }

    /**
     * Build a successful result from API response.
     */
    private function buildSuccessResult(
        string $keyword,
        SERPOptions $options,
        array $data,
        int $durationMs
    ): SERPResult {
        $results = [];
        $targetUrl = $options->getTargetUrl();
        $items = $data['items'] ?? [];

        foreach ($items as $index => $item) {
            $url = $item['link'] ?? '';
            if (empty($url)) {
                continue;
            }

            $position = $index + 1;
            $isOurSite = $targetUrl ? $this->isTargetMatch($targetUrl, $url) : false;

            $results[] = new SERPEntryData(
                $position,
                $url,
                $item['title'] ?? '',
                $item['snippet'] ?? '',
                $isOurSite
            );
        }

        $checkedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM);

        return new SERPResultData(
            $keyword,
            $options->getSearchEngine(),
            $options->getLocation(),
            $options->getDevice(),
            $results,
            $checkedAt,
            null,
            $durationMs,
            $this->extractSerpFeatures($data)
        );
    }

    /**
     * Handle API error responses.
     */
    private function handleApiError(
        string $keyword,
        SERPOptions $options,
        \Illuminate\Http\Client\Response $response,
        int $durationMs
    ): SERPResult {
        $statusCode = $response->status();
        $errorData = $response->json();
        
        // Extract error message from Google API response format
        $errorMessage = $errorData['error']['message'] 
            ?? $errorData['error']['errors'][0]['message'] 
            ?? $errorData['error'] 
            ?? "HTTP {$statusCode}";

        // Determine error type
        $errorMessageLower = strtolower($errorMessage);
        $errorType = match (true) {
            $statusCode === 429 => self::ERROR_RATE_LIMITED,
            $statusCode === 403 && str_contains($errorMessageLower, 'quota') => self::ERROR_QUOTA_EXCEEDED,
            $statusCode === 400 && str_contains($errorMessageLower, 'api key') => self::ERROR_INVALID_CREDENTIALS,
            default => self::ERROR_API_ERROR,
        };

        $this->logUsage($keyword, $durationMs, false, "{$errorType}: {$errorMessage}");

        // Fall back to mock if configured
        if ($this->mockFallback && in_array($errorType, [self::ERROR_QUOTA_EXCEEDED, self::ERROR_RATE_LIMITED])) {
            Log::info('Google CSE: Falling back to mock fetcher', [
                'keyword' => $keyword,
                'error_type' => $errorType,
            ]);

            return $this->mockFallback->fetch($keyword, $options);
        }

        return $this->buildErrorResult($keyword, $options, "{$errorType}: {$errorMessage}", $durationMs);
    }

    /**
     * Handle missing credentials.
     */
    private function handleMissingCredentials(
        string $keyword,
        SERPOptions $options,
        float $start
    ): SERPResult {
        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $error = self::ERROR_INVALID_CREDENTIALS . ': API key or Search Engine ID not configured';

        $this->logUsage($keyword, $durationMs, false, $error);

        // Fall back to mock if configured
        if ($this->mockFallback) {
            Log::info('Google CSE: Falling back to mock fetcher due to missing credentials');
            return $this->mockFallback->fetch($keyword, $options);
        }

        return $this->buildErrorResult($keyword, $options, $error, $durationMs);
    }

    /**
     * Build an error result.
     */
    private function buildErrorResult(
        string $keyword,
        SERPOptions $options,
        string $error,
        int $durationMs
    ): SERPResult {
        $checkedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM);

        return new SERPResultData(
            $keyword,
            $options->getSearchEngine(),
            $options->getLocation(),
            $options->getDevice(),
            [],
            $checkedAt,
            $error,
            $durationMs
        );
    }

    /**
     * Check if a result URL matches the target URL.
     */
    private function isTargetMatch(string $targetUrl, string $resultUrl): bool
    {
        if (strcasecmp($targetUrl, $resultUrl) === 0) {
            return true;
        }

        $targetHost = parse_url($targetUrl, PHP_URL_HOST);
        $resultHost = parse_url($resultUrl, PHP_URL_HOST);

        if (!$targetHost || !$resultHost) {
            return false;
        }

        return strcasecmp($targetHost, $resultHost) === 0;
    }

    /**
     * Extract SERP features from API response.
     *
     * @return array<string>|null
     */
    private function extractSerpFeatures(array $data): ?array
    {
        $features = [];

        // Check for various SERP features in the response
        if (isset($data['queries']['nextPage'])) {
            $features[] = 'pagination';
        }

        if (isset($data['spelling'])) {
            $features[] = 'did_you_mean';
        }

        // Check for featured snippets (pagemap contains structured data)
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                if (isset($item['pagemap']['metatags'])) {
                    $features[] = 'structured_data';
                    break;
                }
            }
        }

        // Check for images
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                if (isset($item['pagemap']['cse_image'])) {
                    $features[] = 'images';
                    break;
                }
            }
        }

        return count($features) > 0 ? array_unique($features) : null;
    }

    /**
     * Log API usage for quota monitoring.
     */
    private function logUsage(string $keyword, int $durationMs, bool $success, ?string $error = null): void
    {
        $context = [
            'keyword' => $keyword,
            'duration_ms' => $durationMs,
            'success' => $success,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($error) {
            $context['error'] = $error;
        }

        Log::channel('daily')->info('Google CSE API Request', $context);
    }
}
