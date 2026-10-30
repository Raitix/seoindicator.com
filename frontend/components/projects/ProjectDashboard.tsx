"use client";

import * as React from "react";
import Link from "next/link";
import {
  TrendingUp,
  TrendingDown,
  Hash,
  Target,
  BarChart3,
  RefreshCw,
  Loader2,
  AlertCircle,
  Calendar,
} from "lucide-react";

import type { Project, ProjectMetrics } from "@/lib/types/projects";
import { useProjectMetrics } from "@/lib/api/projects";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";

interface ProjectDashboardProps {
  project: Project;
  className?: string;
}

interface MetricCardProps {
  label: string;
  value: string | number | null;
  change?: number | null;
  icon: React.ReactNode;
  format?: "number" | "percentage" | "position";
  isLoading?: boolean;
}

function MetricCard({
  label,
  value,
  change,
  icon,
  format = "number",
  isLoading = false,
}: MetricCardProps) {
  const formatValue = (val: number | string | null): string => {
    if (val === null || val === undefined) return "—";
    if (format === "percentage") return `${val}%`;
    if (format === "position") return `#${val}`;
    return String(val);
  };

  const isPositive = change !== null && change !== undefined && change > 0;
  const isNegative = change !== null && change !== undefined && change < 0;

  return (
    <Card className="border-slate-800 bg-slate-900/60">
      <CardContent className="p-4">
        <div className="flex items-start justify-between">
          <div className="space-y-1">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
              {label}
            </p>
            {isLoading ? (
              <div className="h-7 w-16 animate-pulse rounded bg-slate-700" />
            ) : (
              <p className="text-2xl font-bold text-white">
                {formatValue(value)}
              </p>
            )}
          </div>
          <div className="rounded-md bg-slate-800 p-2 text-slate-400">
            {icon}
          </div>
        </div>
        {change !== undefined && !isLoading && (
          <div className="mt-2 flex items-center gap-1 text-xs">
            {isPositive && (
              <>
                <TrendingUp className="h-3 w-3 text-emerald-400" />
                <span className="text-emerald-400">+{change}</span>
              </>
            )}
            {isNegative && (
              <>
                <TrendingDown className="h-3 w-3 text-rose-400" />
                <span className="text-rose-400">{change}</span>
              </>
            )}
            {!isPositive && !isNegative && change !== null && (
              <span className="text-slate-500">No change</span>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  );
}

export function ProjectDashboard({ project, className }: ProjectDashboardProps) {
  const { metrics, isLoading, error, mutate } = useProjectMetrics(project.id);
  const [dateRange, setDateRange] = React.useState<"7d" | "30d" | "90d">("30d");

  const handleRefresh = () => {
    mutate();
  };

  if (error) {
    return (
      <Card className={cn("border-rose-500/40 bg-rose-500/10", className)}>
        <CardContent className="flex flex-col gap-3 p-6 text-rose-200">
          <div className="flex items-center gap-2">
            <AlertCircle className="h-5 w-5" />
            <span className="font-semibold">Failed to load metrics</span>
          </div>
          <p className="text-sm text-rose-200/80">
            {error.message ?? "Unable to fetch project metrics. Please try again."}
          </p>
          <Button
            variant="outline"
            className="w-fit border-rose-400 text-rose-100"
            onClick={handleRefresh}
          >
            <RefreshCw className="mr-2 h-4 w-4" />
            Retry
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className={cn("space-y-6", className)}>
      {/* Header with project info and actions */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-white">
            {project.name}
          </h1>
          {project.domain && (
            <p className="text-sm text-slate-400">{project.domain}</p>
          )}
        </div>
        <div className="flex items-center gap-2">
          {/* Date range selector */}
          <div className="flex rounded-md border border-slate-700 bg-slate-800/50 p-0.5">
            {(["7d", "30d", "90d"] as const).map((range) => (
              <button
                key={range}
                onClick={() => setDateRange(range)}
                className={cn(
                  "rounded px-3 py-1 text-xs font-medium transition-colors",
                  dateRange === range
                    ? "bg-emerald-500/20 text-emerald-300"
                    : "text-slate-400 hover:text-white"
                )}
              >
                {range === "7d" ? "7 days" : range === "30d" ? "30 days" : "90 days"}
              </button>
            ))}
          </div>
          <Button
            variant="ghost"
            size="icon"
            onClick={handleRefresh}
            disabled={isLoading}
            className="text-slate-400 hover:text-white"
          >
            <RefreshCw className={cn("h-4 w-4", isLoading && "animate-spin")} />
          </Button>
        </div>
      </div>

      {/* Metrics Grid */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <MetricCard
          label="Keywords"
          value={metrics?.keywordsCount ?? 0}
          icon={<Hash className="h-4 w-4" />}
          isLoading={isLoading}
        />
        <MetricCard
          label="Avg Position"
          value={metrics?.avgPosition ?? null}
          change={metrics?.change?.avgPosition ?? null}
          icon={<Target className="h-4 w-4" />}
          format="position"
          isLoading={isLoading}
        />
        <MetricCard
          label="Visibility"
          value={metrics?.visibility ?? 0}
          change={metrics?.change?.visibility ?? null}
          icon={<BarChart3 className="h-4 w-4" />}
          format="percentage"
          isLoading={isLoading}
        />
        <MetricCard
          label="Position Change"
          value={metrics?.change?.avgPosition ?? 0}
          icon={<TrendingUp className="h-4 w-4" />}
          format="number"
          isLoading={isLoading}
        />
      </div>

      {/* Quick Actions */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Link
          href={`/projects/${project.id}/keywords`}
          className="group flex items-center gap-4 rounded-lg border border-slate-700 bg-slate-800/50 p-4 transition-colors hover:border-emerald-500/50 hover:bg-slate-800"
        >
          <div className="rounded-md bg-emerald-500/20 p-3 text-emerald-400 transition-colors group-hover:bg-emerald-500/30">
            <Hash className="h-5 w-5" />
          </div>
          <div>
            <h3 className="font-medium text-white">Keywords</h3>
            <p className="text-sm text-slate-400">
              Manage tracked keywords
            </p>
          </div>
        </Link>

        <Link
          href={`/projects/${project.id}/competitors`}
          className="group flex items-center gap-4 rounded-lg border border-slate-700 bg-slate-800/50 p-4 transition-colors hover:border-emerald-500/50 hover:bg-slate-800"
        >
          <div className="rounded-md bg-blue-500/20 p-3 text-blue-400 transition-colors group-hover:bg-blue-500/30">
            <Target className="h-5 w-5" />
          </div>
          <div>
            <h3 className="font-medium text-white">Competitors</h3>
            <p className="text-sm text-slate-400">
              Track competitor rankings
            </p>
          </div>
        </Link>

        <Link
          href={`/projects/${project.id}/reports`}
          className="group flex items-center gap-4 rounded-lg border border-slate-700 bg-slate-800/50 p-4 transition-colors hover:border-emerald-500/50 hover:bg-slate-800"
        >
          <div className="rounded-md bg-purple-500/20 p-3 text-purple-400 transition-colors group-hover:bg-purple-500/30">
            <BarChart3 className="h-5 w-5" />
          </div>
          <div>
            <h3 className="font-medium text-white">Reports</h3>
            <p className="text-sm text-slate-400">
              Generate SEO reports
            </p>
          </div>
        </Link>
      </div>

      {/* Empty state for projects with no keywords */}
      {!isLoading && metrics?.keywordsCount === 0 && (
        <Card className="border-slate-800 bg-slate-900/60">
          <CardContent className="flex flex-col items-center gap-4 p-8 text-center">
            <div className="rounded-full bg-emerald-500/20 p-4 text-emerald-400">
              <Hash className="h-8 w-8" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-white">
                Start tracking keywords
              </h3>
              <p className="mt-1 text-sm text-slate-400">
                Add your first keywords to begin monitoring your SEO performance.
              </p>
            </div>
            <Link
              href={`/projects/${project.id}/keywords/new`}
              className="inline-flex items-center rounded-md bg-emerald-500 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600"
            >
              Add Keywords
            </Link>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

export default ProjectDashboard;
