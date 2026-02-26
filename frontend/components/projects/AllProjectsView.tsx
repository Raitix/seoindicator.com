"use client";

import * as React from "react";
import Link from "next/link";
import {
  Plus,
  Search,
  TrendingUp,
  TrendingDown,
  Hash,
  Target,
  BarChart3,
  Loader2,
  AlertCircle,
  RefreshCw,
  ChevronUp,
  ChevronDown,
  ArrowUpDown,
  Archive,
  Eye,
  EyeOff,
} from "lucide-react";

import type { ProjectBreakdown, AggregateMetrics } from "@/lib/types/projects";
import { fetchAggregateMetrics } from "@/lib/api/projects";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";

type SortField = "name" | "keywords" | "position" | "visibility" | "change";
type SortOrder = "asc" | "desc";

interface AllProjectsViewProps {
  onCreateProject?: () => void;
  className?: string;
}

interface SortableHeaderProps {
  label: string;
  field: SortField;
  currentSort: SortField;
  sortOrder: SortOrder;
  onSort: (field: SortField) => void;
  className?: string;
}

function SortableHeader({
  label,
  field,
  currentSort,
  sortOrder,
  onSort,
  className,
}: SortableHeaderProps) {
  return (
    <button
      onClick={() => onSort(field)}
      className={cn(
        "flex items-center gap-1 text-left text-xs font-medium uppercase tracking-wide text-slate-400 hover:text-white",
        className
      )}
    >
      {label}
      {currentSort === field ? (
        sortOrder === "asc" ? (
          <ChevronUp className="h-3 w-3" />
        ) : (
          <ChevronDown className="h-3 w-3" />
        )
      ) : (
        <ArrowUpDown className="h-3 w-3 opacity-50" />
      )}
    </button>
  );
}

export function AllProjectsView({ onCreateProject, className }: AllProjectsViewProps) {
  const [aggregate, setAggregate] = React.useState<AggregateMetrics | null>(null);
  const [projects, setProjects] = React.useState<ProjectBreakdown[]>([]);
  const [isLoading, setIsLoading] = React.useState(true);
  const [error, setError] = React.useState<Error | null>(null);
  const [searchQuery, setSearchQuery] = React.useState("");
  const [sortField, setSortField] = React.useState<SortField>("visibility");
  const [sortOrder, setSortOrder] = React.useState<SortOrder>("desc");
  const [showArchived, setShowArchived] = React.useState(false);

  const fetchData = React.useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await fetchAggregateMetrics();
      setAggregate(data.aggregate);
      setProjects(data.projects);
    } catch (err) {
      setError(err instanceof Error ? err : new Error("Failed to fetch data"));
    } finally {
      setIsLoading(false);
    }
  }, []);

  React.useEffect(() => {
    fetchData();
  }, [fetchData]);

  // Filter and sort projects
  const filteredProjects = React.useMemo(() => {
    let filtered = projects.filter((p) =>
      p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      (p.domain?.toLowerCase().includes(searchQuery.toLowerCase()) ?? false)
    );

    if (!showArchived) {
      filtered = filtered.filter((p) => p.status === "active");
    }

    // Sort
    filtered.sort((a, b) => {
      let aVal: number | string;
      let bVal: number | string;

      switch (sortField) {
        case "name":
          aVal = a.name.toLowerCase();
          bVal = b.name.toLowerCase();
          break;
        case "keywords":
          aVal = a.metrics.keywordsCount;
          bVal = b.metrics.keywordsCount;
          break;
        case "position":
          aVal = a.metrics.avgPosition ?? 999;
          bVal = b.metrics.avgPosition ?? 999;
          break;
        case "visibility":
          aVal = a.metrics.visibility;
          bVal = b.metrics.visibility;
          break;
        case "change":
          aVal = Math.abs(a.metrics.change.visibility ?? 0);
          bVal = Math.abs(b.metrics.change.visibility ?? 0);
          break;
        default:
          return 0;
      }

      if (typeof aVal === "string") {
        return sortOrder === "asc"
          ? aVal.localeCompare(bVal as string)
          : (bVal as string).localeCompare(aVal);
      }

      return sortOrder === "asc" ? aVal - (bVal as number) : (bVal as number) - aVal;
    });

    return filtered;
  }, [projects, searchQuery, sortField, sortOrder, showArchived]);

  const handleSort = (field: SortField) => {
    if (sortField === field) {
      setSortOrder((prev) => (prev === "asc" ? "desc" : "asc"));
    } else {
      setSortField(field);
      setSortOrder("desc");
    }
  };

  // Loading state
  if (isLoading) {
    return (
      <div className={cn("space-y-6", className)}>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Card key={i} className="border-slate-800 bg-slate-900/60">
              <CardContent className="p-4">
                <div className="h-4 w-20 animate-pulse rounded bg-slate-700" />
                <div className="mt-2 h-7 w-16 animate-pulse rounded bg-slate-700" />
              </CardContent>
            </Card>
          ))}
        </div>
        <div className="animate-pulse space-y-3">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-14 rounded-md bg-slate-800/50" />
          ))}
        </div>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <Card className={cn("border-rose-500/40 bg-rose-500/10", className)}>
        <CardContent className="flex flex-col gap-3 p-6 text-rose-200">
          <div className="flex items-center gap-2">
            <AlertCircle className="h-5 w-5" />
            <span className="font-semibold">Failed to load projects</span>
          </div>
          <p className="text-sm text-rose-200/80">{error.message}</p>
          <Button
            variant="outline"
            className="w-fit border-rose-400 text-rose-100"
            onClick={fetchData}
          >
            <RefreshCw className="mr-2 h-4 w-4" />
            Retry
          </Button>
        </CardContent>
      </Card>
    );
  }

  // Empty state
  if (projects.length === 0) {
    return (
      <Card className={cn("border-slate-800 bg-slate-900/60", className)}>
        <CardContent className="flex flex-col items-center gap-4 p-10 text-center">
          <div className="rounded-full bg-emerald-500/20 p-4 text-emerald-400">
            <BarChart3 className="h-8 w-8" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-white">
              Create your first project
            </h3>
            <p className="mt-1 text-sm text-slate-400">
              Projects help you organize keywords and track SEO performance across multiple sites.
            </p>
          </div>
          {onCreateProject ? (
            <Button
              onClick={onCreateProject}
              className="bg-emerald-500 text-white hover:bg-emerald-600"
            >
              <Plus className="mr-2 h-4 w-4" />
              Create Project
            </Button>
          ) : (
            <Link
              href="/projects/new"
              className="inline-flex items-center rounded-md bg-emerald-500 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600"
            >
              <Plus className="mr-2 h-4 w-4" />
              Create Project
            </Link>
          )}
        </CardContent>
      </Card>
    );
  }

  return (
    <div className={cn("space-y-6", className)}>
      {/* Aggregate Metrics */}
      {aggregate && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card className="border-slate-800 bg-slate-900/60">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Total Projects
                  </p>
                  <p className="mt-1 text-2xl font-bold text-white">
                    {aggregate.totalProjects}
                  </p>
                </div>
                <BarChart3 className="h-5 w-5 text-slate-400" />
              </div>
            </CardContent>
          </Card>
          <Card className="border-slate-800 bg-slate-900/60">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Total Keywords
                  </p>
                  <p className="mt-1 text-2xl font-bold text-white">
                    {aggregate.totalKeywords.toLocaleString()}
                  </p>
                </div>
                <Hash className="h-5 w-5 text-slate-400" />
              </div>
            </CardContent>
          </Card>
          <Card className="border-slate-800 bg-slate-900/60">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Avg Position
                  </p>
                  <p className="mt-1 text-2xl font-bold text-white">
                    {aggregate.avgPosition ? `#${aggregate.avgPosition}` : "—"}
                  </p>
                </div>
                <Target className="h-5 w-5 text-slate-400" />
              </div>
            </CardContent>
          </Card>
          <Card className="border-slate-800 bg-slate-900/60">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Total Visibility
                  </p>
                  <p className="mt-1 text-2xl font-bold text-white">
                    {aggregate.totalVisibility.toFixed(1)}%
                  </p>
                </div>
                <TrendingUp className="h-5 w-5 text-emerald-400" />
              </div>
              {aggregate.visibilityChange !== null && (
                <div className="mt-2 flex items-center gap-1 text-xs">
                  {aggregate.visibilityChange > 0 ? (
                    <>
                      <TrendingUp className="h-3 w-3 text-emerald-400" />
                      <span className="text-emerald-400">
                        +{aggregate.visibilityChange.toFixed(1)}%
                      </span>
                    </>
                  ) : aggregate.visibilityChange < 0 ? (
                    <>
                      <TrendingDown className="h-3 w-3 text-rose-400" />
                      <span className="text-rose-400">
                        {aggregate.visibilityChange.toFixed(1)}%
                      </span>
                    </>
                  ) : (
                    <span className="text-slate-500">No change</span>
                  )}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      )}

      {/* Filters and Search */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="relative max-w-sm flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <Input
            placeholder="Search projects..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="border-slate-700 bg-slate-800/50 pl-10 text-white placeholder-slate-500"
          />
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setShowArchived(!showArchived)}
            className={cn(
              "text-slate-400",
              showArchived && "text-emerald-400"
            )}
          >
            {showArchived ? (
              <>
                <Eye className="mr-2 h-4 w-4" />
                Hide Archived
              </>
            ) : (
              <>
                <EyeOff className="mr-2 h-4 w-4" />
                Show Archived
              </>
            )}
          </Button>
          {onCreateProject && (
            <Button
              onClick={onCreateProject}
              size="sm"
              className="bg-emerald-500 text-white hover:bg-emerald-600"
            >
              <Plus className="mr-2 h-4 w-4" />
              New Project
            </Button>
          )}
        </div>
      </div>

      {/* Projects Table */}
      <div className="overflow-hidden rounded-lg border border-slate-700">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-700 bg-slate-800/50">
              <tr>
                <th className="px-4 py-3">
                  <SortableHeader
                    label="Project"
                    field="name"
                    currentSort={sortField}
                    sortOrder={sortOrder}
                    onSort={handleSort}
                  />
                </th>
                <th className="px-4 py-3">
                  <SortableHeader
                    label="Keywords"
                    field="keywords"
                    currentSort={sortField}
                    sortOrder={sortOrder}
                    onSort={handleSort}
                    className="text-right"
                  />
                </th>
                <th className="px-4 py-3">
                  <SortableHeader
                    label="Avg Position"
                    field="position"
                    currentSort={sortField}
                    sortOrder={sortOrder}
                    onSort={handleSort}
                    className="text-right"
                  />
                </th>
                <th className="px-4 py-3">
                  <SortableHeader
                    label="Visibility"
                    field="visibility"
                    currentSort={sortField}
                    sortOrder={sortOrder}
                    onSort={handleSort}
                    className="text-right"
                  />
                </th>
                <th className="px-4 py-3">
                  <SortableHeader
                    label="Change"
                    field="change"
                    currentSort={sortField}
                    sortOrder={sortOrder}
                    onSort={handleSort}
                    className="text-right"
                  />
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800">
              {filteredProjects.map((project) => (
                <tr
                  key={project.id}
                  className="group cursor-pointer bg-slate-900/40 transition-colors hover:bg-slate-800/50"
                  onClick={() => window.location.href = `/projects/${project.id}/dashboard`}
                >
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-3">
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-medium text-white group-hover:text-emerald-300">
                            {project.name}
                          </span>
                          {project.status === "archived" && (
                            <Archive className="h-3 w-3 text-slate-500" />
                          )}
                        </div>
                        {project.domain && (
                          <p className="text-xs text-slate-500">
                            {project.domain}
                          </p>
                        )}
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-right text-slate-300">
                    {project.metrics.keywordsCount.toLocaleString()}
                  </td>
                  <td className="px-4 py-3 text-right text-slate-300">
                    {project.metrics.avgPosition
                      ? `#${project.metrics.avgPosition}`
                      : "—"}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <span className="text-white">
                      {project.metrics.visibility.toFixed(1)}%
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    {project.metrics.change.visibility !== null ? (
                      <div className="flex items-center justify-end gap-1">
                        {project.metrics.change.visibility > 0 ? (
                          <>
                            <TrendingUp className="h-3 w-3 text-emerald-400" />
                            <span className="text-emerald-400">
                              +{project.metrics.change.visibility.toFixed(1)}%
                            </span>
                          </>
                        ) : (
                          <>
                            <TrendingDown className="h-3 w-3 text-rose-400" />
                            <span className="text-rose-400">
                              {project.metrics.change.visibility.toFixed(1)}%
                            </span>
                          </>
                        )}
                      </div>
                    ) : (
                      <span className="text-slate-500">—</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {filteredProjects.length === 0 && searchQuery && (
          <div className="p-8 text-center">
            <p className="text-slate-400">
              No projects matching "{searchQuery}"
            </p>
          </div>
        )}
      </div>
    </div>
  );
}

export default AllProjectsView;
