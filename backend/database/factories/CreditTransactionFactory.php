<?php

namespace Database\Factories;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditTransaction>
 */
class CreditTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CreditTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'credit_id' => null,
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'balance_after' => $this->faker->randomFloat(2, 0, 500),
            'type' => 'purchase',
            'description' => $this->faker->sentence(),
            'reference_type' => null,
            'reference_id' => null,
            'payment_provider' => null,
            'payment_id' => null,
            'metadata' => null,
        ];
    }

    /**
     * Create a purchase transaction.
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'purchase',
            'amount' => $this->faker->randomFloat(2, 10, 100),
            'description' => 'Purchased credits via ' . $this->faker->randomElement(['Stripe', 'PayPal']),
            'payment_provider' => $this->faker->randomElement(['stripe', 'paypal']),
            'payment_id' => 'pi_' . $this->faker->uuid(),
        ]);
    }

    /**
     * Create a usage transaction.
     */
    public function usage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'usage',
            'amount' => -$this->faker->randomFloat(2, 1, 50),
            'description' => $this->faker->randomElement([
                'Analysis job #' . $this->faker->randomNumber(4),
                'Keyword research',
                'SEO audit',
                'Report generation',
            ]),
        ]);
    }

    /**
     * Create a refund transaction.
     */
    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'refund',
            'amount' => $this->faker->randomFloat(2, 5, 50),
            'description' => 'Refund for failed analysis job #' . $this->faker->randomNumber(4),
        ]);
    }

    /**
     * Create a bonus transaction.
     */
    public function bonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bonus',
            'amount' => $this->faker->randomFloat(2, 5, 25),
            'description' => $this->faker->randomElement([
                'Welcome bonus',
                'Referral bonus',
                'Promotional credit',
                'Loyalty reward',
            ]),
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
     * Associate with a specific credit record.
     */
    public function forCredit(Credit $credit): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_id' => $credit->id,
            'user_id' => $credit->user_id,
        ]);
    }

    /**
     * Add payment information.
     */
    public function withPayment(string $provider = 'stripe', ?string $paymentId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_provider' => $provider,
            'payment_id' => $paymentId ?? ('pi_' . $this->faker->uuid()),
        ]);
    }

    /**
     * Add reference information.
     */
    public function withReference(string $type = 'App\\Models\\Analysis', ?int $id = null): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_type' => $type,
            'reference_id' => $id ?? $this->faker->randomNumber(5),
        ]);
    }

    /**
     * Add metadata.
     */
    public function withMetadata(array $metadata = []): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge([
                'created_by' => 'system',
                'ip_address' => $this->faker->ipv4(),
            ], $metadata),
        ]);
    }

    /**
     * Create transaction with specific amount.
     */
    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Create transaction with specific balance_after.
     */
    public function withBalanceAfter(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_after' => $balance,
        ]);
    }

    /**
     * Create a large transaction.
     */
    public function large(float $amount = 500.00): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'description' => 'Large credit purchase',
        ]);
    }

    /**
     * Create a small transaction.
     */
    public function small(float $amount = 1.00): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'description' => 'Small usage deduction',
        ]);
    }
}
