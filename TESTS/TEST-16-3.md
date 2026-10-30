# TEST-16-3: Keyword Density Checker Tests

## STATUS
`Tests Coverage In Progress`

## Related
- Task: [TASK-16-3](../TASKS/TASK-16-3.md)
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)

---

## Test Coverage Summary

| Category | Tests | Assertions | Status |
|----------|-------|------------|--------|
| Text Extraction | 15 | 15 | ✅ |
| Keyword Frequency | 12 | 12 | ✅ |
| Density Calculation | 10 | 10 | ✅ |
| Placement Analysis | 12 | 12 | ✅ |
| Keyword Stuffing Detection | 8 | 8 | ✅ |
| Phrase Keywords | 10 | 10 | ✅ |
| Edge Cases | 12 | 12 | ✅ |
| Recommendations | 8 | 8 | ✅ |
| **Total** | **87** | **87** | ✅ |

---

## Test Scenarios

### Text Extraction (TEXT)

| ID | Scenario | HTML Input | Expected |
|----|----------|------------|----------|
| TEXT-001 | Simple paragraph | `<p>Hello world</p>` | `Hello world` |
| TEXT-002 | Remove script tags | `<script>alert(1)</script>Text` | `Text` |
| TEXT-003 | Remove style tags | `<style>.a{}</style>Text` | `Text` |
| TEXT-004 | Remove noscript | `<noscript>Hidden</noscript>Text` | `Text` |
| TEXT-005 | Remove hidden elements | `<div style="display:none">Hidden</div>` | (empty) |
| TEXT-006 | Preserve text in links | `<a href="#">Link text</a>` | `Link text` |
| TEXT-007 | Extract from multiple paragraphs | Multiple `<p>` tags | Combined text |
| TEXT-008 | Handle br tags | `Line1<br>Line2` | `Line1 Line2` |
| TEXT-009 | Decode HTML entities | `&amp;` | `&` |
| TEXT-010 | Normalize whitespace | `Word1  Word2` | `Word1 Word2` |
| TEXT-011 | Extract from headings | `<h1>Title</h1>` | `Title` |
| TEXT-012 | Skip meta tags | Meta in head | Not in extracted text |
| TEXT-013 | Extract from lists | `<ul><li>Item</li></ul>` | `Item` |
| TEXT-014 | Handle tables | `<td>Cell</td>` | `Cell` |
| TEXT-015 | Remove comments | `<!-- comment -->` | (empty) |

### Keyword Frequency (FREQ)

| ID | Scenario | Text | Keyword | Expected Count |
|----|----------|------|---------|----------------|
| FREQ-001 | Single occurrence | `SEO is great` | `SEO` | 1 |
| FREQ-002 | Multiple occurrences | `SEO SEO SEO` | `SEO` | 3 |
| FREQ-003 | Case insensitive | `Seo SEO seo` | `seo` | 3 |
| FREQ-004 | No occurrence | `Hello world` | `SEO` | 0 |
| FREQ-005 | Partial match not counted | `SEOTools` | `SEO` | 0 |
| FREQ-006 | Word boundary | `SEO-Tools` | `SEO` | 0 (hyphenated) |
| FREQ-007 | With punctuation | `SEO, SEO! SEO.` | `SEO` | 3 |
| FREQ-008 | In quotes | `"SEO" is great` | `SEO` | 1 |
| FREQ-009 | Unicode text | `Café café` | `café` | 2 |
| FREQ-010 | Numbers in keyword | `SEO2024 SEO2024` | `SEO2024` | 2 |
| FREQ-011 | Apostrophe handling | `SEO's SEOs` | `SEO` | 0 (not exact) |
| FREQ-012 | Mixed case phrase | `Seo Tools seo tools` | `seo tools` | 2 |

### Density Calculation (DENSITY)

| ID | Scenario | Words | Keyword Count | Expected Density |
|----|----------|-------|---------------|------------------|
| DENSITY-001 | 1% density | 100 words, 1 occurrence | 1 | 1.0% |
| DENSITY-002 | 2% density | 100 words, 2 occurrences | 2 | 2.0% |
| DENSITY-003 | 0% density | 100 words, 0 occurrences | 0 | 0.0% |
| DENSITY-004 | High density | 100 words, 5 occurrences | 5 | 5.0% |
| DENSITY-005 | Phrase keyword | 100 words, "seo tools" x2 | 2 | 2.0% (phrase counts as 1) |
| DENSITY-006 | Zero words edge | 0 words | 0 | 0.0% (handle division) |
| DENSITY-007 | Decimal precision | 97 words, 2 occurrences | 2 | 2.06% |
| DENSITY-008 | Very low density | 200 words, 1 occurrence | 1 | 0.5% |
| DENSITY-009 | Over 100% impossible | N/A | N/A | Max is 100% |
| DENSITY-010 | Density status good | 1.5% density | good | `good` |

### Placement Analysis (PLACE)

| ID | Scenario | HTML | Keyword | Expected |
|----|----------|------|---------|----------|
| PLACE-001 | In title | `<title>SEO Guide</title>` | `SEO` | `inTitle: true` |
| PLACE-002 | Not in title | `<title>Guide</title>` | `SEO` | `inTitle: false` |
| PLACE-003 | In H1 | `<h1>SEO Tips</h1>` | `SEO` | `inH1: true` |
| PLACE-004 | In H2 | `<h2>SEO Guide</h2>` | `SEO` | `headings: ['H2']` |
| PLACE-005 | In first paragraph | `<p>SEO is...</p>` | `SEO` | `inFirstParagraph: true` |
| PLACE-006 | In meta description | `<meta name="description" content="SEO...">` | `SEO` | `inMetaDescription: true` |
| PLACE-007 | Multiple headings | `<h1>SEO</h1><h2>SEO Tips</h2>` | `SEO` | Multiple headings |
| PLACE-008 | Case insensitive match | `<h1>seo TOOLS</h1>` | `SEO Tools` | `inH1: true` |
| PLACE-009 | Not in body | `<body>Hello world</body>` | `SEO` | Not found |
| PLACE-010 | In URL | URL: `/seo-guide` | `SEO` | `inUrl: true` |
| PLACE-011 | In alt text | `<img alt="SEO image">` | `SEO` | `inAltText: true` |
| PLACE-012 | In anchor text | `<a href="#">SEO link</a>` | `SEO` | `inAnchorText: true` |

### Keyword Stuffing Detection (STUFF)

| ID | Scenario | Density | Expected Status |
|----|----------|---------|-----------------|
| STUFF-001 | Under 0.5% | 0.3% | `low` |
| STUFF-002 | 0.5% - 2% | 1.5% | `good` |
| STUFF-003 | 2% - 3% | 2.5% | `acceptable` |
| STUFF-004 | Over 3% | 5% | `stuffing` |
| STUFF-005 | Exactly 0.5% | 0.5% | `good` |
| STUFF-006 | Exactly 2% | 2% | `good` |
| STUFF-007 | Exactly 3% | 3% | `acceptable` |
| STUFF-008 | Warning message | 5% | Warning about stuffing |

### Phrase Keywords (PHRASE)

| ID | Scenario | Keyword | Expected |
|----|----------|---------|----------|
| PHRASE-001 | Two-word phrase | `seo tools` | Matches "SEO tools" |
| PHRASE-002 | Three-word phrase | `best seo tools` | Matches exact phrase |
| PHRASE-003 | Phrase with hyphen | `seo-friendly` | Matches variations |
| PHRASE-004 | Phrase case insensitive | `SEO Tools` | Matches "seo tools" |
| PHRASE-005 | Partial phrase not counted | `seo tools` | Does not match "SEO" alone |
| PHRASE-006 | Phrase across sentences | `SEO. Tools.` | Does not match |
| PHRASE-007 | Phrase in title | `seo tools` | Detects in title |
| PHRASE-008 | Phrase density calculation | 100 words, "seo tools" x3 | 3.0% (phrase = 1 word) |
| PHRASE-009 | Long phrase | 5+ word phrase | Handles gracefully |
| PHRASE-010 | Single word vs phrase | Both tracked | Separate counts |

### Edge Cases (EC)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EC-001 | Empty HTML | `''` | Zero counts, warnings |
| EC-002 | No body | `<html><head></head></html>` | Zero counts |
| EC-003 | Very long text | 100KB of text | Handles without timeout |
| EC-004 | Special characters | `C++ programming` | Handles + symbol |
| EC-005 | Unicode keyword | `café` | Matches correctly |
| EC-006 | Empty keyword | `''` | Error or null result |
| EC-007 | Only script/style content | Script tags only | Zero text extracted |
| EC-008 | Nested elements | `<div><div>Text</div></div>` | Extracts text |
| EC-009 | Malformed HTML | `<p>Unclosed` | Still extracts |
| EC-010 | Very long keyword | 50+ char keyword | Handles gracefully |
| EC-011 | Numbers in keyword | `SEO2024` | Matches correctly |
| EC-012 | Keyword with apostrophe | `SEO's` | Matches variations |

### Recommendations (REC)

| ID | Scenario | Condition | Recommendation |
|----|----------|-----------|----------------|
| REC-001 | Keyword in title | `inTitle: true` | "✓ Keyword appears in title tag" |
| REC-002 | Keyword missing from title | `inTitle: false` | "Consider adding keyword to title" |
| REC-003 | Keyword in H1 | `inH1: true` | "✓ Keyword appears in H1" |
| REC-004 | Low density | < 0.5% | "Consider adding more keyword mentions" |
| REC-005 | Good density | 0.5-2% | "✓ Optimal keyword density" |
| REC-006 | High density | > 3% | "⚠ Risk of keyword stuffing" |
| REC-007 | Missing from first paragraph | `inFirstParagraph: false` | "Consider adding keyword to first paragraph" |
| REC-008 | In meta description | `inMetaDescription: true` | "✓ Keyword in meta description" |

---

## Test Files

| File | Location | Tests |
|------|----------|-------|
| KeywordDensityCheckerTest.php | `backend/tests/Unit/Services/Tools/` | 87 |

---

## Security Considerations

- Input sanitization for keywords
- No XSS from extracted content
- Memory limits for large pages
- Timeout protection for processing

---

## Completion Date
2026-02-27
