import * as React from "react";
import { cn } from "@/lib/utils";
import { Skeleton } from "@/components/ui/skeleton";

export interface ChartSkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  type?: "line" | "bar" | "pie";
}

/**
 * ChartSkeleton - Loading placeholder for chart visualizations
 * @param type - Chart type: 'line', 'bar', or 'pie' (default: 'line')
 * @param className - Additional CSS classes
 */
export function ChartSkeleton({
  type = "line",
  className,
  ...props
}: ChartSkeletonProps) {
  const renderLineChart = () => (
    <div className="relative h-full w-full">
      {/* Grid lines */}
      <div className="absolute inset-0 flex flex-col justify-between py-4">
        {Array.from({ length: 5 }).map((_, i) => (
          <div
            key={i}
            className="grid-line h-px w-full bg-muted"
          />
        ))}
      </div>

      {/* Y-axis labels */}
      <div className="absolute left-0 top-0 flex h-full flex-col justify-between py-2">
        {Array.from({ length: 5 }).map((_, i) => (
          <Skeleton
            key={i}
            className="y-label h-3 w-8"
          />
        ))}
      </div>

      {/* Chart area with line */}
      <svg
        className="chart-area absolute left-12 right-4 top-4 bottom-8"
        viewBox="0 0 100 60"
        preserveAspectRatio="none"
      >
        <Skeleton
          asChild
          className="h-full w-full"
        >
          <path
            d="M0,50 Q20,40 40,30 T80,20 T100,15"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            className="text-muted"
          />
        </Skeleton>
        {/* Data points */}
        {[20, 40, 60, 80].map((x, i) => (
          <circle
            key={i}
            cx={x}
            cy={35 - i * 5}
            r="3"
            className="data-point fill-muted"
          />
        ))}
      </svg>

      {/* X-axis labels */}
      <div className="absolute bottom-0 left-12 right-4 flex justify-between">
        {Array.from({ length: 5 }).map((_, i) => (
          <Skeleton
            key={i}
            className="x-label h-3 w-10"
          />
        ))}
      </div>
    </div>
  );

  const renderBarChart = () => (
    <div className="relative h-full w-full p-4">
      {/* Y-axis labels */}
      <div className="absolute left-0 top-4 bottom-8 flex flex-col justify-between">
        {Array.from({ length: 5 }).map((_, i) => (
          <Skeleton
            key={i}
            className="y-label h-3 w-8"
          />
        ))}
      </div>

      {/* Bars container */}
      <div className="absolute left-12 right-4 top-4 bottom-8 flex items-end justify-around gap-2">
        {Array.from({ length: 7 }).map((_, i) => (
          <div
            key={i}
            className="bar-skeleton flex-1"
          >
            <Skeleton
              className="w-full"
              style={{ height: `${30 + Math.random() * 60}%` }}
            />
          </div>
        ))}
      </div>

      {/* X-axis labels */}
      <div className="absolute bottom-0 left-12 right-4 flex justify-around">
        {Array.from({ length: 7 }).map((_, i) => (
          <Skeleton
            key={i}
            className="x-label h-3 w-8"
          />
        ))}
      </div>
    </div>
  );

  const renderPieChart = () => (
    <div className="relative flex h-full w-full items-center justify-center gap-8 p-4">
      {/* Pie circle */}
      <div className="relative">
        <svg
          viewBox="0 0 100 100"
          className="h-40 w-40"
        >
          <Skeleton
            asChild
            className="h-full w-full"
          >
            <circle
              cx="50"
              cy="50"
              r="40"
              className="pie-circle fill-muted"
            />
          </Skeleton>
          {/* Segments represented as arcs */}
          <path
            d="M50,50 L50,10 A40,40 0 0,1 90,50 Z"
            className="pie-segment fill-muted-foreground/20"
          />
          <path
            d="M50,50 L90,50 A40,40 0 0,1 50,90 Z"
            className="pie-segment fill-muted-foreground/30"
          />
        </svg>
      </div>

      {/* Legend */}
      <div className="legend flex flex-col gap-2">
        {Array.from({ length: 4 }).map((_, i) => (
          <div
            key={i}
            className="flex items-center gap-2"
          >
            <Skeleton className="h-3 w-3 rounded-sm" />
            <Skeleton className="h-3 w-20" />
          </div>
        ))}
      </div>
    </div>
  );

  return (
    <div
      className={cn(
        "chart-skeleton",
        "rounded-lg border bg-card p-4",
        "dark:bg-card dark:border-border",
        "w-full min-h-[200px] aspect-video",
        className
      )}
      aria-busy="true"
      aria-label={`Loading ${type} chart`}
      role="img"
      {...props}
    >
      {/* Title placeholder */}
      <Skeleton className="chart-title mb-4 h-5 w-32" />

      {/* Chart content */}
      <div className="h-[calc(100%-2rem)]">
        {type === "line" && renderLineChart()}
        {type === "bar" && renderBarChart()}
        {type === "pie" && renderPieChart()}
        {!["line", "bar", "pie"].includes(type) && renderLineChart()}
      </div>
    </div>
  );
}

export default ChartSkeleton;
