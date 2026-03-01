/**
 * ResultRow Component - TASK-19-12
 *
 * Individual row component for displaying a single indicator result.
 *
 * Features:
 * - Status indicator with color coding
 * - Indicator name display
 * - Score/percentage display
 * - Expandable details section
 * - Smooth animations for state changes
 * - Accessibility: ARIA labels, keyboard navigation
 *
 * @see TASK-19-12
 */

'use client';

import React, { memo, useCallback } from 'react';
import {
  IndicatorResult,
  INDICATOR_STATUS_LABELS,
  INDICATOR_STATUS_COLORS,
} from '@/lib/types/analysis-job';
import {
  getScoreColor,
  getScoreBgColor,
  getIndicatorDisplayName,
} from '@/lib/api/analysis-job';
import { ResultRowProps } from '@/lib/types/analysis-job';
import { ChevronDown, ChevronRight } from 'lucide-react';

// Status indicator component
const StatusIndicator = memo(function StatusIndicator({
  status,
}: {
  status: string;
}) {
  const colorClasses: Record<string, string> = {
    blue: 'bg-blue-500',
    green: 'bg-green-500',
    yellow: 'bg-yellow-500',
    red: 'bg-red-500',
    gray: 'bg-gray-400',
  };

  const color =
    INDICATOR_STATUS_COLORS[status as keyof typeof INDICATOR_STATUS_COLORS] ||
    'gray';

  // Animated pulse for running status
  const pulseClass = status === 'running' ? 'animate-pulse' : '';

  return (
    <span
      className={`inline-block w-2.5 h-2.5 rounded-full ${colorClasses[color]} ${pulseClass}`}
      role="presentation"
      aria-hidden="true"
    />
  );
});

// Score display component
const ScoreDisplay = memo(function ScoreDisplay({
  score,
  maxScore,
  percentage,
  status,
}: {
  score: number;
  maxScore: number;
  percentage: number;
  status: string;
}) {
  // Don't show score for pending/running items
  if (status === 'pending' || status === 'running') {
    return (
      <span className="text-sm text-muted-foreground">
        {status === 'running' ? 'Analyzing...' : 'Waiting...'}
      </span>
    );
  }

  return (
    <div className="flex items-center gap-2">
      <span
        className={`text-sm font-medium ${getScoreColor(percentage)}`}
        aria-label={`Score: ${percentage}%`}
      >
        {percentage}%
      </span>
      <span className="text-xs text-muted-foreground">
        ({score}/{maxScore})
      </span>
    </div>
  );
});

// Expandable details section
const DetailsSection = memo(function DetailsSection({
  result,
  isExpanded,
}: {
  result: IndicatorResult;
  isExpanded: boolean;
}) {
  if (!isExpanded) return null;

  const hasData = result.data && Object.keys(result.data).length > 0;
  const hasRecommendations =
    result.recommendations && result.recommendations.length > 0;

  return (
    <div
      className="col-span-full mt-2 p-3 bg-muted/50 rounded-md text-sm space-y-3"
      role="region"
      aria-label={`Details for ${getIndicatorDisplayName(result.indicator)}`}
    >
      {/* Error message */}
      {result.error_message && (
        <div className="text-red-600 dark:text-red-400" role="alert">
          <span className="font-medium">Error: </span>
          {result.error_message}
        </div>
      )}

      {/* Data section */}
      {hasData && (
        <div>
          <h4 className="font-medium text-foreground mb-2">Details</h4>
          <dl className="grid grid-cols-2 gap-x-4 gap-y-1">
            {Object.entries(result.data).map(([key, value]) => (
              <React.Fragment key={key}>
                <dt className="text-muted-foreground capitalize">
                  {key.replace(/_/g, ' ')}:
                </dt>
                <dd className="text-foreground">
                  {typeof value === 'object'
                    ? JSON.stringify(value)
                    : String(value)}
                </dd>
              </React.Fragment>
            ))}
          </dl>
        </div>
      )}

      {/* Recommendations section */}
      {hasRecommendations && (
        <div>
          <h4 className="font-medium text-foreground mb-2">
            Recommendations ({result.recommendations!.length})
          </h4>
          <ul className="list-disc list-inside space-y-1">
            {result.recommendations!.map((rec, idx) => (
              <li key={idx} className="text-muted-foreground">
                {rec}
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* No details available */}
      {!hasData && !hasRecommendations && !result.error_message && (
        <p className="text-muted-foreground italic">No additional details available.</p>
      )}
    </div>
  );
});

// Main ResultRow component
function ResultRowComponent({
  result,
  onClick,
  isExpanded = false,
  onToggleExpand,
  className = '',
}: ResultRowProps) {
  const statusLabel =
    INDICATOR_STATUS_LABELS[result.status as keyof typeof INDICATOR_STATUS_LABELS] ||
    result.status;
  const indicatorName = getIndicatorDisplayName(result.indicator);

  const handleClick = useCallback(() => {
    onClick?.(result);
  }, [onClick, result]);

  const handleToggleExpand = useCallback(() => {
    onToggleExpand?.();
  }, [onToggleExpand]);

  const handleKeyDown = useCallback(
    (e: React.KeyboardEvent) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        handleClick();
      }
      if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
        e.preventDefault();
        handleToggleExpand();
      }
    },
    [handleClick, handleToggleExpand]
  );

  const isInteractive = !!onClick;
  const showExpandButton = result.status === 'completed' || result.status === 'failed';
  const scoreBgClass = result.status === 'completed' ? getScoreBgColor(result.percentage) : '';

  return (
    <div
      className={`
        grid grid-cols-[auto_1fr_auto] gap-3 items-center p-3 rounded-lg
        transition-all duration-200 ease-in-out
        ${isInteractive ? 'cursor-pointer hover:bg-muted/50' : ''}
        ${scoreBgClass}
        ${className}
      `}
      role="row"
      onClick={isInteractive ? handleClick : undefined}
      onKeyDown={isInteractive ? handleKeyDown : undefined}
      tabIndex={isInteractive ? 0 : -1}
      aria-label={`${indicatorName}: ${statusLabel}, Score ${result.percentage}%`}
    >
      {/* Expand toggle button */}
      <button
        type="button"
        onClick={(e) => {
          e.stopPropagation();
          handleToggleExpand();
        }}
        className={`
          p-1 rounded hover:bg-muted transition-colors
          ${showExpandButton ? 'opacity-100' : 'opacity-0 pointer-events-none'}
        `}
        aria-label={isExpanded ? 'Collapse details' : 'Expand details'}
        aria-expanded={isExpanded}
        disabled={!showExpandButton}
      >
        {isExpanded ? (
          <ChevronDown className="w-4 h-4 text-muted-foreground" />
        ) : (
          <ChevronRight className="w-4 h-4 text-muted-foreground" />
        )}
      </button>

      {/* Status + Name */}
      <div className="flex items-center gap-3 min-w-0">
        <StatusIndicator status={result.status} />
        <span className="font-medium text-foreground truncate" title={indicatorName}>
          {indicatorName}
        </span>
        <span className="sr-only">{statusLabel}</span>
      </div>

      {/* Score display */}
      <ScoreDisplay
        score={result.score}
        maxScore={result.max_score}
        percentage={result.percentage}
        status={result.status}
      />

      {/* Expandable details */}
      <DetailsSection result={result} isExpanded={isExpanded} />
    </div>
  );
}

// Memoize for performance
export const ResultRow = memo(ResultRowComponent);

export default ResultRow;
