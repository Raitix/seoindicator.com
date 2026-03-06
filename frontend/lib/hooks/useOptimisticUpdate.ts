/**
 * useOptimisticUpdate Hook - TASK-28-11
 *
 * Custom hook for managing optimistic updates with rollback capability.
 * Shows expected result before API response and reverts on failure.
 *
 * @see TESTS/TEST-28-11.md
 */

"use client";

import * as React from "react";

/**
 * OptimisticUpdateState interface
 */
export interface OptimisticUpdateState<T> {
  /** Current confirmed data */
  data: T | null;
  /** Optimistic data shown during pending operations */
  optimisticData: T | null;
  /** Whether an operation is in progress */
  isPending: boolean;
  /** Current error, if any */
  error: Error | null;
  /** Trigger an optimistic update */
  update: (newData: T) => void;
  /** Rollback to last confirmed data */
  rollback: () => void;
  /** Confirm the optimistic update (clear optimistic state) */
  confirm: () => void;
  /** Set data directly */
  setData: (data: T | null) => void;
}

/**
 * useOptimisticUpdate options
 */
export interface UseOptimisticUpdateOptions<T> {
  /** Initial data value */
  initialData?: T | null;
  /** Callback to execute when update is triggered */
  onUpdate?: (data: T) => Promise<void> | void;
  /** Callback to execute on successful update */
  onSuccess?: (data: T) => void;
  /** Callback to execute on failed update */
  onError?: (error: Error, data: T) => void;
}

/**
 * Hook for managing optimistic updates with rollback capability
 *
 * @example
 * ```tsx
 * const { data, optimisticData, isPending, update, rollback } = useOptimisticUpdate({
 *   initialData: { position: 5 },
 *   onUpdate: async (newData) => {
 *     await updatePosition(newData.position);
 *   }
 * });
 *
 * // Display optimistic value during pending operation
 * const displayValue = optimisticData?.position ?? data?.position;
 * ```
 */
export function useOptimisticUpdate<T>(
  options: UseOptimisticUpdateOptions<T> = {}
): OptimisticUpdateState<T> {
  const {
    initialData = null,
    onUpdate,
    onSuccess,
    onError,
  } = options;

  const [data, setDataState] = React.useState<T | null>(initialData);
  const [optimisticData, setOptimisticData] = React.useState<T | null>(null);
  const [isPending, setIsPending] = React.useState(false);
  const [error, setError] = React.useState<Error | null>(null);

  const update = React.useCallback((newData: T) => {
    // Set optimistic data immediately
    setOptimisticData(newData);
    setIsPending(true);
    setError(null);

    if (onUpdate) {
      Promise.resolve(onUpdate(newData))
        .then(() => {
          // Confirm update on success
          setDataState(newData);
          setOptimisticData(null);
          setIsPending(false);
          onSuccess?.(newData);
        })
        .catch((err) => {
          // Rollback on error
          setOptimisticData(null);
          setIsPending(false);
          const error = err instanceof Error ? err : new Error(String(err));
          setError(error);
          onError?.(error, newData);
        });
    }
  }, [onUpdate, onSuccess, onError]);

  const rollback = React.useCallback(() => {
    setOptimisticData(null);
    setIsPending(false);
    setError(null);
  }, []);

  const confirm = React.useCallback(() => {
    if (optimisticData) {
      setDataState(optimisticData);
      setOptimisticData(null);
      setIsPending(false);
    }
  }, [optimisticData]);

  const setData = React.useCallback((newData: T | null) => {
    setDataState(newData);
    setOptimisticData(null);
    setIsPending(false);
    setError(null);
  }, []);

  return {
    data,
    optimisticData,
    isPending,
    error,
    update,
    rollback,
    confirm,
    setData,
  };
}

/**
 * Hook return type for type inference
 */
export type UseOptimisticUpdateReturn<T> = ReturnType<typeof useOptimisticUpdate<T>>;
