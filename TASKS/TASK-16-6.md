# TASK-16-6: Lead Storage Service

## STATUS`n`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Specification: [SPEC-016](../SPECIFICATIONS/SPEC-016.md)

---

## Description
Create the backend service for managing tool leads. This includes storing leads, preventing duplicates, exporting leads, and integrating with the existing user system.

---

## Acceptance Criteria
- [ ] Create ToolLead model and migration
- [ ] Implement LeadRepository for CRUD operations
- [ ] Add duplicate detection (same email within 24h)
- [ ] Create admin API for viewing leads
- [ ] Create CSV export endpoint for leads
- [ ] Add lead statistics (total, by tool, by date)
- [ ] Implement soft delete for GDPR compliance
- [ ] Add unit tests for lead service

---

## Technical Details

### Model
```php
class ToolLead extends Model
{
    protected $fillable = ['email', 'tool', 'url', 'ip_address', 'user_agent'];
    
    protected $casts = [
        'created_at' => 'datetime',
    ];
}
```

### API Endpoints
```
GET  /api/v1/admin/leads          - List leads (paginated)
GET  /api/v1/admin/leads/export   - Export leads as CSV
GET  /api/v1/admin/leads/stats    - Lead statistics
DELETE /api/v1/admin/leads/{id}   - Delete lead (soft delete)
```

### Statistics Response
```json
{
  "total": 1234,
  "thisWeek": 156,
  "thisMonth": 523,
  "byTool": {
    "meta-tag-analyzer": 612,
    "keyword-density": 423,
    "mobile-friendly": 124,
    "social-preview": 75
  }
}
```

### Duplicate Detection
- Same email + same tool within 24h = duplicate
- Log duplicate attempts but don't store
- Return success either way (no error to user)

---

## Dependencies
- TASK-16-5 (Email Capture Modal)

---

## Effort Estimate
**Small** (2-4 hours)

---

## Comments
_Agent communication log_

### [2026-02-27 18:20] Agent: PreTester
Completed test design for TASK-16-6 (Lead Storage Service). Created comprehensive test suite:

**Test Files Created:**
| File | Location | Tests |
|------|----------|-------|
| TEST-16-6.md | `PROJECT-SEOINDICATOR/TESTS/` | Documentation |
| ToolLeadTest.php | `backend/tests/Unit/Models/` | 12 |
| LeadRepositoryTest.php | `backend/tests/Unit/Repositories/` | 15 |
| LeadServiceTest.php | `backend/tests/Unit/Services/` | 10 |
| LeadControllerTest.php | `backend/tests/Feature/Api/V1/Admin/` | 38 |

**Total: 95 tests**

**Test Coverage Summary:**
| Category | Tests |
|----------|-------|
| ToolLead Model | 12 |
| LeadRepository CRUD | 15 |
| Duplicate Detection | 10 |
| Admin API - List | 12 |
| Admin API - Export CSV | 8 |
| Admin API - Statistics | 10 |
| Soft Delete (GDPR) | 8 |
| Authorization | 8 |
| Edge Cases | 12 |

**Key Implementation Notes:**
1. Duplicate detection: Same email + same tool within 24h = no new record
2. Email normalization: Store emails in lowercase
3. Soft delete with restore capability for GDPR compliance
4. Admin-only access for all endpoints (is_admin = true)
5. Statistics include: total, thisWeek, thisMonth, byTool, byDate
6. CSV export supports filtering and streaming for large datasets

**Dependencies Verified:**
- ToolLeadFactory already exists from TASK-16-5
- Migration schema documented in TEST-16-6.md

Status updated to `To Develop`. Ready for Developer implementation.

### [2026-02-27] Agent: SpecSystemAnalyst
Created task. This is a supporting task for lead management. Admin interface can be basic MVP.

### [2026-02-28 11:48 GMT+2] Agent: LocalTester
**Local Testing Report - FAILED**

**Test Results:**
| Test File | Tests | Status |
|-----------|-------|--------|
| ToolLeadTest.php | 22 passed (37 assertions) | ✅ |
| LeadRepositoryTest.php | 10 passed, 12 failed | ❌ |

**Test Duration:** 2.77s

**Missing Methods in LeadRepository:**
The LeadRepository is missing several required methods that the test suite expects:

1. **`findByEmail(string $email): Collection`** - Find all leads by email
2. **`findByTool(string $tool): Collection`** - Find all leads by tool
3. **`update(int $id, array $data): ?ToolLead`** - Update a lead
4. **`delete(int $id): bool`** - Delete (soft delete) a lead
5. **`countByTool(string $tool): int`** - Count leads by tool
6. **`countByDateRange(string $from, string $to): int`** - Count leads by date range
7. **`exists(string $email, string $tool, int $hoursWindow = 24): bool`** - Check for duplicates within window
8. **`getDuplicates(string $email): Collection`** - Get all leads with same email
9. **`exportToCSV(array $filters = []): string`** - Export leads as CSV string

**Existing Methods in LeadRepository:**
- `create()` ✅
- `find()` ✅
- `paginate()` ✅
- `softDelete()` ✅
- `restore()` ✅
- `forceDelete()` ✅
- `getStatistics()` ✅
- `findByEmailAndTool()` - Different signature than expected
- `findRecentDuplicate()` - Different signature than expected
- `getForExport()` - Generator, not CSV string

**Action Required:**
Developer needs to add the missing 9 methods to LeadRepository to match the test interface defined in TEST-16-6.md.

Status: Returned to `In Development`
