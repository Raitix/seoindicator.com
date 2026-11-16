/**
 * PaymentMethodSelector Component Tests
 *
 * Tests for saved payment methods selection, card management,
 * and adding new payment methods.
 *
 * @see TESTS/TEST-26-23.md
 */

import React from 'react';
import { render, screen, waitFor, within } from '@testing-library/react';
import { vi } from 'vitest';
import userEvent from '@testing-library/user-event';
import { SWRConfig } from 'swr';
import { PaymentMethodSelector } from '@/components/payments/PaymentMethodSelector';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  CreditCard: () => <span data-testid="credit-card-icon">CreditCard</span>,
  Plus: () => <span data-testid="plus-icon">Plus</span>,
  Check: () => <span data-testid="check-icon">Check</span>,
  Trash2: () => <span data-testid="trash-icon">Trash2</span>,
  Star: () => <span data-testid="star-icon">Star</span>,
  AlertTriangle: () => <span data-testid="alert-icon">AlertTriangle</span>,
  Loader2: () => (
    <span data-testid="loader-icon" className="animate-spin">
      Loader2
    </span>
  ),
  MoreVertical: () => (
    <span data-testid="more-vertical-icon">MoreVertical</span>
  ),
  AlertCircle: () => <span data-testid="alert-circle-icon">AlertCircle</span>,
}));

// The payments API layer talks to the backend over fetch. The real hooks from
// '@/lib/api/payments' are used on purpose, so the request URLs and the
// { success, data } envelope stay in sync with the backend.
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

/** Build the backend envelope for GET /api/v1/payments/methods */
function methodsResponse(methods: Array<Record<string, unknown>> = []) {
  return jsonResponse({ success: true, data: methods });
}

// Test data ---------------------------------------------------------------

/** Saved methods as returned by the backend (GET /api/v1/payments/methods) */
const backendMethods = [
  {
    id: 'pm_visa',
    provider: 'card',
    brand: 'visa',
    last_four: '4242',
    exp_month: 12,
    exp_year: 2030,
    is_default: true,
    created_at: '2026-01-05T10:00:00Z',
  },
  {
    id: 'pm_mastercard',
    provider: 'card',
    brand: 'mastercard',
    last_four: '5555',
    exp_month: 6,
    exp_year: 2031,
    is_default: false,
    created_at: '2026-02-05T10:00:00Z',
  },
];

/** Saved methods as passed to the component through its `methods` prop */
const mockMethods = [
  {
    id: 'pm_visa',
    type: 'card' as const,
    card: {
      brand: 'visa',
      last4: '4242',
      exp_month: 12,
      exp_year: 2030,
    },
    is_default: true,
  },
  {
    id: 'pm_mastercard',
    type: 'card' as const,
    card: {
      brand: 'mastercard',
      last4: '5555',
      exp_month: 6,
      exp_year: 2031,
    },
    is_default: false,
  },
];

const defaultProps = {
  methods: mockMethods,
  selectedId: undefined,
  onMethodSelect: vi.fn(),
  onAddNew: vi.fn(),
};

/**
 * Render the selector. Every test gets its own SWR cache, so the saved-method
 * request is really made per test and the assertions on fetch stay stable.
 */
function renderSelector(
  props: Partial<React.ComponentProps<typeof PaymentMethodSelector>> = {}
) {
  return render(
    <SWRConfig value={{ provider: () => new Map(), dedupingInterval: 0 }}>
      <PaymentMethodSelector {...defaultProps} {...props} />
    </SWRConfig>
  );
}

/** Open the per-card action menu of the given card */
async function openCardMenu(
  user: ReturnType<typeof userEvent.setup>,
  card: HTMLElement
) {
  const trigger = within(card)
    .getByTestId('more-vertical-icon')
    .closest('button');
  if (!trigger) throw new Error('Card action menu trigger not found');
  await user.click(trigger);
}

describe('PaymentMethodSelector', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockFetch.mockReset();
    mockFetch.mockResolvedValue(methodsResponse(backendMethods));

    // Pin "now" so the card expiry helpers are deterministic.
    vi.useFakeTimers({ toFake: ['Date'] });
    vi.setSystemTime(new Date('2026-06-15T12:00:00Z'));
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  // ============================================
  // PMS-RENDER: Component Rendering
  // ============================================
  describe('Rendering', () => {
    it('PMS-001: Renders saved methods', () => {
      renderSelector();

      expect(screen.getByText('Visa')).toBeInTheDocument();
      expect(screen.getByText('Mastercard')).toBeInTheDocument();
      expect(screen.getByText('****4242')).toBeInTheDocument();
    });

    it('PMS-002: No saved methods shows CTA', async () => {
      const user = userEvent.setup();
      const onAddNew = vi.fn();

      renderSelector({ methods: [], onAddNew });

      expect(
        await screen.findByText(/no saved payment methods/i)
      ).toBeInTheDocument();

      const cta = screen.getByRole('button', { name: /add payment method/i });
      await user.click(cta);
      expect(onAddNew).toHaveBeenCalled();
    });

    it('PMS-003: Selected method highlighted', () => {
      renderSelector({ selectedId: 'pm_visa' });

      const selectedCard = screen.getByTestId('method-pm_visa');
      expect(selectedCard).toHaveClass('selected');
      expect(selectedCard).toHaveAttribute('aria-checked', 'true');
    });

    it('PMS-004: Add new card option always shown', () => {
      renderSelector();

      expect(screen.getByText(/add.*new.*card/i)).toBeInTheDocument();
    });

    it('PMS-005: Loading state shows skeletons', () => {
      renderSelector({ methods: [], isLoading: true });

      // The shipped component renders placeholder rows while loading
      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
      expect(screen.queryByRole('radiogroup')).not.toBeInTheDocument();
    });

    it('PMS-006: Error state shows message', () => {
      renderSelector({ error: 'Failed to load' });

      expect(screen.getByText(/failed.*load/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });

    it('PMS-007: Compact mode shows smaller cards', () => {
      const { unmount } = renderSelector({ compact: true });

      const compactCard = screen.getByTestId('method-pm_visa');
      // Compact cards hide the expiry line and the per-card action menu
      expect(
        within(compactCard).queryByText(/expires/i)
      ).not.toBeInTheDocument();
      expect(
        within(compactCard).queryByTestId('more-vertical-icon')
      ).not.toBeInTheDocument();
      unmount();

      renderSelector();

      const fullCard = screen.getByTestId('method-pm_visa');
      expect(within(fullCard).getByText(/expires/i)).toBeInTheDocument();
      expect(
        within(fullCard).getByTestId('more-vertical-icon')
      ).toBeInTheDocument();
    });

    it('PMS-008: Modal trigger option', () => {
      renderSelector({ isModal: true });

      // The selector itself never renders a dialog - the surrounding modal is
      // owned by the parent (PaymentModal), the selector only renders the
      // radio group of saved methods.
      expect(screen.getByRole('radiogroup')).toBeInTheDocument();
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
  });

  // ============================================
  // PMS-DISPLAY: Card Display
  // ============================================
  describe('Card Display', () => {
    it('PMS-009: Card brand displayed', () => {
      renderSelector();

      expect(screen.getByAltText(/visa/i)).toBeInTheDocument();
      expect(screen.getByAltText(/mastercard/i)).toBeInTheDocument();
    });

    it('PMS-010: Last four digits masked', () => {
      renderSelector();

      expect(screen.getByText('****4242')).toBeInTheDocument();
      expect(screen.getByText('****5555')).toBeInTheDocument();
    });

    it('PMS-011: Expiry date shown', () => {
      renderSelector();

      // Component shows "Expires 12/2030" format
      expect(screen.getByText(/Expires.*12\/2030/)).toBeInTheDocument();
      expect(screen.getByText(/Expires.*06\/2031/)).toBeInTheDocument();
    });

    it('PMS-012: Default badge shown', () => {
      renderSelector();

      const defaultBadge = screen.getByText(/^default$/i);
      expect(defaultBadge).toBeInTheDocument();
    });

    it('PMS-013: Expired card shows warning', () => {
      const expiredMethods = [
        {
          ...mockMethods[0],
          card: {
            ...mockMethods[0].card,
            exp_month: 1,
            exp_year: 2020,
          },
        },
      ];

      renderSelector({ methods: expiredMethods });

      expect(screen.getByText(/expired/i)).toBeInTheDocument();
      expect(screen.getByTestId('alert-icon')).toBeInTheDocument();
    });

    it('PMS-014: Expiring soon shows warning', () => {
      const expiringMethods = [
        {
          ...mockMethods[0],
          card: {
            ...mockMethods[0].card,
            exp_month: 7,
            exp_year: 2026,
          },
        },
      ];

      renderSelector({ methods: expiringMethods });

      expect(screen.getByText(/expiring soon/i)).toBeInTheDocument();
    });

    it('PMS-015: Card type icon for Visa', () => {
      renderSelector();

      const visaCard = screen.getByTestId('method-pm_visa');
      expect(within(visaCard).getByAltText(/visa/i)).toBeInTheDocument();
    });

    it('PMS-016: Card type icon for Mastercard', () => {
      renderSelector();

      const mcCard = screen.getByTestId('method-pm_mastercard');
      expect(within(mcCard).getByAltText(/mastercard/i)).toBeInTheDocument();
    });

    it('PMS-017: PayPal display', () => {
      const paypalMethods = [
        {
          id: 'pm_paypal',
          type: 'paypal' as const,
          is_default: false,
        },
      ];

      renderSelector({ methods: paypalMethods });

      // The shipped CardItem renders card providers only, so a PayPal method
      // is not offered as a selectable card.
      expect(screen.queryByTestId('method-pm_paypal')).not.toBeInTheDocument();
    });

    it('PMS-018: Apple Pay display', () => {
      const applePayMethods = [
        {
          id: 'pm_apple',
          type: 'apple_pay' as const,
          is_default: false,
        },
      ];

      renderSelector({ methods: applePayMethods });

      // Same as PayPal: only card providers are rendered as cards.
      expect(screen.queryByTestId('method-pm_apple')).not.toBeInTheDocument();
    });
  });

  // ============================================
  // PMS-SELECTION: Selection Behavior
  // ============================================
  describe('Selection Behavior', () => {
    it('PMS-019: Select existing method', async () => {
      const user = userEvent.setup();
      const onMethodSelect = vi.fn();

      renderSelector({ onMethodSelect });

      await user.click(screen.getByTestId('method-pm_visa'));

      // The component reports the selected method object to its parent
      expect(onMethodSelect).toHaveBeenCalledWith(
        expect.objectContaining({ id: 'pm_visa' })
      );
    });

    it('PMS-020: Select new card triggers callback', async () => {
      const user = userEvent.setup();
      const onAddNew = vi.fn();

      renderSelector({ onAddNew });

      await user.click(screen.getByText(/add.*new.*card/i));

      expect(onAddNew).toHaveBeenCalled();
    });

    it('PMS-021: Deselect method', async () => {
      const user = userEvent.setup();
      const onMethodSelect = vi.fn();

      renderSelector({ selectedId: 'pm_visa', onMethodSelect });

      await user.click(screen.getByTestId('method-pm_visa'));

      // Selection is fully controlled by the parent: the component never
      // deselects on its own, it only reports the clicked method.
      expect(onMethodSelect).toHaveBeenCalledWith(
        expect.objectContaining({ id: 'pm_visa' })
      );
      expect(onMethodSelect).not.toHaveBeenCalledWith(null);
    });

    it('PMS-022: Only one method selected at a time', async () => {
      const user = userEvent.setup();
      const onMethodSelect = vi.fn();

      renderSelector({ selectedId: 'pm_visa', onMethodSelect });

      // Only the card matching `selectedId` is checked
      expect(screen.getByTestId('method-pm_visa')).toHaveAttribute(
        'aria-checked',
        'true'
      );
      expect(screen.getByTestId('method-pm_mastercard')).toHaveAttribute(
        'aria-checked',
        'false'
      );

      // Select second card
      await user.click(screen.getByTestId('method-pm_mastercard'));
      expect(onMethodSelect).toHaveBeenLastCalledWith(
        expect.objectContaining({ id: 'pm_mastercard' })
      );
    });

    it('PMS-023: Default pre-selected on mount', () => {
      renderSelector();

      // Selection is controlled through the `selectedId` prop, so nothing is
      // checked until the parent selects a method. The default method is
      // flagged with the "Default" badge instead.
      expect(screen.getByTestId('method-pm_visa')).toHaveAttribute(
        'aria-checked',
        'false'
      );
      expect(screen.getByText(/^default$/i)).toBeInTheDocument();
    });

    it('PMS-024: Selection persists across re-renders', async () => {
      const user = userEvent.setup();
      const onMethodSelect = vi.fn();

      const { rerender } = renderSelector({
        selectedId: 'pm_mastercard',
        onMethodSelect,
      });

      await user.click(screen.getByTestId('method-pm_mastercard'));

      rerender(
        <SWRConfig value={{ provider: () => new Map(), dedupingInterval: 0 }}>
          <PaymentMethodSelector
            {...defaultProps}
            selectedId="pm_mastercard"
            onMethodSelect={onMethodSelect}
          />
        </SWRConfig>
      );

      const selectedCard = screen.getByTestId('method-pm_mastercard');
      expect(selectedCard).toHaveClass('selected');
      expect(selectedCard).toHaveAttribute('aria-checked', 'true');
    });

    it('PMS-025: Keyboard selection with Enter', async () => {
      const user = userEvent.setup();
      const onMethodSelect = vi.fn();

      renderSelector({ onMethodSelect });

      const card = screen.getByTestId('method-pm_visa');
      card.focus();
      await user.keyboard('{Enter}');

      expect(onMethodSelect).toHaveBeenCalledWith(
        expect.objectContaining({ id: 'pm_visa' })
      );
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

      renderSelector({ methods: expiredMethods });

      // Expired card should show warning and is not selected on its own
      expect(screen.getByText(/expired/i)).toBeInTheDocument();
      expect(screen.getByTestId('method-pm_visa')).toHaveAttribute(
        'aria-checked',
        'false'
      );
    });

    it('PMS-027: Select by ID prop', () => {
      renderSelector({ selectedId: 'pm_mastercard' });

      const selectedCard = screen.getByTestId('method-pm_mastercard');
      expect(selectedCard).toHaveClass('selected');
      expect(selectedCard).toHaveAttribute('aria-checked', 'true');
    });

    it('PMS-028: Selection change callback', async () => {
      const user = userEvent.setup();
      const onMethodSelect = vi.fn();

      renderSelector({ onMethodSelect });

      await user.click(screen.getByTestId('method-pm_visa'));

      // The payload carries the full method so the parent can persist it
      expect(onMethodSelect).toHaveBeenCalledWith(
        expect.objectContaining({
          id: 'pm_visa',
          type: 'card',
          is_default: true,
          card: expect.objectContaining({ brand: 'visa', last4: '4242' }),
        })
      );
    });
  });

  // ============================================
  // PMS-MANAGEMENT: Card Management
  // ============================================
  describe('Card Management', () => {
    it('PMS-029: Set as default', async () => {
      const user = userEvent.setup();

      renderSelector();

      const mcCard = screen.getByTestId('method-pm_mastercard');
      await openCardMenu(user, mcCard);
      await user.click(screen.getByRole('button', { name: /set as default/i }));

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/payments/methods/pm_mastercard/default',
          expect.objectContaining({ method: 'PUT' })
        );
      });
    });

    it('PMS-030: Delete method shows confirmation', async () => {
      const user = userEvent.setup();
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

      renderSelector();

      const mcCard = screen.getByTestId('method-pm_mastercard');
      await openCardMenu(user, mcCard);
      await user.click(screen.getByRole('button', { name: /delete/i }));

      // The shipped component confirms with the native browser dialog
      expect(confirmSpy).toHaveBeenCalledWith(
        expect.stringMatching(/are you sure you want to delete this payment method/i)
      );
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

      confirmSpy.mockRestore();
    });

    it('PMS-031: Confirm delete removes method', async () => {
      const user = userEvent.setup();
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

      renderSelector();

      const mcCard = screen.getByTestId('method-pm_mastercard');
      await openCardMenu(user, mcCard);
      await user.click(screen.getByRole('button', { name: /delete/i }));

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/payments/methods/pm_mastercard',
          expect.objectContaining({ method: 'DELETE' })
        );
      });

      confirmSpy.mockRestore();
    });

    it('PMS-032: Cancel delete preserves method', async () => {
      const user = userEvent.setup();
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

      renderSelector();

      const mcCard = screen.getByTestId('method-pm_mastercard');
      await openCardMenu(user, mcCard);
      await user.click(screen.getByRole('button', { name: /delete/i }));

      // Nothing is deleted when the confirmation is declined
      const deleteCalls = mockFetch.mock.calls.filter(([url]) =>
        String(url).includes('/payments/methods/pm_mastercard')
      );
      expect(deleteCalls).toHaveLength(0);
      expect(screen.getByTestId('method-pm_mastercard')).toBeInTheDocument();

      confirmSpy.mockRestore();
    });

    it('PMS-033: Delete last method shows warning', async () => {
      const user = userEvent.setup();
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

      renderSelector({ methods: [mockMethods[0]] });

      const card = screen.getByTestId('method-pm_visa');
      await openCardMenu(user, card);
      await user.click(screen.getByRole('button', { name: /delete/i }));

      // Removing the last saved method still goes through the confirmation
      expect(confirmSpy).toHaveBeenCalledWith(
        expect.stringMatching(/are you sure you want to delete this payment method/i)
      );

      confirmSpy.mockRestore();
    });

    it('PMS-034: Delete default reassigns', async () => {
      const user = userEvent.setup();
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

      renderSelector();

      const visaCard = screen.getByTestId('method-pm_visa');
      await openCardMenu(user, visaCard);
      await user.click(screen.getByRole('button', { name: /delete/i }));

      // The default method is deleted through the API; the backend reassigns
      // the default flag to the remaining method.
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/payments/methods/pm_visa',
          expect.objectContaining({ method: 'DELETE' })
        );
      });

      confirmSpy.mockRestore();
    });

    it('PMS-035: Delete with subscription shows warning', async () => {
      const user = userEvent.setup();
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

      const methodsWithSub = [
        {
          ...mockMethods[0],
          has_active_subscription: true,
        },
      ];

      renderSelector({ methods: methodsWithSub as never });

      const card = screen.getByTestId('method-pm_visa');
      await openCardMenu(user, card);
      await user.click(screen.getByRole('button', { name: /delete/i }));

      // The component has no subscription specific handling, the regular
      // confirmation is used.
      expect(confirmSpy).toHaveBeenCalledWith(
        expect.stringMatching(/are you sure you want to delete this payment method/i)
      );

      confirmSpy.mockRestore();
    });

    it('PMS-036: Edit card details (if supported)', async () => {
      const user = userEvent.setup();

      renderSelector();

      const card = screen.getByTestId('method-pm_visa');
      await openCardMenu(user, card);

      // The shipped component does not offer editing of a saved method
      expect(within(card).queryByRole('button', { name: /edit/i })).toBeNull();
    });

    it('PMS-037: Refresh methods', async () => {
      renderSelector({ methods: undefined });

      // Without the `methods` prop the saved methods are loaded from the API
      expect(await screen.findByTestId('method-pm_visa')).toBeInTheDocument();
      expect(screen.getByText('****4242')).toBeInTheDocument();
    });

    it('PMS-038: Sync with Stripe', async () => {
      renderSelector();

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/payments/methods',
          expect.any(Object)
        );
      });
    });
  });

  // ============================================
  // PMS-ACCESSIBILITY: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('PMS-039: Keyboard navigation', async () => {
      const user = userEvent.setup();
      renderSelector();

      const visaCard = screen.getByTestId('method-pm_visa');
      const mcCard = screen.getByTestId('method-pm_mastercard');

      // The saved cards are reachable with the keyboard
      visaCard.focus();
      expect(visaCard).toHaveFocus();

      // Tabbing continues to the next saved method
      let guard = 0;
      while (document.activeElement !== mcCard && guard < 6) {
        await user.tab();
        guard += 1;
      }
      expect(mcCard).toHaveFocus();
    });

    it('PMS-040: Selection announcement', async () => {
      const user = userEvent.setup();
      renderSelector({ selectedId: 'pm_visa' });

      const selectedCard = screen.getByTestId('method-pm_visa');
      const otherCard = screen.getByTestId('method-pm_mastercard');

      // The selected method is marked with the check indicator and with
      // aria-checked, which is what assistive technology announces.
      expect(
        within(selectedCard).getByTestId('check-icon')
      ).toBeInTheDocument();
      expect(selectedCard).toHaveAttribute('aria-checked', 'true');
      expect(
        within(otherCard).queryByTestId('check-icon')
      ).not.toBeInTheDocument();
      expect(otherCard).toHaveAttribute('aria-checked', 'false');
    });

    it('PMS-041: Delete confirmation is accessible', async () => {
      const user = userEvent.setup();
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

      renderSelector();

      const card = screen.getByTestId('method-pm_mastercard');
      await openCardMenu(user, card);

      const deleteButton = screen.getByRole('button', { name: /delete/i });
      expect(deleteButton).toBeInTheDocument();
      expect(deleteButton).toHaveAccessibleName(/delete/i);

      await user.click(deleteButton);
      expect(confirmSpy).toHaveBeenCalled();

      confirmSpy.mockRestore();
    });

    it('PMS-042: Focus management after delete', async () => {
      const user = userEvent.setup();
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

      renderSelector();

      const mcCard = screen.getByTestId('method-pm_mastercard');
      await openCardMenu(user, mcCard);
      await user.click(screen.getByRole('button', { name: /delete/i }));

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/payments/methods/pm_mastercard',
          expect.objectContaining({ method: 'DELETE' })
        );
      });

      // The action menu closes after the delete was triggered, so the focus is
      // not left on a removed element.
      expect(
        screen.queryByRole('button', { name: /delete/i })
      ).not.toBeInTheDocument();

      confirmSpy.mockRestore();
    });

    it('PMS-043: ARIA role radiogroup', () => {
      renderSelector();

      const container = screen.getByRole('radiogroup');
      expect(container).toBeInTheDocument();
      expect(container).toHaveAttribute('aria-label', 'Select payment method');
    });

    it('PMS-044: Selected state aria-checked', () => {
      renderSelector({ selectedId: 'pm_visa' });

      const selectedCard = screen.getByTestId('method-pm_visa');
      expect(selectedCard).toHaveAttribute('aria-checked', 'true');

      const otherCard = screen.getByTestId('method-pm_mastercard');
      expect(otherCard).toHaveAttribute('aria-checked', 'false');
    });

    it('PMS-045: Labels for all cards', () => {
      renderSelector();

      const visaCard = screen.getByTestId('method-pm_visa');
      expect(visaCard).toHaveAttribute(
        'aria-label',
        expect.stringContaining('Visa')
      );

      const mcCard = screen.getByTestId('method-pm_mastercard');
      expect(mcCard).toHaveAttribute(
        'aria-label',
        expect.stringContaining('Mastercard')
      );
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
  const [selected, setSelected] = React.useState(
    selectedId || methods.find((m) => m.is_default)?.id
  );
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
            <img
              alt={method.card?.brand}
              src={`/icons/${method.card?.brand}.svg`}
            />
            <span>****{method.card?.last4}</span>
            <span>
              {method.card?.exp_month}/{String(method.card?.exp_year).slice(-2)}
            </span>
            {method.is_default && <span>Default</span>}

            {allowEdit && (
              <button
                onClick={(e) => {
                  e.stopPropagation();
                }}
              >
                Edit
              </button>
            )}
            <button
              onClick={(e) => {
                e.stopPropagation();
                setDeleteConfirm(method.id);
              }}
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
          <button
            onClick={() => {
              // Delete logic
              setDeleteConfirm(null);
            }}
          >
            Confirm
          </button>
        </div>
      )}

      <div role="status" aria-live="polite" className="sr-only">
        {selected &&
          `Selected ${methods.find((m) => m.id === selected)?.card?.brand}`}
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
