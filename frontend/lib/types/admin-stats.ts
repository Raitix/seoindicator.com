/**
 * Admin Stats Types
 * Task: TASK-27-15 - Metric Cards and Overview Section
 */

// ==================== User Stats ====================

export interface UserStats {
  total: number;
  active_today: number;
  growth_rate: number;
}

// ==================== Usage Stats ====================

export interface UsageStats {
  requests_24h: number;
  avg_response_time: number;
  error_rate: number;
  trend: number;
}

// ==================== Credits Stats ====================

export interface CreditsStats {
  revenue_month: number;
  credits_purchased: number;
  credits_used: number;
  trend: number;
}

// ==================== Health Stats ====================

export type HealthStatusType = "healthy" | "degraded" | "unhealthy" | "unknown";

export interface HealthCheck {
  name: string;
  status: HealthStatusType;
  latency?: number;
  message?: string;
}

export interface HealthStats {
  status: HealthStatusType;
  checks: Record<string, HealthStatusType | HealthCheck>;
  latency?: number;
}

// ==================== Admin Overview Data ====================

export interface AdminOverviewData {
  users: UserStats;
  usage: UsageStats;
  credits: CreditsStats;
  health: HealthStats;
  lastUpdated: string;
}

// ==================== API Response Types ====================

export interface AdminStatsResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

// ==================== Metric Card Data ====================

export interface MetricCardData {
  id: string;
  title: string;
  value: number | null;
  trend?: number | null;
  format?: "number" | "percent" | "currency";
  href?: string;
}
