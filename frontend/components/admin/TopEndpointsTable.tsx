/**
 * TopEndpointsTable Component
 * Task: TASK-27-16 - Usage Charts and Tables
 *
 * Data table component displaying top API endpoints by request count.
 * Supports sorting, pagination, and responsive design.
 */

"use client";

import React, { useState, useMemo, useCallback } from "react";
import {
  RefreshCw,
  Download,
  ChevronUp,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  Table,
} from "lucide-react";
import { cn } from "@/lib/utils";

// ============================================================================
// Types
// ============================================================================

export interface EndpointData {
  endpoint: string;
  requests: number;
  percentage: number;
  avgResponseTime?: number;
  lastUsed?: string;
}

export interface TopEndpointsTableProps {
  /** Array of endpoint data */
  data: EndpointData[];
  /** Loading state */
  isLoading?: boolean;
  /** Error message */
  error?: string | null;
  /** Maximum rows to display */
  maxRows?: number;
  /** Retry callback */
  onRetry?: () => void;
  /** Export callback */
  onExport?: () => void;
  /** Additional CSS classes */
  className?: string;
  /** Accessible label */
  "aria-label"?: string;
}

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Format number with K/M suffix
 */
export function formatNumber(value: number): string {
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + "M";
  }
  if (value >= 1000) {
    return (value / 1000).toFixed(1) + "K";
  }
  return value.toString();
}

/**
 * Truncate endpoint URL for display
 */
export function truncateEndpoint(
  endpoint: string,
  maxLength: number = 40
): string {
  if (endpoint.length <= maxLength) return endpoint;
  return endpoint.substring(0, maxLength - 3) + "...";
}

/**
 * Format response time
 */
export function formatResponseTime(ms: number): string {
  if (ms >= 1000) {
    const seconds = ms / 1000;
    return seconds % 1 === 0 ? seconds.toFixed(0) + "s" : seconds.toFixed(1) + "s";
  }
  return ms + "ms";
}

/**
 * Format relative time
 */
export function formatRelativeTime(dateStr: string): string {
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);

  if (diffMins < 1) return "just now";
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
  return `${Math.floor(diffMins / 1440)}d ago`;
}

/**
 * Calculate progress bar width (clamped 0-100)
 */
export function calculateProgressWidth(percentage: number): number {
  return Math.max(0, Math.min(100, percentage));
}

// ============================================================================
// Types
// ============================================================================

type SortKey = "endpoint" | "requests" | "percentage" | "avgResponseTime";
type SortOrder = "asc" | "desc";

// ============================================================================
// Component
// ============================================================================

export function TopEndpointsTable({
  data,
  isLoading = false,
  error = null,
  maxRows = 10,
  onRetry,
  onExport,
  className,
  "aria-label": ariaLabel = "Top API endpoints by request count",
}: TopEndpointsTableProps) {
  const [sortKey, setSortKey] = useState<SortKey>("requests");
  const [sortOrder, setSortOrder] = useState<SortOrder>("desc");
  const [page, setPage] = useState(0);

  // Sort data
  const sortedData = useMemo(() => {
    return [...data].sort((a, b) => {
      let aVal: string | number = a[sortKey] ?? 0;
      let bVal: string | number = b[sortKey] ?? 0;

      if (typeof aVal === "string" && typeof bVal === "string") {
        return sortOrder === "asc"
          ? aVal.localeCompare(bVal)
          : bVal.localeCompare(aVal);
      }

      return sortOrder === "asc"
        ? (aVal as number) - (bVal as number)
        : (bVal as number) - (aVal as number);
    });
  }, [data, sortKey, sortOrder]);

  // Paginated data
  const totalPages = Math.ceil(sortedData.length / maxRows);
  const paginatedData = useMemo(() => {
    const start = page * maxRows;
    return sortedData.slice(start, start + maxRows);
  }, [sortedData, page, maxRows]);

  // Handle sort
  const handleSort = useCallback((key: SortKey) => {
    setSortKey(key);
    setSortOrder((prev) => (key === sortKey && prev === "desc" ? "asc" : "desc"));
  }, [sortKey]);

  // Handle pagination
  const handlePrevPage = useCallback(() => {
    setPage((prev) => Math.max(0, prev - 1));
  }, []);

  const handleNextPage = useCallback(() => {
    setPage((prev) => Math.min(totalPages - 1, prev + 1));
  }, [totalPages]);

  // Handle export
  const handleExport = useCallback(() => {
    if (onExport) {
      onExport();
    }
    // Placeholder - future CSV export
  }, [onExport]);

  // Loading state
  if (isLoading) {
    return (
      <div
        className={cn(
          "rounded-lg border border-slate-700 bg-slate-800 p-6",
          className
        )}
        aria-busy="true"
      >
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-white">Top Endpoints</h3>
        </div>
        <div className="space-y-3">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="flex gap-4">
              <div className="h-6 w-1/3 animate-pulse rounded bg-slate-700" />
              <div className="h-6 w-20 animate-pulse rounded bg-slate-700" />
              <div className="h-6 w-20 animate-pulse rounded bg-slate-700" />
            </div>
          ))}
        </div>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div
        className={cn(
          "rounded-lg border border-red-700 bg-slate-800 p-6",
          className
        )}
        role="alert"
      >
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-white">Top Endpoints</h3>
        </div>
        <div className="flex h-48 flex-col items-center justify-center gap-4">
          <p className="text-red-400">{error}</p>
          {onRetry && (
            <button
              onClick={onRetry}
              className="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
            >
              <RefreshCw className="h-4 w-4" />
              Retry
            </button>
          )}
        </div>
      </div>
    );
  }

  // Empty state
  if (data.length === 0) {
    return (
      <div
        className={cn(
          "rounded-lg border border-slate-700 bg-slate-800 p-6",
          className
        )}
      >
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-white">Top Endpoints</h3>
        </div>
        <div className="flex h-48 flex-col items-center justify-center gap-2 text-slate-400">
          <Table className="h-8 w-8" />
          <p>No endpoint data available</p>
          <p className="text-sm">API requests will appear here</p>
        </div>
      </div>
    );
  }

  // Render table
  return (
    <div
      className={cn(
        "rounded-lg border border-slate-700 bg-slate-800 p-6",
        className
      )}
      aria-label={ariaLabel}
    >
      {/* Header */}
      <div className="mb-4 flex items-center justify-between">
        <h3 className="text-lg font-semibold text-white">Top Endpoints</h3>
        <button
          onClick={handleExport}
          aria-label="Export to CSV"
          className="flex items-center gap-1 rounded-md bg-slate-700 px-3 py-1.5 text-sm text-slate-300 transition-colors hover:bg-slate-600"
        >
          <Download className="h-4 w-4" />
          Export
        </button>
      </div>

      {/* Table */}
      <div className="overflow-x-auto">
        <table className="w-full min-w-[600px]" role="table">
          <thead>
            <tr className="border-b border-slate-700 text-left text-sm text-slate-400">
              <th
                scope="col"
                className="cursor-pointer px-4 py-3 font-medium hover:text-white"
                onClick={() => handleSort("endpoint")}
              >
                <div className="flex items-center gap-1">
                  Endpoint
                  {sortKey === "endpoint" && (
                    sortOrder === "asc" ? (
                      <ChevronUp className="h-4 w-4" />
                    ) : (
                      <ChevronDown className="h-4 w-4" />
                    )
                  )}
                </div>
              </th>
              <th
                scope="col"
                className="cursor-pointer px-4 py-3 font-medium hover:text-white"
                onClick={() => handleSort("requests")}
              >
                <div className="flex items-center gap-1">
                  Requests
                  {sortKey === "requests" && (
                    sortOrder === "asc" ? (
                      <ChevronUp className="h-4 w-4" />
                    ) : (
                      <ChevronDown className="h-4 w-4" />
                    )
                  )}
                </div>
              </th>
              <th
                scope="col"
                className="cursor-pointer px-4 py-3 font-medium hover:text-white"
                onClick={() => handleSort("percentage")}
              >
                <div className="flex items-center gap-1">
                  Share
                  {sortKey === "percentage" && (
                    sortOrder === "asc" ? (
                      <ChevronUp className="h-4 w-4" />
                    ) : (
                      <ChevronDown className="h-4 w-4" />
                    )
                  )}
                </div>
              </th>
              <th
                scope="col"
                className="cursor-pointer px-4 py-3 font-medium hover:text-white"
                onClick={() => handleSort("avgResponseTime")}
              >
                <div className="flex items-center gap-1">
                  Avg Time
                  {sortKey === "avgResponseTime" && (
                    sortOrder === "asc" ? (
                      <ChevronUp className="h-4 w-4" />
                    ) : (
                      <ChevronDown className="h-4 w-4" />
                    )
                  )}
                </div>
              </th>
              <th scope="col" className="px-4 py-3 font-medium">
                Last Used
              </th>
            </tr>
          </thead>
          <tbody>
            {paginatedData.map((row, index) => (
              <tr
                key={index}
                className="border-b border-slate-700/50 text-sm hover:bg-slate-700/30"
              >
                <td className="px-4 py-3">
                  <span
                    className="font-mono text-slate-300"
                    title={row.endpoint}
                  >
                    {truncateEndpoint(row.endpoint)}
                  </span>
                </td>
                <td className="px-4 py-3 text-white">
                  {formatNumber(row.requests)}
                </td>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-2">
                    <div className="h-2 w-16 rounded-full bg-slate-700">
                      <div
                        className="h-full rounded-full bg-blue-500"
                        style={{
                          width: `${calculateProgressWidth(row.percentage)}%`,
                        }}
                      />
                    </div>
                    <span className="text-slate-300">
                      {row.percentage.toFixed(1)}%
                    </span>
                  </div>
                </td>
                <td className="px-4 py-3 text-slate-300">
                  {row.avgResponseTime !== undefined
                    ? formatResponseTime(row.avgResponseTime)
                    : "-"}
                </td>
                <td className="px-4 py-3 text-slate-400">
                  {row.lastUsed ? formatRelativeTime(row.lastUsed) : "-"}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="mt-4 flex items-center justify-between text-sm">
          <span className="text-slate-400">
            Showing {page * maxRows + 1}-{Math.min((page + 1) * maxRows, data.length)} of {data.length}
          </span>
          <div className="flex items-center gap-2">
            <button
              onClick={handlePrevPage}
              disabled={page === 0}
              aria-label="Previous page"
              className="rounded-md p-2 text-slate-400 transition-colors hover:bg-slate-700 hover:text-white disabled:opacity-50 disabled:hover:bg-transparent"
            >
              <ChevronLeft className="h-4 w-4" />
            </button>
            <span className="text-slate-400">
              Page {page + 1} of {totalPages}
            </span>
            <button
              onClick={handleNextPage}
              disabled={page >= totalPages - 1}
              aria-label="Next page"
              className="rounded-md p-2 text-slate-400 transition-colors hover:bg-slate-700 hover:text-white disabled:opacity-50 disabled:hover:bg-transparent"
            >
              <ChevronRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

export default TopEndpointsTable;
