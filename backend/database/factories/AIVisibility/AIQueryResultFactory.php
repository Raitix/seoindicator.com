<?php

namespace Database\Factories\AIVisibility;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIVisibility\AIQueryResult>
 */
class AIQueryResultFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\AIVisibility\AIQueryResult::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\AIVisibility\AIVisibilityProject::factory(),
            'platform' => fake()->randomElement(['chatgpt', 'perplexity']),
            'query_text' => fake()->sentence(),
            'response_text' => fake()->paragraph(),
            'model_used' => 'google/gemini-2.0-flash-exp:free',
            'tokens_used' => fake()->numberBetween(100, 1000),
            'latency_ms' => fake()->numberBetween(500, 3000),
            'success' => true,
            'error_message' => null,
            'mentions' => [],
            'sentiment' => fake()->randomElement(['positive', 'neutral', 'negative']),
            'queried_at' => now(),
        ];
    }

    /**
     * Indicate that the query failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => false,
            'error_message' => fake()->sentence(),
            'response_text' => null,
        ]);
    }

    /**
     * Indicate that the query has brand mentions.
     */
    public function withMentions(string $brandName, int $count = 1): static
    {
        return $this->state(function (array $attributes) use ($brandName, $count) {
            $mentions = [];
            for ($i = 0; $i < $count; $i++) {
                $mentions[] = [
                    'brand' => $brandName,
                    'position' => fake()->numberBetween(0, 500),
                    'context' => fake()->sentence(),
                ];
            }
            return ['mentions' => $mentions];
        });
    }

    /**
     * Indicate a specific platform.
     */
    public function forPlatform(string $platform): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => $platform,
        ]);
    }

    /**
     * Indicate a specific sentiment.
     */
    public function withSentiment(string $sentiment): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => $sentiment,
        ]);
    }
}
