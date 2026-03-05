"use client";

import * as React from "react";
import { ExternalLink, Trash2, Eye, Loader2 } from "lucide-react";

import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { MoreHorizontal } from "lucide-react";

import type { HistoryTableProps, AnalysisRecord, AnalysisStatus } from "@/lib/types/analysis";

// ==================== Status Badge ====================

interface StatusBadgeProps {
  status: AnalysisStatus;
}

function StatusBadge({ status }: StatusBadgeProps) {
  const config: Record<AnalysisStatus, { label: string; className: string; icon?: React.ReactNode }> = {
    completed: {
      label: "Completed",
      className: "bg-green-500/20 text-green-400 border-green-500/30",
    },
    failed: {
      label: "Failed",
      className: "bg-red-500/20 text-red-400 border-red-500/30",
    },
    pending: {
      label: "Pending",
      className: "bg-yellow-500/20 text-yellow-400 border-yellow-500/30",
    },
    running: {
      label: "Running",
      className: "bg-blue-500/20 text-blue-400 border-blue-500/30",
      icon: <Loader2 className="h-3 w-3 animate-spin mr-1" />,
    },
  };

  const { label, className, icon } = config[status];

  return (
    <span
      className={cn(
        "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border",
        className
      )}
      data-testid={`status-badge-${status}`}
    >
      {icon}
      {label}
    </span>
  );
}

// ==================== Duration Formatter ====================

function formatDuration(ms: number): string {
  if (ms < 1000) {
    return `${ms}ms`;
  }
  if (ms < 60000) {
    return `${(ms / 1000).toFixed(2)}s`;
  }
  const minutes = Math.floor(ms / 60000);
  const seconds = Math.round((ms % 60000) / 1000);
  return `${minutes}m ${seconds}s`;
}

// ==================== Cost Formatter ====================

function formatCost(total: number, currency: string): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 4,
  }).format(total);
}

// ==================== Date Formatter ====================

function formatDate(isoString: string): string {
  const date = new Date(isoString);
  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

// ==================== URL Truncator ====================

function truncateUrl(url: string, maxLength: number = 40): string {
  try {
    const urlObj = new URL(url);
    const display = urlObj.hostname + urlObj.pathname;
    if (display.length <= maxLength) {
      return display;
    }
    return display.substring(0, maxLength - 3) + "...";
  } catch {
    return url.length > maxLength ? url.substring(0, maxLength - 3) + "..." : url;
  }
}

// ==================== History Table ====================

export function HistoryTable({
  analyses,
  isLoading = false,
  onRowClick,
  onViewDetails,
  onDelete,
}: HistoryTableProps) {
  // Handle row click
  const handleRowClick = (analysis: AnalysisRecord) => {
    onRowClick?.(analysis);
  };

  // Handle view details
  const handleViewDetails = (e: React.MouseEvent, analysis: AnalysisRecord) => {
    e.stopPropagation();
    onViewDetails?.(analysis);
  };

  // Handle delete
  const handleDelete = (e: React.MouseEvent, analysis: AnalysisRecord) => {
    e.stopPropagation();
    onDelete?.(analysis);
  };

  // Loading skeleton
  if (isLoading) {
    return (
      <div className="rounded-lg border border-slate-800 overflow-hidden" data-testid="history-table-loading">
        <Table>
          <TableHeader>
            <TableRow className="border-slate-800 hover:bg-slate-900">
              <TableHead className="text-slate-400">Tool</TableHead>
              <TableHead className="text-slate-400">URL</TableHead>
              <TableHead className="text-slate-400">Status</TableHead>
              <TableHead className="text-slate-400">Cost</TableHead>
              <TableHead className="text-slate-400">Duration</TableHead>
              <TableHead className="text-slate-400">Date</TableHead>
              <TableHead className="text-slate-400 text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {[...Array(5)].map((_, i) => (
              <TableRow key={i} className="border-slate-800">
                {[...Array(7)].map((_, j) => (
                  <TableCell key={j}>
                    <div className="h-4 bg-slate-800 rounded animate-pulse" />
                  </TableCell>
                ))}
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    );
  }

  // Empty state handled by parent

  return (
    <div className="rounded-lg border border-slate-800 overflow-hidden" data-testid="history-table">
      <Table>
        <TableHeader>
          <TableRow className="border-slate-800 hover:bg-slate-900 bg-slate-900/50">
            <TableHead className="text-slate-400 font-medium">Tool</TableHead>
            <TableHead className="text-slate-400 font-medium">URL</TableHead>
            <TableHead className="text-slate-400 font-medium">Status</TableHead>
            <TableHead className="text-slate-400 font-medium">Cost</TableHead>
            <TableHead className="text-slate-400 font-medium">Duration</TableHead>
            <TableHead className="text-slate-400 font-medium">Date</TableHead>
            <TableHead className="text-slate-400 font-medium text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {analyses.map((analysis) => (
            <TableRow
              key={analysis.id}
              className={cn(
                "border-slate-800 hover:bg-slate-900/50 cursor-pointer transition-colors",
                "focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-950"
              )}
              onClick={() => handleRowClick(analysis)}
              tabIndex={0}
              onKeyDown={(e) => {
                if (e.key === "Enter" || e.key === " ") {
                  e.preventDefault();
                  handleRowClick(analysis);
                }
              }}
              data-testid={`analysis-row-${analysis.id}`}
            >
              {/* Tool Name */}
              <TableCell className="text-slate-200 font-medium">
                {analysis.toolDisplayName}
              </TableCell>

              {/* Input URL */}
              <TableCell className="text-slate-400 max-w-[200px]">
                <span className="truncate block" title={analysis.inputUrl}>
                  {truncateUrl(analysis.inputUrl)}
                </span>
              </TableCell>

              {/* Status */}
              <TableCell>
                <StatusBadge status={analysis.status} />
              </TableCell>

              {/* Cost */}
              <TableCell className="text-slate-200">
                {analysis.cost?.total !== undefined
                  ? formatCost(analysis.cost.total, analysis.cost?.currency || "USD")
                  : "—"}
              </TableCell>

              {/* Duration */}
              <TableCell className="text-slate-400">
                {formatDuration(analysis.analysisDurationMs)}
              </TableCell>

              {/* Date */}
              <TableCell className="text-slate-400">
                {formatDate(analysis.analyzedAt)}
              </TableCell>

              {/* Actions */}
              <TableCell className="text-right">
                <div className="flex items-center justify-end gap-2">
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-8 w-8 p-0 text-slate-400 hover:text-slate-200"
                    onClick={(e) => handleViewDetails(e, analysis)}
                    data-testid={`view-details-${analysis.id}`}
                    aria-label="View details"
                  >
                    <Eye className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-8 w-8 p-0 text-slate-400 hover:text-red-400"
                    onClick={(e) => handleDelete(e, analysis)}
                    data-testid={`delete-button-${analysis.id}`}
                    aria-label="Delete analysis"
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}

export default HistoryTable;
