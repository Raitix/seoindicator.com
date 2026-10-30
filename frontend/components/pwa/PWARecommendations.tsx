"use client";

import * as React from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  CheckCircle,
  AlertCircle,
  ArrowUpCircle,
  ExternalLink,
  ChevronDown,
  BookOpen,
} from "lucide-react";
import { cn } from "@/lib/utils";
import type { PWARecommendation } from "@/lib/types/pwa";

/**
 * Props for PWARecommendations component
 */
export interface PWARecommendationsProps {
  recommendations: PWARecommendation[];
  className?: string;
  onGuideClick?: (guide: string) => void;
  maxVisible?: number;
}

/**
 * Extended recommendation with sorting index for stable sort
 */
interface IndexedRecommendation extends PWARecommendation {
  _originalIndex: number;
}

/**
 * Priority configuration for styling and icons
 */
const PRIORITY_CONFIG = {
  high: {
    icon: AlertCircle,
    label: "High",
    color: "text-red-400",
    bgColor: "bg-red-500/10",
    borderColor: "border-red-500/20",
  },
  medium: {
    icon: ArrowUpCircle,
    label: "Medium",
    color: "text-yellow-400",
    bgColor: "bg-yellow-500/10",
    borderColor: "border-yellow-500/20",
  },
  low: {
    icon: CheckCircle,
    label: "Low",
    color: "text-emerald-400",
    bgColor: "bg-emerald-500/10",
    borderColor: "border-emerald-500/20",
  },
} as const;

/**
 * Priority order for sorting (lower = higher priority)
 */
const PRIORITY_ORDER: Record<PWARecommendation["priority"], number> = {
  high: 0,
  medium: 1,
  low: 2,
};

/**
 * Sort recommendations by priority (high first, then medium, then low)
 * Maintains stable sort within same priority
 */
function sortRecommendations(
  recommendations: PWARecommendation[]
): IndexedRecommendation[] {
  return recommendations
    .map((rec, index) => ({ ...rec, _originalIndex: index }))
    .sort((a, b) => {
      const priorityDiff = PRIORITY_ORDER[a.priority] - PRIORITY_ORDER[b.priority];
      return priorityDiff !== 0 ? priorityDiff : a._originalIndex - b._originalIndex;
    });
}

/**
 * Group recommendations by priority
 */
function groupByPriority(
  recommendations: IndexedRecommendation[]
): Record<PWARecommendation["priority"], IndexedRecommendation[]> {
  return {
    high: recommendations.filter((r) => r.priority === "high"),
    medium: recommendations.filter((r) => r.priority === "medium"),
    low: recommendations.filter((r) => r.priority === "low"),
  };
}

/**
 * Check if reduced motion is preferred
 */
function usePrefersReducedMotion(): boolean {
  const [prefersReduced, setPrefersReduced] = React.useState(false);

  React.useEffect(() => {
    const mediaQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
    setPrefersReduced(mediaQuery.matches);

    const handler = (e: MediaQueryListEvent) => setPrefersReduced(e.matches);
    mediaQuery.addEventListener("change", handler);
    return () => mediaQuery.removeEventListener("change", handler);
  }, []);

  return prefersReduced;
}

/**
 * Individual recommendation item component
 */
interface RecommendationItemProps {
  recommendation: IndexedRecommendation;
  onGuideClick?: (guide: string) => void;
  showAnimation?: boolean;
  animationDelay?: number;
}

const RecommendationItem = React.memo(function RecommendationItem({
  recommendation,
  onGuideClick,
  showAnimation = false,
  animationDelay = 0,
}: RecommendationItemProps) {
  const config = PRIORITY_CONFIG[recommendation.priority];
  const Icon = config.icon;

  const handleGuideClick = React.useCallback(
    (e: React.MouseEvent) => {
      e.preventDefault();
      if (recommendation.guide) {
        onGuideClick?.(recommendation.guide);
      }
    },
    [recommendation.guide, onGuideClick]
  );

  return (
    <li
      role="listitem"
      className={cn(
        "flex items-start gap-3 py-2",
        showAnimation && "animate-fade-in"
      )}
      style={showAnimation ? { animationDelay: `${animationDelay}ms` } : undefined}
    >
      {/* Priority Badge */}
      <Badge
        variant="outline"
        className={cn(
          "flex-shrink-0 gap-1 px-2 py-1",
          config.color,
          config.bgColor,
          config.borderColor
        )}
        aria-label={`${config.label} priority`}
      >
        <Icon className="h-3 w-3" aria-hidden="true" />
        <span className="sr-only">{config.label}</span>
        <span aria-hidden="true">{config.label}</span>
      </Badge>

      {/* Message Content */}
      <div className="flex-1 min-w-0">
        <p className="text-sm text-slate-300 break-words">
          {recommendation.message}
        </p>
        {recommendation.field && (
          <span className="text-xs text-slate-500 mt-0.5 block">
            Field: {recommendation.field}
          </span>
        )}
      </div>

      {/* Guide Link */}
      {recommendation.guide && (
        <button
          onClick={handleGuideClick}
          className={cn(
            "flex-shrink-0 p-2 rounded-lg transition-colors",
            "text-slate-400 hover:text-emerald-400 hover:bg-slate-800",
            "min-h-[44px] min-w-[44px] flex items-center justify-center"
          )}
          aria-label={`Learn more about ${recommendation.field || recommendation.type}`}
        >
          <ExternalLink className="h-4 w-4" aria-hidden="true" />
        </button>
      )}
    </li>
  );
});

/**
 * Priority section component (expandable/collapsible)
 */
interface PrioritySectionProps {
  priority: PWARecommendation["priority"];
  recommendations: IndexedRecommendation[];
  defaultExpanded: boolean;
  onGuideClick?: (guide: string) => void;
  showAnimation?: boolean;
  startDelay?: number;
}

function PrioritySection({
  priority,
  recommendations,
  defaultExpanded,
  onGuideClick,
  showAnimation = false,
  startDelay = 0,
}: PrioritySectionProps) {
  const config = PRIORITY_CONFIG[priority];
  const Icon = config.icon;
  const [isExpanded, setIsExpanded] = React.useState(defaultExpanded);
  const contentRef = React.useRef<HTMLDivElement>(null);
  const prefersReduced = usePrefersReducedMotion();

  const toggleExpanded = React.useCallback(() => {
    setIsExpanded((prev) => !prev);
  }, []);

  const handleKeyDown = React.useCallback(
    (e: React.KeyboardEvent) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        toggleExpanded();
      }
    },
    [toggleExpanded]
  );

  if (recommendations.length === 0) return null;

  return (
    <div className="border-b border-slate-800 last:border-b-0">
      {/* Section Header */}
      <button
        type="button"
        onClick={toggleExpanded}
        onKeyDown={handleKeyDown}
        className={cn(
          "w-full flex items-center justify-between py-3 px-4",
          "text-left hover:bg-slate-800/50 transition-colors",
          "min-h-[44px]"
        )}
        aria-expanded={isExpanded}
        aria-controls={`section-content-${priority}`}
      >
        <div className="flex items-center gap-2">
          <Icon className={cn("h-4 w-4", config.color)} aria-hidden="true" />
          <span className={cn("font-medium", config.color)}>
            {config.label}
          </span>
          <Badge
            variant="outline"
            className="text-xs text-slate-400 border-slate-700 bg-slate-800/50"
          >
            {recommendations.length}
          </Badge>
        </div>
        <ChevronDown
          className={cn(
            "h-4 w-4 text-slate-400 transition-transform duration-200",
            isExpanded && "rotate-180"
          )}
          aria-hidden="true"
        />
      </button>

      {/* Section Content */}
      <div
        id={`section-content-${priority}`}
        ref={contentRef}
        className={cn(
          "overflow-hidden transition-all duration-200",
          prefersReduced && "transition-none",
          isExpanded ? "max-h-[2000px] opacity-100" : "max-h-0 opacity-0"
        )}
        role="region"
        aria-hidden={!isExpanded}
      >
        <ul role="list" className="px-4 pb-3 space-y-1">
          {recommendations.map((rec, idx) => (
            <RecommendationItem
              key={`${rec.type}-${rec._originalIndex}`}
              recommendation={rec}
              onGuideClick={onGuideClick}
              showAnimation={showAnimation && isExpanded}
              animationDelay={startDelay + idx * 50}
            />
          ))}
        </ul>
      </div>
    </div>
  );
}

/**
 * Empty state component
 */
function EmptyState() {
  const prefersReduced = usePrefersReducedMotion();

  return (
    <div
      className={cn(
        "flex flex-col items-center justify-center py-8 text-center",
        !prefersReduced && "animate-fade-in"
      )}
    >
      <div
        className={cn(
          "flex items-center justify-center w-16 h-16 rounded-full mb-4",
          "bg-emerald-500/10",
          !prefersReduced && "animate-pulse-slow"
        )}
      >
        <CheckCircle
          className="h-8 w-8 text-emerald-400"
          aria-hidden="true"
        />
      </div>
      <p className="text-lg font-medium text-emerald-400">
        All checks passed!
      </p>
      <p className="text-sm text-slate-400 mt-1">
        Your PWA is configured correctly. Great job!
      </p>
    </div>
  );
}

/**
 * PWARecommendations Component
 *
 * Displays actionable PWA improvement suggestions grouped by priority.
 * Features:
 * - Sorted by priority (high → medium → low)
 * - Expandable/collapsible sections
 * - Guide links for each recommendation
 * - Empty state for perfect PWAs
 * - Responsive design
 * - Full accessibility support
 */
export function PWARecommendations({
  recommendations,
  className,
  onGuideClick,
  maxVisible,
}: PWARecommendationsProps) {
  // Handle undefined/null recommendations
  const safeRecommendations = recommendations ?? [];
  const isEmpty = safeRecommendations.length === 0;

  // Sort and group recommendations
  const sortedRecommendations = React.useMemo(
    () => sortRecommendations(safeRecommendations),
    [safeRecommendations]
  );

  const groupedRecommendations = React.useMemo(
    () => groupByPriority(sortedRecommendations),
    [sortedRecommendations]
  );

  // Handle maxVisible
  const [showAll, setShowAll] = React.useState(false);
  const displayRecommendations = React.useMemo(() => {
    if (!maxVisible || showAll) return sortedRecommendations;
    return sortedRecommendations.slice(0, maxVisible);
  }, [sortedRecommendations, maxVisible, showAll]);

  const hiddenCount = maxVisible
    ? sortedRecommendations.length - displayRecommendations.length
    : 0;

  // Determine default expansion for sections
  const totalItems = safeRecommendations.length;
  const defaultExpandAll = totalItems <= 3;

  // Handle "Show More" click
  const handleShowMore = React.useCallback(() => {
    setShowAll((prev) => !prev);
  }, []);

  // If few items, render as flat list without sections
  if (!isEmpty && totalItems <= 3) {
    return (
      <Card
        className={cn("border-slate-800 bg-slate-900/60", className)}
        data-testid="pwa-recommendations"
        aria-label="PWA Recommendations"
      >
        <CardHeader>
          <CardTitle className="text-sm font-medium text-slate-300">
            Recommendations
          </CardTitle>
        </CardHeader>
        <CardContent>
          <ul role="list" className="space-y-2">
            {displayRecommendations.map((rec) => (
              <RecommendationItem
                key={`${rec.type}-${rec._originalIndex}`}
                recommendation={rec}
                onGuideClick={onGuideClick}
                showAnimation
              />
            ))}
          </ul>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card
      className={cn("border-slate-800 bg-slate-900/60", className)}
      data-testid="pwa-recommendations"
      aria-label="PWA Recommendations"
    >
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="text-sm font-medium text-slate-300">
            Recommendations
          </CardTitle>
          {!isEmpty && (
            <Button
              variant="ghost"
              size="sm"
              asChild
              className="text-emerald-400 hover:text-emerald-300 hover:bg-slate-800"
            >
              <a
                href="/guides/pwa"
                aria-label="View full PWA guide"
              >
                <BookOpen className="h-4 w-4 mr-1" aria-hidden="true" />
                View Guide
              </a>
            </Button>
          )}
        </div>
      </CardHeader>
      <CardContent className="p-0">
        {isEmpty ? (
          <EmptyState />
        ) : (
          <>
            {/* Priority Sections */}
            <div className="divide-y divide-slate-800">
              {(["high", "medium", "low"] as const).map((priority) => (
                <PrioritySection
                  key={priority}
                  priority={priority}
                  recommendations={
                    showAll || !maxVisible
                      ? groupedRecommendations[priority]
                      : groupedRecommendations[priority].filter((r) =>
                          displayRecommendations.some(
                            (d) => d._originalIndex === r._originalIndex
                          )
                        )
                  }
                  defaultExpanded={
                    defaultExpandAll || priority === "high"
                  }
                  onGuideClick={onGuideClick}
                  showAnimation
                />
              ))}
            </div>

            {/* Show More Button */}
            {maxVisible && hiddenCount > 0 && !showAll && (
              <div className="p-4 border-t border-slate-800">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={handleShowMore}
                  className="w-full text-slate-300 border-slate-700 hover:bg-slate-800"
                  aria-label={`Show ${hiddenCount} more recommendations`}
                >
                  +{hiddenCount} more recommendations
                </Button>
              </div>
            )}

            {/* Show Less Button */}
            {maxVisible && showAll && sortedRecommendations.length > maxVisible && (
              <div className="p-4 border-t border-slate-800">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={handleShowMore}
                  className="w-full text-slate-300 border-slate-700 hover:bg-slate-800"
                  aria-label="Show fewer recommendations"
                >
                  Show less
                </Button>
              </div>
            )}
          </>
        )}
      </CardContent>
    </Card>
  );
}

/**
 * Skeleton component for loading state
 */
export function PWARecommendationsSkeleton() {
  return (
    <Card className="border-slate-800 bg-slate-900/60">
      <CardHeader>
        <div className="h-4 w-24 animate-pulse rounded bg-slate-700" />
      </CardHeader>
      <CardContent className="space-y-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="flex items-start gap-3">
            <div className="h-6 w-16 animate-pulse rounded bg-slate-700" />
            <div className="flex-1 space-y-1">
              <div className="h-4 w-full animate-pulse rounded bg-slate-700" />
              <div className="h-3 w-1/2 animate-pulse rounded bg-slate-700" />
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  );
}

export default PWARecommendations;
