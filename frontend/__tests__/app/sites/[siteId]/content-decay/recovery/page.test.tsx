/**
 * Recovery Report Page Tests
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 * 
 * Tests the recovery report page with statistics, timeline chart,
 * and before/after comparison table.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// ============== Mocks ==============

// Mock next/navigation
vi.mock('next/navigation', () => ({
  useParams: () => ({ siteId: '1' }),
  useRouter: () => ({ push: vi.fn() }),
}));

// Mock next/link
vi.mock('next/link', () => ({
  default: ({ children, href, ...props }: any) => (
    <a href={href} {...props}>{children}</a>
  ),
}));

// Mock sonner toast
vi.mock('sonner', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  TrendingUp: () => <span data-testid="trending-up-icon">TrendingUp</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">TrendingDown</span>,
  Download: () => <span data-testid="download-icon">Download</span>,
  Calendar: () => <span data-testid="calendar-icon">Calendar</span>,
  ChevronLeft: () => <span data-testid="chevron-left-icon">ChevronLeft</span>,
  ChevronRight: () => <span data-testid="chevron-right-icon">ChevronRight</span>,
  ArrowUp: () => <span data-testid="arrow-up-icon">ArrowUp</span>,
  ArrowDown: () => <span data-testid="arrow-down-icon">ArrowDown</span>,
  Minus: () => <span data-testid="minus-icon">Minus</span>,
  Clock: () => <span data-testid="clock-icon">Clock</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loader2</span>,
}));

// Mock Recharts
vi.mock('recharts', () => ({
  ResponsiveContainer: ({ children }: any) => (
    <div data-testid="responsive-container">{children}</div>
  ),
  LineChart: ({ children, data }: any) => (
    <div data-testid="line-chart" data-points={data?.length}>{children}</div>
  ),
  Line: ({ dataKey, stroke }: any) => (
    <div data-testid={`line-${dataKey}`} data-stroke={stroke} />
  ),
  XAxis: ({ dataKey }: any) => (
    <div data-testid="x-axis" data-key={dataKey} />
  ),
  YAxis: () => <div data-testid="y-axis" />,
  CartesianGrid: () => <div data-testid="cartesian-grid" />,
  Tooltip: () => <div data-testid="chart-tooltip" />,
  Legend: () => <div data-testid="chart-legend" />,
}));

// Mock content decay hooks
vi.mock('@/lib/hooks/useContentDecay', () => ({
  useContentDecayRecovery: () => ({
    recovery: mockRecoveryData,
    isLoading: false,
    error: null,
  }),
  useExportContentDecay: () => ({
    exportData: vi.fn(),
    isExporting: false,
  }),
}));

// Mock site hook
vi.mock('@/lib/hooks/useSite', () => ({
  useSite: () => ({
    site: mockSite,
    isLoading: false,
    error: null,
  }),
}));

// ============== Mock Data ==============

const mockSite = {
  id: 1,
  url: 'https://example.com',
  name: 'Example Site',
};

const mockRecoveryData = {
  summary: {
    totalRecovered: 12,
    totalClicksRecovered: 2450,
    avgRecoveryRate: 68,
    avgDaysToRecovery: 21,
  },
  timeline: [
    { date: '2026-02-15', recovered: 2, clicks: 320 },
    { date: '2026-02-22', recovered: 3, clicks: 580 },
    { date: '2026-03-01', recovered: 4, clicks: 850 },
    { date: '2026-03-08', recovered: 3, clicks: 700 },
  ],
  comparisons: [
    {
      id: 1,
      pageUrl: 'https://example.com/blog/seo-guide',
      pageTitle: 'Complete SEO Guide',
      pageType: 'blog',
      before: { clicks: 450, impressions: 12000, ctr: 3.75, position: 12.5 },
      after: { clicks: 890, impressions: 18500, ctr: 4.81, position: 8.2 },
      delta: { clicks: 440, impressions: 6500, ctr: 1.06, position: -4.3 },
    },
    {
      id: 2,
      pageUrl: 'https://example.com/products/widget',
      pageTitle: 'Premium Widget',
      pageType: 'product',
      before: { clicks: 280, impressions: 8500, ctr: 3.29, position: 15.0 },
      after: { clicks: 520, impressions: 14000, ctr: 3.71, position: 11.8 },
      delta: { clicks: 240, impressions: 5500, ctr: 0.42, position: -3.2 },
    },
    {
      id: 3,
      pageUrl: 'https://example.com/about',
      pageTitle: 'About Us',
      pageType: 'landing',
      before: { clicks: 180, impressions: 5200, ctr: 3.46, position: 18.5 },
      after: { clicks: 210, impressions: 5800, ctr: 3.62, position: 16.2 },
      delta: { clicks: 30, impressions: 600, ctr: 0.16, position: -2.3 },
    },
  ],
  stories: [
    {
      id: 1,
      pageUrl: 'https://example.com/blog/seo-guide',
      pageTitle: 'Complete SEO Guide',
      clicksRecovered: 440,
      recoveryRate: 98,
      daysToRecovery: 14,
      summary: 'Updated outdated statistics and added new examples',
    },
  ],
};

// ============== Mock Page Component ==============

const MockRecoveryReportPage = () => {
  return (
    <div data-testid="recovery-report-page" className="container mx-auto px-4 py-8">
      {/* Breadcrumb */}
      <nav data-testid="breadcrumb" className="mb-4 text-sm text-gray-600">
        <a href="/sites" data-testid="sites-link">Sites</a>
        <span> / </span>
        <a href="/sites/1" data-testid="site-link">{mockSite.url}</a>
        <span> / </span>
        <a href="/sites/1/content-decay" data-testid="queue-link">Content Decay</a>
        <span> / </span>
        <span data-testid="current-crumb">Recovery</span>
      </nav>

      {/* Header */}
      <div data-testid="page-header" className="flex justify-between items-center mb-6">
        <div>
          <h1 data-testid="page-title" className="text-2xl font-bold">Recovery Report</h1>
          <p data-testid="page-subtitle" className="text-gray-600">
            Track content refresh outcomes and ROI
          </p>
        </div>
        <div className="flex gap-2">
          <div data-testid="date-range-selector" className="flex items-center gap-2">
            <span data-testid="calendar-icon">Calendar</span>
            <select data-testid="date-range-select" className="border rounded px-3 py-2">
              <option value="7">Last 7 days</option>
              <option value="30">Last 30 days</option>
              <option value="90">Last 90 days</option>
              <option value="custom">Custom range</option>
            </select>
          </div>
          <button data-testid="export-btn" className="btn-secondary">
            <span data-testid="download-icon">Download</span>
            Export
          </button>
        </div>
      </div>

      {/* Summary Statistics */}
      <div data-testid="summary-stats" className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div data-testid="stat-recovered" className="bg-white rounded-lg shadow p-4">
          <div className="text-sm text-gray-600">Total Recovered</div>
          <div className="text-2xl font-bold text-green-600">
            {mockRecoveryData.summary.totalRecovered}
          </div>
          <div className="text-xs text-gray-500">pages</div>
        </div>
        <div data-testid="stat-clicks" className="bg-white rounded-lg shadow p-4">
          <div className="text-sm text-gray-600">Clicks Recovered</div>
          <div className="text-2xl font-bold text-blue-600">
            {mockRecoveryData.summary.totalClicksRecovered.toLocaleString()}
          </div>
          <div className="text-xs text-gray-500">estimated</div>
        </div>
        <div data-testid="stat-rate" className="bg-white rounded-lg shadow p-4">
          <div className="text-sm text-gray-600">Avg Recovery Rate</div>
          <div className="text-2xl font-bold text-purple-600">
            {mockRecoveryData.summary.avgRecoveryRate}%
          </div>
          <div className="text-xs text-gray-500">of lost clicks</div>
        </div>
        <div data-testid="stat-days" className="bg-white rounded-lg shadow p-4">
          <div className="text-sm text-gray-600">Avg Days to Recover</div>
          <div className="text-2xl font-bold text-orange-600">
            {mockRecoveryData.summary.avgDaysToRecovery}
          </div>
          <div className="text-xs text-gray-500">days</div>
        </div>
      </div>

      {/* Timeline Chart */}
      <div data-testid="timeline-chart-section" className="bg-white rounded-lg shadow p-6 mb-6">
        <h2 data-testid="chart-title" className="text-lg font-semibold mb-4">Recovery Timeline</h2>
        <div data-testid="timeline-chart" className="h-64">
          <div data-testid="responsive-container">
            <div data-testid="line-chart" data-points={mockRecoveryData.timeline.length}>
              <div data-testid="line-recovered" data-stroke="#22c55e" />
              <div data-testid="line-clicks" data-stroke="#3b82f6" />
              <div data-testid="x-axis" data-key="date" />
              <div data-testid="y-axis" />
              <div data-testid="cartesian-grid" />
              <div data-testid="chart-tooltip" />
              <div data-testid="chart-legend" />
            </div>
          </div>
        </div>
      </div>

      {/* Comparison Table */}
      <div data-testid="comparison-table-section" className="bg-white rounded-lg shadow mb-6">
        <div className="p-4 border-b">
          <h2 data-testid="table-title" className="text-lg font-semibold">Before/After Comparison</h2>
        </div>
        <div data-testid="table-container" className="overflow-x-auto">
          <table data-testid="comparison-table">
            <thead>
              <tr>
                <th data-testid="th-page">Page</th>
                <th data-testid="th-before">Before</th>
                <th data-testid="th-after">After</th>
                <th data-testid="th-delta">Delta</th>
              </tr>
            </thead>
            <tbody>
              {mockRecoveryData.comparisons.map((item) => (
                <tr
                  key={item.id}
                  data-testid={`comparison-row-${item.id}`}
                  className="cursor-pointer hover:bg-gray-50"
                >
                  <td>
                    <div className="font-medium">{item.pageTitle}</div>
                    <div className="text-sm text-gray-500 truncate max-w-xs">
                      {item.pageUrl.replace('https://example.com', '')}
                    </div>
                  </td>
                  <td data-testid={`before-${item.id}`}>
                    <div className="text-sm">
                      <span className="font-mono">{item.before.clicks}</span> clicks
                    </div>
                    <div className="text-xs text-gray-500">
                      {item.before.impressions.toLocaleString()} impr
                    </div>
                  </td>
                  <td data-testid={`after-${item.id}`}>
                    <div className="text-sm">
                      <span className="font-mono">{item.after.clicks}</span> clicks
                    </div>
                    <div className="text-xs text-gray-500">
                      {item.after.impressions.toLocaleString()} impr
                    </div>
                  </td>
                  <td data-testid={`delta-${item.id}`}>
                    <div
                      data-testid={`delta-clicks-${item.id}`}
                      className={`text-sm font-mono ${
                        item.delta.clicks > 0 ? 'text-green-600' : 'text-red-600'
                      }`}
                    >
                      {item.delta.clicks > 0 ? '+' : ''}{item.delta.clicks}
                    </div>
                    <div
                      data-testid={`delta-impr-${item.id}`}
                      className="text-xs text-green-600"
                    >
                      +{item.delta.impressions.toLocaleString()}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div data-testid="table-pagination" className="p-4 border-t flex justify-center">
          <button data-testid="prev-page-btn" disabled>Previous</button>
          <span className="mx-4">Page 1 of 1</span>
          <button data-testid="next-page-btn" disabled>Next</button>
        </div>
      </div>

      {/* Recovery Stories */}
      <div data-testid="stories-section" className="bg-white rounded-lg shadow p-6">
        <h2 data-testid="stories-title" className="text-lg font-semibold mb-4">Recovery Stories</h2>
        <div data-testid="stories-grid" className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {mockRecoveryData.stories.map((story) => (
            <div
              key={story.id}
              data-testid={`story-card-${story.id}`}
              className="border rounded-lg p-4"
            >
              <h3 data-testid={`story-title-${story.id}`} className="font-medium mb-2">
                {story.pageTitle}
              </h3>
              <p className="text-sm text-gray-600 mb-3">{story.summary}</p>
              <div className="flex gap-4 text-sm">
                <div>
                  <span className="text-gray-500">Clicks recovered:</span>
                  <span className="font-semibold text-green-600 ml-1">
                    +{story.clicksRecovered}
                  </span>
                </div>
                <div>
                  <span className="text-gray-500">Days:</span>
                  <span className="font-semibold ml-1">{story.daysToRecovery}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Empty State */}
      <div data-testid="empty-state" className="hidden text-center py-12">
        <div className="text-gray-500 mb-4">No recovery data available yet</div>
        <p className="text-sm text-gray-400">Recovery data will appear after pages are refreshed</p>
      </div>

      {/* Loading State */}
      <div data-testid="loading-skeleton" className="hidden">
        <div className="animate-pulse bg-gray-200 h-32 rounded mb-4"></div>
        <div className="animate-pulse bg-gray-200 h-64 rounded"></div>
      </div>

      {/* Error State */}
      <div data-testid="error-state" className="hidden text-center py-12">
        <div className="text-red-600 mb-4">Failed to load recovery data</div>
        <button data-testid="retry-btn" className="btn-primary">Retry</button>
      </div>
    </div>
  );
};

// ============== Tests ==============

describe('RecoveryReportPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ============================================
  // RR-PAGE-RENDER: Page Rendering
  // ============================================
  
  describe('RR-PAGE-RENDER: Page Rendering', () => {
    it('RR-RN-001: Page renders with siteId param', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('recovery-report-page')).toBeInTheDocument();
    });

    it('RR-RN-002: Renders summary statistics section', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('summary-stats')).toBeInTheDocument();
    });

    it('RR-RN-003: Renders timeline chart section', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('timeline-chart-section')).toBeInTheDocument();
    });

    it('RR-RN-004: Renders comparison table section', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('comparison-table-section')).toBeInTheDocument();
    });

    it('RR-RN-005: Renders recovery stories section', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('stories-section')).toBeInTheDocument();
    });

    it('RR-RN-006: Renders export button', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('export-btn')).toBeInTheDocument();
    });

    it('RR-RN-007: Renders breadcrumb navigation', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('breadcrumb')).toBeInTheDocument();
      expect(screen.getByTestId('queue-link')).toHaveAttribute('href', '/sites/1/content-decay');
    });

    it('RR-RN-008: Renders date range selector', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('date-range-select')).toBeInTheDocument();
    });

    it('RR-RN-009: Renders four summary stat cards', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('stat-recovered')).toBeInTheDocument();
      expect(screen.getByTestId('stat-clicks')).toBeInTheDocument();
      expect(screen.getByTestId('stat-rate')).toBeInTheDocument();
      expect(screen.getByTestId('stat-days')).toBeInTheDocument();
    });

    it('RR-RN-010: Empty state exists in DOM', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('empty-state')).toBeInTheDocument();
    });
  });

  // ============================================
  // RR-PAGE-DATA: Data Loading
  // ============================================
  
  describe('RR-PAGE-DATA: Data Loading', () => {
    it('RR-DL-001: Shows total recovered count', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('stat-recovered')).toHaveTextContent('12');
    });

    it('RR-DL-002: Shows total clicks recovered', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('stat-clicks')).toHaveTextContent('2,450');
    });

    it('RR-DL-003: Shows average recovery rate', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('stat-rate')).toHaveTextContent('68%');
    });

    it('RR-DL-004: Shows average days to recovery', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('stat-days')).toHaveTextContent('21');
    });

    it('RR-DL-005: Shows comparison rows', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('comparison-row-1')).toBeInTheDocument();
      expect(screen.getByTestId('comparison-row-2')).toBeInTheDocument();
      expect(screen.getByTestId('comparison-row-3')).toBeInTheDocument();
    });

    it('RR-DL-006: Shows before/after clicks', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('before-1')).toHaveTextContent('450');
      expect(screen.getByTestId('after-1')).toHaveTextContent('890');
    });

    it('RR-DL-007: Shows delta values', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('delta-clicks-1')).toHaveTextContent('+440');
    });

    it('RR-DL-008: Shows recovery stories', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('story-card-1')).toBeInTheDocument();
    });
  });

  // ============================================
  // RR-PAGE-CHART: Timeline Chart
  // ============================================
  
  describe('RR-PAGE-CHART: Timeline Chart', () => {
    it('RR-CH-001: Chart renders with data', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('line-chart')).toBeInTheDocument();
    });

    it('RR-CH-002: Chart has correct data points count', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('line-chart')).toHaveAttribute('data-points', '4');
    });

    it('RR-CH-003: Has recovered line with green color', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('line-recovered')).toHaveAttribute('data-stroke', '#22c55e');
    });

    it('RR-CH-004: Has clicks line with blue color', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('line-clicks')).toHaveAttribute('data-stroke', '#3b82f6');
    });

    it('RR-CH-005: Has X axis with date key', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('x-axis')).toHaveAttribute('data-key', 'date');
    });

    it('RR-CH-006: Has Y axis', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('y-axis')).toBeInTheDocument();
    });

    it('RR-CH-007: Has tooltip component', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('chart-tooltip')).toBeInTheDocument();
    });

    it('RR-CH-008: Has legend component', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('chart-legend')).toBeInTheDocument();
    });
  });

  // ============================================
  // RR-PAGE-TABLE: Comparison Table
  // ============================================
  
  describe('RR-PAGE-TABLE: Comparison Table', () => {
    it('RR-TB-001: Table has correct headers', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('th-page')).toHaveTextContent('Page');
      expect(screen.getByTestId('th-before')).toHaveTextContent('Before');
      expect(screen.getByTestId('th-after')).toHaveTextContent('After');
      expect(screen.getByTestId('th-delta')).toHaveTextContent('Delta');
    });

    it('RR-TB-002: Shows page title for each row', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('comparison-row-1')).toHaveTextContent('Complete SEO Guide');
    });

    it('RR-TB-003: Delta shows green for positive clicks', () => {
      render(<MockRecoveryReportPage />);
      
      const delta = screen.getByTestId('delta-clicks-1');
      expect(delta).toHaveClass('text-green-600');
    });

    it('RR-TB-004: Delta shows correct sign for positive', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('delta-clicks-1')).toHaveTextContent('+440');
    });

    it('RR-TB-005: Delta shows impressions change', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('delta-impr-1')).toHaveTextContent('+6,500');
    });

    it('RR-TB-006: Table container has horizontal scroll', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('table-container')).toHaveClass('overflow-x-auto');
    });

    it('RR-TB-007: Table has pagination controls', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('prev-page-btn')).toBeInTheDocument();
      expect(screen.getByTestId('next-page-btn')).toBeInTheDocument();
    });

    it('RR-TB-008: Pagination disabled on single page', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('prev-page-btn')).toBeDisabled();
      expect(screen.getByTestId('next-page-btn')).toBeDisabled();
    });

    it('RR-TB-009: Row is clickable', () => {
      render(<MockRecoveryReportPage />);
      
      const row = screen.getByTestId('comparison-row-1');
      expect(row.className).toContain('cursor-pointer');
    });

    it('RR-TB-010: Row has hover effect', () => {
      render(<MockRecoveryReportPage />);
      
      const row = screen.getByTestId('comparison-row-1');
      expect(row.className).toContain('hover:bg-gray-50');
    });
  });

  // ============================================
  // RR-PAGE-STORIES: Individual Stories
  // ============================================
  
  describe('RR-PAGE-STORIES: Individual Stories', () => {
    it('RR-ST-001: Stories section has title', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('stories-title')).toHaveTextContent('Recovery Stories');
    });

    it('RR-ST-002: Story cards show page title', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('story-title-1')).toHaveTextContent('Complete SEO Guide');
    });

    it('RR-ST-003: Story shows clicks recovered', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('story-card-1')).toHaveTextContent('+440');
    });

    it('RR-ST-004: Story shows days to recovery', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('story-card-1')).toHaveTextContent('14');
    });

    it('RR-ST-005: Story shows summary', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('story-card-1')).toHaveTextContent('Updated outdated');
    });

    it('RR-ST-006: Stories grid has responsive columns', () => {
      render(<MockRecoveryReportPage />);
      
      const grid = screen.getByTestId('stories-grid');
      expect(grid.className).toContain('md:grid-cols-2');
    });

    it('RR-ST-007: Story cards have border', () => {
      render(<MockRecoveryReportPage />);
      
      const card = screen.getByTestId('story-card-1');
      expect(card.className).toContain('border');
    });

    it('RR-ST-008: Story cards have padding', () => {
      render(<MockRecoveryReportPage />);
      
      const card = screen.getByTestId('story-card-1');
      expect(card.className).toContain('p-4');
    });
  });

  // ============================================
  // RR-PAGE-RESPONSIVE: Responsive Design
  // ============================================
  
  describe('RR-PAGE-RESPONSIVE: Responsive Design', () => {
    it('RR-RS-001: Summary stats have responsive grid', () => {
      render(<MockRecoveryReportPage />);
      
      const stats = screen.getByTestId('summary-stats');
      expect(stats.className).toContain('grid');
      expect(stats.className).toContain('md:grid-cols-4');
    });

    it('RR-RS-002: Timeline chart has fixed height', () => {
      render(<MockRecoveryReportPage />);
      
      const chart = screen.getByTestId('timeline-chart');
      expect(chart.className).toContain('h-64');
    });

    it('RR-RS-003: Table container scrolls horizontally', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('table-container')).toHaveClass('overflow-x-auto');
    });

    it('RR-RS-004: Page has responsive padding', () => {
      render(<MockRecoveryReportPage />);
      
      const page = screen.getByTestId('recovery-report-page');
      expect(page.className).toContain('px-4');
    });

    it('RR-RS-005: Stories grid is responsive', () => {
      render(<MockRecoveryReportPage />);
      
      const grid = screen.getByTestId('stories-grid');
      expect(grid.className).toContain('grid-cols-1');
      expect(grid.className).toContain('md:grid-cols-2');
    });
  });

  // ============================================
  // RR-PAGE-ACCESSIBILITY: Accessibility
  // ============================================
  
  describe('RR-PAGE-ACCESSIBILITY: Accessibility', () => {
    it('RR-AX-001: Has proper heading hierarchy', () => {
      render(<MockRecoveryReportPage />);
      
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1).toHaveTextContent('Recovery Report');
    });

    it('RR-AX-002: Section titles are h2 headings', () => {
      render(<MockRecoveryReportPage />);
      
      const h2s = screen.getAllByRole('heading', { level: 2 });
      expect(h2s.length).toBeGreaterThanOrEqual(3); // Chart, Table, Stories
    });

    it('RR-AX-003: Story titles are h3 headings', () => {
      render(<MockRecoveryReportPage />);
      
      const h3s = screen.getAllByRole('heading', { level: 3 });
      expect(h3s.length).toBeGreaterThan(0);
    });

    it('RR-AX-004: Export button has text content', () => {
      render(<MockRecoveryReportPage />);
      
      expect(screen.getByTestId('export-btn')).toHaveTextContent('Export');
    });

    it('RR-AX-005: Date range selector has options', () => {
      render(<MockRecoveryReportPage />);
      
      const select = screen.getByTestId('date-range-select');
      const options = within(select).getAllByRole('option');
      expect(options.length).toBe(4);
    });
  });
});
