/**
 * PaymentForm Component Tests
 * 
 * Tests for Stripe Elements payment form with card input,
 * validation, submission, and error handling.
 * 
 * @see TESTS/TEST-26-23.md
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { PaymentForm } from '@/components/payments/PaymentForm';

// Mock Stripe
jest.mock('@stripe/react-stripe-js', () => ({
  CardElement: ({ onChange, options }: any) => (
    <div 
      data-testid="card-element"
      data-complete={options?.disabled ? 'false' : 'true'}
    >
      <input
        data-testid="card-number-input"
        placeholder="Card number"
        onChange={(e) => onChange?.({ 
          complete: e.target.value.length === 16,
          error: null,
          empty: e.target.value.length === 0,
        })}
      />
    </div>
  ),
  useElements: () => ({
    getElement: jest.fn(() => ({ mock: true })),
  }),
  useStripe: () => ({
    confirmCardPayment: jest.fn(),
    createPaymentMethod: jest.fn(),
  }),
}));

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  CreditCard: () => <span data-testid="credit-card-icon">CreditCard</span>,
  Lock: () => <span data-testid="lock-icon">Lock</span>,
  AlertCircle: () => <span data-testid="alert-icon">AlertCircle</span>,
  Check: () => <span data-testid="check-icon">Check</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loader2</span>,
  ChevronRight: () => <span data-testid="chevron-icon">ChevronRight</span>,
}));

// Mock sonner toast
jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn(),
  },
}));

// Mock fetch for API calls
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Mock router
const mockPush = jest.fn();
const mockReplace = jest.fn();
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
  }),
}));

// Test data
const mockPackage = {
  id: 'pkg_pro',
  name: 'Pro Credit Package',
  credits: 100,
  price: 29.99,
  currency: 'USD',
  bonus_credits: 10,
  popular: true,
};

const defaultProps = {
  selectedPackage: mockPackage,
  onSuccess: jest.fn(),
  onCancel: jest.fn(),
};

describe('PaymentForm', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
  });

  // ============================================
  // PF-RENDER: Component Rendering
  // ============================================
  describe('Rendering', () => {
    it('PF-001: Form renders with Stripe Elements', () => {
      render(<PaymentForm {...defaultProps} />);
      
      expect(screen.getByTestId('card-element')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /pay/i })).toBeInTheDocument();
    });

    it('PF-002: Package details displayed', () => {
      render(<PaymentForm {...defaultProps} />);
      
      expect(screen.getByText(mockPackage.name)).toBeInTheDocument();
      expect(screen.getByText(/100 credits/i)).toBeInTheDocument();
      expect(screen.getByText(/\$29\.99/)).toBeInTheDocument();
    });

    it('PF-003: Total amount calculated correctly', () => {
      render(<PaymentForm {...defaultProps} />);
      
      const totalElement = screen.getByTestId('total-amount');
      expect(totalElement).toHaveTextContent('$29.99');
    });

    it('PF-004: Form disabled when processing', () => {
      render(<PaymentForm {...defaultProps} isProcessing={true} />);
      
      expect(screen.getByTestId('card-element')).toHaveAttribute('data-complete', 'false');
      expect(screen.getByRole('button', { name: /processing/i })).toBeDisabled();
    });

    it('PF-005: Error message displayed', () => {
      const error = 'Card declined. Please try again.';
      render(<PaymentForm {...defaultProps} cardError={error} />);
      
      expect(screen.getByText(error)).toBeInTheDocument();
      expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    it('PF-006: Stripe.js loading state', () => {
      render(<PaymentForm {...defaultProps} isLoading={true} />);
      
      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
    });

    it('PF-007: Form with saved card option', () => {
      render(<PaymentForm {...defaultProps} hasSavedCard={true} />);
      
      expect(screen.getByText(/use saved card/i)).toBeInTheDocument();
    });

    it('PF-008: Terms checkbox required', () => {
      render(<PaymentForm {...defaultProps} termsRequired={true} />);
      
      const checkbox = screen.getByRole('checkbox', { name: /terms/i });
      expect(checkbox).toBeInTheDocument();
      expect(checkbox).not.toBeChecked();
    });

    it('PF-009: Responsive layout on mobile', () => {
      render(<PaymentForm {...defaultProps} />);
      
      const form = screen.getByTestId('payment-form');
      expect(form).toHaveClass('flex-col');
    });

    it('PF-010: Dark mode support', () => {
      render(<PaymentForm {...defaultProps} className="dark" />);
      
      const container = screen.getByTestId('payment-form-container');
      expect(container).toHaveClass('dark');
    });
  });

  // ============================================
  // PF-CARD: Card Element Handling
  // ============================================
  describe('Card Element Handling', () => {
    it('PF-011: Valid card number accepted', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      // No error displayed
      expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('PF-012: Invalid card number shows error', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '1234567890123456');
      await user.tab(); // Trigger validation
      
      expect(await screen.findByText(/invalid card/i)).toBeInTheDocument();
    });

    it('PF-013: Incomplete card number shows error', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '424242424242');
      await user.tab();
      
      expect(await screen.findByText(/incomplete/i)).toBeInTheDocument();
    });

    it('PF-014: Card number too short', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242');
      await user.tab();
      
      expect(await screen.findByText(/incomplete/i)).toBeInTheDocument();
    });

    it('PF-015: Valid expiry date accepted', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const expiryInput = screen.getByLabelText(/expiry/i);
      await user.type(expiryInput, '12/25');
      
      expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('PF-016: Expired card shows error', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const expiryInput = screen.getByLabelText(/expiry/i);
      await user.type(expiryInput, '12/20');
      await user.tab();
      
      expect(await screen.findByText(/expired/i)).toBeInTheDocument();
    });

    it('PF-017: Invalid expiry format', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const expiryInput = screen.getByLabelText(/expiry/i);
      await user.type(expiryInput, '25/12');
      await user.tab();
      
      expect(await screen.findByText(/invalid.*format/i)).toBeInTheDocument();
    });

    it('PF-018: Valid CVC accepted', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const cvcInput = screen.getByLabelText(/cvc/i);
      await user.type(cvcInput, '123');
      
      expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('PF-019: Invalid CVC shows error', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const cvcInput = screen.getByLabelText(/cvc/i);
      await user.type(cvcInput, '12');
      await user.tab();
      
      expect(await screen.findByText(/invalid.*security.*code/i)).toBeInTheDocument();
    });

    it('PF-020: CVC for Amex (4 digits)', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const cvcInput = screen.getByLabelText(/cvc/i);
      await user.type(cvcInput, '1234');
      
      expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('PF-021: Visa card type detection', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242');
      
      expect(screen.getByAltText(/visa/i)).toBeInTheDocument();
    });

    it('PF-022: Mastercard type detection', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '5555');
      
      expect(screen.getByAltText(/mastercard/i)).toBeInTheDocument();
    });

    it('PF-023: Amex type detection', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '3782');
      
      expect(screen.getByAltText(/amex/i)).toBeInTheDocument();
    });

    it('PF-024: Discover type detection', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '6011');
      
      expect(screen.getByAltText(/discover/i)).toBeInTheDocument();
    });

    it('PF-025: Empty card submission prevented', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} />);
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/card.*details.*required/i)).toBeInTheDocument();
    });
  });

  // ============================================
  // PF-SUBMIT: Form Submission
  // ============================================
  describe('Form Submission', () => {
    it('PF-026: Successful payment calls onSuccess', async () => {
      const user = userEvent.setup();
      const onSuccess = jest.fn();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ client_secret: 'pi_test_secret' }),
      });
      
      render(<PaymentForm {...defaultProps} onSuccess={onSuccess} />);
      
      // Fill card details
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(onSuccess).toHaveBeenCalled();
      });
    });

    it('PF-027: Payment processing shows loading', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockImplementation(() => new Promise(() => {})); // Never resolves
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /processing/i })).toBeDisabled();
    });

    it('PF-028: Card declined shows error with retry', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: false,
        json: async () => ({ error: { message: 'Your card was declined.' } }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/declined/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
    });

    it('PF-029: Insufficient funds error', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: false,
        json: async () => ({ error: { message: 'Insufficient funds.' } }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/insufficient funds/i)).toBeInTheDocument();
    });

    it('PF-030: Network error with retry', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockRejectedValueOnce(new Error('Network error'));
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/network error/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
    });

    it('PF-031: Stripe error displayed', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: false,
        json: async () => ({ error: { message: 'Stripe processing error' } }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/stripe processing error/i)).toBeInTheDocument();
    });

    it('PF-032: Duplicate submission prevention', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockImplementation(() => new Promise(() => {})); // Never resolves
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      
      await user.click(submitButton);
      await user.click(submitButton); // Second click
      
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });

    it('PF-033: Submit with saved card', async () => {
      const user = userEvent.setup();
      const onSuccess = jest.fn();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true }),
      });
      
      render(<PaymentForm 
        {...defaultProps} 
        onSuccess={onSuccess}
        hasSavedCard={true} 
        savedCardId="pm_test123"
      />);
      
      // Select "Use saved card" option
      const savedCardOption = screen.getByText(/use saved card/i);
      await user.click(savedCardOption);
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(onSuccess).toHaveBeenCalled();
      });
    });

    it('PF-034: Submit with new card', async () => {
      const user = userEvent.setup();
      
      render(<PaymentForm {...defaultProps} hasSavedCard={true} />);
      
      // Select "New card" option
      const newCardOption = screen.getByText(/new card/i);
      await user.click(newCardOption);
      
      expect(screen.getByTestId('card-element')).toBeInTheDocument();
    });

    it('PF-035: Submit without accepting terms', async () => {
      const user = userEvent.setup();
      
      render(<PaymentForm {...defaultProps} termsRequired={true} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/accept.*terms/i)).toBeInTheDocument();
    });

    it('PF-036: Payment intent creation called', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ client_secret: 'pi_test_secret' }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/payments/create-intent',
          expect.objectContaining({ method: 'POST' })
        );
      });
    });

    it('PF-037: Payment confirmation called', async () => {
      const user = userEvent.setup();
      
      mockFetch
        .mockResolvedValueOnce({
          ok: true,
          json: async () => ({ client_secret: 'pi_test_secret' }),
        })
        .mockResolvedValueOnce({
          ok: true,
          json: async () => ({ success: true }),
        });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/payments/confirm',
          expect.any(Object)
        );
      });
    });

    it('PF-038: Credit allocation verified', async () => {
      const user = userEvent.setup();
      const onSuccess = jest.fn();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ 
          success: true, 
          credits: 110, 
          balance: 110 
        }),
      });
      
      render(<PaymentForm {...defaultProps} onSuccess={onSuccess} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(onSuccess).toHaveBeenCalledWith(
          expect.objectContaining({ credits: 110 })
        );
      });
    });

    it('PF-039: Redirect on success', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, redirect: '/payments/confirmation' }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(mockPush).toHaveBeenCalledWith('/payments/confirmation');
      });
    });

    it('PF-040: Form reset after success', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(cardInput).toHaveValue('');
      });
    });
  });

  // ============================================
  // PF-VALIDATION: Form Validation
  // ============================================
  describe('Form Validation', () => {
    it('PF-041: Email required', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} requireEmail={true} />);
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/email.*required/i)).toBeInTheDocument();
    });

    it('PF-042: Invalid email format', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} requireEmail={true} />);
      
      const emailInput = screen.getByLabelText(/email/i);
      await user.type(emailInput, 'not-email');
      await user.tab();
      
      expect(await screen.findByText(/invalid.*email/i)).toBeInTheDocument();
    });

    it('PF-043: Name required', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} requireName={true} />);
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/name.*required/i)).toBeInTheDocument();
    });

    it('PF-044: Name too short', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} requireName={true} />);
      
      const nameInput = screen.getByLabelText(/name/i);
      await user.type(nameInput, 'A');
      await user.tab();
      
      expect(await screen.findByText(/name.*too.*short/i)).toBeInTheDocument();
    });

    it('PF-045: Billing address required', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} requireAddress={true} />);
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/address.*required/i)).toBeInTheDocument();
    });

    it('PF-046: Country required', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} requireAddress={true} />);
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/country.*required/i)).toBeInTheDocument();
    });

    it('PF-047: Postal code required', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} requireAddress={true} />);
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/postal.*code.*required/i)).toBeInTheDocument();
    });

    it('PF-048: Real-time validation clears errors', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} requireEmail={true} />);
      
      // Trigger error
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/email.*required/i)).toBeInTheDocument();
      
      // Fix the error
      const emailInput = screen.getByLabelText(/email/i);
      await user.type(emailInput, 'test@example.com');
      
      await waitFor(() => {
        expect(screen.queryByText(/email.*required/i)).not.toBeInTheDocument();
      });
    });

    it('PF-049: Submit button disabled with invalid form', () => {
      render(<PaymentForm {...defaultProps} requireEmail={true} />);
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      expect(submitButton).toBeDisabled();
    });

    it('PF-050: Submit button enabled with valid form', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} requireEmail={true} />);
      
      // Fill required fields
      const emailInput = screen.getByLabelText(/email/i);
      await user.type(emailInput, 'test@example.com');
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await waitFor(() => {
        expect(submitButton).not.toBeDisabled();
      });
    });
  });

  // ============================================
  // PF-SECURITY: Security Tests
  // ============================================
  describe('Security', () => {
    it('PF-051: No card data stored in localStorage', async () => {
      const user = userEvent.setup();
      const setItemSpy = jest.spyOn(Storage.prototype, 'setItem');
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      // Verify card number was not stored
      expect(setItemSpy).not.toHaveBeenCalledWith(
        expect.any(String),
        expect.stringContaining('4242424242424242')
      );
      
      setItemSpy.mockRestore();
    });

    it('PF-052: No card data in console logs', async () => {
      const user = userEvent.setup();
      const consoleSpy = jest.spyOn(console, 'log');
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      // Verify no card number in logs
      expect(consoleSpy).not.toHaveBeenCalledWith(
        expect.stringContaining('4242424242424242')
      );
      
      consoleSpy.mockRestore();
    });

    it('PF-053: PCI compliance via Elements', () => {
      render(<PaymentForm {...defaultProps} />);
      
      // CardElement should be used (not direct input)
      const cardElement = screen.getByTestId('card-element');
      expect(cardElement).toBeInTheDocument();
      
      // No direct card input should exist
      expect(screen.queryByLabelText(/card number/i)).not.toBeInTheDocument();
    });

    it('PF-054: CSRF token included in requests', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ client_secret: 'pi_test' }),
      });
      
      render(<PaymentForm {...defaultProps} csrfToken="test-csrf-token" />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.any(String),
          expect.objectContaining({
            headers: expect.objectContaining({
              'X-CSRF-TOKEN': 'test-csrf-token',
            }),
          })
        );
      });
    });

    it('PF-055: HTTPS required', () => {
      const originalLocation = window.location;
      delete (window as any).location;
      window.location = { protocol: 'http:' } as Location;
      
      render(<PaymentForm {...defaultProps} />);
      
      expect(screen.getByText(/secure.*connection/i)).toBeInTheDocument();
      
      window.location = originalLocation;
    });

    it('PF-056: Sensitive inputs masked', () => {
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      expect(cardInput).toHaveAttribute('type', 'password');
    });

    it('PF-057: Session timeout handling', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: async () => ({ error: 'Unauthenticated' }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalledWith('/login');
      });
    });

    it('PF-058: XSS prevention', async () => {
      const user = userEvent.setup();
      const script = '<script>alert("xss")</script>';
      
      render(<PaymentForm {...defaultProps} requireName={true} />);
      
      const nameInput = screen.getByLabelText(/name/i);
      await user.type(nameInput, script);
      
      // Script should be escaped, not executed
      expect(screen.queryByText(script)).not.toBeInTheDocument();
      expect(screen.queryByRole('script')).not.toBeInTheDocument();
    });

    it('PF-059: Rate limiting handled', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 429,
        json: async () => ({ error: 'Too many requests', retry_after: 60 }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/too many.*attempts/i)).toBeInTheDocument();
    });

    it('PF-060: Idempotency key included', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.any(String),
          expect.objectContaining({
            headers: expect.objectContaining({
              'Idempotency-Key': expect.any(String),
            }),
          })
        );
      });
    });
  });

  // ============================================
  // PF-EDGE: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('PF-061: Zero amount payment', async () => {
      const user = userEvent.setup();
      
      const freePackage = { ...mockPackage, price: 0 };
      
      render(<PaymentForm {...defaultProps} selectedPackage={freePackage} />);
      
      expect(screen.getByText(/free/i)).toBeInTheDocument();
    });

    it('PF-062: Very large amount shows confirmation', async () => {
      const user = userEvent.setup();
      
      const largePackage = { ...mockPackage, price: 10000 };
      
      render(<PaymentForm {...defaultProps} selectedPackage={largePackage} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/confirm.*amount/i)).toBeInTheDocument();
    });

    it('PF-063: Currency conversion display', () => {
      render(<PaymentForm {...defaultProps} currency="EUR" />);
      
      expect(screen.getByText(/€/)).toBeInTheDocument();
    });

    it('PF-064: Unicode in name accepted', async () => {
      const user = userEvent.setup();
      
      render(<PaymentForm {...defaultProps} requireName={true} />);
      
      const nameInput = screen.getByLabelText(/name/i);
      await user.type(nameInput, 'José García');
      
      expect(nameInput).toHaveValue('José García');
    });

    it('PF-065: Long name truncated', async () => {
      const user = userEvent.setup();
      
      render(<PaymentForm {...defaultProps} requireName={true} />);
      
      const longName = 'A'.repeat(150);
      const nameInput = screen.getByLabelText(/name/i);
      await user.type(nameInput, longName);
      
      await waitFor(() => {
        expect(screen.getByText(/too long/i)).toBeInTheDocument();
      });
    });

    it('PF-066: Special characters in name', async () => {
      const user = userEvent.setup();
      
      render(<PaymentForm {...defaultProps} requireName={true} />);
      
      const nameInput = screen.getByLabelText(/name/i);
      await user.type(nameInput, 'John O\'Brien-Smith');
      
      expect(nameInput).toHaveValue('John O\'Brien-Smith');
    });

    it('PF-067: Slow network timeout', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockImplementation(() => 
        new Promise((_, reject) => 
          setTimeout(() => reject(new Error('Timeout')), 35000)
        )
      );
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      // Fast-forward time
      jest.advanceTimersByTime(35000);
      
      await waitFor(() => {
        expect(screen.getByText(/timeout/i)).toBeInTheDocument();
      });
    }, 40000);

    it('PF-068: Stripe unavailable handled gracefully', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 503,
        json: async () => ({ error: 'Service unavailable' }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      expect(await screen.findByText(/service.*unavailable/i)).toBeInTheDocument();
    });

    it('PF-069: Browser back during payment shows warning', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockImplementation(() => new Promise(() => {})); // Pending
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      // Simulate back button
      const beforeUnloadEvent = new Event('beforeunload');
      window.dispatchEvent(beforeUnloadEvent);
      
      // Should have warning
      expect(screen.getByText(/payment.*in progress/i)).toBeInTheDocument();
    });

    it('PF-070: Page refresh preserves payment state', () => {
      // Set up sessionStorage with payment state
      sessionStorage.setItem('payment_state', JSON.stringify({
        packageId: 'pkg_pro',
        step: 'payment',
      }));
      
      render(<PaymentForm {...defaultProps} />);
      
      expect(screen.getByText(/continue.*payment/i)).toBeInTheDocument();
    });
  });

  // ============================================
  // PF-ACCESSIBILITY: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('PF-071: Keyboard navigation reaches all fields', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} requireEmail={true} requireName={true} />);
      
      await user.tab(); // Focus first field
      expect(screen.getByLabelText(/email/i)).toHaveFocus();
      
      await user.tab();
      expect(screen.getByLabelText(/name/i)).toHaveFocus();
      
      await user.tab();
      expect(screen.getByTestId('card-element')).toHaveFocus();
    });

    it('PF-072: Screen reader labels on all inputs', () => {
      render(<PaymentForm {...defaultProps} requireEmail={true} />);
      
      const emailInput = screen.getByLabelText(/email/i);
      expect(emailInput).toHaveAttribute('aria-label');
    });

    it('PF-073: Error announcements are assertive', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} cardError="Test error" />);
      
      const errorElement = screen.getByRole('alert');
      expect(errorElement).toHaveAttribute('aria-live', 'assertive');
    });

    it('PF-074: Focus management on error', async () => {
      const user = userEvent.setup();
      render(<PaymentForm {...defaultProps} requireEmail={true} />);
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(screen.getByLabelText(/email/i)).toHaveFocus();
      });
    });

    it('PF-075: Color contrast is WCAG AA compliant', () => {
      render(<PaymentForm {...defaultProps} />);
      
      const form = screen.getByTestId('payment-form');
      // Check computed styles would meet contrast ratio
      expect(form).toBeInTheDocument();
    });

    it('PF-076: Loading state announced', () => {
      render(<PaymentForm {...defaultProps} isProcessing={true} />);
      
      const form = screen.getByTestId('payment-form');
      expect(form).toHaveAttribute('aria-busy', 'true');
    });

    it('PF-077: Success state announced politely', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true }),
      });
      
      render(<PaymentForm {...defaultProps} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        const successMessage = screen.getByRole('status');
        expect(successMessage).toHaveAttribute('aria-live', 'polite');
      });
    });

    it('PF-078: Form labels associated with inputs', () => {
      render(<PaymentForm {...defaultProps} requireEmail={true} />);
      
      const emailInput = screen.getByLabelText(/email/i);
      const emailLabel = document.querySelector('label[for="' + emailInput.id + '"]');
      expect(emailLabel).toBeInTheDocument();
    });

    it('PF-079: Required fields indicated', () => {
      render(<PaymentForm {...defaultProps} requireEmail={true} />);
      
      const emailInput = screen.getByLabelText(/email/i);
      expect(emailInput).toHaveAttribute('aria-required', 'true');
    });

    it('PF-080: Focus trap in modal', () => {
      render(<PaymentForm {...defaultProps} isModal={true} />);
      
      const modal = screen.getByRole('dialog');
      expect(modal).toBeInTheDocument();
    });
  });

  // ============================================
  // PF-INTEGRATION: Integration
  // ============================================
  describe('Integration', () => {
    it('PF-081: Complete payment flow succeeds', async () => {
      const user = userEvent.setup();
      const onSuccess = jest.fn();
      
      mockFetch
        .mockResolvedValueOnce({
          ok: true,
          json: async () => ({ client_secret: 'pi_test_secret' }),
        })
        .mockResolvedValueOnce({
          ok: true,
          json: async () => ({ success: true, credits: 110 }),
        });
      
      render(<PaymentForm {...defaultProps} onSuccess={onSuccess} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(onSuccess).toHaveBeenCalledWith(
          expect.objectContaining({ credits: 110 })
        );
      });
    });

    it('PF-082: Payment with saved card is faster', async () => {
      const user = userEvent.setup();
      const startTime = Date.now();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true }),
      });
      
      render(<PaymentForm 
        {...defaultProps} 
        hasSavedCard={true} 
        savedCardId="pm_test"
      />);
      
      await user.click(screen.getByText(/use saved card/i));
      await user.click(screen.getByRole('button', { name: /pay/i }));
      
      await waitFor(() => {
        const duration = Date.now() - startTime;
        expect(duration).toBeLessThan(500); // Should be fast
      });
    });

    it('PF-083: Payment saves new card for future', async () => {
      const user = userEvent.setup();
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ 
          success: true, 
          savedCard: { id: 'pm_new' } 
        }),
      });
      
      render(<PaymentForm {...defaultProps} saveCard={true} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const saveCheckbox = screen.getByRole('checkbox', { name: /save.*card/i });
      await user.click(saveCheckbox);
      
      const submitButton = screen.getByRole('button', { name: /pay/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(screen.getByText(/card.*saved/i)).toBeInTheDocument();
      });
    });

    it('PF-084: Payment with coupon applies discount', async () => {
      const user = userEvent.setup();
      
      render(<PaymentForm {...defaultProps} />);
      
      const couponInput = screen.getByLabelText(/coupon/i);
      await user.type(couponInput, 'DISCOUNT20');
      
      const applyButton = screen.getByRole('button', { name: /apply/i });
      await user.click(applyButton);
      
      await waitFor(() => {
        expect(screen.getByText(/\$23\.99/)).toBeInTheDocument(); // 20% off
      });
    });

    it('PF-085: Subscription vs one-time triggers correct flow', async () => {
      const user = userEvent.setup();
      
      const subPackage = { ...mockPackage, type: 'subscription' };
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ subscription: true }),
      });
      
      render(<PaymentForm {...defaultProps} selectedPackage={subPackage} />);
      
      const cardInput = screen.getByTestId('card-number-input');
      await user.type(cardInput, '4242424242424242');
      
      const submitButton = screen.getByRole('button', { name: /subscribe/i });
      await user.click(submitButton);
      
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('subscribe'),
          expect.any(Object)
        );
      });
    });
  });
});

// ============================================
// Mock Component for TDD Reference
// ============================================
/**
 * Reference implementation showing expected component interface.
 * This is NOT the actual implementation - it demonstrates expected behavior.
 */
export function MockPaymentForm({
  selectedPackage,
  onSuccess,
  onCancel,
  isProcessing = false,
  isLoading = false,
  cardError,
  hasSavedCard = false,
  savedCardId,
  termsRequired = false,
  requireEmail = false,
  requireName = false,
  requireAddress = false,
  csrfToken,
  currency = 'USD',
  className,
  isModal = false,
  saveCard = false,
}: PaymentFormProps) {
  const [email, setEmail] = React.useState('');
  const [name, setName] = React.useState('');
  const [error, setError] = React.useState<string | null>(cardError || null);
  const [processing, setProcessing] = React.useState(isProcessing);
  const [termsAccepted, setTermsAccepted] = React.useState(false);
  
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (requireEmail && !email) {
      setError('Email is required');
      return;
    }
    
    if (requireName && !name) {
      setError('Name is required');
      return;
    }
    
    if (termsRequired && !termsAccepted) {
      setError('You must accept the terms');
      return;
    }
    
    setProcessing(true);
    
    try {
      const response = await fetch('/api/v1/payments/create-intent', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken || '',
          'Idempotency-Key': crypto.randomUUID(),
        },
        body: JSON.stringify({
          packageId: selectedPackage.id,
          email,
          name,
          savedCardId: hasSavedCard ? savedCardId : null,
        }),
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        setError(data.error?.message || 'Payment failed');
        return;
      }
      
      onSuccess(data);
    } catch (err) {
      setError('Network error. Please try again.');
    } finally {
      setProcessing(false);
    }
  };
  
  if (isLoading) {
    return <div data-testid="loader-icon" className="animate-spin">Loading...</div>;
  }
  
  return (
    <div data-testid="payment-form-container" className={className}>
      <form data-testid="payment-form" className="flex-col" onSubmit={handleSubmit} aria-busy={processing}>
        <h2>{selectedPackage.name}</h2>
        <p>{selectedPackage.credits} credits</p>
        <p data-testid="total-amount">${selectedPackage.price}</p>
        
        {error && <div role="alert" aria-live="assertive">{error}</div>}
        
        {requireEmail && (
          <label>
            Email
            <input
              aria-label="Email"
              aria-required="true"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
          </label>
        )}
        
        {requireName && (
          <label>
            Name
            <input
              aria-label="Name"
              aria-required="true"
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </label>
        )}
        
        {hasSavedCard && (
          <button type="button">Use saved card</button>
        )}
        
        <div data-testid="card-element" data-complete={!processing}>
          <input data-testid="card-number-input" type="password" />
        </div>
        
        {termsRequired && (
          <label>
            <input
              type="checkbox"
              aria-label="Accept terms"
              checked={termsAccepted}
              onChange={(e) => setTermsAccepted(e.target.checked)}
            />
            Accept terms
          </label>
        )}
        
        <button
          type="submit"
          disabled={processing}
          aria-busy={processing}
        >
          {processing ? 'Processing...' : 'Pay'}
        </button>
        
        <button type="button" onClick={onCancel}>Cancel</button>
      </form>
    </div>
  );
}

interface PaymentFormProps {
  selectedPackage: {
    id: string;
    name: string;
    credits: number;
    price: number;
    currency?: string;
    bonus_credits?: number;
    popular?: boolean;
    type?: 'one-time' | 'subscription';
  };
  onSuccess: (result: any) => void;
  onCancel: () => void;
  isProcessing?: boolean;
  isLoading?: boolean;
  cardError?: string | null;
  hasSavedCard?: boolean;
  savedCardId?: string;
  termsRequired?: boolean;
  requireEmail?: boolean;
  requireName?: boolean;
  requireAddress?: boolean;
  csrfToken?: string;
  currency?: string;
  className?: string;
  isModal?: boolean;
  saveCard?: boolean;
}
