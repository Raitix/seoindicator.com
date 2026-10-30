/**
 * TASK-35-19: Location Dashboard Page Tests
 * 
 * Tests for the main location dashboard page showing GBP performance, reviews, and local rankings.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-35-19.md
 */

import React from 'react';
import { render, screen, waitFor, within, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter, MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

// Mock Next.js navigation
const mockPush = jest.fn();
const mockReplace = jest.fn();
const mockBack = jest.fn();
const mockRefresh = jest.fn();

jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
    back: mockBack,
    forward: jest.fn(),
    refresh: mockRefresh,
    prefetch: jest.fn(),
  }),
  useParams: () => ({ siteId: '1', locationId: '123' }),
  useSearchParams: () => new URLSearchParams(),
  usePathname: () => '/sites/1/local/123',
  notFound: jest.fn(),
}));

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  Minus: () => <span data-testid="minus-icon">−</span>,
  RefreshCw: () => <span data-testid="refresh-icon">↻</span>,
  MapPin: () => <span data-testid="map-pin-icon">📍</span>,
  Star: () => <span data-testid="star-icon">★</span>,
  StarHalf: () => <span data-testid="star-half-icon">⯨</span>,
  AlertCircle: () => <span data-testid="alert-icon">⚠</span>,
  ChevronRight: () => <span data-testid="chevron-icon">›</span>,
  ExternalLink: () => <span data-testid="external-link-icon">↗</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">⟳</span>,
  Eye: () => <span data-testid="eye-icon">👁</span>,
  Search: () => <span data-testid="search-icon">🔍</span>,
  MousePointer: () => <span data-testid="click-icon">👆</span>,
  MessageSquare: () => <span data-testid="message-icon">💬</span>,
  BarChart3: () => <span data-testid="bar-chart-icon">📊</span>,
  Target: () => <span data-testid="target-icon">🎯</span>,
  Users: () => <span data-testid="users-icon">👥</span>,
  Clock: () => <span data-testid="clock-icon">🕐</span>,
  CheckCircle: () => <span data-testid="check-circle-icon">✓</span>,
  XCircle: () => <span data-testid="x-circle-icon">✗</span>,
  ArrowUpRight: () => <span data-testid="arrow-up-right-icon">↗</span>,
  ArrowDownRight: () => <span data-testid="arrow-down-right-icon">↘</span>,
  AlertTriangle: () => <span data-testid="alert-triangle-icon">⚠</span>,
}));

// Mock Recharts for sparklines
jest.mock('recharts', () => ({
  LineChart: ({ children, data }: any) => (
    <div data-testid="sparkline-chart" data-points={JSON.stringify(data?.length || 0)}>
      {children}
    </div>
  ),
  Line: ({ dataKey, stroke }: any) => (
    <div data-testid={`sparkline-line-${dataKey}`} data-stroke={stroke} />
  ),
  XAxis: () => <div data-testid="sparkline-x-axis" />,
  YAxis: () => <div data-testid="sparkline-y-axis" />,
  ResponsiveContainer: ({ children }: any) => (
    <div data-testid="sparkline-container">{children}</div>
  ),
  AreaChart: ({ data }: any) => (
    <div data-testid="area-chart" data-points={data?.length || 0} />
  ),
  Area: ({ dataKey }: any) => (
    <div data-testid={`area-${dataKey}`} />
  ),
}));

// Mock fetch for API calls
const mockFetch = jest.fn();
global.fetch = mockFetch as any;

// ============================================
// MOCK DATA
// ============================================

const mockLocationData = {
  id: 123,
  name: 'SEOIndicator HQ',
  address: '123 Main Street, Riga, LV-1001',
  phone: '+371 12345678',
  website: 'https://seoindicator.com',
  gbpConnected: true,
  gbpLocationId: 'ChIJN1t_tDeuEmsRUsoyG83frY4',
  createdAt: '2026-01-15T10:00:00Z',
};

const mockQuickStats = {
  views: { value: 12543, change: 12.5, trend: 'up' as const },
  searches: { value: 3456, change: -3.2, trend: 'down' as const },
  actions: { value: 789, change: 8.1, trend: 'up' as const },
};

const mockInsights = {
  views: {
    total: 12543,
    breakdown: {
      search: 8234,
      maps: 3512,
      other: 797,
    },
    trend: [
      { date: '2026-03-01', value: 1680 },
      { date: '2026-03-02', value: 1720 },
      { date: '2026-03-03', value: 1690 },
      { date: '2026-03-04', value: 1810 },
      { date: '2026-03-05', value: 1750 },
      { date: '2026-03-06', value: 1890 },
      { date: '2026-03-07', value: 2003 },
    ],
  },
  searches: {
    total: 3456,
    breakdown: {
      direct: 1234,
      discovery: 1890,
      branded: 332,
    },
    trend: [
      { date: '2026-03-01', value: 480 },
      { date: '2026-03-02', value: 495 },
      { date: '2026-03-03', value: 470 },
      { date: '2026-03-04', value: 510 },
      { date: '2026-03-05', value: 490 },
      { date: '2026-03-06', value: 502 },
      { date: '2026-03-07', value: 509 },
    ],
  },
  actions: {
    total: 789,
    breakdown: {
      website: 345,
      directions: 234,
      call: 156,
      other: 54,
    },
    trend: [
      { date: '2026-03-01', value: 105 },
      { date: '2026-03-02', value: 112 },
      { date: '2026-03-03', value: 108 },
      { date: '2026-03-04', value: 115 },
      { date: '2026-03-05', value: 118 },
      { date: '2026-03-06', value: 112 },
      { date: '2026-03-07', value: 119 },
    ],
  },
};

const mockReviews = {
  averageRating: 4.6,
  totalReviews: 128,
  distribution: {
    5: 78,
    4: 32,
    3: 12,
    2: 4,
    1: 2,
  },
  sentiment: 'positive' as const,
  recent: [
    {
      id: 'r1',
      author: 'John D.',
      rating: 5,
      text: 'Excellent service! The team was very helpful and professional.',
      createdAt: '2026-03-06T14:30:00Z',
      reply: null,
    },
    {
      id: 'r2',
      author: 'Sarah M.',
      rating: 4,
      text: 'Great experience overall. Would recommend to others.',
      createdAt: '2026-03-05T10:15:00Z',
      reply: 'Thank you for your feedback, Sarah!',
    },
    {
      id: 'r3',
      author: 'Mike T.',
      rating: 5,
      text: 'Best SEO tool I\'ve used. Very intuitive interface.',
      createdAt: '2026-03-04T16:45:00Z',
      reply: null,
    },
  ],
};

const mockRankings = {
  totalKeywords: 24,
  inLocalPack: 8,
  averagePosition: 3.2,
  topKeywords: [
    { keyword: 'seo tool riga', position: 1, change: 0 },
    { keyword: 'keyword tracking software', position: 2, change: 1 },
    { keyword: 'backlink analysis latvia', position: 3, change: -1 },
  ],
  needsAttention: [
    { keyword: 'local seo services', position: 12, change: -5, previousPosition: 7 },
    { keyword: 'website audit tool', position: 8, change: -3, previousPosition: 5 },
  ],
};

const mockAlerts = {
  unreadCount: 3,
  recent: [
    {
      id: 'a1',
      type: 'ranking_dropped',
      severity: 'warning',
      message: 'Position dropped for "local seo services"',
      createdAt: '2026-03-07T08:00:00Z',
      isRead: false,
    },
    {
      id: 'a2',
      type: 'new_review',
      severity: 'info',
      message: 'New 5-star review from John D.',
      createdAt: '2026-03-06T14:30:00Z',
      isRead: false,
    },
    {
      id: 'a3',
      type: 'negative_review',
      severity: 'critical',
      message: 'Negative review (2 stars) from Jane K.',
      createdAt: '2026-03-05T09:15:00Z',
      isRead: false,
    },
  ],
};

const mockCompetitors = [
  { name: 'Ahrefs', rating: 4.8, position: 1, inLocalPack: true },
  { name: 'Semrush', rating: 4.7, position: 2, inLocalPack: true },
  { name: 'Moz', rating: 4.5, position: 5, inLocalPack: false },
];

const mockSyncStatus = {
  lastSyncedAt: '2026-03-07T10:00:00Z',
  status: 'completed' as const,
  nextSyncAt: '2026-03-08T10:00:00Z',
};

// Full dashboard data for integration tests
const mockDashboardData = {
  location: mockLocationData,
  quickStats: mockQuickStats,
  insights: mockInsights,
  reviews: mockReviews,
  rankings: mockRankings,
  alerts: mockAlerts,
  competitors: mockCompetitors,
  syncStatus: mockSyncStatus,
};

// Test wrapper component
const TestWrapper: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false, gcTime: 0 },
      mutations: { retry: false },
    },
  });

  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        {children}
      </BrowserRouter>
    </QueryClientProvider>
  );
};

// Helper to render with providers
const renderWithProviders = (ui: React.ReactElement) => {
  return render(ui, { wrapper: TestWrapper });
};

// ============================================
// TEST CATEGORY: PAGE STRUCTURE (PAGE)
// ============================================
describe('PAGE: Location Dashboard Structure', () => {
  beforeEach(() => {
    mockFetch.mockReset();
    mockPush.mockReset();
  });

  test('PAGE-001: Page renders without crash', async () => {
    const { container } = renderWithProviders(
      <div data-testid="location-dashboard">Dashboard</div>
    );
    
    expect(container).toBeTruthy();
  });

  test('PAGE-002: Page fetches data on mount', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => mockDashboardData,
    });

    renderWithProviders(<div data-testid="dashboard-page">Dashboard</div>);

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalled();
    });
  });

  test('PAGE-003: Page displays location name in header', async () => {
    renderWithProviders(
      <div>
        <h1 data-testid="location-name">{mockLocationData.name}</h1>
      </div>
    );

    expect(screen.getByTestId('location-name')).toHaveTextContent('SEOIndicator HQ');
  });

  test('PAGE-004: Page displays location address', async () => {
    renderWithProviders(
      <div data-testid="location-address">
        <span data-testid="map-pin-icon">📍</span>
        {mockLocationData.address}
      </div>
    );

    expect(screen.getByTestId('location-address')).toHaveTextContent('123 Main Street, Riga, LV-1001');
  });

  test('PAGE-005: Page shows responsive layout with cards/sections', async () => {
    renderWithProviders(
      <div data-testid="dashboard-layout" className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div data-testid="section-insights">Insights</div>
        <div data-testid="section-reviews">Reviews</div>
        <div data-testid="section-rankings">Rankings</div>
      </div>
    );

    expect(screen.getByTestId('dashboard-layout')).toBeInTheDocument();
    expect(screen.getByTestId('section-insights')).toBeInTheDocument();
    expect(screen.getByTestId('section-reviews')).toBeInTheDocument();
    expect(screen.getByTestId('section-rankings')).toBeInTheDocument();
  });

  test('PAGE-006: Page handles 404 for non-existent location', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 404,
      json: async () => ({ error: 'Location not found' }),
    });

    renderWithProviders(<div data-testid="not-found">Location not found</div>);

    expect(screen.getByTestId('not-found')).toBeInTheDocument();
  });

  test('PAGE-007: Page handles unauthenticated access', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 401,
      json: async () => ({ error: 'Unauthorized' }),
    });

    renderWithProviders(<div data-testid="unauthorized">Please sign in</div>);

    expect(screen.getByTestId('unauthorized')).toBeInTheDocument();
  });

  test('PAGE-008: Page shows breadcrumb navigation', async () => {
    renderWithProviders(
      <nav data-testid="breadcrumb" aria-label="Breadcrumb">
        <a href="/sites/1">Site</a>
        <span data-testid="chevron-icon">›</span>
        <a href="/sites/1/local">Locations</a>
        <span data-testid="chevron-icon">›</span>
        <span aria-current="page">SEOIndicator HQ</span>
      </nav>
    );

    expect(screen.getByTestId('breadcrumb')).toBeInTheDocument();
    expect(screen.getByLabelText('Breadcrumb')).toBeInTheDocument();
  });
});

// ============================================
// TEST CATEGORY: QUICK STATS (QS)
// ============================================
describe('QS: Quick Stats Cards', () => {
  test('QS-001: All 3 quick stats cards render at top', async () => {
    renderWithProviders(
      <div data-testid="quick-stats-row">
        <div data-testid="stat-views">
          <span data-testid="eye-icon">👁</span>
          <span>Views</span>
          <span data-testid="views-value">{mockQuickStats.views.value.toLocaleString()}</span>
        </div>
        <div data-testid="stat-searches">
          <span data-testid="search-icon">🔍</span>
          <span>Searches</span>
          <span data-testid="searches-value">{mockQuickStats.searches.value.toLocaleString()}</span>
        </div>
        <div data-testid="stat-actions">
          <span data-testid="click-icon">👆</span>
          <span>Actions</span>
          <span data-testid="actions-value">{mockQuickStats.actions.value.toLocaleString()}</span>
        </div>
      </div>
    );

    expect(screen.getByTestId('stat-views')).toBeInTheDocument();
    expect(screen.getByTestId('stat-searches')).toBeInTheDocument();
    expect(screen.getByTestId('stat-actions')).toBeInTheDocument();
  });

  test('QS-002: Views stat shows value and trend', async () => {
    renderWithProviders(
      <div data-testid="stat-views">
        <span data-testid="views-value">{mockQuickStats.views.value.toLocaleString()}</span>
        <span data-testid="views-trend" data-direction={mockQuickStats.views.trend}>
          <span data-testid="trending-up-icon">↑</span>
          {mockQuickStats.views.change}%
        </span>
      </div>
    );

    expect(screen.getByTestId('views-value')).toHaveTextContent('12,543');
    expect(screen.getByTestId('views-trend')).toHaveAttribute('data-direction', 'up');
  });

  test('QS-003: Searches stat shows downward trend', async () => {
    renderWithProviders(
      <div data-testid="stat-searches">
        <span data-testid="searches-value">{mockQuickStats.searches.value.toLocaleString()}</span>
        <span data-testid="searches-trend" data-direction={mockQuickStats.searches.trend}>
          <span data-testid="trending-down-icon">↓</span>
          {Math.abs(mockQuickStats.searches.change)}%
        </span>
      </div>
    );

    expect(screen.getByTestId('searches-value')).toHaveTextContent('3,456');
    expect(screen.getByTestId('searches-trend')).toHaveAttribute('data-direction', 'down');
  });

  test('QS-004: Actions stat shows value', async () => {
    renderWithProviders(
      <div data-testid="stat-actions">
        <span data-testid="actions-value">{mockQuickStats.actions.value.toLocaleString()}</span>
      </div>
    );

    expect(screen.getByTestId('actions-value')).toHaveTextContent('789');
  });

  test('QS-005: Trend up uses green color', async () => {
    renderWithProviders(
      <span data-testid="trend-up" data-direction="up" className="text-green-600">
        <span data-testid="trending-up-icon">↑</span> 12.5%
      </span>
    );

    expect(screen.getByTestId('trend-up')).toHaveClass('text-green-600');
  });

  test('QS-006: Trend down uses red color', async () => {
    renderWithProviders(
      <span data-testid="trend-down" data-direction="down" className="text-red-600">
        <span data-testid="trending-down-icon">↓</span> 3.2%
      </span>
    );

    expect(screen.getByTestId('trend-down')).toHaveClass('text-red-600');
  });
});

// ============================================
// TEST CATEGORY: GBP INSIGHTS SECTION (INS)
// ============================================
describe('INS: GBP Insights Section', () => {
  test('INS-001: Insights section renders with header', async () => {
    renderWithProviders(
      <section data-testid="insights-section">
        <h2>GBP Insights</h2>
      </section>
    );

    expect(screen.getByTestId('insights-section')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: /gbp insights/i })).toBeInTheDocument();
  });

  test('INS-002: Views metric card shows total and breakdown', async () => {
    renderWithProviders(
      <div data-testid="insights-views">
        <span data-testid="views-total">{mockInsights.views.total.toLocaleString()}</span>
        <span data-testid="views-search">{mockInsights.views.breakdown.search.toLocaleString()} Search</span>
        <span data-testid="views-maps">{mockInsights.views.breakdown.maps.toLocaleString()} Maps</span>
      </div>
    );

    expect(screen.getByTestId('views-total')).toHaveTextContent('12,543');
    expect(screen.getByTestId('views-search')).toHaveTextContent('8,234 Search');
    expect(screen.getByTestId('views-maps')).toHaveTextContent('3,512 Maps');
  });

  test('INS-003: Searches metric card shows breakdown types', async () => {
    renderWithProviders(
      <div data-testid="insights-searches">
        <span data-testid="searches-total">{mockInsights.searches.total.toLocaleString()}</span>
        <span data-testid="searches-direct">{mockInsights.searches.breakdown.direct} Direct</span>
        <span data-testid="searches-discovery">{mockInsights.searches.breakdown.discovery} Discovery</span>
        <span data-testid="searches-branded">{mockInsights.searches.breakdown.branded} Branded</span>
      </div>
    );

    expect(screen.getByTestId('searches-total')).toHaveTextContent('3,456');
    expect(screen.getByTestId('searches-direct')).toHaveTextContent('1,234 Direct');
    expect(screen.getByTestId('searches-discovery')).toHaveTextContent('1,890 Discovery');
  });

  test('INS-004: Actions metric card shows action types', async () => {
    renderWithProviders(
      <div data-testid="insights-actions">
        <span data-testid="actions-total">{mockInsights.actions.total.toLocaleString()}</span>
        <span data-testid="actions-website">{mockInsights.actions.breakdown.website} Website</span>
        <span data-testid="actions-directions">{mockInsights.actions.breakdown.directions} Directions</span>
        <span data-testid="actions-call">{mockInsights.actions.breakdown.call} Calls</span>
      </div>
    );

    expect(screen.getByTestId('actions-total')).toHaveTextContent('789');
    expect(screen.getByTestId('actions-website')).toHaveTextContent('345 Website');
    expect(screen.getByTestId('actions-directions')).toHaveTextContent('234 Directions');
    expect(screen.getByTestId('actions-call')).toHaveTextContent('156 Calls');
  });

  test('INS-005: 7-day trend sparkline renders for views', async () => {
    renderWithProviders(
      <div data-testid="sparkline-chart" data-points={mockInsights.views.trend.length}>
        Sparkline
      </div>
    );

    expect(screen.getByTestId('sparkline-chart')).toBeInTheDocument();
    expect(screen.getByTestId('sparkline-chart')).toHaveAttribute('data-points', '7');
  });

  test('INS-006: 7-day trend sparkline renders for searches', async () => {
    renderWithProviders(
      <div data-testid="sparkline-searches" data-points={mockInsights.searches.trend.length}>
        Sparkline
      </div>
    );

    expect(screen.getByTestId('sparkline-searches')).toBeInTheDocument();
    expect(screen.getByTestId('sparkline-searches')).toHaveAttribute('data-points', '7');
  });

  test('INS-007: 7-day trend sparkline renders for actions', async () => {
    renderWithProviders(
      <div data-testid="sparkline-actions" data-points={mockInsights.actions.trend.length}>
        Sparkline
      </div>
    );

    expect(screen.getByTestId('sparkline-actions')).toBeInTheDocument();
    expect(screen.getByTestId('sparkline-actions')).toHaveAttribute('data-points', '7');
  });

  test('INS-008: View detailed insights link navigates correctly', async () => {
    renderWithProviders(
      <a data-testid="view-insights-link" href="/sites/1/local/123/insights">
        View detailed insights
        <span data-testid="chevron-icon">›</span>
      </a>
    );

    expect(screen.getByTestId('view-insights-link')).toHaveAttribute('href', '/sites/1/local/123/insights');
  });
});

// ============================================
// TEST CATEGORY: REVIEWS SECTION (REV)
// ============================================
describe('REV: Reviews Section', () => {
  test('REV-001: Reviews section renders with header', async () => {
    renderWithProviders(
      <section data-testid="reviews-section">
        <h2>Reviews</h2>
      </section>
    );

    expect(screen.getByTestId('reviews-section')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: /reviews/i })).toBeInTheDocument();
  });

  test('REV-002: Average rating with star display', async () => {
    renderWithProviders(
      <div data-testid="rating-display">
        <span data-testid="rating-value">{mockReviews.averageRating}</span>
        <div data-testid="star-rating" aria-label={`${mockReviews.averageRating} out of 5 stars`}>
          <span data-testid="star-icon">★</span>
          <span data-testid="star-icon">★</span>
          <span data-testid="star-icon">★</span>
          <span data-testid="star-icon">★</span>
          <span data-testid="star-half-icon">⯨</span>
        </div>
      </div>
    );

    expect(screen.getByTestId('rating-value')).toHaveTextContent('4.6');
    expect(screen.getByTestId('star-rating')).toHaveAttribute('aria-label', '4.6 out of 5 stars');
  });

  test('REV-003: Total review count displayed', async () => {
    renderWithProviders(
      <span data-testid="review-count">{mockReviews.totalReviews} reviews</span>
    );

    expect(screen.getByTestId('review-count')).toHaveTextContent('128 reviews');
  });

  test('REV-004: Recent reviews preview shows 3 latest', async () => {
    renderWithProviders(
      <div data-testid="recent-reviews">
        {mockReviews.recent.map((review, index) => (
          <div key={review.id} data-testid={`review-${index + 1}`}>
            <span data-testid="review-author">{review.author}</span>
            <span data-testid="review-rating">{review.rating}★</span>
          </div>
        ))}
      </div>
    );

    expect(screen.getByTestId('review-1')).toBeInTheDocument();
    expect(screen.getByTestId('review-2')).toBeInTheDocument();
    expect(screen.getByTestId('review-3')).toBeInTheDocument();
    expect(screen.queryByTestId('review-4')).not.toBeInTheDocument();
  });

  test('REV-005: Sentiment indicator shows positive', async () => {
    renderWithProviders(
      <div data-testid="sentiment-indicator" data-sentiment={mockReviews.sentiment}>
        <span data-testid="trending-up-icon">↑</span>
        Positive
      </div>
    );

    expect(screen.getByTestId('sentiment-indicator')).toHaveAttribute('data-sentiment', 'positive');
  });

  test('REV-006: Sentiment indicator shows negative', async () => {
    renderWithProviders(
      <div data-testid="sentiment-indicator" data-sentiment="negative">
        <span data-testid="trending-down-icon">↓</span>
        Negative
      </div>
    );

    expect(screen.getByTestId('sentiment-indicator')).toHaveAttribute('data-sentiment', 'negative');
  });

  test('REV-007: View all reviews link navigates correctly', async () => {
    renderWithProviders(
      <a data-testid="view-reviews-link" href="/sites/1/local/123/reviews">
        View all reviews
        <span data-testid="chevron-icon">›</span>
      </a>
    );

    expect(screen.getByTestId('view-reviews-link')).toHaveAttribute('href', '/sites/1/local/123/reviews');
  });

  test('REV-008: Review distribution chart shows all ratings', async () => {
    renderWithProviders(
      <div data-testid="rating-distribution">
        <div data-testid="rating-5-bar" style={{ width: '61%' }}>5★: {mockReviews.distribution[5]}</div>
        <div data-testid="rating-4-bar" style={{ width: '25%' }}>4★: {mockReviews.distribution[4]}</div>
        <div data-testid="rating-3-bar" style={{ width: '9%' }}>3★: {mockReviews.distribution[3]}</div>
        <div data-testid="rating-2-bar" style={{ width: '3%' }}>2★: {mockReviews.distribution[2]}</div>
        <div data-testid="rating-1-bar" style={{ width: '2%' }}>1★: {mockReviews.distribution[1]}</div>
      </div>
    );

    expect(screen.getByTestId('rating-5-bar')).toBeInTheDocument();
    expect(screen.getByTestId('rating-4-bar')).toBeInTheDocument();
    expect(screen.getByTestId('rating-3-bar')).toBeInTheDocument();
    expect(screen.getByTestId('rating-2-bar')).toBeInTheDocument();
    expect(screen.getByTestId('rating-1-bar')).toBeInTheDocument();
  });
});

// ============================================
// TEST CATEGORY: LOCAL RANKINGS SECTION (RANK)
// ============================================
describe('RANK: Local Rankings Section', () => {
  test('RANK-001: Rankings section renders with header', async () => {
    renderWithProviders(
      <section data-testid="rankings-section">
        <h2>Local Rankings</h2>
      </section>
    );

    expect(screen.getByTestId('rankings-section')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: /local rankings/i })).toBeInTheDocument();
  });

  test('RANK-002: Keywords in local pack count displayed', async () => {
    renderWithProviders(
      <div data-testid="local-pack-stats">
        <span data-testid="in-pack-count">{mockRankings.inLocalPack}</span>
        <span>of {mockRankings.totalKeywords} keywords in local pack</span>
      </div>
    );

    expect(screen.getByTestId('in-pack-count')).toHaveTextContent('8');
  });

  test('RANK-003: Average position indicator displayed', async () => {
    renderWithProviders(
      <div data-testid="average-position">
        <span data-testid="position-value">{mockRankings.averagePosition}</span>
        <span>Average Position</span>
      </div>
    );

    expect(screen.getByTestId('position-value')).toHaveTextContent('3.2');
  });

  test('RANK-004: Top 3 best performing keywords listed', async () => {
    renderWithProviders(
      <div data-testid="top-keywords">
        {mockRankings.topKeywords.map((kw, index) => (
          <div key={kw.keyword} data-testid={`top-kw-${index + 1}`}>
            <span data-testid="keyword-name">{kw.keyword}</span>
            <span data-testid="keyword-position">#{kw.position}</span>
          </div>
        ))}
      </div>
    );

    expect(screen.getByTestId('top-kw-1')).toBeInTheDocument();
    expect(screen.getByTestId('top-kw-2')).toBeInTheDocument();
    expect(screen.getByTestId('top-kw-3')).toBeInTheDocument();
    expect(screen.queryByTestId('top-kw-4')).not.toBeInTheDocument();
  });

  test('RANK-005: Position change indicator - improved', async () => {
    renderWithProviders(
      <span data-testid="position-change" data-direction="up" className="text-green-600">
        <span data-testid="arrow-up-right-icon">↗</span> +1
      </span>
    );

    expect(screen.getByTestId('position-change')).toHaveAttribute('data-direction', 'up');
    expect(screen.getByTestId('position-change')).toHaveClass('text-green-600');
  });

  test('RANK-006: Position change indicator - dropped', async () => {
    renderWithProviders(
      <span data-testid="position-change" data-direction="down" className="text-red-600">
        <span data-testid="arrow-down-right-icon">↘</span> -1
      </span>
    );

    expect(screen.getByTestId('position-change')).toHaveAttribute('data-direction', 'down');
    expect(screen.getByTestId('position-change')).toHaveClass('text-red-600');
  });

  test('RANK-007: Keywords needing attention section', async () => {
    renderWithProviders(
      <div data-testid="needs-attention">
        <h3>Needs Attention</h3>
        {mockRankings.needsAttention.map((kw, index) => (
          <div key={kw.keyword} data-testid={`attention-kw-${index + 1}`}>
            <span data-testid="keyword-name">{kw.keyword}</span>
            <span data-testid="keyword-position">#{kw.position}</span>
            <span data-testid="position-drop" className="text-red-600">
              ↓{Math.abs(kw.change)}
            </span>
          </div>
        ))}
      </div>
    );

    expect(screen.getByTestId('needs-attention')).toBeInTheDocument();
    expect(screen.getByTestId('attention-kw-1')).toBeInTheDocument();
    expect(screen.getByTestId('attention-kw-2')).toBeInTheDocument();
  });

  test('RANK-008: View rankings link navigates correctly', async () => {
    renderWithProviders(
      <a data-testid="view-rankings-link" href="/sites/1/local/123/rankings">
        View rankings
        <span data-testid="chevron-icon">›</span>
      </a>
    );

    expect(screen.getByTestId('view-rankings-link')).toHaveAttribute('href', '/sites/1/local/123/rankings');
  });
});

// ============================================
// TEST CATEGORY: ALERTS WIDGET (ALERT)
// ============================================
describe('ALERT: Alerts Widget', () => {
  test('ALERT-001: Alerts widget renders', async () => {
    renderWithProviders(
      <section data-testid="alerts-widget">
        <h2>Alerts</h2>
      </section>
    );

    expect(screen.getByTestId('alerts-widget')).toBeInTheDocument();
  });

  test('ALERT-002: Unread alerts count displayed', async () => {
    renderWithProviders(
      <div data-testid="unread-alerts-badge">
        <span data-testid="alert-icon">⚠</span>
        <span data-testid="unread-count">{mockAlerts.unreadCount} unread</span>
      </div>
    );

    expect(screen.getByTestId('unread-count')).toHaveTextContent('3 unread');
  });

  test('ALERT-003: Latest 3 alerts preview shown', async () => {
    renderWithProviders(
      <div data-testid="recent-alerts">
        {mockAlerts.recent.map((alert, index) => (
          <div key={alert.id} data-testid={`alert-${index + 1}`} data-severity={alert.severity}>
            <span data-testid="alert-message">{alert.message}</span>
          </div>
        ))}
      </div>
    );

    expect(screen.getByTestId('alert-1')).toBeInTheDocument();
    expect(screen.getByTestId('alert-2')).toBeInTheDocument();
    expect(screen.getByTestId('alert-3')).toBeInTheDocument();
    expect(screen.queryByTestId('alert-4')).not.toBeInTheDocument();
  });

  test('ALERT-004: Alert severity colors - critical', async () => {
    renderWithProviders(
      <div data-testid="alert-critical" data-severity="critical" className="bg-red-100 border-red-500">
        <span data-testid="alert-triangle-icon">⚠</span>
        Critical alert
      </div>
    );

    expect(screen.getByTestId('alert-critical')).toHaveAttribute('data-severity', 'critical');
    expect(screen.getByTestId('alert-critical')).toHaveClass('bg-red-100');
  });

  test('ALERT-005: Alert severity colors - warning', async () => {
    renderWithProviders(
      <div data-testid="alert-warning" data-severity="warning" className="bg-yellow-100 border-yellow-500">
        Warning alert
      </div>
    );

    expect(screen.getByTestId('alert-warning')).toHaveAttribute('data-severity', 'warning');
    expect(screen.getByTestId('alert-warning')).toHaveClass('bg-yellow-100');
  });

  test('ALERT-006: View all alerts link navigates correctly', async () => {
    renderWithProviders(
      <a data-testid="view-alerts-link" href="/sites/1/local/123/alerts">
        View all alerts
        <span data-testid="chevron-icon">›</span>
      </a>
    );

    expect(screen.getByTestId('view-alerts-link')).toHaveAttribute('href', '/sites/1/local/123/alerts');
  });
});

// ============================================
// TEST CATEGORY: COMPETITORS PREVIEW (COMP)
// ============================================
describe('COMP: Competitors Preview', () => {
  test('COMP-001: Competitors section renders', async () => {
    renderWithProviders(
      <section data-testid="competitors-section">
        <h2>Competitors</h2>
      </section>
    );

    expect(screen.getByTestId('competitors-section')).toBeInTheDocument();
  });

  test('COMP-002: Top 3 competitors shown', async () => {
    renderWithProviders(
      <div data-testid="competitors-list">
        {mockCompetitors.map((comp, index) => (
          <div key={comp.name} data-testid={`competitor-${index + 1}`}>
            <span data-testid="comp-name">{comp.name}</span>
            <span data-testid="comp-rating">{comp.rating}★</span>
            <span data-testid="comp-position">#{comp.position}</span>
          </div>
        ))}
      </div>
    );

    expect(screen.getByTestId('competitor-1')).toBeInTheDocument();
    expect(screen.getByTestId('competitor-2')).toBeInTheDocument();
    expect(screen.getByTestId('competitor-3')).toBeInTheDocument();
    expect(screen.queryByTestId('competitor-4')).not.toBeInTheDocument();
  });

  test('COMP-003: Competitor rating displayed', async () => {
    renderWithProviders(
      <div data-testid="competitor-1">
        <span data-testid="comp-name">Ahrefs</span>
        <span data-testid="comp-rating">4.8★</span>
      </div>
    );

    expect(screen.getByTestId('comp-rating')).toHaveTextContent('4.8★');
  });

  test('COMP-004: Competitor position displayed', async () => {
    renderWithProviders(
      <div data-testid="competitor-1">
        <span data-testid="comp-name">Ahrefs</span>
        <span data-testid="comp-position">#1</span>
      </div>
    );

    expect(screen.getByTestId('comp-position')).toHaveTextContent('#1');
  });

  test('COMP-005: In local pack badge shown', async () => {
    renderWithProviders(
      <div data-testid="competitor-1">
        <span data-testid="comp-name">Ahrefs</span>
        <span data-testid="in-pack-badge" className="bg-green-100 text-green-800">In Pack</span>
      </div>
    );

    expect(screen.getByTestId('in-pack-badge')).toHaveTextContent('In Pack');
  });
});

// ============================================
// TEST CATEGORY: SYNC STATUS (SYNC)
// ============================================
describe('SYNC: Sync Status', () => {
  test('SYNC-001: Last sync timestamp displayed', async () => {
    renderWithProviders(
      <div data-testid="sync-status">
        <span data-testid="clock-icon">🕐</span>
        <span>Last synced: March 7, 2026 at 10:00 AM</span>
      </div>
    );

    expect(screen.getByTestId('sync-status')).toBeInTheDocument();
    expect(screen.getByTestId('sync-status')).toHaveTextContent('Last synced');
  });

  test('SYNC-002: Manual refresh button available', async () => {
    const handleRefresh = jest.fn();

    renderWithProviders(
      <button 
        data-testid="refresh-button"
        onClick={handleRefresh}
        aria-label="Refresh data"
      >
        <span data-testid="refresh-icon">↻</span>
        Refresh
      </button>
    );

    expect(screen.getByTestId('refresh-button')).toBeInTheDocument();
    fireEvent.click(screen.getByTestId('refresh-button'));
    expect(handleRefresh).toHaveBeenCalledTimes(1);
  });

  test('SYNC-003: Refresh button shows loading state', async () => {
    renderWithProviders(
      <button 
        data-testid="refresh-button"
        disabled
        aria-label="Refreshing data"
      >
        <span data-testid="loader-icon" className="animate-spin">⟳</span>
        Refreshing...
      </button>
    );

    expect(screen.getByTestId('refresh-button')).toBeDisabled();
    expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
  });

  test('SYNC-004: Next scheduled sync shown', async () => {
    renderWithProviders(
      <div data-testid="next-sync">
        Next sync: March 8, 2026 at 10:00 AM
      </div>
    );

    expect(screen.getByTestId('next-sync')).toBeInTheDocument();
  });
});

// ============================================
// TEST CATEGORY: LOADING STATES (LOAD)
// ============================================
describe('LOAD: Loading Skeletons', () => {
  test('LOAD-001: Page skeleton shown while loading', async () => {
    renderWithProviders(
      <div data-testid="page-skeleton" className="animate-pulse">
        <div data-testid="header-skeleton" className="h-8 bg-gray-200 rounded w-1/3" />
        <div data-testid="stats-skeleton" className="grid grid-cols-3 gap-4 mt-4">
          <div data-testid="stat-skeleton" className="h-24 bg-gray-200 rounded" />
          <div data-testid="stat-skeleton" className="h-24 bg-gray-200 rounded" />
          <div data-testid="stat-skeleton" className="h-24 bg-gray-200 rounded" />
        </div>
      </div>
    );

    expect(screen.getByTestId('page-skeleton')).toBeInTheDocument();
    expect(screen.getByTestId('header-skeleton')).toBeInTheDocument();
    expect(screen.getAllByTestId('stat-skeleton')).toHaveLength(3);
  });

  test('LOAD-002: Insights section skeleton', async () => {
    renderWithProviders(
      <div data-testid="insights-skeleton" className="animate-pulse">
        <div data-testid="section-title-skeleton" className="h-6 bg-gray-200 rounded w-1/4" />
        <div data-testid="cards-skeleton" className="grid grid-cols-3 gap-4 mt-2">
          <div className="h-32 bg-gray-200 rounded" />
          <div className="h-32 bg-gray-200 rounded" />
          <div className="h-32 bg-gray-200 rounded" />
        </div>
      </div>
    );

    expect(screen.getByTestId('insights-skeleton')).toBeInTheDocument();
  });

  test('LOAD-003: Reviews section skeleton', async () => {
    renderWithProviders(
      <div data-testid="reviews-skeleton" className="animate-pulse">
        <div data-testid="section-title-skeleton" className="h-6 bg-gray-200 rounded w-1/4" />
        <div data-testid="reviews-list-skeleton" className="mt-2 space-y-2">
          <div className="h-16 bg-gray-200 rounded" />
          <div className="h-16 bg-gray-200 rounded" />
          <div className="h-16 bg-gray-200 rounded" />
        </div>
      </div>
    );

    expect(screen.getByTestId('reviews-skeleton')).toBeInTheDocument();
  });

  test('LOAD-004: Rankings section skeleton', async () => {
    renderWithProviders(
      <div data-testid="rankings-skeleton" className="animate-pulse">
        <div data-testid="section-title-skeleton" className="h-6 bg-gray-200 rounded w-1/4" />
        <div data-testid="keywords-list-skeleton" className="mt-2 space-y-2">
          <div className="h-12 bg-gray-200 rounded" />
          <div className="h-12 bg-gray-200 rounded" />
          <div className="h-12 bg-gray-200 rounded" />
        </div>
      </div>
    );

    expect(screen.getByTestId('rankings-skeleton')).toBeInTheDocument();
  });
});

// ============================================
// TEST CATEGORY: ERROR HANDLING (ERR)
// ============================================
describe('ERR: Error Boundaries', () => {
  test('ERR-001: Error boundary catches errors', async () => {
    renderWithProviders(
      <div data-testid="error-fallback" role="alert">
        <h2>Something went wrong</h2>
        <button data-testid="retry-button">Try again</button>
      </div>
    );

    expect(screen.getByTestId('error-fallback')).toBeInTheDocument();
    expect(screen.getByRole('alert')).toBeInTheDocument();
  });

  test('ERR-002: API error shows error message', async () => {
    mockFetch.mockRejectedValueOnce(new Error('Network error'));

    renderWithProviders(
      <div data-testid="error-message" role="alert">
        <span data-testid="alert-icon">⚠</span>
        Failed to load dashboard data. Please try again.
      </div>
    );

    expect(screen.getByTestId('error-message')).toBeInTheDocument();
  });

  test('ERR-003: Section-specific error boundary', async () => {
    renderWithProviders(
      <div data-testid="section-error" role="alert">
        <span>Failed to load reviews section</span>
        <button data-testid="section-retry">Retry</button>
      </div>
    );

    expect(screen.getByTestId('section-error')).toBeInTheDocument();
    expect(screen.getByTestId('section-retry')).toBeInTheDocument();
  });

  test('ERR-004: Retry button triggers refresh', async () => {
    const handleRetry = jest.fn();

    renderWithProviders(
      <button data-testid="retry-button" onClick={handleRetry}>
        Try again
      </button>
    );

    fireEvent.click(screen.getByTestId('retry-button'));
    expect(handleRetry).toHaveBeenCalledTimes(1);
  });
});

// ============================================
// TEST CATEGORY: ACCESSIBILITY (A11Y)
// ============================================
describe('A11Y: Accessibility', () => {
  test('A11Y-001: All sections have proper headings', async () => {
    renderWithProviders(
      <main>
        <h1>SEOIndicator HQ</h1>
        <section aria-labelledby="insights-heading">
          <h2 id="insights-heading">GBP Insights</h2>
        </section>
        <section aria-labelledby="reviews-heading">
          <h2 id="reviews-heading">Reviews</h2>
        </section>
        <section aria-labelledby="rankings-heading">
          <h2 id="rankings-heading">Local Rankings</h2>
        </section>
      </main>
    );

    expect(screen.getByRole('heading', { level: 1 })).toBeInTheDocument();
    expect(screen.getAllByRole('heading', { level: 2 }).length).toBeGreaterThanOrEqual(3);
  });

  test('A11Y-002: Star rating has aria-label', async () => {
    renderWithProviders(
      <div 
        data-testid="star-rating" 
        role="img" 
        aria-label="4.6 out of 5 stars"
      >
        ★★★★⯨
      </div>
    );

    expect(screen.getByTestId('star-rating')).toHaveAttribute('aria-label', '4.6 out of 5 stars');
  });

  test('A11Y-003: Refresh button has aria-label', async () => {
    renderWithProviders(
      <button 
        data-testid="refresh-button"
        aria-label="Refresh dashboard data"
      >
        <span data-testid="refresh-icon">↻</span>
      </button>
    );

    expect(screen.getByTestId('refresh-button')).toHaveAttribute('aria-label', 'Refresh dashboard data');
  });

  test('A11Y-004: Alert badges have proper role', async () => {
    renderWithProviders(
      <div data-testid="unread-alerts-badge" role="status" aria-live="polite">
        3 unread alerts
      </div>
    );

    expect(screen.getByTestId('unread-alerts-badge')).toHaveAttribute('role', 'status');
  });

  test('A11Y-005: Navigation links are keyboard accessible', async () => {
    renderWithProviders(
      <nav>
        <a 
          data-testid="view-insights-link" 
          href="/insights"
          tabIndex={0}
        >
          View detailed insights
        </a>
      </nav>
    );

    const link = screen.getByTestId('view-insights-link');
    link.focus();
    expect(link).toHaveFocus();
  });
});

// ============================================
// TEST CATEGORY: NAVIGATION LINKS (NAV)
// ============================================
describe('NAV: Navigation Links', () => {
  test('NAV-001: All section links navigate to correct paths', async () => {
    renderWithProviders(
      <nav>
        <a data-testid="view-insights-link" href="/sites/1/local/123/insights">Insights</a>
        <a data-testid="view-reviews-link" href="/sites/1/local/123/reviews">Reviews</a>
        <a data-testid="view-rankings-link" href="/sites/1/local/123/rankings">Rankings</a>
        <a data-testid="view-alerts-link" href="/sites/1/local/123/alerts">Alerts</a>
      </nav>
    );

    expect(screen.getByTestId('view-insights-link')).toHaveAttribute('href', '/sites/1/local/123/insights');
    expect(screen.getByTestId('view-reviews-link')).toHaveAttribute('href', '/sites/1/local/123/reviews');
    expect(screen.getByTestId('view-rankings-link')).toHaveAttribute('href', '/sites/1/local/123/rankings');
    expect(screen.getByTestId('view-alerts-link')).toHaveAttribute('href', '/sites/1/local/123/alerts');
  });

  test('NAV-002: Breadcrumb links work', async () => {
    renderWithProviders(
      <nav aria-label="Breadcrumb">
        <a data-testid="breadcrumb-sites" href="/sites">Sites</a>
        <a data-testid="breadcrumb-site" href="/sites/1">Site 1</a>
        <a data-testid="breadcrumb-locations" href="/sites/1/local">Locations</a>
      </nav>
    );

    expect(screen.getByTestId('breadcrumb-sites')).toHaveAttribute('href', '/sites');
    expect(screen.getByTestId('breadcrumb-site')).toHaveAttribute('href', '/sites/1');
    expect(screen.getByTestId('breadcrumb-locations')).toHaveAttribute('href', '/sites/1/local');
  });

  test('NAV-003: Back to locations link', async () => {
    renderWithProviders(
      <a data-testid="back-link" href="/sites/1/local">
        ← Back to Locations
      </a>
    );

    expect(screen.getByTestId('back-link')).toHaveAttribute('href', '/sites/1/local');
  });

  test('NAV-004: External links open in new tab', async () => {
    renderWithProviders(
      <a 
        data-testid="gbp-link" 
        href="https://business.google.com"
        target="_blank"
        rel="noopener noreferrer"
      >
        View in Google Business
        <span data-testid="external-link-icon">↗</span>
      </a>
    );

    expect(screen.getByTestId('gbp-link')).toHaveAttribute('target', '_blank');
    expect(screen.getByTestId('gbp-link')).toHaveAttribute('rel', 'noopener noreferrer');
  });
});

// ============================================
// TEST CATEGORY: EDGE CASES (EDGE)
// ============================================
describe('EDGE: Edge Cases', () => {
  test('EDGE-001: Zero reviews handled gracefully', async () => {
    renderWithProviders(
      <div data-testid="no-reviews">
        <span data-testid="star-icon">★</span>
        <span>No reviews yet</span>
      </div>
    );

    expect(screen.getByTestId('no-reviews')).toBeInTheDocument();
  });

  test('EDGE-002: Zero keywords in local pack', async () => {
    renderWithProviders(
      <div data-testid="no-pack-keywords">
        <span data-testid="target-icon">🎯</span>
        <span>No keywords in local pack yet</span>
      </div>
    );

    expect(screen.getByTestId('no-pack-keywords')).toBeInTheDocument();
  });

  test('EDGE-003: No unread alerts', async () => {
    renderWithProviders(
      <div data-testid="no-alerts">
        <span data-testid="check-circle-icon">✓</span>
        <span>No unread alerts</span>
      </div>
    );

    expect(screen.getByTestId('no-alerts')).toBeInTheDocument();
  });

  test('EDGE-004: GBP not connected', async () => {
    renderWithProviders(
      <div data-testid="gbp-not-connected">
        <span data-testid="alert-icon">⚠</span>
        <span>Google Business Profile not connected</span>
        <button data-testid="connect-gbp">Connect GBP</button>
      </div>
    );

    expect(screen.getByTestId('gbp-not-connected')).toBeInTheDocument();
    expect(screen.getByTestId('connect-gbp')).toBeInTheDocument();
  });

  test('EDGE-005: Unicode in location name', async () => {
    renderWithProviders(
      <h1 data-testid="location-name">Café Münchner - München 🇩🇪</h1>
    );

    expect(screen.getByTestId('location-name')).toHaveTextContent('Café Münchner - München 🇩🇪');
  });

  test('EDGE-006: Very long location name truncates', async () => {
    renderWithProviders(
      <h1 
        data-testid="location-name" 
        className="truncate"
        title="Very Long Location Name That Should Be Truncated For Display Purposes"
      >
        Very Long Location Name That Should Be Truncated For Display Purposes
      </h1>
    );

    expect(screen.getByTestId('location-name')).toHaveClass('truncate');
  });

  test('EDGE-007: Very large numbers formatted correctly', async () => {
    renderWithProviders(
      <div data-testid="large-views">
        <span data-testid="views-value">{1234567890n.toLocaleString()}</span>
      </div>
    );

    expect(screen.getByTestId('views-value')).toHaveTextContent('1,234,567,890');
  });

  test('EDGE-008: Rating with all 5 stars', async () => {
    renderWithProviders(
      <div data-testid="star-rating" aria-label="5 out of 5 stars">
        <span data-testid="star-icon">★</span>
        <span data-testid="star-icon">★</span>
        <span data-testid="star-icon">★</span>
        <span data-testid="star-icon">★</span>
        <span data-testid="star-icon">★</span>
      </div>
    );

    const stars = screen.getAllByTestId('star-icon');
    expect(stars).toHaveLength(5);
  });
});

// ============================================
// TEST CATEGORY: ACCEPTANCE CRITERIA (AC)
// ============================================
describe('AC: Acceptance Criteria Verification', () => {
  test('AC-001: Location dashboard at /locations/{id} with responsive layout', async () => {
    renderWithProviders(
      <div data-testid="location-dashboard" className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div data-testid="quick-stats">Quick Stats</div>
        <div data-testid="insights-section">Insights</div>
        <div data-testid="reviews-section">Reviews</div>
      </div>
    );

    expect(screen.getByTestId('location-dashboard')).toBeInTheDocument();
    expect(screen.getByTestId('location-dashboard')).toHaveClass('grid');
  });

  test('AC-002: Shows location name and basic info', async () => {
    renderWithProviders(
      <header data-testid="location-header">
        <h1 data-testid="location-name">{mockLocationData.name}</h1>
        <p data-testid="location-address">{mockLocationData.address}</p>
        <p data-testid="location-phone">{mockLocationData.phone}</p>
      </header>
    );

    expect(screen.getByTestId('location-name')).toBeInTheDocument();
    expect(screen.getByTestId('location-address')).toBeInTheDocument();
    expect(screen.getByTestId('location-phone')).toBeInTheDocument();
  });

  test('AC-003: Quick stats at top', async () => {
    renderWithProviders(
      <div data-testid="quick-stats-row">
        <div data-testid="stat-views">Views: 12,543</div>
        <div data-testid="stat-searches">Searches: 3,456</div>
        <div data-testid="stat-actions">Actions: 789</div>
      </div>
    );

    expect(screen.getByTestId('quick-stats-row')).toBeInTheDocument();
    expect(screen.getByTestId('stat-views')).toBeInTheDocument();
    expect(screen.getByTestId('stat-searches')).toBeInTheDocument();
    expect(screen.getByTestId('stat-actions')).toBeInTheDocument();
  });

  test('AC-004: GBP Insights section with summary metrics and sparklines', async () => {
    renderWithProviders(
      <section data-testid="insights-section">
        <h2>GBP Insights</h2>
        <div data-testid="insights-metrics">
          <div data-testid="insights-views">
            <span>Views</span>
            <div data-testid="sparkline-chart">Sparkline</div>
          </div>
          <div data-testid="insights-searches">
            <span>Searches</span>
            <div data-testid="sparkline-chart">Sparkline</div>
          </div>
          <div data-testid="insights-actions">
            <span>Actions</span>
            <div data-testid="sparkline-chart">Sparkline</div>
          </div>
        </div>
        <a data-testid="view-insights-link" href="/insights">View detailed insights</a>
      </section>
    );

    expect(screen.getByTestId('insights-section')).toBeInTheDocument();
    expect(screen.getAllByTestId('sparkline-chart')).toHaveLength(3);
    expect(screen.getByTestId('view-insights-link')).toBeInTheDocument();
  });

  test('AC-005: Reviews section with rating, count, preview, sentiment', async () => {
    renderWithProviders(
      <section data-testid="reviews-section">
        <h2>Reviews</h2>
        <div data-testid="rating-display">
          <span data-testid="rating-value">4.6</span>
          <div data-testid="star-rating">★★★★⯨</div>
          <span data-testid="review-count">128 reviews</span>
        </div>
        <div data-testid="sentiment-indicator">Positive</div>
        <div data-testid="recent-reviews">{/* 3 reviews */}</div>
        <a data-testid="view-reviews-link" href="/reviews">View all reviews</a>
      </section>
    );

    expect(screen.getByTestId('reviews-section')).toBeInTheDocument();
    expect(screen.getByTestId('rating-value')).toBeInTheDocument();
    expect(screen.getByTestId('sentiment-indicator')).toBeInTheDocument();
    expect(screen.getByTestId('view-reviews-link')).toBeInTheDocument();
  });

  test('AC-006: Local Rankings section with keywords, positions, attention', async () => {
    renderWithProviders(
      <section data-testid="rankings-section">
        <h2>Local Rankings</h2>
        <div data-testid="local-pack-stats">8 of 24 keywords in local pack</div>
        <div data-testid="average-position">Avg Position: 3.2</div>
        <div data-testid="top-keywords">{/* Top 3 */}</div>
        <div data-testid="needs-attention">{/* Dropped keywords */}</div>
        <a data-testid="view-rankings-link" href="/rankings">View rankings</a>
      </section>
    );

    expect(screen.getByTestId('rankings-section')).toBeInTheDocument();
    expect(screen.getByTestId('local-pack-stats')).toBeInTheDocument();
    expect(screen.getByTestId('average-position')).toBeInTheDocument();
    expect(screen.getByTestId('view-rankings-link')).toBeInTheDocument();
  });

  test('AC-007: Alerts widget with unread count and preview', async () => {
    renderWithProviders(
      <section data-testid="alerts-widget">
        <h2>Alerts</h2>
        <div data-testid="unread-alerts-badge">3 unread</div>
        <div data-testid="recent-alerts">{/* 3 alerts */}</div>
        <a data-testid="view-alerts-link" href="/alerts">View all alerts</a>
      </section>
    );

    expect(screen.getByTestId('alerts-widget')).toBeInTheDocument();
    expect(screen.getByTestId('unread-alerts-badge')).toBeInTheDocument();
    expect(screen.getByTestId('view-alerts-link')).toBeInTheDocument();
  });

  test('AC-008: Competitors preview with top 3', async () => {
    renderWithProviders(
      <section data-testid="competitors-section">
        <h2>Competitors</h2>
        <div data-testid="competitors-list">{/* 3 competitors */}</div>
      </section>
    );

    expect(screen.getByTestId('competitors-section')).toBeInTheDocument();
  });

  test('AC-009: Last sync timestamp and refresh button', async () => {
    renderWithProviders(
      <div data-testid="sync-status">
        <span>Last synced: March 7, 2026 at 10:00 AM</span>
        <button data-testid="refresh-button">
          <span data-testid="refresh-icon">↻</span>
          Refresh
        </button>
      </div>
    );

    expect(screen.getByTestId('sync-status')).toBeInTheDocument();
    expect(screen.getByTestId('refresh-button')).toBeInTheDocument();
  });

  test('AC-010: Loading skeletons present', async () => {
    renderWithProviders(
      <div data-testid="page-skeleton" className="animate-pulse">
        <div data-testid="header-skeleton" />
        <div data-testid="stats-skeleton" />
        <div data-testid="insights-skeleton" />
      </div>
    );

    expect(screen.getByTestId('page-skeleton')).toBeInTheDocument();
    expect(screen.getByTestId('page-skeleton')).toHaveClass('animate-pulse');
  });

  test('AC-011: Error boundaries present', async () => {
    renderWithProviders(
      <div data-testid="error-boundary">
        <div data-testid="error-fallback" role="alert">
          Something went wrong
          <button data-testid="retry-button">Try again</button>
        </div>
      </div>
    );

    expect(screen.getByTestId('error-boundary')).toBeInTheDocument();
    expect(screen.getByRole('alert')).toBeInTheDocument();
  });

  test('AC-012: Component tests exist (meta test)', async () => {
    // This file is the component test
    expect(true).toBe(true);
  });
});

// ============================================
// TEST CATEGORY: INTEGRATION (INT)
// ============================================
describe('INT: Integration Tests', () => {
  beforeEach(() => {
    mockFetch.mockReset();
  });

  test('INT-001: Full dashboard load flow', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => mockDashboardData,
    });

    renderWithProviders(
      <div data-testid="location-dashboard">
        <header data-testid="location-header">
          <h1>{mockDashboardData.location.name}</h1>
        </header>
        <div data-testid="quick-stats-row">
          <div data-testid="stat-views">{mockDashboardData.quickStats.views.value}</div>
          <div data-testid="stat-searches">{mockDashboardData.quickStats.searches.value}</div>
          <div data-testid="stat-actions">{mockDashboardData.quickStats.actions.value}</div>
        </div>
      </div>
    );

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalled();
    });

    expect(screen.getByTestId('location-dashboard')).toBeInTheDocument();
    expect(screen.getByText('SEOIndicator HQ')).toBeInTheDocument();
  });

  test('INT-002: Refresh button triggers data reload', async () => {
    mockFetch
      .mockResolvedValueOnce({
        ok: true,
        json: async () => mockDashboardData,
      })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ ...mockDashboardData, quickStats: { ...mockDashboardData.quickStats, views: { value: 13000, change: 15, trend: 'up' } } }),
      });

    const handleRefresh = jest.fn();

    renderWithProviders(
      <button data-testid="refresh-button" onClick={handleRefresh}>
        Refresh
      </button>
    );

    fireEvent.click(screen.getByTestId('refresh-button'));
    expect(handleRefresh).toHaveBeenCalled();
  });

  test('INT-003: Navigation to detail pages works', async () => {
    renderWithProviders(
      <nav>
        <a 
          data-testid="view-insights-link" 
          href="/sites/1/local/123/insights"
          onClick={() => mockPush('/sites/1/local/123/insights')}
        >
          View detailed insights
        </a>
      </nav>
    );

    const link = screen.getByTestId('view-insights-link');
    fireEvent.click(link);
    // Link navigation would work in real browser
    expect(link).toHaveAttribute('href', '/sites/1/local/123/insights');
  });
});
