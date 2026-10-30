/**
 * Analysis Types - TASK-25-20
 * 
 * TypeScript type definitions for analysis history data structures.
 * 
 * @see SPEC-025
 * @see TASK-25-20
 */

// ==================== Analysis Status ====================

export type AnalysisStatus = 'pending' | 'running' | 'completed' | 'failed';

// ==================== Analysis Record ====================

export interface AnalysisRecord {
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
  
  /** Duration in milliseconds */
  analysisDurationMs: number;
  
  /** Cost for this analysis */
  cost: {
    total: number;
    currency: string;
  };
  
  /** Error message if failed */
  errorMessage?: string;
}

// ==================== Analysis Detail ====================

export interface AnalysisCostBreakdown {
  type: string;
  amount: number;
  description?: string;
}

export interface AnalysisDetail extends AnalysisRecord {
  /** Full results JSON */
  results: Record<string, unknown>;
  
  /** Cost breakdown */
  costBreakdown: AnalysisCostBreakdown[];
  
  /** User ID */
  userId: number;
  
  /** Created at timestamp */
  createdAt: string;
  
  /** Updated at timestamp */
  updatedAt: string;
}

// ==================== Analysis List Response ====================

export interface AnalysisListMeta {
  total: number;
  page: number;
  perPage: number;
  lastPage: number;
  from: number;
  to: number;
}

export interface AnalysisListResponse {
  success: boolean;
  data: AnalysisRecord[];
  meta: AnalysisListMeta;
}

// ==================== Analysis Detail Response ====================

export interface AnalysisDetailResponse {
  success: boolean;
  data: AnalysisDetail;
}

// ==================== Filter Types ====================

export interface AnalysisFilters {
  /** Filter by tool name */
  tool?: string;
  
  /** Filter by status */
  status?: AnalysisStatus | '';
  
  /** Filter from date (Y-m-d) */
  from?: string;
  
  /** Filter to date (Y-m-d) */
  to?: string;
}

export interface AnalysisQueryParams extends AnalysisFilters {
  /** Page number (1-indexed) */
  page?: number;
  
  /** Items per page (max: 100) */
  perPage?: number;
}

// ==================== Tool Option ====================

export interface ToolOption {
  value: string;
  label: string;
}

// ==================== Status Option ====================

export interface StatusOption {
  value: AnalysisStatus | '' | 'all';
  label: string;
  color?: string;
}

// ==================== Constants ====================

export const STATUS_OPTIONS: StatusOption[] = [
  { value: 'all', label: 'All Statuses' },
  { value: 'completed', label: 'Completed', color: 'green' },
  { value: 'failed', label: 'Failed', color: 'red' },
  { value: 'pending', label: 'Pending', color: 'yellow' },
  { value: 'running', label: 'Running', color: 'blue' },
];

export const DEFAULT_TOOLS: ToolOption[] = [
  { value: 'all', label: 'All Tools' },
  { value: 'meta-tag-analyzer', label: 'Meta Tag Analyzer' },
  { value: 'keyword-density', label: 'Keyword Density Checker' },
  { value: 'mobile-friendly', label: 'Mobile-Friendly Checker' },
  { value: 'social-preview', label: 'Social Media Preview' },
];

// ==================== Component Props ====================

export interface HistoryFiltersProps {
  filters: AnalysisFilters;
  onFiltersChange: (filters: AnalysisFilters) => void;
  tools?: ToolOption[];
  onClear?: () => void;
}

export interface HistoryTableProps {
  analyses: AnalysisRecord[];
  isLoading?: boolean;
  onRowClick?: (analysis: AnalysisRecord) => void;
  onViewDetails?: (analysis: AnalysisRecord) => void;
  onDelete?: (analysis: AnalysisRecord) => void;
}

export interface AnalysisDetailsModalProps {
  analysis: AnalysisDetail | null;
  isOpen: boolean;
  onClose: () => void;
  isLoading?: boolean;
}

export interface DeleteConfirmationDialogProps {
  analysis: AnalysisRecord | null;
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  isDeleting?: boolean;
}

export interface PaginationProps {
  meta: AnalysisListMeta;
  onPageChange: (page: number) => void;
  onPerPageChange?: (perPage: number) => void;
}

export interface EmptyStateProps {
  hasFilters: boolean;
  onClearFilters?: () => void;
}

export interface HistorySkeletonProps {
  rows?: number;
}
