<?php

namespace Database\Factories;

use App\Models\AnalysisCost;
use App\Models\AnalysisHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for AnalysisCost model.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalysisCost>
 */
class AnalysisCostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AnalysisCost::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'analysis_id' => AnalysisHistory::factory(),
            'cost_type' => $this->faker->randomElement([
                'compute',
                'api_call',
                'storage',
                'variable',
            ]),
            'cost_units' => $this->faker->randomFloat(4, 0.1, 100),
            'cost_currency' => 'USD',
            'cost_amount' => $this->faker->randomFloat(6, 0.001, 1),
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    // =========================================================================
    // States
    // =========================================================================

    /**
     * API call cost type.
     */
    public function apiCall(): static
    {
        return $this->state(fn(array $attributes) => [
            'cost_type' => 'api_call',
            'description' => 'External API request',
        ]);
    }

    /**
     * Compute cost type.
     */
    public function compute(): static
    {
        return $this->state(fn(array $attributes) => [
            'cost_type' => 'compute',
            'description' => 'CPU processing time',
        ]);
    }

    /**
     * Storage cost type.
     */
    public function storage(): static
    {
        return $this->state(fn(array $attributes) => [
            'cost_type' => 'storage',
            'cost_units' => $this->faker->randomFloat(4, 1, 1000), // KB/MB
            'description' => 'Data storage used',
        ]);
    }

    /**
     * Variable cost type.
     */
    public function variable(): static
    {
        return $this->state(fn(array $attributes) => [
            'cost_type' => 'variable',
            'description' => 'Variable resource usage',
        ]);
    }

    /**
     * High cost (> $1.00).
     */
    public function highCost(): static
    {
        return $this->state(fn(array $attributes) => [
            'cost_amount' => $this->faker->randomFloat(6, 1.0, 10.0),
        ]);
    }

    /**
     * Low cost (< $0.01).
     */
    public function lowCost(): static
    {
        return $this->state(fn(array $attributes) => [
            'cost_amount' => $this->faker->randomFloat(6, 0.000001, 0.009999),
        ]);
    }

    /**
     * EUR currency.
     */
    public function eur(): static
    {
        return $this->state(fn(array $attributes) => [
            'cost_currency' => 'EUR',
        ]);
    }

    /**
     * Zero cost.
     */
    public function zeroCost(): static
    {
        return $this->state(fn(array $attributes) => [
            'cost_amount' => 0,
            'cost_units' => 0,
        ]);
    }

    /**
     * For a specific analysis.
     */
    public function forAnalysis(int $analysisId): static
    {
        return $this->state(fn(array $attributes) => [
            'analysis_id' => $analysisId,
        ]);
    }

    /**
     * Google CSE API call cost.
     */
    public function googleCseCall(): static
    {
        return $this->state(fn(array $attributes) => [
            'cost_type' => 'api_call',
            'cost_units' => 1,
            'cost_amount' => 0.00, // Free tier
            'description' => 'Google Custom Search API call',
        ]);
    }

    /**
     * SERP API call cost (estimated).
     */
    public function serpApiCall(): static
    {
        return $this->state(fn(array $attributes) => [
            'cost_type' => 'api_call',
            'cost_units' => 1,
            'cost_amount' => 0.02,
            'description' => 'SERP API request',
        ]);
    }
}
