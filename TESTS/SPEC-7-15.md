# TESTS/SPEC-7-15.md — Tests for Report Enhancement for SERP Features

## Task Reference
- Task: [TASK-7-15](../TASKS/TASK-7-15.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: Low

---

## Overview

Tests for enhancing the reporting system (FEAT-004) to include SERP feature data in PDF and CSV exports.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/unit/services/report-serp-features.test.js` | Report enhancement tests |
| `tests/integration/report-export-serp.test.js` | Export integration tests |

---

## Test Scenarios

### 1. PDF Report Generation

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PDF-001 | Feature section included | Template with features enabled | Section rendered |
| PDF-002 | Feature section excluded | Template with features disabled | Section not rendered |
| PDF-003 | Feature section optional | No template setting | Default behavior |
| PDF-004 | Total features shown | Site with features | Total count displayed |
| PDF-005 | Breakdown by type | Multiple feature types | Each type with count |
| PDF-006 | Recent gains shown | Recent feature gains | Gains listed |
| PDF-007 | Recent losses shown | Recent feature losses | Losses listed |
| PDF-008 | No features data | Site without features | Empty or placeholder |

### 2. PDF Feature Icons

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PI-001 | Icons rendered correctly | Featured snippet | 🏆 icon displayed |
| PI-002 | Icons for all types | All feature types | Correct icon per type |
| PI-003 | Icons in keyword table | Keywords with features | Icons in table cells |
| PI-004 | Multiple icons per keyword | Keyword with 3 features | 3 icons displayed |

### 3. CSV Export

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CSV-001 | serp_features column added | Export with features | Column present |
| CSV-002 | Features as icons | Keyword with snippet | 🏆 in column |
| CSV-003 | Multiple features in cell | Keyword with 3 features | Multiple icons |
| CSV-004 | No features | Keyword without features | Empty cell |
| CSV-005 | CSV headers correct | Export with features | serp_features in headers |

### 4. Report Template Integration

| ID | Scenario | Template Setting | Expected Behavior |
|----|----------|------------------|-------------------|
| RT-001 | Features enabled | showSerpFeatures: true | Section included |
| RT-002 | Features disabled | showSerpFeatures: false | Section excluded |
| RT-003 | Custom date range | featuresDateRange: 30 | Last 30 days shown |

### 5. Data Integration

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| DI-001 | Feature data fetched | From summary endpoint |
| DI-002 | Data merged with report | Combined with other sections |
| DI-003 | Empty data handled | No crash on empty features |

---

## Mock Data

### Report SERP Features Section
```javascript
{
  serpFeatures: {
    totalKeywords: 100,
    keywordsWithFeatures: 35,
    featureCounts: {
      featured_snippet: 8,
      local_pack: 5,
      sitelinks: 12,
      reviews: 10
    },
    recentChanges: {
      gained: [
        { keyword: 'seo tools', feature: 'featured_snippet', detectedAt: '2026-02-25' }
      ],
      lost: [
        { keyword: 'analytics', feature: 'local_pack', detectedAt: '2026-02-24' }
      ]
    }
  }
}
```

### CSV Export Sample
```csv
keyword,position,serp_features
"seo tools",1,"🏆🔗⭐"
"analytics",2,"🔗"
"reporting",3,""
```

---

## Coverage Targets

| Component | Target |
|-----------|--------|
| PDF section rendering | 100% |
| Icon rendering | 100% |
| CSV export | 100% |
| Template integration | 100% |

---

## Test Execution

```bash
npm test -- tests/unit/services/report-serp-features.test.js
npm test -- tests/integration/report-export-serp.test.js
```

---

## Dependencies

- TASK-7-11 (Summary endpoint)
- FEAT-004 (Report system)

---

## Comments

### [2026-02-26 19:00] Agent: PreTester
Created test documentation for TASK-7-15 Report Enhancement.

Test coverage: 20 scenarios covering PDF generation, icons, CSV export, and template integration.

Ready for Developer implementation.
