<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorsEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the /api/indicators endpoint exists and returns the correct response.
     *
     * @return void
     */
    public function test_endpoint_exists_and_responds_correctly()
    {
        $response = $this->get('/api/indicators');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => []
        ]);
    }

    /**
     * Test that only GET method is allowed on the /api/indicators endpoint.
     *
     * @dataProvider disallowedHttpMethods
     * @param string $method
     * @return void
     */
    public function test_only_get_method_is_allowed($method)
    {
        $response = $this->$method('/api/indicators');

        $response->assertStatus(405); // Method Not Allowed
    }

    public function disallowedHttpMethods(): array
    {
        return [
            ['post'],
            ['put'],
            ['delete'],
            ['patch']
        ];
    }

    /**
     * Test that the response structure is valid.
     *
     * @return void
     */
    public function test_response_structure_is_valid()
    {
        $response = $this->get('/api/indicators');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data'
        ]);
        $this->assertIsArray($response->json('data'));
        $this->assertEmpty($response->json('data'));
    }

    /**
     * Test edge cases for the /api/indicators endpoint.
     *
     * @return void
     */
    public function test_edge_cases()
    {
        $response = $this->get('/api/indicators?foo=bar');
        $response->assertStatus(200);
        $response->assertJson([
            'data' => []
        ]);

        $response = $this->get('/api/indicators/');
        $response->assertStatus(200);

        $response = $this->get('/api/indicators', ['Accept' => 'application/xml']);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test performance constraints.
     *
     * @return void
     */
    public function test_performance_constraints()
    {
        $start = microtime(true);

        $this->get('/api/indicators')->assertStatus(200);

        $duration = microtime(true) - $start;
        $this->assertLessThan(0.1, $duration, 'Response time exceeded 100ms');
    }
}