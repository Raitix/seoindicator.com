<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\IssueType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditIssue>
 */
class AuditIssueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AuditIssue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $severities = ['critical', 'warning', 'notice'];

        return [
            'audit_id' => Audit::factory(),
            'url' => fake()->url(),
            'issue_type_id' => IssueType::factory(),
            'severity' => fake()->randomElement($severities),
            'description' => fake()->sentence(),
            'recommendation' => fake()->paragraph(),
            'status' => 'open',
        ];
    }

    /**
     * Indicate that the issue is critical.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }

    /**
    /**
     * Indicate that the issue is a warning.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'warning',
        ]);
    }

    /**
     * Indicate that the issue is a notice.
     */
    public function notice(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'notice',
        ]);
    }

    /**
     * Indicate that the issue is fixed.
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'fixed',
        ]);
    }

    /**
     * Indicate that the issue is ignored.
     */
    public function ignored(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ignored',
        ]);
    }
}
