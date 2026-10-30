<?php

namespace Database\Factories\AIVisibility;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIVisibility\AIVisibilityAlert>
 */
class AIVisibilityAlertFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\AIVisibility\AIVisibilityAlert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\AIVisibility\AIVisibilityProject::factory(),
            'alert_type' => fake()->randomElement([
                'mention_spike',
                'mention_drop',
                'competitor_gain',
            ]),
            'threshold' => fake()->randomFloat(2, 15, 25),
            'previous_value' => fake()->randomFloat(2, 10, 50),
            'current_value' => fake()->randomFloat(2, 20, 100),
            'change_percent' => fake()->randomFloat(2, 20, 50),
            'triggered_at' => now(),
            'is_read' => false,
            'is_resolved' => false,
        ];
    }

    /**
     * Indicate that the alert is a mention spike.
     */
    public function mentionSpike(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'mention_spike',
        ]);
    }

    /**
     * Indicate that the alert is a mention drop.
     */
    public function mentionDrop(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'mention_drop',
        ]);
    }

    /**
     * Indicate that the alert is a competitor gain.
     */
    public function competitorGain(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'competitor_gain',
        ]);
    }

    /**
     * Indicate that the alert has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }

    /**
     * Indicate that the alert has been resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => true,
        ]);
    }
}
