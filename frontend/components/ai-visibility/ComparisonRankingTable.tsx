/**
 * TASK-34-17: ComparisonRankingTable Component
 * 
 * Displays a ranking table showing brand position vs all competitors
 * with trend indicators and sorting functionality.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-17.md
 */

'use client';

import * as React from 'react';
import { TrendingUp, TrendingDown, Minus, ChevronUp, ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { RankingEntry } from '@/lib/hooks/useComparisonData';

// ============================================
// Types
// ============================================

interface ComparisonRankingTableProps {
  rankings: RankingEntry[];
  brandName: string;
  brandPosition: number;
  onRowClick?: (entry: RankingEntry) => void;
  className?: string;
}

type SortKey = 'position' | 'rate' | 'name';
type SortOrder = 'asc' | 'desc';

// ============================================
// Component
// ============================================

export function ComparisonRankingTable({
  rankings,
  brandName,
  brandPosition,
  onRowClick,
  className,
}: ComparisonRankingTableProps) {
  const [sortKey, setSortKey] = React.useState<SortKey>('position');
  const [sortOrder, setSortOrder] = React.useState<SortOrder>('asc');

  // Handle sorting
  const handleSort = (key: SortKey) => {
    if (sortKey === key) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setSortKey(key);
      setSortOrder('asc');
    }
  };

  // Sort rankings
  const sortedRankings = React.useMemo(() => {
    const sorted = [...rankings];
    sorted.sort((a, b) => {
      let comparison = 0;
      
      if (sortKey === 'position') {
        comparison = a.position - b.position;
      } else if (sortKey === 'rate') {
        comparison = a.rate - b.rate;
      } else if (sortKey === 'name') {
        comparison = a.name.localeCompare(b.name);
      }
      
      return sortOrder === 'asc' ? comparison : -comparison;
    });
    
    return sorted;
  }, [rankings, sortKey, sortOrder]);

  // Empty state
  if (!rankings || rankings.length === 0) {
    return (
      <div data-testid="ranking-table-section" className={cn('rounded-lg bg-slate-800 p-6', className)}>
        <p className="text-center text-slate-400">No ranking data available</p>
      </div>
    );
  }

  return (
    <div data-testid="ranking-table-section" className={cn('rounded-lg bg-slate-800', className)}>
      {/* Brand Position Badge */}
      <div className="flex items-center justify-between p-4 border-b border-slate-700">
        <h3 className="text-lg font-semibold text-white">Rankings</h3>
        <div className="flex items-center gap-2">
          <span className="text-sm text-slate-400">Your position:</span>
          <span className="inline-flex items-center rounded-full bg-blue-500/20 px-3 py-1 text-sm font-medium text-blue-400">
            #{brandPosition}
          </span>
        </div>
      </div>

      {/* Table */}
      <div className="overflow-x-auto">
        <table role="table" className="w-full">
          <thead>
            <tr className="border-b border-slate-700">
              <th
                role="columnheader"
                className="px-4 py-3 text-left text-sm font-medium text-slate-400 cursor-pointer hover:text-white"
                onClick={() => handleSort('position')}
              >
                <div className="flex items-center gap-1">
                  Position
                  {sortKey === 'position' && (
                    sortOrder === 'asc' ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />
                  )}
                </div>
              </th>
              <th
                role="columnheader"
                className="px-4 py-3 text-left text-sm font-medium text-slate-400 cursor-pointer hover:text-white"
                onClick={() => handleSort('name')}
              >
                <div className="flex items-center gap-1">
                  Name
                  {sortKey === 'name' && (
                    sortOrder === 'asc' ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />
                  )}
                </div>
              </th>
              <th
                role="columnheader"
                className="px-4 py-3 text-left text-sm font-medium text-slate-400 cursor-pointer hover:text-white"
                onClick={() => handleSort('rate')}
              >
                <div className="flex items-center gap-1">
                  Rate
                  {sortKey === 'rate' && (
                    sortOrder === 'asc' ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />
                  )}
                </div>
              </th>
              <th
                role="columnheader"
                className="px-4 py-3 text-left text-sm font-medium text-slate-400"
              >
                Trend
              </th>
            </tr>
          </thead>
          <tbody>
            {sortedRankings.map((entry) => (
              <tr
                key={entry.position}
                role="row"
                data-is-brand={entry.isBrand ? 'true' : 'false'}
                className={cn(
                  'border-b border-slate-700/50 cursor-pointer hover:bg-slate-700/30 transition-colors',
                  entry.isBrand && 'bg-blue-500/10 hover:bg-blue-500/20'
                )}
                onClick={() => onRowClick?.(entry)}
              >
                <td role="cell" className="px-4 py-3 text-sm text-slate-300">
                  {entry.position}
                </td>
                <td
                  role="cell"
                  className={cn(
                    'px-4 py-3 text-sm font-medium truncate max-w-[200px]',
                    entry.isBrand ? 'text-blue-400' : 'text-white'
                  )}
                  title={entry.name}
                >
                  {entry.name}
                </td>
                <td role="cell" className="px-4 py-3 text-sm text-slate-300">
                  {entry.rate.toFixed(1)}%
                </td>
                <td role="cell" className="px-4 py-3 text-sm">
                  <TrendIndicator trend={entry.trend} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ============================================
// Trend Indicator Sub-Component
// ============================================

function TrendIndicator({ trend }: { trend: 'up' | 'down' | 'stable' }) {
  if (trend === 'up') {
    return (
      <span data-testid="trending-up-icon" className="inline-flex items-center text-green-400">
        <TrendingUp className="h-4 w-4" />
      </span>
    );
  }
  
  if (trend === 'down') {
    return (
      <span data-testid="trending-down-icon" className="inline-flex items-center text-red-400">
        <TrendingDown className="h-4 w-4" />
      </span>
    );
  }
  
  return (
    <span data-testid="minus-icon" className="inline-flex items-center text-slate-400">
      <Minus className="h-4 w-4" />
    </span>
  );
}

export default ComparisonRankingTable;
