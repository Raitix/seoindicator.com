# TEST-31-5: AIAnalysisResponseParser

## Task Reference
- **Task:** TASK-31-5
- **Specification:** SPEC-31: Unified AI Prompt + Context Management
- **Feature:** FEAT-031

## Overview
Comprehensive unit test suite for the AIAnalysisResponseParser service that parses AI responses, extracts JSON from various formats, validates against schema, and returns structured AnalysisResult DTOs.

## Test Files

| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `backend/tests/Unit/Services/AI/AIAnalysisResponseParserTest.php` | 140+ | 200+ | TDD Ready |
| `backend/tests/Unit/Services/AI/DTOs/AnalysisResultTest.php` | 30+ | 50+ | TDD Ready |

## Test Scenarios

### Service Instantiation (SI)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SI-001 | Service can be instantiated | Container resolve | AIAnalysisResponseParser instance |
| SI-002 | Service has parse method | method_exists | true |
| SI-003 | Service has extractJson method | method_exists | true |
| SI-004 | Service has validateSchema method | method_exists | true |
| SI-005 | Service has repairPartialJson method | method_exists | true |

### JSON Extraction - Markdown Code Block (JE-MD)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JE-MD-001 | Extract from markdown code block | Response with ```json block | Parsed array |
| JE-MD-002 | Extract with whitespace | Code block with leading/trailing whitespace | Parsed array |
| JE-MD-003 | Extract with nested braces | Nested JSON in code block | Parsed nested structure |
| JE-MD-004 | Multiple code blocks | Python + JSON blocks | Extract JSON block (first valid) |
| JE-MD-005 | Code block without language | ``` without json specifier | Attempt extraction |

### JSON Extraction - Raw JSON (JE-RJ)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JE-RJ-001 | Raw JSON response | `{"key": "value"}` | Parsed array |
| JE-RJ-002 | Nested JSON structure | Nested objects/arrays | Parsed nested structure |
| JE-RJ-003 | JSON with whitespace | Leading/trailing whitespace | Parsed array |

### JSON Extraction - Mixed Content (JE-MX)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JE-MX-001 | JSON with surrounding text | `prefix {"key": 1} suffix` | Parsed array |
| JE-MX-002 | JSON with text prefix | `Result: {"score": 75}` | Parsed array |
| JE-MX-003 | Nested JSON in mixed content | Complex nested structure | Parsed nested structure |
| JE-MX-004 | Prefer code block over inline | Inline + code block | Code block extracted |

### JSON Extraction - Invalid Input (JE-IV)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| JE-IV-001 | Plain text | No JSON present | null |
| JE-IV-002 | Broken JSON | Unclosed brackets | null |
| JE-IV-003 | Empty string | `""` | null |
| JE-IV-004 | Whitespace only | `"   "` | null |
| JE-IV-005 | null literal | `"null"` | null |
| JE-IV-006 | JSON array | `"[...]"` | null (expect object) |

### Schema Validation - Required Fields (SV-RF)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SV-RF-001 | Valid response | Complete valid data | true |
| SV-RF-002 | Missing overall_score | No score field | false |
| SV-RF-003 | Missing overall_summary | No summary field | false |
| SV-RF-004 | Missing indicators | No indicators field | false |
| SV-RF-005 | Without optional fields | Only required fields | true |

### Schema Validation - Type Checking (SV-TC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SV-TC-001 | Non-integer overall_score | `"75"` (string) | false |
| SV-TC-002 | Non-string overall_summary | `12345` (number) | false |
| SV-TC-003 | Non-array indicators | `"not array"` | false |
| SV-TC-004 | Non-array top_priorities | `"not array"` | false |
| SV-TC-005 | Numeric overall_score | `75.0` (float) | true |

### Schema Validation - Range Checking (SV-RC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SV-RC-001 | Score at minimum | `0` | true |
| SV-RC-002 | Score at maximum | `100` | true |
| SV-RC-003 | Negative score | `-10` | false |
| SV-RC-004 | Score over 100 | `150` | false |
| SV-RC-005 | Summary too short | `<50 chars` | false |
| SV-RC-006 | Summary too long | `>500 chars` | false |
| SV-RC-007 | Summary at min boundary | `50 chars` | true |
| SV-RC-008 | Summary at max boundary | `500 chars` | true |

### Schema Validation - Indicators (SV-IN)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SV-IN-001 | Less than 17 indicators | 16 items | false |
| SV-IN-002 | More than 17 indicators | 18 items | false |
| SV-IN-003 | Exactly 17 indicators | 17 items | true |
| SV-IN-004 | Missing id field | Indicator without id | false |
| SV-IN-005 | Missing name field | Indicator without name | false |
| SV-IN-006 | Missing score field | Indicator without score | false |
| SV-IN-007 | Missing passed field | Indicator without passed | false |
| SV-IN-008 | Score over 3 | `score: 5` | false |
| SV-IN-009 | Score at boundaries | `0, 1, 2, 3` | true |
| SV-IN-010 | Negative indicator score | `score: -1` | false |
| SV-IN-011 | Passed not boolean | `passed: "true"` | false |

### Schema Validation - Category Scores (SV-CS)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SV-CS-001 | Valid category_scores | Proper structure | true |
| SV-CS-002 | Score over 100 | `content: 150` | false |
| SV-CS-003 | Negative score | `technical: -10` | false |

### Schema Validation - Top Priorities (SV-TP)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SV-TP-001 | Valid top_priorities | Array of strings | true |
| SV-TP-002 | More than 5 items | 6 items | false |
| SV-TP-003 | Exactly 5 items | 5 items | true |
| SV-TP-004 | Empty array | `[]` | true |

### Repair Logic (RP)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RP-001 | Clamp score over 100 | `150` | `100` |
| RP-002 | Clamp negative score | `-10` | `0` |
| RP-003 | Clamp indicator score over 3 | `10` | `3` |
| RP-004 | Truncated summary | `<50 chars` | Padded or accepted |
| RP-005 | Missing optional fields | No category_scores | Default `[]` |
| RP-006 | Repair unclosed structures | `{"key": "val"` | Repaired object |
| RP-007 | Repair missing brackets | Unclosed JSON | Repaired object |
| RP-008 | Completely broken JSON | `{{{` | null |

### Parse Method - Success (PS)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PS-001 | Returns AnalysisResult | Valid JSON | AnalysisResult instance |
| PS-002 | Populates required fields | Valid JSON | All fields populated |
| PS-003 | Populates optional fields | JSON with optionals | Optional fields set |
| PS-004 | Markdown code block | Response in ```json | Parsed result |
| PS-005 | Extra fields ignored | JSON with extras | Clean result |

### Parse Method - Errors (PE)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PE-001 | No JSON | Plain text | InvalidAIResponseException |
| PE-002 | Empty string | `""` | InvalidAIResponseException |
| PE-003 | null JSON | `"null"` | InvalidAIResponseException |
| PE-004 | Invalid schema | Missing required | InvalidAIResponseException |
| PE-005 | Exception message | Invalid input | Context in message |

### AnalysisResult DTO (DTO)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DTO-001 | Has all properties | Valid data | All properties accessible |
| DTO-002 | fromArray creates instance | Valid array | AnalysisResult instance |
| DTO-003 | Defaults for missing fields | Minimal data | Defaults applied |
| DTO-004 | Properties are readonly | Attempt modification | Error thrown |
| DTO-005 | Serialize to array | Call toArray() | Array representation |

### Edge Cases (EC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Unicode in JSON | Japanese/Chinese/Emoji | Preserved correctly |
| EC-002 | Very long summary | 500 chars | Accepted |
| EC-003 | Escaped quotes | `"he said \"hi\""` | Unescaped correctly |
| EC-004 | Newlines in values | `line1\nline2` | Preserved |
| EC-005 | Large response (1000+ indicators) | Oversized array | Validation fails |
| EC-006 | Deeply nested JSON | 4+ levels deep | Parsed correctly |
| EC-007 | Boolean vs integer passed | `1` vs `true` | Handle gracefully |

### Performance (PF)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PF-001 | Parse typical response | Standard JSON | < 50ms |
| PF-002 | Extract JSON 100x | Markdown blocks | < 100ms total |
| PF-003 | Validate schema 100x | Valid data | < 100ms total |

### Security (SC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | XSS in JSON | `<script>alert(1)</script>` | Preserved as string |
| SC-002 | SQL injection | `'; DROP TABLE --` | Preserved as string |
| SC-003 | Code execution attempt | `${system(...)}` | Preserved as string |
| SC-004 | Null bytes | `\x00` in string | Handled safely |

### Logging (LG)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LG-001 | Repair logged | Out-of-range score | Warning logged |
| LG-002 | Validation failure logged | Invalid schema | Warning logged |
| LG-003 | Extraction failure logged | No JSON found | Warning logged |

### Acceptance Criteria (AC)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC-001 | Extracts JSON from markdown | ```json block | Parsed array |
| AC-002 | Extracts JSON from raw | Raw JSON string | Parsed array |
| AC-003 | Validates against schema | Valid data | true |
| AC-004 | Repairs common issues | Out-of-range values | Clamped values |
| AC-005 | Returns AnalysisResult DTO | Valid JSON | AnalysisResult |
| AC-006 | Logs repair warnings | Repair triggered | Warning logged |
| AC-007 | Throws for unparseable | Invalid input | InvalidAIResponseException |

## Mock Data

### Valid Analysis Response
```json
{
    "overall_score": 75,
    "overall_summary": "This is a comprehensive summary of the SEO analysis with sufficient length.",
    "category_scores": {
        "content": 80,
        "technical": 70,
        "accessibility": 75,
        "performance": 72
    },
    "indicators": [
        {"id": "title", "name": "Title Tag", "score": 2, "passed": true},
        {"id": "meta_description", "name": "Meta Description", "score": 1, "passed": false},
        {"id": "meta_keywords", "name": "Meta Keywords", "score": 3, "passed": true},
        {"id": "robots_txt", "name": "Robots.txt", "score": 2, "passed": true},
        {"id": "sitemap", "name": "XML Sitemap", "score": 2, "passed": true},
        {"id": "headings", "name": "Headings", "score": 1, "passed": false},
        {"id": "image_alt", "name": "Image Alt Text", "score": 0, "passed": false},
        {"id": "internal_links", "name": "Internal Links", "score": 2, "passed": true},
        {"id": "external_links", "name": "External Links", "score": 2, "passed": true},
        {"id": "url_structure", "name": "URL Structure", "score": 3, "passed": true},
        {"id": "canonical_url", "name": "Canonical URL", "score": 2, "passed": true},
        {"id": "https", "name": "HTTPS", "score": 3, "passed": true},
        {"id": "mobile_friendly", "name": "Mobile Friendly", "score": 2, "passed": true},
        {"id": "page_speed", "name": "Page Speed", "score": 1, "passed": false},
        {"id": "structured_data", "name": "Structured Data", "score": 0, "passed": false},
        {"id": "social_tags", "name": "Social Tags", "score": 1, "passed": false},
        {"id": "content_quality", "name": "Content Quality", "score": 2, "passed": true}
    ],
    "top_priorities": ["image_alt", "structured_data", "page_speed", "headings", "social_tags"],
    "model": "llama-3.1-8b-instruct",
    "prompt_tokens": 500,
    "response_tokens": 1000
}
```

### Markdown Code Block Response
```markdown
Based on my analysis, here are the results:

```json
{
    "overall_score": 85,
    "overall_summary": "Analysis summary that meets the minimum character requirement for validation.",
    "indicators": [...]
}
```

Let me know if you need any clarification.
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Service Instantiation | 100% | 5 |
| JSON Extraction | 100% | 18 |
| Schema Validation | 100% | 26 |
| Repair Logic | 100% | 8 |
| Parse Method | 100% | 10 |
| AnalysisResult DTO | 100% | 5 |
| Edge Cases | 100% | 7 |
| Performance | 100% | 3 |
| Security | 100% | 4 |
| Logging | 100% | 3 |
| Acceptance Criteria | 100% | 7 |
| **Total** | **100%** | **140+** |

## Test Execution

```bash
# Run all tests for this task
cd backend
php artisan test --filter=AIAnalysisResponseParserTest

# Run with coverage
php artisan test --filter=AIAnalysisResponseParserTest --coverage

# Run specific test category
php artisan test --filter="AIAnalysisResponseParserTest::test_je_md"
```

## Dependencies
- None (standalone parser service)

## Notes for Developer

### Expected Service Interface
```php
class AIAnalysisResponseParser
{
    public function parse(string $response): AnalysisResult;
    public function extractJson(string $response): ?array;
    public function validateSchema(array $data): bool;
    public function repairPartialJson(string $json): ?array;
}
```

### Expected DTO Structure
```php
class AnalysisResult
{
    public readonly int $overallScore;
    public readonly string $overallSummary;
    public readonly array $categoryScores;
    public readonly array $indicators;
    public readonly array $topPriorities;
    public readonly string $model;
    public readonly int $promptTokens;
    public readonly int $responseTokens;

    public static function fromArray(array $data): self;
    public function toArray(): array;
}
```

### Expected Exception
```php
class InvalidAIResponseException extends Exception
{
    public function __construct(string $message, ?string $rawResponse = null);
}
```

### JSON Schema Constants
- `overall_score`: integer, 0-100
- `overall_summary`: string, 50-500 characters
- `indicators`: array, exactly 17 items
- Indicator score: integer, 0-3
- `top_priorities`: array, max 5 items
- `category_scores`: optional object

---

## Comments

### PreTester (2026-03-05)
- Designed 140+ tests covering all acceptance criteria
- Tests are TDD-ready (will fail until implementation)
- Schema validation based on TASK-31-5 specification
- Security tests ensure XSS/SQL injection handled safely
- Performance tests ensure < 50ms parse time

---

*Last updated: 2026-03-05*
