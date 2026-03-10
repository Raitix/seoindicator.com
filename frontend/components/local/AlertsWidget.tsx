"use client";

import Link from "next/link";
import { AlertTriangle, ChevronRight } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";
import { formatDateTime, getSeverityTone } from "@/lib/location-dashboard";
import type { AlertSummary } from "@/lib/types/location-dashboard";

export interface AlertsWidgetProps {
  locationId: string;
  alerts: AlertSummary | null;
  isLoading?: boolean;
  error?: Error | null;
  onRetry?: () => void;
  linkHref: string;
  className?: string;
}

export function AlertsWidget({
  alerts,
  isLoading = false,
  error,
  onRetry,
  linkHref,
  className,
}: AlertsWidgetProps) {
  return (
    <Card className={cn("border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10", className)}>
      <CardHeader className="flex flex-row items-start justify-between gap-4 border-b border-slate-800/80 pb-5">
        <div>
          <CardTitle className="flex items-center gap-2 text-white">
            <AlertTriangle className="h-5 w-5 text-amber-300" aria-hidden="true" />
            Alerts
          </CardTitle>
          <p className="mt-2 text-sm text-slate-400">Unread local SEO alerts and the latest activity for this location.</p>
        </div>

        <Button asChild variant="ghost" className="text-amber-300 hover:bg-slate-900 hover:text-amber-200">
          <Link href={linkHref}>
            View all alerts
            <ChevronRight className="h-4 w-4" aria-hidden="true" />
          </Link>
        </Button>
      </CardHeader>

      <CardContent className="space-y-4 pt-6">
        {error ? (
          <div className="rounded-2xl border border-red-500/20 bg-red-500/10 p-5 text-red-50" role="alert">
            <p className="text-sm text-red-100/90">{error.message}</p>
            {onRetry ? (
              <Button type="button" variant="outline" className="mt-4" onClick={onRetry}>
                Try again
              </Button>
            ) : null}
          </div>
        ) : isLoading ? (
          <div className="space-y-3">
            <Skeleton className="h-12 w-32 bg-slate-800" />
            <Skeleton className="h-20 w-full bg-slate-800" />
            <Skeleton className="h-20 w-full bg-slate-800" />
          </div>
        ) : alerts ? (
          <>
            <div className="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-sm font-medium text-amber-100" role="status" aria-live="polite">
              <span>{alerts.unread_count}</span>
              <span>unread alert{alerts.unread_count === 1 ? "" : "s"}</span>
            </div>

            <div className="space-y-3">
              {alerts.latest.length ? (
                alerts.latest.map((alert) => (
                  <div key={alert.id} className={cn("rounded-2xl border p-4", getSeverityTone(alert.severity))}>
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <div className="text-sm font-medium text-white">{alert.message}</div>
                        <div className="mt-1 text-xs text-slate-300/80">{formatDateTime(alert.timestamp)}</div>
                      </div>
                      <span className="rounded-full border border-current/30 px-2.5 py-1 text-[11px] uppercase tracking-wide">
                        {alert.severity}
                      </span>
                    </div>
                  </div>
                ))
              ) : (
                <div className="rounded-2xl border border-dashed border-slate-800 bg-slate-900/60 px-4 py-6 text-sm text-slate-400">
                  No unread alerts right now.
                </div>
              )}
            </div>
          </>
        ) : (
          <div className="rounded-2xl border border-dashed border-slate-800 bg-slate-900/60 px-4 py-6 text-sm text-slate-400">
            Alert data is not available yet.
          </div>
        )}
      </CardContent>
    </Card>
  );
}

export default AlertsWidget;
