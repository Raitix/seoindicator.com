<?php

namespace Database\Factories;

use App\Models\AISuggestionCache;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AISuggestionCache>
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
            'request_hash' => hash('sha256', Str::random(64)),
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
            'context_hash' => hash('sha256', Str::random(64)),
            'response_json' => json_encode([
                'score' => $this->faker->numberBetween(0, 100),
                'issues' => $this->faker->randomElements([
                    'Missing keyword in title',
                    'Title too short',
                    'Duplicate content detected',
                    'Missing meta description',
                ], $this->faker->numberBetween(0, 3)),
                'suggestions' => [
                    [
                        'type' => 'improve',
                        'text' => $this->faker->sentence(),
                        'reason' => $this->faker->sentence(),
                    ],
                ],
                'best_practices' => $this->faker->randomElements([
                    'Keep title under 60 characters',
                    'Include primary keyword near the beginning',
                    'Make it compelling for clicks',
                ], 2),
            ]),
            'provider' => $this->faker->randomElement([
                'openrouter',
                'vercel',
            ]),
            'expires_at' => now()->addHours(24),
        ];
    }

    /**
     * Indicate that the cache entry is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHours(1),
        ]);
    }

    /**
     * Indicate that the cache entry is valid (not expired).
     */
    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Indicate that the cache is for a specific indicator.
     */
    public function indicator(string $indicator): static
    {
        return $this->state(fn (array $attributes) => [
            'indicator' => $indicator,
        ]);
    }

    /**
     * Indicate that the cache is for a specific provider.
     */
    public function provider(string $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }

    /**
     * Set a custom TTL for the cache entry.
     */
    public function ttl(int $hours): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours($hours),
        ]);
    }

    /**
     * Create cache with specific request hash.
     */
    public function withHash(string $hash): static
    {
        return $this->state(fn (array $attributes) => [
            'request_hash' => $hash,
        ]);
    }
}
