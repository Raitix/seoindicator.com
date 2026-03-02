<?php

namespace Database\Factories;

use App\Models\AISuggestionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<AISuggestionLog>
 */
class AISuggestionLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AISuggestionLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_id' => fake()->optional()->uuid(),
            'indicator' => fake()->randomElement(['title', 'meta_description', 'robots', 'sitemap', 'headings', 'image_alt', 'filenames', 'url', 'links', '404']),
            'provider' => fake()->randomElement(['openrouter', 'vercel', 'static_fallback']),
            'model' => fake()->optional()->randomElement(['llama-3.1-8b-instruct', 'gpt-4o-mini', 'gemma-2-9b-it']),
            'prompt_tokens' => fake()->optional()->numberBetween(50, 500),
            'completion_tokens' => fake()->optional()->numberBetween(20, 300),
            'latency_ms' => fake()->numberBetween(100, 5000),
            'success' => true,
            'error_message' => null,
            'request_hash' => fake()->optional()->sha256(),
        ];
    }

    /**
     * Indicate that the log is for a guest user (no user_id).
     */
    public function guest(): static
    {
        return $this->state([
            'user_id' => null,
            'session_id' => fake()->uuid(),
        ]);
    }

    /**
     * Indicate that the log is for an authenticated user.
     */
    public function forUser(?User $user = null): static
    {
        return $this->state([
            'user_id' => $user?->id ?? User::factory(),
            'session_id' => null,
        ]);
    }

    /**
     * Indicate that the request failed.
     */
    public function failed(): static
    {
        return $this->state([
            'success' => false,
            'error_message' => fake()->randomElement(['Timeout', 'Rate limit exceeded', 'Invalid API key', 'Service unavailable', 'Content policy violation']),
            'completion_tokens' => null,
        ]);
    }

    /**
     * Indicate that the log is for a specific indicator.
     */
    public function indicator(string $indicator): static
    {
        return $this->state(['indicator' => $indicator]);
    }

    /**
     * Indicate that the log is for a specific provider.
     */
    public function provider(string $provider): static
    {
        return $this->state(['provider' => $provider]);
    }

    /**
     * Indicate a slow request (high latency).
     */
    public function slow(): static
    {
        return $this->state(['latency_ms' => fake()->numberBetween(5000, 30000)]);
    }

    /**
     * Indicate a fast request (low latency).
     */
    public function fast(): static
    {
        return $this->state(['latency_ms' => fake()->numberBetween(50, 500)]);
    }
}
