<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\IssueType;
use App\Models\User;

class IssueTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_issue_types()
    {
        // Arrange: Create 10 issue types
        IssueType::factory()->count(10)->create();

        // Act: Fetch issue types via API
        $response = $this->actingAs(User::factory()->create())->getJson('/api/v1/issue-types');

        // Assert: Validate response
        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
    }

    /** @test */
    public function it_can_create_an_issue_type()
    {
        // Arrange: Prepare issue type data
        $data = [
            'category' => 'content',
            'name' => 'Keyword Density',
            'description' => 'Describes optimal keyword density.',
            'severity_default' => 'warning',
            'recommendation_template' => 'Ensure keyword density is under 5%.',
        ];

        // Act: Send POST request to create new issue type
        $response = $this->actingAs(User::factory()->create())->postJson('/api/v1/issue-types', $data);

        // Assert: Validate response and database changes
        $response->assertStatus(201);
        $this->assertDatabaseHas('issue_types', $data);
    }

    /** @test */
    public function it_can_update_an_issue_type()
    {
        // Arrange: Create an issue type and prepare update data
        $issueType = IssueType::factory()->create();
        $updatedData = ['name' => 'Updated Name'];

        // Act: Send PUT request to update the issue type
        $response = $this->actingAs(User::factory()->create())->putJson("/api/v1/issue-types/{$issueType->id}", $updatedData);

        // Assert: Validate response and database update
        $response->assertStatus(200);
        $this->assertDatabaseHas('issue_types', ['id' => $issueType->id, 'name' => 'Updated Name']);
    }

    /** @test */
    public function it_can_delete_an_issue_type()
    {
        // Arrange: Create an issue type
        $issueType = IssueType::factory()->create();

        // Act: Send DELETE request to remove the issue type
        $response = $this->actingAs(User::factory()->create())->deleteJson("/api/v1/issue-types/{$issueType->id}");

        // Assert: Validate response and database state
        $response->assertStatus(204);
        $this->assertDatabaseMissing('issue_types', ['id' => $issueType->id]);
    }
}