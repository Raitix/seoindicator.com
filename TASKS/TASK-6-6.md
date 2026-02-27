# TASK-6-6: Request Logging

## Status
`To Write Tests`

## Priority
`Medium`

## Risk
`Low`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Implement API request logging for analytics and debugging.

## Requirements

### Log Table
```sql
CREATE TABLE api_request_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    api_key_id INTEGER NOT NULL,
    endpoint TEXT NOT NULL,
    method TEXT NOT NULL,
    status_code INTEGER,
    response_time_ms INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
);
```

### Logging Middleware
- Capture request start time
- Log after response
- Include all metadata
- Async logging (don't block response)

### Retention
- Keep logs for 30 days
- Cleanup job (optional for MVP)

## Acceptance Criteria
- [ ] Table created
- [ ] Middleware implemented
- [ ] All API requests logged
- [ ] Response time captured
- [ ] Non-blocking logging

## Dependencies
- TASK-6-1 (Database schema)

## Estimated Effort
Small (1-2 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: Medium (useful but not MVP-critical)
- Risk: Low (logging only)
- Part of Phase 2: Rate Limiting
