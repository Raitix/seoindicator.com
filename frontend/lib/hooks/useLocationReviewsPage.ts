"use client";

import { useCallback } from "react";
import useSWR, { mutate as globalMutate } from "swr";

import { buildApiUrl } from "@/lib/api/base-url";
import { getStoredAuthToken } from "@/lib/api/auth";
import type {
  LocationReview,
  LocationReviewResponse,
  ReviewFilterState,
  ReviewListMeta,
  ReviewListResponse,
  ReviewSentiment,
  ReviewStatsDetailed,
} from "@/lib/types/location-reviews";

const FALLBACK_META: ReviewListMeta = {
  currentPage: 1,
  perPage: 20,
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

const asNullableNumber = (value: unknown): number | null => {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const parsed = asNumber(value, Number.NaN);
  return Number.isFinite(parsed) ? parsed : null;
};

const asString = (value: unknown, fallback = ""): string => {
  if (typeof value !== "string") {
    return fallback;
  }

  return value.trim();
};

const asNullableString = (value: unknown): string | null => {
  const next = asString(value, "");
  return next.length ? next : null;
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

const normalizeSentiment = (value: unknown, rating?: number): ReviewSentiment => {
  const sentiment = asNullableString(value)?.toLowerCase();

  if (sentiment === "positive" || sentiment === "neutral" || sentiment === "negative") {
    return sentiment;
  }

  if ((rating ?? 0) >= 4) {
    return "positive";
  }

  if ((rating ?? 0) <= 2) {
    return "negative";
  }

  return "neutral";
};

const normalizeReviewResponse = (value: unknown, parent?: Record<string, unknown>): LocationReviewResponse | null => {
  const record = asRecord(value);
  const parentRecord = parent ?? {};
  const text =
    asNullableString(record.text ?? record.response_text ?? record.comment) ??
    asNullableString(parentRecord.owner_response ?? parentRecord.response_text);

  if (!text) {
    return null;
  }

  return {
    text,
    respondedAt:
      asNullableString(record.responded_at ?? record.owner_response_date ?? record.created_at) ??
      asNullableString(parentRecord.responded_at ?? parentRecord.owner_response_date),
    responderName:
      asNullableString(record.responder_name ?? record.author ?? record.owner_name) ??
      asNullableString(parentRecord.responder_name),
  };
};

const buildGoogleReviewUrl = (record: Record<string, unknown>, gbpReviewId: string | null) => {
  return (
    asNullableString(record.google_url ?? record.review_url ?? record.url) ??
    (gbpReviewId ? `https://business.google.com/reviews/l/${encodeURIComponent(gbpReviewId)}` : null)
  );
};

export const normalizeLocationReview = (payload: unknown): LocationReview => {
  const record = asRecord(payload);
  const numericId = asNullableNumber(record.id);
  const rating = asNumber(record.rating);
  const gbpReviewId = asNullableString(record.gbp_review_id ?? record.google_review_id ?? record.googleReviewId);
  const response = normalizeReviewResponse(record.response, record);
  const reviewedAt =
    asNullableString(record.reviewed_at ?? record.review_date ?? record.date ?? record.created_at) ??
    new Date().toISOString();

  return {
    id: String(record.id ?? gbpReviewId ?? `review-${Math.random()}`),
    numericId,
    gbpReviewId,
    author: asNullableString(record.author ?? record.author_name ?? record.reviewer) ?? "Anonymous reviewer",
    avatarUrl: asNullableString(record.avatar_url ?? record.author_photo ?? record.authorPhoto),
    rating,
    text: asNullableString(record.text ?? record.review_text ?? record.comment) ?? "",
    sentiment: normalizeSentiment(record.sentiment ?? record.review_text_sentiment, rating),
    reviewedAt,
    hasResponse: asBoolean(record.has_response) || response !== null,
    response,
    googleUrl: buildGoogleReviewUrl(record, gbpReviewId),
    isLocalGuide: asBoolean(record.is_local_guide ?? record.local_guide),
    reviewCount: asNullableNumber(record.review_count ?? record.total_reviews),
  };
};

export const normalizeReviewStatsDetailed = (payload: unknown): ReviewStatsDetailed => {
  const root = asRecord(payload);
  const record = asRecord(root.data ?? payload);
  const distributionSource = asRecord(
    record.rating_distribution ?? record.distribution ?? record.ratingDistribution
  );
  const sentimentSource = asRecord(
    record.sentiment_breakdown ?? record.sentiment ?? record.sentimentBreakdown
  );

  return {
    averageRating: asNumber(record.average_rating ?? record.averageRating),
    previousRating: asNullableNumber(record.previous_rating ?? record.previousRating),
    totalCount: asNumber(record.total_count ?? record.totalCount),
    previousCount: asNullableNumber(record.previous_count ?? record.previousCount),
    ratingDistribution: [1, 2, 3, 4, 5].reduce<Record<number, number>>((accumulator, rating) => {
      accumulator[rating] = asNumber(distributionSource[String(rating)] ?? distributionSource[rating]);
      return accumulator;
    }, {}),
    sentimentBreakdown: {
      positive: asNumber(sentimentSource.positive),
      neutral: asNumber(sentimentSource.neutral),
      negative: asNumber(sentimentSource.negative),
    },
    responseRate: asNumber(record.response_rate ?? record.responseRate),
    averageResponseTimeHours: asNullableNumber(
      record.average_response_time_hours ?? record.averageResponseTimeHours
    ),
  };
};

const normalizeReviewListMeta = (payload: unknown): ReviewListMeta => {
  const record = asRecord(payload);

  return {
    currentPage: asNumber(record.current_page ?? record.currentPage, FALLBACK_META.currentPage),
    perPage: asNumber(record.per_page ?? record.perPage, FALLBACK_META.perPage),
    total: asNumber(record.total, FALLBACK_META.total),
    lastPage: asNumber(record.last_page ?? record.lastPage, FALLBACK_META.lastPage),
  };
};

export const normalizeReviewListResponse = (payload: unknown): ReviewListResponse => {
  const root = asRecord(payload);
  const reviews = asArray(root.data ?? root.reviews ?? payload).map(normalizeLocationReview);
  const meta = normalizeReviewListMeta(root.meta ?? root.pagination ?? {});

  return {
    reviews,
    meta: {
      currentPage: meta.currentPage || FALLBACK_META.currentPage,
      perPage: meta.perPage || FALLBACK_META.perPage,
      total: meta.total,
      lastPage: Math.max(meta.lastPage || 1, 1),
    },
  };
};

const fetchJson = async (url: string) => {
  const response = await fetch(url, buildRequestInit());

  if (!response.ok) {
    if (response.status === 401) {
      throw new Error("Please sign in to manage reviews");
    }

    if (response.status === 403) {
      throw new Error("You do not have access to this location's reviews");
    }

    if (response.status === 404) {
      throw new Error("Location or review not found");
    }

    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? "Failed to load review data");
  }

  return response.json().catch(() => null);
};

const toResponseStatusParam = (value: ReviewFilterState["responseStatus"]) => {
  switch (value) {
    case "responded":
      return "true";
    case "unresponded":
      return "false";
    default:
      return null;
  }
};

const buildReviewQuery = (
  filters: ReviewFilterState,
  page: number,
  perPage: number
) => {
  const query = new URLSearchParams();
  query.set("page", String(page));
  query.set("per_page", String(perPage));
  query.set("sort", filters.sort);

  if (filters.sentiment !== "all") {
    query.set("sentiment", filters.sentiment);
  }

  const responseParam = toResponseStatusParam(filters.responseStatus);
  if (responseParam !== null) {
    query.set("has_response", responseParam);
  }

  if (filters.startDate) {
    query.set("start_date", filters.startDate);
  }

  if (filters.endDate) {
    query.set("end_date", filters.endDate);
  }

  if (filters.ratings.length === 1) {
    query.set("rating", String(filters.ratings[0]));
  }

  if (filters.ratings.length > 1) {
    filters.ratings.forEach((rating) => query.append("ratings[]", String(rating)));
  }

  return query.toString() ? `?${query.toString()}` : "";
};

const getStatsKey = (locationId: string | number) => buildApiUrl(`/locations/${locationId}/reviews/stats`);
const getReviewsKey = (
  locationId: string | number,
  filters: ReviewFilterState,
  page: number,
  perPage: number
) => buildApiUrl(`/locations/${locationId}/reviews${buildReviewQuery(filters, page, perPage)}`);

const reviewsKeyPrefix = (locationId: string | number) => buildApiUrl(`/locations/${locationId}/reviews`);

export async function revalidateLocationReviewData(locationId?: string | number) {
  if (!locationId) {
    return;
  }

  const prefix = reviewsKeyPrefix(locationId);

  await globalMutate(
    (key) => typeof key === "string" && key.startsWith(prefix),
    undefined,
    { revalidate: true }
  );
}

export function useLocationReviewStatsDetailed(locationId?: string | number) {
  const key = locationId ? getStatsKey(locationId) : null;
  const { data, error, isLoading, mutate } = useSWR<ReviewStatsDetailed>(
    key,
    async (url: string) => normalizeReviewStatsDetailed(await fetchJson(url))
  );

  return {
    stats: data,
    error,
    isLoading,
    mutate,
  };
}

export function useLocationReviewPage(
  locationId: string | number | undefined,
  filters: ReviewFilterState,
  page: number,
  perPage: number
) {
  const key = locationId ? getReviewsKey(locationId, filters, page, perPage) : null;
  const { data, error, isLoading, mutate } = useSWR<ReviewListResponse>(
    key,
    async (url: string) => normalizeReviewListResponse(await fetchJson(url))
  );

  return {
    reviews: data?.reviews ?? [],
    meta: data?.meta ?? { ...FALLBACK_META, perPage },
    error,
    isLoading,
    mutate,
  };
}

export function useSubmitLocationReviewResponse(locationId?: string | number) {
  const submitResponse = useCallback(
    async (reviewId: string | number, text: string) => {
      if (!locationId) {
        throw new Error("Location ID is required to respond to a review");
      }

      const response = await fetch(
        buildApiUrl(`/locations/${locationId}/reviews/${reviewId}/respond`),
        buildRequestInit(
          {
            method: "POST",
            body: JSON.stringify({ text }),
          },
          true
        )
      );

      if (!response.ok) {
        const errorBody = await response.json().catch(() => null);
        throw new Error(errorBody?.message ?? "Failed to post review response");
      }

      const payload = await response.json().catch(() => null);
      await revalidateLocationReviewData(locationId);
      return payload;
    },
    [locationId]
  );

  return { submitResponse };
}

export function useSyncLocationReviews(locationId?: string | number) {
  const triggerSync = useCallback(async () => {
    if (!locationId) {
      throw new Error("Location ID is required to sync reviews");
    }

    const response = await fetch(
      buildApiUrl(`/locations/${locationId}/reviews/sync`),
      buildRequestInit({ method: "POST" })
    );

    if (!response.ok) {
      const errorBody = await response.json().catch(() => null);
      throw new Error(errorBody?.message ?? "Failed to sync reviews");
    }

    const payload = await response.json().catch(() => null);
    await revalidateLocationReviewData(locationId);
    return payload;
  }, [locationId]);

  return { triggerSync };
}
