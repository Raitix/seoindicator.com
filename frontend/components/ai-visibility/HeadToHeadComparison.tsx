/**
 * TASK-34-17: HeadToHeadComparison Component
 * 
 * Side-by-side comparison cards for brand vs selected competitor
 * with metrics and difference indicators.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-17.md
 */

'use client';

import * as React from 'react';
import { Users, ArrowUp, ArrowDown, Minus } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Competitor, HeadToHeadData } from '@/lib/hooks/useComparisonData';

// ============================================
// Types
// ============================================

interface HeadToHeadComparisonProps {
  brandName: string;
  competitors: Competitor[];
  selectedCompetitor: Competitor | null;
  headToHead: HeadToHeadData | null;
  onSelectCompetitor: (competitor: Competitor) => void;
  onAddCompetitor?: () => void;
  isLoading?: boolean;
  className?: string;
}

// ============================================
// Component
// ============================================

export function HeadToHeadComparison({
  brandName,
  competitors,
  selectedCompetitor,
  headToHead,
  onSelectCompetitor,
  onAddCompetitor,
  isLoading,
  className,
}: HeadToHeadComparisonProps) {
  // Loading state
  if (isLoading) {
    return (
      <div data-testid="head-to-head-section" className={cn('rounded-lg bg-slate-800 p-6', className)}>
        <div data-testid="loading-skeleton" className="animate-pulse space-y-4">
          <div className="h-10 bg-slate-700 rounded w-1/3" />
          <div className="grid grid-cols-2 gap-4">
            <div className="h-40 bg-slate-700 rounded" />
            <div className="h-40 bg-slate-700 rounded" />
          </div>
        </div>
      </div>
    );
  }

  // No competitors state
  if (!competitors || competitors.length === 0) {
    return (
      <div data-testid="head-to-head-section" className={cn('rounded-lg bg-slate-800 p-6', className)}>
        <div className="text-center py-8">
          <p className="text-slate-400 mb-4">No competitors added yet</p>
          {onAddCompetitor && (
            <button
              onClick={onAddCompetitor}
              className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
            >
              Add Competitor
            </button>
          )}
        </div>
      </div>
    );
  }

  // No competitor selected state
  if (!selectedCompetitor) {
    return (
      <div data-testid="head-to-head-section" className={cn('rounded-lg bg-slate-800 p-6', className)}>
        <p className="text-center text-slate-400">Select a competitor to compare</p>
      </div>
    );
  }

  // No head-to-head data
  if (!headToHead) {
    return (
      <div data-testid="head-to-head-section" className={cn('rounded-lg bg-slate-800 p-6', className)}>
        <div className="text-center py-8">
          <p className="text-slate-400">Select a competitor to view comparison data</p>
        </div>
      </div>
    );
  }

  return (
    <div
      data-testid="head-to-head-section"
      className={cn('rounded-lg bg-slate-800', className)}
    >
      {/* Header with Competitor Selector */}
      <div className="flex items-center justify-between p-4 border-b border-slate-700">
        <h3 className="text-lg font-semibold text-white">Head-to-Head Comparison</h3>
        
        {/* Competitor Dropdown */}
        <select
          role="combobox"
          aria-label="Select competitor"
          value={selectedCompetitor.id}
          onChange={(e) => {
            const competitor = competitors.find(c => c.id === e.target.value);
            if (competitor) onSelectCompetitor(competitor);
          }}
          className="rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          {competitors.map((competitor) => (
            <option key={competitor.id} value={competitor.id}>
              {competitor.name}
            </option>
          ))}
        </select>
      </div>

      {/* Comparison Cards */}
      <div className="p-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* Brand Card */}
          <ComparisonCard
            testId="brand-card"
            name={brandName}
            mentions={headToHead.brand.mentions}
            rate={headToHead.brand.rate}
            sentiment={headToHead.brand.sentiment}
            advantages={headToHead.brand_advantage}
            isBrand
          />

          {/* Competitor Card */}
          <ComparisonCard
            testId="competitor-card"
            name={selectedCompetitor.name}
            mentions={headToHead.competitor.mentions}
            rate={headToHead.competitor.rate}
            sentiment={headToHead.competitor.sentiment}
            advantages={headToHead.competitor_advantage}
            isBrand={false}
          />
        </div>

        {/* Co-mentions */}
        <div className="mt-4 flex items-center justify-center gap-2 text-slate-400 text-sm">
          <Users className="h-4 w-4" />
          <span>{headToHead.co_mentions} co-mentions</span>
        </div>

        {/* Difference Indicator */}
        <div className="mt-4 flex justify-center">
          <DifferenceIndicator
            brandRate={headToHead.brand.rate}
            competitorRate={headToHead.competitor.rate}
            brandName={brandName}
            competitorName={selectedCompetitor.name}
          />
        </div>
      </div>
    </div>
  );
}

// ============================================
// Comparison Card Sub-Component
// ============================================

interface ComparisonCardProps {
  testId: string;
  name: string;
  mentions: number;
  rate: number;
  sentiment: number;
  advantages: string[];
  isBrand: boolean;
}

function ComparisonCard({
  testId,
  name,
  mentions,
  rate,
  sentiment,
  advantages,
  isBrand,
}: ComparisonCardProps) {
  return (
    <div
      data-testid={testId}
      aria-label={`${name} metrics`}
      className={cn(
        'rounded-lg p-4',
        isBrand ? 'bg-blue-500/10 border border-blue-500/20' : 'bg-slate-700/50 border border-slate-600'
      )}
    >
      <h4 className={cn('text-lg font-semibold mb-4', isBrand ? 'text-blue-400' : 'text-white')}>
        {name}
      </h4>

      <div className="space-y-3">
        {/* Mentions */}
        <div className="flex items-center justify-between">
          <span className="text-sm text-slate-400">Mentions</span>
          <span className="text-sm font-medium text-white">{mentions} mentions</span>
        </div>

        {/* Rate */}
        <div className="flex items-center justify-between">
          <span className="text-sm text-slate-400">Mention Rate</span>
          <span className="text-sm font-medium text-white">{rate.toFixed(1)}%</span>
        </div>

        {/* Sentiment */}
        <div className="flex items-center justify-between">
          <span className="text-sm text-slate-400">Sentiment Score</span>
          <span className={cn(
            'text-sm font-medium',
            sentiment > 0 ? 'text-green-400' : sentiment < 0 ? 'text-red-400' : 'text-slate-400'
          )}>
            {sentiment.toFixed(2)}
          </span>
        </div>

        {/* Advantages */}
        {advantages.length > 0 && (
          <div className="mt-4 pt-3 border-t border-slate-600">
            <span className="text-xs text-slate-500 block mb-2">
              {isBrand ? 'Advantages' : 'Winning Categories'}
            </span>
            <div className="flex flex-wrap gap-1">
              {advantages.map((adv) => (
                <span
                  key={adv}
                  className={cn(
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                    isBrand ? 'bg-blue-500/20 text-blue-300' : 'bg-slate-600 text-slate-300'
                  )}
                >
                  {formatCategory(adv)}
                </span>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

// ============================================
// Difference Indicator Sub-Component
// ============================================

interface DifferenceIndicatorProps {
  brandRate: number;
  competitorRate: number;
  brandName: string;
  competitorName: string;
}

function DifferenceIndicator({
  brandRate,
  competitorRate,
  brandName,
  competitorName,
}: DifferenceIndicatorProps) {
  const diff = brandRate - competitorRate;
  const absDiff = Math.abs(diff);

  if (Math.abs(diff) < 0.1) {
    return (
      <div className="inline-flex items-center gap-2 rounded-full bg-slate-700 px-4 py-2 text-sm text-slate-300">
        <Minus className="h-4 w-4" />
        Tied
      </div>
    );
  }

  if (diff > 0) {
    return (
      <div className="inline-flex items-center gap-2 rounded-full bg-green-500/20 px-4 py-2 text-sm text-green-400">
        <ArrowUp className="h-4 w-4" />
        {brandName} ahead by {absDiff.toFixed(1)}%
      </div>
    );
  }

  return (
    <div className="inline-flex items-center gap-2 rounded-full bg-red-500/20 px-4 py-2 text-sm text-red-400">
      <ArrowDown className="h-4 w-4" />
      {brandName} behind by {absDiff.toFixed(1)}%
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

export default HeadToHeadComparison;
