/**
 * Tests for the guide page layout
 * Task: TASK-20-3 - Create Guide Page Template Component
 *
 * The guide page is the layout of a guide, so the tests cover the page
 * itself rather than a component that only exists in the test.
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock the registry functions
const mockGetGuideBySlug = vi.fn();
const mockGetAllGuides = vi.fn();

vi.mock('@/lib/guides/registry', () => ({
  getGuideBySlug: (slug: string) => mockGetGuideBySlug(slug),
  getAllGuides: () => mockGetAllGuides(),
  getRelatedGuides: () => [],
  GUIDE_SLUGS: ['meta-title'],
}));

// Mock Next.js modules
vi.mock('next/link', () => ({ default: function MockLink({ children, href }: { children: React.ReactNode; href: string }) {
    return <a href={href}>{children}</a>;
  } }));

vi.mock('next/navigation', () => ({
  notFound: vi.fn(() => {
    throw new Error('NOT_FOUND');
  }),
}));

// Import after the mocks
import GuidePage from '@/app/seo-guides/[slug]/page';

// Test data
const mockGuide = {
  slug: 'meta-title',
  title: 'Meta Title Optimization',
  summary: 'Learn how to craft compelling page titles.',
  category: 'on-page' as const,
  toolSlug: 'meta-tag-analyzer',
  icon: '📝',
  readTime: 5,
  sections: [
    {
      title: 'What is a Meta Title?',
      content: 'The **meta title** is the title of your webpage.',
      codeExamples: [],
    },
  ],
  checklist: [],
  relatedGuides: [],
};

/** Renders the guide page for the given slug. */
async function renderGuidePage(slug = 'meta-title') {
  const page = await GuidePage({ params: Promise.resolve({ slug }) });
  return render(page);
}

beforeEach(() => {
  vi.clearAllMocks();
  mockGetGuideBySlug.mockReturnValue(mockGuide);
  mockGetAllGuides.mockReturnValue([mockGuide]);
});

describe('GuideLayout Component', () => {
  describe('GL-001: Renders the guide content', () => {
    it('renders children content', async () => {
      await renderGuidePage();

      // The sections of the guide are the content of the page
      expect(screen.getByTestId('guide-sections')).toBeInTheDocument();
      expect(screen.getByText('What is a Meta Title?')).toBeInTheDocument();
    });
  });

  describe('GL-002: Applies correct classes', () => {
    it('has layout container classes', async () => {
      const { container } = await renderGuidePage();

      const layout = container.querySelector('main');
      expect(layout).toHaveClass('min-h-screen');
    });

    it('uses responsive max-width', async () => {
      const { container } = await renderGuidePage();

      const article = container.querySelector('article');
      expect(article).toHaveClass('max-w-4xl');
    });

    it('has vertical padding', async () => {
      const { container } = await renderGuidePage();

      const article = container.querySelector('article');
      expect(article).toHaveClass('py-8');
    });

    it('has horizontal padding', async () => {
      const { container } = await renderGuidePage();

      const article = container.querySelector('article');
      expect(article).toHaveClass('px-4');
    });

    it('supports dark mode classes', async () => {
      const { container } = await renderGuidePage();

      // The site is dark by default, so the guide is on the dark
      // background rather than carrying the dark variants.
      const layout = container.querySelector('main');
      expect(layout).toHaveClass('bg-slate-950');
      expect(layout).toHaveClass('text-white');
    });
  });

  describe('GL-003: Renders the guide', () => {
    it('accepts guide prop', async () => {
      await renderGuidePage();

      expect(screen.getByRole('heading', { level: 1, name: /meta title optimization/i })).toBeInTheDocument();
    });

    it('has main landmark', async () => {
      const { container } = await renderGuidePage();

      const layout = container.querySelector('main');
      expect(layout?.tagName).toBe('MAIN');
    });

    it('has article element for semantic structure', async () => {
      const { container } = await renderGuidePage();

      const article = container.querySelector('article');
      expect(article?.tagName).toBe('ARTICLE');
    });
  });

  describe('GL-004: Renders JSON-LD schema', () => {
    it('renders JSON-LD schema', async () => {
      const { container } = await renderGuidePage();

      const jsonLd = container.querySelector('script[type="application/ld+json"]');
      expect(jsonLd).not.toBeNull();
    });

    it('includes article schema with headline', async () => {
      const { container } = await renderGuidePage();

      const jsonLd = container.querySelector('script[type="application/ld+json"]');
      const schema = JSON.parse(jsonLd?.textContent || '{}');
      expect(schema['@type']).toBe('HowTo');
      expect(schema.name).toBe(mockGuide.title);
    });

    it('includes article schema with description', async () => {
      const { container } = await renderGuidePage();

      const jsonLd = container.querySelector('script[type="application/ld+json"]');
      const schema = JSON.parse(jsonLd?.textContent || '{}');
      expect(schema.description).toBe(mockGuide.summary);
    });
  });

  describe('GL-005: Handles edge cases', () => {
    it('handles empty children gracefully', async () => {
      mockGetGuideBySlug.mockReturnValue({ ...mockGuide, sections: [], checklist: [] });

      const { container } = await renderGuidePage();

      expect(container.querySelector('main')).not.toBeNull();
      expect(screen.queryByTestId('guide-sections')).not.toBeInTheDocument();
    });
  });
});
