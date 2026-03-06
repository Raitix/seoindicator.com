<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\PublicAudit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PublicAudit>
 */
class PublicAuditFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PublicAudit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_id' => Audit::factory(),
            'public_hash' => null,
            'visibility' => 'private',
            'public_hash_created_at' => null,
        ];
    }

    /**
     * Indicate that the public audit is published (public).
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
            'public_hash' => $attributes['public_hash'] ?? Str::random(32),
            'public_hash_created_at' => now(),
        ]);
    }

    /**
     * Indicate that the public audit is private.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
            'public_hash' => null,
            'public_hash_created_at' => null,
        ]);
    }

    /**
     * Indicate that the public audit has a specific hash.
     */
    public function withHash(string $hash = null): static
    {
        return $this->state(fn (array $attributes) => [
            'public_hash' => $hash ?? Str::random(32),
            'visibility' => 'public',
            'public_hash_created_at' => now(),
        ]);
    }

    /**
     * Indicate that the public audit belongs to a specific audit.
     */
    public function forAudit(Audit $audit): static
    {
        return $this->state(fn (array $attributes) => [
            'audit_id' => $audit->id,
        ]);
    }

    /**
     * Indicate that the public audit was published at a specific time.
     */
    public function publishedAt(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
            'public_hash' => $attributes['public_hash'] ?? Str::random(32),
            'public_hash_created_at' => $date,
        ]);
    }

    /**
     * Indicate that the public audit was published days ago.
     */
    public function publishedDaysAgo(int $days): static
    {
        return $this->publishedAt(now()->subDays($days));
    }

    /**
     * Indicate that the public audit has a recently created hash.
     */
    public function recentlyPublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
            'public_hash' => $attributes['public_hash'] ?? Str::random(32),
            'public_hash_created_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * Indicate that the public audit has an old hash.
     */
    public function oldHash(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
            'public_hash' => $attributes['public_hash'] ?? Str::random(32),
            'public_hash_created_at' => now()->subDays(30),
        ]);
    }
}
