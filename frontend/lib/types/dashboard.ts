/**
 * Dashboard Types - TASK-25-19
 * 
 * TypeScript type definitions for dashboard data structures.
 * 
 * @see SPEC-025
 * @see TASK-25-19
 */

// ==================== Dashboard Stats ====================

export interface DashboardStats {
  /** Total number of analyses for user */
  totalAnalyses: number;
  
  /** Analyses in current month */
  thisMonth: number;
  
  /** Analyses in current week */
  thisWeek: number;
  
  /** Analyses today */
  today: number;
}

// ==================== Cost Summary ====================

export interface CostSummary {
  /** Total cost across all time */
  totalCost: number;
  
  /** Cost in current month */
  thisMonthCost: number;
  
  /** Currency code (ISO 4217) */
  currency: string;
  
  /** Optional trend indicator */
  trend?: 'up' | 'down' | 'stable';
  
  /** Optional percentage change */
  percentChange?: number;
}

// ==================== Analysis History ====================

export type AnalysisStatus = 'pending' | 'running' | 'completed' | 'failed';

export interface RecentAnalysis {
  /** Unique analysis ID */
  id: number;
  
  /** Machine-readable tool identifier */
  toolName: string;
  
  /** Human-readable tool name */
  toolDisplayName: string;
  
  /** Input URL that was analyzed */
  inputUrl: string;
  
  /** Analysis status */
  status: AnalysisStatus;
  
  /** When analysis was completed */
  analyzedAt: string; // ISO 8601 timestamp
  
  /** Duration in milliseconds (optional) */
  durationMs?: number;
  
  /** Cost for this analysis (optional) */
  cost?: {
    total: number;
    currency: string;
  };
}

// ==================== Quick Insights ====================

export interface QuickInsights {
  /** Most frequently used tool identifier */
  mostUsedTool: string;
  
  /** Display name for most used tool */
  mostUsedToolDisplayName: string;
  
  /** Average analysis duration in seconds */
  avgAnalysisTime: number;
  
  /** Success rate percentage (0-100) */
  successRate: number;
  
  /** Total keywords tracked (optional) */
  totalKeywords?: number;
  
  /** Average position (optional) */
  avgPosition?: number;
}

// ==================== Dashboard Response ====================

export interface DashboardData {
  /** Analysis statistics */
  stats: DashboardStats;
  
  /** Cost summary */
  costSummary: CostSummary | null;
  
  /** Recent analyses list */
  recentAnalyses: RecentAnalysis[];
  
  /** Quick insights */
  quickInsights: QuickInsights | null;
  
  /** Last updated timestamp */
  lastUpdated?: string;
}

// ==================== API Response ====================

export interface DashboardApiResponse {
  success: boolean;
  data: DashboardData;
  meta?: {
    cached: boolean;
    generatedAt: string;
  };
}

// ==================== Filter/Query Types ====================

export interface DashboardQueryParams {
  /** Number of recent analyses to fetch */
  recentLimit?: number;
  
  /** Include cached data */
  useCache?: boolean;
}

// ==================== User Context ====================

export interface DashboardUser {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  lastLoginAt?: string;
}

// ==================== Component Props ====================

export interface StatsCardProps {
  label: string;
  value: number;
  icon?: React.ReactNode;
  trend?: 'up' | 'down' | 'stable';
  trendValue?: number;
  loading?: boolean;
}

export interface CostCardProps {
  data: CostSummary | null;
  loading?: boolean;
  error?: Error | null;
}

export interface RecentAnalysesProps {
  analyses: RecentAnalysis[];
  loading?: boolean;
  error?: Error | null;
  onRowClick?: (analysis: RecentAnalysis) => void;
}

export interface QuickInsightsProps {
  insights: QuickInsights | null;
  loading?: boolean;
  error?: Error | null;
}

export interface QuickActionsProps {
  onNewAnalysis?: () => void;
  onViewHistory?: () => void;
}
