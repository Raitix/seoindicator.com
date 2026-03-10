<?php

namespace Tests\Unit\Services\LocalSEO;

use App\Services\LocalSEO\Contracts\NominatimServiceInterface;
use App\Services\LocalSEO\NominatimService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class NominatimServiceTest extends TestCase
{
    protected NominatimService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Config::set('app.name', 'SEOIndicator');
        Config::set('services.nominatim.base_url', 'https://nominatim.openstreetmap.org');
        Config::set('services.nominatim.contact_email', 'ops@example.com');
        Config::set('services.nominatim.user_agent', null);

        $this->service = app(NominatimService::class);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // =====================================================================
    // NOM-CLS: Class & Contract Tests
    // =====================================================================

    #[Test]
    public function nom_cls_001_service_class_exists(): void
    {
        $this->assertTrue(class_exists(NominatimService::class));
    }

    #[Test]
    public function nom_cls_002_service_implements_interface(): void
    {
        $this->assertInstanceOf(NominatimServiceInterface::class, $this->service);
    }

    #[Test]
    public function nom_cls_003_service_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, 'geocode'));
        $this->assertTrue(method_exists($this->service, 'reverseGeocode'));
    }

    #[Test]
    public function nom_cls_004_interface_matches_task_contract(): void
    {
        $geocode = new ReflectionMethod(NominatimServiceInterface::class, 'geocode');
        $reverse = new ReflectionMethod(NominatimServiceInterface::class, 'reverseGeocode');

        $this->assertCount(1, $geocode->getParameters());
        $this->assertSame('address', $geocode->getParameters()[0]->getName());
        $this->assertSame('string', $geocode->getParameters()[0]->getType()?->getName());
        $this->assertSame('array', $geocode->getReturnType()?->getName());
        $this->assertTrue($geocode->getReturnType()?->allowsNull());

        $this->assertCount(2, $reverse->getParameters());
        $this->assertSame('lat', $reverse->getParameters()[0]->getName());
        $this->assertSame('float', $reverse->getParameters()[0]->getType()?->getName());
        $this->assertSame('lng', $reverse->getParameters()[1]->getName());
        $this->assertSame('float', $reverse->getParameters()[1]->getType()?->getName());
        $this->assertSame('string', $reverse->getReturnType()?->getName());
        $this->assertTrue($reverse->getReturnType()?->allowsNull());
    }

    // =====================================================================
    // NOM-GEO: geocode() Tests
    // =====================================================================

    #[Test]
    public function nom_geo_001_geocode_returns_normalized_payload_on_success(): void
    {
        $address = '350 5th Ave, New York, NY 10118';

        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '40.7484405',
                    'lon' => '-73.9856644',
                    'display_name' => '350 5th Ave, New York, NY 10118, USA',
                ],
            ], 200),
        ]);

        $result = $this->service->geocode($address);

        $this->assertSame([
            'lat' => 40.7484405,
            'lng' => -73.9856644,
            'display_name' => '350 5th Ave, New York, NY 10118, USA',
        ], $result);
    }

    #[Test]
    public function nom_geo_002_geocode_uses_search_endpoint_and_query_parameters(): void
    {
        $address = '1600 Amphitheatre Parkway, Mountain View, CA';

        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '37.4220',
                    'lon' => '-122.0841',
                    'display_name' => '1600 Amphitheatre Parkway, Mountain View, CA',
                ],
            ], 200),
        ]);

        $this->service->geocode($address);

        Http::assertSent(function (Request $request) use ($address) {
            return str_contains($request->url(), '/search')
                && str_contains($request->url(), 'format=jsonv2')
                && str_contains($request->url(), 'limit=1')
                && str_contains(urldecode($request->url()), 'q=' . $address);
        });
    }

    #[Test]
    public function nom_geo_003_geocode_sets_required_user_agent_header(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '56.9496',
                    'lon' => '24.1052',
                    'display_name' => 'Riga, Latvia',
                ],
            ], 200),
        ]);

        $this->service->geocode('Riga, Latvia');

        Http::assertSent(function (Request $request) {
            $header = $request->header('User-Agent')[0] ?? '';

            return str_contains($header, 'SEOIndicator')
                && str_contains($header, 'ops@example.com');
        });
    }

    #[Test]
    public function nom_geo_004_geocode_uses_configurable_base_url(): void
    {
        Config::set('services.nominatim.base_url', 'https://geo.example.test');
        $service = app(NominatimService::class);

        Http::fake([
            'https://geo.example.test/search*' => Http::response([
                [
                    'lat' => '51.5072',
                    'lon' => '-0.1276',
                    'display_name' => 'London, UK',
                ],
            ], 200),
        ]);

        $service->geocode('London, UK');

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://geo.example.test/search'));
    }

    #[Test]
    public function nom_geo_005_geocode_returns_null_when_api_response_is_empty(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([], 200),
        ]);

        $this->assertNull($this->service->geocode('Unknown Place 12345'));
    }

    #[Test]
    public function nom_geo_006_geocode_returns_null_on_http_error(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                'error' => 'Internal server error',
            ], 500),
        ]);

        $this->assertNull($this->service->geocode('Paris, France'));
    }

    #[Test]
    public function nom_geo_007_geocode_returns_null_on_connection_exception(): void
    {
        Http::fake([
            '*' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $this->assertNull($this->service->geocode('Berlin, Germany'));
    }

    #[Test]
    public function nom_geo_008_geocode_reads_from_expected_cache_key(): void
    {
        $address = 'Riga, Latvia';
        $expected = [
            'lat' => 56.9496,
            'lng' => 24.1052,
            'display_name' => 'Riga, Latvia',
        ];

        Cache::put('geocode:' . md5($address), $expected, now()->addDays(30));
        Http::preventStrayRequests();

        $result = $this->service->geocode($address);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function nom_geo_009_geocode_caches_identical_requests_for_thirty_days(): void
    {
        $address = 'Tallinn, Estonia';

        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '59.4370',
                    'lon' => '24.7536',
                    'display_name' => 'Tallinn, Estonia',
                ],
            ], 200),
        ]);

        $first = $this->service->geocode($address);
        $second = $this->service->geocode($address);

        Http::assertSentCount(1);
        $this->assertSame($first, $second);
        $this->assertSame($first, Cache::get('geocode:' . md5($address)));
    }

    #[Test]
    public function nom_geo_010_rate_limiting_enforces_one_request_per_second_for_uncached_calls(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '40.7128',
                    'lon' => '-74.0060',
                    'display_name' => 'New York, NY, USA',
                ],
            ], 200),
        ]);

        $start = microtime(true);

        $this->service->geocode('New York, NY, USA');
        $this->service->geocode('Boston, MA, USA');

        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(0.9, $elapsed, 'Uncached Nominatim requests must be throttled to 1 req/sec.');
    }

    // =====================================================================
    // NOM-REV: reverseGeocode() Tests
    // =====================================================================

    #[Test]
    public function nom_rev_001_reverse_geocode_returns_display_name_on_success(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Riga, Latvia',
            ], 200),
        ]);

        $result = $this->service->reverseGeocode(56.9496, 24.1052);

        $this->assertSame('Riga, Latvia', $result);
    }

    #[Test]
    public function nom_rev_002_reverse_geocode_uses_reverse_endpoint_with_coordinates(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Riga, Latvia',
            ], 200),
        ]);

        $this->service->reverseGeocode(56.9496, 24.1052);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/reverse')
                && str_contains($request->url(), 'format=jsonv2')
                && str_contains($request->url(), 'lat=56.9496')
                && str_contains($request->url(), 'lon=24.1052');
        });
    }

    #[Test]
    public function nom_rev_003_reverse_geocode_returns_null_when_display_name_missing(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'lat' => '56.9496',
                'lon' => '24.1052',
            ], 200),
        ]);

        $this->assertNull($this->service->reverseGeocode(56.9496, 24.1052));
    }

    #[Test]
    public function nom_rev_004_reverse_geocode_returns_null_on_http_error(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'error' => 'Bad Gateway',
            ], 502),
        ]);

        $this->assertNull($this->service->reverseGeocode(56.9496, 24.1052));
    }

    #[Test]
    public function nom_rev_005_reverse_geocode_returns_null_on_connection_exception(): void
    {
        Http::fake([
            '*' => function () {
                throw new ConnectionException('DNS lookup failed');
            },
        ]);

        $this->assertNull($this->service->reverseGeocode(56.9496, 24.1052));
    }

    #[Test]
    public function nom_rev_006_reverse_geocode_reads_from_expected_cache_key(): void
    {
        $expected = 'Riga, Latvia';
        Cache::put('reverse_geocode:' . md5('56.9496,24.1052'), $expected, now()->addDays(30));
        Http::preventStrayRequests();

        $result = $this->service->reverseGeocode(56.9496, 24.1052);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function nom_rev_007_reverse_geocode_caches_identical_requests(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Vilnius, Lithuania',
            ], 200),
        ]);

        $first = $this->service->reverseGeocode(54.6872, 25.2797);
        $second = $this->service->reverseGeocode(54.6872, 25.2797);

        Http::assertSentCount(1);
        $this->assertSame($first, $second);
        $this->assertSame($first, Cache::get('reverse_geocode:' . md5('54.6872,25.2797')));
    }
}

