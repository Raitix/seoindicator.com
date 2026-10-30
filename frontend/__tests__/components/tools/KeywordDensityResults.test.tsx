/**
 * KeywordDensityResults Component Tests
 * Task: TASK-16-2 - Keyword Density Checker Frontend
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import { KeywordDensityResults, type KeywordDensityResult } from '@/components/tools/KeywordDensityResults';

// Create a minimal result for testing
const createTestResult = (overrides: Partial<KeywordDensityResult> = {}): KeywordDensityResult => ({
  keyword: 'SEO',
  density: 2.5,
  occurrences: 12,
  totalWords: 480,
  positions: [15, 45, 89, 120, 156, 200, 234, 312, 378, 401, 445, 470],
  status: 'good',
  headings: [
    { tag: 'h1', text: 'SEO Best Practices Guide', keywordFound: true, level: 1 },
    { tag: 'h2', text: 'Introduction to SEO', keywordFound: true, level: 2 },
    { tag: 'h2', text: 'On-Page Optimization', keywordFound: false, level: 2 },
  ],
  warnings: [],
  analyzedAt: '2026-02-26T19:30:00Z',
  ...overrides,
});

describe('KeywordDensityResults', () => {
  const defaultResult = createTestResult();

  // UT-017: Displays keyword density percentage
  it('should display keyword density percentage', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    const densityValue = screen.getByTestId('density-value');
    expect(densityValue).toHaveTextContent('2.5%');
  });

  // UT-018: Green status for optimal density
  it('should display green status for optimal density', () => {
    render(<KeywordDensityResults result={createTestResult({ status: 'good' })} />);

    const densityValue = screen.getByTestId('density-value');
    expect(densityValue).toHaveTextContent('Good');
  });

  // UT-019: Yellow warning for low density
  it('should display yellow warning for low density', () => {
    render(<KeywordDensityResults result={createTestResult({ density: 0.5, status: 'low' })} />);

    const densityValue = screen.getByTestId('density-value');
    expect(densityValue).toHaveTextContent('Too low');
  });

  // UT-020: Red warning for high density
  it('should display red warning for over-optimized density', () => {
    render(<KeywordDensityResults result={createTestResult({ density: 5.0, status: 'over-optimized' })} />);

    const densityValue = screen.getByTestId('density-value');
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
    const resultWithMultipleMatches = createTestResult({
      headings: [
        { tag: 'h1', text: 'SEO Guide', keywordFound: true, level: 1 },
        { tag: 'h2', text: 'SEO Basics', keywordFound: true, level: 2 },
        { tag: 'h3', text: 'Advanced SEO', keywordFound: true, level: 3 },
      ],
    });

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
    render(<KeywordDensityResults result={createTestResult({ headings: [] })} />);

    expect(screen.getByText('No headings found in the content.')).toBeInTheDocument();
  });

  // UT-030: Handles empty results
  it('should handle zero occurrences', () => {
    render(
      <KeywordDensityResults
        result={createTestResult({
          occurrences: 0,
          density: 0,
          status: 'low',
        })}
      />
    );

    expect(screen.getByTestId('occurrences')).toHaveTextContent('0 occurrences');
    expect(screen.getByTestId('density-value')).toHaveTextContent('0.0%');
  });

  // Additional: Warnings displayed
  it('should display warnings list', () => {
    const resultWithWarnings = createTestResult({
      warnings: [
        { type: 'warning', message: 'Keyword density is too low' },
        { type: 'info', message: 'Consider adding keyword to more headings' },
      ],
    });

    render(<KeywordDensityResults result={resultWithWarnings} />);

    const alerts = screen.getAllByRole('alert');
    expect(alerts.length).toBeGreaterThanOrEqual(2);
    expect(screen.getByText(/Keyword density is too low/)).toBeInTheDocument();
    expect(screen.getByText(/Consider adding keyword to more headings/)).toBeInTheDocument();
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

    // Main header shows the keyword
    expect(screen.getByRole('heading', { name: /Analysis Results for/i })).toBeInTheDocument();
    // Card titles are divs with data-slot="card-title"
    expect(screen.getByText('Keyword Density')).toBeInTheDocument();
    expect(screen.getByText('Heading Analysis')).toBeInTheDocument();
  });

  // Summary section
  it('should display summary of heading matches', () => {
    render(<KeywordDensityResults result={defaultResult} />);

    // Text is split across elements, use a more flexible matcher
    expect(screen.getByText((content, element) => {
      return element?.textContent?.includes('Found keyword in') && element?.textContent?.includes('2') && element?.textContent?.includes('of 3 headings') ?? false;
    })).toBeInTheDocument();
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
    render(<KeywordDensityResults result={createTestResult({ density: 2.345 })} />);

    expect(screen.getByTestId('density-value')).toHaveTextContent('2.3%');
  });
});
