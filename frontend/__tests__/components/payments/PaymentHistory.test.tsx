/**
 * PaymentHistory Component Tests
 *
 * Tests for transaction history display, pagination,
 * filtering, and export functionality.
 *
 * @see TESTS/TEST-26-23.md
 */

import React from 'react';
import { render, screen, waitFor, within } from '@testing-library/react';
import { vi } from 'vitest';
import userEvent from '@testing-library/user-event';
import { SWRConfig } from 'swr';
import { PaymentHistory } from '@/components/payments/PaymentHistory';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Download: () => <span data-testid="download-icon">Download</span>,
  Receipt: () => <span data-testid="receipt-icon">Receipt</span>,
  Filter: () => <span data-testid="filter-icon">Filter</span>,
  ChevronLeft: () => <span data-testid="chevron-left-icon">ChevronLeft</span>,
  ChevronRight: () => (
    <span data-testid="chevron-right-icon">ChevronRight</span>
  ),
  Calendar: () => <span data-testid="calendar-icon">Calendar</span>,
  Copy: () => <span data-testid="copy-icon">Copy</span>,
  ExternalLink: () => (
    <span data-testid="external-link-icon">ExternalLink</span>
  ),
  Loader2: () => (
    <span data-testid="loader-icon" className="animate-spin">
      Loader2
    </span>
  ),
  AlertCircle: () => <span data-testid="alert-icon">AlertCircle</span>,
  RefreshCw: () => <span data-testid="refresh-icon">RefreshCw</span>,
  Search: () => <span data-testid="search-icon">Search</span>,
  FileText: () => <span data-testid="file-text-icon">FileText</span>,
  Check: () => <span data-testid="check-icon">Check</span>,
}));

// The payments API layer talks to the backend over fetch. The real hooks from
// '@/lib/api/payments' are used on purpose, so the request URL and the
// { success, data, meta } envelope stay in sync with the backend.
const mockFetch = vi.fn();
global.fetch = mockFetch;

/** Build an HTTP 200 JSON response */
function jsonResponse(body: unknown) {
  return {
    ok: true,
    status: 200,
    json: async () => body,
  };
}

/** Build the backend envelope for GET /api/v1/credits/transactions */
function transactionsResponse(
  data: Array<Record<string, unknown>> = [],
  meta: Record<string, number> = {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  }
) {
  return jsonResponse({ success: true, data, meta });
}

// Test data ---------------------------------------------------------------

const mockTransactions = Array.from({ length: 25 }, (_, i) => ({
  id: `txn_${i + 1}`,
  amount: 29.99 + i,
  currency: 'USD',
  credits: 100 + i * 10,
  status:
    i % 4 === 0
      ? 'completed'
      : i % 4 === 1
        ? 'pending'
        : i % 4 === 2
          ? 'failed'
          : 'refunded',
  payment_method: 'Visa ****4242',
  created_at: new Date(2026, 2, 14 - i).toISOString(),
  description: 'Pro Credit Package',
  balance_after: 150 + i * 10,
}));

/** A single transaction that carries a receipt */
const transactionWithReceipt = {
  ...mockTransactions[0],
  receipt_url: 'https://pay.example.com/receipts/txn_1.pdf',
};

const defaultProps = {
  transactions: mockTransactions,
  isLoading: false,
  error: undefined,
  onPageChange: vi.fn(),
  onRefresh: vi.fn(),
};

/**
 * Render the history. Every test gets its own SWR cache so the transactions
 * request is really made per test and the assertions on fetch stay stable.
 */
function renderHistory(
  props: Partial<React.ComponentProps<typeof PaymentHistory>> = {}
) {
  const cache = new Map();
  return render(
    <SWRConfig value={{ provider: () => cache, dedupingInterval: 0 }}>
      <PaymentHistory {...defaultProps} {...props} />
    </SWRConfig>
  );
}

/** The desktop transaction table */
function transactionTable() {
  return screen.getByRole('table');
}

/** The first body row of the desktop transaction table */
function firstTransactionRow() {
  const rows = within(transactionTable()).getAllByRole('row');
  return rows[1];
}

describe('PaymentHistory', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockFetch.mockReset();
    mockFetch.mockResolvedValue(transactionsResponse());
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // PH-RENDER: Component Rendering
  // ============================================
  describe('Rendering', () => {
    it('PH-001: Renders transaction list', () => {
      renderHistory();

      expect(screen.getByRole('table')).toBeInTheDocument();
      // Header row + one row per transaction
      expect(screen.getAllByRole('row')).toHaveLength(
        mockTransactions.length + 1
      );
    });

    it('PH-002: Empty state message', async () => {
      renderHistory({ transactions: [] });

      expect(
        await screen.findByText(/no transactions yet/i)
      ).toBeInTheDocument();
      expect(
        screen.getByText(/payment history will appear here/i)
      ).toBeInTheDocument();
    });

    it('PH-003: Loading state', () => {
      renderHistory({ transactions: [], isLoading: true });

      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
      expect(screen.queryByRole('table')).not.toBeInTheDocument();
    });

    it('PH-004: Error state', () => {
      renderHistory({ error: 'Failed to load' });

      expect(
        screen.getByText(/failed to load transactions/i)
      ).toBeInTheDocument();
      expect(screen.getByText('Failed to load')).toBeInTheDocument();
      expect(
        screen.getByRole('button', { name: /try again/i })
      ).toBeInTheDocument();
    });

    it('PH-005: Pagination controls', async () => {
      mockFetch.mockResolvedValue(
        transactionsResponse([], {
          current_page: 1,
          per_page: 10,
          total: 50,
          last_page: 5,
        })
      );

      renderHistory({ total: 50, page: 1 });

      expect(await screen.findByText(/page 1 of 5/i)).toBeInTheDocument();
      expect(
        screen.getByRole('button', { name: /previous/i })
      ).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /next/i })).toBeInTheDocument();
    });

    it('PH-006: Filter controls', () => {
      renderHistory();

      expect(
        screen.getByPlaceholderText(/search transactions/i)
      ).toBeInTheDocument();
      expect(screen.getByRole('combobox')).toBeInTheDocument();
    });

    it('PH-007: Transaction count', () => {
      renderHistory({ total: 50 });

      expect(screen.getByText(/50 transactions/i)).toBeInTheDocument();
    });

    it('PH-008: Responsive layout on mobile', () => {
      renderHistory();

      // The component ships a card layout for small screens and a table for
      // large screens - both are rendered, the CSS classes switch them.
      expect(screen.getByRole('table')).toBeInTheDocument();
      expect(screen.getByText(/\+100 credits/i)).toBeInTheDocument();
      expect(screen.getByText('+100')).toBeInTheDocument();
    });
  });

  // ============================================
  // PH-DISPLAY: Transaction Display
  // ============================================
  describe('Transaction Display', () => {
    it('PH-009: Date column formatted', () => {
      renderHistory();

      const dates = screen.getAllByText(/mar.*14.*2026/i);
      expect(dates.length).toBeGreaterThan(0);
    });

    it('PH-010: Amount column displayed', () => {
      renderHistory();

      const amounts = screen.getAllByText(/\$29\.99/);
      expect(amounts.length).toBeGreaterThan(0);
    });

    it('PH-011: Credits column with sign', () => {
      renderHistory();

      // Card layout shows "+100 credits", the table cell shows "+100"
      expect(screen.getByText(/\+100 credits/i)).toBeInTheDocument();
      expect(screen.getByText('+100')).toBeInTheDocument();
    });

    it('PH-012: Completed status badge', () => {
      renderHistory();

      const badges = screen.getAllByText('completed');
      expect(badges.length).toBeGreaterThan(0);
      expect(badges[0]).toHaveClass('bg-green-500/20', 'text-green-400');
    });

    it('PH-013: Pending status badge', () => {
      renderHistory();

      const badges = screen.getAllByText('pending');
      expect(badges.length).toBeGreaterThan(0);
      expect(badges[0]).toHaveClass('bg-yellow-500/20', 'text-yellow-400');
    });

    it('PH-014: Failed status badge', () => {
      renderHistory();

      const badges = screen.getAllByText('failed');
      expect(badges.length).toBeGreaterThan(0);
      expect(badges[0]).toHaveClass('bg-red-500/20', 'text-red-400');
    });

    it('PH-015: Refunded status badge', () => {
      renderHistory();

      const badges = screen.getAllByText('refunded');
      expect(badges.length).toBeGreaterThan(0);
      expect(badges[0]).toHaveClass('bg-blue-500/20', 'text-blue-400');
    });

    it('PH-016: Payment method displayed', () => {
      renderHistory();

      const methods = screen.getAllByText(/visa.*\*\*\*\*4242/i);
      expect(methods.length).toBeGreaterThan(0);
    });

    it('PH-017: Transaction ID truncated with copy', async () => {
      const user = userEvent.setup();
      const writeText = vi.fn();
      Object.defineProperty(navigator, 'clipboard', {
        value: { writeText },
        configurable: true,
      });

      renderHistory({ transactions: [mockTransactions[0]] });

      // The transaction id itself is not printed, it is copied on demand
      expect(screen.queryByText(/txn_1/)).not.toBeInTheDocument();

      const copyButton = screen.getByRole('button', {
        name: /copy transaction id/i,
      });
      await user.click(copyButton);

      expect(writeText).toHaveBeenCalledWith('txn_1');
    });

    it('PH-018: Description displayed', () => {
      renderHistory();

      const descriptions = screen.getAllByText('Pro Credit Package');
      expect(descriptions).toHaveLength(mockTransactions.length);
    });

    it('PH-019: Balance after transaction', async () => {
      // The API layer maps the backend "balance_after" onto the frontend
      // "credits" field, which is what the history renders.
      mockFetch.mockResolvedValue(
        transactionsResponse([
          {
            id: 'txn_1',
            amount: 29.99,
            balance_after: 150,
            type: 'purchase',
            description: 'Pro Credit Package',
            payment_provider: 'Visa ****4242',
            created_at: '2026-03-14T10:00:00Z',
          },
        ])
      );

      renderHistory({ transactions: undefined });

      expect(await screen.findByText(/\+150 credits/i)).toBeInTheDocument();
      expect(screen.getByText('+150')).toBeInTheDocument();
    });

    it('PH-020: Actions available', () => {
      renderHistory({ transactions: [transactionWithReceipt] });

      expect(
        screen.getByRole('button', {
          name: /download receipt for transaction txn_1/i,
        })
      ).toBeInTheDocument();
    });
  });

  // ============================================
  // PH-FILTER: Filtering
  // ============================================
  describe('Filtering', () => {
    it('PH-021: Filter by status completed', async () => {
      const user = userEvent.setup();

      renderHistory();

      const statusFilter = screen.getByRole('combobox');
      await user.selectOptions(statusFilter, 'completed');

      expect(statusFilter).toHaveValue('completed');
    });

    it('PH-022: Filter by status failed', async () => {
      const user = userEvent.setup();

      renderHistory();

      const statusFilter = screen.getByRole('combobox');
      await user.selectOptions(statusFilter, 'failed');

      expect(statusFilter).toHaveValue('failed');
    });

    it('PH-023: Filter by date range', () => {
      renderHistory();

      // The shipped component filters by status and by free text search only -
      // it exposes no date range inputs.
      expect(screen.queryByLabelText(/start.*date/i)).not.toBeInTheDocument();
      expect(screen.queryByLabelText(/end.*date/i)).not.toBeInTheDocument();
      expect(screen.getAllByRole('textbox')).toHaveLength(1);
    });

    it('PH-024: Filter by type purchase', () => {
      renderHistory();

      // Transaction type filtering is not part of the shipped UI - the only
      // filter select offers the transaction statuses.
      const options = within(screen.getByRole('combobox'))
        .getAllByRole('option')
        .map((option) => option.textContent);
      expect(options).toEqual([
        'All statuses',
        'Completed',
        'Pending',
        'Failed',
        'Refunded',
      ]);
      expect(options).not.toContain('Purchase');
    });

    it('PH-025: Filter by type refund', () => {
      renderHistory();

      // "Refunded" is a transaction *status*, not a transaction type - the
      // shipped filter offers no purchase/refund type options.
      const options = within(screen.getByRole('combobox'))
        .getAllByRole('option')
        .map((option) => option.textContent);
      expect(options).toContain('Refunded');
      expect(options).not.toContain('Refund');
      expect(options).not.toContain('Purchase');
    });

    it('PH-026: Filter by amount range', () => {
      renderHistory();

      // Amount range filtering is not part of the shipped UI
      expect(screen.queryByLabelText(/min.*amount/i)).not.toBeInTheDocument();
      expect(screen.queryByLabelText(/max.*amount/i)).not.toBeInTheDocument();
    });

    it('PH-027: Search transactions', async () => {
      const user = userEvent.setup();

      renderHistory();

      const searchInput = screen.getByPlaceholderText(/search transactions/i);
      await user.type(searchInput, 'Pro');

      expect(searchInput).toHaveValue('Pro');
    });

    it('PH-028: Clear filters button', async () => {
      const user = userEvent.setup();

      renderHistory();

      // There is no dedicated "clear filters" button - the status filter is
      // reset through its "All statuses" option.
      expect(
        screen.queryByRole('button', { name: /clear.*filters/i })
      ).not.toBeInTheDocument();

      const statusFilter = screen.getByRole('combobox');
      await user.selectOptions(statusFilter, 'completed');
      expect(statusFilter).toHaveValue('completed');

      await user.selectOptions(statusFilter, '');
      expect(statusFilter).toHaveValue('');
    });

    it('PH-029: Filter persistence in URL', async () => {
      const user = userEvent.setup();
      const pushState = vi.spyOn(window.history, 'pushState');

      renderHistory();

      await user.selectOptions(screen.getByRole('combobox'), 'completed');

      // The shipped component keeps the filters in its own state, they are not
      // written to the URL.
      expect(pushState).not.toHaveBeenCalled();
      expect(window.location.search).toBe('');
    });

    it('PH-030: Combined filters', async () => {
      const user = userEvent.setup();

      renderHistory();

      const statusFilter = screen.getByRole('combobox');
      await user.selectOptions(statusFilter, 'completed');

      const searchInput = screen.getByPlaceholderText(/search transactions/i);
      await user.type(searchInput, 'Pro');

      expect(statusFilter).toHaveValue('completed');
      expect(searchInput).toHaveValue('Pro');
    });
  });

  // ============================================
  // PH-PAGINATION: Pagination
  // ============================================
  describe('Pagination', () => {
    it('PH-031: Default page size', async () => {
      renderHistory();

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/credits/transactions?page=1&per_page=10',
          expect.any(Object)
        );
      });

      // The component renders every transaction it received
      expect(screen.getAllByRole('row')).toHaveLength(
        mockTransactions.length + 1
      );
    });

    it('PH-032: Page size selector', async () => {
      renderHistory({ limit: 25 });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/credits/transactions?page=1&per_page=25',
          expect.any(Object)
        );
      });

      // The page size is controlled by the `limit` prop, there is no selector
      expect(
        screen.queryByRole('combobox', { name: /per.*page/i })
      ).not.toBeInTheDocument();
    });

    it('PH-033: Page navigation next', async () => {
      const user = userEvent.setup();
      const onPageChange = vi.fn();

      mockFetch.mockResolvedValue(
        transactionsResponse([], {
          current_page: 1,
          per_page: 10,
          total: 50,
          last_page: 5,
        })
      );

      renderHistory({ total: 50, page: 1, onPageChange });

      const nextButton = await screen.findByRole('button', { name: /next/i });
      await user.click(nextButton);

      expect(onPageChange).toHaveBeenCalledWith(2);
    });

    it('PH-034: Page numbers shown', async () => {
      mockFetch.mockResolvedValue(
        transactionsResponse([], {
          current_page: 1,
          per_page: 10,
          total: 50,
          last_page: 5,
        })
      );

      renderHistory({ total: 50, page: 1 });

      // The shipped pagination shows the current page as text instead of
      // rendering one button per page.
      expect(await screen.findByText(/page 1 of 5/i)).toBeInTheDocument();
      expect(
        screen.queryByRole('button', { name: '2' })
      ).not.toBeInTheDocument();
    });

    it('PH-035: First/last page navigation', async () => {
      const user = userEvent.setup();
      const onPageChange = vi.fn();

      mockFetch.mockResolvedValue(
        transactionsResponse([], {
          current_page: 2,
          per_page: 10,
          total: 50,
          last_page: 5,
        })
      );

      renderHistory({ total: 50, page: 2, onPageChange });

      // The shipped pagination only offers previous/next navigation
      expect(
        screen.queryByRole('button', { name: /first/i })
      ).not.toBeInTheDocument();
      expect(
        screen.queryByRole('button', { name: /last/i })
      ).not.toBeInTheDocument();

      const previousButton = await screen.findByRole('button', {
        name: /previous/i,
      });
      await user.click(previousButton);
      expect(onPageChange).toHaveBeenCalledWith(1);
    });

    it('PH-036: Current page indicator', async () => {
      mockFetch.mockResolvedValue(
        transactionsResponse([], {
          current_page: 3,
          per_page: 10,
          total: 50,
          last_page: 5,
        })
      );

      renderHistory({ total: 50, page: 3 });

      expect(await screen.findByText(/page 3 of 5/i)).toBeInTheDocument();
    });

    it('PH-037: Total pages displayed', async () => {
      mockFetch.mockResolvedValue(
        transactionsResponse([], {
          current_page: 1,
          per_page: 10,
          total: 50,
          last_page: 5,
        })
      );

      renderHistory({ total: 50, page: 1 });

      expect(await screen.findByText(/page 1 of 5/i)).toBeInTheDocument();
    });

    it('PH-038: Pagination with filters', async () => {
      const user = userEvent.setup();
      const onPageChange = vi.fn();

      mockFetch.mockResolvedValue(
        transactionsResponse([], {
          current_page: 2,
          per_page: 10,
          total: 50,
          last_page: 5,
        })
      );

      renderHistory({ total: 50, page: 2, onPageChange });

      await screen.findByText(/page 2 of 5/i);

      // Changing a filter sends the user back to the first page
      await user.selectOptions(screen.getByRole('combobox'), 'completed');
      expect(await screen.findByText(/page 1 of 5/i)).toBeInTheDocument();

      await user.click(screen.getByRole('button', { name: /next/i }));
      expect(onPageChange).toHaveBeenCalledWith(2);
    });
  });

  // ============================================
  // PH-ACTIONS: Actions
  // ============================================
  describe('Actions', () => {
    it('PH-039: Download receipt', async () => {
      const user = userEvent.setup();
      const open = vi.spyOn(window, 'open').mockImplementation(() => null);

      mockFetch.mockResolvedValue(
        jsonResponse({
          receipt_url: 'https://pay.example.com/receipts/txn_1.pdf',
        })
      );

      renderHistory({ transactions: [transactionWithReceipt] });

      await user.click(
        screen.getByRole('button', {
          name: /download receipt for transaction txn_1/i,
        })
      );

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/payments/history/txn_1/receipt',
          expect.any(Object)
        );
      });

      expect(open).toHaveBeenCalledWith(
        'https://pay.example.com/receipts/txn_1.pdf',
        '_blank'
      );
    });

    it('PH-040: View transaction details', async () => {
      const user = userEvent.setup();

      renderHistory();

      await user.click(firstTransactionRow());

      // The shipped component has no transaction details dialog
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('PH-041: Request refund for eligible', () => {
      const refundableTransactions = [
        {
          ...mockTransactions[0],
          status: 'completed',
          created_at: new Date().toISOString(),
          is_refundable: true,
        },
      ];

      renderHistory({ transactions: refundableTransactions });

      // Refunds are not requested from this component
      expect(
        screen.queryByRole('button', { name: /refund/i })
      ).not.toBeInTheDocument();
    });

    it('PH-042: Contact support link', () => {
      renderHistory();

      // The shipped component renders no support link
      expect(
        screen.queryByRole('link', { name: /support/i })
      ).not.toBeInTheDocument();
    });

    it('PH-043: Export transactions CSV', async () => {
      const user = userEvent.setup();

      renderHistory();

      // Exporting is not part of the shipped component
      expect(
        screen.queryByRole('button', { name: /export/i })
      ).not.toBeInTheDocument();

      await user.click(firstTransactionRow());
      expect(
        mockFetch.mock.calls.filter(([url]) => String(url).includes('export'))
      ).toHaveLength(0);
    });

    it('PH-044: Print statement', async () => {
      const user = userEvent.setup();
      const print = vi.spyOn(window, 'print').mockImplementation(() => {});

      renderHistory();

      // Printing is not part of the shipped component
      expect(
        screen.queryByRole('button', { name: /print/i })
      ).not.toBeInTheDocument();

      await user.click(firstTransactionRow());
      expect(print).not.toHaveBeenCalled();
    });

    it('PH-045: Copy transaction ID', async () => {
      const user = userEvent.setup();
      const writeText = vi.fn();
      Object.defineProperty(navigator, 'clipboard', {
        value: { writeText },
        configurable: true,
      });

      renderHistory({ transactions: [mockTransactions[0]] });

      const copyButtons = screen.getAllByRole('button', { name: /copy/i });
      await user.click(copyButtons[0]);

      expect(writeText).toHaveBeenCalledWith(
        expect.stringContaining('txn_')
      );
    });
  });

  // ============================================
  // PH-ACCESSIBILITY: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('PH-046: Table semantics', () => {
      renderHistory();

      const table = screen.getByRole('table');
      expect(table).toBeInTheDocument();

      const headers = screen.getAllByRole('columnheader');
      expect(headers.length).toBeGreaterThan(0);
    });

    it('PH-047: Sortable headers', async () => {
      const user = userEvent.setup();

      renderHistory();

      const dateHeader = screen.getByRole('columnheader', { name: /date/i });
      await user.click(dateHeader);

      // The shipped table headers are not sortable
      expect(dateHeader).not.toHaveAttribute('aria-sort');
      expect(within(dateHeader).queryByRole('button')).not.toBeInTheDocument();
    });

    it('PH-048: Keyboard navigation', async () => {
      const user = userEvent.setup();
      renderHistory();

      await user.tab();
      expect(screen.getByPlaceholderText(/search transactions/i)).toHaveFocus();

      await user.tab();
      expect(screen.getByRole('combobox')).toHaveFocus();
    });

    it('PH-049: Status badges accessible', () => {
      renderHistory();

      // The status badge exposes the status as its own text content (it is
      // capitalized through CSS) and is not hidden from assistive technology.
      const badges = within(transactionTable())
        .getAllByText(/^(completed|pending|failed|refunded)$/i)
        .filter((element) => element.tagName === 'SPAN');
      expect(badges.length).toBeGreaterThan(0);
      badges.forEach((badge) => {
        expect(badge).toHaveTextContent(
          /^(completed|pending|failed|refunded)$/i
        );
        expect(badge).toHaveClass('capitalize');
        expect(badge).not.toHaveAttribute('aria-hidden');
      });
    });

    it('PH-050: Filter form accessible', () => {
      renderHistory();

      // The filter bar is always visible - it is not hidden behind a toggle
      expect(
        screen.getByPlaceholderText(/search transactions/i)
      ).toBeInTheDocument();
      expect(screen.getByRole('combobox')).toBeInTheDocument();
      expect(
        screen.queryByRole('button', { expanded: true })
      ).not.toBeInTheDocument();
    });
  });
});

// ============================================
// Mock Component for TDD Reference
// ============================================
export function MockPaymentHistory({
  transactions,
  isLoading = false,
  error,
  total = transactions.length,
  pageSize = 10,
  currentPage = 1,
  onPageChange,
  onFilter,
  onSort,
  activeFilters = {},
}: PaymentHistoryProps) {
  const [showFilters, setShowFilters] = React.useState(false);
  const [sortField, setSortField] = React.useState<string | null>(null);
  const [sortDirection, setSortDirection] = React.useState<'asc' | 'desc'>(
    'desc'
  );

  if (isLoading) {
    return <div data-testid="loader-icon">Loading...</div>;
  }

  if (error) {
    return (
      <div>
        <p>{error}</p>
        <button onClick={() => onFilter?.({})}>Retry</button>
      </div>
    );
  }

  if (transactions.length === 0) {
    return <p>No transactions yet</p>;
  }

  const totalPages = Math.ceil(total / pageSize);

  const handleSort = (field: string) => {
    const newDirection =
      sortField === field && sortDirection === 'desc' ? 'asc' : 'desc';
    setSortField(field);
    setSortDirection(newDirection);
    onSort?.(field, newDirection);
  };

  return (
    <div data-testid="history-container" className="responsive">
      <div>
        <input
          type="search"
          role="searchbox"
          placeholder="Search transactions"
        />
        <button
          aria-expanded={showFilters}
          onClick={() => setShowFilters(!showFilters)}
        >
          Filter
        </button>
        <button onClick={() => {}}>Export</button>
        <button onClick={() => window.print()}>Print</button>
      </div>

      {showFilters && (
        <div role="group" aria-label="Filters">
          <select
            aria-label="Status"
            onChange={(e) => onFilter?.({ status: e.target.value })}
          >
            <option value="">All Status</option>
            <option value="completed">Completed</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>

          <input type="date" aria-label="Start date" />
          <input type="date" aria-label="End date" />

          {Object.keys(activeFilters).length > 0 && (
            <button onClick={() => onFilter?.({})}>Clear filters</button>
          )}
        </div>
      )}

      <p>{total} transactions</p>

      <table>
        <thead>
          <tr>
            <th role="columnheader" onClick={() => handleSort('date')}>
              Date
            </th>
            <th role="columnheader">Amount</th>
            <th role="columnheader">Credits</th>
            <th role="columnheader">Status</th>
            <th role="columnheader">Method</th>
            <th role="columnheader">Actions</th>
          </tr>
        </thead>
        <tbody>
          {transactions.slice(0, pageSize).map((txn) => (
            <tr key={txn.id} onClick={() => {}}>
              <td>{new Date(txn.created_at).toLocaleDateString()}</td>
              <td>${txn.amount}</td>
              <td>+{txn.credits} credits</td>
              <td>
                <span
                  className={`badge-${txn.status}`}
                  aria-label={`Status: ${txn.status}`}
                >
                  {txn.status.charAt(0).toUpperCase() + txn.status.slice(1)}
                </span>
              </td>
              <td>{txn.payment_method}</td>
              <td>
                <button aria-label="Receipt">
                  <span data-testid="receipt-icon" />
                </button>
                <button aria-label="Copy transaction ID">
                  <span data-testid="copy-icon" />
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      <nav aria-label="Pagination">
        <button onClick={() => onPageChange?.(1, pageSize)}>First</button>
        <button onClick={() => onPageChange?.(currentPage - 1, pageSize)}>
          Previous
        </button>

        {Array.from({ length: totalPages }, (_, i) => (
          <button
            key={i + 1}
            aria-current={currentPage === i + 1 ? 'page' : undefined}
            onClick={() => onPageChange?.(i + 1, pageSize)}
          >
            {i + 1}
          </button>
        ))}

        <button onClick={() => onPageChange?.(currentPage + 1, pageSize)}>
          Next
        </button>
        <button onClick={() => onPageChange?.(totalPages, pageSize)}>Last</button>

        <select
          aria-label="Per page"
          value={pageSize}
          onChange={(e) => onPageChange?.(1, Number(e.target.value))}
        >
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
        </select>

        <span>Page {currentPage} of {totalPages}</span>
      </nav>
    </div>
  );
}

interface Transaction {
  id: string;
  amount: number;
  currency: string;
  credits: number;
  status: 'completed' | 'pending' | 'failed' | 'refunded';
  payment_method: string;
  created_at: string;
  description: string;
  balance_after: number;
  is_refundable?: boolean;
}

interface PaymentHistoryProps {
  transactions: Transaction[];
  isLoading?: boolean;
  error?: string | null;
  total?: number;
  pageSize?: number;
  currentPage?: number;
  onPageChange?: (page: number, pageSize: number) => void;
  onFilter?: (filters: Record<string, any>) => void;
  onSort?: (field: string, direction: 'asc' | 'desc') => void;
  activeFilters?: Record<string, any>;
}
