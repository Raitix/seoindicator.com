/**
 * TASK-34-16: QueryCategoriesChart Component
 * 
 * Pie chart showing distribution of query categories.
 * Uses Recharts for rendering.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-16.md
 */

'use client';

import * as React from 'react';
import {
  PieChart,
  Pie,
  Cell,
  Tooltip,
  ResponsiveContainer,
  TooltipProps,
} from 'recharts';
import { cn } from '@/lib/utils';
import type { QueryCategoriesChartProps, QueryCategoryItem } from '@/lib/types/ai-visibility';

// ============================================
// Colors
// ============================================

const COLORS = [
  '#3b82f6', // Blue - comparison
  '#10b981', // Emerald - recommendation
  '#f59e0b', // Amber - feature
  '#8b5cf6', // Violet - use_case
];

const CATEGORY_LABELS: Record<string, string> = {
  comparison: 'Comparison',
  recommendation: 'Recommendation',
  feature: 'Feature',
  use_case: 'Use Case',
};

// ============================================
// Custom Tooltip Component
// ============================================

interface CustomTooltipProps extends TooltipProps<number, string> {
  active?: boolean;
  payload?: Array<{
    value: number;
    payload: QueryCategoryItem & { percent: number };
  }>;
}

function CustomTooltip({ active, payload }: CustomTooltipProps) {
  if (!active || !payload || payload.length === 0) {
    return null;
  }

  const item = payload[0].payload;
  const label = CATEGORY_LABELS[item.name] || item.name;

  return (
    <div className="rounded-lg bg-slate-800 border border-slate-700 p-3 shadow-lg">
      <p className="text-sm font-medium text-white">{label}</p>
      <p className="text-lg font-bold text-blue-400">{item.value} queries</p>
      <p className="text-xs text-slate-400">{(item.percent * 100).toFixed(0)}% of total</p>
    </div>
  );
}

// ============================================
// Chart Component
// ============================================

export function QueryCategoriesChart({ data, className }: QueryCategoriesChartProps) {
  // Empty state
  if (!data || data.length === 0) {
    return (
      <div
        data-testid="chart-empty-state"
        className={cn(
          'flex flex-col items-center justify-center rounded-lg bg-slate-800 p-8',
          className
        )}
      >
        <p className="text-slate-400">No category data available</p>
        <p className="mt-1 text-sm text-slate-500">
          Run queries to see category distribution
        </p>
      </div>
    );
  }

  // Calculate total for percentages
  const total = data.reduce((sum, item) => sum + item.value, 0);

  // Helper to calculate percentage
  const calculatePercentage = (value: number, total: number): string => {
    if (total === 0) return '0';
    return ((value / total) * 100).toFixed(0);
  };

  return (
    <div data-testid="query-categories" className={className}>
      {/* Chart */}
      <div data-testid="pie-chart">
        <ResponsiveContainer width="100%" height={250}>
          <PieChart>
            <Pie
              data={data}
              cx="50%"
              cy="50%"
              innerRadius={60}
              outerRadius={100}
              paddingAngle={2}
              dataKey="value"
              data-testid="pie"
              data-segments={data.length}
            >
              {data.map((entry, index) => (
                <Cell
                  key={`cell-${index}`}
                  fill={COLORS[index % COLORS.length]}
                  data-testid={`pie-segment-${entry.name}`}
                  data-value={entry.value}
                />
              ))}
            </Pie>
            <Tooltip content={<CustomTooltip />} />
          </PieChart>
        </ResponsiveContainer>
      </div>

      {/* Legend */}
      <div data-testid="chart-legend" className="mt-4 grid grid-cols-2 gap-2">
        {data.map((item, index) => {
          const label = CATEGORY_LABELS[item.name] || item.name;
          const percentage = calculatePercentage(item.value, total);

          return (
            <div
              key={item.name}
              data-testid={`legend-${item.name}`}
              className="flex items-center gap-2"
            >
              <span
                data-testid={`legend-color-${item.name}`}
                className="h-3 w-3 rounded"
                style={{ backgroundColor: COLORS[index % COLORS.length] }}
              />
              <span className="text-sm text-slate-400">
                {label} ({percentage}%)
              </span>
            </div>
          );
        })}
      </div>

      {/* Stats */}
      <div data-testid="chart-stats" className="mt-4 pt-4 border-t border-slate-700">
        <div data-testid="total-queries" className="flex items-center justify-between">
          <span className="text-sm text-slate-500">Total Queries</span>
          <span data-testid="total-value" className="text-lg font-bold text-white">{total}</span>
        </div>
      </div>
    </div>
  );
}

export default QueryCategoriesChart;
