/**
 * Content Decay Recovery Report Page
 * Shows recovery statistics, timeline, and before/after comparisons
 * 
 * @see TASK-36-13 for implementation requirements
 * @see SPEC-036 for architectural details
 */

"use client";

import * as React from "react";
import { useParams, useRouter } from "next/navigation";
import {
  LayoutDashboard,
  Kanban,
  BarChart3,
  TrendingUp,
  MousePointerClick,
  Clock,
  Target,
  Download,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { RecoveryComparisonCard } from "@/components/content-decay/RecoveryComparisonCard";
import {
  useContentDecayRecovery,
  useExportContentDecay,
} from "@/lib/hooks/useContentDecay";
import { cn } from "@/lib/utils";
import { toast } from "sonner";

type DateRangeValue = "7d" | "30d" | "90d" | "all";

const DATE_RANGE_OPTIONS: { value: DateRangeValue; label: string }[] = [
  { value: "7d", label: "Last 7 days" },
  { value: "30d", label: "Last 30 days" },
  { value: "90d", label: "Last 90 days" },
  { value: "all", label: "All time" },
];

function formatNumber(num: number): string {
  if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`;
  if (num >= 1000) return `${(num / 1000).toFixed(1)}K`;
  return num.toString();
}

function formatDate(dateStr: string): string {
  try {
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-US", { month: "short", day: "numeric" });
  } catch {
    return dateStr;
  }
}

interface StatCardProps {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  iconColor: string;
  description?: string;
}

function StatCard({ title, value, icon, iconColor, description }: StatCardProps) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">
          {title}
        </CardTitle>
        <div className={cn("p-2 rounded-lg", iconColor)}>{icon}</div>
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold" data-testid={`stat-${title.toLowerCase().replace(/\s+/g, "-")}`}>
          {value}
        </div>
        {description && (
          <p className="text-xs text-muted-foreground mt-1">{description}</p>
        )}
      </CardContent>
    </Card>
  );
}

export default function ContentDecayRecoveryPage() {
  const params = useParams();
  const router = useRouter();
  const siteId = params.siteId as string;

  // State
  const [dateRange, setDateRange] = React.useState<DateRangeValue>("30d");

  // Calculate date range for API
  const dateRangeParams = React.useMemo(() => {
    if (dateRange === "all") return undefined;
    
    const end = new Date();
    const start = new Date();
    
    switch (dateRange) {
      case "7d":
        start.setDate(start.getDate() - 7);
        break;
      case "30d":
        start.setDate(start.getDate() - 30);
        break;
      case "90d":
        start.setDate(start.getDate() - 90);
        break;
    }
    
    return {
      start: start.toISOString().split("T")[0],
      end: end.toISOString().split("T")[0],
    };
  }, [dateRange]);

  // Data hooks
  const { recovery, isLoading } = useContentDecayRecovery(siteId, dateRangeParams);
  const { exportData, isExporting } = useExportContentDecay(siteId);

  // Handlers
  const handleExportPdf = React.useCallback(async () => {
    const result = await exportData("pdf");
    if (result.success) {
      toast.success("Recovery report exported");
    } else {
      toast.error("Failed to export report");
    }
  }, [exportData]);

  // Loading state
  if (isLoading) {
    return (
      <div className="container mx-auto py-6 space-y-6" data-testid="loading-state">
        <div className="flex items-center justify-between">
          <Skeleton className="h-8 w-48" />
        </div>
        <div className="grid gap-4 sm:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-32" />
          ))}
        </div>
        <Skeleton className="h-64" />
        <Skeleton className="h-96" />
      </div>
    );
  }

  // Default recovery data for empty state
  const summary = recovery?.summary ?? {
    totalRecovered: 0,
    totalClicksRecovered: 0,
    avgRecoveryRate: 0,
    avgDaysToRecovery: 0,
  };
  const timeline = recovery?.timeline ?? [];
  const comparisons = recovery?.comparisons ?? [];
  const stories = recovery?.stories ?? [];

  return (
    <div className="container mx-auto py-6 space-y-6" data-testid="content-decay-recovery">
      {/* Header */}
      <header className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold" data-testid="page-title">
            Recovery Report
          </h1>
          <p className="text-muted-foreground">
            Track content refresh outcomes and recovered traffic
          </p>
        </div>

        <div className="flex items-center gap-3">
          <Select
            value={dateRange}
            onValueChange={(value) => setDateRange(value as DateRangeValue)}
          >
            <SelectTrigger
              className="w-32"
              aria-label="Date range"
              id="date-range-select"
              data-testid="date-range-filter"
            >
              <SelectValue placeholder="Date range" />
            </SelectTrigger>
            <SelectContent>
              {DATE_RANGE_OPTIONS.map((opt) => (
                <SelectItem key={opt.value} value={opt.value}>
                  {opt.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Button
            variant="outline"
            size="sm"
            onClick={handleExportPdf}
            disabled={isExporting}
            data-testid="export-pdf-button"
          >
            <Download className="h-4 w-4 mr-2" />
            Export PDF
          </Button>
        </div>
      </header>

      {/* Navigation Tabs */}
      <nav aria-label="Content decay navigation">
        <Tabs defaultValue="recovery" className="w-full">
          <TabsList data-testid="navigation-tabs">
            <TabsTrigger
              value="queue"
              onClick={() => router.push(`/sites/${siteId}/content-decay`)}
              className="flex items-center gap-2"
              aria-label="Refresh Queue"
            >
              <LayoutDashboard className="h-4 w-4" />
              Queue
            </TabsTrigger>
            <TabsTrigger
              value="board"
              onClick={() => router.push(`/sites/${siteId}/content-decay/board`)}
              className="flex items-center gap-2"
              aria-label="Recovery Board"
            >
              <Kanban className="h-4 w-4" />
              Board
            </TabsTrigger>
            <TabsTrigger
              value="recovery"
              onClick={() => router.push(`/sites/${siteId}/content-decay/recovery`)}
              className="flex items-center gap-2"
              aria-label="Recovery Report"
            >
              <BarChart3 className="h-4 w-4" />
              Recovery
            </TabsTrigger>
          </TabsList>
        </Tabs>
      </nav>

      {/* Summary Statistics */}
      <section aria-label="Recovery summary statistics">
        <div className="grid gap-4 sm:grid-cols-4">
          <StatCard
            title="Total Recovered"
            value={summary.totalRecovered}
            icon={<Target className="h-4 w-4 text-white" />}
            iconColor="bg-green-500"
          />
          <StatCard
            title="Clicks Recovered"
            value={formatNumber(summary.totalClicksRecovered)}
            icon={<MousePointerClick className="h-4 w-4 text-white" />}
            iconColor="bg-blue-500"
          />
          <StatCard
            title="Avg Recovery Rate"
            value={`${Math.round(summary.avgRecoveryRate)}%`}
            icon={<TrendingUp className="h-4 w-4 text-white" />}
            iconColor="bg-purple-500"
          />
          <StatCard
            title="Avg Days to Recovery"
            value={summary.avgDaysToRecovery}
            icon={<Clock className="h-4 w-4 text-white" />}
            iconColor="bg-orange-500"
          />
        </div>
      </section>

      {/* Recovery Timeline Chart */}
      <section aria-label="Recovery timeline">
        <Card>
          <CardHeader>
            <CardTitle>Recovery Timeline</CardTitle>
          </CardHeader>
          <CardContent>
            {timeline.length > 0 ? (
              <div className="space-y-4">
                {/* Bar Chart Visualization */}
                <div className="flex items-end gap-2 h-48" role="img" aria-label="Recovery timeline bar chart">
                  {timeline.slice(-14).map((item, index) => {
                    const maxClicks = Math.max(...timeline.map((t) => t.clicks), 1);
                    const heightPercent = (item.clicks / maxClicks) * 100;
                    
                    return (
                      <div
                        key={index}
                        className="flex-1 flex flex-col items-center"
                        data-testid={`timeline-bar-${index}`}
                      >
                        <div className="text-xs text-muted-foreground mb-1">
                          {formatNumber(item.clicks)}
                        </div>
                        <div
                          className="w-full bg-green-500 rounded-t-sm min-h-[4px] transition-all hover:bg-green-400"
                          style={{ height: `${Math.max(heightPercent, 4)}%` }}
                          title={`${formatDate(item.date)}: ${item.recovered} recovered, ${formatNumber(item.clicks)} clicks`}
                        />
                        <div className="text-xs text-muted-foreground mt-1 truncate w-full text-center">
                          {formatDate(item.date)}
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            ) : (
              <div className="text-center text-muted-foreground py-8">
                No recovery data available for the selected period
              </div>
            )}
          </CardContent>
        </Card>
      </section>

      {/* Before/After Comparison Table */}
      <section aria-label="Before and after comparison">
        <Card>
          <CardHeader>
            <CardTitle>Before & After Comparison</CardTitle>
          </CardHeader>
          <CardContent>
            {comparisons.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full text-sm" role="table" aria-label="Before and after comparison table">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left py-3 px-2 font-medium">Page</th>
                      <th className="text-right py-3 px-2 font-medium">Clicks Before</th>
                      <th className="text-right py-3 px-2 font-medium">Clicks After</th>
                      <th className="text-right py-3 px-2 font-medium">Change</th>
                      <th className="text-right py-3 px-2 font-medium">Position Before</th>
                      <th className="text-right py-3 px-2 font-medium">Position After</th>
                    </tr>
                  </thead>
                  <tbody>
                    {comparisons.slice(0, 10).map((comp, index) => (
                      <tr key={comp.id} className="border-b hover:bg-muted/50">
                        <td className="py-3 px-2">
                          <div className="max-w-xs truncate font-medium">
                            {comp.pageTitle || comp.pageUrl}
                          </div>
                        </td>
                        <td className="text-right py-3 px-2 text-muted-foreground">
                          {formatNumber(comp.before.clicks)}
                        </td>
                        <td className="text-right py-3 px-2">
                          {formatNumber(comp.after.clicks)}
                        </td>
                        <td className="text-right py-3 px-2">
                          <span
                            className={cn(
                              "font-medium",
                              comp.delta.clicks > 0 ? "text-green-600" : "text-red-600"
                            )}
                            data-testid={`delta-clicks-${index}`}
                          >
                            {comp.delta.clicks > 0 ? "+" : ""}
                            {formatNumber(comp.delta.clicks)}
                          </span>
                        </td>
                        <td className="text-right py-3 px-2 text-muted-foreground">
                          {comp.before.position.toFixed(1)}
                        </td>
                        <td className="text-right py-3 px-2">
                          {comp.after.position.toFixed(1)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="text-center text-muted-foreground py-8">
                No comparison data available
              </div>
            )}
          </CardContent>
        </Card>
      </section>

      {/* Recovery Stories */}
      <section aria-label="Recovery stories">
        <Card>
          <CardHeader>
            <CardTitle>Recovery Stories</CardTitle>
          </CardHeader>
          <CardContent>
            {stories.length > 0 ? (
              <div className="grid gap-4 sm:grid-cols-2">
                {stories.map((story) => (
                  <div
                    key={story.id}
                    className="p-4 border rounded-lg space-y-2"
                    data-testid={`story-${story.id}`}
                  >
                    <div className="font-medium truncate">
                      {story.pageTitle || story.pageUrl}
                    </div>
                    <div className="flex items-center gap-4 text-sm">
                      <div className="text-green-600 font-medium">
                        +{formatNumber(story.clicksRecovered)} clicks
                      </div>
                      <div className="text-muted-foreground">
                        {Math.round(story.recoveryRate)}% recovery
                      </div>
                      <div className="text-muted-foreground">
                        {story.daysToRecovery} days
                      </div>
                    </div>
                    {story.summary && (
                      <p className="text-sm text-muted-foreground">
                        {story.summary}
                      </p>
                    )}
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center text-muted-foreground py-8">
                No recovery stories yet. Completed refreshes will appear here.
              </div>
            )}
          </CardContent>
        </Card>
      </section>
    </div>
  );
}
