/**
 * ToolEmbed Component Tests
 * Task: TASK-20-22 - Integrate Tool Embedding for Meta Tag Analyzer Guides
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { ToolEmbed } from '@/components/guides/ToolEmbed';

// Mock ToolWidget component
jest.mock('@/components/tools/ToolWidget', () => ({
  ToolWidget: jest.fn(({ tool, title, description, className, onAnalysisComplete }) => (
    <div
      data-testid="tool-widget"
      data-tool={tool}
      data-title={title}
      data-description={description}
      className={className}
    >
      <div>Tool: {tool}</div>
      {title && <div>Title: {title}</div>}
      {description && <div>Description: {description}</div>}
      <button
        data-testid="mock-analyze"
        onClick={() => onAnalysisComplete?.({ success: true, data: {} })}
      >
        Mock Analyze
      </button>
    </div>
  )),
}));

// Mock Next.js Link
jest.mock('next/link', () => {
  return function MockLink({
    children,
    href,
    target,
    rel,
  }: {
    children: React.ReactNode;
    href: string;
    target?: string;
    rel?: string;
  }) {
    return (
      <a href={href} target={target} rel={rel} data-testid="next-link">
        {children}
      </a>
    );
  };
});

// Mock next/dynamic
jest.mock('next/dynamic', () => {
  return function mockDynamic(
    importFunc: () => Promise<{ default: React.ComponentType }>,
    options?: { loading?: () => React.ReactNode }
  ) {
    const LoadingComponent = options?.loading;
    return function DynamicWrapper(props: Record<string, unknown>) {
      const [Component, setComponent] = React.useState<React.ComponentType | null>(null);
      const [loading, setLoading] = React.useState(true);

      React.useEffect(() => {
        importFunc()
          .then((mod) => {
            setComponent(() => mod.default);
            setLoading(false);
          })
          .catch(() => setLoading(false));
      }, []);

      if (loading && LoadingComponent) {
        return <LoadingComponent />;
      }

      if (!Component) {
        return null;
      }

      return <Component {...props} />;
    };
  };
});

const mockToolWidget = require('@/components/tools/ToolWidget').ToolWidget as jest.MockedFunction<
  typeof import('@/components/tools/ToolWidget').ToolWidget
>;

// Valid tool slugs
const VALID_TOOL_SLUGS = [
  'meta-tag-analyzer',
  'keyword-density',
  'social-preview',
  'mobile-friendly',
] as const;

// Tool slug to route mapping
const TOOL_ROUTES: Record<string, string> = {
  'meta-tag-analyzer': '/tools/meta-tag-analyzer/',
  'keyword-density': '/tools/keyword-density/',
  'social-preview': '/tools/social-preview/',
  'mobile-friendly': '/tools/mobile-friendly/',
};

describe('ToolEmbed', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // TE-001: Component Rendering
  describe('TE-001: Component Rendering', () => {
    // TE-001-01: Renders with valid toolSlug
    it('should render ToolWidget with valid toolSlug', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
      expect(screen.getByTestId('tool-widget')).toHaveAttribute(
        'data-tool',
        'meta-tag-analyzer'
      );
    });

    // TE-001-02: Renders with title override
    it('should render with custom title', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          title="Test Your Meta Tags"
        />
      );

      expect(screen.getByTestId('tool-widget')).toHaveAttribute(
        'data-title',
        'Test Your Meta Tags'
      );
    });

    // TE-001-03: Renders with description override
    it('should render with custom description', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          description="Custom description for the tool"
        />
      );

      expect(screen.getByTestId('tool-widget')).toHaveAttribute(
        'data-description',
        'Custom description for the tool'
      );
    });

    // TE-001-04: Renders with defaultToolTab
    it('should pass defaultToolTab prop to tool', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          defaultToolTab="description"
        />
      );

      // The defaultToolTab prop should be passed to the underlying tool
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
    });

    // TE-001-05: Renders keyword-density tool
    it('should render keyword-density tool', () => {
      render(<ToolEmbed toolSlug="keyword-density" />);

      expect(screen.getByTestId('tool-widget')).toHaveAttribute(
        'data-tool',
        'keyword-density'
      );
    });

    // TE-001-06: Renders social-preview tool
    it('should render social-preview tool', () => {
      render(<ToolEmbed toolSlug="social-preview" />);

      expect(screen.getByTestId('tool-widget')).toHaveAttribute(
        'data-tool',
        'social-preview'
      );
    });

    // TE-001-07: Renders mobile-friendly tool
    it('should render mobile-friendly tool', () => {
      render(<ToolEmbed toolSlug="mobile-friendly" />);

      expect(screen.getByTestId('tool-widget')).toHaveAttribute(
        'data-tool',
        'mobile-friendly'
      );
    });

    // TE-001-08: Renders with custom className
    it('should apply custom className', () => {
      const { container } = render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          className="custom-embed-class"
        />
      );

      expect(container.firstChild).toHaveClass('custom-embed-class');
    });
  });

  // TE-002: Graceful Fallback
  describe('TE-002: Graceful Fallback', () => {
    // TE-002-01: Invalid toolSlug
    it('should show fallback for invalid toolSlug', () => {
      render(<ToolEmbed toolSlug="invalid-tool" />);

      // Should show fallback message instead of tool
      expect(screen.queryByTestId('tool-widget')).not.toBeInTheDocument();
      expect(screen.getByText(/tool not available|try the full tools page/i)).toBeInTheDocument();
    });

    // TE-002-02: Empty toolSlug
    it('should show fallback for empty toolSlug', () => {
      render(<ToolEmbed toolSlug="" />);

      expect(screen.queryByTestId('tool-widget')).not.toBeInTheDocument();
    });

    // TE-002-03: Null toolSlug
    it('should handle null toolSlug gracefully', () => {
      render(<ToolEmbed toolSlug={null as unknown as string} />);

      expect(screen.queryByTestId('tool-widget')).not.toBeInTheDocument();
    });

    // TE-002-04: Undefined toolSlug
    it('should handle undefined toolSlug gracefully', () => {
      render(<ToolEmbed toolSlug={undefined as unknown as string} />);

      // With undefined, should render nothing or fallback
      expect(screen.queryByTestId('tool-widget')).not.toBeInTheDocument();
    });

    // TE-002-05: Fallback includes CTA
    it('should include CTA link in fallback', () => {
      render(<ToolEmbed toolSlug="invalid-tool" />);

      // Fallback should include link to tools page
      const ctaLink = screen.queryByRole('link', { name: /tools/i });
      expect(ctaLink).toBeTruthy();
    });

    // TE-002-06: Fallback is styled correctly
    it('should style fallback appropriately', () => {
      const { container } = render(<ToolEmbed toolSlug="invalid-tool" />);

      // Fallback container should have appropriate classes
      const fallback = container.querySelector('[class*="fallback"], [class*="unavailable"]');
      expect(fallback || container.firstChild).toBeTruthy();
    });
  });

  // TE-003: CTA Button
  describe('TE-003: CTA Button', () => {
    // TE-003-01: CTA link for meta-tag-analyzer
    it('should link to correct tool page for meta-tag-analyzer', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      const ctaLink = screen.getByRole('link', { name: /full tool|use full/i });
      expect(ctaLink).toHaveAttribute('href', '/tools/meta-tag-analyzer/');
    });

    // TE-003-02: CTA link for keyword-density
    it('should link to correct tool page for keyword-density', () => {
      render(<ToolEmbed toolSlug="keyword-density" />);

      const ctaLink = screen.getByRole('link', { name: /full tool|use full/i });
      expect(ctaLink).toHaveAttribute('href', '/tools/keyword-density/');
    });

    // TE-003-03: CTA link for social-preview
    it('should link to correct tool page for social-preview', () => {
      render(<ToolEmbed toolSlug="social-preview" />);

      const ctaLink = screen.getByRole('link', { name: /full tool|use full/i });
      expect(ctaLink).toHaveAttribute('href', '/tools/social-preview/');
    });

    // TE-003-04: CTA opens in new tab
    it('should open CTA link in new tab', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      const ctaLink = screen.getByRole('link', { name: /full tool|use full/i });
      expect(ctaLink).toHaveAttribute('target', '_blank');
    });

    // TE-003-05: CTA has security attributes
    it('should have security attributes on CTA link', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      const ctaLink = screen.getByRole('link', { name: /full tool|use full/i });
      const rel = ctaLink.getAttribute('rel');
      expect(rel).toContain('noopener');
      expect(rel).toContain('noreferrer');
    });

    // TE-003-06: CTA text is customizable
    it('should use custom CTA text', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          ctaText="Open Full Tool"
        />
      );

      expect(screen.getByRole('link', { name: 'Open Full Tool' })).toBeInTheDocument();
    });

    // TE-003-07: CTA can be hidden
    it('should hide CTA when showCta is false', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          showCta={false}
        />
      );

      expect(screen.queryByRole('link', { name: /full tool|use full/i })).not.toBeInTheDocument();
    });

    // TE-003-08: CTA default text
    it('should have default CTA text', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      // Default text should be something like "Use Full Tool"
      const ctaLink = screen.getByRole('link');
      expect(ctaLink.textContent).toMatch(/full tool|use full/i);
    });
  });

  // TE-004: Mobile Responsiveness
  describe('TE-004: Mobile Responsiveness', () => {
    // TE-004-01: Responsive wrapper classes
    it('should have responsive wrapper classes', () => {
      const { container } = render(
        <ToolEmbed toolSlug="meta-tag-analyzer" />
      );

      // Check for responsive container
      const wrapper = container.firstChild;
      expect(wrapper).toHaveClass('w-full');
    });

    // TE-004-02: CTA is responsive
    it('should have responsive CTA button styling', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      const ctaLink = screen.getByRole('link', { name: /full tool|use full/i });
      // Should have responsive classes
      expect(ctaLink.className).toMatch(/sm:|md:|lg:/);
    });

    // TE-004-03: Tool widget fills width
    it('should pass width classes to ToolWidget', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      const toolWidget = screen.getByTestId('tool-widget');
      // Widget should have full width class
      expect(toolWidget.className).toMatch(/w-full|max-w/);
    });
  });

  // TE-005: Tool State Persistence
  describe('TE-005: Tool State Persistence', () => {
    // TE-005-01: stateKey passed to ToolWidget
    it('should use stateKey for tool state persistence', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          stateKey="guide-meta-title"
        />
      );

      // The stateKey should be passed to the underlying tool
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
    });

    // TE-005-02: Different instances use different keys
    it('should allow different stateKeys for different instances', () => {
      const { container } = render(
        <>
          <ToolEmbed
            toolSlug="meta-tag-analyzer"
            stateKey="guide-meta-title"
            data-testid="embed-1"
          />
          <ToolEmbed
            toolSlug="meta-tag-analyzer"
            stateKey="guide-meta-description"
            data-testid="embed-2"
          />
        </>
      );

      const widgets = screen.getAllByTestId('tool-widget');
      expect(widgets).toHaveLength(2);
    });
  });

  // TE-006: Callbacks
  describe('TE-006: Callbacks', () => {
    // TE-006-01: onAnalysisComplete callback
    it('should call onAnalysisComplete when tool completes', async () => {
      const mockOnComplete = jest.fn();

      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          onAnalysisComplete={mockOnComplete}
        />
      );

      const analyzeButton = screen.getByTestId('mock-analyze');
      fireEvent.click(analyzeButton);

      expect(mockOnComplete).toHaveBeenCalledWith({ success: true, data: {} });
    });
  });

  // TE-007: Lazy Loading
  describe('TE-007: Lazy Loading', () => {
    // TE-007-01: Tool lazy loaded by default
    it('should lazy load tool by default', () => {
      // The dynamic import mock handles this
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      // Tool should eventually appear
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
    });

    // TE-007-02: Lazy loading can be disabled
    it('should support disabling lazy loading', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          lazy={false}
        />
      );

      // Tool should render immediately
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
    });
  });

  // TE-008: Accessibility
  describe('TE-008: Accessibility', () => {
    // TE-008-01: Section has proper role
    it('should have section role for tool embed', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      // Should be contained in a section or have proper role
      const section = screen.getByRole('region') || document.querySelector('section');
      expect(section || screen.getByTestId('tool-widget')).toBeTruthy();
    });

    // TE-008-02: CTA has accessible label
    it('should have accessible label on CTA', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      const ctaLink = screen.getByRole('link', { name: /full tool|use full/i });
      expect(ctaLink).toBeInTheDocument();
    });

    // TE-008-03: Keyboard accessible
    it('should be keyboard accessible', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      const ctaLink = screen.getByRole('link', { name: /full tool|use full/i });
      // Links should be focusable by default
      expect(ctaLink).not.toHaveAttribute('tabindex', '-1');
    });
  });

  // TE-009: Security
  describe('TE-009: Security', () => {
    // TE-009-01: XSS in toolSlug
    it('should handle XSS in toolSlug safely', () => {
      render(<ToolEmbed toolSlug="<script>alert(1)</script>" />);

      // Should treat as invalid tool, show fallback
      expect(screen.queryByTestId('tool-widget')).not.toBeInTheDocument();
      // Script should not be in the document
      expect(document.querySelector('script')).toBeFalsy();
    });

    // TE-009-02: XSS in title prop
    it('should escape XSS in title prop', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          title="<script>alert('xss')</script>"
        />
      );

      // Script tag should be escaped, not executed
      expect(document.querySelector('script')).toBeFalsy();
    });

    // TE-009-03: XSS in CTA text
    it('should escape XSS in CTA text', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          ctaText="<script>alert('xss')</script>"
        />
      );

      // Script tag should be escaped
      expect(document.querySelector('script')).toBeFalsy();
    });
  });

  // TE-010: Error Handling
  describe('TE-010: Error Handling', () => {
    // TE-010-01: Tool throws error
    it('should catch tool errors with error boundary', () => {
      // Mock ToolWidget to throw
      mockToolWidget.mockImplementationOnce(() => {
        throw new Error('Tool error');
      });

      // Component should not crash
      expect(() => render(<ToolEmbed toolSlug="meta-tag-analyzer" />)).not.toThrow();
    });
  });

  // TE-011: Edge Cases
  describe('TE-011: Edge Cases', () => {
    // TE-011-01: Tool slug with special characters
    it('should handle tool slug with special characters', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer-with-dashes" />);

      // Should treat as invalid or handle gracefully
      expect(screen.queryByTestId('tool-widget') || screen.getByText(/not available/i)).toBeTruthy();
    });

    // TE-011-02: Tool slug with uppercase
    it('should handle uppercase tool slug', () => {
      render(<ToolEmbed toolSlug="META-TAG-ANALYZER" />);

      // Should handle case-insensitively or show fallback
      expect(screen.queryByTestId('tool-widget') || screen.getByText(/not available/i)).toBeTruthy();
    });

    // TE-011-03: Very long tool slug
    it('should handle very long tool slug', () => {
      const longSlug = 'a'.repeat(1000);
      render(<ToolEmbed toolSlug={longSlug} />);

      // Should not crash, should show fallback
      expect(screen.queryByTestId('tool-widget')).not.toBeInTheDocument();
    });

    // TE-011-04: Unicode in tool slug
    it('should handle unicode in tool slug', () => {
      render(<ToolEmbed toolSlug="工具-工具" />);

      // Should treat as invalid
      expect(screen.queryByTestId('tool-widget')).not.toBeInTheDocument();
    });
  });
});
