/**
 * PaymentError Component Tests
 * TASK-26-23: Stripe Payment Flow
 * 
 * Tests for payment error handling and user feedback.
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import { vi } from 'vitest';
import userEvent from '@testing-library/user-event';
import { PaymentError } from '@/components/payments/PaymentError';

describe('PaymentError', () => {
  const mockOnRetry = vi.fn();
  const mockOnCancel = vi.fn();

  const defaultProps = {
    errorType: 'unknown',
    onRetry: mockOnRetry,
    onCancel: mockOnCancel
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('PE-001: Base Rendering', () => {
    it('renders error state', () => {
      render(<PaymentError {...defaultProps} />);
      
      expect(screen.getByRole('heading', { name: /payment failed/i })).toBeInTheDocument();
      expect(screen.getByTestId('error-icon')).toBeInTheDocument();
    });
  });

  describe('PE-002 to PE-006: Error Messages', () => {
    it('shows card declined message', () => {
      render(<PaymentError {...defaultProps} errorType="card_declined" />);
      
      expect(screen.getByText(/card was declined/i)).toBeInTheDocument();
    });

    it('shows insufficient funds message', () => {
      render(<PaymentError {...defaultProps} errorType="insufficient_funds" />);
      
      expect(screen.getByText(/insufficient funds/i)).toBeInTheDocument();
    });

    it('shows network error message', () => {
      render(<PaymentError {...defaultProps} errorType="network_error" />);
      
      expect(screen.getByText(/network error/i)).toBeInTheDocument();
    });

    it('shows timeout error message', () => {
      render(<PaymentError {...defaultProps} errorType="timeout" />);
      
      expect(screen.getByText(/timed out/i)).toBeInTheDocument();
    });

    it('shows generic error message for unknown', () => {
      render(<PaymentError {...defaultProps} errorType="unknown" />);
      
      expect(screen.getByText(/an error occurred/i)).toBeInTheDocument();
      expect(screen.getByText(/contact support/i)).toBeInTheDocument();
    });
  });

  describe('PE-007 to PE-011: Actions', () => {
    it('shows retry button', () => {
      render(<PaymentError {...defaultProps} />);
      
      expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
    });

    it('calls onRetry when retry clicked', async () => {
      const user = userEvent.setup();
      render(<PaymentError {...defaultProps} />);
      
      await user.click(screen.getByRole('button', { name: /try again/i }));
      
      expect(mockOnRetry).toHaveBeenCalledTimes(1);
    });

    it('shows contact support link', () => {
      render(<PaymentError {...defaultProps} />);
      
      expect(screen.getByRole('link', { name: /contact support/i })).toBeInTheDocument();
    });

    it('shows error code when available', () => {
      render(<PaymentError {...defaultProps} errorCode="err_abc123" />);
      
      expect(screen.getByText('err_abc123')).toBeInTheDocument();
    });

    it('hides sensitive card details from error', () => {
      render(
        <PaymentError 
          {...defaultProps} 
          errorMessage="Card 4242424242424242 declined"
        />
      );
      
      // Should not show full card number
      expect(screen.queryByText('4242424242424242')).not.toBeInTheDocument();
    });
  });

  describe('PE-012 to PE-014: Logging & Cancel', () => {
    it('logs error in development mode', () => {
      const consoleSpy = jest.spyOn(console, 'error').mockImplementation();
      
      render(
        <PaymentError 
          {...defaultProps} 
          errorDetails={{ type: 'test', message: 'test error' }}
        />
      );
      
      expect(consoleSpy).toHaveBeenCalled();
      
      consoleSpy.mockRestore();
    });

    it('shows cancel option', () => {
      render(<PaymentError {...defaultProps} />);
      
      expect(screen.getByRole('button', { name: /cancel/i })).toBeInTheDocument();
    });

    it('calls onCancel when cancel clicked', async () => {
      const user = userEvent.setup();
      render(<PaymentError {...defaultProps} />);
      
      await user.click(screen.getByRole('button', { name: /cancel/i }));
      
      expect(mockOnCancel).toHaveBeenCalledTimes(1);
    });
  });

  describe('Accessibility', () => {
    it('has alert role for screen readers', () => {
      render(<PaymentError {...defaultProps} />);
      
      expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    it('error icon has aria-label', () => {
      render(<PaymentError {...defaultProps} />);
      
      const icon = screen.getByTestId('error-icon');
      expect(icon).toHaveAttribute('aria-label', 'Error');
    });
  });
});

