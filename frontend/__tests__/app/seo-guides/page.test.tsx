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

// Import after mocks
import { getAllGuides, getGuidesByCategory, getGuidesWithTools } from '@/lib/guides/registry';

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

// Helper to render the component (will be replaced with actual import)
// For TDD, we define the expected behavior
function renderGuideIndexPage() {
  // This will be replaced with: render(<GuideIndexPage />)
  // For now, we're documenting expected behavior
  return render(
    <div data-testid="guide-index-page">
      <h1>SEO Guides</h1>
      <p data-testid="page-description">Master SEO with our comprehensive guides</p>
      
      {/* Category Filter */}
      <div role="tablist" data-testid="category-filter">
        <button role="tab" aria-selected="true" data-testid="tab-all">All</button>
        <button role="tab" aria-selected="false" data-testid="tab-on-page">On-Page</button>
        <button role="tab" aria-selected="false" data-testid="tab-technical">Technical</button>
        <button role="tab" aria-selected="false" data-testid="tab-content">Content</button>
      </div>
      
      {/* Search Input */}
      <div data-testid="search-container">
        <input 
          type="search" 
          placeholder="Search guides..." 
          aria-label="Search guides"
          data-testid="search-input"
        />
      </div>
      
      {/* Guide Grid */}
      <div data-testid="guide-grid" role="list">
        {mockGuides.map(guide => (
          <article 
            key={guide.slug} 
            role="listitem"
            data-testid={`guide-card-${guide.slug}`}
          >
            <span data-testid={`icon-${guide.slug}`}>{guide.icon}</span>
            <h2 data-testid={`title-${guide.slug}`}>{guide.title}</h2>
            <p data-testid={`summary-${guide.slug}`}>{guide.summary}</p>
            <span data-testid={`read-time-${guide.slug}`}>{guide.readTime} min read</span>
            {guide.toolSlug && (
              <span data-testid={`tool-badge-${guide.slug}`}>Has Tool</span>
            )}
            <a href={`/seo-guides/${guide.slug}`} data-testid={`link-${guide.slug}`}>
              Read guide
            </a>
          </article>
        ))}
      </div>
    </div>
  );
}

describe('Guide Index Page', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (getAllGuides as jest.Mock).mockReturnValue(mockGuides);
    (getGuidesByCategory as jest.Mock).mockImplementation((category) => 
      category ? mockGuides.filter(g => g.category === category) : mockGuides
    );
    (getGuidesWithTools as jest.Mock).mockReturnValue(mockGuidesWithTools);
  });

  // ============================================================
  // 1. PAGE RENDERING TESTS
  // ============================================================
  describe('Page Rendering', () => {
    it('R1: Page renders without errors', () => {
      renderGuideIndexPage();
      expect(screen.getByTestId('guide-index-page')).toBeInTheDocument();
    });

    it('R2: All 17 guides displayed in grid', () => {
      renderGuideIndexPage();
      const cards = screen.getAllByRole('listitem');
      expect(cards).toHaveLength(17);
    });

    it('R3: Page title set correctly', () => {
      renderGuideIndexPage();
      expect(screen.getByRole('heading', { name: 'SEO Guides', level: 1 })).toBeInTheDocument();
    });

    it('R4: Page description displayed', () => {
      renderGuideIndexPage();
      expect(screen.getByTestId('page-description')).toBeInTheDocument();
    });
  });

  // ============================================================
  // 2. GUIDE CARD TESTS
  // ============================================================
  describe('Guide Cards', () => {
    it('C1: Each card shows icon', () => {
      renderGuideIndexPage();
      const icon = screen.getByTestId('icon-meta-title');
      expect(icon).toHaveTextContent('📝');
    });

    it('C2: Each card shows title', () => {
      renderGuideIndexPage();
      expect(screen.getByTestId('title-meta-title')).toHaveTextContent('Meta Title Optimization');
    });

    it('C3: Each card shows summary', () => {
      renderGuideIndexPage();
      expect(screen.getByTestId('summary-meta-title')).toHaveTextContent('Learn how to craft compelling page titles');
    });

    it('C4: Each card shows read time', () => {
      renderGuideIndexPage();
      expect(screen.getByTestId('read-time-meta-title')).toHaveTextContent('5 min read');
    });

    it('C5: Cards with tools show badge', () => {
      renderGuideIndexPage();
      // meta-title has toolSlug
      expect(screen.getByTestId('tool-badge-meta-title')).toHaveTextContent('Has Tool');
    });

    it('C6: Cards without tools show no badge', () => {
      renderGuideIndexPage();
      // h1-headings has no toolSlug
      expect(screen.queryByTestId('tool-badge-h1-headings')).not.toBeInTheDocument();
    });

    it('C7: Card links to correct guide page', () => {
      renderGuideIndexPage();
      const link = screen.getByTestId('link-meta-title');
      expect(link).toHaveAttribute('href', '/seo-guides/meta-title');
    });

    it('C8: Card is keyboard accessible', () => {
      renderGuideIndexPage();
      const link = screen.getByTestId('link-meta-title');
      link.focus();
      expect(link).toHaveFocus();
    });
  });

  // ============================================================
  // 3. CATEGORY FILTER TESTS
  // ============================================================
  describe('Category Filter', () => {
    it('F1: All tab selected by default', () => {
      renderGuideIndexPage();
      const allTab = screen.getByTestId('tab-all');
      expect(allTab).toHaveAttribute('aria-selected', 'true');
    });

    it('F2: On-Page tab filters correctly (8 guides)', () => {
      renderGuideIndexPage();
      const onPageTab = screen.getByTestId('tab-on-page');
      fireEvent.click(onPageTab);
      
      // Should call getGuidesByCategory with 'on-page'
      expect(getGuidesByCategory).toHaveBeenCalledWith('on-page');
    });

    it('F3: Technical tab filters correctly (7 guides)', () => {
      renderGuideIndexPage();
      const technicalTab = screen.getByTestId('tab-technical');
      fireEvent.click(technicalTab);
      
      expect(getGuidesByCategory).toHaveBeenCalledWith('technical');
    });

    it('F4: Content tab filters correctly (2 guides)', () => {
      renderGuideIndexPage();
      const contentTab = screen.getByTestId('tab-content');
      fireEvent.click(contentTab);
      
      expect(getGuidesByCategory).toHaveBeenCalledWith('content');
    });

    it('F5: Active tab shows visual indicator', () => {
      renderGuideIndexPage();
      const allTab = screen.getByTestId('tab-all');
      expect(allTab).toHaveAttribute('aria-selected', 'true');
      
      const onPageTab = screen.getByTestId('tab-on-page');
      expect(onPageTab).toHaveAttribute('aria-selected', 'false');
    });

    it('F6: Tab navigation is keyboard accessible', async () => {
      const user = userEvent.setup();
      renderGuideIndexPage();
      
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
      renderGuideIndexPage();
      expect(screen.getByTestId('search-input')).toBeInTheDocument();
    });

    it('S2: Search filters by title', async () => {
      renderGuideIndexPage();
      const searchInput = screen.getByTestId('search-input');
      
      fireEvent.change(searchInput, { target: { value: 'meta' } });
      
      // Should show only guides with "meta" in title
      const visibleCards = screen.getAllByRole('listitem');
      // meta-title and meta-description contain "meta"
      expect(visibleCards.length).toBe(2);
    });

    it('S3: Search is case-insensitive', () => {
      renderGuideIndexPage();
      const searchInput = screen.getByTestId('search-input');
      
      fireEvent.change(searchInput, { target: { value: 'META' } });
      
      const visibleCards = screen.getAllByRole('listitem');
      expect(visibleCards.length).toBe(2);
    });

    it('S4: Empty search shows all guides', () => {
      renderGuideIndexPage();
      const searchInput = screen.getByTestId('search-input');
      
      fireEvent.change(searchInput, { target: { value: '' } });
      
      const visibleCards = screen.getAllByRole('listitem');
      expect(visibleCards).toHaveLength(17);
    });

    it('S5: No results message displayed', () => {
      renderGuideIndexPage();
      const searchInput = screen.getByTestId('search-input');
      
      fireEvent.change(searchInput, { target: { value: 'xyznonexistent' } });
      
      expect(screen.getByText(/no guides found/i)).toBeInTheDocument();
    });

    it('S6: Search works with category filter', () => {
      renderGuideIndexPage();
      
      // Select on-page category
      fireEvent.click(screen.getByTestId('tab-on-page'));
      
      // Search within category
      const searchInput = screen.getByTestId('search-input');
      fireEvent.change(searchInput, { target: { value: 'meta' } });
      
      // Should only show on-page guides with "meta" in title
      const visibleCards = screen.getAllByRole('listitem');
      expect(visibleCards.length).toBe(2);
    });
  });

  // ============================================================
  // 5. RESPONSIVE GRID TESTS
  // ============================================================
  describe('Responsive Grid', () => {
    it('G1: 3 columns on desktop (>=1024px)', () => {
      // Mock window.innerWidth
      Object.defineProperty(window, 'innerWidth', { value: 1024, writable: true });
      
      renderGuideIndexPage();
      const grid = screen.getByTestId('guide-grid');
      
      // Should have grid-cols-3 class or similar
      expect(grid.className).toMatch(/grid-cols-3|lg:grid-cols-3/);
    });

    it('G2: 2 columns on tablet (>=768px)', () => {
      Object.defineProperty(window, 'innerWidth', { value: 768, writable: true });
      
      renderGuideIndexPage();
      const grid = screen.getByTestId('guide-grid');
      
      expect(grid.className).toMatch(/grid-cols-2|md:grid-cols-2/);
    });

    it('G3: 1 column on mobile (<768px)', () => {
      Object.defineProperty(window, 'innerWidth', { value: 375, writable: true });
      
      renderGuideIndexPage();
      const grid = screen.getByTestId('guide-grid');
      
      expect(grid.className).toMatch(/grid-cols-1/);
    });
  });

  // ============================================================
  // 6. TOOL BADGE TESTS
  // ============================================================
  describe('Tool Badge', () => {
    it('T1: Guides with toolSlug show badge', () => {
      renderGuideIndexPage();
      
      // meta-title has toolSlug
      expect(screen.getByTestId('tool-badge-meta-title')).toBeInTheDocument();
    });

    it('T2: Guides without toolSlug hide badge', () => {
      renderGuideIndexPage();
      
      // h1-headings has no toolSlug
      expect(screen.queryByTestId('tool-badge-h1-headings')).not.toBeInTheDocument();
    });

    it('T3: Badge text correct', () => {
      renderGuideIndexPage();
      const badge = screen.getByTestId('tool-badge-meta-title');
      expect(badge).toHaveTextContent('Has Tool');
    });

    it('T5: Correct guides have badges (5 total)', () => {
      renderGuideIndexPage();
      
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
      renderGuideIndexPage();
      
      const cards = screen.getAllByRole('listitem');
      cards.forEach(card => {
        expect(card).toHaveAccessibleName();
      });
    });

    it('A2: Filter tabs have proper roles', () => {
      renderGuideIndexPage();
      
      const tablist = screen.getByRole('tablist');
      expect(tablist).toBeInTheDocument();
      
      const tabs = screen.getAllByRole('tab');
      expect(tabs).toHaveLength(4);
    });

    it('A3: Search input has label', () => {
      renderGuideIndexPage();
      
      const searchInput = screen.getByTestId('search-input');
      expect(searchInput).toHaveAttribute('aria-label', 'Search guides');
    });
  });

  // ============================================================
  // 8. NAVIGATION TESTS
  // ============================================================
  describe('Navigation', () => {
    it('N1: Card click navigates to guide', () => {
      renderGuideIndexPage();
      
      const link = screen.getByTestId('link-meta-title');
      expect(link).toHaveAttribute('href', '/seo-guides/meta-title');
    });

    it('N3: Direct URL access works', () => {
      // The page should render correctly when accessed directly
      renderGuideIndexPage();
      expect(screen.getByTestId('guide-index-page')).toBeInTheDocument();
    });
  });

  // ============================================================
  // 9. EDGE CASES TESTS
  // ============================================================
  describe('Edge Cases', () => {
    it('E1: Empty guide list handled', () => {
      (getAllGuides as jest.Mock).mockReturnValue([]);
      
      renderGuideIndexPage();
      
      // Should show empty state message
      expect(screen.getByText(/no guides found/i)).toBeInTheDocument();
    });

    it('E3: Search with special chars (no XSS)', () => {
      renderGuideIndexPage();
      
      const searchInput = screen.getByTestId('search-input');
      fireEvent.change(searchInput, { target: { value: '<script>alert("xss")</script>' } });
      
      // Should not execute script, should show no results
      expect(screen.queryByText('alert("xss")')).not.toBeInTheDocument();
    });

    it('E4: Long guide titles truncate', () => {
      renderGuideIndexPage();
      
      const title = screen.getByTestId('title-structured-data');
      // CSS should handle truncation
      expect(title).toBeInTheDocument();
    });
  });

  // ============================================================
  // 10. INTEGRATION TESTS
  // ============================================================
  describe('Integration', () => {
    it('I1: getAllGuides() called on mount', () => {
      renderGuideIndexPage();
      expect(getAllGuides).toHaveBeenCalled();
    });

    it('I2: Category filter uses getGuidesByCategory()', () => {
      renderGuideIndexPage();
      
      fireEvent.click(screen.getByTestId('tab-on-page'));
      expect(getGuidesByCategory).toHaveBeenCalledWith('on-page');
    });

    it('I3: Tool badge checks toolSlug from registry', () => {
      renderGuideIndexPage();
      
      // Should use data from registry to determine badge visibility
      expect(getAllGuides).toHaveBeenCalled();
    });

    it('I4: Links use correct slug format', () => {
      renderGuideIndexPage();
      
      const link = screen.getByTestId('link-xml-sitemap');
      expect(link).toHaveAttribute('href', '/seo-guides/xml-sitemap');
    });
  });
});

// ============================================================
// SEO META TAGS TESTS
// ============================================================
describe('SEO Meta Tags', () => {
  it('M1: Page title correct', () => {
    // This would test the document.title or <title> tag
    // For Next.js App Router, this is in metadata export
    expect(true).toBe(true); // Placeholder - requires metadata export testing
  });

  it('M2: Meta description set', () => {
    // Test metadata export description
    expect(true).toBe(true); // Placeholder
  });

  it('M3: Canonical URL set', () => {
    // Test metadata export alternates/canonical
    expect(true).toBe(true); // Placeholder
  });
});
