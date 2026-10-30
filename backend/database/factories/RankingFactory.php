<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ranking>
 */
class RankingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $features = fake()->boolean()
            ? ['featured_snippet' => true]
            : ['local_pack' => false];

        return [
            'keyword_id' => \App\Models\Keyword::factory(),
            'position' => fake()->numberBetween(1, 100),
            'url_found' => fake()->optional()->url(),
            'serp_features' => fake()->optional()->passthrough(json_encode($features)),
            'checked_at' => fake()->dateTimeThisMonth(),
            'check_duration_ms' => fake()->optional()->numberBetween(50, 800),
            'error' => null,
        ];
    }
}
