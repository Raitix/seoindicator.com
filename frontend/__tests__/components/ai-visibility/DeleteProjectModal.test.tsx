/**
 * Tests for DeleteProjectModal Component
 * Task: TASK-34-8
 * Feature: SPEC-034 - AI Visibility Tracking
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';

// Import actual component
import { DeleteProjectModal } from '@/components/ai-visibility/DeleteProjectModal';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  AlertTriangle: () => <span data-testid="alert-triangle-icon" />,
  Trash2: () => <span data-testid="trash-icon" />,
  X: () => <span data-testid="x-icon" />,
  Loader2: () => <span data-testid="loader-icon" />
}));

// Mock cn utility
vi.mock('@/lib/utils', () => ({
  cn: (...classes: any[]) => classes.filter(Boolean).join(' ')
}));

describe('DeleteProjectModal Component', () => {
  const user = userEvent.setup();
  const mockOnClose = vi.fn();
  const mockOnConfirm = vi.fn();

  const defaultProps = {
    isOpen: true,
    onClose: mockOnClose,
    onConfirm: mockOnConfirm,
    projectName: 'Test Project',
    isLoading: false
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('DM-RENDER: Basic Rendering', () => {
    it('should render when open', () => {
      render(<DeleteProjectModal {...defaultProps} />);
      expect(screen.getByTestId('delete-modal')).toBeInTheDocument();
    });

    it('should not render when closed', () => {
      render(<DeleteProjectModal {...defaultProps} isOpen={false} />);
      expect(screen.queryByTestId('delete-modal')).not.toBeInTheDocument();
    });

    it('should show project name in message', () => {
      render(<DeleteProjectModal {...defaultProps} />);
      expect(screen.getByText(/Test Project/)).toBeInTheDocument();
    });

    it('should have delete confirmation title', () => {
      render(<DeleteProjectModal {...defaultProps} />);
      expect(screen.getByText('Delete Project')).toBeInTheDocument();
    });

    it('should have warning icon', () => {
      render(<DeleteProjectModal {...defaultProps} />);
      expect(screen.getByTestId('alert-triangle-icon')).toBeInTheDocument();
    });
  });

  describe('DM-ACTIONS: Action Buttons', () => {
    it('should have Cancel button', () => {
      render(<DeleteProjectModal {...defaultProps} />);
      expect(screen.getByTestId('cancel-button')).toBeInTheDocument();
    });

    it('should have Delete button', () => {
      render(<DeleteProjectModal {...defaultProps} />);
      expect(screen.getByTestId('confirm-button')).toBeInTheDocument();
    });

    it('should call onClose when Cancel clicked', async () => {
      render(<DeleteProjectModal {...defaultProps} />);

      const cancelButton = screen.getByTestId('cancel-button');
      await user.click(cancelButton);

      expect(mockOnClose).toHaveBeenCalledTimes(1);
    });

    it('should call onConfirm when Delete clicked', async () => {
      mockOnConfirm.mockResolvedValueOnce(undefined);
      render(<DeleteProjectModal {...defaultProps} />);

      const confirmButton = screen.getByTestId('confirm-button');
      await user.click(confirmButton);

      expect(mockOnConfirm).toHaveBeenCalledTimes(1);
    });
  });

  describe('DM-LOADING: Loading States', () => {
    it('should show loading state on confirm button', () => {
      render(<DeleteProjectModal {...defaultProps} isLoading={true} />);
      expect(screen.getByText('Deleting...')).toBeInTheDocument();
    });

    it('should disable buttons during loading', () => {
      render(<DeleteProjectModal {...defaultProps} isLoading={true} />);

      expect(screen.getByTestId('cancel-button')).toBeDisabled();
      expect(screen.getByTestId('confirm-button')).toBeDisabled();
    });

    it('should show loader icon during loading', () => {
      render(<DeleteProjectModal {...defaultProps} isLoading={true} />);
      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
    });
  });

  describe('DM-A11Y: Accessibility', () => {
    it('should have role="dialog"', () => {
      render(<DeleteProjectModal {...defaultProps} />);
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('should have aria-modal="true"', () => {
      render(<DeleteProjectModal {...defaultProps} />);
      expect(screen.getByRole('dialog')).toHaveAttribute('aria-modal', 'true');
    });

    it('should have aria-labelledby', () => {
      render(<DeleteProjectModal {...defaultProps} />);
      expect(screen.getByRole('dialog')).toHaveAttribute('aria-labelledby', 'modal-title');
    });

    it('should have aria-describedby', () => {
      render(<DeleteProjectModal {...defaultProps} />);
      expect(screen.getByRole('dialog')).toHaveAttribute('aria-describedby', 'modal-description');
    });
  });

  describe('DM-KEYBOARD: Keyboard Interactions', () => {
    it('should close on Escape key', async () => {
      render(<DeleteProjectModal {...defaultProps} />);

      await user.keyboard('{Escape}');

      expect(mockOnClose).toHaveBeenCalledTimes(1);
    });

    it('should not close on Escape when loading', async () => {
      render(<DeleteProjectModal {...defaultProps} isLoading={true} />);

      await user.keyboard('{Escape}');

      expect(mockOnClose).not.toHaveBeenCalled();
    });
  });

  describe('DM-OVERLAY: Overlay Click', () => {
    it('should have overlay backdrop', () => {
      render(<DeleteProjectModal {...defaultProps} />);
      expect(screen.getByTestId('modal-overlay')).toBeInTheDocument();
    });
  });

  describe('DM-ERROR: Error Handling', () => {
    it('should handle onConfirm rejection', async () => {
      mockOnConfirm.mockRejectedValueOnce(new Error('Delete failed'));

      render(<DeleteProjectModal {...defaultProps} />);

      const confirmButton = screen.getByTestId('confirm-button');
      await user.click(confirmButton);

      // Modal should still be open after error
      expect(screen.getByTestId('delete-modal')).toBeInTheDocument();
    });
  });
});
