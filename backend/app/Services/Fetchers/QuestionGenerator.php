<?php

namespace App\Services\Fetchers;

/**
 * Generates question-based keyword variations.
 *
 * Creates keyword suggestions by combining a seed keyword with
 * common question prefixes (what, how, why, when, where, who, which).
 *
 * @see TASK-5-5
 * @see SPEC-005
 */
class QuestionGenerator
{
    /**
     * Default question prefixes.
     */
    protected const DEFAULT_PREFIXES = [
        'what is',
        'how to',
        'why',
        'when',
        'where',
        'who',
        'which',
    ];

    /**
     * Question prefixes to use.
     *
     * @var array<string>
     */
    protected array $prefixes;

    /**
     * Create a new QuestionGenerator instance.
     *
     * @param array<string>|null $prefixes Custom prefixes (null for defaults)
     */
    public function __construct(?array $prefixes = null)
    {
        $this->prefixes = $prefixes ?? self::DEFAULT_PREFIXES;
    }

    /**
     * Generate question variations for a keyword.
     *
     * @param string|null $keyword
     * @return array<string> List of question keywords
     */
    public function generate(?string $keyword): array
    {
        // Handle null
        if ($keyword === null) {
            return [];
        }

        // Normalize keyword
        $keyword = trim($keyword);

        // Handle empty or whitespace-only
        if (empty($keyword)) {
            return [];
        }

        $questions = [];

        foreach ($this->prefixes as $prefix) {
            $questions[] = $prefix . ' ' . $keyword;
        }

        return $questions;
    }

    /**
     * Make the generator invokable.
     *
     * @param string|null $keyword
     * @return array
     */
    public function __invoke(?string $keyword): array
    {
        return $this->generate($keyword);
    }

    /**
     * Get the configured prefixes.
     *
     * @return array<string>
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }

    /**
     * Set custom prefixes.
     *
     * @param array<string> $prefixes
     * @return void
     */
    public function setPrefixes(array $prefixes): void
    {
        $this->prefixes = $prefixes;
    }
}
