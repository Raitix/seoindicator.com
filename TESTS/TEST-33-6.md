# TEST-33-6.md — BadgeService Tests

## Task Reference
- **Task:** TASK-33-6 - Implement BadgeService for SVG Generation
- **Spec:** SPEC-33 (Public SEO Audit Reports & Embeddable Score Widget)
- **Feature:** FEAT-033 (Public SEO Audit Reports & Embeddable Score Widget)

## Overview
Unit tests for BadgeService that generates SVG badges in three sizes (small, medium, large) with score visualization, color coding, and branding. Tests cover service instantiation, SVG generation, size handling, theme support, score color coding, branding, category breakdown, accessibility, caching, input validation, and security.

## Test Files

| Test File | Tests | Status |
|-----------|-------|--------|
| `backend/tests/Unit/Services/BadgeServiceTest.php` | 120+ | ⏳ TDD (service not implemented) |

## Test Categories

### SVC: Service Instantiation (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| SVC-001 | Service is instantiable | N/A | Instance of BadgeService |
| SVC-002 | Service has generate method | N/A | method_exists returns true |
| SVC-003 | Service is registered in container | N/A | app() returns instance |

### SVG: Basic SVG Generation (6 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| SVG-001 | generate returns string | score=75, size=medium | String result |
| SVG-002 | generate returns valid SVG | score=75, size=medium | Contains `<svg` and `</svg>` |
| SVG-003 | SVG includes xmlns attribute | score=75, size=medium | `xmlns="http://www.w3.org/2000/svg"` |
| SVG-004 | SVG includes viewBox | score=75, size=medium | Contains `viewBox=` |
| SVG-005 | Default theme works | score=75, size=medium (no theme) | Valid SVG |
| SVG-006 | Theme null works | score=75, size=medium, theme=null | Valid SVG |

### SIZE-SML: Small Badge Size (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| SIZE-SML-001 | Small badge width | size=small | width="120" |
| SIZE-SML-002 | Small badge height | size=small | height="40" |
| SIZE-SML-003 | Small badge viewBox | size=small | viewBox="0 0 120 40" |

### SIZE-MED: Medium Badge Size (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| SIZE-MED-001 | Medium badge width | size=medium | width="150" |
| SIZE-MED-002 | Medium badge height | size=medium | height="100" |
| SIZE-MED-003 | Medium badge viewBox | size=medium | viewBox="0 0 150 100" |

### SIZE-LRG: Large Badge Size (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| SIZE-LRG-001 | Large badge width | size=large | width="300" |
| SIZE-LRG-002 | Large badge height | size=large | height="150" |
| SIZE-LRG-003 | Large badge viewBox | size=large | viewBox="0 0 300 150" |

### THM-LGT: Light Theme (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| THM-LGT-001 | Light theme background | theme=light | Contains #ffffff |
| THM-LGT-002 | Light theme text | theme=light | Contains #000000 |
| THM-LGT-003 | Light theme explicit | theme=light | Valid SVG |

### THM-DRK: Dark Theme (4 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| THM-DRK-001 | Dark theme background | theme=dark | Contains #1a1a1a |
| THM-DRK-002 | Dark theme text | theme=dark | Contains #ffffff |
| THM-DRK-003 | Dark theme explicit | theme=dark | Valid SVG |
| THM-DRK-004 | Theme defaults to light | No theme param | Same as light theme |

### CLR-GRN: Green Score Range - 90-100 (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| CLR-GRN-001 | Score 90 uses green | score=90 | Contains #22c55e |
| CLR-GRN-002 | Score 100 uses green | score=100 | Contains #22c55e |
| CLR-GRN-003 | Score 95 uses green | score=95 | Contains #22c55e |

### CLR-BLU: Blue Score Range - 70-89 (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| CLR-BLU-001 | Score 70 uses blue | score=70 | Contains #3b82f6 |
| CLR-BLU-002 | Score 89 uses blue | score=89 | Contains #3b82f6 |
| CLR-BLU-003 | Score 75 uses blue | score=75 | Contains #3b82f6 |

### CLR-YLW: Yellow Score Range - 50-69 (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| CLR-YLW-001 | Score 50 uses yellow | score=50 | Contains #eab308 |
| CLR-YLW-002 | Score 69 uses yellow | score=69 | Contains #eab308 |
| CLR-YLW-003 | Score 60 uses yellow | score=60 | Contains #eab308 |

### CLR-ORG: Orange Score Range - 30-49 (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| CLR-ORG-001 | Score 30 uses orange | score=30 | Contains #f97316 |
| CLR-ORG-002 | Score 49 uses orange | score=49 | Contains #f97316 |
| CLR-ORG-003 | Score 40 uses orange | score=40 | Contains #f97316 |

### CLR-RED: Red Score Range - 0-29 (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| CLR-RED-001 | Score 0 uses red | score=0 | Contains #ef4444 |
| CLR-RED-002 | Score 29 uses red | score=29 | Contains #ef4444 |
| CLR-RED-003 | Score 15 uses red | score=15 | Contains #ef4444 |

### SCR: Score Display (5 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| SCR-001 | Score displayed in SVG | score=75 | Contains "75" |
| SCR-002 | Score 0 displayed | score=0 | Contains "0" |
| SCR-003 | Score 100 displayed | score=100 | Contains "100" |
| SCR-004 | Decimal score rounded up | score=75.6 | Contains "76" |
| SCR-005 | Decimal score rounded down | score=75.4 | Contains "75" |

### BRD: Branding (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| BRD-001 | Small badge has branding | size=small | "Powered by SEOIndicator" |
| BRD-002 | Medium badge has branding | size=medium | "Powered by SEOIndicator" |
| BRD-003 | Large badge has branding | size=large | "Powered by SEOIndicator" |

### CAT: Category Breakdown (4 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| CAT-001 | Large badge accepts categories | categories array | Valid SVG |
| CAT-002 | Large badge displays categories | categories with scores | All scores in SVG |
| CAT-003 | Small badge ignores categories | size=small + categories | Valid SVG |
| CAT-004 | Medium badge ignores categories | size=medium + categories | Valid SVG |

### A11Y: Accessibility (6 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| A11Y-001 | SVG has role="img" | any valid | Contains role="img" |
| A11Y-002 | SVG has aria-label | any valid | Contains aria-label= |
| A11Y-003 | aria-label contains score | score=75 | aria-label contains "75" |
| A11Y-004 | aria-label has SEO Score text | any valid | Contains "SEO Score" |
| A11Y-005 | Small badge accessible | size=small | Has role and aria-label |
| A11Y-006 | Large badge accessible | size=large | Has role and aria-label |

### CACHE: Caching (5 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| CACHE-001 | Generate uses cache | same params twice | Same result |
| CACHE-002 | Different scores differ | score=75 vs 85 | Different SVGs |
| CACHE-003 | Different sizes differ | size=small vs medium | Different SVGs |
| CACHE-004 | Different themes differ | theme=light vs dark | Different SVGs |
| CACHE-005 | Cache TTL is 1 hour | any valid | TTL = 3600 seconds |

### VAL: Input Validation (5 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| VAL-001 | Invalid size throws | size="extra-large" | InvalidArgumentException |
| VAL-002 | Uppercase size throws | size="SMALL" | InvalidArgumentException |
| VAL-003 | Invalid theme defaults | theme="invalid" | Uses light theme |
| VAL-004 | Negative score clamped | score=-10 | Score 0, red color |
| VAL-005 | Score above 100 clamped | score=150 | Score 100, green color |

### SVG-STR: SVG Structure (5 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| SVG-STR-001 | SVG is valid XML | any valid | DOMDocument parses it |
| SVG-STR-002 | No script tags | any valid | No `<script>` |
| SVG-STR-003 | No javascript: protocol | any valid | No `javascript:` |
| SVG-STR-004 | No onerror handlers | any valid | No `onerror` |
| SVG-STR-005 | No onclick handlers | any valid | No `onclick` |

### EDGE: Edge Cases (10 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| EDGE-001 | Empty theme uses default | theme="" | Uses light theme |
| EDGE-002 | Null theme uses default | theme=null | Uses light theme |
| EDGE-003 | Score boundary 89 | score=89 | Blue color |
| EDGE-004 | Score boundary 90 | score=90 | Green color |
| EDGE-005 | Score boundary 69 | score=69 | Yellow color |
| EDGE-006 | Score boundary 70 | score=70 | Blue color |
| EDGE-007 | Score boundary 49 | score=49 | Orange color |
| EDGE-008 | Score boundary 50 | score=50 | Yellow color |
| EDGE-009 | Score boundary 29 | score=29 | Red color |
| EDGE-010 | Score boundary 30 | score=30 | Orange color |

### PERF: Performance (2 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| PERF-001 | Generation is fast | 100 generations | < 1 second |
| PERF-002 | Cached generation faster | cold vs cached | Cached not slower |

### DATA: Data Provider Tests (30 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| DATA-SIZE-001 | Small size via provider | size=small | width=120, height=40 |
| DATA-SIZE-002 | Medium size via provider | size=medium | width=150, height=100 |
| DATA-SIZE-003 | Large size via provider | size=large | width=300, height=150 |
| DATA-CLR-001-015 | All score colors via provider | scores 0-100 | Correct colors |
| DATA-THM-001-002 | All themes via provider | light/dark | Correct bg/text colors |

### SEC: Security (3 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| SEC-001 | No external resources | any valid | No http:// or https:// |
| SEC-002 | No data URIs | any valid | No `data:` |
| SEC-003 | Special chars handled | score=75 | No XSS vectors |

### INT: Integration (2 tests)
| Test ID | Scenario | Input | Expected Output |
|---------|----------|-------|-----------------|
| INT-001 | Full generation workflow | score=85, dark, medium | Complete valid SVG |
| INT-002 | All sizes all themes | combinations | All valid with A11Y |

### AC: Acceptance Criteria (9 tests)
| Test ID | Criteria | Status |
|---------|----------|--------|
| AC-01 | `generate(int $score, string $size, ?string $theme = null)` method exists | ⏳ |
| AC-02 | Three sizes supported: small, medium, large | ⏳ |
| AC-03 | Two themes supported: light, dark | ⏳ |
| AC-04 | Score color-coded by range (5 ranges) | ⏳ |
| AC-05 | All badges include "Powered by SEOIndicator" text | ⏳ |
| AC-06 | Large badge includes category breakdown | ⏳ |
| AC-07 | SVG is valid, accessible (aria-label, role) | ⏳ |
| AC-08 | Cache generated SVGs for performance | ⏳ |
| AC-09 | Unit tests for all badge variations | ⏳ |

## Mock Data

### Score Color Mapping
```php
private const SCORE_COLORS = [
    'green' => '#22c55e',   // 90-100
    'blue' => '#3b82f6',    // 70-89
    'yellow' => '#eab308',  // 50-69
    'orange' => '#f97316',  // 30-49
    'red' => '#ef4444',     // 0-29
];
```

### Badge Dimensions
```php
private const DIMENSIONS = [
    'small' => ['width' => 120, 'height' => 40],
    'medium' => ['width' => 150, 'height' => 100],
    'large' => ['width' => 300, 'height' => 150],
];
```

### Theme Colors
```php
private const THEME_COLORS = [
    'light' => ['background' => '#ffffff', 'text' => '#000000'],
    'dark' => ['background' => '#1a1a1a', 'text' => '#ffffff'],
];
```

### Category Scores Example
```php
$categoryScores = [
    'technical' => 85,
    'content' => 70,
    'onpage' => 90,
    'mobile' => 65,
];
```

## Expected Service Interface

```php
namespace App\Services;

class BadgeService
{
    /**
     * Generate an SVG badge.
     *
     * @param int $score Overall SEO score (0-100)
     * @param string $size Badge size: 'small', 'medium', 'large'
     * @param string|null $theme Theme: 'light' or 'dark', defaults to 'light'
     * @param array $categoryScores Category breakdown (only used for large badges)
     * @return string SVG markup
     * @throws \InvalidArgumentException for invalid size
     */
    public function generate(
        int $score,
        string $size,
        ?string $theme = null,
        array $categoryScores = []
    ): string;
}
```

## Cache Key Format
```
badge:{score}:{size}:{theme}
```

Example: `badge:75:medium:light`

## Coverage Targets

| Category | Target | Actual |
|----------|--------|--------|
| Statements | ≥ 80% | ⏳ |
| Branches | ≥ 80% | ⏳ |
| Functions | ≥ 80% | ⏳ |
| Lines | ≥ 80% | ⏳ |

## Test Execution

```bash
# Run BadgeService tests
cd backend
php artisan test --filter=BadgeServiceTest

# Run specific test group
php artisan test --filter=BadgeServiceTest --group=badge-color

# Run with coverage
php artisan test --filter=BadgeServiceTest --coverage

# Run all tests in file
php artisan test tests/Unit/Services/BadgeServiceTest.php
```

## Dependencies

| Dependency | Task | Status |
|------------|------|--------|
| Laravel Cache facade | Core | ✅ Available |
| Blade templates | Core | ✅ Available |
| TASK-33-1 (visibility columns) | Done | ✅ |

## Files to Create by Developer

1. `backend/app/Services/BadgeService.php` - Main service class
2. `backend/resources/views/badges/small.svg.blade.php` - Small badge template
3. `backend/resources/views/badges/medium.svg.blade.php` - Medium badge template
4. `backend/resources/views/badges/large.svg.blade.php` - Large badge template

## Notes for Developer

1. **Score Clamping:** Negative scores → 0, scores > 100 → 100
2. **Size Validation:** Only 'small', 'medium', 'large' allowed (case-sensitive)
3. **Theme Fallback:** Invalid or missing theme → 'light'
4. **Cache TTL:** 3600 seconds (1 hour)
5. **Accessibility:** Always include `role="img"` and `aria-label="SEO Score: X"`
6. **Security:** No script tags, no event handlers, no external resources
7. **Branding:** All badges must include "Powered by SEOIndicator"
8. **Large Badge:** Include category breakdown (4 categories: technical, content, onpage, mobile)

## Comments

### PreTester Agent Log
- **2026-03-06**: Created test file with 120+ comprehensive tests
- **2026-03-06**: Tests cover all acceptance criteria
- **2026-03-06**: TDD pattern - tests expect service to be implemented

---

Last updated: 2026-03-06
