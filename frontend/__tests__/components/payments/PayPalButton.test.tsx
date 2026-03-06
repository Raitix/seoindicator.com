/**
 * PayPalButton Component Tests
 * TASK-26-24: PayPal Payment Flow
 */

import React from 'react';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { PayPalButton } from '@/components/payments/PayPalButton';
import * as paypalApi from '@/lib/api/paypal';

// Mock PayPal SDK
const mockButtonsRender = jest.fn().mockResolvedValue(undefined);
const mockButtonsClose = jest.fn();
const mockButtons = jest.fn(() => ({
  render: mockButtonsRender,
  close: mockButtonsClose,
}));

// Mock window.paypal
Object.defineProperty(window, 'paypal', {
  value: { Buttons: mockButtons },
  writable: true,
});

// Mock API functions
jest.mock('@/lib/api/paypal', () => ({
  ...jest.requireActual('@/lib/api/paypal'),
  createPayPalOrder: jest.fn(),
  capturePayPalOrder: jest.fn(),
  loadPayPalSDK: jest.fn().mockResolvedValue(undefined),
  getPayPalClientId: jest.fn().mockReturnValue('test_client_id'),
  isPayPalSDKLoaded: jest.fn().mockReturnValue(true),
  getReturnUrl: jest.fn().mockReturnValue('https://example.com/paypal/success'),
  getCancelUrl: jest.fn().mockReturnValue('https://example.com/paypal/cancel'),
  storeOrderInfo: jest.fn(),
  clearStoredOrderInfo: jest.fn(),
  getPayPalErrorMessage: jest.fn((err) => err.message || 'Error'),
}));

// Mock useBalance
jest.mock('@/lib/api/credits', () => ({
  useBalance: () => ({ mutate: jest.fn() }),
}));

const mockPackage = {
  id: 1,
  name: 'Starter',
  credits: 50,
  price: 10.00,
  bonus_percent: 0,
  is_popular: false,
};

const mockCaptureData = {
  capture_id: '7G894532KG123456A',
  credits: 50,
  new_balance: 125.50,
  transaction_id: 'txn_abc123',
  order_id: '5O190127TN364715T',
};

describe('PayPalButton', () => {
  const mockOnSuccess = jest.fn();
  const mockOnCancel = jest.fn();
  const mockOnError = jest.fn();
  const mockOnRedirect = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    mockButtonsRender.mockClear();
    mockButtonsClose.mockClear();
    mockButtons.mockClear();
    (paypalApi.createPayPalOrder as jest.Mock).mockClear();
    (paypalApi.capturePayPalOrder as jest.Mock).mockClear();
    (paypalApi.loadPayPalSDK as jest.Mock).mockClear();
    (paypalApi.isPayPalSDKLoaded as jest.Mock).mockReturnValue(true);
    (paypalApi.getPayPalClientId as jest.Mock).mockReturnValue('test_client_id');
  });

  describe('PB-001: Rendering', () => {
    it('renders PayPal button container', async () => {
      render(
        <PayPalButton package={mockPackage} />
      );

      await waitFor(() => {
        expect(screen.getByTestId('paypal-button-container')).toBeInTheDocument();
      });
    });

    it('shows loading skeleton while SDK loads', () => {
      (paypalApi.isPayPalSDKLoaded as jest.Mock).mockReturnValue(false);
      (paypalApi.getPayPalClientId as jest.Mock).mockReturnValue('test_client_id');

      render(
        <PayPalButton package={mockPackage} />
      );

      expect(screen.getByTestId('paypal-loading')).toBeInTheDocument();
    });

    it('renders buttons after SDK loads', async () => {
      render(
        <PayPalButton package={mockPackage} />
      );

      await waitFor(() => {
        expect(mockButtons).toHaveBeenCalled();
        expect(mockButtonsRender).toHaveBeenCalledWith('#paypal-button-container');
      });
    });
  });

  describe('PB-002: SDK Error Handling', () => {
    it('shows error when client ID not configured', async () => {
      (paypalApi.getPayPalClientId as jest.Mock).mockReturnValue(null);

      render(
        <PayPalButton package={mockPackage} />
      );

      await waitFor(() => {
        expect(screen.getByTestId('paypal-sdk-error')).toBeInTheDocument();
        expect(screen.getByText(/client ID not configured/i)).toBeInTheDocument();
      });
    });

    it('shows error when SDK fails to load', async () => {
      (paypalApi.loadPayPalSDK as jest.Mock).mockRejectedValue(new Error('SDK load failed'));
      (paypalApi.isPayPalSDKLoaded as jest.Mock).mockReturnValue(false);

      render(
        <PayPalButton package={mockPackage} />
      );

      await waitFor(() => {
        expect(screen.getByTestId('paypal-sdk-error')).toBeInTheDocument();
      });
    });
  });

  describe('PB-003: Order Creation', () => {
    it('creates order on button click', async () => {
      (paypalApi.createPayPalOrder as jest.Mock).mockResolvedValue({
        order_id: 'test_order_id',
        approval_url: 'https://paypal.com/approve',
        amount: 10.00,
        credits: 50,
        package_id: 1,
        package_name: 'Starter',
      });

      render(
        <PayPalButton package={mockPackage} />
      );

      await waitFor(() => {
        expect(mockButtons).toHaveBeenCalled();
      });

      // Get the createOrder callback
      const createOrderCallback = mockButtons.mock.calls[0][0].createOrder;

      await createOrderCallback();

      expect(paypalApi.createPayPalOrder).toHaveBeenCalledWith(1, undefined, undefined);
    });

    it('shows error on order creation failure', async () => {
      const error = new Error('Order creation failed');
      (paypalApi.createPayPalOrder as jest.Mock).mockRejectedValue(error);

      render(
        <PayPalButton package={mockPackage} onError={mockOnError} />
      );

      await waitFor(() => {
        expect(mockButtons).toHaveBeenCalled();
      });

      const createOrderCallback = mockButtons.mock.calls[0][0].createOrder;

      await expect(createOrderCallback()).rejects.toThrow();
      expect(mockOnError).toHaveBeenCalled();
    });
  });

  describe('PB-004: Order Capture', () => {
    it('captures order on approval', async () => {
      (paypalApi.createPayPalOrder as jest.Mock).mockResolvedValue({
        order_id: 'test_order_id',
        approval_url: 'https://paypal.com/approve',
        amount: 10.00,
        credits: 50,
        package_id: 1,
        package_name: 'Starter',
      });
      (paypalApi.capturePayPalOrder as jest.Mock).mockResolvedValue(mockCaptureData);

      render(
        <PayPalButton package={mockPackage} onSuccess={mockOnSuccess} />
      );

      await waitFor(() => {
        expect(mockButtons).toHaveBeenCalled();
      });

      const onApproveCallback = mockButtons.mock.calls[0][0].onApprove;
      await onApproveCallback({ orderID: 'test_order_id' });

      expect(paypalApi.capturePayPalOrder).toHaveBeenCalledWith('test_order_id');
      expect(mockOnSuccess).toHaveBeenCalledWith({
        captureId: mockCaptureData.capture_id,
        credits: mockCaptureData.credits,
        newBalance: mockCaptureData.new_balance,
        transactionId: mockCaptureData.transaction_id,
        orderId: mockCaptureData.order_id,
      });
    });

    it('shows error on capture failure', async () => {
      (paypalApi.createPayPalOrder as jest.Mock).mockResolvedValue({
        order_id: 'test_order_id',
        approval_url: 'https://paypal.com/approve',
        amount: 10.00,
        credits: 50,
        package_id: 1,
        package_name: 'Starter',
      });
      const error = new Error('Capture failed');
      (paypalApi.capturePayPalOrder as jest.Mock).mockRejectedValue(error);

      render(
        <PayPalButton package={mockPackage} onError={mockOnError} />
      );

      await waitFor(() => {
        expect(mockButtons).toHaveBeenCalled();
      });

      const onApproveCallback = mockButtons.mock.calls[0][0].onApprove;
      await onApproveCallback({ orderID: 'test_order_id' });

      expect(mockOnError).toHaveBeenCalled();
    });
  });

  describe('PB-005: Cancellation', () => {
    it('calls onCancel when user cancels', async () => {
      render(
        <PayPalButton package={mockPackage} onCancel={mockOnCancel} />
      );

      await waitFor(() => {
        expect(mockButtons).toHaveBeenCalled();
      });

      const onCancelCallback = mockButtons.mock.calls[0][0].onCancel;
      onCancelCallback();

      expect(mockOnCancel).toHaveBeenCalled();
      expect(paypalApi.clearStoredOrderInfo).toHaveBeenCalled();
    });
  });

  describe('PB-006: Error Callback', () => {
    it('calls onError when SDK error occurs', async () => {
      render(
        <PayPalButton package={mockPackage} onError={mockOnError} />
      );

      await waitFor(() => {
        expect(mockButtons).toHaveBeenCalled();
      });

      const onErrorCallback = mockButtons.mock.calls[0][0].onError;
      onErrorCallback(new Error('SDK error'));

      expect(mockOnError).toHaveBeenCalled();
      expect(paypalApi.clearStoredOrderInfo).toHaveBeenCalled();
    });
  });

  describe('PB-007: Disabled State', () => {
    it('does not render buttons when disabled', async () => {
      render(
        <PayPalButton package={mockPackage} disabled={true} />
      );

      // Should not call Buttons when disabled
      expect(mockButtons).not.toHaveBeenCalled();
    });
  });

  describe('PB-008: Redirect Flow', () => {
    it('stores order info and redirects for redirect flow', async () => {
      const approvalUrl = 'https://paypal.com/approve?token=xyz';
      (paypalApi.createPayPalOrder as jest.Mock).mockResolvedValue({
        order_id: 'test_order_id',
        approval_url: approvalUrl,
        amount: 10.00,
        credits: 50,
        package_id: 1,
        package_name: 'Starter',
      });

      render(
        <PayPalButton
          package={mockPackage}
          flow="redirect"
          onRedirect={mockOnRedirect}
        />
      );

      await waitFor(() => {
        expect(mockButtons).toHaveBeenCalled();
      });

      const createOrderCallback = mockButtons.mock.calls[0][0].createOrder;
      await createOrderCallback();

      expect(paypalApi.storeOrderInfo).toHaveBeenCalledWith('test_order_id', 1);
      expect(mockOnRedirect).toHaveBeenCalledWith(approvalUrl);
    });
  });

  describe('PB-009: Processing State', () => {
    it('shows processing indicator during payment', async () => {
      // Make capture hang
      (paypalApi.capturePayPalOrder as jest.Mock).mockImplementation(
        () => new Promise((resolve) => setTimeout(() => resolve(mockCaptureData), 100))
      );
      (paypalApi.createPayPalOrder as jest.Mock).mockResolvedValue({
        order_id: 'test_order_id',
        approval_url: 'https://paypal.com/approve',
        amount: 10.00,
        credits: 50,
        package_id: 1,
        package_name: 'Starter',
      });

      render(
        <PayPalButton package={mockPackage} />
      );

      await waitFor(() => {
        expect(mockButtons).toHaveBeenCalled();
      });

      const onApproveCallback = mockButtons.mock.calls[0][0].onApprove;
      const capturePromise = onApproveCallback({ orderID: 'test_order_id' });

      // Processing should be shown
      await waitFor(() => {
        expect(screen.getByTestId('paypal-processing')).toBeInTheDocument();
      });

      await capturePromise;
    });
  });

  describe('PB-010: Error Display', () => {
    it('shows error message with retry option', async () => {
      const error = new Error('Payment declined');
      (paypalApi.getPayPalErrorMessage as jest.Mock).mockReturnValue('Payment declined');
      (paypalApi.createPayPalOrder as jest.Mock).mockRejectedValue(error);

      render(
        <PayPalButton package={mockPackage} onError={mockOnError} />
      );

      await waitFor(() => {
        expect(mockButtons).toHaveBeenCalled();
      });

      const createOrderCallback = mockButtons.mock.calls[0][0].createOrder;
      try {
        await createOrderCallback();
      } catch {
        // Expected to throw
      }

      await waitFor(() => {
        expect(screen.getByTestId('paypal-error')).toBeInTheDocument();
        expect(screen.getByText(/Payment declined/i)).toBeInTheDocument();
      });
    });
  });
});
