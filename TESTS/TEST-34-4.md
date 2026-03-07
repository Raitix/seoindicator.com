# TEST-34-4: MentionParser Service Tests

## Overview
Unit tests for the `MentionParser` service that extracts brand mentions from AI responses.

## Test File
`backend/tests/Unit/Services/AIVisibility/MentionParserTest.php`

## Test Summary
- **Total Tests:** 86
- **Categories:** 7
- **TDD Pattern:** Tests written before implementation

---

## Test Categories

### AC-1: Class Structure (2 tests)
| Test ID | Description |
|---------|-------------|
| MP-001 | Class exists at correct namespace |
| MP-002 | Class is instantiable |

### AC-2: extractMentions() Method (25 tests)
| Test ID | Description |
|---------|-------------|
| MP-003 | Returns array |
| MP-004 | Finds single brand mention |
| MP-005 | Returns correct structure (brand, position, context, type) |
| MP-006 | Returns correct position |
| MP-007 | Case-insensitive matching |
| MP-008 | Word boundary matching (no partial matches) |
| MP-009 | Multiple brands in same response |
| MP-010 | Multiple mentions of same brand |
| MP-011 | Empty response returns empty array |
| MP-012 | No mentions found returns empty array |
| MP-013 | Empty brands array returns empty array |
| MP-014 | Brand with special characters (- and +) |
| MP-015 | Brand with dots |
| MP-016 | Brand with apostrophe |
| MP-017 | Unicode text support |
| MP-018 | Japanese text support |
| MP-019 | Emoji in text |
| MP-020 | Very long response (>10KB) |
| MP-021 | Position is byte-safe for unicode |

### AC-3: extractContext() Method (14 tests)
| Test ID | Description |
|---------|-------------|
| MP-022 | Returns string |
| MP-023 | Extracts surrounding text |
| MP-024 | Default radius (100 chars) |
| MP-025 | At start of string |
| MP-026 | At end of string |
| MP-027 | Radius exceeds text length |
| MP-028 | Empty text handling |
| MP-029 | Preserves original text |
| MP-030 | Does not truncate words |
| MP-031 | Unicode characters |
| MP-032 | Emoji in context |
| MP-033 | Negative position handled |
| MP-034 | Position beyond text length |
| MP-035 | Custom radius parameter |

### AC-4: detectMentionType() Method (16 tests)
| Test ID | Description |
|---------|-------------|
| MP-036 | Returns string |
| MP-037 | Returns valid type (list/comparison/recommendation/neutral) |
| MP-038 | Detects list with "top 10" |
| MP-039 | Detects list with "best" |
| MP-040 | Detects list with "including" |
| MP-041 | Detects comparison with "vs" |
| MP-042 | Detects comparison with "compared to" |
| MP-043 | Detects comparison with "better than" |
| MP-044 | Detects recommendation with "I recommend" |
| MP-045 | Detects recommendation with "should use" |
| MP-046 | Detects recommendation with "suggest" |
| MP-047 | Returns neutral for plain text |
| MP-048 | Case-insensitive pattern matching |
| MP-049 | Empty context returns neutral |
| MP-050 | List pattern priority |
| MP-051 | Comparison vs recommendation priority |

### AC-5: extractCompetitorMentions() Method (9 tests)
| Test ID | Description |
|---------|-------------|
| MP-052 | Returns array |
| MP-053 | Finds competitor mention |
| MP-054 | Returns correct structure |
| MP-055 | Multiple competitors |
| MP-056 | Empty response handling |
| MP-057 | No competitors found |
| MP-058 | Empty competitors array |
| MP-059 | Case-insensitive matching |
| MP-060 | Word boundary matching |

### AC-6: Edge Cases (15 tests)
| Test ID | Description |
|---------|-------------|
| MP-061 | Empty response text handled |
| MP-062 | No mentions found handled |
| MP-063 | Brand name with special regex characters ($, +) |
| MP-064 | Brand name with forward slash |
| MP-065 | Brand name with backslash |
| MP-066 | Brand name with parentheses |
| MP-067 | Brand name with brackets |
| MP-068 | Very long brand name |
| MP-069 | Response with newlines |
| MP-070 | Response with tabs |
| MP-071 | Response with HTML entities |
| MP-072 | Mixed case brands list |
| MP-073 | Very large response (50KB) |
| MP-074 | Unicode brand name |
| MP-075 | Accented characters in brand |

### Integration Scenarios (5 tests)
| Test ID | Description |
|---------|-------------|
| MP-076 | Full flow with extractMentions and detectMentionType |
| MP-077 | Multiple mentions with different types |
| MP-078 | Context extraction with mention |
| MP-079 | Competitor and brand mentions in same response |
| MP-080 | Performance with 100 brands |

---

## Key Patterns

### Mention Type Detection Patterns
| Type | Patterns |
|------|----------|
| list | "top 10", "top 5", "best", "including", "among the" |
| comparison | "vs", "compared to", "better than", "versus", "alternative to" |
| recommendation | "I recommend", "you should use", "I suggest", "try" |
| neutral | (default when no patterns match) |

### Word Boundary Matching
- Uses regex word boundaries (`\b`) for exact matching
- `SEOIndicatorPro` should NOT match `SEOIndicator`
- Case-insensitive matching with `i` flag

### Context Extraction
- Default radius: 100 characters
- Handles edge cases at start/end of string
- Preserves original text (no truncation)
- Unicode-safe byte counting

### Special Character Escaping
- All brand names escaped with `preg_quote()` before regex
- Supports: `-`, `+`, `.`, `'`, `/`, `\`, `(`, `)`, `[`, `]`

---

## Expected Interface

```php
namespace App\Services\AIVisibility;

class MentionParser
{
    /**
     * Extract brand mentions from AI response.
     *
     * @param string $response The AI response text
     * @param array $targetBrands Array of brand names to search for
     * @return array Array of mentions with: brand, position, context, type
     */
    public function extractMentions(string $response, array $targetBrands): array;

    /**
     * Extract context around a position in text.
     *
     * @param string $text The full text
     * @param int $position The position to center around
     * @param int $radius Number of characters on each side (default: 100)
     * @return string The surrounding context
     */
    public function extractContext(string $text, int $position, int $radius = 100): string;

    /**
     * Detect the type of mention based on context.
     *
     * @param string $context The context around the mention
     * @return string One of: list, comparison, recommendation, neutral
     */
    public function detectMentionType(string $context): string;

    /**
     * Extract competitor mentions from AI response.
     *
     * @param string $response The AI response text
     * @param array $competitors Array of competitor names to search for
     * @return array Array of competitor mentions with position and context
     */
    public function extractCompetitorMentions(string $response, array $competitors): array;
}
```

---

## Return Structures

### Mention Object
```php
[
    'brand' => string,      // The matched brand name
    'position' => int,      // Byte offset in response
    'context' => string,    // Surrounding text
    'type' => string,       // list|comparison|recommendation|neutral
]
```

---

## Dependencies
- **None** - Pure PHP regex and string manipulation
- No external services required
- No database queries

---

## Performance Considerations
- Test MP-080: 100 brands should be processed in < 1 second
- Test MP-073: 50KB response should be handled gracefully
- Uses `preg_match_all()` with `PREG_OFFSET_CAPTURE` for efficient position tracking

---

## Security Considerations
- All brand names escaped with `preg_quote()` to prevent regex injection
- No SQL queries or external API calls
- No sensitive data processing

---

*Created by PreTester on 2026-03-07*
*Task: TASK-34-4*
