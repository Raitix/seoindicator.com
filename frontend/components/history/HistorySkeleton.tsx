"use client";

import * as React from "react";
import { Loader2 } from "lucide-react";

import type { HistorySkeletonProps } from "@/lib/types/analysis";

// ==================== History Skeleton Component ====================

export function HistorySkeleton({ rows = 10 }: HistorySkeletonProps) {
  return (
    <div className="space-y-6" data-testid="history-skeleton">
      {/* Loading Spinner - primary loading indicator */}
      <div className="flex items-center justify-center py-4" data-testid="loading-spinner">
        <Loader2 className="h-8 w-8 animate-spin text-slate-400" />
      </div>

      {/* Filters Skeleton */}
      <div className="flex flex-wrap items-center gap-3" data-testid="skeleton">
        <div className="h-10 w-48 bg-slate-800 rounded-md animate-pulse" />
        <div className="h-10 w-40 bg-slate-800 rounded-md animate-pulse" />
        <div className="h-10 w-40 bg-slate-800 rounded-md animate-pulse" />
      </div>

      {/* Table Skeleton */}
      <div className="rounded-lg border border-slate-800 overflow-hidden">
        <div className="bg-slate-900/50 p-4 border-b border-slate-800">
          <div className="flex items-center justify-between">
            {[...Array(7)].map((_, i) => (
              <div
                key={i}
                className="h-4 bg-slate-800 rounded animate-pulse"
                style={{ width: `${60 + Math.random() * 40}px` }}
              />
            ))}
          </div>
        </div>

        <div className="divide-y divide-slate-800">
          {[...Array(rows)].map((_, i) => (
            <div key={i} className="p-4 flex items-center justify-between">
              <div className="flex items-center gap-4 w-full">
                {/* Tool name */}
                <div className="h-4 bg-slate-800 rounded animate-pulse w-32" />
                
                {/* URL */}
                <div className="h-4 bg-slate-800 rounded animate-pulse w-40" />
                
                {/* Status */}
                <div className="h-6 bg-slate-800 rounded-full animate-pulse w-20" />
                
                {/* Cost */}
                <div className="h-4 bg-slate-800 rounded animate-pulse w-12" />
                
                {/* Duration */}
                <div className="h-4 bg-slate-800 rounded animate-pulse w-16" />
                
                {/* Date */}
                <div className="h-4 bg-slate-800 rounded animate-pulse w-28" />
                
                {/* Actions */}
                <div className="flex gap-2 ml-auto">
                  <div className="h-8 w-8 bg-slate-800 rounded animate-pulse" />
                  <div className="h-8 w-8 bg-slate-800 rounded animate-pulse" />
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Pagination Skeleton */}
      <div className="flex items-center justify-between py-4">
        <div className="h-4 bg-slate-800 rounded animate-pulse w-32" />
        <div className="flex items-center gap-2">
          <div className="h-8 w-8 bg-slate-800 rounded animate-pulse" />
          <div className="h-8 w-8 bg-slate-800 rounded animate-pulse" />
          <div className="h-8 w-8 bg-slate-800 rounded animate-pulse" />
          <div className="h-8 w-8 bg-slate-800 rounded animate-pulse" />
          <div className="h-8 w-16 bg-slate-800 rounded animate-pulse ml-4" />
        </div>
      </div>
    </div>
  );
}

export default HistorySkeleton;
