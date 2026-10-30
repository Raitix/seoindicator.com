import useSWR from "swr";

import type { SERPFeatureType } from "@/lib/types/keywords";

const API_BASE = "/api/v1";

// SERP Feature metadata for display
export const SERP_FEATURE_META: Record<SERPFeatureType, {
  name: string;
  icon: string;
  description: string;
  ctrImpact: string;
}> = {
  featured_snippet: {
    name: "Featured Snippet",
    icon: "🏆",
    description: "Answer box at top of search results",
    ctrImpact: "+30-50%",
  },
  local_pack: {
    name: "Local Pack",
    icon: "📍",
    description: "Map with 3 local business listings",
    ctrImpact: "High for local",
  },
  image_pack: {
    name: "Image Pack",
    icon: "🖼️",
    description: "Horizontal row of images in results",
    ctrImpact: "Visual attraction",
  },
  video: {
    name: "Video Result",
    icon: "🎬",
    description: "Video thumbnail in search results",
    ctrImpact: "High engagement",
  },
  people_also_ask: {
    name: "People Also Ask",
    icon: "❓",
    description: "Expandable related questions",
    ctrImpact: "Secondary visibility",
  },
  knowledge_panel: {
    name: "Knowledge Panel",
    icon: "📋",
    description: "Entity information sidebar",
    ctrImpact: "Brand authority",
  },
  sitelinks: {
    name: "Sitelinks",
    icon: "🔗",
    description: "Additional page links under result",
    ctrImpact: "+20-30%",
  },
  reviews: {
    name: "Reviews/Ratings",
    icon: "⭐",
    description: "Star ratings displayed with result",
    ctrImpact: "+10-15%",
  },
  faq: {
    name: "FAQ Rich Result",
    icon: "📝",
    description: "FAQ accordion in search results",
    ctrImpact: "Increased visibility",
  },
  product: {
    name: "Product Listing",
    icon: "🛒",
    description: "Product rich result with price/availability",
    ctrImpact: "Shopping intent",
  },
};

export interface SERPFeatureHistoryItem {
  date: string;
  features: Partial<Record<SERPFeatureType, boolean>>;
}

export interface SERPFeatureChange {
  type: SERPFeatureType;
  action: "gained" | "lost";
  detectedAt: string;
}

export interface SERPFeatureCurrentFeature {
  type: SERPFeatureType;
  isPresent: boolean;
  url?: string;
  title?: string;
  snippet?: string;
}

export interface SERPFeatureKeywordData {
  id: number;
  keyword: string;
}

export interface SERPFeatureHistoryResponse {
  success: boolean;
  data: {
    keyword: SERPFeatureKeywordData;
    currentFeatures: SERPFeatureCurrentFeature[];
    history: SERPFeatureHistoryItem[];
    changes: SERPFeatureChange[];
  };
}

export interface SERPFeatureHistoryParams {
  from?: string;
  to?: string;
  days?: number;
}

const fetcher = async <T>(url: string): Promise<T> => {
  const response = await fetch(url);
  if (!response.ok) {
    const errorData = await response.json().catch(() => null);
    throw new Error(
      errorData?.error?.message || errorData?.message || `HTTP ${response.status}`
    );
  }
  return response.json();
};

/**
 * Hook to fetch SERP feature history for a keyword.
 * 
 * @param keywordId - The keyword ID to fetch features for
 * @param params - Optional query parameters (from, to, days)
 */
export function useSerpFeatureHistory(
  keywordId: number | null | undefined,
  params: SERPFeatureHistoryParams = {}
) {
  const queryParams = new URLSearchParams();
  
  if (params.from) {
    queryParams.set("from", params.from);
  }
  if (params.to) {
    queryParams.set("to", params.to);
  }
  if (params.days) {
    queryParams.set("days", String(params.days));
  }
  
  const queryString = queryParams.toString();
  const key = keywordId
    ? `${API_BASE}/keywords/${keywordId}/serp-features${queryString ? `?${queryString}` : ""}`
    : null;

  const { data, error, isLoading, mutate } = useSWR<SERPFeatureHistoryResponse>(
    key,
    fetcher
  );

  return {
    data: data?.success ? data.data : null,
    isLoading,
    error,
    mutate,
  };
}

/**
 * Get feature metadata by type.
 */
export function getFeatureMeta(type: SERPFeatureType) {
  return SERP_FEATURE_META[type] || {
    name: type.replace(/_/g, " "),
    icon: "📊",
    description: "SERP feature",
    ctrImpact: "Unknown",
  };
}

/**
 * Format date for display.
 */
export function formatFeatureDate(dateString: string): string {
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return dateString;
  
  return new Intl.DateTimeFormat("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  }).format(date);
}

/**
 * Get relative time string (e.g., "2 days ago").
 */
export function getRelativeTime(dateString: string): string {
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return dateString;
  
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  
  if (diffDays === 0) return "Today";
  if (diffDays === 1) return "Yesterday";
  if (diffDays < 7) return `${diffDays} days ago`;
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
  if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;
  return `${Math.floor(diffDays / 365)} years ago`;
}

/**
 * Check if a date is within the last N days.
 */
export function isWithinDays(dateString: string, days: number): boolean {
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return false;
  
  const now = new Date();
  const cutoff = new Date(now.getTime() - days * 24 * 60 * 60 * 1000);
  
  return date >= cutoff;
}
