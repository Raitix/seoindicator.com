<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Mockery;

/**
 * Health Endpoint Tests for TASK-1-21
 * 
 * Tests verify that GET /api/health returns correct response structure
 * and handles database connectivity states properly.
 */
class HealthEndpointTest extends TestCase
{
    /**
     * Test health endpoint returns 200 OK with correct structure.
     */
    public function test_health_endpoint_returns_ok_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'database',
                'app',
                'env',
            ])
            ->assertJson([
                'status' => 'ok',
            ]);
    }

    /**
     * Test health endpoint shows database as connected when DB is available.
     */
    public function test_health_endpoint_shows_database_connected(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonPath('database', 'connected');
    }

    /**
     * Test health endpoint timestamp is valid ISO8601 format.
     */
    public function test_health_endpoint_timestamp_is_valid_iso8601(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $timestamp = $response->json('timestamp');
        
        // Verify it's a valid ISO8601 timestamp
        $parsed = date_create_from_format('Y-m-d\TH:i:sP', $timestamp);
        $this->assertNotFalse($parsed, "Timestamp should be valid ISO8601 format");
    }

    /**
     * Test health endpoint returns app name from environment.
     */
    public function test_health_endpoint_returns_app_name(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonPath('app', 'SEOIndicator');
    }

    /**
     * Test health endpoint returns environment name.
     */
    public function test_health_endpoint_returns_environment(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        // In testing environment, should return 'testing'
        $this->assertEquals('testing', $response->json('env'));
    }

    /**
     * Test health endpoint is publicly accessible (no auth required).
     */
    public function test_health_endpoint_is_public(): void
    {
        // Make request without any authentication
        $response = $this->getJson('/api/health');

        // Should not return 401 Unauthorized
        $response->assertStatus(200);
    }

    /**
     * Test health endpoint handles database disconnection gracefully.
     */
    public function test_health_endpoint_handles_database_disconnection(): void
    {
        // Mock DB facade to throw exception (simulating disconnection)
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \PDOException('Connection refused'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'database' => 'disconnected',
            ]);
    }

    /**
     * Test health endpoint response time is reasonable (< 1 second).
     */
    public function test_health_endpoint_response_time(): void
    {
        $start = microtime(true);
        
        $this->getJson('/api/health');
        
        $end = microtime(true);
        $duration = $end - $start;

        // Health check should complete in under 1 second
        $this->assertLessThan(1.0, $duration, "Health endpoint should respond in under 1 second");
    }

    /**
     * Test health endpoint returns correct content type.
     */
    public function test_health_endpoint_returns_json_content_type(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test health endpoint does not expose sensitive information.
     */
    public function test_health_endpoint_does_not_expose_secrets(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Should not contain any key names, passwords, or secrets
        $this->assertStringNotContainsString('APP_KEY', $content);
        $this->assertStringNotContainsString('password', $content);
        $this->assertStringNotContainsString('secret', $content);
        $this->assertStringNotContainsString('token', $content);
        $this->assertStringNotContainsString('api_key', $content);
    }
}
