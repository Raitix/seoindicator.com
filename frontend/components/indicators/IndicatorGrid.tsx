'use client';

import * as React from 'react';
import { cn } from '@/lib/utils';
import { IndicatorCard, type IndicatorStatus } from './IndicatorCard';
import { type LucideIconName } from './IndicatorIcon';
import { Skeleton } from '@/components/ui/skeleton';

// ============================================================================
// Types
// ============================================================================

/**
 * Data structure for an indicator card
 */
export interface IndicatorData {
  /** Unique identifier */
  id: string;
  /** Lucide icon name or custom React node */
  icon: LucideIconName | React.ReactNode;
  /** Indicator title */
  title: string;
  /** Current value */
  value: string | number;
  /** Description text */
  description: string;
  /** Status indicator */
  status: IndicatorStatus;
  /** Link destination */
  href: string;
  /** Optional tooltip override */
  tooltip?: string;
  /** Optional category for filtering */
  category?: string;
}

/**
 * Responsive column configuration
 */
export interface ColumnConfig {
  /** Columns on mobile (< 640px) */
  mobile?: number;
  /** Columns on sm (>= 640px) */
  sm?: number;
  /** Columns on md (>= 768px) */
  md?: number;
  /** Columns on lg (>= 1024px) */
  lg?: number;
  /** Columns on xl (>= 1280px) */
  xl?: number;
}

export interface IndicatorGridProps extends React.HTMLAttributes<HTMLDivElement> {
  /** Array of indicator data */
  indicators: IndicatorData[];
  /** Responsive column configuration */
  columns?: ColumnConfig;
  /** Gap spacing */
  gap?: 'sm' | 'md' | 'lg';
  /** Loading state */
  isLoading?: boolean;
  /** Custom aria-label */
  'aria-label'?: string;
}

// ============================================================================
// Constants
// ============================================================================

const DEFAULT_COLUMNS: Required<ColumnConfig> = {
  mobile: 1,
  sm: 2,
  md: 3,
  lg: 4,
  xl: 4,
};

const GAP_CLASSES: Record<'sm' | 'md' | 'lg', string> = {
  sm: 'gap-2',
  md: 'gap-4',
  lg: 'gap-6',
};

const COLUMN_CLASSES: Record<number, string> = {
  1: 'grid-cols-1',
  2: 'grid-cols-2',
  3: 'grid-cols-3',
  4: 'grid-cols-4',
  5: 'grid-cols-5',
  6: 'grid-cols-6',
};

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
// Utility Functions
// ============================================================================

/**
 * Get the Tailwind grid-cols class for a column count.
 * Falls back to grid-cols-1 for invalid values.
 */
function getColumnClass(columns: number | undefined): string {
  if (!columns || columns < 1) {
    return COLUMN_CLASSES[1];
  }
  return COLUMN_CLASSES[columns] ?? COLUMN_CLASSES[1];
}

/**
 * Merge user-provided columns with defaults
 */
function mergeColumns(columns: ColumnConfig | undefined): Required<ColumnConfig> {
  if (!columns) {
    return DEFAULT_COLUMNS;
  }
  return {
    mobile: columns.mobile ?? DEFAULT_COLUMNS.mobile,
    sm: columns.sm ?? DEFAULT_COLUMNS.sm,
    md: columns.md ?? DEFAULT_COLUMNS.md,
    lg: columns.lg ?? DEFAULT_COLUMNS.lg,
    xl: columns.xl ?? DEFAULT_COLUMNS.xl,
  };
}

// ============================================================================
// Sub-Components
// ============================================================================

interface GridSkeletonProps {
  count: number;
  columnClass: string;
  gapClass: string;
}

function GridSkeleton({ count, columnClass, gapClass }: GridSkeletonProps) {
  return (
    <>
      {Array.from({ length: count }).map((_, index) => (
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
    </>
  );
}

// ============================================================================
// Component
// ============================================================================

/**
 * IndicatorGrid - A responsive grid layout for displaying indicator cards
 *
 * Renders a CSS Grid-based layout with configurable responsive breakpoints,
 * gap spacing, and loading states. Supports variable numbers of indicators
 * with animated layout transitions.
 *
 * @example
 * ```tsx
 * // Basic usage
 * <IndicatorGrid indicators={indicatorData} />
 *
 * // With custom columns
 * <IndicatorGrid
 *   indicators={indicatorData}
 *   columns={{ mobile: 2, sm: 3, md: 4, lg: 5, xl: 6 }}
 * />
 *
 * // With loading state
 * <IndicatorGrid indicators={[]} isLoading />
 *
 * // With custom gap
 * <IndicatorGrid indicators={indicatorData} gap="lg" />
 * ```
 */
export const IndicatorGrid = React.forwardRef<HTMLDivElement, IndicatorGridProps>(
  (
    {
      indicators,
      columns: columnsProp,
      gap = 'md',
      isLoading = false,
      className,
      'aria-label': ariaLabel = 'SEO Indicators',
      ...props
    },
    ref
  ) => {
    // Merge columns with defaults
    const columns = mergeColumns(columnsProp);

    // Check for reduced motion preference
    const reducedMotion = usePrefersReducedMotion();

    // Build responsive grid classes
    const gridClasses = React.useMemo(() => {
      const baseClass = 'grid';
      const mobileClass = getColumnClass(columns.mobile);
      const smClass = columns.sm !== columns.mobile ? `sm:${getColumnClass(columns.sm)}` : '';
      const mdClass = columns.md !== columns.sm ? `md:${getColumnClass(columns.md)}` : '';
      const lgClass = columns.lg !== columns.md ? `lg:${getColumnClass(columns.lg)}` : '';
      const xlClass = columns.xl !== columns.lg ? `xl:${getColumnClass(columns.xl)}` : '';

      return cn(
        baseClass,
        mobileClass,
        smClass,
        mdClass,
        lgClass,
        xlClass,
        GAP_CLASSES[gap],
        !reducedMotion && 'transition-all duration-200 ease-out'
      );
    }, [columns, gap, reducedMotion]);

    // Skeleton count (show 8 skeletons during loading)
    const skeletonCount = 8;

    return (
      <div
        ref={ref}
        data-testid="indicator-grid"
        data-slot="indicator-grid"
        data-loading={isLoading}
        data-indicator-count={isLoading ? 0 : indicators.length}
        className={cn(gridClasses, className)}
        role="grid"
        aria-label={ariaLabel}
        aria-rowcount={isLoading ? 0 : Math.ceil(indicators.length / columns.lg)}
        aria-busy={isLoading}
        {...props}
      >
        {isLoading ? (
          <GridSkeleton
            count={skeletonCount}
            columnClass={gridClasses}
            gapClass={GAP_CLASSES[gap]}
          />
        ) : (
          indicators.map((indicator) => (
            <div
              key={indicator.id}
              role="gridcell"
              className="contents"
            >
              <IndicatorCard
                id={indicator.id}
                icon={indicator.icon}
                title={indicator.title}
                value={indicator.value}
                description={indicator.description}
                status={indicator.status}
                href={indicator.href}
                tooltip={indicator.tooltip}
                isLoading={false}
              />
            </div>
          ))
        )}
      </div>
    );
  }
);

IndicatorGrid.displayName = 'IndicatorGrid';

// ============================================================================
// Exports
// ============================================================================

export default IndicatorGrid;
