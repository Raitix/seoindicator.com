/**
 * Payment Flow Integration Tests
 * TASK-26-23: Stripe Payment Flow
 * 
 * End-to-end tests for the complete payment flow from package
 * selection to balance update.
 */

import React from 'react';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { rest } from 'msw';
import { setupServer } from 'msw/node';
import { PaymentFlow } from '@/components/payments/PaymentFlow';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

// Mock Stripe
const mockConfirmPayment = jest.fn();
jest.mock('@stripe/react-stripe-js', () => ({
  Elements: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  PaymentElement: () => <div data-testid="payment-element" />,
  useStripe: () => ({ confirmPayment: mockConfirmPayment }),
  useElements: () => ({ submit: jest.fn() }),
}));

jest.mock('@stripe/stripe-js', () => ({
  loadStripe: jest.fn(() => Promise.resolve({ confirmPayment: mockConfirmPayment })),
}));

// MSW Server for API mocking
const server = setupServer(
  // Payment Intent endpoint
  rest.post('/api/v1/payments/stripe/intent', (req, res, ctx) => {
    return res(ctx.json({
      client_secret: 'pi_test_123_secret_456',
      payment_intent_id: 'pi_test_123',
      amount: 25.00,
      credits: 150,
      package_id: 2
    }));
  }),
  
  // Credit balance endpoint
  rest.get('/api/v1/credits/balance', (req, res, ctx) => {
    return res(ctx.json({
      balance: 175.50,
      total_purchased: 200.00,
      total_spent: 24.50
    }));
  }),
  
  // Credit packages endpoint
  rest.get('/api/v1/packages', (req, res, ctx) => {
    return res(ctx.json([
      { id: 1, name: 'Starter', credits: 50, price: 10.00, bonus_percent: 0, is_popular: false },
      { id: 2, name: 'Growth', credits: 150, price: 25.00, bonus_percent: 10, is_popular: true },
      { id: 3, name: 'Pro', credits: 500, price: 75.00, bonus_percent: 15, is_popular: false }
    ]));
  })
);

beforeAll(() => server.listen());
afterEach(() => {
  server.resetHandlers();
  jest.clearAllMocks();
});
afterAll(() => server.close());

const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } }
  });
  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
};

describe('Payment Flow Integration', () => {
  describe('PF-001 to PF-004: Flow Initialization', () => {
    it('starts with package selection', async () => {
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => {
        expect(screen.getByText('Growth')).toBeInTheDocument();
      });
    });

    it('opens payment modal when package selected', async () => {
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => {
        expect(screen.getByText('Growth')).toBeInTheDocument();
      });
      
      await user.click(screen.getByRole('button', { name: /select growth/i }));
      
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('shows correct package in modal', async () => {
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => {
        expect(screen.getByText('Growth')).toBeInTheDocument();
      });
      
      await user.click(screen.getByRole('button', { name: /select growth/i }));
      
      const modal = screen.getByRole('dialog');
      expect(within(modal).getByText('150 credits')).toBeInTheDocument();
      expect(within(modal).getByText('$25.00')).toBeInTheDocument();
    });

    it('creates payment intent on modal open', async () => {
      let intentCreated = false;
      server.use(
        rest.post('/api/v1/payments/stripe/intent', (req, res, ctx) => {
          intentCreated = true;
          return res(ctx.json({
            client_secret: 'pi_test_123_secret_456',
            payment_intent_id: 'pi_test_123',
            amount: 25.00,
            credits: 150
          }));
        })
      );
      
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => screen.getByText('Growth'));
      await user.click(screen.getByRole('button', { name: /select/i }));
      
      await waitFor(() => {
        expect(intentCreated).toBe(true);
      });
    });
  });

  describe('PF-005 to PF-007: Payment Success', () => {
    it('submits payment to Stripe', async () => {
      mockConfirmPayment.mockResolvedValue({
        paymentIntent: { status: 'succeeded', id: 'pi_test_123' }
      });
      
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => screen.getByText('Growth'));
      await user.click(screen.getByRole('button', { name: /select/i }));
      
      await waitFor(() => screen.getByRole('dialog'));
      await user.click(screen.getByRole('button', { name: /pay/i }));
      
      expect(mockConfirmPayment).toHaveBeenCalled();
    });

    it('shows success component on success', async () => {
      mockConfirmPayment.mockResolvedValue({
        paymentIntent: { status: 'succeeded', id: 'pi_test_123' }
      });
      
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => screen.getByText('Growth'));
      await user.click(screen.getByRole('button', { name: /select/i }));
      await waitFor(() => screen.getByRole('dialog'));
      await user.click(screen.getByRole('button', { name: /pay/i }));
      
      await waitFor(() => {
        expect(screen.getByText(/payment successful/i)).toBeInTheDocument();
      });
    });

    it('refreshes balance after success', async () => {
      const balanceSpy = jest.fn();
      server.use(
        rest.get('/api/v1/credits/balance', (req, res, ctx) => {
          balanceSpy();
          return res(ctx.json({ balance: 175.50 }));
        })
      );
      
      mockConfirmPayment.mockResolvedValue({
        paymentIntent: { status: 'succeeded' }
      });
      
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      // Complete payment flow
      await waitFor(() => screen.getByText('Growth'));
      await user.click(screen.getByRole('button', { name: /select/i }));
      await waitFor(() => screen.getByRole('dialog'));
      await user.click(screen.getByRole('button', { name: /pay/i }));
      await waitFor(() => screen.getByText(/payment successful/i));
      
      // Balance should be fetched
      expect(balanceSpy).toHaveBeenCalled();
    });
  });

  describe('PF-008 to PF-014: Error Handling', () => {
    it('handles card decline', async () => {
      mockConfirmPayment.mockResolvedValue({
        error: { type: 'card_error', code: 'card_declined' }
      });
      
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => screen.getByText('Growth'));
      await user.click(screen.getByRole('button', { name: /select/i }));
      await waitFor(() => screen.getByRole('dialog'));
      await user.click(screen.getByRole('button', { name: /pay/i }));
      
      await waitFor(() => {
        expect(screen.getByText(/declined/i)).toBeInTheDocument();
      });
    });

    it('handles network error with retry', async () => {
      mockConfirmPayment.mockRejectedValue(new Error('Network error'));
      
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => screen.getByText('Growth'));
      await user.click(screen.getByRole('button', { name: /select/i }));
      await waitFor(() => screen.getByRole('dialog'));
      await user.click(screen.getByRole('button', { name: /pay/i }));
      
      await waitFor(() => {
        expect(screen.getByText(/network error/i)).toBeInTheDocument();
      });
      
      // Retry button should be visible
      expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
    });

    it('user can retry after error', async () => {
      mockConfirmPayment
        .mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValueOnce({ paymentIntent: { status: 'succeeded' } });
      
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => screen.getByText('Growth'));
      await user.click(screen.getByRole('button', { name: /select/i }));
      await waitFor(() => screen.getByRole('dialog'));
      await user.click(screen.getByRole('button', { name: /pay/i }));
      
      await waitFor(() => screen.getByText(/error/i));
      await user.click(screen.getByRole('button', { name: /try again/i }));
      
      // Should return to payment form
      await waitFor(() => {
        expect(screen.getByTestId('payment-element')).toBeInTheDocument();
      });
    });

    it('user can cancel flow', async () => {
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => screen.getByText('Growth'));
      await user.click(screen.getByRole('button', { name: /select/i }));
      await waitFor(() => screen.getByRole('dialog'));
      
      // Click close button
      await user.click(screen.getByRole('button', { name: /close/i }));
      
      // Modal should close
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
  });

  describe('PF-015 to PF-018: UI Updates', () => {
    it('progress indicator updates during flow', async () => {
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => screen.getByText('Growth'));
      await user.click(screen.getByRole('button', { name: /select/i }));
      
      const modal = await screen.findByRole('dialog');
      
      // Should show step indicator
      expect(within(modal).getByText(/package/i)).toBeInTheDocument();
      expect(within(modal).getByText(/payment/i)).toBeInTheDocument();
    });

    it('balance updates in header after payment', async () => {
      // This test would verify the global balance state updates
      // Requires integration with global state/context
      
      mockConfirmPayment.mockResolvedValue({
        paymentIntent: { status: 'succeeded' }
      });
      
      const user = userEvent.setup();
      render(<PaymentFlow />, { wrapper: createWrapper() });
      
      await waitFor(() => screen.getByText('Growth'));
      await user.click(screen.getByRole('button', { name: /select/i }));
      await waitFor(() => screen.getByRole('dialog'));
      await user.click(screen.getByRole('button', { name: /pay/i }));
      
      await waitFor(() => {
        expect(screen.getByText(/payment successful/i)).toBeInTheDocument();
      });
      
      // Verify new balance is shown
      expect(screen.getByText(/\$175\.50/)).toBeInTheDocument();
    });
  });
});

describe('API Error Scenarios', () => {
  it('handles 401 unauthorized', async () => {
    server.use(
      rest.post('/api/v1/payments/stripe/intent', (req, res, ctx) => {
        return res(ctx.status(401));
      })
    );
    
    const user = userEvent.setup();
    render(<PaymentFlow />, { wrapper: createWrapper() });
    
    await waitFor(() => screen.getByText('Growth'));
    await user.click(screen.getByRole('button', { name: /select/i }));
    
    // Should show login prompt or redirect
    await waitFor(() => {
      expect(screen.getByText(/sign in/i)).toBeInTheDocument();
    });
  });

  it('handles 429 rate limit', async () => {
    server.use(
      rest.post('/api/v1/payments/stripe/intent', (req, res, ctx) => {
        return res(ctx.status(429), ctx.json({
          error: { message: 'Too many requests', retry_after: 60 }
        }));
      })
    );
    
    const user = userEvent.setup();
    render(<PaymentFlow />, { wrapper: createWrapper() });
    
    await waitFor(() => screen.getByText('Growth'));
    await user.click(screen.getByRole('button', { name: /select/i }));
    
    await waitFor(() => {
      expect(screen.getByText(/too many requests/i)).toBeInTheDocument();
    });
  });

  it('handles 500 server error gracefully', async () => {
    server.use(
      rest.post('/api/v1/payments/stripe/intent', (req, res, ctx) => {
        return res(ctx.status(500));
      })
    );
    
    const user = userEvent.setup();
    render(<PaymentFlow />, { wrapper: createWrapper() });
    
    await waitFor(() => screen.getByText('Growth'));
    await user.click(screen.getByRole('button', { name: /select/i }));
    
    await waitFor(() => {
      expect(screen.getByText(/error occurred/i)).toBeInTheDocument();
    });
  });
});
