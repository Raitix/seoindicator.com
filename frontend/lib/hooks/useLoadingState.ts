/**
 * useLoadingState Hook - TASK-28-11
 *
 * Custom hook for managing loading states with retry functionality.
 * Provides isLoading, error, retry, and retryCount state.
 *
 * @see TESTS/TEST-28-11.md
 */

"use client";

import * as React from "react";

/**
 * LoadingState interface
 */
export interface LoadingState {
  /** Whether data is currently being loaded */
  isLoading: boolean;
  /** Set loading state */
  setIsLoading: (loading: boolean) => void;
  /** Current error, if any */
  error: Error | null;
  /** Set error state */
  setError: (error: Error | null) => void;
  /** Retry the last operation */
  retry: () => void;
  /** Number of retry attempts */
  retryCount: number;
  /** Maximum number of retries allowed */
  maxRetries: number;
  /** Reset loading state to initial values */
  reset: () => void;
}

/**
 * useLoadingState options
 */
export interface UseLoadingStateOptions {
  /** Maximum number of retry attempts (default: 3) */
  maxRetries?: number;
  /** Initial loading state */
  initialLoading?: boolean;
  /** Callback to execute on retry */
  onRetry?: () => Promise<void> | void;
}

/**
 * Hook for managing loading states with retry functionality
 *
 * @example
 * ```tsx
 * const { isLoading, error, retry, retryCount } = useLoadingState({
 *   onRetry: async () => {
 *     const data = await fetchData();
 *     setData(data);
 *   }
 * });
 * ```
 */
export function useLoadingState(options: UseLoadingStateOptions = {}): LoadingState {
  const {
    maxRetries = 3,
    initialLoading = false,
    onRetry,
  } = options;

  const [isLoading, setIsLoadingState] = React.useState(initialLoading);
  const [error, setErrorState] = React.useState<Error | null>(null);
  const [retryCount, setRetryCount] = React.useState(0);

  const setIsLoading = React.useCallback((loading: boolean) => {
    setIsLoadingState(loading);
  }, []);

  const setError = React.useCallback((err: Error | null) => {
    setErrorState(err);
  }, []);

  const retry = React.useCallback(() => {
    if (retryCount >= maxRetries) {
      return;
    }

    setRetryCount((prev) => prev + 1);
    setErrorState(null);
    setIsLoadingState(true);

    if (onRetry) {
      Promise.resolve(onRetry())
        .then(() => {
          setIsLoadingState(false);
        })
        .catch((err) => {
          setErrorState(err instanceof Error ? err : new Error(String(err)));
          setIsLoadingState(false);
        });
    }
  }, [retryCount, maxRetries, onRetry]);

  const reset = React.useCallback(() => {
    setIsLoadingState(false);
    setErrorState(null);
    setRetryCount(0);
  }, []);

  return {
    isLoading,
    setIsLoading,
    error,
    setError,
    retry,
    retryCount,
    maxRetries,
    reset,
  };
}

/**
 * Hook return type for type inference
 */
export type UseLoadingStateReturn = ReturnType<typeof useLoadingState>;
