# TEST-24-6: PWAScorerService Tests

## Overview
Unit tests for PWAScorerService. Tests cover score calculation algorithm, readiness level determination, and score breakdown generation based on PWAAnalysis model data.

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `backend/tests/Unit/Services/PWAScorerServiceTest.php` | Unit | 45+ | Score calculation, readiness levels, breakdown |

---

## Scoring Algorithm Reference

### Points Distribution (Total: 85)

| Category | Max Points | Breakdown |
|----------|------------|-----------|
| Manifest Presence | 20 | 10 for presence, 10 for validity |
| Required Fields | 15 | name, short_name, start_url, display (3.75 each) |
| Recommended Fields | 15 | background_color, theme_color, description (5 each) |
| Required Icons | 25 | 192x192: 10, 512x512: 10, 180x180: 5 |
| Icon Validity Bonus | 10 | Proportional to icons_valid/icons_total |

**Note:** The specification originally listed total as 100, but the algorithm mathematically totals 85. Tests have been updated to reflect the correct values.

### Readiness Levels

| Level | Score Range | Description |
|-------|-------------|-------------|
| `none` | 0-39 | No manifest or critically incomplete |
| `basic` | 40-69 | Manifest present with basic fields |
| `enhanced` | 70-89 | Most fields and icons present |
| `full` | 90-100 | Complete PWA with all recommended elements |

---

## Test Cases

### Category 1: calculateScore() Method Tests (CS-001 to CS-020)

| ID | Test | Description | Expected Score |
|----|------|-------------|----------------|
| CS-001 | No manifest | has_manifest=false | 0 |
| CS-002 | Manifest present, invalid | has_manifest=true, manifest_valid=false | 10 |
| CS-003 | Manifest present, valid | has_manifest=true, manifest_valid=true | 20 |
| CS-004 | All required fields | name, short_name, start_url, display present | 15 |
| CS-005 | 3 of 4 required fields | Missing one required field | 11.25 ≈ 11 |
| CS-006 | 2 of 4 required fields | Missing two required fields | 7.5 ≈ 8 |
| CS-007 | 1 of 4 required fields | Only one required field present | 3.75 ≈ 4 |
| CS-008 | No required fields | All required fields missing | 0 |
| CS-009 | All recommended fields | background_color, theme_color, description present | 15 |
| CS-010 | 2 of 3 recommended fields | Missing one recommended field | 10 |
| CS-011 | 1 of 3 recommended fields | Only one recommended field present | 5 |
| CS-012 | No recommended fields | All recommended fields missing | 0 |
| CS-013 | All required icons | 192x192, 512x512, 180x180 present | 25 |
| CS-014 | Only 192x192 icon | 192x192 present, others missing | 10 |
| CS-015 | Only 512x512 icon | 512x512 present, others missing | 10 |
| CS-016 | Only 180x180 icon | 180x180 present, others missing | 5 |
| CS-017 | 192x192 + 512x512 icons | Both Android icons present | 20 |
| CS-018 | No icons | icons_total=0 | 0 |
| CS-019 | Icon validity 100% | icons_valid=icons_total | 10 |
| CS-020 | Icon validity 50% | icons_valid=icons_total/2 | 5 |

### Category 2: Full Score Scenarios (FS-001 to FS-005)

| ID | Test | Description | Expected Score |
|----|------|-------------|----------------|
| FS-001 | Perfect PWA | All manifest, all fields, all icons, all valid | 85 |
| FS-002 | Near perfect (no 180 icon) | Missing iOS icon only | 80 |
| FS-003 | Near perfect (no description) | Missing description only | 80 |
| FS-004 | Near perfect (80% icon validity) | icons_valid=4, icons_total=5 | 83 |
| FS-005 | High score baseline | Valid manifest, required fields, 192+512 icons | 65 |

### Category 3: Score Boundary Tests (SB-001 to SB-008)

| ID | Test | Description | Expected Score |
|----|------|-------------|----------------|
| SB-001 | Score 0 boundary | No manifest, no fields, no icons | 0 |
| SB-002 | Score 39 (none upper) | Just below basic threshold | 39 |
| SB-003 | Score 40 (basic lower) | Just into basic threshold | 40 |
| SB-004 | Score 69 (basic upper) | Just below enhanced threshold | 69 |
| SB-005 | Score 70 (enhanced lower) | Just into enhanced threshold | 70 |
| SB-006 | Score 89 (enhanced upper) | Just below full threshold | 89 |
| SB-007 | Score 90 (full lower) | Just into full threshold | 90 |
| SB-008 | Score rounding | Decimal scores rounded correctly | Integer |

### Category 4: determineReadinessLevel() Method Tests (RL-001 to RL-012)

| ID | Test | Score Input | Expected Level |
|----|------|-------------|----------------|
| RL-001 | Level none (0) | 0 | none |
| RL-002 | Level none (20) | 20 | none |
| RL-003 | Level none (39) | 39 | none |
| RL-004 | Level basic (40) | 40 | basic |
| RL-005 | Level basic (50) | 50 | basic |
| RL-006 | Level basic (69) | 69 | basic |
| RL-007 | Level enhanced (70) | 70 | enhanced |
| RL-008 | Level enhanced (80) | 80 | enhanced |
| RL-009 | Level enhanced (89) | 89 | enhanced |
| RL-010 | Level full (90) | 90 | full |
| RL-011 | Level full (95) | 95 | full |
| RL-012 | Level full (100) | 100 | full |

### Category 5: getScoreBreakdown() Method Tests (BD-001 to BD-010)

| ID | Test | Description |
|----|------|-------------|
| BD-001 | Returns array structure | Returns array with all categories |
| BD-002 | Manifest breakdown | Shows presence and validity separately |
| BD-003 | Required fields breakdown | Shows each required field status |
| BD-004 | Recommended fields breakdown | Shows each recommended field status |
| BD-005 | Icons breakdown | Shows each icon size status |
| BD-006 | Icon validity breakdown | Shows ratio and points earned |
| BD-007 | Total score matches | Breakdown total equals calculateScore() |
| BD-008 | Empty analysis breakdown | Handles no manifest case |
| BD-009 | Partial manifest breakdown | Handles partial data |
| BD-010 | Full manifest breakdown | Shows all points earned |

### Category 6: Edge Cases (EC-001 to EC-010)

| ID | Test | Description |
|----|------|-------------|
| EC-001 | Null PWAAnalysis | Handle null analysis gracefully |
| EC-002 | Zero icons_total, positive icons_valid | Invalid ratio handling |
| EC-003 | Negative score cap | Score never goes below 0 |
| EC-004 | Over 100 score cap | Score never exceeds 100 |
| EC-005 | Very large icons_total | Handles large icon counts |
| EC-006 | Icons with errors | icon_errors present doesn't affect score |
| EC-007 | Inconsistent data | has_name=false but manifest_json has name |
| EC-008 | Score precision | Decimal handling in all categories |
| EC-009 | Missing PWAAnalysis fields | Fields with null/missing values |
| EC-010 | Rapid re-calculation | Same analysis produces same score |

### Category 7: Integration with PWAAnalysis Model (INT-001 to INT-005)

| ID | Test | Description |
|----|------|-------------|
| INT-001 | Model field access | Access has_name, has_short_name, etc. |
| INT-002 | Model icon fields | Access icons_192_present, etc. |
| INT-003 | Model icon validity ratio | Use getIconValidityRatio() method |
| INT-004 | Factory test data | Use PWAAnalysisFactory states |
| INT-005 | Database persistence | Score calculated matches stored pwa_score |

---

## Test Implementation Notes

### Service Methods to Implement

```php
class PWAScorerService
{
    /**
     * Calculate PWA readiness score (0-100).
     */
    public function calculateScore(PWAAnalysis $analysis): int;
    
    /**
     * Determine readiness level based on score.
     */
    public function determineReadinessLevel(int $score): string;
    
    /**
     * Get detailed breakdown of score calculation.
     * 
     * @return array{
     *   manifest: array{presence: int, validity: int, max: int},
     *   required_fields: array{fields: array<string, bool>, earned: int, max: int},
     *   recommended_fields: array{fields: array<string, bool>, earned: int, max: int},
     *   icons: array{sizes: array<string, bool>, earned: int, max: int},
     *   icon_validity: array{ratio: float, earned: int, max: int},
     *   total: int,
     *   max_total: int
     * }
     */
    public function getScoreBreakdown(PWAAnalysis $analysis): array;
}
```

### Expected Breakdown Structure

```php
[
    'manifest' => [
        'presence' => 10,    // or 0
        'validity' => 10,    // or 0
        'earned' => 20,      // total for this category
        'max' => 20
    ],
    'required_fields' => [
        'fields' => [
            'name' => true,
            'short_name' => true,
            'start_url' => true,
            'display' => true
        ],
        'earned' => 15,
        'max' => 15
    ],
    'recommended_fields' => [
        'fields' => [
            'background_color' => true,
            'theme_color' => true,
            'description' => false
        ],
        'earned' => 10,
        'max' => 15
    ],
    'icons' => [
        'sizes' => [
            '192x192' => true,
            '512x512' => true,
            '180x180' => false
        ],
        'earned' => 20,
        'max' => 25
    ],
    'icon_validity' => [
        'ratio' => 0.75,
        'earned' => 7,
        'max' => 10
    ],
    'total' => 72,
    'max_total' => 100,
    'readiness_level' => 'enhanced'
]
```

### Test Data from Factory

```php
// Use existing factory states:
PWAAnalysis::factory()->fullPWA()->make();      // Score 85 (max)
PWAAnalysis::factory()->basicPWA()->make();     // Score 55
PWAAnalysis::factory()->withManifest()->make(); // Score ~35
PWAAnalysis::factory()->withoutManifest()->make(); // Score 0
PWAAnalysis::factory()->noIcons()->make();      // Score without icons
```

---

## Status
- Created: 2026-03-01
- Agent: PreTester
- Task: TASK-24-6
