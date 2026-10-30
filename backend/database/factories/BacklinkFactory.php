<?php

namespace Database\Factories;

use App\Models\Backlink;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Backlink>
 */
class BacklinkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Backlink::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sourceDomain = $this->faker->domainName();
        
        return [
            'project_id' => Project::factory(),
            'source_url' => 'https://' . $sourceDomain . '/' . $this->faker->slug(),
            'source_domain' => $sourceDomain,
            'target_url' => 'https://myproject.com/' . $this->faker->slug(),
            'anchor_text' => $this->faker->optional()->words(3, true),
            'link_type' => $this->faker->randomElement(['dofollow', 'nofollow', 'unknown']), // NOT NULL with default
            'first_seen_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_checked_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'is_active' => true,
        ];
    }

    /**
     * Indicate the backlink is dofollow.
     */
    public function dofollow(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => 'dofollow',
        ]);
    }

    /**
     * Indicate the backlink is nofollow.
     */
    public function nofollow(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => 'nofollow',
        ]);
    }

    /**
     * Indicate the backlink is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate the backlink was recently checked.
     */
    public function recentlyChecked(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_checked_at' => now(),
        ]);
    }
}
