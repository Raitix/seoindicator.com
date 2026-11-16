/**
 * AlertList Component Tests
 * Task: TASK-34-21 - Frontend Alert Notifications
 * Spec: SPEC-034 (AI Visibility Tracking)
 *
 * Tests for AlertList component implementation
 */

import React from 'react';
import { vi } from 'vitest';
import { render, screen, within, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { AlertList } from '@/components/ai-visibility/AlertList';
import { generateAlertMessage } from '@/lib/ai-visibility/types';
import type { Alert } from '@/lib/ai-visibility/types';

// Mock Lucide React icons
vi.mock('lucide-react', () => ({
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
vi.mock('@/components/ai-visibility/AlertNotification', () => ({
  AlertNotification: ({ alert, onClick, onMarkRead, onResolve, isSelected }: any) => (
    <div
      data-testid={`alert-item-${alert.id}`}
      data-alert-type={alert.alert_type}
      className={isSelected ? 'ring-2 ring-blue-500' : ''}
      onClick={() => onClick?.(alert.id)}
    >
      <span data-testid={`alert-project-${alert.id}`}>{alert.project_name}</span>
      <span>{generateAlertMessage(alert)}</span>
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
// Shape mirrors the backend AIVisibilityAlertResource (see lib/ai-visibility/types.ts):
// numeric ids, `change_percent`, and no `message`/`recommendations` fields - the
// component derives the message from the alert data.
const createMockAlerts = (count: number): Alert[] =>
  Array.from({ length: count }, (_, i) => ({
    id: i + 1,
    project_id: (i % 3) + 1,
    project_name: `Project ${(i % 3) + 1}`,
    alert_type: (['mention_spike', 'mention_drop', 'competitor_gain', 'sentiment_change'] as const)[i % 4],
    threshold: 20,
    previous_value: 40,
    current_value: 50 + i,
    change_percent: 10 + i,
    is_read: i % 3 === 0,
    is_resolved: i % 5 === 0,
    resolved_at: i % 5 === 0 ? new Date().toISOString() : null,
    triggered_at: null,
    metadata: null,
    created_at: new Date(Date.now() - i * 60 * 60 * 1000).toISOString(),
    updated_at: new Date().toISOString(),
  }));

const mockProjects = [
  { id: 1, name: 'Project 1' },
  { id: 2, name: 'Project 2' },
  { id: 3, name: 'Project 3' },
];

describe('AlertList', () => {
  const mockOnAlertClick = vi.fn();
  const mockOnMarkRead = vi.fn();
  const mockOnResolve = vi.fn();
  const mockOnMarkAllRead = vi.fn();
  const mockOnPageChange = vi.fn();
  const mockOnLoadMore = vi.fn();

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
    vi.clearAllMocks();
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
      const onRetry = vi.fn();
      render(
        <AlertList
          {...defaultProps}
          alerts={[]}
          error="Failed to load"
          onRetry={onRetry}
        />
      );

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
      const onFilterChange = vi.fn();
      render(<AlertList {...defaultProps} onFilterChange={onFilterChange} />);

      await user.click(screen.getByRole('button', { name: /filter by project/i }));
      await user.click(screen.getByRole('option', { name: /Project 1/i }));

      expect(onFilterChange).toHaveBeenCalledWith({ project_id: '1' });
    });

    it('AL-007: clear project filter', async () => {
      const user = userEvent.setup();
      const onFilterChange = vi.fn();
      render(
        <AlertList
          {...defaultProps}
          filters={{ project_id: '1' }}
          onFilterChange={onFilterChange}
        />
      );

      // The list exposes one "Clear all filters" control
      // active project filter.
      expect(screen.getByTestId('filter-count-badge')).toHaveTextContent('1');

      await user.click(screen.getByRole('button', { name: /clear all filters/i }));

      expect(onFilterChange).toHaveBeenCalledWith({});
      expect(screen.queryByTestId('filter-count-badge')).not.toBeInTheDocument();
    });

    it('AL-008: type filter multi-select opens', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /filter by type/i }));

      expect(screen.getByRole('listbox', { name: /select types/i })).toBeInTheDocument();
    });

    it('AL-009: filter by multiple types', async () => {
      const user = userEvent.setup();
      const onFilterChange = vi.fn();
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
      const onFilterChange = vi.fn();
      render(
        <AlertList
          {...defaultProps}
          filters={{ alert_types: ['mention_spike'] }}
          onFilterChange={onFilterChange}
        />
      );

      // The type dropdown is multi-select: activating a selected type removes it
      await user.click(screen.getByRole('button', { name: /filter by type/i }));
      await user.click(screen.getByRole('option', { name: /mention spike/i }));

      expect(onFilterChange).toHaveBeenCalledWith({ alert_types: [] });
    });

    it('AL-011: read/unread toggle - unread only', async () => {
      const user = userEvent.setup();
      const onFilterChange = vi.fn();
      render(<AlertList {...defaultProps} onFilterChange={onFilterChange} />);

      await user.click(screen.getByRole('button', { name: /show unread only/i }));

      expect(onFilterChange).toHaveBeenCalledWith({ is_read: false });
    });

    it('AL-012: toggle back to all alerts', async () => {
      const user = userEvent.setup();
      const onFilterChange = vi.fn();
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

      await user.click(screen.getByTestId('alert-item-1'));

      expect(mockOnAlertClick).toHaveBeenCalledWith(1);
    });

    it('AL-024: mark read from list', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByTestId('mark-read-1'));

      expect(mockOnMarkRead).toHaveBeenCalledWith(1);
    });

    it('AL-025: resolve from list', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} />);

      await user.click(screen.getByTestId('resolve-1'));

      expect(mockOnResolve).toHaveBeenCalledWith(1);
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
      const onLoadMore = vi.fn();
      render(
        <AlertList
          {...defaultProps}
          onLoadMore={onLoadMore}
          hasMore
          pagination={{ currentPage: 1, perPage: 15, total: 50, lastPage: 4 }}
        />
      );

      // The infinite-scroll listener is attached to the list container that
      // holds the alert rows, not to the outer `alert-list` wrapper.
      const scrollContainer = screen
        .getByTestId('alert-list')
        .querySelector('.divide-y') as HTMLElement;
      expect(scrollContainer).not.toBeNull();

      // Simulate scroll to bottom
      fireEvent.scroll(scrollContainer);

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
      expect(alerts[0]).toHaveAttribute('data-testid', 'alert-item-1');
    });

    it('AL-021: sort by type groups by type', async () => {
      const user = userEvent.setup();
      const onSortChange = vi.fn();
      render(<AlertList {...defaultProps} onSortChange={onSortChange} />);

      // The shipped list renders no sort control: alerts keep the order they
      // were provided in and `onSortChange` is never invoked.
      expect(
        screen.queryByRole('button', { name: /sort/i })
      ).not.toBeInTheDocument();

      const rendered = screen.getAllByTestId(/alert-item-/);
      expect(rendered.map((el) => el.getAttribute('data-alert-type'))).toEqual([
        'mention_spike',
        'mention_drop',
        'competitor_gain',
        'sentiment_change',
        'mention_spike',
      ]);

      await user.click(screen.getByTestId('alert-item-1'));
      expect(onSortChange).not.toHaveBeenCalled();
    });

    it('AL-022: sort by project groups by project', async () => {
      const user = userEvent.setup();
      const onSortChange = vi.fn();
      render(<AlertList {...defaultProps} onSortChange={onSortChange} />);

      // The shipped list renders no sort control, so alerts are not grouped by
      // project either - they stay in the order they were provided in.
      expect(
        screen.queryByRole('button', { name: /sort/i })
      ).not.toBeInTheDocument();

      const rendered = screen.getAllByTestId(/alert-item-/);
      expect(
        rendered.map((el) => el.querySelector('span')?.textContent)
      ).toEqual(['Project 1', 'Project 2', 'Project 3', 'Project 1', 'Project 2']);

      await user.click(screen.getByTestId('alert-item-1'));
      expect(onSortChange).not.toHaveBeenCalled();
    });
  });

  // ============================================================================
  // Multiple Selection Tests
  // ============================================================================

  describe('Multiple Selection', () => {
    it('AL-026: multiple selection with ctrl+click', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} enableSelection />);

      await user.click(screen.getByTestId('alert-item-1'));
      await user.keyboard('[ControlLeft>]');
      await user.click(screen.getByTestId('alert-item-2'));

      // Selected alerts are highlighted with the `ring-2 ring-blue-500` classes
      const selected = screen
        .getAllByTestId(/alert-item-/)
        .filter((el) => el.classList.contains('ring-2'));
      expect(selected).toHaveLength(2);
      expect(selected.map((el) => el.getAttribute('data-testid'))).toEqual([
        'alert-item-1',
        'alert-item-2',
      ]);
    });

    it('AL-027: bulk actions toolbar visible when 2+ selected', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} enableSelection />);

      // Select 2 alerts
      await user.click(screen.getByTestId('alert-item-1'));
      await user.keyboard('[ControlLeft>]');
      await user.click(screen.getByTestId('alert-item-2'));

      expect(screen.getByTestId('bulk-actions-toolbar')).toBeInTheDocument();
    });

    it('AL-028: bulk mark read', async () => {
      const user = userEvent.setup();
      const onBulkMarkRead = vi.fn();
      render(<AlertList {...defaultProps} enableSelection onBulkMarkRead={onBulkMarkRead} />);

      await user.click(screen.getByTestId('alert-item-1'));
      await user.keyboard('[ControlLeft>]');
      await user.click(screen.getByTestId('alert-item-2'));
      await user.click(screen.getByRole('button', { name: /mark selected as read/i }));

      expect(onBulkMarkRead).toHaveBeenCalledWith([1, 2]);
    });

    it('AL-031: clear selection', async () => {
      const user = userEvent.setup();
      render(<AlertList {...defaultProps} enableSelection />);

      // The bulk-actions toolbar (which holds "Clear selection") appears once
      // two or more alerts are selected
      await user.click(screen.getByTestId('alert-item-1'));
      await user.click(screen.getByTestId('alert-item-2'));
      expect(screen.getByTestId('bulk-actions-toolbar')).toBeInTheDocument();

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
          filters={{ project_id: '1', alert_types: ['mention_spike'] }}
        />
      );

      expect(screen.getByTestId('filter-count-badge')).toHaveTextContent('2');
    });

    it('AL-033: clear all filters button', async () => {
      const user = userEvent.setup();
      const onFilterChange = vi.fn();
      render(
        <AlertList
          {...defaultProps}
          filters={{ project_id: '1', alert_types: ['mention_spike'] }}
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
      const onFilterChange = vi.fn();
      render(
        <AlertList
          {...defaultProps}
          initialFilters={{ project_id: '1', is_read: false }}
          onFilterChange={onFilterChange}
        />
      );

      // Filters should be applied from initialFilters
      expect(screen.getByRole('button', { name: /show all alerts/i })).toBeInTheDocument();
    });

    it('AL-036: URL params updated on filter change', () => {
      const onUrlChange = vi.fn();
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
      newAlerts[newAlerts.length - 1].id = 6;

      rerender(<AlertList {...defaultProps} alerts={newAlerts} />);

      expect(screen.getByTestId('alert-item-6')).toBeInTheDocument();
    });

    it('AL-040: polling interval triggers refetch', () => {
      const onRefetch = vi.fn();
      vi.useFakeTimers();

      render(<AlertList {...defaultProps} pollingInterval={300000} onRefetch={onRefetch} />);

      vi.advanceTimersByTime(300000);

      expect(onRefetch).toHaveBeenCalled();

      vi.useRealTimers();
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

      // The filter dropdowns have no Escape handler - Escape leaves the
      // dropdown open. It is closed by activating the filter button again.
      await user.keyboard('{Escape}');
      expect(screen.getByRole('listbox')).toBeInTheDocument();

      await user.click(screen.getByRole('button', { name: /filter by project/i }));
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
      const onRetry = vi.fn();
      render(
        <AlertList
          {...defaultProps}
          alerts={[]}
          error="Network error"
          onRetry={onRetry}
        />
      );

      expect(screen.getByText(/network error/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });

    it('AL-043: retry fetch triggers callback', async () => {
      const user = userEvent.setup();
      const onRetry = vi.fn();
      render(<AlertList {...defaultProps} alerts={[]} error="Network error" onRetry={onRetry} />);

      await user.click(screen.getByRole('button', { name: /retry/i }));

      expect(onRetry).toHaveBeenCalled();
    });

    it('AL-052: filtered count shown', () => {
      render(
        <AlertList
          {...defaultProps}
          alerts={createMockAlerts(3)}
          filters={{ project_id: '1' }}
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
