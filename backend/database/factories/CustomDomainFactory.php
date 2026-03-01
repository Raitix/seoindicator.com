<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\CustomDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for CustomDomain model.
 *
 * @see TASK-15-2
 * @see TEST-15-2
 */
class CustomDomainFactory extends Factory
{
    protected $model = CustomDomain::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'domain' => $this->faker->unique()->domainName,
            'dns_verified' => false,
            'ssl_provisioned' => false,
            'verified_at' => null,
        ];
    }

    /**
     * Account-specific domain.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }

    /**
     * Verified domain (DNS verified).
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'dns_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Pending domain (not verified).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'dns_verified' => false,
            'verified_at' => null,
        ]);
    }

    /**
     * Domain with SSL provisioned.
     */
    public function withSsl(): static
    {
        return $this->state(fn (array $attributes) => [
            'ssl_provisioned' => true,
        ]);
    }

    /**
     * Domain without SSL.
     */
    public function withoutSsl(): static
    {
        return $this->state(fn (array $attributes) => [
            'ssl_provisioned' => false,
        ]);
    }

    /**
     * Fully configured domain (verified + SSL).
     */
    public function fullyConfigured(): static
    {
        return $this->state(fn (array $attributes) => [
            'dns_verified' => true,
            'ssl_provisioned' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Specific domain name.
     */
    public function withDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $domain,
        ]);
    }

    /**
     * Verified at specific time.
     */
    public function verifiedAt(string|\DateTime $date): static
    {
        return $this->state(fn (array $attributes) => [
            'dns_verified' => true,
            'verified_at' => is_string($date) ? $date : $date->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Unicode/IDN domain.
     */
    public function unicodeDomain(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => 'münchen.example.com',
        ]);
    }

    /**
     * Subdomain.
     */
    public function subdomain(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => 'sub.' . $this->faker->domainName,
        ]);
    }

    /**
     * Long domain (near max length).
     */
    public function longDomain(): static
    {
        $longPart = str_repeat('a', 243);
        return $this->state(fn (array $attributes) => [
            'domain' => $longPart . '.com',
        ]);
    }
}
