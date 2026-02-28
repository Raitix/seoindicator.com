<?php

namespace Database\Factories;

use App\Models\ReferringDomain;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReferringDomain>
 */
class ReferringDomainFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReferringDomain::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'domain' => $this->faker->domainName(),
            'backlinks_count' => $this->faker->numberBetween(1, 100), // NOT NULL with default 0
            'domain_trust_score' => $this->faker->numberBetween(0, 100), // NOT NULL with default 0
            'first_seen_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_seen_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate the domain has high trust.
     */
    public function highTrust(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain_trust_score' => $this->faker->numberBetween(70, 100),
        ]);
    }

    /**
     * Indicate the domain has low trust.
     */
    public function lowTrust(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain_trust_score' => $this->faker->numberBetween(0, 30),
        ]);
    }

    /**
     * Indicate the domain has many backlinks.
     */
    public function manyBacklinks(): static
    {
        return $this->state(fn (array $attributes) => [
            'backlinks_count' => $this->faker->numberBetween(50, 200),
        ]);
    }
}
