"use client";

import * as React from "react";
import { Card, CardContent } from "@/components/ui/card";
import { PWAScoreGaugeSkeleton } from "./PWAScoreGauge";
import { ManifestStatusCardSkeleton } from "./ManifestStatusCard";

export function PWASkeleton() {
  return (
    <div
      className="space-y-6"
      data-testid="pwa-skeleton"
      aria-busy="true"
      aria-label="Loading PWA analysis"
    >
      {/* Header Skeleton */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <div className="h-6 w-32 animate-pulse rounded bg-slate-700" />
          <div className="h-4 w-48 animate-pulse rounded bg-slate-700" />
        </div>
        <div className="h-10 w-32 animate-pulse rounded bg-slate-700" />
      </div>

      {/* Content Grid */}
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {/* Score Gauge Skeleton */}
        <Card className="border-slate-800 bg-slate-900/60">
          <CardContent className="p-6 flex justify-center">
            <PWAScoreGaugeSkeleton />
          </CardContent>
        </Card>

        {/* Manifest Status Skeleton */}
        <ManifestStatusCardSkeleton />

        {/* Recommendations Skeleton */}
        <Card className="border-slate-800 bg-slate-900/60">
          <CardContent className="p-6 space-y-3">
            <div className="h-5 w-28 animate-pulse rounded bg-slate-700" />
            {Array.from({ length: 3 }).map((_, i) => (
              <div key={i} className="flex items-start gap-2">
                <div className="h-4 w-4 mt-0.5 animate-pulse rounded bg-slate-700" />
                <div className="flex-1 space-y-1">
                  <div className="h-4 w-full animate-pulse rounded bg-slate-700" />
                  <div className="h-3 w-24 animate-pulse rounded bg-slate-700" />
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

export function PWAErrorSkeleton({
  message,
  onRetry,
}: {
  message: string;
  onRetry?: () => void;
}) {
  return (
    <div
      className="flex flex-col items-center justify-center p-8 text-center"
      data-testid="pwa-error"
    >
      <div className="rounded-full bg-red-500/10 p-4 mb-4">
        <svg
          className="h-8 w-8 text-red-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>
      <h3 className="text-lg font-medium text-white mb-2">Unable to Load PWA Data</h3>
      <p className="text-sm text-slate-400 mb-4 max-w-md">{message}</p>
      {onRetry && (
        <button
          onClick={onRetry}
          className="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors"
        >
          Try Again
        </button>
      )}
    </div>
  );
}

export function PWANoAnalysisSkeleton({
  onAnalyze,
}: {
  onAnalyze?: () => void;
}) {
  return (
    <div
      className="flex flex-col items-center justify-center p-8 text-center"
      data-testid="pwa-no-analysis"
    >
      <div className="rounded-full bg-blue-500/10 p-4 mb-4">
        <svg
          className="h-8 w-8 text-blue-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
          />
        </svg>
      </div>
      <h3 className="text-lg font-medium text-white mb-2">No PWA Analysis Yet</h3>
      <p className="text-sm text-slate-400 mb-4 max-w-md">
        Analyze your site's PWA capabilities to see detailed information about
        manifest, icons, and installability.
      </p>
      {onAnalyze && (
        <button
          onClick={onAnalyze}
          className="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors"
        >
          Analyze Now
        </button>
      )}
    </div>
  );
}

export default PWASkeleton;
