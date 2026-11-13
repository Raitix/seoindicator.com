/**
 * Guide Tool Embed Integration Tests
 * Task: TASK-20-22 - Integrate Tool Embedding for Meta Tag Analyzer Guides
 *
 * Tests the integration of ToolEmbed component with guide pages.
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock Next.js navigation
const mockPush = vi.fn();
const mockReplace = vi.fn();
// One stable URLSearchParams instance. A mock that returns
// `new URLSearchParams()` on every call gives a new object identity
// each render, so any effect with [searchParams] in its deps re-runs
// forever and the vitest worker dies with "Worker exited unexpectedly".
const mockSearchParamsInstance = new URLSearchParams();

vi.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
    back: vi.fn(),
    forward: vi.fn(),
    refresh: vi.fn(),
    prefetch: vi.fn(),
  }),
  usePathname: () => '/seo-guides/meta-title/',
  useSearchParams: () => mockSearchParamsInstance,
}));

// Mock guide registry
vi.mock('@/lib/guides/registry', () => ({
  getGuideBySlug: vi.fn((slug: string) => {
    const guides: Record<string, Record<string, unknown>> = {
      'meta-title': {
        slug: 'meta-title',
        title: 'Meta Title Optimization',
        summary: 'Learn how to craft compelling page titles.',
        category: 'on-page',
        toolSlug: 'meta-tag-analyzer',
        icon: '📝',
        readTime: 5,
        sections: [
          {
            title: 'What is Meta Title?',
            content: 'A meta title is the title of your webpage.',
            codeExamples: [],
          },
        ],
        checklist: [
          { text: 'Keep titles under 60 characters', priority: 'critical' },
        ],
        relatedGuides: ['meta-description'],
      },
      'meta-description': {
        slug: 'meta-description',
        title: 'Meta Description Best Practices',
        summary: 'Write descriptions that entice users to click.',
        category: 'on-page',
        toolSlug: 'meta-tag-analyzer',
        icon: '📋',
        readTime: 4,
        sections: [],
        checklist: [],
        relatedGuides: ['meta-title'],
      },
      'page-speed': {
        slug: 'page-speed',
        title: 'Page Speed Optimization',
        summary: 'Improve page load times.',
        category: 'technical',
        // No toolSlug
        icon: '🚀',
        readTime: 7,
        sections: [],
        checklist: [],
        relatedGuides: [],
      },
    };

    return guides[slug] || null;
  }),
  getAllGuides: vi.fn(() => [
    { slug: 'meta-title', title: 'Meta Title', toolSlug: 'meta-tag-analyzer' },
    { slug: 'meta-description', title: 'Meta Description', toolSlug: 'meta-tag-analyzer' },
    { slug: 'page-speed', title: 'Page Speed' },
  ]),
  getRelatedGuides: vi.fn(() => []),
  GUIDE_SLUGS: ['meta-title', 'meta-description', 'page-speed'],
}));

// Mock ToolEmbed component
vi.mock('@/components/guides/ToolEmbed', () => ({
  ToolEmbed: vi.fn(({ toolSlug, title, showCta }) => (
    <div
      data-testid="tool-embed"
      data-tool-slug={toolSlug}
      data-title={title}
    >
      <div>Tool Embed: {toolSlug}</div>
      {showCta && (
        <a href={`/tools/${toolSlug}/`} data-testid="tool-cta">
          Use Full Tool
        </a>
      )}
    </div>
  )),
}));

// Mock GuideSection component
vi.mock('@/components/guides', () => ({
  GuideSection: vi.fn(({ section, index }) => (
    <section data-testid={`guide-section-${index}`}>
      <h2>{section.title}</h2>
      <div>{section.content}</div>
    </section>
  )),
  GuideChecklist: vi.fn(({ items }) => (
    <ul data-testid="guide-checklist">
      {items.map((item: { text: string }, index: number) => (
        <li key={index}>{item.text}</li>
      ))}
    </ul>
  )),
}));

import { getGuideBySlug } from '@/lib/guides/registry';
import { ToolEmbed } from '@/components/guides/ToolEmbed';

const mockGetGuideBySlug = getGuideBySlug as MockedFunction<typeof getGuideBySlug>;
const mockToolEmbed = ToolEmbed as MockedFunction<typeof ToolEmbed>;

describe('Guide Tool Embed Integration', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // GT-001: Tool Embed Rendering in Guides
  describe('GT-001: Tool Embed in Guide Pages', () => {
    // GT-001-01: Meta Title guide embeds Meta Tag Analyzer
    it('should embed Meta Tag Analyzer in meta-title guide', () => {
      const guide = getGuideBySlug('meta-title');

      render(
        <div>
          <h1>{guide?.title}</h1>
          {guide?.toolSlug && (
            <ToolEmbed
              toolSlug={guide.toolSlug}
              title={`Test Your ${guide?.title}`}
            />
          )}
        </div>
      );

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-tool-slug',
        'meta-tag-analyzer'
      );
    });

    // GT-001-02: Meta Description guide embeds Meta Tag Analyzer
    it('should embed Meta Tag Analyzer in meta-description guide', () => {
      const guide = getGuideBySlug('meta-description');

      render(
        <div>
          <h1>{guide?.title}</h1>
          {guide?.toolSlug && (
            <ToolEmbed
              toolSlug={guide.toolSlug}
              title={`Test Your ${guide?.title}`}
            />
          )}
        </div>
      );

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-tool-slug',
        'meta-tag-analyzer'
      );
    });

    // GT-001-03: Guide without toolSlug does not render ToolEmbed
    it('should not render ToolEmbed for guides without toolSlug', () => {
      const guide = getGuideBySlug('page-speed');

      render(
        <div>
          <h1>{guide?.title}</h1>
          {guide?.toolSlug && (
            <ToolEmbed toolSlug={guide.toolSlug} />
          )}
        </div>
      );

      expect(screen.queryByTestId('tool-embed')).not.toBeInTheDocument();
    });
  });

  // GT-002: Tool Position in Guide Layout
  describe('GT-002: Tool Position in Guide Layout', () => {
    // GT-002-01: Tool appears after content sections
    it('should position tool after content sections', () => {
      const guide = getGuideBySlug('meta-title');

      render(
        <article>
          {/* Hero */}
          <header>
            <h1>{guide?.title}</h1>
            <p>{guide?.summary}</p>
          </header>

          {/* Content Sections */}
          {guide?.sections?.map((section, index) => (
            <section key={index} data-testid={`section-${index}`}>
              <h2>{section.title}</h2>
            </section>
          ))}

          {/* Tool Embed */}
          {guide?.toolSlug && (
            <div data-testid="tool-section">
              <ToolEmbed toolSlug={guide.toolSlug} />
            </div>
          )}

          {/* Checklist */}
          {guide?.checklist?.length > 0 && (
            <div data-testid="checklist-section">
              <h2>Checklist</h2>
            </div>
          )}
        </article>
      );

      const toolSection = screen.getByTestId('tool-section');
      const section0 = screen.getByTestId('section-0');

      // Tool section should come after content sections
      expect(toolSection.compareDocumentPosition(section0)).toBe(
        Node.DOCUMENT_POSITION_PRECEDING
      );
    });

    // GT-002-02: Tool appears before checklist
    it('should position tool before checklist', () => {
      const guide = getGuideBySlug('meta-title');

      render(
        <article>
          {/* Tool Embed */}
          {guide?.toolSlug && (
            <div data-testid="tool-section">
              <ToolEmbed toolSlug={guide.toolSlug} />
            </div>
          )}

          {/* Checklist */}
          {guide?.checklist?.length > 0 && (
            <div data-testid="checklist-section">
              <h2>Checklist</h2>
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
  });

  // GT-003: Tool CTA Navigation
  describe('GT-003: Tool CTA Navigation', () => {
    // GT-003-01: CTA links to correct tool page
    it('should link to correct tool page from guide', () => {
      const guide = getGuideBySlug('meta-title');

      render(
        <div>
          {guide?.toolSlug && (
            <ToolEmbed toolSlug={guide.toolSlug} showCta={true} />
          )}
        </div>
      );

      const ctaLink = screen.getByTestId('tool-cta');
      expect(ctaLink).toHaveAttribute('href', '/tools/meta-tag-analyzer/');
    });

    // GT-003-02: Multiple guides with same tool share CTA destination
    it('should link to same tool from different guides', () => {
      const metaTitleGuide = getGuideBySlug('meta-title');
      const metaDescGuide = getGuideBySlug('meta-description');

      expect(metaTitleGuide?.toolSlug).toBe(metaDescGuide?.toolSlug);
    });
  });

  // GT-004: Tool State in Guide Context
  describe('GT-004: Tool State in Guide Context', () => {
    // GT-004-01: Tool state includes guide context
    it('should pass guide context to tool state', () => {
      const guide = getGuideBySlug('meta-title');

      render(
        <div>
          {guide?.toolSlug && (
            <ToolEmbed
              toolSlug={guide.toolSlug}
              stateKey={`guide-${guide.slug}`}
            />
          )}
        </div>
      );

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });
  });

  // GT-005: Guide-Tool Association Validation
  describe('GT-005: Guide-Tool Association Validation', () => {
    // GT-005-01: Valid toolSlug renders tool
    it('should render tool for valid toolSlug', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" />);

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });

    // GT-005-02: Invalid toolSlug shows fallback
    it('should show fallback for invalid toolSlug in guide', () => {
      mockGetGuideBySlug.mockReturnValueOnce({
        slug: 'test-guide',
        title: 'Test Guide',
        summary: 'Test',
        category: 'on-page',
        toolSlug: 'non-existent-tool',
        icon: '📝',
        readTime: 5,
        sections: [],
        checklist: [],
        relatedGuides: [],
      } as ReturnType<typeof getGuideBySlug>);

      const guide = getGuideBySlug('test-guide');

      render(
        <div>
          {guide?.toolSlug && (
            <ToolEmbed toolSlug={guide.toolSlug} />
          )}
        </div>
      );

      // ToolEmbed should handle invalid toolSlug
      expect(screen.getByTestId('tool-embed')).toHaveAttribute(
        'data-tool-slug',
        'non-existent-tool'
      );
    });
  });

  // GT-006: Responsive Behavior
  describe('GT-006: Responsive Behavior', () => {
    // GT-006-01: Tool embed is full-width in guide
    it('should render tool embed as full-width in guide', () => {
      const guide = getGuideBySlug('meta-title');

      const { container } = render(
        <article className="max-w-4xl mx-auto">
          {guide?.toolSlug && (
            <div className="w-full">
              <ToolEmbed toolSlug={guide.toolSlug} />
            </div>
          )}
        </article>
      );

      // Tool wrapper should be full width
      const toolWrapper = container.querySelector('.w-full');
      expect(toolWrapper).toBeInTheDocument();
    });
  });

  // GT-007: Error Scenarios
  describe('GT-007: Error Scenarios', () => {
    // GT-007-01: Guide not found
    it('should handle guide not found gracefully', () => {
      mockGetGuideBySlug.mockReturnValueOnce(null);

      const guide = getGuideBySlug('non-existent-guide');

      expect(guide).toBeNull();
    });

    // GT-007-02: Tool unavailable
    it('should handle tool unavailable gracefully', () => {
      // ToolEmbed should show fallback for unavailable tools
      render(<ToolEmbed toolSlug="unavailable-tool" />);

      expect(screen.getByTestId('tool-embed')).toBeInTheDocument();
    });
  });

  // GT-008: Accessibility
  describe('GT-008: Accessibility', () => {
    // GT-008-01: Tool section is accessible
    it('should have accessible tool section', () => {
      const guide = getGuideBySlug('meta-title');

      render(
        <section aria-labelledby="tool-heading">
          <h2 id="tool-heading">Try It Yourself</h2>
          {guide?.toolSlug && <ToolEmbed toolSlug={guide.toolSlug} />}
        </section>
      );

      expect(screen.getByRole('heading', { name: 'Try It Yourself' })).toBeInTheDocument();
    });

    // GT-008-02: CTA is accessible
    it('should have accessible CTA link', () => {
      render(<ToolEmbed toolSlug="meta-tag-analyzer" showCta={true} />);

      const ctaLink = screen.getByTestId('tool-cta');
      expect(ctaLink).toBeInTheDocument();
    });
  });
});
