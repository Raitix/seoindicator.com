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
        $amount = $this->faker->randomFloat(2, 1, 100);
        $type = $this->faker->randomElement([
            CreditTransaction::TYPE_PURCHASE,
            CreditTransaction::TYPE_USAGE,
            CreditTransaction::TYPE_REFUND,
            CreditTransaction::TYPE_BONUS,
        ]);

        return [
            'user_id' => User::factory(),
            'amount' => $type === CreditTransaction::TYPE_USAGE ? -$amount : $amount,
            'balance_after' => $this->faker->randomFloat(2, 0, 1000),
            'type' => $type,
            'description' => $this->faker->sentence(),
            'reference_type' => null,
            'reference_id' => null,
            'payment_provider' => null,
            'payment_id' => null,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that this is a purchase transaction.
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CreditTransaction::TYPE_PURCHASE,
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'description' => 'Purchased credits via ' . $this->faker->randomElement(['stripe', 'paypal']),
            'payment_provider' => $this->faker->randomElement(['stripe', 'paypal']),
            'payment_id' => 'pi_' . $this->faker->uuid(),
        ]);
    }

    /**
     * Indicate that this is a usage transaction.
     */
    public function usage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CreditTransaction::TYPE_USAGE,
            'amount' => -$this->faker->randomFloat(2, 0.01, 50),
            'description' => 'Analysis job #' . $this->faker->randomNumber(4),
            'reference_type' => 'App\\Models\\AnalysisJob',
            'reference_id' => $this->faker->randomNumber(4),
            'metadata' => ['model' => 'gpt-4', 'tokens' => $this->faker->numberBetween(100, 10000)],
        ]);
    }

    /**
     * Indicate that this is a refund transaction.
     */
    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CreditTransaction::TYPE_REFUND,
            'amount' => $this->faker->randomFloat(2, 1, 50),
            'description' => 'Refund for failed analysis',
            'reference_type' => 'App\\Models\\AnalysisJob',
            'reference_id' => $this->faker->randomNumber(4),
            'metadata' => ['reason' => 'timeout'],
        ]);
    }

    /**
     * Indicate that this is a bonus transaction.
     */
    public function bonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CreditTransaction::TYPE_BONUS,
            'amount' => $this->faker->randomFloat(2, 5, 100),
            'description' => $this->faker->randomElement([
                'Welcome bonus',
                'Referral bonus',
                'Promotional credit',
            ]),
            'metadata' => ['campaign' => $this->faker->slug()],
        ]);
    }

    /**
     * Indicate that this transaction was paid via Stripe.
     */
    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_' . $this->faker->uuid(),
        ]);
    }

    /**
     * Indicate that this transaction was paid via PayPal.
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_provider' => 'paypal',
            'payment_id' => 'PAY-' . $this->faker->uuid(),
        ]);
    }

    /**
     * Indicate that this transaction has metadata.
     */
    public function withMetadata(array $metadata = []): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge([
                'key' => $this->faker->word(),
                'value' => $this->faker->sentence(),
            ], $metadata),
        ]);
    }

    /**
     * Set a specific amount for the transaction.
     */
    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Set a specific user for the transaction.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
