<?php

namespace Database\Factories;

use App\Models\ReportBranding;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportBranding>
 */
class ReportBrandingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReportBranding::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fontFamilies = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins'];

        return [
            'site_id' => Site::factory(),
            'logo_url' => fake()->optional(0.3)->url(),
            'logo_file' => null,
            'primary_color' => '#2563eb',
            'secondary_color' => '#64748b',
            'font_family' => fake()->randomElement($fontFamilies),
            'company_name' => fake()->optional(0.7)->company(),
            'company_website' => fake()->optional(0.5)->url(),
        ];
    }

    /**
     * Indicate that the branding has a logo.
     */
    public function withLogo(): static
    {
        return $this->state(fn (array $attributes) => [
            'logo_url' => fake()->url(),
            'logo_file' => 'uploads/logos/site-' . ($attributes['site_id'] ?? 1) . '-logo.png',
        ]);
    }

    /**
     * Indicate that the branding has custom colors.
     */
    public function withCustomColors(): static
    {
        return $this->state(fn (array $attributes) => [
            'primary_color' => '#' . fake()->hexColor(6),
            'secondary_color' => '#' . fake()->hexColor(6),
        ]);
    }

    /**
     * Indicate that the branding has company info.
     */
    public function withCompanyInfo(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_name' => fake()->company(),
            'company_website' => fake()->url(),
        ]);
    }
}
