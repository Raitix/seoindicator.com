"use client";

import * as React from "react";
import { Suspense } from "react";
import { useRouter } from "next/navigation";
import { RefreshCw, History, AlertCircle } from "lucide-react";

import { useAuth } from "@/lib/contexts/AuthContext";
import { useAnalysisHistory, useAnalysisDelete, useAnalysisDetail } from "@/lib/hooks/useAnalysisHistory";
import { Button } from "@/components/ui/button";
import {
  HistoryFilters,
  HistoryTable,
  Pagination,
  AnalysisDetailsModal,
  DeleteConfirmationDialog,
  EmptyState,
  HistorySkeleton,
} from "@/components/history";

import type { AnalysisRecord } from "@/lib/types/analysis";

// ==================== History Content (uses useSearchParams) ====================

function HistoryContent() {
  const router = useRouter();
  const { isAuthenticated, isLoading: authLoading } = useAuth();

  // Analysis history hook (uses useSearchParams internally)
  const {
    data,
    isLoading,
    error,
    filters,
    setFilters,
    clearFilters,
    refresh,
    goToPage,
    changePerPage,
  } = useAnalysisHistory();

  // Analysis detail hook
  const {
    data: selectedAnalysis,
    isLoading: isLoadingDetail,
    fetch: fetchDetail,
  } = useAnalysisDetail();

  // Local state for modals
  const [detailsModalOpen, setDetailsModalOpen] = React.useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = React.useState(false);
  const [analysisToDelete, setAnalysisToDelete] = React.useState<AnalysisRecord | null>(null);

  // Delete hook
  const { deleteOne, isDeleting } = useAnalysisDelete(() => {
    setDeleteDialogOpen(false);
    setAnalysisToDelete(null);
    refresh();
  });

  // Redirect to login if not authenticated
  React.useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.push("/login");
    }
  }, [authLoading, isAuthenticated, router]);

  // Handle row click - view details
  const handleRowClick = React.useCallback(
    (analysis: AnalysisRecord) => {
      fetchDetail(analysis.id);
      setDetailsModalOpen(true);
    },
    [fetchDetail]
  );

  // Handle view details button
  const handleViewDetails = React.useCallback(
    (analysis: AnalysisRecord) => {
      fetchDetail(analysis.id);
      setDetailsModalOpen(true);
    },
    [fetchDetail]
  );

  // Handle delete button
  const handleDelete = React.useCallback((analysis: AnalysisRecord) => {
    setAnalysisToDelete(analysis);
    setDeleteDialogOpen(true);
  }, []);

  // Confirm delete
  const confirmDelete = React.useCallback(async () => {
    if (analysisToDelete) {
      await deleteOne(analysisToDelete.id);
    }
  }, [analysisToDelete, deleteOne]);

  // Close details modal
  const closeDetailsModal = React.useCallback(() => {
    setDetailsModalOpen(false);
  }, []);

  // Close delete dialog
  const closeDeleteDialog = React.useCallback(() => {
    if (!isDeleting) {
      setDeleteDialogOpen(false);
      setAnalysisToDelete(null);
    }
  }, [isDeleting]);

  // Check if any filters are active
  const hasActiveFilters = Boolean(filters.tool || filters.status || filters.from || filters.to);

  // Auth loading state
  if (authLoading) {
    return (
      <div className="min-h-screen bg-slate-950" data-testid="auth-loading">
        <div className="container mx-auto px-4 py-10">
          <HistorySkeleton />
        </div>
      </div>
    );
  }

  // Not authenticated (will redirect)
  if (!isAuthenticated) {
    return (
      <div className="min-h-screen bg-slate-950" data-testid="auth-loading">
        <div className="container mx-auto px-4 py-10">
          <HistorySkeleton />
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-950" data-testid="history-page">
      <div className="container mx-auto px-4 py-10">
        {/* Header */}
        <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
          <div>
            <h1 className="text-2xl font-semibold text-white flex items-center gap-2">
              <History className="h-6 w-6" />
              Analysis History
            </h1>
            <p className="text-sm text-slate-400 mt-1">
              View and manage your past SEO analyses
            </p>
          </div>
          <Button
            onClick={refresh}
            variant="outline"
            className="border-slate-700 text-slate-200"
            disabled={isLoading}
          >
            <RefreshCw className={`h-4 w-4 mr-2 ${isLoading ? "animate-spin" : ""}`} />
            Refresh
          </Button>
        </div>

        {/* Filters */}
        <div className="mb-6">
          <HistoryFilters
            filters={filters}
            onFiltersChange={setFilters}
            onClear={clearFilters}
          />
        </div>

        {/* Error State */}
        {error && (
          <div className="rounded-lg border border-red-500/30 bg-red-500/10 p-6 text-center mb-6">
            <AlertCircle className="h-10 w-10 text-red-400 mx-auto mb-3" />
            <h3 className="text-lg font-medium text-white mb-2">Failed to load analyses</h3>
            <p className="text-sm text-red-300 mb-4">
              {error.message || "An error occurred while loading your analysis history."}
            </p>
            <Button
              onClick={refresh}
              variant="outline"
              className="border-red-500/30 text-red-300"
            >
              <RefreshCw className="h-4 w-4 mr-2" />
              Retry
            </Button>
          </div>
        )}

        {/* Loading State */}
        {isLoading && !error && <HistorySkeleton rows={filters.perPage || 15} />}

        {/* Content */}
        {!isLoading && !error && (
          <>
            {/* Empty State */}
            {data && data.data.length === 0 ? (
              <div className="rounded-lg border border-slate-800">
                <EmptyState
                  hasFilters={hasActiveFilters}
                  onClearFilters={clearFilters}
                />
              </div>
            ) : (
              <>
                {/* History Table */}
                {data && (
                  <>
                    <HistoryTable
                      analyses={data.data}
                      onRowClick={handleRowClick}
                      onViewDetails={handleViewDetails}
                      onDelete={handleDelete}
                    />

                    {/* Pagination */}
                    {data.meta && data.meta.total > (filters.perPage || 15) && (
                      <Pagination
                        meta={data.meta}
                        onPageChange={goToPage}
                        onPerPageChange={changePerPage}
                      />
                    )}
                  </>
                )}
              </>
            )}
          </>
        )}

        {/* Analysis Details Modal */}
        <AnalysisDetailsModal
          analysis={selectedAnalysis || null}
          isOpen={detailsModalOpen}
          onClose={closeDetailsModal}
        />

        {/* Delete Confirmation Dialog */}
        <DeleteConfirmationDialog
          analysis={analysisToDelete}
          isOpen={deleteDialogOpen}
          onClose={closeDeleteDialog}
          onConfirm={confirmDelete}
          isDeleting={isDeleting}
        />
      </div>
    </div>
  );
}

// ==================== History Page (with Suspense) ====================

export default function HistoryPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen bg-slate-950">
          <div className="container mx-auto px-4 py-10">
            <HistorySkeleton />
          </div>
        </div>
      }
    >
      <HistoryContent />
    </Suspense>
  );
}
