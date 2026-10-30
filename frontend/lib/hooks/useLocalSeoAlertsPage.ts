"use client";

import { useCallback } from "react";
import useSWR, { mutate as globalMutate } from "swr";

import { buildApiUrl } from "@/lib/api/base-url";
import { getStoredAuthToken } from "@/lib/api/auth";
import {
  defaultLocalSeoAlertFilters,
  type LocalSeoAlert,
  type LocalSeoAlertFilterState,
  type LocalSeoAlertListMeta,
  type LocalSeoAlertListResponse,
  type LocalSeoAlertSeverity,
  type LocalSeoAlertType,
  type LocalSeoUnreadCount,
} from "@/lib/types/location-alerts";

const FALLBACK_META: LocalSeoAlertListMeta = {
  currentPage: 1,
  perPage: 15,
  total: 0,
  lastPage: 1,
};

const asRecord = (value: unknown): Record<string, unknown> => {
  if (value && typeof value === "object" && !Array.isArray(value)) {
    return value as Record<string, unknown>;
  }

  return {};
};

const asArray = <T = unknown>(value: unknown): T[] => (Array.isArray(value) ? (value as T[]) : []);

const asString = (value: unknown, fallback = "") => (typeof value === "string" ? value.trim() : fallback);
const asNullableString = (value: unknown) => {
  const next = asString(value, "");
  return next.length ? next : null;
};

const asNumber = (value: unknown, fallback = 0): number => {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : fallback;
  }

  if (typeof value === "string") {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  return fallback;
};

const asNullableNumber = (value: unknown) => {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const parsed = asNumber(value, Number.NaN);
  return Number.isFinite(parsed) ? parsed : null;
};

const asBoolean = (value: unknown): boolean => {
  if (typeof value === "boolean") {
    return value;
  }

  if (typeof value === "number") {
    return value === 1;
  }

  if (typeof value === "string") {
    return ["1", "true", "yes"].includes(value.toLowerCase());
  }

  return false;
};

const buildRequestHeaders = (headers?: HeadersInit, includeJsonContentType = false) => {
  const nextHeaders: Record<string, string> = Array.isArray(headers)
    ? Object.fromEntries(headers)
    : typeof Headers !== "undefined" && headers instanceof Headers
      ? Object.fromEntries(headers.entries())
      : { ...((headers as Record<string, string> | undefined) ?? {}) };

  if (!nextHeaders.Accept) {
    nextHeaders.Accept = "application/json";
  }

  if (includeJsonContentType && !nextHeaders["Content-Type"]) {
    nextHeaders["Content-Type"] = "application/json";
  }

  const token = getStoredAuthToken();
  if (token && !nextHeaders.Authorization) {
    nextHeaders.Authorization = `Bearer ${token}`;
  }

  return nextHeaders;
};

const buildRequestInit = (init: RequestInit = {}, includeJsonContentType = false): RequestInit => ({
  ...init,
  credentials: "include",
  headers: buildRequestHeaders(init.headers, includeJsonContentType),
});

const normalizeSeverity = (value: unknown): LocalSeoAlertSeverity => {
  const severity = asNullableString(value)?.toLowerCase();
  if (severity === "critical" || severity === "warning") {
    return severity;
  }

  return "info";
};

const normalizeType = (value: unknown): LocalSeoAlertType => {
  const type = asNullableString(value) as LocalSeoAlertType | null;

  switch (type) {
    case "ranking_improved":
    case "ranking_dropped":
    case "new_review":
    case "negative_review":
    case "rating_dropped":
    case "competitor_overtake":
    case "gbp_disconnected":
      return type;
    default:
      return "ranking_dropped";
  }
};

export const normalizeLocalSeoAlert = (payload: unknown): LocalSeoAlert => {
  const record = asRecord(payload);
  const relatedSource = asRecord(record.data ?? record.meta ?? {});
  const location = asRecord(record.location);

  const relatedData = Object.entries(relatedSource)
    .map(([label, value]) => {
      if (value === null || value === undefined || value === "") {
        return null;
      }

      return {
        label,
        value: String(value),
      };
    })
    .filter((item): item is { label: string; value: string } => item !== null);

  return {
    id: String(record.id ?? `alert-${Math.random()}`),
    numericId: asNullableNumber(record.id),
    type: normalizeType(record.type ?? record.alert_type),
    severity: normalizeSeverity(record.severity),
    title: asNullableString(record.title) ?? "Alert triggered",
    message: asNullableString(record.message) ?? "No details available",
    isRead: asBoolean(record.is_read ?? record.isRead),
    readAt: asNullableString(record.read_at ?? record.readAt),
    createdAt: asNullableString(record.created_at ?? record.createdAt) ?? new Date().toISOString(),
    location:
      asNullableString(location.name) || asNullableString(location.id)
        ? {
            id: String(location.id ?? ""),
            name: asNullableString(location.name) ?? "Unknown location",
          }
        : null,
    relatedData,
    quickAction: null,
  };
};

export const normalizeLocalSeoAlertListResponse = (payload: unknown): LocalSeoAlertListResponse => {
  const root = asRecord(payload);
  const meta = asRecord(root.meta ?? root.pagination ?? {});

  return {
    alerts: asArray(root.data ?? root.alerts ?? payload).map(normalizeLocalSeoAlert),
    meta: {
      currentPage: asNumber(meta.current_page ?? meta.currentPage, FALLBACK_META.currentPage),
      perPage: asNumber(meta.per_page ?? meta.perPage, FALLBACK_META.perPage),
      total: asNumber(meta.total, FALLBACK_META.total),
      lastPage: asNumber(meta.last_page ?? meta.lastPage, FALLBACK_META.lastPage),
    },
  };
};

export const normalizeLocalSeoUnreadCount = (payload: unknown): LocalSeoUnreadCount => {
  const root = asRecord(payload);
  const bySeverity = asRecord(root.by_severity ?? root.bySeverity);

  return {
    total: asNumber(root.total),
    bySeverity: {
      info: asNumber(bySeverity.info),
      warning: asNumber(bySeverity.warning),
      critical: asNumber(bySeverity.critical),
    },
  };
};

const fetchJson = async (url: string) => {
  const response = await fetch(url, buildRequestInit());

  if (!response.ok) {
    if (response.status === 401) {
      throw new Error("Please sign in to view local SEO alerts");
    }

    if (response.status === 403) {
      throw new Error("You do not have access to these alerts");
    }

    if (response.status === 404) {
      throw new Error("Alert data not found");
    }

    const body = await response.json().catch(() => null);
    throw new Error(body?.message ?? "Failed to load local SEO alerts");
  }

  return response.json().catch(() => null);
};

export const buildLocalSeoAlertQuery = (
  filters: LocalSeoAlertFilterState,
  page: number,
  perPage: number
) => {
  const query = new URLSearchParams();
  query.set("page", String(page));
  query.set("per_page", String(perPage));

  if (filters.locationId) {
    query.set("location_id", filters.locationId);
  }

  if (filters.severity !== "all") {
    query.set("severity", filters.severity);
  }

  if (filters.type !== "all") {
    query.set("type", filters.type);
  }

  if (filters.readStatus === "read") {
    query.set("is_read", "true");
  }

  if (filters.readStatus === "unread") {
    query.set("is_read", "false");
  }

  return query.toString() ? `?${query.toString()}` : "";
};

const alertsPrefix = buildApiUrl("/local-seo/alerts");
const unreadCountPrefix = buildApiUrl("/local-seo/alerts/unread-count");

export async function revalidateLocalSeoAlerts() {
  await globalMutate(
    (key) => typeof key === "string" && (key.startsWith(alertsPrefix) || key.startsWith(unreadCountPrefix)),
    undefined,
    { revalidate: true }
  );
}

export function useLocalSeoAlertsPage(
  initialFilters: Partial<LocalSeoAlertFilterState> = {},
  page = 1,
  perPage = FALLBACK_META.perPage
) {
  const filters = {
    ...defaultLocalSeoAlertFilters,
    ...initialFilters,
  } satisfies LocalSeoAlertFilterState;

  const listKey = `${buildApiUrl("/local-seo/alerts")}${buildLocalSeoAlertQuery(filters, page, perPage)}`;
  const countKey = buildApiUrl(
    `/local-seo/alerts/unread-count${filters.locationId ? `?location_id=${encodeURIComponent(filters.locationId)}` : ""}`
  );

  const listState = useSWR<LocalSeoAlertListResponse>(
    listKey,
    async (url: string) => normalizeLocalSeoAlertListResponse(await fetchJson(url))
  );

  const countState = useSWR<LocalSeoUnreadCount>(
    countKey,
    async (url: string) => normalizeLocalSeoUnreadCount(await fetchJson(url))
  );

  const markAlertRead = useCallback(async (alertId: string | number) => {
    const response = await fetch(
      buildApiUrl(`/local-seo/alerts/${alertId}/read`),
      buildRequestInit({ method: "POST" })
    );

    if (!response.ok) {
      const body = await response.json().catch(() => null);
      throw new Error(body?.message ?? "Failed to mark alert as read");
    }

    await revalidateLocalSeoAlerts();
    return response.json().catch(() => null);
  }, []);

  const markAllRead = useCallback(async (scope: Partial<LocalSeoAlertFilterState> = {}) => {
    const effectiveFilters = { ...filters, ...scope };
    const query = new URLSearchParams();

    if (effectiveFilters.locationId) {
      query.set("location_id", effectiveFilters.locationId);
    }

    if (effectiveFilters.severity !== "all") {
      query.set("severity", effectiveFilters.severity);
    }

    const suffix = query.toString() ? `?${query.toString()}` : "";
    const response = await fetch(
      buildApiUrl(`/local-seo/alerts/mark-all-read${suffix}`),
      buildRequestInit({ method: "POST" })
    );

    if (!response.ok) {
      const body = await response.json().catch(() => null);
      throw new Error(body?.message ?? "Failed to mark all alerts as read");
    }

    await revalidateLocalSeoAlerts();
    return response.json().catch(() => null);
  }, [filters]);

  const deleteAlert = useCallback(async (alertId: string | number) => {
    const response = await fetch(
      buildApiUrl(`/local-seo/alerts/${alertId}`),
      buildRequestInit({ method: "DELETE" })
    );

    if (!response.ok) {
      const body = await response.json().catch(() => null);
      throw new Error(body?.message ?? "Failed to delete alert");
    }

    await revalidateLocalSeoAlerts();
    return true;
  }, []);

  return {
    alerts: listState.data?.alerts ?? [],
    meta: listState.data?.meta ?? FALLBACK_META,
    unreadCount: countState.data ?? {
      total: 0,
      bySeverity: { info: 0, warning: 0, critical: 0 },
    },
    isLoading: listState.isLoading || countState.isLoading,
    error: listState.error ?? countState.error ?? null,
    mutateAlerts: listState.mutate,
    mutateUnreadCount: countState.mutate,
    markAlertRead,
    markAllRead,
    deleteAlert,
  };
}
