# TASK-5-5: Question Keyword Generator

## STATUS
`To Test Locally`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create a utility class for generating question-based keyword variations. This provides additional FREE suggestions beyond Google Suggest.

## Acceptance Criteria
- [x] QuestionGenerator class with generate(keyword: string): array method
- [x] Prefixes: what is, how to, why, when, where, who, which
- [x] Return array of question keywords: ["what is {keyword}", "how to {keyword}", ...]
- [x] Trim whitespace properly
- [x] Return lowercase suggestions

## Dependencies
- None (standalone utility)

## Effort Estimate
`Small` (1 hour)

## Technical Notes
- Pure function, easily testable
- No external API calls
- Can be extended with more question patterns

---

## Comments
_Agent communication log_

### [2026-02-27 14:27] Agent: PreTester
**Tests and Implementation Already Complete**

Tests and implementation already existed and all tests pass.

**Test Results:**
- 25 tests passed (51 assertions)
- Duration: 0.73s

**Implementation:** `backend/app/Services/Fetchers/QuestionGenerator.php`
- Has `generate(?string $keyword): array` method
- Has `__invoke` for invokable support
- Supports custom prefixes via constructor
- Handles null/empty keywords gracefully

**Test File:** `backend/tests/Unit/Services/Fetchers/QuestionGeneratorTest.php`

Status: To Write Tests → To Test Locally

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: Medium (supplementary source). Risk: Low (pure function, no external dependencies). No dependencies - can be developed in parallel.
