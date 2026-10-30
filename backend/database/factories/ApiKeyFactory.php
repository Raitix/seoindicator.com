<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ApiKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a fake key
        $keyBody = $this->faker->regexify('[A-Za-z0-9_-]{43}');
        $key = 'sk_live_' . $keyBody;
        $prefix = substr($key, 0, 20);

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true) . ' API Key',
            'key_hash' => hash('sha256', $key),
            'key_prefix' => $prefix,
            'permissions' => ['keywords:read', 'rankings:read'],
            'rate_limit' => ApiKey::DEFAULT_RATE_LIMIT,
            'last_used_at' => null,
            'expires_at' => null,
            'revoked_at' => null,
        ];
    }

    /**
     * Indicate that the key is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now()->subDays(5),
        ]);
    }

    /**
     * Indicate that the key is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the key expires soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Indicate that the key has been used recently.
     */
    public function recentlyUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => now()->subHour(),
        ]);
    }

    /**
     * Set specific permissions.
     */
    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Set a custom rate limit.
     */
    public function withRateLimit(int $rateLimit): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_limit' => $rateLimit,
        ]);
    }

    /**
     * Create key for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
