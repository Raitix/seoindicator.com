<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\AIAnalysisResponseParser;
use App\Services\AI\DTOs\AnalysisResult;
use App\Exceptions\InvalidAIResponseException;
use Illuminate\Support\Facades\Log;

/**
 * Unit tests for AIAnalysisResponseParser service.
 * 
 * Tests JSON extraction from various AI response formats, schema validation,
 * repair logic for malformed JSON, and AnalysisResult DTO creation.
 * 
 * @see TASK-31-5
 * @see SPEC-031
 */
class AIAnalysisResponseParserTest extends TestCase
{
    private AIAnalysisResponseParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AIAnalysisResponseParser();
    }

    // =========================================================================
    // Service Instantiation Tests (SI)
    // =========================================================================

    /** @test SI-001: Service can be instantiated */
    public function service_can_be_instantiated(): void
    {
        $parser = app(AIAnalysisResponseParser::class);
        $this->assertInstanceOf(AIAnalysisResponseParser::class, $parser);
    }

    /** @test SI-002: Service has parse method */
    public function service_has_parse_method(): void
    {
        $this->assertTrue(method_exists($this->parser, 'parse'));
    }

    /** @test SI-003: Service has extractJson method */
    public function service_has_extract_json_method(): void
    {
        $this->assertTrue(method_exists($this->parser, 'extractJson'));
    }

    /** @test SI-004: Service has validateSchema method */
    public function service_has_validate_schema_method(): void
    {
        $this->assertTrue(method_exists($this->parser, 'validateSchema'));
    }

    /** @test SI-005: Service has repairPartialJson method */
    public function service_has_repair_partial_json_method(): void
    {
        $this->assertTrue(method_exists($this->parser, 'repairPartialJson'));
    }

    // =========================================================================
    // JSON Extraction - Markdown Code Block (JE-MD)
    // =========================================================================

    /** @test JE-MD-001: Extract JSON from markdown code block */
    public function extract_json_from_markdown_code_block(): void
    {
        $response = <<<'MD'
Here is the analysis:

```json
{
    "overall_score": 75,
    "overall_summary": "This is a sample summary that is at least 50 characters long.",
    "indicators": []
}
```

Let me know if you need more details.
MD;

        $json = $this->parser->extractJson($response);

        $this->assertIsArray($json);
        $this->assertEquals(75, $json['overall_score']);
        $this->assertArrayHasKey('overall_summary', $json);
    }

    /** @test JE-MD-002: Extract JSON from markdown with whitespace */
    public function extract_json_from_markdown_with_whitespace(): void
    {
        $response = <<<'MD'
```json
    {
        "overall_score": 85,
        "overall_summary": "Summary text that is sufficiently long for the requirements.",
        "indicators": []
    }
```
MD;

        $json = $this->parser->extractJson($response);

        $this->assertIsArray($json);
        $this->assertEquals(85, $json['overall_score']);
    }

    /** @test JE-MD-003: Extract JSON from markdown code block with nested braces */
    public function extract_json_from_markdown_with_nested_braces(): void
    {
        $response = <<<'MD'
```json
{
    "overall_score": 75,
    "indicators": [
        {
            "id": "title",
            "name": "Title Tag",
            "score": 2,
            "passed": true,
            "details": {"length": 55}
        }
    ]
}
```
MD;

        $json = $this->parser->extractJson($response);

        $this->assertIsArray($json);
        $this->assertEquals('title', $json['indicators'][0]['id']);
        $this->assertEquals(55, $json['indicators'][0]['details']['length']);
    }

    /** @test JE-MD-004: Extract JSON handles multiple code blocks - extracts first JSON block */
    public function extract_json_handles_multiple_code_blocks(): void
    {
        $response = <<<'MD'
```python
print("hello")
```

```json
{
    "overall_score": 80,
    "overall_summary": "Summary text for the analysis results.",
    "indicators": []
}
```
MD;

        $json = $this->parser->extractJson($response);

        $this->assertIsArray($json);
        $this->assertEquals(80, $json['overall_score']);
    }

    /** @test JE-MD-005: Extract JSON handles code block without language specifier */
    public function extract_json_handles_code_block_without_language(): void
    {
        $response = <<<'MD'
```
{
    "overall_score": 70,
    "overall_summary": "Summary without language specifier in code block.",
    "indicators": []
}
```
MD;

        // Should still try to extract JSON from code block
        $json = $this->parser->extractJson($response);

        $this->assertIsArray($json);
        $this->assertEquals(70, $json['overall_score']);
    }

    // =========================================================================
    // JSON Extraction - Raw JSON (JE-RJ)
    // =========================================================================

    /** @test JE-RJ-001: Extract raw JSON response */
    public function extract_raw_json_response(): void
    {
        $response = '{"overall_score": 85, "overall_summary": "Raw JSON summary text here.", "indicators": []}';

        $json = $this->parser->extractJson($response);

        $this->assertIsArray($json);
        $this->assertEquals(85, $json['overall_score']);
    }

    /** @test JE-RJ-002: Extract raw JSON with nested structure */
    public function extract_raw_json_with_nested_structure(): void
    {
        $response = json_encode([
            'overall_score' => 75,
            'overall_summary' => 'Nested JSON structure summary text here.',
            'category_scores' => [
                'content' => 80,
                'technical' => 70,
                'accessibility' => 75,
                'performance' => 72,
            ],
            'indicators' => [
                [
                    'id' => 'title',
                    'name' => 'Title Tag',
                    'score' => 2,
                    'passed' => true,
                ],
            ],
        ]);

        $json = $this->parser->extractJson($response);

        $this->assertIsArray($json);
        $this->assertEquals(80, $json['category_scores']['content']);
        $this->assertEquals('title', $json['indicators'][0]['id']);
    }

    /** @test JE-RJ-003: Extract raw JSON with whitespace */
    public function extract_raw_json_with_whitespace(): void
    {
        $response = '
        {
            "overall_score": 90,
            "overall_summary": "JSON with leading and trailing whitespace.",
            "indicators": []
        }
        ';

        $json = $this->parser->extractJson($response);

        $this->assertIsArray($json);
        $this->assertEquals(90, $json['overall_score']);
    }

    // =========================================================================
    // JSON Extraction - Mixed Content (JE-MX)
    // =========================================================================

    /** @test JE-MX-001: Extract JSON from text with surrounding content */
    public function extract_json_from_mixed_content(): void
    {
        $response = 'I analyzed the page and here are the results: {"overall_score": 90, "overall_summary": "Mixed content summary text.", "indicators": []} That covers it!';

        $json = $this->parser->extractJson($response);

        $this->assertIsArray($json);
        $this->assertEquals(90, $json['overall_score']);
    }

    /** @test JE-MX-002: Extract JSON from text with prefix */
    public function extract_json_from_text_with_prefix(): void
    {
        $response = 'Here is the analysis result: {"overall_score": 75, "overall_summary": "Analysis with prefix text.", "indicators": []}';

        $json = $this->parser->extractJson($response);

        $this->assertIsArray($json);
        $this->assertEquals(75, $json['overall_score']);
    }

    /** @test JE-MX-003: Extract JSON handles nested objects in mixed content */
    public function extract_json_handles_nested_objects_in_mixed_content(): void
    {
        $response = 'Analysis: {"overall_score": 80, "indicators": [{"id": "meta", "score": 2}], "overall_summary": "Nested in mixed."}';

        $json = $this->parser->extractJson($response);

        $this->assertIsArray($json);
        $this->assertEquals('meta', $json['indicators'][0]['id']);
    }

    /** @test JE-MX-004: Extract JSON prefers code block over inline JSON */
    public function extract_json_prefers_code_block_over_inline(): void
    {
        $response = <<<'MD'
Inline: {"overall_score": 50}

```json
{
    "overall_score": 80,
    "overall_summary": "Code block should take priority.",
    "indicators": []
}
```
MD;

        $json = $this->parser->extractJson($response);

        // Code block should be extracted
        $this->assertIsArray($json);
        $this->assertEquals(80, $json['overall_score']);
    }

    // =========================================================================
    // JSON Extraction - Invalid Input (JE-IV)
    // =========================================================================

    /** @test JE-IV-001: Extract JSON returns null for plain text */
    public function extract_json_returns_null_for_plain_text(): void
    {
        $response = 'This is not JSON at all, just plain text.';

        $json = $this->parser->extractJson($response);

        $this->assertNull($json);
    }

    /** @test JE-IV-002: Extract JSON returns null for broken JSON */
    public function extract_json_returns_null_for_broken_json(): void
    {
        $response = '{"overall_score": 75, "indicators": ["unclosed array"';

        $json = $this->parser->extractJson($response);

        $this->assertNull($json);
    }

    /** @test JE-IV-003: Extract JSON returns null for empty string */
    public function extract_json_returns_null_for_empty_string(): void
    {
        $response = '';

        $json = $this->parser->extractJson($response);

        $this->assertNull($json);
    }

    /** @test JE-IV-004: Extract JSON returns null for whitespace only */
    public function extract_json_returns_null_for_whitespace_only(): void
    {
        $response = '   ' . PHP_EOL . '   ' . PHP_EOL . '   ';

        $json = $this->parser->extractJson($response);

        $this->assertNull($json);
    }

    /** @test JE-IV-005: Extract JSON handles null literal */
    public function extract_json_handles_null_literal(): void
    {
        $response = 'null';

        $json = $this->parser->extractJson($response);

        // null is valid JSON but should return null for our purposes
        $this->assertNull($json);
    }

    /** @test JE-IV-006: Extract JSON handles JSON array instead of object */
    public function extract_json_handles_json_array(): void
    {
        $response = '[{"overall_score": 75}]';

        $json = $this->parser->extractJson($response);

        // Should return null since we expect an object, not array
        $this->assertNull($json);
    }

    // =========================================================================
    // Schema Validation - Required Fields (SV-RF)
    // =========================================================================

    /** @test SV-RF-001: Validate schema accepts valid response */
    public function validate_schema_accepts_valid_response(): void
    {
        $validData = $this->getValidAnalysisData();

        $result = $this->parser->validateSchema($validData);

        $this->assertTrue($result);
    }

    /** @test SV-RF-002: Validate schema rejects missing overall_score */
    public function validate_schema_rejects_missing_overall_score(): void
    {
        $data = [
            'overall_summary' => str_repeat('a', 60),
            'indicators' => [],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-RF-003: Validate schema rejects missing overall_summary */
    public function validate_schema_rejects_missing_overall_summary(): void
    {
        $data = [
            'overall_score' => 75,
            'indicators' => [],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-RF-004: Validate schema rejects missing indicators */
    public function validate_schema_rejects_missing_indicators(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-RF-005: Validate schema accepts without optional fields */
    public function validate_schema_accepts_without_optional_fields(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertTrue($result);
    }

    // =========================================================================
    // Schema Validation - Type Checking (SV-TC)
    // =========================================================================

    /** @test SV-TC-001: Validate schema rejects non-integer overall_score */
    public function validate_schema_rejects_non_integer_overall_score(): void
    {
        $data = [
            'overall_score' => '75',
            'overall_summary' => str_repeat('a', 60),
            'indicators' => [],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-TC-002: Validate schema rejects non-string overall_summary */
    public function validate_schema_rejects_non_string_overall_summary(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => 12345,
            'indicators' => [],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-TC-003: Validate schema rejects non-array indicators */
    public function validate_schema_rejects_non_array_indicators(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => 'not an array',
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-TC-004: Validate schema rejects non-array top_priorities */
    public function validate_schema_rejects_non_array_top_priorities(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
            'top_priorities' => 'not an array',
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-TC-005: Validate schema accepts numeric overall_score */
    public function validate_schema_accepts_numeric_overall_score(): void
    {
        $data = [
            'overall_score' => 75.0, // Float that equals an integer
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
        ];

        $result = $this->parser->validateSchema($data);

        // Should accept numeric values that are whole numbers
        $this->assertTrue($result);
    }

    // =========================================================================
    // Schema Validation - Range Checking (SV-RC)
    // =========================================================================

    /** @test SV-RC-001: Validate schema accepts score at minimum boundary */
    public function validate_schema_accepts_score_at_minimum(): void
    {
        $data = [
            'overall_score' => 0,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertTrue($result);
    }

    /** @test SV-RC-002: Validate schema accepts score at maximum boundary */
    public function validate_schema_accepts_score_at_maximum(): void
    {
        $data = [
            'overall_score' => 100,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertTrue($result);
    }

    /** @test SV-RC-003: Validate schema rejects negative overall_score */
    public function validate_schema_rejects_negative_overall_score(): void
    {
        $data = [
            'overall_score' => -10,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => [],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-RC-004: Validate schema rejects overall_score over 100 */
    public function validate_schema_rejects_overall_score_over_100(): void
    {
        $data = [
            'overall_score' => 150,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => [],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-RC-005: Validate schema rejects summary too short */
    public function validate_schema_rejects_summary_too_short(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 49), // Under 50 chars
            'indicators' => [],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-RC-006: Validate schema rejects summary too long */
    public function validate_schema_rejects_summary_too_long(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 501), // Over 500 chars
            'indicators' => [],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-RC-007: Validate schema accepts summary at min boundary */
    public function validate_schema_accepts_summary_at_min_boundary(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 50), // Exactly 50 chars
            'indicators' => $this->getValidIndicators(),
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertTrue($result);
    }

    /** @test SV-RC-008: Validate schema accepts summary at max boundary */
    public function validate_schema_accepts_summary_at_max_boundary(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 500), // Exactly 500 chars
            'indicators' => $this->getValidIndicators(),
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertTrue($result);
    }

    // =========================================================================
    // Schema Validation - Indicators (SV-IN)
    // =========================================================================

    /** @test SV-IN-001: Validate schema rejects less than 17 indicators */
    public function validate_schema_rejects_less_than_17_indicators(): void
    {
        $indicators = [];
        for ($i = 0; $i < 16; $i++) {
            $indicators[] = [
                'id' => "indicator_$i",
                'name' => "Indicator $i",
                'score' => 2,
                'passed' => true,
            ];
        }

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-IN-002: Validate schema rejects more than 17 indicators */
    public function validate_schema_rejects_more_than_17_indicators(): void
    {
        $indicators = [];
        for ($i = 0; $i < 18; $i++) {
            $indicators[] = [
                'id' => "indicator_$i",
                'name' => "Indicator $i",
                'score' => 2,
                'passed' => true,
            ];
        }

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-IN-003: Validate schema accepts exactly 17 indicators */
    public function validate_schema_accepts_exactly_17_indicators(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertTrue($result);
    }

    /** @test SV-IN-004: Validate indicator requires id field */
    public function validate_indicator_requires_id_field(): void
    {
        $indicators = $this->getValidIndicators();
        unset($indicators[0]['id']);

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-IN-005: Validate indicator requires name field */
    public function validate_indicator_requires_name_field(): void
    {
        $indicators = $this->getValidIndicators();
        unset($indicators[0]['name']);

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-IN-006: Validate indicator requires score field */
    public function validate_indicator_requires_score_field(): void
    {
        $indicators = $this->getValidIndicators();
        unset($indicators[0]['score']);

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-IN-007: Validate indicator requires passed field */
    public function validate_indicator_requires_passed_field(): void
    {
        $indicators = $this->getValidIndicators();
        unset($indicators[0]['passed']);

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-IN-008: Validate indicator score is between 0 and 3 */
    public function validate_indicator_score_is_between_0_and_3(): void
    {
        $indicators = $this->getValidIndicators();
        $indicators[0]['score'] = 5; // Invalid, max is 3

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-IN-009: Validate indicator accepts score at boundaries */
    public function validate_indicator_accepts_score_at_boundaries(): void
    {
        foreach ([0, 1, 2, 3] as $score) {
            $indicators = $this->getValidIndicators();
            $indicators[0]['score'] = $score;

            $data = [
                'overall_score' => 75,
                'overall_summary' => str_repeat('a', 60),
                'indicators' => $indicators,
            ];

            $result = $this->parser->validateSchema($data);
            $this->assertTrue($result, "Score $score should be valid");
        }
    }

    /** @test SV-IN-010: Validate indicator rejects negative score */
    public function validate_indicator_rejects_negative_score(): void
    {
        $indicators = $this->getValidIndicators();
        $indicators[0]['score'] = -1;

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-IN-011: Validate indicator passed must be boolean */
    public function validate_indicator_passed_must_be_boolean(): void
    {
        $indicators = $this->getValidIndicators();
        $indicators[0]['passed'] = 'true'; // String, not boolean

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Schema Validation - Category Scores (SV-CS)
    // =========================================================================

    /** @test SV-CS-001: Validate category_scores accepts valid structure */
    public function validate_category_scores_accepts_valid_structure(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
            'category_scores' => [
                'content' => 80,
                'technical' => 70,
                'accessibility' => 75,
                'performance' => 72,
            ],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertTrue($result);
    }

    /** @test SV-CS-002: Validate category_scores rejects score over 100 */
    public function validate_category_scores_rejects_score_over_100(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
            'category_scores' => [
                'content' => 150, // Invalid
            ],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-CS-003: Validate category_scores rejects negative score */
    public function validate_category_scores_rejects_negative_score(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
            'category_scores' => [
                'technical' => -10, // Invalid
            ],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Schema Validation - Top Priorities (SV-TP)
    // =========================================================================

    /** @test SV-TP-001: Validate top_priorities accepts valid array */
    public function validate_top_priorities_accepts_valid_array(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
            'top_priorities' => ['title', 'meta_description', 'headings'],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertTrue($result);
    }

    /** @test SV-TP-002: Validate top_priorities rejects more than 5 items */
    public function validate_top_priorities_rejects_more_than_5_items(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
            'top_priorities' => ['a', 'b', 'c', 'd', 'e', 'f'], // 6 items
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertFalse($result);
    }

    /** @test SV-TP-003: Validate top_priorities accepts exactly 5 items */
    public function validate_top_priorities_accepts_exactly_5_items(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
            'top_priorities' => ['a', 'b', 'c', 'd', 'e'],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertTrue($result);
    }

    /** @test SV-TP-004: Validate top_priorities accepts empty array */
    public function validate_top_priorities_accepts_empty_array(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
            'top_priorities' => [],
        ];

        $result = $this->parser->validateSchema($data);

        $this->assertTrue($result);
    }

    // =========================================================================
    // Repair Logic (RP)
    // =========================================================================

    /** @test RP-001: Repair clamps overall_score over 100 */
    public function repair_clamps_overall_score_over_100(): void
    {
        $data = [
            'overall_score' => 150,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
        ];

        $result = $this->parser->parse(json_encode($data));

        $this->assertEquals(100, $result->overallScore);
    }

    /** @test RP-002: Repair clamps negative overall_score */
    public function repair_clamps_negative_overall_score(): void
    {
        $data = [
            'overall_score' => -10,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
        ];

        $result = $this->parser->parse(json_encode($data));

        $this->assertEquals(0, $result->overallScore);
    }

    /** @test RP-003: Repair clamps indicator score over 3 */
    public function repair_clamps_indicator_score_over_3(): void
    {
        $indicators = $this->getValidIndicators();
        $indicators[0]['score'] = 10;

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $result = $this->parser->parse(json_encode($data));

        $this->assertEquals(3, $result->indicators[0]['score']);
    }

    /** @test RP-004: Repair handles truncated summary */
    public function repair_handles_truncated_summary(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => 'Too short', // Under 50 chars
            'indicators' => $this->getValidIndicators(),
        ];

        $result = $this->parser->parse(json_encode($data));

        // Should pad or accept as-is with warning logged
        $this->assertNotEmpty($result->overallSummary);
    }

    /** @test RP-005: Repair handles missing optional fields with defaults */
    public function repair_handles_missing_optional_fields(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
            // Missing: category_scores, top_priorities
        ];

        $result = $this->parser->parse(json_encode($data));

        $this->assertEquals([], $result->categoryScores);
        $this->assertEquals([], $result->topPriorities);
    }

    /** @test RP-006: repairPartialJson attempts to close unclosed structures */
    public function repair_partial_json_attempts_close_structures(): void
    {
        $brokenJson = '{"overall_score": 75, "indicators": [{"id": "title"';

        $repaired = $this->parser->repairPartialJson($brokenJson);

        $this->assertNotNull($repaired);
        $this->assertArrayHasKey('overall_score', $repaired);
    }

    /** @test RP-007: repairPartialJson handles missing closing brackets */
    public function repair_partial_json_handles_missing_closing_brackets(): void
    {
        $brokenJson = '{"overall_score": 75, "indicators": []';

        $repaired = $this->parser->repairPartialJson($brokenJson);

        $this->assertNotNull($repaired);
        $this->assertEquals(75, $repaired['overall_score']);
    }

    /** @test RP-008: repairPartialJson returns null for completely broken JSON */
    public function repair_partial_json_returns_null_for_completely_broken(): void
    {
        $brokenJson = 'this is not json at all {{{}}}';

        $repaired = $this->parser->repairPartialJson($brokenJson);

        $this->assertNull($repaired);
    }

    // =========================================================================
    // Parse Method - Success Cases (PS)
    // =========================================================================

    /** @test PS-001: Parse returns AnalysisResult DTO */
    public function parse_returns_analysis_result_dto(): void
    {
        $response = json_encode($this->getValidAnalysisData());

        $result = $this->parser->parse($response);

        $this->assertInstanceOf(AnalysisResult::class, $result);
    }

    /** @test PS-002: Parse populates all required fields */
    public function parse_populates_all_required_fields(): void
    {
        $data = $this->getValidAnalysisData();
        $response = json_encode($data);

        $result = $this->parser->parse($response);

        $this->assertEquals($data['overall_score'], $result->overallScore);
        $this->assertEquals($data['overall_summary'], $result->overallSummary);
        $this->assertCount(17, $result->indicators);
    }

    /** @test PS-003: Parse populates optional fields when present */
    public function parse_populates_optional_fields_when_present(): void
    {
        $data = $this->getValidAnalysisData();
        $data['category_scores'] = ['content' => 80, 'technical' => 70];
        $data['top_priorities'] = ['title', 'meta_description'];
        $data['model'] = 'llama-3.1-8b-instruct';
        $data['prompt_tokens'] = 500;
        $data['response_tokens'] = 1000;

        $response = json_encode($data);
        $result = $this->parser->parse($response);

        $this->assertEquals(['content' => 80, 'technical' => 70], $result->categoryScores);
        $this->assertEquals(['title', 'meta_description'], $result->topPriorities);
        $this->assertEquals('llama-3.1-8b-instruct', $result->model);
        $this->assertEquals(500, $result->promptTokens);
        $this->assertEquals(1000, $result->responseTokens);
    }

    /** @test PS-004: Parse handles markdown code block response */
    public function parse_handles_markdown_code_block_response(): void
    {
        $response = <<<'MD'
```json
{
    "overall_score": 85,
    "overall_summary": "This is a valid summary text for the analysis output.",
    "indicators": []
}
```
MD;

        // Note: This would fail validation because indicators is empty
        // But it tests the extraction logic
        $json = $this->parser->extractJson($response);
        $this->assertEquals(85, $json['overall_score']);
    }

    /** @test PS-005: Parse handles extra fields gracefully */
    public function parse_handles_extra_fields_gracefully(): void
    {
        $data = $this->getValidAnalysisData();
        $data['extra_field'] = 'should be ignored';
        $data['another_extra'] = ['nested' => 'data'];

        $response = json_encode($data);
        $result = $this->parser->parse($response);

        $this->assertInstanceOf(AnalysisResult::class, $result);
        // Extra fields should be ignored
    }

    // =========================================================================
    // Parse Method - Error Cases (PE)
    // =========================================================================

    /** @test PE-001: Parse throws InvalidAIResponseException for no JSON */
    public function parse_throws_exception_for_no_json(): void
    {
        $this->expectException(InvalidAIResponseException::class);

        $this->parser->parse('This is not JSON at all.');
    }

    /** @test PE-002: Parse throws InvalidAIResponseException for empty string */
    public function parse_throws_exception_for_empty_string(): void
    {
        $this->expectException(InvalidAIResponseException::class);

        $this->parser->parse('');
    }

    /** @test PE-003: Parse throws InvalidAIResponseException for null JSON */
    public function parse_throws_exception_for_null_json(): void
    {
        $this->expectException(InvalidAIResponseException::class);

        $this->parser->parse('null');
    }

    /** @test PE-004: Parse throws InvalidAIResponseException for invalid schema */
    public function parse_throws_exception_for_invalid_schema(): void
    {
        $this->expectException(InvalidAIResponseException::class);

        $this->parser->parse('{"invalid": "structure"}');
    }

    /** @test PE-005: Parse exception message contains context */
    public function parse_exception_message_contains_context(): void
    {
        $invalidResponse = 'plain text response';

        try {
            $this->parser->parse($invalidResponse);
            $this->fail('Expected InvalidAIResponseException');
        } catch (InvalidAIResponseException $e) {
            $this->assertStringContainsString('JSON', $e->getMessage());
        }
    }

    // =========================================================================
    // AnalysisResult DTO Tests (DTO)
    // =========================================================================

    /** @test DTO-001: AnalysisResult has all expected properties */
    public function analysis_result_has_all_expected_properties(): void
    {
        $data = $this->getValidAnalysisData();
        $result = AnalysisResult::fromArray($data);

        $this->assertEquals($data['overall_score'], $result->overallScore);
        $this->assertEquals($data['overall_summary'], $result->overallSummary);
        $this->assertEquals($data['category_scores'] ?? [], $result->categoryScores);
        $this->assertEquals($data['indicators'], $result->indicators);
        $this->assertEquals($data['top_priorities'] ?? [], $result->topPriorities);
        $this->assertEquals($data['model'] ?? 'unknown', $result->model);
        $this->assertEquals($data['prompt_tokens'] ?? 0, $result->promptTokens);
        $this->assertEquals($data['response_tokens'] ?? 0, $result->responseTokens);
    }

    /** @test DTO-002: AnalysisResult fromArray creates valid instance */
    public function analysis_result_from_array_creates_valid_instance(): void
    {
        $data = $this->getValidAnalysisData();
        $result = AnalysisResult::fromArray($data);

        $this->assertInstanceOf(AnalysisResult::class, $result);
    }

    /** @test DTO-003: AnalysisResult uses defaults for missing optional fields */
    public function analysis_result_uses_defaults_for_missing_fields(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
        ];

        $result = AnalysisResult::fromArray($data);

        $this->assertEquals([], $result->categoryScores);
        $this->assertEquals([], $result->topPriorities);
        $this->assertEquals('unknown', $result->model);
        $this->assertEquals(0, $result->promptTokens);
        $this->assertEquals(0, $result->responseTokens);
    }

    /** @test DTO-004: AnalysisResult properties are readonly */
    public function analysis_result_properties_are_readonly(): void
    {
        $data = $this->getValidAnalysisData();
        $result = AnalysisResult::fromArray($data);

        // Try to modify - should fail if readonly
        $this->expectException(\Error::class);
        $result->overallScore = 999;
    }

    /** @test DTO-005: AnalysisResult can be serialized to array */
    public function analysis_result_can_be_serialized_to_array(): void
    {
        $data = $this->getValidAnalysisData();
        $result = AnalysisResult::fromArray($data);

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('overall_score', $array);
        $this->assertArrayHasKey('overall_summary', $array);
        $this->assertArrayHasKey('indicators', $array);
    }

    // =========================================================================
    // Edge Cases (EC)
    // =========================================================================

    /** @test EC-001: Handle unicode in JSON response */
    public function handle_unicode_in_json_response(): void
    {
        $data = $this->getValidAnalysisData();
        $data['overall_summary'] = '日本語テスト 中文 العربية 🎉 Emoji test';

        $response = json_encode($data);
        $result = $this->parser->parse($response);

        $this->assertStringContainsString('日本語', $result->overallSummary);
    }

    /** @test EC-002: Handle very long summary */
    public function handle_very_long_summary(): void
    {
        $data = $this->getValidAnalysisData();
        $data['overall_summary'] = str_repeat('a', 500);

        $response = json_encode($data);
        $result = $this->parser->parse($response);

        $this->assertEquals(500, strlen($result->overallSummary));
    }

    /** @test EC-003: Handle escaped quotes in JSON */
    public function handle_escaped_quotes_in_json(): void
    {
        $data = $this->getValidAnalysisData();
        $data['overall_summary'] = 'He said "hello" and she replied \'hi\'';

        $response = json_encode($data);
        $result = $this->parser->parse($response);

        $this->assertStringContainsString('"hello"', $result->overallSummary);
    }

    /** @test EC-004: Handle newlines in JSON values */
    public function handle_newlines_in_json_values(): void
    {
        $data = $this->getValidAnalysisData();
        $data['overall_summary'] = "Line 1\nLine 2\nLine 3";

        $response = json_encode($data);
        $result = $this->parser->parse($response);

        $this->assertStringContainsString("\n", $result->overallSummary);
    }

    /** @test EC-005: Handle very large response (1000+ indicators in array - should fail) */
    public function handle_very_large_response(): void
    {
        $indicators = [];
        for ($i = 0; $i < 1000; $i++) {
            $indicators[] = [
                'id' => "indicator_$i",
                'name' => "Indicator $i",
                'score' => rand(0, 3),
                'passed' => (bool)rand(0, 1),
            ];
        }

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $response = json_encode($data);

        // Should fail validation because there are too many indicators
        $this->expectException(InvalidAIResponseException::class);
        $this->parser->parse($response);
    }

    /** @test EC-006: Handle deeply nested JSON */
    public function handle_deeply_nested_json(): void
    {
        $data = $this->getValidAnalysisData();
        $data['indicators'][0]['details'] = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => 'deep value',
                    ],
                ],
            ],
        ];

        $response = json_encode($data);
        $result = $this->parser->parse($response);

        $this->assertEquals('deep value', $result->indicators[0]['details']['level1']['level2']['level3']['level4']);
    }

    /** @test EC-007: Handle boolean vs integer passed field variations */
    public function handle_boolean_vs_integer_passed_field(): void
    {
        // Some AI models might return 1/0 instead of true/false
        $indicators = $this->getValidIndicators();
        $indicators[0]['passed'] = 1; // Integer instead of boolean

        $data = [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $indicators,
        ];

        $response = json_encode($data);

        // Should either accept with conversion or reject
        $json = $this->parser->extractJson($response);
        $this->assertNotNull($json);
    }

    // =========================================================================
    // Performance Tests (PF)
    // =========================================================================

    /** @test PF-001: Parse is performant for typical response */
    public function parse_is_performant(): void
    {
        $data = $this->getValidAnalysisData();
        $response = json_encode($data);

        $start = microtime(true);
        $this->parser->parse($response);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(50, $elapsed, 'Parse should complete in < 50ms');
    }

    /** @test PF-002: JSON extraction is performant */
    public function json_extraction_is_performant(): void
    {
        $response = '```json' . PHP_EOL . json_encode($this->getValidAnalysisData()) . PHP_EOL . '```';

        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->parser->extractJson($response);
        }
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $elapsed, '100 extractions should complete in < 100ms');
    }

    /** @test PF-003: Schema validation is performant */
    public function schema_validation_is_performant(): void
    {
        $data = $this->getValidAnalysisData();

        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->parser->validateSchema($data);
        }
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $elapsed, '100 validations should complete in < 100ms');
    }

    // =========================================================================
    // Security Tests (SC)
    // =========================================================================

    /** @test SC-001: XSS payload in JSON is preserved as data */
    public function xss_payload_in_json_is_preserved_as_data(): void
    {
        $data = $this->getValidAnalysisData();
        $data['overall_summary'] = '<script>alert("xss")</script>';

        $response = json_encode($data);
        $result = $this->parser->parse($response);

        // XSS should be in the data, not executed
        $this->assertStringContainsString('<script>', $result->overallSummary);
    }

    /** @test SC-002: SQL injection payload in JSON is preserved as data */
    public function sql_injection_payload_in_json_is_preserved(): void
    {
        $data = $this->getValidAnalysisData();
        $data['indicators'][0]['id'] = "'; DROP TABLE users; --";

        $response = json_encode($data);
        $result = $this->parser->parse($response);

        // Injection should be in the data, not executed
        $this->assertStringContainsString('DROP TABLE', $result->indicators[0]['id']);
    }

    /** @test SC-003: Parser does not execute code in JSON */
    public function parser_does_not_execute_code_in_json(): void
    {
        $data = $this->getValidAnalysisData();
        $data['overall_summary'] = '${system("rm -rf /")}';

        $response = json_encode($data);
        $result = $this->parser->parse($response);

        // Should be preserved as string, not executed
        $this->assertStringContainsString('system', $result->overallSummary);
    }

    /** @test SC-004: Parser handles null bytes safely */
    public function parser_handles_null_bytes_safely(): void
    {
        $data = $this->getValidAnalysisData();
        $data['overall_summary'] = "Summary with \x00 null byte";

        $response = json_encode($data);
        $result = $this->parser->parse($response);

        // Should handle null bytes safely
        $this->assertInstanceOf(AnalysisResult::class, $result);
    }

    // =========================================================================
    // Logging Tests (LG)
    // =========================================================================

    /** @test LG-001: Repair attempts are logged */
    public function repair_attempts_are_logged(): void
    {
        Log::shouldReceive('warning')
            ->atLeast()->once();

        $data = [
            'overall_score' => 150, // Will trigger clamp/repair
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
        ];

        $this->parser->parse(json_encode($data));
    }

    /** @test LG-002: Validation failures are logged */
    public function validation_failures_are_logged(): void
    {
        Log::shouldReceive('warning')
            ->atLeast()->once();

        try {
            $this->parser->parse('{"invalid": "structure"}');
        } catch (InvalidAIResponseException $e) {
            // Expected
        }
    }

    /** @test LG-003: Extraction failures are logged */
    public function extraction_failures_are_logged(): void
    {
        Log::shouldReceive('warning')
            ->atLeast()->once();

        try {
            $this->parser->parse('not json at all');
        } catch (InvalidAIResponseException $e) {
            // Expected
        }
    }

    // =========================================================================
    // Acceptance Criteria Tests (AC)
    // =========================================================================

    /** @test AC-001: Extracts JSON from markdown code blocks */
    public function acceptance_extracts_json_from_markdown_code_blocks(): void
    {
        $response = <<<'MD'
```json
{"overall_score": 85, "overall_summary": "Test summary text for validation purposes.", "indicators": []}
```
MD;

        $json = $this->parser->extractJson($response);

        $this->assertNotNull($json);
        $this->assertEquals(85, $json['overall_score']);
    }

    /** @test AC-002: Extracts JSON from raw response */
    public function acceptance_extracts_json_from_raw_response(): void
    {
        $response = '{"overall_score": 90, "overall_summary": "Raw JSON response for testing.", "indicators": []}';

        $json = $this->parser->extractJson($response);

        $this->assertNotNull($json);
        $this->assertEquals(90, $json['overall_score']);
    }

    /** @test AC-003: Validates against JSON schema */
    public function acceptance_validates_against_json_schema(): void
    {
        $validData = $this->getValidAnalysisData();

        $result = $this->parser->validateSchema($validData);

        $this->assertTrue($result);
    }

    /** @test AC-004: Attempts repair for common issues */
    public function acceptance_attempts_repair_for_common_issues(): void
    {
        $data = [
            'overall_score' => 150, // Out of range
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
        ];

        $result = $this->parser->parse(json_encode($data));

        // Should clamp to 100
        $this->assertEquals(100, $result->overallScore);
    }

    /** @test AC-005: Returns structured AnalysisResult DTO */
    public function acceptance_returns_structured_analysis_result_dto(): void
    {
        $data = $this->getValidAnalysisData();
        $result = $this->parser->parse(json_encode($data));

        $this->assertInstanceOf(AnalysisResult::class, $result);
        $this->assertIsInt($result->overallScore);
        $this->assertIsString($result->overallSummary);
        $this->assertIsArray($result->indicators);
    }

    /** @test AC-006: Logs warnings for repair attempts */
    public function acceptance_logs_warnings_for_repair_attempts(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $data = [
            'overall_score' => -10, // Invalid, will be clamped
            'overall_summary' => str_repeat('a', 60),
            'indicators' => $this->getValidIndicators(),
        ];

        $this->parser->parse(json_encode($data));
    }

    /** @test AC-007: Throws InvalidAIResponseException for unparseable responses */
    public function acceptance_throws_exception_for_unparseable_responses(): void
    {
        $this->expectException(InvalidAIResponseException::class);

        $this->parser->parse('completely invalid response with no json');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Get valid analysis data for testing.
     */
    private function getValidAnalysisData(): array
    {
        return [
            'overall_score' => 75,
            'overall_summary' => str_repeat('a', 60),
            'category_scores' => [
                'content' => 80,
                'technical' => 70,
                'accessibility' => 75,
                'performance' => 72,
            ],
            'indicators' => $this->getValidIndicators(),
            'top_priorities' => ['title', 'meta_description', 'headings'],
            'model' => 'llama-3.1-8b-instruct',
            'prompt_tokens' => 500,
            'response_tokens' => 1000,
        ];
    }

    /**
     * Get valid indicators array (17 items).
     */
    private function getValidIndicators(): array
    {
        $indicatorIds = [
            'title', 'meta_description', 'meta_keywords', 'robots_txt', 'sitemap',
            'headings', 'image_alt', 'internal_links', 'external_links', 'url_structure',
            'canonical_url', 'https', 'mobile_friendly', 'page_speed', 'structured_data',
            'social_tags', 'content_quality',
        ];

        $indicators = [];
        foreach ($indicatorIds as $id) {
            $indicators[] = [
                'id' => $id,
                'name' => ucfirst(str_replace('_', ' ', $id)),
                'score' => rand(0, 3),
                'passed' => (bool)rand(0, 1),
            ];
        }

        return $indicators;
    }
}
