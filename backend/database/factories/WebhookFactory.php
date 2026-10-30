<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Webhook model instances for testing.
 */
class WebhookFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Webhook::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true) . ' Webhook',
            'url' => $this->faker->url(),
            'secret' => Webhook::generateSecret(),
            'events' => ['ranking.changed'],
            'enabled' => true,
        ];
    }

    /**
     * Indicate that the webhook is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }

    /**
     * Set specific events for the webhook.
     */
    public function withEvents(array $events): static
    {
        return $this->state(fn (array $attributes) => [
            'events' => $events,
        ]);
    }

    /**
     * Set a specific user for the webhook.
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
