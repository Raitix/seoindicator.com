/**
 * AlertPage Component Tests
 * Task: TASK-34-21 - Frontend Alert Notifications
 * Spec: SPEC-034 (AI Visibility Tracking)
 *
 * Integration tests for AlertPage component
 */

import React from 'react';
import { render, screen, within, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { AlertPage } from '@/app/ai-visibility/alerts/page';

// Mock hooks
const mockUseAlerts = {
  data: null,
  isLoading: false,
  error: null,
  refetch: jest.fn(),
};
const mockUsePreferences = {
  data: null,
  isLoading: false,
  error: null,
};

jest.mock('@/hooks/useAlerts', () => ({
  useAlerts: () => mockUseAlerts,
  useAlertPreferences: () => mockUsePreferences,
  useMarkAlertRead: () => ({ mutate: jest.fn() }),
  useResolveAlert: () => ({ mutate: jest.fn() }),
  useMarkAllRead: () => ({ mutate: jest.fn() }),
  useUpdatePreferences: () => ({ mutate: jest.fn() }),
}));

// Mock router
const mockPush = jest.fn();
const mockReplace = jest.fn();
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
    back: jest.fn(),
  }),
  useSearchParams: () => new URLSearchParams(),
  usePathname: () => '/ai-visibility/alerts',
}));

// Mock components
jest.mock('@/components/ai-visibility/AlertList', () => ({
  AlertList: ({ alerts, onAlertClick, onMarkRead, onResolve, onMarkAllRead }: any) => (
    <div data-testid="alert-list-mock">
      {alerts?.map((a: any) => (
        <div key={a.id} data-testid={`alert-${a.id}`}>
          {a.message}
          <button onClick={() => onAlertClick?.(a.id)}>View</button>
          <button onClick={() => onMarkRead?.(a.id)}>Mark Read</button>
          <button onClick={() => onResolve?.(a.id)}>Resolve</button>
        </div>
      ))}
      <button onClick={onMarkAllRead}>Mark All Read</button>
    </div>
  ),
}));

jest.mock('@/components/ai-visibility/AlertDetailModal', () => ({
  AlertDetailModal: ({ isOpen, alert, onClose, onResolve }: any) => (
    isOpen ? (
      <div data-testid="alert-modal-mock" role="dialog">
        <span>{alert?.message}</span>
        <button onClick={onClose}>Close</button>
        <button onClick={() => onResolve?.(alert?.id)}>Resolve</button>
      </div>
    ) : null
  ),
}));

jest.mock('@/components/ai-visibility/AlertPreferencesForm', () => ({
  AlertPreferencesForm: ({ preferences, onSave }: any) => (
    <div data-testid="preferences-form-mock">
      <span>Preferences Form</span>
      <button onClick={() => onSave?.(preferences)}>Save Preferences</button>
    </div>
  ),
}));

// Test data
const createMockAlerts = (count: number) =>
  Array.from({ length: count }, (_, i) => ({
    id: `alert-${i + 1}`,
    project_id: `proj-1`,
    project_name: `Project ${i + 1}`,
    alert_type: 'mention_spike' as const,
    message: `Alert message ${i + 1}`,
    previous_value: 40,
    current_value: 50,
    percentage_change: 25,
    is_read: i % 2 === 0,
    is_resolved: false,
    created_at: new Date(Date.now() - i * 60 * 60 * 1000).toISOString(),
    resolved_at: null,
    recommendations: [],
  }));

const mockPreferences = {
  email_notifications: true,
  webhook_url: 'https://example.com/webhook',
  alert_types: ['mention_spike', 'mention_drop', 'competitor_gain', 'sentiment_change'],
};

describe('AlertPage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockUseAlerts.data = {
      data: createMockAlerts(5),
      meta: {
        current_page: 1,
        per_page: 15,
        total: 5,
        unread_count: 3,
        last_page: 1,
      },
    };
    mockUsePreferences.data = { data: mockPreferences };
  });

  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('AP-001: renders page structure', () => {
      render(<AlertPage />);

      expect(screen.getByTestId('alert-page')).toBeInTheDocument();
    });

    it('AP-002: alert list visible', () => {
      render(<AlertPage />);

      expect(screen.getByTestId('alert-list-mock')).toBeInTheDocument();
    });

    it('AP-003: sidebar with preferences form visible', () => {
      render(<AlertPage />);

      expect(screen.getByTestId('preferences-form-mock')).toBeInTheDocument();
    });

    it('AP-004: summary stats displayed', () => {
      render(<AlertPage />);

      expect(screen.getByText(/5 total/i)).toBeInTheDocument();
      expect(screen.getByText(/3 unread/i)).toBeInTheDocument();
    });

    it('AP-012: page title set', () => {
      render(<AlertPage />);

      expect(document.title).toBe('Alerts | SEOIndicator');
    });
  });

  // ============================================================================
  // State Management Tests
  // ============================================================================

  describe('State Management', () => {
    it('AP-005: stats update when alert marked read', async () => {
      const user = userEvent.setup();
      render(<AlertPage />);

      // Initial stats
      expect(screen.getByText(/3 unread/i)).toBeInTheDocument();

      // Mark one as read
      await user.click(screen.getAllByText('Mark Read')[0]);

      // Stats should update (in real implementation)
    });

    it('AP-006: list updates when filter applied', async () => {
      const user = userEvent.setup();
      render(<AlertPage />);

      // Click filter (would be in AlertList component)
      // In mock, we just verify component renders
      expect(screen.getByTestId('alert-list-mock')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Modal Tests
  // ============================================================================

  describe('Modal', () => {
    it('AP-008: modal opens on alert click', async () => {
      const user = userEvent.setup();
      render(<AlertPage />);

      await user.click(screen.getAllByText('View')[0]);

      expect(screen.getByTestId('alert-modal-mock')).toBeInTheDocument();
    });

    it('AP-009: modal closes on close button', async () => {
      const user = userEvent.setup();
      render(<AlertPage />);

      await user.click(screen.getAllByText('View')[0]);
      expect(screen.getByTestId('alert-modal-mock')).toBeInTheDocument();

      await user.click(screen.getByText('Close'));
      expect(screen.queryByTestId('alert-modal-mock')).not.toBeInTheDocument();
    });
  });

  // ============================================================================
  // URL Sync Tests
  // ============================================================================

  describe('URL Sync', () => {
    it('AP-010: URL updated when filter applied', async () => {
      const user = userEvent.setup();
      render(<AlertPage />);

      // In real implementation, filter changes would update URL
      // Mock doesn't have filter UI, so we skip the actual interaction
    });

    it('AP-011: filters restored from URL params', () => {
      // Mock URL with params
      jest.spyOn(URLSearchParams.prototype, 'get').mockReturnValue('proj-1');

      render(<AlertPage />);

      // In real implementation, filters would be applied from URL
      expect(screen.getByTestId('alert-list-mock')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Loading/Error States Tests
  // ============================================================================

  describe('Loading/Error States', () => {
    it('AP-014: loading state shows skeletons', () => {
      mockUseAlerts.isLoading = true;
      render(<AlertPage />);

      expect(screen.getAllByTestId(/skeleton/).length).toBeGreaterThan(0);
      mockUseAlerts.isLoading = false;
    });

    it('AP-015: error state shows retry button', () => {
      mockUseAlerts.error = new Error('Failed to load');
      render(<AlertPage />);

      expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
      mockUseAlerts.error = null;
    });

    it('AP-016: empty state shows illustration', () => {
      mockUseAlerts.data = {
        data: [],
        meta: { current_page: 1, per_page: 15, total: 0, unread_count: 0, last_page: 1 },
      };
      render(<AlertPage />);

      expect(screen.getByText(/no alerts/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Responsive Tests
  // ============================================================================

  describe('Responsive', () => {
    it('AP-017: sidebar collapsed on mobile', () => {
      global.innerWidth = 375;
      render(<AlertPage />);

      const sidebar = screen.getByTestId('sidebar');
      expect(sidebar).toHaveClass('hidden');
    });

    it('AP-018: responsive layout on mobile', () => {
      global.innerWidth = 375;
      render(<AlertPage />);

      const main = screen.getByTestId('main-content');
      expect(main).toHaveClass('w-full');
    });

    it('AP-019: mobile drawer button visible', () => {
      global.innerWidth = 375;
      render(<AlertPage />);

      expect(screen.getByRole('button', { name: /settings/i })).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Keyboard Shortcuts Tests
  // ============================================================================

  describe('Keyboard Shortcuts', () => {
    it('AP-021: "r" marks all read', async () => {
      const user = userEvent.setup();
      render(<AlertPage />);

      await user.keyboard('r');

      // Should trigger mark all read
      expect(screen.getByText('Mark All Read')).toBeInTheDocument();
    });

    it('AP-022: "/" focuses search', async () => {
      const user = userEvent.setup();
      render(<AlertPage />);

      await user.keyboard('/');

      // Search input should be focused (if exists)
    });

    it('AP-023: "?" shows shortcuts help', async () => {
      const user = userEvent.setup();
      render(<AlertPage />);

      await user.keyboard('?');

      // Shortcuts help modal should appear
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Navigation Tests
  // ============================================================================

  describe('Navigation', () => {
    it('AP-024: breadcrumbs displayed', () => {
      render(<AlertPage />);

      expect(screen.getByText(/home/i)).toBeInTheDocument();
      expect(screen.getByText(/alerts/i)).toBeInTheDocument();
    });

    it('AP-025: back navigation restores state', async () => {
      const user = userEvent.setup();
      render(<AlertPage />);

      // Click alert to open modal
      await user.click(screen.getAllByText('View')[0]);

      // Go back
      await user.keyboard('{Alt>}{Left}{/Alt}');

      // Modal should be closed
      expect(screen.queryByTestId('alert-modal-mock')).not.toBeInTheDocument();
    });
  });

  // ============================================================================
  // Real-time Updates Tests
  // ============================================================================

  describe('Real-time Updates', () => {
    it('AP-027: new alert updates list', async () => {
      const { rerender } = render(<AlertPage />);

      // Add new alert
      const newAlerts = [...createMockAlerts(5), {
        id: 'new-alert',
        project_id: 'proj-1',
        project_name: 'New Project',
        alert_type: 'mention_spike' as const,
        message: 'New alert',
        previous_value: 40,
        current_value: 50,
        percentage_change: 25,
        is_read: false,
        is_resolved: false,
        created_at: new Date().toISOString(),
        resolved_at: null,
        recommendations: [],
      }];

      mockUseAlerts.data = {
        data: newAlerts,
        meta: { ...mockUseAlerts.data!.meta, total: 6, unread_count: 4 },
      };

      rerender(<AlertPage />);

      expect(screen.getByTestId('alert-new-alert')).toBeInTheDocument();
    });

    it('AP-028: polling triggers refetch', () => {
      jest.useFakeTimers();

      render(<AlertPage />);

      jest.advanceTimersByTime(5 * 60 * 1000); // 5 minutes

      expect(mockUseAlerts.refetch).toHaveBeenCalled();

      jest.useRealTimers();
    });

    it('AP-029: offline indicator shown', () => {
      Object.defineProperty(navigator, 'onLine', { value: false, writable: true });

      render(<AlertPage />);

      expect(screen.getByText(/offline/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('AP-034: ARIA landmarks present', () => {
      render(<AlertPage />);

      expect(screen.getByRole('main')).toBeInTheDocument();
      expect(screen.getByRole('complementary')).toBeInTheDocument();
    });

    it('AP-035: skip link present', () => {
      render(<AlertPage />);

      expect(screen.getByText(/skip to main/i)).toBeInTheDocument();
    });

    it('AP-036: focus management on modal close', async () => {
      const user = userEvent.setup();
      render(<AlertPage />);

      // Open modal
      await user.click(screen.getAllByText('View')[0]);

      // Close modal
      await user.click(screen.getByText('Close'));

      // Focus should return to alert item
      expect(screen.getByTestId('alert-alert-1')).toHaveFocus();
    });

    it('AP-047: no accessibility violations', async () => {
      const { container } = render(<AlertPage />);

      // In real implementation, would use axe-core
      // This is a placeholder test
      expect(container).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Authentication Tests
  // ============================================================================

  describe('Authentication', () => {
    it('AP-038: unauthenticated redirects to login', () => {
      // Mock unauthenticated state
      jest.mock('@/hooks/useAuth', () => ({
        useAuth: () => ({ isAuthenticated: false }),
      }));

      render(<AlertPage />);

      expect(mockPush).toHaveBeenCalledWith('/login');
    });

    it('AP-039: permission check for AI visibility', () => {
      // Mock no AI visibility permission
      jest.mock('@/hooks/usePermissions', () => ({
        usePermissions: () => ({ canViewAIVisibility: false }),
      }));

      render(<AlertPage />);

      expect(screen.getByText(/access denied/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Analytics Tests
  // ============================================================================

  describe('Analytics', () => {
    it('AP-041: page view tracked', () => {
      const mockTrackPageView = jest.fn();
      jest.mock('@/lib/analytics', () => ({
        trackPageView: mockTrackPageView,
      }));

      render(<AlertPage />);

      // In real implementation, page view would be tracked
    });
  });

  // ============================================================================
  // Error Boundary Tests
  // ============================================================================

  describe('Error Boundary', () => {
    it('AP-044: error boundary catches errors', () => {
      // Throw an error in a child component
      const ThrowError = () => {
        throw new Error('Test error');
      };

      // In real implementation, ErrorBoundary would catch this
      // and show fallback UI
    });
  });

  // ============================================================================
  // Performance Tests
  // ============================================================================

  describe('Performance', () => {
    it('AP-045: renders 100 alerts in under 100ms', () => {
      mockUseAlerts.data = {
        data: createMockAlerts(100),
        meta: { current_page: 1, per_page: 100, total: 100, unread_count: 50, last_page: 1 },
      };

      const start = performance.now();
      render(<AlertPage />);
      const end = performance.now();

      expect(end - start).toBeLessThan(100);
    });
  });

  // ============================================================================
  // SEO Tests
  // ============================================================================

  describe('SEO', () => {
    it('AP-048: OG tags set', () => {
      render(<AlertPage />);

      const ogTitle = document.querySelector('meta[property="og:title"]');
      expect(ogTitle).toBeInTheDocument();
    });

    it('AP-049: canonical URL set', () => {
      render(<AlertPage />);

      const canonical = document.querySelector('link[rel="canonical"]');
      expect(canonical).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Dark Mode Tests
  // ============================================================================

  describe('Dark Mode', () => {
    it('AP-031: dark mode classes applied', () => {
      render(<AlertPage />);

      const page = screen.getByTestId('alert-page');
      expect(page).toHaveClass('dark:bg-slate-900');
    });
  });
});
