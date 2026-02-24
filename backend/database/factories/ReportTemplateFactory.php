<?php

namespace Database\Factories;

use App\Models\ReportTemplate;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportTemplate>
 */
class ReportTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'name' => $this->faker->words(3, true) . ' Template',
            'is_default' => false,
            'sections' => ['overview', 'keywords', 'backlinks', 'technical'],
            'branding' => [
                'logo_url' => $this->faker->imageUrl(),
                'primary_color' => $this->faker->hexColor(),
            ],
            'cover_page_text' => $this->faker->sentence(),
            'executive_summary' => $this->faker->paragraph(),
        ];
    }

    /**
     * Indicate that the template is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
