"use client";

import { Clock3, Loader2, RefreshCw } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import { formatDateTime, formatRelativeTime } from "@/lib/location-dashboard";

export interface SyncStatusProps {
  locationId: string;
  lastSyncAt: string | null;
  onRefresh: () => Promise<void>;
  isLoading?: boolean;
  className?: string;
}

export function SyncStatus({
  lastSyncAt,
  onRefresh,
  isLoading = false,
  className,
}: SyncStatusProps) {
  return (
    <Card className={cn("border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10", className)}>
      <CardHeader className="border-b border-slate-800/80 pb-5">
        <CardTitle className="flex items-center gap-2 text-white">
          <Clock3 className="h-5 w-5 text-slate-300" aria-hidden="true" />
          Sync status
        </CardTitle>
        <p className="mt-2 text-sm text-slate-400">See when Local SEO data was updated and trigger a manual refresh.</p>
      </CardHeader>

      <CardContent className="space-y-4 pt-6">
        <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
          <div className="text-sm text-slate-400">Last synced</div>
          <div className="mt-2 text-lg font-semibold text-white">{formatRelativeTime(lastSyncAt)}</div>
          <div className="mt-1 text-xs text-slate-500">{formatDateTime(lastSyncAt)}</div>
        </div>

        <Button
          type="button"
          className="w-full"
          onClick={() => void onRefresh()}
          disabled={isLoading}
          aria-label={isLoading ? "Refreshing location data" : "Refresh location data"}
        >
          {isLoading ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" /> : <RefreshCw className="h-4 w-4" aria-hidden="true" />}
          {isLoading ? "Refreshing…" : "Refresh data"}
        </Button>
      </CardContent>
    </Card>
  );
}

export default SyncStatus;
