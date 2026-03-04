import { cn } from "@/lib/utils"
import { Skeleton } from "@/components/ui/skeleton"
import React from "react"

export interface TableSkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  rows?: number
  columns?: number
  showHeader?: boolean
}

/**
 * TableSkeleton - Loading placeholder for data tables
 * Supports configurable rows, columns, and header visibility
 */
export function TableSkeleton({
  rows = 5,
  columns = 4,
  showHeader = true,
  className,
  ...props
}: TableSkeletonProps) {
  // Handle edge cases
  const safeRows = Math.max(0, Math.floor(rows ?? 5))
  const safeColumns = Math.max(0, Math.floor(columns ?? 4))

  return (
    <div
      className={cn("overflow-x-auto rounded-lg border dark:border-muted", className)}
      aria-busy="true"
      aria-label="Loading table data"
      {...props}
    >
      <table className="w-full border-collapse dark:border-muted/50">
        {/* Header */}
        {showHeader && safeColumns > 0 && (
          <thead className="border-b bg-muted/50 dark:bg-muted/30">
            <tr className="transition-colors hover:bg-muted/40">
              {Array.from({ length: safeColumns }).map((_, colIndex) => (
                <th
                  key={colIndex}
                  className="border-r px-4 py-3 last:border-r-0 dark:border-muted/50"
                >
                  <Skeleton className="h-4 w-20" />
                </th>
              ))}
            </tr>
          </thead>
        )}

        {/* Body rows */}
        <tbody>
          {safeRows > 0 && safeColumns > 0 && Array.from({ length: safeRows }).map((_, rowIndex) => (
            <tr
              key={rowIndex}
              className={cn(
                "border-b last:border-b-0 transition-colors hover:bg-muted/30 dark:border-muted/50 dark:hover:bg-muted/20"
              )}
            >
              {Array.from({ length: safeColumns }).map((_, colIndex) => (
                <td
                  key={colIndex}
                  className="border-r px-4 py-3 last:border-r-0 dark:border-muted/50"
                >
                  <Skeleton className="h-4 w-16" />
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

export default TableSkeleton
