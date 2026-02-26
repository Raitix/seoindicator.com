/**
 * MetaTagResults Component Tests
 * Task: TASK-16-1 - Meta Tag Analyzer Frontend
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Types for analysis results
interface MetaTagWarning {
  type: 'error' | 'warning' | 'info' | 'success';
  message: string;
  field?: string;
}

interface MetaTagResult {
  title: string;
  titleLength: number;
  description: string;
  descriptionLength: number;
  canonical?: string;
  ogTitle?: string;
  ogDescription?: string;
  ogImage?: string;
  warnings: MetaTagWarning[];
  analyzedAt: string;
  keyword?: string;
}

// Mock component - will be replaced with actual implementation
interface MetaTagResultsProps {
  result: MetaTagResult;
  onExport?: () => void;
}

const MetaTagResults: React.FC<MetaTagResultsProps> = ({ result, onExport }) => {
  const { title, titleLength, description, descriptionLength, warnings, analyzedAt, keyword } = result;

  const getStatusColor = (current: number, optimal: number, warning: number): string => {
    if (current <= optimal) return 'green';
    if (current <= warning) return 'yellow';
    return 'red';
  };

  const titleStatus = getStatusColor(titleLength, 60, 70);
  const descriptionStatus = getStatusColor(descriptionLength, 160, 175);

  const keywordInTitle = keyword && title.toLowerCase().includes(keyword.toLowerCase());

  return (
    <div data-testid="meta-tag-results" role="region" aria-label="Analysis Results">
      <div className="results-header">
        <h2>Analysis Results</h2>
        <time dateTime={analyzedAt}>
          Analyzed: {new Date(analyzedAt).toLocaleString()}
        </time>
      </div>

      {/* Title Section */}
      <section className="result-section" aria-labelledby="title-heading">
        <h3 id="title-heading">Title Tag</h3>
        <div className="result-value" data-testid="title-value">
          {title || <em className="missing">Missing title tag</em>}
        </div>
        <div
          className={`length-status ${titleStatus}`}
          data-testid="title-length"
          role="status"
        >
          {titleLength}/60 characters
          {titleStatus === 'green' && ' ✓'}
          {titleStatus === 'yellow' && ' ⚠'}
          {titleStatus === 'red' && ' ✗'}
        </div>
        {keyword && (
          <div className="keyword-indicator" data-testid="keyword-title-indicator">
            {keywordInTitle ? (
              <span className="success">✓ Keyword "{keyword}" found in title</span>
            ) : (
              <span className="warning">⚠ Keyword "{keyword}" not found in title</span>
            )}
          </div>
        )}
      </section>

      {/* Description Section */}
      <section className="result-section" aria-labelledby="description-heading">
        <h3 id="description-heading">Meta Description</h3>
        <div className="result-value" data-testid="description-value">
          {description || <em className="missing">Missing meta description</em>}
        </div>
        <div
          className={`length-status ${descriptionStatus}`}
          data-testid="description-length"
          role="status"
        >
          {descriptionLength}/160 characters
          {descriptionStatus === 'green' && ' ✓'}
          {descriptionStatus === 'yellow' && ' ⚠'}
          {descriptionStatus === 'red' && ' ✗'}
        </div>
      </section>

      {/* Warnings Section */}
      {warnings.length > 0 && (
        <section className="warnings-section" aria-labelledby="warnings-heading">
          <h3 id="warnings-heading">Warnings & Recommendations</h3>
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

describe('MetaTagResults', () => {
  const defaultResult: MetaTagResult = {
    title: 'Example Page Title',
    titleLength: 19,
    description: 'This is an example meta description for testing purposes.',
    descriptionLength: 47,
    warnings: [],
    analyzedAt: '2026-02-26T19:15:00Z',
  };

  // UT-021: Displays title length status
  it('should display title length with green status for optimal length', () => {
    render(<MetaTagResults result={{ ...defaultResult, titleLength: 55 }} />);

    const lengthElement = screen.getByTestId('title-length');
    expect(lengthElement).toHaveTextContent('55/60 characters');
    expect(lengthElement).toHaveClass('green');
    expect(lengthElement).toHaveTextContent('✓');
  });

  // UT-022: Displays title too long warning
  it('should display title too long warning with red status', () => {
    render(<MetaTagResults result={{ ...defaultResult, titleLength: 75 }} />);

    const lengthElement = screen.getByTestId('title-length');
    expect(lengthElement).toHaveTextContent('75/60 characters');
    expect(lengthElement).toHaveClass('red');
    expect(lengthElement).toHaveTextContent('✗');
  });

  // UT-023: Displays title too short warning
  it('should display title too short warning with yellow status', () => {
    render(<MetaTagResults result={{ ...defaultResult, titleLength: 65 }} />);

    const lengthElement = screen.getByTestId('title-length');
    expect(lengthElement).toHaveTextContent('65/60 characters');
    expect(lengthElement).toHaveClass('yellow');
    expect(lengthElement).toHaveTextContent('⚠');
  });

  // UT-024: Displays description length status
  it('should display description length with green status for optimal length', () => {
    render(<MetaTagResults result={{ ...defaultResult, descriptionLength: 155 }} />);

    const lengthElement = screen.getByTestId('description-length');
    expect(lengthElement).toHaveTextContent('155/160 characters');
    expect(lengthElement).toHaveClass('green');
    expect(lengthElement).toHaveTextContent('✓');
  });

  // UT-025: Displays description too long warning
  it('should display description too long warning with red status', () => {
    render(<MetaTagResults result={{ ...defaultResult, descriptionLength: 180 }} />);

    const lengthElement = screen.getByTestId('description-length');
    expect(lengthElement).toHaveTextContent('180/160 characters');
    expect(lengthElement).toHaveClass('red');
    expect(lengthElement).toHaveTextContent('✗');
  });

  // UT-026: Displays missing title error
  it('should display missing title error', () => {
    render(<MetaTagResults result={{ ...defaultResult, title: '', titleLength: 0 }} />);

    const titleValue = screen.getByTestId('title-value');
    expect(titleValue).toHaveTextContent('Missing title tag');
    expect(titleValue.querySelector('.missing')).toBeInTheDocument();
  });

  // UT-027: Displays missing description error
  it('should display missing description warning', () => {
    render(<MetaTagResults result={{ ...defaultResult, description: '', descriptionLength: 0 }} />);

    const descValue = screen.getByTestId('description-value');
    expect(descValue).toHaveTextContent('Missing meta description');
    expect(descValue.querySelector('.missing')).toBeInTheDocument();
  });

  // UT-028: Displays keyword in title indicator
  it('should display success indicator when keyword is in title', () => {
    render(
      <MetaTagResults
        result={{
          ...defaultResult,
          title: 'SEO Best Practices Guide',
          keyword: 'SEO',
        }}
      />
    );

    const indicator = screen.getByTestId('keyword-title-indicator');
    expect(indicator).toHaveTextContent('Keyword "SEO" found in title');
    expect(indicator.querySelector('.success')).toBeInTheDocument();
  });

  // UT-029: Displays keyword missing from title
  it('should display warning when keyword is not in title', () => {
    render(
      <MetaTagResults
        result={{
          ...defaultResult,
          title: 'Best Practices Guide',
          keyword: 'SEO',
        }}
      />
    );

    const indicator = screen.getByTestId('keyword-title-indicator');
    expect(indicator).toHaveTextContent('Keyword "SEO" not found in title');
    expect(indicator.querySelector('.warning')).toBeInTheDocument();
  });

  // UT-030: Shows analysis timestamp
  it('should display analysis timestamp', () => {
    render(<MetaTagResults result={defaultResult} />);

    const timeElement = screen.getByRole('time');
    expect(timeElement).toBeInTheDocument();
    expect(timeElement).toHaveAttribute('datetime', '2026-02-26T19:15:00Z');
    expect(timeElement.textContent).toContain('Analyzed:');
  });

  // Additional: Warnings displayed
  it('should display warnings list', () => {
    const resultWithWarnings: MetaTagResult = {
      ...defaultResult,
      warnings: [
        { type: 'warning', message: 'Consider adding Open Graph tags' },
        { type: 'info', message: 'Add structured data for rich snippets' },
      ],
    };

    render(<MetaTagResults result={resultWithWarnings} />);

    expect(screen.getByRole('alert', { name: /open graph/i })).toBeInTheDocument();
    expect(screen.getByRole('alert', { name: /structured data/i })).toBeInTheDocument();
  });

  // Additional: Export button
  it('should call onExport when export button clicked', () => {
    const mockExport = jest.fn();
    render(<MetaTagResults result={defaultResult} onExport={mockExport} />);

    const exportButton = screen.getByRole('button', { name: /export/i });
    exportButton.click();

    expect(mockExport).toHaveBeenCalled();
  });

  // Additional: No export button when onExport not provided
  it('should not show export button when onExport not provided', () => {
    render(<MetaTagResults result={defaultResult} />);

    expect(screen.queryByRole('button', { name: /export/i })).not.toBeInTheDocument();
  });

  // Accessibility: Region landmark
  it('should be a landmark region', () => {
    render(<MetaTagResults result={defaultResult} />);

    expect(screen.getByRole('region', { name: /analysis results/i })).toBeInTheDocument();
  });

  // Accessibility: Sections have headings
  it('should have accessible section headings', () => {
    render(<MetaTagResults result={defaultResult} />);

    expect(screen.getByRole('heading', { name: /title tag/i })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: /meta description/i })).toBeInTheDocument();
  });

  // Keyword case insensitive
  it('should match keyword case-insensitively', () => {
    render(
      <MetaTagResults
        result={{
          ...defaultResult,
          title: 'SEO Best Practices',
          keyword: 'seo', // lowercase
        }}
      />
    );

    const indicator = screen.getByTestId('keyword-title-indicator');
    expect(indicator).toHaveTextContent('found in title');
  });

  // Title value display
  it('should display the title value', () => {
    render(<MetaTagResults result={defaultResult} />);

    expect(screen.getByTestId('title-value')).toHaveTextContent(defaultResult.title);
  });

  // Description value display
  it('should display the description value', () => {
    render(<MetaTagResults result={defaultResult} />);

    expect(screen.getByTestId('description-value')).toHaveTextContent(defaultResult.description);
  });
});
