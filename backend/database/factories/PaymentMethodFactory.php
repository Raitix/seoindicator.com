<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brands = ['visa', 'mastercard', 'amex', 'discover'];
        $brand = fake()->randomElement($brands);

        return [
            'user_id' => User::factory(),
            'stripe_customer_id' => 'cus_' . fake()->lexify('??????????'),
            'stripe_payment_method_id' => 'pm_' . fake()->lexify('??????????????'),
            'provider' => 'stripe',
            'brand' => $brand,
            'last_four' => fake()->numerify('####'),
            'exp_month' => fake()->numberBetween(1, 12),
            'exp_year' => fake()->numberBetween(now()->year, now()->year + 10),
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the payment method is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the payment method is not the default.
     */
    public function notDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => false,
        ]);
    }

    /**
     * Create a Visa card.
     */
    public function visa(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => 'visa',
            'last_four' => '4242',
        ]);
    }

    /**
     * Create a Mastercard.
     */
    public function mastercard(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => 'mastercard',
            'last_four' => '5555',
        ]);
    }

    /**
     * Create an American Express card.
     */
    public function amex(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => 'amex',
            'last_four' => '3782',
        ]);
    }

    /**
     * Create an expired card.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'exp_month' => 1,
            'exp_year' => now()->year - 1,
        ]);
    }

    /**
     * Create a card expiring soon (within 30 days).
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'exp_month' => now()->month,
            'exp_year' => now()->year,
        ]);
    }

    /**
     * Create a payment method without card details (e.g., bank account).
     */
    public function withoutCardDetails(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => null,
            'last_four' => null,
            'exp_month' => null,
            'exp_year' => null,
        ]);
    }

    /**
     * Create a payment method for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a payment method with a specific Stripe customer ID.
     */
    public function withStripeCustomer(string $stripeCustomerId): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_customer_id' => $stripeCustomerId,
        ]);
    }

    /**
     * Create a PayPal payment method.
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'paypal',
            'stripe_payment_method_id' => null,
            'brand' => 'paypal',
            'exp_month' => null,
            'exp_year' => null,
        ]);
    }
}
