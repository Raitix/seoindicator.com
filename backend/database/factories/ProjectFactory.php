<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
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
            'user_id' => User::factory(),
            'name' => $this->faker->company() . ' SEO Project',
            'domain' => $domain,
            'notes' => $this->faker->optional()->sentence(),
            'is_default' => false,
            'archived_at' => null,
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

    /**
     * Indicate that the project is the default project for the user.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the project is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the project has specific notes.
     */
    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }

    /**
     * Indicate that the project belongs to a specific user.
     */
    public function forUser(User|int $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user instanceof User ? $user->id : $user,
        ]);
    }
}