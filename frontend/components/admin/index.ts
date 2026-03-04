// Admin Dashboard Components
// Task: TASK-27-14 - Admin Dashboard Layout and Routing
// Task: TASK-27-15 - Metric Cards and Overview Section

export { AdminGuard, AdminUnauthorized, AdminForbidden } from "./AdminGuard";
export type { AdminGuardProps, AdminUnauthorizedProps, AdminForbiddenProps } from "./AdminGuard";

export { AdminNav } from "./AdminNav";
export type { AdminNavProps, NavItem } from "./AdminNav";

export { AdminLayout } from "./AdminLayout";
export type { AdminLayoutProps, Breadcrumb } from "./AdminLayout";

// Metric Components (TASK-27-15)
export { TrendIndicator } from "./TrendIndicator";
export type { TrendIndicatorProps } from "./TrendIndicator";
export {
  getTrendDirection,
  getTrendColorClass,
  formatTrendValue,
} from "./TrendIndicator";

export { HealthBadge } from "./HealthBadge";
export type { HealthBadgeProps, HealthStatus } from "./HealthBadge";
export {
  getStatusColorClass,
  getDefaultLabel,
  getStatusIcon,
} from "./HealthBadge";

export { MetricCard } from "./MetricCard";
export type { MetricCardProps, MetricFormat } from "./MetricCard";
export { formatNumber, formatCurrency, formatPercent, formatValue } from "./MetricCard";

export { MetricGrid } from "./MetricGrid";
export type { MetricGridProps, MetricCardData } from "./MetricGrid";
