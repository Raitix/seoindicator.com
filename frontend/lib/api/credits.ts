/**
 * Credits API functions for SEOIndicator
 *
 * Fetches user credit balance from the backend API.
 * Uses SWR for caching and real-time updates.
 *
 * @see SPEC-026
 * @see TASK-26-21
 */

import useSWR, { type MutatorCallback } from "swr";

// ==================== Types ====================

export interface BalanceResponse {
  balance: number;
  formatted_balance: string;
  total_purchased: number;
  total_spent: number;
  has_low_balance: boolean;
  low_balance_threshold: number;
  currency: string;
}

export interface FetchBalanceOptions {
  retries?: number;
  retryDelay?: number;
}

export class CreditsError extends Error {
  public status?: number;
  public code?: string;
  public retryAfter?: number;

  constructor(message: string, status?: number, code?: string) {
    super(message);
    this.name = "CreditsError";
    this.status = status;
    this.code = code;
  }
}

// ==================== Constants ====================

const API_BASE = "/api/v1";
const BALANCE_ENDPOINT = `${API_BASE}/credits/balance`;

// Default balance response for error cases
const DEFAULT_BALANCE: BalanceResponse = {
  balance: 0,
  formatted_balance: "$0.00",
  total_purchased: 0,
  total_spent: 0,
  has_low_balance: true,
  low_balance_threshold: 2.0,
  currency: "USD",
};

// ==================== fetchBalance ====================

/**
 * Fetch user credit balance from the API
 */
export async function fetchBalance(
  options: FetchBalanceOptions = {}
): Promise<BalanceResponse> {
  const { retries = 0, retryDelay = 1000 } = options;

  let lastError: Error | null = null;
  let attempt = 0;

  while (attempt <= retries) {
    try {
      const response = await fetch(BALANCE_ENDPOINT, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
      });

      if (!response.ok) {
        // Handle specific error statuses
        if (response.status === 401) {
          throw new CreditsError("Unauthorized. Please log in.", 401, "UNAUTHORIZED");
        }

        if (response.status === 429) {
          const retryAfter = response.headers.get("Retry-After");
          const error = new CreditsError(
            "Rate limit exceeded. Please try again later.",
            429,
            "RATE_LIMITED"
          );
          if (retryAfter) {
            error.retryAfter = parseInt(retryAfter, 10);
          }
          throw error;
        }

        if (response.status === 403) {
          throw new CreditsError("Access forbidden.", 403, "FORBIDDEN");
        }

        if (response.status >= 500) {
          throw new CreditsError("Server error. Please try again later.", response.status, "SERVER_ERROR");
        }

        // Try to parse error message from response
        try {
          const errorData = await response.json();
          throw new CreditsError(
            errorData?.error?.message || errorData?.message || `Request failed with status ${response.status}`,
            response.status,
            errorData?.error?.code
          );
        } catch {
          throw new CreditsError(`Request failed with status ${response.status}`, response.status);
        }
      }

      const data = await response.json();

      // Validate and return the response
      return {
        balance: typeof data.balance === "number" ? data.balance : 0,
        formatted_balance: data.formatted_balance || formatCurrency(data.balance || 0),
        total_purchased: typeof data.total_purchased === "number" ? data.total_purchased : 0,
        total_spent: typeof data.total_spent === "number" ? data.total_spent : 0,
        has_low_balance: Boolean(data.has_low_balance),
        low_balance_threshold: typeof data.low_balance_threshold === "number" ? data.low_balance_threshold : 2.0,
        currency: data.currency || "USD",
      };
    } catch (error) {
      lastError = error as Error;

      // Don't retry on client errors (4xx) except 429
      if (error instanceof CreditsError && error.status && error.status < 500 && error.status !== 429) {
        throw error;
      }

      // Retry on server errors or network errors
      if (attempt < retries) {
        await sleep(retryDelay * Math.pow(2, attempt)); // Exponential backoff
        attempt++;
        continue;
      }

      // Handle network errors
      if (error instanceof TypeError && error.message.includes("fetch")) {
        throw new CreditsError("Network error. Please check your connection.", 0, "NETWORK_ERROR");
      }

      throw error;
    }

    attempt++;
  }

  throw lastError || new CreditsError("Failed to fetch balance");
}

// ==================== useBalance Hook ====================

export interface UseBalanceReturn {
  balance: BalanceResponse | null;
  isLoading: boolean;
  isValidating: boolean;
  error: Error | null;
  mutate: (
    data?: BalanceResponse | Promise<BalanceResponse> | MutatorCallback<BalanceResponse>,
    options?: { revalidate?: boolean }
  ) => Promise<BalanceResponse | undefined>;
  refresh: () => Promise<void>;
}

/**
 * Hook to fetch and cache user credit balance
 */
export function useBalance(): UseBalanceReturn {
  const { data, isLoading, isValidating, error, mutate } = useSWR<BalanceResponse, Error>(
    BALANCE_ENDPOINT,
    () => fetchBalance(),
    {
      revalidateOnFocus: true,
      revalidateOnReconnect: true,
      shouldRetryOnError: false, // We handle retries in fetchBalance
      dedupingInterval: 5000, // Dedupe requests within 5 seconds
      errorRetryCount: 0,
    }
  );

  const refresh = async () => {
    await mutate();
  };

  return {
    balance: data || null,
    isLoading,
    isValidating,
    error: error || null,
    mutate,
    refresh,
  };
}

// ==================== invalidateBalance ====================

export interface InvalidateOptions {
  optimisticData?: Partial<BalanceResponse>;
}

/**
 * Invalidate the balance cache and optionally update with optimistic data
 */
export async function invalidateBalance(
  mutate: UseBalanceReturn["mutate"],
  options: InvalidateOptions = {}
): Promise<void> {
  try {
    if (options.optimisticData) {
      // Optimistic update
      await mutate(
        (current) => {
          if (!current) return current;
          return {
            ...current,
            ...options.optimisticData,
          };
        },
        { revalidate: true }
      );
    } else {
      // Just revalidate
      await mutate();
    }
  } catch (error) {
    console.error("Failed to invalidate balance:", error);
  }
}

// ==================== Helpers ====================

/**
 * Format a number as currency
 */
export function formatCurrency(
  amount: number,
  currency: string = "USD",
  locale: string = "en-US"
): string {
  return new Intl.NumberFormat(locale, {
    style: "currency",
    currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
}

/**
 * Check if balance is low
 */
export function isLowBalance(balance: number, threshold: number = 2.0): boolean {
  return balance < threshold;
}

/**
 * Sleep utility for retry delays
 */
function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

// ==================== Local Storage ====================

const BALANCE_STORAGE_KEY = "seoindicator_balance_cache";

/**
 * Cache balance to localStorage for offline access
 */
export function cacheBalance(balance: BalanceResponse): void {
  if (typeof window === "undefined") return;

  try {
    localStorage.setItem(
      BALANCE_STORAGE_KEY,
      JSON.stringify({
        ...balance,
        cachedAt: Date.now(),
      })
    );
  } catch {
    // Ignore storage errors
  }
}

/**
 * Get cached balance from localStorage
 */
export function getCachedBalance(): (BalanceResponse & { cachedAt: number }) | null {
  if (typeof window === "undefined") return null;

  try {
    const cached = localStorage.getItem(BALANCE_STORAGE_KEY);
    if (cached) {
      return JSON.parse(cached);
    }
  } catch {
    // Ignore storage errors
  }

  return null;
}

/**
 * Clear cached balance
 */
export function clearCachedBalance(): void {
  if (typeof window === "undefined") return;

  try {
    localStorage.removeItem(BALANCE_STORAGE_KEY);
  } catch {
    // Ignore storage errors
  }
}

// Export default balance for fallback
export { DEFAULT_BALANCE };
