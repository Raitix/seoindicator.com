<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Project;
use App\Models\Keyword;
use App\Models\UserOnboarding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserOnboarding>
 */
class UserOnboardingFactory extends Factory
{
    protected $model = UserOnboarding::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => null,
            'current_step' => 1,
            'completed_steps' => null,
            'website_url' => null,
            'first_keyword' => null,
            'keyword_id' => null,
            'alert_preferences' => null,
            'completed_at' => null,
            'skipped_at' => null,
        ];
    }

    /**
     * Indicate that the onboarding is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_step' => 5,
            'completed_steps' => [1, 2, 3, 4, 5],
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the onboarding was skipped.
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'skipped_at' => now(),
        ]);
    }

    /**
     * Indicate that the onboarding is at a specific step.
     */
    public function atStep(int $step): static
    {
        return $this->state(fn (array $attributes) => [
            'current_step' => $step,
            'completed_steps' => $step > 1 ? range(1, $step - 1) : null,
        ]);
    }

    /**
     * Indicate that the onboarding has a website URL.
     */
    public function withWebsite(string $url = 'https://example.com'): static
    {
        return $this->state(fn (array $attributes) => [
            'website_url' => $url,
        ]);
    }

    /**
     * Indicate that the onboarding has a first keyword.
     */
    public function withKeyword(string $keyword = 'seo tools'): static
    {
        return $this->state(fn (array $attributes) => [
            'first_keyword' => $keyword,
        ]);
    }

    /**
     * Indicate that the onboarding has alert preferences.
     */
    public function withAlertPreferences(bool $email = true, bool $weekly = true): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_preferences' => [
                'email' => $email,
                'weekly' => $weekly,
            ],
        ]);
    }
}
