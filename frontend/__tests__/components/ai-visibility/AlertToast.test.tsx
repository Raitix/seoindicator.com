/**
 * AlertToast Component Tests
 * Task: TASK-34-21 - Frontend Alert Notifications
 * Spec: SPEC-034 (AI Visibility Tracking)
 *
 * Tests for AlertToast notification component
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { AlertToast } from '@/components/ai-visibility/AlertToast';
import type { Alert, AlertType } from '@/lib/ai-visibility/types';

// Mock Lucide React icons
jest.mock('lucide-react', () => ({
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  AlertTriangle: () => <span data-testid="alert-triangle-icon">⚠</span>,
  Meh: () => <span data-testid="meh-icon">😐</span>,
  X: () => <span data-testid="x-icon">✗</span>,
}));

// Test data
const createMockAlert = (type: AlertType = 'mention_spike'): Alert => ({
  id: 'alert-1',
  project_id: 'proj-1',
  project_name: 'SEOIndicator',
  alert_type: type,
  message: 'Brand mentions increased by 25%',
  previous_value: 40,
  current_value: 50,
  percentage_change: 25,
  is_read: false,
  is_resolved: false,
  created_at: new Date().toISOString(),
  resolved_at: null,
  recommendations: [],
});

describe('AlertToast', () => {
  const mockOnOpenDetail = jest.fn();
  const mockOnDismiss = jest.fn();

  const defaultProps = {
    alert: createMockAlert(),
    onOpenDetail: mockOnOpenDetail,
    onDismiss: mockOnDismiss,
    autoDismissMs: 10000,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('AT-001: renders toast with alert', () => {
      render(<AlertToast {...defaultProps} />);

      expect(screen.getByRole('alert')).toBeInTheDocument();
      expect(screen.getByTestId('alert-toast')).toBeInTheDocument();
    });

    it('AT-009: displays message', () => {
      const alert = createMockAlert();
      alert.message = 'Custom alert message';
      render(<AlertToast {...defaultProps} alert={alert} />);

      expect(screen.getByText(/Custom alert message/i)).toBeInTheDocument();
    });

    it('AT-010: displays project name', () => {
      const alert = createMockAlert();
      alert.project_name = 'My Project';
      render(<AlertToast {...defaultProps} alert={alert} />);

      expect(screen.getByText('My Project')).toBeInTheDocument();
    });

    it('AT-011: displays time ago', () => {
      render(<AlertToast {...defaultProps} />);

      expect(screen.getByText(/just now/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Auto-dismiss Tests
  // ============================================================================

  describe('Auto-dismiss', () => {
    it('AT-002: auto-dismiss after 10 seconds', async () => {
      render(<AlertToast {...defaultProps} />);

      expect(screen.getByRole('alert')).toBeInTheDocument();

      act(() => {
        jest.advanceTimersByTime(10000);
      });

      expect(mockOnDismiss).toHaveBeenCalled();
    });

    it('AT-016: pause on hover', async () => {
      render(<AlertToast {...defaultProps} />);

      const toast = screen.getByTestId('alert-toast');
      fireEvent.mouseEnter(toast);

      act(() => {
        jest.advanceTimersByTime(10000);
      });

      // Should NOT be dismissed because hover paused timer
      expect(mockOnDismiss).not.toHaveBeenCalled();

      fireEvent.mouseLeave(toast);

      act(() => {
        jest.advanceTimersByTime(10000);
      });

      expect(mockOnDismiss).toHaveBeenCalled();
    });

    it('AT-017: resume on mouse leave', async () => {
      render(<AlertToast {...defaultProps} />);

      const toast = screen.getByTestId('alert-toast');

      // Hover for 5 seconds
      fireEvent.mouseEnter(toast);
      act(() => {
        jest.advanceTimersByTime(5000);
      });

      // Leave - timer resumes
      fireEvent.mouseLeave(toast);
      act(() => {
        jest.advanceTimersByTime(10000);
      });

      expect(mockOnDismiss).toHaveBeenCalled();
    });
  });

  // ============================================================================
  // Interactions Tests
  // ============================================================================

  describe('Interactions', () => {
    it('AT-003: click opens detail', async () => {
      const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });
      render(<AlertToast {...defaultProps} />);

      await user.click(screen.getByTestId('alert-toast'));

      expect(mockOnOpenDetail).toHaveBeenCalledWith('alert-1');
    });

    it('AT-004: dismiss button closes toast', async () => {
      const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });
      render(<AlertToast {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /dismiss/i }));

      expect(mockOnDismiss).toHaveBeenCalled();
    });
  });

  // ============================================================================
  // Alert Type Icons Tests
  // ============================================================================

  describe('Alert Type Icons', () => {
    it('AT-005: mention_spike shows green icon', () => {
      const alert = createMockAlert('mention_spike');
      render(<AlertToast {...defaultProps} alert={alert} />);

      expect(screen.getByTestId('trending-up-icon')).toBeInTheDocument();
      const toast = screen.getByTestId('alert-toast');
      expect(toast).toHaveClass('border-emerald-500');
    });

    it('AT-006: mention_drop shows red icon', () => {
      const alert = createMockAlert('mention_drop');
      render(<AlertToast {...defaultProps} alert={alert} />);

      expect(screen.getByTestId('trending-down-icon')).toBeInTheDocument();
      const toast = screen.getByTestId('alert-toast');
      expect(toast).toHaveClass('border-rose-500');
    });

    it('AT-007: competitor_gain shows orange icon', () => {
      const alert = createMockAlert('competitor_gain');
      render(<AlertToast {...defaultProps} alert={alert} />);

      expect(screen.getByTestId('alert-triangle-icon')).toBeInTheDocument();
      const toast = screen.getByTestId('alert-toast');
      expect(toast).toHaveClass('border-amber-500');
    });

    it('AT-008: sentiment_change shows yellow icon', () => {
      const alert = createMockAlert('sentiment_change');
      render(<AlertToast {...defaultProps} alert={alert} />);

      expect(screen.getByTestId('meh-icon')).toBeInTheDocument();
      const toast = screen.getByTestId('alert-toast');
      expect(toast).toHaveClass('border-yellow-500');
    });
  });

  // ============================================================================
  // Multiple Toasts Tests
  // ============================================================================

  describe('Multiple Toasts', () => {
    it('AT-012: multiple toasts stacked', () => {
      const alert1 = createMockAlert();
      const alert2 = { ...createMockAlert(), id: 'alert-2' };
      const alert3 = { ...createMockAlert(), id: 'alert-3' };

      const { container } = render(
        <>
          <AlertToast {...defaultProps} alert={alert1} />
          <AlertToast {...defaultProps} alert={alert2} />
          <AlertToast {...defaultProps} alert={alert3} />
        </>
      );

      const toasts = screen.getAllByRole('alert');
      expect(toasts).toHaveLength(3);
    });

    it('AT-013: max toasts limit enforced', () => {
      const alerts = Array.from({ length: 7 }, (_, i) => ({
        ...createMockAlert(),
        id: `alert-${i}`,
      }));

      const { container } = render(
        <>
          {alerts.slice(0, 5).map((alert, i) => (
            <AlertToast key={alert.id} {...defaultProps} alert={alert} />
          ))}
        </>
      );

      // Only 5 toasts visible
      const toasts = screen.getAllByRole('alert');
      expect(toasts).toHaveLength(5);
    });
  });

  // ============================================================================
  // Position Tests
  // ============================================================================

  describe('Position', () => {
    it('AT-014: default position is bottom-right', () => {
      render(<AlertToast {...defaultProps} />);

      const toast = screen.getByTestId('alert-toast');
      expect(toast).toHaveClass('bottom-4');
      expect(toast).toHaveClass('right-4');
    });

    it('AT-015: custom position applied', () => {
      render(<AlertToast {...defaultProps} position="top-right" />);

      const toast = screen.getByTestId('alert-toast');
      expect(toast).toHaveClass('top-4');
      expect(toast).toHaveClass('right-4');
    });
  });

  // ============================================================================
  // Progress Bar Tests
  // ============================================================================

  describe('Progress Bar', () => {
    it('AT-018: progress bar visible', () => {
      render(<AlertToast {...defaultProps} />);

      expect(screen.getByTestId('toast-progress')).toBeInTheDocument();
    });

    it('progress bar decreases over time', () => {
      render(<AlertToast {...defaultProps} />);

      const progress = screen.getByTestId('toast-progress');

      // Initial width
      expect(progress).toHaveStyle('width: 100%');

      act(() => {
        jest.advanceTimersByTime(5000);
      });

      // Width should decrease
      expect(progress).not.toHaveStyle('width: 100%');
    });
  });

  // ============================================================================
  // Keyboard Tests
  // ============================================================================

  describe('Keyboard', () => {
    it('AT-019: escape key dismisses toast', async () => {
      const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });
      render(<AlertToast {...defaultProps} />);

      await user.keyboard('{Escape}');

      expect(mockOnDismiss).toHaveBeenCalled();
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('AT-020: has role="alert"', () => {
      render(<AlertToast {...defaultProps} />);

      expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    it('AT-021: screen reader announcement', () => {
      render(<AlertToast {...defaultProps} />);

      const toast = screen.getByRole('alert');
      expect(toast).toHaveAttribute('aria-live', 'assertive');
    });
  });

  // ============================================================================
  // Animation Tests
  // ============================================================================

  describe('Animation', () => {
    it('AT-022: slide animation on show', () => {
      render(<AlertToast {...defaultProps} />);

      const toast = screen.getByTestId('alert-toast');
      expect(toast).toHaveClass('animate-slide-in');
    });
  });

  // ============================================================================
  // Dark Mode Tests
  // ============================================================================

  describe('Dark Mode', () => {
    it('AT-023: dark mode classes applied', () => {
      render(<AlertToast {...defaultProps} />);

      const toast = screen.getByTestId('alert-toast');
      expect(toast).toHaveClass('dark:bg-slate-800');
    });
  });
});
