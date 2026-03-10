<?php

namespace Database\Factories;

use App\Models\BusinessLocation;
use App\Models\LocalSEOAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LocalSEOAlert>
 */
class LocalSEOAlertFactory extends Factory
{
    protected $model = LocalSEOAlert::class;

    public function configure(): static
    {
        return $this->afterMaking(function (LocalSEOAlert $alert): void {
            if ($alert->user_id && !User::query()->whereKey($alert->user_id)->exists()) {
                User::factory()->create(['id' => $alert->user_id]);
            }

            if ($alert->location_id && !BusinessLocation::query()->whereKey($alert->location_id)->exists()) {
                BusinessLocation::factory()->create([
                    'id' => $alert->location_id,
                    'user_id' => $alert->user_id,
                ]);
            }
        });
    }

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

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function rankingImproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'ranking_improved',
            'severity' => 'info',
            'title' => 'Ranking improved',
            'message' => 'Your local pack ranking improved.',
        ]);
    }

    public function rankingDropped(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'ranking_dropped',
            'severity' => 'warning',
            'title' => 'Ranking dropped',
            'message' => 'Your local pack ranking dropped.',
        ]);
    }

    public function negativeReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'negative_review',
            'severity' => 'warning',
            'title' => 'Negative review received',
            'message' => 'A negative review has been posted.',
        ]);
    }

    public function gbpDisconnected(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'gbp_disconnected',
            'severity' => 'critical',
            'title' => 'GBP disconnected',
            'message' => 'Your Google Business Profile connection has been lost.',
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }

    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'warning',
        ]);
    }

    public function info(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'info',
        ]);
    }
}
