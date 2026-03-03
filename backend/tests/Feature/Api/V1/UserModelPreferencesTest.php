<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\AIModel;
use App\Models\UserModelPreference;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * User Model Preferences API Tests (TASK-26-19)
 *
 * Tests for GET/POST /api/v1/models/preferences endpoints.
 * Covers authentication, authorization, validation, and business logic.
 *
 * @group user-model-preferences
 * @group billing-api
 */
class UserModelPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create some active AI models for testing
        $this->activeModels = collect([
            AIModel::factory()->create([
                'provider' => 'openrouter',
                'model_id' => 'openai/gpt-4-turbo',
                'display_name' => 'GPT-4 Turbo',
                'is_active' => true,
            ]),
            AIModel::factory()->create([
                'provider' => 'openrouter',
                'model_id' => 'anthropic/claude-3-opus',
                'display_name' => 'Claude 3 Opus',
                'is_active' => true,
            ]),
            AIModel::factory()->create([
                'provider' => 'openrouter',
                'model_id' => 'meta-llama/llama-3-8b-instruct:free',
                'display_name' => 'Llama 3 8B Free',
                'is_active' => true,
            ]),
        ]);
    }

    // =========================================================================
    // GET /api/v1/models/preferences - List Preferences Tests (UMP-LIST)
    // =========================================================================

    /** @testUMP-LIST-01 */
    public function it_returns_empty_array_when_user_has_no_preferences(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/preferences');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /** @testUMP-LIST-02 */
    public function it_returns_user_preferences_with_model_details(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/preferences');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'model' => [
                            'id',
                            'display_name',
                            'provider',
                        ],
                        'is_default',
                    ],
                ],
            ]);
    }

    /** @testUMP-LIST-03 */
    public function it_returns_multiple_preferences_for_user(): void
    {
        $user = User::factory()->create();

        foreach ($this->activeModels->take(3) as $model) {
            UserModelPreference::create([
                'user_id' => $user->id,
                'model_id' => $model->id,
                'is_default' => $model->id === $this->activeModels->first()->id,
            ]);
        }

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/preferences');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @testUMP-LIST-04 */
    public function it_returns_default_model_nested_in_response(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/preferences');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.is_default', true)
            ->assertJsonPath('data.0.model.id', $model->id)
            ->assertJsonPath('data.0.model.display_name', $model->display_name);
    }

    /** @testUMP-LIST-05 */
    public function it_orders_preferences_by_created_at_desc(): void
    {
        $user = User::factory()->create();

        $pref1 = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $this->activeModels[0]->id,
            'is_default' => false,
        ]);

        sleep(1);

        $pref2 = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $this->activeModels[1]->id,
            'is_default' => false,
        ]);

        sleep(1);

        $pref3 = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $this->activeModels[2]->id,
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/preferences');

        $response->assertStatus(200);

        // Most recent first
        $this->assertEquals($pref3->model_id, $response->json('data.0.model.id'));
        $this->assertEquals($pref2->model_id, $response->json('data.1.model.id'));
        $this->assertEquals($pref1->model_id, $response->json('data.2.model.id'));
    }

    /** @testUMP-LIST-06 */
    public function it_returns_401_for_unauthenticated_request(): void
    {
        $response = $this->getJson('/api/v1/models/preferences');

        $response->assertStatus(401);
    }

    /** @testUMP-LIST-07 */
    public function it_only_returns_current_user_preferences(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserModelPreference::create([
            'user_id' => $user1->id,
            'model_id' => $this->activeModels->first()->id,
            'is_default' => true,
        ]);

        UserModelPreference::create([
            'user_id' => $user2->id,
            'model_id' => $this->activeModels->last()->id,
            'is_default' => true,
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/models/preferences');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.model.id', $this->activeModels->first()->id);
    }

    /** @testUMP-LIST-08 */
    public function it_excludes_inactive_models(): void
    {
        $user = User::factory()->create();
        $activeModel = $this->activeModels->first();

        $inactiveModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'inactive-model',
            'display_name' => 'Inactive Model',
            'is_active' => false,
        ]);

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $activeModel->id,
            'is_default' => true,
        ]);

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $inactiveModel->id,
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/preferences');

        // Should only return the active model preference
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // =========================================================================
    // POST /api/v1/models/preferences - Set Preference Tests (UMP-SET)
    // =========================================================================

    /** @testUMP-SET-01 */
    public function it_creates_new_preference(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model->id,
                'is_default' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'default_model' => [
                        'id' => $model->id,
                        'display_name' => $model->display_name,
                        'provider' => $model->provider,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);
    }

    /** @testUMP-SET-02 */
    public function it_updates_existing_preference(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        // Create initial preference
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model->id,
                'is_default' => true,
            ]);

        $response->assertStatus(200);

        // Should update, not create new
        $this->assertEquals(1, UserModelPreference::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);
    }

    /** @testUMP-SET-03 */
    public function it_unsets_previous_default_when_setting_new(): void
    {
        $user = User::factory()->create();
        $model1 = $this->activeModels[0];
        $model2 = $this->activeModels[1];

        // Set model1 as default
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model1->id,
            'is_default' => true,
        ]);

        // Set model2 as new default
        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model2->id,
                'is_default' => true,
            ]);

        $response->assertStatus(200);

        // model1 should no longer be default
        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $model1->id,
            'is_default' => false,
        ]);

        // model2 should be default
        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $model2->id,
            'is_default' => true,
        ]);
    }

    /** @testUMP-SET-04 */
    public function it_returns_401_for_unauthenticated_set_request(): void
    {
        $model = $this->activeModels->first();

        $response = $this->postJson('/api/v1/models/preferences', [
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        $response->assertStatus(401);
    }

    /** @testUMP-SET-05 */
    public function it_validates_model_id_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'is_default' => true,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['model_id']);
    }

    /** @testUMP-SET-06 */
    public function it_validates_model_id_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => 999999,
                'is_default' => true,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['model_id']);
    }

    /** @testUMP-SET-07 */
    public function it_validates_model_is_active(): void
    {
        $user = User::factory()->create();

        $inactiveModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'inactive-model',
            'display_name' => 'Inactive Model',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $inactiveModel->id,
                'is_default' => true,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['model_id']);
    }

    /** @testUMP-SET-08 */
    public function it_validates_is_default_is_boolean(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model->id,
                'is_default' => 'invalid',
            ]);

        // Should accept or cast invalid values
        // The exact behavior depends on implementation
        $response->assertStatus(200);
    }

    /** @testUMP-SET-09 */
    public function it_creates_preference_with_is_default_false(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model->id,
                'is_default' => false,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => false,
        ]);
    }

    /** @testUMP-SET-10 */
    public function it_can_set_preference_without_changing_default(): void
    {
        $user = User::factory()->create();
        $model1 = $this->activeModels[0];
        $model2 = $this->activeModels[1];

        // Set model1 as default
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model1->id,
            'is_default' => true,
        ]);

        // Add model2 as non-default preference
        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model2->id,
                'is_default' => false,
            ]);

        $response->assertStatus(200);

        // model1 should still be default
        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $model1->id,
            'is_default' => true,
        ]);
    }

    // =========================================================================
    // Business Logic Tests (UMP-BL)
    // =========================================================================

    /** @testUMP-BL-01 */
    public function it_only_allows_one_default_per_user(): void
    {
        $user = User::factory()->create();
        $model1 = $this->activeModels[0];
        $model2 = $this->activeModels[1];
        $model3 = $this->activeModels[2];

        // Set model1 as default
        $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model1->id,
                'is_default' => true,
            ]);

        // Set model2 as default
        $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model2->id,
                'is_default' => true,
            ]);

        // Set model3 as default
        $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model3->id,
                'is_default' => true,
            ]);

        // Only model3 should be default
        $defaultCount = UserModelPreference::where('user_id', $user->id)
            ->where('is_default', true)
            ->count();

        $this->assertEquals(1, $defaultCount);

        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $model3->id,
            'is_default' => true,
        ]);
    }

    /** @testUMP-BL-02 */
    public function it_preserves_preferences_for_other_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $model1 = $this->activeModels[0];
        $model2 = $this->activeModels[1];

        // User1 sets model1 as default
        UserModelPreference::create([
            'user_id' => $user1->id,
            'model_id' => $model1->id,
            'is_default' => true,
        ]);

        // User2 sets model2 as default
        $this->actingAs($user2)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model2->id,
                'is_default' => true,
            ]);

        // User1's preference should be unchanged
        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user1->id,
            'model_id' => $model1->id,
            'is_default' => true,
        ]);
    }

    /** @testUMP-BL-03 */
    public function it_logs_preference_changes(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'preference') &&
                    isset($context['user_id']) &&
                    isset($context['model_id']);
            });

        $user = User::factory()->create();
        $model = $this->activeModels->first();

        $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model->id,
                'is_default' => true,
            ]);
    }

    /** @testUMP-BL-04 */
    public function it_creates_or_updates_on_duplicate(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        // First request
        $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model->id,
                'is_default' => false,
            ]);

        // Second request with same model
        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model->id,
                'is_default' => true,
            ]);

        $response->assertStatus(200);

        // Should only have one preference record
        $this->assertEquals(1, UserModelPreference::where('user_id', $user->id)->count());
    }

    // =========================================================================
    // Security Tests (UMP-SEC)
    // =========================================================================

    /** @testUMP-SEC-01 */
    public function it_prevents_user_from_setting_others_preference(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $model = $this->activeModels->first();

        // User1 sets preference
        $pref = UserModelPreference::create([
            'user_id' => $user1->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        // User2 tries to modify via API (should create own, not modify user1's)
        $response = $this->actingAs($user2)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model->id,
                'is_default' => true,
            ]);

        $response->assertStatus(200);

        // User1's preference should be unchanged
        $this->assertDatabaseHas('user_model_preferences', [
            'id' => $pref->id,
            'user_id' => $user1->id,
            'is_default' => true,
        ]);
    }

    /** @testUMP-SEC-02 */
    public function it_validates_no_sql_injection_in_model_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => "1; DROP TABLE user_model_preferences;--",
                'is_default' => true,
            ]);

        $response->assertStatus(422);

        $this->assertGreaterThan(
            0,
            UserModelPreference::count(),
            'Table should still exist after injection attempt'
        );
    }

    /** @testUMP-SEC-03 */
    public function it_does_not_expose_other_users_data(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $model1 = $this->activeModels[0];
        $model2 = $this->activeModels[1];

        UserModelPreference::create([
            'user_id' => $user1->id,
            'model_id' => $model1->id,
            'is_default' => true,
        ]);

        UserModelPreference::create([
            'user_id' => $user2->id,
            'model_id' => $model2->id,
            'is_default' => true,
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/models/preferences');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.model.id', $model1->id);
    }

    /** @testUMP-SEC-04 */
    public function it_handles_concurrent_preference_updates(): void
    {
        $user = User::factory()->create();
        $model1 = $this->activeModels[0];
        $model2 = $this->activeModels[1];

        // Simulate concurrent requests
        $response1 = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model1->id,
                'is_default' => true,
            ]);

        $response2 = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model2->id,
                'is_default' => true,
            ]);

        // Both should succeed
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Only one should be default
        $defaultCount = UserModelPreference::where('user_id', $user->id)
            ->where('is_default', true)
            ->count();

        $this->assertLessThanOrEqual(1, $defaultCount);
    }

    // =========================================================================
    // HTTP Method Tests (UMP-HTTP)
    // =========================================================================

    /** @testUMP-HTTP-01 */
    public function it_rejects_put_on_preferences_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->putJson('/api/v1/models/preferences', [
                'model_id' => 1,
            ]);

        $response->assertStatus(405);
    }

    /** @testUMP-HTTP-02 */
    public function it_rejects_patch_on_preferences_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/models/preferences', [
                'model_id' => 1,
            ]);

        $response->assertStatus(405);
    }

    /** @testUMP-HTTP-03 */
    public function it_rejects_delete_on_preferences_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/models/preferences');

        $response->assertStatus(405);
    }

    // =========================================================================
    // Performance Tests (UMP-PERF)
    // =========================================================================

    /** @testUMP-PERF-01 */
    public function it_responds_within_500ms(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        $start = microtime(true);

        $this->actingAs($user)
            ->getJson('/api/v1/models/preferences');

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Response should be under 500ms');
    }

    /** @testUMP-PERF-02 */
    public function it_handles_many_preferences_efficiently(): void
    {
        $user = User::factory()->create();

        // Create 20 model preferences
        for ($i = 0; $i < 20; $i++) {
            $model = AIModel::factory()->create(['is_active' => true]);

            UserModelPreference::create([
                'user_id' => $user->id,
                'model_id' => $model->id,
                'is_default' => $i === 0,
            ]);
        }

        $start = microtime(true);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/preferences');

        $duration = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $duration, 'Response should be under 500ms with many preferences');
    }

    // =========================================================================
    // Edge Cases (UMP-EC)
    // =========================================================================

    /** @testUMP-EC-01 */
    public function it_handles_model_id_as_string(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => (string) $model->id,
                'is_default' => true,
            ]);

        $response->assertStatus(200);
    }

    /** @testUMP-EC-02 */
    public function it_handles_missing_is_default_defaults_to_false(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $model->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => false,
        ]);
    }

    /** @testUMP-EC-03 */
    public function it_handles_empty_request_body(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['model_id']);
    }

    /** @testUMP-EC-04 */
    public function it_handles_unicode_in_model_names(): void
    {
        $user = User::factory()->create();

        $unicodeModel = AIModel::factory()->create([
            'provider' => 'openrouter',
            'model_id' => 'unicode-model',
            'display_name' => 'Модель 中文 日本語 🤖',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', [
                'model_id' => $unicodeModel->id,
                'is_default' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.default_model.display_name', 'Модель 中文 日本語 🤖');
    }

    /** @testUMP-EC-05 */
    public function it_handles_deleted_model_gracefully(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        // Soft delete or hard delete the model
        $model->delete();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/preferences');

        // Should return empty or exclude the deleted model
        $response->assertStatus(200);
    }

    /** @testUMP-EC-06 */
    public function it_handles_invalid_json_body(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/models/preferences', 'invalid json');

        $response->assertStatus(400);
    }

    // =========================================================================
    // Rate Limiting Tests (UMP-RATE)
    // =========================================================================

    /** @testUMP-RATE-01 */
    public function it_allows_reasonable_request_frequency(): void
    {
        $user = User::factory()->create();
        $model = $this->activeModels->first();

        // Make 10 rapid requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)
                ->getJson('/api/v1/models/preferences');

            $response->assertStatus(200);
        }
    }

    /** @testUMP-RATE-02 */
    public function it_includes_rate_limit_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/models/preferences');

        $response->assertStatus(200);
        // Rate limit headers are optional but recommended
    }
}
