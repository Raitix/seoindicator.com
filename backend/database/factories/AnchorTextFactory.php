<?php

namespace Database\Factories;

use App\Models\AnchorText;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnchorText>
 */
class AnchorTextFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AnchorText::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'anchor_text' => $this->faker->words(3, true),
            'backlinks_count' => $this->faker->numberBetween(1, 100),
            'is_branded' => false,
            'is_exact_match' => false,
        ];
    }

    /**
     * Indicate the anchor is branded.
     */
    public function branded(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_branded' => true,
            'anchor_text' => 'BrandName',
        ]);
    }

    /**
     * Indicate the anchor is exact match.
     */
    public function exactMatch(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_exact_match' => true,
            'anchor_text' => 'target keyword',
        ]);
    }

    /**
     * Indicate the anchor has many backlinks.
     */
    public function manyBacklinks(): static
    {
        return $this->state(fn (array $attributes) => [
            'backlinks_count' => $this->faker->numberBetween(50, 200),
        ]);
    }

    /**
     * Indicate the anchor is a URL.
     */
    public function urlAnchor(): static
    {
        return $this->state(fn (array $attributes) => [
            'anchor_text' => $this->faker->url(),
        ]);
    }
}
