"use client";

import * as React from "react";
import { useState, useMemo } from "react";
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
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Skeleton } from "@/components/ui/skeleton";
import { Checkbox } from "@/components/ui/checkbox";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { cn } from "@/lib/utils";
import {
  DecayCandidate,
  ContentDecaySeverity,
  ContentDecayCandidateStatus,
  ContentDecayPageType,
  DecayQueueFilterState,
  DEFAULT_DECAY_QUEUE_FILTERS,
  PAGE_TYPE_CONFIG,
} from "@/lib/types/content-decay";
import { SeverityBadge } from "./SeverityBadge";
import { DecayReasonBadges } from "./DecayReasonBadges";
import { DecayTrendMiniChart } from "./DecayTrendMiniChart";

/**
 * Props for the DecayQueueTable component
 */
export interface DecayQueueTableProps {
  /** Array of decay candidates to display */
  candidates: DecayCandidate[];
  /** Loading state */
  isLoading?: boolean;
  /** Optional additional CSS classes */
  className?: string;
  /** Callback when a row is clicked */
  onRowClick?: (candidate: DecayCandidate) => void;
  /** Callback when bulk status update is triggered */
  onBulkUpdate?: (ids: number[], status: ContentDecayCandidateStatus) => void;
  /** Callback when export is triggered */
  onExport?: () => void;
  /** Initial filter state */
  initialFilters?: Partial<DecayQueueFilterState>;
  /** Page size for pagination */
  pageSize?: number;
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
 * Truncate URL for display
 */
function truncateUrl(url: string, maxLength: number = 50): string {
  if (url.length <= maxLength) return url;
  return url.substring(0, maxLength - 3) + "...";
}

/**
 * Status options for filter dropdown
 */
const STATUS_OPTIONS: { value: ContentDecayCandidateStatus | "all"; label: string }[] = [
  { value: "all", label: "All Statuses" },
  { value: "backlog", label: "Backlog" },
  { value: "planned", label: "Planned" },
  { value: "in_progress", label: "In Progress" },
  { value: "refreshed", label: "Refreshed" },
  { value: "recovered", label: "Recovered" },
  { value: "ignored", label: "Ignored" },
];

/**
 * Severity options for filter dropdown
 */
const SEVERITY_OPTIONS: { value: ContentDecaySeverity | "all"; label: string }[] = [
  { value: "all", label: "All Severities" },
  { value: "critical", label: "Critical" },
  { value: "high", label: "High" },
  { value: "medium", label: "Medium" },
  { value: "low", label: "Low" },
];

/**
 * Page type options for filter dropdown
 */
const PAGE_TYPE_OPTIONS: { value: ContentDecayPageType | "all"; label: string }[] = [
  { value: "all", label: "All Types" },
  { value: "home", label: "Homepage" },
  { value: "blog", label: "Blog" },
  { value: "landing", label: "Landing" },
  { value: "product", label: "Product" },
  { value: "tool", label: "Tool" },
  { value: "compare", label: "Compare" },
  { value: "docs", label: "Docs" },
  { value: "other", label: "Other" },
];

/**
 * DecayQueueTable displays a sortable, filterable table of content decay candidates
 * with support for bulk operations and pagination.
 *
 * @example
 * ```tsx
 * <DecayQueueTable
 *   candidates={candidates}
 *   onRowClick={(candidate) => console.log(candidate)}
 *   onBulkUpdate={(ids, status) => updateStatus(ids, status)}
 * />
 * ```
 */
export function DecayQueueTable({
  candidates,
  isLoading = false,
  className,
  onRowClick,
  onBulkUpdate,
  onExport,
  initialFilters,
  pageSize = 10,
}: DecayQueueTableProps) {
  // State management
  const [filters, setFilters] = useState<DecayQueueFilterState>({
    ...DEFAULT_DECAY_QUEUE_FILTERS,
    ...initialFilters,
  });
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
  const [currentPage, setCurrentPage] = useState(1);

  // Filter and sort candidates
  const filteredCandidates = useMemo(() => {
    let result = [...candidates];

    // Apply status filter
    if (filters.status !== "all") {
      result = result.filter((c) => c.status === filters.status);
    }

    // Apply severity filter
    if (filters.severity !== "all") {
      result = result.filter((c) => c.severity === filters.severity);
    }

    // Apply page type filter
    if (filters.pageType !== "all") {
      result = result.filter((c) => c.pageType === filters.pageType);
    }

    // Apply search filter
    if (filters.search) {
      const searchLower = filters.search.toLowerCase();
      result = result.filter(
        (c) =>
          c.pageUrl.toLowerCase().includes(searchLower) ||
          c.pageTitle?.toLowerCase().includes(searchLower)
      );
    }

    // Apply sorting
    result.sort((a, b) => {
      let aValue: number | string = 0;
      let bValue: number | string = 0;

      switch (filters.sort) {
        case "decay_score":
          aValue = a.decayScore;
          bValue = b.decayScore;
          break;
        case "recovery_upside_estimate":
          aValue = a.recoveryUpsideEstimate;
          bValue = b.recoveryUpsideEstimate;
          break;
        case "lost_click_estimate":
          aValue = a.lostClickEstimate;
          bValue = b.lostClickEstimate;
          break;
        case "last_detected_at":
          aValue = a.lastDetectedAt;
          bValue = b.lastDetectedAt;
          break;
      }

      if (filters.order === "asc") {
        return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
      }
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0;
    });

    return result;
  }, [candidates, filters]);

  // Pagination
  const totalPages = Math.ceil(filteredCandidates.length / pageSize);
  const paginatedCandidates = filteredCandidates.slice(
    (currentPage - 1) * pageSize,
    currentPage * pageSize
  );

  // Selection handlers
  const handleSelectAll = () => {
    if (selectedIds.size === paginatedCandidates.length) {
      setSelectedIds(new Set());
    } else {
      setSelectedIds(new Set(paginatedCandidates.map((c) => c.id)));
    }
  };

  const handleSelectRow = (id: number) => {
    const newSelected = new Set(selectedIds);
    if (newSelected.has(id)) {
      newSelected.delete(id);
    } else {
      newSelected.add(id);
    }
    setSelectedIds(newSelected);
  };

  // Sort handler
  const handleSort = (column: typeof filters.sort) => {
    setFilters((prev) => ({
      ...prev,
      sort: column,
      order: prev.sort === column && prev.order === "desc" ? "asc" : "desc",
    }));
  };

  // Render loading skeleton
  if (isLoading) {
    return (
      <div className={cn("space-y-4", className)}>
        <div className="flex gap-4">
          <Skeleton className="h-10 w-48" />
          <Skeleton className="h-10 w-32" />
          <Skeleton className="h-10 w-32" />
        </div>
        <div className="rounded-md border">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="flex items-center gap-4 p-4 border-b last:border-0">
              <Skeleton className="h-4 w-4" />
              <Skeleton className="h-4 flex-1" />
              <Skeleton className="h-4 w-20" />
              <Skeleton className="h-4 w-16" />
            </div>
          ))}
        </div>
      </div>
    );
  }

  // Render empty state
  if (candidates.length === 0) {
    return (
      <div
        className={cn("flex flex-col items-center justify-center py-12 text-center", className)}
        role="status"
        aria-label="No decay candidates found"
      >
        <p className="text-muted-foreground text-lg font-medium">No decay candidates</p>
        <p className="text-muted-foreground text-sm mt-1">
          Your pages are performing well or haven't been analyzed yet.
        </p>
      </div>
    );
  }

  return (
    <div className={cn("space-y-4", className)}>
      {/* Filters and Search */}
      <div className="flex flex-col sm:flex-row gap-4">
        {/* Search */}
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Search by URL or title..."
            value={filters.search}
            onChange={(e) => {
              setFilters((prev) => ({ ...prev, search: e.target.value }));
              setCurrentPage(1);
            }}
            className="pl-9"
            aria-label="Search candidates"
          />
        </div>

        {/* Status Filter */}
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
          <SelectTrigger className="w-[140px]" aria-label="Filter by status">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            {STATUS_OPTIONS.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {/* Severity Filter */}
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
          <SelectTrigger className="w-[140px]" aria-label="Filter by severity">
            <SelectValue placeholder="Severity" />
          </SelectTrigger>
          <SelectContent>
            {SEVERITY_OPTIONS.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {/* Page Type Filter */}
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
          <SelectTrigger className="w-[140px]" aria-label="Filter by page type">
            <SelectValue placeholder="Page Type" />
          </SelectTrigger>
          <SelectContent>
            {PAGE_TYPE_OPTIONS.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {/* Export Button */}
        {onExport && (
          <Button variant="outline" size="sm" onClick={onExport} className="ml-auto">
            <Download className="h-4 w-4 mr-2" />
            Export
          </Button>
        )}
      </div>

      {/* Bulk Actions */}
      {selectedIds.size > 0 && onBulkUpdate && (
        <div className="flex items-center gap-2 p-2 bg-muted/50 rounded-md">
          <span className="text-sm text-muted-foreground">
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
              <DropdownMenuItem
                onClick={() => {
                  onBulkUpdate(Array.from(selectedIds), "planned");
                  setSelectedIds(new Set());
                }}
              >
                Mark as Planned
              </DropdownMenuItem>
              <DropdownMenuItem
                onClick={() => {
                  onBulkUpdate(Array.from(selectedIds), "in_progress");
                  setSelectedIds(new Set());
                }}
              >
                Mark In Progress
              </DropdownMenuItem>
              <DropdownMenuItem
                onClick={() => {
                  onBulkUpdate(Array.from(selectedIds), "ignored");
                  setSelectedIds(new Set());
                }}
              >
                Ignore
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      )}

      {/* Table */}
      <div className="rounded-md border" role="grid" aria-label="Content decay candidates">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-12">
                <Checkbox
                  checked={selectedIds.size === paginatedCandidates.length && paginatedCandidates.length > 0}
                  onCheckedChange={handleSelectAll}
                  aria-label="Select all candidates"
                />
              </TableHead>
              <TableHead
                className="cursor-pointer hover:bg-muted/50"
                onClick={() => handleSort("decay_score")}
              >
                <div className="flex items-center gap-1">
                  Score
                  {filters.sort === "decay_score" && (
                    filters.order === "desc" ? <ChevronDown className="h-4 w-4" /> : <ChevronUp className="h-4 w-4" />
                  )}
                </div>
              </TableHead>
              <TableHead>Page</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Severity</TableHead>
              <TableHead
                className="cursor-pointer hover:bg-muted/50"
                onClick={() => handleSort("lost_click_estimate")}
              >
                <div className="flex items-center gap-1">
                  Lost Clicks
                  {filters.sort === "lost_click_estimate" && (
                    filters.order === "desc" ? <ChevronDown className="h-4 w-4" /> : <ChevronUp className="h-4 w-4" />
                  )}
                </div>
              </TableHead>
              <TableHead
                className="cursor-pointer hover:bg-muted/50"
                onClick={() => handleSort("recovery_upside_estimate")}
              >
                <div className="flex items-center gap-1">
                  Upside
                  {filters.sort === "recovery_upside_estimate" && (
                    filters.order === "desc" ? <ChevronDown className="h-4 w-4" /> : <ChevronUp className="h-4 w-4" />
                  )}
                </div>
              </TableHead>
              <TableHead>Reasons</TableHead>
              <TableHead>Trend</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="w-12">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {paginatedCandidates.map((candidate) => (
              <TableRow
                key={candidate.id}
                className={cn(
                  "cursor-pointer",
                  selectedIds.has(candidate.id) && "bg-muted/50"
                )}
                onClick={() => onRowClick?.(candidate)}
                role="row"
                aria-label={`Candidate: ${candidate.pageTitle || candidate.pageUrl}`}
              >
                <TableCell onClick={(e) => e.stopPropagation()}>
                  <Checkbox
                    checked={selectedIds.has(candidate.id)}
                    onCheckedChange={() => handleSelectRow(candidate.id)}
                    aria-label={`Select ${candidate.pageTitle || candidate.pageUrl}`}
                  />
                </TableCell>
                <TableCell>
                  <span className="font-medium">{candidate.decayScore.toFixed(1)}</span>
                </TableCell>
                <TableCell>
                  <div className="max-w-xs">
                    <a
                      href={candidate.pageUrl}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-blue-500 hover:underline text-sm"
                      onClick={(e) => e.stopPropagation()}
                    >
                      {truncateUrl(candidate.pageUrl)}
                    </a>
                    {candidate.pageTitle && (
                      <p className="text-xs text-muted-foreground truncate mt-0.5">
                        {candidate.pageTitle}
                      </p>
                    )}
                  </div>
                </TableCell>
                <TableCell>
                  <span className="text-sm">{PAGE_TYPE_CONFIG[candidate.pageType]?.label || candidate.pageType}</span>
                </TableCell>
                <TableCell>
                  <SeverityBadge severity={candidate.severity} size="sm" />
                </TableCell>
                <TableCell>
                  <span className="text-sm text-red-500">
                    -{formatNumber(candidate.lostClickEstimate)}
                  </span>
                </TableCell>
                <TableCell>
                  <span className="text-sm text-green-500">
                    +{formatNumber(candidate.recoveryUpsideEstimate)}
                  </span>
                </TableCell>
                <TableCell onClick={(e) => e.stopPropagation()}>
                  <DecayReasonBadges
                    reasons={candidate.reasonCodes}
                    primaryReason={candidate.primaryReasonCode}
                    size="sm"
                    maxSupporting={2}
                  />
                </TableCell>
                <TableCell onClick={(e) => e.stopPropagation()}>
                  <DecayTrendMiniChart
                    data={candidate.trend}
                    width={80}
                    height={30}
                  />
                </TableCell>
                <TableCell>
                  <span className="text-sm capitalize">{candidate.status.replace("_", " ")}</span>
                </TableCell>
                <TableCell onClick={(e) => e.stopPropagation()}>
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
                      <DropdownMenuItem asChild>
                        <a
                          href={candidate.pageUrl}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="flex items-center"
                        >
                          Open Page
                          <ExternalLink className="ml-2 h-4 w-4" />
                        </a>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-muted-foreground">
            Showing {(currentPage - 1) * pageSize + 1} to{" "}
            {Math.min(currentPage * pageSize, filteredCandidates.length)} of{" "}
            {filteredCandidates.length} candidates
          </p>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
              disabled={currentPage === 1}
            >
              Previous
            </Button>
            {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
              const page = i + 1;
              return (
                <Button
                  key={page}
                  variant={currentPage === page ? "default" : "outline"}
                  size="sm"
                  onClick={() => setCurrentPage(page)}
                >
                  {page}
                </Button>
              );
            })}
            {totalPages > 5 && <span className="text-muted-foreground">...</span>}
            <Button
              variant="outline"
              size="sm"
              onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
              disabled={currentPage === totalPages}
            >
              Next
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}

export default DecayQueueTable;
