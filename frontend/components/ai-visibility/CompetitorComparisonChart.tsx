/**
 * TASK-34-16: CompetitorComparisonChart Component
 * 
 * Bar chart comparing brand vs competitors for share of voice.
 * Uses Recharts for rendering.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-16.md
 */

'use client';

import * as React from 'react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  TooltipProps,
  Cell,
} from 'recharts';
import { cn } from '@/lib/utils';
import type { CompetitorComparisonChartProps, CompetitorComparisonItem } from '@/lib/types/ai-visibility';

// ============================================
// Colors
// ============================================

const BRAND_COLOR = '#3b82f6'; // Blue
const COMPETITOR_COLOR = '#94a3b8'; // Slate

// ============================================
// Custom Tooltip Component
// ============================================

interface CustomTooltipProps extends TooltipProps<number, string> {
  active?: boolean;
  payload?: Array<{
    value: number;
    payload: CompetitorComparisonItem;
  }>;
  label?: string;
}

function CustomTooltip({ active, payload, label }: CustomTooltipProps) {
  if (!active || !payload || payload.length === 0) {
    return null;
  }

  const item = payload[0].payload;

  return (
    <div className="rounded-lg bg-slate-800 border border-slate-700 p-3 shadow-lg">
      <p className="text-sm font-medium text-white">{item.name}</p>
      <p className="text-lg font-bold text-blue-400">{item.value.toFixed(1)}%</p>
      {item.isBrand && (
        <p className="text-xs text-emerald-400 mt-1">Your brand</p>
      )}
    </div>
  );
}

// ============================================
// Chart Component
// ============================================

export function CompetitorComparisonChart({
  data,
  onBarClick,
  className,
}: CompetitorComparisonChartProps) {
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
        <p className="text-slate-400">No competitor data available</p>
        <p className="mt-1 text-sm text-slate-500">
          Add competitors to your project to see comparison
        </p>
      </div>
    );
  }

  // Sort data by value descending
  const sortedData = [...data].sort((a, b) => b.value - a.value);

  return (
    <div data-testid="competitor-comparison" className={className}>
      {/* Chart */}
      <ResponsiveContainer width="100%" height={300}>
          <BarChart
            data={sortedData}
            layout="vertical"
            margin={{ top: 5, right: 30, left: 80, bottom: 5 }}
          >
            <CartesianGrid strokeDasharray="3 3" stroke="#334155" horizontal={true} vertical={false} />
            <XAxis
              type="number"
              domain={[0, 100]}
              stroke="#64748b"
              tick={{ fill: '#94a3b8', fontSize: 12 }}
              tickFormatter={(value) => `${value}%`}
            />
            <YAxis
              type="category"
              dataKey="name"
              stroke="#64748b"
              tick={{ fill: '#94a3b8', fontSize: 12 }}
              width={70}
            />
            <Tooltip content={<CustomTooltip />} />
            <Bar
              dataKey="value"
              radius={[0, 4, 4, 0]}
              className="cursor-pointer"
              onClick={(data) => onBarClick?.(data.payload)}
            >
              {sortedData.map((entry, index) => (
                <Cell
                  key={`cell-${index}`}
                  fill={entry.isBrand ? BRAND_COLOR : COMPETITOR_COLOR}
                  data-testid={entry.isBrand ? 'bar-brand' : `bar-competitor-${index}`}
                />
              ))}
            </Bar>
          </BarChart>
        </ResponsiveContainer>

      {/* Legend */}
      <div data-testid="chart-legend" className="mt-4 flex items-center justify-center gap-6">
        <div data-testid="legend-brand" className="flex items-center gap-2">
          <span data-testid="legend-color-brand" className="h-3 w-3 rounded bg-blue-500" style={{ backgroundColor: BRAND_COLOR }} />
          <span className="text-sm text-slate-400">Your Brand</span>
        </div>
        <div data-testid="legend-competitors" className="flex items-center gap-2">
          <span data-testid="legend-color-competitors" className="h-3 w-3 rounded bg-slate-400" style={{ backgroundColor: COMPETITOR_COLOR }} />
          <span className="text-sm text-slate-400">Competitors</span>
        </div>
      </div>

      {/* Details Grid */}
      <div data-testid="details-grid" className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        {sortedData.map((item) => (
          <div
            key={item.name}
            data-testid={`bar-${item.name.toLowerCase()}`}
            data-value={item.value}
            data-is-brand={item.isBrand}
            className={cn(
              'rounded-lg p-3 text-center cursor-pointer transition-colors',
              item.isBrand ? 'bg-blue-500/20 border border-blue-500/30' : 'bg-slate-700/50 hover:bg-slate-700'
            )}
            onClick={() => onBarClick?.(item)}
          >
            <p data-testid={`name-${item.name.toLowerCase()}`} className="text-xs text-slate-400 truncate">{item.name}</p>
            <p
              data-testid={`value-${item.name.toLowerCase()}`}
              className={cn(
                'text-lg font-bold',
                item.isBrand ? 'text-blue-400' : 'text-white'
              )}
            >
              {item.value.toFixed(1)}%
            </p>
          </div>
        ))}
      </div>
    </div>
  );
}

export default CompetitorComparisonChart;
