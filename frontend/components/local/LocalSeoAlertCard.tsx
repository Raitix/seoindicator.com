"use client";

import Link from "next/link";
import { CheckCircle2, Circle } from "lucide-react";

import { LocalSeoAlertTypeIcon } from "@/components/local/LocalSeoAlertTypeIcon";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { formatRelativeTime } from "@/lib/location-dashboard";
import { LOCAL_SEO_ALERT_SEVERITY_LABELS, type LocalSeoAlert } from "@/lib/types/location-alerts";

export interface LocalSeoAlertCardProps {
  alert: LocalSeoAlert;
  onOpen?: (alert: LocalSeoAlert) => void;
  onMarkRead?: (alert: LocalSeoAlert) => void | Promise<void>;
}

export function LocalSeoAlertCard({ alert, onOpen, onMarkRead }: LocalSeoAlertCardProps) {
  return (
    <Card data-testid={`local-seo-alert-card-${alert.id}`}>
      <CardContent className="space-y-4 p-4">
        <div className="flex items-start justify-between gap-3">
          <div className="space-y-2">
            <div className="flex items-center gap-2">
              <LocalSeoAlertTypeIcon type={alert.type} />
              <Badge variant="outline">{LOCAL_SEO_ALERT_SEVERITY_LABELS[alert.severity]}</Badge>
              {alert.isRead ? (
                <span className="inline-flex items-center gap-1 text-xs text-slate-500" data-testid="read-indicator">
                  <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" />
                  Read
                </span>
              ) : (
                <span className="inline-flex items-center gap-1 text-xs text-sky-500" data-testid="unread-indicator">
                  <Circle className="h-3.5 w-3.5 fill-current" aria-hidden="true" />
                  Unread
                </span>
              )}
            </div>
            <button
              type="button"
              className="text-left"
              onClick={() => onOpen?.(alert)}
            >
              <h3 className="text-base font-semibold text-slate-100">{alert.title}</h3>
              <p className="mt-1 text-sm text-slate-400">{alert.message}</p>
            </button>
          </div>

          {!alert.isRead ? (
            <Button type="button" variant="outline" size="sm" onClick={() => onMarkRead?.(alert)}>
              Mark as read
            </Button>
          ) : null}
        </div>

        <div className="flex flex-wrap items-center gap-3 text-xs text-slate-400">
          {alert.location ? (
            <Link href={`/local-seo/alerts?location_id=${encodeURIComponent(alert.location.id)}`} className="underline-offset-4 hover:underline">
              {alert.location.name}
            </Link>
          ) : null}
          <span>{formatRelativeTime(alert.createdAt)}</span>
        </div>
      </CardContent>
    </Card>
  );
}

export default LocalSeoAlertCard;
