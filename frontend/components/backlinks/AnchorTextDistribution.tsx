'use client';

import React, { useMemo } from 'react';
import {
  PieChart,
  Pie,
  Cell,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  PieChart as PieChartIcon,
  BarChart3,
  Type,
  ExternalLink,
  ChevronRight,
} from 'lucide-react';
import type { AnchorText } from './types';

// ==================== Types ====================

interface AnchorTextDistributionProps {
  anchors: AnchorText[];
  loading?: boolean;
  className?: string;
  chartType?: 'pie' | 'bar';
  onChartTypeChange?: (type: 'pie' | 'bar') => void;
  maxVisible?: number;
}

// ==================== Constants ====================

const COLORS = [
  '#2563eb', // blue
  '#16a34a', // green
  '#f59e0b', // amber
  '#dc2626', // red
  '#8b5cf6', // violet
  '#06b6d4', // cyan
  '#ec4899', // pink
  '#84cc16', // lime
  '#f97316', // orange
  '#6366f1', // indigo
];

const ANCHOR_TYPES = {
  branded: { label: 'Branded', color: '#16a34a' },
  exact_match: { label: 'Exact Match', color: '#dc2626' },
  partial_match: { label: 'Partial Match', color: '#f59e0b' },
  generic: { label: 'Generic', color: '#6b7280' },
  other: { label: 'Other', color: '#8b5cf6' },
};

// ==================== Helper Functions ====================

/**
 * Format number with K/M suffix
 */
function formatNumber(value: number): string {
  if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
  if (value >= 1000) return `${(value / 1000).toFixed(1)}K`;
  return value.toString();
}

/**
 * Truncate text with ellipsis
 */
function truncateText(text: string, maxLength: number): string {
  if (text.length <= maxLength) return text;
  return `${text.slice(0, maxLength)}...`;
}

/**
 * Get anchor type based on flags
 */
function getAnchorType(anchor: AnchorText): keyof typeof ANCHOR_TYPES {
  if (anchor.is_branded) return 'branded';
  if (anchor.is_exact_match) return 'exact_match';
  return 'other';
}

/**
 * Calculate anchor type distribution
 */
function calculateTypeDistribution(anchors: AnchorText[]) {
  const types: Record<keyof typeof ANCHOR_TYPES, number> = {
    branded: 0,
    exact_match: 0,
    partial_match: 0,
    generic: 0,
    other: 0,
  };

  anchors.forEach((anchor) => {
    const type = getAnchorType(anchor);
    types[type] += anchor.backlinks_count;
  });

  return Object.entries(types)
    .filter(([, count]) => count > 0)
    .map(([type, count]) => ({
      type,
      label: ANCHOR_TYPES[type as keyof typeof ANCHOR_TYPES].label,
      value: count,
      color: ANCHOR_TYPES[type as keyof typeof ANCHOR_TYPES].color,
    }));
}

// ==================== Skeleton Component ====================

function ChartSkeleton() {
  return (
    <div className="animate-pulse" data-testid="chart-skeleton">
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
      <Type className="h-12 w-12 mb-4 opacity-50" />
      <p className="text-lg font-medium">No anchor text data</p>
      <p className="text-sm mt-2">Anchor text distribution will appear after analysis</p>
    </div>
  );
}

// ==================== Custom Tooltip Component ====================

interface CustomTooltipProps {
  active?: boolean;
  payload?: Array<{
    payload?: {
      anchor_text?: string;
      backlinks_count?: number;
      percentage?: number;
      type?: string;
      name?: string;
      value?: number;
      color?: string;
    };
  }>;
}

function CustomTooltip({ active, payload }: CustomTooltipProps) {
  if (!active || !payload || payload.length === 0) return null;

  const data = payload[0]?.payload;
  if (!data) return null;

  // For pie chart
  if (data.anchor_text !== undefined) {
    return (
      <div
        className="bg-background border rounded-lg shadow-lg p-3 min-w-[180px]"
        role="tooltip"
      >
        <p className="font-medium mb-1 truncate" title={data.anchor_text}>
          {truncateText(data.anchor_text ?? '', 30)}
        </p>
        <div className="space-y-1 text-sm">
          <div className="flex justify-between gap-4">
            <span className="text-muted-foreground">Backlinks:</span>
            <span className="font-medium">{formatNumber(data.backlinks_count ?? 0)}</span>
          </div>
          <div className="flex justify-between gap-4">
            <span className="text-muted-foreground">Share:</span>
            <span className="font-medium">{(data.percentage ?? 0).toFixed(1)}%</span>
          </div>
        </div>
      </div>
    );
  }

  // For type distribution
  return (
    <div
      className="bg-background border rounded-lg shadow-lg p-3 min-w-[150px]"
      role="tooltip"
    >
      <p className="font-medium mb-1">{data.name}</p>
      <div className="text-sm">
        <span className="font-medium">{formatNumber(data.value ?? 0)}</span>
        <span className="text-muted-foreground ml-1">backlinks</span>
      </div>
    </div>
  );
}

// ==================== Anchor List Component ====================

interface AnchorListProps {
  anchors: AnchorText[];
  maxVisible?: number;
}

const AnchorList: React.FC<AnchorListProps> = ({ anchors, maxVisible = 10 }) => {
  const visibleAnchors = anchors.slice(0, maxVisible);

  return (
    <div className="space-y-2 mt-4" data-testid="anchor-list">
      {visibleAnchors.map((anchor, index) => (
        <div
          key={`${anchor.anchor_text}-${index}`}
          className="flex items-center justify-between py-2 border-b last:border-0"
        >
          <div className="flex items-center gap-2 min-w-0 flex-1">
            <div
              className="w-3 h-3 rounded-full flex-shrink-0"
              style={{ backgroundColor: COLORS[index % COLORS.length] }}
            />
            <span
              className="text-sm truncate"
              title={anchor.anchor_text}
            >
              {truncateText(anchor.anchor_text, 40)}
            </span>
            {anchor.is_branded && (
              <Badge variant="secondary" className="text-xs flex-shrink-0">
                Branded
              </Badge>
            )}
            {anchor.is_exact_match && (
              <Badge variant="destructive" className="text-xs flex-shrink-0">
                Exact
              </Badge>
            )}
          </div>
          <div className="flex items-center gap-2 flex-shrink-0 ml-2">
            <span className="text-sm font-medium">
              {formatNumber(anchor.backlinks_count)}
            </span>
            <span className="text-xs text-muted-foreground">
              ({anchor.percentage.toFixed(1)}%)
            </span>
          </div>
        </div>
      ))}
      {anchors.length > maxVisible && (
        <Button variant="ghost" size="sm" className="w-full mt-2">
          View all {anchors.length} anchors
          <ChevronRight className="h-4 w-4 ml-1" />
        </Button>
      )}
    </div>
  );
};

// ==================== Main Component ====================

const AnchorTextDistribution: React.FC<AnchorTextDistributionProps> = ({
  anchors,
  loading = false,
  className,
  chartType = 'bar',
  onChartTypeChange,
  maxVisible = 10,
}) => {
  // Calculate type distribution
  const typeDistribution = useMemo(
    () => calculateTypeDistribution(anchors),
    [anchors]
  );

  // Prepare chart data
  const chartData = useMemo(
    () =>
      anchors.slice(0, maxVisible).map((anchor, index) => ({
        ...anchor,
        name: truncateText(anchor.anchor_text, 20),
        fill: COLORS[index % COLORS.length],
      })),
    [anchors, maxVisible]
  );

  // Loading state
  if (loading) {
    return (
      <Card className={className} data-testid="anchor-text-distribution-loading">
        <CardContent className="pt-6">
          <ChartSkeleton />
        </CardContent>
      </Card>
    );
  }

  // Empty state
  if (!loading && anchors.length === 0) {
    return (
      <Card className={className} data-testid="anchor-text-distribution-empty">
        <CardContent className="pt-6">
          <EmptyState />
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={className} data-testid="anchor-text-distribution">
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="text-lg flex items-center gap-2">
            <Type className="h-5 w-5" />
            Anchor Text Distribution
          </CardTitle>
          <div className="flex items-center gap-1">
            <Button
              variant={chartType === 'pie' ? 'default' : 'ghost'}
              size="sm"
              onClick={() => onChartTypeChange?.('pie')}
              aria-label="Pie chart view"
            >
              <PieChartIcon className="h-4 w-4" />
            </Button>
            <Button
              variant={chartType === 'bar' ? 'default' : 'ghost'}
              size="sm"
              onClick={() => onChartTypeChange?.('bar')}
              aria-label="Bar chart view"
            >
              <BarChart3 className="h-4 w-4" />
            </Button>
          </div>
        </div>
        <p className="text-sm text-muted-foreground">
          Distribution of anchor texts in your backlink profile
        </p>
      </CardHeader>
      <CardContent>
        {/* Charts */}
        <div className="grid gap-6 md:grid-cols-2">
          {/* Main Chart */}
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              {chartType === 'pie' ? (
                <PieChart>
                  <Pie
                    data={chartData}
                    dataKey="backlinks_count"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    outerRadius={80}
                    innerRadius={40}
                    paddingAngle={2}
                  >
                    {chartData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.fill} />
                    ))}
                  </Pie>
                  <Tooltip content={<CustomTooltip />} />
                  <Legend />
                </PieChart>
              ) : (
                <BarChart data={chartData} layout="vertical">
                  <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                  <XAxis type="number" tickFormatter={(v) => formatNumber(v)} />
                  <YAxis
                    type="category"
                    dataKey="name"
                    width={100}
                    tick={{ fontSize: 11 }}
                  />
                  <Tooltip content={<CustomTooltip />} />
                  <Bar dataKey="backlinks_count" radius={[0, 4, 4, 0]}>
                    {chartData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.fill} />
                    ))}
                  </Bar>
                </BarChart>
              )}
            </ResponsiveContainer>
          </div>

          {/* Type Distribution */}
          <div>
            <h4 className="text-sm font-medium mb-3">Type Overview</h4>
            <div className="space-y-2">
              {typeDistribution.map((type) => (
                <div key={type.type} className="flex items-center gap-2">
                  <div
                    className="w-3 h-3 rounded-full"
                    style={{ backgroundColor: type.color }}
                  />
                  <span className="text-sm flex-1">{type.label}</span>
                  <span className="text-sm font-medium">
                    {formatNumber(type.value)}
                  </span>
                </div>
              ))}
            </div>

            {/* Anchor List */}
            <AnchorList anchors={anchors} maxVisible={5} />
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default AnchorTextDistribution;

// Named export for flexibility
export { AnchorTextDistribution };
