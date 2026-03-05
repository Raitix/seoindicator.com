<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Audit>
 */
class AuditFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Audit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'status' => 'completed',
            'pages_crawled' => fake()->numberBetween(1, 100),
            'issues_found' => fake()->numberBetween(0, 50),
            'visibility' => 'private',
            'public_hash' => null,
            'public_hash_created_at' => null,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ];
    }

    /**
     * Indicate that the audit is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'pages_crawled' => 0,
            'issues_found' => 0,
        ]);
    }

    /**
     * Indicate that the audit is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now(),
            'completed_at' => null,
            'pages_crawled' => fake()->numberBetween(1, 50),
            'issues_found' => 0,
        ]);
    }

    /**
     * Indicate that the audit is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the audit has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the audit is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
            'public_hash' => Str::random(32),
            'public_hash_created_at' => now(),
        ]);
    }

    /**
     * Indicate that the audit is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
            'public_hash' => null,
            'public_hash_created_at' => null,
        ]);
    }

    /**
     * Indicate that the audit has a specific public hash.
     */
    public function withPublicHash(string $hash = null): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
            'public_hash' => $hash ?? Str::random(32),
            'public_hash_created_at' => now(),
        ]);
    }

    /**
     * Indicate that the audit has many issues.
     */
    public function withManyIssues(): static
    {
        return $this->state(fn (array $attributes) => [
            'issues_found' => fake()->numberBetween(50, 200),
        ]);
    }

    /**
     * Indicate that the audit has no issues.
     */
    public function withNoIssues(): static
    {
        return $this->state(fn (array $attributes) => [
            'issues_found' => 0,
        ]);
    }

    /**
     * Indicate that the audit crawled many pages.
     */
    public function withManyPages(): static
    {
        return $this->state(fn (array $attributes) => [
            'pages_crawled' => fake()->numberBetween(100, 1000),
        ]);
    }
}
