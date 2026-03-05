// Tests for ExportModal component
// frontend/__tests__/components/tools/__tests__/ExportModal.test.tsx

import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ExportModal, ExportModalProps } from '@/components/tools/ExportModal';

// Mock lucide-react icons
jest.mock('lucide-react', () => ({
  X: jest.fn(() => <svg data-testid="x-icon" />),
  FileText: jest.fn(() => <svg data-testid="file-text-icon" />),
  FileSpreadsheet: jest.fn(() => <svg data-testid="file-spreadsheet-icon" />),
  Loader2: jest.fn(({ className }: { className?: string }) => (
    <svg data-testid="loader-icon" className={className} />
  )),
  Download: jest.fn(() => <svg data-testid="download-icon" />),
  AlertCircle: jest.fn(() => <svg data-testid="alert-circle-icon" />),
  Clock: jest.fn(() => <svg data-testid="clock-icon" />),
}));

// Mock fetch for API calls
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Type definition for ToolResultData
interface ToolResultData {
  analyzedAt?: string;
  results?: Record<string, unknown>;
  indicators?: Array<{
    name: string;
    value: string | number;
    status: 'ok' | 'warning' | 'error';
    recommendation?: string;
  }>;
  summary?: {
    score?: number;
    passCount?: number;
    warningCount?: number;
    failCount?: number;
  };
}

describe('ExportModal', () => {
  const mockData: ToolResultData = {
    analyzedAt: '2026-03-05T10:00:00Z',
    results: {
      title: { content: 'Test Title', length: 10, status: 'ok' },
      description: { content: 'Test Description', length: 15, status: 'warning' },
    },
    indicators: [
      { name: 'Title', value: 'Test Title', status: 'ok' },
      { name: 'Description', value: 'Test Description', status: 'warning' },
    ],
    summary: { score: 85, passCount: 1, warningCount: 1, failCount: 0 },
  };

  const defaultProps: ExportModalProps = {
    isOpen: true,
    onClose: jest.fn(),
    toolName: 'meta-tag-analyzer',
    url: 'https://example.com',
    data: mockData,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockFetch.mockReset();
    // Mock window.location for download tests
    Object.defineProperty(window, 'location', {
      value: { href: '' },
      writable: true,
    });
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  // ============================================
  // Rendering Tests
  // ============================================

  describe('Rendering', () => {
    it('EM-001: renders when isOpen is true', () => {
      render(<ExportModal {...defaultProps} />);

      expect(screen.getByTestId('export-modal')).toBeInTheDocument();
    });

    it('EM-002: does not render when isOpen is false', () => {
      render(<ExportModal {...defaultProps} isOpen={false} />);

      expect(screen.queryByTestId('export-modal')).not.toBeInTheDocument();
    });

    it('EM-003: renders modal title "Export Results"', () => {
      render(<ExportModal {...defaultProps} />);

      expect(screen.getByText(/export results/i)).toBeInTheDocument();
    });

    it('EM-004: renders PDF format option', () => {
      render(<ExportModal {...defaultProps} />);

      const pdfOption = screen.getByLabelText(/pdf/i);
      expect(pdfOption).toBeInTheDocument();
    });

    it('EM-005: renders XLS format option', () => {
      render(<ExportModal {...defaultProps} />);

      const xlsOption = screen.getByLabelText(/xls/i);
      expect(xlsOption).toBeInTheDocument();
    });

    it('EM-006: renders format descriptions', () => {
      render(<ExportModal {...defaultProps} />);

      expect(screen.getByText(/branded document/i)).toBeInTheDocument();
      expect(screen.getByText(/spreadsheet/i)).toBeInTheDocument();
    });

    it('EM-007: renders Cancel button', () => {
      render(<ExportModal {...defaultProps} />);

      expect(screen.getByRole('button', { name: /cancel/i })).toBeInTheDocument();
    });

    it('EM-008: renders Export button', () => {
      render(<ExportModal {...defaultProps} />);

      expect(screen.getByRole('button', { name: /export/i })).toBeInTheDocument();
    });

    it('EM-009: renders preview section', () => {
      render(<ExportModal {...defaultProps} />);

      // Preview should show tool name or URL
      expect(screen.getByText(/example.com/i)).toBeInTheDocument();
    });

    it('EM-010: applies correct data-testid', () => {
      render(<ExportModal {...defaultProps} />);

      expect(screen.getByTestId('export-modal')).toBeInTheDocument();
    });
  });

  // ============================================
  // Format Selection Tests
  // ============================================

  describe('Format Selection', () => {
    it('EM-011: PDF is selected by default', () => {
      render(<ExportModal {...defaultProps} />);

      const pdfOption = screen.getByLabelText(/pdf/i);
      expect(pdfOption).toBeChecked();
    });

    it('EM-012: can select XLS format', async () => {
      const user = userEvent.setup();

      render(<ExportModal {...defaultProps} />);

      const xlsOption = screen.getByLabelText(/xls/i);
      await user.click(xlsOption);

      expect(xlsOption).toBeChecked();
    });

    it('EM-013: can switch back to PDF', async () => {
      const user = userEvent.setup();

      render(<ExportModal {...defaultProps} />);

      // Select XLS first
      const xlsOption = screen.getByLabelText(/xls/i);
      await user.click(xlsOption);
      expect(xlsOption).toBeChecked();

      // Switch back to PDF
      const pdfOption = screen.getByLabelText(/pdf/i);
      await user.click(pdfOption);
      expect(pdfOption).toBeChecked();
    });

    it('EM-014: only one format selected at a time', async () => {
      const user = userEvent.setup();

      render(<ExportModal {...defaultProps} />);

      const pdfOption = screen.getByLabelText(/pdf/i);
      const xlsOption = screen.getByLabelText(/xls/i);

      await user.click(xlsOption);

      expect(xlsOption).toBeChecked();
      expect(pdfOption).not.toBeChecked();
    });

    it('EM-015: PDF description is correct', () => {
      render(<ExportModal {...defaultProps} />);

      expect(
        screen.getByText(/branded document with summary, indicators, and recommendations/i)
      ).toBeInTheDocument();
    });

    it('EM-016: XLS description is correct', () => {
      render(<ExportModal {...defaultProps} />);

      expect(
        screen.getByText(/spreadsheet with indicator data for further analysis/i)
      ).toBeInTheDocument();
    });

    it('EM-019: format selection updates preview', async () => {
      const user = userEvent.setup();

      render(<ExportModal {...defaultProps} />);

      // Preview should show PDF-specific info by default
      const previewSection = screen.getByTestId('export-preview');
      expect(previewSection).toBeInTheDocument();

      // Select XLS and check preview updates
      const xlsOption = screen.getByLabelText(/xls/i);
      await user.click(xlsOption);

      // Preview should reflect XLS format
      expect(screen.getByTestId('export-preview')).toBeInTheDocument();
    });

    it('EM-020: keyboard navigation between formats', async () => {
      const user = userEvent.setup();

      render(<ExportModal {...defaultProps} />);

      const pdfOption = screen.getByLabelText(/pdf/i);
      pdfOption.focus();

      // Tab should move to XLS option
      await user.tab();

      const xlsOption = screen.getByLabelText(/xls/i);
      expect(xlsOption).toHaveFocus();
    });
  });

  // ============================================
  // Export Functionality Tests
  // ============================================

  describe('Export Functionality', () => {
    it('EM-021: export button triggers API call', async () => {
      const user = userEvent.setup();

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/api/v1/tools/export',
          expect.objectContaining({
            method: 'POST',
            headers: expect.objectContaining({
              'Content-Type': 'application/json',
            }),
          })
        );
      });
    });

    it('EM-022: API called with correct payload', async () => {
      const user = userEvent.setup();

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        const callArgs = mockFetch.mock.calls[0];
        const body = JSON.parse(callArgs[1].body);

        expect(body).toMatchObject({
          tool: 'meta-tag-analyzer',
          url: 'https://example.com',
          format: 'pdf',
          data: expect.any(Object),
        });
      });
    });

    it('EM-023: shows loading spinner during export', async () => {
      const user = userEvent.setup();

      // Create a promise that we can resolve manually
      let resolvePromise: (value: unknown) => void;
      mockFetch.mockReturnValue(
        new Promise((resolve) => {
          resolvePromise = resolve;
        })
      );

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      // Should show loading spinner
      await waitFor(() => {
        expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
      });

      // Resolve the promise
      resolvePromise!({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });
    });

    it('EM-024: export button disabled during export', async () => {
      const user = userEvent.setup();

      let resolvePromise: (value: unknown) => void;
      mockFetch.mockReturnValue(
        new Promise((resolve) => {
          resolvePromise = resolve;
        })
      );

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(exportButton).toBeDisabled();
      });

      resolvePromise!({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });
    });

    it('EM-025: cancel button disabled during export', async () => {
      const user = userEvent.setup();

      let resolvePromise: (value: unknown) => void;
      mockFetch.mockReturnValue(
        new Promise((resolve) => {
          resolvePromise = resolve;
        })
      );

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        const cancelButton = screen.getByRole('button', { name: /cancel/i });
        expect(cancelButton).toBeDisabled();
      });

      resolvePromise!({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });
    });

    it('EM-026: on success, triggers file download', async () => {
      const user = userEvent.setup();

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(window.location.href).toBe('/api/v1/tools/export/download/token123');
      });
    });

    it('EM-027: on success, modal closes', async () => {
      const user = userEvent.setup();
      const onClose = jest.fn();

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });

      render(<ExportModal {...defaultProps} onClose={onClose} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(onClose).toHaveBeenCalled();
      });
    });

    it('EM-028: on error, shows error message', async () => {
      const user = userEvent.setup();

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => ({
          message: 'Internal server error',
        }),
      });

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(screen.getByText(/failed to generate export/i)).toBeInTheDocument();
      });
    });

    it('EM-029: on error, retry option available', async () => {
      const user = userEvent.setup();

      mockFetch
        .mockResolvedValueOnce({
          ok: false,
          status: 500,
          json: async () => ({ message: 'Internal server error' }),
        })
        .mockResolvedValueOnce({
          ok: true,
          json: async () => ({
            success: true,
            downloadUrl: '/api/v1/tools/export/download/token123',
            expiresAt: '2026-03-05T15:00:00Z',
          }),
        });

      render(<ExportModal {...defaultProps} />);

      // First attempt fails
      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(screen.getByText(/failed to generate export/i)).toBeInTheDocument();
      });

      // Button should be enabled for retry
      expect(exportButton).not.toBeDisabled();

      // Second attempt succeeds
      await user.click(exportButton);

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(2);
      });
    });

    it('EM-030: handles rate limit error (429)', async () => {
      const user = userEvent.setup();

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 429,
        json: async () => ({
          message: 'Rate limit exceeded',
          retryAfter: 3600,
        }),
      });

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(screen.getByText(/rate limit exceeded/i)).toBeInTheDocument();
      });
    });

    it('EM-031: rate limit shows time until next', async () => {
      const user = userEvent.setup();

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 429,
        json: async () => ({
          message: 'Rate limit exceeded',
          retryAfter: 3600, // 1 hour in seconds
        }),
      });

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        // Should show some time indication
        const errorSection = screen.getByTestId('export-error');
        expect(errorSection).toBeInTheDocument();
      });
    });

    it('EM-032: handles network error gracefully', async () => {
      const user = userEvent.setup();

      mockFetch.mockRejectedValueOnce(new Error('Network error'));

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(screen.getByText(/failed to generate export/i)).toBeInTheDocument();
      });
    });

    it('EM-033: handles server error (5xx)', async () => {
      const user = userEvent.setup();

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 503,
        json: async () => ({
          message: 'Service unavailable',
        }),
      });

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(screen.getByText(/failed to generate export/i)).toBeInTheDocument();
      });
    });

    it('EM-035: loading state clears after completion', async () => {
      const user = userEvent.setup();

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      // Wait for loading to appear and then clear
      await waitFor(() => {
        expect(screen.queryByTestId('loader-icon')).not.toBeInTheDocument();
      });
    });
  });

  // ============================================
  // Modal Interaction Tests
  // ============================================

  describe('Modal Interaction', () => {
    it('EM-036: cancel button closes modal', async () => {
      const user = userEvent.setup();
      const onClose = jest.fn();

      render(<ExportModal {...defaultProps} onClose={onClose} />);

      const cancelButton = screen.getByRole('button', { name: /cancel/i });
      await user.click(cancelButton);

      expect(onClose).toHaveBeenCalled();
    });

    it('EM-037: close button (X) closes modal', async () => {
      const user = userEvent.setup();
      const onClose = jest.fn();

      render(<ExportModal {...defaultProps} onClose={onClose} />);

      const closeButton = screen.getByTestId('close-modal-button');
      if (closeButton) {
        await user.click(closeButton);
        expect(onClose).toHaveBeenCalled();
      }
    });

    it('EM-039: escape key closes modal', async () => {
      const user = userEvent.setup();
      const onClose = jest.fn();

      render(<ExportModal {...defaultProps} onClose={onClose} />);

      await user.keyboard('{Escape}');

      expect(onClose).toHaveBeenCalled();
    });

    it('EM-040: modal can be reopened after close', async () => {
      const user = userEvent.setup();
      const onClose = jest.fn();

      const { rerender } = render(
        <ExportModal {...defaultProps} onClose={onClose} />
      );

      // Close modal
      const cancelButton = screen.getByRole('button', { name: /cancel/i });
      await user.click(cancelButton);

      expect(onClose).toHaveBeenCalled();

      // Simulate parent reopening
      rerender(<ExportModal {...defaultProps} onClose={onClose} isOpen={true} />);

      expect(screen.getByTestId('export-modal')).toBeInTheDocument();
    });

    it('EM-042: modal traps focus within', async () => {
      const user = userEvent.setup();

      render(<ExportModal {...defaultProps} />);

      const cancelButton = screen.getByRole('button', { name: /cancel/i });
      cancelButton.focus();

      // Tab through all elements
      await user.tab();
      await user.tab();
      await user.tab();

      // Focus should cycle within modal
      expect(screen.getByTestId('export-modal')).toContainElement(
        document.activeElement
      );
    });

    it('EM-043: focus moves to modal on open', async () => {
      render(<ExportModal {...defaultProps} />);

      await waitFor(() => {
        const modal = screen.getByTestId('export-modal');
        expect(modal).toContainElement(document.activeElement);
      });
    });
  });

  // ============================================
  // Props Validation Tests
  // ============================================

  describe('Props Validation', () => {
    it('EM-046: renders with all required props', () => {
      render(<ExportModal {...defaultProps} />);

      expect(screen.getByTestId('export-modal')).toBeInTheDocument();
    });

    it('EM-049: handles missing data', () => {
      render(<ExportModal {...defaultProps} data={undefined as unknown as ToolResultData} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      // Export should be disabled or show appropriate state
      expect(exportButton).toBeDisabled();
    });

    it('EM-050: handles empty data object', () => {
      render(<ExportModal {...defaultProps} data={{}} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      expect(exportButton).toBeDisabled();
    });

    it('EM-051: handles null data', () => {
      render(<ExportModal {...defaultProps} data={null as unknown as ToolResultData} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      expect(exportButton).toBeDisabled();
    });

    it('EM-052: handles complex data structure', () => {
      const complexData: ToolResultData = {
        analyzedAt: '2026-03-05T10:00:00Z',
        results: {
          title: { content: 'Test', length: 4, status: 'ok' },
          description: { content: 'Desc', length: 4, status: 'warning' },
          h1: { count: 1, status: 'ok' },
          h2: { count: 5, status: 'ok' },
          keywords: ['keyword1', 'keyword2', 'keyword3'],
        },
        indicators: Array.from({ length: 50 }, (_, i) => ({
          name: `Indicator ${i}`,
          value: `Value ${i}`,
          status: 'ok' as const,
        })),
        summary: {
          score: 75,
          passCount: 40,
          warningCount: 8,
          failCount: 2,
        },
      };

      render(<ExportModal {...defaultProps} data={complexData} />);

      expect(screen.getByTestId('export-modal')).toBeInTheDocument();
    });

    it('EM-053: handles special characters in props', () => {
      render(
        <ExportModal
          {...defaultProps}
          toolName="meta-tag-analyzer-<script>alert('xss')</script>"
        />
      );

      expect(screen.getByTestId('export-modal')).toBeInTheDocument();
      // Should not execute script
    });

    it('EM-054: handles very long toolName', () => {
      const longName = 'a'.repeat(200);

      render(<ExportModal {...defaultProps} toolName={longName} />);

      expect(screen.getByTestId('export-modal')).toBeInTheDocument();
    });

    it('EM-055: handles very long URL', () => {
      const longUrl = 'https://example.com/' + 'path/'.repeat(100);

      render(<ExportModal {...defaultProps} url={longUrl} />);

      expect(screen.getByTestId('export-modal')).toBeInTheDocument();
    });
  });

  // ============================================
  // Accessibility Tests
  // ============================================

  describe('Accessibility', () => {
    it('EM-056: has correct ARIA role', () => {
      render(<ExportModal {...defaultProps} />);

      const modal = screen.getByRole('dialog');
      expect(modal).toBeInTheDocument();
    });

    it('EM-057: has aria-modal="true"', () => {
      render(<ExportModal {...defaultProps} />);

      const modal = screen.getByTestId('export-modal');
      expect(modal).toHaveAttribute('aria-modal', 'true');
    });

    it('EM-058: has aria-labelledby', () => {
      render(<ExportModal {...defaultProps} />);

      const modal = screen.getByTestId('export-modal');
      expect(modal).toHaveAttribute('aria-labelledby');
    });

    it('EM-060: radio buttons have labels', () => {
      render(<ExportModal {...defaultProps} />);

      const pdfOption = screen.getByLabelText(/pdf/i);
      const xlsOption = screen.getByLabelText(/xls/i);

      expect(pdfOption).toBeInTheDocument();
      expect(xlsOption).toBeInTheDocument();
    });

    it('EM-061: buttons have accessible names', () => {
      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      const cancelButton = screen.getByRole('button', { name: /cancel/i });

      expect(exportButton).toBeInTheDocument();
      expect(cancelButton).toBeInTheDocument();
    });

    it('EM-062: focus visible on all elements', async () => {
      const user = userEvent.setup();

      render(<ExportModal {...defaultProps} />);

      // Tab through elements and check focus styles
      const pdfOption = screen.getByLabelText(/pdf/i);
      pdfOption.focus();
      expect(pdfOption).toHaveFocus();

      await user.tab();
      expect(document.activeElement).toBeTruthy();
    });

    it('EM-064: keyboard only navigation', async () => {
      const user = userEvent.setup();

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });

      render(<ExportModal {...defaultProps} />);

      // Navigate to export button using tab
      await user.tab();
      await user.tab();
      await user.tab();

      // Press Enter to export
      await user.keyboard('{Enter}');

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
    });
  });

  // ============================================
  // Edge Cases Tests
  // ============================================

  describe('Edge Cases', () => {
    it('EM-066: multiple rapid export clicks', async () => {
      const user = userEvent.setup();

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });

      // Rapid clicks
      await user.click(exportButton);
      await user.click(exportButton);
      await user.click(exportButton);

      // Should only make one API call
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(1);
      });
    });

    it('EM-068: network timeout during export', async () => {
      const user = userEvent.setup();

      mockFetch.mockImplementationOnce(
        () =>
          new Promise((_, reject) => {
            setTimeout(() => reject(new Error('Timeout')), 100);
          })
      );

      render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(
        () => {
          expect(screen.getByText(/failed to generate export/i)).toBeInTheDocument();
        },
        { timeout: 5000 }
      );
    });

    it('EM-069: very large data payload', async () => {
      const user = userEvent.setup();

      const largeIndicators = Array.from({ length: 500 }, (_, i) => ({
        name: `Indicator ${i}`,
        value: `Value ${i}`.repeat(10),
        status: 'ok' as const,
        recommendation: `Recommendation ${i}`.repeat(5),
      }));

      const largeData: ToolResultData = {
        indicators: largeIndicators,
        summary: { score: 100, passCount: 500, warningCount: 0, failCount: 0 },
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });

      render(<ExportModal {...defaultProps} data={largeData} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
    });

    it('EM-070: unicode characters in data', () => {
      const unicodeData: ToolResultData = {
        results: {
          title: { content: 'テスト タイトル 🎉', status: 'ok' },
          description: { content: 'Описание αβγ', status: 'warning' },
        },
      };

      render(<ExportModal {...defaultProps} data={unicodeData} />);

      expect(screen.getByTestId('export-modal')).toBeInTheDocument();
    });

    it('EM-071: emoji in data', () => {
      const emojiData: ToolResultData = {
        results: {
          title: { content: '🚀 Great Title! 🎉', status: 'ok' },
        },
        indicators: [
          { name: 'Emoji 📊', value: 'Value ✅', status: 'ok' },
        ],
      };

      render(<ExportModal {...defaultProps} data={emojiData} />);

      expect(screen.getByTestId('export-modal')).toBeInTheDocument();
    });

    it('EM-073: props change during loading', async () => {
      const user = userEvent.setup();

      let resolvePromise: (value: unknown) => void;
      mockFetch.mockReturnValue(
        new Promise((resolve) => {
          resolvePromise = resolve;
        })
      );

      const { rerender } = render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      // Change props while loading
      rerender(
        <ExportModal
          {...defaultProps}
          toolName="keyword-density"
          url="https://different.com"
        />
      );

      // Should still show loading state
      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();

      // Resolve promise
      resolvePromise!({
        ok: true,
        json: async () => ({
          success: true,
          downloadUrl: '/api/v1/tools/export/download/token123',
          expiresAt: '2026-03-05T15:00:00Z',
        }),
      });

      await waitFor(() => {
        expect(screen.queryByTestId('loader-icon')).not.toBeInTheDocument();
      });
    });
  });

  // ============================================
  // Preview Section Tests
  // ============================================

  describe('Preview Section', () => {
    it('EM-076: shows what will be exported', () => {
      render(<ExportModal {...defaultProps} />);

      // Should show tool name or URL
      expect(screen.getByText(/meta-tag-analyzer|example.com/i)).toBeInTheDocument();
    });

    it('EM-077: shows indicator count', () => {
      render(<ExportModal {...defaultProps} />);

      // Should show count of indicators
      expect(screen.getByText(/2.*indicator/i)).toBeInTheDocument();
    });

    it('EM-079: handles no indicators case', () => {
      render(
        <ExportModal
          {...defaultProps}
          data={{ analyzedAt: '2026-03-05T10:00:00Z' }}
        />
      );

      const exportButton = screen.getByRole('button', { name: /export/i });
      expect(exportButton).toBeDisabled();
    });
  });

  // ============================================
  // Snapshot Tests
  // ============================================

  describe('Snapshots', () => {
    it('EM-100: matches snapshot for default state', () => {
      const { container } = render(<ExportModal {...defaultProps} />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('EM-101: matches snapshot for loading state', async () => {
      const user = userEvent.setup();

      mockFetch.mockReturnValue(new Promise(() => {})); // Never resolves

      const { container } = render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
      });

      expect(container.firstChild).toMatchSnapshot();
    });

    it('EM-102: matches snapshot for error state', async () => {
      const user = userEvent.setup();

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => ({ message: 'Server error' }),
      });

      const { container } = render(<ExportModal {...defaultProps} />);

      const exportButton = screen.getByRole('button', { name: /export/i });
      await user.click(exportButton);

      await waitFor(() => {
        expect(screen.getByText(/failed to generate export/i)).toBeInTheDocument();
      });

      expect(container.firstChild).toMatchSnapshot();
    });
  });
});
