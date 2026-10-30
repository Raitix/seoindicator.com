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
            'name' => $this->faker->words(2, true),
            'credits' => $this->faker->randomFloat(2, 5, 100),
            'price_usd' => $this->faker->randomFloat(2, 5, 100),
            'bonus_percent' => $this->faker->numberBetween(0, 20),
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Starter tier package ($5, 0% bonus).
     *
     * @return static
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Starter',
            'credits' => 5.00,
            'price_usd' => 5.00,
            'bonus_percent' => 0,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Basic tier package ($10, 5% bonus).
     *
     * @return static
     */
    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Basic',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'bonus_percent' => 5,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    /**
     * Pro tier package ($25, 10% bonus, popular).
     *
     * @return static
     */
    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pro',
            'credits' => 25.00,
            'price_usd' => 25.00,
            'bonus_percent' => 10,
            'is_popular' => true,
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }

    /**
     * Enterprise tier package ($50, 15% bonus).
     *
     * @return static
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Enterprise',
            'credits' => 50.00,
            'price_usd' => 50.00,
            'bonus_percent' => 15,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 4,
        ]);
    }

    /**
     * Mark package as popular.
     *
     * @return static
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_popular' => true,
        ]);
    }

    /**
     * Mark package as inactive.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set custom bonus percentage.
     *
     * @param int $percent
     * @return static
     */
    public function withBonus(int $percent): static
    {
        return $this->state(fn (array $attributes) => [
            'bonus_percent' => $percent,
        ]);
    }

    /**
     * Set custom price.
     *
     * @param float $price
     * @return static
     */
    public function withPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price_usd' => $price,
            'credits' => $price, // credits = price by default
        ]);
    }

    /**
     * Set custom sort order.
     *
     * @param int $order
     * @return static
     */
    public function withSortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }
}
