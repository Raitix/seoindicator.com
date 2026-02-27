# TASK-6-11: Reports API Endpoints

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
Implement REST API endpoint for triggering report generation.

## Requirements

### Endpoint
- `POST /api/v1/reports` - Generate report

### Request Format
```json
{
  "site_id": 1,
  "type": "weekly",
  "format": "pdf"
}
```

### Response Format
```json
{
  "success": true,
  "data": {
    "job_id": "rpt-abc123",
    "status": "queued"
  }
}
```

### Features
- Async report generation
- Job ID for status tracking
- Support multiple formats (PDF, CSV)

## Acceptance Criteria
- [ ] Endpoint working
- [ ] Job queued for processing
- [ ] Job ID returned
- [ ] Rate limiting applied

## Dependencies
- TASK-6-4 (API Key Management)
- Reports feature implementation

## Estimated Effort
Medium (2-3 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: Medium (Post-MVP feature)
- Risk: Low (triggers async job)
- Part of Phase 3: REST API
- Note: May need to wait for reports feature
