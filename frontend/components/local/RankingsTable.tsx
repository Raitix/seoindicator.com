"use client";

import * as React from "react";
import { ArrowDown, ArrowUp, ArrowUpDown, RefreshCw } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { formatDateTime } from "@/lib/location-dashboard";
import type {
  RankingsSortDirection,
  RankingsSortKey,
} from "@/lib/types/location-rankings";
import { cn } from "@/lib/utils";

export interface RankingsTableRowView {
  keywordId: number;
  keyword: string;
  localPackPosition: number | null;
  localOrganicPosition: number | null;
  packChange: number | null;
  organicChange: number | null;
  lastChecked: string | null;
  competitorsInPackCount: number;
}

export interface RankingsTableProps {
  rows: RankingsTableRowView[];
  selectedKeywordId: number | null;
  sortKey: RankingsSortKey;
  sortDirection: RankingsSortDirection;
  isLoading?: boolean;
  error?: Error | null;
  checkingKeywordId?: number | null;
  onSelectKeyword: (keywordId: number) => void;
  onSortChange: (key: RankingsSortKey) => void;
  onRetry?: () => void;
  onCheckKeyword?: (keywordId: number) => void;
}

const formatPosition = (value: number | null) => {
  if (value === null || value <= 0) {
    return "Not found";
  }

  return `#${value}`;
};

const getPositionClasses = (value: number | null) => {
  if (value !== null && value > 0 && value <= 3) {
    return "border-emerald-500/30 bg-emerald-500/10 text-emerald-100";
  }

  if (value !== null && value > 0 && value <= 10) {
    return "border-amber-500/30 bg-amber-500/10 text-amber-100";
  }

  return "border-red-500/20 bg-red-500/10 text-red-100";
};

function ChangeIndicator({ value }: { value: number | null }) {
  if (value === null || value === 0) {
    return <span className="text-xs text-slate-400">—</span>;
  }

  const improved = value > 0;

  return (
    <span
      className={cn(
        "inline-flex items-center gap-1 rounded-full border px-2 py-1 text-xs font-medium",
        improved
          ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-100"
          : "border-red-500/30 bg-red-500/10 text-red-100"
      )}
    >
      {improved ? <ArrowUp className="h-3 w-3" aria-hidden="true" /> : <ArrowDown className="h-3 w-3" aria-hidden="true" />}
      {improved ? "+" : ""}
      {value}
    </span>
  );
}

function SortableHeader({
  label,
  sortKey,
  activeSortKey,
  sortDirection,
  onSortChange,
  className,
}: {
  label: string;
  sortKey: RankingsSortKey;
  activeSortKey: RankingsSortKey;
  sortDirection: RankingsSortDirection;
  onSortChange: (key: RankingsSortKey) => void;
  className?: string;
}) {
  const isActive = activeSortKey === sortKey;

  return (
    <Button
      type="button"
      variant="ghost"
      size="sm"
      className={cn("-ml-2 h-auto px-2 py-1 text-slate-300 hover:bg-slate-900 hover:text-white", className)}
      onClick={() => onSortChange(sortKey)}
    >
      {label}
      {isActive ? (
        sortDirection === "asc" ? <ArrowUp className="h-3.5 w-3.5" aria-hidden="true" /> : <ArrowDown className="h-3.5 w-3.5" aria-hidden="true" />
      ) : (
        <ArrowUpDown className="h-3.5 w-3.5 text-slate-500" aria-hidden="true" />
      )}
    </Button>
  );
}

export function RankingsTable({
  rows,
  selectedKeywordId,
  sortKey,
  sortDirection,
  isLoading = false,
  error,
  checkingKeywordId = null,
  onSelectKeyword,
  onSortChange,
  onRetry,
  onCheckKeyword,
}: RankingsTableProps) {
  return (
    <div className="rounded-3xl border border-slate-800 bg-slate-950/70 shadow-lg shadow-slate-950/10">
      <div className="flex flex-col gap-4 border-b border-slate-800/80 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 className="text-lg font-semibold text-white">Tracked keywords</h2>
          <p className="mt-1 text-sm text-slate-400">Monitor current local-pack visibility, organic coverage, and fresh changes for each keyword.</p>
        </div>
      </div>

      <div className="px-2 pb-2 pt-3 sm:px-4">
        {error ? (
          <div className="rounded-2xl border border-red-500/20 bg-red-500/10 p-5 text-red-50" role="alert">
            <p className="text-sm text-red-100/90">{error.message}</p>
            {onRetry ? (
              <Button type="button" variant="outline" className="mt-4" onClick={onRetry}>
                Try again
              </Button>
            ) : null}
          </div>
        ) : isLoading ? (
          <div className="space-y-3 px-2 pb-4">
            <Skeleton className="h-12 w-full bg-slate-800" />
            <Skeleton className="h-12 w-full bg-slate-800" />
            <Skeleton className="h-12 w-full bg-slate-800" />
            <Skeleton className="h-12 w-full bg-slate-800" />
          </div>
        ) : rows.length ? (
          <Table>
            <TableHeader>
              <TableRow className="hover:bg-transparent">
                <TableHead>
                  <SortableHeader
                    label="Keyword"
                    sortKey="keyword"
                    activeSortKey={sortKey}
                    sortDirection={sortDirection}
                    onSortChange={onSortChange}
                  />
                </TableHead>
                <TableHead>
                  <SortableHeader
                    label="Local pack"
                    sortKey="localPackPosition"
                    activeSortKey={sortKey}
                    sortDirection={sortDirection}
                    onSortChange={onSortChange}
                  />
                </TableHead>
                <TableHead>
                  <SortableHeader
                    label="Organic"
                    sortKey="localOrganicPosition"
                    activeSortKey={sortKey}
                    sortDirection={sortDirection}
                    onSortChange={onSortChange}
                  />
                </TableHead>
                <TableHead>
                  <SortableHeader
                    label="Pack change"
                    sortKey="packChange"
                    activeSortKey={sortKey}
                    sortDirection={sortDirection}
                    onSortChange={onSortChange}
                  />
                </TableHead>
                <TableHead>
                  <SortableHeader
                    label="Last checked"
                    sortKey="lastChecked"
                    activeSortKey={sortKey}
                    sortDirection={sortDirection}
                    onSortChange={onSortChange}
                  />
                </TableHead>
                <TableHead className="text-right text-slate-400">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {rows.map((row) => {
                const isSelected = row.keywordId === selectedKeywordId;
                const isChecking = checkingKeywordId === row.keywordId;

                return (
                  <TableRow
                    key={row.keywordId}
                    className={cn(
                      "cursor-pointer border-slate-800",
                      isSelected ? "bg-slate-900/70" : undefined
                    )}
                    data-state={isSelected ? "selected" : undefined}
                    onClick={() => onSelectKeyword(row.keywordId)}
                  >
                    <TableCell>
                      <div>
                        <div className="font-medium text-white">{row.keyword}</div>
                        <div className="mt-1 text-xs text-slate-500">
                          {row.competitorsInPackCount > 0
                            ? `${row.competitorsInPackCount} local-pack result${row.competitorsInPackCount === 1 ? "" : "s"} captured`
                            : "No local-pack snapshot yet"}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <span className={cn("inline-flex min-w-[90px] justify-center rounded-full border px-3 py-1.5 text-xs font-medium", getPositionClasses(row.localPackPosition))}>
                        {formatPosition(row.localPackPosition)}
                      </span>
                    </TableCell>
                    <TableCell>
                      <span className={cn("inline-flex min-w-[90px] justify-center rounded-full border px-3 py-1.5 text-xs font-medium", getPositionClasses(row.localOrganicPosition))}>
                        {formatPosition(row.localOrganicPosition)}
                      </span>
                    </TableCell>
                    <TableCell>
                      <ChangeIndicator value={row.packChange} />
                    </TableCell>
                    <TableCell>
                      <div className="text-sm text-slate-300">{formatDateTime(row.lastChecked)}</div>
                      {row.organicChange !== null ? (
                        <div className="mt-1 text-xs text-slate-500">Organic change: {row.organicChange > 0 ? "+" : ""}{row.organicChange}</div>
                      ) : null}
                    </TableCell>
                    <TableCell className="text-right">
                      {onCheckKeyword ? (
                        <Button
                          type="button"
                          size="sm"
                          variant="outline"
                          className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800"
                          disabled={isChecking}
                          onClick={(event) => {
                            event.stopPropagation();
                            onCheckKeyword(row.keywordId);
                          }}
                        >
                          <RefreshCw className={cn("h-4 w-4", isChecking ? "animate-spin" : undefined)} aria-hidden="true" />
                          {isChecking ? "Checking…" : "Check"}
                        </Button>
                      ) : null}
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        ) : (
          <div className="rounded-2xl border border-dashed border-slate-800 bg-slate-900/60 px-4 py-10 text-center text-sm text-slate-400">
            No rankings found for this location yet.
          </div>
        )}
      </div>
    </div>
  );
}

export default RankingsTable;
