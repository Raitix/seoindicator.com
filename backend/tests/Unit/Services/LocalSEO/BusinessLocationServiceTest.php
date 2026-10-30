<?php

namespace Tests\Unit\Services\LocalSEO;

use App\Models\BusinessLocation;
use App\Models\Site;
use App\Models\User;
use App\Services\LocalSEO\Contracts\BusinessLocationServiceInterface;
use App\Services\LocalSEO\Contracts\NominatimServiceInterface;
use App\Services\LocalSEO\BusinessLocationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class BusinessLocationServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // BLS-001: Service Instantiation
    // =========================================================================

    /** @test */
    public function bsl_001_01_service_class_exists(): void
    {
        $this->assertTrue(class_exists(BusinessLocationService::class));
    }

    /** @test */
    public function bsl_001_02_implements_interface(): void
    {
        $service = app(BusinessLocationServiceInterface::class);
        $this->assertInstanceOf(BusinessLocationServiceInterface::class, $service);
    }

    /** @test */
    public function bsl_001_03_constructor_injection(): void
    {
        $service = app(BusinessLocationService::class);
        $this->assertInstanceOf(BusinessLocationService::class, $service);
    }

    /** @test */
    public function bsl_001_04_container_binding(): void
    {
        $service = app(BusinessLocationServiceInterface::class);
        $this->assertInstanceOf(BusinessLocationService::class, $service);
    }

    /** @test */
    public function bsl_001_05_interface_resolvable(): void
    {
        $service = app(BusinessLocationServiceInterface::class);
        $this->assertNotNull($service);
    }

    // =========================================================================
    // BLS-002: createLocation() - Basic
    // =========================================================================

    /** @test */
    public function bsl_002_01_create_with_required_fields(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $location = $service->createLocation($data);

        $this->assertInstanceOf(BusinessLocation::class, $location);
        $this->assertEquals('Test Location', $location->name);
    }

    /** @test */
    public function bsl_002_02_returns_created_model(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $location = $service->createLocation($data);

        $this->assertInstanceOf(BusinessLocation::class, $location);
        $this->assertNotNull($location->id);
    }

    /** @test */
    public function bsl_002_03_geocoding_called_automatically(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')
            ->once()
            ->andReturn([
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'display_name' => '123 Main St, New York, US',
            ]);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $service->createLocation($data);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function bsl_002_04_geocoding_success_populates_coordinates(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')
            ->andReturn([
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'display_name' => '123 Main St, New York, US',
            ]);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $location = $service->createLocation($data);

        $this->assertEquals(40.7128, $location->latitude);
        $this->assertEquals(-74.0060, $location->longitude);
    }

    /** @test */
    public function bsl_002_05_geocoding_failure_creates_location_without_coordinates(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')
            ->andReturn(null);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Invalid Address',
            'address' => 'Nonexistent Street',
            'city' => 'Unknown City',
            'country' => 'XX',
        ];

        $location = $service->createLocation($data);

        $this->assertInstanceOf(BusinessLocation::class, $location);
        $this->assertNull($location->latitude);
        $this->assertNull($location->longitude);
    }

    /** @test */
    public function bsl_002_06_partial_address_geocode_returns_null(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')
            ->andReturn(null);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'country' => 'US',
            // Missing city
        ];

        $location = $service->createLocation($data);

        $this->assertInstanceOf(BusinessLocation::class, $location);
    }

    /** @test */
    public function bsl_002_07_optional_fields_stored(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'phone' => '+1-555-123-4567',
            'website' => 'https://example.com',
            'business_hours' => ['monday' => ['09:00-17:00']],
        ];

        $location = $service->createLocation($data);

        $this->assertEquals('+1-555-123-4567', $location->phone);
        $this->assertEquals('https://example.com', $location->website);
        $this->assertEquals(['monday' => ['09:00-17:00']], $location->business_hours);
    }

    /** @test */
    public function bsl_002_08_gbp_fields_stored(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'gbp_location_id' => 'locations/ABC123',
            'gbp_place_id' => 'ChIJ1234567890',
        ];

        $location = $service->createLocation($data);

        $this->assertEquals('locations/ABC123', $location->gbp_location_id);
        $this->assertEquals('ChIJ1234567890', $location->gbp_place_id);
    }

    /** @test */
    public function bsl_002_09_settings_json_stored(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'settings' => ['notification_email' => 'owner@example.com'],
        ];

        $location = $service->createLocation($data);

        $this->assertEquals(['notification_email' => 'owner@example.com'], $location->settings);
    }

    /** @test */
    public function bsl_002_10_active_by_default(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $location = $service->createLocation($data);

        $this->assertTrue($location->is_active);
    }

    /** @test */
    public function bsl_002_11_created_timestamp_populated(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $location = $service->createLocation($data);

        $this->assertNotNull($location->created_at);
    }

    /** @test */
    public function bsl_002_12_database_persisted(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $location = $service->createLocation($data);

        $this->assertDatabaseHas('business_locations', [
            'id' => $location->id,
            'name' => 'Test Location',
        ]);
    }

    // =========================================================================
    // BLS-003: createLocation() - Validation
    // =========================================================================

    /** @test */
    public function bsl_003_01_missing_site_id_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();

        $data = [
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $service->createLocation($data);
    }

    /** @test */
    public function bsl_003_02_missing_name_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $service->createLocation($data);
    }

    /** @test */
    public function bsl_003_03_missing_address_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'city' => 'New York',
            'country' => 'US',
        ];

        $service->createLocation($data);
    }

    /** @test */
    public function bsl_003_04_missing_city_is_allowed(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'country' => 'US',
        ];

        $location = $service->createLocation($data);

        $this->assertInstanceOf(BusinessLocation::class, $location);
    }

    /** @test */
    public function bsl_003_05_missing_country_is_allowed(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
        ];

        $location = $service->createLocation($data);

        $this->assertInstanceOf(BusinessLocation::class, $location);
    }

    /** @test */
    public function bsl_003_06_invalid_site_id_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();

        $data = [
            'site_id' => 99999,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $service->createLocation($data);
    }

    /** @test */
    public function bsl_003_07_name_too_long_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => str_repeat('a', 256),
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $service->createLocation($data);
    }

    /** @test */
    public function bsl_003_08_address_too_long_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => str_repeat('a', 501),
            'city' => 'New York',
            'country' => 'US',
        ];

        $service->createLocation($data);
    }

    /** @test */
    public function bsl_003_09_invalid_phone_format_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'phone' => 'abc123',
        ];

        $service->createLocation($data);
    }

    /** @test */
    public function bsl_003_10_invalid_website_url_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'website' => 'not-a-url',
        ];

        $service->createLocation($data);
    }

    /** @test */
    public function bsl_003_11_invalid_business_hours_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'business_hours' => 'not-an-array',
        ];

        $service->createLocation($data);
    }

    /** @test */
    public function bsl_003_12_invalid_settings_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'settings' => 'not-an-array',
        ];

        $service->createLocation($data);
    }

    /** @test */
    public function bsl_003_13_duplicate_location_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        // Create first location
        $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        // Try to create duplicate
        $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);
    }

    /** @test */
    public function bsl_003_14_unicode_in_name_allowed(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Café ☕ Restaurant',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $location = $service->createLocation($data);

        $this->assertEquals('Café ☕ Restaurant', $location->name);
    }

    /** @test */
    public function bsl_003_15_emoji_in_address_allowed(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $data = [
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St 🏢',
            'city' => 'New York',
            'country' => 'US',
        ];

        $location = $service->createLocation($data);

        $this->assertEquals('123 Main St 🏢', $location->address);
    }

    // =========================================================================
    // BLS-004: updateLocation() - Basic
    // =========================================================================

    /** @test */
    public function bsl_004_01_update_name(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Original Name',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $updated = $service->updateLocation($location->id, ['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $updated->name);
    }

    /** @test */
    public function bsl_004_02_update_address_regeocodes(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')
            ->twice()
            ->andReturn([
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'display_name' => 'Updated Address, New York, US',
            ]);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $updated = $service->updateLocation($location->id, ['address' => '456 Oak Ave']);

        $this->assertEquals('456 Oak Ave', $updated->address);
    }

    /** @test */
    public function bsl_004_03_update_phone(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'phone' => '+1-555-000-0000',
        ]);

        $updated = $service->updateLocation($location->id, ['phone' => '+1-555-123-4567']);

        $this->assertEquals('+1-555-123-4567', $updated->phone);
    }

    /** @test */
    public function bsl_004_04_update_website(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'website' => 'https://old.example.com',
        ]);

        $updated = $service->updateLocation($location->id, ['website' => 'https://new.example.com']);

        $this->assertEquals('https://new.example.com', $updated->website);
    }

    /** @test */
    public function bsl_004_05_update_business_hours(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'business_hours' => ['monday' => ['09:00-17:00']],
        ]);

        $updated = $service->updateLocation($location->id, [
            'business_hours' => ['monday' => ['10:00-18:00']],
        ]);

        $this->assertEquals(['monday' => ['10:00-18:00']], $updated->business_hours);
    }

    /** @test */
    public function bsl_004_06_update_settings(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'settings' => ['key1' => 'value1'],
        ]);

        $updated = $service->updateLocation($location->id, [
            'settings' => ['key2' => 'value2'],
        ]);

        $this->assertEquals(['key2' => 'value2'], $updated->settings);
    }

    /** @test */
    public function bsl_004_07_partial_update_preserves_other_fields(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Original Name',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'phone' => '+1-555-000-0000',
        ]);

        $updated = $service->updateLocation($location->id, ['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('123 Main St', $updated->address);
        $this->assertEquals('+1-555-000-0000', $updated->phone);
    }

    /** @test */
    public function bsl_004_08_updated_timestamp_changed(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $originalUpdatedAt = $location->updated_at;

        sleep(1);

        $updated = $service->updateLocation($location->id, ['name' => 'Updated Name']);

        $this->assertNotEquals($originalUpdatedAt, $updated->updated_at);
    }

    /** @test */
    public function bsl_004_09_returns_updated_model(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $updated = $service->updateLocation($location->id, ['name' => 'Updated Name']);

        $this->assertInstanceOf(BusinessLocation::class, $updated);
    }

    /** @test */
    public function bsl_004_10_regeocode_on_address_change(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')
            ->once()
            ->andReturn([
                'latitude' => 40.7128,
                'longitude' => -74.0060,
            ]);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $location = BusinessLocation::create([
            'user_id' => $site->user_id,
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'latitude' => null,
            'longitude' => null,
        ]);

        $service->updateLocation($location->id, ['address' => '456 Oak Ave']);

        $this->addToAssertionCount(1);
    }

    // =========================================================================
    // BLS-005: updateLocation() - Validation
    // =========================================================================

    /** @test */
    public function bsl_005_01_non_existent_location_throws_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = $this->getMockedService();

        $service->updateLocation(99999, ['name' => 'Updated Name']);
    }

    /** @test */
    public function bsl_005_02_invalid_name_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->updateLocation($location->id, ['name' => '']);
    }

    /** @test */
    public function bsl_005_03_invalid_address_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->updateLocation($location->id, ['address' => '']);
    }

    /** @test */
    public function bsl_005_04_invalid_phone_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->updateLocation($location->id, ['phone' => 'abc']);
    }

    /** @test */
    public function bsl_005_05_invalid_website_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->updateLocation($location->id, ['website' => 'not-url']);
    }

    /** @test */
    public function bsl_005_06_invalid_business_hours_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->updateLocation($location->id, ['business_hours' => 'not-array']);
    }

    /** @test */
    public function bsl_005_07_same_name_different_address_allowed(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $updated = $service->updateLocation($location->id, ['address' => '456 Oak Ave']);

        $this->assertEquals('456 Oak Ave', $updated->address);
    }

    /** @test */
    public function bsl_005_08_site_id_change_ignored(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();
        $otherSite = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $updated = $service->updateLocation($location->id, ['site_id' => $otherSite->id]);

        $this->assertEquals($site->id, $updated->site_id);
    }

    /** @test */
    public function bsl_005_09_gbp_fields_ignored(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $updated = $service->updateLocation($location->id, [
            'gbp_location_id' => 'locations/NEW',
        ]);

        // GBP fields should not be updated via regular update
        $this->assertNotEquals('locations/NEW', $updated->gbp_location_id);
    }

    /** @test */
    public function bsl_005_10_soft_deleted_location_throws_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $location->delete();

        $service->updateLocation($location->id, ['name' => 'Updated Name']);
    }

    // =========================================================================
    // BLS-006: deleteLocation()
    // =========================================================================

    /** @test */
    public function bsl_006_01_soft_delete_existing(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $result = $service->deleteLocation($location->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function bsl_006_02_sets_deleted_at(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->deleteLocation($location->id);

        $this->assertSoftDeleted('business_locations', ['id' => $location->id]);
    }

    /** @test */
    public function bsl_006_03_non_existent_throws_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = $this->getMockedService();

        $service->deleteLocation(99999);
    }

    /** @test */
    public function bsl_006_04_already_deleted_throws_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->deleteLocation($location->id);

        // Try to delete again
        $service->deleteLocation($location->id);
    }

    /** @test */
    public function bsl_006_05_restore_location(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->deleteLocation($location->id);
        $restored = $service->restoreLocation($location->id);

        $this->assertNull($restored->deleted_at);
    }

    /** @test */
    public function bsl_006_06_force_delete(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $result = $service->deleteLocation($location->id, true);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('business_locations', ['id' => $location->id]);
    }

    /** @test */
    public function bsl_006_07_returns_boolean(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $result = $service->deleteLocation($location->id);

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    // =========================================================================
    // BLS-007: getLocationsForSite()
    // =========================================================================

    /** @test */
    public function bsl_007_01_returns_collection(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $collection = $service->getLocationsForSite($site->id);

        $this->assertInstanceOf(Collection::class, $collection);
    }

    /** @test */
    public function bsl_007_02_empty_result(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $collection = $service->getLocationsForSite($site->id);

        $this->assertCount(0, $collection);
    }

    /** @test */
    public function bsl_007_03_excludes_soft_deleted(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->deleteLocation($location->id);

        $collection = $service->getLocationsForSite($site->id);

        $this->assertCount(0, $collection);
    }

    /** @test */
    public function bsl_007_04_include_deleted_flag(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->deleteLocation($location->id);

        $collection = $service->getLocationsForSite($site->id, ['include_deleted' => true]);

        $this->assertCount(1, $collection);
    }

    /** @test */
    public function bsl_007_05_filter_by_active(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Active Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'is_active' => true,
        ]);

        $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Inactive Location',
            'address' => '456 Oak Ave',
            'city' => 'New York',
            'country' => 'US',
            'is_active' => false,
        ]);

        $collection = $service->getLocationsForSite($site->id, ['active_only' => true]);

        $this->assertCount(1, $collection);
        $this->assertEquals('Active Location', $collection->first()->name);
    }

    /** @test */
    public function bsl_007_05b_active_only_tolerates_missing_is_active_column(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Schema Drift Location',
            'address' => '789 Drift Ave',
            'city' => 'New York',
            'country' => 'US',
            'is_active' => true,
        ]);

        if (Schema::hasColumn('business_locations', 'is_active')) {
            Schema::table('business_locations', function ($table) {
                $table->dropColumn('is_active');
            });
        }

        $collection = $service->getLocationsForSite($site->id, ['active_only' => true]);

        $this->assertCount(1, $collection);
        $this->assertEquals('Schema Drift Location', $collection->first()->name);
    }

    /** @test */
    public function bsl_007_06_pagination(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        // Create multiple locations
        for ($i = 0; $i < 15; $i++) {
            $service->createLocation([
                'site_id' => $site->id,
                'name' => "Location $i",
                'address' => "{$i}00 Main St",
                'city' => 'New York',
                'country' => 'US',
            ]);
        }

        $result = $service->getLocationsForSite($site->id, ['per_page' => 10]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
    }

    /** @test */
    public function bsl_007_07_order_by_name(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Zebra Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Alpha Location',
            'address' => '456 Oak Ave',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $collection = $service->getLocationsForSite($site->id, ['sort' => 'name']);

        $this->assertEquals('Alpha Location', $collection->first()->name);
        $this->assertEquals('Zebra Location', $collection->last()->name);
    }

    // =========================================================================
    // BLS-008: getLocationsForUser()
    // =========================================================================

    /** @test */
    public function bsl_008_01_returns_collection(): void
    {
        $service = $this->getMockedService();
        $user = $this->createUser();
        $site = $this->createSite(['user_id' => $user->id]);

        $collection = $service->getLocationsForUser($user->id);

        $this->assertInstanceOf(Collection::class, $collection);
    }

    /** @test */
    public function bsl_008_02_via_sites_relationship(): void
    {
        $service = $this->getMockedService();
        $user = $this->createUser();
        $site = $this->createSite(['user_id' => $user->id]);

        $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $collection = $service->getLocationsForUser($user->id);

        $this->assertCount(1, $collection);
    }

    /** @test */
    public function bsl_008_03_empty_result(): void
    {
        $service = $this->getMockedService();
        $user = $this->createUser();

        $collection = $service->getLocationsForUser($user->id);

        $this->assertCount(0, $collection);
    }

    /** @test */
    public function bsl_008_04_excludes_other_users(): void
    {
        $service = $this->getMockedService();
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $site1 = $this->createSite(['user_id' => $user1->id]);
        $site2 = $this->createSite(['user_id' => $user2->id]);

        $service->createLocation([
            'site_id' => $site1->id,
            'name' => 'User1 Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->createLocation([
            'site_id' => $site2->id,
            'name' => 'User2 Location',
            'address' => '456 Oak Ave',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $collection = $service->getLocationsForUser($user1->id);

        $this->assertCount(1, $collection);
        $this->assertEquals('User1 Location', $collection->first()->name);
    }

    /** @test */
    public function bsl_008_05_filter_by_site(): void
    {
        $service = $this->getMockedService();
        $user = $this->createUser();
        $site1 = $this->createSite(['user_id' => $user->id]);
        $site2 = $this->createSite(['user_id' => $user->id]);

        $service->createLocation([
            'site_id' => $site1->id,
            'name' => 'Site1 Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->createLocation([
            'site_id' => $site2->id,
            'name' => 'Site2 Location',
            'address' => '456 Oak Ave',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $collection = $service->getLocationsForUser($user->id, ['site_id' => $site1->id]);

        $this->assertCount(1, $collection);
        $this->assertEquals('Site1 Location', $collection->first()->name);
    }

    // =========================================================================
    // BLS-009: validateLocationData()
    // =========================================================================

    /** @test */
    public function bsl_009_01_valid_data_returns_true(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $result = $service->validateLocationData([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function bsl_009_02_missing_site_id_returns_false(): void
    {
        $service = $this->getMockedService();

        $result = $service->validateLocationData([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('site_id', $result['errors']);
    }

    /** @test */
    public function bsl_009_03_missing_name_returns_false(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $result = $service->validateLocationData([
            'site_id' => $site->id,
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('name', $result['errors']);
    }

    /** @test */
    public function bsl_009_04_missing_address_returns_false(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $result = $service->validateLocationData([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('address', $result['errors']);
    }

    /** @test */
    public function bsl_009_05_missing_city_returns_true(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $result = $service->validateLocationData([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'country' => 'US',
        ]);

        $this->assertTrue($result['valid']);
        $this->assertArrayNotHasKey('city', $result['errors']);
    }

    /** @test */
    public function bsl_009_06_missing_country_returns_true(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $result = $service->validateLocationData([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
        ]);

        $this->assertTrue($result['valid']);
        $this->assertArrayNotHasKey('country', $result['errors']);
    }

    /** @test */
    public function bsl_009_07_invalid_website_returns_false(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $result = $service->validateLocationData([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'website' => 'not-a-url',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('website', $result['errors']);
    }

    /** @test */
    public function bsl_009_08_returns_errors_array(): void
    {
        $service = $this->getMockedService();

        $result = $service->validateLocationData([]);

        $this->assertIsArray($result['errors']);
    }

    /** @test */
    public function bsl_009_09_multiple_errors(): void
    {
        $service = $this->getMockedService();

        $result = $service->validateLocationData([
            'name' => '', // Invalid
            'address' => '', // Invalid
            'city' => '', // Invalid
        ]);

        $this->assertFalse($result['valid']);
        $this->assertGreaterThanOrEqual(3, count($result['errors']));
    }

    /** @test */
    public function bsl_009_10_unicode_allowed(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $result = $service->validateLocationData([
            'site_id' => $site->id,
            'name' => 'Café 东京 رستوران',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertTrue($result['valid']);
    }

    // =========================================================================
    // BLS-010: Geocoding Integration
    // =========================================================================

    /** @test */
    public function bsl_010_01_geocode_on_create(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')
            ->once()
            ->andReturn(['latitude' => 40.7128, 'longitude' => -74.0060]);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function bsl_010_02_store_coordinates(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')
            ->andReturn(['latitude' => 40.7128, 'longitude' => -74.0060]);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertEquals(40.7128, $location->latitude);
        $this->assertEquals(-74.0060, $location->longitude);
    }

    /** @test */
    public function bsl_010_03_geocode_on_update_address(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')
            ->twice()
            ->andReturn(['latitude' => 40.7128, 'longitude' => -74.0060]);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $service->updateLocation($location->id, ['address' => '456 Oak Ave']);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function bsl_010_04_geocode_failure_creates_without_coords(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')
            ->andReturn(null);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => 'Invalid Address',
            'city' => 'Unknown',
            'country' => 'XX',
        ]);

        $this->assertNull($location->latitude);
        $this->assertNull($location->longitude);
    }

    /** @test */
    public function bsl_010_05_skip_geocode_flag(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldNotReceive('geocode');

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'skip_geocode' => true,
        ]);

        $this->assertNull($location->latitude);
    }

    /** @test */
    public function bsl_010_06_manual_coordinates_skip_geocode(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldNotReceive('geocode');

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ]);

        $this->assertEquals(51.5074, $location->latitude);
        $this->assertEquals(-0.1278, $location->longitude);
    }

    // =========================================================================
    // BLS-012: Security
    // =========================================================================

    /** @test */
    public function bsl_012_01_sql_injection_in_name_stored_as_literal(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $maliciousName = "'; DROP TABLE business_locations; --";

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => $maliciousName,
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertEquals($maliciousName, $location->name);
        $this->assertDatabaseHas('business_locations', ['name' => $maliciousName]);
    }

    /** @test */
    public function bsl_012_03_xss_in_name_sanitized(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $xssName = '<script>alert("xss")</script>';

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => $xssName,
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        // Name should be stored but escaped when displayed
        $this->assertEquals($xssName, $location->name);
    }

    /** @test */
    public function bsl_012_06_mass_assignment_ignored(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'is_admin' => true, // Should be ignored
        ]);

        $this->assertFalse(isset($location->is_admin));
    }

    // =========================================================================
    // BLS-013: Performance
    // =========================================================================

    /** @test */
    public function bsl_013_01_create_performance(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $start = microtime(true);

        $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $time = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $time, 'Create location should take less than 500ms');
    }

    /** @test */
    public function bsl_013_02_list_performance(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        // Create 100 locations
        for ($i = 0; $i < 100; $i++) {
            $service->createLocation([
                'site_id' => $site->id,
                'name' => "Location $i",
                'address' => "{$i}00 Main St",
                'city' => 'New York',
                'country' => 'US',
            ]);
        }

        $start = microtime(true);

        $service->getLocationsForSite($site->id);

        $time = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $time, 'List locations should take less than 200ms');
    }

    // =========================================================================
    // BLS-014: Edge Cases
    // =========================================================================

    /** @test */
    public function bsl_014_01_empty_name_throws_validation(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->getMockedService();
        $site = $this->createSite();

        $service->createLocation([
            'site_id' => $site->id,
            'name' => '',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);
    }

    /** @test */
    public function bsl_014_03_unicode_name_stored_correctly(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $unicodeName = '東京餐厅مطعم';

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => $unicodeName,
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertEquals($unicodeName, $location->name);
    }

    /** @test */
    public function bsl_014_04_emoji_in_fields_stored_correctly(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => '📍 Location Name 🏢',
            'address' => '123 Main St 🚪',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertEquals('📍 Location Name 🏢', $location->name);
        $this->assertEquals('123 Main St 🚪', $location->address);
    }

    /** @test */
    public function bsl_014_09_null_optional_fields_accepted(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'phone' => null,
            'website' => null,
        ]);

        $this->assertNull($location->phone);
        $this->assertNull($location->website);
    }

    // =========================================================================
    // BLS-015: Acceptance Criteria
    // =========================================================================

    /** @test */
    public function bsl_015_01_service_exists(): void
    {
        $this->assertTrue(class_exists(BusinessLocationService::class));
    }

    /** @test */
    public function bsl_015_02_interface_defined(): void
    {
        $this->assertTrue(interface_exists(BusinessLocationServiceInterface::class));
    }

    /** @test */
    public function bsl_015_03_create_location_method(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertNotNull($location->id);
    }

    /** @test */
    public function bsl_015_04_update_location_method(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $updated = $service->updateLocation($location->id, ['name' => 'Updated']);

        $this->assertEquals('Updated', $updated->name);
    }

    /** @test */
    public function bsl_015_05_delete_location_method(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $result = $service->deleteLocation($location->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function bsl_015_06_get_locations_for_site_method(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $locations = $service->getLocationsForSite($site->id);

        $this->assertInstanceOf(Collection::class, $locations);
    }

    /** @test */
    public function bsl_015_07_get_locations_for_user_method(): void
    {
        $service = $this->getMockedService();
        $user = $this->createUser();

        $locations = $service->getLocationsForUser($user->id);

        $this->assertInstanceOf(Collection::class, $locations);
    }

    /** @test */
    public function bsl_015_08_validate_location_data_method(): void
    {
        $service = $this->getMockedService();
        $site = $this->createSite();

        $result = $service->validateLocationData([
            'site_id' => $site->id,
            'name' => 'Test',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
    }

    /** @test */
    public function bsl_015_09_geocoding_integration(): void
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')
            ->andReturn(['latitude' => 40.7128, 'longitude' => -74.0060]);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        $service = app(BusinessLocationService::class);
        $site = $this->createSite();

        $location = $service->createLocation([
            'site_id' => $site->id,
            'name' => 'Test Location',
            'address' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ]);

        $this->assertNotNull($location->latitude);
        $this->assertNotNull($location->longitude);
    }

    /** @test */
    public function bsl_015_10_service_provider_binding(): void
    {
        $service = app(BusinessLocationServiceInterface::class);
        $this->assertInstanceOf(BusinessLocationService::class, $service);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function getMockedService(): BusinessLocationService
    {
        $nominatimMock = Mockery::mock(NominatimServiceInterface::class);
        $nominatimMock->shouldReceive('geocode')->andReturn(null);

        $this->app->instance(NominatimServiceInterface::class, $nominatimMock);

        return app(BusinessLocationService::class);
    }

    protected function createSite(array $attributes = []): Site
    {
        return Site::factory()->create($attributes);
    }

    protected function createUser(): User
    {
        return User::factory()->create();
    }
}