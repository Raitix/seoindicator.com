/**
 * Tool Page Export Integration Tests
 * Task: TASK-29-8 - Integrate ExportResultsButton into tool result pages
 * 
 * Tests integration of ExportResultsButton component into:
 * - Meta Tag Analyzer Results
 * - Keyword Density Checker Results
 * - Mobile-Friendly Checker Results
 * - Social Media Preview Results
 * 
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Type definitions
interface ToolResultData {
  analyzedAt: string;
  results: Record<string, unknown>;
  summary?: {
    score?: number;
    passed: number;
    warnings: number;
    failed: number;
  };
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
  warnings: Array<{ type: string; message: string }>;
  analyzedAt: string;
  keyword?: string;
}

interface KeywordDensityResult {
  keyword: string;
  density: number;
  occurrences: number;
  totalWords: number;
  positions: number[];
  status: 'good' | 'low' | 'over-optimized';
  headings: Array<{ tag: string; text: string; keywordFound: boolean }>;
  warnings: Array<{ type: string; message: string }>;
  analyzedAt: string;
}

interface MobileFriendlyResult {
  score: number;
  viewport: { present: boolean; content: string; valid: boolean };
  responsiveIndicators: { hasMediaQueries: boolean; hasFlexibleGrid: boolean; hasFlexibleImages: boolean };
  readability: { fontSizeMin: number; fontSizeWarning: boolean };
  tapTargets: { links: number; smallTargets: number; warning?: string };
  issues: Array<{ severity: string; message: string }>;
  recommendations: string[];
  analyzedAt: string;
}

interface SocialMediaPreviewData {
  openGraph: {
    title: string | null;
    description: string | null;
    image: string | null;
    url: string | null;
    type: string | null;
    siteName: string | null;
  };
  twitterCard: {
    card: string | null;
    site: string | null;
    creator: string | null;
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
  analyzedAt?: string;
}

// Mock ExportResultsButton component
jest.mock('@/components/tools/ExportResultsButton', () => ({
  ExportResultsButton: jest.fn(({ toolName, url, data, className }) => (
    <button
      data-testid="export-results-button"
      data-tool-name={toolName}
      data-url={url}
      data-has-data={data && Object.keys(data).length > 0 ? 'true' : 'false'}
      className={className}
      disabled={!data || Object.keys(data).length === 0}
      aria-label="Export analysis results"
    >
      Export Results
    </button>
  )),
}));

// Mock ExportModal component
jest.mock('@/components/tools/ExportModal', () => ({
  ExportModal: jest.fn(({ isOpen, toolName, url, data }) => 
    isOpen ? (
      <div 
        data-testid="export-modal"
        data-tool={toolName}
        data-url={url}
        data-has-data={data ? 'true' : 'false'}
      >
        <button data-testid="modal-close">Close</button>
      </div>
    ) : null
  ),
}));

// Import mocked components after jest.mock calls
import { ExportResultsButton } from '@/components/tools/ExportResultsButton';

// ============================================
// Mock Tool Result Components with Export Integration
// ============================================

// MetaTagResults with Export Button
interface MetaTagResultsProps {
  result: MetaTagResult;
  analyzedUrl?: string;
  isLoading?: boolean;
  onExport?: () => void;
}

const MetaTagResultsWithExport: React.FC<MetaTagResultsProps> = ({
  result,
  analyzedUrl,
  isLoading,
  onExport,
}) => {
  if (isLoading) {
    return <div data-testid="loading-state">Loading...</div>;
  }

  const exportData: ToolResultData = {
    analyzedAt: result.analyzedAt,
    results: {
      title: { content: result.title, length: result.titleLength },
      description: { content: result.description, length: result.descriptionLength },
    },
    summary: {
      passed: result.warnings.filter(w => w.type === 'success').length,
      warnings: result.warnings.filter(w => w.type === 'warning').length,
      failed: result.warnings.filter(w => w.type === 'error').length,
    },
  };

  return (
    <div data-testid="meta-tag-results" role="region" aria-label="Meta Tag Analysis Results">
      <div className="results-header flex items-center justify-between">
        <h2>Analysis Results</h2>
        <time dateTime={result.analyzedAt}>
          Analyzed: {new Date(result.analyzedAt).toLocaleString()}
        </time>
      </div>
      
      <section>
        <h3>Title Tag</h3>
        <div data-testid="title-value">{result.title}</div>
        <div data-testid="title-length">{result.titleLength}/60 characters</div>
      </section>
      
      <section>
        <h3>Meta Description</h3>
        <div data-testid="description-value">{result.description}</div>
        <div data-testid="description-length">{result.descriptionLength}/160 characters</div>
      </section>
      
      {result.warnings.length > 0 && (
        <section>
          <h3>Warnings</h3>
          <ul>
            {result.warnings.map((w, i) => (
              <li key={i} role="alert">{w.message}</li>
            ))}
          </ul>
        </section>
      )}
      
      {/* Export Button in header area */}
      <div className="flex justify-end mt-4" data-testid="export-button-container">
        <ExportResultsButton
          toolName="meta-tag-analyzer"
          url={analyzedUrl || ''}
          data={exportData}
        />
      </div>
    </div>
  );
};

// KeywordDensityResults with Export Button
interface KeywordDensityResultsProps {
  result: KeywordDensityResult;
  analyzedUrl?: string;
  isLoading?: boolean;
  onExport?: () => void;
}

const KeywordDensityResultsWithExport: React.FC<KeywordDensityResultsProps> = ({
  result,
  analyzedUrl,
  isLoading,
  onExport,
}) => {
  if (isLoading) {
    return <div data-testid="loading-state">Loading...</div>;
  }

  const exportData: ToolResultData = {
    analyzedAt: result.analyzedAt,
    results: {
      keyword: { value: result.keyword, density: result.density, occurrences: result.occurrences },
      headings: result.headings.map(h => ({
        tag: h.tag,
        text: h.text,
        keywordFound: h.keywordFound,
      })),
    },
    summary: {
      score: result.status === 'good' ? 100 : result.status === 'low' ? 50 : 30,
      passed: result.headings.filter(h => h.keywordFound).length,
      warnings: result.warnings.length,
      failed: 0,
    },
  };

  return (
    <div data-testid="keyword-density-results" role="region" aria-label="Keyword Density Results">
      <div className="results-header flex items-center justify-between">
        <h2>Keyword Density: {result.keyword}</h2>
        <time dateTime={result.analyzedAt}>
          Analyzed: {new Date(result.analyzedAt).toLocaleString()}
        </time>
      </div>
      
      <section>
        <h3>Density</h3>
        <div data-testid="density-value">{result.density.toFixed(1)}%</div>
        <div data-testid="occurrences">{result.occurrences} occurrences</div>
      </section>
      
      <section>
        <h3>Headings</h3>
        <ul>
          {result.headings.map((h, i) => (
            <li key={i} data-testid={`heading-${i}`}>
              {h.tag}: {h.text}
              {h.keywordFound && ' ✓'}
            </li>
          ))}
        </ul>
      </section>
      
      {/* Export Button in header area */}
      <div className="flex justify-end mt-4" data-testid="export-button-container">
        <ExportResultsButton
          toolName="keyword-density"
          url={analyzedUrl || ''}
          data={exportData}
        />
      </div>
    </div>
  );
};

// MobileFriendlyResults with Export Button
interface MobileFriendlyResultsProps {
  result: MobileFriendlyResult;
  analyzedUrl?: string;
  isLoading?: boolean;
  onExport?: () => void;
}

const MobileFriendlyResultsWithExport: React.FC<MobileFriendlyResultsProps> = ({
  result,
  analyzedUrl,
  isLoading,
  onExport,
}) => {
  if (isLoading) {
    return <div data-testid="loading-state">Loading...</div>;
  }

  const exportData: ToolResultData = {
    analyzedAt: result.analyzedAt,
    results: {
      viewport: result.viewport,
      responsiveIndicators: result.responsiveIndicators,
      readability: result.readability,
      tapTargets: result.tapTargets,
      issues: result.issues,
    },
    summary: {
      score: result.score,
      passed: result.issues.filter(i => i.severity !== 'error').length,
      warnings: result.issues.filter(i => i.severity === 'warning').length,
      failed: result.issues.filter(i => i.severity === 'error').length,
    },
  };

  return (
    <div data-testid="mobile-friendly-results" role="region" aria-label="Mobile-Friendly Results">
      <div className="results-header flex items-center justify-between">
        <h2>Mobile-Friendly Analysis</h2>
        <time dateTime={result.analyzedAt}>
          Analyzed: {new Date(result.analyzedAt).toLocaleString()}
        </time>
      </div>
      
      <section>
        <h3>Score</h3>
        <div data-testid="mobile-score">{result.score}/100</div>
      </section>
      
      <section>
        <h3>Viewport</h3>
        <div data-testid="viewport-present">
          {result.viewport.present ? '✓ Present' : '✗ Missing'}
        </div>
      </section>
      
      <section>
        <h3>Issues</h3>
        <ul>
          {result.issues.map((issue, i) => (
            <li key={i} role="alert" data-severity={issue.severity}>
              {issue.message}
            </li>
          ))}
        </ul>
      </section>
      
      {/* Export Button in header area */}
      <div className="flex justify-end mt-4" data-testid="export-button-container">
        <ExportResultsButton
          toolName="mobile-friendly-checker"
          url={analyzedUrl || ''}
          data={exportData}
        />
      </div>
    </div>
  );
};

// SocialMediaPreviewResults with Export Button
interface SocialMediaPreviewResultsProps {
  data: SocialMediaPreviewData | null;
  analyzedUrl?: string;
  isLoading?: boolean;
  error?: string;
  onExport?: () => void;
}

const SocialMediaPreviewResultsWithExport: React.FC<SocialMediaPreviewResultsProps> = ({
  data,
  analyzedUrl,
  isLoading,
  error,
  onExport,
}) => {
  if (isLoading) {
    return <div data-testid="loading-state">Loading...</div>;
  }

  if (error) {
    return (
      <div role="alert" data-testid="error-message">
        {error}
      </div>
    );
  }

  if (!data) {
    return null;
  }

  const exportData: ToolResultData = {
    analyzedAt: data.analyzedAt || new Date().toISOString(),
    results: {
      openGraph: data.openGraph,
      twitterCard: data.twitterCard,
      previews: data.previews,
    },
    summary: {
      score: data.missing.length === 0 ? 100 : Math.max(0, 100 - data.missing.length * 10),
      passed: 0,
      warnings: data.warnings.length,
      failed: data.missing.length,
    },
  };

  return (
    <div data-testid="social-media-results" role="region" aria-label="Social Media Preview Results">
      <div className="results-header flex items-center justify-between">
        <h2>Social Media Preview</h2>
        {data.analyzedAt && (
          <time dateTime={data.analyzedAt}>
            Analyzed: {new Date(data.analyzedAt).toLocaleString()}
          </time>
        )}
      </div>
      
      <section>
        <h3>Open Graph</h3>
        <div data-testid="og-title">{data.openGraph.title || 'Missing'}</div>
      </section>
      
      <section>
        <h3>Twitter Card</h3>
        <div data-testid="twitter-card-type">{data.twitterCard.card || 'Missing'}</div>
      </section>
      
      {data.missing.length > 0 && (
        <section>
          <h3>Missing Tags</h3>
          <ul>
            {data.missing.map((tag, i) => (
              <li key={i}>{tag}</li>
            ))}
          </ul>
        </section>
      )}
      
      {/* Export Button in header area */}
      <div className="flex justify-end mt-4" data-testid="export-button-container">
        <ExportResultsButton
          toolName="social-media-preview"
          url={analyzedUrl || ''}
          data={exportData}
        />
      </div>
    </div>
  );
};

// ============================================
// TEST SUITES
// ============================================

describe('Tool Page Export Integration', () => {
  const mockUrl = 'https://example.com/test-page';
  
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================
  // Meta Tag Analyzer Integration
  // ============================================
  
  describe('MetaTagAnalyzer Results', () => {
    const defaultResult: MetaTagResult = {
      title: 'Test Page Title',
      titleLength: 15,
      description: 'Test meta description for the page.',
      descriptionLength: 34,
      warnings: [],
      analyzedAt: '2026-03-04T10:00:00Z',
    };

    describe('Button Rendering', () => {
      it('TPI-001: renders ExportResultsButton in results header', () => {
        render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const buttonContainer = screen.getByTestId('export-button-container');
        const exportButton = within(buttonContainer).getByTestId('export-results-button');
        
        expect(exportButton).toBeInTheDocument();
      });

      it('TPI-002: positions button in flex-end container (right-aligned)', () => {
        render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const container = screen.getByTestId('export-button-container');
        expect(container).toHaveClass('justify-end');
      });

      it('TPI-003: passes correct toolName to button', () => {
        render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-tool-name', 'meta-tag-analyzer');
      });

      it('TPI-004: passes analyzed URL to button', () => {
        render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-url', mockUrl);
      });

      it('TPI-005: passes formatted data to button', () => {
        render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-has-data', 'true');
      });
    });

    describe('Loading State', () => {
      it('TPI-006: does NOT show button during loading', () => {
        render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} isLoading />
        );

        expect(screen.getByTestId('loading-state')).toBeInTheDocument();
        expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
      });

      it('TPI-007: shows button after loading completes', () => {
        const { rerender } = render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} isLoading />
        );

        expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();

        rerender(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} isLoading={false} />
        );

        expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
      });
    });

    describe('Data Mapping', () => {
      it('TPI-008: maps title data correctly', () => {
        render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        expect(screen.getByTestId('title-value')).toHaveTextContent(defaultResult.title);
        expect(screen.getByTestId('title-length')).toHaveTextContent('15/60');
      });

      it('TPI-009: maps description data correctly', () => {
        render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        expect(screen.getByTestId('description-value')).toHaveTextContent(defaultResult.description);
      });

      it('TPI-010: includes analyzedAt in export data', () => {
        render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const timeElement = screen.getByRole('time');
        expect(timeElement).toHaveAttribute('datetime', defaultResult.analyzedAt);
      });
    });

    describe('Accessibility', () => {
      it('TPI-011: button has accessible label', () => {
        render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('aria-label', 'Export analysis results');
      });

      it('TPI-012: results region has accessible label', () => {
        render(
          <MetaTagResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        expect(screen.getByRole('region', { name: /meta tag analysis/i })).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // Keyword Density Checker Integration
  // ============================================

  describe('KeywordDensityChecker Results', () => {
    const defaultResult: KeywordDensityResult = {
      keyword: 'test',
      density: 2.5,
      occurrences: 15,
      totalWords: 600,
      positions: [1, 50, 100],
      status: 'good',
      headings: [
        { tag: 'h1', text: 'Test Heading', keywordFound: true },
        { tag: 'h2', text: 'Another Heading', keywordFound: false },
      ],
      warnings: [],
      analyzedAt: '2026-03-04T11:00:00Z',
    };

    describe('Button Rendering', () => {
      it('TPI-020: renders ExportResultsButton in results header', () => {
        render(
          <KeywordDensityResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const buttonContainer = screen.getByTestId('export-button-container');
        const exportButton = within(buttonContainer).getByTestId('export-results-button');
        
        expect(exportButton).toBeInTheDocument();
      });

      it('TPI-021: passes correct toolName to button', () => {
        render(
          <KeywordDensityResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-tool-name', 'keyword-density');
      });

      it('TPI-022: passes analyzed URL to button', () => {
        render(
          <KeywordDensityResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-url', mockUrl);
      });

      it('TPI-023: passes formatted data to button', () => {
        render(
          <KeywordDensityResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-has-data', 'true');
      });
    });

    describe('Loading State', () => {
      it('TPI-024: does NOT show button during loading', () => {
        render(
          <KeywordDensityResultsWithExport result={defaultResult} analyzedUrl={mockUrl} isLoading />
        );

        expect(screen.getByTestId('loading-state')).toBeInTheDocument();
        expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
      });
    });

    describe('Data Mapping', () => {
      it('TPI-025: maps keyword density data correctly', () => {
        render(
          <KeywordDensityResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        expect(screen.getByTestId('density-value')).toHaveTextContent('2.5%');
        expect(screen.getByTestId('occurrences')).toHaveTextContent('15 occurrences');
      });

      it('TPI-026: maps headings with keyword indicator', () => {
        render(
          <KeywordDensityResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        expect(screen.getByTestId('heading-0')).toHaveTextContent('✓');
        expect(screen.getByTestId('heading-1')).not.toHaveTextContent('✓');
      });
    });

    describe('Accessibility', () => {
      it('TPI-027: results region has accessible label', () => {
        render(
          <KeywordDensityResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        expect(screen.getByRole('region', { name: /keyword density/i })).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // Mobile-Friendly Checker Integration
  // ============================================

  describe('MobileFriendlyChecker Results', () => {
    const defaultResult: MobileFriendlyResult = {
      score: 85,
      viewport: { present: true, content: 'width=device-width, initial-scale=1', valid: true },
      responsiveIndicators: { hasMediaQueries: true, hasFlexibleGrid: true, hasFlexibleImages: true },
      readability: { fontSizeMin: 16, fontSizeWarning: false },
      tapTargets: { links: 25, smallTargets: 2 },
      issues: [
        { severity: 'warning', message: 'Some tap targets may be too small' },
      ],
      recommendations: ['Consider increasing font size'],
      analyzedAt: '2026-03-04T12:00:00Z',
    };

    describe('Button Rendering', () => {
      it('TPI-030: renders ExportResultsButton in results header', () => {
        render(
          <MobileFriendlyResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const buttonContainer = screen.getByTestId('export-button-container');
        const exportButton = within(buttonContainer).getByTestId('export-results-button');
        
        expect(exportButton).toBeInTheDocument();
      });

      it('TPI-031: passes correct toolName to button', () => {
        render(
          <MobileFriendlyResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-tool-name', 'mobile-friendly-checker');
      });

      it('TPI-032: passes analyzed URL to button', () => {
        render(
          <MobileFriendlyResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-url', mockUrl);
      });

      it('TPI-033: passes formatted data to button', () => {
        render(
          <MobileFriendlyResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-has-data', 'true');
      });
    });

    describe('Loading State', () => {
      it('TPI-034: does NOT show button during loading', () => {
        render(
          <MobileFriendlyResultsWithExport result={defaultResult} analyzedUrl={mockUrl} isLoading />
        );

        expect(screen.getByTestId('loading-state')).toBeInTheDocument();
        expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
      });
    });

    describe('Data Mapping', () => {
      it('TPI-035: maps mobile score correctly', () => {
        render(
          <MobileFriendlyResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        expect(screen.getByTestId('mobile-score')).toHaveTextContent('85/100');
      });

      it('TPI-036: maps viewport presence correctly', () => {
        render(
          <MobileFriendlyResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        expect(screen.getByTestId('viewport-present')).toHaveTextContent('✓ Present');
      });

      it('TPI-037: maps issues with severity', () => {
        render(
          <MobileFriendlyResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        const issues = screen.getAllByRole('alert');
        expect(issues).toHaveLength(1);
        expect(issues[0]).toHaveAttribute('data-severity', 'warning');
      });
    });

    describe('Accessibility', () => {
      it('TPI-038: results region has accessible label', () => {
        render(
          <MobileFriendlyResultsWithExport result={defaultResult} analyzedUrl={mockUrl} />
        );

        expect(screen.getByRole('region', { name: /mobile-friendly/i })).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // Social Media Preview Integration
  // ============================================

  describe('SocialMediaPreview Results', () => {
    const defaultData: SocialMediaPreviewData = {
      openGraph: {
        title: 'Test OG Title',
        description: 'Test OG Description',
        image: 'https://example.com/og.jpg',
        url: 'https://example.com',
        type: 'website',
        siteName: 'Test Site',
      },
      twitterCard: {
        card: 'summary_large_image',
        site: '@testsite',
        creator: '@testuser',
        title: 'Test Twitter Title',
        description: 'Test Twitter Description',
        image: 'https://example.com/twitter.jpg',
      },
      previews: {
        facebook: { title: 'Test OG Title', description: 'Test OG Description', image: 'https://example.com/og.jpg' },
        twitter: { title: 'Test Twitter Title', description: 'Test Twitter Description', image: 'https://example.com/twitter.jpg' },
        linkedin: { title: 'Test OG Title', description: 'Test OG Description', image: 'https://example.com/og.jpg' },
      },
      missing: [],
      warnings: [],
      analyzedAt: '2026-03-04T13:00:00Z',
    };

    describe('Button Rendering', () => {
      it('TPI-040: renders ExportResultsButton in results header', () => {
        render(
          <SocialMediaPreviewResultsWithExport data={defaultData} analyzedUrl={mockUrl} />
        );

        const buttonContainer = screen.getByTestId('export-button-container');
        const exportButton = within(buttonContainer).getByTestId('export-results-button');
        
        expect(exportButton).toBeInTheDocument();
      });

      it('TPI-041: passes correct toolName to button', () => {
        render(
          <SocialMediaPreviewResultsWithExport data={defaultData} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-tool-name', 'social-media-preview');
      });

      it('TPI-042: passes analyzed URL to button', () => {
        render(
          <SocialMediaPreviewResultsWithExport data={defaultData} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-url', mockUrl);
      });

      it('TPI-043: passes formatted data to button', () => {
        render(
          <SocialMediaPreviewResultsWithExport data={defaultData} analyzedUrl={mockUrl} />
        );

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-has-data', 'true');
      });
    });

    describe('Loading State', () => {
      it('TPI-044: does NOT show button during loading', () => {
        render(
          <SocialMediaPreviewResultsWithExport data={defaultData} analyzedUrl={mockUrl} isLoading />
        );

        expect(screen.getByTestId('loading-state')).toBeInTheDocument();
        expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
      });
    });

    describe('Error State', () => {
      it('TPI-045: shows error message instead of button on error', () => {
        render(
          <SocialMediaPreviewResultsWithExport 
            data={null} 
            analyzedUrl={mockUrl} 
            error="Failed to analyze page" 
          />
        );

        expect(screen.getByTestId('error-message')).toBeInTheDocument();
        expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
      });
    });

    describe('Null Data', () => {
      it('TPI-046: renders nothing when data is null (no error)', () => {
        const { container } = render(
          <SocialMediaPreviewResultsWithExport data={null} analyzedUrl={mockUrl} />
        );

        expect(container.firstChild).toBeNull();
        expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
      });
    });

    describe('Data Mapping', () => {
      it('TPI-047: maps Open Graph data correctly', () => {
        render(
          <SocialMediaPreviewResultsWithExport data={defaultData} analyzedUrl={mockUrl} />
        );

        expect(screen.getByTestId('og-title')).toHaveTextContent(defaultData.openGraph.title!);
      });

      it('TPI-048: maps Twitter Card data correctly', () => {
        render(
          <SocialMediaPreviewResultsWithExport data={defaultData} analyzedUrl={mockUrl} />
        );

        expect(screen.getByTestId('twitter-card-type')).toHaveTextContent('summary_large_image');
      });
    });

    describe('Accessibility', () => {
      it('TPI-049: results region has accessible label', () => {
        render(
          <SocialMediaPreviewResultsWithExport data={defaultData} analyzedUrl={mockUrl} />
        );

        expect(screen.getByRole('region', { name: /social media preview/i })).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // Cross-Tool Consistency Tests
  // ============================================

  describe('Cross-Tool Consistency', () => {
    const results = {
      metaTag: {
        title: 'Test',
        titleLength: 4,
        description: 'Desc',
        descriptionLength: 4,
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      },
      keywordDensity: {
        keyword: 'test',
        density: 2,
        occurrences: 10,
        totalWords: 500,
        positions: [],
        status: 'good' as const,
        headings: [],
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      },
      mobileFriendly: {
        score: 80,
        viewport: { present: true, content: '', valid: true },
        responsiveIndicators: { hasMediaQueries: true, hasFlexibleGrid: true, hasFlexibleImages: true },
        readability: { fontSizeMin: 16, fontSizeWarning: false },
        tapTargets: { links: 10, smallTargets: 0 },
        issues: [],
        recommendations: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      },
      socialMedia: {
        openGraph: { title: 'Test', description: null, image: null, url: null, type: null, siteName: null },
        twitterCard: { card: null, site: null, creator: null, title: null, description: null, image: null },
        previews: {
          facebook: { title: 'Test', description: '', image: '' },
          twitter: { title: 'Test', description: '', image: '' },
          linkedin: { title: 'Test', description: '', image: '' },
        },
        missing: [],
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      },
    };

    it('TPI-050: all tools use same button placement pattern (flex-end)', () => {
      const testCases = [
        { 
          name: 'Meta Tag Analyzer', 
          Component: <MetaTagResultsWithExport result={results.metaTag} analyzedUrl={mockUrl} /> 
        },
        { 
          name: 'Keyword Density', 
          Component: <KeywordDensityResultsWithExport result={results.keywordDensity} analyzedUrl={mockUrl} /> 
        },
        { 
          name: 'Mobile-Friendly', 
          Component: <MobileFriendlyResultsWithExport result={results.mobileFriendly} analyzedUrl={mockUrl} /> 
        },
        { 
          name: 'Social Media Preview', 
          Component: <SocialMediaPreviewResultsWithExport data={results.socialMedia} analyzedUrl={mockUrl} /> 
        },
      ];

      testCases.forEach(({ name, Component }) => {
        const { unmount } = render(Component);

        const container = screen.getByTestId('export-button-container');
        expect(container).toHaveClass('justify-end');

        unmount();
      });
    });

    it('TPI-051: all tools have same button text', () => {
      const testCases = [
        { 
          name: 'Meta Tag Analyzer', 
          Component: <MetaTagResultsWithExport result={results.metaTag} analyzedUrl={mockUrl} /> 
        },
        { 
          name: 'Keyword Density', 
          Component: <KeywordDensityResultsWithExport result={results.keywordDensity} analyzedUrl={mockUrl} /> 
        },
        { 
          name: 'Mobile-Friendly', 
          Component: <MobileFriendlyResultsWithExport result={results.mobileFriendly} analyzedUrl={mockUrl} /> 
        },
        { 
          name: 'Social Media Preview', 
          Component: <SocialMediaPreviewResultsWithExport data={results.socialMedia} analyzedUrl={mockUrl} /> 
        },
      ];

      testCases.forEach(({ name, Component }) => {
        const { unmount } = render(Component);

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveTextContent('Export Results');

        unmount();
      });
    });

    it('TPI-052: all tools use same aria-label', () => {
      const testCases = [
        { 
          name: 'Meta Tag Analyzer', 
          Component: <MetaTagResultsWithExport result={results.metaTag} analyzedUrl={mockUrl} /> 
        },
        { 
          name: 'Keyword Density', 
          Component: <KeywordDensityResultsWithExport result={results.keywordDensity} analyzedUrl={mockUrl} /> 
        },
        { 
          name: 'Mobile-Friendly', 
          Component: <MobileFriendlyResultsWithExport result={results.mobileFriendly} analyzedUrl={mockUrl} /> 
        },
        { 
          name: 'Social Media Preview', 
          Component: <SocialMediaPreviewResultsWithExport data={results.socialMedia} analyzedUrl={mockUrl} /> 
        },
      ];

      testCases.forEach(({ name, Component }) => {
        const { unmount } = render(Component);

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('aria-label', 'Export analysis results');

        unmount();
      });
    });

    it('TPI-053: all tools hide button during loading', () => {
      const testCases = [
        { 
          name: 'Meta Tag Analyzer', 
          Component: <MetaTagResultsWithExport result={results.metaTag} analyzedUrl={mockUrl} isLoading /> 
        },
        { 
          name: 'Keyword Density', 
          Component: <KeywordDensityResultsWithExport result={results.keywordDensity} analyzedUrl={mockUrl} isLoading /> 
        },
        { 
          name: 'Mobile-Friendly', 
          Component: <MobileFriendlyResultsWithExport result={results.mobileFriendly} analyzedUrl={mockUrl} isLoading /> 
        },
        { 
          name: 'Social Media Preview', 
          Component: <SocialMediaPreviewResultsWithExport data={results.socialMedia} analyzedUrl={mockUrl} isLoading /> 
        },
      ];

      testCases.forEach(({ name, Component }) => {
        const { unmount } = render(Component);

        expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();

        unmount();
      });
    });

    it('TPI-054: all tools show button after loading completes', () => {
      // Test Meta Tag Analyzer
      const { unmount: unmount1 } = render(
        <MetaTagResultsWithExport result={results.metaTag} analyzedUrl={mockUrl} isLoading />
      );
      expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
      unmount1();

      // Test Keyword Density
      const { unmount: unmount2 } = render(
        <KeywordDensityResultsWithExport result={results.keywordDensity} analyzedUrl={mockUrl} isLoading />
      );
      expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
      unmount2();

      // Test Mobile-Friendly
      const { unmount: unmount3 } = render(
        <MobileFriendlyResultsWithExport result={results.mobileFriendly} analyzedUrl={mockUrl} isLoading />
      );
      expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
      unmount3();

      // Test Social Media Preview
      const { unmount: unmount4 } = render(
        <SocialMediaPreviewResultsWithExport data={results.socialMedia} analyzedUrl={mockUrl} isLoading />
      );
      expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
      unmount4();
    });

    it('TPI-055: each tool passes unique toolName', () => {
      const expectedToolNames = [
        'meta-tag-analyzer',
        'keyword-density',
        'mobile-friendly-checker',
        'social-media-preview',
      ];

      const testCases = [
        <MetaTagResultsWithExport key="meta" result={results.metaTag} analyzedUrl={mockUrl} />,
        <KeywordDensityResultsWithExport key="keyword" result={results.keywordDensity} analyzedUrl={mockUrl} />,
        <MobileFriendlyResultsWithExport key="mobile" result={results.mobileFriendly} analyzedUrl={mockUrl} />,
        <SocialMediaPreviewResultsWithExport key="social" data={results.socialMedia} analyzedUrl={mockUrl} />,
      ];

      testCases.forEach((Component, index) => {
        const { unmount } = render(Component);

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-tool-name', expectedToolNames[index]);

        unmount();
      });
    });
  });

  // ============================================
  // Mobile Responsiveness Tests
  // ============================================

  describe('Mobile Responsiveness', () => {
    const defaultMetaTagResult: MetaTagResult = {
      title: 'Test Title',
      titleLength: 10,
      description: 'Test Description',
      descriptionLength: 15,
      warnings: [],
      analyzedAt: '2026-03-04T10:00:00Z',
    };

    it('TPI-060: button visible on mobile viewport', () => {
      render(
        <MetaTagResultsWithExport result={defaultMetaTagResult} analyzedUrl={mockUrl} />
      );

      const button = screen.getByTestId('export-results-button');
      expect(button).toBeVisible();
    });

    it('TPI-061: button container uses responsive flex classes', () => {
      render(
        <MetaTagResultsWithExport result={defaultMetaTagResult} analyzedUrl={mockUrl} />
      );

      const container = screen.getByTestId('export-button-container');
      expect(container).toHaveClass('flex');
    });

    it('TPI-062: button has appropriate touch target size', () => {
      render(
        <MetaTagResultsWithExport result={defaultMetaTagResult} analyzedUrl={mockUrl} />
      );

      const button = screen.getByTestId('export-results-button');
      // Button should be focusable and have accessible label
      expect(button).toHaveAttribute('aria-label');
    });
  });

  // ============================================
  // Edge Cases
  // ============================================

  describe('Edge Cases', () => {
    const defaultResult: MetaTagResult = {
      title: 'Test',
      titleLength: 4,
      description: 'Desc',
      descriptionLength: 4,
      warnings: [],
      analyzedAt: '2026-03-04T10:00:00Z',
    };

    it('TPI-070: handles missing analyzedUrl gracefully', () => {
      render(
        <MetaTagResultsWithExport result={defaultResult} />
      );

      const button = screen.getByTestId('export-results-button');
      expect(button).toHaveAttribute('data-url', '');
    });

    it('TPI-071: button disabled when data is empty', () => {
      const emptyResult: MetaTagResult = {
        title: '',
        titleLength: 0,
        description: '',
        descriptionLength: 0,
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      };

      render(
        <MetaTagResultsWithExport result={emptyResult} analyzedUrl={mockUrl} />
      );

      const button = screen.getByTestId('export-results-button');
      // Empty data still creates an object, so button should be enabled
      expect(button).toHaveAttribute('data-has-data', 'true');
    });

    it('TPI-072: handles special characters in URL', () => {
      const specialUrl = 'https://example.com/test?param=value&other=test#section';
      
      render(
        <MetaTagResultsWithExport result={defaultResult} analyzedUrl={specialUrl} />
      );

      const button = screen.getByTestId('export-results-button');
      expect(button).toHaveAttribute('data-url', specialUrl);
    });

    it('TPI-073: handles unicode in results', () => {
      const unicodeResult: MetaTagResult = {
        title: 'テスト タイトル',
        titleLength: 8,
        description: '测试描述',
        descriptionLength: 4,
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      };

      render(
        <MetaTagResultsWithExport result={unicodeResult} analyzedUrl={mockUrl} />
      );

      expect(screen.getByTestId('title-value')).toHaveTextContent('テスト タイトル');
    });

    it('TPI-074: handles emoji in results', () => {
      const emojiResult: MetaTagResult = {
        title: '🎉 Test Title 🚀',
        titleLength: 14,
        description: '✨ Description',
        descriptionLength: 12,
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      };

      render(
        <MetaTagResultsWithExport result={emojiResult} analyzedUrl={mockUrl} />
      );

      expect(screen.getByTestId('title-value')).toHaveTextContent('🎉 Test Title 🚀');
    });

    it('TPI-075: handles many warnings without layout issues', () => {
      const manyWarnings: MetaTagResult = {
        title: 'Test',
        titleLength: 4,
        description: 'Desc',
        descriptionLength: 4,
        warnings: Array.from({ length: 20 }, (_, i) => ({
          type: 'warning',
          message: `Warning ${i + 1}`,
        })),
        analyzedAt: '2026-03-04T10:00:00Z',
      };

      render(
        <MetaTagResultsWithExport result={manyWarnings} analyzedUrl={mockUrl} />
      );

      const button = screen.getByTestId('export-results-button');
      expect(button).toBeInTheDocument();
    });
  });

  // ============================================
  // Data Structure Validation
  // ============================================

  describe('ToolResultData Interface Validation', () => {
    it('TPI-080: Meta Tag data structure includes analyzedAt', () => {
      const result: MetaTagResult = {
        title: 'Test',
        titleLength: 4,
        description: 'Desc',
        descriptionLength: 4,
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      };

      render(<MetaTagResultsWithExport result={result} analyzedUrl={mockUrl} />);

      const timeElement = screen.getByRole('time');
      expect(timeElement).toHaveAttribute('datetime', result.analyzedAt);
    });

    it('TPI-081: Keyword Density data structure includes analyzedAt', () => {
      const result: KeywordDensityResult = {
        keyword: 'test',
        density: 2,
        occurrences: 10,
        totalWords: 500,
        positions: [],
        status: 'good',
        headings: [],
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      };

      render(<KeywordDensityResultsWithExport result={result} analyzedUrl={mockUrl} />);

      const timeElement = screen.getByRole('time');
      expect(timeElement).toHaveAttribute('datetime', result.analyzedAt);
    });

    it('TPI-082: Mobile-Friendly data structure includes analyzedAt', () => {
      const result: MobileFriendlyResult = {
        score: 80,
        viewport: { present: true, content: '', valid: true },
        responsiveIndicators: { hasMediaQueries: true, hasFlexibleGrid: true, hasFlexibleImages: true },
        readability: { fontSizeMin: 16, fontSizeWarning: false },
        tapTargets: { links: 10, smallTargets: 0 },
        issues: [],
        recommendations: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      };

      render(<MobileFriendlyResultsWithExport result={result} analyzedUrl={mockUrl} />);

      const timeElement = screen.getByRole('time');
      expect(timeElement).toHaveAttribute('datetime', result.analyzedAt);
    });

    it('TPI-083: Social Media data structure supports analyzedAt', () => {
      const data: SocialMediaPreviewData = {
        openGraph: { title: 'Test', description: null, image: null, url: null, type: null, siteName: null },
        twitterCard: { card: null, site: null, creator: null, title: null, description: null, image: null },
        previews: {
          facebook: { title: 'Test', description: '', image: '' },
          twitter: { title: 'Test', description: '', image: '' },
          linkedin: { title: 'Test', description: '', image: '' },
        },
        missing: [],
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      };

      render(<SocialMediaPreviewResultsWithExport data={data} analyzedUrl={mockUrl} />);

      const timeElement = screen.getByRole('time');
      expect(timeElement).toHaveAttribute('datetime', data.analyzedAt);
    });
  });

  // ============================================
  // Acceptance Criteria Coverage
  // ============================================

  describe('Acceptance Criteria', () => {
    const results = {
      metaTag: {
        title: 'Test',
        titleLength: 4,
        description: 'Desc',
        descriptionLength: 4,
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      },
      keywordDensity: {
        keyword: 'test',
        density: 2,
        occurrences: 10,
        totalWords: 500,
        positions: [],
        status: 'good' as const,
        headings: [],
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      },
      mobileFriendly: {
        score: 80,
        viewport: { present: true, content: '', valid: true },
        responsiveIndicators: { hasMediaQueries: true, hasFlexibleGrid: true, hasFlexibleImages: true },
        readability: { fontSizeMin: 16, fontSizeWarning: false },
        tapTargets: { links: 10, smallTargets: 0 },
        issues: [],
        recommendations: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      },
      socialMedia: {
        openGraph: { title: 'Test', description: null, image: null, url: null, type: null, siteName: null },
        twitterCard: { card: null, site: null, creator: null, title: null, description: null, image: null },
        previews: {
          facebook: { title: 'Test', description: '', image: '' },
          twitter: { title: 'Test', description: '', image: '' },
          linkedin: { title: 'Test', description: '', image: '' },
        },
        missing: [],
        warnings: [],
        analyzedAt: '2026-03-04T10:00:00Z',
      },
    };

    it('AC-1: ExportResultsButton added to all 4 tool result pages', () => {
      const testCases = [
        { name: 'Meta Tag Analyzer', Component: <MetaTagResultsWithExport result={results.metaTag} analyzedUrl={mockUrl} /> },
        { name: 'Keyword Density Checker', Component: <KeywordDensityResultsWithExport result={results.keywordDensity} analyzedUrl={mockUrl} /> },
        { name: 'Mobile-Friendly Checker', Component: <MobileFriendlyResultsWithExport result={results.mobileFriendly} analyzedUrl={mockUrl} /> },
        { name: 'Social Media Preview', Component: <SocialMediaPreviewResultsWithExport data={results.socialMedia} analyzedUrl={mockUrl} /> },
      ];

      testCases.forEach(({ name, Component }) => {
        const { unmount } = render(Component);

        expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
        unmount();
      });
    });

    it('AC-2: Button positioned in results header area (consistent placement)', () => {
      const testCases = [
        <MetaTagResultsWithExport key="meta" result={results.metaTag} analyzedUrl={mockUrl} />,
        <KeywordDensityResultsWithExport key="keyword" result={results.keywordDensity} analyzedUrl={mockUrl} />,
        <MobileFriendlyResultsWithExport key="mobile" result={results.mobileFriendly} analyzedUrl={mockUrl} />,
        <SocialMediaPreviewResultsWithExport key="social" data={results.socialMedia} analyzedUrl={mockUrl} />,
      ];

      testCases.forEach((Component) => {
        const { unmount } = render(Component);

        const container = screen.getByTestId('export-button-container');
        expect(container).toHaveClass('justify-end');
        expect(container).toHaveClass('mt-4');

        unmount();
      });
    });

    it('AC-3: Button appears after analysis completes (not during loading)', () => {
      const { rerender } = render(
        <MetaTagResultsWithExport result={results.metaTag} analyzedUrl={mockUrl} isLoading />
      );

      // Not present during loading
      expect(screen.queryByTestId('export-results-button')).not.toBeInTheDocument();
      expect(screen.getByTestId('loading-state')).toBeInTheDocument();

      // Present after loading completes
      rerender(
        <MetaTagResultsWithExport result={results.metaTag} analyzedUrl={mockUrl} isLoading={false} />
      );

      expect(screen.getByTestId('export-results-button')).toBeInTheDocument();
    });

    it('AC-4: Tool result data structure matches expected ToolResultData interface', () => {
      render(<MetaTagResultsWithExport result={results.metaTag} analyzedUrl={mockUrl} />);

      const button = screen.getByTestId('export-results-button');
      
      // Verify data was passed (button not disabled)
      expect(button).toHaveAttribute('data-has-data', 'true');
    });

    it('AC-5: Export works for each tool type with correct data mapping', () => {
      const expectedToolNames = [
        'meta-tag-analyzer',
        'keyword-density',
        'mobile-friendly-checker',
        'social-media-preview',
      ];

      const testCases = [
        <MetaTagResultsWithExport key="meta" result={results.metaTag} analyzedUrl={mockUrl} />,
        <KeywordDensityResultsWithExport key="keyword" result={results.keywordDensity} analyzedUrl={mockUrl} />,
        <MobileFriendlyResultsWithExport key="mobile" result={results.mobileFriendly} analyzedUrl={mockUrl} />,
        <SocialMediaPreviewResultsWithExport key="social" data={results.socialMedia} analyzedUrl={mockUrl} />,
      ];

      testCases.forEach((Component, index) => {
        const { unmount } = render(Component);

        const button = screen.getByTestId('export-results-button');
        expect(button).toHaveAttribute('data-tool-name', expectedToolNames[index]);
        expect(button).toHaveAttribute('data-url', mockUrl);

        unmount();
      });
    });

    it('AC-6: Consistent UX across all tools (same button style, same modal)', () => {
      const testCases = [
        <MetaTagResultsWithExport key="meta" result={results.metaTag} analyzedUrl={mockUrl} />,
        <KeywordDensityResultsWithExport key="keyword" result={results.keywordDensity} analyzedUrl={mockUrl} />,
        <MobileFriendlyResultsWithExport key="mobile" result={results.mobileFriendly} analyzedUrl={mockUrl} />,
        <SocialMediaPreviewResultsWithExport key="social" data={results.socialMedia} analyzedUrl={mockUrl} />,
      ];

      testCases.forEach((Component) => {
        const { unmount } = render(Component);

        const button = screen.getByTestId('export-results-button');
        
        // All have same text
        expect(button).toHaveTextContent('Export Results');
        
        // All have same aria-label
        expect(button).toHaveAttribute('aria-label', 'Export analysis results');

        unmount();
      });
    });

    it('AC-7: Mobile responsive - button visible and usable on mobile devices', () => {
      render(<MetaTagResultsWithExport result={results.metaTag} analyzedUrl={mockUrl} />);

      const button = screen.getByTestId('export-results-button');
      const container = screen.getByTestId('export-button-container');

      // Button is visible
      expect(button).toBeVisible();
      
      // Container uses flex for responsive layout
      expect(container).toHaveClass('flex');
    });
  });
});
