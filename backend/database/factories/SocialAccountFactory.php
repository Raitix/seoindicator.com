<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SocialAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'google',
            'provider_id' => fake()->unique()->uuid(),
            'provider_token' => 'test-oauth-token-' . fake()->randomAscii(20),
            'provider_refresh_token' => 'test-refresh-token-' . fake()->randomAscii(20),
            'provider_token_expires_at' => now()->addHour(),
        ];
    }

    /**
     * Indicate the social account is for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate the social account is for Google.
     */
    public function google(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'google',
            'provider_id' => 'google-' . fake()->unique()->randomNumber(8),
        ]);
    }

    /**
     * Indicate the social account is for GitHub.
     */
    public function github(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'github',
            'provider_id' => (string) fake()->unique()->randomNumber(7),
        ]);
    }

    /**
     * Indicate the social account is for Facebook.
     */
    public function facebook(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'facebook',
            'provider_id' => 'fb-' . fake()->unique()->randomNumber(9),
        ]);
    }

    /**
     * Indicate the social account is for LinkedIn.
     */
    public function linkedin(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'linkedin',
            'provider_id' => 'li-' . fake()->unique()->randomNumber(8),
        ]);
    }

    /**
     * Indicate the social account has a token.
     */
    public function withToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_token' => 'ya29.' . fake()->randomAscii(50),
            'provider_token_expires_at' => now()->addHour(),
        ]);
    }

    /**
     * Indicate the social account has no token.
     */
    public function withoutToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_token' => null,
            'provider_token_expires_at' => null,
        ]);
    }

    /**
     * Indicate the social account has a refresh token.
     */
    public function withRefreshToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_refresh_token' => 'refresh-' . fake()->randomAscii(30),
        ]);
    }

    /**
     * Indicate the social account has no refresh token.
     */
    public function withoutRefreshToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_refresh_token' => null,
        ]);
    }

    /**
     * Indicate the token is expired.
     */
    public function expiredToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_token_expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate the token expires soon (5 minutes).
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_token_expires_at' => now()->addMinutes(5),
        ]);
    }

    /**
     * Indicate the token expires in the future.
     */
    public function validToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_token' => 'ya29.' . fake()->randomAscii(50),
            'provider_token_expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Indicate minimal fields only (no tokens).
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_token' => null,
            'provider_refresh_token' => null,
            'provider_token_expires_at' => null,
        ]);
    }
}
