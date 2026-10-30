# TASK-16-3: Keyword Density Checker Tool

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Low`

## Related
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Specification: [SPEC-016](../SPECIFICATIONS/SPEC-016.md)

---

## Description
Implement the Keyword Density Checker tool that analyzes keyword frequency and density in page content. Provides density percentage, placement analysis, and over-optimization warnings.

---

## Acceptance Criteria
- [x] Accept URL and target keyword as input
- [x] Extract visible text content (ignore scripts, styles, hidden elements)
- [x] Calculate keyword frequency (exact match)
- [x] Calculate keyword density percentage
- [x] Analyze keyword placement (title, headings, first paragraph)
- [x] Detect keyword stuffing (density > 3%)
- [x] Support case-insensitive matching
- [x] Support phrase keywords (multi-word)
- [x] Return structured JSON result with recommendations
- [ ] Frontend component with visual density meter (TASK-16-4)
- [x] Unit tests for density calculations

---

## Technical Details

### Input
```json
{
  "url": "https://example.com/page",
  "keyword": "seo tools"
}
```

### Output Structure
```json
{
  "keyword": "seo tools",
  "totalWords": 500,
  "keywordCount": 8,
  "density": 1.6,
  "densityStatus": "good",
  "placement": {
    "inTitle": true,
    "inH1": true,
    "inFirstParagraph": true,
    "inMetaDescription": true,
    "headings": ["H1", "H2"]
  },
  "recommendations": [
    "Keyword appears in title tag ✓",
    "Keyword appears in H1 heading ✓",
    "Keyword density is within optimal range (1-2%) ✓"
  ],
  "warnings": []
}
```

### Density Thresholds
| Density | Status | Recommendation |
|---------|--------|----------------|
| < 0.5% | low | Consider adding more keyword mentions |
| 0.5% - 2% | good | Optimal density range |
| 2% - 3% | acceptable | Slightly high, monitor |
| > 3% | stuffing | Risk of keyword stuffing penalty |

### Text Extraction
- Use Cheerio to extract text from body
- Remove script, style, noscript tags
- Remove hidden elements (display: none)
- Normalize whitespace
- Split into words for counting

---

## Dependencies
- TASK-16-1 (Tools Infrastructure)

---

## Effort Estimate
**Medium** (4-6 hours)

---

## Comments
_Agent communication log_

### [2026-02-27 19:05] Agent: Developer
Verified complete implementation in `Services/Tools/KeywordDensityChecker.php`:

**Implementation Features:**
- URL + keyword input validation
- Visible text extraction (scripts, styles, hidden elements removed)
- Keyword frequency counting with word boundary matching
- Density percentage calculation
- Density status determination (low, good, acceptable, stuffing)
- Comprehensive placement analysis:
  - Title tag
  - H1 heading
  - Meta description
  - First paragraph
  - URL
  - Image alt text
- Case-insensitive matching
- Phrase keyword support (multi-word)
- Structured JSON output with recommendations
- Warning system for keyword stuffing detection

**Density Thresholds Implemented:**
- < 0.5%: Low (needs more mentions)
- 0.5% - 2%: Good (optimal range)
- 2% - 3%: Acceptable (monitor)
- > 3%: Stuffing (risk of penalty)

**Test Coverage:**
- `KeywordDensityCheckerTest.php`: 18 tests covering density calculations, placement, warnings

**Deferred Items:**
- Frontend component with visual density meter (handled in TASK-16-4)

Task marked as Done. Backend implementation complete.

### [2026-02-27] Agent: SpecSystemAnalyst
Created task. Key challenge is accurate text extraction and keyword matching. Consider stemming for better matching in future versions.

### [2026-02-27] Agent: Developer
Starting implementation. Building on existing HtmlParserService infrastructure.
