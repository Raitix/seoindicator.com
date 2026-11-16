/**
 * Payment Flow Integration Tests
 * TASK-26-23: Stripe Payment Flow
 *
 * End-to-end tests for the payment flow from package selection through
 * to the confirmation.
 *
 * The flow is covered through the components that actually implement it:
 * the PaymentModal opens the flow, creates the payment intent and shows
 * the Stripe checkout, and the StripeCheckout submits the payment.
 */

import React from 'react';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { PaymentModal } from '@/components/payments/PaymentModal';

// Mock Stripe
const mockConfirmPayment = vi.fn();
const mockSubmit = vi.fn().mockResolvedValue({ error: undefined });
vi.mock('@stripe/react-stripe-js', () => ({
  Elements: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  PaymentElement: () => <div data-testid="payment-element" />,
  useStripe: () => ({ confirmPayment: mockConfirmPayment }),
  useElements: () => ({ submit: mockSubmit }),
}));

// The toast is called on a successful payment.
//
// vi.mock is hoisted to the top of the file, so the mock has to be
// hoisted with it.
const { mockToastSuccess } = vi.hoisted(() => ({
  mockToastSuccess: vi.fn(),
}));

vi.mock('sonner', () => ({
  toast: { success: mockToastSuccess, error: vi.fn() },
  Toaster: () => null,
}));

vi.mock('@stripe/stripe-js', () => ({
  loadStripe: vi.fn(() => Promise.resolve({ confirmPayment: mockConfirmPayment })),
}));

// The API is mocked through fetch.
//
// msw is not a declared dependency of the project and msw 2 does not
// export `rest`, so the endpoints are stubbed on global.fetch instead.
type FetchHandler = (url: string) => Response | null;

let handlers: FetchHandler[] = [];

const jsonResponse = (body: unknown, status = 200) =>
  new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });

const defaultHandlers: FetchHandler[] = [
  // Payment Intent endpoint
  (url) =>
    url.includes('/api/v1/payments/stripe/intent')
      ? jsonResponse({
          success: true,
          data: {
            client_secret: 'pi_test_123_secret_456',
            payment_intent_id: 'pi_test_123',
            amount: 25.00,
            credits: 150,
            package_id: 2,
          },
        })
      : null,

  // Credit balance endpoint
  (url) =>
    url.includes('/api/v1/credits/balance')
      ? jsonResponse({
          balance: 175.50,
          total_purchased: 200.00,
          total_spent: 24.50,
        })
      : null,

  // Payment methods endpoint
  (url) =>
    url.includes('/api/v1/payments/methods')
      ? jsonResponse({ success: true, data: [] })
      : null,
];

const originalFetch = global.fetch;

beforeEach(() => {
  // The submit resolves with an object that has no error, so the
  // checkout proceeds to the confirmation.
  mockSubmit.mockResolvedValue({ error: undefined });
  mockConfirmPayment.mockResolvedValue({
    paymentIntent: { status: 'succeeded', id: 'pi_test_123' },
  });
  handlers = [...defaultHandlers];
  global.fetch = vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
    const url = typeof input === 'string' ? input : input.toString();
    for (const handler of handlers) {
      const response = handler(url);
      if (response) return response;
    }
    return originalFetch(input as RequestInfo, init);
  }) as unknown as typeof global.fetch;
});

afterEach(() => {
  global.fetch = originalFetch;
  vi.clearAllMocks();
});

const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } }
  });
  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
};

const GROWTH_PACKAGE = {
  id: 2,
  name: 'Growth',
  credits: 150,
  price: 25,
  bonus_percent: 0,
  is_popular: false,
};

const renderModal = (
  overrides: Partial<React.ComponentProps<typeof PaymentModal>> = {}
) => {
  const onSuccess = vi.fn();
  const onClose = vi.fn();

  render(
    <PaymentModal
      isOpen={true}
      onClose={onClose}
      onSuccess={onSuccess}
      creditPackage={GROWTH_PACKAGE}
      {...overrides}
    />,
    { wrapper: createWrapper() }
  );

  return { onSuccess, onClose };
};

describe('Payment Flow Integration', () => {
  describe('PF-001 to PF-004: Flow Initialization', () => {
    it('opens the payment modal with the selected package', async () => {
      renderModal();

      expect(screen.getByRole('dialog')).toBeInTheDocument();
      expect(screen.getByText('Complete Purchase')).toBeInTheDocument();
    });

    it('shows correct package in modal', async () => {
      renderModal();

      const modal = screen.getByRole('dialog');
      expect(within(modal).getByText('Growth')).toBeInTheDocument();
      expect(within(modal).getByText('150 credits')).toBeInTheDocument();
      expect(within(modal).getByText('$25.00')).toBeInTheDocument();
    });

    it('creates payment intent on modal open', async () => {
      let intentCreated = false;
      handlers.unshift(() => {
        intentCreated = true;
        return jsonResponse({
          success: true,
          data: {
            client_secret: 'pi_test_123_secret_456',
            payment_intent_id: 'pi_test_123',
            amount: 25.00,
            credits: 150,
          },
        });
      });

      renderModal();

      await waitFor(() => {
        expect(intentCreated).toBe(true);
      });
    });

    it('shows the progress indicator for the payment step', async () => {
      renderModal();

      expect(screen.getByTestId('step-0')).toBeInTheDocument();
      expect(screen.getByTestId('step-1')).toBeInTheDocument();
      expect(screen.getByTestId('step-2')).toBeInTheDocument();
    });
  });

  describe('PF-005 to PF-007: Payment Success', () => {
    it('submits payment to Stripe', async () => {
      mockConfirmPayment.mockResolvedValue({
        paymentIntent: { status: 'succeeded', id: 'pi_test_123' }
      });

      const user = userEvent.setup();
      renderModal();

      await waitFor(() => screen.getByTestId('stripe-checkout-form'));

      await user.click(screen.getByRole('button', { name: /pay/i }));

      // The submit handler is async, so the calls land after the click
      // has resolved.
      await waitFor(() => {
        expect(mockSubmit).toHaveBeenCalled();
      });
      await waitFor(() => {
        expect(mockConfirmPayment).toHaveBeenCalled();
      });
    });

    it('reports the payment to the caller on success', async () => {
      mockConfirmPayment.mockResolvedValue({
        paymentIntent: { status: 'succeeded', id: 'pi_test_123' }
      });

      const user = userEvent.setup();
      const { onSuccess } = renderModal();

      await waitFor(() => screen.getByTestId('stripe-checkout-form'));

      await user.click(screen.getByRole('button', { name: /pay/i }));

      await waitFor(() => {
        expect(onSuccess).toHaveBeenCalledWith(
          expect.objectContaining({ paymentIntentId: 'pi_test_123' })
        );
      });
    });
  });

  describe('PF-008 to PF-010: Error handling', () => {
    it('handles card decline', async () => {
      mockConfirmPayment.mockResolvedValue({
        error: { type: 'card_error', message: 'Your card was declined.' }
      });

      const user = userEvent.setup();
      renderModal();

      await waitFor(() => screen.getByTestId('stripe-checkout-form'));

      await user.click(screen.getByRole('button', { name: /pay/i }));

      await waitFor(() => {
        expect(mockConfirmPayment).toHaveBeenCalled();
      });

      // The modal catches the error from the checkout and shows it with
      // a retry button.
      await waitFor(() => {
        expect(screen.getByText(/your card was declined/i)).toBeInTheDocument();
      });
      expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
    });

    it('shows the error and a retry when the intent fails', async () => {
      handlers.unshift(() =>
        jsonResponse({ success: false, message: 'Failed to initialize payment' }, 500)
      );

      renderModal();

      await waitFor(() => {
        expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
      });
    });

    it('handles network error with retry', async () => {
      handlers.unshift(() =>
        jsonResponse({ success: false, message: 'Service unavailable' }, 503)
      );

      renderModal();

      await waitFor(() => {
        expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
      });
    });

    it('user can retry after error', async () => {
      let attempts = 0;
      handlers.unshift(() => {
        attempts += 1;
        if (attempts === 1) {
          return jsonResponse({ success: false, message: 'Failed to initialize payment' }, 500);
        }
        return jsonResponse({
          success: true,
          data: {
            client_secret: 'pi_test_123_secret_456',
            payment_intent_id: 'pi_test_123',
            amount: 25.00,
            credits: 150,
          },
        });
      });

      const user = userEvent.setup();
      renderModal();

      await waitFor(() => {
        expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
      });

      await user.click(screen.getByRole('button', { name: /try again/i }));

      await waitFor(() => {
        expect(screen.getByTestId('stripe-checkout-form')).toBeInTheDocument();
      });
    });
  });

  describe('PF-011 to PF-013: Cancellation and progress', () => {
    it('user can cancel flow', async () => {
      const user = userEvent.setup();
      const { onClose } = renderModal();

      await waitFor(() => screen.getByTestId('stripe-checkout-form'));

      // The checkout has no cancel button of its own. The modal's close
      // button is the way out of the flow.
      await user.click(screen.getByRole('button', { name: /close modal/i }));

      expect(onClose).toHaveBeenCalled();
    });

    it('closes on Escape', async () => {
      const user = userEvent.setup();
      const { onClose } = renderModal();

      await waitFor(() => screen.getByTestId('stripe-checkout-form'));

      await user.keyboard('{Escape}');

      expect(onClose).toHaveBeenCalled();
    });

    it('shows the step names in the progress indicator', async () => {
      renderModal();

      const modal = screen.getByRole('dialog');
      expect(within(modal).getByText(/package/i)).toBeInTheDocument();
      expect(within(modal).getByText('Payment')).toBeInTheDocument();
    });
  });

  describe('PF-014 to PF-016: HTTP error responses', () => {
    it('handles 401 unauthorized', async () => {
      handlers.unshift(() => jsonResponse({ success: false, message: 'Unauthenticated.' }, 401));

      renderModal();

      await waitFor(() => {
        expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
      });
    });

    it('handles 429 rate limit', async () => {
      handlers.unshift(() => jsonResponse({ success: false, message: 'Too many requests.' }, 429));

      renderModal();

      await waitFor(() => {
        expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
      });
    });

    it('handles 500 server error gracefully', async () => {
      handlers.unshift(() =>
        jsonResponse({ success: false, message: 'Internal server error' }, 500)
      );

      renderModal();

      await waitFor(() => {
        expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
      });
    });
  });
});
