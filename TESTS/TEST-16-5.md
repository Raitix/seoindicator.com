# TEST-16-5: Email Capture Modal & API Tests

## STATUS
`Tests Coverage In Progress`

## Related
- Task: [TASK-16-5](../TASKS/TASK-16-5.md)
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)

---

## Test Coverage Summary

| Category | Tests | Assertions | Status |
|----------|-------|------------|--------|
| Modal Component | 12 | 12 | ✅ |
| Email Validation | 10 | 10 | ✅ |
| API Endpoint | 12 | 12 | ✅ |
| Lead Storage | 10 | 10 | ✅ |
| Session Handling | 8 | 8 | ✅ |
| Rate Limiting | 6 | 6 | ✅ |
| Edge Cases | 10 | 10 | ✅ |
| **Total** | **68** | **68** | ✅ |

---

## Test Scenarios

### Modal Component (MODAL)

| ID | Scenario | Condition | Expected |
|----|----------|-----------|----------|
| MODAL-001 | Shows on trigger | Click "See Full Analysis" | Modal visible |
| MODAL-002 | Hidden initially | Page load | Modal hidden |
| MODAL-003 | Closes on X | Click close button | Modal hidden |
| MODAL-004 | Closes on backdrop | Click outside | Modal hidden |
| MODAL-005 | Closes on Escape | Press Escape | Modal hidden |
| MODAL-006 | Shows basic results | Before capture | Top 3 issues |
| MODAL-007 | Email input field | Form visible | Input present |
| MODAL-008 | Submit button | Valid email | Button enabled |
| MODAL-009 | Loading state | Submitting | Spinner shown |
| MODAL-010 | Success message | After submit | Success shown |
| MODAL-011 | Already captured | Returning user | Skip modal |
| MODAL-012 | Responsive design | Mobile | Stacked layout |

### Email Validation (EMAIL)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EMAIL-001 | Valid email | user@example.com | Valid |
| EMAIL-002 | Invalid - no @ | userexample.com | Invalid |
| EMAIL-003 | Invalid - no domain | user@ | Invalid |
| EMAIL-004 | Invalid - no TLD | user@example | Invalid |
| EMAIL-005 | Invalid - spaces | user @example.com | Invalid |
| EMAIL-006 | Valid with + | user+tag@example.com | Valid |
| EMAIL-007 | Valid with . | first.last@example.com | Valid |
| EMAIL-008 | Max length | 255 chars | Valid |
| EMAIL-009 | Too long | 256+ chars | Invalid |
| EMAIL-010 | Empty | "" | Invalid |

### API Endpoint (API)

| ID | Scenario | Request | Expected |
|----|----------|---------|----------|
| API-001 | Create lead | POST valid email | 201 Created |
| API-002 | Missing email | POST without email | 422 Validation |
| API-003 | Invalid email | POST invalid email | 422 Validation |
| API-004 | Duplicate email | Same email + tool | Success (no error) |
| API-005 | Multiple tools | Email for each tool | Separate records |
| API-006 | Include URL | URL in request | Stored with lead |
| API-007 | Include IP | Client IP | Stored with lead |
| API-008 | Include UA | User agent | Stored with lead |
| API-009 | Rate limited | 5 per hour | 429 Too Many |
| API-010 | Unauthenticated | No auth required | Success |
| API-011 | Success response | Valid capture | {success: true} |
| API-012 | Return lead ID | Valid capture | {leadId: 123} |

### Lead Storage (STORE)

| ID | Scenario | Data | Expected |
|----|----------|------|----------|
| STORE-001 | Store all fields | Full request | All fields saved |
| STORE-002 | Duplicate prevention | Same email/tool 24h | No new record |
| STORE-003 | Different tools | Same email, diff tool | Separate records |
| STORE-004 | Timestamp | Auto | created_at set |
| STORE-005 | Email indexing | Query by email | Fast lookup |
| STORE-006 | Tool indexing | Query by tool | Fast lookup |
| STORE-007 | Soft delete | GDPR request | Soft deleted |
| STORE-008 | Export CSV | Admin request | CSV generated |
| STORE-009 | Statistics | Query stats | Counts returned |
| STORE-010 | Pagination | List leads | Paginated |

### Session Handling (SESS)

| ID | Scenario | Condition | Expected |
|----|----------|-----------|----------|
| SESS-001 | Store captured flag | After capture | localStorage set |
| SESS-002 | Read captured flag | Return visit | Flag read |
| SESS-003 | Skip modal | Already captured | No modal shown |
| SESS-004 | Clear on logout | User logs out | Flag cleared |
| SESS-005 | Per-tool tracking | Multiple tools | Independent |
| SESS-006 | Expiry handling | After 30 days | Flag expired |
| SESS-007 | Session storage | In-memory | Works correctly |
| SESS-008 | Cross-tab sync | Multiple tabs | Synced state |

### Rate Limiting (RATE)

| ID | Scenario | Requests | Expected |
|----|----------|----------|----------|
| RATE-001 | Under limit | 3 per hour | All allowed |
| RATE-002 | At limit | 5 per hour | All allowed |
| RATE-003 | Over limit | 6 per hour | 6th blocked |
| RATE-004 | Different IPs | 5 each | All allowed |
| RATE-005 | Retry after | At limit | Header present |
| RATE-006 | Error message | Rate limited | Clear message |

### Edge Cases (EC)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EC-001 | Very long email | 300+ chars | Truncated or error |
| EC-002 | Unicode email | 用户@例え.jp | Valid |
| EC-003 | SQL injection | '; DROP TABLE | Sanitized |
| EC-004 | XSS in email | <script>@x.com | Sanitized |
| EC-005 | Multiple submits | Rapid clicks | Debounced |
| EC-006 | Network error | Fetch fails | Error shown |
| EC-007 | Timeout | Long request | Cancelled |
| EC-008 | Concurrent requests | Same user | Handled |
| EC-009 | Missing tool | No tool param | Error |
| EC-010 | Invalid tool | Unknown tool | Error |

---

## Test Files

| File | Location | Tests |
|------|----------|-------|
| EmailCaptureModal.test.tsx | `frontend/__tests__/components/tools/` | 34 |
| EmailCaptureControllerTest.php | `backend/tests/Feature/Api/V1/Tools/` | 34 |

---

## Completion Date
2026-02-27
