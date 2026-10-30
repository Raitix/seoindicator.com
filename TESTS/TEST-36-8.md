# TEST-36-8: ContentDecayExportService Tests

## Task Reference
- **Task:** TASK-36-8: ContentDecayExportService
- **Specification:** SPEC-036: Content Decay Detection & Refresh Planner
- **Status:** To Develop

---

## Overview

Comprehensive unit test suite for `ContentDecayExportService` that handles export and AI brief generation for Content Decay data.

---

## Test Files

| File | Location | Tests |
|------|----------|-------|
| ContentDecayExportServiceTest.php | `backend/tests/Unit/Services/ContentDecay/` | 72 |

---

## Test Scenarios

### Service Instantiation (3 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-001 | Service can be instantiated | Instance of ContentDecayExportService |
| EXP-002 | Service implements interface | Instance of ContentDecayExportServiceInterface |
| EXP-003 | Service has required methods | exportQueue, generateAiBrief, getExportFormats, isAiBriefAvailable, getAiBrief, getExportFilename |

### Export Formats (5 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-010 | getExportFormats returns array | Array with 3 items |
| EXP-011 | getExportFormats contains csv | 'csv' in array |
| EXP-012 | getExportFormats contains pdf | 'pdf' in array |
| EXP-013 | getExportFormats contains xls | 'xls' in array |
| EXP-014 | getExportFormats returns exactly three formats | ['csv', 'pdf', 'xls'] |

### AI Brief Availability (4 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-020 | isAiBriefAvailable returns bool | Boolean value |
| EXP-021 | isAiBriefAvailable true when OpenAI key set | true |
| EXP-022 | isAiBriefAvailable true when Anthropic key set | true |
| EXP-023 | isAiBriefAvailable false when no keys set | false |

### Export Filename (7 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-030 | getExportFilename generates correct format | Contains 'content_decay_queue', 'site_ID', extension |
| EXP-031 | getExportFilename includes date | Date format Y-m-d_His |
| EXP-032 | getExportFilename uses correct extension for csv | Ends with '.csv' |
| EXP-033 | getExportFilename uses correct extension for pdf | Ends with '.pdf' |
| EXP-034 | getExportFilename uses correct extension for xls | Ends with '.xls' |
| EXP-035 | getExportFilename uses recovery_report prefix | Contains 'recovery_report' when flag set |
| EXP-036 | getExportFilename with different site IDs | Contains correct site ID |

### Format Validation (5 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-040 | exportQueue throws for invalid format | InvalidArgumentException |
| EXP-041 | exportQueue throws for missing project | InvalidArgumentException |
| EXP-042 | exportQueue accepts csv format | File path with .csv extension |
| EXP-043 | exportQueue accepts pdf format | File path with .pdf extension |
| EXP-044 | exportQueue accepts xls format | File path with .xls extension |

### CSV Export (8 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-050 | CSV export creates file | File exists at returned path |
| EXP-051 | CSV export has correct headers | URL, Title, Page Type, Severity, Decay Score, Lost Clicks, Recovery Upside, Status, Primary Reason, First Detected, Last Detected |
| EXP-052 | CSV export includes candidate data | Contains page_url, page_title, severity, status |
| EXP-053 | CSV export with multiple candidates | Header + N data rows |
| EXP-054 | CSV export empty queue has headers only | Single row with headers |
| EXP-055 | CSV export contains decay score | Decay score value in content |
| EXP-056 | CSV export contains lost clicks estimate | Lost clicks value in content |
| EXP-057 | CSV export contains recovery upside estimate | Recovery upside value in content |

### PDF Export (3 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-060 | PDF export creates file | File exists at returned path |
| EXP-061 | PDF export contains candidate count | Candidate count in content |
| EXP-062 | PDF export with recovery report flag | File created successfully |

### XLS Export (2 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-070 | XLS export creates file | File exists at returned path |
| EXP-071 | XLS export contains candidate count | Candidate count in content |

### Filtering (6 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-080 | Filter by status | Only matching status in export |
| EXP-081 | Filter by severity | Only matching severity in export |
| EXP-082 | Filter by date_from | Only candidates after date in export |
| EXP-083 | Filter by date_to | Only candidates before date in export |
| EXP-084 | Filter with multiple criteria | Only candidates matching all filters |
| EXP-085 | Filter with date range | Only candidates within range |

### AI Brief Generation (5 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-090 | generateAiBrief returns array | Array with job_id and status |
| EXP-091 | generateAiBrief returns job_id | Non-empty job_id string |
| EXP-092 | generateAiBrief returns status queued | 'queued' status |
| EXP-093 | generateAiBrief job_id contains candidate id | Candidate ID in job_id |
| EXP-094 | generateAiBrief throws for non-existent candidate | InvalidArgumentException |

### Get AI Brief (3 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-100 | getAiBrief returns null for non-existent | null |
| EXP-101 | getAiBrief returns null when no brief | null |
| EXP-102 | getAiBrief returns brief when exists | Brief array with summary |

### Security (5 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-110 | XSS in page_url is preserved in CSV | Script tag present (not executed) |
| EXP-111 | SQL injection in filter is handled safely | No exception, file created |
| EXP-112 | Special characters in title handled | File created successfully |
| EXP-113 | Unicode characters handled | Unicode preserved in output |
| EXP-114 | Emoji in content handled | Emoji preserved in output |

### Edge Cases (7 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-120 | Export with null values handled | File created successfully |
| EXP-121 | Export with very long URL | File created successfully |
| EXP-122 | Export with zero decay score | Score '0' in output |
| EXP-123 | Export with max decay score | Score '100' in output |
| EXP-124 | Export with all reason codes | Reason codes in output |
| EXP-125 | Export with all statuses | All statuses in output |
| EXP-126 | Export with all severities | All severities in output |

### Integration (3 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-130 | Full export workflow | File exists with content > 100 bytes |
| EXP-131 | Multiple exports for same site | All files created successfully |
| EXP-132 | Container binding resolves service | Service instance from container |

### Acceptance Criteria (6 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| EXP-AC01 | ContentDecayExportService class created | Service instance |
| EXP-AC02 | exportQueue method exists with correct signature | Method with siteId, format, filters params |
| EXP-AC03 | CSV export includes all required columns | All required columns in headers |
| EXP-AC04 | Support filtering by status | Filter works without exception |
| EXP-AC05 | Support filtering by severity | Filter works without exception |
| EXP-AC06 | Support filtering by date range | Filter works without exception |

---

## Mock Data Patterns

### ContentDecayProject Factory
```php
ContentDecayProject::factory()->create([
    'site_id' => 1,
    'is_detection_enabled' => true,
]);
```

### ContentDecayCandidate Factory
```php
ContentDecayCandidate::factory()->create([
    'project_id' => $project->id,
    'page_url' => 'https://example.com/test-page',
    'page_title' => 'Test Page Title',
    'severity' => 'high',
    'decay_score' => 75.50,
    'status' => 'backlog',
    'lost_click_estimate' => 450,
    'recovery_upside_estimate' => 1200,
]);
```

---

## Coverage Targets

| Category | Target | Actual |
|----------|--------|--------|
| Service Instantiation | 100% | ✅ 100% |
| Export Formats | 100% | ✅ 100% |
| CSV Generation | 100% | ✅ 100% |
| PDF Generation | 100% | ✅ 100% (placeholder) |
| Filtering | 100% | ✅ 100% |
| AI Brief | 100% | ✅ 100% |
| Security | 100% | ✅ 100% |
| Edge Cases | 100% | ✅ 100% |

---

## Test Execution

```bash
cd backend
php artisan test tests/Unit/Services/ContentDecay/ContentDecayExportServiceTest.php
```

**Results:**
- Tests: 72 passed
- Assertions: 138
- Duration: ~5.5s

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| ContentDecayProject model | ✅ | Factory exists |
| ContentDecayCandidate model | ✅ | Factory exists |
| TASK-36-2 (Eloquent Models) | To Deploy | Models implemented with factories |

---

## Notes for Developer

### Implementation Status
1. **Service Class** - Exists with basic implementation
2. **CSV Export** - Functional with most columns
3. **PDF Export** - Placeholder only (needs real PDF library)
4. **XLS Export** - Placeholder only (needs PhpSpreadsheet)

### Missing Features
1. `exportRecoveryReport()` dedicated method - currently uses `exportQueue` with filter
2. `exportCandidateDetail()` method - not implemented
3. Additional CSV columns: Confidence, All Reason Codes, Planned/Started/Refreshed/Recovered dates
4. Real PDF generation with charts and formatting
5. Real XLS generation with formatting

### Suggested Enhancements
1. Integrate DomPDF or Snappy for real PDF generation
2. Integrate PhpSpreadsheet or Laravel Excel for XLS
3. Add reason code breakdown chart in PDF
4. Add recovery timeline visualization

---

*Created by PreTester agent on 2026-03-12*
