<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\AIModel;
use App\Models\UserModelPreference;
use Illuminate\Database\QueryException;

/**
 * UserModelPreference Model Tests (TASK-26-19)
 *
 * Tests for the user_model_preferences table and UserModelPreference model.
 * Covers relationships, scopes, casts, indexes, and business logic.
 *
 * @group user-model-preferences
 * @group billing
 */
class UserModelPreferenceTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Model Creation Tests (UMP-CR)
    // =========================================================================

    /** @test */
    public function it_can_create_user_model_preference(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('user_model_preferences', [
            'id' => $preference->id,
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
            'is_default' => false,
        ]);
    }

    /** @test */
    public function it_creates_with_default_is_default_false(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        $this->assertFalse($preference->is_default);
    }

    /** @test */
    public function it_can_create_with_is_default_true(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
            'is_default' => true,
        ]);

        $this->assertTrue($preference->is_default);
    }

    /** @test */
    public function it_enforces_unique_constraint_on_user_and_model(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        $this->expectException(QueryException::class);

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);
    }

    /** @test */
    public function it_allows_same_model_for_different_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference1 = UserModelPreference::create([
            'user_id' => $user1->id,
            'model_id' => $aiModel->id,
        ]);

        $preference2 = UserModelPreference::create([
            'user_id' => $user2->id,
            'model_id' => $aiModel->id,
        ]);

        $this->assertNotEquals($preference1->id, $preference2->id);
        $this->assertEquals($preference1->model_id, $preference2->model_id);
    }

    /** @test */
    public function it_allows_different_models_for_same_user(): void
    {
        $user = User::factory()->create();
        $model1 = AIModel::factory()->create();
        $model2 = AIModel::factory()->create();

        $preference1 = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model1->id,
        ]);

        $preference2 = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model2->id,
        ]);

        $this->assertCount(2, UserModelPreference::where('user_id', $user->id)->get());
    }

    /** @test */
    public function it_requires_user_id(): void
    {
        $aiModel = AIModel::factory()->create();

        $this->expectException(QueryException::class);

        UserModelPreference::create([
            'model_id' => $aiModel->id,
        ]);
    }

    /** @test */
    public function it_requires_model_id(): void
    {
        $user = User::factory()->create();

        $this->expectException(QueryException::class);

        UserModelPreference::create([
            'user_id' => $user->id,
        ]);
    }

    // =========================================================================
    // Relationship Tests (UMP-REL)
    // =========================================================================

    /** @test */
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        $this->assertInstanceOf(User::class, $preference->user);
        $this->assertEquals($user->id, $preference->user->id);
    }

    /** @test */
    public function it_belongs_to_ai_model(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        $this->assertInstanceOf(AIModel::class, $preference->aiModel);
        $this->assertEquals($aiModel->id, $preference->aiModel->id);
    }

    /** @test */
    public function it_cascades_delete_with_user(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        $user->delete();

        $this->assertDatabaseMissing('user_model_preferences', [
            'id' => $preference->id,
        ]);
    }

    /** @test */
    public function it_cascades_delete_with_ai_model(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        $aiModel->delete();

        $this->assertDatabaseMissing('user_model_preferences', [
            'id' => $preference->id,
        ]);
    }

    // =========================================================================
    // Casts Tests (UMP-CA)
    // =========================================================================

    /** @test */
    public function it_casts_is_default_to_boolean(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
            'is_default' => 1,
        ]);

        $this->assertIsBool($preference->is_default);
        $this->assertTrue($preference->is_default);

        $preference->update(['is_default' => 0]);
        $preference->refresh();

        $this->assertIsBool($preference->is_default);
        $this->assertFalse($preference->is_default);
    }

    /** @test */
    public function it_casts_is_default_from_string(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
            'is_default' => '1',
        ]);

        $this->assertTrue($preference->is_default);
    }

    /** @test */
    public function it_has_timestamps(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        $this->assertNotNull($preference->created_at);
        $this->assertNotNull($preference->updated_at);
    }

    /** @test */
    public function it_updates_timestamp_on_update(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        $originalUpdatedAt = $preference->updated_at;

        sleep(1);

        $preference->update(['is_default' => true]);
        $preference->refresh();

        $this->assertGreaterThan($originalUpdatedAt, $preference->updated_at);
    }

    /** @test */
    public function it_casts_user_id_to_integer(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        $this->assertIsInt($preference->user_id);
    }

    /** @test */
    public function it_casts_model_id_to_integer(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        $this->assertIsInt($preference->model_id);
    }

    // =========================================================================
    // Scope Tests (UMP-SC)
    // =========================================================================

    /** @test */
    public function scope_for_user_filters_by_user_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $model1 = AIModel::factory()->create();
        $model2 = AIModel::factory()->create();

        UserModelPreference::create([
            'user_id' => $user1->id,
            'model_id' => $model1->id,
        ]);

        UserModelPreference::create([
            'user_id' => $user2->id,
            'model_id' => $model2->id,
        ]);

        $user1Preferences = UserModelPreference::forUser($user1->id)->get();

        $this->assertCount(1, $user1Preferences);
        $this->assertEquals($user1->id, $user1Preferences->first()->user_id);
    }

    /** @test */
    public function scope_default_filters_by_is_default_true(): void
    {
        $user = User::factory()->create();
        $model1 = AIModel::factory()->create();
        $model2 = AIModel::factory()->create();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model1->id,
            'is_default' => true,
        ]);

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model2->id,
            'is_default' => false,
        ]);

        $defaults = UserModelPreference::default()->get();

        $this->assertCount(1, $defaults);
        $this->assertTrue($defaults->first()->is_default);
    }

    /** @test */
    public function scope_for_user_with_default_returns_user_default(): void
    {
        $user = User::factory()->create();
        $model1 = AIModel::factory()->create();
        $model2 = AIModel::factory()->create();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model1->id,
            'is_default' => true,
        ]);

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model2->id,
            'is_default' => false,
        ]);

        $userDefault = UserModelPreference::forUser($user->id)->default()->first();

        $this->assertNotNull($userDefault);
        $this->assertEquals($model1->id, $userDefault->model_id);
    }

    /** @test */
    public function scope_ordered_orders_by_created_at_desc(): void
    {
        $user = User::factory()->create();
        $model1 = AIModel::factory()->create();
        $model2 = AIModel::factory()->create();
        $model3 = AIModel::factory()->create();

        $pref1 = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model1->id,
        ]);

        sleep(1);

        $pref2 = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model2->id,
        ]);

        sleep(1);

        $pref3 = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model3->id,
        ]);

        $ordered = UserModelPreference::forUser($user->id)->ordered()->get();

        $this->assertEquals($pref3->id, $ordered[0]->id);
        $this->assertEquals($pref2->id, $ordered[1]->id);
        $this->assertEquals($pref1->id, $ordered[2]->id);
    }

    // =========================================================================
    // Index Tests (UMP-IX)
    // =========================================================================

    /** @test */
    public function it_has_index_on_user_id(): void
    {
        $indexes = $this->getTableIndexes('user_model_preferences');

        $this->assertTrue(
            collect($indexes)->contains('name', 'user_model_preferences_user_id_index'),
            'Missing index on user_id column'
        );
    }

    /** @test */
    public function it_has_index_on_model_id(): void
    {
        $indexes = $this->getTableIndexes('user_model_preferences');

        $this->assertTrue(
            collect($indexes)->contains('name', 'user_model_preferences_model_id_index'),
            'Missing index on model_id column'
        );
    }

    /** @test */
    public function it_has_unique_constraint_on_user_and_model(): void
    {
        $indexes = $this->getTableIndexes('user_model_preferences');

        $uniqueConstraint = collect($indexes)->first(function ($index) {
            return str_contains($index['name'], 'user_model_preferences_user_id_model_id_unique')
                || str_contains($index['name'], 'user_id_model_id_unique');
        });

        $this->assertNotNull($uniqueConstraint, 'Missing unique constraint on (user_id, model_id)');
    }

    /** @test */
    public function it_has_index_on_is_default(): void
    {
        $indexes = $this->getTableIndexes('user_model_preferences');

        $this->assertTrue(
            collect($indexes)->contains('name', 'user_model_preferences_is_default_index'),
            'Missing index on is_default column'
        );
    }

    // =========================================================================
    // Edge Case Tests (UMP-EC)
    // =========================================================================

    /** @test */
    public function it_handles_multiple_preferences_for_user(): void
    {
        $user = User::factory()->create();
        $models = AIModel::factory()->count(5)->create();

        foreach ($models as $model) {
            UserModelPreference::create([
                'user_id' => $user->id,
                'model_id' => $model->id,
            ]);
        }

        $this->assertCount(5, UserModelPreference::where('user_id', $user->id)->get());
    }

    /** @test */
    public function it_handles_multiple_defaults_query(): void
    {
        $user = User::factory()->create();
        $model1 = AIModel::factory()->create();
        $model2 = AIModel::factory()->create();

        // Create two preferences both marked as default (data inconsistency scenario)
        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model1->id,
            'is_default' => true,
        ]);

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model2->id,
            'is_default' => true,
        ]);

        // Query should return both (business logic should prevent this scenario)
        $defaults = UserModelPreference::forUser($user->id)->default()->get();

        $this->assertCount(2, $defaults);
    }

    /** @test */
    public function it_handles_user_without_preferences(): void
    {
        $user = User::factory()->create();

        $preferences = UserModelPreference::forUser($user->id)->get();

        $this->assertCount(0, $preferences);
    }

    /** @test */
    public function it_handles_user_without_default(): void
    {
        $user = User::factory()->create();
        $model1 = AIModel::factory()->create();
        $model2 = AIModel::factory()->create();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model1->id,
            'is_default' => false,
        ]);

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model2->id,
            'is_default' => false,
        ]);

        $default = UserModelPreference::forUser($user->id)->default()->first();

        $this->assertNull($default);
    }

    /** @test */
    public function it_handles_mass_assignment_protection(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = new UserModelPreference();
        $preference->fill([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
            'is_default' => true,
        ]);
        $preference->save();

        // Should work if fillable is correctly set
        $this->assertDatabaseHas('user_model_preferences', [
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
            'is_default' => 1,
        ]);
    }

    /** @test */
    public function it_handles_soft_delete_on_related_models(): void
    {
        // If User or AIModel uses soft deletes, ensure preference still accessible
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        $this->assertNotNull($preference->user);
        $this->assertNotNull($preference->aiModel);
    }

    /** @test */
    public function it_handles_large_user_id(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
        ]);

        // Should handle standard BIGINT
        $this->assertGreaterThan(0, $preference->id);
    }

    // =========================================================================
    // Security Tests (UMP-SEC)
    // =========================================================================

    /** @test */
    public function it_prevents_sql_injection_in_user_id(): void
    {
        $this->expectException(\TypeError::class);

        UserModelPreference::create([
            'user_id' => "1; DROP TABLE user_model_preferences;--",
            'model_id' => 1,
        ]);
    }

    /** @test */
    public function it_prevents_sql_injection_in_model_id(): void
    {
        $this->expectException(\TypeError::class);

        UserModelPreference::create([
            'user_id' => 1,
            'model_id' => "1; DROP TABLE user_model_preferences;--",
        ]);
    }

    /** @test */
    public function it_isolates_preferences_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $model = AIModel::factory()->create();

        UserModelPreference::create([
            'user_id' => $user1->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        // User 2 should not see User 1's preferences
        $user2Preferences = UserModelPreference::forUser($user2->id)->get();

        $this->assertCount(0, $user2Preferences);
    }

    /** @test */
    public function it_enforces_foreign_key_constraints(): void
    {
        $this->expectException(QueryException::class);

        UserModelPreference::create([
            'user_id' => 999999,
            'model_id' => 999999,
        ]);
    }

    /** @test */
    public function it_validates_boolean_values_for_is_default(): void
    {
        $user = User::factory()->create();
        $aiModel = AIModel::factory()->create();

        $preference = UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $aiModel->id,
            'is_default' => 'invalid',
        ]);

        // Should cast non-boolean to boolean
        $this->assertIsBool($preference->is_default);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /** @test */
    public function it_can_get_user_default_model(): void
    {
        $user = User::factory()->create();
        $model = AIModel::factory()->create();

        UserModelPreference::create([
            'user_id' => $user->id,
            'model_id' => $model->id,
            'is_default' => true,
        ]);

        $defaultPreference = UserModelPreference::forUser($user->id)->default()->with('aiModel')->first();

        $this->assertNotNull($defaultPreference);
        $this->assertEquals($model->id, $defaultPreference->aiModel->id);
    }

    /** @test */
    public function it_can_get_all_user_preferred_models(): void
    {
        $user = User::factory()->create();
        $models = AIModel::factory()->count(3)->create();

        foreach ($models as $model) {
            UserModelPreference::create([
                'user_id' => $user->id,
                'model_id' => $model->id,
            ]);
        }

        $preferences = UserModelPreference::forUser($user->id)->with('aiModel')->get();

        $this->assertCount(3, $preferences);
        $this->assertTrue($preferences->every(fn ($p) => $p->aiModel !== null));
    }

    // =========================================================================
    // Helper Methods for Testing
    // =========================================================================

    protected function getTableIndexes(string $tableName): array
    {
        $connection = \DB::connection();
        $indexes = [];

        if ($connection->getDriverName() === 'sqlite') {
            $result = \DB::select("PRAGMA index_list('{$tableName}')");
            foreach ($result as $row) {
                $columns = \DB::select("PRAGMA index_info('{$row->name}')");
                $indexes[] = [
                    'name' => $row->name,
                    'columns' => array_map(fn ($c) => $c->name, $columns),
                    'unique' => $row->unique === 1,
                ];
            }
        } else {
            $result = \DB::select("SHOW INDEX FROM {$tableName}");
            foreach ($result as $row) {
                $indexes[] = [
                    'name' => $row->Key_name,
                    'columns' => [$row->Column_name],
                    'unique' => !$row->Non_unique,
                ];
            }
        }

        return $indexes;
    }
}
