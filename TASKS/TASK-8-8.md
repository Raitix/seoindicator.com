# TASK-8-8: Frontend Components for Projects

## STATUS
`To Write Tests`

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