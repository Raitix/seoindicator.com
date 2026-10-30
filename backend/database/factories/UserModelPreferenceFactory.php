<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\AIModel;
use App\Models\UserModelPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserModelPreference>
 */
class UserModelPreferenceFactory extends Factory
{
    protected $model = UserModelPreference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'model_id' => AIModel::factory(),
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the preference is the user's default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the preference is not the default.
     */
    public function notDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => false,
        ]);
    }

    /**
     * Create a preference for a specific user.
     */
    public function forUser(User|int $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    /**
     * Create a preference for a specific model.
     */
    public function forModel(AIModel|int $model): static
    {
        return $this->state(fn (array $attributes) => [
            'model_id' => $model instanceof AIModel ? $model->id : $model,
        ]);
    }

    /**
     * Create a preference with an existing user and model.
     * Use this when user and model already exist to avoid factory duplication.
     */
    public function existing(): static
    {
        return $this->state(function (array $attributes) {
            // If user_id or model_id are already set, keep them
            return [
                'user_id' => $attributes['user_id'] ?? User::factory(),
                'model_id' => $attributes['model_id'] ?? AIModel::factory(),
            ];
        });
    }
}
