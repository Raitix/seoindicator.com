<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'name' => fake()->sentence(3),
            'type' => fake()->randomElement(['summary', 'detailed', 'comparison']),
            'format' => fake()->randomElement(['pdf', 'csv']),
            'status' => fake()->randomElement(['pending', 'generating', 'completed', 'failed']),
            'date_range_start' => fake()->dateTimeBetween('-30 days', '-15 days')->format('Y-m-d'),
            'date_range_end' => fake()->dateTimeBetween('-14 days', '-1 day')->format('Y-m-d'),
            'compare_range_start' => fake()->optional()->dateTimeBetween('-60 days', '-45 days')?->format('Y-m-d'),
            'compare_range_end' => fake()->optional()->dateTimeBetween('-44 days', '-31 days')?->format('Y-m-d'),
            'sections' => fake()->optional()->randomElement([
                ['overview', 'keywords', 'traffic'],
                ['overview', 'backlinks', 'competitors'],
                ['keywords', 'rankings'],
            ]),
            'generated_at' => fake()->optional()->dateTimeThisMonth(),
            'file_path' => fake()->optional()->filePath(),
            'file_size' => fake()->optional()->numberBetween(1024, 10485760),
            'error' => null,
        ];
    }

    /**
     * Indicate that the report is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'generated_at' => null,
            'file_path' => null,
            'file_size' => null,
            'error' => null,
        ]);
    }

    /**
     * Indicate that the report is generating.
     */
    public function generating(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'generating',
            'generated_at' => null,
            'file_path' => null,
            'file_size' => null,
            'error' => null,
        ]);
    }

    /**
     * Indicate that the report is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'generated_at' => now(),
            'file_path' => 'reports/report_' . fake()->uuid() . '.pdf',
            'file_size' => fake()->numberBetween(1024, 10485760),
            'error' => null,
        ]);
    }

    /**
     * Indicate that the report has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'generated_at' => null,
            'file_path' => null,
            'file_size' => null,
            'error' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the report is a PDF.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => 'pdf',
        ]);
    }

    /**
     * Indicate that the report is a CSV.
     */
    public function csv(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => 'csv',
        ]);
    }
}
