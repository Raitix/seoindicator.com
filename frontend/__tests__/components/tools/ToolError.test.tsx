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
        expect(badge).toHaveClass('error-badge');
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
    it('shows loading state when isRetrying is true', () => {
        render(<ToolError {...defaultProps} isRetrying={true} />);

        expect(screen.getByTestId('retry-spinner')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /try again/i })).toBeDisabled();
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
     * FE-010: Error icon by severity - error
     */
    it('renders error icon for error severity', () => {
        render(<ToolError {...defaultProps} severity="error" />);

        expect(screen.getByTestId('error-icon')).toBeInTheDocument();
    });

    /**
     * FE-011: Warning icon by severity
     */
    it('renders warning icon for warning severity', () => {
        render(<ToolError {...defaultProps} severity="warning" />);

        expect(screen.getByTestId('warning-icon')).toBeInTheDocument();
    });

    /**
     * FE-012: Info icon by severity
     */
    it('renders info icon for info severity', () => {
        render(<ToolError {...defaultProps} severity="info" />);

        expect(screen.getByTestId('info-icon')).toBeInTheDocument();
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
     * FE-015: Dark mode support
     */
    it('applies dark mode styles when theme is dark', () => {
        render(<ToolError {...defaultProps} theme="dark" />);

        const container = screen.getByRole('alert');
        expect(container).toHaveClass('dark');
    });

    /**
     * FE-016: Long message truncation
     */
    it('truncates long messages with ellipsis', () => {
        const longMessage = 'This is a very long error message that should be truncated because it exceeds the maximum display length for error messages in the UI component';

        render(<ToolError {...defaultProps} message={longMessage} />);

        const messageElement = screen.getByText(longMessage);
        expect(messageElement).toHaveClass('truncate');
    });

    /**
     * FE-017: HTML in message escaped
     */
    it('escapes HTML in error message to prevent XSS', () => {
        const xssMessage = '<script>alert("xss")</script>Error occurred';

        render(<ToolError {...defaultProps} message={xssMessage} />);

        // Script tag should not be rendered as HTML
        expect(screen.queryByText('alert("xss")')).not.toBeInTheDocument();
        expect(document.querySelector('script')).toBeNull();
    });

    /**
     * FE-018: Error animation
     */
    it('applies fade-in animation on mount', () => {
        render(<ToolError {...defaultProps} />);

        const container = screen.getByRole('alert');
        expect(container).toHaveClass('animate-fade-in');
    });

    /**
     * FE-019: Multiple errors
     */
    it('displays multiple errors in stacked layout', () => {
        const errors = [
            { code: 'FETCH_FAILED', message: 'First error' },
            { code: 'PARSE_ERROR', message: 'Second error' },
        ];

        render(<ToolError errors={errors} />);

        expect(screen.getByText('First error')).toBeInTheDocument();
        expect(screen.getByText('Second error')).toBeInTheDocument();
    });

    /**
     * FE-020: Error code badge color
     */
    it('applies color-coded badges based on error code', () => {
        const errorCodes = [
            { code: 'INVALID_URL', expectedClass: 'badge-warning' },
            { code: 'RATE_LIMITED', expectedClass: 'badge-info' },
            { code: 'FETCH_FAILED', expectedClass: 'badge-error' },
        ];

        errorCodes.forEach(({ code, expectedClass }) => {
            const { unmount } = render(<ToolError {...defaultProps} code={code as any} />);
            const badge = screen.getByText(code);
            expect(badge).toHaveClass(expectedClass);
            unmount();
        });
    });
});
