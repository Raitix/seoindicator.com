// Admin Dashboard Components
// Task: TASK-27-14 - Admin Dashboard Layout and Routing
// Task: TASK-27-15 - Metric Cards and Overview Section
// Task: TASK-27-16 - Usage Charts and Tables

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
  getStatusIcon as getHealthStatusIcon,
} from "./HealthBadge";

export { MetricCard } from "./MetricCard";
export type { MetricCardProps, MetricFormat } from "./MetricCard";
export { formatNumber, formatCurrency, formatPercent, formatValue } from "./MetricCard";

export { MetricGrid } from "./MetricGrid";
export type { MetricGridProps, MetricCardData } from "./MetricGrid";

// Usage Charts and Tables (TASK-27-16)
export { UsageChart } from "./UsageChart";
export type { UsageChartProps, UsageDataPoint } from "./UsageChart";
export {
  formatDate as formatChartDate,
  formatNumber as formatChartNumber,
  calculateYMax,
} from "./UsageChart";

export { TokenDistributionChart } from "./TokenDistributionChart";
export type { TokenDistributionChartProps, TokenDistributionData } from "./TokenDistributionChart";
export {
  formatTokens,
  calculateTotal,
  getModelColor,
} from "./TokenDistributionChart";

export { TopEndpointsTable } from "./TopEndpointsTable";
export type { TopEndpointsTableProps, EndpointData } from "./TopEndpointsTable";
export {
  formatNumber as formatEndpointNumber,
  truncateEndpoint,
  formatResponseTime,
  formatRelativeTime,
  calculateProgressWidth,
} from "./TopEndpointsTable";

export { DateRangeSelector } from "./DateRangeSelector";
export type { DateRangeSelectorProps, PresetRange } from "./DateRangeSelector";
export {
  getPresetLabel,
  calculateDateRange,
  formatDateDisplay,
  isValidDateRange,
} from "./DateRangeSelector";

// Health Monitoring Components (TASK-27-17)
export { ServiceStatusIndicator } from "./ServiceStatusIndicator";
export type { ServiceStatusIndicatorProps, ServiceStatus } from "./ServiceStatusIndicator";
export {
  getStatusIcon as getServiceStatusIcon,
  getStatusLabel,
  formatLatency as formatServiceLatency,
  getRelativeTime,
  getStatusClasses,
} from "./ServiceStatusIndicator";

export { QueueStatusPanel } from "./QueueStatusPanel";
export type { QueueStatusPanelProps, QueueStatus } from "./QueueStatusPanel";
export {
  getQueueHealth,
  getPendingLevel,
  formatJobAge,
  getWorkerStatus,
  getOldestJobDisplay,
} from "./QueueStatusPanel";

export { DatabaseMetricsPanel } from "./DatabaseMetricsPanel";
export type { DatabaseMetricsPanelProps, DatabaseMetrics } from "./DatabaseMetricsPanel";
export {
  getConnectionPercentage,
  getConnectionStatus,
  getLatencyStatus,
  formatConnections,
  formatLatency as formatDatabaseLatency,
  formatQueryCount,
  getDatabaseHealth,
} from "./DatabaseMetricsPanel";

export { AlertsPanel } from "./AlertsPanel";
export type { AlertsPanelProps, Alert, AlertSeverity } from "./AlertsPanel";
export {
  getSeverityClasses,
  getSeverityIcon,
  getRelativeTime as getAlertRelativeTime,
  countBySeverity,
  sortAlerts,
} from "./AlertsPanel";

export { HealthOverview } from "./HealthOverview";
export type { HealthOverviewProps, HealthData, SystemMetrics } from "./HealthOverview";
export {
  getStatusMessage,
  getStatusHeaderClass,
  getStatusTextClass,
  countServicesByStatus,
  formatUptime,
  getTimeSinceUpdate,
  getProgressColor,
} from "./HealthOverview";
