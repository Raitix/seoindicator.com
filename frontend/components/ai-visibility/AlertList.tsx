'use client';

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import {
  Filter,
  X,
  Check,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  Loader2,
  Bell,
  Inbox,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { AlertNotification } from './AlertNotification';
import type { Alert, AlertType } from '@/lib/ai-visibility/types';

interface AlertListProps {
  alerts: Alert[];
  projects?: { id: string; name: string }[];
  onAlertClick?: (id: string) => void;
  onMarkRead?: (id: string) => void;
  onResolve?: (id: string) => void;
  onMarkAllRead?: () => void;
  onFilterChange?: (filters: AlertFilters) => void;
  onSortChange?: (sort: string) => void;
  onPageChange?: (page: number) => void;
  onLoadMore?: () => void;
  onRefetch?: () => void;
  onRetry?: () => void;
  onBulkMarkRead?: (ids: string[]) => void;
  onUrlChange?: () => void;
  filters?: AlertFilters;
  initialFilters?: AlertFilters;
  isLoading?: boolean;
  isLoadingMore?: boolean;
  error?: string | null;
  unreadCount?: number;
  pagination?: {
    currentPage: number;
    perPage: number;
    total: number;
    lastPage: number;
  };
  hasMore?: boolean;
  pollingInterval?: number;
  totalBeforeFilter?: number;
  enableSelection?: boolean;
}

interface AlertFilters {
  project_id?: string | null;
  alert_types?: AlertType[];
  is_read?: boolean | null;
}

const ALERT_TYPE_OPTIONS: { value: AlertType; label: string }[] = [
  { value: 'mention_spike', label: 'Mention Spike' },
  { value: 'mention_drop', label: 'Mention Drop' },
  { value: 'competitor_gain', label: 'Competitor Gain' },
  { value: 'sentiment_change', label: 'Sentiment Change' },
];

export function AlertList({
  alerts,
  projects = [],
  onAlertClick,
  onMarkRead,
  onResolve,
  onMarkAllRead,
  onFilterChange,
  onSortChange,
  onPageChange,
  onLoadMore,
  onRefetch,
  onRetry,
  onBulkMarkRead,
  onUrlChange,
  filters = {},
  initialFilters,
  isLoading = false,
  isLoadingMore = false,
  error = null,
  unreadCount = 0,
  pagination,
  hasMore = false,
  pollingInterval,
  totalBeforeFilter,
  enableSelection = false,
}: AlertListProps) {
  const [internalFilters, setInternalFilters] = useState<AlertFilters>(
    initialFilters || filters
  );
  const [projectDropdownOpen, setProjectDropdownOpen] = useState(false);
  const [typeDropdownOpen, setTypeDropdownOpen] = useState(false);
  const [selectedAlerts, setSelectedAlerts] = useState<Set<string>>(new Set());
  const [isMobile, setIsMobile] = useState(false);
  const [mobileDrawerOpen, setMobileDrawerOpen] = useState(false);
  const listRef = React.useRef<HTMLDivElement>(null);

  // Check for mobile viewport
  useEffect(() => {
    const checkMobile = () => setIsMobile(window.innerWidth < 640);
    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  // Polling
  useEffect(() => {
    if (pollingInterval && onRefetch) {
      const interval = setInterval(onRefetch, pollingInterval);
      return () => clearInterval(interval);
    }
  }, [pollingInterval, onRefetch]);

  // Infinite scroll
  useEffect(() => {
    if (!onLoadMore || !hasMore) return;

    const handleScroll = () => {
      if (!listRef.current) return;
      const { scrollTop, scrollHeight, clientHeight } = listRef.current;
      if (scrollHeight - scrollTop - clientHeight < 100) {
        onLoadMore();
      }
    };

    const list = listRef.current;
    list?.addEventListener('scroll', handleScroll);
    return () => list?.removeEventListener('scroll', handleScroll);
  }, [onLoadMore, hasMore]);

  const handleFilterChange = useCallback(
    (newFilters: Partial<AlertFilters>) => {
      const updated = { ...internalFilters, ...newFilters };
      setInternalFilters(updated);
      onFilterChange?.(updated);
      onUrlChange?.();
    },
    [internalFilters, onFilterChange, onUrlChange]
  );

  const clearFilters = useCallback(() => {
    const cleared: AlertFilters = {};
    setInternalFilters(cleared);
    onFilterChange?.(cleared);
    onUrlChange?.();
  }, [onFilterChange, onUrlChange]);

  const activeFilterCount = useMemo(() => {
    let count = 0;
    if (internalFilters.project_id) count++;
    if (internalFilters.alert_types?.length) count++;
    if (internalFilters.is_read !== null && internalFilters.is_read !== undefined) count++;
    return count;
  }, [internalFilters]);

  const handleAlertClick = useCallback(
    (id: string) => {
      if (enableSelection && selectedAlerts.has(id)) {
        const newSet = new Set(selectedAlerts);
        newSet.delete(id);
        setSelectedAlerts(newSet);
      } else if (enableSelection) {
        const newSet = new Set(selectedAlerts);
        newSet.add(id);
        setSelectedAlerts(newSet);
      }
      onAlertClick?.(id);
    },
    [enableSelection, selectedAlerts, onAlertClick]
  );

  const clearSelection = useCallback(() => {
    setSelectedAlerts(new Set());
  }, []);

  const handleBulkMarkRead = useCallback(() => {
    if (onBulkMarkRead && selectedAlerts.size > 0) {
      onBulkMarkRead(Array.from(selectedAlerts));
      clearSelection();
    }
  }, [onBulkMarkRead, selectedAlerts, clearSelection]);

  // Loading skeleton
  if (isLoading && alerts.length === 0) {
    return (
      <div data-testid="alert-list" className="space-y-3">
        {Array.from({ length: 5 }).map((_, i) => (
          <div
            key={i}
            data-testid={`skeleton-${i}`}
            className="animate-pulse rounded-lg border border-slate-200 bg-slate-100 p-4"
          >
            <div className="flex items-start gap-3">
              <div className="h-8 w-8 rounded-full bg-slate-200" />
              <div className="flex-1 space-y-2">
                <div className="h-4 w-1/3 rounded bg-slate-200" />
                <div className="h-4 w-2/3 rounded bg-slate-200" />
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="flex flex-col items-center justify-center py-12 text-center">
        <p className="text-red-600 dark:text-red-400 mb-4">{error}</p>
        {onRetry && (
          <button
            onClick={onRetry}
            className="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg transition-colors"
          >
            Retry
          </button>
        )}
      </div>
    );
  }

  // Empty state
  if (!isLoading && alerts.length === 0) {
    const hasActiveFilters = activeFilterCount > 0;
    return (
      <div data-testid="empty-state" className="flex flex-col items-center justify-center py-12 text-center">
        <Inbox className="h-12 w-12 text-slate-300 dark:text-slate-600 mb-4" />
        <p className="text-slate-600 dark:text-slate-400">
          {hasActiveFilters
            ? 'No alerts match your filters'
            : 'No alerts yet'}
        </p>
        {hasActiveFilters && (
          <button
            onClick={clearFilters}
            className="mt-2 text-sm text-blue-600 hover:text-blue-700"
          >
            Clear all filters
          </button>
        )}
      </div>
    );
  }

  const isAllRead = unreadCount === 0;

  return (
    <div data-testid="alert-list" className="dark:bg-slate-900 rounded-lg transition-all">
      {/* Filter Header */}
      <div
        data-testid="filter-header"
        className={cn(
          'sticky top-0 z-10 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 p-4',
          isMobile && 'flex items-center justify-between'
        )}
      >
        {isMobile ? (
          <>
            <button
              onClick={() => setMobileDrawerOpen(true)}
              className="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400"
              aria-label="Open filters"
            >
              <Filter className="h-4 w-4" />
              Filters
              {activeFilterCount > 0 && (
                <span data-testid="filter-count-badge" className="bg-blue-500 text-white text-xs px-1.5 py-0.5 rounded-full">
                  {activeFilterCount}
                </span>
              )}
            </button>
            {unreadCount > 0 && (
              <span data-testid="unread-count-badge" className="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                {unreadCount}
              </span>
            )}
          </>
        ) : (
          <div className="flex flex-wrap items-center gap-3">
            {/* Project Filter */}
            <div className="relative">
              <button
                onClick={() => setProjectDropdownOpen(!projectDropdownOpen)}
                className="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700"
                aria-label="Filter by project"
                aria-expanded={projectDropdownOpen}
              >
                <Filter className="h-3 w-3" />
                Project
                <ChevronDown className="h-3 w-3" />
              </button>
              {projectDropdownOpen && (
                <div
                  role="listbox"
                  aria-label="Select project"
                  className="absolute top-full left-0 mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg z-20 min-w-[200px]"
                >
                  {projects.map((project) => (
                    <button
                      key={project.id}
                      role="option"
                      aria-selected={internalFilters.project_id === project.id}
                      onClick={() => {
                        handleFilterChange({ project_id: project.id });
                        setProjectDropdownOpen(false);
                      }}
                      className="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700"
                    >
                      {project.name}
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* Type Filter */}
            <div className="relative">
              <button
                onClick={() => setTypeDropdownOpen(!typeDropdownOpen)}
                className="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700"
                aria-label="Filter by type"
                aria-expanded={typeDropdownOpen}
              >
                Type
                <ChevronDown className="h-3 w-3" />
              </button>
              {typeDropdownOpen && (
                <div
                  role="listbox"
                  aria-label="Select types"
                  className="absolute top-full left-0 mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg z-20 min-w-[200px]"
                >
                  {ALERT_TYPE_OPTIONS.map((option) => (
                    <button
                      key={option.value}
                      role="option"
                      aria-selected={internalFilters.alert_types?.includes(option.value)}
                      onClick={() => {
                        const current = internalFilters.alert_types || [];
                        const updated = current.includes(option.value)
                          ? current.filter((t) => t !== option.value)
                          : [...current, option.value];
                        handleFilterChange({ alert_types: updated });
                      }}
                      className="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2"
                    >
                      {internalFilters.alert_types?.includes(option.value) && (
                        <Check className="h-3 w-3" />
                      )}
                      {option.label}
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* Read/Unread Toggle */}
            <button
              onClick={() =>
                handleFilterChange({
                  is_read:
                    internalFilters.is_read === false ? null : false,
                })
              }
              className="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700"
              aria-label={internalFilters.is_read === false ? 'Show all alerts' : 'Show unread only'}
            >
              {internalFilters.is_read === false ? 'Unread Only' : 'All Alerts'}
            </button>

            {/* Active Filters */}
            {activeFilterCount > 0 && (
              <button
                onClick={clearFilters}
                className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                aria-label="Clear all filters"
              >
                <X className="h-3 w-3" />
                Clear all
              </button>
            )}

            {/* Filter Count Badge */}
            {activeFilterCount > 0 && (
              <span
                data-testid="filter-count-badge"
                className="bg-blue-500 text-white text-xs px-1.5 py-0.5 rounded-full"
              >
                {activeFilterCount}
              </span>
            )}

            {/* Spacer */}
            <div className="flex-1" />

            {/* Unread Count Badge */}
            {unreadCount > 0 && (
              <span
                data-testid="unread-count-badge"
                className="bg-blue-500 text-white text-xs px-2 py-1 rounded-full"
              >
                {unreadCount}
              </span>
            )}

            {/* Mark All as Read */}
            {onMarkAllRead && (
              <button
                onClick={onMarkAllRead}
                disabled={isAllRead}
                className="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                aria-label="Mark all as read"
              >
                <Check className="h-3 w-3" />
                Mark All as Read
              </button>
            )}
          </div>
        )}
      </div>

      {/* Mobile Filter Drawer */}
      {isMobile && mobileDrawerOpen && (
        <>
          <div
            data-testid="drawer-overlay"
            className="fixed inset-0 bg-black/50 z-30"
            onClick={() => setMobileDrawerOpen(false)}
          />
          <div
            data-testid="filter-drawer"
            className="fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-800 rounded-t-2xl z-40 p-4 max-h-[70vh] overflow-y-auto"
          >
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-medium">Filters</h3>
              <button onClick={() => setMobileDrawerOpen(false)}>
                <X className="h-5 w-5" />
              </button>
            </div>
            {/* Mobile filter content */}
            <div className="space-y-4">
              <div>
                <label className="text-sm text-slate-600 dark:text-slate-400">Project</label>
                <select
                  value={internalFilters.project_id || ''}
                  onChange={(e) => handleFilterChange({ project_id: e.target.value || null })}
                  className="w-full mt-1 px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800"
                >
                  <option value="">All Projects</option>
                  {projects.map((p) => (
                    <option key={p.id} value={p.id}>{p.name}</option>
                  ))}
                </select>
              </div>
            </div>
          </div>
        </>
      )}

      {/* Bulk Actions Toolbar */}
      {enableSelection && selectedAlerts.size >= 2 && (
        <div
          data-testid="bulk-actions-toolbar"
          className="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800"
        >
          <span className="text-sm text-blue-700 dark:text-blue-300">
            {selectedAlerts.size} selected
          </span>
          {onBulkMarkRead && (
            <button
              onClick={handleBulkMarkRead}
              className="text-sm text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200"
            >
              Mark selected as read
            </button>
          )}
          <button
            onClick={clearSelection}
            className="text-sm text-slate-600 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300"
          >
            Clear selection
          </button>
        </div>
      )}

      {/* Alert List */}
      <div ref={listRef} className="divide-y divide-slate-100 dark:divide-slate-800">
        {alerts.map((alert) => (
          <div key={alert.id} className="p-2">
            <AlertNotification
              alert={alert}
              onClick={handleAlertClick}
              onMarkRead={onMarkRead}
              onResolve={onResolve}
              isSelected={selectedAlerts.has(alert.id)}
              variant="default"
            />
          </div>
        ))}
      </div>

      {/* Filtered Count */}
      {totalBeforeFilter !== undefined && totalBeforeFilter > alerts.length && (
        <div className="p-4 text-center text-sm text-slate-500">
          {alerts.length} of {totalBeforeFilter} alerts
        </div>
      )}

      {/* Loading More Indicator */}
      {isLoadingMore && (
        <div data-testid="loading-more" className="flex justify-center p-4">
          <Loader2 className="h-6 w-6 animate-spin text-slate-400" />
        </div>
      )}

      {/* End of Results */}
      {pagination && pagination.currentPage === pagination.lastPage && alerts.length > 0 && (
        <div className="p-4 text-center text-sm text-slate-500">
          End of results
        </div>
      )}

      {/* Pagination */}
      {pagination && pagination.lastPage > 1 && (
        <div data-testid="pagination" className="flex items-center justify-center gap-2 p-4">
          <button
            onClick={() => onPageChange?.(pagination.currentPage - 1)}
            disabled={pagination.currentPage === 1}
            className="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50"
          >
            <ChevronLeft className="h-4 w-4" />
          </button>
          {Array.from({ length: pagination.lastPage }, (_, i) => i + 1).map((page) => (
            <button
              key={page}
              onClick={() => onPageChange?.(page)}
              aria-label={`Page ${page}`}
              className={cn(
                'px-3 py-1 rounded-lg text-sm',
                page === pagination.currentPage
                  ? 'bg-blue-500 text-white'
                  : 'hover:bg-slate-100 dark:hover:bg-slate-800'
              )}
            >
              {page}
            </button>
          ))}
          <button
            onClick={() => onPageChange?.(pagination.currentPage + 1)}
            disabled={pagination.currentPage === pagination.lastPage}
            className="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50"
          >
            <ChevronRight className="h-4 w-4" />
          </button>
        </div>
      )}
    </div>
  );
}
