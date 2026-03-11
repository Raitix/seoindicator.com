/**
 * DecayQueueTable Component
 * Table displaying decay candidates with filtering, sorting, and bulk actions
 * 
 * @see TASK-36-11 for implementation requirements
 */

"use client";

import * as React from "react";
import { useState, useMemo, useCallback } from "react";
import {
  ChevronDown,
  ChevronUp,
  Search,
  ExternalLink,
  MoreHorizontal,
  CheckSquare,
  Square,
  Download,
} from "lucide-react";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Checkbox } from "@/components/ui/checkbox";
import { SeverityBadge } from "./SeverityBadge";
import { DecayReasonBadges } from "./DecayReasonBadges";
import { DecayTrendMiniChart } from "./DecayTrendMiniChart";
import {
  type DecayCandidate,
  type ContentDecayCandidateStatus,
  type ContentDecaySeverity,
  type ContentDecayPageType,
  PAGE_TYPE_CONFIG,
  VALID_STATUS_TRANSITIONS,
} from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export interface DecayQueueTableProps {
  /** Array of decay candidates to display */
  candidates: DecayCandidate[];
  /** Loading state */
  isLoading?: boolean;
  /** Number of items per page */
  pageSize?: number;
  /** Callback when row is clicked */
  onRowClick?: (candidate: DecayCandidate) => void;
  /** Callback when bulk status update is triggered */
  onBulkUpdate?: (ids: number[], newStatus: ContentDecayCandidateStatus) => void;
  /** Callback when export is triggered */
  onExport?: () => void;
  /** Additional CSS classes */
  className?: string;
}

const STATUS_OPTIONS: { value: ContentDecayCandidateStatus | "all"; label: string }[] = [
  { value: "all", label: "All Statuses" },
  { value: "backlog", label: "Backlog" },
  { value: "planned", label: "Planned" },
  { value: "in_progress", label: "In Progress" },
  { value: "refreshed", label: "Refreshed" },
  { value: "recovered", label: "Recovered" },
  { value: "ignored", label: "Ignored" },
];

const SEVERITY_OPTIONS: { value: ContentDecaySeverity | "all"; label: string }[] = [
  { value: "all", label: "All Severities" },
  { value: "critical", label: "Critical" },
  { value: "high", label: "High" },
  { value: "medium", label: "Medium" },
  { value: "low", label: "Low" },
];

const PAGE_TYPE_OPTIONS: { value: ContentDecayPageType | "all"; label: string }[] = [
  { value: "all", label: "All Types" },
  { value: "home", label: "Homepage" },
  { value: "blog", label: "Blog" },
  { value: "landing", label: "Landing Page" },
  { value: "product", label: "Product" },
  { value: "tool", label: "Tool" },
  { value: "compare", label: "Comparison" },
  { value: "docs", label: "Documentation" },
  { value: "other", label: "Other" },
];

function formatNumber(num: number): string {
  if (num >= 1000000) {
    return `${(num / 1000000).toFixed(1)}M`;
  }
  if (num >= 1000) {
    return `${(num / 1000).toFixed(1)}K`;
  }
  return num.toString();
}

function truncateUrl(url: string, maxLength: number = 50): string {
  if (url.length <= maxLength) return url;
  const start = url.substring(0, maxLength / 2 - 2);
  const end = url.substring(url.length - maxLength / 2 + 2);
  return `${start}...${end}`;
}

interface FilterState {
  status: ContentDecayCandidateStatus | "all";
  severity: ContentDecaySeverity | "all";
  pageType: ContentDecayPageType | "all";
  search: string;
}

type SortKey = "decay_score" | "lost_click_estimate" | "recovery_upside_estimate" | "last_detected_at";
type SortOrder = "asc" | "desc";

export function DecayQueueTable({
  candidates,
  isLoading = false,
  pageSize = 10,
  onRowClick,
  onBulkUpdate,
  onExport,
  className,
}: DecayQueueTableProps) {
  const [filters, setFilters] = useState<FilterState>({
    status: "all",
    severity: "all",
    pageType: "all",
    search: "",
  });
  
  const [sortKey, setSortKey] = useState<SortKey>("decay_score");
  const [sortOrder, setSortOrder] = useState<SortOrder>("desc");
  
  const [currentPage, setCurrentPage] = useState(1);
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());

  // Filter and sort candidates
  const filteredCandidates = useMemo(() => {
    let result = candidates.filter((c) => {
      if (filters.status !== "all" && c.status !== filters.status) return false;
      if (filters.severity !== "all" && c.severity !== filters.severity) return false;
      if (filters.pageType !== "all" && c.pageType !== filters.pageType) return false;
      if (filters.search) {
        const searchLower = filters.search.toLowerCase();
        if (
          !c.pageUrl.toLowerCase().includes(searchLower) &&
          !(c.pageTitle?.toLowerCase().includes(searchLower) ?? false)
        ) {
          return false;
        }
      }
      return true;
    });

    // Sort
    result.sort((a, b) => {
      let aVal: number | string = 0;
      let bVal: number | string = 0;
      
      switch (sortKey) {
        case "decay_score":
          aVal = a.decayScore;
          bVal = b.decayScore;
          break;
        case "lost_click_estimate":
          aVal = a.lostClickEstimate;
          bVal = b.recoveryUpsideEstimate;
          break;
        case "recovery_upside_estimate":
          aVal = a.recoveryUpsideEstimate;
          bVal = b.recoveryUpsideEstimate;
          break;
        case "last_detected_at":
          aVal = a.lastDetectedAt;
          bVal = b.lastDetectedAt;
          break;
      }
      
      if (typeof aVal === "string") {
        return sortOrder === "asc"
          ? aVal.localeCompare(bVal as string)
          : (bVal as string).localeCompare(aVal);
      }
      
      return sortOrder === "asc" ? aVal - (bVal as number) : (bVal as number) - aVal;
    });

    return result;
  }, [candidates, filters, sortKey, sortOrder]);

  // Pagination
  const totalPages = Math.ceil(filteredCandidates.length / pageSize);
  const paginatedCandidates = filteredCandidates.slice(
    (currentPage - 1) * pageSize,
    currentPage * pageSize
  );

  // Selection handlers
  const handleSelectAll = useCallback(() => {
    if (selectedIds.size === paginatedCandidates.length) {
      setSelectedIds(new Set());
    } else {
      setSelectedIds(new Set(paginatedCandidates.map((c) => c.id)));
    }
  }, [paginatedCandidates, selectedIds.size]);

  const handleSelectRow = useCallback((id: number) => {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  }, []);

  // Sorting handler
  const handleSort = useCallback((key: SortKey) => {
    if (sortKey === key) {
      setSortOrder((prev) => (prev === "asc" ? "desc" : "asc"));
    } else {
      setSortKey(key);
      setSortOrder("desc");
    }
  }, [sortKey]);

  // Bulk update handler
  const handleBulkUpdate = useCallback((newStatus: ContentDecayCandidateStatus) => {
    if (onBulkUpdate && selectedIds.size > 0) {
      onBulkUpdate(Array.from(selectedIds), newStatus);
      setSelectedIds(new Set());
    }
  }, [onBulkUpdate, selectedIds]);

  // Render loading state
  if (isLoading) {
    return (
      <div className={cn("space-y-4", className)}>
        <div className="flex gap-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-10 w-32" data-testid="skeleton" />
          ))}
        </div>
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="h-16 w-full" data-testid="skeleton" />
          ))}
        </div>
      </div>
    );
  }

  // Render empty state
  if (candidates.length === 0) {
    return (
      <div className={cn("flex flex-col items-center justify-center py-12", className)}>
        <p className="text-lg text-muted-foreground">No decay candidates</p>
        <p className="text-sm text-muted-foreground">
          Pages with detected content decay will appear here
        </p>
      </div>
    );
  }

  return (
    <div className={cn("space-y-4", className)}>
      {/* Filters Row */}
      <div className="flex flex-wrap items-center gap-3">
        <div className="relative flex-1 min-w-[200px] max-w-md">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search by URL or title..."
            value={filters.search}
            onChange={(e) => {
              setFilters((prev) => ({ ...prev, search: e.target.value }));
              setCurrentPage(1);
            }}
            className="pl-9"
          />
        </div>

        <Select
          value={filters.status}
          onValueChange={(value) => {
            setFilters((prev) => ({
              ...prev,
              status: value as ContentDecayCandidateStatus | "all",
            }));
            setCurrentPage(1);
          }}
        >
          <SelectTrigger className="w-40" aria-label="Filter by status">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            {STATUS_OPTIONS.map((opt) => (
              <SelectItem key={opt.value} value={opt.value}>
                {opt.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <Select
          value={filters.severity}
          onValueChange={(value) => {
            setFilters((prev) => ({
              ...prev,
              severity: value as ContentDecaySeverity | "all",
            }));
            setCurrentPage(1);
          }}
        >
          <SelectTrigger className="w-36" aria-label="Filter by severity">
            <SelectValue placeholder="Severity" />
          </SelectTrigger>
          <SelectContent>
            {SEVERITY_OPTIONS.map((opt) => (
              <SelectItem key={opt.value} value={opt.value}>
                {opt.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <Select
          value={filters.pageType}
          onValueChange={(value) => {
            setFilters((prev) => ({
              ...prev,
              pageType: value as ContentDecayPageType | "all",
            }));
            setCurrentPage(1);
          }}
        >
          <SelectTrigger className="w-36" aria-label="Filter by page type">
            <SelectValue placeholder="Page Type" />
          </SelectTrigger>
          <SelectContent>
            {PAGE_TYPE_OPTIONS.map((opt) => (
              <SelectItem key={opt.value} value={opt.value}>
                {opt.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {onExport && (
          <Button variant="outline" size="sm" onClick={onExport}>
            <Download className="h-4 w-4 mr-2" />
            Export
          </Button>
        )}
      </div>

      {/* Bulk Actions Bar */}
      {selectedIds.size > 0 && (
        <div className="flex items-center gap-4 rounded-lg bg-muted p-3">
          <span className="text-sm font-medium">
            {selectedIds.size} selected
          </span>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="outline" size="sm">
                Update Status
                <ChevronDown className="ml-2 h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent>
              <DropdownMenuItem onClick={() => handleBulkUpdate("planned")}>
                Mark as Planned
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleBulkUpdate("in_progress")}>
                Mark as In Progress
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleBulkUpdate("ignored")}>
                Ignore
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setSelectedIds(new Set())}
          >
            Clear Selection
          </Button>
        </div>
      )}

      {/* Table */}
      <div className="rounded-lg border overflow-hidden">
        <div role="grid" aria-label="Content decay candidates" className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-muted/50">
              <tr className="border-b">
                <th className="w-10 p-3">
                  <Checkbox
                    aria-label="Select all"
                    checked={
                      paginatedCandidates.length > 0 &&
                      selectedIds.size === paginatedCandidates.length
                    }
                    onCheckedChange={handleSelectAll}
                  />
                </th>
                <th
                  className={cn(
                    "p-3 text-left font-medium cursor-pointer hover:bg-muted",
                    "w-20"
                  )}
                  onClick={() => handleSort("decay_score")}
                >
                  <div className="flex items-center gap-1">
                    Score
                    {sortKey === "decay_score" && (
                      sortOrder === "asc" ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />
                    )}
                  </div>
                </th>
                <th className="p-3 text-left font-medium">Page</th>
                <th className="p-3 text-left font-medium w-28">Type</th>
                <th className="p-3 text-left font-medium w-28">Severity</th>
                <th className="p-3 text-left font-medium">Reasons</th>
                <th
                  className={cn(
                    "p-3 text-left font-medium cursor-pointer hover:bg-muted w-28"
                  )}
                  onClick={() => handleSort("lost_click_estimate")}
                >
                  <div className="flex items-center gap-1">
                    Lost Clicks
                    {sortKey === "lost_click_estimate" && (
                      sortOrder === "asc" ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />
                    )}
                  </div>
                </th>
                <th
                  className={cn(
                    "p-3 text-left font-medium cursor-pointer hover:bg-muted w-24"
                  )}
                  onClick={() => handleSort("recovery_upside_estimate")}
                >
                  <div className="flex items-center gap-1">
                    Upside
                    {sortKey === "recovery_upside_estimate" && (
                      sortOrder === "asc" ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />
                    )}
                  </div>
                </th>
                <th className="p-3 text-left font-medium w-24">Status</th>
                <th className="p-3 text-left font-medium w-20">Trend</th>
                <th className="p-3 w-16">Actions</th>
              </tr>
            </thead>
            <tbody>
              {paginatedCandidates.map((candidate) => (
                <tr
                  key={candidate.id}
                  role="row"
                  aria-label={candidate.pageTitle || candidate.pageUrl}
                  className={cn(
                    "border-b hover:bg-muted/50 cursor-pointer",
                    selectedIds.has(candidate.id) && "bg-muted/30"
                  )}
                  onClick={() => onRowClick?.(candidate)}
                >
                  <td className="p-3" onClick={(e) => e.stopPropagation()}>
                    <Checkbox
                      aria-label={`Select ${candidate.pageTitle || candidate.pageUrl}`}
                      checked={selectedIds.has(candidate.id)}
                      onCheckedChange={() => handleSelectRow(candidate.id)}
                    />
                  </td>
                  <td className="p-3 font-mono text-xs">
                    {candidate.decayScore.toFixed(1)}
                  </td>
                  <td className="p-3">
                    <div className="space-y-1">
                      {candidate.pageTitle && (
                        <div className="font-medium truncate max-w-xs">
                          {candidate.pageTitle}
                        </div>
                      )}
                      <a
                        href={candidate.pageUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-xs text-muted-foreground hover:text-primary flex items-center gap-1"
                        onClick={(e) => e.stopPropagation()}
                      >
                        {truncateUrl(candidate.pageUrl)}
                        <ExternalLink className="h-3 w-3" />
                      </a>
                    </div>
                  </td>
                  <td className="p-3">
                    <span className="text-xs capitalize">
                      {PAGE_TYPE_CONFIG[candidate.pageType]?.label ?? candidate.pageType}
                    </span>
                  </td>
                  <td className="p-3">
                    <SeverityBadge severity={candidate.severity} size="sm" />
                  </td>
                  <td className="p-3">
                    <DecayReasonBadges
                      reasons={candidate.reasonCodes}
                      primaryReason={candidate.primaryReasonCode}
                      maxSupporting={2}
                      size="sm"
                    />
                  </td>
                  <td className="p-3 text-red-600 font-medium">
                    -{formatNumber(candidate.lostClickEstimate)}
                  </td>
                  <td className="p-3 text-green-600 font-medium">
                    +{formatNumber(candidate.recoveryUpsideEstimate)}
                  </td>
                  <td className="p-3">
                    <span className="text-xs capitalize px-2 py-1 rounded-full bg-muted">
                      {candidate.status.replace("_", " ")}
                    </span>
                  </td>
                  <td className="p-3" onClick={(e) => e.stopPropagation()}>
                    <DecayTrendMiniChart data={candidate.trend} width={100} height={32} />
                  </td>
                  <td className="p-3" onClick={(e) => e.stopPropagation()}>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                          <MoreHorizontal className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => onRowClick?.(candidate)}>
                          View Details
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        {VALID_STATUS_TRANSITIONS.filter(
                          (t) => t.from === candidate.status
                        ).map((transition) => (
                          <DropdownMenuItem
                            key={transition.to}
                            onClick={() => onBulkUpdate?.([candidate.id], transition.to)}
                          >
                            {transition.label}
                          </DropdownMenuItem>
                        ))}
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-muted-foreground">
            Showing {(currentPage - 1) * pageSize + 1} to{" "}
            {Math.min(currentPage * pageSize, filteredCandidates.length)} of{" "}
            {filteredCandidates.length}
          </p>
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={currentPage === 1}
              onClick={() => setCurrentPage((p) => p - 1)}
            >
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={currentPage === totalPages}
              onClick={() => setCurrentPage((p) => p + 1)}
            >
              Next
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
