"use client";

import * as React from "react";
import Link from "next/link";
import {
  ArrowLeft,
  Download,
  FileSpreadsheet,
  MapPin,
  Plus,
  RefreshCw,
  Search,
  ShieldAlert,
  Target,
  Trophy,
  Users,
} from "lucide-react";
import { toast } from "sonner";

import RankingHistoryChart, { type RankingHistoryPoint } from "@/components/charts/RankingHistoryChart";
import { RankingsTable, type RankingsTableRowView } from "@/components/local/RankingsTable";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Progress } from "@/components/ui/progress";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { buildLocationDashboardLinks, formatDateTime, formatNumber, formatRating } from "@/lib/location-dashboard";
import { useDashboardLocation } from "@/lib/hooks/useLocationDashboard";
import {
  useCheckAllLocationRankings,
  useCheckSingleLocationRanking,
  useCreateTrackedCompetitor,
  useDeleteTrackedCompetitor,
  useLocationRankingHistory,
  useLocationRankingQuota,
  useLocationRankingsData,
  useLocationTrackedCompetitors,
} from "@/lib/hooks/useLocationRankingsPage";
import type {
  LocationRankingRow,
  PackCompetitor,
  RankingHistorySeries,
  RankingsSortDirection,
  RankingsSortKey,
  TrackedCompetitor,
} from "@/lib/types/location-rankings";
import { cn } from "@/lib/utils";

export interface LocationRankingsPageProps {
  locationId: string;
  siteId?: string | null;
}

type RankingMetric = "pack" | "organic";

interface RankingChangeSet {
  pack: number | null;
  organic: number | null;
  previousPackPosition: number | null;
  previousOrganicPosition: number | null;
}

interface CompetitorComparisonItem {
  id: number;
  name: string;
  googlePlaceId: string | null;
  rating: number | null;
  reviewCount: number | null;
  averagePackPosition: number | null;
  appearances: number;
}

interface AlertHighlight {
  keywordId: number;
  keyword: string;
  change: number;
  previousPackPosition: number | null;
  currentPackPosition: number | null;
  severity: "critical" | "warning";
}

const defaultSort: { key: RankingsSortKey; direction: RankingsSortDirection } = {
  key: "localPackPosition",
  direction: "asc",
};

const normalizeName = (value: string) => value.trim().toLowerCase();

const average = (values: number[]) => {
  if (!values.length) {
    return null;
  }

  return Number((values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(1));
};

const metricToPoints = (series: RankingHistorySeries | undefined, metric: RankingMetric): RankingHistoryPoint[] => {
  if (!series) {
    return [];
  }

  return series.snapshots.map((snapshot) => ({
    date: snapshot.date,
    position: metric === "pack" ? snapshot.localPackPosition : snapshot.localOrganicPosition,
  }));
};

const buildChangeMap = (history: RankingHistorySeries[]) => {
  const next = new Map<number, RankingChangeSet>();

  history.forEach((series) => {
    const snapshots = [...series.snapshots].sort((left, right) => new Date(left.date).getTime() - new Date(right.date).getTime());
    const current = snapshots[snapshots.length - 1] ?? null;
    const previous = snapshots[snapshots.length - 2] ?? null;

    const resolveChange = (previousValue: number | null, currentValue: number | null) => {
      if (previousValue === null || currentValue === null) {
        return null;
      }

      return previousValue - currentValue;
    };

    next.set(series.keywordId, {
      pack: resolveChange(previous?.localPackPosition ?? null, current?.localPackPosition ?? null),
      organic: resolveChange(previous?.localOrganicPosition ?? null, current?.localOrganicPosition ?? null),
      previousPackPosition: previous?.localPackPosition ?? null,
      previousOrganicPosition: previous?.localOrganicPosition ?? null,
    });
  });

  return next;
};

const compareNullableNumbers = (left: number | null, right: number | null, direction: RankingsSortDirection) => {
  if (left === null && right === null) {
    return 0;
  }

  if (left === null) {
    return 1;
  }

  if (right === null) {
    return -1;
  }

  return direction === "asc" ? left - right : right - left;
};

const triggerBrowserDownload = (content: string, mimeType: string, fileName: string) => {
  const blob = new Blob([content], { type: mimeType });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement("a");
  anchor.href = url;
  anchor.download = fileName;
  anchor.click();
  URL.revokeObjectURL(url);
};

const escapeCell = (value: unknown) => `"${`${value ?? ""}`.replace(/"/g, '""')}"`;

const buildCsv = (rows: RankingsTableRowView[]) => {
  const header = ["Keyword", "Local Pack Position", "Organic Position", "Pack Change", "Organic Change", "Last Checked"];
  const records = rows.map((row) => [
    row.keyword,
    row.localPackPosition ?? "",
    row.localOrganicPosition ?? "",
    row.packChange ?? "",
    row.organicChange ?? "",
    row.lastChecked ?? "",
  ]);

  return [header, ...records].map((record) => record.map(escapeCell).join(",")).join("\n");
};

const buildExcelTable = (rows: RankingsTableRowView[]) => {
  const body = rows
    .map(
      (row) =>
        `<tr><td>${row.keyword}</td><td>${row.localPackPosition ?? ""}</td><td>${row.localOrganicPosition ?? ""}</td><td>${row.packChange ?? ""}</td><td>${row.organicChange ?? ""}</td><td>${row.lastChecked ?? ""}</td></tr>`
    )
    .join("");

  return `
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
      <head>
        <meta charset="utf-8" />
      </head>
      <body>
        <table>
          <thead>
            <tr>
              <th>Keyword</th>
              <th>Local Pack Position</th>
              <th>Organic Position</th>
              <th>Pack Change</th>
              <th>Organic Change</th>
              <th>Last Checked</th>
            </tr>
          </thead>
          <tbody>${body}</tbody>
        </table>
      </body>
    </html>`;
};

const getPackTone = (position: number | null) => {
  if (position !== null && position > 0 && position <= 3) {
    return "border-emerald-500/30 bg-emerald-500/10 text-emerald-100";
  }

  return "border-slate-700 bg-slate-900 text-slate-300";
};

const buildCompetitorComparison = (
  trackedCompetitors: TrackedCompetitor[],
  rankings: LocationRankingRow[]
): CompetitorComparisonItem[] => {
  const appearances = new Map<string, number[]>();

  rankings.forEach((row) => {
    row.competitorsInPack.forEach((competitor) => {
      const key = normalizeName(competitor.name);
      const current = appearances.get(key) ?? [];
      current.push(competitor.position);
      appearances.set(key, current);
    });
  });

  return trackedCompetitors.map((competitor) => {
    const positions = appearances.get(normalizeName(competitor.name)) ?? [];

    return {
      id: competitor.id,
      name: competitor.name,
      googlePlaceId: competitor.googlePlaceId,
      rating: competitor.rating,
      reviewCount: competitor.reviewCount,
      averagePackPosition: average(positions),
      appearances: positions.length,
    } satisfies CompetitorComparisonItem;
  });
};

const buildAlertHighlights = (
  rankings: LocationRankingRow[],
  changeMap: Map<number, RankingChangeSet>
): AlertHighlight[] => {
  return rankings
    .map((ranking) => {
      const changeSet = changeMap.get(ranking.keywordId);
      const change = changeSet?.pack ?? null;

      if (change === null || Math.abs(change) < 2) {
        return null;
      }

      return {
        keywordId: ranking.keywordId,
        keyword: ranking.keyword,
        change,
        previousPackPosition: changeSet?.previousPackPosition ?? null,
        currentPackPosition: ranking.localPackPosition,
        severity: Math.abs(change) >= 5 ? "critical" : "warning",
      } satisfies AlertHighlight;
    })
    .filter((item): item is AlertHighlight => item !== null)
    .sort((left, right) => Math.abs(right.change) - Math.abs(left.change))
    .slice(0, 4);
};

function PageSkeleton() {
  return (
    <div className="space-y-6">
      <Skeleton className="h-40 w-full rounded-3xl bg-slate-800" />
      <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <Skeleton className="h-[240px] w-full rounded-3xl bg-slate-800" />
        <Skeleton className="h-[240px] w-full rounded-3xl bg-slate-800" />
      </div>
      <Skeleton className="h-[420px] w-full rounded-3xl bg-slate-800" />
      <div className="grid gap-6 xl:grid-cols-2">
        <Skeleton className="h-[320px] w-full rounded-3xl bg-slate-800" />
        <Skeleton className="h-[320px] w-full rounded-3xl bg-slate-800" />
      </div>
    </div>
  );
}

function QuotaIndicator({
  used,
  limit,
  resetDate,
  disabled,
}: {
  used: number;
  limit: number;
  resetDate: string | null;
  disabled?: boolean;
}) {
  const percentage = limit > 0 ? Math.round((used / limit) * 100) : 0;

  return (
    <Card className="border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10">
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-white">
          <Target className="h-5 w-5 text-sky-300" aria-hidden="true" />
          Quota usage
        </CardTitle>
        <CardDescription className="text-slate-400">
          Free local rank checks reset every month.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex items-end justify-between gap-4">
          <div>
            <div className="text-3xl font-semibold text-white">{used} / {limit}</div>
            <div className="mt-1 text-sm text-slate-400">Checks used this month</div>
          </div>
          <Badge className={cn("border", disabled ? "border-red-500/30 bg-red-500/10 text-red-100" : "border-emerald-500/30 bg-emerald-500/10 text-emerald-100")}>
            {disabled ? "Quota exhausted" : `${Math.max(limit - used, 0)} remaining`}
          </Badge>
        </div>
        <Progress value={percentage} className="h-2 bg-slate-800" />
        <div className="text-xs text-slate-500">Resets on {formatDateTime(resetDate)}</div>
      </CardContent>
    </Card>
  );
}

function LocalPackVisualization({
  locationName,
  keyword,
  packPosition,
  competitors,
}: {
  locationName: string;
  keyword: string | null;
  packPosition: number | null;
  competitors: PackCompetitor[];
}) {
  const slots = [1, 2, 3].map((position) => {
    const match = competitors.find((competitor) => competitor.position === position) ?? null;
    const isLocation = packPosition === position && match ? normalizeName(match.name) === normalizeName(locationName) : packPosition === position;

    return {
      position,
      label: isLocation ? locationName : match?.name ?? "Open slot",
      rating: match?.rating ?? null,
      reviewCount: match?.reviewCount ?? null,
      isLocation,
    };
  });

  return (
    <Card className="border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10">
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-white">
          <Trophy className="h-5 w-5 text-amber-300" aria-hidden="true" />
          Local pack snapshot
        </CardTitle>
        <CardDescription className="text-slate-400">
          {keyword ? `Current three-pack for “${keyword}”.` : "Select a keyword to inspect current local-pack positions."}
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="grid gap-3 md:grid-cols-3">
          {slots.map((slot) => (
            <div key={slot.position} className={cn("rounded-2xl border p-4", slot.isLocation ? "border-emerald-500/30 bg-emerald-500/10" : "border-slate-800 bg-slate-900/70") }>
              <div className="flex items-center justify-between gap-3">
                <span className="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-xs font-medium text-slate-300">#{slot.position}</span>
                {slot.isLocation ? <Badge className="border-emerald-500/30 bg-emerald-500/15 text-emerald-100">Your location</Badge> : null}
              </div>
              <div className="mt-4 font-medium text-white">{slot.label}</div>
              {slot.rating !== null || slot.reviewCount !== null ? (
                <div className="mt-2 text-xs text-slate-400">
                  {slot.rating !== null ? `${formatRating(slot.rating)} ★` : "—"}
                  {slot.reviewCount !== null ? ` • ${formatNumber(slot.reviewCount)} reviews` : ""}
                </div>
              ) : (
                <div className="mt-2 text-xs text-slate-500">Waiting for a fresh pack capture.</div>
              )}
            </div>
          ))}
        </div>
        <div className="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3 text-sm text-slate-400">
          {packPosition && packPosition <= 3
            ? `${locationName} is currently in the local pack at #${packPosition}.`
            : `${locationName} is not currently inside the top 3 local-pack positions for this keyword.`}
        </div>
      </CardContent>
    </Card>
  );
}

function AddCompetitorDialog({
  open,
  isSubmitting,
  onClose,
  onSubmit,
}: {
  open: boolean;
  isSubmitting: boolean;
  onClose: () => void;
  onSubmit: (input: { name: string; googlePlaceId: string; notes: string }) => Promise<void>;
}) {
  const [name, setName] = React.useState("");
  const [googlePlaceId, setGooglePlaceId] = React.useState("");
  const [notes, setNotes] = React.useState("");

  React.useEffect(() => {
    if (!open) {
      setName("");
      setGooglePlaceId("");
      setNotes("");
    }
  }, [open]);

  return (
    <Dialog open={open} onOpenChange={(next) => (!next ? onClose() : undefined)}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add tracked competitor</DialogTitle>
          <DialogDescription>
            Save a Google Business Profile competitor to compare its pack visibility, rating, and review volume on this page.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="competitor-name">Competitor name</Label>
            <Input id="competitor-name" value={name} onChange={(event) => setName(event.target.value)} placeholder="Joe's Pizza" />
          </div>
          <div className="space-y-2">
            <Label htmlFor="competitor-place-id">Google Place ID</Label>
            <Input id="competitor-place-id" value={googlePlaceId} onChange={(event) => setGooglePlaceId(event.target.value)} placeholder="ChIJabc123xyz" />
          </div>
          <div className="space-y-2">
            <Label htmlFor="competitor-notes">Notes (optional)</Label>
            <Input id="competitor-notes" value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Main city-center competitor" />
          </div>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={onClose}>
            Cancel
          </Button>
          <Button
            type="button"
            disabled={isSubmitting || !name.trim() || !googlePlaceId.trim()}
            onClick={() => void onSubmit({ name: name.trim(), googlePlaceId: googlePlaceId.trim(), notes: notes.trim() })}
          >
            {isSubmitting ? "Adding…" : "Add competitor"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export function LocationRankingsPage({ locationId, siteId }: LocationRankingsPageProps) {
  const { location, error: locationError, isLoading: locationLoading, mutate: mutateLocation } = useDashboardLocation(locationId);
  const { rankings, error: rankingsError, isLoading: rankingsLoading, mutate: mutateRankings } = useLocationRankingsData(locationId);
  const { history, error: historyError, isLoading: historyLoading, mutate: mutateHistory } = useLocationRankingHistory(locationId);
  const { quota, error: quotaError, isLoading: quotaLoading, mutate: mutateQuota } = useLocationRankingQuota(locationId);
  const { competitors, error: competitorsError, isLoading: competitorsLoading, mutate: mutateCompetitors } = useLocationTrackedCompetitors(locationId);
  const { checkAll } = useCheckAllLocationRankings(locationId);
  const { checkKeyword } = useCheckSingleLocationRanking(locationId);
  const { createCompetitor } = useCreateTrackedCompetitor(locationId);
  const { deleteCompetitor } = useDeleteTrackedCompetitor(locationId);

  const [query, setQuery] = React.useState("");
  const [sortKey, setSortKey] = React.useState<RankingsSortKey>(defaultSort.key);
  const [sortDirection, setSortDirection] = React.useState<RankingsSortDirection>(defaultSort.direction);
  const [selectedKeywordId, setSelectedKeywordId] = React.useState<number | null>(null);
  const [selectedMetric, setSelectedMetric] = React.useState<RankingMetric>("pack");
  const [isCheckingAll, setIsCheckingAll] = React.useState(false);
  const [checkingKeywordId, setCheckingKeywordId] = React.useState<number | null>(null);
  const [showAddCompetitor, setShowAddCompetitor] = React.useState(false);
  const [isAddingCompetitor, setIsAddingCompetitor] = React.useState(false);
  const [deletingCompetitorId, setDeletingCompetitorId] = React.useState<number | null>(null);
  const [actionError, setActionError] = React.useState<string | null>(null);

  const resolvedSiteId = siteId ?? (location?.siteId !== null && location?.siteId !== undefined ? String(location.siteId) : undefined);
  const links = buildLocationDashboardLinks(locationId, resolvedSiteId);

  const changeMap = React.useMemo(() => buildChangeMap(history), [history]);

  const filteredRows = React.useMemo<RankingsTableRowView[]>(() => {
    const normalizedQuery = normalizeName(query);

    const rows = rankings
      .filter((ranking) => !normalizedQuery || normalizeName(ranking.keyword).includes(normalizedQuery))
      .map((ranking) => {
        const changeSet = changeMap.get(ranking.keywordId);

        return {
          keywordId: ranking.keywordId,
          keyword: ranking.keyword,
          localPackPosition: ranking.localPackPosition,
          localOrganicPosition: ranking.localOrganicPosition,
          packChange: changeSet?.pack ?? null,
          organicChange: changeSet?.organic ?? null,
          lastChecked: ranking.lastChecked,
          competitorsInPackCount: ranking.competitorsInPack.length,
        } satisfies RankingsTableRowView;
      });

    const compareRows = (left: RankingsTableRowView, right: RankingsTableRowView) => {
      switch (sortKey) {
        case "keyword":
          return sortDirection === "asc"
            ? left.keyword.localeCompare(right.keyword)
            : right.keyword.localeCompare(left.keyword);
        case "packChange":
          return compareNullableNumbers(left.packChange, right.packChange, sortDirection);
        case "organicChange":
          return compareNullableNumbers(left.organicChange, right.organicChange, sortDirection);
        case "localPackPosition":
          return compareNullableNumbers(left.localPackPosition, right.localPackPosition, sortDirection);
        case "localOrganicPosition":
          return compareNullableNumbers(left.localOrganicPosition, right.localOrganicPosition, sortDirection);
        case "lastChecked": {
          const leftValue = left.lastChecked ? new Date(left.lastChecked).getTime() : null;
          const rightValue = right.lastChecked ? new Date(right.lastChecked).getTime() : null;
          return compareNullableNumbers(leftValue, rightValue, sortDirection);
        }
        default:
          return 0;
      }
    };

    return [...rows].sort(compareRows);
  }, [changeMap, query, rankings, sortDirection, sortKey]);

  React.useEffect(() => {
    if (!filteredRows.length) {
      setSelectedKeywordId(null);
      return;
    }

    const exists = selectedKeywordId !== null && filteredRows.some((row) => row.keywordId === selectedKeywordId);

    if (!exists) {
      setSelectedKeywordId(filteredRows[0]?.keywordId ?? null);
    }
  }, [filteredRows, selectedKeywordId]);

  const selectedRanking = React.useMemo(
    () => rankings.find((ranking) => ranking.keywordId === selectedKeywordId) ?? null,
    [rankings, selectedKeywordId]
  );

  const selectedHistory = React.useMemo(
    () => history.find((item) => item.keywordId === selectedKeywordId),
    [history, selectedKeywordId]
  );

  const chartPoints = React.useMemo(
    () => metricToPoints(selectedHistory, selectedMetric),
    [selectedHistory, selectedMetric]
  );

  const alertHighlights = React.useMemo(
    () => buildAlertHighlights(rankings, changeMap),
    [changeMap, rankings]
  );

  const competitorComparison = React.useMemo(
    () => buildCompetitorComparison(competitors, rankings),
    [competitors, rankings]
  );

  const retryAll = React.useCallback(async () => {
    await Promise.all([
      mutateLocation(),
      mutateRankings(),
      mutateHistory(),
      mutateQuota(),
      mutateCompetitors(),
    ]);
  }, [mutateCompetitors, mutateHistory, mutateLocation, mutateQuota, mutateRankings]);

  const handleSortChange = React.useCallback((nextKey: RankingsSortKey) => {
    setSortDirection((currentDirection) => {
      if (sortKey === nextKey) {
        return currentDirection === "asc" ? "desc" : "asc";
      }

      return nextKey === "keyword" ? "asc" : "asc";
    });
    setSortKey(nextKey);
  }, [sortKey]);

  const handleCheckAll = React.useCallback(async () => {
    setActionError(null);
    setIsCheckingAll(true);

    try {
      const payload = await checkAll();
      await Promise.all([mutateQuota(), mutateRankings(), mutateHistory()]);
      toast.success(`Ranking check queued for ${payload?.data?.keywords_queued ?? 0} keyword(s)`);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Failed to start ranking check";
      setActionError(message);
      toast.error(message);
    } finally {
      setIsCheckingAll(false);
    }
  }, [checkAll, mutateHistory, mutateQuota, mutateRankings]);

  const handleCheckKeyword = React.useCallback(async (keywordId: number) => {
    setActionError(null);
    setCheckingKeywordId(keywordId);

    try {
      await checkKeyword(keywordId);
      await Promise.all([mutateQuota(), mutateRankings(), mutateHistory()]);
      toast.success("Keyword ranking check queued");
    } catch (error) {
      const message = error instanceof Error ? error.message : "Failed to check keyword ranking";
      setActionError(message);
      toast.error(message);
    } finally {
      setCheckingKeywordId(null);
    }
  }, [checkKeyword, mutateHistory, mutateQuota, mutateRankings]);

  const handleAddCompetitor = React.useCallback(async (input: { name: string; googlePlaceId: string; notes: string }) => {
    setIsAddingCompetitor(true);

    try {
      await createCompetitor(input);
      toast.success("Competitor added to tracking");
      setShowAddCompetitor(false);
      await mutateCompetitors();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Failed to add competitor");
    } finally {
      setIsAddingCompetitor(false);
    }
  }, [createCompetitor, mutateCompetitors]);

  const handleDeleteCompetitor = React.useCallback(async (competitorId: number) => {
    setDeletingCompetitorId(competitorId);

    try {
      await deleteCompetitor(competitorId);
      toast.success("Competitor removed");
      await mutateCompetitors();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Failed to remove competitor");
    } finally {
      setDeletingCompetitorId(null);
    }
  }, [deleteCompetitor, mutateCompetitors]);

  const handleExportCsv = React.useCallback(() => {
    triggerBrowserDownload(buildCsv(filteredRows), "text/csv;charset=utf-8;", `location-${locationId}-rankings.csv`);
    toast.success("Rankings exported as CSV");
  }, [filteredRows, locationId]);

  const handleExportExcel = React.useCallback(() => {
    triggerBrowserDownload(buildExcelTable(filteredRows), "application/vnd.ms-excel;charset=utf-8;", `location-${locationId}-rankings.xls`);
    toast.success("Rankings exported for Excel");
  }, [filteredRows, locationId]);

  if (locationLoading && !location) {
    return <PageSkeleton />;
  }

  if (locationError && !location) {
    return (
      <Card className="border-red-500/20 bg-red-500/10 text-red-50">
        <CardContent className="space-y-4 px-6 py-8">
          <div className="text-2xl font-semibold">Unable to load the rankings workspace</div>
          <p className="text-sm text-red-100/90">{locationError.message}</p>
          <Button type="button" variant="outline" onClick={() => void mutateLocation()}>
            Try again
          </Button>
        </CardContent>
      </Card>
    );
  }

  if (!location) {
    return <PageSkeleton />;
  }

  const quotaUsed = quota?.usedThisMonth ?? 0;
  const quotaLimit = quota?.monthlyLimit ?? 50;
  const quotaExhausted = quota !== null ? quota.remaining <= 0 : false;

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-950 via-slate-950 to-slate-900 p-6 shadow-2xl shadow-slate-950/20">
        <div className="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
          <div className="space-y-4">
            <nav className="flex flex-wrap items-center gap-2 text-sm text-slate-400" aria-label="Breadcrumb">
              {resolvedSiteId ? (
                <>
                  <Link href="/sites" className="transition hover:text-slate-200">Sites</Link>
                  <span>/</span>
                  <Link href={`/sites/${resolvedSiteId}`} className="transition hover:text-slate-200">Site #{resolvedSiteId}</Link>
                  <span>/</span>
                  <Link href={`/sites/${resolvedSiteId}/local`} className="transition hover:text-slate-200">Local locations</Link>
                </>
              ) : (
                <Link href="/locations" className="transition hover:text-slate-200">Locations</Link>
              )}
              <span>/</span>
              <Link href={links.dashboard} className="transition hover:text-slate-200">{location.name}</Link>
              <span>/</span>
              <span aria-current="page" className="text-slate-200">Rankings</span>
            </nav>

            <div className="space-y-3">
              <div className="flex flex-wrap items-center gap-3">
                <h1 className="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Local rankings tracker</h1>
                <Badge className={location.isGbpConnected ? "border-emerald-500/30 bg-emerald-500/15 text-emerald-200" : "border-slate-700 bg-slate-900 text-slate-300"}>
                  {location.isGbpConnected ? "GBP connected" : "GBP not connected"}
                </Badge>
              </div>
              <p className="max-w-3xl text-sm leading-6 text-slate-400">
                Monitor current local-pack and organic positions, inspect ranking history, compare tracked competitors, and queue fresh checks for this location.
              </p>
              <div className="flex flex-wrap items-center gap-3 text-sm text-slate-400">
                <span className="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5">
                  <MapPin className="h-4 w-4 text-slate-500" aria-hidden="true" />
                  {location.name}
                </span>
                <span className="rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5">
                  Last synced: <span className="ml-1 text-slate-200">{formatDateTime(location.lastSyncAt)}</span>
                </span>
              </div>
            </div>
          </div>

          <div className="flex w-full flex-col gap-3 sm:w-auto sm:min-w-[220px]">
            <Button type="button" variant="outline" className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" asChild>
              <Link href={links.dashboard}>
                <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                Back to dashboard
              </Link>
            </Button>
            <Button type="button" onClick={() => void handleCheckAll()} disabled={!location.isGbpConnected || quotaExhausted || isCheckingAll}>
              <RefreshCw className={cn("h-4 w-4", isCheckingAll ? "animate-spin" : undefined)} aria-hidden="true" />
              {isCheckingAll ? "Checking all…" : "Check all rankings"}
            </Button>
          </div>
        </div>
      </section>

      {!location.isGbpConnected ? (
        <Card className="border-amber-500/20 bg-amber-500/10 text-amber-50">
          <CardContent className="px-6 py-5 text-sm text-amber-100/90">
            This location is not connected to Google Business Profile yet. Connect it first to track local-pack performance and competitor visibility.
          </CardContent>
        </Card>
      ) : null}

      {actionError ? (
        <Card className="border-red-500/20 bg-red-500/10 text-red-50">
          <CardContent className="px-6 py-5 text-sm text-red-100/90">
            {actionError}
          </CardContent>
        </Card>
      ) : null}

      <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        {quotaLoading ? (
          <Skeleton className="h-[220px] w-full rounded-3xl bg-slate-800" />
        ) : quotaError ? (
          <Card className="border-red-500/20 bg-red-500/10 text-red-50">
            <CardContent className="space-y-4 px-6 py-8">
              <div className="text-lg font-semibold">Unable to load quota usage</div>
              <p className="text-sm text-red-100/90">{quotaError.message}</p>
              <Button type="button" variant="outline" onClick={() => void mutateQuota()}>
                Try again
              </Button>
            </CardContent>
          </Card>
        ) : (
          <QuotaIndicator used={quotaUsed} limit={quotaLimit} resetDate={quota?.resetDate ?? null} disabled={quotaExhausted} />
        )}

        <Card className="border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-white">
              <ShieldAlert className="h-5 w-5 text-fuchsia-300" aria-hidden="true" />
              Position change alerts
            </CardTitle>
            <CardDescription className="text-slate-400">
              Keywords with the biggest recent local-pack movement.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {historyLoading && !history.length ? (
              <>
                <Skeleton className="h-16 w-full bg-slate-800" />
                <Skeleton className="h-16 w-full bg-slate-800" />
                <Skeleton className="h-16 w-full bg-slate-800" />
              </>
            ) : historyError ? (
              <div className="rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-100/90" role="alert">
                {historyError.message}
              </div>
            ) : alertHighlights.length ? (
              alertHighlights.map((alert) => (
                <Link
                  key={alert.keywordId}
                  href={links.alerts}
                  className={cn(
                    "block rounded-2xl border p-4 transition hover:border-slate-700 hover:bg-slate-900/80",
                    alert.severity === "critical"
                      ? "border-red-500/20 bg-red-500/10"
                      : "border-amber-500/20 bg-amber-500/10"
                  )}
                >
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <div className="font-medium text-white">{alert.keyword}</div>
                      <div className="mt-1 text-sm text-slate-300">
                        {alert.previousPackPosition ? `#${alert.previousPackPosition}` : "Not found"} → {alert.currentPackPosition ? `#${alert.currentPackPosition}` : "Not found"}
                      </div>
                    </div>
                    <Badge className={alert.severity === "critical" ? "border-red-500/30 bg-red-500/15 text-red-100" : "border-amber-500/30 bg-amber-500/15 text-amber-100"}>
                      {alert.change > 0 ? `+${alert.change}` : alert.change}
                    </Badge>
                  </div>
                </Link>
              ))
            ) : (
              <div className="rounded-2xl border border-dashed border-slate-800 bg-slate-900/60 px-4 py-8 text-center text-sm text-slate-400">
                No significant ranking movement detected yet.
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="rounded-3xl border border-slate-800 bg-slate-950/70 p-4 shadow-lg shadow-slate-950/10 sm:p-6">
        <div className="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div className="relative w-full max-w-md">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" aria-hidden="true" />
            <Input
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder="Search tracked keywords"
              className="border-slate-700 bg-slate-900 pl-10 text-slate-100 placeholder:text-slate-500"
              aria-label="Search tracked keywords"
            />
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <Button type="button" variant="outline" className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" onClick={handleExportCsv} disabled={!filteredRows.length}>
              <Download className="h-4 w-4" aria-hidden="true" />
              Export CSV
            </Button>
            <Button type="button" variant="outline" className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" onClick={handleExportExcel} disabled={!filteredRows.length}>
              <FileSpreadsheet className="h-4 w-4" aria-hidden="true" />
              Export Excel
            </Button>
          </div>
        </div>

        <RankingsTable
          rows={filteredRows}
          selectedKeywordId={selectedKeywordId}
          sortKey={sortKey}
          sortDirection={sortDirection}
          isLoading={rankingsLoading && !rankings.length}
          error={rankingsError ?? null}
          checkingKeywordId={checkingKeywordId}
          onSelectKeyword={setSelectedKeywordId}
          onSortChange={handleSortChange}
          onRetry={() => void retryAll()}
          onCheckKeyword={(keywordId) => void handleCheckKeyword(keywordId)}
        />
      </div>

      <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <Card className="border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10">
          <CardHeader>
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <CardTitle className="text-white">Ranking history</CardTitle>
                <CardDescription className="text-slate-400">
                  Compare local-pack and organic movement for the selected keyword.
                </CardDescription>
              </div>
              <Tabs value={selectedMetric} onValueChange={(value) => setSelectedMetric(value as RankingMetric)}>
                <TabsList variant="default" className="bg-slate-900/80">
                  <TabsTrigger value="pack">Local pack</TabsTrigger>
                  <TabsTrigger value="organic">Organic</TabsTrigger>
                </TabsList>
              </Tabs>
            </div>
          </CardHeader>
          <CardContent>
            <RankingHistoryChart
              keywordName={selectedRanking?.keyword ?? "Keyword"}
              data={chartPoints}
              isLoading={historyLoading && !chartPoints.length}
              initialRange="30d"
            />
          </CardContent>
        </Card>

        <LocalPackVisualization
          locationName={location.name}
          keyword={selectedRanking?.keyword ?? null}
          packPosition={selectedRanking?.localPackPosition ?? null}
          competitors={selectedRanking?.competitorsInPack ?? []}
        />
      </div>

      <Card className="border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10">
        <CardHeader>
          <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
              <CardTitle className="flex items-center gap-2 text-white">
                <Users className="h-5 w-5 text-fuchsia-300" aria-hidden="true" />
                Competitor comparison
              </CardTitle>
              <CardDescription className="text-slate-400">
                Track saved competitors side-by-side with current average local-pack visibility.
              </CardDescription>
            </div>
            <Button type="button" onClick={() => setShowAddCompetitor(true)}>
              <Plus className="h-4 w-4" aria-hidden="true" />
              Add competitor
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {competitorsLoading && !competitors.length ? (
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              <Skeleton className="h-40 w-full rounded-3xl bg-slate-800" />
              <Skeleton className="h-40 w-full rounded-3xl bg-slate-800" />
              <Skeleton className="h-40 w-full rounded-3xl bg-slate-800" />
            </div>
          ) : competitorsError ? (
            <div className="rounded-2xl border border-red-500/20 bg-red-500/10 p-5 text-sm text-red-100/90" role="alert">
              {competitorsError.message}
            </div>
          ) : competitorComparison.length ? (
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              {competitorComparison.map((competitor) => (
                <div key={competitor.id} className="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <div className="font-medium text-white">{competitor.name}</div>
                      <div className="mt-2 text-sm text-slate-400">
                        {competitor.averagePackPosition !== null
                          ? `Average pack position #${competitor.averagePackPosition}`
                          : "Not seen in recent local-pack snapshots"}
                      </div>
                    </div>
                    <Badge className={cn("border", getPackTone(competitor.averagePackPosition))}>
                      {competitor.averagePackPosition !== null ? `#${competitor.averagePackPosition}` : "Not found"}
                    </Badge>
                  </div>
                  <div className="mt-4 flex flex-wrap gap-3 text-sm text-slate-400">
                    <span>{competitor.rating !== null ? `${formatRating(competitor.rating)} ★` : "No rating"}</span>
                    <span>{competitor.reviewCount !== null ? `${formatNumber(competitor.reviewCount)} reviews` : "No review count"}</span>
                    <span>{competitor.appearances} pack appearance{competitor.appearances === 1 ? "" : "s"}</span>
                  </div>
                  <div className="mt-4">
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      className="text-slate-300 hover:bg-slate-800 hover:text-white"
                      disabled={deletingCompetitorId === competitor.id}
                      onClick={() => void handleDeleteCompetitor(competitor.id)}
                    >
                      {deletingCompetitorId === competitor.id ? "Removing…" : "Remove"}
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="rounded-2xl border border-dashed border-slate-800 bg-slate-900/60 px-4 py-10 text-center text-sm text-slate-400">
              No tracked competitors yet. Add competitors to compare their local-pack visibility against this location.
            </div>
          )}
        </CardContent>
      </Card>

      <AddCompetitorDialog
        open={showAddCompetitor}
        isSubmitting={isAddingCompetitor}
        onClose={() => setShowAddCompetitor(false)}
        onSubmit={handleAddCompetitor}
      />
    </div>
  );
}

export default LocationRankingsPage;
