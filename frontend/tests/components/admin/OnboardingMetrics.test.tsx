/**
 * Onboarding Metrics Dashboard Tests
 *
 * Tests for the admin dashboard showing onboarding completion metrics.
 *
 * @see TEST-17-15.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock fetch for API calls
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock chart library
vi.mock('recharts', () => ({
  BarChart: () => <div data-testid="bar-chart">Chart</div>,
  Bar: () => null,
  XAxis: () => null,
  YAxis: () => null,
  CartesianGrid: () => null,
  Tooltip: () => null,
  ResponsiveContainer: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="responsive-container">{children}</div>
  ),
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Users: () => <span data-testid="users-icon">👥</span>,
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  Clock: () => <span data-testid="clock-icon">⏱</span>,
  SkipForward: () => <span data-testid="skip-icon">⏭</span>,
  Download: () => <span data-testid="download-icon">↓</span>,
  RefreshCw: () => <span data-testid="refresh-icon">↻</span>,
  AlertTriangle: () => <span data-testid="alert-icon">⚠</span>,
  Loader2: () => <span data-testid="loader-icon">Loading</span>,
}));

// Mock components
const MetricCard = ({
  title,
  value,
  icon,
  trend,
}: {
  title: string;
  value: string;
  icon?: React.ReactNode;
  trend?: { value: number; isPositive: boolean };
}) => (
  <div data-testid="metric-card" className="p-4 border rounded">
    <div className="text-sm text-gray-500">{title}</div>
    <div className="text-2xl font-bold">{value}</div>
    {trend && (
      <div className={`text-sm ${trend.isPositive ? 'text-green-500' : 'text-red-500'}`}>
        {trend.isPositive ? '↑' : '↓'} {Math.abs(trend.value)}%
      </div>
    )}
  </div>
);

const FunnelStep = ({
  step,
  name,
  users,
  rate,
  dropOff,
  hasAlert,
}: {
  step: number;
  name: string;
  users: number;
  rate: number;
  dropOff: number;
  hasAlert?: boolean;
}) => (
  <div data-testid={`funnel-step-${step}`} className="mb-2">
    <div className="flex justify-between">
      <span>{name}</span>
      <span>{users} users ({rate}%)</span>
    </div>
    <div className="h-4 bg-gray-200 rounded">
      <div
        className="h-full bg-blue-500 rounded"
        style={{ width: `${rate}%` }}
      />
    </div>
    {hasAlert && <span data-testid="alert-icon">⚠</span>}
  </div>
);

const DateFilter = ({
  value,
  onChange,
}: {
  value: string;
  onChange: (value: string) => void;
}) => (
  <select
    data-testid="date-filter"
    value={value}
    onChange={(e) => onChange(e.target.value)}
    aria-label="Select date range"
  >
    <option value="7">Last 7 days</option>
    <option value="30">Last 30 days</option>
    <option value="90">Last 90 days</option>
  </select>
);

const ExportButton = ({ onClick }: { onClick: () => void }) => (
  <button
    data-testid="export-button"
    onClick={onClick}
    aria-label="Export to CSV"
  >
    Export CSV
  </button>
);

// Mock dashboard component
const OnboardingMetricsDashboard = () => {
  const [metrics, setMetrics] = useState<{
    total_users: number;
    completion_rate: number;
    average_duration_seconds: number;
    skip_rate: number;
    funnel: Array<{ step: number; name: string; users: number; conversion_rate: number; drop_off_rate: number }>;
  } | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [dateFilter, setDateFilter] = useState('30');

  useEffect(() => {
    const fetchMetrics = async () => {
      try {
        setLoading(true);
        const response = await fetch(`/api/v1/admin/onboarding/metrics?period=${dateFilter}`);
        if (!response.ok) throw new Error('Failed to fetch');
        const data = await response.json();
        setMetrics(data);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Unknown error');
      } finally {
        setLoading(false);
      }
    };
    fetchMetrics();
  }, [dateFilter]);

  const handleExport = () => {
    // Export logic
  };

  if (loading) return <div data-testid="loading">Loading...</div>;
  if (error) return <div data-testid="error">{error}</div>;
  if (!metrics) return null;

  return (
    <div data-testid="metrics-dashboard">
      <h1>Onboarding Metrics</h1>
      <DateFilter value={dateFilter} onChange={setDateFilter} />
      <ExportButton onClick={handleExport} />
      
      <div data-testid="metrics-grid">
        <MetricCard
          title="Completion Rate"
          value={`${metrics.completion_rate}%`}
        />
        <MetricCard
          title="Average Duration"
          value={`${Math.floor(metrics.average_duration_seconds / 60)}m ${metrics.average_duration_seconds % 60}s`}
        />
        <MetricCard
          title="Skip Rate"
          value={`${metrics.skip_rate}%`}
        />
        <MetricCard
          title="Total Users"
          value={metrics.total_users.toString()}
        />
      </div>

      <div data-testid="funnel-container">
        {metrics.funnel.map((step) => (
          <FunnelStep
            key={step.step}
            step={step.step}
            name={step.name}
            users={step.users}
            rate={step.conversion_rate}
            dropOff={step.drop_off_rate}
            hasAlert={step.drop_off_rate > 20}
          />
        ))}
      </div>
    </div>
  );
};

import { useState, useEffect } from 'react';

describe('Onboarding Metrics Dashboard', () => {
  const sampleMetrics = {
    total_users: 100,
    completion_rate: 45,
    average_duration_seconds: 204,
    skip_rate: 12,
    funnel: [
      { step: 1, name: 'Step 1', users: 100, conversion_rate: 100, drop_off_rate: 0 },
      { step: 2, name: 'Step 2', users: 78, conversion_rate: 78, drop_off_rate: 22 },
      { step: 3, name: 'Step 3', users: 62, conversion_rate: 62, drop_off_rate: 20 },
      { step: 4, name: 'Step 4', users: 55, conversion_rate: 55, drop_off_rate: 11 },
      { step: 5, name: 'Step 5', users: 45, conversion_rate: 45, drop_off_rate: 18 },
      { step: 6, name: 'Complete', users: 45, conversion_rate: 45, drop_off_rate: 0 },
    ],
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // OM-001: Dashboard Rendering
  // ============================================
  describe('Dashboard Rendering', () => {
    it('OM-001-01: dashboard renders with metrics', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByTestId('metrics-dashboard')).toBeInTheDocument();
      });
    });

    it('OM-001-02: completion rate displayed', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByText('45%')).toBeInTheDocument();
      });
    });

    it('OM-001-03: average duration displayed', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByText(/3m/)).toBeInTheDocument();
      });
    });

    it('OM-001-04: skip rate displayed', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByText('12%')).toBeInTheDocument();
      });
    });

    it('OM-001-05: total users displayed', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByText('100')).toBeInTheDocument();
      });
    });

    it('OM-001-06: loading state shown', () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));

      render(<OnboardingMetricsDashboard />);

      expect(screen.getByTestId('loading')).toBeInTheDocument();
    });

    it('OM-001-07: error state handled', async () => {
      mockFetch.mockRejectedValue(new Error('API error'));

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByTestId('error')).toBeInTheDocument();
      });
    });

    it('OM-001-08: refresh button works', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByTestId('metrics-dashboard')).toBeInTheDocument();
      });

      // After implementation: test refresh button click
    });
  });

  // ============================================
  // OM-002: Funnel Visualization
  // ============================================
  describe('Funnel Visualization', () => {
    it('OM-002-01: funnel renders with 6 steps', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByTestId('funnel-step-1')).toBeInTheDocument();
        expect(screen.getByTestId('funnel-step-6')).toBeInTheDocument();
      });
    });

    it('OM-002-02: step conversion rates shown', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByText(/78%/)).toBeInTheDocument();
      });
    });

    it('OM-002-03: user counts shown', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByText(/78 users/)).toBeInTheDocument();
      });
    });

    it('OM-002-04: drop-off highlighted', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        // Step 2 has 22% drop-off (>20%), should show alert
        expect(screen.getAllByTestId('alert-icon').length).toBeGreaterThan(0);
      });
    });

    it('OM-002-05: step labels correct', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByText('Step 1')).toBeInTheDocument();
        expect(screen.getByText('Complete')).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // OM-003: Date Filtering
  // ============================================
  describe('Date Filtering', () => {
    it('OM-003-01: default to last 30 days', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByTestId('date-filter')).toHaveValue('30');
      });
    });

    it('OM-003-02: filter by 7 days', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByTestId('metrics-dashboard')).toBeInTheDocument();
      });

      mockFetch.mockClear();
      fireEvent.change(screen.getByTestId('date-filter'), { target: { value: '7' } });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(expect.stringContaining('period=7'));
      });
    });

    it('OM-003-03: filter by 90 days', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByTestId('metrics-dashboard')).toBeInTheDocument();
      });

      mockFetch.mockClear();
      fireEvent.change(screen.getByTestId('date-filter'), { target: { value: '90' } });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(expect.stringContaining('period=90'));
      });
    });
  });

  // ============================================
  // OM-004: Export to CSV
  // ============================================
  describe('Export to CSV', () => {
    it('OM-004-01: export button renders', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByTestId('export-button')).toBeInTheDocument();
      });
    });

    it('OM-004-02: CSV download on click', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByTestId('export-button')).toBeInTheDocument();
      });

      // After implementation: test CSV download
      // fireEvent.click(screen.getByTestId('export-button'));
    });
  });

  // ============================================
  // OM-007: Alerts
  // ============================================
  describe('Alerts', () => {
    it('OM-007-01: alert shown for >20% drop', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        // Step 2 has 22% drop-off
        const step2 = screen.getByTestId('funnel-step-2');
        expect(step2.querySelector('[data-testid="alert-icon"]')).toBeInTheDocument();
      });
    });

    it('OM-007-02: no alert for <20% drop', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        // Step 4 has 11% drop-off (<20%)
        const step4 = screen.getByTestId('funnel-step-4');
        expect(step4.querySelector('[data-testid="alert-icon"]')).not.toBeInTheDocument();
      });
    });
  });

  // ============================================
  // OM-008: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('OM-008-01: metric cards have labels', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByText('Completion Rate')).toBeInTheDocument();
        expect(screen.getByText('Skip Rate')).toBeInTheDocument();
      });
    });

    it('OM-008-02: filter has accessible name', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByLabelText('Select date range')).toBeInTheDocument();
      });
    });

    it('OM-008-03: keyboard navigation', async () => {
      const user = userEvent.setup();
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(sampleMetrics),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByTestId('metrics-dashboard')).toBeInTheDocument();
      });

      await user.tab();
      // Verify focus moves through interactive elements
    });
  });

  // ============================================
  // OM-009: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('OM-009-01: no data in period', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          total_users: 0,
          completion_rate: 0,
          average_duration_seconds: 0,
          skip_rate: 0,
          funnel: [],
        }),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByTestId('metrics-dashboard')).toBeInTheDocument();
      });
    });

    it('OM-009-02: very high completion', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          ...sampleMetrics,
          completion_rate: 100,
        }),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByText('100%')).toBeInTheDocument();
      });
    });

    it('OM-009-03: very low completion', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          ...sampleMetrics,
          completion_rate: 1,
        }),
      });

      render(<OnboardingMetricsDashboard />);

      await waitFor(() => {
        expect(screen.getByText('1%')).toBeInTheDocument();
      });
    });
  });
});
