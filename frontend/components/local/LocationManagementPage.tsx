"use client";

import * as React from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Building2, MapPin, Plus, RefreshCw, Search, SlidersHorizontal, Star } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { LocationCard, LocationCardSkeleton } from "@/components/local/LocationCard";
import { LocationForm } from "@/components/local/LocationForm";
import { DeleteLocationDialog } from "@/components/local/DeleteLocationDialog";
import {
  useCreateLocation,
  useDeleteLocation,
  useLocations,
  useUpdateLocation,
  type LocationConnectionFilter,
} from "@/lib/hooks/useLocations";
import type {
  BusinessLocation,
  LocationListMeta,
  LocationListResponse,
  LocationMutationPayload,
} from "@/lib/types/locations";

export interface LocationManagementPageProps {
  siteId: string;
}

type SortOption = "updated" | "name" | "connection" | "rating" | "reviews" | "pack-position";

const makeTempLocation = (payload: LocationMutationPayload, siteId: string): BusinessLocation => ({
  id: -Date.now(),
  siteId: Number(siteId),
  name: payload.name,
  address: payload.address,
  phone: payload.phone ?? null,
  website: payload.website ?? null,
  category: payload.category ?? null,
  latitude: payload.latitude ?? null,
  longitude: payload.longitude ?? null,
  isGbpConnected: false,
  gbpLocationId: null,
  isActive: true,
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  reviewStats: null,
  insightsSummary: null,
  rating: null,
  reviewCount: null,
  packPosition: null,
});

const ensureResponse = (
  current: LocationListResponse | undefined,
  fallbackMeta?: Partial<LocationListMeta>
): LocationListResponse => ({
  data: current?.data ?? [],
  meta: {
    current_page: current?.meta?.current_page ?? fallbackMeta?.current_page ?? 1,
    per_page: current?.meta?.per_page ?? fallbackMeta?.per_page ?? 15,
    total: current?.meta?.total ?? fallbackMeta?.total ?? 0,
    last_page: current?.meta?.last_page ?? fallbackMeta?.last_page ?? 1,
  },
});

const withCreatedLocation = (
  current: LocationListResponse | undefined,
  location: BusinessLocation
): LocationListResponse => {
  const snapshot = ensureResponse(current);
  const alreadyExists = snapshot.data.some((item) => item.id === location.id);

  return {
    data: [location, ...snapshot.data.filter((item) => item.id !== location.id)],
    meta: {
      ...snapshot.meta,
      total: alreadyExists ? snapshot.meta.total : snapshot.meta.total + 1,
    },
  };
};

const replaceOptimisticLocation = (
  current: LocationListResponse | undefined,
  optimisticId: number,
  location: BusinessLocation
): LocationListResponse => {
  const snapshot = ensureResponse(current);
  const withoutOptimistic = snapshot.data.filter((item) => item.id !== optimisticId && item.id !== location.id);

  return {
    data: [location, ...withoutOptimistic],
    meta: snapshot.meta,
  };
};

const withUpdatedLocation = (
  current: LocationListResponse | undefined,
  location: BusinessLocation
): LocationListResponse => {
  const snapshot = ensureResponse(current);

  return {
    data: snapshot.data.map((item) => (item.id === location.id ? location : item)),
    meta: snapshot.meta,
  };
};

const withoutLocation = (
  current: LocationListResponse | undefined,
  locationId: number
): LocationListResponse => {
  const snapshot = ensureResponse(current);
  const nextData = snapshot.data.filter((item) => item.id !== locationId);
  const removed = snapshot.data.length !== nextData.length;

  return {
    data: nextData,
    meta: {
      ...snapshot.meta,
      total: removed ? Math.max(0, snapshot.meta.total - 1) : snapshot.meta.total,
    },
  };
};

const sortLocations = (locations: BusinessLocation[], sortBy: SortOption) => {
  const collator = new Intl.Collator(undefined, { sensitivity: "base", numeric: true });
  const toNumber = (value: number | null) => (value === null || Number.isNaN(value) ? -Infinity : value);
  const toDate = (value: string | null) => (value ? Date.parse(value) : 0);

  return [...locations].sort((left, right) => {
    switch (sortBy) {
      case "name":
        return collator.compare(left.name, right.name);
      case "connection":
        return Number(right.isGbpConnected) - Number(left.isGbpConnected) || collator.compare(left.name, right.name);
      case "rating":
        return toNumber(right.rating) - toNumber(left.rating) || collator.compare(left.name, right.name);
      case "reviews":
        return toNumber(right.reviewCount) - toNumber(left.reviewCount) || collator.compare(left.name, right.name);
      case "pack-position": {
        const leftValue = left.packPosition === null ? Number.POSITIVE_INFINITY : left.packPosition;
        const rightValue = right.packPosition === null ? Number.POSITIVE_INFINITY : right.packPosition;
        return leftValue - rightValue || collator.compare(left.name, right.name);
      }
      case "updated":
      default:
        return toDate(right.updatedAt) - toDate(left.updatedAt) || collator.compare(left.name, right.name);
    }
  });
};

function SummaryCard({
  title,
  value,
  hint,
  icon: Icon,
}: {
  title: string;
  value: string | number;
  hint: string;
  icon: React.ComponentType<{ className?: string }>;
}) {
  return (
    <Card className="border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10">
      <CardContent className="flex items-start justify-between gap-3 px-6 py-5">
        <div>
          <div className="text-sm text-slate-400">{title}</div>
          <div className="mt-2 text-3xl font-semibold text-white">{value}</div>
          <div className="mt-1 text-xs text-slate-500">{hint}</div>
        </div>
        <div className="rounded-xl border border-slate-800 bg-slate-900/80 p-2.5 text-slate-300">
          <Icon className="h-5 w-5" />
        </div>
      </CardContent>
    </Card>
  );
}

export function LocationManagementPage({ siteId }: LocationManagementPageProps) {
  const router = useRouter();
  const [searchInput, setSearchInput] = React.useState("");
  const [debouncedSearch, setDebouncedSearch] = React.useState("");
  const [statusFilter, setStatusFilter] = React.useState<LocationConnectionFilter>("all");
  const [sortBy, setSortBy] = React.useState<SortOption>("updated");
  const [page, setPage] = React.useState(1);
  const [isAddOpen, setIsAddOpen] = React.useState(false);
  const [editingLocation, setEditingLocation] = React.useState<BusinessLocation | null>(null);
  const [deletingLocation, setDeletingLocation] = React.useState<BusinessLocation | null>(null);
  const [activeMutation, setActiveMutation] = React.useState<"create" | "update" | "delete" | null>(null);

  React.useEffect(() => {
    const timeout = window.setTimeout(() => setDebouncedSearch(searchInput.trim()), 250);
    return () => window.clearTimeout(timeout);
  }, [searchInput]);

  React.useEffect(() => {
    setPage(1);
  }, [debouncedSearch, statusFilter, sortBy]);

  const hookSortBy = sortBy === "name" || sortBy === "updated" || sortBy === "connection" ? sortBy : undefined;

  const { locations, meta, isLoading, error, mutate } = useLocations(siteId, {
    page,
    search: debouncedSearch || undefined,
    status: statusFilter,
    sortBy: hookSortBy,
    sortOrder: sortBy === "name" ? "asc" : "desc",
  });

  const { createLocation } = useCreateLocation(siteId);
  const { updateLocation } = useUpdateLocation();
  const { deleteLocation } = useDeleteLocation(siteId);

  const displayedLocations = React.useMemo(() => {
    const filtered = locations.filter((location) => {
      const needle = searchInput.trim().toLowerCase();

      if (statusFilter === "connected" && !location.isGbpConnected) {
        return false;
      }

      if (statusFilter === "not-connected" && location.isGbpConnected) {
        return false;
      }

      if (!needle) {
        return true;
      }

      return [location.name, location.address, location.category, location.phone, location.website]
        .filter(Boolean)
        .some((value) => value!.toLowerCase().includes(needle));
    });

    return sortLocations(filtered, sortBy);
  }, [locations, searchInput, sortBy, statusFilter]);

  const connectedCount = locations.filter((location) => location.isGbpConnected).length;
  const unconnectedCount = locations.length - connectedCount;
  const averageRating = React.useMemo(() => {
    const values = locations.map((location) => location.rating).filter((value): value is number => value !== null);
    if (values.length === 0) {
      return "—";
    }

    const total = values.reduce((sum, value) => sum + value, 0);
    return (total / values.length).toFixed(1);
  }, [locations]);

  const handleCreate = async (payload: LocationMutationPayload) => {
    const optimisticLocation = makeTempLocation(payload, siteId);
    setActiveMutation("create");

    try {
      await mutate(
        async (current) => {
          const created = await createLocation(payload);
          return replaceOptimisticLocation(current, optimisticLocation.id, created);
        },
        {
          optimisticData: (current) => withCreatedLocation(current, optimisticLocation),
          rollbackOnError: true,
          populateCache: true,
          revalidate: false,
        }
      );

      setIsAddOpen(false);
      toast.success("Location created");
    } catch (mutationError) {
      const message = mutationError instanceof Error ? mutationError.message : "Failed to create location";
      toast.error(message);
      throw mutationError;
    } finally {
      setActiveMutation(null);
    }
  };

  const handleUpdate = async (payload: LocationMutationPayload) => {
    if (!editingLocation) {
      return;
    }

    const optimisticLocation: BusinessLocation = {
      ...editingLocation,
      name: payload.name,
      address: payload.address,
      phone: payload.phone ?? null,
      website: payload.website ?? null,
      category: payload.category ?? null,
      latitude: payload.latitude ?? editingLocation.latitude,
      longitude: payload.longitude ?? editingLocation.longitude,
      updatedAt: new Date().toISOString(),
    };

    setActiveMutation("update");

    try {
      await mutate(
        async (current) => {
          const updated = await updateLocation(editingLocation.id, payload);
          return withUpdatedLocation(current, updated);
        },
        {
          optimisticData: (current) => withUpdatedLocation(current, optimisticLocation),
          rollbackOnError: true,
          populateCache: true,
          revalidate: false,
        }
      );

      setEditingLocation(null);
      toast.success("Location updated");
    } catch (mutationError) {
      const message = mutationError instanceof Error ? mutationError.message : "Failed to update location";
      toast.error(message);
      throw mutationError;
    } finally {
      setActiveMutation(null);
    }
  };

  const handleDelete = async (location: BusinessLocation) => {
    setActiveMutation("delete");

    try {
      await mutate(
        async (current) => {
          await deleteLocation(location.id);
          return withoutLocation(current, location.id);
        },
        {
          optimisticData: (current) => withoutLocation(current, location.id),
          rollbackOnError: true,
          populateCache: true,
          revalidate: false,
        }
      );

      setDeletingLocation(null);
      toast.success("Location deleted");
    } catch (mutationError) {
      const message = mutationError instanceof Error ? mutationError.message : "Failed to delete location";
      toast.error(message);
      throw mutationError;
    } finally {
      setActiveMutation(null);
    }
  };

  const hasNoLocations = !isLoading && meta.total === 0 && locations.length === 0;
  const hasNoFilteredResults = !isLoading && !hasNoLocations && displayedLocations.length === 0;

  return (
    <div className="space-y-6">
      <div className="space-y-4 rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-950 via-slate-950 to-slate-900 p-6 shadow-2xl shadow-slate-950/20">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div className="space-y-3">
            <nav className="flex flex-wrap items-center gap-2 text-sm text-slate-400">
              <Link href="/sites" className="transition hover:text-slate-200">
                Sites
              </Link>
              <span>/</span>
              <Link href={`/sites/${siteId}`} className="transition hover:text-slate-200">
                Site #{siteId}
              </Link>
              <span>/</span>
              <span className="text-slate-200">Local locations</span>
            </nav>

            <div>
              <h1 className="text-3xl font-semibold tracking-tight text-white">Location management</h1>
              <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-400">
                Manage business locations, monitor GBP connection status, and keep the Local SEO data layer ready for ranking and review analysis.
              </p>
            </div>
          </div>

          <Button className="w-full sm:w-auto" onClick={() => setIsAddOpen(true)}>
            <Plus className="h-4 w-4" aria-hidden="true" />
            Add location
          </Button>
        </div>

        <div className="grid gap-4 md:grid-cols-3">
          <SummaryCard title="Tracked locations" value={meta.total || locations.length} hint="All locations currently linked to this site." icon={Building2} />
          <SummaryCard title="GBP connected" value={connectedCount} hint="Locations already connected to Google Business Profile." icon={MapPin} />
          <SummaryCard title="Average rating" value={averageRating} hint="Based on available review aggregates in the loaded dataset." icon={Star} />
        </div>
      </div>

      <Card className="border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10">
        <CardHeader className="gap-4 border-b border-slate-800/80 pb-5">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <CardTitle className="text-white">Browse locations</CardTitle>
              <p className="mt-1 text-sm text-slate-400">
                Search, filter, and jump into location details without leaving this page.
              </p>
            </div>

            <div className="flex flex-wrap items-center gap-2 text-xs text-slate-400">
              <span className="rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5">{connectedCount} connected</span>
              <span className="rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5">{unconnectedCount} not connected</span>
            </div>
          </div>

          <div className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_220px_auto]">
            <div className="relative">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" aria-hidden="true" />
              <Input
                value={searchInput}
                onChange={(event) => setSearchInput(event.target.value)}
                placeholder="Search by name, address, phone, or category"
                aria-label="Search locations"
                className="border-slate-700 bg-slate-900 pl-10 text-slate-100"
              />
            </div>

            <label className="flex items-center gap-2 rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-300 focus-within:ring-2 focus-within:ring-blue-500/40">
              <SlidersHorizontal className="h-4 w-4 text-slate-500" aria-hidden="true" />
              <select
                value={statusFilter}
                onChange={(event) => setStatusFilter(event.target.value as LocationConnectionFilter)}
                aria-label="Filter by GBP connection status"
                className="w-full bg-transparent text-sm outline-none"
              >
                <option value="all">All statuses</option>
                <option value="connected">Connected</option>
                <option value="not-connected">Not connected</option>
              </select>
            </label>

            <label className="flex items-center gap-2 rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-300 focus-within:ring-2 focus-within:ring-blue-500/40">
              <SlidersHorizontal className="h-4 w-4 text-slate-500" aria-hidden="true" />
              <select
                value={sortBy}
                onChange={(event) => setSortBy(event.target.value as SortOption)}
                aria-label="Sort locations"
                className="w-full bg-transparent text-sm outline-none"
              >
                <option value="updated">Recently updated</option>
                <option value="name">Name</option>
                <option value="connection">Connection status</option>
                <option value="rating">Rating</option>
                <option value="reviews">Review count</option>
                <option value="pack-position">Pack position</option>
              </select>
            </label>

            <Button
              type="button"
              variant="outline"
              className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800"
              disabled={isLoading}
              onClick={() => {
                void mutate();
              }}
            >
              <RefreshCw className={`h-4 w-4 ${isLoading ? "animate-spin" : ""}`} aria-hidden="true" />
              Refresh
            </Button>
          </div>
        </CardHeader>

        <CardContent className="pt-6">
          {error ? (
            <div className="rounded-2xl border border-red-500/20 bg-red-500/10 p-6 text-red-50">
              <div className="text-lg font-medium">Failed to load locations</div>
              <p className="mt-2 text-sm text-red-100/90">{error.message}</p>
              <Button className="mt-4" variant="outline" onClick={() => void mutate()}>
                Try again
              </Button>
            </div>
          ) : hasNoLocations ? (
            <div className="rounded-2xl border border-dashed border-slate-700 bg-slate-900/60 px-6 py-14 text-center">
              <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-800 bg-slate-950 text-slate-300">
                <MapPin className="h-6 w-6" aria-hidden="true" />
              </div>
              <h2 className="mt-5 text-xl font-semibold text-white">No locations yet</h2>
              <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-400">
                Add your first business location to start tracking GBP connectivity, reviews, and local pack performance.
              </p>
              <Button className="mt-6" onClick={() => setIsAddOpen(true)}>
                <Plus className="h-4 w-4" aria-hidden="true" />
                Add your first location
              </Button>
            </div>
          ) : hasNoFilteredResults ? (
            <div className="rounded-2xl border border-slate-800 bg-slate-900/50 px-6 py-12 text-center">
              <h2 className="text-lg font-semibold text-white">No locations match the current filters</h2>
              <p className="mt-2 text-sm text-slate-400">Try clearing the search or resetting the status and sorting filters.</p>
              <Button
                className="mt-4"
                variant="outline"
                onClick={() => {
                  setSearchInput("");
                  setStatusFilter("all");
                  setSortBy("updated");
                }}
              >
                Reset filters
              </Button>
            </div>
          ) : (
            <div className="space-y-4">
              {isLoading && displayedLocations.length === 0 ? (
                Array.from({ length: 3 }).map((_, index) => <LocationCardSkeleton key={index} />)
              ) : (
                displayedLocations.map((location) => (
                  <LocationCard
                    key={location.id}
                    siteId={siteId}
                    location={location}
                    isDeleting={activeMutation === "delete" && deletingLocation?.id === location.id}
                    onViewDetails={(item) => router.push(`/sites/${siteId}/local/${item.id}`)}
                    onEdit={(item) => setEditingLocation(item)}
                    onDelete={(item) => setDeletingLocation(item)}
                  />
                ))
              )}
            </div>
          )}

          {meta.last_page > 1 ? (
            <div className="mt-6 flex flex-col items-center justify-between gap-3 border-t border-slate-800 pt-6 sm:flex-row">
              <div className="text-sm text-slate-400">
                Page {meta.current_page} of {meta.last_page}
              </div>
              <div className="flex items-center gap-2">
                <Button
                  type="button"
                  variant="outline"
                  className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800"
                  disabled={page <= 1 || isLoading}
                  onClick={() => setPage((current) => Math.max(1, current - 1))}
                >
                  Previous
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800"
                  disabled={page >= meta.last_page || isLoading}
                  onClick={() => setPage((current) => Math.min(meta.last_page, current + 1))}
                >
                  Next
                </Button>
              </div>
            </div>
          ) : null}
        </CardContent>
      </Card>

      <LocationForm
        open={isAddOpen}
        mode="add"
        isSubmitting={activeMutation === "create"}
        onClose={() => setIsAddOpen(false)}
        onSubmit={handleCreate}
      />

      <LocationForm
        open={Boolean(editingLocation)}
        mode="edit"
        location={editingLocation}
        isSubmitting={activeMutation === "update"}
        onClose={() => setEditingLocation(null)}
        onSubmit={handleUpdate}
      />

      <DeleteLocationDialog
        open={Boolean(deletingLocation)}
        location={deletingLocation}
        isDeleting={activeMutation === "delete"}
        onClose={() => setDeletingLocation(null)}
        onConfirm={handleDelete}
      />
    </div>
  );
}

export default LocationManagementPage;
