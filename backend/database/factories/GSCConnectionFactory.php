<?php

namespace Database\Factories;

use App\Models\GSCConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GSCConnection>
 */
class GSCConnectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GSCConnection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = $this->faker->unique()->domainName();
        
        return [
            'user_id' => User::factory(),
            'site_url' => $domain,
            'property_url' => 'https://' . $domain . '/',
            'access_token' => 'mock_access_token_' . $this->faker->uuid(),
            'refresh_token' => 'mock_refresh_token_' . $this->faker->uuid(),
            'token_expires_at' => now()->addHour(),
            'status' => GSCConnection::STATUS_ACTIVE,
            'last_synced_at' => null,
        ];
    }

    /**
     * Indicate that the connection token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_expires_at' => now()->subHour(),
            'status' => GSCConnection::STATUS_EXPIRED,
        ]);
    }

    /**
     * Indicate that the connection is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GSCConnection::STATUS_REVOKED,
        ]);
    }

    /**
     * Indicate that the connection was recently synced.
     */
    public function recentlySynced(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_synced_at' => now()->subMinutes(30),
        ]);
    }

    /**
     * Indicate that the token expires soon.
     */
    public function expiresSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_expires_at' => now()->addMinutes(3),
        ]);
    }
}
