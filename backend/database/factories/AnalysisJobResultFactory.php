<?php

namespace Database\Factories;

use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalysisJobResult>
 */
class AnalysisJobResultFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AnalysisJobResult::class;

    /**
     * Available indicator slugs.
     */
    public const INDICATORS = [
        'meta_tags' => 'Meta Tags',
        'headings' => 'Headings Structure',
        'images' => 'Images Optimization',
        'links' => 'Links Analysis',
        'performance' => 'Performance',
        'mobile' => 'Mobile Friendliness',
        'security' => 'Security',
        'structured_data' => 'Structured Data',
        'content_quality' => 'Content Quality',
        'canonicalization' => 'Canonicalization',
        'robots_txt' => 'Robots.txt',
        'sitemap' => 'XML Sitemap',
        'internal_linking' => 'Internal Linking',
        'external_links' => 'External Links',
        'social_tags' => 'Social Tags',
        'hreflang' => 'Hreflang',
        'crawlability' => 'Crawlability',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $indicatorSlug = fake()->randomElement(array_keys(self::INDICATORS));
        $indicatorName = self::INDICATORS[$indicatorSlug];

        return [
            'job_id' => AnalysisJob::factory(),
            'indicator_slug' => $indicatorSlug,
            'indicator_name' => $indicatorName,
            'status' => AnalysisJobResult::STATUS_PENDING,
            'score' => null,
            'max_score' => null,
            'percentage' => null,
            'data' => null,
            'recommendations' => null,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'processing_time_ms' => null,
        ];
    }

    /**
     * Indicate that the result is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisJobResult::STATUS_PENDING,
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the result is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisJobResult::STATUS_RUNNING,
            'started_at' => now()->subSeconds(30),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the result is completed with a score.
     */
    public function completed(?float $score = null, ?float $maxScore = 100.00): static
    {
        $score = $score ?? fake()->randomFloat(2, 40, 100);
        $percentage = $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0;

        return $this->state(fn (array $attributes) => [
            'status' => AnalysisJobResult::STATUS_COMPLETED,
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'started_at' => now()->subSeconds(fake()->randomElement([15, 30, 45, 60])),
            'completed_at' => now(),
            'processing_time_ms' => fake()->numberBetween(500, 5000),
        ]);
    }

    /**
     * Indicate that the result has failed.
     */
    public function failed(?string $errorMessage = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisJobResult::STATUS_FAILED,
            'error_message' => $errorMessage ?? fake()->sentence(),
            'started_at' => now()->subSeconds(30),
            'completed_at' => now(),
            'processing_time_ms' => fake()->numberBetween(1000, 30000),
        ]);
    }

    /**
     * Indicate that the result was skipped.
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisJobResult::STATUS_SKIPPED,
        ]);
    }

    /**
     * Set a specific indicator.
     */
    public function forIndicator(string $slug): static
    {
        $name = self::INDICATORS[$slug] ?? ucfirst(str_replace('_', ' ', $slug));

        return $this->state(fn (array $attributes) => [
            'indicator_slug' => $slug,
            'indicator_name' => $name,
        ]);
    }

    /**
     * Create a result for a specific job.
     */
    public function forJob(int $jobId): static
    {
        return $this->state(fn (array $attributes) => [
            'job_id' => $jobId,
        ]);
    }

    /**
     * Add recommendations to the result.
     */
    public function withRecommendations(array $recommendations = null): static
    {
        $recommendations = $recommendations ?? [
            [
                'priority' => 'high',
                'message' => fake()->sentence(),
                'details' => fake()->paragraph(),
            ],
            [
                'priority' => 'medium',
                'message' => fake()->sentence(),
                'details' => fake()->paragraph(),
            ],
        ];

        return $this->state(fn (array $attributes) => [
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Add data to the result.
     */
    public function withData(array $data = null): static
    {
        $data = $data ?? [
            'items_checked' => fake()->numberBetween(10, 100),
            'items_passed' => fake()->numberBetween(5, 50),
            'items_failed' => fake()->numberBetween(0, 10),
        ];

        return $this->state(fn (array $attributes) => [
            'data' => $data,
        ]);
    }
}
