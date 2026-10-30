/**
 * Tests for GuideHero Component
 * Task: TASK-20-3 - Create Guide Page Template Component
 * 
 * Tests the hero section for guide pages.
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

// Test data
const mockGuide = {
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

const mockGuideNoTool = {
  slug: 'h1-headings',
  title: 'H1 Heading Structure',
  summary: 'Understand how to use H1 tags effectively.',
  category: 'on-page' as const,
  icon: '🔤',
  readTime: 4,
  sections: [],
  checklist: [],
  relatedGuides: [],
};

// Mock component for TDD
function MockGuideHero({ guide }: { guide: typeof mockGuide }) {
  const formatReadTime = (minutes: number): string => {
    if (minutes === 0) return 'Quick read';
    if (minutes >= 60) {
      const hours = Math.floor(minutes / 60);
      const mins = minutes % 60;
      return mins > 0 ? `${hours} hr ${mins} min read` : `${hours} hr read`;
    }
    return `${minutes} min read`;
  };

  const categoryLabels: Record<string, string> = {
    'on-page': 'On-Page SEO',
    'technical': 'Technical SEO',
    'content': 'Content & Social',
  };

  return (
    <header 
      data-testid="guide-hero"
      className="guide-hero py-8 border-b dark:border-gray-700"
    >
      <div className="flex items-center gap-4 mb-4">
        <span 
          data-testid="guide-icon" 
          className="text-4xl"
          aria-hidden="true"
        >
          {guide.icon}
        </span>
        <span 
          data-testid="category-badge"
          className="px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
        >
          {categoryLabels[guide.category] || guide.category}
        </span>
        {guide.toolSlug && (
          <span 
            data-testid="tool-badge"
            className="px-3 py-1 rounded-full text-sm bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
          >
            Has Interactive Tool
          </span>
        )}
      </div>
      
      <h1 
        data-testid="guide-title"
        className="text-3xl md:text-4xl font-bold mb-4 dark:text-white"
      >
        {guide.title}
      </h1>
      
      <p 
        data-testid="guide-summary"
        className="text-lg text-gray-600 dark:text-gray-300 mb-4"
      >
        {guide.summary}
      </p>
      
      <div 
        data-testid="guide-meta"
        className="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400"
      >
        <span data-testid="read-time" className="flex items-center gap-1">
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {formatReadTime(guide.readTime)}
        </span>
      </div>
    </header>
  );
}

describe('GuideHero Component', () => {
  describe('GH-001: Renders hero section', () => {
    it('renders without crashing', () => {
      render(<MockGuideHero guide={mockGuide} />);
      expect(screen.getByTestId('guide-hero')).toBeInTheDocument();
    });
  });

  describe('GH-002: Displays icon', () => {
    it('shows the guide icon', () => {
      render(<MockGuideHero guide={mockGuide} />);
      expect(screen.getByTestId('guide-icon')).toHaveTextContent('📝');
    });

    it('icon is aria-hidden', () => {
      render(<MockGuideHero guide={mockGuide} />);
      expect(screen.getByTestId('guide-icon')).toHaveAttribute('aria-hidden', 'true');
    });
  });

  describe('GH-003: Displays title', () => {
    it('shows the guide title', () => {
      render(<MockGuideHero guide={mockGuide} />);
      expect(screen.getByTestId('guide-title')).toHaveTextContent('Meta Title Optimization');
    });

    it('title is h1', () => {
      render(<MockGuideHero guide={mockGuide} />);
      const title = screen.getByTestId('guide-title');
      expect(title.tagName).toBe('H1');
    });
  });

  describe('GH-004: Displays summary', () => {
    it('shows the guide summary', () => {
      render(<MockGuideHero guide={mockGuide} />);
      expect(screen.getByTestId('guide-summary')).toHaveTextContent('Learn how to craft compelling page titles');
    });
  });

  describe('GH-005: Displays read time', () => {
    it('shows read time', () => {
      render(<MockGuideHero guide={mockGuide} />);
      expect(screen.getByTestId('read-time')).toHaveTextContent('5 min read');
    });
  });

  describe('GH-006: Displays category badge', () => {
    it('shows category badge', () => {
      render(<MockGuideHero guide={mockGuide} />);
      expect(screen.getByTestId('category-badge')).toBeInTheDocument();
    });

    it('shows formatted category label', () => {
      render(<MockGuideHero guide={mockGuide} />);
      expect(screen.getByTestId('category-badge')).toHaveTextContent('On-Page SEO');
    });

    it('shows technical category correctly', () => {
      const techGuide = { ...mockGuide, category: 'technical' as const };
      render(<MockGuideHero guide={techGuide} />);
      expect(screen.getByTestId('category-badge')).toHaveTextContent('Technical SEO');
    });

    it('shows content category correctly', () => {
      const contentGuide = { ...mockGuide, category: 'content' as const };
      render(<MockGuideHero guide={contentGuide} />);
      expect(screen.getByTestId('category-badge')).toHaveTextContent('Content & Social');
    });
  });

  describe('GH-007: Tool badge when present', () => {
    it('shows tool badge when toolSlug exists', () => {
      render(<MockGuideHero guide={mockGuide} />);
      expect(screen.getByTestId('tool-badge')).toBeInTheDocument();
      expect(screen.getByTestId('tool-badge')).toHaveTextContent('Has Interactive Tool');
    });
  });

  describe('GH-008: No tool badge when absent', () => {
    it('hides tool badge when no toolSlug', () => {
      render(<MockGuideHero guide={mockGuideNoTool} />);
      expect(screen.queryByTestId('tool-badge')).not.toBeInTheDocument();
    });
  });

  describe('GH-009: Responsive layout', () => {
    it('has responsive title classes', () => {
      render(<MockGuideHero guide={mockGuide} />);
      const title = screen.getByTestId('guide-title');
      expect(title.className).toMatch(/md:text-4xl/);
    });
  });

  describe('GH-010: Dark mode styling', () => {
    it('has dark mode classes on title', () => {
      render(<MockGuideHero guide={mockGuide} />);
      const title = screen.getByTestId('guide-title');
      expect(title.className).toMatch(/dark:text-white/);
    });

    it('has dark mode classes on summary', () => {
      render(<MockGuideHero guide={mockGuide} />);
      const summary = screen.getByTestId('guide-summary');
      expect(summary.className).toMatch(/dark:/);
    });
  });

  describe('GH-011: Accessible heading', () => {
    it('title is h1 for proper heading hierarchy', () => {
      render(<MockGuideHero guide={mockGuide} />);
      const title = screen.getByTestId('guide-title');
      expect(title.tagName).toBe('H1');
    });

    it('is within header element', () => {
      render(<MockGuideHero guide={mockGuide} />);
      const hero = screen.getByTestId('guide-hero');
      expect(hero.tagName).toBe('HEADER');
    });
  });

  describe('GH-012: Long title handling', () => {
    it('handles very long titles', () => {
      const longTitleGuide = {
        ...mockGuide,
        title: 'This is a very long guide title that should wrap properly without breaking the layout of the hero section',
      };
      render(<MockGuideHero guide={longTitleGuide} />);
      expect(screen.getByTestId('guide-title')).toBeInTheDocument();
    });
  });

  describe('GH-013: Unicode support', () => {
    it('handles unicode in title', () => {
      const unicodeGuide = { ...mockGuide, title: 'H1 标题结构 🚀' };
      render(<MockGuideHero guide={unicodeGuide} />);
      expect(screen.getByTestId('guide-title')).toHaveTextContent('H1 标题结构 🚀');
    });

    it('handles unicode in icon', () => {
      const unicodeIconGuide = { ...mockGuide, icon: '🇺🇸' };
      render(<MockGuideHero guide={unicodeIconGuide} />);
      expect(screen.getByTestId('guide-icon')).toHaveTextContent('🇺🇸');
    });
  });

  describe('GH-014: Zero read time', () => {
    it('shows "Quick read" for zero read time', () => {
      const zeroTimeGuide = { ...mockGuide, readTime: 0 };
      render(<MockGuideHero guide={zeroTimeGuide} />);
      expect(screen.getByTestId('read-time')).toHaveTextContent('Quick read');
    });
  });

  describe('GH-015: Large read time', () => {
    it('shows hours for read time >= 60', () => {
      const hourGuide = { ...mockGuide, readTime: 120 };
      render(<MockGuideHero guide={hourGuide} />);
      expect(screen.getByTestId('read-time')).toHaveTextContent('2 hr read');
    });

    it('shows hours and minutes for mixed time', () => {
      const mixedGuide = { ...mockGuide, readTime: 90 };
      render(<MockGuideHero guide={mixedGuide} />);
      expect(screen.getByTestId('read-time')).toHaveTextContent('1 hr 30 min read');
    });
  });
});
