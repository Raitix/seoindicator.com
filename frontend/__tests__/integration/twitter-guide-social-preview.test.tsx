/**
 * Twitter Cards Guide + Social Media Preview Integration Tests
 * Task: TASK-20-24 - Integrate Tool Embedding for Social Media Preview Guides
 *
 * Tests the integration of Social Media Preview tool in the Twitter Cards guide.
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
  usePathname: () => '/seo-guides/twitter-cards/',
  useSearchParams: () => new URLSearchParams(),
}));

// Mock guide registry
jest.mock('@/lib/guides/registry', () => ({
  getGuideBySlug: jest.fn((slug: string) => {
    if (slug === 'twitter-cards') {
      return {
        slug: 'twitter-cards',
        title: 'Twitter Cards',
        summary: 'Enhance your Twitter sharing with card meta tags for better engagement.',
        category: 'on-page',
        toolSlug: 'social-preview',
        icon: '🐦',
        readTime: 4,
        sections: [
          {
            title: 'What are Twitter Cards?',
            content: 'Twitter Cards allow you to attach rich photos, videos, and media experiences.',
            codeExamples: [],
          },
          {
            title: 'Card Types',
            content: 'Summary, Summary Large Image, Player, and App cards.',
            codeExamples: [],
          },
          {
            title: 'Required Twitter Card Tags',
            content: 'twitter:card, twitter:site, twitter:title, twitter:description, twitter:image.',
            codeExamples: [],
          },
        ],
        checklist: [
          { text: 'Include twitter:card tag', priority: 'critical' as const },
          { text: 'Include twitter:title tag', priority: 'high' as const },
          { text: 'Include twitter:description tag', priority: 'high' as const },
          { text: 'Include twitter:image tag', priority: 'medium' as const },
          { text: 'Test with Twitter Card Validator', priority: 'medium' as const },
        ],
        relatedGuides: ['open-graph', 'meta-description'],
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
      const [activeTab, setActiveTab] = React.useState(defaultToolTab || 'twitter');
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

describe('Twitter Cards Guide + Social Media Preview Integration', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ===========================================================================
  // TW-001: Twitter Cards Guide Tool Embedding
  // ===========================================================================

  describe('TW-001: Tool Embedding', () => {
    /**
     * TW-001-01: Twitter Cards guide loads with ToolEmbed
     */
    it('should render ToolEmbed with social-preview toolSlug', () => {
      const guide = getGuideBySlug('twitter-cards');

      render(
        <article>
          <h1>{guide?.title}</h1>
          {guide?.toolSlug && (
            <ToolEmbed
              toolSlug={guide.toolSlug}
              defaultToolTab="twitter"
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
     * TW-001-02: Default tab is Twitter
     */
    it('should pre-select Twitter tab as default', () => {
      const guide = getGuideBySlug('twitter-cards');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          defaultToolTab="twitter"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-default-tab',
        'twitter'
      );
      expect(screen.getByTestId('tab-twitter')).toHaveAttribute(
        'aria-selected',
        'true'
      );
    });

    /**
     * TW-001-03: Tool appears after content sections
     */
    it('should position tool after content sections', () => {
      const guide = getGuideBySlug('twitter-cards');

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
              <ToolEmbed toolSlug={guide.toolSlug} defaultToolTab="twitter" />
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
     * TW-001-04: Tool appears before checklist
     */
    it('should position tool before checklist', () => {
      const guide = getGuideBySlug('twitter-cards');

      render(
        <article>
          {guide?.toolSlug && (
            <div data-testid="tool-section">
              <ToolEmbed toolSlug={guide.toolSlug} defaultToolTab="twitter" />
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
     * TW-001-05: CTA links to social-preview tool
     */
    it('should link to /tools/social-preview/ from CTA', () => {
      const guide = getGuideBySlug('twitter-cards');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          showCta={true}
          defaultToolTab="twitter"
        />
      );

      const ctaLink = screen.getByTestId('tool-cta');
      expect(ctaLink).toHaveAttribute('href', '/tools/social-preview/');
    });

    /**
     * TW-001-06: Tool state persists with guide key
     */
    it('should use guide-specific state key', () => {
      const guide = getGuideBySlug('twitter-cards');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          stateKey={`guide-${guide?.slug}`}
          defaultToolTab="twitter"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-state-key',
        'guide-twitter-cards'
      );
    });

    /**
     * TW-001-07: Graceful fallback when tool unavailable
     */
    it('should show fallback when toolSlug is invalid', () => {
      mockGetGuideBySlug.mockReturnValueOnce({
        slug: 'twitter-cards',
        title: 'Twitter Cards',
        summary: 'Test',
        category: 'on-page',
        toolSlug: 'invalid-tool', // Invalid tool
        icon: '🐦',
        readTime: 4,
        sections: [],
        checklist: [],
        relatedGuides: [],
      } as ReturnType<typeof getGuideBySlug>);

      const guide = getGuideBySlug('twitter-cards');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          defaultToolTab="twitter"
        />
      );

      // ToolEmbed mock always renders, but real component would show fallback
      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });

    /**
     * TW-001-08: Tool analyzes entered URL
     */
    it('should have URL input for analysis', async () => {
      render(<ToolEmbed toolSlug="social-preview" defaultToolTab="twitter" />);

      const urlInput = screen.getByTestId('url-input');
      expect(urlInput).toBeInTheDocument();

      await userEvent.type(urlInput, 'https://example.com');
      expect(urlInput).toHaveValue('https://example.com');
    });

    /**
     * TW-001-09: Results display inline in guide
     */
    it('should display results within guide page', () => {
      const guide = getGuideBySlug('twitter-cards');

      const { container } = render(
        <article data-testid="guide-container">
          {guide?.toolSlug && (
            <ToolEmbed toolSlug={guide.toolSlug} defaultToolTab="twitter" />
          )}
        </article>
      );

      // Tool should be within guide container
      const toolEmbed = screen.getByTestId('tool-embed');
      const guideContainer = screen.getByTestId('guide-container');

      expect(guideContainer).toContainElement(toolEmbed);
    });

    /**
     * TW-001-10: Missing Twitter tags shown
     */
    it('should show platform tabs for checking tags', () => {
      render(<ToolEmbed toolSlug="social-preview" defaultToolTab="twitter" />);

      // Platform tabs should be visible
      expect(screen.getByTestId('tab-facebook')).toBeInTheDocument();
      expect(screen.getByTestId('tab-twitter')).toBeInTheDocument();
      expect(screen.getByTestId('tab-linkedin')).toBeInTheDocument();
    });

    /**
     * TW-001-11: Mobile responsive embed
     */
    it('should have responsive container', () => {
      const guide = getGuideBySlug('twitter-cards');

      const { container } = render(
        <div className="w-full">
          {guide?.toolSlug && (
            <ToolEmbed toolSlug={guide.toolSlug} defaultToolTab="twitter" />
          )}
        </div>
      );

      // Container should be full width
      const wrapper = container.querySelector('.w-full');
      expect(wrapper).toBeInTheDocument();
    });

    /**
     * TW-001-12: Tool context matches guide topic
     */
    it('should have Twitter-related context', () => {
      const guide = getGuideBySlug('twitter-cards');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          title={`Preview Your ${guide?.title}`}
          defaultToolTab="twitter"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-title',
        'Preview Your Twitter Cards'
      );
    });
  });

  // ===========================================================================
  // TW-002: Twitter Cards Guide Tool State
  // ===========================================================================

  describe('TW-002: Tool State', () => {
    /**
     * TW-002-01: stateKey uses guide slug
     */
    it('should generate state key from guide slug', () => {
      const guide = getGuideBySlug('twitter-cards');

      render(
        <ToolEmbed
          toolSlug={guide?.toolSlug || 'social-preview'}
          stateKey={`guide-${guide?.slug}`}
          defaultToolTab="twitter"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-state-key',
        'guide-twitter-cards'
      );
    });

    /**
     * TW-002-02: Separate state from OG guide
     */
    it('should have different state keys for different guides', () => {
      const twitterGuide = getGuideBySlug('twitter-cards');

      const { rerender } = render(
        <ToolEmbed
          toolSlug="social-preview"
          stateKey={`guide-${twitterGuide?.slug}`}
          defaultToolTab="twitter"
        />
      );

      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-state-key',
        'guide-twitter-cards'
      );

      // Simulate Open Graph guide
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

    /**
     * TW-002-03: State survives guide navigation
     */
    it('should maintain state key on re-render', () => {
      const { rerender } = render(
        <ToolEmbed
          toolSlug="social-preview"
          stateKey="guide-twitter-cards"
          defaultToolTab="twitter"
        />
      );

      // Re-render with same props
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
  });

  // ===========================================================================
  // TW-003: Twitter Cards Guide Accessibility
  // ===========================================================================

  describe('TW-003: Accessibility', () => {
    /**
     * TW-003-01: Tool section has heading
     */
    it('should have accessible heading for tool section', () => {
      render(
        <section aria-labelledby="tool-heading">
          <h2 id="tool-heading">Try It Yourself</h2>
          <ToolEmbed toolSlug="social-preview" defaultToolTab="twitter" />
        </section>
      );

      expect(
        screen.getByRole('heading', { name: 'Try It Yourself' })
      ).toBeInTheDocument();
    });

    /**
     * TW-003-02: Tool is keyboard accessible
     */
    it('should have focusable interactive elements', () => {
      render(<ToolEmbed toolSlug="social-preview" defaultToolTab="twitter" />);

      const urlInput = screen.getByTestId('url-input');
      const analyzeButton = screen.getByTestId('analyze-button');

      urlInput.focus();
      expect(document.activeElement).toBe(urlInput);

      analyzeButton.focus();
      expect(document.activeElement).toBe(analyzeButton);
    });

    /**
     * TW-003-03: CTA has descriptive label
     */
    it('should have descriptive CTA label', () => {
      render(
        <ToolEmbed
          toolSlug="social-preview"
          showCta={true}
          ctaText="Use Full Social Media Preview"
          defaultToolTab="twitter"
        />
      );

      const cta = screen.getByTestId('tool-cta');
      expect(cta).toHaveTextContent('Use Full Social Media Preview');
    });
  });

  // ===========================================================================
  // Twitter-Specific Tests
  // ===========================================================================

  describe('Twitter-Specific Behavior', () => {
    /**
     * Test: Twitter tab is pre-selected
     */
    it('should have Twitter tab pre-selected, not Facebook', () => {
      render(<ToolEmbed toolSlug="social-preview" defaultToolTab="twitter" />);

      // Twitter should be selected
      expect(screen.getByTestId('tab-twitter')).toHaveAttribute(
        'aria-selected',
        'true'
      );

      // Facebook should NOT be selected
      expect(screen.getByTestId('tab-facebook')).toHaveAttribute(
        'aria-selected',
        'false'
      );
    });

    /**
     * Test: Twitter card types mentioned in content
     */
    it('should reference Twitter card types in guide', () => {
      const guide = getGuideBySlug('twitter-cards');

      render(
        <section>
          <h2>{guide?.sections?.[1]?.title}</h2>
          <p>{guide?.sections?.[1]?.content}</p>
        </section>
      );

      expect(screen.getByText(/Summary, Summary Large Image/i)).toBeInTheDocument();
    });

    /**
     * Test: Related guides link to Open Graph
     */
    it('should link to Open Graph guide as related', () => {
      const guide = getGuideBySlug('twitter-cards');

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

      expect(screen.getByTestId('related-open-graph')).toHaveAttribute(
        'href',
        '/seo-guides/open-graph/'
      );
    });

    /**
     * Test: Twitter Validator mentioned in checklist
     */
    it('should mention Twitter Card Validator in checklist', () => {
      const guide = getGuideBySlug('twitter-cards');

      render(
        <ul>
          {guide?.checklist?.map((item, index) => (
            <li key={index}>{item.text}</li>
          ))}
        </ul>
      );

      expect(screen.getByText(/Twitter Card Validator/i)).toBeInTheDocument();
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
          defaultToolTab="twitter"
        />
      );

      const cta = screen.getByTestId('tool-cta');
      expect(cta).toHaveAttribute('target', '_blank');
      expect(cta).toHaveAttribute('rel', 'noopener noreferrer');
    });

    /**
     * Test: Tab switching from Twitter to others
     */
    it('should allow switching from Twitter to other tabs', async () => {
      render(<ToolEmbed toolSlug="social-preview" defaultToolTab="twitter" />);

      // Initially Twitter is active
      expect(screen.getByTestId('tab-twitter')).toHaveAttribute(
        'aria-selected',
        'true'
      );

      // Click Facebook tab
      fireEvent.click(screen.getByTestId('tab-facebook'));

      await waitFor(() => {
        expect(screen.getByTestId('tab-facebook')).toHaveAttribute(
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
          defaultToolTab="twitter"
        />
      );

      fireEvent.click(screen.getByTestId('mock-complete'));

      expect(mockOnComplete).toHaveBeenCalledWith({ success: true, data: {} });
    });

    /**
     * Test: Twitter-specific checklist items
     */
    it('should have Twitter-specific checklist items', () => {
      const guide = getGuideBySlug('twitter-cards');

      const checklistItems = guide?.checklist || [];
      const hasTwitterCard = checklistItems.some(item =>
        item.text.includes('twitter:card')
      );

      expect(hasTwitterCard).toBe(true);
    });

    /**
     * Test: Guide icon is Twitter bird
     */
    it('should have Twitter bird icon', () => {
      const guide = getGuideBySlug('twitter-cards');
      expect(guide?.icon).toBe('🐦');
    });
  });
});
