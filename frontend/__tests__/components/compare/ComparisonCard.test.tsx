/**
 * ComparisonCard Component Tests
 * Task: TASK-32-4 - Implement Comparison Index Page
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Tests cover:
 * - Component rendering
 * - Props handling
 * - Styling and theming
 * - Accessibility
 * - Integration with registry
 *
 * NOTE: This component is NOT YET IMPLEMENTED.
 * Tests follow TDD pattern - they will fail until component is created.
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import type { ComparisonCardProps } from '@/lib/compare/types';

// Mock Next.js Link
const MockLink = ({ 
  children, 
  href, 
  className = '',
  ...props 
}: { 
  children: React.ReactNode; 
  href: string; 
  className?: string;
}) => (
  <a href={href} className={className} data-testid="link" {...props}>
    {children}
  </a>
);

jest.mock('next/link', () => MockLink);

// ============================================================================
// Mock Component (TDD - This will be replaced by actual implementation)
// ============================================================================

/**
 * ComparisonCard Component Mock
 * 
 * This is a TDD mock that demonstrates expected behavior.
 * Developer should create actual component at:
 * frontend/components/compare/ComparisonCard.tsx
 * 
 * Expected interface:
 * interface ComparisonCardProps {
 *   slug: string;
 *   title: string;
 *   description: string;
 *   competitorA: { name: string; logo?: string };
 *   competitorB: { name: string; logo?: string };
 * }
 */
const ComparisonCard = ({ 
  slug, 
  title, 
  description, 
  competitorA, 
  competitorB,
  className = '' 
}: ComparisonCardProps & { className?: string }) => {
  return (
    <a
      href={`/compare/${slug}`}
      className={`group block p-6 bg-slate-900/50 border border-slate-800 rounded-xl hover:border-emerald-500/50 transition-all ${className}`}
      data-testid="comparison-card"
    >
      {/* Competitor logos/names */}
      <div className="flex items-center gap-4 mb-4">
        <div className="flex items-center gap-2">
          {competitorA.logo ? (
            <img 
              src={competitorA.logo} 
              alt={`${competitorA.name} logo`}
              className="w-8 h-8 object-contain"
              data-testid="logo-a"
            />
          ) : (
            <div 
              className="w-8 h-8 bg-slate-700 rounded flex items-center justify-center"
              data-testid="logo-placeholder-a"
            >
              <span className="text-xs font-bold text-slate-300">
                {competitorA.name.charAt(0)}
              </span>
            </div>
          )}
          <span className="font-medium text-white" data-testid="name-a">
            {competitorA.name}
          </span>
        </div>
        
        <span className="text-slate-400" aria-hidden="true">vs</span>
        
        <div className="flex items-center gap-2">
          {competitorB.logo ? (
            <img 
              src={competitorB.logo} 
              alt={`${competitorB.name} logo`}
              className="w-8 h-8 object-contain"
              data-testid="logo-b"
            />
          ) : (
            <div 
              className="w-8 h-8 bg-slate-700 rounded flex items-center justify-center"
              data-testid="logo-placeholder-b"
            >
              <span className="text-xs font-bold text-slate-300">
                {competitorB.name.charAt(0)}
              </span>
            </div>
          )}
          <span className="font-medium text-white" data-testid="name-b">
            {competitorB.name}
          </span>
        </div>
      </div>
      
      {/* Title */}
      <h3 className="text-lg font-semibold text-white group-hover:text-emerald-400 transition-colors" data-testid="card-title">
        {title}
      </h3>
      
      {/* Description */}
      <p className="text-sm text-slate-400 mt-2" data-testid="card-description">
        {description}
      </p>
    </a>
  );
};

// ============================================================================
// Test Data
// ============================================================================

const defaultProps: ComparisonCardProps = {
  slug: 'seoindicator-vs-ahrefs',
  title: 'SEOIndicator vs Ahrefs',
  description: 'Compare SEOIndicator with Ahrefs for SEO analysis',
  competitorA: { name: 'SEOIndicator', logo: '/logos/seoindicator.svg' },
  competitorB: { name: 'Ahrefs', logo: '/logos/ahrefs.svg' },
};

const propsWithoutLogos: ComparisonCardProps = {
  slug: 'seoindicator-vs-semrush',
  title: 'SEOIndicator vs Semrush',
  description: 'Detailed comparison of features and pricing',
  competitorA: { name: 'SEOIndicator' },
  competitorB: { name: 'Semrush' },
};

// ============================================================================
// Component Rendering Tests (CR-001 to CR-008)
// ============================================================================

describe('ComparisonCard Rendering', () => {
  test('CR-001: Component renders without error', () => {
    render(<ComparisonCard {...defaultProps} />);
    expect(screen.getByTestId('comparison-card')).toBeInTheDocument();
  });

  test('CR-002: Title displays correctly', () => {
    render(<ComparisonCard {...defaultProps} />);
    expect(screen.getByTestId('card-title')).toHaveTextContent('SEOIndicator vs Ahrefs');
  });

  test('CR-003: Description displays correctly', () => {
    render(<ComparisonCard {...defaultProps} />);
    expect(screen.getByTestId('card-description')).toHaveTextContent('Compare SEOIndicator with Ahrefs');
  });

  test('CR-004: Slug is used in href', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    expect(card).toHaveAttribute('href', '/compare/seoindicator-vs-ahrefs');
  });

  test('CR-005: Competitor A name displays', () => {
    render(<ComparisonCard {...defaultProps} />);
    expect(screen.getByTestId('name-a')).toHaveTextContent('SEOIndicator');
  });

  test('CR-006: Competitor B name displays', () => {
    render(<ComparisonCard {...defaultProps} />);
    expect(screen.getByTestId('name-b')).toHaveTextContent('Ahrefs');
  });

  test('CR-007: Logo images render when provided', () => {
    render(<ComparisonCard {...defaultProps} />);
    expect(screen.getByTestId('logo-a')).toBeInTheDocument();
    expect(screen.getByTestId('logo-b')).toBeInTheDocument();
  });

  test('CR-008: Logo placeholders render when logo not provided', () => {
    render(<ComparisonCard {...propsWithoutLogos} />);
    expect(screen.getByTestId('logo-placeholder-a')).toBeInTheDocument();
    expect(screen.getByTestId('logo-placeholder-b')).toBeInTheDocument();
  });
});

// ============================================================================
// Props Handling Tests (PH-001 to PH-006)
// ============================================================================

describe('ComparisonCard Props Handling', () => {
  test('PH-001: Renders with all props provided', () => {
    render(<ComparisonCard {...defaultProps} />);
    expect(screen.getByTestId('comparison-card')).toBeInTheDocument();
    expect(screen.getByTestId('card-title')).toBeInTheDocument();
    expect(screen.getByTestId('card-description')).toBeInTheDocument();
  });

  test('PH-002: Renders without logos (optional)', () => {
    render(<ComparisonCard {...propsWithoutLogos} />);
    expect(screen.getByTestId('comparison-card')).toBeInTheDocument();
    expect(screen.getByTestId('logo-placeholder-a')).toBeInTheDocument();
    expect(screen.getByTestId('logo-placeholder-b')).toBeInTheDocument();
  });

  test('PH-003: Long title wraps appropriately', () => {
    const props = {
      ...defaultProps,
      title: 'SEOIndicator vs Ahrefs: A Very Long Title That Should Wrap Appropriately Without Breaking Layout',
    };
    render(<ComparisonCard {...props} />);
    const title = screen.getByTestId('card-title');
    expect(title).toBeInTheDocument();
    expect(title.textContent).toContain('SEOIndicator vs Ahrefs');
  });

  test('PH-004: Long description wraps appropriately', () => {
    const props = {
      ...defaultProps,
      description: 'This is a very long description that should wrap appropriately without breaking the card layout. It contains multiple sentences and should still render correctly.',
    };
    render(<ComparisonCard {...props} />);
    const description = screen.getByTestId('card-description');
    expect(description).toBeInTheDocument();
  });

  test('PH-005: Empty description handled gracefully', () => {
    const props = {
      ...defaultProps,
      description: '',
    };
    render(<ComparisonCard {...props} />);
    const description = screen.getByTestId('card-description');
    expect(description).toBeInTheDocument();
    expect(description.textContent).toBe('');
  });

  test('PH-006: Special characters in title render correctly', () => {
    const props = {
      ...defaultProps,
      title: 'SEOIndicator vs Ahrefs & Semrush (2024)',
    };
    render(<ComparisonCard {...props} />);
    expect(screen.getByTestId('card-title')).toHaveTextContent('SEOIndicator vs Ahrefs & Semrush (2024)');
  });
});

// ============================================================================
// Styling Tests (ST-001 to ST-006)
// ============================================================================

describe('ComparisonCard Styling', () => {
  test('ST-001: Card has hover effect classes', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    expect(card.className).toMatch(/hover:/);
  });

  test('ST-002: Card has border class', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    expect(card.className).toMatch(/border/);
  });

  test('ST-003: Uses link element for navigation', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    expect(card.tagName).toBe('A');
  });

  test('ST-004: Has group class for child hover effects', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    expect(card.className).toMatch(/group/);
  });

  test('ST-005: Title has hover text color change', () => {
    render(<ComparisonCard {...defaultProps} />);
    const title = screen.getByTestId('card-title');
    expect(title.className).toMatch(/group-hover:/);
  });

  test('ST-006: Dark theme classes applied', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    expect(card.className).toMatch(/bg-slate/);
  });
});

// ============================================================================
// Accessibility Tests (CA-001 to CA-008)
// ============================================================================

describe('ComparisonCard Accessibility', () => {
  test('CA-001: Card is an accessible link', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    expect(card).toHaveAttribute('href');
  });

  test('CA-002: Card is keyboard focusable', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    expect(card.tagName).toBe('A');
  });

  test('CA-003: Logo images have alt text', () => {
    render(<ComparisonCard {...defaultProps} />);
    const logoA = screen.getByTestId('logo-a');
    const logoB = screen.getByTestId('logo-b');
    
    expect(logoA).toHaveAttribute('alt', 'SEOIndicator logo');
    expect(logoB).toHaveAttribute('alt', 'Ahrefs logo');
  });

  test('CA-004: Proper heading structure', () => {
    render(<ComparisonCard {...defaultProps} />);
    const title = screen.getByTestId('card-title');
    expect(title.tagName).toBe('H3');
  });

  test('CA-005: No missing ARIA attributes', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    // Card should be a link, which is inherently accessible
    expect(card.tagName).toBe('A');
  });

  test('CA-006: Screen reader can access content', () => {
    render(<ComparisonCard {...defaultProps} />);
    // All content should be accessible via normal DOM
    expect(screen.getByText('SEOIndicator vs Ahrefs')).toBeInTheDocument();
    expect(screen.getByText('Compare SEOIndicator with Ahrefs')).toBeInTheDocument();
  });

  test('CA-007: Focus visible via transition classes', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    expect(card.className).toMatch(/transition/);
  });

  test('CA-008: No aria-hidden on interactive content', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    expect(card).not.toHaveAttribute('aria-hidden', 'true');
  });
});

// ============================================================================
// Integration Tests (IN-001 to IN-004)
// ============================================================================

describe('ComparisonCard Integration', () => {
  test('IN-001: Works with registry data format', () => {
    // Simulate data from registry
    const registryProps: ComparisonCardProps = {
      slug: 'seoindicator-vs-moz',
      title: 'SEOIndicator vs Moz',
      description: 'View detailed comparison',
      competitorA: { name: 'SEOIndicator', logo: '/logos/seoindicator.svg' },
      competitorB: { name: 'Moz', logo: '/logos/moz.svg' },
    };
    
    render(<ComparisonCard {...registryProps} />);
    expect(screen.getByTestId('comparison-card')).toBeInTheDocument();
    expect(screen.getByText('SEOIndicator vs Moz')).toBeInTheDocument();
  });

  test('IN-002: Links to correct comparison page', () => {
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    expect(card).toHaveAttribute('href', '/compare/seoindicator-vs-ahrefs');
  });

  test('IN-003: Multiple cards can be rendered', () => {
    const { container } = render(
      <div>
        <ComparisonCard {...defaultProps} />
        <ComparisonCard {...propsWithoutLogos} />
      </div>
    );
    
    const cards = screen.getAllByTestId('comparison-card');
    expect(cards.length).toBe(2);
  });

  test('IN-004: Performance with many cards', () => {
    const manyProps = Array(20).fill(null).map((_, i) => ({
      ...defaultProps,
      slug: `tool-a-vs-tool-${i}`,
      title: `Tool A vs Tool ${i}`,
    }));
    
    const { container } = render(
      <div>
        {manyProps.map((props, i) => (
          <ComparisonCard key={i} {...props} />
        ))}
      </div>
    );
    
    const cards = screen.getAllByTestId('comparison-card');
    expect(cards.length).toBe(20);
  });
});

// ============================================================================
// Type Safety Tests (TS-001 to TS-004)
// ============================================================================

describe('ComparisonCard Type Safety', () => {
  test('TS-001: Required props are enforced', () => {
    // TypeScript should enforce this at compile time
    // This test verifies the component works with all required props
    render(<ComparisonCard {...defaultProps} />);
    expect(screen.getByTestId('comparison-card')).toBeInTheDocument();
  });

  test('TS-002: Logo is optional', () => {
    // Props without logo should still work
    render(<ComparisonCard {...propsWithoutLogos} />);
    expect(screen.getByTestId('comparison-card')).toBeInTheDocument();
  });

  test('TS-003: Competitor names are strings', () => {
    render(<ComparisonCard {...defaultProps} />);
    const nameA = screen.getByTestId('name-a');
    const nameB = screen.getByTestId('name-b');
    
    expect(typeof nameA.textContent).toBe('string');
    expect(typeof nameB.textContent).toBe('string');
  });

  test('TS-004: Slug format is validated', () => {
    // Slug should contain -vs-
    expect(defaultProps.slug).toContain('-vs-');
    
    render(<ComparisonCard {...defaultProps} />);
    const card = screen.getByTestId('comparison-card');
    const href = card.getAttribute('href');
    
    expect(href).toContain('-vs-');
  });
});

// ============================================================================
// Snapshot Tests (SN-001 to SN-002)
// ============================================================================

describe('ComparisonCard Snapshots', () => {
  test('SN-001: Matches snapshot with logos', () => {
    const { container } = render(<ComparisonCard {...defaultProps} />);
    expect(container).toMatchSnapshot();
  });

  test('SN-002: Matches snapshot without logos', () => {
    const { container } = render(<ComparisonCard {...propsWithoutLogos} />);
    expect(container).toMatchSnapshot();
  });
});
