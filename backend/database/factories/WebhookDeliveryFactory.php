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
            'response_status' => null,
            'response_body' => null,
            'attempt_count' => 1,
            'delivered_at' => null,
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
            'delivered_at' => now(),
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

    /**
     * Indicate that the delivery is pending (not yet processed).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => null,
            'response_body' => null,
            'delivered_at' => null,
            'next_retry_at' => null,
        ]);
    }

    /**
     * Indicate that the delivery is in retry queue.
     */
    public function retrying(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => 500,
            'next_retry_at' => now()->addMinutes(5),
            'attempt_count' => 1,
            'delivered_at' => null,
        ]);
    }

    /**
     * Indicate that the delivery has exhausted all retries.
     */
    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => 500,
            'next_retry_at' => null,
            'attempt_count' => 5,
            'delivered_at' => null,
        ]);
    }
}
