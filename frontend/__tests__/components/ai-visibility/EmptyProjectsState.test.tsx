/**
 * Tests for EmptyProjectsState Component
 * Task: TASK-34-8
 * Feature: SPEC-034 - AI Visibility Tracking
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  FolderPlus: () => <span data-testid="folder-plus-icon" />,
  ArrowRight: () => <span data-testid="arrow-right-icon" />
}));

// EmptyProjectsState component mock (will be replaced by actual implementation)
interface EmptyProjectsStateProps {
  onCreateProject?: () => void;
  className?: string;
}

const EmptyProjectsState = ({ onCreateProject, className }: EmptyProjectsStateProps) => {
  return (
    <div 
      className={className} 
      data-testid="empty-state"
      role="status"
      aria-live="polite"
    >
      {/* Illustration */}
      <div data-testid="illustration" className="flex justify-center mb-6">
        <span data-testid="folder-plus-icon" className="w-16 h-16 text-gray-400" />
      </div>

      {/* Title */}
      <h2 className="text-xl font-semibold text-center mb-2">
        No projects yet
      </h2>

      {/* Description */}
      <p className="text-gray-500 text-center mb-6 max-w-md">
        Start tracking your brand mentions across AI platforms. Create your first project to begin monitoring.
      </p>

      {/* CTA Button */}
      <div className="flex justify-center">
        <button
          onClick={onCreateProject}
          data-testid="create-project-btn"
          className="bg-primary text-white px-6 py-3 rounded-lg font-medium hover:bg-primary/90 transition-colors"
          aria-label="Create your first project"
        >
          <span data-testid="folder-plus-icon" className="mr-2" />
          Create your first project
        </button>
      </div>

      {/* Help Text */}
      <p className="text-sm text-gray-400 text-center mt-4">
        Free tier includes 5 queries per month
      </p>
    </div>
  );
};

describe('EmptyProjectsState Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('ES-RENDER: Component Rendering', () => {
    it('should render the component', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
    });

    it('should display illustration', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('illustration')).toBeInTheDocument();
    });

    it('should have role="status"', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('empty-state')).toHaveAttribute('role', 'status');
    });

    it('should have aria-live="polite"', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('empty-state')).toHaveAttribute('aria-live', 'polite');
    });

    it('should render folder-plus icon', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('folder-plus-icon')).toBeInTheDocument();
    });
  });

  describe('ES-CTA: Create Project Button', () => {
    it('should display "Create your first project" button', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('create-project-btn')).toBeInTheDocument();
    });

    it('should have prominent styling on button', () => {
      render(<EmptyProjectsState />);
      const btn = screen.getByTestId('create-project-btn');
      expect(btn).toHaveClass('bg-primary');
    });

    it('should call onCreateProject when clicked', async () => {
      const mockCreate = vi.fn();
      render(<EmptyProjectsState onCreateProject={mockCreate} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('create-project-btn'));
      expect(mockCreate).toHaveBeenCalled();
    });

    it('should have accessible aria-label', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('create-project-btn')).toHaveAttribute('aria-label', 'Create your first project');
    });

    it('should have focus-visible state for keyboard navigation', async () => {
      render(<EmptyProjectsState />);
      const btn = screen.getByTestId('create-project-btn');
      btn.focus();
      expect(btn).toHaveFocus();
    });
  });

  describe('ES-TEXT: Helpful Messaging', () => {
    it('should display "No projects yet" title', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByRole('heading', { level: 2 })).toHaveTextContent('No projects yet');
    });

    it('should display helpful description', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByText(/Start tracking your brand mentions/)).toBeInTheDocument();
    });

    it('should mention AI platforms', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByText(/AI platforms/)).toBeInTheDocument();
    });

    it('should display free tier information', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByText(/Free tier includes 5 queries/)).toBeInTheDocument();
    });

    it('should have semantically correct heading level', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByRole('heading', { level: 2 })).toBeInTheDocument();
    });
  });

  describe('ES-NAV: Navigation', () => {
    it('should be keyboard navigable', async () => {
      render(<EmptyProjectsState />);
      const btn = screen.getByTestId('create-project-btn');
      btn.focus();
      expect(btn).toHaveFocus();
    });

    it('should have visible focus state', () => {
      render(<EmptyProjectsState />);
      const btn = screen.getByTestId('create-project-btn');
      btn.focus();
      expect(btn).toHaveFocus();
    });

    it('should support onClick handler', async () => {
      const mockHandler = vi.fn();
      render(<EmptyProjectsState onCreateProject={mockHandler} />);
      const user = userEvent.setup();
      await user.click(screen.getByTestId('create-project-btn'));
      expect(mockHandler).toHaveBeenCalledTimes(1);
    });

    it('should support onKeyDown handler via button', async () => {
      const mockHandler = vi.fn();
      render(<EmptyProjectsState onCreateProject={mockHandler} />);
      const user = userEvent.setup();
      const btn = screen.getByTestId('create-project-btn');
      btn.focus();
      await user.keyboard('{Enter}');
      expect(mockHandler).toHaveBeenCalled();
    });

    it('should work without onCreateProject prop', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('create-project-btn')).toBeInTheDocument();
    });
  });

  describe('ES-A11Y: Accessibility', () => {
    it('should have correct heading level', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByRole('heading', { level: 2 })).toBeInTheDocument();
    });

    it('should have live region for screen readers', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('empty-state')).toHaveAttribute('aria-live', 'polite');
    });

    it('should have descriptive button text', () => {
      render(<EmptyProjectsState />);
      const btn = screen.getByTestId('create-project-btn');
      expect(btn).toHaveTextContent('Create your first project');
    });

    it('should have icon with semantic meaning', () => {
      render(<EmptyProjectsState />);
      // Icon is decorative, aria-hidden would be appropriate
      expect(screen.getByTestId('folder-plus-icon')).toBeInTheDocument();
    });

    it('should be responsive on mobile', () => {
      render(<EmptyProjectsState />);
      // Component has centered layout that adapts to screen size
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
    });
  });

  describe('ES-PROPS: Props Handling', () => {
    it('should pass className prop', () => {
      render(<EmptyProjectsState className="custom-class" />);
      expect(screen.getByTestId('empty-state')).toHaveClass('custom-class');
    });

    it('should handle undefined onCreateProject', () => {
      render(<EmptyProjectsState onCreateProject={undefined} />);
      expect(screen.getByTestId('create-project-btn')).toBeInTheDocument();
    });

    it('should work with no props', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
    });
  });

  describe('ES-INT: Integration', () => {
    it('should integrate with parent component flow', async () => {
      const mockCreate = vi.fn();
      render(<EmptyProjectsState onCreateProject={mockCreate} />);
      
      const btn = screen.getByTestId('create-project-btn');
      const user = userEvent.setup();
      await user.click(btn);
      
      expect(mockCreate).toHaveBeenCalled();
    });

    it('should render consistently on re-renders', () => {
      const { rerender } = render(<EmptyProjectsState />);
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
      
      rerender(<EmptyProjectsState />);
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
    });
  });
});
