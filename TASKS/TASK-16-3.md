# TASK-16-3: Keyword Density Checker Tool

## STATUS
`To Write Tests`

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
- [ ] Accept URL and target keyword as input
- [ ] Extract visible text content (ignore scripts, styles, hidden elements)
- [ ] Calculate keyword frequency (exact match)
- [ ] Calculate keyword density percentage
- [ ] Analyze keyword placement (title, headings, first paragraph)
- [ ] Detect keyword stuffing (density > 3%)
- [ ] Support case-insensitive matching
- [ ] Support phrase keywords (multi-word)
- [ ] Return structured JSON result with recommendations
- [ ] Frontend component with visual density meter
- [ ] Unit tests for density calculations

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

### [2026-02-27] Agent: SpecSystemAnalyst
Created task. Key challenge is accurate text extraction and keyword matching. Consider stemming for better matching in future versions.
