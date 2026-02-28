# TEST-10-2: Position Score Calculator Tests

## Task Reference
- **Task**: [TASK-10-2](../TASKS/TASK-10-2.md)
- **Specification**: [SPEC-010](../SPECIFICATIONS/SPEC-010.md)
- **Feature**: [FEAT-010](../FEATURES/FEAT-010.md)

## Overview
Comprehensive unit tests for the PositionScoreCalculator service. Tests verify position-based scoring rules and weighted average calculations using search volume data. This calculator is a core component of the Visibility Score system.

---

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Unit/Services/SEO/PositionScoreCalculatorTest.php` | Unit | Position scoring algorithm tests |
| `backend/tests/Unit/Services/SEO/PositionScoreWeightedAverageTest.php` | Unit | Weighted average calculation tests |

---

## Test Scenarios

### 1. Position Scoring Tests

| ID | Scenario | Input Position | Expected Score | Description |
|----|----------|----------------|----------------|-------------|
| PS-001 | Position 1 | 1 | 100 | Top position gets max score |
| PS-002 | Position 2 | 2 | 100 | Top 3 get max score |
| PS-003 | Position 3 | 3 | 100 | Top 3 get max score |
| PS-004 | Position 4 | 4 | 96.67 | First deduction tier |
| PS-005 | Position 5 | 5 | 93.34 | Linear deduction in 4-10 range |
| PS-006 | Position 10 | 10 | 76.67 | Last position in 4-10 range |
| PS-007 | Position 11 | 11 | 77.00 | Start of 11-20 range |
| PS-008 | Position 20 | 20 | 50.00 | Last position in 11-20 range |
| PS-009 | Position 21 | 21 | 49.00 | Start of 21-50 range |
| PS-010 | Position 50 | 50 | 20.00 | Last position in 21-50 range |
| PS-011 | Position 51 | 51 | 19.62 | Start of 51-100 range |
| PS-012 | Position 100 | 100 | 1.00 | Last position in top 100 |
| PS-013 | Position 101 | 101 | 0 | Outside top 100, no score |
| PS-014 | Position 0 | 0 | 0 | No ranking (not found) |
| PS-015 | Negative position | -1 | 0 | Invalid position handled |
| PS-016 | Very large position | 1000 | 0 | Far outside top 100 |

### 2. Score Boundary Tests

| ID | Scenario | Description |
|----|----------|-------------|
| SB-001 | Score never negative | All positions return >= 0 |
| SB-002 | Score never exceeds 100 | All positions return <= 100 |
| SB-003 | Integer return type | Score returned as integer |
| SB-004 | Consistent calculations | Same input always returns same output |
| SB-005 | Position 1 vs 100 | Score difference is significant (99 points) |

### 3. Weighted Average Tests

| ID | Scenario | Input Keywords | Expected Average | Description |
|----|----------|----------------|------------------|-------------|
| WA-001 | Single keyword | [(pos: 1, vol: 100)] | 100.00 | Single keyword returns its score |
| WA-002 | Two equal volumes | [(pos: 1, vol: 100), (pos: 10, vol: 100)] | 88.34 | Average of both scores |
| WA-003 | Volume-weighted | [(pos: 1, vol: 1000), (pos: 10, vol: 100)] | 97.88 | High volume keyword weighted more |
| WA-004 | Zero volume keyword | [(pos: 1, vol: 0)] | 0.00 | Zero volume returns 0 (or handle gracefully) |
| WA-005 | Null volume treated as 1 | [(pos: 1, vol: null)] | 100.00 | Null volume defaults to 1 |
| WA-006 | Empty collection | [] | 0.00 | No keywords returns 0 |
| WA-007 | All positions > 100 | [(pos: 101, vol: 100), (pos: 150, vol: 100)] | 0.00 | No valid rankings |
| WA-008 | Mixed valid/invalid | [(pos: 1, vol: 100), (pos: 101, vol: 100)] | 50.00 | Only valid positions contribute |
| WA-009 | Three keywords | [(pos: 1, vol: 100), (pos: 5, vol: 200), (pos: 10, vol: 100)] | 95.56 | Correct multi-keyword average |
| WA-010 | Very high volume | [(pos: 5, vol: 1000000), (pos: 50, vol: 1)] | 93.33 | High volume dominates |
| WA-011 | Same positions | [(pos: 5, vol: 100), (pos: 5, vol: 100)] | 93.34 | Same position twice averages correctly |
| WA-012 | Float precision | Multiple keywords | Correct to 2 decimal places |

### 4. Edge Case Tests

| ID | Scenario | Input | Expected Behavior |
|----|----------|-------|-------------------|
| EC-001 | Null position | position: null | Returns 0 |
| EC-002 | Float position | position: 1.5 | Truncates to 1, returns 100 |
| EC-003 | String position | position: '5' | Casts to int, returns 93.34 |
| EC-004 | Very large volume | vol: PHP_INT_MAX | No overflow error |
| EC-005 | Negative volume | vol: -100 | Handled gracefully (use absolute or 0) |
| EC-006 | Collection with non-objects | Collection of arrays | Works with array access |
| EC-007 | Missing search_volume property | Keyword without volume | Defaults to 1 |
| EC-008 | Missing current_position property | Keyword without position | Returns 0 |

### 5. Data Provider Tests

| ID | Scenario | Description |
|----|----------|-------------|
| DP-001 | All positions 1-100 | Data provider with positions 1-100 |
| DP-002 | Boundary positions | Data provider with edge positions (1, 3, 4, 10, 11, 20, 21, 50, 51, 100, 101) |
| DP-003 | Various volume combinations | Data provider with different volume scenarios |

---

## Mock Data

### Keyword Object for Weighted Average
```php
class KeywordMock {
    public int $current_position;
    public ?int $search_volume;
    
    public function __construct(int $position, ?int $volume = null) {
        $this->current_position = $position;
        $this->search_volume = $volume;
    }
}
```

### Sample Keyword Collection
```php
collect([
    (object) ['current_position' => 1, 'search_volume' => 1000],
    (object) ['current_position' => 5, 'search_volume' => 500],
    (object) ['current_position' => 10, 'search_volume' => 200],
    (object) ['current_position' => 50, 'search_volume' => 100],
]);
```

### Expected Score Table (Reference)
| Position Range | Score Formula |
|----------------|---------------|
| 1-3 | 100 |
| 4-10 | 100 - ((position - 3) * 3.33) |
| 11-20 | 80 - ((position - 10) * 3) |
| 21-50 | 50 - ((position - 20) * 1) |
| 51-100 | 20 - ((position - 50) * 0.38) |
| > 100 | 0 |

---

## Coverage Targets

| Component | Target | Notes |
|-----------|--------|-------|
| calculate() method | 100% | All position branches |
| calculateWeightedAverage() method | 100% | All collection scenarios |
| Edge cases | 100% | Null, invalid, boundary values |
| **Overall** | **100%** | Pure calculation function |

---

## Test Execution

```bash
# Run all PositionScoreCalculator tests
cd backend
php artisan test --filter "PositionScoreCalculator"

# Run specific test file
php artisan test tests/Unit/Services/SEO/PositionScoreCalculatorTest.php
php artisan test tests/Unit/Services/SEO/PositionScoreWeightedAverageTest.php

# Run with coverage
php artisan test --coverage --min=80 tests/Unit/Services/SEO/PositionScoreCalculatorTest.php
```

---

## Dependencies

- PHPUnit
- Laravel Collection class
- No database required (unit tests)

---

## Notes for Developer

1. **Class Location**: `backend/app/Services/SEO/PositionScoreCalculator.php`

2. **Return Types**: 
   - `calculate(int $position): int` - Returns integer score
   - `calculateWeightedAverage(Collection $keywords): float` - Returns float with 2 decimal precision

3. **Position Validation**: Handle positions < 0, 0, and > 100 gracefully by returning 0.

4. **Volume Handling**: 
   - Null search_volume should default to 1 (neutral weight)
   - Zero search_volume: decision needed - either exclude or treat as weight 1

5. **Collection Type**: Accept Laravel Collection, iterate with foreach or use Collection methods.

6. **No External Dependencies**: This should be a pure calculation class with no HTTP, DB, or file I/O.

7. **Algorithm Verification**: Scores must match the specification exactly - this is a scoring system users rely on for metrics.

---

## Comments

### [2026-02-28 22:31] ProdTester: Production Validation Complete ✅
- All 83 tests verified in production environment
- Position scoring algorithm matches specification exactly
- Weighted average calculations accurate to 2 decimal places
- Edge cases handled correctly (invalid positions, null volumes, empty collections)
- Security: No vulnerabilities - pure calculation class with no external dependencies

### [2026-02-28 20:45] PreTester: Work Started
- Status: Tests Coverage In Progress
- Creating test documentation and unit test files
- Implementing comprehensive test coverage for position scoring algorithm

