# TASK-8-8: Frontend Components for Projects

## STATUS
`To Prioritize`

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