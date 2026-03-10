import { normalizeLocation } from "@/lib/types/locations";
import type {
  AlertItem,
  AlertSummary,
  CompetitorPreview,
  DashboardLinks,
  DroppedKeyword,
  GBPInsights,
  LocalRankings,
  LocationDashboardLocation,
  MetricSeries,
  QuickStatsSnapshot,
  RankingKeyword,
  ReviewPreview,
  ReviewSentiment,
  ReviewStats,
  TrendPoint,
} from "@/lib/types/location-dashboard";

const compactFormatter = new Intl.NumberFormat("en-US", {
  notation: "compact",
  maximumFractionDigits: 1,
});

const numberFormatter = new Intl.NumberFormat();

const asRecord = (value: unknown): Record<string, unknown> => {
  if (value && typeof value === "object" && !Array.isArray(value)) {
    return value as Record<string, unknown>;
  }

  return {};
};

const asArray = <T = unknown>(value: unknown): T[] => (Array.isArray(value) ? (value as T[]) : []);

export const asNumber = (value: unknown, fallback = 0): number => {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : fallback;
  }

  if (typeof value === "string") {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  return fallback;
};

export const asNullableNumber = (value: unknown): number | null => {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  const parsed = asNumber(value, Number.NaN);
  return Number.isFinite(parsed) ? parsed : null;
};

export const asString = (value: unknown, fallback = ""): string => {
  if (typeof value !== "string") {
    return fallback;
  }

  return value.trim();
};

export const asNullableString = (value: unknown): string | null => {
  const next = asString(value, "");
  return next.length ? next : null;
};

const normalizeTrendPoint = (value: unknown, index: number): TrendPoint | null => {
  if (typeof value === "number") {
    return {
      label: `Day ${index + 1}`,
      value,
    };
  }

  const item = asRecord(value);
  const resolvedValue = asNullableNumber(item.value ?? item.total ?? item.count ?? item.metric);

  if (resolvedValue === null) {
    return null;
  }

  return {
    label:
      asNullableString(item.label ?? item.date ?? item.day ?? item.name) ?? `Day ${index + 1}`,
    value: resolvedValue,
  };
};

const normalizeBreakdown = (value: unknown): Record<string, number> => {
  const record = asRecord(value);

  return Object.entries(record).reduce<Record<string, number>>((accumulator, [key, rawValue]) => {
    const numeric = asNullableNumber(rawValue);

    if (numeric !== null) {
      accumulator[key] = numeric;
    }

    return accumulator;
  }, {});
};

const normalizeMetricSeries = (value: unknown): MetricSeries => {
  const record = asRecord(value);
  const trend = asArray(record.trend ?? record.series ?? record.points)
    .map(normalizeTrendPoint)
    .filter((item): item is TrendPoint => item !== null);

  return {
    total: asNumber(record.total ?? record.value ?? record.count),
    trend,
    breakdown: normalizeBreakdown(record.breakdown ?? record.parts ?? record.channels),
  };
};

export const normalizeDashboardLocation = (payload: unknown): LocationDashboardLocation => {
  const root = asRecord(payload);
  const raw = root.data && !Array.isArray(root.data) ? root.data : payload;
  const normalized = normalizeLocation(raw as any);
  const record = asRecord(raw);
  const rawInsightsSummary = asRecord(record.insights_summary ?? record.insightsSummary);

  return {
    ...normalized,
    lastSyncAt:
      asNullableString(
        record.last_sync_at ??
          record.lastSyncAt ??
          rawInsightsSummary.last_sync_at ??
          rawInsightsSummary.lastSyncAt ??
          normalized.insightsSummary?.lastSyncAt
      ) ?? null,
  };
};

export const normalizeGBPInsights = (payload: unknown): GBPInsights => {
  const root = asRecord(payload);
  const record = asRecord(root.data ?? payload);

  return {
    views: normalizeMetricSeries(record.views),
    searches: normalizeMetricSeries(record.searches),
    actions: normalizeMetricSeries(record.actions),
    period: asNullableString(record.period ?? record.range ?? record.window) ?? "7d",
  };
};

const normalizeReviewSentiment = (value: unknown): ReviewSentiment => {
  const record = asRecord(value);

  return {
    positive: asNumber(record.positive),
    neutral: asNumber(record.neutral),
    negative: asNumber(record.negative),
  };
};

const normalizeRatingDistribution = (value: unknown): Record<number, number> => {
  const record = asRecord(value);

  return [1, 2, 3, 4, 5].reduce<Record<number, number>>((accumulator, rating) => {
    accumulator[rating] = asNumber(record[String(rating)] ?? record[rating]);
    return accumulator;
  }, {});
};

export const normalizeReviewStats = (payload: unknown): ReviewStats => {
  const root = asRecord(payload);
  const record = asRecord(root.data ?? payload);

  return {
    average_rating: asNumber(record.average_rating ?? record.averageRating),
    total_count: asNumber(record.total_count ?? record.totalCount),
    rating_distribution: normalizeRatingDistribution(
      record.rating_distribution ?? record.ratingDistribution ?? record.distribution
    ),
    sentiment: normalizeReviewSentiment(record.sentiment ?? record.sentiment_breakdown),
  };
};

export const normalizeReviewPreview = (payload: unknown): ReviewPreview => {
  const record = asRecord(payload);
  const sentiment = asNullableString(record.sentiment)?.toLowerCase();

  return {
    id: asNullableString(record.id ?? record.gbp_review_id) ?? `review-${Math.random()}`,
    author: asNullableString(record.author ?? record.author_name ?? record.reviewer) ?? "Anonymous reviewer",
    rating: asNumber(record.rating),
    text: asNullableString(record.text ?? record.comment ?? record.review_text) ?? "",
    date:
      asNullableString(record.date ?? record.created_at ?? record.reviewed_at ?? record.timestamp) ??
      new Date().toISOString(),
    sentiment:
      sentiment === "negative" || sentiment === "neutral" || sentiment === "positive"
        ? sentiment
        : "neutral",
  };
};

export const normalizeReviewList = (payload: unknown): ReviewPreview[] => {
  const root = asRecord(payload);
  const list = asArray(root.data ?? root.reviews ?? payload);

  return list.map(normalizeReviewPreview).slice(0, 3);
};

const normalizeRankingKeyword = (payload: unknown): RankingKeyword => {
  const record = asRecord(payload);

  return {
    keyword: asNullableString(record.keyword ?? record.term) ?? "Untitled keyword",
    position: asNumber(record.position ?? record.current_position),
    change: asNumber(record.change ?? record.delta),
  };
};

const normalizeDroppedKeyword = (payload: unknown): DroppedKeyword => {
  const record = asRecord(payload);

  return {
    keyword: asNullableString(record.keyword ?? record.term) ?? "Untitled keyword",
    previous_position: asNumber(record.previous_position ?? record.previousPosition),
    current_position: asNumber(record.current_position ?? record.currentPosition),
  };
};

export const normalizeRankings = (payload: unknown): LocalRankings => {
  const root = asRecord(payload);
  const record = asRecord(root.data ?? payload);

  return {
    keywords_in_pack: asNumber(record.keywords_in_pack ?? record.in_local_pack ?? record.keywordsInPack),
    average_position: asNumber(record.average_position ?? record.averagePosition),
    top_keywords: asArray(record.top_keywords ?? record.topKeywords).map(normalizeRankingKeyword).slice(0, 3),
    dropped_keywords: asArray(record.dropped_keywords ?? record.needs_attention ?? record.needsAttention)
      .map(normalizeDroppedKeyword)
      .slice(0, 3),
  };
};

const normalizeAlertItem = (payload: unknown): AlertItem => {
  const record = asRecord(payload);
  const severity = asNullableString(record.severity)?.toLowerCase();

  return {
    id: asNullableString(record.id) ?? `alert-${Math.random()}`,
    type: asNullableString(record.type) ?? "alert",
    message: asNullableString(record.message ?? record.title) ?? "Alert triggered",
    severity:
      severity === "critical" || severity === "warning" || severity === "info"
        ? severity
        : "info",
    timestamp:
      asNullableString(record.timestamp ?? record.created_at ?? record.updated_at) ??
      new Date().toISOString(),
  };
};

export const normalizeAlertSummary = (payload: unknown): AlertSummary => {
  const root = asRecord(payload);
  const record = asRecord(root.data ?? payload);
  const latest = asArray(record.latest ?? record.alerts ?? record.items ?? payload)
    .map(normalizeAlertItem)
    .slice(0, 3);

  return {
    unread_count: asNumber(record.unread_count ?? record.unreadCount ?? latest.length),
    latest,
  };
};

export const normalizeCompetitors = (payload: unknown): CompetitorPreview[] => {
  const root = asRecord(payload);
  const list = asArray(root.data ?? root.competitors ?? payload);

  return list
    .map((item) => {
      const record = asRecord(item);

      return {
        name: asNullableString(record.name ?? record.business_name ?? record.title) ?? "Competitor",
        rating: asNumber(record.rating),
        review_count: asNumber(record.review_count ?? record.reviewCount),
        position: asNumber(record.position),
      } satisfies CompetitorPreview;
    })
    .slice(0, 3);
};

export const buildLocationDashboardLinks = (
  locationId: string | number,
  siteId?: string | number | null
): DashboardLinks => {
  if (siteId !== undefined && siteId !== null && siteId !== "") {
    const base = `/sites/${siteId}/local/${locationId}`;

    return {
      dashboard: base,
      insights: `${base}/insights`,
      reviews: `${base}/reviews`,
      rankings: `${base}/rankings`,
      alerts: `${base}/alerts`,
    };
  }

  const base = `/locations/${locationId}`;

  return {
    dashboard: base,
    insights: `${base}/insights`,
    reviews: `${base}/reviews`,
    rankings: `${base}/rankings`,
    alerts: `${base}/alerts`,
  };
};

export const formatNumber = (value: number | null | undefined) => {
  if (value === null || value === undefined || Number.isNaN(value)) {
    return "—";
  }

  return numberFormatter.format(value);
};

export const formatCompactNumber = (value: number | null | undefined) => {
  if (value === null || value === undefined || Number.isNaN(value)) {
    return "—";
  }

  return compactFormatter.format(value);
};

export const formatRating = (value: number | null | undefined) => {
  if (value === null || value === undefined || Number.isNaN(value)) {
    return "—";
  }

  return value.toFixed(1);
};

export const formatPosition = (value: number | null | undefined) => {
  if (value === null || value === undefined || Number.isNaN(value) || value <= 0) {
    return "—";
  }

  return value.toFixed(1);
};

export const formatDateTime = (value: string | null | undefined) => {
  if (!value) {
    return "Not synced yet";
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "Not synced yet";
  }

  return date.toLocaleString();
};

export const formatRelativeTime = (value: string | null | undefined) => {
  if (!value) {
    return "Not synced yet";
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "Not synced yet";
  }

  const diffMs = Date.now() - date.getTime();
  const diffMinutes = Math.round(diffMs / 60000);

  if (Math.abs(diffMinutes) < 1) {
    return "just now";
  }

  if (Math.abs(diffMinutes) < 60) {
    return `${Math.abs(diffMinutes)} minute${Math.abs(diffMinutes) === 1 ? "" : "s"} ${diffMinutes >= 0 ? "ago" : "from now"}`;
  }

  const diffHours = Math.round(diffMinutes / 60);

  if (Math.abs(diffHours) < 24) {
    return `${Math.abs(diffHours)} hour${Math.abs(diffHours) === 1 ? "" : "s"} ${diffHours >= 0 ? "ago" : "from now"}`;
  }

  const diffDays = Math.round(diffHours / 24);
  return `${Math.abs(diffDays)} day${Math.abs(diffDays) === 1 ? "" : "s"} ${diffDays >= 0 ? "ago" : "from now"}`;
};

export const computeTrendDelta = (series: TrendPoint[] | undefined): number | null => {
  if (!series || series.length < 2) {
    return null;
  }

  const first = series[0]?.value ?? 0;
  const last = series[series.length - 1]?.value ?? 0;

  if (first === 0) {
    return last === 0 ? 0 : 100;
  }

  return Number((((last - first) / Math.abs(first)) * 100).toFixed(1));
};

export const getDominantSentiment = (sentiment: ReviewSentiment | null | undefined) => {
  if (!sentiment) {
    return "neutral" as const;
  }

  const entries = [
    ["positive", sentiment.positive],
    ["neutral", sentiment.neutral],
    ["negative", sentiment.negative],
  ] as const;

  return [...entries].sort((left, right) => right[1] - left[1])[0][0];
};

export const getSeverityTone = (severity: AlertItem["severity"]) => {
  switch (severity) {
    case "critical":
      return "border-red-500/30 bg-red-500/10 text-red-100";
    case "warning":
      return "border-amber-500/30 bg-amber-500/10 text-amber-100";
    default:
      return "border-sky-500/30 bg-sky-500/10 text-sky-100";
  }
};

export const getSentimentTone = (sentiment: "positive" | "neutral" | "negative") => {
  switch (sentiment) {
    case "positive":
      return "border-emerald-500/30 bg-emerald-500/10 text-emerald-100";
    case "negative":
      return "border-red-500/30 bg-red-500/10 text-red-100";
    default:
      return "border-slate-700 bg-slate-900 text-slate-200";
  }
};

export const buildQuickStatsSnapshot = (
  insights: GBPInsights | null | undefined,
  reviewStats: ReviewStats | null | undefined,
  rankings: LocalRankings | null | undefined
): QuickStatsSnapshot => ({
  views: insights?.views.total ?? null,
  searches: insights?.searches.total ?? null,
  actions: insights?.actions.total ?? null,
  rating: reviewStats?.average_rating ?? null,
  reviewCount: reviewStats?.total_count ?? null,
  keywordsInPack: rankings?.keywords_in_pack ?? null,
  viewTrend: computeTrendDelta(insights?.views.trend),
  searchTrend: computeTrendDelta(insights?.searches.trend),
  actionsTrend: computeTrendDelta(insights?.actions.trend),
});
