"use client";

import { Suspense, useState, useMemo } from "react";
import { useParams, useRouter } from "next/navigation";
import { Metadata } from "next";
import { ArrowLeft, Download, TrendingUp, BarChart3, Calendar, ArrowUpRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { RecoveryComparisonCard } from "@/components/content-decay";
import {
  useContentDecayRecovery,
  useExportContentDecay,
} from "@/lib/hooks/useContentDecay";
import { cn } from "@/lib/utils";

export const metadata: Metadata = {
  title: "Recovery Report | SEOIndicator",
  description: "Track content recovery progress and outcomes.",
};

function formatNumber(num: number): string {
  if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`;
  if (num >= 1000) return `${(num / 1000).toFixed(1)}K`;
  return num.toString();
}

function formatDate(dateStr: string): string {
  try {
    return new Date(dateStr).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  } catch {
    return dateStr;
  }
}

function StatCard({
  title,
  value,
  icon,
  trend,
  description,
}: {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  trend?: "up" | "down" | "neutral";
  description?: string;
}) {
  return (
    <Card role="article" aria-label={title}>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">
          {title}
        </CardTitle>
        <span className="text-muted-foreground">{icon}</span>
      </CardHeader>
      <CardContent>
        <div
          className={cn(
            "text-2xl font-bold tabular-nums",
            trend === "up" && "text-green-500",
            trend === "down" && "text-red-500"
          )}
          data-testid={`stat-value-${title.toLowerCase().replace(/\s+/g, "-")}`}
        >
          {value}
        </div>
        {description && (
          <p className="text-xs text-muted-foreground mt-1">{description}</p>
        )}
      </CardContent>
    </Card>
  );
}

function TimelineChart({
  data,
}: {
  data: Array<{ date: string; recovered: number; clicks: number }>;
}) {
  if (!data || data.length === 0) {
    return (
      <div className="text-center text-muted-foreground py-8" data-testid="no-timeline-data">
        No timeline data available
      </div>
    );
  }

  const maxRecovered = Math.max(...data.map((d) => d.recovered), 1);
  const maxClicks = Math.max(...data.map((d) => d.clicks), 1);

  return (
    <div
      className="space-y-4"
      data-testid="timeline-chart"
      role="img"
      aria-label="Recovery timeline chart"
    >
      {/* Simple bar chart representation */}
      <div className="flex items-end gap-2 h-40 border-b border-slate-800 pb-4">
        {data.slice(-14).map((point, i) => {
          const heightPercent = (point.recovered / maxRecovered) * 100;
          return (
            <div
              key={i}
              className="flex-1 flex flex-col items-center gap-1"
              data-testid={`timeline-bar-${i}`}
            >
              <div
                className="w-full bg-green-500/30 hover:bg-green-500/50 transition-colors rounded-t relative group"
                style={{ height: `${Math.max(heightPercent, 5)}%` }}
              >
                <div className="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 whitespace-nowrap">
                  {point.recovered} recovered
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* X-axis labels */}
      <div className="flex gap-2 text-xs text-muted-foreground">
        {data.slice(-14).map((point, i) => (
          <div key={i} className="flex-1 text-center truncate">
            {i % 3 === 0 ? formatDate(point.date).split(",")[0] : ""}
          </div>
        ))}
      </div>
    </div>
  );
}

function RecoveryStories({
  stories,
}: {
  stories: Array<{
    id: number;
    pageUrl: string;
    pageTitle: string;
    clicksRecovered: number;
    recoveryRate: number;
    daysToRecovery: number;
    summary: string;
  }>;
}) {
  if (!stories || stories.length === 0) {
    return (
      <div className="text-center text-muted-foreground py-8" data-testid="no-stories">
        No recovery stories yet
      </div>
    );
  }

  return (
    <div className="space-y-4" data-testid="recovery-stories" role="list">
      {stories.slice(0, 5).map((story) => (
        <Card
          key={story.id}
          role="listitem"
          className="hover:border-primary/50 transition-colors"
          data-testid={`story-${story.id}`}
        >
          <CardContent className="p-4">
            <div className="flex items-start justify-between gap-4">
              <div className="flex-1 min-w-0">
                <h4 className="font-medium truncate">{story.pageTitle || "Untitled"}</h4>
                <a
                  href={story.pageUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm text-muted-foreground hover:text-primary truncate block"
                >
                  {story.pageUrl}
                </a>
                <p className="text-sm text-muted-foreground mt-2">{story.summary}</p>
              </div>
              <div className="text-right shrink-0">
                <div className="text-green-500 font-bold">
                  +{formatNumber(story.clicksRecovered)}
                </div>
                <div className="text-xs text-muted-foreground">
                  {Math.round(story.recoveryRate * 100)}% recovered
                </div>
                <Badge variant="outline" className="mt-1">
                  {story.daysToRecovery} days
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function ComparisonTable({
  comparisons,
}: {
  comparisons: Array<{
    id: number;
    pageUrl: string;
    pageTitle: string;
    pageType: string;
    before: { clicks: number; impressions: number; ctr: number; position: number };
    after: { clicks: number; impressions: number; ctr: number; position: number };
    delta: { clicks: number; impressions: number; ctr: number; position: number };
  }>;
}) {
  if (!comparisons || comparisons.length === 0) {
    return (
      <div className="text-center text-muted-foreground py-8" data-testid="no-comparisons">
        No comparison data available
      </div>
    );
  }

  return (
    <div className="overflow-x-auto" data-testid="comparison-table">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-slate-800">
            <th className="text-left p-3 font-medium text-muted-foreground">Page</th>
            <th className="text-right p-3 font-medium text-muted-foreground">Before</th>
            <th className="text-right p-3 font-medium text-muted-foreground">After</th>
            <th className="text-right p-3 font-medium text-muted-foreground">Change</th>
          </tr>
        </thead>
        <tbody>
          {comparisons.slice(0, 10).map((comp) => (
            <tr
              key={comp.id}
              className="border-b border-slate-800/50 hover:bg-slate-900/50"
              data-testid={`comparison-row-${comp.id}`}
            >
              <td className="p-3">
                <div className="font-medium truncate max-w-xs">{comp.pageTitle || "Untitled"}</div>
                <a
                  href={comp.pageUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-xs text-muted-foreground hover:text-primary truncate block"
                >
                  {comp.pageUrl}
                </a>
              </td>
              <td className="p-3 text-right text-muted-foreground">
                {formatNumber(comp.before.clicks)} clicks
              </td>
              <td className="p-3 text-right font-medium">
                {formatNumber(comp.after.clicks)} clicks
              </td>
              <td className="p-3 text-right">
                <span
                  className={cn(
                    "font-medium",
                    comp.delta.clicks >= 0 ? "text-green-500" : "text-red-500"
                  )}
                >
                  {comp.delta.clicks >= 0 ? "+" : ""}{formatNumber(comp.delta.clicks)}
                </span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function RecoveryReportContent({ siteId }: { siteId: string }) {
  const router = useRouter();
  const [dateRange, setDateRange] = useState<"7d" | "30d" | "90d" | "all">("30d");

  // Calculate date range
  const dateParams = useMemo(() => {
    if (dateRange === "all") return undefined;
    const end = new Date();
    const start = new Date();
    const days = dateRange === "7d" ? 7 : dateRange === "30d" ? 30 : 90;
    start.setDate(start.getDate() - days);
    return {
      start: start.toISOString().split("T")[0],
      end: end.toISOString().split("T")[0],
    };
  }, [dateRange]);

  const { recovery, isLoading } = useContentDecayRecovery(siteId, dateParams);
  const { exportData, isExporting } = useExportContentDecay(siteId);

  const handleExport = async () => {
    await exportData("pdf");
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => router.push(`/sites/${siteId}/content-decay`)}
            data-testid="back-button"
          >
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to Queue
          </Button>
          <div>
            <h1 className="text-2xl font-bold text-white">Recovery Report</h1>
            <p className="text-sm text-slate-400">
              Track your content recovery outcomes
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <Select
            value={dateRange}
            onValueChange={(v) => setDateRange(v as typeof dateRange)}
          >
            <SelectTrigger className="w-32" aria-label="Select date range">
              <SelectValue placeholder="Date Range" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="7d">Last 7 days</SelectItem>
              <SelectItem value="30d">Last 30 days</SelectItem>
              <SelectItem value="90d">Last 90 days</SelectItem>
              <SelectItem value="all">All time</SelectItem>
            </SelectContent>
          </Select>
          <Button
            variant="outline"
            size="sm"
            onClick={handleExport}
            disabled={isExporting}
            data-testid="export-button"
          >
            <Download className="h-4 w-4 mr-2" />
            Export PDF
          </Button>
        </div>
      </div>

      {/* Summary Statistics */}
      <section aria-label="Recovery statistics" data-testid="stats-section">
        <div className="grid gap-4 md:grid-cols-4">
          {isLoading ? (
            Array.from({ length: 4 }).map((_, i) => (
              <Skeleton key={i} className="h-28 w-full" data-testid="skeleton" />
            ))
          ) : (
            <>
              <StatCard
                title="Total Recovered"
                value={recovery?.summary?.totalRecovered ?? 0}
                icon={<TrendingUp className="h-4 w-4 text-green-500" />}
                trend="up"
                description="Pages successfully recovered"
              />
              <StatCard
                title="Clicks Recovered"
                value={formatNumber(recovery?.summary?.totalClicksRecovered ?? 0)}
                icon={<ArrowUpRight className="h-4 w-4 text-green-500" />}
                trend="up"
                description="Estimated traffic restored"
              />
              <StatCard
                title="Avg Recovery Rate"
                value={`${Math.round((recovery?.summary?.avgRecoveryRate ?? 0) * 100)}%`}
                icon={<BarChart3 className="h-4 w-4 text-blue-500" />}
                description="Average performance restored"
              />
              <StatCard
                title="Avg Days to Recovery"
                value={recovery?.summary?.avgDaysToRecovery ?? 0}
                icon={<Calendar className="h-4 w-4 text-purple-500" />}
                description="Average time to recover"
              />
            </>
          )}
        </div>
      </section>

      {/* Timeline Chart */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Recovery Timeline</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <Skeleton className="h-40 w-full" data-testid="skeleton" />
          ) : (
            <TimelineChart data={recovery?.timeline ?? []} />
          )}
        </CardContent>
      </Card>

      {/* Two Column Layout */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Before/After Comparison */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Before/After Comparison</CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="space-y-2">
                {Array.from({ length: 5 }).map((_, i) => (
                  <Skeleton key={i} className="h-12 w-full" data-testid="skeleton" />
                ))}
              </div>
            ) : (
              <ComparisonTable comparisons={recovery?.comparisons ?? []} />
            )}
          </CardContent>
        </Card>

        {/* Recovery Stories */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Recovery Stories</CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="space-y-2">
                {Array.from({ length: 3 }).map((_, i) => (
                  <Skeleton key={i} className="h-24 w-full" data-testid="skeleton" />
                ))}
              </div>
            ) : (
              <RecoveryStories stories={recovery?.stories ?? []} />
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

function LoadingSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <Skeleton className="h-10 w-48" data-testid="skeleton" />
        <div className="flex gap-2">
          <Skeleton className="h-10 w-32" data-testid="skeleton" />
          <Skeleton className="h-10 w-32" data-testid="skeleton" />
        </div>
      </div>
      <div className="grid gap-4 md:grid-cols-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <Skeleton key={i} className="h-28 w-full" data-testid="skeleton" />
        ))}
      </div>
      <Skeleton className="h-48 w-full" data-testid="skeleton" />
      <div className="grid gap-6 lg:grid-cols-2">
        <Skeleton className="h-64 w-full" data-testid="skeleton" />
        <Skeleton className="h-64 w-full" data-testid="skeleton" />
      </div>
    </div>
  );
}

export default function RecoveryReportPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
      <div className="mx-auto w-full max-w-7xl">
        <Suspense fallback={<LoadingSkeleton />}>
          <RecoveryReportContent siteId={siteId} />
        </Suspense>
      </div>
    </div>
  );
}
