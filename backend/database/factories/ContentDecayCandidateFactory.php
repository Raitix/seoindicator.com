<?php

namespace Database\Factories;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentDecayCandidate>
 */
class ContentDecayCandidateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ContentDecayCandidate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pageUrl = fake()->url() . '/page/' . fake()->slug();

        return [
            'project_id' => ContentDecayProject::factory(),
            'page_url' => $pageUrl,
            'page_url_hash' => hash('sha256', $pageUrl),
            'page_title' => fake()->sentence(6),
            'page_type' => fake()->randomElement(['blog', 'landing', 'product', 'tool', 'compare', 'docs', 'home', 'other']),
            'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'decay_score' => fake()->randomFloat(2, 0, 100),
            'detection_confidence' => fake()->randomFloat(2, 0.70, 1.00),
            'lost_click_estimate' => fake()->numberBetween(0, 1000),
            'recovery_upside_estimate' => fake()->numberBetween(0, 1500),
            'primary_reason_code' => fake()->randomElement([
                'clicks_down',
                'impressions_down',
                'ctr_down',
                'avg_position_down',
                'tracked_rankings_down',
                'audit_issues_present',
                'stale_content',
            ]),
            'reason_codes' => fake()->randomElements([
                'clicks_down',
                'impressions_down',
                'ctr_down',
                'avg_position_down',
                'tracked_rankings_down',
                'audit_issues_present',
                'stale_content',
                'ai_visibility_down',
                'competitor_gain_suspected',
                'internal_link_weakness',
            ], fake()->numberBetween(1, 4)),
            'signal_sources' => fake()->randomElements(['gsc', 'rankings', 'audit', 'annotations', 'ai_visibility'], fake()->numberBetween(1, 3)),
            'evidence_json' => [
                'clicks_before' => fake()->numberBetween(500, 2000),
                'clicks_after' => fake()->numberBetween(100, 500),
                'impressions_before' => fake()->numberBetween(5000, 20000),
                'impressions_after' => fake()->numberBetween(2000, 10000),
            ],
            'status' => 'backlog',
            'first_detected_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'last_detected_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'planned_at' => null,
            'started_at' => null,
            'refreshed_at' => null,
            'recovered_at' => null,
            'ignored_at' => null,
        ];
    }

    /**
     * Indicate candidate is in planned status.
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planned',
            'planned_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate candidate is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'planned_at' => fake()->dateTimeBetween('-14 days', '-7 days'),
            'started_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate candidate is refreshed.
     */
    public function refreshed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refreshed',
            'planned_at' => fake()->dateTimeBetween('-21 days', '-14 days'),
            'started_at' => fake()->dateTimeBetween('-14 days', '-7 days'),
            'refreshed_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate candidate is recovered.
     */
    public function recovered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'recovered',
            'planned_at' => fake()->dateTimeBetween('-30 days', '-21 days'),
            'started_at' => fake()->dateTimeBetween('-21 days', '-14 days'),
            'refreshed_at' => fake()->dateTimeBetween('-14 days', '-7 days'),
            'recovered_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate candidate is ignored.
     */
    public function ignored(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ignored',
            'ignored_at' => fake()->dateTimeBetween('-14 days', 'now'),
        ]);
    }

    /**
     * Indicate critical severity.
     */
    public function criticalSeverity(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
            'decay_score' => fake()->randomFloat(2, 70, 100),
        ]);
    }

    /**
     * Indicate high severity.
     */
    public function highSeverity(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'high',
            'decay_score' => fake()->randomFloat(2, 40, 69),
        ]);
    }

    /**
     * Indicate medium severity.
     */
    public function mediumSeverity(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'medium',
            'decay_score' => fake()->randomFloat(2, 20, 39),
        ]);
    }

    /**
     * Indicate low severity.
     */
    public function lowSeverity(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'low',
            'decay_score' => fake()->randomFloat(2, 0, 19),
        ]);
    }

    /**
     * Indicate high decay score.
     */
    public function highDecayScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'decay_score' => fake()->randomFloat(2, 75, 100),
            'severity' => 'critical',
        ]);
    }

    /**
     * Indicate blog page type.
     */
    public function blogPage(): static
    {
        return $this->state(fn (array $attributes) => [
            'page_type' => 'blog',
        ]);
    }

    /**
     * Indicate product page type.
     */
    public function productPage(): static
    {
        return $this->state(fn (array $attributes) => [
            'page_type' => 'product',
        ]);
    }

    /**
     * Indicate landing page type.
     */
    public function landingPage(): static
    {
        return $this->state(fn (array $attributes) => [
            'page_type' => 'landing',
        ]);
    }

    /**
     * Indicate high lost clicks.
     */
    public function highLostClicks(): static
    {
        return $this->state(fn (array $attributes) => [
            'lost_click_estimate' => fake()->numberBetween(500, 2000),
            'recovery_upside_estimate' => fake()->numberBetween(750, 3000),
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ContentDecayCandidate $candidate): void {
            // Auto-generate page_url_hash if not set
            if ($candidate->page_url && !$candidate->page_url_hash) {
                $candidate->page_url_hash = hash('sha256', $candidate->page_url);
            }
        });
    }
}
