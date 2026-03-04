/**
 * Skeleton Loader Variants
 * Re-export all skeleton components from a single entry point
 */

export { CardSkeleton, type CardSkeletonProps } from './CardSkeleton'
export { TableSkeleton, type TableSkeletonProps } from './TableSkeleton'
export { ChartSkeleton, type ChartSkeletonProps, type ChartType } from './ChartSkeleton'
export {
  IndicatorCatalogSkeleton,
  type IndicatorCatalogSkeletonProps,
  type ResponsiveColumns
} from './IndicatorCatalogSkeleton'

// Default export for convenience
export { CardSkeleton as default } from './CardSkeleton'
