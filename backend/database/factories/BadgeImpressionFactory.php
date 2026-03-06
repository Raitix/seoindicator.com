<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\BadgeImpression;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BadgeImpression>
 */
class BadgeImpressionFactory extends Factory
{
    protected $model = BadgeImpression::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_id' => Audit::factory(),
            'public_hash' => $this->faker->bothify('????####????####????####????##'),
            'referrer_domain' => $this->faker->domainName(),
            'impression_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'clicked_at' => null,
            'click_target' => null,
            'ip_hash' => hash('sha256', $this->faker->ipv4() . config('app.key')),
        ];
    }

    /**
     * Impression with a click event.
     */
    public function clicked(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicked_at' => $this->faker->dateTimeBetween($attributes['impression_at'] ?? '-1 hour', 'now'),
            'click_target' => $this->faker->randomElement(['audit', 'home']),
        ]);
    }

    /**
     * Impression without a click event.
     */
    public function notClicked(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicked_at' => null,
            'click_target' => null,
        ]);
    }

    /**
     * Link to a specific audit.
     */
    public function forAudit(Audit|int $audit): static
    {
        return $this->state(fn (array $attributes) => [
            'audit_id' => $audit instanceof Audit ? $audit->id : $audit,
            'public_hash' => $audit instanceof Audit && $audit->public_hash 
                ? $audit->public_hash 
                : $this->faker->bothify('????####????####????####????##'),
        ]);
    }

    /**
     * Set a specific referrer domain.
     */
    public function fromDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'referrer_domain' => $domain,
        ]);
    }

    /**
     * Click target is 'audit' (navigated to audit page).
     */
    public function clickAudit(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicked_at' => $this->faker->dateTimeBetween($attributes['impression_at'] ?? '-1 hour', 'now'),
            'click_target' => 'audit',
        ]);
    }

    /**
     * Click target is 'home' (navigated to homepage).
     */
    public function clickHome(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicked_at' => $this->faker->dateTimeBetween($attributes['impression_at'] ?? '-1 hour', 'now'),
            'click_target' => 'home',
        ]);
    }

    /**
     * Impression within recent X days.
     */
    public function recent(int $days = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'impression_at' => $this->faker->dateTimeBetween("-{$days} days", 'now'),
        ]);
    }

    /**
     * Set a specific IP hash.
     */
    public function withIpHash(string $ip): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_hash' => hash('sha256', $ip . config('app.key')),
        ]);
    }

    /**
     * Impression from Google search results.
     */
    public function fromGoogle(): static
    {
        return $this->state(fn (array $attributes) => [
            'referrer_domain' => 'google.com',
        ]);
    }

    /**
     * Direct visit (no referrer).
     */
    public function directVisit(): static
    {
        return $this->state(fn (array $attributes) => [
            'referrer_domain' => null,
        ]);
    }

    /**
     * Impression with specific public hash.
     */
    public function withHash(string $hash): static
    {
        return $this->state(fn (array $attributes) => [
            'public_hash' => $hash,
        ]);
    }

    /**
     * Impression from a specific time.
     */
    public function impressionAt(\DateTimeInterface|string $time): static
    {
        return $this->state(fn (array $attributes) => [
            'impression_at' => $time,
        ]);
    }

    /**
     * Click at a specific time.
     */
    public function clickedAt(\DateTimeInterface|string $time): static
    {
        return $this->state(fn (array $attributes) => [
            'clicked_at' => $time,
            'click_target' => 'audit',
        ]);
    }
}
