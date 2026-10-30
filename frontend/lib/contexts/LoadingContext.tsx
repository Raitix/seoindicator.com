/**
 * LoadingStateProvider - TASK-28-11
 *
 * React context provider for loading states across the application.
 * Provides centralized loading state management.
 *
 * @see TESTS/TEST-28-11.md
 */

"use client";

import * as React from "react";

/**
 * Loading context value
 */
interface LoadingContextValue {
  /** Global loading state */
  isLoading: boolean;
  /** Set global loading state */
  setLoading: (loading: boolean) => void;
  /** Page-level loading state */
  isPageLoading: boolean;
  /** Set page-level loading state */
  setPageLoading: (loading: boolean) => void;
  /** Current loading message */
  loadingMessage: string | null;
  /** Set loading message */
  setLoadingMessage: (message: string | null) => void;
}

const LoadingContext = React.createContext<LoadingContextValue | undefined>(undefined);

/**
 * LoadingStateProvider Props
 */
interface LoadingStateProviderProps {
  children: React.ReactNode;
}

/**
 * LoadingStateProvider Component
 *
 * Provides loading state context to child components.
 *
 * @example
 * ```tsx
 * <LoadingStateProvider>
 *   <App />
 * </LoadingStateProvider>
 * ```
 */
export function LoadingStateProvider({ children }: LoadingStateProviderProps) {
  const [isLoading, setIsLoading] = React.useState(false);
  const [isPageLoading, setIsPageLoading] = React.useState(false);
  const [loadingMessage, setLoadingMessage] = React.useState<string | null>(null);

  const setLoading = React.useCallback((loading: boolean) => {
    setIsLoading(loading);
    if (!loading) {
      setLoadingMessage(null);
    }
  }, []);

  const setPageLoading = React.useCallback((loading: boolean) => {
    setIsPageLoading(loading);
  }, []);

  const value = React.useMemo(
    () => ({
      isLoading,
      setLoading,
      isPageLoading,
      setPageLoading,
      loadingMessage,
      setLoadingMessage,
    }),
    [isLoading, setLoading, isPageLoading, setPageLoading, loadingMessage]
  );

  return (
    <LoadingContext.Provider value={value}>
      {children}
    </LoadingContext.Provider>
  );
}

/**
 * useLoadingContext hook
 *
 * Access loading state from context.
 *
 * @example
 * ```tsx
 * const { isLoading, setLoading, loadingMessage } = useLoadingContext();
 * ```
 */
export function useLoadingContext(): LoadingContextValue {
  const context = React.useContext(LoadingContext);
  if (!context) {
    throw new Error("useLoadingContext must be used within a LoadingStateProvider");
  }
  return context;
}
