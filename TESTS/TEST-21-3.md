# TEST-21-3: Static Fallback Provider Test Suite

## Task Reference
- Task: [TASK-21-3](../TASKS/TASK-21-3.md)
- Specification: [SPEC-021](../SPECIFICATIONS/SPEC-021.md)
- Feature: [FEAT-021](../FEATURES/FEAT-021.md)

## Overview
Unit tests for the StaticFallbackProvider - a rule-based SEO suggestion provider that generates recommendations without external AI dependencies. Tests cover interface implementation, all 10 indicator-specific rules, response format compliance, and edge cases.

---

## Test Files

| File | Tests | Focus |
|------|-------|-------|
| `backend/tests/Unit/Services/AI/Providers/StaticFallbackProviderTest.php` | 45+ | Core provider functionality |
| `backend/tests/Unit/Services/AI/Providers/StaticFallback/TitleRulesTest.php` | 30+ | Title tag SEO rules |
| `backend/tests/Unit/Services/AI/Providers/StaticFallback/MetaDescriptionRulesTest.php` | 25+ | Meta description SEO rules |
| `backend/tests/Unit/Services/AI/Providers/StaticFallback/RobotsRulesTest.php` | 25+ | Robots.txt rules |
| `backend/tests/Unit/Services/AI/Providers/StaticFallback/SitemapRulesTest.php` | 25+ | XML sitemap rules |
| `backend/tests/Unit/Services/AI/Providers/StaticFallback/HeadingsRulesTest.php` | 30+ | Heading hierarchy rules |
| `backend/tests/Unit/Services/AI/Providers/StaticFallback/ImageAltRulesTest.php` | 25+ | Image alt text rules |
| `backend/tests/Unit/Services/AI/Providers/StaticFallback/FilenamesRulesTest.php` | 20+ | SEO-friendly filename rules |
| `backend/tests/Unit/Services/AI/Providers/StaticFallback/UrlRulesTest.php` | 25+ | URL structure rules |
| `backend/tests/Unit/Services/AI/Providers/StaticFallback/LinksRulesTest.php` | 25+ | Internal/external link rules |
| `backend/tests/Unit/Services/AI/Providers/StaticFallback/NotFoundRulesTest.php` | 20+ | 404 error handling rules |

**Total Tests:** 300+

---

## Core Provider Tests (AC-1, AC-4, AC-5)

### Interface Implementation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SFP-001 | Implements AIProviderInterface | Reflection | Instance of interface |
| SFP-002 | No external dependencies | Code analysis | No HTTP/curl/file ops |
| SFP-003 | File at correct path | File check | `Providers/StaticFallbackProvider.php` |

### Availability Tests (AC-4)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AVA-001 | isAvailable returns true | Method call | true |
| AVA-002 | isAvailable is deterministic | Multiple calls | Always true |
| AVA-003 | No environment dependencies | Empty $_ENV | Still returns true |

### Name Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NAM-001 | getName returns static_fallback | Method call | 'static_fallback' |
| NAM-002 | getName is consistent | Multiple calls | Same value |

### Generate Method Tests (AC-3)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GEN-001 | Returns AIResponse instance | Any prompt | AIResponse object |
| GEN-002 | Returns successful response | Any prompt | success=true |
| GEN-003 | Handles empty prompt | '' | Valid AIResponse |
| GEN-004 | Includes provider identifier | Any prompt | provider='static_fallback' |
| GEN-005 | Tracks latency | Any prompt | latencyMs >= 0 |
| GEN-006 | tokensUsed is zero | Any prompt | tokensUsed=0 |

### Response Format Tests (AC-5)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FMT-001 | Has required fields | Any prompt | score, issues, suggestions, best_practices |
| FMT-002 | Score within bounds | Any prompt | 0-100 |
| FMT-003 | Issues is array | Any prompt | Array type |
| FMT-004 | Suggestions is array | Any prompt | Array type |
| FMT-005 | Best practices is array | Any prompt | Array type |
| FMT-006 | generated_at valid datetime | Any prompt | ISO 8601 format |
| FMT-007 | model is 'rule-based' | Any prompt | Fixed value |
| FMT-008 | Suggestions have valid structure | Any prompt | type, text fields |

---

## Title Rules Tests (AC-2: title)

### Length Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TIT-001 | Optimal length (50-60 chars) | 55 char title | score >= 90 |
| TIT-002 | Too short (<30 chars) | "Home" | score < 80, has issue |
| TIT-003 | Too long (>60 chars) | 80 char title | score < 85, has issue |
| TIT-004 | Empty title | "" | score < 50 |

### Keyword Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TIT-005 | Keyword at start | "Widgets - Best..." | Score boost |
| TIT-006 | Keyword in middle | "Best Widgets Store" | Moderate boost |
| TIT-007 | Keyword absent | "Welcome" + keyword "widgets" | score < 80 |
| TIT-008 | Case insensitive match | "WIDGETS" | Match found |

### Quality Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TIT-009 | All caps title | "BEST WIDGETS" | Penalty applied |
| TIT-010 | Duplicate words | "Best Best Widgets" | Issue flagged |
| TIT-011 | Stop words only | "The and or but" | score < 60 |
| TIT-012 | Brand only | "ACME Corp" | score < 70 |
| TIT-013 | Perfect title | "Best Widgets 2026 \| Premium" | score >= 95 |

---

## Meta Description Rules Tests (AC-2: meta_description)

### Length Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MET-001 | Optimal length (150-160 chars) | 155 char description | score >= 90 |
| MET-002 | Too short (<120 chars) | Short description | score < 80 |
| MET-003 | Too long (>160 chars) | 200 char description | score < 85 |
| MET-004 | Empty description | "" | score < 50 |

### Call-to-Action Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MET-005 | CTA present | "Shop now!" | Score boost |
| MET-006 | No CTA | "We sell widgets." | Suggestion generated |
| MET-007 | Multiple CTAs (spammy) | "Buy! Shop! Order!" | Penalty applied |

### Content Quality Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MET-008 | Duplicate of title | title = description | Issue flagged |
| MET-009 | Unicode support | "Découvrez nos widgets" | Valid analysis |
| MET-010 | Price mention | "Starting at $9.99" | Good for e-commerce |

---

## Robots.txt Rules Tests (AC-2: robots)

### Directive Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ROB-001 | Valid User-agent | "User-agent: *" | Recognized |
| ROB-002 | Allow directive | "Allow: /" | Parsed correctly |
| ROB-003 | Disallow directive | "Disallow: /admin" | Parsed correctly |
| ROB-004 | Sitemap reference | "Sitemap: https://..." | Recognized |
| ROB-005 | Crawl-delay | "Crawl-delay: 10" | Recognized |

### Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ROB-006 | Blocks entire site | "Disallow: /" | Warning issued |
| ROB-007 | Blocks CSS/JS | "Disallow: /css/" | Warning issued |
| ROB-008 | Exposes admin paths | "Disallow: /admin" | Note about exposure |

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ROB-009 | Empty robots.txt | "" | Default suggestions |
| ROB-010 | Malformed directives | "InvalidEntry" | Graceful handling |
| ROB-011 | Large file | 100KB+ content | Performance OK |

---

## Sitemap Rules Tests (AC-2: sitemap)

### Structure Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SIT-001 | Valid XML structure | Proper XML | Parsed correctly |
| SIT-002 | Has urlset root | `<urlset>` | Recognized |
| SIT-003 | Has url entries | `<url><loc>...</loc></url>` | Count extracted |
| SIT-004 | Has lastmod | `<lastmod>2026-01-01</lastmod>` | Recognized |

### Coverage Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SIT-005 | URL count reported | 100 URLs | Count in response |
| SIT-006 | Empty sitemap | 0 URLs | Warning issued |
| SIT-007 | Missing required fields | URL without loc | Issue flagged |

### Format Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SIT-008 | Invalid XML | Not XML | Error handled |
| SIT-009 | Gzip detection | .xml.gz | Suggestion to decompress |
| SIT-010 | Index file | `<sitemapindex>` | Recognized |

---

## Headings Rules Tests (AC-2: headings)

### H1 Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HEA-001 | Single H1 | One H1 element | score >= 90 |
| HEA-002 | Multiple H1s | Two H1 elements | Issue flagged |
| HEA-003 | No H1 | No H1 element | Issue flagged |
| HEA-004 | Empty H1 | `<h1></h1>` | Issue flagged |

### Hierarchy Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HEA-005 | Valid hierarchy | H1 > H2 > H3 | score >= 85 |
| HEA-006 | Skipped level | H1 > H3 (no H2) | Warning issued |
| HEA-007 | Multiple H2s | Multiple H2 sections | Valid structure |

### Content Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| HEA-008 | H1 matches title | Same content | Good SEO |
| HEA-009 | Keyword in H1 | Keyword present | Score boost |
| HEA-010 | Empty headings | `<h2></h2>` | Issue flagged |

---

## Image Alt Rules Tests (AC-2: image_alt)

### Alt Text Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IMG-001 | All images have alt | 100% coverage | score >= 95 |
| IMG-002 | Some missing alt | 50% coverage | score proportional |
| IMG-003 | No images | Empty list | N/A or high score |
| IMG-004 | Empty alt | `alt=""` | Flagged as decorative |

### Quality Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IMG-005 | Generic alt text | "image" or "photo" | Warning issued |
| IMG-006 | Too long alt | 150+ chars | Warning issued |
| IMG-007 | Keyword stuffing | Repeated keywords | Warning issued |

### Suggestion Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IMG-008 | Missing alt suggestion | Image without alt | Suggestion generated |
| IMG-009 | Multiple images missing | 5 images without alt | All listed |

---

## Filenames Rules Tests (AC-2: filenames)

### SEO-Friendly Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FIL-001 | Lowercase with hyphens | "best-widgets.jpg" | score >= 90 |
| FIL-002 | Spaces in name | "best widgets.jpg" | Issue flagged |
| FIL-003 | Uppercase letters | "BestWidgets.jpg" | Suggestion to lowercase |
| FIL-004 | Special characters | "widget@2026.jpg" | Issue flagged |

### Pattern Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FIL-005 | Generic names | "image1.jpg" | Warning issued |
| FIL-006 | Too long filename | 100+ char name | Warning issued |
| FIL-007 | Hash in filename | "abc123def456.jpg" | Warning issued |

---

## URL Rules Tests (AC-2: url)

### Structure Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| URL-001 | Clean URL | "/products/widgets" | score >= 90 |
| URL-002 | Query parameters | "?id=123&cat=5" | Suggestion to clean |
| URL-003 | Hash fragment | "#section" | Note about indexing |
| URL-004 | Trailing slash | "/page/" | Consistency check |

### SEO Quality Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| URL-005 | Keywords in URL | "/best-widgets-2026" | Score boost |
| URL-006 | Dynamic URLs | "/product.php?id=123" | Issue flagged |
| URL-007 | Too long URL | 100+ char path | Warning issued |
| URL-008 | Underscores | "/best_widgets" | Hyphen suggestion |

---

## Links Rules Tests (AC-2: links)

### Internal Links Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LNK-001 | Adequate internal links | 10+ internal links | score >= 85 |
| LNK-002 | No internal links | 0 internal links | Issue flagged |
| LNK-003 | Too many internal | 100+ links | Warning about dilution |

### External Links Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LNK-004 | nofollow on external | rel="nofollow" | Good practice |
| LNK-005 | Missing nofollow | External without rel | Suggestion added |
| LNK-006 | noopener for target blank | rel="noopener" | Security good |
| LNK-007 | Missing noopener | target="_blank" no noopener | Security issue |

### Broken Links Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LNK-008 | Broken link detected | 404 URL | Issue flagged |
| LNK-009 | Multiple broken links | 3+ broken | Severity increased |

---

## 404 Rules Tests (AC-2: 404)

### Error Analysis Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NFO-001 | Page not found | 404 error | Causes analyzed |
| NFO-002 | Redirect suggestion | Old URL | Suggestion provided |
| NFO-003 | Custom 404 check | No custom page | Recommendation |

### Common Causes Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| NFO-004 | URL typo | Misspelled URL | Correction suggested |
| NFO-005 | Deleted page | Removed content | Redirect suggested |
| NFO-006 | Case sensitivity | Mixed case URL | Normalization suggested |

---

## Edge Cases & Security Tests

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EDG-001 | Very long input | 10000 char string | Handled gracefully |
| EDG-002 | Special characters | `<script>alert(1)</script>` | Sanitized output |
| EDG-003 | Unicode content | "Лучшие товары 你好" | Valid response |
| EDG-004 | SQL injection attempt | "'; DROP TABLE--" | No impact |
| EDG-005 | Null byte injection | "test\x00injection" | Handled safely |

### Performance Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PRF-001 | Response time | Any prompt | < 50ms |
| PRF-002 | Memory usage | 100 iterations | < 1MB growth |
| PRF-003 | Concurrent requests | Multiple calls | Thread-safe |

---

## Coverage Targets

| Component | Target | Focus Areas |
|-----------|--------|-------------|
| StaticFallbackProvider | 100% | Interface, availability, response format |
| Title Rules | 95%+ | Length, keywords, quality |
| Meta Description Rules | 95%+ | Length, CTA, content |
| Robots Rules | 90%+ | Directives, security |
| Sitemap Rules | 90%+ | Structure, coverage |
| Headings Rules | 95%+ | H1, hierarchy |
| Image Alt Rules | 90%+ | Coverage, quality |
| Filenames Rules | 85%+ | SEO-friendly patterns |
| URL Rules | 90%+ | Structure, keywords |
| Links Rules | 90%+ | Internal, external, security |
| 404 Rules | 85%+ | Causes, suggestions |

---

## Test Execution

```bash
# Run all StaticFallback tests
php artisan test --filter=StaticFallback

# Run specific indicator tests
php artisan test --filter=TitleRules
php artisan test --filter=MetaDescriptionRules
php artisan test --filter=RobotsRules
# ... etc

# Run with coverage
php artisan test --coverage --filter=StaticFallback
```

---

## Dependencies
- PHPUnit 10+
- Laravel 11.x testing utilities
- AIProviderInterface (TASK-21-1)
- AIResponse DTO (TASK-21-1)
- SuggestionResult DTO (TASK-21-1)

---

## Notes for Developer

### Implementation Location
- Provider: `backend/app/Services/AI/Providers/StaticFallbackProvider.php`

### Implementation Checklist
- [ ] Create StaticFallbackProvider class
- [ ] Implement AIProviderInterface
- [ ] Create generate() method with rule routing
- [ ] Implement isAvailable() returning true
- [ ] Implement getName() returning 'static_fallback'
- [ ] Create indicator-specific analysis methods:
  - [ ] scoreTitle() - title tag analysis
  - [ ] scoreMetaDescription() - meta description analysis
  - [ ] scoreRobots() - robots.txt analysis
  - [ ] scoreSitemap() - sitemap analysis
  - [ ] scoreHeadings() - heading hierarchy analysis
  - [ ] scoreImageAlt() - image alt text analysis
  - [ ] scoreFilenames() - filename analysis
  - [ ] scoreUrl() - URL structure analysis
  - [ ] scoreLinks() - link analysis
  - [ ] score404() - 404 error analysis
- [ ] Create response formatting method
- [ ] Handle edge cases gracefully

---

## Comments
_Agent log_

### [2026-03-01 18:53 GMT+2] PreTester - Test Design Complete
Created test documentation and unit test files for TASK-21-3.

**Test Files Created:**
1. `backend/tests/Unit/Services/AI/Providers/StaticFallbackProviderTest.php` - 45 tests (core)
2. `backend/tests/Unit/Services/AI/Providers/StaticFallback/TitleRulesTest.php` - 30 tests
3. `backend/tests/Unit/Services/AI/Providers/StaticFallback/MetaDescriptionRulesTest.php` - 25 tests
4. `backend/tests/Unit/Services/AI/Providers/StaticFallback/RobotsRulesTest.php` - 25 tests (NEW)
5. `backend/tests/Unit/Services/AI/Providers/StaticFallback/SitemapRulesTest.php` - 25 tests (NEW)
6. `backend/tests/Unit/Services/AI/Providers/StaticFallback/HeadingsRulesTest.php` - 30 tests (NEW)
7. `backend/tests/Unit/Services/AI/Providers/StaticFallback/ImageAltRulesTest.php` - 25 tests (NEW)
8. `backend/tests/Unit/Services/AI/Providers/StaticFallback/FilenamesRulesTest.php` - 20 tests (NEW)
9. `backend/tests/Unit/Services/AI/Providers/StaticFallback/UrlRulesTest.php` - 25 tests (NEW)
10. `backend/tests/Unit/Services/AI/Providers/StaticFallback/LinksRulesTest.php` - 25 tests (NEW)
11. `backend/tests/Unit/Services/AI/Providers/StaticFallback/NotFoundRulesTest.php` - 20 tests (NEW)

**Total Tests:** 300+

**Acceptance Criteria Coverage:**
- AC-1: StaticFallbackProvider class created - Covered in core tests
- AC-2: Indicator-specific rules - All 10 indicators have dedicated test files
- AC-3: Response generation - Covered in core tests
- AC-4: Availability check - Covered in AVA-* tests
- AC-5: Response format - Covered in FMT-* tests
- AC-6: Unit tests - 300+ tests across 11 files

**TDD Approach:** Tests will fail until implementation is complete. This is expected.

Status: `To Write Tests` → `Tests Coverage In Progress`

---
*Created by PreTester on 2026-03-01*
