/**
 * Skeleton Loader Components
 * 
 * A collection of specialized skeleton loader components for different content types.
 * All skeletons use the base shadcn/ui Skeleton component with pulse animation.
 * 
 * Components:
 * - CardSkeleton: Loading placeholder for indicator cards
 * - TableSkeleton: Loading placeholder for data tables
 * - ChartSkeleton: Loading placeholder for chart visualizations
 * - IndicatorCatalogSkeleton: Loading placeholder for the indicator catalog
 * 
 * @example
 * ```tsx
 * import { CardSkeleton, TableSkeleton, ChartSkeleton, IndicatorCatalogSkeleton } from '@/components/ui/skeletons';
 * 
 * // Basic card skeleton
 * <CardSkeleton />
 * 
 * // Card without icon
 * <CardSkeleton showIcon={false} lines={2} />
 * 
 * // Table with custom dimensions
 * <TableSkeleton rows={10} columns={5} />
 * 
 * // Bar chart skeleton
 * <ChartSkeleton type="bar" />
 * 
 * // Catalog skeleton
 * <IndicatorCatalogSkeleton count={12} columns={{ md: 2, lg: 3 }} />
 * ```
 */

// Components
export { CardSkeleton, type CardSkeletonProps } from "./CardSkeleton";
export { TableSkeleton, type TableSkeletonProps } from "./TableSkeleton";
export { ChartSkeleton, type ChartSkeletonProps } from "./ChartSkeleton";
export { IndicatorCatalogSkeleton, type IndicatorCatalogSkeletonProps, type ResponsiveColumns } from "./IndicatorCatalogSkeleton";

// Default exports for convenience
export { default as CardSkeletonDefault } from "./CardSkeleton";
export { default as TableSkeletonDefault } from "./TableSkeleton";
export { default as ChartSkeletonDefault } from "./ChartSkeleton";
export { default as IndicatorCatalogSkeletonDefault } from "./IndicatorCatalogSkeleton";
