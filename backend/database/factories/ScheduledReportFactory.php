<?php

namespace Database\Factories;

use App\Models\ScheduledReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for ScheduledReport.
 */
class ScheduledReportFactory extends Factory
{
    protected $model = ScheduledReport::class;

    public function definition(): array
    {
        return [
            'site_id' => \App\Models\Site::factory(),
            'template_id' => null,
            'name' => $this->faker->words(3, true),
            'frequency' => $this->faker->randomElement(['daily', 'weekly', 'biweekly', 'monthly']),
            'format' => $this->faker->randomElement(['pdf', 'csv']),
            'recipients' => [$this->faker->email, $this->faker->email, $this->faker->email],
            'enabled' => $this->faker->boolean(80),
            'last_run' => null,
            'next_run' => now()->addHours($this->faker->numberBetween(1, 72)),
        ];
    }
}
