/**
 * IssueFilters Component
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Filter controls for the issue list.
 */

"use client";

import * as React from 'react';
import { Search, X, ArrowUpDown, ArrowUp, ArrowDown, Filter } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type {
  IssueFilters as IssueFiltersType,
  IssueSeverity,
  IssueStatus,
  SortField,
  SortOrder,
} from '@/lib/types/audit-issues';
import {
  ISSUE_TYPE_OPTIONS,
  SEVERITY_OPTIONS,
  STATUS_OPTIONS,
  SORT_OPTIONS,
} from '@/lib/types/audit-issues';

// ============================================================
// Props
// ============================================================

export interface IssueFiltersProps {
  /** Current filter values */
  filters: IssueFiltersType;
  /** Callback when filters change */
  onFilterChange: (filters: Partial<IssueFiltersType>) => void;
  /** Number of active filters */
  activeFilterCount?: number;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Helper to count active filters
// ============================================================

export function countActiveFilters(filters: IssueFiltersType): number {
  let count = 0;
  if (filters.type) count++;
  if (filters.severity) count++;
  if (filters.status) count++;
  if (filters.search) count++;
  return count;
}

// ============================================================
// Component
// ============================================================

export function IssueFilters({
  filters,
  onFilterChange,
  activeFilterCount,
  className,
}: IssueFiltersProps) {
  const [searchValue, setSearchValue] = React.useState(filters.search || '');
  const searchTimeoutRef = React.useRef<NodeJS.Timeout | null>(null);

  // Calculate active filter count
  const activeCount = activeFilterCount ?? countActiveFilters(filters);

  // Debounced search
  const handleSearchChange = React.useCallback(
    (value: string) => {
      setSearchValue(value);

      // Clear previous timeout
      if (searchTimeoutRef.current) {
        clearTimeout(searchTimeoutRef.current);
      }

      // Set new timeout (300ms debounce)
      searchTimeoutRef.current = setTimeout(() => {
        onFilterChange({ search: value || undefined });
      }, 300);
    },
    [onFilterChange]
  );

  // Clear search
  const handleClearSearch = React.useCallback(() => {
    setSearchValue('');
    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
    }
    onFilterChange({ search: undefined });
  }, [onFilterChange]);

  // Clear all filters
  const handleClearAll = React.useCallback(() => {
    setSearchValue('');
    onFilterChange({
      type: undefined,
      severity: undefined,
      status: undefined,
      search: undefined,
    });
  }, [onFilterChange]);

  // Toggle sort order
  const handleToggleSortOrder = React.useCallback(() => {
    onFilterChange({
      sortOrder: filters.sortOrder === 'asc' ? 'desc' : 'asc',
    });
  }, [filters.sortOrder, onFilterChange]);

  // Keyboard shortcut for search focus
  React.useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      // Ctrl+K or Cmd+K to focus search
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('issue-search-input')?.focus();
      }
      // Escape to clear filters
      if (e.key === 'Escape') {
        handleClearAll();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [handleClearAll]);

  // Cleanup timeout on unmount
  React.useEffect(() => {
    return () => {
      if (searchTimeoutRef.current) {
        clearTimeout(searchTimeoutRef.current);
      }
    };
  }, []);

  return (
    <div data-testid="issue-filters" className={cn('space-y-3', className)}>
      {/* Filter Row */}
      <div className="flex flex-wrap items-center gap-2">
        {/* Issue Type Filter */}
        <Select
          value={filters.type || ''}
          onValueChange={(value) => onFilterChange({ type: value || undefined })}
        >
          <SelectTrigger
            data-testid="type-filter"
            className="w-[140px] h-9 text-sm"
            aria-label="Filter by issue type"
          >
            <SelectValue placeholder="All Issues" />
          </SelectTrigger>
          <SelectContent>
            {ISSUE_TYPE_OPTIONS.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {/* Severity Filter */}
        <Select
          value={filters.severity || ''}
          onValueChange={(value) => onFilterChange({ severity: (value || undefined) as IssueSeverity | undefined })}
        >
          <SelectTrigger
            data-testid="severity-filter"
            className="w-[150px] h-9 text-sm"
            aria-label="Filter by severity"
          >
            <SelectValue placeholder="All Severities" />
          </SelectTrigger>
          <SelectContent>
            {SEVERITY_OPTIONS.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {/* Status Filter */}
        <Select
          value={filters.status || ''}
          onValueChange={(value) => onFilterChange({ status: (value || undefined) as IssueStatus | undefined })}
        >
          <SelectTrigger
            data-testid="status-filter"
            className="w-[140px] h-9 text-sm"
            aria-label="Filter by status"
          >
            <SelectValue placeholder="All Statuses" />
          </SelectTrigger>
          <SelectContent>
            {STATUS_OPTIONS.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {/* Search Input */}
        <div className="relative flex-1 min-w-[200px]">
          <Search
            className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
            aria-hidden="true"
          />
          <Input
            id="issue-search-input"
            type="text"
            placeholder="Search URL or description..."
            value={searchValue}
            onChange={(e) => handleSearchChange(e.target.value)}
            className="pl-9 pr-8 h-9 text-sm"
            data-testid="search-input"
            aria-label="Search issues"
          />
          {searchValue && (
            <button
              onClick={handleClearSearch}
              className="absolute right-2 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-white transition-colors"
              aria-label="Clear search"
              data-testid="clear-search-button"
            >
              <X className="h-4 w-4" />
            </button>
          )}
        </div>

        {/* Clear Filters Button */}
        {activeCount > 0 && (
          <Button
            variant="ghost"
            size="sm"
            onClick={handleClearAll}
            data-testid="clear-filters-button"
            className="h-9 text-slate-400 hover:text-white"
          >
            <X className="h-4 w-4 mr-1" aria-hidden="true" />
            Clear
          </Button>
        )}
      </div>

      {/* Sort Row */}
      <div className="flex items-center gap-2">
        <span className="text-sm text-slate-400">Sort by:</span>
        
        <Select
          value={filters.sortBy || 'created_at'}
          onValueChange={(value) => onFilterChange({ sortBy: value as SortField })}
        >
          <SelectTrigger
            data-testid="sort-field"
            className="w-[140px] h-8 text-sm"
            aria-label="Sort field"
          >
            <SelectValue placeholder="Date Found" />
          </SelectTrigger>
          <SelectContent>
            {SORT_OPTIONS.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <Button
          variant="ghost"
          size="sm"
          onClick={handleToggleSortOrder}
          data-testid="sort-order-button"
          className="h-8 px-2 text-slate-400 hover:text-white"
          aria-label={filters.sortOrder === 'asc' ? 'Oldest first' : 'Newest first'}
        >
          {filters.sortOrder === 'asc' ? (
            <>
              <ArrowUp className="h-4 w-4 mr-1" aria-hidden="true" />
              Oldest First
            </>
          ) : (
            <>
              <ArrowDown className="h-4 w-4 mr-1" aria-hidden="true" />
              Newest First
            </>
          )}
        </Button>

        {/* Active Filter Count Badge */}
        {activeCount > 0 && (
          <span
            data-testid="active-filter-count"
            className="ml-auto inline-flex items-center gap-1 text-xs text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full"
          >
            <Filter className="h-3 w-3" aria-hidden="true" />
            {activeCount} filter{activeCount > 1 ? 's' : ''} active
          </span>
        )}
      </div>

      {/* Active Filter Chips */}
      {activeCount > 0 && (
        <div className="flex flex-wrap gap-2" data-testid="active-filter-chips">
          {filters.type && (
            <FilterChip
              label={`Type: ${filters.type}`}
              onRemove={() => onFilterChange({ type: undefined })}
            />
          )}
          {filters.severity && (
            <FilterChip
              label={`Severity: ${filters.severity}`}
              onRemove={() => onFilterChange({ severity: undefined })}
            />
          )}
          {filters.status && (
            <FilterChip
              label={`Status: ${filters.status}`}
              onRemove={() => onFilterChange({ status: undefined })}
            />
          )}
          {filters.search && (
            <FilterChip
              label={`Search: "${filters.search}"`}
              onRemove={() => {
                setSearchValue('');
                onFilterChange({ search: undefined });
              }}
            />
          )}
        </div>
      )}
    </div>
  );
}

// ============================================================
// Filter Chip Component
// ============================================================

interface FilterChipProps {
  label: string;
  onRemove: () => void;
}

function FilterChip({ label, onRemove }: FilterChipProps) {
  return (
    <span
      data-testid="filter-chip"
      className="inline-flex items-center gap-1 text-xs bg-slate-800 text-slate-300 px-2 py-1 rounded-full"
    >
      {label}
      <button
        onClick={onRemove}
        className="p-0.5 text-slate-400 hover:text-white transition-colors"
        aria-label={`Remove filter: ${label}`}
        data-testid="remove-filter-chip"
      >
        <X className="h-3 w-3" />
      </button>
    </span>
  );
}

export default IssueFilters;
