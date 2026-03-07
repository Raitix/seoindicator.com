/**
 * AlertList Component Tests
 * Task: TASK-34-21 - Frontend Alert Notifications
 * Spec: SPEC-034 (AI Visibility Tracking)
 *
 * Tests for AlertList component implementation
 */

import React from 'react';
import { render, screen, within, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { AlertList } from '@/components/ai-visibility/AlertList';
import type { Alert } from '@/lib/ai-visibility/types';

// Mock Lucide React icons
jest.mock('lucide-react', () => ({
  Filter: () => <span data-testid="filter-icon">FilterWhere</span>,
  X: () => <span data-testid="x-icon">✗</span>,
  Check: () => <span data-testid="check-icon">✓</span>,
  ChevronDown: () => <span data-testid="chevron-down-icon">▼</span>,
  ChevronLeft: () => <span data-testid="chevron-left-icon">◀</span>,
  ChevronRight: () => <span data-testid="chevron-right-icon">▶</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
  Bell: () => <span data-testid="bell-icon">🔔</span>,
  Inbox: () => <span data-testid="inbox-icon">📥</span>,
}));

// Mock AlertNotification component
jest.mock('@/components/ai-visibility/AlertNotification', () => ({
  AlertNotification: ({ alert, onClick, onMarkRead, onResolve }: any) => (
    <div
      data-testid={`alert-item-${alert.id}`}
      data-alert-type={alert.alert_type}
      onClick={() => onClick?.(alert.id)}
    >
      <span>{alert.project_name}</span>
      <span>{alert.message}</span>
      <button
        data-testid={`mark-read-${alert.id}`}
        onClick={(e) => {
          e.stopPropagation();
          onMarkRead?.(alert.id);
        }}
      >
        Mark Read
      </button>
      <button
        data-testid={`resolve-${alert.id}`}
        onClick={(e) => {
          e.stopPropagation();
          onResolve?.(alert.id);
        }}
      >
        Resolve
      </button>
    </div>
  ),
}));

// Test data
const createMockAlerts = (count: number): Alert[] =>
  Array.from({ length: count }, (_, i) => ({
    id: `alert-${i + 1}`,
    project_id: `proj-${(i % 3) + 1}`,
    project_name: `Project ${(i % 3) + 1}`,
    alert_type: (['mention_spike', 'mention_drop', 'competitor_gain', 'sentiment_change'] as const)[i % 4],
    message: `Alert message ${i + 1}`,
    previous_value: 40,
    current_value: 50 + i,
    percentage_change: 10 + i,
    is_read: i % 3 === 0,
    is_resolved: i % 5 === 0,
    created_at: new Date(Date.now() - i * 60 * 60 * 1000).toISOString(),
    resolved_at: i % 5 === 0 ? new Date().toISOString() : null,
    recommendations: [],
  }));

const mockProjects = [
  { id: 'proj-1', name: 'Project 1' },
  { id: 'proj-2', name: 'Project 2' },
  { id: 'proj-3', name: 'Project 3' },
];

describe('AlertList', () => {
  const mockOnAlertClick = jest.fn();
  const mockOnMarkRead = jest.fn();
  const mockOnResolve = jest.fn();
  const mockOnMarkAllRead = jest.fn();
  const mockOnPageChange = jest.fn();
  const mockOnLoadMore = jest.fn();

  const defaultProps = {
    alerts: createMockAlerts(5),
    projects: mockProjects,
    onAlertClick: mockOnAlertClick,
    onMarkRead: mockOnMarkRead,
    onResolve: mockOnResolve,
    onMarkAllRead: mockOnMarkAllRead,
    isLoading: false,
    unreadCount: 3,
    pagination: {
      currentPage: 1,
      perPage: 15,
      total: 5,
      lastPage: 1,
    },
    onPageChange: mockOnPageChange,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('AL-001: renders list of alerts', () => {
      render(<AlertList {...defaultProps} />);

      expect(screen.getByTestId('alert-list')).toBeInTheDocument();
      expect(screen.getAllByTestId(/alert-item-/)).toHaveLength(5);
    });

    it('AL-002: empty state shows illustration', () => {
      render(<AlertList {...defaultProps} alerts={[]} />);

      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
      expect(screen.getByText(/no alerts/i)).toBeInTheDocument();
    });

    it('AL-003: loading skeleton renders', () => {
      render(<AlertList {...defaultProps} alerts={[]} isLoading />);

      expect(screen.getAllByTestId(/skeleton/).length).toBeGreaterThan(0);
    });

    it('AL-004: error state with retry button', () => {
      render(<AlertList {...defaultProps} alerts={[]} error="Failed to load" />);

      expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Filter Tests
  // ============================================================================

  describe('Filters', () => {
    it('AL-005: project filter dropdown opens', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /filter by project/i }));

      expect(screen.getByRole('listbox', { name: /select project/i })).toBeInTheDocument();
    });

    it('AL-006: filter by project', async () => {
      const user = userEvent.setup();
      const onFilterChange = jest.fn();
      render(<AlertList {...defaultProps} onFilterChange={onFilterChange} />);

      await user.click(screen.getByRole('button', { name: /filter by project/i }));
      await user.click(screen.getByRole('option', { name: /Project 1/i }));

      expect(onFilterChange).toHaveBeenCalledWith({ project_id: 'proj-1' });
    });

    it('AL-007: clear project filter', async () => {
      const user = userEvent.setup();
      const onFilterChange = jest.fn();
      render(
        <AlertList
          {...defaultProps}
          filters={{ project_id: 'proj-1' }}
          onFilterChange={onFilterChange}
        />
      );

      await user.click(screen.getByRole('button', { name: /clear project filter/i }));

      expect(onFilterChange).toHaveBeenCalledWith({ project_id: null });
    });

    it('AL-008: type filter multi-select opens', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /filter by type/i }));

      expect(screen.getByRole('listbox', { name: /select types/i })).toBeInTheDocument();
    });

    it('AL-009: filter by multiple types', async () => {
      const user = userEvent.setup();
      const onFilterChange = jest.fn();
      render(<AlertList {...defaultProps} onFilterChange={onFilterChange} />);

      await user.click(screen.getByRole('button', { name: /filter by type/i }));
      await user.click(screen.getByRole('option', { name: /mention spike/i }));
      await user.click(screen.getByRole('option', { name: /mention drop/i }));

      expect(onFilterChange).toHaveBeenCalledWith({
        alert_types: ['mention_spike', 'mention_drop'],
      });
    });

    it('AL-010: clear type filter', async () => {
      const user = userEvent.setup();
      const onFilterChange = jest.fn();
      render(
        <AlertList
          {...defaultProps}
          filters={{ alert_types: ['mention_spike'] }}
          onFilterChange={onFilterChange}
        />
      );

      await user.click(screen.getByRole('button', { name: /clear type filter/i }));

      expect(onFilterChange).toHaveBeenCalledWith({ alert_types: [] });
    });

    it('AL-011: read/unread toggle - unread only', async () => {
      const user = userEvent.setup();
      const onFilterChange = jest.fn();
      render(<AlertList {...defaultProps} onFilterChange={onFilterChange} />);

      await user.click(screen.getByRole('button', { name: /show unread only/i }));

      expect(onFilterChange).toHaveBeenCalledWith({ is_read: false });
    });

    it('AL-012: toggle back to all alerts', async () => {
      const user = userEvent.setup();
      const onFilterChange = jest.fn();
      render(
        <AlertList
          {...defaultProps}
          filters={{ is_read: false }}
          onFilterChange={onFilterChange}
        />
      );

      await user.click(screen.getByRole('button', { name: /show all alerts/i }));

      expect(onFilterChange).toHaveBeenCalledWith({ is_read: null });
    });
  });

  // ============================================================================
  // Actions Tests
  // ============================================================================

  describe('Actions', () => {
    it('AL-013: mark all as read button triggers callback', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /mark all as read/i }));

      expect(mockOnMarkAllRead).toHaveBeenCalled();
    });

    it('AL-014: mark all disabled when all read', () => {
      render(<AlertList {...defaultProps} unreadCount={0} />);

      expect(screen.getByRole('button', { name: /mark all as read/i })).toBeDisabled();
    });

    it('AL-023: alert click triggers callback', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByTestId('alert-item-alert-1'));

      expect(mockOnAlertClick).toHaveBeenCalledWith('alert-1');
    });

    it('AL-024: mark read from list', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByTestId('mark-read-alert-1'));

      expect(mockOnMarkRead).toHaveBeenCalledWith('alert-1');
    });

    it('AL-025: resolve from list', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByTestId('resolve-alert-1'));

      expect(mockOnResolve).toHaveBeenCalledWith('alert-1');
    });
  });

  // ============================================================================
  // Pagination Tests
  // ============================================================================

  describe('Pagination', () => {
    it('AL-015: pagination controls rendered', () => {
      render(
        <AlertList
          {...defaultProps}
          pagination={{ currentPage: 1, perPage: 15, total: 50, lastPage: 4 }}
        />
      );

      expect(screen.getByTestId('pagination')).toBeInTheDocument();
    });

    it('AL-016: page change triggers callback', async () => {
      const user = userEvent.setup();
      render(
        <AlertList
          {...defaultProps}
          pagination={{ currentPage: 1, perPage: 15, total: 50, lastPage: 4 }}
        />
      );

      await user.click(screen.getByRole('button', { name: /page 2/i }));

      expect(mockOnPageChange).toHaveBeenCalledWith(2);
    });

    it('AL-019: no more results message', () => {
      render(
        <AlertList
          {...defaultProps}
          pagination={{ currentPage: 4, perPage: 15, total: 50, lastPage: 4 }}
        />
      );

      expect(screen.getByText(/end of results/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Infinite Scroll Tests
  // ============================================================================

  describe('Infinite Scroll', () => {
    it('AL-017: scroll to bottom triggers onLoadMore', async () => {
      const onLoadMore = jest.fn();
      render(
        <AlertList
          {...defaultProps}
          onLoadMore={onLoadMore}
          hasMore
          pagination={{ currentPage: 1, perPage: 15, total: 50, lastPage: 4 }}
        />
      );

      // Simulate scroll to bottom
      const list = screen.getByTestId('alert-list');
      fireEvent.scroll(list, { target: { scrollY: 1000 } });

      await waitFor(() => {
        expect(onLoadMore).toHaveBeenCalled();
      });
    });

    it('AL-018: loading indicator at bottom', () => {
      render(<AlertList {...defaultProps} isLoadingMore />);

      expect(screen.getByTestId('loading-more')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Sort Tests
  // ============================================================================

  describe('Sorting', () => {
    it('AL-020: default sort by date (newest first)', () => {
      render(<AlertList {...defaultProps} />);

      const alerts = screen.getAllByTestId(/alert-item-/);
      // First alert should be most recent (alert-1)
      expect(alerts[0]).toHaveAttribute('data-testid', 'alert-item-alert-1');
    });

    it('AL-021: sort by type groups by type', async () => {
      const user = userEvent.setup();
      const onSortChange = jest.fn();
      render(<AlertList {...defaultProps} onSortChange={onSortChange} />);

      await user.click(screen.getByRole('button', { name: /sort by/i }));
      await user.click(screen.getByRole('option', { name: /type/i }));

      expect(onSortChange).toHaveBeenCalledWith('type');
    });

    it('AL-022: sort by project groups by project', async () => {
      const user = userEvent.setup();
      const onSortChange = jest.fn();
      render(<AlertList {...defaultProps} onSortChange={onSortChange} />);

      await user.click(screen.getByRole('button', { name: /sort by/i }));
      await user.click(screen.getByRole('option', { name: /project/i }));

      expect(onSortChange).toHaveBeenCalledWith('project');
    });
  });

  // ============================================================================
  // Multiple Selection Tests
  // ============================================================================

  describe('Multiple Selection', () => {
    it('AL-026: multiple selection with ctrl+click', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} enableSelection />);

      await user.click(screen.getByTestId('alert-item-alert-1'));
      await user.keyboard('[ControlLeft>]');
      await user.click(screen.getByTestId('alert-item-alert-2'));

      expect(screen.getAllByTestId(/alert-item-/).filter(el => el.classList.contains('selected'))).toHaveLength(2);
    });

    it('AL-027: bulk actions toolbar visible when 2+ selected', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} enableSelection />);

      // Select 2 alerts
      await user.click(screen.getByTestId('alert-item-alert-1'));
      await user.keyboard('[ControlLeft>]');
      await user.click(screen.getByTestId('alert-item-alert-2'));

      expect(screen.getByTestId('bulk-actions-toolbar')).toBeInTheDocument();
    });

    it('AL-028: bulk mark read', async () => {
      const user = userEvent.setup();
      const onBulkMarkRead = jest.fn();
      render(<AlertList {...defaultProps} enableSelection onBulkMarkRead={onBulkMarkRead} />);

      await user.click(screen.getByTestId('alert-item-alert-1'));
      await user.keyboard('[ControlLeft>]');
      await user.click(screen.getByTestId('alert-item-alert-2'));
      await user.click(screen.getByRole('button', { name: /mark selected as read/i }));

      expect(onBulkMarkRead).toHaveBeenCalledWith(['alert-1', 'alert-2']);
    });

    it('AL-031: clear selection', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} enableSelection />);

      await user.click(screen.getByTestId('alert-item-alert-1'));
      await user.click(screen.getByRole('button', { name: /clear selection/i }));

      expect(screen.queryByTestId('bulk-actions-toolbar')).not.toBeInTheDocument();
    });
  });

  // ============================================================================
  // Filter UI Tests
  // ============================================================================

  describe('Filter UI', () => {
    it('AL-032: filter count badge shows active filters', () => {
      render(
        <AlertList
          {...defaultProps}
          filters={{ project_id: 'proj-1', alert_types: ['mention_spike'] }}
        />
      );

      expect(screen.getByTestId('filter-count-badge')).toHaveTextContent('2');
    });

    it('AL-033: clear all filters button', async () => {
      const user = userEvent.setup();
      const onFilterChange = jest.fn();
      render(
        <AlertList
          {...defaultProps}
          filters={{ project_id: 'proj-1', alert_types: ['mention_spike'] }}
          onFilterChange={onFilterChange}
        />
      );

      await user.click(screen.getByRole('button', { name: /clear all filters/i }));

      expect(onFilterChange).toHaveBeenCalledWith({});
    });

    it('AL-034: sticky header on scroll', () => {
      render(<AlertList {...defaultProps} />);

      const header = screen.getByTestId('filter-header');
      expect(header).toHaveClass('sticky');
      expect(header).toHaveClass('top-0');
    });
  });

  // ============================================================================
  // URL/State Persistence Tests
  // ============================================================================

  describe('State Persistence', () => {
    it('AL-035: filters restored from URL params', () => {
      // Mock URL with params
      const onFilterChange = jest.fn();
      render(
        <AlertList
          {...defaultProps}
          initialFilters={{ project_id: 'proj-1', is_read: false }}
          onFilterChange={onFilterChange}
        />
      );

      // Filters should be applied from initialFilters
      expect(screen.getByRole('button', { name: /show all alerts/i })).toBeInTheDocument();
    });

    it('AL-036: URL params updated on filter change', () => {
      const onUrlChange = jest.fn();
      render(<AlertList {...defaultProps} onUrlChange={onUrlChange} />);

      // Trigger filter change
      fireEvent.click(screen.getByRole('button', { name: /show unread only/i }));

      expect(onUrlChange).toHaveBeenCalled();
    });
  });

  // ============================================================================
  // Real-time Updates Tests
  // ============================================================================

  describe('Real-time Updates', () => {
    it('AL-037: unread count badge displays count', () => {
      render(<AlertList {...defaultProps} unreadCount={5} />);

      expect(screen.getByTestId('unread-count-badge')).toHaveTextContent('5');
    });

    it('AL-038: no badge when zero unread', () => {
      render(<AlertList {...defaultProps} unreadCount={0} />);

      expect(screen.queryByTestId('unread-count-badge')).not.toBeInTheDocument();
    });

    it('AL-039: list updates on new alert', () => {
      const { rerender } = render(<AlertList {...defaultProps} />);

      const newAlerts = [...createMockAlerts(5), createMockAlerts(1)[0]];
      newAlerts[newAlerts.length - 1].id = 'new-alert';

      rerender(<AlertList {...defaultProps} alerts={newAlerts} />);

      expect(screen.getByTestId('alert-item-new-alert')).toBeInTheDocument();
    });

    it('AL-040: polling interval triggers refetch', () => {
      const onRefetch = jest.fn();
      jest.useFakeTimers();

      render(<AlertList {...defaultProps} pollingInterval={300000} onRefetch={onRefetch} />);

      jest.advanceTimersByTime(300000);

      expect(onRefetch).toHaveBeenCalled();

      jest.useRealTimers();
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('AL-044: accessible filter labels', () => {
      render(<AlertList {...defaultProps} />);

      expect(screen.getByRole('button', { name: /filter by project/i })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /filter by type/i })).toBeInTheDocument();
    });

    it('AL-045: keyboard navigation through filters', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.tab(); // First filter
      expect(screen.getByRole('button', { name: /filter by project/i })).toHaveFocus();

      await user.tab(); // Second filter
      expect(screen.getByRole('button', { name: /filter by type/i })).toHaveFocus();
    });

    it('AL-046: escape closes dropdown', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /filter by project/i }));
      expect(screen.getByRole('listbox')).toBeInTheDocument();

      await user.keyboard('{Escape}');
      expect(screen.queryByRole('listbox')).not.toBeInTheDocument();
    });
  });

  // ============================================================================
  // Mobile Tests
  // ============================================================================

  describe('Mobile', () => {
    beforeEach(() => {
      global.innerWidth = 375;
    });

    it('AL-047: mobile filter drawer button', () => {
      render(<AlertList {...defaultProps} />);

      expect(screen.getByRole('button', { name: /open filters/i })).toBeInTheDocument();
    });

    it('AL-048: mobile drawer opens', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /open filters/i }));

      expect(screen.getByTestId('filter-drawer')).toBeInTheDocument();
    });

    it('AL-049: mobile drawer closes on outside click', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /open filters/i }));
      await user.click(screen.getByTestId('drawer-overlay'));

      expect(screen.queryByTestId('filter-drawer')).not.toBeInTheDocument();
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases', () => {
    it('AL-042: connection error shows retry button', () => {
      render(<AlertList {...defaultProps} alerts={[]} error="Network error" />);

      expect(screen.getByText(/network error/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });

    it('AL-043: retry fetch triggers callback', async () => {
      const user = userEvent.setup();
      const onRetry = jest.fn();
      render(<AlertList {...defaultProps} alerts={[]} error="Network error" onRetry={onRetry} />);

      await user.click(screen.getByRole('button', { name: /retry/i }));

      expect(onRetry).toHaveBeenCalled();
    });

    it('AL-052: filtered count shown', () => {
      render(
        <AlertList
          {...defaultProps}
          alerts={createMockAlerts(3)}
          filters={{ project_id: 'proj-1' }}
          totalBeforeFilter={10}
        />
      );

      expect(screen.getByText(/3 of 10 alerts/i)).toBeInTheDocument();
    });

    it('AL-053: all filtered out shows message', () => {
      render(
        <AlertList
          {...defaultProps}
          alerts={[]}
          filters={{ project_id: 'non-existent' }}
          totalBeforeFilter={10}
        />
      );

      expect(screen.getByText(/no alerts match your filters/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Dark Mode Tests
  // ============================================================================

  describe('Dark Mode', () => {
    it('AL-054: dark mode classes applied', () => {
      render(<AlertList {...defaultProps} />);

      const list = screen.getByTestId('alert-list');
      expect(list).toHaveClass('dark:bg-slate-900');
    });
  });

  // ============================================================================
  // Animation Tests
  // ============================================================================

  describe('Animation', () => {
    it('AL-055: smooth animation on item change', () => {
      const { rerender } = render(<AlertList {...defaultProps} />);

      // Remove an item
      const newAlerts = defaultProps.alerts.slice(0, 4);
      rerender(<AlertList {...defaultProps} alerts={newAlerts} />);

      const list = screen.getByTestId('alert-list');
      expect(list).toHaveClass('transition-all');
    });
  });
});
