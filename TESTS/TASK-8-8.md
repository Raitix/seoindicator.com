# TESTS/TASK-8-8: Frontend Components for Projects

## Related
- Task: TASKS/TASK-8-8.md
- Feature: FEAT-008 Multi-Site Project Management
- Specification: SPEC-008

## Test Status
- [x] Tests designed
- [ ] Unit tests written
- [ ] Integration tests written
- [ ] E2E tests written
- [ ] All tests passing locally
- [ ] All tests passing in production

## Dependencies
- **TASK-8-7**: CRUD API for projects (status: To Write Tests) - Required for API mocking
- **TASK-8-1**: Projects Table (status: To Write Tests) - Required for type definitions
- **TASK-8-6**: Project Policies (status: To Write Tests) - Required for authorization tests

## Test Environment
- Framework: Jest + React Testing Library
- E2E: Playwright
- Component Testing: @testing-library/react
- Mocking: MSW (Mock Service Worker) for API calls

---

## Component Test Cases

### 1. ProjectSelector Component

#### UC-001: Renders with projects list
- **Type:** Unit
- **Description:** Verify ProjectSelector renders correctly with projects
- **Preconditions:** projects array provided as prop
- **Steps:**
  1. Render ProjectSelector with 3 mock projects
  2. Verify dropdown trigger is visible
  3. Click trigger to open dropdown
  4. Verify all 3 projects are listed
- **Expected Result:** Dropdown displays all projects

#### UC-002: Displays current project as selected
- **Type:** Unit
- **Description:** Verify currentProject prop is shown as selected
- **Preconditions:** currentProject prop provided
- **Steps:**
  1. Render ProjectSelector with currentProject set
  2. Verify current project name is displayed in trigger
- **Expected Result:** Current project name visible in trigger

#### UC-003: Calls onSelect when project selected
- **Type:** Unit
- **Description:** Verify onSelect callback is called with correct project
- **Preconditions:** onSelect mock function provided
- **Steps:**
  1. Render ProjectSelector with projects
  2. Open dropdown
  3. Click on a project
  4. Verify onSelect called with selected project
- **Expected Result:** onSelect called with correct project object

#### UC-004: Shows "Create New" button
- **Type:** Unit
- **Description:** Verify "Create New" option is available
- **Preconditions:** onCreateNew callback provided
- **Steps:**
  1. Render ProjectSelector
  2. Open dropdown
  3. Verify "Create New Project" option exists
  4. Click option
  5. Verify onCreateNew callback called
- **Expected Result:** Create New option visible and functional

#### UC-005: Empty projects state
- **Type:** Unit
- **Description:** Verify behavior when no projects exist
- **Preconditions:** Empty projects array
- **Steps:**
  1. Render ProjectSelector with empty projects array
  2. Verify "No projects" message or Create button prominently displayed
- **Expected Result:** Graceful empty state shown

#### UC-006: Loading state
- **Type:** Unit
- **Description:** Verify loading state while fetching projects
- **Preconditions:** loading prop = true
- **Steps:**
  1. Render ProjectSelector with loading=true
  2. Verify skeleton or spinner displayed
  3. Dropdown should be disabled during loading
- **Expected Result:** Loading indicator shown, interaction disabled

#### UC-007: Error state handling
- **Type:** Unit
- **Description:** Verify error display when projects fail to load
- **Preconditions:** error prop provided
- **Steps:**
  1. Render ProjectSelector with error message
  2. Verify error is displayed to user
  3. Verify retry option available
- **Expected Result:** Error message displayed with retry option

#### UC-008: Keyboard navigation
- **Type:** Unit
- **Description:** Verify keyboard accessibility
- **Preconditions:** Component rendered
- **Steps:**
  1. Focus on ProjectSelector
  2. Press Enter to open dropdown
  3. Press ArrowDown to navigate options
  4. Press Enter to select
  5. Press Escape to close
- **Expected Result:** Full keyboard navigation works

#### UC-009: Long project names truncation
- **Type:** Unit
- **Description:** Verify long project names are handled gracefully
- **Preconditions:** Project with very long name (>50 chars)
- **Steps:**
  1. Render with long-named project
  2. Verify text truncation with ellipsis
  3. Verify full name visible on hover/tooltip
- **Expected Result:** Long names truncated with tooltip

---

### 2. ProjectDashboard Component

#### UC-010: Renders with project metrics
- **Type:** Unit
- **Description:** Verify ProjectDashboard displays metrics correctly
- **Preconditions:** projectId and metrics props provided
- **Steps:**
  1. Render ProjectDashboard with mock metrics
  2. Verify all metric cards are displayed
  3. Verify metric values match props
- **Expected Result:** All metrics rendered correctly

#### UC-011: Shows project-scoped navigation
- **Type:** Unit
- **Description:** Verify navigation shows project-scoped links
- **Preconditions:** projectId provided
- **Steps:**
  1. Render ProjectDashboard
  2. Verify navigation links include projectId parameter
  3. Click each link and verify URL includes projectId
- **Expected Result:** All links scoped to current project

#### UC-012: Fetches metrics on mount
- **Type:** Integration
- **Description:** Verify metrics are fetched when component mounts
- **Preconditions:** API endpoint mocked
- **Steps:**
  1. Mount ProjectDashboard with projectId
  2. Verify API call made to /api/projects/{id}/metrics
  3. Verify loading state shown during fetch
  4. Verify metrics displayed after fetch
- **Expected Result:** Metrics fetched and displayed

#### UC-013: Handles no metrics scenario
- **Type:** Unit
- **Description:** Verify behavior when project has no metrics
- **Preconditions:** Empty metrics object
- **Steps:**
  1. Render ProjectDashboard with empty metrics
  2. Verify "No data" state shown
  3. Verify call-to-action for adding keywords
- **Expected Result:** Empty state with helpful guidance

#### UC-014: Refresh metrics action
- **Type:** Unit
- **Description:** Verify refresh button triggers metrics refetch
- **Preconditions:** Component rendered with metrics
- **Steps:**
  1. Render ProjectDashboard
  2. Click refresh button
  3. Verify loading state shown
  4. Verify API call made again
- **Expected Result:** Metrics refetched on refresh click

#### UC-015: Metrics date range filter
- **Type:** Unit
- **Description:** Verify date range filtering works
- **Preconditions:** Date range picker component available
- **Steps:**
  1. Render ProjectDashboard
  2. Select date range
  3. Verify API call includes date parameters
  4. Verify metrics update with filtered data
- **Expected Result:** Date filtering applied correctly

#### UC-016: Project not found handling
- **Type:** Unit
- **Description:** Verify 404 handling for non-existent project
- **Preconditions:** API returns 404
- **Steps:**
  1. Mock API to return 404 for project
  2. Render ProjectDashboard
  3. Verify "Project not found" message
  4. Verify link back to projects list
- **Expected Result:** 404 handled gracefully

---

### 3. AllProjectsView Component

#### UC-017: Renders aggregate metrics
- **Type:** Unit
- **Description:** Verify aggregate metrics displayed correctly
- **Preconditions:** aggregateMetrics prop provided
- **Steps:**
  1. Render AllProjectsView with mock aggregate metrics
  2. Verify total keywords, rankings, visibility metrics shown
  3. Verify comparison to previous period
- **Expected Result:** All aggregate metrics displayed

#### UC-018: Shows projects breakdown table
- **Type:** Unit
- **Description:** Verify projects breakdown table renders
- **Preconditions:** projectsBreakdown prop provided
- **Steps:**
  1. Render AllProjectsView with multiple projects
  2. Verify table shows each project row
  3. Verify columns: Name, Keywords, Avg Position, Visibility, Change
- **Expected Result:** Breakdown table shows all projects

#### UC-019: Sortable table columns
- **Type:** Unit
- **Description:** Verify table columns are sortable
- **Preconditions:** Table rendered with data
- **Steps:**
  1. Click on "Keywords" column header
  2. Verify table sorted by keywords count
  3. Click again for descending order
  4. Click on "Visibility" header
  5. Verify table sorted by visibility
- **Expected Result:** Sorting works on all columns

#### UC-020: Click to view project
- **Type:** Unit
- **Description:** Verify clicking project row navigates to project
- **Preconditions:** Table with clickable rows
- **Steps:**
  1. Click on a project row
  2. Verify navigation to /projects/{id}/dashboard
- **Expected Result:** Navigation to project dashboard

#### UC-021: Filter by project status
- **Type:** Unit
- **Description:** Verify status filter functionality
- **Preconditions:** Projects with different statuses
- **Steps:**
  1. Render AllProjectsView
  2. Apply "Active" filter
  3. Verify only active projects shown
  4. Apply "Archived" filter
  5. Verify only archived projects shown
- **Expected Result:** Filtering works correctly

#### UC-022: Search projects
- **Type:** Unit
- **Description:** Verify search functionality
- **Preconditions:** Multiple projects exist
- **Steps:**
  1. Type search query in search box
  2. Verify table filtered to matching projects
  3. Clear search
  4. Verify all projects shown again
- **Expected Result:** Search filters projects correctly

#### UC-023: No projects state
- **Type:** Unit
- **Description:** Verify empty state when no projects
- **Preconditions:** No projects in account
- **Steps:**
  1. Render AllProjectsView with empty array
  2. Verify "Create your first project" CTA shown
  3. Verify link to project creation page
- **Expected Result:** Empty state with CTA

#### UC-024: Aggregate chart visualization
- **Type:** Unit
- **Description:** Verify aggregate trend chart displays
- **Preconditions:** Historical metrics data provided
- **Steps:**
  1. Render AllProjectsView with historical data
  2. Verify line chart shows visibility trend
  3. Verify chart legend for each project
  4. Verify hover tooltips work
- **Expected Result:** Chart renders with historical data

---

### 4. ProjectSettingsView Component

#### UC-025: Renders project settings form
- **Type:** Unit
- **Description:** Verify form displays current project settings
- **Preconditions:** project prop provided
- **Steps:**
  1. Render ProjectSettingsView with mock project
  2. Verify name field shows project name
  3. Verify domain field shows project domain
  4. Verify other settings fields populated
- **Expected Result:** Form populated with current values

#### UC-026: Form validation - name required
- **Type:** Unit
- **Description:** Verify name field validation
- **Preconditions:** Form rendered
- **Steps:**
  1. Clear name field
  2. Click Save
  3. Verify validation error shown
  4. Verify form not submitted
- **Expected Result:** Validation error for empty name

#### UC-027: Form validation - domain format
- **Type:** Unit
- **Description:** Verify domain field validation
- **Preconditions:** Form rendered
- **Steps:**
  1. Enter invalid domain "not-a-domain"
  2. Click Save
  3. Verify validation error shown
  4. Enter valid domain "example.com"
  5. Verify no validation error
- **Expected Result:** Domain format validated

#### UC-028: Save changes calls API
- **Type:** Integration
- **Description:** Verify save functionality
- **Preconditions:** Form with valid data, API mocked
- **Steps:**
  1. Modify project name
  2. Click Save button
  3. Verify PUT /api/projects/{id} called
  4. Verify success toast shown
  5. Verify onSave callback called
- **Expected Result:** Changes saved successfully

#### UC-029: Cancel reverts changes
- **Type:** Unit
- **Description:** Verify cancel functionality
- **Preconditions:** Form with modified values
- **Steps:**
  1. Modify project name
  2. Click Cancel button
  3. Verify form reset to original values
  4. Verify onCancel callback called
- **Expected Result:** Changes reverted on cancel

#### UC-030: Delete project action
- **Type:** Integration
- **Description:** Verify project deletion
- **Preconditions:** Project settings view with delete option
- **Steps:**
  1. Click "Delete Project" button
  2. Verify confirmation dialog shown
  3. Type project name to confirm
  4. Click "Delete" in dialog
  5. Verify DELETE /api/projects/{id} called
  6. Verify redirect to projects list
- **Expected Result:** Project deleted after confirmation

#### UC-031: Unsaved changes warning
- **Type:** Unit
- **Description:** Verify navigation warning for unsaved changes
- **Preconditions:** Form with modified values
- **Steps:**
  1. Modify project name
  2. Attempt to navigate away
  3. Verify confirmation dialog shown
  4. Click "Discard" to proceed
  5. Verify navigation completed
  6. Click "Stay" to remain on page
  7. Verify still on settings page
- **Expected Result:** Warning shown for unsaved changes

#### UC-032: Archive project action
- **Type:** Integration
- **Description:** Verify project archiving
- **Preconditions:** Archive option available
- **Steps:**
  1. Click "Archive Project" button
  2. Verify confirmation dialog shown
  3. Confirm archive action
  4. Verify API call made
  5. Verify project status updated
- **Expected Result:** Project archived successfully

#### UC-033: Settings save loading state
- **Type:** Unit
- **Description:** Verify loading state during save
- **Preconditions:** API call in progress
- **Steps:**
  1. Click Save button
  2. Verify button shows loading spinner
  3. Verify button disabled during save
  4. After save, verify button returns to normal
- **Expected Result:** Loading state shown during save

---

## Context Test Cases

### 5. ProjectContext

#### UC-034: Provides projects list
- **Type:** Unit
- **Description:** Verify context provides projects array
- **Preconditions:** ProjectProvider wraps component
- **Steps:**
  1. Use useProjects() hook
  2. Verify projects array available
  3. Verify loading state available
  4. Verify error state available
- **Expected Result:** Context provides projects data

#### UC-035: Provides current project
- **Type:** Unit
- **Description:** Verify context tracks current project
- **Preconditions:** ProjectProvider with projectId
- **Steps:**
  1. Use useCurrentProject() hook
  2. Verify current project available
  3. Verify null when no project selected
- **Expected Result:** Current project accessible

#### UC-036: switchProject method
- **Type:** Unit
- **Description:** Verify project switching works
- **Preconditions:** Multiple projects exist
- **Steps:**
  1. Call switchProject(projectId)
  2. Verify current project updated
  3. Verify localStorage updated
  4. Verify navigation triggered
- **Expected Result:** Project switched correctly

#### UC-037: createProject method
- **Type:** Integration
- **Description:** Verify project creation through context
- **Preconditions:** API mocked, form data ready
- **Steps:**
  1. Call createProject({ name, domain })
  2. Verify POST /api/projects called
  3. Verify new project added to list
  4. Verify new project set as current
- **Expected Result:** Project created and selected

#### UC-038: updateProject method
- **Type:** Integration
- **Description:** Verify project update through context
- **Preconditions:** Existing project, API mocked
- **Steps:**
  1. Call updateProject(id, { name: "New Name" })
  2. Verify PUT /api/projects/{id} called
  3. Verify project updated in projects list
  4. Verify optimistic update applied
- **Expected Result:** Project updated in context

#### UC-039: deleteProject method
- **Type:** Integration
- **Description:** Verify project deletion through context
- **Preconditions:** Existing project, API mocked
- **Steps:**
  1. Call deleteProject(id)
  2. Verify DELETE /api/projects/{id} called
  3. Verify project removed from list
  4. If deleted was current, verify first project selected
- **Expected Result:** Project removed from context

#### UC-040: Persist current project to localStorage
- **Type:** Unit
- **Description:** Verify project selection persisted
- **Preconditions:** localStorage available
- **Steps:**
  1. Select a project
  2. Verify localStorage.setItem called with projectId
  3. Refresh page
  4. Verify same project selected
- **Expected Result:** Selection persisted across sessions

#### UC-041: Fetch projects on mount
- **Type:** Integration
- **Description:** Verify projects fetched on provider mount
- **Preconditions:** API mocked
- **Steps:**
  1. Render component using ProjectProvider
  2. Verify GET /api/projects called
  3. Verify loading state during fetch
  4. Verify projects populated after fetch
- **Expected Result:** Projects fetched on mount

#### UC-042: Handle fetch error
- **Type:** Integration
- **Description:** Verify error handling for projects fetch
- **Preconditions:** API returns error
- **Steps:**
  1. Mock API to return 500 error
  2. Render ProjectProvider
  3. Verify error state set
  4. Verify retry function available
- **Expected Result:** Error handled gracefully

#### UC-043: Optimistic updates rollback
- **Type:** Integration
- **Description:** Verify rollback on API failure
- **Preconditions:** API will fail
- **Steps:**
  1. Call updateProject with changes
  2. Verify optimistic update applied immediately
  3. Mock API to return error
  4. Verify state rolled back to original
  5. Verify error notification shown
- **Expected Result:** Changes rolled back on failure

---

## API Integration Tests

### 6. API Endpoints

#### UC-044: GET /api/projects returns projects list
- **Type:** Integration
- **Description:** Verify projects list endpoint
- **Preconditions:** User authenticated, projects exist
- **Steps:**
  1. Make GET request to /api/projects
  2. Verify 200 response
  3. Verify projects array in response
  4. Verify each project has id, name, domain, created_at
- **Expected Result:** Projects list returned

#### UC-045: GET /api/projects/:id returns single project
- **Type:** Integration
- **Description:** Verify single project endpoint
- **Preconditions:** Project exists
- **Steps:**
  1. Make GET request to /api/projects/{id}
  2. Verify 200 response
  3. Verify project details returned
- **Expected Result:** Project details returned

#### UC-046: POST /api/projects creates project
- **Type:** Integration
- **Description:** Verify project creation endpoint
- **Preconditions:** Valid project data
- **Steps:**
  1. Make POST request with project data
  2. Verify 201 response
  3. Verify project created in database
  4. Verify response includes new project with id
- **Expected Result:** Project created successfully

#### UC-047: PUT /api/projects/:id updates project
- **Type:** Integration
- **Description:** Verify project update endpoint
- **Preconditions:** Project exists
- **Steps:**
  1. Make PUT request with updated data
  2. Verify 200 response
  3. Verify project updated in database
  4. Verify response includes updated project
- **Expected Result:** Project updated successfully

#### UC-048: DELETE /api/projects/:id deletes project
- **Type:** Integration
- **Description:** Verify project deletion endpoint
- **Preconditions:** Project exists
- **Steps:**
  1. Make DELETE request
  2. Verify 200 or 204 response
  3. Verify project removed from database
  4. Verify associated data handled (cascade/nullify)
- **Expected Result:** Project deleted successfully

#### UC-049: Authorization - User can only access own projects
- **Type:** Integration
- **Description:** Verify user isolation
- **Preconditions:** Two users with different projects
- **Steps:**
  1. User A creates project
  2. User B attempts to access User A's project
  3. Verify 403 or 404 response
  4. User B attempts to update User A's project
  5. Verify 403 response
- **Expected Result:** Users isolated from other's projects

---

## E2E Test Cases

### 7. End-to-End Workflows

#### UC-050: Create new project flow
- **Type:** E2E
- **Description:** Verify full project creation flow
- **Preconditions:** User logged in
- **Steps:**
  1. Navigate to /projects
  2. Click "New Project" button
  3. Fill in project name: "Test Project"
  4. Fill in domain: "example.com"
  5. Click "Create Project"
  6. Verify redirect to project dashboard
  7. Verify project in selector
- **Expected Result:** Project created and accessible

#### UC-051: Switch between projects
- **Type:** E2E
- **Description:** Verify project switching flow
- **Preconditions:** Multiple projects exist
- **Steps:**
  1. Note current project name
  2. Open project selector dropdown
  3. Click different project
  4. Verify URL updated with new project ID
  5. Verify dashboard shows new project data
  6. Verify selector shows new project name
- **Expected Result:** Project switched successfully

#### UC-052: Edit project settings
- **Type:** E2E
- **Description:** Verify project editing flow
- **Preconditions:** Project exists
- **Steps:**
  1. Navigate to project settings
  2. Change project name
  3. Click Save
  4. Verify success message
  5. Navigate away and back
  6. Verify new name persisted
- **Expected Result:** Settings saved successfully

#### UC-053: Delete project with confirmation
- **Type:** E2E
- **Description:** Verify project deletion flow
- **Preconditions:** Project exists with no critical data
- **Steps:**
  1. Navigate to project settings
  2. Click "Delete Project"
  3. Verify confirmation dialog appears
  4. Type project name to confirm
  5. Click "Delete"
  6. Verify redirect to projects list
  7. Verify project no longer in list
- **Expected Result:** Project deleted after confirmation

#### UC-054: View all projects aggregate
- **Type:** E2E
- **Description:** Verify aggregate view functionality
- **Preconditions:** Multiple projects with data
- **Steps:**
  1. Navigate to /projects (all projects view)
  2. Verify aggregate metrics shown
  3. Verify breakdown table lists all projects
  4. Sort by visibility column
  5. Verify table re-sorted
  6. Click on project row
  7. Verify navigation to project dashboard
- **Expected Result:** Aggregate view functional

#### UC-055: Archive project
- **Type:** E2E
- **Description:** Verify project archiving flow
- **Preconditions:** Active project exists
- **Steps:**
  1. Navigate to project settings
  2. Click "Archive Project"
  3. Confirm in dialog
  4. Verify project no longer in active list
  5. Toggle to show archived projects
  6. Verify project visible in archived list
- **Expected Result:** Project archived correctly

---

## Security Test Cases

### 8. Security Tests

#### UC-056: XSS prevention in project name
- **Type:** Security
- **Description:** Verify XSS is prevented in project fields
- **Steps:**
  1. Create project with name: `<script>alert('xss')</script>`
  2. Verify script not executed
  3. Verify name displayed as escaped text
  4. Check project selector dropdown
  5. Verify XSS payload not executed
- **Expected Result:** XSS payload neutralized

#### UC-057: XSS prevention in domain field
- **Type:** Security
- **Description:** Verify XSS is prevented in domain field
- **Steps:**
  1. Create project with domain: `javascript:alert('xss')`
  2. Verify validation rejects invalid domain
  3. If allowed, verify link not clickable as javascript:
- **Expected Result:** JavaScript URLs rejected

#### UC-058: CSRF protection on mutations
- **Type:** Security
- **Description:** Verify CSRF tokens on POST/PUT/DELETE
- **Steps:**
  1. Attempt POST without CSRF token
  2. Verify request rejected
  3. Include valid CSRF token
  4. Verify request accepted
- **Expected Result:** CSRF protection active

#### UC-059: Authorization header present
- **Type:** Security
- **Description:** Verify auth token included in requests
- **Steps:**
  1. Intercept API requests
  2. Verify Authorization header present
  3. Verify Bearer token format
- **Expected Result:** Auth token included

#### UC-060: No sensitive data in localStorage beyond projectId
- **Type:** Security
- **Description:** Verify localStorage doesn't expose sensitive data
- **Steps:**
  1. Check localStorage contents after login
  2. Verify only projectId stored, not tokens
  3. Verify no project details in localStorage
- **Expected Result:** Minimal data in localStorage

---

## Accessibility Test Cases

### 9. Accessibility Tests

#### UC-061: ProjectSelector keyboard accessible
- **Type:** Accessibility
- **Description:** Verify full keyboard navigation
- **Steps:**
  1. Tab to ProjectSelector
  2. Press Enter to open
  3. Arrow keys to navigate options
  4. Enter to select
  5. Escape to close
- **Expected Result:** Full keyboard access

#### UC-062: Screen reader announcements
- **Type:** Accessibility
- **Description:** Verify screen reader compatibility
- **Steps:**
  1. Enable screen reader
  2. Navigate to ProjectSelector
  3. Verify "Project selector, currently selected: [name]" announced
  4. Open dropdown
  5. Verify options announced
- **Expected Result:** Proper ARIA labels

#### UC-063: Focus management on project switch
- **Type:** Accessibility
- **Description:** Verify focus after project switch
- **Steps:**
  1. Switch project via selector
  2. Verify focus moves to main content
  3. Verify new project announced
- **Expected Result:** Focus managed correctly

#### UC-064: Form labels in ProjectSettingsView
- **Type:** Accessibility
- **Description:** Verify form accessibility
- **Steps:**
  1. Navigate to project settings
  2. Verify all inputs have labels
  3. Verify error messages associated with inputs
  4. Tab through all form elements
- **Expected Result:** Form fully accessible

#### UC-065: Color contrast compliance
- **Type:** Accessibility
- **Description:** Verify WCAG color contrast
- **Steps:**
  1. Check text on all components
  2. Verify 4.5:1 contrast ratio for normal text
  3. Verify 3:1 for large text
- **Expected Result:** WCAG AA compliant

---

## Performance Test Cases

### 10. Performance Tests

#### UC-066: ProjectSelector renders with 100 projects
- **Type:** Performance
- **Description:** Verify performance with many projects
- **Steps:**
  1. Render ProjectSelector with 100 projects
  2. Verify dropdown opens in < 100ms
  3. Verify typing in search responsive
  4. Verify no lag on selection
- **Expected Result:** Smooth performance

#### UC-067: AllProjectsView with 50 projects
- **Type:** Performance
- **Description:** Verify table performance
- **Steps:**
  1. Render AllProjectsView with 50 projects
  2. Verify table renders in < 500ms
  3. Verify sorting completes in < 100ms
  4. Verify smooth scrolling
- **Expected Result:** Acceptable performance

#### UC-068: API response time for projects list
- **Type:** Performance
- **Description:** Verify API performance
- **Steps:**
  1. Make request to /api/projects
  2. Verify response in < 200ms
  3. Test with 100 projects
  4. Verify response in < 500ms
- **Expected Result:** Fast API response

#### UC-069: Chart rendering performance
- **Type:** Performance
- **Description:** Verify chart with historical data
- **Steps:**
  1. Render aggregate chart with 365 days data
  2. Verify chart renders in < 1000ms
  3. Verify hover interactions smooth
  4. Verify no memory leaks on re-render
- **Expected Result:** Charts performant

---

## Edge Cases

### 11. Edge Cases

#### UC-070: Project with no domain
- **Type:** Edge Case
- **Description:** Verify handling of optional domain
- **Steps:**
  1. Create project without domain
  2. Verify project displays correctly
  3. Verify "Add domain" prompt shown
- **Expected Result:** Graceful handling

#### UC-071: Project with special characters in name
- **Type:** Edge Case
- **Description:** Verify special character handling
- **Steps:**
  1. Create project with name: "Test & Co. <special>"
  2. Verify displayed correctly
  3. Verify URL slug generated safely
- **Expected Result:** Special characters handled

#### UC-072: Duplicate project name allowed
- **Type:** Edge Case
- **Description:** Verify duplicate names allowed for same user
- **Steps:**
  1. Create project named "Test"
  2. Create another project named "Test"
  3. Verify both exist
  4. Verify selector shows both with distinguishing info
- **Expected Result:** Duplicates allowed with distinction

#### UC-073: Domain with and without protocol
- **Type:** Edge Case
- **Description:** Verify domain normalization
- **Steps:**
  1. Create project with domain "https://example.com"
  2. Verify stored as "example.com" (normalized)
  3. Create with "example.com/"
  4. Verify trailing slash removed
- **Expected Result:** Domain normalized

#### UC-074: Concurrent project switch
- **Type:** Edge Case
- **Description:** Verify rapid switching doesn't cause issues
- **Steps:**
  1. Quickly switch between projects 5 times
  2. Verify final state correct
  3. Verify no orphan API requests
  4. Verify no race conditions
- **Expected Result:** State consistent

#### UC-075: Network offline during project creation
- **Type:** Edge Case
- **Description:** Verify offline handling
- **Steps:**
  1. Go offline
  2. Attempt to create project
  3. Verify error message shown
  4. Go back online
  5. Verify retry works
- **Expected Result:** Offline handled gracefully

---

## Test File Locations

| Test Type | Location |
|-----------|----------|
| Unit - Components | `frontend/__tests__/components/projects/` |
| Unit - Context | `frontend/__tests__/contexts/ProjectContext.test.tsx` |
| Integration | `frontend/__tests__/integration/projects/` |
| E2E | `frontend/e2e/projects.spec.ts` |
| API Tests | `backend/tests/Feature/Projects/` |

---

## Test Execution Order

1. **Unit Tests** (no API required)
   - Component rendering tests
   - Context tests with mocks

2. **Integration Tests** (API mocked)
   - API integration tests
   - Form submission tests

3. **E2E Tests** (full stack)
   - User flow tests
   - Cross-component tests

---

## Notes for Developer

### Key Implementation Points
1. Use TypeScript interfaces for Project model
2. Implement optimistic updates for better UX
3. Handle loading states gracefully
4. Persist project selection to localStorage
5. Use React Query or SWR for data fetching
6. Implement proper error boundaries

### Mock Data Structure
```typescript
interface Project {
  id: number;
  name: string;
  domain: string | null;
  status: 'active' | 'archived';
  created_at: string;
  updated_at: string;
  keywords_count?: number;
  avg_position?: number;
  visibility?: number;
}

interface ProjectMetrics {
  keywords_count: number;
  avg_position: number;
  visibility: number;
  change: {
    visibility: number;
    avg_position: number;
  };
}
```

### Required API Endpoints
- `GET /api/projects` - List all projects
- `GET /api/projects/:id` - Get single project
- `POST /api/projects` - Create project
- `PUT /api/projects/:id` - Update project
- `DELETE /api/projects/:id` - Delete project
- `GET /api/projects/:id/metrics` - Get project metrics

---

## Test Summary

| Category | Count |
|----------|-------|
| ProjectSelector | 9 |
| ProjectDashboard | 7 |
| AllProjectsView | 8 |
| ProjectSettingsView | 9 |
| ProjectContext | 10 |
| API Integration | 6 |
| E2E Tests | 6 |
| Security Tests | 5 |
| Accessibility Tests | 5 |
| Performance Tests | 4 |
| Edge Cases | 6 |
| **Total** | **75** |

---

Last updated: 2026-02-26
