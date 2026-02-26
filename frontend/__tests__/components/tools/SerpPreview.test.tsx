/**
 * SerpPreview Component Tests
 * Task: TASK-16-1 - Meta Tag Analyzer Frontend
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock component - will be replaced with actual implementation
interface SerpPreviewProps {
  title: string;
  url: string;
  description: string;
  isMobile?: boolean;
  onToggleMobile?: () => void;
}

const SerpPreview: React.FC<SerpPreviewProps> = ({
  title,
  url,
  description,
  isMobile = false,
  onToggleMobile,
}) => {
  const maxTitleLength = 60;
  const maxDescriptionLength = isMobile ? 120 : 160;

  const truncateText = (text: string, maxLength: number): string => {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength - 3) + '...';
  };

  const displayTitle = truncateText(title, maxTitleLength);
  const displayDescription = truncateText(description, maxDescriptionLength);
  const isTitleTruncated = title.length > maxTitleLength;
  const isDescriptionTruncated = description.length > maxDescriptionLength;

  return (
    <div
      data-testid="serp-preview"
      className={`serp-preview ${isMobile ? 'mobile' : 'desktop'}`}
      role="region"
      aria-label="SERP Preview"
    >
      <div className="preview-toggle">
        <button
          onClick={onToggleMobile}
          aria-pressed={isMobile}
          aria-label={isMobile ? 'Switch to desktop view' : 'Switch to mobile view'}
        >
          {isMobile ? 'Desktop' : 'Mobile'}
        </button>
      </div>

      <div className="serp-result">
        <div className="serp-url" data-testid="serp-url">
          {url}
        </div>
        <h3 className="serp-title" data-testid="serp-title">
          {displayTitle || 'No title'}
          {isTitleTruncated && (
            <span className="truncation-indicator" title="Title was truncated">
              (truncated)
            </span>
          )}
        </h3>
        <p className="serp-description" data-testid="serp-description">
          {displayDescription || 'No description available'}
          {isDescriptionTruncated && (
            <span className="truncation-indicator" title="Description was truncated">
              (truncated)
            </span>
          )}
        </p>
      </div>

      {title.length > 0 && title.length < 30 && (
        <div className="warning short-title" role="status">
          Title may be too short for optimal visibility
        </div>
      )}

      {!description && (
        <div className="warning missing-description" role="status">
          Missing meta description - Google may generate one
        </div>
      )}
    </div>
  );
};

describe('SerpPreview', () => {
  const defaultProps = {
    title: 'Example Page Title',
    url: 'https://example.com',
    description: 'This is an example meta description for the page.',
  };

  // UT-011: Renders SERP preview container
  it('should render SERP preview container', () => {
    render(<SerpPreview {...defaultProps} />);

    expect(screen.getByTestId('serp-preview')).toBeInTheDocument();
    expect(screen.getByRole('region', { name: /serp preview/i })).toBeInTheDocument();
  });

  // UT-012: Displays title in preview
  it('should display title in preview', () => {
    render(<SerpPreview {...defaultProps} title="Example Page" />);

    expect(screen.getByTestId('serp-title')).toHaveTextContent('Example Page');
  });

  // UT-013: Displays URL in preview
  it('should display URL in preview', () => {
    render(<SerpPreview {...defaultProps} url="https://example.com/page" />);

    expect(screen.getByTestId('serp-url')).toHaveTextContent('https://example.com/page');
  });

  // UT-014: Displays description in preview
  it('should display description in preview', () => {
    render(<SerpPreview {...defaultProps} description="Example description" />);

    expect(screen.getByTestId('serp-description')).toHaveTextContent('Example description');
  });

  // UT-015: Truncates long title (>60 chars)
  it('should truncate long title over 60 characters', () => {
    const longTitle = 'A very long title that exceeds sixty characters limit and should be truncated';
    render(<SerpPreview {...defaultProps} title={longTitle} />);

    const titleElement = screen.getByTestId('serp-title');
    expect(titleElement.textContent).toContain('...');
    expect(titleElement.querySelector('.truncation-indicator')).toBeInTheDocument();
  });

  // UT-016: Truncates long description (>160 chars)
  it('should truncate long description over 160 characters', () => {
    const longDescription = 'A'.repeat(180);
    render(<SerpPreview {...defaultProps} description={longDescription} />);

    const descElement = screen.getByTestId('serp-description');
    expect(descElement.textContent).toContain('...');
    expect(descElement.querySelector('.truncation-indicator')).toBeInTheDocument();
  });

  // UT-017: Shows warning for short title
  it('should show warning for short title', () => {
    render(<SerpPreview {...defaultProps} title="Short" />);

    expect(screen.getByRole('status')).toHaveTextContent('Title may be too short');
  });

  // UT-018: Shows warning for missing description
  it('should show warning for missing description', () => {
    render(<SerpPreview {...defaultProps} description="" />);

    expect(screen.getByRole('status')).toHaveTextContent('Missing meta description');
  });

  // UT-019: Mobile preview toggle
  it('should toggle to mobile preview when button clicked', () => {
    const mockToggle = jest.fn();
    render(<SerpPreview {...defaultProps} onToggleMobile={mockToggle} />);

    const toggleButton = screen.getByRole('button', { name: /mobile/i });
    fireEvent.click(toggleButton);

    expect(mockToggle).toHaveBeenCalled();
  });

  // UT-020: Desktop preview default
  it('should show desktop layout by default', () => {
    render(<SerpPreview {...defaultProps} />);

    const preview = screen.getByTestId('serp-preview');
    expect(preview).toHaveClass('desktop');
    expect(preview).not.toHaveClass('mobile');
  });

  // Additional: Mobile description truncation (120 chars)
  it('should truncate description at 120 characters in mobile view', () => {
    const longDescription = 'A'.repeat(150);
    render(<SerpPreview {...defaultProps} description={longDescription} isMobile={true} />);

    const descElement = screen.getByTestId('serp-description');
    // 120 - 3 for "..." = 117 characters + "..."
    expect(descElement.textContent?.replace('(truncated)', '').trim().length).toBeLessThanOrEqual(123);
  });

  // Additional: Empty title handling
  it('should show placeholder when title is empty', () => {
    render(<SerpPreview {...defaultProps} title="" />);

    expect(screen.getByTestId('serp-title')).toHaveTextContent('No title');
  });

  // Additional: Empty description handling
  it('should show placeholder when description is empty', () => {
    render(<SerpPreview {...defaultProps} description="" />);

    expect(screen.getByTestId('serp-description')).toHaveTextContent('No description available');
  });

  // Additional: Mobile layout class
  it('should have mobile class when isMobile is true', () => {
    render(<SerpPreview {...defaultProps} isMobile={true} />);

    const preview = screen.getByTestId('serp-preview');
    expect(preview).toHaveClass('mobile');
    expect(preview).not.toHaveClass('desktop');
  });

  // Accessibility: Toggle button state
  it('should have aria-pressed on toggle button', () => {
    render(<SerpPreview {...defaultProps} isMobile={true} />);

    const toggleButton = screen.getByRole('button', { name: /desktop/i });
    expect(toggleButton).toHaveAttribute('aria-pressed', 'true');
  });

  // Accessibility: Region landmark
  it('should be a landmark region', () => {
    render(<SerpPreview {...defaultProps} />);

    expect(screen.getByRole('region')).toBeInTheDocument();
  });

  // Title exactly at limit
  it('should not truncate title at exactly 60 characters', () => {
    const exactTitle = 'A'.repeat(60);
    render(<SerpPreview {...defaultProps} title={exactTitle} />);

    const titleElement = screen.getByTestId('serp-title');
    expect(titleElement.textContent).toBe(exactTitle);
    expect(titleElement.querySelector('.truncation-indicator')).not.toBeInTheDocument();
  });

  // Description exactly at limit
  it('should not truncate description at exactly 160 characters', () => {
    const exactDescription = 'A'.repeat(160);
    render(<SerpPreview {...defaultProps} description={exactDescription} />);

    const descElement = screen.getByTestId('serp-description');
    expect(descElement.textContent).toBe(exactDescription);
    expect(descElement.querySelector('.truncation-indicator')).not.toBeInTheDocument();
  });
});
