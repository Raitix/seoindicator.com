/**
 * Content Decay Recovery Report Page
 * Shows recovery statistics, timeline, and before/after comparisons
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 */

"use client";

import * as React from "react";
import { useParams, useRouter } from "next/navigation";
import {
  Settings,
  LayoutDashboard,
  KanbanSquare,
  BarChart3,
  Download,
  TrendingUp,
  TrendingDown,
  Clock,
  Target,
  Calendar,
  ExternalLink,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { RecoveryComparisonCard } from "@/components/content-decay/RecoveryComparisonCard";
import { useContentDecayRecovery } from "@/lib/hooks/useContentDecay";
import { cn } from "@/lib/utils";

// ============================================================
// Loading Skeleton
// ============================================================

function PageSkeleton() {
  return (
    <div className="space-y-6 p-6" data-testid="page-skeleton">
      <div className="flex items-center justify-between">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-10 w-64" />
      </div>
      <div className="grid grid-cols-4 gap-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <Skeleton key={i} className="h-24" data-testid="skeleton" />
        ))}
      </div>
      <Skeleton className="h-64" />
      <Skeleton className="h-96" />
    </div>
  );
}

// ============================================================
// Navigation Tabs
// ============================================================

interface NavTabsProps {
  siteId: string;
  activeTab: string;
}

function NavTabs({ siteId, activeTab }: NavTabsProps) {
  const router = useRouter();

  return (
    <Tabs value={activeTab} className="w-auto">
      <TabsList>
        <TabsTrigger
          value="queue"
          onClick={() => router.push(`/sites/${siteId}/content-decay`)}
          className="gap-1.5"
        >
          <LayoutDashboard className="h-4 w-4" />
          Queue
        </TabsTrigger>
        <TabsTrigger
          value="board"
          onClick={() => router.push(`/sites/${siteId}/content-decay/board`)}
          className="gap-1.5"
        >
          <KanbanSquare className="h-4 w-4" />
          Board
        </TabsTrigger>
        <TabsTrigger
          value="recovery"
          onClick={() => router.push(`/sites/${siteId}/content-decay/recovery`)}
          className="gap-1.5"
        >
          <BarChart3 className="h-4 w-4" />
          Recovery
        </TabsTrigger>
      </TabsList>
    </Tabs>
  );
}

// ============================================================
// Summary Stat Card
// ============================================================

interface StatCardProps {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  description?: string;
  trend?: "up" | "down" | "neutral";
  className?: string;
}

function StatCard({ title, value, icon, description, trend, className }: StatCardProps) {
  return (
    <Card className={cn("bg-slate-800/50 border-slate-700", className)}>
      <CardContent className="p-4">
        <div className="flex items-center justify-between">
          <span className="text-sm text-slate-400">{title}</span>
          {icon}
        </div>
        <div className="mt-2">
          <span
            className={cn(
              "text-2xl font-bold",
              trend === "up" && "text-green-400",
              trend === "down" && "text-red-400"
            )}
          >
            {value}
          </span>
          {description && (
            <p className="text-xs text-slate-500 mt-1">{description}</p>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

// ============================================================
// Timeline Chart (Simple Bar Visualization)
// ============================================================

interface TimelineChartProps {
  data: Array<{
    date: string;
    recovered: number;
    clicks: number;
  }>;
}

function TimelineChart({ data }: TimelineChartProps) {
  if (!data || data.length === 0) {
    return (
      <div className="text-center text-slate-500 py-12">
        No timeline data available
      </div>
    );
  }

  const maxValue = Math.max(...data.map((d) => d.recovered), 1);

  return (
    <div className="space-y-4">
      {/* Chart */}
      <div className="flex items-end gap-2 h-40" data-testid="timeline-chart">
        {data.slice(-14).map((item, index) => {
          const height = (item.recovered / maxValue) * 100;
          return (
            <div
              key={index}
              className="flex-1 flex flex-col items-center gap-1"
              data-testid={`bar-${index}`}
            >
              <div
                className="w-full bg-green-500/80 rounded-t"
                style={{ height: `${Math.max(height, 4)}%` }}
                title={`${item.recovered} recovered on ${item.date}`}
              />
              <span className="text-xs text-slate-500 truncate w-full text-center">
                {new Date(item.date).toLocaleDateString("en-US", { month: "short", day: "numeric" })}
              </span>
            </div>
          );
        })}
      </div>

      {/* Legend */}
      <div className="flex items-center justify-center gap-4 text-xs text-slate-400">
        <div className="flex items-center gap-1">
          <div className="w-3 h-3 bg-green-500/80 rounded" />
          <span>Pages Recovered</span>
        </div>
      </div>
    </div>
  );
}

// ============================================================
// Comparison Table
// ============================================================

interface ComparisonTableProps {
  comparisons: Array<{
    id: number;
    pageUrl: string;
    pageTitle: string;
    pageType: string;
    before: {
      clicks: number;
      impressions: number;
      ctr: number;
      position: number;
    };
    after: {
      clicks: number;
      impressions: number;
      ctr: number;
      position: number;
    };
    delta: {
      clicks: number;
      impressions: number;
      ctr: number;
      position: number;
    };
  }>;
}

function ComparisonTable({ comparisons }: ComparisonTableProps) {
  if (!comparisons || comparisons.length === 0) {
    return (
      <div className="text-center text-slate-500 py-12">
        No recovery comparisons available
      </div>
    );
  }

  const formatNumber = (n: number) => n.toLocaleString();
  const formatPercent = (n: number) => `${(n * 100).toFixed(2)}%`;

  return (
    <div className="rounded-lg border border-slate-700 overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow className="bg-slate-800/50 hover:bg-slate-800/50">
            <TableHead className="text-slate-300">Page</TableHead>
            <TableHead className="text-slate-300 text-right">Before Clicks</TableHead>
            <TableHead className="text-slate-300 text-right">After Clicks</TableHead>
            <TableHead className="text-slate-300 text-right">Clicks Change</TableHead>
            <TableHead className="text-slate-300 text-right">Position Change</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {comparisons.map((item) => (
            <TableRow key={item.id} className="border-slate-700">
              <TableCell>
                <div className="max-w-xs">
                  <p className="text-sm font-medium text-slate-200 truncate">
                    {item.pageTitle || "Untitled"}
                  </p>
                  <a
                    href={item.pageUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-xs text-slate-400 hover:text-slate-300 flex items-center gap-1"
                  >
                    {item.pageUrl.length > 40
                      ? item.pageUrl.substring(0, 40) + "..."
                      : item.pageUrl}
                    <ExternalLink className="h-3 w-3" />
                  </a>
                </div>
              </TableCell>
              <TableCell className="text-right text-slate-400">
                {formatNumber(item.before.clicks)}
              </TableCell>
              <TableCell className="text-right text-slate-300 font-medium">
                {formatNumber(item.after.clicks)}
              </TableCell>
              <TableCell className="text-right">
                <span
                  className={cn(
                    "font-medium",
                    item.delta.clicks >= 0 ? "text-green-400" : "text-red-400"
                  )}
                >
                  {item.delta.clicks >= 0 ? "+" : ""}
                  {formatNumber(item.delta.clicks)}
                </span>
              </TableCell>
              <TableCell className="text-right">
                <span
                  className={cn(
                    "font-medium",
                    item.delta.position <= 0 ? "text-green-400" : "text-red-400"
                  )}
                >
                  {item.delta.position <= 0 ? "+" : ""}
                  {Math.abs(item.delta.position).toFixed(1)}
                </span>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}

// ============================================================
// Recovery Stories
// ============================================================

interface RecoveryStoriesProps {
  stories: Array<{
    id: number;
    pageUrl: string;
    pageTitle: string;
    clicksRecovered: number;
    recoveryRate: number;
    daysToRecovery: number;
    summary: string;
  }>;
}

function RecoveryStories({ stories }: RecoveryStoriesProps) {
  if (!stories || stories.length === 0) {
    return (
      <div className="text-center text-slate-500 py-12">
        No recovery stories available yet
      </div>
    );
  }

  return (
    <div className="grid gap-4 md:grid-cols-2" data-testid="recovery-stories">
      {stories.slice(0, 4).map((story) => (
        <Card
          key={story.id}
          className="bg-slate-800/50 border-slate-700"
          data-testid={`story-${story.id}`}
        >
          <CardContent className="p-4 space-y-3">
            {/* Title */}
            <div>
              <h4 className="text-sm font-medium text-slate-200 truncate">
                {story.pageTitle || "Untitled"}
              </h4>
              <a
                href={story.pageUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="text-xs text-slate-400 hover:text-slate-300 flex items-center gap-1"
              >
                View page <ExternalLink className="h-3 w-3" />
              </a>
            </div>

            {/* Metrics */}
            <div className="grid grid-cols-3 gap-2">
              <div className="text-center p-2 rounded bg-slate-700/50">
                <div className="text-lg font-bold text-green-400">
                  +{story.clicksRecovered.toLocaleString()}
                </div>
                <div className="text-xs text-slate-400">Clicks Recovered</div>
              </div>
              <div className="text-center p-2 rounded bg-slate-700/50">
                <div className="text-lg font-bold text-slate-200">
                  {Math.round(story.recoveryRate * 100)}%
                </div>
                <div className="text-xs text-slate-400">Recovery Rate</div>
              </div>
              <div className="text-center p-2 rounded bg-slate-700/50">
                <div className="text-lg font-bold text-slate-200">
                  {story.daysToRecovery}
                </div>
                <div className="text-xs text-slate-400">Days</div>
              </div>
            </div>

            {/* Summary */}
            {story.summary && (
              <p className="text-xs text-slate-400 line-clamp-2">
                {story.summary}
              </p>
            )}
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

// ============================================================
// Recovery Report Content
// ============================================================

function RecoveryReportContent({ siteId }: { siteId: string }) {
  const router = useRouter();

  // Date range state
  const [dateRange, setDateRange] = React.useState<"7d" | "30d" | "90d" | "all">("30d");

  // Calculate date range for API
  const dateRangeParams = React.useMemo(() => {
    if (dateRange === "all") return undefined;

    const end = new Date();
    const start = new Date();

    switch (dateRange) {
      case "7d":
        start.setDate(end.getDate() - 7);
        break;
      case "30d":
        start.setDate(end.getDate() - 30);
        break;
      case "90d":
        start.setDate(end.getDate() - 90);
        break;
    }

    return {
      start: start.toISOString().split("T")[0],
      end: end.toISOString().split("T")[0],
    };
  }, [dateRange]);

  // Hooks
  const { recovery, isLoading } = useContentDecayRecovery(siteId, dateRangeParams);

  // Export handler (placeholder)
  const handleExport = () => {
    // Would call export API
    console.log("Export recovery report");
  };

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900">
        <PageSkeleton />
      </div>
    );
  }

  const summary = recovery?.summary ?? {
    totalRecovered: 0,
    totalClicksRecovered: 0,
    avgRecoveryRate: 0,
    avgDaysToRecovery: 0,
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900">
      <div className="container mx-auto px-4 py-6">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div>
            <h1 className="text-2xl font-bold text-white flex items-center gap-2">
              <BarChart3 className="h-6 w-6 text-green-500" />
              Recovery Report
            </h1>
            <p className="text-sm text-slate-400">
              Track content recovery progress and results
            </p>
          </div>

          <div className="flex items-center gap-3">
            <NavTabs siteId={siteId} activeTab="recovery" />
            <Button
              variant="outline"
              size="sm"
              onClick={() => router.push(`/sites/${siteId}/content-decay/settings`)}
              className="border-slate-700 text-slate-300"
            >
              <Settings className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={handleExport}
              className="border-slate-700 text-slate-300"
            >
              <Download className="h-4 w-4 mr-2" />
              Export PDF
            </Button>
          </div>
        </div>

        {/* Date Range Filter */}
        <div className="flex items-center gap-2 mb-6">
          <Calendar className="h-4 w-4 text-slate-400" aria-hidden="true" />
          <label htmlFor="date-range-select" className="sr-only">Date range</label>
          <Select value={dateRange} onValueChange={(v) => setDateRange(v as typeof dateRange)}>
            <SelectTrigger 
              id="date-range-select"
              className="w-32 bg-slate-800 border-slate-700 text-slate-200"
              aria-label="Date range"
            >
              <SelectValue placeholder="Date Range" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="7d">Last 7 days</SelectItem>
              <SelectItem value="30d">Last 30 days</SelectItem>
              <SelectItem value="90d">Last 90 days</SelectItem>
              <SelectItem value="all">All time</SelectItem>
            </SelectContent>
          </Select>
        </div>

        {/* Summary Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" data-testid="summary-stats">
          <StatCard
            title="Total Recovered"
            value={summary.totalRecovered}
            icon={<TrendingUp className="h-4 w-4 text-green-500" />}
            trend="up"
          />
          <StatCard
            title="Clicks Recovered"
            value={summary.totalClicksRecovered.toLocaleString()}
            icon={<Target className="h-4 w-4 text-blue-500" />}
            trend="up"
          />
          <StatCard
            title="Avg Recovery Rate"
            value={`${Math.round(summary.avgRecoveryRate * 100)}%`}
            icon={<BarChart3 className="h-4 w-4 text-purple-500" />}
          />
          <StatCard
            title="Avg Days to Recovery"
            value={summary.avgDaysToRecovery.toFixed(1)}
            icon={<Clock className="h-4 w-4 text-amber-500" />}
          />
        </div>

        {/* Timeline Chart */}
        <Card className="bg-slate-800/50 border-slate-700 mb-6">
          <CardHeader>
            <CardTitle className="text-slate-200">Recovery Timeline</CardTitle>
          </CardHeader>
          <CardContent>
            <TimelineChart data={recovery?.timeline ?? []} />
          </CardContent>
        </Card>

        {/* Comparison Table */}
        <Card className="bg-slate-800/50 border-slate-700 mb-6">
          <CardHeader>
            <CardTitle className="text-slate-200">Before/After Comparison</CardTitle>
          </CardHeader>
          <CardContent>
            <ComparisonTable comparisons={recovery?.comparisons ?? []} />
          </CardContent>
        </Card>

        {/* Recovery Stories */}
        <Card className="bg-slate-800/50 border-slate-700">
          <CardHeader>
            <CardTitle className="text-slate-200">Recovery Stories</CardTitle>
          </CardHeader>
          <CardContent>
            <RecoveryStories stories={recovery?.stories ?? []} />
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

// ============================================================
// Page Component
// ============================================================

export default function RecoveryReportPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  if (!siteId) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center">
        <p className="text-slate-400">Site not found</p>
      </div>
    );
  }

  return <RecoveryReportContent siteId={siteId} />;
}
