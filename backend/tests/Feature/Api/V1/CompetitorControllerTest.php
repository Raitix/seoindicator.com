<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Competitor;

/**
 * Feature tests for SPEC-003-2: Competitor CRUD API
 *
 * Tests the REST API endpoints for competitor management.
 */
class CompetitorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /**
     * Test 1: Create competitor with valid data
     */
    public function test_create_competitor_with_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/competitors', [
                'domain' => 'competitor.com',
                'displayName' => 'Main Competitor',
                'notes' => 'Primary rival',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.domain', 'competitor.com');
        $response->assertJsonPath('data.displayName', 'Main Competitor');
    }

    /**
     * Test 3: Create competitor - missing domain
     */
    public function test_create_competitor_missing_domain(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/competitors', [
                'displayName' => 'Competitor Name',
            ]);

        $response->assertStatus(400);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $response->assertJsonPath('error.details.domain', ['The domain field is required.']);
    }

    /**
     * Test 5: Create competitor - duplicate domain for same user
     */
    public function test_create_duplicate_competitor_for_same_user(): void
    {
        $user = User::factory()->create();

        Competitor::factory()->create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/competitors', [
                'domain' => 'competitor.com',
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'COMPETITOR_EXISTS');
    }

    /**
     * Test 15: List competitors - empty list
     */
    public function test_list_competitors_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/competitors');

        $response->assertOk();
        $response->assertJsonPath('data', []);
    }

    /**
     * Test 16: Get competitor - success
     */
    public function test_get_competitor(): void
    {
        $user = User::factory()->create();
        $competitor = Competitor::factory()->create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/competitors/{$competitor->id}");

        $response->assertOk();
        $response->assertJsonPath('data.domain', 'competitor.com');
    }

    /**
     * Test 27: Delete competitor - success
     */
    public function test_delete_competitor(): void
    {
        $user = User::factory()->create();
        $competitor = Competitor::factory()->create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/competitors/{$competitor->id}");

        $response->assertNoContent();

        // Verify soft delete (record should still exist but with deleted_at set)
        $this->assertSoftDeleted('competitors', [
            'id' => $competitor->id,
        ]);
    }
}
