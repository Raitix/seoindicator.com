/**
 * TASK-34-17: CompetitorDetailPanel Component
 * 
 * Expandable accordion panel showing competitor details:
 * recent mentions, sentiment breakdown, and platform distribution.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-17.md
 */

'use client';

import * as React from 'react';
import { ChevronDown, ChevronUp, MessageSquare, Globe } from 'lucide-react';
import {
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  Tooltip,
  BarChart,
  Bar,
  XAxis,
  YAxis,
} from 'recharts';
import { cn } from '@/lib/utils';
import type { CompetitorDetails } from '@/lib/hooks/useComparisonData';

// ============================================
// Types
// ============================================

interface CompetitorDetailPanelProps {
  competitorName: string;
  details: CompetitorDetails | null;
  isExpanded: boolean;
  onToggle: () => void;
  className?: string;
}

// ============================================
// Constants
// ============================================

const SENTIMENT_COLORS = {
  positive: '#22c55e', // green-500
  neutral: '#94a3b8', // slate-400
  negative: '#ef4444', // red-500
};

const PLATFORM_COLORS = {
  chatgpt: '#10b981', // emerald-500
  perplexity: '#6366f1', // indigo-500
};

// ============================================
// Component
// ============================================

export function CompetitorDetailPanel({
  competitorName,
  details,
  isExpanded,
  onToggle,
  className,
}: CompetitorDetailPanelProps) {
  return (
    <div
      data-testid="competitor-panel"
      className={cn('rounded-lg bg-slate-800 border border-slate-700', className)}
    >
      {/* Header */}
      <button
        onClick={onToggle}
        aria-expanded={isExpanded}
        className="w-full flex items-center justify-between p-4 hover:bg-slate-700/30 transition-colors"
      >
        <span className="text-lg font-semibold text-white">{competitorName}</span>
        {isExpanded ? (
          <ChevronUp data-testid="chevron-up-icon" className="h-5 w-5 text-slate-400" />
        ) : (
          <ChevronDown data-testid="chevron-down-icon" className="h-5 w-5 text-slate-400" />
        )}
      </button>

      {/* Expanded Content */}
      {isExpanded && (
        <div
          data-testid="panel-content"
          role="region"
          aria-label={`${competitorName} details`}
          className="p-4 border-t border-slate-700"
        >
          {details ? (
            <div className="space-y-6">
              {/* Recent Mentions */}
              <div>
                <h4 className="text-sm font-medium text-slate-400 mb-3">Recent Mentions</h4>
                {details.recent_mentions && details.recent_mentions.length > 0 ? (
                  <div className="space-y-3">
                    {details.recent_mentions.map((mention, idx) => (
                      <div
                        key={idx}
                        className="rounded-lg bg-slate-700/50 p-3"
                      >
                        <p className="text-sm text-slate-300 mb-2">
                          {mention.context}
                        </p>
                        <div className="flex items-center gap-2">
                          {/* Platform Badge */}
                          <span
                            className={cn(
                              'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                              mention.platform === 'chatgpt'
                                ? 'bg-emerald-500/20 text-emerald-400'
                                : 'bg-indigo-500/20 text-indigo-400'
                            )}
                          >
                            {mention.platform === 'chatgpt' ? (
                              <MessageSquare className="h-3 w-3" />
                            ) : (
                              <Globe className="h-3 w-3" />
                            )}
                            {mention.platform === 'chatgpt' ? 'ChatGPT' : 'Perplexity'}
                          </span>
                          
                          {/* Sentiment Badge */}
                          <span
                            className={cn(
                              'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                              mention.sentiment === 'positive'
                                ? 'bg-green-100 text-green-700'
                                : mention.sentiment === 'negative'
                                ? 'bg-red-100 text-red-700'
                                : 'bg-slate-100 text-slate-700'
                            )}
                          >
                            {mention.sentiment}
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-slate-500">No mentions available</p>
                )}
              </div>

              {/* Charts Row */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Sentiment Breakdown */}
                <div>
                  <h4 className="text-sm font-medium text-slate-400 mb-3">Sentiment Breakdown</h4>
                  {details.sentiment_breakdown &&
                  (details.sentiment_breakdown.positive > 0 ||
                    details.sentiment_breakdown.neutral > 0 ||
                    details.sentiment_breakdown.negative > 0) ? (
                    <ResponsiveContainer width="100%" height={150}>
                      <PieChart>
                        <Pie
                          data={[
                            { name: 'Positive', value: details.sentiment_breakdown.positive },
                            { name: 'Neutral', value: details.sentiment_breakdown.neutral },
                            { name: 'Negative', value: details.sentiment_breakdown.negative },
                          ]}
                          dataKey="value"
                          nameKey="name"
                          cx="50%"
                          cy="50%"
                          outerRadius={50}
                        >
                          <Cell fill={SENTIMENT_COLORS.positive} />
                          <Cell fill={SENTIMENT_COLORS.neutral} />
                          <Cell fill={SENTIMENT_COLORS.negative} />
                        </Pie>
                        <Tooltip data-testid="tooltip" />
                      </PieChart>
                    </ResponsiveContainer>
                  ) : (
                    <p className="text-sm text-slate-500">No sentiment data</p>
                  )}
                </div>

                {/* Platform Distribution */}
                <div>
                  <h4 className="text-sm font-medium text-slate-400 mb-3">Platform Distribution</h4>
                  {details.platforms && details.platforms.length > 0 ? (
                    <ResponsiveContainer width="100%" height={150}>
                      <BarChart
                        data={details.platforms.map(p => ({
                          name: p,
                          value: 1,
                        }))}
                      >
                        <XAxis
                          data-testid="x-axis"
                          dataKey="name"
                          tick={{ fill: '#94a3b8', fontSize: 10 }}
                        />
                        <YAxis data-testid="y-axis" hide />
                        <Bar data-testid="bar" dataKey="value" fill="#6366f1">
                          {details.platforms.map((p, idx) => (
                            <Cell
                              key={`cell-${idx}`}
                              fill={
                                p === 'chatgpt'
                                  ? PLATFORM_COLORS.chatgpt
                                  : PLATFORM_COLORS.perplexity
                              }
                            />
                          ))}
                        </Bar>
                      </BarChart>
                    </ResponsiveContainer>
                  ) : (
                    <p className="text-sm text-slate-500">No platform data</p>
                  )}
                </div>
              </div>

              {/* Query Categories */}
              {details.query_categories && details.query_categories.length > 0 && (
                <div>
                  <h4 className="text-sm font-medium text-slate-400 mb-3">Query Categories</h4>
                  <div className="flex flex-wrap gap-2">
                    {details.query_categories.map(category => (
                      <span
                        key={category}
                        className="inline-flex items-center rounded-full bg-slate-700 px-3 py-1 text-sm text-slate-300 capitalize"
                      >
                        {formatCategory(category)}
                      </span>
                    ))}
                  </div>
                </div>
              )}
            </div>
          ) : (
            <p className="text-center text-slate-500 py-4">No details available</p>
          )}
        </div>
      )}
    </div>
  );
}

// ============================================
// Helpers
// ============================================

function formatCategory(category: string): string {
  return category
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

export default CompetitorDetailPanel;
