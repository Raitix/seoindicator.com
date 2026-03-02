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
            'balance' => $this->faker->randomFloat(2, 0, 1000),
            'total_purchased' => $this->faker->randomFloat(2, 0, 2000),
            'total_spent' => $this->faker->randomFloat(2, 0, 1000),
        ];
    }

    /**
     * Indicate that the credit has zero balance.
     */
    public function zeroBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => 0,
            'total_purchased' => 0,
            'total_spent' => 0,
        ]);
    }

    /**
     * Indicate that the credit has a specific balance.
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
            'total_purchased' => $balance,
            'total_spent' => 0,
        ]);
    }

    /**
     * Indicate that the credit has a negative balance.
     */
    public function negativeBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $this->faker->randomFloat(2, -100, -1),
        ]);
    }

    /**
     * Indicate that the credit has a large balance.
     */
    public function largeBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => 99999.99,
            'total_purchased' => 99999.99,
        ]);
    }
}
