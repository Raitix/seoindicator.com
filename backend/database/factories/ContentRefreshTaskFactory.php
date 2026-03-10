<?php

namespace Database\Factories;

use App\Models\ContentRefreshTask;
use App\Models\ContentDecayCandidate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentRefreshTask>
 */
class ContentRefreshTaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ContentRefreshTask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $recommendationTypes = [
            'meta_update',
            'content_refresh',
            'technical_fix',
            'link_building',
            'keyword_optimization',
            'schema_addition',
            'image_optimization',
            'structure_improvement',
        ];

        $titles = [
            'Update meta description for better CTR',
            'Refresh outdated content sections',
            'Fix broken internal links',
            'Add relevant internal links',
            'Optimize target keywords in H1-H3',
            'Add FAQ schema markup',
            'Compress and optimize images',
            'Improve content structure with subheadings',
            'Update statistics and data',
            'Add comparison tables',
        ];

        return [
            'candidate_id' => ContentDecayCandidate::factory(),
            'title' => fake()->randomElement($titles),
            'recommendation_type' => fake()->randomElement($recommendationTypes),
            'description' => fake()->optional(70)->paragraph(),
            'notes' => fake()->optional(40)->sentence(),
            'owner_user_id' => fake()->optional(60)->randomElement(User::pluck('id')->toArray() ?: [null]),
            'due_date' => fake()->optional(50)->dateTimeBetween('now', '+30 days')?->format('Y-m-d'),
            'status' => 'todo',
            'source' => fake()->randomElement(['system', 'manual', 'ai']),
            'metadata_json' => fake()->optional(40)->passthrough([
                'priority' => fake()->randomElement(['low', 'medium', 'high']),
                'estimated_time' => fake()->randomElement(['30 min', '1 hour', '2 hours', '4 hours']),
                'impact' => fake()->randomElement(['low', 'medium', 'high']),
            ]),
        ];
    }

    /**
     * Indicate task is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * Indicate task is done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'done',
        ]);
    }

    /**
     * Indicate task is skipped.
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'skipped',
        ]);
    }

    /**
     * Indicate task is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day')?->format('Y-m-d'),
            'status' => 'todo',
        ]);
    }

    /**
     * Indicate task is due soon (within 7 days).
     */
    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => fake()->dateTimeBetween('now', '+7 days')?->format('Y-m-d'),
            'status' => 'todo',
        ]);
    }

    /**
     * Indicate task has no due date.
     */
    public function noDueDate(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => null,
        ]);
    }

    /**
     * Indicate task is system-generated.
     */
    public function systemSource(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'system',
        ]);
    }

    /**
     * Indicate task is manually created.
     */
    public function manualSource(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'manual',
        ]);
    }

    /**
     * Indicate task is AI-generated.
     */
    public function aiSource(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'ai',
        ]);
    }

    /**
     * Indicate task is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata_json' => [
                'priority' => 'high',
                'impact' => 'high',
            ],
        ]);
    }

    /**
     * Indicate task has an owner.
     */
    public function withOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_user_id' => User::factory(),
        ]);
    }

    /**
     * Indicate task is meta update type.
     */
    public function metaUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'meta_update',
            'title' => 'Update meta description for better CTR',
        ]);
    }

    /**
     * Indicate task is content refresh type.
     */
    public function contentRefresh(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'content_refresh',
            'title' => 'Refresh outdated content sections',
        ]);
    }

    /**
     * Indicate task is technical fix type.
     */
    public function technicalFix(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'technical_fix',
            'title' => 'Fix technical SEO issues',
        ]);
    }

    /**
     * Configure for a specific candidate.
     */
    public function forCandidate(int $candidateId): static
    {
        return $this->state(fn (array $attributes) => [
            'candidate_id' => $candidateId,
        ]);
    }

    /**
     * Configure for a specific owner.
     */
    public function forOwner(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_user_id' => $userId,
        ]);
    }
}
