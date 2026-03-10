"use client";

import * as React from "react";
import { AlertTriangle, TrendingDown, TrendingUp, RefreshCw, FileText } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";
import { DecaySummaryData } from "@/lib/types/content-decay";

/**
 * Props for the DecaySummaryCards component
 */
export interface DecaySummaryCardsProps {
  /** Summary statistics for content decay */
  summary: DecaySummaryData | null;
  /** Loading state */
  isLoading?: boolean;
  /** Optional additional CSS classes */
  className?: string;
}

/**
 * Format large numbers with K, M suffixes
 */
function formatNumber(value: number): string {
  if (value >= 1000000) {
    return `${(value / 1000000).toFixed(1)}M`;
  }
  if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K`;
  }
  return value.toString();
}

/**
 * Individual summary card component
 */
interface SummaryCardProps {
  title: string;
  value: string | number;
  icon: React.ElementType;
  iconColor: string;
  description?: string;
  isLoading: boolean;
  ariaLabel: string;
}

function SummaryCard({
  title,
  value,
  icon: Icon,
  iconColor,
  description,
  isLoading,
  ariaLabel,
}: SummaryCardProps) {
  return (
    <Card className="relative overflow-hidden" role="article" aria-label={ariaLabel}>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">
          {title}
        </CardTitle>
        <Icon className={cn("h-4 w-4", iconColor)} aria-hidden="true" />
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <Skeleton className="h-8 w-20" />
        ) : (
          <div className="text-2xl font-bold">{value}</div>
        )}
        {description && !isLoading && (
          <p className="text-xs text-muted-foreground mt-1">{description}</p>
        )}
      </CardContent>
    </Card>
  );
}

/**
 * DecaySummaryCards displays a grid of summary statistics for the content
 * decay detection feature, including total candidates, critical count,
 * lost clicks, recovery upside, and recovered pages.
 *
 * @example
 * ```tsx
 * const summary = {
 *   totalCandidates: 45,
 *   criticalCount: 8,
 *   estimatedLostClicks: 1234,
 *   recoveryUpside: 5678,
 *   recoveredCount: 12
 * };
 *
 * <DecaySummaryCards summary={summary} />
 * ```
 */
export function DecaySummaryCards({
  summary,
  isLoading = false,
  className,
}: DecaySummaryCardsProps) {
  const cards = [
    {
      title: "Total Candidates",
      value: summary?.totalCandidates ?? 0,
      icon: FileText,
      iconColor: "text-blue-500",
      ariaLabel: `Total candidates: ${summary?.totalCandidates ?? 0} pages needing attention`,
    },
    {
      title: "Critical",
      value: summary?.criticalCount ?? 0,
      icon: AlertTriangle,
      iconColor: "text-red-500",
      ariaLabel: `Critical severity: ${summary?.criticalCount ?? 0} pages`,
      highlight: (summary?.criticalCount ?? 0) > 0,
    },
    {
      title: "Lost Clicks",
      value: formatNumber(summary?.estimatedLostClicks ?? 0),
      icon: TrendingDown,
      iconColor: "text-red-400",
      ariaLabel: `Estimated lost clicks: ${formatNumber(summary?.estimatedLostClicks ?? 0)}`,
    },
    {
      title: "Recovery Upside",
      value: formatNumber(summary?.recoveryUpside ?? 0),
      icon: TrendingUp,
      iconColor: "text-green-500",
      ariaLabel: `Potential recovery upside: ${formatNumber(summary?.recoveryUpside ?? 0)} clicks`,
    },
    {
      title: "Recovered",
      value: summary?.recoveredCount ?? 0,
      icon: RefreshCw,
      iconColor: "text-emerald-500",
      ariaLabel: `Successfully recovered pages: ${summary?.recoveredCount ?? 0}`,
    },
  ];

  return (
    <div
      className={cn("grid gap-4 grid-cols-2 md:grid-cols-3 lg:grid-cols-5", className)}
      role="region"
      aria-label="Content decay summary statistics"
    >
      {cards.map((card) => (
        <SummaryCard
          key={card.title}
          title={card.title}
          value={card.value}
          icon={card.icon}
          iconColor={card.iconColor}
          isLoading={isLoading}
          ariaLabel={card.ariaLabel}
        />
      ))}
    </div>
  );
}

export default DecaySummaryCards;
