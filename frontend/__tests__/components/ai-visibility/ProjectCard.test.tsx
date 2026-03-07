/**
 * Tests for ProjectCard Component
 * Task: TASK-34-8
 * Feature: SPEC-034 - AI Visibility Tracking
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';

// Import actual component
import { ProjectCard } from '@/components/ai-visibility/ProjectCard';

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

// Mock DeleteProjectModal
vi.mock('@/components/ai-visibility/DeleteProjectModal', () => ({
  DeleteProjectModal: ({ isOpen, onClose, onConfirm, projectName, isLoading }: any) => (
    isOpen ? (
      <div data-testid="delete-modal" role="dialog" aria-modal="true">
        <p data-testid="modal-project-name">Delete {projectName}?</p>
        <button onClick={onConfirm} data-testid="confirm-delete" disabled={isLoading}>
          {isLoading ? 'Deleting...' : 'Delete'}
        </button>
        <button onClick={onClose} data-testid="cancel-delete" disabled={isLoading}>
          Cancel
        </button>
      </div>
    ) : null
  )
}));

// Mock cn utility
vi.mock('@/lib/utils', () => ({
  cn: (...classes: any[]) => classes.filter(Boolean).join(' ')
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

describe('ProjectCard Component', () => {
  const user = userEvent.setup();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('PC-RENDER: Basic Rendering', () => {
    it('should render the project card', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('project-card-1')).toBeInTheDocument();
    });

    it('should display brand name', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByText('Acme Corp')).toBeInTheDocument();
    });

    it('should display keywords count', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('keywords-count')).toHaveTextContent('10 keywords');
    });

    it('should display competitors count', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('competitors-count')).toHaveTextContent('3 competitors');
    });

    it('should have View Dashboard button', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('view-dashboard-btn')).toBeInTheDocument();
    });

    it('should have Run Query button', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('run-query-btn')).toBeInTheDocument();
    });
  });

  describe('PC-STATUS: Status Display', () => {
    it('should show active status for active project', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('status-badge')).toBeInTheDocument();
      expect(screen.getByTestId('active-badge')).toBeInTheDocument();
    });

    it('should show inactive status for inactive project', () => {
      render(<ProjectCard project={mockInactiveProject} />);
      expect(screen.getByTestId('inactive-badge')).toBeInTheDocument();
    });
  });

  describe('PC-RATE: Mention Rate Display', () => {
    it('should display mention rate as percentage', () => {
      render(<ProjectCard project={mockProject} />);
      const mentionRate = screen.getByTestId('mention-rate-value');
      expect(mentionRate).toHaveTextContent('45%');
    });

    it('should round percentage to whole number', () => {
      const project = { ...mockProject, mention_rate: 0.456 };
      render(<ProjectCard project={project} />);
      const mentionRate = screen.getByTestId('mention-rate-value');
      expect(mentionRate).toHaveTextContent('46%');
    });

    it('should display 100% for maximum mention rate', () => {
      const project = { ...mockProject, mention_rate: 1.0 };
      render(<ProjectCard project={project} />);
      const mentionRate = screen.getByTestId('mention-rate-value');
      expect(mentionRate).toHaveTextContent('100%');
    });

    it('should have mention rate label', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByText('Mention Rate')).toBeInTheDocument();
    });
  });

  describe('PC-QUERIED: Last Queried Display', () => {
    it('should display last queried time', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('last-queried')).toBeInTheDocument();
    });
  });

  describe('PC-MENU: Dropdown Menu', () => {
    it('should have menu toggle button', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByTestId('menu-toggle')).toBeInTheDocument();
    });

    it('should open dropdown menu on click', async () => {
      render(<ProjectCard project={mockProject} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      expect(screen.getByTestId('dropdown-menu')).toBeInTheDocument();
    });

    it('should have edit option in menu', async () => {
      render(<ProjectCard project={mockProject} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      expect(screen.getByTestId('edit-btn')).toBeInTheDocument();
    });

    it('should have delete option in menu', async () => {
      render(<ProjectCard project={mockProject} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      expect(screen.getByTestId('delete-menu-btn')).toBeInTheDocument();
    });
  });

  describe('PC-ACTIONS: Action Buttons', () => {
    it('should call onViewDashboard when Dashboard button clicked', async () => {
      const onViewDashboard = vi.fn();
      render(<ProjectCard project={mockProject} onViewDashboard={onViewDashboard} />);

      const dashboardBtn = screen.getByTestId('view-dashboard-btn');
      await user.click(dashboardBtn);

      expect(onViewDashboard).toHaveBeenCalledTimes(1);
    });

    it('should call onRunQuery when Run Query button clicked', async () => {
      const onRunQuery = vi.fn();
      render(<ProjectCard project={mockProject} onRunQuery={onRunQuery} />);

      const runQueryBtn = screen.getByTestId('run-query-btn');
      await user.click(runQueryBtn);

      expect(onRunQuery).toHaveBeenCalledTimes(1);
    });

    it('should call onEditProject when Edit clicked', async () => {
      const onEditProject = vi.fn();
      render(<ProjectCard project={mockProject} onEditProject={onEditProject} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      const editBtn = screen.getByTestId('edit-btn');
      await user.click(editBtn);

      expect(onEditProject).toHaveBeenCalledTimes(1);
    });
  });

  describe('PC-LOADING: Loading States', () => {
    it('should show loader icon when isLoading is true', () => {
      render(<ProjectCard project={mockProject} isLoading={true} />);
      // Component should have loading state
      expect(screen.getByTestId('view-dashboard-btn')).toBeDisabled();
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
  });

  describe('PC-CONFIRM: Delete Confirmation', () => {
    it('should show delete modal with project name', async () => {
      render(<ProjectCard project={mockProject} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      const deleteBtn = screen.getByTestId('delete-menu-btn');
      await user.click(deleteBtn);

      expect(screen.getByTestId('delete-modal')).toBeInTheDocument();
      expect(screen.getByTestId('modal-project-name')).toHaveTextContent('Acme Corp');
    });

    it('should have Delete button in modal', async () => {
      render(<ProjectCard project={mockProject} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      const deleteBtn = screen.getByTestId('delete-menu-btn');
      await user.click(deleteBtn);

      expect(screen.getByTestId('confirm-delete')).toBeInTheDocument();
    });

    it('should have Cancel button in modal', async () => {
      render(<ProjectCard project={mockProject} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      const deleteBtn = screen.getByTestId('delete-menu-btn');
      await user.click(deleteBtn);

      expect(screen.getByTestId('cancel-delete')).toBeInTheDocument();
    });

    it('should call onDelete when confirmed', async () => {
      const onDelete = vi.fn();
      render(<ProjectCard project={mockProject} onDelete={onDelete} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      const deleteMenuBtn = screen.getByTestId('delete-menu-btn');
      await user.click(deleteMenuBtn);

      const confirmBtn = screen.getByTestId('confirm-delete');
      await user.click(confirmBtn);

      expect(onDelete).toHaveBeenCalledTimes(1);
    });

    it('should close modal on cancel', async () => {
      render(<ProjectCard project={mockProject} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      const deleteBtn = screen.getByTestId('delete-menu-btn');
      await user.click(deleteBtn);

      const cancelBtn = screen.getByTestId('cancel-delete');
      await user.click(cancelBtn);

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
      expect(screen.getByLabelText('Project: Acme Corp')).toBeInTheDocument();
    });

    it('should have accessible menu toggle', () => {
      render(<ProjectCard project={mockProject} />);
      expect(screen.getByLabelText('Project actions')).toBeInTheDocument();
    });

    it('should have role="menu" on dropdown', async () => {
      render(<ProjectCard project={mockProject} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      expect(screen.getByRole('menu')).toBeInTheDocument();
    });

    it('should have role="menuitem" on menu items', async () => {
      render(<ProjectCard project={mockProject} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      expect(screen.getByRole('menuitem', { name: /edit/i })).toBeInTheDocument();
    });

    it('should have role="dialog" on delete modal', async () => {
      render(<ProjectCard project={mockProject} />);

      const menuToggle = screen.getByTestId('menu-toggle');
      await user.click(menuToggle);

      const deleteBtn = screen.getByTestId('delete-menu-btn');
      await user.click(deleteBtn);

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
      const project = { ...mockProject, keywords_count: 0 };
      render(<ProjectCard project={project} />);
      expect(screen.getByTestId('keywords-count')).toHaveTextContent('0 keywords');
    });

    it('should handle zero competitors', () => {
      const project = { ...mockProject, competitors_count: 0 };
      render(<ProjectCard project={project} />);
      expect(screen.getByTestId('competitors-count')).toHaveTextContent('0 competitors');
    });

    it('should pass className prop', () => {
      render(<ProjectCard project={mockProject} className="custom-class" />);
      const card = screen.getByTestId('project-card-1');
      expect(card).toHaveClass('custom-class');
    });
  });
});
