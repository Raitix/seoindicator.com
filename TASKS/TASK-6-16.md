# TASK-6-16: Event Emitters Integration

## Status
`To Write Tests`

## Priority
`High`

## Risk
`Medium`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Integrate webhook event emitters into existing services.

## Requirements

### Events to Emit
| Event | Trigger Location |
|-------|------------------|
| `ranking.changed` | Ranking check service |
| `alert.triggered` | Alert service |
| `report.completed` | Report generation |
| `keyword.added` | Keyword creation |
| `keyword.removed` | Keyword deletion |

### Event Emitter Pattern
```typescript
// In ranking service
eventEmitter.emit('ranking.changed', {
  keyword_id: keyword.id,
  keyword: keyword.keyword,
  old_position: previousPosition,
  new_position: currentPosition
});
```

### Integration Points
- Ranking service (position changes)
- Alert service (triggered alerts)
- Report service (completion)
- Keyword service (add/remove)

## Acceptance Criteria
- [ ] Events emitted at correct locations
- [ ] Event data complete
- [ ] Webhook dispatcher receives events
- [ ] No performance impact on main flows

## Dependencies
- TASK-6-14 (Webhook dispatcher)

## Estimated Effort
Medium (2-3 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (connects webhooks to features)
- Risk: Medium (modifies existing services)
- Part of Phase 4: Webhooks
