<?php

namespace Database\Factories\AIVisibility;

use App\Models\AIVisibility\AIMention;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIVisibility\AIMention>
 */
class AIMentionFactory extends Factory
{
    protected $model = AIMention::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $entityTypes = [AIMention::ENTITY_TYPE_BRAND, AIMention::ENTITY_TYPE_COMPETITOR];
        $entityType = $this->faker->randomElement($entityTypes);
        
        $mentionTypes = [
            AIMention::MENTION_TYPE_RECOMMENDATION,
            AIMention::MENTION_TYPE_COMPARISON,
            AIMention::MENTION_TYPE_MENTION,
            AIMention::MENTION_TYPE_CITATION,
            AIMention::MENTION_TYPE_NEUTRAL,
        ];
        
        $sentiments = [
            AIMention::SENTIMENT_POSITIVE,
            AIMention::SENTIMENT_NEGATIVE,
            AIMention::SENTIMENT_NEUTRAL,
            AIMention::SENTIMENT_MIXED,
        ];
        
        $platforms = ['chatgpt', 'perplexity', 'claude', 'gemini'];
        
        return [
            'project_id' => AIVisibilityProject::factory(),
            'query_result_id' => null,
            'entity_type' => $entityType,
            'entity_name' => $entityType === AIMention::ENTITY_TYPE_BRAND 
                ? 'SEOIndicator' 
                : $this->faker->randomElement(['Ahrefs', 'SEMrush', 'Moz', 'SurferSEO']),
            'mention_type' => $this->faker->randomElement($mentionTypes),
            'sentiment' => $this->faker->randomElement($sentiments),
            'sentiment_score' => $this->faker->randomFloat(2, 0, 1),
            'context' => $this->faker->sentence(10),
            'platform' => $this->faker->randomElement($platforms),
            'mentioned_at' => now(),
        ];
    }

    /**
     * Indicate this is a brand mention.
     */
    public function brand(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => AIMention::ENTITY_TYPE_BRAND,
            'entity_name' => $attributes['entity_name'] ?? 'SEOIndicator',
        ]);
    }

    /**
     * Indicate this is a competitor mention.
     */
    public function competitor(string $name = null): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => AIMention::ENTITY_TYPE_COMPETITOR,
            'entity_name' => $name ?? $this->faker->randomElement(['Ahrefs', 'SEMrush', 'Moz']),
        ]);
    }

    /**
     * Indicate positive sentiment.
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => AIMention::SENTIMENT_POSITIVE,
            'sentiment_score' => $this->faker->randomFloat(2, 0.6, 1.0),
        ]);
    }

    /**
     * Indicate negative sentiment.
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => AIMention::SENTIMENT_NEGATIVE,
            'sentiment_score' => $this->faker->randomFloat(2, 0.0, 0.4),
        ]);
    }

    /**
     * Indicate neutral sentiment.
     */
    public function neutral(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => AIMention::SENTIMENT_NEUTRAL,
            'sentiment_score' => 0.5,
        ]);
    }

    /**
     * Indicate this is a recommendation.
     */
    public function recommendation(): static
    {
        return $this->state(fn (array $attributes) => [
            'mention_type' => AIMention::MENTION_TYPE_RECOMMENDATION,
        ]);
    }

    /**
     * Indicate this is a comparison.
     */
    public function comparison(): static
    {
        return $this->state(fn (array $attributes) => [
            'mention_type' => AIMention::MENTION_TYPE_COMPARISON,
        ]);
    }

    /**
     * Set the platform.
     */
    public function forPlatform(string $platform): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => $platform,
        ]);
    }

    /**
     * Set the mentioned at date.
     */
    public function mentionedAt(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'mentioned_at' => $date,
        ]);
    }
}
