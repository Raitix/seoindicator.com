<?php

namespace Database\Factories;

use App\Models\KeywordSuggestion;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KeywordSuggestion>
 */
class KeywordSuggestionFactory extends Factory
{
    protected $model = KeywordSuggestion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'keyword' => fake()->words(3, true),
            'source' => fake()->randomElement(['suggest', 'competitor', 'related']),
            'search_volume' => null,
            'difficulty_score' => null,
            'opportunity_score' => fake()->numberBetween(50, 100),
            'is_tracking' => false,
            'is_dismissed' => false,
            'discovered_at' => now(),
        ];
    }

    /**
     * Suggestion is being tracked.
     */
    public function tracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_tracking' => true,
        ]);
    }

    /**
     * Suggestion is dismissed.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dismissed' => true,
        ]);
    }

    /**
     * High opportunity score (>= 70).
     */
    public function highOpportunity(): static
    {
        return $this->state(fn (array $attributes) => [
            'opportunity_score' => fake()->numberBetween(70, 100),
        ]);
    }

    /**
     * Low opportunity score (< 70).
     */
    public function lowOpportunity(): static
    {
        return $this->state(fn (array $attributes) => [
            'opportunity_score' => fake()->numberBetween(50, 69),
        ]);
    }

    /**
     * Source is Google Suggest.
     */
    public function fromSuggest(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'suggest',
        ]);
    }

    /**
     * Source is competitor analysis.
     */
    public function fromCompetitor(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'competitor',
        ]);
    }

    /**
     * Source is related searches.
     */
    public function fromRelated(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'related',
        ]);
    }
}
