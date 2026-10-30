/**
 * ErrorBoundary Component - TASK-28-11
 *
 * React error boundary with retry functionality.
 * Catches errors gracefully and provides retry option.
 *
 * @see TESTS/TEST-28-11.md
 */

"use client";

import * as React from "react";
import { AlertTriangle, RefreshCw } from "lucide-react";
import { cn } from "@/lib/utils";

/**
 * ErrorBoundary Props
 */
export interface ErrorBoundaryProps {
  /** Child components to render */
  children: React.ReactNode;
  /** Custom fallback component */
  fallback?: React.ComponentType<{ error: Error; retry: () => void; retryCount: number }>;
  /** Callback when error is caught */
  onError?: (error: Error, errorInfo: React.ErrorInfo) => void;
  /** Maximum number of retry attempts */
  maxRetries?: number;
  /** Custom CSS class */
  className?: string;
}

/**
 * ErrorBoundary State
 */
interface ErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
  retryCount: number;
}

/**
 * Default error fallback component
 */
function DefaultErrorFallback({
  error,
  retry,
  retryCount,
  maxRetries = 3,
  className,
}: {
  error: Error;
  retry: () => void;
  retryCount: number;
  maxRetries?: number;
  className?: string;
}) {
  const canRetry = retryCount < maxRetries;

  return (
    <div
      data-testid="error-boundary"
      role="alert"
      className={cn(
        "flex flex-col items-center justify-center p-8 rounded-lg bg-surface-2 border border-error/30",
        className
      )}
    >
      <AlertTriangle className="h-12 w-12 text-error mb-4" aria-hidden="true" />
      <h3 className="text-lg font-semibold text-white mb-2">
        Error Loading Content
      </h3>
      <p className="text-sm text-slate-400 mb-4 text-center max-w-md">
        {error.message || "An unexpected error occurred. Please try again."}
      </p>
      
      {/* Retry count display */}
      {retryCount > 0 && (
        <p className="text-xs text-slate-500 mb-4">
          Retry attempt: {retryCount} of {maxRetries}
        </p>
      )}

      {/* Retry button */}
      <button
        data-testid="retry-button"
        onClick={retry}
        disabled={!canRetry}
        className={cn(
          "flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors",
          canRetry
            ? "bg-error text-white hover:bg-error/90"
            : "bg-slate-700 text-slate-500 cursor-not-allowed"
        )}
      >
        <RefreshCw className="h-4 w-4" aria-hidden="true" />
        {canRetry ? "Try Again" : "Max Retries Reached"}
      </button>
    </div>
  );
}

/**
 * ErrorBoundary Component
 *
 * Catches JavaScript errors anywhere in the child component tree,
 * logs those errors, and displays a fallback UI with retry functionality.
 *
 * @example
 * ```tsx
 * <ErrorBoundary maxRetries={3}>
 *   <MyComponent />
 * </ErrorBoundary>
 * ```
 */
export class ErrorBoundary extends React.Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      retryCount: 0,
    };
  }

  static getDerivedStateFromError(error: Error): Partial<ErrorBoundaryState> {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo): void {
    this.props.onError?.(error, errorInfo);
  }

  handleRetry = (): void => {
    const { maxRetries = 3 } = this.props;
    const { retryCount } = this.state;

    if (retryCount < maxRetries) {
      this.setState((prev) => ({
        hasError: false,
        error: null,
        retryCount: prev.retryCount + 1,
      }));
    }
  };

  render(): React.ReactNode {
    const { children, fallback, maxRetries = 3, className } = this.props;
    const { hasError, error, retryCount } = this.state;

    if (hasError && error) {
      if (fallback) {
        return React.createElement(fallback, {
          error,
          retry: this.handleRetry,
          retryCount,
        });
      }

      return (
        <DefaultErrorFallback
          error={error}
          retry={this.handleRetry}
          retryCount={retryCount}
          maxRetries={maxRetries}
          className={className}
        />
      );
    }

    return children;
  }
}

/**
 * useErrorBoundary hook for functional components
 */
export function useErrorBoundary() {
  const [, setError] = React.useState<Error | null>(null);

  const showBoundary = React.useCallback((error: Error) => {
    setError(() => {
      throw error;
    });
  }, []);

  return { showBoundary };
}
