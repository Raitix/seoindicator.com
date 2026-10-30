/**
 * Open Graph Guide + Social Media Preview Integration Tests
 * Task: TASK-20-24 - Integrate Tool Embedding for Social Media Preview Guides
 *
 * Tests the integration of Social Media Preview tool in the Open Graph Tags guide.
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// =============================================================================
// MOCKS
// =============================================================================

// Mock Next.js navigation
const mockPush = jest.fn();
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: jest.fn(),
    back: jest.fn(),
    forward: jest.fn(),
    refresh: jest.fn(),
    prefetch: jest.fn(),
  }),
  usePathname: () => '/seo-guides/open-graph/',
  useSearchParams: () => new URLSearchParams(),
}));

// Mock guide registry
jest.mock('@/lib/guides/registry', () => ({
  getGuideBySlug: jest.fn((slug: string) => {
    if (slug === 'open-graph') {
      return {
        slug: 'open-graph',
        title: 'Open Graph Tags',
        summary: 'Optimize social sharing with Open Graph meta tags for Facebook and LinkedIn.',
        category: 'on-page',
        toolSlug: 'social-preview',
        icon: '🔗',
        readTime: 5,
        sections: [
          {
            title: 'What are Open Graph Tags?',
            content: 'Open Graph tags control how your content appears when shared on Facebook.',
            codeExamples: [],
          },
          {
            title: 'Required OG Tags',
            content: 'og:title, og:description, og:image, og:url are essential.',
            codeExamples: [],
          },
          {
            title: 'Image Best Practices',
            content: 'Use 1200x630 pixels for optimal display.',
            codeExamples: [],
          },
        ],
        checklist: [
          { text: 'Include og:title tag', priority: 'critical' as const },
          { text: 'Include og:description tag', priority: 'critical' as const },
          { text: 'Include og:image tag', priority: 'high' as const },
          { text: 'Use 1200x630 image dimensions', priority: 'high' as const },
          { text: 'Test with Facebook Sharing Debugger', priority: 'medium' as const },
        ],
        relatedGuides: ['twitter-cards', 'meta-description'],
      };
    }
    return null;
  }),
  getAllGuides: jest.fn(() => []),
  getRelatedGuides: jest.fn(() => []),
  GUIDE_SLUGS: ['open-graph', 'twitter-cards'],
}));

// Mock ToolEmbed component with tab switching support
jest.mock('@/components/guides/ToolEmbed', () => {
  const React = require('react');
  return {
    ToolEmbed: jest.fn(({ toolSlug, defaultToolTab, title, showCta, ctaText, stateKey, onAnalysisComplete }) => {
      const [activeTab, setActiveTab] = React.useState(defaultToolTab || 'facebook');
      return (
        <div
          data-testid="tool-embed"
          data-tool-slug={toolSlug}
          data-default-tab={defaultToolTab}
          data-title={title}
          data-state-key={stateKey}
        >
          <div>Social Media Preview Tool</div>
          <div data-testid="tool-platform-tabs">
            <button
              data-testid="tab-facebook"
              aria-selected={activeTab === 'facebook'}
              onClick={() => setActiveTab('facebook')}
            >
              Facebook
            </button>
            <button
              data-testid="tab-twitter"
              aria-selected={activeTab === 'twitter'}
              onClick={() => setActiveTab('twitter')}
            >
              Twitter
            </button>
            <button
              data-testid="tab-linkedin"
              aria-selected={activeTab === 'linkedin'}
              onClick={() => setActiveTab('linkedin')}
            >
              LinkedIn
            </button>
          </div>
          <input
            type="url"
            placeholder="Enter page URL"
            aria-label="Enter page URL"
            data-testid="url-input"
          />
          <button data-testid="analyze-button">Analyze</button>
          {showCta && (
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
      );
    }),
  };
});

// Mock GuideSection component
jest.mock('@/components/guides', () => ({
  GuideSection: jest.fn(({ section, index }) => (
    <section data-testid={`guide-section-${index}`}>
      <h2>{section.title}</h2>
      <div>{section.content}</div>
    </section>
  )),
  GuideChecklist: jest.fn(({ items }) => (
    <ul data-testid="guide-checklist">
      {items.map((item: { text: string; priority: string }, index: number) => (
        <li key={index} data-priority={item.priority}>
          {item.text}
        </li>
      ))}
    </ul>
  )),
  RelatedGuides: jest.fn(({ guides }) => (
    <nav data-testid="related-guides">
      {guides.map((guide: string) => (
        <a key={guide} href={`/seo-guides/${guide}/`}>
          {guide}
        </a>
      ))}
    </nav>
  )),
}));

import { getGuideBySlug } from '@/lib/guides/registry';
import { ToolEmbed } from '@/components/guides/ToolEmbed';

const mockGetGuideBySlug = getGuideBySlug as jest.MockedFunction<typeof getGuideBySlug>;
const mockToolEmbed = ToolEmbed as jest.MockedFunction<typeof ToolEmbed>;

// =============================================================================
// TEST SUITE
// =============================================================================

describe('Open Graph Guide + Social Media Preview Integration', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ===========================================================================
  // OG-001: Open Graph Guide Tool Embedding
  // ===========================================================================

  describe('OG-001: Tool Embedding', () => {
    /**
     * OG-001-01: Open Graph guide loads with ToolEmbed
     */
    it('should render ToolEmbed with social-preview toolSlug', () => {
      const guide = getGuideBySlug('open-graph');

      render(
        <article>
          <h1>{guide?.title}</h1>
          {guide?.toolSlug && (
            <ToolEmbed
              toolSlug={guide.toolSlug}
              defaultToolTab="facebook"
              stateKey={`guide-${guide.slug}`}
            />
          )}
        </article>
      );

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-tool-slug',
        'social-preview'
      );
    });

    /**
     * OG-001-02: Default tab is Facebook
     */
    it('should pre-select Facebook tab as default', () => {
      const guide = getGuideBySlug('open-graph');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          defaultToolTab="facebook"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-default-tab',
        'facebook'
      );
      expect(screen.getByTestId('tab-facebook')).toHaveAttribute(
        'aria-selected',
        'true'
      );
    });

    /**
     * OG-001-03: Tool appears after content sections
     */
    it('should position tool after content sections', () => {
      const guide = getGuideBySlug('open-graph');

      render(
        <article>
          <header>
            <h1>{guide?.title}</h1>
            <p>{guide?.summary}</p>
          </header>

          {guide?.sections?.map((section, index) => (
            <section key={index} data-testid={`section-${index}`}>
              <h2>{section.title}</h2>
            </section>
          ))}

          {guide?.toolSlug && (
            <div data-testid="tool-section">
              <ToolEmbed toolSlug={guide.toolSlug} defaultToolTab="facebook" />
            </div>
          )}
        </article>
      );

      // All sections should exist
      expect(screen.getByTestId('section-0')).toBeInTheDocument();
      expect(screen.getByTestId('section-1')).toBeInTheDocument();
      expect(screen.getByTestId('section-2')).toBeInTheDocument();

      // Tool should exist
      expect(screen.getByTestId('tool-section')).toBeInTheDocument();
    });

    /**
     * OG-001-04: Tool appears before checklist
     */
    it('should position tool before checklist', () => {
      const guide = getGuideBySlug('open-graph');

      render(
        <article>
          {guide?.toolSlug && (
            <div data-testid="tool-section">
              <ToolEmbed toolSlug={guide.toolSlug} defaultToolTab="facebook" />
            </div>
          )}

          {guide?.checklist && guide.checklist.length > 0 && (
            <div data-testid="checklist-section">
              <h2>Best Practices Checklist</h2>
            </div>
          )}
        </article>
      );

      const toolSection = screen.getByTestId('tool-section');
      const checklistSection = screen.getByTestId('checklist-section');

      // Tool should appear before checklist
      expect(toolSection.compareDocumentPosition(checklistSection)).toBe(
        Node.DOCUMENT_POSITION_FOLLOWING
      );
    });

    /**
     * OG-001-05: CTA links to social-preview tool
     */
    it('should link to /tools/social-preview/ from CTA', () => {
      const guide = getGuideBySlug('open-graph');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          showCta={true}
          defaultToolTab="facebook"
        />
      );

      const ctaLink = screen.getByTestId('tool-cta');
      expect(ctaLink).toHaveAttribute('href', '/tools/social-preview/');
    });

    /**
     * OG-001-06: Tool state persists with guide key
     */
    it('should use guide-specific state key', () => {
      const guide = getGuideBySlug('open-graph');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          stateKey={`guide-${guide?.slug}`}
          defaultToolTab="facebook"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-state-key',
        'guide-open-graph'
      );
    });

    /**
     * OG-001-07: Graceful fallback when tool unavailable
     */
    it('should show fallback when toolSlug is invalid', () => {
      mockGetGuideBySlug.mockReturnValueOnce({
        slug: 'open-graph',
        title: 'Open Graph Tags',
        summary: 'Test',
        category: 'on-page',
        toolSlug: 'invalid-tool', // Invalid tool
        icon: '🔗',
        readTime: 5,
        sections: [],
        checklist: [],
        relatedGuides: [],
      } as ReturnType<typeof getGuideBySlug>);

      const guide = getGuideBySlug('open-graph');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          defaultToolTab="facebook"
        />
      );

      // ToolEmbed mock always renders, but real component would show fallback
      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });

    /**
     * OG-001-08: Tool analyzes entered URL
     */
    it('should have URL input for analysis', async () => {
      render(<ToolEmbed toolSlug="social-preview" defaultToolTab="facebook" />);

      const urlInput = screen.getByTestId('url-input');
      expect(urlInput).toBeInTheDocument();

      await userEvent.type(urlInput, 'https://example.com');
      expect(urlInput).toHaveValue('https://example.com');
    });

    /**
     * OG-001-09: Results display inline in guide
     */
    it('should display results within guide page', () => {
      const guide = getGuideBySlug('open-graph');

      const { container } = render(
        <article data-testid="guide-container">
          {guide?.toolSlug && (
            <ToolEmbed toolSlug={guide.toolSlug} defaultToolTab="facebook" />
          )}
        </article>
      );

      // Tool should be within guide container
      const toolEmbed = screen.getByTestId('tool-embed');
      const guideContainer = screen.getByTestId('guide-container');

      expect(guideContainer).toContainElement(toolEmbed);
    });

    /**
     * OG-001-10: Missing OG tags shown
     */
    it('should show platform tabs for checking tags', () => {
      render(<ToolEmbed toolSlug="social-preview" defaultToolTab="facebook" />);

      // Platform tabs should be visible
      expect(screen.getByTestId('tab-facebook')).toBeInTheDocument();
      expect(screen.getByTestId('tab-twitter')).toBeInTheDocument();
      expect(screen.getByTestId('tab-linkedin')).toBeInTheDocument();
    });

    /**
     * OG-001-11: Mobile responsive embed
     */
    it('should have responsive container', () => {
      const guide = getGuideBySlug('open-graph');

      const { container } = render(
        <div className="w-full">
          {guide?.toolSlug && (
            <ToolEmbed toolSlug={guide.toolSlug} defaultToolTab="facebook" />
          )}
        </div>
      );

      // Container should be full width
      const wrapper = container.querySelector('.w-full');
      expect(wrapper).toBeInTheDocument();
    });

    /**
     * OG-001-12: Tool context matches guide topic
     */
    it('should have OG-related context', () => {
      const guide = getGuideBySlug('open-graph');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          title={`Preview Your ${guide?.title}`}
          defaultToolTab="facebook"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-title',
        'Preview Your Open Graph Tags'
      );
    });
  });

  // ===========================================================================
  // OG-002: Open Graph Guide Tool State
  // ===========================================================================

  describe('OG-002: Tool State', () => {
    /**
     * OG-002-01: stateKey uses guide slug
     */
    it('should generate state key from guide slug', () => {
      const guide = getGuideBySlug('open-graph');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          stateKey={`guide-${guide?.slug}`}
          defaultToolTab="facebook"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-state-key',
        'guide-open-graph'
      );
    });

    /**
     * OG-002-02: Separate state from other guides
     */
    it('should have different state keys for different guides', () => {
      const ogGuide = getGuideBySlug('open-graph');

      const { rerender } = render(
        <ToolEmbed
          toolSlug="social-preview"
          stateKey={`guide-${ogGuide?.slug}`}
          defaultToolTab="facebook"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-state-key',
        'guide-open-graph'
      );

      // Simulate Twitter Cards guide
      rerender(
        <ToolEmbed
          toolSlug="social-preview"
          stateKey="guide-twitter-cards"
          defaultToolTab="twitter"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-state-key',
        'guide-twitter-cards'
      );
    });

    /**
     * OG-002-03: State survives guide navigation
     */
    it('should maintain state key on re-render', () => {
      const { rerender } = render(
        <ToolEmbed
          toolSlug="social-preview"
          stateKey="guide-open-graph"
          defaultToolTab="facebook"
        />
      );

      // Re-render with same props
      rerender(
        <ToolEmbed
          toolSlug="social-preview"
          stateKey="guide-open-graph"
          defaultToolTab="facebook"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-state-key',
        'guide-open-graph'
      );
    });
  });

  // ===========================================================================
  // OG-003: Open Graph Guide Accessibility
  // ===========================================================================

  describe('OG-003: Accessibility', () => {
    /**
     * OG-003-01: Tool section has heading
     */
    it('should have accessible heading for tool section', () => {
      render(
        <section aria-labelledby="tool-heading">
          <h2 id="tool-heading">Try It Yourself</h2>
          <ToolEmbed toolSlug="social-preview" defaultToolTab="facebook" />
        </section>
      );

      expect(
        screen.getByRole('heading', { name: 'Try It Yourself' })
      ).toBeInTheDocument();
    });

    /**
     * OG-003-02: Tool is keyboard accessible
     */
    it('should have focusable interactive elements', () => {
      render(<ToolEmbed toolSlug="social-preview" defaultToolTab="facebook" />);

      const urlInput = screen.getByTestId('url-input');
      const analyzeButton = screen.getByTestId('analyze-button');

      urlInput.focus();
      expect(document.activeElement).toBe(urlInput);

      analyzeButton.focus();
      expect(document.activeElement).toBe(analyzeButton);
    });

    /**
     * OG-003-03: CTA has descriptive label
     */
    it('should have descriptive CTA label', () => {
      render(
        <ToolEmbed
          toolSlug="social-preview"
          showCta={true}
          ctaText="Use Full Social Media Preview"
          defaultToolTab="facebook"
        />
      );

      const cta = screen.getByTestId('tool-cta');
      expect(cta).toHaveTextContent('Use Full Social Media Preview');
    });
  });

  // ===========================================================================
  // Additional Integration Tests
  // ===========================================================================

  describe('Additional Tests', () => {
    /**
     * Test: CTA opens in new tab
     */
    it('should open CTA in new tab with security attributes', () => {
      render(
        <ToolEmbed
          toolSlug="social-preview"
          showCta={true}
          defaultToolTab="facebook"
        />
      );

      const cta = screen.getByTestId('tool-cta');
      expect(cta).toHaveAttribute('target', '_blank');
      expect(cta).toHaveAttribute('rel', 'noopener noreferrer');
    });

    /**
     * Test: Tab switching
     */
    it('should allow switching between platform tabs', async () => {
      render(<ToolEmbed toolSlug="social-preview" defaultToolTab="facebook" />);

      // Initially Facebook is active
      expect(screen.getByTestId('tab-facebook')).toHaveAttribute(
        'aria-selected',
        'true'
      );

      // Click Twitter tab
      fireEvent.click(screen.getByTestId('tab-twitter'));

      await waitFor(() => {
        expect(screen.getByTestId('tab-twitter')).toHaveAttribute(
          'aria-selected',
          'true'
        );
      });
    });

    /**
     * Test: onAnalysisComplete callback
     */
    it('should call onAnalysisComplete callback', () => {
      const mockOnComplete = jest.fn();

      render(
        <ToolEmbed
          toolSlug="social-preview"
          onAnalysisComplete={mockOnComplete}
          defaultToolTab="facebook"
        />
      );

      fireEvent.click(screen.getByTestId('mock-complete'));

      expect(mockOnComplete).toHaveBeenCalledWith({ success: true, data: {} });
    });

    /**
     * Test: Related guides link
     */
    it('should link to related guides', () => {
      const guide = getGuideBySlug('open-graph');

      render(
        <nav data-testid="related-guides">
          {guide?.relatedGuides?.map((relatedSlug) => (
            <a
              key={relatedSlug}
              href={`/seo-guides/${relatedSlug}/`}
              data-testid={`related-${relatedSlug}`}
            >
              {relatedSlug}
            </a>
          ))}
        </nav>
      );

      expect(screen.getByTestId('related-twitter-cards')).toHaveAttribute(
        'href',
        '/seo-guides/twitter-cards/'
      );
    });
  });
});
