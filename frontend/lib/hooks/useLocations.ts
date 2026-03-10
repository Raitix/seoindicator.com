"use client";

import { useCallback } from "react";
import useSWR, { mutate as globalMutate } from "swr";

import { buildApiUrl } from "@/lib/api/base-url";
import { getStoredAuthToken } from "@/lib/api/auth";
import {
  defaultLocationMeta,
  normalizeLocation,
  normalizeLocationListResponse,
  type BusinessLocation,
  type LocationListResponse,
  type LocationMutationPayload,
} from "@/lib/types/locations";

export type LocationConnectionFilter = "all" | "connected" | "not-connected";
export type LocationSortBy = "name" | "updated" | "created" | "connection";

export interface LocationQueryParams {
  page?: number;
  perPage?: number;
  search?: string;
  status?: LocationConnectionFilter;
  sortBy?: LocationSortBy;
  sortOrder?: "asc" | "desc";
}

const buildQueryString = (params: LocationQueryParams = {}) => {
  const query = new URLSearchParams();

  if (params.page) {
    query.set("page", String(params.page));
  }

  if (params.perPage) {
    query.set("per_page", String(params.perPage));
  }

  if (params.search?.trim()) {
    query.set("search", params.search.trim());
  }

  if (params.status === "connected") {
    query.set("is_gbp_connected", "true");
  }

  if (params.status === "not-connected") {
    query.set("is_gbp_connected", "false");
  }

  const sortFieldMap: Record<LocationSortBy, string> = {
    name: "name",
    updated: "updated_at",
    created: "created_at",
    connection: "is_gbp_connected",
  };

  if (params.sortBy) {
    const field = sortFieldMap[params.sortBy];
    const direction = params.sortOrder === "desc" ? `-${field}` : field;
    query.set("sort", direction);
  }

  const queryString = query.toString();
  return queryString ? `?${queryString}` : "";
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

const fetcher = async (url: string): Promise<LocationListResponse> => {
  const response = await fetch(url, buildRequestInit());

  if (!response.ok) {
    if (response.status === 401) {
      throw new Error("Please sign in to manage locations");
    }

    if (response.status === 403) {
      throw new Error("You do not have access to this site's locations");
    }

    if (response.status === 404) {
      throw new Error("Site not found");
    }

    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? "Failed to load locations");
  }

  const payload = await response.json();
  return normalizeLocationListResponse(payload);
};

const mapPayload = (payload: LocationMutationPayload) => ({
  name: payload.name,
  address: payload.address,
  phone: payload.phone ?? null,
  website: payload.website ?? null,
  category: payload.category ?? null,
  latitude: payload.latitude ?? null,
  longitude: payload.longitude ?? null,
});

const getLocationListKey = (siteId: string | number, params?: LocationQueryParams) =>
  buildApiUrl(`/sites/${siteId}/locations${buildQueryString(params)}`);

const getLocationDetailKey = (locationId: string | number) => buildApiUrl(`/locations/${locationId}`);

const refreshLocationCaches = async (siteId?: string | number, locationId?: number) => {
  const sitePrefix = siteId ? buildApiUrl(`/sites/${siteId}/locations`) : null;
  const genericLocationsSegment = "/locations";

  await globalMutate(
    (key) =>
      typeof key === "string" &&
      (Boolean(sitePrefix && key.startsWith(sitePrefix)) || key.includes(genericLocationsSegment)),
    undefined,
    { revalidate: true }
  );

  if (locationId) {
    await globalMutate(getLocationDetailKey(locationId));
  }
};

export function useLocations(siteId: string | number, params: LocationQueryParams = {}) {
  const key = siteId ? getLocationListKey(siteId, params) : null;

  const { data, error, isLoading, mutate } = useSWR<LocationListResponse>(key, fetcher);

  return {
    locations: data?.data ?? [],
    meta: data?.meta ?? defaultLocationMeta,
    isLoading,
    error,
    mutate,
    key,
  };
}

export function useLocation(id?: string | number) {
  const key = id ? getLocationDetailKey(id) : null;

  const { data, error, isLoading, mutate } = useSWR<BusinessLocation>(
    key,
    async (url: string) => {
      const response = await fetch(url, buildRequestInit());

      if (!response.ok) {
        if (response.status === 401) {
          throw new Error("Please sign in to view location details");
        }

        if (response.status === 404) {
          throw new Error("Location not found");
        }

        throw new Error("Failed to load location");
      }

      const payload = await response.json();
      return normalizeLocation(payload?.data ?? payload);
    }
  );

  return {
    location: data,
    error,
    isLoading,
    mutate,
  };
}

export function useCreateLocation(siteId: string | number) {
  const createLocation = useCallback(
    async (payload: LocationMutationPayload) => {
      const response = await fetch(
        getLocationListKey(siteId),
        buildRequestInit(
          {
            method: "POST",
            body: JSON.stringify(mapPayload(payload)),
          },
          true
        )
      );

      if (!response.ok) {
        const errorBody = await response.json().catch(() => null);
        throw new Error(errorBody?.message ?? "Failed to create location");
      }

      const data = await response.json();
      const created = normalizeLocation(data?.data ?? data);
      await refreshLocationCaches(siteId, created.id);
      return created;
    },
    [siteId]
  );

  return { createLocation };
}

export function useUpdateLocation() {
  const updateLocation = useCallback(async (id: number, payload: LocationMutationPayload) => {
    const response = await fetch(
      getLocationDetailKey(id),
      buildRequestInit(
        {
          method: "PUT",
          body: JSON.stringify(mapPayload(payload)),
        },
        true
      )
    );

    if (!response.ok) {
      const errorBody = await response.json().catch(() => null);
      throw new Error(errorBody?.message ?? "Failed to update location");
    }

    const data = await response.json();
    const updated = normalizeLocation(data?.data ?? data);
    await refreshLocationCaches(updated.siteId ?? undefined, updated.id);
    return updated;
  }, []);

  return { updateLocation };
}

export function useDeleteLocation(siteId?: string | number) {
  const deleteLocation = useCallback(
    async (id: number) => {
      const response = await fetch(
        getLocationDetailKey(id),
        buildRequestInit({
          method: "DELETE",
        })
      );

      if (!response.ok && response.status !== 204) {
        const errorBody = await response.json().catch(() => null);
        throw new Error(errorBody?.message ?? "Failed to delete location");
      }

      await refreshLocationCaches(siteId, id);
    },
    [siteId]
  );

  return { deleteLocation };
}
