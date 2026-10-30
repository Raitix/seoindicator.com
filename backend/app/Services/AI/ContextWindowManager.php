<?php

namespace App\Services\AI;

/**
 * Context Window Manager Service.
 * 
 * Estimates token counts, checks context window limits for different AI models,
 * and selects the optimal model based on content size.
 * 
 * @see TASK-31-2
 * @see SPEC-031
 */
class ContextWindowManager
{
    /**
     * Model context window limits (in tokens).
     */
    private const MODEL_LIMITS = [
        'google/gemini-2.0-flash-exp:free' => 1000000,
        'qwen/qwen-2-7b-instruct:free' => 32768,
        'mistralai/mistral-7b-instruct:free' => 32768,
        'meta-llama/llama-3-8b-instruct:free' => 8192,
    ];

    /**
     * Safety margin to apply to context limits (10%).
     * Protects against edge cases in token counting and model API variations.
     */
    private const SAFETY_MARGIN = 0.10;

    /**
     * Base prompt overhead in tokens.
     * Reserved for system prompt, response schema, and prompt formatting.
     */
    private const BASE_PROMPT_OVERHEAD = 3400;

    /**
     * Estimate token count from content.
     * 
     * Uses character-based estimation: 1 token ≈ 4 characters.
     * This is a reasonable approximation for English text.
     * 
     * @param string $content The content to estimate tokens for
     * @return int Estimated token count
     */
    public function estimateTokens(string $content): int
    {
        if ($content === '') {
            return 0;
        }

        return (int) ceil(strlen($content) / 4);
    }

    /**
     * Get the effective context limit for a model.
     * 
     * Applies safety margin and subtracts base prompt overhead.
     * 
     * @param string $model The model identifier
     * @return int Effective token limit
     */
    public function getEffectiveLimit(string $model): int
    {
        $limit = self::MODEL_LIMITS[$model] ?? 8192;
        
        return (int) ($limit * (1 - self::SAFETY_MARGIN)) - self::BASE_PROMPT_OVERHEAD;
    }

    /**
     * Check if content fits within a model's context window.
     * 
     * @param string $model The model identifier
     * @param int $estimatedTokens Estimated token count
     * @return bool True if content fits, false otherwise
     */
    public function fitsInContext(string $model, int $estimatedTokens): bool
    {
        return $estimatedTokens <= $this->getEffectiveLimit($model);
    }

    /**
     * Select the optimal model for the given token count.
     * 
     * Returns the smallest sufficient model to optimize for cost and speed.
     * Order: Llama → Qwen → Mistral → Gemini
     * 
     * @param int $estimatedTokens Estimated token count
     * @return string The selected model identifier
     */
    public function selectOptimalModel(int $estimatedTokens): string
    {
        // Order from smallest to largest context window
        // This ensures we select the smallest sufficient model
        $models = [
            'meta-llama/llama-3-8b-instruct:free',
            'qwen/qwen-2-7b-instruct:free',
            'mistralai/mistral-7b-instruct:free',
            'google/gemini-2.0-flash-exp:free',
        ];

        foreach ($models as $model) {
            if ($this->fitsInContext($model, $estimatedTokens)) {
                return $model;
            }
        }

        // Fallback: return largest context model (will need truncation)
        return 'google/gemini-2.0-flash-exp:free';
    }

    /**
     * Get all supported model limits.
     * 
     * @return array<string, int> Model identifier => context limit
     */
    public function getModelLimits(): array
    {
        return self::MODEL_LIMITS;
    }
}
