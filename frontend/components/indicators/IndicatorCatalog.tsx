'use client';

import * as React from 'react';
import { RefreshCw, AlertCircle, FolderOpen } from 'lucide-react';
import { cn } from '@/lib/utils';
import { IndicatorGrid, type IndicatorData } from './IndicatorGrid';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';

// ============================================================================
// Types
// ============================================================================

/**
 * Props for the IndicatorCatalog component
 */
export interface IndicatorCatalogProps extends Omit<React.HTMLAttributes<HTMLElement>, 'title'> {
  /** Array of indicator data */
  indicators: IndicatorData[];
  /** Section title (default: "SEO Indicators") */
  title?: string;
  /** Section description */
  description?: string;
  /** Optional categories for filtering */
  categories?: string[];
  /** Currently selected category */
  selectedCategory?: string;
  /** Category change callback */
  onCategoryChange?: (category: string) => void;
  /** Refresh callback */
  onRefresh?: () => void;
  /** Loading state */
  isLoading?: boolean;
  /** Error state */
  error?: Error | null;
  /** Custom empty state message */
  emptyMessage?: string;
}

// ============================================================================
// Constants
// ============================================================================

const DEFAULT_TITLE = 'SEO Indicators';
const SKELETON_COUNT = 8;

// ============================================================================
// Hooks
// ============================================================================

/**
 * Hook to check if the user prefers reduced motion.
 * Returns false during SSR to avoid hydration mismatches.
 */
function usePrefersReducedMotion(): boolean {
  const [prefersReducedMotion, setPrefersReducedMotion] = React.useState(false);

  React.useEffect(() => {
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    setPrefersReducedMotion(mediaQuery.matches);

    const handler = (event: MediaQueryListEvent) => {
      setPrefersReducedMotion(event.matches);
    };

    mediaQuery.addEventListener('change', handler);
    return () => mediaQuery.removeEventListener('change', handler);
  }, []);

  return prefersReducedMotion;
}

// ============================================================================
// Sub-Components
// ============================================================================

interface CatalogSkeletonProps {
  showTitle?: boolean;
  showDescription?: boolean;
}

/**
 * Skeleton grid for loading state
 */
function CatalogSkeleton({ showTitle = true, showDescription = true }: CatalogSkeletonProps) {
  return (
    <div data-testid="catalog-skeleton-grid" className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
      {showTitle && <Skeleton className="col-span-full h-8 w-48" />}
      {showDescription && <Skeleton className="col-span-full h-4 w-64" />}
      {Array.from({ length: SKELETON_COUNT }).map((_, index) => (
        <div
          key={`skeleton-${index}`}
          className="flex flex-col gap-3 rounded-xl border bg-card p-4"
          role="presentation"
        >
          <div className="flex items-start justify-between gap-2">
            <Skeleton className="size-10 rounded-lg" />
            <Skeleton className="h-5 w-16 rounded-full" />
          </div>
          <div className="flex flex-col gap-1.5">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-8 w-16" />
          </div>
          <Skeleton className="h-4 w-full" />
        </div>
      ))}
    </div>
  );
}

interface CatalogErrorProps {
  error: Error;
  onRetry?: () => void;
}

/**
 * Error state with retry button
 */
function CatalogError({ error, onRetry }: CatalogErrorProps) {
  return (
    <div
      data-testid="catalog-error"
      role="alert"
      className="flex flex-col items-center justify-center gap-4 rounded-lg border border-destructive/50 bg-destructive/10 p-8 text-center"
    >
      <AlertCircle className="size-12 text-destructive" aria-hidden="true" />
      <div className="flex flex-col gap-2">
        <h3 className="text-lg font-semibold text-destructive">Failed to load indicators</h3>
        <p className="text-sm text-muted-foreground">{error.message}</p>
      </div>
      {onRetry && (
        <Button
          variant="outline"
          onClick={onRetry}
          className="mt-2"
        >
          Retry
        </Button>
      )}
    </div>
  );
}

interface CatalogEmptyProps {
  category?: string;
  message?: string;
}

/**
 * Empty state with helpful message
 */
function CatalogEmpty({ category, message }: CatalogEmptyProps) {
  const defaultMessage = category
    ? `No indicators found in the "${category}" category`
    : 'No indicators available';

  return (
    <div
      data-testid="catalog-empty"
      role="status"
      className="flex flex-col items-center justify-center gap-4 rounded-lg border border-dashed bg-muted/50 p-8 text-center"
    >
      <FolderOpen className="size-12 text-muted-foreground" aria-hidden="true" />
      <div className="flex flex-col gap-2">
        <h3 className="text-lg font-semibold">No indicators</h3>
        <p className="text-sm text-muted-foreground">{message || defaultMessage}</p>
      </div>
    </div>
  );
}

// ============================================================================
// Utility Functions
// ============================================================================

/**
 * Filter indicators by category
 */
function filterIndicators(
  indicators: IndicatorData[],
  selectedCategory: string | undefined
): IndicatorData[] {
  if (!selectedCategory || selectedCategory === 'All') {
    return indicators;
  }
  return indicators.filter((indicator) => indicator.category === selectedCategory);
}

/**
 * Generate a unique ID for the title element
 */
function generateTitleId(): string {
  return `catalog-title-${Math.random().toString(36).substring(2, 9)}`;
}

// ============================================================================
// Component
// ============================================================================

/**
 * IndicatorCatalog - Main container for displaying SEO indicators
 *
 * Orchestrates the display of all indicators with optional filtering,
 * grouping, and refresh capabilities. Implements state priority:
 * Loading > Error > Empty > Content
 *
 * @example
 * ```tsx
 * // Basic usage
 * <IndicatorCatalog indicators={indicatorData} />
 *
 * // With categories and filtering
 * <IndicatorCatalog
 *   indicators={indicatorData}
 *   categories={['All', 'rankings', 'technical']}
 *   selectedCategory="rankings"
 *   onCategoryChange={setCategory}
 * />
 *
 * // With loading state
 * <IndicatorCatalog indicators={[]} isLoading />
 *
 * // With error state
 * <IndicatorCatalog indicators={[]} error={new Error('Failed to load')} onRefresh={refetch} />
 *
 * // With refresh capability
 * <IndicatorCatalog indicators={indicatorData} onRefresh={refetch} />
 * ```
 */
export const IndicatorCatalog = React.forwardRef<HTMLElement, IndicatorCatalogProps>(
  (
    {
      indicators,
      title = DEFAULT_TITLE,
      description,
      categories,
      selectedCategory: selectedCategoryProp,
      onCategoryChange,
      onRefresh,
      isLoading = false,
      error = null,
      emptyMessage,
      className,
      ...props
    },
    ref
  ) => {
    // Generate stable title ID for aria-labelledby
    const [titleId] = React.useState(generateTitleId);

    // Internal category state if not controlled
    const [internalCategory, setInternalCategory] = React.useState('All');
    const selectedCategory = selectedCategoryProp ?? internalCategory;

    // Check for reduced motion preference
    const reducedMotion = usePrefersReducedMotion();

    // Handle category change
    const handleCategoryChange = React.useCallback(
      (value: string) => {
        if (onCategoryChange) {
          onCategoryChange(value);
        } else {
          setInternalCategory(value);
        }
      },
      [onCategoryChange]
    );

    // Filter indicators by selected category
    const filteredIndicators = React.useMemo(
      () => filterIndicators(indicators, selectedCategory),
      [indicators, selectedCategory]
    );

    // Determine if we should show tabs
    const showTabs = categories && categories.length > 0;

    // Determine state to render (priority: Loading > Error > Empty > Content)
    const renderContent = () => {
      // Loading state
      if (isLoading) {
        return <CatalogSkeleton showTitle={!!title} showDescription={!!description} />;
      }

      // Error state
      if (error) {
        return <CatalogError error={error} onRetry={onRefresh} />;
      }

      // Empty state
      if (filteredIndicators.length === 0) {
        return <CatalogEmpty category={selectedCategory} message={emptyMessage} />;
      }

      // Content state - render grid
      return (
        <IndicatorGrid
          indicators={filteredIndicators}
          aria-label={`${title} - ${selectedCategory === 'All' ? 'All categories' : selectedCategory}`}
        />
      );
    };

    // Compute data attributes
    const dataAttributes = {
      'data-loading': isLoading || undefined,
      'data-error': error ? 'true' : undefined,
      'data-empty': !isLoading && !error && filteredIndicators.length === 0 ? 'true' : undefined,
    };

    return (
      <section
        ref={ref as React.Ref<HTMLElement>}
        data-testid="indicator-catalog"
        data-slot="indicator-catalog"
        className={cn(
          'flex w-full flex-col gap-4',
          'px-4 py-4 sm:px-6 sm:py-5 md:px-8 md:py-6',
          !reducedMotion && 'transition-all duration-200 ease-out',
          className
        )}
        aria-labelledby={title ? titleId : undefined}
        {...dataAttributes}
        {...props}
      >
        {/* Header Section */}
        {(title || description || onRefresh) && (
          <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex flex-col gap-1">
              {title && (
                <h2
                  id={titleId}
                  data-testid="catalog-title"
                  className="text-xl font-semibold tracking-tight sm:text-2xl"
                >
                  {title}
                </h2>
              )}
              {description && (
                <p
                  data-testid="catalog-description"
                  className="text-sm text-muted-foreground"
                >
                  {description}
                </p>
              )}
            </div>
            {onRefresh && (
              <Button
                data-testid="catalog-refresh-button"
                variant="outline"
                size="icon"
                onClick={onRefresh}
                disabled={isLoading}
                aria-label="Refresh indicators"
                className="self-start sm:self-auto"
              >
                <RefreshCw
                  className={cn(
                    'size-4',
                    isLoading && !reducedMotion && 'animate-spin'
                  )}
                />
              </Button>
            )}
          </div>
        )}

        {/* Category Tabs */}
        {showTabs && (
          <>
            <Tabs
              value={selectedCategory}
              onValueChange={handleCategoryChange}
              aria-label="Filter by category"
            >
              <TabsList role="tablist" className="w-full justify-start overflow-x-auto">
                {categories.map((category) => (
                  <TabsTrigger
                    key={category}
                    value={category}
                    data-testid={`tab-${category.toLowerCase().replace(/\s+/g, '-')}`}
                    role="tab"
                  >
                    {category}
                  </TabsTrigger>
                ))}
              </TabsList>
              <TabsContent value={selectedCategory} className="mt-0">
                {/* Content rendered below tabs */}
              </TabsContent>
            </Tabs>
            <Separator className="my-2" />
          </>
        )}

        {/* Main Content */}
        {renderContent()}
      </section>
    );
  }
);

IndicatorCatalog.displayName = 'IndicatorCatalog';

// ============================================================================
// Exports
// ============================================================================

export default IndicatorCatalog;
