<?php

namespace Database\Factories;

use App\Models\AIModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIModel>
 */
class AIModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AIModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'openrouter',
            'model_id' => fake()->unique()->slug(2),
            'display_name' => fake()->words(3, true),
            'cost_per_1k_input' => fake()->randomFloat(6, 0, 0.1),
            'cost_per_1k_output' => fake()->randomFloat(6, 0, 0.3),
            'context_window' => fake()->randomElement([4096, 8192, 16384, 32768, 128000]),
            'is_free_tier' => false,
            'free_tier_limit' => null,
            'capabilities' => null,
            'is_active' => true,
            'last_synced_at' => now(),
        ];
    }

    /**
     * Indicate that the model is free tier.
     */
    public function freeTier(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_free_tier' => true,
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
            'free_tier_limit' => fake()->randomElement([10, 20, 50]),
        ]);
    }

    /**
     * Indicate that the model is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the model has vision capabilities.
     */
    public function withVision(): static
    {
        return $this->state(fn (array $attributes) => [
            'capabilities' => json_encode(['vision' => true]),
        ]);
    }

    /**
     * Create a GPT-4 style model.
     */
    public function gpt4(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_id' => 'openai/gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo',
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'context_window' => 128000,
        ]);
    }

    /**
     * Create a Claude style model.
     */
    public function claude(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_id' => 'anthropic/claude-3-opus',
            'display_name' => 'Claude 3 Opus',
            'cost_per_1k_input' => 0.015,
            'cost_per_1k_output' => 0.075,
            'context_window' => 200000,
        ]);
    }

    /**
     * Create a Llama free tier model.
     */
    public function llamaFree(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_id' => 'meta-llama/llama-3-8b-instruct:free',
            'display_name' => 'Llama 3 8B Free',
            'is_free_tier' => true,
            'cost_per_1k_input' => 0,
            'cost_per_1k_output' => 0,
            'context_window' => 8192,
        ]);
    }
}
