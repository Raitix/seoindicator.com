<?php

namespace Tests\Feature\Api\V1;

use App\Models\IssueType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for TASK-14-3: Issue Types CRUD API
 *
 * Tests the REST API endpoints for issue type management.
 * All endpoints require authentication.
 */
class IssueTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->user = User::factory()->create();
    }

    // ===========================================
    // INDEX Tests
    // ===========================================

    /**
     * Test 1: List all issue types - success
     */
    public function test_list_issue_types(): void
    {
        IssueType::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/issue-types');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(5, 'data');
    }

    /**
     * Test 2: List issue types - empty list
     */
    public function test_list_issue_types_empty(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/issue-types');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data', []);
    }

    /**
     * Test 3: List issue types with pagination
     */
    public function test_list_issue_types_with_pagination(): void
    {
        IssueType::factory()->count(25)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/issue-types?per_page=10');

        $response->assertOk();
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 25);
    }

    /**
     * Test 4: List issue types - filter by category
     */
    public function test_list_issue_types_filter_by_category(): void
    {
        IssueType::factory()->count(3)->create(['category' => 'meta']);
        IssueType::factory()->count(2)->create(['category' => 'content']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/issue-types?category=meta');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test 5: List issue types - filter by severity
     */
    public function test_list_issue_types_filter_by_severity(): void
    {
        IssueType::factory()->count(2)->create(['severity_default' => 'critical']);
        IssueType::factory()->count(3)->create(['severity_default' => 'warning']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/issue-types?severity=critical');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    /**
     * Test 6: List issue types - search by name
     */
    public function test_list_issue_types_search_by_name(): void
    {
        IssueType::factory()->create(['name' => 'Missing Title Tag']);
        IssueType::factory()->create(['name' => 'Duplicate Meta Description']);
        IssueType::factory()->create(['name' => 'Slow Page Load']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/issue-types?search=Title');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Missing Title Tag');
    }

    /**
     * Test 7: List issue types - sorting
     */
    public function test_list_issue_types_sorting(): void
    {
        IssueType::factory()->create(['name' => 'Zebra Issue']);
        IssueType::factory()->create(['name' => 'Alpha Issue']);
        IssueType::factory()->create(['name' => 'Middle Issue']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/issue-types?sort=name&direction=asc');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Alpha Issue');
        $response->assertJsonPath('data.1.name', 'Middle Issue');
        $response->assertJsonPath('data.2.name', 'Zebra Issue');
    }

    // ===========================================
    // CATEGORIES Tests
    // ===========================================

    /**
     * Test 8: Get categories - success
     */
    public function test_get_categories(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/issue-types/categories');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.meta', 'SEO Meta');
        $response->assertJsonPath('data.content', 'Content Quality');
        $response->assertJsonPath('data.backlink', 'Backlinks');
        $response->assertJsonPath('data.performance', 'Performance');
    }

    // ===========================================
    // STORE Tests
    // ===========================================

    /**
     * Test 9: Create issue type - success
     */
    public function test_create_issue_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/issue-types', [
            'category' => 'meta',
            'name' => 'Missing Title Tag',
            'description' => 'The page is missing a title tag.',
            'severity_default' => 'critical',
            'recommendation_template' => 'Add a descriptive title tag to the page.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.category', 'meta');
        $response->assertJsonPath('data.name', 'Missing Title Tag');
        $response->assertJsonPath('data.severityDefault', 'critical');

        $this->assertDatabaseHas('issue_types', [
            'category' => 'meta',
            'name' => 'Missing Title Tag',
        ]);
    }

    /**
     * Test 10: Create issue type - validation error (missing fields)
     */
    public function test_create_issue_type_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/issue-types', [
            'name' => 'Missing Category',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    /**
     * Test 11: Create issue type - invalid severity
     */
    public function test_create_issue_type_invalid_severity(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/issue-types', [
            'category' => 'meta',
            'name' => 'Test Issue',
            'description' => 'Test description',
            'severity_default' => 'invalid_severity',
            'recommendation_template' => 'Test template',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    /**
     * Test 12: Create issue type - invalid category
     */
    public function test_create_issue_type_invalid_category(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/issue-types', [
            'category' => 'invalid_category',
            'name' => 'Test Issue',
            'description' => 'Test description',
            'severity_default' => 'warning',
            'recommendation_template' => 'Test template',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    /**
     * Test 13: Create issue type - duplicate category+name
     */
    public function test_create_issue_type_duplicate(): void
    {
        IssueType::factory()->create([
            'category' => 'meta',
            'name' => 'Missing Title Tag',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/issue-types', [
            'category' => 'meta',
            'name' => 'Missing Title Tag',
            'description' => 'Another description',
            'severity_default' => 'warning',
            'recommendation_template' => 'Test template',
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'ISSUE_TYPE_EXISTS');
    }

    // ===========================================
    // SHOW Tests
    // ===========================================

    /**
     * Test 14: Get single issue type - success
     */
    public function test_get_issue_type(): void
    {
        $issueType = IssueType::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issue-types/{$issueType->id}");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.id', $issueType->id);
        $response->assertJsonPath('data.name', $issueType->name);
    }

    /**
     * Test 15: Get single issue type - not found
     */
    public function test_get_issue_type_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/issue-types/99999');

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'NOT_FOUND');
    }

    // ===========================================
    // UPDATE Tests
    // ===========================================

    /**
     * Test 16: Update issue type - success
     */
    public function test_update_issue_type(): void
    {
        $issueType = IssueType::factory()->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/issue-types/{$issueType->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('issue_types', [
            'id' => $issueType->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test 17: Update issue type - not found
     */
    public function test_update_issue_type_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/issue-types/99999', [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'NOT_FOUND');
    }

    /**
     * Test 18: Update issue type - validation error
     */
    public function test_update_issue_type_validation_error(): void
    {
        $issueType = IssueType::factory()->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/issue-types/{$issueType->id}", [
            'severity_default' => 'invalid_severity',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    /**
     * Test 19: Update issue type - duplicate on update
     */
    public function test_update_issue_type_duplicate(): void
    {
        IssueType::factory()->create([
            'category' => 'meta',
            'name' => 'Existing Issue',
        ]);

        $issueType = IssueType::factory()->create([
            'category' => 'content',
            'name' => 'To Update',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/issue-types/{$issueType->id}", [
            'category' => 'meta',
            'name' => 'Existing Issue',
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'ISSUE_TYPE_EXISTS');
    }

    // ===========================================
    // DESTROY Tests
    // ===========================================

    /**
     * Test 20: Delete issue type - success
     */
    public function test_delete_issue_type(): void
    {
        $issueType = IssueType::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/issue-types/{$issueType->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('issue_types', [
            'id' => $issueType->id,
        ]);
    }

    /**
     * Test 21: Delete issue type - not found
     */
    public function test_delete_issue_type_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/issue-types/99999');

        $response->assertStatus(404);
    }

    // ===========================================
    // Response Format Tests
    // ===========================================

    /**
     * Test 22: Response includes categoryLabel
     */
    public function test_response_includes_category_label(): void
    {
        $issueType = IssueType::factory()->create(['category' => 'meta']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issue-types/{$issueType->id}");

        $response->assertOk();
        $response->assertJsonPath('data.categoryLabel', 'SEO Meta');
    }

    /**
     * Test 23: Response includes timestamps
     */
    public function test_response_includes_timestamps(): void
    {
        $issueType = IssueType::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issue-types/{$issueType->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'category',
                'categoryLabel',
                'name',
                'description',
                'severityDefault',
                'recommendationTemplate',
                'createdAt',
                'updatedAt',
            ],
        ]);
    }

    // ===========================================
    // Security Tests
    // ===========================================

    /**
     * Test 24: SQL injection protection
     */
    public function test_sql_injection_protection(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/issue-types?search='; DROP TABLE issue_types; --");

        // Should not cause an error, just return empty or no results
        $response->assertOk();
    }

    /**
     * Test 25: XSS protection in search
     */
    public function test_xss_protection_in_search(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/issue-types?search=<script>alert(1)</script>');

        $response->assertOk();
    }

    // ===========================================
    // Authentication Tests
    // ===========================================

    /**
     * Test 26: Unauthenticated access denied - index
     */
    public function test_unauthenticated_index(): void
    {
        $response = $this->getJson('/api/v1/issue-types');
        $response->assertStatus(401);
    }

    /**
     * Test 27: Unauthenticated access denied - store
     */
    public function test_unauthenticated_store(): void
    {
        $response = $this->postJson('/api/v1/issue-types', [
            'category' => 'meta',
            'name' => 'Test',
            'description' => 'Test',
            'severity_default' => 'warning',
            'recommendation_template' => 'Test',
        ]);
        $response->assertStatus(401);
    }

    /**
     * Test 28: Unauthenticated access denied - show
     */
    public function test_unauthenticated_show(): void
    {
        $issueType = IssueType::factory()->create();
        $response = $this->getJson("/api/v1/issue-types/{$issueType->id}");
        $response->assertStatus(401);
    }

    /**
     * Test 29: Unauthenticated access denied - update
     */
    public function test_unauthenticated_update(): void
    {
        $issueType = IssueType::factory()->create();
        $response = $this->putJson("/api/v1/issue-types/{$issueType->id}", ['name' => 'Updated']);
        $response->assertStatus(401);
    }

    /**
     * Test 30: Unauthenticated access denied - destroy
     */
    public function test_unauthenticated_destroy(): void
    {
        $issueType = IssueType::factory()->create();
        $response = $this->deleteJson("/api/v1/issue-types/{$issueType->id}");
        $response->assertStatus(401);
    }
}
