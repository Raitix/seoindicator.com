"use client";

import * as React from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import {
  RefreshCw,
  Download,
  Settings,
  LayoutDashboard,
  Columns,
  BarChart3,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Skeleton } from "@/components/ui/skeleton";
import { toast } from "sonner";
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
} from "@/lib/hooks/useContentDecay";
import type {
  DecayCandidate,
  ContentDecayCandidateStatus,
} from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export default function ContentDecayQueuePage() {
  const params = useParams();
  const router = useRouter();
  const siteId = params.siteId as string;

  // State
  const [selectedCandidateId, setSelectedCandidateId] = React.useState<number | null>(null);
  const [isDrawerOpen, setIsDrawerOpen] = React.useState(false);

  // Data hooks
  const { summary, isLoading: isSummaryLoading } = useContentDecaySummary(siteId);
  const { candidates, isLoading: isCandidatesLoading } = useContentDecayCandidates(siteId);
  const { candidate: selectedCandidate, isLoading: isCandidateLoading } = useContentDecayCandidate(selectedCandidateId ?? 0);
  
  // Mutations
  const { runScan, isRunning } = useRunContentDecayScan(siteId);
  const { updateStatus, isUpdating } = useUpdateCandidateStatus(selectedCandidateId ?? 0);
  const { exportData, isExporting } = useExportContentDecay(siteId);

  // Handlers
  const handleRowClick = React.useCallback((candidate: DecayCandidate) => {
    setSelectedCandidateId(candidate.id);
    setIsDrawerOpen(true);
  }, []);

  const handleDrawerClose = React.useCallback(() => {
    setIsDrawerOpen(false);
    setSelectedCandidateId(null);
  }, []);

  const handleStatusChange = React.useCallback(async (status: ContentDecayCandidateStatus) => {
    const result = await updateStatus(status);
    if (result.success) {
      toast.success(`Status updated to ${status.replace("_", " ")}`);
      setIsDrawerOpen(false);
      setSelectedCandidateId(null);
    } else {
      toast.error("Failed to update status");
    }
  }, [updateStatus]);

  const handleRunScan = React.useCallback(async () => {
    const result = await runScan();
    if (result.success) {
      toast.success("Content decay scan started");
    } else {
      toast.error(result.message || "Failed to start scan");
    }
  }, [runScan]);

  const handleExport = React.useCallback(async () => {
    const result = await exportData("csv");
    if (!result.success) {
      toast.error("Failed to export data");
    }
  }, [exportData]);

  // Loading state
  if (isSummaryLoading && isCandidatesLoading) {
    return (
      <div className="container mx-auto py-6 space-y-6">
        <div className="flex items-center justify-between">
          <Skeleton className="h-8 w-48" />
          <div className="flex gap-2">
            <Skeleton className="h-9 w-24" />
            <Skeleton className="h-9 w-24" />
          </div>
        </div>
        <Skeleton className="h-32 w-full" />
        <Skeleton className="h-96 w-full" />
      </div>
    );
  }

  return (
    <div className="container mx-auto py-6 space-y-6">
      {/* Header */}
      <header className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">Content Decay</h1>
          <p className="text-muted-foreground text-sm">
            Identify and recover pages with declining performance
          </p>
        </div>

        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={handleRunScan}
            disabled={isRunning}
          >
            <RefreshCw className={cn("h-4 w-4 mr-2", isRunning && "animate-spin")} />
            {isRunning ? "Scanning..." : "Run Scan"}
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={handleExport}
            disabled={isExporting}
          >
            <Download className="h-4 w-4 mr-2" />
            Export CSV
          </Button>
          <Link href={`/sites/${siteId}/content-decay/settings`}>
            <Button variant="outline" size="sm">
              <Settings className="h-4 w-4 mr-2" />
              Settings
            </Button>
          </Link>
        </div>
      </header>

      {/* Navigation Tabs */}
      <Tabs defaultValue="queue" className="w-full">
        <TabsList>
          <TabsTrigger value="queue" asChild>
            <Link href={`/sites/${siteId}/content-decay`}>
              <LayoutDashboard className="h-4 w-4 mr-2" />
              Queue
            </Link>
          </TabsTrigger>
          <TabsTrigger value="board" asChild>
            <Link href={`/sites/${siteId}/content-decay/board`}>
              <Columns className="h-4 w-4 mr-2" />
              Board
            </Link>
          </TabsTrigger>
          <TabsTrigger value="recovery" asChild>
            <Link href={`/sites/${siteId}/content-decay/recovery`}>
              <BarChart3 className="h-4 w-4 mr-2" />
              Recovery
            </Link>
          </TabsTrigger>
        </TabsList>
      </Tabs>

      {/* Summary Cards */}
      <section aria-label="Summary statistics">
        <DecaySummaryCards summary={summary} isLoading={isSummaryLoading} />
      </section>

      {/* Queue Table */}
      <section aria-label="Content decay queue">
        <DecayQueueTable
          candidates={candidates}
          isLoading={isCandidatesLoading}
          onRowClick={handleRowClick}
          onExport={handleExport}
        />
      </section>

      {/* Candidate Detail Drawer */}
      <CandidateDetailDrawer
        candidate={selectedCandidate}
        isOpen={isDrawerOpen}
        onClose={handleDrawerClose}
        onStatusChange={handleStatusChange}
        isLoading={isCandidateLoading}
      />
    </div>
  );
}
