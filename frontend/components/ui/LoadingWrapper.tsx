/**
 * LoadingWrapper Component - TASK-28-11
 *
 * A reusable component that wraps content with loading states:
 * - Shows skeleton while loading
 * - Shows error boundary on error
 * - Shows content when loaded
 *
 * @see TESTS/TEST-28-11.md
 */

"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { LinearProgress } from "@/components/ui/LinearProgress";
import { ErrorBoundary } from "@/components/ui/ErrorBoundary";
import { EmptyState } from "@/components/ui/EmptyState";

/**
 * LoadingWrapper Props
 */
export interface LoadingWrapperProps {
  /** Whether content is loading */
  isLoading: boolean;
  /** Error object if fetch failed */
  error?: Error | null;
  /** Retry function */
  onRetry?: () => void;
  /** Skeleton component to show while loading */
  skeleton?: React.ReactNode;
  /** Show linear progress bar at top */
  showProgress?: boolean;
  /** Progress value (0-100) for determinate mode */
  progressValue?: number;
  /** Empty state type or config */
  emptyState?: React.ReactNode | {
    type?: "keywords" | "competitors" | "reports" | "analyses" | "projects" | "custom";
    title?: string;
    description?: string;
    onCtaClick?: () => void;
  };
  /** Whether data is empty */
  isEmpty?: boolean;
  /** Children to render when loaded */
  children: React.ReactNode;
  /** Additional CSS classes */
  className?: string;
  /** Retry count for error state */
  retryCount?: number;
  /** Maximum retries */
  maxRetries?: number;
}

/**
 * LoadingWrapper Component
 *
 * A unified loading state wrapper that handles:
 * - Loading skeleton display
 * - Linear progress indicator
 * - Error display with retry
 * - Empty state display
 * - Content display
 *
 * @example
 * ```tsx
 * <LoadingWrapper
 *   isLoading={isLoading}
 *   error={error}
 *   onRetry={retry}
 *   skeleton={<IndicatorCatalogSkeleton count={8} />}
 *   showProgress
 * >
 *   <IndicatorCatalog indicators={data} />
 * </LoadingWrapper>
 * ```
 */
export function LoadingWrapper({
  isLoading,
  error,
  onRetry,
  skeleton,
  showProgress = true,
  progressValue,
  emptyState,
  isEmpty = false,
  children,
  className,
  retryCount = 0,
  maxRetries = 3,
}: LoadingWrapperProps) {
  // Show loading state
  if (isLoading) {
    return (
      <div
        data-testid="loading-wrapper-loading"
        className={cn("relative", className)}
        aria-busy="true"
        aria-label="Loading content"
      >
        {/* Linear progress bar */}
        {showProgress && (
          <div className="absolute top-0 left-0 right-0 z-10">
            <LinearProgress value={progressValue} />
          </div>
        )}
        
        {/* Skeleton content */}
        {skeleton && (
          <div className="pt-2">
            {skeleton}
          </div>
        )}
      </div>
    );
  }

  // Show error state
  if (error) {
    return (
      <div
        data-testid="loading-wrapper-error"
        className={className}
        role="alert"
      >
        <div className="flex flex-col items-center justify-center p-8 rounded-lg bg-surface-2 border border-error/30">
          <h3 className="text-lg font-semibold text-white mb-2">
            Error Loading Content
          </h3>
          <p className="text-sm text-slate-400 mb-4 text-center">
            {error.message || "An unexpected error occurred"}
          </p>
          
          {retryCount > 0 && (
            <p className="text-xs text-slate-500 mb-4">
              Retry attempt: {retryCount} of {maxRetries}
            </p>
          )}

          {onRetry && retryCount < maxRetries && (
            <button
              data-testid="retry-button"
              onClick={onRetry}
              className="flex items-center gap-2 px-4 py-2 bg-error text-white rounded-md hover:bg-error/90"
            >
              Try Again
            </button>
          )}
        </div>
      </div>
    );
  }

  // Show empty state
  if (isEmpty) {
    if (React.isValidElement(emptyState)) {
      return (
        <div data-testid="loading-wrapper-empty" className={className}>
          {emptyState}
        </div>
      );
    }

    if (typeof emptyState === "object" && emptyState !== null) {
      return (
        <div data-testid="loading-wrapper-empty" className={className}>
          <EmptyState {...emptyState} />
        </div>
      );
    }

    return (
      <div data-testid="loading-wrapper-empty" className={className}>
        <EmptyState type="custom" />
      </div>
    );
  }

  // Show content
  return (
    <div data-testid="loading-wrapper-content" className={className} role="status" aria-live="polite">
      {children}
    </div>
  );
}

/**
 * useLoadingWrapper hook
 *
 * Combines loading state management with the LoadingWrapper component.
 */
export function useLoadingWrapper<T>(
  fetchFn: () => Promise<T>,
  options: {
    initialData?: T | null;
    maxRetries?: number;
    onSuccess?: (data: T) => void;
    onError?: (error: Error) => void;
  } = {}
) {
  const { initialData = null, maxRetries = 3, onSuccess, onError } = options;

  const [data, setData] = React.useState<T | null>(initialData);
  const [isLoading, setIsLoading] = React.useState(false);
  const [error, setError] = React.useState<Error | null>(null);
  const [retryCount, setRetryCount] = React.useState(0);

  const execute = React.useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      const result = await fetchFn();
      setData(result);
      setRetryCount(0);
      onSuccess?.(result);
    } catch (err) {
      const error = err instanceof Error ? err : new Error(String(err));
      setError(error);
      setRetryCount((prev) => prev + 1);
      onError?.(error);
    } finally {
      setIsLoading(false);
    }
  }, [fetchFn, onSuccess, onError]);

  const retry = React.useCallback(() => {
    if (retryCount < maxRetries) {
      execute();
    }
  }, [execute, retryCount, maxRetries]);

  const reset = React.useCallback(() => {
    setData(null);
    setError(null);
    setRetryCount(0);
    setIsLoading(false);
  }, []);

  return {
    data,
    isLoading,
    error,
    retryCount,
    execute,
    retry,
    reset,
    canRetry: retryCount < maxRetries,
  };
}
