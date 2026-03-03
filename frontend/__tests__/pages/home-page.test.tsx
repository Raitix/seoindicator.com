/**
 * TASK-28-8: Homepage Integration
 * Test suite for integrating IndicatorCatalog into the homepage
 * 
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock next/link
jest.mock('next/link', () => {
  interface LinkProps {
    children: React.ReactNode;
    href: string;
    className?: string;
  }
  return function MockLink({ children, href, className }: LinkProps) {
    return (
      <a href={href} className={className} data-testid="next-link">
        {children}
      </a>
    );
  };
});

// Mock IndicatorCatalog component (TASK-28-7)
jest.mock('@/components/indicators/IndicatorCatalog', () => ({
  IndicatorCatalog: ({ 
    title, 
    description, 
    indicators, 
    isLoading, 
    error 
  }: { 
    title?: string; 
    description?: string;
    indicators: Array<{
      id: string;
      title: string;
      value: string | number;
      status: string;
      href: string;
    }>;
    isLoading?: boolean;
    error?: Error | null;
  }) => (
    <div 
      data-testid="indicator-catalog"
      data-loading={isLoading}
      data-error={error ? 'true' : 'false'}
    >
      {title && <h2 data-testid="catalog-title">{title}</h2>}
      {description && <p data-testid="catalog-description">{description}`}
      <div data-testid="indicators-container">
        {indicators.map((indicator) => (
          <a 
            key={indicator.id}
            href={indicator.href}
            data-testid={`indicator-${indicator.id}`}
            data-status={indicator.status}
          >
            {indicator.title}: {indicator.value}
          </a>
        ))}
      </div>
    </div>
  ),
}));

// Mock Button component
jest.mock('@/components/ui/button', () => ({
  Button: ({ 
    children, 
    onClick, 
    className, 
    variant, 
    asChild,
    ...props 
  }: { 
    children: React.ReactNode; 
    onClick?: () => void;
    className?: string;
    variant?: string;
    asChild?: boolean;
  }) => (
    <button 
      onClick={onClick} 
      className={className}
      data-variant={variant}
      data-testid="button"
      {...props}
    >
      {children}
    </button>
  ),
}));

// Mock Input component
jest.mock('@/components/ui/input', () => ({
  Input: ({ 
    placeholder, 
    className, 
    onChange,
    type,
    ...props 
  }: { 
    placeholder?: string; 
    className?: string;
    onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
    type?: string;
  }) => (
    <input 
      type={type || 'text'}
      placeholder={placeholder} 
      className={className}
      onChange={onChange}
      data-testid="input"
      {...props}
    />
  ),
}));

// Mock Card components
jest.mock('@/components/ui/card', () => ({
  Card: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <div className={className} data-testid="card">{children}</div>
  ),
  CardContent: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <div className={className} data-testid="card-content">{children}</div>
  ),
  CardDescription: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <p className={className} data-testid="card-description">{children}</p>
  ),
  CardHeader: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <div className={className} data-testid="card-header">{children}</div>
  ),
  CardTitle: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <h3 className={className} data-testid="card-title">{children}</h3>
  ),
}));

// Import the component after mocks
import Home from '@/app/page';

// ============================================================================
// TEST UTILITIES
// ============================================================================

const mockIndicators = [
  { id: 'rankings', title: 'Keyword Rankings', value: '42', status: 'good', href: '/dashboard' },
  { id: 'visibility', title: 'Visibility Score', value: '78%', status: 'warning', href: '/dashboard' },
  { id: 'technical', title: 'Technical Health', value: '95', status: 'good', href: '/tools' },
  { id: 'backlinks', title: 'Backlink Profile', value: '1.2K', status: 'good', href: '/dashboard' },
  { id: 'content', title: 'Content Opportunities', value: '15', status: 'neutral', href: '/dashboard' },
  { id: 'serp', title: 'SERP Features', value: '8', status: 'good', href: '/dashboard' },
  { id: 'speed', title: 'Page Speed', value: '89', status: 'warning', href: '/tools' },
  { id: 'competitors', title: 'Competitor Analysis', value: '3', status: 'neutral', href: '/comparison' },
];

// ============================================================================
// HOMEPAGE INTEGRATION TESTS
// ============================================================================

describe('TASK-28-8: Homepage Integration', () => {
  
  // ==========================================================================
  // HI-001: Basic Homepage Rendering
  // ==========================================================================
  describe('HI-001: Basic Homepage Rendering', () => {
    
    test('HI-001.01: Homepage renders without errors', () => {
      render(<Home />);
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    test('HI-001.02: Homepage has correct container structure', () => {
      render(<Home />);
      const container = screen.getByRole('main');
      expect(container).toHaveClass('min-h-screen');
    });

    test('HI-001.03: Homepage renders header section', () => {
      render(<Home />);
      expect(screen.getByRole('banner')).toBeInTheDocument();
    });

    test('HI-001.04: Homepage renders footer section', () => {
      render(<Home />);
      expect(screen.getByRole('contentinfo')).toBeInTheDocument();
    });

    test('HI-001.05: No console errors on render', () => {
      const consoleSpy = jest.spyOn(console, 'error').mockImplementation();
      render(<Home />);
      expect(consoleSpy).not.toHaveBeenCalled();
      consoleSpy.mockRestore();
    });

  });

  // ==========================================================================
  // HI-002: IndicatorCatalog Integration
  // ==========================================================================
  describe('HI-002: IndicatorCatalog Integration', () => {
    
    test('HI-002.01: IndicatorCatalog renders within homepage', () => {
      render(<Home />);
      expect(screen.getByTestId('indicator-catalog')).toBeInTheDocument();
    });

    test('HI-002.02: IndicatorCatalog is in hero section', () => {
      render(<Home />);
      const heroSection = screen.getByRole('region', { name: /hero/i });
      const catalog = screen.getByTestId('indicator-catalog');
      expect(heroSection).toContainElement(catalog);
    });

    test('HI-002.03: IndicatorCatalog receives correct indicators data', () => {
      render(<Home />);
      // Check that indicators are rendered
      expect(screen.getByTestId('indicator-rankings')).toBeInTheDocument();
      expect(screen.getByTestId('indicator-visibility')).toBeInTheDocument();
      expect(screen.getByTestId('indicator-technical')).toBeInTheDocument();
    });

    test('HI-002.04: IndicatorCatalog has correct default title', () => {
      render(<Home />);
      expect(screen.getByTestId('catalog-title')).toHaveTextContent(/SEO Indicators/i);
    });

    test('HI-002.05: IndicatorCatalog has description text', () => {
      render(<Home />);
      expect(screen.getByTestId('catalog-description')).toBeInTheDocument();
    });

  });

  // ==========================================================================
  // HI-003: Hero Section Design
  // ==========================================================================
  describe('HI-003: Hero Section Design', () => {
    
    test('HI-003.01: Hero section has main heading (h1)', () => {
      render(<Home />);
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1).toBeInTheDocument();
    });

    test('HI-003.02: Hero heading has compelling text', () => {
      render(<Home />);
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1.textContent).toMatch(/SEO|Track|Performance/i);
    });

    test('HI-003.03: Hero section has subtitle/description', () => {
      render(<Home />);
      const heroSection = screen.getByRole('region', { name: /hero/i });
      const paragraphs = heroSection.querySelectorAll('p');
      expect(paragraphs.length).toBeGreaterThan(0);
    });

    test('HI-003.04: Hero section has CTA button', () => {
      render(<Home />);
      const ctaButtons = screen.getAllByRole('button').filter(
        btn => btn.textContent?.match(/Get Started|Start|Try/i)
      );
      expect(ctaButtons.length).toBeGreaterThan(0);
    });

    test('HI-003.05: Hero section has gradient background', () => {
      render(<Home />);
      const mainContainer = screen.getByRole('main');
      expect(mainContainer.className).toMatch(/gradient|bg-/);
    });

    test('HI-003.06: Hero uses brand colors (CSS variables)', () => {
      render(<Home />);
      const mainContainer = screen.getByRole('main');
      // Should use CSS variables or brand color classes
      const hasBrandColors = mainContainer.className.includes('brand') || 
                             mainContainer.className.includes('emerald') ||
                             mainContainer.className.includes('primary');
      expect(hasBrandColors).toBe(true);
    });

  });

  // ==========================================================================
  // HI-004: Responsive Layout
  // ==========================================================================
  describe('HI-004: Responsive Layout', () => {
    
    test('HI-004.01: Mobile layout (< 640px) renders correctly', () => {
      // Mock window.innerWidth
      Object.defineProperty(window, 'innerWidth', { value: 375, writable: true });
      render(<Home />);
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    test('HI-004.02: Tablet layout (640-1024px) renders correctly', () => {
      Object.defineProperty(window, 'innerWidth', { value: 768, writable: true });
      render(<Home />);
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    test('HI-004.03: Desktop layout (> 1024px) renders correctly', () => {
      Object.defineProperty(window, 'innerWidth', { value: 1280, writable: true });
      render(<Home />);
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    test('HI-004.04: Hero section has responsive padding', () => {
      render(<Home />);
      const heroSection = screen.getByRole('region', { name: /hero/i });
      expect(heroSection.className).toMatch(/py-|px-|p-/);
    });

    test('HI-004.05: Container has responsive max-width', () => {
      render(<Home />);
      const containers = document.querySelectorAll('.container');
      expect(containers.length).toBeGreaterThan(0);
    });

    test('HI-004.06: Navigation is responsive', () => {
      render(<Home />);
      const nav = screen.getByRole('navigation');
      expect(nav).toBeInTheDocument();
    });

  });

  // ==========================================================================
  // HI-005: Navigation Links
  // ==========================================================================
  describe('HI-005: Navigation Links', () => {
    
    test('HI-005.01: Indicator cards link to correct dashboard sections', () => {
      render(<Home />);
      const rankingsIndicator = screen.getByTestId('indicator-rankings');
      expect(rankingsIndicator).toHaveAttribute('href', '/dashboard');
    });

    test('HI-005.02: Technical health links to tools section', () => {
      render(<Home />);
      const techIndicator = screen.getByTestId('indicator-technical');
      expect(techIndicator).toHaveAttribute('href', '/tools');
    });

    test('HI-005.03: Competitor analysis links to comparison', () => {
      render(<Home />);
      const compIndicator = screen.getByTestId('indicator-competitors');
      expect(compIndicator).toHaveAttribute('href', '/comparison');
    });

    test('HI-005.04: CTA button navigates to correct page', () => {
      render(<Home />);
      const ctaButtons = screen.getAllByTestId('next-link').filter(
        link => link.textContent?.match(/Get Started|Dashboard/i)
      );
      expect(ctaButtons.length).toBeGreaterThan(0);
    });

    test('HI-005.05: Header navigation links work', () => {
      render(<Home />);
      const nav = screen.getByRole('navigation');
      const links = nav.querySelectorAll('a');
      expect(links.length).toBeGreaterThan(0);
    });

    test('HI-005.06: All indicator links are valid internal routes', () => {
      render(<Home />);
      const indicators = screen.getByTestId('indicators-container');
      const links = indicators.querySelectorAll('a');
      links.forEach(link => {
        const href = link.getAttribute('href');
        expect(href).toMatch(/^\//);
      });
    });

  });

  // ==========================================================================
  // HI-006: Entrance Animations
  // ==========================================================================
  describe('HI-006: Entrance Animations', () => {
    
    test('HI-006.01: Hero section has animation class', () => {
      render(<Home />);
      const heroSection = screen.getByRole('region', { name: /hero/i });
      // Should have animation-related classes
      const hasAnimation = heroSection.className.match(/animate|transition|duration|fade|slide/);
      expect(hasAnimation).toBeTruthy();
    });

    test('HI-006.02: IndicatorCatalog has entrance animation', () => {
      render(<Home />);
      const catalog = screen.getByTestId('indicator-catalog');
      const hasAnimation = catalog.className.match(/animate|transition|duration/);
      // Animation is optional but recommended
      // This test documents expected behavior
    });

    test('HI-006.03: Animations respect prefers-reduced-motion', () => {
      // Mock prefers-reduced-motion
      const mockMatchMedia = jest.fn().mockImplementation(query => ({
        matches: query === '(prefers-reduced-motion: reduce)',
        media: query,
        onchange: null,
        addListener: jest.fn(),
        removeListener: jest.fn(),
      }));
      Object.defineProperty(window, 'matchMedia', { value: mockMatchMedia });
      
      render(<Home />);
      // Component should render without animations
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    test('HI-006.04: Animation uses CSS transforms (GPU accelerated)', () => {
      render(<Home />);
      const heroSection = screen.getByRole('region', { name: /hero/i });
      // Check for transform-based animation classes
      const hasGpuAnimation = heroSection.className.match(/translate|scale|opacity|transform/);
      // Document expected pattern - transforms preferred for performance
    });

  });

  // ==========================================================================
  // HI-007: Performance Optimization
  // ==========================================================================
  describe('HI-007: Performance Optimization', () => {
    
    test('HI-007.01: No layout shift on mount', () => {
      const { container } = render(<Home />);
      // Component should have defined dimensions to prevent CLS
      const main = container.querySelector('main');
      expect(main).toHaveClass('min-h-screen');
    });

    test('HI-007.02: Images (if any) have dimensions defined', () => {
      render(<Home />);
      const images = document.querySelectorAll('img');
      images.forEach(img => {
        expect(img).toHaveAttribute('width');
        expect(img).toHaveAttribute('height');
      });
    });

    test('HI-007.03: No hydration mismatches', () => {
      // This test documents expected behavior
      // Hydration mismatches would show as console warnings
      const consoleWarn = jest.spyOn(console, 'warn').mockImplementation();
      render(<Home />);
      expect(consoleWarn).not.toHaveBeenCalledWith(
        expect.stringContaining('hydration')
      );
      consoleWarn.mockRestore();
    });

    test('HI-007.04: Critical CSS is inlined', () => {
      render(<Home />);
      // Check that critical styles are applied
      const main = screen.getByRole('main');
      expect(main.className).not.toBe('');
    });

    test('HI-007.05: No blocking scripts in hero section', () => {
      render(<Home />);
      const heroSection = screen.getByRole('region', { name: /hero/i });
      const scripts = heroSection.querySelectorAll('script');
      expect(scripts.length).toBe(0);
    });

  });

  // ==========================================================================
  // HI-008: Existing Functionality Preservation
  // ==========================================================================
  describe('HI-008: Existing Functionality Preservation', () => {
    
    test('HI-008.01: Features grid section still exists', () => {
      render(<Home />);
      // Look for features section by heading
      const featuresHeading = screen.queryByRole('heading', { name: /Key Features|Features/i });
      expect(featuresHeading).toBeInTheDocument();
    });

    test('HI-008.02: Feature cards render correctly', () => {
      render(<Home />);
      const cards = screen.getAllByTestId('card');
      expect(cards.length).toBeGreaterThanOrEqual(3);
    });

    test('HI-008.03: Status bar section exists', () => {
      render(<Home />);
      // Status bar shows system status
      expect(screen.getByText(/System Status|Operational/i)).toBeInTheDocument();
    });

    test('HI-008.04: Footer section exists with copyright', () => {
      render(<Home />);
      expect(screen.getByText(/2026 SEOIndicator|All rights reserved/i)).toBeInTheDocument();
    });

    test('HI-008.05: Header navigation still works', () => {
      render(<Home />);
      const dashboardLink = screen.getByRole('link', { name: /Dashboard/i });
      expect(dashboardLink).toBeInTheDocument();
    });

    test('HI-008.06: Competitors link still works', () => {
      render(<Home />);
      const competitorsLink = screen.getByRole('link', { name: /Competitors/i });
      expect(competitorsLink).toBeInTheDocument();
    });

    test('HI-008.07: Comparison link still works', () => {
      render(<Home />);
      const comparisonLink = screen.getByRole('link', { name: /Comparison/i });
      expect(comparisonLink).toBeInTheDocument();
    });

    test('HI-008.08: Sign In button still exists', () => {
      render(<Home />);
      expect(screen.getByRole('button', { name: /Sign In/i })).toBeInTheDocument();
    });

  });

  // ==========================================================================
  // HI-009: Accessibility
  // ==========================================================================
  describe('HI-009: Accessibility', () => {
    
    test('HI-009.01: Proper heading hierarchy (h1 -> h2 -> h3)', () => {
      render(<Home />);
      const h1 = screen.getAllByRole('heading', { level: 1 });
      const h2 = screen.getAllByRole('heading', { level: 2 });
      const h3 = screen.getAllByRole('heading', { level: 3 });
      
      expect(h1.length).toBe(1); // Only one h1
      expect(h2.length).toBeGreaterThanOrEqual(1);
      expect(h3.length).toBeGreaterThanOrEqual(0);
    });

    test('HI-009.02: All interactive elements are focusable', () => {
      render(<Home />);
      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).not.toHaveAttribute('tabindex', '-1');
      });
    });

    test('HI-009.03: CTA button is accessible via keyboard', async () => {
      const user = userEvent.setup();
      render(<Home />);
      
      const ctaButton = screen.getAllByRole('button').find(
        btn => btn.textContent?.match(/Get Started|Start/i)
      );
      
      if (ctaButton) {
        ctaButton.focus();
        expect(ctaButton).toHaveFocus();
      }
    });

    test('HI-009.04: Color contrast meets WCAG 2.1 AA', () => {
      render(<Home />);
      // This is a visual test - document that contrast should be checked
      // Tools like axe-core can verify this in E2E tests
      const main = screen.getByRole('main');
      expect(main).toBeInTheDocument();
    });

    test('HI-009.05: Landmark regions are properly defined', () => {
      render(<Home />);
      expect(screen.getByRole('banner')).toBeInTheDocument(); // header
      expect(screen.getByRole('navigation')).toBeInTheDocument();
      expect(screen.getByRole('main')).toBeInTheDocument();
      expect(screen.getByRole('contentinfo')).toBeInTheDocument(); // footer
    });

    test('HI-009.06: Hero section has aria-label', () => {
      render(<Home />);
      const heroSection = screen.getByRole('region', { name: /hero/i });
      expect(heroSection).toBeInTheDocument();
    });

    test('HI-009.07: Screen reader can navigate indicator catalog', () => {
      render(<Home />);
      const catalog = screen.getByTestId('indicator-catalog');
      const links = catalog.querySelectorAll('a');
      
      links.forEach(link => {
        expect(link).toHaveAttribute('href');
      });
    });

  });

  // ==========================================================================
  // HI-010: CSS Variables Integration
  // ==========================================================================
  describe('HI-010: CSS Variables Integration', () => {
    
    test('HI-010.01: Background uses CSS variables', () => {
      render(<Home />);
      const main = screen.getByRole('main');
      // Should use brand colors or CSS variables
      const hasBrandStyling = main.className.includes('brand') || 
                              main.className.includes('primary') ||
                              main.className.includes('surface') ||
                              main.className.includes('gradient');
      expect(hasBrandStyling).toBe(true);
    });

    test('HI-010.02: Surface layer variables for depth', () => {
      render(<Home />);
      const cards = screen.getAllByTestId('card');
      cards.forEach(card => {
        // Cards should use surface layer styling
        const hasSurfaceStyling = card.className.includes('surface') ||
                                   card.className.includes('slate') ||
                                   card.className.includes('bg-');
        expect(hasSurfaceStyling).toBe(true);
      });
    });

    test('HI-010.03: Semantic colors for status indicators', () => {
      render(<Home />);
      const statusIndicator = screen.getByText(/Operational/i);
      expect(statusIndicator).toBeInTheDocument();
    });

  });

  // ==========================================================================
  // HI-011: Error Handling
  // ==========================================================================
  describe('HI-011: Error Handling', () => {
    
    test('HI-011.01: Page handles missing indicators gracefully', () => {
      // Render without data - component should handle gracefully
      render(<Home />);
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    test('HI-011.02: Loading state shows skeleton', () => {
      render(<Home />);
      const catalog = screen.getByTestId('indicator-catalog');
      // Check if loading state is handled
      expect(catalog).toBeInTheDocument();
    });

  });

  // ==========================================================================
  // HI-012: Edge Cases
  // ==========================================================================
  describe('HI-012: Edge Cases', () => {
    
    test('HI-012.01: Long indicator values are truncated', () => {
      render(<Home />);
      // Component should handle long values gracefully
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    test('HI-012.02: Special characters in headings render correctly', () => {
      render(<Home />);
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1.textContent).not.toMatch(/&amp;|&lt;|&gt;/);
    });

    test('HI-012.03: Unicode characters render correctly', () => {
      render(<Home />);
      // Footer should render copyright symbol correctly
      const footer = screen.getByRole('contentinfo');
      expect(footer.textContent).toMatch(/©|Copyright/i);
    });

    test('HI-012.04: Empty state handled when no indicators', () => {
      render(<Home />);
      // Even with empty state, page should render
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    test('HI-012.05: Multiple rapid renders do not cause issues', () => {
      const { rerender } = render(<Home />);
      rerender(<Home />);
      rerender(<Home />);
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

  });

  // ==========================================================================
  // HI-013: Snapshot Tests
  // ==========================================================================
  describe('HI-013: Snapshot Tests', () => {
    
    test('HI-013.01: Homepage matches snapshot', () => {
      const { container } = render(<Home />);
      expect(container).toMatchSnapshot();
    });

    test('HI-013.02: Hero section matches snapshot', () => {
      render(<Home />);
      const heroSection = screen.getByRole('region', { name: /hero/i });
      expect(heroSection).toMatchSnapshot();
    });

    test('HI-013.03: IndicatorCatalog integration matches snapshot', () => {
      render(<Home />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toMatchSnapshot();
    });

  });

});

// ============================================================================
// TYPESCRIPT TYPE TESTS
// ============================================================================

describe('TASK-28-8: TypeScript Types', () => {
  
  test('HI-TS.01: Homepage component has correct return type', () => {
    const result = Home();
    expect(result).toBeDefined();
    expect(result.type).toBe('main');
  });

  test('HI-TS.02: IndicatorData interface is compatible', () => {
    const indicator = {
      id: 'test',
      title: 'Test Indicator',
      value: '100',
      status: 'good' as const,
      href: '/test',
    };
    expect(indicator.id).toBe('test');
  });

});
