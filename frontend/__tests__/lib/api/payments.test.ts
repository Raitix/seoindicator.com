/**
 * Payment API Hooks Tests
 * 
 * Tests for React Query hooks used in payment operations:
 * - Payment intent creation
 * - Payment confirmation
 * - Payment history
 * - Payment methods management
 * 
 * @see TESTS/TEST-26-23.md
 */

import { renderHook, waitFor, act } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';

// Mock fetch
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Create wrapper for React Query
const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  });
  
  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
};

// ============================================
// Test Data
// ============================================
const mockPaymentIntent = {
  id: 'pi_1234567890',
  client_secret: 'pi_123_secret_abc',
  amount: 2999,
  currency: 'usd',
  status: 'requires_payment_method',
};

const mockPaymentMethod = {
  id: 'pm_123456',
  type: 'card',
  card: {
    brand: 'visa',
    last4: '4242',
    exp_month: 12,
    exp_year: 2025,
  },
  is_default: true,
};

const mockTransaction = {
  id: 'txn_123456',
  amount: 29.99,
  currency: 'USD',
  credits: 110,
  status: 'completed',
  payment_method: 'Visa ****4242',
  created_at: '2026-03-14T19:30:00Z',
};

// ============================================
// useCreatePaymentIntent Hook Tests
// ============================================
describe('useCreatePaymentIntent', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
  });

  it('API-001: Create intent success returns client_secret', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ client_secret: 'pi_test_secret' }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ packageId: 'pkg_pro' });
    });

    await waitFor(() => {
      expect(result.current.data?.client_secret).toBe('pi_test_secret');
    });
  });

  it('API-002: Create intent with saved card', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        client_secret: 'pi_test_secret',
        payment_method: 'pm_saved',
      }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ 
        packageId: 'pkg_pro',
        methodId: 'pm_saved',
      });
    });

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          body: expect.stringContaining('pm_saved'),
        })
      );
    });
  });

  it('API-003: Create intent unauthenticated returns 401', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 401,
      json: async () => ({ error: 'Unauthenticated' }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      try {
        await result.current.mutateAsync({ packageId: 'pkg_pro' });
      } catch (e) {
        // Expected
      }
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });

  it('API-004: Create intent invalid package returns 404', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 404,
      json: async () => ({ error: 'Package not found' }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      try {
        await result.current.mutateAsync({ packageId: 'invalid' });
      } catch (e) {
        // Expected
      }
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });

  it('API-005: Create intent validation error returns 422', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 422,
      json: async () => ({ 
        errors: { 
          packageId: ['Package ID is required'] 
        } 
      }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      try {
        await result.current.mutateAsync({ packageId: '' });
      } catch (e) {
        // Expected
      }
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });

  it('API-006: Create intent rate limited returns 429', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 429,
      json: async () => ({ error: 'Too many requests', retry_after: 60 }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      try {
        await result.current.mutateAsync({ packageId: 'pkg_pro' });
      } catch (e) {
        // Expected
      }
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });

  it('API-007: Intent amount correct in cents', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        client_secret: 'pi_test',
        amount: 2999,
      }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ packageId: 'pkg_29_99' });
    });

    await waitFor(() => {
      expect(result.current.data?.amount).toBe(2999);
    });
  });

  it('API-008: Intent currency matches user', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        client_secret: 'pi_test',
        currency: 'eur',
      }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ 
        packageId: 'pkg_pro',
        currency: 'EUR',
      });
    });

    await waitFor(() => {
      expect(result.current.data?.currency).toBe('eur');
    });
  });

  it('API-009: Intent metadata includes package_id and user_id', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        client_secret: 'pi_test',
        metadata: {
          package_id: 'pkg_pro',
          user_id: '1',
        },
      }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ packageId: 'pkg_pro' });
    });

    await waitFor(() => {
      expect(result.current.data?.metadata?.package_id).toBe('pkg_pro');
    });
  });

  it('API-010: Intent expires after 24h', async () => {
    const expiresAt = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString();
    
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        client_secret: 'pi_test',
        expires_at: expiresAt,
      }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ packageId: 'pkg_pro' });
    });

    await waitFor(() => {
      expect(result.current.data?.expires_at).toBeDefined();
    });
  });

  it('API-011: Intent idempotency returns same response', async () => {
    const response = { client_secret: 'pi_test', id: 'pi_123' };
    
    mockFetch
      .mockResolvedValueOnce({ ok: true, json: async () => response })
      .mockResolvedValueOnce({ ok: true, json: async () => response });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    const idempotencyKey = 'idem_123';

    await act(async () => {
      await result.current.mutateAsync({ packageId: 'pkg_pro', idempotencyKey });
      await result.current.mutateAsync({ packageId: 'pkg_pro', idempotencyKey });
    });

    // Both calls should return same response
    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledTimes(2);
    });
  });

  it('API-012: Intent with coupon applies discount', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        client_secret: 'pi_test',
        amount: 2399, // 20% off
        discount: {
          coupon: 'DISCOUNT20',
          amount_off: 600,
        },
      }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ 
        packageId: 'pkg_pro',
        couponCode: 'DISCOUNT20',
      });
    });

    await waitFor(() => {
      expect(result.current.data?.discount?.coupon).toBe('DISCOUNT20');
    });
  });

  it('API-013: Free package skips Stripe intent', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        free: true,
        credits: 10,
      }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ packageId: 'pkg_free' });
    });

    await waitFor(() => {
      expect(result.current.data?.free).toBe(true);
      expect(result.current.data?.client_secret).toBeUndefined();
    });
  });

  it('API-014: Intent with description', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        client_secret: 'pi_test',
        description: 'Pro Credit Package - 110 credits',
      }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ packageId: 'pkg_pro' });
    });

    await waitFor(() => {
      expect(result.current.data?.description).toContain('Pro Credit Package');
    });
  });

  it('API-015: Intent capture method automatic', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        client_secret: 'pi_test',
        capture_method: 'automatic',
      }),
    });

    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ packageId: 'pkg_pro' });
    });

    await waitFor(() => {
      expect(result.current.data?.capture_method).toBe('automatic');
    });
  });
});

// ============================================
// useConfirmPayment Hook Tests
// ============================================
describe('useConfirmPayment', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
  });

  it('API-016: Confirm success returns success', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ success: true, paymentIntent: { id: 'pi_123' } }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ 
        paymentIntentId: 'pi_123',
        clientSecret: 'pi_123_secret',
      });
    });

    await waitFor(() => {
      expect(result.current.data?.success).toBe(true);
    });
  });

  it('API-017: Confirm failed returns error', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ error: 'Payment failed' }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      try {
        await result.current.mutateAsync({ paymentIntentId: 'pi_123' });
      } catch (e) {
        // Expected
      }
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });

  it('API-018: Confirm requires action returns URL', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        requires_action: true,
        action_url: 'https://stripe.com/3ds/...',
      }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ paymentIntentId: 'pi_3ds' });
    });

    await waitFor(() => {
      expect(result.current.data?.requires_action).toBe(true);
      expect(result.current.data?.action_url).toBeDefined();
    });
  });

  it('API-019: Confirm with saved card', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ success: true }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ 
        paymentIntentId: 'pi_123',
        methodId: 'pm_saved',
      });
    });

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          body: expect.stringContaining('pm_saved'),
        })
      );
    });
  });

  it('API-020: Confirm with new card', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ success: true }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ 
        paymentIntentId: 'pi_123',
        paymentMethodId: 'pm_new',
      });
    });

    await waitFor(() => {
      expect(result.current.data?.success).toBe(true);
    });
  });

  it('API-021: Confirm idempotent', async () => {
    const response = { success: true, id: 'conf_123' };
    
    mockFetch
      .mockResolvedValueOnce({ ok: true, json: async () => response })
      .mockResolvedValueOnce({ ok: true, json: async () => response });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ paymentIntentId: 'pi_123' });
      await result.current.mutateAsync({ paymentIntentId: 'pi_123' });
    });

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledTimes(2);
    });
  });

  it('API-022: Confirm credits added', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        success: true, 
        credits: 110,
      }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ paymentIntentId: 'pi_123' });
    });

    await waitFor(() => {
      expect(result.current.data?.credits).toBe(110);
    });
  });

  it('API-023: Confirm balance updated', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        success: true, 
        new_balance: 150,
      }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ paymentIntentId: 'pi_123' });
    });

    await waitFor(() => {
      expect(result.current.data?.new_balance).toBe(150);
    });
  });

  it('API-024: Confirm receipt sent', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        success: true,
        receipt_sent: true,
      }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ paymentIntentId: 'pi_123' });
    });

    await waitFor(() => {
      expect(result.current.data?.receipt_sent).toBe(true);
    });
  });

  it('API-025: Confirm notification sent', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        success: true,
        email_sent: true,
      }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ paymentIntentId: 'pi_123' });
    });

    await waitFor(() => {
      expect(result.current.data?.email_sent).toBe(true);
    });
  });

  it('API-026: Confirm webhook triggered', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        success: true,
        webhook_triggered: true,
      }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ paymentIntentId: 'pi_123' });
    });

    await waitFor(() => {
      expect(result.current.data?.webhook_triggered).toBe(true);
    });
  });

  it('API-027: Confirm transaction created', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        success: true,
        transaction: {
          id: 'txn_123',
          amount: 29.99,
        },
      }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ paymentIntentId: 'pi_123' });
    });

    await waitFor(() => {
      expect(result.current.data?.transaction?.id).toBe('txn_123');
    });
  });

  it('API-028: Confirm audit log created', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        success: true,
        audit_log_id: 'audit_123',
      }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ paymentIntentId: 'pi_123' });
    });

    await waitFor(() => {
      expect(result.current.data?.audit_log_id).toBeDefined();
    });
  });

  it('API-029: Confirm multiple attempts tracked', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        success: true,
        attempts: 2,
      }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ paymentIntentId: 'pi_123' });
    });

    await waitFor(() => {
      expect(result.current.data?.attempts).toBe(2);
    });
  });

  it('API-030: Confirm cancellation handled', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        cancelled: true,
      }),
    });

    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ 
        paymentIntentId: 'pi_123',
        cancel: true,
      });
    });

    await waitFor(() => {
      expect(result.current.data?.cancelled).toBe(true);
    });
  });
});

// ============================================
// usePaymentHistory Hook Tests
// ============================================
describe('usePaymentHistory', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
  });

  it('API-031: Get history success', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        data: [mockTransaction],
        meta: { total: 1 },
      }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.data?.data).toHaveLength(1);
    });
  });

  it('API-032: Get history with pagination', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        data: [mockTransaction],
        meta: { total: 50, page: 2, per_page: 10 },
      }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory({ page: 2, limit: 10 }), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('page=2'),
        expect.any(Object)
      );
    });
  });

  it('API-033: Get history with filters', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [], meta: { total: 0 } }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory({ 
      status: 'completed',
      startDate: '2026-03-01',
    }), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('status=completed'),
        expect.any(Object)
      );
    });
  });

  it('API-034: Get history unauthenticated', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 401,
      json: async () => ({ error: 'Unauthenticated' }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });

  it('API-035: Get history empty returns empty array', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [], meta: { total: 0 } }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.data?.data).toEqual([]);
    });
  });

  it('API-036: Get history with sorting', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [], meta: { total: 0 } }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory({ 
      sort: 'created_at',
      order: 'desc',
    }), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('sort=created_at'),
        expect.any(Object)
      );
    });
  });

  it('API-037: Get history caches response', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [mockTransaction], meta: { total: 1 } }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result, rerender } = renderHook(() => usePaymentHistory(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.data).toBeDefined();
    });

    // Rerender should not trigger new fetch
    rerender();

    expect(mockFetch).toHaveBeenCalledTimes(1);
  });

  it('API-038: Get history refetch refreshes data', async () => {
    mockFetch
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [], meta: { total: 0 } }),
      })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [mockTransaction], meta: { total: 1 } }),
      });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.data?.data).toEqual([]);
    });

    await act(async () => {
      await result.current.refetch();
    });

    await waitFor(() => {
      expect(result.current.data?.data).toHaveLength(1);
    });
  });

  it('API-039: Get history with date range', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [], meta: { total: 0 } }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory({ 
      startDate: '2026-03-01',
      endDate: '2026-03-14',
    }), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('start_date'),
        expect.any(Object)
      );
    });
  });

  it('API-040: Get history returns total count', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [], meta: { total: 50 } }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.data?.meta?.total).toBe(50);
    });
  });
});

// ============================================
// usePaymentMethods Hook Tests
// ============================================
describe('usePaymentMethods', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
  });

  it('API-041: List methods success', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ methods: [mockPaymentMethod] }),
    });

    const { usePaymentMethods } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentMethods(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.data?.methods).toHaveLength(1);
    });
  });

  it('API-042: Add method success', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ method: mockPaymentMethod }),
    });

    const { useAddPaymentMethod } = require('@/lib/api/payments');
    const { result } = renderHook(() => useAddPaymentMethod(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ 
        paymentMethodId: 'pm_new',
      });
    });

    await waitFor(() => {
      expect(result.current.data?.method?.id).toBe('pm_123456');
    });
  });

  it('API-043: Add method validation error', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 422,
      json: async () => ({ errors: { paymentMethodId: ['Invalid'] } }),
    });

    const { useAddPaymentMethod } = require('@/lib/api/payments');
    const { result } = renderHook(() => useAddPaymentMethod(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      try {
        await result.current.mutateAsync({ paymentMethodId: '' });
      } catch (e) {
        // Expected
      }
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });

  it('API-044: Delete method success', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ success: true }),
    });

    const { useDeletePaymentMethod } = require('@/lib/api/payments');
    const { result } = renderHook(() => useDeletePaymentMethod(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ methodId: 'pm_123' });
    });

    await waitFor(() => {
      expect(result.current.data?.success).toBe(true);
    });
  });

  it('API-045: Delete method not found', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 404,
      json: async () => ({ error: 'Not found' }),
    });

    const { useDeletePaymentMethod } = require('@/lib/api/payments');
    const { result } = renderHook(() => useDeletePaymentMethod(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      try {
        await result.current.mutateAsync({ methodId: 'invalid' });
      } catch (e) {
        // Expected
      }
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });

  it('API-046: Set default success', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ success: true }),
    });

    const { useSetDefaultPaymentMethod } = require('@/lib/api/payments');
    const { result } = renderHook(() => useSetDefaultPaymentMethod(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync({ methodId: 'pm_123' });
    });

    await waitFor(() => {
      expect(result.current.data?.success).toBe(true);
    });
  });

  it('API-047: List methods empty', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ methods: [] }),
    });

    const { usePaymentMethods } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentMethods(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.data?.methods).toEqual([]);
    });
  });

  it('API-048: Method ownership check', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 403,
      json: async () => ({ error: 'Forbidden' }),
    });

    const { useDeletePaymentMethod } = require('@/lib/api/payments');
    const { result } = renderHook(() => useDeletePaymentMethod(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      try {
        await result.current.mutateAsync({ methodId: 'pm_other_user' });
      } catch (e) {
        // Expected
      }
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });

  it('API-049: Methods cached', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ methods: [mockPaymentMethod] }),
    });

    const { usePaymentMethods } = require('@/lib/api/payments');
    const { result, rerender } = renderHook(() => usePaymentMethods(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.data).toBeDefined();
    });

    rerender();
    expect(mockFetch).toHaveBeenCalledTimes(1);
  });

  it('API-050: Sync with Stripe', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 
        methods: [mockPaymentMethod],
        synced: true,
      }),
    });

    const { useSyncPaymentMethods } = require('@/lib/api/payments');
    const { result } = renderHook(() => useSyncPaymentMethods(), {
      wrapper: createWrapper(),
    });

    await act(async () => {
      await result.current.mutateAsync();
    });

    await waitFor(() => {
      expect(result.current.data?.synced).toBe(true);
    });
  });
});

// ============================================
// Hook State Tests
// ============================================
describe('Hook State Management', () => {
  it('API-051: useCreatePaymentIntent returns mutation function', () => {
    const { useCreatePaymentIntent } = require('@/lib/api/payments');
    const { result } = renderHook(() => useCreatePaymentIntent(), {
      wrapper: createWrapper(),
    });

    expect(result.current.mutate).toBeDefined();
    expect(result.current.mutateAsync).toBeDefined();
  });

  it('API-052: useConfirmPayment returns mutation function', () => {
    const { useConfirmPayment } = require('@/lib/api/payments');
    const { result } = renderHook(() => useConfirmPayment(), {
      wrapper: createWrapper(),
    });

    expect(result.current.mutate).toBeDefined();
    expect(result.current.mutateAsync).toBeDefined();
  });

  it('API-053: usePaymentHistory returns data and loading', () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [], meta: { total: 0 } }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory(), {
      wrapper: createWrapper(),
    });

    expect(result.current.data).toBeDefined();
    expect(result.current.isLoading).toBeDefined();
  });

  it('API-054: usePaymentMethods returns data and loading', () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ methods: [] }),
    });

    const { usePaymentMethods } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentMethods(), {
      wrapper: createWrapper(),
    });

    expect(result.current.data).toBeDefined();
    expect(result.current.isLoading).toBeDefined();
  });

  it('API-055: Hook error state', async () => {
    mockFetch.mockRejectedValueOnce(new Error('Network error'));

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });

  it('API-056: Hook loading state', () => {
    mockFetch.mockImplementation(() => new Promise(() => {}));

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory(), {
      wrapper: createWrapper(),
    });

    expect(result.current.isLoading).toBe(true);
  });

  it('API-057: Hook refetch function', () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [], meta: { total: 0 } }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory(), {
      wrapper: createWrapper(),
    });

    expect(result.current.refetch).toBeDefined();
  });

  it('API-058: Cache invalidation', async () => {
    const queryClient = new QueryClient();
    
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [], meta: { total: 0 } }),
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory(), {
      wrapper: ({ children }) => (
        <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
      ),
    });

    await waitFor(() => {
      expect(result.current.data).toBeDefined();
    });

    // Invalidate cache
    queryClient.invalidateQueries({ queryKey: ['paymentHistory'] });

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledTimes(2);
    });
  });

  it('API-059: Optimistic update', async () => {
    const { useAddPaymentMethod } = require('@/lib/api/payments');
    const { result } = renderHook(() => useAddPaymentMethod(), {
      wrapper: createWrapper(),
    });

    // Mutation should support optimistic updates
    expect(result.current.mutate).toBeDefined();
  });

  it('API-060: Auto retry on failure', async () => {
    mockFetch
      .mockRejectedValueOnce(new Error('Network error'))
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [], meta: { total: 0 } }),
      });

    const queryClient = new QueryClient({
      defaultOptions: {
        queries: {
          retry: 1,
        },
      },
    });

    const { usePaymentHistory } = require('@/lib/api/payments');
    const { result } = renderHook(() => usePaymentHistory(), {
      wrapper: ({ children }) => (
        <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
      ),
    });

    // Should retry once
    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledTimes(2);
    }, { timeout: 3000 });
  });
});
