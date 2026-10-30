/**
 * TASK-34-16: MentionRateChart Component
 * 
 * Line chart showing mention rate over time with platform breakdown.
 * Uses Recharts for rendering.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-16.md
 */

'use client';

import * as React from 'react';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  TooltipProps,
} from 'recharts';
import { Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { MentionRateChartProps, MentionRateDataPoint } from '@/lib/types/ai-visibility';

// ============================================
// Custom Tooltip Component
// ============================================

interface CustomTooltipProps extends TooltipProps<number, string> {
  active?: boolean;
  payload?: Array<{
    value: number;
    name: string;
    color: string;
  }>;
  label?: string;
}

function CustomTooltip({ active, payload, label }: CustomTooltipProps) {
  if (!active || !payload || payload.length === 0) {
    return null;
  }

  return (
    <div className="rounded-lg bg-slate-800 border border-slate-700 p-3 shadow-lg">
      <p className="text-xs text-slate-400 mb-2">{label}</p>
      {payload.map((entry, index) => (
        <div key={index} className="flex items-center gap-2 text-sm">
          <span
            className="w-2 h-2 rounded-full"
            style={{ backgroundColor: entry.color }}
          />
          <span className="text-slate-300 capitalize">{entry.name}:</span>
          <span className="font-medium text-white">{entry.value.toFixed(1)}%</span>
        </div>
      ))}
    </div>
  );
}

// ============================================
// Chart Component
// ============================================

export function MentionRateChart({ data, className }: MentionRateChartProps) {
  // Empty state
  if (!data || data.length === 0) {
    return (
      <div
        data-testid="chart-empty-state"
        data-empty="true"
        className={cn(
          'flex flex-col items-center justify-center rounded-lg bg-slate-800 p-8',
          className
        )}
      >
        <p className="text-slate-400">No data available yet</p>
        <p className="mt-1 text-sm text-slate-500">
          Run queries to start tracking your mention rate over time
        </p>
      </div>
    );
  }

  return (
    <div data-testid="mention-rate-chart" className={className}>
      {/* Chart */}
      <div data-testid="chart-platforms">
        <ResponsiveContainer width="100%" height={300}>
          <LineChart
            data={data}
            margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
          >
            <CartesianGrid data-testid="cartesian-grid" strokeDasharray="3 3" stroke="#334155" />
            <XAxis
              data-testid="x-axis"
              dataKey="date"
              stroke="#64748b"
              tick={{ fill: '#94a3b8', fontSize: 12 }}
              tickFormatter={(value) => {
                const date = new Date(value);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
              }}
            />
            <YAxis
              data-testid="y-axis"
              data-domain="[0,100]"
              stroke="#64748b"
              tick={{ fill: '#94a3b8', fontSize: 12 }}
              domain={[0, 100]}
              tickFormatter={(value) => `${value}%`}
            />
            <Tooltip content={<CustomTooltip />} />
            <Line
              data-testid="line-overall"
              type="monotone"
              dataKey="overall"
              stroke="#3b82f6"
              strokeWidth={2}
              dot={false}
              activeDot={{ r: 4, fill: '#3b82f6' }}
            />
            <Line
              data-testid="line-chatgpt"
              type="monotone"
              dataKey="chatgpt"
              stroke="#10b981"
              strokeWidth={2}
              dot={false}
              activeDot={{ r: 4, fill: '#10b981' }}
            />
            <Line
              data-testid="line-perplexity"
              type="monotone"
              dataKey="perplexity"
              stroke="#f59e0b"
              strokeWidth={2}
              dot={false}
              activeDot={{ r: 4, fill: '#f59e0b' }}
            />
          </LineChart>
        </ResponsiveContainer>
      </div>

      {/* Legend */}
      <div data-testid="chart-legend" className="mt-4 flex items-center justify-center gap-6">
        <div data-testid="legend-overall" className="flex items-center gap-2">
          <span data-testid="legend-color-overall" className="h-3 w-3 rounded-full bg-blue-500" style={{ backgroundColor: '#3b82f6' }} />
          <span className="text-sm text-slate-400">Overall</span>
        </div>
        <div data-testid="legend-chatgpt" className="flex items-center gap-2">
          <span data-testid="legend-color-chatgpt" className="h-3 w-3 rounded-full bg-emerald-500" style={{ backgroundColor: '#10b981' }} />
          <span className="text-sm text-slate-400">ChatGPT</span>
        </div>
        <div data-testid="legend-perplexity" className="flex items-center gap-2">
          <span data-testid="legend-color-perplexity" className="h-3 w-3 rounded-full bg-amber-500" style={{ backgroundColor: '#f59e0b' }} />
          <span className="text-sm text-slate-400">Perplexity</span>
        </div>
      </div>
    </div>
  );
}

export default MentionRateChart;
