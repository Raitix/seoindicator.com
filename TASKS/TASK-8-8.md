# TASK-8-8: Frontend Components for Projects

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Implement React components and context for project management. These components handle project selection, CRUD operations, and multi-site dashboard views.

## Components
1. **ProjectSelector**
   - A dropdown for switching projects.
      ```tsx
      <ProjectSelector 
        projects={projects}
        currentProject={currentProject}
        onSelect={switchProject}
        onCreateNew={() => setShowCreateModal(true)}
      />
      ```

2. **ProjectDashboard**
   - A dashboard scoped to the current project.
      ```tsx
      <ProjectDashboard 
        projectId={currentProject?.id}
        metrics={projectMetrics}
      />
      ```

3. **AllProjectsView**
   - An aggregate multi-site view:
      ```tsx
      <AllProjectsView 
        aggregateMetrics={aggregateMetrics}
        projectsBreakdown={projectsBreakdown}
      />
      ```

4. **ProjectSettingsView**
   - A form for editing project details.

## Acceptance Criteria
- [ ] Components built with TypeScript and tested.
- [ ] Context includes `switchProject`, `createProject`, `updateProject` methods.
- [ ] Components integrated into frontend navigation.
- [ ] All CRUD operations linked to the backend API.

## Dependencies
- TASK-8-7: CRUD API for projects must be functional.

## Effort Estimate
4-6 hours.

---

## Comments
### [2026-02-20 17:46] Agent: SpecSystemAnalyst
Frontend tasks created for project selection, dashboards, and aggregate metrics views.

### [2026-02-26 19:18] Agent: ProjectManager
**Prioritization Decision:**
- **Priority: High** (confirmed) - User-facing UI layer for FEAT-008 Multi-Site Project Management
- **Risk: Medium** (confirmed) - React components with TypeScript, API integration required

**Dependency Analysis:**
- **Depends on:** TASK-8-7 (CRUD API) - status `To Write Tests` ✓
- **Depends on:** TASK-8-1 (Projects Table) - status `To Write Tests` ✓ (indirect)
- **Depends on:** TASK-8-6 (Policies) - status `To Write Tests` ✓ (indirect)

**Why High Priority:**
1. **User-facing feature:** The UI layer is what users interact with for project management
2. **Core user story:** Enables "Project Selector" dropdown - key agency workflow
3. **Sequential dependency:** UI cannot be built until API layer exists (TASK-8-7)
4. **Business value:** Without frontend components, multi-site management is invisible to users

**Components to Implement:**
| Component | Purpose | Props |
|-----------|---------|-------|
| `ProjectSelector` | Dropdown for switching projects | `projects`, `currentProject`, `onSelect`, `onCreateNew` |
| `ProjectDashboard` | Dashboard scoped to current project | `projectId`, `metrics` |
| `AllProjectsView` | Aggregate multi-site view | `aggregateMetrics`, `projectsBreakdown` |
| `ProjectSettingsView` | Form for editing project details | `project`, `onSave`, `onCancel` |

**Context Requirements:**
- React Context with `switchProject`, `createProject`, `updateProject` methods
- TypeScript types for Project model
- Integration with backend API endpoints (TASK-8-7)
- Navigation integration

**Test Coverage Requirements:**
- Component rendering (each component)
- Context state management
- API integration mocking
- User interaction (select, create, edit)
- Navigation integration

**Status changed:** `To Prioritize` → `To Write Tests`

### [2026-02-26 22:35] Agent: PreTester
**Test Documentation Complete**

Created comprehensive test documentation at `TESTS/TASK-8-8.md` with **75 test scenarios** across 11 categories:

| Category | Tests | Focus |
|----------|-------|-------|
| ProjectSelector | 9 | Rendering, selection, keyboard nav, empty/loading/error states |
| ProjectDashboard | 7 | Metrics display, project scoping, date filtering, 404 handling |
| AllProjectsView | 8 | Aggregate metrics, sortable table, filtering, search, charts |
| ProjectSettingsView | 9 | Form validation, save/cancel, delete/archive, unsaved changes |
| ProjectContext | 10 | CRUD methods, state management, optimistic updates, persistence |
| API Integration | 6 | CRUD endpoints, authorization |
| E2E Tests | 6 | Full user workflows (create, switch, edit, delete) |
| Security Tests | 5 | XSS prevention, CSRF, authorization |
| Accessibility | 5 | Keyboard nav, screen reader, focus management |
| Performance | 4 | Large dataset handling, API response times |
| Edge Cases | 6 | Offline, concurrent ops, special chars, duplicates |

**Key Implementation Notes for Developer:**
1. Use TypeScript interfaces for Project model (interface provided in docs)
2. Implement optimistic updates with rollback on failure
3. Persist project selection to localStorage
4. Use SWR (already in deps) for data fetching
5. Handle loading/error states gracefully
6. Required API endpoints documented in test file

**Test File:** `TESTS/TASK-8-8.md`

**Status changed:** `To Write Tests` → `To Develop`