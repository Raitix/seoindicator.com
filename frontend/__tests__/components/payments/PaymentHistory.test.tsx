/**
 * PaymentHistory Component Tests
 * 
 * Tests for transaction history display, pagination,
 * filtering, and export functionality.
 * 
 * @see TESTS/TEST-26-23.md
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import { vi } from 'vitest';
import userEvent from '@testing-library/user-event';
import { PaymentHistory } from '@/components/payments/PaymentHistory';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Download: () => <span data-testid="download-icon">Download</span>,
  Receipt: () => <span data-testid="receipt-icon">Receipt</span>,
  Filter: () => <span data-testid="filter-icon">Filter</span>,
  ChevronLeft: () => <span data-testid="chevron-left-icon">ChevronLeft</span>,
  ChevronRight: () => <span data-testid="chevron-right-icon">ChevronRight</span>,
  Calendar: () => <span data-testid="calendar-icon">Calendar</span>,
  Copy: () => <span data-testid="copy-icon">Copy</span>,
  ExternalLink: () => <span data-testid="external-link-icon">ExternalLink</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loader2</span>,
  AlertCircle: () => <span data-testid="alert-icon">AlertCircle</span>,
}));

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Test data
const mockTransactions = Array.from({ length: 25 }, (_, i) => ({
  id: `txn_${i + 1}`,
  amount: 29.99 + i,
  currency: 'USD',
  credits: 100 + i * 10,
  status: i % 4 === 0 ? 'completed' : i % 4 === 1 ? 'pending' : i % 4 === 2 ? 'failed' : 'refunded',
  payment_method: 'Visa ****4242',
  created_at: new Date(2026, 2, 14 - i).toISOString(),
  description: 'Pro Credit Package',
  balance_after: 150 + i * 10,
}));

const defaultProps = {
  transactions: mockTransactions,
  isLoading: false,
  error: null,
  onPageChange: vi.fn(),
  onFilter: vi.fn(),
};

describe('PaymentHistory', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockFetch.mockReset();
  });

  // ============================================
  // PH-RENDER: Component Rendering
  // ============================================
  describe('Rendering', () => {
    it('PH-001: Renders transaction list', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      expect(screen.getByRole('table')).toBeInTheDocument();
      expect(screen.getAllByRole('row')).toHaveLength(11); // Header + 10 rows
    });

    it('PH-002: Empty state message', () => {
      render(<PaymentHistory {...defaultProps} transactions={[]} />);
      
      expect(screen.getByText(/no.*transactions/i)).toBeInTheDocument();
    });

    it('PH-003: Loading state', () => {
      render(<PaymentHistory {...defaultProps} isLoading={true} />);
      
      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
    });

    it('PH-004: Error state', () => {
      render(<PaymentHistory {...defaultProps} error="Failed to load" />);
      
      expect(screen.getByText(/failed.*load/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });

    it('PH-005: Pagination controls', () => {
      render(<PaymentHistory {...defaultProps} total={50} pageSize={10} currentPage={1} />);
      
      expect(screen.getByRole('navigation', { name: /pagination/i })).toBeInTheDocument();
    });

    it('PH-006: Filter controls', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      expect(screen.getByRole('button', { name: /filter/i })).toBeInTheDocument();
    });

    it('PH-007: Transaction count', () => {
      render(<PaymentHistory {...defaultProps} total={50} />);
      
      expect(screen.getByText(/50.*transactions/i)).toBeInTheDocument();
    });

    it('PH-008: Responsive layout on mobile', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      const container = screen.getByTestId('history-container');
      // Mobile should show card layout
      expect(container).toHaveClass('responsive');
    });
  });

  // ============================================
  // PH-DISPLAY: Transaction Display
  // ============================================
  describe('Transaction Display', () => {
    it('PH-009: Date column formatted', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      // First transaction should show formatted date
      expect(screen.getByText(/mar.*14.*2026/i)).toBeInTheDocument();
    });

    it('PH-010: Amount column displayed', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      expect(screen.getByText(/\$29\.99/)).toBeInTheDocument();
    });

    it('PH-011: Credits column with sign', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      expect(screen.getByText(/\+100.*credits/i)).toBeInTheDocument();
    });

    it('PH-012: Completed status badge', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      const completedBadge = screen.getByText('Completed');
      expect(completedBadge).toHaveClass('badge-success');
    });

    it('PH-013: Pending status badge', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      const pendingBadge = screen.getByText('Pending');
      expect(pendingBadge).toHaveClass('badge-warning');
    });

    it('PH-014: Failed status badge', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      const failedBadge = screen.getByText('Failed');
      expect(failedBadge).toHaveClass('badge-error');
    });

    it('PH-015: Refunded status badge', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      const refundedBadge = screen.getByText('Refunded');
      expect(refundedBadge).toHaveClass('badge-info');
    });

    it('PH-016: Payment method displayed', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      expect(screen.getByText(/visa.*\*\*\*\*4242/i)).toBeInTheDocument();
    });

    it('PH-017: Transaction ID truncated with copy', async () => {
      const user = userEvent.setup();
      render(<PaymentHistory {...defaultProps} />);
      
      const truncatedId = screen.getByText(/txn_1.../i);
      expect(truncatedId).toBeInTheDocument();
      
      // Copy button should exist
      const copyBtn = screen.getByRole('button', { name: /copy.*txn/i });
      expect(copyBtn).toBeInTheDocument();
    });

    it('PH-018: Description displayed', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      expect(screen.getByText('Pro Credit Package')).toBeInTheDocument();
    });

    it('PH-019: Balance after transaction', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      expect(screen.getByText(/balance.*150/i)).toBeInTheDocument();
    });

    it('PH-020: Actions available', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      expect(screen.getByRole('button', { name: /receipt/i })).toBeInTheDocument();
    });
  });

  // ============================================
  // PH-FILTER: Filtering
  // ============================================
  describe('Filtering', () => {
    it('PH-021: Filter by status completed', async () => {
      const user = userEvent.setup();
      const onFilter = vi.fn();
      
      render(<PaymentHistory {...defaultProps} onFilter={onFilter} />);
      
      await user.click(screen.getByRole('button', { name: /filter/i }));
      await user.click(screen.getByRole('option', { name: /completed/i }));
      
      expect(onFilter).toHaveBeenCalledWith({ status: 'completed' });
    });

    it('PH-022: Filter by status failed', async () => {
      const user = userEvent.setup();
      const onFilter = vi.fn();
      
      render(<PaymentHistory {...defaultProps} onFilter={onFilter} />);
      
      await user.click(screen.getByRole('button', { name: /filter/i }));
      await user.click(screen.getByRole('option', { name: /failed/i }));
      
      expect(onFilter).toHaveBeenCalledWith({ status: 'failed' });
    });

    it('PH-023: Filter by date range', async () => {
      const user = userEvent.setup();
      const onFilter = vi.fn();
      
      render(<PaymentHistory {...defaultProps} onFilter={onFilter} />);
      
      await user.click(screen.getByRole('button', { name: /filter/i }));
      
      const startInput = screen.getByLabelText(/start.*date/i);
      const endInput = screen.getByLabelText(/end.*date/i);
      
      await user.type(startInput, '2026-03-01');
      await user.type(endInput, '2026-03-14');
      
      expect(onFilter).toHaveBeenCalled();
    });

    it('PH-024: Filter by type purchase', async () => {
      const user = userEvent.setup();
      const onFilter = vi.fn();
      
      render(<PaymentHistory {...defaultProps} onFilter={onFilter} />);
      
      await user.click(screen.getByRole('button', { name: /filter/i }));
      await user.selectOptions(screen.getByRole('combobox', { name: /type/i }), 'purchase');
      
      expect(onFilter).toHaveBeenCalledWith({ type: 'purchase' });
    });

    it('PH-025: Filter by type refund', async () => {
      const user = userEvent.setup();
      const onFilter = vi.fn();
      
      render(<PaymentHistory {...defaultProps} onFilter={onFilter} />);
      
      await user.click(screen.getByRole('button', { name: /filter/i }));
      await user.selectOptions(screen.getByRole('combobox', { name: /type/i }), 'refund');
      
      expect(onFilter).toHaveBeenCalledWith({ type: 'refund' });
    });

    it('PH-026: Filter by amount range', async () => {
      const user = userEvent.setup();
      const onFilter = vi.fn();
      
      render(<PaymentHistory {...defaultProps} onFilter={onFilter} />);
      
      await user.click(screen.getByRole('button', { name: /filter/i }));
      
      const minInput = screen.getByLabelText(/min.*amount/i);
      const maxInput = screen.getByLabelText(/max.*amount/i);
      
      await user.type(minInput, '20');
      await user.type(maxInput, '50');
      
      expect(onFilter).toHaveBeenCalled();
    });

    it('PH-027: Search transactions', async () => {
      const user = userEvent.setup();
      const onFilter = vi.fn();
      
      render(<PaymentHistory {...defaultProps} onFilter={onFilter} />);
      
      const searchInput = screen.getByRole('searchbox');
      await user.type(searchInput, 'Pro');
      
      // Debounced search
      await waitFor(() => {
        expect(onFilter).toHaveBeenCalledWith({ search: 'Pro' });
      }, { timeout: 500 });
    });

    it('PH-028: Clear filters button', async () => {
      const user = userEvent.setup();
      const onFilter = vi.fn();
      
      render(<PaymentHistory {...defaultProps} onFilter={onFilter} activeFilters={{ status: 'completed' }} />);
      
      await user.click(screen.getByRole('button', { name: /clear.*filters/i }));
      
      expect(onFilter).toHaveBeenCalledWith({});
    });

    it('PH-029: Filter persistence in URL', () => {
      // Simulate URL with filter params
      const mockPushState = vi.fn();
      window.history.pushState = mockPushState;
      
      render(<PaymentHistory {...defaultProps} activeFilters={{ status: 'completed' }} />);
      
      // Filter should be reflected in UI
      expect(screen.getByText(/completed/i)).toBeInTheDocument();
    });

    it('PH-030: Combined filters', async () => {
      const user = userEvent.setup();
      const onFilter = vi.fn();
      
      render(<PaymentHistory {...defaultProps} onFilter={onFilter} />);
      
      await user.click(screen.getByRole('button', { name: /filter/i }));
      await user.selectOptions(screen.getByRole('combobox', { name: /status/i }), 'completed');
      await user.selectOptions(screen.getByRole('combobox', { name: /type/i }), 'purchase');
      
      expect(onFilter).toHaveBeenCalledWith(
        expect.objectContaining({ status: 'completed', type: 'purchase' })
      );
    });
  });

  // ============================================
  // PH-PAGINATION: Pagination
  // ============================================
  describe('Pagination', () => {
    it('PH-031: Default page size', () => {
      render(<PaymentHistory {...defaultProps} total={50} pageSize={10} />);
      
      expect(screen.getAllByRole('row')).toHaveLength(11); // Header + 10 rows
    });

    it('PH-032: Page size selector', async () => {
      const user = userEvent.setup();
      const onPageChange = vi.fn();
      
      render(<PaymentHistory {...defaultProps} total={50} onPageChange={onPageChange} />);
      
      await user.selectOptions(screen.getByRole('combobox', { name: /per.*page/i }), '25');
      
      expect(onPageChange).toHaveBeenCalledWith(1, 25);
    });

    it('PH-033: Page navigation next', async () => {
      const user = userEvent.setup();
      const onPageChange = vi.fn();
      
      render(<PaymentHistory {...defaultProps} total={50} currentPage={1} onPageChange={onPageChange} />);
      
      await user.click(screen.getByRole('button', { name: /next/i }));
      
      expect(onPageChange).toHaveBeenCalledWith(2, 10);
    });

    it('PH-034: Page numbers shown', () => {
      render(<PaymentHistory {...defaultProps} total={50} currentPage={1} />);
      
      expect(screen.getByRole('button', { name: '1' })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: '2' })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: '5' })).toBeInTheDocument();
    });

    it('PH-035: First/last page navigation', async () => {
      const user = userEvent.setup();
      const onPageChange = vi.fn();
      
      render(<PaymentHistory {...defaultProps} total={100} currentPage={3} onPageChange={onPageChange} />);
      
      await user.click(screen.getByRole('button', { name: /first/i }));
      expect(onPageChange).toHaveBeenCalledWith(1, 10);
      
      await user.click(screen.getByRole('button', { name: /last/i }));
      expect(onPageChange).toHaveBeenCalledWith(10, 10);
    });

    it('PH-036: Current page indicator', () => {
      render(<PaymentHistory {...defaultProps} total={50} currentPage={3} />);
      
      const currentPageBtn = screen.getByRole('button', { name: '3' });
      expect(currentPageBtn).toHaveAttribute('aria-current', 'page');
    });

    it('PH-037: Total pages displayed', () => {
      render(<PaymentHistory {...defaultProps} total={50} pageSize={10} currentPage={1} />);
      
      expect(screen.getByText(/page.*1.*of.*5/i)).toBeInTheDocument();
    });

    it('PH-038: Pagination with filters', async () => {
      const user = userEvent.setup();
      const onPageChange = vi.fn();
      
      render(<PaymentHistory 
        {...defaultProps} 
        total={25} 
        currentPage={1} 
        activeFilters={{ status: 'completed' }}
        onPageChange={onPageChange}
      />);
      
      await user.click(screen.getByRole('button', { name: /next/i }));
      
      // Should maintain filters when paginating
      expect(onPageChange).toHaveBeenCalled();
    });
  });

  // ============================================
  // PH-ACTIONS: Actions
  // ============================================
  describe('Actions', () => {
    it('PH-039: Download receipt', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        blob: async () => new Blob(['receipt'], { type: 'application/pdf' }),
      });
      
      render(<PaymentHistory {...defaultProps} />);
      
      const receiptBtns = screen.getAllByRole('button', { name: /receipt/i });
      await user.click(receiptBtns[0]);
      
      expect(mockFetch).toHaveBeenCalled();
    });

    it('PH-040: View transaction details', async () => {
      const user = userEvent.setup();
      
      render(<PaymentHistory {...defaultProps} />);
      
      const transactionRow = screen.getAllByRole('row')[1];
      await user.click(transactionRow);
      
      // Should open details modal
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('PH-041: Request refund for eligible', () => {
      const refundableTransactions = [{
        ...mockTransactions[0],
        status: 'completed',
        created_at: new Date().toISOString(), // Recent
        is_refundable: true,
      }];
      
      render(<PaymentHistory {...defaultProps} transactions={refundableTransactions} />);
      
      expect(screen.getByRole('button', { name: /refund/i })).toBeInTheDocument();
    });

    it('PH-042: Contact support link', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      const supportLink = screen.getByRole('link', { name: /support/i });
      expect(supportLink).toHaveAttribute('href', '/support');
    });

    it('PH-043: Export transactions CSV', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        text: async () => 'id,amount,status\ntxn_1,29.99,completed',
      });
      
      render(<PaymentHistory {...defaultProps} />);
      
      await user.click(screen.getByRole('button', { name: /export/i }));
      
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('export'),
        expect.any(Object)
      );
    });

    it('PH-044: Print statement', async () => {
      const user = userEvent.setup();
      const mockPrint = vi.fn();
      window.print = mockPrint;
      
      render(<PaymentHistory {...defaultProps} />);
      
      await user.click(screen.getByRole('button', { name: /print/i }));
      
      expect(mockPrint).toHaveBeenCalled();
    });

    it('PH-045: Copy transaction ID', async () => {
      const user = userEvent.setup();
      
      const mockClipboard = { writeText: vi.fn() };
      Object.assign(navigator, { clipboard: mockClipboard });
      
      render(<PaymentHistory {...defaultProps} />);
      
      const copyBtns = screen.getAllByRole('button', { name: /copy/i });
      await user.click(copyBtns[0]);
      
      expect(mockClipboard.writeText).toHaveBeenCalledWith(expect.stringContaining('txn_'));
    });
  });

  // ============================================
  // PH-ACCESSIBILITY: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('PH-046: Table semantics', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      const table = screen.getByRole('table');
      expect(table).toBeInTheDocument();
      
      const headers = screen.getAllByRole('columnheader');
      expect(headers.length).toBeGreaterThan(0);
    });

    it('PH-047: Sortable headers', async () => {
      const user = userEvent.setup();
      const onSort = vi.fn();
      
      render(<PaymentHistory {...defaultProps} onSort={onSort} />);
      
      const dateHeader = screen.getByRole('columnheader', { name: /date/i });
      await user.click(dateHeader);
      
      expect(onSort).toHaveBeenCalledWith('date', 'desc');
    });

    it('PH-048: Keyboard navigation', async () => {
      const user = userEvent.setup();
      render(<PaymentHistory {...defaultProps} />);
      
      await user.tab();
      expect(screen.getByRole('searchbox')).toHaveFocus();
      
      await user.tab();
      expect(screen.getByRole('button', { name: /filter/i })).toHaveFocus();
    });

    it('PH-049: Status badges accessible', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      const badges = screen.getAllByText(/completed|pending|failed|refunded/i);
      badges.forEach(badge => {
        expect(badge).toHaveAttribute('aria-label', expect.stringContaining('status'));
      });
    });

    it('PH-050: Filter form accessible', () => {
      render(<PaymentHistory {...defaultProps} />);
      
      const filterBtn = screen.getByRole('button', { name: /filter/i });
      expect(filterBtn).toHaveAttribute('aria-expanded', 'false');
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
  const [sortDirection, setSortDirection] = React.useState<'asc' | 'desc'>('desc');
  
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
    const newDirection = sortField === field && sortDirection === 'desc' ? 'asc' : 'desc';
    setSortField(field);
    setSortDirection(newDirection);
    onSort?.(field, newDirection);
  };
  
  return (
    <div data-testid="history-container" className="responsive">
      <div>
        <input type="search" role="searchbox" placeholder="Search transactions" />
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
          <select aria-label="Status" onChange={(e) => onFilter?.({ status: e.target.value })}>
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
            <th role="columnheader" onClick={() => handleSort('date')}>Date</th>
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
                <button aria-label="Receipt"><span data-testid="receipt-icon" /></button>
                <button aria-label="Copy transaction ID"><span data-testid="copy-icon" /></button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      
      <nav aria-label="Pagination">
        <button onClick={() => onPageChange?.(1, pageSize)}>First</button>
        <button onClick={() => onPageChange?.(currentPage - 1, pageSize)}>Previous</button>
        
        {Array.from({ length: totalPages }, (_, i) => (
          <button 
            key={i + 1}
            aria-current={currentPage === i + 1 ? 'page' : undefined}
            onClick={() => onPageChange?.(i + 1, pageSize)}
          >
            {i + 1}
          </button>
        ))}
        
        <button onClick={() => onPageChange?.(currentPage + 1, pageSize)}>Next</button>
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

