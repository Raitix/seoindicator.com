/**
 * PayPal API Helper Tests
 * TASK-26-24: PayPal Payment Flow
 */

import {
  createPayPalOrder,
  capturePayPalOrder,
  getPayPalClientId,
  isPayPalSDKLoaded,
  loadPayPalSDK,
  getReturnUrl,
  getCancelUrl,
  parseReturnParams,
  storeOrderInfo,
  getStoredOrderInfo,
  clearStoredOrderInfo,
  getPayPalErrorMessage,
  PayPalError,
  PayPalErrorMessages,
} from '@/lib/api/paypal';

// Mock fetch
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Mock window.location and sessionStorage
const mockLocation = {
  origin: 'https://example.com',
  search: '',
  href: 'https://example.com',
  pathname: '/',
  hash: '',
  host: 'example.com',
  hostname: 'example.com',
  port: '',
  protocol: 'https:',
  assign: jest.fn(),
  reload: jest.fn(),
  replace: jest.fn(),
};

const mockSessionStorage = {
  store: {} as Record<string, string>,
  getItem: jest.fn((key: string) => mockSessionStorage.store[key] || null),
  setItem: jest.fn((key: string, value: string) => {
    mockSessionStorage.store[key] = value;
  }),
  removeItem: jest.fn((key: string) => {
    delete mockSessionStorage.store[key];
  }),
};

// Override window properties - use delete first to avoid "cannot redefine" error
try {
  // @ts-ignore - intentionally deleting to mock
  delete window.location;
  // @ts-ignore - intentionally assigning mock
  window.location = mockLocation;
} catch {
  // If delete fails, try configurable approach
  try {
    Object.defineProperty(window, 'location', {
      value: mockLocation,
      writable: true,
      configurable: true,
    });
  } catch {
    // Location mocking not possible in this environment
  }
}

// Mock sessionStorage
try {
  Object.defineProperty(window, 'sessionStorage', {
    value: mockSessionStorage,
    writable: true,
    configurable: true,
  });
} catch {
  // SessionStorage mocking not possible
}

// Mock process.env
process.env.NEXT_PUBLIC_PAYPAL_CLIENT_ID = 'test_client_id_123';

describe('PayPal API Helpers', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockClear();
    mockSessionStorage.store = {};
    mockLocation.search = '';
    // Reset PayPal SDK mock
    (window as any).paypal = undefined;
  });

  describe('API-001: createPayPalOrder', () => {
    it('creates order with valid package ID', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          data: {
            order_id: '5O190127TN364715T',
            approval_url: 'https://www.paypal.com/checkoutnow?token=5O190127TN364715T',
            amount: 10.00,
            credits: 50,
            package_id: 1,
            package_name: 'Starter',
          },
        }),
      });

      const result = await createPayPalOrder(1);

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/payments/paypal/order',
        expect.objectContaining({
          method: 'POST',
          credentials: 'include',
          body: JSON.stringify({ package_id: 1 }),
        })
      );

      expect(result.order_id).toBe('5O190127TN364715T');
      expect(result.amount).toBe(10.00);
      expect(result.credits).toBe(50);
    });

    it('includes return and cancel URLs for redirect flow', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          data: {
            order_id: 'test_order',
            approval_url: 'https://paypal.com/approve',
            amount: 10.00,
            credits: 50,
            package_id: 1,
            package_name: 'Starter',
          },
        }),
      });

      await createPayPalOrder(1, 'https://example.com/success', 'https://example.com/cancel');

      const callArgs = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(callArgs.return_url).toBe('https://example.com/success');
      expect(callArgs.cancel_url).toBe('https://example.com/cancel');
    });

    it('throws PayPalError on failure', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: false,
          error: {
            message: 'Invalid package',
            code: 'INVALID_PACKAGE',
          },
        }),
      });

      await expect(createPayPalOrder(999)).rejects.toThrow(PayPalError);
    });
  });

  describe('API-002: capturePayPalOrder', () => {
    it('captures order successfully', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          data: {
            capture_id: '7G894532KG123456A',
            credits: 50,
            new_balance: 125.50,
            transaction_id: 'txn_abc123',
            order_id: '5O190127TN364715T',
          },
        }),
      });

      const result = await capturePayPalOrder('5O190127TN364715T');

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/payments/paypal/capture',
        expect.objectContaining({
          method: 'POST',
          credentials: 'include',
          body: JSON.stringify({ order_id: '5O190127TN364715T' }),
        })
      );

      expect(result.capture_id).toBe('7G894532KG123456A');
      expect(result.credits).toBe(50);
      expect(result.new_balance).toBe(125.50);
    });

    it('throws PayPalError on capture failure', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: false,
          error: {
            message: 'Order already captured',
            code: 'ORDER_ALREADY_CAPTURED',
          },
        }),
      });

      await expect(capturePayPalOrder('test_order')).rejects.toThrow(PayPalError);
    });
  });

  describe('API-003: Error Handling', () => {
    it('handles 401 unauthorized', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
      });

      await expect(createPayPalOrder(1)).rejects.toThrow(/unauthorized/i);
    });

    it('handles 429 rate limit with retry info', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 429,
        headers: new Headers({ 'Retry-After': '60' }),
      });

      await expect(createPayPalOrder(1)).rejects.toThrow(/rate limit/i);
    });

    it('handles 500 server error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
      });

      await expect(createPayPalOrder(1)).rejects.toThrow(/server error/i);
    });

    it('handles 404 not found', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 404,
      });

      await expect(capturePayPalOrder('nonexistent')).rejects.toThrow(/not found/i);
    });
  });

  describe('SDK Helpers', () => {
    it('getPayPalClientId returns env variable', () => {
      const clientId = getPayPalClientId();
      expect(clientId).toBe('test_client_id_123');
    });

    it('isPayPalSDKLoaded returns false when not loaded', () => {
      expect(isPayPalSDKLoaded()).toBe(false);
    });

    it('isPayPalSDKLoaded returns true when loaded', () => {
      (window as any).paypal = { Buttons: jest.fn() };
      expect(isPayPalSDKLoaded()).toBe(true);
    });

    it('loadPayPalSDK resolves when already loaded', async () => {
      (window as any).paypal = { Buttons: jest.fn() };
      await expect(loadPayPalSDK('test_id')).resolves.toBeUndefined();
    });
  });

  describe('URL Helpers', () => {
    it('getReturnUrl returns correct URL', () => {
      const url = getReturnUrl();
      // jsdom uses http://localhost by default, so just check the path
      expect(url).toContain('/paypal/success');
    });

    it('getCancelUrl returns correct URL', () => {
      const url = getCancelUrl();
      // jsdom uses http://localhost by default, so just check the path
      expect(url).toContain('/paypal/cancel');
    });

    it('parseReturnParams extracts token and payerId', () => {
      mockLocation.search = '?token=abc123&PayerID=XYZ789';
      const { token, payerId } = parseReturnParams(mockLocation.search);
      expect(token).toBe('abc123');
      expect(payerId).toBe('XYZ789');
    });

    it('parseReturnParams returns null for missing params', () => {
      mockLocation.search = '';
      const { token, payerId } = parseReturnParams(mockLocation.search);
      expect(token).toBeNull();
      expect(payerId).toBeNull();
    });
  });

  describe('Session Storage', () => {
    it('storeOrderInfo stores order data', () => {
      storeOrderInfo('order123', 1);
      const stored = getStoredOrderInfo();
      expect(stored?.orderId).toBe('order123');
      expect(stored?.packageId).toBe(1);
      expect(stored?.createdAt).toBeDefined();
    });

    it('getStoredOrderInfo returns null when empty', () => {
      const stored = getStoredOrderInfo();
      expect(stored).toBeNull();
    });

    it('clearStoredOrderInfo removes data', () => {
      storeOrderInfo('order123', 1);
      clearStoredOrderInfo();
      const stored = getStoredOrderInfo();
      expect(stored).toBeNull();
    });
  });

  describe('Error Messages', () => {
    it('getPayPalErrorMessage returns mapped message for known code', () => {
      const error = new PayPalError('Test', 400, 'PAYMENT_DENIED');
      const message = getPayPalErrorMessage(error);
      expect(message).toBe(PayPalErrorMessages.PAYMENT_DENIED);
    });

    it('getPayPalErrorMessage returns original message for unknown code', () => {
      const error = new PayPalError('Custom error message', 400, 'UNKNOWN_CODE');
      const message = getPayPalErrorMessage(error);
      expect(message).toBe('Custom error message');
    });

    it('getPayPalErrorMessage handles generic Error', () => {
      const error = new Error('Generic error');
      const message = getPayPalErrorMessage(error);
      expect(message).toBe('Generic error');
    });
  });

  describe('PayPalError Class', () => {
    it('creates error with status and code', () => {
      const error = new PayPalError('Test error', 400, 'TEST_CODE');
      expect(error.message).toBe('Test error');
      expect(error.status).toBe(400);
      expect(error.code).toBe('TEST_CODE');
      expect(error.name).toBe('PayPalError');
    });

    it('creates error without status and code', () => {
      const error = new PayPalError('Simple error');
      expect(error.message).toBe('Simple error');
      expect(error.status).toBeUndefined();
      expect(error.code).toBeUndefined();
    });
  });
});
