<?php

namespace Database\Factories;

use App\Models\Credit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Credit>
 */
class CreditFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Credit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => 0.00,
            'total_purchased' => 0.00,
            'total_spent' => 0.00,
        ];
    }

    /**
     * Set a specific balance.
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
        ]);
    }

    /**
     * Set a specific total_purchased.
     */
    public function withPurchased(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'total_purchased' => $amount,
        ]);
    }

    /**
     * Set a specific total_spent.
     */
    public function withSpent(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'total_spent' => $amount,
        ]);
    }

    /**
     * Associate with a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create credit with a positive balance.
     */
    public function withFunds(float $amount = 100.00): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $amount,
            'total_purchased' => $amount,
        ]);
    }

    /**
     * Create credit with zero balance.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => 0.00,
            'total_purchased' => 0.00,
            'total_spent' => 0.00,
        ]);
    }

    /**
     * Create credit with negative balance.
     */
    public function overdrawn(float $amount = -10.00): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $amount,
        ]);
    }
}
