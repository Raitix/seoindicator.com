"use client";

import Link from "next/link";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { formatDateTime } from "@/lib/location-dashboard";
import type { LocalSeoAlert } from "@/lib/types/location-alerts";

export interface LocalSeoAlertDetailModalProps {
  alert: LocalSeoAlert | null;
  isOpen: boolean;
  onClose: () => void;
  onMarkRead?: (alert: LocalSeoAlert) => void | Promise<void>;
}

export function LocalSeoAlertDetailModal({
  alert,
  isOpen,
  onClose,
  onMarkRead,
}: LocalSeoAlertDetailModalProps) {
  return (
    <Dialog open={isOpen} onOpenChange={(next) => (!next ? onClose() : undefined)}>
      <DialogContent aria-describedby="local-seo-alert-detail-description">
        {alert ? (
          <>
            <DialogHeader>
              <DialogTitle>{alert.title}</DialogTitle>
              <DialogDescription id="local-seo-alert-detail-description">
                {alert.message}
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4 text-sm text-slate-300">
              {alert.location ? (
                <div>
                  <span className="font-medium text-white">Location:</span> {alert.location.name}
                </div>
              ) : null}

              <div>
                <span className="font-medium text-white">Triggered:</span> {formatDateTime(alert.createdAt)}
              </div>

              {alert.relatedData.length ? (
                <dl className="space-y-2" data-testid="local-seo-alert-related-data">
                  {alert.relatedData.map((item) => (
                    <div key={item.label} className="grid grid-cols-[140px_1fr] gap-3">
                      <dt className="text-slate-400">{item.label}</dt>
                      <dd>{item.value}</dd>
                    </div>
                  ))}
                </dl>
              ) : null}
            </div>

            <DialogFooter>
              {alert.quickAction ? (
                <Button asChild>
                  <Link href={alert.quickAction.href}>{alert.quickAction.label}</Link>
                </Button>
              ) : null}

              {!alert.isRead ? (
                <Button type="button" variant="outline" onClick={() => onMarkRead?.(alert)}>
                  Mark as read
                </Button>
              ) : null}

              <Button type="button" variant="ghost" onClick={onClose}>
                Close
              </Button>
            </DialogFooter>
          </>
        ) : null}
      </DialogContent>
    </Dialog>
  );
}

export default LocalSeoAlertDetailModal;
