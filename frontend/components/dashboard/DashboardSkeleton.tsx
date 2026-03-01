"use client";

import * as React from "react";
import { Card, CardContent } from "@/components/ui/card";

export function DashboardSkeleton() {
  return (
    <div className="space-y-6" data-testid="dashboard-skeleton">
      {/* Welcome Card Skeleton */}
      <Card className="border-slate-800 bg-slate-900/60">
        <CardContent className="p-6">
          <div className="space-y-2">
            <div className="h-4 w-32 animate-pulse rounded bg-slate-800" />
            <div className="h-8 w-48 animate-pulse rounded bg-slate-800" />
          </div>
        </CardContent>
      </Card>

      {/* Stats Grid Skeleton */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <Card key={i} className="border-slate-800 bg-slate-900/60">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div className="space-y-2">
                  <div className="h-4 w-24 animate-pulse rounded bg-slate-800" />
                  <div className="h-8 w-16 animate-pulse rounded bg-slate-800" />
                </div>
                <div className="h-10 w-10 animate-pulse rounded-lg bg-slate-800" />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Secondary Cards Skeleton */}
      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="border-slate-800 bg-slate-900/60">
          <CardContent className="p-6 space-y-4">
            <div className="h-5 w-32 animate-pulse rounded bg-slate-800" />
            <div className="h-10 w-24 animate-pulse rounded bg-slate-800" />
            <div className="h-6 w-20 animate-pulse rounded bg-slate-800" />
          </CardContent>
        </Card>
        <Card className="border-slate-800 bg-slate-900/60">
          <CardContent className="p-6 space-y-4">
            <div className="h-5 w-32 animate-pulse rounded bg-slate-800" />
            {Array.from({ length: 3 }).map((_, i) => (
              <div key={i} className="h-10 w-full animate-pulse rounded bg-slate-800" />
            ))}
          </CardContent>
        </Card>
        <Card className="border-slate-800 bg-slate-900/60">
          <CardContent className="p-6 space-y-3">
            <div className="h-5 w-32 animate-pulse rounded bg-slate-800" />
            {Array.from({ length: 3 }).map((_, i) => (
              <div key={i} className="h-10 w-full animate-pulse rounded bg-slate-800" />
            ))}
          </CardContent>
        </Card>
      </div>

      {/* Recent Analyses Skeleton */}
      <Card className="border-slate-800 bg-slate-900/60">
        <CardContent className="p-6 space-y-3">
          <div className="h-5 w-40 animate-pulse rounded bg-slate-800" />
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-12 w-full animate-pulse rounded bg-slate-800" />
          ))}
        </CardContent>
      </Card>
    </div>
  );
}

export function AuthLoadingState() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-950">
      <div className="text-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-slate-700 border-t-emerald-500 mx-auto" />
        <p className="mt-4 text-sm text-slate-400">Loading...</p>
      </div>
    </div>
  );
}
