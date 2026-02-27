# TEST-16-5: Email Capture Modal & API Tests

## STATUS
`Tests Coverage In Progress`

## Related
- Task: [TASK-16-5](../TASKS/TASK-16-5.md)
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Specification: [SPEC-016](../SPECIFICATIONS/SPEC-016.md)

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
| Accessibility | 4 | 4 | ✅ |
| Additional Validation | 5 | 5 | ✅ |
| **Total** | **77** | **77** | ✅ |

---

## Test Files

| File | Location | Tests | Status |
|------|----------|-------|--------|
| EmailCaptureModal.test.tsx | `frontend/__tests__/components/tools/` | 34 | ✅ Created |
| EmailCaptureControllerTest.php | `backend/tests/Feature/Api/V1/Tools/` | 43 | ✅ Created |
| ToolLeadFactory.php | `backend/database/factories/` | - | ✅ Created |

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

### Accessibility (A11Y)

| ID | Scenario | Condition | Expected |
|----|----------|-----------|----------|
| A11Y-001 | ARIA attributes | Modal render | aria-modal="true" |
| A11Y-002 | Email label | Input visible | Accessible label |
| A11Y-003 | Error announcement | Validation error | role="alert" |
| A11Y-004 | Focus trap | Modal open | Tab cycles within |

### Additional Validation (VAL)

| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| VAL-001 | Valid tools | meta-tag-analyzer, etc. | Accepted |
| VAL-002 | Optional URL | No URL provided | Success |
| VAL-003 | Long URL | 2083+ chars | Handled |
| VAL-004 | Email lowercase | User@Example.COM | user@example.com |
| VAL-005 | IPv6 address | 2001:db8::... | Stored correctly |

---

## API Specification

### POST /api/v1/tools/capture-email

**Request:**
```json
{
  "email": "user@example.com",
  "tool": "meta-tag-analyzer",
  "url": "https://example.com"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "leadId": 123
  }
}
```

**Response (422 Validation Error):**
```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**Response (429 Rate Limited):**
```json
{
  "message": "Too many requests"
}
```

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
    deleted_at TIMESTAMP NULL,
    INDEX idx_tool_leads_email (email),
    INDEX idx_tool_leads_tool (tool),
    INDEX idx_tool_leads_created_at (created_at)
);
```

---

## Rate Limiting Configuration

```php
// config/rate-limiting.php
RateLimiter::for('email-capture', function (Request $request) {
    return Limit::perHour(5)->by($request->ip());
});
```

---

## Frontend Component Props

```typescript
interface EmailCaptureModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (email: string) => void;
  tool: 'meta-tag-analyzer' | 'keyword-density' | 'mobile-friendly' | 'social-preview';
  url?: string;
  basicResults: Array<{
    type: 'warning' | 'info' | 'error';
    message: string;
  }>;
}
```

---

## Local Storage Keys

| Key | Type | Description |
|-----|------|-------------|
| `seoindicator_tools_captured` | `'true'` or JSON | Global capture flag |
| `seoindicator_capture_date` | ISO date | Capture timestamp |
| `seoindicator_capture_tool_*` | JSON | Per-tool capture status |

---

## Notes for Developer

1. **Email Normalization:** Store emails in lowercase for consistent lookups
2. **Rate Limiting:** 5 captures per IP per hour - use sliding window
3. **Idempotency:** Same email + tool combination within 24h = no new record
4. **GDPR:** Support soft delete for "right to be forgotten" requests
5. **IPv6:** Store full IPv6 addresses (up to 45 chars)

---

## Comments

### [2026-02-27 17:45] Agent: PreTester
Created comprehensive test suite for TASK-16-5:
- Frontend: 34 tests in EmailCaptureModal.test.tsx
- Backend: 43 tests in EmailCaptureControllerTest.php
- Factory: ToolLeadFactory with helper states

Test coverage includes:
- Modal visibility and interaction
- Email validation (RFC 5322 compliance)
- API endpoint CRUD operations
- Lead storage with indexing
- Session/localStorage handling
- Rate limiting (5 per IP per hour)
- Edge cases and security tests
- Accessibility compliance

Status: Tests Coverage In Progress
Ready for Developer implementation.
