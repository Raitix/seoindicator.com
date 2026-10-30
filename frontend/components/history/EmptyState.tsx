"use client";

import * as React from "react";
import { FileQuestion, Search, Play } from "lucide-react";

import { Button } from "@/components/ui/button";

import type { EmptyStateProps } from "@/lib/types/analysis";

// ==================== Empty State Component ====================

export function EmptyState({ hasFilters, onClearFilters }: EmptyStateProps) {
  if (hasFilters) {
    // No results from filters
    return (
      <div
        className="flex flex-col items-center justify-center py-16 px-4"
        data-testid="empty-state-filtered"
      >
        <div className="h-16 w-16 rounded-full bg-slate-800 flex items-center justify-center mb-4">
          <Search className="h-8 w-8 text-slate-500" />
        </div>
        <h3 className="text-lg font-medium text-white mb-2">
          No analyses match your filters
        </h3>
        <p className="text-sm text-slate-400 text-center max-w-md mb-6">
          Try adjusting your filter criteria or clear all filters to see all analyses.
        </p>
        {onClearFilters && (
          <Button
            variant="outline"
            onClick={onClearFilters}
            className="border-slate-700 text-slate-200"
            data-testid="clear-filters-empty-button"
          >
            Clear Filters
          </Button>
        )}
      </div>
    );
  }

  // No analyses at all - first time user
  return (
    <div
      className="flex flex-col items-center justify-center py-16 px-4"
      data-testid="empty-state-no-analyses"
    >
      <div className="h-16 w-16 rounded-full bg-slate-800 flex items-center justify-center mb-4">
        <FileQuestion className="h-8 w-8 text-slate-500" />
      </div>
      <h3 className="text-lg font-medium text-white mb-2">
        No analyses yet
      </h3>
      <p className="text-sm text-slate-400 text-center max-w-md mb-6">
        You haven&apos;t run any SEO analyses yet. Start by analyzing a URL to see your first results here.
      </p>
      <Button
        className="bg-blue-600 hover:bg-blue-700 text-white"
        data-testid="run-first-analysis-button"
        onClick={() => {
          // Navigate to tools page or open analysis modal
          window.location.href = "/tools";
        }}
      >
        <Play className="h-4 w-4 mr-2" />
        Run Your First Analysis
      </Button>
    </div>
  );
}

export default EmptyState;
