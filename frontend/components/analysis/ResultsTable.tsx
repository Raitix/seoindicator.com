/**
 * ResultsTable Component - TASK-19-12
 *
 * React/Next.js component for displaying analysis results as they complete.
 *
 * Features:
 * - ResultsTable component with real-time updates
 * - ResultRow components for each indicator
 * - Sorting options (by status, score, name)
 * - Filtering options (all, completed, failed)
 * - Smooth animations for state changes
 * - Accessibility: table semantics, ARIA live regions
 *
 * @see TASK-19-12
 */

'use client';

import React, { useState, useMemo, useCallback } from 'react';
import {
  IndicatorResult,
  ResultsTableProps,
  ResultsSortBy,
  ResultsFilterBy,
  INDICATOR_STATUS_LABELS,
} from '@/lib/types/analysis-job';
import {
  sortResults,
  filterResults,
  getIndicatorSummary,
} from '@/lib/api/analysis-job';
import { ResultRow } from './ResultRow';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowUpDown, Filter, CheckCircle, XCircle, List } from 'lucide-react';

// Sort control component
function SortControl({
  value,
  onChange,
}: {
  value: ResultsSortBy;
  onChange: (value: ResultsSortBy) => void;
}) {
  return (
    <div className="flex items-center gap-2">
      <ArrowUpDown className="w-4 h-4 text-muted-foreground" />
      <Select
        value={value}
        onValueChange={(v) => onChange(v as ResultsSortBy)}
      >
        <SelectTrigger className="w-[140px]" aria-label="Sort results by">
          <SelectValue placeholder="Sort by" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="status">Status</SelectItem>
          <SelectItem value="score">Score (lowest)</SelectItem>
          <SelectItem value="name">Name (A-Z)</SelectItem>
        </SelectContent>
      </Select>
    </div>
  );
}

// Filter control component
function FilterControl({
  value,
  onChange,
  counts,
}: {
  value: ResultsFilterBy;
  onChange: (value: ResultsFilterBy) => void;
  counts: { completed: number; failed: number };
}) {
  return (
    <div className="flex items-center gap-2">
      <Filter className="w-4 h-4 text-muted-foreground" />
      <Select
        value={value}
        onValueChange={(v) => onChange(v as ResultsFilterBy)}
      >
        <SelectTrigger className="w-[160px]" aria-label="Filter results">
          <SelectValue placeholder="Filter" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">
            <span className="flex items-center gap-2">
              <List className="w-4 h-4" />
              All ({counts.completed + counts.failed})
            </span>
          </SelectItem>
          <SelectItem value="completed">
            <span className="flex items-center gap-2">
              <CheckCircle className="w-4 h-4 text-green-500" />
              Completed ({counts.completed})
            </span>
          </SelectItem>
          <SelectItem value="failed">
            <span className="flex items-center gap-2">
              <XCircle className="w-4 h-4 text-red-500" />
              Failed ({counts.failed})
            </span>
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
  );
}

// Summary stats component
function SummaryStats({
  results,
}: {
  results: IndicatorResult[];
}) {
  const summary = getIndicatorSummary(results);

  return (
    <div className="flex items-center gap-4 text-sm text-muted-foreground">
      <span className="flex items-center gap-1.5">
        <span className="w-2 h-2 rounded-full bg-green-500" />
        {summary.completed} completed
      </span>
      <span className="flex items-center gap-1.5">
        <span className="w-2 h-2 rounded-full bg-red-500" />
        {summary.failed} failed
      </span>
      {(summary.pending > 0 || summary.running > 0) && (
        <span className="flex items-center gap-1.5">
          <span className="w-2 h-2 rounded-full bg-yellow-500 animate-pulse" />
          {summary.running + summary.pending} remaining
        </span>
      )}
    </div>
  );
}

// Empty state component
function EmptyState({ filter }: { filter: ResultsFilterBy }) {
  const messages: Record<ResultsFilterBy, string> = {
    all: 'No results available yet.',
    completed: 'No completed results yet.',
    failed: 'No failed results.',
  };

  return (
    <div className="text-center py-8 text-muted-foreground">
      {messages[filter]}
    </div>
  );
}

// Main ResultsTable component
export function ResultsTable({
  jobId,
  results,
  onResultClick,
  className = '',
  initialSortBy = 'status',
  initialFilterBy = 'all',
  showSortControls = true,
  showFilterControls = true,
}: ResultsTableProps) {
  const [sortBy, setSortBy] = useState<ResultsSortBy>(initialSortBy);
  const [filterBy, setFilterBy] = useState<ResultsFilterBy>(initialFilterBy);
  const [expandedRows, setExpandedRows] = useState<Set<string>>(new Set());

  // Process results with sorting and filtering
  const processedResults = useMemo(() => {
    const filtered = filterResults(results, filterBy);
    return sortResults(filtered, sortBy);
  }, [results, sortBy, filterBy]);

  // Get counts for filter badges
  const summary = useMemo(() => getIndicatorSummary(results), [results]);

  // Toggle row expansion
  const toggleRowExpand = useCallback((indicator: string) => {
    setExpandedRows((prev) => {
      const next = new Set(prev);
      if (next.has(indicator)) {
        next.delete(indicator);
      } else {
        next.add(indicator);
      }
      return next;
    });
  }, []);

  // Handle result click
  const handleResultClick = useCallback(
    (result: IndicatorResult) => {
      onResultClick?.(result);
    },
    [onResultClick]
  );

  return (
    <Card className={className}>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <CardTitle className="text-lg">Analysis Results</CardTitle>
          <SummaryStats results={results} />
        </div>

        {/* Controls row */}
        <div className="flex items-center gap-4 pt-2">
          {showSortControls && (
            <SortControl value={sortBy} onChange={setSortBy} />
          )}
          {showFilterControls && (
            <FilterControl
              value={filterBy}
              onChange={setFilterBy}
              counts={{ completed: summary.completed, failed: summary.failed }}
            />
          )}
        </div>
      </CardHeader>

      <CardContent className="p-0">
        {/* Results list with table semantics */}
        <div
          role="table"
          aria-label="Analysis results table"
          className="divide-y divide-border"
        >
          {/* Screen reader row headers */}
          <div role="rowgroup" className="sr-only">
            <div role="row">
              <span role="columnheader">Status</span>
              <span role="columnheader">Indicator</span>
              <span role="columnheader">Score</span>
            </div>
          </div>

          {/* Results */}
          <div role="rowgroup">
            {processedResults.length > 0 ? (
              processedResults.map((result, index) => (
                <ResultRow
                  key={`${result.indicator}-${index}`}
                  result={result}
                  onClick={handleResultClick}
                  isExpanded={expandedRows.has(result.indicator)}
                  onToggleExpand={() => toggleRowExpand(result.indicator)}
                  className="border-b last:border-b-0"
                />
              ))
            ) : (
              <EmptyState filter={filterBy} />
            )}
          </div>
        </div>

        {/* Live region for screen readers */}
        <div
          className="sr-only"
          role="status"
          aria-live="polite"
          aria-atomic="true"
        >
          {processedResults.length > 0 && (
            <>
              Showing {processedResults.length} results.
              {summary.completed > 0 && ` ${summary.completed} completed.`}
              {summary.failed > 0 && ` ${summary.failed} failed.`}
            </>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

export default ResultsTable;
