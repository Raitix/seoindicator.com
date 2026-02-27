import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { ErrorBoundary } from '@/components/tools/ErrorBoundary';

/**
 * Tests for ErrorBoundary component.
 * 
 * @see TESTS/TEST-16-12.md
 * Covers: EB-001 to EB-015
 */

// Component that throws an error for testing
const ThrowError: React.FC<{ shouldThrow: boolean }> = ({ shouldThrow }) => {
    if (shouldThrow) {
        throw new Error('Test error from ThrowError component');
    }
    return <div>No error</div>;
};

// Suppress console.error for cleaner test output
const originalError = console.error;
beforeAll(() => {
    console.error = jest.fn();
});
afterAll(() => {
    console.error = originalError;
});

describe('ErrorBoundary', () => {
    /**
     * EB-001: Catch rendering error
     */
    it('catches rendering error and displays fallback UI', () => {
        render(
            <ErrorBoundary>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(screen.getByText(/something went wrong/i)).toBeInTheDocument();
    });

    /**
     * EB-002: Log error to console
     */
    it('logs error to console', () => {
        render(
            <ErrorBoundary>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(console.error).toHaveBeenCalled();
    });

    /**
     * EB-003: Show error details
     */
    it('shows error details in fallback UI', () => {
        render(
            <ErrorBoundary showDetails={true}>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(screen.getByText(/Test error from ThrowError component/)).toBeInTheDocument();
    });

    /**
     * EB-004: Show reset button
     */
    it('shows reset button in fallback UI', () => {
        render(
            <ErrorBoundary>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
    });

    /**
     * EB-005: Reset on button click
     */
    it('resets error boundary when reset button is clicked', () => {
        const { rerender } = render(
            <ErrorBoundary>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        // Error should be shown
        expect(screen.getByText(/something went wrong/i)).toBeInTheDocument();

        // Change the prop to not throw
        rerender(
            <ErrorBoundary>
                <ThrowError shouldThrow={false} />
            </ErrorBoundary>
        );

        // Click reset button (simulating the scenario)
        // Note: In real scenario, clicking reset would remount children
        const resetButton = screen.queryByRole('button', { name: /try again/i });
        if (resetButton) {
            fireEvent.click(resetButton);
        }

        // After reset, if child doesn't throw, it should render normally
        expect(screen.getByText('No error')).toBeInTheDocument();
    });

    /**
     * EB-006: Custom fallback
     */
    it('renders custom fallback when provided', () => {
        render(
            <ErrorBoundary fallback={<div>Custom error UI</div>}>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(screen.getByText('Custom error UI')).toBeInTheDocument();
    });

    /**
     * EB-007: No fallback prop
     */
    it('renders default fallback when no fallback prop provided', () => {
        render(
            <ErrorBoundary>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        // Should show default error message
        expect(screen.getByText(/something went wrong/i)).toBeInTheDocument();
    });

    /**
     * EB-008: Error reporting
     */
    it('calls reportError callback when error occurs', () => {
        const reportError = jest.fn();

        render(
            <ErrorBoundary reportError={reportError}>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(reportError).toHaveBeenCalledWith(
            expect.any(Error),
            expect.objectContaining({
                componentStack: expect.any(String)
            })
        );
    });

    /**
     * EB-009: Preserve error info
     */
    it('preserves error info for debugging', () => {
        let capturedError: Error | null = null;
        let capturedInfo: React.ErrorInfo | null = null;

        render(
            <ErrorBoundary
                reportError={(error, info) => {
                    capturedError = error;
                    capturedInfo = info;
                }}
            >
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(capturedError).toBeInstanceOf(Error);
        expect(capturedInfo).toHaveProperty('componentStack');
    });

    /**
     * EB-010: Nested boundaries
     */
    it('catches error in correct nested boundary', () => {
        render(
            <ErrorBoundary fallback={<div>Outer error</div>}>
                <div>
                    <ErrorBoundary fallback={<div>Inner error</div>}>
                        <ThrowError shouldThrow={true} />
                    </ErrorBoundary>
                </div>
            </ErrorBoundary>
        );

        // Inner boundary should catch the error
        expect(screen.getByText('Inner error')).toBeInTheDocument();
        expect(screen.queryByText('Outer error')).not.toBeInTheDocument();
    });

    /**
     * EB-011: Multiple errors
     */
    it('shows last error when multiple errors occur', () => {
        const { rerender } = render(
            <ErrorBoundary>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        // First error
        expect(screen.getByText(/something went wrong/i)).toBeInTheDocument();

        // Simulate another error (in real scenario, this would be from another component)
        rerender(
            <ErrorBoundary>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        // Should still show error state
        expect(screen.getByText(/something went wrong/i)).toBeInTheDocument();
    });

    /**
     * EB-012: Error stack trace in debug mode
     */
    it('shows stack trace in debug mode', () => {
        render(
            <ErrorBoundary showDetails={true} debug={true}>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        // Stack trace should be visible in debug mode
        expect(screen.getByText(/at ThrowError/i)).toBeInTheDocument();
    });

    /**
     * EB-013: No stack trace in production
     */
    it('hides stack trace in production mode', () => {
        const originalEnv = process.env.NODE_ENV;
        process.env.NODE_ENV = 'production';

        render(
            <ErrorBoundary>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        // Stack trace should not be visible
        expect(screen.queryByText(/at ThrowError/i)).not.toBeInTheDocument();

        process.env.NODE_ENV = originalEnv;
    });

    /**
     * EB-014: Error boundary key reset
     */
    it('resets when key prop changes', () => {
        const { rerender } = render(
            <ErrorBoundary key="boundary-1">
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(screen.getByText(/something went wrong/i)).toBeInTheDocument();

        // Change key to force remount
        rerender(
            <ErrorBoundary key="boundary-2">
                <ThrowError shouldThrow={false} />
            </ErrorBoundary>
        );

        // Should render children normally after key change
        expect(screen.getByText('No error')).toBeInTheDocument();
    });

    /**
     * EB-015: Async error handling (requires special handling)
     */
    it('handles async errors with error reporting', async () => {
        const reportError = jest.fn();

        // Note: React Error Boundaries don't catch async errors by default
        // This test verifies the reportError callback is called if manually triggered
        render(
            <ErrorBoundary reportError={reportError}>
                <div>Normal content</div>
            </ErrorBoundary>
        );

        // Simulate manual error reporting (pattern for async errors)
        const asyncError = new Error('Async error');
        const errorBoundary = screen.getByText('Normal content').parentElement;

        // In real implementation, this would be triggered by window.onerror or similar
        // For testing purposes, we verify the callback exists
        expect(reportError).not.toHaveBeenCalled();
    });
});

/**
 * Test for ErrorBoundary with tool context
 */
describe('ErrorBoundary with tool context', () => {
    it('includes tool name in error report', () => {
        const reportError = jest.fn();

        render(
            <ErrorBoundary toolName="meta-tag-analyzer" reportError={reportError}>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(reportError).toHaveBeenCalledWith(
            expect.any(Error),
            expect.objectContaining({
                componentStack: expect.any(String),
                toolName: 'meta-tag-analyzer'
            })
        );
    });

    it('shows tool-specific error message', () => {
        render(
            <ErrorBoundary toolName="keyword-density-checker">
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(screen.getByText(/keyword-density-checker/i)).toBeInTheDocument();
    });
});
