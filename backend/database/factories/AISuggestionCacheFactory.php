<?php

namespace Database\Factories;

use App\Models\AISuggestionCache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<AISuggestionCache>
 */
class AISuggestionCacheFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AISuggestionCache::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_hash' => hash('sha256', fake()->unique()->uuid()),
            'indicator' => fake()->randomElement(['title', 'meta_description', 'robots', 'sitemap', 'headings', 'image_alt', 'filenames', 'url', 'links', '404']),
            'context_hash' => hash('sha256', fake()->uuid()),
            'response_json' => json_encode([
                'score' => fake()->numberBetween(50, 100),
                'suggestions' => [
                    ['type' => 'improve', 'text' => fake()->sentence(), 'reason' => fake()->sentence()],
                ],
            ]),
            'provider' => fake()->randomElement(['openrouter', 'vercel']),
            'expires_at' => now()->addHours(24),
        ];
    }

    /**
     * Indicate that the cache entry is expired.
     */
    public function expired(): static
    {
        return $this->state([
            'expires_at' => now()->subHours(1),
        ]);
    }

    /**
     * Indicate that the cache entry is still valid.
     */
    public function valid(): static
    {
        return $this->state([
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Indicate that the cache is for a specific indicator.
     */
    public function indicator(string $indicator): static
    {
        return $this->state(['indicator' => $indicator]);
    }

    /**
     * Indicate that the cache is for a specific provider.
     */
    public function provider(string $provider): static
    {
        return $this->state(['provider' => $provider]);
    }

    /**
     * Set a custom TTL (time-to-live) in hours.
     */
    public function ttl(int $hours): static
    {
        return $this->state([
            'expires_at' => now()->addHours($hours),
        ]);
    }

    /**
     * Set a specific request hash.
     */
    public function withHash(string $hash): static
    {
        return $this->state(['request_hash' => $hash]);
    }
}
