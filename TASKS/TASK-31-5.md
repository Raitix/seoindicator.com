# TASK-31-5: AIAnalysisResponseParser

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`High`

## Specification
SPEC-31: Unified AI Prompt + Context Management

## Description
Create the `AIAnalysisResponseParser` service that parses AI responses, extracts JSON from various formats, validates against schema, and returns structured `AnalysisResult` DTOs.

## Component Location
- **Service:** `backend/app/Services/AI/AIAnalysisResponseParser.php`
- **DTO:** `backend/app/Services/AI/DTOs/AnalysisResult.php`
- **Exception:** `backend/app/Exceptions/InvalidAIResponseException.php`
- **Tests:** `backend/tests/Unit/Services/AI/AIAnalysisResponseParserTest.php`

## Core Functionalities

| Method | Description |
|--------|-------------|
| `parse(string $rawResponse): AnalysisResult` | Parse raw AI response to structured result |
| `extractJson(string $response): array` | Extract JSON from various response formats |
| `validateSchema(array $data): bool` | Validate against JSON schema |
| `repairPartialJson(string $json): ?array` | Attempt to repair malformed JSON |

## Response Formats Handled

| Format | Example | Extraction Method |
|--------|---------|-------------------|
| Markdown code block | ` ```json\n{...}\n``` ` | Regex extract between markers |
| Raw JSON | `{...}` | Direct decode |
| JSON with surrounding text | `Here's the analysis: {...}` | Regex find JSON object |
| Multi-block | Multiple ` ```json ` blocks | Extract first valid |

## JSON Schema Validation

```php
class AIAnalysisResponseParser
{
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
    
    public function parse(string $rawResponse): AnalysisResult
    {
        $json = $this->extractJson($rawResponse);
        
        if (!$json) {
            throw new InvalidAIResponseException('No valid JSON found in response');
        }
        
        $errors = $this->validateSchema($json);
        
        if (!empty($errors)) {
            // Attempt repair for common issues
            $json = $this->attemptRepair($json, $errors);
        }
        
        return AnalysisResult::fromArray($json);
    }
    
    public function extractJson(string $response): ?array
    {
        // Try markdown code block first
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $response, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        // Try finding JSON object in response
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return null;
    }
}
```

## AnalysisResult DTO

```php
class AnalysisResult
{
    public function __construct(
        public readonly int $overallScore,
        public readonly string $overallSummary,
        public readonly array $categoryScores,
        public readonly array $indicators,
        public readonly array $topPriorities,
        public readonly string $model,
        public readonly int $promptTokens,
        public readonly int $responseTokens,
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            overallScore: $data['overall_score'],
            overallSummary: $data['overall_summary'],
            categoryScores: $data['category_scores'] ?? [],
            indicators: $data['indicators'],
            topPriorities: $data['top_priorities'] ?? [],
            model: $data['model'] ?? 'unknown',
            promptTokens: $data['prompt_tokens'] ?? 0,
            responseTokens: $data['response_tokens'] ?? 0,
        );
    }
}
```

## Error Handling

| Error | Response |
|-------|----------|
| No JSON found | `InvalidAIResponseException` with raw response logged |
| Schema validation fails | Attempt repair, log warning |
| Missing required field | Use defaults, log warning |
| Invalid indicator ID | Skip indicator, log warning |
| Score out of range | Clamp to valid range |

## Acceptance Criteria
- [ ] Extracts JSON from markdown code blocks
- [ ] Extracts JSON from raw response
- [ ] Validates against JSON schema
- [ ] Attempts repair for common issues
- [ ] Returns structured `AnalysisResult` DTO
- [ ] Logs warnings for repair attempts
- [ ] Throws `InvalidAIResponseException` for unparseable responses

## Dependencies
- None (can run in parallel)

## Effort Estimate
3 hours

## Comments
_Agent log_

### ProjectManager Prioritization (2026-03-05)
- **Priority:** HIGH (confirmed) - Foundation service for unified AI analysis pipeline
- **Risk:** HIGH (confirmed) - Handles malformed AI responses, JSON parsing errors, schema validation failures
- **Dependencies:** None - standalone parsing service, can proceed immediately
- **Downstream:** TASK-31-7 (AIAnalysisService) depends on this parser
- **TDD Ready:** PreTester can design tests immediately with no blockers
- **Test Focus Areas:**
  - JSON extraction from various formats (markdown, raw, with text, multi-block)
  - Schema validation (required fields, type checking, range validation)
  - Repair logic for malformed JSON
  - Error handling (InvalidAIResponseException scenarios)
  - DTO creation (AnalysisResult from parsed data)
- **Parallel Development:** Can proceed alongside TASK-31-3, TASK-31-4, TASK-31-6
