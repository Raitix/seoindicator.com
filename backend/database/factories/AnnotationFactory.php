<?php

namespace Database\Factories;

use App\Models\Annotation;
use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Annotation>
 */
class AnnotationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Annotation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = Annotation::CATEGORIES;

        return [
            'keyword_id' => Keyword::factory(),
            'project_id' => Project::factory(),
            'date' => fake()->dateTimeBetween('-90 days', '+30 days')->format('Y-m-d'),
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.7)->paragraph(),
            'category' => fake()->randomElement($categories),
        ];
    }

    /**
     * Create an algorithm update annotation.
     */
    public function algorithmUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'algorithm-update',
            'title' => fake()->randomElement([
                'Google Core Update Impact',
                'Helpful Content Update Applied',
                'Link Spam Update Detected',
                'Mobile-First Indexing Change',
            ]),
            'description' => fake()->randomElement([
                'Position dropped significantly after algorithm update.',
                'SERP volatility detected. Monitoring changes.',
                'Algorithm change affected rankings. Reviewing affected pages.',
            ]),
        ]);
    }

    /**
     * Create a content update annotation.
     */
    public function contentUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'content-update',
            'title' => fake()->randomElement([
                'Published new blog post',
                'Updated meta descriptions',
                'Added FAQ schema markup',
                'Refreshed landing page content',
            ]),
            'description' => fake()->randomElement([
                'Content optimization completed. Monitoring for ranking improvements.',
                'Fresh content added to improve relevance signals.',
                'Updated content to better match search intent.',
            ]),
        ]);
    }

    /**
     * Create a link building annotation.
     */
    public function linkBuilding(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'link-building',
            'title' => fake()->randomElement([
                'Guest post published on industry blog',
                'New backlink from authority site',
                'Broken link building success',
                'HARO link acquired',
            ]),
            'description' => fake()->randomElement([
                'New backlink acquired. Monitoring impact on rankings.',
                'Link building campaign showing results.',
                'Quality backlink from relevant source.',
            ]),
        ]);
    }

    /**
     * Create a minimal annotation with only required fields.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => null,
            'category' => 'other',
        ]);
    }
}
