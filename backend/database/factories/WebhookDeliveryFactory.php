<?php

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating WebhookDelivery model instances for testing.
 */
class WebhookDeliveryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = WebhookDelivery::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'webhook_id' => Webhook::factory(),
            'event_type' => $this->faker->randomElement([
                'ranking.changed',
                'alert.triggered',
                'report.completed',
            ]),
            'payload' => [
                'test' => true,
                'timestamp' => now()->toIso8601String(),
            ],
            'response_status' => $this->faker->randomElement([200, 201, 204, 400, 500]),
            'response_body' => null,
            'attempt_count' => 1,
            'delivered_at' => now(),
            'next_retry_at' => null,
        ];
    }

    /**
     * Indicate that the delivery was successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => $this->faker->randomElement([200, 201, 204]),
        ]);
    }

    /**
     * Indicate that the delivery failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => $this->faker->randomElement([400, 500, 502, 503]),
            'next_retry_at' => now()->addMinutes(5),
        ]);
    }

    /**
     * Set a specific webhook for the delivery.
     */
    public function forWebhook(int $webhookId): static
    {
        return $this->state(fn (array $attributes) => [
            'webhook_id' => $webhookId,
        ]);
    }
}
