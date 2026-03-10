"use client";

import * as React from "react";

import { Button } from "@/components/ui/button";
import type { LocalSeoAlert } from "@/lib/types/location-alerts";

export interface LocalSeoAlertToastProps {
  alert: LocalSeoAlert;
  onOpen: (alert: LocalSeoAlert) => void;
  onDismiss: (alertId: string) => void;
  autoDismissMs?: number;
}

export function LocalSeoAlertToast({
  alert,
  onOpen,
  onDismiss,
  autoDismissMs = 5000,
}: LocalSeoAlertToastProps) {
  React.useEffect(() => {
    const timer = window.setTimeout(() => {
      onDismiss(alert.id);
    }, autoDismissMs);

    return () => window.clearTimeout(timer);
  }, [alert.id, autoDismissMs, onDismiss]);

  return (
    <div
      role="status"
      aria-live="assertive"
      data-testid={`local-seo-alert-toast-${alert.id}`}
      className="rounded-2xl border border-slate-700 bg-slate-950/95 p-4 text-sm text-slate-100 shadow-2xl"
    >
      <div className="space-y-1">
        <div className="font-medium">{alert.title}</div>
        <div className="text-slate-400">{alert.message}</div>
      </div>
      <div className="mt-3 flex items-center gap-2">
        <Button type="button" size="sm" onClick={() => onOpen(alert)}>
          View details
        </Button>
        <Button type="button" size="sm" variant="ghost" onClick={() => onDismiss(alert.id)}>
          Dismiss
        </Button>
      </div>
    </div>
  );
}

export default LocalSeoAlertToast;
