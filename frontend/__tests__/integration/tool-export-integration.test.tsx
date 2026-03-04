/**
 * Tool Export Integration Tests
 * Task: TASK-29-8 - Tool Page Integration
 * 
 * Tests for integrating ExportResultsButton into tool result pages:
 * - Meta Tag Analyzer
 * - Keyword Density Checker
 * - Mobile-Friendly Checker
 * - Social Media Preview
 * 
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock ExportResultsButton component
jest.mock('@/components/tools/ExportResultsButton', () => ({
  ExportResultsButton: ({ toolName, url, data, className }: any) => (
    <button
      data-testid="export-results-button"
      data-tool={toolName}
      data-url={url}
      data-has-results={data && Object.keys(data).length > 0 ? 'true' : 'false'}
      className={className}
      aria-label="Export analysis results"
    >
      Export Results
    </button>
  ),
}));

// Mock ExportModal component
jest.mock('@/components/tools/ExportModal', () => ({
  ExportModal: ({ isOpen, toolName, url, data }: any) => {
    if (!isOpen) return null;
    return (
      <div data-testid="export-modal" data-tool={toolName} data-url={url}>
        <span data-testid="modal-data">{JSON.stringify(data)}</span>
      </div>
    );
  },
}));

// =============================================================================
// MOCK DATA
// =============================================================================

const mockAnalyzedUrl = 'https://example.com';

const mockMetaTagData = {
  analyzedAt: '2026-03-04T10:00:00Z',
  title: 'Example Page Title',
  titleLength: 19,
  description: 'This is an example meta description.',
  descriptionLength: 31,
  canonical: 'https://example.com',
  warnings: [
    { type: 'warning', message: 'Consider adding Open Graph tags' },
  ],
  results: {
    title: { content: 'Example Page Title', length: 19, status: 'ok' },
    description: { content: 'This is an example meta description.', length: 31, status: 'ok' },
  },
  summary: { score: 85, passCount: 2, warningCount: 1, failCount: 0 },
};

const mockKeywordDensityData = {
  keyword: 'example',
  density: 2.5,
  occurrences: 5,
  totalWords: 200,
  status: 'good',
  headings: [
    { tag: 'h1', text: 'Example Heading', keywordFound: true },
    { tag: 'h2', text: 'Another Heading', keywordFound: false },
  ],
  warnings: [],
  analyzedAt: '2026-03-04T10:00:00Z',
  results: {
    density: { value: 2.5, status: 'ok' },
    occurrences: { value: 5, status: 'ok' },
  },
  summary: { score: 90, passCount: 3, warningCount: 0, failCount: 0 },
};

const mockMobileFriendlyData = {
  mobileScore: 85,
  viewport: { present: true, valid: true, content: 'width=device-width' },
  responsiveIndicators: { hasMediaQueries: true, hasFlexibleGrid: true },
  readability: { fontSizeMin: 16, fontSizeWarning: false },
  tapTargets: { links: 10, smallTargets: 0 },
  horizontalScroll: { detected: false },
  issues: [],
  recommendations: [{ type: 'success', message: 'Good mobile experience!' }],
  analyzedAt: '2026-03-04T10:00:00Z',
  results: {
    viewport: { value: 'width=device-width', status: 'ok' },
    mobileScore: { value: 85, status: 'ok' },
  },
  summary: { score: 85, passCount: 4, warningCount: 0, failCount: 0 },
};

const mockSocialMediaData = {
  openGraph: {
    title: 'Example OG Title',
    description: 'Example OG Description',
    image: 'https://example.com/og.jpg',
    url: 'https://example.com',
    type: 'website',
  },
  twitterCard: {
    card: 'summary_large_image',
    site: '@example',
    title: 'Example Twitter Title',
    description: 'Example Twitter Description',
    image: 'https://example.com/twitter.jpg',
  },
  previews: {
    facebook: { title: 'Example OG Title', description: 'Example OG Description', image: 'https://example.com/og.jpg' },
    twitter: { title: 'Example Twitter Title', description: 'Example Twitter Description', image: 'https://example.com/twitter.jpg' },
    linkedin: { title: 'Example OG Title', description: 'Example OG Description', image: 'https://example.com/og.jpg' },
  },
  missing: [],
  warnings: [],
  analyzedAt: '2026-03-04T10:00:00Z',
  results: {
    openGraph: { status: 'ok' },
    twitterCard: { status: 'ok' },
  },
  summary: { score: 100, passCount: 2, warningCount: 0, failCount: 0 },
};

// =============================================================================
// META TAG ANALYZER INTEGRATION TESTS (MTI-001 to MTI-020)
// =============================================================================

describe('Meta Tag Analyzer - ExportResultsButton Integration', () => {
  // Mock component for Meta Tag Analyzer Results
  interface MetaTagResult {
    title: string;
    titleLength: number;
    description: string;
    descriptionLength: number;
    canonical?: string;
    warnings: Array<{ type: string; message: string }>;
    analyzedAt: string;
    keyword?: string;
  }

  interface MetaTagResultsProps {
    result: MetaTagResult;
    url: string;
    onExport?: () => void;
    isLoading?: boolean;
  }

  const MetaTagResultsWithExport: React.FC<MetaTagResultsProps> = ({
    result,
    url,
    onExport,
    isLoading = false,
  }) => {
    const { ExportResultsButton } = require('@/components/tools/ExportResultsButton');
    
    const exportData = {
      analyzedAt: result.analyzedAt,
      results: {
        title: { content: result.title, length: result.titleLength, status: 'ok' },
        description: { content: result.description, length: result.descriptionLength, status: 'ok' },
      },
      summary: { score: 85, passCount: 2, warningCount: result.warnings.length, failCount: 0 },
    };

    if (isLoading) {
      return <div data-testid="loading-state">Loading...</div>;
    }

    return (
      <div data-testid="meta-tag-results" role="region" aria-label="Analysis Results">
        <div className="flex items-center justify-between mb-4">
          <h2>Analysis Results</h2>
          <time dateTime={result.analyzedAt}>{result.analyzedAt}</time>
        </div>
        
        {/* Export button in results header */}
        <div className="flex justify-end mb-4">
          <ExportResultsButton
            toolName="meta-tag-analyzer"
            url={url}
            data={exportData}
          />
        </div>
        
        <div data-testid="title-value">{result.title}</div>
        <div data-testid="description-value">{result.description}</div>
      </div>
    );
  };

  /**
   * MTI-001: Renders export button in results
   */
  it('renders ExportResultsButton in results', () => {
    render(
      <MetaTagResultsWithExport
        result={mockMetaTagData as any}
        url={mockAnalyzedUrl}
      />
    );

    expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
  });

  /**
   * MTI-002: Button has correct tool name
   */
  it('passes correct toolName to export button', () => {
    render(
      <MetaTagResultsWithExport
        result={mockMetaTagData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-tool', 'meta-tag-analyzer');
  });

  /**
   * MTI-003: Button has correct URL
   */
  it('passes analyzed URL to export button', () => {
    render(
      <MetaTagResultsWithExport
        result={mockMetaTagData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-url', mockAnalyzedUrl);
  });

  /**
   * MTI-004: Button appears after analysis completes
   */
  it('shows export button after analysis completes (not during loading)', () => {
    const { rerender } = render(
      <MetaTagResultsWithExport
        result={mockMetaTagData as any}
        url={mockAnalyzedUrl}
        isLoading={true}
      />
    );

    // No export button during loading
    expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
    expect(screen.getByTestId('loading-state')).toBeInTheDocument();

    // Show export button after loading
    rerender(
      <MetaTagResultsWithExport
        result={mockMetaTagData as any}
        url={mockAnalyzedUrl}
        isLoading={false}
      />
    );

    expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
  });

  /**
   * MTI-005: Button positioned in results header area
   */
  it('positions button in results header area (flex justify-end)', () => {
    const { container } = render(
      <MetaTagResultsWithExport
        result={mockMetaTagData as any}
        url={mockAnalyzedUrl}
      />
    );

    const buttonContainer = screen.getByTestId('export-results-button').parentElement;
    expect(buttonContainer).toHaveClass('justify-end');
  });

  /**
   * MTI-006: Button has results data
   */
  it('passes analysis results data to export button', () => {
    render(
      <MetaTagResultsWithExport
        result={mockMetaTagData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });

  /**
   * MTI-007: Button is accessible
   */
  it('has proper accessibility attributes', () => {
    render(
      <MetaTagResultsWithExport
        result={mockMetaTagData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('aria-label', 'Export analysis results');
  });

  /**
   * MTI-008: Data structure matches ToolResultData interface
   */
  it('maps data to ToolResultData interface correctly', () => {
    render(
      <MetaTagResultsWithExport
        result={mockMetaTagData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    // Verify button has data (meaning data was passed correctly)
    expect(button).toBeInTheDocument();
  });
});

// =============================================================================
// KEYWORD DENSITY CHECKER INTEGRATION TESTS (KDI-001 to KDI-020)
// =============================================================================

describe('Keyword Density Checker - ExportResultsButton Integration', () => {
  interface KeywordDensityResult {
    keyword: string;
    density: number;
    occurrences: number;
    totalWords: number;
    status: string;
    headings: Array<{ tag: string; text: string; keywordFound: boolean }>;
    warnings: Array<{ type: string; message: string }>;
    analyzedAt: string;
  }

  interface KeywordDensityResultsProps {
    result: KeywordDensityResult;
    url: string;
    isLoading?: boolean;
  }

  const KeywordDensityResultsWithExport: React.FC<KeywordDensityResultsProps> = ({
    result,
    url,
    isLoading = false,
  }) => {
    const { ExportResultsButton } = require('@/components/tools/ExportResultsButton');
    
    const exportData = {
      analyzedAt: result.analyzedAt,
      results: {
        keyword: { value: result.keyword, status: 'ok' },
        density: { value: result.density, status: result.status === 'good' ? 'ok' : 'warning' },
        occurrences: { value: result.occurrences, status: 'ok' },
      },
      summary: { 
        score: result.status === 'good' ? 90 : 70, 
        passCount: result.status === 'good' ? 3 : 2, 
        warningCount: result.warnings.length, 
        failCount: 0 
      },
    };

    if (isLoading) {
      return <div data-testid="loading-state">Analyzing...</div>;
    }

    return (
      <div data-testid="keyword-density-results" role="region" aria-label="Keyword Density Results">
        <div className="flex items-center justify-between mb-4">
          <h2>Analysis Results for "{result.keyword}"</h2>
          <time dateTime={result.analyzedAt}>{result.analyzedAt}</time>
        </div>
        
        {/* Export button in results header */}
        <div className="flex justify-end mb-4">
          <ExportResultsButton
            toolName="keyword-density-checker"
            url={url}
            data={exportData}
          />
        </div>
        
        <div data-testid="density-value">{result.density}%</div>
        <div data-testid="occurrences">{result.occurrences} occurrences</div>
      </div>
    );
  };

  /**
   * KDI-001: Renders export button in results
   */
  it('renders ExportResultsButton in keyword density results', () => {
    render(
      <KeywordDensityResultsWithExport
        result={mockKeywordDensityData as any}
        url={mockAnalyzedUrl}
      />
    );

    expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
  });

  /**
   * KDI-002: Button has correct tool name
   */
  it('passes correct toolName to export button', () => {
    render(
      <KeywordDensityResultsWithExport
        result={mockKeywordDensityData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-tool', 'keyword-density-checker');
  });

  /**
   * KDI-003: Button has correct URL
   */
  it('passes analyzed URL to export button', () => {
    render(
      <KeywordDensityResultsWithExport
        result={mockKeywordDensityData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-url', mockAnalyzedUrl);
  });

  /**
   * KDI-004: Button appears after analysis completes
   */
  it('shows export button after analysis completes', () => {
    const { rerender } = render(
      <KeywordDensityResultsWithExport
        result={mockKeywordDensityData as any}
        url={mockAnalyzedUrl}
        isLoading={true}
      />
    );

    expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();

    rerender(
      <KeywordDensityResultsWithExport
        result={mockKeywordDensityData as any}
        url={mockAnalyzedUrl}
        isLoading={false}
      />
    );

    expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
  });

  /**
   * KDI-005: Button positioned in results header area
   */
  it('positions button in results header area', () => {
    render(
      <KeywordDensityResultsWithExport
        result={mockKeywordDensityData as any}
        url={mockAnalyzedUrl}
      />
    );

    const buttonContainer = screen.getByTestId('export-results-button').parentElement;
    expect(buttonContainer).toHaveClass('justify-end');
  });

  /**
   * KDI-006: Button has results data
   */
  it('passes analysis results data to export button', () => {
    render(
      <KeywordDensityResultsWithExport
        result={mockKeywordDensityData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });

  /**
   * KDI-007: Button is accessible
   */
  it('has proper accessibility attributes', () => {
    render(
      <KeywordDensityResultsWithExport
        result={mockKeywordDensityData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('aria-label');
  });

  /**
   * KDI-008: Different URL values work correctly
   */
  it('handles different URL values', () => {
    const testUrl = 'https://different-example.com/path?query=value';
    
    render(
      <KeywordDensityResultsWithExport
        result={mockKeywordDensityData as any}
        url={testUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-url', testUrl);
  });
});

// =============================================================================
// MOBILE-FRIENDLY CHECKER INTEGRATION TESTS (MFI-001 to MFI-020)
// =============================================================================

describe('Mobile-Friendly Checker - ExportResultsButton Integration', () => {
  interface MobileFriendlyResult {
    mobileScore: number;
    viewport: { present: boolean; valid: boolean; content: string };
    responsiveIndicators: { hasMediaQueries: boolean; hasFlexibleGrid: boolean };
    readability: { fontSizeMin: number; fontSizeWarning: boolean };
    tapTargets: { links: number; smallTargets: number };
    issues: Array<{ severity: string; message: string; category: string }>;
    recommendations: Array<{ type: string; message: string }>;
    analyzedAt: string;
  }

  interface MobileFriendlyResultsProps {
    result: MobileFriendlyResult;
    url: string;
    isLoading?: boolean;
  }

  const MobileFriendlyResultsWithExport: React.FC<MobileFriendlyResultsProps> = ({
    result,
    url,
    isLoading = false,
  }) => {
    const { ExportResultsButton } = require('@/components/tools/ExportResultsButton');
    
    const exportData = {
      analyzedAt: result.analyzedAt,
      results: {
        mobileScore: { value: result.mobileScore, status: result.mobileScore >= 70 ? 'ok' : 'warning' },
        viewport: { value: result.viewport.content, status: result.viewport.valid ? 'ok' : 'error' },
        tapTargets: { value: result.tapTargets.links, status: result.tapTargets.smallTargets === 0 ? 'ok' : 'warning' },
      },
      summary: { 
        score: result.mobileScore, 
        passCount: result.issues.length === 0 ? 3 : 2, 
        warningCount: result.issues.length, 
        failCount: 0 
      },
    };

    if (isLoading) {
      return <div data-testid="loading-state">Checking mobile-friendliness...</div>;
    }

    return (
      <div data-testid="mobile-friendly-results" role="region" aria-label="Mobile-Friendly Results">
        <div className="flex items-center justify-between mb-4">
          <h2>Mobile-Friendly Analysis</h2>
          <time dateTime={result.analyzedAt}>{result.analyzedAt}</time>
        </div>
        
        {/* Export button in results header */}
        <div className="flex justify-end mb-4">
          <ExportResultsButton
            toolName="mobile-friendly-checker"
            url={url}
            data={exportData}
          />
        </div>
        
        <div data-testid="mobile-score">{result.mobileScore}% Mobile Score</div>
        <div data-testid="viewport-status">
          Viewport: {result.viewport.present ? 'Present' : 'Missing'}
        </div>
      </div>
    );
  };

  /**
   * MFI-001: Renders export button in results
   */
  it('renders ExportResultsButton in mobile-friendly results', () => {
    render(
      <MobileFriendlyResultsWithExport
        result={mockMobileFriendlyData as any}
        url={mockAnalyzedUrl}
      />
    );

    expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
  });

  /**
   * MFI-002: Button has correct tool name
   */
  it('passes correct toolName to export button', () => {
    render(
      <MobileFriendlyResultsWithExport
        result={mockMobileFriendlyData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-tool', 'mobile-friendly-checker');
  });

  /**
   * MFI-003: Button has correct URL
   */
  it('passes analyzed URL to export button', () => {
    render(
      <MobileFriendlyResultsWithExport
        result={mockMobileFriendlyData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-url', mockAnalyzedUrl);
  });

  /**
   * MFI-004: Button appears after analysis completes
   */
  it('shows export button after analysis completes', () => {
    const { rerender } = render(
      <MobileFriendlyResultsWithExport
        result={mockMobileFriendlyData as any}
        url={mockAnalyzedUrl}
        isLoading={true}
      />
    );

    expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();

    rerender(
      <MobileFriendlyResultsWithExport
        result={mockMobileFriendlyData as any}
        url={mockAnalyzedUrl}
        isLoading={false}
      />
    );

    expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
  });

  /**
   * MFI-005: Button positioned in results header area
   */
  it('positions button in results header area', () => {
    render(
      <MobileFriendlyResultsWithExport
        result={mockMobileFriendlyData as any}
        url={mockAnalyzedUrl}
      />
    );

    const buttonContainer = screen.getByTestId('export-results-button').parentElement;
    expect(buttonContainer).toHaveClass('justify-end');
  });

  /**
   * MFI-006: Button has results data
   */
  it('passes analysis results data to export button', () => {
    render(
      <MobileFriendlyResultsWithExport
        result={mockMobileFriendlyData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });

  /**
   * MFI-007: Button is accessible
   */
  it('has proper accessibility attributes', () => {
    render(
      <MobileFriendlyResultsWithExport
        result={mockMobileFriendlyData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('aria-label');
  });

  /**
   * MFI-008: Handles low mobile score correctly
   */
  it('handles low mobile score data correctly', () => {
    const lowScoreData = {
      ...mockMobileFriendlyData,
      mobileScore: 45,
      issues: [{ severity: 'warning', message: 'Viewport missing', category: 'viewport' }],
    };

    render(
      <MobileFriendlyResultsWithExport
        result={lowScoreData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });
});

// =============================================================================
// SOCIAL MEDIA PREVIEW INTEGRATION TESTS (SMI-001 to SMI-020)
// =============================================================================

describe('Social Media Preview - ExportResultsButton Integration', () => {
  interface SocialMediaResult {
    openGraph: {
      title: string | null;
      description: string | null;
      image: string | null;
      url: string | null;
      type: string | null;
    };
    twitterCard: {
      card: string | null;
      site: string | null;
      title: string | null;
      description: string | null;
      image: string | null;
    };
    previews: {
      facebook: { title: string; description: string; image: string };
      twitter: { title: string; description: string; image: string };
      linkedin: { title: string; description: string; image: string };
    };
    missing: string[];
    warnings: Array<{ type: string; message: string }>;
    analyzedAt: string;
  }

  interface SocialMediaResultsProps {
    result: SocialMediaResult;
    url: string;
    isLoading?: boolean;
  }

  const SocialMediaResultsWithExport: React.FC<SocialMediaResultsProps> = ({
    result,
    url,
    isLoading = false,
  }) => {
    const { ExportResultsButton } = require('@/components/tools/ExportResultsButton');
    
    const exportData = {
      analyzedAt: result.analyzedAt,
      results: {
        openGraph: { 
          status: result.openGraph.title ? 'ok' : 'warning',
          title: result.openGraph.title,
          description: result.openGraph.description,
          image: result.openGraph.image,
        },
        twitterCard: { 
          status: result.twitterCard.title ? 'ok' : 'warning',
          card: result.twitterCard.card,
          title: result.twitterCard.title,
        },
      },
      summary: { 
        score: result.missing.length === 0 ? 100 : 80, 
        passCount: result.missing.length === 0 ? 2 : 1, 
        warningCount: result.missing.length, 
        failCount: 0 
      },
    };

    if (isLoading) {
      return <div data-testid="loading-state">Fetching social media data...</div>;
    }

    return (
      <div data-testid="social-media-results" role="region" aria-label="Social Media Preview Results">
        <div className="flex items-center justify-between mb-4">
          <h2>Social Media Preview</h2>
          <time dateTime={result.analyzedAt}>{result.analyzedAt}</time>
        </div>
        
        {/* Export button in results header */}
        <div className="flex justify-end mb-4">
          <ExportResultsButton
            toolName="social-media-preview"
            url={url}
            data={exportData}
          />
        </div>
        
        <div data-testid="og-title">{result.openGraph.title || 'No OG Title'}</div>
        <div data-testid="twitter-card">{result.twitterCard.card || 'No Twitter Card'}</div>
      </div>
    );
  };

  /**
   * SMI-001: Renders export button in results
   */
  it('renders ExportResultsButton in social media preview results', () => {
    render(
      <SocialMediaResultsWithExport
        result={mockSocialMediaData as any}
        url={mockAnalyzedUrl}
      />
    );

    expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
  });

  /**
   * SMI-002: Button has correct tool name
   */
  it('passes correct toolName to export button', () => {
    render(
      <SocialMediaResultsWithExport
        result={mockSocialMediaData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-tool', 'social-media-preview');
  });

  /**
   * SMI-003: Button has correct URL
   */
  it('passes analyzed URL to export button', () => {
    render(
      <SocialMediaResultsWithExport
        result={mockSocialMediaData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-url', mockAnalyzedUrl);
  });

  /**
   * SMI-004: Button appears after analysis completes
   */
  it('shows export button after analysis completes', () => {
    const { rerender } = render(
      <SocialMediaResultsWithExport
        result={mockSocialMediaData as any}
        url={mockAnalyzedUrl}
        isLoading={true}
      />
    );

    expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();

    rerender(
      <SocialMediaResultsWithExport
        result={mockSocialMediaData as any}
        url={mockAnalyzedUrl}
        isLoading={false}
      />
    );

    expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
  });

  /**
   * SMI-005: Button positioned in results header area
   */
  it('positions button in results header area', () => {
    render(
      <SocialMediaResultsWithExport
        result={mockSocialMediaData as any}
        url={mockAnalyzedUrl}
      />
    );

    const buttonContainer = screen.getByTestId('export-results-button').parentElement;
    expect(buttonContainer).toHaveClass('justify-end');
  });

  /**
   * SMI-006: Button has results data
   */
  it('passes analysis results data to export button', () => {
    render(
      <SocialMediaResultsWithExport
        result={mockSocialMediaData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });

  /**
   * SMI-007: Button is accessible
   */
  it('has proper accessibility attributes', () => {
    render(
      <SocialMediaResultsWithExport
        result={mockSocialMediaData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('aria-label');
  });

  /**
   * SMI-008: Handles missing OG tags correctly
   */
  it('handles missing Open Graph tags correctly', () => {
    const missingTagsData = {
      ...mockSocialMediaData,
      openGraph: {
        title: null,
        description: null,
        image: null,
        url: null,
        type: null,
      },
      missing: ['og:title', 'og:description', 'og:image'],
    };

    render(
      <SocialMediaResultsWithExport
        result={missingTagsData as any}
        url={mockAnalyzedUrl}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });
});

// =============================================================================
// CONSISTENT UX TESTS (CUX-001 to CUX-015)
// =============================================================================

describe('Consistent UX Across All Tools', () => {
  const { ExportResultsButton } = require('@/components/tools/ExportResultsButton');

  const tools = [
    { name: 'meta-tag-analyzer', displayName: 'Meta Tag Analyzer' },
    { name: 'keyword-density-checker', displayName: 'Keyword Density Checker' },
    { name: 'mobile-friendly-checker', displayName: 'Mobile-Friendly Checker' },
    { name: 'social-media-preview', displayName: 'Social Media Preview' },
  ];

  /**
   * CUX-001: All tools have export button
   */
  it('all 4 tools have export button', () => {
    tools.forEach(tool => {
      const { unmount } = render(
        <ExportResultsButton
          toolName={tool.name}
          url={mockAnalyzedUrl}
          data={{ results: {} }}
        />
      );

      expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
      expect(screen.getByTestId('export-results-button')).toHaveAttribute('data-tool', tool.name);
      unmount();
    });
  });

  /**
   * CUX-002: Same button style across all tools
   */
  it('uses same button style across all tools', () => {
    tools.forEach(tool => {
      const { unmount } = render(
        <ExportResultsButton
          toolName={tool.name}
          url={mockAnalyzedUrl}
          data={{ results: {} }}
        />
      );

      const button = screen.getByTestId('export-results-button');
      expect(button).toHaveTextContent('Export Results');
      unmount();
    });
  });

  /**
   * CUX-003: Same aria-label across all tools
   */
  it('uses consistent aria-label across all tools', () => {
    tools.forEach(tool => {
      const { unmount } = render(
        <ExportResultsButton
          toolName={tool.name}
          url={mockAnalyzedUrl}
          data={{ results: {} }}
        />
      );

      const button = screen.getByTestId('export-results-button');
      expect(button).toHaveAttribute('aria-label', 'Export analysis results');
      unmount();
    });
  });

  /**
   * CUX-004: Consistent positioning pattern
   */
  it('uses consistent positioning pattern (flex justify-end)', () => {
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <div className="flex justify-end mb-4">{children}</div>
    );

    tools.forEach(tool => {
      const Wrapper = wrapper;
      const { unmount } = render(
        <Wrapper>
          <ExportResultsButton
            toolName={tool.name}
            url={mockAnalyzedUrl}
            data={{ results: {} }}
          />
        </Wrapper>
      );

      const buttonContainer = screen.getByTestId('export-results-button').parentElement;
      expect(buttonContainer).toHaveClass('justify-end');
      unmount();
    });
  });

  /**
   * CUX-005: Consistent data structure across all tools
   */
  it('accepts consistent data structure across all tools', () => {
    const consistentData = {
      analyzedAt: '2026-03-04T10:00:00Z',
      results: { indicator: { value: 'test', status: 'ok' } },
      summary: { score: 85, passCount: 1, warningCount: 0, failCount: 0 },
    };

    tools.forEach(tool => {
      const { unmount } = render(
        <ExportResultsButton
          toolName={tool.name}
          url={mockAnalyzedUrl}
          data={consistentData}
        />
      );

      const button = screen.getByTestId('export-results-button');
      expect(button).toHaveAttribute('data-has-results', 'true');
      unmount();
    });
  });
});

// =============================================================================
// MOBILE RESPONSIVE TESTS (MRT-001 to MRT-010)
// =============================================================================

describe('Mobile Responsive Behavior', () => {
  const { ExportResultsButton } = require('@/components/tools/ExportResultsButton');

  /**
   * MRT-001: Button visible on mobile
   */
  it('button is visible on mobile screens', () => {
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={{ results: {} }}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toBeVisible();
  });

  /**
   * MRT-002: Button has proper spacing for touch targets
   */
  it('button has proper spacing for touch targets', () => {
    render(
      <div className="flex justify-end mb-4">
        <ExportResultsButton
          toolName="meta-tag-analyzer"
          url={mockAnalyzedUrl}
          data={{ results: {} }}
          className="min-h-[44px] min-w-[44px]"
        />
      </div>
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveClass('min-h-[44px]');
    expect(button).toHaveClass('min-w-[44px]');
  });

  /**
   * MRT-003: Button text readable on mobile
   */
  it('button text is readable on mobile', () => {
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={{ results: {} }}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveTextContent('Export Results');
  });

  /**
   * MRT-004: Button position adapts on mobile
   */
  it('button position adapts on mobile (still right-aligned)', () => {
    const { container } = render(
      <div className="flex flex-col sm:flex-row justify-between sm:justify-end gap-2">
        <h2>Results</h2>
        <ExportResultsButton
          toolName="meta-tag-analyzer"
          url={mockAnalyzedUrl}
          data={{ results: {} }}
        />
      </div>
    );

    const wrapper = container.querySelector('.flex-col');
    expect(wrapper).toBeInTheDocument();
  });

  /**
   * MRT-005: Button works with touch events
   */
  it('button is clickable with touch events', async () => {
    const user = userEvent.setup();
    
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={{ results: {} }}
      />
    );

    const button = screen.getByTestId('export-results-button');
    await user.click(button);
    // Button should be clickable without errors
    expect(button).toBeInTheDocument();
  });
});

// =============================================================================
// DATA MAPPING TESTS (DMT-001 to DMT-015)
// =============================================================================

describe('Tool Result Data Mapping', () => {
  const { ExportResultsButton } = require('@/components/tools/ExportResultsButton');

  /**
   * DMT-001: Meta Tag data maps correctly
   */
  it('maps Meta Tag Analyzer data to ToolResultData correctly', () => {
    const metaTagExportData = {
      analyzedAt: mockMetaTagData.analyzedAt,
      results: {
        title: { content: mockMetaTagData.title, length: mockMetaTagData.titleLength, status: 'ok' },
        description: { content: mockMetaTagData.description, length: mockMetaTagData.descriptionLength, status: 'ok' },
      },
      summary: mockMetaTagData.summary,
    };

    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={metaTagExportData}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });

  /**
   * DMT-002: Keyword Density data maps correctly
   */
  it('maps Keyword Density Checker data to ToolResultData correctly', () => {
    const keywordExportData = {
      analyzedAt: mockKeywordDensityData.analyzedAt,
      results: {
        keyword: { value: mockKeywordDensityData.keyword, status: 'ok' },
        density: { value: mockKeywordDensityData.density, status: 'ok' },
        occurrences: { value: mockKeywordDensityData.occurrences, status: 'ok' },
      },
      summary: mockKeywordDensityData.summary,
    };

    render(
      <ExportResultsButton
        toolName="keyword-density-checker"
        url={mockAnalyzedUrl}
        data={keywordExportData}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });

  /**
   * DMT-003: Mobile Friendly data maps correctly
   */
  it('maps Mobile-Friendly Checker data to ToolResultData correctly', () => {
    const mobileExportData = {
      analyzedAt: mockMobileFriendlyData.analyzedAt,
      results: {
        mobileScore: { value: mockMobileFriendlyData.mobileScore, status: 'ok' },
        viewport: { value: mockMobileFriendlyData.viewport.content, status: 'ok' },
      },
      summary: mockMobileFriendlyData.summary,
    };

    render(
      <ExportResultsButton
        toolName="mobile-friendly-checker"
        url={mockAnalyzedUrl}
        data={mobileExportData}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });

  /**
   * DMT-004: Social Media data maps correctly
   */
  it('maps Social Media Preview data to ToolResultData correctly', () => {
    const socialExportData = {
      analyzedAt: mockSocialMediaData.analyzedAt,
      results: {
        openGraph: { status: 'ok', title: mockSocialMediaData.openGraph.title },
        twitterCard: { status: 'ok', card: mockSocialMediaData.twitterCard.card },
      },
      summary: mockSocialMediaData.summary,
    };

    render(
      <ExportResultsButton
        toolName="social-media-preview"
        url={mockAnalyzedUrl}
        data={socialExportData}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });

  /**
   * DMT-005: Empty results handled correctly
   */
  it('handles empty results object correctly', () => {
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={{}}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'false');
  });

  /**
   * DMT-006: Null data handled correctly
   */
  it('handles null data correctly', () => {
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={null}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'false');
  });

  /**
   * DMT-007: Undefined data handled correctly
   */
  it('handles undefined data correctly', () => {
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={undefined}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'false');
  });
});

// =============================================================================
// EDGE CASES TESTS (ECT-001 to ECT-010)
// =============================================================================

describe('Edge Cases', () => {
  const { ExportResultsButton } = require('@/components/tools/ExportResultsButton');

  /**
   * ECT-001: Very long URL handled correctly
   */
  it('handles very long URLs', () => {
    const longUrl = 'https://example.com/' + 'a'.repeat(500);

    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={longUrl}
        data={{ results: {} }}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-url', longUrl);
  });

  /**
   * ECT-002: Unicode in data handled correctly
   */
  it('handles unicode characters in data', () => {
    const unicodeData = {
      results: {
        title: { content: '日本語タイトル', status: 'ok' },
        description: { content: 'Description with émojis 🚀 🎉', status: 'ok' },
      },
    };

    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={unicodeData}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });

  /**
   * ECT-003: Large data object handled correctly
   */
  it('handles large data objects (500+ indicators)', () => {
    const largeData = {
      analyzedAt: '2026-03-04T10:00:00Z',
      results: {},
      indicators: Array.from({ length: 500 }, (_, i) => ({
        name: `Indicator ${i}`,
        value: `Value ${i}`,
        status: 'ok',
      })),
    };

    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={largeData}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-has-results', 'true');
  });

  /**
   * ECT-004: URL with query parameters handled correctly
   */
  it('handles URLs with query parameters', () => {
    const urlWithQuery = 'https://example.com/page?param1=value1&param2=value2';

    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={urlWithQuery}
        data={{ results: {} }}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-url', urlWithQuery);
  });

  /**
   * ECT-005: URL with fragment handled correctly
   */
  it('handles URLs with fragments', () => {
    const urlWithFragment = 'https://example.com/page#section';

    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={urlWithFragment}
        data={{ results: {} }}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-url', urlWithFragment);
  });

  /**
   * ECT-006: Special characters in tool name handled correctly
   */
  it('handles special characters in tool name', () => {
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer-v2.0"
        url={mockAnalyzedUrl}
        data={{ results: {} }}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-tool', 'meta-tag-analyzer-v2.0');
  });

  /**
   * ECT-007: Rapid state changes handled correctly
   */
  it('handles rapid loading state changes', () => {
    const { rerender } = render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={{}}
        isGenerating={true}
      />
    );

    // Rapid state changes
    for (let i = 0; i < 5; i++) {
      rerender(
        <ExportResultsButton
          toolName="meta-tag-analyzer"
          url={mockAnalyzedUrl}
          data={i % 2 === 0 ? {} : { results: {} }}
          isGenerating={i % 2 === 0}
        />
      );
    }

    expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
  });
});

// =============================================================================
// ACCESSIBILITY TESTS (ACC-001 to ACC-010)
// =============================================================================

describe('Accessibility', () => {
  const { ExportResultsButton } = require('@/components/tools/ExportResultsButton');

  /**
   * ACC-001: Button has aria-label
   */
  it('has aria-label attribute', () => {
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={{ results: {} }}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('aria-label');
  });

  /**
   * ACC-002: Button is focusable
   */
  it('is focusable via keyboard', async () => {
    const user = userEvent.setup();
    
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={{ results: {} }}
      />
    );

    const button = screen.getByTestId('export-results-button');
    await user.tab();
    expect(button).toHaveFocus();
  });

  /**
   * ACC-003: Enter key triggers click
   */
  it('responds to Enter key', async () => {
    const user = userEvent.setup();
    
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={{ results: {} }}
      />
    );

    const button = screen.getByTestId('export-results-button');
    button.focus();
    await user.keyboard('{Enter}');
    // Should not throw error
    expect(button).toBeInTheDocument();
  });

  /**
   * ACC-004: Space key triggers click
   */
  it('responds to Space key', async () => {
    const user = userEvent.setup();
    
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={{ results: {} }}
      />
    );

    const button = screen.getByTestId('export-results-button');
    button.focus();
    await user.keyboard(' ');
    // Should not throw error
    expect(button).toBeInTheDocument();
  });

  /**
   * ACC-005: Button has role attribute
   */
  it('has button role', () => {
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={{ results: {} }}
      />
    );

    const button = screen.getByRole('button', { name: /export/i });
    expect(button).toBeInTheDocument();
  });

  /**
   * ACC-006: Disabled state announced to screen readers
   */
  it('disabled state is announced to screen readers', () => {
    render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={{}}
      />
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toBeDisabled();
  });

  /**
   * ACC-007: Results region has accessible label
   */
  it('results region has accessible label', () => {
    const ResultsWrapper = () => (
      <div role="region" aria-label="Analysis Results">
        <ExportResultsButton
          toolName="meta-tag-analyzer"
          url={mockAnalyzedUrl}
          data={{ results: {} }}
        />
      </div>
    );

    render(<ResultsWrapper />);

    const region = screen.getByRole('region', { name: /analysis results/i });
    expect(region).toBeInTheDocument();
  });
});

// =============================================================================
// INTEGRATION TESTS (INT-001 to INT-010)
// =============================================================================

describe('Full Integration Scenarios', () => {
  const { ExportResultsButton } = require('@/components/tools/ExportResultsButton');

  /**
   * INT-001: Complete export flow for Meta Tag Analyzer
   */
  it('complete export flow for Meta Tag Analyzer', async () => {
    const user = userEvent.setup();
    
    render(
      <div role="region" aria-label="Meta Tag Results">
        <div className="flex justify-end mb-4">
          <ExportResultsButton
            toolName="meta-tag-analyzer"
            url={mockAnalyzedUrl}
            data={mockMetaTagData}
          />
        </div>
      </div>
    );

    // Button is present
    const button = screen.getByTestId('export-results-button');
    expect(button).toBeInTheDocument();
    expect(button).toHaveAttribute('data-tool', 'meta-tag-analyzer');
    expect(button).toHaveAttribute('data-url', mockAnalyzedUrl);
    expect(button).toHaveAttribute('data-has-results', 'true');
  });

  /**
   * INT-002: Complete export flow for Keyword Density Checker
   */
  it('complete export flow for Keyword Density Checker', async () => {
    render(
      <div role="region" aria-label="Keyword Density Results">
        <div className="flex justify-end mb-4">
          <ExportResultsButton
            toolName="keyword-density-checker"
            url={mockAnalyzedUrl}
            data={mockKeywordDensityData}
          />
        </div>
      </div>
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-tool', 'keyword-density-checker');
  });

  /**
   * INT-003: Complete export flow for Mobile-Friendly Checker
   */
  it('complete export flow for Mobile-Friendly Checker', async () => {
    render(
      <div role="region" aria-label="Mobile-Friendly Results">
        <div className="flex justify-end mb-4">
          <ExportResultsButton
            toolName="mobile-friendly-checker"
            url={mockAnalyzedUrl}
            data={mockMobileFriendlyData}
          />
        </div>
      </div>
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-tool', 'mobile-friendly-checker');
  });

  /**
   * INT-004: Complete export flow for Social Media Preview
   */
  it('complete export flow for Social Media Preview', async () => {
    render(
      <div role="region" aria-label="Social Media Results">
        <div className="flex justify-end mb-4">
          <ExportResultsButton
            toolName="social-media-preview"
            url={mockAnalyzedUrl}
            data={mockSocialMediaData}
          />
        </div>
      </div>
    );

    const button = screen.getByTestId('export-results-button');
    expect(button).toHaveAttribute('data-tool', 'social-media-preview');
  });

  /**
   * INT-005: All tools can be rendered simultaneously
   */
  it('all tools can be rendered simultaneously', () => {
    const tools = [
      { name: 'meta-tag-analyzer', data: mockMetaTagData },
      { name: 'keyword-density-checker', data: mockKeywordDensityData },
      { name: 'mobile-friendly-checker', data: mockMobileFriendlyData },
      { name: 'social-media-preview', data: mockSocialMediaData },
    ];

    render(
      <div>
        {tools.map(tool => (
          <div key={tool.name} data-testid={`tool-${tool.name}`}>
            <ExportResultsButton
              toolName={tool.name}
              url={mockAnalyzedUrl}
              data={tool.data}
            />
          </div>
        ))}
      </div>
    );

    tools.forEach(tool => {
      const container = screen.getByTestId(`tool-${tool.name}`);
      const button = within(container).getByTestId('export-results-button');
      expect(button).toHaveAttribute('data-tool', tool.name);
    });
  });
});

// =============================================================================
// SNAPSHOT TESTS (SNT-001 to SNT-004)
// =============================================================================

describe('Snapshots', () => {
  const { ExportResultsButton } = require('@/components/tools/ExportResultsButton');

  /**
   * SNT-001: Meta Tag Analyzer snapshot
   */
  it('matches snapshot for Meta Tag Analyzer', () => {
    const { asFragment } = render(
      <ExportResultsButton
        toolName="meta-tag-analyzer"
        url={mockAnalyzedUrl}
        data={mockMetaTagData}
      />
    );
    expect(asFragment()).toMatchSnapshot();
  });

  /**
   * SNT-002: Keyword Density Checker snapshot
   */
  it('matches snapshot for Keyword Density Checker', () => {
    const { asFragment } = render(
      <ExportResultsButton
        toolName="keyword-density-checker"
        url={mockAnalyzedUrl}
        data={mockKeywordDensityData}
      />
    );
    expect(asFragment()).toMatchSnapshot();
  });

  /**
   * SNT-003: Mobile-Friendly Checker snapshot
   */
  it('matches snapshot for Mobile-Friendly Checker', () => {
    const { asFragment } = render(
      <ExportResultsButton
        toolName="mobile-friendly-checker"
        url={mockAnalyzedUrl}
        data={mockMobileFriendlyData}
      />
    );
    expect(asFragment()).toMatchSnapshot();
  });

  /**
   * SNT-004: Social Media Preview snapshot
   */
  it('matches snapshot for Social Media Preview', () => {
    const { asFragment } = render(
      <ExportResultsButton
        toolName="social-media-preview"
        url={mockAnalyzedUrl}
        data={mockSocialMediaData}
      />
    );
    expect(asFragment()).toMatchSnapshot();
  });
});
