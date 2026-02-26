"use client";

import * as React from "react";
import { 
  ArrowUp, 
  ArrowDown, 
  Clock, 
  RefreshCcw, 
  AlertCircle,
  ChevronDown,
  ChevronUp,
  History,
  TrendingUp,
} from "lucide-react";

import {
  useSerpFeatureHistory,
  getFeatureMeta,
  formatFeatureDate,
  getRelativeTime,
  isWithinDays,
  type SERPFeatureChange,
  type SERPFeatureType,
} from "@/lib/api/serp-features";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface SERPFeatureHistoryProps {
  keywordId: number;
  days?: number;
  showRecentWidget?: boolean;
  recentDays?: number;
  maxItems?: number;
  className?: string;
}

interface TimelineEntryProps {
  change: SERPFeatureChange;
  index: number;
}

const TimelineEntry: React.FC<TimelineEntryProps> = ({ change, index }) => {
  const meta = getFeatureMeta(change.type);
  const isGained = change.action === "gained";
  
  return (
    <div 
      className="flex items-start gap-3 py-3 border-b border-slate-800/50 last:border-0 animate-fadeIn"
      style={{ animationDelay: `${index * 50}ms` }}
    >
      <div className="flex-shrink-0 text-2xl" aria-hidden="true">
        {meta.icon}
      </div>
      <div className="flex-grow min-w-0">
        <div className="flex items-center gap-2 flex-wrap">
          <span className="font-medium text-white">{meta.name}</span>
          <span
            className={`inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full ${
              isGained
                ? "bg-emerald-500/15 text-emerald-300"
                : "bg-rose-500/15 text-rose-300"
            }`}
          >
            {isGained ? (
              <>
                <ArrowUp className="h-3 w-3" />
                Gained
              </>
            ) : (
              <>
                <ArrowDown className="h-3 w-3" />
                Lost
              </>
            )}
          </span>
        </div>
        <p className="text-sm text-slate-400 mt-0.5">{meta.description}</p>
      </div>
      <div className="flex-shrink-0 text-right">
        <time className="text-xs text-slate-500" dateTime={change.detectedAt}>
          {formatFeatureDate(change.detectedAt)}
        </time>
        <p className="text-xs text-slate-600 mt-0.5">
          {getRelativeTime(change.detectedAt)}
        </p>
      </div>
    </div>
  );
};

const LoadingSkeleton: React.FC = () => (
  <div className="space-y-3">
    {[1, 2, 3].map((i) => (
      <div key={i} className="flex items-start gap-3 py-3">
        <div className="w-8 h-8 bg-slate-800 rounded animate-pulse" />
        <div className="flex-grow space-y-2">
          <div className="h-4 bg-slate-800 rounded w-1/3 animate-pulse" />
          <div className="h-3 bg-slate-800 rounded w-1/2 animate-pulse" />
        </div>
        <div className="w-20 h-4 bg-slate-800 rounded animate-pulse" />
      </div>
    ))}
  </div>
);

const EmptyState: React.FC<{ message: string }> = ({ message }) => (
  <div className="flex flex-col items-center justify-center py-8 text-center">
    <History className="h-10 w-10 text-slate-600 mb-3" />
    <p className="text-slate-400">{message}</p>
  </div>
);

const ErrorState: React.FC<{ message: string; onRetry: () => void }> = ({
  message,
  onRetry,
}) => (
  <div className="flex flex-col items-center justify-center py-8 text-center">
    <AlertCircle className="h-10 w-10 text-rose-400 mb-3" />
    <p className="text-rose-300 mb-3">{message}</p>
    <Button
      variant="outline"
      size="sm"
      onClick={onRetry}
      className="border-rose-400/50 text-rose-200"
    >
      <RefreshCcw className="h-4 w-4 mr-2" />
      Retry
    </Button>
  </div>
);

const RecentChangesWidget: React.FC<{
  changes: SERPFeatureChange[];
  days: number;
  maxItems: number;
}> = ({ changes, days, maxItems }) => {
  const recentChanges = changes
    .filter((change) => isWithinDays(change.detectedAt, days))
    .slice(0, maxItems);

  if (recentChanges.length === 0) {
    return (
      <div className="rounded-lg border border-slate-800 bg-slate-900/50 p-4">
        <div className="flex items-center gap-2 mb-3">
          <TrendingUp className="h-4 w-4 text-slate-400" />
          <h4 className="text-sm font-medium text-slate-300">Recent Changes</h4>
          <span className="text-xs text-slate-500">(Last {days} days)</span>
        </div>
        <p className="text-sm text-slate-500">No recent changes detected.</p>
      </div>
    );
  }

  const gained = recentChanges.filter((c) => c.action === "gained").length;
  const lost = recentChanges.filter((c) => c.action === "lost").length;

  return (
    <div className="rounded-lg border border-slate-800 bg-slate-900/50 p-4">
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-2">
          <TrendingUp className="h-4 w-4 text-slate-400" />
          <h4 className="text-sm font-medium text-slate-300">Recent Changes</h4>
          <span className="text-xs text-slate-500">(Last {days} days)</span>
        </div>
        <div className="flex items-center gap-3 text-xs">
          {gained > 0 && (
            <span className="flex items-center gap-1 text-emerald-300">
              <ArrowUp className="h-3 w-3" />
              {gained} gained
            </span>
          )}
          {lost > 0 && (
            <span className="flex items-center gap-1 text-rose-300">
              <ArrowDown className="h-3 w-3" />
              {lost} lost
            </span>
          )}
        </div>
      </div>
      <div className="space-y-2">
        {recentChanges.map((change, index) => {
          const meta = getFeatureMeta(change.type);
          const isGained = change.action === "gained";
          return (
            <div
              key={`${change.type}-${change.detectedAt}-${index}`}
              className="flex items-center gap-2 text-sm"
            >
              <span className="text-base">{meta.icon}</span>
              <span className="text-slate-300">{meta.name}</span>
              <span
                className={`text-xs ${
                  isGained ? "text-emerald-400" : "text-rose-400"
                }`}
              >
                {isGained ? "gained" : "lost"}
              </span>
              <span className="text-slate-600 text-xs ml-auto">
                {getRelativeTime(change.detectedAt)}
              </span>
            </div>
          );
        })}
      </div>
    </div>
  );
};

const SERPFeatureHistory: React.FC<SERPFeatureHistoryProps> = ({
  keywordId,
  days = 30,
  showRecentWidget = true,
  recentDays = 7,
  maxItems = 10,
  className = "",
}) => {
  const [expanded, setExpanded] = React.useState(true);
  const [showAll, setShowAll] = React.useState(false);

  const { data, isLoading, error, mutate } = useSerpFeatureHistory(keywordId, {
    days,
  });

  const changes = data?.changes ?? [];
  const displayedChanges = showAll ? changes : changes.slice(0, maxItems);
  const hasMore = changes.length > maxItems;

  const toggleExpanded = () => setExpanded((prev) => !prev);
  const toggleShowAll = () => setShowAll((prev) => !prev);

  const renderContent = () => {
    if (isLoading) {
      return <LoadingSkeleton />;
    }

    if (error) {
      return (
        <ErrorState
          message={error.message || "Failed to load SERP feature history"}
          onRetry={() => mutate()}
        />
      );
    }

    if (!data || changes.length === 0) {
      return (
        <EmptyState message="No SERP feature changes recorded for this keyword." />
      );
    }

    return (
      <div className="space-y-4">
        {showRecentWidget && (
          <RecentChangesWidget
            changes={changes}
            days={recentDays}
            maxItems={5}
          />
        )}

        <div className="rounded-lg border border-slate-800 bg-slate-900/30">
          <div className="px-4 py-3 border-b border-slate-800">
            <div className="flex items-center justify-between">
              <h4 className="text-sm font-medium text-slate-300 flex items-center gap-2">
                <Clock className="h-4 w-4" />
                Timeline
              </h4>
              <span className="text-xs text-slate-500">{changes.length} changes</span>
            </div>
          </div>
          <div className="px-4 py-2">
            {displayedChanges.map((change, index) => (
              <TimelineEntry key={`${change.type}-${change.detectedAt}-${index}`} change={change} index={index} />
            ))}
          </div>
          {hasMore && !showAll && (
            <div className="px-4 py-3 border-t border-slate-800/50">
              <Button
                variant="ghost"
                size="sm"
                onClick={toggleShowAll}
                className="w-full text-slate-400 hover:text-white"
              >
                <ChevronDown className="h-4 w-4 mr-2" />
                Show {changes.length - maxItems} more changes
              </Button>
            </div>
          )}
          {showAll && hasMore && (
            <div className="px-4 py-3 border-t border-slate-800/50">
              <Button
                variant="ghost"
                size="sm"
                onClick={toggleShowAll}
                className="w-full text-slate-400 hover:text-white"
              >
                <ChevronUp className="h-4 w-4 mr-2" />
                Show less
              </Button>
            </div>
          )}
        </div>
      </div>
    );
  };

  return (
    <Card className={`border-slate-800 bg-slate-900/50 ${className}`}>
      <CardHeader className="cursor-pointer" onClick={toggleExpanded}>
        <div className="flex items-center justify-between">
          <div>
            <CardTitle className="text-lg flex items-center gap-2">
              <History className="h-5 w-5" />
              SERP Feature History
            </CardTitle>
            <p className="text-sm text-slate-400 mt-1">
              Track gained and lost SERP features over time.
            </p>
          </div>
          <Button variant="ghost" size="sm" className="text-slate-400">
            {expanded ? (
              <ChevronUp className="h-5 w-5" />
            ) : (
              <ChevronDown className="h-5 w-5" />
            )}
          </Button>
        </div>
      </CardHeader>
      {expanded && (
        <CardContent>
          {!isLoading && !error && data && (
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-4 text-sm">
                <span className="text-slate-400">
                  Current features:{" "}
                  <span className="text-white font-medium">
                    {data.currentFeatures.filter((f) => f.isPresent).length}
                  </span>
                </span>
              </div>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => mutate()}
                className="text-slate-400 hover:text-white"
              >
                <RefreshCcw className="h-4 w-4" />
              </Button>
            </div>
          )}
          {renderContent()}
        </CardContent>
      )}
    </Card>
  );
};

export default SERPFeatureHistory;
