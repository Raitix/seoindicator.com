/**
 * Content Decay Refresh Queue Page
 * Main page for viewing and managing content decay candidates
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 */

"use client";

import * as React from "react";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import {
  RefreshCw,
  Download,
  Settings,
  LayoutDashboard,
  KanbanSquare,
  BarChart3,
  AlertTriangle,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { DecaySummaryCards } from "@/components/content-decay/DecaySummaryCards";
import { DecayQueueTable } from "@/components/content-decay/DecayQueueTable";
import { CandidateDetailDrawer } from "@/components/content-decay/CandidateDetailDrawer";
import {
  useContentDecaySummary,
  useContentDecayCandidates,
  useContentDecayCandidate,
  useContentRefreshTasks,
  useRunContentDecayScan,
  useUpdateCandidateStatus,
  useExportContentDecay,
  useCreateRefreshTask,
} from "@/lib/hooks/useContentDecay";
import type {
  DecayCandidate,
  DecayQueueFilterState,
  ContentDecayCandidateStatus,
  ContentRefreshTaskCreateInput,
} from "@/lib/types/content-decay";

// ============================================================
// Loading Skeleton
// ============================================================

function PageSkeleton() {
  return (
    <div className="space-y-6 p-6" data-testid="page-skeleton">
      <div className="flex items-center justify-between">
        <Skeleton className="h-8 w-48" />
        <div className="flex gap-2">
          <Skeleton className="h-10 w-24" />
          <Skeleton className="h-10 w-24" />
        </div>
      </div>
      <div className="grid gap-4 md:grid-cols-5">
        {Array.from({ length: 5 }).map((_, i) => (
          <Skeleton key={i} className="h-24" data-testid="skeleton" />
        ))}
      </div>
      <Skeleton className="h-96" />
    </div>
  );
}

// ============================================================
// Navigation Tabs
// ============================================================

interface NavTabsProps {
  siteId: string;
  activeTab: string;
}

function NavTabs({ siteId, activeTab }: NavTabsProps) {
  const router = useRouter();

  return (
    <Tabs value={activeTab} className="w-auto">
      <TabsList>
        <TabsTrigger
          value="queue"
          onClick={() => router.push(`/sites/${siteId}/content-decay`)}
          className="gap-1.5"
        >
          <LayoutDashboard className="h-4 w-4" />
          Queue
        </TabsTrigger>
        <TabsTrigger
          value="board"
          onClick={() => router.push(`/sites/${siteId}/content-decay/board`)}
          className="gap-1.5"
        >
          <KanbanSquare className="h-4 w-4" />
          Board
        </TabsTrigger>
        <TabsTrigger
          value="recovery"
          onClick={() => router.push(`/sites/${siteId}/content-decay/recovery`)}
          className="gap-1.5"
        >
          <BarChart3 className="h-4 w-4" />
          Recovery
        </TabsTrigger>
      </TabsList>
    </Tabs>
  );
}

// ============================================================
// Refresh Queue Content
// ============================================================

function RefreshQueueContent({ siteId }: { siteId: string }) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const candidateIdParam = searchParams.get("candidate");

  // State for filters
  const [filters, setFilters] = React.useState<DecayQueueFilterState>({
    status: "all",
    severity: "all",
    pageType: "all",
    reasonCode: "all",
    minLostClicks: null,
    search: "",
    sort: "decay_score",
    order: "desc",
  });

  // Hooks
  const {
    summary,
    isLoading: isSummaryLoading,
    mutate: mutateSummary,
  } = useContentDecaySummary(siteId);

  const {
    candidates,
    meta,
    isLoading: isCandidatesLoading,
    mutate: mutateCandidates,
  } = useContentDecayCandidates(siteId, filters);

  const { runScan, isRunning: isScanRunning } = useRunContentDecayScan(siteId);
  const { exportData, isExporting } = useExportContentDecay(siteId);

  // Selected candidate for drawer
  const [selectedCandidateId, setSelectedCandidateId] = React.useState<number | null>(
    candidateIdParam ? parseInt(candidateIdParam, 10) : null
  );

  const {
    candidate: selectedCandidate,
    isLoading: isCandidateLoading,
  } = useContentDecayCandidate(selectedCandidateId ?? 0);

  const { tasks: selectedCandidateTasks } = useContentRefreshTasks(selectedCandidateId ?? 0);
  const { updateStatus, isUpdating } = useUpdateCandidateStatus(selectedCandidateId ?? 0);
  const { createTask, isCreating: isCreatingTask } = useCreateRefreshTask(selectedCandidateId ?? 0);

  // Handlers
  const handleRefresh = async () => {
    const result = await runScan();
    if (result.success) {
      mutateSummary();
      mutateCandidates();
    }
  };

  const handleExport = async () => {
    await exportData("csv");
  };

  const handleRowClick = (candidate: DecayCandidate) => {
    setSelectedCandidateId(candidate.id);
    router.push(`/sites/${siteId}/content-decay?candidate=${candidate.id}`);
  };

  const handleCloseDrawer = () => {
    setSelectedCandidateId(null);
    router.push(`/sites/${siteId}/content-decay`);
  };

  const handleStatusChange = async (status: ContentDecayCandidateStatus) => {
    const result = await updateStatus(status);
    if (result.success) {
      mutateCandidates();
    }
  };

  const handleBulkUpdate = async (ids: number[], status: ContentDecayCandidateStatus) => {
    // For bulk updates, we would need a bulk update endpoint
    // For now, update one by one
    for (const id of ids) {
      const { updateStatus: updateOne } = useUpdateCandidateStatus(id);
      await updateOne(status);
    }
    mutateCandidates();
  };

  const handleCreateTask = async (task: ContentRefreshTaskCreateInput) => {
    await createTask(task);
  };

  const isLoading = isSummaryLoading || isCandidatesLoading;

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900">
      <div className="container mx-auto px-4 py-6">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div>
            <h1 className="text-2xl font-bold text-white flex items-center gap-2">
              <AlertTriangle className="h-6 w-6 text-amber-500" />
              Content Decay
            </h1>
            <p className="text-sm text-slate-400">
              Monitor and recover pages with declining performance
            </p>
          </div>

          <div className="flex items-center gap-3">
            <NavTabs siteId={siteId} activeTab="queue" />
            <Button
              variant="outline"
              size="sm"
              onClick={() => router.push(`/sites/${siteId}/content-decay/settings`)}
              className="border-slate-700 text-slate-300"
            >
              <Settings className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={handleExport}
              disabled={isExporting}
              className="border-slate-700 text-slate-300"
            >
              <Download className="h-4 w-4 mr-2" />
              Export
            </Button>
            <Button
              variant="default"
              size="sm"
              onClick={handleRefresh}
              disabled={isScanRunning}
              className="bg-primary text-primary-foreground"
            >
              <RefreshCw className={`h-4 w-4 mr-2 ${isScanRunning ? "animate-spin" : ""}`} />
              Run Scan
            </Button>
          </div>
        </div>

        {/* Summary Cards */}
        <div className="mb-6">
          <DecaySummaryCards
            summary={summary}
            isLoading={isSummaryLoading}
            className="grid gap-4 md:grid-cols-5"
          />
        </div>

        {/* Queue Table */}
        <div className="rounded-lg border border-slate-800 bg-slate-900/50 p-4">
          <DecayQueueTable
            candidates={candidates}
            isLoading={isCandidatesLoading}
            pageSize={10}
            onRowClick={handleRowClick}
            onBulkUpdate={handleBulkUpdate}
            onExport={handleExport}
          />
        </div>

        {/* Candidate Detail Drawer */}
        <CandidateDetailDrawer
          candidate={selectedCandidate}
          tasks={selectedCandidateTasks}
          isOpen={selectedCandidateId !== null}
          onClose={handleCloseDrawer}
          onStatusChange={handleStatusChange}
          onCreateTask={handleCreateTask}
          isLoading={isCandidateLoading}
        />
      </div>
    </div>
  );
}

// ============================================================
// Page Component
// ============================================================

export default function ContentDecayPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  if (!siteId) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center">
        <p className="text-slate-400">Site not found</p>
      </div>
    );
  }

  return <RefreshQueueContent siteId={siteId} />;
}
