"use client";

import React, { useState, Suspense } from "react";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import {
  DecaySummaryCards,
  DecayQueueTable,
  CandidateDetailDrawer,
} from "@/components/content-decay";
import {
  useContentDecaySummary,
  useContentDecayCandidates,
  useContentDecayCandidate,
  useRunContentDecayScan,
  useExportContentDecay,
} from "@/lib/hooks/useContentDecay";
import type { DecayCandidate, DecayQueueFilterState } from "@/lib/types/content-decay";
import { RefreshCw, Download, Settings, LayoutDashboard, Columns, BarChart3 } from "lucide-react";

// Loading fallback for Suspense
function ContentDecayLoadingFallback() {
  return (
    <div className="container mx-auto px-4 py-6 space-y-6" data-testid="content-decay-page-loading">
      <div className="animate-pulse">
        <div className="h-8 w-48 bg-slate-700 rounded mb-2" />
        <div className="h-4 w-64 bg-slate-700 rounded" />
      </div>
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="h-24 bg-slate-700 rounded-lg" />
        ))}
      </div>
      <div className="h-96 bg-slate-700 rounded-lg" />
    </div>
  );
}

/**
 * Content Decay Refresh Queue Page - Content Component
 * Route: /sites/[siteId]/content-decay
 */
function ContentDecayContent() {
  const params = useParams();
  const router = useRouter();
  const searchParams = useSearchParams();
  const siteId = params.siteId as string;

  const [selectedCandidateId, setSelectedCandidateId] = useState<number | null>(null);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);

  const filters: DecayQueueFilterState = {
    severity: (searchParams.get("severity") as DecayQueueFilterState["severity"]) || "all",
    status: (searchParams.get("status") as DecayQueueFilterState["status"]) || "all",
    pageType: (searchParams.get("pageType") as DecayQueueFilterState["pageType"]) || "all",
    search: searchParams.get("search") || "",
    sortBy: (searchParams.get("sortBy") as DecayQueueFilterState["sortBy"]) || "decay_score",
    sortOrder: (searchParams.get("sortOrder") as DecayQueueFilterState["sortOrder"]) || "desc",
    page: parseInt(searchParams.get("page") || "1"),
    perPage: parseInt(searchParams.get("perPage") || "25"),
  };

  const { summary, isLoading: isSummaryLoading } = useContentDecaySummary(siteId);
  const { candidates, meta, isLoading: isCandidatesLoading } = useContentDecayCandidates(siteId, filters);
  const { candidate: selectedCandidate, isLoading: isCandidateLoading } = useContentDecayCandidate(selectedCandidateId);
  const { trigger: runScan, isMutating: isScanning } = useRunContentDecayScan(siteId);
  const { trigger: exportData, isMutating: isExporting } = useExportContentDecay(siteId);

  const handleCandidateClick = (candidate: DecayCandidate) => {
    setSelectedCandidateId(candidate.id);
    setIsDrawerOpen(true);
  };

  const handleRunScan = async () => {
    try { await runScan(); } catch (error) { console.error("Failed to trigger scan:", error); }
  };

  const handleExport = async () => {
    try { await exportData({ format: "csv" }); } catch (error) { console.error("Failed to export:", error); }
  };

  const handleFilterChange = (newFilters: Partial<DecayQueueFilterState>) => {
    const updatedFilters = { ...filters, ...newFilters };
    const params = new URLSearchParams();
    Object.entries(updatedFilters).forEach(([key, value]) => {
      if (value && value !== "all" && value !== "") {
        params.set(key, String(value));
      }
    });
    router.push(`/sites/${siteId}/content-decay?${params.toString()}`);
  };

  const handleDrawerClose = () => {
    setIsDrawerOpen(false);
    setSelectedCandidateId(null);
  };

  const tabs = [
    { id: "queue", label: "Queue", href: `/sites/${siteId}/content-decay`, icon: LayoutDashboard, active: true },
    { id: "board", label: "Board", href: `/sites/${siteId}/content-decay/board`, icon: Columns },
    { id: "recovery", label: "Recovery", href: `/sites/${siteId}/content-decay/recovery`, icon: BarChart3 },
  ];

  return (
    <div className="container mx-auto px-4 py-6 space-y-6" data-testid="content-decay-page">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white">Content Decay</h1>
          <p className="text-slate-400 text-sm mt-1">Identify and refresh underperforming content</p>
        </div>
        <div className="flex items-center gap-2">
          <button onClick={handleRunScan} disabled={isScanning} className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-600 text-white text-sm font-medium rounded-lg transition-colors" aria-label="Run manual scan">
            <RefreshCw className={`w-4 h-4 ${isScanning ? "animate-spin" : ""}`} />
            {isScanning ? "Scanning..." : "Run Scan"}
          </button>
          <button onClick={handleExport} disabled={isExporting} className="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 disabled:bg-slate-800 text-slate-200 text-sm font-medium rounded-lg transition-colors" aria-label="Export to CSV">
            <Download className="w-4 h-4" />Export
          </button>
          <Link href={`/sites/${siteId}/content-decay/settings`} className="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 text-sm font-medium rounded-lg transition-colors" aria-label="Content decay settings">
            <Settings className="w-4 h-4" />Settings
          </Link>
        </div>
      </div>

      <nav className="flex gap-1 border-b border-slate-700 pb-2" aria-label="Content decay views">
        {tabs.map((tab) => (
          <Link key={tab.id} href={tab.href} className={`inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-t-lg transition-colors ${tab.active ? "bg-slate-800 text-white" : "text-slate-400 hover:text-white hover:bg-slate-700"}`} aria-current={tab.active ? "page" : undefined}>
            <tab.icon className="w-4 h-4" />{tab.label}
          </Link>
        ))}
      </nav>

      <section aria-label="Summary statistics">
        <DecaySummaryCards summary={summary} isLoading={isSummaryLoading} />
      </section>

      <section aria-label="Decay candidates queue">
        <DecayQueueTable candidates={candidates || []} isLoading={isCandidatesLoading} filters={filters} onFilterChange={handleFilterChange} onCandidateClick={handleCandidateClick} meta={meta} />
      </section>

      <CandidateDetailDrawer candidate={selectedCandidate} isOpen={isDrawerOpen} onClose={handleDrawerClose} isLoading={isCandidateLoading} />
    </div>
  );
}

// Wrap with Suspense for static export compatibility with useSearchParams
export default function ContentDecayPage() {
  return (
    <Suspense fallback={<ContentDecayLoadingFallback />}>
      <ContentDecayContent />
    </Suspense>
  );
}
