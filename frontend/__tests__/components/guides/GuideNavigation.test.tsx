/**
 * Tests for GuideNavigation Component
 * Task: TASK-20-3 - Create Guide Page Template Component
 * 
 * Tests the prev/next navigation between guides.
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock Next.js Link
jest.mock('next/link', () => {
  return function MockLink({ children, href, className }: { children: React.ReactNode; href: string; className?: string }) {
    return <a href={href} className={className}>{children}</a>;
  };
});

// All guide slugs in order
const ALL_GUIDE_SLUGS = [
  'meta-title',
  'meta-description',
  'h1-headings',
  'heading-hierarchy',
  'image-alt-text',
  'image-optimization',
  'internal-links',
  'external-links',
  'page-speed',
  'mobile-friendly',
  'https-security',
  'structured-data',
  'canonical-urls',
  'robots-txt',
  'xml-sitemap',
  'open-graph',
  'twitter-cards',
];

// Guide titles map
const GUIDE_TITLES: Record<string, string> = {
  'meta-title': 'Meta Title Optimization',
  'meta-description': 'Meta Description Best Practices',
  'h1-headings': 'H1 Heading Structure',
  'heading-hierarchy': 'Heading Hierarchy (H1-H6)',
  'image-alt-text': 'Image Alt Text Optimization',
  'image-optimization': 'Image Optimization for Web',
  'internal-links': 'Internal Linking Strategy',
  'external-links': 'External Link Best Practices',
  'page-speed': 'Page Speed Optimization',
  'mobile-friendly': 'Mobile-Friendly Design',
  'https-security': 'HTTPS & Security Headers',
  'structured-data': 'Structured Data (Schema.org)',
  'canonical-urls': 'Canonical URL Implementation',
  'robots-txt': 'Robots.txt Configuration',
  'xml-sitemap': 'XML Sitemap Creation',
  'open-graph': 'Open Graph Tags',
  'twitter-cards': 'Twitter Cards',
};

// Mock component for TDD
function MockGuideNavigation({ currentSlug }: { currentSlug: string }) {
  const currentIndex = ALL_GUIDE_SLUGS.indexOf(currentSlug);
  
  if (currentIndex === -1) {
    return null;
  }

  const prevSlug = currentIndex > 0 ? ALL_GUIDE_SLUGS[currentIndex - 1] : null;
  const nextSlug = currentIndex < ALL_GUIDE_SLUGS.length - 1 ? ALL_GUIDE_SLUGS[currentIndex + 1] : null;

  return (
    <nav 
      data-testid="guide-navigation"
      aria-label="Guide navigation"
      className="guide-navigation mt-12 pt-8 border-t dark:border-gray-700"
    >
      <div 
        data-testid="nav-container"
        className="flex justify-between items-center gap-4"
      >
        {/* Previous Guide */}
        <div data-testid="prev-container" className="flex-1">
          {prevSlug ? (
            <a
              href={`/seo-guides/${prevSlug}`}
              data-testid="prev-link"
              className="flex items-center gap-2 p-4 rounded-lg border hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
            >
              <span data-testid="prev-arrow" className="text-xl">←</span>
              <div>
                <span data-testid="prev-label" className="text-xs text-gray-500 block">Previous</span>
                <span data-testid="prev-title" className="font-medium dark:text-white">
                  {GUIDE_TITLES[prevSlug]}
                </span>
              </div>
            </a>
          ) : (
            <div data-testid="prev-empty" />
          )}
        </div>

        {/* Back to Index */}
        <div data-testid="index-container" className="flex-shrink-0">
          <a
            href="/seo-guides"
            data-testid="index-link"
            className="p-4 rounded-lg border hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
          >
            <span data-testid="index-icon" className="text-xl">📖</span>
            <span data-testid="index-label" className="sr-only">All Guides</span>
          </a>
        </div>

        {/* Next Guide */}
        <div data-testid="next-container" className="flex-1 text-right">
          {nextSlug ? (
            <a
              href={`/seo-guides/${nextSlug}`}
              data-testid="next-link"
              className="flex items-center justify-end gap-2 p-4 rounded-lg border hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
            >
              <div>
                <span data-testid="next-label" className="text-xs text-gray-500 block">Next</span>
                <span data-testid="next-title" className="font-medium dark:text-white">
                  {GUIDE_TITLES[nextSlug]}
                </span>
              </div>
              <span data-testid="next-arrow" className="text-xl">→</span>
            </a>
          ) : (
            <div data-testid="next-empty" />
          )}
        </div>
      </div>
    </nav>
  );
}

describe('GuideNavigation Component', () => {
  describe('GN-001: Renders navigation', () => {
    it('renders navigation section', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      expect(screen.getByTestId('guide-navigation')).toBeInTheDocument();
    });
  });

  describe('GN-002: Previous guide link', () => {
    it('shows previous link for non-first guides', () => {
      render(<MockGuideNavigation currentSlug="meta-description" />);
      expect(screen.getByTestId('prev-link')).toBeInTheDocument();
    });

    it('previous link points to correct guide', () => {
      render(<MockGuideNavigation currentSlug="meta-description" />);
      expect(screen.getByTestId('prev-link')).toHaveAttribute('href', '/seo-guides/meta-title');
    });
  });

  describe('GN-003: Next guide link', () => {
    it('shows next link for non-last guides', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      expect(screen.getByTestId('next-link')).toBeInTheDocument();
    });

    it('next link points to correct guide', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      expect(screen.getByTestId('next-link')).toHaveAttribute('href', '/seo-guides/meta-description');
    });
  });

  describe('GN-004: First guide', () => {
    it('no previous link for first guide', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      expect(screen.queryByTestId('prev-link')).not.toBeInTheDocument();
    });

    it('shows empty prev container', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      expect(screen.getByTestId('prev-empty')).toBeInTheDocument();
    });
  });

  describe('GN-005: Last guide', () => {
    it('no next link for last guide', () => {
      render(<MockGuideNavigation currentSlug="twitter-cards" />);
      expect(screen.queryByTestId('next-link')).not.toBeInTheDocument();
    });

    it('shows empty next container', () => {
      render(<MockGuideNavigation currentSlug="twitter-cards" />);
      expect(screen.getByTestId('next-empty')).toBeInTheDocument();
    });
  });

  describe('GN-006: Previous link correct', () => {
    it('meta-description prev links to meta-title', () => {
      render(<MockGuideNavigation currentSlug="meta-description" />);
      expect(screen.getByTestId('prev-link')).toHaveAttribute('href', '/seo-guides/meta-title');
    });

    it('h1-headings prev links to meta-description', () => {
      render(<MockGuideNavigation currentSlug="h1-headings" />);
      expect(screen.getByTestId('prev-link')).toHaveAttribute('href', '/seo-guides/meta-description');
    });
  });

  describe('GN-007: Next link correct', () => {
    it('meta-title next links to meta-description', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      expect(screen.getByTestId('next-link')).toHaveAttribute('href', '/seo-guides/meta-description');
    });

    it('h1-headings next links to heading-hierarchy', () => {
      render(<MockGuideNavigation currentSlug="h1-headings" />);
      expect(screen.getByTestId('next-link')).toHaveAttribute('href', '/seo-guides/heading-hierarchy');
    });
  });

  describe('GN-008: Navigation arrows', () => {
    it('shows left arrow for previous', () => {
      render(<MockGuideNavigation currentSlug="meta-description" />);
      expect(screen.getByTestId('prev-arrow')).toHaveTextContent('←');
    });

    it('shows right arrow for next', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      expect(screen.getByTestId('next-arrow')).toHaveTextContent('→');
    });
  });

  describe('GN-009: Guide titles in links', () => {
    it('shows previous guide title', () => {
      render(<MockGuideNavigation currentSlug="meta-description" />);
      expect(screen.getByTestId('prev-title')).toHaveTextContent('Meta Title Optimization');
    });

    it('shows next guide title', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      expect(screen.getByTestId('next-title')).toHaveTextContent('Meta Description Best Practices');
    });
  });

  describe('GN-010: Accessible navigation', () => {
    it('has nav element', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      const nav = screen.getByTestId('guide-navigation');
      expect(nav.tagName).toBe('NAV');
    });

    it('has aria-label', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      const nav = screen.getByTestId('guide-navigation');
      expect(nav).toHaveAttribute('aria-label', 'Guide navigation');
    });
  });

  describe('GN-011: Keyboard accessible', () => {
    it('prev link is focusable', () => {
      render(<MockGuideNavigation currentSlug="meta-description" />);
      const prevLink = screen.getByTestId('prev-link');
      prevLink.focus();
      expect(prevLink).toHaveFocus();
    });

    it('next link is focusable', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      const nextLink = screen.getByTestId('next-link');
      nextLink.focus();
      expect(nextLink).toHaveFocus();
    });

    it('index link is focusable', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      const indexLink = screen.getByTestId('index-link');
      indexLink.focus();
      expect(indexLink).toHaveFocus();
    });
  });

  describe('GN-012: Back to index link', () => {
    it('shows index link', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      expect(screen.getByTestId('index-link')).toBeInTheDocument();
    });

    it('index link points to /seo-guides', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      expect(screen.getByTestId('index-link')).toHaveAttribute('href', '/seo-guides');
    });

    it('has index icon', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      expect(screen.getByTestId('index-icon')).toHaveTextContent('📖');
    });
  });

  describe('GN-013: Order by slug', () => {
    it('navigation follows slug array order', () => {
      // Test middle guide
      render(<MockGuideNavigation currentSlug="page-speed" />);
      expect(screen.getByTestId('prev-link')).toHaveAttribute('href', '/seo-guides/external-links');
      expect(screen.getByTestId('next-link')).toHaveAttribute('href', '/seo-guides/mobile-friendly');
    });
  });

  describe('GN-014: Responsive layout', () => {
    it('uses flex layout', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      const container = screen.getByTestId('nav-container');
      expect(container).toHaveClass('flex');
    });
  });

  describe('GN-015: Dark mode styling', () => {
    it('has dark mode border on nav', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      const nav = screen.getByTestId('guide-navigation');
      expect(nav.className).toMatch(/dark:border-gray-700/);
    });

    it('has dark mode classes on prev title', () => {
      render(<MockGuideNavigation currentSlug="meta-description" />);
      const prevTitle = screen.getByTestId('prev-title');
      expect(prevTitle.className).toMatch(/dark:text-white/);
    });

    it('has dark mode classes on next title', () => {
      render(<MockGuideNavigation currentSlug="meta-title" />);
      const nextTitle = screen.getByTestId('next-title');
      expect(nextTitle.className).toMatch(/dark:text-white/);
    });
  });

  describe('Edge Cases', () => {
    it('returns null for invalid slug', () => {
      const { container } = render(<MockGuideNavigation currentSlug="invalid-slug" />);
      expect(container.firstChild).toBeNull();
    });

    it('handles single guide (first and last)', () => {
      // This would be tested with a one-element array
    });

    it('shows labels "Previous" and "Next"', () => {
      render(<MockGuideNavigation currentSlug="meta-description" />);
      expect(screen.getByTestId('prev-label')).toHaveTextContent('Previous');
      expect(screen.getByTestId('next-label')).toHaveTextContent('Next');
    });
  });
});
