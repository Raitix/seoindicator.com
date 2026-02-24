<?php

namespace Tests\Unit\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class Spec00421Test extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_new_report()
    {
        // Arrange: Prepare data
        $data = [
            'title' => 'Test Report',
            'description' => 'A sample report description.',
        ];

        // Act: Call API to create report
        $response = $this->actingAs($this->authenticatedUser())
                         ->postJson('/api/reports', $data);

        // Assert: Verify response
        $response->assertStatus(201)
                 ->assertJsonFragment(['title' => 'Test Report']);

        $this->assertDatabaseHas('reports', [
            'title' => 'Test Report',
        ]);
    }

    /** @test */
    public function it_reads_an_existing_report()
    {
        // Arrange: Create a report in database
        $report = Report::factory()->create();

        // Act: Retrieve report through API
        $response = $this->actingAs($this->authenticatedUser())
                         ->getJson("/api/reports/{$report->id}");

        // Assert: Verify response
        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $report->id]);
    }

    // Additional tests cover each endpoint described in TASK-4-21.md
}
