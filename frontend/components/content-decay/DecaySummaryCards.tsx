/**
 * DecaySummaryCards Component
 * Displays summary statistics for content decay in card format
 * 
 * @see TASK-36-11 for implementation requirements
 */

import * as React from "react";
import {
  AlertTriangle,
  TrendingDown,
  TrendingUp,
  RefreshCw,
  FileText,
} from "lucide-react";
import { Skeleton } from "@/components/ui/skeleton";
import type { DecaySummaryData } from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export interface DecaySummaryCardsProps {
  /** Summary statistics data */
  summary: DecaySummaryData | null | undefined;
  /** Loading state */
  isLoading?: boolean;
  /** Additional CSS classes */
  className?: string;
}

function formatNumber(num: number): string {
  if (num >= 1000000000) {
    return `${(num / 1000000000).toFixed(1)}B`;
  }
  if (num >= 1000000) {
    return `${(num / 1000000).toFixed(1)}M`;
  }
  if (num >= 1000) {
    return `${(num / 1000).toFixed(1)}K`;
  }
  return num.toString();
}

interface SummaryCardProps {
  title: string;
  value: number | string;
  icon: React.ReactNode;
  ariaLabel: string;
  valueClassName?: string;
  isLoading?: boolean;
}

function SummaryCard({
  title,
  value,
  icon,
  ariaLabel,
  valueClassName,
  isLoading,
}: SummaryCardProps) {
  return (
    <article
      role="article"
      aria-label={ariaLabel}
      className="rounded-lg border bg-card p-4 shadow-sm"
    >
      <div className="flex items-center justify-between space-y-0 pb-2">
        <span className="text-sm font-medium text-muted-foreground">{title}</span>
        <span className="text-muted-foreground">{icon}</span>
      </div>
      {isLoading ? (
        <Skeleton className="h-8 w-20" data-testid="skeleton" />
      ) : (
        <div
          className={cn(
            "text-2xl font-bold tabular-nums",
            valueClassName
          )}
        >
          {value}
        </div>
      )}
    </article>
  );
}

export function DecaySummaryCards({
  summary,
  isLoading = false,
  className,
}: DecaySummaryCardsProps) {
  // Default to zeros for null/undefined summary
  const data: DecaySummaryData = summary ?? {
    totalCandidates: 0,
    criticalCount: 0,
    highCount: 0,
    mediumCount: 0,
    lowCount: 0,
    estimatedLostClicks: 0,
    recoveryUpside: 0,
    recoveredCount: 0,
    lastScannedAt: null,
  };

  const cards: SummaryCardProps[] = [
    {
      title: "Total Candidates",
      value: data.totalCandidates,
      icon: <FileText className="h-4 w-4" data-testid="file-text-icon" />,
      ariaLabel: `Total candidates: ${data.totalCandidates}`,
    },
    {
      title: "Critical",
      value: data.criticalCount,
      icon: <AlertTriangle className="h-4 w-4 text-red-500" data-testid="alert-triangle-icon" />,
      ariaLabel: `Critical severity: ${data.criticalCount}`,
      valueClassName: data.criticalCount > 0 ? "text-red-600" : undefined,
    },
    {
      title: "Lost Clicks",
      value: formatNumber(data.estimatedLostClicks),
      icon: <TrendingDown className="h-4 w-4 text-red-500" data-testid="trending-down-icon" />,
      ariaLabel: `Estimated lost clicks: ${formatNumber(data.estimatedLostClicks)}`,
      valueClassName: "text-red-600",
    },
    {
      title: "Recovery Upside",
      value: formatNumber(data.recoveryUpside),
      icon: <TrendingUp className="h-4 w-4 text-green-500" data-testid="trending-up-icon" />,
      ariaLabel: `Potential recovery upside: ${formatNumber(data.recoveryUpside)}`,
      valueClassName: "text-green-600",
    },
    {
      title: "Recovered",
      value: data.recoveredCount,
      icon: <RefreshCw className="h-4 w-4 text-green-500" data-testid="refresh-cw-icon" />,
      ariaLabel: `Successfully recovered pages: ${data.recoveredCount}`,
      valueClassName: "text-green-600",
    },
  ];

  return (
    <section
      role="region"
      aria-label="Content decay summary statistics"
      className={cn("grid gap-4 md:grid-cols-5", className)}
    >
      {cards.map((card, index) => (
        <SummaryCard
          key={index}
          {...card}
          isLoading={isLoading}
        />
      ))}
    </section>
  );
}
