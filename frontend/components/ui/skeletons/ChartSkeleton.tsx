import { cn } from "@/lib/utils"
import { Skeleton } from "@/components/ui/skeleton"
import React from "react"

export type ChartType = 'line' | 'bar' | 'pie'

export interface ChartSkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  type?: ChartType
}

/**
 * ChartSkeleton - Loading placeholder for chart components
 * Supports line, bar, and pie chart types
 */
export function ChartSkeleton({
  type = 'line',
  className,
  ...props
}: ChartSkeletonProps) {
  return (
    <div
      className={cn(
        "chart-skeleton rounded-lg border bg-card p-4 shadow-sm dark:border-muted dark:bg-muted/20",
        "w-full min-h-[300px]",
        type === 'pie' && "flex items-center justify-center",
        className
      )}
      aria-busy="true"
      aria-label={`Loading ${type} chart`}
      role="img"
      {...props}
    >
      {/* Title placeholder */}
      <div className="chart-title mb-4">
        <Skeleton className="h-5 w-32" />
      </div>

      {/* Chart area */}
      <div className="flex aspect-video w-full items-center justify-center">
        {type === 'line' && <LineChartSkeleton />}
        {type === 'bar' && <BarChartSkeleton />}
        {type === 'pie' && <PieChartSkeleton />}
      </div>
    </div>
  )
}

function LineChartSkeleton() {
  return (
    <div className="chart-area relative h-full w-full">
      {/* Axes */}
      <div className="axis-skeleton absolute bottom-0 left-6 right-0 h-px bg-muted-foreground/20" data-axis="x" />
      <div className="axis-skeleton absolute bottom-0 left-6 top-6 w-px bg-muted-foreground/20" data-axis="y" />

      {/* Grid lines */}
      <div className="absolute bottom-0 left-6 right-0 top-6">
        {[0.25, 0.5, 0.75].map((pos) => (
          <div
            key={pos}
            className="grid-line absolute left-0 right-0 h-px bg-muted-foreground/10"
            style={{ bottom: `${pos * 100}%` }}
          />
        ))}
      </div>

      {/* Y-axis labels */}
      <div className="absolute bottom-0 left-0 top-6 flex flex-col justify-between py-1">
        {[0, 25, 50, 75, 100].map((val) => (
          <Skeleton key={val} className="y-label h-3 w-5" />
        ))}
      </div>

      {/* Data points and line area */}
      <div className="absolute bottom-4 left-10 right-4 top-8">
        <svg className="h-full w-full overflow-visible" viewBox="0 0 200 100" preserveAspectRatio="none">
          {/* Line path placeholder */}
          <path
            d="M0,80 Q40,60 80,50 T160,30 T200,20"
            fill="none"
            className="stroke-muted-foreground/20"
            strokeWidth="2"
          />
        </svg>
        {/* Data points */}
        {[20, 80, 140].map((x) => (
          <div
            key={x}
            className="data-point absolute h-3 w-3 rounded-full bg-muted"
            style={{ left: `${x * 0.5}%`, top: '30%' }}
          />
        ))}
      </div>

      {/* X-axis labels */}
      <div className="absolute bottom-0 left-10 right-4 flex justify-between">
        {[0, 1, 2, 3].map((i) => (
          <Skeleton key={i} className="x-label h-3 w-8" />
        ))}
      </div>
    </div>
  )
}

function BarChartSkeleton() {
  const barHeights = [70, 45, 85, 55, 90, 40, 75]

  return (
    <div className="chart-area relative h-full w-full">
      {/* Axes */}
      <div className="axis-skeleton absolute bottom-6 left-8 right-0 h-px bg-muted-foreground/20" data-axis="x" />
      <div className="axis-skeleton absolute bottom-6 left-8 top-4 w-px bg-muted-foreground/20" data-axis="y" />

      {/* Y-axis labels */}
      <div className="absolute bottom-6 left-0 top-4 flex flex-col justify-between py-1">
        {[0, 50, 100].map((val) => (
          <Skeleton key={val} className="y-label h-3 w-6" />
        ))}
      </div>

      {/* Bars */}
      <div className="absolute bottom-8 left-10 right-4 top-6 flex items-end justify-around gap-2">
        {barHeights.map((height, index) => (
          <div
            key={index}
            className="bar-skeleton w-full max-w-12 rounded-t bg-muted"
            style={{ height: `${height}%` }}
          />
        ))}
      </div>

      {/* X-axis labels */}
      <div className="absolute bottom-0 left-10 right-4 flex justify-around">
        {barHeights.map((_, index) => (
          <Skeleton key={index} className="x-label h-3 w-8" />
        ))}
      </div>
    </div>
  )
}

function PieChartSkeleton() {
  return (
    <div className="flex h-full w-full items-center justify-center gap-4">
      {/* Pie circle */}
      <div className="relative h-32 w-32">
        <svg className="h-full w-full -rotate-90" viewBox="0 0 36 36">
          {/* Segments */}
          <circle
            className="pie-segment fill-muted"
            cx="18"
            cy="18"
            r="15.9"
            pathLength="100"
            style={{ strokeDasharray: '30 70', strokeDashoffset: 0 }}
          />
          <circle
            className="pie-segment fill-muted-foreground/30"
            cx="18"
            cy="18"
            r="15.9"
            pathLength="100"
            style={{ strokeDasharray: '25 75', strokeDashoffset: -30 }}
          />
          <circle
            className="pie-segment fill-muted-foreground/50"
            cx="18"
            cy="18"
            r="15.9"
            pathLength="100"
            style={{ strokeDasharray: '25 75', strokeDashoffset: -55 }}
          />
          <circle
            className="pie-segment fill-muted-foreground/70"
            cx="18"
            cy="18"
            r="15.9"
            pathLength="100"
            style={{ strokeDasharray: '20 80', strokeDashoffset: -80 }}
          />
        </svg>
        {/* Center circle for donut effect */}
        <div className="absolute inset-4 rounded-full bg-card dark:bg-muted/20" />
      </div>

      {/* Legend */}
      <div className="legend flex flex-col gap-2">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="flex items-center gap-2">
            <Skeleton className="h-3 w-3 rounded-sm" />
            <Skeleton className="h-3 w-16" />
          </div>
        ))}
      </div>
    </div>
  )
}

export default ChartSkeleton
