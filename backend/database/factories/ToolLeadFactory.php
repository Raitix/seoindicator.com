<?php

namespace Database\Factories;

use App\Models\ToolLead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ToolLead>
 */
class ToolLeadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ToolLead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'tool' => fake()->randomElement([
                'meta-tag-analyzer',
                'keyword-density',
                'mobile-friendly',
                'social-preview',
            ]),
            'url' => fake()->url(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Indicate the lead is for a specific tool.
     */
    public function forTool(string $tool): static
    {
        return $this->state(fn (array $attributes) => [
            'tool' => $tool,
        ]);
    }

    /**
     * Indicate the lead is for meta-tag-analyzer.
     */
    public function metaTagAnalyzer(): static
    {
        return $this->forTool('meta-tag-analyzer');
    }

    /**
     * Indicate the lead is for keyword-density.
     */
    public function keywordDensity(): static
    {
        return $this->forTool('keyword-density');
    }

    /**
     * Indicate the lead is for mobile-friendly.
     */
    public function mobileFriendly(): static
    {
        return $this->forTool('mobile-friendly');
    }

    /**
     * Indicate the lead is for social-preview.
     */
    public function socialPreview(): static
    {
        return $this->forTool('social-preview');
    }

    /**
     * Indicate the lead has a specific email.
     */
    public function withEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }

    /**
     * Indicate the lead has a specific IP address.
     */
    public function withIp(string $ipAddress): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Indicate the lead has no URL.
     */
    public function withoutUrl(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => null,
        ]);
    }

    /**
     * Indicate the lead is soft deleted (GDPR request).
     */
    public function softDeleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
