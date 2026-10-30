<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\VisibilityScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VisibilityScore>
 */
class VisibilityScoreFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VisibilityScore::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $positionComponent = $this->faker->randomFloat(2, 0, 40);
        $serpComponent = $this->faker->randomFloat(2, 0, 30);
        $volumeComponent = $this->faker->randomFloat(2, 0, 30);
        $totalScore = (int) min(100, $positionComponent + $serpComponent + $volumeComponent);

        return [
            'project_id' => Project::factory(),
            'score' => $totalScore,
            'position_component' => $positionComponent,
            'serp_component' => $serpComponent,
            'volume_component' => $volumeComponent,
            'keywords_count' => $this->faker->numberBetween(10, 500),
            'calculated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate a high visibility score (70-100).
     */
    public function high(): static
    {
        return $this->state(function (array $attributes) {
            $score = $this->faker->numberBetween(70, 100);
            return [
                'score' => $score,
                'position_component' => $this->faker->randomFloat(2, 25, 40),
                'serp_component' => $this->faker->randomFloat(2, 20, 35),
                'volume_component' => $this->faker->randomFloat(2, 20, 35),
            ];
        });
    }

    /**
     * Indicate a medium visibility score (40-69).
     */
    public function medium(): static
    {
        return $this->state(function (array $attributes) {
            $score = $this->faker->numberBetween(40, 69);
            return [
                'score' => $score,
                'position_component' => $this->faker->randomFloat(2, 10, 30),
                'serp_component' => $this->faker->randomFloat(2, 10, 25),
                'volume_component' => $this->faker->randomFloat(2, 10, 25),
            ];
        });
    }

    /**
     * Indicate a low visibility score (0-39).
     */
    public function low(): static
    {
        return $this->state(function (array $attributes) {
            $score = $this->faker->numberBetween(0, 39);
            return [
                'score' => $score,
                'position_component' => $this->faker->randomFloat(2, 0, 20),
                'serp_component' => $this->faker->randomFloat(2, 0, 15),
                'volume_component' => $this->faker->randomFloat(2, 0, 15),
            ];
        });
    }

    /**
     * Indicate that the score was calculated at a specific date.
     */
    public function calculatedAt(\DateTime|string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'calculated_at' => $date,
        ]);
    }

    /**
     * Indicate that the score belongs to a specific project.
     */
    public function forProject(Project|int $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project instanceof Project ? $project->id : $project,
        ]);
    }

    /**
     * Indicate a specific score value.
     */
    public function withScore(int $score): static
    {
        return $this->state(function (array $attributes) use ($score) {
            // Distribute the score proportionally across components
            $positionComponent = round($score * 0.4, 2);
            $serpComponent = round($score * 0.3, 2);
            $volumeComponent = round($score * 0.3, 2);

            return [
                'score' => min(100, max(0, $score)),
                'position_component' => $positionComponent,
                'serp_component' => $serpComponent,
                'volume_component' => $volumeComponent,
            ];
        });
    }
}
