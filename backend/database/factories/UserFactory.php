<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'avatar' => null,
            'role' => 'user',
            'last_login_at' => fake()->optional(0.8)->dateTimeBetween('-30 days', 'now'),
            'plan' => fake()->randomElement(['free', 'pro', 'enterprise']),
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indicate that the user is a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'super_admin',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate the user was created via OAuth (no password).
     */
    public function oauthUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => null,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Indicate the user has an avatar.
     */
    public function withAvatar(?string $avatar = null): static
    {
        return $this->state(fn (array $attributes) => [
            'avatar' => $avatar ?? fake()->imageUrl(200, 200, 'people'),
        ]);
    }
}
