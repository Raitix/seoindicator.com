<?php

namespace Database\Factories;

use App\Models\AISuggestionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AISuggestionLog>
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
            'user_id' => User::factory(),
            'session_id' => Str::random(32),
            'indicator' => $this->faker->randomElement([
                'title',
                'meta_description',
                'robots',
                'sitemap',
                'headings',
                'image_alt',
                'filenames',
                'url',
                'links',
                '404',
            ]),
            'provider' => $this->faker->randomElement([
                'openrouter',
                'vercel',
                'static_fallback',
            ]),
            'model' => $this->faker->randomElement([
                'meta-llama/llama-3.1-8b-instruct:free',
                'google/gemma-2-9b-it:free',
                'qwen/qwen-2-7b-instruct:free',
                'gpt-4o-mini',
            ]),
            'prompt_tokens' => $this->faker->numberBetween(50, 500),
            'completion_tokens' => $this->faker->numberBetween(100, 1000),
            'latency_ms' => $this->faker->numberBetween(100, 5000),
            'success' => $this->faker->boolean(90),
            'error_message' => null,
            'request_hash' => hash('sha256', Str::random(64)),
        ];
    }

    /**
     * Indicate that the log is for a guest user (no user_id).
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the log represents a failed request.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => false,
            'error_message' => $this->faker->randomElement([
                'Connection timeout',
                'API rate limit exceeded',
                'Invalid response from provider',
                'Authentication failed',
                'Model not available',
            ]),
        ]);
    }

    /**
     * Indicate that the log is for a specific indicator.
     */
    public function indicator(string $indicator): static
    {
        return $this->state(fn (array $attributes) => [
            'indicator' => $indicator,
        ]);
    }

    /**
     * Indicate that the log is for a specific provider.
     */
    public function provider(string $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }

    /**
     * Indicate a slow response (high latency).
     */
    public function slow(): static
    {
        return $this->state(fn (array $attributes) => [
            'latency_ms' => $this->faker->numberBetween(5000, 30000),
        ]);
    }

    /**
     * Indicate a fast response (low latency).
     */
    public function fast(): static
    {
        return $this->state(fn (array $attributes) => [
            'latency_ms' => $this->faker->numberBetween(50, 500),
        ]);
    }
}
