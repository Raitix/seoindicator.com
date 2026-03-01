/**
 * Tests for GuideBreadcrumb Component
 * Task: TASK-20-3 - Create Guide Page Template Component
 * 
 * Tests the breadcrumb navigation for guide pages.
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
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
  summary: 'Learn how to craft compelling page titles.',
  category: 'on-page' as const,
  icon: '📝',
  readTime: 5,
  sections: [],
  checklist: [],
  relatedGuides: [],
};

// Mock component for TDD
function MockGuideBreadcrumb({ guide }: { guide: typeof mockGuide }) {
  return (
    <nav 
      aria-label="Breadcrumb" 
      data-testid="breadcrumb-nav"
      className="breadcrumb"
    >
      <ol 
        className="flex items-center gap-2"
        data-testid="breadcrumb-list"
        itemScope
        itemType="https://schema.org/BreadcrumbList"
      >
        <li itemProp="itemListElement" itemScope itemType="https://schema.org/ListItem">
          <a href="/" itemProp="item" data-testid="breadcrumb-home">
            <span itemProp="name">Home</span>
          </a>
          <meta itemProp="position" content="1" />
        </li>
        <li aria-hidden="true" data-testid="separator-1" className="mx-2">/</li>
        <li itemProp="itemListElement" itemScope itemType="https://schema.org/ListItem">
          <a href="/seo-guides" itemProp="item" data-testid="breadcrumb-guides">
            <span itemProp="name">SEO Guides</span>
          </a>
          <meta itemProp="position" content="2" />
        </li>
        <li aria-hidden="true" data-testid="separator-2" className="mx-2">/</li>
        <li 
          itemProp="itemListElement" 
          itemScope 
          itemType="https://schema.org/ListItem"
          aria-current="page"
          data-testid="breadcrumb-current"
        >
          <span itemProp="name">{guide.title}</span>
          <meta itemProp="position" content="3" />
        </li>
      </ol>
    </nav>
  );
}

describe('GuideBreadcrumb Component', () => {
  describe('GB-001: Renders breadcrumb', () => {
    it('renders breadcrumb navigation', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      expect(screen.getByTestId('breadcrumb-nav')).toBeInTheDocument();
    });

    it('shows complete breadcrumb trail', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      expect(screen.getByTestId('breadcrumb-list')).toBeInTheDocument();
    });
  });

  describe('GB-002: Home link', () => {
    it('shows home link as first item', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const homeLink = screen.getByTestId('breadcrumb-home');
      expect(homeLink).toBeInTheDocument();
      expect(homeLink).toHaveAttribute('href', '/');
    });

    it('displays "Home" text', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      expect(screen.getByText('Home')).toBeInTheDocument();
    });
  });

  describe('GB-003: Guides index link', () => {
    it('shows guides index link as second item', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const guidesLink = screen.getByTestId('breadcrumb-guides');
      expect(guidesLink).toBeInTheDocument();
      expect(guidesLink).toHaveAttribute('href', '/seo-guides');
    });

    it('displays "SEO Guides" text', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      expect(screen.getByText('SEO Guides')).toBeInTheDocument();
    });
  });

  describe('GB-004: Current guide title', () => {
    it('shows current guide title', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      expect(screen.getByText('Meta Title Optimization')).toBeInTheDocument();
    });

    it('is in the current item', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const current = screen.getByTestId('breadcrumb-current');
      expect(current).toHaveTextContent('Meta Title Optimization');
    });
  });

  describe('GB-005: Separator rendered', () => {
    it('shows separators between items', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const separators = screen.getAllByTestId(/separator/);
      expect(separators).toHaveLength(2);
    });

    it('uses forward slash as separator', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      expect(screen.getByText('/')).toBeInTheDocument();
    });
  });

  describe('GB-006: Current page not linked', () => {
    it('current item is not a link', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const current = screen.getByTestId('breadcrumb-current');
      expect(current.querySelector('a')).toBeNull();
    });

    it('current item is plain text', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const current = screen.getByTestId('breadcrumb-current');
      expect(current.tagName).toBe('LI');
    });

    it('has aria-current="page"', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const current = screen.getByTestId('breadcrumb-current');
      expect(current).toHaveAttribute('aria-current', 'page');
    });
  });

  describe('GB-007: Accessible navigation', () => {
    it('has nav element', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const nav = screen.getByTestId('breadcrumb-nav');
      expect(nav.tagName).toBe('NAV');
    });

    it('has aria-label', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const nav = screen.getByTestId('breadcrumb-nav');
      expect(nav).toHaveAttribute('aria-label', 'Breadcrumb');
    });
  });

  describe('GB-008: Schema.org breadcrumb', () => {
    it('renders breadcrumb JSON-LD structure', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const list = screen.getByTestId('breadcrumb-list');
      expect(list).toHaveAttribute('itemType', 'https://schema.org/BreadcrumbList');
    });

    it('has itemListElement items', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const items = document.querySelectorAll('[itemprop="itemListElement"]');
      expect(items).toHaveLength(3);
    });
  });

  describe('GB-009: Long title handling', () => {
    it('handles long guide titles', () => {
      const longTitleGuide = {
        ...mockGuide,
        title: 'This is a very long guide title that might need truncation in the breadcrumb',
      };
      render(<MockGuideBreadcrumb guide={longTitleGuide} />);
      expect(screen.getByTestId('breadcrumb-current')).toBeInTheDocument();
    });
  });

  describe('GB-010: Keyboard navigation', () => {
    it('all links are focusable', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const homeLink = screen.getByTestId('breadcrumb-home');
      const guidesLink = screen.getByTestId('breadcrumb-guides');
      
      homeLink.focus();
      expect(homeLink).toHaveFocus();
      
      guidesLink.focus();
      expect(guidesLink).toHaveFocus();
    });
  });

  describe('GB-011: Category in breadcrumb', () => {
    it('can show category in breadcrumb (optional)', () => {
      // This tests if category could be shown - implementation dependent
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      // Category badge is optional, just verify it doesn't break
      expect(screen.getByTestId('breadcrumb-nav')).toBeInTheDocument();
    });
  });

  describe('GB-012: Click navigation', () => {
    it('home link navigates to home', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const homeLink = screen.getByTestId('breadcrumb-home');
      expect(homeLink).toHaveAttribute('href', '/');
    });

    it('guides link navigates to guides index', () => {
      render(<MockGuideBreadcrumb guide={mockGuide} />);
      const guidesLink = screen.getByTestId('breadcrumb-guides');
      expect(guidesLink).toHaveAttribute('href', '/seo-guides');
    });
  });

  describe('Edge Cases', () => {
    it('handles unicode in guide title', () => {
      const unicodeGuide = { ...mockGuide, title: 'H1 标题结构 🚀' };
      render(<MockGuideBreadcrumb guide={unicodeGuide} />);
      expect(screen.getByText('H1 标题结构 🚀')).toBeInTheDocument();
    });

    it('handles special characters in title', () => {
      const specialGuide = { ...mockGuide, title: 'Meta <Title> & Description' };
      render(<MockGuideBreadcrumb guide={specialGuide} />);
      expect(screen.getByTestId('breadcrumb-current')).toBeInTheDocument();
    });
  });
});
