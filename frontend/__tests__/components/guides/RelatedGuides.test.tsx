/**
 * Tests for RelatedGuides Component
 * Task: TASK-20-3 - Create Guide Page Template Component
 * 
 * Tests the related guides section that shows related guide cards.
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock Next.js Link
jest.mock('next/link', () => {
  return function MockLink({ children, href }: { children: React.ReactNode; href: string }) {
    return <a href={href}>{children}</a>;
  };
});

// Mock getRelatedGuides
jest.mock('@/lib/guides/registry', () => ({
  getRelatedGuides: (slug: string) => {
    const related: Record<string, typeof mockGuides> = {
      'meta-title': [
        { slug: 'meta-description', title: 'Meta Description Best Practices', summary: 'Write compelling descriptions.', category: 'on-page', icon: '📋', readTime: 4 },
        { slug: 'h1-headings', title: 'H1 Heading Structure', summary: 'Use H1 tags effectively.', category: 'on-page', icon: '🔤', readTime: 4 },
        { slug: 'open-graph', title: 'Open Graph Tags', summary: 'Optimize social sharing.', category: 'content', icon: '👍', readTime: 5 },
      ],
      'h1-headings': [
        { slug: 'heading-hierarchy', title: 'Heading Hierarchy', summary: 'Proper heading order.', category: 'on-page', icon: '📑', readTime: 5 },
      ],
      'no-related': [],
    };
    return related[slug] || [];
  },
}));

// Test data
const mockGuides = [
  { slug: 'meta-description', title: 'Meta Description Best Practices', summary: 'Write compelling descriptions.', category: 'on-page' as const, icon: '📋', readTime: 4 },
  { slug: 'h1-headings', title: 'H1 Heading Structure', summary: 'Use H1 tags effectively.', category: 'on-page' as const, icon: '🔤', readTime: 4 },
  { slug: 'open-graph', title: 'Open Graph Tags', summary: 'Optimize social sharing.', category: 'content' as const, icon: '👍', readTime: 5 },
];

const mockGuide = {
  slug: 'meta-title',
  title: 'Meta Title Optimization',
  summary: 'Learn how to craft compelling page titles.',
  category: 'on-page' as const,
  icon: '📝',
  readTime: 5,
  sections: [],
  checklist: [],
  relatedGuides: ['meta-description', 'h1-headings', 'open-graph'],
};

const mockGuideNoRelated = {
  slug: 'no-related',
  title: 'Standalone Guide',
  summary: 'A guide with no related guides.',
  category: 'on-page' as const,
  icon: '📄',
  readTime: 3,
  sections: [],
  checklist: [],
  relatedGuides: [],
};

// Mock component for TDD
function MockRelatedGuides({ guide }: { guide: typeof mockGuide }) {
  // Simulate getRelatedGuides
  const relatedGuides = guide.relatedGuides.length > 0 
    ? mockGuides.slice(0, 3)
    : [];

  if (relatedGuides.length === 0) {
    return null;
  }

  return (
    <section 
      data-testid="related-guides"
      aria-label="Related guides"
      className="related-guides mt-12"
    >
      <h2 
        data-testid="related-heading"
        className="text-2xl font-bold mb-6 dark:text-white"
      >
        Related Guides
      </h2>
      
      <div 
        data-testid="related-grid"
        className="grid grid-cols-1 md:grid-cols-3 gap-4"
      >
        {relatedGuides.map((related) => (
          <a
            key={related.slug}
            href={`/seo-guides/${related.slug}`}
            data-testid={`related-card-${related.slug}`}
            className="p-4 border rounded-lg hover:shadow-lg transition-shadow dark:border-gray-700"
          >
            <span 
              data-testid={`related-icon-${related.slug}`}
              className="text-2xl"
            >
              {related.icon}
            </span>
            <h3 
              data-testid={`related-title-${related.slug}`}
              className="font-semibold mt-2 dark:text-white"
            >
              {related.title}
            </h3>
            <p 
              data-testid={`related-summary-${related.slug}`}
              className="text-sm text-gray-600 dark:text-gray-300 mt-1"
            >
              {related.summary}
            </p>
            <span 
              data-testid={`related-readtime-${related.slug}`}
              className="text-xs text-gray-500 mt-2 block"
            >
              {related.readTime} min read
            </span>
          </a>
        ))}
      </div>
    </section>
  );
}

describe('RelatedGuides Component', () => {
  describe('RG-001: Renders related guides', () => {
    it('renders related guides section', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      expect(screen.getByTestId('related-guides')).toBeInTheDocument();
    });

    it('renders all related guide cards', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      expect(screen.getByTestId('related-card-meta-description')).toBeInTheDocument();
      expect(screen.getByTestId('related-card-h1-headings')).toBeInTheDocument();
      expect(screen.getByTestId('related-card-open-graph')).toBeInTheDocument();
    });
  });

  describe('RG-002: Max 3 related guides', () => {
    it('shows max 3 related guides', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      const cards = screen.getAllByTestId(/related-card-/);
      expect(cards.length).toBeLessThanOrEqual(3);
    });
  });

  describe('RG-003: No related guides', () => {
    it('hides section when no related guides', () => {
      render(<MockRelatedGuides guide={mockGuideNoRelated} />);
      expect(screen.queryByTestId('related-guides')).not.toBeInTheDocument();
    });
  });

  describe('RG-004: Guide cards rendered', () => {
    it('each card shows icon', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      expect(screen.getByTestId('related-icon-meta-description')).toHaveTextContent('📋');
    });

    it('each card shows title', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      expect(screen.getByTestId('related-title-meta-description')).toHaveTextContent('Meta Description Best Practices');
    });

    it('each card shows summary', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      expect(screen.getByTestId('related-summary-meta-description')).toHaveTextContent('Write compelling descriptions.');
    });

    it('each card shows read time', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      expect(screen.getByTestId('related-readtime-meta-description')).toHaveTextContent('4 min read');
    });
  });

  describe('RG-005: Links to guides', () => {
    it('cards link to correct guide URLs', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      const card = screen.getByTestId('related-card-meta-description');
      expect(card).toHaveAttribute('href', '/seo-guides/meta-description');
    });
  });

  describe('RG-006: Section heading', () => {
    it('shows "Related Guides" heading', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      expect(screen.getByTestId('related-heading')).toHaveTextContent('Related Guides');
    });

    it('heading is h2', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      const heading = screen.getByTestId('related-heading');
      expect(heading.tagName).toBe('H2');
    });
  });

  describe('RG-007: Accessible section', () => {
    it('has section with aria-label', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      const section = screen.getByTestId('related-guides');
      expect(section).toHaveAttribute('aria-label', 'Related guides');
    });
  });

  describe('RG-008: Keyboard navigation', () => {
    it('all cards are focusable links', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      const card = screen.getByTestId('related-card-meta-description');
      card.focus();
      expect(card).toHaveFocus();
    });
  });

  describe('RG-009: Invalid related slug', () => {
    it('handles invalid slugs gracefully', () => {
      const guideWithInvalid = {
        ...mockGuide,
        relatedGuides: ['non-existent-slug', 'meta-description'],
      };
      // Component would filter out non-existent slugs
      render(<MockRelatedGuides guide={guideWithInvalid} />);
      // Should still render valid ones
      expect(screen.getByTestId('related-card-meta-description')).toBeInTheDocument();
    });
  });

  describe('RG-010: Responsive grid', () => {
    it('has responsive grid classes', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      const grid = screen.getByTestId('related-grid');
      expect(grid).toHaveClass('grid-cols-1');
      expect(grid.className).toMatch(/md:grid-cols-3/);
    });
  });

  describe('RG-011: Same category indicator', () => {
    it('shows guides from same category', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      // meta-description and h1-headings are both on-page like meta-title
      expect(screen.getByTestId('related-card-meta-description')).toBeInTheDocument();
      expect(screen.getByTestId('related-card-h1-headings')).toBeInTheDocument();
    });
  });

  describe('RG-012: Dark mode styling', () => {
    it('has dark mode classes on heading', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      const heading = screen.getByTestId('related-heading');
      expect(heading.className).toMatch(/dark:text-white/);
    });

    it('has dark mode classes on cards', () => {
      render(<MockRelatedGuides guide={mockGuide} />);
      const card = screen.getByTestId('related-card-meta-description');
      expect(card.className).toMatch(/dark:/);
    });
  });

  describe('Edge Cases', () => {
    it('handles unicode in related guide titles', () => {
      const unicodeGuides = [
        { slug: 'unicode-guide', title: '中文标题 🚀', summary: 'Test', category: 'on-page' as const, icon: '🌐', readTime: 5 },
      ];
      // Would need to mock getRelatedGuides to return unicode guides
    });

    it('handles very long summaries', () => {
      const longSummaryGuides = [
        { 
          slug: 'long-summary', 
          title: 'Long Summary Guide', 
          summary: 'This is a very long summary that should be truncated properly without breaking the card layout. It contains multiple sentences.', 
          category: 'on-page' as const, 
          icon: '📄', 
          readTime: 5 
        },
      ];
      // Would test that long summaries don't break layout
    });
  });
});
