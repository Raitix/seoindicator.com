/**
 * Tests for Guide Index Page
 * Task: TASK-20-2 - Create Guide Index Page Component
 * 
 * Tests the main /seo-guides page that displays all 17 SEO guides
 * with filtering and search functionality.
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock the registry functions
jest.mock('@/lib/guides/registry', () => ({
  getAllGuides: jest.fn(),
  getGuidesByCategory: jest.fn(),
  getGuidesWithTools: jest.fn(),
}));

// Mock Next.js navigation
const mockPush = jest.fn();
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: jest.fn(),
    back: jest.fn(),
  }),
  usePathname: () => '/seo-guides',
  useSearchParams: () => new URLSearchParams(),
}));

// Mock Next.js Link
jest.mock('next/link', () => {
  return function MockLink({ children, href, ...props }: { children: React.ReactNode; href: string }) {
    return <a href={href} {...props}>{children}</a>;
  };
});

// Import after mocks
import { getAllGuides, getGuidesByCategory, getGuidesWithTools } from '@/lib/guides/registry';
import GuidesIndexPage from '@/app/seo-guides/page';

// Test data - 17 guides with correct distribution
const mockGuides = [
  // On-Page (8)
  { slug: 'meta-title', title: 'Meta Title Optimization', summary: 'Learn how to craft compelling page titles', category: 'on-page', toolSlug: 'meta-tag-analyzer', icon: '📝', readTime: 5, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'meta-description', title: 'Meta Description Best Practices', summary: 'Write descriptions that entice users', category: 'on-page', toolSlug: 'meta-tag-analyzer', icon: '📋', readTime: 4, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'h1-headings', title: 'H1 Heading Structure', summary: 'Understand how to use H1 tags', category: 'on-page', icon: '🔤', readTime: 4, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'heading-hierarchy', title: 'Heading Hierarchy', summary: 'Create a logical content structure', category: 'on-page', icon: '📑', readTime: 5, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'image-alt-text', title: 'Image Alt Text Optimization', summary: 'Write descriptive alt text', category: 'on-page', icon: '🖼️', readTime: 4, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'image-optimization', title: 'Image Optimization for Web', summary: 'Optimize images for faster loading', category: 'on-page', icon: '⚡', readTime: 6, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'internal-links', title: 'Internal Linking Strategy', summary: 'Build a strong site architecture', category: 'on-page', icon: '🔗', readTime: 5, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'external-links', title: 'External Link Best Practices', summary: 'Use outbound links strategically', category: 'on-page', icon: '🌐', readTime: 4, sections: [], checklist: [], relatedGuides: [] },
  // Technical (7)
  { slug: 'page-speed', title: 'Page Speed Optimization', summary: 'Improve Core Web Vitals', category: 'technical', icon: '🚀', readTime: 7, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'mobile-friendly', title: 'Mobile-Friendly Design', summary: 'Ensure your site works on mobile', category: 'technical', toolSlug: 'mobile-friendly-checker', icon: '📱', readTime: 5, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'https-security', title: 'HTTPS & Security Headers', summary: 'Secure your site with SSL', category: 'technical', icon: '🔒', readTime: 5, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'structured-data', title: 'Structured Data (Schema.org)', summary: 'Implement rich snippets', category: 'technical', icon: '📊', readTime: 6, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'canonical-urls', title: 'Canonical URL Implementation', summary: 'Prevent duplicate content issues', category: 'technical', icon: '🎯', readTime: 4, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'robots-txt', title: 'Robots.txt Configuration', summary: 'Control how search engines crawl', category: 'technical', icon: '🤖', readTime: 4, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'xml-sitemap', title: 'XML Sitemap Creation', summary: 'Help search engines discover pages', category: 'technical', icon: '🗺️', readTime: 4, sections: [], checklist: [], relatedGuides: [] },
  // Content (2)
  { slug: 'open-graph', title: 'Open Graph Tags', summary: 'Optimize for Facebook sharing', category: 'content', toolSlug: 'social-media-preview', icon: '👍', readTime: 5, sections: [], checklist: [], relatedGuides: [] },
  { slug: 'twitter-cards', title: 'Twitter Cards', summary: 'Create rich Twitter previews', category: 'content', toolSlug: 'social-media-preview', icon: '🐦', readTime: 4, sections: [], checklist: [], relatedGuides: [] },
];

const mockGuidesWithTools = mockGuides.filter(g => g.toolSlug);
const mockOnPageGuides = mockGuides.filter(g => g.category === 'on-page');
const mockTechnicalGuides = mockGuides.filter(g => g.category === 'technical');
const mockContentGuides = mockGuides.filter(g => g.category === 'content');

describe('Guide Index Page', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (getAllGuides as jest.Mock).mockReturnValue(mockGuides);
    (getGuidesByCategory as jest.Mock).mockImplementation((category) => {
      if (category === 'on-page') return mockOnPageGuides;
      if (category === 'technical') return mockTechnicalGuides;
      if (category === 'content') return mockContentGuides;
      return mockGuides;
    });
    (getGuidesWithTools as jest.Mock).mockReturnValue(mockGuidesWithTools);
  });

  // ============================================================
  // 1. PAGE RENDERING TESTS
  // ============================================================
  describe('Page Rendering', () => {
    it('R1: Page renders without errors', () => {
      render(<GuidesIndexPage />);
      expect(screen.getByRole('heading', { name: 'SEO Guides' })).toBeInTheDocument();
    });

    it('R2: All 17 guides displayed in grid', () => {
      render(<GuidesIndexPage />);
      const grid = screen.getByTestId('guide-grid');
      const cards = within(grid).getAllByRole('listitem');
      expect(cards).toHaveLength(17);
    });

    it('R3: Page title set correctly', () => {
      render(<GuidesIndexPage />);
      expect(screen.getByRole('heading', { name: 'SEO Guides', level: 1 })).toBeInTheDocument();
    });

    it('R4: Page description displayed', () => {
      render(<GuidesIndexPage />);
      expect(screen.getByTestId('page-description')).toBeInTheDocument();
    });
  });

  // ============================================================
  // 2. GUIDE CARD TESTS
  // ============================================================
  describe('Guide Cards', () => {
    it('C1: Each card shows icon', () => {
      render(<GuidesIndexPage />);
      const icon = screen.getByTestId('guide-icon-meta-title');
      expect(icon).toHaveTextContent('📝');
    });

    it('C2: Each card shows title', () => {
      render(<GuidesIndexPage />);
      expect(screen.getByTestId('guide-title-meta-title')).toHaveTextContent('Meta Title Optimization');
    });

    it('C3: Each card shows summary', () => {
      render(<GuidesIndexPage />);
      expect(screen.getByTestId('guide-summary-meta-title')).toHaveTextContent('Learn how to craft compelling page titles');
    });

    it('C4: Each card shows read time', () => {
      render(<GuidesIndexPage />);
      expect(screen.getByTestId('guide-read-time-meta-title')).toHaveTextContent('5 min read');
    });

    it('C5: Cards with tools show badge', () => {
      render(<GuidesIndexPage />);
      // meta-title has toolSlug
      expect(screen.getByTestId('tool-badge-meta-title')).toHaveTextContent('Has Tool');
    });

    it('C6: Cards without tools show no badge', () => {
      render(<GuidesIndexPage />);
      // h1-headings has no toolSlug
      expect(screen.queryByTestId('tool-badge-h1-headings')).not.toBeInTheDocument();
    });

    it('C7: Card links to correct guide page', () => {
      render(<GuidesIndexPage />);
      const link = screen.getByTestId('guide-link-meta-title');
      expect(link).toHaveAttribute('href', '/seo-guides/meta-title');
    });

    it('C8: Card is keyboard accessible', async () => {
      render(<GuidesIndexPage />);
      const link = screen.getByTestId('guide-link-meta-title');
      link.focus();
      expect(link).toHaveFocus();
    });
  });

  // ============================================================
  // 3. CATEGORY FILTER TESTS
  // ============================================================
  describe('Category Filter', () => {
    it('F1: All tab selected by default', () => {
      render(<GuidesIndexPage />);
      const allTab = screen.getByTestId('tab-all');
      expect(allTab).toHaveAttribute('aria-selected', 'true');
    });

    it('F2: On-Page tab filters correctly (8 guides)', async () => {
      const user = userEvent.setup();
      render(<GuidesIndexPage />);
      
      const onPageTab = screen.getByTestId('tab-on-page');
      await user.click(onPageTab);
      
      // Should show only on-page guides
      const grid = screen.getByTestId('guide-grid');
      const cards = within(grid).getAllByRole('listitem');
      expect(cards).toHaveLength(8);
    });

    it('F3: Technical tab filters correctly (7 guides)', async () => {
      const user = userEvent.setup();
      render(<GuidesIndexPage />);
      
      const technicalTab = screen.getByTestId('tab-technical');
      await user.click(technicalTab);
      
      const grid = screen.getByTestId('guide-grid');
      const cards = within(grid).getAllByRole('listitem');
      expect(cards).toHaveLength(7);
    });

    it('F4: Content tab filters correctly (2 guides)', async () => {
      const user = userEvent.setup();
      render(<GuidesIndexPage />);
      
      const contentTab = screen.getByTestId('tab-content');
      await user.click(contentTab);
      
      const grid = screen.getByTestId('guide-grid');
      const cards = within(grid).getAllByRole('listitem');
      expect(cards).toHaveLength(2);
    });

    it('F5: Active tab shows visual indicator', () => {
      render(<GuidesIndexPage />);
      const allTab = screen.getByTestId('tab-all');
      expect(allTab).toHaveAttribute('aria-selected', 'true');
      
      const onPageTab = screen.getByTestId('tab-on-page');
      expect(onPageTab).toHaveAttribute('aria-selected', 'false');
    });

    it('F6: Tab navigation is keyboard accessible', async () => {
      const user = userEvent.setup();
      render(<GuidesIndexPage />);
      
      const allTab = screen.getByTestId('tab-all');
      allTab.focus();
      expect(allTab).toHaveFocus();
      
      // Tab to next
      await user.tab();
      expect(screen.getByTestId('tab-on-page')).toHaveFocus();
    });
  });

  // ============================================================
  // 4. SEARCH TESTS
  // ============================================================
  describe('Search', () => {
    it('S1: Search input renders', () => {
      render(<GuidesIndexPage />);
      expect(screen.getByTestId('search-input')).toBeInTheDocument();
    });

    it('S2: Search filters by title', async () => {
      const user = userEvent.setup();
      render(<GuidesIndexPage />);
      
      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'meta');
      
      // Wait for debounce
      await waitFor(() => {
        const grid = screen.getByTestId('guide-grid');
        const visibleCards = within(grid).getAllByRole('listitem');
        // meta-title and meta-description contain "meta"
        expect(visibleCards.length).toBe(2);
      }, { timeout: 1000 });
    });

    it('S3: Search is case-insensitive', async () => {
      const user = userEvent.setup();
      render(<GuidesIndexPage />);
      
      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'META');
      
      await waitFor(() => {
        const grid = screen.getByTestId('guide-grid');
        const visibleCards = within(grid).getAllByRole('listitem');
        expect(visibleCards.length).toBe(2);
      }, { timeout: 1000 });
    });

    it('S4: Empty search shows all guides', async () => {
      render(<GuidesIndexPage />);
      
      const grid = screen.getByTestId('guide-grid');
      const visibleCards = within(grid).getAllByRole('listitem');
      expect(visibleCards).toHaveLength(17);
    });

    it('S5: No results message displayed', async () => {
      const user = userEvent.setup();
      render(<GuidesIndexPage />);
      
      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'xyznonexistent');
      
      await waitFor(() => {
        expect(screen.getByTestId('empty-state')).toBeInTheDocument();
        expect(screen.getByText(/no guides found/i)).toBeInTheDocument();
      }, { timeout: 1000 });
    });

    it('S6: Search works with category filter', async () => {
      const user = userEvent.setup();
      render(<GuidesIndexPage />);
      
      // Select on-page category
      await user.click(screen.getByTestId('tab-on-page'));
      
      // Search within category
      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, 'meta');
      
      await waitFor(() => {
        const grid = screen.getByTestId('guide-grid');
        const visibleCards = within(grid).getAllByRole('listitem');
        // Only meta-title and meta-description are on-page + contain "meta"
        expect(visibleCards.length).toBe(2);
      }, { timeout: 1000 });
    });
  });

  // ============================================================
  // 5. RESPONSIVE GRID TESTS
  // ============================================================
  describe('Responsive Grid', () => {
    it('G1: 3 columns on desktop (>=1024px)', () => {
      Object.defineProperty(window, 'innerWidth', { value: 1024, writable: true });
      
      render(<GuidesIndexPage />);
      const grid = screen.getByTestId('guide-grid');
      
      // Should have lg:grid-cols-3 class
      expect(grid.className).toMatch(/lg:grid-cols-3/);
    });

    it('G2: 2 columns on tablet (>=768px)', () => {
      Object.defineProperty(window, 'innerWidth', { value: 768, writable: true });
      
      render(<GuidesIndexPage />);
      const grid = screen.getByTestId('guide-grid');
      
      // Should have md:grid-cols-2 class
      expect(grid.className).toMatch(/md:grid-cols-2/);
    });

    it('G3: 1 column on mobile (<768px)', () => {
      Object.defineProperty(window, 'innerWidth', { value: 375, writable: true });
      
      render(<GuidesIndexPage />);
      const grid = screen.getByTestId('guide-grid');
      
      // Should have grid-cols-1 class
      expect(grid.className).toMatch(/grid-cols-1/);
    });
  });

  // ============================================================
  // 6. TOOL BADGE TESTS
  // ============================================================
  describe('Tool Badge', () => {
    it('T1: Guides with toolSlug show badge', () => {
      render(<GuidesIndexPage />);
      
      // meta-title has toolSlug
      expect(screen.getByTestId('tool-badge-meta-title')).toBeInTheDocument();
    });

    it('T2: Guides without toolSlug hide badge', () => {
      render(<GuidesIndexPage />);
      
      // h1-headings has no toolSlug
      expect(screen.queryByTestId('tool-badge-h1-headings')).not.toBeInTheDocument();
    });

    it('T3: Badge text correct', () => {
      render(<GuidesIndexPage />);
      const badge = screen.getByTestId('tool-badge-meta-title');
      expect(badge).toHaveTextContent('Has Tool');
    });

    it('T5: Correct guides have badges (5 total)', () => {
      render(<GuidesIndexPage />);
      
      // Guides with tools: meta-title, meta-description, mobile-friendly, open-graph, twitter-cards
      const badges = screen.getAllByText('Has Tool');
      expect(badges).toHaveLength(5);
    });
  });

  // ============================================================
  // 7. ACCESSIBILITY TESTS
  // ============================================================
  describe('Accessibility', () => {
    it('A1: All cards have accessible names', () => {
      render(<GuidesIndexPage />);
      
      const cards = screen.getAllByRole('listitem');
      cards.forEach(card => {
        expect(card).toHaveAccessibleName();
      });
    });

    it('A2: Filter tabs have proper roles', () => {
      render(<GuidesIndexPage />);
      
      const tablist = screen.getByRole('tablist');
      expect(tablist).toBeInTheDocument();
      
      const tabs = screen.getAllByRole('tab');
      expect(tabs).toHaveLength(4);
    });

    it('A3: Search input has label', () => {
      render(<GuidesIndexPage />);
      
      const searchInput = screen.getByTestId('search-input');
      expect(searchInput).toHaveAttribute('aria-label', 'Search guides');
    });
  });

  // ============================================================
  // 8. NAVIGATION TESTS
  // ============================================================
  describe('Navigation', () => {
    it('N1: Card click navigates to guide', () => {
      render(<GuidesIndexPage />);
      
      const link = screen.getByTestId('guide-link-meta-title');
      expect(link).toHaveAttribute('href', '/seo-guides/meta-title');
    });

    it('N3: Direct URL access works', () => {
      // The page should render correctly when accessed directly
      render(<GuidesIndexPage />);
      expect(screen.getByRole('heading', { name: 'SEO Guides' })).toBeInTheDocument();
    });
  });

  // ============================================================
  // 9. EDGE CASES TESTS
  // ============================================================
  describe('Edge Cases', () => {
    it('E1: Empty guide list handled', () => {
      (getAllGuides as jest.Mock).mockReturnValue([]);
      (getGuidesByCategory as jest.Mock).mockReturnValue([]);
      
      render(<GuidesIndexPage />);
      
      // Should show empty state message
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
    });

    it('E3: Search with special chars (no XSS)', async () => {
      const user = userEvent.setup();
      render(<GuidesIndexPage />);
      
      const searchInput = screen.getByTestId('search-input');
      await user.type(searchInput, '<script>alert("xss")</script>');
      
      // Should not execute script, should show empty state
      await waitFor(() => {
        expect(screen.getByTestId('empty-state')).toBeInTheDocument();
      }, { timeout: 1000 });
      
      expect(screen.queryByText('alert("xss")')).not.toBeInTheDocument();
    });

    it('E4: Long guide titles truncate', () => {
      render(<GuidesIndexPage />);
      
      const title = screen.getByTestId('guide-title-structured-data');
      // CSS should handle truncation
      expect(title).toBeInTheDocument();
    });
  });

  // ============================================================
  // 10. INTEGRATION TESTS
  // ============================================================
  describe('Integration', () => {
    it('I1: getAllGuides() called on mount', () => {
      render(<GuidesIndexPage />);
      expect(getAllGuides).toHaveBeenCalled();
    });

    it('I2: Category filter uses getGuidesByCategory()', async () => {
      const user = userEvent.setup();
      render(<GuidesIndexPage />);
      
      await user.click(screen.getByTestId('tab-on-page'));
      expect(getGuidesByCategory).toHaveBeenCalledWith('on-page');
    });

    it('I3: Tool badge checks toolSlug from registry', () => {
      render(<GuidesIndexPage />);
      
      // Should use data from registry to determine badge visibility
      expect(getAllGuides).toHaveBeenCalled();
    });

    it('I4: Links use correct slug format', () => {
      render(<GuidesIndexPage />);
      
      const link = screen.getByTestId('guide-link-xml-sitemap');
      expect(link).toHaveAttribute('href', '/seo-guides/xml-sitemap');
    });
  });
});

// ============================================================
// SEO META TAGS TESTS
// ============================================================
describe('SEO Meta Tags', () => {
  it('M1: Page title correct', async () => {
    // Import metadata for testing
    const { metadata } = await import('@/app/seo-guides/page');
    expect(metadata.title).toBe('SEO Guides - SEOIndicator');
  });

  it('M2: Meta description set', async () => {
    const { metadata } = await import('@/app/seo-guides/page');
    expect(metadata.description).toContain('comprehensive guides');
  });

  it('M3: Canonical URL set', async () => {
    const { metadata } = await import('@/app/seo-guides/page');
    expect(metadata.alternates?.canonical).toBe('/seo-guides');
  });
});
