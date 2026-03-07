/**
 * AlertNotification Component Tests
 * Task: TASK-34-21 - Frontend Alert Notifications
 * Spec: SPEC-034 (AI Visibility Tracking)
 *
 * Tests for AlertNotification component implementation
 */

import React from 'react';
import { render, screen, within, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { AlertNotification } from '@/components/ai-visibility/AlertNotification';
import type { Alert, AlertType } from '@/lib/ai-visibility/types';

// Mock Lucide React icons
jest.mock('lucide-react', () => ({
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  AlertTriangle: () => <span data-testid="alert-triangle-icon">⚠</span>,
  Meh: () => <span data-testid="meh-icon">😐</span>,
  Check: () => <span data-testid="check-icon">✓</span>,
  X: () => <span data-testid="x-icon">✗</span>,
  Clock: () => <span data-testid="clock-icon">🕐</span>,
  ChevronRight: () => <span data-testid="chevron-icon">›</span>,
}));

// Test data
const createMockAlert = (overrides: Partial<Alert> = {}): Alert => ({
  id: 'alert-1',
  project_id: 'proj-1',
  project_name: 'SEOIndicator',
  alert_type: 'mention_spike',
  message: 'Brand mentions increased by 25%',
  previous_value: 40,
  current_value: 50,
  percentage_change: 25,
  is_read: false,
  is_resolved: false,
  created_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(), // 2 hours ago
  resolved_at: null,
  recommendations: ['Investigate source', 'Track trending queries'],
  ...overrides,
});

describe('AlertNotification', () => {
  const mockOnClick = jest.fn();
  const mockOnMarkRead = jest.fn();
  const mockOnResolve = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('AN-001: renders with mention_spike type (green)', () => {
      const alert = createMockAlert({ alert_type: 'mention_spike' });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveClass('border-emerald-200');
      expect(card).toHaveClass('bg-emerald-50');
      expect(screen.getByTestId('trending-up-icon')).toBeInTheDocument();
    });

    it('AN-002: renders with mention_drop type (red)', () => {
      const alert = createMockAlert({ alert_type: 'mention_drop' });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveClass('border-rose-200');
      expect(card).toHaveClass('bg-rose-50');
      expect(screen.getByTestId('trending-down-icon')).toBeInTheDocument();
    });

    it('AN-003: renders with competitor_gain type (orange)', () => {
      const alert = createMockAlert({ alert_type: 'competitor_gain' });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveClass('border-amber-200');
      expect(card).toHaveClass('bg-amber-50');
      expect(screen.getByTestId('alert-triangle-icon')).toBeInTheDocument();
    });

    it('AN-004: renders with sentiment_change type (yellow)', () => {
      const alert = createMockAlert({ alert_type: 'sentiment_change' });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveClass('border-yellow-200');
      expect(card).toHaveClass('bg-yellow-50');
      expect(screen.getByTestId('meh-icon')).toBeInTheDocument();
    });

    it('AN-005: displays project name', () => {
      const alert = createMockAlert({ project_name: 'My Test Project' });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      expect(screen.getByText('My Test Project')).toBeInTheDocument();
    });

    it('AN-006: displays message', () => {
      const alert = createMockAlert({ message: 'Test alert message' });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      expect(screen.getByText('Test alert message')).toBeInTheDocument();
    });

    it('AN-007: displays time ago', () => {
      const alert = createMockAlert({
        created_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
      });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      expect(screen.getByText(/2 hours ago/i)).toBeInTheDocument();
    });

    it('AN-008: shows unread indicator for unread alert', () => {
      const alert = createMockAlert({ is_read: false });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      expect(screen.getByTestId('unread-indicator')).toBeInTheDocument();
    });

    it('AN-009: no unread indicator for read alert', () => {
      const alert = createMockAlert({ is_read: true });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      expect(screen.queryByTestId('unread-indicator')).not.toBeInTheDocument();
    });

    it('AN-010: shows resolved badge for resolved alert', () => {
      const alert = createMockAlert({ is_resolved: true });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      expect(screen.getByText(/resolved/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Interaction Tests
  // ============================================================================

  describe('Interactions', () => {
    it('AN-011: click triggers onClick', async () => {
      const user = userEvent.setup();
      const alert = createMockAlert();
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      await user.click(screen.getByTestId('alert-notification'));

      expect(mockOnClick).toHaveBeenCalledWith(alert.id);
    });

    it('AN-012: mark as read button triggers onMarkRead', async () => {
      const user = userEvent.setup();
      const alert = createMockAlert({ is_read: false });
      render(
        <AlertNotification
          alert={alert}
          onClick={mockOnClick}
          onMarkRead={mockOnMarkRead}
        />
      );

      const markReadBtn = screen.getByRole('button', { name: /mark as read/i });
      await user.click(markReadBtn);

      expect(mockOnMarkRead).toHaveBeenCalledWith(alert.id);
      expect(mockOnClick).not.toHaveBeenCalled();
    });

    it('AN-013: resolve button triggers onResolve', async () => {
      const user = userEvent.setup();
      const alert = createMockAlert({ is_resolved: false });
      render(
        <AlertNotification
          alert={alert}
          onClick={mockOnClick}
          onResolve={mockOnResolve}
        />
      );

      const resolveBtn = screen.getByRole('button', { name: /resolve/i });
      await user.click(resolveBtn);

      expect(mockOnResolve).toHaveBeenCalledWith(alert.id);
      expect(mockOnClick).not.toHaveBeenCalled();
    });

    it('AN-014: keyboard navigation - Enter triggers click', async () => {
      const user = userEvent.setup();
      const alert = createMockAlert();
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const card = screen.getByTestId('alert-notification');
      card.focus();
      await user.keyboard('{Enter}');

      expect(mockOnClick).toHaveBeenCalledWith(alert.id);
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('AN-015: has correct ARIA role', () => {
      const alert = createMockAlert();
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveAttribute('role', 'button');
      expect(card).toHaveAttribute('tabIndex', '0');
    });

    it('AN-016: has aria-label with alert info', () => {
      const alert = createMockAlert({
        alert_type: 'mention_spike',
        project_name: 'Test Project',
        is_read: false,
      });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveAttribute('aria-label', expect.stringContaining('Test Project'));
      expect(card).toHaveAttribute('aria-label', expect.stringContaining('unread'));
    });
  });

  // ============================================================================
  // Percentage Change Tests
  // ============================================================================

  describe('Percentage Change', () => {
    it('AN-017: positive percentage change displayed with plus', () => {
      const alert = createMockAlert({ percentage_change: 25 });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const changeEl = screen.getByTestId('percentage-change');
      expect(changeEl).toHaveTextContent('+25%');
      expect(changeEl).toHaveClass('text-emerald-600');
    });

    it('AN-018: negative percentage change displayed with minus', () => {
      const alert = createMockAlert({ percentage_change: -15 });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const changeEl = screen.getByTestId('percentage-change');
      expect(changeEl).toHaveTextContent('-15%');
      expect(changeEl).toHaveClass('text-rose-600');
    });

    it('AN-035: zero percentage change in neutral color', () => {
      const alert = createMockAlert({ percentage_change: 0 });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const changeEl = screen.getByTestId('percentage-change');
      expect(changeEl).toHaveTextContent('0%');
      expect(changeEl).toHaveClass('text-slate-500');
    });

    it('AN-036: large percentage change formatted correctly', () => {
      const alert = createMockAlert({ percentage_change: 500 });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const changeEl = screen.getByTestId('percentage-change');
      expect(changeEl).toHaveTextContent('+500%');
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases', () => {
    it('AN-019: long message truncated with ellipsis', () => {
      const longMessage = 'A'.repeat(150);
      const alert = createMockAlert({ message: longMessage });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const messageEl = screen.getByTestId('alert-message');
      expect(messageEl.textContent!.length).toBeLessThan(longMessage.length);
    });

    it('AN-020: loading state shows skeleton', () => {
      render(<AlertNotification isLoading onClick={mockOnClick} />);

      expect(screen.getByTestId('alert-skeleton')).toBeInTheDocument();
      expect(screen.queryByTestId('alert-notification')).not.toBeInTheDocument();
    });

    it('AN-023: has data attributes', () => {
      const alert = createMockAlert({ id: 'alert-123', alert_type: 'mention_spike' });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveAttribute('data-alert-id', 'alert-123');
      expect(card).toHaveAttribute('data-alert-type', 'mention_spike');
    });

    it('AN-024: empty recommendations - no section shown', () => {
      const alert = createMockAlert({ recommendations: [] });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      expect(screen.queryByTestId('recommendations-preview')).not.toBeInTheDocument();
    });

    it('AN-025: multiple recommendations shown in preview', () => {
      const alert = createMockAlert({
        recommendations: ['First', 'Second', 'Third'],
      });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const preview = screen.getByTestId('recommendations-preview');
      expect(within(preview).getByText('First')).toBeInTheDocument();
      expect(within(preview).getByText('Second')).toBeInTheDocument();
    });

    it('AN-026: custom className applied', () => {
      const alert = createMockAlert();
      render(
        <AlertNotification
          alert={alert}
          onClick={mockOnClick}
          className="custom-class"
        />
      );

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveClass('custom-class');
    });

    it('AN-037: unicode in message rendered correctly', () => {
      const alert = createMockAlert({ message: 'Brand 🚀 mentions increased! 日本語' });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      expect(screen.getByText(/Brand 🚀 mentions increased!/)).toBeInTheDocument();
    });

    it('AN-038: XSS prevention - script not executed', () => {
      const alert = createMockAlert({
        message: '<script>alert("xss")</script>Normal text',
      });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      // Script tag should be escaped, not executed
      expect(screen.queryByText(/alert\("xss"\)/)).not.toBeInTheDocument();
      expect(screen.getByText(/Normal text/)).toBeInTheDocument();
    });

    it('AN-039: long project name truncated', () => {
      const longName = 'A'.repeat(50);
      const alert = createMockAlert({ project_name: longName });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const nameEl = screen.getByTestId('project-name');
      expect(nameEl.textContent!.length).toBeLessThan(longName.length);
    });
  });

  // ============================================================================
  // Variants Tests
  // ============================================================================

  describe('Variants', () => {
    it('AN-027: compact variant has smaller padding', () => {
      const alert = createMockAlert();
      render(<AlertNotification alert={alert} onClick={mockOnClick} variant="compact" />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveClass('p-2');
    });

    it('AN-028: full variant shows all recommendations', () => {
      const alert = createMockAlert({
        recommendations: ['First', 'Second', 'Third'],
      });
      render(<AlertNotification alert={alert} onClick={mockOnClick} variant="full" />);

      expect(screen.getByText('First')).toBeInTheDocument();
      expect(screen.getByText('Second')).toBeInTheDocument();
      expect(screen.getByText('Third')).toBeInTheDocument();
    });

    it('AN-029: disabled state - no click, opacity reduced', () => {
      const alert = createMockAlert();
      render(<AlertNotification alert={alert} onClick={mockOnClick} disabled />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveClass('opacity-50');
      expect(card).toHaveAttribute('aria-disabled', 'true');
    });

    it('AN-030: selected state has border highlight', () => {
      const alert = createMockAlert();
      render(<AlertNotification alert={alert} onClick={mockOnClick} isSelected />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveClass('ring-2');
      expect(card).toHaveClass('ring-blue-500');
    });
  });

  // ============================================================================
  // Timestamp Tests
  // ============================================================================

  describe('Timestamps', () => {
    it('AN-033: future timestamp shows "Just now"', () => {
      const alert = createMockAlert({
        created_at: new Date(Date.now() + 10000).toISOString(),
      });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      expect(screen.getByText(/just now/i)).toBeInTheDocument();
    });

    it('AN-034: years ago timestamp formatted correctly', () => {
      const alert = createMockAlert({
        created_at: new Date(Date.now() - 2 * 365 * 24 * 60 * 60 * 1000).toISOString(),
      });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      expect(screen.getByText(/2 years ago/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Responsive Tests
  // ============================================================================

  describe('Responsive', () => {
    it('AN-040: stacked layout on mobile', () => {
      const alert = createMockAlert();
      // Mock mobile viewport
      global.innerWidth = 375;

      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveClass('flex-col');
    });
  });

  // ============================================================================
  // Dark Mode Tests
  // ============================================================================

  describe('Dark Mode', () => {
    it('AN-041: dark mode classes applied', () => {
      const alert = createMockAlert({ alert_type: 'mention_spike' });
      render(<AlertNotification alert={alert} onClick={mockOnClick} />);

      const card = screen.getByTestId('alert-notification');
      expect(card).toHaveClass('dark:bg-emerald-950');
    });
  });
});
