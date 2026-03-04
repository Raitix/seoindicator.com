import * as React from "react";
import { cn } from "@/lib/utils";
import { Skeleton } from "@/components/ui/skeleton";

export interface TableSkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  rows?: number;
  columns?: number;
  showHeader?: boolean;
}

/**
 * TableSkeleton - Loading placeholder for data tables
 * @param rows - Number of body rows (default: 5)
 * @param columns - Number of columns (default: 4)
 * @param showHeader - Whether to show header row (default: true)
 * @param className - Additional CSS classes
 */
export function TableSkeleton({
  rows = 5,
  columns = 4,
  showHeader = true,
  className,
  ...props
}: TableSkeletonProps) {
  // Handle edge cases
  const safeRows = Math.max(0, Math.floor(rows ?? 5));
  const safeColumns = Math.max(0, Math.floor(columns ?? 4));

  return (
    <div
      className={cn("overflow-x-auto rounded-lg border", className)}
      aria-busy="true"
      aria-label="Loading table data"
      {...props}
    >
      <table className="w-full border-collapse">
        {showHeader && (
          <thead className="bg-muted/50 dark:bg-muted/30">
            <tr>
              {Array.from({ length: safeColumns }).map((_, i) => (
                <th
                  key={i}
                  className="border-b p-3 text-left"
                >
                  <Skeleton className="h-4 w-20" />
                </th>
              ))}
            </tr>
          </thead>
        )}
        <tbody>
          {Array.from({ length: safeRows }).map((_, rowIndex) => (
            <tr
              key={rowIndex}
              className={cn(
                "border-b transition-colors hover:bg-muted/50 dark:hover:bg-muted/20",
                rowIndex % 2 === 1 ? "bg-muted/30 dark:bg-muted/10" : ""
              )}
            >
              {Array.from({ length: safeColumns }).map((_, colIndex) => (
                <td
                  key={colIndex}
                  className="p-3"
                >
                  <Skeleton
                    className={cn(
                      "h-4",
                      colIndex === 0 ? "w-32" : colIndex === safeColumns - 1 ? "w-16" : "w-24"
                    )}
                  />
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default TableSkeleton;
