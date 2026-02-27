# TASK-16-5: Email Capture Modal & API

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Specification: [SPEC-016](../SPECIFICATIONS/SPEC-016.md)

---

## Description
Implement the email capture modal that gates detailed analysis results. Users get basic results for free, but must provide their email to see the full analysis. This is the lead generation mechanism for the tools.

---

## Acceptance Criteria
- [ ] Create email capture modal component
- [ ] Show basic results (top 3 issues) before capture
- [ ] Gate detailed results behind email form
- [ ] Validate email format before submission
- [ ] Create API endpoint for email capture
- [ ] Store captured emails in database
- [ ] Add email verification (optional, future)
- [ ] Show success message after capture
- [ ] Remember user who already captured (localStorage/session)
- [ ] Rate limit email capture attempts
- [ ] Unit tests for modal and API

---

## Technical Details

### Modal Flow
1. User analyzes URL
2. Basic results shown (limited issues)
3. "See Full Analysis" button triggers modal
4. User enters email
5. On submit, show full results
6. Store email in leads table

### API Endpoint
```
POST /api/v1/tools/capture-email
Content-Type: application/json

{
  "email": "user@example.com",
  "tool": "meta-tag-analyzer",
  "url": "https://example.com"
}
```

### Database Schema
```sql
CREATE TABLE tool_leads (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    tool VARCHAR(50) NOT NULL,
    url VARCHAR(2083),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tool_leads_email (email),
    INDEX idx_tool_leads_tool (tool),
    INDEX idx_tool_leads_created_at (created_at)
);
```

### Frontend State
```typescript
// Store in localStorage after capture
localStorage.setItem('seoindicator_tools_captured', 'true');
```

### Validation
- Email: RFC 5322 compliant regex
- Rate limit: Max 5 captures per IP per hour

---

## Dependencies
- TASK-16-4 (Tool Embedding System)

---

## Effort Estimate
**Medium** (4-6 hours)

---

## Comments
_Agent communication log_

### [2026-02-27] Agent: SpecSystemAnalyst
Created task. This is critical for lead generation. Consider A/B testing different messaging in future.
