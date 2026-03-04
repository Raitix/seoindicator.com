"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { IndicatorGrid, type IndicatorData } from "./IndicatorGrid";
import { Button } from "@/components/ui/button";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { Separator } from "@/components/ui/separator";
import { Skeleton } from "@/components/ui/skeleton";
import { RefreshCw, AlertCircle, Inbox } from "lucide-react";

// ============================================
// Types
// ============================================

export type { IndicatorData } from "./IndicatorGrid";

export interface IndicatorCatalogProps {
  /** Array of indicator data to display */
  indicators: IndicatorData[];
  /** Catalog title */
  title?: string;
  /** Catalog description */
  description?: string;
  /** Available categories for filtering */
  categories?: string[];
  /** Currently selected category */
  selectedCategory?: string;
  /** Callback when category changes */
  onCategoryChange?: (category: string) => void;
  /** Callback when refresh button is clicked */
  onRefresh?: () => void;
  /** Show loading state */
  isLoading?: boolean;
  /** Show error state */
  error?: Error | null;
  /** Additional CSS classes */
  className?: string;
  /** Show category tabs */
  showCategoryTabs?: boolean;
  /** Show refresh button */
  showRefreshButton?: boolean;
  /** Empty state message */
  emptyMessage?: string;
  /** Callback when indicator is clicked */
  onIndicatorClick?: (indicator: IndicatorData) => void;
}

// ============================================
// Skeleton Grid Component
// ============================================

function SkeletonGrid() {
  return (
    <div
      className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
      aria-hidden="true"
      data-testid="catalog-skeleton-grid"
    >
      {Array.from({ length: 8 }).map((_, i) => (
        <div
          key={`skeleton-${i}`}
          className="rounded-lg border border-slate-700/50 p-4 bg-slate-900/50"
        >
          <div className="flex items-start justify-between gap-2 mb-3">
            <div className="flex items-center gap-2">
              <Skeleton className="w-5 h-5 rounded" />
              <Skeleton className="h-4 w-24" />
            </div>
            <Skeleton className="w-4 h-4 rounded" />
          </div>
          <div className="flex items-end justify-between">
            <div>
              <Skeleton className="h-7 w-16 mb-1" />
              <Skeleton className="h-3 w-32" />
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}

// ============================================
// Empty State Component
// ============================================

function EmptyState({
  message = "No indicators available",
  category,
}: {
  message?: string;
  category?: string;
}) {
  const displayMessage =
    category && category !== "All"
      ? `No indicators in "${category}" category`
      : message;

  return (
    <div
      role="status"
      className="flex flex-col items-center justify-center py-12 text-center"
      data-testid="catalog-empty"
    >
      <div className="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center mb-4">
        <Inbox className="w-6 h-6 text-slate-500" aria-hidden="true" />
      </div>
      <p className="text-sm text-slate-400">{displayMessage}</p>
    </div>
  );
}

// ============================================
// Error State Component
// ============================================

function ErrorState({
  error,
  onRetry,
}: {
  error: Error;
  onRetry?: () => void;
}) {
  return (
    <div
      role="alert"
      className="flex flex-col items-center justify-center py-12 text-center"
      data-testid="catalog-error"
    >
      <div className="w-12 h-12 rounded-full bg-red-900/50 flex items-center justify-center mb-4">
        <AlertCircle className="w-6 h-6 text-red-400" aria-hidden="true" />
      </div>
      <p className="text-sm text-red-400 mb-4">{error.message}</p>
      {onRetry && (
        <Button
          onClick={onRetry}
          variant="outline"
          size="sm"
          className="border-red-800 text-red-300 hover:bg-red-900/50"
        >
          <RefreshCw className="w-4 h-4 mr-2" />
          Retry
        </Button>
      )}
    </div>
  );
}

// ============================================
// Component
// ============================================

/**
 * IndicatorCatalog Component
 *
 * Main container component for displaying SEO indicators with filtering,
 * grouping, and refresh capabilities. Fully accessible with ARIA labels,
 * keyboard navigation, and screen reader support.
 *
 * @example
 * ```tsx
 * <IndicatorCatalog
 *   title="SEO Indicators"
 *   description="Track your website's SEO performance"
 *   indicators={indicators}
 *   categories={['All', 'Rankings', 'Traffic', 'Technical']}
 *   selectedCategory="All"
 *   onCategoryChange={(cat) => setSelectedCategory(cat)}
 *   onRefresh={() => refetch()}
 *   isLoading={loading}
 *   error={error}
 * />
 * ```
 */
export function IndicatorCatalog({
  indicators = [],
  title = "SEO Indicators",
  description,
  categories = [],
  selectedCategory = "All",
  onCategoryChange,
  onRefresh,
  isLoading = false,
  error = null,
  className,
  showCategoryTabs = true,
  showRefreshButton = true,
  emptyMessage = "No indicators available",
  onIndicatorClick,
}: IndicatorCatalogProps) {
  // Generate unique IDs for accessibility
  const catalogId = React.useId();
  const titleId = `${catalogId}-title`;
  const descriptionId = `${catalogId}-description`;

  // Filter indicators by selected category
  const filteredIndicators = React.useMemo(() => {
    if (!categories.length || selectedCategory === "All") {
      return indicators;
    }
    return indicators.filter(
      (indicator) => indicator.category === selectedCategory
    );
  }, [indicators, categories, selectedCategory]);

  // Handle tab keyboard navigation
  const handleTabKeyDown = (e: React.KeyboardEvent) => {
    if (!categories.length) return;

    const currentIndex = categories.indexOf(selectedCategory);

    if (e.key === "ArrowRight" || e.key === "ArrowDown") {
      e.preventDefault();
      const nextIndex = (currentIndex + 1) % categories.length;
      onCategoryChange?.(categories[nextIndex]);
    } else if (e.key === "ArrowLeft" || e.key === "ArrowUp") {
      e.preventDefault();
      const prevIndex =
        (currentIndex - 1 + categories.length) % categories.length;
      onCategoryChange?.(categories[prevIndex]);
    } else if (e.key === "Home") {
      e.preventDefault();
      onCategoryChange?.(categories[0]);
    } else if (e.key === "End") {
      e.preventDefault();
      onCategoryChange?.(categories[categories.length - 1]);
    }
  };

  return (
    <section
      aria-labelledby={title ? titleId : undefined}
      aria-describedby={description ? descriptionId : undefined}
      aria-busy={isLoading ? "true" : undefined}
      className={cn("w-full", className)}
      data-testid="indicator-catalog"
      data-loading={isLoading ? "true" : "false"}
      data-error={error ? "true" : "false"}
      data-empty={
        !isLoading && !error && filteredIndicators.length === 0 ? "true" : "false"
      }
    >
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          {title && (
            <h2
              id={titleId}
              className="text-xl font-semibold text-white"
              data-testid="catalog-title"
            >
              {title}
            </h2>
          )}
          {description && (
            <p
              id={descriptionId}
              className="text-sm text-slate-400 mt-1"
              data-testid="catalog-description"
            >
              {description}
            </p>
          )}
        </div>

        {/* Refresh Button */}
        {showRefreshButton && onRefresh && (
          <Button
            onClick={onRefresh}
            variant="outline"
            size="sm"
            disabled={isLoading}
            aria-label="Refresh indicators"
            className="border-slate-700 text-slate-300 hover:bg-slate-800"
            data-testid="catalog-refresh-button"
          >
            <RefreshCw
              className={cn("w-4 h-4 mr-2", isLoading && "animate-spin")}
              aria-hidden="true"
            />
            Refresh
          </Button>
        )}
      </div>

      {/* Category Tabs */}
      {showCategoryTabs && categories.length > 0 && (
        <div className="mb-6" onKeyDown={handleTabKeyDown}>
          <Tabs
            value={selectedCategory}
            onValueChange={onCategoryChange}
            aria-label="Filter indicators by category"
          >
            <TabsList
              role="tablist"
              aria-label="Indicator categories"
              className="bg-slate-900/50 border border-slate-800"
            >
              {categories.map((category) => (
                <TabsTrigger
                  key={category}
                  value={category}
                  role="tab"
                  aria-selected={selectedCategory === category}
                  className="data-[state=active]:bg-blue-600 data-[state=active]:text-white"
                  data-testid={`tab-${category.toLowerCase().replace(/\s+/g, "-")}`}
                >
                  {category}
                </TabsTrigger>
              ))}
            </TabsList>
          </Tabs>
        </div>
      )}

      {/* Separator */}
      {(title || (showCategoryTabs && categories.length > 0)) && (
        <Separator className="mb-6 bg-slate-800" />
      )}

      {/* Content */}
      <div role="region" aria-label="Indicators list">
        {/* Loading State */}
        {isLoading && <SkeletonGrid />}

        {/* Error State */}
        {!isLoading && error && <ErrorState error={error} onRetry={onRefresh} />}

        {/* Empty State */}
        {!isLoading && !error && filteredIndicators.length === 0 && (
          <EmptyState message={emptyMessage} category={selectedCategory} />
        )}

        {/* Indicators Grid */}
        {!isLoading && !error && filteredIndicators.length > 0 && (
          <div data-testid="catalog-grid">
            <IndicatorGrid
              indicators={filteredIndicators}
              onIndicatorClick={onIndicatorClick}
              aria-label={`${selectedCategory === "All" ? "All" : selectedCategory} indicators`}
              showCategories={categories.length > 0}
            />
          </div>
        )}
      </div>
    </section>
  );
}

export default IndicatorCatalog;
