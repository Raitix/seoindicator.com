export interface LocationReviewStats {
  averageRating: number | null;
  totalReviews: number | null;
  responseRate: number | null;
}

export interface LocationInsightsSummary {
  totalQueries: number | null;
  totalViews: number | null;
  totalActions: number | null;
  lastSyncAt: string | null;
}

export interface BusinessLocation {
  id: number;
  siteId: number | null;
  name: string;
  address: string;
  phone: string | null;
  website: string | null;
  category: string | null;
  latitude: number | null;
  longitude: number | null;
  isGbpConnected: boolean;
  gbpLocationId: string | null;
  isActive: boolean;
  createdAt: string | null;
  updatedAt: string | null;
  reviewStats: LocationReviewStats | null;
  insightsSummary: LocationInsightsSummary | null;
  rating: number | null;
  reviewCount: number | null;
  packPosition: number | null;
}

export interface LocationListMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface LocationListResponse {
  data: BusinessLocation[];
  meta: LocationListMeta;
}

export interface LocationMutationPayload {
  name: string;
  address: string;
  phone?: string | null;
  website?: string | null;
  category?: string | null;
  latitude?: number | null;
  longitude?: number | null;
}

export const defaultLocationMeta: LocationListMeta = {
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1,
};

const asNumber = (value: unknown): number | null => {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  if (typeof value === "number") {
    return Number.isFinite(value) ? value : null;
  }

  if (typeof value === "string") {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
  }

  return null;
};

const asString = (value: unknown): string | null => {
  if (typeof value !== "string") {
    return null;
  }

  const trimmed = value.trim();
  return trimmed.length > 0 ? trimmed : null;
};

const asBoolean = (value: unknown): boolean => {
  if (typeof value === "boolean") {
    return value;
  }

  if (typeof value === "number") {
    return value === 1;
  }

  if (typeof value === "string") {
    return ["1", "true", "yes", "connected"].includes(value.toLowerCase());
  }

  return false;
};

export const normalizeLocation = (item: any): BusinessLocation => {
  const rawReviewStats = item?.review_stats ?? item?.reviewStats ?? null;
  const rawInsightsSummary = item?.insights_summary ?? item?.insightsSummary ?? null;

  const reviewStats: LocationReviewStats | null = rawReviewStats
    ? {
        averageRating: asNumber(rawReviewStats.average_rating ?? rawReviewStats.averageRating),
        totalReviews: asNumber(rawReviewStats.total_reviews ?? rawReviewStats.totalReviews),
        responseRate: asNumber(rawReviewStats.response_rate ?? rawReviewStats.responseRate),
      }
    : null;

  const insightsSummary: LocationInsightsSummary | null = rawInsightsSummary
    ? {
        totalQueries: asNumber(rawInsightsSummary.total_queries ?? rawInsightsSummary.totalQueries),
        totalViews: asNumber(rawInsightsSummary.total_views ?? rawInsightsSummary.totalViews),
        totalActions: asNumber(rawInsightsSummary.total_actions ?? rawInsightsSummary.totalActions),
        lastSyncAt:
          asString(rawInsightsSummary.last_sync_at ?? rawInsightsSummary.lastSyncAt) ?? null,
      }
    : null;

  return {
    id: asNumber(item?.id) ?? 0,
    siteId: asNumber(item?.site_id ?? item?.siteId),
    name: asString(item?.name) ?? "Untitled location",
    address: asString(item?.address) ?? "",
    phone: asString(item?.phone),
    website: asString(item?.website),
    category: asString(item?.category),
    latitude: asNumber(item?.latitude),
    longitude: asNumber(item?.longitude),
    isGbpConnected: asBoolean(item?.is_gbp_connected ?? item?.gbp_connected ?? item?.isGbpConnected),
    gbpLocationId: asString(item?.gbp_location_id ?? item?.gbp_place_id ?? item?.gbpLocationId),
    isActive: item?.is_active === undefined ? true : asBoolean(item?.is_active ?? item?.isActive),
    createdAt: asString(item?.created_at ?? item?.createdAt),
    updatedAt: asString(item?.updated_at ?? item?.updatedAt),
    reviewStats,
    insightsSummary,
    rating: asNumber(item?.rating ?? reviewStats?.averageRating),
    reviewCount: asNumber(item?.review_count ?? reviewStats?.totalReviews),
    packPosition: asNumber(item?.pack_position ?? item?.local_pack_position ?? item?.packPosition),
  };
};

export const normalizeLocationListResponse = (payload: any): LocationListResponse => {
  if (Array.isArray(payload)) {
    return {
      data: payload.map(normalizeLocation),
      meta: {
        ...defaultLocationMeta,
        total: payload.length,
        last_page: 1,
      },
    };
  }

  const rawData = Array.isArray(payload?.data) ? payload.data : [];
  const rawMeta = payload?.meta ?? payload?.pagination ?? {};

  return {
    data: rawData.map(normalizeLocation),
    meta: {
      current_page: asNumber(rawMeta.current_page ?? rawMeta.page) ?? defaultLocationMeta.current_page,
      per_page: asNumber(rawMeta.per_page ?? rawMeta.perPage) ?? defaultLocationMeta.per_page,
      total: asNumber(rawMeta.total) ?? rawData.length,
      last_page: asNumber(rawMeta.last_page ?? rawMeta.lastPage) ?? 1,
    },
  };
};
