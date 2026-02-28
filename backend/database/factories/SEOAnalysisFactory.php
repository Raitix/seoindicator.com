<?php

namespace Database\Factories;

use App\Models\SEOAnalysis;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SEOAnalysis>
 */
class SEOAnalysisFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SEOAnalysis::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = $this->faker->domainName;

        return [
            'user_id' => null,
            'session_id' => null,
            'domain' => $domain,
            'normalized_url' => 'https://' . $domain,
            'overall_score' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'error_message' => null,
            'analysis_data' => [
                'overallScore' => $this->faker->numberBetween(0, 100),
                'categoryScores' => [
                    'content' => $this->faker->numberBetween(0, 100),
                    'technical' => $this->faker->numberBetween(0, 100),
                    'accessibility' => $this->faker->numberBetween(0, 100),
                    'performance' => $this->faker->numberBetween(0, 100),
                ],
                'indicators' => [],
            ],
            'checked_at' => $this->faker->optional()->dateTime(),
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+1 day'),
        ];
    }

    /**
     * Indicate that the analysis belongs to a user.
     */
    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the analysis is for a session (anonymous).
     */
    public function forSession(string $sessionId): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Indicate that the analysis is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'checked_at' => null,
        ]);
    }

    /**
     * Indicate that the analysis is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'checked_at' => null,
        ]);
    }

    /**
     * Indicate that the analysis is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'checked_at' => now(),
            'expires_at' => now()->addDay(),
        ]);
    }

    /**
     * Indicate that the analysis has failed.
     */
    public function failed(string $errorMessage = 'Analysis failed'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Indicate that the analysis has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'expires_at' => now()->subHour(),
            'checked_at' => now()->subHours(25),
        ]);
    }

    /**
     * Indicate that the analysis has a high score.
     */
    public function highScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'overall_score' => $this->faker->numberBetween(80, 100),
            'status' => 'completed',
            'checked_at' => now(),
            'expires_at' => now()->addDay(),
        ]);
    }

    /**
     * Indicate that the analysis has a low score.
     */
    public function lowScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'overall_score' => $this->faker->numberBetween(0, 40),
            'status' => 'completed',
            'checked_at' => now(),
            'expires_at' => now()->addDay(),
        ]);
    }

    /**
     * Set a specific domain for the analysis.
     */
    public function forDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $domain,
            'normalized_url' => 'https://' . $domain,
        ]);
    }

    /**
     * Set specific analysis data.
     */
    public function withData(array $data): static
    {
        return $this->state(fn (array $attributes) => [
            'analysis_data' => $data,
        ]);
    }
}
