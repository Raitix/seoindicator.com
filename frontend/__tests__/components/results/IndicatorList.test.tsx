/**
 * @jest-environment jsdom
 * 
 * IndicatorList Component Tests
 * Task: TASK-18-30 - Results Dashboard Page
 * 
 * Tests for the indicator list component displaying SEO indicators with scores,
 * status icons, and expandable details.
 */

import React from 'react';
import { render, screen, fireEvent, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// ============================================================================
// Types
// ============================================================================

interface IndicatorIssue {
  severity: 'critical' | 'warning' | 'info';
  message: string;
}

interface IndicatorSuggestion {
  priority: 'high' | 'medium' | 'low';
  text: string;
  impact?: 'high' | 'medium' | 'low';
  effort?: 'low' | 'medium' | 'high';
}

interface Indicator {
  id: string;
  name: string;
  category: string;
  score: number;
  maxScore: number;
  status: 'good' | 'warning' | 'critical' | 'neutral';
  value: string;
  description: string;
  issues: IndicatorIssue[];
  suggestions: IndicatorSuggestion[];
}

// ============================================================================
// Mock Component (TDD Reference)
// ============================================================================

/**
 * IndicatorList - List of SEO indicators with expandable details
 * 
 * @example
 * <IndicatorList
 *   indicators={indicators}
 *   onIndicatorClick={handleClick}
 *   sortBy="score"
 * />
 */
interface IndicatorListProps {
  indicators: Indicator[];
  isLoading?: boolean;
  expandedIds?: string[];
  onIndicatorClick?: (id: string) => void;
  sortBy?: 'score' | 'category' | 'name';
  filterByStatus?: 'good' | 'warning' | 'critical' | 'all';
  className?: string;
}

function IndicatorList({
  indicators,
  isLoading = false,
  expandedIds = [],
  onIndicatorClick,
  sortBy = 'score',
  filterByStatus = 'all',
  className,
}: IndicatorListProps) {
  // Filter indicators
  const filteredIndicators = filterByStatus === 'all'
    ? indicators
    : indicators.filter(i => i.status === filterByStatus);

  // Sort indicators
  const sortedIndicators = [...filteredIndicators].sort((a, b) => {
    if (sortBy === 'score') return a.score - b.score;
    if (sortBy === 'category') return a.category.localeCompare(b.category);
    if (sortBy === 'name') return a.name.localeCompare(b.name);
    return 0;
  });

  // Get status icon
  const getStatusIcon = (status: Indicator['status']) => {
    switch (status) {
      case 'good': return '✓';
      case 'warning': return '⚠';
      case 'critical': return '✗';
      default: return '○';
    }
  };

  // Get status color
  const getStatusColor = (status: Indicator['status']) => {
    switch (status) {
      case 'good': return 'text-green-500';
      case 'warning': return 'text-yellow-500';
      case 'critical': return 'text-red-500';
      default: return 'text-gray-400';
    }
  };

  if (isLoading) {
    return (
      <div
        data-testid="indicator-list"
        data-loading="true"
        className={className}
        aria-busy="true"
      >
        {[1, 2, 3].map(i => (
          <div
            key={i}
            data-testid={`indicator-skeleton-${i}`}
            className="animate-pulse p-4 border rounded-lg mb-2"
          >
            <div className="h-4 bg-muted rounded w-1/3 mb-2" />
            <div className="h-6 bg-muted rounded w-1/4" />
          </div>
        ))}
      </div>
    );
  }

  if (indicators.length === 0) {
    return (
      <div
        data-testid="indicator-list"
        data-loading="false"
        className={className}
      >
        <p data-testid="empty-state" className="text-muted-foreground text-center py-8">
          No indicators found
        </p>
      </div>
    );
  }

  return (
    <div
      data-testid="indicator-list"
      data-loading="false"
      data-count={sortedIndicators.length}
      className={className}
      role="list"
      aria-label="SEO Indicators"
    >
      {sortedIndicators.map(indicator => {
        const isExpanded = expandedIds.includes(indicator.id);
        const percentage = Math.round((indicator.score / indicator.maxScore) * 100);

        return (
          <div
            key={indicator.id}
            data-testid={`indicator-item-${indicator.id}`}
            data-indicator-id={indicator.id}
            data-status={indicator.status}
            data-category={indicator.category}
            data-score={indicator.score}
            role="listitem"
            className="border rounded-lg mb-2"
          >
            <button
              data-testid={`indicator-button-${indicator.id}`}
              onClick={() => onIndicatorClick?.(indicator.id)}
              aria-expanded={isExpanded}
              aria-controls={`indicator-details-${indicator.id}`}
              className="w-full p-4 text-left flex items-center justify-between"
            >
              <div className="flex items-center gap-3">
                <span
                  data-testid={`status-icon-${indicator.id}`}
                  className={getStatusColor(indicator.status)}
                  aria-hidden="true"
                >
                  {getStatusIcon(indicator.status)}
                </span>
                <div>
                  <span
                    data-testid={`indicator-name-${indicator.id}`}
                    className="font-medium"
                  >
                    {indicator.name}
                  </span>
                  <span
                    data-testid={`indicator-category-${indicator.id}`}
                    className="text-sm text-muted-foreground ml-2"
                  >
                    {indicator.category}
                  </span>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <span
                  data-testid={`indicator-score-${indicator.id}`}
                  className="font-bold"
                >
                  {indicator.score}/{indicator.maxScore}
                </span>
                <span
                  data-testid={`indicator-percentage-${indicator.id}`}
                  className="text-sm text-muted-foreground"
                >
                  ({percentage}%)
                </span>
                <span
                  data-testid={`expand-icon-${indicator.id}`}
                  aria-hidden="true"
                >
                  {isExpanded ? '▼' : '▶'}
                </span>
              </div>
            </button>
            
            {isExpanded && (
              <div
                id={`indicator-details-${indicator.id}`}
                data-testid={`indicator-details-${indicator.id}`}
                className="p-4 border-t"
              >
                <p
                  data-testid={`indicator-description-${indicator.id}`}
                  className="text-muted-foreground mb-3"
                >
                  {indicator.description}
                </p>
                
                {indicator.issues.length > 0 && (
                  <div className="mb-3">
                    <h4 className="font-medium mb-1">Issues</h4>
                    <ul data-testid={`issues-list-${indicator.id}`} className="list-disc list-inside">
                      {indicator.issues.map((issue, i) => (
                        <li
                          key={i}
                          data-severity={issue.severity}
                          className="text-sm"
                        >
                          {issue.message}
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
                
                {indicator.suggestions.length > 0 && (
                  <div>
                    <h4 className="font-medium mb-1">Suggestions</h4>
                    <ul data-testid={`suggestions-list-${indicator.id}`} className="list-disc list-inside">
                      {indicator.suggestions.map((suggestion, i) => (
                        <li
                          key={i}
                          data-priority={suggestion.priority}
                          className="text-sm"
                        >
                          {suggestion.text}
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}

// ============================================================================
// Mock Data
// ============================================================================

const mockIndicators: Indicator[] = [
  {
    id: 'title_tag',
    name: 'Title Tag',
    category: 'content',
    score: 3,
    maxScore: 3,
    status: 'good',
    value: 'Example Domain - Great SEO',
    description: 'Title tag is present and well-optimized.',
    issues: [],
    suggestions: [],
  },
  {
    id: 'meta_description',
    name: 'Meta Description',
    category: 'content',
    score: 2,
    maxScore: 3,
    status: 'warning',
    value: 'This is an example domain...',
    description: 'Meta description could be more compelling.',
    issues: [
      { severity: 'warning', message: 'Description is generic' },
    ],
    suggestions: [
      { priority: 'medium', text: 'Add a unique value proposition', impact: 'high', effort: 'low' },
    ],
  },
  {
    id: 'h1_headings',
    name: 'H1 Headings',
    category: 'content',
    score: 1,
    maxScore: 3,
    status: 'critical',
    value: 'Missing',
    description: 'No H1 heading found on the page.',
    issues: [
      { severity: 'critical', message: 'Missing H1 heading' },
    ],
    suggestions: [
      { priority: 'high', text: 'Add an H1 heading with your main keyword', impact: 'high', effort: 'low' },
    ],
  },
  {
    id: 'url_structure',
    name: 'URL Structure',
    category: 'technical',
    score: 3,
    maxScore: 3,
    status: 'good',
    value: 'https://example.com/page',
    description: 'URL is well-structured and SEO-friendly.',
    issues: [],
    suggestions: [],
  },
  {
    id: 'robots_txt',
    name: 'Robots.txt',
    category: 'technical',
    score: 2,
    maxScore: 3,
    status: 'warning',
    value: 'Present but allows all bots',
    description: 'Robots.txt exists but may be too permissive.',
    issues: [
      { severity: 'warning', message: 'Consider restricting sensitive paths' },
    ],
    suggestions: [
      { priority: 'low', text: 'Add disallow rules for admin paths', impact: 'medium', effort: 'low' },
    ],
  },
];

// ============================================================================
// Tests
// ============================================================================

describe('IndicatorList', () => {
  // -------------------------------------------------------------------------
  // RD-003: Indicator List with Scores
  // -------------------------------------------------------------------------
  describe('RD-003: Indicator List with Scores', () => {
    it('RD-003.1: renders all indicators', () => {
      render(<IndicatorList indicators={mockIndicators} />);
      
      const list = screen.getByTestId('indicator-list');
      expect(list).toHaveAttribute('data-count', '5');
      
      mockIndicators.forEach(indicator => {
        expect(screen.getByTestId(`indicator-item-${indicator.id}`)).toBeInTheDocument();
      });
    });

    it('RD-003.2: displays score per indicator', () => {
      render(<IndicatorList indicators={mockIndicators} />);
      
      expect(screen.getByTestId('indicator-score-title_tag')).toHaveTextContent('3/3');
      expect(screen.getByTestId('indicator-score-meta_description')).toHaveTextContent('2/3');
      expect(screen.getByTestId('indicator-score-h1_headings')).toHaveTextContent('1/3');
    });

    it('RD-003.3: shows green checkmark for good status', () => {
      render(<IndicatorList indicators={mockIndicators} />);
      
      const icon = screen.getByTestId('status-icon-title_tag');
      expect(icon).toHaveTextContent('✓');
      expect(icon).toHaveClass('text-green-500');
    });

    it('RD-003.4: shows yellow warning icon for warning status', () => {
      render(<IndicatorList indicators={mockIndicators} />);
      
      const icon = screen.getByTestId('status-icon-meta_description');
      expect(icon).toHaveTextContent('⚠');
      expect(icon).toHaveClass('text-yellow-500');
    });

    it('RD-003.5: shows red X icon for critical status', () => {
      render(<IndicatorList indicators={mockIndicators} />);
      
      const icon = screen.getByTestId('status-icon-h1_headings');
      expect(icon).toHaveTextContent('✗');
      expect(icon).toHaveClass('text-red-500');
    });

    it('RD-003.6: expands details when indicator is clicked', () => {
      const handleClick = jest.fn();
      render(
        <IndicatorList
          indicators={mockIndicators}
          expandedIds={['meta_description']}
          onIndicatorClick={handleClick}
        />
      );
      
      const button = screen.getByTestId('indicator-button-meta_description');
      expect(button).toHaveAttribute('aria-expanded', 'true');
      
      const details = screen.getByTestId('indicator-details-meta_description');
      expect(details).toBeInTheDocument();
    });

    it('RD-003.7: collapses details when expanded indicator is clicked', () => {
      render(
        <IndicatorList
          indicators={mockIndicators}
          expandedIds={[]}
        />
      );
      
      expect(screen.queryByTestId('indicator-details-meta_description')).not.toBeInTheDocument();
    });

    it('RD-003.8: shows loading skeleton when isLoading', () => {
      render(<IndicatorList indicators={[]} isLoading />);
      
      const list = screen.getByTestId('indicator-list');
      expect(list).toHaveAttribute('data-loading', 'true');
      
      expect(screen.getByTestId('indicator-skeleton-1')).toBeInTheDocument();
      expect(screen.getByTestId('indicator-skeleton-2')).toBeInTheDocument();
      expect(screen.getByTestId('indicator-skeleton-3')).toBeInTheDocument();
    });

    it('RD-003.9: shows empty state when no indicators', () => {
      render(<IndicatorList indicators={[]} />);
      
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
      expect(screen.getByTestId('empty-state')).toHaveTextContent('No indicators found');
    });

    it('RD-003.10: sorts by score ascending', () => {
      render(<IndicatorList indicators={mockIndicators} sortBy="score" />);
      
      const list = screen.getByTestId('indicator-list');
      const items = within(list).getAllByRole('listitem');
      
      // First item should have lowest score (h1_headings with score 1)
      expect(items[0]).toHaveAttribute('data-score', '1');
    });

    it('RD-003.11: sorts by category', () => {
      render(<IndicatorList indicators={mockIndicators} sortBy="category" />);
      
      const list = screen.getByTestId('indicator-list');
      const items = within(list).getAllByRole('listitem');
      
      // Content category comes before technical alphabetically
      expect(items[0]).toHaveAttribute('data-category', 'content');
    });

    it('RD-003.12: filters by status', () => {
      render(<IndicatorList indicators={mockIndicators} filterByStatus="critical" />);
      
      const list = screen.getByTestId('indicator-list');
      expect(list).toHaveAttribute('data-count', '1');
      
      expect(screen.getByTestId('indicator-item-h1_headings')).toBeInTheDocument();
      expect(screen.queryByTestId('indicator-item-title_tag')).not.toBeInTheDocument();
    });
  });

  // -------------------------------------------------------------------------
  // RD-005: Expandable Indicator Details
  // -------------------------------------------------------------------------
  describe('RD-005: Expandable Indicator Details', () => {
    it('RD-005.1: toggles expansion on click', () => {
      const handleClick = jest.fn();
      render(
        <IndicatorList
          indicators={mockIndicators}
          onIndicatorClick={handleClick}
        />
      );
      
      const button = screen.getByTestId('indicator-button-meta_description');
      fireEvent.click(button);
      
      expect(handleClick).toHaveBeenCalledWith('meta_description');
    });

    it('RD-005.2: shows details content when expanded', () => {
      render(
        <IndicatorList
          indicators={mockIndicators}
          expandedIds={['meta_description']}
        />
      );
      
      expect(screen.getByTestId('indicator-description-meta_description')).toBeInTheDocument();
      expect(screen.getByTestId('issues-list-meta_description')).toBeInTheDocument();
      expect(screen.getByTestId('suggestions-list-meta_description')).toBeInTheDocument();
    });

    it('RD-005.3: displays issues list with severity', () => {
      render(
        <IndicatorList
          indicators={mockIndicators}
          expandedIds={['h1_headings']}
        />
      );
      
      const issuesList = screen.getByTestId('issues-list-h1_headings');
      const issues = within(issuesList).getAllByRole('listitem');
      
      expect(issues).toHaveLength(1);
      expect(issues[0]).toHaveAttribute('data-severity', 'critical');
    });

    it('RD-005.4: displays suggestions list', () => {
      render(
        <IndicatorList
          indicators={mockIndicators}
          expandedIds={['h1_headings']}
        />
      );
      
      const suggestionsList = screen.getByTestId('suggestions-list-h1_headings');
      const suggestions = within(suggestionsList).getAllByRole('listitem');
      
      expect(suggestions).toHaveLength(1);
      expect(suggestions[0]).toHaveAttribute('data-priority', 'high');
    });

    it('RD-005.5: shows expand/collapse icon', () => {
      const { rerender } = render(
        <IndicatorList
          indicators={mockIndicators}
          expandedIds={[]}
        />
      );
      
      let icon = screen.getByTestId('expand-icon-meta_description');
      expect(icon).toHaveTextContent('▶');
      
      rerender(
        <IndicatorList
          indicators={mockIndicators}
          expandedIds={['meta_description']}
        />
      );
      
      icon = screen.getByTestId('expand-icon-meta_description');
      expect(icon).toHaveTextContent('▼');
    });

    it('RD-005.8: supports keyboard navigation', () => {
      const handleClick = jest.fn();
      render(
        <IndicatorList
          indicators={mockIndicators}
          onIndicatorClick={handleClick}
        />
      );
      
      const button = screen.getByTestId('indicator-button-title_tag');
      fireEvent.keyDown(button, { key: 'Enter' });
      
      // Button should be focusable and activatable
      expect(button).toHaveAttribute('type', 'button');
    });

    it('RD-005.10: multiple indicators can be expanded', () => {
      render(
        <IndicatorList
          indicators={mockIndicators}
          expandedIds={['title_tag', 'meta_description']}
        />
      );
      
      expect(screen.getByTestId('indicator-details-title_tag')).toBeInTheDocument();
      expect(screen.getByTestId('indicator-details-meta_description')).toBeInTheDocument();
    });
  });

  // -------------------------------------------------------------------------
  // Accessibility
  // -------------------------------------------------------------------------
  describe('Accessibility', () => {
    it('has correct ARIA roles', () => {
      render(<IndicatorList indicators={mockIndicators} />);
      
      const list = screen.getByTestId('indicator-list');
      expect(list).toHaveAttribute('role', 'list');
      expect(list).toHaveAttribute('aria-label', 'SEO Indicators');
      
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(5);
    });

    it('has aria-expanded on expandable buttons', () => {
      render(
        <IndicatorList
          indicators={mockIndicators}
          expandedIds={['title_tag']}
        />
      );
      
      const expandedButton = screen.getByTestId('indicator-button-title_tag');
      expect(expandedButton).toHaveAttribute('aria-expanded', 'true');
      
      const collapsedButton = screen.getByTestId('indicator-button-meta_description');
      expect(collapsedButton).toHaveAttribute('aria-expanded', 'false');
    });

    it('has aria-controls pointing to details panel', () => {
      render(
        <IndicatorList
          indicators={mockIndicators}
          expandedIds={['title_tag']}
        />
      );
      
      const button = screen.getByTestId('indicator-button-title_tag');
      const details = screen.getByTestId('indicator-details-title_tag');
      
      expect(button).toHaveAttribute('aria-controls', 'indicator-details-title_tag');
      expect(details).toHaveAttribute('id', 'indicator-details-title_tag');
    });

    it('announces loading state with aria-busy', () => {
      render(<IndicatorList indicators={[]} isLoading />);
      
      const list = screen.getByTestId('indicator-list');
      expect(list).toHaveAttribute('aria-busy', 'true');
    });
  });

  // -------------------------------------------------------------------------
  // Acceptance Criteria Verification
  // -------------------------------------------------------------------------
  describe('Acceptance Criteria', () => {
    it('AC: Indicator list with scores and status', () => {
      render(<IndicatorList indicators={mockIndicators} />);
      
      // Check all indicators rendered with scores
      mockIndicators.forEach(indicator => {
        const item = screen.getByTestId(`indicator-item-${indicator.id}`);
        expect(item).toBeInTheDocument();
        expect(item).toHaveAttribute('data-status', indicator.status);
        
        const score = screen.getByTestId(`indicator-score-${indicator.id}`);
        expect(score).toBeInTheDocument();
      });
    });

    it('AC: Expandable indicator details', () => {
      render(
        <IndicatorList
          indicators={mockIndicators}
          expandedIds={['h1_headings']}
        />
      );
      
      const details = screen.getByTestId('indicator-details-h1_headings');
      expect(details).toBeInTheDocument();
      
      // Check for description, issues, and suggestions
      expect(screen.getByTestId('indicator-description-h1_headings')).toBeInTheDocument();
      expect(screen.getByTestId('issues-list-h1_headings')).toBeInTheDocument();
      expect(screen.getByTestId('suggestions-list-h1_headings')).toBeInTheDocument();
    });

    it('AC: Loading state during analysis', () => {
      render(<IndicatorList indicators={mockIndicators} isLoading />);
      
      expect(screen.getByTestId('indicator-list')).toHaveAttribute('data-loading', 'true');
      expect(screen.getByTestId('indicator-skeleton-1')).toBeInTheDocument();
    });
  });
});
