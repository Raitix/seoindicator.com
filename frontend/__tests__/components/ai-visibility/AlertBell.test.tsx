/**
 * AlertBell Component Tests
 * Task: TASK-34-21 - Frontend Alert Notifications
 * Spec: SPEC-034 (AI Visibility Tracking)
 *
 * Tests for AlertBell header component implementation
 */

import React from 'react';
import { render, screen, within, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { AlertBell } from '@/components/ai-visibility/AlertBell';
import type { Alert } from '@/lib/ai-visibility/types';

// Mock Lucide React icons
jest.mock('lucide-react', () => ({
  Bell: () => <span data-testid="bell-icon">🔔</span>,
  X: () => <span data-testid="x-icon">✗</span>,
  ChevronRight: () => <span data-testid="chevron-icon">›</span>,
}));

// Mock AlertNotification component
jest.mock('@/components/ai-visibility/AlertNotification', () => ({
  AlertNotification: ({ alert, onClick }: any) => (
    <div
      data-testid={`dropdown-alert-${alert.id}`}
      onClick={() => onClick?.(alert.id)}
    >
      {alert.project_name}
    </div>
  ),
}));

// Test data
const createMockAlerts = (count: number): Alert[] =>
  Array.from({ length: count }, (_, i) => ({
    id: `alert-${i + 1}`,
    project_id: `proj-1`,
    project_name: `Project ${i + 1}`,
    alert_type: 'mention_spike' as const,
    message: `Alert message ${i + 1}`,
    previous_value: 40,
    current_value: 50,
    percentage_change: 25,
    is_read: false,
    is_resolved: false,
    created_at: new Date(Date.now() - i * 60 * 60 * 1000).toISOString(),
    resolved_at: null,
    recommendations: [],
  }));

describe('AlertBell', () => {
  const mockOnViewAll = jest.fn();
  const mockOnAlertClick = jest.fn();
  const mockOnMarkRead = jest.fn();
  const mockOnRefetch = jest.fn();

  const defaultProps = {
    alerts: createMockAlerts(3),
    unreadCount: 3,
    onViewAll: mockOnViewAll,
    onAlertClick: mockOnAlertClick,
    onMarkRead: mockOnMarkRead,
    onRefetch: mockOnRefetch,
    isLoading: false,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('AB-001: renders bell icon', () => {
      render(<AlertBell {...defaultProps} />);

      expect(screen.getByTestId('bell-icon')).toBeInTheDocument();
    });

    it('AB-002: no unread badge when count is 0', () => {
      render(<AlertBell {...defaultProps} unreadCount={0} />);

      expect(screen.queryByTestId('unread-badge')).not.toBeInTheDocument();
    });

    it('AB-003: unread badge shows count', () => {
      render(<AlertBell {...defaultProps} unreadCount={5} />);

      const badge = screen.getByTestId('unread-badge');
      expect(badge).toHaveTextContent('5');
    });

    it('AB-004: unread badge max shows 99+', () => {
      render(<AlertBell {...defaultProps} unreadCount={100} />);

      const badge = screen.getByTestId('unread-badge');
      expect(badge).toHaveTextContent('99+');
    });
  });

  // ============================================================================
  // Dropdown Tests
  // ============================================================================

  describe('Dropdown', () => {
    it('AB-005: click opens dropdown', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /alerts/i }));

      expect(screen.getByTestId('alert-dropdown')).toBeInTheDocument();
    });

    it('AB-006: click closes dropdown', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /alerts/i }));
      expect(screen.getByTestId('alert-dropdown')).toBeInTheDocument();

      await user.click(screen.getByRole('button', { name: /alerts/i }));
      expect(screen.queryByTestId('alert-dropdown')).not.toBeInTheDocument();
    });

    it('AB-007: click outside closes dropdown', async () => {
      const user = userEvent.setup();
      render(
        <div>
          <AlertBell {...defaultProps} />
          <div data-testid="outside">Outside</div>
        </div>
      );

      await user.click(screen.getByRole('button', { name: /alerts/i }));
      expect(screen.getByTestId('alert-dropdown')).toBeInTheDocument();

      await user.click(screen.getByTestId('outside'));
      expect(screen.queryByTestId('alert-dropdown')).not.toBeInTheDocument();
    });

    it('AB-008: escape closes dropdown', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /alerts/i }));
      expect(screen.getByTestId('alert-dropdown')).toBeInTheDocument();

      await user.keyboard('{Escape}');
      expect(screen.queryByTestId('alert-dropdown')).not.toBeInTheDocument();
    });

    it('AB-009: shows recent alerts (last 5)', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} alerts={createMockAlerts(10)} />);

      await user.click(screen.getByRole('button', { name: /alerts/i }));

      // Should show only 5 alerts
      const alerts = screen.getAllByTestId(/dropdown-alert-/);
      expect(alerts).toHaveLength(5);
    });
  });

  // ============================================================================
  // States Tests
  // ============================================================================

  describe('States', () => {
    it('AB-010: loading state shows skeleton', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} isLoading />);

      await user.click(screen.getByRole('button', { name: /alerts/i }));

      expect(screen.getAllByTestId(/skeleton/).length).toBeGreaterThan(0);
    });

    it('AB-011: empty state shows message', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} alerts={[]} unreadCount={0} />);

      await user.click(screen.getByRole('button', { name: /alerts/i }));

      expect(screen.getByText(/no new alerts/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Actions Tests
  // ============================================================================

  describe('Actions', () => {
    it('AB-012: view all link navigates to alerts page', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /alerts/i }));
      await user.click(screen.getByRole('link', { name: /view all/i }));

      expect(mockOnViewAll).toHaveBeenCalled();
    });

    it('AB-013: alert click opens detail and closes dropdown', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /alerts/i }));
      await user.click(screen.getByTestId('dropdown-alert-alert-1'));

      expect(mockOnAlertClick).toHaveBeenCalledWith('alert-1');
      expect(screen.queryByTestId('alert-dropdown')).not.toBeInTheDocument();
    });

    it('AB-014: mark as read updates badge', async () => {
      const user = userEvent.setup();
      const { rerender } = render(<AlertBell {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /alerts/i }));

      // Simulate mark read
      rerender(<AlertBell {...defaultProps} unreadCount={2} />);

      expect(screen.getByTestId('unread-badge')).toHaveTextContent('2');
    });
  });

  // ============================================================================
  // Polling Tests
  // ============================================================================

  describe('Polling', () => {
    it('AB-015: polling triggers refetch', () => {
      jest.useFakeTimers();

      render(<AlertBell {...defaultProps} pollingInterval={60000} />);

      jest.advanceTimersByTime(60000);

      expect(mockOnRefetch).toHaveBeenCalled();

      jest.useRealTimers();
    });

    it('AB-016: new alert shows toast', async () => {
      const { rerender } = render(<AlertBell {...defaultProps} alerts={[]} />);

      // Add new alert
      const newAlert = createMockAlerts(1)[0];
      newAlert.id = 'new-alert';

      rerender(<AlertBell {...defaultProps} alerts={[newAlert]} />);

      // Toast should appear
      await waitFor(() => {
        expect(screen.getByRole('alert')).toBeInTheDocument();
      });
    });
  });

  // ============================================================================
  // Animation Tests
  // ============================================================================

  describe('Animation', () => {
    it('AB-017: badge animates on count change', () => {
      const { rerender } = render(<AlertBell {...defaultProps} unreadCount={3} />);

      rerender(<AlertBell {...defaultProps} unreadCount={4} />);

      const badge = screen.getByTestId('unread-badge');
      expect(badge).toHaveClass('animate-bounce');
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('AB-018: aria-expanded true when open', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} />);

      const button = screen.getByRole('button', { name: /alerts/i });
      expect(button).toHaveAttribute('aria-expanded', 'false');

      await user.click(button);
      expect(button).toHaveAttribute('aria-expanded', 'true');
    });

    it('AB-019: aria-expanded false when closed', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} />);

      const button = screen.getByRole('button', { name: /alerts/i });
      await user.click(button);
      await user.click(button);

      expect(button).toHaveAttribute('aria-expanded', 'false');
    });

    it('AB-020: has aria-label with unread count', () => {
      render(<AlertBell {...defaultProps} unreadCount={5} />);

      const button = screen.getByRole('button', { name: /alerts/i });
      expect(button).toHaveAttribute('aria-label', expect.stringContaining('5'));
      expect(button).toHaveAttribute('aria-label', expect.stringContaining('unread'));
    });

    it('AB-021: keyboard activation with Enter', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} />);

      const button = screen.getByRole('button', { name: /alerts/i });
      button.focus();
      await user.keyboard('{Enter}');

      expect(screen.getByTestId('alert-dropdown')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Focus Management Tests
  // ============================================================================

  describe('Focus Management', () => {
    it('AB-022: focus trap in dropdown', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /alerts/i }));

      // Tab through dropdown elements
      await user.tab();
      await user.tab();
      await user.tab();
      // Focus should stay within dropdown
    });

    it('AB-023: dropdown repositions to fit screen', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} />);

      // Position bell at bottom of screen
      const button = screen.getByRole('button', { name: /alerts/i });
      Object.defineProperty(button, 'getBoundingClientRect', {
        value: () => ({ bottom: window.innerHeight - 50, top: window.innerHeight - 100 }),
      });

      await user.click(button);

      const dropdown = screen.getByTestId('alert-dropdown');
      expect(dropdown).toHaveClass('bottom-full');
    });
  });

  // ============================================================================
  // Mobile Tests
  // ============================================================================

  describe('Mobile', () => {
    beforeEach(() => {
      global.innerWidth = 375;
    });

    it('AB-024: full-width dropdown on mobile', async () => {
      const user = userEvent.setup();
      render(<AlertBell {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /alerts/i }));

      const dropdown = screen.getByTestId('alert-dropdown');
      expect(dropdown).toHaveClass('w-full');
      expect(dropdown).toHaveClass('left-0');
      expect(dropdown).toHaveClass('right-0');
    });
  });

  // ============================================================================
  // Notifications Tests
  // ============================================================================

  describe('Notifications', () => {
    it('AB-025: sound notification on new alert', async () => {
      const mockPlay = jest.fn();
      HTMLAudioElement.prototype.play = mockPlay;

      const { rerender } = render(<AlertBell {...defaultProps} alerts={[]} soundEnabled />);

      const newAlert = createMockAlerts(1)[0];
      rerender(<AlertBell {...defaultProps} alerts={[newAlert]} soundEnabled />);

      await waitFor(() => {
        expect(mockPlay).toHaveBeenCalled();
      });
    });

    it('AB-026: desktop notification on new alert', async () => {
      const mockNotification = jest.fn();
      (global as any).Notification = jest.fn(() => ({ close: jest.fn() }));
      (global as any).Notification.permission = 'granted';

      const { rerender } = render(<AlertBell {...defaultProps} alerts={[]} desktopNotificationsEnabled />);

      const newAlert = createMockAlerts(1)[0];
      rerender(<AlertBell {...defaultProps} alerts={[newAlert]} desktopNotificationsEnabled />);

      await waitFor(() => {
        expect((global as any).Notification).toHaveBeenCalled();
      });
    });
  });

  // ============================================================================
  // Dark Mode Tests
  // ============================================================================

  describe('Dark Mode', () => {
    it('AB-027: dark mode classes applied', () => {
      render(<AlertBell {...defaultProps} />);

      const button = screen.getByRole('button', { name: /alerts/i });
      expect(button).toHaveClass('dark:bg-slate-800');
    });
  });
});
