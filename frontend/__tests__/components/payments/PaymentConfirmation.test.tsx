/**
 * PaymentConfirmation Component Tests
 * 
 * Tests for payment success/error states, retry mechanism,
 * receipt handling, and transaction details display.
 * 
 * @see TESTS/TEST-26-23.md
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { PaymentConfirmation } from '@/components/payments/PaymentConfirmation';

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  CheckCircle: () => <span data-testid="check-circle-icon">CheckCircle</span>,
  XCircle: () => <span data-testid="x-circle-icon">XCircle</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loader2</span>,
  Download: () => <span data-testid="download-icon">Download</span>,
  RefreshCw: () => <span data-testid="refresh-icon">RefreshCw</span>,
  Mail: () => <span data-testid="mail-icon">Mail</span>,
  ArrowLeft: () => <span data-testid="arrow-left-icon">ArrowLeft</span>,
  ExternalLink: () => <span data-testid="external-link-icon">ExternalLink</span>,
  AlertTriangle: () => <span data-testid="alert-icon">AlertTriangle</span>,
}));

// Mock router
const mockPush = jest.fn();
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
}));

// Mock confetti
jest.mock('canvas-confetti', () => ({
  default: jest.fn(),
}));

// Test data
const mockTransaction = {
  id: 'txn_123456',
  amount: 29.99,
  currency: 'USD',
  credits: 110,
  status: 'completed' as const,
  payment_method: 'Visa ****4242',
  created_at: '2026-03-14T19:30:00Z',
  receipt_url: 'https://receipt.url',
};

const defaultProps = {
  status: 'success' as const,
  transaction: mockTransaction,
  onRetry: jest.fn(),
  onBackToDashboard: jest.fn(),
};

describe('PaymentConfirmation', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================
  // PC-RENDER: Component Rendering
  // ============================================
  describe('Rendering', () => {
    it('PC-001: Success state renders correctly', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      expect(screen.getByTestId('check-circle-icon')).toBeInTheDocument();
      expect(screen.getByText(/payment.*successful/i)).toBeInTheDocument();
    });

    it('PC-002: Error state renders correctly', () => {
      render(<PaymentConfirmation {...defaultProps} status="error" error="Card declined" />);
      
      expect(screen.getByTestId('x-circle-icon')).toBeInTheDocument();
      expect(screen.getByText(/card.*declined/i)).toBeInTheDocument();
    });

    it('PC-003: Pending state renders correctly', () => {
      render(<PaymentConfirmation {...defaultProps} status="pending" />);
      
      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
      expect(screen.getByText(/processing/i)).toBeInTheDocument();
    });

    it('PC-004: Cancelled state renders correctly', () => {
      render(<PaymentConfirmation {...defaultProps} status="cancelled" />);
      
      expect(screen.getByText(/cancelled/i)).toBeInTheDocument();
    });

    it('PC-005: Transaction details displayed', () => {
      render(<PaymentConfirmation {...defaultProps} />);
      
      expect(screen.getByText(/\$29\.99/)).toBeInTheDocument();
      expect(screen.getByText(/110.*credits/i)).toBeInTheDocument();
    });

    it('PC-006: Receipt download button', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      const receiptBtn = screen.getByRole('button', { name: /receipt/i });
      expect(receiptBtn).toBeInTheDocument();
    });

    it('PC-007: Email confirmation message', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" emailSent={true} />);
      
      expect(screen.getByText(/email.*sent/i)).toBeInTheDocument();
    });

    it('PC-008: Back to dashboard button', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      const backBtn = screen.getByRole('button', { name: /dashboard/i });
      expect(backBtn).toBeInTheDocument();
    });

    it('PC-009: Retry button on error', () => {
      render(<PaymentConfirmation {...defaultProps} status="error" />);
      
      const retryBtn = screen.getByRole('button', { name: /try again/i });
      expect(retryBtn).toBeInTheDocument();
    });

    it('PC-010: Contact support link', () => {
      render(<PaymentConfirmation {...defaultProps} status="error" />);
      
      const supportLink = screen.getByRole('link', { name: /support/i });
      expect(supportLink).toBeInTheDocument();
    });
  });

  // ============================================
  // PC-SUCCESS: Success States
  // ============================================
  describe('Success States', () => {
    it('PC-011: Success message displayed', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      expect(screen.getByText(/payment.*successful/i)).toBeInTheDocument();
    });

    it('PC-012: Credits added message', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      expect(screen.getByText(/\+110.*credits.*added/i)).toBeInTheDocument();
    });

    it('PC-013: New balance shown', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" newBalance={150} />);
      
      expect(screen.getByText(/balance.*150.*credits/i)).toBeInTheDocument();
    });

    it('PC-014: Transaction ID displayed', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      expect(screen.getByText(/txn_123456/i)).toBeInTheDocument();
    });

    it('PC-015: Amount confirmation', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      expect(screen.getByText(/\$29\.99.*charged/i)).toBeInTheDocument();
    });

    it('PC-016: Payment method shown', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      expect(screen.getByText(/visa.*\*\*\*\*4242/i)).toBeInTheDocument();
    });

    it('PC-017: Timestamp formatted', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      // Mar 14, 2026 format
      expect(screen.getByText(/mar.*14.*2026/i)).toBeInTheDocument();
    });

    it('PC-018: Confetti animation on success', () => {
      const confetti = require('canvas-confetti').default;
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      expect(confetti).toHaveBeenCalled();
    });

    it('PC-019: Share button optional', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" showShare={true} />);
      
      expect(screen.getByRole('button', { name: /share/i })).toBeInTheDocument();
    });

    it('PC-020: View credits link', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      const creditsLink = screen.getByRole('link', { name: /view.*credits/i });
      expect(creditsLink).toBeInTheDocument();
      expect(creditsLink).toHaveAttribute('href', '/credits');
    });
  });

  // ============================================
  // PC-ERROR: Error States
  // ============================================
  describe('Error States', () => {
    it('PC-021: Card declined error', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="card_declined" 
      />);
      
      expect(screen.getByText(/card.*declined/i)).toBeInTheDocument();
    });

    it('PC-022: Insufficient funds error', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="insufficient_funds" 
      />);
      
      expect(screen.getByText(/insufficient.*funds/i)).toBeInTheDocument();
    });

    it('PC-023: Expired card error', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="expired_card" 
      />);
      
      expect(screen.getByText(/card.*expired/i)).toBeInTheDocument();
    });

    it('PC-024: Invalid CVC error', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="incorrect_cvc" 
      />);
      
      expect(screen.getByText(/invalid.*security.*code/i)).toBeInTheDocument();
    });

    it('PC-025: Processing error', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="processing_error" 
      />);
      
      expect(screen.getByText(/processing.*error/i)).toBeInTheDocument();
    });

    it('PC-026: Network error', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="network" 
      />);
      
      expect(screen.getByText(/network.*error/i)).toBeInTheDocument();
    });

    it('PC-027: Rate limit error', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="rate_limit" 
      />);
      
      expect(screen.getByText(/too.*many.*attempts/i)).toBeInTheDocument();
    });

    it('PC-028: Payment timeout', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="timeout" 
      />);
      
      expect(screen.getByText(/timed.*out/i)).toBeInTheDocument();
    });

    it('PC-029: Duplicate payment', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="duplicate" 
      />);
      
      expect(screen.getByText(/already.*processed/i)).toBeInTheDocument();
    });

    it('PC-030: Fraud detection', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="fraud" 
      />);
      
      expect(screen.getByText(/blocked/i)).toBeInTheDocument();
      expect(screen.getByText(/contact.*support/i)).toBeInTheDocument();
    });

    it('PC-031: Currency not supported', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="currency_not_supported" 
      />);
      
      expect(screen.getByText(/currency.*not.*supported/i)).toBeInTheDocument();
    });

    it('PC-032: Amount too large', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorCode="amount_too_large" 
      />);
      
      expect(screen.getByText(/exceeds.*limit/i)).toBeInTheDocument();
    });

    it('PC-033: Generic error fallback', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        error="Something went wrong"
      />);
      
      expect(screen.getByText(/something.*went.*wrong/i)).toBeInTheDocument();
    });

    it('PC-034: Error details toggle', async () => {
      const user = userEvent.setup();
      
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        error="Test error"
        errorDetails="Detailed error message"
      />);
      
      const toggleBtn = screen.getByRole('button', { name: /details/i });
      await user.click(toggleBtn);
      
      expect(screen.getByText(/detailed error message/i)).toBeInTheDocument();
    });

    it('PC-035: Error reference code', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        errorReference="ERR-ABC123"
      />);
      
      expect(screen.getByText(/ERR-ABC123/i)).toBeInTheDocument();
    });
  });

  // ============================================
  // PC-RETRY: Retry Mechanism
  // ============================================
  describe('Retry Mechanism', () => {
    it('PC-036: Retry button visible on error', () => {
      render(<PaymentConfirmation {...defaultProps} status="error" />);
      
      const retryBtn = screen.getByRole('button', { name: /try again/i });
      expect(retryBtn).toBeVisible();
    });

    it('PC-037: Retry click redirects', async () => {
      const user = userEvent.setup();
      const onRetry = jest.fn();
      
      render(<PaymentConfirmation {...defaultProps} status="error" onRetry={onRetry} />);
      
      await user.click(screen.getByRole('button', { name: /try again/i }));
      
      expect(onRetry).toHaveBeenCalled();
    });

    it('PC-038: Retry with new card option', () => {
      render(<PaymentConfirmation {...defaultProps} status="error" allowNewCard={true} />);
      
      expect(screen.getByRole('button', { name: /different.*card/i })).toBeInTheDocument();
    });

    it('PC-039: Retry count limit', async () => {
      const user = userEvent.setup();
      const onRetry = jest.fn();
      
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        onRetry={onRetry}
        retryCount={3}
        maxRetries={3}
      />);
      
      // Should show message instead of retry button
      expect(screen.getByText(/too.*many.*attempts/i)).toBeInTheDocument();
      expect(screen.queryByRole('button', { name: /try again/i })).not.toBeInTheDocument();
    });

    it('PC-040: Retry after delay with countdown', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        retryAfter={60}
      />);
      
      expect(screen.getByText(/wait.*60.*seconds/i)).toBeInTheDocument();
    });

    it('PC-041: Retry preserves package data', async () => {
      const user = userEvent.setup();
      const onRetry = jest.fn();
      
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        onRetry={onRetry}
        packageId="pkg_pro"
      />);
      
      await user.click(screen.getByRole('button', { name: /try again/i }));
      
      expect(onRetry).toHaveBeenCalledWith({ packageId: 'pkg_pro' });
    });

    it('PC-042: Retry with different saved card', () => {
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        savedCards={[
          { id: 'pm_1', last4: '4242' },
          { id: 'pm_2', last4: '5555' },
        ]}
      />);
      
      expect(screen.getByRole('combobox', { name: /select.*card/i })).toBeInTheDocument();
    });

    it('PC-043: Cancel retry goes to dashboard', async () => {
      const user = userEvent.setup();
      const onBackToDashboard = jest.fn();
      
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        onBackToDashboard={onBackToDashboard}
      />);
      
      await user.click(screen.getByRole('button', { name: /cancel/i }));
      
      expect(onBackToDashboard).toHaveBeenCalled();
    });

    it('PC-044: Retry analytics tracked', async () => {
      const user = userEvent.setup();
      const trackEvent = jest.fn();
      
      render(<PaymentConfirmation 
        {...defaultProps} 
        status="error" 
        onRetry={() => {}}
        trackEvent={trackEvent}
      />);
      
      await user.click(screen.getByRole('button', { name: /try again/i }));
      
      expect(trackEvent).toHaveBeenCalledWith('payment_retry', expect.any(Object));
    });

    it('PC-045: Retry success shows normal success', async () => {
      const { rerender } = render(<PaymentConfirmation {...defaultProps} status="error" />);
      
      expect(screen.getByTestId('x-circle-icon')).toBeInTheDocument();
      
      // Simulate successful retry
      rerender(<PaymentConfirmation {...defaultProps} status="success" />);
      
      expect(screen.getByTestId('check-circle-icon')).toBeInTheDocument();
    });
  });

  // ============================================
  // PC-ACCESSIBILITY: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('PC-046: Success announced politely', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      const announcement = screen.getByRole('status');
      expect(announcement).toHaveAttribute('aria-live', 'polite');
    });

    it('PC-047: Error announced assertively', () => {
      render(<PaymentConfirmation {...defaultProps} status="error" error="Test error" />);
      
      const announcement = screen.getByRole('alert');
      expect(announcement).toHaveAttribute('aria-live', 'assertive');
    });

    it('PC-048: Focus on success message', async () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      const successMessage = screen.getByRole('status');
      await waitFor(() => {
        expect(successMessage).toHaveFocus();
      });
    });

    it('PC-049: Focus on error message', async () => {
      render(<PaymentConfirmation {...defaultProps} status="error" error="Test error" />);
      
      const errorMessage = screen.getByRole('alert');
      await waitFor(() => {
        expect(errorMessage).toHaveFocus();
      });
    });

    it('PC-050: Keyboard navigation', async () => {
      const user = userEvent.setup();
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      await user.tab();
      expect(screen.getByRole('button', { name: /receipt/i })).toHaveFocus();
      
      await user.tab();
      expect(screen.getByRole('button', { name: /dashboard/i })).toHaveFocus();
    });

    it('PC-051: Receipt link accessible', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      const receiptBtn = screen.getByRole('button', { name: /receipt/i });
      expect(receiptBtn).toHaveAttribute('aria-label', expect.stringContaining('Download receipt'));
    });

    it('PC-052: Retry button label clear', () => {
      render(<PaymentConfirmation {...defaultProps} status="error" />);
      
      const retryBtn = screen.getByRole('button', { name: /try again/i });
      expect(retryBtn).toHaveAccessibleName();
    });

    it('PC-053: Status icon has meaning', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      const icon = screen.getByTestId('check-circle-icon');
      expect(icon).toHaveAttribute('aria-label', 'Success');
    });

    it('PC-054: Transaction info structured', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      const transactionSection = screen.getByRole('region', { name: /transaction/i });
      expect(transactionSection).toBeInTheDocument();
    });

    it('PC-055: Screen reader text complete', () => {
      render(<PaymentConfirmation {...defaultProps} status="success" />);
      
      // All important info should be in accessible text
      expect(screen.getByText(/110.*credits/i)).toBeInTheDocument();
      expect(screen.getByText(/\$29\.99/i)).toBeInTheDocument();
    });
  });
});

// ============================================
// Mock Component for TDD Reference
// ============================================
export function MockPaymentConfirmation({
  status,
  transaction,
  error,
  errorCode,
  errorDetails,
  errorReference,
  onRetry,
  onBackToDashboard,
  emailSent = false,
  newBalance,
  showShare = false,
  allowNewCard = false,
  retryCount = 0,
  maxRetries = 3,
  retryAfter,
  packageId,
  savedCards,
  trackEvent,
}: PaymentConfirmationProps) {
  const [showDetails, setShowDetails] = React.useState(false);
  const [countdown, setCountdown] = React.useState(retryAfter || 0);
  
  React.useEffect(() => {
    if (retryAfter && countdown > 0) {
      const timer = setTimeout(() => setCountdown(c => c - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [retryAfter, countdown]);
  
  React.useEffect(() => {
    if (status === 'success') {
      // Trigger confetti
      require('canvas-confetti').default();
    }
  }, [status]);
  
  const errorMessage = React.useMemo(() => {
    const messages: Record<string, string> = {
      card_declined: 'Your card was declined.',
      insufficient_funds: 'Insufficient funds in your account.',
      expired_card: 'Your card has expired.',
      incorrect_cvc: 'Invalid security code.',
      processing_error: 'Processing error. Please try again.',
      network: 'Network error. Please check your connection.',
      rate_limit: 'Too many attempts. Please wait.',
      timeout: 'Payment timed out.',
      duplicate: 'Payment already processed.',
      fraud: 'Payment blocked. Please contact support.',
      currency_not_supported: 'Currency not supported.',
      amount_too_large: 'Amount exceeds limit.',
    };
    return messages[errorCode || ''] || error || 'Something went wrong.';
  }, [errorCode, error]);
  
  const handleRetry = () => {
    trackEvent?.('payment_retry', { packageId });
    onRetry?.({ packageId });
  };
  
  const canRetry = retryCount < maxRetries && countdown === 0;
  
  return (
    <div data-testid="confirmation-container">
      {status === 'success' && (
        <>
          <div data-testid="check-circle-icon" aria-label="Success">CheckCircle</div>
          <div role="status" aria-live="polite" tabIndex={-1}>
            <h2>Payment successful!</h2>
            <p>+{transaction?.credits} credits added</p>
            {newBalance && <p>Balance: {newBalance} credits</p>}
          </div>
          
          <section role="region" aria-label="Transaction details">
            <p>Transaction ID: {transaction?.id}</p>
            <p>${transaction?.amount} charged</p>
            <p>Paid with {transaction?.payment_method}</p>
            <p>{new Date(transaction?.created_at || '').toLocaleDateString('en-US', { 
              month: 'short', day: 'numeric', year: 'numeric' 
            })}</p>
          </section>
          
          {emailSent && <p><span data-testid="mail-icon" /> Confirmation email sent</p>}
          
          <button aria-label="Download receipt for transaction {transaction?.id}">
            <span data-testid="download-icon" /> Receipt
          </button>
          
          {showShare && <button>Share</button>}
          
          <a href="/credits">View credits</a>
          
          <button onClick={onBackToDashboard}>
            <span data-testid="arrow-left-icon" /> Back to Dashboard
          </button>
        </>
      )}
      
      {status === 'error' && (
        <>
          <div data-testid="x-circle-icon" aria-label="Error">XCircle</div>
          <div role="alert" aria-live="assertive" tabIndex={-1}>
            <h2>{errorMessage}</h2>
          </div>
          
          {errorDetails && (
            <button onClick={() => setShowDetails(!showDetails)}>
              {showDetails ? 'Hide' : 'Show'} details
            </button>
          )}
          {showDetails && <p>{errorDetails}</p>}
          
          {errorReference && <p>Reference: {errorReference}</p>}
          
          {canRetry ? (
            <>
              <button onClick={handleRetry}>
                <span data-testid="refresh-icon" /> Try again
              </button>
              {allowNewCard && <button>Use different card</button>}
              {savedCards && savedCards.length > 0 && (
                <select aria-label="Select card">
                  {savedCards.map(card => (
                    <option key={card.id} value={card.id}>****{card.last4}</option>
                  ))}
                </select>
              )}
            </>
          ) : (
            retryCount >= maxRetries && <p>Too many attempts. Please contact support.</p>
          )}
          
          {countdown > 0 && <p>Wait {countdown} seconds</p>}
          
          <button onClick={onBackToDashboard}>Cancel</button>
          
          <a href="/support">Contact support</a>
        </>
      )}
      
      {status === 'pending' && (
        <>
          <div data-testid="loader-icon" className="animate-spin">Loader2</div>
          <p>Processing your payment...</p>
        </>
      )}
      
      {status === 'cancelled' && (
        <>
          <p>Payment cancelled</p>
          <button onClick={onBackToDashboard}>Back to Dashboard</button>
        </>
      )}
    </div>
  );
}

interface PaymentConfirmationProps {
  status: 'success' | 'error' | 'pending' | 'cancelled';
  transaction?: {
    id: string;
    amount: number;
    currency: string;
    credits: number;
    status: string;
    payment_method: string;
    created_at: string;
    receipt_url?: string;
  };
  error?: string;
  errorCode?: string;
  errorDetails?: string;
  errorReference?: string;
  onRetry?: (data?: any) => void;
  onBackToDashboard?: () => void;
  emailSent?: boolean;
  newBalance?: number;
  showShare?: boolean;
  allowNewCard?: boolean;
  retryCount?: number;
  maxRetries?: number;
  retryAfter?: number;
  packageId?: string;
  savedCards?: Array<{ id: string; last4: string }>;
  trackEvent?: (event: string, data: any) => void;
}
