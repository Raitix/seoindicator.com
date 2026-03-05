<?php

namespace App\Services\AI;

use App\Exceptions\InvalidAIResponseException;
use App\Services\AI\DTOs\AnalysisResult;
use Illuminate\Support\Facades\Log;

/**
 * Service for parsing AI responses into structured AnalysisResult DTOs.
 *
 * Handles various response formats:
 * - Markdown code blocks with JSON
 * - Raw JSON responses
 * - JSON embedded in text
 *
 * Validates against schema and attempts repairs for common issues.
 *
 * @see TASK-31-5
 * @see SPEC-31
 */
class AIAnalysisResponseParser
{
    /**
     * JSON Schema for analysis response validation.
     */
    private const SCHEMA = [
        'type' => 'object',
        'required' => ['overall_score', 'overall_summary', 'indicators'],
        'properties' => [
            'overall_score' => [
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 100,
            ],
            'overall_summary' => [
                'type' => 'string',
                'minLength' => 50,
                'maxLength' => 500,
            ],
            'category_scores' => [
                'type' => 'object',
                'properties' => [
                    'content' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                    'technical' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                    'accessibility' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                    'performance' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                ],
            ],
            'indicators' => [
                'type' => 'array',
                'minItems' => 17,
                'maxItems' => 17,
                'items' => [
                    'type' => 'object',
                    'required' => ['id', 'name', 'score', 'passed'],
                    'properties' => [
                        'id' => ['type' => 'string'],
                        'name' => ['type' => 'string'],
                        'score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 3],
                        'passed' => ['type' => 'boolean'],
                        'details' => ['type' => 'object'],
                        'suggestions' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                ],
            ],
            'top_priorities' => [
                'type' => 'array',
                'maxItems' => 5,
            ],
        ],
    ];

    /**
     * Parse raw AI response into structured AnalysisResult.
     *
     * @param string $rawResponse Raw AI response text
     * @return AnalysisResult Structured analysis result
     * @throws InvalidAIResponseException If response cannot be parsed
     */
    public function parse(string $rawResponse): AnalysisResult
    {
        // Extract JSON from response
        $json = $this->extractJson($rawResponse);

        if ($json === null) {
            Log::warning('AIAnalysisResponseParser: No JSON found in response', [
                'response_length' => strlen($rawResponse),
                'response_preview' => substr($rawResponse, 0, 200),
            ]);

            throw InvalidAIResponseException::noJsonFound($rawResponse);
        }

        // Validate schema
        if (!$this->validateSchema($json)) {
            // Attempt repair for common issues
            $json = $this->attemptRepair($json);

            // Re-validate after repair
            if (!$this->validateSchema($json)) {
                Log::warning('AIAnalysisResponseParser: Schema validation failed', [
                    'json_keys' => array_keys($json),
                ]);

                throw InvalidAIResponseException::schemaValidationFailed(
                    $this->getValidationErrors($json),
                    $rawResponse
                );
            }
        }

        return AnalysisResult::fromArray($json);
    }

    /**
     * Extract JSON from various response formats.
     *
     * Handles:
     * - Markdown code blocks with json specifier
     * - Markdown code blocks without language
     * - Raw JSON
     * - JSON embedded in text
     *
     * @param string $response Response text
     * @return array|null Extracted JSON as array, or null if not found
     */
    public function extractJson(string $response): ?array
    {
        // Handle empty or whitespace-only responses
        $trimmed = trim($response);
        if ($trimmed === '') {
            return null;
        }

        // Check if the entire response is valid JSON first
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Reject null
            if ($decoded === null) {
                return null;
            }
            // Reject indexed arrays - we need an object
            if (is_array($decoded) && array_is_list($decoded)) {
                return null;
            }
            // Accept objects (associative arrays)
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Try markdown code block with json specifier first
        if (preg_match('/```json\s*([\s\S]*?)\s*```/i', $response, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !array_is_list($decoded)) {
                return $decoded;
            }
        }

        // Try markdown code block without language specifier
        if (preg_match('/```\s*([\s\S]*?)\s*```/', $response, $matches)) {
            $content = trim($matches[1]);
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !array_is_list($decoded)) {
                return $decoded;
            }
        }

        // Try finding JSON object in response (handles embedded JSON)
        // Match the outermost JSON object - NOT array
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            // Only accept if it's an object (associative array), not an indexed array
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !array_is_list($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Validate data against JSON schema.
     *
     * @param array $data Data to validate
     * @return bool True if valid
     */
    public function validateSchema(array $data): bool
    {
        $errors = $this->getValidationErrors($data);
        return empty($errors);
    }

    /**
     * Get validation errors for data against schema.
     *
     * @param array $data Data to validate
     * @return array<string> List of validation errors
     */
    private function getValidationErrors(array $data): array
    {
        $errors = [];

        // Check required fields
        $required = self::SCHEMA['required'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Validate overall_score
        if (isset($data['overall_score'])) {
            if (!is_int($data['overall_score']) && !(is_float($data['overall_score']) && $data['overall_score'] === floor($data['overall_score']))) {
                $errors[] = 'overall_score must be an integer';
            } elseif ($data['overall_score'] < 0 || $data['overall_score'] > 100) {
                $errors[] = 'overall_score must be between 0 and 100';
            }
        }

        // Validate overall_summary
        if (isset($data['overall_summary'])) {
            if (!is_string($data['overall_summary'])) {
                $errors[] = 'overall_summary must be a string';
            } else {
                $len = strlen($data['overall_summary']);
                if ($len < 50) {
                    $errors[] = "overall_summary must be at least 50 characters (got {$len})";
                }
                if ($len > 500) {
                    $errors[] = "overall_summary must be at most 500 characters (got {$len})";
                }
            }
        }

        // Validate indicators
        if (isset($data['indicators'])) {
            if (!is_array($data['indicators'])) {
                $errors[] = 'indicators must be an array';
            } else {
                $count = count($data['indicators']);
                if ($count < 17) {
                    $errors[] = "indicators must have at least 17 items (got {$count})";
                }
                if ($count > 17) {
                    $errors[] = "indicators must have at most 17 items (got {$count})";
                }

                // Validate each indicator
                foreach ($data['indicators'] as $index => $indicator) {
                    if (!is_array($indicator)) {
                        $errors[] = "indicators[{$index}] must be an object";
                        continue;
                    }

                    // Check required fields
                    foreach (['id', 'name', 'score', 'passed'] as $field) {
                        if (!array_key_exists($field, $indicator)) {
                            $errors[] = "indicators[{$index}] missing required field: {$field}";
                        }
                    }

                    // Validate score
                    if (isset($indicator['score'])) {
                        if (!is_int($indicator['score'])) {
                            $errors[] = "indicators[{$index}].score must be an integer";
                        } elseif ($indicator['score'] < 0 || $indicator['score'] > 3) {
                            $errors[] = "indicators[{$index}].score must be between 0 and 3";
                        }
                    }

                    // Validate passed is boolean
                    if (isset($indicator['passed']) && !is_bool($indicator['passed'])) {
                        $errors[] = "indicators[{$index}].passed must be a boolean";
                    }
                }
            }
        }

        // Validate category_scores if present
        if (isset($data['category_scores'])) {
            if (!is_array($data['category_scores'])) {
                $errors[] = 'category_scores must be an object';
            } else {
                foreach (['content', 'technical', 'accessibility', 'performance'] as $category) {
                    if (isset($data['category_scores'][$category])) {
                        $score = $data['category_scores'][$category];
                        if (!is_int($score)) {
                            $errors[] = "category_scores.{$category} must be an integer";
                        } elseif ($score < 0 || $score > 100) {
                            $errors[] = "category_scores.{$category} must be between 0 and 100";
                        }
                    }
                }
            }
        }

        // Validate top_priorities if present
        if (isset($data['top_priorities'])) {
            if (!is_array($data['top_priorities'])) {
                $errors[] = 'top_priorities must be an array';
            } elseif (count($data['top_priorities']) > 5) {
                $errors[] = 'top_priorities must have at most 5 items';
            }
        }

        return $errors;
    }

    /**
     * Attempt to repair partial or malformed JSON.
     *
     * @param string $json Partial or malformed JSON string
     * @return array|null Repaired JSON as array, or null if unrepairable
     */
    public function repairPartialJson(string $json): ?array
    {
        // Try to decode as-is first
        $decoded = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !array_is_list($decoded)) {
            return $decoded;
        }

        // Try to close unclosed structures
        $openBraces = substr_count($json, '{') - substr_count($json, '}');
        $openBrackets = substr_count($json, '[') - substr_count($json, ']');
        
        // Count quotes - odd number means unclosed string
        $quoteCount = 0;
        $inEscape = false;
        for ($i = 0; $i < strlen($json); $i++) {
            $char = $json[$i];
            if ($inEscape) {
                $inEscape = false;
                continue;
            }
            if ($char === '\\') {
                $inEscape = true;
                continue;
            }
            if ($char === '"') {
                $quoteCount++;
            }
        }
        $openQuotes = $quoteCount % 2;

        $repaired = $json;

        // Close unclosed quotes
        if ($openQuotes !== 0) {
            $repaired .= '"';
        }

        // Close unclosed brackets and braces
        // Need to close in reverse order of opening
        // But for simplicity, close all brackets first, then braces
        for ($i = 0; $i < $openBrackets; $i++) {
            $repaired .= ']';
        }
        for ($i = 0; $i < $openBraces; $i++) {
            $repaired .= '}';
        }

        $decoded = json_decode($repaired, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !array_is_list($decoded)) {
            return $decoded;
        }

        // Try a more aggressive repair - find the last valid complete structure
        // by truncating at the last complete element
        $lastComma = strrpos($json, ',');
        if ($lastComma !== false) {
            $truncated = substr($json, 0, $lastComma);
            
            // Count open/close for truncated
            $openBracesTrunc = substr_count($truncated, '{') - substr_count($truncated, '}');
            $openBracketsTrunc = substr_count($truncated, '[') - substr_count($truncated, ']');
            
            // Close unclosed quotes
            $quoteCountTrunc = 0;
            $inEscape = false;
            for ($i = 0; $i < strlen($truncated); $i++) {
                $char = $truncated[$i];
                if ($inEscape) {
                    $inEscape = false;
                    continue;
                }
                if ($char === '\\') {
                    $inEscape = true;
                    continue;
                }
                if ($char === '"') {
                    $quoteCountTrunc++;
                }
            }
            $openQuotesTrunc = $quoteCountTrunc % 2;
            
            if ($openQuotesTrunc !== 0) {
                $truncated .= '"';
            }
            for ($i = 0; $i < $openBracketsTrunc; $i++) {
                $truncated .= ']';
            }
            for ($i = 0; $i < $openBracesTrunc; $i++) {
                $truncated .= '}';
            }

            $decoded = json_decode($truncated, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !array_is_list($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Attempt to repair common issues in parsed JSON data.
     *
     * @param array $data Parsed JSON data
     * @return array Repaired data
     */
    private function attemptRepair(array $data): array
    {
        $repaired = false;

        // Clamp overall_score to valid range
        if (isset($data['overall_score'])) {
            $score = $data['overall_score'];
            if ($score < 0) {
                $data['overall_score'] = 0;
                $repaired = true;
                Log::warning('AIAnalysisResponseParser: Clamped negative overall_score to 0', [
                    'original_score' => $score,
                ]);
            } elseif ($score > 100) {
                $data['overall_score'] = 100;
                $repaired = true;
                Log::warning('AIAnalysisResponseParser: Clamped overall_score > 100 to 100', [
                    'original_score' => $score,
                ]);
            }
        }

        // Clamp indicator scores to valid range
        if (isset($data['indicators']) && is_array($data['indicators'])) {
            foreach ($data['indicators'] as $index => &$indicator) {
                if (isset($indicator['score'])) {
                    $score = $indicator['score'];
                    if ($score < 0) {
                        $indicator['score'] = 0;
                        $repaired = true;
                        Log::warning("AIAnalysisResponseParser: Clamped negative indicator score to 0", [
                            'indicator_index' => $index,
                            'original_score' => $score,
                        ]);
                    } elseif ($score > 3) {
                        $indicator['score'] = 3;
                        $repaired = true;
                        Log::warning("AIAnalysisResponseParser: Clamped indicator score > 3 to 3", [
                            'indicator_index' => $index,
                            'original_score' => $score,
                        ]);
                    }
                }
            }
        }

        // Pad short summaries
        if (isset($data['overall_summary'])) {
            $len = strlen($data['overall_summary']);
            if ($len > 0 && $len < 50) {
                // Pad with spaces (minimal padding)
                $data['overall_summary'] = str_pad($data['overall_summary'], 50, ' ');
                $repaired = true;
                Log::warning('AIAnalysisResponseParser: Padded short overall_summary to 50 characters', [
                    'original_length' => $len,
                ]);
            }
        }

        // Ensure optional fields have defaults
        if (!isset($data['category_scores'])) {
            $data['category_scores'] = [];
        }
        if (!isset($data['top_priorities'])) {
            $data['top_priorities'] = [];
        }

        return $data;
    }
}
