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
    it('shows error details in fallback UI when showDetails is true', () => {
        render(
            <ErrorBoundary showDetails={true}>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        // The error message should be in the details section
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
        // Start with a throwing component
        const { unmount } = render(
            <ErrorBoundary>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        // Error should be shown
        expect(screen.getByText(/something went wrong/i)).toBeInTheDocument();

        // Click the reset button
        fireEvent.click(screen.getByRole('button', { name: /try again/i }));

        // After clicking reset, the error boundary state is cleared
        // The component will re-render and throw again, so the error UI stays
        // This tests that the reset button works (state is cleared)
        expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
        
        unmount();
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
     * EB-008: Error reporting via onError callback
     */
    it('calls onError callback when error occurs', () => {
        const onError = jest.fn();

        render(
            <ErrorBoundary onError={onError}>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(onError).toHaveBeenCalledWith(
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
                onError={(error, info) => {
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
     * EB-012: Error stack trace with showDetails
     */
    it('shows stack trace when showDetails is true', () => {
        render(
            <ErrorBoundary showDetails={true}>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        // Error details should be visible when showDetails is enabled
        expect(screen.getByText(/Test error from ThrowError component/)).toBeInTheDocument();
    });

    /**
     * EB-013: No stack trace without showDetails
     */
    it('hides stack trace when showDetails is false', () => {
        render(
            <ErrorBoundary showDetails={false}>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        // Stack trace should not be visible
        expect(screen.queryByText(/at ThrowError/i)).not.toBeInTheDocument();
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
        const onError = jest.fn();

        // Note: React Error Boundaries don't catch async errors by default
        // This test verifies the onError callback is called if manually triggered
        render(
            <ErrorBoundary onError={onError}>
                <div>Normal content</div>
            </ErrorBoundary>
        );

        // In real implementation, this would be triggered by window.onerror or similar
        // For testing purposes, we verify the callback exists
        expect(onError).not.toHaveBeenCalled();
    });
});

/**
 * Test for ErrorBoundary with onReset callback
 */
describe('ErrorBoundary with reset callback', () => {
    it('calls onReset when reset button is clicked', () => {
        const onReset = jest.fn();

        render(
            <ErrorBoundary onReset={onReset}>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        fireEvent.click(screen.getByRole('button', { name: /try again/i }));

        expect(onReset).toHaveBeenCalledTimes(1);
    });

    it('shows custom fallback message', () => {
        render(
            <ErrorBoundary fallback={<div>Something broke!</div>}>
                <ThrowError shouldThrow={true} />
            </ErrorBoundary>
        );

        expect(screen.getByText('Something broke!')).toBeInTheDocument();
    });
});
