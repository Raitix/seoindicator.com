"use client";

import * as React from "react";
import Link from "next/link";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ExternalLink, Clock, AlertCircle, CheckCircle, Loader2 } from "lucide-react";
import type { RecentAnalysis, AnalysisStatus } from "@/lib/types/dashboard";
import { cn } from "@/lib/utils";
import { formatDistanceToNow } from "date-fns";

export interface RecentAnalysesProps {
  analyses: RecentAnalysis[];
  loading?: boolean;
  error?: Error | null;
  onRowClick?: (analysis: RecentAnalysis) => void;
}

function TableSkeleton() {
  return (
    <Card className="border-slate-800 bg-slate-900/60" data-testid="analyses-skeleton">
      <CardHeader>
        <div className="h-5 w-40 animate-pulse rounded bg-slate-800" />
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-12 w-full animate-pulse rounded bg-slate-800" />
          ))}
        </div>
      </CardContent>
    </Card>
  );
}

const statusConfig: Record<AnalysisStatus, { label: string; className: string; icon: React.ReactNode }> = {
  pending: {
    label: "Pending",
    className: "bg-amber-500/20 text-amber-300 border-amber-500/30",
    icon: <Clock className="h-3 w-3" />,
  },
  running: {
    label: "Running",
    className: "bg-sky-500/20 text-sky-300 border-sky-500/30",
    icon: <Loader2 className="h-3 w-3 animate-spin" data-testid="status-spinner" />,
  },
  completed: {
    label: "Completed",
    className: "bg-emerald-500/20 text-emerald-300 border-emerald-500/30",
    icon: <CheckCircle className="h-3 w-3" />,
  },
  failed: {
    label: "Failed",
    className: "bg-rose-500/20 text-rose-300 border-rose-500/30",
    icon: <AlertCircle className="h-3 w-3" />,
  },
};

function StatusBadge({ status }: { status: AnalysisStatus }) {
  const config = statusConfig[status];
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium border",
        config.className
      )}
    >
      {config.icon}
      {config.label}
    </span>
  );
}

export function RecentAnalysesTable({
  analyses,
  loading = false,
  error,
  onRowClick,
}: RecentAnalysesProps) {
  if (loading) {
    return <TableSkeleton />;
  }

  if (error) {
    return (
      <Card className="border-slate-800 bg-slate-900/60">
        <CardHeader>
          <CardTitle className="text-white">Recent Analyses</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-slate-400">Unable to load recent analyses</p>
        </CardContent>
      </Card>
    );
  }

  if (!analyses || analyses.length === 0) {
    return (
      <Card className="border-slate-800 bg-slate-900/60">
        <CardHeader>
          <CardTitle className="text-white">Recent Analyses</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="text-center py-8">
            <AlertCircle className="h-12 w-12 mx-auto text-slate-600 mb-4" />
            <p className="text-sm text-slate-400">No analyses yet</p>
            <p className="text-xs text-slate-500 mt-1">Run your first analysis to see it here</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  const handleRowClick = (analysis: RecentAnalysis) => {
    if (onRowClick) {
      onRowClick(analysis);
    }
  };

  return (
    <Card className="border-slate-800 bg-slate-900/60" data-testid="analyses-section">
      <CardHeader>
        <CardTitle className="text-white">Recent Analyses</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="overflow-hidden rounded-lg border border-slate-800">
          <table
            className="min-w-full text-left text-sm"
            aria-label="Recent analyses"
          >
            <thead className="bg-slate-950 text-slate-400">
              <tr>
                <th className="px-4 py-3 font-semibold">Tool</th>
                <th className="px-4 py-3 font-semibold">URL</th>
                <th className="px-4 py-3 font-semibold">Status</th>
                <th className="px-4 py-3 font-semibold">Date</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800">
              {analyses.map((analysis) => (
                <tr
                  key={analysis.id}
                  className="hover:bg-slate-950/60 cursor-pointer"
                  onClick={() => handleRowClick(analysis)}
                >
                  <td className="px-4 py-3 text-slate-100">
                    {analysis.toolDisplayName}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2 text-slate-400">
                      <span className="truncate max-w-[200px]">{analysis.inputUrl}</span>
                      <ExternalLink className="h-3 w-3 flex-shrink-0" />
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <StatusBadge status={analysis.status} />
                  </td>
                  <td className="px-4 py-3 text-slate-400">
                    {formatDistanceToNow(new Date(analysis.analyzedAt), { addSuffix: true })}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  );
}

export function RecentAnalysesTableSkeleton() {
  return <TableSkeleton />;
}
