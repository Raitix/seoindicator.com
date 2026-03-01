"use client";

import * as React from "react";
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from "lucide-react";

import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

import type { PaginationProps } from "@/lib/types/analysis";

// ==================== Pagination Component ====================

export function Pagination({ meta, onPageChange, onPerPageChange }: PaginationProps) {
  const { total, page, perPage, lastPage, from, to } = meta;

  // Don't show pagination for single page
  if (total <= perPage) {
    return null;
  }

  // Generate page numbers to show
  const getPageNumbers = (): (number | "...")[] => {
    const pages: (number | "...")[] = [];
    const showPages = 5; // Max pages to show in the middle

    if (lastPage <= 7) {
      // Show all pages if total is small
      for (let i = 1; i <= lastPage; i++) {
        pages.push(i);
      }
    } else {
      // Always show first page
      pages.push(1);

      if (page > 3) {
        pages.push("...");
      }

      // Show pages around current
      const start = Math.max(2, page - 1);
      const end = Math.min(lastPage - 1, page + 1);

      for (let i = start; i <= end; i++) {
        if (!pages.includes(i)) {
          pages.push(i);
        }
      }

      if (page < lastPage - 2) {
        pages.push("...");
      }

      // Always show last page
      if (!pages.includes(lastPage)) {
        pages.push(lastPage);
      }
    }

    return pages;
  };

  const pageNumbers = getPageNumbers();

  return (
    <div
      className="flex flex-wrap items-center justify-between gap-4 py-4"
      data-testid="pagination-controls"
    >
      {/* Showing X-Y of Z */}
      <div className="text-sm text-slate-400" data-testid="pagination-info">
        Showing <span className="text-slate-200">{from}</span>-
        <span className="text-slate-200">{to}</span> of{" "}
        <span className="text-slate-200">{total}</span>
      </div>

      {/* Page Controls */}
      <div className="flex items-center gap-2">
        {/* First Page */}
        <Button
          variant="outline"
          size="sm"
          className="h-8 w-8 p-0 bg-slate-900 border-slate-700 text-slate-400 hover:text-slate-200 disabled:opacity-50"
          onClick={() => onPageChange(1)}
          disabled={page === 1}
          data-testid="first-page-button"
          aria-label="First page"
        >
          <ChevronsLeft className="h-4 w-4" />
        </Button>

        {/* Previous Page */}
        <Button
          variant="outline"
          size="sm"
          className="h-8 w-8 p-0 bg-slate-900 border-slate-700 text-slate-400 hover:text-slate-200 disabled:opacity-50"
          onClick={() => onPageChange(page - 1)}
          disabled={page === 1}
          data-testid="previous-page-button"
          aria-label="Previous page"
        >
          <ChevronLeft className="h-4 w-4" />
        </Button>

        {/* Page Numbers */}
        <div className="flex items-center gap-1" data-testid="page-numbers">
          {pageNumbers.map((pageNum, index) =>
            pageNum === "..." ? (
              <span
                key={`ellipsis-${index}`}
                className="px-2 text-slate-500"
              >
                ...
              </span>
            ) : (
              <Button
                key={pageNum}
                variant={page === pageNum ? "default" : "outline"}
                size="sm"
                className={cn(
                  "h-8 w-8 p-0",
                  page === pageNum
                    ? "bg-blue-600 text-white hover:bg-blue-700"
                    : "bg-slate-900 border-slate-700 text-slate-400 hover:text-slate-200"
                )}
                onClick={() => onPageChange(pageNum)}
                data-testid={`page-button-${pageNum}`}
                aria-label={`Page ${pageNum}`}
                aria-current={page === pageNum ? "page" : undefined}
              >
                {pageNum}
              </Button>
            )
          )}
        </div>

        {/* Next Page */}
        <Button
          variant="outline"
          size="sm"
          className="h-8 w-8 p-0 bg-slate-900 border-slate-700 text-slate-400 hover:text-slate-200 disabled:opacity-50"
          onClick={() => onPageChange(page + 1)}
          disabled={page === lastPage}
          data-testid="next-page-button"
          aria-label="Next page"
        >
          <ChevronRight className="h-4 w-4" />
        </Button>

        {/* Last Page */}
        <Button
          variant="outline"
          size="sm"
          className="h-8 w-8 p-0 bg-slate-900 border-slate-700 text-slate-400 hover:text-slate-200 disabled:opacity-50"
          onClick={() => onPageChange(lastPage)}
          disabled={page === lastPage}
          data-testid="last-page-button"
          aria-label="Last page"
        >
          <ChevronsRight className="h-4 w-4" />
        </Button>

        {/* Per Page Selector */}
        {onPerPageChange && (
          <div className="ml-4 flex items-center gap-2">
            <span className="text-sm text-slate-400">Per page:</span>
            <Select
              value={perPage.toString()}
              onValueChange={(value) => onPerPageChange(parseInt(value, 10))}
            >
              <SelectTrigger
                className="h-8 w-16 bg-slate-900 border-slate-700 text-slate-200"
                data-testid="per-page-select"
              >
                <SelectValue />
              </SelectTrigger>
              <SelectContent className="bg-slate-900 border-slate-700">
                {[15, 25, 50, 100].map((size) => (
                  <SelectItem
                    key={size}
                    value={size.toString()}
                    className="text-slate-200 focus:bg-slate-800"
                  >
                    {size}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        )}
      </div>
    </div>
  );
}

export default Pagination;
