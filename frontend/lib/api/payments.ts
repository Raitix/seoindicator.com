/**
 * Payment API Hooks
 * TASK-26-23: Stripe Payment Flow
 * 
 * React hooks and API functions for Stripe payment operations.
 */

import useSWR, { SWRConfiguration, mutate } from 'swr';
import { useCallback, useState } from 'react';

// ==================== Types ====================

export interface CreditPackage {
  id: string | number;
  name: string;
  credits: number;
  price: number;
  currency?: string;
  bonus_credits?: number;
  bonus_percent?: number;
  is_popular?: boolean;
  popular?: boolean;
  type?: 'one-time' | 'subscription';
}

export interface PaymentIntent {
  id: string;
  client_secret: string;
  payment_intent_id: string;
  amount: number;
  credits: number;
  currency?: string;
}

export interface PaymentMethod {
  id: string;
  type: 'card' | 'paypal' | 'apple_pay' | 'google_pay';
  card?: {
    brand: string;
    last4: string;
    exp_month: number;
    exp_year: number;
  };
  is_default: boolean;
}

export interface Transaction {
  id: string;
  amount: number;
  currency: string;
  credits: number;
  status: 'pending' | 'completed' | 'failed' | 'cancelled' | 'refunded';
  payment_method: string;
  created_at: string;
  receipt_url?: string;
  description?: string;
}

export interface PaymentError {
  type: string;
  code?: string;
  message: string;
  retry_after?: number;
}

// ==================== API Base ====================

const API_BASE = '/api/v1';

async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${API_BASE}${endpoint}`;
  
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string>),
  };

  // Add CSRF token if available
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    headers['X-CSRF-TOKEN'] = csrfToken;
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  const data = await response.json();

  if (!response.ok) {
    throw {
      type: 'api_error',
      message: data.error?.message || 'An error occurred',
      code: data.error?.code,
      status: response.status,
    };
  }

  return data;
}

// ==================== Payment Intent Hooks ====================

export interface CreatePaymentIntentParams {
  packageId: string | number;
  email?: string;
  name?: string;
  savedCardId?: string;
  couponCode?: string;
}

/**
 * Hook to create a payment intent
 */
export function useCreatePaymentIntent() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<PaymentError | null>(null);

  const createIntent = useCallback(
    async (params: CreatePaymentIntentParams): Promise<PaymentIntent | null> => {
      setIsLoading(true);
      setError(null);

      const idempotencyKey = crypto.randomUUID();

      try {
        const result = await apiRequest<PaymentIntent>('/payments/create-intent', {
          method: 'POST',
          body: JSON.stringify(params),
          headers: {
            'Idempotency-Key': idempotencyKey,
          },
        });

        return result;
      } catch (err: unknown) {
        const error = err as PaymentError;
        setError(error);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    []
  );

  return { createIntent, isLoading, error, setError };
}

/**
 * Hook to confirm a payment
 */
export function useConfirmPayment() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<PaymentError | null>(null);

  const confirmPayment = useCallback(
    async (
      paymentIntentId: string,
      paymentMethodId?: string,
      saveCard = false
    ): Promise<{
      success: boolean;
      credits?: number;
      balance?: number;
      transaction_id?: string;
    } | null> => {
      setIsLoading(true);
      setError(null);

      const idempotencyKey = crypto.randomUUID();

      try {
        const result = await apiRequest<{
          success: boolean;
          credits?: number;
          balance?: number;
          transaction_id?: string;
        }>('/payments/confirm', {
          method: 'POST',
          body: JSON.stringify({
            payment_intent_id: paymentIntentId,
            payment_method_id: paymentMethodId,
            save_card: saveCard,
          }),
          headers: {
            'Idempotency-Key': idempotencyKey,
          },
        });

        return result;
      } catch (err: unknown) {
        const error = err as PaymentError;
        setError(error);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    []
  );

  return { confirmPayment, isLoading, error, setError };
}

// ==================== Payment Methods Hooks ====================

/**
 * Hook to fetch payment methods
 */
export function usePaymentMethods(options?: SWRConfiguration) {
  const { data, error, isLoading, mutate } = useSWR<{
    methods: PaymentMethod[];
    default_method?: string;
  }>(
    `${API_BASE}/payments/methods`,
    (url) => apiRequest(url),
    {
      revalidateOnFocus: false,
      ...options,
    }
  );

  return {
    methods: data?.methods || [],
    defaultMethod: data?.default_method,
    isLoading,
    error: error as PaymentError | null,
    mutate,
  };
}

/**
 * Hook to add a payment method
 */
export function useAddPaymentMethod() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<PaymentError | null>(null);

  const addMethod = useCallback(
    async (paymentMethodId: string, setDefault = false): Promise<PaymentMethod | null> => {
      setIsLoading(true);
      setError(null);

      try {
        const result = await apiRequest<{ method: PaymentMethod }>('/payments/methods', {
          method: 'POST',
          body: JSON.stringify({
            payment_method_id: paymentMethodId,
            set_default: setDefault,
          }),
        });

        // Revalidate payment methods
        mutate(`${API_BASE}/payments/methods`);

        return result.method;
      } catch (err: unknown) {
        const error = err as PaymentError;
        setError(error);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    []
  );

  return { addMethod, isLoading, error, setError };
}

/**
 * Hook to delete a payment method
 */
export function useDeletePaymentMethod() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<PaymentError | null>(null);

  const deleteMethod = useCallback(
    async (methodId: string): Promise<boolean> => {
      setIsLoading(true);
      setError(null);

      try {
        await apiRequest(`/payments/methods/${methodId}`, {
          method: 'DELETE',
        });

        // Revalidate payment methods
        mutate(`${API_BASE}/payments/methods`);

        return true;
      } catch (err: unknown) {
        const error = err as PaymentError;
        setError(error);
        return false;
      } finally {
        setIsLoading(false);
      }
    },
    []
  );

  return { deleteMethod, isLoading, error, setError };
}

/**
 * Hook to set default payment method
 */
export function useSetDefaultPaymentMethod() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<PaymentError | null>(null);

  const setDefault = useCallback(
    async (methodId: string): Promise<boolean> => {
      setIsLoading(true);
      setError(null);

      try {
        await apiRequest(`/payments/methods/${methodId}/default`, {
          method: 'PUT',
        });

        // Revalidate payment methods
        mutate(`${API_BASE}/payments/methods`);

        return true;
      } catch (err: unknown) {
        const error = err as PaymentError;
        setError(error);
        return false;
      } finally {
        setIsLoading(false);
      }
    },
    []
  );

  return { setDefault, isLoading, error, setError };
}

// ==================== Transaction History Hooks ====================

export interface TransactionFilters {
  page?: number;
  limit?: number;
  status?: string;
  startDate?: string;
  endDate?: string;
  type?: string;
  minAmount?: number;
  maxAmount?: number;
  search?: string;
}

/**
 * Hook to fetch transaction history
 */
export function usePaymentHistory(filters: TransactionFilters = {}, options?: SWRConfiguration) {
  const params = new URLSearchParams();
  
  if (filters.page) params.set('page', String(filters.page));
  if (filters.limit) params.set('limit', String(filters.limit));
  if (filters.status) params.set('status', filters.status);
  if (filters.startDate) params.set('start_date', filters.startDate);
  if (filters.endDate) params.set('end_date', filters.endDate);
  if (filters.type) params.set('type', filters.type);
  if (filters.minAmount) params.set('min_amount', String(filters.minAmount));
  if (filters.maxAmount) params.set('max_amount', String(filters.maxAmount));
  if (filters.search) params.set('search', filters.search);

  const queryString = params.toString();
  const url = `${API_BASE}/payments/history${queryString ? `?${queryString}` : ''}`;

  const { data, error, isLoading, mutate } = useSWR<{
    transactions: Transaction[];
    total: number;
    page: number;
    limit: number;
    total_pages: number;
  }>(
    url,
    (u) => apiRequest(u),
    {
      revalidateOnFocus: false,
      ...options,
    }
  );

  return {
    transactions: data?.transactions || [],
    total: data?.total || 0,
    page: data?.page || 1,
    limit: data?.limit || 10,
    totalPages: data?.total_pages || 1,
    isLoading,
    error: error as PaymentError | null,
    mutate,
  };
}

/**
 * Hook to download a receipt
 */
export function useDownloadReceipt() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<PaymentError | null>(null);

  const downloadReceipt = useCallback(
    async (transactionId: string): Promise<string | null> => {
      setIsLoading(true);
      setError(null);

      try {
        const result = await apiRequest<{ receipt_url: string }>(
          `/payments/history/${transactionId}/receipt`
        );

        // Open receipt in new window or download
        if (result.receipt_url) {
          window.open(result.receipt_url, '_blank');
        }

        return result.receipt_url;
      } catch (err: unknown) {
        const error = err as PaymentError;
        setError(error);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    []
  );

  return { downloadReceipt, isLoading, error, setError };
}

// ==================== Credit Packages Hook ====================

/**
 * Hook to fetch available credit packages
 */
export function useCreditPackages(options?: SWRConfiguration) {
  const { data, error, isLoading, mutate } = useSWR<{ packages: CreditPackage[] }>(
    `${API_BASE}/credits/packages`,
    (url) => apiRequest(url),
    {
      revalidateOnFocus: false,
      ...options,
    }
  );

  return {
    packages: data?.packages || [],
    isLoading,
    error: error as PaymentError | null,
    mutate,
  };
}

// ==================== Utility Functions ====================

/**
 * Get card brand icon
 */
export function getCardBrandIcon(brand: string): string {
  const brands: Record<string, string> = {
    visa: '/icons/cards/visa.svg',
    mastercard: '/icons/cards/mastercard.svg',
    amex: '/icons/cards/amex.svg',
    discover: '/icons/cards/discover.svg',
    diners: '/icons/cards/diners.svg',
    jcb: '/icons/cards/jcb.svg',
    unionpay: '/icons/cards/unionpay.svg',
  };
  return brands[brand.toLowerCase()] || '/icons/cards/unknown.svg';
}

/**
 * Get card brand display name
 */
export function getCardBrandName(brand: string): string {
  const names: Record<string, string> = {
    visa: 'Visa',
    mastercard: 'Mastercard',
    amex: 'American Express',
    discover: 'Discover',
    diners: 'Diners Club',
    jcb: 'JCB',
    unionpay: 'UnionPay',
  };
  return names[brand.toLowerCase()] || brand;
}

/**
 * Format currency
 */
export function formatCurrency(amount: number, currency = 'USD'): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency,
  }).format(amount);
}

/**
 * Format transaction date
 */
export function formatTransactionDate(date: string): string {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
}

/**
 * Get error message from error code
 */
export function getPaymentErrorMessage(code: string): string {
  const messages: Record<string, string> = {
    card_declined: 'Your card was declined.',
    insufficient_funds: 'Insufficient funds in your account.',
    expired_card: 'Your card has expired.',
    incorrect_cvc: 'Invalid security code.',
    processing_error: 'Processing error. Please try again.',
    rate_limit: 'Too many attempts. Please wait.',
    timeout: 'Payment timed out.',
    duplicate: 'Payment already processed.',
    fraud: 'Payment blocked. Please contact support.',
    currency_not_supported: 'Currency not supported.',
    amount_too_large: 'Amount exceeds limit.',
    network: 'Network error. Please check your connection.',
  };
  return messages[code] || 'An error occurred. Please try again.';
}

/**
 * Check if card is expired
 */
export function isCardExpired(expMonth: number, expYear: number): boolean {
  const now = new Date();
  const currentMonth = now.getMonth() + 1;
  const currentYear = now.getFullYear();
  
  if (expYear < currentYear) return true;
  if (expYear === currentYear && expMonth < currentMonth) return true;
  
  return false;
}

/**
 * Check if card is expiring soon (within 30 days)
 */
export function isCardExpiringSoon(expMonth: number, expYear: number): boolean {
  const now = new Date();
  const expiry = new Date(expYear, expMonth - 1);
  const diffDays = Math.ceil((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
  
  return diffDays > 0 && diffDays <= 30;
}

// ==================== Re-export Types ====================

export type {
  CreditPackage as PaymentCreditPackage,
  PaymentIntent as PaymentIntentType,
  PaymentMethod as PaymentMethodType,
  Transaction as TransactionType,
  PaymentError as PaymentErrorType,
};
