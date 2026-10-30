# TEST-TASK-18-10: TitleChecker - Title Tag Indicator

## Task Reference
- Task: [TASK-18-10](../TASKS/TASK-18-10.md)
- Specification: SPEC-018 (On-Page SEO Indicators)
- Feature: FEAT-018 (On-Page SEO Analysis)

## Overview
Unit tests for TitleChecker - the highest-weight content indicator (0.10) that analyzes HTML title tags for SEO optimization.

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `backend/tests/Unit/Services/SEO/Checkers/TitleCheckerTest.php` | 45+ | TitleChecker unit tests |

## Test Scenarios

### TC-001: Service Instantiation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-001.1 | Class exists | - | Class instantiable |
| TC-001.2 | Implements interface | - | Implements IndicatorCheckerInterface |
| TC-001.3 | Extends base class | - | Extends BaseIndicatorChecker |

### TC-002: Indicator Metadata
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-002.1 | ID is correct | `id()` | Returns 'title_tag' |
| TC-002.2 | Name is correct | `name()` | Returns 'Title Tag' |
| TC-002.3 | Category is correct | `category()` | Returns 'content' |
| TC-002.4 | Weight is correct | `weight()` | Returns 0.10 |

### TC-003: Title Existence Check
| ID | Scenario | Input HTML | Expected Output |
|----|----------|------------|-----------------|
| TC-003.1 | Title exists | `<title>Test Page</title>` | passed=true, score=1+ |
| TC-003.2 | Title missing | `<html><body></body></html>` | passed=false, score=0 |
| TC-003.3 | Empty title | `<title></title>` | passed=false, score=0 |
| TC-003.4 | Whitespace title | `<title>   </title>` | passed=false, score=0 |

### TC-004: Title Length Validation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-004.1 | Optimal length (50-60) | 55 char title | score=3, passed=true |
| TC-004.2 | Good length (30-49) | 40 char title | score=2, passed=true |
| TC-004.3 | Too short (<30) | 20 char title | score=1, passed=false |
| TC-004.4 | Too long (>60) | 70 char title | score=2, passed=false |
| TC-004.5 | Very short (<10) | 5 char title | score=1, passed=false |
| TC-004.6 | Very long (>100) | 120 char title | score=1, passed=false |
| TC-004.7 | Exactly 50 chars | 50 char title | score=3 |
| TC-004.8 | Exactly 60 chars | 60 char title | score=3 |

### TC-005: Keyword Detection
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-005.1 | Keyword in title | title="Best SEO Tools Guide", keyword="SEO" | details.keywords_found=['SEO'] |
| TC-005.2 | Multiple keywords | title="SEO Guide 2024", keywords=['SEO', 'Guide'] | details.keywords_found=['SEO', 'Guide'] |
| TC-005.3 | No keyword match | title="Welcome to Our Site", keyword="SEO" | details.keywords_found=[] |
| TC-005.4 | Case insensitive match | title="SEO GUIDE", keyword="seo" | details.keywords_found=['seo'] |
| TC-005.5 | Partial word match | title="SEOptimization", keyword="SEO" | details.keywords_found=['SEO'] |

### TC-006: Multiple Title Tags
| ID | Scenario | Input HTML | Expected Output |
|----|----------|------------|-----------------|
| TC-006.1 | First title used | `<title>First</title><title>Second</title>` | title='First' |
| TC-006.2 | Multiple with warning | 2 title tags | details.multiple_titles=true |

### TC-007: Details Structure
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-007.1 | Has title value | valid title | details.title present |
| TC-007.2 | Has length | valid title | details.length present |
| TC-007.3 | Has keywords_found | valid title | details.keywords_found array |
| TC-007.4 | Has length_status | valid title | details.length_status in ['optimal','short','long'] |

### TC-008: Suggestions Generation
| ID | Scenario | Input Result | Expected Suggestions |
|----|----------|--------------|----------------------|
| TC-008.1 | Missing title | score=0, passed=false | Suggestion type='error', message about missing title |
| TC-008.2 | Too short | score=1, length=15 | Suggestion about lengthening title |
| TC-008.3 | Too long | score=2, length=80 | Suggestion about shortening title |
| TC-008.4 | Optimal | score=3, passed=true | No suggestions or info-level |
| TC-008.5 | No keywords | score=2, keywords=[] | Suggestion about adding keywords |
| TC-008.6 | Multiple titles | multiple=true | Warning about multiple titles |

### TC-009: Edge Cases
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-009.1 | Null crawler | crawler=null | Error result or exception |
| TC-009.2 | Empty HTML | `<html></html>` | passed=false, score=0 |
| TC-009.3 | Unicode in title | title="中文标题 测试" | Handles correctly |
| TC-009.4 | Emoji in title | title="Best Site 🚀 2024" | Handles correctly |
| TC-009.5 | HTML entities | title="Tom &amp; Jerry" | Decodes to "Tom & Jerry" |
| TC-009.6 | Newlines in title | title="Line1\nLine2" | Normalized |
| TC-009.7 | Very long title | 500+ char title | Handles gracefully |

### TC-010: Score Calculation
| ID | Scenario | Title State | Expected Score |
|----|----------|-------------|----------------|
| TC-010.1 | Missing title | No title | 0 |
| TC-010.2 | Empty title | Empty string | 0 |
| TC-010.3 | Short + no keywords | 15 chars, no keywords | 1 |
| TC-010.4 | Short + keywords | 20 chars, has keywords | 1 |
| TC-010.5 | Good length + keywords | 45 chars, has keywords | 2 |
| TC-010.6 | Optimal + keywords | 55 chars, has keywords | 3 |
| TC-010.7 | Long + keywords | 80 chars, has keywords | 2 |
| TC-010.8 | Very long | 150 chars | 1 |

### TC-011: Integration with Base Class
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-011.1 | Error handling | Invalid HTML | Returns error result |
| TC-011.2 | Logging | Any check | Logs debug info |
| TC-011.3 | Default values | - | Uses base defaults correctly |

## Mock Data

### Valid HTML with Optimal Title
```html
<!DOCTYPE html>
<html>
<head>
    <title>Best SEO Tools for Digital Marketing in 2024</title>
</head>
<body>
    <h1>Welcome</h1>
</body>
</html>
```

### HTML Missing Title
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
    <h1>Page Without Title</h1>
</body>
</html>
```

### HTML with Multiple Titles
```html
<!DOCTYPE html>
<html>
<head>
    <title>First Title</title>
    <title>Second Title</title>
</head>
<body></body>
</html>
```

### HTML with Short Title
```html
<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
</head>
<body></body>
</html>
```

### HTML with Long Title
```html
<!DOCTYPE html>
<html>
<head>
    <title>This is an extremely long title that goes well beyond the recommended length for SEO purposes and should be shortened</title>
</head>
<body></body>
</html>
```

## Coverage Targets
- Line Coverage: ≥ 90%
- Branch Coverage: ≥ 85%
- Method Coverage: 100%

## Test Execution
```bash
# Run all TitleChecker tests
php artisan test --filter=TitleCheckerTest

# Run with coverage
php artisan test --filter=TitleCheckerTest --coverage
```

## Dependencies
- TASK-18-3 (IndicatorCheckerInterface) - Done ✅
- Symfony DomCrawler component

## Notes for Developer
1. Create `TitleChecker.php` in `backend/app/Services/SEO/Checkers/`
2. Extend `BaseIndicatorChecker`
3. Implement `name()`, `performCheck()`, `getSuggestions()`
4. Set `defaultWeight = 0.10` and `defaultCategory = 'content'`
5. Use `$this->extractText($crawler, 'title')` to get title
6. Use `$this->countElements($crawler, 'title')` to check for multiple titles
7. Keywords should be passed via `$options['keywords']` array

## Scoring Algorithm
```
Score 0: No title or empty title
Score 1: Title exists but too short (<30) or too long (>100) or no keywords when expected
Score 2: Title good length (30-49 or 61-100) or optimal length but missing keywords
Score 3: Title optimal length (50-60) with relevant keywords
```

## Comments
### [2026-03-18] Agent: PreTester
- Test documentation created
- Test file created with 50+ test scenarios
- TDD pattern: tests will fail until TitleChecker is implemented
- Status: To Develop
