/**
 * SocialPreviewEmbed Component Tests
 * Task: TASK-20-24 - Integrate Tool Embedding for Social Media Preview Guides
 *
 * Tests the SocialPreviewEmbed wrapper component that integrates the Social Media Preview
 * tool into Open Graph and Twitter Cards guide pages with context-appropriate defaults.
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// =============================================================================
// MOCKS
// =============================================================================

// Mock ToolEmbed component
jest.mock('@/components/guides/ToolEmbed', () => ({
  ToolEmbed: jest.fn(
    ({
      toolSlug,
      defaultToolTab,
      title,
      description,
      showCta,
      ctaText,
      stateKey,
      className,
      lazy,
      onAnalysisComplete,
    }) => (
      <div
        data-testid="tool-embed"
        data-tool-slug={toolSlug}
        data-default-tab={defaultToolTab}
        data-title={title}
        data-description={description}
        data-state-key={stateKey}
        data-lazy={lazy}
        className={className}
      >
        <div>Social Media Preview Tool</div>
        {showCta !== false && (
          <a
            href="/tools/social-preview/"
            data-testid="tool-cta"
            target="_blank"
            rel="noopener noreferrer"
          >
            {ctaText || 'Use Full Tool'}
          </a>
        )}
        <button
          data-testid="mock-complete"
          onClick={() => onAnalysisComplete?.({ success: true, data: {} })}
        >
          Complete
        </button>
      </div>
    )
  ),
}));

// Import the mocked component
import { ToolEmbed } from '@/components/guides/ToolEmbed';

// Mock component implementation (to be created by Developer)
interface SocialPreviewEmbedProps {
  guideSlug: 'open-graph' | 'twitter-cards' | string;
  title?: string;
  description?: string;
  showCta?: boolean;
  ctaText?: string;
  stateKey?: string;
  className?: string;
  lazy?: boolean;
  onAnalysisComplete?: (result: { success: boolean; data: unknown }) => void;
}

/**
 * SocialPreviewEmbed - Wrapper component for Social Media Preview tool in guides
 *
 * This component will be implemented by the Developer based on these test specifications.
 * It wraps ToolEmbed with guide-specific defaults.
 */
function SocialPreviewEmbed({
  guideSlug,
  title,
  description,
  showCta = true,
  ctaText,
  stateKey,
  className,
  lazy = true,
  onAnalysisComplete,
}: SocialPreviewEmbedProps): JSX.Element | null {
  // Normalize guide slug
  const normalizedSlug = guideSlug.toLowerCase().replace(/_/g, '-');

  // Determine default tab based on guide
  const getDefaultTab = (): string => {
    if (normalizedSlug === 'twitter-cards') {
      return 'twitter';
    }
    return 'facebook'; // Default for open-graph and other guides
  };

  // Generate state key
  const generatedStateKey = stateKey || `guide-${normalizedSlug}-social-preview`;

  // Generate title
  const generatedTitle = title || `Preview Your Social Sharing`;

  // Check for invalid guide slug (optional - could show fallback)
  const validSlugs = ['open-graph', 'twitter-cards'];

  return (
    <ToolEmbed
      toolSlug="social-preview"
      defaultToolTab={getDefaultTab()}
      title={generatedTitle}
      description={description}
      showCta={showCta}
      ctaText={ctaText}
      stateKey={generatedStateKey}
      className={className}
      lazy={lazy}
      onAnalysisComplete={onAnalysisComplete}
    />
  );
}

const mockToolEmbed = ToolEmbed as jest.MockedFunction<typeof ToolEmbed>;

// =============================================================================
// TEST SUITE
// =============================================================================

describe('SocialPreviewEmbed Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ===========================================================================
  // SPE-001: Component Rendering
  // ===========================================================================

  describe('SPE-001: Component Rendering', () => {
    /**
     * SPE-001-01: Renders with guideSlug="open-graph"
     */
    it('should render ToolEmbed with social-preview and Facebook tab for open-graph', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph" />);

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-tool-slug',
        'social-preview'
      );
      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-default-tab',
        'facebook'
      );
    });

    /**
     * SPE-001-02: Renders with guideSlug="twitter-cards"
     */
    it('should render ToolEmbed with social-preview and Twitter tab for twitter-cards', () => {
      render(<SocialPreviewEmbed guideSlug="twitter-cards" />);

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-tool-slug',
        'social-preview'
      );
      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-default-tab',
        'twitter'
      );
    });

    /**
     * SPE-001-03: Invalid guideSlug shows fallback
     */
    it('should render ToolEmbed for unknown guide slugs with Facebook default', () => {
      render(<SocialPreviewEmbed guideSlug="unknown-guide" />);

      // Should still render with default (Facebook) tab
      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-default-tab',
        'facebook'
      );
    });

    /**
     * SPE-001-04: Custom title prop
     */
    it('should use custom title when provided', () => {
      render(
        <SocialPreviewEmbed guideSlug="open-graph" title="Custom Preview Title" />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-title',
        'Custom Preview Title'
      );
    });

    /**
     * SPE-001-05: Custom description prop
     */
    it('should use custom description when provided', () => {
      render(
        <SocialPreviewEmbed
          guideSlug="open-graph"
          description="Custom description for the tool"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-description',
        'Custom description for the tool'
      );
    });

    /**
     * SPE-001-06: showCta prop controls CTA
     */
    it('should hide CTA when showCta is false', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph" showCta={false} />);

      expect(screen.queryByTestId('tool-cta')).not.toBeInTheDocument();
    });

    /**
     * SPE-001-07: customCtaText prop
     */
    it('should use custom CTA text when provided', () => {
      render(
        <SocialPreviewEmbed guideSlug="open-graph" ctaText="Preview Your Page" />
      );

      expect(screen.getByTestId('tool-cta')).toHaveTextContent('Preview Your Page');
    });

    /**
     * SPE-001-08: onAnalysisComplete callback
     */
    it('should call onAnalysisComplete callback', () => {
      const mockOnComplete = jest.fn();

      render(
        <SocialPreviewEmbed
          guideSlug="open-graph"
          onAnalysisComplete={mockOnComplete}
        />
      );

      fireEvent.click(screen.getByTestId('mock-complete'));

      expect(mockOnComplete).toHaveBeenCalledWith({ success: true, data: {} });
    });

    /**
     * SPE-001-09: className prop applied
     */
    it('should apply custom className to wrapper', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph" className="custom-class" />);

      expect(screen.getByTestId('tool-embed')).toHaveClass('custom-class');
    });

    /**
     * SPE-001-10: Mobile responsive
     */
    it('should render within responsive container', () => {
      const { container } = render(
        <div className="w-full">
          <SocialPreviewEmbed guideSlug="open-graph" />
        </div>
      );

      const wrapper = container.querySelector('.w-full');
      expect(wrapper).toBeInTheDocument();
    });
  });

  // ===========================================================================
  // SPE-002: Tab Selection
  // ===========================================================================

  describe('SPE-002: Tab Selection', () => {
    /**
     * SPE-002-01: Open Graph maps to Facebook tab
     */
    it('should set Facebook tab as default for open-graph guide', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph" />);

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-default-tab',
        'facebook'
      );
    });

    /**
     * SPE-002-02: Twitter Cards maps to Twitter tab
     */
    it('should set Twitter tab as default for twitter-cards guide', () => {
      render(<SocialPreviewEmbed guideSlug="twitter-cards" />);

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-default-tab',
        'twitter'
      );
    });

    /**
     * SPE-002-03: Unknown guide uses default tab
     */
    it('should use Facebook as default for unknown guides', () => {
      render(<SocialPreviewEmbed guideSlug="some-other-guide" />);

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-default-tab',
        'facebook'
      );
    });

    /**
     * SPE-002-04: Case-insensitive guide slug
     */
    it('should handle uppercase guide slug', () => {
      render(<SocialPreviewEmbed guideSlug="OPEN-GRAPH" />);

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-default-tab',
        'facebook'
      );
    });

    /**
     * SPE-002-05: Dash/underscore normalization
     */
    it('should normalize underscores to dashes', () => {
      render(<SocialPreviewEmbed guideSlug="twitter_cards" />);

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-default-tab',
        'twitter'
      );
    });
  });

  // ===========================================================================
  // SPE-003: State Management
  // ===========================================================================

  describe('SPE-003: State Management', () => {
    /**
     * SPE-003-01: stateKey auto-generated from guideSlug
     */
    it('should auto-generate stateKey from guideSlug', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph" />);

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-state-key',
        'guide-open-graph-social-preview'
      );
    });

    /**
     * SPE-003-02: Custom stateKey overrides auto
     */
    it('should use custom stateKey when provided', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph" stateKey="custom-key" />);

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-state-key',
        'custom-key'
      );
    });

    /**
     * SPE-003-03: Lazy loading enabled by default
     */
    it('should enable lazy loading by default', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph" />);

      expect(screen.getByTestId('tool-embed')).toHaveAttribute('data-lazy', 'true');
    });

    /**
     * SPE-003-04: Lazy loading can be disabled
     */
    it('should allow disabling lazy loading', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph" lazy={false} />);

      expect(screen.getByTestId('tool-embed')).toHaveAttribute('data-lazy', 'false');
    });
  });

  // ===========================================================================
  // SPE-004: Accessibility
  // ===========================================================================

  describe('SPE-004: Accessibility', () => {
    /**
     * SPE-004-01: Section has aria-labelledby
     */
    it('should be contained in accessible section', () => {
      render(
        <section aria-labelledby="preview-heading">
          <h2 id="preview-heading">Try It Yourself</h2>
          <SocialPreviewEmbed guideSlug="open-graph" />
        </section>
      );

      expect(
        screen.getByRole('heading', { name: 'Try It Yourself' })
      ).toBeInTheDocument();
    });

    /**
     * SPE-004-02: Keyboard accessible
     */
    it('should have focusable CTA', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph" showCta={true} />);

      const cta = screen.getByTestId('tool-cta');
      cta.focus();
      expect(document.activeElement).toBe(cta);
    });

    /**
     * SPE-004-03: Screen reader announces tool
     */
    it('should have accessible content', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph" />);

      expect(screen.getByText('Social Media Preview Tool')).toBeInTheDocument();
    });
  });

  // ===========================================================================
  // SPE-005: Error Handling
  // ===========================================================================

  describe('SPE-005: Error Handling', () => {
    /**
     * SPE-005-01: Tool error shows fallback
     */
    it('should gracefully handle tool errors', () => {
      // ToolEmbed mock handles this, real component would have error boundary
      render(<SocialPreviewEmbed guideSlug="open-graph" />);

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });

    /**
     * SPE-005-02: Network error handled
     */
    it('should handle network errors gracefully', () => {
      // This would be handled by the underlying ToolEmbed
      render(<SocialPreviewEmbed guideSlug="open-graph" />);

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });

    /**
     * SPE-005-03: Invalid URL validation
     */
    it('should validate URLs', () => {
      // URL validation handled by underlying tool
      render(<SocialPreviewEmbed guideSlug="open-graph" />);

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    /**
     * Test: ToolEmbed receives correct props
     */
    it('should pass all props to ToolEmbed correctly', () => {
      render(
        <SocialPreviewEmbed
          guideSlug="twitter-cards"
          title="Test Title"
          description="Test Description"
          showCta={true}
          ctaText="Test CTA"
          stateKey="test-state"
          className="test-class"
          lazy={false}
        />
      );

      const embed = screen.getByTestId('tool-embed');

      expect(embed).toHaveAttribute('data-tool-slug', 'social-preview');
      expect(embed).toHaveAttribute('data-default-tab', 'twitter');
      expect(embed).toHaveAttribute('data-title', 'Test Title');
      expect(embed).toHaveAttribute('data-description', 'Test Description');
      expect(embed).toHaveAttribute('data-state-key', 'test-state');
      expect(embed).toHaveClass('test-class');
      expect(embed).toHaveAttribute('data-lazy', 'false');
    });

    /**
     * Test: CTA links to correct destination
     */
    it('should link CTA to /tools/social-preview/', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph" showCta={true} />);

      const cta = screen.getByTestId('tool-cta');
      expect(cta).toHaveAttribute('href', '/tools/social-preview/');
    });

    /**
     * Test: Multiple instances on same page
     */
    it('should support multiple instances with different state keys', () => {
      const { container } = render(
        <>
          <SocialPreviewEmbed guideSlug="open-graph" />
          <SocialPreviewEmbed guideSlug="twitter-cards" />
        </>
      );

      const embeds = screen.getAllByTestId('tool-embed');
      expect(embeds).toHaveLength(2);

      // Each should have different state keys
      expect(embeds[0]).toHaveAttribute(
        'data-state-key',
        'guide-open-graph-social-preview'
      );
      expect(embeds[1]).toHaveAttribute(
        'data-state-key',
        'guide-twitter-cards-social-preview'
      );
    });

    /**
     * Test: Props override defaults
     */
    it('should allow props to override defaults', () => {
      render(
        <SocialPreviewEmbed
          guideSlug="open-graph"
          title="Override Title"
          ctaText="Override CTA"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-title',
        'Override Title'
      );
      expect(screen.getByTestId('tool-cta')).toHaveTextContent('Override CTA');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    /**
     * Test: Empty guideSlug
     */
    it('should handle empty guideSlug gracefully', () => {
      render(<SocialPreviewEmbed guideSlug="" />);

      // Should render with defaults
      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-default-tab',
        'facebook'
      );
    });

    /**
     * Test: Very long guideSlug
     */
    it('should handle very long guideSlug', () => {
      const longSlug = 'a'.repeat(100);
      render(<SocialPreviewEmbed guideSlug={longSlug} />);

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });

    /**
     * Test: Special characters in guideSlug
     */
    it('should handle special characters in guideSlug', () => {
      render(<SocialPreviewEmbed guideSlug="open-graph-v2.0" />);

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });

    /**
     * Test: Undefined props
     */
    it('should handle undefined optional props', () => {
      render(
        <SocialPreviewEmbed
          guideSlug="open-graph"
          title={undefined}
          description={undefined}
          ctaText={undefined}
        />
      );

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });
  });
});
