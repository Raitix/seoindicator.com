'use client';

import React, { useMemo, useState, useCallback } from 'react';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { LineChart as LineChartIcon, TrendingUp, TrendingDown } from 'lucide-react';
import { useBacklinkHistory } from '@/lib/api/backlinks';
import type { DateRange, BacklinkHistoryData } from './types';

// ==================== Types ====================

interface BacklinkHistoryChartProps {
  projectId: number;
  dateRange?: DateRange;
  onDateRangeChange?: (range: DateRange) => void;
  className?: string;
}

interface ChartDataPoint {
  date: string;
  total_backlinks: number;
  referring_domains_count: number;
  dofollow_count: number;
  nofollow_count: number;
  avg_domain_trust: number;
  anchor_diversity_score: number;
  displayDate: string;
  formattedBacklinks: string;
  formattedDomains: string;
  formattedTrust: string;
}

interface TooltipPayload {
  value?: number;
  dataKey?: string;
  payload?: ChartDataPoint;
}

type DateRangePreset = '7d' | '30d' | '90d' | '1y';

// ==================== Constants ====================

const DATE_RANGE_PRESETS: { label: string; value: DateRangePreset; days: number }[] = [
  { label: '7 days', value: '7d', days: 7 },
  { label: '30 days', value: '30d', days: 30 },
  { label: '90 days', value: '90d', days: 90 },
  { label: '1 year', value: '1y', days: 365 },
];

const COLORS = {
  backlinks: '#2563eb',
  domains: '#16a34a',
  trust: '#f59e0b',
};

// ==================== Helper Functions ====================

/**
 * Format number with K/M suffix
 */
function formatNumber(value: number | null | undefined): string {
  if (value === null || value === undefined) return '0';
  if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
  if (value >= 1000) return `${(value / 1000).toFixed(1)}K`;
  return value.toString();
}

/**
 * Format date for display
 */
function formatDate(dateStr: string): string {
  const date = new Date(dateStr);
  if (Number.isNaN(date.getTime())) return dateStr;
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
  }).format(date);
}

/**
 * Calculate date range from preset
 */
function getDateRangeFromPreset(preset: DateRangePreset): DateRange {
  const today = new Date();
  const days = DATE_RANGE_PRESETS.find(p => p.value === preset)?.days ?? 30;
  const from = new Date(today);
  from.setDate(today.getDate() - days);
  
  return {
    from: from.toISOString().split('T')[0],
    to: today.toISOString().split('T')[0],
  };
}

/**
 * Get preset from date range
 */
function getPresetFromDateRange(range: DateRange | undefined): DateRangePreset {
  if (!range?.from || !range?.to) return '30d';
  
  const from = new Date(range.from);
  const to = new Date(range.to);
  const days = Math.round((to.getTime() - from.getTime()) / (1000 * 60 * 60 * 24));
  
  if (days <= 7) return '7d';
  if (days <= 30) return '30d';
  if (days <= 90) return '90d';
  return '1y';
}

/**
 * Calculate growth rate
 */
function calculateGrowthRate(data: BacklinkHistoryData[]): number {
  if (data.length < 2) return 0;
  
  const first = data[0].total_backlinks;
  const last = data[data.length - 1].total_backlinks;
  
  if (first === 0) return 0;
  return ((last - first) / first) * 100;
}

// ==================== Skeleton Component ====================

function ChartSkeleton() {
  return (
    <div className="animate-pulse" data-testid="loading-skeleton">
      <div className="h-8 w-48 bg-muted rounded mb-4" />
      <div className="h-64 bg-muted rounded" />
    </div>
  );
}

// ==================== Empty State Component ====================

function EmptyState() {
  return (
    <div 
      className="flex flex-col items-center justify-center py-16 text-muted-foreground"
      data-testid="empty-state"
    >
      <LineChartIcon className="h-12 w-12 mb-4 opacity-50" />
      <p className="text-lg font-medium">No backlink history yet</p>
      <p className="text-sm mt-2">Data will accumulate as backlinks are discovered</p>
    </div>
  );
}

// ==================== Custom Tooltip Component ====================

interface CustomTooltipProps {
  active?: boolean;
  payload?: TooltipPayload[];
  label?: string;
}

function CustomTooltip({ active, payload }: CustomTooltipProps) {
  if (!active || !payload || payload.length === 0) return null;
  
  const data = payload[0]?.payload;
  if (!data) return null;
  
  const growthRate = calculateGrowthRate([data, data]);
  const isPositive = data.total_backlinks >= (data.total_backlinks || 0);
  
  return (
    <div 
      className="bg-background border rounded-lg shadow-lg p-3 min-w-[180px]"
      role="tooltip"
    >
      <p className="font-medium mb-2">{formatDate(data.date)}</p>
      <div className="space-y-1 text-sm">
        <div className="flex justify-between gap-4">
          <span className="text-muted-foreground">Backlinks:</span>
          <span className="font-medium" style={{ color: COLORS.backlinks }}>
            {formatNumber(data.total_backlinks)}
          </span>
        </div>
        <div className="flex justify-between gap-4">
          <span className="text-muted-foreground">Domains:</span>
          <span className="font-medium" style={{ color: COLORS.domains }}>
            {formatNumber(data.referring_domains_count)}
          </span>
        </div>
        <div className="flex justify-between gap-4">
          <span className="text-muted-foreground">Trust Score:</span>
          <span className="font-medium" style={{ color: COLORS.trust }}>
            {data.avg_domain_trust?.toFixed(1) ?? 'N/A'}
          </span>
        </div>
      </div>
      {isPositive !== undefined && (
        <div className="flex items-center gap-1 mt-2 pt-2 border-t text-xs">
          {isPositive ? (
            <TrendingUp className="h-3 w-3 text-green-500" />
          ) : (
            <TrendingDown className="h-3 w-3 text-red-500" />
          )}
          <span className={isPositive ? 'text-green-500' : 'text-red-500'}>
            {isPositive ? 'Growing' : 'Declining'}
          </span>
        </div>
      )}
    </div>
  );
}

// ==================== Main Component ====================

const BacklinkHistoryChart: React.FC<BacklinkHistoryChartProps> = ({
  projectId,
  dateRange: initialDateRange,
  onDateRangeChange,
  className,
}) => {
  // State
  const [selectedPreset, setSelectedPreset] = useState<DateRangePreset>(
    getPresetFromDateRange(initialDateRange)
  );
  
  // Calculate current date range
  const currentDateRange = useMemo(() => {
    if (initialDateRange?.from && initialDateRange?.to) {
      return initialDateRange;
    }
    return getDateRangeFromPreset(selectedPreset);
  }, [initialDateRange, selectedPreset]);
  
  // Fetch data
  const { data: historyData, isLoading, error } = useBacklinkHistory(projectId, currentDateRange);
  
  // Transform data for chart
  const chartData = useMemo<ChartDataPoint[]>(() => {
    return historyData.map((point) => ({
      ...point,
      displayDate: formatDate(point.date),
      formattedBacklinks: formatNumber(point.total_backlinks),
      formattedDomains: formatNumber(point.referring_domains_count),
      formattedTrust: point.avg_domain_trust?.toFixed(1) ?? 'N/A',
    }));
  }, [historyData]);
  
  // Calculate overall trend
  const trend = useMemo(() => {
    if (historyData.length < 2) return null;
    const rate = calculateGrowthRate(historyData);
    return {
      rate: rate.toFixed(1),
      isPositive: rate >= 0,
    };
  }, [historyData]);
  
  // Handle preset change
  const handlePresetChange = useCallback((preset: DateRangePreset) => {
    setSelectedPreset(preset);
    const newRange = getDateRangeFromPreset(preset);
    onDateRangeChange?.(newRange);
  }, [onDateRangeChange]);
  
  // Handle error
  if (error) {
    return (
      <Card className={className}>
        <CardContent className="py-8">
          <div className="text-center text-destructive">
            <p className="font-medium">Failed to load history</p>
            <p className="text-sm text-muted-foreground mt-1">
              {error instanceof Error ? error.message : 'Unknown error'}
            </p>
          </div>
        </CardContent>
      </Card>
    );
  }
  
  return (
    <Card className={className} aria-label="Backlink history chart showing trends over time">
      <CardHeader className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <CardTitle className="text-lg flex items-center gap-2">
            <LineChartIcon className="h-5 w-5" />
            Backlink History
            {trend && (
              <span 
                className={`text-sm font-normal flex items-center gap-1 ${
                  trend.isPositive ? 'text-green-500' : 'text-red-500'
                }`}
              >
                {trend.isPositive ? (
                  <TrendingUp className="h-4 w-4" />
                ) : (
                  <TrendingDown className="h-4 w-4" />
                )}
                {trend.isPositive ? '+' : ''}{trend.rate}%
              </span>
            )}
          </CardTitle>
          <p className="text-sm text-muted-foreground">
            Track backlinks, domains, and trust score over time
          </p>
        </div>
        
        {/* Date Range Selector */}
        <div 
          className="flex items-center gap-1 rounded-full border p-1"
          data-testid="date-range-selector"
          role="group"
          aria-label="Date range selection"
        >
          {DATE_RANGE_PRESETS.map((preset) => (
            <Button
              key={preset.value}
              type="button"
              variant={selectedPreset === preset.value ? 'default' : 'ghost'}
              size="sm"
              onClick={() => handlePresetChange(preset.value)}
              aria-pressed={selectedPreset === preset.value}
            >
              {preset.label}
            </Button>
          ))}
        </div>
      </CardHeader>
      
      <CardContent>
        {/* Loading State */}
        {isLoading && <ChartSkeleton />}
        
        {/* Empty State */}
        {!isLoading && chartData.length === 0 && <EmptyState />}
        
        {/* Chart */}
        {!isLoading && chartData.length > 0 && (
          <div 
            className="h-80"
            aria-live="polite"
          >
            <ResponsiveContainer width="100%" height="100%">
              <LineChart 
                data={chartData} 
                margin={{ top: 10, right: 24, left: 8, bottom: 24 }}
              >
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis
                  dataKey="displayDate"
                  tick={{ fontSize: 12 }}
                  className="text-muted-foreground"
                  angle={-35}
                  textAnchor="end"
                  height={50}
                />
                <YAxis
                  yAxisId="left"
                  tick={{ fontSize: 12 }}
                  className="text-muted-foreground"
                  domain={[0, 'auto']}
                  tickFormatter={(value) => formatNumber(value)}
                />
                <YAxis
                  yAxisId="right"
                  orientation="right"
                  tick={{ fontSize: 12 }}
                  className="text-muted-foreground"
                  domain={[0, 100]}
                  tickFormatter={(value) => value.toString()}
                />
                <Tooltip content={<CustomTooltip />} />
                <Legend />
                <Line
                  yAxisId="left"
                  type="monotone"
                  dataKey="total_backlinks"
                  stroke={COLORS.backlinks}
                  strokeWidth={2}
                  dot={false}
                  name="Backlinks"
                  activeDot={{ r: 4, fill: COLORS.backlinks }}
                  connectNulls
                />
                <Line
                  yAxisId="left"
                  type="monotone"
                  dataKey="referring_domains_count"
                  stroke={COLORS.domains}
                  strokeWidth={2}
                  dot={false}
                  name="Domains"
                  activeDot={{ r: 4, fill: COLORS.domains }}
                  connectNulls
                />
                <Line
                  yAxisId="right"
                  type="monotone"
                  dataKey="avg_domain_trust"
                  stroke={COLORS.trust}
                  strokeWidth={2}
                  dot={false}
                  name="Trust Score"
                  activeDot={{ r: 4, fill: COLORS.trust }}
                  connectNulls
                />
              </LineChart>
            </ResponsiveContainer>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default BacklinkHistoryChart;

// Named export for flexibility
export { BacklinkHistoryChart };
