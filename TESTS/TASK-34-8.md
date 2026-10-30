# TESTS/TASK-34-8.md — Frontend Project List Page Tests

## Test Documentation
**Task:** TASK-34-8: Frontend Project List Page  
**Feature:** SPEC-034: AI Visibility Tracking (Generative Engine Optimization)  
**Status:** Tests Coverage In Progress  
**Created:** 2026-03-07  
**Agent:** PreTester

---

## Overview

Comprehensive test suite for the AI Visibility project list page. Tests cover:
- Page component rendering
- Project list component with filtering/sorting/pagination
- Project card interactions
- Empty state display
- Delete confirmation modal
- API hooks for data fetching
- Responsive design
- Accessibility

---

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `frontend/__tests__/app/ai-visibility/page.test.tsx` | 35 | Page component tests |
| `frontend/__tests__/components/ai-visibility/ProjectList.test.tsx` | 65 | List component tests |
| `frontend/__tests__/components/ai-visibility/ProjectCard.test.tsx` | 50 | Card component tests |
| `frontend/__tests__/components/ai-visibility/EmptyProjectsState.test.tsx` | 25 | Empty state tests |
| `frontend/__tests__/components/ai-visibility/DeleteProjectModal.test.tsx` | 30 | Modal tests |
| `frontend/__tests__/lib/api/ai-visibility-projects.test.ts` | 45 | API hooks tests |
| **Total** | **250** | |

---

## API Response Shape (from TASK-34-6)

```typescript
interface ProjectsApiResponse {
  data: Project[];
  meta: {
    current_page: number;
    total: number;
    per_page: number;
    last_page: number;
  };
}

interface Project {
  id: number;
  brand_name: string;
  is_active: boolean;
  last_queried_at: string | null;  // ISO 8601
  mention_rate: number;            // 0.0 - 1.0
  keywords_count: number;
  competitors_count: number;
  created_at: string;              // ISO 8601
}
```

---

## Test Categories

### 1. Page Component Tests (page.test.tsx)

| Category | Tests | Description |
|----------|-------|-------------|
| PAGE-RENDER | 5 | Page mounts, renders header, container structure |
| PAGE-LOADING | 4 | Loading skeleton during data fetch |
| PAGE-ERROR | 5 | Error state with retry button |
| PAGE-AUTH | 4 | Authentication requirement, redirect |
| PAGE-SEO | 4 | Metadata, title, description |
| PAGE-NAV | 3 | Create project button navigation |
| PAGE-INT | 5 | Full user flows |
| PAGE-A11Y | 5 | Accessibility attributes |

### 2. ProjectList Component Tests (ProjectList.test.tsx)

| Category | Tests | Description |
|----------|-------|-------------|
| PL-RENDER | 5 | Component mounts, renders projects |
| PL-CARDS | 5 | Project cards displayed correctly |
| PL-FILTER-STATUS | 8 | Filter by active/inactive status |
| PL-FILTER-SEARCH | 8 | Search by brand name |
| PL-SORT | 10 | Sort by created_at, last_queried_at, brand_name |
| PL-PAGINATION | 10 | Pagination controls, 10 per page |
| PL-LOADING | 5 | Skeleton cards during fetch |
| PL-ERROR | 5 | Error message with retry |
| PL-EMPTY | 4 | Empty state display |
| PL-INT | 5 | Full user flows |

### 3. ProjectCard Component Tests (ProjectCard.test.tsx)

| Category | Tests | Description |
|----------|-------|-------------|
| PC-RENDER | 5 | Card mounts, displays brand_name, mention_rate |
| PC-ACTIONS | 10 | View Dashboard, Run Query, Settings, Delete buttons |
| PC-STATUS | 5 | Active/inactive status badge |
| PC-TIME | 5 | last_queried_at display (relative time) |
| PC-RATE | 5 | Mention rate display (percentage) |
| PC-LOADING | 5 | Loading spinner on action buttons |
| PC-CONFIRM | 5 | Delete triggers confirmation modal |
| PC-A11Y | 5 | Accessibility attributes |
| PC-EDGE | 5 | Edge cases (null values, boundaries) |

### 4. EmptyProjectsState Component Tests (EmptyProjectsState.test.tsx)

| Category | Tests | Description |
|----------|-------|-------------|
| ES-RENDER | 5 | Component mounts, displays illustration |
| ES-CTA | 5 | "Create Project" button prominent |
| ES-TEXT | 5 | Helpful messaging |
| ES-NAV | 5 | Button navigates to create form |
| ES-A11Y | 5 | Accessibility attributes |

### 5. DeleteProjectModal Tests (DeleteProjectModal.test.tsx)

| Category | Tests | Description |
|----------|-------|-------------|
| DM-RENDER | 3 | Modal mounts when open |
| DM-CONFIRM | 5 | Confirm button triggers delete |
| DM-CANCEL | 5 | Cancel button closes modal |
| DM-LOADING | 5 | Loading state during deletion |
| DM-ERROR | 5 | Error handling with retry |
| DM-A11Y | 5 | Accessibility (focus trap, aria) |
| DM-EDGE | 2 | Edge cases |

### 6. API Hooks Tests (ai-visibility-projects.test.ts)

| Category | Tests | Description |
|----------|-------|-------------|
| API-FETCH | 8 | useProjects hook data fetching |
| API-PAGINATION | 5 | Pagination parameters |
| API-FILTER | 5 | Filter parameters |
| API-SORT | 5 | Sort parameters |
| API-MUTATIONS | 10 | useDeleteProject, useRunQuery hooks |
| API-ERROR | 5 | Error handling |
| API-CACHE | 5 | Cache invalidation |
| API-AUTH | 2 | Authentication errors |

---

## Component Interfaces

### ProjectListProps
```typescript
interface ProjectListProps {
  className?: string;
  onCreateProject?: () => void;
  onViewDashboard?: (projectId: number) => void;
  onEditProject?: (projectId: number) => void;
}
```

### ProjectCardProps
```typescript
interface ProjectCardProps {
  project: Project;
  onViewDashboard?: () => void;
  onRunQuery?: () => void;
  onEditProject?: () => void;
  onDelete?: () => void;
  isLoading?: boolean;
  className?: string;
}
```

### EmptyProjectsStateProps
```typescript
interface EmptyProjectsStateProps {
  onCreateProject?: () => void;
  className?: string;
}
```

### DeleteProjectModalProps
```typescript
interface DeleteProjectModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => Promise<void>;
  projectName: string;
  isLoading?: boolean;
}
```

---

## Mock Data

### Mock Projects
```typescript
const mockProjects: Project[] = [
  {
    id: 1,
    brand_name: 'Acme Corp',
    is_active: true,
    last_queried_at: '2026-03-07T10:00:00Z',
    mention_rate: 0.45,
    keywords_count: 10,
    competitors_count: 3,
    created_at: '2026-03-01T00:00:00Z'
  },
  {
    id: 2,
    brand_name: 'TechStart Inc',
    is_active: false,
    last_queried_at: null,
    mention_rate: 0,
    keywords_count: 5,
    competitors_count: 2,
    created_at: '2026-03-05T00:00:00Z'
  }
];
```

### Mock API Response
```typescript
const mockApiResponse: ProjectsApiResponse = {
  data: mockProjects,
  meta: {
    current_page: 1,
    total: 2,
    per_page: 10,
    last_page: 1
  }
};
```

---

## Test Patterns

### React Query Mock
```typescript
const mockUseQuery = vi.fn();
vi.mock('@tanstack/react-query', () => ({
  useQuery: (options: any) => mockUseQuery(options),
  useMutation: (fn: any) => ({
    mutate: vi.fn(),
    mutateAsync: vi.fn(),
    isLoading: false
  }),
  useQueryClient: () => ({
    invalidateQueries: vi.fn()
  })
}));
```

### Router Mock
```typescript
vi.mock('next/navigation', () => ({
  useRouter: () => ({
    push: vi.fn(),
    replace: vi.fn()
  }),
  useSearchParams: () => ({
    get: vi.fn(),
    toString: vi.fn(() => '')
  }),
  usePathname: () => '/ai-visibility'
}));
```

### Lucide Icons Mock
```typescript
vi.mock('lucide-react', () => ({
  Plus: () => <span data-testid="plus-icon" />,
  Search: () => <span data-testid="search-icon" />,
  ArrowUpDown: () => <span data-testid="sort-icon" />,
  ChevronLeft: () => <span data-testid="chevron-left-icon" />,
  ChevronRight: () => <span data-testid="chevron-right-icon" />,
  MoreVertical: () => <span data-testid="more-icon" />,
  Trash2: () => <span data-testid="trash-icon" />,
  Settings: () => <span data-testid="settings-icon" />,
  Play: () => <span data-testid="play-icon" />,
  Eye: () => <span data-testid="eye-icon" />,
  Loader2: () => <span data-testid="loader-icon" />,
  AlertCircle: () => <span data-testid="alert-icon" />,
  FolderPlus: () => <span data-testid="folder-plus-icon" />
}));
```

---

## Accessibility Requirements

| Component | ARIA Attributes |
|-----------|-----------------|
| ProjectList | `role="list"`, `aria-label="Projects list"` |
| ProjectCard | `role="listitem"`, `aria-label="Project: {brand_name}"` |
| Filter Dropdown | `aria-label="Filter by status"` |
| Sort Dropdown | `aria-label="Sort projects"` |
| Search Input | `aria-label="Search projects"` |
| Delete Button | `aria-label="Delete project"` |
| Delete Modal | `role="dialog"`, `aria-modal="true"`, `aria-labelledby` |
| Pagination | `aria-label="Pagination"` |

---

## Responsive Breakpoints

| Breakpoint | Cards per Row | Columns |
|------------|---------------|---------|
| Mobile (< 640px) | 1 | 1 |
| Tablet (640-1024px) | 2 | 2 |
| Desktop (> 1024px) | 3 | 3 |

---

## API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/ai-visibility/projects` | List projects with pagination |
| POST | `/api/v1/ai-visibility/projects/{id}/run` | Trigger query run |
| DELETE | `/api/v1/ai-visibility/projects/{id}` | Delete project |

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-34-6 (API Endpoints) | To Write Tests | Mocked in tests |
| React Query | Available | @tanstack/react-query |
| shadcn/ui | Available | Card, Button, Input, Select |
| Lucide Icons | Available | lucide-react |

---

## TDD Pattern

Tests are designed to fail until Developer implements the components. Each test file includes:
1. Mock component/interface for reference
2. Comprehensive test scenarios
3. Edge cases and error handling
4. Accessibility verification

---

## Acceptance Criteria Coverage

| AC | Tests | Status |
|----|-------|--------|
| Page created at `frontend/app/ai-visibility/page.tsx` | PAGE-RENDER | ✅ |
| Project list component displays | PL-RENDER, PL-CARDS | ✅ |
| "Create Project" button prominent | PAGE-NAV, ES-CTA | ✅ |
| Empty state with call-to-action | PL-EMPTY, ES-RENDER | ✅ |
| Project card actions | PC-ACTIONS | ✅ |
| Filtering by active/inactive | PL-FILTER-STATUS | ✅ |
| Sort by created_at, last_queried_at, brand_name | PL-SORT | ✅ |
| Search by brand name | PL-FILTER-SEARCH | ✅ |
| Pagination (10 per page) | PL-PAGINATION | ✅ |
| Loading skeleton cards | PL-LOADING | ✅ |
| Error states with retry | PL-ERROR, PAGE-ERROR | ✅ |
| API integration | API-FETCH, API-MUTATIONS | ✅ |
| Responsive design | PL-RENDER (responsive) | ✅ |
| Component tests | All test files | ✅ |

---

*Created by PreTester on 2026-03-07*
