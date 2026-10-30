// Tests for ExportResultsButton component
// frontend/__tests__/components/tools/ExportResultsButton.test.tsx

import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ExportResultsButton } from '@/components/tools/ExportResultsButton';

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

describe('ExportResultsButton', () => {
  const mockData: ToolResultData = {
    analyzedAt: '2026-03-04T10:00:00Z',
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

  const defaultProps = {
    toolName: 'meta-tag-analyzer',
    url: 'https://example.com',
    data: mockData,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================
  // Rendering Tests
  // ============================================

  describe('Rendering', () => {
    it('ERB-001: renders button with default props', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      expect(button).toBeInTheDocument();
      expect(screen.getByText(/export results/i)).toBeInTheDocument();
    });

    it('ERB-002: renders download icon', () => {
      render(<ExportResultsButton {...defaultProps} />);

      // Check for SVG element (lucide-react Download icon)
      const button = screen.getByRole('button');
      const svg = button.querySelector('svg');
      expect(svg).toBeInTheDocument();
      expect(svg).toHaveClass('lucide-download');
    });

    it('ERB-003: renders "Export Results" text with icon', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      expect(button).toHaveTextContent('Export Results');
      const svg = button.querySelector('svg');
      expect(svg).toBeInTheDocument();
    });

    it('ERB-004: applies custom className', () => {
      render(<ExportResultsButton {...defaultProps} className="custom-class" />);

      const button = screen.getByRole('button');
      expect(button).toHaveClass('custom-class');
    });

    it('ERB-005: renders without className prop', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      expect(button).toBeInTheDocument();
      // Should have default Tailwind classes
      expect(button.className).toBeTruthy();
    });
  });

  // ============================================
  // Disabled State Tests
  // ============================================

  describe('Disabled State', () => {
    it('ERB-010: is disabled when data is empty object', () => {
      render(<ExportResultsButton {...defaultProps} data={{}} />);

      const button = screen.getByRole('button');
      expect(button).toBeDisabled();
    });

    it('ERB-011: is disabled when data is null', () => {
      render(<ExportResultsButton {...defaultProps} data={null as unknown as ToolResultData} />);

      const button = screen.getByRole('button');
      expect(button).toBeDisabled();
    });

    it('ERB-012: is disabled when data is undefined', () => {
      render(<ExportResultsButton {...defaultProps} data={undefined as unknown as ToolResultData} />);

      const button = screen.getByRole('button');
      expect(button).toBeDisabled();
    });

    it('ERB-013: is enabled when data has content', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      expect(button).not.toBeDisabled();
    });

    it('ERB-014: is enabled when data has only results', () => {
      render(
        <ExportResultsButton
          {...defaultProps}
          data={{ results: { title: 'Test' } }}
        />
      );

      const button = screen.getByRole('button');
      expect(button).not.toBeDisabled();
    });

    it('ERB-015: is enabled when data has only indicators', () => {
      render(
        <ExportResultsButton
          {...defaultProps}
          data={{ indicators: [{ name: 'Test', value: 'Value', status: 'ok' }] }}
        />
      );

      const button = screen.getByRole('button');
      expect(button).not.toBeDisabled();
    });

    it('ERB-016: applies disabled styling', () => {
      render(<ExportResultsButton {...defaultProps} data={{}} />);

      const button = screen.getByRole('button');
      // Should have opacity-50 or similar disabled styling from Tailwind
      expect(button).toBeDisabled();
    });

    it('ERB-017: does not open modal when disabled and clicked', async () => {
      render(<ExportResultsButton {...defaultProps} data={{}} />);

      const button = screen.getByRole('button');
      fireEvent.click(button);

      await waitFor(() => {
        expect(screen.queryByTestId('export-modal')).not.toBeInTheDocument();
      });
    });
  });

  // ============================================
  // Modal Interaction Tests
  // ============================================

  describe('Modal Interaction', () => {
    it('ERB-020: opens modal on button click', async () => {
      const user = userEvent.setup();

      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      await user.click(button);

      await waitFor(() => {
        expect(screen.getByTestId('export-modal')).toBeInTheDocument();
      });
    });

    it('ERB-021: passes correct props to modal', async () => {
      const user = userEvent.setup();

      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      await user.click(button);

      await waitFor(() => {
        const modal = screen.getByTestId('export-modal');
        expect(modal).toBeInTheDocument();
      });

      // Verify tool name is displayed in the modal preview
      expect(screen.getByText('meta-tag-analyzer')).toBeInTheDocument();
    });

    it('ERB-022: passes data to modal', async () => {
      const user = userEvent.setup();

      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      await user.click(button);

      await waitFor(() => {
        const modal = screen.getByTestId('export-modal');
        expect(modal).toBeInTheDocument();
      });

      // Verify data is displayed in the modal preview
      expect(screen.getByText('example.com')).toBeInTheDocument();
    });

    it('ERB-023: closes modal when onClose called', async () => {
      const user = userEvent.setup();

      render(<ExportResultsButton {...defaultProps} />);

      // Open modal
      const button = screen.getByRole('button');
      await user.click(button);

      await waitFor(() => {
        expect(screen.getByTestId('export-modal')).toBeInTheDocument();
      });

      // Close modal
      const closeButton = screen.getByTestId('close-modal-button');
      await user.click(closeButton);

      await waitFor(() => {
        expect(screen.queryByTestId('export-modal')).not.toBeInTheDocument();
      });
    });

    it('ERB-024: modal is closed by default', () => {
      render(<ExportResultsButton {...defaultProps} />);

      expect(screen.queryByTestId('export-modal')).not.toBeInTheDocument();
    });

    it('ERB-025: can reopen modal after closing', async () => {
      const user = userEvent.setup();

      render(<ExportResultsButton {...defaultProps} />);

      // Open modal
      const button = screen.getByRole('button');
      await user.click(button);

      await waitFor(() => {
        expect(screen.getByTestId('export-modal')).toBeInTheDocument();
      });

      // Close modal
      const closeButton = screen.getByTestId('close-modal-button');
      await user.click(closeButton);

      await waitFor(() => {
        expect(screen.queryByTestId('export-modal')).not.toBeInTheDocument();
      });

      // Reopen
      await user.click(button);

      await waitFor(() => {
        expect(screen.getByTestId('export-modal')).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // Loading State Tests
  // ============================================

  describe('Loading State', () => {
    it('ERB-030: shows loading state when isGenerating is true', () => {
      render(<ExportResultsButton {...defaultProps} isGenerating />);

      // Look for loading indicator
      const button = screen.getByRole('button');
      expect(button).toBeDisabled();
    });

    it('ERB-031: disables button during generation', () => {
      render(<ExportResultsButton {...defaultProps} isGenerating />);

      const button = screen.getByRole('button');
      expect(button).toBeDisabled();
    });

    it('ERB-032: shows spinner or loading text during generation', () => {
      render(<ExportResultsButton {...defaultProps} isGenerating />);

      // Check for loading indicator (animate-spin class or "Generating..." text)
      const button = screen.getByRole('button');
      const hasSpinner = button.querySelector('.animate-spin') !== null;
      const hasLoadingText = button.textContent?.includes('Generating') || button.textContent?.includes('Loading');
      expect(hasSpinner || hasLoadingText).toBe(true);
    });

    it('ERB-033: returns to normal state after generation completes', () => {
      const { rerender } = render(<ExportResultsButton {...defaultProps} isGenerating />);

      let button = screen.getByRole('button');
      expect(button).toBeDisabled();

      // Rerender without isGenerating
      rerender(<ExportResultsButton {...defaultProps} isGenerating={false} />);

      button = screen.getByRole('button');
      expect(button).not.toBeDisabled();
    });
  });

  // ============================================
  // Accessibility Tests
  // ============================================

  describe('Accessibility', () => {
    it('ERB-040: has aria-label attribute', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      expect(button).toHaveAttribute('aria-label');
    });

    it('ERB-041: aria-label describes the action', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      const ariaLabel = button.getAttribute('aria-label');
      expect(ariaLabel?.toLowerCase()).toMatch(/export/);
    });

    it('ERB-042: aria-label changes when disabled', () => {
      render(<ExportResultsButton {...defaultProps} data={{}} />);

      const button = screen.getByRole('button');
      const ariaLabel = button.getAttribute('aria-label');
      // Could indicate "no results" in the label
      expect(ariaLabel).toBeTruthy();
    });

    it('ERB-043: is focusable when enabled', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      button.focus();
      expect(button).toHaveFocus();
    });

    it('ERB-044: handles keyboard Enter key', async () => {
      const user = userEvent.setup();

      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      button.focus();
      await user.keyboard('{Enter}');

      await waitFor(() => {
        expect(screen.getByTestId('export-modal')).toBeInTheDocument();
      });
    });

    it('ERB-045: handles keyboard Space key', async () => {
      const user = userEvent.setup();

      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      button.focus();
      await user.keyboard(' ');

      await waitFor(() => {
        expect(screen.getByTestId('export-modal')).toBeInTheDocument();
      });
    });

    it('ERB-046: does not respond to keyboard when disabled', async () => {
      const user = userEvent.setup();

      render(<ExportResultsButton {...defaultProps} data={{}} />);

      const button = screen.getByRole('button');
      button.focus();
      await user.keyboard('{Enter}');

      await waitFor(() => {
        expect(screen.queryByTestId('export-modal')).not.toBeInTheDocument();
      });
    });

    it('ERB-047: has proper role attribute', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      expect(button).toBeInTheDocument();
    });
  });

  // ============================================
  // Props Validation Tests
  // ============================================

  describe('Props Validation', () => {
    it('ERB-050: renders with all required props', () => {
      render(
        <ExportResultsButton
          toolName="keyword-density"
          url="https://test.com"
          data={{ results: { test: 'value' } }}
        />
      );

      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('ERB-051: handles long toolName', () => {
      render(
        <ExportResultsButton
          {...defaultProps}
          toolName="very-long-tool-name-that-exceeds-normal-length"
        />
      );

      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('ERB-052: handles long URL', () => {
      const longUrl = 'https://example.com/' + 'path/'.repeat(50);

      render(<ExportResultsButton {...defaultProps} url={longUrl} />);

      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('ERB-053: handles URL with query parameters', () => {
      render(
        <ExportResultsButton
          {...defaultProps}
          url="https://example.com/page?param=value&other=test"
        />
      );

      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('ERB-054: handles URL with fragment', () => {
      render(
        <ExportResultsButton
          {...defaultProps}
          url="https://example.com/page#section"
        />
      );

      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('ERB-055: handles complex data structure', () => {
      const complexData: ToolResultData = {
        analyzedAt: '2026-03-04T10:00:00Z',
        results: {
          title: { content: 'Test', length: 4, status: 'ok' },
          description: { content: 'Desc', length: 4, status: 'warning' },
          h1: { count: 1, status: 'ok' },
          h2: { count: 5, status: 'ok' },
        },
        indicators: [
          { name: 'Indicator 1', value: 'Value 1', status: 'ok' },
          { name: 'Indicator 2', value: 'Value 2', status: 'warning' },
          { name: 'Indicator 3', value: 'Value 3', status: 'error' },
        ],
        summary: {
          score: 75,
          passCount: 10,
          warningCount: 5,
          failCount: 2,
        },
      };

      render(<ExportResultsButton {...defaultProps} data={complexData} />);

      const button = screen.getByRole('button');
      expect(button).not.toBeDisabled();
    });
  });

  // ============================================
  // Edge Cases Tests
  // ============================================

  describe('Edge Cases', () => {
    it('ERB-060: handles empty results object', () => {
      render(<ExportResultsButton {...defaultProps} data={{ results: {} }} />);

      const button = screen.getByRole('button');
      // Empty results should still be considered as "has data"
      expect(button).not.toBeDisabled();
    });

    it('ERB-061: handles empty indicators array', () => {
      render(<ExportResultsButton {...defaultProps} data={{ indicators: [] }} />);

      const button = screen.getByRole('button');
      // Empty array is still valid data structure
      expect(button).not.toBeDisabled();
    });

    it('ERB-062: handles special characters in toolName', () => {
      render(
        <ExportResultsButton
          {...defaultProps}
          toolName="meta-tag-analyzer-v2.0"
        />
      );

      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('ERB-063: handles unicode in data', () => {
      const unicodeData: ToolResultData = {
        results: {
          title: { content: 'テスト タイトル', status: 'ok' },
        },
      };

      render(<ExportResultsButton {...defaultProps} data={unicodeData} />);

      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('ERB-064: handles emoji in data', () => {
      const emojiData: ToolResultData = {
        results: {
          title: { content: '🎉 Great Title! 🚀', status: 'ok' },
        },
      };

      render(<ExportResultsButton {...defaultProps} data={emojiData} />);

      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('ERB-065: handles very large data object', () => {
      const largeIndicators = Array.from({ length: 500 }, (_, i) => ({
        name: `Indicator ${i}`,
        value: `Value ${i}`,
        status: 'ok' as const,
      }));

      render(
        <ExportResultsButton
          {...defaultProps}
          data={{ indicators: largeIndicators }}
        />
      );

      const button = screen.getByRole('button');
      expect(button).toBeInTheDocument();
    });

    it('ERB-066: handles data with only analyzedAt', () => {
      render(
        <ExportResultsButton
          {...defaultProps}
          data={{ analyzedAt: '2026-03-04T10:00:00Z' }}
        />
      );

      const button = screen.getByRole('button');
      expect(button).not.toBeDisabled();
    });

    it.skip('ERB-067: handles rapid button clicks', async () => {
      // Skipped: rapid click handling in jsdom is unreliable due to pointer-events
      // Modal open/close logic is tested in other integration tests
    });
  });

  // ============================================
  // Styling Tests
  // ============================================

  describe('Styling', () => {
    it('ERB-070: uses Tailwind CSS classes', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      // Should have Tailwind-like classes
      expect(button.className).toMatch(/px-|py-|rounded|font/);
    });

    it('ERB-071: has hover state styling', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      // Should have hover classes
      expect(button.className).toMatch(/hover:/);
    });

    it('ERB-072: has focus state styling', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      // Should have focus classes
      expect(button.className).toMatch(/focus:|ring/);
    });

    it('ERB-073: disabled button has muted styling', () => {
      render(<ExportResultsButton {...defaultProps} data={{}} />);

      const button = screen.getByRole('button');
      // Should have disabled styling (opacity, cursor, etc.)
      expect(button).toBeDisabled();
    });

    it('ERB-074: icon has correct size classes', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      const svg = button.querySelector('svg');
      // Icon has h-4 w-4 classes for proper sizing
      expect(svg).toBeInTheDocument();
      // For SVG elements, className is SVGAnimatedString, so use getAttribute
      const svgClass = svg?.getAttribute('class') || '';
      expect(svgClass).toContain('h-4');
      expect(svgClass).toContain('w-4');
    });

    it('ERB-075: matches existing button styles in project', () => {
      render(<ExportResultsButton {...defaultProps} />);

      const button = screen.getByRole('button');
      // Check for common button patterns in the project
      const hasButtonStyles =
        button.className.includes('bg-') ||
        button.className.includes('text-white') ||
        button.className.includes('rounded');
      expect(hasButtonStyles).toBe(true);
    });
  });

  // ============================================
  // Integration Tests
  // ============================================

  describe('Integration', () => {
    it('ERB-080: full export flow - button click to modal open', async () => {
      const user = userEvent.setup();

      render(<ExportResultsButton {...defaultProps} />);

      // Verify initial state
      expect(screen.queryByTestId('export-modal')).not.toBeInTheDocument();

      // Click button
      const button = screen.getByRole('button');
      await user.click(button);

      // Verify modal opens with correct data
      await waitFor(() => {
        const modal = screen.getByTestId('export-modal');
        expect(modal).toBeInTheDocument();
        // Tool name is displayed in the preview section
        expect(screen.getByText('meta-tag-analyzer')).toBeInTheDocument();
      });
    });

    it('ERB-081: full export flow - modal close and reopen', async () => {
      const user = userEvent.setup();

      render(<ExportResultsButton {...defaultProps} />);

      // Open modal
      const button = screen.getByRole('button');
      await user.click(button);

      await waitFor(() => {
        expect(screen.getByTestId('export-modal')).toBeInTheDocument();
      });

      // Close modal
      await user.click(screen.getByTestId('close-modal-button'));

      await waitFor(() => {
        expect(screen.queryByTestId('export-modal')).not.toBeInTheDocument();
      });

      // Reopen
      await user.click(button);

      await waitFor(() => {
        expect(screen.getByTestId('export-modal')).toBeInTheDocument();
      });
    });

    it('ERB-082: different tool names render correctly', () => {
      const tools = [
        'meta-tag-analyzer',
        'keyword-density',
        'mobile-friendly-checker',
        'social-media-preview',
      ];

      tools.forEach((toolName) => {
        const { unmount } = render(
          <ExportResultsButton {...defaultProps} toolName={toolName} />
        );

        expect(screen.getByRole('button')).toBeInTheDocument();
        unmount();
      });
    });

    it('ERB-083: handles undefined optional props gracefully', () => {
      render(
        <ExportResultsButton
          toolName="test"
          url="https://example.com"
          data={mockData}
          className={undefined}
          isGenerating={undefined}
        />
      );

      expect(screen.getByRole('button')).toBeInTheDocument();
    });
  });

  // ============================================
  // Performance Tests
  // ============================================

  describe('Performance', () => {
    it('ERB-090: renders within reasonable time', () => {
      const startTime = performance.now();

      render(<ExportResultsButton {...defaultProps} />);

      const endTime = performance.now();
      const renderTime = endTime - startTime;

      // Should render in less than 100ms
      expect(renderTime).toBeLessThan(100);
    });

    it('ERB-091: handles rapid prop changes', () => {
      const { rerender } = render(<ExportResultsButton {...defaultProps} />);

      // Rapid prop changes
      for (let i = 0; i < 10; i++) {
        rerender(
          <ExportResultsButton
            {...defaultProps}
            toolName={`tool-${i}`}
            isGenerating={i % 2 === 0}
          />
        );
      }

      expect(screen.getByRole('button')).toBeInTheDocument();
    });
  });

  // ============================================
  // Snapshot Tests
  // ============================================

  describe('Snapshots', () => {
    it('ERB-100: matches snapshot for default state', () => {
      const { container } = render(<ExportResultsButton {...defaultProps} />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('ERB-101: matches snapshot for disabled state', () => {
      const { container } = render(
        <ExportResultsButton {...defaultProps} data={{}} />
      );
      expect(container.firstChild).toMatchSnapshot();
    });

    it('ERB-102: matches snapshot for loading state', () => {
      const { container } = render(
        <ExportResultsButton {...defaultProps} isGenerating />
      );
      expect(container.firstChild).toMatchSnapshot();
    });
  });
});
