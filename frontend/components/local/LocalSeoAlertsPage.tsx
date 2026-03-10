"use client";

import * as React from "react";
import Link from "next/link";
import { Bell, CheckCheck, RefreshCw } from "lucide-react";
import { toast } from "sonner";

import { LocalSeoAlertCard } from "@/components/local/LocalSeoAlertCard";
import { LocalSeoAlertDetailModal } from "@/components/local/LocalSeoAlertDetailModal";
import { LocalSeoUnreadCountBadge } from "@/components/local/LocalSeoUnreadCountBadge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { buildLocationDashboardLinks } from "@/lib/location-dashboard";
import { useLocalSeoAlertsPage } from "@/lib/hooks/useLocalSeoAlertsPage";
import {
  defaultLocalSeoAlertFilters,
  LOCAL_SEO_ALERT_TYPE_CONFIG,
  type LocalSeoAlert,
  type LocalSeoAlertFilterState,
} from "@/lib/types/location-alerts";

export interface LocalSeoAlertsPageProps {
  locationId?: string;
  siteId?: string;
}

export function LocalSeoAlertsPage({ locationId, siteId }: LocalSeoAlertsPageProps) {
  const [filters, setFilters] = React.useState<LocalSeoAlertFilterState>({
    ...defaultLocalSeoAlertFilters,
    locationId: locationId ?? "",
  });
  const [page] = React.useState(1);
  const [selectedAlert, setSelectedAlert] = React.useState<LocalSeoAlert | null>(null);

  const {
    alerts,
    meta,
    unreadCount,
    isLoading,
    error,
    mutateAlerts,
    mutateUnreadCount,
    markAlertRead,
    markAllRead,
  } = useLocalSeoAlertsPage(filters, page);

  const links = locationId ? buildLocationDashboardLinks(locationId, siteId) : null;

  const handleMarkRead = React.useCallback(
    async (alert: LocalSeoAlert) => {
      await markAlertRead(alert.id);
      toast.success("Alert marked as read");
    },
    [markAlertRead]
  );

  const handleMarkAllRead = React.useCallback(async () => {
    const response = await markAllRead();
    toast.success(`Marked ${response?.count ?? 0} alert(s) as read`);
  }, [markAllRead]);

  return (
    <div className="space-y-6" data-testid="local-seo-alerts-page">
      <section className="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-950 via-slate-950 to-slate-900 p-6 shadow-2xl shadow-slate-950/20">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div className="space-y-3">
            <div className="flex items-center gap-3">
              <Bell className="h-6 w-6 text-sky-300" aria-hidden="true" />
              <h1 className="text-3xl font-semibold tracking-tight text-white">Local SEO alerts</h1>
            </div>
            <p className="max-w-3xl text-sm leading-6 text-slate-400">
              Review alert activity across your tracked locations, filter unread issues, and jump into the affected workspace.
            </p>
            {links ? (
              <div>
                <Link href={links.dashboard} className="text-sm text-sky-300 underline-offset-4 hover:underline">
                  Back to dashboard
                </Link>
              </div>
            ) : null}
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <LocalSeoUnreadCountBadge unreadCount={unreadCount} hideWhenZero={false} />
            <Button type="button" variant="outline" onClick={() => { void mutateAlerts(); void mutateUnreadCount(); }}>
              <RefreshCw className="mr-2 h-4 w-4" aria-hidden="true" />
              Refresh
            </Button>
            <Button type="button" onClick={() => void handleMarkAllRead()}>
              <CheckCheck className="mr-2 h-4 w-4" aria-hidden="true" />
              Mark all as read
            </Button>
          </div>
        </div>
      </section>

      <Card>
        <CardContent className="space-y-4 p-4">
          <div className="grid gap-4 md:grid-cols-4">
            <label className="space-y-2 text-sm text-slate-300">
              <span>Severity</span>
              <select
                aria-label="Filter alerts by severity"
                className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2"
                value={filters.severity}
                onChange={(event) => setFilters((current) => ({ ...current, severity: event.target.value as LocalSeoAlertFilterState["severity"] }))}
              >
                <option value="all">All severities</option>
                <option value="info">Info</option>
                <option value="warning">Warning</option>
                <option value="critical">Critical</option>
              </select>
            </label>

            <label className="space-y-2 text-sm text-slate-300">
              <span>Type</span>
              <select
                aria-label="Filter alerts by type"
                className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2"
                value={filters.type}
                onChange={(event) => setFilters((current) => ({ ...current, type: event.target.value as LocalSeoAlertFilterState["type"] }))}
              >
                <option value="all">All types</option>
                {Object.entries(LOCAL_SEO_ALERT_TYPE_CONFIG).map(([type, config]) => (
                  <option key={type} value={type}>
                    {config.label}
                  </option>
                ))}
              </select>
            </label>

            <label className="space-y-2 text-sm text-slate-300">
              <span>Status</span>
              <select
                aria-label="Filter alerts by read status"
                className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2"
                value={filters.readStatus}
                onChange={(event) => setFilters((current) => ({ ...current, readStatus: event.target.value as LocalSeoAlertFilterState["readStatus"] }))}
              >
                <option value="all">All alerts</option>
                <option value="unread">Unread</option>
                <option value="read">Read</option>
              </select>
            </label>

            <label className="space-y-2 text-sm text-slate-300">
              <span>Sort</span>
              <select
                aria-label="Sort alerts"
                className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2"
                value={filters.sort}
                onChange={(event) => setFilters((current) => ({ ...current, sort: event.target.value as LocalSeoAlertFilterState["sort"] }))}
              >
                <option value="-created_at">Newest first</option>
                <option value="created_at">Oldest first</option>
              </select>
            </label>
          </div>
        </CardContent>
      </Card>

      {error ? (
        <Card>
          <CardContent className="p-6 text-sm text-red-300" role="alert">
            {error.message}
          </CardContent>
        </Card>
      ) : null}

      {isLoading ? (
        <Card>
          <CardContent className="p-6 text-sm text-slate-400">Loading local SEO alerts…</CardContent>
        </Card>
      ) : null}

      {!isLoading && !alerts.length ? (
        <Card>
          <CardContent className="p-6 text-sm text-slate-400">No alerts match the current filters.</CardContent>
        </Card>
      ) : null}

      <div className="space-y-4">
        {alerts.map((alert) => (
          <LocalSeoAlertCard
            key={alert.id}
            alert={alert}
            onOpen={setSelectedAlert}
            onMarkRead={(nextAlert) => void handleMarkRead(nextAlert)}
          />
        ))}
      </div>

      <Card>
        <CardContent className="flex items-center justify-between p-4 text-sm text-slate-400" data-testid="local-seo-alerts-pagination">
          <span>
            Page {meta.currentPage} of {meta.lastPage}
          </span>
          <span>{meta.total} alert(s)</span>
        </CardContent>
      </Card>

      <LocalSeoAlertDetailModal
        alert={selectedAlert}
        isOpen={selectedAlert !== null}
        onClose={() => setSelectedAlert(null)}
        onMarkRead={(alert) => void handleMarkRead(alert)}
      />
    </div>
  );
}

export default LocalSeoAlertsPage;
