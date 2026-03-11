<?php

namespace Database\Factories;

use App\Models\ContentDecayCandidate;
use App\Models\ContentRefreshTask;
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
            'update_content',
            'add_keywords',
            'fix_technical',
            'update_meta',
            'add_internal_links',
            'consolidate',
            'redirect',
            'other',
        ];

        return [
            'candidate_id' => ContentDecayCandidate::factory(),
            'title' => fake()->sentence(4),
            'recommendation_type' => fake()->randomElement($recommendationTypes),
            'description' => fake()->optional(0.7)->paragraph(),
            'notes' => fake()->optional(0.3)->sentence(),
            'owner_user_id' => null,
            'due_date' => fake()->optional(0.6)->dateTimeBetween('now', '+14 days')?->format('Y-m-d'),
            'status' => 'pending',
            'source' => 'system',
            'metadata_json' => null,
        ];
    }

    /**
     * Indicate the task is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * Indicate the task is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Alias for completed() - backward compatibility.
     */
    public function done(): static
    {
        return $this->completed();
    }

    /**
     * Indicate the task is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Alias for cancelled() - backward compatibility.
     */
    public function skipped(): static
    {
        return $this->cancelled();
    }

    /**
     * Indicate the task is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->subDays(3)->format('Y-m-d'),
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate the task is due soon.
     */
    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->addDays(2)->format('Y-m-d'),
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate the task has high priority metadata.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata_json' => ['priority' => 'high'],
        ]);
    }

    /**
     * Indicate the task is for a meta update.
     */
    public function metaUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'update_meta',
            'title' => 'Update meta description and title',
        ]);
    }

    /**
     * Indicate the task is for content refresh.
     */
    public function contentRefresh(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'update_content',
            'title' => 'Refresh outdated content',
        ]);
    }

    /**
     * Indicate the task has an owner.
     */
    public function withOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_user_id' => User::factory(),
        ]);
    }

    /**
     * Indicate manual source.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'manual',
        ]);
    }

    /**
     * Indicate AI source.
     */
    public function aiSuggestion(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'ai',
        ]);
    }
}
