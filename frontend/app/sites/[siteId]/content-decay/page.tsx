"use client";

import React, { useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import {
  RefreshCw,
  Download,
  Settings,
  LayoutDashboard,
  Kanban,
  TrendingUp,
} from "lucide-react";
import { toast } from "sonner";

import { DecaySummaryCards } from "@/components/content-decay/DecaySummaryCards";
import { DecayQueueTable } from "@/components/content-decay/DecayQueueTable";
import { CandidateDetailDrawer } from "@/components/content-decay/CandidateDetailDrawer";
import {
  useContentDecaySummary,
  useContentDecayCandidates,
  useRunContentDecayScan,
  useExportContentDecay,
} from "@/lib/hooks/useContentDecay";
import type { DecayCandidate, DecayQueueFilterState } from "@/lib/types/content-decay";

/**
 * Content Decay Refresh Queue Page
 * 
 * Main entry point for the Content Decay feature.
 * Shows summary cards, queue table with filters, and candidate detail drawer.
 * 
 * @see TASK-36-13
 * @see SPEC-036
 */
export default function ContentDecayPage() {
  const params = useParams();
  const router = useRouter();
  const siteId = params.siteId as string;

  // State
  const [selectedCandidate, setSelectedCandidate] = useState<DecayCandidate | null>(null);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [filters, setFilters] = useState<DecayQueueFilterState>({
    status: "all",
    severity: "all",
    pageType: "all",
    search: "",
    sort: "decay_score",
    order: "desc",
  });

  // Hooks
  const { summary, isLoading: isSummaryLoading } = useContentDecaySummary(siteId);
  const { candidates, meta, isLoading: isCandidatesLoading, mutate } = useContentDecayCandidates(siteId, filters);
  const { runScan, isRunning } = useRunContentDecayScan(siteId);
  const { exportData, isExporting } = useExportContentDecay(siteId);

  // Handlers
  const handleRunScan = async () => {
    const result = await runScan();
    if (result.success) {
      toast.success("Content decay scan started");
      mutate();
    } else {
      toast.error("Failed to start scan");
    }
  };

  const handleExport = async () => {
    const result = await exportData("csv");
    if (result.success) {
      toast.success("Export downloaded");
    } else {
      toast.error("Export failed");
    }
  };

  const handleCandidateClick = (candidate: DecayCandidate) => {
    setSelectedCandidate(candidate);
    setIsDrawerOpen(true);
  };

  const handleDrawerClose = () => {
    setIsDrawerOpen(false);
    setSelectedCandidate(null);
  };

  // Loading skeleton
  if (isSummaryLoading && !summary) {
    return (
      <div className="container mx-auto px-4 py-6" data-testid="loading-skeleton">
        <div className="animate-pulse space-y-6">
          <div className="h-8 w-48 bg-slate-700 rounded" />
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="h-32 bg-slate-700 rounded-lg" />
            ))}
          </div>
          <div className="h-96 bg-slate-700 rounded-lg" />
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-slate-100">Content Decay</h1>
          <p className="text-slate-400 mt-1">
            Identify and recover declining content
          </p>
        </div>
        <div className="flex items-center gap-2">
          <button
            onClick={handleRunScan}
            disabled={isRunning}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-600/50 text-white rounded-lg transition-colors"
            aria-label="Run manual scan"
          >
            <RefreshCw className={`w-4 h-4 ${isRunning ? "animate-spin" : ""}`} />
            {isRunning ? "Scanning..." : "Run Scan"}
          </button>
          <button
            onClick={handleExport}
            disabled={isExporting}
            className="flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 disabled:bg-slate-700/50 text-slate-200 rounded-lg transition-colors"
            aria-label="Export to CSV"
          >
            <Download className="w-4 h-4" />
            Export
          </button>
          <Link
            href={`/sites/${siteId}/content-decay/settings`}
            className="flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-lg transition-colors"
            aria-label="Open settings"
          >
            <Settings className="w-4 h-4" />
            Settings
          </Link>
        </div>
      </div>

      {/* Navigation Tabs */}
      <nav className="flex gap-1 mb-6 border-b border-slate-700" aria-label="Content decay navigation">
        <Link
          href={`/sites/${siteId}/content-decay`}
          className="flex items-center gap-2 px-4 py-3 text-blue-400 border-b-2 border-blue-400 font-medium"
          aria-current="page"
        >
          <LayoutDashboard className="w-4 h-4" />
          Queue
        </Link>
        <Link
          href={`/sites/${siteId}/content-decay/board`}
          className="flex items-center gap-2 px-4 py-3 text-slate-400 hover:text-slate-200 border-b-2 border-transparent hover:border-slate-600 transition-colors"
        >
          <Kanban className="w-4 h-4" />
          Board
        </Link>
        <Link
          href={`/sites/${siteId}/content-decay/recovery`}
          className="flex items-center gap-2 px-4 py-3 text-slate-400 hover:text-slate-200 border-b-2 border-transparent hover:border-slate-600 transition-colors"
        >
          <TrendingUp className="w-4 h-4" />
          Recovery
        </Link>
      </nav>

      {/* Summary Cards */}
      <section aria-label="Summary statistics" className="mb-6">
        <DecaySummaryCards summary={summary} isLoading={isSummaryLoading} />
      </section>

      {/* Queue Table */}
      <section aria-label="Decay candidates queue">
        <DecayQueueTable
          candidates={candidates}
          meta={meta}
          isLoading={isCandidatesLoading}
          filters={filters}
          onFilterChange={setFilters}
          onCandidateClick={handleCandidateClick}
        />
      </section>

      {/* Candidate Detail Drawer */}
      <CandidateDetailDrawer
        candidate={selectedCandidate}
        isOpen={isDrawerOpen}
        onClose={handleDrawerClose}
        onStatusChange={() => {
          mutate();
        }}
      />
    </div>
  );
}
