/**
 * Tests for EmptyProjectsState Component
 * Task: TASK-34-8
 * Feature: SPEC-034 - AI Visibility Tracking
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';

// Import actual component
import { EmptyProjectsState } from '@/components/ai-visibility/EmptyProjectsState';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  FolderPlus: () => <span data-testid="folder-plus-icon" />
}));

// Mock cn utility
vi.mock('@/lib/utils', () => ({
  cn: (...classes: any[]) => classes.filter(Boolean).join(' ')
}));

describe('EmptyProjectsState Component', () => {
  const user = userEvent.setup();
  const mockOnCreateProject = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('EPS-RENDER: Basic Rendering', () => {
    it('should render the empty state', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
    });

    it('should show "No projects yet" message', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByText('No projects yet')).toBeInTheDocument();
    });

    it('should show illustration', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByTestId('illustration')).toBeInTheDocument();
    });

    it('should show folder plus icon', () => {
      render(<EmptyProjectsState />);
      expect(screen.getAllByTestId('folder-plus-icon').length).toBeGreaterThan(0);
    });
  });

  describe('EPS-CTA: Call to Action', () => {
    it('should have create project button', () => {
      render(<EmptyProjectsState onCreateProject={mockOnCreateProject} />);
      expect(screen.getByTestId('create-project-btn')).toBeInTheDocument();
    });

    it('should call onCreateProject when button clicked', async () => {
      render(<EmptyProjectsState onCreateProject={mockOnCreateProject} />);

      const createBtn = screen.getByTestId('create-project-btn');
      await user.click(createBtn);

      expect(mockOnCreateProject).toHaveBeenCalledTimes(1);
    });

    it('should show button text', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByText('Create your first project')).toBeInTheDocument();
    });
  });

  describe('EPS-HELP: Help Text', () => {
    it('should show free tier info', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByText(/Free tier includes/)).toBeInTheDocument();
    });
  });

  describe('EPS-A11Y: Accessibility', () => {
    it('should have role="status"', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByRole('status')).toBeInTheDocument();
    });

    it('should have aria-live="polite"', () => {
      render(<EmptyProjectsState />);
      expect(screen.getByRole('status')).toHaveAttribute('aria-live', 'polite');
    });

    it('should have aria-label on button', () => {
      render(<EmptyProjectsState onCreateProject={mockOnCreateProject} />);
      expect(screen.getByLabelText('Create your first project')).toBeInTheDocument();
    });
  });

  describe('EPS-STYLE: Styling', () => {
    it('should apply className prop', () => {
      render(<EmptyProjectsState className="custom-class" />);
      const container = screen.getByTestId('empty-state');
      expect(container).toHaveClass('custom-class');
    });
  });
});
