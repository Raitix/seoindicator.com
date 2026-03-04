import { cn } from "@/lib/utils"
import { Skeleton } from "@/components/ui/skeleton"
import React from "react"

export interface CardSkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  showIcon?: boolean
  lines?: number
}

/**
 * CardSkeleton - Loading placeholder for card components
 * Used in IndicatorCatalog and other card-based layouts
 */
export function CardSkeleton({
  showIcon = true,
  lines = 3,
  className,
  ...props
}: CardSkeletonProps) {
  // Handle edge cases
  const safeLines = Math.max(0, Math.floor(lines ?? 3))

  // Width variations for visual interest
  const lineWidths = ['w-full', 'w-5/6', 'w-4/5', 'w-3/4', 'w-2/3']

  return (
    <div
      className={cn(
        "card-skeleton rounded-lg border bg-card p-4 shadow-sm dark:border-muted dark:bg-muted/20",
        "flex flex-col gap-3",
        className
      )}
      aria-busy="true"
      aria-label="Loading content"
      {...props}
    >
      {/* Icon placeholder */}
      {showIcon && (
        <div className="flex items-center gap-3">
          <Skeleton
            className="icon-skeleton h-10 w-10 rounded-md shrink-0"
          />
          <Skeleton className="h-5 w-24" />
        </div>
      )}

      {/* Content lines */}
      {safeLines > 0 && (
        <div className={cn("lines-container flex-1 space-y-2", showIcon && "mt-2")}>
          {Array.from({ length: safeLines }).map((_, index) => (
            <Skeleton
              key={index}
              className={cn(
                "line-skeleton h-3",
                lineWidths[index % lineWidths.length]
              )}
            />
          ))}
        </div>
      )}
    </div>
  )
}

export default CardSkeleton
