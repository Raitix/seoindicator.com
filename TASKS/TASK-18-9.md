# TASK-18-9: URLChecker - URL Structure Indicator

## STATUS
`Tests Written, Awaiting Implementation`

## PRIORITY
`High`

## RISK
`Low`

## Description
Implement indicator checker for URL structure analysis: HTTPS, length, keywords, special characters, dynamic parameters, readability.

## Acceptance Criteria
- [ ] Implements IndicatorCheckerInterface
- [ ] Checks HTTPS protocol usage
- [ ] Validates URL length (≤75 chars optimal)
- [ ] Detects keywords in URL path
- [ ] Checks for special characters (alphanumeric, hyphens, underscores only)
- [ ] Identifies dynamic parameters (? and &)
- [ ] Evaluates path segment readability
- [ ] Returns score 1-3 based on criteria
- [ ] Generates actionable suggestions
- [ ] Weight: 0.08, Category: technical
- [ ] Unit tests covering all criteria combinations

## Dependencies
- TASK-18-3 (IndicatorCheckerInterface)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- id(): 'url'
- name(): 'URL Structure'
- Parse URL with parse_url() for analysis
- Suggestions should be specific (e.g., "URL contains 3 dynamic parameters")

---

## Comments
### [2026-02-28] SpecSystemAnalyst
Created from SPEC-018 Section 4.3.1. First indicator checker implementation.


### [2026-02-28] PreTester
**Test Coverage Designed:**
- Created TEST-TASK-18-9.md with comprehensive test scenarios
- Tests cover all acceptance criteria
- Status: **Tests Written, Awaiting Implementation**

### [2026-03-18] PreTester
**Test Files Created:**
- `PROJECT-SEOINDICATOR/TESTS/TEST-TASK-18-9.md` - Test documentation
- `PROJECT-SEOINDICATOR/backend/tests/Unit/Services/SEO/Checkers/URLCheckerTest.php` - 107 tests

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| SI | 6 | Service Instantiation (id, name, category, weight) |
| HTTPS | 8 | HTTPS Protocol Checks |
| LENGTH | 10 | URL Length Validation (≤75 optimal) |
| KEYWORDS | 8 | Keywords in URL Path |
| CHARS | 8 | Special Characters (alphanumeric, hyphens, underscores only) |
| PARAMS | 8 | Dynamic Parameters (? and &) |
| READABLE | 6 | Path Readability |
| SCORE | 12 | Score Calculation (1-3 based on criteria) |
| SUGGEST | 10 | Suggestions Generation |
| ERROR | 6 | Error Handling |
| EDGE | 10 | Edge Cases (fragment, subdomain, port, IDN, IP) |
| SEC | 4 | Security |
| AC | 11 | Acceptance Criteria Verification |
| Data Providers | 8 | Parameterized tests |

**Key Patterns:**
- HTTPS: Required for score 3, HTTP gets ≤2
- Length: ≤50 optimal, 51-75 good, 76-100 needs improvement, >100 poor
- Keywords: Detected from page title, hyphen-separated in URL
- Special chars: Only alphanumeric, hyphens, underscores allowed
- Parameters: 0 optimal, 1-2 acceptable, 3+ flagged
- Session IDs: Detected and flagged
- UTM params: Excluded from problematic count
- Path segments: Counted for readability

**Status:** Tests Written, Awaiting Implementation ✅