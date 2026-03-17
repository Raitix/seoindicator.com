/**
 * AuditDetailPage Component
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Main page component for audit detail view.
 */

"use client";

import * as React from 'react';
import { useParams, useSearchParams, useRouter } from 'next/navigation';
import { cn } from '@/lib/utils';
import { AlertCircle, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { AuditSummary } from '@/components/audit/AuditSummary';
import { IssueFilters, countActiveFilters } from '@/components/audit/IssueFilters';
import { IssueList } from '@/components/audit/IssueList';
import { BulkActions } from '@/components/audit/BulkActions';
import {
  useAuditSummary,
  useAuditIssues,
  useUpdateIssueStatus,
  useBulkUpdateIssues,
} from '@/lib/api/audit-issues';
import type { IssueFilters as IssueFiltersType, IssueStatus, IssueSeverity } from '@/lib/types/audit-issues';

// ============================================================
// Props
// ============================================================

export interface AuditDetailPageProps {
  /** Site ID from route params */
  siteId?: string;
  /** Audit ID from route params */
  auditId?: string;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Component
// ============================================================

export function AuditDetailPage({ siteId: siteIdProp, auditId: auditIdProp, className }: AuditDetailPageProps) {
  const params = useParams();
  const router = useRouter();
  const searchParams = useSearchParams();

  // Get route params - prefer props over useParams for SSR
  const siteId = siteIdProp ?? (params.siteId as string);
  const auditId = auditIdProp ?? (params.auditId as string);

  // Parse URL params for filters - use useMemo to prevent re-creation on every render
  const initialFilters = React.useMemo<IssueFiltersType>(() => ({
    type: searchParams?.get('type') || undefined,
    severity: (searchParams?.get('severity') as IssueSeverity) || undefined,
    status: (searchParams?.get('status') as IssueStatus) || undefined,
    search: searchParams?.get('q') || undefined,
    sortBy: (searchParams?.get('sortBy') as IssueFiltersType['sortBy']) || 'created_at',
    sortOrder: (searchParams?.get('sortOrder') as 'asc' | 'desc') || 'desc',
  }), [searchParams]);

  const initialPage = React.useMemo(() => parseInt(searchParams?.get('page') || '1', 10), [searchParams]);
  const initialPerPage = React.useMemo(() => parseInt(searchParams?.get('perPage') || '25', 10), [searchParams]);

  // State
  const [filters, setFilters] = React.useState<IssueFiltersType>(initialFilters);
  const [page, setPage] = React.useState(initialPage);
  const [perPage, setPerPage] = React.useState(initialPerPage);
  const [selectedIds, setSelectedIds] = React.useState<number[]>([]);
  const [updatingIds, setUpdatingIds] = React.useState<Set<number>>(new Set());

  // Hooks
  const {
    audit,
    severityCounts,
    isLoading: isAuditLoading,
    error: auditError,
    mutate: mutateAudit,
  } = useAuditSummary(auditId);

  const {
    issues,
    meta,
    isLoading: isIssuesLoading,
    error: issuesError,
    mutate: mutateIssues,
  } = useAuditIssues(auditId, filters, { page, perPage });

  const { updateStatus } = useUpdateIssueStatus();
  const { bulkUpdate } = useBulkUpdateIssues();

  // Loading state (computed before useEffect)
  const isLoading = isAuditLoading || isIssuesLoading;
  const error = auditError || issuesError;

  // Track if component is mounted and has successfully loaded data
  const isMountedRef = React.useRef(false);
  
  // Track previous URL to prevent unnecessary navigation
  const prevUrlRef = React.useRef<string>('');

  // Update URL when filters change (only after successful data load)
  // Note: We intentionally omit 'router' from deps to prevent infinite loops
  // The router reference is stable, but including it causes re-renders
  React.useEffect(() => {
    // Don't update URL during loading or error states
    if (isLoading || error) {
      return;
    }

    // Don't update URL on first render until we have data
    if (!audit && !isMountedRef.current) {
      return;
    }

    isMountedRef.current = true;

    const params = new URLSearchParams();

    if (filters.type) params.set('type', filters.type);
    if (filters.severity) params.set('severity', filters.severity);
    if (filters.status) params.set('status', filters.status);
    if (filters.search) params.set('q', filters.search);
    if (filters.sortBy && filters.sortBy !== 'created_at') params.set('sortBy', filters.sortBy);
    if (filters.sortOrder && filters.sortOrder !== 'desc') params.set('sortOrder', filters.sortOrder);
    if (page > 1) params.set('page', String(page));
    if (perPage !== 25) params.set('perPage', String(perPage));

    const queryString = params.toString();
    const url = queryString ? `?${queryString}` : window.location.pathname;
    
    // Only update if URL actually changed
    if (url === prevUrlRef.current) {
      return;
    }
    prevUrlRef.current = url;
    
    // Use try-catch to handle any navigation errors gracefully
    // Use window.history.replaceState to avoid router re-renders
    try {
      window.history.replaceState(null, '', url);
    } catch (navError) {
      console.warn('Navigation error:', navError);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filters, page, perPage, isLoading, error, audit]);

  // Clear selection when issues change
  React.useEffect(() => {
    setSelectedIds([]);
  }, [issues]);

  // Handle filter change
  const handleFilterChange = React.useCallback(
    (newFilters: Partial<IssueFiltersType>) => {
      setFilters((prev) => ({ ...prev, ...newFilters }));
      setPage(1); // Reset to first page when filters change
    },
    []
  );

  // Handle severity card click
  const handleSeverityClick = React.useCallback(
    (severity: IssueSeverity) => {
      if (filters.severity === severity) {
        handleFilterChange({ severity: undefined });
      } else {
        handleFilterChange({ severity });
      }
    },
    [filters.severity, handleFilterChange]
  );

  // Handle page change
  const handlePageChange = React.useCallback((newPage: number) => {
    setPage(newPage);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }, []);

  // Handle per page change
  const handlePerPageChange = React.useCallback((newPerPage: number) => {
    setPerPage(newPerPage);
    setPage(1);
  }, []);

  // Handle status change for single issue
  const handleStatusChange = React.useCallback(
    async (issueId: number, status: IssueStatus) => {
      setUpdatingIds((prev) => new Set(prev).add(issueId));
      try {
        await updateStatus({ issueId, status });
        mutateIssues();
        mutateAudit();
      } catch (error) {
        console.error('Failed to update issue status:', error);
      } finally {
        setUpdatingIds((prev) => {
          const next = new Set(prev);
          next.delete(issueId);
          return next;
        });
      }
    },
    [updateStatus, mutateIssues, mutateAudit]
  );

  // Handle bulk action
  const handleBulkAction = React.useCallback(
    async (status: 'fixed' | 'ignored', ids: number[]) => {
      try {
        await bulkUpdate({ auditId, issueIds: ids, status });
        mutateIssues();
        mutateAudit();
        setSelectedIds([]);
      } catch (error) {
        console.error('Failed to bulk update issues:', error);
      }
    },
    [auditId, bulkUpdate, mutateIssues, mutateAudit]
  );

  // Handle refresh
  const handleRefresh = React.useCallback(() => {
    mutateAudit();
    mutateIssues();
  }, [mutateAudit, mutateIssues]);

  // Error state
  if (error && !isLoading) {
    const isNotFound = error.message === 'Not found';
    const isUnauthorized = error.message === 'Unauthorized';
    const isAccessDenied = error.message === 'Access denied';

    return (
      <div className={cn('min-h-screen bg-slate-950 flex items-center justify-center p-4', className)}>
        <div className="text-center max-w-md">
          <AlertCircle className="h-12 w-12 text-red-400 mx-auto mb-4" aria-hidden="true" />
          <h1 className="text-xl font-semibold text-white mb-2">
            {isNotFound ? 'Audit Not Found' : isUnauthorized ? 'Sign In Required' : isAccessDenied ? 'Access Denied' : 'Error Loading Audit'}
          </h1>
          <p className="text-slate-400 mb-4">
            {isNotFound
              ? 'The audit you are looking for does not exist or has been deleted.'
              : isUnauthorized
              ? 'Please sign in to view this audit.'
              : isAccessDenied
              ? 'You do not have permission to view this audit.'
              : error.message}
          </p>
          <div className="flex gap-3 justify-center">
            <Button variant="outline" onClick={() => router.push(`/sites/${siteId}/audits`)}>
              Back to Audits
            </Button>
            {!isNotFound && !isAccessDenied && (
              <Button onClick={handleRefresh} variant="default">
                <RefreshCw className="h-4 w-4 mr-2" aria-hidden="true" />
                Retry
              </Button>
            )}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className={cn('min-h-screen bg-slate-950', className)}>
      <main className="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        {/* Summary Section */}
        <section aria-label="Audit summary" className="mb-6">
          <AuditSummary
            audit={audit}
            severityCounts={severityCounts}
            onSeverityClick={handleSeverityClick}
            activeSeverity={filters.severity}
            onRefresh={handleRefresh}
            isLoading={isLoading}
            siteId={siteId}
          />
        </section>

        {/* Filters Section */}
        <section aria-label="Issue filters" className="mb-6">
          <IssueFilters
            filters={filters}
            onFilterChange={handleFilterChange}
            activeFilterCount={countActiveFilters(filters)}
          />
        </section>

        {/* Issue List Section */}
        <section aria-label="Issues" className="mb-20">
          <IssueList
            issues={issues}
            isLoading={isIssuesLoading}
            error={issuesError}
            meta={meta}
            selectedIds={selectedIds}
            onSelectionChange={setSelectedIds}
            onStatusChange={handleStatusChange}
            onPageChange={handlePageChange}
            onPerPageChange={handlePerPageChange}
          />
        </section>

        {/* Bulk Actions */}
        {selectedIds.length > 0 && (
          <div className="fixed bottom-4 left-1/2 -translate-x-1/2 w-full max-w-3xl px-4">
            <BulkActions
              selectedIds={selectedIds}
              totalOnPage={issues.length}
              totalIssues={meta?.total}
              currentPage={meta?.current_page}
              totalPages={meta?.last_page}
              onBulkAction={handleBulkAction}
              onClearSelection={() => setSelectedIds([])}
              onSelectAll={() => setSelectedIds(issues.map((i) => i.id))}
            />
          </div>
        )}
      </main>
    </div>
  );
}

export default AuditDetailPage;
