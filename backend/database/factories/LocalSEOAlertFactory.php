<?php

namespace Database\Factories;

use App\Models\LocalSEOAlert;
use App\Models\User;
use App\Models\BusinessLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LocalSEOAlert>
 */
class LocalSEOAlertFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = LocalSEOAlert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $alertType = fake()->randomElement(LocalSEOAlert::ALERT_TYPES);
        $severity = fake()->randomElement(LocalSEOAlert::SEVERITY_LEVELS);

        return [
            'user_id' => User::factory(),
            'location_id' => BusinessLocation::factory(),
            'alert_type' => $alertType,
            'severity' => $severity,
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'data' => null,
            'is_read' => false,
            'read_at' => null,
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the alert is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Indicate that the alert is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Create a ranking improved alert.
     */
    public function rankingImproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'ranking_improved',
            'severity' => 'info',
            'title' => 'Ranking improved',
            'message' => 'Your local pack ranking improved.',
        ]);
    }

    /**
     * Create a ranking dropped alert.
     */
    public function rankingDropped(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'ranking_dropped',
            'severity' => 'warning',
            'title' => 'Ranking dropped',
            'message' => 'Your local pack ranking dropped.',
        ]);
    }

    /**
     * Create a negative review alert.
     */
    public function negativeReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'negative_review',
            'severity' => 'warning',
            'title' => 'Negative review received',
            'message' => 'A negative review has been posted.',
        ]);
    }

    /**
     * Create a GBP disconnected alert.
     */
    public function gbpDisconnected(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'gbp_disconnected',
            'severity' => 'critical',
            'title' => 'GBP disconnected',
            'message' => 'Your Google Business Profile connection has been lost.',
        ]);
    }

    /**
     * Create a critical severity alert.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }

    /**
     * Create a warning severity alert.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'warning',
        ]);
    }

    /**
     * Create an info severity alert.
     */
    public function info(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'info',
        ]);
    }
}
