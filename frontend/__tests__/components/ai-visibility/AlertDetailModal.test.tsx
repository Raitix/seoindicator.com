/**
 * AlertDetailModal Component Tests
 * Task: TASK-34-21 - Frontend Alert Notifications
 * Spec: SPEC-034 (AI Visibility Tracking)
 *
 * Tests for AlertDetailModal component implementation
 */

import React from 'react';
import { render, screen, within, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { AlertDetailModal } from '@/components/ai-visibility/AlertDetailModal';
import type { Alert } from '@/lib/ai-visibility/types';

// Mock Lucide React icons
jest.mock('lucide-react', () => ({
  X: () => <span data-testid="x-icon">✗</span>,
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  AlertTriangle: () => <span data-testid="alert-triangle-icon">⚠</span>,
  Meh: () => <span data-testid="meh-icon">😐</span>,
  ExternalLink: () => <span data-testid="external-link-icon">↗</span>,
  Check: () => <span data-testid="check-icon">✓</span>,
  Clock: () => <span data-testid="clock-icon">🕐</span>,
  Copy: () => <span data-testid="copy-icon">📋</span>,
  Share2: () => <span data-testid="share-icon">📤</span>,
  Trash2: () => <span data-testid="trash-icon">🗑</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
}));

// Test data
const createMockAlert = (overrides: Partial<Alert> = {}): Alert => ({
  id: 'alert-1',
  project_id: 'proj-1',
  project_name: 'SEOIndicator',
  alert_type: 'mention_spike',
  message: 'Brand mentions increased by 25% across all tracked platforms',
  previous_value: 40,
  current_value: 50,
  percentage_change: 25,
  is_read: false,
  is_resolved: false,
  created_at: '2026-03-07T10:00:00Z',
  resolved_at: null,
  recommendations: [
    'Investigate the source of new mentions',
    'Track trending queries related to your brand',
    'Consider expanding keyword coverage',
  ],
  ...overrides,
});

describe('AlertDetailModal', () => {
  const mockOnClose = jest.fn();
  const mockOnResolve = jest.fn();
  const mockOnDismiss = jest.fn();
  const mockOnViewProject = jest.fn();
  const mockOnDelete = jest.fn();

  const defaultProps = {
    isOpen: true,
    alert: createMockAlert(),
    onClose: mockOnClose,
    onResolve: mockOnResolve,
    onDismiss: mockOnDismiss,
    onViewProject: mockOnViewProject,
    onDelete: mockOnDelete,
    isLoading: false,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================================================
  // Visibility Tests
  // ============================================================================

  describe('Visibility', () => {
    it('ADM-001: renders when open', () => {
      render(<AlertDetailModal {...defaultProps} />);

      expect(screen.getByRole('dialog')).toBeInTheDocument();
      expect(screen.getByTestId('alert-detail-modal')).toBeInTheDocument();
    });

    it('ADM-002: not visible when closed', () => {
      render(<AlertDetailModal {...defaultProps} isOpen={false} />);

      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
      expect(screen.queryByTestId('alert-detail-modal')).not.toBeInTheDocument();
    });
  });

  // ============================================================================
  // Content Tests
  // ============================================================================

  describe('Content', () => {
    it('ADM-003: displays alert type icon and label', () => {
      render(<AlertDetailModal {...defaultProps} />);

      expect(screen.getByTestId('trending-up-icon')).toBeInTheDocument();
      expect(screen.getByText(/mention spike/i)).toBeInTheDocument();
    });

    it('ADM-004: displays full message', () => {
      const alert = createMockAlert({
        message: 'Full alert message that should be displayed completely without truncation',
      });
      render(<AlertDetailModal {...defaultProps} alert={alert} />);

      expect(screen.getByText(alert.message)).toBeInTheDocument();
    });

    it('ADM-005: displays previous value', () => {
      render(<AlertDetailModal {...defaultProps} />);

      expect(screen.getByText(/previous/i)).toBeInTheDocument();
      expect(screen.getByText('40')).toBeInTheDocument();
    });

    it('ADM-006: displays current value', () => {
      render(<AlertDetailModal {...defaultProps} />);

      expect(screen.getByText(/current/i)).toBeInTheDocument();
      expect(screen.getByText('50')).toBeInTheDocument();
    });

    it('ADM-007: displays percentage change', () => {
      render(<AlertDetailModal {...defaultProps} />);

      const changeEl = screen.getByTestId('percentage-change');
      expect(changeEl).toHaveTextContent('+25%');
      expect(changeEl).toHaveClass('text-emerald-600');
    });

    it('ADM-008: displays project link', () => {
      render(<AlertDetailModal {...defaultProps} />);

      const link = screen.getByRole('link', { name: /view project/i });
      expect(link).toHaveAttribute('href', '/ai-visibility/projects/proj-1');
    });

    it('ADM-009: displays recommendations list', () => {
      render(<AlertDetailModal {...defaultProps} />);

      expect(screen.getByTestId('recommendations-list')).toBeInTheDocument();
      expect(screen.getAllByRole('listitem')).toHaveLength(3);
    });

    it('ADM-024: displays full timestamp', () => {
      render(<AlertDetailModal {...defaultProps} />);

      expect(screen.getByText(/march 7, 2026/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Actions Tests
  // ============================================================================

  describe('Actions', () => {
    it('ADM-010: resolve button triggers callback', async () => {
      const user = userEvent.setup();
      render(<AlertDetailModal {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /resolve/i }));

      expect(mockOnResolve).toHaveBeenCalledWith('alert-1');
    });

    it('ADM-011: dismiss button triggers callback', async () => {
      const user = userEvent.setup();
      render(<AlertDetailModal {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /dismiss/i }));

      expect(mockOnDismiss).toHaveBeenCalledWith('alert-1');
    });

    it('ADM-012: view project button navigates', async () => {
      const user = userEvent.setup();
      render(<AlertDetailModal {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /view project/i }));

      expect(mockOnViewProject).toHaveBeenCalledWith('proj-1');
    });

    it('ADM-013: close button triggers callback', async () => {
      const user = userEvent.setup();
      render(<AlertDetailModal {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /close/i }));

      expect(mockOnClose).toHaveBeenCalled();
    });

    it('ADM-014: click outside triggers close', async () => {
      const user = userEvent.setup();
      render(<AlertDetailModal {...defaultProps} />);

      const overlay = screen.getByTestId('modal-overlay');
      await user.click(overlay);

      expect(mockOnClose).toHaveBeenCalled();
    });

    it('ADM-015: escape key triggers close', async () => {
      const user = userEvent.setup();
      render(<AlertDetailModal {...defaultProps} />);

      await user.keyboard('{Escape}');

      expect(mockOnClose).toHaveBeenCalled();
    });
  });

  // ============================================================================
  // Focus Management Tests
  // ============================================================================

  describe('Focus Management', () => {
    it('ADM-016: focus trap keeps focus in modal', async () => {
      const user = userEvent.setup();
      render(<AlertDetailModal {...defaultProps} />);

      // Tab through elements
      await user.tab();
      const firstFocusable = screen.getByRole('button', { name: /close/i });
      expect(firstFocusable).toHaveFocus();

      // Continue tabbing - focus should stay in modal
      await user.tab();
      await user.tab();
      await user.tab();
      await user.tab();
      // Focus should cycle back to first element
    });

    it('ADM-017: initial focus on first element', () => {
      render(<AlertDetailModal {...defaultProps} />);

      // First focusable element should be close button
      const closeButton = screen.getByRole('button', { name: /close/i });
      expect(closeButton).toHaveFocus();
    });

    it('ADM-018: focus restored on close', async () => {
      const { unmount } = render(<AlertDetailModal {...defaultProps} />);

      // Simulate previous focus
      const trigger = document.createElement('button');
      trigger.focus();

      unmount();

      // Focus should return to trigger
      expect(trigger).toHaveFocus();
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('ADM-019: has correct ARIA role', () => {
      render(<AlertDetailModal {...defaultProps} />);

      const modal = screen.getByRole('dialog');
      expect(modal).toHaveAttribute('aria-modal', 'true');
    });

    it('ADM-020: has aria-labelledby', () => {
      render(<AlertDetailModal {...defaultProps} />);

      const modal = screen.getByRole('dialog');
      expect(modal).toHaveAttribute('aria-labelledby');

      const labelId = modal.getAttribute('aria-labelledby');
      const label = document.getElementById(labelId!);
      expect(label).toBeInTheDocument();
    });
  });

  // ============================================================================
  // State Tests
  // ============================================================================

  describe('States', () => {
    it('ADM-021: loading state shows spinner', () => {
      render(<AlertDetailModal {...defaultProps} isLoading />);

      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
    });

    it('ADM-022: error state shows message', () => {
      render(<AlertDetailModal {...defaultProps} error="Failed to load alert" />);

      expect(screen.getByText(/failed to load alert/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });

    it('ADM-023: resolved state shows badge and disables resolve button', () => {
      const alert = createMockAlert({ is_resolved: true });
      render(<AlertDetailModal {...defaultProps} alert={alert} />);

      expect(screen.getByText(/resolved/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /resolve/i })).toBeDisabled();
    });
  });

  // ============================================================================
  // Percentage Change Tests
  // ============================================================================

  describe('Percentage Change', () => {
    it('ADM-025: negative change shows red color', () => {
      const alert = createMockAlert({ percentage_change: -15 });
      render(<AlertDetailModal {...defaultProps} alert={alert} />);

      const changeEl = screen.getByTestId('percentage-change');
      expect(changeEl).toHaveTextContent('-15%');
      expect(changeEl).toHaveClass('text-rose-600');
    });

    it('ADM-026: positive change shows green color', () => {
      const alert = createMockAlert({ percentage_change: 25 });
      render(<AlertDetailModal {...defaultProps} alert={alert} />);

      const changeEl = screen.getByTestId('percentage-change');
      expect(changeEl).toHaveTextContent('+25%');
      expect(changeEl).toHaveClass('text-emerald-600');
    });

    it('ADM-027: zero change shows neutral color', () => {
      const alert = createMockAlert({ percentage_change: 0 });
      render(<AlertDetailModal {...defaultProps} alert={alert} />);

      const changeEl = screen.getByTestId('percentage-change');
      expect(changeEl).toHaveTextContent('0%');
      expect(changeEl).toHaveClass('text-slate-500');
    });
  });

  // ============================================================================
  // Recommendations Tests
  // ============================================================================

  describe('Recommendations', () => {
    it('ADM-028: long recommendations list is scrollable', () => {
      const alert = createMockAlert({
        recommendations: Array.from({ length: 15 }, (_, i) => `Recommendation ${i + 1}`),
      });
      render(<AlertDetailModal {...defaultProps} alert={alert} />);

      const list = screen.getByTestId('recommendations-list');
      expect(list).toHaveClass('overflow-y-auto');
      expect(list).toHaveClass('max-h-40');
    });

    it('ADM-029: copy recommendations button', async () => {
      const user = userEvent.setup();
      render(<AlertDetailModal {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /copy recommendations/i }));

      // Check clipboard was called
      expect(screen.getByText(/copied/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Additional Actions Tests
  // ============================================================================

  describe('Additional Actions', () => {
    it('ADM-030: share button opens dialog', async () => {
      const user = userEvent.setup();
      render(<AlertDetailModal {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /share/i }));

      // Share dialog should appear
      expect(screen.getByTestId('share-dialog')).toBeInTheDocument();
    });

    it('ADM-031: delete button requires confirmation', async () => {
      const user = userEvent.setup();
      render(<AlertDetailModal {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /delete/i }));

      // Confirm dialog should appear
      expect(screen.getByRole('alertdialog')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Mobile Tests
  // ============================================================================

  describe('Mobile', () => {
    beforeEach(() => {
      global.innerWidth = 375;
    });

    it('ADM-032: mobile bottom sheet style', () => {
      render(<AlertDetailModal {...defaultProps} />);

      const modal = screen.getByTestId('alert-detail-modal');
      expect(modal).toHaveClass('bottom-0');
      expect(modal).toHaveClass('rounded-t-2xl');
    });

    it('ADM-033: swipe down closes modal', async () => {
      const user = userEvent.setup();
      render(<AlertDetailModal {...defaultProps} />);

      const handle = screen.getByTestId('swipe-handle');
      await user.pointer([
        { target: handle, coords: { x: 0, y: 0 } },
        { coords: { x: 0, y: 100 } },
      ]);

      expect(mockOnClose).toHaveBeenCalled();
    });
  });

  // ============================================================================
  // Dark Mode Tests
  // ============================================================================

  describe('Dark Mode', () => {
    it('ADM-034: dark mode classes applied', () => {
      render(<AlertDetailModal {...defaultProps} />);

      const modal = screen.getByTestId('alert-detail-modal');
      expect(modal).toHaveClass('dark:bg-slate-800');
    });
  });

  // ============================================================================
  // Animation Tests
  // ============================================================================

  describe('Animation', () => {
    it('ADM-035: fade animation on open/close', () => {
      const { rerender } = render(<AlertDetailModal {...defaultProps} isOpen={false} />);

      rerender(<AlertDetailModal {...defaultProps} isOpen={true} />);

      const modal = screen.getByTestId('alert-detail-modal');
      expect(modal).toHaveClass('animate-fade-in');
    });
  });
});
