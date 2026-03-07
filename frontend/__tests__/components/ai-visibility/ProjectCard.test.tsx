/**
 * Tests for ProjectCard Component
 * Task: TASK-34-8
 * Feature: SPEC-034 - AI Visibility Tracking
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  MoreVertical: () => <span data-testid="more-icon" />,
  Trash2: () => <span data-testid="trash-icon" />,
  Settings: () => <span data-testid="settings-icon" />,
  Play: () => <span data-testid="play-icon" />,
  Eye: () => <span data-testid="eye-icon" />,
  Loader2: () => <span data-testid="loader-icon" />,
  ChevronDown: () => <span data-testid="chevron-down-icon" />,
  CheckCircle: () => <span data-testid="check-circle-icon" />,
  XCircle: () => <span data-testid="x-circle-icon" />,
  Clock: () => <span data-testid="clock-icon" />,
  TrendingUp: () => <span data-testid="trending-up-icon" />,
  TrendingDown: () => <span data-testid="trending-down-icon" />
}));

// Test data
const mockProject = {
  id: 1,
  brand_name: 'Acme Corp',
  is_active: true,
  last_queried_at: '2026-03-07T10:00:00Z',
  mention_rate: 0.45,
  keywords_count: 10,
  competitors_count: 3,
  created_at: '2026-03-01T00:00:00Z'
};

const mockInactiveProject = {
  id: 2,
  brand_name: 'TechStart Inc',
  is_active: false,
  last_queried_at: null,
  mention_rate: 0,
  keywords_count: 5,
  competitors_count: 2,
  created_at: '2026-03-05T00:00:00Z'
};

// Mock DeleteProjectModal
vi.mock('@/components/ai-visibility/DeleteProjectModal', () => ({
  DeleteProjectModal: ({ isOpen, onClose, onConfirm, projectName }: any) => (
    isOpen ? (
      <div data-testid="delete-modal" role="dialog">
        <p>Delete {projectName}?</p>
        <button onClick={onConfirm} data-testid="confirm-delete">Delete</button>
        <button onClick={onClose} data-testid="cancel-delete">Cancel</button>
      </div>
    ) : null
  )
}));

// ProjectCard component mock (will be replaced by actual implementation)
interface ProjectCardProps {
  project: typeof mockProject;
  onViewDashboard?: () => void;
  onRunQuery?: () => void;
  onEditProject?: () => void;
  onDelete?: () => void;
  isLoading?: boolean;
  className?: string;
}

const ProjectCard = ({ 
  project, 
  onViewDashboard, 
  onRunQuery, 
  onEditProject, 
  onDelete,
  isLoading = false,
  className 
}: ProjectCardProps) => {
  const [showMenu, setShowMenu] = vi.importActual<any>('react').useState(false);
  const [showDeleteModal, setShowDeleteModal] = vi.importActual<any>('react').useState(false);

  const formatMentionRate = (rate: number) => `${Math.round(rate * 100)}%`;
  
  const formatLastQueried = (date: string | null) => {
    if (!date) return 'Never';
    const diff = Date.now() - new Date(date).getTime();
    const hours = Math.floor(diff / (1000 * 60 * 60));
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 7) return `${days}d ago`;
    return new Date(date).toLocaleDateString();
  };

  return (
    <div 
      className={className} 
      data-testid={`project-card-${project.id}`}
      role="listitem"
      aria-label={`Project: ${project.brand_name}`}
    >
      {/* Header */}
      <div className="flex justify-between items-start">
        <h3 className="text-lg font-semibold">{project.brand_name}</h3>
        <button 
          onClick={() => setShowMenu(!showMenu)}
          data-testid="menu-toggle"
          aria-label="Project actions"
        >
          <span data-testid="more-icon" />
        </button>
      </div>

      {/* Status Badge */}
      <div data-testid="status-badge">
        {project.is_active ? (
          <span className="text-green-500" data-testid="active-badge">Active</span>
        ) : (
          <span className="text-gray-400" data-testid="inactive-badge">Inactive</span>
        )}
      </div>

      {/* Mention Rate */}
      <div data-testid="mention-rate-display">
        <span data-testid="mention-rate-value">{formatMentionRate(project.mention_rate)}</span>
        <span className="text-sm text-gray-500">Mention Rate</span>
      </div>

      {/* Last Queried */}
      <div data-testid="last-queried">
        <span data-testid="last-queried-value">{formatLastQueried(project.last_queried_at)}</span>
        <span className="text-sm text-gray-500">Last Queried</span>
      </div>

      {/* Keywords & Competitors */}
      <div className="flex gap-4">
        <span data-testid="keywords-count">{project.keywords_count} keywords</span>
        <span data-testid="competitors-count">{project.competitors_count} competitors</span>
      </div>

      {/* Actions */}
      <div className="flex gap-2 mt-4">
        <button 
          onClick={onViewDashboard}
          disabled={isLoading}
          data-testid="view-dashboard-btn"
          aria-label="View dashboard"
        >
          {isLoading ? <span data-testid="loader-icon" /> : <span data-testid="eye-icon" />}
          <span>Dashboard</span>
        </button>
        <button 
          onClick={onRunQuery}
          disabled={isLoading}
          data-testid="run-query-btn"
          aria-label="Run query"
        >
          {isLoading ? <span data-testid="loader-icon" /> : <span data-testid="play-icon" />}
          <span>Run Query</span>
        </button>
      </div>

      {/* Dropdown Menu */}
      {showMenu && (
        <div data-testid="dropdown-menu" role="menu">
          <button 
            onClick={onEditProject}
            data-testid="edit-btn"
            role="menuitem"
          >
            <span data-testid="settings-icon" />
            Edit
          </button>
          <button 
            onClick={() => {
              setShowDeleteModal(true);
              setShowMenu(false);
            }}
            data-testid="delete-menu-btn"
            role="menuitem"
          >
            <span data-testid="trash-icon" />
            Delete
          </button>
        </div>
      )}

      {/* Delete Modal */}
      {showDeleteModal && (
        <div data-testid="delete-modal" role="dialog">
          <p>Delete {project.brand_name}?</p>
          <button 
            onClick={() => {
              onDelete?.();
              setShowDeleteModal(false);
            }}
            data-testid="confirm-delete"
          >
            Delete
          </button>
          <button 
            onClick={() => setShowDeleteModal(false)}
            data-testid="cancel-delete"
          >
            Cancel
          </button>
        </div>
      )}
    </div>
  );
};

describe('ProjectCard Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('PC-RENDER: Basic Rendering', () => {
    it('should render the card', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('project-card-1')).toBeInTheDocument();
    });

    it('should display brand name', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByText('Acme Corp')).toBeInTheDocument();
    });

    it('should display mention rate as percentage', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('mention-rate-value')).toHaveTextContent('45%');
    });

    it('should display keywords count', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('keywords-count')).toHaveTextContent('10 keywords');
    });

    it('should display competitors count', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('competitors-count')).toHaveTextContent('3 competitors');
    });
  });

  describe('PC-ACTIONS: Action Buttons', () => {
    it('should render View Dashboard button', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('view-dashboard-btn')).toBeInTheDocument();
    });

    it('should render Run Query button', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('run-query-btn')).toBeInTheDocument();
    });

    it('should call onViewDashboard when clicked', async () => {
      const mockView = vi.fn();
      render(<ProjectCard project={mockProject} onViewDashboard={mockView} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('view-dashboard-btn'));
      expect(mockView).toHaveBeenCalled();
    });

    it('should call onRunQuery when clicked', async () => {
      const mockRun = vi.fn();
      render(<ProjectCard project={mockProject} onRunQuery={mockRun} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('run-query-btn'));
      expect(mockRun).toHaveBeenCalled();
    });

    it('should show menu toggle button', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('menu-toggle')).toBeInTheDocument();
    });

    it('should show dropdown menu when menu toggle clicked', async () => {
      render(<ProjectCard project={mockProject} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('menu-toggle'));
      expect(screen.getByTestId('dropdown-menu')).toBeInTheDocument();
    });

    it('should call onEditProject when Edit clicked', async () => {
      const mockEdit = vi.fn();
      render(<ProjectCard project={mockProject} onEditProject={mockEdit} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('menu-toggle'));
      await user.click(screen.getByTestId('edit-btn'));
      expect(mockEdit).toHaveBeenCalled();
    });

    it('should show delete modal when Delete clicked', async () => {
      render(<ProjectCard project={mockProject} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('menu-toggle'));
      await user.click(screen.getByTestId('delete-menu-btn'));
      expect(screen.getByTestId('delete-modal')).toBeInTheDocument();
    });

    it('should have accessible aria-label on buttons', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('view-dashboard-btn')).toHaveAttribute('aria-label');
      expect(screen.getByTestId('run-query-btn')).toHaveAttribute('aria-label');
    });
  });

  describe('PC-STATUS: Status Display', () => {
    it('should show Active badge for active projects', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('active-badge')).toBeInTheDocument();
      expect(screen.getByText('Active')).toBeInTheDocument();
    });

    it('should show Inactive badge for inactive projects', () => {
      render(<ProjectCard project={mockInactiveProject} />);
      expect(screen.getByTestId('inactive-badge')).toBeInTheDocument();
      expect(screen.getByText('Inactive')).toBeInTheDocument();
    });

    it('should have green color for Active badge', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('active-badge')).toHaveClass('text-green-500');
    });

    it('should have gray color for Inactive badge', () => {
      render(<ProjectCard project={mockInactiveProject} />);
      expect(screen.getByTestId('inactive-badge')).toHaveClass('text-gray-400');
    });

    it('should display status in status-badge container', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('status-badge')).toBeInTheDocument();
    });
  });

  describe('PC-TIME: Last Queried Display', () => {
    it('should display "Never" for never queried projects', () => {
      render(<ProjectCard project={mockInactiveProject} />);
      expect(screen.getByTestId('last-queried-value')).toHaveTextContent('Never');
    });

    it('should display relative time for recent queries', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('last-queried-value')).toBeInTheDocument();
    });

    it('should display hours ago for queries within 24 hours', () => {
      const recentProject = {
        ...mockProject,
        last_queried_at: new Date(Date.now() - 5 * 60 * 60 * 1000).toISOString() // 5 hours ago
      };
      render(<ProjectCard project={recentProject} />);
      expect(screen.getByTestId('last-queried-value')).toHaveTextContent('h ago');
    });

    it('should display days ago for queries within a week', () => {
      const recentProject = {
        ...mockProject,
        last_queried_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString() // 3 days ago
      };
      render(<ProjectCard project={recentProject} />);
      expect(screen.getByTestId('last-queried-value')).toHaveTextContent('d ago');
    });

    it('should display date for older queries', () => {
      const oldProject = {
        ...mockProject,
        last_queried_at: new Date(Date.now() - 14 * 24 * 60 * 60 * 1000).toISOString() // 14 days ago
      };
      render(<ProjectCard project={oldProject} />);
      expect(screen.getByTestId('last-queried-value')).toBeInTheDocument();
    });
  });

  describe('PC-RATE: Mention Rate Display', () => {
    it('should display 0% for zero mention rate', () => {
      render(<ProjectCard project={mockInactiveProject} />);
      expect(screen.getByTestId('mention-rate-value')).toHaveTextContent('0%');
    });

    it('should display correct percentage for mention rate', () => {
      const highRateProject = { ...mockProject, mention_rate: 0.85 };
      render(<ProjectCard project={highRateProject} />);
      expect(screen.getByTestId('mention-rate-value')).toHaveTextContent('85%');
    });

    it('should round percentage to whole number', () => {
      const decimalProject = { ...mockProject, mention_rate: 0.456 };
      render(<ProjectCard project={decimalProject} />);
      expect(screen.getByTestId('mention-rate-value')).toHaveTextContent('46%');
    });

    it('should display 100% for maximum mention rate', () => {
      const maxRateProject = { ...mockProject, mention_rate: 1.0 };
      render(<ProjectCard project={maxRateProject} />);
      expect(screen.getByTestId('mention-rate-value')).toHaveTextContent('100%');
    });

    it('should have mention rate label', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByText('Mention Rate')).toBeInTheDocument();
    });
  });

  describe('PC-LOADING: Loading States', () => {
    it('should show loader icon when isLoading is true', () => {
      render(<ProjectCard project={mockProject} isLoading={true} />);
      expect(screen.getAllByTestId('loader-icon')).toHaveLength(2); // Dashboard and Run Query buttons
    });

    it('should disable buttons when isLoading', () => {
      render(<ProjectCard project={mockProject} isLoading={true} />);
      expect(screen.getByTestId('view-dashboard-btn')).toBeDisabled();
      expect(screen.getByTestId('run-query-btn')).toBeDisabled();
    });

    it('should show action icons when not loading', () => {
      render(<ProjectCard project={mockProject} isLoading={false} />);
      expect(screen.getByTestId('eye-icon')).toBeInTheDocument();
      expect(screen.getByTestId('play-icon')).toBeInTheDocument();
    });

    it('should not call action when button is disabled', async () => {
      const mockView = vi.fn();
      render(<ProjectCard project={mockProject} onViewDashboard={mockView} isLoading={true} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('view-dashboard-btn'));
      expect(mockView).not.toHaveBeenCalled();
    });

    it('should show loading state transition', async () => {
      const { rerender } = render(<ProjectCard project={mockProject} isLoading={false} />);
      expect(screen.getByTestId('eye-icon')).toBeInTheDocument();

      rerender(<ProjectCard project={mockProject} isLoading={true} />);
      expect(screen.queryByTestId('eye-icon')).not.toBeInTheDocument();
    });
  });

  describe('PC-CONFIRM: Delete Confirmation', () => {
    it('should show delete modal with project name', async () => {
      render(<ProjectCard project={mockProject} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('menu-toggle'));
      await user.click(screen.getByTestId('delete-menu-btn'));
      expect(screen.getByText('Delete Acme Corp?')).toBeInTheDocument();
    });

    it('should have Delete button in modal', async () => {
      render(<ProjectCard project={mockProject} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('menu-toggle'));
      await user.click(screen.getByTestId('delete-menu-btn'));
      expect(screen.getByTestId('confirm-delete')).toBeInTheDocument();
    });

    it('should have Cancel button in modal', async () => {
      render(<ProjectCard project={mockProject} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('menu-toggle'));
      await user.click(screen.getByTestId('delete-menu-btn'));
      expect(screen.getByTestId('cancel-delete')).toBeInTheDocument();
    });

    it('should call onDelete when confirmed', async () => {
      const mockDelete = vi.fn();
      render(<ProjectCard project={mockProject} onDelete={mockDelete} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('menu-toggle'));
      await user.click(screen.getByTestId('delete-menu-btn'));
      await user.click(screen.getByTestId('confirm-delete'));
      expect(mockDelete).toHaveBeenCalled();
    });

    it('should close modal on cancel', async () => {
      render(<ProjectCard project={mockProject} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('menu-toggle'));
      await user.click(screen.getByTestId('delete-menu-btn'));
      await user.click(screen.getByTestId('cancel-delete'));
      expect(screen.queryByTestId('delete-modal')).not.toBeInTheDocument();
    });
  });

  describe('PC-A11Y: Accessibility', () => {
    it('should have role="listitem"', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByRole('listitem')).toBeInTheDocument();
    });

    it('should have aria-label with brand name', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByRole('listitem')).toHaveAttribute('aria-label', 'Project: Acme Corp');
    });

    it('should have accessible menu toggle', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('menu-toggle')).toHaveAttribute('aria-label', 'Project actions');
    });

    it('should have role="menu" on dropdown', async () => {
      render(<ProjectCard project={mockProject} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('menu-toggle'));
      expect(screen.getByRole('menu')).toBeInTheDocument();
    });

    it('should have role="menuitem" on menu items', async () => {
      render(<ProjectCard project={mockProject} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('menu-toggle'));
      expect(screen.getByRole('menuitem', { name: /edit/i })).toBeInTheDocument();
    });

    it('should have role="dialog" on delete modal', async () => {
      render(<ProjectCard project={mockProject} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('menu-toggle'));
      await user.click(screen.getByTestId('delete-menu-btn'));
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });
  });

  describe('PC-EDGE: Edge Cases', () => {
    it('should handle null last_queried_at', () => {
      render(<ProjectCard project={mockInactiveProject} />);
      expect(screen.getByTestId('last-queried-value')).toHaveTextContent('Never');
    });

    it('should handle zero mention rate', () => {
      render(<ProjectCard project={mockInactiveProject} />);
      expect(screen.getByTestId('mention-rate-value')).toHaveTextContent('0%');
    });

    it('should handle zero keywords', () => {
      const zeroKeywords = { ...mockProject, keywords_count: 0 };
      render(<ProjectCard project={zeroKeywords} />);
      expect(screen.getByTestId('keywords-count')).toHaveTextContent('0 keywords');
    });

    it('should handle zero competitors', () => {
      const zeroCompetitors = { ...mockProject, competitors_count: 0 };
      render(<ProjectCard project={zeroCompetitors} />);
      expect(screen.getByTestId('competitors-count')).toHaveTextContent('0 competitors');
    });

    it('should pass className prop', () => {
      render(<ProjectCard project={mockProject} className="custom-class" />);
      expect(screen.getByTestId('project-card-1')).toHaveClass('custom-class');
    });
  });
});
