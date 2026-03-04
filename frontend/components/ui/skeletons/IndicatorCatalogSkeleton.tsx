import { cn } from "@/lib/utils"
import { Skeleton } from "@/components/ui/skeleton"
import { CardSkeleton } from "./CardSkeleton"
import React from "react"

export interface ResponsiveColumns {
  default?: number
  sm?: number
  md?: number
  lg?: number
  xl?: number
  '2xl'?: number
}

export interface IndicatorCatalogSkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  count?: number
  columns?: ResponsiveColumns
}

/**
 * IndicatorCatalogSkeleton - Loading placeholder for the indicator catalog
 * Renders a grid of card skeletons with optional header and filter areas
 */
export function IndicatorCatalogSkeleton({
  count = 6,
  columns,
  className,
  ...props
}: IndicatorCatalogSkeletonProps) {
  // Handle edge cases
  const safeCount = Math.max(0, Math.floor(count ?? 6))

  // Default responsive columns
  const defaultColumns: ResponsiveColumns = {
    default: 1,
    sm: 2,
    md: 2,
    lg: 3,
    xl: 4,
  }

  const cols = columns ?? defaultColumns

  // Build grid classes
  const gridClasses = cn(
    "grid gap-4",
    cols.default && `grid-cols-${cols.default}`,
    cols.sm && `sm:grid-cols-${cols.sm}`,
    cols.md && `md:grid-cols-${cols.md}`,
    cols.lg && `lg:grid-cols-${cols.lg}`,
    cols.xl && `xl:grid-cols-${cols.xl}`,
    cols['2xl'] && `2xl:grid-cols-${cols['2xl']}`
  )

  return (
    <div
      className={cn(
        "indicator-catalog-skeleton w-full max-w-7xl mx-auto space-y-6 grid gap-4 dark:bg-muted/10",
        cols.default && `grid-cols-${cols.default}`,
        cols.sm && `sm:grid-cols-${cols.sm}`,
        cols.md && `md:grid-cols-${cols.md}`,
        cols.lg && `lg:grid-cols-${cols.lg}`,
        cols.xl && `xl:grid-cols-${cols.xl}`,
        cols['2xl'] && `2xl:grid-cols-${cols['2xl']}`,
        className
      )}
      aria-busy="true"
      aria-label="Loading indicator catalog"
      {...props}
    >
      {/* Header skeleton */}
      <div className="header-skeleton space-y-2 col-span-full">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-4 w-64" />
      </div>

      {/* Search/Filter skeleton */}
      <div className="search-skeleton flex gap-4 col-span-full">
        <Skeleton className="h-10 w-64" />
        <Skeleton className="h-10 w-32" />
      </div>

      {/* Card grid */}
      {safeCount > 0 && (
        <div className={cn(gridClasses, "col-span-full contents")}>
          {Array.from({ length: safeCount }).map((_, index) => (
            <CardSkeleton
              key={index}
              showIcon={true}
              lines={3}
              className="h-full"
            />
          ))}
        </div>
      )}
    </div>
  )
}

export default IndicatorCatalogSkeleton
