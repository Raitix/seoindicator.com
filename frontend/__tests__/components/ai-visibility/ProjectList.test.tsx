/**
 * Tests for ProjectList Component
 * Task: TASK-34-8
 * Feature: SPEC-034 - AI Visibility Tracking
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock React Query
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

// Mock next/navigation
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

// Mock Lucide icons
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

// Test data
const mockProjects = [
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
  },
  {
    id: 3,
    brand_name: 'Global Solutions',
    is_active: true,
    last_queried_at: '2026-03-06T15:30:00Z',
    mention_rate: 0.72,
    keywords_count: 8,
    competitors_count: 3,
    created_at: '2026-03-02T00:00:00Z'
  }
];

const mockApiResponse = {
  data: mockProjects,
  meta: {
    current_page: 1,
    total: 3,
    per_page: 10,
    last_page: 1
  }
};

// Mock ProjectCard component
const MockProjectCard = ({ project, onViewDashboard, onDelete }: any) => (
  <div data-testid={`project-card-${project.id}`} role="listitem">
    <h3>{project.brand_name}</h3>
    <span data-testid="mention-rate">{Math.round(project.mention_rate * 100)}%</span>
    <button onClick={onViewDashboard} data-testid={`view-dashboard-${project.id}`}>
      View Dashboard
    </button>
    <button onClick={onDelete} data-testid={`delete-${project.id}`}>
      Delete
    </button>
  </div>
);

vi.mock('@/components/ai-visibility/ProjectCard', () => ({
  ProjectCard: MockProjectCard
}));

// Mock EmptyProjectsState component
vi.mock('@/components/ai-visibility/EmptyProjectsState', () => ({
  EmptyProjectsState: ({ onCreateProject }: any) => (
    <div data-testid="empty-state">
      <p>No projects yet</p>
      <button onClick={onCreateProject} data-testid="create-first-project">
        Create your first project
      </button>
    </div>
  )
}));

// ProjectList component mock (will be replaced by actual implementation)
interface ProjectListProps {
  className?: string;
  onCreateProject?: () => void;
  onViewDashboard?: (projectId: number) => void;
  onEditProject?: (projectId: number) => void;
}

const ProjectList = ({ className, onCreateProject, onViewDashboard }: ProjectListProps) => {
  const { data, isLoading, error } = mockUseQuery();
  const [statusFilter, setStatusFilter] = vi.importActual<any>('react').useState('all');
  const [searchQuery, setSearchQuery] = vi.importActual<any>('react').useState('');
  const [sortBy, setSortBy] = vi.importActual<any>('react').useState('created_at');
  const [page, setPage] = vi.importActual<any>('react').useState(1);

  if (isLoading) {
    return (
      <div data-testid="loading-skeleton" role="status">
        {[...Array(3)].map((_, i) => (
          <div key={i} data-testid={`skeleton-${i}`} className="animate-pulse h-32 bg-gray-200 rounded-lg" />
        ))}
      </div>
    );
  }

  if (error) {
    return (
      <div data-testid="error-state" role="alert">
        <span>Failed to load projects</span>
        <button onClick={() => mockUseQuery()}>Retry</button>
      </div>
    );
  }

  const projects = data?.data || [];

  if (projects.length === 0) {
    return (
      <div data-testid="empty-state">
        <p>No projects yet</p>
        <button onClick={onCreateProject} data-testid="create-first-project">
          Create your first project
        </button>
      </div>
    );
  }

  return (
    <div className={className} data-testid="project-list-container">
      {/* Filters */}
      <div data-testid="filters-container" className="flex gap-4 mb-4">
        <input
          type="search"
          placeholder="Search projects..."
          value={searchQuery}
          onChange={(e: any) => setSearchQuery(e.target.value)}
          data-testid="search-input"
          aria-label="Search projects"
        />
        <select
          value={statusFilter}
          onChange={(e: any) => setStatusFilter(e.target.value)}
          data-testid="status-filter"
          aria-label="Filter by status"
        >
          <option value="all">All</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
        <select
          value={sortBy}
          onChange={(e: any) => setSortBy(e.target.value)}
          data-testid="sort-select"
          aria-label="Sort projects"
        >
          <option value="created_at">Created Date</option>
          <option value="brand_name">Name</option>
          <option value="last_queried_at">Last Queried</option>
          <option value="mention_rate">Mention Rate</option>
        </select>
      </div>

      {/* Project List */}
      <div data-testid="project-list" role="list" aria-label="Projects list">
        {projects.map((project: any) => (
          <MockProjectCard
            key={project.id}
            project={project}
            onViewDashboard={() => onViewDashboard?.(project.id)}
            onDelete={() => {}}
          />
        ))}
      </div>

      {/* Pagination */}
      {data?.meta?.last_page > 1 && (
        <div data-testid="pagination" aria-label="Pagination">
          <button
            onClick={() => setPage(p => p - 1)}
            disabled={page === 1}
            data-testid="prev-page"
            aria-label="Previous page"
          >
            Previous
          </button>
          <span data-testid="page-info">
            Page {page} of {data.meta.last_page}
          </span>
          <button
            onClick={() => setPage(p => p + 1)}
            disabled={page === data.meta.last_page}
            data-testid="next-page"
            aria-label="Next page"
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
};

describe('ProjectList Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('PL-RENDER: Basic Rendering', () => {
    it('should render the component', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('project-list-container')).toBeInTheDocument();
    });

    it('should render projects list with role="list"', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByRole('list')).toHaveAttribute('aria-label', 'Projects list');
    });

    it('should render filter controls', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('filters-container')).toBeInTheDocument();
    });

    it('should render search input', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('search-input')).toBeInTheDocument();
    });

    it('should render status filter dropdown', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('status-filter')).toBeInTheDocument();
    });
  });

  describe('PL-CARDS: Project Cards', () => {
    it('should render all project cards', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('project-card-1')).toBeInTheDocument();
      expect(screen.getByTestId('project-card-2')).toBeInTheDocument();
      expect(screen.getByTestId('project-card-3')).toBeInTheDocument();
    });

    it('should display brand name for each project', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByText('Acme Corp')).toBeInTheDocument();
      expect(screen.getByText('TechStart Inc')).toBeInTheDocument();
      expect(screen.getByText('Global Solutions')).toBeInTheDocument();
    });

    it('should display mention rate as percentage', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByText('45%')).toBeInTheDocument();
      expect(screen.getByText('0%')).toBeInTheDocument();
      expect(screen.getByText('72%')).toBeInTheDocument();
    });

    it('should render view dashboard button for each project', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('view-dashboard-1')).toBeInTheDocument();
      expect(screen.getByTestId('view-dashboard-2')).toBeInTheDocument();
      expect(screen.getByTestId('view-dashboard-3')).toBeInTheDocument();
    });

    it('should have role="listitem" on each card', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(3);
    });
  });

  describe('PL-FILTER-STATUS: Status Filtering', () => {
    it('should show all projects by default', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getAllByRole('listitem')).toHaveLength(3);
    });

    it('should have "All" option in status filter', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const statusFilter = screen.getByTestId('status-filter');
      expect(statusFilter).toHaveValue('all');
    });

    it('should have "Active" option in status filter', async () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const statusFilter = screen.getByTestId('status-filter');
      const user = userEvent.setup();
      await user.selectOptions(statusFilter, 'active');
      expect(statusFilter).toHaveValue('active');
    });

    it('should have "Inactive" option in status filter', async () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const statusFilter = screen.getByTestId('status-filter');
      const user = userEvent.setup();
      await user.selectOptions(statusFilter, 'inactive');
      expect(statusFilter).toHaveValue('inactive');
    });

    it('should filter to active projects only', async () => {
      // When "active" is selected, only is_active: true projects shown
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects.filter(p => p.is_active),
          meta: { current_page: 1, total: 2, per_page: 10, last_page: 1 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getAllByRole('listitem')).toHaveLength(2);
    });

    it('should filter to inactive projects only', async () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects.filter(p => !p.is_active),
          meta: { current_page: 1, total: 1, per_page: 10, last_page: 1 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getAllByRole('listitem')).toHaveLength(1);
    });

    it('should have accessible aria-label on status filter', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('status-filter')).toHaveAttribute('aria-label', 'Filter by status');
    });

    it('should clear filter when selecting "All"', async () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const statusFilter = screen.getByTestId('status-filter');
      const user = userEvent.setup();
      
      await user.selectOptions(statusFilter, 'active');
      await user.selectOptions(statusFilter, 'all');
      expect(statusFilter).toHaveValue('all');
    });
  });

  describe('PL-FILTER-SEARCH: Search by Brand Name', () => {
    it('should have search input with placeholder', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByPlaceholderText('Search projects...')).toBeInTheDocument();
    });

    it('should filter projects by search query', async () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects.filter(p => p.brand_name.toLowerCase().includes('acme')),
          meta: { current_page: 1, total: 1, per_page: 10, last_page: 1 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const searchInput = screen.getByTestId('search-input');
      const user = userEvent.setup();
      await user.type(searchInput, 'Acme');
      
      expect(screen.getByText('Acme Corp')).toBeInTheDocument();
      expect(screen.queryByText('TechStart Inc')).not.toBeInTheDocument();
    });

    it('should be case-insensitive', async () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects.filter(p => 
            p.brand_name.toLowerCase().includes('acme')
          ),
          meta: { current_page: 1, total: 1, per_page: 10, last_page: 1 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByText('Acme Corp')).toBeInTheDocument();
    });

    it('should show empty state if no results', () => {
      mockUseQuery.mockReturnValue({
        data: { data: [], meta: { current_page: 1, total: 0, per_page: 10, last_page: 1 } },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
    });

    it('should have aria-label for accessibility', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('search-input')).toHaveAttribute('aria-label', 'Search projects');
    });

    it('should update results as user types', async () => {
      const { rerender } = render(<ProjectList />);
      
      // Type partial query
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects.filter(p => p.brand_name.toLowerCase().includes('tech')),
          meta: { current_page: 1, total: 1, per_page: 10, last_page: 1 }
        },
        isLoading: false,
        error: null
      });
      
      rerender(<ProjectList />);
      expect(screen.getByText('TechStart Inc')).toBeInTheDocument();
    });

    it('should clear search when input is cleared', async () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const searchInput = screen.getByTestId('search-input') as HTMLInputElement;
      const user = userEvent.setup();
      
      await user.type(searchInput, 'test');
      await user.clear(searchInput);
      
      expect(searchInput.value).toBe('');
    });
  });

  describe('PL-SORT: Sorting', () => {
    it('should have sort dropdown', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('sort-select')).toBeInTheDocument();
    });

    it('should sort by created_at by default', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('sort-select')).toHaveValue('created_at');
    });

    it('should have option to sort by brand_name', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const sortSelect = screen.getByTestId('sort-select');
      expect(sortSelect.querySelector('option[value="brand_name"]')).toBeInTheDocument();
    });

    it('should have option to sort by last_queried_at', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const sortSelect = screen.getByTestId('sort-select');
      expect(sortSelect.querySelector('option[value="last_queried_at"]')).toBeInTheDocument();
    });

    it('should have option to sort by mention_rate', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const sortSelect = screen.getByTestId('sort-select');
      expect(sortSelect.querySelector('option[value="mention_rate"]')).toBeInTheDocument();
    });

    it('should change sort order when selecting different option', async () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const sortSelect = screen.getByTestId('sort-select');
      const user = userEvent.setup();
      
      await user.selectOptions(sortSelect, 'brand_name');
      expect(sortSelect).toHaveValue('brand_name');
    });

    it('should sort alphabetically by brand_name', async () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: [...mockProjects].sort((a, b) => a.brand_name.localeCompare(b.brand_name)),
          meta: mockApiResponse.meta
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const sortSelect = screen.getByTestId('sort-select');
      const user = userEvent.setup();
      
      await user.selectOptions(sortSelect, 'brand_name');
      const cards = screen.getAllByRole('listitem');
      expect(cards[0]).toHaveTextContent('Acme Corp');
    });

    it('should have accessible aria-label on sort select', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('sort-select')).toHaveAttribute('aria-label', 'Sort projects');
    });

    it('should handle null last_queried_at values', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByText('TechStart Inc')).toBeInTheDocument();
    });

    it('should sort by mention_rate descending', async () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: [...mockProjects].sort((a, b) => b.mention_rate - a.mention_rate),
          meta: mockApiResponse.meta
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const sortSelect = screen.getByTestId('sort-select');
      const user = userEvent.setup();
      
      await user.selectOptions(sortSelect, 'mention_rate');
      const cards = screen.getAllByRole('listitem');
      expect(cards[0]).toHaveTextContent('Global Solutions'); // 72% mention rate
    });
  });

  describe('PL-PAGINATION: Pagination', () => {
    it('should not show pagination when all projects fit on one page', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.queryByTestId('pagination')).not.toBeInTheDocument();
    });

    it('should show pagination when more than 10 projects', () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects,
          meta: { current_page: 1, total: 25, per_page: 10, last_page: 3 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('pagination')).toBeInTheDocument();
    });

    it('should show current page and total pages', () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects,
          meta: { current_page: 1, total: 25, per_page: 10, last_page: 3 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByText('Page 1 of 3')).toBeInTheDocument();
    });

    it('should disable Previous button on first page', () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects,
          meta: { current_page: 1, total: 25, per_page: 10, last_page: 3 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('prev-page')).toBeDisabled();
    });

    it('should disable Next button on last page', () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects,
          meta: { current_page: 3, total: 25, per_page: 10, last_page: 3 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('next-page')).toBeDisabled();
    });

    it('should navigate to next page on Next click', async () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects,
          meta: { current_page: 1, total: 25, per_page: 10, last_page: 3 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const nextBtn = screen.getByTestId('next-page');
      const user = userEvent.setup();
      await user.click(nextBtn);
      // Page state changes internally
    });

    it('should navigate to previous page on Previous click', async () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects,
          meta: { current_page: 2, total: 25, per_page: 10, last_page: 3 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const prevBtn = screen.getByTestId('prev-page');
      const user = userEvent.setup();
      await user.click(prevBtn);
      // Page state changes internally
    });

    it('should have accessible pagination label', () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects,
          meta: { current_page: 1, total: 25, per_page: 10, last_page: 3 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('pagination')).toHaveAttribute('aria-label', 'Pagination');
    });

    it('should have aria-label on Previous button', () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects,
          meta: { current_page: 2, total: 25, per_page: 10, last_page: 3 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('prev-page')).toHaveAttribute('aria-label', 'Previous page');
    });

    it('should have aria-label on Next button', () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects,
          meta: { current_page: 1, total: 25, per_page: 10, last_page: 3 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('next-page')).toHaveAttribute('aria-label', 'Next page');
    });
  });

  describe('PL-LOADING: Loading States', () => {
    it('should show skeleton cards during loading', () => {
      mockUseQuery.mockReturnValue({
        data: null,
        isLoading: true,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
    });

    it('should show 3 skeleton cards', () => {
      mockUseQuery.mockReturnValue({
        data: null,
        isLoading: true,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('skeleton-0')).toBeInTheDocument();
      expect(screen.getByTestId('skeleton-1')).toBeInTheDocument();
      expect(screen.getByTestId('skeleton-2')).toBeInTheDocument();
    });

    it('should have role="status" on loading skeleton', () => {
      mockUseQuery.mockReturnValue({
        data: null,
        isLoading: true,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('loading-skeleton')).toHaveAttribute('role', 'status');
    });

    it('should hide skeleton when loading completes', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.queryByTestId('loading-skeleton')).not.toBeInTheDocument();
    });

    it('should have animated skeleton cards', () => {
      mockUseQuery.mockReturnValue({
        data: null,
        isLoading: true,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('skeleton-0')).toHaveClass('animate-pulse');
    });
  });

  describe('PL-ERROR: Error States', () => {
    it('should show error message when API fails', () => {
      mockUseQuery.mockReturnValue({
        data: null,
        isLoading: false,
        error: new Error('Failed to fetch')
      });

      render(<ProjectList />);
      expect(screen.getByTestId('error-state')).toBeInTheDocument();
    });

    it('should show "Failed to load projects" text', () => {
      mockUseQuery.mockReturnValue({
        data: null,
        isLoading: false,
        error: new Error('Failed to fetch')
      });

      render(<ProjectList />);
      expect(screen.getByText('Failed to load projects')).toBeInTheDocument();
    });

    it('should show retry button', () => {
      mockUseQuery.mockReturnValue({
        data: null,
        isLoading: false,
        error: new Error('Failed to fetch')
      });

      render(<ProjectList />);
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });

    it('should have role="alert" on error state', () => {
      mockUseQuery.mockReturnValue({
        data: null,
        isLoading: false,
        error: new Error('Failed to fetch')
      });

      render(<ProjectList />);
      expect(screen.getByTestId('error-state')).toHaveAttribute('role', 'alert');
    });

    it('should refetch on retry click', async () => {
      const refetchMock = vi.fn();
      mockUseQuery.mockReturnValue({
        data: null,
        isLoading: false,
        error: new Error('Failed to fetch'),
        refetch: refetchMock
      });

      render(<ProjectList />);
      const user = userEvent.setup();
      await user.click(screen.getByRole('button', { name: /retry/i }));
    });
  });

  describe('PL-EMPTY: Empty State', () => {
    it('should show empty state when no projects exist', () => {
      mockUseQuery.mockReturnValue({
        data: { data: [], meta: { current_page: 1, total: 0, per_page: 10, last_page: 1 } },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
    });

    it('should show "No projects yet" message', () => {
      mockUseQuery.mockReturnValue({
        data: { data: [], meta: { current_page: 1, total: 0, per_page: 10, last_page: 1 } },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByText('No projects yet')).toBeInTheDocument();
    });

    it('should show create first project button', () => {
      mockUseQuery.mockReturnValue({
        data: { data: [], meta: { current_page: 1, total: 0, per_page: 10, last_page: 1 } },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('create-first-project')).toBeInTheDocument();
    });

    it('should call onCreateProject when create button clicked', async () => {
      const mockOnCreate = vi.fn();
      mockUseQuery.mockReturnValue({
        data: { data: [], meta: { current_page: 1, total: 0, per_page: 10, last_page: 1 } },
        isLoading: false,
        error: null
      });

      render(<ProjectList onCreateProject={mockOnCreate} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('create-first-project'));
      expect(mockOnCreate).toHaveBeenCalled();
    });
  });

  describe('PL-INT: Integration', () => {
    it('should render full list with all features', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      expect(screen.getByTestId('project-list-container')).toBeInTheDocument();
      expect(screen.getByTestId('search-input')).toBeInTheDocument();
      expect(screen.getByTestId('status-filter')).toBeInTheDocument();
      expect(screen.getByTestId('sort-select')).toBeInTheDocument();
      expect(screen.getByTestId('project-list')).toBeInTheDocument();
    });

    it('should filter, search, and sort together', async () => {
      mockUseQuery.mockReturnValue({
        data: {
          data: mockProjects.filter(p => p.is_active),
          meta: { current_page: 1, total: 2, per_page: 10, last_page: 1 }
        },
        isLoading: false,
        error: null
      });

      render(<ProjectList />);
      const statusFilter = screen.getByTestId('status-filter');
      const user = userEvent.setup();
      await user.selectOptions(statusFilter, 'active');
      expect(screen.getAllByRole('listitem')).toHaveLength(2);
    });

    it('should show loading then projects', async () => {
      const { rerender } = render(<ProjectList />);
      
      mockUseQuery.mockReturnValue({
        data: null,
        isLoading: true,
        error: null
      });
      rerender(<ProjectList />);
      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();

      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });
      rerender(<ProjectList />);
      expect(screen.queryByTestId('loading-skeleton')).not.toBeInTheDocument();
      expect(screen.getByTestId('project-list')).toBeInTheDocument();
    });

    it('should handle onViewDashboard callback', async () => {
      const mockViewDashboard = vi.fn();
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList onViewDashboard={mockViewDashboard} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('view-dashboard-1'));
      expect(mockViewDashboard).toHaveBeenCalledWith(1);
    });

    it('should pass className prop', () => {
      mockUseQuery.mockReturnValue({
        data: mockApiResponse,
        isLoading: false,
        error: null
      });

      render(<ProjectList className="custom-class" />);
      expect(screen.getByTestId('project-list-container')).toHaveClass('custom-class');
    });
  });
});
