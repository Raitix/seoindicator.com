/**
 * PaymentSuccess Component Tests
 * TASK-26-23: Stripe Payment Flow
 * 
 * Tests for the payment success confirmation component.
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { PaymentSuccess } from '@/components/payments/PaymentSuccess';

describe('PaymentSuccess', () => {
  const mockOnDone = jest.fn();
  
  const defaultProps = {
    credits: 150,
    newBalance: 175.50,
    transactionId: 'txn_abc123',
    onDone: mockOnDone
  };

  beforeEach(() => {
    jest.clearAllMocks();
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  describe('PS-001 to PS-006: Content Display', () => {
    it('renders success message', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      expect(screen.getByRole('heading', { name: /payment successful/i })).toBeInTheDocument();
    });

    it('shows credits added', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      expect(screen.getByText(/150 credits added/i)).toBeInTheDocument();
    });

    it('shows new balance', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      expect(screen.getByText(/\$175\.50/)).toBeInTheDocument();
      expect(screen.getByText(/new balance/i)).toBeInTheDocument();
    });

    it('shows checkmark animation', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      expect(screen.getByTestId('checkmark-icon')).toBeInTheDocument();
    });

    it('shows transaction ID', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      expect(screen.getByText('txn_abc123')).toBeInTheDocument();
    });

    it('shows email receipt note', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      expect(screen.getByText(/receipt sent to your email/i)).toBeInTheDocument();
    });
  });

  describe('PS-007 to PS-011: Interactions', () => {
    it('shows done button', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      expect(screen.getByRole('button', { name: /done/i })).toBeInTheDocument();
    });

    it('calls onDone when done button clicked', async () => {
      const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });
      render(<PaymentSuccess {...defaultProps} />);
      
      await user.click(screen.getByRole('button', { name: /done/i }));
      
      expect(mockOnDone).toHaveBeenCalledTimes(1);
    });

    it('shows view history link', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      expect(screen.getByRole('link', { name: /transaction history/i })).toBeInTheDocument();
    });

    it('auto-closes after delay', () => {
      render(<PaymentSuccess {...defaultProps} autoCloseDelay={5000} />);
      
      expect(mockOnDone).not.toHaveBeenCalled();
      
      jest.advanceTimersByTime(5000);
      
      expect(mockOnDone).toHaveBeenCalledTimes(1);
    });

    it('does not auto-close during hover', () => {
      render(<PaymentSuccess {...defaultProps} autoCloseDelay={5000} />);
      
      const container = screen.getByTestId('success-container');
      fireEvent.mouseEnter(container);
      
      jest.advanceTimersByTime(5000);
      
      expect(mockOnDone).not.toHaveBeenCalled();
    });
  });

  describe('PS-012: Animations', () => {
    it('shows confetti animation', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      expect(screen.getByTestId('confetti')).toBeInTheDocument();
    });

    it('checkmark animates in', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      const checkmark = screen.getByTestId('checkmark-icon');
      expect(checkmark).toHaveClass('animate-scale-in');
    });
  });

  describe('Accessibility', () => {
    it('has correct ARIA role', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    it('announces success to screen readers', () => {
      render(<PaymentSuccess {...defaultProps} />);
      
      const alert = screen.getByRole('alert');
      expect(alert).toHaveTextContent(/payment successful/i);
    });
  });
});
