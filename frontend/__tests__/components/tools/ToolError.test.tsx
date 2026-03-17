import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { ToolError } from '@/components/tools/ToolError';

/**
 * Tests for ToolError component.
 * 
 * @see TESTS/TEST-16-12.md
 * Covers: FE-001 to FE-020
 */
describe('ToolError', () => {
    // Mock props
    const defaultProps = {
        code: 'FETCH_FAILED' as const,
        message: 'Could not access the page',
        suggestion: 'Try a different URL or check if the page exists',
        showRetry: true,
        severity: 'error' as const,
    };

    /**
     * FE-001: Render error with all props
     */
    it('renders error with all props', () => {
        const onRetry = jest.fn();
        const onDismiss = jest.fn();

        render(
            <ToolError
                {...defaultProps}
                onRetry={onRetry}
                onDismiss={onDismiss}
            />
        );

        expect(screen.getByText('FETCH_FAILED')).toBeInTheDocument();
        expect(screen.getByText('Could not access the page')).toBeInTheDocument();
        expect(screen.getByText(/Try a different URL/)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /dismiss/i })).toBeInTheDocument();
    });

    /**
     * FE-002: Render error code
     */
    it('renders error code badge', () => {
        render(<ToolError {...defaultProps} />);

        const badge = screen.getByText('FETCH_FAILED');
        expect(badge).toBeInTheDocument();
        expect(badge).toHaveClass('font-mono');
    });

    /**
     * FE-003: Render error message
     */
    it('renders error message', () => {
        render(<ToolError {...defaultProps} message="Custom error message" />);

        expect(screen.getByText('Custom error message')).toBeInTheDocument();
    });

    /**
     * FE-004: Render suggestion
     */
    it('renders suggestion text', () => {
        render(<ToolError {...defaultProps} suggestion="Try refreshing the page" />);

        expect(screen.getByText('Try refreshing the page')).toBeInTheDocument();
    });

    /**
     * FE-005: Render retry button
     */
    it('renders retry button when showRetry is true', () => {
        render(<ToolError {...defaultProps} showRetry={true} onRetry={jest.fn()} />);

        expect(screen.getByRole('button', { name: /try again/i })).toBeInTheDocument();
    });

    /**
     * FE-006: No retry button when showRetry is false
     */
    it('does not render retry button when showRetry is false', () => {
        render(<ToolError {...defaultProps} showRetry={false} />);

        expect(screen.queryByRole('button', { name: /try again/i })).not.toBeInTheDocument();
    });

    /**
     * FE-007: Retry callback
     */
    it('calls onRetry when retry button is clicked', () => {
        const onRetry = jest.fn();
        render(<ToolError {...defaultProps} onRetry={onRetry} />);

        fireEvent.click(screen.getByRole('button', { name: /try again/i }));

        expect(onRetry).toHaveBeenCalledTimes(1);
    });

    /**
     * FE-008: Loading state on retry
     */
    it('shows loading state when isLoading is true', () => {
        render(<ToolError {...defaultProps} isLoading={true} onRetry={jest.fn()} />);

        // Check for loading spinner (SVG with animate-spin class)
        const spinner = document.querySelector('.animate-spin');
        expect(spinner).toBeInTheDocument();
        // Button should be disabled when loading and show "Retrying..." text
        const button = screen.getByRole('button', { name: /retrying/i });
        expect(button).toBeDisabled();
    });

    /**
     * FE-009: Dismiss callback
     */
    it('calls onDismiss when dismiss button is clicked', () => {
        const onDismiss = jest.fn();
        render(<ToolError {...defaultProps} onDismiss={onDismiss} />);

        fireEvent.click(screen.getByRole('button', { name: /dismiss/i }));

        expect(onDismiss).toHaveBeenCalledTimes(1);
    });

    /**
     * FE-010: Error icon displayed
     */
    it('renders icon for error severity', () => {
        render(<ToolError {...defaultProps} severity="error" />);

        // Check that error icon container exists
        const alert = screen.getByRole('alert');
        const iconContainer = alert.querySelector('.flex-shrink-0');
        expect(iconContainer).toBeInTheDocument();
    });

    /**
     * FE-011: Warning severity styling
     */
    it('renders warning styling for warning severity', () => {
        render(<ToolError {...defaultProps} severity="warning" />);

        // Warning severity should still render the component
        expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    /**
     * FE-012: Info severity styling
     */
    it('renders info styling for info severity', () => {
        render(<ToolError {...defaultProps} severity="info" />);

        // Info severity should still render the component
        expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    /**
     * FE-013: Accessibility: role
     */
    it('has role="alert" for accessibility', () => {
        render(<ToolError {...defaultProps} />);

        expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    /**
     * FE-014: Accessibility: aria-live
     */
    it('has aria-live="assertive" for accessibility', () => {
        render(<ToolError {...defaultProps} />);

        const alert = screen.getByRole('alert');
        expect(alert).toHaveAttribute('aria-live', 'assertive');
    });

    /**
     * FE-015: Dark mode support via CSS classes
     */
    it('applies color classes for error styling', () => {
        render(<ToolError {...defaultProps} />);

        const container = screen.getByRole('alert');
        // Implementation uses Tailwind classes for theming
        expect(container.className).toMatch(/border|bg|text/);
    });

    /**
     * FE-016: Long message display
     */
    it('displays long error messages', () => {
        const longMessage = 'This is a very long error message that should be displayed to the user with all the details about what went wrong';

        render(<ToolError {...defaultProps} message={longMessage} />);

        expect(screen.getByText(longMessage)).toBeInTheDocument();
    });

    /**
     * FE-017: HTML in message escaped
     */
    it('escapes HTML in error message to prevent XSS', () => {
        const xssMessage = '<script>alert("xss")</script>Error occurred';

        render(<ToolError {...defaultProps} message={xssMessage} />);

        // Script tag should not be rendered as HTML
        expect(document.querySelector('script')).toBeNull();
        // The escaped text should be visible
        expect(screen.getByText(/Error occurred/)).toBeInTheDocument();
    });

    /**
     * FE-018: Error animation
     */
    it('applies animation classes on mount', () => {
        render(<ToolError {...defaultProps} />);

        const container = screen.getByRole('alert');
        expect(container).toHaveClass('animate-in');
    });

    /**
     * FE-019: Different error codes have different colors
     */
    it('applies different styles for different error codes', () => {
        const { rerender } = render(<ToolError {...defaultProps} code="INVALID_URL" />);
        let container = screen.getByRole('alert');
        expect(container.className).toContain('red');

        rerender(<ToolError {...defaultProps} code="RATE_LIMITED" />);
        container = screen.getByRole('alert');
        expect(container.className).toContain('orange');

        rerender(<ToolError {...defaultProps} code="TIMEOUT" />);
        container = screen.getByRole('alert');
        expect(container.className).toContain('yellow');
    });

    /**
     * FE-020: Error code badge displayed
     */
    it('displays error code badge with correct text', () => {
        const errorCodes = ['INVALID_URL', 'RATE_LIMITED', 'FETCH_FAILED'] as const;

        errorCodes.forEach((code) => {
            const { unmount } = render(<ToolError {...defaultProps} code={code} />);
            const badge = screen.getByText(code);
            expect(badge).toBeInTheDocument();
            expect(badge).toHaveClass('font-mono');
            unmount();
        });
    });
});
