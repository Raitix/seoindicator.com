/**
 * StripeCheckout Component Tests
 * TASK-26-23: Stripe Payment Flow
 * 
 * Tests for Stripe Elements integration and payment submission.
 * Uses mocked Stripe.js for unit testing without actual API calls.
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { StripeCheckout } from '@/components/payments/StripeCheckout';

// Mock Stripe.js
const mockConfirmPayment = jest.fn();
const mockStripe = {
  confirmPayment: mockConfirmPayment,
  elements: jest.fn(() => mockElements),
};

const mockElements = {
  submit: jest.fn(),
  getElement: jest.fn(),
};

// Mock @stripe/react-stripe-js
jest.mock('@stripe/react-stripe-js', () => ({
  Elements: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  PaymentElement: () => <div data-testid="payment-element">Payment Element</div>,
  useStripe: () => mockStripe,
  useElements: () => mockElements,
}));

jest.mock('@stripe/stripe-js', () => ({
  loadStripe: jest.fn(() => Promise.resolve(mockStripe)),
  Appearance: {},
}));

// Mock credit package
const mockPackage = {
  id: 2,
  name: 'Growth',
  credits: 150,
  price: 25.00,
  bonus_percent: 10,
  is_popular: true
};

const mockPaymentIntent = {
  client_secret: 'pi_test_123_secret_456',
  payment_intent_id: 'pi_test_123',
  amount: 25.00,
  credits: 150
};

describe('StripeCheckout', () => {
  const mockOnSuccess = jest.fn();
  const mockOnError = jest.fn();
  const mockOnCancel = jest.fn();

  const defaultProps = {
    creditPackage: mockPackage,
    paymentIntent: mockPaymentIntent,
    onSuccess: mockOnSuccess,
    onError: mockOnError,
    onCancel: mockOnCancel
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockConfirmPayment.mockResolvedValue({ paymentIntent: { status: 'succeeded' } });
  });

  describe('SC-001 to SC-003: Initial Rendering', () => {
    it('renders payment element', () => {
      render(<StripeCheckout {...defaultProps} />);
      expect(screen.getByTestId('payment-element')).toBeInTheDocument();
    });

    it('shows package summary with credits', () => {
      render(<StripeCheckout {...defaultProps} />);
      
      expect(screen.getByText('150 credits')).toBeInTheDocument();
      expect(screen.getByText('$25.00')).toBeInTheDocument();
    });

    it('shows pay button with correct amount', () => {
      render(<StripeCheckout {...defaultProps} />);
      
      const payButton = screen.getByRole('button', { name: /pay \$25\.00/i });
      expect(payButton).toBeInTheDocument();
    });
  });

  describe('SC-004 to SC-008: Payment Submission', () => {
    it('disables pay button during processing', () => {
      render(<StripeCheckout {...defaultProps} isProcessing={true} />);
      
      const payButton = screen.getByRole('button', { name: /pay/i });
      expect(payButton).toBeDisabled();
    });

    it('shows spinner on button during processing', () => {
      render(<StripeCheckout {...defaultProps} isProcessing={true} />);
      
      expect(screen.getByTestId('button-spinner')).toBeInTheDocument();
    });

    it('calls confirmPayment when pay is clicked', async () => {
      const user = userEvent.setup();
      render(<StripeCheckout {...defaultProps} />);
      
      const payButton = screen.getByRole('button', { name: /pay \$25\.00/i });
      await user.click(payButton);
      
      await waitFor(() => {
        expect(mockConfirmPayment).toHaveBeenCalledWith({
          elements: mockElements,
          confirmParams: {
            return_url: expect.stringContaining('/payments/return'),
          },
        });
      });
    });

    it('calls onSuccess on successful payment', async () => {
      const user = userEvent.setup();
      render(<StripeCheckout {...defaultProps} />);
      
      const payButton = screen.getByRole('button', { name: /pay \$25\.00/i });
      await user.click(payButton);
      
      await waitFor(() => {
        expect(mockOnSuccess).toHaveBeenCalledWith({
          paymentIntentId: 'pi_test_123',
          amount: 25.00,
          credits: 150
        });
      });
    });
  });

  describe('SC-009 to SC-011: 3D Secure Handling', () => {
    it('handles 3D Secure redirect', async () => {
      mockConfirmPayment.mockResolvedValue({
        paymentIntent: { status: 'requires_action' },
        error: null
      });
      
      const user = userEvent.setup();
      render(<StripeCheckout {...defaultProps} />);
      
      const payButton = screen.getByRole('button', { name: /pay/i });
      await user.click(payButton);
      
      // Should show 3D Secure prompt
      await waitFor(() => {
        expect(screen.getByText(/3d secure/i)).toBeInTheDocument();
      });
    });

    it('handles 3D Secure success', async () => {
      mockConfirmPayment
        .mockResolvedValueOnce({
          paymentIntent: { status: 'requires_action' }
        })
        .mockResolvedValueOnce({
          paymentIntent: { status: 'succeeded' }
        });
      
      // Simulate return from 3DS
      const user = userEvent.setup();
      render(<StripeCheckout {...defaultProps} />);
      
      await user.click(screen.getByRole('button', { name: /pay/i }));
      
      // After 3DS success
      expect(mockOnSuccess).toHaveBeenCalled();
    });

    it('handles 3D Secure failure', async () => {
      mockConfirmPayment.mockResolvedValue({
        error: {
          type: 'validation_error',
          message: '3D Secure authentication failed'
        }
      });
      
      const user = userEvent.setup();
      render(<StripeCheckout {...defaultProps} />);
      
      await user.click(screen.getByRole('button', { name: /pay/i }));
      
      await waitFor(() => {
        expect(mockOnError).toHaveBeenCalledWith(
          expect.objectContaining({
            type: 'validation_error'
          })
        );
      });
    });
  });

  describe('SC-012 to SC-013: Loading States', () => {
    it('shows loading skeleton while Stripe loads', () => {
      render(<StripeCheckout {...defaultProps} isLoading={true} />);
      
      expect(screen.getByTestId('skeleton-loader')).toBeInTheDocument();
    });

    it('shows error state when Stripe fails to load', () => {
      render(<StripeCheckout {...defaultProps} loadError={true} />);
      
      expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });
  });

  describe('SC-014 to SC-016: Save Card & Legal', () => {
    it('shows save card checkbox', () => {
      render(<StripeCheckout {...defaultProps} />);
      
      const checkbox = screen.getByLabelText(/save card for future/i);
      expect(checkbox).toBeInTheDocument();
    });

    it('passes saveCard state to submit', async () => {
      const user = userEvent.setup();
      render(<StripeCheckout {...defaultProps} />);
      
      const checkbox = screen.getByLabelText(/save card for future/i);
      await user.click(checkbox);
      
      const payButton = screen.getByRole('button', { name: /pay/i });
      await user.click(payButton);
      
      // Verify saveCard was included
      expect(mockConfirmPayment).toHaveBeenCalledWith(
        expect.objectContaining({
          confirmParams: expect.objectContaining({
            payment_method_data: expect.objectContaining({
              save_for_future: true
            })
          })
        })
      );
    });

    it('shows legal disclaimer', () => {
      render(<StripeCheckout {...defaultProps} />);
      
      expect(screen.getByText(/secured by stripe/i)).toBeInTheDocument();
    });
  });

  describe('SC-017 to SC-018: Validation & Currency', () => {
    it('disables submit when payment element has errors', async () => {
      render(<StripeCheckout {...defaultProps} hasValidationError={true} />);
      
      const payButton = screen.getByRole('button', { name: /pay/i });
      expect(payButton).toBeDisabled();
    });

    it('shows currency in USD format', () => {
      render(<StripeCheckout {...defaultProps} />);
      
      expect(screen.getByText('$25.00')).toBeInTheDocument();
    });

    it('formats large amounts correctly', () => {
      const largePackage = { ...mockPackage, price: 1000 };
      render(<StripeCheckout {...defaultProps} creditPackage={largePackage} />);
      
      expect(screen.getByText('$1,000.00')).toBeInTheDocument();
    });
  });
});
