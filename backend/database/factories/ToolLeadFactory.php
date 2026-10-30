<?php

namespace Database\Factories;

use App\Models\ToolLead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for ToolLead model.
 * 
 * TASK-16-5: Email Capture Modal & API
 * TASK-16-6: Lead Storage Service
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
            'email' => fake()->safeEmail(),
            'tool' => fake()->randomElement(ToolLead::VALID_TOOLS),
            'url' => fake()->url(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Create a lead for a specific tool.
     */
    public function forTool(string $tool): static
    {
        return $this->state(fn (array $attributes) => [
            'tool' => $tool,
        ]);
    }

    /**
     * Create a lead with a specific email.
     */
    public function withEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }

    /**
     * Create a meta-tag-analyzer lead.
     */
    public function metaTagAnalyzer(): static
    {
        return $this->forTool('meta-tag-analyzer');
    }

    /**
     * Create a keyword-density lead.
     */
    public function keywordDensity(): static
    {
        return $this->forTool('keyword-density');
    }

    /**
     * Create a mobile-friendly lead.
     */
    public function mobileFriendly(): static
    {
        return $this->forTool('mobile-friendly');
    }

    /**
     * Create a soft-deleted lead.
     */
    public function softDeleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }

    /**
     * Create a lead without URL.
     */
    public function withoutUrl(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => null,
        ]);
    }

    /**
     * Create a lead with IPv6 address.
     */
    public function withIpv6(): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => fake()->ipv6(),
        ]);
    }
}
