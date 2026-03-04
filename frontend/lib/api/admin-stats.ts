/**
 * Admin Stats API Helper
 * Task: TASK-27-15 - Metric Cards and Overview Section
 * 
 * API functions for fetching admin dashboard statistics.
 */

import type {
  UserStats,
  UsageStats,
  CreditsStats,
  HealthStats,
  AdminOverviewData,
  AdminStatsResponse,
} from "@/lib/types/admin-stats";

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "/api/v1";

// ==================== Utility Functions ====================

async function adminFetch<T>(url: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(url, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      ...options.headers,
    },
    credentials: "include",
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: "Request failed" }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }

  const json: AdminStatsResponse<T> = await response.json();

  if (!json.success) {
    throw new Error(json.message || "Request failed");
  }

  return json.data;
}

// ==================== User Stats ====================

/**
 * Fetch user statistics
 */
export async function fetchUserStats(): Promise<UserStats> {
  return adminFetch<UserStats>(`${API_BASE}/admin/users/stats`);
}

// ==================== Usage Stats ====================

/**
 * Fetch usage statistics
 */
export async function fetchUsageStats(): Promise<UsageStats> {
  return adminFetch<UsageStats>(`${API_BASE}/admin/usage/stats`);
}

// ==================== Credits Stats ====================

/**
 * Fetch credits/revenue statistics
 */
export async function fetchCreditsStats(): Promise<CreditsStats> {
  return adminFetch<CreditsStats>(`${API_BASE}/admin/credits/stats`);
}

// ==================== Health Stats ====================

/**
 * Fetch system health status
 */
export async function fetchHealthStats(): Promise<HealthStats> {
  return adminFetch<HealthStats>(`${API_BASE}/admin/health`);
}

// ==================== Combined Overview ====================

/**
 * Fetch all admin overview data
 * Aggregates data from multiple endpoints
 */
export async function fetchAdminOverview(): Promise<AdminOverviewData> {
  // Fetch all stats in parallel
  const [users, usage, credits, health] = await Promise.all([
    fetchUserStats(),
    fetchUsageStats(),
    fetchCreditsStats(),
    fetchHealthStats(),
  ]);

  return {
    users,
    usage,
    credits,
    health,
    lastUpdated: new Date().toISOString(),
  };
}

// ==================== Fallback/Mock Data ====================

/**
 * Get mock data for development/testing
 */
export function getMockOverviewData(): AdminOverviewData {
  return {
    users: {
      total: 12345,
      active_today: 456,
      growth_rate: 12.5,
    },
    usage: {
      requests_24h: 98765,
      avg_response_time: 150,
      error_rate: 0.5,
      trend: -2.1,
    },
    credits: {
      revenue_month: 12345.67,
      credits_purchased: 50000,
      credits_used: 35000,
      trend: 18.3,
    },
    health: {
      status: "healthy",
      checks: {
        database: "healthy",
        cache: "healthy",
        queue: "healthy",
      },
    },
    lastUpdated: new Date().toISOString(),
  };
}

// ==================== React Query Keys ====================

export const adminKeys = {
  all: ["admin"] as const,
  overview: () => [...adminKeys.all, "overview"] as const,
  users: () => [...adminKeys.all, "users"] as const,
  usage: () => [...adminKeys.all, "usage"] as const,
  credits: () => [...adminKeys.all, "credits"] as const,
  health: () => [...adminKeys.all, "health"] as const,
};
