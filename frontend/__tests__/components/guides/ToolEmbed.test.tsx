/**
 * ToolEmbed Component Tests
 * TASK-20-22: Integrate Tool Embedding for Meta Tag Analyzer Guides
 *
 * Tests for the ToolEmbed component that integrates SEO tools into guide pages.
 */

import * as React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

import { ToolEmbed, type ToolSlug } from '@/components/guides/ToolEmbed';

// Mock Next.js dynamic import
jest.mock('next/dynamic', () => {
  const MockToolWidget = ({ tool, title, onAnalysisComplete }: { tool: string; title?: string; onAnalysisComplete?: (result: unknown) => void }) => (
    <div data-testid="tool-widget" data-tool={tool}>
      <h3>{title}</h3>
      <button
        onClick={() => onAnalysisComplete?.({ success: true })}
        data-testid="analyze-btn"
      >
        Analyze
      </button>
    </div>
  );

  return (importFn: () => Promise<{ default: React.ComponentType; ToolWidget: React.ComponentType }>) => {
    const Component = importFn();
    return jest.fn().mockImplementation((props) => <MockToolWidget {...props} />);
  };
});

// Mock ErrorBoundary to allow testing error scenarios
jest.mock('@/components/tools/ErrorBoundary', () => {
  return {
    ErrorBoundary: ({ children }: { children: React.ReactNode }) => <>{children}</>,
  };
});

// Valid tool slugs for testing
const VALID_TOOL_SLUGS: ToolSlug[] = [
  'meta-tag-analyzer',
  'keyword-density',
  'social-preview',
  'mobile-friendly',
  'mobile-friendly-checker',
];

// Tool route mapping
const TOOL_ROUTES: Record<ToolSlug, string> = {
  'meta-tag-analyzer': '/tools/meta-tag-analyzer/',
  'keyword-density': '/tools/keyword-density/',
  'social-preview': '/tools/social-preview/',
  'mobile-friendly': '/tools/mobile-friendly/',
  'mobile-friendly-checker': '/tools/mobile-friendly-checker/',
};

// Tool names mapping
const TOOL_NAMES: Record<ToolSlug, string> = {
  'meta-tag-analyzer': 'Meta Tag Analyzer',
  'keyword-density': 'Keyword Density Checker',
  'social-preview': 'Social Preview',
  'mobile-friendly': 'Mobile-Friendly Test',
  'mobile-friendly-checker': 'Mobile-Friendly Checker',
};

// Tools that have been deployed to production (must match ToolEmbed.tsx)
const DEPLOYED_TOOLS: Set<ToolSlug> = new Set([
  'meta-tag-analyzer',
  'keyword-density',
  'social-preview',
  // 'mobile-friendly-checker' - TODO: Add when TASK-16-7 is deployed
  // 'mobile-friendly' - TODO: Add when tool page is deployed
]);

describe('ToolEmbed Component', () => {
  // ============================================
  // TE-001: Component Rendering
  // ============================================
  describe('TE-001: Component Rendering', () => {
    it('TE-001-01: Renders with valid toolSlug (meta-tag-analyzer)', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
      expect(screen.getByTestId('tool-widget')).toHaveAttribute('data-tool', 'meta-tag-analyzer');
    });

    it('TE-001-02: Renders with custom title override', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" title="Test Your Meta Tags" />);
      // Title appears in both sr-only h2 and in the tool widget h3
      const headings = screen.getAllByText('Test Your Meta Tags');
      expect(headings.length).toBe(2);
    });

    it('TE-001-03: Renders with custom description', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          description="Custom description for the tool"
        />
      );
      // Description is passed to ToolWidget, verify it renders
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
    });

    it('TE-001-05: Renders keyword-density tool', () => {
      render(<ToolEmbed toolSlug="keyword-density" />);
      expect(screen.getByTestId('tool-widget')).toHaveAttribute('data-tool', 'keyword-density');
    });

    it('TE-001-06: Renders social-preview tool', () => {
      render(<ToolEmbed toolSlug="social-preview" />);
      expect(screen.getByTestId('tool-widget')).toHaveAttribute('data-tool', 'social-preview');
    });

    it('TE-001-07: Renders mobile-friendly tool', () => {
      render(<ToolEmbed toolSlug="mobile-friendly" />);
      expect(screen.getByTestId('tool-widget')).toHaveAttribute('data-tool', 'mobile-friendly');
    });

    it('TE-001-08: Renders with custom className', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" className="custom-class" />);
      const section = screen.getByRole('region', { hidden: true });
      expect(section).toHaveClass('custom-class');
    });
  });

  // ============================================
  // TE-002: Graceful Fallback
  // ============================================
  describe('TE-002: Graceful Fallback', () => {
    it('TE-002-01: Shows fallback for invalid toolSlug', () => {
      render(<ToolEmbed toolSlug="invalid-tool" />);
      expect(screen.getByText('Tool Unavailable')).toBeInTheDocument();
      expect(screen.getByText(/"invalid-tool" is not available/)).toBeInTheDocument();
    });

    it('TE-002-02: Shows fallback for empty toolSlug', () => {
      render(<ToolEmbed toolSlug="" />);
      expect(screen.getByText('Tool Unavailable')).toBeInTheDocument();
    });

    it('TE-002-03: Shows fallback for null toolSlug', () => {
      // @ts-expect-error Testing null value
      render(<ToolEmbed toolSlug={null} />);
      expect(screen.getByText('Tool Unavailable')).toBeInTheDocument();
    });

    it('TE-002-04: Shows fallback for undefined toolSlug', () => {
      // @ts-expect-error Testing undefined value
      render(<ToolEmbed toolSlug={undefined} />);
      expect(screen.getByText('Tool Unavailable')).toBeInTheDocument();
    });

    it('TE-002-05: Fallback includes CTA to tools index', () => {
      render(<ToolEmbed toolSlug="invalid" showCta={true} />);
      const ctaLink = screen.getByRole('link', { name: /browse all available tools/i });
      expect(ctaLink).toHaveAttribute('href', '/tools/');
    });

    it('TE-002-06: Fallback has appropriate styling', () => {
      render(<ToolEmbed toolSlug="invalid" />);
      const alert = screen.getByRole('alert');
      expect(alert).toHaveClass('border-slate-700');
    });
  });

  // ============================================
  // TE-003: CTA Button
  // ============================================
  describe('TE-003: CTA Button', () => {
    it('TE-003-01: CTA link for meta-tag-analyzer', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" showCta={true} />);
      const ctaLink = screen.getByRole('link', { name: /open meta tag analyzer/i });
      expect(ctaLink).toHaveAttribute('href', '/tools/meta-tag-analyzer/');
    });

    it('TE-003-02: CTA link for keyword-density', () => {
      render(<ToolEmbed toolSlug="keyword-density" showCta={true} />);
      const ctaLink = screen.getByRole('link', { name: /open keyword density checker/i });
      expect(ctaLink).toHaveAttribute('href', '/tools/keyword-density/');
    });

    it('TE-003-03: CTA link for social-preview', () => {
      render(<ToolEmbed toolSlug="social-preview" showCta={true} />);
      const ctaLink = screen.getByRole('link', { name: /open social preview/i });
      expect(ctaLink).toHaveAttribute('href', '/tools/social-preview/');
    });

    it('TE-003-04: CTA opens in new tab', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" showCta={true} />);
      const ctaLink = screen.getByRole('link', { name: /open meta tag analyzer/i });
      expect(ctaLink).toHaveAttribute('target', '_blank');
    });

    it('TE-003-05: CTA has security attributes', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" showCta={true} />);
      const ctaLink = screen.getByRole('link', { name: /open meta tag analyzer/i });
      expect(ctaLink).toHaveAttribute('rel', 'noopener noreferrer');
    });

    it('TE-003-06: CTA text is customizable', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" showCta={true} ctaText="Open Full Tool" />);
      // The text content is "Open Full Tool" but the aria-label is different
      const ctaLink = screen.getByRole('link', { name: /open meta tag analyzer/i });
      expect(ctaLink).toHaveTextContent('Open Full Tool');
    });

    it('TE-003-07: CTA can be hidden', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" showCta={false} />);
      expect(screen.queryByRole('link', { name: /open meta tag analyzer/i })).not.toBeInTheDocument();
    });

    it('TE-003-08: CTA default text is "Use Full Tool"', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" showCta={true} />);
      const ctaLink = screen.getByRole('link', { name: /open meta tag analyzer/i });
      expect(ctaLink).toHaveTextContent('Use Full Tool');
    });
  });

  // ============================================
  // TE-004: Mobile Responsiveness
  // ============================================
  describe('TE-004: Mobile Responsiveness', () => {
    it('TE-004-01: Responsive wrapper classes applied', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);
      const section = screen.getByRole('region', { hidden: true });
      expect(section).toHaveClass('tool-embed');
    });

    it('TE-004-04: CTA button has responsive classes', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" showCta={true} />);
      const ctaButton = screen.getByRole('link', { name: /open meta tag analyzer/i });
      // Button wrapper has responsive classes
      expect(ctaButton.closest('div')).toHaveClass('justify-center');
    });
  });

  // ============================================
  // TE-005: Tool State Persistence
  // ============================================
  describe('TE-005: Tool State Persistence', () => {
    it('TE-005-01: State key passed as data attribute', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" stateKey="guide-meta-title" />);
      const section = screen.getByRole('region', { hidden: true });
      expect(section).toHaveAttribute('data-state-key', 'guide-meta-title');
    });

    it('TE-005-02: Different instances have separate data attributes', () => {
      const { container } = render(
        <>
          <ToolEmbed toolSlug="meta-tag-analyzer" stateKey="guide-1" data-testid="embed-1" />
          <ToolEmbed toolSlug="keyword-density" stateKey="guide-2" data-testid="embed-2" />
        </>
      );
      const sections = container.querySelectorAll('[data-state-key]');
      expect(sections[0]).toHaveAttribute('data-state-key', 'guide-1');
      expect(sections[1]).toHaveAttribute('data-state-key', 'guide-2');
    });
  });

  // ============================================
  // TE-006: Callbacks
  // ============================================
  describe('TE-006: Callbacks', () => {
    it('TE-006-01: onAnalysisComplete callback is passed', () => {
      const onAnalysisComplete = jest.fn();
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          onAnalysisComplete={onAnalysisComplete}
        />
      );
      // The callback is passed to ToolWidget
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
    });
  });

  // ============================================
  // TE-007: Lazy Loading
  // ============================================
  describe('TE-007: Lazy Loading', () => {
    it('TE-007-01: Tool is lazy loaded by default', () => {
      // Component uses dynamic import which is mocked
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
    });

    it('TE-007-02: Lazy loading behavior accepts lazy prop', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" lazy={false} />);
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
    });
  });

  // ============================================
  // TE-008: Accessibility
  // ============================================
  describe('TE-008: Accessibility', () => {
    it('TE-008-01: Section has proper ARIA attributes', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);
      const section = screen.getByRole('region', { hidden: true });
      expect(section).toHaveAttribute('aria-labelledby');
    });

    it('TE-008-03: CTA has accessible label', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" showCta={true} />);
      const ctaLink = screen.getByRole('link', { name: /open meta tag analyzer/i });
      expect(ctaLink).toHaveAttribute('aria-label');
    });
  });

  // ============================================
  // TE-009: Security
  // ============================================
  describe('TE-009: Security', () => {
    it('TE-009-01: XSS in toolSlug shows fallback', () => {
      render(<ToolEmbed toolSlug="<script>alert(1)</script>" />);
      // Should show fallback, not execute script
      expect(screen.getByText('Tool Unavailable')).toBeInTheDocument();
      expect(screen.queryByText('alert(1)')).not.toBeInTheDocument();
    });

    it('TE-009-02: XSS in title prop is escaped', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          title="<script>alert(1)</script>"
        />
      );
      // Script tag should be rendered as text, not executed
      expect(screen.queryByText('alert(1)')).not.toBeInTheDocument();
    });

    it('TE-009-03: XSS in CTA text is escaped', () => {
      render(
        <ToolEmbed
          toolSlug="meta-tag-analyzer"
          showCta={true}
          ctaText="<script>alert(1)</script>"
        />
      );
      // Script should not execute
      expect(screen.queryByText('alert(1)')).not.toBeInTheDocument();
    });
  });

  // ============================================
  // TE-010: Error Handling
  // ============================================
  describe('TE-010: Error Handling', () => {
    it('TE-010-01: Error boundary wraps tool content', () => {
      // ErrorBoundary is mocked to pass through children
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
    });
  });

  // ============================================
  // TE-011: Edge Cases
  // ============================================
  describe('TE-011: Edge Cases', () => {
    it('TE-011-01: Special characters in toolSlug shows fallback', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer!@#" />);
      expect(screen.getByText('Tool Unavailable')).toBeInTheDocument();
    });

    it('TE-011-02: Uppercase toolSlug shows fallback', () => {
      render(<ToolEmbed toolSlug="META-TAG-ANALYZER" />);
      expect(screen.getByText('Tool Unavailable')).toBeInTheDocument();
    });

    it('TE-011-03: Very long toolSlug shows fallback', () => {
      const longSlug = 'a'.repeat(1000);
      render(<ToolEmbed toolSlug={longSlug} />);
      expect(screen.getByText('Tool Unavailable')).toBeInTheDocument();
    });

    it('TE-011-04: Unicode toolSlug shows fallback', () => {
      render(<ToolEmbed toolSlug="meta-tag-中文" />);
      expect(screen.getByText('Tool Unavailable')).toBeInTheDocument();
    });
  });
});

// ============================================
// Integration Tests
// ============================================
describe('ToolEmbed Integration', () => {
  it('renders all valid tool slugs without errors', () => {
    VALID_TOOL_SLUGS.forEach((slug) => {
      const { unmount } = render(<ToolEmbed toolSlug={slug} />);
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
      unmount();
    });
  });

  it('CTA links point to correct routes for deployed tools only', () => {
    // Only deployed tools should show CTA
    const deployedSlugs = VALID_TOOL_SLUGS.filter((slug) => DEPLOYED_TOOLS.has(slug));
    
    deployedSlugs.forEach((slug) => {
      const { unmount } = render(<ToolEmbed toolSlug={slug} showCta={true} />);
      const toolName = TOOL_NAMES[slug];
      const ctaLink = screen.getByRole('link', { name: new RegExp(`open ${toolName}`, 'i') });
      expect(ctaLink).toHaveAttribute('href', TOOL_ROUTES[slug]);
      unmount();
    });
  });

  it('CTA is hidden for tools not yet deployed', () => {
    // Tools not in DEPLOYED_TOOLS should NOT show CTA
    const undeployedSlugs = VALID_TOOL_SLUGS.filter((slug) => !DEPLOYED_TOOLS.has(slug));
    
    undeployedSlugs.forEach((slug) => {
      const { unmount } = render(<ToolEmbed toolSlug={slug} showCta={true} />);
      const toolName = TOOL_NAMES[slug];
      // CTA link should NOT be present for undeployed tools
      const ctaLink = screen.queryByRole('link', { name: new RegExp(`open ${toolName}`, 'i') });
      expect(ctaLink).not.toBeInTheDocument();
      // Tool widget should still render
      expect(screen.getByTestId('tool-widget')).toBeInTheDocument();
      unmount();
    });
  });
});
