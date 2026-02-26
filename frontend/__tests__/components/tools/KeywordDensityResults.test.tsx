/**
 * KeywordDensityResults Component Tests
 * Task: TASK-16-2 - Keyword Density Checker Frontend
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Types for analysis results
interface HeadingAnalysis {
  tag: string;
  text: string;
  keywordFound: boolean;
}

interface KeywordWarning {
  type: 'error' | 'warning' | 'info' | 'success';
  message: string;
}

interface KeywordDensityResult {
  keyword: string;
  density: number;
  occurrences: number;
  totalWords: number;
  positions: number[];
  status: 'good' | 'low' | 'over-optimized';
  headings: HeadingAnalysis[];
  warnings: KeywordWarning[];
  analyzedAt: string;
}

// Mock component - will be replaced with actual implementation
interface KeywordDensityResultsProps {
  result: KeywordDensityResult;
  onExport?: () => void;
}

const KeywordDensityResults: React.FC<KeywordDensityResultsProps> = ({ result, onExport }) => {
  const { keyword, density, occurrences, totalWords, headings, warnings, analyzedAt, status } = result;

  const getStatusColor = (): string => {
    if (status === 'good') return 'green';
    if (status === 'low') return 'yellow';
    return 'red';
  };

  const getStatusLabel = (): string => {
    if (status === 'good') return 'Good';
    if (status === 'low') return 'Too low';
    return 'Over-optimized';
  };

  const statusColor = getStatusColor();
  const statusLabel = getStatusLabel();

  const headingsWithKeyword = headings.filter(h => h.keywordFound);
  const headingsWithoutKeyword = headings.filter(h => !h.keywordFound);

  return (
    <div data-testid="keyword-density-results" role="region" aria-label="Keyword Density Results">
      <div className="results-header">
        <h2>Analysis Results for "{keyword}"</h2>
        <time dateTime={analyzedAt}>
          Analyzed: {new Date(analyzedAt).toLocaleString()}
        </time>
      </div>

      {/* Density Section */}
      <section className="density-section" aria-labelledby="density-heading">
        <h3 id="density-heading">Keyword Density</h3>
        <div className={`density-value ${statusColor}`} data-testid="density-value" role="status">
          {density.toFixed(1)}%
          <span className="status-indicator">{statusLabel}</span>
        </div>
        <div className="density-stats">
          <span data-testid="occurrences">{occurrences} occurrences</span>
          <span data-testid="total-words">{totalWords} total words</span>
        </div>
      </section>

      {/* Headings Section */}
      <section className="headings-section" aria-labelledby="headings-heading">
        <h3 id="headings-heading">Heading Analysis</h3>
        {headings.length === 0 ? (
          <p className="no-headings">No headings found in the content.</p>
        ) : (
          <ul className="headings-list">
            {headings.map((heading, index) => (
              <li key={index} className={`heading-item ${heading.keywordFound ? 'found' : 'not-found'}`}>
                <span className="heading-tag">{heading.tag.toUpperCase()}</span>
                <span className="heading-text">{heading.text}</span>
                {heading.keywordFound ? (
                  <span className="keyword-badge success" data-testid="keyword-found">
                    ✓ Keyword found
                  </span>
                ) : (
                  <span className="keyword-badge warning" data-testid="keyword-missing">
                    ⚠ Not found
                  </span>
                )}
              </li>
            ))}
          </ul>
        )}
      </section>

      {/* Summary */}
      <section className="summary-section" aria-labelledby="summary-heading">
        <h3 id="summary-heading">Summary</h3>
        <p>
          Found keyword in {headingsWithKeyword.length} of {headings.length} headings.
        </p>
        {headingsWithoutKeyword.length > 0 && (
          <p className="recommendation">
            Consider adding "{keyword}" to: {headingsWithoutKeyword.map(h => h.text).join(', ')}
          </p>
        )}
      </section>

      {/* Warnings Section */}
      {warnings.length > 0 && (
        <section className="warnings-section" aria-labelledby="warnings-heading">
          <h3 id="warnings-heading">Recommendations</h3>
          <ul className="warnings-list">
            {warnings.map((warning, index) => (
              <li key={index} className={`warning-item ${warning.type}`} role="alert">
                {warning.message}
              </li>
            ))}
          </ul>
        </section>
      )}

      {/* Export Button */}
      {onExport && (
        <button onClick={onExport} className="export-button">
          Export Results
        </button>
      )}
    </div>
  );
};

describe('KeywordDensityResults', () => {
  const defaultResult: KeywordDensityResult = {
    keyword: 'SEO',
    density: 2.5,
    occurrences: 12,
    totalWords: 480,
    positions: [15, 45, 89, 120, 156, 200, 234, 312, 378, 401, 445, 470],
    status: 'good',
    headings: [
      { tag: 'h1', text: 'SEO Best Practices Guide', keywordFound: true },
      { tag: 'h2', text: 'Introduction to SEO', keywordFound: true },
      { tag: 'h2', text: 'On-Page Optimization', keywordFound: false },
    ],
    warnings: [],
    analyzedAt: '2026-02-26T19:30:00Z',
  };

  // UT-017: Displays keyword density percentage
  it('should display keyword density percentage', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    const densityValue = screen.getByTestId('density-value');
    expect(densityValue).toHaveTextContent('2.5%');
  });

  // UT-018: Green status for optimal density
  it('should display green status for optimal density', () => {
    render(<KeywordDensityResults result={{ ...defaultResult, status: 'good' }} />);

    const densityValue = screen.getByTestId('density-value');
    expect(densityValue).toHaveClass('green');
    expect(densityValue).toHaveTextContent('Good');
  });

  // UT-019: Yellow warning for low density
  it('should display yellow warning for low density', () => {
    render(<KeywordDensityResults result={{ ...defaultResult, density: 0.5, status: 'low' }} />);

    const densityValue = screen.getByTestId('density-value');
    expect(densityValue).toHaveClass('yellow');
    expect(densityValue).toHaveTextContent('Too low');
  });

  // UT-020: Red warning for high density
  it('should display red warning for over-optimized density', () => {
    render(<KeywordDensityResults result={{ ...defaultResult, density: 5.0, status: 'over-optimized' }} />);

    const densityValue = screen.getByTestId('density-value');
    expect(densityValue).toHaveClass('red');
    expect(densityValue).toHaveTextContent('Over-optimized');
  });

  // UT-021: Displays keyword count
  it('should display keyword occurrence count', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    expect(screen.getByTestId('occurrences')).toHaveTextContent('12 occurrences');
  });

  // UT-022: Displays total word count
  it('should display total word count', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    expect(screen.getByTestId('total-words')).toHaveTextContent('480 total words');
  });

  // UT-023: Displays keyword positions
  it('should display heading analysis', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    expect(screen.getByText('SEO Best Practices Guide')).toBeInTheDocument();
    expect(screen.getByText('Introduction to SEO')).toBeInTheDocument();
    expect(screen.getByText('On-Page Optimization')).toBeInTheDocument();
  });

  // UT-024: Displays heading analysis
  it('should display heading tags', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    expect(screen.getByText('H1')).toBeInTheDocument();
    expect(screen.getAllByText('H2')).toHaveLength(2);
  });

  // UT-025: Shows keyword in heading success
  it('should show success indicator when keyword found in heading', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    const foundBadges = screen.getAllByTestId('keyword-found');
    expect(foundBadges).toHaveLength(2);
    foundBadges.forEach(badge => {
      expect(badge).toHaveTextContent('Keyword found');
    });
  });

  // UT-026: Shows keyword missing from headings
  it('should show warning when keyword not in heading', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    const missingBadges = screen.getAllByTestId('keyword-missing');
    expect(missingBadges).toHaveLength(1);
    expect(missingBadges[0]).toHaveTextContent('Not found');
  });

  // UT-027: Shows multiple heading matches
  it('should display multiple heading matches correctly', () => {
    const resultWithMultipleMatches: KeywordDensityResult = {
      ...defaultResult,
      headings: [
        { tag: 'h1', text: 'SEO Guide', keywordFound: true },
        { tag: 'h2', text: 'SEO Basics', keywordFound: true },
        { tag: 'h3', text: 'Advanced SEO', keywordFound: true },
      ],
    };

    render(<KeywordDensityResults result={resultWithMultipleMatches} />);

    const foundBadges = screen.getAllByTestId('keyword-found');
    expect(foundBadges).toHaveLength(3);
  });

  // UT-028: Shows analysis timestamp
  it('should display analysis timestamp', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    const timeElement = screen.getByRole('time');
    expect(timeElement).toBeInTheDocument();
    expect(timeElement).toHaveAttribute('datetime', '2026-02-26T19:30:00Z');
    expect(timeElement.textContent).toContain('Analyzed:');
  });

  // UT-029: Shows loading skeleton
  it('should handle empty headings gracefully', () => {
    render(<KeywordDensityResults result={{ ...defaultResult, headings: [] }} />);

    expect(screen.getByText('No headings found in the content.')).toBeInTheDocument();
  });

  // UT-030: Handles empty results
  it('should handle zero occurrences', () => {
    render(
      <KeywordDensityResults
        result={{
          ...defaultResult,
          occurrences: 0,
          density: 0,
          status: 'low',
        }}
      />
    );

    expect(screen.getByTestId('occurrences')).toHaveTextContent('0 occurrences');
    expect(screen.getByTestId('density-value')).toHaveTextContent('0.0%');
  });

  // Additional: Warnings displayed
  it('should display warnings list', () => {
    const resultWithWarnings: KeywordDensityResult = {
      ...defaultResult,
      warnings: [
        { type: 'warning', message: 'Keyword density is too low' },
        { type: 'info', message: 'Consider adding keyword to more headings' },
      ],
    };

    render(<KeywordDensityResults result={resultWithWarnings} />);

    expect(screen.getByRole('alert', { name: /too low/i })).toBeInTheDocument();
    expect(screen.getByRole('alert', { name: /headings/i })).toBeInTheDocument();
  });

  // Additional: Export button
  it('should call onExport when export button clicked', () => {
    const mockExport = jest.fn();
    render(<KeywordDensityResults result={defaultResult} onExport={mockExport} />);

    const exportButton = screen.getByRole('button', { name: /export/i });
    exportButton.click();

    expect(mockExport).toHaveBeenCalled();
  });

  // Additional: No export button when onExport not provided
  it('should not show export button when onExport not provided', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    expect(screen.queryByRole('button', { name: /export/i })).not.toBeInTheDocument();
  });

  // Accessibility: Region landmark
  it('should be a landmark region', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    expect(screen.getByRole('region', { name: /keyword density results/i })).toBeInTheDocument();
  });

  // Accessibility: Sections have headings
  it('should have accessible section headings', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    expect(screen.getByRole('heading', { name: /keyword density/i })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: /heading analysis/i })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: /summary/i })).toBeInTheDocument();
  });

  // Summary section
  it('should display summary of heading matches', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    expect(screen.getByText(/Found keyword in 2 of 3 headings/)).toBeInTheDocument();
  });

  // Recommendation for missing headings
  it('should show recommendation for headings missing keyword', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    expect(screen.getByText(/Consider adding/)).toBeInTheDocument();
    expect(screen.getByText(/On-Page Optimization/)).toBeInTheDocument();
  });

  // Keyword in title
  it('should display the analyzed keyword in header', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    expect(screen.getByRole('heading', { name: /Analysis Results for "SEO"/i })).toBeInTheDocument();
  });

  // Density precision
  it('should display density with one decimal place', () => {
    render(<KeywordDensityResults result={{ ...defaultResult, density: 2.345 }} />);

    expect(screen.getByTestId('density-value')).toHaveTextContent('2.3%');
  });
});
