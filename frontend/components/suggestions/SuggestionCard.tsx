'use client';

import * as React from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import type { KeywordSuggestion, SuggestionSource } from '@/lib/types/suggestions';

/**
 * Props for the SuggestionCard component
 */
export interface SuggestionCardProps {
  /** The suggestion data to display */
  suggestion: KeywordSuggestion;
  /** Callback when track button is clicked */
  onTrack?: (id: number) => void;
  /** Callback when dismiss button is clicked */
  onDismiss?: (id: number) => void;
  /** Show loading state for action buttons */
  isLoading?: boolean;
  /** Additional CSS classes */
  className?: string;
}

// Source badge color mapping
const SOURCE_COLORS: Record<SuggestionSource, string> = {
  suggest: 'bg-blue-500',
  question: 'bg-purple-500',
  related: 'bg-emerald-500',
  competitor: 'bg-orange-500',
};

// Source display labels
const SOURCE_LABELS: Record<SuggestionSource, string> = {
  suggest: 'Suggest',
  question: 'Question',
  related: 'Related',
  competitor: 'Competitor',
};

/**
 * SuggestionCard Component
 *
 * A reusable card component for displaying individual keyword suggestions with actions.
 * Features:
 * - Source badge with color coding
 * - Opportunity score with progress bar
 * - Track/Dismiss actions
 * - Expandable details view
 * - Keyboard accessibility
 */
const SuggestionCard = React.memo(function SuggestionCard({
  suggestion,
  onTrack,
  onDismiss,
  isLoading = false,
  className,
}: SuggestionCardProps) {
  const [isExpanded, setIsExpanded] = React.useState(false);

  const isTracked = suggestion.isTracking;
  const isDismissed = suggestion.isDismissed;
  const showActions = !isTracked && !isDismissed;

  // Score clamped to 0-100 range
  const displayScore = Math.max(0, Math.min(100, suggestion.opportunityScore ?? 0));

  // Format discovered date
  const formattedDate = React.useMemo(() => {
    try {
      return new Date(suggestion.discoveredAt).toLocaleDateString();
    } catch {
      return 'Unknown';
    }
  }, [suggestion.discoveredAt]);

  // Keyboard handler for track button
  const handleKeyDown = React.useCallback(
    (e: React.KeyboardEvent) => {
      if (e.key === 'Enter' && onTrack && !isTracked && !isDismissed) {
        e.preventDefault();
        onTrack(suggestion.id);
      }
    },
    [onTrack, suggestion.id, isTracked, isDismissed]
  );

  return (
    <div
      data-testid={`suggestion-card-${suggestion.id}`}
      className={cn(
        'suggestion-card',
        'group relative rounded-lg border bg-card p-4 shadow-sm transition-all',
        'hover:border-primary/50 hover:shadow-md',
        'focus-within:ring-2 focus-within:ring-primary/20',
        isDismissed && 'opacity-60',
        className
      )}
      tabIndex={0}
      onKeyDown={handleKeyDown}
    >
      {/* Main Content */}
      <div data-testid="card-content" className="space-y-3">
        {/* Header Row: Keyword + Source Badge */}
        <div className="flex items-start justify-between gap-2">
          <h3
            data-testid="keyword-text"
            className="font-medium leading-tight break-words flex-1"
          >
            {suggestion.keyword}
          </h3>

          {/* Source Badge */}
          <span
            data-testid="source-badge"
            className={cn(
              'shrink-0 px-2 py-0.5 rounded-full text-xs font-medium text-white',
              SOURCE_COLORS[suggestion.source]
            )}
          >
            {SOURCE_LABELS[suggestion.source]}
          </span>
        </div>

        {/* Opportunity Score Section */}
        <div data-testid="opportunity-score" className="space-y-1">
          <div className="flex items-center justify-between text-sm">
            <span className="text-muted-foreground">Opportunity Score</span>
            <span data-testid="score-value" className="font-semibold">
              {displayScore}
            </span>
          </div>
          <div data-testid="progress-bar">
            <Progress value={displayScore} className="h-2" />
          </div>
        </div>
      </div>

      {/* Tracking Status Indicator */}
      {isTracked && (
        <span
          data-testid="tracking-checkmark"
          aria-label="Tracking"
          className="absolute top-2 right-2 text-emerald-500"
        >
          <svg
            className="w-5 h-5"
            fill="currentColor"
            viewBox="0 0 20 20"
            aria-hidden="true"
          >
            <path
              fillRule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clipRule="evenodd"
            />
          </svg>
        </span>
      )}

      {/* Dismissed Status Indicator */}
      {isDismissed && (
        <span
          data-testid="dismissed-indicator"
          aria-label="Dismissed"
          className="text-sm text-muted-foreground italic"
        >
          Dismissed
        </span>
      )}

      {/* Action Buttons */}
      {showActions && (
        <div data-testid="action-buttons" className="flex gap-2 mt-3">
          <Button
            data-testid="track-button"
            size="sm"
            variant="default"
            onClick={() => onTrack?.(suggestion.id)}
            disabled={isLoading}
            aria-label={`Track ${suggestion.keyword}`}
          >
            {isLoading ? 'Tracking...' : 'Track'}
          </Button>
          <Button
            data-testid="dismiss-button"
            size="sm"
            variant="outline"
            onClick={() => onDismiss?.(suggestion.id)}
            disabled={isLoading}
            aria-label={`Dismiss ${suggestion.keyword}`}
          >
            Dismiss
          </Button>
        </div>
      )}

      {/* More Info Toggle */}
      <button
        data-testid="more-info-button"
        onClick={() => setIsExpanded(!isExpanded)}
        aria-expanded={isExpanded}
        aria-controls={`expanded-content-${suggestion.id}`}
        className="mt-3 text-sm text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-primary/20 rounded"
      >
        {isExpanded ? 'Less Info' : 'More Info'}
      </button>

      {/* Expanded View */}
      <div
        id={`expanded-content-${suggestion.id}`}
        data-testid="expanded-content"
        hidden={!isExpanded}
        className="mt-3 pt-3 border-t text-sm text-muted-foreground space-y-1"
      >
        <p data-testid="discovered-date">Discovered: {formattedDate}</p>
        <p data-testid="tracking-status">
          Status: {isTracked ? 'Tracking' : 'Not Tracking'}
        </p>
        {suggestion.searchVolume !== null && (
          <p>Search Volume: {suggestion.searchVolume.toLocaleString()}</p>
        )}
        {suggestion.difficultyScore !== null && (
          <p>Difficulty: {suggestion.difficultyScore}/100</p>
        )}
      </div>

      {/* Loading State Indicator */}
      <div
        data-testid="loading-indicator"
        hidden={!isLoading}
        className="mt-2 text-sm text-muted-foreground"
      >
        <span>Loading...</span>
      </div>
    </div>
  );
});

export { SuggestionCard };
