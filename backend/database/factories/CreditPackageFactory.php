<?php

namespace Database\Factories;

use App\Models\CreditPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditPackage>
 */
class CreditPackageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CreditPackage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Starter', 'Basic', 'Pro', 'Enterprise']),
            'credits' => $this->faker->randomFloat(2, 5, 100),
            'price_usd' => $this->faker->randomFloat(2, 5, 100),
            'bonus_percent' => $this->faker->randomElement([0, 0, 0, 5, 10, 15]),
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Indicate that the package is the starter tier.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Starter',
            'credits' => 5.00,
            'price_usd' => 5.00,
            'bonus_percent' => 0,
            'is_popular' => false,
            'sort_order' => 1,
        ]);
    }

    /**
     * Indicate that the package is the basic tier.
     */
    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Basic',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'bonus_percent' => 5,
            'is_popular' => false,
            'sort_order' => 2,
        ]);
    }

    /**
     * Indicate that the package is the pro tier (popular).
     */
    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pro',
            'credits' => 25.00,
            'price_usd' => 25.00,
            'bonus_percent' => 10,
            'is_popular' => true,
            'sort_order' => 3,
        ]);
    }

    /**
     * Indicate that the package is the enterprise tier.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Enterprise',
            'credits' => 50.00,
            'price_usd' => 50.00,
            'bonus_percent' => 15,
            'is_popular' => false,
            'sort_order' => 4,
        ]);
    }

    /**
     * Indicate that the package is marked as popular.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_popular' => true,
        ]);
    }

    /**
     * Indicate that the package is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the package has a specific bonus percentage.
     */
    public function withBonus(int $percent): static
    {
        return $this->state(fn (array $attributes) => [
            'bonus_percent' => $percent,
        ]);
    }

    /**
     * Indicate that the package has a specific price.
     */
    public function withPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price_usd' => $price,
            'credits' => $price, // Credits equal to price by default
        ]);
    }

    /**
     * Indicate that the package has a specific sort order.
     */
    public function withSortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }
}
