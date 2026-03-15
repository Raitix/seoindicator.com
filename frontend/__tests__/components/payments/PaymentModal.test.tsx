/**
 * PaymentModal Component Tests
 * TASK-26-23: Stripe Payment Flow
 * 
 * Tests for the main payment modal wrapper component that contains
 * the Stripe checkout flow and handles modal state management.
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { vi } from 'vitest';
import userEvent from '@testing-library/user-event';
import { PaymentModal } from '@/components/payments/PaymentModal';

// Mock credit package data
const mockPackage = {
  id: 2,
  name: 'Growth',
  credits: 150,
  price: 25.00,
  bonus_percent: 10,
  is_popular: true
};

describe('PaymentModal', () => {
  const mockOnClose = vi.fn();
  const mockOnSuccess = vi.fn();

  const defaultProps = {
    isOpen: true,
    onClose: mockOnClose,
    onSuccess: mockOnSuccess,
    creditPackage: mockPackage
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('PM-001: Rendering', () => {
    it('renders modal when isOpen is true', () => {
      render(<PaymentModal {...defaultProps} />);
      expect(screen.getByRole('dialog')).toBeInTheDocument();
      expect(screen.getByText('Complete Purchase')).toBeInTheDocument();
    });

    it('does not render modal when isOpen is false', () => {
      render(<PaymentModal {...defaultProps} isOpen={false} />);
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('shows backdrop overlay when open', () => {
      render(<PaymentModal {...defaultProps} />);
      const backdrop = screen.getByTestId('modal-backdrop');
      expect(backdrop).toBeInTheDocument();
      expect(backdrop).toHaveClass('bg-black/50');
    });
  });

  describe('PM-002 to PM-006: Close Behavior', () => {
    it('calls onClose when backdrop is clicked', async () => {
      const user = userEvent.setup();
      render(<PaymentModal {...defaultProps} />);
      
      const backdrop = screen.getByTestId('modal-backdrop');
      await user.click(backdrop);
      
      expect(mockOnClose).toHaveBeenCalledTimes(1);
    });

    it('calls onClose when Escape key is pressed', () => {
      render(<PaymentModal {...defaultProps} />);
      
      fireEvent.keyDown(document, { key: 'Escape' });
      
      expect(mockOnClose).toHaveBeenCalledTimes(1);
    });

    it('does not call onClose when modal content is clicked', async () => {
      const user = userEvent.setup();
      render(<PaymentModal {...defaultProps} />);
      
      const modalContent = screen.getByRole('dialog');
      await user.click(modalContent);
      
      expect(mockOnClose).not.toHaveBeenCalled();
    });

    it('does not call onClose when Escape is pressed during processing', () => {
      render(<PaymentModal {...defaultProps} isProcessing={true} />);
      
      fireEvent.keyDown(document, { key: 'Escape' });
      
      expect(mockOnClose).not.toHaveBeenCalled();
    });
  });

  describe('PM-007 to PM-009: Package Info and States', () => {
    it('shows selected package info', () => {
      render(<PaymentModal {...defaultProps} />);
      
      expect(screen.getByText('Growth')).toBeInTheDocument();
      expect(screen.getByText('150 credits')).toBeInTheDocument();
      expect(screen.getByText('$25.00')).toBeInTheDocument();
    });

    it('shows bonus credits when applicable', () => {
      render(<PaymentModal {...defaultProps} />);
      
      // 10% bonus on 150 = 15 extra
      expect(screen.getByText(/\+15 bonus/i)).toBeInTheDocument();
    });

    it('shows loading state with spinner', () => {
      render(<PaymentModal {...defaultProps} isLoading={true} />);
      
      expect(screen.getByTestId('loading-spinner')).toBeInTheDocument();
    });

    it('disables close button during processing', () => {
      render(<PaymentModal {...defaultProps} isProcessing={true} />);
      
      const closeButton = screen.getByRole('button', { name: /close/i });
      expect(closeButton).toBeDisabled();
    });
  });

  describe('PM-010 to PM-012: Accessibility', () => {
    it('traps focus within modal', async () => {
      const user = userEvent.setup();
      render(<PaymentModal {...defaultProps} />);
      
      const modal = screen.getByRole('dialog');
      const closeButton = screen.getByRole('button', { name: /close/i });
      
      closeButton.focus();
      
      // Tab through elements
      await user.tab();
      await user.tab();
      await user.tab();
      
      // Focus should stay within modal
      expect(document.activeElement).not.toEqual(document.body);
    });

    it('has correct ARIA attributes', () => {
      render(<PaymentModal {...defaultProps} />);
      
      const modal = screen.getByRole('dialog');
      expect(modal).toHaveAttribute('aria-modal', 'true');
      expect(modal).toHaveAttribute('aria-labelledby');
    });

    it('announces title to screen readers', () => {
      render(<PaymentModal {...defaultProps} />);
      
      const title = screen.getByRole('heading', { name: /complete purchase/i });
      expect(title).toHaveAttribute('id');
    });
  });

  describe('PM-013 to PM-015: Progress Steps', () => {
    it('shows progress step indicator', () => {
      render(<PaymentModal {...defaultProps} />);
      
      expect(screen.getByText('Package')).toBeInTheDocument();
      expect(screen.getByText('Payment')).toBeInTheDocument();
      expect(screen.getByText('Confirm')).toBeInTheDocument();
    });

    it('highlights current step', () => {
      render(<PaymentModal {...defaultProps} currentStep={1} />);
      
      const steps = screen.getAllByTestId(/step-/);
      // Step 0 is completed (has green classes)
      expect(steps[0]).toHaveClass('bg-green-500/20');
      expect(steps[0]).toHaveClass('text-green-400');
      // Step 1 is active (has blue classes)
      expect(steps[1]).toHaveClass('bg-blue-500/20');
      expect(steps[1]).toHaveClass('text-blue-400');
      // Step 2 is not active (has slate classes)
      expect(steps[2]).toHaveClass('bg-slate-800');
    });

    it('transitions between steps', async () => {
      const { rerender } = render(<PaymentModal {...defaultProps} currentStep={0} />);
      
      // Step 0 is active at start
      expect(screen.getByTestId('step-0')).toHaveClass('bg-blue-500/20');
      
      rerender(<PaymentModal {...defaultProps} currentStep={1} />);
      
      // Step 1 is now active
      expect(screen.getByTestId('step-1')).toHaveClass('bg-blue-500/20');
    });
  });
});

