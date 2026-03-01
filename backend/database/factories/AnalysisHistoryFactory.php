<?php

namespace Database\Factories;

use App\Models\AnalysisHistory;
use App\Models\User;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for AnalysisHistory model.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalysisHistory>
 */
class AnalysisHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AnalysisHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'site_id' => null,
            'tool_name' => $this->faker->randomElement([
                'meta-tag-analyzer',
                'keyword-density',
                'heading-analyzer',
                'image-alt-checker',
                'link-analyzer',
            ]),
            'input_url' => $this->faker->url(),
            'status' => 'completed',
            'results' => [
                'title' => $this->faker->sentence(4),
                'meta_description' => $this->faker->sentence(10),
                'h1_count' => $this->faker->numberBetween(0, 3),
                'score' => $this->faker->numberBetween(50, 100),
            ],
            'error_message' => null,
            'analysis_duration_ms' => $this->faker->numberBetween(100, 5000),
            'analyzed_at' => now(),
        ];
    }

    // =========================================================================
    // States
    // =========================================================================

    /**
     * Analysis is completed.
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Analysis is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'results' => null,
            'analysis_duration_ms' => null,
        ]);
    }

    /**
     * Analysis failed.
     */
    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'failed',
            'results' => null,
            'error_message' => $this->faker->randomElement([
                'Connection timeout after 30 seconds',
                'Invalid URL format',
                'SSL certificate verification failed',
                'HTTP 404 Not Found',
                'Rate limit exceeded',
            ]),
            'analysis_duration_ms' => $this->faker->numberBetween(100, 30000),
        ]);
    }

    /**
     * Analysis with populated results.
     */
    public function withResults(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'results' => [
                'title' => $this->faker->sentence(4),
                'meta_description' => $this->faker->sentence(10),
                'meta_keywords' => implode(', ', $this->faker->words(5)),
                'h1_count' => $this->faker->numberBetween(1, 2),
                'h2_count' => $this->faker->numberBetween(2, 8),
                'images_total' => $this->faker->numberBetween(5, 20),
                'images_without_alt' => $this->faker->numberBetween(0, 5),
                'internal_links' => $this->faker->numberBetween(5, 30),
                'external_links' => $this->faker->numberBetween(1, 10),
                'score' => $this->faker->numberBetween(60, 100),
                'issues' => [
                    ['type' => 'warning', 'message' => 'Missing meta keywords'],
                    ['type' => 'info', 'message' => 'Consider adding more internal links'],
                ],
            ],
        ]);
    }

    /**
     * Analysis without site (standalone).
     */
    public function withoutSite(): static
    {
        return $this->state(fn(array $attributes) => [
            'site_id' => null,
        ]);
    }

    /**
     * Analysis with site.
     */
    public function withSite(): static
    {
        return $this->state(fn(array $attributes) => [
            'site_id' => Site::factory(),
        ]);
    }

    /**
     * Long running analysis.
     */
    public function longDuration(): static
    {
        return $this->state(fn(array $attributes) => [
            'analysis_duration_ms' => $this->faker->numberBetween(10000, 60000),
        ]);
    }

    /**
     * Meta tag analyzer tool.
     */
    public function metaTagAnalyzer(): static
    {
        return $this->state(fn(array $attributes) => [
            'tool_name' => 'meta-tag-analyzer',
            'results' => [
                'title' => $this->faker->sentence(4),
                'title_length' => $this->faker->numberBetween(30, 60),
                'meta_description' => $this->faker->sentence(15),
                'meta_description_length' => $this->faker->numberBetween(120, 160),
                'meta_keywords' => implode(', ', $this->faker->words(5)),
                'og_title' => $this->faker->sentence(4),
                'og_description' => $this->faker->sentence(10),
                'og_image' => $this->faker->imageUrl(),
                'twitter_card' => 'summary_large_image',
                'score' => $this->faker->numberBetween(70, 100),
            ],
        ]);
    }

    /**
     * Keyword density tool.
     */
    public function keywordDensity(): static
    {
        return $this->state(fn(array $attributes) => [
            'tool_name' => 'keyword-density',
            'results' => [
                'total_words' => $this->faker->numberBetween(500, 2000),
                'unique_words' => $this->faker->numberBetween(200, 800),
                'keywords' => [
                    ['word' => 'example', 'count' => $this->faker->numberBetween(5, 20), 'density' => $this->faker->randomFloat(2, 0.5, 3.0)],
                    ['word' => 'keyword', 'count' => $this->faker->numberBetween(3, 15), 'density' => $this->faker->randomFloat(2, 0.3, 2.5)],
                    ['word' => 'content', 'count' => $this->faker->numberBetween(2, 10), 'density' => $this->faker->randomFloat(2, 0.2, 2.0)],
                ],
                'readability_score' => $this->faker->randomFloat(1, 50, 90),
                'avg_sentence_length' => $this->faker->randomFloat(1, 12, 25),
            ],
        ]);
    }

    /**
     * Associate with a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Associate with a specific site.
     */
    public function forSite(Site $site): static
    {
        return $this->state(fn(array $attributes) => [
            'site_id' => $site->id,
        ]);
    }
}
