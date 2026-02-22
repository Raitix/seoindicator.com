'use client';

import React, { useState, useEffect } from 'react';
import GSCMetricsTable from './GSCMetricsTable';
import GSCDateFilter from './GSCDateFilter';
import GSCTrendChart from './GSCTrendChart';
import GSCOpportunities from './GSCOpportunities';
import {
  fetchGSCData,
  fetchGSCTrend,
  fetchGSCOpportunities,
  getDefaultDateRange,
  formatNumber,
} from '@/lib/api/gsc';
import { DateRange, GSCMetric, GSCTrendData, GSCOpportunityKeyword } from '@/lib/types/gsc';
import { TrendingUp, Eye, Target, BarChart3 } from 'lucide-react';

interface GSCDataDashboardProps {
  projectId: number;
  initialDateRange?: DateRange;
}

// Summary card component
interface SummaryCardProps {
  title: string;
  value: string | number;
  icon: React.ReactNode;
}

const SummaryCard: React.FC<SummaryCardProps> = ({ title, value, icon }) => (
  <div className="bg-card border rounded-lg p-4">
    <div className="flex items-center justify-between">
      <div>
        <p className="text-sm text-muted-foreground">{title}</p>
        <p className="text-2xl font-bold">{value}</p>
      </div>
      <div className="rounded-full bg-muted p-2">{icon}</div>
    </div>
  </div>
);

const GSCDataDashboard: React.FC<GSCDataDashboardProps> = ({
  projectId,
  initialDateRange,
}) => {
  const [dateRange, setDateRange] = useState<DateRange>(
    initialDateRange || getDefaultDateRange()
  );
  const [metricsData, setMetricsData] = useState<GSCMetric[]>([]);
  const [trendData, setTrendData] = useState<GSCTrendData[]>([]);
  const [opportunities, setOpportunities] = useState<GSCOpportunityKeyword[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Fetch data when date range changes
  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      setError(null);

      try {
        const [metricsResponse, trendResponse] = await Promise.all([
          fetchGSCData(projectId, dateRange),
          fetchGSCTrend(projectId, dateRange),
        ]);

        setMetricsData(metricsResponse.data);
        setTrendData(trendResponse.data);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to fetch GSC data');
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, [projectId, dateRange]);

  // Fetch opportunities separately (not date-dependent)
  useEffect(() => {
    const fetchOpportunitiesData = async () => {
      try {
        const response = await fetchGSCOpportunities(projectId);
        setOpportunities(response.data);
      } catch (err) {
        console.error('Failed to fetch opportunities:', err);
      }
    };

    fetchOpportunitiesData();
  }, [projectId]);

  // Calculate summary stats
  const totalClicks = metricsData.reduce((sum, item) => sum + item.clicks, 0);
  const totalImpressions = metricsData.reduce((sum, item) => sum + item.impressions, 0);
  const avgCTR =
    metricsData.length > 0
      ? ((metricsData.reduce((sum, item) => sum + item.ctr, 0) / metricsData.length) * 100).toFixed(2)
      : '0.00';
  const avgPosition =
    metricsData.length > 0
      ? (metricsData.reduce((sum, item) => sum + item.avg_position, 0) / metricsData.length).toFixed(1)
      : '-';

  return (
    <div className="space-y-6">
      {/* Error State */}
      {error && (
        <div className="bg-destructive/10 text-destructive rounded-lg p-4">
          <p className="font-medium">Error: {error}</p>
        </div>
      )}

      {/* Date Filter */}
      <GSCDateFilter
        dateRange={dateRange}
        onDateRangeChange={setDateRange}
        isLoading={isLoading}
      />

      {/* Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <SummaryCard
          title="Total Clicks"
          value={formatNumber(totalClicks)}
          icon={<TrendingUp className="h-4 w-4 text-primary" />}
        />
        <SummaryCard
          title="Total Impressions"
          value={formatNumber(totalImpressions)}
          icon={<Eye className="h-4 w-4 text-purple-500" />}
        />
        <SummaryCard
          title="Avg CTR"
          value={`${avgCTR}%`}
          icon={<Target className="h-4 w-4 text-green-500" />}
        />
        <SummaryCard
          title="Avg Position"
          value={avgPosition}
          icon={<BarChart3 className="h-4 w-4 text-yellow-500" />}
        />
      </div>

      {/* Trend Chart */}
      <GSCTrendChart data={trendData} isLoading={isLoading} />

      {/* Metrics Table */}
      <GSCMetricsTable data={metricsData} isLoading={isLoading} />

      {/* Opportunities */}
      <GSCOpportunities data={opportunities} isLoading={isLoading} />
    </div>
  );
};

export default GSCDataDashboard;
