import * as React from "react";
import { cn } from "@/lib/utils";
import { Skeleton } from "@/components/ui/skeleton";

export interface CardSkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  showIcon?: boolean;
  lines?: number;
}

/**
 * CardSkeleton - Loading placeholder for indicator cards
 * @param showIcon - Whether to show icon placeholder (default: true)
 * @param lines - Number of text lines to show (default: 3)
 * @param className - Additional CSS classes
 */
export function CardSkeleton({
  showIcon = true,
  lines = 3,
  className,
  ...props
}: CardSkeletonProps) {
  // Handle edge cases
  const safeLines = Math.max(0, Math.floor(lines ?? 3));

  return (
    <div
      className={cn(
        "card-skeleton",
        "rounded-lg border bg-card p-4 shadow-sm",
        "dark:bg-card dark:border-border",
        className
      )}
      aria-busy="true"
      aria-label="Loading content"
      {...props}
    >
      <div className="flex items-start gap-4">
        {showIcon && (
          <Skeleton
            className="icon-skeleton h-12 w-12 rounded-md flex-shrink-0"
          />
        )}
        <div className={cn("lines-container flex-1", safeLines > 1 ? "space-y-2" : "")}>
          {Array.from({ length: safeLines }).map((_, i) => (
            <Skeleton
              key={i}
              className={cn(
                "line-skeleton h-4",
                // Varying widths for visual interest
                i === 0 ? "w-full" : i === 1 ? "w-5/6" : i === 2 ? "w-4/6" : i === 3 ? "w-3/4" : "w-2/3"
              )}
            />
          ))}
        </div>
      </div>
    </div>
  );
}

export default CardSkeleton;
