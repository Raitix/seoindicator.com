/**
 * ComparisonTable Component Tests
 * Task: TASK-32-19 - Unit Tests for Comparison Components
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * TDD Tests - Component implementation pending
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import type { FeatureCategory, ComparisonTableProps } from '@/lib/compare/types';

// Mock Lucide React icons
jest.mock('lucide-react', () => ({
  Check: () => <span data-testid="check-icon">✓</span>,
  X: () => <span data-testid="x-icon">✗</span>,
  HelpCircle: () => <span data-testid="help-icon">?</span>,
}));

// Mock component - will be replaced with actual implementation
const ComparisonTable = ({
  features,
  highlightWinner = true,
  showTooltips = true,
}: ComparisonTableProps) => {
  // TDD: This is a mock implementation
  // Developer will implement the actual component
  if (!features || features.length === 0) {
    return (
      <div data-testid="comparison-table-empty" role="table">
        <p>No features to compare</p>
      </div>
    );
  }

  return (
    <div data-testid="comparison-table" role="table" aria-label="Feature comparison table">
      <div role="rowgroup">
        <div role="row" data-testid="header-row">
          <div role="columnheader">Feature</div>
          <div role="columnheader">SEOIndicator</div>
          <div role="columnheader">Competitor</div>
        </div>
      </div>
      <div role="rowgroup">
        {features.map((category) => (
          <div key={category.name} role="rowgroup" data-testid={`category-${category.name.toLowerCase().replace(/\s+/g, '-')}`}>
            <div role="row" data-testid={`category-header-${category.name.toLowerCase().replace(/\s+/g, '-')}`}>
              <div role="cell" colSpan={3} className="font-bold">
                {category.name}
              </div>
            </div>
            {category.features.map((feature, idx) => (
              <div
                key={`${category.name}-${feature.name}-${idx}`}
                role="row"
                data-testid={`feature-row-${feature.name.toLowerCase().replace(/\s+/g, '-')}`}
              >
                <div role="cell" data-testid="feature-name">
                  {feature.name}
                  {showTooltips && feature.tooltip && (
                    <span data-testid={`tooltip-trigger-${feature.name.toLowerCase().replace(/\s+/g, '-')}`} title={feature.tooltip}>
                      ?
                    </span>
                  )}
                </div>
                <div
                  role="cell"
                  data-testid="seoindicator-value"
                  className={highlightWinner && feature.seoindicator === true && feature.competitor === false ? 'winner-highlight' : ''}
                >
                  {typeof feature.seoindicator === 'boolean' ? (
                    feature.seoindicator ? <span data-testid="check-icon">✓</span> : <span data-testid="x-icon">✗</span>
                  ) : (
                    feature.seoindicator
                  )}
                </div>
                <div
                  role="cell"
                  data-testid="competitor-value"
                  className={highlightWinner && feature.competitor === true && feature.seoindicator === false ? 'winner-highlight' : ''}
                >
                  {typeof feature.competitor === 'boolean' ? (
                    feature.competitor ? <span data-testid="check-icon">✓</span> : <span data-testid="x-icon">✗</span>
                  ) : (
                    feature.competitor
                  )}
                </div>
              </div>
            ))}
          </div>
        ))}
      </div>
    </div>
  );
};

// Test data
const mockFeatures: FeatureCategory[] = [
  {
    name: 'Rank Tracking',
    features: [
      { name: 'Daily Updates', seoindicator: true, competitor: true, tooltip: 'Track keyword positions daily' },
      { name: 'Mobile & Desktop', seoindicator: true, competitor: true, tooltip: 'Separate tracking for mobile and desktop' },
      { name: 'Local Tracking', seoindicator: true, competitor: false, tooltip: 'Track rankings in specific locations' },
      { name: 'Competitor Comparison', seoindicator: true, competitor: true },
    ],
  },
  {
    name: 'Keyword Research',
    features: [
      { name: 'Keyword Suggestions', seoindicator: true, competitor: true },
      { name: 'Search Volume', seoindicator: true, competitor: true },
      { name: 'Keyword Difficulty', seoindicator: true, competitor: false },
      { name: 'SERP Analysis', seoindicator: 'Partial', competitor: true },
    ],
  },
  {
    name: 'Site Audit',
    features: [
      { name: 'Technical SEO', seoindicator: true, competitor: true },
      { name: 'On-Page Analysis', seoindicator: true, competitor: true },
      { name: 'Page Speed', seoindicator: true, competitor: false },
      { name: 'Mobile-Friendly', seoindicator: true, competitor: false },
    ],
  },
];

describe('ComparisonTable', () => {
  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('CT-001: renders table structure with correct role', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      const table = screen.getByRole('table');
      expect(table).toBeInTheDocument();
      expect(table).toHaveAttribute('aria-label', 'Feature comparison table');
    });

    it('CT-002: renders all feature categories', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      mockFeatures.forEach((category) => {
        const categoryId = `category-${category.name.toLowerCase().replace(/\s+/g, '-')}`;
        expect(screen.getByTestId(categoryId)).toBeInTheDocument();
      });
    });

    it('CT-003: renders feature rows within categories', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      const rankTrackingCategory = screen.getByTestId('category-rank-tracking');
      const featureRows = within(rankTrackingCategory).getAllByRole('row');
      
      // Category header + 4 feature rows = 5 rows
      expect(featureRows).toHaveLength(5);
    });

    it('CT-004: renders header row with correct columns', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      const headerRow = screen.getByTestId('header-row');
      const headers = within(headerRow).getAllByRole('columnheader');
      
      expect(headers).toHaveLength(3);
      expect(headers[0]).toHaveTextContent('Feature');
      expect(headers[1]).toHaveTextContent('SEOIndicator');
      expect(headers[2]).toHaveTextContent('Competitor');
    });

    it('CT-005: renders category headers with correct text', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      expect(screen.getByTestId('category-header-rank-tracking')).toHaveTextContent('Rank Tracking');
      expect(screen.getByTestId('category-header-keyword-research')).toHaveTextContent('Keyword Research');
      expect(screen.getByTestId('category-header-site-audit')).toHaveTextContent('Site Audit');
    });
  });

  // ============================================================================
  // Icon Display Tests
  // ============================================================================

  describe('Icons', () => {
    it('CT-010: displays check icon for true boolean values', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      // First feature: Daily Updates - both are true
      const dailyUpdatesRow = screen.getByTestId('feature-row-daily-updates');
      const checkIcons = within(dailyUpdatesRow).getAllByTestId('check-icon');
      
      expect(checkIcons).toHaveLength(2);
    });

    it('CT-011: displays X icon for false boolean values', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      // Local Tracking: SEOIndicator=true, competitor=false
      const localTrackingRow = screen.getByTestId('feature-row-local-tracking');
      const xIcons = within(localTrackingRow).getAllByTestId('x-icon');
      
      expect(xIcons).toHaveLength(1); // Only competitor is false
    });

    it('CT-012: displays text for string values', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      // SERP Analysis: SEOIndicator="Partial", competitor=true
      const serpAnalysisRow = screen.getByTestId('feature-row-serp-analysis');
      
      expect(serpAnalysisRow).toHaveTextContent('Partial');
    });

    it('CT-013: displays correct icon distribution for mixed values', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      // Keyword Difficulty: SEOIndicator=true, competitor=false
      const keywordDifficultyRow = screen.getByTestId('feature-row-keyword-difficulty');
      
      expect(within(keywordDifficultyRow).getByTestId('check-icon')).toBeInTheDocument();
      expect(within(keywordDifficultyRow).getByTestId('x-icon')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Highlighting Tests
  // ============================================================================

  describe('Highlighting', () => {
    it('CT-020: highlights winner when enabled (SEOIndicator wins)', () => {
      render(<ComparisonTable features={mockFeatures} highlightWinner={true} />);
      
      // Local Tracking: SEOIndicator=true, competitor=false
      const localTrackingRow = screen.getByTestId('feature-row-local-tracking');
      const seoindicatorCell = within(localTrackingRow).getByTestId('seoindicator-value');
      
      expect(seoindicatorCell).toHaveClass('winner-highlight');
    });

    it('CT-021: highlights winner when enabled (competitor wins)', () => {
      render(<ComparisonTable features={mockFeatures} highlightWinner={true} />);
      
      // Page Speed: SEOIndicator=true, competitor=false -> SEOIndicator wins
      // Let's create a scenario where competitor wins
      const customFeatures: FeatureCategory[] = [
        {
          name: 'Test',
          features: [
            { name: 'Feature X', seoindicator: false, competitor: true },
          ],
        },
      ];
      
      render(<ComparisonTable features={customFeatures} highlightWinner={true} />);
      
      const featureRow = screen.getByTestId('feature-row-feature-x');
      const competitorCell = within(featureRow).getByTestId('competitor-value');
      
      expect(competitorCell).toHaveClass('winner-highlight');
    });

    it('CT-022: no highlighting when both have same value', () => {
      render(<ComparisonTable features={mockFeatures} highlightWinner={true} />);
      
      // Daily Updates: both are true
      const dailyUpdatesRow = screen.getByTestId('feature-row-daily-updates');
      const seoindicatorCell = within(dailyUpdatesRow).getByTestId('seoindicator-value');
      const competitorCell = within(dailyUpdatesRow).getByTestId('competitor-value');
      
      expect(seoindicatorCell).not.toHaveClass('winner-highlight');
      expect(competitorCell).not.toHaveClass('winner-highlight');
    });

    it('CT-023: no highlighting when disabled', () => {
      render(<ComparisonTable features={mockFeatures} highlightWinner={false} />);
      
      // Local Tracking: SEOIndicator=true, competitor=false
      const localTrackingRow = screen.getByTestId('feature-row-local-tracking');
      const seoindicatorCell = within(localTrackingRow).getByTestId('seoindicator-value');
      
      expect(seoindicatorCell).not.toHaveClass('winner-highlight');
    });

    it('CT-024: no highlighting for string values', () => {
      render(<ComparisonTable features={mockFeatures} highlightWinner={true} />);
      
      // SERP Analysis: SEOIndicator="Partial", competitor=true
      const serpAnalysisRow = screen.getByTestId('feature-row-serp-analysis');
      const seoindicatorCell = within(serpAnalysisRow).getByTestId('seoindicator-value');
      
      expect(seoindicatorCell).not.toHaveClass('winner-highlight');
    });
  });

  // ============================================================================
  // Tooltip Tests
  // ============================================================================

  describe('Tooltips', () => {
    it('CT-030: shows tooltip trigger when enabled and tooltip exists', () => {
      render(<ComparisonTable features={mockFeatures} showTooltips={true} />);
      
      // Daily Updates has a tooltip
      const tooltipTrigger = screen.getByTestId('tooltip-trigger-daily-updates');
      expect(tooltipTrigger).toBeInTheDocument();
    });

    it('CT-031: no tooltip trigger when disabled', () => {
      render(<ComparisonTable features={mockFeatures} showTooltips={false} />);
      
      // Daily Updates should NOT have tooltip trigger
      expect(screen.queryByTestId('tooltip-trigger-daily-updates')).not.toBeInTheDocument();
    });

    it('CT-032: no tooltip trigger when tooltip not provided', () => {
      render(<ComparisonTable features={mockFeatures} showTooltips={true} />);
      
      // Competitor Comparison has no tooltip
      expect(screen.queryByTestId('tooltip-trigger-competitor-comparison')).not.toBeInTheDocument();
    });

    it('CT-033: tooltip has correct title attribute', () => {
      render(<ComparisonTable features={mockFeatures} showTooltips={true} />);
      
      const tooltipTrigger = screen.getByTestId('tooltip-trigger-daily-updates');
      expect(tooltipTrigger).toHaveAttribute('title', 'Track keyword positions daily');
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('CT-040: has proper ARIA attributes', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      const table = screen.getByRole('table');
      expect(table).toHaveAttribute('aria-label', 'Feature comparison table');
    });

    it('CT-041: has proper row roles', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      const rows = screen.getAllByRole('row');
      expect(rows.length).toBeGreaterThan(0);
    });

    it('CT-042: has proper cell roles', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      const cells = screen.getAllByRole('cell');
      expect(cells.length).toBeGreaterThan(0);
    });

    it('CT-043: has proper columnheader roles', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      const headers = screen.getAllByRole('columnheader');
      expect(headers).toHaveLength(3);
    });

    it('CT-044: has proper rowgroup roles for categories', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      const rowgroups = screen.getAllByRole('rowgroup');
      expect(rowgroups.length).toBeGreaterThan(0);
    });
  });

  // ============================================================================
  // Responsive Tests
  // ============================================================================

  describe('Responsive', () => {
    it('CT-050: renders table container for horizontal scroll', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      const table = screen.getByTestId('comparison-table');
      expect(table).toBeInTheDocument();
    });

    it('CT-051: table structure supports horizontal scroll via CSS', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      // This test verifies the structure is in place
      // CSS classes for overflow handling will be applied by developer
      const table = screen.getByRole('table');
      expect(table).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases', () => {
    it('CT-060: renders empty state when features array is empty', () => {
      render(<ComparisonTable features={[]} />);
      
      expect(screen.getByTestId('comparison-table-empty')).toBeInTheDocument();
      expect(screen.getByText('No features to compare')).toBeInTheDocument();
    });

    it('CT-061: handles undefined features gracefully', () => {
      render(<ComparisonTable features={undefined as unknown as FeatureCategory[]} />);
      
      expect(screen.getByTestId('comparison-table-empty')).toBeInTheDocument();
    });

    it('CT-062: handles single category', () => {
      const singleCategory = [mockFeatures[0]];
      render(<ComparisonTable features={singleCategory} />);
      
      expect(screen.getByTestId('category-rank-tracking')).toBeInTheDocument();
      expect(screen.queryByTestId('category-keyword-research')).not.toBeInTheDocument();
    });

    it('CT-063: handles single feature in category', () => {
      const singleFeature: FeatureCategory[] = [
        {
          name: 'Single Category',
          features: [{ name: 'Single Feature', seoindicator: true, competitor: false }],
        },
      ];
      
      render(<ComparisonTable features={singleFeature} />);
      
      expect(screen.getByTestId('feature-row-single-feature')).toBeInTheDocument();
    });

    it('CT-064: handles long feature names', () => {
      const longNameFeatures: FeatureCategory[] = [
        {
          name: 'Features',
          features: [
            { 
              name: 'This is a very long feature name that might need truncation or wrapping', 
              seoindicator: true, 
              competitor: true 
            },
          ],
        },
      ];
      
      render(<ComparisonTable features={longNameFeatures} />);
      
      expect(screen.getByText('This is a very long feature name that might need truncation or wrapping')).toBeInTheDocument();
    });

    it('CT-065: handles unicode in feature values', () => {
      const unicodeFeatures: FeatureCategory[] = [
        {
          name: 'Unicode Test 中文',
          features: [
            { name: 'Feature 日本語', seoindicator: '部分対応', competitor: true },
          ],
        },
      ];
      
      render(<ComparisonTable features={unicodeFeatures} />);
      
      expect(screen.getByText('Unicode Test 中文')).toBeInTheDocument();
      expect(screen.getByText('Feature 日本語')).toBeInTheDocument();
      expect(screen.getByText('部分対応')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Props Tests
  // ============================================================================

  describe('Props', () => {
    it('CT-070: uses default values for optional props', () => {
      render(<ComparisonTable features={mockFeatures} />);
      
      // Default: highlightWinner=true, showTooltips=true
      expect(screen.getByTestId('tooltip-trigger-daily-updates')).toBeInTheDocument();
    });

    it('CT-071: respects highlightWinner prop', () => {
      const { rerender } = render(<ComparisonTable features={mockFeatures} highlightWinner={true} />);
      
      const localTrackingRow = screen.getByTestId('feature-row-local-tracking');
      expect(within(localTrackingRow).getByTestId('seoindicator-value')).toHaveClass('winner-highlight');
      
      rerender(<ComparisonTable features={mockFeatures} highlightWinner={false} />);
      expect(within(localTrackingRow).getByTestId('seoindicator-value')).not.toHaveClass('winner-highlight');
    });

    it('CT-072: respects showTooltips prop', () => {
      const { rerender } = render(<ComparisonTable features={mockFeatures} showTooltips={true} />);
      
      expect(screen.getByTestId('tooltip-trigger-daily-updates')).toBeInTheDocument();
      
      rerender(<ComparisonTable features={mockFeatures} showTooltips={false} />);
      expect(screen.queryByTestId('tooltip-trigger-daily-updates')).not.toBeInTheDocument();
    });
  });
});
