<?php

namespace Tests\Unit\Services\GBP;

use App\Services\GBP\GBPApiService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Adversarial/edge case tests for GBPApiService.
 */
class GBPApiServiceEdgeCaseTest extends TestCase
{
    #[Test]
    public function empty_reply_throws_invalid_argument_exception(): void
    {
        $service = new GBPApiService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GBP review reply cannot be empty');

        $service->respondToReview('accounts/123/locations/456', 'review-1', '', 'token');
    }

    #[Test]
    public function whitespace_only_reply_throws_invalid_argument_exception(): void
    {
        $service = new GBPApiService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GBP review reply cannot be empty');

        $service->respondToReview('accounts/123/locations/456', 'review-1', '   ', 'token');
    }

    #[Test]
    public function insights_missing_end_date_throws_invalid_argument_exception(): void
    {
        $service = new GBPApiService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GBP insights date range must include start and end values');

        $service->getInsights('accounts/123/locations/456', 'token', ['start' => '2026-01-01']);
    }

    #[Test]
    public function insights_missing_start_date_throws_invalid_argument_exception(): void
    {
        $service = new GBPApiService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GBP insights date range must include start and end values');

        $service->getInsights('accounts/123/locations/456', 'token', ['end' => '2026-01-07']);
    }

    #[Test]
    public function insights_empty_date_range_throws_invalid_argument_exception(): void
    {
        $service = new GBPApiService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GBP insights date range must include start and end values');

        $service->getInsights('accounts/123/locations/456', 'token', []);
    }

    #[Test]
    public function insights_invalid_location_format_throws_invalid_argument_exception(): void
    {
        $service = new GBPApiService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GBP location name must use accounts/{accountId}/locations/{locationId} format');

        $service->getInsights('invalid-format', 'token', ['start' => '2026-01-01', 'end' => '2026-01-07']);
    }

    #[Test]
    public function insights_partial_location_format_throws_invalid_argument_exception(): void
    {
        $service = new GBPApiService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GBP location name must use accounts/{accountId}/locations/{locationId} format');

        $service->getInsights('accounts/123', 'token', ['start' => '2026-01-01', 'end' => '2026-01-07']);
    }

    #[Test]
    public function api_error_403_throws_runtime_exception_with_message(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts' => Http::response([
                'error' => ['message' => 'Permission denied'],
            ], 403),
        ]);

        $service = new GBPApiService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Permission denied');

        $service->getAccounts('forbidden-token');
    }

    #[Test]
    public function api_error_404_throws_runtime_exception(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts/123/locations/999' => Http::response([
                'error' => ['message' => 'Location not found'],
            ], 404),
        ]);

        $service = new GBPApiService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Location not found');

        $service->getLocation('accounts/123/locations/999', 'token');
    }

    #[Test]
    public function token_refresh_without_refresh_token_throws_runtime_exception(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts' => Http::response([
                'error' => ['message' => 'Expired token'],
            ], 401),
        ]);

        $service = new GBPApiService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GBP API authentication failed and token refresh unavailable');

        $service->getAccounts('expired-token');
    }

    #[Test]
    public function maximum_retries_exceeded_throws_runtime_exception(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts' => Http::sequence()
                ->push(['error' => ['message' => 'Rate limited']], 429)
                ->push(['error' => ['message' => 'Rate limited']], 429)
                ->push(['error' => ['message' => 'Rate limited']], 429)
                ->push(['error' => ['message' => 'Rate limited']], 429),
        ]);

        $service = new class extends GBPApiService {
            protected function pauseMilliseconds(int $milliseconds): void
            {
                // Skip actual delays in tests
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GBP API request failed after maximum retries');

        $service->getAccounts('rate-limited-token');
    }
}
