/**
 * AuditList Component
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Component for displaying a list of audits with status indicators,
 * filtering, pagination, and empty state handling.
 */

'use client';

import * as React from 'react';
import { Plus, RefreshCw, AlertCircle, FileSearch } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { AuditListCard } from './AuditListCard';
import { StartAuditModal } from './StartAuditModal';
import { useAudits, useStartAudit, useCancelAudit, type Audit, type AuditStatus } from '@/lib/hooks/useAudits';

// ============================================================
// Props
// ============================================================

export interface AuditListProps {
  /** Site ID */
  siteId: string;
  /** Site URL for display */
  siteUrl?: string;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Helpers
// ============================================================

const statusOptions: { value: string; label: string }[] = [
  { value: '', label: 'All Statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'running', label: 'Running' },
  { value: 'completed', label: 'Completed' },
  { value: 'failed', label: 'Failed' },
  { value: 'cancelled', label: 'Cancelled' },
];

// ============================================================
// Component
// ============================================================

export function AuditList({
  siteId,
  siteUrl,
  className,
}: AuditListProps) {
  // State
  const [statusFilter, setStatusFilter] = React.useState<string>('');
  const [page, setPage] = React.useState(1);
  const [isModalOpen, setIsModalOpen] = React.useState(false);
  const [cancellingId, setCancellingId] = React.useState<number | null>(null);

  // Hooks
  const { audits, meta, isLoading, error, refetch } = useAudits(siteId, {
    page,
    status: statusFilter as AuditStatus || undefined,
  });

  const startAudit = useStartAudit();
  const cancelAudit = useCancelAudit();

  // Handlers
  const handleStartAudit = async (params: { max_pages: number; max_depth: number }) => {
    await startAudit.mutateAsync({ siteId, ...params });
  };

  const handleCancelAudit = async (auditId: number) => {
    setCancellingId(auditId);
    try {
      await cancelAudit.mutateAsync(auditId);
    } finally {
      setCancellingId(null);
    }
  };

  const handleRetry = () => {
    refetch();
  };

  // Loading state
  if (isLoading && audits.length === 0) {
    return (
      <div className={cn('space-y-4', className)}>
        <div className="flex items-center justify-between">
          <Skeleton className="h-8 w-48" />
          <Skeleton className="h-10 w-32" />
        </div>
        {[1, 2, 3].map((i) => (
          <Skeleton key={i} className="h-40 w-full" />
        ))}
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className={cn('text-center py-12', className)}>
        <AlertCircle className="h-12 w-12 mx-auto mb-4 text-red-400" />
        <h3 className="text-lg font-medium text-white mb-2">Failed to load audits</h3>
        <p className="text-slate-400 mb-4">{error.message}</p>
        <Button onClick={handleRetry} variant="outline">
          <RefreshCw className="h-4 w-4 mr-2" />
          Retry
        </Button>
      </div>
    );
  }

  return (
    <div className={cn('space-y-4', className)}>
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white">
            Audits {siteUrl && <span className="text-slate-400">for {siteUrl}</span>}
          </h1>
        </div>
        <Button onClick={() => setIsModalOpen(true)}>
          <Plus className="h-4 w-4 mr-2" />
          Start Audit
        </Button>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap items-center gap-4">
        <Select value={statusFilter} onValueChange={setStatusFilter}>
          <SelectTrigger className="w-40">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            {statusOptions.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {/* Refresh button */}
        <Button
          variant="ghost"
          size="sm"
          onClick={handleRetry}
          disabled={isLoading}
        >
          <RefreshCw className={cn('h-4 w-4', isLoading && 'animate-spin')} />
        </Button>
      </div>

      {/* Audit List */}
      {audits.length === 0 ? (
        <div className="text-center py-12 border border-slate-800 rounded-lg bg-slate-900/40">
          <FileSearch className="h-12 w-12 mx-auto mb-4 text-slate-500" />
          <h3 className="text-lg font-medium text-white mb-2">No audits yet</h3>
          <p className="text-slate-400 mb-4">
            Start your first audit to analyze your site&apos;s SEO health.
          </p>
          <Button onClick={() => setIsModalOpen(true)} data-testid="start-first-audit-btn">
            <Plus className="h-4 w-4 mr-2" />
            Start Your First Audit
          </Button>
        </div>
      ) : (
        <div className="space-y-3">
          {audits.map((audit) => (
            <AuditListCard
              key={audit.id}
              audit={audit}
              siteId={siteId}
              onCancel={handleCancelAudit}
              isCancelling={cancellingId === audit.id}
            />
          ))}
        </div>
      )}

      {/* Pagination */}
      {meta.last_page > 1 && (
        <div className="flex items-center justify-center gap-4 pt-4">
          <Button
            variant="outline"
            size="sm"
            onClick={() => setPage((p) => Math.max(1, p - 1))}
            disabled={page === 1 || isLoading}
          >
            Previous
          </Button>
          <span className="text-sm text-slate-400">
            Page {meta.current_page} of {meta.last_page}
          </span>
          <Button
            variant="outline"
            size="sm"
            onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
            disabled={page === meta.last_page || isLoading}
          >
            Next
          </Button>
        </div>
      )}

      {/* Start Audit Modal */}
      <StartAuditModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onConfirm={handleStartAudit}
        isLoading={startAudit.isPending}
        error={startAudit.error?.message}
      />
    </div>
  );
}

export default AuditList;
