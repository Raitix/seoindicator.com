<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\IssueType;

class IssueTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_issue_type()
    {
        $data = [
            'category' => 'Technical',
            'name' => 'Broken Links',
            'description' => 'Identifies and reports broken links on a site.',
            'severity_default' => 'critical',
            'recommendation_template' => 'Fix all reported broken links immediately.',
        ];

        $response = $this->postJson('/api/v1/issue-types', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('issue_types', [
            'category' => 'Technical',
            'name' => 'Broken Links'
        ]);
    }

    public function test_get_issue_types()
    {
        IssueType::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/issue-types');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_update_issue_type()
    {
        $issueType = IssueType::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->putJson("/api/v1/issue-types/{$issueType->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('issue_types', ['name' => 'Updated Name']);
    }

    public function test_delete_issue_type()
    {
        $issueType = IssueType::factory()->create();

        $response = $this->deleteJson("/api/v1/issue-types/{$issueType->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('issue_types', ['id' => $issueType->id]);
    }
}
