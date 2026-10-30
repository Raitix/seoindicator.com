<?php

namespace Database\Factories;

use App\Models\CrawlJob;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CrawlJob>
 */
class CrawlJobFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CrawlJob::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'job_id' => 'crawl-' . Str::uuid()->toString(),
            'url' => $this->faker->url(),
            'status' => CrawlJob::STATUS_QUEUED,
            'depth' => $this->faker->numberBetween(0, 2),
            'options' => [
                'waitForSelector' => null,
                'waitTime' => 2000,
                'userAgent' => 'SEOIndicator/1.0 Crawler',
                'includeImages' => false,
                'followRedirects' => true,
                'maxRedirects' => 5,
            ],
            'pages_crawled' => 0,
            'total_size' => 0,
            'error' => null,
            'started_at' => null,
            'completed_at' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Indicate that the job is queued.
     */
    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CrawlJob::STATUS_QUEUED,
        ]);
    }

    /**
     * Indicate that the job is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CrawlJob::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the job is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CrawlJob::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'pages_crawled' => $this->faker->numberBetween(1, 50),
            'total_size' => $this->faker->numberBetween(1024, 3145728), // 1KB to 3MB
        ]);
    }

    /**
     * Indicate that the job has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CrawlJob::STATUS_FAILED,
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
            'error' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the job is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CrawlJob::STATUS_CANCELLED,
            'started_at' => now()->subMinutes(1),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the job has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the job has no project.
     */
    public function withoutProject(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => null,
        ]);
    }

    /**
     * Indicate that the job belongs to a specific user.
     */
    public function forUser(User|int $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    /**
     * Indicate that the job belongs to a specific project.
     */
    public function forProject(Project|int $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project instanceof Project ? $project->id : $project,
        ]);
    }

    /**
     * Set a specific depth for the job.
     */
    public function withDepth(int $depth): static
    {
        return $this->state(fn (array $attributes) => [
            'depth' => $depth,
        ]);
    }

    /**
     * Set specific options for the job.
     */
    public function withOptions(array $options): static
    {
        return $this->state(fn (array $attributes) => [
            'options' => $options,
        ]);
    }

    /**
     * Set a specific URL for the job.
     */
    public function withUrl(string $url): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => $url,
        ]);
    }

    /**
     * Set a specific job_id.
     */
    public function withJobId(string $jobId): static
    {
        return $this->state(fn (array $attributes) => [
            'job_id' => $jobId,
        ]);
    }
}
