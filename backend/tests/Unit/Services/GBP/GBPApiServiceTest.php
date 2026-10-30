<?php

namespace Tests\Unit\Services\GBP;

use App\Services\GBP\Contracts\GBPApiServiceInterface;
use App\Services\GBP\GBPApiService;
use App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GBPApiServiceTest extends TestCase
{
    protected MockInterface $oauthService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.google.business_profile_base_url', 'https://mybusiness.googleapis.com/v4');
        $this->oauthService = Mockery::mock(GoogleOAuthServiceInterface::class);
    }

    protected function tearDown(): void
    {
        Http::preventStrayRequests();
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function gbp_api_cls_001_service_class_exists(): void
    {
        $this->assertTrue(class_exists(GBPApiService::class));
    }

    #[Test]
    public function gbp_api_cls_002_service_implements_interface(): void
    {
        $service = new GBPApiService($this->oauthService);

        $this->assertInstanceOf(GBPApiServiceInterface::class, $service);
    }

    #[Test]
    public function gbp_api_cls_003_interface_exposes_all_required_methods(): void
    {
        foreach (['getAccounts', 'getLocations', 'getLocation', 'getInsights', 'getReviews', 'respondToReview'] as $method) {
            $this->assertTrue(method_exists(GBPApiServiceInterface::class, $method), "Missing interface method: {$method}");
        }
    }

    #[Test]
    public function gbp_api_cls_004_container_resolves_contract_binding(): void
    {
        $service = $this->app->make(GBPApiServiceInterface::class);

        $this->assertInstanceOf(GBPApiService::class, $service);
    }

    #[Test]
    public function gbp_api_cfg_001_defaults_to_v4_base_url(): void
    {
        $service = $this->makeService();

        $this->assertSame('https://mybusiness.googleapis.com/v4', $service->inspectBaseUrl());
    }

    #[Test]
    public function gbp_api_cfg_002_normalizes_numeric_account_ids_to_google_resource_names(): void
    {
        $service = $this->makeService();

        $this->assertSame('accounts/123456789', $service->inspectNormalizeAccountName('123456789'));
        $this->assertSame('accounts/987654321', $service->inspectNormalizeAccountName('accounts/987654321'));
    }

    #[Test]
    public function gbp_api_acc_001_get_accounts_calls_accounts_endpoint_with_bearer_token_and_returns_accounts_array(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts' => Http::response([
                'accounts' => [
                    [
                        'name' => 'accounts/123',
                        'accountName' => 'Primary Agency',
                        'type' => 'PERSONAL',
                    ],
                ],
            ], 200),
        ]);

        $service = $this->makeService();
        $accounts = $service->getAccounts('access-token-123');

        $this->assertSame('accounts/123', $accounts[0]['name'] ?? null);
        $this->assertSame('Primary Agency', $accounts[0]['accountName'] ?? null);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://mybusiness.googleapis.com/v4/accounts'
                && $request->method() === 'GET'
                && $request->hasHeader('Authorization', 'Bearer access-token-123');
        });
    }

    #[Test]
    public function gbp_api_loc_001_get_locations_calls_account_locations_endpoint_and_normalizes_place_id_name_and_address(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts/123/locations' => Http::response([
                'locations' => [
                    [
                        'name' => 'accounts/123/locations/456',
                        'title' => 'SEOIndicator Riga',
                        'locationKey' => ['placeId' => 'place-123'],
                        'storefrontAddress' => [
                            'addressLines' => ['Brivibas iela 1'],
                            'locality' => 'Riga',
                            'postalCode' => 'LV-1010',
                            'regionCode' => 'LV',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = $this->makeService();
        $locations = $service->getLocations('123', 'access-token-xyz');

        $this->assertSame('place-123', $locations[0]['place_id'] ?? null);
        $this->assertSame('SEOIndicator Riga', $locations[0]['name'] ?? null);
        $this->assertStringContainsString('Brivibas iela 1', $locations[0]['address'] ?? '');

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://mybusiness.googleapis.com/v4/accounts/123/locations'
                && $request->method() === 'GET';
        });
    }

    #[Test]
    public function gbp_api_loc_002_get_location_fetches_single_location_resource(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts/123/locations/456' => Http::response([
                'name' => 'accounts/123/locations/456',
                'title' => 'SEOIndicator Riga',
                'primaryPhone' => '+37100000000',
            ], 200),
        ]);

        $service = $this->makeService();
        $location = $service->getLocation('accounts/123/locations/456', 'token-123');

        $this->assertSame('accounts/123/locations/456', $location['name'] ?? null);
        $this->assertSame('SEOIndicator Riga', $location['title'] ?? null);
    }

    #[Test]
    public function gbp_api_ins_001_get_insights_posts_report_insights_request_for_the_parent_account_and_requested_location(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts/123/locations:reportInsights' => Http::response([
                'locationMetrics' => [
                    [
                        'locationName' => 'accounts/123/locations/456',
                        'metricValues' => [],
                    ],
                ],
            ], 200),
        ]);

        $service = $this->makeService();
        $insights = $service->getInsights('accounts/123/locations/456', 'token-123', [
            'start' => '2026-03-01',
            'end' => '2026-03-07',
        ]);

        $this->assertArrayHasKey('locationMetrics', $insights);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return $request->url() === 'https://mybusiness.googleapis.com/v4/accounts/123/locations:reportInsights'
                && $request->method() === 'POST'
                && ($data['locationNames'][0] ?? null) === 'accounts/123/locations/456'
                && ($data['basicRequest']['timeRange']['startTime'] ?? null) === '2026-03-01'
                && ($data['basicRequest']['timeRange']['endTime'] ?? null) === '2026-03-07';
        });
    }

    #[Test]
    public function gbp_api_rev_001_get_reviews_forwards_page_size_and_page_token(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts/123/locations/456/reviews*' => Http::response([
                'reviews' => [
                    ['reviewId' => 'review-1', 'comment' => 'Great service'],
                ],
                'nextPageToken' => 'page-2',
            ], 200),
        ]);

        $service = $this->makeService();
        $reviews = $service->getReviews('accounts/123/locations/456', 'token-123', 25, 'page-1');

        $this->assertSame('review-1', $reviews['reviews'][0]['reviewId'] ?? null);
        $this->assertSame('page-2', $reviews['nextPageToken'] ?? null);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/accounts/123/locations/456/reviews')
                && str_contains($request->url(), 'pageSize=25')
                && str_contains($request->url(), 'pageToken=page-1');
        });
    }

    #[Test]
    public function gbp_api_rev_002_respond_to_review_puts_reply_payload_to_reply_endpoint(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts/123/locations/456/reviews/review-1/reply' => Http::response([
                'comment' => 'Thanks for your feedback!',
            ], 200),
        ]);

        $service = $this->makeService();
        $result = $service->respondToReview('accounts/123/locations/456', 'review-1', 'Thanks for your feedback!', 'token-123');

        $this->assertTrue($result);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return $request->url() === 'https://mybusiness.googleapis.com/v4/accounts/123/locations/456/reviews/review-1/reply'
                && $request->method() === 'PUT'
                && ($data['comment'] ?? null) === 'Thanks for your feedback!';
        });
    }

    #[Test]
    public function gbp_api_err_001_retries_after_401_when_a_refresh_token_can_be_resolved(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts' => Http::sequence()
                ->push(['error' => ['message' => 'Expired token']], 401)
                ->push(['accounts' => [['name' => 'accounts/123']]], 200),
        ]);

        $this->oauthService
            ->shouldReceive('refreshAccessToken')
            ->once()
            ->with('refresh-token-123')
            ->andReturn([
                'access_token' => 'refreshed-access-token',
                'refresh_token' => 'refresh-token-123',
                'expires_at' => '2026-03-10T14:00:00Z',
            ]);

        $service = $this->makeService(['expired-access-token' => 'refresh-token-123']);
        $accounts = $service->getAccounts('expired-access-token');

        $this->assertSame('accounts/123', $accounts[0]['name'] ?? null);
    }

    #[Test]
    public function gbp_api_err_002_retries_rate_limited_requests_with_exponential_backoff(): void
    {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/accounts' => Http::sequence()
                ->push(['error' => ['message' => 'Rate limited']], 429)
                ->push(['accounts' => [['name' => 'accounts/123']]], 200),
        ]);

        $service = $this->makeService();
        $accounts = $service->getAccounts('rate-limited-token');

        $this->assertSame('accounts/123', $accounts[0]['name'] ?? null);
        $this->assertSame([1000], $service->capturedDelays);
    }

    private function makeService(array $refreshTokenMap = []): object
    {
        return new class($this->oauthService, $refreshTokenMap) extends GBPApiService {
            public array $capturedDelays = [];

            public function __construct(
                GoogleOAuthServiceInterface $oauthService,
                private array $refreshTokenMap,
            ) {
                parent::__construct($oauthService);
            }

            public function inspectBaseUrl(): string
            {
                return $this->baseUrl();
            }

            public function inspectNormalizeAccountName(string $accountId): string
            {
                return $this->normalizeAccountName($accountId);
            }

            protected function resolveRefreshToken(string $accessToken): ?string
            {
                return $this->refreshTokenMap[$accessToken] ?? null;
            }

            protected function pauseMilliseconds(int $milliseconds): void
            {
                $this->capturedDelays[] = $milliseconds;
            }
        };
    }
}
