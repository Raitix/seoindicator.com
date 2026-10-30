<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\DeployController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery;

/**
 * Unit tests for DeployController health method (TASK-1-21)
 */
class DeployControllerTest extends TestCase
{
    /**
     * Test health method returns correct response array.
     */
    public function test_health_method_returns_correct_structure(): void
    {
        $controller = new DeployController();
        
        $response = $controller->health();
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('database', $data);
        $this->assertArrayHasKey('app', $data);
        $this->assertArrayHasKey('env', $data);
    }

    /**
     * Test health method returns 'ok' status.
     */
    public function test_health_method_returns_ok_status(): void
    {
        $controller = new DeployController();
        
        $response = $controller->health();
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('ok', $data['status']);
    }

    /**
     * Test health method returns connected when database is available.
     */
    public function test_health_method_returns_database_connected(): void
    {
        $controller = new DeployController();
        
        $response = $controller->health();
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('connected', $data['database']);
    }

    /**
     * Test health method returns disconnected when database is unavailable.
     */
    public function test_health_method_returns_database_disconnected_on_error(): void
    {
        // Mock DB to throw exception
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \PDOException('Connection refused'));

        $controller = new DeployController();
        
        $response = $controller->health();
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('disconnected', $data['database']);
    }

    /**
     * Test health method timestamp format.
     */
    public function test_health_method_timestamp_format(): void
    {
        $controller = new DeployController();
        
        $response = $controller->health();
        
        $data = json_decode($response->getContent(), true);
        
        // Should be ISO8601 format
        $parsed = date_create_from_format('Y-m-d\TH:i:sP', $data['timestamp']);
        $this->assertNotFalse($parsed);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
