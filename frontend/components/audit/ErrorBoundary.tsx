/**
 * Error Boundary Component
 * 
 * Catches React render errors and displays a fallback UI.
 * Used to prevent the entire app from crashing when a component throws.
 */

'use client';

import * as React from 'react';
import { AlertCircle, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';

// ============================================================
// Types
// ============================================================

interface ErrorBoundaryProps {
  children: React.ReactNode;
  fallback?: React.ReactNode;
  onReset?: () => void;
}

interface ErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
}

// ============================================================
// Component
// ============================================================

export class ErrorBoundary extends React.Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    // Log the error to console for debugging
    console.error('ErrorBoundary caught an error:', error, errorInfo);
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null });
    this.props.onReset?.();
  };

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback;
      }

      return (
        <div className="min-h-screen bg-slate-950 flex items-center justify-center p-4">
          <div className="text-center max-w-md">
            <AlertCircle className="h-12 w-12 text-red-400 mx-auto mb-4" aria-hidden="true" />
            <h1 className="text-xl font-semibold text-white mb-2">Something went wrong</h1>
            <p className="text-slate-400 mb-4">
              An unexpected error occurred. Please try refreshing the page.
            </p>
            <div className="flex gap-3 justify-center">
              <Button onClick={() => window.location.reload()}>
                <RefreshCw className="h-4 w-4 mr-2" aria-hidden="true" />
                Refresh Page
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

// ============================================================
// HOC for wrapping components
// ============================================================

export function withErrorBoundary<P extends object>(
  WrappedComponent: React.ComponentType<P>,
  fallback?: React.ReactNode
) {
  const WithErrorBoundary = (props: P) => (
    <ErrorBoundary fallback={fallback}>
      <WrappedComponent {...props} />
    </ErrorBoundary>
  );

  WithErrorBoundary.displayName = `WithErrorBoundary(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

  return WithErrorBoundary;
}

export default ErrorBoundary;
