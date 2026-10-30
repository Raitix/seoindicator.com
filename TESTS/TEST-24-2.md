# TEST-24-2: PWAAnalysis Model and Repository Tests

## Overview
Unit tests for PWAAnalysis model and PWAAnalysisRepository. Tests cover model relationships, attribute casting, scopes, and repository CRUD operations.

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `backend/tests/Unit/Models/PWAAnalysisTest.php` | Unit | 25+ | Model relationships, casts, scopes |
| `backend/tests/Unit/Repositories/PWAAnalysisRepositoryTest.php` | Unit | 20+ | Repository CRUD methods |

---

## Model Test Cases

### Model Creation (MC)

| ID | Test | Description |
|----|------|-------------|
| MC-001 | Create with all fields | Verify model can be created with all attributes |
| MC-002 | Create with minimal fields | Verify required fields only (site_id) |
| MC-003 | Default values | Verify defaults: has_manifest=false, pwa_score=0, readiness_level='none' |
| MC-004 | JSON casting manifest_json | Verify manifest_json is cast to array |
| MC-005 | JSON casting icon_errors | Verify icon_errors is cast to array |
| MC-006 | Boolean casting has_* fields | Verify boolean fields are cast correctly |
| MC-007 | Boolean casting icons_*_present | Verify icon presence flags are boolean |
| MC-008 | Score range 0-100 | Verify pwa_score accepts valid range |
| MC-009 | Enum readiness_level | Verify enum values: none, basic, enhanced, full |

### Relationships (RL)

| ID | Test | Description |
|----|------|-------------|
| RL-001 | Belongs to Site | Verify site() relationship |
| RL-002 | Site has many PWA analyses | Verify inverse relationship |
| RL-003 | Cascade delete | Verify deletion when site is deleted |

### Scopes (SC)

| ID | Test | Description |
|----|------|-------------|
| SC-001 | Scope forSite | Filter by site_id |
| SC-002 | Scope withManifest | Filter has_manifest=true |
| SC-003 | Scope withoutManifest | Filter has_manifest=false |
| SC-004 | Scope byReadinessLevel | Filter by readiness_level |
| SC-005 | Scope minScore | Filter by minimum pwa_score |

### Accessors (AC)

| ID | Test | Description |
|----|------|-------------|
| AC-001 | Get manifest fields attribute | Returns structured manifest fields |
| AC-002 | Get icon summary attribute | Returns icon validation summary |

### Edge Cases (EC)

| ID | Test | Description |
|----|------|-------------|
| EC-001 | Null manifest_json | Handle null JSON field |
| EC-002 | Empty manifest_json | Handle empty object {} |
| EC-003 | Large manifest_json | Handle large JSON (10KB+) |
| EC-004 | Null icon_errors | Handle null error field |
| EC-005 | Zero icons_total | Handle no icons case |

### Security (SC-SEC)

| ID | Test | Description |
|----|------|-------------|
| SEC-001 | SQL injection in JSON | Verify JSON field is parameterized |
| SEC-002 | XSS in manifest values | Verify values are not executed |

---

## Repository Test Cases

### CRUD Operations (CRUD)

| ID | Test | Description |
|----|------|-------------|
| CRUD-001 | Create analysis | Verify create() returns model |
| CRUD-002 | Find by ID | Verify find() returns model or null |
| CRUD-003 | Update analysis | Verify update() modifies fields |
| CRUD-004 | Delete analysis | Verify delete() removes record |
| CRUD-005 | Find by site ID | Verify findBySiteId() |
| CRUD-006 | Get latest for site | Verify getLatestForSite() |
| CRUD-007 | Create with JSON fields | Verify JSON fields stored correctly |
| CRUD-008 | Update JSON fields | Verify JSON fields update correctly |

### Query Methods (QM)

| ID | Test | Description |
|----|------|-------------|
| QM-001 | Find by site ID returns null if not found | Verify null return |
| QM-002 | Get latest for site ordered by analyzed_at | Verify ordering |
| QM-003 | Get latest for site returns null if none | Verify empty result |
| QM-004 | Multiple analyses same site | Verify latest returned |

### Edge Cases (EC-REP)

| ID | Test | Description |
|----|------|-------------|
| EC-REP-001 | Delete non-existent returns false | Verify graceful failure |
| EC-REP-002 | Update non-existent returns false | Verify graceful failure |
| EC-REP-003 | Find with invalid ID returns null | Verify non-numeric ID |

### Performance (PF)

| ID | Test | Description |
|----|------|-------------|
| PF-001 | Large manifest storage | Verify <100ms for 10KB JSON |
| PF-002 | Bulk operations | Verify efficient batch queries |

---

## Test Implementation Notes

### Model Factory States

```php
// PWAAnalysisFactory states
->withManifest()      // has_manifest=true, manifest_valid=true
->withoutManifest()   // has_manifest=false
->fullPWA()           // readiness_level='full', pwa_score=95
->basicPWA()          // readiness_level='basic', pwa_score=50
->noIcons()           // icons_total=0
->withIconErrors()    // icon_errors with sample errors
```

### Test Data

**Sample Manifest JSON:**
```json
{
  "name": "SEO Indicator",
  "short_name": "SEOIndicator",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#2563eb",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

**Sample Icon Errors:**
```json
[
  { "size": "180x180", "error": "Icon not found", "severity": "warning" }
]
```

---

## Status
- Created: 2026-03-01
- Agent: PreTester
- Task: TASK-24-2
