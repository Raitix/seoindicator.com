<?php

namespace Database\Factories;

use App\Models\AnalysisJob;
use App\Models\User;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalysisJob>
 */
class AnalysisJobFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AnalysisJob::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'site_id' => Site::factory(),
            'status' => AnalysisJob::STATUS_PENDING,
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
            'started_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the job is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisJob::STATUS_PENDING,
            'started_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the job is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisJob::STATUS_RUNNING,
            'started_at' => now()->subMinutes(5),
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the job is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisJob::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
            'cancelled_at' => null,
            'progress_percent' => 100,
        ]);
    }

    /**
     * Indicate that the job is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisJob::STATUS_CANCELLED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => null,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Indicate that the job has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisJob::STATUS_FAILED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => null,
            'cancelled_at' => null,
            'error_message' => fake()->sentence(),
        ]);
    }

    /**
     * Set the total indicators count.
     */
    public function withIndicators(int $total, int $completed = 0, int $failed = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'total_indicators' => $total,
            'completed_indicators' => $completed,
            'failed_indicators' => $failed,
            'progress_percent' => $total > 0 
                ? (int) round((($completed + $failed) / $total) * 100)
                : 0,
        ]);
    }

    /**
     * Create a job for an existing user and site.
     */
    public function forUserAndSite(int $userId, int $siteId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
            'site_id' => $siteId,
        ]);
    }
}
