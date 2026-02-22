<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = $this->faker->unique()->domainName();
        
        return [
            'name' => $this->faker->company() . ' SEO Project',
            'domain' => $domain,
        ];
    }

    /**
     * Indicate that the project has no domain.
     */
    public function noDomain(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => null,
        ]);
    }

    /**
     * Indicate that the project has a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Indicate that the project has a specific domain.
     */
    public function withDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $domain,
        ]);
    }
}