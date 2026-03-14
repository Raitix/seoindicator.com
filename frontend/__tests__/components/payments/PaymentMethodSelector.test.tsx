/**
 * PaymentMethodSelector Component Tests
 * 
 * Tests for saved payment methods selection, card management,
 * and adding new payment methods.
 * 
 * @see TESTS/TEST-26-23.md
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { PaymentMethodSelector } from '@/components/payments/PaymentMethodSelector';

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  CreditCard: () => <span data-testid="credit-card-icon">CreditCard</span>,
  Plus: () => <span data-testid="plus-icon">Plus</span>,
  Check: () => <span data-testid="check-icon">Check</span>,
  Trash2: () => <span data-testid="trash-icon">Trash2</span>,
  Star: () => <span data-testid="star-icon">Star</span>,
  AlertTriangle: () => <span data-testid="alert-icon">AlertTriangle</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loader2</span>,
}));

// Mock fetch
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Test data
const mockMethods = [
  {
    id: 'pm_visa',
    type: 'card',
    card: {
      brand: 'visa',
      last4: '4242',
      exp_month: 12,
      exp_year: 2025,
    },
    is_default: true,
  },
  {
    id: 'pm_mastercard',
    type: 'card',
    card: {
      brand: 'mastercard',
      last4: '5555',
      exp_month: 6,
      exp_year: 2026,
    },
    is_default: false,
  },
];

const defaultProps = {
  methods: mockMethods,
  selectedId: null,
  onMethodSelect: jest.fn(),
  onAddNew: jest.fn(),
};

describe('PaymentMethodSelector', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
  });

  // ============================================
  // PMS-RENDER: Component Rendering
  // ============================================
  describe('Rendering', () => {
    it('PMS-001: Renders saved methods', () => {
      render(<PaymentMethodSelector {...defaultProps} />);
      
      expect(screen.getByText('Visa')).toBeInTheDocument();
      expect(screen.getByText('Mastercard')).toBeInTheDocument();
      expect(screen.getByText('****4242')).toBeInTheDocument();
    });

    it('PMS-002: No saved methods shows CTA', () => {
      render(<PaymentMethodSelector {...defaultProps} methods={[]} />);
      
      expect(screen.getByText(/add.*payment.*method/i)).toBeInTheDocument();
    });

    it('PMS-003: Selected method highlighted', () => {
      render(<PaymentMethodSelector {...defaultProps} selectedId="pm_visa" />);
      
      const selectedCard = screen.getByTestId('method-pm_visa');
      expect(selectedCard).toHaveClass('selected');
      expect(selectedCard).toHaveAttribute('aria-checked', 'true');
    });

    it('PMS-004: Add new card option always shown', () => {
      render(<PaymentMethodSelector {...defaultProps} />);
      
      expect(screen.getByText(/add.*new.*card/i)).toBeInTheDocument();
    });

    it('PMS-005: Loading state shows skeletons', () => {
      render(<PaymentMethodSelector {...defaultProps} isLoading={true} />);
      
      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
    });

    it('PMS-006: Error state shows message', () => {
      render(<PaymentMethodSelector {...defaultProps} error="Failed to load" />);
      
      expect(screen.getByText(/failed.*load/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });

    it('PMS-007: Compact mode shows smaller cards', () => {
      render(<PaymentMethodSelector {...defaultProps} compact={true} />);
      
      const container = screen.getByTestId('method-selector');
      expect(container).toHaveClass('compact');
    });

    it('PMS-008: Modal trigger option', () => {
      render(<PaymentMethodSelector {...defaultProps} inModal={true} />);
      
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });
  });

  // ============================================
  // PMS-DISPLAY: Card Display
  // ============================================
  describe('Card Display', () => {
    it('PMS-009: Card brand displayed', () => {
      render(<PaymentMethodSelector {...defaultProps} />);
      
      expect(screen.getByAltText(/visa/i)).toBeInTheDocument();
      expect(screen.getByAltText(/mastercard/i)).toBeInTheDocument();
    });

    it('PMS-010: Last four digits masked', () => {
      render(<PaymentMethodSelector {...defaultProps} />);
      
      expect(screen.getByText('****4242')).toBeInTheDocument();
      expect(screen.getByText('****5555')).toBeInTheDocument();
    });

    it('PMS-011: Expiry date shown', () => {
      render(<PaymentMethodSelector {...defaultProps} />);
      
      expect(screen.getByText('12/25')).toBeInTheDocument();
      expect(screen.getByText('06/26')).toBeInTheDocument();
    });

    it('PMS-012: Default badge shown', () => {
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const defaultBadge = screen.getByText(/default/i);
      expect(defaultBadge).toBeInTheDocument();
    });

    it('PMS-013: Expired card shows warning', () => {
      const expiredMethods = [{
        ...mockMethods[0],
        card: {
          ...mockMethods[0].card,
          exp_month: 1,
          exp_year: 2020,
        },
      }];
      
      render(<PaymentMethodSelector {...defaultProps} methods={expiredMethods} />);
      
      expect(screen.getByText(/expired/i)).toBeInTheDocument();
      expect(screen.getByTestId('alert-icon')).toBeInTheDocument();
    });

    it('PMS-014: Expiring soon shows warning', () => {
      const expiringMethods = [{
        ...mockMethods[0],
        card: {
          ...mockMethods[0].card,
          exp_month: new Date().getMonth() + 2,
          exp_year: new Date().getFullYear(),
        },
      }];
      
      render(<PaymentMethodSelector {...defaultProps} methods={expiringMethods} />);
      
      expect(screen.getByText(/expiring soon/i)).toBeInTheDocument();
    });

    it('PMS-015: Card type icon for Visa', () => {
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const visaCard = screen.getByTestId('method-pm_visa');
      expect(within(visaCard).getByAltText(/visa/i)).toBeInTheDocument();
    });

    it('PMS-016: Card type icon for Mastercard', () => {
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const mcCard = screen.getByTestId('method-pm_mastercard');
      expect(within(mcCard).getByAltText(/mastercard/i)).toBeInTheDocument();
    });

    it('PMS-017: PayPal display', () => {
      const paypalMethods = [{
        id: 'pm_paypal',
        type: 'paypal',
        paypal: { email: 'test@example.com' },
        is_default: false,
      }];
      
      render(<PaymentMethodSelector {...defaultProps} methods={paypalMethods} />);
      
      expect(screen.getByAltText(/paypal/i)).toBeInTheDocument();
      expect(screen.getByText('test@example.com')).toBeInTheDocument();
    });

    it('PMS-018: Apple Pay display', () => {
      const applePayMethods = [{
        id: 'pm_apple',
        type: 'apple_pay',
        is_default: false,
      }];
      
      render(<PaymentMethodSelector {...defaultProps} methods={applePayMethods} />);
      
      expect(screen.getByAltText(/apple pay/i)).toBeInTheDocument();
    });
  });

  // ============================================
  // PMS-SELECTION: Selection Behavior
  // ============================================
  describe('Selection Behavior', () => {
    it('PMS-019: Select existing method', async () => {
      const user = userEvent.setup();
      const onMethodSelect = jest.fn();
      
      render(<PaymentMethodSelector {...defaultProps} onMethodSelect={onMethodSelect} />);
      
      await user.click(screen.getByTestId('method-pm_visa'));
      
      expect(onMethodSelect).toHaveBeenCalledWith('pm_visa');
    });

    it('PMS-020: Select new card triggers callback', async () => {
      const user = userEvent.setup();
      const onAddNew = jest.fn();
      
      render(<PaymentMethodSelector {...defaultProps} onAddNew={onAddNew} />);
      
      await user.click(screen.getByText(/add.*new.*card/i));
      
      expect(onAddNew).toHaveBeenCalled();
    });

    it('PMS-021: Deselect method', async () => {
      const user = userEvent.setup();
      const onMethodSelect = jest.fn();
      
      render(<PaymentMethodSelector 
        {...defaultProps} 
        selectedId="pm_visa"
        onMethodSelect={onMethodSelect}
      />);
      
      await user.click(screen.getByTestId('method-pm_visa'));
      
      expect(onMethodSelect).toHaveBeenCalledWith(null);
    });

    it('PMS-022: Only one method selected at a time', async () => {
      const user = userEvent.setup();
      const onMethodSelect = jest.fn();
      
      render(<PaymentMethodSelector {...defaultProps} onMethodSelect={onMethodSelect} />);
      
      // Select first card
      await user.click(screen.getByTestId('method-pm_visa'));
      expect(onMethodSelect).toHaveBeenLastCalledWith('pm_visa');
      
      // Select second card
      await user.click(screen.getByTestId('method-pm_mastercard'));
      expect(onMethodSelect).toHaveBeenLastCalledWith('pm_mastercard');
    });

    it('PMS-023: Default pre-selected on mount', () => {
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const defaultCard = screen.getByTestId('method-pm_visa');
      expect(defaultCard).toHaveClass('selected');
    });

    it('PMS-024: Selection persists across re-renders', async () => {
      const user = userEvent.setup();
      const { rerender } = render(<PaymentMethodSelector {...defaultProps} />);
      
      await user.click(screen.getByTestId('method-pm_mastercard'));
      
      rerender(<PaymentMethodSelector {...defaultProps} />);
      
      const selectedCard = screen.getByTestId('method-pm_mastercard');
      expect(selectedCard).toHaveClass('selected');
    });

    it('PMS-025: Keyboard selection with Enter', async () => {
      const user = userEvent.setup();
      const onMethodSelect = jest.fn();
      
      render(<PaymentMethodSelector {...defaultProps} onMethodSelect={onMethodSelect} />);
      
      const card = screen.getByTestId('method-pm_visa');
      card.focus();
      await user.keyboard('{Enter}');
      
      expect(onMethodSelect).toHaveBeenCalledWith('pm_visa');
    });

    it('PMS-026: Expired card not auto-selected', () => {
      const expiredMethods = [
        {
          ...mockMethods[0],
          card: {
            ...mockMethods[0].card,
            exp_year: 2020,
          },
          is_default: true,
        },
        mockMethods[1],
      ];
      
      render(<PaymentMethodSelector 
        {...defaultProps} 
        methods={expiredMethods}
      />);
      
      // Expired card should show warning
      expect(screen.getByText(/expired/i)).toBeInTheDocument();
    });

    it('PMS-027: Select by ID prop', () => {
      render(<PaymentMethodSelector {...defaultProps} selectedId="pm_mastercard" />);
      
      const selectedCard = screen.getByTestId('method-pm_mastercard');
      expect(selectedCard).toHaveClass('selected');
    });

    it('PMS-028: Selection change callback', async () => {
      const user = userEvent.setup();
      const onSelectionChange = jest.fn();
      
      render(<PaymentMethodSelector 
        {...defaultProps} 
        onSelectionChange={onSelectionChange}
      />);
      
      await user.click(screen.getByTestId('method-pm_visa'));
      
      expect(onSelectionChange).toHaveBeenCalledWith('pm_visa');
    });
  });

  // ============================================
  // PMS-MANAGEMENT: Card Management
  // ============================================
  describe('Card Management', () => {
    it('PMS-029: Set as default', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true }),
      });
      
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const mcCard = screen.getByTestId('method-pm_mastercard');
      const setDefaultBtn = within(mcCard).getByRole('button', { name: /set.*default/i });
      await user.click(setDefaultBtn);
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/payments/methods/pm_mastercard/default',
          expect.objectContaining({ method: 'POST' })
        );
      });
    });

    it('PMS-030: Delete method shows confirmation', async () => {
      const user = userEvent.setup();
      
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const mcCard = screen.getByTestId('method-pm_mastercard');
      const deleteBtn = within(mcCard).getByRole('button', { name: /delete/i });
      await user.click(deleteBtn);
      
      expect(screen.getByRole('dialog')).toBeInTheDocument();
      expect(screen.getByText(/delete.*payment.*method/i)).toBeInTheDocument();
    });

    it('PMS-031: Confirm delete removes method', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true }),
      });
      
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const mcCard = screen.getByTestId('method-pm_mastercard');
      await user.click(within(mcCard).getByRole('button', { name: /delete/i }));
      
      await user.click(screen.getByRole('button', { name: /confirm/i }));
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/payments/methods/pm_mastercard',
          expect.objectContaining({ method: 'DELETE' })
        );
      });
    });

    it('PMS-032: Cancel delete preserves method', async () => {
      const user = userEvent.setup();
      
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const mcCard = screen.getByTestId('method-pm_mastercard');
      await user.click(within(mcCard).getByRole('button', { name: /delete/i }));
      
      await user.click(screen.getByRole('button', { name: /cancel/i }));
      
      expect(screen.getByTestId('method-pm_mastercard')).toBeInTheDocument();
    });

    it('PMS-033: Delete last method shows warning', async () => {
      const user = userEvent.setup();
      
      render(<PaymentMethodSelector {...defaultProps} methods={[mockMethods[0]]} />);
      
      const card = screen.getByTestId('method-pm_visa');
      await user.click(within(card).getByRole('button', { name: /delete/i }));
      
      expect(screen.getByText(/this is your only/i)).toBeInTheDocument();
    });

    it('PMS-034: Delete default reassigns', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, newDefault: 'pm_mastercard' }),
      });
      
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const visaCard = screen.getByTestId('method-pm_visa');
      await user.click(within(visaCard).getByRole('button', { name: /delete/i }));
      await user.click(screen.getByRole('button', { name: /confirm/i }));
      
      await waitFor(() => {
        const mcCard = screen.getByTestId('method-pm_mastercard');
        expect(within(mcCard).getByText(/default/i)).toBeInTheDocument();
      });
    });

    it('PMS-035: Delete with subscription shows warning', async () => {
      const user = userEvent.setup();
      
      const methodsWithSub = [{
        ...mockMethods[0],
        has_active_subscription: true,
      }];
      
      render(<PaymentMethodSelector {...defaultProps} methods={methodsWithSub} />);
      
      const card = screen.getByTestId('method-pm_visa');
      await user.click(within(card).getByRole('button', { name: /delete/i }));
      
      expect(screen.getByText(/active subscription/i)).toBeInTheDocument();
    });

    it('PMS-036: Edit card details (if supported)', async () => {
      const user = userEvent.setup();
      
      render(<PaymentMethodSelector {...defaultProps} allowEdit={true} />);
      
      const card = screen.getByTestId('method-pm_visa');
      const editBtn = within(card).getByRole('button', { name: /edit/i });
      
      expect(editBtn).toBeInTheDocument();
    });

    it('PMS-037: Refresh methods', async () => {
      const user = userEvent.setup();
      const onRefresh = jest.fn();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ methods: mockMethods }),
      });
      
      render(<PaymentMethodSelector {...defaultProps} onRefresh={onRefresh} />);
      
      await user.click(screen.getByRole('button', { name: /refresh/i }));
      
      expect(onRefresh).toHaveBeenCalled();
    });

    it('PMS-038: Sync with Stripe', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ methods: mockMethods }),
      });
      
      render(<PaymentMethodSelector {...defaultProps} syncOnMount={true} />);
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith('/api/v1/payments/methods');
      });
    });
  });

  // ============================================
  // PMS-ACCESSIBILITY: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('PMS-039: Keyboard navigation', async () => {
      const user = userEvent.setup();
      render(<PaymentMethodSelector {...defaultProps} />);
      
      await user.tab();
      expect(screen.getByTestId('method-pm_visa')).toHaveFocus();
      
      await user.tab();
      expect(screen.getByTestId('method-pm_mastercard')).toHaveFocus();
    });

    it('PMS-040: Selection announcement', async () => {
      const user = userEvent.setup();
      render(<PaymentMethodSelector {...defaultProps} />);
      
      await user.click(screen.getByTestId('method-pm_visa'));
      
      const announcement = screen.getByRole('status');
      expect(announcement).toHaveTextContent(/visa.*selected/i);
    });

    it('PMS-041: Delete confirmation is accessible', async () => {
      const user = userEvent.setup();
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const card = screen.getByTestId('method-pm_mastercard');
      await user.click(within(card).getByRole('button', { name: /delete/i }));
      
      const dialog = screen.getByRole('dialog');
      expect(dialog).toHaveAttribute('aria-modal', 'true');
      expect(dialog).toHaveAttribute('aria-labelledby');
    });

    it('PMS-042: Focus management after delete', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ success: true }) });
      
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const mcCard = screen.getByTestId('method-pm_mastercard');
      await user.click(within(mcCard).getByRole('button', { name: /delete/i }));
      await user.click(screen.getByRole('button', { name: /confirm/i }));
      
      await waitFor(() => {
        expect(screen.getByTestId('method-pm_visa')).toHaveFocus();
      });
    });

    it('PMS-043: ARIA role radiogroup', () => {
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const container = screen.getByRole('radiogroup');
      expect(container).toBeInTheDocument();
    });

    it('PMS-044: Selected state aria-checked', () => {
      render(<PaymentMethodSelector {...defaultProps} selectedId="pm_visa" />);
      
      const selectedCard = screen.getByTestId('method-pm_visa');
      expect(selectedCard).toHaveAttribute('aria-checked', 'true');
      
      const otherCard = screen.getByTestId('method-pm_mastercard');
      expect(otherCard).toHaveAttribute('aria-checked', 'false');
    });

    it('PMS-045: Labels for all cards', () => {
      render(<PaymentMethodSelector {...defaultProps} />);
      
      const visaCard = screen.getByTestId('method-pm_visa');
      expect(visaCard).toHaveAttribute('aria-label', expect.stringContaining('Visa'));
    });
  });
});

// ============================================
// Mock Component for TDD Reference
// ============================================
export function MockPaymentMethodSelector({
  methods,
  selectedId,
  onMethodSelect,
  onAddNew,
  isLoading = false,
  error,
  compact = false,
  inModal = false,
  onRefresh,
  syncOnMount = false,
  allowEdit = false,
  onSelectionChange,
}: PaymentMethodSelectorProps) {
  const [selected, setSelected] = React.useState(selectedId || methods.find(m => m.is_default)?.id);
  const [deleteConfirm, setDeleteConfirm] = React.useState<string | null>(null);
  
  React.useEffect(() => {
    if (syncOnMount && onRefresh) {
      onRefresh();
    }
  }, [syncOnMount]);
  
  const handleSelect = (id: string) => {
    if (selected === id) {
      setSelected(null);
      onMethodSelect?.(null);
      onSelectionChange?.(null);
    } else {
      setSelected(id);
      onMethodSelect?.(id);
      onSelectionChange?.(id);
    }
  };
  
  if (isLoading) {
    return <div data-testid="loader-icon">Loading...</div>;
  }
  
  if (error) {
    return (
      <div>
        <p>{error}</p>
        <button onClick={onRefresh}>Retry</button>
      </div>
    );
  }
  
  if (methods.length === 0) {
    return (
      <button onClick={onAddNew}>
        Add payment method
      </button>
    );
  }
  
  return (
    <div data-testid="method-selector" className={compact ? 'compact' : ''}>
      <div role="radiogroup">
        {methods.map((method) => (
          <div
            key={method.id}
            data-testid={`method-${method.id}`}
            role="radio"
            aria-checked={selected === method.id}
            aria-label={`${method.card?.brand} ending in ${method.card?.last4}`}
            className={selected === method.id ? 'selected' : ''}
            onClick={() => handleSelect(method.id)}
            onKeyDown={(e) => e.key === 'Enter' && handleSelect(method.id)}
            tabIndex={0}
          >
            <img alt={method.card?.brand} src={`/icons/${method.card?.brand}.svg`} />
            <span>****{method.card?.last4}</span>
            <span>{method.card?.exp_month}/{String(method.card?.exp_year).slice(-2)}</span>
            {method.is_default && <span>Default</span>}
            
            {allowEdit && <button onClick={(e) => { e.stopPropagation(); }}>Edit</button>}
            <button 
              onClick={(e) => { e.stopPropagation(); setDeleteConfirm(method.id); }}
            >
              Delete
            </button>
          </div>
        ))}
      </div>
      
      <button onClick={onAddNew}>Add new card</button>
      
      {deleteConfirm && (
        <div role="dialog" aria-modal="true">
          <p>Delete payment method?</p>
          <button onClick={() => setDeleteConfirm(null)}>Cancel</button>
          <button onClick={() => {
            // Delete logic
            setDeleteConfirm(null);
          }}>Confirm</button>
        </div>
      )}
      
      <div role="status" aria-live="polite" className="sr-only">
        {selected && `Selected ${methods.find(m => m.id === selected)?.card?.brand}`}
      </div>
    </div>
  );
}

interface PaymentMethod {
  id: string;
  type: 'card' | 'paypal' | 'apple_pay';
  card?: {
    brand: string;
    last4: string;
    exp_month: number;
    exp_year: number;
  };
  paypal?: {
    email: string;
  };
  is_default: boolean;
  has_active_subscription?: boolean;
}

interface PaymentMethodSelectorProps {
  methods: PaymentMethod[];
  selectedId?: string | null;
  onMethodSelect?: (id: string | null) => void;
  onAddNew?: () => void;
  isLoading?: boolean;
  error?: string | null;
  compact?: boolean;
  inModal?: boolean;
  onRefresh?: () => void;
  syncOnMount?: boolean;
  allowEdit?: boolean;
  onSelectionChange?: (id: string | null) => void;
}
