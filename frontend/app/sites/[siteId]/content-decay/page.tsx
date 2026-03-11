"use client";

import { Suspense, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { Metadata } from "next";
import { Play, Download, Settings, RefreshCw } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
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
  useUpdateCandidateStatus,
  useExportContentDecay,
  useContentRefreshTasks,
} from "@/lib/hooks/useContentDecay";
import type {
  DecayCandidate,
  ContentDecayCandidateStatus,
  ContentRefreshTaskCreateInput,
} from "@/lib/types/content-decay";

export const metadata: Metadata = {
  title: "Content Decay - Refresh Queue | SEOIndicator",
  description: "Monitor and manage content decay candidates for your site.",
};

function RefreshQueueContent({ siteId }: { siteId: string }) {
  const router = useRouter();
  const [selectedCandidateId, setSelectedCandidateId] = useState<number | null>(null);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [filters, setFilters] = useState({
    status: "all" as const,
    severity: "all" as const,
    pageType: "all" as const,
    search: "",
    sort: "decay_score" as const,
    order: "desc" as const,
  });

  // Data hooks
  const { summary, isLoading: isSummaryLoading, mutate: mutateSummary } = useContentDecaySummary(siteId);
  const { candidates, meta, isLoading: isCandidatesLoading, mutate: mutateCandidates } = useContentDecayCandidates(siteId, filters);
  const { candidate: selectedCandidate, isLoading: isCandidateLoading } = useContentDecayCandidate(selectedCandidateId ?? 0);
  const { tasks, mutate: mutateTasks } = useContentRefreshTasks(selectedCandidateId ?? 0);

  // Action hooks
  const { runScan, isRunning } = useRunContentDecayScan(siteId);
  const { updateStatus, isUpdating } = useUpdateCandidateStatus(selectedCandidateId ?? 0);
  const { exportData, isExporting } = useExportContentDecay(siteId);

  // Handlers
  const handleRowClick = (candidate: DecayCandidate) => {
    setSelectedCandidateId(candidate.id);
    setIsDrawerOpen(true);
  };

  const handleDrawerClose = () => {
    setIsDrawerOpen(false);
    setSelectedCandidateId(null);
  };

  const handleStatusChange = async (status: ContentDecayCandidateStatus) => {
    const result = await updateStatus(status);
    if (result.success) {
      mutateCandidates();
      mutateSummary();
      handleDrawerClose();
    }
  };

  const handleBulkUpdate = async (ids: number[], newStatus: ContentDecayCandidateStatus) => {
    // For bulk updates, we'd need a separate hook - simplified here
    console.log("Bulk update:", ids, newStatus);
  };

  const handleRunScan = async () => {
    const result = await runScan();
    if (result.success) {
      mutateSummary();
      mutateCandidates();
    }
  };

  const handleExport = async () => {
    await exportData("csv");
  };

  const handleCreateTask = async (task: ContentRefreshTaskCreateInput) => {
    console.log("Create task:", task);
    mutateTasks();
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white">Content Decay</h1>
          <p className="text-sm text-slate-400">
            Monitor and manage pages with declining performance
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={handleRunScan}
            disabled={isRunning}
            data-testid="run-scan-button"
          >
            {isRunning ? (
              <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
            ) : (
              <Play className="h-4 w-4 mr-2" />
            )}
            Run Scan
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={handleExport}
            disabled={isExporting}
            data-testid="export-button"
          >
            <Download className="h-4 w-4 mr-2" />
            Export
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.push(`/sites/${siteId}/content-decay/settings`)}
            data-testid="settings-button"
          >
            <Settings className="h-4 w-4 mr-2" />
            Settings
          </Button>
        </div>
      </div>

      {/* Summary Cards */}
      <section aria-label="Summary statistics" data-testid="summary-section">
        <DecaySummaryCards
          summary={summary ?? undefined}
          isLoading={isSummaryLoading}
        />
      </section>

      {/* Navigation Tabs */}
      <div className="flex gap-2 border-b border-slate-800 pb-2">
        <Button
          variant="ghost"
          size="sm"
          className="text-white border-b-2 border-primary"
          data-testid="tab-queue"
        >
          Queue
        </Button>
        <Button
          variant="ghost"
          size="sm"
          className="text-slate-400"
          onClick={() => router.push(`/sites/${siteId}/content-decay/board`)}
          data-testid="tab-board"
        >
          Board
        </Button>
        <Button
          variant="ghost"
          size="sm"
          className="text-slate-400"
          onClick={() => router.push(`/sites/${siteId}/content-decay/recovery`)}
          data-testid="tab-recovery"
        >
          Recovery
        </Button>
      </div>

      {/* Queue Table */}
      <section aria-label="Decay candidates queue" data-testid="queue-section">
        <DecayQueueTable
          candidates={candidates}
          isLoading={isCandidatesLoading}
          onRowClick={handleRowClick}
          onBulkUpdate={handleBulkUpdate}
          onExport={handleExport}
        />
      </section>

      {/* Candidate Detail Drawer */}
      <CandidateDetailDrawer
        candidate={selectedCandidate}
        tasks={tasks}
        isOpen={isDrawerOpen}
        onClose={handleDrawerClose}
        onStatusChange={handleStatusChange}
        onCreateTask={handleCreateTask}
        isLoading={isCandidateLoading}
      />
    </div>
  );
}

function LoadingSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <Skeleton className="h-10 w-48" />
        <div className="flex gap-2">
          <Skeleton className="h-10 w-24" />
          <Skeleton className="h-10 w-24" />
          <Skeleton className="h-10 w-24" />
        </div>
      </div>
      <div className="grid grid-cols-5 gap-4">
        {Array.from({ length: 5 }).map((_, i) => (
          <Skeleton key={i} className="h-24 w-full" data-testid="skeleton" />
        ))}
      </div>
      <Skeleton className="h-10 w-full" />
      <div className="space-y-2">
        {Array.from({ length: 5 }).map((_, i) => (
          <Skeleton key={i} className="h-16 w-full" data-testid="skeleton" />
        ))}
      </div>
    </div>
  );
}

export default function ContentDecayPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
      <div className="mx-auto w-full max-w-7xl">
        <Suspense fallback={<LoadingSkeleton />}>
          <RefreshQueueContent siteId={siteId} />
        </Suspense>
      </div>
    </div>
  );
}
