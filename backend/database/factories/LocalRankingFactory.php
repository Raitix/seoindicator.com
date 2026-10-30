<?php

namespace Database\Factories;

use App\Models\LocalRanking;
use App\Models\BusinessLocation;
use App\Models\Keyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LocalRanking>
 */
class LocalRankingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = LocalRanking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => BusinessLocation::factory(),
            'keyword_id' => Keyword::factory(),
            'search_location' => fake()->city() . ', ' . fake()->stateAbbr(),
            'search_lat' => fake()->latitude(25, 50),
            'search_lng' => fake()->longitude(-125, -70),
            'local_pack_position' => fake()->optional(70)->numberBetween(1, 20),
            'local_organic_position' => fake()->optional(80)->numberBetween(1, 100),
            'competitors_in_pack' => fake()->optional(50)->passthrough([
                ['name' => fake()->company(), 'position' => 1],
                ['name' => fake()->company(), 'position' => 2],
                ['name' => fake()->company(), 'position' => 3],
            ]),
            'checked_at' => now(),
        ];
    }

    /**
     * Indicate that the ranking is in local pack.
     */
    public function inPack(): static
    {
        return $this->state(fn (array $attributes) => [
            'local_pack_position' => fake()->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate that the ranking is not in local pack.
     */
    public function notInPack(): static
    {
        return $this->state(fn (array $attributes) => [
            'local_pack_position' => null,
            'local_organic_position' => fake()->numberBetween(4, 100),
        ]);
    }

    /**
     * Indicate that the ranking was checked at a specific time.
     */
    public function checkedAt(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_at' => $date,
        ]);
    }
}
