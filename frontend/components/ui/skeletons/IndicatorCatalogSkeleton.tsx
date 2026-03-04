import * as React from "react";
import { cn } from "@/lib/utils";
import { CardSkeleton } from "./CardSkeleton";

export interface ResponsiveColumns {
  default?: number;
  sm?: number;
  md?: number;
  lg?: number;
  xl?: number;
  "2xl"?: number;
}

export interface IndicatorCatalogSkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  count?: number;
  columns?: ResponsiveColumns;
}

/**
 * Converts ResponsiveColumns object to Tailwind grid classes
 */
function getGridClasses(columns: ResponsiveColumns): string {
  const classes: string[] = [];

  if (columns.default) {
    classes.push(`grid-cols-${columns.default}`);
  } else {
    classes.push("grid-cols-1");
  }

  if (columns.sm) classes.push(`sm:grid-cols-${columns.sm}`);
  if (columns.md) classes.push(`md:grid-cols-${columns.md}`);
  if (columns.lg) classes.push(`lg:grid-cols-${columns.lg}`);
  if (columns.xl) classes.push(`xl:grid-cols-${columns.xl}`);
  if (columns["2xl"]) classes.push(`2xl:grid-cols-${columns["2xl"]}`);

  return classes.join(" ");
}

/**
 * IndicatorCatalogSkeleton - Loading placeholder for indicator catalog
 * @param count - Number of card skeletons to show (default: 6)
 * @param columns - Responsive grid columns configuration
 * @param className - Additional CSS classes
 */
export function IndicatorCatalogSkeleton({
  count = 6,
  columns = { default: 1, sm: 2, md: 3, lg: 4 },
  className,
  ...props
}: IndicatorCatalogSkeletonProps) {
  // Handle edge cases
  const safeCount = Math.max(0, Math.floor(count ?? 6));

  // Default responsive grid if columns not provided or empty
  const gridClasses = Object.keys(columns).length > 0
    ? getGridClasses(columns)
    : "grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4";

  return (
    <div
      className={cn(
        "indicator-catalog-skeleton",
        "container max-w-7xl space-y-6",
        className
      )}
      aria-busy="true"
      aria-label="Loading indicator catalog"
      {...props}
    >
      {/* Header skeleton */}
      <div className="header-skeleton space-y-2">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-4 w-64" />
      </div>

      {/* Search/Filter skeleton */}
      <div className="search-skeleton flex gap-4">
        <Skeleton className="h-10 w-full max-w-md" />
        <Skeleton className="h-10 w-32" />
      </div>

      {/* Card grid */}
      <div className={cn("grid gap-4", gridClasses)}>
        {Array.from({ length: safeCount }).map((_, i) => (
          <CardSkeleton
            key={i}
            className="h-full"
          />
        ))}
      </div>
    </div>
  );
}

// Import Skeleton for header/search
import { Skeleton } from "@/components/ui/skeleton";

export default IndicatorCatalogSkeleton;
