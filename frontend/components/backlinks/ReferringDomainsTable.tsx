'use client';

import React, { useState, useMemo } from 'react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  ChevronUp,
  ChevronDown,
  ChevronsUpDown,
  ChevronLeft,
  ChevronRight,
  ExternalLink,
  Building2,
  Clock,
} from 'lucide-react';
import type {
  ReferringDomain,
  DomainsMeta,
  DomainsSortField,
  SortOrder,
} from './types';

// ==================== Types ====================

interface ReferringDomainsTableProps {
  projectId: number;
  domains?: ReferringDomain[];
  meta?: DomainsMeta;
  loading?: boolean;
  onSort?: (field: DomainsSortField, order: SortOrder) => void;
  onPageChange?: (page: number) => void;
  sortField?: DomainsSortField;
  sortOrder?: SortOrder;
  currentPage?: number;
  className?: string;
}

interface SortConfig {
  field: DomainsSortField;
  order: SortOrder;
}

// ==================== Constants ====================

const SORT_COLUMNS: { key: DomainsSortField; label: string }[] = [
  { key: 'domain', label: 'Domain' },
  { key: 'backlinks_count', label: 'Backlinks' },
  { key: 'domain_trust_score', label: 'Trust Score' },
  { key: 'first_seen_at', label: 'First Seen' },
];

const PAGE_SIZES = [10, 25, 50, 100];

// ==================== Helper Functions ====================

/**
 * Format date for display
 */
function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return 'Never';
  
  const date = new Date(dateStr);
  if (Number.isNaN(date.getTime())) return 'Invalid';
  
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(date);
}

/**
 * Format relative time
 */
function formatRelativeTime(dateStr: string | null | undefined): string {
  if (!dateStr) return 'Never';
  
  const date = new Date(dateStr);
  if (Number.isNaN(date.getTime())) return 'Invalid';
  
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  
  if (diffDays === 0) return 'Today';
  if (diffDays === 1) return 'Yesterday';
  if (diffDays < 7) return `${diffDays} days ago`;
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
  if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;
  return `${Math.floor(diffDays / 365)} years ago`;
}

/**
 * Get trust score badge variant
 */
function getTrustBadgeVariant(score: number): 'default' | 'secondary' | 'destructive' | 'outline' {
  if (score >= 70) return 'default';
  if (score >= 40) return 'secondary';
  return 'outline';
}

/**
 * Get trust score color class
 */
function getTrustScoreColor(score: number): string {
  if (score >= 70) return 'text-green-500';
  if (score >= 40) return 'text-yellow-500';
  return 'text-red-500';
}

/**
 * Format number with K suffix
 */
function formatNumber(value: number): string {
  if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
  if (value >= 1000) return `${(value / 1000).toFixed(1)}K`;
  return value.toString();
}

// ==================== Skeleton Components ====================

function TableSkeleton({ rows = 10 }: { rows?: number }) {
  return (
    <div className="animate-pulse" data-testid="table-skeleton">
      <div className="space-y-3">
        {Array.from({ length: rows }).map((_, i) => (
          <div key={i} className="flex items-center gap-4 py-4">
            <div className="h-4 w-48 bg-muted rounded" />
            <div className="h-4 w-16 bg-muted rounded" />
            <div className="h-4 w-20 bg-muted rounded" />
            <div className="h-4 w-24 bg-muted rounded" />
          </div>
        ))}
      </div>
    </div>
  );
}

// ==================== Empty State Component ====================

function EmptyState() {
  return (
    <div
      className="flex flex-col items-center justify-center py-16 text-muted-foreground"
      data-testid="empty-state"
    >
      <Building2 className="h-12 w-12 mb-4 opacity-50" />
      <p className="text-lg font-medium">No referring domains yet</p>
      <p className="text-sm mt-2">Run a backlink analysis to discover domains linking to your site</p>
    </div>
  );
}

// ==================== Sort Icon Component ====================

interface SortIconProps {
  field: DomainsSortField;
  currentField?: DomainsSortField;
  currentOrder?: SortOrder;
}

const SortIcon: React.FC<SortIconProps> = ({ field, currentField, currentOrder }) => {
  if (currentField !== field) {
    return <ChevronsUpDown className="h-4 w-4 text-muted-foreground/50" />;
  }
  
  return currentOrder === 'asc' ? (
    <ChevronUp className="h-4 w-4 text-primary" />
  ) : (
    <ChevronDown className="h-4 w-4 text-primary" />
  );
};

// ==================== Pagination Component ====================

interface PaginationProps {
  meta?: DomainsMeta;
  currentPage?: number;
  onPageChange?: (page: number) => void;
}

const Pagination: React.FC<PaginationProps> = ({
  meta,
  currentPage = 1,
  onPageChange,
}) => {
  if (!meta || meta.totalPages <= 1) return null;

  const pages = useMemo(() => {
    const items: (number | 'ellipsis')[] = [];
    const total = meta.totalPages;
    const current = currentPage;

    // Always show first page
    items.push(1);

    // Show pages around current
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);

    if (start > 2) items.push('ellipsis');
    
    for (let i = start; i <= end; i++) {
      items.push(i);
    }
    
    if (end < total - 1) items.push('ellipsis');

    // Always show last page
    if (total > 1) items.push(total);

    return items;
  }, [meta.totalPages, currentPage]);

  return (
    <div className="flex items-center justify-between px-2 py-4">
      <div className="text-sm text-muted-foreground">
        Showing {((currentPage - 1) * meta.perPage) + 1} to{' '}
        {Math.min(currentPage * meta.perPage, meta.total)} of {meta.total} results
      </div>
      
      <div className="flex items-center gap-1" role="navigation" aria-label="Pagination">
        <Button
          variant="outline"
          size="sm"
          onClick={() => onPageChange?.(currentPage - 1)}
          disabled={currentPage <= 1}
          aria-label="Previous page"
        >
          <ChevronLeft className="h-4 w-4" />
        </Button>
        
        {pages.map((page, i) =>
          page === 'ellipsis' ? (
            <span key={`ellipsis-${i}`} className="px-2 text-muted-foreground">
              ...
            </span>
          ) : (
            <Button
              key={page}
              variant={page === currentPage ? 'default' : 'outline'}
              size="sm"
              onClick={() => onPageChange?.(page)}
              aria-current={page === currentPage ? 'page' : undefined}
            >
              {page}
            </Button>
          )
        )}
        
        <Button
          variant="outline"
          size="sm"
          onClick={() => onPageChange?.(currentPage + 1)}
          disabled={currentPage >= meta.totalPages}
          aria-label="Next page"
        >
          <ChevronRight className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
};

// ==================== Main Component ====================

const ReferringDomainsTable: React.FC<ReferringDomainsTableProps> = ({
  projectId,
  domains = [],
  meta,
  loading = false,
  onSort,
  onPageChange,
  sortField: externalSortField,
  sortOrder: externalSortOrder,
  currentPage = 1,
  className,
}) => {
  // Internal sort state (for client-side sorting if no onSort provided)
  const [internalSort, setInternalSort] = useState<SortConfig>({
    field: 'backlinks_count',
    order: 'desc',
  });

  // Use external or internal sort state
  const sortField = externalSortField ?? internalSort.field;
  const sortOrder = externalSortOrder ?? internalSort.order;

  // Handle sort click
  const handleSort = (field: DomainsSortField) => {
    const newOrder: SortOrder = sortField === field && sortOrder === 'asc' ? 'desc' : 'asc';
    
    if (onSort) {
      onSort(field, newOrder);
    } else {
      setInternalSort({ field, order: newOrder });
    }
  };

  // Client-side sorted domains (if no onSort callback)
  const sortedDomains = useMemo(() => {
    if (onSort) return domains; // Server-side sorting

    return [...domains].sort((a, b) => {
      let aVal: string | number = a[sortField] ?? 0;
      let bVal: string | number = b[sortField] ?? 0;

      if (sortField === 'domain') {
        aVal = aVal.toString().toLowerCase();
        bVal = bVal.toString().toLowerCase();
      }

      if (aVal < bVal) return sortOrder === 'asc' ? -1 : 1;
      if (aVal > bVal) return sortOrder === 'asc' ? 1 : -1;
      return 0;
    });
  }, [domains, sortField, sortOrder, onSort]);

  // Loading state
  if (loading) {
    return (
      <Card className={className} data-testid="referring-domains-table-loading">
        <CardHeader>
          <CardTitle className="text-lg">Referring Domains</CardTitle>
        </CardHeader>
        <CardContent>
          <TableSkeleton />
        </CardContent>
      </Card>
    );
  }

  // Empty state
  if (!loading && sortedDomains.length === 0) {
    return (
      <Card className={className} data-testid="referring-domains-table-empty">
        <CardHeader>
          <CardTitle className="text-lg">Referring Domains</CardTitle>
        </CardHeader>
        <CardContent>
          <EmptyState />
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={className} data-testid="referring-domains-table">
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="text-lg">Referring Domains</CardTitle>
          {meta && (
            <Badge variant="secondary">
              {formatNumber(meta.total)} domains
            </Badge>
          )}
        </div>
      </CardHeader>
      <CardContent>
        <div className="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                {SORT_COLUMNS.map((col) => (
                  <TableHead
                    key={col.key}
                    className="cursor-pointer select-none hover:bg-muted/50"
                    onClick={() => handleSort(col.key)}
                    aria-sort={
                      sortField === col.key
                        ? sortOrder === 'asc'
                          ? 'ascending'
                          : 'descending'
                        : 'none'
                    }
                  >
                    <div className="flex items-center gap-2">
                      {col.label}
                      <SortIcon
                        field={col.key}
                        currentField={sortField}
                        currentOrder={sortOrder}
                      />
                    </div>
                  </TableHead>
                ))}
                <TableHead className="w-10" />
              </TableRow>
            </TableHeader>
            <TableBody>
              {sortedDomains.map((domain, index) => (
                <TableRow key={`${domain.domain}-${index}`}>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <div className="h-8 w-8 rounded-full bg-muted flex items-center justify-center">
                        <Building2 className="h-4 w-4 text-muted-foreground" />
                      </div>
                      <div>
                        <div className="font-medium">{domain.domain}</div>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <span className="font-medium">
                      {formatNumber(domain.backlinks_count)}
                    </span>
                  </TableCell>
                  <TableCell>
                    <Badge variant={getTrustBadgeVariant(domain.domain_trust_score)}>
                      <span className={getTrustScoreColor(domain.domain_trust_score)}>
                        {domain.domain_trust_score}
                      </span>
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-1 text-muted-foreground">
                      <Clock className="h-3 w-3" />
                      <span title={formatDate(domain.first_seen_at)}>
                        {formatRelativeTime(domain.first_seen_at)}
                      </span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Button
                      variant="ghost"
                      size="sm"
                      asChild
                      className="h-8 w-8 p-0"
                    >
                      <a
                        href={`https://${domain.domain}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label={`Open ${domain.domain} in new tab`}
                      >
                        <ExternalLink className="h-4 w-4" />
                      </a>
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>

        <Pagination
          meta={meta}
          currentPage={currentPage}
          onPageChange={onPageChange}
        />
      </CardContent>
    </Card>
  );
};

export default ReferringDomainsTable;

// Named export for flexibility
export { ReferringDomainsTable };
