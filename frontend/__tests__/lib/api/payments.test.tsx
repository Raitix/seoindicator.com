/**
 * Payment API Hooks Tests
 *
 * Tests the payment hooks in `lib/api/payments.ts`.
 *
 * These hooks are NOT React Query mutations. The five action hooks
 * (createIntent, confirmPayment, addMethod, deleteMethod, setDefault)
 * are plain async callbacks that own their own loading and error state
 * and resolve to a value; the two read hooks (usePaymentMethods,
 * usePaymentHistory) are SWR hooks that return a `mutate` function.
 *
 * Every request goes through `apiRequest`, which prefixes `/api/v1` and
 * unwraps an `{ success, data }` envelope.
 *
 * @see lib/api/payments.ts
 */

import { renderHook, waitFor, act } from '@testing-library/react';
import { SWRConfig } from 'swr';
import {
  useAddPaymentMethod,
  useConfirmPayment,
  useCreatePaymentIntent,
  useDeletePaymentMethod,
  usePaymentHistory,
  usePaymentMethods,
  useSetDefaultPaymentMethod,
} from '@/lib/api/payments';

// ============================================================================
// Mocks
// ============================================================================

const mockFetch = vi.fn();
global.fetch = mockFetch;

const jsonResponse = (body: unknown, ok = true, status = 200) => ({
  ok,
  status,
  json: async () => body,
});

/**
 * SWR caches by key in a global cache. The read hooks use a fixed key, so
 * without a fresh cache per test the second test in a describe block would
 * get the first test's cached value instead of calling fetch again.
 */
const renderWithFreshCache = <T,>(hook: () => T) =>
  renderHook(hook, {
    wrapper: ({ children }) => (
      <SWRConfig value={{ provider: () => new Map(), dedupingInterval: 0 }}>
        {children}
      </SWRConfig>
    ),
  });

beforeEach(() => {
  mockFetch.mockReset();
});

afterEach(() => {
  vi.restoreAllMocks();
});

// ============================================================================
// useCreatePaymentIntent
// ============================================================================

describe('useCreatePaymentIntent', () => {
  it('starts with no loading state and no error', () => {
    const { result } = renderHook(() => useCreatePaymentIntent());

    expect(result.current.isLoading).toBe(false);
    expect(result.current.error).toBeNull();
  });

  it('POSTs to /api/v1/payments/stripe/intent', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({
        success: true,
        data: {
          client_secret: 'pi_test_secret',
          payment_intent_id: 'pi_123',
          amount: 29,
          credits: 290,
          package_name: 'Pro',
        },
      })
    );

    const { result } = renderHook(() => useCreatePaymentIntent());

    await act(async () => {
      await result.current.createIntent({ packageId: 'pkg_pro' });
    });

    const [url, init] = mockFetch.mock.calls[0];
    expect(url).toBe('/api/v1/payments/stripe/intent');
    expect(init.method).toBe('POST');
    expect(JSON.parse(init.body)).toMatchObject({ package_id: 'pkg_pro' });
  });

  it('sends an Idempotency-Key header', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({
        success: true,
        data: {
          client_secret: 'pi_test_secret',
          payment_intent_id: 'pi_123',
          amount: 29,
          credits: 290,
        },
      })
    );

    const { result } = renderHook(() => useCreatePaymentIntent());

    await act(async () => {
      await result.current.createIntent({ packageId: 'pkg_pro' });
    });

    const headers = mockFetch.mock.calls[0][1].headers;
    expect(headers['Idempotency-Key']).toBeTruthy();
  });

  it('maps the response to a PaymentIntent', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({
        success: true,
        data: {
          client_secret: 'pi_test_secret',
          payment_intent_id: 'pi_123',
          amount: 29,
          credits: 290,
          package_name: 'Pro',
        },
      })
    );

    const { result } = renderHook(() => useCreatePaymentIntent());

    let intent: Awaited<ReturnType<typeof result.current.createIntent>> = null;
    await act(async () => {
      intent = await result.current.createIntent({ packageId: 'pkg_pro' });
    });

    expect(intent).not.toBeNull();
    expect(intent!.client_secret).toBe('pi_test_secret');
    expect(intent!.payment_intent_id).toBe('pi_123');
    expect(intent!.amount).toBe(29);
    expect(intent!.credits).toBe(290);
    expect(intent!.package_name).toBe('Pro');
  });

  it('converts a string amount to a number', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({
        success: true,
        data: {
          client_secret: 'pi_test_secret',
          payment_intent_id: 'pi_123',
          amount: '29.99',
          credits: '299',
        },
      })
    );

    const { result } = renderHook(() => useCreatePaymentIntent());

    let intent: Awaited<ReturnType<typeof result.current.createIntent>> = null;
    await act(async () => {
      intent = await result.current.createIntent({ packageId: 'pkg_pro' });
    });

    expect(intent!.amount).toBe(29.99);
    expect(intent!.credits).toBe(299);
  });

  it('sets the loading state while the request is in flight', async () => {
    let resolve!: (v: unknown) => void;
    mockFetch.mockImplementationOnce(
      () => new Promise((r) => { resolve = r; })
    );

    const { result } = renderHook(() => useCreatePaymentIntent());

    let pending!: Promise<unknown>;
    act(() => {
      pending = result.current.createIntent({ packageId: 'pkg_pro' });
    });

    await waitFor(() => expect(result.current.isLoading).toBe(true));

    await act(async () => {
      resolve(
        jsonResponse({
          success: true,
          data: { client_secret: 'x', payment_intent_id: 'y', amount: 1, credits: 1 },
        })
      );
      await pending;
    });

    await waitFor(() => expect(result.current.isLoading).toBe(false));
  });

  it('clears the loading state and sets the error on failure', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ error: { message: 'Payment failed', code: 'PAYMENT_ERROR' } }, false, 402)
    );

    const { result } = renderHook(() => useCreatePaymentIntent());

    let intent: Awaited<ReturnType<typeof result.current.createIntent>> = null;
    await act(async () => {
      intent = await result.current.createIntent({ packageId: 'pkg_pro' });
    });

    expect(intent).toBeNull();
    await waitFor(() => expect(result.current.error).not.toBeNull());
    expect(result.current.isLoading).toBe(false);
  });

  it('returns null when the envelope reports a failure', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ success: false, message: 'Package not found' })
    );

    const { result } = renderHook(() => useCreatePaymentIntent());

    let intent: Awaited<ReturnType<typeof result.current.createIntent>> = null;
    await act(async () => {
      intent = await result.current.createIntent({ packageId: 'pkg_missing' });
    });

    expect(intent).toBeNull();
    await waitFor(() => expect(result.current.error).not.toBeNull());
  });
});

// ============================================================================
// useConfirmPayment
// ============================================================================

describe('useConfirmPayment', () => {
  it('starts with no loading state and no error', () => {
    const { result } = renderHook(() => useConfirmPayment());

    expect(result.current.isLoading).toBe(false);
    expect(result.current.error).toBeNull();
  });

  it('POSTs the payment intent id to /api/v1/payments/confirm', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ success: true, data: { status: 'succeeded' } })
    );

    const { result } = renderHook(() => useConfirmPayment());

    await act(async () => {
      await result.current.confirmPayment('pi_123');
    });

    const [url, init] = mockFetch.mock.calls[0];
    expect(url).toBe('/api/v1/payments/confirm');
    expect(init.method).toBe('POST');
    expect(JSON.parse(init.body)).toMatchObject({ payment_intent_id: 'pi_123' });
  });

  it('resolves with the confirmation payload from the envelope', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ success: true, data: { success: true, credits: 290, balance: 290 } })
    );

    const { result } = renderHook(() => useConfirmPayment());

    let confirmation: unknown = null;
    await act(async () => {
      confirmation = await result.current.confirmPayment('pi_123');
    });

    expect(confirmation).toMatchObject({ success: true, credits: 290, balance: 290 });
  });

  it('sets the error on failure', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ error: { message: 'Confirmation failed' } }, false, 400)
    );

    const { result } = renderHook(() => useConfirmPayment());

    await act(async () => {
      await result.current.confirmPayment('pi_bad');
    });

    await waitFor(() => expect(result.current.error).not.toBeNull());
  });
});

// ============================================================================
// usePaymentMethods (SWR)
// ============================================================================

describe('usePaymentMethods', () => {
  it('requests /api/v1/payments/methods', async () => {
    mockFetch.mockResolvedValueOnce(jsonResponse({ success: true, data: [] }));

    renderWithFreshCache(() => usePaymentMethods());

    await waitFor(() => expect(mockFetch).toHaveBeenCalled());
    expect(mockFetch.mock.calls[0][0]).toBe('/api/v1/payments/methods');
  });

  it('maps the backend card fields onto the frontend shape', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({
        success: true,
        data: [
          {
            id: 'pm_1',
            provider: 'stripe',
            brand: 'visa',
            last_four: '4242',
            exp_month: 12,
            exp_year: 2030,
            is_default: true,
            created_at: '2026-03-06T10:00:00Z',
          },
        ],
      })
    );

    const { result } = renderWithFreshCache(() => usePaymentMethods());

    await waitFor(() => expect(result.current.methods).toHaveLength(1));
    expect(result.current.methods[0]).toMatchObject({
      id: 'pm_1',
      type: 'card',
      card: { brand: 'visa', last4: '4242', exp_month: 12, exp_year: 2030 },
      is_default: true,
    });
    expect(result.current.defaultMethod).toBe('pm_1');
    expect(result.current.isLoading).toBe(false);
    expect(result.current.error).toBeNull();
  });

  it('falls back to an empty list before the data arrives', () => {
    mockFetch.mockImplementationOnce(() => new Promise(() => {}));

    const { result } = renderWithFreshCache(() => usePaymentMethods());

    expect(result.current.methods).toEqual([]);
    expect(result.current.defaultMethod).toBeUndefined();
  });

  it('surfaces an error when the request fails', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ error: { message: 'Nope' } }, false, 500)
    );

    const { result } = renderWithFreshCache(() => usePaymentMethods());

    await waitFor(() => expect(result.current.error).not.toBeNull());
  });
});

// ============================================================================
// useAddPaymentMethod
// ============================================================================

describe('useAddPaymentMethod', () => {
  it('POSTs to /api/v1/payments/methods', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ method: { id: 'pm_new', brand: 'visa', last4: '1111' } })
    );

    const { result } = renderHook(() => useAddPaymentMethod());

    await act(async () => {
      await result.current.addMethod('pm_new');
    });

    const [url, init] = mockFetch.mock.calls[0];
    expect(url).toBe('/api/v1/payments/methods');
    expect(init.method).toBe('POST');
  });

  it('resolves with the added method', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ method: { id: 'pm_new', brand: 'visa', last4: '1111' } })
    );

    const { result } = renderHook(() => useAddPaymentMethod());

    let method: unknown = null;
    await act(async () => {
      method = await result.current.addMethod('pm_new');
    });

    expect(method).toMatchObject({ id: 'pm_new' });
  });

  it('sets the error on failure', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ error: { message: 'Card declined' } }, false, 402)
    );

    const { result } = renderHook(() => useAddPaymentMethod());

    await act(async () => {
      await result.current.addMethod('pm_bad');
    });

    await waitFor(() => expect(result.current.error).not.toBeNull());
  });
});

// ============================================================================
// useDeletePaymentMethod
// ============================================================================

describe('useDeletePaymentMethod', () => {
  it('DELETEs /api/v1/payments/methods/:id', async () => {
    mockFetch.mockResolvedValueOnce(jsonResponse({ success: true }));

    const { result } = renderHook(() => useDeletePaymentMethod());

    await act(async () => {
      await result.current.deleteMethod('pm_123');
    });

    const [url, init] = mockFetch.mock.calls[0];
    expect(url).toBe('/api/v1/payments/methods/pm_123');
    expect(init.method).toBe('DELETE');
  });

  it('sets the error on failure', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ error: { message: 'Not found' } }, false, 404)
    );

    const { result } = renderHook(() => useDeletePaymentMethod());

    await act(async () => {
      await result.current.deleteMethod('pm_missing');
    });

    await waitFor(() => expect(result.current.error).not.toBeNull());
  });
});

// ============================================================================
// useSetDefaultPaymentMethod
// ============================================================================

describe('useSetDefaultPaymentMethod', () => {
  it('PUTs /api/v1/payments/methods/:id/default', async () => {
    mockFetch.mockResolvedValueOnce(jsonResponse({ success: true }));

    const { result } = renderHook(() => useSetDefaultPaymentMethod());

    await act(async () => {
      await result.current.setDefault('pm_123');
    });

    const [url, init] = mockFetch.mock.calls[0];
    expect(url).toBe('/api/v1/payments/methods/pm_123/default');
    expect(init.method).toBe('PUT');
  });

  it('sets the error on failure', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ error: { message: 'Nope' } }, false, 400)
    );

    const { result } = renderHook(() => useSetDefaultPaymentMethod());

    await act(async () => {
      await result.current.setDefault('pm_bad');
    });

    await waitFor(() => expect(result.current.error).not.toBeNull());
  });
});

// ============================================================================
// usePaymentHistory (SWR)
// ============================================================================

describe('usePaymentHistory', () => {
  it('requests /api/v1/credits/transactions', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ success: true, data: [], meta: { current_page: 1, per_page: 15, total: 0, last_page: 1 } })
    );

    renderWithFreshCache(() => usePaymentHistory());

    await waitFor(() => expect(mockFetch).toHaveBeenCalled());
    expect(mockFetch.mock.calls[0][0]).toBe('/api/v1/credits/transactions');
  });

  it('passes the filters through as query parameters', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ success: true, data: [], meta: { current_page: 2, per_page: 25, total: 0, last_page: 3 } })
    );

    renderWithFreshCache(() => usePaymentHistory({ page: 2, limit: 25, type: 'purchase' }));

    await waitFor(() => expect(mockFetch).toHaveBeenCalled());
    const url = mockFetch.mock.calls[0][0] as string;
    expect(url).toContain('page=2');
    expect(url).toContain('per_page=25');
    expect(url).toContain('type=purchase');
  });

  it('returns the transactions and the pagination values', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({
        success: true,
        data: [
          {
            id: 'tx_1',
            amount: 29,
            balance_after: 290,
            type: 'purchase',
            description: 'Pro plan',
            payment_provider: 'stripe',
            created_at: '2026-03-06T10:00:00Z',
          },
        ],
        meta: { current_page: 2, per_page: 25, total: 42, last_page: 2 },
      })
    );

    const { result } = renderWithFreshCache(() => usePaymentHistory());

    await waitFor(() => expect(result.current.transactions).toHaveLength(1));
    expect(result.current.transactions[0]).toMatchObject({
      id: 'tx_1',
      amount: 29,
      currency: 'USD',
      credits: 290,
      payment_method: 'stripe',
      description: 'Pro plan',
    });
    expect(result.current.total).toBe(42);
    expect(result.current.page).toBe(2);
    expect(result.current.limit).toBe(25);
    expect(result.current.totalPages).toBe(2);
  });

  it('falls back to the default pagination before the data arrives', () => {
    mockFetch.mockImplementationOnce(() => new Promise(() => {}));

    const { result } = renderWithFreshCache(() => usePaymentHistory());

    expect(result.current.transactions).toEqual([]);
    expect(result.current.total).toBe(0);
    expect(result.current.page).toBe(1);
    expect(result.current.limit).toBe(15);
    expect(result.current.totalPages).toBe(1);
  });

  it('surfaces an error when the request fails', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ error: { message: 'Nope' } }, false, 500)
    );

    const { result } = renderWithFreshCache(() => usePaymentHistory());

    await waitFor(() => expect(result.current.error).not.toBeNull());
  });
});

// ============================================================================
// Error shape
// ============================================================================

describe('Payment error handling', () => {
  it('exposes the message and code from the API error envelope', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse(
        { error: { message: 'Insufficient credits', code: 'INSUFFICIENT_CREDITS' } },
        false,
        402
      )
    );

    const { result } = renderHook(() => useCreatePaymentIntent());

    await act(async () => {
      await result.current.createIntent({ packageId: 'pkg_pro' });
    });

    await waitFor(() => expect(result.current.error).not.toBeNull());
    expect(result.current.error!.message).toBe('Insufficient credits');
    expect(result.current.error!.code).toBe('INSUFFICIENT_CREDITS');
  });

  it('can have the error cleared by the caller', async () => {
    mockFetch.mockResolvedValueOnce(
      jsonResponse({ error: { message: 'Nope' } }, false, 400)
    );

    const { result } = renderHook(() => useCreatePaymentIntent());

    await act(async () => {
      await result.current.createIntent({ packageId: 'pkg_pro' });
    });

    await waitFor(() => expect(result.current.error).not.toBeNull());

    act(() => {
      result.current.setError(null);
    });

    expect(result.current.error).toBeNull();
  });
});
