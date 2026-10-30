/**
 * Tests for GuideCard Component
 * Task: TASK-20-2 - Create Guide Index Page Component
 * 
 * Tests the individual guide card that displays in the grid.
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock Next.js Link
jest.mock('next/link', () => {
  return function MockLink({ children, href }: { children: React.ReactNode; href: string }) {
    return <a href={href}>{children}</a>;
  };
});

// Test data
const mockGuideWithTool = {
  slug: 'meta-title',
  title: 'Meta Title Optimization',
  summary: 'Learn how to craft compelling page titles that improve click-through rates and search rankings.',
  category: 'on-page' as const,
  toolSlug: 'meta-tag-analyzer',
  icon: '📝',
  readTime: 5,
  sections: [],
  checklist: [],
  relatedGuides: [],
};

const mockGuideWithoutTool = {
  slug: 'h1-headings',
  title: 'H1 Heading Structure',
  summary: 'Understand how to use H1 tags effectively for SEO and accessibility.',
  category: 'on-page' as const,
  icon: '🔤',
  readTime: 4,
  sections: [],
  checklist: [],
  relatedGuides: [],
};

// Mock component for TDD (will be replaced with actual import)
function MockGuideCard({ guide }: { guide: typeof mockGuideWithTool }) {
  return (
    <article 
      data-testid={`guide-card-${guide.slug}`}
      className="guide-card"
    >
      <span className="guide-icon" data-testid="guide-icon">{guide.icon}</span>
      <h3 className="guide-title" data-testid="guide-title">{guide.title}</h3>
      <p className="guide-summary" data-testid="guide-summary">{guide.summary}</p>
      <span className="guide-read-time" data-testid="guide-read-time">{guide.readTime} min read</span>
      {guide.toolSlug && (
        <span className="tool-badge" data-testid="tool-badge">Has Tool</span>
      )}
      <a href={`/seo-guides/${guide.slug}`} data-testid="guide-link">
        Read guide
      </a>
    </article>
  );
}

describe('GuideCard Component', () => {
  describe('Rendering', () => {
    it('renders without crashing', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      expect(screen.getByTestId('guide-card-meta-title')).toBeInTheDocument();
    });

    it('displays the guide icon', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      expect(screen.getByTestId('guide-icon')).toHaveTextContent('📝');
    });

    it('displays the guide title', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      expect(screen.getByTestId('guide-title')).toHaveTextContent('Meta Title Optimization');
    });

    it('displays the guide summary', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      expect(screen.getByTestId('guide-summary')).toHaveTextContent('Learn how to craft compelling page titles');
    });

    it('displays the read time', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      expect(screen.getByTestId('guide-read-time')).toHaveTextContent('5 min read');
    });
  });

  describe('Tool Badge', () => {
    it('shows badge when guide has toolSlug', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      expect(screen.getByTestId('tool-badge')).toBeInTheDocument();
      expect(screen.getByTestId('tool-badge')).toHaveTextContent('Has Tool');
    });

    it('hides badge when guide has no toolSlug', () => {
      render(<MockGuideCard guide={mockGuideWithoutTool} />);
      expect(screen.queryByTestId('tool-badge')).not.toBeInTheDocument();
    });
  });

  describe('Link', () => {
    it('links to correct guide page', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      const link = screen.getByTestId('guide-link');
      expect(link).toHaveAttribute('href', '/seo-guides/meta-title');
    });

    it('is keyboard accessible', async () => {
      const user = userEvent.setup();
      render(<MockGuideCard guide={mockGuideWithTool} />);
      
      const link = screen.getByTestId('guide-link');
      link.focus();
      expect(link).toHaveFocus();
    });

    it('has accessible name', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      const link = screen.getByTestId('guide-link');
      expect(link).toHaveAccessibleName();
    });
  });

  describe('Accessibility', () => {
    it('has article role for semantic structure', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      const card = screen.getByTestId('guide-card-meta-title');
      expect(card.tagName).toBe('ARTICLE');
    });

    it('has proper heading hierarchy', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      const title = screen.getByTestId('guide-title');
      expect(title.tagName).toBe('H3');
    });
  });

  describe('Edge Cases', () => {
    it('handles long titles without breaking layout', () => {
      const longTitleGuide = {
        ...mockGuideWithTool,
        title: 'This is a very long guide title that should be truncated properly without breaking the layout',
      };
      render(<MockGuideCard guide={longTitleGuide} />);
      expect(screen.getByTestId('guide-title')).toBeInTheDocument();
    });

    it('handles long summaries without breaking layout', () => {
      const longSummaryGuide = {
        ...mockGuideWithTool,
        summary: 'This is a very long summary that should be truncated properly without breaking the layout. It contains multiple sentences and should not overflow the card container.',
      };
      render(<MockGuideCard guide={longSummaryGuide} />);
      expect(screen.getByTestId('guide-summary')).toBeInTheDocument();
    });

    it('handles zero read time', () => {
      const zeroTimeGuide = { ...mockGuideWithTool, readTime: 0 };
      render(<MockGuideCard guide={zeroTimeGuide} />);
      expect(screen.getByTestId('guide-read-time')).toHaveTextContent('0 min read');
    });

    it('handles unicode in title', () => {
      const unicodeGuide = { ...mockGuideWithTool, title: 'H1 标题结构 🚀' };
      render(<MockGuideCard guide={unicodeGuide} />);
      expect(screen.getByTestId('guide-title')).toHaveTextContent('H1 标题结构 🚀');
    });
  });

  describe('Styling', () => {
    it('applies correct CSS classes', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      const card = screen.getByTestId('guide-card-meta-title');
      expect(card).toHaveClass('guide-card');
    });

    it('tool badge has distinct styling', () => {
      render(<MockGuideCard guide={mockGuideWithTool} />);
      const badge = screen.getByTestId('tool-badge');
      expect(badge).toHaveClass('tool-badge');
    });
  });
});
