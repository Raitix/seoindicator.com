<?php

namespace Tests\Feature\Services\LocalSEO;

use App\Services\LocalSEO\Contracts\NominatimServiceInterface;
use App\Services\LocalSEO\NominatimService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NominatimServiceIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Config::set('app.name', 'SEOIndicator');
        Config::set('services.nominatim.base_url', 'https://nominatim.openstreetmap.org');
        Config::set('services.nominatim.contact_email', 'ops@example.com');
        Config::set('services.nominatim.user_agent', null);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    #[Test]
    public function nom_int_001_interface_is_bound_to_service_in_container(): void
    {
        $service = app(NominatimServiceInterface::class);

        $this->assertInstanceOf(NominatimService::class, $service);
        $this->assertInstanceOf(NominatimServiceInterface::class, $service);
    }

    #[Test]
    public function nom_int_002_default_base_url_is_used_when_resolved_from_container(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '48.8566',
                    'lon' => '2.3522',
                    'display_name' => 'Paris, France',
                ],
            ], 200),
        ]);

        app(NominatimServiceInterface::class)->geocode('Paris, France');

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://nominatim.openstreetmap.org/search'));
    }

    #[Test]
    public function nom_int_003_custom_base_url_from_config_flows_through_container_resolution(): void
    {
        Config::set('services.nominatim.base_url', 'https://geo.internal.test');

        Http::fake([
            'https://geo.internal.test/search*' => Http::response([
                [
                    'lat' => '52.5200',
                    'lon' => '13.4050',
                    'display_name' => 'Berlin, Germany',
                ],
            ], 200),
        ]);

        app(NominatimServiceInterface::class)->geocode('Berlin, Germany');

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://geo.internal.test/search'));
    }

    #[Test]
    public function nom_int_004_user_agent_configuration_flows_through_interface_resolution(): void
    {
        Config::set('services.nominatim.user_agent', 'SEOIndicator Test Suite/1.0 (ops@example.com)');

        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '41.9028',
                    'lon' => '12.4964',
                    'display_name' => 'Rome, Italy',
                ],
            ], 200),
        ]);

        app(NominatimServiceInterface::class)->geocode('Rome, Italy');

        Http::assertSent(function (Request $request) {
            $header = $request->header('User-Agent')[0] ?? '';

            return $header === 'SEOIndicator Test Suite/1.0 (ops@example.com)';
        });
    }

    #[Test]
    public function nom_int_005_cached_geocode_results_are_reused_via_interface_resolution(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '59.9139',
                    'lon' => '10.7522',
                    'display_name' => 'Oslo, Norway',
                ],
            ], 200),
        ]);

        $service = app(NominatimServiceInterface::class);

        $first = $service->geocode('Oslo, Norway');
        $second = $service->geocode('Oslo, Norway');

        Http::assertSentCount(1);
        $this->assertSame($first, $second);
        $this->assertSame($first, Cache::get('geocode:' . md5('Oslo, Norway')));
    }

    #[Test]
    public function nom_int_006_reverse_geocode_is_available_through_interface_resolution(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Helsinki, Finland',
            ], 200),
        ]);

        $result = app(NominatimServiceInterface::class)->reverseGeocode(60.1699, 24.9384);

        $this->assertSame('Helsinki, Finland', $result);
    }
}

