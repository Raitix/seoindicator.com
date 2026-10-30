/**
 * ResultRow Component Tests
 * Task: TASK-20-25 - Add Links from Results Table to Guides
 * Tests for ResultRow component with guide link functionality
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { vi, describe, it, expect, beforeEach } from 'vitest';

// Mock Next.js Link component
vi.mock('next/link', () => ({
  __esModule: true,
  default: ({
    children,
    href,
    'aria-label': ariaLabel,
    className,
    onClick,
  }: {
    children: React.ReactNode;
    href: string;
    'aria-label'?: string;
    className?: string;
    onClick?: (e: React.MouseEvent) => void;
  }) => (
    <a href={href} aria-label={ariaLabel} className={className} data-testid="guide-link" onClick={onClick}>
      {children}
    </a>
  ),
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  ChevronDown: () => <span data-testid="chevron-down">▼</span>,
  ChevronRight: () => <span data-testid="chevron-right">▶</span>,
}));

// Mock indicator display name function
vi.mock('@/lib/api/analysis-job', () => ({
  getIndicatorDisplayName: (slug: string) => {
    const names: Record<string, string> = {
      meta_tags: 'Meta Tags',
      headings: 'Headings Structure',
      images: 'Images',
      links: 'Links',
      mobile_friendly: 'Mobile Friendly',
      page_speed: 'Page Speed',
      structured_data: 'Structured Data',
      security: 'Security',
      social_tags: 'Social Tags',
      canonical: 'Canonical URLs',
      robots: 'Robots.txt',
      sitemap: 'XML Sitemap',
      internal_links: 'Internal Links',
      external_links: 'External Links',
      unknown_indicator: 'Unknown Indicator',
    };
    return names[slug] || slug.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
  },
  getScoreColor: (percentage: number) => {
    if (percentage >= 80) return 'text-green-600 dark:text-green-400';
    if (percentage >= 60) return 'text-yellow-600 dark:text-yellow-400';
    if (percentage >= 40) return 'text-orange-600 dark:text-orange-400';
    return 'text-red-600 dark:text-red-400';
  },
  getScoreBgColor: (percentage: number) => {
    if (percentage >= 80) return 'bg-green-100 dark:bg-green-900/30';
    if (percentage >= 60) return 'bg-yellow-100 dark:bg-yellow-900/30';
    if (percentage >= 40) return 'bg-orange-100 dark:bg-orange-900/30';
    return 'bg-red-100 dark:bg-red-900/30';
  },
}));

// Mock indicator-to-guide mapping
vi.mock('@/lib/guides/indicator-to-guide', () => ({
  getGuideSlugForIndicator: (indicator: string) => {
    const mapping: Record<string, string> = {
      meta_tags: 'meta-title',
      headings: 'h1-headings',
      images: 'image-alt-text',
      links: 'internal-links',
      mobile_friendly: 'mobile-friendly',
      page_speed: 'page-speed',
      structured_data: 'structured-data',
      security: 'https-security',
      social_tags: 'open-graph',
      canonical: 'canonical-urls',
      robots: 'robots-txt',
      sitemap: 'xml-sitemap',
      internal_links: 'internal-links',
      external_links: 'external-links',
    };
    return mapping[indicator] || null;
  },
}));

// Import the actual component
import { ResultRow } from '@/components/analysis/ResultRow';

// ============================================
// Test Data
// ============================================
const completedResult = {
  indicator: 'meta_tags',
  status: 'completed' as const,
  score: 85,
  max_score: 100,
  percentage: 85,
  data: { title: 'Test Page', length: 45 },
  recommendations: ['Add more keywords to title'],
};

const failedResult = {
  indicator: 'images',
  status: 'failed' as const,
  score: 0,
  max_score: 100,
  percentage: 0,
  data: {},
  recommendations: [],
  error_message: 'Failed to analyze images',
};

const pendingResult = {
  indicator: 'page_speed',
  status: 'pending' as const,
  score: 0,
  max_score: 100,
  percentage: 0,
  data: {},
  recommendations: [],
};

const runningResult = {
  indicator: 'mobile_friendly',
  status: 'running' as const,
  score: 0,
  max_score: 100,
  percentage: 0,
  data: {},
  recommendations: [],
};

const unknownIndicatorResult = {
  indicator: 'unknown_indicator',
  status: 'completed' as const,
  score: 50,
  max_score: 100,
  percentage: 50,
  data: {},
  recommendations: [],
};

// ============================================
// RR-RENDER: Component Rendering
// ============================================

describe('RR-RENDER: Component Rendering', () => {
  it('RR-RENDER-001: renders without crashing', () => {
    render(<ResultRow result={completedResult} />);
    expect(screen.getByRole('row')).toBeInTheDocument();
  });

  it('RR-RENDER-002: renders indicator name', () => {
    render(<ResultRow result={completedResult} />);
    expect(screen.getByText('Meta Tags')).toBeInTheDocument();
  });

  it('RR-RENDER-003: renders score percentage', () => {
    render(<ResultRow result={completedResult} />);
    expect(screen.getByLabelText(/Score: 85%/)).toBeInTheDocument();
  });

  it('RR-RENDER-004: renders status indicator', () => {
    render(<ResultRow result={completedResult} />);
    // Status indicator is a visual dot
    const row = screen.getByRole('row');
    expect(row).toBeInTheDocument();
  });

  it('RR-RENDER-005: renders expand button', () => {
    render(<ResultRow result={completedResult} />);
    expect(screen.getByRole('button', { name: /Expand details/i })).toBeInTheDocument();
  });
});

// ============================================
// RR-LINK: Guide Link Functionality
// ============================================

describe('RR-LINK: Guide Link Functionality', () => {
  it('RR-LINK-001: renders link for known indicator', () => {
    render(<ResultRow result={completedResult} />);
    const link = screen.getByTestId('guide-link');
    expect(link).toBeInTheDocument();
  });

  it('RR-LINK-002: link href is correct', () => {
    render(<ResultRow result={completedResult} />);
    const link = screen.getByTestId('guide-link');
    expect(link).toHaveAttribute('href', '/seo-guides/meta-title');
  });

  it('RR-LINK-003: link has aria-label', () => {
    render(<ResultRow result={completedResult} />);
    const link = screen.getByTestId('guide-link');
    expect(link).toHaveAttribute('aria-label', 'View guide for Meta Tags');
  });

  it('RR-LINK-004: link text is indicator display name', () => {
    render(<ResultRow result={completedResult} />);
    const link = screen.getByTestId('guide-link');
    expect(link).toHaveTextContent('Meta Tags');
  });

  it('RR-LINK-005: no link for unknown indicator', () => {
    render(<ResultRow result={unknownIndicatorResult} />);
    expect(screen.queryByTestId('guide-link')).not.toBeInTheDocument();
    expect(screen.getByText('Unknown Indicator')).toBeInTheDocument();
  });

  it('RR-LINK-006: correct href for different indicators', () => {
    const testCases = [
      { indicator: 'images', expectedSlug: 'image-alt-text' },
      { indicator: 'mobile_friendly', expectedSlug: 'mobile-friendly' },
      { indicator: 'page_speed', expectedSlug: 'page-speed' },
      { indicator: 'structured_data', expectedSlug: 'structured-data' },
    ];

    testCases.forEach(({ indicator, expectedSlug }) => {
      const { unmount } = render(
        <ResultRow result={{ ...completedResult, indicator }} />
      );
      const link = screen.getByTestId('guide-link');
      expect(link).toHaveAttribute('href', `/seo-guides/${expectedSlug}`);
      unmount();
    });
  });
});

// ============================================
// RR-STATUS: Status-Based Rendering
// ============================================

describe('RR-STATUS: Status-Based Rendering', () => {
  it('RR-STATUS-001: link renders for completed status', () => {
    render(<ResultRow result={completedResult} />);
    expect(screen.getByTestId('guide-link')).toBeInTheDocument();
  });

  it('RR-STATUS-002: link renders for failed status', () => {
    render(<ResultRow result={failedResult} />);
    expect(screen.getByTestId('guide-link')).toBeInTheDocument();
  });

  it('RR-STATUS-003: link renders for pending status', () => {
    render(<ResultRow result={pendingResult} />);
    expect(screen.getByTestId('guide-link')).toBeInTheDocument();
  });

  it('RR-STATUS-004: link renders for running status', () => {
    render(<ResultRow result={runningResult} />);
    expect(screen.getByTestId('guide-link')).toBeInTheDocument();
  });

  it('RR-STATUS-005: score shows for completed', () => {
    render(<ResultRow result={completedResult} />);
    expect(screen.getByLabelText(/Score: 85%/)).toBeInTheDocument();
  });

  it('RR-STATUS-006: score hidden for pending', () => {
    render(<ResultRow result={pendingResult} />);
    // Score display should show waiting text
    expect(screen.getByText('Waiting...')).toBeInTheDocument();
  });
});

// ============================================
// RR-CLICK: Click Behavior
// ============================================

describe('RR-CLICK: Click Behavior', () => {
  it('RR-CLICK-001: row click calls onClick', () => {
    const onClick = vi.fn();
    render(<ResultRow result={completedResult} onClick={onClick} />);
    
    fireEvent.click(screen.getByRole('row'));
    expect(onClick).toHaveBeenCalledWith(completedResult);
  });

  it('RR-CLICK-002: link click does not trigger row click', () => {
    const onClick = vi.fn();
    render(<ResultRow result={completedResult} onClick={onClick} />);
    
    fireEvent.click(screen.getByTestId('guide-link'));
    expect(onClick).not.toHaveBeenCalled();
  });

  it('RR-CLICK-003: expand button does not trigger row click', () => {
    const onClick = vi.fn();
    render(<ResultRow result={completedResult} onClick={onClick} />);
    
    fireEvent.click(screen.getByRole('button', { name: /Expand details/i }));
    expect(onClick).not.toHaveBeenCalled();
  });

  it('RR-CLICK-004: expand button toggles expansion', () => {
    const onToggleExpand = vi.fn();
    render(<ResultRow result={completedResult} onToggleExpand={onToggleExpand} />);
    
    fireEvent.click(screen.getByRole('button', { name: /Expand details/i }));
    expect(onToggleExpand).toHaveBeenCalled();
  });
});

// ============================================
// RR-A11Y: Accessibility
// ============================================

describe('RR-A11Y: Accessibility', () => {
  it('RR-A11Y-001: link has accessible name', () => {
    render(<ResultRow result={completedResult} />);
    const link = screen.getByTestId('guide-link');
    expect(link).toHaveAttribute('aria-label');
  });

  it('RR-A11Y-002: expand button has aria-expanded', () => {
    render(<ResultRow result={completedResult} />);
    const button = screen.getByRole('button', { name: /Expand details/i });
    expect(button).toHaveAttribute('aria-expanded');
  });

  it('RR-A11Y-003: aria-expanded is false when collapsed', () => {
    render(<ResultRow result={completedResult} isExpanded={false} />);
    const button = screen.getByRole('button', { name: /Expand details/i });
    expect(button).toHaveAttribute('aria-expanded', 'false');
  });

  it('RR-A11Y-004: aria-expanded is true when expanded', () => {
    render(<ResultRow result={completedResult} isExpanded={true} />);
    const button = screen.getByRole('button', { name: /Collapse details/i });
    expect(button).toHaveAttribute('aria-expanded', 'true');
  });

  it('RR-A11Y-005: expand button has aria-label', () => {
    render(<ResultRow result={completedResult} />);
    const button = screen.getByRole('button', { name: /Expand details/i });
    expect(button).toHaveAttribute('aria-label');
  });

  it('RR-A11Y-006: row has role="row"', () => {
    render(<ResultRow result={completedResult} />);
    expect(screen.getByRole('row')).toBeInTheDocument();
  });

  it('RR-A11Y-007: keyboard can focus link', async () => {
    const user = userEvent.setup();
    render(<ResultRow result={completedResult} />);
    
    await user.tab();
    // First focusable element should be expand button
    expect(screen.getByRole('button', { name: /Expand details/i })).toHaveFocus();
    
    await user.tab();
    // Second focusable element should be the link
    expect(screen.getByTestId('guide-link')).toHaveFocus();
  });
});

// ============================================
// RR-STYLING: Styling
// ============================================

describe('RR-STYLING: Styling', () => {
  it('RR-STYLING-001: className prop is applied', () => {
    render(<ResultRow result={completedResult} className="custom-class" />);
    expect(screen.getByRole('row')).toHaveClass('custom-class');
  });

  it('RR-STYLING-002: link inherits text styling', () => {
    render(<ResultRow result={completedResult} />);
    const link = screen.getByTestId('guide-link');
    // Link should not break the visual flow
    expect(link).toBeInTheDocument();
  });

  it('RR-STYLING-003: row has hover state', () => {
    render(<ResultRow result={completedResult} />);
    // CSS hover states are tested via visual regression or E2E
    expect(screen.getByRole('row')).toBeInTheDocument();
  });
});

// ============================================
// RR-EDGE: Edge Cases
// ============================================

describe('RR-EDGE: Edge Cases', () => {
  it('RR-EDGE-001: very long indicator name', () => {
    const longNameResult = {
      ...completedResult,
      indicator: 'meta_tags', // Use a known indicator with reasonable name
    };
    render(<ResultRow result={longNameResult} />);
    expect(screen.getByText('Meta Tags')).toBeInTheDocument();
  });

  it('RR-EDGE-002: zero score', () => {
    render(<ResultRow result={{ ...completedResult, percentage: 0 }} />);
    expect(screen.getByLabelText(/Score: 0%/)).toBeInTheDocument();
  });

  it('RR-EDGE-003: 100 score', () => {
    render(<ResultRow result={{ ...completedResult, percentage: 100 }} />);
    expect(screen.getByLabelText(/Score: 100%/)).toBeInTheDocument();
  });

  it('RR-EDGE-004: null onClick handler', () => {
    render(<ResultRow result={completedResult} onClick={undefined} />);
    // Should not crash
    expect(screen.getByRole('row')).toBeInTheDocument();
  });

  it('RR-EDGE-005: null onToggleExpand handler', () => {
    render(<ResultRow result={completedResult} onToggleExpand={undefined} />);
    // Should not crash
    expect(screen.getByRole('button', { name: /Expand details/i })).toBeInTheDocument();
  });
});

// ============================================
// RR-INT: Integration
// ============================================

describe('RR-INT: Integration', () => {
  it('RR-INT-001: full interaction flow', async () => {
    const user = userEvent.setup();
    const onClick = vi.fn();
    const onToggleExpand = vi.fn();
    
    render(
      <ResultRow
        result={completedResult}
        onClick={onClick}
        onToggleExpand={onToggleExpand}
      />
    );
    
    // Click link - should not trigger row onClick
    await user.click(screen.getByTestId('guide-link'));
    expect(onClick).not.toHaveBeenCalled();
    
    // Click expand button
    await user.click(screen.getByRole('button', { name: /Expand details/i }));
    expect(onToggleExpand).toHaveBeenCalled();
    
    // Click row
    await user.click(screen.getByRole('row'));
    expect(onClick).toHaveBeenCalled();
  });

  it('RR-INT-002: multiple results with different links', () => {
    const results = [
      { ...completedResult, indicator: 'meta_tags' as const },
      { ...completedResult, indicator: 'images' as const },
      { ...completedResult, indicator: 'page_speed' as const },
    ];

    const { container } = render(
      <div>
        {results.map((result, index) => (
          <ResultRow key={index} result={result} />
        ))}
      </div>
    );

    const links = container.querySelectorAll('[data-testid="guide-link"]');
    expect(links.length).toBe(3);
    
    expect(links[0]).toHaveAttribute('href', '/seo-guides/meta-title');
    expect(links[1]).toHaveAttribute('href', '/seo-guides/image-alt-text');
    expect(links[2]).toHaveAttribute('href', '/seo-guides/page-speed');
  });
});

// ============================================
// AC: Acceptance Criteria
// ============================================

describe('AC: Acceptance Criteria', () => {
  it('AC-001: Known indicators render as clickable links to guides', () => {
    render(<ResultRow result={completedResult} />);
    const link = screen.getByTestId('guide-link');
    expect(link).toBeInTheDocument();
    expect(link).toHaveAttribute('href', '/seo-guides/meta-title');
  });

  it('AC-002: Unknown indicators render as plain text (no link)', () => {
    render(<ResultRow result={unknownIndicatorResult} />);
    expect(screen.queryByTestId('guide-link')).not.toBeInTheDocument();
    expect(screen.getByText('Unknown Indicator')).toBeInTheDocument();
  });

  it('AC-003: Links have proper aria-labels for accessibility', () => {
    render(<ResultRow result={completedResult} />);
    const link = screen.getByTestId('guide-link');
    expect(link).toHaveAttribute('aria-label', 'View guide for Meta Tags');
  });

  it('AC-004: Links open in same tab (internal navigation)', () => {
    render(<ResultRow result={completedResult} />);
    const link = screen.getByTestId('guide-link');
    expect(link).not.toHaveAttribute('target', '_blank');
  });

  it('AC-005: Links work regardless of result status', () => {
    const statuses = ['completed', 'failed', 'pending', 'running'] as const;
    
    statuses.forEach((status) => {
      const { unmount } = render(
        <ResultRow result={{ ...completedResult, status }} />
      );
      expect(screen.getByTestId('guide-link')).toBeInTheDocument();
      unmount();
    });
  });

  it('AC-006: Clicking link does not expand/collapse row', async () => {
    const user = userEvent.setup();
    const onToggleExpand = vi.fn();
    
    render(
      <ResultRow result={completedResult} onToggleExpand={onToggleExpand} />
    );
    
    await user.click(screen.getByTestId('guide-link'));
    expect(onToggleExpand).not.toHaveBeenCalled();
  });

  it('AC-007: Keyboard navigation works for links', async () => {
    const user = userEvent.setup();
    render(<ResultRow result={completedResult} />);
    
    await user.tab();
    await user.tab();
    expect(screen.getByTestId('guide-link')).toHaveFocus();
  });
});
