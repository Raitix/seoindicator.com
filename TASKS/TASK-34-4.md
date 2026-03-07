# TASK-34-4: MentionParser Service

## STATUS
`In Development`

## PRIORITY
`High`

## RISK
`Low`

## Specification
SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

---

## Description

Create the `MentionParser` service for extracting brand mentions from AI responses, determining mention types, and extracting context.

---

## Acceptance Criteria

- [ ] `MentionParser` class created at `app/Services/AIVisibility/MentionParser.php`

- [ ] `extractMentions()` method implemented
  - [ ] Accepts: `response` (string), `targetBrands` (array)
  - [ ] Returns array of: `{ brand, position, context, type }`
  - [ ] Uses regex with word boundaries for exact matching
  - [ ] Case-insensitive matching
  - [ ] Handles multiple mentions of same brand

- [ ] `extractContext()` method implemented
  - [ ] Accepts: `text`, `position`, `radius` (default: 100)
  - [ ] Returns surrounding text around mention
  - [ ] Handles edge cases (start/end of string)
  - [ ] Preserves original text, doesn't truncate words

- [ ] `detectMentionType()` method implemented
  - [ ] Accepts: `context` (string)
  - [ ] Returns: `list`, `comparison`, `recommendation`, `neutral`
  - [ ] Pattern detection:
    - "top 10", "best", "including" → list
    - "vs", "compared to", "better than" → comparison
    - "I recommend", "you should use" → recommendation
    - Default → neutral

- [ ] `extractCompetitorMentions()` method implemented
  - [ ] Accepts: `response`, `competitors` (array)
  - [ ] Returns array of competitor mentions with position and context
  - [ ] Same logic as brand mention extraction

- [ ] Edge cases handled
  - [ ] Empty response text
  - [ ] No mentions found
  - [ ] Brand name with special characters
  - [ ] Unicode text support
  - [ ] Very long responses (>10KB)

- [ ] Unit tests created
  - [ ] Test brand mention extraction
  - [ ] Test context extraction at boundaries
  - [ ] Test mention type detection
  - [ ] Test competitor mention extraction
  - [ ] Test edge cases

---

## Dependencies
- TASK-34-3 (AIQueryEngine - for integration)

---

## Effort Estimate
**3 hours**

---

## Technical Notes

- Use `preg_match_all()` with `PREG_OFFSET_CAPTURE` for position tracking
- Escape brand names with `preg_quote()` before regex
- Consider performance for large texts (streaming vs loading all)
- Can be made more sophisticated later with NLP libraries

---

*Created by SpecSystemAnalyst on 2026-03-07*

---

## Comments

### PreTester Test Design (2026-03-07 19:30 UTC)

**Status:** Tests Complete ✅

**Test Coverage:** 86 unit tests across 7 categories

**Files Created:**
- `backend/tests/Unit/Services/AIVisibility/MentionParserTest.php` (86 tests)
- `TESTS/TEST-34-4.md` (test documentation)

**Test Categories:**
| Category | Tests | Purpose |
|----------|-------|---------|
| Class Structure | 2 | Namespace, instantiation |
| extractMentions() | 25 | Brand extraction, position, word boundaries, unicode |
| extractContext() | 14 | Context extraction, radius, edge cases |
| detectMentionType() | 16 | Type detection (list/comparison/recommendation/neutral) |
| extractCompetitorMentions() | 9 | Competitor mention extraction |
| Edge Cases | 15 | Special characters, unicode, large responses |
| Integration | 5 | Full flows, performance |

**Key Test Patterns:**
1. **Word boundary matching:** Tests verify `\b` regex behavior
2. **Case-insensitive matching:** Multiple case variants tested
3. **Special character escaping:** Brands with `$`, `+`, `.`, `/`, `(`, `[`, etc.
4. **Unicode support:** Japanese, Cyrillic, emoji in text and brand names
5. **Performance:** 100 brands in <1s, 50KB response handling

**Mention Type Detection Patterns:**
- `list`: "top 10", "best", "including"
- `comparison`: "vs", "compared to", "better than"
- `recommendation`: "I recommend", "you should use", "I suggest"
- `neutral`: default fallback

**TDD Pattern:** All 86 tests will fail until `MentionParser` class is implemented.

**Expected Interface:**
```php
class MentionParser {
    public function extractMentions(string $response, array $targetBrands): array;
    public function extractContext(string $text, int $position, int $radius = 100): string;
    public function detectMentionType(string $context): string;
    public function extractCompetitorMentions(string $response, array $competitors): array;
}
```

**Dependencies:** None - Pure PHP regex/string manipulation, no external services.

**Status:** To Develop → Hand off to Developer

**Priority: HIGH (confirmed)** ✅

**Rationale:**
- **Core Parsing Service for GEO Feature** - MentionParser extracts brand mentions from AI responses, determining mention types and context
- **Enables Analytics Pipeline** - Without this service, brand visibility analytics cannot be calculated
- **MVP Critical Path** - Task is in Phase 1 MVP (Core Tracking) of SPEC-034
- **Business Value** - Directly enables the Generative Engine Optimization feature's core value proposition: understanding how brands appear in AI responses

**Risk: LOW (confirmed)** ✅

**Rationale:**
- **Pure PHP Logic** - No external API calls, all regex and string manipulation
- **Standard Laravel Service** - Follows established patterns from other services
- **Well-Defined Scope** - Clear acceptance criteria with specific methods
- **Testable in Isolation** - Can be unit tested without external dependencies

**Dependency Analysis:**

| Dependency | Status | Ready |
|------------|--------|-------|
| TASK-34-3 (AIQueryEngine) | To Write Tests | ❌ NOT IMPLEMENTED |

**TDD Pattern Applied:**

PreTester CAN design tests immediately using:
1. **Mocked AI Response Strings** - Sample responses for testing mention extraction
2. **Interface-Based Testing** - Define expected input/output contracts
3. **Test Categories:**
   - `extractMentions()` with word boundaries, case-insensitivity
   - `extractContext()` with edge cases (start/end of string)
   - `detectMentionType()` with pattern detection (list, comparison, recommendation, neutral)
   - `extractCompetitorMentions()` with same logic as brand mentions
   - Edge cases: empty response, no mentions, special characters, unicode, long text

**Parallel Development Pattern:**
- PreTester designs tests NOW with mocked response strings
- Tests don't need AIQueryEngine to exist - use static test fixtures
- Implementation proceeds independently
- Integration testing happens when TASK-34-3 completes

**Downstream Impact:**

| Task | Depends On TASK-34-4 |
|------|---------------------|
| TASK-34-5 (AIVisibilityService) | Uses MentionParser for analysis |
| TASK-34-6 (AIVisibilityController) | Exposes mention data via API |
| TASK-34-7 (Query Job) | Uses MentionParser in background |
| All frontend dashboard tasks | Display mention analytics |

**Execution Order:**
1. TASK-34-1 (Migrations) → Done first
2. TASK-34-2 (Models) → To Write Tests
3. TASK-34-3 (AIQueryEngine) → To Write Tests (parallel with this task)
4. TASK-34-4 (MentionParser) → This task (TDD with static fixtures)
5. TASK-34-5, 34-6, 34-7 → After both services ready

**Technical Notes from Task:**
- Uses `preg_match_all()` with `PREG_OFFSET_CAPTURE` for position tracking
- Brand names escaped with `preg_quote()` before regex
- Performance consideration for large texts (>10KB responses)

**Free Tools Compliance:**
- Pure PHP implementation - no external services required
- Regex-based parsing - no paid NLP libraries needed
- Can be enhanced later with ML if needed (out of scope for MVP)

**Handoff to PreTester:** Ready for test design. Focus on:
- `extractMentions()` with word boundaries and case-insensitivity
- `extractContext()` radius handling and edge cases
- `detectMentionType()` pattern detection accuracy
- `extractCompetitorMentions()` reuse of brand extraction logic
- Unicode text support and special character handling
- Performance with long responses (>10KB)

**Status:** `Prioritization In Progress` → `To Write Tests`
