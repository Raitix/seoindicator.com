# TEST-16-6: Lead Storage Service Tests

## STATUS
`Tests Coverage In Progress`

## Related
- Task: [TASK-16-6](../TASKS/TASK-16-6.md)
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Dependency: [TASK-16-5](../TASKS/TASK-16-5.md) (Email Capture Modal)

---

## Test Coverage Summary

| Category | Tests | Assertions | Status |
|----------|-------|------------|--------|
| ToolLead Model | 12 | 12 | ✅ |
| LeadRepository CRUD | 15 | 15 | ✅ |
| Duplicate Detection | 10 | 10 | ✅ |
| Admin API - List | 12 | 12 | ✅ |
| Admin API - Export CSV | 8 | 8 | ✅ |
| Admin API - Statistics | 10 | 10 | ✅ |
| Soft Delete (GDPR) | 8 | 8 | ✅ |
| Authorization | 8 | 8 | ✅ |
| Edge Cases | 12 | 12 | ✅ |
| **Total** | **95** | **95** | ✅ |

---

## Test Files

| File | Location | Tests | Status |
|------|----------|-------|--------|
| ToolLeadTest.php | `backend/tests/Unit/Models/` | 12 | ✅ To Create |
| LeadRepositoryTest.php | `backend/tests/Unit/Repositories/` | 15 | ✅ To Create |
| LeadServiceTest.php | `backend/tests/Unit/Services/` | 10 | ✅ To Create |
| LeadControllerTest.php | `backend/tests/Feature/Api/V1/Admin/` | 38 | ✅ To Create |
| ToolLeadFactory.php | `backend/database/factories/` | - | ✅ Exists (TASK-16-5) |

---

## Database Schema

```sql
CREATE TABLE tool_leads (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    tool VARCHAR(50) NOT NULL,
    url VARCHAR(2083),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_tool_leads_email (email),
    INDEX idx_tool_leads_tool (tool),
    INDEX idx_tool_leads_created_at (created_at),
    INDEX idx_tool_leads_deleted_at (deleted_at)
);
```

---

## Test Scenarios

### ToolLead Model (MODEL)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| MODEL-001 | Create lead | Valid data | Lead created |
| MODEL-002 | Fillable attributes | Mass assignment | All fields fillable |
| MODEL-003 | Casts | created_at | datetime cast |
| MODEL-004 | Soft delete | delete() | deleted_at set |
| MODEL-005 | Restore | restore() | deleted_at null |
| MODEL-006 | Force delete | forceDelete() | Permanently removed |
| MODEL-007 | Email lowercase | User@Example.COM | user@example.com |
| MODEL-008 | URL nullable | No URL | NULL allowed |
| MODEL-009 | IP address IPv6 | 2001:db8::1 | Stored correctly |
| MODEL-010 | User agent long | 500+ chars | Truncated to TEXT |
| MODEL-011 | Tool validation | Invalid tool | Validation error |
| MODEL-012 | Timestamps | Auto | created_at, updated_at set |

### LeadRepository CRUD (REPO)

| ID | Scenario | Method | Expected |
|----|----------|--------|----------|
| REPO-001 | Create lead | create($data) | Lead returned |
| REPO-002 | Find by ID | find($id) | Lead or null |
| REPO-003 | Find by email | findByEmail($email) | Collection |
| REPO-004 | Find by tool | findByTool($tool) | Collection |
| REPO-005 | Paginate | paginate($perPage) | LengthAwarePaginator |
| REPO-006 | Update lead | update($id, $data) | Updated lead |
| REPO-007 | Delete lead | delete($id) | true |
| REPO-008 | Soft delete | softDelete($id) | deleted_at set |
| REPO-009 | Restore | restore($id) | deleted_at null |
| REPO-010 | Force delete | forceDelete($id) | Permanently removed |
| REPO-011 | Count by tool | countByTool($tool) | Integer |
| REPO-012 | Count by date range | countByDateRange($from, $to) | Integer |
| REPO-013 | Exists check | exists($email, $tool) | boolean |
| REPO-014 | Get duplicates | getDuplicates($email) | Collection |
| REPO-015 | Export to CSV | exportToCSV($filters) | string (CSV) |

### Duplicate Detection (DUP)

| ID | Scenario | Condition | Expected |
|----|----------|-----------|----------|
| DUP-001 | Same email + same tool | Within 24h | Duplicate detected |
| DUP-002 | Same email + same tool | After 24h | New record created |
| DUP-003 | Same email + different tool | Within 24h | Separate records |
| DUP-004 | Duplicate logged | Duplicate found | Logged but no error |
| DUP-005 | Duplicate return success | Duplicate found | Success response |
| DUP-006 | Case insensitive email | User@EXAMPLE.com vs user@example.com | Treated as same |
| DUP-007 | Normalized email | user+tag@example.com vs user@example.com | Different records |
| DUP-008 | Concurrent requests | Same data, same time | One record, no race |
| DUP-009 | Check window | 24 hours | Configurable window |
| DUP-010 | Stats for duplicates | Query | Count of duplicate attempts |

### Admin API - List Leads (API-LIST)

| ID | Scenario | Request | Expected |
|----|----------|---------|----------|
| LIST-001 | List all leads | GET /api/v1/admin/leads | Paginated list |
| LIST-002 | Filter by tool | ?tool=meta-tag-analyzer | Filtered list |
| LIST-003 | Filter by email | ?email=user@example.com | Filtered list |
| LIST-004 | Filter by date range | ?from=2026-01-01&to=2026-01-31 | Filtered list |
| LIST-005 | Pagination | ?page=2&perPage=50 | Correct page |
| LIST-006 | Sort by date | ?sort=created_at&order=desc | Sorted list |
| LIST-007 | Include soft deleted | ?withTrashed=true | Include deleted |
| LIST-008 | Only soft deleted | ?onlyTrashed=true | Only deleted |
| LIST-009 | Unauthenticated | No auth token | 401 Unauthorized |
| LIST-010 | Non-admin user | Regular user | 403 Forbidden |
| LIST-011 | Admin user | Admin role | 200 OK |
| LIST-012 | Empty list | No leads | Empty data array |

### Admin API - Export CSV (API-CSV)

| ID | Scenario | Request | Expected |
|----|----------|---------|----------|
| CSV-001 | Export all | GET /api/v1/admin/leads/export | CSV download |
| CSV-002 | Export filtered | ?tool=meta-tag-analyzer | Filtered CSV |
| CSV-003 | CSV headers | Response | Correct headers |
| CSV-004 | CSV content | Response | Valid CSV format |
| CSV-005 | Large export | 10000+ leads | Streamed response |
| CSV-006 | Date range filter | ?from=...&to=... | Filtered CSV |
| CSV-007 | Unauthenticated | No auth token | 401 Unauthorized |
| CSV-008 | Non-admin user | Regular user | 403 Forbidden |

### Admin API - Statistics (API-STATS)

| ID | Scenario | Request | Expected |
|----|----------|---------|----------|
| STATS-001 | Total leads | GET /api/v1/admin/leads/stats | total count |
| STATS-002 | This week | Response | thisWeek count |
| STATS-003 | This month | Response | thisMonth count |
| STATS-004 | By tool breakdown | Response | byTool object |
| STATS-005 | By date breakdown | ?groupBy=date | byDate array |
| STATS-006 | Date range filter | ?from=...&to=... | Filtered stats |
| STATS-007 | Exclude soft deleted | Default | Deleted not counted |
| STATS-008 | Include soft deleted | ?withTrashed=true | Include deleted |
| STATS-009 | Unauthenticated | No auth token | 401 Unauthorized |
| STATS-010 | Non-admin user | Regular user | 403 Forbidden |

### Soft Delete / GDPR (GDPR)

| ID | Scenario | Method | Expected |
|----|----------|--------|----------|
| GDPR-001 | Soft delete | DELETE /api/v1/admin/leads/{id} | deleted_at set |
| GDPR-002 | Restore | POST /api/v1/admin/leads/{id}/restore | deleted_at null |
| GDPR-003 | Force delete | DELETE ...?force=true | Permanent removal |
| GDPR-004 | Query excludes deleted | Default query | Trashed excluded |
| GDPR-005 | Query includes deleted | withTrashed() | Trashed included |
| GDPR-006 | Anonymize email | POST /api/v1/admin/leads/{id}/anonymize | Email hashed/masked |
| GDPR-007 | Bulk soft delete | DELETE with IDs array | All deleted |
| GDPR-008 | Bulk restore | POST restore with IDs | All restored |

### Authorization (AUTH)

| ID | Scenario | User | Expected |
|----|----------|------|----------|
| AUTH-001 | List leads - admin | Admin role | 200 OK |
| AUTH-002 | List leads - non-admin | Regular user | 403 Forbidden |
| AUTH-003 | Export CSV - admin | Admin role | 200 OK |
| AUTH-004 | Export CSV - non-admin | Regular user | 403 Forbidden |
| AUTH-005 | Stats - admin | Admin role | 200 OK |
| AUTH-006 | Stats - non-admin | Regular user | 403 Forbidden |
| AUTH-007 | Delete - admin | Admin role | 200 OK |
| AUTH-008 | Delete - non-admin | Regular user | 403 Forbidden |

### Edge Cases (EC)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EC-001 | Empty email | email: "" | Validation error |
| EC-002 | Invalid email format | email: "not-an-email" | Validation error |
| EC-003 | Very long email | 255+ chars | Truncated or error |
| EC-004 | SQL injection | email: "'; DROP TABLE" | Sanitized |
| EC-005 | XSS in email | email: "<script>@x.com" | Sanitized |
| EC-006 | Unicode email | 用户@例え.jp | Valid (RFC 6531) |
| EC-007 | Invalid tool name | tool: "invalid-tool" | Validation error |
| EC-008 | Missing tool | No tool param | Validation error |
| EC-009 | Very long URL | 2084+ chars | Truncated or error |
| EC-010 | Invalid URL format | url: "not-a-url" | Stored as-is (nullable) |
| EC-011 | Concurrent create | Same data, same time | No duplicate (race handling) |
| EC-012 | Null values | url: null, ip: null | Stored correctly |

---

## API Specification

### GET /api/v1/admin/leads

**Headers:**
```
Authorization: Bearer <admin-token>
Accept: application/json
```

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | int | 1 | Page number |
| perPage | int | 15 | Items per page (max: 100) |
| tool | string | null | Filter by tool |
| email | string | null | Filter by email (partial match) |
| from | date | null | Filter from date |
| to | date | null | Filter to date |
| sort | string | created_at | Sort field |
| order | string | desc | Sort order (asc/desc) |
| withTrashed | bool | false | Include soft deleted |
| onlyTrashed | bool | false | Only soft deleted |

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "email": "user@example.com",
      "tool": "meta-tag-analyzer",
      "url": "https://example.com",
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2026-02-27T10:00:00Z",
      "deleted_at": null
    }
  ],
  "meta": {
    "total": 100,
    "page": 1,
    "perPage": 15,
    "lastPage": 7
  }
}
```

### GET /api/v1/admin/leads/export

**Response (200):**
```
Content-Type: text/csv
Content-Disposition: attachment; filename="leads-2026-02-27.csv"

id,email,tool,url,ip_address,user_agent,created_at
1,user@example.com,meta-tag-analyzer,https://example.com,192.168.1.1,Mozilla/5.0...,2026-02-27 10:00:00
```

### GET /api/v1/admin/leads/stats

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total": 1234,
    "thisWeek": 156,
    "thisMonth": 523,
    "byTool": {
      "meta-tag-analyzer": 612,
      "keyword-density": 423,
      "mobile-friendly": 124,
      "social-preview": 75
    },
    "byDate": [
      { "date": "2026-02-27", "count": 45 },
      { "date": "2026-02-26", "count": 52 }
    ]
  }
}
```

### DELETE /api/v1/admin/leads/{id}

**Response (200):**
```json
{
  "success": true,
  "message": "Lead deleted successfully"
}
```

### POST /api/v1/admin/leads/{id}/restore

**Response (200):**
```json
{
  "success": true,
  "message": "Lead restored successfully"
}
```

---

## Model Definition

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ToolLead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'email',
        'tool',
        'url',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Valid tool names
    public const VALID_TOOLS = [
        'meta-tag-analyzer',
        'keyword-density',
        'mobile-friendly',
        'social-preview',
    ];

    // Auto-normalize email to lowercase
    protected static function booted(): void
    {
        static::creating(function (self $lead) {
            $lead->email = strtolower($lead->email);
        });
    }
}
```

---

## Repository Interface

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\ToolLead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LeadRepositoryInterface
{
    public function create(array $data): ToolLead;
    public function find(int $id): ?ToolLead;
    public function findByEmail(string $email): Collection;
    public function findByTool(string $tool): Collection;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function update(int $id, array $data): ?ToolLead;
    public function delete(int $id): bool;
    public function softDelete(int $id): bool;
    public function restore(int $id): bool;
    public function forceDelete(int $id): bool;
    public function countByTool(string $tool): int;
    public function countByDateRange(string $from, string $to): int;
    public function exists(string $email, string $tool, int $hoursWindow = 24): bool;
    public function getStatistics(array $filters = []): array;
    public function exportToCSV(array $filters = []): string;
}
```

---

## Service Interface

```php
<?php

namespace App\Services\Contracts;

interface LeadServiceInterface
{
    public function store(array $data): array;
    public function isDuplicate(string $email, string $tool): bool;
    public function getPaginated(int $perPage = 15, array $filters = []): array;
    public function getStatistics(array $filters = []): array;
    public function exportCSV(array $filters = []): string;
    public function softDelete(int $id): bool;
    public function restore(int $id): bool;
}
```

---

## Policy Definition

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ToolLead;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, ToolLead $lead): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, ToolLead $lead): bool
    {
        return $user->is_admin;
    }

    public function restore(User $user, ToolLead $lead): bool
    {
        return $user->is_admin;
    }

    public function export(User $user): bool
    {
        return $user->is_admin;
    }
}
```

---

## Valid Tool Names

| Tool ID | Display Name |
|---------|--------------|
| `meta-tag-analyzer` | Meta Tag Analyzer |
| `keyword-density` | Keyword Density Checker |
| `mobile-friendly` | Mobile-Friendly Checker |
| `social-preview` | Social Media Preview |

---

## Statistics Response Fields

| Field | Type | Description |
|-------|------|-------------|
| total | int | Total leads (excluding deleted) |
| thisWeek | int | Leads created this week |
| thisMonth | int | Leads created this month |
| byTool | object | Count per tool |
| byDate | array | Count per date (last 30 days) |

---

## Notes for Developer

1. **Duplicate Detection:** Same email + same tool within 24 hours = no new record, return success
2. **Email Normalization:** Store emails in lowercase for consistent lookups
3. **Soft Delete:** Use Laravel's SoftDeletes trait for GDPR compliance
4. **Admin-Only Access:** All admin endpoints require `is_admin = true`
5. **CSV Export:** Use streaming for large exports (10k+ records)
6. **Rate Limiting:** Already handled in TASK-16-5 (5 per IP per hour)
7. **Statistics Cache:** Consider caching statistics for 5 minutes
8. **IPv6 Support:** Store up to 45 characters for IP addresses

---

## Comments

### [2026-02-27 18:10] Agent: PreTester
Created comprehensive test suite for TASK-16-6 (Lead Storage Service):
- Model tests: 12 tests
- Repository tests: 15 tests
- Duplicate detection: 10 tests
- Admin API tests: 38 tests (list, export, stats, delete)
- Authorization tests: 8 tests
- Edge cases: 12 tests
- Total: 95 tests

**Key Design Decisions:**
1. Duplicate detection window: 24 hours (configurable)
2. Email stored lowercase for consistent lookups
3. Soft delete with restore capability for GDPR
4. Admin-only access for all endpoints
5. Statistics include thisWeek, thisMonth, byTool, byDate

**Integration Notes:**
- ToolLeadFactory already exists from TASK-16-5
- Duplicate detection integrates with email capture from TASK-16-5
- Statistics endpoint supports filtering by date range

Status: Tests Coverage In Progress
Ready for Developer implementation.
