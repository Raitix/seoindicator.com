/**
 * Tests for GuideLayout Component
 * Task: TASK-20-3 - Create Guide Page Template Component
 * 
 * Tests the wrapper component that provides consistent layout for guide pages.
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Test data
const mockGuide = {
  slug: 'meta-title',
  title: 'Meta Title Optimization',
  summary: 'Learn how to craft compelling page titles.',
  category: 'on-page' as const,
  toolSlug: 'meta-tag-analyzer',
  icon: '📝',
  readTime: 5,
  sections: [],
  checklist: [],
  relatedGuides: [],
};

// Mock component for TDD (will be replaced with actual import)
function MockGuideLayout({ 
  children, 
  guide 
}: { 
  children: React.ReactNode;
  guide: typeof mockGuide;
}) {
  return (
    <main 
      data-testid="guide-layout"
      className="guide-layout container mx-auto px-4 py-8 max-w-4xl"
    >
      <article 
        itemScope 
        itemType="https://schema.org/Article"
        data-testid="guide-article"
      >
        <script 
          type="application/ld+json"
          data-testid="guide-jsonld"
          dangerouslySetInnerHTML={{
            __html: JSON.stringify({
              '@context': 'https://schema.org',
              '@type': 'Article',
              headline: guide.title,
              description: guide.summary,
            }),
          }}
        />
        {children}
      </article>
    </main>
  );
}

describe('GuideLayout Component', () => {
  describe('GL-001: Renders children', () => {
    it('renders children content', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div data-testid="child-content">Test Content</div>
        </MockGuideLayout>
      );
      expect(screen.getByTestId('child-content')).toBeInTheDocument();
      expect(screen.getByText('Test Content')).toBeInTheDocument();
    });
  });

  describe('GL-002: Applies correct classes', () => {
    it('has layout container classes', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      const layout = screen.getByTestId('guide-layout');
      expect(layout).toHaveClass('guide-layout');
      expect(layout).toHaveClass('container');
    });
  });

  describe('GL-003: Responsive container', () => {
    it('uses responsive max-width', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      const layout = screen.getByTestId('guide-layout');
      expect(layout).toHaveClass('max-w-4xl');
    });
  });

  describe('GL-004: Proper spacing', () => {
    it('has vertical padding', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      const layout = screen.getByTestId('guide-layout');
      expect(layout).toHaveClass('py-8');
    });

    it('has horizontal padding', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      const layout = screen.getByTestId('guide-layout');
      expect(layout).toHaveClass('px-4');
    });
  });

  describe('GL-005: Dark mode support', () => {
    it('supports dark mode classes', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      const layout = screen.getByTestId('guide-layout');
      // Should have dark mode compatible classes
      expect(layout.className).toMatch(/dark:/);
    });
  });

  describe('GL-006: With guide prop', () => {
    it('accepts guide prop', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      // Component should render without errors
      expect(screen.getByTestId('guide-layout')).toBeInTheDocument();
    });
  });

  describe('GL-007: Accessibility landmark', () => {
    it('has main landmark', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      const layout = screen.getByTestId('guide-layout');
      expect(layout.tagName).toBe('MAIN');
    });

    it('has article element for semantic structure', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      const article = screen.getByTestId('guide-article');
      expect(article.tagName).toBe('ARTICLE');
    });
  });

  describe('GL-008: SEO structured data', () => {
    it('renders JSON-LD schema', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      const jsonLd = screen.getByTestId('guide-jsonld');
      expect(jsonLd).toHaveAttribute('type', 'application/ld+json');
    });

    it('includes article schema with headline', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      const jsonLd = screen.getByTestId('guide-jsonld');
      const schema = JSON.parse(jsonLd.textContent || '{}');
      expect(schema['@type']).toBe('Article');
      expect(schema.headline).toBe('Meta Title Optimization');
    });

    it('includes article schema with description', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      const jsonLd = screen.getByTestId('guide-jsonld');
      const schema = JSON.parse(jsonLd.textContent || '{}');
      expect(schema.description).toBe('Learn how to craft compelling page titles.');
    });
  });

  describe('Edge Cases', () => {
    it('handles empty children gracefully', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          {null}
        </MockGuideLayout>
      );
      expect(screen.getByTestId('guide-layout')).toBeInTheDocument();
    });

    it('handles multiple children', () => {
      render(
        <MockGuideLayout guide={mockGuide}>
          <div data-testid="child-1">First</div>
          <div data-testid="child-2">Second</div>
          <div data-testid="child-3">Third</div>
        </MockGuideLayout>
      );
      expect(screen.getByTestId('child-1')).toBeInTheDocument();
      expect(screen.getByTestId('child-2')).toBeInTheDocument();
      expect(screen.getByTestId('child-3')).toBeInTheDocument();
    });

    it('handles guide with minimal fields', () => {
      const minimalGuide = {
        slug: 'test',
        title: 'Test',
        summary: 'Test summary',
        category: 'on-page' as const,
        icon: '📄',
        readTime: 0,
        sections: [],
        checklist: [],
        relatedGuides: [],
      };
      render(
        <MockGuideLayout guide={minimalGuide}>
          <div>Content</div>
        </MockGuideLayout>
      );
      expect(screen.getByTestId('guide-layout')).toBeInTheDocument();
    });
  });
});
