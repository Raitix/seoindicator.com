/**
 * Content Decay Refresh Queue Page
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 * 
 * Main page for viewing and managing content decay candidates
 */

'use client';

import * as React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import { RefreshCw, Download, Settings, Plus } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
  DecaySummaryCards,
  DecayQueueTable,
  CandidateDetailDrawer,
} from '@/components/content-decay';
import type {
  DecayCandidate,
  ContentDecayCandidateStatus,
  ContentRefreshTask,
  ContentRefreshTaskCreateInput,
} from '@/lib/types/content-decay';
import {
  useContentDecaySummary,
  useContentDecayCandidates,
  useContentDecayCandidate,
  useRunContentDecayScan,
  useUpdateCandidateStatus,
  useExportContentDecay,
  useContentRefreshTasks,
  useCreateRefreshTask,
  useUpdateCandidateStatus as useRestoreCandidate,
} from '@/lib/hooks/useContentDecay';

// ============================================================
// Page Component
// ============================================================

export default function ContentDecayPage() {
  const params = useParams();
  const router = useRouter();
  const siteId = params.siteId as string;

  // State for drawer
  const [selectedCandidateId, setSelectedCandidateId] = React.useState<number | null>(null);
  const [isDrawerOpen, setIsDrawerOpen] = React.useState(false);

  // Data hooks
  const { summary, isLoading: isSummaryLoading, mutate: mutateSummary } = useContentDecaySummary(siteId);
  const { candidates, meta, isLoading: isCandidatesLoading, mutate: mutateCandidates } = useContentDecayCandidates(siteId);
  const { candidate: selectedCandidate, isLoading: isCandidateLoading } = useContentDecayCandidate(selectedCandidateId ?? 0);
  const { tasks, mutate: mutateTasks } = useContentRefreshTasks(selectedCandidateId ?? 0);

  // Action hooks
  const { runScan, isRunning } = useRunContentDecayScan(siteId);
  const { updateStatus, isUpdating } = useUpdateCandidateStatus(selectedCandidateId ?? 0);
  const { exportData, isExporting } = useExportContentDecay(siteId);
  const { createTask, isCreating: isCreatingTask } = useCreateRefreshTask(selectedCandidateId ?? 0);

  // Handlers
  const handleRowClick = React.useCallback((candidate: DecayCandidate) => {
    setSelectedCandidateId(candidate.id);
    setIsDrawerOpen(true);
  }, []);

  const handleCloseDrawer = React.useCallback(() => {
    setIsDrawerOpen(false);
    setSelectedCandidateId(null);
  }, []);

  const handleStatusChange = React.useCallback(async (status: ContentDecayCandidateStatus) => {
    const result = await updateStatus(status);
    if (result.success) {
      toast.success(`Status updated to ${status.replace('_', ' ')}`);
      mutateCandidates();
      mutateSummary();
    } else {
      toast.error('Failed to update status');
    }
  }, [updateStatus, mutateCandidates, mutateSummary]);

  const handleBulkUpdate = React.useCallback(async (ids: number[], newStatus: ContentDecayCandidateStatus) => {
    // Bulk update would need a separate hook - for now, update one by one
    toast.info(`Bulk update: ${ids.length} items to ${newStatus}`);
  }, []);

  const handleRunScan = React.useCallback(async () => {
    const result = await runScan();
    if (result.success) {
      toast.success('Content decay scan started');
      mutateSummary();
      mutateCandidates();
    } else {
      toast.error(result.message || 'Failed to start scan');
    }
  }, [runScan, mutateSummary, mutateCandidates]);

  const handleExport = React.useCallback(async () => {
    const result = await exportData('csv');
    if (result.success) {
      toast.success('Export downloaded');
    } else {
      toast.error('Failed to export data');
    }
  }, [exportData]);

  const handleCreateTask = React.useCallback(async (task: ContentRefreshTaskCreateInput) => {
    const result = await createTask(task);
    if (result.success) {
      toast.success('Task created');
      mutateTasks();
    } else {
      toast.error('Failed to create task');
    }
  }, [createTask, mutateTasks]);

  return (
    <main className="container mx-auto px-4 py-8">
      {/* Breadcrumb */}
      <nav className="mb-4 text-sm text-muted-foreground" aria-label="Breadcrumb">
        <ol className="flex items-center gap-2">
          <li>
            <Link href="/sites" className="hover:text-foreground">
              Sites
            </Link>
          </li>
          <li>/</li>
          <li>
            <Link href={`/sites/${siteId}`} className="hover:text-foreground">
              Site {siteId}
            </Link>
          </li>
          <li>/</li>
          <li className="text-foreground font-medium">Content Decay</li>
        </ol>
      </nav>

      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold">Content Decay</h1>
          <p className="text-muted-foreground">
            Monitor and recover declining content performance
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button onClick={handleRunScan} disabled={isRunning}>
            <RefreshCw className={`h-4 w-4 mr-2 ${isRunning ? 'animate-spin' : ''}`} />
            {isRunning ? 'Scanning...' : 'Scan Now'}
          </Button>
          <Button variant="outline" onClick={handleExport} disabled={isExporting}>
            <Download className="h-4 w-4 mr-2" />
            Export
          </Button>
          <Link href={`/sites/${siteId}/content-decay/settings`}>
            <Button variant="outline">
              <Settings className="h-4 w-4 mr-2" />
              Settings
            </Button>
          </Link>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="mb-6">
        <DecaySummaryCards
          summary={summary}
          isLoading={isSummaryLoading}
        />
      </div>

      {/* Queue Table */}
      <div className="mb-6">
        <DecayQueueTable
          candidates={candidates}
          isLoading={isCandidatesLoading}
          onRowClick={handleRowClick}
          onBulkUpdate={handleBulkUpdate}
          onExport={handleExport}
        />
      </div>

      {/* Navigation Tabs */}
      <div className="flex gap-4 border-b mb-6">
        <Link
          href={`/sites/${siteId}/content-decay`}
          className="px-4 py-2 border-b-2 border-primary font-medium"
        >
          Queue
        </Link>
        <Link
          href={`/sites/${siteId}/content-decay/board`}
          className="px-4 py-2 text-muted-foreground hover:text-foreground"
        >
          Board
        </Link>
        <Link
          href={`/sites/${siteId}/content-decay/recovery`}
          className="px-4 py-2 text-muted-foreground hover:text-foreground"
        >
          Recovery
        </Link>
      </div>

      {/* Candidate Detail Drawer */}
      <CandidateDetailDrawer
        candidate={selectedCandidate}
        tasks={tasks}
        isOpen={isDrawerOpen}
        onClose={handleCloseDrawer}
        onStatusChange={handleStatusChange}
        onCreateTask={handleCreateTask}
        isLoading={isCandidateLoading}
      />
    </main>
  );
}
