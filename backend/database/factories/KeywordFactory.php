<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Keyword>
 */
class KeywordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => \App\Models\Site::factory(),
            'user_id' => null,
            'keyword' => fake()->unique()->words(2, true),
            'target_url' => fake()->optional()->url(),
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
            'last_checked_at' => fake()->optional()->dateTimeThisMonth(),
        ];
    }
}
