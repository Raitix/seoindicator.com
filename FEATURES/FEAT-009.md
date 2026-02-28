# FEAT-009: Keyword Annotations

## STATUS
`To Write Tests`

## PRIORITY
`Low`

## RISK
`Low`

## Description
Allow users to add date-stamped notes and annotations to keywords and ranking history. Users can document SEO activities, algorithm updates, content changes, or any event that might explain ranking fluctuations. This creates a narrative timeline that helps users understand cause and effect in their SEO performance.

## User Story
As an SEO professional, I want to add notes to specific dates in my ranking history so that I can document what actions I took (content updates, link building, technical fixes) and correlate them with ranking changes over time.

## Business Impact
### Revenue
- **Agency productivity**: Reduces time spent explaining ranking changes to clients (notes are already there)
- **Team knowledge transfer**: Annotations preserve institutional knowledge when team members change
- **Onboarding value**: New team members can understand historical context quickly

### User Behavior
- **Cause-effect analysis**: Users actively document and analyze what impacts rankings
- **Learning loop**: Patterns emerge from annotated activities (e.g., "every time we update this page, rankings improve")
- **Client communication**: Agencies share annotation context with clients in reports

### Retention
- **Knowledge asset**: Accumulated annotations become valuable historical reference
- **Workflow integration**: Regular annotation becomes part of SEO workflow
- **Accountability**: Documented actions create sense of progress and responsibility

### Legal & Compliance
- **No additional concerns**: Annotations are user-generated private notes
- **Content moderation**: Consider basic profanity filter for shared team contexts (future)

### Complexity & Cost
- **Minimal implementation**: Simple database table, text input, date association
- **Low development effort**: 1-2 days implementation
- **No external dependencies**: Pure internal feature
- **UI integration**: Add annotation icon to keyword detail views and history charts

## Acceptance Criteria
- [ ] User can add annotation to specific keyword with date, title, and description
- [ ] User can edit existing annotations
- [ ] User can delete annotations
- [ ] Annotations appear on ranking history timeline/chart
- [ ] Hover or click on annotation marker shows full annotation details
- [ ] Annotation list view shows all annotations for a keyword chronologically
- [ ] Project-level annotation view shows all annotations across keywords for selected date range
- [ ] Annotations can be tagged with categories (e.g., "content update", "link building", "algorithm update", "technical fix")
- [ ] Annotations are included in PDF reports (as timeline notes)
- [ ] User can filter ranking history by annotations present
- [ ] Bulk annotation for multiple keywords (same event affects multiple keywords)

## Related
- Specification: [SPEC-009](../SPECIFICATIONS/SPEC-009.md)
- Tasks: (To be created by Developer)
- Enhances: FEAT-001 (context for rankings), FEAT-004 (reports include annotations)

## Notes
### Annotation Categories (suggested defaults)
| Category | Icon | Use Case |
|----------|------|----------|
| Content Update | ✏️ | Page content changes, new content |
| Link Building | 🔗 | New backlinks acquired |
| Technical Fix | 🔧 | Site speed, redirects, schema |
| Algorithm Update | 🤖 | Google algorithm changes |
| Competitor Action | 👀 | Competitor changes observed |
| Seasonal | 📅 | Seasonal traffic/events |
| Manual Action | ⚠️ | Google penalty or warning |
| Other | 📝 | General notes |

### Database Schema
```sql
annotations: id, user_id, project_id, keyword_id, date, title, description, category, created_at, updated_at

-- Optional: support annotations not tied to specific keyword
annotations: id, user_id, project_id, keyword_id (nullable), date, title, description, category, created_at, updated_at
```

### UI Integration
```
Ranking History Chart:
      ↑
  ┌───┼───┐  ← Annotation marker on chart
  │   │   │
──┼───┼───┼──┼──┼───→ Time
      ↓
  Click shows: "Mar 15: Content Update - Updated meta title and H1"
```

### MVP Scope
- Add/edit/delete annotations
- Show on keyword detail page (list view)
- Basic categories
- No chart integration (Phase 2)
- No bulk annotations (Phase 2)
- No project-wide annotation view (Phase 2)

---

## Comments
_Agent communication log_

### [2026-02-20 17:55] Agent: SpecSystemAnalyst
Task breakdown complete for SPEC-9. Created 10 tasks across database, backend, frontend, API, and testing phases. Status set to `To Prioritize`. Ready for ProjectManager review.

### [2026-02-20 19:40] Agent: SpecSystemAnalyst
Verified existing work: SPEC-009 exists with 10 tasks (TASK-9-1 through TASK-9-10). Feature status corrected to `To Prioritize`. Ready for ProjectManager review.

### [2026-02-19 19:24] Agent: Innovator
Created as ninth feature. Annotations are a lightweight, high-value feature that helps users make sense of ranking data. The implementation is simple but the value is significant - users can finally answer "what happened on that date?" when reviewing ranking history.

### [2026-02-19 19:24] Agent: Innovator
Priority Low because it's not essential for core functionality, but it significantly enhances the value of existing features. Risk Low due to minimal implementation complexity. This is a "delight" feature that users don't know they need until they have it.

### [2026-02-20 17:05] Agent: Architect
Created technical specification SPEC-009. Risk level confirmed as Low - simple CRUD with no external dependencies.

Key design decisions:
- `project_id` denormalized on annotations for efficient project-wide queries
- Category enum at application level (PHP enum, not DB enum)
- Annotations cascade delete with keywords
- Date validation: must be today or past (no future annotations)
- 8 default categories with icons

No paid services required ✅
Ready for SpecSystemAnalyst breakdown.

### [2026-02-28 16:13] Agent: ProjectManager
**Prioritization Review Complete:**

All 10 tasks have been previously prioritized:
| Task | Status | Priority | Notes |
|------|--------|----------|-------|
| TASK-9-1 | Done | High | Database schema - deployed |
| TASK-9-2 | To Write Tests | Medium | Enum for categories |
| TASK-9-3 | To Write Tests | High | Annotation model |
| TASK-9-4 | Done | High | Controller - implemented |
| TASK-9-5 | Tests Coverage Complete | High | Authorization policies |
| TASK-9-6 | To Write Tests | High | Input validation requests |
| TASK-9-7 | To Write Tests | Medium | AnnotationList component |
| TASK-9-8 | To Write Tests | Medium | AnnotationForm component |
| TASK-9-9 | To Write Tests | High | Unit tests |
| TASK-9-10 | Done | High | E2E integration tests |

**Priority Distribution:**
- High: 7 tasks (foundation/core functionality)
- Medium: 3 tasks (frontend components, enum)
- Low: 0 tasks

**Execution Order:**
1. TASK-9-2 (Enum) → TASK-9-3 (Model) → TASK-9-6 (Validation)
2. TASK-9-5 (Policies) - already has test coverage
3. TASK-9-7 & TASK-9-8 (Frontend) - can run in parallel
4. TASK-9-9 (Unit tests) - validates all backend components

**Feature Status:** `To Prioritize` → `To Write Tests`
