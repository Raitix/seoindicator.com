'use client';

import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import { Skeleton } from '@/components/ui/skeleton';
import { IndicatorCard, type IndicatorStatus } from './IndicatorCard';
import type { LucideIconName } from './IndicatorIcon';

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
// Types
// ============================================================================

/**
 * Data structure for a single indicator displayed in the grid.
 */
export interface IndicatorData {
  /** Unique identifier for the indicator */
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
}

/**
 * Responsive column configuration for different breakpoints.
 */
export interface ColumnConfig {
  /** Columns on mobile (< 640px). Default: 1 */
  mobile?: number;
  /** Columns on sm breakpoint (>= 640px). Default: 2 */
  sm?: number;
  /** Columns on md breakpoint (>= 768px). Default: 3 */
  md?: number;
  /** Columns on lg breakpoint (>= 1024px). Default: 4 */
  lg?: number;
  /** Columns on xl breakpoint (>= 1280px). Default: 4 */
  xl?: number;
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

const SKELETON_COUNT = 8;

// ============================================================================
// Variants
// ============================================================================

const gapVariants = {
  sm: 'gap-2',
  md: 'gap-4',
  lg: 'gap-6',
};

const gridColClasses: Record<number, string> = {
  1: 'grid-cols-1',
  2: 'grid-cols-2',
  3: 'grid-cols-3',
  4: 'grid-cols-4',
  5: 'grid-cols-5',
  6: 'grid-cols-6',
};

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Validates and returns a safe column count.
 * Invalid values default to 1.
 */
function safeColumnCount(count: number | undefined): number {
  if (count === undefined || count === null || count < 1 || count > 6 || !Number.isInteger(count)) {
    return 1;
  }
  return count;
}

/**
 * Generates responsive grid column classes based on column configuration.
 */
function getGridColumnClasses(columns: Required<ColumnConfig>): string {
  const mobileClass = gridColClasses[safeColumnCount(columns.mobile)] || 'grid-cols-1';
  const smClass = gridColClasses[safeColumnCount(columns.sm)] || 'grid-cols-2';
  const mdClass = gridColClasses[safeColumnCount(columns.md)] || 'grid-cols-3';
  const lgClass = gridColClasses[safeColumnCount(columns.lg)] || 'grid-cols-4';
  const xlClass = gridColClasses[safeColumnCount(columns.xl)] || 'grid-cols-4';

  return cn(
    mobileClass,
    `sm:${smClass}`,
    `md:${mdClass}`,
    `lg:${lgClass}`,
    `xl:${xlClass}`
  );
}

// ============================================================================
// Sub-Components
// ============================================================================

interface GridSkeletonProps {
  columns: Required<ColumnConfig>;
  gap: 'sm' | 'md' | 'lg';
  className?: string;
}

/**
 * Skeleton loader for the indicator grid during loading state.
 */
function GridSkeleton({ columns, gap, className }: GridSkeletonProps) {
  const gridClasses = cn(
    'grid',
    getGridColumnClasses(columns),
    gapVariants[gap],
    className
  );

  return (
    <div
      className={gridClasses}
      data-testid="indicator-grid-skeleton"
      aria-hidden="true"
    >
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

// ============================================================================
// Main Component
// ============================================================================

export interface IndicatorGridProps
  extends Omit<React.HTMLAttributes<HTMLDivElement>, 'role'>,
    VariantProps<typeof cva> {
  /** Array of indicator data to display */
  indicators: IndicatorData[];
  /** Responsive column configuration */
  columns?: ColumnConfig;
  /** Gap spacing between grid items */
  gap?: 'sm' | 'md' | 'lg';
  /** Loading state - shows skeleton grid */
  isLoading?: boolean;
  /** Additional CSS classes */
  className?: string;
  /** Custom aria-label for the grid */
  'aria-label'?: string;
}

/**
 * IndicatorGrid - A responsive grid layout for displaying SEO indicator cards.
 *
 * Features:
 * - CSS Grid with responsive breakpoints (mobile/sm/md/lg/xl)
 * - Configurable gap spacing (sm/md/lg)
 * - Animated layout transitions
 * - Loading state with skeleton placeholders
 * - Full accessibility support (ARIA labels, keyboard navigation)
 * - Respects prefers-reduced-motion for users who prefer less animation
 *
 * @example
 * ```tsx
 * // Basic usage with default columns
 * <IndicatorGrid indicators={myIndicators} />
 *
 * // With custom columns
 * <IndicatorGrid
 *   indicators={myIndicators}
 *   columns={{ mobile: 2, sm: 3, md: 4, lg: 5, xl: 6 }}
 * />
 *
 * // With loading state
 * <IndicatorGrid indicators={[]} isLoading />
 *
 * // With custom gap and aria-label
 * <IndicatorGrid
 *   indicators={myIndicators}
 *   gap="lg"
 *   aria-label="SEO Performance Indicators"
 * />
 * ```
 */
export const IndicatorGrid = React.forwardRef<HTMLDivElement, IndicatorGridProps>(
  (
    {
      indicators,
      columns: customColumns,
      gap = 'md',
      isLoading = false,
      className,
      'aria-label': ariaLabel = 'SEO Indicators',
      ...props
    },
    ref
  ) => {
    // Merge custom columns with defaults
    const columns: Required<ColumnConfig> = {
      mobile: customColumns?.mobile ?? DEFAULT_COLUMNS.mobile,
      sm: customColumns?.sm ?? DEFAULT_COLUMNS.sm,
      md: customColumns?.md ?? DEFAULT_COLUMNS.md,
      lg: customColumns?.lg ?? DEFAULT_COLUMNS.lg,
      xl: customColumns?.xl ?? DEFAULT_COLUMNS.xl,
    };

    // Check for reduced motion preference
    const prefersReducedMotion = usePrefersReducedMotion();

    // Build grid classes
    const gridClasses = cn(
      'grid',
      getGridColumnClasses(columns),
      gapVariants[gap],
      !prefersReducedMotion && 'transition-all duration-200 ease-out',
      className
    );

    // Loading state - render skeleton
    if (isLoading) {
      return (
        <div
          ref={ref}
          data-testid="indicator-grid"
          data-slot="indicator-grid"
          data-loading="true"
          role="grid"
          aria-label={ariaLabel}
          aria-busy="true"
          aria-rowcount={SKELETON_COUNT.toString()}
          className={gridClasses}
          {...props}
        >
          {Array.from({ length: SKELETON_COUNT }).map((_, index) => (
            <div
              key={`skeleton-${index}`}
              className="flex flex-col gap-3 rounded-xl border bg-card p-4"
              role="gridcell"
              aria-hidden="true"
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

    // Empty state - render empty grid with message
    if (!indicators || indicators.length === 0) {
      return (
        <div
          ref={ref}
          data-testid="indicator-grid"
          data-slot="indicator-grid"
          data-loading="false"
          role="grid"
          aria-label={ariaLabel}
          aria-rowcount="0"
          className={cn(gridClasses, 'min-h-[200px] items-center justify-center')}
          {...props}
        >
          <div className="col-span-full flex flex-col items-center justify-center gap-2 py-8 text-center">
            <p className="text-muted-foreground">No indicators available</p>
          </div>
        </div>
      );
    }

    // Render indicator grid
    return (
      <div
        ref={ref}
        data-testid="indicator-grid"
        data-slot="indicator-grid"
        data-loading="false"
        role="grid"
        aria-label={ariaLabel}
        aria-rowcount={indicators.length.toString()}
        className={gridClasses}
        {...props}
      >
        {indicators.map((indicator) => (
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
            />
          </div>
        ))}
      </div>
    );
  }
);

IndicatorGrid.displayName = 'IndicatorGrid';

// ============================================================================
// Exports
// ============================================================================

export default IndicatorGrid;
