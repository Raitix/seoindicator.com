/**
 * Payment API Tests
 * TASK-26-23: Stripe Payment Flow
 * 
 * Tests for payment-related API helper functions.
 */

import { 
  createPaymentIntent, 
  getPaymentMethods, 
  deletePaymentMethod,
  setDefaultPaymentMethod 
} from '@/lib/api/payments';

// Mock fetch
const mockFetch = jest.fn();
global.fetch = mockFetch;

describe('Payment API', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockClear();
  });

  describe('API-001 to API-003: createPaymentIntent', () => {
    it('creates payment intent with valid data', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          client_secret: 'pi_test_123_secret_456',
          payment_intent_id: 'pi_test_123',
          amount: 25.00,
          credits: 150
        })
      });
      
      const result = await createPaymentIntent(25.00, 2);
      
      expect(mockFetch).toHaveBeenCalledWith('/api/v1/payments/stripe/intent', {
        method: 'POST',
        headers: expect.objectContaining({
          'Content-Type': 'application/json'
        }),
        body: JSON.stringify({ amount: 25.00, package_id: 2 })
      });
      
      expect(result).toEqual({
        client_secret: 'pi_test_123_secret_456',
        payment_intent_id: 'pi_test_123',
        amount: 25.00,
        credits: 150
      });
    });

    it('validates amount is positive', async () => {
      await expect(createPaymentIntent(-10, 1)).rejects.toThrow(/invalid amount/i);
      await expect(createPaymentIntent(0, 1)).rejects.toThrow(/invalid amount/i);
    });

    it('validates package ID is positive', async () => {
      await expect(createPaymentIntent(10, 0)).rejects.toThrow(/invalid package/i);
      await expect(createPaymentIntent(10, -1)).rejects.toThrow(/invalid package/i);
    });
  });

  describe('API-004 to API-006: Error Handling', () => {
    it('handles 401 unauthorized', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401
      });
      
      await expect(createPaymentIntent(10, 1)).rejects.toThrow(/unauthorized/i);
    });

    it('handles 429 rate limit with retry info', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 429,
        headers: new Headers({ 'Retry-After': '60' }),
        json: async () => ({ error: { message: 'Too many requests' } })
      });
      
      await expect(createPaymentIntent(10, 1)).rejects.toThrow(/too many requests/i);
    });

    it('handles 500 server error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => ({ error: { message: 'Server error' } })
      });
      
      await expect(createPaymentIntent(10, 1)).rejects.toThrow(/error occurred/i);
    });
  });

  describe('API-007 to API-010: Payment Methods', () => {
    it('gets saved payment methods', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          data: [
            { id: 'pm_123', type: 'card', card: { brand: 'visa', last4: '4242' }, is_default: true },
            { id: 'pm_456', type: 'card', card: { brand: 'mastercard', last4: '5555' }, is_default: false }
          ]
        })
      });
      
      const methods = await getPaymentMethods();
      
      expect(methods).toHaveLength(2);
      expect(methods[0].card.last4).toBe('4242');
    });

    it('deletes payment method', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true })
      });
      
      const result = await deletePaymentMethod('pm_123');
      
      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/payments/methods/pm_123',
        expect.objectContaining({ method: 'DELETE' })
      );
      
      expect(result.success).toBe(true);
    });

    it('sets default payment method', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ 
          id: 'pm_123', 
          is_default: true 
        })
      });
      
      const result = await setDefaultPaymentMethod('pm_123');
      
      expect(result.is_default).toBe(true);
    });

    it('payment intent response includes metadata', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          client_secret: 'pi_test_123',
          payment_intent_id: 'pi_test_123',
          amount: 25.00,
          credits: 150,
          package_id: 2,
          package_name: 'Growth'
        })
      });
      
      const result = await createPaymentIntent(25.00, 2);
      
      expect(result.credits).toBe(150);
      expect(result.package_name).toBe('Growth');
    });
  });
});
